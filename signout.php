<?php
session_start();
//require_once __DIR__ . '/includes/db.php';
//deleting cookie by setting expirty to past time
/************log************/
/*mysqli_query($conDB,"INSERT INTO `activity_log` (`user_editor`,`page`,`pg_id`,`reg_date`) VALUES ('".$_COOKIE['user']."','".$pgname."','signout','".date("c")."')") or die (mysqli_error());*/
/************log************/
$res = setcookie('user', '', time() - 3600);
//destroys all session variables
unset($_SESSION['user']);
unset($_SESSION['verify_user_type']);
session_destroy();
header("Location: ./index.php");
//	session_start();
//	header('Location: index.php');

?>