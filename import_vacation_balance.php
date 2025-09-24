<?php
/************************************************************************************************
 * MODIFICATION SUMMARY (007-import_vacation_balance.php)
 *
 * This file has been reverted to its previous functionality as an "Import Opening Balance" tool,
 * per your request.
 *
 * 1.  **Functionality Restored**: The "Live Calculator" functionality has been removed, and the
 * page now allows you to input and save historical vacation data from your old system.
 * 2.  **Workflow Re-established**: The form fields for entering dates and days are back. They
 * are disabled until an employee is selected to ensure data integrity.
 * 3.  **Projected Balance for Reference**: The "Projected Balance for Today" is calculated and
 * displayed for your information, but the value that is saved is the historical "Opening Balance"
 * as of the "Period End Date" you specify. This provides a correct baseline for all future
 * system calculations.
 ************************************************************************************************/
require_once __DIR__ . '/includes/session_check.php';

// Only HR and Admins should access this page
if (!$isHR && !$is_system_admin) {
    header("Location: ./dashboard.php");
    exit;
}
?>
<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?> - Import Opening Vacation Balance</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit-no">
    <meta content="Anees Afzal" name="author" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="shortcut icon" href="assets/images/favicon.ico">

    <!-- Plugins css -->
    <link href="./plugins/bootstrap-datepicker/css/bootstrap-datepicker.min.css" rel="stylesheet">
    <link href="./plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />

    <!-- App css -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/metisMenu.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
    <script src="assets/js/modernizr.min.js"></script>
    <?php if ($is_rtl) : ?>
        <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
    <?php endif; ?>
</head>

<body class="enlarged" data-keep-enlarged="true">
    <div id="wrapper">
        <!-- ========== Left Sidebar Start ========== -->
        <div class="left side-menu">
            <div class="slimscroll-menu" id="remove-scroll">
                <div class="topbar-left">
                    <a href="dashboard.php" class="logo">
                        <span><img src="assets/images/logo.png" alt="" height="22"></span>
                        <i><img src="assets/images/logo_sm.png" alt="" height="28"></i>
                    </a>
                </div>
                <?php include("./includes/main_menu.php"); ?>
                <div class="clearfix"></div>
            </div>
        </div>
        <!-- Left Sidebar End -->

        <div class="content-page">
            <!-- Top Bar Start -->
            <?php include("./includes/topbar.php"); ?>
            <!-- Top Bar End -->

            <!-- Start Page content -->
            <div class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="card-box">
                                <h4 class="header-title mb-4">Import Opening Vacation Balance</h4>
                                <p class="text-muted font-14">
                                    Use this form to enter the final vacation balance from the old system for an employee. This will serve as the starting point for all future calculations in the new system.
                                </p>
                                <form id="importBalanceForm" class="mt-4">
                                    <div class="form-group col-lg-6 col-md-12 px-0">
                                        <label for="employee_search">Select Employee</label>
                                        <select id="employee_search" class="form-control" name="emp_id" required></select>
                                    </div>

                                    <div id="employee_details" class="alert alert-info" style="display:none;"></div>

                                    <fieldset id="balance_details_fieldset" disabled>
                                        <input type="hidden" name="ajaxType" value="addManualHistory">
                                        <input type="hidden" name="contract_id" id="contract_id">
                                        <input type="hidden" name="name" id="employee_name">

                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label>Period Start Date (From Old System)</label>
                                                <input type="text" class="form-control datepicker" name="period_start" placeholder="YYYY-MM-DD" required autocomplete="off">
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label>Period End Date (Date of Old System Balance)</label>
                                                <input type="text" class="form-control datepicker" name="period_end" placeholder="YYYY-MM-DD" required autocomplete="off">
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-4">
                                                <label>Total Earned Days (From Old System)</label>
                                                <input type="number" step="0.01" class="form-control" name="total_days" id="total_days" placeholder="e.g., 49.62" required>
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label>Total Used Days (From Old System)</label>
                                                <input type="number" step="0.01" class="form-control" name="used_days" id="used_days" placeholder="e.g., 32.00" required>
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label>Opening Balance (Historical)</label>
                                                <input type="number" step="0.01" class="form-control" name="remaining_balance" id="opening_balance" readonly>
                                                <small class="form-text text-muted">This is the balance as of the 'Period End Date'.</small>
                                            </div>
                                        </div>

                                        <div class="alert alert-success mt-3" role="alert">
                                            <h5 class="alert-heading">Projected Balance for Today</h5>
                                            <p>Based on the data above, the employee's projected vacation balance as of today is: <strong id="projected_balance_display" class="font-20">0.00</strong> days.</p>
                                            <hr>
                                            <p class="mb-0 small">This is for your information only. The system will save the historical opening balance and will always calculate the current balance dynamically elsewhere.</p>
                                        </div>

                                        <button type="submit" class="btn btn-primary waves-effect waves-light mt-3">Save Manual History</button>
                                    </fieldset>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <footer class="footer">
                <?= $site_footer ?>
            </footer>
        </div>
    </div>

    <!-- jQuery  -->
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/metisMenu.min.js"></script>
    <script src="assets/js/waves.js"></script>
    <script src="assets/js/jquery.slimscroll.js"></script>

    <!-- Plugins js -->
    <script src="./plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>
    <script src="./plugins/select2/js/select2.min.js" type="text/javascript"></script>

    <!-- App js -->
    <script src="assets/js/jquery.app.js"></script>

    <script>
        $(document).ready(function() {
            // A variable to store the annual days for the selected employee
            let annualVacationDays = 0;

            // Initialize Datepicker
            $('.datepicker').datepicker({
                format: 'yyyy-mm-dd',
                autoclose: true,
                todayHighlight: true,
                endDate: '0d' // Disallow future dates
            });

            // Initialize Select2
            $('#employee_search').select2({
                placeholder: 'Search for an employee by name or ID...',
                allowClear: true,
                minimumInputLength: 2,
                ajax: {
                    url: './includes/ajaxFile/ajaxEmployee.php',
                    type: 'POST',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            ajaxType: 'emp_search_select2',
                            searchTerm: params.term
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.data
                        };
                    },
                    cache: true
                }
            });

            // Handle employee selection
            $('#employee_search').on('select2:select', function(e) {
                const empid = e.params.data.id;
                $.ajax({
                    url: './includes/ajaxFile/ajaxEmployee.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        ajaxType: 'get_emp_vacation_details',
                        empid: empid
                    },
                    success: function(response) {
                        if (response.status === 200) {
                            const data = response.data;
                            $('#employee_details').html(
                                `<strong>Employee:</strong> ${data.name} <br>` +
                                `<strong>Contract:</strong> ${data.vac_period_days} days per period`
                            ).show();
                            $('#contract_id').val(data.vac_period_id);
                            $('#employee_name').val(data.name);

                            // Store annual days and enable the form
                            annualVacationDays = parseFloat(data.vac_period_days) || 0;
                            $('#balance_details_fieldset').prop('disabled', false);
                            updateCalculations();

                        } else {
                            $('#employee_details').text('Error: ' + response.message).show();
                            annualVacationDays = 0; // Reset on error
                            $('#balance_details_fieldset').prop('disabled', true); // Keep form disabled
                        }
                    }
                });
            });

            // Handle clearing the employee selection
            $('#employee_search').on('select2:unselect', function(e) {
                $('#importBalanceForm')[0].reset(); // Reset all form fields
                $('#balance_details_fieldset').prop('disabled', true); // Disable the fieldset
                $('#employee_details').hide(); // Hide details box
                annualVacationDays = 0; // Reset annual days
                updateCalculations(); // Recalculate to show zeros
            });


            /**
             * This function runs all calculations for the page.
             * 1. It calculates the historical opening balance.
             * 2. It calculates the projected balance for today for display purposes.
             */
            function updateCalculations() {
                const total = parseFloat($('#total_days').val()) || 0;
                const used = parseFloat($('#used_days').val()) || 0;
                const periodEndDateStr = $('input[name="period_end"]').val();

                // 1. Calculate and display the historical opening balance (this is what gets saved)
                const openingBalance = total - used;
                $('#opening_balance').val(openingBalance.toFixed(2));

                // 2. Calculate and display the projected balance for today (for information only)
                let projectedBalance = openingBalance;
                if (periodEndDateStr && annualVacationDays > 0) {
                    const periodEndDate = new Date(periodEndDateStr);
                    const today = new Date();
                    periodEndDate.setHours(0, 0, 0, 0);
                    today.setHours(0, 0, 0, 0);

                    if (today > periodEndDate) {
                        const timeDiff = today.getTime() - periodEndDate.getTime();
                        const dayDiff = Math.floor(timeDiff / (1000 * 3600 * 24));
                        const dailyRate = annualVacationDays / 365.0;
                        const accruedDays = dayDiff * dailyRate;
                        projectedBalance += accruedDays;
                    }
                }
                $('#projected_balance_display').text(projectedBalance.toFixed(2));
            }


            // Add event listeners to trigger the calculation on any relevant input change
            $('#total_days, #used_days').on('input', updateCalculations);
            $('input[name="period_end"]').on('change', updateCalculations);


            // Handle form submission
            $('#importBalanceForm').on('submit', function(e) {
                e.preventDefault();
                const formData = $(this).serialize();

                $.ajax({
                    url: './includes/ajaxFile/ajaxVacation.php',
                    type: 'POST',
                    dataType: 'json',
                    data: formData,
                    success: function(response) {
                        Swal.fire({
                            title: response.title,
                            text: response.message,
                            icon: response.type
                        }).then(() => {
                            if (response.type === 'success') {
                                $('#employee_search').val(null).trigger('change');
                                $('#importBalanceForm')[0].reset();
                                $('#balance_details_fieldset').prop('disabled', true);
                                $('#employee_details').hide();
                                annualVacationDays = 0; // Reset
                                updateCalculations(); // Reset display fields
                            }
                        });
                    },
                    error: function() {
                        Swal.fire('Error!', 'An unexpected error occurred.', 'error');
                    }
                });
            });
        });
    </script>
</body>

</html>

