<?php
include './../../includes/db.php';
include("./../../includes/custom_functions.php");

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
	$searchQuery = " AND (`customer_access`.`fullname` LIKE '%".$searchValue."%' OR 
        `cart_order`.`order_id` LIKE '%".$searchValue."%' OR 
        `customer_access`.`mobile` LIKE '%".$searchValue."%' OR
        `customer_cart_address`.`city` LIKE'%".$searchValue."%' ) ";
}

## Total number of records without filtering
/*$sel = mysqli_query($conDB,"select count(*) as allcount from customer");*/
$sel = mysqli_query($conDB,"SELECT COUNT(count) AS `allcount` FROM (SELECT COUNT(`order_id`) AS count FROM `cart_order` WHERE `deleted`='0' GROUP BY `order_id` HAVING count > 0) as A ");

$records = mysqli_fetch_assoc($sel);
$totalRecords = $records['allcount'];

## Total number of records with filtering
/*$sel = mysqli_query($conDB,"select count(*) as allcount from customer WHERE 1 ".$searchQuery);*/
$sel = mysqli_query($conDB,"SELECT COUNT(count) AS `allcount`FROM (SELECT COUNT(`cart_order`.`order_id`) AS count FROM `cart_order` LEFT JOIN `customer_access` ON `customer_access`.`id` = `cart_order`.`uid` LEFT JOIN `customer_cart_address` ON `customer_cart_address`.`cust_id` = `customer_access`.`id` WHERE 1 {$searchQuery} AND `cart_order`.`deleted`='0' GROUP BY `cart_order`.`order_id` HAVING count > 0) AS A ");

$records = mysqli_fetch_assoc($sel);
$totalRecordwithFilter = $records['allcount'];

## Fetch records
// $empQuery = "select * from customer WHERE 1 ".$searchQuery." order by ".$columnName." ".$columnSortOrder." limit ".$row.",".$rowperpage;
$empQuery = "
    SELECT `cart_order`.*, `cart_order`.`created_at` AS `odrDate`, `cart_order_status`.`status` AS `statusOrder`, `customer_access`.*, `customer_cart_address`.`city`
    FROM `cart_order`
    LEFT JOIN `cart_order_status` ON `cart_order_status`.`order_id` = `cart_order`.`order_id`
    LEFT JOIN `customer_access` ON `customer_access`.`id` = `cart_order`.`uid`
    LEFT JOIN `customer_cart_address` ON `customer_cart_address`.`id` = `cart_order`.`uaddsid`
      WHERE 1 {$searchQuery}
      AND `cart_order`.`deleted` = '0'
      AND `cart_order_status`.`status` = (
          SELECT `cart_order_status`.`status`
          FROM `cart_order_status`
          WHERE `cart_order`.`order_id` = `cart_order_status`.`order_id`
          ORDER BY `cart_order_status`.`id` DESC
          LIMIT 1 )
    GROUP BY `cart_order`.`order_id` 
    ORDER BY {$columnName} {$columnSortOrder} 
    LIMIT {$row}, {$rowperpage} ";

$empRecords = mysqli_query($conDB, $empQuery);
$data = array();

while ($row = mysqli_fetch_assoc($empRecords)) {
    $data[] = array(
    	"id"          =>    $row['id'],
    	"fullname"    =>    $row['fullname'],
        "order_id"    =>    $row['order_id'],
    	"qty"         =>    $row['qty'],
        "mobile"      =>    $row['mobile'],
        "city"        =>    $row['city'],
        "odrDate"     =>    timeAgo($row['odrDate']),
    	"statusOrder" =>    ($row['statusOrder']=="draft" ? "<span class='badge-border badge-border-warning'><i class='fa fa-clock-rotate-left'></i> Waiting...</span>" : 
                                ($row['statusOrder']=="preparing" ? "<span class='badge-border badge-border-secondary'><i class='fa fa-box-open-full'></i> Preparing Order</span>" : 
                                  ($row['statusOrder']=="u_shipping" ? "<span class='badge-border badge-border-custom'><i class='fa fa-truck-fast'></i> Shipping Progress</span>" : 
                                    ($row['statusOrder']=="cancel" ? "<span class='badge-border badge-border-danger'><i class='fa fa-times'></i> Canceled</span>" : 
                                      "<span class='badge-border badge-border-success'><i class='fa fa-badge-border-check'></i> Delivered</span>"
                                    )
                                  ) 
                                ) 
                            ),

        "action"     =>     "<div class='btn-group dropdown'>
                    <a href='javascript: void(0);' class='table-action-btn dropdown-toggle arrow-none btn btn-light btn-sm' data-toggle='dropdown' aria-expanded='false'><i class='mdi mdi-dots-horizontal'></i></a>
                    <div class='dropdown-menu dropdown-menu-right' x-placement='bottom-end' >
                        <a class='dropdown-item text-dark' href='./view_order.php?id=$row[order_id]'><i class='mdi mdi-eye-outline mr-2 font-18 vertical-middle'></i>Open</a>
                        <a class='dropdown-item text-info updateOrder' href='javascript:void(0);' data-order_id='$row[order_id]' data-uid='$row[uid]' data-status='$row[statusOrder]' ><i class='mdi mdi-autorenew mr-2 font-18 vertical-middle'></i>Update Status</a>
                        <a href='javascript:void(0);' class='dropdown-item text-danger deleteOrder' data-order_id='$row[order_id]' data-tbl='customer'><i class='fa fa-trash mr-2 font-18 vertical-middle'></i>Delete</a>
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
