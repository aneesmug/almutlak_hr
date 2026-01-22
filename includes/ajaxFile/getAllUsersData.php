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
    
    // Check if columns array exists (server-side filtering)
    if (isset($_POST['columns']) && is_array($_POST['columns'])) {
        // Column 4: Department
        $deptFilter = isset($_POST['columns'][4]['search']['value']) ? $_POST['columns'][4]['search']['value'] : '';
        // Column 6: Role
        $roleFilter = isset($_POST['columns'][6]['search']['value']) ? $_POST['columns'][6]['search']['value'] : '';
        // Column 7: Company
        $companyFilter = isset($_POST['columns'][7]['search']['value']) ? $_POST['columns'][7]['search']['value'] : '';
        // Column 8: Status
        $statusFilter = isset($_POST['columns'][8]['search']['value']) ? $_POST['columns'][8]['search']['value'] : '';
    }
    
    // Build base query
    $sql = "SELECT 
                `admin_login`.`id` AS `lid`,
                `admin_login`.`id_iqama`,
                `admin_login`.`emp_id`,
                `admin_login`.`status`,
                `admin_login`.`user_type`,
                `admin_login`.`dept`,
                `admin_login`.`email`,
                `admin_login`.`allowed_companies`,
                `employees`.`name` AS `efullname`,
                `employees`.`mobile`,
                `department`.`dep_nme` AS `deptnme`
            FROM `admin_login` 
            LEFT JOIN `employees` ON `employees`.`emp_id` = `admin_login`.`emp_id`
            LEFT JOIN `department` ON `department`.`id` = `admin_login`.`dept`
            WHERE 1=1";
    
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
    
    // Count total records before filtering
    $countSql = "SELECT COUNT(*) as total FROM `admin_login` 
                 LEFT JOIN `employees` ON `employees`.`emp_id` = `admin_login`.`emp_id`
                 LEFT JOIN `department` ON `department`.`id` = `admin_login`.`dept`
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
        
        // Convert JSON allowed_companies to company names
        $company_names = "All Companies";
        if (!empty($allowed_companies)) {
            $companies_array = json_decode($allowed_companies, true);
            if (is_array($companies_array) && !empty($companies_array)) {
                $company_ids = implode(',', array_map('intval', $companies_array));
                // Try matching by id first, then by comp_id if not found
                $comp_query = mysqli_query($conDB, "SELECT GROUP_CONCAT(DISTINCT `comp_name` SEPARATOR ', ') AS `names` FROM `companies` WHERE `id` IN ($company_ids) OR `comp_id` IN ($company_ids)");
                if ($comp_query && $comp_row = mysqli_fetch_assoc($comp_query)) {
                    $company_names = $comp_row['names'] ?: "All Companies";
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
            htmlspecialchars($company_names),                // 8: Allowed Companies
            $status_usr,                                      // 9: Status
            $id_user_usr,                                     // 10: Action (ID for button)
            $rec                                              // 11: Full record data (for edit modal) - will be JSON encoded by json_encode()
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
