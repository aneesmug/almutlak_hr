<?php
    header('Content-Type: application/json');
	require_once __DIR__ . '/../../includes/db.php';
    include("./../../includes/helper_functions.php"); // --- Helper Function ---
    
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
function get_current_vacation_balance($conDB, $emp_id) {
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

if($ajaxType == 'emp_search') {
    $stmt = mysqli_query($conDB, "SELECT * FROM `employees` WHERE `status`=1 ORDER BY `name` REGEXP '^[^A-Za-z]' ASC, `name` ");
    $name = [];
    while($row = mysqli_fetch_assoc($stmt)) {
        $name[] = $row;
    }
    mysqli_free_result($stmt); // <-- FIX
    $data = [
        'data'      => $name,
        'status'    => 200
    ];
    echo json_encode($data);
} elseif($ajaxType == 'emp_data') {
    $stmt = mysqli_query($conDB, "SELECT 
    `e`.*,
    `d`.`dep_nme` AS `deptnme`
    FROM `employees` `e`
    LEFT JOIN `department` `d` ON `d`.`id` = `e`.`dept` 
    WHERE `e`.`status`=1 AND `e`.`emp_id`=".(int)$_POST['empid']." "); // Cast to int
    $name = [];
    while($row = mysqli_fetch_assoc($stmt)) {
        $name[] = $row;
    }
    mysqli_free_result($stmt); // <-- FIX
    $data = [
        'data'      => $name,
        'status'    => 200
    ];
    echo json_encode($data);
} elseif($ajaxType == 'emp_department') {
    $stmt = mysqli_query($conDB, "SELECT 
    `e`.*,
    `d`.`dep_nme` AS `deptnme`
    FROM `employees` `e`
    LEFT JOIN `department` `d` ON `d`.`id` = `e`.`dept` 
    WHERE `e`.`status`=1 AND `e`.`dept`=".(int)$_POST['dept']." "); // Cast to int
    $name = [];
    while($row = mysqli_fetch_assoc($stmt)) {
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
        if ($emp_id === '') {
            echo json_encode(['ok' => false, 'message' => 'Invalid employee.']);
            exit;
        }

        // Check for any pending vacation request for this employee
        $pending_inv = null;
        $sql = "SELECT `request_inv_no` FROM `emp_vacation` WHERE `emp_id` = ? AND `current_status` = 'pending_approval' ORDER BY `id` DESC LIMIT 1";
        $stmt = mysqli_prepare($conDB, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $emp_id);
            if (mysqli_stmt_execute($stmt)) {
                $res = mysqli_stmt_get_result($stmt);
                if ($row = mysqli_fetch_assoc($res)) {
                    $pending_inv = $row['request_inv_no'] ?? null;
                }
                if ($res) mysqli_free_result($res);
            }
            mysqli_stmt_close($stmt);
        }

        if (!empty($pending_inv)) {
            // Enrich message with current status and approver
            $current_status = null; $current_level = null; $approver_id = null; $approver_name = null;
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
                            $byLevel[$lvl] = [ 'id' => $row_id, 'name' => $name, 'status' => $db_status ];
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
                        $chain[] = [ 'level' => $lvl, 'name' => parseName($data['name']), 'status' => $display_status ];
                    }
                    usort($chain, function($a, $b){ return ($a['level'] ?? 0) <=> ($b['level'] ?? 0); });
                }
            }

            $status_text = 'Pending approval';
            if ($current_status === 'approved') $status_text = 'Approved';
            elseif ($current_status === 'rejected') $status_text = 'Rejected';

            $human_msg = __("you_already_have_a_vacation_request_pending_approval")." (" . htmlspecialchars($pending_inv) . ").";
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
                'message' => $human_msg
            ]);
            exit;
        }

        // Optionally return remaining balance snapshot for UI
        require_once __DIR__ . '/../../includes/get_vacation_balance.php';
        $remaining_balance = get_employee_vacation_balance($conDB, $emp_id);
        echo json_encode([
            'ok' => true,
            'can_apply' => true,
            'remaining_balance' => $remaining_balance
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

        // 2. Validate critical data
        if (empty($emp_id) || empty($vac_type) || empty($first_approver_id)) {
            throw new Exception(__("missing_required_fields_employee,_vacation_type_or_first_approver"));
        }

        // Validate vacation_salary_type - only allow 'payroll' or 'end_of_service'
        if (!empty($vacation_salary_type) && !in_array($vacation_salary_type, ['payroll', 'end_of_service'])) {
            throw new Exception(__("invalid_vacation_salary_type_selected"));
        }

        // 3. Guard: prevent multiple applications while a request is pending final approval
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

        // 4. Generate a cryptographically unique request_inv_no to avoid race conditions
        // Previous method used MAX(id) which is vulnerable under concurrency.
        // Format: VAC-<YYYYMMDDHHMMSS>-<EMPID>-<RND>
        // RND = 4 hex chars from random_bytes. We attempt up to 5 retries (extremely unlikely needed).
        $request_inv_no = null;
        $max_attempts = 5; $attempt = 0; $last_error = null;
        while ($attempt < $max_attempts) {
            $attempt++;
            $request_inv_no_candidate = sprintf(
                'VAC-%s-%s-%s',
                date('YmdHis'),
                preg_replace('/[^A-Za-z0-9]/','', (string)$emp_id),
                substr(bin2hex(random_bytes(4)),0,4)
            );
            $stmt_chk = mysqli_prepare($conDB, "SELECT 1 FROM emp_vacation WHERE request_inv_no = ? LIMIT 1");
            if ($stmt_chk) {
                mysqli_stmt_bind_param($stmt_chk, 's', $request_inv_no_candidate);
                mysqli_stmt_execute($stmt_chk);
                mysqli_stmt_store_result($stmt_chk);
                $exists = mysqli_stmt_num_rows($stmt_chk) > 0;
                mysqli_stmt_close($stmt_chk);
                if (!$exists) { $request_inv_no = $request_inv_no_candidate; break; }
            } else {
                $last_error = mysqli_error($conDB);
            }
            // Small sleep to reduce chance of same second collisions if looping
            usleep(30000);
        }
        if (!$request_inv_no) {
            throw new Exception(__('failed_to_generate_unique_request_inv_no').($last_error?": $last_error":""));
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
        $remaining_balance = get_current_vacation_balance($conDB, $emp_id);

        // Fallback: calculate remaining from contract period if no balance row
        $effective_remaining = $remaining_balance;
        $contract_days = null; $used_days_in_period = 0; $period_start_str = null; $period_end_str = null;
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

        // [NEW] If this is an Encashment request, use the user-entered encash_days
        $is_encashment_request = (trim(strtolower($notes)) === 'encashment') || (trim(strtolower($vac_type)) === 'encashed');
        if ($is_encashment_request) {
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
                send_json_response(
                    __('insufficient_balance'),
                    sprintf(__("you_requested_to_encash_days_but_your_available_balance_is_only_days"), $encash_days, $effective_remaining),
                    "error",
                    400
                );
                exit;
            }
        }

        if ($vacdays > $effective_remaining) {
            $details = '';
            if ($contract_days !== null && $period_start_str && $period_end_str) {
                $details = sprintf(
                    __("you_are_allowed_days_per_contract_period_used_days_period_start_to_period_end"),
                    $contract_days,
                    $used_days_in_period,
                    $period_start_str,
                    $period_end_str
                );
            }
            send_json_response(
                __('insufficient_balance'),
                __("you_requested_days_but_your_available_balance_is_only_days", $vacdays, $effective_remaining) . $details,
                "error",
                400
            );
            exit;
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
        $departure_date_sql = (!empty($departure_date) && $departure_date !== '0000-00-00') ? "'" . mysqli_real_escape_string($conDB, $departure_date) . "'" : 'NULL';
        $arrival_date_sql = (!empty($arrival_date) && $arrival_date !== '0000-00-00') ? "'" . mysqli_real_escape_string($conDB, $arrival_date) . "'" : 'NULL';
        
        // Escape other string values
        $emp_id_esc = mysqli_real_escape_string($conDB, $emp_id);
        $vac_type_esc = mysqli_real_escape_string($conDB, $vac_type);
        $fly_type_esc = mysqli_real_escape_string($conDB, $fly_type);
        $replacement_per_esc = mysqli_real_escape_string($conDB, $replacement_per);
        $start_date_esc = mysqli_real_escape_string($conDB, $start_date);
        $end_date_esc = mysqli_real_escape_string($conDB, $end_date);
        $notes_esc = mysqli_real_escape_string($conDB, $notes);
        $vacation_salary_type_esc = mysqli_real_escape_string($conDB, $vacation_salary_type);
        $attachment_path_esc = mysqli_real_escape_string($conDB, $attachment_path);
        $request_inv_no_esc = mysqli_real_escape_string($conDB, $request_inv_no);
        
        // Determine is_deductible flag
        // If vacation type is "Fly" OR "Local Vacation" with fly_type "annual", set is_deductible = 0
        // This means the employee stays active in payroll with full salary (no deductions)
        $is_deductible = 1; // Default: deductible (affects payroll)
        if (($vac_type === 'Fly' || $vac_type === 'Local Vacation') && $fly_type === 'annual') {
            $is_deductible = 0; // Not deductible: employee remains in full payroll
        }

        
        $sql = "INSERT INTO `emp_vacation` 
                    (`emp_id`, `submitted_by_emp_id`, `vac_type`, `fly_type`, `replacement_person`, `start_date`, `return_date`, `departure_date`, `arrival_date`, `vacdays`, `remarks`, `vacation_salary_type`, `attachment_path`, `encashment_amount`, `request_inv_no`, `is_deductible`, `current_status`, `current_approval_level`) 
                VALUES 
                    ('$emp_id_esc', $submitted_by_val, '$vac_type_esc', '$fly_type_esc', '$replacement_per_esc', '$start_date_esc', '$end_date_esc', $departure_date_sql, $arrival_date_sql, $vacdays_int, '$notes_esc', '$vacation_salary_type_esc', '$attachment_path_esc', $encashment_amount_val, '$request_inv_no_esc', $is_deductible, 'pending_approval', 1)";

        
        
        if (!mysqli_query($conDB, $sql)) {
            
            throw new Exception("INSERT failed (insert vac): " . mysqli_error($conDB));
        }
        
        // Get the inserted ID
        $inserted_id = mysqli_insert_id($conDB);
        

        // 9. Save the approval chain
        $approver_chain = [$first_approver_id];
        if (!save_approval_chain($conDB, $request_inv_no, 'vacation_request', $approver_chain)) {
            throw new Exception(sprintf(__("vacation_request_created_but_failed_to_save_approval_chain"), htmlspecialchars($request_inv_no)));
        }

    // 9b. Pre-build of remaining approvers DISABLED to avoid duplicate approver rows.
    // The next approvers will be appended dynamically on approval using the chain provided by the approver (or auto-detected if none provided).
    // Keeping the old logic wrapped in a conditional that is always false for reference and quick rollback if needed.
    if (false) try {
            $additional_approvers = [];

            // Helper closure to push unique approvers (skip first approver & duplicates)
            $pushApprover = function($empId) use (&$additional_approvers, $first_approver_id) {
                $empId = (int)$empId;
                if ($empId > 0 && $empId !== (int)$first_approver_id && !in_array($empId, $additional_approvers, true)) {
                    $additional_approvers[] = $empId;
                }
            };

            // STEP A: HR Senior BP (user_type = 'hr_senior_bp')
            $res_hr_senior = mysqli_query($conDB, "SELECT e.emp_id FROM employees e JOIN admin_login al ON e.emp_id = al.emp_id WHERE al.user_type='hr_senior_bp' AND e.status=1 ORDER BY e.emp_id ASC LIMIT 1");
            if ($res_hr_senior && ($row_hr_senior = mysqli_fetch_assoc($res_hr_senior))) {
                $pushApprover($row_hr_senior['emp_id']);
            }
            if ($res_hr_senior) mysqli_free_result($res_hr_senior);

            // STEP B: HR BP (user_type = 'hr_bp')
            $res_hr_bp = mysqli_query($conDB, "SELECT e.emp_id FROM employees e JOIN admin_login al ON e.emp_id = al.emp_id WHERE al.user_type='hr_bp' AND e.status=1 ORDER BY e.emp_id ASC LIMIT 1");
            if ($res_hr_bp && ($row_hr_bp = mysqli_fetch_assoc($res_hr_bp))) {
                $pushApprover($row_hr_bp['emp_id']);
            }
            if ($res_hr_bp) mysqli_free_result($res_hr_bp);

            // STEP C: Asset Clearance Teams (based on assigned assets)
            $sql_assets = "SELECT a.name AS asset_name FROM employee_assets ea JOIN assets a ON ea.asset_id = a.id WHERE ea.emp_id = ? AND ea.status = 'Assigned'";
            $stmt_assets = mysqli_prepare($conDB, $sql_assets);
            if ($stmt_assets) {
                // Bind as string (emp_id may be varchar in schema)
                mysqli_stmt_bind_param($stmt_assets, "s", $emp_id);
                mysqli_stmt_execute($stmt_assets);
                $res_assets = mysqli_stmt_get_result($stmt_assets);
                $needs_it = false; $needs_admin = false; $needs_transport = false;
                while ($res_assets && ($asset_row = mysqli_fetch_assoc($res_assets))) {
                    $asset_name = strtolower(trim($asset_row['asset_name']));
                    if (strpos($asset_name, 'laptop') !== false || strpos($asset_name, 'computer') !== false) { $needs_it = true; }
                    if (strpos($asset_name, 'mobile') !== false || strpos($asset_name, 'phone') !== false || strpos($asset_name, 'sim') !== false) { $needs_admin = true; }
                    if (strpos($asset_name, 'car') !== false || strpos($asset_name, 'vehicle') !== false) { $needs_transport = true; }
                }
                if ($res_assets) mysqli_free_result($res_assets);
                mysqli_stmt_close($stmt_assets);

                if (function_exists('get_department_id_by_name') && function_exists('getDeptManager')) {
                    $deptLookup = [ 'IT' => 'Information Technology', 'Administration' => 'Administration', 'Transportation' => 'Transportation' ];
                    if ($needs_it) {
                        $it_dept_id = get_department_id_by_name($conDB, $deptLookup['IT']);
                        if ($it_dept_id) { $it_mgr = getDeptManager($conDB, $it_dept_id); if ($it_mgr && !empty($it_mgr['emp_id'])) $pushApprover($it_mgr['emp_id']); }
                    }
                    if ($needs_admin) {
                        $admin_dept_id = get_department_id_by_name($conDB, $deptLookup['Administration']);
                        if ($admin_dept_id) { $admin_mgr = getDeptManager($conDB, $admin_dept_id); if ($admin_mgr && !empty($admin_mgr['emp_id'])) $pushApprover($admin_mgr['emp_id']); }
                    }
                    if ($needs_transport) {
                        $transport_dept_id = get_department_id_by_name($conDB, $deptLookup['Transportation']);
                        if ($transport_dept_id) { $transport_mgr = getDeptManager($conDB, $transport_dept_id); if ($transport_mgr && !empty($transport_mgr['emp_id'])) $pushApprover($transport_mgr['emp_id']); }
                    }
                }
            }

            // STEP D: HR Payroll (for ALL annual vacations to process overtime/deductions)
            // All vacations must go to HR Payroll regardless of vacation_salary_type
            if ($fly_type === 'annual') {
                $res_hr_payroll = mysqli_query($conDB, "SELECT e.emp_id FROM employees e JOIN admin_login al ON e.emp_id = al.emp_id WHERE al.user_type='hr_payroll' AND e.status=1 ORDER BY e.emp_id ASC LIMIT 1");
                if ($res_hr_payroll && ($row_hr_payroll = mysqli_fetch_assoc($res_hr_payroll))) {
                    $pushApprover($row_hr_payroll['emp_id']);
                }
                if ($res_hr_payroll) mysqli_free_result($res_hr_payroll);
            }

            // STEP E: GR Officer (ONLY if fly_type = 'annual' AND vac_type = 'Fly')
            if ($fly_type === 'annual' && strtolower($vac_type) === 'fly') {
                $res_gr = mysqli_query($conDB, "SELECT e.emp_id FROM employees e JOIN admin_login al ON e.emp_id = al.emp_id WHERE al.user_type='gr_officer' AND e.status=1 ORDER BY e.emp_id ASC LIMIT 1");
                if ($res_gr && ($row_gr = mysqli_fetch_assoc($res_gr))) {
                    $pushApprover($row_gr['emp_id']);
                }
                if ($res_gr) mysqli_free_result($res_gr);
            }

            if (!empty($additional_approvers)) {
                // Fetch request_type_id for vacation_request
                $type_q = mysqli_query($conDB, "SELECT id FROM approval_request_types WHERE type_name='vacation_request' LIMIT 1");
                if ($type_q && ($type_row = mysqli_fetch_assoc($type_q))) {
                    $request_type_id = (int)$type_row['id'];
                    mysqli_free_result($type_q);

                    // Determine next level start (first approver already level 1)
                    $level = 2;
                    mysqli_begin_transaction($conDB);
                    $ok = true;
                    foreach ($additional_approvers as $aid) {
                        $aid_safe = (int)$aid;
                        $sqlIns = "INSERT INTO request_approvers (request_inv_no, request_type_id, approver_id, approval_level, status) VALUES (?, ?, ?, ?, 'awaiting')";
                        $stmtIns = mysqli_prepare($conDB, $sqlIns);
                        if ($stmtIns) {
                            mysqli_stmt_bind_param($stmtIns, "siii", $request_inv_no, $request_type_id, $aid_safe, $level);
                            if (!mysqli_stmt_execute($stmtIns)) {
                                $last_error = mysqli_error($conDB);
                throw new Exception(__('database_error_during_overlap_check') . $last_error);
                                $ok = false; mysqli_stmt_close($stmtIns); break;
                            }
                            mysqli_stmt_close($stmtIns);
                            $level++;
                        } else {
                            $last_error = mysqli_error($conDB);
                throw new Exception(__('database_error_during_overlap_check') . $last_error);
                            $ok = false; break;
                        }
                    }
                    if ($ok) {
                        mysqli_commit($conDB);
                        $last_error = mysqli_error($conDB);
                throw new Exception(__('database_error_during_overlap_check') . $last_error);
                    } else {
                        mysqli_rollback($conDB);
                        $last_error = mysqli_error($conDB);
                throw new Exception(__('database_error_during_overlap_check') . $last_error);
                    }
                } else {
                    if ($type_q) mysqli_free_result($type_q);
                    $last_error = mysqli_error($conDB);
                throw new Exception(__('database_error_during_overlap_check') . $last_error);
                }
            } else {
                $last_error = mysqli_error($conDB);
                throw new Exception(__('database_error_during_overlap_check') . $last_error);
            }
        } catch (Exception $chainEx) {
            $last_error = mysqli_error($conDB);
                throw new Exception(__('database_error_during_overlap_check') . $last_error);
            // Non-fatal: request remains valid with first approver only.
        }

        // 10. Send success response with next approver name (where it will wait)
        $pending_with_text = '';
        if (function_exists('getEmployeeDetailsForApproval') && !empty($first_approver_id)) {
            $first_details = getEmployeeDetailsForApproval($conDB, (int)$first_approver_id);
            if ($first_details && !empty($first_details['name'])) {
                $label = function_exists('__') ? __('pending_with') : 'Pending with';
                $pending_with_text = " $label: " . $first_details['name'] . ".";
                
                // --- [NEW] SEND NOTIFICATION TO FIRST APPROVER ---
                
                
                if (function_exists('create_browser_notification')) {
                    $notification_title = __("new_vacation_request_pending_your_approval");
                    $notification_message = sprintf(__("new_vacation_request_from_employee_pending_your_approval"), htmlspecialchars($request_inv_no), htmlspecialchars($emp_id));
                    $notification_url = "all_applied_vac.php?status=my_pending";
                    $notif_result = create_browser_notification($conDB, $first_approver_id, $notification_title, $notification_message, $notification_url);
                    
                } else {
                    
                }
                
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
                    
                } else {
                    if (empty($first_details['email'])) {
                        
                    }
                    if (!function_exists('send_approval_email')) {
                        
                    }
                }
                // --- [END NEW] ---
            } else {
                
            }
        } else {
            if (!function_exists('getEmployeeDetailsForApproval')) {
                
            }
            if (empty($first_approver_id)) {
                
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
        
        // Payment and travel details (sent by HR Assistant or GR Officer)
        $departure_date = trim($_POST['departure_date'] ?? '');
        $arrival_date = trim($_POST['arrival_date'] ?? '');
        $ticket_pay = (float)($_POST['ticket_pay'] ?? 0);
        $permit_fee = (float)($_POST['permit_fee'] ?? 0);
        
        
        
        
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
        
        // 1. Get the request_inv_no from the vacation ID
        $query_inv = mysqli_query($conDB, "SELECT `request_inv_no` FROM `emp_vacation` WHERE `id` = " . $vacation_id);
        if (!$query_inv || mysqli_num_rows($query_inv) == 0) {
            if($query_inv) mysqli_free_result($query_inv);
            throw new Exception("Invalid Vacation ID.");
        }
        $row_inv = mysqli_fetch_assoc($query_inv);
        $request_inv_no = $row_inv['request_inv_no'];
        mysqli_free_result($query_inv);

        // 2. Call the main approval handler
        $result = handle_approval_action(
            $conDB, 
            $request_inv_no, 
            'vacation_request', 
            $current_user_id, 
            'approve', 
            'Approved', // Default note
            $approver_chain // Pass the dynamic chain
        );
        
        if ($result['status'] == 'error') {
            throw new Exception($result['message']);
        }

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
                    // Get CC recipient details
                    $cc_ids = implode(',', array_map('intval', $hr_team_cc));
                    $sql_cc = "SELECT emp_id, name, email FROM employees WHERE emp_id IN ($cc_ids) AND status = 1";
                    $result_cc = mysqli_query($conDB, $sql_cc);
                    
                    if ($result_cc && mysqli_num_rows($result_cc) > 0) {
                        // Prepare email template data for CC notification
                        $reqType = trim($vac_data['vacation_type'] ?? 'Vacation Request');
                        $subject = "$reqType Approved (CC) - {$vac_data['emp_name']}";
                        
                        // Determine if it's vacation or leave
                        $vac_type_lower = strtolower($vac_data['vacation_type'] ?? '');
                        $base_url = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME'], 3);
                        
                        // Send email to each CC recipient using template system
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
                                    
                                    send_approval_email($conDB, $cc_rec['email'], $cc_rec['name'], $subject, 'vacation_request', $cc_template_data);
                                    
                                } else {
                                    // Fallback log if helper not found
                                    
                                }
                            } else {
                                
                            }
                        }
                        mysqli_free_result($result_cc);
                    }
                }
                mysqli_stmt_close($stmt_vac);
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
            throw new Exception("Your session has expired. Please log in again.");
        }
        if (empty($rejection_note)) {
            throw new Exception(__("rejection_reason_required"));
            
        }

        // 1. Get the request_inv_no from the vacation ID
        $query_inv = mysqli_query($conDB, "SELECT `request_inv_no` FROM `emp_vacation` WHERE `id` = " . $vacation_id);
         if (!$query_inv || mysqli_num_rows($query_inv) == 0) {
            if($query_inv) mysqli_free_result($query_inv);
            throw new Exception(__("invalid_vacation_id"));
        }
        $row_inv = mysqli_fetch_assoc($query_inv);
        $request_inv_no = $row_inv['request_inv_no'];
        mysqli_free_result($query_inv);

        // 2. Call the main approval handler
        $result = handle_approval_action(
            $conDB, 
            $request_inv_no, 
            'vacation_request', 
            $current_user_id, 
            'reject', 
            $rejection_note, // Pass the rejection note
            [] // No chain needed for rejection
        );
        
        if ($result['status'] == 'error') {
            throw new Exception($result['message']);
        }

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
            throw new Exception(__( "failed_to_prepare_employee_fly_update") . ": " . mysqli_error($conDB));
            
        }
        
        mysqli_stmt_bind_param($stmt_fly, "i", $emp_id);
        if (!mysqli_stmt_execute($stmt_fly)) {
            throw new Exception(__( "failed_to_mark_employee_as_returned") . ": " . mysqli_stmt_error($stmt_fly));
            
        }
        mysqli_stmt_close($stmt_fly);

        // Mark vacation as completed so employee can apply for new vacation
        $sql_complete_vac = "UPDATE `emp_vacation` SET `current_status` = 'completed', `arrived_date` = ? WHERE `id` = ?";
        $stmt_complete_vac = mysqli_prepare($conDB, $sql_complete_vac);
        if (!$stmt_complete_vac) {
            throw new Exception(__( "failed_to_mark_vacation_as_completed") . ": " . mysqli_error($conDB));
            
        }
        
        mysqli_stmt_bind_param($stmt_complete_vac, "si", $actual_return_date, $vacation_id);
        if (!mysqli_stmt_execute($stmt_complete_vac)) {
            throw new Exception(__( "failed_to_update_vacation_status") . ": " . mysqli_stmt_error($stmt_complete_vac));
            
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
            throw new Exception(__('database_prepare_error').": " . mysqli_error($conDB));
        }
        
        mysqli_stmt_bind_param($stmt_pay, "ssddi", $departure_date_val, $arrival_date_val, $ticket_pay, $permit_fee, $vacation_id);
        
        
        
        if (!mysqli_stmt_execute($stmt_pay)) {
            
            throw new Exception(__('database_prepare_error').": " . mysqli_stmt_error($stmt_pay));
        }
        
        $rows_affected = mysqli_stmt_affected_rows($stmt_pay);
        
        mysqli_stmt_close($stmt_pay);

        if ($rows_affected > 0) {
            send_json_response("Success!", "payment_details_have_been_updated", "success");

        } else {
            send_json_response("Info", "no_changes_were_made_to_the_payment_details", "info");
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
        
        $sql = "SELECT departure_date, arrival_date FROM emp_vacation WHERE id = ?";
        $stmt = mysqli_prepare($conDB, $sql);
        if (!$stmt) {
            throw new Exception(__('database_prepare_error').": " . mysqli_error($conDB));
        }
        
        mysqli_stmt_bind_param($stmt, "i", $vacation_id);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception(__('database_prepare_error').": " . mysqli_stmt_error($stmt));
        }
        
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        if ($row) {
            echo json_encode([
                'status' => 200,
                'departure_date' => $row['departure_date'] ?? '',
                'arrival_date' => $row['arrival_date'] ?? ''
            ]);
        } else {
            echo json_encode(['status' => 404, 'message' => 'Vacation not found']);
        }
        
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
            throw new Exception(__('database_prepare_error').": " . mysqli_error($conDB));
        }

        mysqli_stmt_bind_param($stmt, 'i', $vacation_id);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception(__('database_prepare_error').": " . mysqli_stmt_error($stmt));
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
            $passport_doc_is_image = in_array($passport_doc_ext, ['jpg','jpeg','png','gif','webp']);
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
            'message' => 'Traveler details fetched successfully.',
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
                throw new Exception("Invalid file type. Only PDF, JPG, and PNG files are allowed.");
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
            throw new Exception(__('database_prepare_error').": " . mysqli_error($conDB));
        }

        mysqli_stmt_bind_param($stmt, 'i', $vacation_id);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception(__('database_prepare_error').": " . mysqli_stmt_error($stmt));
        }

        $result = mysqli_stmt_get_result($stmt);
        $vacation = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$vacation) {
            throw new Exception(__("vacation_request_not_found"));
        }

        // Validate this is an annual fly vacation
        if ($vacation['vac_type'] !== 'Fly' || $vacation['fly_type'] !== 'annual') {
            throw new Exception("Email can only be sent for Annual Fly vacations.");
        }

        // Validate vacation is approved
        if ($vacation['current_status'] !== 'approved') {
            throw new Exception("Vacation must be approved before sending travel email.");
        }

        // Check if flight dates are available
        if (empty($vacation['departure_date']) || empty($vacation['arrival_date'])) {
            throw new Exception("Flight dates (departure and arrival) are required.");
        }

        // Check if email has already been sent
        if (!empty($vacation['travel_email_sent']) && $vacation['travel_email_sent'] == 1) {
            throw new Exception("Travel email has already been sent for this vacation.");
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
                throw new Exception('Failed to store passport document.');
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
                throw new Exception("Passport copy is required. Please attach a passport file.");
            }
        }

        // Get GR Officer email for CC
        $gr_officer_email = get_setting($conDB, 'gr_officer_email');
        if (empty($gr_officer_email)) {
            // Try to get from admin_login table where user_type contains 'gr_officer'
            $gr_query = mysqli_query($conDB, "SELECT email FROM admin_login WHERE user_type LIKE '%gr_officer%' AND email IS NOT NULL AND email != '' LIMIT 1");
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
            throw new Exception("Failed to send email. Please check SMTP settings and traveling company email configuration.");
        }

        // Update database to mark email as sent AND set status to completed
        $update_sql = "UPDATE `emp_vacation` SET `travel_email_sent` = 1, `current_status` = 'completed' WHERE `id` = ?";
        $update_stmt = mysqli_prepare($conDB, $update_sql);
        if ($update_stmt) {
            mysqli_stmt_bind_param($update_stmt, 'i', $vacation_id);
            mysqli_stmt_execute($update_stmt);
            mysqli_stmt_close($update_stmt);
        }

        // Log the action in status history
        $status_note = "Travel company email sent to traveling company";
        if (!empty($gr_officer_email)) {
            $status_note .= " (CC: GR Officer)";
        }
        if ($stored_doc_used) {
            $status_note .= " (Existing passport doc used)";
        } else {
            $status_note .= " (New passport doc uploaded)";
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
            "Success!", 
            "Travel information has been sent to the traveling company" . (!empty($gr_officer_email) ? " with CC to GR Officer." : "."), 
            "success"
        );

    } catch (Exception $e) {
        
        send_json_response("Error", $e->getMessage(), "error", 500);
    }
    exit;
}

// --- [NEW] BLOCK TO HANDLE REPLACING STORED PASSPORT DOCUMENT ONLY ---
// Allows updating the employee's passport copy before sending email
elseif ($ajaxType == 'replacePassportDoc') {
    try {
        $emp_id = trim($_POST['emp_id'] ?? '');
        if (empty($emp_id)) {
            throw new Exception('Employee ID missing.');
        }
        if (!isset($_FILES['passport_file']) || $_FILES['passport_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('No passport file uploaded.');
        }
        $file = $_FILES['passport_file'];
        $allowed_types = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
        $max_size = 5 * 1024 * 1024;
        if ($file['size'] > $max_size) {
            throw new Exception('File too large (max 5MB).');
        }
        $mime = mime_content_type($file['tmp_name']);
        if (!in_array($mime, $allowed_types)) {
            throw new Exception('Invalid file type. Use PDF/JPG/PNG.');
        }
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $new_filename = $emp_id . '_passport_' . time() . '.' . $ext;
        $destination_dir = __DIR__ . '/../../assets/emp_documents/';
        if (!is_dir($destination_dir)) { @mkdir($destination_dir, 0775, true); }
        $destination_path = $destination_dir . $new_filename;
        if (!move_uploaded_file($file['tmp_name'], $destination_path)) {
            throw new Exception('Failed moving uploaded file.');
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
            'type' => 'success',
            'message' => 'Passport document replaced successfully.',
            'passport_doc_url' => './assets/emp_documents/' . $new_filename,
            'passport_doc_ext' => $ext,
            'passport_doc_is_image' => in_array($ext, ['jpg','jpeg','png','gif','webp'])
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

elseif($ajaxType == 'unassign_asset') {
    try {
        if (empty($_POST['asset_record_id']) || empty($_POST['return_date']) || empty($_POST['return_status'])) {
            throw new Exception('Required fields are missing.');
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
                throw new Exception('Server could not save the uploaded file.');
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

        if($stmt->rowCount() > 0){
            send_json_response("Returned!", "Asset has been marked as returned.", "success");
        } else {
            throw new Exception('Could not update the asset record. It may have already been returned.');
        }

    } catch (Exception $e) {
        send_json_response("Error", $e->getMessage(), "error");
    }
    exit;

} elseif($ajaxType == 'get_asset_types') {
    $stmt = mysqli_query($conDB, "SELECT `id`, `name` FROM `assets` ORDER BY `name` ASC");
    $assets = [];
    while($row = mysqli_fetch_assoc($stmt)) {
        $assets[] = $row;
    }
    mysqli_free_result($stmt); // <-- FIX
    echo json_encode(['success' => true, 'assets' => $assets]);
    exit;

} elseif($ajaxType == 'assign_asset') {
    try {
        if (empty($_POST['emp_id']) || empty($_POST['asset_id']) || empty($_POST['assigned_date'])) {
            throw new Exception('Required fields are missing.');
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

        if($stmt->rowCount() > 0){
            send_json_response("Assigned!", "Asset has been assigned successfully.", "success");
        } else {
            throw new Exception('Failed to insert the asset record.');
        }

    } catch (Exception $e) {
        send_json_response("Error", $e->getMessage(), "error");
    }
    exit;
} elseif($ajaxType == 'avatar') {
    $data = $_POST['image'];
    $id = $_POST['id'];
    $emp_id = $_POST['emp_id'];
    $emptype = $_POST['emptype'];
    $emp_name = str_replace(' ','',$_POST['emp_name']);
    list($type, $data) = explode(';', $data);
    list(, $data) = explode(',', $data);
    $data = base64_decode($data);
    $imageName = time() . '.png';
    $filepath = "./../../assets/emp_pics/";
    $filepathup = "./assets/emp_pics/";
    $imagenameu = $emp_id."".$id."".$emp_name."".$imageName;
    if (empty($data) || (isset($data['error']) && $data['error'] == UPLOAD_ERR_NO_FILE)) {
        echo "No Picture upload";
    } else {
        file_put_contents($filepath . $emp_id."".$id."".$emp_name."".$imageName , $data);
        if ($emptype == 'employee') {
            try {
                $stmt = $pdo->prepare("INSERT INTO `employee_temp_contants` (`emp_id`, `type`, `path`) VALUES (:emp_id, 'Profile Picture', :filepath)");
                $stmt->execute([':emp_id' => $emp_id, ':filepath' => $filepathup . $imagenameu]);
            } catch(Exception $e) {
                send_json_response("Database Error", "The catch block is working. The error was: " . $e->getMessage(), "error");
            }
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE `employees` SET `avatar` = :avatar WHERE `id` = :id AND `emp_id` = :emp_id");
                $stmt->execute([':avatar' => $filepathup . $imagenameu, ':id' => $id, ':emp_id' => $emp_id]);
            } catch(Exception $e) {
                send_json_response("Database Error", "The catch block is working. The error was: " . $e->getMessage(), "error");
            }
        }
        if($stmt->rowCount() > 0){
            send_json_response("Success!", "Image Uploaded Successfully.", "success");
        } else {
            send_json_response("Error!", "No changes made to profile picture", "error");
        }
    }
} elseif($ajaxType == 'add_social_links'){
    $emp_id_up = $_POST['emp_id'];
    $link_up = $_POST['link'];
    $social_id_up = $_POST['social_id'];
    $socquery = mysqli_query($conDB, "SELECT * FROM `social` WHERE `emp_id`='".$emp_id_up."' AND `social_id`='".$social_id_up."' ");
    $num_rows = mysqli_num_rows($socquery);
    mysqli_free_result($socquery); // <-- FIX
    if($num_rows == 0){
        $query="INSERT INTO `social` (`emp_id`,`s_link`, `social_id`, `created_at`) VALUES ('".$emp_id_up."', '".$link_up."', '".$social_id_up."', '".date('Y-m-d H:i:s')."')";
        if(mysqli_query($conDB, $query)){
            send_json_response("Success!", "This social link has been added successfully.", "success");
        } else {
            send_json_response("Error!", "User not updated because there are some error.", "error");
        }
    } else {
        send_json_response("Error!", "This Social Media already exist.", "error");

    }
} elseif($ajaxType == 'social_links'){
    $stmt = mysqli_query($conDB, "SELECT * FROM `social_list` WHERE `id` NOT IN (
            SELECT `social_list`.`id` FROM `social_list`
            LEFT JOIN `social` ON `social`.`social_id` = `social_list`.`id`
            WHERE `social`.`emp_id`='".$_POST['emp_id']."'
        )"
    );
    $section_name = [];
    while($row = mysqli_fetch_assoc($stmt)) {
        $section_name[] = $row;
    }
    mysqli_free_result($stmt); // <-- FIX
    $data = [
        'data'      => $section_name,
        'status'    => 200
    ];
    echo json_encode($data);
} elseif($ajaxType == 'add_portfolio'){
    $emp_id = $_POST['emp_id'];
    $title_up = $_POST['title'];
    $description_up = mysqli_real_escape_string($conDB, $_POST['description']);
    $filename_po = null; // Initialize
    if (file_exists($_FILES['file']['tmp_name']) || is_uploaded_file($_FILES['file']['tmp_name'])) {
        $uploadDir = "./../../assets/emp_documents/";
        $fileName = basename($_FILES['file']['name']);
        $tmp_name = $_FILES['file']['tmp_name'];
        $rand = rand(0000,9999).time();
        $file_ext = explode('.',$fileName);
        $file_ext_count=count($file_ext);
        $cnt=$file_ext_count-1;
        $file_extension= $file_ext[$cnt];
        $filename_po = $id.strtoupper($title_up).$rand.".".$file_extension;
        $uploadFilePath = $uploadDir.$filename_po; 
        move_uploaded_file($tmp_name, $uploadFilePath);
    }
    $sql="INSERT INTO `portfolio` (`emp_id`, `title`, `description`, `attachment`, `created_at`) VALUES ('".$emp_id."', '".$title_up."', '".$description_up."', '".$filename_po."', '".date('Y-m-d H:i:s')."')";
    if(mysqli_query($conDB, $sql)){
        send_json_response("Success!", "This portfoilo has been added successfully.", "success");
    } else {
        send_json_response("Error!", "Record not added because there are some error.", "error");
    }
} elseif($ajaxType == 'id_iqama_update'){
    try{
        // BEFORE these lines can even run, or in the file you are including.
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
        // --- END DEBUGGING BLOCK ---
        include("./../../includes/Hijri_GregorianConvert.php");
        $DateConv = new Hijri_GregorianConvert;
        $format="YYYY-MM-DD";
        if ($_POST['iqama_exp']) {
            $iqama_exp = mysqli_real_escape_string($conDB, $_POST['iqama_exp']);
            $iqama_exp_gup = $DateConv->HijriToGregorian($iqama_exp, $format);
            $iqama_exp_g = date("Y-m-d", strtotime($iqama_exp_gup));
        } else{
            $iqama_exp_g = mysqli_real_escape_string($conDB, $_POST['iqama_exp_g']);
            $iqama_exp = $DateConv->GregorianToHijri($iqama_exp_g, $format);
        }
        $stmt = $pdo->prepare("UPDATE `employees` SET `iqama_exp` = :iqama_exp, `iqama_exp_g` = :iqama_exp_g WHERE `id` = :id");
        $stmt->execute([':iqama_exp' => $iqama_exp, ':iqama_exp_g' => $iqama_exp_g, ':id' => $_POST['id']]);
        if($stmt->rowCount() > 0){
            send_json_response("Updated!", "This record has been update successfully.", "success");
        } else {
            send_json_response("Error!", "Record not updated because there are some error.", "error");
        }
    } catch(Exception $e) {
        send_json_response("Database Error", "The catch block is working. The error was: " . $e->getMessage(), "error");
    }
} elseif($ajaxType == 'emp_doc_type'){
    $stmt = mysqli_query($conDB, "SELECT * FROM `docu_type` ORDER BY `duc_type` REGEXP '^[^A-Za-z]' ASC, `duc_type`");
    $sub_type = [];
    while($row = mysqli_fetch_assoc($stmt)) {
        $sub_type[] = $row;
    }
    mysqli_free_result($stmt); // <-- FIX
    $data = [
        'data'      => $sub_type,
        'status'    => 200
    ];
    echo json_encode($data);
} elseif($ajaxType == 'add_emp_document'){
    try {
        // Validate required inputs
        if (!isset($_POST['id'], $_POST['docu_typ'], $_POST['emp_id'], $_POST['emptype'])) {
            throw new Exception('Missing required parameters');
        }
        // Sanitize inputs
        $id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
        $docu_typ_up = $_POST['docu_typ'];
        $emp_id_up = filter_var($_POST['emp_id'], FILTER_SANITIZE_NUMBER_INT);
        $emptype = $_POST['emptype'];
        // File upload handling
        if (!isset($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
            throw new Exception('No file uploaded or upload error');
        }
        $uploadDir = "./../../assets/emp_documents/";
        $filepathup = "./assets/emp_documents/";
        $fileName = basename($_FILES['file']['name']);
        $tmp_name = $_FILES['file']['tmp_name'];
        // Validate file extension
        $file_ext = pathinfo($fileName, PATHINFO_EXTENSION);
        $allowed_extensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
        if (!in_array(strtolower($file_ext), $allowed_extensions)) {
            throw new Exception('Invalid file type. Allowed types: ' . implode(', ', $allowed_extensions));
        }
        // Generate unique filename
        $rand = rand(0000, 9999) . time();
        $filename_po = $id . strtoupper($docu_typ_up) . $rand . "." . $file_ext;
        $uploadFilePath = $uploadDir . $filename_po;

        // Move uploaded file
        if (!move_uploaded_file($tmp_name, $uploadFilePath)) {
            throw new Exception('Failed to move uploaded file');
        }
        // Begin transaction for multiple database operations
        $pdo->beginTransaction();
        if ($emptype == 'employee') {
            // Insert into employee_temp_contants
            $stmt1 = $pdo->prepare("INSERT INTO `employee_temp_contants` (`emp_id`, `type`, `path`) VALUES (:emp_id, 'Employee Documents', :filepath)");
            $stmt1->execute([':emp_id' => $emp_id_up, ':filepath' => $filepathup . $filename_po ]);
            // Insert into emp_docu with status 'I'
            $stmt2 = $pdo->prepare("INSERT INTO `emp_docu` (`emp_id`, `docu_typ`, `path`, `docu_ext`, `pgid`, `status`) VALUES (:emp_id, :docu_typ, :filename, :ext, :pgid, 'I')");
            $stmt2->execute([':emp_id' => $emp_id_up, ':docu_typ' => $docu_typ_up,':filename' => $filename_po,':ext' => $file_ext,':pgid' => $id]);
        } else {
            // Insert into emp_docu without status
            $stmt = $pdo->prepare("INSERT INTO `emp_docu` (`emp_id`, `docu_typ`, `path`, `docu_ext`, `pgid`) VALUES (:emp_id, :docu_typ, :filename, :ext, :pgid)");
            $stmt->execute([':emp_id' => $emp_id_up,':docu_typ' => $docu_typ_up,':filename' => $filename_po,':ext' => $file_ext,':pgid' => $id]);
        }
        // Commit transaction if all queries succeeded
        $pdo->commit();
        send_json_response("Added!", "Record has been added successfully.", "success");
    } catch (PDOException $e) {
        // Rollback transaction on error
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        // Delete uploaded file if database operation failed
        if (isset($uploadFilePath) && file_exists($uploadFilePath)) {
            unlink($uploadFilePath);
        }
        send_json_response("Database Error", "Failed to add record: " . $e->getMessage(), "error");
    } catch (Exception $e) {
        // Delete uploaded file if validation failed
        if (isset($uploadFilePath) && file_exists($uploadFilePath)) {
            unlink($uploadFilePath);
        }
        send_json_response("Error", $e->getMessage(), "error");
    }
} elseif($ajaxType == 'emp_temp_contannt'){
    $ckh_query = mysqli_query($conDB, "SELECT * FROM `employee_temp_contants` WHERE `status` = 'A' AND `emp_id` = '".(int)$_POST['empid']."' AND `id` = '".(int)$_POST['id']."' ");
    $datackh = mysqli_fetch_assoc($ckh_query);
    mysqli_free_result($ckh_query); // <-- FIX

    if ($_POST['notes'] == 'approve') {
        if ($datackh['type'] == 'Profile Picture' ) {
            mysqli_query($conDB, "UPDATE `employees` SET `avatar`='".$datackH['path']."' WHERE `emp_id`='".(int)$_POST['empid']."' ");
            mysqli_query($conDB, "UPDATE `employee_temp_contants` SET `status`='I', `notes` = 'approve' WHERE `emp_id`='".(int)$_POST['empid']."' AND `id` = '".(int)$_POST['id']."' ");
            send_json_response("Approved!", "Record has been approve successfully.", "success");
        } elseif($datackh['type'] == 'Employee Documents'){
            mysqli_query($conDB, "UPDATE `emp_docu` SET `status`='A' WHERE `emp_id`='".(int)$_POST['empid']."' AND `pgid` = '".(int)$_POST['id']."' "); // Corrected WHERE clause
            mysqli_query($conDB, "UPDATE `employee_temp_contants` SET `status`='I', `notes` = 'approve' WHERE `emp_id`='".(int)$_POST['empid']."' AND `id` = '".(int)$_POST['id']."' ");
            send_json_response("Approved!", "Record has been approve successfully.", "success");
        } else {
            mysqli_query($conDB, "UPDATE `employees` SET `".$datackh['type']."` ='".$datackh['path']."' WHERE `emp_id`='".(int)$_POST['empid']."'"); // Used $datackh['type']
            mysqli_query($conDB, "UPDATE `employee_temp_contants` SET `status`='I', `notes` = 'approve' WHERE `emp_id`='".(int)$_POST['empid']."' AND `id` = '".(int)$_POST['id']."' ");
            send_json_response("Approved!", "Record has been approve successfully.", "success");
        }
    } else {
        mysqli_query($conDB, "UPDATE `employee_temp_contants` SET `status`='I', `notes` = '".$_POST['notes']."' WHERE `emp_id`='".(int)$_POST['empid']."' AND `id` = '".(int)$_POST['id']."' ");
        send_json_response("Rejected!", "Record not approve.", "error");
    }
}elseif ($ajaxType == "bank_list") {
    $stmt = mysqli_query($conDB, "SELECT * FROM `bank_list` ORDER BY `name` REGEXP '^[^A-Za-z]' ASC, `name`");
    $name = [];
    while($row = mysqli_fetch_assoc($stmt)) {
        $name[] = $row;
    }
    mysqli_free_result($stmt); // <-- FIX
    $data = [
        'data'      => $name,
        'status'    => 200
    ];
    echo json_encode($data);  
}elseif ($ajaxType == "emp_edit_contannt") {
    $sql = "INSERT INTO `employee_temp_contants` (`emp_id`, `type`, `path`) VALUES ('".(int)$_POST['empid']."', '".$_POST['edit_contant_check']."', '".$_POST[$_POST['edit_contant_check']]."')";
    if(mysqli_query($conDB, $sql)){
         send_json_response("Added!", "Record has been added successfully.", "success");
    } else {
        send_json_response("Error!", "Record not added because there are some error.", "error");
    }
} elseif ($ajaxType == "add_note") {
    $stmt = $pdo->prepare("INSERT INTO `emp_notice` (`emp_id`, `note`, `created_at`) VALUES (:emp_id, :note, :created_at)");
    $dataPost = [
        ':emp_id' => $_POST['empid'],
        ':note' => $_POST['note'],
        ':created_at' => date('Y-m-d H:i:s')
    ];
    if($stmt->execute($dataPost)){
        send_json_response("Added!", "Record has been added successfully.", "success");
    } else {
        send_json_response("Error!", "Record not added because there are some error.", "error");
    }
} elseif($ajaxType == "view_notes"){
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
} elseif(isset($ajaxType) && $ajaxType == 'emp_temp_contant'){ // This is a duplicate ajaxType, which is bad practice
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
                echo json_encode(['type' => 'error', 'title' => 'Not Found', 'message' => 'The original request could not be found.']);
                exit;
            }
            // 2. Determine which column to update in the main 'employees' table
            // This section is now updated to match your 'employees' table schema.emp_temp_contant
            $updateField = '';
            switch ($request['type']) {
                case 'Mobile':          $updateField = 'mobile'; break;
                case 'Email':           $updateField = 'email'; break;
                case 'Passport No':     $updateField = 'passport_number'; break;
                case 'Passport Exp':    $updateField = 'passport_exp'; break;
                case 'Address':         $updateField = 'address'; break;
                case 'Profile Picture': $updateField = 'avatar'; break;
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
            echo json_encode(['type' => 'success', 'title' => 'Approved!', 'message' => 'Employee information has been successfully updated.']);
        } catch (PDOException $e) {
            // In a real app, log the error
            // For debugging, you can output the error message:
            // echo json_encode(['type' => 'error', 'title' => 'Database Error', 'message' => $e->getMessage()]);
            echo json_encode(['type' => 'error', 'title' => 'Database Error', 'message' => 'An error occurred while updating the data.']);
        }
    // --- If the request is REJECTED ---
    } elseif ($approvalAction == 'not_approve') {
        try {
            // Just update the status to 'Rejected' and add the reason
            $finalStmt = $pdo->prepare("UPDATE employee_temp_contants SET status = 'Rejected', notes = ? WHERE id = ?");
            $finalStmt->execute([$notes, $requestId]);
            echo json_encode(['type' => 'success', 'title' => 'Rejected', 'message' => 'The update request has been rejected.']);
        } catch (PDOException $e) {
            echo json_encode(['type' => 'error', 'title' => 'Database Error', 'message' => 'An error occurred while updating the request status.']);
        }
    } else {
        echo json_encode(['type' => 'error', 'title' => 'Invalid Action', 'message' => 'No valid action was submitted.']);
    }
    exit;
} elseif(isset($ajaxType) && $ajaxType == 'create_update_request'){
    // Set the response header to JSON
    header('Content-Type: application/json');

    // Sanitize and retrieve POST data
    $empId = isset($_POST['emp_id']) ? $_POST['emp_id'] : null;
    $type = isset($_POST['type']) ? $_POST['type'] : null;
    $newValue = isset($_POST['new_value']) ? trim($_POST['new_value']) : null;
    $path = null;

    // Basic Validation
    if (empty($empId) || empty($type)) {
        echo json_encode(['type' => 'error', 'title' => 'Missing Information', 'message' => 'Employee ID or request type is missing.']);
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
                echo json_encode(['type' => 'error', 'title' => 'Upload Failed', 'message' => 'Base64 decode failed.']);
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
                echo json_encode(['type' => 'error', 'title' => 'Upload Failed', 'message' => 'Could not save the cropped image.']);
                exit;
            }
        } else {
            echo json_encode(['type' => 'error', 'title' => 'Invalid Image', 'message' => 'The provided image data was not in a valid format.']);
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
            echo json_encode(['type' => 'error', 'title' => 'Upload Failed', 'message' => 'Server could not save the uploaded file.']);
            exit;
        }
    }

    // Final check: ensure a value or a path was provided
    if (empty($newValue) && empty($path)) {
        echo json_encode(['type' => 'error', 'title' => 'Invalid Input', 'message' => 'Please provide a new value or select a file to submit.']);
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
            'title' => 'Request Submitted!',
            'message' => 'Your request to update your ' . strtolower($type) . ' has been sent to HR for approval.'
        ]);

    } catch (Exception $e) {
        echo json_encode(['type' => 'error', 'title' => 'Database Error', 'message' => 'Could not submit your request at this time.']);
    }
    // IMPORTANT: Stop script execution after handling the AJAX request
    exit;
} elseif ($ajaxType == 'get_emp_vacation_details') {
    $empid = $_POST['empid'] ?? null;
    if (!$empid) {
        echo json_encode(['status' => 400, 'message' => 'Employee ID is required.']);
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
        echo json_encode(['status' => 404, 'message' => 'Employee not found or has no vacation contract assigned.']);
    }
    $stmt->close();
    exit;
} elseif($ajaxType == 'emp_search_select2') { // New case for Select2
    $searchTerm = $_POST['searchTerm'] ?? '';
    $stmt = $conDB->prepare("SELECT `emp_id` as `id`, `name` as `text` FROM `employees` WHERE `status`=1 AND (`name` LIKE ? OR `emp_id` LIKE ?) ORDER BY `name` ASC");
    $likeTerm = "%{$searchTerm}%";
    $stmt->bind_param("ss", $likeTerm, $likeTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    $employees = [];
    while($row = $result->fetch_assoc()) {
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
            echo json_encode(['status' => 400, 'message' => 'Missing request_inv_no']);
            exit;
        }
        $stmt = $conDB->prepare("SELECT status, note, emp_name, created_at FROM smt_request_status WHERE inv_no = ? ORDER BY created_at ASC");
        if (!$stmt) {
            echo json_encode(['status' => 500, 'message' => 'DB error: prepare failed']);
            exit;
        }
        $stmt->bind_param('s', $request_inv_no);
        $stmt->execute();
        $res = $stmt->get_result();
        $history = [];
        while ($row = $res->fetch_assoc()) { $history[] = $row; }
        if ($res) { $res->free(); }
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

elseif($ajaxType == 'get_potential_approvers') {
    // This function is defined in helper_functions.php
    $data = get_potential_approvers($conDB);
    echo json_encode(['data' => $data, 'status' => 200]);
    exit;
}

elseif($ajaxType == 'get_department_approvers') {
    // This function is defined in helper_functions.php
    $dept_id = (int)($_POST['dept_id'] ?? 0);
    $data = get_department_approvers($conDB, $dept_id);
    echo json_encode(['data' => $data, 'status' => 200]);
    exit;
}

elseif($ajaxType == 'get_hr_assistants') {
    // This function is defined in helper_functions.php
    $data = get_hr_assistants($conDB);
    echo json_encode(['data' => $data, 'status' => 200]);
    exit;
}

// ================================================================
// --- [NEW] IMPORT OPENING VACATION BALANCE (Manual History) ---
// ================================================================
elseif($ajaxType == 'addManualHistory') {
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
            throw new Exception('Missing required fields.');
        }

        // Optional basic date format check (YYYY-MM-DD)
        $date_re = '/^\d{4}-\d{2}-\d{2}$/';
        if (!preg_match($date_re, $period_start) || !preg_match($date_re, $period_end)) {
            throw new Exception('Invalid date format. Expected YYYY-MM-DD.');
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

        send_json_response('Saved!', 'Manual opening balance saved successfully.', 'success');
    } catch (Exception $e) {
        send_json_response('Error', $e->getMessage(), 'error');
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
        $leave_type = trim($_POST['leave_type'] ?? '');
        $start_date = trim($_POST['start_date'] ?? '');
        $end_date = trim($_POST['end_date'] ?? '');
        $reason = trim($_POST['reason'] ?? '');
        $trip_destination = trim($_POST['trip_destination'] ?? '');
        $accommodation_provided = trim($_POST['accommodation_provided'] ?? '');
        $transportation_provided = trim($_POST['transportation_provided'] ?? '');
        
        // Validation
        if ($empid <= 0) {
            send_json_response('Error', 'Invalid employee ID.', 'error', 400);
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
            send_json_response('Error', 'Invalid leave type selected.', 'error', 400);
            exit;
        }
        
        // Get employee details including gender
        $sql_emp_check = "SELECT `emp_id`, `sex`, `dept`, `supervisor_id` FROM `employees` WHERE `emp_id` = ? LIMIT 1";
        $stmt_emp_check = mysqli_prepare($conDB, $sql_emp_check);
        if (!$stmt_emp_check) {
            send_json_response('Error', 'Failed to fetch employee information.', 'error', 500);
            exit;
        }
        mysqli_stmt_bind_param($stmt_emp_check, "i", $empid);
        mysqli_stmt_execute($stmt_emp_check);
        $result_emp_check = mysqli_stmt_get_result($stmt_emp_check);
        $emp_info = mysqli_fetch_assoc($result_emp_check);
        mysqli_free_result($result_emp_check);
        mysqli_stmt_close($stmt_emp_check);
        
        if (!$emp_info) {
            send_json_response('Error', 'Employee not found.', 'error', 400);
            exit;
        }
        
        $employee_gender = (int)($emp_info['sex'] ?? 0);
        
        // Validate gender-specific leave types
        if ($leave_type === 'Maternity Leave' && $employee_gender !== 2) {
            send_json_response('Error', 'Maternity Leave is only available for female employees.', 'error', 400);
            exit;
        }
        
        if ($leave_type === 'Newborn Leave' && $employee_gender !== 1) {
            send_json_response('Error', 'Newborn Leave is only available for male employees.', 'error', 400);
            exit;
        }
        
        // Validate required fields - ALL fields are now required
        if (empty($start_date)) {
            send_json_response('Error', 'Start date is required.', 'error', 400);
            exit;
        }
        
        if (empty($end_date)) {
            send_json_response('Error', 'End date is required.', 'error', 400);
            exit;
        }
        
        if (empty($reason)) {
            send_json_response('Error', 'Reason/Notes is required for all leave types.', 'error', 400);
            exit;
        }
        
        // Validate Business Trip destination
        if ($leave_type === 'Business Trip' && empty($trip_destination)) {
            send_json_response('Error', 'Destination is required for Business Trip.', 'error', 400);
            exit;
        }
        
        // Validate Business Trip accommodation and transportation
        if ($leave_type === 'Business Trip') {
            if (empty($accommodation_provided) || !in_array($accommodation_provided, ['yes', 'no'])) {
                send_json_response('Error', 'Accommodation provided status is required for Business Trip.', 'error', 400);
                exit;
            }
            if (empty($transportation_provided) || !in_array($transportation_provided, ['yes', 'no'])) {
                send_json_response('Error', 'Transportation provided status is required for Business Trip.', 'error', 400);
                exit;
            }
        }
        
        // Validate attachment - REQUIRED for ALL leave types
        if (!isset($_FILES['attachment']) || $_FILES['attachment']['error'] !== UPLOAD_ERR_OK) {
            send_json_response('Error', 'Attachment is required for all leave types. Please upload a supporting document.', 'error', 400);
            exit;
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
                            'Date Conflict',
                            'Your requested dates (' . htmlspecialchars($start_date) . ' to ' . htmlspecialchars($end_date) . ') overlap with an existing ' . $status_display . ' ' . htmlspecialchars($active_request['vac_type']) . ' (' . htmlspecialchars($active_request['request_inv_no']) . ') from ' . htmlspecialchars($active_request['start_date']) . ' to ' . htmlspecialchars($active_request['return_date']) . '. Please choose different dates.',
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
                            'Cannot Apply During Annual Vacation',
                            'You cannot apply for excuse leave during your ' . $vac_status . ' annual vacation period (' . htmlspecialchars($vacation['start_date']) . ' to ' . htmlspecialchars($vacation['return_date']) . '). Excuse leave must be applied AFTER your vacation return date: ' . htmlspecialchars($vacation['return_date']) . '.',
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
                            'Date Conflict',
                            'You already have a ' . $status_text . ' ' . htmlspecialchars($overlap['vac_type']) . ' request (' . htmlspecialchars($overlap['request_inv_no']) . ') covering ' . htmlspecialchars($overlap['start_date']) . ' to ' . htmlspecialchars($overlap['return_date']) . '. Your requested dates (' . htmlspecialchars($start_date) . ' to ' . htmlspecialchars($end_date) . ') overlap with this existing request. Please choose different dates.',
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
                    $first_approver_label = 'Direct Supervisor';
                }
            }
        }
        
        // Fallback to department manager if no direct supervisor
        if (!$first_approver || empty($first_approver['emp_id'])) {
            $first_approver = getDeptManager($conDB, $emp_dept);
            $first_approver_label = 'Department Manager';
        }
        
        if (!$first_approver || empty($first_approver['emp_id'])) {
            send_json_response('Error', 'No supervisor or department manager found. Please contact HR.', 'error', 400);
            exit;
        }
        
        // Get HR Senior BP (second approver)
        $sql_hr_bp = "SELECT e.emp_id, e.name, al.email 
                      FROM `employees` e 
                      LEFT JOIN `admin_login` al ON e.emp_id = al.emp_id 
                      WHERE al.`user_type` = 'hr_senior_bp' AND e.`status` = 1 
                      ORDER BY e.emp_id ASC 
                      LIMIT 1";
        $result_hr_bp = mysqli_query($conDB, $sql_hr_bp);
        $hr_bp = mysqli_fetch_assoc($result_hr_bp);
        mysqli_free_result($result_hr_bp);
        
        if (!$hr_bp || empty($hr_bp['emp_id'])) {
            send_json_response('Error', 'HR Senior BP not found. Please contact administrator.', 'error', 400);
            exit;
        }
        
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
        
        // Handle file upload if present
        $attachment_path = null;
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../../assets/leave_attachments/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_ext = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png', 'pdf'];
            
            if (!in_array($file_ext, $allowed_exts)) {
                send_json_response('Error', 'Invalid file type. Only JPG, PNG, and PDF are allowed.', 'error', 400);
                exit;
            }
            
            $file_name = 'leave_' . $empid . '_' . time() . '.' . $file_ext;
            $file_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $file_path)) {
                $attachment_path = 'assets/leave_attachments/' . $file_name;
            }
        }
        
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
                (`emp_id`, `vac_type`, `fly_type`, `replacement_person`, `start_date`, `return_date`, `vacdays`, `remarks`, `vacation_salary_type`, `attachment_path`, `request_inv_no`, `is_deductible`, `current_status`, `current_approval_level`, `accommodation_provided`, `transportation_provided`) 
                VALUES (?, ?, '', '', ?, ?, ?, ?, 'payroll', ?, ?, ?, 'pending_approval', 1, ?, ?)";
        
        $stmt = mysqli_prepare($conDB, $sql);
        if (!$stmt) {
            send_json_response('Error', 'Database preparation failed: ' . mysqli_error($conDB), 'error', 500);
            exit;
        }
        
        mysqli_stmt_bind_param($stmt, "isssisssiss", 
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
            send_json_response('Error', 'Failed to submit leave request: ' . mysqli_stmt_error($stmt), 'error', 500);
            mysqli_stmt_close($stmt);
            exit;
        }
        
        mysqli_stmt_close($stmt);
        
        // Save approval chain: [Direct Supervisor/Dept Manager, HR Senior BP]
        // Note: Using 'vacation_request' type since both vacation and leave use emp_vacation table
        $approver_chain = [(int)$first_approver['emp_id'], (int)$hr_bp['emp_id']];
        if (!save_approval_chain($conDB, $request_inv_no, 'vacation_request', $approver_chain)) {
            
        }
        
        // Send notification to first approver (Direct Supervisor or Department Manager)
        if (function_exists('getEmployeeDetailsForApproval') && !empty($first_approver['emp_id'])) {
            $approver_details = getEmployeeDetailsForApproval($conDB, (int)$first_approver['emp_id']);
            if ($approver_details) {
                // --- [UPDATED] SEND ACTUAL NOTIFICATIONS ---
                
                
                if (function_exists('create_browser_notification')) {
                    $notification_title = "New Leave Request";
                    $notification_message = "A new leave request ($request_inv_no) for $leave_type from employee ID $empid is pending your approval.";
                    $notification_url = "all_applied_vac.php?status=my_pending";
                    $notif_result = create_browser_notification($conDB, $first_approver['emp_id'], $notification_title, $notification_message, $notification_url);
                    
                } else {
                    
                }
                
                if (!empty($approver_details['email']) && function_exists('send_approval_email')) {
                    
                    
                    // Get employee name for template
                    $employee_name = 'Employee';
                    $emp_result = mysqli_query($conDB, "SELECT name FROM employees WHERE emp_id = '$empid' LIMIT 1");
                    if ($emp_result && $emp_row = mysqli_fetch_assoc($emp_result)) {
                        $employee_name = $emp_row['name'];
                    }
                    if ($emp_result) mysqli_free_result($emp_result);
                    
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
                    $email_result = send_approval_email($conDB, $approver_details['email'], $approver_details['name'], $email_subject, 'leave_request', $template_data);
                    
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
            'Success', 
            "Leave request submitted successfully! Request No: {$request_inv_no}. Pending approval from your {$first_approver_label}.", 
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
                'message' => 'Employee ID is required.'
            ]);
            exit;
        }
        
        $balance = get_current_vacation_balance($conDB, $emp_id);
        
        if ($balance === null) {
            echo json_encode([
                'status' => 404,
                'balance' => 0,
                'message' => 'Employee not found or balance unavailable.'
            ]);
            exit;
        }
        
        echo json_encode([
            'status' => 200,
            'balance' => $balance,
            'message' => 'Balance retrieved successfully.'
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'status' => 500,
            'balance' => 0,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
    exit;
}

// Fallback for unknown ajaxType
else {
    
    send_json_response("Error", "Invalid request type specified.", "error", 400);
    exit;
}
?>