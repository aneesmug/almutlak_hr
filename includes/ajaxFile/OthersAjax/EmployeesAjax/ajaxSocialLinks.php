<?php

	include './../../includes/db.php';
	//run a prepared statement 

	// $socquery = mysqli_query($conDB, "SELECT * FROM `social` WHERE `emp_id`='".$_POST['emp_id']."' ");


	// $stmt = mysqli_query($conDB, "SELECT * FROM `social_list` WHERE `id`="'..'" ");
	$stmt = mysqli_query($conDB, "
		SELECT * FROM `social_list` WHERE `id` NOT IN (
		    SELECT `social_list`.`id` FROM `social_list`
		    LEFT JOIN `social` ON `social`.`social_id` = `social_list`.`id`
		    WHERE `social`.`emp_id`='".$_POST['emp_id']."'
		)"
	);
	while($row = mysqli_fetch_assoc($stmt)) {
	    $section_name[] = $row;
	}
	$data = [
		'data'   	=> $section_name,
		'status'  	=> 200
	];
	echo json_encode($data);
	
?>