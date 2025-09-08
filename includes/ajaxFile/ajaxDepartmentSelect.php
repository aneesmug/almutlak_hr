<?php
	include './../../includes/db.php';
	//run a prepared statement 
	$stmt = mysqli_query($conDB, "SELECT * FROM `department` ORDER BY `dep_nme` REGEXP '^[^A-Za-z]' ASC, `dep_nme` ");

	while($row = mysqli_fetch_assoc($stmt)) {
	    $dep_nme[] = $row;
	}

	$data = [
		'data'   	=> $dep_nme,
		'status'  	=> 200
	];
	echo json_encode($data);
?>