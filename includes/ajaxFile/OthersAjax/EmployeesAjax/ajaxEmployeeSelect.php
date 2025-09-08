<?php
	include './../../includes/db.php';
	//run a prepared statement 
	$stmt = mysqli_query($conDB, "SELECT * FROM `employees` WHERE `status`=1 ORDER BY `name` REGEXP '^[^A-Za-z]' ASC, `name` ");

	while($row = mysqli_fetch_assoc($stmt)) {
	    $name[] = $row;
	}

	$data = [
		'data'   	=> $name,
		'status'  	=> 200
	];
	echo json_encode($data);
?>