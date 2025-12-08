<?php
/**
 * ================================================================
 * AJAX Handler for Employee Evaluation System
 * ================================================================
 * Handles AJAX requests for the employee evaluation system
 * - Get previous evaluations for an employee
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

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_previous_evaluations':
            $employee_emp_id = trim($_POST['employee_emp_id'] ?? '');
            
            if (empty($employee_emp_id)) {
                throw new Exception('Employee ID is required');
            }
            
            // Get previous evaluations for this employee
            $stmt = $pdo->prepare("
                SELECT 
                    e.id,
                    e.manager_emp_id,
                    em.name AS manager_name,
                    e.total_score,
                    e.observation,
                    DATE_FORMAT(e.created_at, '%Y-%m-%d %H:%i') AS created_at
                FROM emp_evaluations e
                LEFT JOIN employees em ON e.manager_emp_id = em.emp_id
                WHERE e.employee_emp_id = ?
                ORDER BY e.created_at DESC
                LIMIT 10
            ");
            
            $stmt->execute([$employee_emp_id]);
            $evaluations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'status' => 'success',
                'data' => $evaluations
            ]);
            break;
        
        case 'get_evaluation_details':
            $evaluation_id = (int)($_POST['evaluation_id'] ?? 0);
            
            if ($evaluation_id <= 0) {
                throw new Exception('Invalid evaluation ID');
            }
            
            // Get detailed evaluation information
            $stmt = $pdo->prepare("
                SELECT 
                    e.*,
                    em.name AS manager_name,
                    ack_em.name AS acknowledged_by_name,
                    DATE_FORMAT(e.created_at, '%Y-%m-%d %H:%i') AS created_at,
                    DATE_FORMAT(e.manager_acknowledgment_date, '%Y-%m-%d %H:%i') AS acknowledgment_date
                FROM emp_evaluations e
                LEFT JOIN employees em ON e.manager_emp_id = em.emp_id
                LEFT JOIN employees ack_em ON e.manager_acknowledged_by = ack_em.emp_id
                WHERE e.id = ?
            ");
            
            $stmt->execute([$evaluation_id]);
            $evaluation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$evaluation) {
                throw new Exception('Evaluation not found');
            }
            
            echo json_encode([
                'status' => 'success',
                'data' => $evaluation
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
