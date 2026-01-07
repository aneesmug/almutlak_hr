<?php
/**
 * Approval Chain Manager Class
 * 
 * Centralizes approval chain logic for all request types.
 * Eliminates code duplication and provides consistent approval workflow.
 * 
 * Usage Example:
 * 
 * // 1. Create approval chain when submitting a request
 * $chainManager = new ApprovalChainManager($conDB, $pdo);
 * $result = $chainManager->createApprovalChain(
 *     'resignation_request',  // request type
 *     $request_inv_no,        // unique request ID
 *     $emp_id,                // employee making request
 *     $dept_id                // employee's department
 * );
 * 
 * // 2. Process approval action
 * $result = $chainManager->processApproval(
 *     $request_inv_no,
 *     $current_user_id,
 *     'approve',              // or 'reject'
 *     $note
 * );
 * 
 * // 3. Check approval status
 * $status = $chainManager->getApprovalStatus($request_inv_no);
 */

/**
 * ApprovalChainManager - Centralized Approval Chain Management
 * 
 * This class provides a unified, reusable system for managing multi-level approval chains
 * across all request types in the Al-Mutlak WMS application.
 * 
 * FEATURES:
 * - Dynamic approval chain loading from app_settings
 * - Role-based approver resolution (supervisor, manager, HR, etc.)
 * - Automatic chain creation with proper level ordering
 * - Approval/rejection processing with automatic forwarding
 * - Status tracking and notifications
 * 
 * USAGE EXAMPLES:
 * 
 * 1. Creating a new approval chain for a request:
 * 
 *    require_once 'includes/ApprovalChainManager.php';
 *    $chainManager = new ApprovalChainManager($conDB, $pdo);
 *    
 *    $result = $chainManager->createApprovalChain(
 *        'rejoin_request',           // Request type
 *        'RJ-2024-0001',             // Invoice/Request number
 *        $employee_id,               // Employee making request
 *        $department_id              // Employee's department
 *    );
 *    
 *    if ($result['success']) {
 *        // Notify first approver
 *        $first = $result['first_approver'];
 *        $chainManager->notifyApprover(
 *            $first['approver_id'],
 *            'New Request Awaiting Approval',
 *            'A rejoin request requires your approval.',
 *            'rejoin_approvals.php'
 *        );
 *    }
 * 
 * 2. Verifying if user can approve:
 * 
 *    $canApprove = $chainManager->verifyApprover('RJ-2024-0001', $current_user_id);
 *    if (!$canApprove) {
 *        throw new Exception("You are not authorized to approve this request");
 *    }
 * 
 * 3. Processing an approval/rejection:
 * 
 *    $result = $chainManager->processApproval(
 *        'RJ-2024-0001',             // Request inv_no
 *        $current_user_id,           // Approver user ID
 *        'approve',                  // Action: 'approve' or 'reject'
 *        'Looks good to me'          // Optional note
 *    );
 *    
 *    if ($result['is_final']) {
 *        // This was the final approval - complete the request
 *        updateRequestStatus($request_id, 'approved');
 *    } else {
 *        // More approvals needed
 *        if ($result['next_approver']) {
 *            $chainManager->notifyApprover(
 *                $result['next_approver']['approver_id'],
 *                'Request Awaiting Approval',
 *                'A request has been forwarded to you.',
 *                'approvals.php'
 *            );
 *        }
 *    }
 * 
 * 4. Getting approval status:
 * 
 *    $status = $chainManager->getApprovalStatus('RJ-2024-0001');
 *    echo "Approved: {$status['approved_count']}/{$status['total_count']}";
 *    foreach ($status['chain'] as $level) {
 *        echo "Level {$level['approval_level']}: {$level['status']}";
 *    }
 * 
 * CONFIGURATION:
 * 
 * Approval chains are configured in the app_settings table as JSON arrays:
 * 
 *    approval_chain_rejoin_request = [
 *        {"level": 1, "user_type": "supervisor"},
 *        {"level": 2, "user_type": "hr"}
 *    ]
 * 
 * Supported user_types:
 * - supervisor: Employee's direct supervisor
 * - manager: Department manager
 * - hr: HR department head
 * - admin: System admin
 * - custom_role: Any custom role (extend resolveApprover method)
 * 
 * @author Al-Mutlak Development Team
 * @version 2.0
 * @since 2024
 */
class ApprovalChainManager {
    private $conDB;      // mysqli connection
    private $pdo;        // PDO connection
    private $activityLogger; // ActivityLogger instance
    
    /**
     * Constructor
     * @param mysqli $conDB MySQLi connection
     * @param PDO $pdo PDO connection
     * @param ActivityLogger $activityLogger ActivityLogger instance (optional)
     */
    public function __construct($conDB, $pdo, $activityLogger = null) {
        $this->conDB = $conDB;
        $this->pdo = $pdo;
        $this->activityLogger = $activityLogger;
    }
    
    /**
     * Load approval chain configuration from app_settings
     * 
     * @param string $requestType The request type (e.g., 'resignation_request')
     * @return array Approval chain configuration
     * @throws Exception if chain not configured
     */
    public function loadApprovalChain($requestType) {
        $settingName = "approval_chain_{$requestType}";
        
        $stmt = $this->pdo->prepare("
            SELECT setting_value 
            FROM app_settings 
            WHERE setting_name = :setting_name 
            LIMIT 1
        ");
        $stmt->execute([':setting_name' => $settingName]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row || empty($row['setting_value'])) {
            throw new Exception("Approval chain for '{$requestType}' not configured. Please configure it in App Settings > Approval Chain.");
        }
        
        $chain = json_decode($row['setting_value'], true);
        
        if (!is_array($chain) || empty($chain)) {
            throw new Exception("Invalid or empty approval chain configuration for '{$requestType}'.");
        }
        
        return $chain;
    }
    
    /**
     * Get request_type_id from approval_request_types table
     * 
     * @param string $requestType The request type name
     * @return int Request type ID
     * @throws Exception if request type not found
     */
    public function getRequestTypeId($requestType) {
        $stmt = $this->pdo->prepare("
            SELECT id 
            FROM approval_request_types 
            WHERE type_name = :type_name 
            LIMIT 1
        ");
        $stmt->execute([':type_name' => $requestType]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row || empty($row['id'])) {
            throw new Exception("Request type '{$requestType}' not found in approval_request_types table.");
        }
        
        return (int)$row['id'];
    }
    
    /**
     * Resolve approver ID based on user_type
     * 
     * @param string $userType The user type (e.g., 'direct_supervisor', 'hr_operations')
     * @param string $empId Employee ID (for direct_supervisor resolution)
     * @param int $deptId Department ID (for dept_manager resolution)
     * @return int|null Approver employee ID or null if not found
     */
    public function resolveApprover($userType, $empId = null, $deptId = null) {
        $approverId = null;
        $debugLog = [];
        
        if ($userType === 'direct_supervisor') {
            // Get supervisor from employee record
            $debugLog[] = "Resolving direct_supervisor for empId: $empId";
            
            if (!empty($empId)) {
                $stmt = $this->pdo->prepare("
                    SELECT supervisor_id, name 
                    FROM employees 
                    WHERE emp_id = :emp_id 
                    LIMIT 1
                ");
                $stmt->execute([':emp_id' => $empId]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $debugLog[] = "Employee found: " . json_encode($row);
                
                if ($row && !empty($row['supervisor_id'])) {
                    $approverId = (int)$row['supervisor_id'];  // Cast to INT for consistency
                    $debugLog[] = "Supervisor ID resolved to: $approverId (type: " . gettype($approverId) . ")";
                } else {
                    $debugLog[] = "ERROR: No supervisor_id found for employee $empId";
                }
            } else {
                $debugLog[] = "ERROR: Empty empId provided";
            }
            
            // Log to file for debugging
            error_log("RESOLVER: " . implode(" | ", $debugLog));
        } elseif ($userType === 'dept_manager') {
            // Get department manager
            if (!empty($deptId) && function_exists('getDeptManager')) {
                $approverId = (int)getDeptManager($this->conDB, $deptId);  // Cast to INT
            }
        } else {
            // Find first active user with matching user_type
            $stmt = $this->pdo->prepare("
                SELECT e.emp_id 
                FROM employees e
                JOIN admin_login al ON e.emp_id = al.emp_id
                WHERE al.user_type = :user_type AND e.status = 1
                LIMIT 1
            ");
            $stmt->execute([':user_type' => $userType]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $approverId = (int)$row['emp_id'];  // Cast to INT
            }
        }
        
        return $approverId;
    }
    
    /**
     * Create approval chain for a request
     * 
     * @param string $requestType Request type (e.g., 'resignation_request')
     * @param string $requestInvNo Unique request invoice number
     * @param string $empId Employee ID making the request
     * @param int $deptId Employee's department ID
     * @return array Result with success status and first approver details
     * @throws Exception on failure
     */
    public function createApprovalChain($requestType, $requestInvNo, $empId = null, $deptId = null) {
        // Load approval chain configuration
        $approvalChain = $this->loadApprovalChain($requestType);
        
        // Get request type ID
        $requestTypeId = $this->getRequestTypeId($requestType);
        
        // Log chain creation start
        error_log("CREATE_CHAIN: Starting for $requestType, inv_no: $requestInvNo, emp_id: $empId, dept_id: $deptId");
        
        // Build approval chain
        $chainToInsert = [];
        $firstApproverId = null;
        
        foreach ($approvalChain as $step) {
            $level = (int)$step['level'];
            $userType = $step['user_type'];
            
            error_log("CREATE_CHAIN: Processing level $level with user_type: $userType");
            
            // Resolve approver
            $approverId = $this->resolveApprover($userType, $empId, $deptId);
            
            error_log("CREATE_CHAIN: Level $level, user_type $userType resolved to approver_id: " . ($approverId ?? 'NULL'));
            
            if (empty($approverId)) {
                // Skip this level if approver cannot be resolved
                error_log("CREATE_CHAIN: Skipping level $level - no approver found");
                continue;
            }
            
            $chainToInsert[] = [
                'level' => $level,
                'approver_id' => $approverId
            ];
            
            // Capture first approver
            if ($level === 1) {
                $firstApproverId = $approverId;
            }
        }
        
        error_log("CREATE_CHAIN: Total approvers resolved: " . count($chainToInsert));
        
        if (empty($chainToInsert)) {
            throw new Exception("Could not resolve any approvers for '{$requestType}' approval chain.");
        }
        
        // Insert approval chain into request_approvers
        foreach ($chainToInsert as $chainStep) {
                // First approver (level 1) gets 'pending' status, others get 'awaiting'
                $status = ($chainStep['level'] === 1) ? 'pending' : 'awaiting';
            
                $stmt = $this->pdo->prepare("
                    INSERT INTO request_approvers 
                    (request_inv_no, request_type_id, approver_id, approval_level, status)
                    VALUES (:inv_no, :type_id, :approver_id, :level, :status)
                ");
                $stmt->execute([
                    ':inv_no' => $requestInvNo,
                    ':type_id' => $requestTypeId,
                    ':approver_id' => $chainStep['approver_id'],
                    ':level' => $chainStep['level'],
                    ':status' => $status
                ]);
        }
        
        // Get first approver details
        $firstApproverDetails = null;
        if ($firstApproverId) {
            $stmt = $this->pdo->prepare("
                SELECT al.email, e.name 
                FROM admin_login al
                JOIN employees e ON al.emp_id = e.emp_id
                WHERE al.emp_id = :approver_id
                LIMIT 1
            ");
            $stmt->execute([':approver_id' => $firstApproverId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $firstApproverDetails = [
                    'approver_id' => $firstApproverId,
                    'email' => $row['email'],
                    'name' => $row['name']
                ];
            }
        }
        
        return [
            'success' => true,
            'first_approver' => $firstApproverDetails,
            'total_levels' => count($chainToInsert)
        ];
    }
    
    /**
     * Verify if current user can approve this request
     * 
     * @param string $requestInvNo Request invoice number
     * @param string $currentUserId Current user's employee ID
     * @return array ['authorized' => bool, 'level' => int]
     */
    public function verifyApprover($requestInvNo, $currentUserId) {
        // Include both 'pending' (first level) and 'awaiting' (subsequent levels) so first approvers are authorized
        error_log("VERIFY_APPROVER_START: Checking inv_no=$requestInvNo, currentUserId=$currentUserId (type: " . gettype($currentUserId) . ")");
        
        // First, let's see ALL approvers for debugging
        $debug_stmt = $this->pdo->prepare("
            SELECT approver_id, approval_level, status 
            FROM request_approvers 
            WHERE request_inv_no = :inv_no 
            ORDER BY approval_level ASC
        ");
        $debug_stmt->execute([':inv_no' => $requestInvNo]);
        $all_rows = $debug_stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("VERIFY_APPROVER_DEBUG: Found " . count($all_rows) . " total approvers for inv_no=$requestInvNo");
        foreach ($all_rows as $debug_row) {
            error_log("  Level {$debug_row['approval_level']}: approver_id={$debug_row['approver_id']}, status={$debug_row['status']}");
        }
        
        $stmt = $this->pdo->prepare("
            SELECT approver_id, approval_level 
            FROM request_approvers 
            WHERE request_inv_no = :inv_no 
            AND status IN ('pending', 'awaiting')
            ORDER BY approval_level ASC
            LIMIT 1
        ");
        $stmt->execute([':inv_no' => $requestInvNo]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $debugInfo = "verifyApprover: inv_no=$requestInvNo, currentUserId=$currentUserId (type: " . gettype($currentUserId) . ")";
        
        if (!$row) {
            error_log($debugInfo . " => NO PENDING/AWAITING APPROVALS FOUND");
            error_log("VERIFY_APPROVER_FAILED: No approvers with status pending/awaiting");
            return ['authorized' => false, 'level' => null, 'message' => 'No pending approvals found'];
        }
        
        $approver_id = $row['approver_id'];
        $approval_level = $row['approval_level'];
        $debugInfo .= ", approver_id=$approver_id (type: " . gettype($approver_id) . "), level=$approval_level";
        error_log($debugInfo);
        
        // Compare as integers for consistency
        $approver_id_int = (int)$approver_id;
        $currentUserId_int = (int)$currentUserId;
        
        error_log("VERIFY_APPROVER_COMPARISON: approver_id_int=$approver_id_int vs currentUserId_int=$currentUserId_int");
        
        if ($approver_id_int !== $currentUserId_int) {
            error_log($debugInfo . " => MISMATCH: approver_id_int($approver_id_int) !== currentUserId_int($currentUserId_int)");
            error_log("VERIFY_APPROVER_FAILED: User not authorized");
            return ['authorized' => false, 'level' => null, 'message' => 'You are not authorized to approve this request'];
        }
        
        error_log($debugInfo . " => AUTHORIZED: approver_id_int($approver_id_int) === currentUserId_int($currentUserId_int)");
        error_log("VERIFY_APPROVER_SUCCESS: User authorized at level $approval_level");
        return [
            'authorized' => true,
            'level' => (int)$approval_level,
            'message' => 'Authorized'
        ];
    }
    
    /**
     * Process approval action (approve or reject)
     * 
     * @param string $requestInvNo Request invoice number
     * @param string $currentUserId Current user's employee ID
     * @param string $action 'approve' or 'reject'
     * @param string $note Optional approval/rejection note
     * @return array Result with status and next approver details
     * @throws Exception on failure
     */
    public function processApproval($requestInvNo, $currentUserId, $action, $note = '') {
        // Verify user can approve
        $verification = $this->verifyApprover($requestInvNo, $currentUserId);
        if (!$verification['authorized']) {
            throw new Exception($verification['message']);
        }
        
        $currentLevel = $verification['level'];
        
        if ($action === 'reject') {
            // Mark current level as rejected
            $stmt = $this->pdo->prepare("
                UPDATE request_approvers 
                SET status = 'rejected', note = :note, action_date = NOW()
                WHERE request_inv_no = :inv_no 
                AND approval_level = :level
            ");
            $stmt->execute([
                ':note' => $note,
                ':inv_no' => $requestInvNo,
                ':level' => $currentLevel
            ]);
            
            // Mark all remaining levels as rejected
            $stmt = $this->pdo->prepare("
                UPDATE request_approvers 
                SET status = 'rejected', note = 'Request rejected at earlier level'
                WHERE request_inv_no = :inv_no 
                AND approval_level > :level
            ");
            $stmt->execute([
                ':inv_no' => $requestInvNo,
                ':level' => $currentLevel
            ]);
            
            return [
                'success' => true,
                'status' => 'rejected',
                'is_final' => true,
                'next_approver' => null
            ];
        } else {
            // Mark current level as approved
            $stmt = $this->pdo->prepare("
                UPDATE request_approvers 
                SET status = 'approved', note = :note, action_date = NOW()
                WHERE request_inv_no = :inv_no 
                AND approval_level = :level
            ");
            $stmt->execute([
                ':note' => $note,
                ':inv_no' => $requestInvNo,
                ':level' => $currentLevel
            ]);
            
            // Check if there are more approval levels
            $stmt = $this->pdo->prepare("
                SELECT approver_id, approval_level
                FROM request_approvers 
                WHERE request_inv_no = :inv_no 
                AND approval_level > :current_level
                AND status = 'awaiting'
                ORDER BY approval_level ASC
                LIMIT 1
            ");
            $stmt->execute([
                ':inv_no' => $requestInvNo,
                ':current_level' => $currentLevel
            ]);
            $nextRow = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($nextRow) {
                // More approvals needed
                $nextApproverId = $nextRow['approver_id'];
                $nextLevel = $nextRow['approval_level'];
                
                // Update next approver status from 'awaiting' to 'pending'
                $stmt = $this->pdo->prepare("
                    UPDATE request_approvers 
                    SET status = 'pending'
                    WHERE request_inv_no = :inv_no 
                    AND approval_level = :level
                ");
                $stmt->execute([
                    ':inv_no' => $requestInvNo,
                    ':level' => $nextLevel
                ]);
                
                // Get next approver details
                $stmt = $this->pdo->prepare("
                    SELECT al.email, e.name, al.emp_id
                    FROM admin_login al
                    JOIN employees e ON al.emp_id = e.emp_id
                    WHERE al.emp_id = :approver_id
                    LIMIT 1
                ");
                $stmt->execute([':approver_id' => $nextApproverId]);
                $approverRow = $stmt->fetch(PDO::FETCH_ASSOC);
                
                return [
                    'success' => true,
                    'status' => 'pending',
                    'is_final' => false,
                    'next_approver' => [
                        'approver_id' => $nextApproverId,
                        'emp_id' => $approverRow['emp_id'] ?? $nextApproverId,
                        'email' => $approverRow['email'] ?? null,
                        'name' => $approverRow['name'] ?? null,
                        'fullname' => $approverRow['name'] ?? null,
                        'approval_level' => $nextLevel
                    ]
                ];
            } else {
                // Final approval
                return [
                    'success' => true,
                    'status' => 'approved',
                    'is_final' => true,
                    'next_approver' => null
                ];
            }
        }
    }
    
    /**
     * Get current approval status
     * 
     * @param string $requestInvNo Request invoice number
     * @return array Status information
     */
    public function getApprovalStatus($requestInvNo) {
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total_levels,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_levels,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_levels,
                SUM(CASE WHEN status = 'awaiting' THEN 1 ELSE 0 END) as pending_levels
            FROM request_approvers 
            WHERE request_inv_no = :inv_no
        ");
        $stmt->execute([':inv_no' => $requestInvNo]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row || $row['total_levels'] == 0) {
            return [
                'status' => 'not_found',
                'total_levels' => 0,
                'approved_levels' => 0,
                'rejected_levels' => 0,
                'pending_levels' => 0
            ];
        }
        
        $status = 'pending';
        if ($row['rejected_levels'] > 0) {
            $status = 'rejected';
        } elseif ($row['approved_levels'] == $row['total_levels']) {
            $status = 'approved';
        }
        
        return [
            'status' => $status,
            'total_levels' => (int)$row['total_levels'],
            'approved_levels' => (int)$row['approved_levels'],
            'rejected_levels' => (int)$row['rejected_levels'],
            'pending_levels' => (int)$row['pending_levels']
        ];
    }
    
    /**
     * Send notification to approver
     * 
     * @param string $approverId Approver's employee ID
     * @param string $title Notification title
     * @param string $message Notification message
     * @param string $link Link to approval page
     */
    public function notifyApprover($approverId, $title, $message, $link) {
        if (function_exists('create_and_show_notification')) {
            create_and_show_notification(
                $this->conDB,
                $approverId,
                $title,
                $message,
                $link,
                'info'
            );
        }
    }
    
    /**
     * Handle Finance Manager approval with payer selection
     * When Finance Manager approves, they select a finance employee as payer
     * 
     * @param string $requestInvNo Request invoice number
     * @param string $financeManagerId Finance Manager's employee ID
     * @param string $selectedPayerId Selected finance employee (payer) ID
     * @param string $note Optional approval note
     * @return array Result with status and next step
     * @throws Exception on failure
     */
    public function approveWithPayerSelection($requestInvNo, $financeManagerId, $selectedPayerId, $note = '') {
        // Verify Finance Manager can approve
        $verification = $this->verifyApprover($requestInvNo, $financeManagerId);
        if (!$verification['authorized']) {
            throw new Exception($verification['message']);
        }
        
        $currentLevel = $verification['level'];
        
        // Mark Finance Manager approval as approved
        $stmt = $this->pdo->prepare("
            UPDATE request_approvers 
            SET status = 'approved', note = :note, action_date = NOW()
            WHERE request_inv_no = :inv_no 
            AND approval_level = :level
        ");
        $stmt->execute([
            ':note' => $note ?: 'Approved and payer assigned',
            ':inv_no' => $requestInvNo,
            ':level' => $currentLevel
        ]);
        
        // Create a new payer entry in request_approvers table
        // This represents the finance payer as a step in the approval flow
        $stmt = $this->pdo->prepare("
            INSERT INTO request_approvers 
            (request_inv_no, request_type_id, approver_id, approval_level, status, note)
            SELECT request_inv_no, request_type_id, :payer_id, :payer_level, 'pending', 
                   CONCAT('Finance payer selected by ', :fm_id)
            FROM request_approvers
            WHERE request_inv_no = :inv_no AND approval_level = :current_level
            LIMIT 1
        ");
        
        try {
            $stmt->execute([
                ':payer_id' => $selectedPayerId,
                ':payer_level' => $currentLevel + 100, // Payer gets next sequential approval level
                ':fm_id' => $financeManagerId,
                ':inv_no' => $requestInvNo,
                ':current_level' => $currentLevel
            ]);
        } catch (Exception $e) {
            // Insert alternative: manually get the request_type_id
            $stmt2 = $this->pdo->prepare("
                SELECT request_type_id FROM request_approvers WHERE request_inv_no = :inv_no LIMIT 1
            ");
            $stmt2->execute([':inv_no' => $requestInvNo]);
            $row = $stmt2->fetch(PDO::FETCH_ASSOC);
            $requestTypeId = $row['request_type_id'] ?? 0;
            
            $stmt3 = $this->pdo->prepare("
                INSERT INTO request_approvers 
                (request_inv_no, request_type_id, approver_id, approval_level, status, note)
                VALUES (:inv_no, :type_id, :payer_id, :payer_level, 'awaiting', :note)
            ");
            $stmt3->execute([
                ':inv_no' => $requestInvNo,
                ':type_id' => $requestTypeId,
                ':payer_id' => $selectedPayerId,
                ':payer_level' => $currentLevel + 100,
                ':note' => "Finance payer selected by {$financeManagerId}"
            ]);
        }
        
        // Get payer details for notification
        $stmt = $this->pdo->prepare("
            SELECT al.email, e.name 
            FROM admin_login al
            JOIN employees e ON al.emp_id = e.emp_id
            WHERE al.emp_id = :payer_id
            LIMIT 1
        ");
        $stmt->execute([':payer_id' => $selectedPayerId]);
        $payerRow = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'success' => true,
            'status' => 'awaiting_payer_payment',
            'is_final' => false,
            'payer' => [
                'payer_id' => $selectedPayerId,
                'email' => $payerRow['email'] ?? null,
                'name' => $payerRow['name'] ?? null
            ],
            'message' => 'Finance employee selected as payer. Awaiting payment details.'
        ];
    }
    
    /**
     * Record payment by finance payer
     * Finance payer adds approved amount and payment proof
     * 
     * @param string $requestInvNo Request invoice number
     * @param string $payerId Finance payer's employee ID
     * @param float $approvedAmount Amount approved for payment
     * @param string $paymentProof Payment proof/receipt (file path or reference)
     * @param string $paymentMethod Payment method (e.g., 'bank_transfer', 'check', 'cash')
     * @return array Result with status
     * @throws Exception on failure
     */
    public function recordPayerPayment($requestInvNo, $payerId, $approvedAmount, $paymentProof = '', $paymentMethod = '') {
        // Verify payer is authorized
        $stmt = $this->pdo->prepare("
            SELECT approver_id, approval_level 
            FROM request_approvers 
            WHERE request_inv_no = :inv_no 
            AND approver_id = :payer_id
            AND status = 'awaiting'
            AND approval_level >= 100
            LIMIT 1
        ");
        $stmt->execute([':inv_no' => $requestInvNo, ':payer_id' => $payerId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) {
            throw new Exception('You are not authorized as payer for this request');
        }
        
        $payerLevel = $row['approval_level'];
        
        // Update payer record with payment details
        $stmt = $this->pdo->prepare("
            UPDATE request_approvers 
            SET status = 'approved', 
                note = :note,
                action_date = NOW()
            WHERE request_inv_no = :inv_no 
            AND approver_id = :payer_id
            AND approval_level = :level
        ");
        
        $noteData = "Payment Amount: " . number_format($approvedAmount, 2) . " | Proof: {$paymentProof}" . 
                    (!empty($paymentMethod) ? " | Method: {$paymentMethod}" : "");
        
        $stmt->execute([
            ':note' => $noteData,
            ':inv_no' => $requestInvNo,
            ':payer_id' => $payerId,
            ':level' => $payerLevel
        ]);
        
        // Check if there are more approval levels after payer
        $stmt = $this->pdo->prepare("
            SELECT approver_id, approval_level
            FROM request_approvers 
            WHERE request_inv_no = :inv_no 
            AND approval_level > :current_level
            AND status = 'awaiting'
            ORDER BY approval_level ASC
            LIMIT 1
        ");
        $stmt->execute([':inv_no' => $requestInvNo, ':current_level' => $payerLevel]);
        $nextRow = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($nextRow) {
            // More approvals needed
            $nextApproverId = $nextRow['approver_id'];
            
            $stmt = $this->pdo->prepare("
                SELECT al.email, e.name 
                FROM admin_login al
                JOIN employees e ON al.emp_id = e.emp_id
                WHERE al.emp_id = :approver_id
                LIMIT 1
            ");
            $stmt->execute([':approver_id' => $nextApproverId]);
            $approverRow = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'status' => 'payment_recorded',
                'is_final' => false,
                'next_approver' => [
                    'approver_id' => $nextApproverId,
                    'email' => $approverRow['email'] ?? null,
                    'name' => $approverRow['name'] ?? null
                ],
                'message' => 'Payment recorded. Awaiting next approval.'
            ];
        } else {
            // Final approval complete
            return [
                'success' => true,
                'status' => 'approved',
                'is_final' => true,
                'next_approver' => null,
                'message' => 'Payment recorded. All approvals complete.'
            ];
        }
    }
    
    /**
     * Get finance payer for a request
     * Retrieves the currently assigned finance payer
     * 
     * @param string $requestInvNo Request invoice number
     * @return array|null Payer details or null if not assigned
     */
    public function getFinancePayer($requestInvNo) {
        $stmt = $this->pdo->prepare("
            SELECT ra.approver_id, ra.status, ra.note, 
                   al.email, e.name
            FROM request_approvers ra
            LEFT JOIN admin_login al ON ra.approver_id = al.emp_id
            LEFT JOIN employees e ON ra.approver_id = e.emp_id
            WHERE ra.request_inv_no = :inv_no 
            AND ra.approval_level >= 100
            LIMIT 1
        ");
        $stmt->execute([':inv_no' => $requestInvNo]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row ?: null;
    }
    
    /**
     * Check if current user is assigned as a payer and process payment
     * Validates payment amount and proof, handles file upload, updates approval record
     * 
     * @param string $requestInvNo Request invoice number
     * @param string $currentUserId Current user's employee ID
     * @param float $paymentAmount Payment amount in SAR
     * @param array $paymentProofFile File array from $_FILES['payment_proof']
     * @param string $approvalComment Optional approval comment
     * @return array Result with status and message
     * @throws Exception on failure
     */
    public function processPayerPayment($requestInvNo, $currentUserId, $paymentAmount, $paymentProofFile, $approvalComment = '') {
        // Check if current user is assigned as a PAYER (approval_level >= 100)
        $payer_check_stmt = $this->pdo->prepare("
            SELECT COUNT(*) as is_payer 
            FROM request_approvers 
            WHERE request_inv_no = :inv_no 
            AND approver_id = :emp_id 
            AND approval_level >= 100
        ");
        $payer_check_stmt->execute([
            ':inv_no' => $requestInvNo,
            ':emp_id' => $currentUserId
        ]);
        $payer_check_row = $payer_check_stmt->fetch(PDO::FETCH_ASSOC);
        $is_payer = (int)$payer_check_row['is_payer'] > 0;
        
        // Return early if user is not a payer
        if (!$is_payer) {
            return [
                'success' => false,
                'is_payer' => false,
                'message' => 'User is not assigned as a payer for this request'
            ];
        }
        
        // Validate payment amount
        if ($paymentAmount <= 0) {
            throw new Exception("Payment amount is required and must be greater than zero");
        }
        
        // NEW: Validate payment amount against approved amount from emp_vacation
        $vacation_stmt = $this->pdo->prepare("
            SELECT ev.ticket_pay, ev.permit_fee, ev.encashment_amount, ev.vac_type
            FROM emp_vacation ev
            WHERE ev.request_inv_no = :inv_no
            LIMIT 1
        ");
        $vacation_stmt->execute([':inv_no' => $requestInvNo]);
        $vacation_row = $vacation_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($vacation_row) {
            $vac_type = $vacation_row['vac_type'] ?? '';
            $approved_amount = 0;
            
            if ($vac_type === 'Encashed') {
                // For encashed vacations, use encashment_amount only
                $approved_amount = (float)($vacation_row['encashment_amount'] ?? 0);
            } else {
                // For other vacation types, use ticket_pay + permit_fee
                $approved_amount = (float)($vacation_row['ticket_pay'] ?? 0) + (float)($vacation_row['permit_fee'] ?? 0);
            }
            
            // Enforce exact match (allow small floating point tolerance)
            if ($approved_amount > 0 && abs($paymentAmount - $approved_amount) > 0.01) {
                throw new Exception(
                    "Payment amount (" . number_format($paymentAmount, 2) . " SAR) must exactly match the approved amount (" . 
                    number_format($approved_amount, 2) . " SAR). Please enter the correct amount."
                );
            }
        }
        
        // Validate payment proof file
        $has_payment_proof = isset($paymentProofFile) && $paymentProofFile['error'] == UPLOAD_ERR_OK;
        if (!$has_payment_proof) {
            throw new Exception("Payment proof document is required");
        }
        
        // Handle file upload
        $uploadDir = dirname(__DIR__) . "/assets/payment_proofs/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileExtension = pathinfo($paymentProofFile['name'], PATHINFO_EXTENSION);
        $safe_filename = preg_replace('/[^A-Za-z0-9\._-]/', '', basename($paymentProofFile['name']));
        $fileName = "payment_proof_" . $requestInvNo . "_" . time() . '.' . $fileExtension;
        $targetPath = $uploadDir . $fileName;
        
        if (!move_uploaded_file($paymentProofFile['tmp_name'], $targetPath)) {
            throw new Exception("Failed to upload payment proof document");
        }
        
        // Update request_approvers with payment details
        $update_stmt = $this->pdo->prepare("
            UPDATE request_approvers 
            SET payment_amount = :amount,
                payment_proof_path = :proof_path,
                status = 'approved',
                note = :note,
                action_date = NOW()
            WHERE request_inv_no = :inv_no 
            AND approver_id = :emp_id 
            AND approval_level >= 100
        ");
        $update_stmt->execute([
            ':amount' => $paymentAmount,
            ':proof_path' => $targetPath,
            ':note' => $approvalComment ?: 'Payment processed and proof uploaded',
            ':inv_no' => $requestInvNo,
            ':emp_id' => $currentUserId
        ]);
        
        // Update emp_vacation payment_status when payer processes payment
        try {
            $updateVacation_stmt = $this->pdo->prepare("
                UPDATE emp_vacation 
                SET payment_status = 'paid',
                    payment_date = NOW(),
                    is_payment_completed = 1
                WHERE request_inv_no = :inv_no
                LIMIT 1
            ");
            $updateVacation_stmt->execute([':inv_no' => $requestInvNo]);
        } catch (\Exception $e) {
            // Log the error but don't fail the entire transaction
            error_log("Failed to update emp_vacation payment status: " . $e->getMessage());
        }
        
        // Check if there are more approval levels after payer
        $stmt = $this->pdo->prepare("
            SELECT approver_id, approval_level
            FROM request_approvers 
            WHERE request_inv_no = :inv_no 
            AND approval_level > (
                SELECT approval_level FROM request_approvers 
                WHERE request_inv_no = :inv_no_sub AND approver_id = :emp_id_sub AND approval_level >= 100
            )
            AND status = 'awaiting'
            ORDER BY approval_level ASC
            LIMIT 1
        ");
        $stmt->execute([':inv_no' => $requestInvNo, ':inv_no_sub' => $requestInvNo, ':emp_id_sub' => $currentUserId]);
        $nextRow = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $nextApprover = null;
        if ($nextRow) {
            // More approvals needed - get next approver details
            $nextApproverId = $nextRow['approver_id'];
            
            $stmt = $this->pdo->prepare("
                SELECT al.email, e.name 
                FROM admin_login al
                JOIN employees e ON al.emp_id = e.emp_id
                WHERE al.emp_id = :approver_id
                LIMIT 1
            ");
            $stmt->execute([':approver_id' => $nextApproverId]);
            $approverRow = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $nextApprover = [
                'approver_id' => $nextApproverId,
                'email' => $approverRow['email'] ?? null,
                'name' => $approverRow['name'] ?? null
            ];
        }
        
        return [
            'success' => true,
            'is_payer' => true,
            'is_final' => ($nextApprover === null),
            'payment_amount' => $paymentAmount,
            'payment_proof' => $fileName,
            'next_approver' => $nextApprover,
            'message' => 'Payment of ' . number_format($paymentAmount, 2) . ' SAR has been recorded with proof.'
        ];
    }
}
