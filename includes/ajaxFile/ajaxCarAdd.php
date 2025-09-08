<?php
	require_once __DIR__ . '/../../includes/db.php';
    
    $maker_name = $_POST['maker_name'];
    $maker_model = $_POST['maker_model'];
    $made_year = $_POST['made_year'];
    $plate_no = strtoupper($_POST['plate_no']);
    $type = $_POST['type'];
    $remarks = $_POST['remarks'];


    $sql="INSERT INTO `cars` (`maker_name`, `model`, `made_year`, `plate_no`, `type`, `remarks`, `created_at`) VALUES ('".$maker_name."', '".$maker_model."', '".$made_year."', '".$plate_no."', '".$type."', '".$remarks."', '".date('Y-m-d H:i:s')."')";

    if(mysqli_query($conDB, $sql)){
    	$data = [
            'title'   => "Added!",
            'message' => "This car has been added successfully.",
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