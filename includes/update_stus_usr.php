<?php
	require_once __DIR__ . '/db.php';
//	include("./admin/includes/TimeStamp.php");
	include("./session_check.php");
/*********session start**********/

/*******************/
	mysqli_query($conDB, "UPDATE `admin_login` SET `status`='".$_GET['status']."' WHERE `id`='".$_GET['id']."' ") or die (mysqli_error());
	
	header("Location: ../all_users.php");
/*******************/