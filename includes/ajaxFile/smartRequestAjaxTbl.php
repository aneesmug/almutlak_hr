<?php
/*
MODIFICATION SUMMARY:
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
$typeValue = mysqli_real_escape_string($conDB,$_POST['smtStatus']);

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

$typeSearchQuery = " ";
if($typeValue != ''){
    $typeSearchQuery = " AND `sr`.`current_status` = '".$typeValue."' ";
}

// Base SQL structure
$baseSql = "FROM `smart_request` `sr`
            LEFT JOIN `department` `dept` ON `dept`.`id` = `sr`.`department`
            WHERE 1 {$searchQuery} {$typeSearchQuery}";

// Default conditions (for regular employees)
$additionalConditions = " AND `sr`.`emp_id` = " . (int)$emp_id;

// Override conditions based on user type and role for their specific queues
switch (true) {
    case ($user_type == 'administrator'):
        $additionalConditions = ''; // Admin sees all requests
        break;
    
    case ($user_dept == 2): // Finance Department sees all requests
        $additionalConditions = ''; 
        break;

    case ($user_type == 'gm'):
        $additionalConditions = " AND (`sr`.`current_status` = 'pending_gm_approval' AND `sr`.`gm_id` = ".(int)$emp_id.")";
        break;

    case ($emptype == 'Manager'): // Department Managers see all requests from their department
        $additionalConditions = " AND `sr`.`department` = " . (int)$user_dept;
        break;
}

// Build the final query for data fetching
$sql = "SELECT 
            `sr`.`id`,
            `sr`.`inv_no`,
            `sr`.`sub_title`,
            `sr`.`sub_type`,
            `dept`.`dep_nme` AS `department`,
            `dept`.`dep_nme_ar` AS `department_ar`,
            `sr`.`prep_by`,
            `sr`.`created_at`,
            `sr`.`current_status` AS `status`
        " . $baseSql . $additionalConditions . "
        GROUP BY `sr`.`inv_no`
        ORDER BY `sr`.`id` DESC
        LIMIT " . (int)$start . ", " . (int)$length;

$query = mysqli_query($conDB, $sql);

// Fetch data and format as JSON
$data = array();
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
            "action"          =>($user_type == 'administrator')?"<div class='btn-group dropdown'>
                            <a href='javascript: void(0);' class='table-action-btn dropdown-toggle arrow-none btn btn-light btn-sm' data-toggle='dropdown' aria-expanded='false'><i class='mdi mdi-dots-horizontal'></i></a>
                            <div class='dropdown-menu dropdown-menu-right' x-placement='bottom-end' >
                                <a href='open_request.php?id=$row[inv_no]' class='dropdown-item text-dark' ><i class='mdi mdi-eye-outline'></i></i> ". __('open') ."</a>
                                <a href='javascript:void(0);' class='dropdown-item  text-danger deleteSmt' data-id='$row[inv_no]' ><i class='fa fa-trash mr-2 font-18 vertical-middle'></i>". __('delete') ."</a>
                            </div>
                            </div>":"<a href='open_request.php?id=$row[inv_no]' class='btn btn-dark btn-sm' ><i class='mdi mdi-eye-outline'></i></i> Open</a>",
        );
}

// Count queries
$sqlTotal = "SELECT COUNT(DISTINCT `sr`.`inv_no`) as allcount FROM `smart_request` `sr` WHERE 1";
$sqlFiltered = "SELECT COUNT(DISTINCT `sr`.`inv_no`) as allcount " . $baseSql . $additionalConditions;

$totalRecords = (int)mysqli_fetch_assoc(mysqli_query($conDB, $sqlTotal))['allcount'];
$filteredRecords = (int)mysqli_fetch_assoc(mysqli_query($conDB, $sqlFiltered))['allcount'];

// Prepare JSON response
$response = array(
    "draw" => intval($draw),
    "recordsTotal" => intval($totalRecords),
    "recordsFiltered" => intval($filteredRecords),
    "data" => $data
);

echo json_encode($response);
?>
