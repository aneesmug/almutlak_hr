<?php

if(!empty($_FILES)){ 
require_once __DIR__ . '/../../includes/db.php';
    // Include the database configuration file 

    require_once __DIR__ . '/../../includes/db.php';

    // File path configuration 

    $getinv_no = $_POST['id'];

    // $getinv_no = $_GET['id'];

    $uploadDir = "./../../assets/assets/smt_attachment/"; 

    $fileName = basename($_FILES['file']['name']);

    $tmp_name = $_FILES['file']['tmp_name'];

    // $rand = rand(0000,9999).time();

    // $rand = md5(microtime(true));

    $rand = md5($fileName);

    $file_ext = explode('.',$fileName);

	//count taken (if more than one . exist; files like abc.fff.2013.pdf

	$file_ext_count=count($file_ext);

	//minus 1 to make the offset correct

	$cnt=$file_ext_count-1;

	// the variable will have a value pdf as per the sample file name mentioned above.

	$file_extension= $file_ext[$cnt];



    /*$filename_po = $getinv_no."_".date('dmYHis')."_".$rand.".".$file_extension;*/

    $filename_po = $getinv_no."_".$rand.".".$file_extension;



    $uploadFilePath = $uploadDir.$filename_po; 

     

    // Upload file to server 

    if(move_uploaded_file($tmp_name, $uploadFilePath)){ 

        // Insert file information in the database 

        $sql = "INSERT INTO `smt_attachment` (`inv_no`, `attachment`, `docu_ext`) VALUES ('".$getinv_no."', '".$filename_po."', '".$file_extension."')"; 

        mysqli_query($conDB, $sql);

    } 

} 



?>