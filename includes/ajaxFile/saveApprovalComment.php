<?php
/**
 * ============================================================================
 * SAVE APPROVAL COMMENT - AJAX HANDLER
 * ============================================================================
 * 
 * This AJAX endpoint saves approval comments to the database.
 * Called from approval_comment_form.php when user submits the form.
 * 
 * POST Parameters:
 * - request_id: Request invoice number (REQ-2025-001)
 * - request_type: Type of request (vacation, loan, smart_request, resignation, rejoin)
 * - approval_action: Action taken (approved, rejected, hold, adjusted)
 * - approval_comment: The comment text
 * 
 * Returns JSON response with success/error status
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../session_check.php';

header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => '',
    'data' => null,
    'errors' => []
];

try {
    // Validate required parameters
    $request_id = $_POST['request_id'] ?? null;
    $request_type = $_POST['request_type'] ?? null;
    $approval_action = $_POST['approval_action'] ?? null;
    $approval_comment = $_POST['approval_comment'] ?? '';

    if (!$request_id || !$request_type || !$approval_action) {
        throw new Exception('Missing required parameters: request_id, request_type, approval_action');
    }

    // Validate request type
    $valid_types = ['vacation', 'loan', 'smart_request', 'resignation', 'rejoin'];
    if (!in_array($request_type, $valid_types)) {
        throw new Exception('Invalid request_type: ' . htmlspecialchars($request_type));
    }

    // Validate approval action
    $valid_actions = ['approved', 'rejected', 'hold', 'adjusted'];
    if (!in_array($approval_action, $valid_actions)) {
        throw new Exception('Invalid approval_action: ' . htmlspecialchars($approval_action));
    }

    // Get approver information from session
    $approver_emp_id = isset($_SESSION['empid']) ? (int)$_SESSION['empid'] : null;
    $approver_admin_id = isset($_SESSION['id']) ? (int)$_SESSION['id'] : null;
    $approver_name = isset($_SESSION['fullname']) ? $_SESSION['fullname'] : 
                    (isset($_SESSION['name']) ? $_SESSION['name'] : 'Unknown');

    // Get approval level if available in session
    $approval_level = isset($_POST['approval_level']) ? (int)$_POST['approval_level'] : 0;

    // Sanitize comment text
    $comment_text = trim($approval_comment);
    if (strlen($comment_text) > 5000) {
        $comment_text = substr($comment_text, 0, 5000);
    }

    // Prepare and execute INSERT query
    $insert_sql = "
        INSERT INTO `approval_comments` 
        (request_inv_no, request_type, approval_action, approver_emp_id, approver_admin_id, 
         approver_name, approval_level, comment_text, comment_date)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ";

    $stmt = $conDB->prepare($insert_sql);
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $conDB->error);
    }

    $stmt->bind_param(
        'sssiiisis',
        $request_id,
        $request_type,
        $approval_action,
        $approver_emp_id,
        $approver_admin_id,
        $approver_name,
        $approval_level,
        $comment_text
    );

    if (!$stmt->execute()) {
        throw new Exception('Failed to save approval comment: ' . $stmt->error);
    }

    $inserted_id = $stmt->insert_id;
    $stmt->close();

    // Log the approval action to activity log if available
    if (function_exists('logApprovalComment')) {
        logApprovalComment($conDB, $request_id, $request_type, $approval_action, $comment_text);
    }

    $response['success'] = true;
    $response['message'] = 'Approval comment saved successfully';
    $response['data'] = [
        'comment_id' => $inserted_id,
        'request_id' => $request_id,
        'request_type' => $request_type,
        'approval_action' => $approval_action,
        'approver_name' => $approver_name,
        'timestamp' => date('Y-m-d H:i:s')
    ];

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = 'Error saving approval comment';
    $response['errors'][] = $e->getMessage();

    // Log error
    error_log('[APPROVAL COMMENT ERROR] ' . $e->getMessage());
}

echo json_encode($response);
exit;
?>
