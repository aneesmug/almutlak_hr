<?php
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

$searchValue = mysqli_real_escape_string($conDB, $_POST['search']);
$statusValue = mysqli_real_escape_string($conDB, $_POST['status']);

## Search
$searchQuery = " ";
if($searchValue != ''){
    $searchQuery = " AND (
        `gr`.`inv_no` LIKE '%".$searchValue."%'
        OR `gr`.`request_title` LIKE '%".$searchValue."%'
        OR `gr`.`department_to` LIKE '%".$searchValue."%'
        OR `gr`.`request_category` LIKE '%".$searchValue."%'
        OR `gr`.`emp_name` LIKE '%".$searchValue."%'
    )";
}

// Status filter
$statusSearchQuery = " ";
if($statusValue != ''){
    $statusSearchQuery = " AND `gr`.`current_status` = '".$statusValue."' ";
}

// Get request type ID for general requests
$request_type_id = 1; // Default fallback
$type_query = mysqli_query($conDB, "SELECT id FROM approval_request_types WHERE main_table_name = 'general_requests' LIMIT 1");
if ($type_query && mysqli_num_rows($type_query) > 0) {
    $type_row = mysqli_fetch_assoc($type_query);
    $request_type_id = $type_row['id'];
}

// Base SQL with joins for approval chain
$baseSql = "FROM `general_requests` `gr`
            LEFT JOIN `department` `dept` ON `dept`.`id` = `gr`.`user_dept`
            LEFT JOIN `request_approvers` `ra` ON `gr`.`inv_no` = `ra`.`request_inv_no`
                AND `ra`.`request_type_id` = ".(int)$request_type_id."
                AND `gr`.`current_approval_level` = `ra`.`approval_level`
            LEFT JOIN `request_approvers` `ra_any` ON `gr`.`inv_no` = `ra_any`.`request_inv_no`
                AND `ra_any`.`request_type_id` = ".(int)$request_type_id."
            LEFT JOIN `request_approvers` `ra_fin` ON `gr`.`inv_no` = `ra_fin`.`request_inv_no`
                AND `ra_fin`.`request_type_id` = ".(int)$request_type_id."
                AND `ra_fin`.`approver_id` = ".(int)$emp_id."
            WHERE 1 {$searchQuery} {$statusSearchQuery}";

// Role-based filtering - similar to smart_request
$additionalConditions = "";

// 1. Administrators see everything
if ($user_type == 'administrator') {
    $additionalConditions = '';
}
// 2. Management (dept 10) see everything
elseif ($user_dept == 10) {
    $additionalConditions = '';
}
// 3. Department Managers see requests from their department, pending their approval, or created by them
elseif ($emptype == 'Manager') {
    $additionalConditions = " AND (
                                (`ra`.`approver_id` = ".(int)$emp_id." AND `gr`.`current_status` = 'pending_approval')
                                OR (`gr`.`user_dept` = " . (int)$user_dept . ")
                                OR (`ra_any`.`approver_id` = ".(int)$emp_id.")
                                OR (`gr`.`emp_id` = " . (int)$emp_id . ")
                            )";
}
// 4. Regular users see only their own requests or requests they are assigned to approve
else {
    $additionalConditions = " AND (
                                `gr`.`emp_id` = " . (int)$emp_id . "
                                OR `ra_any`.`approver_id` = ".(int)$emp_id."
                            )";
}

// Build the final query for data fetching
$sql = "SELECT
            `gr`.`id`,
            `gr`.`inv_no`,
            `gr`.`request_title`,
            `gr`.`department_to`,
            `gr`.`request_category`,
            `gr`.`priority`,
            `gr`.`emp_name`,
            `gr`.`created_at`,
            `gr`.`current_status`,
            `gr`.`current_approval_level`,
            `ra_fin`.`approval_level` AS `user_approval_level`,
            CASE WHEN `gr`.`current_approval_level` = `ra_fin`.`approval_level` THEN 1 ELSE 0 END AS `is_current_approver`
        " . $baseSql . $additionalConditions . "
        GROUP BY `gr`.`inv_no`
        ORDER BY `gr`.`id` DESC
        LIMIT " . (int)$start . ", " . (int)$length;

$query = mysqli_query($conDB, $sql);

// Count total records without filter
$totalRecordsSql = "SELECT COUNT(DISTINCT `gr`.`id`) as count " . $baseSql . $additionalConditions;
$totalRecordsQuery = mysqli_query($conDB, $totalRecordsSql);
$totalRecordsRow = mysqli_fetch_assoc($totalRecordsQuery);
$totalRecords = $totalRecordsRow['count'];

// Count filtered records
$filteredRecordsSql = "SELECT COUNT(DISTINCT `gr`.`id`) as count " . $baseSql . $additionalConditions;
$filteredRecordsQuery = mysqli_query($conDB, $filteredRecordsSql);
$filteredRecordsRow = mysqli_fetch_assoc($filteredRecordsQuery);
$recordsFiltered = $filteredRecordsRow['count'];

// Fetch data and format as JSON
$data = array();
if ($query) {
    while ($row = mysqli_fetch_assoc($query)) {
        // Build action buttons
        $actionButtons = "<div class='btn-group dropdown'>
                            <a href='javascript: void(0);' class='table-action-btn dropdown-toggle arrow-none btn btn-light btn-sm' data-toggle='dropdown' aria-expanded='false'><i class='mdi mdi-dots-horizontal'></i></a>
                            <div class='dropdown-menu dropdown-menu-right' x-placement='bottom-end'>
                                <a href='view_general_request.php?id=".$row['inv_no']."' class='dropdown-item text-dark'><i class='mdi mdi-eye-outline'></i> ".__('view')."</a>";
        
        // Allow delete only for draft status and creator
        if ($row['current_status'] == 'draft' && $row['emp_id'] == $emp_id) {
            $actionButtons .= "<a href='javascript:void(0);' class='dropdown-item text-danger deleteRequest' data-id='".$row['inv_no']."'><i class='fa fa-trash mr-2 font-18 vertical-middle'></i>".__('delete')."</a>";
        }
        
        // Administrator can delete any request
        if ($user_type == 'administrator') {
            $actionButtons .= "<a href='javascript:void(0);' class='dropdown-item text-danger deleteRequest' data-id='".$row['inv_no']."'><i class='fa fa-trash mr-2 font-18 vertical-middle'></i>".__('delete')."</a>";
        }
        
        $actionButtons .= "</div></div>";
        
        $data[] = array(
            "id"                     => $row['id'],
            "inv_no"                 => $row['inv_no'],
            "request_title"          => $row['request_title'],
            "department_to"          => $row['department_to'],
            "request_category"       => $row['request_category'],
            "priority"               => $row['priority'],
            "emp_name"               => $row['emp_name'],
            "created_at"             => date("Y-m-d", strtotime($row["created_at"])),
            "current_status"         => $row["current_status"],
            "current_approval_level" => $row["current_approval_level"],
            "user_approval_level"    => $row['user_approval_level'] ?? null,
            "is_current_approver"    => (int)($row['is_current_approver'] ?? 0),
            "action"                 => $actionButtons
        );
    }
}

## Response
$response = array(
    "draw"            => intval($draw),
    "recordsTotal"    => $totalRecords,
    "recordsFiltered" => $recordsFiltered,
    "data"            => $data
);

echo json_encode($response);
?>
