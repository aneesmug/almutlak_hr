<?php 
	require_once __DIR__ . '/db.php';
	$sql=mysqli_query($conDB, "SELECT * FROM `emp_docu` WHERE `id`='".$_GET['id']."' AND `emp_id` = '".$_GET['emp_id']."' ");
		while($row = mysqli_fetch_array($sql)){
			$attachment = $row['attachment'];
			$pgid = $row['pgid'];
		}
	if(unlink("./.".$attachment)) echo "Deleted file ";
	$mysql="DELETE FROM `emp_docu` WHERE `id`='".$_GET['id']."' AND `emp_id` = '".$_GET['emp_id']."' ";
	mysqli_query($conDB, $mysql);
	header("Location: ../view_employee.php?id=$pgid");
?>