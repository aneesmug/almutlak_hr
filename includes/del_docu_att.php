<?php 
	require_once __DIR__ . '/db.php';
	$sql=mysqli_query($conDB, "SELECT * FROM `smt_attachment` WHERE `id`='".$_GET['id']."' AND `inv_no` = '".$_GET['inv_no']."' ");
		while($row = mysqli_fetch_array($sql)){
			$attachment = $row['attachment'];
		}
	echo "./assets/assets/smt_attachment/".$attachment;

	if(unlink("../assets/assets/smt_attachment/".$attachment)) echo "Deleted file ";
	$mysql="DELETE FROM `smt_attachment` WHERE `id`='".$_GET['id']."' AND `inv_no` = '".$_GET['inv_no']."' ";
	mysqli_query($conDB, $mysql);
	header("Location: ../open_request.php?id=$_GET[inv_no]");
?>