<?php
	header('Content-Type: application/json');
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Method: GET');
	header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Request-With');
	include("./../../system/includes/db.php");

	//$data = json_decode(file_get_contents("PHP://input"), true);
	//$emp_id = $data['emp_id'];

	$request = $_SERVER['REQUEST_METHOD'];
	if ($request == "GET") {
		if (isset($_GET['id'])) {
			echo $licence = getData1($_GET['id']);
		}else{
			echo $licence = getData();	
		}
	} else {
		$data = [
			'status' => "405",
			'message'=> $request. 'METHOD NOT ALLOWED'
		];
		header("HTTP/1.0 405 METHOD NOT ALLOWED");
		echo json_encode($data);
	}

	function error422($message){
		$data = [
			'status' 	=> 422,
			'message'	=> $message
		];
		header("HTTP/1.0 422 UNPROCESSABLE ENTITY");
		return json_encode($data);
		exit();
	}

	function getData(){
		global $conDB;
		$query = mysqli_query($conDB,"SELECT * FROM `license_key`");
		if ($query) {
			if (mysqli_num_rows($query)>0) {
				$result = mysqli_fetch_all($query, MYSQLI_ASSOC);
				$data = [
					'status' => "200",
					'message'=> 'LICENCE LIST ARE FETCHED SUCCESSFULLY',
					'data'	 => $result
				];
				header("HTTP/1.0 200 SUCCESS");
				return json_encode($data);
			} else {
				$data = [
					'status' => "404",
					'message'=> $request. 'METHOD NOT ALLOWED'
				];
				header("HTTP/1.0 404 LICENCE NOT FOUND");
				return json_encode($data);
			}
		} else {
			$data = [
				'status' => "500",
				'message'=> $request. 'METHOD NOT ALLOWED'
			];
			header("HTTP/1.0 500 INTERNAL SERVER ERROR");
			return json_encode($data);
		}
	}

	function getData1($lid){
		global $conDB;
		if ($lid['id'] == null) {
			return error422('enter licence key id');
		}
		$id = mysqli_real_escape_string($conDB, $lid['id']);
		$result = mysqli_query($conDB,"SELECT * FROM `license_key` WHERE `id`= {$id} LIMIT 1");
		if ($result) {
			if (mysqli_num_rows($result) == 1) {
				$res = mysqli_fetch_assoc($result);
				$data = [
					'status' => "200",
					'message'=> $request. 'LICENCE ARE FETCHED SUCCESSFULLY',
					'data'	 => $res
				];
			header("HTTP/1.0 200 SUCCESS");
			return json_encode($data);
			} else {
				$data = [
					'status' => "404",
					'message'=> $request. 'NO LICENCE FOUND'
				];
			header("HTTP/1.0 500 INTERNAL SERVER ERROR");
			return json_encode($data);
			}
		}else{
			$data = [
				'status' => "500",
				'message'=> $request. 'METHOD NOT ALLOWED'
			];
			header("HTTP/1.0 500 INTERNAL SERVER ERROR");
			return json_encode($data);
		}
	}








	/*$result = mysqli_query($conDB,"SELECT * FROM `license_key`");
	if (mysqli_num_rows($result)>0) {
		$output = mysqli_fetch_array($result, MYSQLI_ASSOC);
		echo json_encode($output);
	} else {
		echo json_encode(array('message' => 'No records.', 'status' => false));
	}*/
	
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