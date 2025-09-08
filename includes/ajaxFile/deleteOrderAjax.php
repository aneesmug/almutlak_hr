<?php 
	require_once __DIR__ . '/../../includes/db.php';
	// $sql="DELETE FROM `cart_order` WHERE `order_id` = '".$_POST['id']."' ";
    $sql="UPDATE `cart_order` SET `deleted`='1' WHERE `order_id` = '".$_POST['id']."' ";
    
	if (mysqli_query($conDB, $sql)) {
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
    mysqli_close($conDB);
?>