<?php
include './../../includes/db.php';

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
	$searchQuery = " and (emp_id like '%".$searchValue."%' or 
        inv_no like '%".$searchValue."%' or
        emp_name like '%".$searchValue."%' or
        status like'%".$searchValue."%' ) ";
}

## Total number of records without filtering
$sel = mysqli_query($conDB,"select count(*) as allcount from smt_request_status");
$records = mysqli_fetch_assoc($sel);
$totalRecords = $records['allcount'];

## Total number of records with filtering
$sel = mysqli_query($conDB,"select count(*) as allcount from smt_request_status WHERE 1 ".$searchQuery);
$records = mysqli_fetch_assoc($sel);
$totalRecordwithFilter = $records['allcount'];

## Fetch records
$empQuery = "select * from smt_request_status WHERE 1 ".$searchQuery." order by ".$columnName." ".$columnSortOrder." limit ".$row.",".$rowperpage;
$empRecords = mysqli_query($conDB, $empQuery);
$data = array();

while ($row = mysqli_fetch_assoc($empRecords)) {
    $data[] = array(
    		"id"        =>$row['id'],
            "inv_no"    =>$row['inv_no'],
    		"emp_id"    =>$row['emp_id'],
    		"emp_name"  =>$row['emp_name'],
            "note"      =>$row['note'],
    		"status"    =>($row['status'] == "Paid")? __(strtolower($row['status'])):__($row['status']),
            "action"    => "<div class='btn-group' role='group' aria-label='Edit Button'>
                        <a href='javascript:void(0);' class='btn btn-sm btn-danger waves-effect deleteTblAjax' data-id='$row[id]' data-tbl='smt_request_status' data-file='0'><i class='fa fa-trash'></i></a>
                        </div>",
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
