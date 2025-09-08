<?php
	require_once __DIR__ . '/../../includes/db.php';

$ajaxType = $_POST['ajaxType'];

if ($ajaxType == 'add_item') {
    // Add Item Query
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
} elseif($ajaxType == 'edit_item'){
    // Edit Item Query
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
            'type'    => 'success',
        ];
        echo json_encode($data);
    } else {
        $data = [
            'title'   => "Error!",
            'message' => "User not updated because there are some error.",
            'type'    => 'error',
        ];
        echo json_encode($data);
    }
} elseif($ajaxType == 'price_level_view') {
    //Item category view Query
    $request = 0;
    if(isset($_POST['request'])){
        $request = $_POST['request'];
    }
    if($request == 1){
        $id = $_POST['price_level'];
        $stmt = mysqli_query($conDB, "SELECT * FROM `menu_category` WHERE `cate_id`='$id' AND `status`='1' ORDER BY `name_eng` REGEXP '^[^A-Za-z]' ASC, `name_eng` ");
        while($row = mysqli_fetch_assoc($stmt)) {
            echo "<option value='$row[id]'>$row[name_eng]</option>";
        }
    }
} elseif($ajaxType == 'category_type_view') {
    // Item category type view Query
    $stmt = mysqli_query($conDB, "SELECT * FROM `category_type` ORDER BY `type` REGEXP '^[^A-Za-z]' ASC, `type` ");
    while($row = mysqli_fetch_assoc($stmt)) {
        $type[] = $row;
    }
    $data = [
        'data'      => $type,
        'status'    => 200
    ]; 
    echo json_encode($data);
} elseif($ajaxType == 'category_type_add') {
    $name_eng = $_POST['name_eng'];
    $name_ar = mysqli_real_escape_string($conDB, $_POST['name_ar']);
    $desc_eng = $_POST['desc_eng'];
    $desc_ar = mysqli_real_escape_string($conDB, $_POST['desc_ar']);
    $cate_id = $_POST['category_type'];
    $sql = "INSERT INTO `menu_category` (`name_eng`, `name_ar`,`desc_eng`,`desc_ar`,`cate_id`) VALUES ('".$name_eng."','".$name_ar."','".$desc_eng."','".$desc_ar."','".$cate_id."')";
    if(mysqli_query($conDB, $sql)){
        $data = [
            'title'   => "Added!",
            'message' => "This category has been registered successfully.",
            'type'    => 'success',
        ];
        echo json_encode($data);
    } else {
        $data = [
            'title'   => "Error!",
            'message' => "Record not updated because there are some error.",
            'type'    => 'error',
        ];
        echo json_encode($data);
    }
} elseif($ajaxType == 'category_type_edit'){
    $name_eng_up = $_POST['name_eng'];
    $name_ar_up = mysqli_real_escape_string($conDB, $_POST['name_ar']);
    $desc_eng_up = $_POST['desc_eng'];
    $desc_ar_up = mysqli_real_escape_string($conDB, $_POST['desc_ar']);
    $status_up = $_POST['status'];
    $category_type = $_POST['category_type'];
    $sql = "UPDATE `menu_category` SET `name_eng`='".$name_eng_up."', `name_ar`='".$name_ar_up."', `desc_eng`='".$desc_eng_up."', `desc_ar`='".$desc_ar_up."', `status`='".$status_up."', `cate_id`='".$category_type."' WHERE `id`='".$_POST['smid']."' ";
    if(mysqli_query($conDB, $sql)){
        $data = [
            'title'   => "Updated!",
            'message' => "This category has been update successfully.",
            'type'    => 'success',
        ];
        echo json_encode($data);
    } else {
        $data = [
            'title'   => "Error!",
            'message' => "Record not updated because there are some error.",
            'type'    => 'error',
        ];
        echo json_encode($data);
    }
}

?>