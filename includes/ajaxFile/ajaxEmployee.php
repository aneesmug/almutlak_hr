<?php
    header('Content-Type: application/json');
	require_once __DIR__ . '/../../includes/db.php';
    include("./../../includes/helper_functions.php"); // --- Helper Function ---

/****************************************************************
 * MODIFICATION SUMMARY (012-ajaxEmployee.php):
 * 1. ADDED `mysqli_free_result()` after all `mysqli_query` loops to prevent "Commands out of sync" errors.
 * 2. This is critical for stabilizing the $conDB connection.
 * 3. [FIXED] ADDED `get_hr_assistants` ajaxType block, which was missing and causing an error in `all_applied_vac.php`.
 ****************************************************************/

$ajaxType = $_POST['ajaxType'];

if($ajaxType == 'emp_search') {
    $stmt = mysqli_query($conDB, "SELECT * FROM `employees` WHERE `status`=1 ORDER BY `name` REGEXP '^[^A-Za-z]' ASC, `name` ");
    $name = []; // Initialize
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
    $name = []; // Initialize
    while($row = mysqli_fetch_assoc($stmt)) {
        $name[] = $row;
    }
    mysqli_free_result($stmt); // <-- FIX
    $data = [
        'data'      => $name,
        'status'    => 200
    ];
    echo json_encode($data);
} elseif($ajaxType == 'get_direct_supervisor') {
    // Get the direct supervisor ID for an employee
    $empId = isset($_POST['emp_id']) ? (int)$_POST['emp_id'] : 0;
    
    if ($empId > 0) {
        $stmt = mysqli_query($conDB, "SELECT `supervisor_id` FROM `employees` WHERE `emp_id` = {$empId} AND `status` = 1");
        if ($stmt && mysqli_num_rows($stmt) > 0) {
            $row = mysqli_fetch_assoc($stmt);
            $supervisorId = $row['supervisor_id'];
            mysqli_free_result($stmt);
            
            if ($supervisorId && $supervisorId > 0) {
                echo json_encode([
                    'status' => 200,
                    'supervisor_id' => $supervisorId
                ]);
            } else {
                echo json_encode([
                    'status' => 404,
                    'message' => 'No supervisor assigned to this employee.'
                ]);
            }
        } else {
            echo json_encode([
                'status' => 404,
                'message' => 'Employee not found.'
            ]);
        }
    } else {
        echo json_encode([
            'status' => 400,
            'message' => 'Invalid employee ID.'
        ]);
    }
} elseif($ajaxType == 'emp_department') {
    $dept = (int)$_POST['dept'];
    $exclude_emp_id = isset($_POST['exclude_emp_id']) ? mysqli_real_escape_string($conDB, $_POST['exclude_emp_id']) : '';
    
    $where_clause = "`e`.`status`=1 AND `e`.`dept`=$dept";
    if (!empty($exclude_emp_id)) {
        $where_clause .= " AND `e`.`emp_id` != '$exclude_emp_id'";
    }
    
    $stmt = mysqli_query($conDB, "SELECT 
    `e`.*,
    `d`.`dep_nme` AS `deptnme`
    FROM `employees` `e`
    LEFT JOIN `department` `d` ON `d`.`id` = `e`.`dept` 
    WHERE $where_clause");
    $name = []; // Initialize
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
// =================================================================
// == NEW BLOCK TO FETCH ASSIGNED ASSETS FOR AN EMPLOYEE
// =================================================================
elseif($ajaxType == 'get_assigned_assets') {
    $emp_id = $_POST['emp_id'] ?? null;
    if (!$emp_id) {
        echo json_encode(['status' => 400, 'message' => 'Employee ID is required.']);
        exit;
    }
    $sql = "SELECT ea.id, a.name AS asset_name, ea.serial_number, ea.description, ea.assigned_date 
            FROM employee_assets ea 
            JOIN assets a ON ea.asset_id = a.id 
            WHERE ea.emp_id = ? AND ea.status = 'Assigned' 
            ORDER BY ea.assigned_date DESC";
    $stmt = mysqli_prepare($conDB, $sql);
    if (!$stmt) {
        echo json_encode(['status' => 500, 'message' => 'Database error: ' . mysqli_error($conDB)]);
        exit;
    }
    mysqli_stmt_bind_param($stmt, "s", $emp_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $assets = [];
    while($row = mysqli_fetch_assoc($result)) {
        $assets[] = $row;
    }
    if ($result) mysqli_free_result($result);
    mysqli_stmt_close($stmt);
    echo json_encode(['status' => 200, 'assets' => $assets]);
    exit;
}
// =================================================================
// == NEW BLOCK TO FETCH POTENTIAL APPROVERS FOR CHAIN APPROVAL
// =================================================================
elseif($ajaxType == 'get_potential_approvers') {
    $approvers = get_potential_approvers($conDB);
    if (!empty($approvers)) {
        $data = [
            'data'      => $approvers,
            'status'    => 200
        ];
    } else {
        $data = [
            'data'      => [],
            'status'    => 404,
            'message'   => 'No potential approvers found.'
        ];
    }
    echo json_encode($data);
}
// =================================================================
// == NEW BLOCK TO FETCH DEPARTMENT-SPECIFIC APPROVERS
// =================================================================
elseif($ajaxType == 'get_department_approvers') {
    if (empty($_POST['dept_id'])) {
        send_json_response("Error", "Department ID is required.", "error");
        exit;
    }
    $dept_id = (int)$_POST['dept_id'];
    $approvers = get_department_approvers($conDB, $dept_id); // This function is in helper_functions.php
    
    if (!empty($approvers)) {
        $data = [
            'data'      => $approvers,
            'status'    => 200
        ];
    } else {
        $data = [
            'data'      => [],
            'status'    => 404,
            'message'   => 'No potential approvers found for this department.'
        ];
    }
    echo json_encode($data);
}
// =================================================================
// == [FIX] ADDED THIS BLOCK, IT WAS MISSING
// =================================================================
elseif($ajaxType == 'get_hr_assistants') {
    // This function is defined in helper_functions.php
    $data = get_hr_assistants($conDB);
    echo json_encode(['data' => $data, 'status' => 200]);
    exit;
}
// =================================================================
elseif($ajaxType == 'get_hr_senior_bp') {
    // Get all HR Senior BP users for simple leave approval
    $sql = "SELECT e.emp_id, e.name, al.user_type 
            FROM employees e 
            JOIN admin_login al ON e.emp_id = al.emp_id 
            WHERE al.user_type = 'hr_senior_bp' AND e.status = 1
            ORDER BY e.name ASC";
    
    $result = mysqli_query($conDB, $sql);
    $data = [];
    
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = [
                'emp_id' => $row['emp_id'],
                'name' => $row['name'],
                'user_type' => $row['user_type']
            ];
        }
    }
    
    echo json_encode(['data' => $data, 'status' => 200]);
    exit;
}

elseif($ajaxType == 'get_hr_team_members') {
    // Get all HR team members (ALL employees in HR department - dept_id = 5)
    // These are employees who can receive CC notifications
    $sql = "SELECT DISTINCT e.emp_id, e.name, e.email, d.dep_nme, al.user_type 
            FROM employees e 
            LEFT JOIN department d ON e.dept = d.id
            LEFT JOIN admin_login al ON e.emp_id = al.emp_id 
            WHERE e.status = 1 
            AND e.dept = 5
            ORDER BY e.name ASC";
    
    $result = mysqli_query($conDB, $sql);
    $data = [];
    
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = [
                'emp_id' => $row['emp_id'],
                'name' => $row['name'],
                'email' => $row['email'],
                'dept_name' => $row['dep_nme'],
                'user_type' => $row['user_type'] ?? 'hr_staff'
            ];
        }
    }
    
    echo json_encode(['data' => $data, 'status' => 200]);
    exit;
}

// =================================================================
// == Get Employee Salary
// == Fetches the salary details for a given employee
// == Returns contract salary base (for deductions/overtime calculations)
// =================================================================
elseif($ajaxType == 'get_employee_salary') {
    $emp_id = mysqli_real_escape_string($conDB, $_POST['emp_id'] ?? '');
    
    if (empty($emp_id)) {
        echo json_encode(['status' => 400, 'message' => 'Employee ID is required']);
        exit;
    }
    
    // Get full salary details for accurate calculations - ONLY active salary (status = 1)
    $get_salary_data = mysqli_query($conDB, "SELECT * FROM `emp_salary` WHERE `emp_id`='{$emp_id}' AND `status` = 1 ORDER BY `id` DESC LIMIT 1");
    
    if (!$get_salary_data) {
        echo json_encode(['status' => 500, 'message' => 'Database error: ' . mysqli_error($conDB)]);
        exit;
    }
    
    $salaryrow = mysqli_fetch_assoc($get_salary_data);
    
    if (!$salaryrow) {
        echo json_encode(['status' => 404, 'message' => 'No active salary record found for this employee']);
        exit;
    }
    
    // Calculate contract salary base (WITHOUT calculated housing - actual package only)
    // This is used for deduction and overtime calculations per EOS file logic
    $contract_salary_base = 0;
    $contract_salary_base += (float)($salaryrow['basic'] ?? 0);
    $contract_salary_base += (float)($salaryrow['housing'] ?? 0); // Actual housing only
    $contract_salary_base += (float)($salaryrow['transport'] ?? 0);
    $contract_salary_base += (float)($salaryrow['food'] ?? 0);
    $contract_salary_base += (float)($salaryrow['misc'] ?? 0);
    $contract_salary_base += (float)($salaryrow['cashier'] ?? 0);
    $contract_salary_base += (float)($salaryrow['fuel'] ?? 0);
    $contract_salary_base += (float)($salaryrow['tel'] ?? 0);
    $contract_salary_base += (float)($salaryrow['guard'] ?? 0);
    $contract_salary_base += (float)($salaryrow['other'] ?? 0);
    
    $basic_salary = (float)($salaryrow['basic'] ?? 0);
    
    echo json_encode([
        'status' => 200, 
        'salary' => $contract_salary_base,
        'basic_salary' => $basic_salary,
        'breakdown' => [
            'basic' => (float)($salaryrow['basic'] ?? 0),
            'housing' => (float)($salaryrow['housing'] ?? 0),
            'transport' => (float)($salaryrow['transport'] ?? 0),
            'food' => (float)($salaryrow['food'] ?? 0),
            'misc' => (float)($salaryrow['misc'] ?? 0),
            'cashier' => (float)($salaryrow['cashier'] ?? 0),
            'fuel' => (float)($salaryrow['fuel'] ?? 0),
            'tel' => (float)($salaryrow['tel'] ?? 0),
            'guard' => (float)($salaryrow['guard'] ?? 0),
            'other' => (float)($salaryrow['other'] ?? 0)
        ]
    ]);
    exit;
}

// =================================================================
// == WRAPPER: Get Asset Clearance Chain (for backward compatibility)
// == This endpoint wraps build_vacation_approval_chain
// == Takes vacation_id and fetches employee details automatically
// =================================================================
elseif($ajaxType == 'get_asset_clearance_chain') {
    try {
        $vacation_id = (int)($_POST['vacation_id'] ?? 0);
        // Check if exclude_level1 is set and true (handles both boolean and string)
        $exclude_level1 = (isset($_POST['exclude_level1']) && ($_POST['exclude_level1'] === true || $_POST['exclude_level1'] === 'true' || $_POST['exclude_level1'] === 1 || $_POST['exclude_level1'] === '1'));
        
        error_log("get_asset_clearance_chain: vacation_id=$vacation_id, exclude_level1=" . ($exclude_level1 ? 'true' : 'false'));
        
        if (empty($vacation_id)) {
            throw new Exception("Vacation ID is required");
        }
        
        // Get vacation details
        $sql_vac = "SELECT emp_id, vacation_salary_type, fly_type, remarks 
                    FROM emp_vacation 
                    WHERE id = ? LIMIT 1";
        $stmt_vac = mysqli_prepare($conDB, $sql_vac);
        
        if (!$stmt_vac) {
            throw new Exception("Database error: " . mysqli_error($conDB));
        }
        
        mysqli_stmt_bind_param($stmt_vac, "i", $vacation_id);
        if (!mysqli_stmt_execute($stmt_vac)) {
            mysqli_stmt_close($stmt_vac);
            throw new Exception("Failed to fetch vacation details");
        }
        
        $result_vac = mysqli_stmt_get_result($stmt_vac);
        if (!$result_vac || mysqli_num_rows($result_vac) == 0) {
            mysqli_stmt_close($stmt_vac);
            throw new Exception("Vacation request not found");
        }
        
        $vac_data = mysqli_fetch_assoc($result_vac);
        mysqli_free_result($result_vac);
        mysqli_stmt_close($stmt_vac);
        
        $emp_id = (int)$vac_data['emp_id'];
        $vacation_salary_type = $vac_data['vacation_salary_type'] ?? 'end_of_service';
        $fly_type = $vac_data['fly_type'] ?? '';
        $remarks = strtolower(trim($vac_data['remarks'] ?? ''));
        
        // Now call the main chain builder logic (inline to avoid code duplication)
        // We'll reuse the build_vacation_approval_chain logic below
        
        // Build the approval chain
        $chain = [];
        $chain_details = [];
        
        // STEP 1: Get employee's supervisor and department
        $sql_emp = "SELECT supervisor_id, dept FROM employees WHERE emp_id = ? LIMIT 1";
        $stmt_emp = mysqli_prepare($conDB, $sql_emp);
        if (!$stmt_emp) {
            throw new Exception("Database error");
        }
        
        mysqli_stmt_bind_param($stmt_emp, "i", $emp_id);
        mysqli_stmt_execute($stmt_emp);
        $res_emp = mysqli_stmt_get_result($stmt_emp);
        
        if (!$res_emp || mysqli_num_rows($res_emp) == 0) {
            mysqli_stmt_close($stmt_emp);
            throw new Exception("Employee not found");
        }
        
        $emp_row = mysqli_fetch_assoc($res_emp);
        $supervisor_id = $emp_row['supervisor_id'];
        $emp_dept_id = $emp_row['dept'];
        mysqli_free_result($res_emp);
        mysqli_stmt_close($stmt_emp);
        
        // STEP 2: Add Supervisor OR Department Manager as Level 1
        if (!empty($supervisor_id) && $supervisor_id != '0') {
            $sql_sup = "SELECT emp_id, name FROM employees WHERE emp_id = ? AND status = 1 LIMIT 1";
            $stmt_sup = mysqli_prepare($conDB, $sql_sup);
            if ($stmt_sup) {
                mysqli_stmt_bind_param($stmt_sup, "s", $supervisor_id);
                mysqli_stmt_execute($stmt_sup);
                $res_sup = mysqli_stmt_get_result($stmt_sup);
                if ($row_sup = mysqli_fetch_assoc($res_sup)) {
                    $chain[] = $row_sup['emp_id'];
                    $chain_details[] = [
                        'emp_id' => $row_sup['emp_id'],
                        'name' => $row_sup['name'],
                        'label' => 'Direct Supervisor',
                        'level' => 1
                    ];
                }
                if ($res_sup) mysqli_free_result($res_sup);
                mysqli_stmt_close($stmt_sup);
            }
        } else if (!empty($emp_dept_id) && function_exists('getDeptManager')) {
            $dept_mgr = getDeptManager($conDB, $emp_dept_id);
            if ($dept_mgr && !empty($dept_mgr['emp_id'])) {
                $chain[] = $dept_mgr['emp_id'];
                $chain_details[] = [
                    'emp_id' => $dept_mgr['emp_id'],
                    'name' => $dept_mgr['name'],
                    'label' => 'Department Manager',
                    'level' => 1
                ];
            }
        }
        
        // STEP 3: Add HR Senior BP (ALWAYS Level 2 after Direct Manager)
        // This ensures HR Senior BP is in the chain immediately after the direct manager approves
        $sql_hr_bp = "SELECT e.emp_id, e.name FROM employees e 
                      JOIN admin_login al ON e.emp_id = al.emp_id 
                      WHERE al.user_type = 'hr_senior_bp' AND e.status = 1 
                      ORDER BY e.emp_id ASC LIMIT 1";
        $res_hr_bp = mysqli_query($conDB, $sql_hr_bp);
        if ($res_hr_bp && ($row_hr_bp = mysqli_fetch_assoc($res_hr_bp))) {
            if (!in_array($row_hr_bp['emp_id'], $chain)) {
                $chain[] = $row_hr_bp['emp_id'];
                $chain_details[] = [
                    'emp_id' => $row_hr_bp['emp_id'],
                    'name' => $row_hr_bp['name'],
                    'label' => 'HR Senior BP',
                    'level' => count($chain)
                ];
            }
        }
        if ($res_hr_bp) mysqli_free_result($res_hr_bp);
        
        // STEP 4: Add Asset Clearance Teams
        $sql_assets = "SELECT a.name AS asset_name FROM employee_assets ea 
                       JOIN assets a ON ea.asset_id = a.id 
                       WHERE ea.emp_id = ? AND ea.status = 'Assigned'";
        $stmt_assets = mysqli_prepare($conDB, $sql_assets);
        if ($stmt_assets) {
            mysqli_stmt_bind_param($stmt_assets, "s", $emp_id);
            mysqli_stmt_execute($stmt_assets);
            $res_assets = mysqli_stmt_get_result($stmt_assets);
            
            $needs_it = false;
            $needs_admin = false;
            $needs_transport = false;
            
            while ($asset_row = mysqli_fetch_assoc($res_assets)) {
                $asset_name = strtolower(trim($asset_row['asset_name']));
                // Mapping by asset name keywords → departments:
                //  IT: laptop, computer
                //  Administration: mobile, phone, sim card
                //  Transportation: car, vehicle
                if (strpos($asset_name, 'laptop') !== false || strpos($asset_name, 'computer') !== false) {
                    $needs_it = true;
                }
                if (strpos($asset_name, 'mobile') !== false || strpos($asset_name, 'phone') !== false || strpos($asset_name, 'sim') !== false) {
                    $needs_admin = true;
                }
                if (strpos($asset_name, 'car') !== false || strpos($asset_name, 'vehicle') !== false) {
                    $needs_transport = true;
                }
            }
            mysqli_free_result($res_assets);
            mysqli_stmt_close($stmt_assets);
            
            if (function_exists('get_department_id_by_name') && function_exists('getDeptManager')) {
                // Department name normalization mapping to actual dep_nme values in DB
                $deptLookup = [
                    'IT' => 'Information Technology',
                    'Administration' => 'Administration',
                    'Transportation' => 'Transportation'
                ];
                if ($needs_it) {
                    $it_dept_id = get_department_id_by_name($conDB, $deptLookup['IT']);
                    if ($it_dept_id) {
                        $it_mgr = getDeptManager($conDB, $it_dept_id);
                        if ($it_mgr && !empty($it_mgr['emp_id']) && !in_array($it_mgr['emp_id'], $chain)) {
                            $chain[] = $it_mgr['emp_id'];
                            $chain_details[] = [
                                'emp_id' => $it_mgr['emp_id'],
                                'name' => $it_mgr['name'],
                                'label' => 'IT Team (Asset Clearance)',
                                'level' => count($chain)
                            ];
                        }
                    }
                }
                
                if ($needs_admin) {
                    $admin_dept_id = get_department_id_by_name($conDB, $deptLookup['Administration']);
                    if ($admin_dept_id) {
                        $admin_mgr = getDeptManager($conDB, $admin_dept_id);
                        if ($admin_mgr && !empty($admin_mgr['emp_id']) && !in_array($admin_mgr['emp_id'], $chain)) {
                            $chain[] = $admin_mgr['emp_id'];
                            $chain_details[] = [
                                'emp_id' => $admin_mgr['emp_id'],
                                'name' => $admin_mgr['name'],
                                'label' => 'Administration Team (Asset Clearance)',
                                'level' => count($chain)
                            ];
                        }
                    }
                }
                
                if ($needs_transport) {
                    $transport_dept_id = get_department_id_by_name($conDB, $deptLookup['Transportation']);
                    if ($transport_dept_id) {
                        $transport_mgr = getDeptManager($conDB, $transport_dept_id);
                        if ($transport_mgr && !empty($transport_mgr['emp_id']) && !in_array($transport_mgr['emp_id'], $chain)) {
                            $chain[] = $transport_mgr['emp_id'];
                            $chain_details[] = [
                                'emp_id' => $transport_mgr['emp_id'],
                                'name' => $transport_mgr['name'],
                                'label' => 'Transportation Team (Asset Clearance)',
                                'level' => count($chain)
                            ];
                        }
                    }
                }
            }
        }
        
        // STEP 5: Add HR Payroll (for ALL annual vacations to process overtime/deductions)
        // All annual vacations must go to HR Payroll regardless of vacation_salary_type
        if ($fly_type === 'annual') {
            $sql_hr_payroll = "SELECT e.emp_id, e.name FROM employees e 
                               JOIN admin_login al ON e.emp_id = al.emp_id 
                               WHERE al.user_type = 'hr_payroll' AND e.status = 1 
                               ORDER BY e.emp_id ASC LIMIT 1";
            $res_hr_payroll = mysqli_query($conDB, $sql_hr_payroll);
            if ($res_hr_payroll && ($row_hr_payroll = mysqli_fetch_assoc($res_hr_payroll))) {
                if (!in_array($row_hr_payroll['emp_id'], $chain)) {
                    $chain[] = $row_hr_payroll['emp_id'];
                    $chain_details[] = [
                        'emp_id' => $row_hr_payroll['emp_id'],
                        'name' => $row_hr_payroll['name'],
                        'label' => 'HR Payroll',
                        'level' => count($chain)
                    ];
                }
            }
            if ($res_hr_payroll) mysqli_free_result($res_hr_payroll);
        }
        
        // STEP 6: Add GR Officer (ONLY if vac_type = 'Fly' AND fly_type = 'annual')
        // GR Officer handles ticket payments and exit-reentry permits for annual fly vacations
        // Note: vac_type is fetched from emp_vacation.vac_type column
        $sql_vac_type = "SELECT vac_type FROM emp_vacation WHERE id = ? LIMIT 1";
        $stmt_vac_type = mysqli_prepare($conDB, $sql_vac_type);
        $vac_type = null;
        if ($stmt_vac_type) {
            mysqli_stmt_bind_param($stmt_vac_type, "i", $vacation_id);
            mysqli_stmt_execute($stmt_vac_type);
            $res_vac_type = mysqli_stmt_get_result($stmt_vac_type);
            if ($row_vac_type = mysqli_fetch_assoc($res_vac_type)) {
                $vac_type = $row_vac_type['vac_type'];
            }
            if ($res_vac_type) mysqli_free_result($res_vac_type);
            mysqli_stmt_close($stmt_vac_type);
        }
        
        $is_fly_vacation = ($fly_type === 'annual' && strtolower($vac_type) === 'fly');
        if ($is_fly_vacation) {
            $sql_gr_officer = "SELECT e.emp_id, e.name FROM employees e 
                               JOIN admin_login al ON e.emp_id = al.emp_id 
                               WHERE al.user_type = 'gr_officer' AND e.status = 1 
                               ORDER BY e.emp_id ASC LIMIT 1";
            $res_gr_officer = mysqli_query($conDB, $sql_gr_officer);
            if ($res_gr_officer && ($row_gr_officer = mysqli_fetch_assoc($res_gr_officer))) {
                if (!in_array($row_gr_officer['emp_id'], $chain)) {
                    $chain[] = $row_gr_officer['emp_id'];
                    $chain_details[] = [
                        'emp_id' => $row_gr_officer['emp_id'],
                        'name' => $row_gr_officer['name'],
                        'label' => 'GR Officer (Final - Ticket & Exit Fee)',
                        'level' => count($chain),
                        'is_final' => true
                    ];
                }
            }
            if ($res_gr_officer) mysqli_free_result($res_gr_officer);
        }
        
        // Log the chain BEFORE exclusion
        error_log("get_asset_clearance_chain: Chain BEFORE exclude_level1: " . json_encode([
            'chain' => $chain,
            'chain_details' => array_map(function($d) { return $d['emp_id'] . ':' . $d['label']; }, $chain_details)
        ]));
        
        // If exclude_level1 is true, remove the first approver from the chain
        // This is used when Level 1 approver is approving and needs to pass the rest of the chain
        if ($exclude_level1 && count($chain) > 0) {
            array_shift($chain); // Remove first element
            array_shift($chain_details); // Remove first element from details
            
            // Renumber levels in chain_details
            foreach ($chain_details as $index => &$detail) {
                $detail['level'] = $index + 1;
            }
            unset($detail);
            
            // Log for debugging
            error_log("get_asset_clearance_chain: Chain AFTER excluding Level 1: " . json_encode([
                'chain' => $chain,
                'chain_details' => array_map(function($d) { return $d['emp_id'] . ':' . $d['label']; }, $chain_details)
            ]));
        }
        
        // Log the final chain being returned
        error_log("get_asset_clearance_chain: FINAL chain for vacation_id $vacation_id: " . count($chain) . " approvers: " . json_encode($chain));
        
        // Return the chain
        echo json_encode([
            'status' => 200,
            'chain' => $chain,
            'chain_details' => $chain_details,
            'total_levels' => count($chain),
            'flow_type' => $is_fly_vacation ? 'with_gr_officer' : 'standard'
        ]);
        exit;
        
    } catch (Exception $e) {
        echo json_encode([
            'status' => 500,
            'message' => $e->getMessage(),
            'chain' => []
        ]);
        exit;
    }
}

// =================================================================
// == COMPLETE VACATION APPROVAL CHAIN BUILDER
// == Implements the full approval flow:
// == 1. Supervisor → 2. Supervisor's Manager (if exists) → 3. HR Senior BP → 
// == 4. Asset Clearance Teams → 5. HR Payroll (if payroll salary) → 6. GR Officer (if fly)
// =================================================================
elseif($ajaxType == 'build_vacation_approval_chain') {
    try {
        $emp_id = (int)($_POST['emp_id'] ?? 0);
        $vacation_salary_type = $_POST['vacation_salary_type'] ?? 'payroll'; // payroll or end_of_service
        $fly_type = $_POST['fly_type'] ?? 'annual'; // annual or emergency
        $vac_type = $_POST['vac_type'] ?? ''; // Fly, Local Vacation, etc.
        $remarks = strtolower($_POST['remarks'] ?? '');

        if ($emp_id <= 0) {
            send_json_response('Error', 'Invalid employee ID.', 'error', 400);
            exit;
        }

        $chain = [];
        $chain_details = [];
        
        // STEP 1: Get employee's supervisor and department info
        $sql_emp = "SELECT supervisor_id, dept FROM employees WHERE emp_id = ? AND status = 1 LIMIT 1";
        $stmt_emp = mysqli_prepare($conDB, $sql_emp);
        mysqli_stmt_bind_param($stmt_emp, "i", $emp_id);
        mysqli_stmt_execute($stmt_emp);
        $res_emp = mysqli_stmt_get_result($stmt_emp);
        $emp_data = mysqli_fetch_assoc($res_emp);
        mysqli_free_result($res_emp);
        mysqli_stmt_close($stmt_emp);

        if (!$emp_data) {
            send_json_response('Error', 'Employee not found.', 'error', 404);
            exit;
        }

        $supervisor_id = $emp_data['supervisor_id'];
        $emp_dept_id = $emp_data['dept'];

        // STEP 2: Add Supervisor or Department Manager as first approver
        if (!empty($supervisor_id)) {
            // Has assigned supervisor
            $sql_sup = "SELECT emp_id, name, supervisor_id FROM employees WHERE emp_id = ? AND status = 1 LIMIT 1";
            $stmt_sup = mysqli_prepare($conDB, $sql_sup);
            mysqli_stmt_bind_param($stmt_sup, "s", $supervisor_id);
            mysqli_stmt_execute($stmt_sup);
            $res_sup = mysqli_stmt_get_result($stmt_sup);
            $supervisor_data = mysqli_fetch_assoc($res_sup);
            mysqli_free_result($res_sup);
            mysqli_stmt_close($stmt_sup);

            if ($supervisor_data) {
                $chain[] = $supervisor_data['emp_id'];
                $chain_details[] = [
                    'emp_id' => $supervisor_data['emp_id'],
                    'name' => $supervisor_data['name'],
                    'label' => 'Direct Supervisor',
                    'level' => 1
                ];

                // STEP 3: Check if supervisor has a direct manager
                if (!empty($supervisor_data['supervisor_id'])) {
                    $sql_mgr = "SELECT emp_id, name FROM employees WHERE emp_id = ? AND status = 1 LIMIT 1";
                    $stmt_mgr = mysqli_prepare($conDB, $sql_mgr);
                    mysqli_stmt_bind_param($stmt_mgr, "s", $supervisor_data['supervisor_id']);
                    mysqli_stmt_execute($stmt_mgr);
                    $res_mgr = mysqli_stmt_get_result($stmt_mgr);
                    $manager_data = mysqli_fetch_assoc($res_mgr);
                    mysqli_free_result($res_mgr);
                    mysqli_stmt_close($stmt_mgr);

                    if ($manager_data) {
                        $chain[] = $manager_data['emp_id'];
                        $chain_details[] = [
                            'emp_id' => $manager_data['emp_id'],
                            'name' => $manager_data['name'],
                            'label' => 'Supervisor\'s Manager',
                            'level' => 2
                        ];
                    }
                }
            }
        } else {
            // No supervisor - use department manager
            if (function_exists('getDeptManager') && $emp_dept_id) {
                $dept_mgr = getDeptManager($conDB, $emp_dept_id);
                if ($dept_mgr && !empty($dept_mgr['emp_id'])) {
                    $chain[] = $dept_mgr['emp_id'];
                    $chain_details[] = [
                        'emp_id' => $dept_mgr['emp_id'],
                        'name' => $dept_mgr['name'],
                        'label' => 'Department Manager',
                        'level' => 1
                    ];
                }
            }
        }

        // STEP 4: Add HR Senior BP
        $sql_hr_bp = "SELECT e.emp_id, e.name FROM employees e 
                      JOIN admin_login al ON e.emp_id = al.emp_id 
                      WHERE al.user_type = 'hr_senior_bp' AND e.status = 1 
                      ORDER BY e.emp_id ASC LIMIT 1";
        $res_hr_bp = mysqli_query($conDB, $sql_hr_bp);
        if ($res_hr_bp && ($row_hr_bp = mysqli_fetch_assoc($res_hr_bp))) {
            $chain[] = $row_hr_bp['emp_id'];
            $chain_details[] = [
                'emp_id' => $row_hr_bp['emp_id'],
                'name' => $row_hr_bp['name'],
                'label' => 'HR Senior BP',
                'level' => count($chain)
            ];
        }
        if ($res_hr_bp) mysqli_free_result($res_hr_bp);

        // STEP 5: Add Asset Clearance Teams (IT, Administration, Transportation)
        $sql_assets = "SELECT a.name AS asset_name FROM employee_assets ea 
                       JOIN assets a ON ea.asset_id = a.id 
                       WHERE ea.emp_id = ? AND ea.status = 'Assigned'";
        $stmt_assets = mysqli_prepare($conDB, $sql_assets);
        if ($stmt_assets) {
            mysqli_stmt_bind_param($stmt_assets, "s", $emp_id);
            mysqli_stmt_execute($stmt_assets);
            $res_assets = mysqli_stmt_get_result($stmt_assets);
            
            $needs_it = false;
            $needs_admin = false;
            $needs_transport = false;
            
            while ($asset_row = mysqli_fetch_assoc($res_assets)) {
                $asset_name = strtolower(trim($asset_row['asset_name']));
                // Mapping by asset name keywords → departments:
                //  IT: laptop, computer
                //  Administration: mobile, phone, sim card
                //  Transportation: car, vehicle
                if (strpos($asset_name, 'laptop') !== false || strpos($asset_name, 'computer') !== false) {
                    $needs_it = true;
                }
                if (strpos($asset_name, 'mobile') !== false || strpos($asset_name, 'phone') !== false || strpos($asset_name, 'sim') !== false) {
                    $needs_admin = true;
                }
                if (strpos($asset_name, 'car') !== false || strpos($asset_name, 'vehicle') !== false) {
                    $needs_transport = true;
                }
            }
            mysqli_free_result($res_assets);
            mysqli_stmt_close($stmt_assets);

            // Add asset team managers
            if (function_exists('get_department_id_by_name') && function_exists('getDeptManager')) {
                // Department name normalization mapping to actual dep_nme values in DB
                $deptLookup = [
                    'IT' => 'Information Technology',
                    'Administration' => 'Administration',
                    'Transportation' => 'Transportation'
                ];
                if ($needs_it) {
                    $it_dept_id = get_department_id_by_name($conDB, $deptLookup['IT']);
                    if ($it_dept_id) {
                        $it_mgr = getDeptManager($conDB, $it_dept_id);
                        if ($it_mgr && !empty($it_mgr['emp_id']) && !in_array($it_mgr['emp_id'], $chain)) {
                            $chain[] = $it_mgr['emp_id'];
                            $chain_details[] = [
                                'emp_id' => $it_mgr['emp_id'],
                                'name' => $it_mgr['name'],
                                'label' => 'IT Team (Asset Clearance)',
                                'level' => count($chain)
                            ];
                        }
                    }
                }
                
                if ($needs_admin) {
                    $admin_dept_id = get_department_id_by_name($conDB, $deptLookup['Administration']);
                    if ($admin_dept_id) {
                        $admin_mgr = getDeptManager($conDB, $admin_dept_id);
                        if ($admin_mgr && !empty($admin_mgr['emp_id']) && !in_array($admin_mgr['emp_id'], $chain)) {
                            $chain[] = $admin_mgr['emp_id'];
                            $chain_details[] = [
                                'emp_id' => $admin_mgr['emp_id'],
                                'name' => $admin_mgr['name'],
                                'label' => 'Administration Team (Asset Clearance)',
                                'level' => count($chain)
                            ];
                        }
                    }
                }
                
                if ($needs_transport) {
                    $transport_dept_id = get_department_id_by_name($conDB, $deptLookup['Transportation']);
                    if ($transport_dept_id) {
                        $transport_mgr = getDeptManager($conDB, $transport_dept_id);
                        if ($transport_mgr && !empty($transport_mgr['emp_id']) && !in_array($transport_mgr['emp_id'], $chain)) {
                            $chain[] = $transport_mgr['emp_id'];
                            $chain_details[] = [
                                'emp_id' => $transport_mgr['emp_id'],
                                'name' => $transport_mgr['name'],
                                'label' => 'Transportation Team (Asset Clearance)',
                                'level' => count($chain)
                            ];
                        }
                    }
                }
            }
        }

        // STEP 6: Add HR Payroll (ONLY if vacation_salary_type = 'payroll')
        if ($vacation_salary_type === 'payroll') {
            $sql_hr_payroll = "SELECT e.emp_id, e.name FROM employees e 
                               JOIN admin_login al ON e.emp_id = al.emp_id 
                               WHERE al.user_type = 'hr_payroll' AND e.status = 1 
                               ORDER BY e.emp_id ASC LIMIT 1";
            $res_hr_payroll = mysqli_query($conDB, $sql_hr_payroll);
            if ($res_hr_payroll && ($row_hr_payroll = mysqli_fetch_assoc($res_hr_payroll))) {
                if (!in_array($row_hr_payroll['emp_id'], $chain)) {
                    $chain[] = $row_hr_payroll['emp_id'];
                    $chain_details[] = [
                        'emp_id' => $row_hr_payroll['emp_id'],
                        'name' => $row_hr_payroll['name'],
                        'label' => 'HR Payroll',
                        'level' => count($chain)
                    ];
                }
            }
            if ($res_hr_payroll) mysqli_free_result($res_hr_payroll);
        }

        // STEP 7: Add GR Officer (ONLY if fly_type = 'annual' AND vac_type = 'Fly')
        // GR Officer handles ticket payments and exit-reentry permits for annual fly vacations
        $is_fly_vacation = ($fly_type === 'annual' && strtolower($vac_type) === 'fly');
        if ($is_fly_vacation) {
            $sql_gr_officer = "SELECT e.emp_id, e.name FROM employees e 
                               JOIN admin_login al ON e.emp_id = al.emp_id 
                               WHERE al.user_type = 'gr_officer' AND e.status = 1 
                               ORDER BY e.emp_id ASC LIMIT 1";
            $res_gr_officer = mysqli_query($conDB, $sql_gr_officer);
            if ($res_gr_officer && ($row_gr_officer = mysqli_fetch_assoc($res_gr_officer))) {
                if (!in_array($row_gr_officer['emp_id'], $chain)) {
                    $chain[] = $row_gr_officer['emp_id'];
                    $chain_details[] = [
                        'emp_id' => $row_gr_officer['emp_id'],
                        'name' => $row_gr_officer['name'],
                        'label' => 'GR Officer (Final - Ticket & Exit Fee)',
                        'level' => count($chain),
                        'is_final' => true
                    ];
                }
            }
            if ($res_gr_officer) mysqli_free_result($res_gr_officer);
        }

        // Return the complete chain
        echo json_encode([
            'status' => 200,
            'chain' => $chain,
            'chain_details' => $chain_details,
            'total_levels' => count($chain),
            'flow_type' => $is_fly_vacation ? 'with_gr_officer' : 'standard'
        ]);
        exit;

    } catch (Exception $e) {
        send_json_response('Error', $e->getMessage(), 'error', 500);
        exit;
    }
}
// =================================================================
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
    $emptype = isset($_POST['emptype']) ? $_POST['emptype'] : '';
    $emp_name = isset($_POST['emp_name']) ? str_replace(' ', '', $_POST['emp_name']) : '';
    list($type, $data) = explode(';', $data);
    list(, $data) = explode(',', $data);
    $data = base64_decode($data);
    $imageName = time() . '.png';
    $filepath = "./../../assets/emp_pics/";
    $filepathup = "./assets/emp_pics/";
    $imagenameu = $emp_id."".$id."".$emp_name."".$imageName;
    if (empty($data) || (isset($data['error']) && $data['error'] == UPLOAD_ERR_NO_FILE)) {
        send_json_response("Error", "No Picture uploaded", "error");
    } else {
        // Save the file
        $file_saved = file_put_contents($filepath . $emp_id."".$id."".$emp_name."".$imageName , $data);
        
        if (!$file_saved) {
            send_json_response("Error", "Failed to save image file", "error");
            exit;
        }
        
        // Update database based on emptype
        if ($emptype == 'employee') {
            try {
                $stmt = $pdo->prepare("INSERT INTO `employee_temp_contants` (`emp_id`, `type`, `path`) VALUES (:emp_id, 'Profile Picture', :filepath)");
                $stmt->execute([':emp_id' => $emp_id, ':filepath' => $filepathup . $imagenameu]);
                send_json_response("Success!", "Image Uploaded Successfully.", "success");
            } catch(Exception $e) {
                send_json_response("Database Error", "The catch block is working. The error was: " . $e->getMessage(), "error");
            }
        } else {
            // Direct update to employees table
            try {
                $stmt = $pdo->prepare("UPDATE `employees` SET `avatar` = :avatar WHERE `id` = :id AND `emp_id` = :emp_id");
                $stmt->execute([':avatar' => $filepathup . $imagenameu, ':id' => $id, ':emp_id' => $emp_id]);
                send_json_response("Success!", "Image Uploaded Successfully.", "success");
            } catch(Exception $e) {
                send_json_response("Database Error", "The catch block is working. The error was: " . $e->getMessage(), "error");
            }
        }
    }
} elseif($ajaxType == 'update_salary') {
        try {
            $emp_id = $_POST['emp_id'];
            $postedTotal = (float)$_POST['totalsal'];

            // Define allowed salary components (whitelist)
            $allowedFields = [
                'basic', 'housing', 'transport', 'food', 'misc',
                'cashier', 'fuel', 'tel', 'other', 'guard'
            ];

            // Check if employee already has a salary record in emp_salary
            $checkExistingStmt = $pdo->prepare("SELECT id FROM emp_salary WHERE emp_id = :emp_id");
            $checkExistingStmt->execute([':emp_id' => $emp_id]);
            $existingSalaryRecord = $checkExistingStmt->fetch();

            if ($existingSalaryRecord) {
                send_json_response("Error", "This employee already has a salary record. Please update the existing record instead.", "error");
                exit;
            }

            // Calculate sum of submitted components for verification
            $componentsSum = 0;
            foreach ($allowedFields as $field) {
                $componentsSum += (float)($_POST[$field] ?? 0);
            }

            // Verify that the sum of the individual components matches the submitted total
            if (abs($componentsSum - $postedTotal) > 0.01) {
                send_json_response("Error", "Salary components do not add up to the total.", "error");
                exit;
            }

            // Validate basic salary
            if (empty($_POST['basic']) || (float)$_POST['basic'] <= 0) {
                send_json_response("Error", "Basic salary is missing or invalid.", "error");
                exit;
            }

            // Check if the total salary matches the master salary in the employees table
            $stmt = $pdo->prepare("SELECT salary FROM employees WHERE emp_id = :emp_id");
            $stmt->execute([':emp_id' => $emp_id]);
            $masterSalary = $stmt->fetchColumn();

            if ($masterSalary === false) {
                send_json_response("Error", "Employee not found or master salary missing.", "error");
                exit;
            }

            if (abs($masterSalary - $postedTotal) > 0.01) {
                send_json_response("Error", "Master salary does not match the posted total salary.", "error");
                exit;
            }

            // Process dynamic fields
            $salaryData = [':emp_id' => $emp_id];
            $columns = ['emp_id'];
            $placeholders = [':emp_id'];
            foreach ($allowedFields as $field) {
                if (isset($_POST[$field])) {
                    $value = (float)$_POST[$field];
                    $salaryData[":$field"] = $value;
                    $columns[] = $field;
                    $placeholders[] = ":$field";
                }
            }

            // Verify we have data to insert
            if (count($columns) <= 1) {
                send_json_response("Error", "No valid salary components provided.", "error");
                exit;
            }

            // Begin transaction
            $pdo->beginTransaction();

            // 1. Check if record exists and update status to 0 if it does
            $checkStmt = $pdo->prepare("SELECT id FROM emp_salary WHERE emp_id = :emp_id AND status = 1");
            $checkStmt->execute([':emp_id' => $emp_id]);
            $existingRecord = $checkStmt->fetch();

            if ($existingRecord) {
                $updateStmt = $pdo->prepare("UPDATE emp_salary SET status = 0 WHERE id = :id");
                $updateStmt->execute([':id' => $existingRecord['id']]);
            }

            // 2. Insert new record with status = 1
            $columns[] = 'status';
            $placeholders[] = ':status';
            $salaryData[':status'] = 1;

            $sql = "INSERT INTO emp_salary (" . implode(', ', $columns) . ") 
                    VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($salaryData);

            // Commit transaction
            $pdo->commit();

            send_json_response("Success", "Salary updated successfully.", "success");

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            send_json_response("Error", "Database error: " . $e->getMessage(), "error");
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            send_json_response("Error", "General error: " . $e->getMessage(), "error");
        }
        exit;
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
    $section_name = []; // Initialize
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
    $sub_type = []; // Initialize
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
            mysqli_query($conDB, "UPDATE `employees` SET `avatar`='".$datackh['path']."' WHERE `emp_id`='".(int)$_POST['empid']."' ");
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
    $name = []; // Initialize
    while($row = mysqli_fetch_assoc($stmt)) {
        $name[] = $row;
    }
    mysqli_free_result($stmt); // <-- FIX
    $data = [
        'data'      => $name,
        'status'    => 200
    ];
    echo json_encode($data);  
} elseif ($ajaxType == "check_pending_update") {
    // Check if employee has any pending update requests for a specific type
    $empid = isset($_POST['empid']) ? mysqli_real_escape_string($conDB, $_POST['empid']) : '';
    $type = isset($_POST['type']) ? mysqli_real_escape_string($conDB, $_POST['type']) : '';
    
    if (empty($empid)) {
        echo json_encode([
            'status' => 400,
            'message' => 'Employee ID is required'
        ]);
        exit;
    }
    
    if (empty($type)) {
        echo json_encode([
            'status' => 400,
            'message' => 'Type is required'
        ]);
        exit;
    }
    
    $query = "SELECT `type`, `created_at` FROM `employee_temp_contants` 
              WHERE `emp_id` = '$empid' AND `type` = '$type' AND `status` = 'Pending' 
              ORDER BY `created_at` DESC LIMIT 1";
    $result = mysqli_query($conDB, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        echo json_encode([
            'status' => 200,
            'has_pending' => true,
            'pending_type' => $row['type'],
            'created_at' => date('Y-m-d H:i', strtotime($row['created_at']))
        ]);
        mysqli_free_result($result);
    } else {
        echo json_encode([
            'status' => 200,
            'has_pending' => false
        ]);
    }
}elseif ($ajaxType == "emp_edit_contannt") {
    $sql = "INSERT INTO `employee_temp_contants` (`emp_id`, `type`, `path`) VALUES ('".(int)$_POST['empid']."', '".$_POST['edit_contant_check']."', '".$_POST[$_POST['edit_contant_check']]."')";
    if(mysqli_query($conDB, $sql)){
         send_json_response("Added!", "Record has been added successfully.", "success");
    } else {
        send_json_response("Error!", "Record not added because there are some error.", "error");
    }
} elseif ($ajaxType == "add_note") {
    try {
        // Get form data
        $empid = $_POST['empid'] ?? null;
        $note = $_POST['note'] ?? null;
        $noteType = $_POST['note_type'] ?? 'general';
        
        // Validate required fields
        if (empty($empid) || empty($note) || empty($noteType)) {
            send_json_response("Error!", "Missing required fields.", "error");
            exit;
        }

        // Handle file upload if attachment is provided
        $attachmentPath = null;
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['attachment'];
            $allowedTypes = ['application/pdf', 'application/msword', 
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'image/jpeg', 'image/jpg', 'image/png'];
            
            // Validate file type
            if (!in_array($file['type'], $allowedTypes)) {
                send_json_response("Error!", "Invalid file type. Only PDF, DOC, DOCX, JPG, PNG allowed.", "error");
                exit;
            }
            
            // Validate file size (max 5MB)
            if ($file['size'] > 5 * 1024 * 1024) {
                send_json_response("Error!", "File size must be less than 5MB.", "error");
                exit;
            }
            
            // Create upload directory if it doesn't exist
            $uploadDir = __DIR__ . '/../../assets/emp_notes/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Generate unique filename
            $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $uniqueFilename = 'note_' . $empid . '_' . time() . '_' . uniqid() . '.' . $fileExtension;
            $uploadPath = $uploadDir . $uniqueFilename;
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                $attachmentPath = 'assets/emp_notes/' . $uniqueFilename;
            } else {
                send_json_response("Error!", "Failed to upload attachment.", "error");
                exit;
            }
        }

        // Insert note into database
        $stmt = $pdo->prepare("INSERT INTO `emp_notice` (`emp_id`, `note`, `note_type`, `attachment`, `created_at`) 
                               VALUES (:emp_id, :note, :note_type, :attachment, :created_at)");
        $dataPost = [
            ':emp_id' => $empid,
            ':note' => $note,
            ':note_type' => $noteType,
            ':attachment' => $attachmentPath,
            ':created_at' => date('Y-m-d H:i:s')
        ];
        
        if ($stmt->execute($dataPost)) {
            $message = $attachmentPath 
                ? "Note with attachment has been added successfully." 
                : "Note has been added successfully.";
            send_json_response("Added!", $message, "success");
        } else {
            send_json_response("Error!", "Failed to add note. Please try again.", "error");
        }
        
    } catch (Exception $e) {
        error_log("Add Note Error: " . $e->getMessage());
        send_json_response("Error!", "An error occurred while adding the note.", "error");
    }
} elseif($ajaxType == "view_notes"){
    // Use INNER JOIN to ensure only employees with notes are returned.
    $sql = "SELECT
                `n`.`id`, `n`.`note`, `n`.`note_type`, `n`.`attachment`, `n`.`status`, `n`.`created_at`,
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
} elseif(isset($ajaxType) && $ajaxType == 'emp_temp_contant'){ // Duplicate ajaxType
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
        $updateField = '';
        switch ($request['type']) {
            case 'Mobile':          $updateField = 'mobile'; break;
            case 'Email':           $updateField = 'email'; break;
            case 'Passport No':     $updateField = 'passport_number'; break;
            case 'Passport Exp':    $updateField = 'passport_exp'; break;
            case 'Address':         $updateField = 'address'; break;
            case 'Profile Picture': $updateField = 'avatar'; break;
            // case 'Upload Documents': $updateField = 'upload_documents'; break;
        }
        $updateValue = ($request['path']) ? $request['path'] : $request['new_value'];
        // 3. Update the main employees table if a valid field was found
        if (!empty($updateField)) {
            $updateStmt = $pdo->prepare("UPDATE `employees` SET {$updateField} = ? WHERE emp_id = ?");
            $updateStmt->execute([$updateValue, $empId]);
        }
        // 4. If Employee Documents or Upload Documents, approve the document in emp_docu
        if ($request['type'] === 'Employee Documents' || $request['type'] === 'Upload Documents') {
            $docStmt = $pdo->prepare("UPDATE emp_docu SET status = 'A' WHERE emp_id = ? AND pgid = ?");
            $docStmt->execute([$empId, $requestId]);
            $rowsUpdated = $docStmt->rowCount();
            
            // Log for debugging
            error_log("Document Approval: emp_id={$empId}, pgid={$requestId}, rows_updated={$rowsUpdated}");
            
            if ($rowsUpdated === 0) {
                error_log("WARNING: No emp_docu records found with emp_id={$empId} AND pgid={$requestId}");
            }
        }
        // 5. Update the status of the temp request to 'Approved'
        $finalStmt = $pdo->prepare("UPDATE employee_temp_contants SET status = 'Approved', notes = ? WHERE id = ?");
        $finalStmt->execute([$notes, $requestId]);
        echo json_encode(['type' => 'success', 'title' => 'Approved!', 'message' => 'Employee information has been successfully updated.']);
    } catch (PDOException $e) {
        echo json_encode(['type' => 'error', 'title' => 'Database Error', 'message' => 'An error occurred while updating the data.']);
    }
    // --- If the request is REJECTED ---
    } elseif ($approvalAction == 'not_approve') {
        try {
            // 1. Fetch the request details to get the file path
            $stmt = $pdo->prepare("SELECT type, path FROM employee_temp_contants WHERE id = ? AND emp_id = ?");
            $stmt->execute([$requestId, $empId]);
            $request = $stmt->fetch();
            
            if ($request && !empty($request['path'])) {
                // 2. If it's a document upload or profile picture, delete the physical file
                if (in_array($request['type'], ['Employee Documents', 'Upload Documents', 'Profile Picture'])) {
                    // Normalize path to use forward slashes and construct absolute path correctly
                    $relativePath = str_replace('\\', '/', $request['path']);
                    $filePath = realpath(__DIR__ . '/../../' . $relativePath);
                    
                    // Verify file exists and delete it
                    if ($filePath && file_exists($filePath)) {
                        if (unlink($filePath)) {
                            error_log("Rejected file deleted successfully: {$filePath}");
                        } else {
                            error_log("Failed to delete file: {$filePath}");
                        }
                    } else {
                        error_log("File not found for deletion: " . __DIR__ . '/../../' . $relativePath);
                    }
                }
                
                // 3. If it's a document upload, also delete from emp_docu table
                if ($request['type'] === 'Employee Documents' || $request['type'] === 'Upload Documents') {
                    $deleteDocStmt = $pdo->prepare("DELETE FROM emp_docu WHERE emp_id = ? AND pgid = ? AND status = 'I'");
                    $deleteDocStmt->execute([$empId, $requestId]);
                    error_log("Rejected document removed from emp_docu: emp_id={$empId}, pgid={$requestId}");
                }
            }
            
            // 4. Update the status to 'Rejected' and add the reason
            $finalStmt = $pdo->prepare("UPDATE employee_temp_contants SET status = 'Rejected', notes = ? WHERE id = ?");
            $finalStmt->execute([$notes, $requestId]);
            echo json_encode(['type' => 'success', 'title' => 'Rejected', 'message' => 'The update request has been rejected and the file has been deleted.']);
        } catch (PDOException $e) {
            error_log("Rejection error: " . $e->getMessage());
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
} elseif($ajaxType == 'get_vacation_balance') {
    // Get employee's remaining vacation days balance
    $empid = isset($_POST['empid']) ? (int)$_POST['empid'] : 0;
    
    if ($empid > 0) {
        // Fetch available_balance from emp_vacation_balance table (latest record)
        $stmt = mysqli_query($conDB, "SELECT `available_balance` FROM `emp_vacation_balance` WHERE `emp_id` = {$empid} ORDER BY `last_updated` DESC LIMIT 1");
        
        if ($stmt && mysqli_num_rows($stmt) > 0) {
            $row = mysqli_fetch_assoc($stmt);
            $balance = (float)$row['available_balance'];
            mysqli_free_result($stmt);
            
            echo json_encode([
                'status' => 200,
                'balance' => $balance
            ]);
        } else {
            if ($stmt) mysqli_free_result($stmt);
            echo json_encode([
                'status' => 404,
                'balance' => 0,
                'message' => 'No vacation balance record found.'
            ]);
        }
    } else {
        echo json_encode([
            'status' => 400,
            'balance' => 0,
            'message' => 'Invalid employee ID.'
        ]);
    }
    exit;
} elseif($ajaxType == 'calculate_encash_salary') {
    // Calculate encashment salary based on daily rate (matches VacationCalculator logic)
    $empid = isset($_POST['empid']) ? (int)$_POST['empid'] : 0;
    $days = isset($_POST['days']) ? (float)$_POST['days'] : 0;
    
    if ($empid > 0 && $days > 0) {
        // Get active salary record with all components
        $stmt = mysqli_query($conDB, "SELECT * FROM `emp_salary` WHERE `emp_id` = {$empid} AND `status` = 1 ORDER BY `id` DESC LIMIT 1");
        
        if ($stmt && mysqli_num_rows($stmt) > 0) {
            $row = mysqli_fetch_assoc($stmt);
            mysqli_free_result($stmt);
            
            // Calculate total monthly salary from all components (same as VacationCalculator)
            $total_monthly_salary = 
                (float)($row['basic'] ?? 0) + 
                (float)($row['housing'] ?? 0) + 
                (float)($row['transport'] ?? 0) + 
                (float)($row['food'] ?? 0) + 
                (float)($row['misc'] ?? 0) + 
                (float)($row['cashier'] ?? 0) + 
                (float)($row['fuel'] ?? 0) + 
                (float)($row['tel'] ?? 0) + 
                (float)($row['other'] ?? 0) + 
                (float)($row['guard'] ?? 0);
            
            // Calculate daily rate: monthly_salary / 30 days (30/360 day-count convention)
            // Then multiply to calculate encashment amount
            $daily_rate = $total_monthly_salary / 30;
            $encash_amount = $daily_rate * $days;
            
            echo json_encode([
                'status' => 200,
                'salary' => number_format($encash_amount, 2, '.', ''),
                'daily_rate' => number_format($daily_rate, 2, '.', ''),
                'total_monthly_salary' => number_format($total_monthly_salary, 2, '.', '')
            ]);
        } else {
            if ($stmt) mysqli_free_result($stmt);
            echo json_encode([
                'status' => 404,
                'salary' => '0.00',
                'message' => 'Employee salary data not found.'
            ]);
        }
    } else {
        echo json_encode([
            'status' => 400,
            'salary' => '0.00',
            'message' => 'Invalid employee ID or days.'
        ]);
    }
    exit;
}
// ============================================================================
// GET DOCUMENT TYPES FROM DATABASE
// ============================================================================
elseif($ajaxType == 'get_document_types') {
    try {
        $stmt = $pdo->prepare("SELECT `id`, `duc_type` FROM `docu_type` ORDER BY `duc_type` ASC");
        $stmt->execute();
        $documentTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'status' => 200,
            'data' => $documentTypes,
            'message' => 'Document types loaded successfully.'
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'status' => 500,
            'data' => [],
            'message' => 'Error loading document types: ' . $e->getMessage()
        ]);
    }
    exit;
}
// ============================================================================
// UPLOAD EMPLOYEE DOCUMENT
// ============================================================================
elseif($ajaxType == 'upload_employee_document') {
    try {
        // Accept both legacy and new field names
        $emp_id_up   = isset($_POST['emp_id']) ? (int)$_POST['emp_id'] : 0;
        $docu_typ_up = $_POST['docu_typ'] ?? $_POST['document_type'] ?? null; // can be name or id
        // Follow user_type=employee logic (fallback to old 'emptype')
        $requestType = strtolower((string)($_POST['user_type'] ?? $_POST['emptype'] ?? ''));
        $isEmployee  = ($requestType === 'employee');
        // Use posted id as pgid (do not auto-link to temp id); default to 0 to satisfy NOT NULL
        $pgid        = isset($_POST['id']) ? (int)$_POST['id'] : 0;

        // Resolve uploaded file from either 'file' or 'document_file'
        $file = $_FILES['file'] ?? ($_FILES['document_file'] ?? null);

        // Basic validation
        if ($emp_id_up <= 0) {
            send_json_response("Error", "Invalid employee ID.", "error");
            exit;
        }
        if (empty($docu_typ_up)) {
            send_json_response("Error", "Document type is required.", "error");
            exit;
        }
        if (!$file || !isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            send_json_response("Error", "No file uploaded or upload error.", "error");
            exit;
        }

        // Validate file size (max 5MB)
        $maxFileSize = 5 * 1024 * 1024;
        if (($file['size'] ?? 0) > $maxFileSize) {
            send_json_response("Error", "File size exceeds 5MB limit.", "error");
            exit;
        }

        // Validate file extension
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png'];
        if (!in_array($file_ext, $allowed_extensions)) {
            send_json_response("Error", 'Invalid file type. Allowed: ' . implode(', ', $allowed_extensions), "error");
            exit;
        }

        // Ensure upload directory exists
        $uploadDir = __DIR__ . '/../../assets/emp_documents/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Sanitize part of filename from document type
        $doc_segment = preg_replace('/[^A-Za-z0-9]+/', '_', strtoupper((string)$docu_typ_up));
        $filename_po = 'EMP' . $emp_id_up . '_' . $doc_segment . '_' . time() . '_' . mt_rand(1000,9999) . '.' . $file_ext;
        $uploadFilePath = $uploadDir . $filename_po;
        $publicFilePath = 'assets/emp_documents/' . $filename_po; // for DB storage
        $filepathup = 'assets/emp_documents/';

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $uploadFilePath)) {
            send_json_response("Error", "Failed to save uploaded file.", "error");
            exit;
        }

        // Begin transaction for DB operations
        $pdo->beginTransaction();

        // If uploaded by employee (self-service), follow the exact method you provided
        if ($isEmployee) {
            // Insert into employee_temp_contants (store public path like existing code)
            $stmt1 = $pdo->prepare("INSERT INTO `employee_temp_contants` (`emp_id`, `type`, `path`) VALUES (:emp_id, 'Upload Documents', :filepath)");
            $stmt1->execute([':emp_id' => $emp_id_up, ':filepath' => $filepathup . $filename_po]);
            
            // Get the temp request ID that was just created
            $tempRequestId = (int)$pdo->lastInsertId();
            
            // Log for debugging
            error_log("Document Upload: emp_id={$emp_id_up}, tempRequestId={$tempRequestId}, filename={$filename_po}");

            // Insert into emp_docu with status = 'I' and link to temp request via pgid
            $stmt2 = $pdo->prepare("INSERT INTO `emp_docu` (`emp_id`, `docu_typ`, `path`, `docu_ext`, `pgid`, `status`) VALUES (:emp_id, :docu_typ, :filename, :ext, :pgid, 'I')");
            $stmt2->execute([
                ':emp_id'   => $emp_id_up,
                ':docu_typ' => $docu_typ_up,
                ':filename' => $filename_po,
                ':ext'      => $file_ext,
                ':pgid'     => $tempRequestId // Link to temp request for approval
            ]);
            
            // Log the inserted emp_docu record
            error_log("emp_docu inserted with pgid={$tempRequestId}, status=I");
        } else {
            // Direct admin upload -> insert without status (let DB default apply) and use posted pgid (or 0)
            $stmt = $pdo->prepare("INSERT INTO `emp_docu` (`emp_id`, `docu_typ`, `path`, `docu_ext`, `pgid`) VALUES (:emp_id, :docu_typ, :filename, :ext, :pgid)");
            $stmt->execute([
                ':emp_id'   => $emp_id_up,
                ':docu_typ' => $docu_typ_up,
                ':filename' => $filename_po,
                ':ext'      => $file_ext,
                ':pgid'     => $pgid // will be 0 if not provided
            ]);
        }

        $pdo->commit();
        send_json_response("Success", "Document uploaded successfully.", "success");
    } catch (PDOException $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if (isset($uploadFilePath) && file_exists($uploadFilePath)) {
            unlink($uploadFilePath);
        }
        send_json_response("Database Error", "Failed to save record: " . $e->getMessage(), "error");
    } catch (Exception $e) {
        if (isset($uploadFilePath) && file_exists($uploadFilePath)) {
            unlink($uploadFilePath);
        }
        send_json_response("Error", $e->getMessage(), "error");
    }
}

?>
