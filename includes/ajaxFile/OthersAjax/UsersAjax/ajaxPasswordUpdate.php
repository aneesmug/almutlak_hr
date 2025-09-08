<?php
	require_once __DIR__ . '/../../includes/db.php';
	if(isset($_POST['ajax']) && isset($_POST['password'])){
	    $sqlpass = "UPDATE `admin_login` SET `password`='".sha1(md5($_POST['password']))."', `bk_password`='".$_POST['password']."', `updated_at`='".date('Y-m-d H:i:s')."' WHERE `id`='".$_POST['id']."' ";
	    if(mysqli_query($conDB, $sqlpass)){
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