<?php 
	require_once __DIR__ . '/../../includes/db.php';
	require_once __DIR__ . '/../../includes/init.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (isset($_POST['action']) && $_POST['action'] == "signout") {
    
    // 1. Clear the "Remember Me" token from the database if the user is logged in
    if (isset($_SESSION['auth_user']['user_id'])) {
        $user_id = $_SESSION['auth_user']['user_id'];
        
        $update_sql = "UPDATE `admin_login` SET `remember_token`=NULL, `remember_token_expiry`=NULL WHERE `id_iqama`=?";
        $update_stmt = mysqli_prepare($conDB, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "s", $user_id);
        mysqli_stmt_execute($update_stmt);
    }

    // 2. Clear the "Remember Me" cookie from the browser
    if (isset($_COOKIE['remember_me'])) {
        setcookie('remember_me', '', time() - 3600, '/');
    }

    // 3. Unset all session variables
    $_SESSION = array();

    // 4. Destroy the session
    session_destroy();

    $data = [
        'title'   => __('success_title'),
        'message' => __('signout_successfully'),
        'type'    => 'success',
    ];
    echo json_encode($data);

} else {
    $data = [
        'title'   => __('error_title'),
        'message' => __('unable_to_signout_this_action'),
        'type'    => 'error',
    ];
    echo json_encode($data);
}
?>