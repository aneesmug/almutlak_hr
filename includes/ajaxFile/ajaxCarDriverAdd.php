<?php
	require_once __DIR__ . '/../../includes/db.php';
    
    $id = $_POST['cid'];
    $car_user_up = $_POST['car_user'];
    $rcv_date_up = $_POST['rcv_date'];

    $sql="INSERT INTO `cars_drv` (`car_id`, `car_user`, `rcv_date`, `created_at`) VALUES ('".$id."', '".$car_user_up."', '".$rcv_date_up."', '".date('Y-m-d H:i:s')."')";

    if(mysqli_query($conDB, $sql)){
    	$data = [
            'title'   => "Added!",
            'message' => "This car driver has been added successfully.",
            'type'    => 'success',
        ];
        echo json_encode($data);
    } else {
        $data = [
    		'title'   => "Error!",
    		'message' => "Record not added because there are some error.",
    		'type' 	  => 'error',
    	];
        echo json_encode($data);
    }

?>