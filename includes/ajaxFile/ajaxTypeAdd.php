<?php
    require_once __DIR__ . '/../../includes/db.php';

    $type = $_POST['type'];
    $sql = "INSERT INTO `maint_type` (`type`) VALUES ('".$type."')";
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