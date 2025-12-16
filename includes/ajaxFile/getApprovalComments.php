<?php
/**
 * ============================================================================
 * GET APPROVAL COMMENTS - AJAX HANDLER
 * ============================================================================
 * 
 * This AJAX endpoint retrieves approval comments for a specific request.
 * 
 * GET/POST Parameters:
 * - request_id: Request invoice number (REQ-2025-001)
 * - request_type: Type of request (vacation, loan, smart_request, resignation, rejoin)
 * 
 * Returns JSON response with array of comments
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../session_check.php';

header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => '',
    'comments' => [],
    'total' => 0,
    'errors' => []
];

try {
    // Get parameters
    $request_id = $_GET['request_id'] ?? $_POST['request_id'] ?? null;
    $request_type = $_GET['request_type'] ?? $_POST['request_type'] ?? null;

    if (!$request_id || !$request_type) {
        throw new Exception('Missing required parameters: request_id, request_type');
    }

    // Validate request type
    $valid_types = ['vacation', 'loan', 'smart_request', 'resignation', 'rejoin'];
    if (!in_array($request_type, $valid_types)) {
        throw new Exception('Invalid request_type');
    }

    // Fetch comments ordered by date
    $fetch_sql = "
        SELECT 
            id,
            request_inv_no,
            request_type,
            approval_action,
            approver_emp_id,
            approver_admin_id,
            approver_name,
            approval_level,
            comment_text,
            comment_date,
            updated_at
        FROM `approval_comments`
        WHERE request_inv_no = ? AND request_type = ?
        ORDER BY comment_date ASC
    ";

    $stmt = $conDB->prepare($fetch_sql);
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $conDB->error);
    }

    $stmt->bind_param('ss', $request_id, $request_type);
    if (!$stmt->execute()) {
        throw new Exception('Query execution failed: ' . $stmt->error);
    }

    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        // Format the comment data
        $comment = [
            'id' => $row['id'],
            'action' => $row['approval_action'],
            'approver' => $row['approver_name'],
            'level' => $row['approval_level'],
            'comment' => $row['comment_text'],
            'date' => $row['comment_date'],
            'timestamp' => strtotime($row['comment_date'])
        ];
        
        $response['comments'][] = $comment;
    }

    $stmt->close();

    $response['success'] = true;
    $response['total'] = count($response['comments']);
    $response['message'] = $response['total'] . ' comment(s) found';

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = 'Error fetching approval comments';
    $response['errors'][] = $e->getMessage();

    error_log('[APPROVAL COMMENT FETCH ERROR] ' . $e->getMessage());
}

echo json_encode($response);
exit;
?>
