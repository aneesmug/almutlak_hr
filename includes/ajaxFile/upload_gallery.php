<?php
if(!empty($_FILES)){ 
    // Include the database configuration file 
    require_once __DIR__ . '/../../includes/db.php';
    // $getinv_no = $_GET['id'];
    $uploadDir = "./../../assets/gallery/"; 
    $fileName = time().'-'. $_FILES['file']['name'];
    $tmp_name = $_FILES['file']['tmp_name'];
    $uploadFilePath = $uploadDir."/".$fileName;
    // $rand = rand(0000,9999).time();
    // $rand = md5(microtime(true));

     
    // Upload file to server 
    if(move_uploaded_file($tmp_name, $uploadFilePath)){ 
        // Insert file information in the database 
        $sql = "INSERT INTO `gallery` (`image`,`created_at`) VALUES ('".$fileName."', '".date('Y-m-d H:i:s')."')"; 
        mysqli_query($conDB, $sql);
    } 
} 

?>