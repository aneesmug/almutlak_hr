<?php
/**
 * API: Get Rejoin Requests for Supervisor
 * Returns pending, approved, and rejected rejoin requests for the logged-in supervisor
 * System admin sees all requests, others see only their supervised employees
 * Supports both DataTables server-side processing and JSON API
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
    
    // Check if user is system admin (case-insensitive role check)
    // Support multiple admin labels that may exist in user_type
    $normalized_role = strtolower($user_type ?? '');
    $is_admin_label = in_array($normalized_role, ['administrator', 'system_admin', 'system admin', 'admin'], true);
    $is_admin = ($is_system_admin === true) || $is_admin_label;
    
    // Build WHERE clause based on user role
    $supervisor_where = $is_admin ? "1 = 1" : "e.supervisor_id = :supervisor_id";
    $params = $is_admin ? [] : [':supervisor_id' => $supervisor_id];
    
    // Check if this is a DataTables request
    $is_datatables = !empty($_POST['draw']);
    
    if ($is_datatables) {
        // DataTables server-side processing
        $draw = intval($_POST['draw'] ?? 1);
        $start = intval($_POST['start'] ?? 0);
        $length = intval($_POST['length'] ?? 10);
        $search = $_POST['search']['value'] ?? '';
        $status = $_POST['status'] ?? 'pending';
        
        // Determine which query to run based on status
        if ($status === 'pending') {
            // Get pending rejoin requests
            $query = "
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
                WHERE $supervisor_where 
                AND rr.status = 'pending'
                AND (ra.status = 'pending' OR ra.status IS NULL)
            ";
        } elseif ($status === 'approved') {
            // Get approved rejoin requests
            $query = "
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
                WHERE $supervisor_where 
                AND rr.status IN ('approved', 'adjusted')
            ";
        } else {
            // Get rejected rejoin requests
            $query = "
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
                WHERE $supervisor_where 
                AND rr.status = 'rejected'
            ";
        }
        
        // Add search filter if provided
        if (!empty($search)) {
            $query .= " AND (e.name LIKE :search OR e.emp_id LIKE :search OR rr.requested_reason LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        
        // Get total records without search
        $count_query = str_replace(
            ['SELECT *', 'SELECT 
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
                    ra.note as approval_note', 'SELECT COUNT(*) as total'],
            ['SELECT COUNT(*) as total', 'SELECT COUNT(*) as total', 'SELECT COUNT(*) as total'],
            $query
        );
        
        $count_query = preg_replace('/SELECT .+? FROM/i', 'SELECT COUNT(*) as total FROM', $query);
        
        $stmt = $pdo->prepare($count_query);
        $stmt->execute($params);
        $total_records = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        
        // Add sorting
        if (!empty($_POST['order'])) {
            $order_column = intval($_POST['order'][0]['column'] ?? 0);
            $order_dir = strtoupper($_POST['order'][0]['dir'] ?? 'ASC');
            if (in_array($order_dir, ['ASC', 'DESC'])) {
                $columns = $status === 'pending' 
                    ? ['emp_name', 'emp_id', 'return_date', 'requested_rejoin_date', 'requested_reason', 'requested_at', '']
                    : ($status === 'approved'
                        ? ['emp_name', 'emp_id', 'final_approved_date', 'approval_note', 'approved_at']
                        : ['emp_name', 'emp_id', 'rejection_reason', 'approved_at']);
                
                if (isset($columns[$order_column]) && !empty($columns[$order_column])) {
                    $query .= " ORDER BY rr." . $columns[$order_column] . " " . $order_dir;
                }
            }
        } else {
            $query .= $status === 'pending' 
                ? " ORDER BY rr.requested_at DESC"
                : ($status === 'approved'
                    ? " ORDER BY rr.final_approved_at DESC"
                    : " ORDER BY rr.approved_at DESC");
        }
        
        // Add pagination
        $query .= " LIMIT :start, :length";
        $params[':start'] = $start;
        $params[':length'] = $length;
        
        $stmt = $pdo->prepare($query);
        foreach ($params as $key => $value) {
            if (strpos($key, ':') === 0) {
                if ($key === ':start' || $key === ':length') {
                    $stmt->bindValue($key, $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($key, $value);
                }
            }
        }
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'draw' => $draw,
            'recordsTotal' => $total_records,
            'recordsFiltered' => $total_records,
            'data' => $data
        ]);
    } else {
        // Original JSON API response (for backward compatibility)
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
            WHERE $supervisor_where 
            AND rr.status = 'pending'
            AND (ra.status = 'pending' OR ra.status IS NULL)
            ORDER BY rr.requested_at DESC
        ");
        $pending->execute($params);
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
            WHERE $supervisor_where 
            AND rr.status IN ('approved', 'adjusted')
            ORDER BY rr.final_approved_at DESC
            LIMIT 50
        ");
        $approved->execute($params);
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
            WHERE $supervisor_where 
            AND rr.status = 'rejected'
            ORDER BY rr.approved_at DESC
            LIMIT 50
        ");
        $rejected->execute($params);
        $rejected_requests = $rejected->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success',
            'data' => [
                'pending' => $pending_requests,
                'approved' => $approved_requests,
                'rejected' => $rejected_requests
            ]
        ]);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
