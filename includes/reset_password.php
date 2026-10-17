<?php
/********************************************************************************
 * MODIFICATION SUMMARY (Oct 17, 2026) - SECURITY HARDENING
 * - FIXED: `update_password` previously accepted any ID/Iqama without the
 *   verification step, letting anyone reset any account's password. It now
 *   requires a successful `verify_employee` for the same ID in this session,
 *   valid for 10 minutes and usable once.
 * - Failed verifications are recorded and locked out (includes/login_security.php).
 * - Minimum password length raised to 8; a reset also clears "remember me" tokens.
 ********************************************************************************
 * MODIFICATION SUMMARY
 * - This is a new file to handle the multi-step password reset process.
 * - It handles two distinct actions submitted via POST:
 * 1. `verify_employee`:
 * - Takes `id_iqama`, `dob`, and `mobile` as input.
 * - Queries the `employees` table to ensure all three details match a
 * single record.
 * - Returns a JSON response indicating success or failure of the verification.
 * 2. `update_password`:
 * - Takes `id_iqama`, `new_password`, and `confirm_password`.
 * - Validates that the passwords match and meet length requirements.
 * - Hashes the new password securely.
 * - Updates the user's password in the `admin_login` table.
 * - Returns a JSON response indicating success or failure.
 ********************************************************************************/
header('Content-Type: application/json');
require_once __DIR__ . '/session_bootstrap.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/login_security.php';

if (session_status() == PHP_SESSION_NONE) { session_start(); }

$response = ['status' => 'error', 'message' => 'Invalid request.'];

if (isset($_POST['action'])) {
    $action = $_POST['action'];
    $id_iqama = preg_replace('/\s+/', '', (string) ($_POST['id_iqama'] ?? ''));

    if (empty($id_iqama)) {
        $response['message'] = 'ID/Iqama is required.';
        echo json_encode($response);
        exit();
    }

    if (!login_sec_csrf_valid()) {
        $response['message'] = login_sec_csrf_message();
        echo json_encode($response);
        exit();
    }

    if (login_sec_is_locked($conDB, $id_iqama)) {
        $response['message'] = login_sec_locked_message();
        echo json_encode($response);
        exit();
    }

    // ACTION 1: Verify Employee Details
    if ($action === 'verify_employee') {
        $dob = trim((string) ($_POST['dob'] ?? ''));
        $mobile = login_sec_normalize_mobile($_POST['mobile'] ?? '');
        unset($_SESSION['pw_reset']);

        if (empty($dob) || empty($mobile)) {
            $response['message'] = 'Date of Birth and Mobile Number are required.';
        } else {
            $query = "SELECT `id` FROM `employees` WHERE BINARY TRIM(`iqama`) = BINARY TRIM(?) AND DATE(`dob`) = ? AND BINARY TRIM(`mobile`) = BINARY TRIM(?) LIMIT 1";
            $stmt = mysqli_prepare($conDB, $query);

            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "sss", $id_iqama, $dob, $mobile);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);

                if ($result && mysqli_num_rows($result) > 0) {
                    session_regenerate_id(true);
                    $_SESSION['pw_reset'] = ['id_iqama' => $id_iqama, 'expires' => time() + 600];
                    $response = ['status' => 'success', 'message' => 'Verification successful. You can now set a new password.'];
                } else {
                    login_sec_record($conDB, $id_iqama, 'reset', false);
                    $response['message'] = 'The details provided do not match our records. Please try again.';
                }

                mysqli_stmt_close($stmt);
            } else {
                $response['message'] = 'Unable to verify your details right now. Please try again later.';
            }
        }
    }
    // ACTION 2: Update Password (only after verify_employee succeeded for the same ID in this session)
    elseif ($action === 'update_password') {
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $reset = $_SESSION['pw_reset'] ?? null;
        $passwordError = login_sec_password_error($new_password);

        if (empty($reset) || !hash_equals((string) $reset['id_iqama'], $id_iqama) || time() > (int) $reset['expires']) {
            unset($_SESSION['pw_reset']);
            login_sec_record($conDB, $id_iqama, 'reset', false);
            $response['message'] = 'Your verification has expired. Please start the password reset again.';
        } elseif (empty($new_password) || empty($confirm_password)) {
            $response['message'] = 'Please provide and confirm your new password.';
        } elseif ($new_password !== $confirm_password) {
            $response['message'] = 'Passwords do not match.';
        } elseif ($passwordError) {
            $response['message'] = $passwordError;
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_query = "UPDATE `admin_login` SET `password` = ?, `remember_token` = NULL, `remember_token_expiry` = NULL WHERE BINARY TRIM(`id_iqama`) = BINARY TRIM(?)";
            $stmt = mysqli_prepare($conDB, $update_query);

            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "ss", $hashed_password, $id_iqama);

                if (mysqli_stmt_execute($stmt)) {
                    unset($_SESSION['pw_reset']);
                    login_sec_record($conDB, $id_iqama, 'reset', true);
                    $response = ['status' => 'success', 'message' => 'Your password has been reset successfully. You can now log in.'];
                } else {
                    $response['message'] = 'An error occurred while updating your password.';
                }

                mysqli_stmt_close($stmt);
            } else {
                $response['message'] = 'Unable to update the password right now. Please try again later.';
            }
        }
    }
}

echo json_encode($response);
exit();
