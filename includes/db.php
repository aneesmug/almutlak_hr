<?php
/**
 * MODIFICATION SUMMARY
 *
 * - Removed hardcoded database credentials (DB_HOST, DB_USER, DB_PASS, DB_NAME).
 * - Added functionality to parse database settings from an external `config.ini` file.
 * - This approach improves security and makes configuration management easier.
 * - The rest of the script will continue to function as before by using the constants
 * defined from the .ini file.
 * - Added error handling in case the configuration file is unreadable.
 */

ob_start();

// Load database configuration from an external .ini file
$configPath = __DIR__ . '/config.ini';
if (!file_exists($configPath)) {
    die('Error: Configuration file (config.ini) not found.');
}

$config = parse_ini_file($configPath, true);

if (!isset($config['database'])) {
    die('Error: Database configuration section is missing in config.ini.');
}

$dbConfig = $config['database'];

// Define constants from the configuration file
define('DB_HOST', $dbConfig['DB_HOST']);
define('DB_USER', $dbConfig['DB_USER']);
define('DB_PASS', $dbConfig['DB_PASS']);
define('DB_NAME', $dbConfig['DB_NAME']);

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

// --- Function to Get Settings ---
function get_setting($conn, $setting_name) {
    $value = null; // Default value
    $sql = "SELECT setting_value FROM app_settings WHERE setting_name = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $setting_name);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $value = $row['setting_value'];
        }
        $stmt->close();
    }
    return $value;
}

/****time_zone****/
date_default_timezone_set(get_setting($conDB, 'timezone'));
mysqli_query($conDB,"SET NAMES utf8;");
header('Content-Type: text/html; charset=utf-8');


$developer_mode = get_setting($conDB, 'developer_mode');

if($developer_mode == 1){
    error_reporting(E_ALL ^ E_NOTICE);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    ini_set('log_errors', 1);
}


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
