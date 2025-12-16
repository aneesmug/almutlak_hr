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

function approveLoanRequest(loanId, role, requestedAmount, userType, approvalLevel) {
    // Check if this is the final payer (Level 7 - Finance Officer)
    const isFinalPayer = (approvalLevel == 7 && userType === 'finance_officer');
    
    if (isFinalPayer) {
        // Show payment proof and final amount modal for finance officer
        Swal.fire({
            title: __('final_approval_payment_proof_title') || 'Final Approval - Payment Proof Required',
            html: `
                <form id="finalApprovalForm" class="text-left" enctype="multipart/form-data">
                    <p class="alert alert-info text-center"><i class="fa fa-info-circle"></i> ${__('final_approval_notice') || 'As the final approver, you must upload payment proof and confirm the approved amount.'}</p>
                    
                    <div class="form-group">
                        <label for="final_approved_amount">${__('final_approved_amount_label') || 'Final Approved Amount (SAR)'} <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" id="final_approved_amount" name="final_approved_amount" class="form-control" placeholder="${__('enter_approved_amount') || 'Enter amount to be paid'}" value="${requestedAmount}" required>
                        <small class="form-text text-muted">${__('requested_amount_label') || 'Requested Amount'}: ${parseFloat(requestedAmount).toFixed(2)} SAR</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="payment_proof">${__('payment_proof_file_label') || 'Payment Proof Document'} <span class="text-danger">*</span></label>
                        <input type="file" id="payment_proof" name="payment_proof" class="form-control-file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
                        <small class="form-text text-muted">${__('accepted_formats') || 'Accepted: PDF, JPG, PNG, DOC, DOCX'}</small>
                    </div>
                </form>
            `,
            showCancelButton: true,
            confirmButtonText: __('approve_and_submit_button') || 'Approve & Submit',
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#dc3545',
            showLoaderOnConfirm: true,
            allowOutsideClick: false,
            preConfirm: () => {
                const form = document.getElementById('finalApprovalForm');
                const formData = new FormData(form);
                formData.append('ajaxType', 'approve_loan');
                formData.append('loan_id', loanId);
                formData.append('approver_role', role);

                const approvedAmount = formData.get('final_approved_amount');
                const paymentProof = document.getElementById('payment_proof').files[0];

                if (!approvedAmount || parseFloat(approvedAmount) <= 0) {
                    Swal.showValidationMessage(__('approved_amount_required') || 'Approved amount is required and must be greater than zero');
                    return false;
                }
                
                if (!paymentProof) {
                    Swal.showValidationMessage(__('payment_proof_required') || 'Payment proof document is required');
                    return false;
                }

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
                    <span class="text-muted">(${__('optional')})</span>
                </h6>
                <div class="form-group">
                    <textarea id="swal_approval_comment" class="form-control" rows="4" placeholder="${__('write_comment') || 'Write your comment or review for this approval (optional)...'}" style="width: 100%; padding: .375rem .75rem; border: 1px solid #ced4da; border-radius: .25rem; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto; font-size: 14px;"></textarea>
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
            const approvalComment = $('#swal_approval_comment').val() || '';
            return approvalComment.trim();
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
                    <form id="modifyLoanForm" class="text-left">
                        <div class="form-group">
                            <label for="new_loan_amount">${__('loan_amount_label')}</label>
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
                    <form id="modifyLoanFormHR" class="text-left">
                        <div class="form-group">
                            <label for="new_loan_amount_hr">${__('loan_amount_label')}</label>
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
