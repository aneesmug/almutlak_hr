<?php
/**
 * ================================================================
 * AJAX Handler for Employee Evaluation Report
 * ================================================================
 * Handles AJAX requests for the all employee evaluations report page
 * ================================================================
 */

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session_check.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['auth_user'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

// Check access permissions
$allowed_roles = ['hr_recruitment', 'hr_supervisor', 'hr_senior_bp', 'gm', 'administrator'];
$has_access = in_array($user_role, $allowed_roles) || $user_type == 'gm' || $user_type == 'administrator';

if (!$has_access && !$isDeptManager) {
    echo json_encode(['status' => 'error', 'message' => 'Access denied']);
    exit();
}

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_all_evaluations':
            $dept_id = $_POST['dept_id'] ?? '';
            $employee_search = $_POST['employee_search'] ?? '';
            $from_date = $_POST['from_date'] ?? '';
            $to_date = $_POST['to_date'] ?? '';
            $min_score = $_POST['min_score'] ?? '';
            $is_dept_restricted = isset($_POST['is_dept_restricted']) && $_POST['is_dept_restricted'] === 'true';
            $user_dept = (int)($_POST['user_dept'] ?? 0);
            
            // Build query
            $sql = "
                SELECT 
                    e.id,
                    e.employee_emp_id,
                    e.employee_name,
                    e.dept_name,
                    e.employee_position,
                    e.manager_emp_id,
                    em.name AS manager_name,
                    e.total_score,
                    e.observation,
                    DATE_FORMAT(e.created_at, '%Y-%m-%d %H:%i') AS created_at
                FROM emp_evaluations e
                LEFT JOIN employees em ON e.manager_emp_id = em.emp_id
                WHERE 1=1
            ";
            
            $params = [];
            
            // Department restriction
            if ($is_dept_restricted && $user_dept > 0) {
                $sql .= " AND e.dept_id = ?";
                $params[] = $user_dept;
            } elseif (!empty($dept_id)) {
                $sql .= " AND e.dept_id = ?";
                $params[] = $dept_id;
            }
            
            // Employee search
            if (!empty($employee_search)) {
                $sql .= " AND (e.employee_name LIKE ? OR e.employee_emp_id LIKE ?)";
                $search_param = '%' . $employee_search . '%';
                $params[] = $search_param;
                $params[] = $search_param;
            }
            
            // Date range filter
            if (!empty($from_date)) {
                $sql .= " AND DATE(e.created_at) >= ?";
                $params[] = $from_date;
            }
            
            if (!empty($to_date)) {
                $sql .= " AND DATE(e.created_at) <= ?";
                $params[] = $to_date;
            }
            
            // Score filter
            if ($min_score !== '') {
                if ($min_score == '0') {
                    // Below 50
                    $sql .= " AND e.total_score < 50";
                } else {
                    $sql .= " AND e.total_score >= ?";
                    $params[] = (int)$min_score;
                }
            }
            
            // Add access control filters - strict employee scope first (prevents cross-manager leaks)
            if (!empty($allowed_employees_array)) {
                $placeholders = implode(',', array_fill(0, count($allowed_employees_array), '?'));
                $sql .= " AND e.emp_id IN ($placeholders)";
                $params = array_merge($params, $allowed_employees_array);
            } elseif (!empty($allowed_companies_array)) {
                $placeholders = implode(',', array_fill(0, count($allowed_companies_array), '?'));
                $sql .= " AND e.comp_no IN ($placeholders)";
                $params = array_merge($params, $allowed_companies_array);
            }
            
            $sql .= " ORDER BY e.created_at DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $evaluations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'data' => $evaluations
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'data' => []
    ]);
}
?>
