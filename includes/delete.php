<?php 
	require_once __DIR__ . '/db.php';
	$mysql="DELETE FROM `".$_GET['tbl']."` WHERE `id` = '".$_GET['id']."' ";
	mysqli_query($conDB, $mysql);
	/************log************/
	mysqli_query($conDB, "INSERT INTO `activity_log` (`user_editor`,`page`,`pg_id`,`reg_date`) VALUES ('".$_COOKIE['user']."','".$pgname."','".$_GET['tbl']."-".$_GET['id']."','".date("c")."')") or die (mysqli_error());
	/************log************/
//	header("Location: ../view_employee.php?id=".$_GET['id']."");
	echo "<body onload='history.go(-1);'>";
?>