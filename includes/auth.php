<?php
if(session_status() === PHP_SESSION_NONE)
session_start();
$link = $_SERVER['PHP_SELF'];
if(strpos($link,'./login_verification.php') && !isset($_SESSION['otp_verify_user_id'])){
    echo "<script>location.replace('./login.php');</script>";
} if(strpos($link,'login.php') > -1 && isset($_SESSION['user_login'])){
    echo "<script>location.replace('./dashboard.php');</script>";
} if(isset($_SESSION['user_login']) && basename($link) == 'index.php') {
    echo "<script>location.replace('./dashboard.php');</script>";
}

/*if(isset($_COOKIE['user']) && $_COOKIE['user'] != ''){
    $username = $_COOKIE['user'];
} else if(isset($_SESSION['user']) && $_SESSION['user'] !=''){
    $username = $_SESSION['user'];
} else {
    header("Location: ./index.php");
}
*/
include("./includes/menu_active_class.php");
include("./includes/custom_functions.php");