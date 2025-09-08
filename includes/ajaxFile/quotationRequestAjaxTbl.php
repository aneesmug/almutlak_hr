<?php
include './../../includes/db.php';
// Include your database connection code here

// Parameters sent by DataTables
$draw = $_POST['draw'];
$start = $_POST['start'];
$length = $_POST['length'];
$order = $_POST['order'][0];
// $search = $_POST['search']['value'];

$user_type = $_POST['user_type'];
$user_dept = $_POST['user_dept'];
$emptype = $_POST['emptype'];

$searchValue = mysqli_real_escape_string($conDB,$_POST['search']); // Search value
$typeValue = mysqli_real_escape_string($conDB,$_POST['smtStatus']); // Search value

## Search 
$searchQuery = " ";
if($searchValue != ''){
    $searchQuery = " AND (
        `sr`.`inv_no` LIKE '%".$searchValue."%' 
        OR `sr`.`sub_title` LIKE '%".$searchValue."%' 
        OR `sr`.`sub_type` LIKE '%".$searchValue."%' 
        OR `sr`.`department` LIKE '%".$searchValue."%' 
        OR `sr`.`prep_by` LIKE'%".$searchValue."%'
    )";
}

$typeSearchQuery = " ";
if($typeValue != ''){
    $typeSearchQuery = " AND `srs`.`status` = '".$typeValue."' ";
}

if ($user_type == 'administrator') {
// Your LEFT JOIN SQL query
    $sql = "
        SELECT sr.*, srs.status
        FROM (
            SELECT inv_no, MAX(id) AS max_id
            FROM smt_request_status
            GROUP BY inv_no
        ) max_ids
        JOIN smart_request sr ON max_ids.inv_no = sr.inv_no
        LEFT JOIN smt_request_status srs ON max_ids.inv_no = srs.inv_no AND max_ids.max_id = srs.id
        WHERE 1 ".$searchQuery." ".$typeSearchQuery."
        GROUP BY sr.inv_no
        ORDER BY `sr`.`id` DESC
        LIMIT $start, $length
    ";
} elseif ($user_type == "dept_user" AND $emptype == "Manager" AND $user_dept == "Finance") {
    $sql = "
        SELECT sr.*, srs.status
        FROM (
            SELECT inv_no, MAX(id) AS max_id
            FROM smt_request_status
            GROUP BY inv_no
        ) max_ids
        JOIN smart_request sr ON max_ids.inv_no = sr.inv_no
        LEFT JOIN smt_request_status srs ON max_ids.inv_no = srs.inv_no AND max_ids.max_id = srs.id
        WHERE 1 ".$searchQuery." ".$typeSearchQuery."
        GROUP BY sr.inv_no
        ORDER BY `sr`.`id` DESC
        LIMIT $start, $length
    ";
} elseif ($user_type == "assistant" AND $user_dept == "Finance") {
    $sql = "
        SELECT sr.*, srs.status
        FROM (
            SELECT inv_no, MAX(id) AS max_id
            FROM smt_request_status
            GROUP BY inv_no
        ) max_ids
        JOIN smart_request sr ON max_ids.inv_no = sr.inv_no
        LEFT JOIN smt_request_status srs ON max_ids.inv_no = srs.inv_no AND max_ids.max_id = srs.id
        WHERE 1 ".$searchQuery." ".$typeSearchQuery." AND (`srs`.`status` = 'approve' OR `srs`.`status` = 'Paid' OR `srs`.`status` = 'Manager')
        GROUP BY sr.inv_no
        ORDER BY `sr`.`id` DESC
        LIMIT $start, $length
    ";

    $sqlTotalCount = " AND (`srs`.`status` = 'approve' OR `srs`.`status` = 'Paid' OR `srs`.`status` = 'Manager')";
    $sqlFilterCount = " AND (`srs`.`status` = 'approve' OR `srs`.`status` = 'Paid' OR `srs`.`status` = 'Manager')";

} elseif ($user_type == "gm") {
    $sql = "
        SELECT sr.*, srs.status
        FROM (
            SELECT inv_no, MAX(id) AS max_id
            FROM smt_request_status
            GROUP BY inv_no
        ) max_ids
        JOIN smart_request sr ON max_ids.inv_no = sr.inv_no
        LEFT JOIN smt_request_status srs ON max_ids.inv_no = srs.inv_no AND max_ids.max_id = srs.id
        WHERE 1 ".$searchQuery." ".$typeSearchQuery." AND (`srs`.`status` = 'approve' OR `srs`.`status` = 'reject' OR `srs`.`status` = 'Management')
        GROUP BY sr.inv_no
        ORDER BY `sr`.`id` DESC
        LIMIT $start, $length
    ";

    $sqlTotalCount = " AND (`srs`.`status` = 'approve' OR `srs`.`status` = 'reject' OR `srs`.`status` = 'Management')";
    $sqlFilterCount = " AND (`srs`.`status` = 'approve' OR `srs`.`status` = 'reject' OR `srs`.`status` = 'Management')";

} else {
    $sql = "
        SELECT sr.*, srs.status
        FROM (
            SELECT inv_no, MAX(id) AS max_id
            FROM smt_request_status
            GROUP BY inv_no
        ) max_ids
        JOIN smart_request sr ON max_ids.inv_no = sr.inv_no
        LEFT JOIN smt_request_status srs ON max_ids.inv_no = srs.inv_no AND max_ids.max_id = srs.id
        WHERE 1 ".$searchQuery." ".$typeSearchQuery." AND `sr`.`department` = '$user_dept'
        GROUP BY sr.inv_no
        ORDER BY `sr`.`id` DESC
        LIMIT $start, $length
    ";

    $sqlTotalCount = " AND `sr`.`department` = '$user_dept' ";
    $sqlFilterCount = " AND `sr`.`department` = '$user_dept' ";

}

$query = mysqli_query($conDB, $sql);

// Fetch data and format as JSON
$data = array();
while ($row = mysqli_fetch_assoc($query)) {
    // $data[] = $row;
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

$sqlTotal = "
SELECT COUNT(*) AS `allcount`
    FROM (
        SELECT inv_no, MAX(id) AS max_id
        FROM smt_request_status
        GROUP BY inv_no
    ) max_ids
    JOIN smart_request sr ON max_ids.inv_no = sr.inv_no
    LEFT JOIN smt_request_status srs ON max_ids.inv_no = srs.inv_no AND max_ids.max_id = srs.id
    WHERE 1 ".$sqlTotalCount."
    GROUP BY sr.inv_no;
";
// Total records without filtering
$totalRecords = mysqli_num_rows(mysqli_query($conDB, $sqlTotal));

$sqlFilter = "
    SELECT COUNT(*) AS `allcount`
    FROM (
        SELECT inv_no, MAX(id) AS max_id
        FROM smt_request_status
        GROUP BY inv_no
    ) max_ids
    JOIN smart_request sr ON max_ids.inv_no = sr.inv_no
    LEFT JOIN smt_request_status srs ON max_ids.inv_no = srs.inv_no AND max_ids.max_id = srs.id
    WHERE 1 ".$searchQuery." ".$typeSearchQuery." ".$sqlTotalCount."
    GROUP BY sr.inv_no
";
// Total records with filtering
$filteredRecords = mysqli_num_rows(mysqli_query($conDB, $sqlFilter));

// Prepare JSON response
$response = array(
    "draw" => intval($draw),
    "recordsTotal" => intval($totalRecords),
    "recordsFiltered" => intval($filteredRecords),
    "data" => $data
);

echo json_encode($response);
?>
