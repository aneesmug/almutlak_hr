<?php
// session_start();
ob_start();

$localhost = "localhost";
$db_user = "mochachino_user";
$db_pass = "hain6539306";
$db_name = "mochachino_db";
// $db_name = "mochasysdb";
//$db_name = "mochahr_db_demo";

$conDB = mysqli_connect( $localhost , $db_user , $db_pass , $db_name ) or die('Error: Could not connect to database.');
$conDB->set_charset("UTF8");

?>