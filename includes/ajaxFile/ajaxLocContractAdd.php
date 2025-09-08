<?php
	require_once __DIR__ . '/../../includes/db.php';
    
    $owner_name = mysqli_real_escape_string($conDB, $_POST['owner_name']);
    $owner_number = mysqli_real_escape_string($conDB, $_POST['owner_number']);
    $owner_email = mysqli_real_escape_string($conDB, $_POST['owner_email']);
    $contract_no = mysqli_real_escape_string($conDB, $_POST['contract_no']);
    $start_cont_date = mysqli_real_escape_string($conDB, $_POST['start_cont_date']);
    $end_cont_date = mysqli_real_escape_string($conDB, $_POST['end_cont_date']);
    $rent = str_replace(',', '', $_POST['rent']);
    $service = str_replace(',', '', $_POST['service']);
    $elect_prc = str_replace(',', '', $_POST['elect_prc']);
    $water_prc = str_replace(',', '', $_POST['water_prc']);
    $incuranse_prc = str_replace(',', '', $_POST['incuranse_prc']);
    $others = str_replace(',', '', $_POST['others']);


    $sql = "INSERT INTO `location_contract` (`location_id`,`owner_name`, `owner_number`, `owner_email`, `contract_no`, `start_cont_date`, `end_cont_date`, `rent`, `service`, `elect_prc`, `water_prc`, `incuranse_prc`, `others`, `created_at`) VALUES ('".$_POST['locid']."','".$owner_name."', '".$owner_number."', '".$owner_email."','".$contract_no."','".$start_cont_date."','".$end_cont_date."','".$rent."','".$service."','".$elect_prc."','".$water_prc."','".$incuranse_prc."','".$others."', '".date('Y-m-d H:i:s')."')";
        mysqli_query($conDB, $query);

        mysqli_query($conDB, "UPDATE `section` SET `location_owner`='".$owner_name."' WHERE `id`='".$_POST['locid']."' ");


    if(mysqli_query($conDB, $sql)){
    	$data = [
            'title'   => "Added!",
            'message' => "This record has been added successfully.",
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