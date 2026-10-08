<?php 

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS'); 
 
if(array_key_exists('HTTP_ACCESS_CONTROL_REQUEST_HEADERS', $_SERVER)) {
    header('Access-Control-Allow-Headers: '
           . $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']);
} else {
    header('Access-Control-Allow-Headers: origin, x-requested-with, content-type, cache-control');
}
 
if($_SERVER['REQUEST_METHOD']=='OPTIONS') die();


if(!empty($_FILES)){ 
    // Include the database configuration file 
    require_once __DIR__ . '/includes/db.php';
    if (!defined('SKIP_PAGE_ACCESS_CONTROL')) { define('SKIP_PAGE_ACCESS_CONTROL', true); }
    require_once __DIR__ . '/includes/session_check.php';
    require_once __DIR__ . '/includes/FileUploader.php';
     
    // File path configuration 
    // $getlocationid = $_GET['location_id'];
    $uploadDir = "./file_manager/"; 
    $fileName = (string)FileUploader::cleanName($_FILES['file']['name'], 'any');
    $tmp_name = $_FILES['file']['tmp_name'];

    $file_ext = explode('.',$fileName);
	//count taken (if more than one . exist; files like abc.fff.2013.pdf
	$file_ext_count=count($file_ext);
	//minus 1 to make the offset correct
	$cnt=$file_ext_count-1;
	// the variable will have a value pdf as per the sample file name mentioned above.
	$file_extension = (string)FileUploader::safeExt($fileName, 'any');


    $uploadFilePath = $uploadDir.$fileName; 
     
    // Upload file to server 
    if(FileUploader::moveTo($tmp_name, $uploadFilePath, 'any')){ 
        // Insert file information in the database 
        // $sql = "INSERT INTO `location_docu` (`location_id`, `file_name`, `docu_ext`, `date_reg`) VALUES ('".$getlocationid."', '".$fileName."', '".$file_extension."', '".date('c')."')"; 
        // mysql_query($sql);
    } 
} 

?>