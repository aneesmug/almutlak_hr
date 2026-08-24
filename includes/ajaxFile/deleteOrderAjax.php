<?php 
	require_once __DIR__ . '/../../includes/db.php';
	require_once __DIR__ . '/../../includes/session_check.php';
	
	$order_id = $_POST['id'];
	
	// Fetch old order data for logging
	$old_result = mysqli_query($conDB, "SELECT * FROM cart_order WHERE order_id = '{$order_id}'");
	$old_order = mysqli_fetch_assoc($old_result);
	
	// $sql="DELETE FROM `cart_order` WHERE `order_id` = '".$order_id."' ";
    $sql="UPDATE `cart_order` SET `deleted`='1' WHERE `order_id` = '".$order_id."' ";
    
	if (mysqli_query($conDB, $sql)) {
		// Log order deletion
		ActivityLogger::logDelete('Orders', 'deleteOrderAjax.php', $order_id, $old_order ?? [], "Deleted order: {$order_id}", 'cart_order');
		
        $data = [
            'title'   => "Deleted!",
    		'message' => "Record Deleted Successfully ...",
    		'type' 	  => 'success',
    	];
    	echo json_encode($data);
    }else {
    	$data = [
    		'title'   => "Error!",
    		'message' => "Unable to delete this record ...",
    		'type' 	  => 'error',
    	];
        echo json_encode($data);
    }
?>