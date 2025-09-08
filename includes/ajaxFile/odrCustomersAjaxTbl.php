<?php
include './../../includes/db.php';
session_start();

## Read value
$draw = $_POST['draw'];
$row = $_POST['start'];
$rowperpage = $_POST['length']; // Rows display per page
$columnIndex = $_POST['order'][0]['column']; // Column index
$columnName = $_POST['columns'][$columnIndex]['data']; // Column name
$columnSortOrder = $_POST['order'][0]['dir']; // asc or desc
$searchValue = mysqli_real_escape_string($conDB,$_POST['search']['value']); // Search value

## Search 
$searchQuery = " ";
if($searchValue != ''){
	$searchQuery = " and (fullname like '%".$searchValue."%' or 
        email like '%".$searchValue."%' or
        username like '%".$searchValue."%' or
        mobile like'%".$searchValue."%' ) ";
}

## Total number of records without filtering
$sel = mysqli_query($conDB,"select count(*) as allcount from customer_access");
$records = mysqli_fetch_assoc($sel);
$totalRecords = $records['allcount'];

## Total number of records with filtering
$sel = mysqli_query($conDB,"select count(*) as allcount from customer_access WHERE 1 ".$searchQuery);
$records = mysqli_fetch_assoc($sel);
$totalRecordwithFilter = $records['allcount'];

## Fetch records
$empQuery = "select * from customer_access WHERE 1 ".$searchQuery." order by ".$columnName." ".$columnSortOrder." limit ".$row.",".$rowperpage;
$empRecords = mysqli_query($conDB, $empQuery);
$data = array();

// $statusaction = ;

while ($row = mysqli_fetch_assoc($empRecords)) {
    $data[] = array(
    		"id"        =>$row['id'],
    		"fullname"  =>$row['fullname'],
            "email"     =>$row['email'],
    		"username"  =>$row['username'],
    		"mobile"    =>$row['mobile'],
            "status"    =>($row['status'] == 1)?"<span class='badge-border badge-border-success'>Verified</span>" : "<span class='badge-border badge-border-danger'>Not Verified</span>",
            "created_at"=>$row['created_at'],
            "action"    =>($_SESSION['user_type'] == "administrator")?"<div class='btn-group' role='group' aria-label='Edit Button'><a href='javascript:void(0);' class='btn btn-sm btn-danger waves-effect deleteAjax' data-id='$row[id]' data-tbl='customer_access' data-file='0'><i class='fa fa-trash'></i></a></div>":"",
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