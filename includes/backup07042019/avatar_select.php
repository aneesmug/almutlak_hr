<?php 



	/*$sql=mysql_query("SELECT * FROM admin_login");

		while($row = mysqlSELECT * FROM `admin_login` WHERE `id_iqama`='

			$avatar = $row['avatar'];

			}*/

		$sql=mysql_query("SELECT * FROM `admin_login` WHERE username='".$username."'");

		while($row = mysql_fetch_array($sql)){

		$id=$row['id'];

		$fname=$row['firstname'];

		$lname=$row['lastname'];

		$mobile=$row['mobile'];

		$email=$row['email'];

		$user_type=$row['user_type'];

		$avatar=$row['avatar'];

		}

		$userwel = "".$fname." ".$lname."";

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



?>