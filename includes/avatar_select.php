<?php 

	$sql=mysqli_query($conDB, "SELECT 
	`employees`.*, `employees`.`name` AS `efullname`, `employees`.`avatar` AS `eavatar`, `admin_login`.* , `department`.`dep_nme` AS `usrdeptnme`
	FROM `employees` 
	LEFT JOIN `admin_login` ON `employees`.`emp_id` = `admin_login`.`emp_id` 
	LEFT JOIN `department` ON `department`.`id` = `admin_login`.`dept` 
	WHERE `admin_login`.`id_iqama`='".$username."'");

	// $rowAarray;

	while($row = mysqli_fetch_array($sql)){
			$id=$row['id'];
			$empid=$row['emp_id'];
			$fname=$row['efullname'];
			$mobile=$row['mobile'];
			$emailusr=$row['email'];
			$email_pass=$row['email_pass'];
			$user_type=$row['user_type'];
			$emptypeget=$row['emptype'];
			$emp_type=$row['emp_type'];
			$user_dept=$row['dept'];
			$avatar=$row['eavatar'];
			$usrdeptnme=$row['usrdeptnme'];

			$rowAarray[] = $row;
		}
		
		$userwel = parseName($fname);
		
		if($user_type == "administrator"){
			$usracc = "Administrator";
		} elseif ($user_type == "hr"){
			$usracc = "Human Resource";
		} elseif ($user_type == "dephead"){
			$usracc = "Department Head";
		} elseif($user_type == "user"){
			$usracc = "Employee";
		}

		$access1 = "administrator";
		$access2 = "hr";

		$_SESSION['verify_user_type'] = $user_type;
		$_SESSION['user_type'] 	= $user_type;
		$_SESSION['user_dept'] 	= $user_dept;
		$_SESSION['empid'] 		= $empid;

?>