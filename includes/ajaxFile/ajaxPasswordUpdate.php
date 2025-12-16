<?php
	require_once __DIR__ . '/../../includes/db.php';
	require_once __DIR__ . '/../../includes/session_check.php';
	if(isset($_POST['ajax']) && isset($_POST['password'])){
	    $user_id = intval($_POST['id']);
	    
	    // Fetch old user data for logging
	    $old_user_result = mysqli_query($conDB, "SELECT id, email FROM `admin_login` WHERE `id`='".$user_id."'");
	    $old_user = mysqli_fetch_assoc($old_user_result);
	    
	    $sqlpass = "UPDATE `admin_login` SET `password`='".sha1(md5($_POST['password']))."', `bk_password`='".$_POST['password']."', `updated_at`='".date('Y-m-d H:i:s')."' WHERE `id`='".$user_id."' ";
	    if(mysqli_query($conDB, $sqlpass)){
	        // Log password update
	        ActivityLogger::logUpdate('User', 'ajaxPasswordUpdate.php', $user_id, $old_user ?? [], [
	            'password' => '[REDACTED]'
	        ], "Updated password for user ID: {$user_id}", 'admin_login');
	        
	      $data = [
		        'title'    	=> "Updated!",
		        'message'   => "This user has been update successfully.",
		        'type'  	=> 'success',
	      	];
	        echo json_encode($data);
	    } else {
	        $data = [
		        'title'    	=> "Error!",
		        'message'   => "Password not updated because there are some error.",
		        'type'  	=> 'error',
	      	];
	        echo json_encode($data);
	    }
	}

?>