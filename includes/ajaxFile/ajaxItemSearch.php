<?php

include './../../includes/db.php';
	
	$input = $_POST['term'];
	//run a prepared statement 
	$stmt = mysqli_query($conDB, "
		SELECT * 
		FROM `menu_item` 
		WHERE `price_level` = '4' 
		GROUP BY `name_eng`");

	if (mysqli_num_rows($stmt) > 1) {
		while($row = mysqli_fetch_assoc($stmt)) {
		    $items[] = $row['name_eng']." ".$row['name_ar'];
		}
	}
	echo json_encode($items);

?>