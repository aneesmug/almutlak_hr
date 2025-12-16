<?php
/**
 * ============================================================================
 * APPROVAL COMMENT FORM - SweetAlert2 IMPLEMENTATION
 * ============================================================================
 * 
 * This file provides a SweetAlert2-based form for collecting approval comments
 * when approving, rejecting, or holding requests.
 * 
 * Features:
 * - Dynamic form based on approval action (approved/rejected/hold/adjusted)
 * - Character counter (max 5000 characters)
 * - Optional for approvals, Required for rejections
 * - Professional styling with custom CSS
 * 
 * Include this file in your approval pages and call openApprovalCommentForm()
 */
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    /* SweetAlert2 Custom Styling for Approval Comments */
    .swal2-popup.approval-comment-popup {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    }

    .approval-comment-textarea {
        width: 100%;
        min-height: 120px;
        padding: 12px;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        font-size: 14px;
        resize: vertical;
        transition: border-color 0.3s ease;
    }

    .approval-comment-textarea:focus {
        outline: none;
        border-color: #007bff;
        box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
    }

    .approval-comment-label {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
        font-weight: 600;
        font-size: 14px;
    }

    .approval-comment-label .label-text {
        color: #333;
    }

    .approval-comment-label .label-badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .badge-optional {
        background-color: #e8f4f8;
        color: #0066cc;
    }

    .badge-required {
        background-color: #ffe8e8;
        color: #cc0000;
    }

    .approval-comment-counter {
        display: flex;
        justify-content: space-between;
        font-size: 12px;
        color: #666;
        margin-top: 6px;
    }

    .approval-comment-counter .counter-text {
        color: #999;
    }

    .approval-comment-counter .counter-number {
        font-weight: 600;
        font-family: 'Courier New', monospace;
    }

    .counter-limit-reached {
        color: #dc3545 !important;
    }

    /* Approval Action Icons */
    .approval-action-icon {
        display: inline-block;
        margin-right: 8px;
        font-size: 18px;
    }

    /* Form Container */
    .approval-comment-container {
        text-align: left;
        padding: 10px 0;
    }

    /* Title styling */
    .swal2-popup.approval-comment-popup .swal2-title {
        padding: 1rem;
        font-size: 1.5rem;
        color: #333;
    }

    /* HTML Content styling */
    .swal2-popup.approval-comment-popup .swal2-html-container {
        padding: 1rem;
        font-size: 0.95rem;
    }

    /* Buttons */
    .swal2-popup.approval-comment-popup .swal2-confirm {
        min-width: 120px;
        font-weight: 600;
    }

    .swal2-popup.approval-comment-popup .swal2-cancel {
        min-width: 100px;
    }
</style>

<script>
/**
 * Open Approval Comment Form
 * 
 * @param {string} requestId - Request ID (inv_no)
 * @param {string} requestType - Type of request (vacation, loan, smart_request, resignation, rejoin)
 * @param {string} approvalAction - Action being taken (approved, rejected, hold, adjusted)
 * @param {function} onSubmit - Callback function when form is submitted
 */
function openApprovalCommentForm(requestId, requestType, approvalAction = 'approved', onSubmit = null) {
    
    // Configuration based on approval action
    const actionConfig = {
        'approved': {
            title: '✓ Approval Comment',
            titleColor: '#28a745',
            confirmText: 'Approve',
            confirmColor: '#28a745',
            isRequired: false,
            label: 'Approval Comment (Optional)',
            placeholder: 'Add any comments or notes about this approval...',
            icon: 'success'
        },
        'rejected': {
            title: '✗ Rejection Reason',
            titleColor: '#dc3545',
            confirmText: 'Reject',
            confirmColor: '#dc3545',
            isRequired: true,
            label: 'Rejection Reason (Required)',
            placeholder: 'Please explain why you are rejecting this request...',
            icon: 'error'
        },
        'hold': {
            title: '⏸ Hold Reason',
            titleColor: '#ffc107',
            confirmText: 'Hold Request',
            confirmColor: '#ffc107',
            isRequired: true,
            label: 'Reason for Holding (Required)',
            placeholder: 'Explain why you are putting this request on hold...',
            icon: 'warning'
        },
        'adjusted': {
            title: '⚙ Adjustment Note',
            titleColor: '#17a2b8',
            confirmText: 'Adjust',
            confirmColor: '#17a2b8',
            isRequired: true,
            label: 'Adjustment Details (Required)',
            placeholder: 'Describe the adjustment details...',
            icon: 'info'
        }
    };

    const config = actionConfig[approvalAction] || actionConfig['approved'];
    const requiredBadge = config.isRequired 
        ? '<span class="label-badge badge-required">Required</span>' 
        : '<span class="label-badge badge-optional">Optional</span>';

    Swal.fire({
        title: config.title,
        html: `
            <div class="approval-comment-container">
                <div class="approval-comment-label">
                    <span class="label-text">${config.label}</span>
                    ${requiredBadge}
                </div>
                <textarea 
                    id="approvalCommentText" 
                    class="approval-comment-textarea"
                    placeholder="${config.placeholder}"
                    maxlength="5000"
                ></textarea>
                <div class="approval-comment-counter">
                    <span class="counter-text">Character count:</span>
                    <span class="counter-number"><span id="charCount">0</span>/5000</span>
                </div>
            </div>
        `,
        icon: config.icon,
        showCancelButton: true,
        confirmButtonText: config.confirmText,
        confirmButtonColor: config.confirmColor,
        cancelButtonText: 'Cancel',
        allowOutsideClick: false,
        allowEscapeKey: false,
        customClass: {
            popup: 'approval-comment-popup'
        },
        didOpen: () => {
            // Set up character counter
            const textarea = document.getElementById('approvalCommentText');
            const charCountSpan = document.getElementById('charCount');
            const counterNumber = document.querySelector('.counter-number');

            textarea.addEventListener('input', function() {
                const count = this.value.length;
                charCountSpan.textContent = count;
                
                // Change color if limit reached
                if (count >= 4800) {
                    counterNumber.classList.add('counter-limit-reached');
                } else {
                    counterNumber.classList.remove('counter-limit-reached');
                }
            });

            // Focus on textarea
            setTimeout(() => textarea.focus(), 100);
        },
        preConfirm: () => {
            const commentText = document.getElementById('approvalCommentText').value.trim();
            
            // Check if required comment is empty
            if (config.isRequired && !commentText) {
                Swal.showValidationMessage(`${config.label.split(' ')[0]} is required!`);
                return false;
            }

            return {
                requestId: requestId,
                requestType: requestType,
                approvalAction: approvalAction,
                approvalComment: commentText
            };
        }
    }).then((result) => {
        if (result.isConfirmed && typeof onSubmit === 'function') {
            // Call the callback with the form data
            onSubmit(result.value);
        }
    });
}

/**
 * Show Approval Alert - Generic alert message
 * 
 * @param {string} message - Message to display
 * @param {string} type - Type of alert (success, error, warning, info)
 * @param {function} callback - Callback function
 */
function showApprovalAlert(message, type = 'success', callback = null) {
    let icon = type;
    let color = '#28a745';
    
    if (type === 'error' || type === 'danger') {
        icon = 'error';
        color = '#dc3545';
    } else if (type === 'warning') {
        color = '#ffc107';
    } else if (type === 'info') {
        color = '#17a2b8';
    }

    Swal.fire({
        icon: icon,
        title: message,
        color: color,
        confirmButtonColor: color,
        timer: 3000,
        timerProgressBar: true
    }).then(() => {
        if (typeof callback === 'function') {
            callback();
        }
    });
}
</script>
