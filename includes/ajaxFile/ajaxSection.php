<?php

include './../../includes/db.php';

	//run a prepared statement 
	$stmt = mysqli_query($conDB, "SELECT * FROM `section` ORDER BY `section_name` REGEXP '^[^A-Za-z]' ASC, `section_name` ");

	while($row = mysqli_fetch_assoc($stmt)) {
	    $section_name[] = $row;
	}

	$data = [
		'data'   	=> $section_name,
		'status'  	=> 200
	];
	
	echo json_encode($data);


?>