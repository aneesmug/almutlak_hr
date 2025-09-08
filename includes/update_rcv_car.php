<?php
	require_once __DIR__ . '/db.php';
//	include("./admin/includes/TimeStamp.php");
	include("./session_check.php");
/*********session start**********/
	//session_start();
	// set time-out period (in seconds)
//	$mints = 60*60;
//	// check to see if $_SESSION["timeout"] is set
//	if (isset($_SESSION["timeout"])) {
//		// calculate the session's "time to live"
//		$sessionTTL = time() - $_SESSION["timeout"];
//		if ($sessionTTL > 60*$mints) {
//			header("Location:./logout.php");
//		}
//	}
//	if(!isset($_SESSION['cshusername'])){
//		header("Location: ../login");
//		}
//	$_SESSION["timeout"] = time();
/*********session end**********/

//	include("./includes/avatar_select.php");
	//include("./includes/sign_out_session.php");
/*******************/
	mysqli_query($conDB, "UPDATE `cars_drv` SET `status`='no', `rtn_date`='".date("c")."' WHERE `id`='".$_GET['id']."' AND `car_id`='".$_GET['car_id']."' ") or die (mysqli_error());

	/************log************/
	mysqli_query($conDB, "INSERT INTO `activity_log` (`user_editor`,`page`,`pg_id`,`reg_date`) VALUES ('".$_COOKIE['user']."','".$pgname."','".$_GET['id']."','".date("c")."')") or die (mysqli_error());
	/************log************/
	
	header("Location: ../view_car.php?id=".$_GET['id']." ");
/*******************/

?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Login error</title>
</head>
<style>
@keyframes gjPulse {
0% {
    width: 90px;
    height: 90px;
  }
25% {
    width: 105px;
    height: 105px;
  }
50% {
    width: 130px;
    height: 130px;
  }
75% {
    width: 110px;
    height: 110px;
  }
100% {
    width: 90px;
    height: 90px;
  }
}
#gj-counter-box {
  margin: auto;
  position: absolute;
  top: 0;
  left: 0;
  bottom: 0;
  right: 0;
  opacity: 0.5;
  width: 90px;
  height: 90px;
  background-color: #D9534F;
  border-radius: 50%;
  border: 6px solid white;
  visibility: none;
  display: none;
  animation: gjPulse 1s linear infinite;
}
#gj-counter-box:hover {
  opacity: 1;
  cursor: pointer;
}
#gj-counter-num {
  position: relative;
  text-align: center;
  margin: 0px;
  padding: 0px;
  top: 50%;
  transform: translate(0%, -50%);
  color: white;
}
.error-notice {
  margin: 5px 5px; /* Making sure to keep some distance from all side */
}
.oaerror {
  width: 90%; /* Configure it fit in your design  */
  margin: 0 auto; /* Centering Stuff */
  background-color: #FFFFFF; /* Default background */
  padding: 20px;
  border: 1px solid #eee;
  border-left-width: 5px;
  border-radius: 3px;
  margin: 0 auto;
  font-family: 'Open Sans', sans-serif;
  font-size: 16px;
}
.danger {
  border-left-color: #d9534f; /* Left side border color */
  background-color: rgba(217, 83, 79, 0.1); /* Same color as the left border with reduced alpha to 0.1 */
}
.danger strong {
  color:  #d9534f;
}
.warning {
  border-left-color: #f0ad4e;
  background-color: rgba(240, 173, 78, 0.1);
}
.warning strong {
  color: #f0ad4e;
}
.info {
  border-left-color: #5bc0de;
  background-color: rgba(91, 192, 222, 0.1);
}
.info strong {
  color: #5bc0de;
}
.success {
  border-left-color: #2b542c;
  background-color: rgba(43, 84, 44, 0.1);
}
.success strong {
  color: #2b542c;
}
</style>
<script type="text/javascript" src="https://code.jquery.com/jquery-1.11.3.min.js"></script>
<script type="text/javascript">
function gjCountAndRedirect(secounds, url) {
  $('#gj-counter-num').text(secounds);
  $('#gj-counter-box').show();
  var interval = setInterval(function() {
    secounds = secounds - 1;
    $('#gj-counter-num').text(secounds);
    if (secounds == 0) {
      clearInterval(interval);
      	//window.location = url;
      	window.location = '<?php echo $loc_red ?>';
      $('#gj-counter-box').hide();
    }
  }, 1000);
  $('#gj-counter-box').click(function() {
    clearInterval(interval);
    //window.location = url;
	  window.location = '<?php echo $loc_red ?>';
  });
}
// USE EXAMPLE
$(document).ready(function() {
  gjCountAndRedirect(5, document.URL);
});
</script>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
<body>
	<div id="gj-counter-box"><h1 id="gj-counter-num"></h1></div>
	<div class="container">
	<div class="row">
     <br><br>
		<div class="error-notice">
          <?php echo $error ?>
        </div>
	</div>
</div>
</body>
</html>
<!--<script>
function delayRedirect(){
    document.getElementById('delayMsg').innerHTML = 'Please wait you\'ll be redirected after <span id="countDown">5</span> seconds....';
    var count = 5;
    setInterval(function(){
        count--;
        document.getElementById('countDown').innerHTML = count;
        if (count == 0) {
            //window.location = 'https://www.google.com'; 
        }
    },1000);
}
</script>
<body onLoad="delayRedirect()" >
<div id="delayMsg"></div>	
</body>-->