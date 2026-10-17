<?php
/********************************************************************************
 * MODIFICATION SUMMARY (Oct 17, 2026) - SECURITY HARDENING
 * - REMOVED hardcoded SMTP credentials; mail now uses the SMTP settings from
 *   app_settings through login_send_otp_mail() (includes/login_security.php).
 * - OTP generated with random_int(); resends limited to one per minute.
 * - CSRF: requests must carry the session token in the X-CSRF-Token header.
 ********************************************************************************/
// Set response header to JSON
header('Content-Type: application/json');

// Include dependencies
require_once __DIR__ . '/includes/session_bootstrap.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/translation_functions.php'; // Added for email translation
require_once __DIR__ . '/includes/login_security.php';
require_once __DIR__ . '/includes/vendor/autoload.php';

use PHPMailer\PHPMailer\Exception;

if (session_status() == PHP_SESSION_NONE) { session_start(); }

// Initialize response
$resp = ['status' => 'error', 'message' => 'An unknown error occurred.'];

try {
    // 1. Security and Session Check
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }
    if (!login_sec_csrf_valid()) {
        throw new Exception(login_sec_csrf_message());
    }
    if (!isset($_SESSION['otp_verification'], $_SESSION['otp_verification']['user_id'])) {
        throw new Exception('Authentication session not found. Please log in again.');
    }

    $user_id = $_SESSION['otp_verification']['user_id'];

    if (login_sec_otp_recently_sent($_SESSION['otp_verification']['expires'] ?? 0)) {
        throw new Exception('Please wait a minute before requesting another code.');
    }

    // 2. Fetch User's Email from Database
    $query = "SELECT a.`fullname`, a.`email`, e.`name` AS employee_name FROM `admin_login` a LEFT JOIN `employees` e ON a.emp_id = e.emp_id WHERE a.`id_iqama`=? LIMIT 1";
    $stmt = mysqli_prepare($conDB, $query);
    mysqli_stmt_bind_param($stmt, "s", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if ($user && !empty($user['employee_name'])) { $user['fullname'] = $user['employee_name']; }

    if (!$user || empty($user['email'])) {
        throw new Exception('Could not find a registered email for this user.');
    }

    // 3. Generate and Store New OTP
    $otp = login_sec_generate_otp();
    $otp_hash = password_hash($otp, PASSWORD_DEFAULT);

    $otp_expiry = date('Y-m-d H:i:s', time() + LOGIN_SEC_OTP_TTL);
    $update_sql = "UPDATE `admin_login` SET `otp`=?, `otp_expiration`=? WHERE `id_iqama`=?";
    $update_stmt = mysqli_prepare($conDB, $update_sql);
    mysqli_stmt_bind_param($update_stmt, "sss", $otp_hash, $otp_expiry, $user_id);
    if (!mysqli_stmt_execute($update_stmt)) {
        throw new Exception('Failed to update OTP in the database.');
    }

    // 4. Send the New OTP via Email
    login_send_otp_mail($conDB, $user['email'], $user['fullname'], $otp);

    // 5. Update the session expiration time
    $_SESSION['otp_verification']['expires'] = time() + LOGIN_SEC_OTP_TTL;

    $resp['status'] = 'success';
    $resp['message'] = __('a_new_otp_has_been_sent_to_your_email');

} catch (Exception $e) {
    $resp['message'] = $e->getMessage();
    if (stripos($e->getMessage(), 'SMTP') !== false || stripos($e->getMessage(), 'mail') !== false) {
        error_log("Mailer Error on Resend: " . $e->getMessage());
        $resp['message'] = 'Failed to send email. Please try again later.';
    }
} catch (\Throwable $e) {
    error_log("Resend OTP error: " . $e->getMessage());
    $resp['message'] = 'Failed to send a new code. Please try again later.';
}

// Return the final JSON response
echo json_encode($resp);
exit();
