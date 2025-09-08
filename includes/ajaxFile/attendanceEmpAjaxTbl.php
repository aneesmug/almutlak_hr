<?php
include './../../includes/db.php';

## Read value
$emp_id = $_POST['emp_id'];
$FromDate = $_POST['fromdate'];
$ToDate = $_POST['todate'];
$draw = $_POST['draw'];
$row = $_POST['start'];
$rowperpage = $_POST['length']; // Rows display per page
$columnIndex = $_POST['order'][0]['column']; // Column index
$columnName = $_POST['columns'][$columnIndex]['data']; // Column name
$columnSortOrder = $_POST['order'][0]['dir']; // asc or desc
$searchValue = mysqli_real_escape_string($conDB,$_POST['search']['value']); // Search value
$dateFromValue = mysqli_real_escape_string($conDB,$_POST['fromdate']['value']); // Search value
$dateToValue = mysqli_real_escape_string($conDB,$_POST['todate']['value']); // Search value

## Search 
/*$searchQuery = " ";
if($searchValue != ''){
	$searchQuery = " and (emp_id like '%".$searchValue."%' or 
        date like '%".$searchValue."%' or
        type like'%".$searchValue."%' ) ";
}*/

## Date Search
$dateSearchQuery = " ";
if($dateFromValue != '' AND $dateToValue != ''){
    $dateSearchQuery = " AND (`date` BETWEEN '".$FromDate."' AND '".$ToDate."') ";
}

## Total number of records without filtering
$totlCount = mysqli_query($conDB,"SELECT COUNT(*) AS `allcount` FROM `attendance` WHERE `emp_id` = '$emp_id' GROUP BY `emp_id`,`date` ");
$totalRecords = mysqli_num_rows($totlCount);

## Total number of records with filtering
$totlCountFilter = mysqli_query($conDB,"SELECT COUNT(*) AS `allcount` FROM `attendance` WHERE `emp_id` = '$emp_id' AND 1 ".$dateSearchQuery."  GROUP BY `emp_id`, `date`");
$totalRecordwithFilter = mysqli_num_rows($totlCountFilter);

$empSlryQry = "SELECT ROUND((`basic`/30/8)*1.5,2) AS `perhour` FROM `salary_emp` WHERE `emp_id` = '$emp_id' ORDER BY `id` DESC LIMIT 1";
$empSlry = mysqli_fetch_assoc(mysqli_query($conDB, $empSlryQry));

$statusout = "<span class='badge-border badge-border-danger'>NO CHECK OUT</span>";

$empQuery = "
SELECT
    `attendance`.*, 
    `employees`.`name`,
     MIN(`attendance`.`time_in`) as `check_in`,
     MAX(`attendance`.`time_out`) as `check_out`
FROM `attendance`
LEFT JOIN `employees` ON `employees`.`emp_id` = `attendance`.`emp_id`
WHERE `attendance`.`emp_id` = '".$emp_id."' ".$dateSearchQuery." 
GROUP BY `attendance`.`emp_id`, `attendance`.`date`
ORDER BY `attendance`.`date` DESC
LIMIT ".$row.",".$rowperpage;
$empRecords = mysqli_query($conDB, $empQuery);
$data = array();

while ($row = mysqli_fetch_assoc($empRecords)) {
    $first = new DateTime($row['check_in']);
    $last = new DateTime($row['check_out']);
    
    $tmin = ($row['check_in']>="08:34:00")?"<b class='badge-border badge-border-danger'>".$row['check_in']."</b>":$row['check_in'];
    $tmout = ($row['check_out']<="14:00:00")?"<b class='badge-border badge-border-danger'>".$row['check_out']."</b>":$row['check_out'];

    $interval = $first->diff($last);
    $data[] = array(
    		"uid"         =>$row['uid'],
    		"emp_id"      =>$row['emp_id'],
    		"emp_name"    =>$row['name'],
            "date"        => date('Y-m-d',strtotime($row['date'])),
    		"check_in"    =>$tmin,
            "check_out"   =>($row['check_in'] == $row['check_out'])?"N/A":$tmout,
            "hours"       =>($row['check_in'] == $row['check_out'] AND date("Y-m-d") != date('Y-m-d',strtotime($row['date'])))?$statusout:$interval->format('%H:%I:%S'),
            "type"        =>($row['type'] == 0?"Password":($row['type'] == 1?"Fingerprint":($row['type'] == 2?"Card":"Card"))),
            "note"        =>$row['note'],
            "action"      => "" //($row['check_out']<="11:00:00" AND date("Y-m-d") != date('Y-m-d',strtotime($row['date'])))?"Add Note":"",
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
