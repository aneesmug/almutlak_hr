<?php
/**
 * Employee Transfer Request AJAX Handler
 * Creates a supervisor-to-supervisor employee transfer request (temporary or
 * permanent) and starts the generic chain-approval workflow, auto-routing the
 * first approval step to the employee's current direct supervisor.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../special_access_helper.php';

if (session_status() == PHP_SESSION_NONE) session_start();
$current_user_id = trim((string)($_SESSION['empid'] ?? ''));
$userwel = $_SESSION['userwel'] ?? 'System';

$ajaxType = $_POST['ajaxType'] ?? '';

if ($ajaxType === 'submitEmployeeTransferRequest') {

    try {
        if ($current_user_id === '' || $current_user_id === '0') {
            throw new Exception(__('session_expired', 'Your session has expired. Please log in again.'));
        }

        // --- Re-validate server-side that the requester is allowed to create this request ---
        // (the "Request Employee Transfer" button is only shown client-side to supervisors,
        // system admins, and Special-Access grantees, but that check must never be trusted alone).
        $sup_check_stmt = mysqli_prepare($conDB, "SELECT COUNT(*) AS cnt FROM `employees` WHERE `supervisor_id` = ? AND `status` = 1");
        mysqli_stmt_bind_param($sup_check_stmt, "s", $current_user_id);
        mysqli_stmt_execute($sup_check_stmt);
        $sup_check_res = mysqli_stmt_get_result($sup_check_stmt);
        $is_supervisor = $sup_check_res && ($r = mysqli_fetch_assoc($sup_check_res)) && (int)$r['cnt'] > 0;
        if ($sup_check_res) mysqli_free_result($sup_check_res);
        mysqli_stmt_close($sup_check_stmt);

        $can_create_transfer = (
            $is_supervisor
            || ($is_system_admin ?? false)
            || user_has_special_access($conDB, $current_user_id, 'request_employee_transfer', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)
        );

        if (!$can_create_transfer) {
            throw new Exception(__('only_supervisors_can_request_transfer', 'Only employees who currently supervise at least one active employee can request an employee transfer.'));
        }

        // --- Read & validate input ---
        $target_emp_id = trim((string)($_POST['target_emp_id'] ?? ''));
        $to_supervisor_id = trim((string)($_POST['to_supervisor_id'] ?? ''));
        $transfer_type = ($_POST['transfer_type'] ?? '') === 'permanent' ? 'permanent' : (($_POST['transfer_type'] ?? '') === 'temporary' ? 'temporary' : '');
        $start_date = trim((string)($_POST['start_date'] ?? ''));
        $end_date = trim((string)($_POST['end_date'] ?? ''));
        $request_notes = trim((string)($_POST['request_notes'] ?? ''));

        if ($target_emp_id === '') {
            throw new Exception(__('please_select_employee', 'Please select an employee to transfer.'));
        }
        if ($to_supervisor_id === '') {
            throw new Exception(__('please_select_new_supervisor', 'Please select the new Direct Supervisor.'));
        }
        if ($transfer_type === '') {
            throw new Exception(__('please_select_transfer_type', 'Please select Temporary or Permanent.'));
        }
        if ($start_date === '' || !strtotime($start_date)) {
            throw new Exception(__('please_select_valid_start_date', 'Please select a valid start date.'));
        }
        if (strtotime($start_date) < strtotime(date('Y-m-d'))) {
            throw new Exception(__('start_date_cannot_be_in_the_past', 'The start date cannot be in the past.'));
        }
        if ($transfer_type === 'temporary') {
            if ($end_date === '' || !strtotime($end_date)) {
                throw new Exception(__('please_select_valid_end_date', 'Please select a valid end date for a temporary transfer.'));
            }
            if (strtotime($end_date) < strtotime($start_date)) {
                throw new Exception(__('end_date_before_start_date', 'The end date cannot be before the start date.'));
            }
        } else {
            $end_date = null;
        }

        if ($to_supervisor_id === $target_emp_id) {
            throw new Exception(__('cannot_transfer_self', 'An employee cannot be their own supervisor.'));
        }

        // --- Look up target employee: must be active, and find their current supervisor/dept ---
        $emp_stmt = mysqli_prepare($conDB, "SELECT `emp_id`, `name`, `supervisor_id`, `dept`, `status` FROM `employees` WHERE `emp_id` = ? LIMIT 1");
        mysqli_stmt_bind_param($emp_stmt, "s", $target_emp_id);
        mysqli_stmt_execute($emp_stmt);
        $emp_res = mysqli_stmt_get_result($emp_stmt);
        $target_emp = $emp_res ? mysqli_fetch_assoc($emp_res) : null;
        if ($emp_res) mysqli_free_result($emp_res);
        mysqli_stmt_close($emp_stmt);

        if (!$target_emp || (int)$target_emp['status'] !== 1) {
            throw new Exception(__('employee_not_found_or_inactive', 'The selected employee was not found or is not active.'));
        }

        $from_supervisor_id = trim((string)($target_emp['supervisor_id'] ?? ''));
        if ($from_supervisor_id === '' || $from_supervisor_id === '0') {
            throw new Exception(__('employee_has_no_supervisor', 'This employee has no current supervisor assigned, so a transfer request cannot be routed for approval.'));
        }
        if ($from_supervisor_id === $to_supervisor_id) {
            throw new Exception(__('employee_already_reports_to_selected_supervisor', 'This employee already reports to the selected new supervisor.'));
        }

        // --- Validate the new supervisor: must be an active @almutlak.com admin_login account ---
        $sup_valid_stmt = mysqli_prepare($conDB, "SELECT e.name FROM `admin_login` al
            INNER JOIN `employees` e ON e.emp_id = al.emp_id
            WHERE al.emp_id = ? AND e.status = 1
              AND al.email IS NOT NULL AND TRIM(al.email) <> ''
              AND LOWER(TRIM(al.email)) LIKE '%@almutlak.com'
            LIMIT 1");
        mysqli_stmt_bind_param($sup_valid_stmt, "s", $to_supervisor_id);
        mysqli_stmt_execute($sup_valid_stmt);
        $sup_valid_res = mysqli_stmt_get_result($sup_valid_stmt);
        $to_supervisor_row = $sup_valid_res ? mysqli_fetch_assoc($sup_valid_res) : null;
        if ($sup_valid_res) mysqli_free_result($sup_valid_res);
        mysqli_stmt_close($sup_valid_stmt);

        if (!$to_supervisor_row) {
            throw new Exception(__('invalid_new_supervisor', 'The selected new supervisor must be an active account with a valid @almutlak.com email.'));
        }

        // --- Guard against a duplicate in-flight request for the same employee ---
        $dup_stmt = mysqli_prepare($conDB, "SELECT `request_inv_no` FROM `emp_transfers` WHERE `target_emp_id` = ? AND `current_status` IN ('pending_approval','approved') LIMIT 1");
        mysqli_stmt_bind_param($dup_stmt, "s", $target_emp_id);
        mysqli_stmt_execute($dup_stmt);
        $dup_res = mysqli_stmt_get_result($dup_stmt);
        $dup_row = $dup_res ? mysqli_fetch_assoc($dup_res) : null;
        if ($dup_res) mysqli_free_result($dup_res);
        mysqli_stmt_close($dup_stmt);

        if ($dup_row) {
            throw new Exception(__('transfer_already_in_progress', 'There is already an active transfer request for this employee') . ' (' . $dup_row['request_inv_no'] . ').');
        }

        // --- Resolve request type ---
        $type_res = mysqli_query($conDB, "SELECT `id` FROM `approval_request_types` WHERE `type_name` = 'employee_transfer_request' LIMIT 1");
        if (!$type_res || mysqli_num_rows($type_res) === 0) {
            throw new Exception('Configuration error: employee_transfer_request type is not registered.');
        }
        $request_type_id = (int)mysqli_fetch_assoc($type_res)['id'];
        if ($type_res) mysqli_free_result($type_res);

        // --- Build the approver chain from App Settings -> Approval (approval_chain_employee_transfer_request) ---
        // Mirrors ajaxBusinessTrip.php's $resolveApprover pattern. The emp_context is built
        // from the TARGET employee (not the requester), so a 'direct_supervisor' step resolves
        // to $from_supervisor_id - the natural first approver for a transfer request.
        $configured_chain = [];
        $settingQuery = mysqli_query($conDB, "SELECT setting_value FROM app_settings WHERE setting_name = 'approval_chain_employee_transfer_request' LIMIT 1");
        if ($settingQuery && mysqli_num_rows($settingQuery) > 0) {
            $cfg_row = mysqli_fetch_assoc($settingQuery);
            $decoded = json_decode($cfg_row['setting_value'], true);
            if (is_array($decoded)) {
                $configured_chain = $decoded;
            }
        }
        if ($settingQuery) mysqli_free_result($settingQuery);

        $resolveTransferApprover = function ($role, $emp_context) use ($conDB) {
            $role = trim((string)$role);
            if ($role === '') return null;

            if ($role === 'direct_supervisor') {
                return !empty($emp_context['supervisor_id']) ? (string)$emp_context['supervisor_id'] : null;
            }

            if ($role === 'dept_manager' && function_exists('getDeptManager')) {
                $dept_mgr = getDeptManager($conDB, $emp_context['dept']);
                return ($dept_mgr && !empty($dept_mgr['emp_id'])) ? (string)$dept_mgr['emp_id'] : null;
            }

            // Default: first active employee with matching user_type
            $stmt = mysqli_prepare($conDB, "SELECT e.emp_id FROM employees e
                                           JOIN admin_login al ON e.emp_id = al.emp_id
                                           WHERE al.user_type = ? AND e.status = 1
                                           ORDER BY e.emp_id ASC LIMIT 1");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "s", $role);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                if ($res && ($row = mysqli_fetch_assoc($res))) {
                    $foundId = (string)$row['emp_id'];
                    mysqli_free_result($res);
                    mysqli_stmt_close($stmt);
                    return $foundId !== '' ? $foundId : null;
                }
                if ($res) mysqli_free_result($res);
                mysqli_stmt_close($stmt);
            }
            return null;
        };

        $target_emp_ctx = ['supervisor_id' => $from_supervisor_id, 'dept' => $target_emp['dept'] ?? null];

        // Level 1 is always the employee's current direct supervisor - they must sign off on
        // giving up their report, regardless of how the chain is configured. Every step after
        // that comes from App Settings -> Approval (approval_chain_employee_transfer_request).
        $approver_chain = [$from_supervisor_id];
        if (!empty($configured_chain)) {
            foreach ($configured_chain as $step) {
                $role = $step['user_type'] ?? '';
                $approverId = $resolveTransferApprover($role, $target_emp_ctx);
                if ($approverId && !in_array($approverId, $approver_chain, true)) {
                    $approver_chain[] = $approverId;
                }
            }
        }

        // --- Generate a unique request ID (ET-YYYYMMDDHHMMSS-EMPID-xxxx) ---
        $safe_emp_id = preg_replace('/[^A-Za-z0-9]/', '', $target_emp_id);
        $safe_emp_id = ($safe_emp_id !== '') ? $safe_emp_id : 'EMP';
        $request_inv_no = '';
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $candidate = sprintf('ET-%s-%s-%s', date('YmdHis'), $safe_emp_id, substr(bin2hex(random_bytes(4)), 0, 4));
            $exists_stmt = mysqli_prepare($conDB, "SELECT id FROM emp_transfers WHERE request_inv_no = ? LIMIT 1");
            mysqli_stmt_bind_param($exists_stmt, "s", $candidate);
            mysqli_stmt_execute($exists_stmt);
            $exists_res = mysqli_stmt_get_result($exists_stmt);
            $exists = $exists_res && mysqli_num_rows($exists_res) > 0;
            if ($exists_res) mysqli_free_result($exists_res);
            mysqli_stmt_close($exists_stmt);
            if (!$exists) {
                $request_inv_no = $candidate;
                break;
            }
        }
        if ($request_inv_no === '') {
            throw new Exception('Failed to generate a unique request ID. Please try again.');
        }

        mysqli_begin_transaction($conDB);

        try {
            $insert_sql = "INSERT INTO `emp_transfers`
                (`request_inv_no`, `emp_id`, `target_emp_id`, `from_supervisor_id`, `to_supervisor_id`, `transfer_type`,
                 `start_date`, `end_date`, `current_status`, `current_approval_level`, `request_notes`, `created_by`, `created_at`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending_approval', 1, ?, ?, NOW())";
            $stmt = mysqli_prepare($conDB, $insert_sql);
            if (!$stmt) throw new Exception("Prepare failed: " . mysqli_error($conDB));
            mysqli_stmt_bind_param(
                $stmt,
                "sssssssssi",
                $request_inv_no, $current_user_id, $target_emp_id, $from_supervisor_id, $to_supervisor_id, $transfer_type,
                $start_date, $end_date, $request_notes, $current_user_id
            );
            if (!mysqli_stmt_execute($stmt)) throw new Exception("Execute failed: " . mysqli_stmt_error($stmt));
            $transfer_id = mysqli_insert_id($conDB);
            mysqli_stmt_close($stmt);

            foreach ($approver_chain as $index => $approver_id) {
                $level = $index + 1;
                $status = ($level === 1) ? 'pending' : 'awaiting';
                $approver_stmt = mysqli_prepare($conDB, "INSERT INTO `request_approvers`
                    (`request_inv_no`, `request_type_id`, `approver_id`, `approval_level`, `status`)
                    VALUES (?, ?, ?, ?, ?)");
                if (!$approver_stmt) throw new Exception("Approver prepare failed: " . mysqli_error($conDB));
                mysqli_stmt_bind_param($approver_stmt, "siiss", $request_inv_no, $request_type_id, $approver_id, $level, $status);
                if (!mysqli_stmt_execute($approver_stmt)) throw new Exception("Approver execute failed: " . mysqli_stmt_error($approver_stmt));
                mysqli_stmt_close($approver_stmt);
            }

            if (class_exists('ActivityLogger')) {
                ActivityLogger::logSubmit(
                    'Employee Transfer',
                    'ajaxEmployeeTransfer.php',
                    $transfer_id,
                    "Submitted employee transfer request: {$request_inv_no}, Employee: {$target_emp_id}, From: {$from_supervisor_id}, To: {$to_supervisor_id}, Requested by: {$current_user_id}, Type: {$transfer_type}",
                    'emp_transfers'
                );
            }

            // Notify + email the first approver (employee's current supervisor)
            if (function_exists('create_browser_notification')) {
                $notification_title = "New Employee Transfer Request for Approval";
                $notification_message = "Request {$request_inv_no}: {$target_emp['name']} - transfer requested by {$userwel}.";
                $notification_url = "all_applied_employee_transfers.php?status=my_pending";
                create_browser_notification($conDB, $from_supervisor_id, $notification_title, $notification_message, $notification_url);
            }

            if (function_exists('send_approval_email')) {
                $first_approver_details = function_exists('getEmployeeDetailsForApproval')
                    ? getEmployeeDetailsForApproval($conDB, $from_supervisor_id)
                    : null;
                if ($first_approver_details && !empty($first_approver_details['email'])) {
                    $template_data = function_exists('get_request_details_for_email')
                        ? get_request_details_for_email($conDB, $request_inv_no, 'employee_transfer_request', $first_approver_details['name'])
                        : false;
                    if (!$template_data) {
                        $template_data = [
                            'APPROVER_NAME' => $first_approver_details['name'],
                            'REQUEST_ID' => $request_inv_no,
                            'EMAIL_MESSAGE' => 'A new employee transfer request requires your approval.'
                        ];
                    }
                    send_approval_email(
                        $conDB,
                        $first_approver_details['email'],
                        $first_approver_details['name'],
                        "Employee Transfer Request Pending Approval - {$request_inv_no}",
                        'employee_transfer_request',
                        $template_data
                    );
                }
            }

            mysqli_commit($conDB);

            echo json_encode([
                'status' => 'success',
                'title' => __('request_submitted', 'Request Submitted!'),
                'message' => __('employee_transfer_request_submitted', 'Your employee transfer request has been submitted successfully. Request ID:') . ' ' . $request_inv_no,
                'type' => 'success',
                'request_inv_no' => $request_inv_no
            ]);
        } catch (Exception $e) {
            mysqli_rollback($conDB);
            throw $e;
        }
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'title' => __('submission_failed', 'Submission Failed'),
            'message' => $e->getMessage(),
            'type' => 'error'
        ]);
    }
    exit;
}

if ($ajaxType === 'approveEmployeeTransfer' || $ajaxType === 'rejectEmployeeTransfer') {
    $is_approve = ($ajaxType === 'approveEmployeeTransfer');

    try {
        $type_res = mysqli_query($conDB, "SELECT `id` FROM `approval_request_types` WHERE `type_name` = 'employee_transfer_request' LIMIT 1");
        $request_type_id = ($type_res && mysqli_num_rows($type_res) > 0) ? (int)mysqli_fetch_assoc($type_res)['id'] : 0;
        if ($type_res) mysqli_free_result($type_res);
        if ($request_type_id <= 0) {
            throw new Exception('Employee transfer request type is not configured.');
        }

        $request_inv_no = trim((string)($_POST['request_inv_no'] ?? ''));
        $comment = trim((string)($_POST['comment'] ?? ($is_approve ? 'Approved' : '')));

        if ($request_inv_no === '') {
            throw new Exception(__('invalid_request', 'Invalid request.'));
        }
        if (!$is_approve && $comment === '') {
            throw new Exception(__('rejection_reason_required', 'Please provide a rejection reason.'));
        }

        $status_stmt = mysqli_prepare($conDB, "SELECT `current_status` FROM `emp_transfers` WHERE `request_inv_no` = ? LIMIT 1");
        mysqli_stmt_bind_param($status_stmt, "s", $request_inv_no);
        mysqli_stmt_execute($status_stmt);
        $status_res = mysqli_stmt_get_result($status_stmt);
        $transfer_row = $status_res ? mysqli_fetch_assoc($status_res) : null;
        if ($status_res) mysqli_free_result($status_res);
        mysqli_stmt_close($status_stmt);

        if (!$transfer_row) {
            throw new Exception(__('request_not_found', 'Employee transfer request not found.'));
        }
        if (in_array($transfer_row['current_status'], ['approved', 'rejected', 'completed', 'cancelled'], true)) {
            throw new Exception(__('request_already_finalized', 'This request is already finalized and cannot be actioned again.'));
        }

        $pending_stmt = mysqli_prepare($conDB, "SELECT id FROM request_approvers WHERE request_inv_no = ? AND request_type_id = ? AND approver_id = ? AND status = 'pending' LIMIT 1");
        mysqli_stmt_bind_param($pending_stmt, "sis", $request_inv_no, $request_type_id, $current_user_id);
        mysqli_stmt_execute($pending_stmt);
        $pending_res = mysqli_stmt_get_result($pending_stmt);
        $has_pending_access = $pending_res && mysqli_num_rows($pending_res) > 0;
        if ($pending_res) mysqli_free_result($pending_res);
        mysqli_stmt_close($pending_stmt);

        if (!$has_pending_access) {
            throw new Exception(__('not_pending_your_approval', 'This request is not pending your approval.'));
        }

        $approval_result = handle_approval_action(
            $conDB,
            $request_inv_no,
            'employee_transfer_request',
            (int)$current_user_id,
            $is_approve ? 'approve' : 'reject',
            $comment
        );

        if (($approval_result['status'] ?? 'error') === 'error') {
            throw new Exception((string)($approval_result['message'] ?? 'Action failed.'));
        }

        if (class_exists('ActivityLogger')) {
            ActivityLogger::logApproval(
                'Employee Transfer',
                'ajaxEmployeeTransfer.php',
                $request_inv_no,
                $is_approve ? 'approved' : 'rejected',
                ($is_approve ? 'Approved' : 'Rejected') . " employee transfer request: {$request_inv_no}",
                'emp_transfers'
            );
        }

        echo json_encode([
            'status' => 'success',
            'title' => $is_approve ? __('approved', 'Approved') : __('rejected', 'Rejected'),
            'message' => $is_approve
                ? __('employee_transfer_approved', 'Employee transfer request approved successfully.')
                : __('employee_transfer_rejected', 'Employee transfer request rejected.'),
            'type' => 'success',
            'request_inv_no' => $request_inv_no,
            // Present only when this action was the FINAL approval, so the employee's
            // supervisor was actually just moved - lets the UI show old vs new supervisor.
            'employee_transfer_applied' => $approval_result['employee_transfer_applied'] ?? null
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'title' => $is_approve ? __('approval_failed', 'Approval Failed') : __('rejection_failed', 'Rejection Failed'),
            'message' => $e->getMessage(),
            'type' => 'error'
        ]);
    }
    exit;
}

if ($ajaxType === 'getEmployeeTransferDetails') {
    try {
        $request_inv_no = trim((string)($_POST['request_inv_no'] ?? ''));
        if ($request_inv_no === '') {
            throw new Exception(__('invalid_request', 'Invalid request.'));
        }

        $type_res = mysqli_query($conDB, "SELECT `id` FROM `approval_request_types` WHERE `type_name` = 'employee_transfer_request' LIMIT 1");
        $request_type_id = ($type_res && mysqli_num_rows($type_res) > 0) ? (int)mysqli_fetch_assoc($type_res)['id'] : 0;
        if ($type_res) mysqli_free_result($type_res);

        $stmt = mysqli_prepare($conDB, "SELECT
                et.*,
                tgt.name AS target_name,
                d.dep_nme AS deptnme,
                c.comp_name AS compnme,
                j.job AS jobname,
                fs.name AS from_supervisor_name,
                ts.name AS to_supervisor_name,
                req.name AS requester_name
            FROM `emp_transfers` et
            JOIN `employees` tgt ON et.target_emp_id = tgt.emp_id
            LEFT JOIN `employees` req ON et.emp_id = req.emp_id
            LEFT JOIN `employees` fs ON et.from_supervisor_id = fs.emp_id
            LEFT JOIN `employees` ts ON et.to_supervisor_id = ts.emp_id
            LEFT JOIN `department` d ON tgt.dept = d.id
            LEFT JOIN `companies` c ON tgt.comp_no = c.comp_id
            LEFT JOIN `ac_jobs` j ON tgt.actual_job = j.id
            WHERE et.request_inv_no = ?
            LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $request_inv_no);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $transfer = $res ? mysqli_fetch_assoc($res) : null;
        if ($res) mysqli_free_result($res);
        mysqli_stmt_close($stmt);

        if (!$transfer) {
            throw new Exception(__('request_not_found', 'Employee transfer request not found.'));
        }

        // --- Permission check: admins/HR see everything, otherwise only the requester or
        // someone who appears (or appeared) in this request's approval chain. ---
        $can_see_all = ($is_system_admin ?? false) || ($isHR ?? false);
        $is_requester = ((string)$transfer['emp_id'] === (string)$current_user_id);
        $is_in_chain = false;
        if (!$can_see_all && !$is_requester && $request_type_id > 0) {
            $chain_check_stmt = mysqli_prepare($conDB, "SELECT id FROM `request_approvers` WHERE request_inv_no = ? AND request_type_id = ? AND approver_id = ? LIMIT 1");
            mysqli_stmt_bind_param($chain_check_stmt, "sis", $request_inv_no, $request_type_id, $current_user_id);
            mysqli_stmt_execute($chain_check_stmt);
            $chain_check_res = mysqli_stmt_get_result($chain_check_stmt);
            $is_in_chain = $chain_check_res && mysqli_num_rows($chain_check_res) > 0;
            if ($chain_check_res) mysqli_free_result($chain_check_res);
            mysqli_stmt_close($chain_check_stmt);
        }
        if (!$can_see_all && !$is_requester && !$is_in_chain) {
            throw new Exception(__('access_denied', 'You do not have permission to view this request.'));
        }

        // --- Approval chain (for display) ---
        $approval_chain = [];
        if ($request_type_id > 0) {
            $chain_stmt = mysqli_prepare($conDB, "SELECT ra.approval_level, ra.status, ra.note, e.name AS approver_name
                FROM `request_approvers` ra
                LEFT JOIN `employees` e ON e.emp_id = ra.approver_id
                WHERE ra.request_inv_no = ? AND ra.request_type_id = ?
                ORDER BY ra.approval_level ASC");
            mysqli_stmt_bind_param($chain_stmt, "si", $request_inv_no, $request_type_id);
            mysqli_stmt_execute($chain_stmt);
            $chain_res = mysqli_stmt_get_result($chain_stmt);
            while ($row = mysqli_fetch_assoc($chain_res)) {
                $approval_chain[] = $row;
            }
            if ($chain_res) mysqli_free_result($chain_res);
            mysqli_stmt_close($chain_stmt);
        }

        echo json_encode(['status' => 'success', 'data' => ['transfer' => $transfer, 'approval_chain' => $approval_chain]]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
exit;
