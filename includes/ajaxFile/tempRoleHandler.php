<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../session_check.php';
require_once __DIR__ . '/../special_access_helper.php';

// A current hr_senior_bp/hr_payroll, a system admin, or anyone granted the
// 'manage_temp_role_transfer' Special Access key may grant/revoke temporary role
// coverage. $actual_user_type is the caller's own DB role, not any temp role they may
// currently be covering under, so a covered employee can't use their borrowed
// permissions to grant further coverage.
$can_manage_temp_roles = (
    $is_system_admin
    || in_array($actual_user_type ?? '', ['hr_senior_bp', 'hr_payroll'], true)
    || user_has_special_access($conDB, $empid ?? '', 'manage_temp_role_transfer', $user_role ?? '', $actual_user_type ?? '', $is_system_admin ?? false)
);
if (!$can_manage_temp_roles) {
    send_json_response('Access Denied', 'Only HR Senior BP, HR Payroll, or a user granted Temporary Role Transfer access can transfer a temporary role.', 'error', 403);
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

    case 'getEmployeeCurrentRole':
        $emp_id = trim((string)($_POST['emp_id'] ?? ''));
        if ($emp_id === '') {
            send_json_response('Error', 'Employee ID is required.', 'error', 400);
            exit;
        }
        $stmtRole = mysqli_prepare($conDB, "SELECT user_type FROM admin_login WHERE emp_id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmtRole, "s", $emp_id);
        mysqli_stmt_execute($stmtRole);
        $roleRes = mysqli_stmt_get_result($stmtRole);
        $roleRow = $roleRes ? mysqli_fetch_assoc($roleRes) : null;
        if ($roleRes) mysqli_free_result($roleRes);
        mysqli_stmt_close($stmtRole);

        $role = trim((string)($roleRow['user_type'] ?? ''));
        $label = ($role !== '' && function_exists('getRoleLabel')) ? getRoleLabel($role) : $role;
        echo json_encode(['status' => 'success', 'role' => $role, 'role_label' => $label ?: 'No Role'], JSON_UNESCAPED_UNICODE);
        exit;

    case 'listTempRoleAssignments':
        $rows = getManualTempRoleAssignments($conDB);
        echo json_encode(['status' => 'success', 'results' => $rows]);
        exit;

    case 'grantManualTempRole':
        $employee_emp_id = trim((string)($_POST['employee_emp_id'] ?? ''));
        $replacement_emp_id = trim((string)($_POST['replacement_emp_id'] ?? ''));
        $valid_from = trim((string)($_POST['valid_from'] ?? ''));
        $valid_to = trim((string)($_POST['valid_to'] ?? ''));

        $result = grantManualTemporaryRoleAssignment($conDB, $employee_emp_id, $replacement_emp_id, $valid_from, $valid_to, $empid);
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

    case 'revokeManualTempRole':
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            send_json_response('Error', 'Assignment ID is required.', 'error', 400);
            exit;
        }
        $result = closeTemporaryRoleAssignmentById($conDB, $id, $empid, 'revoked');
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
