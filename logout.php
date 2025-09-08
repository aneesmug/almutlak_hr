<?php
if(session_status() === PHP_SESSION_NONE)
session_start();
//destroys all session variables
foreach($_SESSION as $k =>$v){
    unset($_SESSION[$k]);
}
$res = setcookie('user', '', time() - 3600);
unset($_SESSION['user']);
session_destroy();

echo "<script>location.replace('./index.php');</script>";