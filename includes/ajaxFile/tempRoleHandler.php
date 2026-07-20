<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../session_check.php';

// Only a current hr_senior_bp or hr_payroll (or system admin) may grant/revoke temporary role
// coverage. $actual_user_type is the caller's own DB role, not any temp role they may currently
// be covering under, so a covered employee can't use their borrowed permissions to grant further coverage.
$can_manage_temp_roles = (
    $is_system_admin
    || in_array($actual_user_type ?? '', ['hr_senior_bp', 'hr_payroll'], true)
);
if (!$can_manage_temp_roles) {
    send_json_response('Access Denied', 'Only HR Senior BP or HR Payroll can transfer a temporary role.', 'error', 403);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response('Error', 'Invalid request method.', 'error', 405);
    exit;
}

$ajaxType = $_POST['ajaxType'] ?? '';

switch ($ajaxType) {
    case 'grantTempRole':
        $vacation_id = (int)($_POST['vacation_id'] ?? 0);
        if ($vacation_id <= 0) {
            send_json_response('Error', 'Vacation ID is required.', 'error', 400);
            exit;
        }
        $result = grantTemporaryRoleAssignment($conDB, $vacation_id, $empid);
        if (!$result['success']) {
            send_json_response('Error', $result['message'] ?? 'Failed to transfer role.', 'error', 400);
            exit;
        }
        $label = function_exists('getRoleLabel') ? getRoleLabel($result['granted_role']) : $result['granted_role'];
        send_json_response(
            'Success',
            'Temporary role transferred: ' . $label . ' (valid ' . $result['valid_from'] . ' to ' . $result['valid_to'] . ').',
            'success'
        );
        break;

    case 'revokeTempRole':
        $vacation_id = (int)($_POST['vacation_id'] ?? 0);
        if ($vacation_id <= 0) {
            send_json_response('Error', 'Vacation ID is required.', 'error', 400);
            exit;
        }
        $result = closeTemporaryRoleAssignment($conDB, $vacation_id, $empid, 'revoked');
        if (!$result['success']) {
            send_json_response('Error', $result['message'] ?? 'Failed to revoke temporary role.', 'error', 400);
            exit;
        }
        send_json_response('Success', 'Temporary role access revoked.', 'success');
        break;

    default:
        send_json_response('Error', 'Invalid action specified.', 'error', 400);
        break;
}
