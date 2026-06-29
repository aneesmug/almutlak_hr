	<?php
	include './../../includes/db.php';
	include './../../includes/session_check.php';
	
	// Use effective employee scope so explicitly allowed employees are not
	// hidden by company/department intersection.
	$employee_filter = getEmployeeFilterSQL('emp_id', true);
	
	//run a prepared statement 
	$stmt = mysqli_query($conDB, "SELECT * FROM `employees` WHERE `status`=1 ".$employee_filter." ORDER BY `name` REGEXP '^[^A-Za-z]' ASC, `name` ");

	while($row = mysqli_fetch_assoc($stmt)) {
	    $name[] = $row;
	}

	$data = [
		'data'   	=> $name,
		'status'  	=> 200
	];
	echo json_encode($data);
?>