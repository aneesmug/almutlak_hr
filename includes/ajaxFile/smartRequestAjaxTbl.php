<?php
/*
MODIFICATION SUMMARY (008-smartRequestAjaxTbl.php):
- UPDATED: Role-based filtering ($additionalConditions) for Management (dept 10) to see all requests, same as Admin and Finance (dept 2).
- UPDATED: Role-based filtering for Department Managers (Non-Finance/Mgmt) to include requests they created OR are in the approval chain for.
- UPDATED: Default role-based filtering (Employee/Assistant) to show requests they created OR any request where they are included in the approval chain.
- ADDED: A new `LEFT JOIN` to `request_approvers` as `ra_any` to check if the user is in the approval chain at *any* level.
*/
/*
MODIFICATION SUMMARY (007-smartRequestAjaxTbl.php):
- FIXED: Fatal Error "Unknown column 'ra.your_actual_request_type_column_name' / 'ra.request_type'". Changed the LEFT JOIN condition to use the correct column `ra`.`request_type_id` and compare it against the ID `1` (representing 'smart_request' based on the `approval_request_types` table).
*/
/*
MODIFICATION SUMMARY (006-smartRequestAjaxTbl.php):
- FIXED: Fatal Error "Unknown column 'ra.approver_emp_id'". Replaced `ra`.`approver_emp_id` with the correct column name `ra`.`approver_id` in the `$additionalConditions` logic for GM and Manager roles, based on the provided table schema.
*/
/*
MODIFICATION SUMMARY (001-smartRequestAjaxTbl.php):
- UPDATED: Status filtering (`$typeSearchQuery`) now uses `sr.current_status` with the new general statuses ('draft', 'pending_approval', etc.).
- UPDATED: Role-based filtering (`$additionalConditions`) logic:
    - Admin/Finance: See all requests.
    - GM/Managers: See requests specifically pending *their* approval by joining `request_approvers`. Department Managers also see requests from their own department.
    - Employee/Others: See only requests they created.
- ADDED: `LEFT JOIN` to `request_approvers` table to facilitate the new role-based filtering.
- ADDED: Selection of `sr.current_approval_level` for use in the front-end status badge.
- MAINTAINED: `GROUP BY sr.inv_no` to prevent potential duplicates from the main `smart_request` table query structure.
*/

include './../../includes/db.php';
include './../../includes/init.php';

// Parameters sent by DataTables
$draw = $_POST['draw'];
$start = $_POST['start'];
$length = $_POST['length'];
$order = $_POST['order'][0] ?? ['column' => 0, 'dir' => 'desc'];

$user_type = $_POST['user_type'];
$user_dept = $_POST['user_dept'];
$emptype = $_POST['emptype'];
$emp_id = $_POST['emp_id'];
$payerOnly = isset($_POST['payerOnly']) ? (int)$_POST['payerOnly'] : 0;

$searchValue = mysqli_real_escape_string($conDB,$_POST['search']);
$typeValue = mysqli_real_escape_string($conDB,$_POST['smtStatus']); // Will be 'draft', 'pending_approval', etc.

## Search
$searchQuery = " ";
if($searchValue != ''){
    $searchQuery = " AND (
        `sr`.`inv_no` LIKE '%".$searchValue."%'
        OR `sr`.`sub_title` LIKE '%".$searchValue."%'
        OR `sr`.`sub_type` LIKE '%".$searchValue."%'
        OR `dept`.`dep_nme` LIKE '%".$searchValue."%'
        OR `sr`.`prep_by` LIKE'%".$searchValue."%'
    )";
}

// UPDATED: Status filter based on current_status
$typeSearchQuery = " ";
if($typeValue != ''){
    $typeSearchQuery = " AND `sr`.`current_status` = '".$typeValue."' ";
}

// Base SQL structure - FIXED JOIN Condition for request type
// *** Uses `ra`.`request_type_id` = 1 (ID for 'smart_request') ***
$baseSql = "FROM `smart_request` `sr`
            LEFT JOIN `department` `dept` ON `dept`.`id` = `sr`.`department`
            LEFT JOIN `request_approvers` `ra` ON `sr`.`inv_no` = `ra`.`request_inv_no`
                AND `ra`.`request_type_id` = 1 -- <<< FIXED HERE
                AND `sr`.`current_approval_level` = `ra`.`approval_level`
            LEFT JOIN `request_approvers` `ra_any` ON `sr`.`inv_no` = `ra_any`.`request_inv_no` -- NEW JOIN
                AND `ra_any`.`request_type_id` = 1 -- NEW JOIN
            WHERE 1 {$searchQuery} {$typeSearchQuery}";

// UPDATED: Role-based filtering conditions
$additionalConditions = ""; // Start empty

// Admin, Finance (2), and Management (10) see everything (unless payerOnly specified)
if ($payerOnly === 1) {
    // Restrict to records assigned to this user as payer and in pending_payment
    $additionalConditions = " AND `sr`.`current_status` = 'pending_payment' AND `sr`.`payable_by_emp_id` = ".(int)$emp_id;
}
else if ($user_type == 'administrator' || $user_dept == 2 || $user_dept == 10) {
    $additionalConditions = ''; // See all
}
// GM (who is NOT in dept 2 or 10)
else if ($user_type == 'gm') {
    // Show requests only if they are the current approver AND status is pending
    $additionalConditions = " AND `ra`.`approver_id` = ".(int)$emp_id." AND `sr`.`current_status` = 'pending_approval'";
}
// Department Manager (Non-Finance/Mgmt)
else if ($emptype == 'Manager') {
    $additionalConditions = " AND (
                                    (`ra`.`approver_id` = ".(int)$emp_id." AND `sr`.`current_status` = 'pending_approval') -- Pending their action
                                    OR
                                    (`sr`.`department` = " . (int)$user_dept . ") -- From their department
                                    OR
                                    (`ra_any`.`approver_id` = ".(int)$emp_id.") -- In their approval chain
                                    OR
                                    (`sr`.`emp_id` = " . (int)$emp_id . ") -- Created by them
                                )";
}
// Default case (Employee/Assistant etc.)
else {
    $additionalConditions = " AND (
                                    `sr`.`emp_id` = " . (int)$emp_id . " -- Created by them
                                    OR
                                    `ra_any`.`approver_id` = ".(int)$emp_id." -- In their approval chain
                                )";
}


// Build the final query for data fetching - ADDED sr.current_approval_level
$sql = "SELECT
            `sr`.`id`,
            `sr`.`inv_no`,
            `sr`.`sub_title`,
            `sr`.`sub_type`,
            `dept`.`dep_nme` AS `department`,
            `dept`.`dep_nme_ar` AS `department_ar`,
            `sr`.`prep_by`,
            `sr`.`created_at`,
            `sr`.`current_status` AS `status`,
            `sr`.`current_approval_level`
        " . $baseSql . $additionalConditions . "
        GROUP BY `sr`.`inv_no`
        ORDER BY `sr`.`id` DESC
        LIMIT " . (int)$start . ", " . (int)$length;

$query = mysqli_query($conDB, $sql);


// Fetch data and format as JSON
$data = array();
if ($query) { // Check if query was successful before fetching
    while ($row = mysqli_fetch_assoc($query)) {
        $data[] = array(
                "id"              =>$row['id'],
                "inv_no"          =>$row['inv_no'],
                "sub_title"       =>$row['sub_title'],
                "sub_type"        =>$row['sub_type'],
                "department"      =>($is_rtl ?? false ? __($row['department_ar']) : $row['department']),
                "prep_by"         =>$row["prep_by"],
                "created_at"      =>date("Y-m-d",strtotime($row["created_at"])),
                "status"          =>$row["status"],
                "current_approval_level" => $row["current_approval_level"], // Pass level to front-end
                "action"          =>($user_type == 'administrator')?"<div class='btn-group dropdown'>
                                <a href='javascript: void(0);' class='table-action-btn dropdown-toggle arrow-none btn btn-light btn-sm' data-toggle='dropdown' aria-expanded='false'><i class='mdi mdi-dots-horizontal'></i></a>
                                <div class='dropdown-menu dropdown-menu-right' x-placement='bottom-end' >
                                    <a href='open_request.php?id=$row[inv_no]' class='dropdown-item text-dark' ><i class='mdi mdi-eye-outline'></i></i> ". __('open') ."</a>
                                    <a href='javascript:void(0);' class='dropdown-item  text-danger deleteSmt' data-id='$row[inv_no]' ><i class='fa fa-trash mr-2 font-18 vertical-middle'></i>". __('delete') ."</a>
                                </div>
                                </div>":"<a href='open_request.php?id=$row[inv_no]' class='btn btn-dark btn-sm' ><i class='mdi mdi-eye-outline'></i></i> Open</a>",
            );
    }
} else {
    // Log the error if the main query fails
    error_log("MySQL Error in smartRequestAjaxTbl.php (Main Query): " . mysqli_error($conDB));
    error_log("Failing Query: " . $sql); // Log the exact query
}


// Count queries (Ensure base SQL includes JOIN for accurate filtering count)
// For Total Records, we don't need the complex conditions or JOIN
$sqlTotal = "SELECT COUNT(DISTINCT `sr`.`inv_no`) as allcount FROM `smart_request` `sr` WHERE 1";
// Filtered count needs the base SQL (with JOIN) and the role-specific conditions
$sqlFiltered = "SELECT COUNT(DISTINCT `sr`.`inv_no`) as allcount " . $baseSql . $additionalConditions;

$totalRecordsResult = mysqli_query($conDB, $sqlTotal);
$totalRecords = $totalRecordsResult ? (int)mysqli_fetch_assoc($totalRecordsResult)['allcount'] : 0;

// *** LINE 119/125 where errors were previously reported ***
$filteredRecordsResult = mysqli_query($conDB, $sqlFiltered);
// Check for errors specifically on the count query
if (!$filteredRecordsResult) {
    error_log("MySQL Error in smartRequestAjaxTbl.php (Count Query): " . mysqli_error($conDB));
    error_log("Failing Query: " . $sqlFiltered); // Log the exact query that failed
    $filteredRecords = 0; // Set to 0 on error
} else {
    $filteredRecords = (int)mysqli_fetch_assoc($filteredRecordsResult)['allcount'];
}


// Prepare JSON response
$response = array(
    "draw" => intval($draw),
    "recordsTotal" => intval($totalRecords),
    "recordsFiltered" => intval($filteredRecords),
    "data" => $data
);

echo json_encode($response);
?>