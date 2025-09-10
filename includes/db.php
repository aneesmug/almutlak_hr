<?php

ob_start();

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'admin123');
define('DB_NAME', 'almutlak_db');

$conDB = mysqli_connect( DB_HOST , DB_USER , DB_PASS , DB_NAME ) or die('Error: Could not connect to database.');
$conDB->set_charset("UTF8");

$pdo_dsn = 'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4';
$pdo_options = [
    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];
$pdo = new PDO($pdo_dsn, DB_USER, DB_PASS,$pdo_options);

function getDbConnection() {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        // Log the error message (e.g., to a file) and provide a generic message to the user
        error_log('Database Connection Error: ' . $e->getMessage());
        die(json_encode(['status' => 'error', 'message' => 'Could not connect to the database.']));
    }
}

/****time_zone****/
date_default_timezone_set("Asia/Riyadh");
mysqli_query($conDB,"SET NAMES utf8;");
// mysqli_query($conDB,"SET CHARACTER_SET utf8;");
header('Content-Type: text/html; charset=utf-8');




error_reporting(E_ALL ^ E_NOTICE);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);


$pgname = basename($_SERVER['REQUEST_URI'], '?' . $_SERVER['QUERY_STRING']);


/*$url = "http://".$_SERVER['HTTP_HOST']."";
$parsed = parse_url($url);
$domain = explode('.', $parsed['host']);
$maindomain = '';
$subdomain = '';
if ($domain[0] == 'www'){
    $subdomain  = $domain[1];
    $maindomain = (isset($domain[2]))?$domain[2]:"";
} else {
    $subdomain  = $domain[0];
    $maindomain = (isset($domain[1]))?$domain[1]:"";
}

$apiKey = "f4ebae-c62cdf-920748-1ba956-583c33";
$usrid = "mochachino_db";
$url = "https://hekayajazeera.com/restapi";
$ch = curl_init();
curl_setopt($ch, CURLOPT_USERPWD, $usrid . ":" . $apiKey);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_URL,$url);
$result=curl_exec($ch);
$apitbl=json_decode($result, true);
function tbl($index, $array){
    if (array_key_exists($index, $array)) {
        return $array[$index];
    }
}

if (!isset($apitbl['0'])) {
    echo $apitbl['data'];
    // exit();
} else {
    $apitbl = $apitbl;
}*/

require_once __DIR__ . '/init.php'; // Initialize the application and load translations

?>