/**
 * MODIFICATION SUMMARY (009-loanHandling.js):
 *
 * 1. ADDED `applyEmergencyLoan` FUNCTION: A new event handler for the ".applyEmergencyLoan" button.
 * - It presents a simplified modal without End of Service details.
 * - On submission, it calls the `apply_loan` AJAX action with `loan_type: 'emergency'`.
 * 2. MODIFIED `addManualPayment` FUNCTION:
 * - The modal now includes input fields for "Receipt ID" and a file input for "Attachment".
 * - It now uses the `FormData` object to handle the file upload along with other form data.
 * - The AJAX call sends this `FormData` to the backend for processing.
 * 3. REMOVED INTEREST CALCULATION: All client-side interest calculation logic has been removed.
 * 4. FETCH END OF SERVICE: Before showing the modal, an AJAX call is made to `ajaxLoan.php` to get the calculated End of Service and the maximum loan amount.
 * 5. DYNAMIC LOAN LIMITS: The modal now displays the total End of Service and the 40% maximum loan amount fetched from the server.
 * 6. INSTALLMENT SELECTION: Added a dropdown for the user to select the number of monthly installments (from 1 to 12).
 * 7. UPDATED VALIDATION: Loan amount is now validated against the server-provided maximum limit.
 * 8. SIMPLIFIED SUMMARY: The loan summary in the modal is removed as there is no interest to calculate.
 * 9. AJAX SUBMISSION: The selected number of installments is now sent along with the loan application data.
 * 10. DISABLE SUBMIT BUTTON: The "Submit Application" button is now disabled if the entered loan amount is invalid or exceeds the maximum allowed limit.
 * 11. MONTHLY DEDUCTION DISPLAY: A new read-only field shows the calculated monthly deduction, updating in real-time.
 * 12. DATEPICKER INTEGRATION: The "Start Date of Deduction" input now uses the bootstrap-datepicker library for a better user experience.
 * 13. MANDATORY RECEIPT & ATTACHMENT: In the "Add Manual Payment" modal, the "Receipt ID" and "Attachment" fields are now required.
 * 14. SPECIFIC ERROR MESSAGES: Added specific validation checks to show an error message if the Receipt ID is empty or if no attachment is selected.
 * 15. REAL-TIME PAYMENT VALIDATION: The "Add Manual Payment" modal now validates the payment amount in real-time. If the amount exceeds the remaining balance, the submit button is disabled and an error message is shown.
 * 16. LIVE RECEIPT ID CHECK: Added a real-time AJAX check on the "Receipt ID" field. It verifies if the ID is already in use and disables the submit button with an error message if it is a duplicate.
 * 17. UPDATED DISPLAY LOGIC: The decision to show End of Service details is now controlled by a boolean `show_full_details` from the server, which checks the logged-in user's session.
 * 18. ADDED MONTHLY DEDUCTION TO EMERGENCY LOAN: The emergency loan modal now also shows a real-time calculation of the monthly deduction.
 */

$(document).on('click', '.applyLoan', async function(e) {
    e.preventDefault();
    const emp_id = $(this).data('emp_id');
    const user_type = $(this).data('user_type');

    // Build new requirement form without EOS/40% blocks
    Swal.fire({
        title: '<i class="fa fa-hand-holding-usd"></i> ' + __('apply_for_loan_title'),
        html: `
            <form id="loanApplicationForm" class="text-left">
                <div class="vacation-card" style="margin-bottom: 20px;">
                    <div class="vacation-card-header">
                        <i class="fa fa-list-alt"></i>
                        ${__('loan_type_label') || 'Loan Type'} <span class="text-danger">*</span>
                    </div>
                    <div class="vac-radio-group" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-top: 15px;">
                        <div class="vac-radio-option" style="min-height: 100px;">
                            <input type="radio" id="loan_type_eos" name="loan_type" value="end_of_service" checked>
                            <label for="loan_type_eos" class="vac-radio-label" style="height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 15px;">
                                <i class="fa fa-briefcase" style="font-size: 24px; margin-bottom: 8px;"></i>
                                <span style="word-wrap: break-word; font-size: 13px;">${__('loan_type_eos') || 'End of Service'}</span>
                            </label>
                        </div>
                        <div class="vac-radio-option" style="min-height: 100px;">
                            <input type="radio" id="loan_type_housing" name="loan_type" value="housing">
                            <label for="loan_type_housing" class="vac-radio-label" style="height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 15px;">
                                <i class="fa fa-home" style="font-size: 24px; margin-bottom: 8px;"></i>
                                <span style="word-wrap: break-word; font-size: 13px;">${__('loan_type_housing') || 'Housing'}</span>
                            </label>
                        </div>
                        <div class="vac-radio-option" style="min-height: 100px;">
                            <input type="radio" id="loan_type_adv" name="loan_type" value="advance_salary">
                            <label for="loan_type_adv" class="vac-radio-label" style="height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 15px;">
                                <i class="fa fa-dollar-sign" style="font-size: 24px; margin-bottom: 8px;"></i>
                                <span style="word-wrap: break-word; font-size: 13px;">${__('loan_type_advance_salary') || 'Advance Salary'}</span>
                            </label>
                        </div>
                    </div>
                    <style>
                        @media (max-width: 768px) {
                            .vac-radio-group {
                                grid-template-columns: 1fr !important;
                            }
                        }
                    </style>
                </div>
                <div id="eligibility_info" class="alert alert-info" style="display:none; margin-bottom: 20px;"></div>
                <div class="vacation-card" id="eos_info_card" style="display:none; margin-bottom: 20px;">
                    <div class="vacation-card-header">
                        <i class="fa fa-briefcase"></i>
                        ${__('end_of_service_details') || 'End of Service Details'}
                    </div>
                    <div style="padding:15px;">
                        <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                            <span><i class="fa fa-dollar-sign"></i> ${__('eos_total_benefit') || 'Calculated EOS Benefit'}:</span>
                            <strong id="eos_total_span">-</strong>
                        </div>
                        <div style="display:flex; justify-content:space-between;">
                            <span><i class="fa fa-percent"></i> ${__('eos_max_40pct') || 'Max Allowed (40%)'}:</span>
                            <strong id="eos_max_span">-</strong>
                        </div>
                    </div>
                </div>
                <div class="vacation-card" style="margin-bottom: 20px;">
                    <div class="vacation-card-header">
                        <i class="fa fa-money-bill-wave"></i>
                        ${__('loan_amount_label')} <span class="text-danger">*</span>
                    </div>
                    <input type="number" id="loan_amount" name="loan_amount" class="form-control form-control-modern" placeholder="${__('enter_loan_amount_placeholder')}" required step="any" style="margin-top: 15px;">
                    <small id="loan_feedback" class="form-text text-danger" style="margin-top: 5px;"></small>
                </div>
                <div class="vacation-card" id="installments_group" style="display:none; margin-bottom: 20px;">
                    <div class="vacation-card-header">
                        <i class="fa fa-calendar-alt"></i>
                        ${__('number_of_installments_label')} <span class="text-danger">*</span>
                    </div>
                    <select id="installments" name="installments" class="form-control form-control-modern" style="margin-top: 15px;"></select>
                </div>
                <div class="vacation-card" id="deduction_summary" style="display:none; margin-bottom: 20px;">
                    <div class="vacation-card-header">
                        <i class="fa fa-calculator"></i>
                        ${__('monthly_deduction_label')}
                    </div>
                    <input type="text" id="monthly_deduction_display" class="form-control form-control-modern" readonly style="font-weight:bold; margin-top: 15px; background-color: #f8f9fc;">
                </div>

            </form>
        `,
        showCancelButton: true,
        confirmButtonText: '<i class="fa fa-check"></i> ' + __('submit_application_button'),
        cancelButtonText: '<i class="fa fa-times"></i> ' + __('cancel'),
        allowOutsideClick: false,
        confirmButtonColor: '#4e73df',
        cancelButtonColor: '#e74a3b',
        customClass: {
            popup: 'vacation-modal-popup',
            title: 'vacation-modal-title',
            confirmButton: 'btn-modern-confirm',
            cancelButton: 'btn-modern-cancel'
        },
        width: '95%',
        padding: '20px',
        showLoaderOnConfirm: true,
        didOpen: () => {
            const confirmButton = Swal.getConfirmButton();
            const amountInput = $('#loan_amount');
            const installmentsSelect = $('#installments');
            const installmentsGroup = $('#installments_group');
            const deductionGroup = $('#deduction_summary');
            const deductionDisplay = $('#monthly_deduction_display');
            const eligibilityInfo = $('#eligibility_info');
            const loanTypeInputs = $('input[name="loan_type"]');

            let minAmount = 0;
            let maxAmount = 0;
            let maxInstallments = 0;
            let housingAllowance = 0;

            function setInstallmentOptions(n) {
                let opts = '';
                for (let i = 1; i <= n; i++) {
                    opts += `<option value="${i}">${i} ${i>1?(__('months')||'Months'):(__('month')||'Month')}</option>`;
                }
                installmentsSelect.html(opts);
            }

            function updateDeduction() {
                const type = $('input[name="loan_type"]:checked').val();
                const amt = Number(amountInput.val());
                const inst = Number(installmentsSelect.val());
                if (!amt || amt <= 0) { deductionGroup.hide(); return; }
                let monthly = 0;
                if (type === 'end_of_service') {
                    monthly = inst > 0 ? amt / inst : 0;
                } else if (type === 'housing') {
                    monthly = Number(housingAllowance) || 0;
                } else if (type === 'advance_salary') {
                    monthly = amt; // full deduction next payroll
                }
                deductionDisplay.val((Number(monthly)||0).toLocaleString('en-US', { style: 'currency', currency: 'SAR' }));
                deductionGroup.show();
            }

            function validateAmount() {
                const amt = Number(amountInput.val());
                let ok = true;
                if (isNaN(amt) || amt <= 0) ok = false;
                if (maxAmount && amt > maxAmount) ok = false;
                if (minAmount && amt < minAmount) ok = false;
                
                // Show different messages based on user type
                if (user_type === 'employee') {
                    // For employees: generic message without revealing limits
                    if (!ok && amt > maxAmount) {
                        $('#loan_feedback').text(__('entered_amount_exceeds_allowed_limit') || 'The entered amount exceeds the allowed limit for this loan type.');
                    } else if (!ok) {
                        $('#loan_feedback').text(__('please_enter_valid_amount') || 'Please enter a valid amount.');
                    } else {
                        $('#loan_feedback').text('');
                    }
                } else {
                    // For non-employees: detailed message with limits
                    $('#loan_feedback').text(!ok ? `${__('amount_must_be_between') || 'Amount must be between'} ${minAmount.toFixed(2)} - ${maxAmount.toFixed(2)}` : '');
                }
                confirmButton.disabled = !ok;
                updateDeduction();
            }

            async function fetchEligibility(type) {
                eligibilityInfo.hide().removeClass('alert-danger alert-info');
                confirmButton.disabled = true;
                try {
                    const resp = await $.ajax({
                        url: './includes/ajaxFile/ajaxLoan.php',
                        type: 'POST',
                        data: { ajaxType: 'check_loan_eligibility', emp_id: emp_id, loan_type: type },
                        dataType: 'json'
                    });
                    if (resp.status === 'success') {
                        // Build message from key and data
                        let message = '';
                        if (resp.message_key) {
                            message = __(resp.message_key);
                            // Replace placeholders if message_data exists
                            if (resp.message_data) {
                                for (let [key, value] of Object.entries(resp.message_data)) {
                                    message = message.replace(new RegExp('\\{' + key + '\\}', 'g'), value.toLocaleString());
                                }
                            }
                        } else if (resp.message) {
                            // Fallback for old format
                            message = resp.message;
                        }

                        if (!resp.eligible) {
                            eligibilityInfo.addClass('alert-danger').text(message || __('not_eligible')).show();
                            confirmButton.disabled = true;
                            amountInput.prop('disabled', true);
                            installmentsGroup.hide();
                            deductionGroup.hide();
                            return;
                        }
                        minAmount = Number(resp.min_amount) || 0;
                        maxAmount = Number(resp.max_amount) || 0;
                        maxInstallments = Number(resp.max_installments) || 0;
                        housingAllowance = Number(resp.housing_allowance) || 0;
                        amountInput.prop('min', minAmount || 0);
                        if (maxAmount) amountInput.prop('max', maxAmount);
                        amountInput.prop('disabled', false);
                        // Hide maximum value for employees (user_type = employee)
                        if (user_type !== 'employee') {
                            eligibilityInfo.addClass('alert-info').text(message || '').show();
                        }

                        // Show EOS details card only if allowed and EOS type
                        const eosCard = $('#eos_info_card');
                        if (type === 'end_of_service' && resp.show_full_details) {
                            eosCard.show();
                            const eosTotal = Number(resp.eos_benefit) || 0;
                            const eosMax = Number(resp.max_amount) || (eosTotal * 0.4);
                            $('#eos_total_span').text(eosTotal.toLocaleString('en-US', { style: 'currency', currency: 'SAR' }));
                            $('#eos_max_span').text(eosMax.toLocaleString('en-US', { style: 'currency', currency: 'SAR' }));
                        } else {
                            eosCard.hide();
                        }

                        // Configure installments visibility
                        if (type === 'end_of_service') {
                            installmentsGroup.show();
                            setInstallmentOptions(maxInstallments || 12);
                            amountInput.closest('.vacation-card').show();
                        } else if (type === 'housing') {
                            // Hide entire loan amount block if housing not eligible (allowance = 0)
                            const amountBlock = amountInput.closest('.vacation-card');
                            if (!resp.eligible) {
                                amountBlock.hide();
                                installmentsGroup.hide();
                                deductionGroup.hide();
                            } else {
                                amountBlock.show();
                                installmentsGroup.show();
                                setInstallmentOptions(maxInstallments || 6);
                            }
                        } else {
                            installmentsGroup.hide();
                            installmentsSelect.html('<option value="1">1 '+(__('month')||'Month')+'</option>');
                            amountInput.closest('.vacation-card').show();
                        }
                        validateAmount();
                    } else {
                        throw new Error(resp.message || __('failed_to_fetch_loan_details'));
                    }
                } catch (err) {
                    eligibilityInfo.addClass('alert-danger').text(err.message);
                    confirmButton.disabled = true;
                }
            }

            // Init datepicker
            // Start date is now determined by backend (next payroll); no datepicker needed.

            // Bind events
            loanTypeInputs.on('change', function(){ 
                const selectedType = $(this).val();
                fetchEligibility(selectedType);
                // Disable housing radio if no allowance available
                if (selectedType === 'housing' && housingAllowance <= 0) {
                    $('#loan_type_housing').prop('disabled', true);
                    $('#loan_type_housing').closest('.vac-radio-option').css({'opacity': '0.5', 'pointer-events': 'none'});
                } else {
                    $('#loan_type_housing').prop('disabled', false);
                    $('#loan_type_housing').closest('.vac-radio-option').css({'opacity': '1', 'pointer-events': 'auto'});
                }
            });
            // If changing away from EOS, hide eos card immediately
            loanTypeInputs.on('change', function(){ if ($(this).val() !== 'end_of_service') { $('#eos_info_card').hide(); } });
            amountInput.on('input', validateAmount);
            installmentsSelect.on('change', updateDeduction);

            // Initial load
            fetchEligibility($('input[name="loan_type"]:checked').val());
        },
        preConfirm: () => {
            const type = $('input[name="loan_type"]:checked').val();
            const loan_amount = $('#loan_amount').val();
            let installments = $('#installments').val();
            if (type === 'advance_salary') installments = 1;
            if (!loan_amount || !type) {
                Swal.showValidationMessage(__('fill_all_fields_validation'));
                return false;
            }
            return $.ajax({
                url: './includes/ajaxFile/ajaxLoan.php',
                type: 'POST',
                data: { emp_id: emp_id, loan_amount, installments, ajaxType: 'apply_loan', loan_type: type },
                dataType: 'json'
            }).fail((jqXHR, textStatus) => {
                const error = handleAjaxFailure(jqXHR, textStatus);
                Swal.showValidationMessage(`${__('request_failed')} ${error.message}`);
            });
        }
    }).then(result => {
        if (result.isConfirmed) {
            const response = result.value;
            Swal.fire({ title: response.title, text: response.message, icon: response.type, allowOutsideClick: false })
            .then(() => { if (response.status === 'success') location.reload(); });
        }
    });
});

// NEW FUNCTION for handling manual loan payments
$(document).on('click', '.addManualPayment', async function(e) {
    e.preventDefault();
    var loan_id = $(this).data('loan-id');
    var emp_id = $(this).data('emp-id');

    // Show loading indicator
    Swal.fire({
        title: __('loading_loan_balance'),
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {
        // Fetch current loan balance from the server
        const response = await $.ajax({
            url: './includes/ajaxFile/ajaxLoan.php',
            type: 'POST',
            data: {
                loan_id: loan_id,
                ajaxType: 'get_loan_balance'
            },
            dataType: "json",
        });

        if (response.status === 'success') {
            const remainingBalance = response.remaining_balance;

            Swal.fire({
                title: __('add_manual_loan_payment_title'),
                html: `
                    <div class="alert alert-info text-left">
                        <p class="mb-0">${__('remaining_balance_label')} <strong>${remainingBalance.toLocaleString('en-US', { style: 'currency', currency: 'SAR' })}</strong></p>
                    </div>
                    <form id="manualPaymentForm" class="text-left" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="payment_amount">${__('payment_amount_label')}</label>
                            <input type="number" id="payment_amount" name="payment_amount" class="form-control" placeholder="${__('enter_amount_placeholder')}" required step="0.01" max="${remainingBalance}">
                            <small id="payment_feedback" class="form-text text-danger"></small>
                        </div>
                        <div class="form-group">
                            <label for="payment_date">${__('payment_date_label')}</label>
                            <input type="text" id="payment_date" name="payment_date" class="form-control" required autocomplete="off">
                        </div>
                        <div class="form-group">
                            <label for="receipt_id">${__('receipt_id')}</label>
                            <input type="text" id="receipt_id" name="receipt_id" class="form-control" placeholder="${__('enter_receipt_id_placeholder')}" required>
                            <small id="receipt_feedback" class="form-text text-danger"></small>
                        </div>
                        <div class="form-group">
                            <label for="attachment">${__('attachment')}</label>
                            <input type="file" id="attachment" name="attachment" class="form-control-file" required>
                        </div>
                    </form>
                `,
                showCancelButton: true,
                confirmButtonText: __('submit_payment_button'),
                showLoaderOnConfirm: true,
                didOpen: () => {
                    // Initialize Datepicker
                    $('#payment_date').datepicker({
                        format: "yyyy-mm-dd",
                        todayHighlight: true,
                        autoclose: true,
                        endDate: new Date() // Can't be a future date
                    }).datepicker('setDate', new Date());

                    const paymentAmountInput = $('#payment_amount');
                    const paymentFeedback = $('#payment_feedback');
                    const receiptIdInput = $('#receipt_id');
                    const receiptFeedback = $('#receipt_feedback');
                    const confirmButton = Swal.getConfirmButton();
                    let debounceTimer;

                    function validateForm() {
                        const amount = parseFloat(paymentAmountInput.val());
                        const isReceiptDuplicate = receiptFeedback.text() !== '';
                        
                        let isAmountValid = true;
                        if (isNaN(amount) || amount <= 0 || amount > remainingBalance) {
                            isAmountValid = false;
                            if (amount > remainingBalance) {
                                paymentFeedback.text(__('payment_exceeds_balance_validation'));
                            } else {
                                paymentFeedback.text('');
                            }
                        } else {
                            paymentFeedback.text('');
                        }

                        confirmButton.disabled = !isAmountValid || isReceiptDuplicate;
                    }

                    paymentAmountInput.on('input', validateForm);

                    receiptIdInput.on('input', function() {
                        clearTimeout(debounceTimer);
                        const receiptId = $(this).val();

                        if (!receiptId) {
                            receiptFeedback.text('');
                            validateForm();
                            return;
                        }

                        debounceTimer = setTimeout(() => {
                            $.ajax({
                                url: './includes/ajaxFile/ajaxLoan.php',
                                type: 'POST',
                                data: {
                                    ajaxType: 'check_receipt_id',
                                    receipt_id: receiptId
                                },
                                dataType: 'json'
                            }).done(function(response) {
                                if (response.status === 'success' && response.exists) {
                                    receiptFeedback.text(__('receipt_id_duplicate_validation'));
                                } else {
                                    receiptFeedback.text('');
                                }
                                validateForm();
                            });
                        }, 500);
                    });

                    // Initial validation check
                    validateForm();
                },
                preConfirm: () => {
                    const form = document.getElementById('manualPaymentForm');
                    const formData = new FormData(form);
                    formData.append('ajaxType', 'add_manual_payment');
                    formData.append('loan_id', loan_id);

                    const payment_amount = formData.get('payment_amount');
                    const payment_date = formData.get('payment_date');
                    const receipt_id = formData.get('receipt_id');
                    const attachment = document.getElementById('attachment').files[0];

                    if (!payment_amount || !payment_date) {
                        Swal.showValidationMessage(__('fill_amount_and_date_validation'));
                        return false;
                    }
                    if (parseFloat(payment_amount) <= 0) {
                        Swal.showValidationMessage(__('payment_amount_must_be_positive_validation'));
                        return false;
                    }
                    if (parseFloat(payment_amount) > remainingBalance) {
                        Swal.showValidationMessage(`${__('payment_exceeds_balance_validation')} ${remainingBalance.toFixed(2)}.`);
                        return false;
                    }
                    if (!receipt_id) {
                        Swal.showValidationMessage(__('enter_receipt_id_validation'));
                        return false;
                    }
                    if (!attachment) {
                        Swal.showValidationMessage(__('select_receipt_attachment_validation'));
                        return false;
                    }


                    return $.ajax({
                        url: './includes/ajaxFile/ajaxLoan.php',
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        dataType: "json",
                    })
                    .done(function(response){
                        Swal.fire({
                            title: response.title,
                            text: response.message,
                            icon: response.type,
                            allowOutsideClick: false
                        }).then((result) => {
                            if (result.isConfirmed) {
                                location.reload();
                            }
                        });
                    })
                    .fail(function(jqXHR, textStatus) {
                        const error = handleAjaxFailure(jqXHR, textStatus);
                        Swal.showValidationMessage(`${__('request_failed')} ${error.message}`);
                    });
                }
            });

        } else {
            throw new Error(response.message || __('failed_to_fetch_loan_balance'));
        }

    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: __('error_title'),
            text: error.message,
        });
    }
});

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

// NEW: Event handler for Emergency Loan button
$(document).on('click', '.applyEmergencyLoan', function(e) {
    e.preventDefault();
    var emp_id = $(this).data('emp_id');

    let installmentOptions = '';
    for (let i = 1; i <= 12; i++) {
        installmentOptions += `<option value="${i}">${i} ${i > 1 ? __('months') : __('month')}</option>`;
    }

    Swal.fire({
        title: __('apply_for_emergency_loan_title'),
        html: `
            <form id="emergencyLoanForm" class="text-left">
                <div class="alert alert-warning">
                    <h6 class="alert-heading">${__('notice')}</h6>
                    <p class="mb-0">${__('emergency_loan_notice')}</p>
                </div>
                <div class="form-group">
                    <label for="loan_amount_emergency">${__('loan_amount_label')}</label>
                    <input type="number" id="loan_amount_emergency" name="loan_amount" class="form-control" placeholder="${__('enter_loan_amount_placeholder')}" required step="any">
                </div>
                <div class="form-group">
                    <label for="installments_emergency">${__('number_of_installments_label')}</label>
                    <select id="installments_emergency" name="installments" class="form-control" required>
                        ${installmentOptions}
                    </select>
                </div>
                <div class="form-group" id="deduction_summary_emergency" style="display: none;">
                    <label>${__('monthly_deduction_label')}</label>
                    <input type="text" id="monthly_deduction_display_emergency" class="form-control" readonly style="font-weight: bold;">
                </div>
                <div class="form-group">
                    <label for="start_date_emergency">${__('start_date_of_deduction_label')}</label>
                    <input type="text" id="start_date_emergency" name="start_date" class="form-control" required autocomplete="off">
                </div>
            </form>
        `,
        showCancelButton: true,
        confirmButtonText: __('submit_application_button'),
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        showCancelButton: true,
        cancelButtonText: __('cancel'),
        didOpen: () => {
            jQuery('#start_date_emergency').datepicker({
                format: "yyyy-mm-dd",
                todayHighlight: true,
                autoclose: true,
                startDate: new Date(),
            });

            const loanAmountInput = $('#loan_amount_emergency');
            const installmentsInput = $('#installments_emergency');

            function updateDeductionDisplayEmergency() {
                const amount = parseFloat(loanAmountInput.val());
                const installments = parseInt(installmentsInput.val());
                const deductionSummaryDiv = $('#deduction_summary_emergency');
                const deductionDisplayInput = $('#monthly_deduction_display_emergency');

                if (!isNaN(amount) && amount > 0 && !isNaN(installments) && installments > 0) {
                    const monthlyDeduction = amount / installments;
                    deductionDisplayInput.val(monthlyDeduction.toLocaleString('en-US', { style: 'currency', currency: 'SAR' }));
                    deductionSummaryDiv.show();
                } else {
                    deductionSummaryDiv.hide();
                }
            }

            loanAmountInput.on('input', updateDeductionDisplayEmergency);
            installmentsInput.on('change', updateDeductionDisplayEmergency);

            // Initial check
            updateDeductionDisplayEmergency();
        },
        preConfirm: () => {
            const loan_amount = $('#loan_amount_emergency').val();
            const start_date = $('#start_date_emergency').val();
            const installments = $('#installments_emergency').val();

            if (!loan_amount || !start_date || !installments) {
                Swal.showValidationMessage(__('fill_all_fields_validation'));
                return false;
            }
            if (parseFloat(loan_amount) <= 0) {
                Swal.showValidationMessage(__('loan_amount_must_be_positive_validation'));
                return false;
            }

            return $.ajax({
                url: './includes/ajaxFile/ajaxLoan.php',
                type: 'POST',
                data: {
                    emp_id: emp_id,
                    loan_amount: loan_amount,
                    start_date: start_date,
                    installments: installments,
                    ajaxType: 'apply_loan',
                    loan_type: 'emergency' // Specify loan type
                },
                dataType: "json",
            })
            .done(function(response){
                Swal.fire({
                    title: response.title,
                    text: response.message,
                    icon: response.type,
                    allowOutsideClick: false
                }).then(function(isConfirm){
                    if(isConfirm.value){
                        location.reload();
                    }
                });
            })
            .fail(function(jqXHR, textStatus) {
                const error = handleAjaxFailure(jqXHR, textStatus);
                Swal.showValidationMessage(`${__('request_failed')} ${error.message}`);
            });
        }
    });
});
