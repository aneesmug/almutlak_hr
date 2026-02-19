<?php
/**
 * AJAX Handler: Get Employee Access for User
 *
 * Returns:
 * - List of all active employees in system
 * - Currently allowed employees for the user
 *
 * POST Parameters:
 * - user_id: admin_login.id (optional)
 */

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session_check.php';

header('Content-Type: application/json');

// Check authorization - require authenticated user
// All authenticated users can view/manage access levels
if (!isset($user_type)) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized: User must be logged in'
    ]);
    exit();
}

$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

try {
    // 1. Fetch all active employees
    $employees = [];
    $emp_query = "SELECT `emp_id`, `name` FROM `employees` WHERE `status` = 1 ORDER BY `name` ASC";
    $result = mysqli_query($conDB, $emp_query);

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $employees[] = [
                'id' => (string)$row['emp_id'],  // Convert to string for consistent matching
                'name' => $row['name'],
                'display' => $row['emp_id'] . ' - ' . $row['name']
            ];
        }
    }

    // 2. Fetch current user's allowed employees
    $allowed_employees = [];
    if ($user_id > 0) {
        $user_query = "SELECT allowed_employees FROM admin_login WHERE id = ? LIMIT 1";
        $stmt = mysqli_prepare($conDB, $user_query);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            $user_result = mysqli_stmt_get_result($stmt);

            if ($user_result && mysqli_num_rows($user_result) === 1) {
                $user_data = mysqli_fetch_assoc($user_result);

                if (!empty($user_data['allowed_employees'])) {
                    $decoded = json_decode($user_data['allowed_employees'], true);
                    if (is_array($decoded)) {
                        // Convert to strings to match HTML option values
                        $allowed_employees = array_map('strval', $decoded);
                    }
                }
            }

            mysqli_stmt_close($stmt);
        }
    }

    echo json_encode([
        'success' => true,
        'employees' => $employees,
        'allowed_employees' => $allowed_employees,
        'message' => 'Employee access loaded successfully'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error loading employee access: ' . $e->getMessage()
    ]);
}
?>
