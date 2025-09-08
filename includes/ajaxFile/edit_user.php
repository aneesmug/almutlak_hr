<?php
require_once __DIR__ . '/../../includes/db.php';
	require_once __DIR__ . '/../../includes/db.php';

    $username_up = $_POST['username'];

    $fullname_up = $_POST['fullname'];

    $dept_up = $_POST['dept'];

    $user_type_up = $_POST['user_type'];

    $email_up = $_POST['email'];

    $email_pass_up = $_POST['email_pass'];

    $mobile_up = $_POST['mobile'];

    $status_up = $_POST['status'];

    $sql = "UPDATE `admin_login` SET `username`='".$username_up."', `fullname`='".$fullname_up."', `dept`='".$dept_up."', `user_type`='".$user_type_up."', `email`='".$email_up."', `email_pass`='".$email_pass_up."', `mobile`='".$mobile_up."', `status`='".$status_up."', `updated_at`='".date('Y-m-d H:i:s')."' WHERE `id`='".$_POST['id']."' ";

    if(mysqli_query($conDB, $sql)){

    	$data = [

    		'title'   => "Updated!",

    		'message' => "This user has been update successfully.",

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