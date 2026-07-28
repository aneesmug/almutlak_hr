<?php


include './../../includes/session_check.php';
require_once __DIR__ . '/../special_access_helper.php';

// Real, session-derived permission for the delete/cancel action button - captured BEFORE
// $user_type below is overwritten with the client-supplied POST value used for row filtering.
$canCancelSmartRequests = (
    strtolower(trim((string)($user_type ?? ''))) === 'administrator'
    || user_has_special_access($conDB, $empid ?? '', 'cancel_smart_requests', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)
);

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
// NOTE: Added ra_fin join specifically for the logged-in user's position in chain (to derive upcoming level and current flag)
$baseSql = "FROM `smart_request` `sr`
            LEFT JOIN `department` `dept` ON `dept`.`id` = `sr`.`department`
            LEFT JOIN `request_approvers` `ra` ON `sr`.`inv_no` = `ra`.`request_inv_no`
                AND `ra`.`request_type_id` = 1
                AND `sr`.`current_approval_level` = `ra`.`approval_level`
            LEFT JOIN `request_approvers` `ra_any` ON `sr`.`inv_no` = `ra_any`.`request_inv_no`
                AND `ra_any`.`request_type_id` = 1
            LEFT JOIN `request_approvers` `ra_fin` ON `sr`.`inv_no` = `ra_fin`.`request_inv_no`
                AND `ra_fin`.`request_type_id` = 1
                AND `ra_fin`.`approver_id` = ".(int)$emp_id." -- User-specific chain position
            WHERE 1 {$searchQuery} {$typeSearchQuery}";

// UPDATED: Role-based filtering conditions
$additionalConditions = ""; // Start empty

// Role-based visibility rules
// 1. Payer focused view
if ($payerOnly === 1) {
    $additionalConditions = " AND `sr`.`current_status` = 'pending_payment' AND `sr`.`payable_by_emp_id` = ".(int)$emp_id;
}
// 2. Administrators and Management (dept 10) see everything
elseif ($user_type == 'administrator' || $user_dept == 10) {
    $additionalConditions = '';
}
// 3. Finance Manager (dept 2 + Manager) – when viewing pending_approval, show ONLY requests awaiting THEIR approval
elseif ($user_dept == 2 && $emptype == 'Manager') {
    if ($typeValue === 'pending_approval') {
        // Show requests currently pending their action OR upcoming (their level > current level)
        $additionalConditions = " AND `sr`.`current_status` = 'pending_approval' AND (
                                    `ra`.`approver_id` = ".(int)$emp_id." -- Current approver
                                    OR (
                                        `ra_fin`.`approver_id` = ".(int)$emp_id." AND `ra_fin`.`approval_level` > `sr`.`current_approval_level`
                                    ) -- Upcoming approvals for Finance Manager
                                 )";
    } else {
        $additionalConditions = '';
    }
}
// 4. Other Finance users (dept 2 non-managers) – full visibility (needed for payment processing, etc.)
elseif ($user_dept == 2) {
    $additionalConditions = '';
}
// 5. GM – only requests pending their approval
elseif ($user_type == 'gm') {
    $additionalConditions = " AND `ra`.`approver_id` = ".(int)$emp_id." AND `sr`.`current_status` = 'pending_approval'";
}
// 6. Department Managers (non Finance / non Management) – mixed visibility
elseif ($emptype == 'Manager') {
    $additionalConditions = " AND (
                                    (`ra`.`approver_id` = ".(int)$emp_id." AND `sr`.`current_status` = 'pending_approval')
                                    OR (`sr`.`department` = " . (int)$user_dept . ")
                                    OR (`ra_any`.`approver_id` = ".(int)$emp_id.")
                                    OR (`sr`.`emp_id` = " . (int)$emp_id . ")
                                )";
}
// 7. Default (employees, assistants, etc.) – created by them or in their chain
else {
    $additionalConditions = " AND (
                                    `sr`.`emp_id` = " . (int)$emp_id . "
                                    OR `ra_any`.`approver_id` = ".(int)$emp_id." 
                                )";
}


// Build the final query for data fetching - ADDED sr.current_approval_level
$sql = "SELECT
            `sr`.`id`,
            `sr`.`inv_no`,
            `sr`.`emp_id`,
            `sr`.`sub_title`,
            `sr`.`sub_type`,
            `dept`.`dep_nme` AS `department`,
            `dept`.`dep_nme_ar` AS `department_ar`,
            `sr`.`prep_by`,
            `sr`.`created_at`,
            `sr`.`current_status` AS `status`,
            `sr`.`current_approval_level`,
            `ra_fin`.`approval_level` AS `user_approval_level`,
            CASE WHEN `sr`.`current_approval_level` = `ra_fin`.`approval_level` THEN 1 ELSE 0 END AS `is_current_approver`
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
                "current_approval_level" => $row["current_approval_level"],
                "user_approval_level"    => $row['user_approval_level'] ?? null,
                "is_current_approver"    => (int)($row['is_current_approver'] ?? 0),
                "action"          =>(function() use ($canCancelSmartRequests, $row, $emp_id) {
                                $canSelfCancel = in_array($row['status'], ['draft', 'pending_approval', 'approved'], true)
                                    && (int)$row['emp_id'] === (int)$emp_id;
                                if (!$canCancelSmartRequests && !$canSelfCancel) {
                                    return "<a href='open_request.php?id=$row[inv_no]' class='btn btn-dark btn-sm' ><i class='mdi mdi-eye-outline'></i></i> Open</a>";
                                }
                                $html = "<div class='btn-group dropdown'>
                                <a href='javascript: void(0);' class='table-action-btn dropdown-toggle arrow-none btn btn-light btn-sm' data-toggle='dropdown' aria-expanded='false'><i class='mdi mdi-dots-horizontal'></i></a>
                                <div class='dropdown-menu dropdown-menu-right' x-placement='bottom-end' >
                                    <a href='open_request.php?id=$row[inv_no]' class='dropdown-item text-dark' ><i class='mdi mdi-eye-outline'></i></i> ". __('open') ."</a>";
                                if ($canCancelSmartRequests) {
                                    $html .= "<a href='javascript:void(0);' class='dropdown-item  text-danger deleteSmt' data-id='$row[inv_no]' ><i class='fa fa-trash mr-2 font-18 vertical-middle'></i>". __('cancel', 'Cancel') ."</a>";
                                } elseif ($canSelfCancel) {
                                    $html .= "<a href='javascript:void(0);' class='dropdown-item  text-danger cancelSmartRequestSelf' data-id='$row[inv_no]' ><i class='fa fa-ban mr-2 font-18 vertical-middle'></i>". __('cancel_request', 'Cancel Request') ."</a>";
                                }
                                $html .= "</div></div>";
                                return $html;
                            })(),
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