<?php
/**
 * App Settings > Org Structure > Document Expiry Alerts: CRUD for
 * doc_expiry_notify_rules + preview / send-now. Engine: includes/doc_expiry_rules_helper.php.
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../session_check.php';
require_once __DIR__ . '/../special_access_helper.php';
require_once __DIR__ . '/../doc_expiry_rules_helper.php';

function der_reply($ok, $message = '', array $extra = [], $code = 200) {
    http_response_code($code);
    echo json_encode(['success' => $ok, 'message' => $message] + $extra);
    exit;
}

$canManage = $is_system_admin
    || user_has_special_access($conDB, $empid ?? '', 'manage_doc_expiry_alerts', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
if (!$canManage) {
    der_reply(false, 'Access denied.', [], 403);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    der_reply(false, 'Invalid request method.', [], 405);
}

doc_expiry_rules_ensure_tables($conDB);
$types = doc_expiry_rule_types();
$action = $_POST['action'] ?? '';

/** emp_id => display name for the given ids. */
function der_names($conDB, array $ids) {
    $ids = array_values(array_filter(array_map('strval', $ids), 'strlen'));
    if (!$ids) {
        return [];
    }
    $in = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $conDB->prepare("SELECT emp_id, name FROM employees WHERE emp_id IN ($in)");
    $stmt->bind_param(str_repeat('s', count($ids)), ...$ids);
    $stmt->execute();
    $res = $stmt->get_result();
    $out = [];
    while ($r = $res->fetch_assoc()) {
        $out[(string) $r['emp_id']] = getDisplayName($r['name'], 'en');
    }
    $stmt->close();
    return $out;
}

switch ($action) {
    case 'options':
        $docTypes = [];
        foreach ($types as $key => [$label, $labelAr, $scope]) {
            $docTypes[] = ['key' => $key, 'label' => $label, 'label_ar' => $labelAr, 'scope' => $scope];
        }
        // Active employees who can receive email: admin_login.email, employees.email or employees.c_email
        // (normal employees have no login email) - same order doc_expiry_email_for_emp() uses.
        $res = mysqli_query($conDB, "SELECT e.emp_id, e.name, d.dep_nme
            FROM employees e
            LEFT JOIN department d ON d.id = e.dept
            LEFT JOIN admin_login al ON al.emp_id = e.emp_id
            WHERE e.status = 1 AND (COALESCE(al.email, '') <> '' OR COALESCE(e.email, '') <> '' OR COALESCE(e.c_email, '') <> '')
            GROUP BY e.emp_id
            ORDER BY e.name");
        $people = [];
        while ($res && ($r = mysqli_fetch_assoc($res))) {
            $people[] = ['id' => (string) $r['emp_id'], 'name' => getDisplayName($r['name'], 'en'), 'dept' => (string) $r['dep_nme']];
        }
        der_reply(true, '', ['doc_types' => $docTypes, 'people' => $people]);

    case 'list':
        $rules = doc_expiry_rules_all($conDB);
        $allIds = [];
        foreach ($rules as $r) {
            $allIds = array_merge($allIds, $r['recipients']);
        }
        $names = der_names($conDB, array_unique($allIds));
        $out = [];
        foreach ($rules as $r) {
            $tracked = $r['days'] ? doc_expiry_rule_collect($conDB, $r['doc_type'], max($r['days'])) : [];
            $expiredCount = count(array_filter($tracked, fn($t) => $t['days_left'] < 0));
            $out[] = [
                'id' => (int) $r['id'],
                'doc_type' => $r['doc_type'],
                'doc_label' => $types[$r['doc_type']][0] ?? $r['doc_type'],
                'days' => $r['days'],
                'recipients' => array_map(fn($id) => ['id' => $id, 'name' => $names[$id] ?? ('#' . $id)], $r['recipients']),
                'notify_employee' => (int) $r['notify_employee'],
                'include_expired' => (int) $r['include_expired'],
                'status' => (int) $r['status'],
                'notes' => (string) $r['notes'],
                'within_window' => count($tracked) - $expiredCount,
                'expired' => $expiredCount,
            ];
        }
        der_reply(true, '', ['rules' => $out]);

    case 'save':
        $id = (int) ($_POST['id'] ?? 0);
        $docType = (string) ($_POST['doc_type'] ?? '');
        $days = doc_expiry_parse_days($_POST['days_before'] ?? '');
        $recipients = array_values(array_unique(array_filter(array_map('trim', (array) ($_POST['recipients'] ?? [])), 'strlen')));
        if (!isset($types[$docType])) {
            der_reply(false, 'Select a document type.');
        }
        if (!$days) {
            der_reply(false, 'Enter at least one "days before" value (0-365).');
        }
        if (!$recipients && empty($_POST['notify_employee'])) {
            der_reply(false, 'Select at least one user to notify.');
        }
        if ($recipients && count(der_names($conDB, $recipients)) !== count($recipients)) {
            der_reply(false, 'One or more selected users were not found.');
        }
        $daysStr = implode(',', $days);
        $recJson = json_encode($recipients);
        $notifyEmp = !empty($_POST['notify_employee']) && $types[$docType][2] === 'employee' ? 1 : 0;
        $incExpired = !empty($_POST['include_expired']) ? 1 : 0;
        $status = isset($_POST['status']) ? (int) !empty($_POST['status']) : 1;
        $notes = mb_substr(trim((string) ($_POST['notes'] ?? '')), 0, 255);
        if ($id > 0) {
            $stmt = $conDB->prepare("UPDATE doc_expiry_notify_rules SET doc_type = ?, days_before = ?, recipient_emp_ids = ?, notify_employee = ?, include_expired = ?, status = ?, notes = ? WHERE id = ?");
            $stmt->bind_param('sssiiisi', $docType, $daysStr, $recJson, $notifyEmp, $incExpired, $status, $notes, $id);
        } else {
            $by = (string) ($empid ?? '');
            $stmt = $conDB->prepare("INSERT INTO doc_expiry_notify_rules (doc_type, days_before, recipient_emp_ids, notify_employee, include_expired, status, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('sssiiiss', $docType, $daysStr, $recJson, $notifyEmp, $incExpired, $status, $notes, $by);
        }
        $ok = $stmt->execute();
        $stmt->close();
        der_reply($ok, $ok ? 'Notification rule saved.' : 'Could not save the rule.');

    case 'toggle':
        $id = (int) ($_POST['id'] ?? 0);
        $status = (int) !empty($_POST['status']);
        $stmt = $conDB->prepare("UPDATE doc_expiry_notify_rules SET status = ? WHERE id = ?");
        $stmt->bind_param('ii', $status, $id);
        $ok = $stmt->execute() && $stmt->affected_rows >= 0;
        $stmt->close();
        der_reply($ok, $status ? 'Rule enabled.' : 'Rule disabled.');

    case 'delete':
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $conDB->prepare("DELETE FROM doc_expiry_notify_rules WHERE id = ?");
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute() && $stmt->affected_rows > 0;
        $stmt->close();
        if ($ok) {
            $del = $conDB->prepare("DELETE FROM doc_expiry_rule_log WHERE rule_id = ?");
            $del->bind_param('i', $id);
            $del->execute();
            $del->close();
        }
        der_reply($ok, $ok ? 'Rule deleted.' : 'Rule not found.');

    case 'preview':
        $id = (int) ($_POST['id'] ?? 0);
        $rule = null;
        foreach (doc_expiry_rules_all($conDB) as $r) {
            if ((int) $r['id'] === $id) {
                $rule = $r;
            }
        }
        if (!$rule) {
            der_reply(false, 'Rule not found.');
        }
        [$upcoming, $expired] = doc_expiry_rule_due($conDB, $rule);
        $dueKeys = [];
        foreach (array_merge($upcoming, $expired) as $d) {
            $dueKeys[$d['ref_key']] = true;
        }
        $items = [];
        foreach (doc_expiry_rule_collect($conDB, $rule['doc_type'], $rule['days'] ? max($rule['days']) : 0) as $d) {
            if ($d['days_left'] < 0 && empty($rule['include_expired'])) {
                continue;
            }
            $items[] = [
                'name' => getDisplayName($d['name'], 'en'), 'emp_id' => $d['emp_id'], 'sub' => $d['dept'],
                'doc_label' => $d['doc_label'], 'doc_no' => $d['doc_no'], 'expiry' => $d['expiry'],
                'days_left' => $d['days_left'], 'due_today' => isset($dueKeys[$d['ref_key']]),
            ];
        }
        usort($items, fn($a, $b) => $a['days_left'] <=> $b['days_left']);
        der_reply(true, '', ['items' => $items]);

    case 'run':
        $id = (int) ($_POST['id'] ?? 0);
        $s = doc_expiry_rules_run($conDB, $id > 0 ? $id : null);
        if (!$s['due']) {
            der_reply(true, 'Nothing new to send. Every alert due has already been sent.', ['summary' => $s]);
        }
        if (!$s['emails_sent']) {
            der_reply(false, 'No email could be sent. Check the recipients\' emails and App Settings > Email.' . ($s['errors'] ? ' (' . $s['errors'][0] . ')' : ''), ['summary' => $s]);
        }
        der_reply(true, $s['emails_sent'] . ' email(s) sent for ' . $s['due'] . ' document(s).' . ($s['emails_failed'] ? ' ' . $s['emails_failed'] . ' failed.' : ''), ['summary' => $s]);
}

der_reply(false, 'Unknown action.', [], 400);
