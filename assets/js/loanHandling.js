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
    var emp_id = $(this).data('emp_id');

    // Show loading indicator while fetching details
    Swal.fire({
        title: __('loading_loan_details'),
        text: __('please_wait'),
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {
        // Fetch End of Service and Max Loan Amount from the server
        const response = await $.ajax({
            url: './includes/ajaxFile/ajaxLoan.php',
            type: 'POST',
            data: {
                emp_id: emp_id,
                ajaxType: 'get_loan_details'
            },
            dataType: "json",
        });

        if (response.status === 'success') {
            const endOfService = response.end_of_service;
            const maxLoanAmount = response.max_loan_amount;
            const showFullDetails = response.show_full_details; // New boolean flag from server

            let endOfServiceDisplay = '';
            // If showFullDetails is true, show the full end of service calculation
            if (showFullDetails) {
                 endOfServiceDisplay = `
                    <div class="alert alert-info">
                        <h6 class="alert-heading">${__('end_of_service_benefit')}</h6>
                        <p class="mb-0">${__('total_calculated')} <strong>${endOfService.toLocaleString('en-US', { style: 'currency', currency: 'SAR' })}</strong></p>
                        <hr>
                        <p class="mb-0">${__('max_loan_amount_40_percent')} <strong>${maxLoanAmount.toLocaleString('en-US', { style: 'currency', currency: 'SAR' })}</strong></p>
                    </div>`;
            } else {
                // Otherwise, only show the maximum loan amount allowed.
                 endOfServiceDisplay = `
                    <div class="alert alert-info">
                        <p class="mb-0">${__('max_loan_amount')} <strong>${maxLoanAmount.toLocaleString('en-US', { style: 'currency', currency: 'SAR' })}</strong></p>
                    </div>`;
            }

            // Generate options for installments dropdown
            let installmentOptions = '';
            for (let i = 1; i <= 12; i++) {
                installmentOptions += `<option value="${i}">${i} ${i > 1 ? __('months') : __('month')}</option>`;
            }

            Swal.fire({
                title: __('apply_for_loan_title'),
                html: `
                    <form id="loanApplicationForm" class="text-left">
                        <div class="alert alert-warning">
                            <h6 class="alert-heading">${__('notice')}</h6>
                            <p class="mb-0">${__('eos_based_amount_notice')}</p>
                        </div>
                        ${endOfServiceDisplay}
                        <div class="form-group">
                            <label for="loan_amount">${__('loan_amount_label')}</label>
                            <input type="number" id="loan_amount" name="loan_amount" class="form-control" placeholder="${__('enter_loan_amount_placeholder')}" required step="any" max="${maxLoanAmount}">
                            <small id="loan_feedback" class="form-text text-muted"></small>
                        </div>
                        <div class="form-group">
                            <label for="installments">${__('number_of_installments_label')}</label>
                            <select id="installments" name="installments" class="form-control" required>
                                ${installmentOptions}
                            </select>
                        </div>
                        <div class="form-group" id="deduction_summary" style="display: none;">
                            <label>${__('monthly_deduction_label')}</label>
                            <input type="text" id="monthly_deduction_display" class="form-control" readonly style="font-weight: bold;">
                        </div>
                        <div class="form-group">
                            <label for="start_date">${__('start_date_of_deduction_label')}</label>
                            <input type="text" id="start_date" name="start_date" class="form-control" required autocomplete="off">
                        </div>
                    </form>
                `,
                showCancelButton: true,
                confirmButtonText: __('submit_application_button'),
                showLoaderOnConfirm: true,
                allowOutsideClick: false,
                cancelButtonText: __('cancel'),
                didOpen: () => {
                    const loanAmountInput = $('#loan_amount');
                    const installmentsInput = $('#installments');
                    const confirmButton = Swal.getConfirmButton();
                    
                    // Initialize Datepicker
                    jQuery('#start_date').datepicker({
                        format: "yyyy-mm-dd",
                        todayHighlight: true,
                        autoclose: true,
                        startDate: new Date(),
                    });

                    function updateDeductionDisplay() {
                        const amount = parseFloat(loanAmountInput.val());
                        const installments = parseInt(installmentsInput.val());
                        const deductionSummaryDiv = $('#deduction_summary');
                        const deductionDisplayInput = $('#monthly_deduction_display');

                        if (!isNaN(amount) && amount > 0 && amount <= maxLoanAmount && !isNaN(installments) && installments > 0) {
                            const monthlyDeduction = amount / installments;
                            deductionDisplayInput.val(monthlyDeduction.toLocaleString('en-US', { style: 'currency', currency: 'SAR' }));
                            deductionSummaryDiv.show();
                        } else {
                            deductionSummaryDiv.hide();
                        }
                    }

                    loanAmountInput.on('input', function() {
                        const amount = parseFloat($(this).val());
                         if (isNaN(amount) || amount <= 0 || amount > maxLoanAmount) {
                            if (amount > maxLoanAmount) {
                                $('#loan_feedback').text(__('amount_exceeds_max_validation')).css('color', 'red');
                            } else {
                                 $('#loan_feedback').text('');
                            }
                            confirmButton.disabled = true;
                        } else {
                             $('#loan_feedback').text('');
                             confirmButton.disabled = false;
                        }
                        updateDeductionDisplay();
                    });
                    
                    installmentsInput.on('change', updateDeductionDisplay);
                    
                    // Initial check
                    updateDeductionDisplay();
                },
                preConfirm: () => {
                    const loan_amount = $('#loan_amount').val();
                    const start_date = $('#start_date').val();
                    const installments = $('#installments').val();

                    if (!loan_amount || !start_date || !installments) {
                        Swal.showValidationMessage(__('fill_all_fields_validation'));
                        return false;
                    }
                    if (parseFloat(loan_amount) > maxLoanAmount) {
                        Swal.showValidationMessage(`${__('loan_amount_cannot_exceed_validation')} ${maxLoanAmount.toFixed(2)}.`);
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
                            loan_type: 'regular' // Specify loan type
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

        } else {
            throw new Error(response.message || __('failed_to_fetch_loan_details'));
        }

    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: __('error_title'),
            text: error.message,
        });
    }
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
