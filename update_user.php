<?php
	require_once __DIR__ . '/includes/db.php';

	$id = mysqli_real_escape_string($conDB, $_POST['id']);
	$status = mysqli_real_escape_string($conDB, $_POST['status']);

	if(isset($_POST["status"])) {
	  $sql = "UPDATE `admin_login` SET `status`='".$status."' WHERE id=$id";
	  if(mysqli_query($conDB, $sql) === TRUE){
	    echo "Success Updated.";
	    } else {
	      echo "error" . $sql . "<br>";
	    }
	  }

?>