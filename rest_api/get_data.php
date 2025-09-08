<?php

// http://192.168.2.50:88/mochasys2022/system/rest_api/index.php?emp_id=2
$apiKey = "f4ebae-c62cdf-920748-1ba956-583c33";
$usrid = "mochachino_db";
$url = "http://sys.mochachino.local:88/rest_api/index.php";
$ch = curl_init();
curl_setopt($ch, CURLOPT_USERPWD, $usrid . ":" . $apiKey);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_URL,$url);
$result=curl_exec($ch);
$apitbl=json_decode($result, true);
echo "<pre>";
print_r($apitbl);
exit;
function tbl_name($index, $array){
	if (array_key_exists($index, $array)) {
		return $array[$index];
	}
}

echo tbl_name(50,$apitbl);
exit;
// echo $key = implode('-', str_split(substr(strtolower(md5(microtime().rand(1000, 9999))), 0, 30), 6));
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<!-- Required meta tags -->
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
		<title>Page Title</title>
		<!-- Bootstrap CSS -->
		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
	</head>
	<body>
		<h2>Name: <span id="fullname"></span></h1>

		<!-- Optional JavaScript -->
		<!-- jQuery first, then Popper.js, then Bootstrap JS -->
		<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
		<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
		<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.min.js"></script>

		<!-- <script type="text/javascript">
			$(document).ready(function(){
				function loadTable(){
					$.ajax({
						url: 'http://192.168.2.50:88/mochasys2022/system/rest_api/index.php?emp_id=152',
						type: "GET",
						dataType: "JSON",
						success: function(ref) {
							if(ref.status !== false){
								$('#fullname').html(ref.fullname);
							}
						}
					});
				}
				loadTable();
			})
		</script> -->
	</body>
</html>