<?php
	require_once __DIR__ . '/../../includes/db.php';
    
/*if(!empty($_FILES)){ */
    // File path configuration 
    $getlocationid = $_POST['location_id'];
    $uploadDir = "./../../assets/location_content/"; 
    $fileName = basename($_FILES['file']['name']);
    $tmp_name = $_FILES['file']['tmp_name'];
    $file_ext = explode('.',$fileName);
    //count taken (if more than one . exist; files like abc.fff.2013.pdf
    $file_ext_count=count($file_ext);
    //minus 1 to make the offset correct
    $cnt=$file_ext_count-1;
    // the variable will have a value pdf as per the sample file name mentioned above.
    $file_extension= $file_ext[$cnt];
    $uploadFilePath = $uploadDir.$fileName; 
    // Upload file to server 
    if(move_uploaded_file($tmp_name, $uploadFilePath)){ 
        // Insert file information in the database 
        $sql = "INSERT INTO `location_docu` (`location_id`, `file_name`, `docu_ext`, `created_at`) VALUES ('".$getlocationid."', '".$fileName."', '".$file_extension."', '".date('Y-m-d H:i:s')."')"; 
        mysqli_query($conDB, $sql);
        $data = [
            'title'   => "Updated!",
            'message' => "File Uploaded Successfully",
            'type'    => 'success',
        ];
        echo json_encode($data);
    } 
// }


?>