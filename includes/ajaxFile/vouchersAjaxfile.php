<?php
include './../../includes/db.php';
include './../../includes/init.php';
include './../../includes/custom_functions.php';

## Read value
$draw = $_POST['draw'];
$row = $_POST['start'];
$rowperpage = $_POST['length']; // Rows display per page
$columnIndex = $_POST['order'][0]['column']; // Column index
$columnName = $_POST['columns'][$columnIndex]['data']; // Column name
$columnSortOrder = $_POST['order'][0]['dir']; // asc or desc
// $searchValue = mysqli_real_escape_string($conDB,$_POST['search']['value']); // Search value
// $typeValue = mysqli_real_escape_string($conDB,$_POST['payment_type']['value']); // Search value

$searchValue = mysqli_real_escape_string($conDB,$_POST['search']); // Search value
$typeValue = mysqli_real_escape_string($conDB,$_POST['payment_type']); // Search value

## Search 
$searchQuery = " ";
if($searchValue != ''){
	$searchQuery = " AND (
        `employees`.`name` LIKE '%".$searchValue."%' 
        OR `vouchers`.`details` LIKE '%".$searchValue."%' 
        OR `vouchers`.`voucher_no` LIKE '%".$searchValue."%' 
        OR `vouchers`.`voucher_amount` LIKE'%".$searchValue."%'
    )";
}

$typeSearchQuery = " ";
if($typeValue != ''){
    $typeSearchQuery = " AND `vouchers`.`voucher_type` = '".$_POST['payment_type']."' ";
}

if ($_POST['user_type'] == 'administrator') {
    $trCount = mysqli_query($conDB,"SELECT COUNT(*) AS `allcount` FROM `vouchers` ");
    $trfCount = mysqli_query($conDB,"SELECT COUNT(*) AS `allcount` FROM `vouchers` LEFT JOIN `employees` ON `employees`.`emp_id` = `vouchers`.`to_emp` WHERE 1 ".$searchQuery." ".$typeSearchQuery);
    $empQuery = "
    SELECT `vouchers`.*, 
       `employee_to`.`name` AS `name`, 
       `employee_from`.`name` AS `emp_from`
    FROM `vouchers` 
    LEFT JOIN `employees` AS `employee_to` ON `employee_to`.`emp_id` = `vouchers`.`to_emp`
    LEFT JOIN `employees` AS `employee_from` ON `employee_from`.`emp_id` = `vouchers`.`emp_id`
    WHERE 1 ".$searchQuery." ".$typeSearchQuery." ORDER BY ".$columnName." ".$columnSortOrder." LIMIT ".$row.",".$rowperpage;
} else {
    $trCount = mysqli_query($conDB,"SELECT COUNT(*) AS `allcount` FROM `vouchers` WHERE `dept` = '".$_POST['user_dept']."'");
    $trfCount = mysqli_query($conDB,"SELECT COUNT(*) AS `allcount` FROM `vouchers` LEFT JOIN `employees` ON `employees`.`emp_id` = `vouchers`.`to_emp` WHERE 1 AND `vouchers`.`dept` = '".$_POST['user_dept']."' ".$searchQuery." ".$typeSearchQuery);
    $empQuery = "
    SELECT `vouchers`.*, 
       `employee_to`.`name` AS `name`, 
       `employee_from`.`name` AS `emp_from`
    FROM `vouchers` 
    LEFT JOIN `employees` AS `employee_to` ON `employee_to`.`emp_id` = `vouchers`.`to_emp`
    LEFT JOIN `employees` AS `employee_from` ON `employee_from`.`emp_id` = `vouchers`.`emp_id`
    WHERE 1 AND `vouchers`.`dept` = '".$_POST['user_dept']."' ".$searchQuery." ".$typeSearchQuery." ORDER BY ".$columnName." ".$columnSortOrder." LIMIT ".$row.",".$rowperpage;
}

## Total number of records without filtering
$tRecords = mysqli_fetch_assoc($trCount);
$totalRecords = $tRecords['allcount'];
## Total number of records with filtering
$trfRecords = mysqli_fetch_assoc($trfCount);
$totalRecordwithFilter = $trfRecords['allcount'];
## Records fetch
$voucherRecords = mysqli_query($conDB, $empQuery);
$data = array();

while ($row = mysqli_fetch_assoc($voucherRecords)) {

    $attach = ($row['file'])?"<a href=\"javascript:displayPopup('./assets/voucher_documents/"."$row[file]')\" class='dropdown-item text-primary' ><i class='fa fa-paperclip'></i></i> View File</a>":"";

    $isattach = ($row['file'])?"<div class='btn-group dropdown'>
                            <a href='javascript: void(0);' class='table-action-btn dropdown-toggle arrow-none btn btn-light btn-sm' data-toggle='dropdown' aria-expanded='false'><i class='mdi mdi-dots-horizontal'></i></a>
                            <div class='dropdown-menu dropdown-menu-right' x-placement='bottom-end' >
                                <a href='voucher_print.php?id=$row[id]' target='_blank' class='dropdown-item text-dark' ><i class='mdi mdi-printer'></i></i> ".__('print')."</a>
                                $attach
                            </div>
                            </div>":"
                            <a href='voucher_print.php?id=$row[id]' target='_blank' class='btn btn-sm btn-dark' ><i class='mdi mdi-printer'></i></i> ".__('print')."</a>
                            ";

    $data[] = array(
    		"id"              =>$row['id'],
    		"voucher_no"      =>$row['voucher_no'],
            "emp_from"        =>parseName($row["emp_from"]),
            "name"            =>parseName($row["name"]),
            "voucher_type"    =>$row['voucher_type'],
    		"voucher_amount"  =>$row['voucher_amount'],
            "details"         =>$row["details"],
            "created_at"      =>date("Y-m-d",strtotime($row["created_at"])),
            "action"          =>($_POST['user_type'] == 'administrator')?"<div class='btn-group dropdown'>
                            <a href='javascript: void(0);' class='table-action-btn dropdown-toggle arrow-none btn btn-light btn-sm' data-toggle='dropdown' aria-expanded='false'><i class='mdi mdi-dots-horizontal'></i></a>
                            <div class='dropdown-menu dropdown-menu-right' x-placement='bottom-end' >
                                <a href='voucher_print.php?id=$row[id]' target='_blank' class='dropdown-item text-dark' ><i class='mdi mdi-printer'></i></i> ".__('print')."</a>
                                $attach
                                <a href='javascript:void(0);' class='dropdown-item text-danger deleteAjax' data-id='$row[id]' data-tbl='vouchers' data-file='1' data-column='file' ><i class='fa fa-trash mr-2 font-18 vertical-middle'></i>".__('delete')."</a>
                            </div>
                            </div>":$isattach,
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
