<?php
	require_once __DIR__ . '/../../includes/db.php';

/*    $data = [
        'title'   => $_POST,
    ];
    echo json_encode($data);
    exit;*/

    $name_eng_up = $_POST['name_eng'];
    $name_ar_up = $_POST['name_ar'];
    $big_price_up = $_POST['big_price'];
    $small_price_up = $_POST['small_price'];
    $big_cal_up = $_POST['big_cal'];
    $small_cal_up = $_POST['small_cal'];
    $category_id_up = $_POST['category_id'];
    $price_level_up = $_POST['price_level'];

    if (file_exists($_FILES['file']['tmp_name']) || is_uploaded_file($_FILES['file']['tmp_name'])) {
        $uploadDir = "./../../QR_MENU/images/item_img/";
        $fileName = $_FILES["file"]["name"];
        $tmpFilePath = $_FILES["file"]["tmp_name"];
        $fileType = pathinfo($fileName, PATHINFO_EXTENSION); 
        $newfilename = round(microtime(true)) . '.' . $fileType;
        $image = imagecreatefromjpeg($tmpFilePath);        
        $uploadFilePath = $uploadDir.$newfilename;
        imagejpeg($image, $uploadFilePath, 80);
        imagedestroy($image);
    }

    $sql="INSERT INTO `menu_item`(`category_id`, `price_level`, `name_eng`, `name_ar`, `big_price`, `small_price`, `big_cal`, `small_cal`, `created_at`) VALUES ('$category_id_up','$price_level_up','$name_eng_up','$name_ar_up','$big_price_up','$small_price_up','$big_cal_up','$small_cal_up', '".date('Y-m-d H:i:s')."')";

    if(mysqli_query($conDB, $sql)){
    	$data = [
            'title'   => "Added!",
            'message' => "This item has been added successfully.",
            'type'    => 'success',
        ];
        if (file_exists($_FILES['file']['tmp_name']) || is_uploaded_file($_FILES['file']['tmp_name'])) {
            $sqlImg="INSERT INTO `menu_item_img`(`itm_id`, `file`) VALUES ('".mysqli_insert_id($conDB)."','$newfilename')";
            mysqli_query($conDB, $sqlImg);
        }
        echo json_encode($data);
    } else {
        $data = [
    		'title'   => "Error!",
    		'message' => "User not added because there are some error.",
    		'type' 	  => 'error',
    	];
        echo json_encode($data);
    }

?>