<?php
/**
 * API: Get Rejoin Requests for Supervisor
 * Returns pending, approved, and rejected rejoin requests for the logged-in supervisor
 */

header('Content-Type: application/json');
require_once __DIR__ . '/session_check.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

try {
    if (empty($_SESSION['empid'])) {
        throw new Exception('Unauthorized');
    }

    $supervisor_id = $_SESSION['empid'];
    $pdo = getDbConnection();

    // Get pending rejoin requests
    $pending = $pdo->prepare("
        SELECT 
            rr.id as rejoin_request_id,
            rr.emp_id,
            rr.requested_rejoin_date,
            rr.requested_reason,
            rr.requested_at,
            rr.status,
            e.name as emp_name,
            v.return_date,
            v.id as vacation_id,
            v.vac_type,
            ra.status as approval_status,
            ra.note as approval_note
        FROM rejoin_requests rr
        JOIN employees e ON rr.emp_id = e.emp_id
        JOIN emp_vacation v ON rr.vacation_id = v.id
        LEFT JOIN request_approvers ra ON ra.request_inv_no = rr.id AND ra.request_type_id = 5
        WHERE e.supervisor_id = :supervisor_id 
        AND rr.status = 'pending'
        AND (ra.status = 'pending' OR ra.status IS NULL)
        ORDER BY rr.requested_at DESC
    ");
    $pending->execute([':supervisor_id' => $supervisor_id]);
    $pending_requests = $pending->fetchAll(PDO::FETCH_ASSOC);

    // Get approved rejoin requests
    $approved = $pdo->prepare("
        SELECT 
            rr.id as rejoin_request_id,
            rr.emp_id,
            rr.final_approved_date,
            rr.final_approved_at,
            rr.approval_note,
            rr.approved_at,
            rr.status,
            e.name as emp_name,
            v.return_date,
            v.vac_type,
            ra.status as approval_status,
            ra.action_date
        FROM rejoin_requests rr
        JOIN employees e ON rr.emp_id = e.emp_id
        JOIN emp_vacation v ON rr.vacation_id = v.id
        LEFT JOIN request_approvers ra ON ra.request_inv_no = rr.id AND ra.request_type_id = 5
        WHERE e.supervisor_id = :supervisor_id 
        AND rr.status IN ('approved', 'adjusted')
        AND (ra.status = 'approved' OR rr.status = 'approved')
        ORDER BY rr.final_approved_at DESC
        LIMIT 50
    ");
    $approved->execute([':supervisor_id' => $supervisor_id]);
    $approved_requests = $approved->fetchAll(PDO::FETCH_ASSOC);

    // Get rejected rejoin requests
    $rejected = $pdo->prepare("
        SELECT 
            rr.id as rejoin_request_id,
            rr.emp_id,
            rr.rejection_reason,
            rr.approved_at,
            rr.status,
            e.name as emp_name,
            v.return_date,
            v.vac_type,
            ra.status as approval_status,
            ra.note as rejection_note,
            ra.action_date
        FROM rejoin_requests rr
        JOIN employees e ON rr.emp_id = e.emp_id
        JOIN emp_vacation v ON rr.vacation_id = v.id
        LEFT JOIN request_approvers ra ON ra.request_inv_no = rr.id AND ra.request_type_id = 5
        WHERE e.supervisor_id = :supervisor_id 
        AND rr.status = 'rejected'
        AND (ra.status = 'rejected' OR rr.status = 'rejected')
        ORDER BY rr.approved_at DESC
        LIMIT 50
    ");
    $rejected->execute([':supervisor_id' => $supervisor_id]);
    $rejected_requests = $rejected->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => [
            'pending' => $pending_requests,
            'approved' => $approved_requests,
            'rejected' => $rejected_requests
        ]
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
