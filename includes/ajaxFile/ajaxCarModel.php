<?php

include './../../includes/db.php';
$request = 0;
if(isset($_POST['request'])){
 	$request = $_POST['request'];
}
if($request == 1){
	$id = $_POST['maker_name'];
	$stmt = mysqli_query($conDB, "SELECT * FROM `car_model` WHERE `mkid`='$id' ORDER BY `model` REGEXP '^[^A-Za-z]' ASC, `model` ");
	while($row = mysqli_fetch_assoc($stmt)) {
	    echo "<option value='$row[id]'>$row[model]</option>";
	}
}

?>