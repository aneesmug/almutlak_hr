<?php
	require_once __DIR__ . '/../../includes/db.php';

    $id = $_POST['pid'];
    $cid = $_POST['pcid'];

    $sql = "UPDATE `cars_drv` SET `status`='0', `rtn_date`='".date('Y-m-d H:i:s')."' WHERE `id`='".$id."' AND `car_id`='".$cid."'";
    if(mysqli_query($conDB, $sql)){
    	$data = [
    		'title'   => "Return Updated!",
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