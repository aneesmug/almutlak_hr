<?php
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/report_permissions_helper.php';
require_once __DIR__ . '/includes/evaluation_acknowledgment_handler.php';

// Reuse the exact same access rule as reports.php / ajaxReports.php
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
    'HR_Recruitment',
    'IT_Team_Manager'
];

if (!in_array($user_role, $can_see_reports_page) && !$is_system_admin) {
    header("Location: dashboard.php");
    exit();
}

$has_full_access = in_array($user_role, [
    'Administrator',
    'GM',
    'Auditor',
    'Finance_Manager',
    'HR_Manager',
    'HR_Senior_BP',
    'HR_Operations',
    'HR_Supervisor',
    'HR_Recruitment',
    'HR_Payroll'
]) || $is_system_admin;

$current_emp_id_for_reports = (string)($_SESSION['empid'] ?? ($empid ?? ''));
$allowed_report_types = get_allowed_report_types_for_user(
    $conDB,
    $current_emp_id_for_reports,
    $user_role ?? '',
    $user_type ?? '',
    !empty($is_system_admin)
);
$allowed_report_types_map = array_fill_keys($allowed_report_types, true);

$user_dept = isset($_SESSION['user_dept']) ? $_SESSION['user_dept'] : '';

// Build a Group By option list from real column ids - reused everywhere below so
// every report type offers its FULL column set (matching reports.php's own column
// picker), not just a couple of hand-picked axes.
$graph_col = function ($field, $fallback = null) {
    return ['field' => $field, 'label' => __($field, $fallback ?: ucwords(str_replace('_', ' ', $field)))];
};
$graph_cols = function (array $fields) use ($graph_col) {
    return array_map($graph_col, $fields);
};

// Report types that can be charted, backed by the same generateXReport() functions
// ajaxReports.php already exposes. Each type's status-like column (if it has a fixed,
// known value set) is pulled out into its own dedicated Status filter instead of being
// offered as a Group By axis - mirrors reports.php's own statusOptionsConfig per type.
// 'dept_comparison' / 'country_company_comparison' / 'custom' are excluded - they
// already return pre-aggregated or arbitrary rows, not the entity rows this needs.
$graph_report_types = [
    'employee' => [
        'label' => __('employee_report'),
        'columns' => $graph_cols(['name', 'emp_id', 'iqama', 'iqama_exp', 'passport_number', 'passport_exp', 'mobile', 'emg_mobile', 'emg_name', 'email', 'c_email', 'address', 'comp_no', 'dept', 'sectin_nme', 'actual_job', 'emptype', 'salary', 'joining_date', 'contract_expiry', 'ter_date', 'country', 'supervisor_id', 'vacation_days', 'fly', 'bank_name', 'iban', 'dob', 'sex', 'blood_type', 'mar_status', 'gosi', 'insurance_no', 'insurance_class', 'insurance_exp', 'emp_sup_type', 'vac_period', 't_shirt_size', 'probation', 'payment_type']),
        'tableColumns' => $graph_cols(['emp_id', 'name', 'iqama', 'comp_no', 'dept', 'actual_job', 'joining_date', 'mobile']),
        'statusField' => 'status',
        'statusOptions' => [
            ['value' => '1', 'label' => __('active')],
            ['value' => '0', 'label' => __('inactive')],
        ],
    ],
    'vacation' => [
        'label' => __('vacation_report'),
        'columns' => $graph_cols(['emp_id', 'emp_name', 'dept', 'comp_no', 'current_annual_balance', 'leave_type', 'transaction_date', 'transaction_days', 'running_balance', 'request_inv_no', 'vac_type', 'start_date', 'return_date', 'vacdays', 'fly_type', 'permit_no', 'created_at']),
        'tableColumns' => $graph_cols(['emp_id', 'emp_name', 'dept', 'vac_type', 'start_date', 'return_date', 'vacdays', 'created_at']),
        'statusField' => 'current_status',
        'statusOptions' => [
            ['value' => 'draft', 'label' => __('draft')],
            ['value' => 'pending_approval', 'label' => __('pending_approval')],
            ['value' => 'approved', 'label' => __('approved')],
            ['value' => 'rejected', 'label' => __('rejected')],
            ['value' => 'completed', 'label' => __('completed')],
        ],
    ],
    'loan' => [
        'label' => __('loan_report'),
        'columns' => $graph_cols(['emp_id', 'emp_name', 'dept', 'loan_amount', 'monthly_deduction', 'start_date', 'end_date', 'loan_type', 'final_approved_amount', 'total_payable', 'remaining_amount']),
        'tableColumns' => $graph_cols(['emp_id', 'emp_name', 'dept', 'loan_type', 'loan_amount', 'start_date', 'end_date', 'remaining_amount']),
        'statusField' => 'status',
        'statusOptions' => [
            ['value' => 'pending_level_1', 'label' => __('pending_level_1')],
            ['value' => 'pending_level_2', 'label' => __('pending_level_2')],
            ['value' => 'pending_level_3', 'label' => __('pending_level_3')],
            ['value' => 'pending_level_4', 'label' => __('pending_level_4')],
            ['value' => 'pending_level_5', 'label' => __('pending_level_5')],
            ['value' => 'pending_level_6', 'label' => __('pending_level_6')],
            ['value' => 'approved', 'label' => __('approved')],
            ['value' => 'rejected', 'label' => __('rejected')],
            ['value' => 'paid', 'label' => __('paid')],
        ],
    ],
    'salary_increment' => [
        'label' => __('salary_increment_report', 'Salary Increment Report'),
        'columns' => $graph_cols(['request_inv_no', 'emp_id', 'emp_name', 'dept', 'increment_amount', 'approved_amount', 'evaluation_score', 'reason', 'created_at']),
        'tableColumns' => $graph_cols(['request_inv_no', 'emp_id', 'emp_name', 'dept', 'increment_amount', 'approved_amount', 'created_at']),
        'statusField' => 'status',
        'statusOptions' => [
            ['value' => 'pending_approval', 'label' => __('pending_approval')],
            ['value' => 'approved', 'label' => __('approved')],
            ['value' => 'rejected', 'label' => __('rejected')],
            ['value' => 'cancelled', 'label' => __('cancelled')],
        ],
    ],
    'salary' => [
        'label' => __('salary_report'),
        'columns' => $graph_cols(['emp_id', 'emp_name', 'dept', 'basic', 'housing', 'transport', 'food', 'misc', 'fuel', 'tel', 'cashier', 'other', 'guard', 'total_salary']),
        'tableColumns' => $graph_cols(['emp_id', 'emp_name', 'dept', 'basic', 'housing', 'transport', 'total_salary']),
        'statusField' => null,
        'statusOptions' => [],
    ],
    'payroll' => [
        'label' => __('payroll_report'),
        'columns' => $graph_cols(['payroll_id', 'emp_id', 'emp_name', 'dept', 'comp_name', 'month', 'year', 'total_employees', 'total_salary', 'basic_salary', 'housing_allowance', 'transport_allowance', 'food_allowance', 'miscellaneous_allowance', 'cashier_allowance', 'fuel_allowance', 'telephone_allowance', 'other_allowance', 'guard_allowance', 'total_benefits', 'total_deductions', 'net_salary', 'created_at']),
        'tableColumns' => $graph_cols(['emp_id', 'emp_name', 'dept', 'month', 'year', 'total_salary', 'net_salary']),
        'statusField' => 'status',
        'statusOptions' => [
            ['value' => 'generated', 'label' => __('generated')],
            ['value' => 'updated', 'label' => __('updated')],
        ],
    ],
    'attendance' => [
        'label' => __('attendance_report'),
        'columns' => $graph_cols(['emp_id', 'emp_name', 'dept', 'date', 'check_in', 'check_out', 'hours', 'status']),
        'tableColumns' => $graph_cols(['emp_id', 'emp_name', 'dept', 'date', 'check_in', 'check_out', 'hours', 'status']),
        'statusField' => null,
        'statusOptions' => [],
    ],
    'document' => [
        'label' => __('document_report'),
        'columns' => $graph_cols(['emp_id', 'emp_name', 'dept', 'document_type', 'document_name', 'upload_date', 'attachment']),
        'tableColumns' => $graph_cols(['emp_id', 'emp_name', 'dept', 'document_type', 'document_name', 'upload_date']),
        'statusField' => 'status',
        'statusOptions' => [
            ['value' => 'A', 'label' => __('active')],
            ['value' => 'I', 'label' => __('inactive')],
        ],
    ],
    'evaluation' => [
        'label' => __('evaluation_report'),
        'columns' => $graph_cols(['emp_id', 'emp_name', 'dept', 'evaluation_date', 'score', 'rating', 'evaluator', 'acknowledgment_status', 'objection_note']),
        'tableColumns' => $graph_cols(['emp_id', 'emp_name', 'dept', 'evaluation_date', 'score', 'rating', 'evaluator']),
        'statusField' => null,
        'statusOptions' => [],
    ],
    'resignation' => [
        'label' => __('resignation_report'),
        'columns' => $graph_cols(['emp_id', 'emp_name', 'dept', 'resignation_date', 'last_working_day', 'reason']),
        'tableColumns' => $graph_cols(['emp_id', 'emp_name', 'dept', 'resignation_date', 'last_working_day', 'reason']),
        'statusField' => 'status',
        'statusOptions' => [
            ['value' => 'pending', 'label' => __('pending')],
            ['value' => 'approved', 'label' => __('approved')],
            ['value' => 'rejected', 'label' => __('rejected')],
            ['value' => 'cancelled', 'label' => __('cancelled')],
            ['value' => 'withdrawn', 'label' => __('withdrawn')],
        ],
    ],
    'terminated_employees' => [
        'label' => __('terminated_employees'),
        'columns' => $graph_cols(['emp_id', 'emp_name', 'dept', 'joining_date', 'termination_date', 'leaving_reason', 'service_duration', 'basic_salary', 'total_monthly_salary', 'eos_amount', 'vacation_days', 'vacation_salary', 'last_month_salary', 'gosi_deduction', 'absent_days_deduction', 'loan_deduction', 'total_deductions', 'overtime_earnings', 'net_payment', 'bank_name', 'payment_type', 'payment_status']),
        'tableColumns' => $graph_cols(['emp_id', 'emp_name', 'dept', 'joining_date', 'termination_date', 'leaving_reason', 'net_payment']),
        'statusField' => null,
        'statusOptions' => [],
    ],
    'eos' => [
        'label' => __('calculate_end_of_service'),
        'columns' => $graph_cols(['emp_id', 'emp_name', 'dept', 'joining_date', 'termination_date', 'service_duration', 'basic_salary', 'total_salary', 'eos_amount', 'vacation_days', 'vacation_salary', 'total_settlement']),
        'tableColumns' => $graph_cols(['emp_id', 'emp_name', 'dept', 'joining_date', 'termination_date', 'eos_amount', 'total_settlement']),
        'statusField' => null,
        'statusOptions' => [],
    ],
    'assets' => [
        'label' => __('assets_report') ?: 'Asset Inventory Report',
        'columns' => $graph_cols(['asset_name', 'asset_type', 'serial_number', 'asset_tag', 'purchase_date', 'assigned_to', 'assignment_date', 'return_date', 'assignment_status', 'return_notes', 'employee_dept']),
        'tableColumns' => $graph_cols(['asset_name', 'asset_type', 'serial_number', 'assigned_to', 'assignment_date', 'employee_dept']),
        'statusField' => 'asset_status',
        'statusOptions' => [
            ['value' => 'Assigned', 'label' => __('assigned')],
            ['value' => 'Returned', 'label' => __('returned')],
            ['value' => 'Lost', 'label' => __('lost')],
            ['value' => 'Damaged', 'label' => __('damaged')],
        ],
    ],
    'assets_list' => [
        'label' => __('assets_list') ?: 'Assets List',
        'columns' => $graph_cols(['asset_name', 'asset_type', 'purchase_date', 'assigned_to', 'assignment_date', 'return_date', 'employee_dept']),
        'tableColumns' => $graph_cols(['asset_name', 'asset_type', 'assigned_to', 'assignment_date', 'employee_dept']),
        'statusField' => 'asset_status',
        'statusOptions' => [
            ['value' => 'Assigned', 'label' => __('assigned')],
            ['value' => 'Returned', 'label' => __('returned')],
            ['value' => 'Lost', 'label' => __('lost')],
            ['value' => 'Damaged', 'label' => __('damaged')],
        ],
    ],
];

// Only offer types this user is actually allowed to run
$graph_report_types = array_filter($graph_report_types, function ($key) use ($allowed_report_types_map) {
    return isset($allowed_report_types_map[$key]);
}, ARRAY_FILTER_USE_KEY);

// Custom Report: same table catalog as reports.php's Custom Report builder, but here
// any selected column becomes a chartable Group By axis (fetched live via the existing
// includes/ajaxFile/getTableColumns.php + generateCustomReport() in ajaxReports.php -
// no new backend code, this just points the same engine at a chart instead of a table).
$graph_custom_tables_enabled = isset($allowed_report_types_map['custom']);
if ($graph_custom_tables_enabled) {
    $graph_table_names = [
        'employees' => __('employees'),
        'department' => __('departments'),
        'section' => __('sections'),
        'ac_jobs' => __('job_titles'),
        'countries' => __('countries'),
        'companies' => __('companies') ?: 'Companies',
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
        'payrolls' => __('payrolls'),
        'attendance' => __('attendance_records'),
        'locations' => __('locations'),
        'machines' => __('machines'),
        'cars' => __('vehicles'),
        'brands' => __('brands'),
        'activity_log' => __('activity_log'),
    ];
    if (can_acknowledge_evaluations($user_type, $user_role)) {
        $graph_table_names['emp_evaluations'] = __('employee_evaluations');
    }
}

$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='" . $username . "'");
if (mysqli_num_rows($query) == 1) {
    include("./includes/avatar_select.php");
?>
<!doctype html>
<html lang="<?= $is_rtl ? 'ar' : 'en' ?>" dir="<?= $is_rtl ? 'rtl' : 'ltr' ?>">

<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?> - <?= __('graphical_reports', 'Graphical Reports') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="Anees Afzal" name="author" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

    <link rel="shortcut icon" href="<?= get_setting($conDB, 'favicon') ?>">

    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />

    <link href="./plugins/bootstrap-datepicker/css/bootstrap-datepicker.min.css" rel="stylesheet" type="text/css" />
    <link href="./plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />

    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap4-theme@1.0.0/dist/select2-bootstrap4.min.css" rel="stylesheet" />

    <script src="assets/js/modernizr.min.js"></script>

    <style>
        .filter-section {
            background-color: #fff;
            padding: 20px;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .chart-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 24px;
            min-height: 460px;
        }

        .chart-card .chart-summary {
            display: flex;
            gap: 24px;
            flex-wrap: wrap;
            margin-bottom: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid #eef0f2;
        }

        .chart-card .chart-summary .summary-item {
            min-width: 140px;
        }

        .chart-card .chart-summary .summary-item .summary-value {
            font-size: 22px;
            font-weight: 700;
            color: #343a40;
        }

        .chart-card .chart-summary .summary-item .summary-label {
            font-size: 12.5px;
            color: #74788d;
            text-transform: uppercase;
        }

        #graphEmptyState {
            display: none;
            text-align: center;
            color: #74788d;
            padding: 60px 0;
        }

        #graphEmptyState i {
            font-size: 42px;
            margin-bottom: 12px;
            display: block;
            opacity: 0.5;
        }

        /* Slice/bar hover "pop" - same bounce-style scale + lift shadow as Dashboard's
           demographic pie charts (chart-level states.hover is turned off so this CSS
           transform is the only hover effect, matching the dashboard exactly). */
        #graphChartContainer .apexcharts-pie-area,
        #graphChartContainer .apexcharts-bar-area {
            transform-box: fill-box;
            transform-origin: center;
            transition: transform 0.35s cubic-bezier(.34, 1.61, .7, 1), filter 0.2s ease-out;
            cursor: pointer;
        }
        #graphChartContainer .apexcharts-pie-area:hover,
        #graphChartContainer .apexcharts-bar-area:hover {
            transform: scale(1.09);
            filter: drop-shadow(0 6px 10px rgba(0, 0, 0, .35));
        }
        #graphChartContainer .apexcharts-pie-series:hover .apexcharts-pie-area:not(:hover) {
            opacity: .85;
        }

        /* Select2 vertical alignment fix - the plain bootstrap4 theme CSS alone leaves
           the single-select's chosen text floating above the box (wrong padding) and its
           height not matching the other .form-control fields on the same row. */
        .select2-container {
            width: 100% !important;
        }
        .select2-container .select2-selection--single {
            height: calc(1.5em + 0.75rem + 2px) !important;
            padding: 0.375rem 0.75rem;
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
        }
        .select2-container--bootstrap4 .select2-selection--single {
            padding: 0 !important;
        }
        .select2-container .select2-selection--single .select2-selection__rendered {
            line-height: calc(1.5em + 0.75rem);
            padding-left: 0;
            padding-right: 0;
        }
        .select2-container .select2-selection--single .select2-selection__arrow {
            height: calc(1.5em + 0.75rem + 2px);
            top: 0;
            right: 6px;
        }
        .select2-container--bootstrap4 .select2-selection--multiple {
            min-height: calc(1.5em + 0.75rem + 2px);
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
            padding: 3px 6px;
        }
        .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__rendered {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            padding: 0;
            margin: 0;
        }
        .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice {
            display: flex;
            align-items: center;
            background-color: #e9ecef;
            border: 1px solid #ced4da;
            border-radius: 0.2rem;
            padding: 1px 6px;
            margin: 0;
            font-size: 0.875rem;
            line-height: 1.5;
        }
        .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice__remove {
            order: 2;
            margin-left: 6px;
            margin-right: 0;
            border: none;
            color: #6c757d;
        }
        .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice__display {
            padding-left: 0;
        }
        .select2-container--bootstrap4 .select2-selection--multiple .select2-search--inline .select2-search__field {
            margin-top: 0;
            height: 1.7rem;
        }
        .select2-container--bootstrap4.select2-container--focus .select2-selection--single,
        .select2-container--bootstrap4.select2-container--focus .select2-selection--multiple {
            border-color: #80bdff;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        <?php if ($is_rtl): ?>
        body { direction: rtl; text-align: right; }
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

    <div id="wrapper">

        <div class="left side-menu">
            <div class="slimscroll-menu" id="remove-scroll">
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
                <?php include("./includes/main_menu.php"); ?>
                <div class="clearfix"></div>
            </div>
        </div>

        <div class="content-page">
            <div class="topbar">
                <?php include './includes/topbar.php'; ?>
            </div>

            <div class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box">
                                <h4 class="page-title float-left">
                                    <i class="fa fa-chart-pie mr-2"></i><?= __('graphical_reports', 'Graphical Reports') ?>
                                </h4>
                                <div class="clearfix"></div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="filter-section">
                                <h5 class="mb-3"><?= __('report_configuration') ?></h5>
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label for="graphReportType"><?= __('report_type') ?></label>
                                        <select class="form-control" id="graphReportType">
                                            <option value=""><?= __('select_report_type') ?></option>
                                            <?php foreach ($graph_report_types as $type_key => $type_cfg): ?>
                                                <option value="<?= htmlspecialchars($type_key) ?>"><?= htmlspecialchars($type_cfg['label']) ?></option>
                                            <?php endforeach; ?>
                                            <?php if ($graph_custom_tables_enabled): ?>
                                                <option value="custom"><?= __('custom_report') ?></option>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3" id="graphCustomTablesWrapper" style="display:none;">
                                        <label for="graphCustomTables"><?= __('select_tables_multiselect') ?></label>
                                        <select class="form-control" id="graphCustomTables" multiple="multiple" style="width: 100%;">
                                            <?php if ($graph_custom_tables_enabled): foreach ($graph_table_names as $table_key => $table_label): ?>
                                                <option value="<?= htmlspecialchars($table_key) ?>"><?= htmlspecialchars($table_label) ?></option>
                                            <?php endforeach; endif; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="graphGroupBy"><?= __('group_by', 'Group By') ?></label>
                                        <select class="form-control" id="graphGroupBy" disabled>
                                            <option value=""><?= __('select_report_type') ?></option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="graphChartType"><?= __('chart_type', 'Chart Type') ?></label>
                                        <select class="form-control" id="graphChartType">
                                            <option value="pie"><?= __('pie_chart', 'Pie Chart') ?></option>
                                            <option value="donut"><?= __('donut_chart', 'Donut Chart') ?></option>
                                            <option value="bar"><?= __('bar_chart', 'Bar Chart') ?></option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="graphDeptFilter"><?= __('department') ?></label>
                                        <select class="form-control" id="graphDeptFilter">
                                            <option value=""><?= __('all_departments', 'All Departments') ?></option>
                                            <?php
                                            $dept_query_graph = mysqli_query($conDB, "SELECT DISTINCT id, dep_nme, dep_nme_ar FROM department ORDER BY dep_nme");
                                            while ($d = mysqli_fetch_assoc($dept_query_graph)) {
                                                $dLabel = ($is_rtl && !empty($d['dep_nme_ar'])) ? $d['dep_nme_ar'] : $d['dep_nme'];
                                                echo '<option value="' . htmlspecialchars($d['id']) . '">' . htmlspecialchars($dLabel) . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="row" id="graphEmployeeScopeRow">
                                    <div class="col-md-4 mb-3">
                                        <label for="graphCompanyFilter"><?= __('company', 'Company') ?></label>
                                        <select class="form-control" id="graphCompanyFilter" multiple="multiple" style="width: 100%;">
                                            <?php
                                            $company_query_graph = mysqli_query($conDB, "SELECT DISTINCT comp_id, comp_name, comp_name_ar FROM companies ORDER BY comp_name");
                                            while ($c = mysqli_fetch_assoc($company_query_graph)) {
                                                $cLabel = ($is_rtl && !empty($c['comp_name_ar'])) ? $c['comp_name_ar'] : $c['comp_name'];
                                                echo '<option value="' . htmlspecialchars($c['comp_id']) . '">' . htmlspecialchars($cLabel) . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="graphCountryFilter"><?= __('country', 'Country') ?></label>
                                        <select class="form-control" id="graphCountryFilter" multiple="multiple" style="width: 100%;">
                                            <?php
                                            $country_query_graph = mysqli_query($conDB, "SELECT DISTINCT id, name, name_ar FROM countries ORDER BY name");
                                            while ($c = mysqli_fetch_assoc($country_query_graph)) {
                                                $cLabel = ($is_rtl && !empty($c['name_ar'])) ? $c['name_ar'] : $c['name'];
                                                echo '<option value="' . htmlspecialchars($c['id']) . '">' . htmlspecialchars($cLabel) . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3" id="graphStatusWrapper" style="display:none;">
                                        <label for="graphStatusFilter"><?= __('status') ?></label>
                                        <select class="form-control" id="graphStatusFilter">
                                            <option value=""><?= __('all_status') ?></option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label for="graphDateFrom"><?= __('date_from') ?></label>
                                        <input type="text" class="form-control" id="graphDateFrom" placeholder="YYYY-MM-DD" autocomplete="off">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="graphDateTo"><?= __('date_to') ?></label>
                                        <input type="text" class="form-control" id="graphDateTo" placeholder="YYYY-MM-DD" autocomplete="off">
                                    </div>
                                    <div class="col-md-6 mb-3 d-flex align-items-end">
                                        <button type="button" id="generateGraphBtn" class="btn btn-primary" disabled>
                                            <i class="fa fa-chart-pie mr-1"></i><?= __('generate_report') ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="chart-card">
                                <div class="chart-summary" id="graphSummary" style="display:none;"></div>
                                <div id="graphChartContainer"></div>
                                <div id="graphEmptyState">
                                    <i class="fa fa-chart-pie"></i>
                                    <div><?= __('select_report_type_and_generate', 'Select a report type and group-by, then click Generate') ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row" id="graphDataTableRow" style="display:none;">
                        <div class="col-12">
                            <div class="chart-card">
                                <h5 class="mb-3">
                                    <?= __('generated_data', 'Generated Data') ?>
                                    <span id="graphDataTableFilterBadge" class="badge badge-primary ml-2" style="display:none; font-size:12.5px; font-weight:600; cursor:pointer;"></span>
                                </h5>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0" id="graphDataTable" style="width:100%;">
                                        <thead><tr></tr></thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/metisMenu.min.js"></script>
    <script src="assets/js/waves.js"></script>
    <script src="assets/js/jquery.slimscroll.js"></script>
    <script src="./plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="./plugins/datatables/jquery.dataTables.min.js"></script>
    <script src="./plugins/datatables/dataTables.bootstrap4.min.js"></script>
    <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>

    <script>
        const GRAPH_REPORT_TYPES = <?= json_encode($graph_report_types, JSON_UNESCAPED_UNICODE) ?>;
        let graphChartInstance = null;
        let graphDataTableInstance = null;
        let lastCustomColumnDefs = [];
        let clearChartFilterFn = null;

        // Same palette + radial gradient treatment as the Dashboard's demographic pie charts
        const GRAPH_PALETTE = ['#2a78d6', '#eb6834', '#1baf7a', '#eda100', '#e87ba4', '#008300', '#4a3aa7', '#e34948'];
        function graphColorsFor(count) {
            const colors = [];
            for (let i = 0; i < count; i++) {
                colors.push(GRAPH_PALETTE[i % GRAPH_PALETTE.length]);
            }
            return colors;
        }
        const GRAPH_GRADIENT_FILL = {
            type: 'gradient',
            gradient: {
                shade: 'light',
                type: 'radial',
                shadeIntensity: 0.65,
                gradientToColors: undefined,
                inverseColors: true,
                opacityFrom: 1,
                opacityTo: 1,
                stops: [0, 100]
            }
        };

        function humanizeGraphLabel(text) {
            return text.split('_').map(function(w) { return w.charAt(0).toUpperCase() + w.slice(1); }).join(' ');
        }

        $(function() {
            $('#graphDateFrom, #graphDateTo').datepicker({
                format: 'yyyy-mm-dd',
                autoclose: true,
                todayHighlight: true
            });

            $('#graphCustomTables').select2({
                theme: 'bootstrap4',
                placeholder: (typeof __ === 'function') ? __('select_one_or_more_tables') : 'Select one or more tables',
                width: '100%'
            });

            $('#graphReportType, #graphGroupBy, #graphChartType, #graphDeptFilter, #graphStatusFilter').select2({
                theme: 'bootstrap4',
                width: '100%',
                minimumResultsForSearch: 0
            });

            $('#graphCompanyFilter').select2({
                theme: 'bootstrap4',
                width: '100%',
                placeholder: (typeof __ === 'function') ? __('all_companies') : 'All Companies',
                allowClear: true
            });

            $('#graphCountryFilter').select2({
                theme: 'bootstrap4',
                width: '100%',
                placeholder: (typeof __ === 'function') ? __('all_countries', 'All Countries') : 'All Countries',
                allowClear: true
            });

            $(document).on('click', '#graphDataTableFilterBadge', function() {
                if (clearChartFilterFn) { clearChartFilterFn(); }
            });

            $('#graphReportType').on('change', function() {
                const type = $(this).val();
                const $groupBy = $('#graphGroupBy');
                $groupBy.empty();
                $('#generateGraphBtn').prop('disabled', true);

                if (type === 'custom') {
                    $('#graphCustomTablesWrapper').show();
                    // Company/Country filters only apply to the fixed employee-related
                    // types (backed by generateXReport() functions) - Custom Report's
                    // generateCustomReport() doesn't accept them.
                    $('#graphEmployeeScopeRow').hide();
                    $groupBy.append(`<option value="">${(typeof __ === 'function') ? __('select_one_or_more_tables') : 'Select one or more tables'}</option>`);
                    $groupBy.prop('disabled', true).trigger('change');
                    $('#graphCustomTables').val(null).trigger('change');
                    return;
                }

                $('#graphCustomTablesWrapper').hide();
                $('#graphCustomTables').val(null).trigger('change');
                $('#graphEmployeeScopeRow').show();

                if (!type || !GRAPH_REPORT_TYPES[type]) {
                    $groupBy.append(`<option value="">${(typeof __ === 'function') ? __('select_report_type') : 'Select report type'}</option>`);
                    $groupBy.prop('disabled', true).trigger('change');
                    $('#graphStatusWrapper').hide();
                    $('#graphStatusFilter').val('').trigger('change');
                    return;
                }

                const cfg = GRAPH_REPORT_TYPES[type];
                cfg.columns.forEach(function(opt) {
                    $groupBy.append(`<option value="${opt.field}">${opt.label}</option>`);
                });
                $groupBy.prop('disabled', false).trigger('change');
                $('#generateGraphBtn').prop('disabled', false);

                // Show this type's own fixed Status filter (values it actually has - not
                // a generic Active/Inactive) instead of offering status as a Group By axis.
                const $status = $('#graphStatusFilter');
                $status.empty().append(`<option value="">${(typeof __ === 'function') ? __('all_status') : 'All Status'}</option>`);
                if (cfg.statusField && cfg.statusOptions && cfg.statusOptions.length > 0) {
                    cfg.statusOptions.forEach(function(opt) {
                        $status.append(`<option value="${opt.value}">${opt.label}</option>`);
                    });
                    $('#graphStatusWrapper').show();
                } else {
                    $('#graphStatusWrapper').hide();
                }
                $status.trigger('change');
            });

            // Custom Report: fetch real columns for the chosen tables, any of them becomes
            // a chartable Group By axis (same includes/ajaxFile/getTableColumns.php the
            // tabular Custom Report already uses).
            $('#graphCustomTables').on('change', function() {
                const selectedTables = $(this).val() || [];
                const $groupBy = $('#graphGroupBy');
                $groupBy.empty();
                $('#generateGraphBtn').prop('disabled', true);
                $('#graphStatusWrapper').hide();
                $('#graphStatusFilter').empty().append(`<option value="">${(typeof __ === 'function') ? __('all_status') : 'All Status'}</option>`).trigger('change');

                if (selectedTables.length === 0) {
                    $groupBy.append(`<option value="">${(typeof __ === 'function') ? __('select_one_or_more_tables') : 'Select one or more tables'}</option>`);
                    $groupBy.prop('disabled', true).trigger('change');
                    return;
                }

                $.ajax({
                    url: 'includes/ajaxFile/getTableColumns.php',
                    method: 'POST',
                    data: { tables: JSON.stringify(selectedTables) },
                    dataType: 'json',
                    success: function(response) {
                        $groupBy.empty();
                        if (!response.success) {
                            $groupBy.append(`<option value="">${response.message || 'Error'}</option>`);
                            $groupBy.prop('disabled', true).trigger('change');
                            return;
                        }
                        // Employees table has a status field (Active/Inactive) - surface it as
                        // a real filter here, same as the fixed report types already offer.
                        const employeesSelected = selectedTables.includes('employees');
                        const hasEmployeesStatus = response.columns.includes('status') || response.columns.includes('employees.status');
                        if (employeesSelected && hasEmployeesStatus) {
                            const $status = $('#graphStatusFilter');
                            $status.empty().append(`<option value="">${(typeof __ === 'function') ? __('all_status') : 'All Status'}</option>`);
                            $status.append(`<option value="1">${(typeof __ === 'function') ? __('active') : 'Active'}</option>`);
                            $status.append(`<option value="0">${(typeof __ === 'function') ? __('inactive') : 'Inactive'}</option>`);
                            $('#graphStatusWrapper').show();
                            $status.trigger('change');
                        }
                        lastCustomColumnDefs = [];
                        response.columns.forEach(function(col) {
                            // 'status' has its own dedicated filter above (when it's the
                            // employees table's status) - don't also offer it as a Group By axis.
                            if (employeesSelected && hasEmployeesStatus && (col === 'status' || col === 'employees.status')) {
                                return;
                            }
                            const colPart = col.includes('.') ? col.split('.')[1] : col;
                            const tablePart = col.includes('.') ? col.split('.')[0] : '';
                            const label = tablePart ? `${humanizeGraphLabel(colPart)} (${humanizeGraphLabel(tablePart)})` : humanizeGraphLabel(colPart);
                            $groupBy.append(`<option value="${col}">${label}</option>`);
                            lastCustomColumnDefs.push({ field: col, label: label });
                        });
                        $groupBy.prop('disabled', false).trigger('change');
                        $('#generateGraphBtn').prop('disabled', false);
                    },
                    error: function() {
                        $groupBy.empty().append('<option value="">Failed to load columns</option>');
                        $groupBy.prop('disabled', true).trigger('change');
                    }
                });
            });

            $('#generateGraphBtn').on('click', function() {
                const reportType = $('#graphReportType').val();
                const groupByField = $('#graphGroupBy').val();
                const chartType = $('#graphChartType').val();

                if (!reportType || !groupByField) {
                    Swal.fire({
                        icon: 'warning',
                        title: (typeof __ === 'function') ? __('report_type_required') : 'Report Type Required',
                        text: (typeof __ === 'function') ? __('please_select_report_type') : 'Please select a report type'
                    });
                    return;
                }

                if (reportType === 'custom') {
                    const customTables = $('#graphCustomTables').val();
                    if (!customTables || customTables.length === 0) {
                        Swal.fire({
                            icon: 'warning',
                            title: (typeof __ === 'function') ? __('tables_required') : 'Tables Required',
                            text: (typeof __ === 'function') ? __('please_select_at_least_one_table') : 'Please select at least one table'
                        });
                        return;
                    }
                }

                // Beyond the chart's own Group By axis, also pull each type's "important
                // information" columns (or, for Custom, every column loaded for the chosen
                // tables) so the raw records can be listed in a real detail table below the
                // chart - not just the aggregated counts.
                let tableColumnDefs;
                if (reportType === 'custom') {
                    tableColumnDefs = lastCustomColumnDefs;
                } else {
                    const cfg = GRAPH_REPORT_TYPES[reportType];
                    tableColumnDefs = cfg.tableColumns.slice();
                    if (cfg.statusField && !tableColumnDefs.some(c => c.field === cfg.statusField)) {
                        tableColumnDefs.push({ field: cfg.statusField, label: (typeof __ === 'function') ? __('status') : 'Status' });
                    }
                }
                const requestColumns = Array.from(new Set([groupByField].concat(tableColumnDefs.map(c => c.field))));

                const deptVal = $('#graphDeptFilter').val();
                const statusVal = $('#graphStatusWrapper').is(':visible') ? $('#graphStatusFilter').val() : '';
                const companyVal = (reportType !== 'custom') ? ($('#graphCompanyFilter').val() || []) : [];
                const countryVal = (reportType !== 'custom') ? ($('#graphCountryFilter').val() || []) : [];
                const filterData = {
                    reportType: reportType,
                    columns: requestColumns,
                    departments: deptVal ? [deptVal] : [],
                    companies: companyVal,
                    countries: countryVal,
                    dateFrom: $('#graphDateFrom').val(),
                    dateTo: $('#graphDateTo').val(),
                    status: statusVal,
                    employeeId: ''
                };

                if (reportType === 'custom') {
                    filterData.customTables = $('#graphCustomTables').val();
                    filterData.customDepartments = deptVal ? [deptVal] : [];
                }

                Swal.fire({
                    title: (typeof __ === 'function') ? __('generating_report') : 'Generating Report...',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                $.ajax({
                    url: 'includes/ajaxFile/ajaxReports.php',
                    type: 'POST',
                    data: filterData,
                    dataType: 'json',
                    success: function(response) {
                        Swal.close();
                        if (!response.success) {
                            Swal.fire({
                                icon: 'error',
                                title: (typeof __ === 'function') ? __('error') : 'Error',
                                text: response.message || ((typeof __ === 'function') ? __('failed_to_generate_report') : 'Failed to generate report')
                            });
                            return;
                        }
                        // Custom report aliases prefixed columns (table.column) to table_column
                        // in the output rows - non-dotted fields (all other report types) are
                        // unaffected by this replace.
                        const resultKey = groupByField.replace(/\./g, '_');
                        const groupByLabelText = $('#graphGroupBy option:selected').text();
                        const aliasedTableColumnDefs = tableColumnDefs.map(function(c) {
                            return { field: c.field.replace(/\./g, '_'), label: c.label };
                        });
                        renderGraphChart(response.data || [], resultKey, chartType, groupByLabelText, aliasedTableColumnDefs);
                    },
                    error: function(xhr, status, error) {
                        Swal.close();
                        Swal.fire({
                            icon: 'error',
                            title: (typeof __ === 'function') ? __('request_failed') : 'Request Failed',
                            text: error
                        });
                    }
                });
            });

            // Labels currently unchecked/hidden on the chart (legend click or slice/bar
            // click toggles membership) - the table below excludes these, mirroring
            // exactly what the chart itself is showing. Reset every new chart.
            let hiddenChartLabels = new Set();

            function groupValueOf(row, groupByField) {
                let val = row[groupByField];
                if (val === null || val === undefined || String(val).trim() === '') {
                    return (typeof __ === 'function') ? __('unspecified', 'Unspecified') : 'Unspecified';
                }
                return String(val);
            }

            function renderGraphChart(rows, groupByField, chartType, groupByLabelText, tableColumnDefs) {
                hiddenChartLabels = new Set();
                const counts = {};
                rows.forEach(function(row) {
                    const val = groupValueOf(row, groupByField);
                    counts[val] = (counts[val] || 0) + 1;
                });

                const labels = Object.keys(counts);
                const series = labels.map(function(l) { return counts[l]; });
                const total = series.reduce(function(a, b) { return a + b; }, 0);

                $('#graphChartContainer').empty();

                if (graphChartInstance) {
                    graphChartInstance.destroy();
                    graphChartInstance = null;
                }

                if (graphDataTableInstance) {
                    graphDataTableInstance.destroy();
                    graphDataTableInstance = null;
                    $('#graphDataTable tbody').empty();
                }

                if (total === 0) {
                    $('#graphSummary').hide();
                    $('#graphDataTableRow').hide();
                    $('#graphDataTableFilterBadge').hide();
                    $('#graphEmptyState').show().find('div').text((typeof __ === 'function') ? __('no_data_available_in_table') : 'No data available');
                    return;
                }

                $('#graphEmptyState').hide();
                $('#graphSummary').show().html(`
                    <div class="summary-item">
                        <div class="summary-value">${total}</div>
                        <div class="summary-label">${(typeof __ === 'function') ? __('total') : 'Total'}</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-value">${labels.length}</div>
                        <div class="summary-label">${(typeof __ === 'function') ? __('categories', 'Categories') : 'Categories'}</div>
                    </div>
                `);

                // The actual underlying records behind the chart (not just the aggregated
                // counts) - each type's "important information" columns, as a searchable
                // full table below the chart. Clicking a chart label/slice re-filters this
                // table down to just that group (see renderDataTableRows below).
                const colDefs = (tableColumnDefs && tableColumnDefs.length > 0) ? tableColumnDefs : [{ field: groupByField, label: groupByLabelText || 'Group By' }];
                const esc = function(v) { return $('<div>').text(v === null || v === undefined ? '' : v).html(); };

                const $thead = $('#graphDataTable thead tr').empty();
                colDefs.forEach(function(col) {
                    $thead.append(`<th>${esc(col.label)}</th>`);
                });

                function renderDataTableRows() {
                    const visibleRows = hiddenChartLabels.size === 0 ? rows : rows.filter(function(row) {
                        return !hiddenChartLabels.has(groupValueOf(row, groupByField));
                    });

                    const $badge = $('#graphDataTableFilterBadge');
                    if (hiddenChartLabels.size === 0) {
                        $badge.hide();
                    } else {
                        const hiddenText = Array.from(hiddenChartLabels).map(esc).join(', ');
                        $badge.show().html(`${(typeof __ === 'function') ? __('hidden', 'Hidden') : 'Hidden'}: ${hiddenText} <i class="fa fa-times ml-1"></i>`);
                    }

                    if (graphDataTableInstance) {
                        graphDataTableInstance.destroy();
                        graphDataTableInstance = null;
                    }

                    const $tbody = $('#graphDataTable tbody').empty();
                    visibleRows.forEach(function(row) {
                        let tr = '<tr>';
                        colDefs.forEach(function(col) {
                            tr += `<td>${esc(row[col.field])}</td>`;
                        });
                        tr += '</tr>';
                        $tbody.append(tr);
                    });

                    graphDataTableInstance = $('#graphDataTable').DataTable({
                        paging: false,
                        searching: true,
                        info: true,
                        ordering: true,
                        language: {
                            search: `<span>${(typeof __ === 'function') ? __('search') : 'Search'}:</span> _INPUT_`,
                            searchPlaceholder: `${(typeof __ === 'function') ? __('search') : 'Search'}...`,
                            info: `${(typeof __ === 'function') ? __('showing') : 'Showing'} _START_ ${(typeof __ === 'function') ? __('to') : 'to'} _END_ ${(typeof __ === 'function') ? __('of') : 'of'} _TOTAL_ ${(typeof __ === 'function') ? __('entries') : 'entries'}`,
                            zeroRecords: (typeof __ === 'function') ? __('no_matching_records_found') : 'No matching records found'
                        }
                    });
                }

                clearChartFilterFn = function() {
                    hiddenChartLabels.forEach(function(label) {
                        const idx = labels.indexOf(label);
                        if (idx !== -1 && graphChartInstance && typeof graphChartInstance.toggleSeries === 'function') {
                            try { graphChartInstance.toggleSeries(label); } catch (e) {}
                        }
                    });
                    hiddenChartLabels = new Set();
                    renderDataTableRows();
                };

                // Mirrors what the chart itself is now showing: legend click (and clicking
                // a slice/bar) toggles that category hidden/visible - table follows suit,
                // it does NOT isolate down to just the clicked one.
                function onChartLabelClick(idx) {
                    const label = labels[idx];
                    if (label === undefined) { return; }
                    if (hiddenChartLabels.has(label)) {
                        hiddenChartLabels.delete(label);
                    } else {
                        hiddenChartLabels.add(label);
                    }
                    renderDataTableRows();
                }

                $('#graphDataTableRow').show();
                renderDataTableRows();

                let options;
                const chartColors = graphColorsFor(labels.length);
                if (chartType === 'bar') {
                    options = {
                        chart: {
                            type: 'bar', height: 380, toolbar: { show: true }, background: '#ffffff', foreColor: '#212529',
                            events: {
                                dataPointSelection: function(event, chartContext, config) {
                                    onChartLabelClick(config.dataPointIndex);
                                }
                            }
                        },
                        theme: { mode: 'light' },
                        series: [{ name: (typeof __ === 'function') ? __('count', 'Count') : 'Count', data: series }],
                        xaxis: { categories: labels },
                        colors: chartColors,
                        fill: GRAPH_GRADIENT_FILL,
                        stroke: { show: true, width: 2, colors: ['#ffffff'] },
                        plotOptions: { bar: { borderRadius: 4, distributed: true } },
                        states: {
                            hover: { filter: { type: 'none' } },
                            active: { filter: { type: 'none' } }
                        },
                        legend: { show: false },
                        dataLabels: {
                            enabled: true,
                            // Default label: % of total. Hover tooltip shows the raw count.
                            formatter: function(val) {
                                return total > 0 ? ((val / total) * 100).toFixed(1) + '%' : '0%';
                            }
                        },
                        tooltip: {
                            y: {
                                formatter: function(val) {
                                    return val;
                                }
                            }
                        }
                    };
                } else {
                    options = {
                        chart: {
                            type: chartType === 'donut' ? 'donut' : 'pie', height: 400, background: '#ffffff', foreColor: '#212529',
                            events: {
                                dataPointSelection: function(event, chartContext, config) {
                                    onChartLabelClick(config.dataPointIndex);
                                },
                                legendClick: function(chartContext, seriesIndex) {
                                    onChartLabelClick(seriesIndex);
                                }
                            }
                        },
                        theme: { mode: 'light' },
                        series: series,
                        labels: labels,
                        colors: chartColors,
                        fill: GRAPH_GRADIENT_FILL,
                        stroke: { show: true, width: 2, colors: ['#ffffff'] },
                        states: {
                            hover: { filter: { type: 'none' } },
                            active: { filter: { type: 'none' } }
                        },
                        // Donut center: shows the grand total by default, and swaps to the
                        // hovered slice's own count while hovering (native ApexCharts behavior
                        // once labels.value is configured - ignored for plain 'pie' type).
                        plotOptions: {
                            pie: {
                                donut: {
                                    size: '65%',
                                    labels: {
                                        show: true,
                                        value: {
                                            fontSize: '22px',
                                            fontWeight: 700,
                                            formatter: function(val) {
                                                return val;
                                            }
                                        },
                                        total: {
                                            show: true,
                                            label: (typeof __ === 'function') ? __('total') : 'Total',
                                            formatter: function() {
                                                return total;
                                            }
                                        }
                                    }
                                }
                            }
                        },
                        legend: { position: 'bottom' },
                        // val is already the slice's % share for pie/donut - just format it.
                        // The default tooltip on hover already shows the raw count.
                        dataLabels: {
                            enabled: true,
                            style: { fontSize: '11px' },
                            formatter: function(val) {
                                return val.toFixed(1) + '%';
                            }
                        },
                        tooltip: {
                            y: {
                                formatter: function(val) {
                                    return val;
                                }
                            }
                        }
                    };
                }

                graphChartInstance = new ApexCharts(document.getElementById('graphChartContainer'), options);
                graphChartInstance.render();
            }
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
