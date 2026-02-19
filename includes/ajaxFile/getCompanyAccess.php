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
    // 1. Fetch all companies from database
    // Try both possible column names: id and comp_id
    $companies = [];
    $company_query = "SELECT `id`, `comp_id`, `comp_name` as name FROM `companies` ORDER BY `comp_name` ASC";
    $result = mysqli_query($conDB, $company_query);
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            // Use comp_id if available, otherwise use id
            $company_id = !empty($row['comp_id']) ? $row['comp_id'] : $row['id'];
            $companies[] = [
                'id' => (string)$company_id,
                'name' => $row['name']
            ];
        }
    }
    
    // 2. Fetch current user's allowed companies
    $allowed_companies = [];
    if ($user_id > 0) {
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
                        // Convert to strings to match HTML option values
                        $allowed_companies = array_map('strval', $decoded);
                    }
                }
            }
            
            mysqli_stmt_close($stmt);
        }
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
