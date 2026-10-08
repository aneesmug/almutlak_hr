<?php
	require_once __DIR__ . '/../../includes/db.php';
    require_once __DIR__ . '/../FileUploader.php';
    
/*if(!empty($_FILES)){ */
    // File path configuration 
    $getlocationid = $_POST['location_id'];
    $uploadDir = "./../../assets/location_content/"; 
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
        $sql = "INSERT INTO `location_docu` (`location_id`, `file_name`, `docu_ext`, `created_at`) VALUES ('".$getlocationid."', '".$fileName."', '".$file_extension."', '".date('Y-m-d H:i:s')."')"; 
        mysqli_query($conDB, $sql);
        $data = [
            'title'   => "Updated!",
            'message' => "File Uploaded Successfully",
            'type'    => 'success',
        ];
        echo json_encode($data);
    } else {
        echo json_encode(['title' => "Error!", 'message' => FileUploader::lastError(), 'type' => 'error']);
    } 
// }


?>