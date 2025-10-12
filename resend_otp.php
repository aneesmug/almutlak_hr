<?php
// Set response header to JSON
header('Content-Type: application/json');

// Include dependencies
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/translation_functions.php'; // Added for email translation
require_once __DIR__ . '/includes/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (session_status() == PHP_SESSION_NONE) { session_start(); }

// Initialize response
$resp = ['status' => 'error', 'message' => 'An unknown error occurred.'];

try {
    // 1. Security and Session Check
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }
    if (!isset($_SESSION['otp_verification'], $_SESSION['otp_verification']['user_id'])) {
        throw new Exception('Authentication session not found. Please log in again.');
    }

    $user_id = $_SESSION['otp_verification']['user_id'];

    // 2. Fetch User's Email from Database
    $query = "SELECT `fullname`, `email` FROM `admin_login` WHERE `id_iqama`=? LIMIT 1";
    $stmt = mysqli_prepare($conDB, $query);
    mysqli_stmt_bind_param($stmt, "s", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if (!$user || empty($user['email'])) {
        throw new Exception('Could not find a registered email for this user.');
    }

    // 3. Generate and Store New OTP
    $otp = sprintf("%'.06d", mt_rand(0, 999999));
    $otp_hash = password_hash($otp, PASSWORD_DEFAULT);
    $otp_expiry = date('Y-m-d H:i:s', time() + 120); // New 2-minute expiration

    $update_sql = "UPDATE `admin_login` SET `otp`=?, `otp_expiration`=? WHERE `id_iqama`=?";
    $update_stmt = mysqli_prepare($conDB, $update_sql);
    mysqli_stmt_bind_param($update_stmt, "sss", $otp_hash, $otp_expiry, $user_id);
    if (!mysqli_stmt_execute($update_stmt)) {
        throw new Exception('Failed to update OTP in the database.');
    }

    // 4. Send the New OTP via Email
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.office365.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'noreply@almutlak.com';
    $mail->Password = 'HO@66887';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->CharSet = 'UTF-8';
    $mail->setFrom("noreply@almutlak.com", "Al Mutlak System");
    $mail->addAddress($user['email'], $user['fullname']);
    $mail->isHTML(true);
    $mail->Subject = __("email_otp_subject");

    if (file_exists(__DIR__ . '/includes/PHPMailerMaster/otp_email_template_dark.html')) {
        $emailBody = file_get_contents(__DIR__ . '/includes/PHPMailerMaster/otp_email_template_dark.html');

        // Define variables
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $domainName = $_SERVER['HTTP_HOST'];
        $logoUrl = $protocol . $domainName . '/assets/images/logo.png'; // Use white logo for dark theme
        
        // Replace placeholders with variables
        $replacements = [
            '{{LOGO_URL}}' => $logoUrl,
            '{{USER_FULLNAME}}' => htmlspecialchars($user['fullname']),
            '{{OTP_CODE}}' => $otp,
            '{{EMAIL_HEADING}}' => __('email_heading'),
            '{{EMAIL_GREETING}}' => __('email_greeting'),
            '{{EMAIL_VERIFICATION_CODE_LABEL}}' => __('email_verification_code_label'),
            // '{{VERIFICATION_CODE}}' => __('verification_code'),
            '{{EMAIL_EXPIRATION_NOTICE}}' => __('email_expiration_notice'),
            '{{EMAIL_SECURITY_NOTICE}}' => __('email_security_notice'),
        ];
        $emailBody = str_replace(array_keys($replacements), array_values($replacements), $emailBody);
        // Add schema.org microdata for Outlook's "Copy Code" feature
        $schemaMarkup = '<div style="display:none; max-height:0; overflow:hidden;"><div itemscope itemtype="http://schema.org/EmailMessage"><div itemprop="potentialAction" itemscope itemtype="http://schema.org/ViewAction"><div itemprop="result" itemscope itemtype="http://schema.org/OneTimeCode"><meta itemprop="code" content="' . $otp . '"/></div></div></div></div>';
        $mail->Body = $emailBody . $schemaMarkup;
    } else {
        $mail->Body = "Your one-time password (OTP) is: <strong>$otp</strong>";
    }
    
    $mail->send();

    // 5. Update the session expiration time
    $_SESSION['otp_verification']['expires'] = time() + 120;
    
    $resp['status'] = 'success';
    $resp['message'] = __('a_new_otp_has_been_sent_to_your_email');

} catch (Exception $e) {
    // Catch any exception and set it as the error message
    $resp['message'] = $e->getMessage();
    // Log detailed mailer errors for debugging, but don't show them to the user
    if ($e instanceof \PHPMailer\PHPMailer\Exception) {
        error_log("Mailer Error on Resend: " . $e->getMessage());
        $resp['message'] = 'Failed to send email. Please try again later.';
    }
}

// Return the final JSON response
echo json_encode($resp);
exit();
