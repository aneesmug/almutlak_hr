<?php
include './../../includes/db.php';
/*include("./../../includes/custom_functions.php");*/

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
	$searchQuery = " AND (`lang` LIKE '%".$searchValue."%' OR 
        `title_obj` LIKE '%".$searchValue."%' OR 
        `text_obj` LIKE'%".$searchValue."%' ) ";
}

## Total number of records without filtering
$sel = mysqli_query($conDB,"SELECT COUNT(*) AS `allcount` FROM `language`");

$records = mysqli_fetch_assoc($sel);
$totalRecords = $records['allcount'];

## Total number of records with filtering
$sel = mysqli_query($conDB,"SELECT COUNT(*) AS `allcount` FROM `language` WHERE 1 ".$searchQuery);

$records = mysqli_fetch_assoc($sel);
$totalRecordwithFilter = $records['allcount'];

## Fetch records
// $empQuery = "SELECT * FROM `language` WHERE 1 ".$searchQuery." ORDER BY ".$columnName." ".$columnSortOrder." LIMIT ".$row.",".$rowperpage ;
$empQuery = "SELECT * FROM `language` WHERE 1 ".$searchQuery." ORDER BY `id` DESC LIMIT ".$row.",".$rowperpage ;

$empRecords = mysqli_query($conDB, $empQuery);
$data = array();

while ($row = mysqli_fetch_assoc($empRecords)) {
    $data[] = array(
    	"id"          =>    $row['id'],
    	"lang"        =>    $row['lang'],
        "title_obj"   =>    $row['title_obj'],
    	"text_obj"    =>    $row['text_obj'],
        "action"      =>     "<div class='btn-group dropdown'>
                    <a href='javascript: void(0);' class='table-action-btn dropdown-toggle arrow-none btn btn-light btn-sm' data-toggle='dropdown' aria-expanded='false'><i class='mdi mdi-dots-horizontal'></i></a>
                    <div class='dropdown-menu dropdown-menu-right' x-placement='bottom-end' >
                        <a class='dropdown-item text-info updateLang' href='javascript:void(0);' data-id='$row[id]' data-lang='$row[lang]' data-title_obj='$row[title_obj]' data-text_obj='$row[text_obj]' ><i class='mdi mdi-autorenew mr-2 font-18 vertical-middle'></i>Edit</a>
                        <a href='javascript:void(0);' class='dropdown-item text-danger deleteTblAjax' data-id='$row[id]' data-tbl='language' data-file='0'><i class='fa fa-trash mr-2 font-18 vertical-middle'></i>Delete</a>
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
