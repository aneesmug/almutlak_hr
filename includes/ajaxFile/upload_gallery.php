<?php
if(!empty($_FILES)){ 
    // Include the database configuration file 
    require_once __DIR__ . '/../../includes/db.php';
    require_once __DIR__ . '/../../includes/session_check.php';
    require_once __DIR__ . '/../FileUploader.php';
    
    // $getinv_no = $_GET['id'];
    $uploadDir = "./../../assets/gallery/"; 
    $fileName = time() . '-' . (string)FileUploader::cleanName($_FILES['file']['name'], 'image');
    $tmp_name = $_FILES['file']['tmp_name'];
    $uploadFilePath = $uploadDir . $fileName;
    // $rand = rand(0000,9999).time();
    // $rand = md5(microtime(true));

     
    // Upload file to server 
    if(FileUploader::moveTo($tmp_name, $uploadFilePath, 'image')){ 
        // Insert file information in the database 
        $sql = "INSERT INTO `gallery` (`image`,`created_at`) VALUES ('".$fileName."', '".date('Y-m-d H:i:s')."')"; 
        mysqli_query($conDB, $sql);
        
        $file_id = mysqli_insert_id($conDB);
        
        // Log gallery upload
        $file_ext = pathinfo($fileName, PATHINFO_EXTENSION);
        ActivityLogger::logUpload('System', 'upload_gallery.php', $file_id, 
            $fileName, 
            "Uploaded gallery image: {$fileName}", 
            'gallery', 
            $file_ext);
    } else {
        http_response_code(422);
        echo FileUploader::lastError();
    }
} 

?>