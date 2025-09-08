<?php
	require_once __DIR__ . '/../../includes/db.php';

	$id = mysqli_real_escape_string($conDB, $_POST['id']);
	$status = mysqli_real_escape_string($conDB, $_POST['status']);

	if(isset($_GET['status'])) {
		$sql = "UPDATE `section` SET `status`='".$_GET['status']."' WHERE `id`='".$_GET['id']."'";
		if(mysqli_query($conDB, $sql) === TRUE){
			    $data = [
		    		'title'   => "Updated!",
		    		'message' => "This location status has been added successfully.",
		    		'type' 	  => 'success',
		    	];
		        echo json_encode($data);
		    } else {
		        $data = [
		    		'title'   => "Error!",
		    		'message' => "Not updated because there are some error.",
		    		'type' 	  => 'error',
		    	];
		        echo json_encode($data);
		    }
	}

?>