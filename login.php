<?php
/********************************************************************************
 * MODIFICATION SUMMARY (v017-login.php - Oct 17, 2026) - SECURITY HARDENING
 * - REGISTRATION NOW VERIFIES IDENTITY: knowing an ID/Iqama is no longer enough to
 *   claim an account. Normal employees (who usually have no email) must also enter
 *   the Date of Birth and Mobile Number on file before the account is created.
 * - CSRF: the login form carries a per-session token that is checked here.
 * - BRUTE-FORCE PROTECTION: failed attempts are recorded and the ID/IP is locked
 *   after repeated failures (includes/login_security.php).
 * - OTP: generated with random_int(); OTP emails limited to one per minute.
 * - Minimum password length raised to 8 characters.
 * - Session cookie hardening via includes/session_bootstrap.php.
 ********************************************************************************
 * MODIFICATION SUMMARY (v016-login.php - Nov 5, 2025)
 * - DEPARTMENT-BASED ROLE SYSTEM: Updated to work with new user_type values
 * - PASSWORD vs OTP LOGIN:
 *   → user_type = 'employee' → Password authentication ONLY
 *   → All other user_types → OTP authentication (administrator, gm, hr_senior_bp,
 *     hr_operations, hr_supervisor, hr_recruitment, hr_payroll, finance_officer,
 *     auditor, gr_officer, dept_user, etc.)
 * - Removed hardcoded $admin_roles array, now uses simple check: if not 'employee', send OTP
 * - Three login flows:
 *   1. New employee registration (setting a password)
 *   2. Employee login (password verification)
 *   3. Admin/HR/Finance/GM login (OTP to email)
 * - OTP flow respects 'remember_me' checkbox for persistent sessions
 * - Language is determined and saved to session BEFORE OTP redirect
 * - Email templates are fully dynamic and translatable with company logo
 ********************************************************************************/
require_once __DIR__ . '/includes/session_bootstrap.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/translation_functions.php'; // Added for email translation
require_once __DIR__ . '/includes/login_security.php';
require_once __DIR__ . '/includes/microsoft_auth.php';
require_once __DIR__ . '/includes/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


if (session_status() == PHP_SESSION_NONE) { session_start(); }

$resp = ['status' => 'error', 'message' => '', 'redirect_url' => './index.php'];

$noEmailMessage =
    "Authentication System Notice: No registered email address found for this account.<br><br>" .
    "To maintain account security, we are unable to issue a One-Time Password (OTP) at this time.<br>" .
    "Please contact our Support Team for assistance:<br>" .
    "• Email: it@almutlak.com<br>" .
    "• Phone: 01-2644-4426 Ext: 310<br><br>" .
    "Our team will verify your identity and help restore account access.";

try {
    $id_iqama = $_POST['id_iqama'] ?? '';
    if (empty($id_iqama)) { throw new Exception('ID or Iqama number is required.'); }
    $id_iqama = mysqli_real_escape_string($conDB, filter_var($id_iqama, FILTER_SANITIZE_NUMBER_INT));

    if (!login_sec_csrf_valid()) {
        throw new Exception(login_sec_csrf_message());
    }
    if (login_sec_is_locked($conDB, $id_iqama)) {
        throw new Exception(login_sec_locked_message());
    }

    // === FLOW 1: NEW USER REGISTRATION (normal employees; identity verified by DOB + registered mobile) ===
    if (!empty($_POST['new_password'])) {
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'] ?? '';
        $reg_dob = trim((string) ($_POST['reg_dob'] ?? ''));
        $reg_mobile = login_sec_normalize_mobile($_POST['reg_mobile'] ?? '');

        if ($reg_dob === '' || $reg_mobile === '') { throw new Exception('Date of Birth and Mobile Number are required.'); }
        if ($new_password !== $confirm_password) { throw new Exception('Passwords do not match.'); }
        $passwordError = login_sec_password_error($new_password);
        if ($passwordError) { throw new Exception($passwordError); }

        $emp_check_query = "SELECT * FROM `employees` WHERE BINARY TRIM(`iqama`) = BINARY TRIM(?) AND DATE(`dob`) = ? AND BINARY TRIM(`mobile`) = BINARY TRIM(?) LIMIT 1";
        $emp_stmt = mysqli_prepare($conDB, $emp_check_query);
        mysqli_stmt_bind_param($emp_stmt, "sss", $id_iqama, $reg_dob, $reg_mobile);
        mysqli_stmt_execute($emp_stmt);
        $employee_data = mysqli_fetch_assoc(mysqli_stmt_get_result($emp_stmt));

        if (!$employee_data) {
            login_sec_record($conDB, $id_iqama, 'register', false);
            throw new Exception('The details provided do not match our records. Please check your Date of Birth and Mobile Number.');
        }

        // An existing login account must never be overwritten through registration
        $exists_stmt = mysqli_prepare($conDB, "SELECT `id` FROM `admin_login` WHERE `id_iqama` = ? LIMIT 1");
        mysqli_stmt_bind_param($exists_stmt, "s", $id_iqama);
        mysqli_stmt_execute($exists_stmt);
        if (mysqli_fetch_assoc(mysqli_stmt_get_result($exists_stmt))) {
            login_sec_record($conDB, $id_iqama, 'register', false);
            throw new Exception('This ID is already registered. Please log in or use "Forgot Password".');
        }

        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        $emp_id = $employee_data['emp_id'] ?? null;
        $fullname = $employee_data['name'] ?? 'N/A';
        $email = $employee_data['email'] ?? null;
        $dept = $employee_data['dept'] ?? null;

        $insert_query = "INSERT INTO `admin_login` (emp_id, id_iqama, fullname, user_type, dept, email, password, status, created_at, updated_at) VALUES (?, ?, ?, 'employee', ?, ?, ?, 1, NOW(), NOW())";
        $insert_stmt = mysqli_prepare($conDB, $insert_query);
        mysqli_stmt_bind_param($insert_stmt, "ssssss", $emp_id, $id_iqama, $fullname, $dept, $email, $hashed_password);

        if (!mysqli_stmt_execute($insert_stmt)) { throw new Exception('Database error: Could not create user account.'); }
        login_sec_record($conDB, $id_iqama, 'register', true);

        session_regenerate_id(true);
        $_SESSION['auth_user'] = [
            'user_id' => $id_iqama, 'fullname' => $fullname, 'email' => $email, 'user_type' => 'employee', 'dept' => $dept
        ];
        $resp['status'] = 'success';
        $resp['redirect_url'] = './dashboard.php';

    } else {
        // === FLOW 2: EXISTING USER LOGIN ===
        $query = "SELECT a.*, e.name AS employee_name FROM `admin_login` a LEFT JOIN `employees` e ON a.emp_id = e.emp_id WHERE a.`id_iqama`=? LIMIT 1";
        $stmt = mysqli_prepare($conDB, $query);
        mysqli_stmt_bind_param($stmt, "s", $id_iqama);
        mysqli_stmt_execute($stmt);
        $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if (!$user) {
            login_sec_record($conDB, $id_iqama, 'login', false);
            throw new Exception('This ID is not registered. Please contact support.');
        }
        if ($user['status'] != 1) { throw new Exception('Your account is inactive. Please contact support.'); }
        if (!empty($user['employee_name'])) { $user['fullname'] = $user['employee_name']; }

        // Only 'employee' user_type requires password authentication
        // All other user types (administrator, gm, hr_senior_bp, hr_operations, etc.) use OTP
        if ($user['user_type'] !== 'employee') {
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

            // Microsoft Sign-in (App Settings > Integrations): only users assigned 'Microsoft' in
            // App Settings > Microsoft Sign In > Users sign in with Microsoft (password + MFA);
            // everyone else keeps receiving the email OTP.
            if (msauth_enabled($conDB) && msauth_user_assigned($conDB, $user['id_iqama'])) {
                $msSettings = msauth_settings($conDB);
                $msEmailOk = msauth_email_allowed($user['email'] ?? '', $msSettings['domains']);
                $wantsEmailOtp = !empty($_POST['use_email_otp']) && $msSettings['mode'] === 'fallback';

                if (!$msEmailOk && $msSettings['mode'] === 'required') {
                    throw new Exception('Your account email is not a company Microsoft account, so you cannot sign in. Please contact IT support.');
                }
                if ($msEmailOk && !$wantsEmailOtp) {
                    $resp['status'] = 'success';
                    $resp['redirect_url'] = msauth_begin($conDB, $user['id_iqama'], $user['email'], isset($_POST['remember_me']));
                    session_write_close();
                    header("Location: " . $resp['redirect_url']);
                    exit();
                }
            }

            // Non-Employee OTP Login
            if (empty($user['email'])) {
                throw new Exception($noEmailMessage);
            }

            // Limit OTP emails to one per minute per account
            if (login_sec_otp_recently_sent($user['otp_expiration'] ?? null)) {
                throw new Exception('A verification code was just sent. Please wait a minute before requesting another.');
            }

            $otp = login_sec_generate_otp();
            $otp_hash = password_hash($otp, PASSWORD_DEFAULT);
            $otp_expiry = date('Y-m-d H:i:s', time() + LOGIN_SEC_OTP_TTL);

            $update_sql = "UPDATE `admin_login` SET `otp`=?, `otp_expiration`=? WHERE `id_iqama`=?";
            $update_stmt = mysqli_prepare($conDB, $update_sql);
            mysqli_stmt_bind_param($update_stmt, "sss", $otp_hash, $otp_expiry, $user['id_iqama']);
            mysqli_stmt_execute($update_stmt);

            login_send_otp_mail($conDB, $user['email'], $user['fullname'], $otp);

            $_SESSION['otp_verification'] = ['user_id' => $user['id_iqama'], 'expires' => time() + LOGIN_SEC_OTP_TTL];

            if (isset($_POST['remember_me'])) {
                $_SESSION['otp_verification']['remember_me'] = true;
            }

            session_write_close(); // Force session save before redirecting.

            $resp['status'] = 'success';
            $resp['redirect_url'] = './login_verification.php';

        } else { // Assumes 'employee' user type
            $password_input = $_POST['password'] ?? '';
            if (empty($password_input)) { throw new Exception('Password is required.'); }
            if (!password_verify($password_input, $user['password'])) {
                login_sec_record($conDB, $id_iqama, 'login', false);
                throw new Exception('Invalid password provided.');
            }
            login_sec_record($conDB, $id_iqama, 'login', true);

            session_regenerate_id(true);
            $_SESSION['auth_user'] = [
                'user_id' => $user['id_iqama'], 'fullname' => $user['fullname'], 'email' => $user['email'], 'user_type' => $user['user_type'], 'dept' => $user['dept']
            ];
            $resp['status'] = 'success';
            $resp['redirect_url'] = './dashboard.php';

            // Temporary password issued by HR: the employee must choose a new one before anything else
            if (!empty($user['must_change_password'])) {
                $_SESSION['must_change_password'] = true;
                $resp['redirect_url'] = './change_password.php';
            }
        }
    }
} catch (Exception $e) {
    $resp['message'] = $e->getMessage();
    $_SESSION['error_message'] = $e->getMessage();
} catch (\Throwable $e) {
    error_log('[LOGIN] ' . $e->getMessage());
    $resp['message'] = 'Unable to process your request right now. Please try again.';
    $_SESSION['error_message'] = $resp['message'];
}

// Final response
if (!empty($resp['redirect_url'])) {
    header("Location: " . $resp['redirect_url']);
} else {
    header("Location: ./index.php"); // Fallback
}
