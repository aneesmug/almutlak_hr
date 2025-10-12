<?php
/****************************************************************
 * MODIFICATION SUMMARY (008-add_manual_loan.php):
 * 1. ADDED PAYMENT DETAILS FIELDS: Two new fields, "Receipt ID" and "Attachment," have been added to the simplified loan form. This allows users to include documentation for the single payment entry that represents the total paid amount for a historical loan.
 * 2. ADJUSTED LAYOUT: The new fields have been placed in a separate row to maintain a clean and organized layout.
 ****************************************************************
 * MODIFICATION SUMMARY (007-add_manual_loan.php):
 * 1. SIMPLIFIED FORM: The previous complex form for adding detailed loan history and individual payments has been replaced with a much simpler one.
 * 2. NEW FIELDS: After selecting an employee, the user is now presented with a form to enter only the essential details: Loan Date, Total Loan Amount, and Paid Amount.
 * 3. DYNAMIC CALCULATION: A read-only "Remaining Amount" field has been added, which automatically calculates the balance as the user types in the total and paid amounts.
 * 4. UPDATED AJAX SUBMISSION: The form's JavaScript submission logic has been updated to send the data from the new simplified form to a new, corresponding backend function (`add_simplified_manual_loan`).
 ****************************************************************/
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';

$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='" . $username . "'");
if (mysqli_num_rows($query) == 1) {
    include("./includes/avatar_select.php");

    // Only HR and Admins should access this page
if (!$isHR && !$is_system_admin && !$isDeptHr) {
    header("Location: ./dashboard.php");
    exit;
}

?>
    <!doctype html>
    <html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

    <head>
        <meta charset="utf-8" />
        <title><?= $site_title ?> - <?= __('add_manual_loan_history') ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta content="Anees Afzal" name="author" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">
        <link href="./plugins/bootstrap-datepicker/css/bootstrap-datepicker.min.css" rel="stylesheet">
        <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
        <script src="assets/js/modernizr.min.js"></script>
        <style>
            .employee-search-results {
                position: absolute;
                width: 100%;
                background: #fff;
                border: 1px solid #ddd;
                z-index: 1000;
                max-height: 200px;
                overflow-y: auto;
            }

            .employee-search-results .list-group-item {
                cursor: pointer;
            }
            .employee-search-results .list-group-item:hover {
                background-color: #f0f0f0;
            }
        </style>
        <?php if ($is_rtl) : ?>
            <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
        <?php endif; ?>
        <script>
            window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;
        </script>
    </head>

    <body class="enlarged" data-keep-enlarged="true">
        <div id="wrapper">
            <div class="left side-menu">
                <div class="slimscroll-menu" id="remove-scroll">
                    <div class="topbar-left">
                        <a href="dashboard.php" class="logo">
                            <span><img src="<?=get_setting($conDB, 'logo')?>" alt="" height="22"></span>
                            <i><img src="<?=get_setting($conDB, 'white_logo')?>" alt="" height="28"></i>
                        </a>
                    </div>
                    <?php include("./includes/main_menu.php"); ?>
                    <div class="clearfix"></div>
                </div>
            </div>

            <div class="content-page">
                <?php include("./includes/topbar.php"); ?>
                <div class="content">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-12">
                                <div class="card-box">
                                    <h4 class="header-title m-t-0 m-b-30"><?= __('add_manual_loan_history') ?></h4>
                                    <form id="manualLoanForm" enctype="multipart/form-data">
                                        <fieldset>
                                            <!-- Step 1: Employee Selection -->
                                            <h5 class="font-weight-bold"><?=__('select_employee')?></h5>
                                            <p class="text-muted"><?=__('search_for_employee_by_name_or_id')?></p>
                                            <div class="form-group col-md-6 px-0">
                                                <input type="text" id="employeeSearch" class="form-control" placeholder="<?=__('start_typing_to_search')?>" autocomplete="off">
                                                <div id="employeeSearchResults" class="employee-search-results"></div>
                                                <input type="hidden" name="emp_id" id="emp_id" required>
                                            </div>
                                            <div id="selectedEmployee" class="alert alert-success" style="display: none;"></div>
                                            <hr>

                                            <!-- Step 2: Simplified Loan Details -->
                                            <div id="simpleLoanFormSection" style="display:none;">
                                                <h5 class="font-weight-bold mt-4"><?=__('loan_details')?></h5>
                                                <div class="form-row">
                                                    <div class="form-group col-md-3">
                                                        <label><?=__('payment_date')?></label>
                                                        <input type="text" name="start_date" class="form-control datepicker" required autocomplete="off">
                                                    </div>
                                                    <div class="form-group col-md-3">
                                                        <label><?=__('total_payable')?></label>
                                                        <input type="number" step="0.01" id="total_loan_amount" name="total_loan_amount" class="form-control" required>
                                                    </div>
                                                    <div class="form-group col-md-3">
                                                        <label><?=__('total_paid')?></label>
                                                        <input type="number" step="0.01" id="paid_amount" name="paid_amount" class="form-control" required>
                                                    </div>
                                                     <div class="form-group col-md-3">
                                                        <label><?=__('remaining_balance')?></label>
                                                        <input type="text" id="remaining_amount" class="form-control" readonly>
                                                    </div>
                                                </div>
                                                <h5 class="font-weight-bold mt-4"><?=__('payment_details')?></h5>
                                                <div class="form-row">
                                                    <div class="form-group col-md-6">
                                                        <label><?=__('receipt_id')?></label>
                                                        <input type="text" name="payment_receipt_id" class="form-control" placeholder="<?=__('enter_receipt_id')?>">
                                                    </div>
                                                    <div class="form-group col-md-6">
                                                        <label><?=__('attachment')?></label>
                                                        <input type="file" name="payment_attachment" class="form-control">
                                                    </div>
                                                </div>
                                                <hr>
                                                <button type="submit" class="btn btn-success waves-effect waves-light"><?=__('save_loan_history')?></button>
                                            </div>
                                        </fieldset>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <footer class="footer"><?= $site_footer ?></footer>
            </div>
        </div>

        <script src="assets/js/jquery.min.js"></script>
        <script src="assets/js/bootstrap.bundle.min.js"></script>
        <script src="assets/js/metisMenu.min.js"></script>
        <script src="assets/js/waves.js"></script>
        <script src="assets/js/jquery.slimscroll.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="./plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js"></script>

        <script>
            $(document).ready(function() {

                function initializeDatepicker(selector) {
                    $(selector).datepicker({
                        format: 'yyyy-mm-dd',
                        autoclose: true,
                        todayHighlight: true
                    });
                }

                initializeDatepicker('.datepicker');

                // Employee search
                $('#employeeSearch').on('keyup', function() {
                    let searchTerm = $(this).val();
                    if (searchTerm.length > 2) {
                        $.ajax({
                            url: './includes/ajaxFile/ajaxLoan.php',
                            type: 'POST',
                            dataType: 'JSON',
                            data: {
                                ajaxType: 'search_employee',
                                searchTerm: searchTerm
                            },
                            success: function(response) {
                                let results = $('#employeeSearchResults');
                                results.empty();
                                if (response.status === 'success' && response.employees.length > 0) {
                                    let list = $('<ul class="list-group"></ul>');
                                    response.employees.forEach(function(emp) {
                                        list.append(`<li class="list-group-item" data-id="${emp.emp_id}" data-name="${emp.name}">${emp.name} (${emp.emp_id})</li>`);
                                    });
                                    results.html(list);
                                } else {
                                    results.html('<p class="p-2 text-muted">No employees found.</p>');
                                }
                            }
                        });
                    } else {
                        $('#employeeSearchResults').empty();
                    }
                });

                // Select employee from results
                $(document).on('click', '#employeeSearchResults .list-group-item', function() {
                    let empId = $(this).data('id');
                    let empName = $(this).data('name');

                    $('#emp_id').val(empId);
                    $('#employeeSearch').val(empName);
                    $('#selectedEmployee').html(`<strong>Selected:</strong> ${empName} (ID: ${empId})`).show();
                    $('#employeeSearchResults').empty();
                    $('#simpleLoanFormSection').slideDown();
                });

                // Calculate remaining amount
                function calculateRemaining() {
                    let total = parseFloat($('#total_loan_amount').val()) || 0;
                    let paid = parseFloat($('#paid_amount').val()) || 0;
                    let remaining = total - paid;
                    $('#remaining_amount').val(remaining.toFixed(2));
                }

                $('#total_loan_amount, #paid_amount').on('keyup change', calculateRemaining);

                // Form submission
                $('#manualLoanForm').on('submit', function(e) {
                    e.preventDefault();
                    let formData = new FormData(this);
                    formData.append('ajaxType', 'add_simplified_manual_loan');

                    $.ajax({
                        url: './includes/ajaxFile/ajaxLoan.php',
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        dataType: 'json',
                        success: function(response) {
                             Swal.fire({
                                title: response.title,
                                text: response.message,
                                icon: response.type,
                            }).then((result) => {
                                if (response.status === 'success') {
                                    location.reload();
                                }
                            });
                        },
                        error: function() {
                             Swal.fire({
                                title: 'Error!',
                                text: 'An unexpected error occurred.',
                                icon: 'error'
                            });
                        }
                    });
                });
            });
        </script>

    </body>
    </html>
<?php
} else {
    header("Location: ../../index.php");
    exit();
}
?>