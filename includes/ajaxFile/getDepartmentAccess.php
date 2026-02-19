<?php
/**
 * AJAX Handler: Get Department Access for User
 * 
 * Returns:
 * - List of all departments in system
 * - Currently allowed departments for the user
 * 
 * POST Parameters:
 * - user_id: admin_login.id (required)
 */

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session_check.php';

header('Content-Type: application/json');

// Check authorization - only allow system admins to edit department access
if (!$is_system_admin) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized: Only system administrators can manage department access'
    ]);
    exit();
}

$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

try {
    // 1. Fetch all departments from database
    $departments = [];
    $dept_query = "SELECT DISTINCT `id`, `dep_nme` as name FROM `department` ORDER BY `dep_nme` ASC";
    $result = mysqli_query($conDB, $dept_query);
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $departments[] = $row;
        }
    }
    
    // 2. Fetch current user's allowed departments
    $allowed_departments = [];
    if ($user_id > 0) {
        $user_query = "SELECT allowed_departments FROM admin_login WHERE id = ? LIMIT 1";
        $stmt = mysqli_prepare($conDB, $user_query);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            $user_result = mysqli_stmt_get_result($stmt);
            
            if ($user_result && mysqli_num_rows($user_result) === 1) {
                $user_data = mysqli_fetch_assoc($user_result);
                
                // Decode JSON allowed_departments
                if (!empty($user_data['allowed_departments'])) {
                    $decoded = json_decode($user_data['allowed_departments'], true);
                    if (is_array($decoded)) {
                        $allowed_departments = array_map('intval', $decoded);
                    }
                }
            }
            
            mysqli_stmt_close($stmt);
        }
    }
    
    echo json_encode([
        'success' => true,
        'departments' => $departments,
        'allowed_departments' => $allowed_departments,
        'message' => 'Department access loaded successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error loading department access: ' . $e->getMessage()
    ]);
}
?>
