<?php 
	require_once __DIR__ . '/../../includes/db.php';
    $id=$_POST['id'];
    $sql = "DELETE FROM `admin_login` WHERE id=$id ";
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