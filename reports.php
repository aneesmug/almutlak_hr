<?php
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/evaluation_acknowledgment_handler.php';

// Define who can see reports
$can_see_reports_page = [
    'Administrator', 
    'GM', 
    'Auditor', 
    'HR_Senior_BP', 
    'HR_Payroll', 
    'HR_Operations', 
    'HR_Supervisor', 
    'Finance_Officer', 
    'DPT_Manager', 
    'HR_Manager', 
    'Finance_Manager',
    'HR_Recruitment'
];

// Check authorization
if (!in_array($user_role, $can_see_reports_page) && $user_type !== 'is_system_admin') {
    header("Location: dashboard.php");
    exit();
}

// Determine if user has full access (can see all departments)
$has_full_access = in_array($user_role, [
    'Administrator', 
    'GM', 
    'Auditor',
    'HR_Manager',
    'HR_Senior_BP', 
    'HR_Operations', 
    'HR_Supervisor',
    'HR_Recruitment',
    'HR_Payroll',
    'Finance_Officer'
    ]) || $user_type === 'is_system_admin';

// Get user's department for filtering
$user_dept = isset($_SESSION['user_dept']) ? $_SESSION['user_dept'] : '';

$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='" . $username . "'");
if (mysqli_num_rows($query) == 1) {
    include("./includes/avatar_select.php");
?>
    <!doctype html>
    <html lang="<?= $is_rtl ? 'ar' : 'en' ?>" dir="<?= $is_rtl ? 'rtl' : 'ltr' ?>">

    <head>
        <meta charset="utf-8" />
        <title><?= $site_title ?> - Reports</title>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta content="Anees Afzal" name="author" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />

        <!-- App favicon -->
        <link rel="shortcut icon" href="<?= get_setting($conDB, 'favicon') ?>">

        <!-- App css -->
        <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />

        <!-- DataTables -->
        <link href="./plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
        <link href="./plugins/datatables/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" />
        <link href="./plugins/datatables/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />

        <!-- Select2 (CDN) -->
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap4-theme@1.0.0/dist/select2-bootstrap4.min.css" rel="stylesheet" />

        <!-- <link rel="stylesheet" href="./plugins/bootstrap-select/css/bootstrap-select.min.css"> -->
        <!-- <link rel="stylesheet" href="./plugins/select2/css/select2.min.css"> -->

        <script src="assets/js/modernizr.min.js"></script>

        <style>
            .column-selector {
                max-height: 300px;
                overflow-y: auto;
                border: 1px solid #dee2e6;
                padding: 15px;
                border-radius: 4px;
                background-color: #f8f9fa;
            }

            .column-checkbox {
                display: block;
                margin-bottom: 8px;
            }

            .filter-section {
                background-color: #fff;
                padding: 20px;
                border-radius: 4px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                margin-bottom: 20px;
            }

            .report-actions {
                margin-top: 15px;
            }

            #reportTableContainer {
                margin-top: 30px;
            }

            .select2-container--bootstrap4 .select2-selection--multiple {
                min-height: 42px;
            }

            .select2-container {
                width: 100% !important;
            }

            /* Select2 Multi-select polish */
            .select2-container {
                width: 100% !important;
            }

            .select2-container--bootstrap4 .select2-selection--multiple {
                min-height: 38px;
                border: 1px solid #ced4da;
                border-radius: 4px;
                padding: 3px 6px;
                background-color: #fff;
                transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
            }

            .select2-container--bootstrap4.select2-container--focus .select2-selection--multiple {
                border-color: #80bdff;
                outline: 0;
                box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
            }

            .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__rendered {
                display: flex;
                flex-wrap: wrap;
                gap: 4px;
                padding: 0;
            }

            .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice {
                background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
                color: #ffffff;
                border: none;
                border-radius: 14px;
                padding: 2px 10px 2px 24px;
                font-size: 12px;
                font-weight: 500;
                line-height: 18px;
                margin: 0;
                box-shadow: 0 2px 4px rgba(40, 167, 69, 0.3);
                transition: all 0.2s ease;
                position: relative;
            }

            .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice:hover {
                transform: translateY(-1px);
                box-shadow: 0 3px 6px rgba(40, 167, 69, 0.4);
            }

            .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice__remove {
                color: #fff;
                background: rgba(255, 255, 255, 0.25);
                border-radius: 50%;
                width: 16px;
                height: 16px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                margin-right: 0;
                position: absolute;
                left: 4px;
                top: 50%;
                transform: translateY(-50%);
                font-size: 14px;
                font-weight: bold;
                transition: background 0.2s ease;
            }

            .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice__remove:hover {
                background: rgba(255, 255, 255, 0.4);
                color: #fff;
            }

            .select2-dropdown {
                border: 1px solid #ced4da;
                border-radius: 4px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            }

            .select2-container--bootstrap4 .select2-dropdown .select2-search__field {
                border: 1px solid #ced4da;
                border-radius: 4px;
                padding: 6px 12px;
                font-size: 14px;
            }

            .select2-container--bootstrap4 .select2-dropdown .select2-search__field:focus {
                border-color: #80bdff;
                outline: 0;
                box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
            }

            .select2-results__option {
                font-size: 14px;
                padding: 8px 12px;
                transition: background-color 0.15s ease;
            }

            .select2-results__option--highlighted {
                background-color: #007bff !important;
                color: white !important;
            }

            .select2-results__option[aria-selected=true] {
                background-color: #f8f9fa;
                font-weight: 500;
            }

            /* Draggable Column Styles */
            .column-item {
                display: flex;
                align-items: center;
                padding: 8px 10px;
                margin-bottom: 0;
                background-color: #fff;
                border: 1px solid #dee2e6;
                border-radius: 4px;
                cursor: move;
                transition: all 0.2s ease;
                user-select: none;
                font-size: 13px;
            }

            .column-item:hover {
                background-color: #f8f9fa;
                box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
                border-color: #007bff;
            }

            .column-item.dragging {
                opacity: 0.5;
                background-color: #e7f3ff;
                border-color: #007bff;
                box-shadow: 0 3px 8px rgba(0, 123, 255, 0.3);
            }

            .column-item.drag-over {
                background-color: #d4edff;
                border-color: #0056b3;
                border-top: 2px solid #0056b3;
            }

            .column-item input[type="checkbox"] {
                margin: 0 8px 0 0;
                cursor: pointer;
                width: 16px;
                height: 16px;
                flex-shrink: 0;
            }

            .column-item .drag-handle {
                display: flex;
                align-items: center;
                justify-content: center;
                color: #999;
                font-size: 14px;
                margin-right: 6px;
                cursor: grab;
                flex-shrink: 0;
            }

            .column-item .drag-handle:active {
                cursor: grabbing;
            }

            .column-item label {
                flex-grow: 1;
                margin: 0;
                cursor: pointer;
                font-size: 13px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            #columnSortableContainer {
                scrollbar-width: thin;
                scrollbar-color: #007bff #f1f1f1;
            }

            #columnSortableContainer::-webkit-scrollbar {
                width: 8px;
            }

            #columnSortableContainer::-webkit-scrollbar-track {
                background: #f1f1f1;
                border-radius: 4px;
            }

            #columnSortableContainer::-webkit-scrollbar-thumb {
                background: #007bff;
                border-radius: 4px;
            }

            #columnSortableContainer::-webkit-scrollbar-thumb:hover {
                background: #0056b3;
            }

            #selectByTable {
                border: 1px solid #ced4da;
                border-radius: 4px;
                padding: 5px 10px;
                font-size: 13px;
            }

            #selectByTable:focus {
                border-color: #007bff;
                outline: none;
                box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
            }

            .select2-search__field {
                font-size: 14px;
            }

            /* Badge styling for selected count */
            .badge-success {
                background-color: #28a745;
                padding: 4px 10px;
                border-radius: 12px;
                font-size: 12px;
                font-weight: 600;
                margin-left: 8px;
                box-shadow: 0 2px 4px rgba(40, 167, 69, 0.3);
            }

            /* DataTables Responsive Hidden Columns */
            .dtr-hidden {
                display: none !important;
            }

            /* DataTables Control Column Styling */
            td.dt-control {
                cursor: pointer;
                padding: 5px 12px !important;
                text-align: center;
                user-select: none;
            }

            td.dt-control:before {
                content: "▶";
                display: inline-block;
                padding: 4px 8px;
                font-size: 11px;
                color: #007bff;
                transition: all 0.3s ease;
                background-color: #e7f3ff;
                border-radius: 3px;
                font-weight: bold;
                min-width: 24px;
            }

            tr.shown td.dt-control:before {
                content: "▼";
                background-color: #e8f5e9;
                color: #28a745;
            }

            tr.shown {
                background-color: #f0f8ff !important;
            }

            /* Detail Row Styling */
            .details-content {
                padding: 15px;
                background-color: #f9f9f9;
                border-radius: 4px;
                margin: 10px 0;
                border-left: 3px solid #007bff;
            }

            .details-content table {
                margin-bottom: 0 !important;
            }

            /* Horizontal Column Layout for Details */
            .details-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 15px;
                margin-top: 10px;
            }

            .detail-column {
                background-color: white;
                padding: 10px 12px;
                border-radius: 3px;
                border: 1px solid #e0e0e0;
            }

            .detail-label {
                font-size: 12px;
                font-weight: 600;
                color: #555;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                margin-bottom: 5px;
                white-space: nowrap;
            }

            .detail-value {
                font-size: 14px;
                color: #333;
                word-wrap: break-word;
                font-weight: 500;
            }

            .details-content table td {
                padding: 8px 0 !important;
            }

            .dtr-data {
                padding: 12px 0 !important;
            }

            .dtr-title {
                font-weight: 600;
                color: #333;
                padding: 5px 0;
                min-width: 150px;
            }

            @media (max-width: 768px) {
                td.dt-control {
                    display: table-cell !important;
                }

                .dtr-hidden {
                    display: none !important;
                }

                .details-content {
                    background-color: #f5f5f5;
                    border-left: 3px solid #007bff;
                    padding: 12px 15px;
                }

                .details-content table tr:last-child td {
                    border-bottom: none;
                }
            }
            
            <?php if ($is_rtl): ?>
            /* RTL overrides */
            body { direction: rtl; text-align: right; }
            .column-item { direction: rtl; }
            .column-item .drag-handle { margin-right: 0; margin-left: 6px; }
            .column-item input[type="checkbox"] { margin: 0 0 0 8px; }
            #reportTable th, #reportTable td { text-align: right; }
            .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice { padding: 2px 24px 2px 10px; }
            .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice__remove { left: auto; right: 4px; }
            <?php endif; ?>
        </style>
        <?php if ($is_rtl): ?>
            <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
        <?php endif; ?>
        <script>
            window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;
        </script>
    </head>

    <body class="enlarged<?= $is_rtl ? ' rtl' : '' ?>" data-keep-enlarged="true">

        <!-- Begin page -->
        <div id="wrapper">

            <!-- ========== Left Sidebar Start ========== -->
            <div class="left side-menu">

                <div class="slimscroll-menu" id="remove-scroll">

                    <!-- LOGO -->
                    <div class="topbar-left">
                        <a href="dashboard.php" class="logo">
                            <span>
                                <img src="<?= get_setting($conDB, 'logo') ?>" alt="" height="22">
                            </span>
                            <i>
                                <img src="<?= get_setting($conDB, 'white_logo') ?>" alt="" height="28">
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
                <div class="topbar">
                    <?php include './includes/topbar.php'; ?>
                </div>
                <!-- Top Bar End -->

                <!-- Start Page content -->
                <div class="content">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-12">
                                <div class="page-title-box">
                                    <h4 class="page-title float-left">
                                        <i class="fa fa-chart-simple mr-2"></i><?= __('reports') ?>
                                    </h4>
                                    <div class="clearfix"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Filter Section -->
                        <div class="row">
                            <div class="col-12">
                                <div class="filter-section">
                                    <h5 class="mb-3"><?= __('report_configuration') ?></h5>

                                    <div class="row">
                                        <!-- Report Type -->
                                        <div class="col-md-4 mb-3">
                                            <label for="reportType"><?= __('report_type') ?></label>
                                            <select class="form-control" id="reportType">
                                                <option value=""><?= __('select_report_type') ?></option>
                                                <option value="employee"><?= __('employee_report') ?></option>
                                                <option value="vacation"><?= __('vacation_report') ?></option>
                                                <option value="loan"><?= __('loan_report') ?></option>
                                                <option value="salary"><?= __('salary_report') ?></option>
                                                <option value="payroll"><?= __('payroll_report') ?></option>
                                                <option value="attendance"><?= __('attendance_report') ?></option>
                                                <option value="document"><?= __('document_report') ?></option>
                                                <?php if (can_acknowledge_evaluations($user_type, $user_role)):
                                                ?>
                                                    <option value="evaluation"><?= __('evaluation_report') ?></option>
                                                <?php endif; ?>
                                                <option value="resignation"><?= __('resignation_report') ?></option>
                                                <option value="terminated_employees"><?= __('terminated_employees') ?></option>
                                                <option value="eos"><?= __('calculate_end_of_service') ?></option>
                                                <option value="dept_comparison"><?= __('dept_comparison_report') ?></option>
                                                <option value="custom"><?= __('custom_report') ?></option>
                                            </select>
                                        </div> <!-- Department Filter (if authorized) -->
                                        <?php if ($has_full_access): ?>
                                            <div class="col-md-4 mb-3" id="singleDeptFilter">
                                                <label for="deptFilter"><?= __('department') ?></label>
                                                <select class="form-control" id="deptFilter">
                                                    <option value=""><?= __('all_departments') ?></option>
                                                    <?php
                                                    $dept_query = mysqli_query($conDB, "SELECT DISTINCT id, dep_nme, dep_nme_ar FROM department ORDER BY dep_nme");
                                                    while ($dept = mysqli_fetch_assoc($dept_query)) {
                                                        echo '<option value="' . $dept['id'] . '">' . ($current_lang == 'en' ? $dept['dep_nme'] : $dept['dep_nme_ar']) . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="col-md-4 mb-3" id="multiDeptFilter" style="display:none;">
                                                <label for="deptMultiFilter"><?= __('select_departments') ?></label>
                                                <select class="form-control" id="deptMultiFilter" multiple="multiple" size="8" style="height: auto;">
                                                    <option value="all" data-select-all="true">✓ <?= __('all_departments') ?></option>
                                                    <?php
                                                    $dept_query2 = mysqli_query($conDB, "SELECT DISTINCT id, dep_nme, dep_nme_ar FROM department ORDER BY dep_nme");
                                                    while ($dept = mysqli_fetch_assoc($dept_query2)) {
                                                        echo '<option value="' . $dept['id'] . '">' . ($current_lang == 'en' ? $dept['dep_nme'] : $dept['dep_nme_ar']) . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                                <small class="text-muted d-block mt-1"><?= __('select_all_or_specific_departments') ?></small>
                                            </div>
                                        <?php endif; ?> <!-- Date Range -->
                                        <div class="col-md-4 mb-3">
                                            <label for="dateFrom"><?= __('date_from') ?></label>
                                            <input type="text" class="form-control datepicker" id="dateFrom" placeholder="<?= __('select_start_date') ?>">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="dateTo"><?= __('date_to') ?></label>
                                            <input type="text" class="form-control datepicker" id="dateTo" placeholder="<?= __('select_end_date') ?>">
                                        </div>

                                        <!-- Status Filter (contextual per report) -->
                                        <div class="col-md-4 mb-3" id="statusFilterWrapper">
                                            <label for="statusFilter"><?= __('status') ?></label>
                                            <select class="form-control" id="statusFilter">
                                                <option value=""><?= __('all_status') ?></option>
                                            </select>
                                        </div>

                                        <!-- Vacation Type Filter -->
                                        <div class="col-md-4 mb-3" id="vacationTypeFilterWrapper" style="display:none;">
                                            <label for="vacationTypeFilter"><?= __('vac_type') ?></label>
                                            <select class="form-control" id="vacationTypeFilter">
                                                <option value=""><?= __('all_types') ?></option>
                                                <?php
                                                $vacTypeQuery = mysqli_query($conDB, "SELECT DISTINCT vac_type FROM emp_vacation WHERE vac_type <> '' ORDER BY vac_type");
                                                while ($vacType = mysqli_fetch_assoc($vacTypeQuery)) {
                                                    $value = htmlspecialchars($vacType['vac_type'], ENT_QUOTES, 'UTF-8');
                                                    echo '<option value="' . $value . '">' . __(strtolower(str_replace(' ', '_', ucfirst($value)))) . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>

                                        <!-- Custom Report Table Selection -->
                                        <div class="col-md-4 mb-3" id="customTableSelection" style="display:none;">
                                            <label for="customTables"><?= __('select_tables_multiselect') ?></label>
                                            <select class="form-control" id="customTables" multiple="multiple" style="width: 100%; height: auto;">
                                                <?php
                                                // User-friendly table names mapping - only these tables will be available
                                                $table_names = [
                                                    'employees' => __('employees'),
                                                    'department' => __('departments'),
                                                    'section' => __('sections'),
                                                    'ac_jobs' => __('job_titles'),
                                                    'countries' => __('countries'),
                                                    'bank_list' => __('banks'),
                                                    'emp_vacation' => __('employee_vacations'),
                                                    'emp_loan' => __('employee_loans'),
                                                    'emp_loan_payments' => __('loan_payments'),
                                                    'emp_loan_approvals' => __('loan_approvals'),
                                                    'emp_loan_monthly_status' => __('loan_monthly_status'),
                                                    'emp_salary' => __('employee_salaries'),
                                                    'emp_docu' => __('employee_documents'),
                                                    'emp_eos' => __('end_of_service'),
                                                    'emp_vacation_balance' => __('vacation_balance'),
                                                    'emp_notice' => __('employee_notices'),
                                                    'emp_resignations' => __('resignations'),
                                                    'emp_resignation_history' => __('resignation_history'),
                                                    'emp_resignation_attachments' => __('resignation_attachments'),
                                                    'emp_resignation_clearance' => __('resignation_clearance'),
                                                    'emp_exit_interviews' => __('exit_interviews'),
                                                    'payrolls' => __('payrolls'),
                                                    'attendance' => __('attendance_records'),
                                                    'gender' => __('gender'),
                                                    'user_type' => __('user_types'),
                                                    'locations' => __('locations'),
                                                    'machines' => __('machines'),
                                                    'cars' => __('vehicles'),
                                                    'brands' => __('brands'),
                                                    'activity_log' => __('activity_log')
                                                ];

                                                // Add emp_evaluations only for authorized users
                                                if (can_acknowledge_evaluations($user_type, $user_role)) {
                                                    $table_names['emp_evaluations'] = __('employee_evaluations');
                                                }

                                                // Display only the tables defined in $table_names array
                                                foreach ($table_names as $table_key => $table_display_name) {
                                                    echo '<option value="' . $table_key . '">' . $table_display_name . '</option>';
                                                }
                                                ?>
                                            </select>
                                            <small class="text-muted d-block mt-1"><?= __('select_one_or_more_tables') ?></small>
                                        </div>

                                        <!-- Department Filter for Custom Report -->
                                        <div class="col-md-4 mb-3" id="customDeptFilter" style="display:none;">
                                            <label for="customDeptMultiFilter"><?= __('select_departments') ?></label>
                                            <select class="form-control" id="customDeptMultiFilter" multiple="multiple" style="width: 100%; height: auto;">
                                                <option value="all" data-select-all="true">✓ <?= __('all_departments') ?></option>
                                                <?php
                                                $dept_query_custom = mysqli_query($conDB, "SELECT DISTINCT id, dep_nme, dep_nme_ar FROM department ORDER BY dep_nme");
                                                while ($dept = mysqli_fetch_assoc($dept_query_custom)) {
                                                    echo '<option value="' . $dept['id'] . '">' . ($current_lang == 'en' ? $dept['dep_nme'] : $dept['dep_nme_ar']) . '</option>';
                                                }
                                                ?>
                                            </select>
                                            <small class="text-muted d-block mt-1"><?= __('select_all_or_specific_departments') ?></small>
                                        </div>

                                        <!-- Date Range Filter for Custom Report -->
                                        <div class="col-md-4 mb-3" id="customDateFromFilter" style="display:none;">
                                            <label for="customDateFrom"><?= __('from_date') ?></label>
                                            <input type="text" class="form-control datepicker" id="customDateFrom" placeholder="<?= __('select_start_date') ?>">
                                            <small class="text-muted"><?= __('optional_filter_from_date') ?></small>
                                        </div>
                                        <div class="col-md-4 mb-3" id="customDateToFilter" style="display:none;">
                                            <label for="customDateTo"><?= __('to_date') ?></label>
                                            <input type="text" class="form-control datepicker" id="customDateTo" placeholder="<?= __('select_end_date') ?>">
                                            <small class="text-muted"><?= __('optional_filter_to_date') ?></small>
                                        </div>
                                    </div>

                                    <!-- Column Selection -->
                                    <div class="row mt-3" id="columnSelectionRow" style="display:none;">
                                        <div class="col-12">
                                            <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap">
                                                <label for="columnMultiSelect" class="mb-0 mb-md-0"><strong><?= __('select_columns_to_display') ?></strong> <span id="selectedColumnCount" class="badge badge-primary">0 selected</span></label>
                                                <div class="d-flex align-items-center flex-wrap">
                                                    <div class="mr-2 mb-2 mb-md-0" id="selectByTableContainer" style="display:none;">
                                                        <select class="form-control form-control-sm" id="selectByTable" style="width: auto; min-width: 200px;">
                                                            <option value=""><?= __('select_by_table') ?></option>
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <button type="button" class="btn btn-sm btn-info mr-2" id="selectAllColumnsBtn">
                                                            <i class="mdi mdi-check-all mr-1"></i><?= __('select_all') ?>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-warning" id="deselectAllColumnsBtn">
                                                            <i class="mdi mdi-close-circle mr-1"></i><?= __('deselect_all') ?>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div id="columnSortableContainer" style="border: 1px solid #ddd; border-radius: 4px; padding: 15px; background-color: #f9f9f9; max-height: 400px; overflow-y: auto; display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 8px;">
                                                <!-- Sortable columns will be rendered here -->
                                            </div>
                                            <small class="text-muted d-block mt-2"><i class="mdi mdi-information mr-1"></i><?= __('drag_columns_to_reorder_click_checkbox_to_select_deselect_scroll_to_see_more') ?></small>
                                        </div>
                                    </div>

                                    <!-- Report Actions -->
                                    <div class="report-actions">
                                        <button type="button" class="btn btn-primary" id="generateReportBtn">
                                            <i class="mdi mdi-file-chart mr-1"></i><?= __('generate_report') ?>
                                        </button>
                                        <button type="button" class="btn btn-success" id="exportExcelBtn" style="display:none;">
                                            <i class="mdi mdi-file-excel mr-1"></i><?= __('export_to_excel') ?>
                                        </button>
                                        <button type="button" class="btn btn-secondary" id="resetBtn">
                                            <i class="mdi mdi-refresh mr-1"></i><?= __('reset') ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Report Table Container -->
                        <div class="row" id="reportTableContainer" style="display:none;">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title" id="reportTitle">Report Results</h5>
                                        <div class="table-responsive">
                                            <table id="reportTable" class="table table-bordered table-striped dt-responsive nowrap" style="width:100%">
                                                <thead id="reportTableHead">
                                                    <!-- Dynamic headers -->
                                                </thead>
                                                <tbody id="reportTableBody">
                                                    <!-- Dynamic rows -->
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div> <!-- container -->
                </div> <!-- content -->

                <footer class="footer text-right">
                    &copy; <?= date("Y") ?> <?= get_setting($conDB, 'company_name') ?>
                </footer>

            </div>
            <!-- ============================================================== -->
            <!-- End Right content here -->
            <!-- ============================================================== -->

        </div>
        <!-- END wrapper -->

        <!-- jQuery  -->
        <script src="assets/js/jquery.min.js"></script>
        <!-- <script src="assets/js/popper.min.js"></script> -->
        <script src="assets/js/bootstrap.min.js"></script>
        <script src="assets/js/metisMenu.min.js"></script>
        <script src="assets/js/waves.js"></script>
        <script src="assets/js/jquery.slimscroll.js"></script>

        <!-- DataTables -->
        <script src="./plugins/datatables/jquery.dataTables.min.js"></script>
        <script src="./plugins/datatables/dataTables.bootstrap4.min.js"></script>
        <script src="./plugins/datatables/dataTables.buttons.min.js"></script>
        <script src="./plugins/datatables/buttons.bootstrap4.min.js"></script>
        <script src="./plugins/datatables/jszip.min.js"></script>
        <script src="./plugins/datatables/pdfmake.min.js"></script>
        <script src="./plugins/datatables/vfs_fonts.js"></script>
        <script src="./plugins/datatables/buttons.html5.min.js"></script>
        <script src="./plugins/datatables/buttons.print.min.js"></script>
        <script src="./plugins/datatables/dataTables.responsive.min.js"></script>
        <script src="./plugins/datatables/responsive.bootstrap4.min.js"></script>

        <!-- Date Picker -->
        <script src="./plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>

        <!-- SweetAlert2 -->
        <!-- <script src="./assets/plugins/sweet-alert2/sweetalert2.min.js"></script> -->

        <!-- Select2 (CDN) -->
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

        <!-- App js -->
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js"></script>

        <script>
            $(document).ready(function() {
                // Hide department and date filters by default until a report type is selected
                $('#singleDeptFilter').hide();
                $('#multiDeptFilter').hide();
                $('#dateFrom').closest('.col-md-4, .form-group').hide();
                $('#dateTo').closest('.col-md-4, .form-group').hide();
                
                // Initialize date pickers with RTL support (using global function)
                initializeDatepickerRTL();

                // Format function for dropdown options with checkmarks
                function formatDeptOption(data) {
                    if (!data.id) {
                        return data.text;
                    }

                    // Special formatting for "All Departments"
                    if (data.id === 'all') {
                        var $result = $('<span><i class="mdi mdi-select-all text-primary mr-2" style="font-size: 16px;"></i><strong>' + data.text + '</strong></span>');
                        return $result;
                    }
                    // Special formatting for "Deselect All"
                    if (data.id === 'none') {
                        var $result = $('<span><i class="mdi mdi-close-circle text-danger mr-2" style="font-size: 16px;"></i><strong>' + data.text + '</strong></span>');
                        return $result;
                    }
                    var $select = $('#deptMultiFilter');
                    var selectedValues = $select.val() || [];
                    var isSelected = selectedValues.indexOf(data.id) !== -1;

                    var checkmark = isSelected ?
                        '<i class="mdi mdi-check-circle text-success mr-2" style="font-size: 16px;"></i>' :
                        '<i class="mdi mdi-checkbox-blank-circle-outline text-muted mr-2" style="font-size: 16px;"></i>';

                    var $result = $('<span>' + checkmark + data.text + '</span>');
                    return $result;
                }

                // Initialize Select2 for multi-department filter (only if plugin loaded)
                function initDeptSelect2() {
                    if ($.fn.select2) {
                        // Dynamically add Deselect All option if all departments are selected
                        function updateDeselectOption() {
                            var $select = $('#deptMultiFilter');
                            var allValues = [];
                            $select.find('option').each(function() {
                                if ($(this).val() !== 'all' && $(this).val() !== 'none') {
                                    allValues.push($(this).val());
                                }
                            });
                            var selected = $select.val() || [];
                            var $none = $select.find('option[value="none"]');
                            if (selected.length === allValues.length && !$none.length) {
                                $select.prepend(`<option value="none" data-deselect-all="true">✗ ${__('deselect_all')}</option>`);
                            } else if (selected.length !== allValues.length && $none.length) {
                                $none.remove();
                            }
                        }
                        $('#deptMultiFilter').select2({
                            theme: 'bootstrap4',
                            placeholder: __('select_departments'),
                            allowClear: true,
                            closeOnSelect: false,
                            width: '100%',
                            templateResult: formatDeptOption,
                            templateSelection: function(data) {
                                if (data.id === 'all') {
                                    return __('all_departments')
                                }
                                if (data.id === 'none') {
                                    return __('deselect_all')
                                }
                                return data.text;
                            }
                        });
                        // Handle All Departments selection
                        $('#deptMultiFilter').on('select2:select', function(e) {
                            var data = e.params.data;
                            if (data.id === 'all') {
                                var allValues = [];
                                $('#deptMultiFilter option').each(function() {
                                    if ($(this).val() !== 'all' && $(this).val() !== 'none') {
                                        allValues.push($(this).val());
                                    }
                                });
                                $(this).val(allValues).trigger('change');
                            }
                            if (data.id === 'none') {
                                $(this).val(null).trigger('change');
                            }
                            updateDeptSelectionCount();
                            updateDeselectOption();
                            var $select = $(this);
                            if ($select.data('select2') && $select.data('select2').isOpen()) {
                                $select.select2('close');
                                setTimeout(function() {
                                    $select.select2('open');
                                }, 50);
                            }
                        });
                        $('#deptMultiFilter').on('select2:unselect', function(e) {
                            updateDeptSelectionCount();
                            updateDeselectOption();
                            var $select = $(this);
                            if ($select.data('select2') && $select.data('select2').isOpen()) {
                                $select.select2('close');
                                setTimeout(function() {
                                    $select.select2('open');
                                }, 50);
                            }
                        });
                        $('#deptMultiFilter').on('change', function() {
                            updateDeselectOption();
                        });
                        updateDeptSelectionCount();
                        updateDeselectOption();
                    }
                }

                function initCustomTablesSelect2() {
                    if ($.fn.select2) {
                        $('#customTables').select2({
                            theme: 'bootstrap4',
                            placeholder: (typeof __ === 'function') ? __('select_tables_to_generate_report') : 'Select tables to generate report',
                            allowClear: true,
                            closeOnSelect: false,
                            width: '100%'
                        });
                    }
                }

                function initCustomDeptSelect2() {
                    if ($.fn.select2) {
                        // Dynamically add Deselect All option if all departments are selected
                        function updateCustomDeselectOption() {
                            var $select = $('#customDeptMultiFilter');
                            var allValues = [];
                            $select.find('option').each(function() {
                                if ($(this).val() !== 'all' && $(this).val() !== 'none') {
                                    allValues.push($(this).val());
                                }
                            });
                            var selected = $select.val() || [];
                            var $none = $select.find('option[value="none"]');
                            if (selected.length === allValues.length && !$none.length) {
                                $select.prepend(`<option value="none" data-deselect-all="true">✗ ${__('deselect_all')}</option>`);
                            } else if (selected.length !== allValues.length && $none.length) {
                                $none.remove();
                            }
                        }
                        $('#customDeptMultiFilter').select2({
                            theme: 'bootstrap4',
                            placeholder: (typeof __ === 'function') ? __('select_departments') : 'Select departments',
                            allowClear: true,
                            closeOnSelect: false,
                            width: '100%',
                            templateResult: formatDeptOption,
                            templateSelection: function(data) {
                                if (data.id === 'all') {
                                    return (typeof __ === 'function') ? __('all_departments') : 'All Departments';
                                }
                                if (data.id === 'none') {
                                    return (typeof __ === 'function') ? __('deselect_all') : 'Deselect All';
                                }
                                return data.text;
                            }
                        });
                        // Handle All Departments selection
                        $('#customDeptMultiFilter').on('select2:select', function(e) {
                            var data = e.params.data;
                            if (data.id === 'all') {
                                var allValues = [];
                                $('#customDeptMultiFilter option').each(function() {
                                    if ($(this).val() !== 'all' && $(this).val() !== 'none') {
                                        allValues.push($(this).val());
                                    }
                                });
                                $(this).val(allValues).trigger('change');
                            }
                            if (data.id === 'none') {
                                $(this).val(null).trigger('change');
                            }
                            updateCustomDeselectOption();
                            var $select = $(this);
                            if ($select.data('select2') && $select.data('select2').isOpen()) {
                                $select.select2('close');
                                setTimeout(function() {
                                    $select.select2('open');
                                }, 50);
                            }
                        });
                        $('#customDeptMultiFilter').on('select2:unselect', function(e) {
                            updateCustomDeselectOption();
                            var $select = $(this);
                            if ($select.data('select2') && $select.data('select2').isOpen()) {
                                $select.select2('close');
                                setTimeout(function() {
                                    $select.select2('open');
                                }, 50);
                            }
                        });
                        $('#customDeptMultiFilter').on('change', function() {
                            updateCustomDeselectOption();
                        });
                        updateCustomDeselectOption();
                    }
                }

                // Status filter options per report type
                const statusOptionsConfig = {
                    default: {
                        options: [{ value: '', label: (typeof __ === 'function') ? __('all_status') : 'All Status' }],
                        defaultValue: ''
                    },
                    employee: {
                        options: [
                            { value: '', label: (typeof __ === 'function') ? __('all_status') : 'All Status' },
                            { value: '1', label: (typeof __ === 'function') ? __('active') : 'Active' },
                            { value: '0', label: (typeof __ === 'function') ? __('inactive') : 'Inactive' }
                        ],
                        defaultValue: '1'
                    },
                    salary: {
                        options: [
                            { value: '', label: (typeof __ === 'function') ? __('all_status') : 'All Status' },
                            { value: '1', label: (typeof __ === 'function') ? __('active') : 'Active' },
                            { value: '0', label: (typeof __ === 'function') ? __('inactive') : 'Inactive' }
                        ],
                        defaultValue: '1'
                    },
                    vacation: {
                        options: [
                            { value: '', label: (typeof __ === 'function') ? __('all_status') : 'All Status' },
                            { value: 'draft', label: (typeof __ === 'function') ? __('draft') : 'Draft' },
                            { value: 'pending_approval', label: (typeof __ === 'function') ? __('pending_approval') : 'Pending Approval' },
                            { value: 'approved', label: (typeof __ === 'function') ? __('approved') : 'Approved' },
                            { value: 'rejected', label: (typeof __ === 'function') ? __('rejected') : 'Rejected' },
                            { value: 'completed', label: (typeof __ === 'function') ? __('completed') : 'Completed' }
                        ],
                        defaultValue: ''
                    },
                    loan: {
                        options: [
                            { value: '', label: (typeof __ === 'function') ? __('all_status') : 'All Status' },
                            { value: 'pending_level_1', label: (typeof __ === 'function') ? __('pending_level_1') : 'Pending Level 1' },
                            { value: 'pending_level_2', label: (typeof __ === 'function') ? __('pending_level_2') : 'Pending Level 2' },
                            { value: 'pending_level_3', label: (typeof __ === 'function') ? __('pending_level_3') : 'Pending Level 3' },
                            { value: 'pending_level_4', label: (typeof __ === 'function') ? __('pending_level_4') : 'Pending Level 4' },
                            { value: 'pending_level_5', label: (typeof __ === 'function') ? __('pending_level_5') : 'Pending Level 5' },
                            { value: 'pending_level_6', label: (typeof __ === 'function') ? __('pending_level_6') : 'Pending Level 6' },
                            { value: 'approved', label: (typeof __ === 'function') ? __('approved') : 'Approved' },
                            { value: 'rejected', label: (typeof __ === 'function') ? __('rejected') : 'Rejected' },
                            { value: 'paid', label: (typeof __ === 'function') ? __('paid') : 'Paid' }
                        ],
                        defaultValue: ''
                    },
                    payroll: {
                        options: [
                            { value: '', label: (typeof __ === 'function') ? __('all_status') : 'All Status' },
                            { value: 'generated', label: (typeof __ === 'function') ? __('generated') : 'Generated' },
                            { value: 'updated', label: (typeof __ === 'function') ? __('updated') : 'Updated' }
                        ],
                        defaultValue: ''
                    },
                    document: {
                        options: [
                            { value: '', label: (typeof __ === 'function') ? __('all_status') : 'All Status' },
                            { value: 'A', label: (typeof __ === 'function') ? __('active') : 'Active' },
                            { value: 'I', label: (typeof __ === 'function') ? __('inactive') : 'Inactive' }
                        ],
                        defaultValue: ''
                    },
                    evaluation: {
                        options: [
                            { value: '', label: (typeof __ === 'function') ? __('all_status') : 'All Status' },
                            { value: 'objected', label: (typeof __ === 'function') ? __('objected_evaluations') : 'Objected Evaluations' }
                        ],
                        defaultValue: ''
                    },
                    resignation: {
                        options: [
                            { value: '', label: (typeof __ === 'function') ? __('all_status') : 'All Status' },
                            { value: 'pending', label: (typeof __ === 'function') ? __('pending') : 'Pending' },
                            { value: 'approved', label: (typeof __ === 'function') ? __('approved') : 'Approved' },
                            { value: 'rejected', label: (typeof __ === 'function') ? __('rejected') : 'Rejected' },
                            { value: 'cancelled', label: (typeof __ === 'function') ? __('cancelled') : 'Cancelled' },
                            { value: 'withdrawn', label: (typeof __ === 'function') ? __('withdrawn') : 'Withdrawn' }
                        ],
                        defaultValue: ''
                    },
                    attendance: { hide: true },
                    terminated_employees: { hide: true },
                    eos: { hide: true },
                    dept_comparison: { hide: true },
                    custom: { hide: true }
                };

                function renderStatusFilter(reportType) {
                    const cfg = statusOptionsConfig[reportType] || statusOptionsConfig.default;
                    const $wrapper = $('#statusFilterWrapper');
                    const $select = $('#statusFilter');

                    if (cfg.hide) {
                        $wrapper.hide();
                        $select.val('');
                        return;
                    }

                    $wrapper.show();
                    $select.empty();

                    (cfg.options || []).forEach(function(opt) {
                        $select.append(`<option value="${opt.value}">${opt.label}</option>`);
                    });

                    $select.val(cfg.defaultValue || '');
                }

                function toggleVacationTypeFilter(reportType) {
                    if (reportType === 'vacation') {
                        $('#vacationTypeFilterWrapper').show();
                    } else {
                        $('#vacationTypeFilter').val('');
                        $('#vacationTypeFilterWrapper').hide();
                    }
                }

                initDeptSelect2();

                // Initialize status filter and vacation type visibility
                renderStatusFilter('');
                toggleVacationTypeFilter('');

                // Client-side translation for custom column selector labels
                function normalizeKey(str) {
                    if (!str) return '';
                    return String(str)
                        .trim()
                        .replace(/[\s\-]+/g, '_')
                        .replace(/__+/g, '_')
                        .toLowerCase();
                }

                function translateLabel(raw) {
                    var key = normalizeKey(raw);
                    // Try full key via __()
                    if (typeof __ === 'function') {
                        var t1 = __(key);
                        if (t1 && t1 !== key) return t1;
                        // Try last segment if composite key
                        if (key.indexOf('_') !== -1) {
                            var parts = key.split('_');
                            var base = parts[parts.length - 1];
                            var t2 = __(base);
                            if (t2 && t2 !== base) return t2;
                        }
                    }
                    // Fallback to humanized text
                    return raw;
                }

                // Observe column items container to translate labels when rendered
                var columnContainer = document.getElementById('columnSortableContainer');
                if (columnContainer) {
                    var observer = new MutationObserver(function(mutations) {
                        mutations.forEach(function(mutation) {
                            if (mutation.type === 'childList' && mutation.addedNodes.length) {
                                mutation.addedNodes.forEach(function(node) {
                                    if (node.nodeType === 1) {
                                        var label = node.querySelector && node.querySelector('label');
                                        if (label && label.textContent) {
                                            var newText = translateLabel(label.textContent);
                                            if (newText && newText !== label.textContent) {
                                                label.textContent = newText;
                                            }
                                        }
                                    }
                                });
                            }
                        });
                    });
                    observer.observe(columnContainer, { childList: true, subtree: true });
                    // Initial pass for already-rendered items
                    $('#columnSortableContainer label').each(function() {
                        var txt = $(this).text();
                        var t = translateLabel(txt);
                        if (t && t !== txt) $(this).text(t);
                    });
                }

                // Column definitions for each report type
                const reportColumns = {
                    employee: [{
                            id: 'name',
                            label: (typeof __ === 'function') ? __('name') : 'Name',
                            default: true
                        },
                        {
                            id: 'emp_id',
                            label: (typeof __ === 'function') ? __('emp_id') : 'Employee ID',
                            default: true
                        },
                        {
                            id: 'iqama',
                            label: (typeof __ === 'function') ? __('iqama') : 'Iqama',
                            default: true
                        },
                        {
                            id: 'iqama_exp',
                            label: (typeof __ === 'function') ? __('iqama_exp') : 'Iqama Expiry',
                            default: false
                        },
                        {
                            id: 'passport_number',
                            label: (typeof __ === 'function') ? __('passport_number') : 'Passport Number',
                            default: false
                        },
                        {
                            id: 'passport_exp',
                            label: (typeof __ === 'function') ? __('passport_exp') : 'Passport Expiry',
                            default: false
                        },
                        {
                            id: 'mobile',
                            label: (typeof __ === 'function') ? __('mobile') : 'Mobile',
                            default: true
                        },
                        {
                            id: 'emg_mobile',
                            label: (typeof __ === 'function') ? __('emg_mobile') : 'Emergency Mobile',
                            default: false
                        },
                        {
                            id: 'emg_name',
                            label: (typeof __ === 'function') ? __('emg_name') : 'Emergency Contact Name',
                            default: false
                        },
                        {
                            id: 'email',
                            label: (typeof __ === 'function') ? __('email') : 'Email',
                            default: false
                        },
                        {
                            id: 'c_email',
                            label: (typeof __ === 'function') ? __('c_email') : 'Corporate Email',
                            default: false
                        },
                        {
                            id: 'address',
                            label: (typeof __ === 'function') ? __('address') : 'Address',
                            default: false
                        },
                        {
                            id: 'comp_no',
                            label: (typeof __ === 'function') ? __('comp_no') : 'Company',
                            default: true
                        },
                        {
                            id: 'dept',
                            label: (typeof __ === 'function') ? __('dept') : 'Department',
                            default: true
                        },
                        {
                            id: 'sectin_nme',
                            label: (typeof __ === 'function') ? __('sectin_nme') : 'Section',
                            default: false
                        },
                        {
                            id: 'actual_job',
                            label: (typeof __ === 'function') ? __('actual_job') : 'Job Title',
                            default: true
                        },
                        {
                            id: 'emptype',
                            label: (typeof __ === 'function') ? __('emptype') : 'Employee Type',
                            default: true
                        },
                        {
                            id: 'salary',
                            label: (typeof __ === 'function') ? __('salary') : 'Salary',
                            default: false
                        },
                        {
                            id: 'joining_date',
                            label: (typeof __ === 'function') ? __('joining_date') : 'Joining Date',
                            default: true
                        },
                        {
                            id: 'contract_expiry',
                            label: (typeof __ === 'function') ? __('contract_expiry') : 'Contract Expiry',
                            default: true
                        },
                        {
                            id: 'ter_date',
                            label: (typeof __ === 'function') ? __('ter_date') : 'Termination Date',
                            default: true
                        },
                        {
                            id: 'country',
                            label: (typeof __ === 'function') ? __('country') : 'Nationality',
                            default: false
                        },
                        {
                            id: 'supervisor_id',
                            label: (typeof __ === 'function') ? __('supervisor_id') : 'Supervisor',
                            default: false
                        },
                        {
                            id: 'vacation_days',
                            label: (typeof __ === 'function') ? __('vacation_days') : 'Vacation Days',
                            default: false
                        },
                        {
                            id: 'fly',
                            label: (typeof __ === 'function') ? __('fly') : 'Flight Ticket',
                            default: false
                        },
                        {
                            id: 'bank_name',
                            label: (typeof __ === 'function') ? __('bank_name') : 'Bank Name',
                            default: false
                        },
                        {
                            id: 'iban',
                            label: (typeof __ === 'function') ? __('iban') : 'IBAN',
                            default: false
                        },
                        {
                            id: 'dob',
                            label: (typeof __ === 'function') ? __('dob') : 'Date of Birth',
                            default: false
                        },
                        {
                            id: 'sex',
                            label: (typeof __ === 'function') ? __('sex') : 'Gender',
                            default: false
                        },
                        {
                            id: 'blood_type',
                            label: (typeof __ === 'function') ? __('blood_type') : 'Blood Type',
                            default: false
                        },
                        {
                            id: 'mar_status',
                            label: (typeof __ === 'function') ? __('mar_status') : 'Marital Status',
                            default: false
                        },
                        {
                            id: 'gosi',
                            label: (typeof __ === 'function') ? __('gosi') : 'GOSI',
                            default: false
                        },
                        {
                            id: 'insurance_no',
                            label: (typeof __ === 'function') ? __('insurance_no') : 'Insurance No',
                            default: false
                        },
                        {
                            id: 'insurance_class',
                            label: (typeof __ === 'function') ? __('insurance_class') : 'Insurance Class',
                            default: false
                        },
                        {
                            id: 'insurance_exp',
                            label: (typeof __ === 'function') ? __('insurance_exp') : 'Insurance Expiry',
                            default: false
                        },
                        {
                            id: 'emp_sup_type',
                            label: (typeof __ === 'function') ? __('emp_sup_type') : 'Sponsorship Type',
                            default: false
                        },
                        {
                            id: 'vac_period',
                            label: (typeof __ === 'function') ? __('vac_period') : 'Vacation Period',
                            default: false
                        },
                        {
                            id: 't_shirt_size',
                            label: (typeof __ === 'function') ? __('t_shirt_size') : 'T-Shirt Size',
                            default: false
                        },
                        {
                            id: 'probation',
                            label: (typeof __ === 'function') ? __('probation') : 'Probation',
                            default: false
                        },
                        {
                            id: 'payment_type',
                            label: (typeof __ === 'function') ? __('payment_type') : 'Payment Type',
                            default: false
                        },
                        {
                            id: 'status',
                            label: (typeof __ === 'function') ? __('status') : 'Status',
                            default: true
                        }
                    ],
                    vacation: [{
                            id: 'emp_id',
                            label: (typeof __ === 'function') ? __('emp_id') : 'Employee ID',
                            default: true
                        },
                        {
                            id: 'emp_name',
                            label: (typeof __ === 'function') ? __('emp_name') : 'Employee Name',
                            default: true
                        },
                        {
                            id: 'dept',
                            label: (typeof __ === 'function') ? __('dept') : 'Department',
                            default: true
                        },
                        {
                            id: 'vac_type',
                            label: (typeof __ === 'function') ? __('vac_type') : 'Vacation Type',
                            default: true
                        },
                        {
                            id: 'start_date',
                            label: (typeof __ === 'function') ? __('start_date') : 'Start Date',
                            default: true
                        },
                        {
                            id: 'return_date',
                            label: (typeof __ === 'function') ? __('return_date') : 'Return Date',
                            default: true
                        },
                        {
                            id: 'vacdays',
                            label: (typeof __ === 'function') ? __('vacdays') : 'Days',
                            default: true
                        },
                        {
                            id: 'fly_type',
                            label: (typeof __ === 'function') ? __('fly_type') : 'Flight Type',
                            default: false
                        },
                        {
                            id: 'permit_no',
                            label: (typeof __ === 'function') ? __('permit_no') : 'Permit No',
                            default: false
                        },
                        {
                            id: 'current_status',
                            label: (typeof __ === 'function') ? __('current_status') : 'Status',
                            default: true
                        },
                        {
                            id: 'created_at',
                            label: (typeof __ === 'function') ? __('created_at') : 'Applied Date',
                            default: false
                        }
                    ],
                    loan: [{
                            id: 'emp_id',
                            label: (typeof __ === 'function') ? __('emp_id') : 'Employee ID',
                            default: true
                        },
                        {
                            id: 'emp_name',
                            label: (typeof __ === 'function') ? __('emp_name') : 'Employee Name',
                            default: true
                        },
                        {
                            id: 'dept',
                            label: (typeof __ === 'function') ? __('dept') : 'Department',
                            default: true
                        },
                        {
                            id: 'loan_amount',
                            label: (typeof __ === 'function') ? __('loan_amount') : 'Loan Amount',
                            default: true
                        },
                        {
                            id: 'monthly_deduction',
                            label: (typeof __ === 'function') ? __('monthly_deduction') : 'Monthly Deduction',
                            default: true
                        },
                        {
                            id: 'start_date',
                            label: (typeof __ === 'function') ? __('start_date') : 'Start Date',
                            default: true
                        },
                        {
                            id: 'end_date',
                            label: (typeof __ === 'function') ? __('end_date') : 'End Date',
                            default: true
                        },
                        {
                            id: 'loan_type',
                            label: (typeof __ === 'function') ? __('loan_type') : 'Loan Type',
                            default: true
                        },
                        {
                            id: 'status',
                            label: (typeof __ === 'function') ? __('status') : 'Status',
                            default: true
                        },
                        {
                            id: 'final_approved_amount',
                            label: (typeof __ === 'function') ? __('final_approved_amount') : 'Approved Amount',
                            default: false
                        },
                        {
                            id: 'total_payable',
                            label: (typeof __ === 'function') ? __('total_payable') : 'Total Payable',
                            default: false
                        },
                        {
                            id: 'remaining_amount',
                            label: (typeof __ === 'function') ? __('remaining_amount') : 'Remaining Amount',
                            default: true
                        }
                    ],
                    salary: [{
                            id: 'emp_id',
                            label: (typeof __ === 'function') ? __('emp_id') : 'Employee ID',
                            default: true
                        },
                        {
                            id: 'emp_name',
                            label: (typeof __ === 'function') ? __('emp_name') : 'Employee Name',
                            default: true
                        },
                        {
                            id: 'dept',
                            label: (typeof __ === 'function') ? __('dept') : 'Department',
                            default: true
                        },
                        {
                            id: 'basic',
                            label: (typeof __ === 'function') ? __('basic') : 'Basic Salary',
                            default: true
                        },
                        {
                            id: 'housing',
                            label: (typeof __ === 'function') ? __('housing') : 'Housing',
                            default: true
                        },
                        {
                            id: 'transport',
                            label: (typeof __ === 'function') ? __('transport') : 'Transport',
                            default: true
                        },
                        {
                            id: 'food',
                            label: (typeof __ === 'function') ? __('food') : 'Food',
                            default: false
                        },
                        {
                            id: 'misc',
                            label: (typeof __ === 'function') ? __('misc') : 'Misc',
                            default: false
                        },
                        {
                            id: 'fuel',
                            label: (typeof __ === 'function') ? __('fuel') : 'Fuel',
                            default: false
                        },
                        {
                            id: 'tel',
                            label: (typeof __ === 'function') ? __('tel') : 'Telephone',
                            default: false
                        },
                        {
                            id: 'cashier',
                            label: (typeof __ === 'function') ? __('cashier') : 'Cashier',
                            default: false
                        },
                        {
                            id: 'other',
                            label: (typeof __ === 'function') ? __('other') : 'Other',
                            default: false
                        },
                        {
                            id: 'guard',
                            label: (typeof __ === 'function') ? __('guard') : 'Guard',
                            default: false
                        },
                        {
                            id: 'total_salary',
                            label: (typeof __ === 'function') ? __('total_salary') : 'Total Salary',
                            default: true
                        }
                    ],
                    payroll: [{
                            id: 'payroll_id',
                            label: (typeof __ === 'function') ? __('payroll_id') : 'Payroll ID',
                            default: true
                        },
                        {
                            id: 'month',
                            label: (typeof __ === 'function') ? __('month') : 'Month',
                            default: true
                        },
                        {
                            id: 'year',
                            label: (typeof __ === 'function') ? __('year') : 'Year',
                            default: true
                        },
                        {
                            id: 'total_employees',
                            label: (typeof __ === 'function') ? __('total_employees') : 'Total Employees',
                            default: true
                        },
                        {
                            id: 'total_salary',
                            label: (typeof __ === 'function') ? __('total_salary') : 'Total Salary',
                            default: true
                        },
                        {
                            id: 'total_deductions',
                            label: (typeof __ === 'function') ? __('total_deductions') : 'Total Deductions',
                            default: true
                        },
                        {
                            id: 'net_salary',
                            label: (typeof __ === 'function') ? __('net_salary') : 'Net Salary',
                            default: true
                        },
                        {
                            id: 'generated_by',
                            label: (typeof __ === 'function') ? __('generated_by') : 'Generated By',
                            default: false
                        },
                        {
                            id: 'created_at',
                            label: (typeof __ === 'function') ? __('generated_date') : 'Generated Date',
                            default: true
                        }
                    ],
                    attendance: [{
                            id: 'emp_id',
                            label: (typeof __ === 'function') ? __('employee_id') : 'Employee ID',
                            default: true
                        },
                        {
                            id: 'emp_name',
                            label: (typeof __ === 'function') ? __('employee_name') : 'Employee Name',
                            default: true
                        },
                        {
                            id: 'dept',
                            label: (typeof __ === 'function') ? __('department') : 'Department',
                            default: true
                        },
                        {
                            id: 'date',
                            label: (typeof __ === 'function') ? __('date') : 'Date',
                            default: true
                        },
                        {
                            id: 'check_in',
                            label: (typeof __ === 'function') ? __('check_in') : 'Check In',
                            default: true
                        },
                        {
                            id: 'check_out',
                            label: (typeof __ === 'function') ? __('check_out') : 'Check Out',
                            default: true
                        },
                        {
                            id: 'hours',
                            label: (typeof __ === 'function') ? __('hours') : 'Hours',
                            default: true
                        },
                        {
                            id: 'status',
                            label: (typeof __ === 'function') ? __('status') : 'Status',
                            default: true
                        }
                    ],
                    document: [{
                            id: 'emp_id',
                            label: (typeof __ === 'function') ? __('employee_id') : 'Employee ID',
                            default: true
                        },
                        {
                            id: 'emp_name',
                            label: (typeof __ === 'function') ? __('employee_name') : 'Employee Name',
                            default: true
                        },
                        {
                            id: 'dept',
                            label: (typeof __ === 'function') ? __('department') : 'Department',
                            default: true
                        },
                        {
                            id: 'document_type',
                            label: (typeof __ === 'function') ? __('document_type') : 'Document Type',
                            default: true
                        },
                        {
                            id: 'document_name',
                            label: (typeof __ === 'function') ? __('document_name') : 'Document Name',
                            default: true
                        },
                        {
                            id: 'upload_date',
                            label: (typeof __ === 'function') ? __('upload_date') : 'Upload Date',
                            default: true
                        },
                        {
                            id: 'status',
                            label: (typeof __ === 'function') ? __('status') : 'Status',
                            default: true
                        }
                    ],
                    evaluation: [{
                            id: 'emp_id',
                            label: (typeof __ === 'function') ? __('employee_id') : 'Employee ID',
                            default: true
                        },
                        {
                            id: 'emp_name',
                            label: (typeof __ === 'function') ? __('employee_name') : 'Employee Name',
                            default: true
                        },
                        {
                            id: 'dept',
                            label: (typeof __ === 'function') ? __('department') : 'Department',
                            default: true
                        },
                        {
                            id: 'evaluation_date',
                            label: (typeof __ === 'function') ? __('evaluation_date') : 'Evaluation Date',
                            default: true
                        },
                        {
                            id: 'score',
                            label: (typeof __ === 'function') ? __('score') : 'Score',
                            default: true
                        },
                        {
                            id: 'rating',
                            label: (typeof __ === 'function') ? __('rating') : 'Rating',
                            default: true
                        },
                        {
                            id: 'evaluator',
                            label: (typeof __ === 'function') ? __('evaluator') : 'Evaluator',
                            default: false
                        },
                        {
                            id: 'acknowledgment_status',
                            label: (typeof __ === 'function') ? __('acknowledgment_status') : 'Acknowledgment Status',
                            default: true
                        },
                        {
                            id: 'objection_note',
                            label: (typeof __ === 'function') ? __('objection_note') : 'Objection Note',
                            default: true
                        }
                    ],
                    resignation: [{
                            id: 'emp_id',
                            label: (typeof __ === 'function') ? __('employee_id') : 'Employee ID',
                            default: true
                        },
                        {
                            id: 'emp_name',
                            label: (typeof __ === 'function') ? __('employee_name') : 'Employee Name',
                            default: true
                        },
                        {
                            id: 'dept',
                            label: (typeof __ === 'function') ? __('department') : 'Department',
                            default: true
                        },
                        {
                            id: 'resignation_date',
                            label: (typeof __ === 'function') ? __('resignation_date') : 'Resignation Date',
                            default: true
                        },
                        {
                            id: 'last_working_day',
                            label: (typeof __ === 'function') ? __('last_working_day') : 'Last Working Day',
                            default: true
                        },
                        {
                            id: 'reason',
                            label: (typeof __ === 'function') ? __('reason') : 'Reason',
                            default: false
                        },
                        {
                            id: 'status',
                            label: (typeof __ === 'function') ? __('status') : 'Status',
                            default: true
                        }
                    ],
                    terminated_employees: [{
                            id: 'emp_id',
                            label: (typeof __ === 'function') ? __('employee_id') : 'Employee ID',
                            default: true
                        },
                        {
                            id: 'emp_name',
                            label: (typeof __ === 'function') ? __('employee_name') : 'Employee Name',
                            default: true
                        },
                        {
                            id: 'dept',
                            label: (typeof __ === 'function') ? __('department') : 'Department',
                            default: true
                        },
                        {
                            id: 'joining_date',
                            label: (typeof __ === 'function') ? __('joining_date') : 'Joining Date',
                            default: true
                        },
                        {
                            id: 'termination_date',
                            label: (typeof __ === 'function') ? __('termination_date') : 'Termination Date',
                            default: true
                        },
                        {
                            id: 'service_years',
                            label: (typeof __ === 'function') ? __('service_years') : 'Service Years',
                            default: true
                        },
                        {
                            id: 'eos_amount',
                            label: (typeof __ === 'function') ? __('eos_amount') : 'EOS Amount',
                            default: true
                        },
                        {
                            id: 'vacation_balance',
                            label: (typeof __ === 'function') ? __('vacation_balance') : 'Vacation Balance',
                            default: true
                        },
                        {
                            id: 'total_amount',
                            label: (typeof __ === 'function') ? __('total_amount') : 'Total Amount',
                            default: true
                        },
                        {
                            id: 'leaving_reason',
                            label: (typeof __ === 'function') ? __('leaving_reason') : 'Leaving Reason',
                            default: false
                        }
                    ],
                    eos: [{
                            id: 'emp_id',
                            label: (typeof __ === 'function') ? __('employee_id') : 'Employee ID',
                            default: true
                        },
                        {
                            id: 'emp_name',
                            label: (typeof __ === 'function') ? __('employee_name') : 'Employee Name',
                            default: true
                        },
                        {
                            id: 'dept',
                            label: (typeof __ === 'function') ? __('department') : 'Department',
                            default: true
                        },
                        {
                            id: 'joining_date',
                            label: (typeof __ === 'function') ? __('joining_date') : 'Joining Date',
                            default: true
                        },
                        {
                            id: 'termination_date',
                            label: (typeof __ === 'function') ? __('termination_date') : 'Termination Date',
                            default: true
                        },
                        {
                            id: 'service_duration',
                            label: (typeof __ === 'function') ? __('service_duration') : 'Service Duration',
                            default: true
                        },
                        {
                            id: 'basic_salary',
                            label: (typeof __ === 'function') ? __('basic_salary') : 'Basic Salary',
                            default: true
                        },
                        {
                            id: 'total_salary',
                            label: (typeof __ === 'function') ? __('total_salary') : 'Total Salary',
                            default: true
                        },
                        {
                            id: 'eos_amount',
                            label: (typeof __ === 'function') ? __('eos_amount') : 'EOS Amount',
                            default: true
                        },
                        {
                            id: 'vacation_days',
                            label: (typeof __ === 'function') ? __('vacation_days') : 'Vacation Days',
                            default: true
                        },
                        {
                            id: 'vacation_salary',
                            label: (typeof __ === 'function') ? __('vacation_salary') : 'Vacation Salary',
                            default: true
                        },
                        {
                            id: 'total_settlement',
                            label: (typeof __ === 'function') ? __('total_settlement') : 'Total Settlement',
                            default: true
                        }
                    ],
                    dept_comparison: [{
                            id: 'department',
                            label: (typeof __ === 'function') ? __('department') : 'Department',
                            default: true
                        },
                        {
                            id: 'total_employees',
                            label: (typeof __ === 'function') ? __('total_employees') : 'Total Employees',
                            default: true
                        },
                        {
                            id: 'active_employees',
                            label: (typeof __ === 'function') ? __('active_employees') : 'Active Employees',
                            default: true
                        },
                        {
                            id: 'inactive_employees',
                            label: (typeof __ === 'function') ? __('inactive_employees') : 'Inactive Employees',
                            default: false
                        },
                        {
                            id: 'total_salary',
                            label: (typeof __ === 'function') ? __('total_salary') : 'Total Salary',
                            default: true
                        },
                        {
                            id: 'avg_salary',
                            label: (typeof __ === 'function') ? __('average_salary') : 'Average Salary',
                            default: true
                        },
                        {
                            id: 'pending_vacations',
                            label: (typeof __ === 'function') ? __('pending_vacations') : 'Pending Vacations',
                            default: true
                        },
                        {
                            id: 'approved_vacations',
                            label: (typeof __ === 'function') ? __('approved_vacations') : 'Approved Vacations',
                            default: false
                        },
                        {
                            id: 'active_loans',
                            label: (typeof __ === 'function') ? __('active_loans') : 'Active Loans',
                            default: true
                        },
                        {
                            id: 'total_loan_amount',
                            label: (typeof __ === 'function') ? __('total_loan_amount') : 'Total Loan Amount',
                            default: true
                        },
                        {
                            id: 'avg_service_years',
                            label: (typeof __ === 'function') ? __('avg_service_years') : 'Avg Service Years',
                            default: false
                        }
                    ],
                    custom: []
                };

                // When report type changes, load column checkboxes
                $('#reportType').on('change', function() {
                    const reportType = $(this).val();

                    // console.log('*** REPORT TYPE CHANGED TO:', reportType);

                    // Destroy DataTable if exists BEFORE hiding/clearing
                    if ($.fn.DataTable.isDataTable('#reportTable')) {
                        try {
                            const table = $('#reportTable').DataTable();
                            table.destroy(); // Destroy DataTable but keep table structure
                            // console.log('DataTable destroyed successfully');
                        } catch (e) {
                            console.error('Error destroying DataTable:', e);
                        }
                    }

                    // Hide report table and export buttons when changing report type
                    $('#reportTableContainer').hide();
                    $('#exportExcelBtn, #exportPdfBtn').hide();

                    // Clear table content AFTER destroying DataTable
                    $('#reportTableHead').empty();
                    $('#reportTableBody').empty()

                    // Reset all filter fields
                    $('#dateFrom').val('');
                    $('#dateTo').val('');
                    $('#statusFilter').val('');
                    $('#deptFilter').val('');

                    // Clear multi-select department filters
                    $('#deptMultiFilter').val(null).trigger('change');
                    $('#customDeptMultiFilter').val(null).trigger('change');

                    // Clear column selection container and hidden select
                    $('#columnSortableContainer').empty();
                    $('#columnMultiSelect').empty();
                    $('#selectedColumnCount').text('0 selected');

                    // console.log('Cleared column container and select');

                    // Hide select by table dropdown and clear it
                    $('#selectByTableContainer').hide();
                    $('#selectByTable').empty().append('<option value="">Select by Table...</option>');

                    // Update date labels based on report type
                    if (reportType === 'eos') {
                        // For EOS report, change labels to match emp_end_of_service.php
                        $('label[for="dateFrom"]').html('<?= __('date_from') ?>');
                        $('label[for="dateTo"]').html('<?= __('last_working_day') ?> <span class="text-danger">*</span>');
                        $('#dateTo').attr('placeholder', '<?= __('select_last_working_day') ?>');
                        $('#dateFrom').closest('.col-md-4').hide(); // Hide date from for EOS
                    } else {
                        // Reset to default labels
                        $('label[for="dateFrom"]').html('<?= __('date_from') ?>');
                        $('label[for="dateTo"]').html('<?= __('date_to') ?>');
                        $('#dateTo').attr('placeholder', '<?= __('select_end_date') ?>');
                        $('#dateFrom').closest('.col-md-4').show(); // Show date from for other reports
                    }

                    // Update status and vacation-type filters based on report type
                    renderStatusFilter(reportType);
                    toggleVacationTypeFilter(reportType);

                    // Show/hide custom table selector
                    if (reportType === 'custom') {
                        $('#customTableSelection').show();
                        $('#customDeptFilter').hide(); // Hide initially until tables selected
                        $('#customDateFromFilter').hide(); // Hide initially until tables selected
                        $('#customDateToFilter').hide(); // Hide initially until tables selected
                        $('#customDateFrom').val('');
                        $('#customDateTo').val('');
                        // Hide global date filters to avoid duplicates in custom mode
                        $('#dateFrom').closest('.col-md-4, .form-group').hide();
                        $('#dateTo').closest('.col-md-4, .form-group').hide();
                        $('#multiDeptFilter').hide();
                        $('#singleDeptFilter').hide();
                        $('#columnSelectionRow').hide();
                        $('#customTables').val(null).trigger('change');
                        initCustomTablesSelect2();
                        initCustomDeptSelect2();
                    } else {
                        $('#customTableSelection').hide();
                        $('#customDeptFilter').hide();
                        $('#customDateFromFilter').hide();
                        $('#customDateToFilter').hide();
                        // Show global date filters for non-custom reports
                        // For EOS report, only show dateTo (Last Working Day)
                        if (reportType === 'eos') {
                            $('#dateFrom').closest('.col-md-4, .form-group').hide();
                            $('#dateTo').closest('.col-md-4, .form-group').show();
                        } else {
                            $('#dateFrom').closest('.col-md-4, .form-group').show();
                            $('#dateTo').closest('.col-md-4, .form-group').show();
                        }

                        // Hide column selection first
                        $('#columnSelectionRow').hide();

                        // Show/hide department filters based on report type
                        // Show multi-department filter for all report types
                        if (reportType) {
                            $('#singleDeptFilter').hide();
                            $('#multiDeptFilter').show(); // show only after report type selected
                            // For EOS report, only show dateTo (Last Working Day)
                            if (reportType === 'eos') {
                                $('#dateFrom').closest('.col-md-4, .form-group').hide();
                                $('#dateTo').closest('.col-md-4, .form-group').show();
                            } else {
                                $('#dateFrom').closest('.col-md-4, .form-group').show();
                                $('#dateTo').closest('.col-md-4, .form-group').show();
                            }
                            initDeptSelect2();
                            updateDeptSelectionCount();

                            // Only show column selection if report type has columns defined
                            if (reportColumns[reportType]) {
                                // console.log('Calling loadColumnCheckboxes with forceDefault=true');
                                loadColumnCheckboxes(reportType, true); // Force default selections
                                $('#columnSelectionRow').show();
                            }
                        } else {
                            // Keep both hidden when no report type selected
                            $('#singleDeptFilter').hide();
                            $('#multiDeptFilter').hide();
                            $('#dateFrom').closest('.col-md-4, .form-group').hide();
                            $('#dateTo').closest('.col-md-4, .form-group').hide();
                        }
                    }
                });

                // Handle custom table selection
                $('#customTables').on('change', function() {
                    const selectedTables = $(this).val();
                    if (selectedTables && selectedTables.length > 0) {
                        loadCustomTableColumns(selectedTables);
                    }
                });

                function loadCustomTableColumns(tableNames) {
                    $.ajax({
                        url: 'includes/ajaxFile/getTableColumns.php',
                        method: 'POST',
                        data: {
                            tables: JSON.stringify(tableNames)
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                // Update reportColumns.custom with fetched columns from all tables
                                reportColumns.custom = response.columns.map(col => ({
                                    id: col,
                                    label: col.split(' ').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' '),
                                    default: true
                                }));

                                // Check if any selected table has department-related columns
                                const hasDeptColumn = response.columns.some(col => {
                                    const colName = col.includes('.') ? col.split('.')[1] : col;
                                    return ['dept', 'dept_id', 'department'].includes(colName.toLowerCase());
                                });

                                // Check if any selected table has date-related columns
                                const hasDateColumn = response.columns.some(col => {
                                    const colName = col.includes('.') ? col.split('.')[1] : col;
                                    return colName.toLowerCase().includes('date') || ['created_at', 'updated_at', 'month_year', 'start_date', 'end_date', 'joining_date'].includes(colName.toLowerCase());
                                });

                                // Show/hide department filter based on column availability
                                if (hasDeptColumn) {
                                    $('#customDeptFilter').show();
                                } else {
                                    $('#customDeptFilter').hide();
                                    $('#customDeptMultiFilter').val(null).trigger('change');
                                }

                                // Show/hide date filters based on column availability
                                if (hasDateColumn) {
                                    // Show custom date filters and keep global hidden in custom mode
                                    $('#customDateFromFilter').show();
                                    $('#customDateToFilter').show();
                                    $('#dateFrom').closest('.col-md-4, .form-group').hide();
                                    $('#dateTo').closest('.col-md-4, .form-group').hide();
                                } else {
                                    // Hide custom date filters and ensure global remain hidden in custom mode
                                    $('#customDateFromFilter').hide();
                                    $('#customDateToFilter').hide();
                                    $('#customDateFrom').val('');
                                    $('#customDateTo').val('');
                                    $('#dateFrom').closest('.col-md-4, .form-group').hide();
                                    $('#dateTo').closest('.col-md-4, .form-group').hide();
                                }

                                // console.log('Department columns available:', hasDeptColumn);
                                // console.log('Date columns available:', hasDateColumn);

                                // Populate table selector dropdown for custom reports
                                if (tableNames.length > 1) {
                                    const $selectByTable = $('#selectByTable');
                                    $selectByTable.empty();
                                    $selectByTable.append('<option value="">Select by Table...</option>');

                                    // Get unique table names from columns
                                    const tables = {};
                                    response.columns.forEach(col => {
                                        if (col.includes('.')) {
                                            const tableName = col.split('.')[0];
                                            if (!tables[tableName]) {
                                                tables[tableName] = tableName;
                                            }
                                        }
                                    });

                                    // Add options for each table
                                    Object.keys(tables).forEach(table => {
                                        const displayName = table.split(' ').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
                                        $selectByTable.append(`<option value="${table}">${displayName}</option>`);
                                    });

                                    $('#selectByTableContainer').show();
                                } else {
                                    $('#selectByTableContainer').hide();
                                }

                                // Load checkboxes for custom report
                                loadColumnCheckboxes('custom');
                                $('#columnSelectionRow').show();
                            } else {
                                Swal.fire('Error', response.message || 'Failed to load columns', 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Error', 'Failed to fetch table columns', 'error');
                        }
                    });
                }

                // Update department selection count
                function updateDeptSelectionCount() {
                    const count = $('#deptMultiFilter').val() ? $('#deptMultiFilter').val().length : 0;
                    const label = $('#multiDeptFilter label');
                    if (count > 0) {
                        label.html(`${__('select_departments')} <span class="badge badge-success">${count} selected</span>`);
                    } else {
                        label.html(__('select_departments'));
                    }
                }

                // Track Select2 changes
                $('#deptMultiFilter').on('select2:select select2:unselect', function() {
                    updateDeptSelectionCount();
                });

                function loadColumnCheckboxes(reportType, forceDefault = false) {
                    // console.log('=== loadColumnCheckboxes START ===');
                    // console.log('reportType:', reportType);
                    // console.log('forceDefault:', forceDefault);
                    // console.log('reportColumns[reportType] length:', reportColumns[reportType] ? reportColumns[reportType].length : 'undefined');
                    // console.log('reportColumns[reportType]:', reportColumns[reportType]);

                    const columns = reportColumns[reportType];
                    const $container = $('#columnSortableContainer');
                    const $select = $('#columnMultiSelect');

                    // console.log('Before clear - $select.val():', $select.val());

                    // Clear existing items and select options FIRST
                    $container.empty();
                    $select.empty();

                    // Destroy Select2 if exists
                    if ($select.data('select2')) {
                        $select.select2('destroy');
                    }

                    // Get currently selected columns to preserve user selections (only if not forcing default)
                    // Since we cleared above, this will always be empty when forceDefault is true
                    const currentSelected = forceDefault ? [] : [];

                    // console.log('currentSelected:', currentSelected);

                    // Extract base column names from currently selected (remove table prefix)
                    const currentSelectedBase = currentSelected.map(col => {
                        const parts = col.split('.');
                        return parts.length > 1 ? parts[parts.length - 1] : col;
                    });

                    // console.log('currentSelectedBase:', currentSelectedBase);

                    // Track default and selected columns
                    const defaultColumns = [];
                    let htmlItems = '';

                    columns.forEach(function(col, index) {
                        const option = $('<option></option>')
                            .attr('value', col.id)
                            .text(col.label);

                        // Extract base column name for comparison
                        const colBase = col.id.split('.').pop();

                        // Determine if column should be selected
                        let isSelected = false;

                        if (forceDefault) {
                            // Force default selection when switching report types - ignore previous selections
                            if (col.default) {
                                isSelected = true;
                                defaultColumns.push(col.id);
                            }
                        } else {
                            // Preserve user selections when not forcing defaults
                            if (currentSelected.includes(col.id)) {
                                isSelected = true;
                                defaultColumns.push(col.id);
                            } else if (currentSelectedBase.includes(colBase) && reportType === 'custom') {
                                isSelected = true;
                                defaultColumns.push(col.id);
                            } else if (col.default && reportType !== 'custom') {
                                isSelected = true;
                                defaultColumns.push(col.id);
                            }
                        }

                        if (isSelected) {
                            option.attr('selected', 'selected');
                        }

                        $select.append(option);

                        // Build draggable column item
                        const checked = isSelected ? 'checked' : '';
                        // Escape column ID for use in HTML attribute
                        const safeColId = col.id.replace(/[^a-zA-Z0-9_]/g, '_');
                        htmlItems += `
                            <div class="column-item" draggable="true" data-column-id="${col.id}" data-column-index="${index}">
                                <span class="drag-handle">☰</span>
                                <input type="checkbox" id="col_${safeColId}" value="${col.id}" class="column-checkbox" ${checked}>
                                <label for="col_${safeColId}">${col.label}</label>
                            </div>
                        `;
                    });

                    // Render draggable items
                    $container.html(htmlItems);

                    // Initialize drag and drop
                    initDragAndDrop();

                    // Initialize or reinitialize Select2
                    if ($select.data('select2')) {
                        $select.select2('destroy');
                    }

                    $select.select2({
                        theme: 'bootstrap4',
                        placeholder: (typeof __ === 'function') ? __('select_columns_to_display') : 'Select columns to display',
                        allowClear: true,
                        closeOnSelect: false,
                        width: '100%'
                    });

                    // console.log('defaultColumns to set:', defaultColumns);

                    // Set selected values
                    $select.val(defaultColumns).trigger('change');

                    // console.log('After setting - $select.val():', $select.val());
                    // console.log('Column items in DOM:', $('.column-item').length);
                    // console.log('=== loadColumnCheckboxes END ===');

                    // Update column count badge
                    updateColumnCount();
                }

                // Initialize drag and drop for columns
                function initDragAndDrop() {
                    let draggedElement = null;

                    $(document).on('dragstart', '.column-item', function(e) {
                        draggedElement = this;
                        $(this).addClass('dragging');
                        e.originalEvent.dataTransfer.effectAllowed = 'move';
                        e.originalEvent.dataTransfer.setData('text/html', this.innerHTML);
                    });

                    $(document).on('dragend', '.column-item', function(e) {
                        $(this).removeClass('dragging');
                        $('.column-item').removeClass('drag-over');
                    });

                    $(document).on('dragover', '.column-item', function(e) {
                        e.preventDefault();
                        e.originalEvent.dataTransfer.dropEffect = 'move';
                        if (this !== draggedElement) {
                            $(this).addClass('drag-over');
                        }
                    });

                    $(document).on('dragleave', '.column-item', function(e) {
                        $(this).removeClass('drag-over');
                    });

                    $(document).on('drop', '.column-item', function(e) {
                        e.preventDefault();
                        if (this !== draggedElement) {
                            // Swap the elements
                            $(draggedElement).insertBefore($(this));
                            updateColumnSelectOrder();
                        }
                        $('.column-item').removeClass('drag-over');
                    });
                }

                // Update the hidden select with new column order
                function updateColumnSelectOrder() {
                    const $select = $('#columnMultiSelect');
                    const newOrder = [];

                    $('#columnSortableContainer .column-item').each(function() {
                        const columnId = $(this).data('column-id');
                        newOrder.push(columnId);
                    });

                    // Reorder the select options to match the drag order
                    newOrder.forEach(colId => {
                        const $option = $select.find(`option[value="${colId}"]`);
                        $select.append($option);
                    });
                }

                // Update selected column count badge
                function updateColumnCount() {
                    const count = $('#columnSortableContainer .column-checkbox:checked').length;
                    const total = $('#columnSortableContainer .column-checkbox').length;
                    $('#selectedColumnCount').text((typeof __ === 'function') ? (count + ' ' + __('of') + ' ' + total + ' ' + __('selected')) : (count + ' of ' + total + ' selected'));
                }

                // Handle column checkbox changes
                $(document).on('change', '.column-checkbox', function() {
                    const columnId = $(this).val();
                    const $select = $('#columnMultiSelect');
                    const $option = $select.find(`option[value="${columnId}"]`);

                    if ($(this).is(':checked')) {
                        $option.attr('selected', 'selected');
                        $(this).closest('.column-item').css('background-color', '#fff9e6');
                    } else {
                        $option.removeAttr('selected');
                        $(this).closest('.column-item').css('background-color', '#fff');
                    }

                    $select.val($select.val()).trigger('change');
                    updateColumnCount();
                });

                // Select All Columns
                $('#selectAllColumnsBtn').on('click', function() {
                    $('#columnSortableContainer .column-checkbox:not(:checked)').click();
                });

                // Deselect All Columns
                $('#deselectAllColumnsBtn').on('click', function() {
                    $('#columnSortableContainer .column-checkbox:checked').click();
                });

                // Select columns by table
                $('#selectByTable').on('change', function() {
                    const selectedTable = $(this).val();
                    if (!selectedTable) return;

                    // Find all checkboxes that belong to the selected table
                    $('#columnSortableContainer .column-checkbox').each(function() {
                        const columnId = $(this).val();
                        // Check if column starts with table name (e.g., "employees.")
                        if (columnId.startsWith(selectedTable + '.')) {
                            if (!$(this).is(':checked')) {
                                $(this).click();
                            }
                        }
                    });

                    // Reset dropdown
                    $(this).val('');
                });

                // Generate Report
                $('#generateReportBtn').on('click', function() {
                    const reportType = $('#reportType').val();
                    if (!reportType) {
                        Swal.fire({
                            icon: 'warning',
                            allowOutsideClick: false,
                            title: (typeof __ === 'function') ? __('report_type_required') : 'Report Type Required',
                            text: (typeof __ === 'function') ? __('please_select_report_type') : 'Please select a report type'
                        });
                        return;
                    }

                    // Get selected columns in their dragged order (from the draggable container)
                    const selectedColumns = [];
                    $('#columnSortableContainer .column-checkbox:checked').each(function() {
                        selectedColumns.push($(this).val());
                    });

                    if (selectedColumns.length === 0) {
                        Swal.fire({
                            icon: 'warning',
                            allowOutsideClick: false,
                            title: (typeof __ === 'function') ? __('no_columns_selected') : 'No Columns Selected',
                            text: (typeof __ === 'function') ? __('please_select_at_least_one_column') : 'Please select at least one column to display'
                        });
                        return;
                    }

                    // Get department filter value(s)
                    let departments = [];
                    if ($('#multiDeptFilter').is(':visible')) {
                        // Multi-select for all report types
                        $('#deptMultiFilter option:selected').each(function() {
                            departments.push($(this).val());
                        });
                        // If none selected, treat as all departments
                    } else {
                        // Single select for other reports
                        const singleDept = $('#deptFilter').val();
                        if (singleDept) {
                            departments = [singleDept];
                        }
                    }

                    // Prepare filter data
                    const statusValue = $('#statusFilterWrapper').is(':visible') ? $('#statusFilter').val() : '';
                    const vacationTypeValue = $('#vacationTypeFilterWrapper').is(':visible') ? $('#vacationTypeFilter').val() : '';

                    const filterData = {
                        reportType: reportType,
                        columns: selectedColumns,
                        departments: departments,
                        dateFrom: $('#dateFrom').val(),
                        dateTo: $('#dateTo').val(),
                        status: statusValue,
                        hasFullAccess: <?= $has_full_access ? 'true' : 'false' ?>,
                        userDept: '<?= $user_dept ?>'
                    };

                    if (reportType === 'vacation') {
                        filterData.vacationType = vacationTypeValue;
                    }

                    // Add custom table name if custom report
                    if (reportType === 'custom') {
                        const customTables = $('#customTables').val();
                        if (!customTables || customTables.length === 0) {
                            Swal.fire({
                                icon: 'warning',
                                title: (typeof __ === 'function') ? __('tables_required') : 'Tables Required',
                                text: (typeof __ === 'function') ? __('please_select_at_least_one_table') : 'Please select at least one table for the custom report'
                            });
                            return;
                        }

                        // Get selected departments for custom report
                        let customDepts = [];
                        $('#customDeptMultiFilter option:selected').each(function() {
                            customDepts.push($(this).val());
                        });

                        filterData.customTables = customTables;
                        filterData.customDepartments = customDepts;

                        // Get custom date filters if visible
                        if ($('#customDateFromFilter').is(':visible')) {
                            filterData.dateFrom = $('#customDateFrom').val();
                        }
                        if ($('#customDateToFilter').is(':visible')) {
                            filterData.dateTo = $('#customDateTo').val();
                        }
                    }

                    // Show loading
                    Swal.fire({
                        title: (typeof __ === 'function') ? __('generating_report') : 'Generating Report...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // AJAX request to generate report
                    $.ajax({
                        url: 'includes/ajaxFile/ajaxReports.php',
                        type: 'POST',
                        data: filterData,
                        dataType: 'json',
                        success: function(response) {
                            Swal.close();
                            // console.log('=== REPORT RESPONSE ===');
                            // console.log('Full response:', response);
                            // console.log('Success:', response.success);
                            // console.log('Data length:', response.data ? response.data.length : 0);
                            // console.log('Headers:', response.headers);
                            // console.log('First data row:', response.data && response.data.length > 0 ? response.data[0] : 'No data');
                            // console.log('======================');

                            if (response.success) {
                                console.log('=== AJAX RESPONSE RECEIVED ===');
                                console.log('Selected Columns (filterData.columns):', filterData.columns);
                                console.log('Response headers:', response.headers);
                                console.log('Response data row 0 keys:', response.data && response.data.length > 0 ? Object.keys(response.data[0]) : 'No data');
                                console.log('Response data row 0:', response.data && response.data.length > 0 ? response.data[0] : 'No data');
                                displayReport(response.data, response.headers, reportType, selectedColumns, filterData.columns);
                                $('#exportExcelBtn, #exportPdfBtn').show();
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: (typeof __ === 'function') ? __('error') : 'Error',
                                    text: response.message || ((typeof __ === 'function') ? __('failed_to_generate_report') : 'Failed to generate report')
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            Swal.close();
                            console.error('AJAX error:', error, xhr);
                            Swal.fire({
                                icon: 'error',
                                title: (typeof __ === 'function') ? __('error') : 'Error',
                                text: ((typeof __ === 'function') ? __('failed_to_generate_report') : 'Failed to generate report') + ': ' + error
                            });
                        }
                    });
                });

                function displayReport(data, headers, reportType, selectedColumnsOrder, columnIds) {
                    // Helper: translate status keys using global __()
                    function translateStatusKey(key) {
                        if (!key) return '';
                        var k = String(key).toLowerCase().replace(/\s+/g, '_');
                        // Known status keys
                        var known = ['approved', 'active', 'completed', 'rejected', 'inactive', 'cancelled', 'pending', 'pending_approval', 'awaiting', 'draft', 'paid', 'processing', 'in_progress'];
                        if (known.indexOf(k) !== -1 && typeof __ === 'function') {
                            return __(k) || key;
                        }
                        return key;
                    }
                    // console.log('displayReport called with:');
                    // console.log('- Data:', data);
                    // console.log('- Data length:', data ? data.length : 0);
                    // console.log('- Headers:', headers);
                    // console.log('- Headers length:', headers ? headers.length : 0);
                    // console.log('- Column IDs:', columnIds);
                    // console.log('- Column IDs length:', columnIds ? columnIds.length : 0);
                    // console.log('- Report type:', reportType);
                    // console.log('- Selected columns order:', selectedColumnsOrder);

                    // Add 'actions' to columnIds if this is evaluation report with Actions column
                    if (reportType === 'evaluation' && headers.length > columnIds.length && headers[headers.length - 1] === 'Actions') {
                        columnIds = columnIds.concat(['actions']);
                        // console.log('Added actions column. New columnIds length:', columnIds.length);
                    }

                    // Add 'attachment' to columnIds if this is document report with Attachment column
                    if (reportType === 'document' && headers.length > columnIds.length && headers[headers.length - 1] === 'Attachment') {
                        columnIds = columnIds.concat(['attachment']);
                        // console.log('Added attachment column. New columnIds length:', columnIds.length);
                    }

                    // Ensure headers and columnIds have same length
                    if (headers.length !== columnIds.length) {
                        console.error('MISMATCH: Headers length (' + headers.length + ') != Column IDs length (' + columnIds.length + ')');
                        console.error('Headers:', headers);
                        console.error('Column IDs:', columnIds);
                    }

                    // Build table headers (store, apply later after cleanup)
                    let headerHtml = '<tr>';
                    
                    // Add expand control header
                    headerHtml += '<th></th>';
                    
                    // Add ALL column headers (including hidden ones)
                    for (let i = 0; i < headers.length; i++) {
                        var cleanHeader = String(headers[i]);
                        headerHtml += `<th>${(typeof __ === 'function') ? __(headers[i].toLowerCase().replace(/\s+/g, '_')) || cleanHeader : cleanHeader}</th>`;
                    }
                    headerHtml += '</tr>';

                    // Build table rows (store, apply later)
                    let bodyHtml = '';

                    if (!data || data.length === 0) {
                        console.warn('No data returned for report');
                        bodyHtml = '<tr><td colspan="' + (headers.length + 1) + '" class="text-center text-muted py-4">' + ((typeof __ === 'function') ? __('no_records_found') : 'No records found') + '</td></tr>';
                    } else {
                        // console.log('Processing', data.length, 'rows...');
                        data.forEach(function(row, rowIndex) {
                            if (rowIndex < 3) { // Log first 3 rows for debugging
                                // console.log('Row', rowIndex, ':', row);
                            }
                            // Store row index as data attribute for reliable detail row retrieval
                            bodyHtml += '<tr data-row-index="' + rowIndex + '">';
                            
                            // Add blank cell for expand control
                            bodyHtml += '<td></td>';

                            // Build ALL columns in row (visible + hidden)
                            columnIds.forEach(function(columnId, colIndex) {
                                // Try to find the column in the row data
                                let cell = '';

                                // Direct key match
                                if (row.hasOwnProperty(columnId)) {
                                    cell = row[columnId];
                                    if (rowIndex < 3 && colIndex < 5) {
                                        // console.log('Direct match - columnId:', columnId, 'cell:', cell);
                                    }
                                } else {
                                    // Try with underscore replacement for prefixed columns (replace all dots)
                                    const altKey = columnId.replace(/\./g, '_');
                                    if (row.hasOwnProperty(altKey)) {
                                        cell = row[altKey];
                                        if (rowIndex < 3 && colIndex < 5) {
                                            // console.log('Alt match - columnId:', columnId, 'altKey:', altKey, 'cell:', cell);
                                        }
                                    } else {
                                        if (rowIndex < 3 && colIndex < 5) {
                                            console.error('NO MATCH - columnId:', columnId, 'altKey:', altKey, 'Available keys:', Object.keys(row).slice(0, 10));
                                        }
                                    }
                                }

                                // Apply status badge formatting for status columns
                                if (cell !== null && cell !== undefined && cell !== '') {
                                    const lowerCell = String(cell).toLowerCase();
                                    const isStatusColumn = columnId.includes('status') || columnId === 'current_status';

                                    if (isStatusColumn) {
                                        let badgeClass = 'secondary';

                                        // Special handling for acknowledgment_status
                                        if (columnId === 'acknowledgment_status') {
                                            if (lowerCell === 'acknowledged') {
                                                badgeClass = 'success';
                                            } else if (lowerCell === 'objected') {
                                                badgeClass = 'danger';
                                            } else if (lowerCell === 'pending') {
                                                badgeClass = 'warning';
                                            }
                                        } else {
                                            // Status badge colors matching system standards
                                            if (lowerCell === 'approved' || lowerCell === 'active' || lowerCell === 'completed') {
                                                badgeClass = 'success';
                                            } else if (lowerCell === 'rejected' || lowerCell === 'inactive' || lowerCell === 'cancelled') {
                                                badgeClass = 'danger';
                                            } else if (lowerCell === 'pending' || lowerCell === 'pending_approval' || lowerCell === 'awaiting') {
                                                badgeClass = 'warning';
                                            } else if (lowerCell === 'draft') {
                                                badgeClass = 'secondary';
                                            } else if (lowerCell === 'paid' || lowerCell === 'processing') {
                                                badgeClass = 'primary';
                                            } else if (lowerCell === 'in_progress' || lowerCell === 'in progress') {
                                                badgeClass = 'info';
                                            }
                                        }

                                        // Translate status text via __('key'), fallback to formatted text
                                        const translated = translateStatusKey(lowerCell);
                                        const formattedText = String(translated)
                                            
                                            .replace(/\b\w/g, char => char.toUpperCase());

                                        cell = `<span class="badge badge-${badgeClass}">${formattedText}</span>`;
                                    }
                                }

                                bodyHtml += `<td>${cell !== null && cell !== undefined ? cell : ''}</td>`;
                            });
                            bodyHtml += '</tr>';
                        });
                    }
                    // console.log('Body HTML length:', bodyHtml.length);
                    // console.log('Header columns:', headers.length, 'Body columns per row:', columnIds.slice(0, headers.length).length);

                    // (Delay applying header/body until after potential wrapper cleanup)

                    // console.log('Table body HTML set, checking DOM...');
                    // console.log('Rows in tbody:', $('#reportTableBody tr').length);

                    // CRITICAL: Verify column counts match
                    const firstRow = $('#reportTableBody tr:first');
                    const tdCount = firstRow.find('td').length;
                    const thCount = $('#reportTableHead th').length;
                    // console.log('TH count:', thCount, 'TD count in first row:', tdCount);

                    if (thCount !== tdCount && data.length > 0) {
                        console.error('COLUMN MISMATCH! TH:', thCount, 'TD:', tdCount);
                        console.error('This will cause DataTables to fail');
                        Swal.fire({
                            icon: 'error',
                            title: (typeof __ === 'function') ? __('table_structure_error') : 'Table Structure Error',
                            text: (typeof __ === 'function') ? __('column_count_mismatch') + ' ' + __('headers') + ': ' + thCount + ', ' + __('data_columns') + ': ' + tdCount : 'Column count mismatch. Headers: ' + thCount + ', Data columns: ' + tdCount
                        });
                        return;
                    }

                    // Update title with translation: reports
                    var reportWord = (typeof __ === 'function') ? __('reports') || 'Reports' : 'Reports';
                    var typeLabel = (typeof __ === 'function') ? __(reportType.toLowerCase()) || (reportType.charAt(0).toUpperCase() + reportType.slice(1)) : (reportType.charAt(0).toUpperCase() + reportType.slice(1));
                    var recordsWord = (typeof __ === 'function') ? __('records') : 'records';
                    $('#reportTitle').text(typeLabel + ' ' + reportWord + ' - ' + data.length + ' ' + recordsWord);

                    // Show/hide table container based on data
                    if (data.length > 0 && headers.length > 0) {
                        // console.log('Showing report table container');
                        $('#reportTableContainer').show();

                        // Safely destroy/reinitialize DataTable
                        if ($.fn.DataTable.isDataTable('#reportTable')) {
                            try {
                                $('#reportTable').DataTable().destroy();
                                // console.log('DataTable destroyed in displayReport');
                            } catch (e) {
                                console.error('Error destroying DataTable in displayReport:', e);
                            }
                        }
                        // Remove previous DataTables wrapper if exists
                        if ($('#reportTable_wrapper').length) {
                            $('#reportTable_wrapper').remove();
                        }
                        // Rebuild single clean table markup
                        const tableMarkup = '<table id="reportTable" class="table table-bordered table-striped dt-responsive nowrap" width="100%">' +
                            '<thead id="reportTableHead">' + headerHtml + '</thead>' +
                            '<tbody id="reportTableBody">' + bodyHtml + '</tbody>' +
                            '</table>';
                        $('#reportTableContainer').html(tableMarkup);

                        // Generate filename with report name and timestamp
                        const reportName = $('#reportType').val();
                        const now = new Date();
                        const hours = String(now.getHours()).padStart(2, '0');
                        const minutes = String(now.getMinutes()).padStart(2, '0');
                        const seconds = String(now.getSeconds()).padStart(2, '0');
                        const date = String(now.getDate()).padStart(2, '0');
                        const month = String(now.getMonth() + 1).padStart(2, '0');
                        const year = now.getFullYear();
                        const filename = `${reportName}_${hours}${minutes}${seconds}${date}${month}${year}`;

                        // console.log('Initializing DataTable on fresh table...');

                        // Use setTimeout to ensure DOM is fully rendered
                        setTimeout(function() {
                            try {
                                // Store headers and column IDs globally for detail formatting
                                window.reportHeaders = headers;
                                window.reportColumnIds = columnIds;
                                window.reportData = data;
                                window.reportTable = null;

                                // Create column definitions
                                var columnDefsArray = [];
                                
                                // Control column (expand/collapse button)
                                columnDefsArray.push({
                                    targets: 0,
                                    orderable: false,
                                    searchable: false,
                                    className: 'dt-control',
                                    width: '40px'
                                });

                                // Hide columns beyond the first 5 (visible ones are in the expanded detail view)
                                // Column indices: 0 = control, 1-5 = visible columns, 6+ = hidden
                                const hiddenColumnIndices = [];
                                for (let i = 6; i <= headers.length; i++) {
                                    hiddenColumnIndices.push(i);
                                }
                                
                                if (hiddenColumnIndices.length > 0) {
                                    columnDefsArray.push({
                                        targets: hiddenColumnIndices,
                                        visible: false,
                                        searchable: true  // Still searchable even if hidden
                                    });
                                }

                                // Format function for details - show ALL columns in COLUMN (horizontal) layout
                                function formatDetailRow(d, idx) {
                                    // Debug: log the row data structure (only for first row)
                                    if (idx === 0) {
                                        console.log('=== ROW DATA FOR DETAIL FORMATTER ===');
                                        console.log('Row data keys:', Object.keys(d));
                                        console.log('Headers:', window.reportHeaders);
                                        console.log('Column IDs:', window.reportColumnIds);
                                        console.log('Row data sample:', d);
                                    }
                                    
                                    let detailHtml = `<div class="details-content">`;
                                    detailHtml += `<div class="details-grid">`;
                                    
                                    if (window.reportHeaders && window.reportColumnIds) {
                                        window.reportHeaders.forEach(function(label, colIdx) {
                                            if (colIdx < window.reportColumnIds.length) {
                                                const colId = window.reportColumnIds[colIdx];
                                                
                                                // Get value from row data - should match keys from data object
                                                let value = d[colId] || '-';
                                                
                                                // Create column-based layout
                                                detailHtml += `
                                                    <div class="detail-column">
                                                        <div class="detail-label">${label}</div>
                                                        <div class="detail-value">${value}</div>
                                                    </div>
                                                `;
                                            }
                                        });
                                    }
                                    
                                    detailHtml += `</div></div>`;
                                    return detailHtml;
                                }

                                const table = $('#reportTable').DataTable({
                                    dom: 'Bfrtip',
                                    buttons: [
                                        'copy',
                                        {
                                            extend: 'excel',
                                            filename: filename,
                                            exportOptions: {
                                                columns: function(idx, data, node) {
                                                    // Export all columns except the last one (Actions/Attachment) for evaluation and document reports
                                                    if (reportType === 'evaluation' || reportType === 'document') {
                                                        var colCount = $('#reportTable').DataTable().columns().header().length;
                                                        return idx < colCount - 1;
                                                    }
                                                    return true;
                                                },
                                                modifier: {
                                                    page: 'all',
                                                    search: 'applied',
                                                    order: 'applied'
                                                }
                                            }
                                        },
                                        {
                                            extend: 'pdf',
                                            filename: filename,
                                            exportOptions: {
                                                columns: function(idx, data, node) {
                                                    // Export all columns except the last one (Actions/Attachment) for evaluation and document reports
                                                    if (reportType === 'evaluation' || reportType === 'document') {
                                                        var colCount = $('#reportTable').DataTable().columns().header().length;
                                                        return idx < colCount - 1;
                                                    }
                                                    return true;
                                                },
                                                modifier: {
                                                    page: 'all',
                                                    search: 'applied',
                                                    order: 'applied'
                                                }
                                            }
                                        },
                                        'print'
                                    ],
                                    responsive: false,
                                    pageLength: 50,
                                    columnDefs: columnDefsArray,
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
                                    }
                                });

                                // Handle detail row click
                                $('#reportTable').on('click', 'td.dt-control', function() {
                                    var tr = $(this).closest('tr');
                                    var row = table.row(tr);
                                    var rowIndex = parseInt(tr.data('row-index'), 10);
                                    
                                    if (row.child.isShown()) {
                                        row.child.hide();
                                        tr.removeClass('shown');
                                    } else {
                                        // Use original data from window.reportData using the row index
                                        var rowData = window.reportData && rowIndex < window.reportData.length ? window.reportData[rowIndex] : {};
                                        row.child(formatDetailRow(rowData, rowIndex)).show();
                                        tr.addClass('shown');
                                    }
                                });

                                // console.log('DataTable initialized successfully');
                            } catch (e) {
                                console.error('Error initializing DataTable:', e);
                            }
                        }, 100); // Small delay to ensure DOM is ready
                    } else {
                        $('#reportTableContainer').hide();
                        if ($.fn.DataTable.isDataTable('#reportTable')) {
                            $('#reportTable').DataTable().clear().destroy();
                        }
                    }
                }

                // Export to Excel
                $('#exportExcelBtn').on('click', function() {
                    $('#reportTable').DataTable().button('.buttons-excel').trigger();
                });

                // Export to PDF
                $('#exportPdfBtn').on('click', function() {
                    $('#reportTable').DataTable().button('.buttons-pdf').trigger();
                });

                // View Evaluation Details Handler
                $(document).on('click', '.view-evaluation-details', function() {
                    const evalId = $(this).data('eval-id');

                    // Show loading
                    Swal.fire({
                        title: __('loading'),
                        text: __('fetching_evaluation_details', 'Fetching evaluation details'),
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Fetch evaluation details
                    $.ajax({
                        url: 'includes/ajaxFile/ajaxReports.php',
                        type: 'POST',
                        data: {
                            action: 'getEvaluationDetails',
                            evalId: evalId
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                const eval = response.data;

                                // Determine badge color for total score based on rating
                                let totalScoreBadge = 'success'; // Default Excellent
                                if (eval.total_score < 60) {
                                    totalScoreBadge = 'danger'; // Needs Improvement
                                } else if (eval.total_score < 70) {
                                    totalScoreBadge = 'warning'; // Satisfactory
                                } else if (eval.total_score < 80) {
                                    totalScoreBadge = 'info'; // Good
                                } else if (eval.total_score < 90) {
                                    totalScoreBadge = 'primary'; // Very Good
                                }

                                // Function to get badge color for individual scores (out of 10)
                                function getScoreBadge(score) {
                                    if (score >= 9) return 'success'; // 90-100%
                                    if (score >= 8) return 'primary'; // 80-89%
                                    if (score >= 7) return 'info'; // 70-79%
                                    if (score >= 6) return 'warning'; // 60-69%
                                    return 'danger'; // Below 60%
                                }

                                // Build detailed HTML matching the image format
                                let detailsHtml = `
                                    <div class="evaluation-details-print" id="evaluationDetailsPrint" style="text-align: left; padding: 20px;">
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #dee2e6;">
                                            <div style="flex: 1;">
                                                <p style="margin-bottom: 10px;"><strong>${__('employee_name', 'Employee Name')}:</strong> <span class="emp-name">${eval.employee_name}</span></p>
                                                <p style="margin-bottom: 10px;"><strong>${__('employee_id', 'Employee ID')}:</strong> ${eval.employee_emp_id_display || eval.employee_emp_id}</p>
                                                <p style="margin-bottom: 10px;"><strong>${__('department', 'Department')}:</strong> <span class="dept-name">${eval.department}</span></p>
                                                <p style="margin-bottom: 10px;"><strong>${__('position', 'Position')}:</strong> <span class="emp-position">${eval.position || 'IT'}</span></p>
                                            </div>
                                            <div style="flex: 1; text-align: right;">
                                                <p style="margin-bottom: 10px;"><strong>${__('evaluated_by', 'Evaluated By')}:</strong> <span class="manager-name">${eval.manager_name}</span></p>
                                                <p style="margin-bottom: 10px;"><strong>${__('evaluation_date', 'Evaluation Date')}:</strong> ${eval.created_at ? eval.created_at.substring(0, 16).replace('T', ' ') : 'N/A'}</p>
                                                <p style="margin-bottom: 10px;"><strong>${__('total_score', 'Total Score')}:</strong> <span class="badge badge-${totalScoreBadge}" style="font-size: 14px; padding: 5px 10px;">${eval.total_score || '0'}/100</span></p>
                                            </div>
                                        </div>
                                        
                                        <h5 style="margin-top: 20px; margin-bottom: 15px; color: #333;">${__('evaluation_criteria', 'Evaluation Criteria')}</h5>
                                        <table class="table table-bordered" style="width: 100%; margin-bottom: 20px;">
                                            <thead style="background-color: #f8f9fa;">
                                                <tr>
                                                    <th style="padding: 10px; width: 70%;">${__('criteria', 'Criteria')}</th>
                                                    <th style="padding: 10px; text-align: center;">${__('score', 'Score')}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td style="padding: 10px;">${__('punctuality_attendance', 'Punctuality Attendance')}</td>
                                                    <td style="padding: 10px; text-align: center;"><span class="badge badge-${getScoreBadge(eval.punctuality || 0)}">${eval.punctuality || '0'}/10</span></td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 10px;">${__('achieving_at_the_specified_time', 'Achieving at the specified time')}</td>
                                                    <td style="padding: 10px; text-align: center;"><span class="badge badge-${getScoreBadge(eval.achieving_time || 0)}">${eval.achieving_time || '0'}/10</span></td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 10px;">${__('knowledge_of_job', 'Knowledge of job')}</td>
                                                    <td style="padding: 10px; text-align: center;"><span class="badge badge-${getScoreBadge(eval.job_knowledge || 0)}">${eval.job_knowledge || '0'}/10</span></td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 10px;">${__('the_ability_to_solve_problems', 'The Ability to solve problems')}</td>
                                                    <td style="padding: 10px; text-align: center;"><span class="badge badge-${getScoreBadge(eval.problem_solving || 0)}">${eval.problem_solving || '0'}/10</span></td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 10px;">${__('receptiveness_to_feedback_and_instructions', 'Receptiveness to Feedback and Instructions')}</td>
                                                    <td style="padding: 10px; text-align: center;"><span class="badge badge-${getScoreBadge(eval.feedback_receptiveness || 0)}">${eval.feedback_receptiveness || '0'}/10</span></td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 10px;">${__('self_professional_development', 'Self & Professional Development')}</td>
                                                    <td style="padding: 10px; text-align: center;"><span class="badge badge-${getScoreBadge(eval.self_development || 0)}">${eval.self_development || '0'}/10</span></td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 10px;">${__('work_under_pressure', 'Work under pressure')}</td>
                                                    <td style="padding: 10px; text-align: center;"><span class="badge badge-${getScoreBadge(eval.work_under_pressure || 0)}">${eval.work_under_pressure || '0'}/10</span></td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 10px;">${__('communication_skills_and_teamwork', 'Communication skills and Teamwork')}</td>
                                                    <td style="padding: 10px; text-align: center;"><span class="badge badge-${getScoreBadge(eval.communication_teamwork || 0)}">${eval.communication_teamwork || '0'}/10</span></td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 10px;">${__('creativity_and_speed_of_response', 'Creativity and speed of response')}</td>
                                                    <td style="padding: 10px; text-align: center;"><span class="badge badge-${getScoreBadge(eval.creativity_response || 0)}">${eval.creativity_response || '0'}/10</span></td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 10px;">${__('initiative_and_cooperation', 'Initiative and cooperation')}</td>
                                                    <td style="padding: 10px; text-align: center;"><span class="badge badge-${getScoreBadge(eval.initiative_cooperation || 0)}">${eval.initiative_cooperation || '0'}/10</span></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        
                                        ${eval.observation 
                                            ? `<h5 style="margin-top: 30px; margin-bottom: 15px; color: #333;">${__('observationremarks', 'Observation/Remarks')}</h5><p style="padding: 15px; background-color: #f8f9fa; border-radius: 5px; border-left: 4px solid #007bff;">${eval.observation}</p>` 
                                            : `<h5 style="margin-top: 30px; margin-bottom: 15px; color: #333;">${__('observationremarks', 'Observation/Remarks')}</h5><p style="padding: 15px; background-color: #f8f9fa; border-radius: 5px;">${__('no_observation_provided', 'No observation provided.')}</p>`}
                                        
                                        <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #dee2e6;">
                                            <h5 style="margin-bottom: 15px; color: #333;">
                                                ${eval.manager_acknowledgment_status === 'acknowledged' ? __('acknowledgment', 'Acknowledgment') : eval.manager_acknowledgment_status === 'objected' ? __('objection', 'Objection') : __('acknowledgment_status', 'Acknowledgment Status')}
                                            </h5>
                                            ${eval.manager_acknowledgment_status === 'pending' 
                                                ? `<div class="alert alert-warning" style="border-left: 4px solid #ffc107;"><i class="mdi mdi-clock-outline"></i> <strong>${__('status', 'Status')}:</strong> ${__('pending_acknowledgment', 'Pending Acknowledgment')}</div>`
                                                : eval.manager_acknowledgment_status === 'acknowledged'
                                                    ? `<div class="alert alert-success" style="border-left: 4px solid #28a745;">
                                                        <p style="margin-bottom: 5px;"><i class="mdi mdi-check-circle"></i> <strong>${__('status', 'Status')}:</strong> ${__('acknowledged', 'Acknowledged')}</p>
                                                        ${eval.acknowledged_by_name ? `<span style="margin-bottom: 5px;"><strong>${__('acknowledged_by', 'Acknowledged By')}:</strong> <span class="acknow_by_name">${eval.acknowledged_by_name}</span></span></p>` : ''}
                                                        ${eval.acknowledgment_date ? `<p style="margin-bottom: 0;"><strong>${__('date', 'Date')}:</strong> ${eval.acknowledgment_date}</p>` : ''}
                                                    </div>`
                                                    : eval.manager_acknowledgment_status === 'objected'
                                                        ? `<div class="alert alert-danger" style="border-left: 4px solid #dc3545;">
                                                            <p style="margin-bottom: 10px;"><i class="mdi mdi-close-circle"></i> <strong>${__('status', 'Status')}:</strong> ${__('objected', 'Objected')}</p>
                                                            ${eval.manager_objection_note ? `<p style="margin-bottom: 10px;"><strong>${__('objection_note', 'Objection Note')}:</strong></p><p style="padding: 10px; background-color: #fff; border-radius: 4px; white-space: pre-wrap;">${eval.manager_objection_note}</p>` : ''}
                                                            ${eval.acknowledged_by_name ? `<p style="margin-bottom: 5px;"><strong>${__('objected_by', 'Objected By')}:</strong> <span class="acknow_by_name">${eval.acknowledged_by_name}</span></p>` : ''}
                                                            ${eval.acknowledgment_date ? `<p style="margin-bottom: 0;"><strong>${__('date', 'Date')}:</strong> ${eval.acknowledgment_date}</p>` : ''}
                                                        </div>`
                                                        : `<div class="alert alert-secondary"><i class="mdi mdi-information-outline"></i> <strong>${__('status', 'Status')}:</strong> ${__('unknown', 'Unknown')}</div>`
                                            }
                                        </div>
                                    </div>
                                `;

                                Swal.fire({
                                    title: 'Evaluation Details',
                                    html: detailsHtml,
                                    width: '900px',
                                    showCloseButton: true,
                                    showCancelButton: true,
                                    confirmButtonText: `<i class="mdi mdi-printer"></i> ${__('print', 'Print')}`,
                                    confirmButtonColor: '#28a745',
                                    cancelButtonText: __('close', 'Close'),
                                    customClass: {
                                        confirmButton: 'btn btn-success',
                                        cancelButton: 'btn btn-secondary'
                                    },
                                    allowOutsideClick: false,
                                    didOpen: () => {
                                    var currentLang = getCurrentLanguage();
									// Translate employee name
									if (eval.acknowledged_by_name && currentLang === 'ar') {
										translateName(eval.acknowledged_by_name, 'en', 'ar', function(translated) {
											const empNameEl = document.querySelector('.acknow_by_name');
											if (empNameEl) empNameEl.textContent = translated;
										});
									}
									// Translate employee name
									if (eval.employee_name && currentLang === 'ar') {
										translateName(eval.employee_name, 'en', 'ar', function(translated) {
											const empNameEl = document.querySelector('.emp-name');
											if (empNameEl) empNameEl.textContent = translated;
										});
									}
									// Translate department name
									if (eval.dept_name && currentLang === 'ar') {
										translateName(eval.dept_name, 'en', 'ar', function(translated) {
											const deptNameEl = document.querySelector('.dept-name');
											if (deptNameEl) deptNameEl.textContent = translated;
										});
									}
									// Translate position
									if (eval.employee_position && currentLang === 'ar') {
										translateName(eval.employee_position, 'en', 'ar', function(translated) {
											const empPosEl = document.querySelector('.emp-position');
											if (empPosEl) empPosEl.textContent = translated;
										});
									}
									// Translate manager name
									if (eval.manager_name && currentLang === 'ar') {
										translateName(eval.manager_name, 'en', 'ar', function(translated) {
											const managerNameEl = document.querySelector('.manager-name');
											if (managerNameEl) managerNameEl.textContent = translated;
										});
									}
								}
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        // Print the evaluation details using window.print()
                                        const printWindow = window.open('', '_blank');
                                        const printContent = document.getElementById('evaluationDetailsPrint').innerHTML;
                                        printWindow.document.write('<!DOCTYPE html>' +
                                            '<html>' +
                                            '<head>' +
                                            '<title>Evaluation Details - ' + eval.employee_name + '</title>' +
                                            '<link rel="stylesheet" href="assets/css/bootstrap.min.css">' +
                                            '<style>' +
                                            'body { margin: 20px; font-family: Arial, sans-serif; }' +
                                            '.badge { display: inline-block; padding: 5px 10px; border-radius: 3px; font-weight: bold; }' +
                                            '.badge-primary { background-color: #007bff; color: white; }' +
                                            '.badge-success { background-color: #28a745; color: white; }' +
                                            'table { width: 100%; border-collapse: collapse; }' +
                                            'table, th, td { border: 1px solid #dee2e6; }' +
                                            'th, td { padding: 10px; }' +
                                            '@media print { body { margin: 15px; } .no-print { display: none; } }' +
                                            '</style>' +
                                            '</head>' +
                                            '<body>' +
                                            printContent +
                                            '</body>' +
                                            '</html>');
                                        printWindow.document.close();
                                        setTimeout(function() {
                                            printWindow.print();
                                            setTimeout(function() {
                                                printWindow.close();
                                            }, 500);
                                        }, 250);
                                    }
                                });
                            } else {
                                Swal.fire('Error', response.message || 'Failed to load evaluation details', 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Error', 'Failed to fetch evaluation details', 'error');
                        }
                    });
                });

                // Reset form
                $('#resetBtn').on('click', function() {
                    // console.log('Reset button clicked');

                    // Safely destroy DataTable FIRST before clearing anything
                    if ($.fn.DataTable.isDataTable('#reportTable')) {
                        try {
                            $('#reportTable').DataTable().destroy();
                            // console.log('DataTable destroyed during reset');
                        } catch (e) {
                            console.error('Error destroying DataTable during reset:', e);
                        }
                    }

                    // Reset report type
                    $('#reportType').val('');

                    // Reset all filter fields
                    $('#deptFilter').val('');
                    $('#dateFrom').val('');
                    $('#dateTo').val('');
                    $('#statusFilter').val('');
                    $('#vacationTypeFilter').val('');
                    renderStatusFilter('');
                    toggleVacationTypeFilter('');
                    // Show global date filters after reset
                    $('#dateFrom').closest('.col-md-4, .form-group').show();
                    $('#dateTo').closest('.col-md-4, .form-group').show();

                    // Reset custom report date filters
                    $('#customDateFrom').val('');
                    $('#customDateTo').val('');

                    // Reset multi-select filters
                    $('#deptMultiFilter').val(null).trigger('change');
                    $('#customDeptMultiFilter').val(null).trigger('change');
                    $('#customTables').val(null).trigger('change');

                    // Clear and hide column selection
                    $('#columnSortableContainer').empty();
                    $('#columnMultiSelect').empty();
                    $('#selectedColumnCount').text('0 selected');
                    $('#columnSelectionRow').hide();

                    // Hide custom report elements
                    $('#customTableSelection').hide();
                    $('#customDeptFilter').hide();
                    $('#customDateFromFilter').hide();
                    $('#customDateToFilter').hide();
                    $('#selectByTableContainer').hide();
                    $('#selectByTable').empty().append('<option value="">Select by Table...</option>');

                    // Hide report table and export buttons
                    $('#reportTableContainer').hide();
                    $('#exportExcelBtn, #exportPdfBtn').hide();

                    // Clear report table content AFTER destroying DataTable
                    $('#reportTableHead').empty();
                    $('#reportTableBody').empty();

                    // Show default department filter
                    $('#singleDeptFilter').show();
                    $('#multiDeptFilter').hide();

                    // Reset department selection count
                    updateDeptSelectionCount();

                    // console.log('Reset completed');
                });
            });
        </script>

    </body>

    </html>
<?php
} else {
    header("Location: login.php");
    exit();
}
?>
