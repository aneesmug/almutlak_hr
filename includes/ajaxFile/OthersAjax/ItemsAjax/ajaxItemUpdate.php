<?php
	require_once __DIR__ . '/../../includes/db.php';

    $name_eng_up = $_POST['name_eng'];
    $name_ar_up = $_POST['name_ar'];
    $big_price_up = $_POST['big_price'];
    $small_price_up = $_POST['small_price'];
    $big_cal_up = $_POST['big_cal'];
    $small_cal_up = $_POST['small_cal'];
    $category_id_up = $_POST['category_id'];
    $price_level_up = $_POST['price_level'];
    $status_up = $_POST['status'];
    $image_up = $_POST['iimage'];

    if(file_exists($_FILES['file']['tmp_name']) || is_uploaded_file($_FILES['file']['tmp_name'])) {
        $uploadDir = "./../../QR_MENU/images/item_img/";
        $fileName = $_FILES["file"]["name"];
        $tmpFilePath = $_FILES["file"]["tmp_name"];
        $fileType = pathinfo($fileName, PATHINFO_EXTENSION); 
        $newfilename = round(microtime(true)) . '.' . $fileType;
        $image = imagecreatefromjpeg($tmpFilePath);        
        $uploadFilePath = $uploadDir.$newfilename;
        imagejpeg($image, $uploadFilePath, 80);
        imagedestroy($image);
    } else {
        $newfilename = $image_up;
    }

    $sqlImg = "UPDATE `menu_item_img` SET `file`='".$newfilename."' WHERE `itm_id`='".$_POST['itemid']."' ";
    mysqli_query($conDB, $sqlImg);

    $sql = "UPDATE `menu_item` SET `name_eng`='".$name_eng_up."', `name_ar`='".$name_ar_up."', `big_price`='".$big_price_up."', `small_price`='".$small_price_up."',`big_cal`='".$big_cal_up."',`small_cal`='".$small_cal_up."', `price_level`='".$price_level_up."', `category_id`='".$category_id_up."', `status`='".$status_up."' WHERE `id`='".$_POST['itemid']."' ";


    if(mysqli_query($conDB, $sql)){
    	$data = [
    		'title'   => "Updated!",
    		'message' => "This user has been update successfully.",
    		'type' 	  => 'success',
    	];
        echo json_encode($data);
    } else {
        $data = [
    		'title'   => "Error!",
    		'message' => "User not updated because there are some error.",
    		'type' 	  => 'error',
    	];
        echo json_encode($data);
    }

?>