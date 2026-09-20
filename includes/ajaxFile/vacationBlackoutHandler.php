<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../session_check.php';
require_once __DIR__ . '/../special_access_helper.php';

// System admins or anyone granted the 'manage_vacation_blackout_dates' Special Access key.
$can_manage_blackouts = (
    $is_system_admin
    || user_has_special_access($conDB, $empid ?? '', 'manage_vacation_blackout_dates', $user_role ?? '', $actual_user_type ?? ($user_type ?? ''), $is_system_admin ?? false)
);
if (!$can_manage_blackouts) {
    send_json_response('Access Denied', 'You do not have access to manage vacation blackout dates.', 'error', 403);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response('Error', 'Invalid request method.', 'error', 405);
    exit;
}

$ajaxType = $_POST['ajaxType'] ?? '';

switch ($ajaxType) {
    case 'listVacationBlackouts':
        echo json_encode(['status' => 'success', 'results' => getVacationBlackoutDates($conDB, true)]);
        exit;

    case 'addVacationBlackout':
        $result = addVacationBlackoutDate(
            $conDB,
            trim((string)($_POST['start_date'] ?? '')),
            trim((string)($_POST['end_date'] ?? '')),
            (string)($_POST['reason'] ?? ''),
            $empid
        );
        if (!$result['success']) {
            send_json_response('Error', $result['message'] ?? 'Failed to add blackout dates.', 'error', 400);
            exit;
        }
        send_json_response(
            'Success',
            'Vacation requests are now blocked from ' . $result['start_date'] . ' to ' . $result['end_date'] . '.',
            'success'
        );
        break;

    case 'removeVacationBlackout':
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            send_json_response('Error', 'Blackout ID is required.', 'error', 400);
            exit;
        }
        $result = removeVacationBlackoutDate($conDB, $id, $empid);
        if (!$result['success']) {
            send_json_response('Error', $result['message'] ?? 'Failed to remove blackout.', 'error', 400);
            exit;
        }
        send_json_response('Success', 'Blackout removed. Vacation requests are allowed again for those dates.', 'success');
        break;

    default:
        send_json_response('Error', 'Invalid action specified.', 'error', 400);
        break;
}
