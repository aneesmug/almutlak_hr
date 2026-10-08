<?php 

if(!empty($_FILES)){ 
    // Include the database configuration file 
    require_once __DIR__ . '/includes/db.php';
    if (!defined('SKIP_PAGE_ACCESS_CONTROL')) { define('SKIP_PAGE_ACCESS_CONTROL', true); }
    require_once __DIR__ . '/includes/session_check.php';
    require_once __DIR__ . '/includes/FileUploader.php';
     
    // File path configuration 
    $getlocationid = $_GET['location_id'];
    $uploadDir = "assets/location_content/"; 
    $fileName = (string)FileUploader::cleanName($_FILES['file']['name'], 'document');
    $tmp_name = $_FILES['file']['tmp_name'];

    $file_ext = explode('.',$fileName);
	//count taken (if more than one . exist; files like abc.fff.2013.pdf
	$file_ext_count=count($file_ext);
	//minus 1 to make the offset correct
	$cnt=$file_ext_count-1;
	// the variable will have a value pdf as per the sample file name mentioned above.
	$file_extension = (string)FileUploader::safeExt($fileName, 'document');


    $uploadFilePath = $uploadDir.$fileName; 
     
    // Upload file to server 
    if(FileUploader::moveTo($tmp_name, $uploadFilePath, 'document')){ 
        // Insert file information in the database 
        $sql = "INSERT INTO `location_docu` (`location_id`, `file_name`, `docu_ext`, `date_reg`) VALUES ('".$getlocationid."', '".$fileName."', '".$file_extension."', '".date('c')."')"; 
        mysqli_query($conDB, $sql);
    } 
}

?>