	<?php
	include './../../includes/db.php';
	include './../../includes/session_check.php';
	
	// Add company, department, and employee filter based on user's access
	$company_filter = getCompanyFilterSQL('comp_no', true);
	$department_filter = getDepartmentFilterSQL('dept', true);
	$employee_filter = getEmployeeFilterSQL('emp_id', true);
	
	//run a prepared statement 
	$stmt = mysqli_query($conDB, "SELECT * FROM `employees` WHERE `status`=1 ".$company_filter.$department_filter.$employee_filter." ORDER BY `name` REGEXP '^[^A-Za-z]' ASC, `name` ");

	while($row = mysqli_fetch_assoc($stmt)) {
	    $name[] = $row;
	}

	$data = [
		'data'   	=> $name,
		'status'  	=> 200
	];
	echo json_encode($data);
?>