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
$searchQuery = " ";
if($searchValue != ''){
	$searchQuery = " and (emp_id like '%".$searchValue."%' or 
        date like '%".$searchValue."%' or
        punch_state like'%".$searchValue."%' ) ";
}
## Date Search
$dateSearchQuery = " ";
if($dateFromValue != '' AND $dateToValue != ''){
    $dateSearchQuery = " AND (`date` BETWEEN '".$FromDate."' AND '".$ToDate."') ";
}

## Total number of records without filtering
$totlCount = mysqli_query($conDB,"SELECT COUNT(*) AS `allcount` FROM `attendance` WHERE `emp_id` = '$emp_id' GROUP BY `emp_id`, DATE(`punch_time`)");
$totalRecords = mysqli_num_rows($totlCount);

## Total number of records with filtering
$totlCountFilter = mysqli_query($conDB,"SELECT COUNT(*) AS `allcount` FROM `attendance` WHERE `emp_id` = '$emp_id' AND 1 ".$searchQuery." ".$dateSearchQuery."  GROUP BY `emp_id`, DATE(`punch_time`)");
$totalRecordwithFilter = mysqli_num_rows($totlCountFilter);

$empSlryQry = "SELECT ROUND((`basic`/30/8)*1.5,2) AS `perhour` FROM `salary_emp` WHERE `emp_id` = '$emp_id' ORDER BY `id` DESC LIMIT 1";
$empSlry = mysqli_fetch_assoc(mysqli_query($conDB, $empSlryQry));

$empQuery = "
SELECT
    `id`,
    `emp_id`,
    `emp_name`,
    `punch_state`,
    DATE(`punch_time`) AS `date`,
    MIN(time) AS `check_in`,
    MAX(time) AS `check_out`,
    (TIMESTAMPDIFF(MINUTE, MIN(`punch_time`), MAX(`punch_time`))/60) AS `hours`,
    (CASE WHEN ((TIMESTAMPDIFF(MINUTE, MIN(`punch_time`), MAX(`punch_time`))/60) > 7) THEN ROUND(((TIMESTAMPDIFF(MINUTE, MIN(`punch_time`), MAX(`punch_time`))/60) - 7) * '$empSlry[perhour]',2) END) AS `overtime`,
    (CASE WHEN ((TIMESTAMPDIFF(MINUTE, MIN(`punch_time`), MAX(`punch_time`))/60) < 7) THEN 'No Check Out' ELSE '' END) AS `status`
FROM `attendance`
WHERE `emp_id` = '$emp_id' 
AND 1 ".$searchQuery." ".$dateSearchQuery." 
GROUP BY `emp_id`, DATE(`punch_time`)
ORDER BY ".$columnName." ".$columnSortOrder." 
LIMIT ".$row.",".$rowperpage;
$empRecords = mysqli_query($conDB, $empQuery);
$data = array();

while ($row = mysqli_fetch_assoc($empRecords)) {
    $data[] = array(
    		"id"          =>$row['id'],
    		"emp_id"      =>$row['emp_id'],
    		"emp_name"    =>$row['emp_name'],
            "date"        =>$row['date'],
    		"check_in"    =>$row['check_in'],
            "check_out"   =>$row['check_out'],
            "hours"       =>$row['hours'],
            "status"      =>$row['status'],
            "punch_state" =>$row['overtime'],
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
