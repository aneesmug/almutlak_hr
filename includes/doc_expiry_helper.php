<?php
/**
 * Document expiry data + email layout (Employees > Document Expiry): ID/Iqama (= work
 * permit for expats, renewed together), and passport.
 *
 * - Dates come straight from `employees`: iqama_exp_g (Gregorian, 'Y-m-d' or 'Y/m/d')
 *   and passport_exp. Saudis (country code SA) carry a National ID instead of an Iqama
 *   and have no work permit.
 * - Who gets emailed, and when, is ONLY decided by the rules in App Settings > Org
 *   Structure > Document Expiry Alerts (includes/doc_expiry_rules_helper.php).
 *   doc_expiry_run_daily() runs them once a day from zk_sync_import.php (no cron on
 *   this server) and from the page's "Send alerts now" button. No rule = no email.
 * - Mail goes through memo_send_email() (main SMTP settings, App Settings > Email).
 */

if (defined('DOC_EXPIRY_HELPER_INCLUDED')) {
    return;
}
define('DOC_EXPIRY_HELPER_INCLUDED', true);

require_once __DIR__ . '/helper_functions.php';
require_once __DIR__ . '/memo_helper.php';

const DOC_EXPIRY_SAUDI_COUNTRY_CODE = 'SA';

/** 'Y-m-d' for a stored date string ('Y-m-d' / 'Y/m/d'), or null when empty/invalid. */
if (!function_exists('doc_expiry_normalize_date')) {
    function doc_expiry_normalize_date($raw) {
        $raw = trim(str_replace('/', '-', (string) $raw));
        if ($raw === '' || strpos($raw, '0000') === 0) {
            return null;
        }
        $d = DateTime::createFromFormat('!Y-m-d', $raw);
        if (!$d || $d->format('Y') < 1990 || $d->format('Y') > 2100) {
            return null;
        }
        return $d->format('Y-m-d');
    }
}

if (!function_exists('doc_expiry_doc_label')) {
    function doc_expiry_doc_label($docKey, $isSaudi, $lang = 'en') {
        if ($docKey === 'passport') {
            return $lang === 'ar' ? 'جواز السفر' : 'Passport';
        }
        if ($isSaudi) {
            return $lang === 'ar' ? 'الهوية الوطنية' : 'National ID';
        }
        return $lang === 'ar' ? 'الإقامة / رخصة العمل' : 'Iqama / Work Permit';
    }
}

/**
 * All active employees' documents with a valid expiry date, days-left and bucket.
 * $extraWhere: extra SQL on `e` (scope filters from getCompanyFilterSQL() etc.).
 * $maxDaysLeft: skip documents further out than this (null = keep all).
 */
if (!function_exists('doc_expiry_collect')) {
    function doc_expiry_collect($conDB, $extraWhere = '', $maxDaysLeft = null) {
        $sql = "SELECT e.id, e.emp_id, e.name, e.iqama, e.iqama_exp, e.iqama_exp_g, e.passport_number, e.passport_exp,
                       e.email, e.c_email, e.supervisor_id, e.dept, e.comp_no,
                       d.dep_nme, d.dep_nme_ar, c.code AS country_code, c.name AS country_name
                FROM employees e
                LEFT JOIN department d ON d.id = e.dept
                LEFT JOIN countries c ON c.id = e.country
                WHERE e.status = 1 " . $extraWhere . "
                ORDER BY e.name";
        $result = mysqli_query($conDB, $sql);
        if (!$result) {
            error_log('DOC_EXPIRY collect: ' . mysqli_error($conDB));
            return [];
        }

        $today = new DateTime('today');
        $rows = [];
        while ($emp = mysqli_fetch_assoc($result)) {
            $isSaudi = strtoupper((string) $emp['country_code']) === DOC_EXPIRY_SAUDI_COUNTRY_CODE;
            $docs = [
                'iqama' => [doc_expiry_normalize_date($emp['iqama_exp_g']), $emp['iqama'], $emp['iqama_exp']],
                'passport' => [doc_expiry_normalize_date($emp['passport_exp']), $emp['passport_number'], ''],
            ];
            foreach ($docs as $docKey => [$expiry, $docNo, $expiryHijri]) {
                if ($expiry === null) {
                    continue;
                }
                $daysLeft = (int) $today->diff(new DateTime($expiry))->format('%r%a');
                if ($maxDaysLeft !== null && $daysLeft > $maxDaysLeft) {
                    continue;
                }
                $rows[] = [
                    'id' => (int) $emp['id'],
                    'emp_id' => (string) $emp['emp_id'],
                    'name' => (string) $emp['name'],
                    'dept' => (string) $emp['dep_nme'],
                    'dept_ar' => (string) $emp['dep_nme_ar'],
                    'dept_id' => (string) $emp['dept'],
                    'country' => (string) $emp['country_name'],
                    'is_saudi' => $isSaudi,
                    'supervisor_id' => trim((string) $emp['supervisor_id']),
                    'emp_email' => (string) ($emp['email'] ?: $emp['c_email']),
                    'doc_key' => $docKey,
                    'doc_label' => doc_expiry_doc_label($docKey, $isSaudi),
                    'doc_label_ar' => doc_expiry_doc_label($docKey, $isSaudi, 'ar'),
                    'doc_no' => (string) $docNo,
                    'expiry' => $expiry,
                    'expiry_hijri' => (string) $expiryHijri,
                    'days_left' => $daysLeft,
                ];
            }
        }
        mysqli_free_result($result);
        return $rows;
    }
}

/** Last alert sent per "emp_id|doc_key|expiry", as ['bucket' => ..., 'sent_at' => ...]. */
if (!function_exists('doc_expiry_last_alerts')) {
    function doc_expiry_last_alerts($conDB) {
        require_once __DIR__ . '/doc_expiry_rules_helper.php';
        doc_expiry_rules_ensure_tables($conDB);
        $out = [];
        $result = mysqli_query($conDB, "SELECT ref_key, expiry_date, bucket, sent_at FROM doc_expiry_rule_log
            WHERE ref_key LIKE 'iqama:%' OR ref_key LIKE 'passport:%' ORDER BY sent_at");
        while ($result && ($r = mysqli_fetch_assoc($result))) {
            [$docKey, $empId] = explode(':', $r['ref_key'], 2);
            $out[$empId . '|' . $docKey . '|' . $r['expiry_date']] = ['bucket' => $r['bucket'], 'sent_at' => $r['sent_at']];
        }
        return $out;
    }
}

/**
 * First valid email for an emp_id: admin_login.email (system users), then
 * employees.email, then employees.c_email (normal employees have no login email),
 * then $fallback. '' when none is a valid address.
 */
if (!function_exists('doc_expiry_email_for_emp')) {
    function doc_expiry_email_for_emp($conDB, $empId, $fallback = '') {
        $empId = trim((string) $empId);
        $candidates = [];
        if ($empId !== '') {
            $candidates[] = memo_user_email($conDB, $empId);
            $stmt = $conDB->prepare("SELECT email, c_email FROM employees WHERE emp_id = ? ORDER BY status DESC LIMIT 1");
            $stmt->bind_param('s', $empId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc() ?: [];
            $stmt->close();
            $candidates[] = $row['email'] ?? '';
            $candidates[] = $row['c_email'] ?? '';
        }
        $candidates[] = $fallback;
        foreach ($candidates as $email) {
            $email = trim((string) $email);
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $email;
            }
        }
        return '';
    }
}

/* ---------- email layout ---------- */

if (!function_exists('doc_expiry_days_text')) {
    function doc_expiry_days_text($daysLeft) {
        if ($daysLeft < 0) {
            return 'Expired ' . abs($daysLeft) . ' day(s) ago | منتهية منذ ' . abs($daysLeft) . ' يوم';
        }
        if ($daysLeft === 0) {
            return 'Expires today | تنتهي اليوم';
        }
        return $daysLeft . ' day(s) left | متبقي ' . $daysLeft . ' يوم';
    }
}

if (!function_exists('doc_expiry_table_html')) {
    function doc_expiry_table_html(array $items) {
        $h = function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
        $th = 'style="padding:8px 10px;background:#25256e;color:#fff;text-align:left;font-size:12px"';
        $html = '<table style="border-collapse:collapse;width:100%;font-size:13px;margin:8px 0 18px">'
            . '<tr><th ' . $th . '>Employee | الموظف</th><th ' . $th . '>Department | القسم</th><th ' . $th . '>Document | المستند</th>'
            . '<th ' . $th . '>Number | الرقم</th><th ' . $th . '>Expiry | الانتهاء</th><th ' . $th . '>Status | الحالة</th></tr>';
        foreach ($items as $i => $r) {
            $bg = $i % 2 ? '#f9fafb' : '#ffffff';
            $color = $r['days_left'] < 0 ? '#b91c1c' : ($r['days_left'] <= 14 ? '#b45309' : '#1f2937');
            $td = 'style="padding:7px 10px;border-bottom:1px solid #e5e7eb;background:' . $bg . '"';
            $html .= '<tr>'
                . '<td ' . $td . '><strong>' . $h(getDisplayName($r['name'], 'en')) . '</strong><br><span style="color:#6b7280">#' . $h($r['emp_id']) . '</span></td>'
                . '<td ' . $td . '>' . $h($r['dept']) . '</td>'
                . '<td ' . $td . '>' . $h($r['doc_label']) . '<br><span style="color:#6b7280">' . $h($r['doc_label_ar']) . '</span></td>'
                . '<td ' . $td . '>' . $h($r['doc_no']) . '</td>'
                . '<td ' . $td . '>' . $h(date('d M Y', strtotime($r['expiry']))) . ($r['expiry_hijri'] !== '' ? '<br><span style="color:#6b7280">' . $h($r['expiry_hijri']) . ' هـ</span>' : '') . '</td>'
                . '<td ' . $td . '><strong style="color:' . $color . '">' . $h(doc_expiry_days_text($r['days_left'])) . '</strong></td>'
                . '</tr>';
        }
        return $html . '</table>';
    }
}

if (!function_exists('doc_expiry_email_html')) {
    function doc_expiry_email_html($conDB, $bodyHtml) {
        $hasLogo = memo_logo_path($conDB) !== null; // embedded as cid:memo_logo by memo_send_email()
        return '<!doctype html><html><body style="margin:0;padding:24px;background:#f3f4f6;font-family:Segoe UI,Tahoma,Arial,sans-serif;color:#1f2937">'
            . '<div style="max-width:980px;margin:0 auto;background:#ffffff;border-radius:8px;overflow:hidden;border:1px solid #e5e7eb">'
            . ($hasLogo ? '<div style="padding:16px 28px;border-bottom:3px solid #25256e;text-align:center"><img src="cid:memo_logo" alt="" height="60" style="height:60px;width:auto;display:inline-block;border:0"></div>' : '')
            . '<div style="padding:24px 28px;font-size:14px;line-height:1.6">' . $bodyHtml . '</div>'
            . '<div style="padding:12px 28px;background:#f9fafb;color:#6b7280;font-size:12px;border-top:1px solid #e5e7eb">Automatic reminder from the HR system. | تذكير تلقائي من نظام الموارد البشرية.</div>'
            . '</div></body></html>';
    }
}

/* ---------- daily run ---------- */

/**
 * Sends today's alerts; safe to call more than once a day (the log prevents duplicates).
 * Returns ['due' => n, 'expired_due' => n, 'emails_sent' => n, 'emails_failed' => n, 'errors' => [...]].
 */
if (!function_exists('doc_expiry_run_daily')) {
    function doc_expiry_run_daily($conDB) {
        // Only the configurable rules (App Settings > Org Structure > Document Expiry
        // Alerts) send email; the old fixed 60/30/14/7 HR/supervisor/employee alerts are gone.
        require_once __DIR__ . '/doc_expiry_rules_helper.php';
        $rules = doc_expiry_rules_run($conDB);
        return [
            'due' => $rules['due'], 'expired_due' => 0, 'rules' => $rules['rules'],
            'emails_sent' => $rules['emails_sent'], 'emails_failed' => $rules['emails_failed'], 'errors' => $rules['errors'],
        ];
    }
}

