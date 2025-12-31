<?php
	include './../../includes/db.php';
	include './../../includes/session_check.php';
	//run a prepared statement with company filter
	$company_filter = getCompanyFilterSQL('comp_no', true);
	$stmt = mysqli_query($conDB, "SELECT * FROM `employees` WHERE `status`=1" . $company_filter . " ORDER BY `name` REGEXP '^[^A-Za-z]' ASC, `name` ");

	while($row = mysqli_fetch_assoc($stmt)) {
	    $name[] = $row;
	}

	$data = [
		'data'   	=> $name,
		'status'  	=> 200
	];
	echo json_encode($data);
?>