<?php 
	require_once __DIR__ . '/db.php';
	//$mysql="DELETE FROM `employees` WHERE `id` = '".$_GET['id']."' ";
//	mysqli_query($mysql);
	
	mysqli_query($conDB, "INSERT INTO `apply_vac_dep` (`emp_id`,`emp_name`,`dept`,`date_reg`,`status`,`review`,`replacement_per`,`vac_strt_date`,`return_date`,`vac_type`,`empgid`) VALUES ('".$_GET['emp_id']."','".$_GET['emp_name']."','".$_GET['dept']."','".date("c")."','apply','A','".$_GET['replacement_per']."','".$_GET['date']."','".$_GET['return_date']."','".$_GET['vac_type']."','".$_GET['id']."' )") or die (mysqli_error());
	
	/************log************/
	
	mysqli_query($conDB, "INSERT INTO `activity_log` (`user_editor`,`page`,`pg_id`,`reg_date`) VALUES ('".$_COOKIE['user']."','".$pgname."','".$_GET['emp_id']."','".date("c")."')") or die (mysqli_error());
	/************log************/
	header("Location: ../view_employee.php?id=$_GET[id] ");
?>