<?php
require_once 'db.php';

function verifyCredentials($id_iqama, $password) {
    global $conDB;
    
    $query = "SELECT * FROM admin_login WHERE id_iqama = ?";
    $stmt = mysqli_prepare($conDB, $query);
    mysqli_stmt_bind_param($stmt, "s", $id_iqama);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($user = mysqli_fetch_assoc($result)) {
        if (password_verify($password, $user['password'])) {
            return $user;
        }
    }
    return false;
}

function generateAndStoreOtp($userId) {
    global $conDB;
    
    $otp = rand(100000, 999999); // 6-digit OTP
    $expiry = date('Y-m-d H:i:s', time() + 300); // 5 minutes expiry
    
    $query = "UPDATE admin_login SET bk_otp = ?, otp_expiry = ? WHERE id_iqama = ?";
    $stmt = mysqli_prepare($conDB, $query);
    mysqli_stmt_bind_param($stmt, "sss", $otp, $expiry, $userId);
    mysqli_stmt_execute($stmt);
    
    return $otp;
}

function verifyOtp($userId, $submittedOtp) {
    global $conDB;
    
    $query = "SELECT bk_otp, otp_expiry FROM admin_login WHERE id_iqama = ?";
    $stmt = mysqli_prepare($conDB, $query);
    mysqli_stmt_bind_param($stmt, "s", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        if ($row['bk_otp'] === $submittedOtp && 
            strtotime($row['otp_expiry']) > time()) {
            
            // Clear OTP after verification
            $update = "UPDATE admin_login SET bk_otp = NULL, otp_expiry = NULL WHERE id_iqama = ?";
            $stmt = mysqli_prepare($conDB, $update);
            mysqli_stmt_bind_param($stmt, "s", $userId);
            mysqli_stmt_execute($stmt);
            
            return true;
        }
    }
    return false;
}

function requireAuth() {
    if (!isset($_SESSION['user']) || !isset($_SESSION['otp_verified']) || !$_SESSION['otp_verified']) {
        // Clear any partial auth data
        unset($_SESSION['pre_otp_auth'], $_SESSION['otp_required'],
              $_SESSION['otp_user_id'], $_SESSION['pending_auth']);
        
        header("Location: index.php");
        exit();
    }
}
?>