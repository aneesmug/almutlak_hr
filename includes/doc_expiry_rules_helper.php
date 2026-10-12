<?php
/**
 * Configurable document expiry notifications (App Settings > Org Structure >
 * Document Expiry Alerts).
 *
 * Each rule = one document type + chosen recipients (emp_ids) + "days before" list
 * (e.g. 30,14,7). Every day doc_expiry_rules_run() (called from doc_expiry_run_daily(),
 * i.e. zk_sync_import.php's daily guard and the Document Expiry page's "Run alerts now")
 * mails each recipient one digest of the documents that crossed one of the rule's
 * thresholds. A document fires once per threshold per expiry date; if a day is missed
 * only the closest threshold fires. Optional: also email the employee their own
 * document, and a weekly reminder for already expired documents.
 *
 * These rules are the only source of document expiry emails (the old fixed
 * 60/30/14/7-day HR/supervisor/employee alerts were removed).
 */

if (defined('DOC_EXPIRY_RULES_HELPER_INCLUDED')) {
    return;
}
define('DOC_EXPIRY_RULES_HELPER_INCLUDED', true);

require_once __DIR__ . '/helper_functions.php';
require_once __DIR__ . '/memo_helper.php';
require_once __DIR__ . '/doc_expiry_helper.php';

/** Document types a rule can watch: key => [label, label_ar, scope]. */
function doc_expiry_rule_types() {
    return [
        'iqama' => ['Iqama / National ID', 'الإقامة / الهوية الوطنية', 'employee'],
        'passport' => ['Passport', 'جواز السفر', 'employee'],
        'medical_insurance' => ['Medical Insurance', 'التأمين الطبي', 'employee'],
        'eng_council' => ['Engineering Council Membership', 'عضوية هيئة المهندسين', 'employee'],
        'location_license' => ['Location / Baladiya License', 'رخصة الموقع / البلدية', 'location'],
        'car_documents' => ['Car Documents (License, Insurance, MVPI)', 'مستندات السيارات (الاستمارة، التأمين، الفحص)', 'car'],
    ];
}

function doc_expiry_rules_ensure_tables($conDB) {
    static $done = false;
    if ($done) {
        return;
    }
    mysqli_query($conDB, "CREATE TABLE IF NOT EXISTS `doc_expiry_notify_rules` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `doc_type` VARCHAR(40) NOT NULL,
        `days_before` VARCHAR(100) NOT NULL COMMENT 'comma list, e.g. 30,14,7',
        `recipient_emp_ids` TEXT NOT NULL COMMENT 'JSON array of emp_id',
        `notify_employee` TINYINT(1) NOT NULL DEFAULT 0,
        `include_expired` TINYINT(1) NOT NULL DEFAULT 0,
        `status` TINYINT(1) NOT NULL DEFAULT 1,
        `notes` VARCHAR(255) NULL,
        `created_by` VARCHAR(50) NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_type_status` (`doc_type`, `status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    mysqli_query($conDB, "CREATE TABLE IF NOT EXISTS `doc_expiry_rule_log` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `rule_id` INT UNSIGNED NOT NULL,
        `ref_key` VARCHAR(80) NOT NULL,
        `expiry_date` DATE NOT NULL,
        `bucket` VARCHAR(20) NOT NULL COMMENT 'threshold days or expired:<ISO week>',
        `recipients` TEXT NULL,
        `sent_at` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uq_rule_alert` (`rule_id`, `ref_key`, `expiry_date`, `bucket`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $done = true;
}

/** "30, 14,7,7" -> [30, 14, 7] (unique, 0..365, descending). */
function doc_expiry_parse_days($raw) {
    $days = [];
    foreach (preg_split('/[^0-9]+/', (string) $raw) as $d) {
        if ($d !== '' && (int) $d >= 0 && (int) $d <= 365) {
            $days[(int) $d] = true;
        }
    }
    $days = array_keys($days);
    rsort($days);
    return $days;
}

/** All rules (active and inactive), recipients decoded. */
function doc_expiry_rules_all($conDB, $onlyActive = false) {
    doc_expiry_rules_ensure_tables($conDB);
    $result = mysqli_query($conDB, "SELECT * FROM doc_expiry_notify_rules" . ($onlyActive ? " WHERE status = 1" : "") . " ORDER BY doc_type, id");
    $rules = [];
    while ($result && ($r = mysqli_fetch_assoc($result))) {
        $ids = json_decode((string) $r['recipient_emp_ids'], true);
        $r['recipients'] = is_array($ids) ? array_values(array_map('strval', $ids)) : [];
        $r['days'] = doc_expiry_parse_days($r['days_before']);
        $rules[] = $r;
    }
    return $rules;
}

/**
 * Documents of one type with a valid expiry, as rows:
 * ref_key, emp_id ('' for locations/cars), name, sub, doc_label, doc_label_ar, doc_no,
 * expiry, expiry_hijri, days_left, emp_email, supervisor_id.
 */
function doc_expiry_rule_collect($conDB, $docType, $maxDaysLeft = null) {
    $types = doc_expiry_rule_types();
    if (!isset($types[$docType])) {
        return [];
    }
    [$label, $labelAr] = $types[$docType];
    $today = new DateTime('today');
    $rows = [];
    $add = function ($refKey, $empId, $name, $sub, $docLabel, $docLabelAr, $docNo, $rawExpiry, $hijri = '', $email = '', $supervisor = '') use (&$rows, $today, $maxDaysLeft) {
        $expiry = doc_expiry_normalize_date($rawExpiry);
        if ($expiry === null) {
            return;
        }
        $daysLeft = (int) $today->diff(new DateTime($expiry))->format('%r%a');
        if ($maxDaysLeft !== null && $daysLeft > $maxDaysLeft) {
            return;
        }
        $rows[] = [
            'ref_key' => $refKey, 'emp_id' => (string) $empId, 'name' => (string) $name, 'dept' => (string) $sub,
            'doc_label' => $docLabel, 'doc_label_ar' => $docLabelAr, 'doc_no' => (string) $docNo,
            'expiry' => $expiry, 'expiry_hijri' => (string) $hijri, 'days_left' => $daysLeft,
            'emp_email' => (string) $email, 'supervisor_id' => trim((string) $supervisor),
        ];
    };

    if ($docType === 'iqama' || $docType === 'passport') {
        foreach (doc_expiry_collect($conDB, '', $maxDaysLeft) as $r) {
            if ($r['doc_key'] === $docType) {
                $add($docType . ':' . $r['emp_id'], $r['emp_id'], $r['name'], $r['dept'], $r['doc_label'], $r['doc_label_ar'], $r['doc_no'], $r['expiry'], $r['expiry_hijri'], $r['emp_email'], $r['supervisor_id']);
            }
        }
        return $rows;
    }

    $empBase = "FROM employees e LEFT JOIN department d ON d.id = e.dept WHERE e.status = 1";
    if ($docType === 'medical_insurance') {
        $sql = "SELECT e.emp_id, e.name, e.email, e.c_email, e.supervisor_id, d.dep_nme, m.insurance_no AS doc_no, m.medical_expiry AS expiry
                FROM employee_medical_insurance m
                JOIN (SELECT emp_id, MAX(id) AS id FROM employee_medical_insurance GROUP BY emp_id) last ON last.id = m.id
                JOIN employees e ON e.emp_id = m.emp_id
                LEFT JOIN department d ON d.id = e.dept
                WHERE e.status = 1";
    } elseif ($docType === 'eng_council') {
        $sql = "SELECT e.emp_id, e.name, e.email, e.c_email, e.supervisor_id, d.dep_nme, '' AS doc_no, a.eng_council_expiry AS expiry
                FROM employee_additional_info a JOIN employees e ON e.emp_id = a.emp_id
                LEFT JOIN department d ON d.id = e.dept
                WHERE e.status = 1 AND a.eng_council_expiry IS NOT NULL";
    } elseif ($docType === 'location_license') {
        $sql = "SELECT id, section_name, location_name, b_license_no, b_license_exp FROM section WHERE status = '1' AND b_license_exp <> ''";
    } else { // car_documents
        $sql = "SELECT cd.id, cd.doc_type, cd.exp_date, c.plate_no, c.made_year, c.type
                FROM cars_docu cd JOIN cars c ON c.id = cd.car_id
                WHERE c.status = '1' AND cd.exp_date <> ''";
    }
    $result = mysqli_query($conDB, $sql);
    if (!$result) {
        error_log('DOC_EXPIRY_RULES collect ' . $docType . ': ' . mysqli_error($conDB));
        return [];
    }
    while ($r = mysqli_fetch_assoc($result)) {
        if ($docType === 'location_license') {
            $add('loc:' . $r['id'], '', $r['section_name'], $r['location_name'], $label, $labelAr, $r['b_license_no'], $r['b_license_exp']);
        } elseif ($docType === 'car_documents') {
            $add('car:' . $r['id'], '', $r['plate_no'], trim($r['type'] . ' ' . $r['made_year']), 'Car ' . $r['doc_type'], 'السيارة - ' . $r['doc_type'], '', $r['exp_date']);
        } else {
            $add($docType . ':' . $r['emp_id'], $r['emp_id'], $r['name'], $r['dep_nme'], $label, $labelAr, $r['doc_no'], $r['expiry'], '', $r['email'] ?: $r['c_email'], $r['supervisor_id']);
        }
    }
    mysqli_free_result($result);
    return $rows;
}

/** Bucket for days left under a rule's thresholds: closest threshold >= days left, 'expired', or null. */
function doc_expiry_rule_bucket($daysLeft, array $days) {
    if ($daysLeft < 0) {
        return 'expired';
    }
    $bucket = null;
    foreach ($days as $t) {
        if ($daysLeft <= $t) {
            $bucket = (string) $t;
        }
    }
    return $bucket;
}

/** Recipient list [email => name] for a rule's emp_ids. */
function doc_expiry_rule_recipients($conDB, array $empIds) {
    $out = [];
    foreach ($empIds as $empId) {
        $email = doc_expiry_email_for_emp($conDB, (string) $empId);
        if ($email === '') {
            continue;
        }
        $name = '';
        $stmt = $conDB->prepare("SELECT name FROM employees WHERE emp_id = ? LIMIT 1");
        $stmt->bind_param('s', $empId);
        $stmt->execute();
        $name = (string) ($stmt->get_result()->fetch_assoc()['name'] ?? '');
        $stmt->close();
        $out[strtolower($email)] = ['email' => $email, 'name' => getDisplayName($name, 'en')];
    }
    return array_values($out);
}

/**
 * Items of a rule that are due today (not yet logged for their bucket).
 * Returns [upcoming[], expired[]], each row with 'bucket_key'.
 */
function doc_expiry_rule_due($conDB, array $rule) {
    $days = $rule['days'];
    if (!$days) {
        return [[], []];
    }
    $rows = doc_expiry_rule_collect($conDB, $rule['doc_type'], max($days));
    $sent = [];
    $stmt = $conDB->prepare("SELECT ref_key, expiry_date, bucket FROM doc_expiry_rule_log WHERE rule_id = ? AND sent_at >= DATE_SUB(NOW(), INTERVAL 400 DAY)");
    $stmt->bind_param('i', $rule['id']);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $sent[$r['ref_key'] . '|' . $r['expiry_date'] . '|' . $r['bucket']] = true;
    }
    $stmt->close();

    $week = date('o-\WW');
    $upcoming = $expired = [];
    foreach ($rows as $r) {
        $bucket = doc_expiry_rule_bucket($r['days_left'], $days);
        if ($bucket === null || ($bucket === 'expired' && empty($rule['include_expired']))) {
            continue;
        }
        $r['bucket_key'] = $bucket === 'expired' ? 'expired:' . $week : $bucket;
        if (isset($sent[$r['ref_key'] . '|' . $r['expiry'] . '|' . $r['bucket_key']])) {
            continue;
        }
        if ($bucket === 'expired') {
            $expired[] = $r;
        } else {
            $upcoming[] = $r;
        }
    }
    $byDays = function ($a, $b) { return $a['days_left'] <=> $b['days_left']; };
    usort($upcoming, $byDays);
    usort($expired, $byDays);
    return [$upcoming, $expired];
}

/**
 * Sends today's rule-based alerts; safe to call more than once a day (log prevents
 * duplicates). Returns ['rules' => n, 'due' => n, 'emails_sent' => n, 'emails_failed' => n, 'errors' => [...]].
 */
function doc_expiry_rules_run($conDB, $onlyRuleId = null) {
    doc_expiry_rules_ensure_tables($conDB);
    $summary = ['rules' => 0, 'due' => 0, 'emails_sent' => 0, 'emails_failed' => 0, 'errors' => []];
    $types = doc_expiry_rule_types();

    foreach (doc_expiry_rules_all($conDB, true) as $rule) {
        if ($onlyRuleId !== null && (int) $rule['id'] !== (int) $onlyRuleId) {
            continue;
        }
        if (!isset($types[$rule['doc_type']])) {
            continue;
        }
        $summary['rules']++;
        [$upcoming, $expired] = doc_expiry_rule_due($conDB, $rule);
        if (!$upcoming && !$expired) {
            continue;
        }
        $summary['due'] += count($upcoming) + count($expired);
        [$label, $labelAr] = $types[$rule['doc_type']];
        $delivered = [];
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

        // 1) Selected recipients: one digest per rule.
        $body = '<h2 style="margin:0 0 6px;color:#25256e">' . htmlspecialchars($label) . ' Expiry Alert | تنبيه انتهاء ' . htmlspecialchars($labelAr) . '</h2>'
            . '<p style="margin:0 0 14px;color:#6b7280">' . date('d M Y') . '</p>';
        if ($upcoming) {
            $body .= '<h3 style="margin:14px 0 4px">Expiring soon (' . count($upcoming) . ') | تنتهي قريباً</h3>' . doc_expiry_table_html($upcoming);
        }
        if ($expired) {
            $body .= '<h3 style="margin:14px 0 4px;color:#b91c1c">Already expired (' . count($expired) . ') | منتهية</h3>'
                . '<p style="margin:0;color:#6b7280;font-size:12px">Weekly reminder. If renewed, please update the date in the system. | تذكير أسبوعي. إذا تم التجديد يرجى تحديث التاريخ في النظام.</p>'
                . doc_expiry_table_html($expired);
        }
        $subject = $label . ' Expiry Alert: ' . count($upcoming) . ' expiring' . ($expired ? ', ' . count($expired) . ' expired' : '');
        foreach (doc_expiry_rule_recipients($conDB, $rule['recipients']) as $rcpt) {
            if ($send($rcpt['email'], $rcpt['name'], $subject, $body)) {
                foreach (['u' => $upcoming, 'x' => $expired] as $set => $items) {
                    foreach ($items as $i => $_) {
                        $delivered[$set . $i][] = $rcpt['email'];
                    }
                }
            }
        }

        // 2) Optional: each employee about their own document (upcoming only).
        if (!empty($rule['notify_employee'])) {
            foreach ($upcoming as $i => $r) {
                if ($r['emp_id'] === '') {
                    continue;
                }
                $to = doc_expiry_email_for_emp($conDB, $r['emp_id'], $r['emp_email']);
                if ($to === '') {
                    continue;
                }
                $name = getDisplayName($r['name'], 'en');
                $mail = '<h2 style="margin:0 0 6px;color:#25256e">Your ' . htmlspecialchars($r['doc_label']) . ' is expiring | ' . htmlspecialchars($r['doc_label_ar']) . ' الخاصة بك ستنتهي</h2>'
                    . '<p>Dear ' . htmlspecialchars($name) . ',<br>Your document below is expiring soon. Please contact HR / Government Relations to start the renewal.</p>'
                    . '<p dir="rtl" style="text-align:right">عزيزي الموظف،<br>المستند التالي سينتهي قريباً. يرجى التواصل مع الموارد البشرية / العلاقات الحكومية لبدء التجديد.</p>'
                    . doc_expiry_table_html([$r]);
                if ($send($to, $name, 'Reminder: your ' . $r['doc_label'] . ' expires on ' . date('d M Y', strtotime($r['expiry'])), $mail)) {
                    $delivered['u' . $i][] = $to;
                }
            }
        }

        // Log items that reached at least one person; the rest retry tomorrow.
        $stmt = $conDB->prepare("INSERT IGNORE INTO doc_expiry_rule_log (rule_id, ref_key, expiry_date, bucket, recipients, sent_at) VALUES (?, ?, ?, ?, ?, NOW())");
        foreach (['u' => $upcoming, 'x' => $expired] as $set => $items) {
            foreach ($items as $i => $r) {
                if (empty($delivered[$set . $i])) {
                    continue;
                }
                $recipients = implode(', ', array_unique($delivered[$set . $i]));
                $stmt->bind_param('issss', $rule['id'], $r['ref_key'], $r['expiry'], $r['bucket_key'], $recipients);
                $stmt->execute();
            }
        }
        $stmt->close();
    }
    return $summary;
}
