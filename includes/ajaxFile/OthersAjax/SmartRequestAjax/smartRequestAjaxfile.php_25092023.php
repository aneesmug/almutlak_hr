<?php
include './../../includes/db.php';

## Read value
$draw = $_POST['draw'];
$row = $_POST['start'];
$rowperpage = $_POST['length']; // Rows display per page
$columnIndex = $_POST['order'][0]['column']; // Column index
$columnName = $_POST['columns'][$columnIndex]['data']; // Column name
$columnSortOrder = $_POST['order'][0]['dir']; // asc or desc
// $searchValue = mysqli_real_escape_string($conDB,$_POST['search']['value']); // Search value
// $typeValue = mysqli_real_escape_string($conDB,$_POST['payment_type']['value']); // Search value

// $user_type = $_POST['user_type'];
$user_type = $_POST['user_type'];
$user_dept = $_POST['user_dept'];
$emptype = $_POST['emptype'];

$searchValue = mysqli_real_escape_string($conDB,$_POST['search']); // Search value
$typeValue = mysqli_real_escape_string($conDB,$_POST['smtStatus']); // Search value


## Search 
$searchQuery = " ";
if($searchValue != ''){
	$searchQuery = " AND (
        `smart_request`.`inv_no` LIKE '%".$searchValue."%' 
        OR `smart_request`.`sub_title` LIKE '%".$searchValue."%' 
        OR `smart_request`.`sub_type` LIKE '%".$searchValue."%' 
        OR `smart_request`.`department` LIKE '%".$searchValue."%' 
        OR `smart_request`.`prep_by` LIKE'%".$searchValue."%'
    )";
}

$typeSearchQuery = " ";
if($typeValue != ''){
    $typeSearchQuery = " AND `smt_request_status`.`status` = '".$typeValue."' ";
}

if ($user_type == 'administrator') {
    $trCount = mysqli_query($conDB,"
        SELECT COUNT(*) AS `allcount` 
        FROM `smart_request`
        LEFT JOIN `smt_request_status` ON `smart_request`.`inv_no` = `smt_request_status`.`inv_no`
        GROUP BY `smart_request`.`inv_no`
    ");
    $trfCount = mysqli_query($conDB,"
        SELECT COUNT(*) AS `allcount` 
        FROM `smart_request`
        LEFT JOIN `smt_request_status` ON `smart_request`.`inv_no` = `smt_request_status`.`inv_no`
        WHERE 1 
        AND `smt_request_status`.`status` = (
            SELECT `smt_request_status`.`status`
                FROM `smt_request_status`
                WHERE `smart_request`.`inv_no` = `smt_request_status`.`inv_no`
                ORDER BY `smt_request_status`.`id` DESC
            LIMIT 1 ) 
        ".$searchQuery." ".$typeSearchQuery." 
        GROUP BY `smart_request`.`inv_no` 
    ");

    // $empQuery = "SELECT `vouchers`.*, `employees`.`name` FROM `vouchers` LEFT JOIN `employees` ON `employees`.`emp_id` = `vouchers`.`to_emp` WHERE 1 ".$searchQuery." ".$typeSearchQuery." ORDER BY ".$columnName." ".$columnSortOrder." LIMIT ".$row.",".$rowperpage;
    $empQuery = "
        SELECT * , `smart_request`.`inv_no`
        FROM `smart_request`
        LEFT JOIN `smt_request_status` ON `smart_request`.`inv_no` = `smt_request_status`.`inv_no`
        WHERE 1 
        AND `smt_request_status`.`status` = (
            SELECT `smt_request_status`.`status`
            FROM `smt_request_status`
            WHERE `smart_request`.`inv_no` = `smt_request_status`.`inv_no`
            ORDER BY `smt_request_status`.`id` DESC
            LIMIT 1 ) 
        ".$searchQuery." ".$typeSearchQuery."
        GROUP BY `smart_request`.`inv_no`
        LIMIT ".$row.",".$rowperpage."
        ";
}/* else {
    $trCount = mysqli_query($conDB,"SELECT COUNT(*) AS `allcount` FROM `vouchers` WHERE `dept` = '".$_POST['user_dept']."'");
    $trfCount = mysqli_query($conDB,"SELECT COUNT(*) AS `allcount` FROM `vouchers` LEFT JOIN `employees` ON `employees`.`emp_id` = `vouchers`.`to_emp` WHERE 1 AND `dept` = '".$_POST['user_dept']."' ".$searchQuery." ".$typeSearchQuery);
    $empQuery = "SELECT `vouchers`.*, `employees`.`name` FROM `vouchers` LEFT JOIN `employees` ON `employees`.`emp_id` = `vouchers`.`to_emp` WHERE 1 AND `vouchers`.`dept` = '".$_POST['user_dept']."' ".$searchQuery." ".$typeSearchQuery." ORDER BY ".$columnName." ".$columnSortOrder." LIMIT ".$row.",".$rowperpage;
}*/

## Total number of records without filtering
$totalRecords = mysqli_num_rows($trCount);
## Total number of records with filtering
$totalRecordwithFilter = mysqli_num_rows($trfCount);
## Records fetch
$voucherRecords = mysqli_query($conDB, $empQuery);
$data = array();
if ($status == "draft") {
                $status_get = "<a href='javascript:void(0)' class='btn btn-secondary waves-effect'>Not Submited</a>";
            } elseif ($status == "Manager") {
                $status_get = "<a href='javascript:void(0)' class='btn btn-custom waves-effect'>Waiting from ".$department_get." Manager</a>";
            } elseif ($status == "Finance") {
                $status_get = "<a href='javascript:void(0)' class='btn btn-warning waves-effect'>Waiting from Finance</a>";
            } elseif ($status == "Management") {
                $status_get = "<a href='./open_request.php?id=$inv_no_get' class='btn btn-primary waves-effect'>Waiting from Managment</a>";
            } elseif ($status == "approve") {
                $status_get = "<a href='javascript:void(0)' class='btn btn-success waves-effect'>Approved</a>";
            } elseif ($status == "reject" AND $row['emp_id'] == 2) {
                $status_get = "<a href='javascript:void(0)' class='btn btn-danger waves-effect'>Rejected from Managment</a>";
            } elseif ($status == "Paid") {
                $status_get = "<a href='javascript:void(0)' class='btn btn-purple waves-effect'>Paid</a>";
            } else {
                $status_get = "<a href='javascript:void(0)' class='btn btn-danger waves-effect'>Rejected from Finance</a>";
            }
while ($row = mysqli_fetch_assoc($voucherRecords)) {

$status = 
($row["status"]=="draft"?"<span class='badge badge-secondary'>Not Submited</span>":
    ($row["status"]=="Manager" ?"<span class='badge badge-custom'>Waiting from ".$row["department"]." Manager</span>":
        ($row["status"]=="Finance" ?"<span class='badge badge-warning'>Waiting from Finance</span>":
            ($row["status"]=="Management" ?"<span class='badge badge-primary'>Waiting from Managment</span>":
                ($row["status"]=="approve" ?"<span class='badge badge-success'>Approved</span>":
                    ($row["status"]=="reject" ?"<span class='badge badge-danger'>Rejected from Managment</span>":
                        ($row["status"]=="Paid" ?"<span class='badge badge-purple'>Paid</span>":
                            "<span class='badge badge-danger'>Rejected from Finance</span>"
                        )
                    )
                )
            )
        )
    )
);

    $data[] = array(
    		"id"              =>$row['id'],
    		"inv_no"          =>$row['inv_no'],
            "sub_title"       =>$row['sub_title'],
    		"sub_type"        =>$row['sub_type'],
            "department"      =>$row["department"],
            "prep_by"         =>$row["prep_by"],
            "created_at"      =>date("Y-m-d",strtotime($row["created_at"])),
            "status"          =>$status,
            "action"          =>($user_type == 'administrator')?"<div class='btn-group dropdown'>
                            <a href='javascript: void(0);' class='table-action-btn dropdown-toggle arrow-none btn btn-light btn-sm' data-toggle='dropdown' aria-expanded='false'><i class='mdi mdi-dots-horizontal'></i></a>
                            <div class='dropdown-menu dropdown-menu-right' x-placement='bottom-end' >
                                <a href='open_request.php?id=$row[inv_no]' class='dropdown-item text-dark' ><i class='mdi mdi-eye-outline'></i></i> Open</a>
                                <a href='javascript:void(0);' class='dropdown-item  text-danger deleteSmt' data-id='$row[inv_no]' ><i class='fa fa-trash mr-2 font-18 vertical-middle'></i>Delete</a>
                            </div>
                            </div>":"<a href='open_request.php?id=$row[inv_no]' class='btn btn-dark btn-sm' ><i class='mdi mdi-eye-outline'></i></i> Open</a>",
    	);
}

## Response
$response = array(
    "draw" => intval($draw),
    "iTotalRecords" => $totalRecords,
    "iTotalDisplayRecords" => $totalRecordwithFilter,
    "aaData" => $data
);

echo json_encode($response);
