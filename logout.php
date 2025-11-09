<?php
if(session_status() === PHP_SESSION_NONE)
    session_start();

require_once __DIR__ . '/includes/db.php';

// Clear remember_me token from database if user is logged in
if (isset($_SESSION['auth_user']) && isset($_SESSION['auth_user']['user_id'])) {
    $user_id = $_SESSION['auth_user']['user_id'];
    $update_sql = "UPDATE `admin_login` SET `remember_token`=NULL, `remember_token_expiry`=NULL WHERE `id_iqama`=?";
    $stmt = mysqli_prepare($conDB, $update_sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

// Destroy all session variables
foreach($_SESSION as $k => $v){
    unset($_SESSION[$k]);
}

// Clear all cookies
setcookie('user', '', time() - 3600, '/');
setcookie('remember_me', '', time() - 3600, '/');

session_destroy();

echo "<script>location.replace('./index.php');</script>";