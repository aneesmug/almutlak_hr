<?php
include './includes/db.php';

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
	$searchQuery = " and (full_name like '%".$searchValue."%' or 
        injazat_no like '%".$searchValue."%' or 
        acc_no like '%".$searchValue."%' or
        issue_date like '%".$searchValue."%' or
        mobile like'%".$searchValue."%' ) ";
}

## Total number of records without filtering
$sel = mysqli_query($conDB,"select count(*) as allcount from customer");
$records = mysqli_fetch_assoc($sel);
$totalRecords = $records['allcount'];

## Total number of records with filtering
$sel = mysqli_query($conDB,"select count(*) as allcount from customer WHERE 1 ".$searchQuery);
$records = mysqli_fetch_assoc($sel);
$totalRecordwithFilter = $records['allcount'];

## Fetch records
$empQuery = "select * from customer WHERE 1 ".$searchQuery." order by ".$columnName." ".$columnSortOrder." limit ".$row.",".$rowperpage;
$empRecords = mysqli_query($conDB, $empQuery);
$data = array();

while ($row = mysqli_fetch_assoc($empRecords)) {
    $data[] = array(
    		"id"=>$row['id'],
            "injazat_no"=>$row['injazat_no'],
    		"full_name"=>$row['full_name'],
    		"acc_no"=>$row['acc_no'],
            // "status" => ($row['acc_no'] == "A") ? "A":"I",
    		"status"=> ($row['status'] == "A") ? "<a href='./includes/update_stus_cust.php?status=I&id=$row[id]'><span class='badge badge-success'>Active</span></a>" : "<a href='./includes/update_stus_cust.php?status=A&id=$row[id]'><span class='badge badge-danger'>Suspended</span></a>",
    		// "issue_date"=>$row['issue_date'],
            "issue_date"=>str_replace('-', '/', $row['issue_date']),
            "exp_date"=>str_replace('-', '/', $row['exp_date']),
            "mobile"=>$row['mobile'],
            
            "action"=> "<div class='btn-group' role='group' aria-label='Edit Button'><a href='./view_customer.php?id=$row[id]' class='btn btn-sm btn-dark waves-effect'><i class='mdi mdi-eye-outline'></i></a><a href='./edit_customer.php?id=$row[id]' class='btn btn-sm btn-custom waves-effect'><i class='fa fa-edit'></i></a></div>",

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
