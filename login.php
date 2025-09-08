<?php
/********************************************************************************
 * MODIFICATION SUMMARY
 * - Restored the full login logic to handle three cases:
 * 1. New employee registration (setting a password).
 * 2. Existing employee login (verifying a password).
 * 3. Admin/HR/GM login (sending an OTP).
 * - For the OTP login flow, it now checks for the 'remember_me' checkbox.
 * - If 'remember_me' is checked, a flag is set in the session:
 * `$_SESSION['otp_verification']['remember_me'] = true;`
 * - This flag will be used by `login_verification.php` to create the cookie
 * AFTER the OTP is successfully verified.
 * - FIXED: Language is now determined and saved to the session BEFORE redirecting
 * to the OTP page, preventing translation race conditions.
 * - ADDED: session_write_close() to ensure data is saved before the redirect.
 * - UPDATED: Email logic now populates a fully dynamic and translatable HTML
 * template, including the company logo URL.
 * - ROBUSTNESS: Made the language loading for emails more robust to prevent
 * intermittent language inconsistencies.
 ********************************************************************************/
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/translation_functions.php'; // Added for email translation
require_once __DIR__ . '/includes/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (session_status() == PHP_SESSION_NONE) { session_start(); }

$resp = ['status' => 'error', 'message' => '', 'redirect_url' => './index.php'];

try {
    $id_iqama = $_POST['id_iqama'] ?? '';
    if (empty($id_iqama)) { throw new Exception('ID or Iqama number is required.'); }
    $id_iqama = mysqli_real_escape_string($conDB, filter_var($id_iqama, FILTER_SANITIZE_NUMBER_INT));

    // === FLOW 1: NEW USER REGISTRATION ===
    if (!empty($_POST['new_password'])) {
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if ($new_password !== $confirm_password) { throw new Exception('Passwords do not match.'); }
        if (strlen($new_password) < 5) { throw new Exception('Password must be at least 5 characters long.'); }

        $emp_check_query = "SELECT * FROM `employees` WHERE `iqama` = ? LIMIT 1";
        $emp_stmt = mysqli_prepare($conDB, $emp_check_query);
        mysqli_stmt_bind_param($emp_stmt, "s", $id_iqama);
        mysqli_stmt_execute($emp_stmt);
        $employee_data = mysqli_fetch_assoc(mysqli_stmt_get_result($emp_stmt));

        if (!$employee_data) { throw new Exception('Invalid registration attempt. Employee not found.'); }
        
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $emp_id = $employee_data['emp_id'] ?? null;
        $fullname = $employee_data['fullname'] ?? 'N/A';
        $email = $employee_data['email'] ?? null;
        $dept = $employee_data['dept'] ?? null;
        
        $insert_query = "INSERT INTO `admin_login` (emp_id, id_iqama, fullname, user_type, dept, email, password, status, created_at, updated_at) VALUES (?, ?, ?, 'employee', ?, ?, ?, 1, NOW(), NOW())";
        $insert_stmt = mysqli_prepare($conDB, $insert_query);
        mysqli_stmt_bind_param($insert_stmt, "ssssss", $emp_id, $id_iqama, $fullname, $dept, $email, $hashed_password);
        
        if (!mysqli_stmt_execute($insert_stmt)) { throw new Exception('Database error: Could not create user account.'); }
        
        session_regenerate_id(true);
        $_SESSION['auth_user'] = [
            'user_id' => $id_iqama, 'fullname' => $fullname, 'email' => $email, 'user_type' => 'employee', 'dept' => $dept
        ];
        $resp['status'] = 'success';
        $resp['redirect_url'] = './dashboard.php';


    } else {
        // === FLOW 2: EXISTING USER LOGIN ===
        $query = "SELECT * FROM `admin_login` WHERE `id_iqama`=? LIMIT 1";
        $stmt = mysqli_prepare($conDB, $query);
        mysqli_stmt_bind_param($stmt, "s", $id_iqama);
        mysqli_stmt_execute($stmt);
        $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if (!$user) { throw new Exception('This ID is not registered. Please contact support.'); }
        if ($user['status'] != 1) { throw new Exception('Your account is inactive. Please contact support.'); }

        $admin_roles = ['administrator', 'hr', 'gm', 'dept_user', 'assistant'];
        
        if (in_array($user['user_type'], $admin_roles)) {
            // ---> START: LANGUAGE LOADING <---
            // Determine user's preferred language for the email and the next page.
            $user_lang = 'en'; // Default
            $preferred_lang = isset($user['preferred_language']) ? trim($user['preferred_language']) : '';
            if (!empty($preferred_lang) && in_array($preferred_lang, ['en', 'ar'])) {
                $user_lang = $preferred_lang;
            }

            // Load the appropriate translations.
            load_language($user_lang); 
            $_SESSION['lang'] = $user_lang;
            // ---> END: LANGUAGE LOADING <---

            // Non-Employee OTP Login
            if (empty($user['email'])) { throw new Exception('Cannot send OTP. No email is registered for this account.'); }

            $otp = sprintf("%'.06d", mt_rand(0, 999999));
            $otp_hash = password_hash($otp, PASSWORD_DEFAULT);
            $otp_expiry = date('Y-m-d H:i:s', time() + 120);

            $update_sql = "UPDATE `admin_login` SET `otp`=?, `otp_expiration`=? WHERE `id_iqama`=?";
            $update_stmt = mysqli_prepare($conDB, $update_sql);
            mysqli_stmt_bind_param($update_stmt, "sss", $otp_hash, $otp_expiry, $user['id_iqama']);
            mysqli_stmt_execute($update_stmt);

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
            $mail->Subject = __("email_otp_subject"); // Use translation
            
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

            $_SESSION['otp_verification'] = ['user_id' => $user['id_iqama'], 'expires' => time() + 120];
            
            if (isset($_POST['remember_me'])) {
                $_SESSION['otp_verification']['remember_me'] = true;
            }
            
            session_write_close(); // Force session save before redirecting.

            $resp['status'] = 'success';
            $resp['redirect_url'] = './login_verification.php';

        } else { // Assumes 'employee' user type
            $password_input = $_POST['password'] ?? '';
            if (empty($password_input)) { throw new Exception('Password is required.'); }
            if (!password_verify($password_input, $user['password'])) { throw new Exception('Invalid password provided.'); }

            session_regenerate_id(true);
            $_SESSION['auth_user'] = [
                'user_id' => $user['id_iqama'], 'fullname' => $user['fullname'], 'email' => $user['email'], 'user_type' => $user['user_type'], 'dept' => $user['dept']
            ];
            $resp['status'] = 'success';
            $resp['redirect_url'] = './dashboard.php';
        }
    }
} catch (Exception $e) {
    $resp['message'] = $e->getMessage();
    $_SESSION['error_message'] = $e->getMessage();
}

// Final response
if (!empty($resp['redirect_url'])) {
    header("Location: " . $resp['redirect_url']);
} else {
    header("Location: ./index.php"); // Fallback
}
exit();