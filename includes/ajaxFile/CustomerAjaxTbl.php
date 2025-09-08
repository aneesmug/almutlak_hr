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
    $showBtn = ($row['injazat_no'] > 0)?"<a href='javascript:void(0);' class='dropdown-item cardUpdateAttr text-primary' data-id='$row[id]' data-injazat_no='$row[injazat_no]' data-acc_no='$row[acc_no]' ><i class='mdi mdi-account-convert mr-2 font-18 vertical-middle'></i>Update Card</a>" : "";
    $data[] = array(
    		"id"=>$row['id'],
            "injazat_no"=>$row['injazat_no'],
    		"full_name"=>$row['full_name'],
    		"acc_no"=>$row['acc_no'],
    		"status"=> ($row['status'] == "A") ? "<a href='./includes/update_stus_cust.php?status=I&id=$row[id]'><span class='badge-border badge-border-success'>Active</span></a>" : "<a href='./includes/update_stus_cust.php?status=A&id=$row[id]'><span class='badge-border badge-danger'>Suspended</span></a>",
            "created_at"=>str_replace('-', '/', $row['created_at']),
            "exp_date"=>str_replace('-', '/', $row['exp_date']),
            "mobile"=>$row['mobile'],
            
            "action"=> "<div class='btn-group dropdown'>
                            <a href='javascript: void(0);' class='table-action-btn dropdown-toggle arrow-none btn btn-light btn-sm' data-toggle='dropdown' aria-expanded='false'><i class='mdi mdi-dots-horizontal'></i></a>
                            <div class='dropdown-menu dropdown-menu-right' x-placement='bottom-end' >
                                <a class='dropdown-item text-dark' href='./view_customer.php?id=$row[id]'><i class='fa fa-eye mr-2 font-18 vertical-middle'></i>Open</a>
                                <a href='javascript:void(0);' class='dropdown-item text-custom editCustomerAtter' data-id='$row[id]' data-full_name='$row[full_name]' data-mobile='$row[mobile]' data-acc_no='$row[acc_no]' data-location='$row[sectin_nme]'  data-injazat_no='$row[injazat_no]' data-card_exp='$row[exp_date]' ><i class='fa fa-edit mr-2 font-18 vertical-middle'></i>Edit</a>
                                <a href='javascript:void(0);' class='dropdown-item cardPrintAttr text-warning' data-toggle='modal' data-target='#cardPrintModal' data-backdrop='static' data-id='$row[id]' data-full_name='$row[full_name]' data-acc_no='$row[acc_no]' data-created_at='".date('d-m-Y', strtotime(str_replace('-', '/', $row['created_at'])))."' data-exp_date='".$row['exp_date']."'><i class='fa fa-print mr-2 font-18 vertical-middle'></i>Print Card</a>
                                <a href='javascript:voic(0);' class='dropdown-item text-success cardAddAttr' data-id='$row[id]' data-acc_no='$row[acc_no]'><i class='fa fa-file-plus mr-2 font-18 vertical-middle'></i> New Card</a>
                                $showBtn
                                <a href='javascript:void(0);' class='dropdown-item  text-danger deleteAjax' data-id='$row[id]' data-tbl='customer' data-file='0'><i class='fa fa-trash mr-2 font-18 vertical-middle'></i>Delete</a>
                            </div>
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
