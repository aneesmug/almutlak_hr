<?php
/**
 * AJAX Handler: Get Company Access for User
 * 
 * Returns:
 * - List of all companies in system
 * - Currently allowed companies for the user
 * 
 * POST Parameters:
 * - user_id: admin_login.id (required)
 */

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session_check.php';

header('Content-Type: application/json');

// Check authorization - only allow system admins to edit company access
if (!$is_system_admin) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized: Only system administrators can manage company access'
    ]);
    exit();
}

$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

if ($user_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid user ID'
    ]);
    exit();
}

try {
    // 1. Fetch all companies from database
    $companies = [];
    $company_query = "SELECT DISTINCT comp_id as id, comp_name as name FROM companies ORDER BY comp_id ASC";
    $result = mysqli_query($conDB, $company_query);
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $companies[] = $row;
        }
    }
    
    // 2. Fetch current user's allowed companies
    $allowed_companies = [];
    $user_query = "SELECT allowed_companies FROM admin_login WHERE id = ? LIMIT 1";
    $stmt = mysqli_prepare($conDB, $user_query);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $user_result = mysqli_stmt_get_result($stmt);
        
        if ($user_result && mysqli_num_rows($user_result) === 1) {
            $user_data = mysqli_fetch_assoc($user_result);
            
            // Decode JSON allowed_companies
            if (!empty($user_data['allowed_companies'])) {
                $decoded = json_decode($user_data['allowed_companies'], true);
                if (is_array($decoded)) {
                    $allowed_companies = array_map('intval', $decoded);
                }
            }
        }
        
        mysqli_stmt_close($stmt);
    }
    
    echo json_encode([
        'success' => true,
        'companies' => $companies,
        'allowed_companies' => $allowed_companies,
        'message' => 'Company access loaded successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error loading company access: ' . $e->getMessage()
    ]);
}
?>
