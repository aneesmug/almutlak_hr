<?php

include './../../includes/db.php';
$request = 0;
if(isset($_POST['request'])){
 	$request = $_POST['request'];
}
if($request == 1){
	$id = $_POST['price_level'];
	$stmt = mysqli_query($conDB, "SELECT * FROM `menu_category` WHERE `cate_id`='$id' AND `status`='1' ORDER BY `name_eng` REGEXP '^[^A-Za-z]' ASC, `name_eng` ");
	while($row = mysqli_fetch_assoc($stmt)) {
	    echo "<option value='$row[id]'>$row[name_eng]</option>";
	}
}
//run a prepared statement 

/*if($request == 1){
	$id = $_POST['price_level'];
	$stmt = mysqli_query($conDB, "SELECT * FROM `menu_category` WHERE `cate_id`='$id' AND `status`='1' ORDER BY `name_eng` REGEXP '^[^A-Za-z]' ASC, `name_eng` ");
	while($row = mysqli_fetch_assoc($stmt)) {
	    $name_eng[] = $row;
	}
	$data = [
		'data'   	=> $name_eng,
		'status'  	=> 200
	];
	echo json_encode($data);
	exit;
}*/

?>