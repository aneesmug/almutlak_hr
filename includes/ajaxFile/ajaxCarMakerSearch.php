<?php

include './../../includes/db.php';
	
	//run a prepared statement 
	$stmt = mysqli_query($conDB, "
		SELECT * 
		FROM `car_maker`
		GROUP BY `maker`");

	if (mysqli_num_rows($stmt) > 1) {
		while($row = mysqli_fetch_assoc($stmt)) {
		    $items[] = $row;
		}
	}
	$data = [
		'status' 	=> 200,
		'data' 		=> $items,
	];
	echo json_encode($data);

?>