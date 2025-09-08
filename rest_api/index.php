<?php
	header('Content-Type: application/json');
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Method: GET');
	header('WWW-Authenticate: Basic');
	header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Request-With');
	$localhost = "localhost";
	$db_user = "mochachino_user";
	$db_pass = "hain6539306";
	$db_name = "mochachino_db";
	$conDB = mysqli_connect( $localhost , $db_user , $db_pass , $db_name ) or die('Error: Could not connect to database.');
	$conDB->set_charset("UTF8");
	//$data = json_decode(file_get_contents("PHP://input"), true);
	//$emp_id = $data['emp_id'];
	$key = mysqli_real_escape_string($conDB, $_SERVER['PHP_AUTH_PW']);
	$checkkey = mysqli_query($conDB, "SELECT * FROM `license_key` WHERE `serial_key` = '".$key."' AND `status`=1");
	$auth = mysqli_fetch_assoc($checkkey);
	if ($_SERVER['PHP_AUTH_USER'] == "".$auth['db_name']."" && ($_SERVER['PHP_AUTH_PW'] == "".$auth['serial_key']."" )) {
		if ($auth['expiry'] > date('Y-m-d')) {
			if (mysqli_num_rows($checkkey)>0) {
				response(getData());
				/*$result = mysqli_query($conDB,"SELECT * FROM `tables`");
				if (mysqli_num_rows($result)>0) {
					// $output = mysqli_fetch_array($result, MYSQLI_ASSOC);
					while($row = mysqli_fetch_array($result)){
						$data[]=$row;
					}
					echo json_encode($data);
				} else {
					echo json_encode(array('message' => 'No records.', 'status' => false));
				}*/
			} else {
				echo json_encode(array('message' => 'key not valid.', 'status' => false));
			}
		} else {
			echo json_encode(['status'=>false, 'data'=>'Your license key is expired or not valid.']);
		}
	} else {
		echo json_encode(['status'=>false, 'data'=>'api key not found']);
	}

	function getData(){
		global $conDB;
		// $query = mysqli_query($conDB,"SELECT * FROM `tables` WHERE `id` = '".$id."' ");
		$query = mysqli_query($conDB,"SELECT * FROM `tables`");
		while($row = mysqli_fetch_array($query, MYSQLI_ASSOC)){
			$data[]=$row['tbl_name'];
		}
		return $data;
	}
	function response($data){
		echo json_encode($data);
		// echo print_r($data);
	}
	
	/*$request = $_SERVER['REQUEST_METHOD'];
	$data = array();
	//print($request);
	switch ($request) {
		case 'GET':
			response(getData());
			break;
		default:
			// code...
			break;
	}
	function getData(){
		global $conDB;
		if (@$_GET['id']) {
			@$id = $_GET['id'];
			$where = "WHERE id=".$id;
		}else{
			$id = 0;
			$where = "";
		}
		$query = mysqli_query($conDB,"SELECT * FROM `admin_login` ".$where);
		while($row = mysqli_fetch_array($query)){
			$data[]=array('id' => $row['id'],'emp_id' => $row['emp_id'],'fullname' => $row['fullname'],'mobile' => $row['mobile'], );
		}
		return $data;
	}
	function response($data){
		echo json_encode($data);
	}*/
?>