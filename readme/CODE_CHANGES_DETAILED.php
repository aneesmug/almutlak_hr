<?php
/**
 * VERIFICATION: Key Code Changes for Pending Loan Request Validation
 * 
 * This file documents exactly what was changed in the codebase
 */
?>

BACKEND CHANGE - includes/ajaxFile/ajaxLoan.php
===============================================

Function: apply_for_loan() 
Location: Lines 1092-1203

WHAT WAS ADDED (Lines 1103-1191):
─────────────────────────────────

// CHECK IF EMPLOYEE HAS PENDING OR AWAITING LOAN REQUESTS
$pending_check = $conDB->prepare("SELECT id, inv_no, loan_type, loan_amount, status, created_at FROM emp_loan WHERE emp_id = ? AND status IN ('pending', 'awaiting')");
$pending_check->bind_param("s", $emp_id);
$pending_check->execute();
$pending_result = $pending_check->get_result();

if ($pending_result->num_rows > 0) {
    $pending_loan = $pending_result->fetch_assoc();
    $pending_check->close();
    
    // Get approval chain for this pending loan
    $approval_chain_query = $conDB->prepare("
        SELECT ra.approval_level, ra.status, 
               COALESCE(e.name, al.fullname, al.username) as approver_name,
               ra.action_date
        FROM request_approvers ra
        LEFT JOIN employees e ON ra.approver_id = e.emp_id
        LEFT JOIN admin_login al ON ra.approver_id = al.id_iqama
        WHERE ra.request_inv_no = ? AND ra.request_type_id = 2
        ORDER BY ra.approval_level ASC
    ");
    $approval_chain_query->bind_param("s", $pending_loan['inv_no']);
    $approval_chain_query->execute();
    $chain_result = $approval_chain_query->get_result();
    
    $approval_chain_html = '';
    $pending_at_level = null;
    
    while ($chain_row = $chain_result->fetch_assoc()) {
        $status = strtolower($chain_row['status']);
        $icon = '';
        $badge_class = '';
        
        if ($status === 'approved') {
            $icon = '✓';
            $badge_class = 'badge-success';
        } elseif ($status === 'rejected') {
            $icon = '✗';
            $badge_class = 'badge-danger';
        } else {
            $icon = '●';
            $badge_class = 'badge-warning';
            if (!$pending_at_level) {
                $pending_at_level = $chain_row['approval_level'];
            }
        }
        
        $approval_chain_html .= '<div style="display:flex; align-items:center; padding:8px 0; border-bottom:1px solid #eee;">
            <span class="badge ' . $badge_class . '" style="min-width:30px; margin-right:10px;">' . $icon . '</span>
            <span style="flex:1;">Level ' . $chain_row['approval_level'] . ': ' . htmlspecialchars($chain_row['approver_name']) . ' — ' . ucfirst($status) . '</span>
        </div>';
    }
    $approval_chain_query->close();
    
    $pending_at_name = 'Processing';
    if ($pending_at_level) {
        $pending_at_query = $conDB->prepare("
            SELECT COALESCE(e.name, al.fullname, al.username) as approver_name
            FROM request_approvers ra
            LEFT JOIN employees e ON ra.approver_id = e.emp_id
            LEFT JOIN admin_login al ON ra.approver_id = al.id_iqama
            WHERE ra.request_inv_no = ? AND ra.request_type_id = 2 AND ra.approval_level = ?
        ");
        $pending_at_query->bind_param("si", $pending_loan['inv_no'], $pending_at_level);
        $pending_at_query->execute();
        $pending_at_result = $pending_at_query->get_result();
        if ($row = $pending_at_result->fetch_assoc()) {
            $pending_at_name = $row['approver_name'];
        }
        $pending_at_query->close();
    }
    
    echo json_encode([
        'status' => 'error',
        'title' => 'Cannot apply now',
        'message' => 'You already have a ' . strtoupper($pending_loan['loan_type']) . ' loan request pending approval.',
        'type' => 'pending_request',
        'pending_loan' => [
            'inv_no' => $pending_loan['inv_no'],
            'loan_type' => $pending_loan['loan_type'],
            'loan_amount' => $pending_loan['loan_amount'],
            'status' => $pending_loan['status'],
            'created_at' => $pending_loan['created_at'],
            'pending_at_name' => $pending_at_name,
            'approval_chain' => $approval_chain_html
        ]
    ]);
    return;
}
$pending_check->close();

FRONTEND CHANGE - assets/js/loanHandling.js
===========================================

Location: Lines 385-446 (in the .then(result => {}) handler)

WHAT WAS CHANGED:
─────────────────

OLD CODE:
    }).then(result => {
        if (result.isConfirmed) {
            const response = result.value;
            Swal.fire({ title: response.title, text: response.message, icon: response.type, allowOutsideClick: false })
            .then(() => { if (response.status === 'success') location.reload(); });
        }
    });

NEW CODE:
    }).then(result => {
        if (result.isConfirmed) {
            const response = result.value;
            
            // Handle pending request case
            if (response.type === 'pending_request') {
                const pendingLoan = response.pending_loan;
                const approvalChain = pendingLoan.approval_chain || '';
                const createdDate = new Date(pendingLoan.created_at);
                const daysAgo = Math.floor((new Date() - createdDate) / (1000 * 60 * 60 * 24));
                
                Swal.fire({
                    title: '<i class="fa fa-info-circle" style="color: #f39c12;"></i> ' + response.title,
                    html: `
                        <div style="text-align: left; padding: 20px; background: #f8f9fa; border-radius: 5px;">
                            <p style="margin-bottom: 15px;">
                                <strong>You already have a <span style="color: #dc3545;">${pendingLoan.loan_type.toUpperCase()}</span> loan request pending approval.</strong>
                            </p>
                            <div style="background: white; padding: 15px; border-radius: 5px; margin-bottom: 15px; text-align: left;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 10px;">
                                    <span><strong>Invoice:</strong></span>
                                    <span>${pendingLoan.inv_no}</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 10px;">
                                    <span><strong>Amount:</strong></span>
                                    <span>${Number(pendingLoan.loan_amount).toLocaleString('en-US', { style: 'currency', currency: 'SAR' })}</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 10px;">
                                    <span><strong>Status:</strong></span>
                                    <span><span class="badge badge-warning">${pendingLoan.status.toUpperCase()}</span></span>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 10px; padding-bottom: 10px;">
                                    <span><strong>Submitted:</strong></span>
                                    <span>${daysAgo} days ago</span>
                                </div>
                            </div>
                            <div style="background: white; padding: 15px; border-radius: 5px; margin-bottom: 15px;">
                                <div style="font-weight: bold; margin-bottom: 12px; text-align: center; color: #f39c12;">
                                    ⏳ Pending with: <strong>${pendingLoan.pending_at_name}</strong>
                                </div>
                                <div style="background: #f8f9fa; padding: 10px; border-radius: 5px; font-size: 13px;">
                                    ${approvalChain}
                                </div>
                            </div>
                            <p style="color: #666; font-size: 13px; margin: 0;">
                                Please wait for the current approval to complete before submitting another loan request.
                            </p>
                        </div>
                    `,
                    icon: 'info',
                    confirmButtonText: 'Got it',
                    confirmButtonColor: '#f39c12',
                    allowOutsideClick: false
                });
            } else {
                // Regular success/error handling
                Swal.fire({ title: response.title, text: response.message, icon: response.type, allowOutsideClick: false })
                .then(() => { if (response.status === 'success') location.reload(); });
            }
        }
    });

KEY ADDITIONS:
──────────────

1. Check response.type for 'pending_request' value
2. Extract pending_loan object from response
3. Calculate days pending from created_at timestamp
4. Build custom SweetAlert2 modal with:
   - Info icon in orange (#f39c12)
   - "Cannot apply now" title
   - Loan details card
   - Pending approver information
   - Full approval chain with badges
5. Only show "Got it" button (no confirmation button to proceed)
6. Regular success/error handling remains in else clause

BENEFITS:
─────────

✓ Prevents duplicate pending/awaiting loan submissions
✓ Shows clear, user-friendly error message
✓ Displays current approval progress
✓ Shows who is currently reviewing the request
✓ Uses employee-friendly language and formatting
✓ Matches existing UI patterns in the application
✓ Uses prepared statements for security
✓ Works with both employee and admin tables for approver names

TESTING:
────────

To verify this works:

1. Create/find employee with pending loan in emp_loan table
2. Go to loan application page for that employee
3. Try to submit another loan request
4. Should see "Cannot apply now" modal
5. Verify modal shows:
   - Existing loan details (invoice, amount, status, days pending)
   - Approval chain with all levels and statuses
   - Current approver name
