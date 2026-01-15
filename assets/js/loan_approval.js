/**
 * MODIFICATION SUMMARY (015-loan_approval.js):
 * 1.  NEW FUNCTION (modifyAndApproveLoanHRAssistant):
 * - Created a new function for the HR Assistant to modify and approve loans.
 * - This function is a copy of the GM's `modifyAndApproveLoan` but calls the new `modify_and_approve_loan_hr_assistant` AJAX action.
 * 2.  UPDATED `finalizeLoan` FUNCTION:
 * - This function has been completely rewritten to support the new finalization process.
 * - It now opens a Swal modal with a form containing fields for "Receipt ID" and a file input for "Attachment".
 * - Both new fields are mandatory and include client-side validation.
 * - It uses the `FormData` object to correctly handle and submit the file upload along with the receipt ID.
 * 3.  IMPROVED USER EXPERIENCE: The new modals provide a clear and guided process for the HR Assistant's modification and the Finance Assistant's finalization steps.
 * 4.  ADDED REAL-TIME CALCULATION FOR HR ASSISTANT: The `modifyAndApproveLoanHRAssistant` modal now includes a read-only field that displays the calculated monthly deduction, which updates automatically as the loan amount or installments are changed.
 * 5.  ADDED EOS DETAILS TO HR ASSISTANT MODAL: The function now fetches and displays the End of Service and max loan amount details, and validates the new loan amount against this limit.
 * 6.  ADDED EOS DETAILS TO GM MODAL: The `modifyAndApproveLoan` function for the GM has been updated to fetch and display the End of Service and max loan amount details, and validates the new loan amount against this limit.
 */

function approveLoanRequest(loanId, role, requestedAmount, userType, approvalLevel, payerEmpId, currentUserId, currentInstallments = 1) {
    // Check if this is GM (user_type = 'gm')
    const isGM = (userType === 'gm');
    
    // Check if this is Finance Manager (user_type = 'finance')
    const isFinanceManager = (userType === 'finance');
    
    // Check if current user is the assigned payer
    const isPayer = (payerEmpId > 0 && payerEmpId === currentUserId);
    
    if (isGM) {
        // Get employee ID from database for EOS details
        // We need to fetch the loan details to get employee ID
        $.ajax({
            url: './includes/ajaxFile/ajaxLoan.php',
            type: 'POST',
            data: { 
                ajaxType: 'get_loan_details_for_modification',
                loan_id: loanId
            },
            dataType: 'JSON',
        }).done(function(response) {
            if (response.status === 'success' && response.emp_id) {
                // Call the modify and approve function for GM with employee ID and correct installments
                modifyAndApproveLoan(loanId, requestedAmount, currentInstallments, response.emp_id);
            } else {
                Swal.fire({
                    title: __('error_title') || 'Error',
                    text: __('failed_to_load_employee_details') || 'Failed to load employee details',
                    icon: 'error'
                });
            }
        }).fail(function() {
            Swal.fire({
                title: __('error_title') || 'Error',
                text: __('failed_to_load_loan_details') || 'Failed to load loan details',
                icon: 'error'
            });
        });
    } else if (isPayer) {
        // Show payment proof upload modal for assigned payer
        Swal.fire({
            title: __('process_payment_upload_proof') || 'Process Payment & Upload Proof',
            html: `
                <form id="payerApprovalForm" class="text-left" enctype="multipart/form-data">
                    <p class="alert alert-warning text-center"><i class="fa fa-exclamation-triangle"></i> ${__('payer_notice') || 'You have been assigned to process this payment. Please enter the final amount and upload payment proof.'}</p>
                    <div class="form-group">
                        <label for="final_approved_amount">${__('final_approved_amount_sar') || 'Final Approved Amount (SAR)'} <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" id="final_approved_amount" name="final_approved_amount" class="form-control" placeholder="${__('enter_amount_actually_paid') || 'Enter amount actually paid'}" value="${requestedAmount}" required>
                        <small class="form-text text-muted">${__('requested_amount') || 'Requested Amount'}: ${parseFloat(requestedAmount).toFixed(2)} SAR</small>
                    </div>
                    <div class="form-group">
                        <label for="payment_proof">${__('payment_proof_document') || 'Payment Proof Document'} <span class="text-danger">*</span></label>
                        <input type="file" id="payment_proof" name="payment_proof" class="form-control-file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
                        <small class="form-text text-muted">${__('accepted_formats') || 'Accepted: PDF, JPG, PNG, DOC, DOCX'}</small>
                    </div>
                    <div class="form-group">
                        <label for="approval_comment">${__('payment_notes') || 'Payment Notes'}</label>
                        <textarea id="approval_comment" name="approval_comment" class="form-control" rows="3" placeholder="${__('write_payment_notes') || 'Please provide notes for this payment processing...'}" maxlength="5000"></textarea>
                        <small class="form-text text-muted"><span id="char-count">0</span>/5000 ${__('characters')}</small>
                    </div>
                </form>
            `,
            width: '40%',
            showCancelButton: true,
            confirmButtonText: __('confirm_payment_upload_proof') || 'Confirm Payment & Upload Proof',
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#dc3545',
            cancelButtonText: __('cancel'),
            showLoaderOnConfirm: true,
            allowOutsideClick: false,
            didOpen: () => {
                // Character counter
                $('#approval_comment').on('input', function() {
                    $('#char-count').text($(this).val().length);
                });
                // Confirm button enabled by default, disables only if value changes
                const $amount = $('#final_approved_amount');
                function toggleConfirmBtn() {
                    const confirmBtn = Swal.getConfirmButton();
                    let val = $amount.val();
                    if (typeof val === 'string') val = val.trim();
                    val = parseFloat(val);
                    const approved = parseFloat(`${requestedAmount}`);
                    if (!isNaN(val) && !isNaN(approved) && Math.abs(val - approved) < 0.01) {
                        confirmBtn.disabled = false;
                    } else {
                        confirmBtn.disabled = true;
                    }
                }
                $amount.on('input keyup change focus blur', toggleConfirmBtn);
                // Initial state after modal open
                setTimeout(toggleConfirmBtn, 200);
            },
            preConfirm: () => {
                const form = document.getElementById('payerApprovalForm');
                const formData = new FormData(form);
                formData.append('ajaxType', 'approve_loan');
                formData.append('loan_id', loanId);
                formData.append('approver_role', role);

                const approvedAmount = formData.get('final_approved_amount');
                const paymentProof = document.getElementById('payment_proof').files[0];
                const approvalComment = formData.get('approval_comment').trim();

                if (!approvedAmount || parseFloat(approvedAmount) <= 0) {
                    Swal.showValidationMessage(__('approved_amount_required') || 'Final amount is required and must be greater than zero');
                    return false;
                }
                if (parseFloat(approvedAmount) !== parseFloat(`${requestedAmount}`)) {
                    Swal.showValidationMessage(__('amount_must_match_approved') || 'Amount must match the approved amount.');
                    return false;
                }
                if (!paymentProof) {
                    Swal.showValidationMessage(__('payment_proof_document_is_required') || 'Payment proof document is required');
                    return false;
                }
                // Approval comment is optional - no validation required
                return $.ajax({
                    url: './includes/ajaxFile/ajaxLoan.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'JSON',
                })
                .fail(function(jqXHR, textStatus) {
                    const error = handleAjaxFailure(jqXHR, textStatus);
                    Swal.showValidationMessage(`${__('request_failed')} ${error.message}`);
                });
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const response = result.value;
                Swal.fire({
                    title: response.title,
                    text: response.message,
                    icon: response.type,
                    allowOutsideClick: false
                }).then(() => {
                    if (response.status === 'success') {
                        location.reload();
                    }
                });
            }
        });
    } else if (isFinanceManager) {
        // Show payer selection, payment proof and final amount modal for Finance Manager
        // First, fetch list of finance staff who can be payers
        $.ajax({
            url: './includes/ajaxFile/ajaxLoan.php',
            type: 'POST',
            data: { ajaxType: 'get_finance_staff' },
            dataType: 'JSON',
        }).done(function(staffResponse) {
            let payerOptions = '<option value="">-- Select Payer --</option>';
            if (staffResponse.status === 'success' && staffResponse.staff) {
                staffResponse.staff.forEach(function(staff) {
                    payerOptions += `<option value="${staff.emp_id}">${staff.name} (${staff.emp_id})</option>`;
                });
            }
            
            Swal.fire({
                title: __('finance_manager_payment_processing') || 'Finance Manager - Assign Payer',
                html: `
                    <form id="financeApprovalForm" class="text-left">
                        <p class="alert alert-info text-center"><i class="fa fa-info-circle"></i> ${__('finance_manager_approval_notice') || 'Select the finance staff member who will process the payment and upload proof.'}</p>
                        
                        <div class="form-group">
                            <label for="payer_emp_id">${__('who_will_process_payment') || 'Who Will Process Payment?'} <span class="text-danger">*</span></label>
                            <select id="payer_emp_id" name="payer_emp_id" class="form-control" required>
                                ${payerOptions}
                            </select>
                            <small class="form-text text-muted">${__('payer_hint') || 'The selected person will handle payment processing and upload payment proof'}</small>
                        </div>
                        
                        <div class="form-group">
                            <label>${__('requested_amount') || 'Requested Amount'}</label>
                            <div class="form-control-plaintext"><strong>${parseFloat(requestedAmount).toFixed(2)} SAR</strong></div>
                            <small class="form-text text-muted">${__('payer_will_confirm') || 'The payer will confirm the final amount when uploading proof'}</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="approval_comment">${__('approval_comment') || 'Approval Comment'}</label>
                            <textarea id="approval_comment" name="approval_comment" class="form-control" rows="3" placeholder="${__('write_comment') || 'Please provide your approval reasoning...'}" maxlength="5000"></textarea>
                            <small class="form-text text-muted"><span id="char-count">0</span>/5000 ${__('characters')}</small>
                        </div>
                    </form>
                `,
                width: '40%',
                showCancelButton: true,
                confirmButtonText: __('approve_assign_payer') || 'Approve & Assign Payer',
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#dc3545',
                cancelButtonText: __('cancel'),
                showLoaderOnConfirm: true,
                allowOutsideClick: false,
                didOpen: () => {
                    // Character counter
                    $('#approval_comment').on('input', function() {
                        $('#char-count').text($(this).val().length);
                    });
                },
                willClose: (result) => {
                    // Show custom loader when confirmed
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: __('processing'),
                            html: __('assigning_payer_and_sending_notifications') || 'Assigning payer and sending notification emails...',
                            icon: 'info',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                    }
                },
                preConfirm: () => {
                    const payerEmpId = document.getElementById('payer_emp_id').value;
                    const approvalComment = document.getElementById('approval_comment').value.trim();

                    if (!payerEmpId) {
                        Swal.showValidationMessage(__('payer_required') || 'Please select who will process the payment');
                        return false;
                    }

                    // Approval comment is optional - no validation required

                    return $.ajax({
                        url: './includes/ajaxFile/ajaxLoan.php',
                        type: 'POST',
                        data: {
                            ajaxType: 'approve_loan',
                            loan_id: loanId,
                            approver_role: role,
                            payer_emp_id: payerEmpId,
                            approval_comment: approvalComment
                        },
                        dataType: 'JSON',
                    })
                    .fail(function(jqXHR, textStatus) {
                        const error = handleAjaxFailure(jqXHR, textStatus);
                        Swal.showValidationMessage(`${__('request_failed')} ${error.message}`);
                    });
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const response = result.value;
                    Swal.fire({
                        title: response.title,
                        text: response.message,
                        icon: response.type,
                        allowOutsideClick: false
                    }).then(() => {
                        if (response.status === 'success') {
                            location.reload();
                        }
                    });
                }
            });
        }).fail(function() {
            Swal.fire({
                title: __('error_title'),
                text: __('failed_to_load_payer_list') || 'Failed to load payer list',
                icon: 'error'
            });
        });
    } else {
        // Normal approval for other levels
    Swal.fire({
        title: __('confirm_approval_title'),
        html: `
            <div class="text-left">
                <p>${__('confirm_approve_loan_text')}</p>
                <hr>
                <h6 class="text-primary mb-3">
                    <i class="fa fa-comment"></i> ${__('approval_comment') || 'Approval Comment'}
                </h6>
                <div class="form-group">
                    <textarea id="swal_approval_comment" class="form-control" rows="4" placeholder="${__('write_comment') || 'Please explain your decision and any relevant observations...'}" style="width: 100%; padding: .375rem .75rem; border: 1px solid #ced4da; border-radius: .25rem; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto; font-size: 14px;"></textarea>
                    <small class="form-text text-muted">
                        <span id="char-count">0</span>/5000 ${__('characters')}
                    </small>
                </div>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#dc3545',
        confirmButtonText: __('yes_approve_it_button'),
        allowOutsideClick: false,
        willOpen: () => {
            // Character counter for approval comment
            $(document).on('input', '#swal_approval_comment', function() {
                const currentLength = $(this).val().length;
                const maxLength = 5000;
                
                $('#char-count').text(currentLength);
                
                // Change color if approaching limit
                if (currentLength > maxLength * 0.9) {
                    $('#char-count').css('color', '#dc3545'); // Red warning
                } else if (currentLength > maxLength * 0.7) {
                    $('#char-count').css('color', '#ffc107'); // Yellow warning
                } else {
                    $('#char-count').css('color', '#6c757d'); // Default gray
                }
            });
        },
        preConfirm: () => {
            const approvalComment = $('#swal_approval_comment').val().trim();
            
            // Approval comment is optional - return whatever was entered
            return approvalComment;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const approvalComment = result.value;
            
            // Show loader while processing
            Swal.fire({
                title: __('processing_approval'),
                text: __('please_wait_processing'),
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            // Send the approval request
            sendLoanUpdate(loanId, role, 'approve_loan', { approval_comment: approvalComment }).then((response) => {
                Swal.fire({
                    title: response.title,
                    text: response.message,
                    icon: response.type,
                    allowOutsideClick: false
                }).then(() => {
                    if (response.status === 'success') {
                        location.reload();
                    }
                });
            }).catch((error) => {
                Swal.fire({
                    title: __('error_title'),
                    text: error.message || __('unknown_error_occurred'),
                    icon: 'error',
                    allowOutsideClick: false
                });
            });
        }
    });
    }
}

function rejectLoanRequest(loanId, role) {
    Swal.fire({
        title: __('confirm_rejection_title'),
        input: 'textarea',
        inputLabel: __('provide_rejection_reason_label'),
        inputPlaceholder: __('enter_rejection_reason_placeholder'),
        showCancelButton: true,
        confirmButtonText: __('submit_rejection_button'),
        confirmButtonColor: '#dc3545',
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        inputValidator: (value) => {
            if (!value) {
                return __('rejection_reason_required_validation');
            }
        },
        preConfirm: (reason) => {
            return sendLoanUpdate(loanId, role, 'reject_loan', { rejection_note: reason ,cancelButtonColor:'#d33',cancelButtonText:__('cancel')});
        }
    ,cancelButtonColor:'#d33',cancelButtonText:__('cancel')}).then((result) => {
        if (result.isConfirmed) {
            const response = result.value;
            Swal.fire({
                title: response.title,
                text: response.message,
                icon: response.type,
                allowOutsideClick: false
            }).then(() => {
                if (response.status === 'success') {
                    location.reload();
                }
            });
        }
    });
}

function finalizeLoan(loanId) {
    Swal.fire({
        title: __('finalize_and_disburse_loan_title'),
        html: `
            <form id="finalizeLoanForm" class="text-left" enctype="multipart/form-data">
                <p class="text-muted">${__('finalize_loan_notice')}</p>
                <div class="form-group">
                    <label for="finalize_receipt_id">${__('receipt_id')}</label>
                    <input type="text" id="finalize_receipt_id" name="receipt_id" class="form-control" placeholder="${__('enter_receipt_id_placeholder')}" required>
                </div>
                <div class="form-group">
                    <label for="finalize_attachment">${__('receipt_attachment_label')}</label>
                    <input type="file" id="finalize_attachment" name="attachment" class="form-control-file" required>
                </div>
            </form>
        `,
        showCancelButton: true,
        confirmButtonText: __('submit_and_finalize_button'),
        confirmButtonColor: '#17a2b8',
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        preConfirm: () => {
            const form = document.getElementById('finalizeLoanForm');
            const formData = new FormData(form);
            formData.append('ajaxType', 'finalize_loan');
            formData.append('loan_id', loanId);

            const receiptId = formData.get('receipt_id');
            const attachment = document.getElementById('finalize_attachment').files[0];

            if (!receiptId || !attachment) {
                Swal.showValidationMessage(__('receipt_id_and_attachment_required_validation'));
                return false;
            }

            return $.ajax({
                url: './includes/ajaxFile/ajaxLoan.php',
                type: 'POST',
                data: formData,
                processData: false, // Important for FormData
                contentType: false, // Important for FormData
                dataType: 'JSON',
            })
            .fail(function(jqXHR, textStatus) {
                const error = handleAjaxFailure(jqXHR, textStatus);
                Swal.showValidationMessage(`${__('request_failed')} ${error.message}`);
            });
        }
    ,cancelButtonColor:'#d33',cancelButtonText:__('cancel')}).then((result) => {
        if (result.isConfirmed) {
            const response = result.value; // AJAX response is in result.value
            Swal.fire({
                title: response.title,
                text: response.message,
                icon: response.type,
                allowOutsideClick: false
            }).then(() => {
                if (response.status === 'success') {
                    location.reload();
                }
            });
        }
    });
}


function sendLoanUpdate(loanId, role, ajaxType, additionalData = {}) {
    const data = {
        ajaxType: ajaxType,
        loan_id: loanId,
        approver_role: role,
        ...additionalData
    };

    return $.ajax({
        url: './includes/ajaxFile/ajaxLoan.php',
        type: 'POST',
        dataType: 'JSON',
        data: data,
    });
}


function handleAjaxFailure(jqXHR, textStatus) {
    let message = __('unknown_error_occurred');
    if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
        message = jqXHR.responseJSON.message;
    } else if (textStatus === 'timeout') {
        message = __('request_timed_out');
    } else if (textStatus === 'parsererror') {
        message = __('error_parsing_response');
    } else if (jqXHR.status === 0) {
        message = __('could_not_connect_server');
    } else {
        message = `${__('error_title')}: ${jqXHR.status} ${jqXHR.statusText}`;
    }
    return { title: __('error_title'), message: message, type: 'error' };
}

async function modifyAndApproveLoan(loanId, currentAmount, currentInstallments, empId) {
    Swal.fire({
        title: __('loading_loan_details'),
        text: __('calculating_eos_wait_message'),
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    try {
        const response = await $.ajax({
            url: './includes/ajaxFile/ajaxLoan.php',
            type: 'POST',
            data: { emp_id: empId, ajaxType: 'get_loan_details' },
            dataType: "json",
        });

        if (response.status === 'success') {
            const endOfService = response.end_of_service;
            const maxLoanAmount = response.max_loan_amount;

            let installmentOptions = '';
            for (let i = 1; i <= 12; i++) {
                const selected = (i == currentInstallments) ? 'selected' : '';
                installmentOptions += `<option value="${i}" ${selected}>${i} ${i > 1 ? __('months') : __('month')}</option>`;
            }

            Swal.fire({
                title: __('modify_and_approve_loan_title'),
                html: `
                    <div class="alert alert-info text-left">
                        <h6 class="alert-heading">${__('end_of_service_benefit')}</h6>
                        <p class="mb-0">${__('total_calculated')} <strong>${endOfService.toLocaleString('en-US', { style: 'currency', currency: 'SAR' })}</strong></p>
                        <hr>
                        <p class="mb-0">${__('max_loan_amount_40_percent')} <strong>${maxLoanAmount.toLocaleString('en-US', { style: 'currency', currency: 'SAR' })}</strong></p>
                    </div>
                    <div class="alert alert-warning text-left">
                        <strong>${__('applied_amount')}:</strong> <span class="text-dark">${currentAmount.toLocaleString('en-US', { style: 'currency', currency: 'SAR' })}</span><br>
                        <small class="text-muted">${__('original_employee_request')}</small>
                    </div>
                    <form id="modifyLoanForm" class="text-left">
                        <div class="form-group">
                            <label for="new_loan_amount">${__('approved_loan_amount')} <span class="text-danger">*</span></label>
                            <input type="number" id="new_loan_amount" class="form-control" value="${currentAmount}" required max="${maxLoanAmount}">
                            <small id="loan_feedback_gm" class="form-text text-danger"></small>
                        </div>
                        <div class="form-group">
                            <label for="new_installments">${__('number_of_installments_label')}</label>
                            <select id="new_installments" class="form-control" required>
                                ${installmentOptions}
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="monthly_deduction_display">${__('monthly_deduction_label')}</label>
                            <input type="text" id="monthly_deduction_display" class="form-control" readonly style="font-weight: bold; background-color: #e9ecef;">
                        </div>
                        <div class="form-group">
                            <label for="approval_comment_gm">${__('approval_comment') || 'Approval Comment'}</label>
                            <textarea id="approval_comment_gm" class="form-control" rows="3" placeholder="${__('write_comment') || 'Please provide your approval notes and reason for any modifications...'}" maxlength="5000"></textarea>
                            <small class="form-text text-muted"><span id="char-count-gm">0</span>/5000 ${__('characters')}</small>
                        </div>
                    </form>
                `,
                showCancelButton: true,
                confirmButtonText: __('submit_and_approve_button'),
                confirmButtonColor: '#28a745',
                showLoaderOnConfirm: true,
                allowOutsideClick: false,
                didOpen: () => {
                    const amountInput = $('#new_loan_amount');
                    const installmentsSelect = $('#new_installments');
                    const deductionDisplay = $('#monthly_deduction_display');
                    const feedback = $('#loan_feedback_gm');
                    const confirmButton = Swal.getConfirmButton();
                    const commentField = $('#approval_comment_gm');

                    // Character counter
                    commentField.on('input', function() {
                        $('#char-count-gm').text($(this).val().length);
                    });

                    function calculateAndDisplayDeduction() {
                        const amount = parseFloat(amountInput.val());
                        const installments = parseInt(installmentsSelect.val());
                        if (!isNaN(amount) && amount > 0 && !isNaN(installments) && installments > 0) {
                            deductionDisplay.val((amount / installments).toFixed(2) + ' ' + __('sar_currency'));
                        } else {
                            deductionDisplay.val(__('not_applicable'));
                        }
                    }

                    function validateAmount() {
                        const amount = parseFloat(amountInput.val());
                        if (isNaN(amount) || amount <= 0 || amount > maxLoanAmount) {
                            if (amount > maxLoanAmount) {
                                feedback.text(__('amount_exceeds_max_validation'));
                            } else {
                                feedback.text('');
                            }
                            confirmButton.disabled = true;
                        } else {
                            feedback.text('');
                            confirmButton.disabled = false;
                        }
                    }

                    amountInput.on('input', () => {
                        calculateAndDisplayDeduction();
                        validateAmount();
                    });
                    installmentsSelect.on('change', calculateAndDisplayDeduction);

                    calculateAndDisplayDeduction();
                    validateAmount();
                },
                preConfirm: () => {
                    const newAmount = $('#new_loan_amount').val();
                    const newInstallments = $('#new_installments').val();
                    const approvalComment = $('#approval_comment_gm').val().trim();

                    if (!newAmount || !newInstallments || parseFloat(newAmount) <= 0 || parseInt(newInstallments) <= 0) {
                        Swal.showValidationMessage(__('valid_amount_installments_validation'));
                        return false;
                    }
                    if (parseFloat(newAmount) > maxLoanAmount) {
                         Swal.showValidationMessage(`${__('loan_amount_cannot_exceed_max_validation')} ${maxLoanAmount.toFixed(2)}.`);
                        return false;
                    }

                    return $.ajax({
                        url: './includes/ajaxFile/ajaxLoan.php',
                        type: 'POST',
                        dataType: 'JSON',
                        data: {
                            ajaxType: 'modify_and_approve_loan',
                            loan_id: loanId,
                            loan_amount: newAmount,
                            installments: newInstallments,
                            approval_comment: approvalComment
                        }
                    }).fail((jqXHR, textStatus) => {
                        const error = handleAjaxFailure(jqXHR, textStatus);
                        Swal.showValidationMessage(`${__('request_failed')} ${error.message}`);
                    });
                }
            ,cancelButtonColor:'#d33',cancelButtonText:__('cancel')}).then((result) => {
                if (result.isConfirmed) {
                    const response = result.value;
                    Swal.fire({
                        title: response.title,
                        text: response.message,
                        icon: response.type,
                        allowOutsideClick: false
                    }).then(() => {
                        if (response.status === 'success') {
                            location.reload();
                        }
                    });
                }
            });
        } else {
            throw new Error(response.message || __('failed_to_fetch_loan_details'));
        }
    } catch (error) {
        Swal.fire({ icon: 'error', title: __('error_title'), text: error.message ,allowOutsideClick:false});
    }
}

// UPDATED FUNCTION FOR HR ASSISTANT
async function modifyAndApproveLoanHRAssistant(loanId, currentAmount, currentInstallments, empId) {
    Swal.fire({
        title: __('loading_loan_details'),
        text: __('calculating_eos_wait_message'),
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    try {
        const response = await $.ajax({
            url: './includes/ajaxFile/ajaxLoan.php',
            type: 'POST',
            data: { emp_id: empId, ajaxType: 'get_loan_details' },
            dataType: "json",
        });

        if (response.status === 'success') {
            const endOfService = response.end_of_service;
            const maxLoanAmount = response.max_loan_amount;

            let installmentOptions = '';
            for (let i = 1; i <= 12; i++) {
                const selected = (i == currentInstallments) ? 'selected' : '';
                installmentOptions += `<option value="${i}" ${selected}>${i} ${i > 1 ? __('months') : __('month')}</option>`;
            }

            Swal.fire({
                title: __('hr_asst_modify_approve_title'),
                html: `
                    <div class="alert alert-info text-left">
                        <h6 class="alert-heading">${__('end_of_service_benefit')}</h6>
                        <p class="mb-0">${__('total_calculated')} <strong>${endOfService.toLocaleString('en-US', { style: 'currency', currency: 'SAR' })}</strong></p>
                        <hr>
                        <p class="mb-0">${__('max_loan_amount_40_percent')} <strong>${maxLoanAmount.toLocaleString('en-US', { style: 'currency', currency: 'SAR' })}</strong></p>
                    </div>
                    <div class="alert alert-warning text-left">
                        <strong>${__('applied_amount')}:</strong> <span class="text-dark">${currentAmount.toLocaleString('en-US', { style: 'currency', currency: 'SAR' })}</span><br>
                        <small class="text-muted">${__('original_employee_request')}</small>
                    </div>
                    <form id="modifyLoanFormHR" class="text-left">
                        <div class="form-group">
                            <label for="new_loan_amount_hr">${__('approved_loan_amount')} <span class="text-danger">*</span></label>
                            <input type="number" id="new_loan_amount_hr" class="form-control" value="${currentAmount}" required max="${maxLoanAmount}">
                            <small id="loan_feedback_hr" class="form-text text-danger"></small>
                        </div>
                        <div class="form-group">
                            <label for="new_installments_hr">${__('number_of_installments_label')}</label>
                            <select id="new_installments_hr" class="form-control" required>
                                ${installmentOptions}
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="monthly_deduction_display_hr">${__('monthly_deduction_label')}</label>
                            <input type="text" id="monthly_deduction_display_hr" class="form-control" readonly style="font-weight: bold; background-color: #e9ecef;">
                        </div>
                    </form>
                `,
                showCancelButton: true,
                confirmButtonText: __('submit_and_approve_button'),
                confirmButtonColor: '#28a745',
                showLoaderOnConfirm: true,
                allowOutsideClick: false,
                didOpen: () => {
                    const amountInput = $('#new_loan_amount_hr');
                    const installmentsSelect = $('#new_installments_hr');
                    const deductionDisplay = $('#monthly_deduction_display_hr');
                    const feedback = $('#loan_feedback_hr');
                    const confirmButton = Swal.getConfirmButton();

                    function calculateAndDisplayDeduction() {
                        const amount = parseFloat(amountInput.val());
                        const installments = parseInt(installmentsSelect.val());
                        if (!isNaN(amount) && amount > 0 && !isNaN(installments) && installments > 0) {
                            deductionDisplay.val((amount / installments).toFixed(2) + ' ' + __('sar_currency'));
                        } else {
                            deductionDisplay.val(__('not_applicable'));
                        }
                    }
                    
                    function validateAmount() {
                        const amount = parseFloat(amountInput.val());
                        if (isNaN(amount) || amount <= 0 || amount > maxLoanAmount) {
                            if (amount > maxLoanAmount) {
                                feedback.text(__('amount_exceeds_max_validation'));
                            } else {
                                feedback.text('');
                            }
                            confirmButton.disabled = true;
                        } else {
                            feedback.text('');
                            confirmButton.disabled = false;
                        }
                    }

                    amountInput.on('input', () => {
                        calculateAndDisplayDeduction();
                        validateAmount();
                    });
                    installmentsSelect.on('change', calculateAndDisplayDeduction);

                    calculateAndDisplayDeduction();
                    validateAmount();
                },
                preConfirm: () => {
                    const newAmount = $('#new_loan_amount_hr').val();
                    const newInstallments = $('#new_installments_hr').val();

                    if (!newAmount || !newInstallments || parseFloat(newAmount) <= 0 || parseInt(newInstallments) <= 0) {
                        Swal.showValidationMessage(__('valid_amount_installments_validation'));
                        return false;
                    }
                    if (parseFloat(newAmount) > maxLoanAmount) {
                         Swal.showValidationMessage(`${__('loan_amount_cannot_exceed_max_validation')} ${maxLoanAmount.toFixed(2)}.`);
                        return false;
                    }

                    return $.ajax({
                        url: './includes/ajaxFile/ajaxLoan.php',
                        type: 'POST',
                        dataType: 'JSON',
                        data: {
                            ajaxType: 'modify_and_approve_loan_hr_assistant',
                            loan_id: loanId,
                            loan_amount: newAmount,
                            installments: newInstallments
                        }
                    }).fail((jqXHR, textStatus) => {
                        const error = handleAjaxFailure(jqXHR, textStatus);
                        Swal.showValidationMessage(`${__('request_failed')} ${error.message}`);
                    });
                }
            ,cancelButtonColor:'#d33',cancelButtonText:__('cancel')}).then((result) => {
                if (result.isConfirmed) {
                    const response = result.value;
                    Swal.fire({
                        title: response.title,
                        text: response.message,
                        icon: response.type,
                        allowOutsideClick: false
                    }).then(() => {
                        if (response.status === 'success') {
                            location.reload();
                        }
                    });
                }
            });
        } else {
            throw new Error(response.message || __('failed_to_fetch_loan_details'));
        }
    } catch (error) {
        Swal.fire({ icon: 'error', title: __('error_title'), text: error.message ,allowOutsideClick:false});
    }
}
