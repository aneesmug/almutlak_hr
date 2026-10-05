<?php
// D365 widget in the employee header (includes/emp_top_info.php).
// 'status' is open to every logged-in user (status pill); register / sync / sync_payroll need
// system admin or the 'd365_sync_employee' special access.
// Actions (POST, CSRF = $_SESSION['d365_csrf']):
//   status   -> stored/fresh D365 status of the employee (+ company suggestion when not registered)
//   register -> create worker + employment in D365 (company chosen in the popup)
//   sync     -> push the app's person details to the existing D365 worker
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../includes/D365Workers.php';

header('Content-Type: application/json; charset=utf-8');
// D365 can be slow - lift the 25s app-wide limit from includes/db.php
@set_time_limit(120);

$canSync = user_has_special_access($conDB, $empid ?? '', 'd365_sync_employee', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
if (!$canSync && (string)($_POST['action'] ?? 'status') !== 'status') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Forbidden']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['d365_csrf']) || !hash_equals($_SESSION['d365_csrf'], (string)($_POST['csrf'] ?? ''))) {
    echo json_encode(['ok' => false, 'error' => 'Session expired - reload the page']);
    exit;
}

$targetEmp = (string)($_POST['emp_id'] ?? '');
if (!preg_match('/^[A-Za-z0-9\-]{1,20}$/', $targetEmp)) {
    echo json_encode(['ok' => false, 'error' => 'Invalid employee ID']);
    exit;
}

try {
    $client = new D365Client();
    $workers = new D365Workers($conDB, $client);
    $action = (string)($_POST['action'] ?? 'status');

    // Payrolls tab (view_employee.php): sync one paid month of this employee
    if ($action === 'sync_payroll') {
        require_once __DIR__ . '/../../includes/D365Payroll.php';
        $month = (string)($_POST['month'] ?? '');
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            echo json_encode(['ok' => false, 'error' => 'Invalid month']);
            exit;
        }
        @set_time_limit(180);
        $payroll = new D365Payroll($conDB, $client);
        $userId = (string)($empid ?? ($_SESSION['user_id'] ?? ''));
        $result = $payroll->syncEmployeeMonth($targetEmp, $month, date('Y-m-t', strtotime($month . '-01')), $userId);
        $result['environment'] = $client->getEnvironment();
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'register') {
        $result = $workers->register($targetEmp, (string)($_POST['company'] ?? ''));
    } elseif ($action === 'sync') {
        $result = $workers->syncWorker($targetEmp);
    } else {
        $result = ['ok' => true];
    }

    // Every answer carries the current status so the widget can redraw itself
    $status = $workers->getStatus($targetEmp, !empty($_POST['refresh']) ? 0 : 21600);
    $result['status'] = $status;
    $result['environment'] = $client->getEnvironment();
    $result['can_write'] = $client->canWrite();
    if ($canSync && ($status['status'] ?? '') !== 'registered') {
        $result['suggest'] = $workers->suggestCompanyFor($targetEmp);
    }
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
} catch (Throwable $ex) {
    echo json_encode(['ok' => false, 'error' => $ex->getMessage()]);
}
