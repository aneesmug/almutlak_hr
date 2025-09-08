<?php
	include './../../includes/db.php';
	//run a prepared statement 
	$stmt = mysqli_query($conDB, "SELECT * FROM `cars_maint` WHERE `car_id`='$_POST[id]' ORDER BY `id` DESC LIMIT 1 ");

	while($row = mysqli_fetch_assoc($stmt)) {
	    $name = $row['meter'];
	}

	$data = [
		'data'   	=> $name,
	];
	echo json_encode($data);
?>