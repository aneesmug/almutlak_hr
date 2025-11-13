<?php
/**
 * Generic Approval Chain Helper Functions for Loans
 * This follows the same pattern as vacation and smart_request approvals
 * Generated on 2025-11-11
 */

/**
 * Generate unique loan invoice number
 * Pattern: LOAN-YYYYMMDD-EMPID-HASH
 */
function generate_loan_inv_no($emp_id) {
    $date = date('Ymd');
    $hash = substr(md5(uniqid($emp_id . time(), true)), 0, 4);
    return "LOAN-{$date}-{$emp_id}-{$hash}";
}

/**
 * Create approval chain for a new loan request
 * This creates records in request_approvers table following the 6-level approval chain
 * 
 * @param mysqli $conDB Database connection
 * @param string $inv_no Loan invoice number
 * @param string $emp_id Employee ID who applied for loan
 * @return bool Success status
 */
function create_loan_approval_chain($conDB, $inv_no, $emp_id) {
    try {
        // Get employee details
        $stmt = $conDB->prepare("SELECT dept, supervisor_id FROM employees WHERE emp_id = ?");
        $stmt->bind_param("s", $emp_id);
        $stmt->execute();
        $emp_data = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$emp_data) {
            return false;
        }
        
        $dept = $emp_data['dept'];
        $supervisor_id = $emp_data['supervisor_id'];
        $request_type_id = 2; // loan_request
        
        // Level 1: Department Manager or Supervisor
        if (!empty($supervisor_id)) {
            $stmt = $conDB->prepare("INSERT INTO request_approvers (request_inv_no, request_type_id, approver_id, approval_level, status) VALUES (?, ?, ?, 1, 'pending')");
            $stmt->bind_param("sis", $inv_no, $request_type_id, $supervisor_id);
            $stmt->execute();
            $stmt->close();
        } else {
            // Get department manager
            $stmt = $conDB->prepare("SELECT emp_id FROM employees WHERE dept = ? AND emptype = 'Manager' AND status = 1 LIMIT 1");
            $stmt->bind_param("i", $dept);
            $stmt->execute();
            $manager = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if ($manager) {
                $stmt = $conDB->prepare("INSERT INTO request_approvers (request_inv_no, request_type_id, approver_id, approval_level, status) VALUES (?, ?, ?, 1, 'pending')");
                $stmt->bind_param("sis", $inv_no, $request_type_id, $manager['emp_id']);
                $stmt->execute();
                $stmt->close();
            }
        }
        
        // Level 2: HR Assistant (dept 5, user_type assistant)
        $stmt = $conDB->prepare("SELECT id_iqama FROM admin_login WHERE user_type = 'assistant' AND dept = 5 AND status = 1 LIMIT 1");
        $stmt->execute();
        $hr_assistant = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($hr_assistant) {
            $stmt = $conDB->prepare("INSERT INTO request_approvers (request_inv_no, request_type_id, approver_id, approval_level, status) VALUES (?, ?, ?, 2, 'awaiting')");
            $stmt->bind_param("sis", $inv_no, $request_type_id, $hr_assistant['id_iqama']);
            $stmt->execute();
            $stmt->close();
        }
        
        // Level 3: HR Manager (user_type hr)
        $stmt = $conDB->prepare("SELECT id_iqama FROM admin_login WHERE user_type = 'hr' AND status = 1 LIMIT 1");
        $stmt->execute();
        $hr_manager = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($hr_manager) {
            $stmt = $conDB->prepare("INSERT INTO request_approvers (request_inv_no, request_type_id, approver_id, approval_level, status) VALUES (?, ?, ?, 3, 'awaiting')");
            $stmt->bind_param("sis", $inv_no, $request_type_id, $hr_manager['id_iqama']);
            $stmt->execute();
            $stmt->close();
        }
        
        // Level 4: Finance Manager (dept 2, emptype Manager)
        $stmt = $conDB->prepare("SELECT emp_id FROM employees WHERE dept = 2 AND emptype = 'Manager' AND status = 1 LIMIT 1");
        $stmt->execute();
        $finance_manager = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($finance_manager) {
            $stmt = $conDB->prepare("INSERT INTO request_approvers (request_inv_no, request_type_id, approver_id, approval_level, status) VALUES (?, ?, ?, 4, 'awaiting')");
            $stmt->bind_param("sis", $inv_no, $request_type_id, $finance_manager['emp_id']);
            $stmt->execute();
            $stmt->close();
        }
        
        // Level 5: GM (user_type gm)
        $stmt = $conDB->prepare("SELECT id_iqama FROM admin_login WHERE user_type = 'gm' AND status = 1 LIMIT 1");
        $stmt->execute();
        $gm = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($gm) {
            $stmt = $conDB->prepare("INSERT INTO request_approvers (request_inv_no, request_type_id, approver_id, approval_level, status) VALUES (?, ?, ?, 5, 'awaiting')");
            $stmt->bind_param("sis", $inv_no, $request_type_id, $gm['id_iqama']);
            $stmt->execute();
            $stmt->close();
        }
        
        // Level 6: Finance Assistant (dept 2, user_type assistant)
        $stmt = $conDB->prepare("SELECT id_iqama FROM admin_login WHERE user_type = 'assistant' AND dept = 2 AND status = 1 LIMIT 1");
        $stmt->execute();
        $finance_assistant = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($finance_assistant) {
            $stmt = $conDB->prepare("INSERT INTO request_approvers (request_inv_no, request_type_id, approver_id, approval_level, status) VALUES (?, ?, ?, 6, 'awaiting')");
            $stmt->bind_param("sis", $inv_no, $request_type_id, $finance_assistant['id_iqama']);
            $stmt->execute();
            $stmt->close();
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Error creating loan approval chain: " . $e->getMessage());
        return false;
    }
}

/**
 * Get current pending approver for a loan
 * 
 * @param mysqli $conDB Database connection
 * @param string $inv_no Loan invoice number
 * @return array|null Approver data or null
 */
function get_current_loan_approver($conDB, $inv_no) {
    $stmt = $conDB->prepare("
        SELECT ra.*, e.name as approver_name 
        FROM request_approvers ra
        LEFT JOIN employees e ON ra.approver_id = e.emp_id
        WHERE ra.request_inv_no = ? 
        AND ra.request_type_id = 2 
        AND ra.status = 'pending'
        ORDER BY ra.approval_level ASC
        LIMIT 1
    ");
    $stmt->bind_param("s", $inv_no);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    return $result;
}

/**
 * Approve loan at current level and move to next
 * 
 * @param mysqli $conDB Database connection
 * @param string $inv_no Loan invoice number
 * @param string $approver_id ID of approver
 * @param int $approval_level Current approval level
 * @param string $note Optional approval note
 * @return bool Success status
 */
function approve_loan_level($conDB, $inv_no, $approver_id, $approval_level, $note = 'Approved') {
    try {
        $conDB->begin_transaction();
        
        // Update current level to approved
        $stmt = $conDB->prepare("
            UPDATE request_approvers 
            SET status = 'approved', note = ?, action_date = NOW() 
            WHERE request_inv_no = ? 
            AND request_type_id = 2 
            AND approval_level = ? 
            AND approver_id = ?
        ");
        $stmt->bind_param("ssis", $note, $inv_no, $approval_level, $approver_id);
        $stmt->execute();
        $stmt->close();
        
        // Log approval in smt_request_status
        $status_label = "approved_level_{$approval_level}";
        $stmt = $conDB->prepare("
            INSERT INTO smt_request_status (inv_no, emp_id, emp_name, note, status) 
            VALUES (?, ?, 'System', ?, ?)
        ");
        $stmt->bind_param("ssss", $inv_no, $approver_id, $note, $status_label);
        $stmt->execute();
        $stmt->close();
        
        // Check if there's a next level
        $stmt = $conDB->prepare("
            SELECT approval_level 
            FROM request_approvers 
            WHERE request_inv_no = ? 
            AND request_type_id = 2 
            AND approval_level > ? 
            ORDER BY approval_level ASC 
            LIMIT 1
        ");
        $stmt->bind_param("si", $inv_no, $approval_level);
        $stmt->execute();
        $next_level = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($next_level) {
            // Move next level to pending
            $next_approval_level = $next_level['approval_level'];
            $stmt = $conDB->prepare("
                UPDATE request_approvers 
                SET status = 'pending' 
                WHERE request_inv_no = ? 
                AND request_type_id = 2 
                AND approval_level = ?
            ");
            $stmt->bind_param("si", $inv_no, $next_approval_level);
            $stmt->execute();
            $stmt->close();
            
            // Update emp_loan status
            $new_status = "pending_level_{$next_approval_level}";
            $stmt = $conDB->prepare("
                UPDATE emp_loan 
                SET status = ?, current_approval_level = ? 
                WHERE inv_no = ?
            ");
            $stmt->bind_param("sis", $new_status, $next_approval_level, $inv_no);
            $stmt->execute();
            $stmt->close();
        } else {
            // All approvals complete - set to approved
            $stmt = $conDB->prepare("
                UPDATE emp_loan 
                SET status = 'approved', current_approval_level = ? 
                WHERE inv_no = ?
            ");
            $final_level = $approval_level + 1;
            $stmt->bind_param("is", $final_level, $inv_no);
            $stmt->execute();
            $stmt->close();
        }
        
        $conDB->commit();
        return true;
        
    } catch (Exception $e) {
        $conDB->rollback();
        error_log("Error approving loan: " . $e->getMessage());
        return false;
    }
}

/**
 * Reject loan request
 * 
 * @param mysqli $conDB Database connection
 * @param string $inv_no Loan invoice number
 * @param string $approver_id ID of approver who rejected
 * @param int $approval_level Current approval level
 * @param string $reason Rejection reason
 * @return bool Success status
 */
function reject_loan_request($conDB, $inv_no, $approver_id, $approval_level, $reason) {
    try {
        $conDB->begin_transaction();
        
        // Update current level to rejected
        $stmt = $conDB->prepare("
            UPDATE request_approvers 
            SET status = 'rejected', note = ?, action_date = NOW() 
            WHERE request_inv_no = ? 
            AND request_type_id = 2 
            AND approval_level = ?
        ");
        $stmt->bind_param("ssi", $reason, $inv_no, $approval_level);
        $stmt->execute();
        $stmt->close();
        
        // Log rejection
        $stmt = $conDB->prepare("
            INSERT INTO smt_request_status (inv_no, emp_id, emp_name, note, status) 
            VALUES (?, ?, 'System', ?, 'rejected')
        ");
        $stmt->bind_param("sss", $inv_no, $approver_id, $reason);
        $stmt->execute();
        $stmt->close();
        
        // Update emp_loan to rejected
        $stmt = $conDB->prepare("
            UPDATE emp_loan 
            SET status = 'rejected', rejected_by = ?, rejection_reason = ?, rejection_date = NOW() 
            WHERE inv_no = ?
        ");
        $stmt->bind_param("sss", $approver_id, $reason, $inv_no);
        $stmt->execute();
        $stmt->close();
        
        $conDB->commit();
        return true;
        
    } catch (Exception $e) {
        $conDB->rollback();
        error_log("Error rejecting loan: " . $e->getMessage());
        return false;
    }
}
?>
