<?php
// session_start();
ob_start();

$localhost = "localhost";
$db_user = "zamazema_user";
$db_pass = "hain6539306";
$db_name = "zamazema_db";

$conDB = mysqli_connect( $localhost , $db_user , $db_pass , $db_name ) or die('Error: Could not connect to database.');
$conDB->set_charset("UTF8");

$site_name = "Zamazemah Co.";
$site_title = "Zamazemah Co. | cPanel";
$site_footer = "2008 - ".date("Y")." © SnapS Production House";

/****time_zone****/
date_default_timezone_set("Asia/Riyadh");
/****time_zone****/

header('Content-Type: text/html; charset=utf-8');

error_reporting(E_ALL ^ E_NOTICE);
ini_set('display_errors', 0);

// eid_card_names

?>