<?php
/**
 * Employee Memos (Employees > Send Memo): ready-made bilingual (English | Arabic) HR
 * letters/notices. HR picks an employee and a memo type, fills the template's own
 * detail fields (e.g. Warning Letter: violation, incident date), the memo is built
 * side by side in English and Arabic, then emailed and kept as history on the
 * employee's master file (view_employee.php > Memos tab).
 *
 * - Templates live in the `memo_templates` table (Manage Templates on the page).
 *   memo_default_templates() holds the 21 built-in ones: they seed the table and are
 *   what "Reset to default" restores.
 * - Template text uses {{placeholders}}: employee data ({{employee_name}}, ...) filled
 *   per language from memo_employee_placeholders(), and {{f:key}} for the template's
 *   own fields (definitions in fields_json). Filling/layout happens in
 *   assets/js/employee_memos.js so the preview is live; the server stores and emails
 *   the final HTML.
 * - `employee_memos` keeps every memo (draft / sent / failed) with the field values,
 *   so a draft reopens with its fields filled.
 * - Tables are created/upgraded on first use (memo_ensure_table()), because sql/ is
 *   git-ignored and never reaches the live server on deploy.
 * - Mail uses the main SMTP settings (App Settings > Email), same as approval emails.
 */

if (defined('MEMO_HELPER_INCLUDED')) {
    return;
}
define('MEMO_HELPER_INCLUDED', true);

/**
 * Access rules for Employees > Send Memo.
 *  'send'      - use the page / see memo history: roles allowed on employee_memos.php in
 *                App Settings > Page Access, or the 'access_employee_memos' Special Access
 *                key (App Settings > Special Access > Page Access > Employee Management).
 *  'templates' - add / edit / activate / reset / delete templates: system admin, or the
 *                'manage_memo_templates' Special Access key (Employee Master group).
 */
if (!function_exists('memo_user_can')) {
    function memo_user_can($conDB, $what, $empId, $userRole, $userType, $isSystemAdmin) {
        if ($isSystemAdmin || strtolower((string) $userType) === 'administrator') {
            return true;
        }
        $hasKey = function ($key) use ($conDB, $empId, $userRole, $userType, $isSystemAdmin) {
            return function_exists('user_has_special_access') && user_has_special_access($conDB, $empId, $key, $userRole, $userType, $isSystemAdmin);
        };
        if ($what === 'templates') {
            return $hasKey('manage_memo_templates');
        }
        if (!function_exists('page_role_allowed')) {
            require_once __DIR__ . '/page_access_helper.php';
        }
        return page_role_allowed($conDB, 'employee_memos.php', $userRole, $userType, $isSystemAdmin) || $hasKey('access_employee_memos');
    }
}

// Bump when memo_default_templates() changes, so untouched built-ins get refreshed.
if (!defined('MEMO_BUILTIN_VERSION')) {
    define('MEMO_BUILTIN_VERSION', 4);
}

/**
 * Dropdown sources for 'select' detail fields: options come from these tables with
 * their English + Arabic names, so the chosen value fills both sides of the memo.
 */
if (!function_exists('memo_lookup_sources')) {
    function memo_lookup_sources() {
        return [
            'jobs' => ['label' => 'Job titles', 'sql' => "SELECT id, job AS en, job_ar AS ar FROM ac_jobs ORDER BY job"],
            'departments' => ['label' => 'Departments', 'sql' => "SELECT id, dep_nme AS en, dep_nme_ar AS ar FROM department ORDER BY dep_nme"],
            // Optional p1_en/p1_ar, p2_en/p2_ar... = parent levels (outermost first), shown as a
            // breadcrumb in the dropdown so same-named items (e.g. "Filters" in 3 cities) differ.
            'sub_departments' => ['label' => 'Sub-departments', 'sql' => "SELECT sd.id, sd.name_en AS en, sd.name_ar AS ar,
                    d.dep_nme AS p1_en, d.dep_nme_ar AS p1_ar
                FROM sub_departments sd LEFT JOIN department d ON d.id = sd.department_id
                ORDER BY d.dep_nme, sd.name_en"],
            // Picked as Company > City > Location (three linked dropdowns), see memo_location_tree().
            'locations' => ['label' => 'Locations / branches (Company > City > Location)', 'sql' => "SELECT id, name_en AS en, name_ar AS ar FROM locations ORDER BY name_en"],
            'companies' => ['label' => 'Companies', 'sql' => "SELECT comp_id AS id, comp_name AS en, comp_name_ar AS ar FROM companies ORDER BY comp_name"],
        ];
    }
}

if (!function_exists('memo_lookup_options')) {
    function memo_lookup_options($conDB, $source) {
        $sources = memo_lookup_sources();
        if (!isset($sources[$source])) {
            return [];
        }
        $out = [];
        $res = $conDB->query($sources[$source]['sql']);
        while ($res && ($r = $res->fetch_assoc())) {
            $en = trim((string) $r['en']);
            if ($en === '') {
                continue;
            }
            $ar = trim((string) $r['ar']) ?: $en;
            $path = [];
            for ($i = 1; array_key_exists('p' . $i . '_en', $r); $i++) {
                $pe = trim((string) $r['p' . $i . '_en']);
                if ($pe !== '') {
                    $path[] = ['en' => $pe, 'ar' => trim((string) $r['p' . $i . '_ar']) ?: $pe];
                }
            }
            $opt = ['id' => (string) $r['id'], 'en' => $en, 'ar' => $ar, 'path' => $path];
            // Text that goes into the memo: add the parent (e.g. city) when set, so
            // "Filters, Jeddah" is not confused with "Filters, Riyadh".
            $out[] = $opt;
        }
        return $out;
    }
}

/**
 * Data for the Company > City > Location dropdowns of a 'locations' field.
 * Locations belong to a city; they are not tied to a company in the DB, so a company's
 * cities are the ones where it has active staff (all cities when it has none yet).
 */
if (!function_exists('memo_location_tree')) {
    function memo_location_tree($conDB) {
        $tree = ['companies' => [], 'cities' => [], 'locations' => []];
        $res = $conDB->query("SELECT l.id, l.name_en, l.name_ar, l.city_id, sc.name_en AS city_en, sc.name_ar AS city_ar
            FROM locations l LEFT JOIN saudi_cities sc ON sc.id = l.city_id
            ORDER BY sc.name_en, l.name_en");
        while ($res && ($r = $res->fetch_assoc())) {
            $cityId = (string) $r['city_id'];
            if (!isset($tree['cities'][$cityId])) {
                $cityEn = trim((string) $r['city_en']) ?: ('City #' . $cityId);
                $tree['cities'][$cityId] = ['id' => $cityId, 'en' => $cityEn, 'ar' => trim((string) $r['city_ar']) ?: $cityEn];
            }
            $en = trim((string) $r['name_en']);
            $tree['locations'][] = ['id' => (string) $r['id'], 'city_id' => $cityId, 'en' => $en, 'ar' => trim((string) $r['name_ar']) ?: $en];
        }
        $used = [];
        $res = $conDB->query("SELECT DISTINCT e.comp_no, l.city_id FROM employees e
            JOIN locations l ON l.id = e.location_id WHERE e.status = 1");
        while ($res && ($r = $res->fetch_assoc())) {
            $used[(string) $r['comp_no']][] = (string) $r['city_id'];
        }
        $res = $conDB->query("SELECT comp_id, comp_name, comp_name_ar FROM companies ORDER BY comp_name");
        while ($res && ($r = $res->fetch_assoc())) {
            $en = trim((string) $r['comp_name']);
            if ($en === '') {
                continue;
            }
            $id = (string) $r['comp_id'];
            $tree['companies'][] = ['id' => $id, 'en' => $en, 'ar' => trim((string) $r['comp_name_ar']) ?: $en, 'city_ids' => $used[$id] ?? []];
        }
        $tree['cities'] = array_values($tree['cities']);
        return $tree;
    }
}

if (!function_exists('memo_column_exists')) {
    function memo_column_exists($conDB, $table, $column) {
        $res = $conDB->query("SHOW COLUMNS FROM `$table` LIKE '" . $conDB->real_escape_string($column) . "'");
        return $res && $res->num_rows > 0;
    }
}

if (!function_exists('memo_ensure_table')) {
    function memo_ensure_table($conDB) {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        $conDB->query("CREATE TABLE IF NOT EXISTS `employee_memos` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `emp_id` VARCHAR(50) NOT NULL,
            `memo_type` VARCHAR(60) NOT NULL,
            `reference_no` VARCHAR(60) DEFAULT NULL,
            `subject` VARCHAR(255) NOT NULL,
            `body_html` MEDIUMTEXT NOT NULL,
            `field_values` TEXT DEFAULT NULL,
            `is_manual` TINYINT(1) NOT NULL DEFAULT 0,
            `sent_to` VARCHAR(255) DEFAULT NULL,
            `cc` VARCHAR(500) DEFAULT NULL,
            `status` VARCHAR(20) NOT NULL DEFAULT 'sent',
            `error_message` VARCHAR(500) DEFAULT NULL,
            `sent_by` VARCHAR(50) DEFAULT NULL,
            `sent_by_name` VARCHAR(255) DEFAULT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            KEY `idx_emp` (`emp_id`),
            KEY `idx_type` (`memo_type`),
            KEY `idx_status` (`status`),
            KEY `idx_created` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

        // Upgrades for copies created by earlier (never deployed) versions of this feature.
        $col = $conDB->query("SHOW COLUMNS FROM `employee_memos` LIKE 'status'");
        if ($col && ($row = $col->fetch_assoc()) && stripos($row['Type'], 'enum') === 0) {
            $conDB->query("ALTER TABLE `employee_memos` MODIFY `status` VARCHAR(20) NOT NULL DEFAULT 'sent', ADD KEY `idx_status` (`status`)");
        }
        if (!memo_column_exists($conDB, 'employee_memos', 'updated_at')) {
            $conDB->query("ALTER TABLE `employee_memos` ADD `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP");
        }
        if (!memo_column_exists($conDB, 'employee_memos', 'field_values')) {
            $conDB->query("ALTER TABLE `employee_memos` ADD `field_values` TEXT DEFAULT NULL AFTER `body_html`, ADD `is_manual` TINYINT(1) NOT NULL DEFAULT 0 AFTER `field_values`");
        }

        $conDB->query("CREATE TABLE IF NOT EXISTS `memo_templates` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `template_key` VARCHAR(60) NOT NULL,
            `label` VARCHAR(120) NOT NULL,
            `label_ar` VARCHAR(120) DEFAULT NULL,
            `icon` VARCHAR(60) NOT NULL DEFAULT 'fa-envelope',
            `subject` VARCHAR(255) NOT NULL,
            `subject_ar` VARCHAR(255) DEFAULT NULL,
            `body_html` MEDIUMTEXT NOT NULL,
            `body_ar_html` MEDIUMTEXT DEFAULT NULL,
            `fields_json` TEXT DEFAULT NULL,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `sort_order` INT NOT NULL DEFAULT 0,
            `updated_by` VARCHAR(255) DEFAULT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `uniq_key` (`template_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

        // Earlier (English-only, [bracket] text) copy of the table: add the bilingual
        // columns and replace the built-ins with the new field-based versions.
        $refreshBuiltins = false;
        if (!memo_column_exists($conDB, 'memo_templates', 'fields_json')) {
            $conDB->query("ALTER TABLE `memo_templates`
                ADD `label_ar` VARCHAR(120) DEFAULT NULL AFTER `label`,
                ADD `subject_ar` VARCHAR(255) DEFAULT NULL AFTER `subject`,
                ADD `body_ar_html` MEDIUMTEXT DEFAULT NULL AFTER `body_html`,
                ADD `fields_json` TEXT DEFAULT NULL AFTER `body_ar_html`");
            $refreshBuiltins = true;
        }

        // Built-ins changed in code since this database was seeded (e.g. v3 turned
        // "New position" into a job-title dropdown): refresh the ones HR never edited
        // (updated_by IS NULL). Edited templates are left alone - Reset gets the new one.
        $version = (int) get_setting($conDB, 'memo_templates_version');
        if (!$refreshBuiltins && $version < MEMO_BUILTIN_VERSION) {
            $up = $conDB->prepare("UPDATE memo_templates SET label = ?, label_ar = ?, icon = ?, subject = ?, subject_ar = ?, body_html = ?, body_ar_html = ?, fields_json = ?
                                   WHERE template_key = ? AND updated_by IS NULL");
            if ($up) {
                foreach (memo_default_templates() as $key => $tpl) {
                    $fields = json_encode($tpl['fields'], JSON_UNESCAPED_UNICODE);
                    $up->bind_param('sssssssss', $tpl['label'], $tpl['label_ar'], $tpl['icon'], $tpl['subject'], $tpl['subject_ar'], $tpl['body'], $tpl['body_ar'], $fields, $key);
                    $up->execute();
                }
                $up->close();
            }
        }
        if ($version < MEMO_BUILTIN_VERSION) {
            $v = (string) MEMO_BUILTIN_VERSION;
            $conDB->query("INSERT INTO app_settings (setting_name, setting_value, setting_group, description, input_type, options)
                           SELECT 'memo_templates_version', '$v', 'memo_internal', 'Employee memo built-in templates version', 'text', NULL
                           FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM app_settings WHERE setting_name = 'memo_templates_version')");
            $conDB->query("UPDATE app_settings SET setting_value = '$v' WHERE setting_name = 'memo_templates_version'");
        }

        // Seed built-ins once (INSERT IGNORE on the unique key - an edited or
        // deactivated built-in is never overwritten).
        $sql = $refreshBuiltins
            ? "INSERT INTO `memo_templates` (template_key, label, label_ar, icon, subject, subject_ar, body_html, body_ar_html, fields_json, sort_order)
               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
               ON DUPLICATE KEY UPDATE label = VALUES(label), label_ar = VALUES(label_ar), icon = VALUES(icon), subject = VALUES(subject),
                   subject_ar = VALUES(subject_ar), body_html = VALUES(body_html), body_ar_html = VALUES(body_ar_html), fields_json = VALUES(fields_json)"
            : "INSERT IGNORE INTO `memo_templates` (template_key, label, label_ar, icon, subject, subject_ar, body_html, body_ar_html, fields_json, sort_order)
               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conDB->prepare($sql);
        if ($stmt) {
            $order = 0;
            foreach (memo_default_templates() as $key => $tpl) {
                $order += 10;
                $fields = json_encode($tpl['fields'], JSON_UNESCAPED_UNICODE);
                $stmt->bind_param('sssssssssi', $key, $tpl['label'], $tpl['label_ar'], $tpl['icon'], $tpl['subject'], $tpl['subject_ar'], $tpl['body'], $tpl['body_ar'], $fields, $order);
                $stmt->execute();
            }
            $stmt->close();
        }
    }
}

/** Normalise a fields definition list (from JSON or the template editor). */
if (!function_exists('memo_normalize_fields')) {
    function memo_normalize_fields($fields) {
        if (is_string($fields)) {
            $fields = json_decode($fields, true);
        }
        $out = [];
        $seen = [];
        foreach ((array) $fields as $f) {
            if (!is_array($f)) {
                continue;
            }
            $key = strtolower(preg_replace('/[^a-z0-9_]/i', '', (string) ($f['key'] ?? '')));
            if ($key === '' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $type = in_array($f['type'] ?? '', ['text', 'textarea', 'date', 'number', 'select', 'assets'], true) ? $f['type'] : 'text';
            $source = (string) ($f['source'] ?? '');
            if ($type === 'select' && !isset(memo_lookup_sources()[$source])) {
                $type = 'text'; // unknown dropdown source - fall back to free text
                $source = '';
            }
            $out[] = [
                'key' => $key,
                'label' => trim((string) ($f['label'] ?? $key)),
                'label_ar' => trim((string) ($f['label_ar'] ?? '')),
                'type' => $type,
                'source' => $type === 'select' ? $source : '',
                // Text fields can take a separate Arabic value; dates/numbers are shared;
                // a dropdown always carries both names from its table. 'assets' = long text
                // with a picker that adds lines from the asset inventory (search_assets).
                'bilingual' => $type === 'select' || $type === 'assets' || (in_array($type, ['text', 'textarea'], true) && !empty($f['bilingual'])),
                'required' => !empty($f['required']),
            ];
        }
        return $out;
    }
}

/**
 * Templates from the DB: key => [id, label, label_ar, icon, subject, subject_ar, body,
 * body_ar, fields, is_active, is_builtin, ...]. Only active ones unless $includeInactive.
 */
if (!function_exists('memo_templates')) {
    function memo_templates($conDB = null, $includeInactive = false) {
        $conDB = $conDB ?: ($GLOBALS['conDB'] ?? null);
        $defaults = memo_default_templates();
        if (!$conDB) {
            return $defaults;
        }
        memo_ensure_table($conDB);
        $res = $conDB->query("SELECT * FROM memo_templates" . ($includeInactive ? '' : ' WHERE is_active = 1') . " ORDER BY sort_order, label");
        $out = [];
        while ($res && ($r = $res->fetch_assoc())) {
            $out[$r['template_key']] = [
                'id' => (int) $r['id'],
                'label' => $r['label'],
                'label_ar' => (string) $r['label_ar'],
                'icon' => $r['icon'],
                'subject' => $r['subject'],
                'subject_ar' => (string) $r['subject_ar'],
                'body' => $r['body_html'],
                'body_ar' => (string) $r['body_ar_html'],
                'fields' => memo_normalize_fields($r['fields_json']),
                'is_active' => (int) $r['is_active'],
                'is_builtin' => isset($defaults[$r['template_key']]),
                'sort_order' => (int) $r['sort_order'],
                'updated_by' => $r['updated_by'],
                'updated_at' => $r['updated_at'],
            ];
        }
        return $out;
    }
}

/** Label for any memo_type key, including inactive/deleted templates (history rows). */
if (!function_exists('memo_type_label')) {
    function memo_type_label($conDB, $key) {
        static $labels = null;
        if ($labels === null) {
            $labels = [];
            foreach (memo_templates($conDB, true) as $k => $tpl) {
                $labels[$k] = $tpl['label'];
            }
            foreach (memo_default_templates() as $k => $tpl) {
                $labels[$k] = $labels[$k] ?? $tpl['label'];
            }
        }
        return $labels[$key] ?? ucwords(str_replace('_', ' ', (string) $key));
    }
}

/** Employee-data placeholders a template can use (template editor chips). */
if (!function_exists('memo_placeholder_list')) {
    function memo_placeholder_list() {
        return [
            'employee_name' => 'Employee name', 'emp_id' => 'Employee ID', 'iqama' => 'Iqama / ID No.',
            'job_title' => 'Job title', 'department' => 'Department', 'company' => 'Company',
            'nationality' => 'Nationality', 'location' => 'Location', 'joining_date' => 'Joining date',
            'basic_salary' => 'Basic salary', 'total_salary' => 'Total salary', 'salary_table' => 'Salary breakdown table',
            'today' => 'Today\'s date', 'reference_no' => 'Reference No.', 'sender_name' => 'Sender (you)',
        ];
    }
}

/**
 * Built-in bilingual templates (seed + "Reset to default" source; the live copies are
 * in the memo_templates table). {{f:key}} = the template's own fields.
 */
if (!function_exists('memo_default_templates')) {
    function memo_default_templates() {
        // field helper: key, English label, Arabic label, type, bilingual, required
        $f = function ($key, $label, $labelAr, $type = 'text', $bilingual = false, $required = true) {
            return ['key' => $key, 'label' => $label, 'label_ar' => $labelAr, 'type' => $type, 'bilingual' => $bilingual, 'required' => $required];
        };
        // dropdown from a memo_lookup_sources() table (English + Arabic names)
        $sel = function ($key, $label, $labelAr, $source, $required = true) {
            return ['key' => $key, 'label' => $label, 'label_ar' => $labelAr, 'type' => 'select', 'source' => $source, 'bilingual' => true, 'required' => $required];
        };

        $head = '<p><strong>To:</strong> {{employee_name}} (Employee ID: {{emp_id}})<br><strong>Position:</strong> {{job_title}}<br><strong>Department:</strong> {{department}}<br><strong>Date:</strong> {{today}}<br><strong>Ref:</strong> {{reference_no}}</p>';
        $headAr = '<p><strong>إلى:</strong> {{employee_name}} (الرقم الوظيفي: {{emp_id}})<br><strong>الوظيفة:</strong> {{job_title}}<br><strong>القسم:</strong> {{department}}<br><strong>التاريخ:</strong> {{today}}<br><strong>المرجع:</strong> {{reference_no}}</p>';
        $sign = '<p>Regards,<br><strong>{{sender_name}}</strong><br>Human Resources Department<br>{{company}}</p>';
        $signAr = '<p>وتفضلوا بقبول فائق الاحترام،<br><strong>{{sender_name}}</strong><br>إدارة الموارد البشرية<br>{{company}}</p>';
        $dear = '<p>Dear {{employee_name}},</p>';
        $dearAr = '<p>السيد/ {{employee_name}} المحترم،</p><p>السلام عليكم ورحمة الله وبركاته،</p>';
        $cert = function ($title) {
            return '<p><strong>Date:</strong> {{today}}<br><strong>Ref:</strong> {{reference_no}}</p><h3 style="text-align:center">' . $title . '</h3><p><strong>{{f:addressed_to}}</strong></p>';
        };
        $certAr = function ($title) {
            return '<p><strong>التاريخ:</strong> {{today}}<br><strong>المرجع:</strong> {{reference_no}}</p><h3 style="text-align:center">' . $title . '</h3><p><strong>{{f:addressed_to}}</strong></p>';
        };
        $addressed = $f('addressed_to', 'Addressed to', 'موجهة إلى', 'text', true, false);
        $effective = $f('effective_date', 'Effective date', 'تاريخ السريان', 'date');

        $t = [];
        $t['general_memo'] = [
            'label' => 'General Memo', 'label_ar' => 'مذكرة عامة', 'icon' => 'fa-note-sticky',
            'subject' => 'Memo: {{f:topic}}', 'subject_ar' => 'مذكرة: {{f:topic}}',
            'fields' => [$f('topic', 'Topic', 'الموضوع', 'text', true), $f('details', 'Details', 'التفاصيل', 'textarea', true)],
            'body' => $head . $dear . '<p>{{f:details}}</p><p>For any clarification, please contact the HR Department.</p>' . $sign,
            'body_ar' => $headAr . $dearAr . '<p>{{f:details}}</p><p>لأي استفسار يرجى التواصل مع إدارة الموارد البشرية.</p>' . $signAr,
        ];
        $t['employee_notice'] = [
            'label' => 'Employee Notice', 'label_ar' => 'إشعار موظف', 'icon' => 'fa-bullhorn',
            'subject' => 'Notice - {{employee_name}} ({{emp_id}})', 'subject_ar' => 'إشعار - {{employee_name}}',
            'fields' => [$f('matter', 'Notice regarding', 'بخصوص', 'textarea', true), $f('action', 'Required action', 'الإجراء المطلوب', 'textarea', true), $f('deadline', 'Deadline', 'الموعد النهائي', 'date')],
            'body' => $head . $dear . '<p>This is a formal notice regarding: {{f:matter}}</p><p>You are kindly requested to {{f:action}} on or before <strong>{{f:deadline}}</strong>.</p>' . $sign,
            'body_ar' => $headAr . $dearAr . '<p>نود إشعاركم رسمياً بخصوص: {{f:matter}}</p><p>نرجو منكم {{f:action}} في موعد أقصاه <strong>{{f:deadline}}</strong>.</p>' . $signAr,
        ];
        $t['warning_letter'] = [
            'label' => 'Warning Letter', 'label_ar' => 'خطاب إنذار', 'icon' => 'fa-triangle-exclamation',
            'subject' => 'Warning Letter - {{employee_name}} ({{emp_id}})', 'subject_ar' => 'خطاب إنذار - {{employee_name}}',
            'fields' => [
                $f('warning_type', 'Warning type (e.g. First written warning)', 'نوع الإنذار (مثال: إنذار كتابي أول)', 'text', true),
                $f('violation', 'Violation / incident', 'المخالفة / الواقعة', 'textarea', true),
                $f('incident_date', 'Incident date', 'تاريخ الواقعة', 'date'),
            ],
            'body' => $head . $dear . '<p>This letter serves as a <strong>{{f:warning_type}}</strong> regarding the following violation that occurred on <strong>{{f:incident_date}}</strong>:</p><p>{{f:violation}}</p><p>This is in breach of the company policies and the Saudi Labor Law. You are expected to correct it immediately. Any repetition may lead to further disciplinary action, up to and including termination of employment.</p><p>Please sign a copy of this letter to acknowledge receipt.</p>' . $sign,
            'body_ar' => $headAr . $dearAr . '<p>يعتبر هذا الخطاب <strong>{{f:warning_type}}</strong> بخصوص المخالفة التالية التي وقعت بتاريخ <strong>{{f:incident_date}}</strong>:</p><p>{{f:violation}}</p><p>ويعد ذلك مخالفة لسياسات الشركة ونظام العمل السعودي، ونأمل منكم تصحيح ذلك فوراً، علماً بأن تكرار المخالفة قد يعرضكم لإجراءات تأديبية أشد قد تصل إلى إنهاء الخدمة.</p><p>نرجو التوقيع على نسخة من هذا الخطاب بما يفيد الاستلام.</p>' . $signAr,
        ];
        $t['salary_certificate'] = [
            'label' => 'Salary Certificate', 'label_ar' => 'شهادة راتب', 'icon' => 'fa-money-check-dollar',
            'subject' => 'Salary Certificate - {{employee_name}} ({{emp_id}})', 'subject_ar' => 'شهادة راتب - {{employee_name}}',
            'fields' => [$addressed],
            'body' => $cert('Salary Certificate') . '<p>This is to certify that <strong>{{employee_name}}</strong>, {{nationality}} national, holder of Iqama/ID No. <strong>{{iqama}}</strong>, is employed with <strong>{{company}}</strong> as <strong>{{job_title}}</strong> since <strong>{{joining_date}}</strong>, with a total monthly salary of <strong>SAR {{total_salary}}</strong>:</p>{{salary_table}}<p>This certificate is issued upon the employee\'s request without any liability on the company.</p>' . $sign,
            'body_ar' => $certAr('شهادة راتب') . '<p>تشهد الشركة بأن السيد/ <strong>{{employee_name}}</strong>، {{nationality}} الجنسية، حامل إقامة/هوية رقم <strong>{{iqama}}</strong>، يعمل لدى <strong>{{company}}</strong> بوظيفة <strong>{{job_title}}</strong> منذ <strong>{{joining_date}}</strong>، ويتقاضى راتباً شهرياً إجمالياً قدره <strong>{{total_salary}} ريال</strong>:</p>{{salary_table}}<p>وقد أعطيت له هذه الشهادة بناءً على طلبه دون أدنى مسؤولية على الشركة.</p>' . $signAr,
        ];
        $t['employment_certificate'] = [
            'label' => 'Employment Certificate', 'label_ar' => 'شهادة عمل', 'icon' => 'fa-id-badge',
            'subject' => 'Employment Certificate - {{employee_name}} ({{emp_id}})', 'subject_ar' => 'شهادة عمل - {{employee_name}}',
            'fields' => [$addressed],
            'body' => $cert('Employment Certificate') . '<p>This is to certify that <strong>{{employee_name}}</strong>, {{nationality}} national, holder of Iqama/ID No. <strong>{{iqama}}</strong>, is currently employed with <strong>{{company}}</strong> as <strong>{{job_title}}</strong> in the <strong>{{department}}</strong> department since <strong>{{joining_date}}</strong>.</p><p>This certificate is issued upon the employee\'s request without any liability on the company.</p>' . $sign,
            'body_ar' => $certAr('شهادة عمل') . '<p>تشهد الشركة بأن السيد/ <strong>{{employee_name}}</strong>، {{nationality}} الجنسية، حامل إقامة/هوية رقم <strong>{{iqama}}</strong>، يعمل حالياً لدى <strong>{{company}}</strong> بوظيفة <strong>{{job_title}}</strong> في قسم <strong>{{department}}</strong> منذ <strong>{{joining_date}}</strong>.</p><p>وقد أعطيت له هذه الشهادة بناءً على طلبه دون أدنى مسؤولية على الشركة.</p>' . $signAr,
        ];
        $t['experience_certificate'] = [
            'label' => 'Experience Certificate', 'label_ar' => 'شهادة خبرة', 'icon' => 'fa-award',
            'subject' => 'Experience Certificate - {{employee_name}} ({{emp_id}})', 'subject_ar' => 'شهادة خبرة - {{employee_name}}',
            'fields' => [$addressed, $f('last_working_date', 'Last working date', 'آخر يوم عمل', 'date')],
            'body' => $cert('Experience Certificate') . '<p>This is to certify that <strong>{{employee_name}}</strong>, {{nationality}} national, holder of Iqama/ID No. <strong>{{iqama}}</strong>, worked with <strong>{{company}}</strong> as <strong>{{job_title}}</strong> from <strong>{{joining_date}}</strong> to <strong>{{f:last_working_date}}</strong>.</p><p>During this period the employee showed dedication and professionalism. We wish them success in their future career.</p>' . $sign,
            'body_ar' => $certAr('شهادة خبرة') . '<p>تشهد الشركة بأن السيد/ <strong>{{employee_name}}</strong>، {{nationality}} الجنسية، حامل إقامة/هوية رقم <strong>{{iqama}}</strong>، قد عمل لدى <strong>{{company}}</strong> بوظيفة <strong>{{job_title}}</strong> خلال الفترة من <strong>{{joining_date}}</strong> إلى <strong>{{f:last_working_date}}</strong>.</p><p>وقد أبدى خلال هذه الفترة إخلاصاً ومهنية في العمل، ونتمنى له التوفيق في مسيرته المهنية.</p>' . $signAr,
        ];
        $t['promotion_letter'] = [
            'label' => 'Promotion Letter', 'label_ar' => 'خطاب ترقية', 'icon' => 'fa-arrow-trend-up',
            'subject' => 'Promotion Letter - {{employee_name}} ({{emp_id}})', 'subject_ar' => 'خطاب ترقية - {{employee_name}}',
            'fields' => [$sel('new_position', 'New position', 'المسمى الوظيفي الجديد', 'jobs'), $effective, $f('new_salary', 'New total salary (SAR)', 'الراتب الإجمالي الجديد (ريال)', 'number')],
            'body' => $head . $dear . '<p>We are pleased to inform you that, in recognition of your performance and contribution, you have been <strong>promoted</strong> from <strong>{{job_title}}</strong> to <strong>{{f:new_position}}</strong>, effective <strong>{{f:effective_date}}</strong>, with a new total monthly salary of <strong>SAR {{f:new_salary}}</strong>.</p><p>Congratulations, and we wish you continued success.</p>' . $sign,
            'body_ar' => $headAr . $dearAr . '<p>يسرنا إبلاغكم بأنه تقديراً لأدائكم وجهودكم، فقد تمت <strong>ترقيتكم</strong> من وظيفة <strong>{{job_title}}</strong> إلى وظيفة <strong>{{f:new_position}}</strong> اعتباراً من <strong>{{f:effective_date}}</strong>، براتب شهري إجمالي جديد قدره <strong>{{f:new_salary}} ريال</strong>.</p><p>نهنئكم ونتمنى لكم دوام التوفيق.</p>' . $signAr,
        ];
        $t['salary_increment_letter'] = [
            'label' => 'Salary Increment Letter', 'label_ar' => 'خطاب زيادة راتب', 'icon' => 'fa-sack-dollar',
            'subject' => 'Salary Increment - {{employee_name}} ({{emp_id}})', 'subject_ar' => 'زيادة راتب - {{employee_name}}',
            'fields' => [$f('increment_amount', 'Increment amount (SAR)', 'مبلغ الزيادة (ريال)', 'number'), $f('new_salary', 'New total salary (SAR)', 'الراتب الإجمالي الجديد (ريال)', 'number'), $effective],
            'body' => $head . $dear . '<p>We are pleased to inform you that your salary has been increased by <strong>SAR {{f:increment_amount}}</strong>, effective <strong>{{f:effective_date}}</strong>.</p><p>Current total monthly salary: <strong>SAR {{total_salary}}</strong><br>New total monthly salary: <strong>SAR {{f:new_salary}}</strong></p><p>We appreciate your efforts and look forward to your continued contribution.</p>' . $sign,
            'body_ar' => $headAr . $dearAr . '<p>يسرنا إبلاغكم بأنه تمت زيادة راتبكم بمبلغ <strong>{{f:increment_amount}} ريال</strong> اعتباراً من <strong>{{f:effective_date}}</strong>.</p><p>الراتب الشهري الإجمالي الحالي: <strong>{{total_salary}} ريال</strong><br>الراتب الشهري الإجمالي الجديد: <strong>{{f:new_salary}} ريال</strong></p><p>نقدر جهودكم ونتطلع إلى استمرار عطائكم.</p>' . $signAr,
        ];
        $t['job_change'] = [
            'label' => 'Job/Position Change', 'label_ar' => 'تغيير المسمى الوظيفي', 'icon' => 'fa-briefcase',
            'subject' => 'Change of Position - {{employee_name}} ({{emp_id}})', 'subject_ar' => 'تغيير المسمى الوظيفي - {{employee_name}}',
            'fields' => [$sel('new_position', 'New position', 'المسمى الوظيفي الجديد', 'jobs'), $effective],
            'body' => $head . $dear . '<p>Please be informed that your position will change from <strong>{{job_title}}</strong> to <strong>{{f:new_position}}</strong>, effective <strong>{{f:effective_date}}</strong>.</p><p>Your responsibilities and reporting line will be communicated by your manager.</p>' . $sign,
            'body_ar' => $headAr . $dearAr . '<p>نحيطكم علماً بأنه سيتم تغيير مسماكم الوظيفي من <strong>{{job_title}}</strong> إلى <strong>{{f:new_position}}</strong> اعتباراً من <strong>{{f:effective_date}}</strong>.</p><p>وسيقوم مديركم المباشر بإبلاغكم بالمهام والمسؤوليات الجديدة.</p>' . $signAr,
        ];
        $t['department_transfer'] = [
            'label' => 'Department Transfer', 'label_ar' => 'نقل إلى قسم آخر', 'icon' => 'fa-people-arrows',
            'subject' => 'Department Transfer - {{employee_name}} ({{emp_id}})', 'subject_ar' => 'نقل إلى قسم آخر - {{employee_name}}',
            'fields' => [$sel('new_department', 'New department', 'القسم الجديد', 'departments'), $effective],
            'body' => $head . $dear . '<p>Please be informed that you are being transferred from the <strong>{{department}}</strong> department to the <strong>{{f:new_department}}</strong> department, effective <strong>{{f:effective_date}}</strong>.</p><p>Kindly complete the handover of your current duties before the effective date.</p>' . $sign,
            'body_ar' => $headAr . $dearAr . '<p>نحيطكم علماً بأنه سيتم نقلكم من قسم <strong>{{department}}</strong> إلى قسم <strong>{{f:new_department}}</strong> اعتباراً من <strong>{{f:effective_date}}</strong>.</p><p>نرجو إتمام تسليم مهامكم الحالية قبل تاريخ السريان.</p>' . $signAr,
        ];
        $t['location_transfer'] = [
            'label' => 'Location/Branch Transfer', 'label_ar' => 'نقل إلى موقع / فرع آخر', 'icon' => 'fa-location-dot',
            'subject' => 'Location Transfer - {{employee_name}} ({{emp_id}})', 'subject_ar' => 'نقل إلى موقع آخر - {{employee_name}}',
            'fields' => [$sel('new_location', 'New location / branch', 'الموقع / الفرع الجديد', 'locations'), $effective, $f('report_to', 'Report to', 'المسؤول المباشر', 'text', true)],
            'body' => $head . $dear . '<p>Please be informed that your work location will change from <strong>{{location}}</strong> to <strong>{{f:new_location}}</strong>, effective <strong>{{f:effective_date}}</strong>.</p><p>Please report to <strong>{{f:report_to}}</strong> at the new location on the effective date.</p>' . $sign,
            'body_ar' => $headAr . $dearAr . '<p>نحيطكم علماً بأنه سيتم نقل مقر عملكم من <strong>{{location}}</strong> إلى <strong>{{f:new_location}}</strong> اعتباراً من <strong>{{f:effective_date}}</strong>.</p><p>نرجو مراجعة <strong>{{f:report_to}}</strong> في الموقع الجديد في تاريخ السريان.</p>' . $signAr,
        ];
        $t['contract_amendment'] = [
            'label' => 'Contract Amendment', 'label_ar' => 'تعديل عقد العمل', 'icon' => 'fa-file-signature',
            'subject' => 'Employment Contract Amendment - {{employee_name}} ({{emp_id}})', 'subject_ar' => 'تعديل عقد العمل - {{employee_name}}',
            'fields' => [$f('changes', 'Amended terms', 'البنود المعدلة', 'textarea', true), $effective],
            'body' => $head . $dear . '<p>This letter amends your employment contract with {{company}} as follows, effective <strong>{{f:effective_date}}</strong>:</p><p>{{f:changes}}</p><p>All other terms and conditions of your contract remain unchanged. Please sign to confirm your acceptance.</p>' . $sign,
            'body_ar' => $headAr . $dearAr . '<p>يعدل هذا الخطاب عقد عملكم مع {{company}} على النحو التالي اعتباراً من <strong>{{f:effective_date}}</strong>:</p><p>{{f:changes}}</p><p>وتبقى جميع بنود وشروط العقد الأخرى دون تغيير، نرجو التوقيع بما يفيد الموافقة.</p>' . $signAr,
        ];
        $t['leave_notice'] = [
            'label' => 'Leave Approval / Notice', 'label_ar' => 'إشعار / اعتماد إجازة', 'icon' => 'fa-umbrella-beach',
            'subject' => 'Leave Notice - {{employee_name}} ({{emp_id}})', 'subject_ar' => 'إشعار إجازة - {{employee_name}}',
            'fields' => [
                $f('leave_type', 'Leave type', 'نوع الإجازة', 'text', true), $f('start_date', 'Start date', 'تاريخ البداية', 'date'),
                $f('end_date', 'End date', 'تاريخ النهاية', 'date'), $f('days', 'Number of days', 'عدد الأيام', 'number'),
                $f('return_date', 'Return to work date', 'تاريخ العودة للعمل', 'date'),
            ],
            'body' => $head . $dear . '<p>This is to confirm that your <strong>{{f:leave_type}}</strong> leave from <strong>{{f:start_date}}</strong> to <strong>{{f:end_date}}</strong> ({{f:days}} days) has been <strong>approved</strong>.</p><p>You are expected to resume work on <strong>{{f:return_date}}</strong>. Please ensure proper handover before your leave starts.</p>' . $sign,
            'body_ar' => $headAr . $dearAr . '<p>نفيدكم بأنه تم <strong>اعتماد</strong> إجازتكم (<strong>{{f:leave_type}}</strong>) من <strong>{{f:start_date}}</strong> إلى <strong>{{f:end_date}}</strong> ولمدة ({{f:days}}) يوماً.</p><p>ويتعين عليكم مباشرة العمل بتاريخ <strong>{{f:return_date}}</strong>، مع التأكد من تسليم مهامكم قبل بدء الإجازة.</p>' . $signAr,
        ];
        $t['resignation_acknowledgement'] = [
            'label' => 'Resignation Acknowledgement', 'label_ar' => 'إفادة استلام الاستقالة', 'icon' => 'fa-door-open',
            'subject' => 'Resignation Acknowledgement - {{employee_name}} ({{emp_id}})', 'subject_ar' => 'استلام الاستقالة - {{employee_name}}',
            'fields' => [$f('resignation_date', 'Resignation date', 'تاريخ الاستقالة', 'date'), $f('last_working_day', 'Last working day', 'آخر يوم عمل', 'date')],
            'body' => $head . $dear . '<p>We acknowledge receipt of your resignation dated <strong>{{f:resignation_date}}</strong>. Your last working day will be <strong>{{f:last_working_day}}</strong>, in line with your notice period.</p><p>Please complete the handover of your duties and company assets. Your final settlement will be processed as per the Saudi Labor Law.</p><p>We thank you for your service and wish you success.</p>' . $sign,
            'body_ar' => $headAr . $dearAr . '<p>نفيدكم باستلام استقالتكم المؤرخة في <strong>{{f:resignation_date}}</strong>، وسيكون آخر يوم عمل لكم هو <strong>{{f:last_working_day}}</strong> وفقاً لفترة الإشعار.</p><p>نرجو إتمام تسليم المهام وعهد الشركة، وستتم تسوية مستحقاتكم النهائية وفقاً لنظام العمل السعودي.</p><p>نشكركم على جهودكم ونتمنى لكم التوفيق.</p>' . $signAr,
        ];
        $t['termination_notice'] = [
            'label' => 'Termination Notice', 'label_ar' => 'إشعار إنهاء خدمة', 'icon' => 'fa-user-xmark',
            'subject' => 'Termination Notice - {{employee_name}} ({{emp_id}})', 'subject_ar' => 'إشعار إنهاء خدمة - {{employee_name}}',
            'fields' => [$f('termination_date', 'Termination date', 'تاريخ إنهاء الخدمة', 'date'), $f('reason', 'Reason / Labor Law article', 'السبب / مادة نظام العمل', 'textarea', true)],
            'body' => $head . $dear . '<p>We regret to inform you that your employment with {{company}} will be terminated effective <strong>{{f:termination_date}}</strong> for the following reason:</p><p>{{f:reason}}</p><p>Please return all company assets and complete the clearance process. Your final settlement will be processed as per the Saudi Labor Law.</p>' . $sign,
            'body_ar' => $headAr . $dearAr . '<p>نأسف لإبلاغكم بأنه سيتم إنهاء خدمتكم لدى {{company}} اعتباراً من <strong>{{f:termination_date}}</strong> للسبب التالي:</p><p>{{f:reason}}</p><p>نرجو تسليم جميع عهد الشركة وإتمام إجراءات إخلاء الطرف، وستتم تسوية مستحقاتكم النهائية وفقاً لنظام العمل السعودي.</p>' . $signAr,
        ];
        $t['final_settlement'] = [
            'label' => 'Final Settlement Document', 'label_ar' => 'مخالصة نهائية', 'icon' => 'fa-file-invoice-dollar',
            'subject' => 'Final Settlement - {{employee_name}} ({{emp_id}})', 'subject_ar' => 'المخالصة النهائية - {{employee_name}}',
            'fields' => [
                $f('last_working_day', 'Last working day', 'آخر يوم عمل', 'date'), $f('eos_amount', 'End of service benefit (SAR)', 'مكافأة نهاية الخدمة (ريال)', 'number'),
                $f('leave_encashment', 'Leave encashment (SAR)', 'بدل الإجازات (ريال)', 'number'), $f('deductions', 'Deductions (SAR)', 'الاستقطاعات (ريال)', 'number'),
                $f('net_amount', 'Net amount payable (SAR)', 'صافي المبلغ المستحق (ريال)', 'number'),
            ],
            'body' => $head . $dear . '<p>Please find below the summary of your final settlement with {{company}}:</p><ul><li>Joining date: {{joining_date}}</li><li>Last working day: {{f:last_working_day}}</li><li>End of service benefit: SAR {{f:eos_amount}}</li><li>Leave encashment: SAR {{f:leave_encashment}}</li><li>Deductions: SAR {{f:deductions}}</li><li><strong>Net amount payable: SAR {{f:net_amount}}</strong></li></ul><p>Please sign the clearance form to confirm receipt.</p>' . $sign,
            'body_ar' => $headAr . $dearAr . '<p>فيما يلي ملخص المخالصة النهائية الخاصة بكم مع {{company}}:</p><ul><li>تاريخ الالتحاق: {{joining_date}}</li><li>آخر يوم عمل: {{f:last_working_day}}</li><li>مكافأة نهاية الخدمة: {{f:eos_amount}} ريال</li><li>بدل الإجازات: {{f:leave_encashment}} ريال</li><li>الاستقطاعات: {{f:deductions}} ريال</li><li><strong>صافي المبلغ المستحق: {{f:net_amount}} ريال</strong></li></ul><p>نرجو التوقيع على نموذج إخلاء الطرف بما يفيد الاستلام.</p>' . $signAr,
        ];
        $t['policy_acknowledgement'] = [
            'label' => 'Policy Acknowledgement', 'label_ar' => 'إقرار بالاطلاع على سياسة', 'icon' => 'fa-clipboard-check',
            'subject' => 'Policy Acknowledgement - {{f:policy_name}}', 'subject_ar' => 'إقرار بالاطلاع على سياسة - {{f:policy_name}}',
            'fields' => [$f('policy_name', 'Policy name', 'اسم السياسة', 'text', true), $effective],
            'body' => $head . $dear . '<p>Please read the company <strong>{{f:policy_name}}</strong> policy, effective <strong>{{f:effective_date}}</strong>, carefully.</p><p>By replying to this email, you confirm that you have read, understood and agree to comply with this policy.</p>' . $sign,
            'body_ar' => $headAr . $dearAr . '<p>نرجو الاطلاع بعناية على سياسة الشركة <strong>{{f:policy_name}}</strong> السارية اعتباراً من <strong>{{f:effective_date}}</strong>.</p><p>وبالرد على هذا البريد فإنكم تقرون بالاطلاع على هذه السياسة وفهمها والالتزام بها.</p>' . $signAr,
        ];
        $t['asset_handover'] = [
            'label' => 'Asset Handover Memo', 'label_ar' => 'مذكرة تسليم عهدة', 'icon' => 'fa-laptop',
            'subject' => 'Asset Handover - {{employee_name}} ({{emp_id}})', 'subject_ar' => 'تسليم عهدة - {{employee_name}}',
            'fields' => [$f('assets', 'Asset(s) and serial / plate no.', 'العهدة والرقم التسلسلي / رقم اللوحة', 'assets', true), $f('handover_date', 'Handover date', 'تاريخ التسليم', 'date')],
            'body' => $head . $dear . '<p>The following company asset(s) have been handed over to you on <strong>{{f:handover_date}}</strong>:</p><p>{{f:assets}}</p><p>You are responsible for the proper use and safekeeping of these assets and must return them in good condition upon request or at the end of your employment.</p>' . $sign,
            'body_ar' => $headAr . $dearAr . '<p>تم تسليمكم عهدة الشركة التالية بتاريخ <strong>{{f:handover_date}}</strong>:</p><p>{{f:assets}}</p><p>وتتحملون مسؤولية الاستخدام السليم لهذه العهدة والمحافظة عليها، وإعادتها بحالة جيدة عند الطلب أو عند انتهاء الخدمة.</p>' . $signAr,
        ];
        $t['employee_complaint'] = [
            'label' => 'Employee Complaint', 'label_ar' => 'شكوى موظف', 'icon' => 'fa-comment-dots',
            'subject' => 'Your Complaint - Acknowledgement ({{reference_no}})', 'subject_ar' => 'إفادة استلام شكوى ({{reference_no}})',
            'fields' => [$f('complaint_subject', 'Complaint subject', 'موضوع الشكوى', 'text', true), $f('complaint_date', 'Complaint date', 'تاريخ الشكوى', 'date'), $f('reply_by', 'Reply expected by', 'موعد الرد المتوقع', 'date')],
            'body' => $head . $dear . '<p>We acknowledge receipt of your complaint dated <strong>{{f:complaint_date}}</strong> regarding <strong>{{f:complaint_subject}}</strong>.</p><p>The HR Department will review the matter confidentially and get back to you by <strong>{{f:reply_by}}</strong>.</p>' . $sign,
            'body_ar' => $headAr . $dearAr . '<p>نفيدكم باستلام شكواكم المؤرخة في <strong>{{f:complaint_date}}</strong> بخصوص <strong>{{f:complaint_subject}}</strong>.</p><p>وستقوم إدارة الموارد البشرية بدراسة الموضوع بسرية تامة والرد عليكم في موعد أقصاه <strong>{{f:reply_by}}</strong>.</p>' . $signAr,
        ];
        $t['employee_request'] = [
            'label' => 'Employee Request', 'label_ar' => 'طلب موظف', 'icon' => 'fa-hand',
            'subject' => 'Your Request - {{reference_no}}', 'subject_ar' => 'طلبكم - {{reference_no}}',
            'fields' => [$f('request_details', 'Request', 'الطلب', 'text', true), $f('decision', 'Decision (approved / processed / rejected)', 'القرار (موافقة / تم التنفيذ / رفض)', 'text', true), $f('remarks', 'Remarks', 'ملاحظات', 'textarea', true, false)],
            'body' => $head . $dear . '<p>With reference to your request regarding <strong>{{f:request_details}}</strong>, we would like to inform you that it has been <strong>{{f:decision}}</strong>.</p><p>{{f:remarks}}</p>' . $sign,
            'body_ar' => $headAr . $dearAr . '<p>بالإشارة إلى طلبكم بخصوص <strong>{{f:request_details}}</strong>، نفيدكم بأنه قد تم <strong>{{f:decision}}</strong>.</p><p>{{f:remarks}}</p>' . $signAr,
        ];
        $t['other_custom'] = [
            'label' => 'Other / Custom Memo', 'label_ar' => 'مذكرة أخرى', 'icon' => 'fa-pen-to-square',
            'subject' => '{{f:topic}}', 'subject_ar' => '{{f:topic}}',
            'fields' => [$f('topic', 'Subject', 'الموضوع', 'text', true), $f('content', 'Content', 'المحتوى', 'textarea', true)],
            'body' => $head . $dear . '<p>{{f:content}}</p>' . $sign,
            'body_ar' => $headAr . $dearAr . '<p>{{f:content}}</p>' . $signAr,
        ];
        return $t;
    }
}

/** Employee name for lists/selects: parseName() (first + last), then the UI language via getDisplayName(). */
if (!function_exists('memo_short_name')) {
    function memo_short_name($name) {
        $name = (string) $name;
        $short = function_exists('parseName') ? parseName($name) : $name;
        $short = $short !== '' ? $short : $name;
        return function_exists('getDisplayName') ? getDisplayName($short) : $short;
    }
}

if (!function_exists('memo_money')) {
    function memo_money($v) {
        return number_format((float) $v, 2);
    }
}

/**
 * Placeholder values for one employee, per language:
 * ['en' => [...], 'ar' => [...], '_email' => ..., '_personal_email' => ..., '_name' => ...]
 * Values are HTML-escaped (salary_table is HTML). Returns null if not found.
 */
if (!function_exists('memo_employee_placeholders')) {
    function memo_employee_placeholders($conDB, $empId, $senderName = '', $referenceNo = '') {
        // Login email (admin_login) is the one kept up to date for every user; the
        // employee record's emails are only a fallback.
        $sql = "SELECT e.emp_id, e.name, e.iqama, e.email, e.c_email, e.joining_date,
                       (SELECT al.email FROM admin_login al
                        WHERE al.emp_id = e.emp_id AND al.email IS NOT NULL AND al.email <> ''
                        ORDER BY al.status DESC, al.id DESC LIMIT 1) AS login_email,
                       c.comp_name, c.comp_name_ar, d.dep_nme, d.dep_nme_ar, j.job, j.job_ar,
                       co.name AS country_name, co.name_ar AS country_name_ar,
                       l.name_en AS location_name, l.name_ar AS location_name_ar,
                       s.basic, s.housing, s.transport, s.food, s.misc, s.cashier, s.fuel, s.tel, s.other, s.guard
                FROM employees e
                LEFT JOIN companies c ON c.comp_id = e.comp_no
                LEFT JOIN department d ON d.id = e.dept
                LEFT JOIN ac_jobs j ON j.id = e.actual_job
                LEFT JOIN countries co ON co.id = e.country
                LEFT JOIN locations l ON l.id = e.location_id
                LEFT JOIN (SELECT * FROM emp_salary WHERE status = 1 ORDER BY id DESC) s ON s.emp_id = e.emp_id
                WHERE e.emp_id = ?
                LIMIT 1";
        $stmt = $conDB->prepare($sql);
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('s', $empId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) {
            return null;
        }

        $parts = [
            ['Basic', 'الراتب الأساسي', $row['basic']], ['Housing', 'بدل السكن', $row['housing']],
            ['Transport', 'بدل النقل', $row['transport']], ['Food', 'بدل الطعام', $row['food']],
            ['Fuel', 'بدل الوقود', $row['fuel']], ['Telephone', 'بدل الهاتف', $row['tel']],
            ['Cashier', 'بدل صندوق', $row['cashier']], ['Guard', 'بدل حراسة', $row['guard']],
            ['Misc', 'بدلات متفرقة', $row['misc']], ['Other', 'بدلات أخرى', $row['other']],
        ];
        $cell = 'padding:5px 9px;border:1px solid #ddd';
        $total = 0;
        $rowsEn = $rowsAr = '';
        foreach ($parts as [$en, $ar, $amount]) {
            if ((float) $amount <= 0) {
                continue;
            }
            $total += (float) $amount;
            $rowsEn .= '<tr><td style="' . $cell . '">' . $en . '</td><td style="' . $cell . ';text-align:right">SAR ' . memo_money($amount) . '</td></tr>';
            $rowsAr .= '<tr><td style="' . $cell . '">' . $ar . '</td><td style="' . $cell . ';text-align:left">' . memo_money($amount) . ' ريال</td></tr>';
        }
        $rowsEn .= '<tr><td style="' . $cell . '"><strong>Total</strong></td><td style="' . $cell . ';text-align:right"><strong>SAR ' . memo_money($total) . '</strong></td></tr>';
        $rowsAr .= '<tr><td style="' . $cell . '"><strong>الإجمالي</strong></td><td style="' . $cell . ';text-align:left"><strong>' . memo_money($total) . ' ريال</strong></td></tr>';

        $joining = trim((string) $row['joining_date']);
        $joinTs = $joining !== '' ? strtotime(str_replace('/', '-', $joining)) : false;
        $joinText = $joinTs ? date('d/m/Y', $joinTs) : $joining;

        $h = function ($v) {
            return htmlspecialchars(trim((string) $v), ENT_QUOTES, 'UTF-8');
        };
        $nameEn = function_exists('getDisplayName') ? getDisplayName($row['name'], 'en') : $row['name'];
        $nameAr = function_exists('getDisplayName') ? getDisplayName($row['name'], 'ar') : $row['name'];
        $companyEn = $row['comp_name'] ?: (function_exists('get_setting') ? get_setting($conDB, 'site_title') : '');

        $common = [
            'emp_id' => $h($row['emp_id']),
            'iqama' => $h($row['iqama']),
            'joining_date' => $h($joinText),
            'basic_salary' => memo_money($row['basic']),
            'total_salary' => memo_money($total),
            'today' => date('d/m/Y'),
            'reference_no' => $h($referenceNo),
        ];
        return [
            'en' => $common + [
                'employee_name' => $h($nameEn),
                'sender_name' => $h($senderName),
                'job_title' => $h($row['job'] ?: '-'),
                'department' => $h($row['dep_nme'] ?: '-'),
                'company' => $h($companyEn),
                'nationality' => $h($row['country_name'] ?: '-'),
                'location' => $h($row['location_name'] ?: '-'),
                'salary_table' => '<table style="border-collapse:collapse;margin:8px 0 14px">' . $rowsEn . '</table>',
            ],
            'ar' => $common + [
                'employee_name' => $h($nameAr ?: $nameEn),
                'sender_name' => $h(($senderName !== '' && function_exists('getDisplayName') ? getDisplayName($senderName, 'ar') : '') ?: $senderName),
                'job_title' => $h($row['job_ar'] ?: ($row['job'] ?: '-')),
                'department' => $h($row['dep_nme_ar'] ?: ($row['dep_nme'] ?: '-')),
                'company' => $h($row['comp_name_ar'] ?: $companyEn),
                'nationality' => $h($row['country_name_ar'] ?: ($row['country_name'] ?: '-')),
                'location' => $h($row['location_name_ar'] ?: ($row['location_name'] ?: '-')),
                'salary_table' => '<table style="border-collapse:collapse;margin:8px 0 14px" dir="rtl">' . $rowsAr . '</table>',
            ],
            '_name' => $nameEn,
            '_email' => trim((string) ($row['login_email'] ?: ($row['c_email'] ?: $row['email']))),
            '_personal_email' => trim((string) $row['email']),
        ];
    }
}

if (!function_exists('memo_next_reference')) {
    function memo_next_reference($conDB) {
        memo_ensure_table($conDB);
        $year = date('Y');
        $res = $conDB->query("SELECT COUNT(*) AS n FROM employee_memos WHERE YEAR(created_at) = " . (int) $year);
        $n = $res ? ((int) $res->fetch_assoc()['n']) + 1 : 1;
        return sprintf('HR/MEMO/%s/%04d', $year, $n);
    }
}

/**
 * Local file for the email header logo. The App Settings 'logo' is the white version
 * (made for the dark sidebar) and is invisible on the white email, so the full-colour
 * bilingual logo is preferred. Embedded in the email (cid), not linked: a link to the
 * HR server (localhost / intranet) can't load in Outlook.
 */
if (!function_exists('memo_logo_path')) {
    function memo_logo_path($conDB) {
        $root = realpath(__DIR__ . '/..');
        $candidates = ['assets/logo/logo.png'];
        foreach (['white_logo', 'logo'] as $setting) {
            $v = function_exists('get_setting') ? (string) get_setting($conDB, $setting) : '';
            if ($v !== '' && !preg_match('#^https?://#i', $v)) {
                $candidates[] = ltrim($v, './');
            }
        }
        foreach ($candidates as $rel) {
            $path = $root . '/' . $rel;
            if (is_file($path) && preg_match('/\.(png|jpe?g|gif)$/i', $path)) {
                return $path;
            }
        }
        return null;
    }
}

/** Wrap the final (bilingual) body in a simple, email-safe letter layout (always light). */
if (!function_exists('memo_email_html')) {
    function memo_email_html($conDB, $subject, $bodyHtml) {
        $hasLogo = memo_logo_path($conDB) !== null; // embedded as cid:memo_logo by memo_send_email()
        return '<!doctype html><html><body style="margin:0;padding:24px;background:#f3f4f6;font-family:Segoe UI,Tahoma,Arial,sans-serif;color:#1f2937">'
            . '<div style="max-width:980px;margin:0 auto;background:#ffffff;border-radius:8px;overflow:hidden;border:1px solid #e5e7eb">'
            . ($hasLogo ? '<div style="padding:16px 28px;border-bottom:3px solid #25256e;text-align:center"><img src="cid:memo_logo" alt="" height="80" style="height:80px;width:auto;display:inline-block;border:0"></div>' : '')
            . '<div style="padding:24px 28px;font-size:14px;line-height:1.6">' . $bodyHtml . '</div>'
            . '<div style="padding:12px 28px;background:#f9fafb;color:#6b7280;font-size:12px;border-top:1px solid #e5e7eb">This memo was sent by the HR Department via the HR system. | أرسلت هذه المذكرة من إدارة الموارد البشرية عبر نظام الموارد البشرية.</div>'
            . '</div></body></html>';
    }
}

/**
 * Sends one memo email. Returns [bool ok, string error].
 * $cc: array of addresses.
 */
if (!function_exists('memo_send_email')) {
    function memo_send_email($conDB, $toEmail, $toName, $subject, $html, array $cc = []) {
        if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            $autoload = __DIR__ . '/../vendor/autoload.php';
            if (is_file($autoload)) {
                require_once $autoload;
            }
        }
        if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            return [false, 'Mailer library not available.'];
        }
        $host = get_setting($conDB, 'smtp_host');
        $port = (int) get_setting($conDB, 'smtp_port');
        $user = get_setting($conDB, 'smtp_user');
        $pass = get_setting($conDB, 'smtp_pass');
        $from = get_setting($conDB, 'from_email');
        $fromName = get_setting($conDB, 'from_name') ?: 'HR Department';
        $secure = strtolower((string) get_setting($conDB, 'smtp_encryption'));
        if (!$host || !$port || !$user || !$pass || !$from) {
            return [false, 'SMTP settings are incomplete (App Settings > Email).'];
        }

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $host;
            $mail->SMTPAuth = true;
            $mail->Username = $user;
            $mail->Password = $pass;
            if ($secure === 'tls') {
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($secure === 'ssl') {
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = false;
            }
            $mail->Port = $port;
            $mail->CharSet = 'UTF-8';
            $mail->Timeout = 15;
            $mail->setFrom($from, $fromName);
            $mail->addReplyTo($from, $fromName);
            $mail->addAddress($toEmail, $toName);
            foreach ($cc as $ccAddr) {
                $mail->addCC($ccAddr);
            }
            if (($logo = memo_logo_path($conDB)) && strpos($html, 'cid:memo_logo') !== false) {
                $mail->addEmbeddedImage($logo, 'memo_logo', basename($logo));
            }
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $html;
            $mail->AltBody = trim(html_entity_decode(strip_tags(preg_replace('#<br\s*/?>|</p>|</li>|</tr>|</td>#i', "\n", $html)), ENT_QUOTES, 'UTF-8'));
            $mail->send();
            return [true, ''];
        } catch (\Throwable $e) {
            return [false, $mail->ErrorInfo ?: $e->getMessage()];
        }
    }
}

/** Basic HTML clean-up for stored/sent HTML: drop scripts, event handlers, js: links. */
if (!function_exists('memo_clean_html')) {
    function memo_clean_html($html) {
        $html = preg_replace('#<(script|style|iframe|object|embed|form|input|button|textarea|select|link|meta)\b[^>]*>.*?</\1>#is', '', (string) $html);
        $html = preg_replace('#</?(script|style|iframe|object|embed|form|input|button|textarea|select|link|meta)\b[^>]*>#is', '', $html);
        $html = preg_replace('#\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $html);
        $html = preg_replace('#(href|src)\s*=\s*(["\'])\s*javascript:[^"\']*\2#i', '$1="#"', $html);
        return trim($html);
    }
}
