<?php

require_once __DIR__ . '/../session_check.php';
require_once __DIR__ . '/../helper_functions.php';

// Set JSON header
header('Content-Type: application/json');

try {
    // Get DataTables parameters
    $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 0;
    $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
    $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
    $search = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
    
    // Get column-specific filters from DataTables format
    $statusFilter = '';
    $deptFilter = '';
    $roleFilter = '';
    $companyFilter = '';
    $allowedDeptFilter = '';
    $allowedEmployeeFilter = '';
    
    // Check if columns array exists (server-side filtering)
    if (isset($_POST['columns']) && is_array($_POST['columns'])) {
        // Column 5: Department
        $deptFilter = isset($_POST['columns'][5]['search']['value']) ? $_POST['columns'][5]['search']['value'] : '';
        // Column 7: Role
        $roleFilter = isset($_POST['columns'][7]['search']['value']) ? $_POST['columns'][7]['search']['value'] : '';
        // Column 8: Company
        $companyFilter = isset($_POST['columns'][8]['search']['value']) ? $_POST['columns'][8]['search']['value'] : '';
        // Column 9: Allowed Departments
        $allowedDeptFilter = isset($_POST['columns'][9]['search']['value']) ? $_POST['columns'][9]['search']['value'] : '';
        // Column 10: Allowed Employees
        $allowedEmployeeFilter = isset($_POST['columns'][10]['search']['value']) ? $_POST['columns'][10]['search']['value'] : '';
        // Column 11: Status
        $statusFilter = isset($_POST['columns'][11]['search']['value']) ? $_POST['columns'][11]['search']['value'] : '';
    }
    
    // Build base query
    $sql = "SELECT 
                `admin_login`.`id` AS `lid`,
                `admin_login`.`id_iqama`,
                `admin_login`.`emp_id`,
                `admin_login`.`status`,
                `admin_login`.`user_type`,
                `employees`.`dept`,
                `admin_login`.`email`,
                `admin_login`.`allowed_companies`,
                `admin_login`.`allowed_departments`,
                `admin_login`.`allowed_employees`,
                `employees`.`name` AS `efullname`,
                `employees`.`mobile`,
                `department`.`dep_nme` AS `deptnme`
            FROM `admin_login`
            LEFT JOIN `employees` ON `employees`.`emp_id` = `admin_login`.`emp_id`
            LEFT JOIN `department` ON `department`.`id` = `employees`.`dept`
            WHERE 1=1";
    // Employee filter - restrict by accessible employees
    $employee_filter = getEmployeeFilterSQL('admin_login.emp_id', true);
    if (!empty($employee_filter)) {
        $sql .= $employee_filter;
    }
    
    // Apply global search filter
    if (!empty($search)) {
        $search = mysqli_real_escape_string($conDB, $search);
        $sql .= " AND (
            `admin_login`.`id` LIKE '%$search%' OR
            `admin_login`.`id_iqama` LIKE '%$search%' OR
            `admin_login`.`emp_id` LIKE '%$search%' OR
            `employees`.`name` LIKE '%$search%' OR
            `employees`.`mobile` LIKE '%$search%' OR
            `department`.`dep_nme` LIKE '%$search%'
        )";
    }
    
    // Status filter - convert "Active" to 1, "Inactive" to 0
    if (!empty($statusFilter)) {
        if ($statusFilter === 'Active') {
            $sql .= " AND `admin_login`.`status` = 1";
        } elseif ($statusFilter === 'Inactive') {
            $sql .= " AND `admin_login`.`status` = 0";
        }
    }
    
    // Department filter
    if (!empty($deptFilter)) {
        $deptFilter = mysqli_real_escape_string($conDB, $deptFilter);
        $sql .= " AND `department`.`dep_nme` = '$deptFilter'";
    }
    
    // Role filter
    if (!empty($roleFilter)) {
        $roleFilter = mysqli_real_escape_string($conDB, $roleFilter);
        $sql .= " AND `admin_login`.`user_type` = '$roleFilter'";
    }
    
    // Company filter - search using company IDs from allowed_companies
    if (!empty($companyFilter) && $companyFilter !== 'All Companies') {
        $companyFilter = mysqli_real_escape_string($conDB, $companyFilter);
        // First find all company IDs matching the company name
        $companyIds = [];
        $compQuery = mysqli_query($conDB, "SELECT `id` FROM `companies` WHERE `comp_name` LIKE '%$companyFilter%'");
        while ($comp = mysqli_fetch_assoc($compQuery)) {
            $companyIds[] = $comp['id'];
        }
        
        if (!empty($companyIds)) {
            // Search for users who have any of these company IDs in their allowed_companies
            $idList = implode('|', $companyIds);
            $sql .= " AND `admin_login`.`allowed_companies` REGEXP '[$idList]'";
        } else {
            // No matching companies, return no results
            $sql .= " AND 1=0";
        }
    }

    // Allowed department filter - search using department IDs from allowed_departments
    if (!empty($allowedDeptFilter) && $allowedDeptFilter !== 'All Departments') {
        $allowedDeptFilter = mysqli_real_escape_string($conDB, $allowedDeptFilter);
        $deptIds = [];
        $deptQuery = mysqli_query($conDB, "SELECT `id` FROM `department` WHERE `dep_nme` LIKE '%$allowedDeptFilter%'");
        while ($dept = mysqli_fetch_assoc($deptQuery)) {
            $deptIds[] = $dept['id'];
        }

        if (!empty($deptIds)) {
            $idList = implode('|', $deptIds);
            $sql .= " AND `admin_login`.`allowed_departments` REGEXP '[$idList]'";
        } else {
            $sql .= " AND 1=0";
        }
    }

    // Allowed employee filter - search using employee IDs from allowed_employees
    if (!empty($allowedEmployeeFilter) && $allowedEmployeeFilter !== 'All Employees') {
        $allowedEmployeeFilter = mysqli_real_escape_string($conDB, $allowedEmployeeFilter);
        $empIds = [];
        $empQuery = mysqli_query($conDB, "SELECT `emp_id` FROM `employees` WHERE `name` LIKE '%$allowedEmployeeFilter%' OR `emp_id` LIKE '%$allowedEmployeeFilter%'");
        while ($emp = mysqli_fetch_assoc($empQuery)) {
            $empIds[] = $emp['emp_id'];
        }

        if (!empty($empIds)) {
            $idList = implode('|', array_map('intval', $empIds));
            $sql .= " AND `admin_login`.`allowed_employees` REGEXP '[$idList]'";
        } else {
            $sql .= " AND 1=0";
        }
    }
    
    // Count total records before filtering
    $countSql = "SELECT COUNT(*) as total FROM `admin_login`
                 LEFT JOIN `employees` ON `employees`.`emp_id` = `admin_login`.`emp_id`
                 LEFT JOIN `department` ON `department`.`id` = `employees`.`dept`
                 WHERE 1=1";
    $totalResult = mysqli_query($conDB, $countSql);
    $totalRow = mysqli_fetch_assoc($totalResult);
    $totalRecords = $totalRow['total'] ?? 0;
    
    // Count filtered records
    $filteredResult = mysqli_query($conDB, $sql);
    if ($filteredResult) {
        $filteredRecords = mysqli_num_rows($filteredResult);
        mysqli_data_seek($filteredResult, 0);  // Reset pointer
    } else {
        $filteredRecords = 0;
    }
    
    // Add order and limit
    $sql .= " ORDER BY `admin_login`.`id` DESC LIMIT $start, $length";
    
    $query = mysqli_query($conDB, $sql);
    $data = [];
    
    while ($rec = mysqli_fetch_assoc($query)) {
        $id_user_usr = $rec["lid"];
        $id_iqama = $rec["id_iqama"] ?? '';
        $empid = $rec["emp_id"];
        $firstnme_usr = $rec["efullname"] ?? '';
        $usrty_usr = $rec["user_type"];
        $dept_usr = $rec["deptnme"] ?? '';
        $mobile_usr = $rec["mobile"] ?? '';
        $status_usr = $rec["status"];
        $allowed_companies = $rec["allowed_companies"];
        $allowed_departments = $rec["allowed_departments"];
        $allowed_employees = $rec["allowed_employees"];
        
        // Convert JSON allowed_companies to company names with badge formatting
        $company_names = '<span class="all-employees-badge">All Companies</span>';
        if (!empty($allowed_companies)) {
            $companies_array = json_decode($allowed_companies, true);
            if (is_array($companies_array) && !empty($companies_array)) {
                $company_ids = implode(',', array_map('intval', $companies_array));
                $comp_query = mysqli_query($conDB, "SELECT DISTINCT `comp_name` FROM `companies` WHERE `id` IN ($company_ids) OR `comp_id` IN ($company_ids) ORDER BY `comp_name`");
                if ($comp_query && mysqli_num_rows($comp_query) > 0) {
                    $badges = [];
                    while ($comp_row = mysqli_fetch_assoc($comp_query)) {
                        $badges[] = '<span class="employee-badge">' . htmlspecialchars($comp_row['comp_name']) . '</span>';
                    }
                    $company_names = '<div class="employee-badges-container">' . implode('', $badges) . '</div>';
                }
            }
        }
        
        // Convert JSON allowed_departments to department names with badge formatting
        $department_names = '<span class="all-employees-badge">All Departments</span>';
        if (!empty($allowed_departments)) {
            $departments_array = json_decode($allowed_departments, true);
            if (is_array($departments_array) && !empty($departments_array)) {
                $department_ids = implode(',', array_map('intval', $departments_array));
                $dept_query = mysqli_query($conDB, "SELECT DISTINCT `dep_nme` FROM `department` WHERE `id` IN ($department_ids) ORDER BY `dep_nme`");
                if ($dept_query && mysqli_num_rows($dept_query) > 0) {
                    $badges = [];
                    while ($dept_row = mysqli_fetch_assoc($dept_query)) {
                        $badges[] = '<span class="employee-badge">' . htmlspecialchars($dept_row['dep_nme']) . '</span>';
                    }
                    $department_names = '<div class="employee-badges-container">' . implode('', $badges) . '</div>';
                }
            }
        }

        // Convert JSON allowed_employees to employee names with badge formatting
        $employee_names = '<span class="all-employees-badge">All Employees</span>';
        if (!empty($allowed_employees)) {
            $employees_array = json_decode($allowed_employees, true);
            if (is_array($employees_array) && !empty($employees_array)) {
                $employee_ids = implode(',', array_map('intval', $employees_array));
                $emp_query = mysqli_query($conDB, "SELECT `name` AS `emp_display` FROM `employees` WHERE `emp_id` IN ($employee_ids) ORDER BY `name`");
                if ($emp_query && mysqli_num_rows($emp_query) > 0) {
                    $badges = [];
                    while ($emp_row = mysqli_fetch_assoc($emp_query)) {
                        $badges[] = '<span class="employee-badge">' . parseName($emp_row['emp_display']) . '</span>';
                    }
                    $employee_names = '<div class="employee-badges-container">' . implode('', $badges) . '</div>';
                }
            }
        }
        
        // Build row data
        $row = [
            $id_user_usr,                                    // 0: ID
            '',                                               // 1: Checkbox (empty, will be rendered in JS)
            htmlspecialchars($id_iqama),                     // 2: ID/IQAMA
            $empid ?? '',                                     // 3: Employee ID
            htmlspecialchars($firstnme_usr),                 // 4: Employee Name
            htmlspecialchars($dept_usr),                     // 5: Department
            htmlspecialchars($mobile_usr),                   // 6: Mobile
            $usrty_usr,                                      // 7: User Type
            $company_names,                                  // 8: Allowed Companies (contains HTML badges - don't escape)
            $department_names,                               // 9: Allowed Departments (contains HTML badges - don't escape)
            $employee_names,                                 // 10: Allowed Employees (contains HTML badges - don't escape)
            $status_usr,                                      // 11: Status
            $id_user_usr,                                     // 12: Action (ID for button)
            $rec                                              // 13: Full record data (for edit modal) - will be JSON encoded by json_encode()
        ];
        
        $data[] = $row;
    }
    
    // Return JSON response - this will properly encode the entire array including nested record data
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $filteredRecords,
        'data' => $data
    ]);
    
} catch (Exception $e) {
    error_log("getAllUsersData.php Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error processing user data',
        'message' => $e->getMessage()
    ]);
}


?>
