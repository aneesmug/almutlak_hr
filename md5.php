<?php
	
	echo password_hash(12345, PASSWORD_DEFAULT);
	exit;

	$password = "shafer321";
	$password = md5($password);
	echo $password = sha1($password);
?>