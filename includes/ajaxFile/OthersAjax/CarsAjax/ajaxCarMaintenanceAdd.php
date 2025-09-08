<?php
	require_once __DIR__ . '/../../includes/db.php';

    $car_id = $_POST['cid'];
    $meter = $_POST['meter'];
    $car_user = $_POST['car_user'];
    $type = $_POST['type'];
    $diffmeter = $_POST['diffmeter'];
    $date = $_POST['date'];
    $details = mysqli_real_escape_string($conDB, $_POST['details']);
    $remarks = mysqli_real_escape_string($conDB, $_POST['remarks']);

    $sql="INSERT INTO `cars_maint`(`car_id`, `meter`, `diffmeter`, `date`, `car_user`, `type`, `details`, `remarks`, `created_at`) VALUES ('$car_id','$meter','$diffmeter','$date','$car_user','$type','$details','$remarks','".date('Y-m-d H:i:s')."')";

    if(mysqli_query($conDB, $sql)){
    	$data = [
            'title'   => "Added!",
            'message' => "This car maintenance details has been added successfully.",
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