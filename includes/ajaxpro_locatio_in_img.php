<?php
require_once __DIR__ . '/db.php';


//$allowed = array('gif', 'jpeg', 'jpg','pjpeg','x-png','png');
$data = $_POST['image'];
$id = $_POST['id'];
// $emp_id = $_POST['emp_id'];
$section_name = str_replace(' ','',$_POST['section_name_get']);

//$ext = pathinfo($data, PATHINFO_EXTENSION);

list($type, $data) = explode(';', $data);
list(, $data) = explode(',', $data);
$data = base64_decode($data);


$imageName = time() . '.png';
$filepath = "../assets/location_content/";
$filepathup = "./assets/location_content/";
$imagenameu = $id."".$section_name."".$imageName;

    if (empty($data) || (isset($data['error']) && $data['error'] == UPLOAD_ERR_NO_FILE)) {
        echo "No Picture upload";
    } else {

        if (file_exists($filepath . $imageName)) {
            echo 'This picture already exists';
        } else {

                file_put_contents($filepath . $id."".$section_name."".$imageName , $data);
            	mysqli_query($conDB, "UPDATE `location_img` SET `in_img`='".$filepathup."".$imagenameu."' WHERE `location_id`='".$id."' ");
                
                echo "Image Uploaded Successfully";
        }
//		header( "Refresh:0; url= ./view_employee.php?id=".$id." ", true, 303);
    }
	
//} else {
//    echo('Something went wrong');
//}
?>