<?php

include './../../includes/db.php';

	//run a prepared statement 
	$stmt = mysqli_query($conDB, "SELECT * FROM `docu_type` ORDER BY `duc_type` REGEXP '^[^A-Za-z]' ASC, `duc_type`");

	while($row = mysqli_fetch_assoc($stmt)) {
	    $sub_type[] = $row;
	}

	$data = [
		'data'   	=> $sub_type,
		'status'  	=> 200
	];
	
	echo json_encode($data);


?>