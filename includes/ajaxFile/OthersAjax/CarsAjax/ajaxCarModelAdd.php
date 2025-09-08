<?php
    require_once __DIR__ . '/../../includes/db.php';

    $mkid = $_POST['maker_name'];
    $maker_model = $_POST['maker_model'];
    $sql = "INSERT INTO `car_model` (`mkid`, `model`) VALUES ('".$mkid."','".$maker_model."')";
    if(mysqli_query($conDB, $sql)){
        $data = [
            'title'   => "Added!",
            'message' => "This type has been registered successfully.",
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