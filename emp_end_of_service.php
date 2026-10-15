<?php
/****************************************************************
 * MODIFICATION SUMMARY (011-emp_end_of_service.php):
 * - HOUSING IN FULL PACKAGE FOR EOS: When housing allowance is 0, the calculated housing
 *   (Basic / 12) × 2 is included in the full salary package for EOS calculation ONLY.
 *   Example: Basic 2350 + Calculated Housing 391.67 + Food 300 = Total 3041.67 SAR
 * - VACATION CALCULATION: Uses original salary components WITHOUT calculated housing.
 * - actual_salary_base includes calculated housing when housing = 0 (for EOS)
 * - vacation_salary_base uses ONLY actual salary components (no calculated housing)
 * 
 * PREVIOUS MODIFICATION (010-emp_end_of_service.php):
 * - COUNTRY RESTRICTION: Added check to prevent EOS processing for employees
 *   from country ID 121. Form is disabled and error message shown.
 * - HOUSING ALLOWANCE CALCULATION: If employee has no housing allowance (housing = 0),
 *   the system calculates it as (basic/12*2) and adds it as separate benefit.
 * - UPDATED SALARY CALCULATION: Modified to track calculated housing separately.
 * 
 * PREVIOUS MODIFICATION (009-emp_end_of_service.php):
 * - REVERTED & FIXED VACATION LOGIC: Reinstated the use of the
 * `emp_vacation_balance` table to get a carried-over balance.
 * - IMPROVED ACCRUAL CALCULATION: The logic now correctly calculates
 * the vacation accrued only for the final, partial year of service and
 * adds it to the opening balance before subtracting vacation taken
 * during that same period.
 * - ADDED OPENING BALANCE FIELD: A new hidden field has been added
 * to pass the carried-over balance to the JavaScript calculator.
 *
 * MODIFICATION (2025-11-05):
 * - Added logic to pass contract duration (1 or 2 years) to JavaScript.
 * - Modified JavaScript `updateProratedVacation` function to use
 * the correct *annual* vacation rate (e.g., 21 instead of 42)
 * and calculate the daily rate based on 365 days to match
 * the old system's calculation method.
 ****************************************************************/

	require_once __DIR__ . '/includes/init.php';
    require_once __DIR__ . '/includes/session_check.php';
    

	$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
	if(mysqli_num_rows($query) == 1){
	include("./includes/avatar_select.php");
	
	include("./includes/Hijri_GregorianConvert.php");
	$DateConv=new Hijri_GregorianConvert;
	$format="YYYY-MM-DD";
	
    // Use the emp_id from the URL, which is handled by emp_query.php
    $empidget = mysqli_real_escape_string($conDB, $_GET['emp_id']);
	require("./includes/emp_query.php");

	if(mysqli_num_rows($get_emp_data) !== 0){
		$emprow = mysqli_fetch_assoc($get_emp_data);
        
        // Get full salary details for accurate calculations - ONLY active salary (status = 1)
        $get_salary_data = mysqli_query($conDB, "SELECT * FROM `emp_salary` WHERE `emp_id`='".$emprow['empid']."' AND `status` = 1 ORDER BY `id` DESC LIMIT 1");
        $salaryrow = mysqli_fetch_assoc($get_salary_data) ?: [];
        
        // Calculate Total Salary by summing up all components
        $total_salary = 0;
        $two_month_housing = 0; // Separate benefit to add after EOS
        if (!empty($salaryrow)) {
            $basic_salary = (float)($salaryrow['basic'] ?? 0);
            $housing_benefit = (float)($salaryrow['housing'] ?? 0);
            $calculated_housing = 0;

            // If housing benefit is not provided, calculate it as (basic/12)*2 for DISPLAY ONLY
            if ($housing_benefit == 0 && $basic_salary > 0) {
                $calculated_housing = ($basic_salary / 12) * 2;
                $two_month_housing = $calculated_housing; // Store for separate benefit line
            } else {
                $calculated_housing = $housing_benefit;
            }
        }

        // Calculate base salary for EOS calculation (INCLUDES calculated housing when housing is 0)
        $actual_salary_base = 0;
        if (!empty($salaryrow)) {
            $actual_salary_base += (float)($salaryrow['basic'] ?? 0);
            
            // If housing is 0, use calculated housing; otherwise use actual housing
            if ($housing_benefit == 0 && $basic_salary > 0) {
                $actual_salary_base += $calculated_housing; // Add calculated housing to EOS base
            } else {
                $actual_salary_base += (float)($salaryrow['housing'] ?? 0); // Use actual housing
            }
            
            $actual_salary_base += (float)($salaryrow['transport'] ?? 0);
            $actual_salary_base += (float)($salaryrow['food'] ?? 0);
            $actual_salary_base += (float)($salaryrow['misc'] ?? 0);
            $actual_salary_base += (float)($salaryrow['cashier'] ?? 0);
            $actual_salary_base += (float)($salaryrow['fuel'] ?? 0);
            $actual_salary_base += (float)($salaryrow['tel'] ?? 0);
            $actual_salary_base += (float)($salaryrow['guard'] ?? 0);
            $actual_salary_base += (float)($salaryrow['other'] ?? 0);
        }

        // Calculate base salary for VACATION calculation (uses ONLY actual/original salary components, NO calculated housing)
        $vacation_salary_base = 0;
        if (!empty($salaryrow)) {
            $vacation_salary_base += (float)($salaryrow['basic'] ?? 0);
            $vacation_salary_base += (float)($salaryrow['housing'] ?? 0); // ONLY actual housing from salary record
            $vacation_salary_base += (float)($salaryrow['transport'] ?? 0);
            $vacation_salary_base += (float)($salaryrow['food'] ?? 0);
            $vacation_salary_base += (float)($salaryrow['misc'] ?? 0);
            $vacation_salary_base += (float)($salaryrow['cashier'] ?? 0);
            $vacation_salary_base += (float)($salaryrow['fuel'] ?? 0);
            $vacation_salary_base += (float)($salaryrow['tel'] ?? 0);
            $vacation_salary_base += (float)($salaryrow['guard'] ?? 0);
            $vacation_salary_base += (float)($salaryrow['other'] ?? 0);
        }

        // Set total_salary for API (uses EOS base with calculated housing)
        $total_salary = $actual_salary_base;

        // Vacation salary must use employee master total salary (employees.salary)
        $employee_master_total_salary = (float)($emprow['salary'] ?? 0);
        if ($employee_master_total_salary <= 0) {
            // Fallback to salary components total if master salary is missing
            $employee_master_total_salary = $vacation_salary_base;
        }

        // Get the annual vacation entitlement directly from the employee's record.
        // Note: This field (`vacation_days`) stores the TOTAL period entitlement (e.g., 42 for 2 years)
        $annual_vacation_entitlement = (float)($emprow['vacation_days'] ?? 0);

        // --- START: Get current vacation balance (available_balance) and period_end ---
        $current_vacation_balance = 0.0;
        $balance_period_end = null;
        
        $empid_esc = mysqli_real_escape_string($conDB, $emprow['empid']);
        
        // Get current available balance and period_end from emp_vacation_balance
        $vac_balance_q = mysqli_query($conDB, "SELECT available_balance, period_end FROM emp_vacation_balance WHERE emp_id = '{$empid_esc}' ORDER BY id DESC LIMIT 1");
        if ($vac_balance_q && mysqli_num_rows($vac_balance_q) > 0) {
            $vac_row = mysqli_fetch_assoc($vac_balance_q);
            if (isset($vac_row['available_balance']) && is_numeric($vac_row['available_balance'])) {
                $current_vacation_balance = floatval($vac_row['available_balance']);
            }
            if (isset($vac_row['period_end'])) {
                $balance_period_end = $vac_row['period_end'];
            }
        }
        // --- END: Get current vacation balance ---

        // --- START: Get Contract Period Length (1 or 2 years) ---
        $is_two_year_contract = 0;
        if (!empty($emprow['vac_period'])) {
            $vac_period_id = mysqli_real_escape_string($conDB, $emprow['vac_period']);
            $period_q = mysqli_query($conDB, "SELECT period FROM contract_period WHERE id = '{$vac_period_id}' LIMIT 1");
            $period_row = mysqli_fetch_assoc($period_q);
            $contract_period_string = $period_row ? $period_row['period'] : '';
            if (strpos($contract_period_string, '2 Years') !== false) {
                $is_two_year_contract = 1;
            }
        }
        // --- END: Get Contract Period Length ---
        
        // Second query to get EOS-specific details
        $eos_query = mysqli_query($conDB, "SELECT
            `emp_eos`.`created_at` AS `terminationDate`,
            `emp_eos`.`id` AS `eos_id`,
            `emp_eos`.`leaving_reason`
        FROM `emp_eos`
        WHERE `emp_eos`.`emp_id` = '{$emprow['empid']}'
        ORDER BY `emp_eos`.`id` DESC
        LIMIT 1");

        if(mysqli_num_rows($eos_query) > 0) {
            $eos_rec = mysqli_fetch_assoc($eos_query);
            $eos_id = $eos_rec['eos_id'];
            $eos_reason = $eos_rec['leaving_reason'];
            $terminationDate = $eos_rec['terminationDate'];
        } else {
            $eos_id = null;
            $eos_reason = null;
            $terminationDate = null;
        }

        // Query for outstanding loans
        $loan_query = mysqli_query($conDB, "
            SELECT 
                l.id, 
                l.total_payable, 
                COALESCE(SUM(p.amount), 0) as total_paid
            FROM emp_loan l
            LEFT JOIN emp_loan_payments p ON l.id = p.loan_id
            WHERE l.emp_id = '{$emprow['empid']}' AND l.status IN ('approved', 'dept_manager_pending', 'hr_manager_pending', 'finance_manager_pending', 'gm_pending', 'finance_assistant_pending')
            GROUP BY l.id
        ");
        $outstanding_loan = 0;
        if(mysqli_num_rows($loan_query) > 0){
            while($loan_row = mysqli_fetch_assoc($loan_query)){
                $outstanding_loan += $loan_row['total_payable'] - $loan_row['total_paid'];
            }
        }

        // Query for assigned assets
        $assets_query = mysqli_query($conDB, "SELECT COUNT(*) as assigned_assets_count FROM `employee_assets` WHERE `emp_id` = '{$emprow['empid']}' AND `status` = 'Assigned'");
        $assets_count_rec = mysqli_fetch_assoc($assets_query);
        $assigned_assets_count = $assets_count_rec['assigned_assets_count'];

        // Query for company cars currently assigned to this employee as driver.
        // Cars are tracked in `cars_drv`, not `employee_assets`, so the generic
        // assets check above never catches an outstanding car - check separately.
        $cars_query = mysqli_query($conDB, "SELECT `cars_drv`.`id`, `cars_drv`.`car_id`, `cars_drv`.`rcv_date`,
                `cars`.`plate_no`, `cars`.`type` AS `car_type`,
                `car_maker`.`maker` AS `maker_name`, `car_model`.`model`
            FROM `cars_drv`
            JOIN `cars` ON `cars`.`id` = `cars_drv`.`car_id`
            LEFT JOIN `car_maker` ON `car_maker`.`id` = `cars`.`maker_name`
            LEFT JOIN `car_model` ON `car_model`.`id` = `cars`.`model`
            WHERE `cars_drv`.`car_user` = '{$emprow['empid']}' AND `cars_drv`.`status` = 1");
        $assigned_cars = [];
        if ($cars_query) {
            while ($car_row = mysqli_fetch_assoc($cars_query)) {
                $assigned_cars[] = $car_row;
            }
            mysqli_free_result($cars_query);
        }
        $assigned_cars_count = count($assigned_cars);

        // --- NEW: Check for subordinates (employees who have this employee as direct supervisor) ---
        $subordinates_query = mysqli_query($conDB, "SELECT 
            e.emp_id, e.name, e.supervisor_id, d.dep_nme as department
            FROM employees e
            LEFT JOIN department d ON e.dept = d.id
            WHERE e.supervisor_id = '{$emprow['empid']}' AND e.status = 1
            ORDER BY e.name
        ");
        $subordinates = [];
        $has_subordinates = false;
        if ($subordinates_query) {
            while ($sub_row = mysqli_fetch_assoc($subordinates_query)) {
                $sub_row['name'] = parseName($sub_row['name']);
                $subordinates[] = $sub_row;
                $has_subordinates = true;
            }
            mysqli_free_result($subordinates_query);
        }
        // --- END: Subordinates check ---
		
    } else {
		header("Location: ./reg_employee.php");
        exit();
	}

    // --- START: New EOS Calculation Logic (LOCAL JSON-BASED - NO EXTERNAL API) ---
    
    function fetchEndOfServiceReasons() {
        // Load from local JSON file ONLY - No API dependency
        $cache_key = 'eos_reasons_cache';
        
        // Check session cache first for performance
        if (isset($_SESSION[$cache_key])) {
            error_log("EOS Reasons: Using session cache");
            return ['error' => null, 'reasons' => $_SESSION[$cache_key]];
        }
        
        // Load from local JSON file
        $local_json_path = __DIR__ . '/data/eos_reasons.json';
        if (file_exists($local_json_path)) {
            try {
                $json_content = file_get_contents($local_json_path);
                $json_data = json_decode($json_content, true);
                
                if (is_array($json_data) && isset($json_data['EndOfServiceRewardLookUpRs'])) {
                    $reasons_data = $json_data['EndOfServiceRewardLookUpRs']['Body']['EndOfServiceRewardLookUp']['ContractEndReason'] ?? [];
                    
                    if (!empty($reasons_data)) {
                        // Cache for this session
                        $_SESSION[$cache_key] = $reasons_data;
                        error_log("EOS Reasons: Loaded from local JSON (" . count($reasons_data) . " records)");
                        return ['error' => null, 'reasons' => $reasons_data];
                    }
                }
            } catch (Exception $e) {
                error_log("EOS Reasons: Error reading local JSON: " . $e->getMessage());
                return ['error' => __('Error reading termination reasons: ') . $e->getMessage(), 'reasons' => []];
            }
        }
        
        error_log("EOS Reasons: Local JSON file not found at {$local_json_path}");
        return ['error' => __('Termination reasons data file not found'), 'reasons' => []];
    }
    
    /**
     * Fetch contract types from local JSON file ONLY
     * This provides the dropdown options for "Type of Contract"
     * No external API dependency
     */
    function fetchContractTypes() {
        // Check session cache first
        $cache_key = 'contract_types_cache';
        
        if (isset($_SESSION[$cache_key])) {
            error_log("Contract Types: Using session cache");
            return ['error' => null, 'types' => $_SESSION[$cache_key]];
        }
        
        // Load from local JSON file
        $local_json_path = __DIR__ . '/data/contract_types.json';
        if (file_exists($local_json_path)) {
            try {
                $json_content = file_get_contents($local_json_path);
                $json_data = json_decode($json_content, true);
                
                if (is_array($json_data) && isset($json_data['ContractTypes'])) {
                    $contract_types = $json_data['ContractTypes'];
                    
                    if (!empty($contract_types)) {
                        // Cache for this session
                        $_SESSION[$cache_key] = $contract_types;
                        error_log("Contract Types: Loaded from local JSON (" . count($contract_types) . " types)");
                        return ['error' => null, 'types' => $contract_types];
                    }
                }
            } catch (Exception $e) {
                error_log("Contract Types: Error reading local JSON: " . $e->getMessage());
                return ['error' => __('Error reading contract types: ') . $e->getMessage(), 'types' => []];
            }
        }
        
        error_log("Contract Types: Local JSON file not found at {$local_json_path}");
        return ['error' => __('Contract types data file not found'), 'types' => []];
    }
    
    $contractType = $_POST['contract_type'] ?? '1';
    $selectedReasonCode = $_POST['eos_reason'] ?? '';
    $endDateStr = $_GET['end_date'] ?? $_POST['end_date'] ?? '';
    $requestInvNo = $_POST['request_inv_no'] ?? ($_GET['request_inv_no'] ?? '');
    $requestInvNo = trim($requestInvNo);
    $allReasons = [];
    $errors = [];
    $general_error_message = '';

    // Fallback: if request_inv_no not provided, try to get latest resignation request for this employee
    if (empty($requestInvNo) && !empty($emprow['empid'])) {
        $empidEsc = mysqli_real_escape_string($conDB, $emprow['empid']);
        $reqQry = mysqli_query($conDB, "SELECT request_inv_no FROM emp_resignations WHERE emp_id = '{$empidEsc}' ORDER BY updated_at DESC LIMIT 1");
        if ($reqQry && mysqli_num_rows($reqQry) > 0) {
            $reqRow = mysqli_fetch_assoc($reqQry);
            $requestInvNo = trim($reqRow['request_inv_no'] ?? '');
        }
        if ($reqQry) {
            mysqli_free_result($reqQry);
        }
    }
    
    // FINAL FALLBACK: If still no request_inv_no, generate a temporary one for settlement creation
    // Format: EOS-EMPID-TIMESTAMP (e.g., EOS-12345-1739615400)
    if (empty($requestInvNo)) {
        $requestInvNo = 'EOS-' . $emprow['empid'] . '-' . time();
    }

    $reasonsResult = fetchEndOfServiceReasons();
    if ($reasonsResult['error']) {
        $general_error_message = $reasonsResult['error'];
    } else {
        $allReasons = $reasonsResult['reasons'];
    }
    
    // Get pre-fill reason from resignation (if coming from resignation approval)
    $preFillReason = isset($_GET['pre_fill_reason']) ? trim(urldecode($_GET['pre_fill_reason'])) : '';
    
    // If pre-fill reason is provided, try to match it with available EOS reasons
    if (!empty($preFillReason) && empty($selectedReasonCode) && !empty($allReasons)) {
        // If pre-fill reason looks like a code, match by ContractEndReasonCode
        if (is_numeric($preFillReason)) {
            foreach ($allReasons as $reason) {
                $code = isset($reason['ContractEndReasonCode']) ? (string)$reason['ContractEndReasonCode'] : '';
                if ($code !== '' && $code === (string)$preFillReason) {
                    $selectedReasonCode = $reason['ContractEndReasonCode'];
                    break;
                }
            }
        }

        // If still not matched, try to find a matching reason based on the resignation reason text
        if (empty($selectedReasonCode)) {
            foreach($allReasons as $reason) {
                $enDesc = $reason['EnDescription'] ?? '';
                $arDesc = $reason['ArDescription'] ?? '';
                // Match if the pre-fill reason contains or matches the description
                if (stripos($preFillReason, $enDesc) !== false || stripos($preFillReason, $arDesc) !== false ||
                    stripos($enDesc, $preFillReason) !== false || stripos($arDesc, $preFillReason) !== false) {
                    $selectedReasonCode = $reason['ContractEndReasonCode'] ?? '';
                    break;
                }
            }
        }
        // If no exact match found, use the pre-fill reason as display hint in JavaScript
    }

    // Build a human-readable reason label for display/notes (language-aware)
    $preFillReasonLabel = '';
    if (!empty($selectedReasonCode) && !empty($allReasons)) {
        foreach ($allReasons as $reason) {
            if (($reason['ContractEndReasonCode'] ?? '') == $selectedReasonCode) {
                $preFillReasonLabel = ($current_lang === 'ar' && !empty($reason['ArDescription']))
                    ? $reason['ArDescription']
                    : ($reason['EnDescription'] ?? '');
                break;
            }
        }
    } 
    if (empty($preFillReasonLabel) && !empty($preFillReason)) {
        $preFillReasonLabel = $preFillReason;
    }

    // Prefill notes with End of Service Reason when notes are empty
    $notesPrefill = $_POST['notes'] ?? '';
    if (empty($notesPrefill) && !empty($preFillReasonLabel)) {
        $notesPrefill = "End of Service Reason: {$preFillReasonLabel}";
    }

    // Fetch all paid payroll months for the employee to use in JavaScript validation
    $paid_payrolls = [];
    $payroll_query = mysqli_prepare($conDB, "SELECT `month_year` FROM `payrolls` WHERE `emp_id` = ? AND LOWER(TRIM(`status`)) = 'paid'");
    if ($payroll_query) {
        mysqli_stmt_bind_param($payroll_query, "s", $emprow['empid']);
        mysqli_stmt_execute($payroll_query);
        $result = mysqli_stmt_get_result($payroll_query);
        while ($row = mysqli_fetch_assoc($result)) {
            $paid_payrolls[] = trim($row['month_year']);
        }
        mysqli_stmt_close($payroll_query);
    }
    
    // Fetch all deductible vacation records for this employee to be used in JS for client-side calculation
    $vacation_records = [];
    $vac_query = mysqli_query($conDB, "SELECT `start_date`, `vacdays` FROM `emp_vacation` WHERE `emp_id` = '{$emprow['empid']}' AND `is_deductible` = 1");
    if($vac_query){
        while($vac_row = mysqli_fetch_assoc($vac_query)){
            $vacation_records[] = $vac_row;
        }
    }

    // Optional CC recipients (e.g. direct manager) notified after EOS is registered so they
    // can print the report, get it signed by the employee and upload it to the master file.
    // Form posts admin_login ids (several users can share one email); only active users with
    // a valid email are accepted. $ccSelected: [id => info] for re-rendering the field,
    // $ccRecipients: [email => ['name', 'emp_id']] one entry per unique email for sending.
    $ccSelected = [];
    $ccRecipients = [];
    if (isset($_POST['submit']) && !empty($_POST['cc_users']) && is_array($_POST['cc_users'])) {
        $ccIds = array_values(array_unique(array_filter(array_map('intval', $_POST['cc_users']))));
        if (!empty($ccIds)) {
            $ccPlaceholders = implode(',', array_fill(0, count($ccIds), '?'));
            $ccStmt = $conDB->prepare("SELECT id, emp_id, fullname, email FROM admin_login WHERE status = 1 AND id IN ($ccPlaceholders)");
            $ccStmt->bind_param(str_repeat('i', count($ccIds)), ...$ccIds);
            $ccStmt->execute();
            $ccRes = $ccStmt->get_result();
            while ($ccRow = $ccRes->fetch_assoc()) {
                if (!filter_var($ccRow['email'], FILTER_VALIDATE_EMAIL)) {
                    continue;
                }
                $ccSelected[(int) $ccRow['id']] = ['name' => $ccRow['fullname'], 'emp_id' => $ccRow['emp_id'], 'email' => $ccRow['email']];
                if (!isset($ccRecipients[$ccRow['email']])) {
                    $ccRecipients[$ccRow['email']] = ['name' => $ccRow['fullname'], 'emp_id' => $ccRow['emp_id']];
                }
            }
            $ccStmt->close();
        }
    }

    if(isset($_POST['submit'])){
        // Check if employee country is 121 (should not process EOS for this country)
        if ($emprow['country'] == 121) {
            $errors['country'] = "EOS processing is not allowed for employees from country ID: 121.";
        }
        
        // Check if subordinates have been reassigned before allowing EOS
        if ($has_subordinates) {
            $subordinates_updated = isset($_POST['subordinates_updated']) ? $_POST['subordinates_updated'] : '';
            if (empty($subordinates_updated) || $subordinates_updated !== 'true') {
                $errors['subordinates'] = "Cannot process termination. This employee is a direct supervisor to " . count($subordinates) . " employee(s). You must reassign their supervisors before proceeding with EOS.";
            }
        }
        
        if(empty($errors) && ($assigned_assets_count > 0 || $assigned_cars_count > 0)){
            $errors['assets'] = "Cannot process termination. Employee has outstanding assets that must be returned first.";
        } else {
            $contractType = trim($_POST['contract_type'] ?? '');
            $selectedReasonCode = trim($_POST['eos_reason'] ?? '');
            $endDateStr = trim($_POST['end_date'] ?? '');
            $notes = mysqli_real_escape_string($conDB, $_POST['notes']);
            $anul_vac_days = filter_input(INPUT_POST, 'anul_vac_days', FILTER_VALIDATE_FLOAT, ['options' => ['default' => 0]]);
            $deduct = filter_input(INPUT_POST, 'deduct', FILTER_VALIDATE_FLOAT, ['options' => ['default' => 0]]);
            $eos_amount = filter_input(INPUT_POST, 'eos_amount', FILTER_VALIDATE_FLOAT, ['options' => ['default' => 0]]);
            $vacation_salary = filter_input(INPUT_POST, 'anul_vac_salry', FILTER_VALIDATE_FLOAT, ['options' => ['default' => 0]]);
            $other_earnings = filter_input(INPUT_POST, 'other_earnings', FILTER_VALIDATE_FLOAT, ['options' => ['default' => 0]]);
            $net_payment = filter_input(INPUT_POST, 'net_payment', FILTER_VALIDATE_FLOAT, ['options' => ['default' => 0]]);
            $overtime_hours = filter_input(INPUT_POST, 'overtime_hours', FILTER_VALIDATE_FLOAT, ['options' => ['default' => 0]]);
            $overtime_days = filter_input(INPUT_POST, 'overtime_days', FILTER_VALIDATE_FLOAT, ['options' => ['default' => 0]]);
            $absent_days = filter_input(INPUT_POST, 'absent_days', FILTER_VALIDATE_INT, ['options' => ['default' => 0]]);
            $deduction_hours = filter_input(INPUT_POST, 'deduction_hours', FILTER_VALIDATE_FLOAT, ['options' => ['default' => 0]]);
            $gosi_deduction = filter_input(INPUT_POST, 'gosi_deduction', FILTER_VALIDATE_FLOAT, ['options' => ['default' => 0]]);

            // Check if salary is paid for the termination month
            $salaryPaidForTerminationMonth = false;
            if (!empty($endDateStr)) {
                $endDateTime = new DateTime($endDateStr);
                $month_year = $endDateTime->format('Y-m');
                $payroll_check_stmt = $conDB->prepare("SELECT id FROM `payrolls` WHERE `emp_id` = ? AND `month_year` = ? AND LOWER(TRIM(`status`)) = 'paid' LIMIT 1");
                $payroll_check_stmt->bind_param("ss", $emprow['empid'], $month_year);
                $payroll_check_stmt->execute();
                $payroll_check_stmt->store_result();
                if ($payroll_check_stmt->num_rows > 0) {
                    $salaryPaidForTerminationMonth = true;
                }
                $payroll_check_stmt->close();
            }

            if ($salaryPaidForTerminationMonth) {
                $curt_month_days = 0;
                $curt_month_salry = 0.00; // Force salary to 0 if already paid
            } else {
                // Use manually entered working days, fallback to date calculation
                $curt_month_days = filter_input(INPUT_POST, 'curt_month_days', FILTER_VALIDATE_INT, ['options' => ['default' => 0]]);
                $curt_month_salry = filter_input(INPUT_POST, 'curt_month_salry', FILTER_VALIDATE_FLOAT, ['options' => ['default' => 0]]);
            }
            
            if (empty($contractType)) $errors['contract_type'] = 'This field is required.';
            if (empty($selectedReasonCode)) $errors['eos_reason'] = 'This field is required.';
            if (empty($endDateStr)) $errors['end_date'] = 'This field is required.';
            if (empty($notes)) $errors['notes'] = 'This field is required.';
            
            if (!empty($emprow['joining_date']) && !empty($endDateStr)) {
                $startDateTime = new DateTime($emprow['joining_date']);
                $endDateTime = new DateTime($endDateStr);
                if ($startDateTime >= $endDateTime) {
                    $errors['end_date'] = "End date must be after start date.";
                }
            }
            
            $leaving_reason_en = '';
            $leaving_reason_ar = '';
            if (!empty($selectedReasonCode) && !empty($allReasons)) {
                foreach($allReasons as $reason) {
                    if ($reason['ContractEndReasonCode'] == $selectedReasonCode) {
                        $leaving_reason_en = $reason['EnDescription'] ?? 'N/A';
                        $leaving_reason_ar = $reason['ArDescription'] ?? 'N/A';
                        break;
                    }
                }
            }


            if (empty($errors)) {
                // Check if employee already has an EOS record
                $checkEOS = $conDB->prepare("SELECT id FROM `emp_eos` WHERE `emp_id` = ? LIMIT 1");
                $checkEOS->bind_param("i", $emprow['empid']);
                $checkEOS->execute();
                $checkEOS->store_result();
                
                if ($checkEOS->num_rows > 0) {
                    $errors['duplicate_eos'] = 'This employee already has an End of Service record. Cannot create duplicate EOS.';
                    $checkEOS->close();
                } else {
                    $checkEOS->close();
                    
                    $serviceDuration = $startDateTime->diff($endDateTime);
                    $t_years = $serviceDuration->y;
                    $t_months = $serviceDuration->m;
                    $t_days = $serviceDuration->d;

                    // Backward-compatible insert: include other_earnings only when column exists
                    $has_other_earnings_col = false;
                    $col_check = mysqli_query($conDB, "SHOW COLUMNS FROM `emp_eos` LIKE 'other_earnings'");
                    if ($col_check && mysqli_num_rows($col_check) > 0) {
                        $has_other_earnings_col = true;
                    }

                    if ($has_other_earnings_col) {
                        $stmt = $conDB->prepare("INSERT INTO `emp_eos` (`emp_id`, `contract_type`, `eos_reason`, `leaving_reason`, `leaving_reason_ar`, `eos_amount`, `joining_date`, `end_date`, `t_years`, `t_months`, `t_days`, `anul_vac_days`, `anul_vac_salry`, `overtime_hours`, `overtime_days`, `absent_days`, `deduction_hours`, `gosi_deduction`, `deduct`, `other_earnings`, `net_payment`, `notes`, `curt_month_days`, `curt_month_salry`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("sisssdssiiiddddiddddssid", $emprow['empid'], $contractType, $selectedReasonCode, $leaving_reason_en, $leaving_reason_ar, $eos_amount, $emprow['joining_date'], $endDateStr, $t_years, $t_months, $t_days, $anul_vac_days, $vacation_salary, $overtime_hours, $overtime_days, $absent_days, $deduction_hours, $gosi_deduction, $deduct, $other_earnings, $net_payment, $notes, $curt_month_days, $curt_month_salry);
                    } else {
                        $stmt = $conDB->prepare("INSERT INTO `emp_eos` (`emp_id`, `contract_type`, `eos_reason`, `leaving_reason`, `leaving_reason_ar`, `eos_amount`, `joining_date`, `end_date`, `t_years`, `t_months`, `t_days`, `anul_vac_days`, `anul_vac_salry`, `overtime_hours`, `overtime_days`, `absent_days`, `deduction_hours`, `gosi_deduction`, `deduct`, `net_payment`, `notes`, `curt_month_days`, `curt_month_salry`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("sisssdssiiiddddiddddsid", $emprow['empid'], $contractType, $selectedReasonCode, $leaving_reason_en, $leaving_reason_ar, $eos_amount, $emprow['joining_date'], $endDateStr, $t_years, $t_months, $t_days, $anul_vac_days, $vacation_salary, $overtime_hours, $overtime_days, $absent_days, $deduction_hours, $gosi_deduction, $deduct, $net_payment, $notes, $curt_month_days, $curt_month_salry);
                    }
                    $stmt->execute();
                    $eosId = (int) $stmt->insert_id;

                // Keep the CC list on the EOS record. Column is created on first use because
                // sql/ is git-ignored and never reaches the live server.
                if (!empty($ccRecipients) && $eosId > 0) {
                    $ccColCheck = mysqli_query($conDB, "SHOW COLUMNS FROM `emp_eos` LIKE 'cc_emails'");
                    if ($ccColCheck && mysqli_num_rows($ccColCheck) === 0) {
                        mysqli_query($conDB, "ALTER TABLE `emp_eos` ADD COLUMN `cc_emails` TEXT NULL AFTER `notes`");
                    }
                    $ccList = implode(', ', array_keys($ccRecipients));
                    $ccSave = $conDB->prepare("UPDATE `emp_eos` SET `cc_emails` = ? WHERE `id` = ?");
                    $ccSave->bind_param("si", $ccList, $eosId);
                    $ccSave->execute();
                    $ccSave->close();
                }

                $stmt_update = $conDB->prepare("UPDATE `employees` SET `status`='0', `ter_note`=?, `fly`='0', `ter_date`=? WHERE `emp_id`=?");
                $stmt_update->bind_param("sss", $notes, $endDateStr, $emprow['empid']);
                $stmt_update->execute();

                // Keep admin_login in sync: if this employee also has a login record, deactivate it too
                $stmt_admin_login_status = $conDB->prepare("UPDATE `admin_login` SET `status`='0' WHERE `emp_id`=?");
                $stmt_admin_login_status->bind_param("s", $emprow['empid']);
                $stmt_admin_login_status->execute();
                $stmt_admin_login_status->close();

                // Create settlement record after EOS creation (if request_inv_no provided)
                // IMPORTANT: This MUST complete synchronously before page redirect
                $settlementCreated = false;
                if (!empty($requestInvNo)) {
                    error_log("=== EOS SETTLEMENT CREATION START ===");
                    error_log("Request Inv No: {$requestInvNo}");
                    error_log("Employee ID: {$emprow['empid']}");
                    error_log("Settlement Amount: {$net_payment}");
                    error_log("Created by (empid): {$empid}");
                    
                    try {
                        require_once __DIR__ . '/includes/SettlementManager_Corrected.php';
                        $settlementManager = new SettlementManager($conDB, $pdo);
                        $settlementAmount = (float)$net_payment;
                        error_log("Calling createSettlement with: requestInvNo={$requestInvNo}, type=resignation, empId={$emprow['empid']}, amount={$settlementAmount}, userId={$empid}");
                        
                        $settlementResult = $settlementManager->createSettlement(
                            $requestInvNo,
                            'resignation',
                            $emprow['empid'],
                            $settlementAmount,
                            $empid
                        );
                        
                        error_log("Settlement creation result: " . json_encode($settlementResult));
                        if (empty($settlementResult['success'])) {
                            error_log("EOS Settlement creation FAILED for {$requestInvNo}: " . ($settlementResult['message'] ?? 'Unknown error'));
                        } else {
                            error_log("EOS Settlement created SUCCESSFULLY for {$requestInvNo}");
                            error_log("Approval chain and email notifications should have been sent");
                            $settlementCreated = true;
                        }
                    } catch (Exception $e) {
                        error_log("EOS Settlement creation exception for {$requestInvNo}: " . $e->getMessage());
                        error_log("Exception trace: " . $e->getTraceAsString());
                    }
                    error_log("=== EOS SETTLEMENT CREATION END ===");
                } else {
                    error_log("WARNING: No request_inv_no provided, settlement was NOT created");
                }
                
                // mysqli_query($conDB, "INSERT INTO `activity_log` (`user_editor`,`page`,`pg_id`,`reg_date`) VALUES ('".$username."','emp_end_of_service','".$_GET['emp_id']."','".date("c")."')");
                
                // Build success message with settlement info
                $settlementMsg = "";
                if ($settlementCreated && !empty($requestInvNo)) {
                    $settlementMsg = "<br><br><div class='text-success'><i class='fa fa-check-circle'></i> <strong>Settlement {$requestInvNo} created</strong> and will be sent to the next approver for processing.</div>";
                } elseif (!empty($requestInvNo)) {
                    $settlementMsg = "<br><br><div class='text-info'><i class='fa fa-info-circle'></i> <strong>Settlement {$requestInvNo}</strong> processing initiated - approval notifications will be sent.</div>";
                }
                
                // Notify CC recipients: print the EOS report, get it signed, upload to master file
                $ccMsg = "";
                if (!empty($ccRecipients)) {
                    // Email template: includes/PHPMailerMaster/eos_notification_email_template.html
                    $baseUrl = rtrim(get_base_url(), '/');
                    $empIdUrl = urlencode($emprow['empid']);
                    $registeredBy = $username;
                    $byStmt = $conDB->prepare("SELECT fullname FROM admin_login WHERE id_iqama = ? LIMIT 1");
                    $byStmt->bind_param("s", $username);
                    $byStmt->execute();
                    $byRow = $byStmt->get_result()->fetch_assoc();
                    $byStmt->close();
                    if (!empty($byRow['fullname'])) {
                        $registeredBy = $byRow['fullname'];
                    }
                    $ccTemplateData = [
                        'REQUEST_ID' => $requestInvNo,
                        'EMPLOYEE_NAME' => $emprow['name'] ?? '',
                        'EMPLOYEE_ID' => $emprow['empid'],
                        'DEPARTMENT' => $emprow['deptnme'] ?? 'N/A',
                        'JOB_TITLE' => $emprow['jobname'] ?? 'N/A',
                        'JOINING_DATE' => $emprow['joining_date'] ?? 'N/A',
                        'END_DATE' => $endDateStr,
                        'EOS_REASON' => trim($leaving_reason_en . ' | ' . $leaving_reason_ar, ' |'),
                        'SUBMITTED_BY' => $registeredBy,
                        'REQUEST_URL' => $baseUrl . '/emp_end_of_service.php?emp_id=' . $empIdUrl . '&request_inv_no=' . urlencode($requestInvNo),
                        'PRINT_URL' => $baseUrl . '/end_of_service_print.php?emp_id=' . $empIdUrl,
                        'MASTER_FILE_URL' => $baseUrl . '/view_employee.php?emp_id=' . $empIdUrl,
                    ];
                    $ccSubject = "End of Service Registered - {$emprow['name']} ({$emprow['empid']})";
                    $ccSent = [];
                    foreach ($ccRecipients as $ccEmail => $ccInfo) {
                        $ccTemplateData['APPROVER_NAME'] = $ccInfo['name'];
                        if (send_approval_email($conDB, $ccEmail, $ccInfo['name'], $ccSubject, 'eos_notification', $ccTemplateData)) {
                            $ccSent[] = $ccEmail;
                        } else {
                            error_log("EOS CC notification failed for {$ccEmail} (emp {$emprow['empid']})");
                        }
                    }
                    if (!empty($ccSent)) {
                        $ccMsg = "<br><div class='text-success'><i class='fa fa-envelope'></i> " . __('eos_cc_notified', 'Notification sent to') . ": " . htmlspecialchars(implode(', ', $ccSent)) . "</div>";
                    }
                    if (count($ccSent) < count($ccRecipients)) {
                        $ccMsg .= "<br><div class='text-warning'><i class='fa fa-exclamation-triangle'></i> " . __('eos_cc_failed', 'Some CC notifications could not be sent. Check SMTP settings.') . "</div>";
                    }
                }

                $error_1 = "<div><strong>".__('Successfully!')."</strong> ".__('Employee End of Service has been registered.').$settlementMsg.$ccMsg."</div>";
                
                // CRITICAL FIX: Do NOT redirect immediately - wait for settlement to complete
                // Use JavaScript to delay redirect and show proper feedback
                $_SESSION['eos_completion_time'] = time();
                $_SESSION['eos_settlement_created'] = $settlementCreated;
                $_SESSION['eos_request_inv'] = $requestInvNo;
                }
            }
        }
    }

    $filteredReasons = [];
    if (!empty($allReasons)) {
        foreach ($allReasons as $reason) {
            if (isset($reason['ContractTypeCode']) && $reason['ContractTypeCode'] == $contractType) {
                $filteredReasons[] = $reason;
            }
        }
        usort($filteredReasons, function($a, $b) {
            return intval($a['ContractEndReasonCode'] ?? 0) - intval($b['ContractEndReasonCode'] ?? 0);
        });
    }

    $empIdParam = htmlspecialchars($_GET['emp_id'] ?? '', ENT_QUOTES);
    $clearance_pending = $assigned_assets_count + $assigned_cars_count;
    $form_blocked = $clearance_pending > 0;
    $other_allowances = ($salaryrow['cashier'] ?? 0) + ($salaryrow['fuel'] ?? 0) + ($salaryrow['tel'] ?? 0) + ($salaryrow['guard'] ?? 0) + ($salaryrow['other'] ?? 0);
    $housing_is_zero = ($salaryrow['housing'] ?? 0) == 0;
?>
	<!doctype html>
	<html lang="<?=$current_lang?>" dir="<?=($is_rtl) ? 'rtl' : 'ltr'?>">
	<head>
		<meta charset="utf-8" />
		<title><?= $site_title ?> - <?=__('End of Service Settlement');?></title>
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
		<meta content="Anees Afzal" name="author" />
		<meta http-equiv="X-UA-Compatible" content="IE=edge" />
		<link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">
		<link href="./plugins/select2/css/select2.min.css" rel="stylesheet" />

        <!-- App css -->
        <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
		<link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/smart_request.css?v=<?= @filemtime(__DIR__ . '/assets/css/smart_request.css') ?>" rel="stylesheet" type="text/css" />
		<script src="assets/js/modernizr.min.js"></script>
        <?php if ($is_rtl): ?>
            <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
        <?php endif; ?>
        <style>
            .eos-page .sr-head h1 i { color: var(--sr-accent); margin-inline-end: 6px; }
            .eos-page .eos-person { display: flex; align-items: center; gap: 16px; min-width: 0; }
            .eos-page .eos-avatar {
                width: 68px; height: 68px; flex: 0 0 auto; border-radius: 50%; object-fit: cover;
                border: 3px solid var(--sr-surface); box-shadow: 0 0 0 1px var(--sr-border), 0 4px 14px rgba(15, 23, 42, .08);
            }
            .eos-page .eos-person .sr-hero-title { margin: 0 0 6px; }
            .eos-page .sr-hero-amount .sr-amount-value.is-neg { color: var(--tone-red-fg); }
            .eos-page .sr-hero-amount .sr-amount-sub { margin-top: 2px; font-size: 12px; color: var(--sr-muted); }
            .eos-page .sr-stats { margin: 0 0 20px; }
            .eos-page .sr-stat-value small { font-size: 12px; font-weight: 600; color: var(--sr-muted); }
            .eos-page .sr-stat.is-green .sr-stat-value { color: var(--tone-green-fg); }
            .eos-page .sr-card + .sr-card, .eos-page .sr-card + .sr-notice, .eos-page .sr-notice + .sr-card { margin-top: 20px; }
            .eos-page .sr-notice .sr-btn { margin-inline-start: auto; flex: 0 0 auto; }
            .eos-page .sr-notice > div { flex: 1 1 auto; }
            .eos-page fieldset { min-width: 0; margin: 0; padding: 0; border: 0; }
            .eos-page fieldset[disabled] .sr-fsec { opacity: .55; }
            .eos-page .eos-contract { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
            .eos-page .sr-form .sr-choice span { padding: 8px 12px; height: 38px; }
            .eos-page .eos-fsec-earn .sr-fsec-head i { color: var(--tone-green-fg); }
            .eos-page .eos-fsec-deduct .sr-fsec-head i { color: var(--tone-red-fg); }
            .eos-page .eos-amt { display: block; margin-top: 4px; font-size: 11.5px; font-weight: 700; font-variant-numeric: tabular-nums; }
            .eos-page .eos-amt.is-plus { color: var(--tone-green-fg); }
            .eos-page .eos-amt.is-minus { color: var(--tone-red-fg); }
            .eos-page .sr-form .form-control.text-danger { color: var(--tone-red-fg) !important; }
            .eos-page .sr-fhint.is-warn { color: var(--tone-red-fg); }
            .eos-page .sr-kv .row-kv.is-flag { margin: 0 -8px; padding-inline: 8px; border-radius: 8px; background: var(--tone-amber-bg); }
            .eos-page .sr-kv .row-kv.is-total { margin-top: 4px; padding-top: 10px; border-top: 1px solid var(--sr-border-strong); border-bottom: 0; }
            .eos-page .sr-kv .row-kv.is-total dt { color: var(--sr-text); font-weight: 700; }
            .eos-page .sr-kv .row-kv.is-total dd { color: var(--tone-green-fg); font-weight: 800; }
            .eos-page .sr-kv dd small { font-weight: 600; color: var(--sr-muted); }
            .eos-page .eos-sum input {
                width: 150px; padding: 0; border: 0; outline: 0; background: transparent; text-align: end;
                font: inherit; font-weight: 700; color: inherit; font-variant-numeric: tabular-nums;
            }
            .eos-page .eos-sum .row-kv.is-earn dd { color: var(--tone-green-fg); }
            .eos-page .eos-sum .row-kv.is-deduct dd { color: var(--tone-red-fg); }
            .eos-page .eos-net { margin-top: 14px; }
            .eos-page .eos-net input { width: 170px; font-size: 20px; font-weight: 800; }
            .eos-page .eos-submit { margin-top: 14px; }
            .eos-page .eos-submit .sr-btn { width: 100%; justify-content: center; height: 44px; font-size: 14px; }
            .eos-page .eos-submit .sr-btn[disabled] { opacity: .5; cursor: not-allowed; }
            .eos-page .eos-submit .sr-fhint { text-align: center; }
            .eos-page .sr-form .select2-container--default .select2-selection--multiple {
                min-height: 38px; border-radius: 9px; border: 1px solid var(--sr-border-strong); background: var(--sr-surface-2); padding: 2px 6px;
            }
            .eos-page .sr-form .select2-container--default.select2-container--focus .select2-selection--multiple { border-color: var(--sr-accent); box-shadow: 0 0 0 3px rgba(99, 102, 241, .16); }
            .eos-page .sr-form .select2-container--default .select2-selection--multiple .select2-selection__choice {
                background: var(--sr-accent-soft); border: 1px solid transparent; color: var(--sr-accent-strong); border-radius: 7px; font-size: 12px; font-weight: 600; padding: 1px 8px; margin-top: 4px;
            }
            .eos-page .sr-form .select2-search--inline .select2-search__field { color: var(--sr-text); margin-top: 6px; font-size: 13px; height: 24px; }
            .eos-page .eos-actions-cell { display: flex; flex-wrap: wrap; gap: 6px; justify-content: flex-end; }
            .eos-page .eos-actions-cell .sr-btn[disabled] { opacity: .5; cursor: not-allowed; }
            .eos-sub-popup .eos-sub-scroll { max-height: 340px; overflow-y: auto; border: 1px solid var(--sr-border); border-radius: 12px; }
            .eos-sub-popup .eos-sub-scroll thead th { position: sticky; top: 0; z-index: 1; }
            .eos-sub-popup .eos-sub-scroll input[type=checkbox] { width: 16px; height: 16px; cursor: pointer; accent-color: var(--sr-accent); }
            .eos-sub-popup .sr-fsec { margin: 14px 0 0; }
        </style>
		<script> window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;</script>
	</head>
	<body class="enlarged" data-keep-enlarged="true">
		<div id="wrapper">
			<div class="left side-menu">
				<div class="slimscroll-menu" id="remove-scroll">
					<div class="topbar-left">
						<a href="dashboard.php" class="logo">
							<span><img src="<?=get_setting($conDB, 'logo')?>" alt="" height="22"></span>
							<i><img src="<?=get_setting($conDB, 'white_logo')?>" alt="" height="28"></i>
						</a>
					</div>
					<?php include("./includes/main_menu.php"); ?>
					<div class="clearfix"></div>
				</div>
			</div>
			<div class="content-page">
				<?php include("./includes/topbar.php"); ?>
				<div class="content sr-page eos-page">
					<div class="container-fluid">

                        <div class="sr-head">
                            <div>
                                <h1><i class="mdi mdi-exit-to-app"></i><?=__('FINAL SETTLEMENT');?></h1>
                                <p><?=__('Final Settlement Subheading');?></p>
                            </div>
                            <div class="sr-head-actions">
                                <a href="./view_employee.php?emp_id=<?= $empIdParam ?>" class="sr-btn sr-btn-ghost"><i class="mdi mdi-arrow-left"></i> <?=__('employee_profile', 'Employee Profile');?></a>
                                <?php if ($eos_id): ?>
                                    <a href="./end_of_service_print.php?emp_id=<?= $empIdParam ?>" target="_blank" class="sr-btn sr-btn-primary"><i class="mdi mdi-printer"></i> <?=__('Print Report');?></a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($eos_id): ?>
                            <div class="sr-notice tone-red">
                                <i class="mdi mdi-account-off"></i>
                                <div>
                                    <strong><?=__('Terminated on:');?></strong> <?= format_safe_date($terminationDate ?? null, 'd M, Y') ?>
                                    &nbsp;&middot;&nbsp; <strong><?=__('Reason:');?></strong> <?= htmlspecialchars($eos_reason ?? ''); ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Employee hero -->
                        <div class="sr-card sr-hero">
                            <div class="sr-hero-top">
                                <div class="eos-person">
                                    <img class="eos-avatar" src="<?= getAvatarImagePath($emprow['avatar'] ?? '', $emprow['sex'] ?? 1); ?>" alt="<?= htmlspecialchars($emprow['name'] ?? ''); ?>">
                                    <div style="min-width: 0;">
                                        <h2 class="sr-hero-title"><?= htmlspecialchars($emprow['name'] ?? ''); ?></h2>
                                        <div class="sr-hero-tags">
                                            <span class="sr-chip sr-mono"><i class="mdi mdi-account-card-details"></i> <?= htmlspecialchars($emprow['empid'] ?? ''); ?></span>
                                            <?php if ($eos_id): ?>
                                                <span class="sr-pill tone-red"><span class="sr-dot"></span><?=__('terminated', 'Terminated');?></span>
                                            <?php else: ?>
                                                <span class="sr-pill tone-amber"><span class="sr-dot"></span><?=__('eos_pending_settlement', 'Pending settlement');?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php if (!$eos_id): ?>
                                    <div class="sr-hero-amount">
                                        <div class="sr-amount-label"><?=__('Total Net Payment');?></div>
                                        <div class="sr-amount-value"><span id="net_payment_hero">0.00</span> <small><?=__('SAR');?></small></div>
                                        <div class="sr-amount-sub"><?=__('eos_live_estimate', 'Live estimate - updates as you fill the form');?></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="sr-meta">
                                <div class="sr-meta-item">
                                    <div class="sr-meta-label"><i class="mdi mdi-briefcase"></i> <?=__('Department');?></div>
                                    <div class="sr-meta-value"><?= htmlspecialchars($emprow['deptnme'] ?? '-'); ?></div>
                                </div>
                                <div class="sr-meta-item">
                                    <div class="sr-meta-label"><i class="mdi mdi-account-circle"></i> <?=__('Job Title');?></div>
                                    <div class="sr-meta-value"><?= htmlspecialchars($emprow['jobname'] ?? '-'); ?></div>
                                </div>
                                <div class="sr-meta-item">
                                    <div class="sr-meta-label"><i class="mdi mdi-calendar-check"></i> <?=__('Joining Date');?></div>
                                    <div class="sr-meta-value"><?= format_safe_date($emprow['joining_date'] ?? null, 'd M, Y'); ?></div>
                                </div>
                                <div class="sr-meta-item">
                                    <div class="sr-meta-label"><i class="mdi mdi-account-card-details"></i> <?=__('Iqama / ID');?></div>
                                    <div class="sr-meta-value sr-mono"><?= htmlspecialchars($emprow['iqama'] ?? '-'); ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick stats -->
                        <div class="sr-stats" style="margin-top: 20px;">
                            <div class="sr-stat is-sky">
                                <div class="sr-stat-label"><?=__('Total Remaining Vacation Days');?> <i class="mdi mdi-beach"></i></div>
                                <div class="sr-stat-value"><span id="vacation_days_summary"><?= htmlspecialchars(number_format($current_vacation_balance, 2, '.', '')); ?></span> <small><?=__('days');?></small></div>
                                <div class="sr-stat-sub"><?=__('eos_vac_to_lwd', 'Projected to last working day');?></div>
                            </div>
                            <div class="sr-stat <?= $outstanding_loan > 0 ? 'is-red' : 'is-green' ?>">
                                <div class="sr-stat-label"><?=__('Outstanding Loan Balance');?> <i class="mdi mdi-cash-multiple"></i></div>
                                <div class="sr-stat-value"><?= htmlspecialchars(number_format($outstanding_loan, 2)); ?> <small><?=__('SAR');?></small></div>
                                <div class="sr-stat-sub"><?=__('eos_loan_prefilled', 'Pre-filled into deductions');?></div>
                            </div>
                            <div class="sr-stat is-green">
                                <div class="sr-stat-label"><?=__('Total Salary Base (for EOS calculation)');?> <i class="mdi mdi-wallet"></i></div>
                                <div class="sr-stat-value"><?= htmlspecialchars(number_format($actual_salary_base, 2)); ?> <small><?=__('SAR');?></small></div>
                                <div class="sr-stat-sub"><?=__('eos_monthly_package', 'Monthly package');?></div>
                            </div>
                            <?php if ($clearance_pending > 0): ?>
                                <div class="sr-stat is-red">
                                    <div class="sr-stat-label"><?=__('eos_clearance', 'Clearance');?> <i class="mdi mdi-alert-octagon"></i></div>
                                    <div class="sr-stat-value"><?= (int) $clearance_pending ?> <small><?=__('eos_items_pending', 'item(s) pending');?></small></div>
                                    <div class="sr-stat-sub"><?=__('eos_return_before_eos', 'Return assets / vehicles first');?></div>
                                </div>
                            <?php elseif ($has_subordinates): ?>
                                <div class="sr-stat is-amber">
                                    <div class="sr-stat-label"><?=__('eos_clearance', 'Clearance');?> <i class="mdi mdi-account-switch"></i></div>
                                    <div class="sr-stat-value"><?= count($subordinates) ?> <small><?=__('eos_subordinates', 'subordinate(s)');?></small></div>
                                    <div class="sr-stat-sub"><?=__('Employee(s) need supervisor reassignment');?></div>
                                </div>
                            <?php else: ?>
                                <div class="sr-stat is-green">
                                    <div class="sr-stat-label"><?=__('eos_clearance', 'Clearance');?> <i class="mdi mdi-check-circle"></i></div>
                                    <div class="sr-stat-value"><?=__('eos_cleared', 'Cleared');?></div>
                                    <div class="sr-stat-sub"><?=__('eos_no_pending_items', 'No assets or vehicles pending');?></div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php ob_start(); ?>
                        <!-- Salary components -->
                        <div class="sr-card">
                            <div class="sr-card-head">
                                <h2 class="sr-card-title"><i class="mdi mdi-cash-usd"></i> <?=__('Salary Components');?></h2>
                                <span class="sr-card-sub"><?=__('SAR');?></span>
                            </div>
                            <div class="sr-card-body">
                                <dl class="sr-kv">
                                    <div class="row-kv"><dt><?=__('Basic Salary');?></dt><dd><?= htmlspecialchars(number_format($salaryrow['basic'] ?? 0, 2)); ?></dd></div>
                                    <div class="row-kv<?= $housing_is_zero ? ' is-flag' : '' ?>"><dt><?=__('Housing Allowance');?></dt><dd><?= htmlspecialchars(number_format($salaryrow['housing'] ?? 0, 2)); ?></dd></div>
                                    <div class="row-kv"><dt><?=__('Food Allowance');?></dt><dd><?= htmlspecialchars(number_format($salaryrow['food'] ?? 0, 2)); ?></dd></div>
                                    <div class="row-kv"><dt><?=__('Transport Allowance');?></dt><dd><?= htmlspecialchars(number_format($salaryrow['transport'] ?? 0, 2)); ?></dd></div>
                                    <div class="row-kv"><dt><?=__('Miscellaneous');?></dt><dd><?= htmlspecialchars(number_format($salaryrow['misc'] ?? 0, 2)); ?></dd></div>
                                    <div class="row-kv"><dt><?=__('Other Allowances');?></dt><dd><?= htmlspecialchars(number_format($other_allowances, 2)); ?></dd></div>
                                    <div class="row-kv is-total"><dt><?=__('Total Salary Base (for EOS calculation)');?></dt><dd><?= htmlspecialchars(number_format($actual_salary_base, 2)); ?></dd></div>
                                </dl>
                                <?php if ($housing_is_zero && ($salaryrow['basic'] ?? 0) > 0): ?>
                                    <div class="sr-notice tone-sky" style="margin: 14px 0 0;">
                                        <i class="mdi mdi-information-outline"></i>
                                        <div><strong><?=__('Note');?>:</strong> Housing allowance is 0 in salary record. Calculated housing (Basic / 12) &times; 2 = <strong><?= htmlspecialchars(number_format($calculated_housing, 2)); ?> SAR</strong> has been included in the total package above for EOS calculation.</div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php $salaryCardHtml = ob_get_clean(); ?>

                        <?php ob_start(); ?>
                        <?php if ($assigned_assets_count > 0): ?>
                        <!-- Assigned Assets -->
                        <div class="sr-card">
                            <div class="sr-card-head">
                                <h2 class="sr-card-title"><i class="mdi mdi-laptop"></i> <?=__('Assigned Assets for Clearance');?></h2>
                                <span class="sr-pill tone-red"><span class="sr-dot"></span><?= (int) $assigned_assets_count ?></span>
                            </div>
                            <div class="sr-table-wrap">
                                <table class="sr-table">
                                    <thead>
                                        <tr>
                                            <th><?=__('Asset Type');?></th>
                                            <th><?=__('Serial Number');?></th>
                                            <th><?=__('Description');?></th>
                                            <th><?=__('Assigned Date');?></th>
                                            <th class="text-right"><?=__('Action');?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                        $assets_query_display = mysqli_query($conDB, "SELECT ea.*, a.name AS asset_name FROM `employee_assets` ea JOIN `assets` a ON ea.asset_id = a.id WHERE ea.emp_id = '{$emprow['empid']}' AND ea.status = 'Assigned'");
                                        if(mysqli_num_rows($assets_query_display) > 0):
                                            while($asset_row = mysqli_fetch_assoc($assets_query_display)):
                                    ?>
                                        <tr>
                                            <td><div class="sr-cell-title"><?= htmlspecialchars($asset_row['asset_name']); ?></div></td>
                                            <td><span class="sr-mono"><?= htmlspecialchars($asset_row['serial_number']); ?></span></td>
                                            <td><?= htmlspecialchars($asset_row['description']); ?></td>
                                            <td><?= htmlspecialchars($asset_row['assigned_date']); ?></td>
                                            <td>
                                                <div class="eos-actions-cell">
                                                    <a href="asset_return_report.php?asset_id=<?= (int)$asset_row['id']; ?>" target="_blank" class="sr-btn sr-btn-ghost sr-btn-sm eos-print-return-btn" data-asset-id="<?= (int)$asset_row['id']; ?>">
                                                        <i class="mdi mdi-printer"></i> <?= __('print_for_return'); ?>
                                                    </a>
                                                    <button type="button" class="sr-btn sr-btn-danger sr-btn-sm" id="eos-submit-return-btn-<?= (int)$asset_row['id']; ?>" onclick="unassignAsset(<?= (int)$asset_row['id']; ?>)" disabled>
                                                        <i class="mdi mdi-check-circle"></i> <?= __('submit_return'); ?>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php
                                            endwhile;
                                        else:
                                    ?>
                                        <tr><td colspan="5"><div class="sr-empty"><i class="mdi mdi-laptop"></i><?=__('No assets are currently assigned to this employee.');?></div></td></tr>
                                    <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($assigned_cars_count > 0): ?>
                        <!-- Assigned Vehicles -->
                        <div class="sr-card">
                            <div class="sr-card-head">
                                <h2 class="sr-card-title"><i class="mdi mdi-car-convertable"></i> <?=__('Assigned Vehicles for Clearance', 'Assigned Vehicles for Clearance');?></h2>
                                <span class="sr-pill tone-red"><span class="sr-dot"></span><?= (int) $assigned_cars_count ?></span>
                            </div>
                            <div class="sr-table-wrap">
                                <table class="sr-table">
                                    <thead>
                                        <tr>
                                            <th><?=__('plate_no_label', 'Plate No');?></th>
                                            <th><?=__('Description');?></th>
                                            <th><?=__('receive_date_label', 'Receive Date');?></th>
                                            <th class="text-right"><?=__('Action');?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($assigned_cars as $car_row): ?>
                                        <tr>
                                            <td><span class="sr-chip sr-mono"><?= htmlspecialchars($car_row['plate_no']); ?></span></td>
                                            <td><?= htmlspecialchars(trim(($car_row['maker_name'] ?? '') . ' - ' . ($car_row['model'] ?? '')), ENT_QUOTES); ?></td>
                                            <td><?= htmlspecialchars($car_row['rcv_date'] ?? ''); ?></td>
                                            <td>
                                                <div class="eos-actions-cell">
                                                    <a href="javascript:void(0);" class="sr-btn sr-btn-danger sr-btn-sm addRtrnDrvrAtter" data-id="<?= (int)$car_row['id']; ?>" data-cid="<?= (int)$car_row['car_id']; ?>">
                                                        <i class="mdi mdi-car-convertable"></i> <?= __('return_car_button', 'Return Car'); ?>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php $clearanceHtml = ob_get_clean(); ?>

                        <?php if ($eos_id): ?>
                            <div class="sr-import">
                                <div>
                                    <?= $clearanceHtml ?>
                                    <?php if ($clearanceHtml === '' || trim($clearanceHtml) === ''): ?>
                                        <div class="sr-card">
                                            <div class="sr-card-body">
                                                <div class="sr-empty">
                                                    <i class="mdi mdi-check-circle"></i>
                                                    <?=__('Employee End of Service has been registered.');?>
                                                    <div style="margin-top: 14px;">
                                                        <a href="./end_of_service_print.php?emp_id=<?= $empIdParam ?>" target="_blank" class="sr-btn sr-btn-primary"><i class="mdi mdi-printer"></i> <?=__('Print Report');?></a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <aside class="sr-sticky"><?= $salaryCardHtml ?></aside>
                            </div>
                        <?php else: ?>
                        <form id="calculatorForm" class="sr-form" action="emp_end_of_service.php?emp_id=<?= $empIdParam ?>" method="post" autocomplete="off">
                            <div class="sr-import">
                                <div>
                                    <?php if ($assigned_assets_count > 0): ?>
                                        <div class="sr-notice tone-red">
                                            <i class="mdi mdi-alert-octagon"></i>
                                            <div><strong><?=__('Action Required:');?></strong> <?= str_replace('{count}', $assigned_assets_count, __('This employee has {count} outstanding asset(s). Please ensure all assets are returned before proceeding with termination.')) ?></div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($assigned_cars_count > 0): ?>
                                        <div class="sr-notice tone-red">
                                            <i class="mdi mdi-car-convertable"></i>
                                            <div><strong><?=__('Action Required:');?></strong> <?= str_replace('{count}', $assigned_cars_count, __('This employee has {count} outstanding vehicle(s) assigned. Please return the vehicle(s) before proceeding with termination.', 'This employee has {count} outstanding vehicle(s) assigned. Please return the vehicle(s) before proceeding with termination.')) ?></div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($has_subordinates): ?>
                                        <div class="sr-notice tone-amber" id="subordinates-warning-alert">
                                            <i class="mdi mdi-account-switch"></i>
                                            <div><strong><?=__('Action Required:');?></strong> <?= count($subordinates) . ' ' . __('Employee(s) need supervisor reassignment') ?></div>
                                            <button type="button" class="sr-btn sr-btn-sm sr-btn-primary" id="show-subordinates-btn" onclick="showSubordinatesModal()">
                                                <i class="mdi mdi-account-multiple"></i> <?=__('reassign_supervisors');?>
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($general_error_message): ?>
                                        <div class="sr-notice tone-red"><i class="mdi mdi-alert-circle-outline"></i><div><?=htmlspecialchars($general_error_message); ?></div></div>
                                    <?php endif; ?>
                                    <?php foreach (['assets', 'subordinates', 'country', 'duplicate_eos'] as $errKey): ?>
                                        <?php if (!empty($errors[$errKey])): ?>
                                            <div class="sr-notice tone-red"><i class="mdi mdi-alert-circle-outline"></i><div><?=htmlspecialchars($errors[$errKey]); ?></div></div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>

                                    <?= $clearanceHtml ?>
                                    <?php if (trim($clearanceHtml) !== ''): ?><div style="height: 20px;"></div><?php endif; ?>

                                    <fieldset <?= $form_blocked ? 'disabled' : '' ?>>
                                        <!-- Hidden fields for subordinates tracking -->
                                        <input type="hidden" id="subordinates_updated" name="subordinates_updated" value="<?= $has_subordinates ? 'false' : 'true' ?>">
                                        <input type="hidden" id="subordinates_json" value='<?= htmlspecialchars(json_encode($subordinates), ENT_QUOTES, 'UTF-8'); ?>'>
                                        <input type="hidden" id="has_subordinates" value="<?= $has_subordinates ? '1' : '0' ?>">

                                        <!-- Termination details -->
                                        <div class="sr-fsec">
                                            <div class="sr-fsec-head"><span><i class="mdi mdi-file-document"></i><?=__('eos_termination_details', 'Termination Details');?></span></div>
                                            <div class="sr-fgrid">
                                                <div class="sr-fcol c-5">
                                                    <label><?=__('Type of Contract');?> <span class="text-danger">*</span></label>
                                                    <div class="eos-contract">
                                                        <label class="sr-choice"><input type="radio" id="contract-limited" name="contract_type" value="1" <?=($contractType == "1") ? "checked" : ""; ?>><span><i class="mdi mdi-calendar-range"></i> <?=__('Limited Period');?></span></label>
                                                        <label class="sr-choice"><input type="radio" id="contract-unlimited" name="contract_type" value="2" <?=($contractType == "2") ? "checked" : ""; ?>><span><i class="mdi mdi-calendar-clock"></i> <?=__('Unlimited Period');?></span></label>
                                                    </div>
                                                    <?php if (!empty($errors['contract_type'])): ?><small class="sr-fhint is-warn"><?=htmlspecialchars($errors['contract_type']); ?></small><?php endif; ?>
                                                </div>
                                                <div class="sr-fcol c-7">
                                                    <label for="eos_reason"><?=__('End of Service Reason');?> <span class="text-danger">*</span></label>
                                                    <select id="eos_reason" required class="form-control<?= !empty($errors['eos_reason']) ? ' is-invalid' : '' ?>" name="eos_reason" <?php if(empty($filteredReasons) && empty($general_error_message)) echo 'disabled';?>>
                                                        <option value=""><?=__('Choose a reason');?></option>
                                                        <?php foreach ($filteredReasons as $reason): ?>
                                                            <option value="<?=htmlspecialchars($reason['ContractEndReasonCode']); ?>" <?=($selectedReasonCode == $reason['ContractEndReasonCode']) ? "selected" : ""; ?>>
                                                                <?= ($current_lang === 'ar' && !empty($reason['ArDescription'])) ? htmlspecialchars($reason['ArDescription']) : htmlspecialchars($reason['EnDescription']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <?php if (!empty($preFillReasonLabel)): ?>
                                                        <small class="sr-fhint"><i class="mdi mdi-information-outline"></i> <?=__('Pre-filled from resignation');?>: <?=htmlspecialchars($preFillReasonLabel); ?></small>
                                                    <?php endif; ?>
                                                    <?php if (!empty($errors['eos_reason'])): ?><small class="sr-fhint is-warn"><?=htmlspecialchars($errors['eos_reason']); ?></small><?php endif; ?>
                                                </div>
                                                <div class="sr-fcol c-3">
                                                    <label for="joining_date"><?=__('joining_date_label');?></label>
                                                    <input type="text" name="joining_date" class="form-control" id="joining_date" value="<?=htmlspecialchars($emprow['joining_date'] ?? '');?>" readonly>
                                                </div>
                                                <div class="sr-fcol c-3">
                                                    <label for="end_date"><?=__('last_working_day');?> <span class="text-danger">*</span></label>
                                                    <input type="text" name="end_date" class="form-control<?= !empty($errors['end_date']) ? ' is-invalid' : '' ?>" id="end_date" value="<?=htmlspecialchars($endDateStr); ?>" required autocomplete="off" placeholder="YYYY-MM-DD">
                                                    <?php if (!empty($errors['end_date'])): ?><small class="sr-fhint is-warn"><?=htmlspecialchars($errors['end_date']); ?></small><?php endif; ?>
                                                </div>
                                                <div class="sr-fcol c-2">
                                                    <label for="vac_days_delta" id="vac_days_delta_label"><?=__('Accrued days (to LWD)');?></label>
                                                    <input type="number" class="form-control" value="0.00" id="vac_days_delta" name="vac_days_delta" step="any" placeholder="0.00" readonly>
                                                    <small class="sr-fhint is-warn d-none" id="vac_days_delta_hint"><?=__('eos_vac_after_lwd_hint', 'Accrued after LWD until today - removed from balance');?></small>
                                                </div>
                                                <div class="sr-fcol c-2">
                                                    <label for="anul_vac_days"><?=__('Annual vacation days');?></label>
                                                    <input type="number" class="form-control" value="<?= htmlspecialchars(number_format($current_vacation_balance, 2, '.', '')); ?>" id="anul_vac_days" name="anul_vac_days" step="any" placeholder="0.00" readonly>
                                                </div>
                                                <div class="sr-fcol c-2">
                                                    <label for="curt_month_days_display"><?=__('Working Days');?></label>
                                                    <input type="number" class="form-control" id="curt_month_days_display" name="curt_month_days" value="0" readonly>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Earnings -->
                                        <div class="sr-fsec eos-fsec-earn">
                                            <div class="sr-fsec-head"><span><i class="mdi mdi-plus-circle"></i><?=__('eos_earnings', 'Earnings');?></span></div>
                                            <div class="sr-fgrid">
                                                <div class="sr-fcol c-3">
                                                    <label for="curt_month_salry"><?=__('Resignation Month Salary');?></label>
                                                    <input type="text" class="form-control" value="0.00" id="curt_month_salry" name="curt_month_salry" readonly>
                                                    <small class="eos-amt is-plus" id="curt_month_salry_calc">0.00 SAR</small>
                                                </div>
                                                <div class="sr-fcol c-3">
                                                    <label for="overtime_hours"><?=__('Overtime (Hours)');?></label>
                                                    <input type="number" class="form-control calculation-trigger" id="overtime_hours" name="overtime_hours" value="0" min="0" step="0.5">
                                                    <small class="eos-amt is-plus" id="overtime_hours_calc">0.00 SAR</small>
                                                </div>
                                                <div class="sr-fcol c-3">
                                                    <label for="overtime_days"><?=__('Overtime (Days)');?></label>
                                                    <input type="number" class="form-control calculation-trigger" id="overtime_days" name="overtime_days" value="0" min="0" step="0.5">
                                                    <small class="eos-amt is-plus" id="overtime_days_calc">0.00 SAR</small>
                                                </div>
                                                <div class="sr-fcol c-3">
                                                    <label for="other_earnings"><?=__('Other Earnings');?></label>
                                                    <input type="number" class="form-control calculation-trigger" id="other_earnings" name="other_earnings" value="0.00" step="any">
                                                    <small class="eos-amt is-plus" id="other_earnings_calc">0.00 SAR</small>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Deductions -->
                                        <div class="sr-fsec eos-fsec-deduct">
                                            <div class="sr-fsec-head"><span><i class="mdi mdi-minus-circle"></i><?=__('eos_deductions', 'Deductions');?></span></div>
                                            <div class="sr-fgrid">
                                                <div class="sr-fcol c-3">
                                                    <label for="absent_days"><?=__('Absent Days');?></label>
                                                    <input type="number" class="form-control calculation-trigger" id="absent_days" name="absent_days" value="0" min="0">
                                                    <small class="eos-amt is-minus" id="absent_days_calc">0.00 SAR</small>
                                                </div>
                                                <div class="sr-fcol c-3">
                                                    <label for="deduction_hours"><?=__('Deduction (Hours)');?></label>
                                                    <input type="number" class="form-control calculation-trigger" id="deduction_hours" name="deduction_hours" value="0" min="0">
                                                    <small class="eos-amt is-minus" id="deduction_hours_calc">0.00 SAR</small>
                                                </div>
                                                <?php if ($emprow['country'] == 191 && floatval($emprow['gosi']) > 0): ?>
                                                    <div class="sr-fcol c-3">
                                                        <label for="gosi_deduction"><?=__('GOSI Deduction');?></label>
                                                        <input type="number" class="form-control calculation-trigger" id="gosi_deduction" name="gosi_deduction" value="0.00" step="any">
                                                        <small class="eos-amt is-minus" id="gosi_deduction_calc">0.00 SAR</small>
                                                    </div>
                                                    <div class="sr-fcol c-3">
                                                        <label for="deduct"><?=__('Other Deductions (Loan, etc.)');?></label>
                                                        <input type="number" class="form-control calculation-trigger" value="<?= htmlspecialchars($outstanding_loan); ?>" id="deduct" name="deduct" step="any">
                                                        <small class="eos-amt is-minus" id="deduct_calc">0.00 SAR</small>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="sr-fcol c-3">
                                                        <label for="deduct"><?=__('Deduct (Loan, etc.)');?></label>
                                                        <input type="number" class="form-control calculation-trigger" value="<?= htmlspecialchars($outstanding_loan); ?>" id="deduct" name="deduct" step="any">
                                                        <small class="eos-amt is-minus" id="deduct_calc">0.00 SAR</small>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- Notes & notification -->
                                        <div class="sr-fsec">
                                            <div class="sr-fsec-head"><span><i class="mdi mdi-email-outline"></i><?=__('eos_notes_notify', 'Notes & Notification');?></span></div>
                                            <div class="sr-fgrid">
                                                <div class="sr-fcol c-6">
                                                    <label for="notes"><?=__('Notes');?> <span class="text-danger">*</span></label>
                                                    <textarea class="form-control<?= !empty($errors['notes']) ? ' is-invalid' : '' ?>" id="notes" name="notes" rows="3" required><?= htmlspecialchars($notesPrefill); ?></textarea>
                                                    <?php if (!empty($errors['notes'])): ?><small class="sr-fhint is-warn"><?=htmlspecialchars($errors['notes']); ?></small><?php endif; ?>
                                                </div>
                                                <div class="sr-fcol c-6">
                                                    <label for="cc_emails"><?=__('eos_cc_label', 'CC to Notify (Direct Manager)');?></label>
                                                    <select id="cc_emails" name="cc_users[]" class="form-control" multiple style="width: 100%;">
                                                        <?php foreach ($ccSelected as $ccId => $ccInfo): ?>
                                                            <option value="<?= (int) $ccId; ?>" selected><?= htmlspecialchars($ccInfo['name'] . ' (' . $ccInfo['emp_id'] . ') - ' . $ccInfo['email']); ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <small class="sr-fhint"><?=__('eos_cc_hint', 'Optional - search by name or Emp ID. They will be emailed to print the EOS report, get it signed by the employee and upload it to the master file.');?></small>
                                                </div>
                                            </div>
                                        </div>
                                    </fieldset>
                                </div>

                                <aside class="sr-sticky">
                                    <!-- Settlement summary -->
                                    <div class="sr-card">
                                        <div class="sr-card-head">
                                            <h2 class="sr-card-title"><i class="mdi mdi-calculator"></i> <?=__('eos_settlement_summary', 'Settlement Summary');?></h2>
                                            <span class="sr-card-sub"><?=__('SAR');?></span>
                                        </div>
                                        <div class="sr-card-body">
                                            <dl class="sr-kv eos-sum">
                                                <div class="row-kv"><dt><?=__('EOS Amount (from API)');?></dt><dd><input type="text" id="eos_amount_display" value="0.00" readonly tabindex="-1"></dd></div>
                                                <div class="row-kv"><dt><?=__('Vacation Salary');?></dt><dd><input type="text" id="vacation_salary_display" value="0.00" readonly tabindex="-1"></dd></div>
                                                <div class="row-kv is-earn"><dt><?=__('Total Earnings');?></dt><dd><input type="text" id="total_earnings_display" value="0.00" readonly tabindex="-1"></dd></div>
                                                <div class="row-kv is-deduct"><dt><?=__('Total Deductions');?></dt><dd><input type="text" id="total_deductions_display" value="0.00" readonly tabindex="-1"></dd></div>
                                            </dl>
                                            <div class="sr-ftotal eos-net eos-sum">
                                                <span><?=__('Total Net Payment');?></span>
                                                <input type="text" id="net_payment_display" value="0.00" readonly tabindex="-1">
                                            </div>
                                            <div class="eos-submit">
                                                <button type="submit" name="submit" class="sr-btn sr-btn-danger" <?= $form_blocked ? 'disabled' : '' ?>><i class="mdi mdi-account-remove"></i> <?=__('Register EOS');?></button>
                                                <?php if ($form_blocked): ?>
                                                    <small class="sr-fhint is-warn"><?=__('eos_return_before_eos', 'Return assets / vehicles first');?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div style="height: 20px;"></div>
                                    <?= $salaryCardHtml ?>
                                </aside>
                            </div>

                            <!-- Hidden fields for calculation and submission -->
                            <input type="hidden" name="request_inv_no" id="request_inv_no" value="<?= htmlspecialchars($requestInvNo); ?>">
                            <input type="hidden" name="eos_amount" id="eos_amount_hidden" value="">
                            <input type="hidden" name="anul_vac_salry" id="anul_vac_salry_hidden" value="">
                            <input type="hidden" name="net_payment" id="net_payment_hidden" value="">
                            <input type="hidden" id="total_salary" value="<?= htmlspecialchars($total_salary); ?>">
                            <input type="hidden" id="employee_master_total_salary" value="<?= htmlspecialchars($employee_master_total_salary); ?>">
                            <input type="hidden" id="basic_salary" value="<?= htmlspecialchars($salaryrow['basic'] ?? 0); ?>">
                            <input type="hidden" id="housing_allowance" value="<?= htmlspecialchars($salaryrow['housing'] ?? 0); ?>">
                            <input type="hidden" id="calculated_housing" value="<?= htmlspecialchars($calculated_housing); ?>">
                            <input type="hidden" id="two_month_housing_benefit" value="<?= htmlspecialchars($two_month_housing); ?>">
                            <input type="hidden" id="actual_salary_base" value="<?= htmlspecialchars($actual_salary_base); ?>">
                            <input type="hidden" id="vacation_salary_base" value="<?= htmlspecialchars($vacation_salary_base); ?>">
                            <input type="hidden" id="annual_vacation_entitlement" value="<?= htmlspecialchars($annual_vacation_entitlement); ?>">
                            <input type="hidden" id="current_vacation_balance" value="<?= htmlspecialchars($current_vacation_balance); ?>">
                            <input type="hidden" id="balance_period_end" value="<?= htmlspecialchars($balance_period_end ?? ''); ?>">
                            <input type="hidden" id="is_two_year_contract" value="<?= htmlspecialchars($is_two_year_contract); ?>">
                            <input type="hidden" id="emp_country" value="<?= htmlspecialchars($emprow['country'] ?? ''); ?>">
                            <input type="hidden" id="emp_gosi_percent" value="<?= htmlspecialchars($emprow['gosi'] ?? 0); ?>">
                            <input type="hidden" id="emp_contract_type" value="<?= htmlspecialchars($emprow['contract_type'] ?? ''); ?>">
                        </form>
                        <?php endif; ?>
					</div>
				</div>
				<footer class="footer"><?= $site_footer ?></footer>
			</div>
		</div>
		<script src="assets/js/jquery.min.js"></script>
		<script src="assets/js/bootstrap.bundle.min.js"></script>
		<script src="assets/js/metisMenu.min.js"></script>
		<script src="assets/js/waves.js"></script>
		<script src="assets/js/jquery.slimscroll.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
		<script src="./plugins/select2/js/select2.min.js"></script>
		<script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>
        <script type="text/javascript">
            /**
             * =====================================================================
             * == CREATE SETTLEMENT FUNCTION FOR EOS
             * =====================================================================
             * Creates a settlement record for an End of Service
             * Uses settlement_handler.php (EOS-specific endpoint, not vacation-based)
             * Independent from vacation settlement workflow
             */
            function createSettlement(vacationId, requestInvNo, employeeId, employeeName, vacationDays) {
                // Get the calculated settlement amount from the hidden field
                const settlementAmount = parseFloat($('#net_payment_hidden').val()) || 0;
                
                // Validate required parameters
                const empIdInt = parseInt(employeeId);
                if (!requestInvNo || requestInvNo.trim() === '') {
                    Swal.fire({
                        title: __('error') || 'Error',
                        text: 'Request Invoice Number is missing. Cannot create settlement.',
                        icon: 'error',
                        confirmButtonText: __('ok') || 'OK',
                        confirmButtonColor: APP_COLORS.danger
                    });
                    return;
                }
                
                if (isNaN(empIdInt) || empIdInt <= 0) {
                    Swal.fire({
                        title: __('error') || 'Error',
                        text: 'Employee ID is invalid. Cannot create settlement.',
                        icon: 'error',
                        confirmButtonText: __('ok') || 'OK',
                        confirmButtonColor: APP_COLORS.danger
                    });
                    return;
                }
                
                if (isNaN(settlementAmount) || settlementAmount <= 0) {
                    Swal.fire({
                        title: __('error') || 'Error',
                        text: 'Settlement amount must be calculated before creating settlement. Please ensure the form has been calculated.',
                        icon: 'error',
                        confirmButtonText: __('ok') || 'OK',
                        confirmButtonColor: APP_COLORS.danger
                    });
                    return;
                }
                
                // Show confirmation dialog for creating settlement
                Swal.fire({
                    title: __('create_settlement') || 'Create Settlement',
                    html: `
                        <div style="text-align: left; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                            <p><strong>${__('employee_name') || 'Employee Name'}:</strong> ${employeeName}</p>
                            <p><strong>${__('request_number') || 'Request Number'}:</strong> ${requestInvNo}</p>
                            <p><strong>${__('settlement_amount') || 'Settlement Amount'}:</strong> ${settlementAmount.toFixed(2)} SAR</p>
                            <div style="margin-top: 15px; padding: 12px; background: white; border-radius: 6px; border-left: 4px solid #28a745;">
                                <i class="fa fa-info-circle text-success"></i>
                                <strong>${__('note')}:</strong>
                                ${__('settlement_will_be_created_and_sent_for_approval') || 'A settlement record will be created and sent for approval.'}
                            </div>
                        </div>
                    `,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: APP_COLORS.success,
                    confirmButtonText: '<i class="fa fa-check"></i> ' + (__('create_settlement') || 'Create Settlement'),
                    cancelButtonColor: APP_COLORS.danger,
                    cancelButtonText: '<i class="fa fa-times"></i> ' + (__('cancel') || 'Cancel'),
                    allowOutsideClick: false,
                    showLoaderOnConfirm: true,
                    preConfirm: () => {
                        // Submit EOS settlement creation via AJAX to EOS-specific endpoint
                        return $.ajax({
                            url: './includes/ajaxFile/settlement_handler.php',
                            type: 'POST',
                            dataType: 'JSON',
                            data: {
                                action: 'create_settlement',
                                request_type: 'eos',
                                request_inv_no: String(requestInvNo).trim(),
                                emp_id: parseInt(employeeId),
                                settlement_amount: parseFloat(settlementAmount)
                            }
                        }).fail(function(jqXHR, textStatus, errorThrown) {
                            let errorMsg = __('error_creating_settlement') || 'Error creating settlement';
                            if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                                errorMsg = jqXHR.responseJSON.message;
                            }
                            console.error('Settlement AJAX Error:', {
                                status: jqXHR.status,
                                response: jqXHR.responseText,
                                error: errorThrown
                            });
                            Swal.showValidationMessage(errorMsg);
                        });
                    }
                }).then((result) => {
                    if (result.isConfirmed && result.value) {
                        const response = result.value;
                        Swal.fire({
                            title: __('success') || 'Success',
                            text: response.message || (__('settlement_created_successfully') || 'Settlement has been created successfully.'),
                            icon: 'success',
                            confirmButtonText: __('ok') || 'OK',
                            allowOutsideClick: false
                        }).then(() => {
                            location.reload();
                        });
                    }
                });
            }

            // Prompt for return date before printing; do not write to DB
            $(document).on('click', '.eos-print-return-btn', function(event) {
                event.preventDefault();
                const assetId = $(this).data('asset-id');
                const baseUrl = $(this).attr('href');

                Swal.fire({
                    title: __('return_date'),
                    html: '<form class="sr-form" onsubmit="return false;"><div class="sr-fsec" style="margin: 0;"><div class="sr-fgrid"><div class="sr-fcol c-12">' +
                          '<label for="eos-return-date-input">' + (__('return_date') || 'Return Date') + '</label>' +
                          '<input type="text" id="eos-return-date-input" class="form-control" value="' + new Date().toISOString().slice(0, 10) + '">' +
                          '</div></div></div></form>',
                    showCancelButton: true,
                    confirmButtonText: '<i class="mdi mdi-printer"></i> ' + __('print_for_return'),
                    cancelButtonText: __('cancel'),
                    customClass: { popup: 'sr-addline-popup sr-page' },
                    allowOutsideClick: false,
                    didOpen: function() {
                        if (window.AppDate) {
                            AppDate.single('#eos-return-date-input', { static: true });
                        }
                    },
                    preConfirm: () => {
                        const selectedDate = $('#eos-return-date-input').val();
                        if (!selectedDate) {
                            Swal.showValidationMessage(__('please_select_return_date') || 'Please select a return date');
                            return false;
                        }
                        return selectedDate;
                    }
                }).then((result) => {
                    if (result.isConfirmed && result.value) {
                        const selectedDate = result.value;
                        const joinChar = baseUrl.includes('?') ? '&' : '?';
                        const urlWithDate = baseUrl + joinChar + 'print_return_date=' + encodeURIComponent(selectedDate);
                        window.open(urlWithDate, '_blank');
                        $('#eos-submit-return-btn-' + assetId).prop('disabled', false);
                    }
                });
            });
        </script>
        <script>
            const paidPayrolls = <?= json_encode($paid_payrolls); ?>;
            const vacationRecords = <?= json_encode($vacation_records); ?>;
        </script>
		<script type="text/javascript">
            $(document).ready(function(){
                // Show processing alert on EOS submit
                $('#calculatorForm').on('submit', function() {
                    Swal.fire({
                        title: __('processing') || 'Processing...',
                        html: __('please_wait_settlement_note') || 'Please wait while we process the End of Service settlement',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                });
                
                $('#eos_reason').select2();
                
                // Track if user manually edited GOSI deduction
                let gosiManuallyEdited = false;
                
                $('#gosi_deduction').on('input change', function() {
                    // Mark as manually edited when user types
                    gosiManuallyEdited = true;
                });

                function isSalaryPaidForMonth(endDateStr) {
                    if (!endDateStr || !window.paidPayrolls || !window.paidPayrolls.length) return false;
                    const targetMonthYear = endDateStr.substring(0, 7);
                    return window.paidPayrolls.some(paidMonth => String(paidMonth).trim() === targetMonthYear);
                }

                // Server-side current date (balance already up to today)
                const serverTodayStr = '<?= date('Y-m-d'); ?>';
                const serverToday = new Date(serverTodayStr + 'T00:00:00');

                function updateProratedVacation() {
                    const endDateStr = $('#end_date').val();
                    const periodEntitlement = parseFloat($('#annual_vacation_entitlement').val()) || 0; // stored as period total (e.g., 42 for 2-year contract)
                    const currentVacationBalance = parseFloat($('#current_vacation_balance').val()) || 0; // already up to today
                    const isTwoYear = parseInt($('#is_two_year_contract').val()) || 0;

                    // Determine true annual rate (period total / 2 if 2-year contract)
                    const trueAnnualRate = isTwoYear ? (periodEntitlement / 2) : periodEntitlement;
                    const dailyAccrualRate = trueAnnualRate / 365;

                    // Accrual delta always relative to today: future LWD adds, past LWD deducts
                    let accruedDays = 0;
                    if (endDateStr) {
                        const eosDate = new Date(endDateStr + 'T00:00:00');
                        const diffMs = eosDate - serverToday;
                        const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
                        accruedDays = diffDays * dailyAccrualRate;
                    }

                    // Total vacation days = current (as of today) + delta (can be +/-)
                    const totalVacationDays = currentVacationBalance + accruedDays;

                    // Past LWD: balance already accrued up to today, so the days after LWD are
                    // removed. Show them as a positive deduction instead of a confusing negative.
                    const isDeduction = accruedDays < 0;
                    $('#vac_days_delta_label').text(isDeduction
                        ? '<?= addslashes(__('eos_vac_days_deducted', 'Days deducted (after LWD)')); ?>'
                        : '<?= addslashes(__('Accrued days (to LWD)')); ?>');
                    $('#vac_days_delta').val(Math.abs(accruedDays).toFixed(2)).toggleClass('text-danger', isDeduction);
                    $('#vac_days_delta_hint').toggleClass('d-none', !isDeduction);
                    $('#anul_vac_days').val(totalVacationDays.toFixed(2));
                    $('#vacation_days_summary').text(totalVacationDays.toFixed(2));
                }

                function calculateFinalPayment() {
                    const endDateStr = $('#end_date').val();
                    if (!endDateStr) return;

                    const isPaid = isSalaryPaidForMonth(endDateStr);
                    const endDate = new Date(endDateStr);
                    const workingDays = isPaid ? 0 : endDate.getDate();
                    $('#curt_month_days_display').val(workingDays);
                    
                    const actualSalaryBase = parseFloat($('#actual_salary_base').val()) || 0;
                    const contractSalaryBase = parseFloat($('#vacation_salary_base').val()) || 0; // actual package without calculated housing
                    const basicSalary = parseFloat($('#basic_salary').val()) || 0;
                    const housingAllowance = parseFloat($('#housing_allowance').val()) || 0;
                    const absentDays = parseInt($('#absent_days').val()) || 0;
                    
                    // Base daily rate for potential salary (contract package)
                    const dailyRateContract = contractSalaryBase / 30; // 30-day convention
                    // Resignation month's salary is based on worked days only (deductions handled separately)
                    let resignationSalary = (!isPaid) ? dailyRateContract * workingDays : 0;

                    // DEDUCTION BASE RULE: Use contract base for deductions (days) and /8 for hours
                    const DEDUCTION_BASE = contractSalaryBase;
                    const dailyRateDeduction = DEDUCTION_BASE / 30;
                    const deductionHours = parseFloat($('#deduction_hours').val()) || 0;
                    const hourlyRateDeduction = (dailyRateDeduction) / 8;
                    const absentDeductionAmount = dailyRateDeduction * absentDays;
                    const hourlyDeductionAmount = hourlyRateDeduction * deductionHours;
                    
                    // Update calculation displays
                    $('#absent_days_calc').text(absentDeductionAmount.toFixed(2) + ' SAR');
                    $('#deduction_hours_calc').text(hourlyDeductionAmount.toFixed(2) + ' SAR');
                    
                    // Ensure resignation salary doesn't go below zero
                    resignationSalary = Math.max(0, resignationSalary);
                    
                    $('#curt_month_salry').val(resignationSalary.toFixed(2));
                    $('#curt_month_salry_calc').text(resignationSalary.toFixed(2) + ' SAR');

                    const empCountry = $('#emp_country').val();
                    const gosiPercent = parseFloat($('#emp_gosi_percent').val()) || 0;
                    const gosiBase = basicSalary + housingAllowance;
                    
                    // Calculate GOSI deduction for the termination month only
                    // Formula: (Monthly GOSI / 30) × Days worked in termination month
                    let calculatedGosiDeduction = 0;
                    if (empCountry == '191' && gosiPercent > 0 && gosiBase > 0 && endDateStr) {
                        const monthlyGosi = gosiBase * gosiPercent / 100;
                        const dailyGosi = monthlyGosi / 30;
                        const terminationDate = new Date(endDateStr);
                        const daysInTerminationMonth = terminationDate.getDate(); // Days from 1st to termination date
                        calculatedGosiDeduction = dailyGosi * daysInTerminationMonth;
                    }
                    
                    // Only auto-fill if user hasn't manually edited the field
                    if ($('#gosi_deduction').length && !gosiManuallyEdited) {
                        $('#gosi_deduction').val(calculatedGosiDeduction.toFixed(2));
                    }
                    
                    // Get the actual GOSI deduction value (user can override)
                    const gosiDeduction = parseFloat($('#gosi_deduction').val()) || 0;
                    
                    // Update GOSI calculation display
                    $('#gosi_deduction_calc').text(gosiDeduction.toFixed(2) + ' SAR');
                    
                    const eosAmount = parseFloat($('#eos_amount_display').val()) || 0;
                    const vacationSalary = parseFloat($('#vacation_salary_display').val()) || 0;
                    const otherEarnings = parseFloat($('#other_earnings').val()) || 0;
                    
                    // Update Other Earnings display
                    $('#other_earnings_calc').text(otherEarnings.toFixed(2) + ' SAR');
                    const twoMonthHousingBenefit = parseFloat($('#two_month_housing_benefit').val()) || 0; // 2-month housing benefit
                    
                    // Calculate overtime earnings per new rule:
                    // per-hour overtime rate = (basic/240)/2 + (full/240)
                    // full = actualSalaryBase; basic = basicSalary
                    // hours amount = overtimeHourlyRate * overtime_hours
                    // days amount  = overtimeHourlyRate * 8 * overtime_days
                    const overtimeHours = parseFloat($('#overtime_hours').val()) || 0;
                    const overtimeDays = parseFloat($('#overtime_days').val()) || 0;
                    const overtimeHourlyRate = ((basicSalary / 240) / 2) + ((contractSalaryBase) / 240);
                    const overtimeHoursAmount = overtimeHourlyRate * overtimeHours;
                    const overtimeDaysAmount = overtimeHourlyRate * 8 * overtimeDays;
                    const totalOvertimeEarnings = overtimeHoursAmount + overtimeDaysAmount;
                    
                    // Update overtime calculation displays
                    $('#overtime_hours_calc').text(overtimeHoursAmount.toFixed(2) + ' SAR');
                    $('#overtime_days_calc').text(overtimeDaysAmount.toFixed(2) + ' SAR');
                    
                    const loanDeduction = parseFloat($('#deduct').val()) || 0;
                    
                    // Update Other Deductions display
                    $('#deduct_calc').text(loanDeduction.toFixed(2) + ' SAR');
 
                    // Note: twoMonthHousingBenefit is now included in EOS amount, so we don't add it separately
                    const totalEarnings = eosAmount + vacationSalary + resignationSalary + otherEarnings + totalOvertimeEarnings;
                    const totalDeductions = loanDeduction + gosiDeduction + absentDeductionAmount + hourlyDeductionAmount;
                    const netPayment = totalEarnings - totalDeductions;
                    
                    // Update the display fields
                    $('#total_earnings_display').val(totalEarnings.toFixed(2));
                    $('#total_deductions_display').val(totalDeductions.toFixed(2));
                    
                    // --- MODIFICATION: Round up the net payment to the nearest next number ---
                    const roundedNetPayment = Math.ceil(netPayment);
                    
                    $('#net_payment_display').val(roundedNetPayment.toFixed(2));
                    $('#net_payment_hero').text(roundedNetPayment.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }))
                        .parent().toggleClass('is-neg', roundedNetPayment < 0);

                    $('#net_payment_hidden').val(roundedNetPayment.toFixed(2));
                    // --- END MODIFICATION ---
                    
                    $('#anul_vac_salry_hidden').val(vacationSalary.toFixed(2));
                    $('#eos_amount_hidden').val(eosAmount.toFixed(2));
                }

                function performApiCalculation() {
                    const totalSalary = parseFloat($('#total_salary').val()) || 0;
                    const masterTotalSalary = parseFloat($('#employee_master_total_salary').val()) || 0;
                    const basicSalary = parseFloat($('#basic_salary').val()) || 0;
                    const calculatedHousing = parseFloat($('#calculated_housing').val()) || 0;
                    const housingAllowance = parseFloat($('#housing_allowance').val()) || 0;
                    
                    const formData = {
                        contract_type: $('input[name="contract_type"]:checked').val(),
                        eos_reason: $('#eos_reason').val(),
                        end_date: $('#end_date').val(),
                        joining_date: $('#joining_date').val(),
                        salary: totalSalary,
                        vacation_salary_base: masterTotalSalary,
                        anul_vac_days: $('#anul_vac_days').val(),
                        calculated_housing: $('#calculated_housing').val(),
                        housing_allowance: $('#housing_allowance').val()
                    };

                    // Detailed logging of what's being sent to API
                    console.log("=== API CALL DEBUG INFO ===");
                    console.log("Basic Salary:", basicSalary);
                    console.log("Calculated Housing:", calculatedHousing);
                    console.log("Housing Allowance:", housingAllowance);
                    console.log("Total Salary:", totalSalary);
                    console.log("Employee Master Total Salary (Vacation Base):", masterTotalSalary);
                    console.log("Form Data being sent:", formData);
                    console.log("=== END DEBUG INFO ===");

                    if (!formData.end_date || !formData.eos_reason) {
                        $('#eos_amount_display, #vacation_salary_display').val('0.00');
                        calculateFinalPayment();
                        return; 
                    }

                    $('#net_payment_display').val('Calculating...');

                    // Use LOCAL EOS calculator (Saudi Arabia Labor Law) - NO external API dependency
                    $.ajax({
                        type: 'GET',
                        url: './includes/ajaxFile/ajax_eos_calculator_local.php',
                        data: formData,
                        dataType: 'json',
                        cache: false,
                        headers: {
                            'Cache-Control': 'no-cache, no-store, must-revalidate'
                        },
                        success: function(response) {
                            console.log("✓ Local EOS Calculator Response:", response);
                            
                            if (response.success) {
                                console.log("✓✓ Calculation Success - Method:", response.calculation_method);
                                console.log("Service Years:", response.service_years);
                                console.log("EOS Amount:", response.eos_amount);
                                
                                $('#eos_amount_display').val(response.eos_amount);
                                
                                // Use vacation salary from response (already calculated correctly)
                                $('#vacation_salary_display').val(response.vacation_salary);

                            } else {
                                console.warn("✗ Calculation Failed:", response.message);
                                $('#eos_amount_display, #vacation_salary_display').val('0.00');
                            }
                            calculateFinalPayment();
                        },
                        error: function(xhr, status, error) {
                            console.error("✗✗ Calculator Error:", status, error);
                            console.error("Response:", xhr.responseText);
                            $('#eos_amount_display, #vacation_salary_display').val('Error');
                            calculateFinalPayment();
                        }
                    });
                }

                function masterCalculationTrigger() {
                    updateProratedVacation();
                    performApiCalculation();
                }

                if (window.AppDate && $('#end_date').length) {
                    AppDate.single('#end_date', { onChange: masterCalculationTrigger });
                } else {
                    $('#end_date').on('change', masterCalculationTrigger);
                }

                $('input[name="contract_type"]').on('change', performApiCalculation);
                $('#eos_reason').on('change', performApiCalculation);
                $('.calculation-trigger').on('change keyup', calculateFinalPayment);

                // CC to notify: search active system users (admin_login) by name / Emp ID
                $('#cc_emails').select2({
                    placeholder: '<?= addslashes(__('eos_cc_placeholder', 'Type name or Emp ID...')); ?>',
                    allowClear: true,
                    minimumInputLength: 1,
                    ajax: {
                        url: './includes/ajaxFile/hrHandler.php',
                        type: 'POST',
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return { ajaxType: 'cc_email_search', search: params.term || '' };
                        },
                        processResults: function (res) {
                            return {
                                results: (res.data || []).map(function (u) {
                                    return { id: u.id, text: u.name + ' (' + u.emp_id + ') - ' + u.email };
                                })
                            };
                        }
                    }
                });

                if($('#end_date').val()){
                    masterCalculationTrigger();
                }
            });
		</script>

        <!-- NEW: SweetAlert2 Modal for Supervisor Reassignment -->
        <script type="text/javascript">
            /**
             * Show SweetAlert2 modal for reassigning supervisors
             * Called when EOS employee is a direct supervisor to other employees
             */
            function showSubordinatesModal() {
                const hasSubordinates = parseInt($('#has_subordinates').val());
                if (!hasSubordinates) return;

                const subordinatesJson = $('#subordinates_json').val();
                let subordinates = [];
                try {
                    subordinates = JSON.parse(subordinatesJson);
                } catch (e) {
                    console.error('Failed to parse subordinates JSON', e);
                    Swal.fire({
                        title: '<?= __("error") ?>',
                        text: '<?= __("failed_to_load_subordinates") ?>',
                        icon: 'error',
                        confirmButtonText: '<?= __("ok") ?>'
                    });
                    return;
                }

                if (subordinates.length === 0) {
                    return;
                }

                const esc = function(v) {
                    return String(v == null ? '' : v).replace(/[&<>"']/g, function(c) {
                        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
                    });
                };
                const initials = function(name) {
                    return String(name || '').trim().split(/\s+/).slice(0, 2).map(function(p) { return p.charAt(0); }).join('').toUpperCase();
                };

                // Build table HTML for subordinates with checkboxes
                let tableHtml = `
                    <form class="sr-form" onsubmit="return false;">
                    <div class="eos-sub-scroll">
                        <table class="sr-table" style="margin: 0;">
                            <thead>
                                <tr>
                                    <th style="width: 44px; text-align: center;">
                                        <input type="checkbox" id="select-all-subordinates" title="<?= __('select_all') ?>">
                                    </th>
                                    <th><?= __('name') ?></th>
                                    <th><?= __('department') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                subordinates.forEach(function(sub) {
                    tableHtml += `
                        <tr class="subordinate-row">
                            <td style="text-align: center;">
                                <input type="checkbox" class="subordinate-checkbox" data-emp-id="${esc(sub.emp_id)}" data-emp-name="${esc(sub.name)}">
                            </td>
                            <td>
                                <div class="sr-person">
                                    <span class="sr-avatar sr-avatar-sm">${esc(initials(sub.name))}</span>
                                    <div><div class="sr-cell-title">${esc(sub.name)}</div><div class="sr-cell-sub sr-mono">${esc(sub.emp_id)}</div></div>
                                </div>
                            </td>
                            <td>${esc(sub.department || '-')}</td>
                        </tr>
                    `;
                });

                tableHtml += `
                            </tbody>
                        </table>
                    </div>
                    <div class="sr-fsec">
                        <div class="sr-fsec-head"><span><i class="mdi mdi-account-switch"></i><?= __('select_new_supervisor_for_all') ?></span></div>
                        <div class="sr-fgrid">
                            <div class="sr-fcol c-12">
                                <select id="bulk-supervisor-select" class="form-control" style="width: 100%;">
                                    <option value="">-- <?= __('select_supervisor') ?> --</option>
                                </select>
                                <small class="sr-fhint"><i class="mdi mdi-information-outline"></i> <?= __('selected_employees_will_assigned_this_supervisor') ?></small>
                            </div>
                        </div>
                    </div>
                    </form>
                `;

                // Show SweetAlert modal
                Swal.fire({
                    title: '<?= __('reassign_supervisors') ?>',
                    html: tableHtml,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: '<i class="mdi mdi-content-save"></i> <?= __('save_and_update') ?>',
                    cancelButtonText: '<?= __('cancel') ?>',
                    customClass: { popup: 'sr-addline-popup sr-page eos-sub-popup' },
                    width: 720,
                    allowOutsideClick: false,
                    didOpen: function(modal) {
                        // Load available employees for supervisor selection
                        $.ajax({
                            url: './includes/ajaxFile/hrHandler.php',
                            type: 'POST',
                            dataType: 'JSON',
                            data: { ajaxType: 'emp_search' },
                            success: function(res) {
                                if (res.data && res.data.length > 0) {
                                    // Populate select2 for bulk supervisor select
                                    const employees = res.data;
                                    
                                    $('#bulk-supervisor-select').select2({
                                        data: employees.map(emp => ({
                                            id: emp.emp_id,
                                            text: emp.emp_id + ' - ' + emp.name
                                        })),
                                        placeholder: '<?= __("start_typing_to_search") ?>',
                                        allowClear: true,
                                        width: '100%',
                                        dropdownParent: $(Swal.getPopup())
                                    });
                                    
                                    // Prevent select2 dropdown from being hidden by SweetAlert
                                    $('#bulk-supervisor-select').on('select2:open', function() {
                                        setTimeout(function() {
                                            $('body').addClass('swal2-open');
                                        }, 0);
                                    });
                                }
                            }
                        });
                        
                        // Handle "Select All" checkbox
                        $(document).on('change', '#select-all-subordinates', function() {
                            const isChecked = $(this).is(':checked');
                            $('.subordinate-checkbox').prop('checked', isChecked);
                        });
                        
                        // Handle individual checkboxes
                        $(document).on('change', '.subordinate-checkbox', function() {
                            const totalCheckboxes = $('.subordinate-checkbox').length;
                            const checkedCheckboxes = $('.subordinate-checkbox:checked').length;
                            
                            // Update "Select All" checkbox state
                            if (checkedCheckboxes === totalCheckboxes) {
                                $('#select-all-subordinates').prop('checked', true);
                            } else {
                                $('#select-all-subordinates').prop('checked', false);
                            }
                        });
                    },
                    preConfirm: function() {
                        // Get selected subordinates
                        let selectedSubordinates = [];
                        $('.subordinate-checkbox:checked').each(function() {
                            selectedSubordinates.push({
                                emp_id: $(this).data('emp-id'),
                                emp_name: $(this).data('emp-name')
                            });
                        });
                        
                        // Get selected supervisor
                        const newSupervisorId = $('#bulk-supervisor-select').val();
                        
                        // Validate selections
                        if (selectedSubordinates.length === 0) {
                            Swal.showValidationMessage('<?= __("please_select_at_least_one_employee") ?>');
                            return false;
                        }
                        
                        if (!newSupervisorId) {
                            Swal.showValidationMessage('<?= __("please_select_new_supervisor") ?>');
                            return false;
                        }
                        
                        // Make AJAX calls to update all selected subordinates
                        let updatePromises = [];
                        selectedSubordinates.forEach(function(sub) {
                            updatePromises.push(
                                $.ajax({
                                    url: './includes/ajaxFile/hrHandler.php',
                                    type: 'POST',
                                    dataType: 'JSON',
                                    data: {
                                        ajaxType: 'update_subordinate_supervisor',
                                        emp_id: sub.emp_id,
                                        new_supervisor_id: newSupervisorId
                                    }
                                })
                            );
                        });
                        
                        return Promise.all(updatePromises).then(function(responses) {
                            // Check if all updates succeeded
                            let allSuccess = true;
                            responses.forEach(function(res) {
                                if (res.status !== 'success' && res.message?.includes('success') === false) {
                                    allSuccess = false;
                                }
                            });
                            
                            if (!allSuccess) {
                                throw new Error('<?= __("failed_to_update_some_supervisors") ?>');
                            }
                            
                            return {
                                success: true,
                                selectedCount: selectedSubordinates.length,
                                supervisorId: newSupervisorId
                            };
                        });
                    }
                }).then(function(result) {
                    if (result.isConfirmed && result.value && result.value.success) {
                        // Update hidden field to mark as updated
                        $('#subordinates_updated').val('true').trigger('change');

                        // Show success message
                        Swal.fire({
                            title: '<?= __("success") ?>',
                            html: `<strong>${result.value.selectedCount}</strong> <?= __("supervisors_updated_successfully") ?>`,
                            icon: 'success',
                            confirmButtonText: '<?= __("ok") ?>',
                            confirmButtonColor: '#28a745'
                        }).then(function() {
                            // Enable the Register EOS button
                            $('#show-subordinates-btn').prop('disabled', true);
                            $('#subordinates-warning-alert').fadeOut(function() {
                                $(this).removeClass('tone-amber').addClass('tone-green')
                                    .html('<i class="mdi mdi-check-circle"></i><div><?= __("all_supervisors_reassigned") ?></div>').fadeIn();
                            });
                        });
                    }
                });
            }

            // On page load, check if we need to show the subordinates modal
            $(document).ready(function() {
                const hasSubordinates = parseInt($('#has_subordinates').val());
                if (hasSubordinates) {
                    // Disable the submit button until supervisors are updated
                    const registerBtn = $('button[name="submit"]');
                    registerBtn.prop('disabled', true);
                    
                    // If supervisors not updated yet, optionally auto-show the modal
                    const subordinatesUpdated = $('#subordinates_updated').val();
                    if (subordinatesUpdated !== 'true') {
                        // You can uncomment this to auto-show modal on page load
                        // showSubordinatesModal();
                    } else {
                        registerBtn.prop('disabled', false);
                    }
                }
            });

            // Monitor changes to subordinates_updated and enable/disable button
            $(document).on('change', '#subordinates_updated', function() {
                const val = $(this).val();
                const registerBtn = $('button[name="submit"]');
                if (val === 'true') {
                    registerBtn.prop('disabled', false);
                } else {
                    registerBtn.prop('disabled', true);
                }
            });
        </script>

        <?php if (!empty($error_1)): ?>
        <script type="text/javascript">
            $(document).ready(function() {
                Swal.fire({
                    title: __('success') || 'Success',
                    html: `<?= $error_1; ?>`,
                    icon: 'success',
                    confirmButtonText: __('ok') || 'OK',
                    allowOutsideClick: false,
                    didOpen: function() {
                        // Wait 2 seconds before allowing interaction
                        setTimeout(function() {
                            Swal.getConfirmButton().disabled = false;
                        }, 2000);
                    }
                }).then(function(result) {
                    if (result.isConfirmed) {
                        // Redirect back to page
                        window.location.href = './emp_end_of_service.php?emp_id=<?=$_GET['emp_id']?>';
                    }
                });
            });
        </script>
        <?php endif; ?>
	</body>
	</html>
<?php } ?>