<?php
session_start();
ob_start();

$localhost = "localhost";
$db_user = "root";
$db_pass = "admin123";
$db_name = "hr_db";
//$db_name = "mochahr_db_demo";

mysql_connect($localhost , $db_user , $db_pass) or die (mysql_error(0));
mysql_select_db($db_name) or die (mysql_error(0));

$conDB = mysqli_connect( $localhost , $db_user , $db_pass , $db_name )or die('Error: Could not connect to database.');

$result_glr=mysql_select_db($db_name);

$site_name = "Mochachino Co.";
$site_title = "Mochachino Co. | cPanel";
$site_footer = "2009 - ".date("Y")." © SnapS Production House";

/****time_zone****/
date_default_timezone_set("Asia/Riyadh");

mysql_query("SET NAMES utf8;");
mysql_query("SET CHARACTER_SET utf8;");
header('Content-Type: text/html; charset=utf-8');



$url = "http://".$_SERVER['HTTP_HOST']."";
$parsed = parse_url($url);
$domain = explode('.', $parsed['host']);
$subdomain = '';

if ($domain[0] == 'www'){
    $subdomain = $domain[1];
} else {
    $subdomain = $domain[0];
}
error_reporting(E_ALL ^ E_NOTICE);
$pgname = basename($_SERVER['REQUEST_URI'], '?' . $_SERVER['QUERY_STRING']);

?>