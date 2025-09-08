<?php

require_once __DIR__ . '/../../includes/db.php';

//$allowed = array('gif', 'jpeg', 'jpg','pjpeg','x-png','png');
$data = $_POST['image'];
$id = $_POST['id'];
$emp_id = $_POST['emp_id'];
$emp_name = str_replace(' ','',$_POST['emp_name']);
list($type, $data) = explode(';', $data);
list(, $data) = explode(',', $data);
$data = base64_decode($data);
$imageName = time() . '.png';
$filepath = "./../../assets/emp_pics/";
$filepathup = "./assets/emp_pics/";
$imagenameu = $emp_id."".$id."".$emp_name."".$imageName;

//if ((($datap == "image/gif") || ($datap == "image/jpeg") || ($datap == "image/jpg") || ($datap == "image/pjpeg") || ($datap == "image/x-png") || (datap == "image/png"))) {
//if (in_array($ext, $allowed)) {
/*
    if (empty($data) || (isset($data['error']) && $data['error'] == UPLOAD_ERR_NO_FILE)) {
        echo "No Picture upload";
    } else {

        if (file_exists($filepath . $imageName)) {
            echo 'This picture already exists';
        } else {
            file_put_contents($filepath . $emp_id."".$id."".$emp_name."".$imageName , $data);
        	$sql = mysqli_query($conDB, "UPDATE `employees` SET `avatar`='".$filepathup."".$imagenameu."' WHERE `id`='".$id."' AND `emp_id`='".$emp_id."' ");
            echo "Image Uploaded Successfully";
        }
//		header( "Refresh:0; url= ./view_employee.php?id=".$id." ", true, 303);
    }*/

    if (empty($data) || (isset($data['error']) && $data['error'] == UPLOAD_ERR_NO_FILE)) {
        echo "No Picture upload";
    } else {
        file_put_contents($filepath . $emp_id."".$id."".$emp_name."".$imageName , $data);
        mysqli_query($conDB, "UPDATE `employees` SET `avatar`='".$filepathup."".$imagenameu."' WHERE `id`='".$id."' AND `emp_id`='".$emp_id."' ");
        $data = [
            'title'   => "Updated!",
            'message' => "Image Uploaded Successfully",
            'type'    => 'success',
        ];
        echo json_encode($data);
    }


?>