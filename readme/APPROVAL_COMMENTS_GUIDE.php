<?php
/**
 * ============================================================================
 * APPROVAL COMMENTS - IMPLEMENTATION GUIDE
 * ============================================================================
 * 
 * This guide shows how to integrate approval comments into your approval pages.
 * 
 * QUICK START:
 * ============
 * 1. Run the migration: php db/migrate_approval_comments.php
 * 2. Include the form in your page: <?php include 'includes/approval_comment_form.php'; ?>
 * 3. Include helpers: <?php include 'includes/approval_comment_helpers.php'; ?>
 * 4. Call the function when approving: openApprovalCommentForm(requestId, type, 'approved', callback)
 * 5. Save via AJAX using the callback
 * 
 * ============================================================================
 */
?>

<!-- EXAMPLE 1: VACATION REQUEST APPROVAL -->
<!-- File: all_applied_vac.php (or similar) -->

<?php
/*
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/approval_comment_helpers.php';
*/
?>

<script>
/**
 * VACATION APPROVAL FUNCTIONS
 */

function approveVacation(requestId) {
    // Show approval comment form
    openApprovalCommentForm(requestId, 'vacation', 'approved', function(formData) {
        // Save the approval comment first
        saveApprovalCommentAndApprove(formData);
    });
}

function rejectVacation(requestId) {
    // Show rejection form (comment is REQUIRED)
    openApprovalCommentForm(requestId, 'vacation', 'rejected', function(formData) {
        saveApprovalCommentAndReject(formData);
    });
}

function holdVacation(requestId) {
    // Show hold reason form (comment is REQUIRED)
    openApprovalCommentForm(requestId, 'vacation', 'hold', function(formData) {
        saveApprovalCommentAndHold(formData);
    });
}

function saveApprovalCommentAndApprove(formData) {
    // First, save the approval comment
    $.ajax({
        url: './includes/ajaxFile/saveApprovalComment.php',
        type: 'POST',
        data: {
            request_id: formData.requestId,
            request_type: formData.requestType,
            approval_action: formData.approvalAction,
            approval_comment: formData.approvalComment,
            approval_level: 1 // Optional: indicate which approval level
        },
        dataType: 'JSON',
        success: function(response) {
            if (response.success) {
                // Comment saved, now proceed with actual approval
                $.ajax({
                    url: './includes/ajaxFile/leaveHandler.php',
                    type: 'POST',
                    data: {
                        action: 'approve_vacation',
                        request_id: formData.requestId,
                        comment_saved: true
                    },
                    dataType: 'JSON',
                    success: function(approvalResponse) {
                        if (approvalResponse.success) {
                            showApprovalAlert('Vacation approved successfully', 'success', function() {
                                location.reload();
                            });
                        } else {
                            showApprovalAlert(approvalResponse.message || 'Error approving vacation', 'error');
                        }
                    },
                    error: function() {
                        showApprovalAlert('Error communicating with server', 'error');
                    }
                });
            } else {
                showApprovalAlert(response.message || 'Error saving comment', 'error');
            }
        },
        error: function() {
            showApprovalAlert('Error saving approval comment', 'error');
        }
    });
}

function saveApprovalCommentAndReject(formData) {
    // Save the rejection comment
    $.ajax({
        url: './includes/ajaxFile/saveApprovalComment.php',
        type: 'POST',
        data: {
            request_id: formData.requestId,
            request_type: formData.requestType,
            approval_action: formData.approvalAction,
            approval_comment: formData.approvalComment,
            approval_level: 1
        },
        dataType: 'JSON',
        success: function(response) {
            if (response.success) {
                // Comment saved, now reject
                $.ajax({
                    url: './includes/ajaxFile/leaveHandler.php',
                    type: 'POST',
                    data: {
                        action: 'reject_vacation',
                        request_id: formData.requestId,
                        rejection_reason: formData.approvalComment
                    },
                    dataType: 'JSON',
                    success: function(rejectResponse) {
                        if (rejectResponse.success) {
                            showApprovalAlert('Vacation rejected successfully', 'success', function() {
                                location.reload();
                            });
                        } else {
                            showApprovalAlert(rejectResponse.message || 'Error rejecting vacation', 'error');
                        }
                    },
                    error: function() {
                        showApprovalAlert('Error communicating with server', 'error');
                    }
                });
            } else {
                showApprovalAlert(response.message || 'Error saving rejection reason', 'error');
            }
        },
        error: function() {
            showApprovalAlert('Error saving rejection reason', 'error');
        }
    });
}

function saveApprovalCommentAndHold(formData) {
    // Save the hold comment
    $.ajax({
        url: './includes/ajaxFile/saveApprovalComment.php',
        type: 'POST',
        data: {
            request_id: formData.requestId,
            request_type: formData.requestType,
            approval_action: formData.approvalAction,
            approval_comment: formData.approvalComment,
            approval_level: 1
        },
        dataType: 'JSON',
        success: function(response) {
            if (response.success) {
                showApprovalAlert('Vacation put on hold and comment saved', 'info', function() {
                    location.reload();
                });
            } else {
                showApprovalAlert(response.message || 'Error saving hold reason', 'error');
            }
        },
        error: function() {
            showApprovalAlert('Error saving hold reason', 'error');
        }
    });
}
</script>

<!-- HTML Example: Approval buttons in vacation card -->
<!--
<div class="action-buttons">
    <button class="btn btn-success" onclick="approveVacation('REQ-2025-001')">
        <i class="fa fa-check"></i> Approve
    </button>
    <button class="btn btn-danger" onclick="rejectVacation('REQ-2025-001')">
        <i class="fa fa-times"></i> Reject
    </button>
    <button class="btn btn-warning" onclick="holdVacation('REQ-2025-001')">
        <i class="fa fa-pause"></i> Hold
    </button>
    <button class="btn btn-info" onclick="viewApprovalComments('REQ-2025-001', 'vacation')">
        <i class="fa fa-comments"></i> Comments
    </button>
</div>
-->

<!-- EXAMPLE 2: LOAN REQUEST APPROVAL -->

<script>
function approveLoan(requestId) {
    openApprovalCommentForm(requestId, 'loan', 'approved', function(formData) {
        saveApprovalCommentAndApproveLoan(formData);
    });
}

function rejectLoan(requestId) {
    openApprovalCommentForm(requestId, 'loan', 'rejected', function(formData) {
        saveApprovalCommentAndRejectLoan(formData);
    });
}

function saveApprovalCommentAndApproveLoan(formData) {
    $.ajax({
        url: './includes/ajaxFile/saveApprovalComment.php',
        type: 'POST',
        data: {
            request_id: formData.requestId,
            request_type: formData.requestType,
            approval_action: formData.approvalAction,
            approval_comment: formData.approvalComment
        },
        dataType: 'JSON',
        success: function(response) {
            if (response.success) {
                $.ajax({
                    url: './includes/ajaxFile/ajaxLoan.php',
                    type: 'POST',
                    data: {
                        action: 'approve_loan',
                        request_id: formData.requestId
                    },
                    dataType: 'JSON',
                    success: function(r) {
                        if (r.success) {
                            showApprovalAlert('Loan approved and comment saved', 'success', () => location.reload());
                        } else {
                            showApprovalAlert(r.message || 'Error', 'error');
                        }
                    }
                });
            }
        }
    });
}

function saveApprovalCommentAndRejectLoan(formData) {
    $.ajax({
        url: './includes/ajaxFile/saveApprovalComment.php',
        type: 'POST',
        data: {
            request_id: formData.requestId,
            request_type: formData.requestType,
            approval_action: formData.approvalAction,
            approval_comment: formData.approvalComment
        },
        dataType: 'JSON',
        success: function(response) {
            if (response.success) {
                $.ajax({
                    url: './includes/ajaxFile/ajaxLoan.php',
                    type: 'POST',
                    data: {
                        action: 'reject_loan',
                        request_id: formData.requestId,
                        reason: formData.approvalComment
                    },
                    dataType: 'JSON',
                    success: function(r) {
                        if (r.success) {
                            showApprovalAlert('Loan rejected and comment saved', 'success', () => location.reload());
                        } else {
                            showApprovalAlert(r.message || 'Error', 'error');
                        }
                    }
                });
            }
        }
    });
}
</script>

<!-- EXAMPLE 3: VIEW ALL APPROVAL COMMENTS -->

<script>
function viewApprovalComments(requestId, requestType) {
    $.ajax({
        url: './includes/ajaxFile/getApprovalComments.php',
        type: 'GET',
        data: {
            request_id: requestId,
            request_type: requestType
        },
        dataType: 'JSON',
        success: function(response) {
            if (response.success && response.comments.length > 0) {
                let commentsHtml = '<div style="text-align: left; max-height: 400px; overflow-y: auto;">';
                
                response.comments.forEach(function(comment, index) {
                    let actionIcon = '✓';
                    let actionColor = '#28a745';
                    
                    if (comment.action === 'rejected') {
                        actionIcon = '✗';
                        actionColor = '#dc3545';
                    } else if (comment.action === 'hold') {
                        actionIcon = '⏸';
                        actionColor = '#ffc107';
                    }
                    
                    commentsHtml += `
                        <div style="padding: 12px; border-left: 4px solid ${actionColor}; background: #f8f9fa; margin-bottom: 10px; border-radius: 4px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                <strong style="color: ${actionColor};">${actionIcon} ${comment.approver}</strong>
                                <small style="color: #999;">${new Date(comment.date).toLocaleString()}</small>
                            </div>
                            <div style="padding: 10px; background: white; border-radius: 4px;">
                                ${comment.comment || '<em style="color: #999;">No comment provided</em>'}
                            </div>
                        </div>
                    `;
                });
                
                commentsHtml += '</div>';
                
                Swal.fire({
                    title: 'Approval Comments',
                    html: commentsHtml,
                    icon: 'info',
                    confirmButtonText: 'Close'
                });
            } else {
                Swal.fire({
                    title: 'No Comments',
                    text: 'No approval comments found for this request',
                    icon: 'info'
                });
            }
        },
        error: function() {
            showApprovalAlert('Error fetching comments', 'error');
        }
    });
}
</script>

<!-- EXAMPLE 4: SMART REQUEST APPROVAL -->

<script>
function approveSmartRequest(requestId) {
    openApprovalCommentForm(requestId, 'smart_request', 'approved', function(formData) {
        $.ajax({
            url: './includes/ajaxFile/saveApprovalComment.php',
            type: 'POST',
            data: formData,
            dataType: 'JSON',
            success: function(response) {
                if (response.success) {
                    $.ajax({
                        url: './includes/ajaxFile/ajaxSmartRequest.php',
                        type: 'POST',
                        data: { action: 'approve', inv_no: requestId },
                        dataType: 'JSON',
                        success: function(r) {
                            if (r.success) {
                                showApprovalAlert('Request approved', 'success', () => location.reload());
                            }
                        }
                    });
                }
            }
        });
    });
}
</script>

<!-- EXAMPLE 5: RESIGNATION APPROVAL -->

<script>
function approveResignation(requestId) {
    openApprovalCommentForm(requestId, 'resignation', 'approved', function(formData) {
        $.ajax({
            url: './includes/ajaxFile/saveApprovalComment.php',
            type: 'POST',
            data: formData,
            dataType: 'JSON',
            success: function(response) {
                if (response.success) {
                    $.ajax({
                        url: './includes/ajaxFile/ajaxResignation.php',
                        type: 'POST',
                        data: { action: 'approve', request_id: requestId },
                        dataType: 'JSON',
                        success: function(r) {
                            showApprovalAlert('Resignation approved', 'success', () => location.reload());
                        }
                    });
                }
            }
        });
    });
}
</script>

<!-- EXAMPLE 6: REJOIN REQUEST APPROVAL -->

<script>
function approveRejoinRequest(requestId) {
    openApprovalCommentForm(requestId, 'rejoin', 'approved', function(formData) {
        $.ajax({
            url: './includes/ajaxFile/saveApprovalComment.php',
            type: 'POST',
            data: formData,
            dataType: 'JSON',
            success: function(response) {
                if (response.success) {
                    showApprovalAlert('Rejoin approved and comment saved', 'success', () => location.reload());
                }
            }
        });
    });
}
</script>

<?php
/**
 * ============================================================================
 * PHP BACKEND EXAMPLE - DISPLAYING COMMENTS
 * ============================================================================
 */

/*
// In your detail/view page:
<?php

$request_id = $_GET['id'];
$request_type = 'vacation';

// Include helpers
require_once __DIR__ . '/includes/approval_comment_helpers.php';

// Fetch all comments
$comments = get_approval_comments($conDB, $request_id, $request_type);

// Get comment counts by action
$comment_counts = get_comments_count_by_action($conDB, $request_id, $request_type);

?>

<!-- Display in your page -->
<div class="approval-comments-section">
    <h4>Approval Comments & Review</h4>
    
    <!-- Summary -->
    <div class="comment-summary">
        <span class="badge badge-success">
            <i class="fa fa-check"></i> <?php echo $comment_counts['approved']; ?> Approvals
        </span>
        <span class="badge badge-danger">
            <i class="fa fa-times"></i> <?php echo $comment_counts['rejected']; ?> Rejections
        </span>
        <span class="badge badge-warning">
            <i class="fa fa-pause"></i> <?php echo $comment_counts['hold']; ?> On Hold
        </span>
    </div>
    
    <!-- Display comments -->
    <?php echo display_approval_comments_html($comments); ?>
</div>
*/
?>

<!-- EXAMPLE 7: TABLE STRUCTURE INFO -->
<!--
The approval_comments table has the following structure:

- id: Unique comment ID
- request_inv_no: Request invoice number (e.g., 'REQ-2025-001')
- request_type: Type of request (vacation, loan, smart_request, resignation, rejoin)
- approval_action: Action taken (approved, rejected, hold, adjusted)
- approver_emp_id: Employee ID of approver (if employee)
- approver_admin_id: Admin/User ID of approver (if admin user)
- approver_name: Name of approver (for display)
- approval_level: Approval level in chain (1, 2, 3, etc.)
- comment_text: The actual comment/review (up to LONGTEXT)
- comment_date: When comment was added (datetime)
- updated_at: Last update timestamp

Indexes on:
- request_inv_no + request_type (for fast lookup by request)
- approver_emp_id + approver_admin_id (for finding comments by approver)
- approval_action (for filtering by action)
- comment_date (for sorting by date)
-->
