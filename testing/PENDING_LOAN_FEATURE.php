<?php
/**
 * PENDING LOAN REQUEST VALIDATION FEATURE
 * 
 * This feature prevents employees from submitting new loan requests if they already have
 * pending or awaiting loan requests. When they try, they see a user-friendly SweetAlert2
 * modal showing:
 * - Current request status
 * - Complete approval chain with each level's status (✓ Approved, ● Pending, ✗ Rejected)
 * - Who is currently waiting for the request (pending approver name)
 * - Loan details (Invoice, Amount, Status, Days submitted)
 */

// IMPLEMENTATION SUMMARY:
// =====================
// 
// 1. BACKEND (ajaxLoan.php - apply_for_loan function)
//    - Added check at lines 1106-1108: Query emp_loan for pending/awaiting status
//    - If found, fetches approval chain from request_approvers table (lines 1117-1131)
//    - Builds HTML with approval chain showing:
//      * Approval level with approver name
//      * Status badge (green=approved, yellow=pending, red=rejected)
//      * Icon (✓ ● ✗)
//    - Identifies pending approval level and gets current approver name (lines 1143-1155)
//    - Returns JSON with type='pending_request' containing:
//      * inv_no, loan_type, loan_amount, status, created_at
//      * pending_at_name (the person waiting for it)
//      * approval_chain (HTML markup for display)
//
// 2. FRONTEND (loanHandling.js - response handler)
//    - Updated .then(result => {}) handler (lines 395-446)
//    - Checks if response.type === 'pending_request'
//    - Builds SweetAlert2 modal showing:
//      * Title: "Cannot apply now" with info icon
//      * Loan details card (Invoice, Amount, Status, Submitted days ago)
//      * Pending approval section with current approver name
//      * Full approval chain HTML with badges
//    - Uses orange color (#f39c12) for pending state
//    - Doesn't allow submission; only shows information
//
// 3. DATABASE QUERIES
//    Query 1 (Check pending): 
//      SELECT id, inv_no, loan_type, loan_amount, status, created_at 
//      FROM emp_loan 
//      WHERE emp_id = ? AND status IN ('pending', 'awaiting')
//
//    Query 2 (Approval chain):
//      SELECT ra.approval_level, ra.status, 
//             COALESCE(e.name, al.fullname, al.username) as approver_name
//      FROM request_approvers ra
//      LEFT JOIN employees e ON ra.approver_id = e.emp_id
//      LEFT JOIN admin_login al ON ra.approver_id = al.id_iqama
//      WHERE ra.request_inv_no = ? AND ra.request_type_id = 2
//      ORDER BY ra.approval_level ASC
//
//    Query 3 (Current approver at level):
//      SELECT COALESCE(e.name, al.fullname, al.username) as approver_name
//      FROM request_approvers ra
//      LEFT JOIN employees e ON ra.approver_id = e.emp_id
//      LEFT JOIN admin_login al ON ra.approver_id = al.id_iqama
//      WHERE ra.request_inv_no = ? AND ra.request_type_id = 2 
//            AND ra.approval_level = ?
//
// 4. USER EXPERIENCE FLOW
//    Step 1: Employee clicks "Apply for Loan"
//    Step 2: Selects loan type and amount
//    Step 3: Clicks "Submit Application"
//    Step 4: Ajax calls apply_for_loan in ajaxLoan.php
//    Step 5: Backend checks for pending/awaiting loans
//    Step 6a: If NO pending → Continue with normal loan application
//    Step 6b: If YES pending → Return pending_request response
//    Step 7: Frontend detects type === 'pending_request'
//    Step 8: Shows SweetAlert2 modal with:
//            - Cannot apply now message
//            - Current loan details
//            - Approval chain with statuses
//            - Which approver is currently reviewing it
//    Step 9: User clicks "Got it" to close modal
//    Step 10: Form submission is NOT allowed to proceed
//

// ERROR HANDLING:
// - Uses prepared statements to prevent SQL injection
// - Handles both employee and admin_login tables for approver names
// - Gracefully handles missing approval chain records
// - Validates dates and converts to readable format (days ago)

// TESTING:
// To test this feature:
// 1. Create a test employee if not already pending a loan
// 2. Submit a loan request for them (creates pending request)
// 3. Try to apply another loan while first one is pending
// 4. Should see "Cannot apply now" modal with approval chain
//
// Example test flow:
// - Employee 123 submits loan request (becomes pending)
// - Employee 123 tries to apply for another loan
// - System prevents it with "Cannot apply now" modal
// - Shows which level is currently reviewing the first loan

?>
<h1>Pending Loan Request Validation - Implementation Complete</h1>
<p>Feature successfully implemented to prevent duplicate pending loan applications.</p>
