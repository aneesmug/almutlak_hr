<?php
/**
 * AJAX Handler for Manager Evaluation Acknowledgment/Objection
 * Location: includes/ajaxFile/ajaxEvaluationAcknowledgment.php
 * 
 * Handles manager acknowledgment and objection of employee evaluations
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../session_check.php';
require_once __DIR__ . '/../evaluation_acknowledgment_handler.php';

// Check if user is logged in
if (!isset($empid) || empty($empid)) {
    echo json_encode(['status' => 401, 'message' => 'Unauthorized']);
    exit;
}

// Get AJAX action type
$action = $_POST['action'] ?? '';
$ajaxType = $_POST['ajaxType'] ?? $action; // Support both parameter names

// ============================================================
// ACTION: Submit Manager Acknowledgment/Objection
// ============================================================
if ($ajaxType === 'acknowledge' || $ajaxType === 'object' || $ajaxType === 'submit_acknowledgment') {
    $eval_id = isset($_POST['evaluation_id']) ? (int)$_POST['evaluation_id'] : (isset($_POST['eval_id']) ? (int)$_POST['eval_id'] : 0);
    
    // Determine acknowledgment status based on action
    if ($ajaxType === 'acknowledge' || $ajaxType === 'submit_acknowledgment') {
        $acknowledgment_status = 'acknowledged';
    } else if ($ajaxType === 'object') {
        $acknowledgment_status = 'objected';
    } else {
        $acknowledgment_status = $_POST['acknowledgment_status'] ?? '';
    }
    
    $objection_note = $_POST['objection_note'] ?? '';
    
    // Validate inputs
    if ($eval_id <= 0 || !in_array($acknowledgment_status, ['acknowledged', 'objected'])) {
        echo json_encode(['status' => 400, 'message' => __('invalid_parameters')]);
        exit;
    }
    
    // Check if user can acknowledge evaluations
    if (!can_acknowledge_evaluations($user_type, $user_role)) {
        echo json_encode(['status' => 403, 'message' => __('you_do_not_have_permission_to_acknowledge_evaluations')]);
        exit;
    }
    
    // Verify that the evaluation exists and belongs to an employee this manager evaluated
    $verify_stmt = $conDB->prepare("SELECT `id`, `employee_emp_id`, `manager_emp_id` FROM `emp_evaluations` WHERE `id` = ? LIMIT 1");
    if (!$verify_stmt) {
        echo json_encode(['status' => 500, 'message' => __('database_error')]);
        exit;
    }
    
    $verify_stmt->bind_param('i', $eval_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows === 0) {
        echo json_encode(['status' => 404, 'message' => 'Evaluation not found']);
        $verify_stmt->close();
        exit;
    }
    
    $verify_stmt->close();
    
    // Update acknowledgment
    $result = update_manager_acknowledgment($conDB, $eval_id, $empid, $acknowledgment_status, $objection_note);
    
    if ($result['status']) {
        // Log evaluation acknowledgment
        ActivityLogger::logApproval('Evaluation', 'ajaxEvaluationAcknowledgment.php', $eval_id, $acknowledgment_status, "Manager {$acknowledgment_status} evaluation (ID: {$eval_id})", 'emp_evaluations');
        
        echo json_encode([
            'status' => 'success',
            'message' => $result['message'],
            'acknowledgment_status' => $acknowledgment_status,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => $result['message']
        ]);
    }
    exit;
}

// ============================================================
// ACTION: Get Evaluation Acknowledgment Status
// ============================================================
if ($ajaxType === 'get_acknowledgment_status') {
    $eval_id = isset($_POST['eval_id']) ? (int)$_POST['eval_id'] : 0;
    
    if ($eval_id <= 0) {
        echo json_encode(['status' => 400, 'message' => 'Invalid evaluation ID']);
        exit;
    }
    
    $ack_data = get_evaluation_acknowledgment($conDB, $eval_id);
    
    if ($ack_data) {
        echo json_encode([
            'status' => 200,
            'data' => $ack_data
        ]);
    } else {
        echo json_encode([
            'status' => 404,
            'message' => 'No acknowledgment data found'
        ]);
    }
    exit;
}

// ============================================================
// ACTION: Get Acknowledgment Report (for management view)
// ============================================================
if ($ajaxType === 'get_acknowledgment_report') {
    // Check if user can view reports
    if (!can_view_acknowledgment_report($user_type, $user_role)) {
        echo json_encode(['status' => 403, 'message' => __('you_do_not_have_permission_to_view_acknowledgment_reports')]);
        exit;
    }
    
    $filter = $_POST['filter'] ?? 'pending'; // 'pending', 'acknowledged', 'objected'
    
    $report_data = get_acknowledgment_report($conDB, $filter);
    
    echo json_encode([
        'status' => 200,
        'data' => $report_data,
        'count' => count($report_data)
    ]);
    exit;
}

// ============================================================
// DEFAULT: Unknown action
// ============================================================
echo json_encode(['status' => 400, 'message' => 'Unknown AJAX action']);
?>
