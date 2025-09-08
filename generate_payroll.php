<?php
    require_once __DIR__ . '/includes/db.php';
    require_once __DIR__ . '/includes/session_check.php';
    $query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='" . $username . "'");
    if (mysqli_num_rows($query) == 1) {
        include("./includes/avatar_select.php");
?>
    <!doctype html>
    <html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

    <head>
        <meta charset="utf-8" />
        <title><?= $site_title ?> - <?=__('payroll_management_title')?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <!--        <meta content="A fully featured admin theme which can be used to build CRM, CMS, etc." name="description" />-->
        <meta content="Anees Afzal" name="author" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />

        <!-- App favicon -->
        <link rel="shortcut icon" href="assets/images/favicon.ico">

        <!-- Modal -->
        <link href="./plugins/custombox/css/custombox.min.css" rel="stylesheet">

        <!-- Plugins css -->
        <link href="./plugins/bootstrap-timepicker/bootstrap-timepicker.min.css" rel="stylesheet">
        <link href="./plugins/bootstrap-colorpicker/css/bootstrap-colorpicker.min.css" rel="stylesheet">
        <link href="./plugins/bootstrap-datepicker/css/bootstrap-datepicker.min.css" rel="stylesheet">
        <link href="./plugins/clockpicker/css/bootstrap-clockpicker.min.css" rel="stylesheet">
        <link href="./plugins/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
        <!-- DataTables -->
        <link href="./plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
        <link href="./plugins/datatables/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" />
        <link href="./plugins/bootstrap-select/css/bootstrap-select.min.css" rel="stylesheet" />
        <link href="./plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
        <!-- Responsive datatable examples -->
        <link href="./plugins/datatables/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />

        <!-- Multi Item Selection examples -->
        <link href="./plugins/datatables/select.bootstrap4.min.css" rel="stylesheet" type="text/css" />

        <!-- App css -->
        <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
        <script src="assets/js/modernizr.min.js"></script>
        <style>
            .swal2-html-container{
                overflow: hidden !important;
            }
            .rounded-left-0{
                border-radius: 0 0.25rem 0.25rem 0 !important; 
            }
            .rounded-right-0{
                border-radius: 0.25rem 0 0 0.25rem !important; 
            }
            .rounded-0{
                border-radius: 0 !important; 
            }
            .currencyicon-right{
                border: 1px solid #d9e3e9 !important;
                border-radius: 0.25rem 0 0 0.25rem !important; 
                border-right: 0px !important;
            }
            .currencyicon-right-nbc{
                border-radius: 0.25rem 0 0 0.25rem !important; 
                border-right: 0px !important;
            }
            .currencyicon-left{
                border: 1px solid #d9e3e9 !important;
                border-radius: 0 0.25rem 0.25rem 0 !important; 
                border-left: 0px !important;
            }
            .currencyicon-left-right-no-radius{
                border: 1px solid #d9e3e9 !important; 
                border-radius: 0px 0px 0px 0px !important;
                border-left: 0px !important;
                border-right: 0px !important;
            }
            .icon-saudi_riyal{
                font-size: 10px !important;
            }
            .input-group-text{
                font-size: 14px !important;
                border: 1px solid #d9e3e9 !important; 
            }
        </style>
        <?php if ($is_rtl): ?>
            <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
        <?php endif; ?>
		<script> window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;</script>
    </head>

    <body class="enlarged" data-keep-enlarged="true">

        <!-- Begin page -->
        <div id="wrapper">

            <!-- ========== Left Sidebar Start ========== -->
            <div class="left side-menu">

                <div class="slimscroll-menu" id="remove-scroll">

                    <!-- LOGO -->
                    <div class="topbar-left">
                        <a href="dashboard.php" class="logo">
                            <span>
                                <img src="assets/images/logo.png" alt="" height="22">
                            </span>
                            <i>
                                <img src="assets/images/logo_sm.png" alt="" height="28">
                            </i>
                        </a>
                    </div>

                    <!-- User box -->

                    <!--- Sidemenu -->
                    <?php include("./includes/main_menu.php"); ?>
                    <!-- Sidebar -->

                    <div class="clearfix"></div>

                </div>
                <!-- Sidebar -left -->

            </div>
            <!-- Left Sidebar End -->



            <!-- ============================================================== -->
            <!-- Start right Content here -->
            <!-- ============================================================== -->

            <div class="content-page">

                <!-- Top Bar Start -->
                <?php include("./includes/topbar.php"); ?>
                <!-- Top Bar End -->


                <!-- Start Page content -->
                <div class="content">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-12">
                                <div class="card-box table-responsive">
                                    <!-- <a href="add_car.php" class="btn btn-primary waves-effect"><i class="mdi mdi-car"></i> Add New Car</a> -->
                                    <h4 class="m-t-0 header-title"><?=__('employee_payroll_management')?></h4>

                                    <div class="card-body">
                                        <!-- Controls Section -->
                                        <div class="card bg-light border-light-subtle mb-4 p-3 rounded-3">
                                            <div class="row g-3 align-items-end">
                                                <!-- Month Selector -->
                                                <div class="col-lg-3 col-md-6">
                                                    <label for="payrollMonth" class="form-label fw-medium"><?=__('select_month_label')?></label>
                                                    <input type="month" id="payrollMonth" class="form-control">
                                                </div>

                                                <!-- Department Filter -->
                                                <div class="col-lg-3 col-md-6">
                                                    <label for="companyFilter" class="form-label fw-medium"><?=__('filter_by_company_label')?></label>
                                                    <select id="companyFilter" class="custom-select">
                                                        <option value="" selected><?=__('all_companies_option')?></option>
                                                        <!-- Department options will be populated here by JavaScript -->
                                                    </select>
                                                </div>

                                                <!-- Spacer Column -->
                                                <div class="col-lg"></div>

                                                <!-- Action Buttons -->
                                                <div class="col-lg-auto btn-group">
                                                    <button id="generatePayrollBtn" class="btn btn-lg btn-primary"><?=__('generate_payroll_for_selected_button')?></button>
                                                    <button id="generateReportBtn" class="btn btn-lg btn-outline-secondary"><?=__('generate_payroll_report_button')?></button>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Table Section -->
                                        <div class="table-responsive">
                                            <table id="employeeTable" class="table table-striped table-hover align-middle w-100">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th scope="col" class="text-center" style="width: 50px;">
                                                            <input class="" type="checkbox" id="selectAllEmployees">
                                                        </th>
                                                        <th scope="col" style="width: 120px;"><?=__('employee_id')?></th>
                                                        <th scope="col"><?=__('name')?></th>
                                                        <th scope="col" style="width: 230px;"><?=__('company_label')?></th>
                                                        <th scope="col" style="width: 200px;"><?=__('salary_label')?></th>
                                                        <th scope="col" style="width: 100px;"><?=__('actions_label')?></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <!-- Data will be loaded by DataTables or JavaScript -->
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>


                                </div>
                            </div>
                        </div>


                    </div> <!-- container -->

                </div> <!-- content -->

                <footer class="footer">
                    <?= $site_footer ?>
                </footer>

            </div>

            <!-- ============================================================== -->
            <!-- End Right content here -->
            <!-- ============================================================== -->
        </div>
        <!-- END wrapper -->


        <!-- jQuery  -->
        <script src="assets/js/jquery.min.js"></script>
        <script src="assets/js/bootstrap.bundle.min.js"></script>
        <script src="assets/js/metisMenu.min.js"></script>
        <script src="assets/js/waves.js"></script>
        <script src="assets/js/jquery.slimscroll.js"></script>


        <!-- Modal-Effect -->
        <script type="text/javascript" src="./plugins/parsleyjs/parsley.min.js"></script>
        <!-- <script src="./plugins/bootstrap-inputmask/bootstrap-inputmask.min.js" type="text/javascript"></script> -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/3.3.4/jquery.inputmask.bundle.min.js" type="text/javascript"></script>
        <script src="./plugins/autoNumeric/autoNumeric.js" type="text/javascript"></script>


        <script src="./plugins/moment/moment.js"></script>
        <script src="./plugins/bootstrap-timepicker/bootstrap-timepicker.js"></script>
        <script src="./plugins/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js"></script>
        <script src="./plugins/clockpicker/js/bootstrap-clockpicker.min.js"></script>
        <script src="./plugins/bootstrap-daterangepicker/daterangepicker.js"></script>
        <script src="./plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>

        <!-- App js -->
        <!-- <script src="assets/pages/jquery.form-pickers.init.js"></script> -->

        <!-- Required datatable js -->
        <script src="./plugins/datatables/jquery.dataTables.min.js"></script>
        <script src="./plugins/datatables/dataTables.bootstrap4.min.js"></script>
        <!-- Buttons examples -->
        <script src="./plugins/datatables/dataTables.buttons.min.js"></script>
        <script src="./plugins/datatables/buttons.bootstrap4.min.js"></script>
        <script src="./plugins/datatables/jszip.min.js"></script>
        <script src="./plugins/datatables/pdfmake.min.js"></script>
        <script src="./plugins/datatables/vfs_fonts.js"></script>
        <script src="./plugins/datatables/buttons.html5.min.js"></script>
        <script src="./plugins/datatables/buttons.print.min.js"></script>

        <!-- Key Tables -->
        <script src="./plugins/datatables/dataTables.keyTable.min.js"></script>

        <script src="./plugins/select2/js/select2.min.js" type="text/javascript"></script>
        <script src="./plugins/bootstrap-select/js/bootstrap-select.js" type="text/javascript"></script>

        <!-- Responsive examples -->
        <script src="./plugins/datatables/dataTables.responsive.min.js"></script>
        <script src="./plugins/datatables/responsive.bootstrap4.min.js"></script>

        <script type="text/javascript" src="./plugins/autocomplete/jquery.autocomplete.min.js"></script>

        <!-- Selection table -->
        <script src="./plugins/datatables/dataTables.select.min.js"></script>

        
        
        <script src="https://cdn.sheetjs.com/xlsx-0.19.3/package/dist/xlsx.full.min.js"></script>
        <!-- jsPDF and jspdf-autotable for PDF export -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>


        <!-- App js -->
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js"></script>

<script>
// ================================================================================
// |
// | JAVASCRIPT FOR PAYROLL MANAGEMENT (Updated: 26 June 2025)
// |
// | This is the complete script for your `payroll_management.php` page.
// | It integrates the new dynamic deduction features into your existing structure.
// |
// | MODIFICATION SUMMARY:
// | 1. Modified `exportPdfReport` function to generate a detailed landscape PDF.
// | 2. The new PDF report includes columns for "Benefits Details" and "Deductions Details".
// | 3. These columns list all individual benefits and deductions with their notes/amounts for each employee.
// | 4. The function uses jsPDF-AutoTable to create a well-formatted table that fits the detailed data.
// | 5. The rest of the functionality, including Excel export, remains unchanged as requested.
// |
// ================================================================================

let employeeTable; // DataTables instance
let allEmployeesData = []; // Store raw employee data fetched from API
let allBenefitTypesData = []; // Store raw benefit types data
let currentEventListeners = []; // Array to store cleanup functions for event listeners
let payroll; // Globally available payroll object for modal calculations

// This function is correct as is, but ensure you are using the latest version from previous replies.
const buildDeductionsHtml = (deductions, payrollData) => {
    if (!deductions || deductions.length === 0) {
        return `<div id="no-deductions-alert" class="alert alert-info py-2 mb-0 small">${__('no_deductions_recorded')}</div>`;
    }
    return deductions.map(d => {
        const deductionId = d.id || '';
        const calcType = d.calculation_type || 'fixed';
        const deductionName = d.name || d.deduction || '';
        const noteAmount = parseFloat(d.amount || d.note || 0).toFixed(2);
        const hours = d.hours || '';
        const days = calcType === 'daily_deduction' && hours ? (hours / 8).toFixed(2) : '';
        const isGosi = deductionName.toUpperCase() === 'GOSI';
        const isCalculated = calcType !== 'fixed';
        const isAmountReadonly = isGosi || isCalculated;
        const options = `
            <option value="fixed" ${calcType === 'fixed' ? 'selected' : ''}>${__('fixed_amount_option')}</option>
            <option value="hourly_deduction" ${calcType === 'hourly_deduction' ? 'selected' : ''}>${__('deduction_by_hour_option')}</option>
            <option value="daily_deduction" ${calcType === 'daily_deduction' ? 'selected' : ''}>${__('deduction_by_day_option')}</option>
        `;
        let nameColumnHtml;
        if (isGosi) {
            // For GOSI, just show a single readonly input.
            nameColumnHtml = `<input type="text" class="form-control form-control-sm gosi-deduction-name" value="GOSI" readonly>`;
        } else {
            // For other types, use an Input Group to put the select and text input on one line.
            // The "deduction-name" input is hidden with style when it's a calculated type.
            nameColumnHtml = `
                <div class="input-group input-group-sm">
                    <select class="form-control form-control-sm deduction-type">${options}</select>
                    <input type="text" class="form-control form-control-sm deduction-name" 
                           placeholder="${__('deduction_reason_placeholder')}" value="${deductionName}" 
                           style="${isCalculated ? 'display: none;' : ''}">
                </div>
            `;
        }
        return `
        <div class="deduction-row row mb-2 align-items-center g-2" data-deduction-id="${deductionId}">
            <div class="col-md-4">
                ${nameColumnHtml}
            </div>
            <div class="col-md-3 deduction-period-input" style="${isCalculated ? '' : 'display: none;'}">
                <div class="input-group input-group-sm">
                    <input type="number" step="any" class="form-control form-control-sm deduction-hours" 
                           placeholder="${__('hours_placeholder')}" value="${hours}" style="${calcType !== 'hourly_deduction' ? 'display: none;' : ''}">
                    <input type="number" step="any" class="form-control form-control-sm deduction-days" 
                           placeholder="${__('days_placeholder')}" value="${days}" style="${calcType !== 'daily_deduction' ? 'display: none;' : ''}">
                </div>
            </div>
            <div class="col-md">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-right-0 rounded-right-0"><i class="icon-saudi_riyal"></i></span>
                    <input type="text" class="form-control deduction-amount" value="${noteAmount}" 
                           placeholder="${__('amount_placeholder')}">
                </div>
            </div>
            <div class="col-md-1 text-center">
                ${!isGosi ? `<button class="btn btn-sm btn-outline-danger delete-deduction-btn"><i class="fas fa-trash-alt"></i></button>` : ''}
            </div>
        </div>`;
    }).join('');
};

function buildBenefitsHtml(benefits, benefitTypes) {
    if (!benefits || benefits.length === 0) {
        return `<div id="no-benefits-alert" class="alert alert-info py-2 mb-0 small">${__('no_benefits_recorded_for_month')}</div>`;
    }

    return benefits.map(b => {
        const benefitName = b.benefit || '';
        const benefitAmount = parseFloat(b.note || 0).toFixed(2);
        // Fallback to a simple text input if benefit types are not available
        const benefitOptionsHtml = benefitTypes && benefitTypes.length > 0
            ? `
                <select class="form-select form-select-sm benefit-type custom-select" data-benefit-id="${b.id}">
                    <option>${__('select_type_option')}</option>
                    ${benefitTypes.map(type => `
                        <option value="${type.id}" data-calculation="${type.calculation_type}" ${type.name === benefitName ? 'selected' : ''}>
                            ${type.name}
                        </option>
                    `).join('')}
                </select>
            `
            : `
                <input type="text" class="form-control form-control-sm benefit-name" 
                       data-benefit-id="${b.id}" value="${benefitName}" placeholder="${__('benefit_name_placeholder')}">
            `;

        return `
            <div class="benefit-row row mb-2 align-items-center g-2">
                <div class="col-md-6">
                    ${benefitOptionsHtml}
                </div>
                <div class="col-md-2">
                    </div>
                <div class="col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-right-0 rounded-right-0"><i class="icon-saudi_riyal"></i></span>
                        <input type="text" step="0.01" class="form-control benefit-amount" 
                               data-benefit-id="${b.id}" value="${benefitAmount}" placeholder="${__('amount_placeholder')}">
                    </div>
                </div>
                <div class="col-md-1 text-center">
                    <button class="btn btn-sm btn-outline-danger delete-benefit-btn" data-benefit-id="${b.id}">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            </div>
        `;
    }).join('');
}

// --- (NEW) Calculate Deduction Amount Function ---
const calculateDeductionAmount = function() {
    if (typeof payroll === 'undefined') { return; }
    const row = $(this).closest('.deduction-row');
    const deductionType = row.find('.deduction-type').val();
    const hoursInput = row.find('.deduction-hours');
    const daysInput = row.find('.deduction-days');
    const amountInput = row.find('.deduction-amount');
    const basic = parseFloat(payroll.basic_salary || 0);
    const housing = parseFloat(payroll.housing_allowance || 0);
    const transport = parseFloat(payroll.transport_allowance || 0);
    if (deductionType === 'hourly_deduction' || deductionType === 'daily_deduction') {
        const deductibleSalary = basic + transport + housing;
        const hourlyRate = deductibleSalary > 0 ? (deductibleSalary / 240) : 0;
        let hoursToDeduct = 0;
        if (deductionType === 'hourly_deduction') {
            hoursToDeduct = parseFloat(hoursInput.val()) || 0;
        } else { // daily_deduction
            const daysToDeduct = parseFloat(daysInput.val()) || 0;
            hoursToDeduct = daysToDeduct * 8;
        }
        const amount = (hourlyRate * hoursToDeduct).toFixed(2);
        amountInput.val(amount);
    }
    updateNetSalaryDisplay(payroll.total_gross_salary);
};


// --- Main Script Logic (Your existing functions) ---
$(document).ready(function() {
    window.today = new Date();
    $('#payrollMonth').val(`${getDateParts(today, 'year')}-${getDateParts(today, 'month')}`);
    initializeDataTable();
    $('#payrollMonth').on('change', fetchEmployees);
    fetchEmployees();
    fetchBenefitTypes();
    $('#generateReportBtn').off('click').on('click', generatePayrollReport);
});

function initializeDataTable() {
    employeeTable = $('#employeeTable').DataTable({
        columns: [
            { 
                data: null,
                orderable: false,
                className: 'text-center',
                render: function(data, type, row) {
                    // Check if payroll is generated for the current month
                    // The `payroll_status` comes from the get_employees.php API response
                    const isPayrollGenerated = row.payroll_status && (row.payroll_status === 'generated');
                    const isPayrollPaid = row.payroll_status && (row.payroll_status === 'paid');
                    if (isPayrollGenerated) {
                        return `<span class="badge badge-primary">${__('generated_badge')}</span>`;
                    } else if (isPayrollPaid){
                        return `<span class="badge badge-success"><i class="fa fa-certificate"></i> ${__('paid_badge')}</span>`;
                    }
                    return `<input type="checkbox" class="employee-checkbox" data-emp-id="${row.emp_id}">`;
                }
            },
            { data: 'emp_id' },
            { data: 'name' },
            { data: 'comp_name' }, // Use 'dept' field for department name
            { 
                data: 'salary',
                render: function(data, type, row) {
                    return `SAR ${parseFloat(data).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                }
            },
            {
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    const isPayrollPaid = row.payroll_status && (row.payroll_status === 'paid');
                    if (isPayrollPaid){
                        return `<button class="btn btn-danger btn-sm btn-rounded" disabled ><i class="mdi mdi-account-edit"></i> ${__('edit')}</button>`;
                    }
                    return `<button class="btn btn-dark btn-sm view-edit-btn" data-emp-id="${row.emp_id}" data-emp-name="${row.name}"><i class="mdi mdi-account-edit"></i> ${__('edit')}</button>`;
                }
            }
        ],
        order: [[2, 'asc']], // Sort by Name by default
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50, 100, 500],
        language: {
            search: `<span>${__('search')}:</span> _INPUT_`,
            searchPlaceholder: `${__('search')}...`,
            lengthMenu: `${__('show')} _MENU_ ${__('entries')}`,
            info: `${__('showing')} _START_ ${__('to')} _END_ ${__('of')} _TOTAL_ ${__('entries')}`,
            infoEmpty: `${__('showing')} 0 ${__('to')} 0 ${__('of')} 0 ${__('entries')}`,
            infoFiltered: `(${__('filtered_from')} _MAX_ ${__('total_entries')})`,
            paginate: {
                first: __('first'),
                last: __('last'),
                next: __('next'),
                previous: __('previous')
            },
            emptyTable: __('no_data_available_in_table'),
            zeroRecords: __('no_matching_records_found'),
            processing: `<div class="spinner-border text-primary" role="status"><span class="visually-hidden">${__('loading')}...</span></div>`
        },
        // dom: '<"flex justify-between items-center mb-4"lf>rt<"flex justify-between items-center mt-4"ip>',
        // The `initComplete` function is crucial for attaching event listeners after DataTables has drawn the table.
        initComplete: function() {
            addEventListeners(); // Initial attachment of listeners
        }
    });
}

async function fetchEmployees() {
    const loadingIndicator = $('#loadingIndicator');
    const noDataMessage = $('#noDataMessage');
    const selectedMonth = $('#payrollMonth').val();
    loadingIndicator.removeClass('hidden');
    noDataMessage.addClass('hidden');
    try {
        // Ensure this path is correct for your server setup
        const response = await fetch(`./includes/api/get_employees.php?month=${selectedMonth}`);
        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`Server responded with status ${response.status}: ${errorText}`);
        }
        const data = await response.json();
        if (data.status === 'success') {
            allEmployeesData = data.employees;
            // Clear existing DataTables rows and add new ones
            employeeTable.clear().rows.add(allEmployeesData).draw();
            populateCompanyFilter(allEmployeesData);
            addEventListeners(); // Re-attach listeners after data update
        } else {
            showError('Error', data.message || 'Failed to load employee data.');
            employeeTable.clear().draw(); // Clear table on error
        }
    } catch (error) {
        console.error('Error fetching employees:', error);
        showError('Network Error', `Error connecting to the server or parsing data: ${error.message}.`);
        employeeTable.clear().draw(); // Clear table on network error
    } finally {
        loadingIndicator.addClass('hidden');
        if (allEmployeesData.length === 0 && noDataMessage.hasClass('hidden')) {
                // Only show no data message if there truly is no data after fetch
            noDataMessage.removeClass('hidden').text(__('no_employee_data_available_for_month'));
        }
    }
}
        
async function fetchBenefitTypes() {
    try {
        // Ensure this path is correct for your server setup
        const response = await fetch(`./includes/api/get_benefit_types.php`);
        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`Server responded with status ${response.status}: ${errorText}`);
        }
        const data = await response.json();
        if (data.status === 'success') {
            allBenefitTypesData = data.benefit_types;
        } else {
            showError('Error', data.message || 'Failed to load employee data.');
        }
    } catch (error) {
        console.error('Error fetching employees:', error);
        showError('Network Error', `Error connecting to the server or parsing data: ${error.message}.`);
        employeeTable.clear().draw(); // Clear table on network error
    } finally {
        if (allBenefitTypesData.length === 0 && noDataMessage.hasClass('hidden')) {
                // Only show no data message if there truly is no data after fetch
            noDataMessage.removeClass('hidden').text(__('no_employee_data_available_for_month'));
        }
    }
}

function populateCompanyFilter(employees) {
    const companyFilter = $('#companyFilter');
    const currentSelectedDept = companyFilter.val(); // Remember current selection
    companyFilter.empty().append(`<option value="">${__('all_companies_option')}</option>`);
    const company = new Set();
    employees.forEach(emp => {
        if (emp.comp_name) {
            company.add(emp.comp_name);
        }
    });
    const sortedCompanies = Array.from(company).sort();
    sortedCompanies.forEach(comp_name => {
        companyFilter.append(`<option value="${comp_name}">${comp_name}</option>`);
    });
    // Restore previous selection if it still exists
    if (sortedCompanies.includes(currentSelectedDept)) {
        companyFilter.val(currentSelectedDept);
    } else {
        companyFilter.val(''); // Reset to All if previous selection is gone
    }
    // Unbind and rebind change event for department filter
    companyFilter.off('change').on('change', function() {
        const selectedDept = $(this).val();
        // DataTables column search works on the raw data of the column
        // In our setup, 'dept' is the 4th column (index 3, 0-indexed)
        employeeTable.column(3).search(selectedDept ? `^${selectedDept}$` : '', true, false).draw();
        // Update main select all checkbox for currently visible rows
        updateMainSelectAllCheckbox();
    });
}

function addEventListeners() {
    // It's crucial to remove previous event listeners before re-adding them
    // to prevent multiple bindings and unexpected behavior, especially with DataTables.
    // Clear previously stored cleanup functions
    currentEventListeners.forEach(cleanup => cleanup());
    currentEventListeners = [];
    // Select all checkbox
    const selectAllHandler = function() {
        const isChecked = $(this).prop('checked');
        // Select only visible and non-generated/selectable checkboxes
        employeeTable.rows({ page: 'current' }).nodes().to$().find('.employee-checkbox:not(:disabled)').prop('checked', isChecked);
        updateMainSelectAllCheckbox();
    };
    $('#selectAllEmployees').off('change', selectAllHandler).on('change', selectAllHandler);
    currentEventListeners.push(() => $('#selectAllEmployees').off('change', selectAllHandler));
    // Individual employee checkbox (delegated using jQuery on)
    const employeeCheckboxHandler = function() {
        updateMainSelectAllCheckbox();
    };
    $('#employeeTable').off('change', '.employee-checkbox', employeeCheckboxHandler).on('change', '.employee-checkbox', employeeCheckboxHandler);
    currentEventListeners.push(() => $('#employeeTable').off('change', '.employee-checkbox', employeeCheckboxHandler));
    // View/Edit Payroll button (delegated using jQuery on)
    const viewEditBtnHandler = function() {
        const empId = $(this).data('emp-id');
        const empName = $(this).data('emp-name');
        const month = $('#payrollMonth').val();
        showPayrollDetails(empId, empName, month);
    };
    $('#employeeTable').off('click', '.view-edit-btn', viewEditBtnHandler).on('click', '.view-edit-btn', viewEditBtnHandler);
    currentEventListeners.push(() => $('#employeeTable').off('click', '.view-edit-btn', viewEditBtnHandler));
    // Generate Payroll button
    $('#generatePayrollBtn').off('click', generatePayroll).on('click', generatePayroll);
    currentEventListeners.push(() => $('#generatePayrollBtn').off('click', generatePayroll));

    // New: Generate Payroll Report button
    $('#generateReportBtn').off('click', generatePayrollReport).on('click', generatePayrollReport);
    currentEventListeners.push(() => $('#generateReportBtn').off('click', generatePayrollReport));

    // Handle delete buttons within the SweetAlert2 modal for benefits
    // These event listeners need to be attached dynamically *after* the SweetAlert2 modal is opened.
    // This is handled within the `showPayrollDetails` function's `didOpen` callback.
}

// Updates the main "Select All" checkbox based on individual employee checkboxes
function updateMainSelectAllCheckbox() {
    const selectAllMain = $('#selectAllEmployees');
    // Get only the checkboxes that are currently visible (on the current page of DataTables)
    // and are not disabled (i.e., not already generated payrolls)
    const visibleSelectableCheckboxes = employeeTable.rows({ page: 'current' }).nodes().to$().find('.employee-checkbox:not(:disabled)');
    const checkedVisibleCheckboxes = visibleSelectableCheckboxes.filter(':checked');

    if (visibleSelectableCheckboxes.length === 0) {
        selectAllMain.prop('checked', false).prop('indeterminate', false);
    } else if (checkedVisibleCheckboxes.length === visibleSelectableCheckboxes.length) {
        selectAllMain.prop('checked', true).prop('indeterminate', false);
    } else if (checkedVisibleCheckboxes.length > 0) {
        selectAllMain.prop('checked', false).prop('indeterminate', true);
    } else {
        selectAllMain.prop('checked', false).prop('indeterminate', false);
    }
}
        
async function generatePayroll() {
    // Get all checked employee checkboxes and extract their IDs
    const selectedEmployees = employeeTable.rows().nodes().to$().find('.employee-checkbox:checked').map(function() {
        return $(this).data('emp-id');
    }).get();

    const payrollMonth = $('#payrollMonth').val();

    // Validate that at least one employee is selected
    if (selectedEmployees.length === 0) {
        showWarning(__('no_employees_selected_warning_title'), __('please_select_one_employee_warning'));
        return;
    }

    // Validate that a payroll month is selected
    if (!payrollMonth) {
        showWarning(__('month_not_selected_warning_title'), __('please_select_payroll_month_warning'));
        return;
    }

    // Show a loading indicator while processing
    Swal.fire({
        title: __('generating_payroll_title'),
        html: __('please_wait_generating_payroll'),
        didOpen: () => Swal.showLoading(),
        allowOutsideClick: false,
        allowEscapeKey: false
    });

    try {
        // Send the request to the server to process the payroll
        const response = await fetch('./includes/api/process_payroll.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                employee_ids: selectedEmployees,
                month: payrollMonth
            }),
        });
        const result = await response.json();

        // If the server responds with 'success', show a success message
        if (result.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: __('payroll_generated_success_title'),
                text: result.message,
                confirmButtonColor: '#6366f1',
                allowOutsideClick: false,
            });
            fetchEmployees(); // Refresh employee list to update status
        } else {
            // If the server responds with an error (e.g., previous month unpaid), throw an error
            throw new Error(result.message || 'An unexpected error occurred.');
        }
    } catch (error) {
        // Catch any errors from the fetch or from the server's response and display them
        console.error('Error:', error);
        // The error message from the PHP script will be displayed here
        showError(__('error_generating_payroll_title'), error.message);
    }
}

function showError(title, message) {
    Swal.fire({
        icon: 'error',
        title: title,
        text: message,
        confirmButtonColor: '#6366f1',
        confirmButtonText: __('close'),
        allowOutsideClick: false,
    });
}

function showWarning(title, message) {
    Swal.fire({
        icon: 'warning',
        title: title,
        text: message,
        confirmButtonColor: '#6366f1',
        confirmButtonText: __('close'),
        allowOutsideClick: false,
    });
}

// --- NEW: Payroll Report Functionality ---
async function generatePayrollReport() {
    Swal.fire({
        title: __('select_report_month_title'),
        html: `
            <div class="text-left mb-4">
                <label for="reportMonthSelect" class="block text-gray-700 text-sm font-bold mb-2">
                    ${__('choose_month_for_report_label')}
                </label>
                <select id="reportMonthSelect" class="custom-select shadow px-3">
                    <!-- Options will be loaded dynamically -->
                </select>
            </div>
        `,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: __('generate_report'),
        cancelButtonText: __('close'),
        confirmButtonColor: '#6366f1',
        allowOutsideClick: false,
        // Pre-confirmation logic: validate if a month is selected
        preConfirm: () => {
            const selectedMonth = $('#reportMonthSelect').val();
            if (!selectedMonth) {
                Swal.showValidationMessage(__('please_select_month_for_report_validation'));
            }
            return selectedMonth; // Return the selected month if valid
        },
        // didOpen callback: executed after the modal is opened
        didOpen: async () => {
            const reportMonthSelect = document.getElementById('reportMonthSelect');
            Swal.showLoading(); // Show loading indicator inside the modal
            try {
                // Fetch available payroll months from your specified API
                const response = await fetch('./includes/api/get_available_months.php'); 
                if (!response.ok) {
                    throw new Error(__('failed_to_fetch_available_months_error'));
                }
                const data = await response.json();

                if (data.status === 'success' && data.months.length > 0) {
                    // Populate the select dropdown with fetched months
                    data.months.forEach(month => {
                        const option = document.createElement('option');
                        option.value = month.value;
                        option.textContent = month.label;
                        reportMonthSelect.appendChild(option);
                    });
                    // Select the first month by default (usually the most recent)
                    if (data.months.length > 0) {
                        reportMonthSelect.value = data.months[0].value;
                    }
                    Swal.hideLoading(); // Hide loading indicator
                } else {
                    Swal.hideLoading();
                    // If no months are found, show a validation message and disable the confirm button
                    Swal.showValidationMessage(__('no_generated_payroll_months_found'));
                    Swal.getConfirmButton().disabled = true;
                }
            } catch (error) {
                console.error('Error loading report months:', error);
                Swal.hideLoading();
                Swal.showValidationMessage(`${__('error_loading_report_months')} ${error.message}`);
                Swal.getConfirmButton().disabled = true; // Disable button on error
            }
        }
    }).then(async (result) => {
        // After the user confirms the month selection
        if (result.isConfirmed) {
            const selectedMonth = result.value;
            // Proceed to fetch and display the payroll report for the selected month
            await fetchAndDisplayPayrollReport(selectedMonth);
        }
    });
}

function updateNetSalaryDisplay(grossSalary) {
    const benefitsDisplay = document.getElementById('totalBenefitsDisplay');
    const deductionsDisplay = document.getElementById('totalDeductionsDisplay');
    const netSalaryDisplay = document.getElementById('netSalaryDisplay');
    
    if (!benefitsDisplay || !deductionsDisplay || !netSalaryDisplay) {
        return; // Exit if elements don't exist (e.g., modal not open)
    }

    const parsedGross = typeof grossSalary === 'string' ? 
        parseFloat(grossSalary.replace(/[^0-9.-]/g, '')) : 
        parseFloat(grossSalary);

    let totalBenefits = 0;
    document.querySelectorAll('.benefit-amount, .new-benefit-amount').forEach(input => {
        const value = input.value.trim();
        totalBenefits += value ? parseFloat(value) : 0;
    });

    let totalDeductions = 0;
    document.querySelectorAll('.deduction-amount, .new-deduction-amount').forEach(input => {
        const value = input.value.trim();
        totalDeductions += value ? parseFloat(value) : 0;
    });

    const netSalary = Math.round((parsedGross + totalBenefits - totalDeductions) * 100) / 100;

    const formatCurrency = (amount) => {
        return 'SAR ' + amount.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    };

    benefitsDisplay.textContent = formatCurrency(totalBenefits);
    deductionsDisplay.textContent = formatCurrency(totalDeductions);
    netSalaryDisplay.textContent = formatCurrency(netSalary);
}

async function savePayrollChanges(empId, month, updatedBenefits, updatedDeductions) {
    Swal.fire({
        title: __('saving_changes_title'),
        html: __('please_wait_fetching_data'),
        didOpen: () => Swal.showLoading(),
        allowOutsideClick: false,
        allowEscapeKey: false,
    });

    try {
        const response = await fetch('./includes/api/update_payroll.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                emp_id: empId,
                month: month,
                benefits: updatedBenefits,
                deductions: updatedDeductions
            }),
        });
        const result = await response.json();

        if (result.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: __('changes_saved_success_title'),
                text: result.message,
                confirmButtonColor: '#6366f1',
                allowOutsideClick: false,
            });
            fetchEmployees(); // Refresh employee list to ensure payroll status is updated
        } else {
            throw new Error(result.message || 'Failed to save changes');
        }
    } catch (error) {
        console.error('Error:', error);
        showError(__('error_saving_changes_title'), error.message);
    }
}



// --- (UPDATED) showPayrollDetails Function ---



async function showPayrollDetails(empId, empName, month) {
    // Clean up previous listeners before opening new modal
    currentEventListeners.forEach(cleanup => cleanup());
    currentEventListeners = [];

    Swal.fire({
        title: `${__('loading_payroll_for_employee')} ${empName} (${empId})...`,
        html: __('please_wait_fetching_data'),
        didOpen: () => Swal.showLoading(),
        allowOutsideClick: false,
        allowEscapeKey: false
    });

    try {
        const response = await fetch(`./includes/api/get_payroll_details.php?emp_id=${empId}&month=${month}`);
        const data = await response.json();

        if (data.status === 'success') {
            payroll = data.payroll; // Set global payroll object
            const employee = data.employee;
            const benefits = data.benefits;
            let deductions = data.deductions;
            const gosiAmnt = (employee.gosi || 0) / 100;

            const benefitTypes = Array.isArray(data.benefit_types) ? data.benefit_types : [];
            // Your warning for missing benefit types remains
            if (benefitTypes.length === 0) { console.warn('No benefit types received from server'); }

            // Your GOSI deduction logic remains unchanged
            if (employee && employee.country === '191' && payroll) {
                const basicPlusHousing = parseFloat(payroll.basic_salary || 0) + parseFloat(payroll.housing_allowance || 0);
                const gosiAmount = (basicPlusHousing * gosiAmnt).toFixed(2);
                const gosiExists = deductions.some(d => (d.name && d.name.toUpperCase() === 'GOSI') || (d.deduction && d.deduction.toUpperCase() === 'GOSI'));
                if (!gosiExists) {
                    deductions.push({ id: null, name: 'GOSI', amount: gosiAmount, note: gosiAmount, readonly: true, calculation_type: 'fixed' });
                }
            }

            // --- Build Benefits HTML ---
            // --- MODIFIED: Use the new buildBenefitsHtml function ---
            let benefitsHtml = buildBenefitsHtml(benefits, benefitTypes);

            // --- Build Deductions HTML ---
            // --- MODIFIED: Use the new buildDeductionsHtml function ---
            let deductionsHtml = buildDeductionsHtml(deductions, payroll);


            // --- Salary Breakdown HTML ---
            const salaryBreakdownHtml = `
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h6 class="card-title border-bottom pb-2 mb-3">${__('basic_components_title')}</h6>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="small text-muted mb-1">${__('basic_salary_label')}</label>
                                        <div class="input-group input-group-sm mb-2">
                                            <span class="input-group-text bg-light border-right-0 bg-light rounded-right-0"><i class="icon-saudi_riyal"></i></span>
                                            <input type="text" class="form-control bg-light" 
                                                value="${parseFloat(payroll.basic_salary).toLocaleString('en-US', { minimumFractionDigits: 2 })}" readonly>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <label class="small text-muted mb-1">${__('housing_allowance_label')}</label>
                                        <div class="input-group input-group-sm mb-2">
                                            <span class="input-group-text bg-light border-right-0 rounded-right-0"><i class="icon-saudi_riyal"></i></span>
                                            <input type="text" class="form-control bg-light" 
                                                value="${parseFloat(payroll.housing_allowance).toLocaleString('en-US', { minimumFractionDigits: 2 })}" readonly>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <label class="small text-muted mb-1">${__('transport_allowance_label')}</label>
                                        <div class="input-group input-group-sm mb-2">
                                            <span class="input-group-text bg-light border-right-0 rounded-right-0"><i class="icon-saudi_riyal"></i></span>
                                            <input type="text" class="form-control bg-light" 
                                                value="${parseFloat(payroll.transport_allowance).toLocaleString('en-US', { minimumFractionDigits: 2 })}" readonly>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <label class="small text-muted mb-1">${__('food_allowance_label')}</label>
                                        <div class="input-group input-group-sm mb-2">
                                            <span class="input-group-text bg-light border-right-0 rounded-right-0"><i class="icon-saudi_riyal"></i></span>
                                            <input type="text" class="form-control bg-light" 
                                                value="${parseFloat(payroll.food_allowance).toLocaleString('en-US', { minimumFractionDigits: 2 })}" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h6 class="card-title border-bottom pb-2 mb-3">${__('additional_components_title')}</h6>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="small text-muted mb-1">${__('miscellaneous_allowance_label')}</label>
                                        <div class="input-group input-group-sm mb-2">
                                            <span class="input-group-text bg-light border-right-0 rounded-right-0"><i class="icon-saudi_riyal"></i></span>
                                            <input type="text" class="form-control bg-light" 
                                                value="${parseFloat(payroll.miscellaneous_allowance).toLocaleString('en-US', { minimumFractionDigits: 2 })}" readonly>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <label class="small text-muted mb-1">${__('cashier_allowance_label')}</label>
                                        <div class="input-group input-group-sm mb-2">
                                            <span class="input-group-text bg-light border-right-0 rounded-right-0"><i class="icon-saudi_riyal"></i></span>
                                            <input type="text" class="form-control bg-light" 
                                                value="${parseFloat(payroll.cashier_allowance).toLocaleString('en-US', { minimumFractionDigits: 2 })}" readonly>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <label class="small text-muted mb-1">${__('fuel_allowance_label')}</label>
                                        <div class="input-group input-group-sm mb-2">
                                            <span class="input-group-text bg-light border-right-0 rounded-right-0"><i class="icon-saudi_riyal"></i></span>
                                            <input type="text" class="form-control bg-light" 
                                                value="${parseFloat(payroll.fuel_allowance).toLocaleString('en-US', { minimumFractionDigits: 2 })}" readonly>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <label class="small text-muted mb-1">${__('telephone_allowance_label')}</label>
                                        <div class="input-group input-group-sm mb-2">
                                            <span class="input-group-text bg-light border-right-0 rounded-right-0"><i class="icon-saudi_riyal"></i></span>
                                            <input type="text" class="form-control bg-light" 
                                                value="${parseFloat(payroll.telephone_allowance).toLocaleString('en-US', { minimumFractionDigits: 2 })}" readonly>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <label class="small text-muted mb-1">${__('guard_allowance_label')}</label>
                                        <div class="input-group input-group-sm mb-2">
                                            <span class="input-group-text bg-light border-right-0 rounded-right-0"><i class="icon-saudi_riyal"></i></span>
                                            <input type="text" class="form-control bg-light" 
                                                value="${parseFloat(payroll.guard_allowance || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}" readonly>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <label class="small text-muted mb-1">${__('other_allowance_label')}</label>
                                        <div class="input-group input-group-sm mb-2">
                                            <span class="input-group-text bg-light border-right-0 rounded-right-0"><i class="icon-saudi_riyal"></i></span>
                                            <input type="text" class="form-control bg-light" 
                                                value="${parseFloat(payroll.other_allowance || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <div class="card border-primary shadow-sm">
                            <div class="card-body py-2">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h6 class="mb-0 text-primary">${__('total_gross_salary_label')}</h6>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-primary text-white py-1 rounded-right-0 border border-primary"><i class="icon-saudi_riyal"></i></span>
                                            <input type="text" class="form-control bg-light fw-bold" 
                                                value="${parseFloat(payroll.total_gross_salary).toLocaleString('en-US', { minimumFractionDigits: 2 })}" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // --- Main Modal HTML ---
            const modalHtml = `
                <div class="payroll-details-container">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="mb-0">${empName}</h5>
                            <small class="text-muted">${__('employee_id')}: ${empId} | ${new Date(month + '-01').toLocaleDateString('en-US', { month: 'long', year: 'numeric' })}</small>
                        </div>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-sm btn-outline-primary active" data-section="salary">
                                <i class="fas fa-money-bill-wave"></i> ${__('salary_section')}
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-success" data-section="benefits">
                                <i class="fas fa-gift"></i> ${__('benefits_section')}
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-section="deductions">
                                <i class="fas fa-minus-circle"></i> ${__('deductions_section')}
                            </button>
                        </div>
                    </div>
                    
                    <div class="section-content">
                        <!-- Salary Section (default visible) -->
                        <div class="section-pane active" id="salary-section">
                            ${salaryBreakdownHtml}
                        </div>
                        
                        <!-- Benefits Section (hidden by default) -->
                        <div class="section-pane d-none" id="benefits-section">
                            <div class="card border-0 shadow-sm mb-3">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="mb-0">${__('benefits_section')}</h6>
                                        <button id="addBenefitBtn" class="btn btn-sm btn-success">
                                            <i class="fas fa-plus-circle me-1"></i> ${__('add_benefit_button')}
                                        </button>
                                    </div>
                                    <div id="benefits-list">
                                        ${benefitsHtml}
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Deductions Section (hidden by default) -->
                        <div class="section-pane d-none" id="deductions-section">
                            <div class="card border-0 shadow-sm mb-3">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="mb-0">${__('deductions_section')}</h6>
                                        <button id="addDeductionBtn" class="btn btn-sm btn-danger">
                                            <i class="fas fa-plus-circle me-1"></i> ${__('add_deduction_button')}
                                        </button>
                                    </div>
                                    <div id="deductions-list">
                                        ${deductionsHtml}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Summary Card -->
                    <div class="card border-primary shadow-sm mt-3">
                        <div class="card-body py-2">
                            <div class="row align-items-center">
                                <div class="col-md-4">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-info text-white py-1 rounded-right-0 border border-info">${__('total_benefits_label')}</span>
                                        <span class="input-group-text bg-light border-left-0 border-right-0 rounded-0 "><i class="icon-saudi_riyal"></i></span>
                                        <input type="text" class="form-control bg-light" id="totalBenefitsDisplay" 
                                            value="${parseFloat(payroll.total_benefits).toLocaleString('en-US', { minimumFractionDigits: 2 })}" readonly>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-warning text-dark py-1 rounded-right-0 border border-warning">${__('total_deductions_label')}</span>
                                        <span class="input-group-text bg-light border-left-0 border-right-0 rounded-0 "><i class="icon-saudi_riyal"></i></span>
                                        <input type="text" class="form-control bg-light" id="totalDeductionsDisplay" 
                                            value="${parseFloat(payroll.total_deductions).toLocaleString('en-US', { minimumFractionDigits: 2 })}" readonly>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-primary text-white py-1 rounded-right-0 border border-primary">${__('net_salary_label')}</span>
                                        <span class="input-group-text bg-light border-left-0 border-right-0 rounded-0 "><i class="icon-saudi_riyal"></i></span>
                                        <input type="text" class="form-control bg-light fw-bold" id="netSalaryDisplay" 
                                            value="${parseFloat(payroll.net_salary).toLocaleString('en-US', { minimumFractionDigits: 2 })}" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            Swal.fire({
                html: modalHtml,
                width: '900px',
                showCancelButton: true,
                confirmButtonText: __('save_changes_button'),
                confirmButtonColor: '#6366f1',
                cancelButtonText: __('close'),
                allowOutsideClick: false,
                didOpen: () => {
                    const originalGrossSalary = parseFloat(payroll.total_gross_salary);
                    const updateDynamicNetSalary = () => updateNetSalaryDisplay(originalGrossSalary);

                    // Section navigation buttons
                    document.querySelectorAll('[data-section]').forEach(btn => {
                        btn.addEventListener('click', function() {
                            // Update active state of buttons
                            document.querySelectorAll('[data-section]').forEach(b => {
                                b.classList.remove('active');
                            });
                            this.classList.add('active');
                            
                            // Show the selected section
                            const section = this.dataset.section;
                            document.querySelectorAll('.section-pane').forEach(pane => {
                                pane.classList.add('d-none');
                            });
                            document.getElementById(`${section}-section`).classList.remove('d-none');
                        });
                    });

                    // Helper to attach event listeners
                    const addDynamicEventListener = (element, event, handler) => {
                        element.addEventListener(event, handler);
                        currentEventListeners.push(() => element.removeEventListener(event, handler));
                    };

                    // Benefit Type Change Handler
                    const handleBenefitTypeChange = function() {
                        const selectedOption = $(this).find('option:selected');
                        const calculationType = selectedOption.data('calculation');
                        const row = $(this).closest('.benefit-row');
                        const hoursContainer = row.find('.col-md-2');
                        const amountInput = row.find('.benefit-amount');
                        const noteInput = row.find('.benefit-note');
                        
                        // Show/hide hours input
                        if (['overtime_basic', 'overtime_total'].includes(calculationType)) {
                            if (hoursContainer.find('.benefit-hours').length === 0) {
                                hoursContainer.html(`
                                    <div class="input-group input-group-sm">
                                        <input type="text" min="0" class="form-control benefit-hours" value="0" placeholder="Hours">
                                        <span class="input-group-text bg-light rounded-left-0" style="font-size:12px !important;">hrs</span>
                                    </div>
                                `);
                                // Add event listener to new hours input
                                const newHoursInput = hoursContainer.find('.benefit-hours')[0];
                                addDynamicEventListener(newHoursInput, 'input', calculateOvertime);
                            }
                        } else {
                            hoursContainer.html('<div class="col-md-2"></div>');
                        }
                        // Calculate initial amount
                        calculateOvertime.call(this);
                        updateDynamicNetSalary();
                    };

                    // Overtime Calculation Function (Updated with new formula)
                    const calculateOvertime = function() {
                        const row = $(this).closest('.benefit-row');
                        const benefitTypeSelect = row.find('.benefit-type');
                        
                        // Ensure the select element exists before proceeding
                        if (!benefitTypeSelect.length) {
                            return;
                        }

                        const benefitType = benefitTypeSelect.find('option:selected').data('calculation');
                        const hoursInput = row.find('.benefit-hours');
                        const hours = hoursInput.length ? parseFloat(hoursInput.val()) || 0 : 0;
                        const amountInput = row.find('.benefit-amount');
                        const noteInput = row.find('.benefit-note');

                        if (benefitType === 'overtime_basic') {
                            // ** NEW CALCULATION LOGIC AS PER YOUR REQUEST **
                            const basicSalary = parseFloat(payroll.basic_salary);
                            const totalSalary = parseFloat(payroll.total_gross_salary);

                            // (Rate 1 from Basic) + (Rate 2 from Total) = Final Hourly Rate
                            const hourlyRate = (basicSalary / 240 / 2) + (totalSalary / 240);
                            const amount = (hourlyRate * hours).toFixed(2);
                            
                            amountInput.val(amount).prop('readonly', true);
                            noteInput.val(`Overtime (${hours} hours)`);

                        } else if (benefitType === 'overtime_total') {
                            // This calculation remains the same, using only the total salary
                            const totalSalary = parseFloat(payroll.total_gross_salary);
                            const amount = ((totalSalary / 240) * hours).toFixed(2);
                            amountInput.val(amount).prop('readonly', true);
                            noteInput.val(`Overtime (${hours} hours)`);

                        } else {
                            // If not an overtime type, unlock the amount field
                            amountInput.prop('readonly', benefitType === 'fixed' ? false : true);
                            // Do not clear the amount if it's a fixed type that the user might have entered
                            if (benefitType !== 'fixed') {
                                amountInput.val('0.00');
                            }
                        }
                        
                        // This function should be defined elsewhere in your `didOpen` block to update totals
                        updateDynamicNetSalary(); 
                    };
                    // Add event listeners for existing benefit type selects
                    document.querySelectorAll('.benefit-type').forEach(select => {
                        addDynamicEventListener(select, 'change', handleBenefitTypeChange);
                    });

                    // Add event listeners for existing hours inputs
                    document.querySelectorAll('.benefit-hours').forEach(input => {
                        addDynamicEventListener(input, 'input', calculateOvertime);
                    });

                    // Add Benefit Button
                    const addBenefitBtn = document.getElementById('addBenefitBtn');
                    addDynamicEventListener(addBenefitBtn, 'click', () => {
                        const benefitsList = document.getElementById('benefits-list');
                        const newRow = document.createElement('div');
                        newRow.classList.add('benefit-row', 'row', 'mb-2', 'align-items-center', 'g-2');
                        newRow.innerHTML = `
                            <div class="col-md-6">
                                <select class="form-select form-select-sm benefit-type custom-select">
                                    <option value="">${__('select_benefit_type')}</option>
                                    ${benefitTypes.map(type => `
                                        <option value="${type.id}" data-calculation="${type.calculation_type}">
                                            ${type.name}
                                        </option>
                                    `).join('')}
                                </select>
                            </div>
                            <div class="col-md-2"></div>
                            <div class="col-md-3">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-right-0 rounded-right-0"><i class="icon-saudi_riyal"></i></span>
                                    <input type="text" step="0.01" class="form-control benefit-amount" placeholder="${__('amount_placeholder')}" readonly>
                                </div>
                            </div>
                            <div class="col-md-1 text-center">
                                <button class="btn btn-sm btn-outline-danger delete-benefit-btn">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        `;
                        benefitsList.appendChild(newRow);
                        
                        // Scroll to the new row
                        newRow.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        
                        // Add event listeners to new row
                        const benefitTypeSelect = newRow.querySelector('.benefit-type');
                        addDynamicEventListener(benefitTypeSelect, 'change', handleBenefitTypeChange);
                        
                        const deleteBtn = newRow.querySelector('.delete-benefit-btn');
                        addDynamicEventListener(deleteBtn, 'click', function() {
                            $(this).closest('.benefit-row').remove();
                            updateDynamicNetSalary();
                        });
                        
                        updateDynamicNetSalary();
                    });

                    // Delete Benefit Button
                    document.querySelectorAll('.delete-benefit-btn').forEach(btn => {
                        addDynamicEventListener(btn, 'click', async function() {
                            const benefitId = this.dataset.benefitId;
                            const row = this.closest('.benefit-row');
                            
                            if (!benefitId) {
                                row.remove();
                                updateDynamicNetSalary();
                                return;
                            }

                            const swalResult = await Swal.fire({
                                title: __('delete_benefit_q_title'),
                                text: __('are_you_sure_delete_benefit_q'),
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonColor: '#d33',
                                cancelButtonColor: '#3085d6',
                                confirmButtonText: __('yes_delete_it_button'),
                                allowOutsideClick: false
                            });

                            if (swalResult.isConfirmed) {
                                try {
                                    const response = await fetch('./includes/api/delete_benefit.php', {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/json' },
                                        body: JSON.stringify({ 
                                            benefit_id: benefitId, 
                                            emp_id: empId, 
                                            month: month 
                                        })
                                    });
                                    const data = await response.json();
                                    
                                    if (data.status === 'success') {
                                        row.remove();
                                        updateDynamicNetSalary();
                                        Swal.fire(__('deleted_success_title'), __('benefit_deleted_success_msg'), 'success');
                                    } else {
                                        throw new Error(data.message || 'Failed to delete benefit');
                                    }
                                } catch (error) {
                                    Swal.fire('Error!', error.message, 'error');
                                }
                            }
                        });
                    });

                    // Delete Deduction Button
                    document.querySelectorAll('.delete-deduction-btn').forEach(btn => {
                        addDynamicEventListener(btn, 'click', async function() {
                            const deductionId = this.dataset.deductionId;
                            const row = this.closest('.deduction-row');

                            if (!deductionId) {
                                row.remove();
                                updateDynamicNetSalary();
                                return;
                            }

                            const swalResult = await Swal.fire({
                                title: __('delete_deduction_q_title'),
                                text: __('are_you_sure_delete_deduction_q'),
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonColor: '#d33',
                                cancelButtonColor: '#3085d6',
                                confirmButtonText: __('yes_delete_it_button'),
                                allowOutsideClick: false
                            });

                            if (swalResult.isConfirmed) {
                                try {
                                    const response = await fetch('./includes/api/delete_deduction.php', {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/json' },
                                        body: JSON.stringify({ 
                                            deduction_id: deductionId, 
                                            emp_id: empId, 
                                            month: month 
                                        })
                                    });
                                    const data = await response.json();
                                    
                                    if (data.status === 'success') {
                                        row.remove();
                                        updateDynamicNetSalary();
                                        Swal.fire(__('deleted_success_title'), __('deduction_deleted_success_msg'), 'success');
                                    } else {
                                        throw new Error(data.message || 'Failed to delete deduction');
                                    }
                                } catch (error) {
                                    Swal.fire('Error!', error.message, 'error');
                                }
                            }
                        });
                    });

                    // Input change listeners
                    document.querySelectorAll('.benefit-amount, .deduction-amount').forEach(input => {
                        addDynamicEventListener(input, 'input', updateDynamicNetSalary);
                    });

                    updateDynamicNetSalary();

                    const swalContainer = Swal.getHtmlContainer();
                    // --- ATTACH NEW DEDUCTION EVENT LISTENERS ---
                    $(swalContainer).find('.deduction-row').each(function() {
                        calculateDeductionAmount.call(this);
                    });
                    $(swalContainer).on('change', '.deduction-type', function() {
                        const row = $(this).closest('.deduction-row');
                        row.find('.deduction-period-input').toggle(this.value !== 'fixed').find('input').val('');
                        row.find('.deduction-hours').toggle(this.value === 'hourly_deduction');
                        row.find('.deduction-days').toggle(this.value === 'daily_deduction');
                        row.find('.deduction-name').toggle(this.value === 'fixed');
                        row.find('.deduction-amount').prop('readonly', this.value !== 'fixed');
                        if (this.value === 'fixed') row.find('.deduction-amount').val('0.00');
                        calculateDeductionAmount.call(this);
                    });
                    $(swalContainer).on('keyup change', '.deduction-hours, .deduction-days', calculateDeductionAmount);
                    $(swalContainer).on('keyup change', '.deduction-amount', function() { if (!$(this).is('[readonly]')) updateNetSalaryDisplay(payroll.total_gross_salary); });
                    
                    $(swalContainer).on('click', '#addDeductionBtn', () => {
                         const newRowHtml = buildDeductionsHtml([{ calculation_type: 'fixed' }], payroll);
                         $('#no-deductions-alert').remove();
                         $('#deductions-list').append(newRowHtml);
                    });
                    
                    $(swalContainer).on('click', '.delete-deduction-btn', function() {
                        $(this).closest('.deduction-row').remove();
                        updateNetSalaryDisplay(payroll.total_gross_salary);
                    });

                    updateNetSalaryDisplay(payroll.total_gross_salary);
                },
                preConfirm: () => {
                    const updatedBenefits = Array.from(document.querySelectorAll('#benefits-list .benefit-row')).map(row => {
                        const benefitTypeSelect = row.querySelector('.benefit-type');
                        const benefitNameInput = row.querySelector('.benefit-name'); // this is the input field
                        const hoursInput = row.querySelector('.benefit-hours');
                        const amountInput = row.querySelector('.benefit-amount');
                        const noteInput = row.querySelector('.benefit-note');
                        const benefitId = benefitTypeSelect?.dataset.benefitId || benefitNameInput?.dataset.benefitId || null;
                        const benefitTypeId = benefitTypeSelect ? benefitTypeSelect.value : null;
                        // Get benefit name depending on available input type
                        let benefitName = '';
                        if (benefitTypeSelect) {
                            benefitName = benefitTypeSelect.options[benefitTypeSelect.selectedIndex].text;
                        } else if (benefitNameInput) {
                            benefitName = benefitNameInput.value.trim();
                        }
                        return {
                            id: benefitId,
                            type_id: benefitTypeId,
                            benefit: benefitName,
                            note: noteInput ? noteInput.value.trim() : '',
                            amount: parseFloat(amountInput.value || 0),
                            hours: hoursInput ? parseFloat(hoursInput.value || 0) : null
                        };
                    }).filter(b => b.benefit !== '' || b.amount > 0);

                    // --- REVISED LOGIC TO GATHER DEDUCTIONS ---
                    const updatedDeductions = [];
                    document.querySelectorAll('#deductions-list .deduction-row').forEach(row => {
                        const deductionId = row.dataset.deductionId || null;
                        const typeSelect = row.querySelector('.deduction-type');
                        // This will handle both existing GOSI and newly added GOSI
                        const gosiNameInput = row.querySelector('.gosi-deduction-name');

                        if (gosiNameInput) {
                            // This is the GOSI row
                            updatedDeductions.push({
                                id: deductionId,
                                calculation_type: 'fixed',
                                deduction: 'GOSI',
                                note: parseFloat(row.querySelector('.deduction-amount').value) || 0,
                                hours: 0,
                            });
                        } else {
                            // This is a regular or new deduction row
                            const calcType = typeSelect ? typeSelect.value : 'fixed';
                            const nameInput = row.querySelector('.deduction-name');
                            let hours = 0;
                            let name = '';

                            if (calcType === 'hourly_deduction') {
                                hours = parseFloat(row.querySelector('.deduction-hours').value) || 0;
                                name = __('hourly_deduction_default_name'); // Use a default name
                            } else if (calcType === 'daily_deduction') {
                                const days = parseFloat(row.querySelector('.deduction-days').value) || 0;
                                hours = days * 8;
                                name = __('daily_deduction_default_name'); // Use a default name
                            } else {
                                // This is a "Fixed Amount" deduction, so we get its name
                                name = nameInput.value.trim();
                            }
                            
                            // Only add it if there's a name or an amount
                            if (name) {
                                updatedDeductions.push({
                                    id: deductionId,
                                    calculation_type: calcType,
                                    deduction: name,
                                    note: parseFloat(row.querySelector('.deduction-amount').value) || 0,
                                    hours: hours,
                                });
                            }
                        }
                    });

                    return { updatedBenefits, updatedDeductions };
                }
            }).then((result) => {
                addEventListeners();
                if (result.isConfirmed) {
                    savePayrollChanges(empId, month, result.value.updatedBenefits, result.value.updatedDeductions);
                }
            });
        } else {
            showError(__('error_loading_payroll_title'), data.message || 'Failed to load payroll details');
        }
    } catch (error) {
        console.error('Error:', error);
        showError('Network Error', error.message);
    }
}
// NOTE: All your other functions are preserved but omitted here for brevity.
        

        

        

        // --- New helper function to encapsulate report fetching and display ---
        async function fetchAndDisplayPayrollReport(selectedMonth) {
            Swal.fire({
                title: __('generating_report_title'),
                html: `${__('fetching_payroll_data_for_month')} ${new Date(selectedMonth + '-01').toLocaleString('default', { month: 'long', year: 'numeric' })}. ${__('please_wait_fetching_data')}`,
                didOpen: () => Swal.showLoading(),
                allowOutsideClick: false,
                allowEscapeKey: false
            });

            try {
                // Fetch the payroll report data for the chosen month
                const response = await fetch(`./includes/api/get_payroll_report.php?month=${selectedMonth}`);
                if (!response.ok) {
                    const errorText = await response.text();
                    throw new Error(`Server responded with status ${response.status}: ${errorText}`);
                }
                const data = await response.json();

                if (data.status === 'success') {
                const reportData = data.report;
                if (reportData.length === 0) {
                    Swal.fire({ icon: 'info', title: __('no_payroll_data_info_title'), text: __('no_generated_payrolls_for_month_info') });
                    return;
                }
                let grandTotalNet = reportData.reduce((sum, p) => sum + parseFloat(p.net_salary || 0), 0);
                const reportHtml = `
                    <div id="payrollReportModal" class="text-left">
                        <h2 class="text-2xl font-bold mb-4 text-center">${__('payroll_report_for_month_title')} ${new Date(selectedMonth + '-01').toLocaleString('default', { month: 'long', year: 'numeric' })}</h2>
                        <div class="mb-4 text-center">
                            <button id="markAsPaidBtn" class="btn btn-custom"><i class="fas fa-check-circle"></i> ${__('mark_as_paid_button')}</button>
                            <button id="exportPdfBtn" class="btn btn-danger"><i class="fas fa-file-pdf"></i> ${__('pdf_button')}</button>
                            <button id="exportExcelBtn" class="btn btn-success"><i class="fas fa-file-excel"></i> ${__('excel_button')}</button>
                        </div>
                        <table class="table table-bordered" id="payrollgentbl" style="width:100%;">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="reportSelectAll"/></th>
                                    <th>${__('emp_id')}</th>
                                    <th>${__('name')}</th>
                                    <th class="text-right">${__('net_salary_label')}</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3" class="text-right">${__('grand_total_label')}</th>
                                    <th class="text-right">${grandTotalNet.toLocaleString('en-US', { style: 'currency', currency: 'SAR' })}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>`;

                Swal.fire({
                    html: reportHtml,
                    width: '90%',
                    showConfirmButton: false,
                    showCancelButton: true,
                    cancelButtonText: __('close'),
                    allowOutsideClick: false,
                    didOpen: () => {
                        const table = $('#payrollgentbl').DataTable({
                            data: reportData,
                            columns: [
                                {
                                    data: 'payroll_id',
                                    orderable: false,
                                    className: 'text-center',
                                    render: function(data, type, row) {
                                        if (row.status === 'paid') {
                                            return `<span class="badge badge-success"><i class="fa fa-check-circle"></i> ${__('paid_badge')}</span>`;
                                        }
                                        return `<input type="checkbox" class="report-checkbox" data-payroll-id="${row.payroll_id}">`;
                                    }
                                },
                                { data: 'emp_id' },
                                { data: 'employee_name' },
                                { data: 'net_salary', className: 'text-right', render: (d) => parseFloat(d || 0).toLocaleString('en-US', { style: 'currency', currency: 'SAR' }) }
                            ],
                            pageLength: 10,
                            lengthMenu: [10, 25, 50, -1],
                            order: [[1, 'asc']]
                        });

                        $('#markAsPaidBtn').on('click', async () => {
                            const selectedPayrollIds = table.rows().nodes().to$().find('.report-checkbox:checked').map(function() {
                                return $(this).data('payroll-id');
                            }).get();
                            
                            await updatePayrollStatus(selectedPayrollIds, 'paid', () => {
                                Swal.close();
                                fetchEmployees();
                            });
                        });
                        
                        $('#reportSelectAll').on('change', function() {
                            const isChecked = $(this).prop('checked');
                            table.rows().nodes().to$().find('.report-checkbox').prop('checked', isChecked);
                        });

                        $('#exportPdfBtn').on('click', () => exportPdfReport(reportData, selectedMonth));
                        $('#exportExcelBtn').on('click', () => exportExcelReport(reportData, selectedMonth));
                    }
                });
            } else {
                    showError(__('error_generating_report_title'), data.message || 'An unexpected error occurred while fetching report data.');
                }
            } catch (error) {
                console.error('Error fetching and displaying payroll report:', error);
                showError('Network Error', `Could not connect to the server or process report: ${error.message}. Please try again.`);
            }
        }

        // --- PDF Export Function (MODIFIED FOR DETAILED REPORT) ---
        async function exportPdfReport(reportData, selectedMonth) {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF({
                orientation: 'landscape',
                unit: 'pt', // Use points for better control over font sizes and margins
                format: 'a4'
            });
            const reportTitle = `${__('employee_payroll_report_for_month')} ${new Date(selectedMonth + '-01').toLocaleString('default', { month: 'long', year: 'numeric' })}`;

            // Define the two-part header structure
            const head = [
                [
                    { content: '#', rowSpan: 2, styles: { valign: 'middle', halign: 'center' } },
                    { content: __('emp_id'), rowSpan: 2, styles: { valign: 'middle', halign: 'center' } },
                    { content: __('employee_name'), rowSpan: 2, styles: { valign: 'middle' } },
                    { content: __('salary_allowances_breakdown'), colSpan: 11, styles: { halign: 'center' } },
                    { content: __('benefits_section'), colSpan: 2, styles: { halign: 'center' } },
                    { content: __('deductions_section'), colSpan: 2, styles: { halign: 'center' } },
                    { content: __('net_salary_label'), rowSpan: 2, styles: { valign: 'middle', halign: 'center' } }
                ],
                [
                    // Sub-headers for 'Salary & Allowances Breakdown'
                    __('basic_salary_label'), __('housing_allowance_label'), __('transport_allowance_label'), __('food_allowance_label'), __('miscellaneous_allowance_label'), __('cashier_allowance_label'), __('fuel_allowance_label'), __('telephone_allowance_label'), __('other_allowance_label'), __('guard_allowance_label'), __('total_gross_salary_label'),
                    // Sub-headers for 'Benefits'
                    __('benefits_details_label'), __('benefits_total_label'),
                    // Sub-headers for 'Deductions'
                    __('deductions_details_label'), __('deductions_total_label')
                ]
            ];

            // Sort the reportData array by emp_id in ascending order before mapping
            reportData.sort((a, b) => a.emp_id.localeCompare(b.emp_id, undefined, { numeric: true }));

            // Prepare table body with one row per employee, matching the sub-headers
            const body = reportData.map((p, index) => {
                // Format benefits list into a multi-line string
                const benefitsDetails = p.benefits_list && p.benefits_list.length > 0
                    ? p.benefits_list.map(b => `${b.benefit || __('benefit')}: ${parseFloat(b.note || 0).toFixed(2)}`).join('\n')
                    : 'N/A';

                // Format deductions list into a multi-line string
                const deductionsDetails = p.deductions_list && p.deductions_list.length > 0
                    ? p.deductions_list.map(d => `${d.deduction || __('deduction')}: ${parseFloat(d.note || 0).toFixed(2)}`).join('\n')
                    : 'N/A';

                // Return a single array for the table row with all components
                return [
                    index + 1,
                    p.emp_id,
                    p.employee_name,
                    // Salary & Allowances Data
                    parseFloat(p.basic_salary || 0).toFixed(2),
                    parseFloat(p.housing_allowance || 0).toFixed(2),
                    parseFloat(p.transport_allowance || 0).toFixed(2),
                    parseFloat(p.food_allowance || 0).toFixed(2),
                    parseFloat(p.miscellaneous_allowance || 0).toFixed(2),
                    parseFloat(p.cashier_allowance || 0).toFixed(2),
                    parseFloat(p.fuel_allowance || 0).toFixed(2),
                    parseFloat(p.telephone_allowance || 0).toFixed(2),
                    parseFloat(p.other_allowance || 0).toFixed(2),
                    parseFloat(p.guard_allowance || 0).toFixed(2),
                    parseFloat(p.total_gross_salary || 0).toFixed(2),
                    // Benefits Data
                    benefitsDetails,
                    parseFloat(p.total_benefits || 0).toFixed(2),
                    // Deductions Data
                    deductionsDetails,
                    parseFloat(p.total_deductions || 0).toFixed(2),
                    // Net Salary Data
                    parseFloat(p.net_salary || 0).toFixed(2)
                ];
            });


            // Add report title
            doc.setFontSize(16);
            doc.text(reportTitle, doc.internal.pageSize.width / 2, 40, { align: 'center' });

            // Generate table using autoTable plugin
            doc.autoTable({
                startY: 60,
                head: head,
                body: body,
                theme: 'grid',
                headStyles: {
                    fillColor: [41, 128, 185],
                    textColor: 255,
                    fontStyle: 'bold',
                    halign: 'center',
                    fontSize: 8
                },
                styles: {
                    fontSize: 6.5,
                    cellPadding: 3,
                    valign: 'middle'
                },
                columnStyles: {
                    0: { halign: 'center' }, // #
                    1: { halign: 'center' }, // Emp ID
                    // Salary & Allowances Columns (right-aligned)
                    3: { halign: 'right' }, 4: { halign: 'right' }, 5: { halign: 'right' },
                    6: { halign: 'right' }, 7: { halign: 'right' }, 8: { halign: 'right' },
                    9: { halign: 'right' }, 10: { halign: 'right' }, 11: { halign: 'right' },
                    12: { halign: 'right' },
                    13: { halign: 'right', fontStyle: 'bold' }, // Gross Salary
                    // Benefits Columns
                    14: { halign: 'left' }, // Details
                    15: { halign: 'right' }, // Total
                    // Deductions Columns
                    16: { halign: 'left' }, // Details
                    17: { halign: 'right' }, // Total
                    // Net Salary
                    18: { halign: 'right', fontStyle: 'bold' } 
                },
                didDrawPage: function (data) {
                    // Footer
                    let str = `${__('page_footer')} ` + doc.internal.getNumberOfPages();
                    doc.setFontSize(7);
                    doc.text(str, data.settings.margin.left, doc.internal.pageSize.height - 10);
                }
            });

            // Save the PDF
            doc.save(`detailed_payroll_report_${selectedMonth.replace('-', '_')}.pdf`);
        }


        // --- Excel (XLSX) Export Function (Unchanged as per request) ---
        function exportExcelReport(reportData, selectedMonth) {
            // Ensure the XLSX library is available
            if (typeof XLSX === 'undefined') {
                console.error("The XLSX library (SheetJS) is not loaded. Please include it in your project.");
                // You could also add a user-facing message here.
                return;
            }

            // Create a new workbook
            const wb = XLSX.utils.book_new();

            // 1. Add headers row
            const headers = [
                'SER', 'ID / IQAMA', 'EMPLOYEE NAME', 'IBAN', 'BANK CODE',
                'NET SALARY', 'BASIC', 'HOUSE', 'OTHER', 'DEDUCTION',
                'ADDRESS', 'CUR', 'STATUS', 'DESCRIPTION', 'REF'
            ];

            // 2. Map reportData to the desired row format, converting strings to numbers
            // By processing the data first, we can separate logic from the sheet creation step.
            const dataRows = reportData.map((p, index) => {
                // Calculate the total for the 'OTHER' allowances column
                // We ensure all values are parsed as numbers.
                const totalAllowances =
                    parseFloat(p.transport_allowance || 0) +
                    parseFloat(p.food_allowance || 0) +
                    parseFloat(p.miscellaneous_allowance || 0) +
                    parseFloat(p.cashier_allowance || 0) +
                    parseFloat(p.fuel_allowance || 0) +
                    parseFloat(p.telephone_allowance || 0) +
                    parseFloat(p.other_allowance || 0) +
                    parseFloat(p.guard_allowance || 0);
                // Return an array representing the row.
                // We use parseFloat to ensure all monetary values are treated as numbers.
                // We DO NOT use .toFixed() here, because it converts numbers to strings,
                // which prevents Excel from recognizing them as numbers.
                return [
                    index + 1, // Serial number
                    p.iqama,
                    p.employee_name,
                    p.iban || 'N/A',
                    p.bank_name_s || 'N/A',
                    parseFloat(p.net_salary || 0),
                    parseFloat(p.basic_salary || 0),
                    parseFloat(p.housing_allowance || 0),
                    totalAllowances,
                    parseFloat(p.total_deductions || 0),
                    'INDUSTRIAL CITY',
                    'SAR',
                    'ACTIVE',
                    'PAYROLL',
                    p.sponsor
                ];
            });

            // Combine headers and data rows
            const allRows = [headers, ...dataRows];

            // Convert the array of rows into an Excel worksheet
            // The library will automatically detect data types (number, string, etc.)
            const ws = XLSX.utils.aoa_to_sheet(allRows);

            // Optional: You can explicitly set column formats if needed, for example, to show 2 decimal places.
            // This gives you more control over the appearance in Excel.
            const numberFormat = '#,##0.00';
            const columnsToFormat = ['F', 'G', 'H', 'I', 'J']; // Corresponds to NET SALARY, BASIC, etc.

            // Loop through all data rows (starting from row 2 in Excel, which is index 1 here)
            for (let i = 1; i <= dataRows.length; i++) {
                columnsToFormat.forEach(colLetter => {
                    const cellAddress = colLetter + (i + 1); // e.g., F2, G2...
                    if (ws[cellAddress]) { // Check if the cell exists
                        ws[cellAddress].z = numberFormat; // 'z' is the number format property
                    }
                });
            }
            // Add the worksheet to the workbook
            XLSX.utils.book_append_sheet(wb, ws, "Payroll Report");

            // Generate the XLSX file and trigger download with a dynamic filename
            const fileName = `payroll_report_${selectedMonth.replace('-', '_')}.xlsx`;
            XLSX.writeFile(wb, fileName);
        }

        function formatNumber(value) {
        // Parse input (default to 0 if invalid), round to 2 decimal places, and format for SA locale
            const num = parseFloat(value || 0).toFixed(2);
            return num;
        }

        function formaNumberWFractionDigits(value) {
        // Parse input (default to 0 if invalid), round to 2 decimal places, and format for SA locale
            const num = Number(parseFloat(value || 0).toFixed(2));
            return num.toLocaleString('en-SA', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function getDateParts(date = new Date(), part = null) {
            const year = date.getFullYear();
            const month = (date.getMonth() + 1).toString().padStart(2, '0');
            const day = date.getDate().toString().padStart(2, '0');
            const parts = {
                year,
                month,
                day,
                fullDate: `${year}-${month}-${day}`
            };
            return part ? parts[part] : parts;
        }

        async function loadAvailableMonthsForMainPage() {
            try {
                const response = await fetch('./includes/api/get_available_months.php'); 
                if (!response.ok) {
                    throw new Error('Failed to fetch available months for main page');
                }
                const data = await response.json();
                
                const monthSelect = $('#payrollMonth');
                monthSelect.empty(); // Clear existing options

                if (data.status === 'success' && data.months.length > 0) {
                    data.months.forEach(month => {
                        monthSelect.append($('<option>', {
                            value: month.value,
                            text: month.label
                        }));
                    });

                    // Automatically select the most recent month (first in the sorted list)
                    monthSelect.val(data.months[0].value);
                    // Crucially, call fetchEmployees here to load data for the initially selected month
                    fetchEmployees(); 
                    $('#generateReportBtn').prop('disabled', false); // Enable report button
                } else {
                    // If no months are available, show a message and disable the report button
                    monthSelect.append($('<option>', {
                        value: '',
                        text: 'No months available for payroll',
                        disabled: true,
                        selected: true
                    }));
                    $('#generateReportBtn').prop('disabled', true);
                    showInfo('No Payroll Months', 'No generated payroll months found. Please generate payrolls first.');
                }
            } catch (error) {
                console.error('Error loading available months for main page:', error);
                showError('Error', 'Could not load available months for the main filter: ' + error.message);
                $('#generateReportBtn').prop('disabled', true); // Disable button on error
            }
        }

        async function updatePayrollStatus(payrollIds, status, successCallback = null) {
            if (!payrollIds || payrollIds.length === 0) {
                showWarning(__('no_records_selected_warning_title'), __('please_select_one_record_to_update_warning'));
                return;
            }
            const confirmation = await Swal.fire({
                title: __('mark_records_as_status_q_title').replace('{0}', payrollIds.length).replace('{1}', status),
                text: __('action_cannot_be_undone'),
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                confirmButtonText: __('yes_mark_as_status_button').replace('{0}', status),
                allowOutsideClick: false,
            });

            if (!confirmation.isConfirmed) return;

            Swal.fire({ title: __('updating_status_title'), didOpen: () => Swal.showLoading(), allowOutsideClick: false });

            try {
                const response = await fetch('./includes/api/update_payroll_status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ payroll_ids: payrollIds, status: status }),
                });
                const result = await response.json();
                if (result.status === 'success') {
                    Swal.fire({ icon: 'success', title: __('status_updated_success_title'), text: result.message })
                    .then(() => successCallback && successCallback());
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                console.error('Error updating payroll status:', error);
                showError(__('update_failed_title'), error.message);
            }
        }

    </script>

    </body>

    </html>
<?php } ?>
