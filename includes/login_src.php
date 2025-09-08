<?php	
	include ('db.php');
	
	if(isset($_POST['submit'])){

	$username = $_POST['username'];
	$password = $_POST['password'];
	
	$password = md5($password);
	$password = sha1($password);
	
	$query = mysql_query(" SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."' AND `password`='".$password."' ");
	
	if(mysql_num_rows($query) == 1){
	
	include("./includes/avatar_select.php");
	
		$_SESSION['username'] = $_POST['username'];
		header("Location:dashboard.php");
		
	} else {
		$error = "<div id='error_fill' class='error_fill'>The password you entered is incorrect. Please try again (make sure your caps lock is off).</div>";
	}

}
?>