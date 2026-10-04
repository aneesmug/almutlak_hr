<?php
include './../../includes/db.php';
include './../../includes/init.php';
include './../../includes/helper_functions.php';

## Read value
$draw = $_POST['draw'];
$row = $_POST['start'];
$rowperpage = $_POST['length']; // Rows display per page
$columnIndex = $_POST['order'][0]['column']; // Column index
$columnName = $_POST['columns'][$columnIndex]['data'] ?? 'id'; // Column name
$columnSortOrder = strtolower($_POST['order'][0]['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc'; // asc or desc
// Only real, sortable columns may reach ORDER BY
$sortableColumns = ['id' => '`vouchers`.`id`', 'voucher_no' => '`vouchers`.`voucher_no`', 'voucher_type' => '`vouchers`.`voucher_type`',
    'voucher_amount' => '`vouchers`.`voucher_amount`', 'created_at' => '`vouchers`.`created_at`', 'name' => '`name`', 'emp_from' => '`emp_from`'];
$columnName = $sortableColumns[$columnName] ?? '`vouchers`.`id`';
$userDeptSafe = mysqli_real_escape_string($conDB, (string)($_POST['user_dept'] ?? ''));
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
    $typeSearchQuery = " AND `vouchers`.`voucher_type` = '".$typeValue."' ";
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
    $trCount = mysqli_query($conDB,"SELECT COUNT(*) AS `allcount` FROM `vouchers` WHERE `dept` = '".$userDeptSafe."'");
    $trfCount = mysqli_query($conDB,"SELECT COUNT(*) AS `allcount` FROM `vouchers` LEFT JOIN `employees` ON `employees`.`emp_id` = `vouchers`.`to_emp` WHERE 1 AND `vouchers`.`dept` = '".$userDeptSafe."' ".$searchQuery." ".$typeSearchQuery);
    $empQuery = "
    SELECT `vouchers`.*, 
       `employee_to`.`name` AS `name`, 
       `employee_from`.`name` AS `emp_from`
    FROM `vouchers` 
    LEFT JOIN `employees` AS `employee_to` ON `employee_to`.`emp_id` = `vouchers`.`to_emp`
    LEFT JOIN `employees` AS `employee_from` ON `employee_from`.`emp_id` = `vouchers`.`emp_id`
    WHERE 1 AND `vouchers`.`dept` = '".$userDeptSafe."' ".$searchQuery." ".$typeSearchQuery." ORDER BY ".$columnName." ".$columnSortOrder." LIMIT ".$row.",".$rowperpage;
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

    $vid = (int)$row['id'];
    $fileSafe = htmlspecialchars((string)$row['file'], ENT_QUOTES);
    $attach = ($row['file']) ? "<a href=\"javascript:displayPopup('./assets/voucher_documents/".$fileSafe."')\" class='dropdown-item text-dark'><i class='fa fa-paperclip'></i>".__('view_file', 'View File')."</a>" : "";
    $delete = ($_POST['user_type'] == 'administrator')
        ? "<div class='dropdown-divider'></div><a href='javascript:void(0);' class='dropdown-item text-danger deleteAjax' data-id='".$vid."' data-tbl='vouchers' data-file='1' data-column='file'><i class='fa fa-trash'></i>".__('delete')."</a>"
        : "";
    $actionHtml = "<div class='sr-actions'>
                        <a href='voucher_print.php?id=".$vid."' target='_blank' class='sr-open-btn'><i class='mdi mdi-printer'></i> ".__('print')."</a>";
    if ($attach !== '' || $delete !== '') {
        $actionHtml .= "<div class='btn-group dropdown'>
                        <a href='javascript: void(0);' class='sr-more-btn dropdown-toggle arrow-none' data-toggle='dropdown' aria-expanded='false'><i class='mdi mdi-dots-vertical'></i></a>
                        <div class='dropdown-menu dropdown-menu-right' x-placement='bottom-end'>".$attach.$delete."</div>
                    </div>";
    }
    $actionHtml .= "</div>";

    $data[] = array(
    		"id"              =>$row['id'],
    		"voucher_no"      =>$row['voucher_no'],
            "emp_from"        =>parseName($row["emp_from"]),
            "name"            =>parseName($row["name"]),
            "voucher_type"    =>$row['voucher_type'],
    		"voucher_amount"  =>$row['voucher_amount'],
            "details"         =>$row["details"],
            "created_at"      =>date("Y-m-d",strtotime($row["created_at"])),
            "action"          => $actionHtml,
    	);
}

## Per-type count + amount for the summary tiles (same scope/search, every type)
$counts = ['all' => ['count' => 0, 'amount' => 0]];
$deptScope = ($_POST['user_type'] == 'administrator') ? '' : " AND `vouchers`.`dept` = '".$userDeptSafe."'";
$sumRes = mysqli_query($conDB, "SELECT `vouchers`.`voucher_type`, COUNT(*) AS `c`, COALESCE(SUM(`vouchers`.`voucher_amount`), 0) AS `amt`
    FROM `vouchers` LEFT JOIN `employees` ON `employees`.`emp_id` = `vouchers`.`to_emp`
    WHERE 1 ".$deptScope." ".$searchQuery." GROUP BY `vouchers`.`voucher_type`");
while ($sumRes && ($sr = mysqli_fetch_assoc($sumRes))) {
    $counts[$sr['voucher_type']] = ['count' => (int)$sr['c'], 'amount' => (float)$sr['amt']];
    $counts['all']['count'] += (int)$sr['c'];
    $counts['all']['amount'] += (float)$sr['amt'];
}

## Response
$response = array(
    "draw" => intval($draw),
    "iTotalRecords" => $totalRecords,
    "iTotalDisplayRecords" => $totalRecordwithFilter,
    "counts" => $counts,
    "aaData" => $data
);

echo json_encode($response);
