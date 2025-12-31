	<?php
	include './../../includes/db.php';
	include './../../includes/session_check.php';
	
	// Add company filter based on user's access
	$company_filter = getCompanyFilterSQL('comp_no', true);
	
	//run a prepared statement 
	$stmt = mysqli_query($conDB, "SELECT * FROM `employees` WHERE `status`=1 ".$company_filter." ORDER BY `name` REGEXP '^[^A-Za-z]' ASC, `name` ");

	while($row = mysqli_fetch_assoc($stmt)) {
	    $name[] = $row;
	}

	$data = [
		'data'   	=> $name,
		'status'  	=> 200
	];
	echo json_encode($data);
?>