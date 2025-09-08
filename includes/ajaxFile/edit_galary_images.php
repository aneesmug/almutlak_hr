<?php
	require_once __DIR__ . '/../../includes/db.php';
    $details_up = $_POST['details'];
    $status_up = $_POST['status'];
    $sql = "UPDATE `gallery` SET `details`='".$details_up."', `status`='".$status_up."', `updated_at`='".date('Y-m-d H:i:s')."' WHERE `id`='".$_POST['id']."' ";
    if(mysqli_query($conDB, $sql)){
    	$data = [
    		'title'   => "Updated!",
    		'message' => "This item has been update successfully.",
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