<?php
	require_once __DIR__ . '/../../includes/db.php';
    
    $maker_name = $_POST['maker_name'];
    $maker_model = $_POST['maker_model'];
    $made_year = $_POST['made_year'];
    $plate_no = strtoupper($_POST['plate_no']);
    $type = $_POST['type'];
    $remarks = $_POST['remarks'];
    $status = $_POST['status'];

    $sql = "UPDATE `cars` SET `maker_name`='".$maker_name."', `model`='".$maker_model."', `made_year`='".$made_year."', `plate_no`='".$plate_no."', `type`='".$type."', `remarks`='".$remarks."', `status`='".$status."' WHERE `id`='".$_POST['carid']."' ";
    if(mysqli_query($conDB, $sql)){
    	$data = [
    		'title'   => "Updated!",
    		'message' => "This record has been update successfully.",
    		'type' 	  => 'success',
    	];
        echo json_encode($data);
    } else {
        $data = [
    		'title'   => "Error!",
    		'message' => "Record not updated because there are some error.",
    		'type' 	  => 'error',
    	];
        echo json_encode($data);
    }

?>