<?php
/********************************************************************************
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
require_once __DIR__ . '/db.php';

$response = ['status' => 'error', 'message' => 'Invalid request.'];

if (isset($_POST['action'])) {
    $action = $_POST['action'];
    $id_iqama = isset($_POST['id_iqama']) ? mysqli_real_escape_string($conDB, $_POST['id_iqama']) : '';

    if (empty($id_iqama)) {
        $response['message'] = 'ID/Iqama is required.';
        echo json_encode($response);
        exit();
    }

    // ACTION 1: Verify Employee Details
    if ($action === 'verify_employee') {
        $dob = $_POST['dob'] ?? '';
        $mobile = $_POST['mobile'] ?? '';

        if (empty($dob) || empty($mobile)) {
            $response['message'] = 'Date of Birth and Mobile Number are required.';
        } else {
            $query = "SELECT `id` FROM `employees` WHERE `iqama` = ? AND `dob` = ? AND `mobile` = ? LIMIT 1";
            $stmt = mysqli_prepare($conDB, $query);
            mysqli_stmt_bind_param($stmt, "sss", $id_iqama, $dob, $mobile);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if (mysqli_num_rows($result) > 0) {
                $response = ['status' => 'success', 'message' => 'Verification successful. You can now set a new password.'];
            } else {
                $response['message'] = 'The details provided do not match our records. Please try again.';
            }
        }
    }
    // ACTION 2: Update Password
    elseif ($action === 'update_password') {
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($new_password) || empty($confirm_password)) {
            $response['message'] = 'Please provide and confirm your new password.';
        } elseif ($new_password !== $confirm_password) {
            $response['message'] = 'Passwords do not match.';
        } elseif (strlen($new_password) < 5) {
            $response['message'] = 'Password must be at least 5 characters long.';
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_query = "UPDATE `admin_login` SET `password` = ? WHERE `id_iqama` = ?";
            $stmt = mysqli_prepare($conDB, $update_query);
            mysqli_stmt_bind_param($stmt, "ss", $hashed_password, $id_iqama);
            
            if (mysqli_stmt_execute($stmt)) {
                $response = ['status' => 'success', 'message' => 'Your password has been reset successfully. You can now log in.'];
            } else {
                $response['message'] = 'An error occurred while updating your password.';
            }
        }
    }
}

echo json_encode($response);
exit();
