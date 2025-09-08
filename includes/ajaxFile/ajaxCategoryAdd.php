<?php
    require_once __DIR__ . '/../../includes/db.php';

    /*$data = [
        'title'   => $_POST,
    ];
    echo json_encode($data);*/

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
?>