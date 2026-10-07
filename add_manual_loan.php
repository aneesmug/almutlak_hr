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
// ...or an explicit 'Access Page: Add Manual Loan' Special Access grant.
if (!$isHR && !$is_system_admin && !$isDeptHr && !user_has_special_access($conDB, $empid ?? '', 'access_add_manual_loan', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)) {
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
        <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
        <script src="assets/js/modernizr.min.js"></script>
        <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/smart_request.css?v=<?= @filemtime(__DIR__ . '/assets/css/smart_request.css') ?>" rel="stylesheet" type="text/css" />
        <style>
            .sr-page .ml-search { position: relative; }
            .sr-page .employee-search-results {
                position: absolute; top: 100%; left: 0; right: 0; z-index: 1000; margin-top: 4px; max-height: 240px; overflow-y: auto;
                background: var(--sr-surface); border-radius: 10px; box-shadow: 0 10px 28px rgba(15, 23, 42, .12);
            }
            .sr-page .employee-search-results:empty { display: none; }
            .sr-page .employee-search-results .list-group { border: 1px solid var(--sr-border); border-radius: 10px; overflow: hidden; }
            .sr-page .employee-search-results .list-group-item {
                display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 8px 12px; font-size: 13px;
                border: 0; border-bottom: 1px solid var(--sr-border); background: var(--sr-surface); color: var(--sr-text-2);
            }
            .sr-page .employee-search-results .list-group-item:last-child { border-bottom: 0; }
            .sr-page .employee-search-results .list-group-item:hover { background: var(--sr-accent-soft); color: var(--sr-accent-strong); }
            .sr-page .employee-search-results p { margin: 0; border: 1px solid var(--sr-border); border-radius: 10px; color: var(--sr-muted); }
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
                <div class="content sr-page">
                    <div class="container-fluid">
                        <div class="sr-head">
                            <div>
                                <h1><?= __('add_manual_loan_history') ?></h1>
                                <p><?= __('search_for_employee_by_name_or_id') ?></p>
                            </div>
                        </div>

                        <form id="manualLoanForm" class="sr-form" enctype="multipart/form-data" autocomplete="off">
                            <div class="sr-import">
                                <div class="sr-card">
                                    <div class="sr-card-body">
                                        <!-- Step 1: Employee Selection -->
                                        <div class="sr-fsec">
                                            <div class="sr-fsec-head"><span><i class="mdi mdi-account-search"></i><?= __('select_employee') ?></span></div>
                                            <div class="sr-fgrid">
                                                <div class="sr-fcol c-12 ml-search">
                                                    <input type="text" id="employeeSearch" class="form-control" placeholder="<?= __('start_typing_to_search') ?>" autocomplete="off">
                                                    <div id="employeeSearchResults" class="employee-search-results"></div>
                                                    <input type="hidden" name="emp_id" id="emp_id" required>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Step 2: Simplified Loan Details -->
                                        <div id="simpleLoanFormSection" style="display:none;">
                                            <div class="sr-fsec">
                                                <div class="sr-fsec-head"><span><i class="mdi mdi-cash-multiple"></i><?= __('loan_details') ?></span></div>
                                                <div class="sr-fgrid">
                                                    <div class="sr-fcol c-4">
                                                        <label><?= __('payment_date') ?></label>
                                                        <input type="text" name="start_date" class="form-control datepicker" placeholder="YYYY-MM-DD" required autocomplete="off">
                                                    </div>
                                                    <div class="sr-fcol c-4">
                                                        <label><?= __('total_payable') ?></label>
                                                        <input type="number" step="0.01" id="total_loan_amount" name="total_loan_amount" class="form-control" required>
                                                    </div>
                                                    <div class="sr-fcol c-4">
                                                        <label><?= __('total_paid') ?></label>
                                                        <input type="number" step="0.01" id="paid_amount" name="paid_amount" class="form-control" required>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="sr-fsec">
                                                <div class="sr-fsec-head"><span><i class="mdi mdi-receipt"></i><?= __('payment_details') ?></span></div>
                                                <div class="sr-fgrid">
                                                    <div class="sr-fcol c-12">
                                                        <label><?= __('receipt_id') ?></label>
                                                        <input type="text" name="payment_receipt_id" class="form-control" placeholder="<?= __('enter_receipt_id') ?>">
                                                    </div>
                                                    <div class="sr-fcol c-12">
                                                        <label><?= __('attachment') ?></label>
                                                        <label class="sr-filepick" for="payment_attachment">
                                                            <input type="file" name="payment_attachment" id="payment_attachment">
                                                            <i class="mdi mdi-cloud-upload"></i>
                                                            <span><b class="js-file-name"><?= __('choose_file', 'Choose a file or drop it here') ?></b><small></small></span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="sr-import-actions">
                                                <button type="submit" class="sr-btn sr-btn-success"><i class="mdi mdi-content-save"></i> <?= __('save_loan_history') ?></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="sr-card">
                                    <div class="sr-card-head">
                                        <h2 class="sr-card-title"><i class="mdi mdi-account"></i> <?= __('select_employee') ?></h2>
                                    </div>
                                    <div class="sr-card-body">
                                        <div id="selectedEmployeeEmpty" class="sr-card-sub"><?= __('search_for_employee_by_name_or_id') ?></div>
                                        <div id="selectedEmployee" class="sr-person" style="display: none;"></div>
                                        <div class="sr-ftotal mt-3"><span><?= __('remaining_balance') ?></span><b id="remaining_amount">0.00</b></div>
                                    </div>
                                </div>
                            </div>
                        </form>
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
        <script src="assets/js/sr_forms.js?v=<?= @filemtime(__DIR__ . '/assets/js/sr_forms.js') ?>"></script>
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>

        <script>
            $(document).ready(function() {

                AppDate.all('.datepicker');
                SRForm.bindFilePicker($('#manualLoanForm'));

                function initials(name) {
                    return String(name || '').trim().split(/\s+/).slice(0, 2).map(function(w) { return w.charAt(0); }).join('').toUpperCase();
                }

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
                                        $('<li class="list-group-item"></li>')
                                            .attr('data-id', emp.emp_id).attr('data-name', emp.name)
                                            .append($('<span class="sr-avatar sr-avatar-sm"></span>').text(initials(emp.name)))
                                            .append($('<span></span>').text(emp.name + ' (' + emp.emp_id + ')'))
                                            .appendTo(list);
                                    });
                                    results.html(list);
                                } else {
                                    results.html('<p class="p-2">No employees found.</p>');
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
                    $('#selectedEmployee').empty()
                        .append($('<span class="sr-avatar"></span>').text(initials(empName)))
                        .append($('<div></div>')
                            .append($('<div class="sr-cell-title"></div>').text(empName))
                            .append($('<div class="sr-cell-sub"></div>').text('ID: ' + empId)))
                        .show();
                    $('#selectedEmployeeEmpty').hide();
                    $('#employeeSearchResults').empty();
                    $('#simpleLoanFormSection').slideDown();
                });

                // Calculate remaining amount
                function calculateRemaining() {
                    let total = parseFloat($('#total_loan_amount').val()) || 0;
                    let paid = parseFloat($('#paid_amount').val()) || 0;
                    let remaining = total - paid;
                    $('#remaining_amount').text(remaining.toFixed(2));
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