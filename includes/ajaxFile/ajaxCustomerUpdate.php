<?php
    require_once __DIR__ . '/../../includes/db.php';

    $full_name_up = strtoupper($_POST['full_name']);
    $acc_no_up = strtoupper($_POST['acc_no']);
    $mobile_up = $_POST['mobile'];
    $sectin_nme_up = $_POST['location'];


    $sql = "UPDATE `customer` SET `full_name`='".$full_name_up."', `acc_no`='".$acc_no_up."', `mobile`='".$mobile_up."', `sectin_nme`='".$sectin_nme_up."' WHERE `id`='".$_POST['id']."' ";
    if(mysqli_query($conDB, $sql)){
        $data = [
            'title'   => "Updated!",
            'message' => "This customer has been update successfully.",
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