<?php
/*
MODIFICATION SUMMARY (007-smartRequestAjaxTbl.php):
- FIXED: Fatal Error "Unknown column 'ra.your_actual_request_type_column_name' / 'ra.request_type'". Changed the LEFT JOIN condition to use the correct column `ra`.`request_type_id` and compare it against the ID `1` (representing 'smart_request' based on the `approval_request_types` table).
*/
/*
MODIFICATION SUMMARY (006-smartRequestAjaxTbl.php):
- FIXED: Fatal Error "Unknown column 'ra.approver_emp_id'". Replaced `ra`.`approver_emp_id` with the correct column name `ra`.`approver_id` in the `$additionalConditions` logic for GM and Manager roles, based on the provided table schema.
*/
/*
MODIFICATION SUMMARY (005-smartRequestAjaxTbl.php):
- ACTION REQUIRED: Replaced `ra`.`request_type` with a placeholder `ra`.`your_actual_request_type_column_name` in the LEFT JOIN condition. The user MUST replace this placeholder with the actual column name from their `request_approvers` database table that stores the request type (e.g., 'smart_request'). This is necessary to fix the persistent "Unknown column 'ra.request_type'" error.
*/
/*
MODIFICATION SUMMARY (004-smartRequestAjaxTbl.php):
- ADDED: Comments near the `ra`.`request_type` condition in the LEFT JOIN, highlighting the location of the new fatal error ("Unknown column 'ra.request_type'") and advising to verify this column name in the database.
*/
/*
MODIFICATION SUMMARY (003-smartRequestAjaxTbl.php):
- FIXED: Fatal Error "Unknown column 'ra.request_id'". Changed the LEFT JOIN condition from `ra`.`request_id` to `ra`.`request_inv_no`.
- NOTE: If the column in the `request_approvers` table storing the invoice number is named differently, `ra`.`request_inv_no` must be replaced with the actual column name.
*/
/*
MODIFICATION SUMMARY (002-smartRequestAjaxTbl.php):
- ADDED: Comments near the LEFT JOIN to 'request_approvers' highlighting the location of the reported fatal error ("Unknown column 'ra.request_id'") and advising to verify the column name in the database table structure. No functional code changes made as the query syntax appears correct based on the previously defined schema.
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
/*
MODIFICATION SUMMARY (Previous):
- Modified: The role-based filtering logic (`$additionalConditions`) for Department Managers and the Finance Department.
- New Logic for Finance: Users in the Finance department (`$user_dept == 2`) will now see ALL requests from ALL departments, regardless of status, similar to an administrator.
- New Logic for Department Managers: Non-finance managers (`$emptype == 'Manager'`) will now see ALL requests where the request's department matches their own department, allowing them to track all activity within their team.
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
            WHERE 1 {$searchQuery} {$typeSearchQuery}";

// UPDATED: Role-based filtering conditions
$additionalConditions = " AND `sr`.`emp_id` = " . (int)$emp_id; // Default: Employee sees their own

switch (true) {
    // Admin & Finance see everything
    case ($user_type == 'administrator' || $user_dept == 2):
        $additionalConditions = '';
        break;

    // GM sees requests pending their approval
    case ($user_type == 'gm'):
        // Show requests only if they are the current approver AND status is pending
        // FIXED: Use ra.approver_id instead of ra.approver_emp_id
        $additionalConditions = " AND `ra`.`approver_id` = ".(int)$emp_id." AND `sr`.`current_status` = 'pending_approval'";
        break;

    // Department Manager (Non-Finance) sees requests pending their approval OR from their dept
    case ($emptype == 'Manager' && $user_dept != 2):
         // FIXED: Use ra.approver_id instead of ra.approver_emp_id
        $additionalConditions = " AND (
                                    (`ra`.`approver_id` = ".(int)$emp_id." AND `sr`.`current_status` = 'pending_approval')
                                    OR
                                    (`sr`.`department` = " . (int)$user_dept . ")
                                )";
        break;

    // Default case (Employee/Assistant etc.) is handled by the initial $additionalConditions
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
