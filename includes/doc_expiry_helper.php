<?php
/**
 * Document expiry alerts (Employees > Document Expiry): ID/Iqama (= work permit for
 * expats, renewed together), and passport.
 *
 * - Dates come straight from `employees`: iqama_exp_g (Gregorian, 'Y-m-d' or 'Y/m/d')
 *   and passport_exp. Saudis (country code SA) carry a National ID instead of an Iqama
 *   and have no work permit.
 * - doc_expiry_run_daily() is called once a day from zk_sync_import.php (no cron on
 *   this server) and from the page's "Run alerts now" button. Each document fires once
 *   per threshold (60 / 30 / 14 / 7 days) for a given expiry date; if a day is missed
 *   only the closest threshold fires, never a burst of old ones. Already expired
 *   documents go to HR only, once a week, so stale dates don't spam employees and
 *   managers.
 * - Who gets what: HR users (hr, hr_senior_bp, hr_operations, hr_supervisor,
 *   gr_officer) one digest of everything; each supervisor one digest of their team;
 *   each employee an email about their own document.
 * - `doc_expiry_alert_log` remembers what was sent; it's created on first use because
 *   sql/ is git-ignored and never reaches the live server.
 * - Mail goes through memo_send_email() (main SMTP settings, App Settings > Email).
 */

if (defined('DOC_EXPIRY_HELPER_INCLUDED')) {
    return;
}
define('DOC_EXPIRY_HELPER_INCLUDED', true);

require_once __DIR__ . '/helper_functions.php';
require_once __DIR__ . '/memo_helper.php';

const DOC_EXPIRY_THRESHOLDS = [60, 30, 14, 7];
const DOC_EXPIRY_HR_USER_TYPES = ['hr', 'hr_senior_bp', 'hr_operations', 'hr_supervisor', 'gr_officer'];
const DOC_EXPIRY_SAUDI_COUNTRY_CODE = 'SA';

if (!function_exists('doc_expiry_ensure_table')) {
    function doc_expiry_ensure_table($conDB) {
        static $done = false;
        if ($done) {
            return;
        }
        mysqli_query($conDB, "CREATE TABLE IF NOT EXISTS `doc_expiry_alert_log` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `emp_id` VARCHAR(50) NOT NULL,
            `doc_key` VARCHAR(20) NOT NULL,
            `expiry_date` DATE NOT NULL,
            `bucket` VARCHAR(20) NOT NULL COMMENT '60/30/14/7 or expired:<ISO week>',
            `recipients` TEXT NULL,
            `sent_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_doc_alert` (`emp_id`, `doc_key`, `expiry_date`, `bucket`),
            KEY `idx_emp_doc` (`emp_id`, `doc_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $done = true;
    }
}

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

/** Alert bucket for a days-left value: 'expired', the closest threshold (60/30/14/7), or null (> 60 days). */
if (!function_exists('doc_expiry_bucket')) {
    function doc_expiry_bucket($daysLeft) {
        if ($daysLeft < 0) {
            return 'expired';
        }
        $bucket = null;
        foreach (DOC_EXPIRY_THRESHOLDS as $t) {
            if ($daysLeft <= $t) {
                $bucket = (string) $t;
            }
        }
        return $bucket;
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
                    'bucket' => doc_expiry_bucket($daysLeft),
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
        doc_expiry_ensure_table($conDB);
        $out = [];
        $result = mysqli_query($conDB, "SELECT emp_id, doc_key, expiry_date, bucket, sent_at FROM doc_expiry_alert_log ORDER BY sent_at");
        while ($result && ($r = mysqli_fetch_assoc($result))) {
            $out[$r['emp_id'] . '|' . $r['doc_key'] . '|' . $r['expiry_date']] = ['bucket' => $r['bucket'], 'sent_at' => $r['sent_at']];
        }
        return $out;
    }
}

/** Valid email for an emp_id: login email first, then the employee record. */
if (!function_exists('doc_expiry_email_for_emp')) {
    function doc_expiry_email_for_emp($conDB, $empId, $fallback = '') {
        $email = memo_user_email($conDB, $empId);
        if ($email === '' && $fallback === '' && $empId !== '') {
            $stmt = $conDB->prepare("SELECT email, c_email FROM employees WHERE emp_id = ? ORDER BY status DESC LIMIT 1");
            $stmt->bind_param('s', $empId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $fallback = (string) (($row['email'] ?? '') ?: ($row['c_email'] ?? ''));
        }
        if ($email === '') {
            $email = trim($fallback);
        }
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';
    }
}

if (!function_exists('doc_expiry_hr_recipients')) {
    function doc_expiry_hr_recipients($conDB) {
        $types = "'" . implode("','", DOC_EXPIRY_HR_USER_TYPES) . "'";
        $result = mysqli_query($conDB, "SELECT DISTINCT email, fullname FROM admin_login WHERE status = 1 AND user_type IN ($types) AND email <> ''");
        $out = [];
        while ($result && ($r = mysqli_fetch_assoc($result))) {
            $email = trim((string) $r['email']);
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $out[strtolower($email)] = ['email' => $email, 'name' => (string) $r['fullname']];
            }
        }
        return array_values($out);
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
        doc_expiry_ensure_table($conDB);
        $summary = ['due' => 0, 'expired_due' => 0, 'emails_sent' => 0, 'emails_failed' => 0, 'errors' => []];

        $maxThreshold = max(DOC_EXPIRY_THRESHOLDS);
        $rows = doc_expiry_collect($conDB, '', $maxThreshold);
        $week = date('o-\WW');

        // Which (doc, expiry, bucket) were already sent.
        $sent = [];
        $result = mysqli_query($conDB, "SELECT emp_id, doc_key, expiry_date, bucket FROM doc_expiry_alert_log WHERE sent_at >= DATE_SUB(NOW(), INTERVAL 400 DAY)");
        while ($result && ($r = mysqli_fetch_assoc($result))) {
            $sent[$r['emp_id'] . '|' . $r['doc_key'] . '|' . $r['expiry_date'] . '|' . $r['bucket']] = true;
        }

        $upcoming = [];   // newly crossed a threshold today: everyone is told
        $expired = [];    // expired, not yet reported this week: HR only
        foreach ($rows as $r) {
            if ($r['bucket'] === null) {
                continue;
            }
            $bucketKey = $r['bucket'] === 'expired' ? 'expired:' . $week : $r['bucket'];
            if (isset($sent[$r['emp_id'] . '|' . $r['doc_key'] . '|' . $r['expiry'] . '|' . $bucketKey])) {
                continue;
            }
            $r['bucket_key'] = $bucketKey;
            if ($r['bucket'] === 'expired') {
                $expired[] = $r;
            } else {
                $upcoming[] = $r;
            }
        }
        $summary['due'] = count($upcoming);
        $summary['expired_due'] = count($expired);
        if (!$upcoming && !$expired) {
            return $summary;
        }

        $delivered = []; // index into $upcoming/$expired => list of recipient emails
        $send = function ($to, $name, $subject, $body) use ($conDB, &$summary) {
            [$ok, $err] = memo_send_email($conDB, $to, $name, $subject, doc_expiry_email_html($conDB, $body));
            if ($ok) {
                $summary['emails_sent']++;
            } else {
                $summary['emails_failed']++;
                $summary['errors'][] = $to . ': ' . $err;
            }
            return $ok;
        };
        $byDays = function ($a, $b) { return $a['days_left'] <=> $b['days_left']; };
        usort($upcoming, $byDays);
        usort($expired, $byDays);

        // 1) HR digest: everything.
        $hrList = doc_expiry_hr_recipients($conDB);
        if ($hrList) {
            $body = '<h2 style="margin:0 0 6px;color:#25256e">Document Expiry Alert | تنبيه انتهاء المستندات</h2>'
                . '<p style="margin:0 0 14px;color:#6b7280">' . date('d M Y') . '</p>';
            if ($upcoming) {
                $body .= '<h3 style="margin:14px 0 4px">Expiring soon (' . count($upcoming) . ') | تنتهي قريباً</h3>'
                    . doc_expiry_table_html($upcoming);
            }
            if ($expired) {
                $body .= '<h3 style="margin:14px 0 4px;color:#b91c1c">Already expired (' . count($expired) . ') | منتهية</h3>'
                    . '<p style="margin:0;color:#6b7280;font-size:12px">Weekly reminder. If a document was already renewed, update its date in the system (Employees &gt; Document Expiry, or Import Iqama Expiry). | تذكير أسبوعي. إذا تم التجديد يرجى تحديث التاريخ في النظام.</p>'
                    . doc_expiry_table_html($expired);
            }
            $subject = 'Document Expiry Alert: ' . count($upcoming) . ' expiring, ' . count($expired) . ' expired';
            foreach ($hrList as $hr) {
                if ($send($hr['email'], $hr['name'], $subject, $body)) {
                    foreach (['u' => $upcoming, 'x' => $expired] as $set => $items) {
                        foreach ($items as $i => $_) {
                            $delivered[$set . $i][] = $hr['email'];
                        }
                    }
                }
            }
        }

        // 2) Manager digest: their own team, upcoming only.
        $teams = [];
        foreach ($upcoming as $i => $r) {
            if ($r['supervisor_id'] !== '' && $r['supervisor_id'] !== $r['emp_id']) {
                $teams[$r['supervisor_id']][$i] = $r;
            }
        }
        foreach ($teams as $supId => $items) {
            $to = doc_expiry_email_for_emp($conDB, (string) $supId);
            if ($to === '') {
                continue;
            }
            $body = '<h2 style="margin:0 0 6px;color:#25256e">Team Document Expiry | انتهاء مستندات الفريق</h2>'
                . '<p>The following documents of your team members are expiring soon. Please make sure they contact HR / Government Relations for renewal.<br>'
                . 'المستندات التالية لأعضاء فريقك ستنتهي قريباً. يرجى التأكد من مراجعتهم للموارد البشرية / العلاقات الحكومية للتجديد.</p>'
                . doc_expiry_table_html(array_values($items));
            if ($send($to, '', 'Team Document Expiry: ' . count($items) . ' document(s)', $body)) {
                foreach (array_keys($items) as $i) {
                    $delivered['u' . $i][] = $to;
                }
            }
        }

        // 3) Employee: own document, upcoming only.
        foreach ($upcoming as $i => $r) {
            $to = doc_expiry_email_for_emp($conDB, $r['emp_id'], $r['emp_email']);
            if ($to === '') {
                continue;
            }
            $name = getDisplayName($r['name'], 'en');
            $body = '<h2 style="margin:0 0 6px;color:#25256e">Your ' . htmlspecialchars($r['doc_label']) . ' is expiring | ' . htmlspecialchars($r['doc_label_ar']) . ' الخاصة بك ستنتهي</h2>'
                . '<p>Dear ' . htmlspecialchars($name) . ',<br>Your document below is expiring soon. Please contact HR / Government Relations to start the renewal.</p>'
                . '<p dir="rtl" style="text-align:right">عزيزي الموظف،<br>المستند التالي سينتهي قريباً. يرجى التواصل مع الموارد البشرية / العلاقات الحكومية لبدء التجديد.</p>'
                . doc_expiry_table_html([$r]);
            if ($send($to, $name, 'Reminder: your ' . $r['doc_label'] . ' expires on ' . date('d M Y', strtotime($r['expiry'])), $body)) {
                $delivered['u' . $i][] = $to;
            }
        }

        // Log every item that reached at least one person; the rest retry tomorrow.
        $stmt = $conDB->prepare("INSERT IGNORE INTO doc_expiry_alert_log (emp_id, doc_key, expiry_date, bucket, recipients, sent_at) VALUES (?, ?, ?, ?, ?, NOW())");
        foreach (['u' => $upcoming, 'x' => $expired] as $set => $items) {
            foreach ($items as $i => $r) {
                if (empty($delivered[$set . $i])) {
                    continue;
                }
                $recipients = implode(', ', array_unique($delivered[$set . $i]));
                $stmt->bind_param('sssss', $r['emp_id'], $r['doc_key'], $r['expiry'], $r['bucket_key'], $recipients);
                $stmt->execute();
            }
        }
        $stmt->close();

        return $summary;
    }
}
