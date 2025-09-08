<?php
	include './../../includes/db.php';
	//run a prepared statement 
	$stmt = mysqli_query($conDB, "SELECT * FROM `category_type` ORDER BY `type` REGEXP '^[^A-Za-z]' ASC, `type` ");

	while($row = mysqli_fetch_assoc($stmt)) {
	    $type[] = $row;
	}

	$data = [
		'data'   	=> $type,
		'status'  	=> 200
	];
	echo json_encode($data);
?>