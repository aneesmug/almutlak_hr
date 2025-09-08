<?php 
	
	require_once __DIR__ . '/db.php';
	mysqli_query($conDB, "DELETE FROM `smt_request_status` WHERE `inv_no` = '".$_GET['id']."' ");
	mysqli_query($conDB, "DELETE FROM `smart_request` WHERE `inv_no` = '".$_GET['id']."' ");
	$sql=mysqli_query($conDB, "SELECT * FROM `smt_attachment` WHERE `inv_no` = '".$_GET['id']."' ");
		while($row = mysqli_fetch_array($sql)){
			$attachment = $row['attachment'];
			if(unlink("../assets/assets/smt_attachment/".$attachment));
		}
	mysqli_query($conDB, "DELETE FROM `smt_attachment` WHERE `inv_no` = '".$_GET['id']."' ");
	echo "<body onload='history.go(-1);'>";


	// echo "./assets/assets/smt_attachment/".$attachment;

	/*$data = mysql_query("SELECT * FROM `smt_attachment` WHERE `inv_no` = '".$_GET['id']."' ") or die(mysql_error());
	$row = mysql_fetch_array($data);

   $files[] = $row['attachment'];
   foreach ($files as $file) {
     	// unlink("../assets/assets/smt_attachment/".$file);
   		echo $file;
		//mysql_query("DELETE FROM `smt_attachment` WHERE `inv_no` = '".$_GET['id']."' ");
   }*/

		//echo "<body onload='history.go(-1);'>";

	/*if(unlink("../assets/assets/smt_attachment/".$attachment)) echo "Deleted file ";*/

	// mysql_query($mysql);
	/************log************/
	// mysql_query("INSERT INTO `activity_log` (`user_editor`,`page`,`pg_id`,`reg_date`) VALUES ('".$_COOKIE['user']."','".$pgname."','".$_GET['tbl']."-".$_GET['id']."','".date("c")."')") or die (mysql_error());
	/************log************/
//	header("Location: ../view_employee.php?id=".$_GET['id']."");
?>