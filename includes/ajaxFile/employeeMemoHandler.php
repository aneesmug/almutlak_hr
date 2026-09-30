<?php
// Employees > Send Memo (employee_memos.php) + the Memos tab on view_employee.php.
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../session_check.php';
require_once __DIR__ . '/../page_access_helper.php';
require_once __DIR__ . '/../memo_helper.php';

// Same rules as the page (see memo_user_can()): send/view = Page Access role or the
// 'access_employee_memos' Special Access key; add/edit/delete templates = admin or
// the 'manage_memo_templates' key.
$canSendMemos = memo_user_can($conDB, 'send', $empid ?? '', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
$canManageTemplates = memo_user_can($conDB, 'templates', $empid ?? '', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);

function memo_json($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

if (!$canSendMemos) {
    memo_json(['status' => 'error', 'message' => 'Access denied.'], 403);
}

memo_ensure_table($conDB);
$action = $_REQUEST['action'] ?? '';
$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
$actorId = (string) ($empid ?? '');
$actorName = (string) ($fname ?? '');

/** Validated memo fields from POST (shared by send + draft). */
function memo_posted_fields($conDB, $requireEmail) {
    $f = [
        'emp_id' => trim((string) ($_POST['emp_id'] ?? '')),
        'memo_type' => (string) ($_POST['memo_type'] ?? ''),
        'subject' => trim((string) ($_POST['subject'] ?? '')),
        'body' => memo_clean_html($_POST['body'] ?? ''),
        'to_email' => trim((string) ($_POST['to_email'] ?? '')),
        'reference_no' => trim((string) ($_POST['reference_no'] ?? '')),
        'cc' => [],
        'is_manual' => !empty($_POST['is_manual']) ? 1 : 0,
        'field_values' => null,
    ];
    // {key: value} or {key: {en, ar}} - kept so a draft reopens with its fields filled.
    $values = json_decode((string) ($_POST['field_values'] ?? ''), true);
    if (is_array($values)) {
        $clean = [];
        foreach ($values as $k => $v) {
            $k = preg_replace('/[^a-z0-9_]/i', '', (string) $k);
            if ($k === '') {
                continue;
            }
            if (is_array($v)) {
                $item = ['en' => mb_substr((string) ($v['en'] ?? ''), 0, 5000), 'ar' => mb_substr((string) ($v['ar'] ?? ''), 0, 5000)];
                // Dropdown picks keep their ids so the draft reopens with them selected.
                foreach (['id', 'company_id', 'city_id'] as $idKey) {
                    if (isset($v[$idKey]) && $v[$idKey] !== '') {
                        $item[$idKey] = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $v[$idKey]);
                    }
                }
                $clean[$k] = $item;
            } else {
                $clean[$k] = mb_substr((string) $v, 0, 5000);
            }
        }
        $f['field_values'] = json_encode($clean, JSON_UNESCAPED_UNICODE);
    }
    if ($f['emp_id'] === '' || !isset(memo_templates($conDB, true)[$f['memo_type']])) {
        memo_json(['status' => 'error', 'message' => 'Select an employee and a memo type.'], 400);
    }
    if ($f['subject'] === '' || trim(strip_tags($f['body'])) === '') {
        memo_json(['status' => 'error', 'message' => 'Subject and message are required.'], 400);
    }
    if ($f['to_email'] !== '' || $requireEmail) {
        if (!filter_var($f['to_email'], FILTER_VALIDATE_EMAIL)) {
            memo_json(['status' => 'error', 'message' => 'Enter a valid employee email address.'], 400);
        }
    }
    foreach (preg_split('/[,;\s]+/', (string) ($_POST['cc'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) as $addr) {
        if (!filter_var($addr, FILTER_VALIDATE_EMAIL)) {
            memo_json(['status' => 'error', 'message' => 'Invalid CC address: ' . $addr], 400);
        }
        $f['cc'][] = $addr;
    }
    if (!memo_employee_placeholders($conDB, $f['emp_id'])) {
        memo_json(['status' => 'error', 'message' => 'Employee not found.'], 404);
    }
    if ($f['reference_no'] === '') {
        $f['reference_no'] = memo_next_reference($conDB);
    }
    return $f;
}

/** Existing draft row (id from POST draft_id), or null. Fails if the id isn't a draft. */
function memo_posted_draft_id($conDB) {
    $id = (int) ($_POST['draft_id'] ?? 0);
    if ($id <= 0) {
        return 0;
    }
    $stmt = $conDB->prepare("SELECT status FROM employee_memos WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row || $row['status'] !== 'draft') {
        memo_json(['status' => 'error', 'message' => 'This draft no longer exists or was already sent.'], 409);
    }
    return $id;
}

/** Insert or update (draft) one memo row; returns its id. */
function memo_store($conDB, $draftId, array $f, $status, $error, $actorId, $actorName) {
    $cc = implode(', ', $f['cc']);
    if ($draftId > 0) {
        $stmt = $conDB->prepare("UPDATE employee_memos SET emp_id = ?, memo_type = ?, reference_no = ?, subject = ?, body_html = ?, field_values = ?, is_manual = ?, sent_to = ?, cc = ?, status = ?, error_message = ?, sent_by = ?, sent_by_name = ?
                                 WHERE id = ? AND status = 'draft'");
        $stmt->bind_param('ssssssissssssi', $f['emp_id'], $f['memo_type'], $f['reference_no'], $f['subject'], $f['body'], $f['field_values'], $f['is_manual'], $f['to_email'], $cc, $status, $error, $actorId, $actorName, $draftId);
        $stmt->execute();
        $stmt->close();
        return $draftId;
    }
    $stmt = $conDB->prepare("INSERT INTO employee_memos (emp_id, memo_type, reference_no, subject, body_html, field_values, is_manual, sent_to, cc, status, error_message, sent_by, sent_by_name)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('ssssssissssss', $f['emp_id'], $f['memo_type'], $f['reference_no'], $f['subject'], $f['body'], $f['field_values'], $f['is_manual'], $f['to_email'], $cc, $status, $error, $actorId, $actorName);
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();
    return $id;
}

$templateActions = ['save_template', 'toggle_template', 'reset_template', 'delete_template'];
if ((in_array($action, $templateActions, true) || ($action === 'list_templates' && !empty($_POST['include_inactive']))) && !$canManageTemplates) {
    memo_json(['status' => 'error', 'message' => 'You do not have access to manage memo templates.'], 403);
}

switch ($action) {
    // select2 ajax: search by Employee ID, name or Iqama
    case 'search_employees':
        $term = trim((string) ($_GET['q'] ?? ''));
        $like = '%' . $term . '%';
        // Active employees only.
        $stmt = $conDB->prepare("SELECT e.emp_id, e.name, d.dep_nme, d.dep_nme_ar, c.comp_name, c.comp_name_ar
                                 FROM employees e
                                 LEFT JOIN department d ON d.id = e.dept
                                 LEFT JOIN companies c ON c.comp_id = e.comp_no
                                 WHERE e.status = 1 AND (e.name LIKE ? OR e.emp_id LIKE ? OR e.iqama LIKE ?)
                                 ORDER BY (e.emp_id = ?) DESC, e.name ASC LIMIT 30");
        $stmt->bind_param('ssss', $like, $like, $like, $term);
        $stmt->execute();
        $res = $stmt->get_result();
        $isAr = ($current_lang ?? 'en') === 'ar';
        $results = [];
        while ($r = $res->fetch_assoc()) {
            $name = memo_short_name($r['name']);
            $dept = (string) (($isAr ? $r['dep_nme_ar'] : '') ?: $r['dep_nme']);
            $company = (string) (($isAr ? $r['comp_name_ar'] : '') ?: $r['comp_name']);
            $results[] = [
                'id' => $r['emp_id'],
                'text' => $name . ' (' . $r['emp_id'] . ')' . ($dept !== '' || $company !== '' ? ' - ' . implode(' | ', array_filter([$dept, $company])) : ''),
                'name' => $name,
                'dept' => $dept,
                'company' => $company,
            ];
        }
        $stmt->close();
        memo_json(['results' => $results]);

    // select2 ajax for an 'assets' detail field: search the asset inventory by serial /
    // tracking / plate no., description or asset name. The memo employee's own assets
    // come first, then available stock. Each result carries the memo line (en + ar).
    case 'search_assets':
        $term = trim((string) ($_GET['q'] ?? ''));
        $like = '%' . $term . '%';
        $forEmp = trim((string) ($_GET['emp_id'] ?? ''));
        $rows = [];
        $stmt = $conDB->prepare("SELECT CONCAT('i', ai.id) AS id, a.name, ai.serial_number, ai.tracking_id, ai.description, ai.status,
                                        e.emp_id AS holder_id, e.name AS holder_name
                                 FROM asset_items ai
                                 JOIN assets a ON a.id = ai.asset_id
                                 LEFT JOIN employees e ON e.id = ai.assigned_emp_id AND ai.status = 'Assigned'
                                 WHERE ai.status IN ('Available', 'Assigned')
                                   AND (ai.serial_number LIKE ? OR ai.tracking_id LIKE ? OR ai.description LIKE ? OR a.name LIKE ?)
                                 ORDER BY (e.emp_id = ?) DESC, (ai.status = 'Available') DESC, a.name, ai.serial_number LIMIT 30");
        if ($stmt) {
            $stmt->bind_param('sssss', $like, $like, $like, $like, $forEmp);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
        // Assignments recorded only in employee_assets (no inventory item behind them).
        $stmt = $conDB->prepare("SELECT CONCAT('e', ea.id) AS id, a.name, ea.serial_number, '' AS tracking_id, ea.description, ea.status,
                                        ea.emp_id AS holder_id, e.name AS holder_name
                                 FROM employee_assets ea
                                 JOIN assets a ON a.id = ea.asset_id
                                 LEFT JOIN employees e ON e.emp_id = ea.emp_id
                                 WHERE ea.status = 'Assigned'
                                   AND (ea.serial_number LIKE ? OR ea.description LIKE ? OR a.name LIKE ?)
                                   AND NOT EXISTS (SELECT 1 FROM asset_items ai WHERE ai.asset_id = ea.asset_id
                                                   AND (ai.tracking_id = ea.serial_number OR ai.serial_number = ea.serial_number))
                                 ORDER BY (ea.emp_id = ?) DESC, a.name, ea.serial_number LIMIT 30");
        if ($stmt) {
            $stmt->bind_param('ssss', $like, $like, $like, $forEmp);
            $stmt->execute();
            $rows = array_merge($rows, $stmt->get_result()->fetch_all(MYSQLI_ASSOC));
            $stmt->close();
        }
        $results = [];
        foreach ($rows as $r) {
            $name = trim((string) $r['name']);
            $serial = trim((string) $r['serial_number']) ?: trim((string) $r['tracking_id']);
            $desc = trim((string) $r['description']);
            $isCar = (bool) preg_match('/\b(car|vehicle|truck)\b/i', $name);
            $holderId = (string) ($r['holder_id'] ?? '');
            $results[] = [
                'id' => $r['id'],
                'text' => implode(' - ', array_filter([$name, $desc, $serial])),
                'name' => $name,
                'serial' => $serial,
                'desc' => $desc,
                'status' => $r['status'],
                'holder' => $holderId !== '' ? memo_short_name((string) $r['holder_name']) . ' (' . $holderId . ')' : '',
                'own' => $forEmp !== '' && $holderId === $forEmp,
                'en' => $name . ($desc !== '' ? ' - ' . $desc : '') . ($serial !== '' ? ' - ' . ($isCar ? 'Plate No.' : 'S/N') . ': ' . $serial : ''),
                'ar' => $name . ($desc !== '' ? ' - ' . $desc : '') . ($serial !== '' ? ' - ' . ($isCar ? 'رقم اللوحة' : 'الرقم التسلسلي') . ': ' . $serial : ''),
            ];
        }
        // The memo employee's assets first, across both lists.
        usort($results, function ($a, $b) {
            return (int) $b['own'] - (int) $a['own'];
        });
        memo_json(['results' => $results]);

    // Template + the employee's data in both languages. The page fills
    // {{placeholders}} / {{f:fields}} itself so the bilingual preview is live.
    case 'render_template':
        $empId = trim((string) ($_POST['emp_id'] ?? ''));
        $type = (string) ($_POST['memo_type'] ?? '');
        $templates = memo_templates($conDB, true); // inactive too: an old draft may use one
        if ($empId === '' || !isset($templates[$type])) {
            memo_json(['status' => 'error', 'message' => 'Select an employee and a memo type.'], 400);
        }
        $ref = memo_next_reference($conDB);
        $data = memo_employee_placeholders($conDB, $empId, $actorName, $ref);
        if (!$data) {
            memo_json(['status' => 'error', 'message' => 'Employee not found.'], 404);
        }
        $tpl = $templates[$type];
        $options = [];
        foreach ($tpl['fields'] as $field) {
            if ($field['type'] === 'select' && !isset($options[$field['source']])) {
                $options[$field['source']] = $field['source'] === 'locations'
                    ? memo_location_tree($conDB)
                    : memo_lookup_options($conDB, $field['source']);
            }
        }
        memo_json([
            'status' => 'success',
            'template' => [
                'key' => $type, 'label' => $tpl['label'], 'label_ar' => $tpl['label_ar'],
                'subject' => $tpl['subject'], 'subject_ar' => $tpl['subject_ar'],
                'body' => $tpl['body'], 'body_ar' => $tpl['body_ar'], 'fields' => $tpl['fields'],
            ],
            'data' => ['en' => $data['en'], 'ar' => $data['ar']],
            'options' => $options ?: new stdClass(),
            'reference_no' => $ref,
            'email' => $data['_email'],
            'personal_email' => $data['_personal_email'],
        ]);

    case 'save_draft':
        if (!$isPost) {
            memo_json(['status' => 'error', 'message' => 'Invalid request.'], 405);
        }
        $draftId = memo_posted_draft_id($conDB);
        $f = memo_posted_fields($conDB, false);
        $id = memo_store($conDB, $draftId, $f, 'draft', null, $actorId, $actorName);
        memo_json(['status' => 'success', 'message' => 'Draft saved.', 'memo_id' => $id, 'reference_no' => $f['reference_no']]);

    case 'send_memo':
        if (!$isPost) {
            memo_json(['status' => 'error', 'message' => 'Invalid request.'], 405);
        }
        $draftId = memo_posted_draft_id($conDB);
        $f = memo_posted_fields($conDB, true);
        $emp = memo_employee_placeholders($conDB, $f['emp_id']);

        [$ok, $error] = memo_send_email($conDB, $f['to_email'], $emp['_name'], $f['subject'], memo_email_html($conDB, $f['subject'], $f['body']), $f['cc']);

        // Kept either way - a failed send is still visible on the master file. Sending
        // an opened draft turns that same row into the sent/failed record.
        $memoId = memo_store($conDB, $draftId, $f, $ok ? 'sent' : 'failed', $ok ? null : mb_substr($error, 0, 500), $actorId, $actorName);

        // In-portal notification so the employee also sees it without checking email.
        if ($ok && function_exists('create_browser_notification')) {
            @create_browser_notification($conDB, $f['emp_id'], memo_type_label($conDB, $f['memo_type']), $f['subject'], 'profile.php');
        }
        if (!$ok) {
            memo_json(['status' => 'error', 'message' => 'Email could not be sent: ' . $error . ' (saved in history as failed).', 'memo_id' => $memoId], 500);
        }
        memo_json(['status' => 'success', 'message' => 'Memo sent to ' . $f['to_email'] . '.', 'memo_id' => $memoId]);

    case 'delete_draft':
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $conDB->prepare("DELETE FROM employee_memos WHERE id = ? AND status = 'draft'");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $deleted = $stmt->affected_rows;
        $stmt->close();
        memo_json($deleted ? ['status' => 'success', 'message' => 'Draft deleted.'] : ['status' => 'error', 'message' => 'Only drafts can be deleted.'], $deleted ? 200 : 400);

    // History: all memos, or one employee's (view_employee.php Memos tab)
    case 'list_memos':
        $empId = trim((string) ($_POST['emp_id'] ?? ''));
        $sql = "SELECT m.id, m.emp_id, e.name AS employee_name, m.memo_type, m.reference_no, m.subject, m.sent_to, m.status, m.sent_by_name, m.created_at, m.updated_at
                FROM employee_memos m LEFT JOIN employees e ON e.emp_id = m.emp_id";
        if ($empId !== '') {
            $stmt = $conDB->prepare($sql . " WHERE m.emp_id = ? ORDER BY m.id DESC");
            $stmt->bind_param('s', $empId);
        } else {
            $stmt = $conDB->prepare($sql . " ORDER BY m.id DESC LIMIT 500");
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($r = $res->fetch_assoc()) {
            $r['memo_type_label'] = memo_type_label($conDB, $r['memo_type']);
            $r['employee_name'] = memo_short_name($r['employee_name']);
            $rows[] = $r;
        }
        $stmt->close();
        memo_json(['status' => 'success', 'data' => $rows]);

    case 'get_memo':
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $conDB->prepare("SELECT m.*, e.name AS employee_name FROM employee_memos m LEFT JOIN employees e ON e.emp_id = m.emp_id WHERE m.id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) {
            memo_json(['status' => 'error', 'message' => 'Memo not found.'], 404);
        }
        $row['memo_type_label'] = memo_type_label($conDB, $row['memo_type']);
        $row['field_values'] = json_decode((string) $row['field_values'], true) ?: new stdClass();
        $row['employee_display'] = memo_short_name($row['employee_name']) . ' (' . $row['emp_id'] . ')';
        memo_json(['status' => 'success', 'data' => $row]);

    // English -> Arabic for a detail field's Arabic box (HR can still correct it).
    // Same MyMemory + translation_cache path as the rest of the app (auto_translate_text());
    // that API takes max ~500 chars, so longer text goes line by line / in chunks.
    case 'translate':
        $text = trim((string) ($_POST['q'] ?? ''));
        if ($text === '') {
            memo_json(['status' => 'success', 'text' => '']);
        }
        $text = mb_substr($text, 0, 3000);
        // Only the function is needed; that file's endpoint code runs only for POST 'text'.
        require_once __DIR__ . '/translateText.php';
        if (!function_exists('auto_translate_text')) {
            memo_json(['status' => 'error', 'message' => 'Translation is not available.'], 500);
        }
        $out = [];
        foreach (preg_split('/\r?\n/', $text) as $line) {
            if (trim($line) === '') {
                $out[] = '';
                continue;
            }
            $pieces = [];
            $buf = '';
            foreach (preg_split('/(?<=[.!?;])\s+/u', $line) as $sentence) {
                if ($buf !== '' && mb_strlen($buf . ' ' . $sentence) > 450) {
                    $pieces[] = $buf;
                    $buf = '';
                }
                $buf = $buf === '' ? $sentence : $buf . ' ' . $sentence;
            }
            if ($buf !== '') {
                $pieces[] = $buf;
            }
            $out[] = implode(' ', array_map(function ($p) {
                return auto_translate_text(mb_substr($p, 0, 490), 'en', 'ar');
            }, $pieces));
        }
        $translated = implode("\n", $out);
        memo_json(['status' => 'success', 'text' => $translated, 'translated' => $translated !== $text]);

    // ---------- Manage Templates ----------
    case 'list_templates':
        $list = [];
        foreach (memo_templates($conDB, !empty($_POST['include_inactive'])) as $key => $tpl) {
            $tpl['key'] = $key;
            $list[] = $tpl;
        }
        $sources = [];
        foreach (memo_lookup_sources() as $k => $src) {
            $sources[$k] = $src['label'];
        }
        memo_json(['status' => 'success', 'data' => $list, 'placeholders' => memo_placeholder_list(), 'sources' => $sources]);

    case 'save_template':
        if (!$isPost) {
            memo_json(['status' => 'error', 'message' => 'Invalid request.'], 405);
        }
        $key = trim((string) ($_POST['template_key'] ?? ''));
        $label = trim((string) ($_POST['label'] ?? ''));
        $labelAr = trim((string) ($_POST['label_ar'] ?? ''));
        $icon = preg_replace('/[^a-z0-9\- ]/i', '', (string) ($_POST['icon'] ?? '')) ?: 'fa-envelope';
        $subject = trim((string) ($_POST['subject'] ?? ''));
        $subjectAr = trim((string) ($_POST['subject_ar'] ?? ''));
        $body = memo_clean_html($_POST['body'] ?? '');
        $bodyAr = memo_clean_html($_POST['body_ar'] ?? '');
        $fieldsJson = json_encode(memo_normalize_fields((string) ($_POST['fields_json'] ?? '[]')), JSON_UNESCAPED_UNICODE);
        if ($label === '' || $subject === '' || trim(strip_tags($body)) === '') {
            memo_json(['status' => 'error', 'message' => 'Name, English subject and English body are required.'], 400);
        }
        if ($key === '') {
            // New custom template
            $key = 'custom_' . date('YmdHis') . '_' . substr(md5(uniqid('', true)), 0, 4);
            $order = (int) ($conDB->query("SELECT COALESCE(MAX(sort_order), 0) + 10 AS o FROM memo_templates")->fetch_assoc()['o'] ?? 999);
            $stmt = $conDB->prepare("INSERT INTO memo_templates (template_key, label, label_ar, icon, subject, subject_ar, body_html, body_ar_html, fields_json, sort_order, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('sssssssssis', $key, $label, $labelAr, $icon, $subject, $subjectAr, $body, $bodyAr, $fieldsJson, $order, $actorName);
        } else {
            $stmt = $conDB->prepare("UPDATE memo_templates SET label = ?, label_ar = ?, icon = ?, subject = ?, subject_ar = ?, body_html = ?, body_ar_html = ?, fields_json = ?, updated_by = ? WHERE template_key = ?");
            $stmt->bind_param('ssssssssss', $label, $labelAr, $icon, $subject, $subjectAr, $body, $bodyAr, $fieldsJson, $actorName, $key);
        }
        $stmt->execute();
        $stmt->close();
        memo_json(['status' => 'success', 'message' => 'Template saved.', 'template_key' => $key]);

    case 'toggle_template':
        $key = (string) ($_POST['template_key'] ?? '');
        $active = !empty($_POST['is_active']) ? 1 : 0;
        $stmt = $conDB->prepare("UPDATE memo_templates SET is_active = ?, updated_by = ? WHERE template_key = ?");
        $stmt->bind_param('iss', $active, $actorName, $key);
        $stmt->execute();
        $stmt->close();
        memo_json(['status' => 'success']);

    case 'reset_template':
        $key = (string) ($_POST['template_key'] ?? '');
        $defaults = memo_default_templates();
        if (!isset($defaults[$key])) {
            memo_json(['status' => 'error', 'message' => 'Only built-in templates can be reset.'], 400);
        }
        $d = $defaults[$key];
        $fieldsJson = json_encode($d['fields'], JSON_UNESCAPED_UNICODE);
        $stmt = $conDB->prepare("UPDATE memo_templates SET label = ?, label_ar = ?, icon = ?, subject = ?, subject_ar = ?, body_html = ?, body_ar_html = ?, fields_json = ?, is_active = 1, updated_by = ? WHERE template_key = ?");
        $stmt->bind_param('ssssssssss', $d['label'], $d['label_ar'], $d['icon'], $d['subject'], $d['subject_ar'], $d['body'], $d['body_ar'], $fieldsJson, $actorName, $key);
        $stmt->execute();
        $stmt->close();
        memo_json(['status' => 'success', 'message' => 'Template reset to default.']);

    case 'delete_template':
        // Custom templates only - built-ins can be deactivated instead. History rows
        // keep their memo_type; memo_type_label() still names them.
        $key = (string) ($_POST['template_key'] ?? '');
        if (isset(memo_default_templates()[$key])) {
            memo_json(['status' => 'error', 'message' => 'Built-in templates cannot be deleted - deactivate it instead.'], 400);
        }
        $stmt = $conDB->prepare("DELETE FROM memo_templates WHERE template_key = ?");
        $stmt->bind_param('s', $key);
        $stmt->execute();
        $stmt->close();
        memo_json(['status' => 'success', 'message' => 'Template deleted.']);

    default:
        memo_json(['status' => 'error', 'message' => 'Unknown action.'], 400);
}
