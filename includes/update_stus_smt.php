<?php
require_once __DIR__ . '/db.php';
	require_once __DIR__ . '/db.php';

//	include("./admin/includes/TimeStamp.php");

	include("./session_check.php");

/*********session start**********/



/*******************/

	mysqli_query($conDB, "INSERT INTO `smt_request_status` (`emp_id`, `inv_no`, `emp_name`, `status` ) VALUES ('".$_GET['emp_id']."', '".$_GET['id']."', '".$_GET['emp_name']."', 'Paid' )");

	

	// header("Location: ../all_requests.php");

	header("Location: ../open_request.php?id=$_GET[id]");

/*******************/