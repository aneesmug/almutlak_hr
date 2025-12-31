<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session_check.php';
include("./../../includes/helper_functions.php"); // --- Helper Function ---
include("./../../includes/validate_supervisor.php"); // --- Supervisor Validation ---

// Get the User's ID and Name from session if they exist
// These are used by the handle_approval_action function
if (session_status() == PHP_SESSION_NONE) session_start(); // Ensure session is started
$current_user_id = $_SESSION['empid'] ?? 0;
$userwel = $_SESSION['userwel'] ?? 'System';

/**
 * Calculate current vacation balance for an employee
 * Uses VacationCalculator to get live balance until today
 * 
 * @param mysqli $conDB Database connection
 * @param string $emp_id Employee ID
 * @return float|null Current available balance or null on error
 */
function get_current_vacation_balance($conDB, $emp_id)
{
    $finalvacd = 0;
    $dynamicBalance = null;

    if (empty($emp_id)) {
        return null;
    }

    // Get employee's available_balance from emp_vacation_balance as fallback
    $stmt = mysqli_query($conDB, "SELECT `available_balance` FROM `emp_vacation_balance` WHERE `emp_id` = '" . mysqli_real_escape_string($conDB, $emp_id) . "' ORDER BY `last_updated` DESC LIMIT 1");
    if ($stmt && mysqli_num_rows($stmt) > 0) {
        $row = mysqli_fetch_assoc($stmt);
        $finalvacd = (float)$row['available_balance'];
        mysqli_free_result($stmt);
    }

    // Attempt live calculation using VacationCalculator
    $calcFile = __DIR__ . '/../../includes/vacation_calculator.php';
    if (file_exists($calcFile)) {
        require_once $calcFile;
        if (class_exists('VacationCalculator')) {
            try {
                $vc = new VacationCalculator($conDB);
                $live = $vc->getCalculatedBalance($emp_id);
                if ($live && isset($live['available_balance'])) {
                    $dynamicBalance = (float)$live['available_balance'];
                }
            } catch (Throwable $e) {
            }
        }
    }

    // Return calculated balance if available, otherwise return static balance
    return ($dynamicBalance !== null ? $dynamicBalance : $finalvacd);
}

$ajaxType = $_POST['ajaxType'] ?? null; // Use null coalescing

if ($ajaxType == 'emp_search') {
    // Add company filter based on user's access
    $company_filter = getCompanyFilterSQL('comp_no', true);
    
    $stmt = mysqli_query($conDB, "SELECT * FROM `employees` WHERE `status`=1 ".$company_filter." ORDER BY `name` REGEXP '^[^A-Za-z]' ASC, `name` ");
    $name = [];
    while ($row = mysqli_fetch_assoc($stmt)) {
        $name[] = $row;
    }
    mysqli_free_result($stmt); // <-- FIX
    $data = [
        'data'      => $name,
        'status'    => 200
    ];
    echo json_encode($data);
} elseif ($ajaxType == 'emp_data') {
    // Add company filter based on user's access
    $company_filter = getCompanyFilterSQL('e.comp_no', true);
    
    $stmt = mysqli_query($conDB, "SELECT 
    `e`.*,
    `d`.`dep_nme` AS `deptnme`
    FROM `employees` `e`
    LEFT JOIN `department` `d` ON `d`.`id` = `e`.`dept` 
    WHERE `e`.`status`=1 AND `e`.`emp_id`=" . (int)$_POST['empid'] . " ".$company_filter); // Cast to int
    $name = [];
    while ($row = mysqli_fetch_assoc($stmt)) {
        $name[] = $row;
    }
    mysqli_free_result($stmt); // <-- FIX
    $data = [
        'data'      => $name,
        'status'    => 200
    ];
    echo json_encode($data);
} elseif ($ajaxType == 'emp_department') {
    $stmt = mysqli_query($conDB, "SELECT 
    `e`.*,
    `d`.`dep_nme` AS `deptnme`
    FROM `employees` `e`
    LEFT JOIN `department` `d` ON `d`.`id` = `e`.`dept` 
    WHERE `e`.`status`=1 AND `e`.`dept`=" . (int)$_POST['dept'] . " "); // Cast to int
    $name = [];
    while ($row = mysqli_fetch_assoc($stmt)) {
        $name[] = $row;
    }
    mysqli_free_result($stmt); // <-- FIX
    $data = [
        'data'      => $name,
        'status'    => 200
    ];
    echo json_encode($data);
}

// ================================================================
// --- [NEW] QUICK ELIGIBILITY CHECK BEFORE OPENING APPLY MODAL ---
// ================================================================
elseif ($ajaxType == 'canApplyVacation') {
    try {
        // Treat emp_id as string to match DB schema (employees.emp_id is VARCHAR)
        $emp_id = trim((string)($_POST['emp_id'] ?? ''));
        $is_emergency = isset($_POST['is_emergency']) && $_POST['is_emergency'] == '1';
        
        if ($emp_id === '') {
            echo json_encode(['ok' => false, 'message' => 'Invalid employee.']);
            exit;
        }

        // Check for any pending vacation request for this employee
        $pending_inv = null;
        $active_return_date = null;
        $sql = "SELECT `request_inv_no`, `return_date` FROM `emp_vacation` WHERE `emp_id` = ? AND `current_status` = 'pending_approval' ORDER BY `id` DESC LIMIT 1";
        $stmt = mysqli_prepare($conDB, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $emp_id);
            if (mysqli_stmt_execute($stmt)) {
                $res = mysqli_stmt_get_result($stmt);
                if ($row = mysqli_fetch_assoc($res)) {
                    $pending_inv = $row['request_inv_no'] ?? null;
                    $active_return_date = $row['return_date'] ?? null;
                }
                if ($res) mysqli_free_result($res);
            }
            mysqli_stmt_close($stmt);
        }
        
        // ALSO check for any active/approved vacation (for Emergency vacation date restriction)
        // If there's an approved vacation that hasn't ended yet, get its return_date
        // Also check if there's an active vacation with review = 'A' to show its approval chain
        $active_vacation_inv = null;
        if (empty($active_return_date)) {
            $sql_active = "SELECT `return_date`, `request_inv_no` FROM `emp_vacation` 
                          WHERE `emp_id` = ? 
                          AND `current_status` IN ('approved', 'completed') 
                          AND `return_date` >= CURDATE() 
                          ORDER BY `return_date` DESC LIMIT 1";
            $stmt_active = mysqli_prepare($conDB, $sql_active);
            if ($stmt_active) {
                mysqli_stmt_bind_param($stmt_active, "s", $emp_id);
                if (mysqli_stmt_execute($stmt_active)) {
                    $res_active = mysqli_stmt_get_result($stmt_active);
                    if ($row_active = mysqli_fetch_assoc($res_active)) {
                        $active_return_date = $row_active['return_date'] ?? null;
                        $active_vacation_inv = $row_active['request_inv_no'] ?? null;
                    }
                    if ($res_active) mysqli_free_result($res_active);
                }
                mysqli_stmt_close($stmt_active);
            }
        }

        // Allow emergency vacation applications even with pending requests
        if (!empty($pending_inv) && !$is_emergency) {
            // Enrich message with current status and approver
            $current_status = null;
            $current_level = null;
            $approver_id = null;
            $approver_name = null;
            $sql_v = "SELECT `current_status`, `current_approval_level` FROM `emp_vacation` WHERE `request_inv_no` = ? LIMIT 1";
            $stmt_v = mysqli_prepare($conDB, $sql_v);
            if ($stmt_v) {
                mysqli_stmt_bind_param($stmt_v, "s", $pending_inv);
                if (mysqli_stmt_execute($stmt_v)) {
                    $res_v = mysqli_stmt_get_result($stmt_v);
                    if ($rowv = mysqli_fetch_assoc($res_v)) {
                        $current_status = $rowv['current_status'] ?? null;
                        $current_level = isset($rowv['current_approval_level']) ? (int)$rowv['current_approval_level'] : null;
                    }
                    if ($res_v) mysqli_free_result($res_v);
                }
                mysqli_stmt_close($stmt_v);
            }

            // Find the current pending approver based on current_approval_level (not DB status which may be stale)
            // Get the approver at the current_level from request_approvers (use latest row for that level)
            if (!empty($current_level)) {
                $sql_ra = "SELECT `approver_id` FROM `request_approvers` 
                           WHERE `request_inv_no` = ? AND `approval_level` = ? 
                           ORDER BY `id` DESC LIMIT 1";
                $stmt_ra = mysqli_prepare($conDB, $sql_ra);
                if ($stmt_ra) {
                    mysqli_stmt_bind_param($stmt_ra, "si", $pending_inv, $current_level);
                    if (mysqli_stmt_execute($stmt_ra)) {
                        $res_ra = mysqli_stmt_get_result($stmt_ra);
                        if ($rowra = mysqli_fetch_assoc($res_ra)) {
                            $approver_id = isset($rowra['approver_id']) ? (int)$rowra['approver_id'] : null;
                        }
                        if ($res_ra) mysqli_free_result($res_ra);
                    }
                    mysqli_stmt_close($stmt_ra);
                }
            }
            if (!empty($approver_id) && function_exists('getEmployeeDetailsForApproval')) {
                $details = getEmployeeDetailsForApproval($conDB, $approver_id);
                if ($details && !empty($details['name'])) {
                    $approver_name = parseName($details['name']);
                }
            }

            // Build full approval chain showing only the current active approval path
            // Logic: levels < current_level should be 'approved', current_level is 'pending', levels > current_level are 'awaiting'
            $chain = [];
            if (function_exists('get_approval_chain_status')) {
                $rows = get_approval_chain_status($conDB, $pending_inv, 'vacation_request');
                if (is_array($rows)) {
                    // Group by level to get the approver for each level (use latest row ID per level)
                    $byLevel = [];
                    foreach ($rows as $r) {
                        $lvl = isset($r['approval_level']) ? (int)$r['approval_level'] : 0;
                        $row_id = isset($r['id']) ? (int)$r['id'] : 0;
                        $aid = isset($r['approver_id']) ? (int)$r['approver_id'] : 0;
                        $name = isset($r['approver_name']) ? $r['approver_name'] : ('ID ' . ($aid ?: ''));
                        $db_status = $r['status'] ?? '';

                        // Keep the latest row for each level
                        if (!isset($byLevel[$lvl]) || $row_id > $byLevel[$lvl]['id']) {
                            $byLevel[$lvl] = ['id' => $row_id, 'name' => $name, 'status' => $db_status];
                        }
                    }

                    // Build chain with corrected statuses based on current_level
                    foreach ($byLevel as $lvl => $data) {
                        $display_status = '';
                        if ($lvl < $current_level) {
                            $display_status = 'approved'; // Already passed this level
                        } elseif ($lvl == $current_level) {
                            $display_status = 'pending'; // Currently at this level
                        } else {
                            $display_status = 'awaiting'; // Future levels
                        }
                        $chain[] = ['level' => $lvl, 'name' => parseName($data['name']), 'status' => $display_status];
                    }
                    usort($chain, function ($a, $b) {
                        return ($a['level'] ?? 0) <=> ($b['level'] ?? 0);
                    });
                }
            }

            $status_text = 'Pending approval';
            if ($current_status === 'approved') $status_text = 'Approved';
            elseif ($current_status === 'rejected') $status_text = 'Rejected';

            $human_msg = __("you_already_have_a_vacation_request_pending_approval") . " (" . htmlspecialchars($pending_inv) . ").";
            $extra = [];
            if ($status_text) $extra[] = "Current status: $status_text" . ($current_level ? " (Level $current_level)" : "");
            if ($approver_name) $extra[] = "Pending with: " . htmlspecialchars($approver_name);
            if (!empty($extra)) $human_msg .= "\n" . implode("\n", $extra);

            echo json_encode([
                'ok' => true,
                'can_apply' => false,
                'pending_inv' => $pending_inv,
                'current_status' => $current_status,
                'current_level' => $current_level,
                'current_approver_id' => $approver_id,
                'current_approver_name' => $approver_name,
                'chain' => $chain,
                'message' => $human_msg,
                'active_return_date' => $active_return_date
            ]);
            exit;
        }

        // Also show approval chain for active vacation with review = 'A'
        if (!empty($active_vacation_inv)) {
            $sql_active_review = "SELECT `review` FROM `emp_vacation` WHERE `request_inv_no` = ? AND `current_status` IN ('approved', 'completed') LIMIT 1";
            $stmt_review = mysqli_prepare($conDB, $sql_active_review);
            if ($stmt_review) {
                mysqli_stmt_bind_param($stmt_review, "s", $active_vacation_inv);
                if (mysqli_stmt_execute($stmt_review)) {
                    $res_review = mysqli_stmt_get_result($stmt_review);
                    if ($row_review = mysqli_fetch_assoc($res_review)) {
                        if (!empty($row_review['review']) && strtoupper($row_review['review']) === 'A') {
                            // Build approval chain for this active vacation
                            $chain = [];
                            if (function_exists('get_approval_chain_status')) {
                                $rows = get_approval_chain_status($conDB, $active_vacation_inv, 'vacation_request');
                                if (is_array($rows)) {
                                    // Group by level to get the approver for each level
                                    $byLevel = [];
                                    foreach ($rows as $r) {
                                        $lvl = isset($r['approval_level']) ? (int)$r['approval_level'] : 0;
                                        $row_id = isset($r['id']) ? (int)$r['id'] : 0;
                                        $aid = isset($r['approver_id']) ? (int)$r['approver_id'] : 0;
                                        $name = isset($r['approver_name']) ? $r['approver_name'] : ('ID ' . ($aid ?: ''));
                                        $db_status = $r['status'] ?? 'approved'; // All are approved in completed vacation

                                        // Keep the latest row for each level
                                        if (!isset($byLevel[$lvl]) || $row_id > $byLevel[$lvl]['id']) {
                                            $byLevel[$lvl] = ['id' => $row_id, 'name' => $name, 'status' => $db_status];
                                        }
                                    }

                                    // Build chain - all levels should be approved for completed vacation
                                    foreach ($byLevel as $lvl => $data) {
                                        $chain[] = ['level' => $lvl, 'name' => parseName($data['name']), 'status' => 'approved'];
                                    }
                                    usort($chain, function ($a, $b) {
                                        return ($a['level'] ?? 0) <=> ($b['level'] ?? 0);
                                    });
                                }
                            }

                            // $human_msg = __("you_have_an_active_vacation") . " (" . htmlspecialchars($active_vacation_inv) . "). " . __("viewing_approval_chain");
                            $human_msg = sprintf(__("you_have_an_active_vacation_viewing_approval_chain"), $active_vacation_inv);
                            
                            echo json_encode([
                                'ok' => true,
                                'can_apply' => false,
                                'is_active_vacation' => true,
                                'pending_inv' => $active_vacation_inv,
                                'current_status' => 'approved',
                                'current_level' => null,
                                'chain' => $chain,
                                'message' => $human_msg,
                                'active_return_date' => $active_return_date
                            ]);
                            if ($res_review) mysqli_free_result($res_review);
                            mysqli_stmt_close($stmt_review);
                            exit;
                        }
                    }
                    if ($res_review) mysqli_free_result($res_review);
                }
                mysqli_stmt_close($stmt_review);
            }
        }

        // Optionally return remaining balance snapshot for UI
        require_once __DIR__ . '/../../includes/get_vacation_balance.php';
        $remaining_balance = get_employee_vacation_balance($conDB, $emp_id);
        echo json_encode([
            'ok' => true,
            'can_apply' => true,
            'remaining_balance' => $remaining_balance,
            'active_return_date' => $active_return_date
        ]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// ================================================================
// --- [NEW] BLOCK TO HANDLE VACATION APPLICATION ---
// ================================================================
elseif ($ajaxType == 'applyVacation') {
    try {
        // 1. Sanitize all inputs
        $emp_id = (int)($_POST['emp_id'] ?? 0);
        
        // 1.1 Validate supervisor assignment FIRST
        $supervisor_check = validate_employee_supervisor($conDB, $emp_id);
        if (!$supervisor_check['valid']) {
            send_supervisor_validation_error($supervisor_check['message']);
        }
        $first_approver_id = (int)($_POST['first_approver_id'] ?? 0);
        $vac_type = escape_string($_POST['vac_type'] ?? '');
        $fly_type = escape_string($_POST['fly_type'] ?? '');
        $replacement_per = escape_string($_POST['replacement_per'] ?? '');
        $start_date = escape_string($_POST['start_date'] ?? '');
        $end_date = escape_string($_POST['end_date'] ?? '');
        $departure_date = escape_string($_POST['departure_date'] ?? '');
        $arrival_date = escape_string($_POST['arrival_date'] ?? '');
        $notes = escape_string($_POST['remarks'] ?? ''); // Changed from 'notes' to 'remarks' to match form field
        $vacation_salary_type = escape_string($_POST['vacation_salary_type'] ?? '');
        $encash_days = isset($_POST['encash_days']) ? (float)$_POST['encash_days'] : 0;
        $encashment_salary = isset($_POST['encashment_salary']) ? (float)str_replace(',', '', $_POST['encashment_salary']) : 0;

        // Build approval chain from configured settings
        $approver_chain = [];
        $is_annual_vacation = (($vac_type === 'Fly' || $vac_type === 'Local Vacation') && $fly_type === 'annual');
        $is_encashed_vacation = ($vac_type === 'Encashed');

        if ($is_annual_vacation) {
            // Fetch employee context (supervisor + department) for role resolution
            $emp_ctx = ['supervisor_id' => null, 'dept' => null];
            $stmt_ctx = mysqli_prepare($conDB, "SELECT supervisor_id, dept FROM employees WHERE emp_id = ? LIMIT 1");
            if ($stmt_ctx) {
                mysqli_stmt_bind_param($stmt_ctx, "i", $emp_id);
                mysqli_stmt_execute($stmt_ctx);
                $res_ctx = mysqli_stmt_get_result($stmt_ctx);
                if ($res_ctx && ($row_ctx = mysqli_fetch_assoc($res_ctx))) {
                    $emp_ctx['supervisor_id'] = $row_ctx['supervisor_id'];
                    $emp_ctx['dept'] = $row_ctx['dept'];
                }
                if ($res_ctx) mysqli_free_result($res_ctx);
                mysqli_stmt_close($stmt_ctx);
            }

            // Load configured approval chain from app_settings (approval_chain_vacation_request)
            $settingName = "approval_chain_vacation_request";
            $cfg_res = mysqli_query($conDB, "SELECT setting_value FROM app_settings WHERE setting_name = '" . escape_string($settingName) . "' LIMIT 1");
            $configured_chain = [];
            if ($cfg_res && mysqli_num_rows($cfg_res) > 0) {
                $cfg_row = mysqli_fetch_assoc($cfg_res);
                $decoded = json_decode($cfg_row['setting_value'], true);
                if (is_array($decoded)) {
                    $configured_chain = $decoded;
                }
            }
            if ($cfg_res) mysqli_free_result($cfg_res);

            // Helper: resolve a configured role to an approver emp_id
            $resolveApprover = function ($role) use ($conDB, $emp_ctx) {
                $role = trim((string)$role);
                if ($role === '') return null;

                // Direct Supervisor
                if ($role === 'direct_supervisor') {
                    return !empty($emp_ctx['supervisor_id']) ? (int)$emp_ctx['supervisor_id'] : null;
                }

                // Department Manager
                if ($role === 'dept_manager' && function_exists('getDeptManager')) {
                    $dept_mgr = getDeptManager($conDB, $emp_ctx['dept']);
                    return ($dept_mgr && !empty($dept_mgr['emp_id'])) ? (int)$dept_mgr['emp_id'] : null;
                }

                // Default: first active employee with matching user_type
                $stmt = mysqli_prepare($conDB, "SELECT e.emp_id FROM employees e JOIN admin_login al ON e.emp_id = al.emp_id WHERE al.user_type = ? AND e.status = 1 ORDER BY e.emp_id ASC LIMIT 1");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "s", $role);
                    mysqli_stmt_execute($stmt);
                    $res = mysqli_stmt_get_result($stmt);
                    if ($res && ($row = mysqli_fetch_assoc($res))) {
                        $empId = (int)$row['emp_id'];
                        mysqli_free_result($res);
                        mysqli_stmt_close($stmt);
                        return $empId > 0 ? $empId : null;
                    }
                    if ($res) mysqli_free_result($res);
                    mysqli_stmt_close($stmt);
                }
                return null;
            };

            if (!empty($configured_chain)) {
                foreach ($configured_chain as $step) {
                    $role = $step['user_type'] ?? '';
                    $approverId = $resolveApprover($role);
                    if ($approverId && !in_array($approverId, $approver_chain, true)) {
                        $approver_chain[] = $approverId;
                    }
                }
            }

            // Fallback to provided first approver if config produced nothing
            if (empty($approver_chain) && $first_approver_id > 0) {
                $approver_chain[] = $first_approver_id;
            }

            // If we have a chain, override first approver with the configured first step
            if (!empty($approver_chain)) {
                $first_approver_id = (int)$approver_chain[0];
            }
        }

        // ENCASHED VACATION: separate logic, always follow app_settings and append Finance Manager at the end
        if ($is_encashed_vacation) {
            // Reset chain for encashed flow
            $approver_chain = [];

            // Fetch employee context (supervisor + department) for role resolution
            $emp_ctx = ['supervisor_id' => null, 'dept' => null];
            $stmt_ctx = mysqli_prepare($conDB, "SELECT supervisor_id, dept FROM employees WHERE emp_id = ? LIMIT 1");
            if ($stmt_ctx) {
                mysqli_stmt_bind_param($stmt_ctx, "i", $emp_id);
                mysqli_stmt_execute($stmt_ctx);
                $res_ctx = mysqli_stmt_get_result($stmt_ctx);
                if ($res_ctx && ($row_ctx = mysqli_fetch_assoc($res_ctx))) {
                    $emp_ctx['supervisor_id'] = $row_ctx['supervisor_id'];
                    $emp_ctx['dept'] = $row_ctx['dept'];
                }
                if ($res_ctx) mysqli_free_result($res_ctx);
                mysqli_stmt_close($stmt_ctx);
            }

            // Load configured approval chain from app_settings (approval_chain_encashed_vacation)
            $settingName = "approval_chain_encashed_vacation";
            $cfg_res = mysqli_query($conDB, "SELECT setting_value FROM app_settings WHERE setting_name = '" . escape_string($settingName) . "' LIMIT 1");
            $configured_chain = [];
            if ($cfg_res && mysqli_num_rows($cfg_res) > 0) {
                $cfg_row = mysqli_fetch_assoc($cfg_res);
                $decoded = json_decode($cfg_row['setting_value'], true);
                if (is_array($decoded)) {
                    $configured_chain = $decoded;
                }
            }
            if ($cfg_res) mysqli_free_result($cfg_res);

            // Helper: resolve a configured role to an approver emp_id
            $resolveApprover = function ($role) use ($conDB, $emp_ctx) {
                $role = trim((string)$role);
                if ($role === '') return null;

                // Direct Supervisor
                if ($role === 'direct_supervisor') {
                    return !empty($emp_ctx['supervisor_id']) ? (int)$emp_ctx['supervisor_id'] : null;
                }

                // Department Manager
                if ($role === 'dept_manager' && function_exists('getDeptManager')) {
                    $dept_mgr = getDeptManager($conDB, $emp_ctx['dept']);
                    return ($dept_mgr && !empty($dept_mgr['emp_id'])) ? (int)$dept_mgr['emp_id'] : null;
                }

                // Finance Manager - find user with user_type = 'finance', emp_type = 'Manager', dept = 2
                if ($role === 'finance_manager') {
                    $stmt = mysqli_prepare($conDB, "SELECT e.emp_id FROM employees e JOIN admin_login al ON e.emp_id = al.emp_id WHERE al.user_type = 'finance' AND al.emp_type = 'Manager' AND al.dept = 2 AND e.status = 1 ORDER BY e.emp_id ASC LIMIT 1");
                    if ($stmt) {
                        mysqli_stmt_execute($stmt);
                        $res = mysqli_stmt_get_result($stmt);
                        if ($res && ($row = mysqli_fetch_assoc($res))) {
                            $empId = (int)$row['emp_id'];
                            mysqli_free_result($res);
                            mysqli_stmt_close($stmt);
                            return $empId > 0 ? $empId : null;
                        }
                        if ($res) mysqli_free_result($res);
                        mysqli_stmt_close($stmt);
                    }
                    return null;
                }

                // Default: first active employee with matching user_type
                $stmt = mysqli_prepare($conDB, "SELECT e.emp_id FROM employees e JOIN admin_login al ON e.emp_id = al.emp_id WHERE al.user_type = ? AND e.status = 1 ORDER BY e.emp_id ASC LIMIT 1");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "s", $role);
                    mysqli_stmt_execute($stmt);
                    $res = mysqli_stmt_get_result($stmt);
                    if ($res && ($row = mysqli_fetch_assoc($res))) {
                        $empId = (int)$row['emp_id'];
                        mysqli_free_result($res);
                        mysqli_stmt_close($stmt);
                        return $empId > 0 ? $empId : null;
                    }
                    if ($res) mysqli_free_result($res);
                    mysqli_stmt_close($stmt);
                }
                return null;
            };

            // Build chain from settings (skip finance_manager here to append at end)
            if (!empty($configured_chain)) {
                foreach ($configured_chain as $step) {
                    $role = $step['user_type'] ?? '';
                    if ($role === 'finance_manager') {
                        continue; // will append Finance Manager at the end
                    }
                    $approverId = $resolveApprover($role);
                    if ($approverId && !in_array($approverId, $approver_chain, true)) {
                        $approver_chain[] = $approverId;
                    }
                }
            }

            // Always append Finance Manager at the end (per requirement)
            $finance_mgr_id = $resolveApprover('finance_manager');
            if ($finance_mgr_id && !in_array($finance_mgr_id, $approver_chain, true)) {
                $approver_chain[] = $finance_mgr_id;
            }

            // Fallback to provided first approver if config+finance produced nothing
            if (empty($approver_chain) && $first_approver_id > 0) {
                $approver_chain[] = $first_approver_id;
            }

            // If we have a chain, override first approver with the configured first step
            if (!empty($approver_chain)) {
                $first_approver_id = (int)$approver_chain[0];
            }
        }

        // For non-annual or if config missing, default chain starts with provided first approver
        if (empty($approver_chain) && $first_approver_id > 0) {
            $approver_chain = [$first_approver_id];
        }

        // 2. Validate critical data
        if (empty($emp_id) || empty($vac_type) || empty($first_approver_id)) {
            throw new Exception(__("missing_required_fields_employee,_vacation_type_or_first_approver"));
        }

        // Validate vacation_salary_type - only allow 'payroll' or 'end_of_service'
        if (!empty($vacation_salary_type) && !in_array($vacation_salary_type, ['payroll', 'end_of_service'])) {
            throw new Exception(__("invalid_vacation_salary_type_selected"));
        }

        // 3. Guard: prevent multiple applications while a request is pending final approval
        // EXCEPTION: Allow emergency vacation applications even with pending requests (date overlap check below)
        $is_emergency = ($vac_type === 'Fly' && $fly_type === 'emergency');
        
        if (!$is_emergency) {
            $pending_inv = null;
            $sql_pending = "SELECT `request_inv_no` FROM `emp_vacation` WHERE `emp_id` = ? AND `current_status` = 'pending_approval' ORDER BY `id` DESC LIMIT 1";
            $stmt_pending = mysqli_prepare($conDB, $sql_pending);
            if ($stmt_pending) {
                mysqli_stmt_bind_param($stmt_pending, "s", $emp_id);
                if (mysqli_stmt_execute($stmt_pending)) {
                    $res_pending = mysqli_stmt_get_result($stmt_pending);
                    if ($rowp = mysqli_fetch_assoc($res_pending)) {
                        $pending_inv = $rowp['request_inv_no'] ?? null;
                    }
                    if ($res_pending) mysqli_free_result($res_pending);
                }
                mysqli_stmt_close($stmt_pending);
            }
            if (!empty($pending_inv)) {
                send_json_response(
                    __("pending_request_exists"),
                    __("you_already_have_a_vacation_request_pending_approval") . " (" . htmlspecialchars($pending_inv) . "). " . __("please_wait_until_it_is_finalized_before_applying_again"),
                    "info",
                    400
                );
                exit;
            }
        }

        // 4. Generate a cryptographically unique request_inv_no to avoid race conditions
        // Previous method used MAX(id) which is vulnerable under concurrency.
        // Format: VAC-<YYYYMMDDHHMMSS>-<EMPID>-<RND>
        // RND = 4 hex chars from random_bytes. We attempt up to 5 retries (extremely unlikely needed).
        $request_inv_no = null;
        $max_attempts = 5;
        $attempt = 0;
        $last_error = null;
        while ($attempt < $max_attempts) {
            $attempt++;
            $request_inv_no_candidate = sprintf(
                'VAC-%s-%s-%s',
                date('YmdHis'),
                preg_replace('/[^A-Za-z0-9]/', '', (string)$emp_id),
                substr(bin2hex(random_bytes(4)), 0, 4)
            );
            $stmt_chk = mysqli_prepare($conDB, "SELECT 1 FROM emp_vacation WHERE request_inv_no = ? LIMIT 1");
            if ($stmt_chk) {
                mysqli_stmt_bind_param($stmt_chk, 's', $request_inv_no_candidate);
                mysqli_stmt_execute($stmt_chk);
                mysqli_stmt_store_result($stmt_chk);
                $exists = mysqli_stmt_num_rows($stmt_chk) > 0;
                mysqli_stmt_close($stmt_chk);
                if (!$exists) {
                    $request_inv_no = $request_inv_no_candidate;
                    break;
                }
            } else {
                $last_error = mysqli_error($conDB);
            }
            // Small sleep to reduce chance of same second collisions if looping
            usleep(30000);
        }
        if (!$request_inv_no) {
            throw new Exception(__('failed_to_generate_unique_request_inv_no') . ($last_error ? ": $last_error" : ""));
        }

        // 5. Calculate total vacation days
        $vacdays = 0;
        if (!empty($start_date) && !empty($end_date)) {
            $date1 = new DateTime($start_date);
            $date2 = new DateTime($end_date);
            $diff = $date1->diff($date2);
            $vacdays = $diff->days + 1;
        }

        // 5b. Check if employee is currently on an active approved/completed vacation that overlaps with requested dates
        if (!empty($start_date) && !empty($end_date)) {
            $sql_active = "SELECT request_inv_no, start_date, return_date, current_status FROM emp_vacation 
                           WHERE emp_id = ? 
                             AND current_status IN ('approved', 'completed')
                             AND start_date <= ? 
                             AND return_date >= ? 
                           LIMIT 1";
            $stmt_active = mysqli_prepare($conDB, $sql_active);
            if ($stmt_active) {
                mysqli_stmt_bind_param($stmt_active, 'sss', $emp_id, $end_date, $start_date);



                if (mysqli_stmt_execute($stmt_active)) {
                    $res_active = mysqli_stmt_get_result($stmt_active);
                    if ($res_active && mysqli_num_rows($res_active) > 0) {
                        $active_vac = mysqli_fetch_assoc($res_active);



                        if ($res_active) mysqli_free_result($res_active);
                        mysqli_stmt_close($stmt_active);

                        $status_display = ($active_vac['current_status'] === 'completed') ? __('completed') : __('approved');

                        send_json_response(
                            __('date_conflict_with_active_vacation'),
                            __('your_requested_dates') . ' (' . htmlspecialchars($start_date) . ' ' . __('to') . ' ' . htmlspecialchars($end_date) . ') ' . __('overlap_with_an_existing') . ' ' . $status_display . ' ' . __('vacation') . ' (' . htmlspecialchars($active_vac['request_inv_no']) . ') ' . __('from') . ' ' . htmlspecialchars($active_vac['start_date']) . ' ' . __('to') . ' ' . htmlspecialchars($active_vac['return_date']) . '. ' . __('please_choose_different_dates'),
                            'error',
                            400
                        );
                        exit;
                    }
                    if ($res_active) mysqli_free_result($res_active);
                }
                mysqli_stmt_close($stmt_active);
            }
        }

        // 5c. Prevent overlapping / duplicate vacation requests (pending, approved, or completed)
        if (!empty($start_date) && !empty($end_date)) {
            $sql_overlap = "SELECT request_inv_no, start_date, return_date, current_status FROM emp_vacation 
                            WHERE emp_id = ? 
                              AND current_status IN ('pending_approval', 'approved', 'completed') 
                              AND start_date <= ? 
                              AND return_date >= ? ";
            $stmt_overlap = mysqli_prepare($conDB, $sql_overlap);
            if ($stmt_overlap) {
                // bind as strings (emp_id may be varchar in schema)
                mysqli_stmt_bind_param($stmt_overlap, 'sss', $emp_id, $end_date, $start_date);



                if (mysqli_stmt_execute($stmt_overlap)) {
                    $res_overlap = mysqli_stmt_get_result($stmt_overlap);
                    if ($res_overlap && mysqli_num_rows($res_overlap) > 0) {
                        $ov = mysqli_fetch_assoc($res_overlap);



                        if ($res_overlap) mysqli_free_result($res_overlap);
                        mysqli_stmt_close($stmt_overlap);

                        $status_text = 'pending';
                        if ($ov['current_status'] === 'approved') {
                            $status_text = 'approved';
                        } elseif ($ov['current_status'] === 'completed') {
                            $status_text = 'completed';
                        }

                        send_json_response(
                            __('date_conflict'),
                            __('you_already_have_a') . ' ' . $status_text . ' ' . __('vacation_request') . ' (' . htmlspecialchars($ov['request_inv_no']) . ') ' . __('covering') . ' ' . htmlspecialchars($ov['start_date']) . ' ' . __('to') . ' ' . htmlspecialchars($ov['return_date']) . '. ' . __('your_requested_dates') . ' (' . htmlspecialchars($start_date) . ' ' . __('to') . ' ' . htmlspecialchars($end_date) . ') ' . __('overlap_with_this_existing_request') . '. ' . __('please_choose_different_dates'),
                            'error',
                            400
                        );
                        exit;
                    }
                    if ($res_overlap) mysqli_free_result($res_overlap);
                }
                mysqli_stmt_close($stmt_overlap);
            } else {
                $last_error = mysqli_error($conDB);
                throw new Exception(__('database_error_during_overlap_check') . $last_error);
            }
        }

        // 6. Check balance before proceeding (use live calculated balance)
        // EXCEPTION: Emergency vacation does NOT require balance check (it is unpaid)
        $is_emergency_vacation = ($vac_type === 'Fly' && $fly_type === 'emergency') || ($vac_type === 'Local Vacation' && $fly_type === 'emergency');
        
        $remaining_balance = get_current_vacation_balance($conDB, $emp_id);

        // Fallback: calculate remaining from contract period if no balance row
        $effective_remaining = $remaining_balance;
        $contract_days = null;
        $used_days_in_period = 0;
        $period_start_str = null;
        $period_end_str = null;
        if ($effective_remaining === false) {
            // Get contract days and joining date
            $sql_contract = "SELECT cp.vac_period AS contract_days, e.joining_date FROM employees e JOIN contract_period cp ON e.vac_period = cp.id WHERE e.emp_id = ? LIMIT 1";
            $stmt_contract = mysqli_prepare($conDB, $sql_contract);
            if ($stmt_contract) {
                // emp_id can be numeric/string; bind as string for safety
                mysqli_stmt_bind_param($stmt_contract, "s", $emp_id);
                mysqli_stmt_execute($stmt_contract);
                $res_contract = mysqli_stmt_get_result($stmt_contract);
                $row_contract = mysqli_fetch_assoc($res_contract);
                mysqli_free_result($res_contract);
                mysqli_stmt_close($stmt_contract);

                if ($row_contract) {
                    $contract_days = (float)$row_contract['contract_days'];
                    $joining_date = !empty($row_contract['joining_date']) ? new DateTime($row_contract['joining_date']) : null;
                    $today = new DateTime();
                    if ($joining_date instanceof DateTime) {
                        // Determine current anniversary period [period_start, period_end]
                        $anniv = (clone $joining_date);
                        $anniv->setDate((int)$today->format('Y'), (int)$joining_date->format('m'), (int)$joining_date->format('d'));
                        if ($anniv > $today) {
                            $anniv->modify('-1 year');
                        }
                        $period_start = (clone $anniv);
                        $period_end = (clone $anniv);
                        $period_end->modify('+1 year')->modify('-1 day');
                        $period_start_str = $period_start->format('Y-m-d');
                        $period_end_str = $period_end->format('Y-m-d');

                        // Sum approved deductible vacations in this window
                        $sql_used = "SELECT COALESCE(SUM(vacdays),0) AS used FROM emp_vacation 
                                     WHERE emp_id = ? AND current_status = 'approved' 
                                       AND ( (vac_type = 'Fly' AND (fly_type = 'annual' OR fly_type = 'emergency'))
                                            OR (vac_type = 'Local Vacation') )
                                       AND start_date >= ? AND return_date <= ?";
                        $stmt_used = mysqli_prepare($conDB, $sql_used);
                        if ($stmt_used) {
                            mysqli_stmt_bind_param($stmt_used, "iss", $emp_id, $period_start_str, $period_end_str);
                            mysqli_stmt_execute($stmt_used);
                            $res_used = mysqli_stmt_get_result($stmt_used);
                            $row_used = mysqli_fetch_assoc($res_used);
                            $used_days_in_period = (float)($row_used['used'] ?? 0);
                            mysqli_free_result($res_used);
                            mysqli_stmt_close($stmt_used);
                        }

                        $calc_remaining = max(0.0, $contract_days - $used_days_in_period);
                        $effective_remaining = $calc_remaining; // now numeric
                    }
                }
            }
        }

        // If still unknown, default to 0 remaining to be safe
        if ($effective_remaining === false || $effective_remaining === null) {
            $effective_remaining = 0.0;
        }

        // [NEW] If this is an Encashment request, force today's dates and use the user-entered encash_days
        $is_encashment_request = (trim(strtolower($notes)) === 'encashment') || (trim(strtolower($vac_type)) === 'encashed');
        if ($is_encashment_request) {
            // Force start/return dates to today for encashment
            $today = date('Y-m-d');
            $start_date = $today;
            $end_date = $today;

            // Use user-entered days from the form
            if ($encash_days > 0) {
                $vacdays = $encash_days;
            } else {
                send_json_response(
                    __('invalid_input'),
                    __('please_enter_the_number_of_days_you_want_to_encash'),
                    "error",
                    400
                );
                exit;
            }
            // Validate user didn't request more than available
            if ($encash_days > $effective_remaining) {
                $error_message = sprintf(
                    __('you_requested_to_encash_days_but_your_available_balance_is_only_days', 'You requested to encash %s days but your available balance is only %s days.'),
                    $encash_days,
                    $effective_remaining
                );
                send_json_response(
                    __('insufficient_balance', 'Insufficient Balance'),
                    $error_message,
                    "error",
                    400
                );
                exit;
            }
        }

        if ($vacdays > $effective_remaining) {
            // Emergency vacation is unpaid and does NOT deduct from balance - skip this check
            if ($is_emergency_vacation) {
                // Emergency vacation can exceed balance - it's unpaid
                // Continue without throwing error
            } else {
                $details = '';
                if ($contract_days !== null && $period_start_str && $period_end_str) {
                    $details = ' ' . sprintf(
                        __("you_are_allowed_days_per_contract_period_used_days_period_start_to_period_end", "You are allowed %s days per contract period. You have used %s days (from %s to %s)."),
                        $contract_days,
                        $used_days_in_period,
                        $period_start_str,
                        $period_end_str
                    );
                }
                $error_message = sprintf(
                    __("you_requested_days_but_your_available_balance_is_only_days", "You requested %s days but your available balance is only %s days."),
                    $vacdays,
                    $effective_remaining
                ) . $details;
                
                send_json_response(
                    __('insufficient_balance', 'Insufficient Balance'),
                    $error_message,
                    "error",
                    400
                );
                exit;
            }
        }

        // 7. Handle file upload (if any)
        $attachment_path = null;
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == UPLOAD_ERR_OK) {
            $uploadDir = "./../../assets/vac_uploads/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $fileExtension = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
            $safe_filename = preg_replace('/[^A-Za-z0-9\._-]/', '', basename($_FILES['attachment']['name']));
            $fileName = "vac_" . $request_inv_no . "_" . time() . '.' . $fileExtension;
            $targetPath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $targetPath)) {
                $attachment_path = $targetPath;
            } else {
            }
        }

        // [NEW] Use encashment amount calculated on frontend
        $encashment_amount = null;
        if ($is_encashment_request) {
            // Use the encashment_salary already calculated by frontend
            $encashment_amount = $encashment_salary;
        }

        // 8. Insert the main vacation request
        // NOTE: For DATE columns that need NULL support, we build the SQL manually because mysqli_stmt_bind_param
        // with 's' type doesn't properly handle NULL for DATE columns (converts to '0000-00-00')

        $vacdays_int = (int)$vacdays;
        $encashment_amount_val = ($encashment_amount !== null ? (float)$encashment_amount : 0.0);
        $submitted_by_val = ($current_user_id && (int)$current_user_id > 0) ? (int)$current_user_id : 'NULL';

        // Prepare date values - use NULL keyword for empty dates
        // Use null-safe approach: only escape non-empty strings
        $departure_date_sql = (!empty($departure_date) && $departure_date !== '0000-00-00') ? "'" . mysqli_real_escape_string($conDB, (string)$departure_date) . "'" : 'NULL';
        $arrival_date_sql = (!empty($arrival_date) && $arrival_date !== '0000-00-00') ? "'" . mysqli_real_escape_string($conDB, (string)$arrival_date) . "'" : 'NULL';

        // Escape other string values
        $emp_id_esc = mysqli_real_escape_string($conDB, $emp_id);
        $vac_type_esc = mysqli_real_escape_string($conDB, $vac_type);
        $fly_type_esc = mysqli_real_escape_string($conDB, $fly_type);
        $replacement_per_esc = mysqli_real_escape_string($conDB, $replacement_per);
        $start_date_esc = mysqli_real_escape_string($conDB, $start_date);
        $end_date_esc = mysqli_real_escape_string($conDB, $end_date);
        $notes_esc = mysqli_real_escape_string($conDB, $notes ?? '');
        $vacation_salary_type_esc = mysqli_real_escape_string($conDB, $vacation_salary_type ?? '');
        $attachment_path_esc = $attachment_path ? mysqli_real_escape_string($conDB, $attachment_path) : '';
        $request_inv_no_esc = mysqli_real_escape_string($conDB, $request_inv_no);

        // Determine is_deductible flag
        // If vacation type is "Fly" OR "Local Vacation" with fly_type "annual", set is_deductible = 0
        // This means the employee stays active in payroll with full salary (no deductions)
        $is_deductible = 1; // Default: deductible (affects payroll)
        if (($vac_type === 'Fly' || $vac_type === 'Local Vacation') && $fly_type === 'annual') {
            $is_deductible = 0; // Not deductible: employee remains in full payroll
        }


        $sql = "INSERT INTO `emp_vacation` 
                    (`emp_id`, `submitted_by_emp_id`, `vac_type`, `fly_type`, `replacement_person`, `start_date`, `return_date`, `departure_date`, `arrival_date`, `vacdays`, `remarks`, `vacation_salary_type`, `attachment_path`, `encashment_amount`, `request_inv_no`, `is_deductible`, `current_status`, `current_approval_level`,`review`) 
                VALUES 
                    ('$emp_id_esc', $submitted_by_val, '$vac_type_esc', '$fly_type_esc', '$replacement_per_esc', '$start_date_esc', '$end_date_esc', $departure_date_sql, $arrival_date_sql, $vacdays_int, '$notes_esc', '$vacation_salary_type_esc', '$attachment_path_esc', $encashment_amount_val, '$request_inv_no_esc', $is_deductible, 'pending_approval', 1,'A')";

        if (!mysqli_query($conDB, $sql)) {

            throw new Exception("INSERT failed (insert vac): " . mysqli_error($conDB));
        }

        // Get the inserted ID
        $inserted_id = mysqli_insert_id($conDB);
        
        // Log vacation request submission
        ActivityLogger::logSubmit('Vacation', 'ajaxVacation.php', $inserted_id, "Submitted vacation request: {$request_inv_no}, Days: {$vacdays}", 'emp_vacation');

        // 8.5 DEDUCT FROM VACATION BALANCE for annual vacation and encashment
        // Deduct immediately upon submission to reserve the days
        $should_deduct_balance = false;
        
        // NOTE: Balance deduction is now handled ONLY on final approval via update_vacation_balance_on_approval()
        // Do NOT deduct from emp_vacation_balance when applying - only at final approval
        // This prevents duplicate deductions when multiple approval stages occur

        // 9. Create approval chain using ApprovalChainManager
        require_once __DIR__ . '/../ApprovalChainManager.php';
        $chainManager = new ApprovalChainManager($conDB, $pdo);
        
        // Get employee department for approval chain
        $dept_id = null;
        $dept_stmt = mysqli_prepare($conDB, "SELECT dept FROM employees WHERE emp_id = ? LIMIT 1");
        if ($dept_stmt) {
            mysqli_stmt_bind_param($dept_stmt, "i", $emp_id);
            mysqli_stmt_execute($dept_stmt);
            $dept_res = mysqli_stmt_get_result($dept_stmt);
            if ($dept_row = mysqli_fetch_assoc($dept_res)) {
                $dept_id = $dept_row['dept'];
            }
            if ($dept_res) mysqli_free_result($dept_res);
            mysqli_stmt_close($dept_stmt);
        }
        
        $chainResult = $chainManager->createApprovalChain(
            'vacation_request',
            $request_inv_no,
            $emp_id,
            $dept_id
        );
        
        if (!$chainResult['success']) {
            throw new Exception(sprintf(__("vacation_request_created_but_failed_to_save_approval_chain"), htmlspecialchars($request_inv_no)));
        }
        
        $first_approver = $chainResult['first_approver'];

        // ENCASHED: ensure Finance Manager is appended at the end of the approval chain (per requirement)
        if ($is_encashed_vacation) {
            // Resolve Finance Manager (user_type = finance, emp_type = Manager, dept = 2)
            $finance_mgr_id = null;
            $stmt_fm = mysqli_prepare($conDB, "SELECT e.emp_id FROM employees e JOIN admin_login al ON e.emp_id = al.emp_id WHERE al.user_type = 'finance' AND al.emp_type = 'Manager' AND al.dept = 2 AND e.status = 1 ORDER BY e.emp_id ASC LIMIT 1");
            if ($stmt_fm) {
                mysqli_stmt_execute($stmt_fm);
                $res_fm = mysqli_stmt_get_result($stmt_fm);
                if ($res_fm && ($row_fm = mysqli_fetch_assoc($res_fm))) {
                    $finance_mgr_id = (int)$row_fm['emp_id'];
                }
                if ($res_fm) mysqli_free_result($res_fm);
                mysqli_stmt_close($stmt_fm);
            }

            if ($finance_mgr_id) {
                // Determine request_type_id for vacation_request
                $request_type_id = 3; // default fallback
                $type_q = mysqli_query($conDB, "SELECT id FROM approval_request_types WHERE type_name='vacation_request' LIMIT 1");
                if ($type_q && ($type_row = mysqli_fetch_assoc($type_q))) {
                    $request_type_id = (int)$type_row['id'];
                    mysqli_free_result($type_q);
                }

                // Skip if Finance Manager already present
                $exists_stmt = mysqli_prepare($conDB, "SELECT 1 FROM request_approvers WHERE request_inv_no = ? AND request_type_id = ? AND approver_id = ? LIMIT 1");
                $already_present = false;
                if ($exists_stmt) {
                    mysqli_stmt_bind_param($exists_stmt, "sii", $request_inv_no, $request_type_id, $finance_mgr_id);
                    mysqli_stmt_execute($exists_stmt);
                    mysqli_stmt_store_result($exists_stmt);
                    $already_present = mysqli_stmt_num_rows($exists_stmt) > 0;
                    mysqli_stmt_close($exists_stmt);
                }

                if (!$already_present) {
                    // Find current max approval level
                    $max_level = 1;
                    $max_stmt = mysqli_prepare($conDB, "SELECT MAX(approval_level) as max_lvl FROM request_approvers WHERE request_inv_no = ? AND request_type_id = ?");
                    if ($max_stmt) {
                        mysqli_stmt_bind_param($max_stmt, "si", $request_inv_no, $request_type_id);
                        mysqli_stmt_execute($max_stmt);
                        $max_res = mysqli_stmt_get_result($max_stmt);
                        if ($max_res && ($max_row = mysqli_fetch_assoc($max_res))) {
                            $max_level = (int)($max_row['max_lvl'] ?? 1);
                        }
                        if ($max_res) mysqli_free_result($max_res);
                        mysqli_stmt_close($max_stmt);
                    }

                    $insert_stmt = mysqli_prepare($conDB, "INSERT INTO request_approvers (request_inv_no, request_type_id, approver_id, approval_level, status) VALUES (?, ?, ?, ?, 'awaiting')");
                    if ($insert_stmt) {
                        $next_level = $max_level + 1;
                        mysqli_stmt_bind_param($insert_stmt, "siii", $request_inv_no, $request_type_id, $finance_mgr_id, $next_level);
                        mysqli_stmt_execute($insert_stmt);
                        mysqli_stmt_close($insert_stmt);
                    }
                }
            }
        }

        // 10. Send success response with next approver name (where it will wait)
        $pending_with_text = '';
        if ($first_approver && !empty($first_approver['approver_id'])) {
            $first_details = getEmployeeDetailsForApproval($conDB, (int)$first_approver['approver_id']);
            if ($first_details && !empty($first_details['name'])) {
                $label = function_exists('__') ? __('pending_with') : 'Pending with';
                $pending_with_text = " $label: " . $first_details['name'] . ".";

                // Notify first approver using ApprovalChainManager
                $chainManager->notifyApprover(
                    $first_approver['approver_id'],
                    __("new_vacation_request_pending_your_approval"),
                    sprintf(__("new_vacation_request_from_employee_pending_your_approval"), htmlspecialchars($request_inv_no), htmlspecialchars($emp_id)),
                    "all_applied_vac.php?status=my_pending"
                );

                // Send email notification
                if (!empty($first_details['email']) && function_exists('send_approval_email')) {
                    // Get employee name for template
                    $employee_name = 'Employee';
                    $emp_result = mysqli_query($conDB, "SELECT name FROM employees WHERE emp_id = '$emp_id' LIMIT 1");
                    if ($emp_result && $emp_row = mysqli_fetch_assoc($emp_result)) {
                        $employee_name = $emp_row['name'];
                    }
                    if ($emp_result) mysqli_free_result($emp_result);

                    // Prepare template data
                    $base_url = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME'], 3);
                    $template_data = [
                        'APPROVER_NAME' => $first_details['name'],
                        'REQUEST_TYPE' => 'Annual Vacation Request',
                        'REQUEST_TYPE_LOWER' => 'annual vacation request',
                        'REQUEST_ID' => $request_inv_no,
                        'EMPLOYEE_NAME' => $employee_name,
                        'START_DATE' => date('d M Y', strtotime($start_date)),
                        'END_DATE' => date('d M Y', strtotime($end_date)),
                        'DURATION' => $vacdays,
                        'REQUEST_URL' => $base_url . '/all_applied_vac.php?status=my_pending'
                    ];

                    $email_subject = __("new_vacation_request_pending_approval");
                    $email_result = send_approval_email($conDB, $first_details['email'], $first_details['name'], $email_subject, 'vacation_request', $template_data);
                }
            }
        }
        
        send_json_response("Success!", sprintf(__("your_vacation_request_submitted_for_approval"), htmlspecialchars($request_inv_no)) . $pending_with_text, "success");
    } catch (Exception $e) {
        send_json_response("Error", __("an_error_occurred") . ": " . $e->getMessage(), "error", 500);
    }
    exit;
}

// ================================================================
// --- [NEW] BLOCK TO HANDLE VACATION APPROVAL ---
// ================================================================
elseif ($ajaxType == 'approveVacation') {
    try {
        $vacation_id = (int)($_POST['vacation_id'] ?? 0);
        $approver_chain = (array)($_POST['approver_chain'] ?? []);
        $asset_checker_emp_id = (int)($_POST['asset_checker_emp_id'] ?? 0);

        // Payment and travel details (sent by HR Assistant or GR Officer)
        $departure_date = trim($_POST['departure_date'] ?? '');
        $arrival_date = trim($_POST['arrival_date'] ?? '');
        $ticket_pay = (float)($_POST['ticket_pay'] ?? 0);
        $permit_fee = (float)($_POST['permit_fee'] ?? 0);

        // Approval comment (optional)
        $approval_comment = trim($_POST['approval_comment'] ?? '');
        $approval_comment = mb_substr($approval_comment, 0, 5000); // Limit to 5000 chars

        // Payroll details (only sent by HR Payroll)
        $overtime_hours = (float)($_POST['overtime_hours'] ?? 0);
        $deduction_hours = (float)($_POST['deduction_hours'] ?? 0);
        $deduction_days = (float)($_POST['deduction_days'] ?? 0);
        $payroll_note = trim($_POST['payroll_note'] ?? '');

        // HR Team CC recipients (only sent by HR Senior BP)
        $hr_team_cc = (array)($_POST['hr_team_cc'] ?? []);

        // Log for debugging


        if (empty($vacation_id)) {
            throw new Exception(__("vacation_id_missing"));
        }
        if (empty($current_user_id)) {
            throw new Exception(__("session_expired_please_log_in_again"));
        }

        // 1. Get the request_inv_no and payment-related fields from the vacation ID
        $query_inv = mysqli_query($conDB, "SELECT `request_inv_no`, `vac_type`, `fly_type`, `payment_status`, `is_payment_completed`, `departure_date`, `arrival_date`, `ticket_pay`, `permit_fee` FROM `emp_vacation` WHERE `id` = " . $vacation_id);
        if (!$query_inv || mysqli_num_rows($query_inv) == 0) {
            if ($query_inv) mysqli_free_result($query_inv);
            throw new Exception("Invalid Vacation ID.");
        }
        $row_inv = mysqli_fetch_assoc($query_inv);
        $request_inv_no = $row_inv['request_inv_no'];
        $vac_type = $row_inv['vac_type'] ?? '';
        $fly_type = $row_inv['fly_type'] ?? '';
        $payment_status = $row_inv['payment_status'] ?? 'pending_payment';
        $is_payment_completed = (int)($row_inv['is_payment_completed'] ?? 0);
        // Payment fields that must be present before final approval
        $has_departure = !empty($row_inv['departure_date']);
        $has_arrival = !empty($row_inv['arrival_date']);
        $ticket_pay_val = (float)($row_inv['ticket_pay'] ?? 0);
        $permit_fee_val = (float)($row_inv['permit_fee'] ?? 0);
        mysqli_free_result($query_inv);
        
        // 1.1 CHECK (UPDATED): HR Payroll can approve Fly + Annual without upfront payment.
        // Payment and travel details will be handled after approval via a separate action.
        // Get current user's role and department
        $user_role = '';
        $current_user_dept = null;
        $user_role_query = mysqli_query($conDB, "SELECT al.user_type, e.dept FROM admin_login al LEFT JOIN employees e ON al.emp_id = e.emp_id WHERE al.emp_id = '{$current_user_id}'");
        if ($user_role_query && mysqli_num_rows($user_role_query) > 0) {
            $user_data = mysqli_fetch_assoc($user_role_query);
            $user_role = $user_data['user_type'];
            $current_user_dept = isset($user_data['dept']) ? (int)$user_data['dept'] : null;
            mysqli_free_result($user_role_query);
            // No enforcement here; allow approval to proceed.
        }

        // Normalize approver chain into integers and enforce uniqueness early
        $approver_chain = array_values(array_filter(array_map('intval', $approver_chain)));
        $asset_checker_added = false;

        // Asset checker selection (IT/Administration/Transportation managers only)
        // NOTE: Asset checker is NOT applicable for Encashed vacations
        $asset_manager_departments = [1, 6, 17];
        // Treat any active staff within the asset departments as eligible to assign a checker;
        // frontend is already restricted to those departments.
        $is_asset_manager = ($current_user_dept !== null && in_array($current_user_dept, $asset_manager_departments, true));
        $is_encashed_vacation = ($vac_type === 'Encashed');

        if ($asset_checker_emp_id > 0) {
            // Check: Asset checker selection NOT allowed for Encashed vacations
            if ($is_encashed_vacation) {
                throw new Exception('Asset checker selection is not applicable for Encashed vacations.');
            }

            if (!$is_asset_manager) {
                throw new Exception('Only IT, Administration, or Transportation managers can assign an asset checker.');
            }

            // Validate asset checker exists and belongs to the same department
            $checker_dept = null;
            $checker_status = null;
            $checker_stmt = mysqli_prepare($conDB, "SELECT dept, status FROM employees WHERE emp_id = ? LIMIT 1");
            if ($checker_stmt) {
                mysqli_stmt_bind_param($checker_stmt, "i", $asset_checker_emp_id);
                mysqli_stmt_execute($checker_stmt);
                $checker_res = mysqli_stmt_get_result($checker_stmt);
                if ($checker_res && ($checker_row = mysqli_fetch_assoc($checker_res))) {
                    $checker_dept = isset($checker_row['dept']) ? (int)$checker_row['dept'] : null;
                    $checker_status = (int)($checker_row['status'] ?? 0);
                }
                if ($checker_res) mysqli_free_result($checker_res);
                mysqli_stmt_close($checker_stmt);
            }

            if ($checker_status !== 1) {
                throw new Exception('Selected asset checker is not active.');
            }
            if ($checker_dept !== $current_user_dept) {
                throw new Exception('Asset checker must belong to your department.');
            }

            // Don't add asset checker to chain here - it will be added after approval with correct level
            // $approver_chain = array_merge([$asset_checker_emp_id], $approver_chain);
            $asset_checker_added = true;
        }

        // Remove duplicates and self-assignments
        $approver_chain = array_values(array_filter(array_unique($approver_chain), function ($id) use ($current_user_id) {
            return $id > 0 && $id !== (int)$current_user_id;
        }));

        // 2. Detect the actual request type (vacation_request or excuse_leave)
        $type_detect_query = mysqli_query($conDB, "SELECT art.type_name, art.id AS type_id FROM request_approvers ra JOIN approval_request_types art ON ra.request_type_id = art.id WHERE ra.request_inv_no = '" . escape_string($request_inv_no) . "' LIMIT 1");
        $detected_type = 'vacation_request'; // Default fallback
        $detected_type_id = null;
        if ($type_detect_query && mysqli_num_rows($type_detect_query) > 0) {
            $type_row = mysqli_fetch_assoc($type_detect_query);
            $detected_type = $type_row['type_name'];
            $detected_type_id = isset($type_row['type_id']) ? (int)$type_row['type_id'] : null;
            mysqli_free_result($type_detect_query);
        }
        
        // 2.1 Check if Finance Manager is selecting a payer for this vacation
        $payer_emp_id = (int)($_POST['payer_emp_id'] ?? 0);
        $is_finance_manager = ($user_role === 'finance');
        
        if ($is_finance_manager && $payer_emp_id > 0) {
            // Finance Manager is approving and selecting a payer
            require_once __DIR__ . '/../ApprovalChainManager.php';
            $chainManager = new ApprovalChainManager($conDB, $pdo);
            
            try {
                // Use ApprovalChainManager to handle payer selection
                $payerSelectionResult = $chainManager->approveWithPayerSelection(
                    $request_inv_no,
                    $current_user_id,
                    $payer_emp_id,
                    $approval_comment ?: 'Approved. Finance payer selected for payment processing.'
                );
                
                // Payer information is now recorded in request_approvers table (no need to update emp_vacation)
                
                error_log("Vacation $request_inv_no: Payer selection via ApprovalChainManager - Payer: {$payer_emp_id}");
                
                // Notify payer about assignment via browser notification
                $chainManager->notifyApprover(
                    $payer_emp_id,
                    'Vacation Request - Payment Processing Assignment',
                    'You have been assigned to process payment for vacation request. Please record the payment amount and proof.',
                    'all_applied_vac.php'
                );
                
                // Send email to payer
                if (!empty($payerSelectionResult['payer']['email']) && function_exists('send_approval_email')) {
                    $vacation_details_stmt = $pdo->prepare("
                        SELECT ev.*, e.name as employee_name 
                        FROM emp_vacation ev
                        LEFT JOIN employees e ON ev.emp_id = e.emp_id
                        WHERE ev.request_inv_no = :inv_no
                        LIMIT 1
                    ");
                    $vacation_details_stmt->execute([':inv_no' => $request_inv_no]);
                    $vacation_details = $vacation_details_stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($vacation_details) {
                        $base_url = 'https://hr.almutlaksystem.com';
                        $payer_name = $payerSelectionResult['payer']['name'] ?? 'Finance Staff';
                        $employee_name = $vacation_details['employee_name'] ?? 'Employee';
                        $emp_id = $vacation_details['emp_id'] ?? 'N/A';
                        $vacation_type = ucfirst($vacation_details['vac_type'] ?? 'Vacation');
                        $fly_type = !empty($vacation_details['fly_type']) ? ucfirst($vacation_details['fly_type']) : '';
                        $start_date = $vacation_details['start_date'] ?? 'N/A';
                        $return_date = $vacation_details['return_date'] ?? 'N/A';
                        $vacation_days = $vacation_details['vacdays'] ?? 'N/A';
                        $replacement = $vacation_details['replacement_person'] ?? 'N/A';
                        
                        // Format dates for display
                        $start_date_formatted = ($start_date !== 'N/A') ? date('d M Y', strtotime($start_date)) : 'N/A';
                        $return_date_formatted = ($return_date !== 'N/A') ? date('d M Y', strtotime($return_date)) : 'N/A';
                        
                        $template_data = [
                            'APPROVER_NAME' => $payer_name,
                            'REQUEST_ID' => $request_inv_no,
                            'EMPLOYEE_NAME' => $employee_name,
                            'EMPLOYEE_ID' => $emp_id,
                            'VACATION_TYPE' => $fly_type ? "$vacation_type ($fly_type)" : $vacation_type,
                            'START_DATE' => $start_date_formatted,
                            'RETURN_DATE' => $return_date_formatted,
                            'VACATION_DAYS' => $vacation_days,
                            'REPLACEMENT_PERSON' => $replacement,
                            'REQUEST_URL' => $base_url . '/all_applied_vac.php?status=my_pending',
                            'EMAIL_MESSAGE' => 'You have been assigned by Finance Manager to process the payment for this vacation request. Please review all details, record the payment amount, confirm the salary deduction details, and upload payment proof to complete the transaction. Click the link above to process this request.'
                        ];
                        
                        $email_subject = "Vacation Payment Processing Assignment - " . $request_inv_no . " (" . $employee_name . ")";
                        send_approval_email($conDB, $payerSelectionResult['payer']['email'], $payer_name, $email_subject, 'vacation_request', $template_data);
                    }
                }
                
                // Return success - payer selection is complete, don't call normal approval handler
                echo json_encode([
                    'status' => 'success',
                    'title' => 'Payer Assigned Successfully',
                    'message' => 'Finance payer has been assigned. Awaiting payment details.',
                    'type' => 'success'
                ]);
                return;
            } catch (Exception $e) {
                // Log error and return failure
                error_log("Vacation $request_inv_no: Error in approveWithPayerSelection: " . $e->getMessage());
                echo json_encode([
                    'status' => 'error',
                    'title' => 'Error',
                    'message' => 'Error assigning payer: ' . $e->getMessage(),
                    'type' => 'error'
                ]);
                return;
            }
        }
        
        // 2.2 Check if current user is assigned as a PAYER and process payment
        require_once __DIR__ . '/../ApprovalChainManager.php';
        $chainManager = new ApprovalChainManager($conDB, $pdo);
        
        // Try to process payer payment
        try {
            $paymentAmount = (float)($_POST['payment_amount'] ?? 0);
            $paymentProof = $_FILES['payment_proof'] ?? null;
            
            $payerResult = $chainManager->processPayerPayment(
                $request_inv_no,
                $current_user_id,
                $paymentAmount,
                $paymentProof,
                $approval_comment
            );
            
            // If user IS a payer, handle payment response
            if ($payerResult['is_payer']) {
                if ($payerResult['success']) {
                    // Payer payment processed successfully
                    ActivityLogger::logUpdate('Vacation', 'ajaxVacation.php', $vacation_id, 
                        "Payer approved: Payment amount {$paymentAmount} SAR, Proof: {$payerResult['payment_proof']}", 'emp_vacation');
                    
                    // For ENCASHED vacations, mark as completed immediately (no asset clearance needed)
                    if ($vac_type === 'Encashed') {
                        $update_encashed_stmt = mysqli_prepare($conDB, "UPDATE emp_vacation SET current_status = 'completed', review = 'C' WHERE id = ?");
                        if ($update_encashed_stmt) {
                            mysqli_stmt_bind_param($update_encashed_stmt, "i", $vacation_id);
                            mysqli_stmt_execute($update_encashed_stmt);
                            mysqli_stmt_close($update_encashed_stmt);
                            
                            ActivityLogger::logUpdate('Vacation', 'ajaxVacation.php', $vacation_id, 
                                "Encashed vacation marked as completed after payer payment - no asset clearance required", 'emp_vacation');
                        }
                    }
                    
                    // Return success
                    send_json_response(
                        "Payment Confirmed!",
                        $payerResult['message'] . " Vacation request completed.",
                        "success"
                    );
                } else {
                    // Payment processing failed
                    throw new Exception($payerResult['message'] ?? "Payment processing failed");
                }
            }
            // If not a payer, continue with normal approval below
            
        } catch (Exception $payerEx) {
            // Check if user is a payer but payment validation failed
            // Try to get payer status without payment requirements
            try {
                $payer_check = $chainManager->processPayerPayment(
                    $request_inv_no,
                    $current_user_id,
                    0,
                    null,
                    ''
                );
                
                if ($payer_check['is_payer']) {
                    // User IS a payer but payment processing failed, throw error
                    throw $payerEx;
                }
            } catch (Exception $e) {
                // If still error, might indicate user is indeed a payer
                // Throw original exception
                throw $payerEx;
            }
            // User is NOT a payer, continue with normal approval
        }
        
        // 2.3 Call the main approval handler with detected type (only if NOT Finance Manager with payer selection and NOT a payer)
        $result = handle_approval_action(
            $conDB,
            $request_inv_no,
            $detected_type, // Use detected type instead of hardcoded
            $current_user_id,
            'approve',
            'Approved', // Default note
            $approver_chain // Pass the dynamic chain
        );

        if ($result['status'] == 'error') {
            throw new Exception($result['message']);
        }

        // Notify the selected asset checker and ensure their task is marked AWAITING (not pending)
        // They will be awaiting their turn, and when shown the approval UI, they'll see asset clearance modal
        if ($asset_checker_added && $asset_checker_emp_id > 0) {
            if ($detected_type_id !== null) {
                // Get the maximum approval level from existing approvers to calculate asset checker's level
                $max_level_query = mysqli_query($conDB, "SELECT MAX(approval_level) as max_level FROM request_approvers WHERE request_inv_no = '" . escape_string($request_inv_no) . "' AND request_type_id = " . (int)$detected_type_id);
                $max_level = 1; // Default to 1 if no existing approvers
                if ($max_level_query && mysqli_num_rows($max_level_query) > 0) {
                    $level_row = mysqli_fetch_assoc($max_level_query);
                    $max_level = isset($level_row['max_level']) && $level_row['max_level'] > 0 ? (int)$level_row['max_level'] : 0;
                    mysqli_free_result($max_level_query);
                }
                
                // Asset checker level = last_level + 1
                $asset_checker_level = $max_level + 1;
                
                // Check if asset checker already exists in the approval chain
                $check_exists_stmt = mysqli_prepare($conDB, "SELECT id FROM request_approvers WHERE request_inv_no = ? AND request_type_id = ? AND approver_id = ? LIMIT 1");
                if ($check_exists_stmt) {
                    mysqli_stmt_bind_param($check_exists_stmt, "sii", $request_inv_no, $detected_type_id, $asset_checker_emp_id);
                    mysqli_stmt_execute($check_exists_stmt);
                    $exists_result = mysqli_stmt_get_result($check_exists_stmt);
                    $exists = mysqli_num_rows($exists_result) > 0;
                    mysqli_stmt_close($check_exists_stmt);
                    
                    if ($exists_result) mysqli_free_result($exists_result);
                    
                    if ($exists) {
                        // Asset checker already exists - just update status and level
                        $stmt_set_awaiting = mysqli_prepare($conDB, "UPDATE request_approvers SET status = 'awaiting', approval_level = ? WHERE request_inv_no = ? AND request_type_id = ? AND approver_id = ?");
                        if ($stmt_set_awaiting) {
                            mysqli_stmt_bind_param($stmt_set_awaiting, "isii", $asset_checker_level, $request_inv_no, $detected_type_id, $asset_checker_emp_id);
                            mysqli_stmt_execute($stmt_set_awaiting);
                            mysqli_stmt_close($stmt_set_awaiting);
                        }
                    } else {
                        // Asset checker doesn't exist - INSERT them into the approval chain
                        $stmt_insert_checker = mysqli_prepare($conDB, "INSERT INTO request_approvers (request_inv_no, request_type_id, approver_id, approval_level, status) VALUES (?, ?, ?, ?, 'awaiting')");
                        if ($stmt_insert_checker) {
                            mysqli_stmt_bind_param($stmt_insert_checker, "siii", $request_inv_no, $detected_type_id, $asset_checker_emp_id, $asset_checker_level);
                            mysqli_stmt_execute($stmt_insert_checker);
                            mysqli_stmt_close($stmt_insert_checker);
                        }
                    }
                }
            }

            $notification_title = 'Asset clearance required';
            $notification_message = "Vacation {$request_inv_no} needs asset clearance.";
            $notification_url = "all_applied_vac.php?status=my_pending";
            if (function_exists('create_browser_notification')) {
                create_browser_notification($conDB, $asset_checker_emp_id, $notification_title, $notification_message, $notification_url);
            }

            if (function_exists('getEmployeeDetailsForApproval')) {
                $checker_details = getEmployeeDetailsForApproval($conDB, $asset_checker_emp_id);
                if ($checker_details && !empty($checker_details['email'])) {
                    $template_data = get_request_details_for_email($conDB, $request_inv_no, $detected_type, $checker_details['name']);
                    send_approval_email($conDB, $checker_details['email'], $checker_details['name'], $notification_title, $detected_type, $template_data ?: []);
                }
            }
        }
        
        // 2.4 Save approval comment if provided
        if (!empty($approval_comment)) {
            // Get approver name
            $sql_approver = "SELECT name FROM employees WHERE emp_id = ?";
            $stmt_approver = mysqli_prepare($conDB, $sql_approver);
            if ($stmt_approver) {
                mysqli_stmt_bind_param($stmt_approver, "i", $current_user_id);
                mysqli_stmt_execute($stmt_approver);
                $result_approver = mysqli_stmt_get_result($stmt_approver);
                $approver_data = mysqli_fetch_assoc($result_approver);
                $approver_name = $approver_data['name'] ?? 'Unknown';
                mysqli_free_result($result_approver);
                mysqli_stmt_close($stmt_approver);
            } else {
                $approver_name = 'Unknown';
            }
            
            // Save to approval_comments table if the function exists
            if (function_exists('save_approval_comment_db')) {
                save_approval_comment_db(
                    $conDB,
                    $request_inv_no,
                    $detected_type, // Use detected type
                    'approved',
                    $current_user_id,
                    $approver_name,
                    $approval_comment
                );
            }
        }
        
        // Log vacation approval
        $vacation_details = mysqli_query($conDB, "SELECT * FROM emp_vacation WHERE id = {$vacation_id}");
        $old_vacation = mysqli_fetch_assoc($vacation_details);
        if ($old_vacation) {
            ActivityLogger::logApproval('Vacation', 'ajaxVacation.php', $vacation_id, 'approved', "Approved vacation request: {$request_inv_no}", 'emp_vacation');
        }
        if ($vacation_details) mysqli_free_result($vacation_details);

        // 3. Always update travel dates if provided (by HR Assistant or GR Officer), regardless of payment amounts
        // This ensures arrival_date gets saved even if there are no payments
        // Update BOTH dates and payments in ONE query to ensure atomicity
        $needs_update = false;
        $update_fields = [];
        $update_values = [];
        $update_types = "";

        // Check if we have dates to update
        if (!empty($departure_date)) {
            $update_fields[] = "`departure_date` = ?";
            $update_values[] = $departure_date;
            $update_types .= "s";
            $needs_update = true;
        }

        if (!empty($arrival_date)) {
            $update_fields[] = "`arrival_date` = ?";
            $update_values[] = $arrival_date;
            $update_types .= "s";
            $needs_update = true;
        }

        // Check if we have payment amounts to update
        if ($ticket_pay > 0) {
            $update_fields[] = "`ticket_pay` = ?";
            $update_values[] = $ticket_pay;
            $update_types .= "d";
            $needs_update = true;
        }

        if ($permit_fee > 0) {
            $update_fields[] = "`permit_fee` = ?";
            $update_values[] = $permit_fee;
            $update_types .= "d";
            $needs_update = true;
        }

        // Execute the update if we have any fields to update
        if ($needs_update) {
            $sql_update = "UPDATE `emp_vacation` SET " . implode(", ", $update_fields) . " WHERE `id` = ?";
            $update_values[] = $vacation_id;
            $update_types .= "i";



            $stmt_update = mysqli_prepare($conDB, $sql_update);
            if ($stmt_update) {
                mysqli_stmt_bind_param($stmt_update, $update_types, ...$update_values);
                if (!mysqli_stmt_execute($stmt_update)) {
                } else {
                    $affected = mysqli_stmt_affected_rows($stmt_update);


                    // Verify the update by reading back
                    $verify_sql = "SELECT departure_date, arrival_date, ticket_pay, permit_fee FROM emp_vacation WHERE id = ?";
                    $verify_stmt = mysqli_prepare($conDB, $verify_sql);
                    if ($verify_stmt) {
                        mysqli_stmt_bind_param($verify_stmt, "i", $vacation_id);
                        mysqli_stmt_execute($verify_stmt);
                        $verify_result = mysqli_stmt_get_result($verify_stmt);
                        if ($verify_row = mysqli_fetch_assoc($verify_result)) {
                        }
                        mysqli_stmt_close($verify_stmt);
                    }
                }
                mysqli_stmt_close($stmt_update);
            } else {
            }
        } else {
        }

        // 3.1 If payroll adjustments were included (by HR Payroll), save them
        if ($overtime_hours > 0 || $deduction_hours > 0 || $deduction_days > 0 || !empty($payroll_note)) {
            // Get employee ID from vacation record
            $sql_emp = "SELECT emp_id FROM emp_vacation WHERE id = ?";
            $stmt_emp = mysqli_prepare($conDB, $sql_emp);
            if ($stmt_emp) {
                mysqli_stmt_bind_param($stmt_emp, "i", $vacation_id);
                mysqli_stmt_execute($stmt_emp);
                $result_emp = mysqli_stmt_get_result($stmt_emp);
                if ($emp_row = mysqli_fetch_assoc($result_emp)) {
                    $emp_id = $emp_row['emp_id'];

                    // Save payroll adjustments to vacation record or a separate table
                    // Update emp_vacation with payroll adjustments (assuming columns exist)
                    $sql_payroll = "UPDATE `emp_vacation` 
                                   SET `overtime_hours` = ?, `deduction_hours` = ?, `deduction_days` = ?, `payroll_note` = ? 
                                   WHERE `id` = ?";
                    $stmt_payroll = mysqli_prepare($conDB, $sql_payroll);
                    if ($stmt_payroll) {
                        mysqli_stmt_bind_param($stmt_payroll, "dddsi", $overtime_hours, $deduction_hours, $deduction_days, $payroll_note, $vacation_id);
                        if (!mysqli_stmt_execute($stmt_payroll)) {

                            // Don't fail the whole approval, just log this error
                        } else {
                        }
                        mysqli_stmt_close($stmt_payroll);
                    }
                }
                mysqli_stmt_close($stmt_emp);
            }
        }

        // 4. Send email notifications to HR Team CC recipients (if any)
        if (!empty($hr_team_cc) && is_array($hr_team_cc)) {
            // Filter out empty values and ensure we have valid employee IDs
            $hr_team_cc = array_filter(array_map('intval', $hr_team_cc), function($id) {
                return $id > 0;
            });
            
            if (!empty($hr_team_cc)) {
                // Get vacation details for email
                $sql_vac = "SELECT ev.*, e.name as emp_name, e.email as emp_email 
                            FROM emp_vacation ev 
                            JOIN employees e ON ev.emp_id = e.emp_id 
                            WHERE ev.id = ?";
                $stmt_vac = mysqli_prepare($conDB, $sql_vac);

                if ($stmt_vac) {
                    mysqli_stmt_bind_param($stmt_vac, "i", $vacation_id);
                    mysqli_stmt_execute($stmt_vac);
                    $result_vac = mysqli_stmt_get_result($stmt_vac);

                    if ($vac_data = mysqli_fetch_assoc($result_vac)) {
                        // Get CC recipient details - join with admin_login to get email
                        $cc_ids = implode(',', $hr_team_cc);
                        $sql_cc = "SELECT e.emp_id, e.name, al.email 
                                   FROM employees e 
                                   LEFT JOIN admin_login al ON e.emp_id = al.emp_id 
                                   WHERE e.emp_id IN ($cc_ids) AND e.status = 1";
                        $result_cc = mysqli_query($conDB, $sql_cc);

                        if ($result_cc && mysqli_num_rows($result_cc) > 0) {
                            // Prepare email template data for CC notification
                            // Use vac_type (correct column name) instead of vacation_type
                            $vac_type = trim($vac_data['vac_type'] ?? 'Vacation Request');
                            $fly_type = trim($vac_data['fly_type'] ?? '');
                            
                            // Build request type description
                            $reqType = $vac_type;
                            if (!empty($fly_type)) {
                                $reqType .= ' (' . ucfirst($fly_type) . ')';
                            }
                            
                            $subject = "$reqType Approved (CC) - {$vac_data['emp_name']}";
                            $base_url = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME'], 3);

                            // Send email to each CC recipient using template system
                            $cc_sent_count = 0;
                            while ($cc_rec = mysqli_fetch_assoc($result_cc)) {
                                if (!empty($cc_rec['email'])) {
                                    if (function_exists('send_approval_email')) {
                                        // Prepare template data for this CC recipient
                                        $cc_template_data = [
                                            'APPROVER_NAME' => $cc_rec['name'],
                                            'REQUEST_TYPE' => $reqType . ' (Approved - CC)',
                                            'REQUEST_TYPE_LOWER' => strtolower($reqType) . ' (approved)',
                                            'REQUEST_ID' => $vac_data['request_inv_no'],
                                            'EMPLOYEE_NAME' => $vac_data['emp_name'],
                                            'START_DATE' => date('d M Y', strtotime($vac_data['start_date'])),
                                            'END_DATE' => date('d M Y', strtotime($vac_data['return_date'])),
                            'DURATION' => $vac_data['vacdays'],
                                            'REQUEST_URL' => $base_url . '/all_applied_vac.php'
                                        ];

                                        $email_sent = send_approval_email($conDB, $cc_rec['email'], $cc_rec['name'], $subject, 'vacation_request', $cc_template_data);
                                        if ($email_sent) {
                                            $cc_sent_count++;
                                        }
                                    }
                                }
                            }
                            
                            // Log CC notification sent
                            if ($cc_sent_count > 0) {
                                ActivityLogger::logUpdate('Vacation', 'ajaxVacation.php', $vacation_id, 
                                    "Sent CC notification to {$cc_sent_count} HR team members for request {$vac_data['request_inv_no']}", 
                                    'emp_vacation');
                            }
                            
                            mysqli_free_result($result_cc);
                        }
                    }
                    mysqli_stmt_close($stmt_vac);
                }
            }
        }

        // 5. Send success response
        send_json_response("Approved!", __("the_vacation_request_has_been_approved"), "success");
    } catch (Exception $e) {

        send_json_response("Error", $e->getMessage(), "error", 500);
    }
    exit;
}

// ================================================================
// === [NEW] PROCESS PAYMENT ENDPOINT (HR PAYROLL ONLY) ===
// ================================================================
// Purpose: HR Payroll processes payment separately from approval
// This marks the vacation as "paid" and moves to approval stage
// ================================================================
elseif ($ajaxType == 'processPayment') {
    try {
        $vacation_id = (int)($_POST['vacation_id'] ?? 0);
        $request_inv_no = trim($_POST['request_inv_no'] ?? '');
        
        if (empty($vacation_id) || empty($request_inv_no)) {
            throw new Exception("Missing vacation_id or request_inv_no");
        }
        
        if (empty($current_user_id)) {
            throw new Exception(__("session_expired_please_log_in_again"));
        }
        
        // Verify current user is HR Payroll
        $user_check = mysqli_query($conDB, "SELECT user_type FROM admin_login WHERE emp_id = '{$current_user_id}'");
        if (!$user_check || mysqli_num_rows($user_check) === 0) {
            throw new Exception("User not found or unauthorized");
        }
        $user_row = mysqli_fetch_assoc($user_check);
        if ($user_row['user_type'] !== 'hr_payroll') {
            throw new Exception("Only HR Payroll can process payments");
        }
        mysqli_free_result($user_check);
        
        // 1. Verify vacation exists and is in correct status
        $vac_query = mysqli_query($conDB, "SELECT * FROM emp_vacation WHERE id = {$vacation_id}");
        if (!$vac_query || mysqli_num_rows($vac_query) === 0) {
            throw new Exception("Vacation request not found");
        }
        $vacation = mysqli_fetch_assoc($vac_query);
        mysqli_free_result($vac_query);
        
        // Verify current status is "approved" or "pending_approval" with right approval level
        if (!in_array($vacation['current_status'], ['approved', 'pending_approval'])) {
            throw new Exception("Vacation must be approved or pending approval before payment");
        }
        
        // 2. Update payment status to "paid"
        $payment_date = date('Y-m-d H:i:s');
        $update_query = "UPDATE emp_vacation 
                        SET payment_status = 'paid',
                            payment_date = ?,
                            is_payment_completed = 1
                        WHERE id = ?";
        
        $stmt = mysqli_prepare($conDB, $update_query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conDB->error);
        }
        
        mysqli_stmt_bind_param($stmt, "si", $payment_date, $vacation_id);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Payment update failed: " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
        
        // 3. Log the payment action
        ActivityLogger::logUpdate('Vacation', 'ajaxVacation.php', $vacation_id, 
            "Payment processed by HR Payroll - Request {$request_inv_no}", 'emp_vacation');
        
        // 4. Send success response with next steps
        send_json_response("Payment Processed!", 
            "Payment has been recorded. You can now approve the vacation request.", 
            "success");
            
    } catch (Exception $e) {
        send_json_response("Error", $e->getMessage(), "error", 500);
    }
    exit;
}

// ================================================================
// === [NEW] MODIFY PAYMENT ENDPOINT (HR PAYROLL ONLY) ===
// ================================================================
// Purpose: HR Payroll can modify payment details if marked as "needs_modification"
// ================================================================
elseif ($ajaxType == 'modifyPayment') {
    try {
        $vacation_id = (int)($_POST['vacation_id'] ?? 0);
        $payment_note = trim($_POST['payment_note'] ?? '');
        
        if (empty($vacation_id)) {
            throw new Exception("Missing vacation_id");
        }
        
        if (empty($current_user_id)) {
            throw new Exception(__("session_expired_please_log_in_again"));
        }
        
        // Verify current user is HR Payroll
        $user_check = mysqli_query($conDB, "SELECT user_type FROM admin_login WHERE emp_id = '{$current_user_id}'");
        if (!$user_check || mysqli_num_rows($user_check) === 0) {
            throw new Exception("User not found or unauthorized");
        }
        $user_row = mysqli_fetch_assoc($user_check);
        if ($user_row['user_type'] !== 'hr_payroll') {
            throw new Exception("Only HR Payroll can modify payments");
        }
        mysqli_free_result($user_check);
        
        // 1. Verify vacation exists
        $vac_query = mysqli_query($conDB, "SELECT * FROM emp_vacation WHERE id = {$vacation_id}");
        if (!$vac_query || mysqli_num_rows($vac_query) === 0) {
            throw new Exception("Vacation request not found");
        }
        $vacation = mysqli_fetch_assoc($vac_query);
        mysqli_free_result($vac_query);
        
        // 2. Mark payment as "needs_modification"
        $modified_date = date('Y-m-d H:i:s');
        $update_query = "UPDATE emp_vacation 
                        SET payment_status = 'needs_modification',
                            payment_modified_date = ?,
                            payment_modified_by = ?,
                            payroll_note = CONCAT(COALESCE(payroll_note, ''), '\n[PAYMENT MODIFICATION] ', ?)
                        WHERE id = ?";
        
        $stmt = mysqli_prepare($conDB, $update_query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conDB->error);
        }
        
        mysqli_stmt_bind_param($stmt, "siss", $modified_date, $current_user_id, $payment_note, $vacation_id);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Payment modification failed: " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
        
        // 3. Log the modification
        ActivityLogger::logUpdate('Vacation', 'ajaxVacation.php', $vacation_id, 
            "Payment marked for modification by HR Payroll - Note: {$payment_note}", 'emp_vacation');
        
        // 4. Send success response
        send_json_response("Payment Modification Recorded!", 
            "Payment has been marked for modification. Please review and update as needed.", 
            "success");
            
    } catch (Exception $e) {
        send_json_response("Error", $e->getMessage(), "error", 500);
    }
    exit;
}

// ================================================================
// --- [NEW] BLOCK TO HANDLE VACATION REJECTION ---
// ================================================================
elseif ($ajaxType == 'rejectVacation') {
    try {
        $vacation_id = (int)($_POST['vacation_id'] ?? 0);
        $rejection_note = escape_string($_POST['rejection_note'] ?? 'Rejected');

        if (empty($vacation_id)) {
            throw new Exception(__("vacation_id_missing"));
        }
        if (empty($current_user_id)) {
            throw new Exception(__("your_session_has_expired_please_log_in_again"));
        }
        if (empty($rejection_note)) {
            throw new Exception(__("rejection_reason_required"));
        }

        // 1. Get the request_inv_no from the vacation ID
        $query_inv = mysqli_query($conDB, "SELECT `request_inv_no` FROM `emp_vacation` WHERE `id` = " . $vacation_id);
        if (!$query_inv || mysqli_num_rows($query_inv) == 0) {
            if ($query_inv) mysqli_free_result($query_inv);
            throw new Exception(__("invalid_vacation_id"));
        }
        $row_inv = mysqli_fetch_assoc($query_inv);
        $request_inv_no = $row_inv['request_inv_no'];
        mysqli_free_result($query_inv);

        // 2. Detect the actual request type (vacation_request or excuse_leave)
        $type_detect_query = mysqli_query($conDB, "SELECT art.type_name FROM request_approvers ra JOIN approval_request_types art ON ra.request_type_id = art.id WHERE ra.request_inv_no = '" . escape_string($request_inv_no) . "' LIMIT 1");
        $detected_type = 'vacation_request'; // Default fallback
        if ($type_detect_query && mysqli_num_rows($type_detect_query) > 0) {
            $type_row = mysqli_fetch_assoc($type_detect_query);
            $detected_type = $type_row['type_name'];
            mysqli_free_result($type_detect_query);
        }
        
        // 2.1 Call the main approval handler with detected type
        $result = handle_approval_action(
            $conDB,
            $request_inv_no,
            $detected_type, // Use detected type instead of hardcoded
            $current_user_id,
            'reject',
            $rejection_note, // Pass the rejection note
            [] // No chain needed for rejection
        );

        if ($result['status'] == 'error') {
            throw new Exception($result['message']);
        }
        
        // Log vacation rejection
        $vacation_details = mysqli_query($conDB, "SELECT * FROM emp_vacation WHERE id = {$vacation_id}");
        $old_vacation = mysqli_fetch_assoc($vacation_details);
        if ($old_vacation) {
            ActivityLogger::logApproval('Vacation', 'ajaxVacation.php', $vacation_id, 'rejected', "Rejected vacation request: {$request_inv_no}, Reason: {$rejection_note}", 'emp_vacation');
            
            // REFUND VACATION BALANCE - When a vacation request is rejected, restore the deducted days
            // Only refund for annual vacation and encashment (not for emergency vacation)
            $emp_id = $old_vacation['emp_id'];
            $vac_type = $old_vacation['vac_type'];
            $fly_type = $old_vacation['fly_type'];
            $vacdays = (float)$old_vacation['vacdays'];
            $remarks = strtolower(trim($old_vacation['remarks'] ?? ''));
            
            $is_annual = (($vac_type === 'Fly' || $vac_type === 'Local Vacation') && $fly_type === 'annual');
            $is_encashment = (strpos($remarks, 'encashment') !== false || strtolower($vac_type) === 'encashed');
            $is_emergency = (($vac_type === 'Fly' || $vac_type === 'Local Vacation') && $fly_type === 'emergency');
            
            // Refund balance if it was deducted (annual or encashment, but not emergency)
            if (($is_annual || $is_encashment) && !$is_emergency && $vacdays > 0) {
                // Get the current balance record for this employee
                $sql_get_balance = "SELECT `id`, `used_days`, `remaining_balance`, `available_balance` 
                                    FROM `emp_vacation_balance` 
                                    WHERE `emp_id` = ? 
                                    ORDER BY `last_updated` DESC 
                                    LIMIT 1";
                $stmt_get_balance = mysqli_prepare($conDB, $sql_get_balance);
                
                if ($stmt_get_balance) {
                    mysqli_stmt_bind_param($stmt_get_balance, "s", $emp_id);
                    mysqli_stmt_execute($stmt_get_balance);
                    $res_balance = mysqli_stmt_get_result($stmt_get_balance);
                    
                    if ($res_balance && ($row_balance = mysqli_fetch_assoc($res_balance))) {
                        $balance_id = (int)$row_balance['id'];
                        $current_used_days = (float)$row_balance['used_days'];
                        $current_remaining = (float)$row_balance['remaining_balance'];
                        $current_available = (float)$row_balance['available_balance'];
                        
                        // Calculate new values after refund (restore the days)
                        $new_used_days = max(0, $current_used_days - $vacdays);
                        $new_remaining = $current_remaining + $vacdays;
                        $new_available = $current_available + $vacdays;
                        
                        // Update the balance record
                        $sql_update_balance = "UPDATE `emp_vacation_balance` 
                                              SET `used_days` = ?, 
                                                  `remaining_balance` = ?, 
                                                  `available_balance` = ?,
                                                  `last_updated` = NOW() 
                                              WHERE `id` = ?";
                        $stmt_update_balance = mysqli_prepare($conDB, $sql_update_balance);
                        
                        if ($stmt_update_balance) {
                            mysqli_stmt_bind_param($stmt_update_balance, "dddi", $new_used_days, $new_remaining, $new_available, $balance_id);
                            if (mysqli_stmt_execute($stmt_update_balance)) {
                                // Balance successfully refunded
                                ActivityLogger::logUpdate('VacationBalance', 'ajaxVacation.php', $balance_id, 
                                    "Refunded {$vacdays} days for rejected request {$request_inv_no}. New remaining: {$new_remaining}", 
                                    'emp_vacation_balance');
                            } else {
                                error_log("Failed to refund vacation balance for emp_id {$emp_id}: " . mysqli_stmt_error($stmt_update_balance));
                            }
                            mysqli_stmt_close($stmt_update_balance);
                        }
                    }
                    
                    if ($res_balance) mysqli_free_result($res_balance);
                    mysqli_stmt_close($stmt_get_balance);
                }
            }
        }
        if ($vacation_details) mysqli_free_result($vacation_details);

        // 3. Send success response
        send_json_response("Rejected!", __("the_vacation_request_has_been_rejected"), "success");
    } catch (Exception $e) {

        send_json_response("Error", $e->getMessage(), "error", 500);
    }
    exit;
}

// ================================================================
// --- [NEW] BLOCK TO HANDLE EMPLOYEE RETURN FROM VACATION ---
// ================================================================
elseif ($ajaxType == 'returnVacation') {
    try {
        $vacation_id = (int)($_POST['vacation_id'] ?? 0);
        $actual_return_date = escape_string($_POST['returnDate'] ?? '');

        if (empty($vacation_id)) {
            throw new Exception(__("vacation_id_is_missing"));
        }
        if (empty($actual_return_date)) {
            throw new Exception("Return date is required.");
        }

        // Get vacation details (emp_id, planned return_date, vacdays)
        $sql_vac = "SELECT `emp_id`, `return_date`, `vacdays`, `id` FROM `emp_vacation` WHERE `id` = ?";
        $stmt_vac = mysqli_prepare($conDB, $sql_vac);
        if (!$stmt_vac) {
            throw new Exception(__("database_prepare_error") . ": " . mysqli_error($conDB));
        }

        mysqli_stmt_bind_param($stmt_vac, "i", $vacation_id);
        mysqli_stmt_execute($stmt_vac);
        $res_vac = mysqli_stmt_get_result($stmt_vac);

        if (!$res_vac || !($row_vac = mysqli_fetch_assoc($res_vac))) {
            throw new Exception(__("vacation_record_not_found"));
        }

        $emp_id = (int)$row_vac['emp_id'];
        $planned_return_date = $row_vac['return_date'];
        $original_vacdays = (int)$row_vac['vacdays'];
        $vac_id_for_balance = (int)$row_vac['id'];

        if ($res_vac) mysqli_free_result($res_vac);
        mysqli_stmt_close($stmt_vac);

        // Calculate extra days (if actual return is after planned return)
        $extra_days = 0;
        if (!empty($planned_return_date) && !empty($actual_return_date)) {
            $planned_date_obj = new DateTime($planned_return_date);
            $actual_date_obj = new DateTime($actual_return_date);

            if ($actual_date_obj > $planned_date_obj) {
                $diff = $planned_date_obj->diff($actual_date_obj);
                $extra_days = $diff->days;
            }
        }

        // If there are extra days, deduct from vacation balance
        if ($extra_days > 0) {
            // Get the current balance record for this vacation
            $sql_balance = "SELECT `id`, `remaining_balance` FROM `emp_vacation_balance` WHERE `vac_id` = ? LIMIT 1";
            $stmt_balance = mysqli_prepare($conDB, $sql_balance);
            if ($stmt_balance) {
                mysqli_stmt_bind_param($stmt_balance, "i", $vac_id_for_balance);
                mysqli_stmt_execute($stmt_balance);
                $res_balance = mysqli_stmt_get_result($stmt_balance);

                if ($res_balance && ($row_balance = mysqli_fetch_assoc($res_balance))) {
                    $balance_id = (int)$row_balance['id'];
                    $current_remaining = (float)$row_balance['remaining_balance'];

                    // Deduct extra days from remaining balance (don't go below 0)
                    $new_remaining = max(0, $current_remaining - $extra_days);

                    $sql_update_balance = "UPDATE `emp_vacation_balance` SET `remaining_balance` = ? WHERE `id` = ?";
                    $stmt_update_balance = mysqli_prepare($conDB, $sql_update_balance);
                    if ($stmt_update_balance) {
                        mysqli_stmt_bind_param($stmt_update_balance, "di", $new_remaining, $balance_id);
                        mysqli_stmt_execute($stmt_update_balance);
                        mysqli_stmt_close($stmt_update_balance);
                    }
                }

                if ($res_balance) mysqli_free_result($res_balance);
                mysqli_stmt_close($stmt_balance);
            }

            // Update the vacation record with new total days
            $new_total_vacdays = $original_vacdays + $extra_days;
            $sql_update_vac = "UPDATE `emp_vacation` SET `vacdays` = ? WHERE `id` = ?";
            $stmt_update_vac = mysqli_prepare($conDB, $sql_update_vac);
            if ($stmt_update_vac) {
                mysqli_stmt_bind_param($stmt_update_vac, "ii", $new_total_vacdays, $vacation_id);
                mysqli_stmt_execute($stmt_update_vac);
                mysqli_stmt_close($stmt_update_vac);
            }
        }

        // Set employee fly status to 0 (returned/available)
        $sql_fly = "UPDATE `employees` SET `fly` = 0 WHERE `emp_id` = ?";
        $stmt_fly = mysqli_prepare($conDB, $sql_fly);
        if (!$stmt_fly) {
            throw new Exception(__("failed_to_prepare_employee_fly_update") . ": " . mysqli_error($conDB));
        }

        mysqli_stmt_bind_param($stmt_fly, "i", $emp_id);
        if (!mysqli_stmt_execute($stmt_fly)) {
            throw new Exception(__("failed_to_mark_employee_as_returned") . ": " . mysqli_stmt_error($stmt_fly));
        }
        mysqli_stmt_close($stmt_fly);

        // Mark vacation as completed so employee can apply for new vacation
        $sql_complete_vac = "UPDATE `emp_vacation` SET `current_status` = 'completed', `arrived_date` = ? WHERE `id` = ?";
        $stmt_complete_vac = mysqli_prepare($conDB, $sql_complete_vac);
        if (!$stmt_complete_vac) {
            throw new Exception(__("failed_to_mark_vacation_as_completed") . ": " . mysqli_error($conDB));
        }

        mysqli_stmt_bind_param($stmt_complete_vac, "si", $actual_return_date, $vacation_id);
        if (!mysqli_stmt_execute($stmt_complete_vac)) {
            throw new Exception(__("failed_to_update_vacation_status") . ": " . mysqli_stmt_error($stmt_complete_vac));
        }
        mysqli_stmt_close($stmt_complete_vac);

        $message = __("employee_marked_as_returned");

        if ($extra_days > 0) {
            $message .= " " . sprintf(__("extra_days_deducted_from_balance"), $extra_days);
        }

        send_json_response("Success!", $message, "success");
    } catch (Exception $e) {

        send_json_response("Error", $e->getMessage(), "error", 500);
    }
    exit;
}

// ================================================================
// --- [NEW] BLOCK TO HANDLE VACATION PAYMENT UPDATES ---
// ================================================================
elseif ($ajaxType == 'updateVacationPayments') {
    try {
        // Only HR Managers or Admins should be able to do this.
        // We assume the session check in all_applied_vac.php already handled this.

        $vacation_id = (int)($_POST['vacation_id'] ?? 0);
        $departure_date = trim($_POST['departure_date'] ?? '');
        $arrival_date = trim($_POST['arrival_date'] ?? '');
        $ticket_pay = (float)($_POST['ticket_pay'] ?? 0);
        $permit_fee = (float)($_POST['permit_fee'] ?? 0);



        if (empty($vacation_id)) {
            throw new Exception(__("vacation_id_is_missing"));
        }

        // Convert empty strings to NULL for date fields
        $departure_date_val = (!empty($departure_date) ? $departure_date : null);
        $arrival_date_val = (!empty($arrival_date) ? $arrival_date : null);



        $sql_pay = "UPDATE `emp_vacation` SET `departure_date` = ?, `arrival_date` = ?, `ticket_pay` = ?, `permit_fee` = ? WHERE `id` = ?";
        $stmt_pay = mysqli_prepare($conDB, $sql_pay);
        if (!$stmt_pay) {
            throw new Exception(__('database_prepare_error') . ": " . mysqli_error($conDB));
        }

        mysqli_stmt_bind_param($stmt_pay, "ssddi", $departure_date_val, $arrival_date_val, $ticket_pay, $permit_fee, $vacation_id);



        if (!mysqli_stmt_execute($stmt_pay)) {

            throw new Exception(__('database_prepare_error') . ": " . mysqli_stmt_error($stmt_pay));
        }

        $rows_affected = mysqli_stmt_affected_rows($stmt_pay);

        mysqli_stmt_close($stmt_pay);

        if ($rows_affected > 0) {
            // Check if this is an Annual Fly vacation and if all required fields are filled
            // Then update status to completed
            $check_complete_sql = "SELECT vac_type, fly_type, ticket_pay, permit_fee, overtime_hours, deduction_hours, emp_id FROM emp_vacation WHERE id = ?";
            $check_complete_stmt = mysqli_prepare($conDB, $check_complete_sql);
            if ($check_complete_stmt) {
                mysqli_stmt_bind_param($check_complete_stmt, "i", $vacation_id);
                mysqli_stmt_execute($check_complete_stmt);
                $check_res = mysqli_stmt_get_result($check_complete_stmt);
                $vac_data = mysqli_fetch_assoc($check_res);
                mysqli_stmt_close($check_complete_stmt);
                
                // For Annual Fly: ticket_pay AND permit_fee AND (overtime_hours OR deduction_hours) must all be filled
                if ($vac_data && $vac_data['vac_type'] === 'Fly' && $vac_data['fly_type'] === 'annual') {
                    $has_payment = ($vac_data['ticket_pay'] > 0 || $vac_data['permit_fee'] > 0);
                    $has_adjustment = ($vac_data['overtime_hours'] > 0 || $vac_data['deduction_hours'] > 0);
                    
                    if ($has_payment && $has_adjustment) {
                        // All required fields are filled - mark as completed
                        $complete_sql = "UPDATE emp_vacation SET current_status = 'completed' WHERE id = ?";
                        $complete_stmt = mysqli_prepare($conDB, $complete_sql);
                        if ($complete_stmt) {
                            mysqli_stmt_bind_param($complete_stmt, "i", $vacation_id);
                            mysqli_stmt_execute($complete_stmt);
                            mysqli_stmt_close($complete_stmt);
                        }
                        // Set employees.fly = 1 except for Encashment type
                        $vac_type_lower = strtolower($vac_data['vac_type'] ?? '');
                        if ($vac_type_lower !== 'encashed' && !empty($vac_data['emp_id'])) {
                            $stmtFly = mysqli_prepare($conDB, "UPDATE employees SET fly = 1 WHERE emp_id = ?");
                            if ($stmtFly) {
                                mysqli_stmt_bind_param($stmtFly, "s", $vac_data['emp_id']);
                                mysqli_stmt_execute($stmtFly);
                                mysqli_stmt_close($stmtFly);
                            }
                        }
                    }
                }
            }
            send_json_response("Success!", __("payment_details_have_been_updated"), "success");
        } else {
            send_json_response("Info", __("no_changes_were_made_to_the_payment_details"), "info");
        }
    } catch (Exception $e) {

        send_json_response("Error", $e->getMessage(), "error", 500);
    }
    exit;
}

// ================================================================
// --- [NEW] BLOCK TO HANDLE VACATION PAYROLL ADJUSTMENTS (POST-APPROVAL) ---
// ================================================================
elseif ($ajaxType == 'updateVacationAdjustments') {
    try {
        $vacation_id = (int)($_POST['vacation_id'] ?? 0);
        $overtime_hours = (float)($_POST['overtime_hours'] ?? 0);
        $deduction_hours = (float)($_POST['deduction_hours'] ?? 0);
        $deduction_days = (float)($_POST['deduction_days'] ?? 0);
        $other_earnings = (float)($_POST['other_earnings'] ?? 0);
        $payroll_note = trim($_POST['payroll_note'] ?? '');

        if (empty($vacation_id)) {
            throw new Exception(__("vacation_id_is_missing"));
        }

        // Basic validation for negative values
        if ($overtime_hours < 0 || $deduction_hours < 0 || $deduction_days < 0 || $other_earnings < 0) {
            throw new Exception(__("invalid_negative_values_not_allowed"));
        }

        // Ensure vacation exists and get employee salary info
        $exists_sql = "SELECT ev.id, ev.emp_id, es.basic, es.housing FROM emp_vacation ev 
                       LEFT JOIN emp_salary es ON ev.emp_id = es.emp_id AND es.status = 1
                       WHERE ev.id = ?";
        $exists_stmt = mysqli_prepare($conDB, $exists_sql);
        if (!$exists_stmt) {
            throw new Exception(__('database_prepare_error') . ": " . mysqli_error($conDB));
        }
        mysqli_stmt_bind_param($exists_stmt, "i", $vacation_id);
        mysqli_stmt_execute($exists_stmt);
        $exists_res = mysqli_stmt_get_result($exists_stmt);
        if (!$exists_res || mysqli_num_rows($exists_res) === 0) {
            mysqli_stmt_close($exists_stmt);
            throw new Exception(__("invalid_vacation_id"));
        }
        $vacation_row = mysqli_fetch_assoc($exists_res);
        mysqli_stmt_close($exists_stmt);

        // Get full salary base for calculation
        $salary_sql = "SELECT basic, housing, transport, food, misc, cashier, fuel, tel, guard, other 
                       FROM emp_salary WHERE emp_id = ? AND status = 1";
        $salary_stmt = mysqli_prepare($conDB, $salary_sql);
        if ($salary_stmt) {
            mysqli_stmt_bind_param($salary_stmt, "s", $vacation_row['emp_id']);
            mysqli_stmt_execute($salary_stmt);
            $salary_res = mysqli_stmt_get_result($salary_stmt);
            $salary_data = mysqli_fetch_assoc($salary_res);
            mysqli_stmt_close($salary_stmt);
        } else {
            $salary_data = ['basic' => 0, 'housing' => 0];
        }

        // Calculate salary base for adjustments (excluding calculated housing)
        $basic_salary = 0;
        $salary_base = 0;
        if ($salary_data) {
            $basic_salary = (float)($salary_data['basic'] ?? 0);
            $salary_base = $basic_salary +
                          (float)($salary_data['housing'] ?? 0) +
                          (float)($salary_data['transport'] ?? 0) +
                          (float)($salary_data['food'] ?? 0) +
                          (float)($salary_data['misc'] ?? 0) +
                          (float)($salary_data['cashier'] ?? 0) +
                          (float)($salary_data['fuel'] ?? 0) +
                          (float)($salary_data['tel'] ?? 0) +
                          (float)($salary_data['guard'] ?? 0) +
                          (float)($salary_data['other'] ?? 0);
        }

        // Calculate overtime and deduction amounts using EOS formula
        // OVERTIME CALCULATION (matching EOS file and frontend):
        // per-hour overtime rate = (basic/240)/2 + (salary_base/240)
        // This gives higher overtime rate as per labor law requirements
        $overtime_hourly_rate = $basic_salary > 0 ? (($basic_salary / 240) / 2) + ($salary_base / 240) : 0;
        
        // DEDUCTION CALCULATION (standard hourly/daily rate):
        $daily_rate_deduction = $salary_base > 0 ? $salary_base / 30 : 0;
        $hourly_rate_deduction = $daily_rate_deduction > 0 ? $daily_rate_deduction / 8 : 0;
        
        $overtime_amount = ($overtime_hours * $overtime_hourly_rate);
        $deduction_amount = ($deduction_hours * $hourly_rate_deduction) + ($deduction_days * $daily_rate_deduction);
        
        // DEBUG: Log calculation details
        error_log("OVERTIME CALC - Emp: {$vacation_row['emp_id']}, Basic: {$basic_salary}, Total: {$salary_base}, Hours: {$overtime_hours}, Rate: {$overtime_hourly_rate}, Amount: {$overtime_amount}");
        
        // Update adjustments with calculation fields
        $sql_adj = "UPDATE emp_vacation SET overtime_hours = ?, deduction_hours = ?, deduction_days = ?, other_earnings = ?, payroll_note = ?, overtime_amount = ?, deduction_amount = ? WHERE id = ?";
        $stmt_adj = mysqli_prepare($conDB, $sql_adj);
        if (!$stmt_adj) {
            throw new Exception(__('database_prepare_error') . ": " . mysqli_error($conDB));
        }
        mysqli_stmt_bind_param($stmt_adj, "ddddsddi", $overtime_hours, $deduction_hours, $deduction_days, $other_earnings, $payroll_note, $overtime_amount, $deduction_amount, $vacation_id);
        if (!mysqli_stmt_execute($stmt_adj)) {
            $err = mysqli_stmt_error($stmt_adj);
            mysqli_stmt_close($stmt_adj);
            throw new Exception(__('database_prepare_error') . ": " . $err);
        }
        $rows = mysqli_stmt_affected_rows($stmt_adj);
        mysqli_stmt_close($stmt_adj);

        if ($rows > 0) {
            // Add calculation details to response for debugging
            $calc_details = [
                'emp_id' => $vacation_row['emp_id'],
                'basic_salary' => $basic_salary,
                'total_salary' => $salary_base,
                'overtime_hours' => $overtime_hours,
                'overtime_hourly_rate' => round($overtime_hourly_rate, 4),
                'overtime_amount' => round($overtime_amount, 2),
                'deduction_hours' => $deduction_hours,
                'deduction_days' => $deduction_days,
                'hourly_rate_deduction' => round($hourly_rate_deduction, 4),
                'daily_rate_deduction' => round($daily_rate_deduction, 2),
                'deduction_amount' => round($deduction_amount, 2),
                'formula' => "overtime_rate = (basic/{$basic_salary}/240/2) + (total/{$salary_base}/240) = " . round($overtime_hourly_rate, 4)
            ];
            
            // Check vacation type and decide completion + balance update rules
            $check_complete_sql = "SELECT vac_type, fly_type, ticket_pay, permit_fee, overtime_hours, deduction_hours FROM emp_vacation WHERE id = ?";
            $check_complete_stmt = mysqli_prepare($conDB, $check_complete_sql);
            if ($check_complete_stmt) {
                mysqli_stmt_bind_param($check_complete_stmt, "i", $vacation_id);
                mysqli_stmt_execute($check_complete_stmt);
                $check_res = mysqli_stmt_get_result($check_complete_stmt);
                $vac_data = mysqli_fetch_assoc($check_res);
                mysqli_stmt_close($check_complete_stmt);
                
                if ($vac_data) {
                    $did_complete = false;
                    $is_fly = ($vac_data['vac_type'] === 'Fly');
                    $is_annual = (strtolower($vac_data['fly_type']) === 'annual');
                    $is_emergency = (strtolower($vac_data['fly_type']) === 'emergency');

                    $has_payment = ((float)($vac_data['ticket_pay'] ?? 0) > 0 || (float)($vac_data['permit_fee'] ?? 0) > 0);
                    $has_adjustment = ((float)($vac_data['overtime_hours'] ?? 0) > 0 || (float)($vac_data['deduction_hours'] ?? 0) > 0);

                    // Rule 1: Local | Annual vacation -> Booking button hidden; complete on adjustments update
                    // CRITICAL: review stays 'A' until employee rejoins (review = 'C' only on rejoin)
                    if (!$is_fly && $is_annual && $has_adjustment) {
                        $complete_sql = "UPDATE emp_vacation SET current_status = 'completed' WHERE id = ?";
                        $complete_stmt = mysqli_prepare($conDB, $complete_sql);
                        if ($complete_stmt) {
                            mysqli_stmt_bind_param($complete_stmt, "i", $vacation_id);
                            mysqli_stmt_execute($complete_stmt);
                            mysqli_stmt_close($complete_stmt);
                        }
                        $did_complete = true;
                    }

                    // Rule 2: Fly | Annual vacation -> complete when booking (payment) AND adjustments are updated
                    // CRITICAL: review stays 'A' until employee rejoins (review = 'C' only on rejoin)
                    if ($is_fly && $is_annual && $has_payment && $has_adjustment) {
                        $complete_sql = "UPDATE emp_vacation SET current_status = 'completed' WHERE id = ?";
                        $complete_stmt = mysqli_prepare($conDB, $complete_sql);
                        if ($complete_stmt) {
                            mysqli_stmt_bind_param($complete_stmt, "i", $vacation_id);
                            mysqli_stmt_execute($complete_stmt);
                            mysqli_stmt_close($complete_stmt);
                        }
                        $did_complete = true;
                    }

                    // Rule 3: Fly | Emergency vacation -> Booking button hidden; complete on adjustments; deduct annual balance once
                    // CRITICAL: review stays 'A' until employee rejoins (review = 'C' only on rejoin)
                    if ($is_fly && $is_emergency && $has_adjustment) {
                        // Mark completed
                        $complete_sql = "UPDATE emp_vacation SET current_status = 'completed' WHERE id = ?";
                        $complete_stmt = mysqli_prepare($conDB, $complete_sql);
                        if ($complete_stmt) {
                            mysqli_stmt_bind_param($complete_stmt, "i", $vacation_id);
                            mysqli_stmt_execute($complete_stmt);
                            mysqli_stmt_close($complete_stmt);
                        }
                        $did_complete = true;

                        // Deduct annual balance if not already linked to this vacation
                        $bal_chk_sql = "SELECT id FROM emp_vacation_balance WHERE vac_id = ? LIMIT 1";
                        $bal_chk_stmt = mysqli_prepare($conDB, $bal_chk_sql);
                        if ($bal_chk_stmt) {
                            mysqli_stmt_bind_param($bal_chk_stmt, "i", $vacation_id);
                            mysqli_stmt_execute($bal_chk_stmt);
                            $bal_chk_res = mysqli_stmt_get_result($bal_chk_stmt);
                            $has_balance_link = ($bal_chk_res && mysqli_fetch_assoc($bal_chk_res));
                            if ($bal_chk_res) mysqli_free_result($bal_chk_res);
                            mysqli_stmt_close($bal_chk_stmt);

                            if (!$has_balance_link && function_exists('update_vacation_balance_on_approval')) {
                                // Update balance once for emergency vacations using annual balance
                                update_vacation_balance_on_approval($conDB, $vacation_id);
                            }
                        }
                    }

                    // If completed in any branch, set employees.fly = 1 unless Encashment
                    if ($did_complete && !empty($vacation_row['emp_id'])) {
                        $vac_type_lower = strtolower($vac_data['vac_type'] ?? '');
                        if ($vac_type_lower !== 'encashed') {
                            $stmtFly = mysqli_prepare($conDB, "UPDATE employees SET fly = 1 WHERE emp_id = ?");
                            if ($stmtFly) {
                                mysqli_stmt_bind_param($stmtFly, "s", $vacation_row['emp_id']);
                                mysqli_stmt_execute($stmtFly);
                                mysqli_stmt_close($stmtFly);
                            }
                        }
                    }
                }
            }
            send_json_response("Success!", __("payroll_adjustments_saved"), "success", 200, $calc_details);
        } else {
            send_json_response("Info", __("no_changes_were_made_to_the_adjustments"), "info");
        }
    } catch (Exception $e) {
        send_json_response("Error", $e->getMessage(), "error", 500);
    }
    exit;
}


// ================================================================
// --- GET VACATION DETAILS (for payment modal) ---
// ================================================================
elseif ($ajaxType == 'getVacationDetails') {
    try {
        $vacation_id = (int)($_POST['vacation_id'] ?? 0);

        if (empty($vacation_id)) {
            throw new Exception(__("vacation_id_is_missing"));
        }

        $sql = "SELECT id, departure_date, arrival_date, ticket_pay, permit_fee, encashment_amount, vac_type, emp_id, request_inv_no FROM emp_vacation WHERE id = ?";
        $stmt = mysqli_prepare($conDB, $sql);
        if (!$stmt) {
            throw new Exception(__('database_prepare_error') . ": " . mysqli_error($conDB));
        }

        mysqli_stmt_bind_param($stmt, "i", $vacation_id);

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception(__('database_prepare_error') . ": " . mysqli_stmt_error($stmt));
        }

        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($row) {
            // Get asset checker ID from request_approvers table if exists
            // Asset checker is an approver from IT (6), Admin (1), or Transport (17) departments
            $asset_checker_emp_id = null;
            if (!empty($row['request_inv_no'])) {
                // First, get the vacation_request type ID
                $type_query = mysqli_query($conDB, "SELECT id FROM approval_request_types WHERE type_name = 'vacation_request' LIMIT 1");
                $type_id = null;
                if ($type_query && mysqli_num_rows($type_query) > 0) {
                    $type_row = mysqli_fetch_assoc($type_query);
                    $type_id = $type_row['id'];
                    mysqli_free_result($type_query);
                }

                // If we found the type, look for asset checker
                if ($type_id) {
                    $checker_sql = "SELECT ra.approver_id, e.dept
                                   FROM request_approvers ra
                                   JOIN employees e ON ra.approver_id = e.emp_id
                                   WHERE ra.request_inv_no = ? AND ra.request_type_id = ?
                                   AND e.dept IN (1, 6, 17)
                                   ORDER BY ra.approval_level DESC
                                   LIMIT 1";
                    $checker_stmt = mysqli_prepare($conDB, $checker_sql);
                    if ($checker_stmt) {
                        mysqli_stmt_bind_param($checker_stmt, "si", $row['request_inv_no'], $type_id);
                        if (mysqli_stmt_execute($checker_stmt)) {
                            $checker_result = mysqli_stmt_get_result($checker_stmt);
                            if ($checker_row = mysqli_fetch_assoc($checker_result)) {
                                $asset_checker_emp_id = $checker_row['approver_id'];
                            }
                            if ($checker_result) mysqli_free_result($checker_result);
                        }
                        mysqli_stmt_close($checker_stmt);
                    }
                }
            }

            // Get current user's emp_type from admin_login table
            $current_user_emp_type = null;
            $current_empid = $_SESSION['empid'] ?? null;
            if ($current_empid) {
                $emp_type_sql = "SELECT emp_type FROM admin_login WHERE emp_id = ?";
                $emp_type_stmt = mysqli_prepare($conDB, $emp_type_sql);
                if ($emp_type_stmt) {
                    mysqli_stmt_bind_param($emp_type_stmt, "i", $current_empid);
                    if (mysqli_stmt_execute($emp_type_stmt)) {
                        $emp_type_result = mysqli_stmt_get_result($emp_type_stmt);
                        if ($emp_type_row = mysqli_fetch_assoc($emp_type_result)) {
                            $current_user_emp_type = $emp_type_row['emp_type'];
                        }
                        if ($emp_type_result) mysqli_free_result($emp_type_result);
                    }
                    mysqli_stmt_close($emp_type_stmt);
                }
            }

            echo json_encode([
                'status' => 200,
                'departure_date' => $row['departure_date'] ?? '',
                'arrival_date' => $row['arrival_date'] ?? '',
                'ticket_pay' => (float)($row['ticket_pay'] ?? 0),
                'permit_fee' => (float)($row['permit_fee'] ?? 0),
                'encashment_amount' => (float)($row['encashment_amount'] ?? 0),
                'vac_type' => $row['vac_type'] ?? '',
                'emp_id' => $row['emp_id'] ?? '',
                'asset_checker_emp_id' => $asset_checker_emp_id,
                'current_user_emp_type' => $current_user_emp_type
            ]);
        } else {
            echo json_encode(['status' => 404, 'message' => 'Vacation not found']);
        }
    } catch (Exception $e) {

        echo json_encode(['status' => 500, 'message' => $e->getMessage()]);
    }
    exit;
}

// --- [NEW] BLOCK TO HANDLE FETCHING EMPLOYEE ASSIGNED ASSETS ---
// ================================================================
elseif ($ajaxType == 'getEmployeeAssignedAssets') {
    try {
        $emp_id = (int)($_POST['emp_id'] ?? 0);

        if (empty($emp_id)) {
            throw new Exception(__("employee_id_is_missing"));
        }

        // Fetch all assigned assets for the employee (status = 'Assigned' and not yet returned)
        $sql = "SELECT id, asset_id, serial_number, description, assigned_date, status 
                FROM employee_assets 
                WHERE emp_id = ? AND status = 'Assigned'
                ORDER BY assigned_date DESC";
        
        $stmt = mysqli_prepare($conDB, $sql);
        if (!$stmt) {
            throw new Exception(__('database_prepare_error') . ": " . mysqli_error($conDB));
        }

        mysqli_stmt_bind_param($stmt, "i", $emp_id);

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception(__('database_prepare_error') . ": " . mysqli_stmt_error($stmt));
        }

        $result = mysqli_stmt_get_result($stmt);
        $assets = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $assets[] = [
                'id' => $row['id'],
                'asset_id' => $row['asset_id'],
                'serial_number' => $row['serial_number'] ?? '',
                'description' => $row['description'] ?? '',
                'assigned_date' => $row['assigned_date'],
                'status' => $row['status']
            ];
        }

        mysqli_stmt_close($stmt);

        echo json_encode([
            'status' => 200,
            'assets' => $assets,
            'total' => count($assets)
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 500, 'message' => $e->getMessage()]);
    }
    exit;
}

// --- [NEW] BLOCK TO HANDLE FETCHING TRAVELER DETAILS ---
// ================================================================
elseif ($ajaxType == 'getTravelerDetails') {
    try {
        $vacation_id = (int)($_POST['vacation_id'] ?? 0);

        if (empty($vacation_id)) {
            throw new Exception(__("vacation_id_is_missing"));
        }

        // Fetch vacation and employee details including passport info
        $sql = "SELECT 
                    v.id,
                    v.emp_id,
                    v.start_date,
                    v.return_date,
                    v.departure_date,
                    v.arrival_date,
                    v.request_inv_no,
                    v.vac_type,
                    v.fly_type,
                    e.name as employee_name,
                    e.passport_number,
                    e.passport_exp,
                    c.name as country_name
                FROM emp_vacation v
                JOIN employees e ON v.emp_id = e.emp_id
                LEFT JOIN countries c ON e.country = c.id
                WHERE v.id = ?";

        $stmt = mysqli_prepare($conDB, $sql);
        if (!$stmt) {
            throw new Exception(__('database_prepare_error') . ": " . mysqli_error($conDB));
        }

        mysqli_stmt_bind_param($stmt, 'i', $vacation_id);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception(__('database_prepare_error') . ": " . mysqli_stmt_error($stmt));
        }

        $result = mysqli_stmt_get_result($stmt);
        $vacation = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$vacation) {
            throw new Exception(__("vacation_request_not_found"));
        }

        // Format dates for display and include raw values for client-side validation
        // Fetch existing passport document if present (docu_typ contains 'passport')
        $passport_doc = null;
        $passport_sql = "SELECT id, docu_typ, path, docu_ext FROM emp_docu WHERE emp_id = ? AND (LOWER(docu_typ) LIKE '%passport%' OR LOWER(path) LIKE '%passport%') ORDER BY id DESC LIMIT 1";
        if ($ps = mysqli_prepare($conDB, $passport_sql)) {
            mysqli_stmt_bind_param($ps, 's', $vacation['emp_id']);
            if (mysqli_stmt_execute($ps)) {
                $pr = mysqli_stmt_get_result($ps);
                $passport_doc = mysqli_fetch_assoc($pr);
            }
            mysqli_stmt_close($ps);
        }
        $passport_doc_url = '';
        $passport_doc_ext = '';
        $passport_doc_is_image = false;
        if ($passport_doc && !empty($passport_doc['path'])) {
            $passport_doc_url = './assets/emp_documents/' . $passport_doc['path'];
            $passport_doc_ext = strtolower($passport_doc['docu_ext']);
            $passport_doc_is_image = in_array($passport_doc_ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
        }
        $data = [
            'emp_id' => $vacation['emp_id'],
            'employee_name' => $vacation['employee_name'],
            'passport_number' => !empty($vacation['passport_number']) ? $vacation['passport_number'] : 'Not Provided',
            'passport_number_raw' => $vacation['passport_number'],
            'passport_exp' => !empty($vacation['passport_exp']) ? date('d M Y', strtotime($vacation['passport_exp'])) : 'Not Provided',
            'passport_exp_raw' => $vacation['passport_exp'],
            'country_name' => !empty($vacation['country_name']) ? $vacation['country_name'] : 'Not Specified',
            'departure_date' => !empty($vacation['departure_date']) ? date('d M Y', strtotime($vacation['departure_date'])) : 'Not Provided',
            'departure_date_raw' => $vacation['departure_date'],
            'arrival_date' => !empty($vacation['arrival_date']) ? date('d M Y', strtotime($vacation['arrival_date'])) : 'Not Provided',
            'start_date' => !empty($vacation['start_date']) ? date('d M Y', strtotime($vacation['start_date'])) : 'Not Provided',
            'return_date' => !empty($vacation['return_date']) ? date('d M Y', strtotime($vacation['return_date'])) : 'Not Provided',
            'request_inv_no' => $vacation['request_inv_no'],
            'vac_type' => $vacation['vac_type'],
            'fly_type' => $vacation['fly_type'],
            'passport_doc_url' => $passport_doc_url,
            'passport_doc_ext' => $passport_doc_ext,
            'passport_doc_is_image' => $passport_doc_is_image
        ];

        echo json_encode([
            'type' => 'success',
            'message' => __('traveler_details_fetched_successfully'),
            'data' => $data
        ]);
    } catch (Exception $e) {

        echo json_encode([
            'type' => 'error',
            'message' => $e->getMessage(),
            'data' => null
        ]);
    }
    exit;
}

// --- [NEW] BLOCK TO HANDLE SENDING TRAVEL COMPANY EMAIL ---
// ================================================================
elseif ($ajaxType == 'sendTravelEmail') {
    try {
        $vacation_id = (int)($_POST['vacation_id'] ?? 0);

        if (empty($vacation_id)) {
            throw new Exception(__("vacation_id_is_missing"));
        }

        // Prepare passport file variables (can be new upload or existing stored doc)
        $passport_file_path = '';
        $passport_file_name = '';
        $stored_doc_used = false;

        $has_new_upload = (isset($_FILES['passport_file']) && $_FILES['passport_file']['error'] === UPLOAD_ERR_OK);
        if ($has_new_upload) {
            $passport_file = $_FILES['passport_file'];
            $allowed_types = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
            $max_size = 5 * 1024 * 1024; // 5MB
            if ($passport_file['size'] > $max_size) {
                throw new Exception("Passport file is too large. Maximum size is 5MB.");
            }
            $file_type = mime_content_type($passport_file['tmp_name']);
            if (!in_array($file_type, $allowed_types)) {
                throw new Exception(__("invalid_file_type_only_pdf_jpg_and_png_files_are_allowed"));
            }
            $file_extension = strtolower(pathinfo($passport_file['name'], PATHINFO_EXTENSION));
            // Persist the uploaded file into employee documents (emp_docu) so it can be reused later
            // Build filename: EMPID_passport_TIMESTAMP.ext
            // Need employee id first (will fetch vacation below; temporarily store ext and tmp path)
        } else {
            // No new upload; will attempt to use existing stored passport document after loading vacation
        }

        // Fetch vacation and employee details including passport info
        $sql = "SELECT 
                    v.*, 
                    e.name as employee_name,
                    e.passport_number,
                    e.passport_exp,
                    e.email as employee_email,
                    c.name as country_name
                FROM emp_vacation v
                JOIN employees e ON v.emp_id = e.emp_id
                LEFT JOIN countries c ON e.country = c.id
                WHERE v.id = ?";

        $stmt = mysqli_prepare($conDB, $sql);
        if (!$stmt) {
            throw new Exception(__('database_prepare_error') . ": " . mysqli_error($conDB));
        }

        mysqli_stmt_bind_param($stmt, 'i', $vacation_id);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception(__('database_prepare_error') . ": " . mysqli_stmt_error($stmt));
        }

        $result = mysqli_stmt_get_result($stmt);
        $vacation = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$vacation) {
            throw new Exception(__("vacation_request_not_found"));
        }

        // Validate this is an annual fly vacation
        if ($vacation['vac_type'] !== 'Fly' || $vacation['fly_type'] !== 'annual') {
            throw new Exception(__("email_can_only_be_sent_for_annual_fly_vacations"));
        }

        // Validate vacation is approved
        if ($vacation['current_status'] !== 'approved') {
            throw new Exception(__("vacation_must_be_approved_before_sending_travel_email"));
        }

        // Check if flight dates are available
        if (empty($vacation['departure_date']) || empty($vacation['arrival_date'])) {
            throw new Exception(__("flight_dates_departure_and_arrival_are_required"));
        }

        // Check if email has already been sent
        if (!empty($vacation['travel_email_sent']) && $vacation['travel_email_sent'] == 1) {
            throw new Exception(__("travel_email_has_already_been_sent_for_this_vacation"));
        }

        // Handle passport file persistence or fallback to existing stored doc
        if ($has_new_upload) {
            $emp_id_for_passport = $vacation['emp_id'];
            $file_extension = strtolower(pathinfo($_FILES['passport_file']['name'], PATHINFO_EXTENSION));
            $new_filename = $emp_id_for_passport . '_passport_' . time() . '.' . $file_extension;
            $destination_dir = __DIR__ . '/../../assets/emp_documents/';
            if (!is_dir($destination_dir)) {
                @mkdir($destination_dir, 0775, true);
            }
            $destination_path = $destination_dir . $new_filename;
            if (!move_uploaded_file($_FILES['passport_file']['tmp_name'], $destination_path)) {
                throw new Exception(__("failed_to_store_passport_document"));
            }
            // Upsert into emp_docu
            $existing_passport_doc = null;
            $chk_sql = "SELECT id FROM emp_docu WHERE emp_id = ? AND LOWER(docu_typ) LIKE '%passport%' ORDER BY id DESC LIMIT 1";
            if ($cs = mysqli_prepare($conDB, $chk_sql)) {
                mysqli_stmt_bind_param($cs, 's', $emp_id_for_passport);
                mysqli_stmt_execute($cs);
                $cr = mysqli_stmt_get_result($cs);
                $existing_passport_doc = mysqli_fetch_assoc($cr);
                mysqli_stmt_close($cs);
            }
            if ($existing_passport_doc) {
                $upd_sql = "UPDATE emp_docu SET path = ?, docu_ext = ?, created_at = NOW() WHERE id = ?";
                if ($us = mysqli_prepare($conDB, $upd_sql)) {
                    mysqli_stmt_bind_param($us, 'ssi', $new_filename, $file_extension, $existing_passport_doc['id']);
                    mysqli_stmt_execute($us);
                    mysqli_stmt_close($us);
                }
            } else {
                $ins_sql = "INSERT INTO emp_docu (emp_id, docu_typ, path, docu_ext, pgid, status, created_at) VALUES (?, 'passport', ?, ?, 0, 'A', NOW())";
                if ($is = mysqli_prepare($conDB, $ins_sql)) {
                    mysqli_stmt_bind_param($is, 'sss', $emp_id_for_passport, $new_filename, $file_extension);
                    mysqli_stmt_execute($is);
                    mysqli_stmt_close($is);
                }
            }
            $passport_file_path = $destination_path; // absolute path for attachment
            $passport_file_name = $new_filename;
        } else {
            // Attempt to load existing passport document
            $existing_sql = "SELECT path, docu_ext FROM emp_docu WHERE emp_id = ? AND LOWER(docu_typ) LIKE '%passport%' ORDER BY id DESC LIMIT 1";
            if ($es = mysqli_prepare($conDB, $existing_sql)) {
                mysqli_stmt_bind_param($es, 's', $vacation['emp_id']);
                mysqli_stmt_execute($es);
                $er = mysqli_stmt_get_result($es);
                $existing_passport = mysqli_fetch_assoc($er);
                mysqli_stmt_close($es);
                if ($existing_passport && !empty($existing_passport['path'])) {
                    $passport_file_name = $existing_passport['path'];
                    $passport_file_path = __DIR__ . '/../../assets/emp_documents/' . $passport_file_name;
                    $stored_doc_used = true;
                }
            }
            if (empty($passport_file_path) || !file_exists($passport_file_path)) {
                throw new Exception(__("passport_copy_is_required_please_attach_a_passport_file"));
            }
        }

        // Get GR Officer email for CC
        $gr_officer_email = get_setting($conDB, 'gr_officer_email');
        if (empty($gr_officer_email)) {
            // Try to get from admin_login table where user_type contains 'gr_officer'
            // $gr_query = mysqli_query($conDB, "SELECT email FROM admin_login WHERE user_type LIKE '%gr_officer%' AND email IS NOT NULL AND email != '' LIMIT 1");
            $gr_query = mysqli_query($conDB, "SELECT email FROM admin_login WHERE user_type LIKE '%hr_payroll%' AND email IS NOT NULL AND email != '' LIMIT 1");
            if ($gr_query && $gr_row = mysqli_fetch_assoc($gr_query)) {
                $gr_officer_email = $gr_row['email'];
            }
            if ($gr_query) mysqli_free_result($gr_query);
        }

        // Send email to traveling company with CC to GR Officer and passport attachment
        require_once __DIR__ . '/../helper_functions.php';

        $email_sent = send_travel_company_email(
            $conDB,
            $vacation['employee_name'],
            $vacation['passport_number'],
            $vacation['passport_exp'],
            $vacation['country_name'],
            $vacation['departure_date'],
            $vacation['arrival_date'],
            $vacation['request_inv_no'],
            $gr_officer_email, // CC to GR Officer
            $passport_file_path, // Passport file path
            $passport_file_name  // Passport file name
        );

        if (!$email_sent) {
            throw new Exception(__("error_sending_travel_email", "Failed to send email to travel company. Please check SMTP settings and email configuration."));
        }

        // Update database to mark email as sent (but DO NOT mark as completed yet)
        // Status will be marked as completed only when payment AND overtime/deduction are entered
        $update_sql = "UPDATE `emp_vacation` SET `travel_email_sent` = 1 WHERE `id` = ?";
        $update_stmt = mysqli_prepare($conDB, $update_sql);
        if ($update_stmt) {
            mysqli_stmt_bind_param($update_stmt, 'i', $vacation_id);
            mysqli_stmt_execute($update_stmt);
            mysqli_stmt_close($update_stmt);
        }

        // Log the action in status history
        $status_note = __("travel_company_email_sent_to_traveling_company");
        if (!empty($gr_officer_email)) {
            $status_note .= " " . __("cc:_hr");
        }
        if ($stored_doc_used) {
            $status_note .= " (".__("existing_passport_doc_used").")";
        } else {
            $status_note .= " (".__("new_passport_doc_uploaded").")";
        }

        $status_sql = "INSERT INTO `smt_request_status` 
                       (`inv_no`, `status`, `note`, `emp_name`, `created_at`) 
                       VALUES (?, 'email_sent', ?, ?, NOW())";
        $status_stmt = mysqli_prepare($conDB, $status_sql);
        if ($status_stmt) {
            $current_user = $_SESSION['username'] ?? 'System';
            mysqli_stmt_bind_param($status_stmt, 'sss', $vacation['request_inv_no'], $status_note, $current_user);
            mysqli_stmt_execute($status_stmt);
            mysqli_stmt_close($status_stmt);
        }


        send_json_response(
            __('success'),
            // "Travel information has been sent to the traveling company" . (!empty($gr_officer_email) ? " with CC to GR Officer." : "."), 
            __("travel_information_has_been_sent_to_the_traveling_company") . (!empty($gr_officer_email) ? " " . __("with_cc_to_hr") : "."),
            "success"
        );
    } catch (Exception $e) {

        send_json_response(__("error"), $e->getMessage(), "error", 500);
    }
    exit;
}

// --- [NEW] BLOCK TO HANDLE REPLACING STORED PASSPORT DOCUMENT ONLY ---
// Allows updating the employee's passport copy before sending email
elseif ($ajaxType == 'replacePassportDoc') {
    try {
        $emp_id = trim($_POST['emp_id'] ?? '');
        if (empty($emp_id)) {
            throw new Exception(__('employee_id_missing'));
        }
        if (!isset($_FILES['passport_file']) || $_FILES['passport_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception(__('no_passport_file_uploaded'));
        }
        $file = $_FILES['passport_file'];
        $allowed_types = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
        $max_size = 5 * 1024 * 1024;
        if ($file['size'] > $max_size) {
            throw new Exception(__('upload_size_limit_5mb_validation'));
        }
        $mime = mime_content_type($file['tmp_name']);
        if (!in_array($mime, $allowed_types)) {
            throw new Exception(__('invalid_file_type_only_pdf_jpg_and_png_files_are_allowed'));
        }
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $new_filename = $emp_id . '_passport_' . time() . '.' . $ext;
        $destination_dir = __DIR__ . '/../../assets/emp_documents/';
        if (!is_dir($destination_dir)) {
            @mkdir($destination_dir, 0775, true);
        }
        $destination_path = $destination_dir . $new_filename;
        if (!move_uploaded_file($file['tmp_name'], $destination_path)) {
            throw new Exception(__('failed_moving_uploaded_file'));
        }
        // Upsert record
        $existing_passport_doc = null;
        $chk_sql = "SELECT id FROM emp_docu WHERE emp_id = ? AND LOWER(docu_typ) LIKE '%passport%' ORDER BY id DESC LIMIT 1";
        if ($cs = mysqli_prepare($conDB, $chk_sql)) {
            mysqli_stmt_bind_param($cs, 's', $emp_id);
            mysqli_stmt_execute($cs);
            $cr = mysqli_stmt_get_result($cs);
            $existing_passport_doc = mysqli_fetch_assoc($cr);
            mysqli_stmt_close($cs);
        }
        if ($existing_passport_doc) {
            $upd_sql = "UPDATE emp_docu SET path = ?, docu_ext = ?, created_at = NOW() WHERE id = ?";
            if ($us = mysqli_prepare($conDB, $upd_sql)) {
                mysqli_stmt_bind_param($us, 'ssi', $new_filename, $ext, $existing_passport_doc['id']);
                mysqli_stmt_execute($us);
                mysqli_stmt_close($us);
            }
        } else {
            $ins_sql = "INSERT INTO emp_docu (emp_id, docu_typ, path, docu_ext, pgid, status, created_at) VALUES (?, 'passport', ?, ?, 0, 'A', NOW())";
            if ($is = mysqli_prepare($conDB, $ins_sql)) {
                mysqli_stmt_bind_param($is, 'sss', $emp_id, $new_filename, $ext);
                mysqli_stmt_execute($is);
                mysqli_stmt_close($is);
            }
        }
        echo json_encode([
            'type' => __('success'),
            'message' => __('passport_document_replaced_successfully'),
            'passport_doc_url' => './assets/emp_documents/' . $new_filename,
            'passport_doc_ext' => $ext,
            'passport_doc_is_image' => in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])
        ]);
    } catch (Exception $e) {
        $last_error = mysqli_error($conDB);
        throw new Exception(__('database_error_during_overlap_check') . $last_error);
        echo json_encode(['type' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}


// --- OTHER AJAX FUNCTIONS (These are duplicated in ajaxEmployee.php, but required for JS calls) ---
// --- We will keep them here to ensure JS calls to this file don't break ---

elseif ($ajaxType == 'unassign_asset') {
    try {
        if (empty($_POST['asset_record_id']) || empty($_POST['return_date']) || empty($_POST['return_status'])) {
            throw new Exception(__('required_fields_are_missing'));
        }

        $attachment_path = null;
        if (isset($_FILES['return_attachment']) && $_FILES['return_attachment']['error'] == UPLOAD_ERR_OK) {
            $uploadDir = "./../../assets/assets_return/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $fileExtension = pathinfo($_FILES['return_attachment']['name'], PATHINFO_EXTENSION);
            $fileName = "return_" . $_POST['asset_record_id'] . "_" . time() . '.' . $fileExtension;
            $targetPath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['return_attachment']['tmp_name'], $targetPath)) {
                $attachment_path = $targetPath;
            } else {
                throw new Exception(__('server_could_not_save_the_uploaded_file'));
            }
        }

        $stmt = $pdo->prepare(
            "UPDATE `employee_assets` SET 
                `status` = :return_status, 
                `return_date` = :return_date,
                `return_attachment` = :return_attachment
             WHERE `id` = :asset_record_id"
        );

        $stmt->execute([
            ':return_status' => $_POST['return_status'],
            ':return_date' => $_POST['return_date'],
            ':return_attachment' => $attachment_path,
            ':asset_record_id' => $_POST['asset_record_id']
        ]);

        if ($stmt->rowCount() > 0) {
            send_json_response(__('returned'), __('asset_has_been_marked_as_returned'), "success");
        } else {
            throw new Exception(__('could_not_update_the_asset_record_it_may_have_already_been_returned'));
        }
    } catch (Exception $e) {
        send_json_response(__('error'), $e->getMessage(), "error");
    }
    exit;
} elseif ($ajaxType == 'get_asset_types') {
    $stmt = mysqli_query($conDB, "SELECT `id`, `name` FROM `assets` ORDER BY `name` ASC");
    $assets = [];
    while ($row = mysqli_fetch_assoc($stmt)) {
        $assets[] = $row;
    }
    mysqli_free_result($stmt); // <-- FIX
    echo json_encode(['success' => true, 'assets' => $assets]);
    exit;
} elseif ($ajaxType == 'assign_asset') {
    try {
        if (empty($_POST['emp_id']) || empty($_POST['asset_id']) || empty($_POST['assigned_date'])) {
            throw new Exception(__('required_fields_are_missing'));
        }

        $stmt = $pdo->prepare(
            "INSERT INTO `employee_assets` (`emp_id`, `asset_id`, `serial_number`, `description`, `assigned_date`, `status`) 
             VALUES (:emp_id, :asset_id, :serial_number, :description, :assigned_date, 'Assigned')"
        );

        $stmt->execute([
            ':emp_id'         => $_POST['emp_id'],
            ':asset_id'       => $_POST['asset_id'],
            ':serial_number'  => $_POST['serial_number'],
            ':description'    => $_POST['description'],
            ':assigned_date'  => $_POST['assigned_date']
        ]);

        if ($stmt->rowCount() > 0) {
            send_json_response(__('assigned'), __('asset_has_been_assigned_successfully'), "success");
        } else {
            throw new Exception(__('failed_to_insert_the_asset_record'));
        }
    } catch (Exception $e) {
        send_json_response(__('error'), $e->getMessage(), "error");
    }
    exit;
} elseif ($ajaxType == 'avatar') {
    $data = $_POST['image'];
    $id = $_POST['id'];
    $emp_id = $_POST['emp_id'];
    $emptype = $_POST['emptype'];
    $emp_name = str_replace(' ', '', $_POST['emp_name']);
    list($type, $data) = explode(';', $data);
    list(, $data) = explode(',', $data);
    $data = base64_decode($data);
    $imageName = time() . '.png';
    $filepath = "./../../assets/emp_pics/";
    $filepathup = "./assets/emp_pics/";
    $imagenameu = $emp_id . "" . $id . "" . $emp_name . "" . $imageName;
    if (empty($data) || (isset($data['error']) && $data['error'] == UPLOAD_ERR_NO_FILE)) {
        echo "No Picture upload";
    } else {
        file_put_contents($filepath . $emp_id . "" . $id . "" . $emp_name . "" . $imageName, $data);
        if ($emptype == 'employee') {
            try {
                $stmt = $pdo->prepare("INSERT INTO `employee_temp_contants` (`emp_id`, `type`, `path`) VALUES (:emp_id, 'Profile Picture', :filepath)");
                $stmt->execute([':emp_id' => $emp_id, ':filepath' => $filepathup . $imagenameu]);
            } catch (Exception $e) {
                send_json_response(__('database_error'), __('the_catch_block_is_working_the_error_was') . ": " . $e->getMessage(), "error");
            }
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE `employees` SET `avatar` = :avatar WHERE `id` = :id AND `emp_id` = :emp_id");
                $stmt->execute([':avatar' => $filepathup . $imagenameu, ':id' => $id, ':emp_id' => $emp_id]);
            } catch (Exception $e) {
                send_json_response(__('database_error'), __('the_catch_block_is_working_the_error_was') . ": " . $e->getMessage(), "error");
            }
        }
        if ($stmt->rowCount() > 0) {
            send_json_response(__('success'), __('image_uploaded_successfully'), "success");
        } else {
            send_json_response(__('error'), __('no_changes_made_to_profile_picture'), "error");
        }
    }
} elseif ($ajaxType == 'add_social_links') {
    $emp_id_up = $_POST['emp_id'];
    $link_up = $_POST['link'];
    $social_id_up = $_POST['social_id'];
    $socquery = mysqli_query($conDB, "SELECT * FROM `social` WHERE `emp_id`='" . $emp_id_up . "' AND `social_id`='" . $social_id_up . "' ");
    $num_rows = mysqli_num_rows($socquery);
    mysqli_free_result($socquery); // <-- FIX
    if ($num_rows == 0) {
        $query = "INSERT INTO `social` (`emp_id`,`s_link`, `social_id`, `created_at`) VALUES ('" . $emp_id_up . "', '" . $link_up . "', '" . $social_id_up . "', '" . date('Y-m-d H:i:s') . "')";
        if (mysqli_query($conDB, $query)) {
            send_json_response(__('success'), __('this_social_link_has_been_added_successfully'), "success");
        } else {
            send_json_response(__('error'), __('failed_to_add_social_link'), "error");
        }
    } else {
        send_json_response(__('error'), __('this_social_media_already_exist'), "error");
    }
} elseif ($ajaxType == 'social_links') {
    $stmt = mysqli_query(
        $conDB,
        "SELECT * FROM `social_list` WHERE `id` NOT IN (
            SELECT `social_list`.`id` FROM `social_list`
            LEFT JOIN `social` ON `social`.`social_id` = `social_list`.`id`
            WHERE `social`.`emp_id`='" . $_POST['emp_id'] . "'
        )"
    );
    $section_name = [];
    while ($row = mysqli_fetch_assoc($stmt)) {
        $section_name[] = $row;
    }
    mysqli_free_result($stmt); // <-- FIX
    $data = [
        'data'      => $section_name,
        'status'    => 200
    ];
    echo json_encode($data);
} elseif ($ajaxType == 'add_portfolio') {
    $emp_id = $_POST['emp_id'];
    $title_up = $_POST['title'];
    $description_up = mysqli_real_escape_string($conDB, $_POST['description']);
    $filename_po = null; // Initialize
    if (file_exists($_FILES['file']['tmp_name']) || is_uploaded_file($_FILES['file']['tmp_name'])) {
        $uploadDir = "./../../assets/emp_documents/";
        $fileName = basename($_FILES['file']['name']);
        $tmp_name = $_FILES['file']['tmp_name'];
        $rand = rand(0000, 9999) . time();
        $file_ext = explode('.', $fileName);
        $file_ext_count = count($file_ext);
        $cnt = $file_ext_count - 1;
        $file_extension = $file_ext[$cnt];
        $filename_po = $id . strtoupper($title_up) . $rand . "." . $file_extension;
        $uploadFilePath = $uploadDir . $filename_po;
        move_uploaded_file($tmp_name, $uploadFilePath);
    }
    $sql = "INSERT INTO `portfolio` (`emp_id`, `title`, `description`, `attachment`, `created_at`) VALUES ('" . $emp_id . "', '" . $title_up . "', '" . $description_up . "', '" . $filename_po . "', '" . date('Y-m-d H:i:s') . "')";
    if (mysqli_query($conDB, $sql)) {
        send_json_response(__('success'), __('this_portfolio_has_been_added_successfully'), "success");
    } else {
        send_json_response(__('error'), __('record_not_added_because_there_are_some_error'), "error");
    }
} elseif ($ajaxType == 'id_iqama_update') {
    try {
        // BEFORE these lines can even run, or in the file you are including.
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
        // --- END DEBUGGING BLOCK ---
        include("./../../includes/Hijri_GregorianConvert.php");
        $DateConv = new Hijri_GregorianConvert;
        $format = "YYYY-MM-DD";
        if ($_POST['iqama_exp']) {
            $iqama_exp = mysqli_real_escape_string($conDB, $_POST['iqama_exp']);
            $iqama_exp_gup = $DateConv->HijriToGregorian($iqama_exp, $format);
            $iqama_exp_g = date("Y-m-d", strtotime($iqama_exp_gup));
        } else {
            $iqama_exp_g = mysqli_real_escape_string($conDB, $_POST['iqama_exp_g']);
            $iqama_exp = $DateConv->GregorianToHijri($iqama_exp_g, $format);
        }
        $stmt = $pdo->prepare("UPDATE `employees` SET `iqama_exp` = :iqama_exp, `iqama_exp_g` = :iqama_exp_g WHERE `id` = :id");
        $stmt->execute([':iqama_exp' => $iqama_exp, ':iqama_exp_g' => $iqama_exp_g, ':id' => $_POST['id']]);
        if ($stmt->rowCount() > 0) {
            send_json_response(__("updated"), __("this_record_has_been_updated_successfully"), "success");
        } else {
        send_json_response(__("error"), __("record_not_updated_because_there_are_some_error"), "error");
        }
    } catch (Exception $e) {
        send_json_response(__("database_error"), __("the_catch_block_is_working_the_error_was") . ": " . $e->getMessage(), "error");
    }
} elseif ($ajaxType == 'emp_doc_type') {
    $stmt = mysqli_query($conDB, "SELECT * FROM `docu_type` ORDER BY `duc_type` REGEXP '^[^A-Za-z]' ASC, `duc_type`");
    $sub_type = [];
    while ($row = mysqli_fetch_assoc($stmt)) {
        $sub_type[] = $row;
    }
    mysqli_free_result($stmt); // <-- FIX
    $data = [
        'data'      => $sub_type,
        'status'    => 200
    ];
    echo json_encode($data);
} elseif ($ajaxType == 'add_emp_document') {
    try {
        // Validate required inputs
        if (!isset($_POST['id'], $_POST['docu_typ'], $_POST['emp_id'], $_POST['emptype'])) {
            throw new Exception(__('missing_required_parameters'));
        }
        // Sanitize inputs
        $id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
        $docu_typ_up = $_POST['docu_typ'];
        $emp_id_up = filter_var($_POST['emp_id'], FILTER_SANITIZE_NUMBER_INT);
        $emptype = $_POST['emptype'];
        // File upload handling
        if (!isset($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
            throw new Exception(__('no_file_uploaded_or_upload_error'));
        }
        $uploadDir = "./../../assets/emp_documents/";
        $filepathup = "./assets/emp_documents/";
        $fileName = basename($_FILES['file']['name']);
        $tmp_name = $_FILES['file']['tmp_name'];
        // Validate file extension
        $file_ext = pathinfo($fileName, PATHINFO_EXTENSION);
        $allowed_extensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
        if (!in_array(strtolower($file_ext), $allowed_extensions)) {
            throw new Exception(__('invalid_file_type_allowed_types') . ': ' . implode(', ', $allowed_extensions));
        }
        // Generate unique filename
        $rand = rand(0000, 9999) . time();
        $filename_po = $id . strtoupper($docu_typ_up) . $rand . "." . $file_ext;
        $uploadFilePath = $uploadDir . $filename_po;

        // Move uploaded file
        if (!move_uploaded_file($tmp_name, $uploadFilePath)) {
            throw new Exception(__('failed_to_move_uploaded_file'));
        }
        // Begin transaction for multiple database operations
        $pdo->beginTransaction();
        if ($emptype == 'employee') {
            // Insert into employee_temp_contants
            $stmt1 = $pdo->prepare("INSERT INTO `employee_temp_contants` (`emp_id`, `type`, `path`) VALUES (:emp_id, 'Employee Documents', :filepath)");
            $stmt1->execute([':emp_id' => $emp_id_up, ':filepath' => $filepathup . $filename_po]);
            // Insert into emp_docu with status 'I'
            $stmt2 = $pdo->prepare("INSERT INTO `emp_docu` (`emp_id`, `docu_typ`, `path`, `docu_ext`, `pgid`, `status`) VALUES (:emp_id, :docu_typ, :filename, :ext, :pgid, 'I')");
            $stmt2->execute([':emp_id' => $emp_id_up, ':docu_typ' => $docu_typ_up, ':filename' => $filename_po, ':ext' => $file_ext, ':pgid' => $id]);
        } else {
            // Insert into emp_docu without status
            $stmt = $pdo->prepare("INSERT INTO `emp_docu` (`emp_id`, `docu_typ`, `path`, `docu_ext`, `pgid`) VALUES (:emp_id, :docu_typ, :filename, :ext, :pgid)");
            $stmt->execute([':emp_id' => $emp_id_up, ':docu_typ' => $docu_typ_up, ':filename' => $filename_po, ':ext' => $file_ext, ':pgid' => $id]);
        }
        // Commit transaction if all queries succeeded
        $pdo->commit();
        send_json_response(__("added"), __("record_has_been_added_successfully"), "success");
    } catch (PDOException $e) {
        // Rollback transaction on error
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        // Delete uploaded file if database operation failed
        if (isset($uploadFilePath) && file_exists($uploadFilePath)) {
            unlink($uploadFilePath);
        }
        send_json_response(__("database_error"), __("failed_to_add_record") . ": " . $e->getMessage(), "error");
    } catch (Exception $e) {
        // Delete uploaded file if validation failed
        if (isset($uploadFilePath) && file_exists($uploadFilePath)) {
            unlink($uploadFilePath);
        }
        send_json_response("Error", $e->getMessage(), "error");
    }
} elseif ($ajaxType == 'emp_temp_contannt') {
    $ckh_query = mysqli_query($conDB, "SELECT * FROM `employee_temp_contants` WHERE `status` = 'A' AND `emp_id` = '" . (int)$_POST['empid'] . "' AND `id` = '" . (int)$_POST['id'] . "' ");
    $datackh = mysqli_fetch_assoc($ckh_query);
    mysqli_free_result($ckh_query); // <-- FIX

    if ($_POST['notes'] == 'approve') {
        if ($datackh['type'] == 'Profile Picture') {
            mysqli_query($conDB, "UPDATE `employees` SET `avatar`='" . $datackH['path'] . "' WHERE `emp_id`='" . (int)$_POST['empid'] . "' ");
            mysqli_query($conDB, "UPDATE `employee_temp_contants` SET `status`='I', `notes` = 'approve' WHERE `emp_id`='" . (int)$_POST['empid'] . "' AND `id` = '" . (int)$_POST['id'] . "' ");
            send_json_response(__("approved"), __("record_has_been_approve_successfully"), "success");
        } elseif ($datackh['type'] == 'Employee Documents') {
            mysqli_query($conDB, "UPDATE `emp_docu` SET `status`='A' WHERE `emp_id`='" . (int)$_POST['empid'] . "' AND `pgid` = '" . (int)$_POST['id'] . "' "); // Corrected WHERE clause
            mysqli_query($conDB, "UPDATE `employee_temp_contants` SET `status`='I', `notes` = 'approve' WHERE `emp_id`='" . (int)$_POST['empid'] . "' AND `id` = '" . (int)$_POST['id'] . "' ");
            send_json_response(__("approved"), __("record_has_been_approve_successfully"), "success");
        } else {
            mysqli_query($conDB, "UPDATE `employees` SET `" . $datackh['type'] . "` ='" . $datackh['path'] . "' WHERE `emp_id`='" . (int)$_POST['empid'] . "'"); // Used $datackh['type']
            mysqli_query($conDB, "UPDATE `employee_temp_contants` SET `status`='I', `notes` = 'approve' WHERE `emp_id`='" . (int)$_POST['empid'] . "' AND `id` = '" . (int)$_POST['id'] . "' ");
            send_json_response(__("approved"), __("record_has_been_approve_successfully"), "success");
        }
    } else {
        mysqli_query($conDB, "UPDATE `employee_temp_contants` SET `status`='I', `notes` = '" . $_POST['notes'] . "' WHERE `emp_id`='" . (int)$_POST['empid'] . "' AND `id` = '" . (int)$_POST['id'] . "' ");
        send_json_response(__("rejected"), __("record_not_approve"), "error");
    }
} elseif ($ajaxType == "bank_list") {
    $stmt = mysqli_query($conDB, "SELECT * FROM `bank_list` ORDER BY `name` REGEXP '^[^A-Za-z]' ASC, `name`");
    $name = [];
    while ($row = mysqli_fetch_assoc($stmt)) {
        $name[] = $row;
    }
    mysqli_free_result($stmt); // <-- FIX
    $data = [
        'data'      => $name,
        'status'    => 200
    ];
    echo json_encode($data);
} elseif ($ajaxType == "emp_edit_contannt") {
    $sql = "INSERT INTO `employee_temp_contants` (`emp_id`, `type`, `path`) VALUES ('" . (int)$_POST['empid'] . "', '" . $_POST['edit_contant_check'] . "', '" . $_POST[$_POST['edit_contant_check']] . "')";
    if (mysqli_query($conDB, $sql)) {
        send_json_response(__("success"), __("record_has_been_added_successfully"), "success");
    } else {
        send_json_response(__("error"), __("record_not_added_due_to_error"), "error");
    }
} elseif ($ajaxType == "add_note") {
    $stmt = $pdo->prepare("INSERT INTO `emp_notice` (`emp_id`, `note`, `created_at`) VALUES (:emp_id, :note, :created_at)");
    $dataPost = [
        ':emp_id' => $_POST['empid'],
        ':note' => $_POST['note'],
        ':created_at' => date('Y-m-d H:i:s')
    ];
    if ($stmt->execute($dataPost)) {
        send_json_response(__("success"), __("record_has_been_added_successfully"), "success");
    } else {
        send_json_response(__("error"), __("record_not_added_due_to_error"), "error");
    }
} elseif ($ajaxType == "view_notes") {
    // Use INNER JOIN to ensure only employees with notes are returned.
    $sql = "SELECT
                `n`.`id`, `n`.`note`, `n`.`status`, `n`.`created_at`,
                `e`.`name`, `e`.`emp_id`
            FROM `employees` `e`
            INNER JOIN `emp_notice` `n` ON `e`.`emp_id` = `n`.`emp_id` AND `n`.`is_deleted` = 0
            WHERE `e`.`emp_id` = :emp_id
            ORDER BY `n`.`created_at` DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':emp_id', $_POST['emp_id'], PDO::PARAM_INT); // It's better to use PDO::PARAM_INT for IDs
    $stmt->execute();
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // This will now correctly return an empty 'notes' array if no records are found
    echo json_encode(['status' => 'success', 'notes' => $notes]);
    exit; // Good practice to exit after an AJAX response
} elseif (isset($ajaxType) && $ajaxType == 'emp_temp_contant') { // This is a duplicate ajaxType, which is bad practice
    header('Content-Type: application/json');
    $requestId = $_POST['id'];
    $empId = $_POST['empid'];
    $approvalAction = $_POST['contant_check']; // 'approve' or 'not_approve'
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
    // --- If the request is APPROVED ---
    if ($approvalAction == 'approve') {
        try {
            // 1. Fetch the request details from the temp table
            $stmt = $pdo->prepare("SELECT type, new_value, path FROM employee_temp_contants WHERE id = ? AND emp_id = ?");
            $stmt->execute([$requestId, $empId]);
            $request = $stmt->fetch();

            if (!$request) {
                echo json_encode(['type' => 'error', 'title' => __('not_found'), 'message' => __('the_original_request_could_not_be_found')]);
                exit;
            }
            // 2. Determine which column to update in the main 'employees' table
            // This section is now updated to match your 'employees' table schema.emp_temp_contant
            $updateField = '';
            switch ($request['type']) {
                case 'Mobile':
                    $updateField = 'mobile';
                    break;
                case 'Email':
                    $updateField = 'email';
                    break;
                case 'Passport No':
                    $updateField = 'passport_number';
                    break;
                case 'Passport Exp':
                    $updateField = 'passport_exp';
                    break;
                case 'Address':
                    $updateField = 'address';
                    break;
                case 'Profile Picture':
                    $updateField = 'avatar';
                    break;
                    // NOTE: 'Employee Documents' case was removed as there is no matching column in your 'employees' table.
                    // If you have a column for general document paths, add a case for it here.
            }
            // Use the path for file-based updates, otherwise use new_value
            $updateValue = ($request['path']) ? $request['path'] : $request['new_value'];
            // 3. Update the main employees table if a valid field was found
            if (!empty($updateField)) {
                // IMPORTANT: The query now uses `emp_id` as the WHERE clause key.
                $updateStmt = $pdo->prepare("UPDATE `employees` SET {$updateField} = ? WHERE emp_id = ?");
                $updateStmt->execute([$updateValue, $empId]);
            }
            // 4. Update the status of the temp request to 'Approved'
            $finalStmt = $pdo->prepare("UPDATE employee_temp_contants SET status = 'Approved', notes = ? WHERE id = ?");
            $finalStmt->execute([$notes, $requestId]);
            echo json_encode(['type' => 'success', 'title' => __('approved'), 'message' => __('employee_information_has_been_successfully_updated')]);
        } catch (PDOException $e) {
            // In a real app, log the error
            // For debugging, you can output the error message:
            // echo json_encode(['type' => 'error', 'title' => 'Database Error', 'message' => $e->getMessage()]);
            echo json_encode(['type' => 'error', 'title' => __('database_error'), 'message' => __('an_error_occurred_while_updating_the_data')]);
        }
        // --- If the request is REJECTED ---
    } elseif ($approvalAction == 'not_approve') {
        try {
            // Just update the status to 'Rejected' and add the reason
            $finalStmt = $pdo->prepare("UPDATE employee_temp_contants SET status = 'Rejected', notes = ? WHERE id = ?");
            $finalStmt->execute([$notes, $requestId]);
            echo json_encode(['type' => 'success', 'title' => __('rejected'), 'message' => __('the_update_request_has_been_rejected')]);
        } catch (PDOException $e) {
            echo json_encode(['type' => 'error', 'title' => __('database_error'), 'message' => __('an_error_occurred_while_updating_the_request_status')]);
        }
    } else {
        echo json_encode(['type' => 'error', 'title' => __('invalid_action'), 'message' => __('no_valid_action_was_submitted')]);
    }
    exit;
} elseif (isset($ajaxType) && $ajaxType == 'create_update_request') {
    // Set the response header to JSON
    header('Content-Type: application/json');

    // Sanitize and retrieve POST data
    $empId = isset($_POST['emp_id']) ? $_POST['emp_id'] : null;
    $type = isset($_POST['type']) ? $_POST['type'] : null;
    $newValue = isset($_POST['new_value']) ? trim($_POST['new_value']) : null;
    $path = null;

    // Basic Validation
    if (empty($empId) || empty($type)) {
        echo json_encode(['type' => 'error', 'title' => __('missing_information'), 'message' => __('employee_id_or_request_type_is_missing')]);
        exit;
    }

    // --- Handle base64 image upload from Croppie ---
    if (isset($_POST['image_base64'])) {
        $data = $_POST['image_base64'];
        // Basic check for base64 string
        if (preg_match('/^data:image\/(\w+);base64,/', $data, $type_match)) {
            $data = substr($data, strpos($data, ',') + 1);
            $image_type = strtolower($type_match[1]); // jpg, png, gif

            $data = base64_decode($data);
            if ($data === false) {
                echo json_encode(['type' => 'error', 'title' => __('upload_failed'), 'message' => __('base64_decode_failed')]);
                exit;
            }

            $uploadDir = './../../assets/emp_pics/emp_' . $empId . '/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $fileName = 'avatar_' . time() . '.' . $image_type;
            $targetPath = $uploadDir . $fileName;

            if (file_put_contents($targetPath, $data)) {
                $path = $targetPath;
            } else {
                echo json_encode(['type' => 'error', 'title' => __('upload_failed'), 'message' => __('could_not_save_the_cropped_image')]);
                exit;
            }
        } else {
            echo json_encode(['type' => 'error', 'title' => __('invalid_image'), 'message' => __('the_provided_image_data_was_not_in_a_valid_format')]);
            exit;
        }
    }
    // --- Handle standard file uploads (for other document types in the future) ---
    else if (isset($_FILES['file']) && $_FILES['file']['error'] == UPLOAD_ERR_OK) {
        $uploadDir = './../../assets/emp_pics/emp_' . $empId . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $fileExtension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
        $fileName = time() . '_' . uniqid() . '.' . $fileExtension;
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
            $path = $targetPath;
        } else {
            echo json_encode(['type' => 'error', 'title' => __('upload_failed'), 'message' => __('server_could_not_save_the_uploaded_file')]);
            exit;
        }
    }

    // Final check: ensure a value or a path was provided
    if (empty($newValue) && empty($path)) {
        echo json_encode(['type' => 'error', 'title' => __('invalid_input'), 'message' => __('please_provide_a_new_value_or_select_a_file_to_submit')]);
        exit;
    }

    try {
        // Insert the request into the temporary table for HR approval
        $sql = "INSERT INTO employee_temp_contants (emp_id, type, new_value, path, status, created_at) VALUES (?, ?, ?, ?, 'Pending', NOW())";
        $stmt = mysqli_prepare($conDB, $sql);
        mysqli_stmt_bind_param($stmt, 'isss', $empId, $type, $newValue, $path);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt); // <-- FIX

        // Send a success response back to the browser
        echo json_encode([
            'type' => 'success',
            'title' => __('request_submitted'),
            // 'message' => 'Your request to update your ' . strtolower($type) . ' has been sent to HR for approval.'
            'message' => sprintf(__('your_request_to_update_your_has_been_sent_to_hr_for_approval'), strtolower($type))
        ]);
    } catch (Exception $e) {
        echo json_encode(['type' => 'error', 'title' => __('database_error'), 'message' => __('the_catch_block_is_working_the_error_was')]);
    }
    // IMPORTANT: Stop script execution after handling the AJAX request
    exit;
} elseif ($ajaxType == 'get_emp_vacation_details') {
    $empid = $_POST['empid'] ?? null;
    if (!$empid) {
        echo json_encode(['status' => 400, 'message' => __('employee_id_is_required')]);
        exit;
    }

    $query = "SELECT e.name, e.vac_period as vac_period_id, cp.vac_period as vac_period_days 
              FROM employees e 
              JOIN contract_period cp ON e.vac_period = cp.id 
              WHERE e.emp_id = ?";

    $stmt = $conDB->prepare($query);
    $stmt->bind_param("s", $empid);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        $data['emp_id'] = $empid;
        $result->free(); // <-- FIX
        echo json_encode(['status' => 200, 'data' => $data]);
    } else {
        $result->free(); // <-- FIX
        echo json_encode(['status' => 404, 'message' => __('employee_not_found_or_has_no_vacation_contract_assigned')]);
    }
    $stmt->close();
    exit;
} elseif ($ajaxType == 'emp_search_select2') { // New case for Select2
    $searchTerm = $_POST['searchTerm'] ?? '';
    $stmt = $conDB->prepare("SELECT `emp_id` as `id`, `name` as `text` FROM `employees` WHERE `status`=1 AND (`name` LIKE ? OR `emp_id` LIKE ?) ORDER BY `name` ASC");
    $likeTerm = "%{$searchTerm}%";
    $stmt->bind_param("ss", $likeTerm, $likeTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    $employees = [];
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
    $result->free(); // <-- FIX
    $stmt->close(); // <-- FIX
    echo json_encode(['data' => $employees]);
    exit;
}

// ================================================================
// --- [NEW] GET VACATION STATUS HISTORY (for timeline modal) ---
// ================================================================
elseif ($ajaxType == 'getVacationStatusHistory') {
    try {
        $request_inv_no = trim($_POST['request_inv_no'] ?? '');
        if ($request_inv_no === '') {
            echo json_encode(['status' => 400, 'message' => __('missing_request_inv_no')]);
            exit;
        }
        $stmt = $conDB->prepare("SELECT status, note, emp_name, created_at FROM smt_request_status WHERE inv_no = ? ORDER BY created_at ASC");
        if (!$stmt) {
            echo json_encode(['status' => 500, 'message' => __('db_error_prepare_failed')]);
            exit;
        }
        $stmt->bind_param('s', $request_inv_no);
        $stmt->execute();
        $res = $stmt->get_result();
        $history = [];
        while ($row = $res->fetch_assoc()) {
            $history[] = $row;
        }
        if ($res) {
            $res->free();
        }
        $stmt->close();
        echo json_encode(['status' => 200, 'history' => $history]);
    } catch (Exception $e) {
        echo json_encode(['status' => 500, 'message' => $e->getMessage()]);
    }
    exit;
}

// ================================================================
// --- NEW CHAIN APPROVAL AJAX FUNCTIONS ---
// ================================================================

elseif ($ajaxType == 'get_potential_approvers') {
    // This function is defined in helper_functions.php
    $data = get_potential_approvers($conDB);
    echo json_encode(['data' => $data, 'status' => 200]);
    exit;
} elseif ($ajaxType == 'get_department_approvers') {
    // This function is defined in helper_functions.php
    $dept_id = (int)($_POST['dept_id'] ?? 0);
    $data = get_department_approvers($conDB, $dept_id);
    echo json_encode(['data' => $data, 'status' => 200]);
    exit;
} elseif ($ajaxType == 'get_asset_department_employees') {
    // Return all active employees for the manager's asset department (Admin, IT, or Transportation)
    $dept_id = (int)($_POST['dept_id'] ?? 0);
    if ($dept_id <= 0) {
        echo json_encode(['status' => 400, 'message' => 'Department id is required']);
        exit;
    }

    $asset_dept_ids = [1, 6, 17];
    if (!in_array($dept_id, $asset_dept_ids, true)) {
        echo json_encode(['status' => 403, 'message' => 'Invalid asset department']);
        exit;
    }

    $employees = get_department_employees_all($conDB, $dept_id);
    echo json_encode(['status' => 200, 'employees' => $employees]);
    exit;
} elseif ($ajaxType == 'get_hr_assistants') {
    // This function is defined in helper_functions.php
    $data = get_hr_assistants($conDB);
    echo json_encode(['data' => $data, 'status' => 200]);
    exit;
}

// ================================================================
// --- [NEW] CHECK IF CURRENT USER IS ASSET CHECKER FOR VACATION ---
// ================================================================
elseif ($ajaxType == 'checkAssetCheckerStatus') {
    try {
        $vacation_id = (int)($_POST['vacation_id'] ?? 0);
        if (empty($vacation_id) || empty($current_user_id)) {
            throw new Exception('Missing required parameters');
        }

        // Get the request_inv_no from vacation ID
        $query_inv = mysqli_query($conDB, "SELECT request_inv_no FROM emp_vacation WHERE id = " . $vacation_id);
        if (!$query_inv || mysqli_num_rows($query_inv) == 0) {
            throw new Exception('Vacation not found');
        }
        $inv_row = mysqli_fetch_assoc($query_inv);
        $request_inv_no = $inv_row['request_inv_no'];
        mysqli_free_result($query_inv);

        // Check if current user is an asset checker in pending status
        $asset_check_query = mysqli_query($conDB, "SELECT ra.id, ra.status, ra.approval_level, art.type_name FROM request_approvers ra 
            JOIN approval_request_types art ON ra.request_type_id = art.id
            WHERE ra.request_inv_no = '" . escape_string($request_inv_no) . "' 
            AND ra.approver_id = " . (int)$current_user_id . "
            AND ra.status IN ('pending', 'awaiting')
            LIMIT 1");

        $is_asset_checker = false;
        $approver_status = null;

        if ($asset_check_query && mysqli_num_rows($asset_check_query) > 0) {
            $approver_row = mysqli_fetch_assoc($asset_check_query);
            $approver_status = $approver_row['status'];
            
            // Check if this is an asset checker role (first in chain, approval_level = 1)
            // Asset checkers are those added at the beginning of the approval chain
            $is_asset_checker = ($approver_row['approval_level'] == 1);
        }
        mysqli_free_result($asset_check_query);

        echo json_encode([
            'status' => 'success',
            'is_asset_checker' => $is_asset_checker,
            'approver_status' => $approver_status
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage(),
            'is_asset_checker' => false
        ]);
    }
    exit;
}

// ================================================================
// --- [NEW] PROCESS ASSET CLEARANCE DECISION ---
// ================================================================
elseif ($ajaxType == 'processAssetClearance') {
    try {
        $vacation_id = (int)($_POST['vacation_id'] ?? 0);
        $asset_decision = trim($_POST['asset_decision'] ?? ''); // 'assets_received' or 'employee_keeps_assets'
        $clearance_comment = trim($_POST['clearance_comment'] ?? '');

        if (empty($vacation_id) || empty($current_user_id)) {
            throw new Exception('Missing required parameters');
        }

        if (!in_array($asset_decision, ['assets_received', 'employee_keeps_assets'])) {
            throw new Exception('Invalid asset decision');
        }

        // Get the request_inv_no and emp_id from vacation ID
        $query_inv = mysqli_query($conDB, "SELECT request_inv_no, emp_id FROM emp_vacation WHERE id = " . $vacation_id);
        if (!$query_inv || mysqli_num_rows($query_inv) == 0) {
            throw new Exception('Vacation not found');
        }
        $inv_row = mysqli_fetch_assoc($query_inv);
        $request_inv_no = $inv_row['request_inv_no'];
        $emp_id = $inv_row['emp_id'];
        mysqli_free_result($query_inv);

        // Save the asset clearance decision by approving as asset checker
        // NOTE: Do NOT update emp_vacation_balance here - it's already updated by HR_Payroll when they approve
        $result = handle_approval_action(
            $conDB,
            $request_inv_no,
            'vacation_request',
            $current_user_id,
            'approve',
            "Asset Clearance: " . ($asset_decision === 'assets_received' ? 'Assets received from employee' : 'Employee is keeping assets')
        );

        if ($result['status'] == 'error') {
            throw new Exception($result['message']);
        }

        // Log the asset clearance decisiona
        $asset_decision_label = ($asset_decision === 'assets_received') ? 'Assets Received' : 'Employee Keeps Assets';
        if (function_exists('save_approval_comment_db')) {
            $approver_name = 'Asset Checker';
            save_approval_comment_db(
                $conDB,
                $request_inv_no,
                'vacation_request',
                'approve',
                $current_user_id,
                $approver_name,
                "Asset Clearance Decision: {$asset_decision_label}" . (!empty($clearance_comment) ? " - {$clearance_comment}" : '')
            );
        }

        send_json_response((__("asset_clearance_complete")), sprintf(__("asset_clearance_has_been_recorded_asset_decision_label"), $asset_decision_label), "success");
    } catch (Exception $e) {
        send_json_response("Error", $e->getMessage(), "error");
    }
    exit;
}

// ================================================================
// --- [NEW] IMPORT OPENING VACATION BALANCE (Manual History) ---
// ================================================================
elseif ($ajaxType == 'addManualHistory') {
    try {
        // Validate required inputs
        $emp_id        = (int)($_POST['emp_id'] ?? 0);
        $contract_id   = (int)($_POST['contract_id'] ?? 0);
        $period_start  = trim($_POST['period_start'] ?? '');
        $period_end    = trim($_POST['period_end'] ?? '');
        $total_days    = isset($_POST['total_days']) ? (float)$_POST['total_days'] : null;
        $used_days     = isset($_POST['used_days']) ? (float)$_POST['used_days'] : null;
        $rem_balance   = isset($_POST['remaining_balance']) ? (float)$_POST['remaining_balance'] : null;

        if ($emp_id <= 0 || $contract_id <= 0 || empty($period_start) || empty($period_end) || $total_days === null || $used_days === null || $rem_balance === null) {
            throw new Exception(__('missing_required_fields'));
        }

        // Optional basic date format check (YYYY-MM-DD)
        $date_re = '/^\d{4}-\d{2}-\d{2}$/';
        if (!preg_match($date_re, $period_start) || !preg_match($date_re, $period_end)) {
            throw new Exception(__('invalid_date_format_expected_yyyy_mm_dd'));
        }

        // Upsert into emp_vacation_balance
        // If a row for same emp_id + contract_id + period_start exists, UPDATE; else INSERT
        $stmt = $pdo->prepare("SELECT `id` FROM `emp_vacation_balance` WHERE `emp_id` = :emp_id AND `contract_id` = :contract_id AND `period_start` = :period_start LIMIT 1");
        $stmt->execute([
            ':emp_id' => $emp_id,
            ':contract_id' => $contract_id,
            ':period_start' => $period_start,
        ]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $upd = $pdo->prepare("UPDATE `emp_vacation_balance` SET 
            `vac_id` = 0,
                    `period_end` = :period_end,
                    `total_days` = :total_days,
                    `used_days` = :used_days,
                    `remaining_balance` = :remaining_balance,
                    `available_balance` = :available_balance,
                    `carryover_days` = :carryover_days,
                    `last_updated` = NOW()
                 WHERE `id` = :id");
            $upd->execute([
                ':period_end' => $period_end,
                ':total_days' => $total_days,
                ':used_days' => $used_days,
                ':remaining_balance' => $rem_balance,
                ':available_balance' => $rem_balance,
                ':carryover_days' => 0,
                ':id' => (int)$existing['id'],
            ]);
        } else {
            $ins = $pdo->prepare("INSERT INTO `emp_vacation_balance` 
            (`emp_id`, `vac_id`, `contract_id`, `period_start`, `period_end`, `total_days`, `used_days`, `remaining_balance`, `available_balance`, `carryover_days`, `last_updated`) 
            VALUES (:emp_id, 0, :contract_id, :period_start, :period_end, :total_days, :used_days, :remaining_balance, :available_balance, :carryover_days, NOW())");
            $ins->execute([
                ':emp_id' => $emp_id,
                ':contract_id' => $contract_id,
                ':period_start' => $period_start,
                ':period_end' => $period_end,
                ':total_days' => $total_days,
                ':used_days' => $used_days,
                ':remaining_balance' => $rem_balance,
                ':available_balance' => $rem_balance,
                ':carryover_days' => 0,
            ]);
        }

        send_json_response(__('success'), __('manual_opening_balance_saved_successfully'), 'success');
    } catch (Exception $e) {
        send_json_response(__('error'), $e->getMessage(), 'error');
    }
    exit;
}

// ================================================================
// --- SIMPLE LEAVE APPLICATION (2-LEVEL APPROVAL) ---
// Approval Chain: Direct Supervisor (or Dept Manager) → HR Senior BP
// ================================================================
elseif ($ajaxType == 'applyLeave') {
    try {
        $empid = (int)($_POST['empid'] ?? 0);
        
        // Validate supervisor assignment FIRST
        $supervisor_check = validate_employee_supervisor($conDB, $empid);
        if (!$supervisor_check['valid']) {
            send_supervisor_validation_error($supervisor_check['message']);
        }
        
        $leave_type = trim($_POST['leave_type'] ?? '');
        $start_date = trim($_POST['start_date'] ?? '');
        $end_date = trim($_POST['end_date'] ?? '');
        $reason = trim($_POST['reason'] ?? '');
        $trip_destination = trim($_POST['trip_destination'] ?? '');
        $accommodation_provided = trim($_POST['accommodation_provided'] ?? '');
        $transportation_provided = trim($_POST['transportation_provided'] ?? '');

        // Validation
        if ($empid <= 0) {
            send_json_response(__('error'), __('invalid_employee_id'), 'error', 400);
            exit;
        }

        // Define valid leave types
        $valid_leave_types = [
            'Sick Leave',
            'Exam Leave',
            'Hajj Leave',
            'Maternity Leave',
            'Marriage Leave',
            'Newborn Leave',
            'Death Leave',
            'Business Trip'
        ];

        if (empty($leave_type) || !in_array($leave_type, $valid_leave_types)) {
            send_json_response(__('error'), __('invalid_leave_type_selected'), 'error', 400);
            exit;
        }

        // Get employee details including gender
        $sql_emp_check = "SELECT `emp_id`, `sex`, `dept`, `supervisor_id` FROM `employees` WHERE `emp_id` = ? LIMIT 1";
        $stmt_emp_check = mysqli_prepare($conDB, $sql_emp_check);
        if (!$stmt_emp_check) {
            send_json_response(__('error'), __('failed_to_fetch_employee_information'), 'error', 500);
            exit;
        }
        mysqli_stmt_bind_param($stmt_emp_check, "i", $empid);
        mysqli_stmt_execute($stmt_emp_check);
        $result_emp_check = mysqli_stmt_get_result($stmt_emp_check);
        $emp_info = mysqli_fetch_assoc($result_emp_check);
        mysqli_free_result($result_emp_check);
        mysqli_stmt_close($stmt_emp_check);

        if (!$emp_info) {
            send_json_response(__('error'), __('employee_not_found'), 'error', 400);
            exit;
        }

        $employee_gender = (int)($emp_info['sex'] ?? 0);

        // Validate gender-specific leave types
        if ($leave_type === 'Maternity Leave' && $employee_gender !== 2) {
            send_json_response(__('error'), __('maternity_leave_is_only_available_for_female_employees'), 'error', 400);
            exit;
        }

        if ($leave_type === 'Newborn Leave' && $employee_gender !== 1) {
            send_json_response(__('error'), __('newborn_leave_is_only_available_for_male_employees'), 'error', 400);
            exit;
        }

        // Validate required fields - ALL fields are now required
        if (empty($start_date)) {
            send_json_response(__('error'), __('start_date_is_required'), 'error', 400);
            exit;
        }

        if (empty($end_date)) {
            send_json_response(__('error'), __('end_date_is_required'), 'error', 400);
            exit;
        }

        if (empty($reason)) {
            send_json_response(__('error'), __('reason_is_required_for_all_leave_types'), 'error', 400);
            exit;
        }

        // Validate Business Trip destination
        if ($leave_type === 'Business Trip' && empty($trip_destination)) {
            send_json_response(__('error'), __('destination_is_required_for_business_trip'), 'error', 400);
            exit;
        }

        // Validate Business Trip accommodation and transportation
        if ($leave_type === 'Business Trip') {
            if (empty($accommodation_provided) || !in_array($accommodation_provided, ['yes', 'no'])) {
                send_json_response(__('error'), __('accommodation_provided_status_is_required_for_business_trip'), 'error', 400);
                exit;
            }
            if (empty($transportation_provided) || !in_array($transportation_provided, ['yes', 'no'])) {
                send_json_response(__('error'), __('transportation_provided_status_is_required_for_business_trip'), 'error', 400);
                exit;
            }
        }

        // Validate attachments - REQUIRED for ALL leave types (1-10 files, max 5MB each)
        if (!isset($_FILES['attachments']) || empty($_FILES['attachments']['name'][0])) {
            send_json_response(__('error'), __('attachment_is_required_for_all_leave_types'), 'error', 400);
            exit;
        }

        // Count number of files
        $file_count = count($_FILES['attachments']['name']);
        if ($file_count < 1) {
            send_json_response(__('error'), __('at_least_one_file_required') ?: 'At least one file is required', 'error', 400);
            exit;
        }
        if ($file_count > 10) {
            send_json_response(__('error'), __('max_10_files_allowed') ?: 'Maximum 10 files allowed', 'error', 400);
            exit;
        }

        // Validate each file size (max 5MB)
        $max_file_size = 5 * 1024 * 1024; // 5MB in bytes
        for ($i = 0; $i < $file_count; $i++) {
            if ($_FILES['attachments']['error'][$i] === UPLOAD_ERR_OK) {
                if ($_FILES['attachments']['size'][$i] > $max_file_size) {
                    $file_size_mb = round($_FILES['attachments']['size'][$i] / 1024 / 1024, 2);
                    send_json_response(__('error'), (__('file_too_large') ?: 'File too large') . ': ' . $_FILES['attachments']['name'][$i] . " ({$file_size_mb}MB). " . (__('max_5mb') ?: 'Maximum 5MB per file'), 'error', 400);
                    exit;
                }
            }
        }

        // Check if employee is currently on an active approved vacation/leave (can't apply while on leave)

        // Check: Does the requested excuse leave overlap with ANY approved/completed vacation or leave?
        if (!empty($start_date) && !empty($end_date)) {
            $sql_active = "SELECT request_inv_no, vac_type, start_date, return_date, current_status FROM emp_vacation 
                           WHERE emp_id = ? 
                             AND current_status IN ('approved', 'completed')
                             AND start_date <= ? 
                             AND return_date >= ? 
                           LIMIT 1";
            $stmt_active = mysqli_prepare($conDB, $sql_active);
            if ($stmt_active) {
                mysqli_stmt_bind_param($stmt_active, 'iss', $empid, $end_date, $start_date);



                if (mysqli_stmt_execute($stmt_active)) {
                    $res_active = mysqli_stmt_get_result($stmt_active);
                    if ($res_active && mysqli_num_rows($res_active) > 0) {
                        $active_request = mysqli_fetch_assoc($res_active);

                        if ($res_active) mysqli_free_result($res_active);
                        mysqli_stmt_close($stmt_active);

                        $request_type_name = (strpos($active_request['request_inv_no'], 'VAC-') === 0) ? 'Annual Vacation' : 'Leave';
                        $status_display = ($active_request['current_status'] === 'completed') ? 'completed' : 'approved';

                        send_json_response(
                            __('date_conflict'),
                            // 'Your requested dates (' . htmlspecialchars($start_date) . ' to ' . htmlspecialchars($end_date) . ') overlap with an existing ' . $status_display . ' ' . htmlspecialchars($active_request['vac_type']) . ' (' . htmlspecialchars($active_request['request_inv_no']) . ') from ' . htmlspecialchars($active_request['start_date']) . ' to ' . htmlspecialchars($active_request['return_date']) . '. Please choose different dates.',
                            sprintf(__('date_conflict_message'), htmlspecialchars($start_date), htmlspecialchars($end_date), $status_display, htmlspecialchars($active_request['vac_type']), htmlspecialchars($active_request['request_inv_no']), htmlspecialchars($active_request['start_date']), htmlspecialchars($active_request['return_date'])),
                            'error',
                            400
                        );
                        exit;
                    }
                    if ($res_active) mysqli_free_result($res_active);
                }
                mysqli_stmt_close($stmt_active);
            }
        }

        // Check for overlapping leave requests (pending, approved, or completed)
        // IMPORTANT: Excuse leave CANNOT overlap with approved/completed annual vacation
        // If employee has approved/completed annual vacation, excuse leave must be AFTER return_date
        if (!empty($start_date) && !empty($end_date)) {
            // First check for approved/completed annual vacation (VAC-*)
            $sql_vacation = "SELECT request_inv_no, vac_type, start_date, return_date, current_status FROM emp_vacation 
                            WHERE emp_id = ? 
                              AND current_status IN ('approved', 'completed')
                              AND request_inv_no LIKE 'VAC-%'
                              AND start_date <= ? 
                              AND return_date >= ? ";
            $stmt_vacation = mysqli_prepare($conDB, $sql_vacation);
            if ($stmt_vacation) {
                mysqli_stmt_bind_param($stmt_vacation, 'iss', $empid, $end_date, $start_date);



                if (mysqli_stmt_execute($stmt_vacation)) {
                    $res_vacation = mysqli_stmt_get_result($stmt_vacation);
                    if ($res_vacation && mysqli_num_rows($res_vacation) > 0) {
                        $vacation = mysqli_fetch_assoc($res_vacation);



                        if ($res_vacation) mysqli_free_result($res_vacation);
                        mysqli_stmt_close($stmt_vacation);

                        $vac_status = ($vacation['current_status'] === 'completed') ? 'completed' : 'approved';

                        send_json_response(
                            __('cannot_apply_during_annual_vacation'),
                            // 'You cannot apply for excuse leave during your ' . $vac_status . ' annual vacation period (' . htmlspecialchars($vacation['start_date']) . ' to ' . htmlspecialchars($vacation['return_date']) . '). Excuse leave must be applied AFTER your vacation return date: ' . htmlspecialchars($vacation['return_date']) . '.',
                            sprintf(__('cannot_apply_during_annual_vacation_message'), $vac_status, htmlspecialchars($vacation['start_date']), htmlspecialchars($vacation['return_date']), htmlspecialchars($vacation['return_date'])),
                            'error',
                            400
                        );
                        exit;
                    }
                    if ($res_vacation) mysqli_free_result($res_vacation);
                }
                mysqli_stmt_close($stmt_vacation);
            }

            // Then check for other overlapping leave requests (pending, approved, or completed)
            $sql_overlap = "SELECT request_inv_no, vac_type, start_date, return_date, current_status FROM emp_vacation 
                            WHERE emp_id = ? 
                              AND current_status IN ('pending_approval', 'approved', 'completed') 
                              AND start_date <= ? 
                              AND return_date >= ? ";
            $stmt_overlap = mysqli_prepare($conDB, $sql_overlap);
            if ($stmt_overlap) {
                mysqli_stmt_bind_param($stmt_overlap, 'iss', $empid, $end_date, $start_date);
                if (mysqli_stmt_execute($stmt_overlap)) {
                    $res_overlap = mysqli_stmt_get_result($stmt_overlap);
                    if ($res_overlap && mysqli_num_rows($res_overlap) > 0) {
                        $overlap = mysqli_fetch_assoc($res_overlap);
                        if ($res_overlap) mysqli_free_result($res_overlap);
                        mysqli_stmt_close($stmt_overlap);

                        $status_text = 'pending';
                        if ($overlap['current_status'] === 'approved') {
                            $status_text = 'approved';
                        } elseif ($overlap['current_status'] === 'completed') {
                            $status_text = 'completed';
                        }

                        send_json_response(
                            __('date_conflict'),
                            // 'You already have a ' . $status_text . ' ' . htmlspecialchars($overlap['vac_type']) . ' request (' . htmlspecialchars($overlap['request_inv_no']) . ') covering ' . htmlspecialchars($overlap['start_date']) . ' to ' . htmlspecialchars($overlap['return_date']) . '. Your requested dates (' . htmlspecialchars($start_date) . ' to ' . htmlspecialchars($end_date) . ') overlap with this existing request. Please choose different dates.',
                            sprintf(__('date_conflict_message_existing_request'), $status_text, htmlspecialchars($overlap['vac_type']), htmlspecialchars($overlap['request_inv_no']), htmlspecialchars($overlap['start_date']), htmlspecialchars($overlap['return_date']), htmlspecialchars($start_date), htmlspecialchars($end_date)),
                            'error',
                            400
                        );
                        exit;
                    }
                    if ($res_overlap) mysqli_free_result($res_overlap);
                }
                mysqli_stmt_close($stmt_overlap);
            }
        }

        // Use employee data already fetched above
        $emp_dept = $emp_info['dept'];
        $supervisor_id = $emp_info['supervisor_id'] ?? null;

        // Get first approver: Direct Supervisor OR Department Manager
        $first_approver = null;
        $first_approver_label = '';

        if (!empty($supervisor_id)) {
            // Try to get direct supervisor first
            $sql_supervisor = "SELECT e.emp_id, e.name, al.email 
                              FROM `employees` e 
                              LEFT JOIN `admin_login` al ON e.emp_id = al.emp_id 
                              WHERE e.`emp_id` = ? AND e.`status` = 1 
                              LIMIT 1";
            $stmt_supervisor = mysqli_prepare($conDB, $sql_supervisor);
            if ($stmt_supervisor) {
                mysqli_stmt_bind_param($stmt_supervisor, "s", $supervisor_id);
                mysqli_stmt_execute($stmt_supervisor);
                $result_supervisor = mysqli_stmt_get_result($stmt_supervisor);
                $first_approver = mysqli_fetch_assoc($result_supervisor);
                mysqli_free_result($result_supervisor);
                mysqli_stmt_close($stmt_supervisor);

                if ($first_approver && !empty($first_approver['emp_id'])) {
                    $first_approver_label = __('direct_supervisor');
                }
            }
        }

        // Fallback to department manager if no direct supervisor
        if (!$first_approver || empty($first_approver['emp_id'])) {
            $first_approver = getDeptManager($conDB, $emp_dept);
            $first_approver_label = __('department_manager');
        }

        if (!$first_approver || empty($first_approver['emp_id'])) {
            send_json_response(__('error'), __('no_supervisor_or_department_manager_found_please_contact_hr'), 'error', 400);
            exit;
        }

        // ================================================================
        // BUILD APPROVAL CHAIN FROM DATABASE (approval_request_types + app_settings)
        // This replaces hardcoded approver roles with database-driven configuration
        // ================================================================
        $approver_chain = [];
        
        // Load configured approval chain from app_settings for 'excuse_leave'
        $settingName = "approval_chain_excuse_leave";
        $query_chain = mysqli_query($conDB, "SELECT setting_value FROM app_settings WHERE setting_name = '{$settingName}' LIMIT 1");
        
        if ($query_chain && mysqli_num_rows($query_chain) > 0) {
            $row_chain = mysqli_fetch_assoc($query_chain);
            $chainConfig = json_decode($row_chain['setting_value'], true);
            
            if ($chainConfig && is_array($chainConfig) && count($chainConfig) > 0) {
                // Resolve each approver in the configured chain
                foreach ($chainConfig as $step) {
                    $user_type = $step['user_type'] ?? '';
                    $approver_emp_id = null;
                    
                    // Special handling for direct_supervisor
                    if ($user_type === 'direct_supervisor') {
                        if (!empty($first_approver['emp_id'])) {
                            $approver_emp_id = (int)$first_approver['emp_id'];
                        }
                    } 
                    // Special handling for dept_manager
                    elseif ($user_type === 'dept_manager') {
                        $dept_mgr = getDeptManager($conDB, $emp_dept);
                        if ($dept_mgr && !empty($dept_mgr['emp_id'])) {
                            $approver_emp_id = (int)$dept_mgr['emp_id'];
                        }
                    }
                    // For all other user types, query by user_type
                    else {
                        $user_type_esc = mysqli_real_escape_string($conDB, $user_type);
                        $sql_approver = "SELECT e.emp_id FROM employees e 
                                        LEFT JOIN admin_login al ON e.emp_id = al.emp_id 
                                        WHERE al.user_type = '{$user_type_esc}' AND e.status = 1 
                                        ORDER BY e.emp_id ASC LIMIT 1";
                        $result_approver = mysqli_query($conDB, $sql_approver);
                        if ($result_approver && mysqli_num_rows($result_approver) > 0) {
                            $approver_row = mysqli_fetch_assoc($result_approver);
                            $approver_emp_id = (int)$approver_row['emp_id'];
                            mysqli_free_result($result_approver);
                        }
                    }
                    
                    // Add approver to chain if found (skip duplicates)
                    if ($approver_emp_id > 0 && !in_array($approver_emp_id, $approver_chain, true)) {
                        $approver_chain[] = $approver_emp_id;
                    }
                }
            }
        }
        // Free result after all processing is complete
        if ($query_chain) mysqli_free_result($query_chain);
        
        // FALLBACK: If no configured chain or chain is empty, use legacy 2-level approval
        // [Direct Supervisor/Dept Manager → HR Senior BP]
        if (empty($approver_chain)) {
            // Add first approver (Direct Supervisor or Dept Manager)
            if (!empty($first_approver['emp_id'])) {
                $approver_chain[] = (int)$first_approver['emp_id'];
            }
            
            // Add HR Senior BP as second approver
            $sql_hr_senior_bp = "SELECT e.emp_id FROM employees e 
                                LEFT JOIN admin_login al ON e.emp_id = al.emp_id 
                                WHERE al.user_type = 'hr_senior_bp' AND e.status = 1 
                                ORDER BY e.emp_id ASC LIMIT 1";
            $result_hr_senior_bp = mysqli_query($conDB, $sql_hr_senior_bp);
            if ($result_hr_senior_bp && mysqli_num_rows($result_hr_senior_bp) > 0) {
                $hr_senior_bp = mysqli_fetch_assoc($result_hr_senior_bp);
                if (!in_array((int)$hr_senior_bp['emp_id'], $approver_chain, true)) {
                    $approver_chain[] = (int)$hr_senior_bp['emp_id'];
                }
                mysqli_free_result($result_hr_senior_bp);
            }
        }
        
        // Validate that we have at least one approver
        if (empty($approver_chain)) {
            send_json_response(__('error'), __('no_approvers_configured_for_excuse_leave_please_contact_administrator'), 'error', 400);
            exit;
        }
        
        // Get first approver for notifications
        $first_approver_emp_id = $approver_chain[0];
        // ================================================================
        // END: Database-driven approval chain configuration
        // ================================================================

        // Determine if leave is deductible (affects payroll)

        //$deductible_types = ['Sick Leave', 'Casual Leave', 'Unpaid Leave'];
        //$is_deductible = in_array($leave_type, $deductible_types) ? 1 : 0;

        $is_deductible = 0; // All leave types are deductible by default

        // Generate unique request invoice number with LV- prefix (Leave Request)
        // Annual Vacation applications use the applyVacation handler which generates VAC- prefix
        $request_inv_no = 'LV-' . date('Ymd') . '-' . str_pad($empid, 4, '0', STR_PAD_LEFT) . '-' . substr(uniqid(), -4);

        // Calculate days if dates are provided
        $vacdays = 1; // Default 1 day
        if (!empty($start_date) && !empty($end_date)) {
            $start = new DateTime($start_date);
            $end = new DateTime($end_date);
            $interval = $start->diff($end);
            $vacdays = $interval->days + 1; // Include both start and end dates
        }

        // Handle multiple file uploads
        $attachment_paths = [];
        if (isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {
            $upload_dir = __DIR__ . '/../../assets/leave_attachments/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $allowed_exts = ['jpg', 'jpeg', 'png', 'pdf'];
            $file_count = count($_FILES['attachments']['name']);

            for ($i = 0; $i < $file_count; $i++) {
                if ($_FILES['attachments']['error'][$i] === UPLOAD_ERR_OK) {
                    $file_ext = strtolower(pathinfo($_FILES['attachments']['name'][$i], PATHINFO_EXTENSION));

                    if (!in_array($file_ext, $allowed_exts)) {
                        send_json_response(__('error'), __('invalid_file_type_only_jpg_png_and_pdf_are_allowed') . ': ' . $_FILES['attachments']['name'][$i], 'error', 400);
                        exit;
                    }

                    $file_name = 'leave_' . $empid . '_' . time() . '_' . ($i + 1) . '.' . $file_ext;
                    $file_path = $upload_dir . $file_name;

                    if (move_uploaded_file($_FILES['attachments']['tmp_name'][$i], $file_path)) {
                        $attachment_paths[] = 'assets/leave_attachments/' . $file_name;
                    }
                }
            }
        }

        // Convert attachment paths array to JSON string for storage
        $attachment_path = !empty($attachment_paths) ? json_encode($attachment_paths) : null;

        // Prepare remarks with leave type and reason
        $remarks = $leave_type;
        if (!empty($trip_destination)) {
            $remarks .= ' - Destination: ' . $trip_destination;
        }
        if ($leave_type === 'Business Trip') {
            $remarks .= ' - Accommodation: ' . ($accommodation_provided === 'yes' ? 'Yes' : 'No');
            $remarks .= ' - Transportation: ' . ($transportation_provided === 'yes' ? 'Yes' : 'No');
        }
        if (!empty($reason)) {
            $remarks .= ' - ' . $reason;
        }

        // Insert into emp_vacation table with pending approval status
        $sql = "INSERT INTO `emp_vacation` 
                (`emp_id`, `vac_type`, `fly_type`, `replacement_person`, `start_date`, `return_date`, `vacdays`, `remarks`, `vacation_salary_type`, `attachment_path`, `request_inv_no`, `is_deductible`, `current_status`, `current_approval_level`, `accommodation_provided`, `transportation_provided`, `review`) 
                VALUES (?, ?, '', '', ?, ?, ?, ?, 'payroll', ?, ?, ?, 'pending_approval', 1, ?, ?, 'A')";

        $stmt = mysqli_prepare($conDB, $sql);
        if (!$stmt) {
            send_json_response(__('error'), __('database_preparation_failed') . ': ' . mysqli_error($conDB), 'error', 500);
            exit;
        }

        mysqli_stmt_bind_param(
            $stmt,
            "isssisssiss",
            $empid,
            $leave_type,
            $start_date,
            $end_date,
            $vacdays,
            $remarks,
            $attachment_path,
            $request_inv_no,
            $is_deductible,
            $accommodation_provided,
            $transportation_provided
        );

        if (!mysqli_stmt_execute($stmt)) {
            send_json_response(__('error'), __('failed_to_submit_leave_request') . ': ' . mysqli_stmt_error($stmt), 'error', 500);
            mysqli_stmt_close($stmt);
            exit;
        }

        mysqli_stmt_close($stmt);
        
        // NOTE: Excuse leave types (Sick, Exam, Hajj, Maternity, Marriage, Newborn, Death, Business Trip) 
        // do NOT deduct from annual vacation balance (emp_vacation_balance.total_days)
        // They are tracked separately and don't consume the employee's annual vacation entitlement
        // Only annual vacation (applied through applyVacation with fly_type='annual') and encashment deduct from balance

        // ================================================================
        // SAVE APPROVAL CHAIN using ApprovalChainManager
        // Note: Request type 'excuse_leave' is stored separately from 'vacation_request'
        // to allow independent approval workflow configuration
        // ================================================================
        
        require_once __DIR__ . '/../ApprovalChainManager.php';
        $chainManager = new ApprovalChainManager($conDB, $pdo);
        
        $chainResult = $chainManager->createApprovalChain(
            'excuse_leave',
            $request_inv_no,
            $empid,
            $employee['dept']
        );
        
        if (!$chainResult['success']) {
            error_log("EXCUSE LEAVE ERROR: createApprovalChain failed for request $request_inv_no");
            send_json_response(__('error'), 'Failed to create approval chain: ' . ($chainResult['message'] ?? 'Unknown error'), 'error', 500);
            exit;
        }
        
        $first_approver = $chainResult['first_approver'];
        
        // ================================================================
        // SEND NOTIFICATION TO FIRST APPROVER
        // Using the first approver from the database-configured chain
        // ================================================================
        if ($first_approver && !empty($first_approver['approver_id'])) {
            $approver_details = getEmployeeDetailsForApproval($conDB, (int)$first_approver['approver_id']);
            if ($approver_details) {
                // Send browser notification using ApprovalChainManager
                $chainManager->notifyApprover(
                    $first_approver['approver_id'],
                    "New Leave Request",
                    "A new leave request ($request_inv_no) for $leave_type from employee ID $empid is pending your approval.",
                    "all_applied_vac.php?status=my_pending"
                );

                if (!empty($approver_details['email']) && function_exists('send_approval_email')) {
                    // Get employee name for template
                    $employee_name = 'Employee';
                    $emp_result = mysqli_query($conDB, "SELECT name FROM employees WHERE emp_id = '$empid' LIMIT 1");
                    if ($emp_result && $emp_row = mysqli_fetch_assoc($emp_result)) {
                        $employee_name = $emp_row['name'];
                    }
                    if ($emp_result) mysqli_free_result($emp_result);

                    // Get HR Payroll email for CC (only for excuse leave requests)
                    $cc_emails = [];
                    $hr_payroll_result = mysqli_query($conDB, "SELECT e.name, al.email FROM employees e JOIN admin_login al ON e.emp_id = al.emp_id WHERE al.user_type='hr_payroll' AND e.status=1 AND al.email IS NOT NULL AND al.email != '' ORDER BY e.emp_id ASC LIMIT 1");
                    if ($hr_payroll_result && $hr_payroll_row = mysqli_fetch_assoc($hr_payroll_result)) {
                        if (!empty($hr_payroll_row['email'])) {
                            $cc_emails[$hr_payroll_row['email']] = $hr_payroll_row['name'];
                        }
                    }
                    if ($hr_payroll_result) mysqli_free_result($hr_payroll_result);

                    // Prepare template data
                    $base_url = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME'], 3);
                    $template_data = [
                        'APPROVER_NAME' => $approver_details['name'],
                        'REQUEST_TYPE' => ucfirst($leave_type) . ' Leave Request',
                        'REQUEST_TYPE_LOWER' => strtolower($leave_type) . ' leave request',
                        'REQUEST_ID' => $request_inv_no,
                        'EMPLOYEE_NAME' => $employee_name,
                        'START_DATE' => date('d M Y', strtotime($start_date)),
                        'END_DATE' => date('d M Y', strtotime($end_date)),
                        'DURATION' => $vacdays,
                        'REQUEST_URL' => $base_url . '/all_applied_vac.php?status=my_pending'
                    ];

                    $email_subject = "New " . ucfirst($leave_type) . " Leave Request Pending Approval";
                    $email_result = send_approval_email($conDB, $approver_details['email'], $approver_details['name'], $email_subject, 'leave_request', $template_data, $cc_emails);
                } else {
                    if (empty($approver_details['email'])) {
                    }
                    if (!function_exists('send_approval_email')) {
                    }
                }
                // --- [END UPDATED] ---
            } else {
            }
        } else {
            if (!function_exists('getEmployeeDetailsForApproval')) {
            }
            if (empty($first_approver['emp_id'])) {
            }
        }

        send_json_response(
            __('success'),
            sprintf(__('leave_request_submitted_successfully_request_no_pending_approval_from_your'), $request_inv_no, 
                    isset($first_approver_label) ? $first_approver_label : __('supervisor')),
            'success'
        );
    } catch (Exception $e) {
        send_json_response('Error', $e->getMessage(), 'error', 500);
    }
    exit;
}

// ================================================================
// --- GET CURRENT VACATION BALANCE (Live Calculated) ---
// ================================================================
elseif ($ajaxType == 'getCurrentVacationBalance') {
    try {
        $emp_id = trim((string)($_POST['empid'] ?? $_POST['emp_id'] ?? ''));

        if (empty($emp_id)) {
            echo json_encode([
                'status' => 400,
                'balance' => 0,
                'message' => __('employee_id_is_required')
            ]);
            exit;
        }

        $balance = get_current_vacation_balance($conDB, $emp_id);

        if ($balance === null) {
            echo json_encode([
                'status' => 404,
                'balance' => 0,
                'message' => __('employee_not_found_or_balance_unavailable')
            ]);
            exit;
        }

        echo json_encode([
            'status' => 200,
            'balance' => $balance,
            'message' => __('balance_retrieved_successfully')
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'status' => 500,
            'balance' => 0,
            'message' => __('error') . ': ' . $e->getMessage()
        ]);
    }
    exit;
}

// ============================================================================
// CHECK ACTIVE REJOIN REQUEST HANDLER
// ============================================================================
elseif ($ajaxType == 'checkActiveRejoinRequest') {
    try {
        $emp_id = (int)($_POST['emp_id'] ?? 0);

        if (empty($emp_id)) {
            throw new Exception(__("required_fields_missing"));
        }

        $pdo = getDbConnection();
        
        // Check if employee already has an active rejoin request (pending or adjusted status)
        $stmt_check = $pdo->prepare("
            SELECT rr.id, rr.request_inv_no, rr.status, rr.requested_rejoin_date, rr.requested_at, rr.vacation_id,
                   v.request_inv_no as vacation_inv_no, v.vac_type
            FROM rejoin_requests rr
            JOIN emp_vacation v ON rr.vacation_id = v.id
            WHERE rr.emp_id = :emp_id 
            AND rr.status IN ('pending', 'adjusted')
            LIMIT 1
        ");
        $stmt_check->execute([':emp_id' => $emp_id]);
        $active_request = $stmt_check->fetch(PDO::FETCH_ASSOC);
        
        if ($active_request) {
            // Employee has an active request - return it with warning
            send_json_response(
                __("active_request_exists"),
                sprintf(
                    __("you_already_have_an_active_rejoin_request", "You already have an active rejoin request (%s) with status '%s' for vacation %s. Please wait for approval or rejection before submitting a new request."),
                    htmlspecialchars($active_request['request_inv_no']),
                    htmlspecialchars($active_request['status']),
                    htmlspecialchars($active_request['vacation_inv_no'])
                ),
                "warning",
                200,
                [
                    'active_request' => [
                        'id' => (int)$active_request['id'],
                        'request_inv_no' => $active_request['request_inv_no'],
                        'status' => $active_request['status'],
                        'requested_rejoin_date' => $active_request['requested_rejoin_date'],
                        'requested_at' => $active_request['requested_at'],
                        'vacation_inv_no' => $active_request['vacation_inv_no'],
                        'vac_type' => $active_request['vac_type']
                    ]
                ]
            );
        } else {
            // No active request found - allow submission
            send_json_response(
                __("no_active_request"),
                __("you_can_submit_rejoin_request"),
                "success",
                200
            );
        }

    } catch (Exception $e) {
        send_json_response(
            __("error"),
            $e->getMessage(),
            "error",
            400
        );
    }
}

// ============================================================================
// REJOIN REQUEST HANDLER
// ============================================================================
elseif ($ajaxType == 'submitRejoinRequest') {
    try {
        $vacation_id = (int)($_POST['vacation_id'] ?? 0);
        $rejoin_date = escape_string($_POST['rejoin_date'] ?? '');
        $rejoin_reason = escape_string($_POST['rejoin_reason'] ?? '');
        
        // Get employee ID from vacation record
        $emp_id_query = "SELECT emp_id FROM emp_vacation WHERE id = ? LIMIT 1";
        $emp_stmt = $conDB->prepare($emp_id_query);
        $emp_stmt->bind_param('i', $vacation_id);
        $emp_stmt->execute();
        $emp_result = $emp_stmt->get_result();
        $emp_row = $emp_result->fetch_assoc();
        $emp_stmt->close();
        
        if (!$emp_row) {
            send_json_response(__('error'), __('vacation_not_found'), 'error', 404);
            exit;
        }
        
        $emp_id = $emp_row['emp_id'];
        
        // Validate supervisor assignment FIRST
        $supervisor_check = validate_employee_supervisor($conDB, $emp_id);
        if (!$supervisor_check['valid']) {
            send_supervisor_validation_error($supervisor_check['message']);
        }
        $emp_id = (int)($_POST['emp_id'] ?? $current_user_id);

        // Validation: vacation_id and emp_id are required
        if (empty($vacation_id) || empty($emp_id)) {
            throw new Exception(__("required_fields_missing"));
        }

        $pdo = getDbConnection();
        
        // Check if employee already has an active rejoin request (not rejected or approved)
        $stmt_check = $pdo->prepare("
            SELECT rr.id, rr.request_inv_no, rr.status, rr.requested_rejoin_date, rr.requested_at, rr.vacation_id,
                   v.request_inv_no as vacation_inv_no, v.vac_type
            FROM rejoin_requests rr
            JOIN emp_vacation v ON rr.vacation_id = v.id
            WHERE rr.emp_id = :emp_id 
            AND rr.status IN ('pending', 'adjusted')
            LIMIT 1
        ");
        $stmt_check->execute([':emp_id' => $emp_id]);
        $active_request = $stmt_check->fetch(PDO::FETCH_ASSOC);
        
        if ($active_request) {
            // Employee has an active request - return it with alert info
            send_json_response(
                __("active_request_exists"),
                sprintf(
                    __("you_already_have_an_active_rejoin_request", "You already have an active rejoin request (%s) with status '%s' for vacation %s. Please wait for approval or rejection before submitting a new request."),
                    htmlspecialchars($active_request['request_inv_no']),
                    htmlspecialchars($active_request['status']),
                    htmlspecialchars($active_request['vacation_inv_no'])
                ),
                "warning",
                400,
                [
                    'active_request' => [
                        'id' => (int)$active_request['id'],
                        'request_inv_no' => $active_request['request_inv_no'],
                        'status' => $active_request['status'],
                        'requested_rejoin_date' => $active_request['requested_rejoin_date'],
                        'requested_at' => $active_request['requested_at'],
                        'vacation_inv_no' => $active_request['vacation_inv_no'],
                        'vac_type' => $active_request['vac_type']
                    ]
                ]
            );
            exit;
        }

        $pdo->beginTransaction();

        // Get vacation details
        $stmt = $pdo->prepare("
            SELECT v.*, e.emp_id, e.supervisor_id, e.name 
            FROM emp_vacation v
            JOIN employees e ON v.emp_id = e.emp_id
            WHERE v.id = :vacation_id
        ");
        $stmt->execute([':vacation_id' => $vacation_id]);
        $vacation = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$vacation) {
            throw new Exception(__("vacation_record_not_found"));
        }

        // Use vacation return_date if rejoin_date not provided
        if (empty($rejoin_date)) {
            $rejoin_date = $vacation['return_date'] ?? null;
            if (empty($rejoin_date)) {
                throw new Exception(__("vacation_return_date_not_found"));
            }
        }

        // Create rejoin request
        $stmt = $pdo->prepare("
            INSERT INTO rejoin_requests 
            (emp_id, vacation_id, requested_rejoin_date, requested_reason, requested_by_emp_id, status)
            VALUES (:emp_id, :vacation_id, :rejoin_date, :reason, :requested_by, 'pending')
        ");
        $stmt->execute([
            ':emp_id' => $emp_id,
            ':vacation_id' => $vacation_id,
            ':rejoin_date' => $rejoin_date,
            ':reason' => $rejoin_reason,
            ':requested_by' => $current_user_id
        ]);
        $rejoin_request_id = (int)$pdo->lastInsertId();
        
        if ($rejoin_request_id <= 0) {
            throw new Exception(__("failed_to_create_rejoin_request"));
        }
        
        // Generate request_inv_no with same pattern as vacation requests
        $request_inv_no = null;
        $max_attempts = 5;
        $attempt = 0;
        while ($attempt < $max_attempts) {
            $attempt++;
            $request_inv_no_candidate = sprintf(
                'RR-%s-%s-%s',
                date('YmdHis'),
                preg_replace('/[^A-Za-z0-9]/', '', (string)$emp_id),
                substr(bin2hex(random_bytes(4)), 0, 4)
            );
            
            // Check if this request_inv_no already exists
            $stmt_chk = $pdo->prepare("SELECT 1 FROM rejoin_requests WHERE request_inv_no = ? LIMIT 1");
            $stmt_chk->execute([$request_inv_no_candidate]);
            if ($stmt_chk->rowCount() === 0) {
                $request_inv_no = $request_inv_no_candidate;
                break;
            }
            usleep(30000);
        }
        
        if (empty($request_inv_no)) {
            throw new Exception(__("failed_to_generate_unique_request_id"));
        }

        // Update rejoin_requests with the request_inv_no
        $stmt_update = $pdo->prepare("
            UPDATE rejoin_requests 
            SET request_inv_no = :inv_no 
            WHERE id = :id
        ");
        $result = $stmt_update->execute([
            ':inv_no' => $request_inv_no,
            ':id' => $rejoin_request_id
        ]);
        
        if (!$result || $stmt_update->rowCount() === 0) {
            throw new Exception(__("failed_to_update_request_inv_no"));
        }

        // ====================================================================
        // CREATE APPROVAL CHAIN USING APPROVAL CHAIN MANAGER
        // ====================================================================
        require_once __DIR__ . '/../ApprovalChainManager.php';
        
        $chainManager = new ApprovalChainManager($conDB, $pdo);
        
        // Get employee department
        $stmt_dept = $pdo->prepare("SELECT dept FROM employees WHERE emp_id = :emp_id LIMIT 1");
        $stmt_dept->execute([':emp_id' => $emp_id]);
        $dept_row = $stmt_dept->fetch(PDO::FETCH_ASSOC);
        $dept_id = $dept_row ? (int)$dept_row['dept'] : null;
        
        // Create approval chain
        $chainResult = $chainManager->createApprovalChain(
            'rejoin_request',
            $request_inv_no,
            $emp_id,
            $dept_id
        );
        
        if (!$chainResult['success']) {
            throw new Exception("Failed to create approval chain");
        }
        
        $firstApprover = $chainResult['first_approver'];

        $pdo->commit();

        // Send email notification to first approver
        if ($firstApprover && !empty($firstApprover['email'])) {
            // Get employee details
            $employee_name = $vacation['name'] ?? 'Employee';
            
            // Prepare email template data
            $base_url = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME'], 3);
            
            $template_data = [
                'EMPLOYEE_NAME' => $employee_name,
                'EMPLOYEE_ID' => $emp_id,
                'REQUESTED_DATE' => $rejoin_date,
                'REASON' => $rejoin_reason,
                'APPROVAL_URL' => $base_url . '/rejoin_approvals.php',
                'APPROVER_NAME' => $firstApprover['name']
            ];

            // Send email if function exists
            if (function_exists('send_approval_email')) {
                $email_subject = "New Rejoin Request from " . $employee_name . " (ID: " . $emp_id . ")";
                send_approval_email(
                    $conDB, 
                    $firstApprover['email'], 
                    $firstApprover['name'], 
                    $email_subject, 
                    'rejoin_request', 
                    $template_data
                );
            }
            
            // Send browser notification to first approver
            $chainManager->notifyApprover(
                $firstApprover['approver_id'],
                'New Rejoin Request',
                $employee_name . ' has submitted a rejoin request requiring your approval.',
                'rejoin_approvals.php'
            );
        }

        send_json_response(
            __("success"),
            __("rejoin_request_submitted_text", "Your rejoin request has been submitted to your supervisor for approval"),
            "success",
            200,
            ['rejoin_request_id' => (int)$rejoin_request_id]
        );

    } catch (Exception $e) {
        // Only rollback if a transaction is active
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        send_json_response(
            __("error"),
            $e->getMessage(),
            "error",
            400
        );
    }
}

// ============================================================================
// REJOIN APPROVAL HANDLER
// ============================================================================
elseif ($ajaxType == 'processRejoinApproval') {
    try {
        $rejoin_request_id = (int)($_POST['rejoin_request_id'] ?? 0);
        $action = escape_string($_POST['action'] ?? '');
        $approval_note = escape_string($_POST['approval_note'] ?? '');
        $adjustment_date = escape_string($_POST['adjustment_date'] ?? '');
        $adjustment_note = escape_string($_POST['adjustment_note'] ?? '');
        $rejection_reason = escape_string($_POST['rejection_reason'] ?? '');

        if (empty($rejoin_request_id) || empty($action)) {
            throw new Exception(__("required_fields_missing"));
        }

        if (!in_array($action, ['approve', 'adjust', 'reject'])) {
            throw new Exception(__("invalid_action_specified"));
        }

        $pdo = getDbConnection();
        $pdo->beginTransaction();

        // Get rejoin request
        $stmt = $pdo->prepare("
            SELECT rr.*, v.return_date, v.vacdays, v.vac_type AS vacation_vac_type, v.fly_type AS vacation_fly_type, e.supervisor_id
            FROM rejoin_requests rr
            JOIN emp_vacation v ON rr.vacation_id = v.id
            JOIN employees e ON rr.emp_id = e.emp_id
            WHERE rr.id = :id
        ");
        $stmt->execute([':id' => $rejoin_request_id]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$request) {
            throw new Exception(__("rejoin_request_not_found"));
        }

        // Get emp_id from rejoin_requests table
        $employee_id = $request['emp_id'];

        // ====================================================================
        // VERIFY APPROVER USING APPROVAL CHAIN MANAGER
        // ====================================================================
        require_once __DIR__ . '/../ApprovalChainManager.php';
        $chainManager = new ApprovalChainManager($conDB, $pdo);
        
        $verification = $chainManager->verifyApprover($request['request_inv_no'], $current_user_id);
        if (!$verification['authorized']) {
            throw new Exception($verification['message']);
        }
        
        $current_level = $verification['level'];
        
        // Check if current user is Finance Manager
        $user_type_stmt = $pdo->prepare("
            SELECT user_type FROM admin_login WHERE emp_id = :emp_id LIMIT 1
        ");
        $user_type_stmt->execute([':emp_id' => $current_user_id]);
        $user_type_row = $user_type_stmt->fetch(PDO::FETCH_ASSOC);
        $is_finance_manager = ($user_type_row && $user_type_row['user_type'] == 'finance');
        
        if ($action === 'approve') {
            // Check if Finance Manager is selecting a payer
            if ($is_finance_manager && isset($_POST['payer_emp_id']) && !empty($_POST['payer_emp_id'])) {
                $payer_emp_id = (int)$_POST['payer_emp_id'];
                
                if ($payer_emp_id <= 0) {
                    throw new Exception(__("invalid_payer_selected", "Invalid payer selected"));
                }
                
                // Use ApprovalChainManager to handle payer selection
                try {
                    $payerSelectionResult = $chainManager->approveWithPayerSelection(
                        $request['request_inv_no'],
                        $current_user_id,
                        $payer_emp_id,
                        $approval_note ?: 'Approved. Finance payer selected for payment processing.'
                    );
                    
                    // Payer is now recorded in request_approvers table (no need to update rejoin_requests)
                    
                    // Notify payer via browser notification
                    $chainManager->notifyApprover(
                        $payer_emp_id,
                        'Rejoin Request - Payment Processing Assignment',
                        'You have been assigned to process payment for rejoin request. Please record the payment amount and proof.',
                        'rejoin_approvals.php'
                    );
                    
                    // Send email to payer
                    if (!empty($payerSelectionResult['payer']['email']) && function_exists('send_approval_email')) {
                        $rejoin_details_stmt = $pdo->prepare("
                            SELECT rr.*, e.name as employee_name 
                            FROM rejoin_requests rr
                            LEFT JOIN employees e ON rr.emp_id = e.emp_id
                            WHERE rr.request_inv_no = :inv_no
                            LIMIT 1
                        ");
                        $rejoin_details_stmt->execute([':inv_no' => $request['request_inv_no']]);
                        $rejoin_details = $rejoin_details_stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($rejoin_details) {
                            $base_url = 'https://hr.almutlaksystem.com';
                            $payer_name = $payerSelectionResult['payer']['name'] ?? 'Finance Staff';
                            
                            $template_data = [
                                'APPROVER_NAME' => $payer_name,
                                'REQUEST_ID' => $request['request_inv_no'],
                                'EMPLOYEE_NAME' => $rejoin_details['employee_name'] ?? 'Employee',
                                'REJOIN_DATE' => $rejoin_details['requested_rejoin_date'] ?? 'N/A',
                                'REQUEST_URL' => $base_url . '/rejoin_approvals.php',
                                'EMAIL_MESSAGE' => 'You have been assigned by Finance Manager to process the payment for this rejoin request. Please record the payment amount, confirm the settlement details, and upload payment proof to complete the transaction.'
                            ];
                            
                            $email_subject = "Rejoin Payment Processing Assignment - " . $request['request_inv_no'];
                            send_approval_email($conDB, $payerSelectionResult['payer']['email'], $payer_name, $email_subject, 'rejoin_request', $template_data);
                        }
                    }
                    
                    // Return early with success response - do NOT call handle_approval_action as it checks for duplicate approvals
                    echo json_encode([
                        'status' => 'success',
                        'message' => __("rejoin_payer_assigned", "Rejoin request approved. Finance payer assigned."),
                        'payer_assigned' => true,
                        'payer_name' => $payerSelectionResult['payer']['name'] ?? 'Unknown'
                    ]);
                    exit;
                } catch (Exception $e) {
                    throw new Exception("Error assigning payer: " . $e->getMessage());
                }
            } else {
                // Normal approval without payer selection
                $approvalResult = $chainManager->processApproval(
                    $request['request_inv_no'],
                    $current_user_id,
                    'approve',
                    $approval_note
                );
                
                if ($approvalResult['is_final']) {
                    // Final approval - complete the request
                    $stmt = $pdo->prepare("
                        UPDATE rejoin_requests 
                        SET 
                            status = 'approved',
                            approved_by_emp_id = :approver_id,
                            approved_at = NOW(),
                            approval_note = :note,
                            final_approved_date = :rejoin_date
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        ':approver_id' => $current_user_id,
                        ':note' => $approval_note,
                        ':rejoin_date' => $request['requested_rejoin_date'],
                        ':id' => $rejoin_request_id
                    ]);

                    // Set employee fly status to 0 (employee has rejoined)
                    $stmt = $pdo->prepare("
                        UPDATE employees 
                        SET fly = 0
                        WHERE emp_id = :emp_id
                    ");
                    $stmt->execute([':emp_id' => $employee_id]);

                    // Mark vacation as reviewed/completed
                    $stmt = $pdo->prepare("
                        UPDATE emp_vacation 
                        SET review = 'C'
                        WHERE id = :vacation_id
                    ");
                    $stmt->execute([':vacation_id' => $request['vacation_id']]);

                    $message = __("rejoin_request_approved_text", "Rejoin request has been approved");
                } else {
                    // More approvals needed
                    $stmt = $pdo->prepare("
                        UPDATE rejoin_requests 
                        SET 
                            status = 'pending',
                            approval_note = :note
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        ':note' => $approval_note,
                        ':id' => $rejoin_request_id
                    ]);
                    
                    // Notify next approver
                    if ($approvalResult['next_approver']) {
                        $chainManager->notifyApprover(
                            $approvalResult['next_approver']['approver_id'],
                            'Rejoin Request Awaiting Approval',
                            'A rejoin request requires your approval.',
                            'rejoin_approvals.php'
                        );
                    }
                    
                    $message = __("rejoin_request_approved_text", "Rejoin request has been approved and forwarded to next approver");
                }
            }

        } elseif ($action === 'adjust') {
            // Allow employee to adjust date within 3-day window
            $from_date = new DateTime($request['requested_rejoin_date']);
            $from_date->modify('-3 days');
            $to_date = new DateTime($request['requested_rejoin_date']);
            $to_date->modify('+3 days');

            // If supervisor selected an adjustment date, use it directly and mark as approved
            if (!empty($adjustment_date)) {
                $adjustDate = new DateTime($adjustment_date);
                // Validate the date is within range
                if ($adjustDate < $from_date || $adjustDate > $to_date) {
                    throw new Exception(__("adjustment_date_out_of_range", "Adjustment date is outside the allowed range"));
                }

                // Adjust with supervisor-selected date
                $stmt = $pdo->prepare("
                    UPDATE rejoin_requests 
                    SET 
                        status = 'approved',
                        approved_by_emp_id = :supervisor_id,
                        approved_at = NOW(),
                        adjustment_allowed = 1,
                        adjustment_from_date = :from_date,
                        adjustment_to_date = :to_date,
                        adjustment_reason_text = :reason,
                        adjustment_submitted_date = :adj_date_submitted,
                        adjustment_submitted_at = NOW(),
                        final_approved_date = :adj_date_final,
                        final_approved_at = NOW(),
                        approval_note = :approval_note
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':supervisor_id' => $current_user_id,
                    ':from_date' => $from_date->format('Y-m-d'),
                    ':to_date' => $to_date->format('Y-m-d'),
                    ':reason' => $adjustment_note,
                    ':adj_date_submitted' => $adjustment_date,
                    ':adj_date_final' => $adjustment_date,
                    ':approval_note' => $adjustment_note,
                    ':id' => $rejoin_request_id
                ]);

                // If supervisor selected a later rejoin date, deduct extra days from balance
                $extra_days = 0;
                if (!empty($request['return_date'])) {
                    $origReturn = new DateTime($request['return_date']);
                    if ($adjustDate > $origReturn) {
                        $extra_days = $origReturn->diff($adjustDate)->days;
                    }
                }

                if ($extra_days > 0) {
                    // Skip balance deduction for Emergency vacations on rejoin
                    $is_emergency = (strtolower($request['vacation_fly_type'] ?? '') === 'emergency');
                    // Extend vacation record and total days
                    $stmtUpdVac = $pdo->prepare("UPDATE emp_vacation SET return_date = :new_date, vacdays = vacdays + :extra WHERE id = :vac_id");
                    $stmtUpdVac->execute([
                        ':new_date' => $adjustment_date,
                        ':extra' => $extra_days,
                        ':vac_id' => $request['vacation_id']
                    ]);
                    if (!$is_emergency) {
                        // Deduct from linked balance row if available
                        $stmtBal = $pdo->prepare("SELECT id, remaining_balance FROM emp_vacation_balance WHERE vac_id = :vac_id LIMIT 1");
                        $stmtBal->execute([':vac_id' => $request['vacation_id']]);
                        if ($balRow = $stmtBal->fetch(PDO::FETCH_ASSOC)) {
                            $newRemaining = max(0, ((float)$balRow['remaining_balance']) - $extra_days);
                            $stmtUpdBal = $pdo->prepare("UPDATE emp_vacation_balance SET remaining_balance = :rem WHERE id = :id");
                            $stmtUpdBal->execute([':rem' => $newRemaining, ':id' => $balRow['id']]);
                        }
                    }
                }

                // Update request_approvers table with approval
                $stmt = $pdo->prepare("
                    UPDATE request_approvers 
                    SET status = 'approved', note = :note, action_date = NOW()
                    WHERE request_inv_no = :inv_no AND request_type_id = 5
                ");
                $stmt->execute([
                    ':note' => 'Approved with adjustment to: ' . $adjustment_date . '. Reason: ' . $adjustment_note,
                    ':inv_no' => $request['request_inv_no']
                ]);

                // Set employee fly status to 0 (employee has rejoined)
                $stmt = $pdo->prepare("
                    UPDATE employees 
                    SET fly = 0
                    WHERE emp_id = :emp_id
                ");
                $stmt->execute([':emp_id' => $employee_id]);

                // Mark vacation as reviewed/completed
                $stmt = $pdo->prepare("
                    UPDATE emp_vacation 
                    SET review = 'C'
                    WHERE id = :vacation_id
                ");
                $stmt->execute([':vacation_id' => $request['vacation_id']]);

                $message = __("rejoin_request_adjusted_and_approved_text", "Rejoin request has been adjusted and approved");
            } else {
                // Allow employee to adjust date themselves
                $stmt = $pdo->prepare("
                    UPDATE rejoin_requests 
                    SET 
                        status = 'adjusted',
                        approved_by_emp_id = :supervisor_id,
                        approved_at = NOW(),
                        adjustment_allowed = 1,
                        adjustment_from_date = :from_date,
                        adjustment_to_date = :to_date,
                        adjustment_reason_text = :reason
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':supervisor_id' => $current_user_id,
                    ':from_date' => $from_date->format('Y-m-d'),
                    ':to_date' => $to_date->format('Y-m-d'),
                    ':reason' => $adjustment_note,
                    ':id' => $rejoin_request_id
                ]);

                // Update request_approvers table (status pending until employee submits adjusted date)
                $stmt = $pdo->prepare("
                    UPDATE request_approvers 
                    SET status = 'pending', note = :note, action_date = NOW()
                    WHERE request_inv_no = :inv_no AND request_type_id = 5
                ");
                $stmt->execute([
                    ':note' => 'Adjustment allowed: ' . $adjustment_note,
                    ':inv_no' => $request['request_inv_no']
                ]);

                $message = __("rejoin_adjustment_allowed_text", "Employee has been allowed to adjust rejoin date within 3 days");
            }


        } else { // reject
            if (empty($rejection_reason)) {
                throw new Exception(__("rejection_reason_required"));
            }

            // Process rejection using chain manager
            $approvalResult = $chainManager->processApproval(
                $request['request_inv_no'],
                $current_user_id,
                'reject',
                'Rejected: ' . $rejection_reason
            );
            
            $stmt = $pdo->prepare("
                UPDATE rejoin_requests 
                SET 
                    status = 'rejected',
                    approved_by_emp_id = :supervisor_id,
                    approved_at = NOW(),
                    rejection_reason = :reason
                WHERE id = :id
            ");
            $stmt->execute([
                ':supervisor_id' => $current_user_id,
                ':reason' => $rejection_reason,
                ':id' => $rejoin_request_id
            ]);

            $message = __("rejoin_request_rejected_text", "Rejoin request has been rejected");
            
            $pdo->commit();

            send_json_response(
                __("rejected"),
                $message,
                "error",
                200
            );
            exit;
        }

        $pdo->commit();

        send_json_response(
            __("success"),
            $message,
            "success",
            200
        );

    } catch (Exception $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        send_json_response(
            __("error"),
            $e->getMessage(),
            "error",
            400
        );
    }
}

// ============================================================================
// SUBMIT ADJUSTED REJOIN DATE
// ============================================================================
elseif ($ajaxType == 'submitAdjustedRejoinDate') {
    try {
        $rejoin_request_id = (int)($_POST['rejoin_request_id'] ?? 0);
        $adjusted_date = escape_string($_POST['adjusted_date'] ?? '');
        $emp_id = (int)($current_user_id);

        if (empty($rejoin_request_id) || empty($adjusted_date)) {
            throw new Exception(__("required_fields_missing"));
        }

        $pdo = getDbConnection();
        $pdo->beginTransaction();

        // Get rejoin request
        $stmt = $pdo->prepare("
            SELECT rr.*, v.return_date, v.vacdays, v.vac_type AS vacation_vac_type, v.fly_type AS vacation_fly_type
            FROM rejoin_requests rr
            JOIN emp_vacation v ON rr.vacation_id = v.id
            WHERE rr.id = :id AND rr.emp_id = :emp_id
        ");
        $stmt->execute([':id' => $rejoin_request_id, ':emp_id' => $emp_id]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$request) {
            throw new Exception(__("rejoin_request_not_found"));
        }

        if ($request['status'] !== 'adjusted' || !$request['adjustment_allowed']) {
            throw new Exception(__("rejoin_adjustment_not_allowed"));
        }

        // Verify adjusted date is within allowed range
        $adjusted = new DateTime($adjusted_date);
        $from = new DateTime($request['adjustment_from_date']);
        $to = new DateTime($request['adjustment_to_date']);

        if ($adjusted < $from || $adjusted > $to) {
            throw new Exception(
                __("adjusted_date_out_of_range", 
                    "Adjusted date must be between " . 
                    $from->format('Y-m-d') . " and " . 
                    $to->format('Y-m-d')
                )
            );
        }

        // Update rejoin request with adjusted date
        $stmt = $pdo->prepare("
            UPDATE rejoin_requests 
            SET 
                adjustment_submitted_date = :submitted_date,
                adjustment_submitted_at = NOW(),
                final_approved_date = :final_date,
                final_approved_at = NOW(),
                status = 'approved'
            WHERE id = :id
        ");
        $stmt->execute([
            ':submitted_date' => $adjusted_date,
            ':final_date' => $adjusted_date,
            ':id' => $rejoin_request_id
        ]);

        // If employee chose a later rejoin date, deduct extra days and extend vacation
        $extra_days = 0;
        if (!empty($request['return_date'])) {
            $origReturn = new DateTime($request['return_date']);
            if ($adjusted > $origReturn) {
                $extra_days = $origReturn->diff($adjusted)->days;
            }
        }

        if ($extra_days > 0) {
            $is_emergency = (strtolower($request['vacation_fly_type'] ?? '') === 'emergency');
            $stmtUpdVac = $pdo->prepare("UPDATE emp_vacation SET return_date = :new_date, vacdays = vacdays + :extra WHERE id = :vac_id");
            $stmtUpdVac->execute([
                ':new_date' => $adjusted_date,
                ':extra' => $extra_days,
                ':vac_id' => $request['vacation_id']
            ]);
            if (!$is_emergency) {
                $stmtBal = $pdo->prepare("SELECT id, remaining_balance FROM emp_vacation_balance WHERE vac_id = :vac_id LIMIT 1");
                $stmtBal->execute([':vac_id' => $request['vacation_id']]);
                if ($balRow = $stmtBal->fetch(PDO::FETCH_ASSOC)) {
                    $newRemaining = max(0, ((float)$balRow['remaining_balance']) - $extra_days);
                    $stmtUpdBal = $pdo->prepare("UPDATE emp_vacation_balance SET remaining_balance = :rem WHERE id = :id");
                    $stmtUpdBal->execute([':rem' => $newRemaining, ':id' => $balRow['id']]);
                }
            }
        }

        // Update request_approvers table to approved
        $stmt = $pdo->prepare("
            UPDATE request_approvers 
            SET status = 'approved', note = CONCAT(COALESCE(note, ''), ' - Employee adjusted date to: ', :date), action_date = NOW()
            WHERE request_inv_no = :inv_no AND request_type_id = 5
        ");
        $stmt->execute([
            ':date' => $adjusted_date,
            ':inv_no' => $request['request_inv_no']
        ]);

        // Set employee fly status to 0 (employee has rejoined)
        $stmt = $pdo->prepare("
            UPDATE employees 
            SET fly = 0
            WHERE emp_id = :emp_id
        ");
        $stmt->execute([':emp_id' => $emp_id]);

        // Mark vacation as reviewed/completed
        $stmt = $pdo->prepare("
            UPDATE emp_vacation 
            SET review = 'C'
            WHERE id = :vacation_id
        ");
        $stmt->execute([':vacation_id' => $request['vacation_id']]);

        $pdo->commit();

        send_json_response(
            __("success"),
            __("adjusted_date_confirmed_text", "Your adjusted rejoin date has been confirmed"),
            "success",
            200
        );

    } catch (Exception $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        send_json_response(
            __("error"),
            $e->getMessage(),
            "error",
            400
        );
    }
}

// Fallback for unknown ajaxType
else {

    send_json_response(__('error'), __('invalid_request_type_specified'), "error", 400);
    exit;
}
