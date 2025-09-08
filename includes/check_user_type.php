<?php
// This script now checks both the login and employee tables.

require_once __DIR__ . '/db.php';

// Set the response content type to JSON
header('Content-Type: application/json');

// Initialize the response array
$response = ['status' => 'error', 'user_type' => null, 'message' => ''];

try {
    if (empty($_POST['id_iqama'])) {
        throw new Exception('ID/Iqama not provided.');
    }

    $id_iqama = mysqli_real_escape_string($conDB, filter_var($_POST['id_iqama'], FILTER_SANITIZE_NUMBER_INT));

    if (strlen($id_iqama) < 10) {
        throw new Exception('Invalid ID/Iqama format.');
    }

    // --- Step 1: Check if user already exists in admin_login ---
    $login_query = "SELECT `user_type` FROM `admin_login` WHERE `id_iqama` = ? LIMIT 1";
    $stmt = mysqli_prepare($conDB, $login_query);
    mysqli_stmt_bind_param($stmt, "s", $id_iqama);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($user = mysqli_fetch_assoc($result)) {
        // --- User is already a registered login user ---
        $response['status'] = 'success';
        $response['user_type'] = $user['user_type'];
    } else {
        // --- Step 2: User not in admin_login, check the employees table ---
        $employee_query = "SELECT `iqama` FROM `employees` WHERE `iqama` = ? LIMIT 1";
        $emp_stmt = mysqli_prepare($conDB, $employee_query);
        mysqli_stmt_bind_param($emp_stmt, "s", $id_iqama);
        mysqli_stmt_execute($emp_stmt);
        $emp_result = mysqli_stmt_get_result($emp_stmt);

        if (mysqli_num_rows($emp_result) > 0) {
            // --- User found in employees table, needs to set a password ---
            $response['status'] = 'needs_registration';
        } else {
            // --- User not found in either table ---
            $response['status'] = 'not_found';
            $response['message'] = 'This ID/Iqama is not registered in the system.';
        }
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

// Send the JSON response back to the login page
echo json_encode($response);
exit();
