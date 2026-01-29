/**
 * Settlement Management Frontend
 * Handles UI for settlement approval workflow and payment processing
 */

class SettlementManager {
    constructor() {
        this.settlementData = null;
        this.currentSettlementId = null;
    }

    /**
     * Create new settlement from completed request
     */
    async createSettlement(requestInvNo, requestType, empId, settlementAmount) {
        try {
            const response = await fetch('./includes/api/settlement_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'create_settlement',
                    request_inv_no: requestInvNo,
                    request_type: requestType,
                    emp_id: empId,
                    settlement_amount: settlementAmount
                })
            });

            const result = await response.json();
            
            if (result.success) {
                this.currentSettlementId = result.settlement_id;
                Swal.fire({
                    icon: 'success',
                    title: 'Settlement Created',
                    text: result.message,
                    confirmButtonColor: '#6366f1'
                });
                return result;
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: result.message || 'Failed to create settlement'
                });
                return result;
            }
        } catch (error) {
            console.error('Error creating settlement:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Network error: ' + error.message
            });
            return { success: false };
        }
    }

    /**
     * Get settlement details and approval chain status
     */
    async getSettlementDetails(settlementId) {
        try {
            const response = await fetch('./includes/api/settlement_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'get_settlement_details',
                    settlement_id: settlementId
                })
            });

            const result = await response.json();
            if (result.status === 'success') {
                this.settlementData = result.data;
                return result.data;
            }
            return null;
        } catch (error) {
            console.error('Error getting settlement details:', error);
            return null;
        }
    }

    /**
     * Display settlement details modal
     */
    async showSettlementModal(settlementId) {
        const details = await this.getSettlementDetails(settlementId);
        if (!details) {
            Swal.fire('Error', 'Could not load settlement details', 'error');
            return;
        }

        const settlement = details.settlement;
        const approvals = details.approvals;

        let approvalsHtml = '<div class="approval-chain mt-3">';
        approvals.forEach((approval, index) => {
            const statusClass = approval.approval_status === 'pending' ? 'badge-warning' : 
                               approval.approval_status === 'approved' ? 'badge-success' : 'badge-danger';
            approvalsHtml += `
                <div class="approval-step mb-2">
                    <div class="d-flex align-items-center">
                        <span class="badge ${statusClass} mr-2">Level ${approval.approval_level}</span>
                        <span>Approver ID: ${approval.approver_id}</span>
                        ${approval.approval_date ? `<span class="ml-auto text-muted small">${new Date(approval.approval_date).toLocaleDateString()}</span>` : ''}
                    </div>
                    ${approval.approval_notes ? `<small class="text-muted">${approval.approval_notes}</small>` : ''}
                </div>
            `;
        });
        approvalsHtml += '</div>';

        const paymentStatusBadge = settlement.settlement_status === 'pending' ? 'badge-warning' :
                                   settlement.settlement_status === 'approved' ? 'badge-info' :
                                   settlement.settlement_status === 'processed' ? 'badge-success' : 'badge-danger';

        const modalHtml = `
            <div class="settlement-details">
                <h5 class="mb-3">Settlement Details</h5>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p><strong>Request Type:</strong> ${settlement.request_type.replace('_', ' ').toUpperCase()}</p>
                        <p><strong>Invoice #:</strong> ${settlement.request_inv_no}</p>
                        <p><strong>Employee ID:</strong> ${settlement.emp_id}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Amount:</strong> SAR ${parseFloat(settlement.settlement_amount).toFixed(2)}</p>
                        <p><strong>Status:</strong> <span class="badge ${paymentStatusBadge}">${settlement.settlement_status.toUpperCase()}</span></p>
                        <p><strong>Created:</strong> ${new Date(settlement.created_at).toLocaleDateString()}</p>
                    </div>
                </div>
                
                <h6 class="mb-2">Approval Chain Progress</h6>
                ${approvalsHtml}
                
                ${settlement.payment_reference ? `
                    <div class="alert alert-info mt-3">
                        <strong>Payment Reference:</strong> ${settlement.payment_reference}<br>
                        <strong>Payment Method:</strong> ${settlement.settlement_method.toUpperCase()}<br>
                        <strong>Payment Date:</strong> ${new Date(settlement.payment_date).toLocaleDateString()}
                    </div>
                ` : ''}
            </div>
        `;

        const actionButtons = [];
        
        // Check if user is current approver
        const currentApproval = approvals.find(a => a.approval_status === 'pending');
        if (currentApproval) {
            actionButtons.push({
                text: '✓ Approve',
                handler: () => this.showApproveModal(settlementId)
            });
            actionButtons.push({
                text: '✗ Reject',
                handler: () => this.showRejectModal(settlementId)
            });
        }

        // If all approvals done, show payment processing option
        if (settlement.settlement_status === 'approved' && details.approved_count === details.total_approvers) {
            actionButtons.push({
                text: '💳 Process Payment',
                handler: () => this.showPaymentModal(settlementId)
            });
        }

        Swal.fire({
            title: 'Settlement Details',
            html: modalHtml,
            width: 700,
            confirmButtonText: 'Close',
            showDenyButton: actionButtons.length > 0,
            allowOutsideClick: false,
            didOpen: () => {
                // Add action buttons dynamically
                if (actionButtons.length > 0) {
                    const container = document.querySelector('.swal2-actions');
                    actionButtons.forEach(btn => {
                        const button = document.createElement('button');
                        button.className = 'btn btn-sm btn-primary ml-2';
                        button.textContent = btn.text;
                        button.onclick = btn.handler;
                        container.appendChild(button);
                    });
                }
            }
        });
    }

    /**
     * Show approval modal
     */
    showApproveModal(settlementId) {
        Swal.fire({
            title: 'Approve Settlement',
            html: `
                <div class="form-group">
                    <label for="approvalNotes">Approval Notes (Optional):</label>
                    <textarea id="approvalNotes" class="form-control" rows="3" placeholder="Add any notes about this approval"></textarea>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Approve',
            confirmButtonColor: '#28a745',
            cancelButtonText: 'Cancel',
            preConfirm: () => {
                const notes = document.getElementById('approvalNotes').value;
                return this.approveSettlement(settlementId, notes);
            }
        });
    }

    /**
     * Show rejection modal
     */
    showRejectModal(settlementId) {
        Swal.fire({
            title: 'Reject Settlement',
            html: `
                <div class="form-group">
                    <label for="rejectReason">Rejection Reason:</label>
                    <textarea id="rejectReason" class="form-control" rows="3" placeholder="Please provide reason for rejection" required></textarea>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Reject',
            confirmButtonColor: '#dc3545',
            cancelButtonText: 'Cancel',
            preConfirm: () => {
                const reason = document.getElementById('rejectReason').value;
                if (!reason.trim()) {
                    Swal.showValidationMessage('Reason is required');
                    return false;
                }
                return this.rejectSettlement(settlementId, reason);
            }
        });
    }

    /**
     * Show payment processing modal
     */
    showPaymentModal(settlementId) {
        Swal.fire({
            title: 'Process Payment',
            html: `
                <div class="form-group">
                    <label for="paymentMethod">Payment Method:</label>
                    <select id="paymentMethod" class="form-control">
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="cash">Cash</option>
                        <option value="check">Check</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="paymentReference">Payment Reference/Receipt:</label>
                    <input type="text" id="paymentReference" class="form-control" placeholder="e.g., Receipt #12345 or Transfer Reference">
                </div>
            `,
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: 'Process Payment',
            confirmButtonColor: '#007bff',
            cancelButtonText: 'Cancel',
            preConfirm: () => {
                const method = document.getElementById('paymentMethod').value;
                const reference = document.getElementById('paymentReference').value;
                if (!reference.trim()) {
                    Swal.showValidationMessage('Payment reference is required');
                    return false;
                }
                return this.processPayment(settlementId, method, reference);
            }
        });
    }

    /**
     * Approve settlement
     */
    async approveSettlement(settlementId, notes) {
        try {
            const response = await fetch('./includes/api/settlement_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'approve_settlement',
                    settlement_id: settlementId,
                    notes: notes
                })
            });

            const result = await response.json();
            
            if (result.success) {
                const message = result.is_final ? 
                    'Settlement approved - ready for payment processing' : 
                    'Approval recorded - forwarding to next approver';
                
                Swal.fire({
                    icon: 'success',
                    title: 'Approved',
                    text: message,
                    confirmButtonColor: '#28a745'
                }).then(() => {
                    this.showSettlementModal(settlementId);
                });
            } else {
                Swal.fire('Error', result.message || 'Failed to approve', 'error');
            }
            return result;
        } catch (error) {
            console.error('Error approving settlement:', error);
            Swal.fire('Error', 'Network error: ' + error.message, 'error');
            return { success: false };
        }
    }

    /**
     * Reject settlement
     */
    async rejectSettlement(settlementId, reason) {
        try {
            const response = await fetch('./includes/api/settlement_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'reject_settlement',
                    settlement_id: settlementId,
                    reason: reason
                })
            });

            const result = await response.json();
            
            if (result.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Rejected',
                    text: 'Settlement has been rejected',
                    confirmButtonColor: '#dc3545'
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('Error', result.message || 'Failed to reject', 'error');
            }
            return result;
        } catch (error) {
            console.error('Error rejecting settlement:', error);
            Swal.fire('Error', 'Network error: ' + error.message, 'error');
            return { success: false };
        }
    }

    /**
     * Process payment
     */
    async processPayment(settlementId, method, reference) {
        try {
            const response = await fetch('./includes/api/settlement_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'process_payment',
                    settlement_id: settlementId,
                    payment_method: method,
                    payment_reference: reference
                })
            });

            const result = await response.json();
            
            if (result.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Payment Processed',
                    text: 'Settlement payment has been processed successfully',
                    confirmButtonColor: '#007bff'
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('Error', result.message || 'Failed to process payment', 'error');
            }
            return result;
        } catch (error) {
            console.error('Error processing payment:', error);
            Swal.fire('Error', 'Network error: ' + error.message, 'error');
            return { success: false };
        }
    }

    /**
     * Get settlement list for employee
     */
    async getEmployeeSettlements(empId = null, status = 'all') {
        try {
            const response = await fetch('./includes/api/settlement_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'get_employee_settlements',
                    emp_id: empId || '',
                    status: status
                })
            });

            const result = await response.json();
            return result.settlements || [];
        } catch (error) {
            console.error('Error getting employee settlements:', error);
            return [];
        }
    }
}

// Initialize global settlement manager
const settlementManager = new SettlementManager();
