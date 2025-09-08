<?php
include './includes/db.php';

## Read value
$draw = $_POST['draw'];
$row = $_POST['start'];
$rowperpage = $_POST['length']; // Rows display per page
$columnIndex = $_POST['order'][0]['column']; // Column index
$columnName = $_POST['columns'][$columnIndex]['data']; // Column name
$columnSortOrder = $_POST['order'][0]['dir']; // asc or desc
// $searchValue = mysqli_real_escape_string($conDB,$_POST['search']['value']); // Search value
$searchValue = mysqli_escape_string($conDB,$_POST['search']['value']); // Search value

## Search 
$searchQuery = " ";
if($searchValue != ''){
	$searchQuery = " and (
        full_name like '%".$searchValue."%' or 
        email like '%".$searchValue."%' or 
        mobile like '%".$searchValue."%' or
        location like'%".$searchValue."%' ) or
        gender like'%".$searchValue."%' ) ";
}

## Total number of records without filtering
$sel = mysqli_query($conDB,"select count(*) as allcount from survey");
$records = mysqli_fetch_assoc($sel);
$totalRecords = $records['allcount'];

## Total number of records with filtering
$sel = mysqli_query($conDB,"select count(*) as allcount from survey WHERE 1 ".$searchQuery);
$records = mysqli_fetch_assoc($sel);
$totalRecordwithFilter = $records['allcount'];

## Fetch records

$empQuery = "select * from survey WHERE 1 ".$searchQuery." order by ".$columnName." ".$columnSortOrder." limit ".$row.",".$rowperpage;
$empRecords = mysqli_query($conDB, $empQuery);
$data = array();

while ($row = mysqli_fetch_assoc($empRecords)) {
    $data[] = array(
    		"id"=>$row['id'],
    		"full_name"=>$row['full_name'],
    		"email"=>$row['email'],
            "gender"=>$row['gender'],
            "mobile"=>$row['mobile'],
            "location"=>$row['location'],
            
            "action"=> "<div class='btn-group' role='group' aria-label='Edit Button'><a href='./view_customer_survey.php?id=$row[id]' class='btn btn-sm btn-dark waves-effect'><i class='mdi mdi-eye-outline'></i></a></div>",

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
// echo json_encode($response);
