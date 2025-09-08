<?php
/****************************************************************
 * NEW FILE (003-role_management_ajax.php)
 *
 * 1. CREATE ROLE FUNCTIONALITY: This file provides the server-side
 * logic to handle the creation of new roles.
 *
 * 2. DATABASE INTERACTION: It receives the role name and description
 * from an AJAX request (sent from all_users.php) and inserts
 * it into the `roles` table.
 *
 * 3. JSON RESPONSE: It returns a JSON response indicating the
 * success or failure of the operation, which is then used to show
 * a confirmation message to the user.
 ****************************************************************/
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session_check.php';

header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

// Check if user has permission to manage roles
if (!can('manage_roles')) {
    $response['message'] = 'You do not have permission to perform this action.';
    echo json_encode($response);
    exit();
}

if (isset($_POST['action']) && $_POST['action'] == 'create_role') {
    $role_name = trim($_POST['role_name'] ?? '');
    $role_description = trim($_POST['role_description'] ?? '');

    if (empty($role_name)) {
        $response['message'] = 'Role name cannot be empty.';
        echo json_encode($response);
        exit();
    }

    // Check if role already exists
    $check_stmt = mysqli_prepare($conDB, "SELECT id FROM `roles` WHERE `name` = ?");
    mysqli_stmt_bind_param($check_stmt, "s", $role_name);
    mysqli_stmt_execute($check_stmt);
    mysqli_stmt_store_result($check_stmt);

    if (mysqli_stmt_num_rows($check_stmt) > 0) {
        $response['message'] = 'This role name already exists.';
    } else {
        // Insert new role
        $insert_stmt = mysqli_prepare($conDB, "INSERT INTO `roles` (name, description) VALUES (?, ?)");
        mysqli_stmt_bind_param($insert_stmt, "ss", $role_name, $role_description);
        if (mysqli_stmt_execute($insert_stmt)) {
            $response['status'] = 'success';
            $response['message'] = 'Role created successfully.';
        } else {
            $response['message'] = 'Database error: Could not create role.';
        }
        mysqli_stmt_close($insert_stmt);
    }
    mysqli_stmt_close($check_stmt);
}

echo json_encode($response);
exit();
?>
