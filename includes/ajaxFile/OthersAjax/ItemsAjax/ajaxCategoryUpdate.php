<?php
    require_once __DIR__ . '/../../includes/db.php';

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
?>