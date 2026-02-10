<?php
/**
 * Settlement Manager - Corrected Version
 * Uses ApprovalChainManager to ensure settlements follow app_settings approval chain
 * Creates entries in request_approvers, settlement_records, and smt_request_status
 */

require_once __DIR__ . '/ApprovalChainManager.php';
// Note: helper_functions.php is included by the calling file to avoid duplicate declarations

class SettlementManager {
    private $conDB;
    private $pdo;
    private $approvalChainManager;
    
    public function __construct($conDB, $pdo = null) {
        $this->conDB = $conDB;
        $this->pdo = $pdo;
        // Initialize ApprovalChainManager for consistent approval chain handling
        if ($pdo) {
            $this->approvalChainManager = new ApprovalChainManager($conDB, $pdo);
        }
    }
    
    /**
     * Create Settlement from approved vacation/loan
     * Creates entries in request_approvers and smt_request_status using ApprovalChainManager
     * Sends notifications to first approver
     * 
     * @param string $requestInvNo Original request inv_no (e.g., VAC-...)
     * @param string $requestType 'annual_vacation' or 'loan_request'
     * @param string $empId Employee ID
     * @param float $amount Settlement amount
     * @param string $userId Current user ID
     * @return array Result
     */
    public function createSettlement($requestInvNo, $requestType, $empId, $amount, $userId) {
        try {
            $settlementInvNo = 'SETL-' . $requestInvNo;
            
            // Check if settlement already exists
            $checkQry = mysqli_query($this->conDB, "SELECT id FROM settlement_records WHERE request_inv_no = '{$settlementInvNo}' LIMIT 1");
            if ($checkQry && mysqli_num_rows($checkQry) > 0) {
                return ['success' => false, 'message' => 'Settlement already created for this request'];
            }
            if ($checkQry) mysqli_free_result($checkQry);
            
            // 1. Insert into settlement_records
            $insertSettlement = mysqli_query($this->conDB, "
                INSERT INTO settlement_records 
                (request_inv_no, request_type, emp_id, settlement_amount, settlement_status, created_by) 
                VALUES 
                ('{$settlementInvNo}', '{$requestType}', '{$empId}', {$amount}, 'pending_approval', '{$userId}')
            ");
            
            if (!$insertSettlement) {
                return ['success' => false, 'message' => 'Error creating settlement record: ' . mysqli_error($this->conDB)];
            }
            
            // 2. Create approval chain using ApprovalChainManager
            // This ensures settlements follow the EXACT approval chain configured in app_settings
            // NO other approvals are created - ONLY those in the approval_chain_settlement setting
            if ($this->approvalChainManager && $this->pdo) {
                try {
                    // Get employee's department for dept_manager resolution
                    $empDeptQry = mysqli_query($this->conDB, "SELECT dept FROM employees WHERE emp_id = '{$empId}' LIMIT 1");
                    $empDeptRow = $empDeptQry ? mysqli_fetch_assoc($empDeptQry) : null;
                    $empDept = $empDeptRow['dept'] ?? null;
                    if ($empDeptQry) mysqli_free_result($empDeptQry);
                    
                    error_log("Settlement $settlementInvNo: Employee $empId is in department: $empDept");
                    
                    $chainResult = $this->approvalChainManager->createApprovalChain(
                        'settlement',           // Request type: must be 'settlement'
                        $settlementInvNo,       // Settlement invoice number
                        $empId,                 // Employee ID
                        $empDept                // Department ID for dept_manager resolution
                    );
                    
                    if (!$chainResult['success']) {
                        return ['success' => false, 'message' => 'Error creating approval chain: ' . $chainResult['message']];
                    }
                    
                    error_log("Settlement $settlementInvNo: Approval chain created successfully using ApprovalChainManager");
                } catch (Exception $e) {
                    error_log("ApprovalChainManager error for settlement $settlementInvNo: " . $e->getMessage());
                    return ['success' => false, 'message' => 'Error creating approval chain: ' . $e->getMessage()];
                }
            } else {
                // Fallback if ApprovalChainManager not available
                error_log("Warning: ApprovalChainManager not available, using legacy chain creation for settlement $settlementInvNo");
                $this->createLegacySettlementChain($settlementInvNo, $empId);
            }
            
            // 3. Add initial status to smt_request_status
            $insertStatus = mysqli_query($this->conDB, "
                INSERT INTO smt_request_status 
                (inv_no, emp_id, emp_name, note, status) 
                VALUES 
                ('{$settlementInvNo}', '{$userId}', 'System', 'Settlement created from {$requestType}', 'pending')
            ");
            
            // 4. Send notifications to first approver
            error_log("=== SETTLEMENT NOTIFICATION: About to call sendSettlementCreationNotifications for $settlementInvNo ===");
            $this->sendSettlementCreationNotifications($settlementInvNo, $empId, $amount);
            error_log("=== SETTLEMENT NOTIFICATION: Completed sendSettlementCreationNotifications for $settlementInvNo ===");
            
            return [
                'success' => true,
                'settlement_inv_no' => $settlementInvNo,
                'message' => 'Settlement created successfully. Awaiting approval.'
            ];
            
        } catch (Exception $e) {
            error_log("Settlement creation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Send notifications to first approver when settlement is created
     * Sends both browser notification and email
     * @param string $settlementInvNo Settlement invoice number
     * @param string $empId Employee ID
     * @param float $amount Settlement amount
     */
    private function sendSettlementCreationNotifications($settlementInvNo, $empId, $amount) {
        try {
            error_log("NOTIFICATION_DEBUG: sendSettlementCreationNotifications called for settlement=$settlementInvNo, empId=$empId, amount=$amount");
            
            // Get settlement request type ID
            $typeQry = mysqli_query($this->conDB, "SELECT id FROM approval_request_types WHERE type_name = 'settlement' LIMIT 1");
            if (!$typeQry || mysqli_num_rows($typeQry) == 0) {
                error_log("NOTIFICATION_DEBUG: CRITICAL - settlement request type not found in approval_request_types table");
                return;
            }
            $typeId = (int)mysqli_fetch_assoc($typeQry)['id'];
            mysqli_free_result($typeQry);
            error_log("NOTIFICATION_DEBUG: Found settlement request type ID: $typeId");
            
            // Get first pending approver with email from admin_login table
            $approverSql = "
                SELECT ra.approver_id, e.name, e.dept, al.email
                FROM request_approvers ra
                JOIN employees e ON e.emp_id = ra.approver_id
                LEFT JOIN admin_login al ON al.emp_id = ra.approver_id
                WHERE ra.request_inv_no = '$settlementInvNo'
                AND ra.request_type_id = $typeId
                AND ra.status = 'pending'
                ORDER BY ra.approval_level ASC
                LIMIT 1
            ";
            error_log("NOTIFICATION_DEBUG: Executing query to find first approver: $approverSql");
            
            $approverQry = mysqli_query($this->conDB, $approverSql);
            
            if (!$approverQry) {
                error_log("NOTIFICATION_DEBUG: CRITICAL - Query failed: " . mysqli_error($this->conDB));
                return;
            }
            
            if (mysqli_num_rows($approverQry) == 0) {
                error_log("NOTIFICATION_DEBUG: CRITICAL - No approver found for settlement $settlementInvNo");
                return;
            }
            
            $approver = mysqli_fetch_assoc($approverQry);
            mysqli_free_result($approverQry);
            
            error_log("NOTIFICATION_DEBUG: Found approver - ID: {$approver['approver_id']}, Name: {$approver['name']}, Email: {$approver['email']}");
            
            // Get employee details for email
            $empQry = mysqli_query($this->conDB, "SELECT name, dept FROM employees WHERE emp_id = '$empId' LIMIT 1");
            $emp = $empQry ? mysqli_fetch_assoc($empQry) : null;
            if ($empQry) mysqli_free_result($empQry);
            
            $empName = $emp['name'] ?? 'Employee';
            $empDept = $emp['dept'] ?? 'N/A';
            
            // Get department name
            $deptQry = mysqli_query($this->conDB, "SELECT dep_nme FROM department WHERE id = '$empDept' LIMIT 1");
            $deptRow = $deptQry ? mysqli_fetch_assoc($deptQry) : null;
            if ($deptQry) mysqli_free_result($deptQry);
            $deptName = $deptRow['dep_nme'] ?? 'N/A';
            
            error_log("NOTIFICATION_DEBUG: Employee details - Name: $empName, Dept: $empDept, Dept Name: $deptName");
            
            // Include helper functions for notifications
            $helperPath = __DIR__ . '/helper_functions.php';
            error_log("NOTIFICATION_DEBUG: Helper path: $helperPath, File exists: " . (file_exists($helperPath) ? 'YES' : 'NO'));
            
            if (file_exists($helperPath) && strpos($helperPath, 'helper_functions.php') !== false) {
                require_once $helperPath;
                error_log("NOTIFICATION_DEBUG: helper_functions.php included successfully");
            } else {
                error_log("NOTIFICATION_DEBUG: WARNING - Could not include helper_functions.php");
            }
            
            // Check if functions exist
            error_log("NOTIFICATION_DEBUG: create_browser_notification exists: " . (function_exists('create_browser_notification') ? 'YES' : 'NO'));
            error_log("NOTIFICATION_DEBUG: send_approval_email exists: " . (function_exists('send_approval_email') ? 'YES' : 'NO'));
            
            // Send browser notification if function exists
            if (function_exists('create_browser_notification')) {
                create_browser_notification($this->conDB, $approver['approver_id'],
                    'Settlement Approval Required',
                    'Settlement ' . $settlementInvNo . ' for employee ' . $empName . ' requires your approval. Amount: SAR ' . number_format($amount, 2),
                    'all_settlements.php?status=my_pending'
                );
                error_log("Settlement $settlementInvNo: Browser notification sent to approver {$approver['approver_id']}");
            } else {
                error_log("Settlement $settlementInvNo: create_browser_notification function not found");
            }
            
            // Send email notification if function exists
            if (function_exists('send_approval_email')) {
                if (!empty($approver['email'])) {
                    error_log("Settlement $settlementInvNo: Sending email to {$approver['email']} for approver {$approver['name']}");
                    $emailResult = send_approval_email($this->conDB, $approver['email'], $approver['name'],
                        'Settlement Approval Required - ' . $settlementInvNo, 'settlement_approval', [
                            'APPROVER_NAME' => $approver['name'],
                            'REQUEST_ID' => $settlementInvNo,
                            'REQUEST_TITLE' => 'Settlement Approval',
                            'EMPLOYEE_NAME' => $empName,
                            'EMPLOYEE_ID' => $empId,
                            'DEPARTMENT' => $deptName,
                            'SETTLEMENT_AMOUNT' => number_format($amount, 2),
                            'REQUEST_SOURCE' => 'Vacation/Loan Settlement',
                            'EMAIL_MESSAGE' => 'A settlement requires your approval.',
                            'REQUEST_URL' => (function_exists('get_base_url') ? get_base_url() : 'http://localhost') . '/all_settlements.php?status=my_pending'
                        ]
                    );
                    error_log("Settlement $settlementInvNo: Email send result: " . ($emailResult ? 'Success' : 'Failed'));
                } else {
                    error_log("Settlement $settlementInvNo: Cannot send email - approver email is empty for approver ID {$approver['approver_id']}");
                }
            } else {
                error_log("Settlement $settlementInvNo: send_approval_email function not found");
            }
            
            error_log("Settlement notifications completed for approver {$approver['approver_id']} (email: {$approver['email']}) for settlement $settlementInvNo");
            
        } catch (Exception $e) {
            error_log("Error sending settlement notifications: " . $e->getMessage());
        }
    }
    
    /**
     * Legacy fallback: Create settlement approval chain without ApprovalChainManager
     * Only used if ApprovalChainManager is not available
     * IMPORTANT: This method should NOT be used in normal operation
     * Settlements MUST use ApprovalChainManager to respect app_settings configuration
     */
    private function createLegacySettlementChain($settlementInvNo, $empId) {
        try {
            // Get settlement request type ID
            $requestTypeQry = mysqli_query($this->conDB, "SELECT id FROM approval_request_types WHERE type_name = 'settlement' LIMIT 1");
            if (!$requestTypeQry) {
                error_log("Error: settlement request type not found");
                return;
            }
            
            $requestTypeRow = mysqli_fetch_assoc($requestTypeQry);
            $requestTypeId = $requestTypeRow['id'] ?? null;
            mysqli_free_result($requestTypeQry);
            
            if (!$requestTypeId) {
                error_log("Error: settlement request type ID not resolved");
                return;
            }
            
            // Get approval chain for settlement from app_settings
            $chain = $this->getSettlementApprovalChain();
            
            if (empty($chain)) {
                error_log("ERROR: Settlement approval chain NOT configured in app_settings (approval_chain_settlement)");
                return;
            }
            
            error_log("Legacy: Creating settlement chain with " . count($chain) . " levels");
            
            // Create approval entries ONLY from configured chain
            foreach ($chain as $level => $chainStep) {
                $approverRole = $chainStep['user_type'] ?? 'dept_manager';
                
                // Find appropriate approver for this role
                $approverId = $this->findApproverByRole($approverRole, $empId);
                
                if ($approverId) {
                    $insertApproval = mysqli_query($this->conDB, "
                        INSERT INTO request_approvers 
                        (request_inv_no, request_type_id, approver_id, approval_level, status) 
                        VALUES 
                        ('{$settlementInvNo}', {$requestTypeId}, {$approverId}, " . ($level + 1) . ", 'pending')
                    ");
                    
                    if (!$insertApproval) {
                        error_log("Error inserting approval for level " . ($level + 1) . ": " . mysqli_error($this->conDB));
                    } else {
                        error_log("Legacy: Created approval level " . ($level + 1) . " for approver " . $approverId);
                    }
                } else {
                    error_log("Legacy: No approver found for role $approverRole at level " . ($level + 1));
                }
            }
        } catch (Exception $e) {
            error_log("Legacy chain creation error: " . $e->getMessage());
        }
    }
    
    /**
     * Get settlement approval chain from app_settings
     * Returns ONLY the configured chain - no hardcoded approvals
     * @return array Chain configuration
     */
    public function getSettlementApprovalChain() {
        $query = mysqli_query($this->conDB, "
            SELECT setting_value FROM app_settings 
            WHERE setting_name = 'approval_chain_settlement' LIMIT 1
        ");
        
        if ($query && $row = mysqli_fetch_assoc($query)) {
            $chain = json_decode($row['setting_value'], true);
            mysqli_free_result($query);
            return is_array($chain) ? $chain : [];
        }
        
        if ($query) mysqli_free_result($query);
        return [];
    }
    
    /**
     * Find approver employee ID by role
     * Looks up admin_login user with specific user_type
     * 
     * @param string $role Role type (dept_manager, finance_officer, hr_payroll, etc.)
     * @param string $empId Employee ID (for finding department manager)
     * @return int|null Approver employee emp_id (emp_id field from admin_login)
     */
    private function findApproverByRole($role, $empId) {
        switch ($role) {
            case 'dept_manager':
                // Get department manager from admin_login for employee's department
                $deptQuery = mysqli_query($this->conDB, "SELECT dept FROM employees WHERE id = '{$empId}' LIMIT 1");
                $deptRow = mysqli_fetch_assoc($deptQuery);
                $dept = $deptRow['dept'] ?? '';
                
                $query = mysqli_query($this->conDB, "
                    SELECT emp_id FROM admin_login 
                    WHERE user_type IN ('department_manager', 'dept_manager', 'hr')
                    AND dept = '{$dept}'
                    AND status = 1
                    LIMIT 1
                ");
                break;
                
            case 'finance_officer':
                $query = mysqli_query($this->conDB, "
                    SELECT emp_id FROM admin_login 
                    WHERE user_type = 'finance_officer' 
                    AND status = 1
                    LIMIT 1
                ");
                break;
                
            case 'hr_payroll':
                $query = mysqli_query($this->conDB, "
                    SELECT emp_id FROM admin_login 
                    WHERE user_type = 'hr_payroll' 
                    AND status = 1
                    LIMIT 1
                ");
                break;
                
            case 'hr':
                $query = mysqli_query($this->conDB, "
                    SELECT emp_id FROM admin_login 
                    WHERE user_type IN ('hr', 'hr_senior_bp', 'hr_operations')
                    AND status = 1
                    LIMIT 1
                ");
                break;
                
            case 'gm':
                $query = mysqli_query($this->conDB, "
                    SELECT emp_id FROM admin_login 
                    WHERE user_type = 'gm' 
                    AND status = 1
                    LIMIT 1
                ");
                break;
                
            case 'finance':
                $query = mysqli_query($this->conDB, "
                    SELECT emp_id FROM admin_login 
                    WHERE user_type IN ('finance_officer', 'auditor', 'finance')
                    AND status = 1
                    LIMIT 1
                ");
                break;
                
            default:
                return null;
        }
        
        if ($query && $row = mysqli_fetch_assoc($query)) {
            return $row['emp_id'];
        }
        
        return null;
    }
    
    /**
     * Approve settlement
     * Updates request_approvers and smt_request_status
     * Saves approval comment to approval_comments table
     * 
     * @param string $settlementInvNo Settlement invoice number
     * @param int $approverId Approver employee ID
     * @param int $level Approval level
     * @param string $notes Approval notes
     * @param string $comment Optional approval comment
     * @return array Result
     */
    public function approveSettlement($settlementInvNo, $approverId, $level, $notes = '', $comment = '') {
        try {
            // Update request_approvers
            $updateQry = mysqli_query($this->conDB, "
                UPDATE request_approvers 
                SET status = 'approved', note = '{$notes}', action_date = NOW()
                WHERE request_inv_no = '{$settlementInvNo}' 
                AND approval_level = {$level}
                AND approver_id = {$approverId}
            ");
            
            if (!$updateQry) {
                return ['success' => false, 'message' => 'Error updating approval: ' . mysqli_error($this->conDB)];
            }
            
            // Add status record
            $insertStatus = mysqli_query($this->conDB, "
                INSERT INTO smt_request_status 
                (inv_no, emp_id, emp_name, note, status) 
                VALUES 
                ('{$settlementInvNo}', {$approverId}, 'System', '{$notes}', 'approved_level_{$level}')
            ");
            
            // Save approval comment to approval_comments table (if provided)
            if (!empty($comment)) {
                $approverName = $this->getApproverName($approverId);
                $escapedComment = mysqli_real_escape_string($this->conDB, $comment);
                mysqli_query($this->conDB, "
                    INSERT INTO approval_comments 
                    (request_inv_no, request_type, approval_action, approver_emp_id, approver_name, approval_level, comment_text) 
                    VALUES 
                    ('{$settlementInvNo}', 'settlement', 'approved', {$approverId}, '{$approverName}', {$level}, '{$escapedComment}')
                ");
            }
            
            // Check if all levels are approved
            $checkAllQry = mysqli_query($this->conDB, "
                SELECT COUNT(*) as total, SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved
                FROM request_approvers 
                WHERE request_inv_no = '{$settlementInvNo}'
            ");
            
            $checkResult = mysqli_fetch_assoc($checkAllQry);
            if ($checkResult['total'] == $checkResult['approved']) {
                // All levels approved - update settlement status
                mysqli_query($this->conDB, "
                    UPDATE settlement_records 
                    SET settlement_status = 'approved' 
                    WHERE request_inv_no = '{$settlementInvNo}'
                ");
            }
            
            return ['success' => true, 'message' => 'Settlement approved successfully'];
            
        } catch (Exception $e) {
            error_log("Settlement approval error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get settlement approvers list
     * @param string $settlementInvNo Settlement invoice number
     * @return array Approvers list
     */
    public function getSettlementApprovers($settlementInvNo) {
        $query = mysqli_query($this->conDB, "
            SELECT 
                ra.approval_level,
                ra.approver_id,
                ra.status,
                ra.note,
                ra.action_date,
                e.name as approver_name
            FROM request_approvers ra
            LEFT JOIN employees e ON e.id = ra.approver_id
            WHERE ra.request_inv_no = '{$settlementInvNo}'
            ORDER BY ra.approval_level ASC
        ");
        
        $approvers = [];
        while ($row = mysqli_fetch_assoc($query)) {
            $row['approver_name'] = getDisplayName($row['approver_name']);
            $approvers[] = $row;
        }
        
        return $approvers;
    }
    
    /**
     * Reject settlement
     * Updates request_approvers and smt_request_status
     * Saves rejection comment to approval_comments table
     * 
     * @param string $settlementInvNo Settlement invoice number
     * @param int $rejecterId Rejecter employee ID
     * @param int $level Approval level being rejected
     * @param string $reason Rejection reason
     * @param string $comment Optional rejection comment
     * @return array Result
     */
    public function rejectSettlement($settlementInvNo, $rejecterId, $level, $reason = '', $comment = '') {
        try {
            // Update request_approvers
            $updateQry = mysqli_query($this->conDB, "
                UPDATE request_approvers 
                SET status = 'rejected', note = '{$reason}', action_date = NOW()
                WHERE request_inv_no = '{$settlementInvNo}' 
                AND approval_level = {$level}
                AND approver_id = {$rejecterId}
            ");
            
            if (!$updateQry) {
                return ['success' => false, 'message' => 'Error updating rejection: ' . mysqli_error($this->conDB)];
            }
            
            // Add rejection status record
            $insertStatus = mysqli_query($this->conDB, "
                INSERT INTO smt_request_status 
                (inv_no, emp_id, emp_name, note, status) 
                VALUES 
                ('{$settlementInvNo}', {$rejecterId}, 'System', '{$reason}', 'rejected')
            ");
            
            // Save rejection comment to approval_comments table (if provided)
            if (!empty($comment)) {
                $approverName = $this->getApproverName($rejecterId);
                $escapedComment = mysqli_real_escape_string($this->conDB, $comment);
                mysqli_query($this->conDB, "
                    INSERT INTO approval_comments 
                    (request_inv_no, request_type, approval_action, approver_emp_id, approver_name, approval_level, comment_text) 
                    VALUES 
                    ('{$settlementInvNo}', 'settlement', 'rejected', {$rejecterId}, '{$approverName}', {$level}, '{$escapedComment}')
                ");
            }
            
            // Update settlement_records status to rejected
            $updateSettlement = mysqli_query($this->conDB, "
                UPDATE settlement_records 
                SET settlement_status = 'rejected' 
                WHERE request_inv_no LIKE CONCAT('{$settlementInvNo}', '%')
            ");
            
            return ['success' => true, 'message' => 'Settlement rejected successfully'];
            
        } catch (Exception $e) {
            error_log("Settlement rejection error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Process payment for approved settlement
     * Updates settlement_records and adds payment entry to smt_request_status
     * 
     * @param string $settlementInvNo Settlement invoice number
     * @param string $paymentMethod Payment method (bank_transfer, cash, check, etc.)
     * @param string $paymentReference Payment reference number or check number
     * @param string $userId User processing payment
     * @return array Result
     */
    public function processPayment($settlementInvNo, $paymentMethod = 'bank_transfer', $paymentReference = '', $userId = '') {
        try {
            // Check settlement is approved
            $checkQry = mysqli_query($this->conDB, "
                SELECT settlement_status FROM settlement_records 
                WHERE request_inv_no LIKE CONCAT('{$settlementInvNo}', '%')
                LIMIT 1
            ");
            
            if (!$checkQry || mysqli_num_rows($checkQry) === 0) {
                return ['success' => false, 'message' => 'Settlement not found'];
            }
            
            $settlementRow = mysqli_fetch_assoc($checkQry);
            if ($settlementRow['settlement_status'] !== 'approved') {
                return ['success' => false, 'message' => 'Settlement must be approved before processing payment'];
            }
            
            // Update settlement_records
            $updateQry = mysqli_query($this->conDB, "
                UPDATE settlement_records 
                SET settlement_status = 'processed', payment_date = NOW(), settlement_method = '{$paymentMethod}', payment_reference = '{$paymentReference}'
                WHERE request_inv_no LIKE CONCAT('{$settlementInvNo}', '%')
            ");
            
            if (!$updateQry) {
                return ['success' => false, 'message' => 'Error processing payment: ' . mysqli_error($this->conDB)];
            }
            
            // Add payment entry to smt_request_status
            $paymentNote = 'Payment processed - Method: ' . $paymentMethod;
            if (!empty($paymentReference)) {
                $paymentNote .= ' | Reference: ' . $paymentReference;
            }
            
            $insertStatus = mysqli_query($this->conDB, "
                INSERT INTO smt_request_status 
                (inv_no, emp_id, emp_name, note, status) 
                VALUES 
                ('{$settlementInvNo}', '{$userId}', 'System', '{$paymentNote}', 'processed')
            ");
            
            return ['success' => true, 'message' => 'Payment processed successfully'];
            
        } catch (Exception $e) {
            error_log("Settlement payment processing error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get complete settlement details
     * Combines data from settlement_records, request_approvers, and smt_request_status
     * 
     * @param string $settlementInvNo Settlement invoice number
     * @return array|null Settlement details with complete approval history
     */
    public function getSettlementDetails($settlementInvNo) {
        try {
            // Get basic settlement info
            $settlementQry = mysqli_query($this->conDB, "
                SELECT 
                    sr.id,
                    sr.request_inv_no as original_request,
                    sr.request_type,
                    sr.emp_id,
                    e.name as employee_name,
                    sr.amount,
                    sr.status,
                    sr.created_at,
                    sr.updated_at
                FROM settlement_records sr
                LEFT JOIN employees e ON e.id = sr.emp_id
                WHERE sr.request_inv_no LIKE CONCAT('{$settlementInvNo}', '%')
                LIMIT 1
            ");
            
            if (!$settlementQry || mysqli_num_rows($settlementQry) === 0) {
                return null;
            }
            
            $settlement = mysqli_fetch_assoc($settlementQry);
            $settlement['employee_name'] = getDisplayName($settlement['employee_name']);
            
            // Get approval chain status
            $approvalsQry = mysqli_query($this->conDB, "
                SELECT 
                    ra.approval_level,
                    ra.approver_id,
                    e.name as approver_name,
                    ra.status,
                    ra.note,
                    ra.action_date
                FROM request_approvers ra
                LEFT JOIN employees e ON e.id = ra.approver_id
                WHERE ra.request_inv_no LIKE CONCAT('{$settlementInvNo}', '%')
                ORDER BY ra.approval_level ASC
            ");
            
            $settlement['approvals'] = [];
            while ($approval = mysqli_fetch_assoc($approvalsQry)) {
                $approval['approver_name'] = getDisplayName($approval['approver_name']);
                $settlement['approvals'][] = $approval;
            }
            
            // Get complete history from smt_request_status
            $historyQry = mysqli_query($this->conDB, "
                SELECT 
                    emp_id,
                    emp_name,
                    note,
                    status,
                    created_at
                FROM smt_request_status
                WHERE inv_no LIKE CONCAT('{$settlementInvNo}', '%')
                ORDER BY created_at DESC
            ");
            
            $settlement['history'] = [];
            while ($history = mysqli_fetch_assoc($historyQry)) {
                $history['emp_name'] = getDisplayName($history['emp_name']);
                $settlement['history'][] = $history;
            }
            
            return $settlement;
            
        } catch (Exception $e) {
            error_log("Error getting settlement details: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get approver name by employee ID
     * @param int $approverId Approver employee ID
     * @return string Approver name
     */
    // private function getApproverName($approverId) {
    //     $query = mysqli_query($this->conDB, "
    //         SELECT name FROM employees 
    //         WHERE id = {$approverId}
    //         LIMIT 1
    //     ");
        
    //     if ($query && $row = mysqli_fetch_assoc($query)) {
    //         return $row['name'];
    //     }
        
    //     return 'Unknown';
    // }
    
    /**
     * Get employee settlements
     * Retrieves all settlements for an employee
     * 
     * @param string $empId Employee ID
     * @param string $status Filter by status ('all', 'pending', 'approved', 'processed', 'rejected')
     * @return array Settlements
     */
    public function getEmployeeSettlements($empId, $status = 'all') {
        try {
            $statusFilter = '';
            if ($status !== 'all') {
                $statusFilter = "AND sr.status = '{$status}'";
            }
            
            $query = mysqli_query($this->conDB, "
                SELECT 
                    sr.id,
                    sr.request_inv_no,
                    sr.request_type,
                    sr.emp_id,
                    sr.amount,
                    sr.status,
                    sr.created_at,
                    sr.updated_at,
                    COUNT(CASE WHEN ra.status = 'approved' THEN 1 END) as approved_levels,
                    COUNT(ra.id) as total_levels
                FROM settlement_records sr
                LEFT JOIN request_approvers ra ON ra.request_inv_no LIKE CONCAT('SETTLEMENT-', sr.request_inv_no)
                WHERE sr.emp_id = '{$empId}'
                {$statusFilter}
                GROUP BY sr.id
                ORDER BY sr.created_at DESC
            ");
            
            $settlements = [];
            while ($row = mysqli_fetch_assoc($query)) {
                $settlements[] = $row;
            }
            
            return $settlements;
            
        } catch (Exception $e) {
            error_log("Error getting employee settlements: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get Settlement Approval Chain from app_settings
     * Retrieves the approval chain configuration for settlement requests
     * 
     * @param string $requestType Optional request type (not used for settlement as it has single chain)
     * @return array Approval chain array with user_type and role info
     */
    public function getSettlementChain($requestType = '') {
        try {
            // Retrieve settlement approval chain from app_settings
            // Settlement has a single approval chain configuration: approval_chain_settlement
            $query = mysqli_query($this->conDB, "
                SELECT setting_value FROM app_settings 
                WHERE setting_name = 'approval_chain_settlement' 
                LIMIT 1
            ");
            
            if (!$query) {
                error_log("Settlement Chain Query Error: " . mysqli_error($this->conDB));
                return [];
            }
            
            if (mysqli_num_rows($query) == 0) {
                error_log("Settlement approval chain not configured in app_settings");
                return [];
            }
            
            $row = mysqli_fetch_assoc($query);
            mysqli_free_result($query);
            
            $chainJson = $row['setting_value'];
            
            // Parse JSON chain configuration
            $chain = json_decode($chainJson, true);
            
            if (!is_array($chain)) {
                error_log("Settlement approval chain is not valid JSON: " . $chainJson);
                return [];
            }
            
            error_log("Settlement approval chain retrieved: " . json_encode($chain));
            return $chain;
            
        } catch (Exception $e) {
            error_log("Error getting settlement chain: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get approver name by employee ID
     * Helper method for storing approver names in approval_comments
     * 
     * @param int $approverId Employee ID
     * @return string Approver name or 'System'
     */
    private function getApproverName($approverId) {
        $query = mysqli_query($this->conDB, "SELECT name FROM employees WHERE id = {$approverId} OR emp_id = {$approverId} LIMIT 1");
        if ($query && ($row = mysqli_fetch_assoc($query))) {
            return $row['name'] ?? 'System';
        }
        return 'System';
    }
}
?>
