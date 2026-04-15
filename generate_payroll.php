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
        <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">

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
            .payroll-nav-footer {
                display: flex;
                flex-direction: column;
                align-items: stretch;
                justify-content: center;
                gap: 10px;
                width: 100%;
            }
            .payroll-nav-actions {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                padding: 8px;
                background: linear-gradient(135deg, #f8fafc 0%, #eef2f7 100%);
                border: 1px solid #d9e3e9;
                border-radius: 18px;
                box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
                width: 100%;
            }
            .payroll-nav-btn {
                min-width: 140px;
                border: 0 !important;
                border-radius: 999px !important;
                padding: 10px 18px !important;
                font-weight: 600 !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                gap: 8px;
                transition: transform 0.18s ease, box-shadow 0.18s ease, opacity 0.18s ease;
            }
            .payroll-nav-btn:hover:not(:disabled) {
                transform: translateY(-1px);
            }
            .payroll-nav-btn:disabled {
                opacity: 0.45;
                box-shadow: none !important;
                cursor: not-allowed;
            }
            .payroll-nav-btn-prev {
                background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%) !important;
                color: #1e293b !important;
                box-shadow: 0 10px 18px rgba(100, 116, 139, 0.22);
            }
            .payroll-nav-btn-next {
                background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%) !important;
                color: #ffffff !important;
                box-shadow: 0 10px 20px rgba(37, 99, 235, 0.28);
            }
            .payroll-nav-counter {
                font-size: 12px;
                font-weight: 600;
                color: #64748b;
                letter-spacing: 0.04em;
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
            .payroll-toolbar {
                border-radius: 18px;
                padding: 16px;
                background: linear-gradient(130deg, #f8fbff 0%, #eef4fb 55%, #eaf8f7 100%);
                border: 1px solid #d9e5f0;
                box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
                margin-bottom: 18px;
            }
            .payroll-toolbar-grid {
                display: grid;
                grid-template-columns: minmax(380px, 1.6fr) minmax(190px, 1fr) minmax(190px, 1fr) auto;
                gap: 12px;
                align-items: stretch;
            }
            .payroll-toolbar-field {
                display: flex;
                flex-direction: column;
                gap: 6px;
            }
            .payroll-toolbar-label {
                margin-bottom: 0;
                font-size: 14px;
                font-weight: 700;
                color: #1e293b;
            }
            .payroll-filter-input {
                min-height: 40px;
                border: 1px solid #cbdbeb;
                border-radius: 10px;
                font-weight: 700;
                background: #ffffff;
            }
            .payroll-filter-input:focus {
                border-color: #3b82f6;
                box-shadow: 0 0 0 0.12rem rgba(59, 130, 246, 0.2);
            }
            .payroll-filter-control .select2-container {
                width: 100% !important;
            }
            .payroll-filter-control .select2-container--default .select2-selection--multiple {
                min-height: 40px;
                border: 1px solid #cbdbeb;
                border-radius: 10px;
                background: #ffffff;
                padding: 2px 6px;
            }
            .payroll-filter-control .select2-container--default.select2-container--focus .select2-selection--multiple {
                border-color: #3b82f6;
                box-shadow: 0 0 0 0.12rem rgba(59, 130, 246, 0.2);
            }
            .payroll-filter-control .select2-container--default .select2-selection--multiple .select2-selection__choice {
                margin-top: 4px;
                background: #e0f2fe;
                border: 1px solid #bae6fd;
                color: #0f172a;
                border-radius: 999px;
                padding: 2px 8px;
                font-size: 12px;
            }
            .payroll-filter-card-shared {
                display: flex;
                flex-direction: column;
                gap: 8px;
                min-height: 104px;
            }
            .payroll-filter-control {
                display: grid;
                grid-template-columns: 40px 1fr;
                gap: 8px;
                align-items: center;
            }
            .payroll-filter-icon {
                height: 40px;
                border-radius: 10px;
                border: 1px solid #cbdbeb;
                background: #ffffff;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                color: #1e293b;
                font-size: 16px;
            }
            .payroll-filter-caption {
                margin: 0;
                font-size: 11px;
                font-weight: 600;
                color: #64748b;
            }
            .payroll-month-card {
                background: rgba(255, 255, 255, 0.88);
                border: 1px solid #d0e0ef;
                border-radius: 14px;
                padding: 10px;
            }
            .payroll-month-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 8px;
            }
            .payroll-month-header-actions {
                display: inline-flex;
                align-items: center;
                gap: 8px;
            }
            .payroll-month-label {
                margin-bottom: 0;
                font-size: 14px;
                font-weight: 700;
                color: #1e293b;
            }
            .payroll-month-value {
                font-size: 12px;
                font-weight: 700;
                color: #0f766e;
                background: #ccfbf1;
                border: 1px solid #99f6e4;
                border-radius: 999px;
                padding: 3px 10px;
                white-space: nowrap;
            }
            .payroll-filter-clear-btn {
                width: 28px;
                height: 28px;
                border-radius: 999px;
                border: 1px solid #fecaca;
                background: #fff1f2;
                color: #dc2626;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 0;
                transition: background-color 0.18s ease, border-color 0.18s ease, transform 0.18s ease;
            }
            .payroll-filter-clear-btn:hover {
                background: #ffe4e6;
                border-color: #fda4af;
                transform: translateY(-1px);
            }
            .payroll-filter-clear-btn.hidden {
                display: none;
            }
            .payroll-month-controls {
                display: grid;
                grid-template-columns: 44px 1fr 44px auto;
                gap: 8px;
                align-items: center;
            }
            .payroll-month-btn {
                height: 40px;
                border-radius: 10px;
                border: 1px solid #cbdbeb;
                background: #ffffff;
                color: #1e293b;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                transition: all 0.18s ease;
            }
            .payroll-month-btn:hover {
                background: #eef6ff;
                border-color: #9ec5f8;
                transform: translateY(-1px);
            }
            .payroll-month-input {
                height: 40px;
                border: 1px solid #cbdbeb;
                border-radius: 10px;
                font-weight: 700;
            }
            .payroll-month-input:focus {
                border-color: #3b82f6;
                box-shadow: 0 0 0 0.12rem rgba(59, 130, 246, 0.2);
            }
            .payroll-month-today-btn {
                height: 40px;
                padding: 0 14px;
                border-radius: 10px;
                font-weight: 700;
            }
            .payroll-toolbar-actions {
                display: flex;
                align-items: center;
                justify-content: flex-end;
                gap: 8px;
                flex-wrap: nowrap;
                align-self: stretch;
                min-height: 104px;
            }
            .payroll-toolbar-actions .btn {
                min-height: 42px;
                height: 42px;
                padding: 8px 14px;
                border-radius: 10px;
                font-weight: 700;
                font-size: 14px;
                white-space: nowrap;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }
            .payroll-toolbar-actions #generatePayrollBtn {
                background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
                border-color: #1d4ed8;
            }
            .payroll-toolbar-actions #payrollActionsToggle {
                background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
                border-color: #1d4ed8;
                color: #fff;
                min-width: 220px;
                justify-content: space-between;
                gap: 10px;
            }
            .payroll-actions-menu {
                min-width: 260px;
                border-radius: 12px;
                border: 1px solid #d9e3e9;
                box-shadow: 0 12px 28px rgba(15, 23, 42, 0.15);
            }
            .payroll-actions-menu .dropdown-item {
                padding: 9px 14px;
                font-weight: 600;
                color: #1e293b;
                position: relative;
                padding-left: 20px;
                transform: translateX(0);
                transition: background-color 0.18s ease, color 0.18s ease, transform 0.18s ease;
            }
            .payroll-actions-menu .dropdown-item::before {
                content: '';
                position: absolute;
                left: 6px;
                top: 8px;
                bottom: 8px;
                width: 4px;
                border-radius: 999px;
                background: transparent;
                opacity: 0;
                transition: opacity 0.18s ease, background-color 0.18s ease;
            }
            .payroll-actions-menu .dropdown-item i {
                width: 18px;
                margin-right: 8px;
                text-align: center;
            }
            .payroll-actions-menu .dropdown-item:hover,
            .payroll-actions-menu .dropdown-item:focus {
                background-color: #eff6ff;
                transform: translateX(6px);
                cursor: pointer;
            }
            .payroll-actions-menu .dropdown-item:hover::before,
            .payroll-actions-menu .dropdown-item:focus::before {
                opacity: 1;
            }
            .payroll-actions-menu #actionGeneratePayrollBtn {
                color: #1f2937;
            }
            .payroll-actions-menu #actionGeneratePayrollBtn:hover,
            .payroll-actions-menu #actionGeneratePayrollBtn:focus {
                color: #111827;
            }
            .payroll-actions-menu #actionGeneratePayrollBtn:hover::before,
            .payroll-actions-menu #actionGeneratePayrollBtn:focus::before {
                background-color: #111827;
            }
            .payroll-actions-menu #actionImportPayrollExcelBtn {
                color: #1d4ed8;
            }
            .payroll-actions-menu #actionImportPayrollExcelBtn:hover,
            .payroll-actions-menu #actionImportPayrollExcelBtn:focus {
                color: #1e40af;
            }
            .payroll-actions-menu #actionImportPayrollExcelBtn:hover::before,
            .payroll-actions-menu #actionImportPayrollExcelBtn:focus::before {
                background-color: #1e40af;
            }
            .payroll-actions-menu #actionRegeneratePayrollBtn {
                color: #0f766e;
            }
            .payroll-actions-menu #actionRegeneratePayrollBtn:hover,
            .payroll-actions-menu #actionRegeneratePayrollBtn:focus {
                color: #115e59;
            }
            .payroll-actions-menu #actionRegeneratePayrollBtn:hover::before,
            .payroll-actions-menu #actionRegeneratePayrollBtn:focus::before {
                background-color: #115e59;
            }
            .payroll-actions-menu #actionGenerateReportBtn {
                color: #7c3aed;
            }
            .payroll-actions-menu #actionGenerateReportBtn:hover,
            .payroll-actions-menu #actionGenerateReportBtn:focus {
                color: #6d28d9;
            }
            .payroll-actions-menu #actionGenerateReportBtn:hover::before,
            .payroll-actions-menu #actionGenerateReportBtn:focus::before {
                background-color: #6d28d9;
            }
            .payroll-actions-menu #actionToggleFeedbackFilterBtn {
                color: #d97706;
            }
            .payroll-actions-menu #actionToggleFeedbackFilterBtn:hover,
            .payroll-actions-menu #actionToggleFeedbackFilterBtn:focus {
                color: #b45309;
            }
            .payroll-actions-menu #actionToggleFeedbackFilterBtn:hover::before,
            .payroll-actions-menu #actionToggleFeedbackFilterBtn:focus::before {
                background-color: #b45309;
            }
            .payroll-actions-menu #actionStartApprovalBtn {
                color: #dc2626;
            }
            .payroll-actions-menu #actionStartApprovalBtn:hover,
            .payroll-actions-menu #actionStartApprovalBtn:focus {
                color: #b91c1c;
            }
            .payroll-actions-menu #actionStartApprovalBtn:hover::before,
            .payroll-actions-menu #actionStartApprovalBtn:focus::before {
                background-color: #b91c1c;
            }
            .payroll-import-review-summary {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                flex-wrap: wrap;
                margin-bottom: 14px;
                padding: 12px 14px;
                border: 1px solid #dbeafe;
                border-radius: 12px;
                background: #f8fbff;
                color: #334155;
                font-size: 13px;
            }
            .payroll-import-review-table-wrap {
                max-height: 60vh;
                overflow: auto;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
            }
            .payroll-import-review-table {
                width: 100%;
                min-width: 1180px;
                margin-bottom: 0;
                background: #fff;
            }
            .payroll-import-review-table thead th {
                position: sticky;
                top: 0;
                z-index: 2;
                background: #eff6ff;
                vertical-align: middle;
                white-space: nowrap;
            }
            .payroll-import-review-table td {
                vertical-align: middle;
                min-width: 120px;
            }
            .payroll-import-review-table .form-control,
            .payroll-import-review-table .custom-select {
                min-width: 120px;
            }
            .payroll-import-review-table .payroll-import-hours-input {
                min-width: 78px !important;
                width: 88px;
                max-width: 88px;
                text-align: center;
                margin: 0 auto;
            }
            .payroll-import-review-table .input-group {
                flex-wrap: nowrap;
                align-items: stretch;
            }
            .payroll-import-review-table .input-group > .form-control {
                min-width: 0;
            }
            .payroll-import-review-table .input-group-append {
                display: flex;
                flex: 0 0 auto;
            }
            .payroll-import-clear-entry-btn {
                min-width: 38px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 0 10px !important;
                min-height: 31px !important;
            }
            .payroll-import-review-cell-static {
                display: inline-flex;
                align-items: center;
                min-height: 38px;
                font-weight: 600;
                color: #1e293b;
            }
            .payroll-import-review-employee-cell {
                display: flex;
                flex-direction: column;
                gap: 2px;
                min-width: 0;
                line-height: 1.2;
            }
            .payroll-import-review-employee-id {
                font-size: 12px;
                font-weight: 700;
                color: #1e293b;
                word-break: break-word;
            }
            .payroll-import-review-employee-name {
                font-size: 11px;
                color: #64748b;
                word-break: break-word;
            }
            .payroll-import-result-summary {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
                gap: 10px;
                margin-bottom: 16px;
            }
            .payroll-import-result-stat {
                padding: 12px 14px;
                border: 1px solid #dbeafe;
                border-radius: 12px;
                background: #f8fbff;
            }
            .payroll-import-result-stat-label {
                display: block;
                margin-bottom: 4px;
                font-size: 12px;
                font-weight: 700;
                color: #64748b;
                text-transform: uppercase;
                letter-spacing: 0.03em;
            }
            .payroll-import-result-stat-value {
                font-size: 24px;
                font-weight: 800;
                color: #1e293b;
                line-height: 1;
            }
            .payroll-import-result-skipped {
                margin-top: 12px;
                padding: 14px 16px;
                border: 1px solid #fde68a;
                border-radius: 12px;
                background: #fffbeb;
                text-align: left;
            }
            .payroll-import-result-skipped-title {
                margin: 0 0 10px;
                font-size: 14px;
                font-weight: 700;
                color: #92400e;
            }
            .payroll-import-result-skipped-list {
                max-height: 260px;
                overflow-y: auto;
                margin: 0;
                padding-left: 18px;
                color: #334155;
            }
            .payroll-import-result-skipped-list li {
                margin-bottom: 8px;
                line-height: 1.45;
                word-break: break-word;
            }
            .deduction-period-input-empty {
                opacity: 0;
            }
            .deduction-period-input-empty .form-control,
            .deduction-period-input-empty .input-group-text {
                pointer-events: none;
            }
            .payroll-toolbar-actions #generateReportBtn {
                background: #f8fafc;
                border-color: #94a3b8;
                color: #334155;
            }
            @media (max-width: 1200px) {
                .payroll-toolbar-grid {
                    grid-template-columns: minmax(360px, 1fr) 1fr 1fr;
                }
                .payroll-toolbar-actions {
                    grid-column: 1 / -1;
                    justify-content: flex-start;
                    min-height: 42px;
                }
                .payroll-toolbar-actions .btn {
                    min-height: 42px;
                    height: 42px;
                }
            }
            @media (max-width: 992px) {
                .payroll-toolbar-grid {
                    grid-template-columns: 1fr;
                }
                .payroll-toolbar-actions {
                    justify-content: stretch;
                    min-height: 42px;
                }
                .payroll-toolbar-actions .btn {
                    flex: 1 1 220px;
                    min-height: 42px;
                    height: 42px;
                }
            }
            @media (max-width: 576px) {
                .payroll-toolbar {
                    padding: 12px;
                    border-radius: 14px;
                }
                .payroll-month-controls {
                    grid-template-columns: 40px 1fr 40px;
                }
                .payroll-month-today-btn {
                    grid-column: 1 / -1;
                }
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
                                <img src="<?=get_setting($conDB, 'logo')?>" alt="" height="22">
                            </span>
                            <i>
                                <img src="<?=get_setting($conDB, 'white_logo')?>" alt="" height="28">
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
                                        <div class="payroll-toolbar">
                                            <div class="payroll-toolbar-grid">
                                                <div class="payroll-month-card">
                                                    <div class="payroll-month-header">
                                                        <label for="payrollMonth" class="payroll-month-label"><?=__('select_month_label')?></label>
                                                        <span id="payrollMonthLabel" class="payroll-month-value">--</span>
                                                    </div>
                                                    <div class="payroll-month-controls">
                                                        <button type="button" id="prevPayrollMonthBtn" class="btn payroll-month-btn" title="Previous month">
                                                            <i class="mdi mdi-chevron-left"></i>
                                                        </button>
                                                        <input type="month" id="payrollMonth" class="form-control payroll-month-input">
                                                        <button type="button" id="nextPayrollMonthBtn" class="btn payroll-month-btn" title="Next month">
                                                            <i class="mdi mdi-chevron-right"></i>
                                                        </button>
                                                        <button type="button" id="currentPayrollMonthBtn" class="btn btn-outline-primary payroll-month-today-btn" title="Current month">
                                                            <?= __('today') ?: 'Current' ?>
                                                        </button>
                                                    </div>
                                                </div>

                                                <div class="payroll-toolbar-field">
                                                    <div class="payroll-month-card payroll-filter-card-shared">
                                                        <div class="payroll-month-header">
                                                            <label for="companyFilter" class="payroll-month-label"><?=__('filter_by_company_label')?></label>
                                                            <span id="companyFilterLabel" class="payroll-month-value"><?=__('all_companies_option')?></span>
                                                        </div>
                                                        <div class="payroll-filter-control">
                                                            <span class="payroll-filter-icon"><i class="fa fa-solid fa-filter-list"></i></span>
                                                            <select id="companyFilter" class="custom-select payroll-filter-input">
                                                                <option value="" selected><?=__('all_companies_option')?></option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="payroll-toolbar-field">
                                                    <div class="payroll-month-card payroll-filter-card-shared">
                                                        <div class="payroll-month-header">
                                                            <label for="payrollStatusFilter" class="payroll-month-label"><?= __('filter_by_status_label') ?></label>
                                                            <span class="payroll-month-header-actions">
                                                                <span id="statusFilterLabel" class="payroll-month-value"><?= __('all') ?: 'All' ?></span>
                                                                <button type="button" id="clearPayrollStatusFilterBtn" class="payroll-filter-clear-btn hidden" title="<?= __('clear_filter', 'Clear Filter') ?>" aria-label="<?= __('clear_filter', 'Clear Filter') ?>">
                                                                    <i class="fa fa-times"></i>
                                                                </button>
                                                            </span>
                                                        </div>
                                                        <div class="payroll-filter-control">
                                                            <span class="payroll-filter-icon"><i class="fa fa-solid fa-filter-list"></i></span>
                                                            <select id="payrollStatusFilter" class="payroll-filter-input" multiple data-placeholder="<?= __('all') ?: 'All' ?>">
                                                                <option value="generated"><?= __('generated_badge') ?: 'Generated' ?></option>
                                                                <option value="not_generated"><?= __('not_generated') ?: 'Not Generated' ?></option>
                                                                <option value="paid"><?= __('paid_badge') ?: 'Paid' ?></option>
                                                                <option value="bank"><?= __('bank_option') ?: 'Bank' ?></option>
                                                                <option value="cash"><?= __('cash_option') ?: 'Cash' ?></option>
                                                                <option value="hold"><?= __('payroll_on_hold') ?: 'On Hold' ?></option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="payroll-toolbar-actions">
                                                    <div class="dropdown">
                                                        <button id="payrollActionsToggle" class="btn dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                            <span><i class="mdi mdi-clipboard-list-outline"></i> <?= __('actions_label') ?: 'Actions' ?></span>
                                                        </button>
                                                        <div class="dropdown-menu dropdown-menu-right payroll-actions-menu" aria-labelledby="payrollActionsToggle">
                                                            <button type="button" class="dropdown-item" id="actionGeneratePayrollBtn">
                                                                <i class="fa fa-solid fa-calculator-simple"></i> <?= __('generate_payroll_for_selected_button') ?>
                                                            </button>
                                                            <button type="button" class="dropdown-item hidden" id="actionImportPayrollExcelBtn" style="display:none;">
                                                                <i class="fa fa-solid fa-file-arrow-up"></i> <?= __('upload_payroll_excel') ?: 'Upload Payroll Excel' ?>
                                                            </button>
                                                            <button type="button" class="dropdown-item hidden" id="actionRegeneratePayrollBtn" style="display:none;" title="Re-generate payroll and skip hold employees">
                                                                <i class="fa fa-solid fa-refresh"></i> <?= __('regenerate_payroll_button') ?? 'Re-generate Payroll' ?>
                                                            </button>
                                                            <button type="button" class="dropdown-item" id="actionGenerateReportBtn">
                                                                <i class="fa fa-solid fa-chart-mixed"></i> <?= __('generate_payroll_report_button') ?>
                                                            </button>
                                                            <button type="button" class="dropdown-item hidden" id="actionToggleFeedbackFilterBtn" style="display:none;">
                                                                <i class="fa fa-solid fa-comment-dots"></i>
                                                                <span class="feedback-filter-btn-label"><?= __('show_feedback_employees', 'Show Feedback Employees') ?></span>
                                                                <span class="badge badge-warning ml-2" id="feedbackFilterCountBadge" style="display:none;"></span>
                                                            </button>
                                                            <div class="dropdown-divider"></div>
                                                            <button type="button" class="dropdown-item hidden" id="actionStartApprovalBtn" style="display:none;">
                                                                <i class="fa fa-solid fa-paper-plane"></i> <?= __('start_approval', 'Start Approval') ?>
                                                            </button>
                                                        </div>
                                                    </div>
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
                                                        <th scope="col" style="width: 160px;"><?=__('salary_payment_type_label')?></th>
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

        
        <!-- SweetAlert2 -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        
        <script src="https://cdn.sheetjs.com/xlsx-0.19.3/package/dist/xlsx.full.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/exceljs@4.4.0/dist/exceljs.min.js"></script>
        <!-- jsPDF and jspdf-autotable for PDF export -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>


        <!-- App js -->
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>

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
let employeeStatusFilterRegistered = false;
let generatedPayrollMonthsCache = [];
let feedbackFilterActive = false;
let payrollImportReviewTable = null;

async function getGeneratedPayrollMonths() {
    try {
        const response = await fetch('./includes/api/get_available_months.php');
        if (!response.ok) {
            return [];
        }

        const data = await response.json();
        if (data.status === 'success' && Array.isArray(data.months)) {
            return data.months;
        }
    } catch (error) {
        console.error('Error loading generated payroll months:', error);
    }

    return [];
}

function hasGeneratedPayrollForMonth(monthValue, months = []) {
    if (!monthValue || !Array.isArray(months) || months.length === 0) {
        return false;
    }

    return months.some(month => {
        if (typeof month === 'string') {
            return month === monthValue;
        }

        return month && month.value === monthValue;
    });
}

async function updateStartApprovalButtonVisibility(monthValue = null) {
    const months = await getGeneratedPayrollMonths();
    generatedPayrollMonthsCache = months;
    const selectedMonth = monthValue || $('#payrollMonth').val() || getCurrentPayrollMonthValue();

    if (hasGeneratedPayrollForMonth(selectedMonth, months)) {
        $('#actionStartApprovalBtn').removeClass('hidden').show();
    } else {
        $('#actionStartApprovalBtn').addClass('hidden').hide();
    }
}

function getCurrentPayrollMonthValue() {
    const currentDate = window.today instanceof Date ? window.today : new Date();
    return `${getDateParts(currentDate, 'year')}-${getDateParts(currentDate, 'month')}`;
}

function formatPayrollMonthValue(monthValue) {
    if (!monthValue || !/^\d{4}-\d{2}$/.test(monthValue)) {
        return '--';
    }

    const [year, month] = monthValue.split('-');
    const parsedDate = new Date(Number(year), Number(month) - 1, 1);
    if (isNaN(parsedDate.getTime())) {
        return '--';
    }

    return parsedDate.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
}

function updatePayrollMonthLabel(monthValue) {
    const monthLabelNode = document.getElementById('payrollMonthLabel');
    if (!monthLabelNode) {
        return;
    }
    monthLabelNode.textContent = formatPayrollMonthValue(monthValue);
}

function updateCompanyFilterLabel() {
    const companyLabelNode = document.getElementById('companyFilterLabel');
    if (!companyLabelNode) {
        return;
    }

    const selectedText = $('#companyFilter option:selected').text().trim();
    companyLabelNode.textContent = selectedText || 'All Companies';
}

function updateStatusFilterLabel() {
    const statusLabelNode = document.getElementById('statusFilterLabel');
    if (!statusLabelNode) {
        return;
    }

    const selectedTexts = $('#payrollStatusFilter option:selected').map(function() {
        return $(this).text().trim();
    }).get().filter(Boolean);

    if (selectedTexts.length === 0) {
        statusLabelNode.textContent = __('all') || 'All';
        updateStatusFilterClearButton(false);
        return;
    }

    statusLabelNode.textContent = selectedTexts.length <= 2
        ? selectedTexts.join(', ')
        : `${selectedTexts.slice(0, 2).join(', ')} +${selectedTexts.length - 2}`;

    updateStatusFilterClearButton(selectedTexts.length > 0);
}

function updateStatusFilterClearButton(hasSelections) {
    const clearButton = document.getElementById('clearPayrollStatusFilterBtn');
    if (!clearButton) {
        return;
    }

    clearButton.classList.toggle('hidden', !hasSelections);
}

function clearPayrollStatusFilter() {
    const statusFilter = $('#payrollStatusFilter');
    if (!statusFilter.length) {
        return;
    }

    statusFilter.val(null).trigger('change');
}

function employeeHasOpenFeedback(employee) {
    if (!employee || typeof employee !== 'object') {
        return false;
    }

    const feedbackCount = parseInt(employee.open_feedback_count || employee.feedback_count || 0, 10);
    if (!Number.isNaN(feedbackCount) && feedbackCount > 0) {
        return true;
    }

    const feedbackFlag = employee.has_open_feedback;
    return feedbackFlag === true
        || feedbackFlag === 1
        || feedbackFlag === '1'
        || String(feedbackFlag || '').toLowerCase() === 'true'
        || String(feedbackFlag || '').toLowerCase() === 'yes';
}

function getEmployeesWithOpenFeedbackCount() {
    return Array.isArray(allEmployeesData)
        ? allEmployeesData.filter(employeeHasOpenFeedback).length
        : 0;
}

function updateFeedbackFilterButtonVisibility() {
    const feedbackBtn = $('#actionToggleFeedbackFilterBtn');
    const feedbackBadge = $('#feedbackFilterCountBadge');

    if (!feedbackBtn.length) {
        return;
    }

    const feedbackEmployeesCount = getEmployeesWithOpenFeedbackCount();

    if (feedbackEmployeesCount > 0) {
        feedbackBtn.removeClass('hidden').show();
        feedbackBadge.text(feedbackEmployeesCount).show();
    } else {
        feedbackFilterActive = false;
        feedbackBtn.addClass('hidden').hide();
        feedbackBadge.text('').hide();
    }

    feedbackBtn.find('.feedback-filter-btn-label').text(
        feedbackFilterActive
            ? (__('show_all_employees', 'Show All Employees') || 'Show All Employees')
            : (__('show_feedback_employees', 'Show Feedback Employees') || 'Show Feedback Employees')
    );

    feedbackBtn.toggleClass('active', feedbackFilterActive);
}

function setPayrollMonthValue(monthValue, triggerChange = true) {
    if (!monthValue) {
        return;
    }
    $('#payrollMonth').val(monthValue);
    updatePayrollMonthLabel(monthValue);
    if (triggerChange) {
        $('#payrollMonth').trigger('change');
    }
}

function shiftPayrollMonth(offset) {
    const currentValue = $('#payrollMonth').val() || getCurrentPayrollMonthValue();
    if (!/^\d{4}-\d{2}$/.test(currentValue)) {
        return;
    }

    const [year, month] = currentValue.split('-');
    const dateCursor = new Date(Number(year), Number(month) - 1, 1);
    dateCursor.setMonth(dateCursor.getMonth() + offset);

    const nextMonthValue = `${dateCursor.getFullYear()}-${String(dateCursor.getMonth() + 1).padStart(2, '0')}`;
    setPayrollMonthValue(nextMonthValue, true);
}

function registerEmployeeStatusFilter() {
    if (employeeStatusFilterRegistered || !$.fn.dataTable || !$.fn.dataTable.ext) {
        return;
    }

    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex, rowData) {
        if (!settings.nTable || settings.nTable.id !== 'employeeTable') {
            return true;
        }

        const selectedStatusesRaw = $('#payrollStatusFilter').val() || [];
        const selectedStatuses = Array.isArray(selectedStatusesRaw)
            ? selectedStatusesRaw.filter(Boolean)
            : [selectedStatusesRaw].filter(Boolean);

        const employee = rowData || (settings.aoData && settings.aoData[dataIndex] ? settings.aoData[dataIndex]._aData : null);
        if (!employee) {
            return true;
        }

        const paymentType = parseInt(employee.payment_type || 1, 10);
        const payrollStatus = String(employee.payroll_status || '').toLowerCase();
        const matchesStatusFilter = selectedStatuses.length === 0
            ? true
            : selectedStatuses.some(selectedStatus => {
                if (selectedStatus === 'hold') {
                    return paymentType === 3;
                }

                if (selectedStatus === 'cash') {
                    return paymentType === 2;
                }

                if (selectedStatus === 'bank') {
                    return paymentType === 1;
                }

                if (selectedStatus === 'paid') {
                    return payrollStatus === 'paid';
                }

                if (selectedStatus === 'generated') {
                    return payrollStatus === 'generated' || payrollStatus === 'paid';
                }

                if (selectedStatus === 'not_generated') {
                    return paymentType !== 3 && payrollStatus !== 'generated' && payrollStatus !== 'paid';
                }

                return false;
            });

        if (!matchesStatusFilter) {
            return false;
        }

        if (feedbackFilterActive) {
            return employeeHasOpenFeedback(employee);
        }

        return true;
    });

    employeeStatusFilterRegistered = true;
}

function initializePayrollStatusFilter() {
    const statusFilter = $('#payrollStatusFilter');
    const clearStatusFilterBtn = $('#clearPayrollStatusFilterBtn');

    if ($.fn.select2) {
        if (statusFilter.hasClass('select2-hidden-accessible')) {
            statusFilter.select2('destroy');
        }

        statusFilter.select2({
            placeholder: statusFilter.data('placeholder') || __('all') || 'All',
            allowClear: true,
            closeOnSelect: false,
            width: '100%'
        });
    }

    statusFilter.off('change').on('change', function() {
        updateStatusFilterLabel();
        if (!employeeTable) {
            return;
        }

        employeeTable.draw();
        updateMainSelectAllCheckbox();
    });

    clearStatusFilterBtn.off('click').on('click', function() {
        clearPayrollStatusFilter();
    });

    updateStatusFilterLabel();
}

function getFilteredEditableEmployees() {
    if (!employeeTable) {
        return [];
    }

    return employeeTable
        .rows({ search: 'applied', order: 'applied' })
        .data()
        .toArray()
        .filter(emp => emp && emp.payroll_status !== 'paid');
}

function getEmployeeNavigationState(empId) {
    const employees = getFilteredEditableEmployees();
    const currentIndex = employees.findIndex(emp => String(emp.emp_id) === String(empId));

    return {
        employees,
        currentIndex,
        previousEmployee: currentIndex > 0 ? employees[currentIndex - 1] : null,
        nextEmployee: currentIndex >= 0 && currentIndex < employees.length - 1 ? employees[currentIndex + 1] : null
    };
}

function collectPayrollModalData(employee) {
    const benefitsRoot = document.querySelector('#benefits-list');
    const deductionsRoot = document.querySelector('#deductions-list');

    if (!benefitsRoot || !deductionsRoot) {
        return null;
    }

    const updatedBenefits = Array.from(document.querySelectorAll('#benefits-list .benefit-row')).map(row => {
        const benefitTypeSelect = row.querySelector('.benefit-type');
        const benefitNameInput = row.querySelector('.benefit-name');
        const hoursInput = row.querySelector('.benefit-hours');
        const amountInput = row.querySelector('.benefit-amount');
        const noteInput = row.querySelector('.benefit-note');
        const benefitId = benefitTypeSelect?.dataset.benefitId || benefitNameInput?.dataset.benefitId || null;
        const benefitTypeId = benefitTypeSelect ? benefitTypeSelect.value : null;
        let benefitName = '';

        if (benefitTypeSelect) {
            benefitName = benefitTypeSelect.options[benefitTypeSelect.selectedIndex]?.text || '';
        } else if (benefitNameInput) {
            benefitName = benefitNameInput.value.trim();
        }

        return {
            id: benefitId,
            type_id: benefitTypeId,
            benefit: benefitName,
            note: noteInput ? noteInput.value.trim() : '',
            amount: parseFloat(amountInput?.value || 0),
            hours: hoursInput ? parseFloat(hoursInput.value || 0) : null
        };
    }).filter(b => b.benefit !== '' || b.amount > 0);

    const updatedDeductions = [];
    document.querySelectorAll('#deductions-list .deduction-row').forEach(row => {
        const deductionId = row.dataset.deductionId || null;
        const typeSelect = row.querySelector('.deduction-type');
        const gosiNameInput = row.querySelector('.gosi-deduction-name');

        if (gosiNameInput) {
            updatedDeductions.push({
                id: deductionId,
                calculation_type: 'fixed',
                deduction: 'GOSI',
                note: parseFloat(row.querySelector('.deduction-amount')?.value) || 0,
                hours: 0,
                days: 0,
            });
            return;
        }

        const calcType = typeSelect ? typeSelect.value : 'fixed';
        const nameInput = row.querySelector('.deduction-name');
        const amountVal = parseFloat(row.querySelector('.deduction-amount')?.value) || 0;
        let hours = 0;
        let days = 0;
        let name = '';

        if (calcType === 'hourly_deduction') {
            hours = parseFloat(row.querySelector('.deduction-hours')?.value) || 0;
            name = nameInput ? nameInput.value.trim() : __('hourly_deduction_default_name');
        } else if (calcType === 'daily_deduction') {
            days = parseFloat(row.querySelector('.deduction-days')?.value) || 0;
            name = nameInput ? nameInput.value.trim() : __('daily_deduction_default_name');
        } else {
            name = nameInput ? nameInput.value.trim() : '';
        }

        if (name || amountVal > 0) {
            updatedDeductions.push({
                id: deductionId,
                calculation_type: calcType,
                deduction: name || (calcType === 'hourly_deduction' ? __('hourly_deduction_default_name') : (calcType === 'daily_deduction' ? __('daily_deduction_default_name') : '')),
                note: amountVal,
                hours: hours,
                days: days,
            });
        }
    });

    const activePayBtn = document.querySelector('#payment-type-tabs .btn.active');
    const paymentType = activePayBtn ? parseInt(activePayBtn.dataset.paytype, 10) : Number(employee?.payment_type || 1);

    return { updatedBenefits, updatedDeductions, paymentType };
}

async function navigatePayrollEmployee(targetEmployee, currentEmployee, month, initialModalState) {
    if (!targetEmployee) {
        return;
    }

    const currentModalState = collectPayrollModalData(currentEmployee);
    const hasUnsavedChanges = currentModalState && initialModalState && JSON.stringify(currentModalState) !== JSON.stringify(initialModalState);

    if (hasUnsavedChanges) {
        const confirmation = await Swal.fire({
            icon: 'warning',
            title: __('unsaved_changes_title') || 'Unsaved changes',
            text: __('unsaved_changes_navigation_warning') || 'You have unsaved changes. Move to another employee without saving?',
            showCancelButton: true,
            confirmButtonColor: '#f59e0b',
            cancelButtonColor: '#6c757d',
            confirmButtonText: __('continue_button') || 'Continue',
            cancelButtonText: __('cancel') || 'Cancel',
            allowOutsideClick: false
        });

        if (!confirmation.isConfirmed) {
            return;
        }
    }

    Swal.close();
    showPayrollDetails(targetEmployee.emp_id, targetEmployee.name, month);
}

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
        const days = d.days || '';
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
        const periodSlotClass = isCalculated ? 'deduction-period-slot' : 'deduction-period-slot deduction-period-input-empty';
        const hoursStyle = calcType === 'hourly_deduction' ? '' : 'display: none;';
        const daysStyle = calcType === 'daily_deduction' ? '' : 'display: none;';
        const unitStyle = isCalculated ? '' : 'display: none;';
        const unitLabel = calcType === 'hourly_deduction' ? 'hrs' : 'days';
        return `
        <div class="deduction-row row mb-2 align-items-center g-3" data-deduction-id="${deductionId}">
            <div class="col-12 col-md-6">
                ${nameColumnHtml}
            </div>
            <div class="col-6 col-md-2 ${periodSlotClass}">
                <div class="input-group input-group-sm">
                    <input type="number" step="any" class="form-control form-control-sm deduction-hours" 
                           placeholder="${__('hours_placeholder')}" value="${hours}" style="${hoursStyle}">
                    <input type="number" step="any" class="form-control form-control-sm deduction-days" 
                           placeholder="${__('days_placeholder')}" value="${days}" style="${daysStyle}">
                    <span class="input-group-text bg-light deduction-period-unit" style="${unitStyle}">${unitLabel}</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-right-0 rounded-right-0"><i class="icon-saudi_riyal"></i></span>
                    <input type="text" class="form-control deduction-amount" value="${noteAmount}" 
                           placeholder="${__('amount_placeholder')}" ${isAmountReadonly ? 'readonly' : ''}>
                </div>
            </div>
            <div class="col-12 col-md-1 text-end text-md-center">
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
        
        // Check if this is a vacation-related benefit and extract description
        let benefitLabel = '';
        let isVacationBenefit = false;
        let displayName = benefitName;
        
        if (benefitName.includes('Working Days Salary for Vacation')) {
            isVacationBenefit = true;
            const match = benefitName.match(/ID:\s*(\d+)/);
            const vacationId = match ? match[1] : '';
            displayName = 'Working Days Before Vacation';
            benefitLabel = `<small class="text-muted d-block mt-1"><i class="fas fa-info-circle"></i> Salary for working days before vacation (Vac ID: ${vacationId})</small>`;
        } else if (benefitName.includes('Vacation Salary Benefit')) {
            isVacationBenefit = true;
            const match = benefitName.match(/ID:\s*(\d+)/);
            const vacationId = match ? match[1] : '';
            displayName = 'Vacation Salary';
            benefitLabel = `<small class="text-success d-block mt-1"><i class="fas fa-plane-departure"></i> Vacation salary benefit (Vac ID: ${vacationId})</small>`;
        } else if (benefitName.includes('Loan Installment')) {
            displayName = 'Loan Installment Deduction';
            benefitLabel = `<small class="text-danger d-block mt-1"><i class="fas fa-hand-holding-usd"></i> Deducted from active loan</small>`;
        }
        
        // For vacation benefits, always show as readonly text
        // For regular benefits, use dropdown if benefit types are available
        const savedHours = parseFloat(b.hours || 0);
        const savedTypeId = parseInt(b.type_id || 0, 10);

        const shouldUseBenefitSelect = !isVacationBenefit && savedTypeId > 0 && benefitTypes && benefitTypes.length > 0;

        const benefitOptionsHtml = isVacationBenefit
            ? `<input type="text" class="form-control form-control-sm benefit-name bg-light" 
                       data-benefit-id="${b.id}" value="${displayName}" readonly>`
            : (shouldUseBenefitSelect
                ? `<select class="form-control form-control-sm benefit-type" data-benefit-id="${b.id}">
                    <option>${__('select_type_option')}</option>
                    ${benefitTypes.map(type => {
                        const isSelected = parseInt(type.id, 10) === savedTypeId;
                        return `<option value="${type.id}" data-calculation="${type.calculation_type}" ${isSelected ? 'selected' : ''}>${type.name}</option>`;
                    }).join('')}
                </select>`
                : `<input type="text" class="form-control form-control-sm benefit-name ${isVacationBenefit ? 'bg-light' : ''}" 
                       data-benefit-id="${b.id}" value="${displayName || benefitName}" placeholder="${__('benefit_name_placeholder')}" ${isVacationBenefit ? 'readonly' : ''}>`
            );

        return `
            <div class="benefit-row row mb-2 align-items-center g-3">
                <div class="col-12 col-md-6">
                    ${benefitOptionsHtml}
                    ${benefitLabel}
                </div>
                <div class="col-6 col-md-2 benefit-hours-slot" data-hours="${savedHours}"></div>
                <div class="col-6 col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-right-0 rounded-right-0"><i class="icon-saudi_riyal"></i></span>
                        <input type="text" step="0.01" class="form-control benefit-amount ${isVacationBenefit ? 'bg-light' : ''}" 
                               data-benefit-id="${b.id}" value="${benefitAmount}" placeholder="${__('amount_placeholder')}" ${isVacationBenefit ? 'readonly' : ''}>
                    </div>
                </div>
                <div class="col-12 col-md-1 text-end text-md-center">
                    <button class="btn btn-sm btn-outline-danger delete-benefit-btn" data-benefit-id="${b.id}" ${isVacationBenefit ? 'disabled title="Cannot delete vacation benefits"' : ''}>
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
    const food = parseFloat(payroll.food_allowance || 0);
    const totalGrossFromPayroll = parseFloat(payroll.total_gross_salary || 0);
    const totalGrossFromComponents = basic
        + housing
        + food
        + parseFloat(payroll.transport_allowance || 0)
        + parseFloat(payroll.miscellaneous_allowance || 0)
        + parseFloat(payroll.cashier_allowance || 0)
        + parseFloat(payroll.fuel_allowance || 0)
        + parseFloat(payroll.telephone_allowance || 0)
        + parseFloat(payroll.other_allowance || 0)
        + parseFloat(payroll.guard_allowance || 0);
    if (deductionType === 'hourly_deduction' || deductionType === 'daily_deduction') {
        const totalGross = totalGrossFromPayroll > 0 ? totalGrossFromPayroll : totalGrossFromComponents;
        const deductibleSalary = Math.max(totalGross - housing - food, 0);
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
    
    // Set current month as default for month input
    const currentMonth = getCurrentPayrollMonthValue();
    setPayrollMonthValue(currentMonth, false);

    registerEmployeeStatusFilter();
    initializePayrollStatusFilter();
    initializeDataTable();

    $('#payrollMonth').on('change', function() {
        const selectedMonth = $(this).val();
        updatePayrollMonthLabel(selectedMonth);
        updateStartApprovalButtonVisibility(selectedMonth);
        fetchEmployees();
    });

    $('#prevPayrollMonthBtn').off('click').on('click', function() {
        shiftPayrollMonth(-1);
    });

    $('#nextPayrollMonthBtn').off('click').on('click', function() {
        shiftPayrollMonth(1);
    });

    $('#currentPayrollMonthBtn').off('click').on('click', function() {
        setPayrollMonthValue(getCurrentPayrollMonthValue(), true);
    });

    fetchEmployees();
    fetchBenefitTypes();
    $('#actionGenerateReportBtn').off('click').on('click', generatePayrollReport);

    const paymentQueryParams = new URLSearchParams(window.location.search);
    const paymentMonthFromUrl = (paymentQueryParams.get('payment_month') || '').trim();
    const shouldOpenPaymentReport = paymentQueryParams.get('open_payment_report') === '1';
    if (shouldOpenPaymentReport && /^\d{4}-\d{2}$/.test(paymentMonthFromUrl)) {
        setPayrollMonthValue(paymentMonthFromUrl, false);
        setTimeout(() => {
            fetchAndDisplayPayrollReport(paymentMonthFromUrl);
        }, 400);
    }
});

function initializeDataTable() {
    employeeTable = $('#employeeTable').DataTable({
        columns: [
            { 
                data: null,
                orderable: false,
                className: 'text-center',
                render: function(data, type, row) {
                    // Check if payroll is on hold (payment_type = 3)
                    const isPayrollOnHold = row.payment_type === 3 || row.payment_type === '3';
                    if (isPayrollOnHold) {
                        return `<span class="badge badge-warning" style="background-color: #ff9800;"><i class="mdi mdi-pause-circle"></i> ${__('payroll_on_hold')}</span>`;
                    }
                    
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
                { data: 'comp_name' }, // Company name
                { 
                    data: 'payment_type',
                    render: function(data) {
                        const pt = parseInt(data || 1, 10);
                        if (pt === 2) {
                            return `<span class="badge badge-info">${__('cash_option') || 'Cash'}</span>`;
                        }
                        if (pt === 3) {
                            return `<span class="badge badge-warning" style="background-color: #ff9800;">${__('hold_option') || 'Hold'}</span>`;
                        }
                        return `<span class="badge badge-primary">${__('bank_option') || 'Bank'}</span>`;
                    }
                },
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
                    const isPayrollGenerated = row.payroll_status && (row.payroll_status === 'generated');
                    
                    // Locked for paid payrolls
                    if (isPayrollPaid) {
                        return `<button class="btn btn-danger btn-sm btn-rounded" disabled style="cursor: not-allowed; opacity: 0.6;" title="${__('payroll_is_locked')}">
                                    <i class="mdi mdi-lock"></i> ${__('locked')}
                                </button>`;
                    }
                    
                    // Edit button for both pending and generated (warning color for generated)
                    const buttonColor = isPayrollGenerated ? 'btn-warning' : 'btn-dark';
                    const buttonTitle = isPayrollGenerated ? __('edit_generated_payroll_title') : __('create_edit_payroll_title');
                    return `<button class="btn ${buttonColor} btn-sm view-edit-btn" data-emp-id="${row.emp_id}" data-emp-name="${row.name}" title="${buttonTitle}">
                                <i class="mdi mdi-account-edit"></i> ${__('edit')}
                            </button>`;
                }
            }
        ],
        order: [[2, 'asc']], // Sort by Name by default
        pageLength: 10,
        lengthMenu: [[-1, 5, 10, 25, 50, 100], ['All', 5, 10, 25, 50, 100]],
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
            
            // Show/hide Re-generate button based on whether any payroll is already generated
            updateRegenerateButtonVisibility();
            updateFeedbackFilterButtonVisibility();
            employeeTable.draw();
            updateMainSelectAllCheckbox();
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
        await updateStartApprovalButtonVisibility();
    }
}

function updateRegenerateButtonVisibility() {
    // Check if any employee has a generated or paid payroll status
    const hasGeneratedPayroll = Array.isArray(allEmployeesData)
        ? allEmployeesData.some(emp => emp.payroll_status === 'generated' || emp.payroll_status === 'paid')
        : false;
    
    // Show payroll follow-up actions only when payroll already exists for the selected month.
    if (hasGeneratedPayroll) {
        $('#actionImportPayrollExcelBtn').removeClass('hidden').show();
    } else {
        $('#actionImportPayrollExcelBtn').addClass('hidden').hide();
    }

    // Show the button only if payroll exists
    if (hasGeneratedPayroll) {
        $('#actionRegeneratePayrollBtn').removeClass('hidden').show();
    } else {
        $('#actionRegeneratePayrollBtn').addClass('hidden').hide();
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
    updateCompanyFilterLabel();

    // Unbind and rebind change event for department filter
    companyFilter.off('change').on('change', function() {
        const selectedDept = $(this).val();
        updateCompanyFilterLabel();
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
        // Check if this employee has payment_type = 3 (on hold)
        const checkbox = $(this);
        const isOnHold = checkbox.closest('tr').find('td:first').find('.badge-warning').length > 0;
        
        if (isOnHold && checkbox.prop('checked')) {
            // Prevent selection and show warning
            checkbox.prop('checked', false);
            showWarning(__('payroll_on_hold'), __('cannot_select_employee_on_hold'));
            return;
        }
        
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
    $('#actionGeneratePayrollBtn').off('click', generatePayroll).on('click', generatePayroll);
    currentEventListeners.push(() => $('#actionGeneratePayrollBtn').off('click', generatePayroll));

    $('#actionImportPayrollExcelBtn').off('click', openPayrollExcelImportModal).on('click', openPayrollExcelImportModal);
    currentEventListeners.push(() => $('#actionImportPayrollExcelBtn').off('click', openPayrollExcelImportModal));

    // New: Generate Payroll Report button
    $('#actionGenerateReportBtn').off('click', generatePayrollReport).on('click', generatePayrollReport);
    currentEventListeners.push(() => $('#actionGenerateReportBtn').off('click', generatePayrollReport));

    // New: Re-generate Payroll button
    $('#actionRegeneratePayrollBtn').off('click', regeneratePayroll).on('click', regeneratePayroll);
    currentEventListeners.push(() => $('#actionRegeneratePayrollBtn').off('click', regeneratePayroll));

    // New: Start Approval button
    $('#actionStartApprovalBtn').off('click', startPayrollApproval).on('click', startPayrollApproval);
    currentEventListeners.push(() => $('#actionStartApprovalBtn').off('click', startPayrollApproval));

    const toggleFeedbackFilterHandler = function() {
        const feedbackEmployeesCount = getEmployeesWithOpenFeedbackCount();
        if (feedbackEmployeesCount < 1) {
            feedbackFilterActive = false;
            updateFeedbackFilterButtonVisibility();
            if (employeeTable) {
                employeeTable.draw();
            }
            updateMainSelectAllCheckbox();
            return;
        }

        feedbackFilterActive = !feedbackFilterActive;
        updateFeedbackFilterButtonVisibility();
        if (employeeTable) {
            employeeTable.draw();
        }
        updateMainSelectAllCheckbox();
    };
    $('#actionToggleFeedbackFilterBtn').off('click', toggleFeedbackFilterHandler).on('click', toggleFeedbackFilterHandler);
    currentEventListeners.push(() => $('#actionToggleFeedbackFilterBtn').off('click', toggleFeedbackFilterHandler));

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
                month: payrollMonth,
                is_generate: true
            }),
        });
        const result = await response.json();

        // If the server responds with 'warning', show a warning message (some employees skipped)
        if (result.status === 'warning') {
            const skippedEmployees = getSkippedEmployeesForDisplay(result.skipped_employees || []);
            const skippedEmployeesHtml = buildSkippedEmployeesHtml(skippedEmployees);
            Swal.fire({
                icon: 'warning',
                title: __('processing_completed_with_warnings') || 'Processing Completed with Warnings',
                html: `${(result.message || '').replace(/\n/g, '<br>')}${skippedEmployeesHtml}`,
                confirmButtonColor: '#ffc107',
                confirmButtonText: __('ok'),
                allowOutsideClick: false,
                width: '85%',
                didOpen: () => {
                    initSkippedEmployeesTable(skippedEmployees);
                }
            });
            fetchEmployees(); // Refresh employee list to update status
        }
        // If the server responds with 'success', show a success message
        else if (result.status === 'success') {
            const skippedEmployees = getSkippedEmployeesForDisplay(result.skipped_employees || []);
            const skippedEmployeesHtml = buildSkippedEmployeesHtml(skippedEmployees);
            Swal.fire({
                icon: 'success',
                title: __('payroll_generated_success_title'),
                html: `${(result.message || '').replace(/\n/g, '<br>')}${skippedEmployeesHtml}`,
                confirmButtonColor: '#6366f1',
                confirmButtonText: __('ok'),
                allowOutsideClick: false,
                width: skippedEmployees.length > 0 ? '85%' : undefined,
                didOpen: () => {
                    initSkippedEmployeesTable(skippedEmployees);
                }
            });
            await fetchEmployees(); // Refresh employee list to update status and action visibility
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

function normalizePayrollImportHeader(header) {
    return String(header || '')
        .trim()
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/^_+|_+$/g, '');
}

function getPayrollImportValue(row, aliases) {
    const normalizedRow = {};
    Object.keys(row || {}).forEach(key => {
        normalizedRow[normalizePayrollImportHeader(key)] = row[key];
    });

    for (const alias of aliases) {
        const normalizedAlias = normalizePayrollImportHeader(alias);
        if (Object.prototype.hasOwnProperty.call(normalizedRow, normalizedAlias)) {
            return normalizedRow[normalizedAlias];
        }
    }

    return '';
}

function normalizePayrollImportGosiText(value) {
    return String(value || '')
        .toLowerCase()
        .replace(/[^a-z]/g, '')
        .replace(/(.)\1+/g, '$1');
}

function isPayrollImportGosiReason(value) {
    return normalizePayrollImportGosiText(value).includes('gosi');
}

function normalizePayrollImportBenefitType(value) {
    const normalized = String(value || '').trim().toLowerCase();
    if (normalized === 'overtime_basic' || normalized === 'overtime_total' || normalized === 'by_hours' || normalized === 'fixed') {
        return normalized;
    }

    if (normalized === 'hourly' || normalized === 'calculated' || normalized === 'hours') {
        return 'by_hours';
    }

    return 'fixed';
}

function normalizePayrollImportDeductionType(value) {
    const normalized = String(value || '').trim().toLowerCase();
    if (normalized === 'fixed' || normalized === 'hourly_deduction' || normalized === 'daily_deduction') {
        return normalized;
    }

    if (normalized === 'hourly' || normalized === 'hours') {
        return 'hourly_deduction';
    }

    if (normalized === 'daily' || normalized === 'days') {
        return 'daily_deduction';
    }

    return 'fixed';
}

function mapPayrollImportRow(row, defaultMonth) {
    const mappedRow = {
        checkpoint_code: String(getPayrollImportValue(row, ['checkpoint_code', 'import_checkpoint_code', 'upload_checkpoint_code', 'template_checkpoint_code']) || '').trim(),
        emp_id: String(getPayrollImportValue(row, ['emp_id', 'employee_id', 'empid', 'employee code']) || '').trim(),
        month: String(getPayrollImportValue(row, ['month', 'payroll_month', 'month_year']) || defaultMonth || '').trim(),
        benefit_type: normalizePayrollImportBenefitType(getPayrollImportValue(row, ['benefit_type', 'overtime_type'])),
        overtime_value: String(getPayrollImportValue(row, ['overtime_value', 'overtime', 'benefit_value', 'benefits']) || '').trim(),
        overtime_hours: String(getPayrollImportValue(row, ['overtime_hours', 'overtime_hour', 'benefit_hours', 'hours']) || '').trim(),
        overtime_reason: String(getPayrollImportValue(row, ['overtime_reason', 'benefit_reason', 'benefits_reason', 'reason']) || '').trim(),
        deduction_type: normalizePayrollImportDeductionType(getPayrollImportValue(row, ['deduction_type'])),
        deduction_value: String(getPayrollImportValue(row, ['deduction_value', 'deduction', 'deductions', 'total_deduction']) || '').trim(),
        deduction_hours: String(getPayrollImportValue(row, ['deduction_hours', 'deduction_hour']) || '').trim(),
        deduction_days: String(getPayrollImportValue(row, ['deduction_days', 'deduction_day']) || '').trim(),
        deduction_reason: String(getPayrollImportValue(row, ['deduction_reason', 'deductions_reason', 'deduction_note']) || '').trim(),
    };

    if (isPayrollImportGosiReason(mappedRow.deduction_reason)) {
        mappedRow.deduction_type = 'fixed';
        mappedRow.deduction_value = '';
        mappedRow.deduction_hours = '';
        mappedRow.deduction_days = '';
        mappedRow.deduction_reason = '';
    }

    return mappedRow;
}

function rowHasPayrollImportData(row) {
    return Object.entries(row || {}).some(([field, value]) => !['checkpoint_code', 'month', 'benefit_type', 'deduction_type'].includes(field) && String(value || '').trim() !== '');
}

function generatePayrollImportCheckpointCode() {
    const prefix = 'PAYIMP';
    const monthValue = ($('#payrollMonth').val() || getCurrentPayrollMonthValue() || '').replace(/[^0-9]/g, '');

    if (window.crypto && typeof window.crypto.getRandomValues === 'function') {
        const bytes = new Uint8Array(6);
        window.crypto.getRandomValues(bytes);
        const randomCode = Array.from(bytes, byte => byte.toString(16).padStart(2, '0')).join('').toUpperCase();
        return `${prefix}-${monthValue}-${randomCode}`;
    }

    return `${prefix}-${monthValue}-${Date.now().toString(36).toUpperCase()}`;
}

function isValidPayrollImportCheckpointCode(checkpointCode) {
    return /^PAYIMP-\d{6}-[A-Z0-9]+$/i.test(String(checkpointCode || '').trim());
}

function getPayrollImportMetadataSheetName() {
    return '__PAYROLL_IMPORT_META';
}

function getPayrollImportBenefitsSheetName() {
    return 'Benefits Import';
}

function getPayrollImportDeductionsSheetName() {
    return 'Deductions Import';
}

function getPayrollImportMetadataFromWorkbook(workbook, dataSheetName, defaultMonth) {
    const metadataSheetName = getPayrollImportMetadataSheetName();
    const metadataSheet = workbook && workbook.Sheets ? workbook.Sheets[metadataSheetName] : null;
    const metadataCheckpointCode = String((metadataSheet && metadataSheet.A1 && metadataSheet.A1.v) || '').trim();
    const metadataMonthValue = String((metadataSheet && metadataSheet.B1 && metadataSheet.B1.v) || '').trim();

    if (isValidPayrollImportCheckpointCode(metadataCheckpointCode)) {
        return {
            checkpointCode: metadataCheckpointCode,
            monthValue: metadataMonthValue || defaultMonth || '',
            dataStartRow: 0,
        };
    }

    const worksheet = workbook && workbook.Sheets ? workbook.Sheets[dataSheetName] : null;
    const legacyCheckpointCode = String((worksheet && worksheet.A1 && worksheet.A1.v) || '').trim();

    if (isValidPayrollImportCheckpointCode(legacyCheckpointCode)) {
        return {
            checkpointCode: legacyCheckpointCode,
            monthValue: defaultMonth || '',
            dataStartRow: 1,
        };
    }

    return {
        checkpointCode: '',
        monthValue: defaultMonth || '',
        dataStartRow: 0,
    };
}

async function fetchPayrollImportCheckpointCode(defaultMonth) {
    const response = await fetch('./includes/api/generate_payroll_import_checkpoint.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            default_month: defaultMonth || ''
        })
    });

    const payload = await response.json().catch(() => null);
    if (!response.ok || !payload || payload.status !== 'success' || !isValidPayrollImportCheckpointCode(payload.checkpoint_code || '')) {
        throw new Error((payload && payload.message) || (__('payroll_import_checkpoint_generation_failed') || 'Unable to generate a payroll import template right now. Please try again.'));
    }

    return String(payload.checkpoint_code || '').trim();
}

async function downloadPayrollImportTemplate() {
    const defaultMonth = $('#payrollMonth').val() || getCurrentPayrollMonthValue();
    const checkpointCode = await fetchPayrollImportCheckpointCode(defaultMonth);
    const benefitTypeOptions = ['fixed', 'by_hours', 'overtime_total', 'overtime_basic'];
    const deductionTypeOptions = ['fixed', 'hourly_deduction', 'daily_deduction'];
    const benefitsSheetName = getPayrollImportBenefitsSheetName();
    const deductionsSheetName = getPayrollImportDeductionsSheetName();

    if (typeof ExcelJS !== 'undefined') {
        const workbook = new ExcelJS.Workbook();
        const benefitsWorksheet = workbook.addWorksheet(benefitsSheetName);
        const deductionsWorksheet = workbook.addWorksheet(deductionsSheetName);
        const typeListSheet = workbook.addWorksheet('__PAYROLL_IMPORT_TYPE_LISTS');
        const metadataSheet = workbook.addWorksheet(getPayrollImportMetadataSheetName());

        benefitsWorksheet.addRow(['emp_id', 'benefit_type', 'benefit_value', 'benefit_hours', 'benefit_reason']);
        benefitsWorksheet.addRow(['1001', 'fixed', '250.00', '', 'Project Support Benefit']);

        deductionsWorksheet.addRow(['emp_id', 'deduction_type', 'deduction_value', 'deduction_hours', 'deduction_days', 'deduction_reason']);
        deductionsWorksheet.addRow(['1001', 'hourly_deduction', '', '3', '', 'Late Arrival Deduction']);

        typeListSheet.addRow(['benefit_type_options', 'deduction_type_options']);
        const maxRows = Math.max(benefitTypeOptions.length, deductionTypeOptions.length);
        for (let i = 0; i < maxRows; i += 1) {
            typeListSheet.addRow([
                benefitTypeOptions[i] || '',
                deductionTypeOptions[i] || ''
            ]);
        }

        metadataSheet.addRow([checkpointCode, defaultMonth]);

        benefitsWorksheet.dataValidations.add('B2:B5000', {
            type: 'list',
            allowBlank: true,
            formulae: ['=__PAYROLL_IMPORT_TYPE_LISTS!$A$2:$A$5']
        });
        deductionsWorksheet.dataValidations.add('B2:B5000', {
            type: 'list',
            allowBlank: true,
            formulae: ['=__PAYROLL_IMPORT_TYPE_LISTS!$B$2:$B$4']
        });

        benefitsWorksheet.columns = [
            { width: 16 },
            { width: 20 },
            { width: 16 },
            { width: 16 },
            { width: 30 }
        ];

        deductionsWorksheet.columns = [
            { width: 16 },
            { width: 24 },
            { width: 18 },
            { width: 18 },
            { width: 16 },
            { width: 30 }
        ];

        typeListSheet.state = 'veryHidden';
        metadataSheet.state = 'veryHidden';

        const buffer = await workbook.xlsx.writeBuffer();
        const now = new Date();
        const mm = String(now.getMonth() + 1).padStart(2, '0');
        const dd = String(now.getDate()).padStart(2, '0');
        const yy = String(now.getFullYear()).slice(-2);
        const hh = String(now.getHours()).padStart(2, '0');
        const mins = String(now.getMinutes()).padStart(2, '0');
        const ss = String(now.getSeconds()).padStart(2, '0');
        const fileName = `payroll_import_template_${mm}${dd}${yy}${hh}${mins}${ss}.xlsx`;

        const blob = new Blob([buffer], {
            type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = fileName;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
        return;
    }

    // Fallback in case ExcelJS CDN is blocked; keeps template download available.
    const benefitsWorksheet = XLSX.utils.aoa_to_sheet([
        ['emp_id', 'benefit_type', 'benefit_value', 'benefit_hours', 'benefit_reason'],
        ['1001', 'fixed', '250.00', '', 'Project Support Benefit']
    ]);

    const deductionsWorksheet = XLSX.utils.aoa_to_sheet([
        ['emp_id', 'deduction_type', 'deduction_value', 'deduction_hours', 'deduction_days', 'deduction_reason'],
        ['1001', 'hourly_deduction', '', '3', '', 'Late Arrival Deduction']
    ]);

    const metadataSheetName = getPayrollImportMetadataSheetName();
    const metadataWorksheet = XLSX.utils.aoa_to_sheet([
        [checkpointCode, defaultMonth]
    ]);

    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, benefitsWorksheet, benefitsSheetName);
    XLSX.utils.book_append_sheet(workbook, deductionsWorksheet, deductionsSheetName);
    XLSX.utils.book_append_sheet(workbook, metadataWorksheet, metadataSheetName);
    workbook.Workbook = workbook.Workbook || {};
    workbook.Workbook.Sheets = [
        { name: benefitsSheetName, Hidden: 0 },
        { name: deductionsSheetName, Hidden: 0 },
        { name: metadataSheetName, Hidden: 2 }
    ];

    const now = new Date();
    const mm = String(now.getMonth() + 1).padStart(2, '0');
    const dd = String(now.getDate()).padStart(2, '0');
    const yy = String(now.getFullYear()).slice(-2);
    const hh = String(now.getHours()).padStart(2, '0');
    const mins = String(now.getMinutes()).padStart(2, '0');
    const ss = String(now.getSeconds()).padStart(2, '0');
    XLSX.writeFile(workbook, `payroll_import_template_${mm}${dd}${yy}${hh}${mins}${ss}.xlsx`);
}

function parsePayrollImportFile(file, defaultMonth) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();

        reader.onerror = () => reject(new Error(__('unable_to_read_file') || 'Unable to read the selected file.'));
        reader.onload = (event) => {
            try {
                const workbook = XLSX.read(event.target.result, { type: 'array', cellDates: true });
                const firstSheetName = workbook.SheetNames[0];
                const benefitsSheetName = getPayrollImportBenefitsSheetName();
                const deductionsSheetName = getPayrollImportDeductionsSheetName();
                const hasBenefitsSheet = !!(workbook.Sheets && workbook.Sheets[benefitsSheetName]);
                const hasDeductionsSheet = !!(workbook.Sheets && workbook.Sheets[deductionsSheetName]);

                if (!firstSheetName) {
                    throw new Error(__('no_sheet_found_in_excel') || 'No worksheet found in the Excel file.');
                }

                if (!hasBenefitsSheet || !hasDeductionsSheet) {
                    throw new Error(
                        __('payroll_import_invalid_file')
                        || `Invalid payroll import file. Please ensure both sheets exist: "${benefitsSheetName}" and "${deductionsSheetName}".`
                    );
                }

                const metadataDataSheetName = benefitsSheetName;
                const metadata = getPayrollImportMetadataFromWorkbook(workbook, metadataDataSheetName, defaultMonth);
                const checkpointCode = metadata.checkpointCode;
                const monthValue = String(metadata.monthValue || defaultMonth || '').trim();

                if (!isValidPayrollImportCheckpointCode(checkpointCode)) {
                    throw new Error(__('payroll_import_invalid_file') || 'The selected file is not valid. Please upload a valid payroll import file.');
                }

                const parseSheetRows = (worksheet, mapper, rangeStart = 0) => {
                    if (!worksheet) {
                        return [];
                    }

                    const rawRows = XLSX.utils.sheet_to_json(worksheet, {
                        range: rangeStart,
                        defval: '',
                        raw: false,
                        blankrows: false
                    });

                    if (!Array.isArray(rawRows) || rawRows.length === 0) {
                        return [];
                    }

                    return rawRows
                        .map((row) => mapper({ checkpoint_code: checkpointCode, month: monthValue, ...row }, monthValue))
                        .filter(rowHasPayrollImportData);
                };

                const mapBenefitSheetRow = (row, monthDefault) => {
                    const mappedRow = mapPayrollImportRow(row, monthDefault);
                    return {
                        checkpoint_code: mappedRow.checkpoint_code,
                        emp_id: mappedRow.emp_id,
                        month: mappedRow.month,
                        benefit_type: mappedRow.benefit_type,
                        overtime_value: mappedRow.overtime_value,
                        overtime_hours: mappedRow.overtime_hours,
                        overtime_reason: mappedRow.overtime_reason,
                        deduction_type: '',
                        deduction_value: '',
                        deduction_hours: '',
                        deduction_days: '',
                        deduction_reason: ''
                    };
                };

                const mapDeductionSheetRow = (row, monthDefault) => {
                    const mappedRow = mapPayrollImportRow(row, monthDefault);
                    return {
                        checkpoint_code: mappedRow.checkpoint_code,
                        emp_id: mappedRow.emp_id,
                        month: mappedRow.month,
                        benefit_type: '',
                        overtime_value: '',
                        overtime_hours: '',
                        overtime_reason: '',
                        deduction_type: mappedRow.deduction_type,
                        deduction_value: mappedRow.deduction_value,
                        deduction_hours: mappedRow.deduction_hours,
                        deduction_days: mappedRow.deduction_days,
                        deduction_reason: mappedRow.deduction_reason
                    };
                };

                const parsedRows = (() => {
                    const benefitRows = parseSheetRows(workbook.Sheets[benefitsSheetName], mapBenefitSheetRow, 0);
                    const deductionRows = parseSheetRows(workbook.Sheets[deductionsSheetName], mapDeductionSheetRow, 0);

                    const mergedMap = new Map();
                    const mergeRow = (row, keyPrefix, index) => {
                        const key = row.emp_id
                            ? `${row.checkpoint_code}::${row.month}::${row.emp_id}`
                            : `${keyPrefix}::${index}`;
                        const existing = mergedMap.get(key) || {
                            checkpoint_code: row.checkpoint_code,
                            emp_id: row.emp_id,
                            month: row.month,
                            benefit_type: '',
                            overtime_value: '',
                            overtime_hours: '',
                            overtime_reason: '',
                            deduction_type: '',
                            deduction_value: '',
                            deduction_hours: '',
                            deduction_days: '',
                            deduction_reason: ''
                        };

                        const merged = { ...existing };
                        Object.keys(row).forEach((field) => {
                            const value = String(row[field] == null ? '' : row[field]).trim();
                            if (value !== '') {
                                merged[field] = value;
                            }
                        });
                        mergedMap.set(key, merged);
                    };

                    benefitRows.forEach((row, index) => mergeRow(row, 'benefit', index));
                    deductionRows.forEach((row, index) => mergeRow(row, 'deduction', index));

                    return Array.from(mergedMap.values()).filter(rowHasPayrollImportData);
                })();

                if (parsedRows.length === 0) {
                    throw new Error(__('no_valid_rows_found') || 'No valid rows were found in the selected Excel file.');
                }

                const checkpointCodes = Array.from(new Set(parsedRows.map(row => String(row.checkpoint_code || '').trim()).filter(Boolean)));
                if (checkpointCodes.length === 0) {
                    throw new Error(__('payroll_import_invalid_file') || 'The selected file is not valid. Please upload a valid payroll import file.');
                }

                if (checkpointCodes.length > 1) {
                    throw new Error(__('payroll_import_invalid_file') || 'The selected file is not valid. Please upload a valid payroll import file.');
                }

                resolve(parsedRows);
            } catch (error) {
                reject(error);
            }
        };

        reader.readAsArrayBuffer(file);
    });
}

async function validatePayrollImportCheckpoint(checkpointCode) {
    const normalizedCheckpointCode = String(checkpointCode || '').trim();

    if (!isValidPayrollImportCheckpointCode(normalizedCheckpointCode)) {
        throw new Error(__('payroll_import_invalid_file') || 'The selected file is not valid. Please upload a valid payroll import file.');
    }

    const response = await fetch('./includes/api/check_payroll_import_checkpoint.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            checkpoint_code: normalizedCheckpointCode
        })
    });

    const payload = await response.json().catch(() => null);
    if (!response.ok || !payload || payload.status !== 'success') {
        throw new Error((payload && payload.message) || (__('payroll_import_validation_failed') || 'Unable to validate the selected file right now. Please try again.'));
    }

    if (!payload.exists) {
        throw new Error(payload.message || (__('payroll_import_invalid_file') || 'The selected file is not valid. Please upload a valid payroll import file.'));
    }

    if (payload.is_used) {
        throw new Error(payload.message || (__('payroll_import_file_already_used') || 'This file was already used. Please upload a different file.'));
    }

    return payload;
}

function getPayrollImportFileSignature(file) {
    if (!file) {
        return '';
    }

    return [file.name || '', file.size || 0, file.lastModified || 0].join('::');
}

function escapePayrollImportHtml(value) {
    return String(value == null ? '' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function getPayrollImportEmployeeMeta(empId) {
    const normalizedEmpId = String(empId || '').trim();

    if (!normalizedEmpId || !Array.isArray(allEmployeesData)) {
        return { emp_id: normalizedEmpId, parsed_name: '' };
    }

    const employee = allEmployeesData.find(item => String((item && item.emp_id) || '').trim() === normalizedEmpId);

    return {
        emp_id: normalizedEmpId,
        parsed_name: employee && employee.parsed_name ? String(employee.parsed_name).trim() : ''
    };
}

function getPayrollImportEmployeeCompensation(empId) {
    const normalizedEmpId = String(empId || '').trim();
    if (!normalizedEmpId || !Array.isArray(allEmployeesData)) {
        return {
            basic: 0,
            housing: 0,
            transport: 0,
            totalGross: 0
        };
    }

    const employee = allEmployeesData.find(item => String((item && item.emp_id) || '').trim() === normalizedEmpId) || {};
    const toNumber = (value) => {
        const parsed = parseFloat(value);
        return Number.isFinite(parsed) ? parsed : 0;
    };

    const basic = toNumber(employee.basic_salary || employee.basic);
    const housing = toNumber(employee.housing_allowance || employee.housing);
    const food = toNumber(employee.food_allowance || employee.food);
    const transport = toNumber(employee.transport_allowance || employee.transport);

    const totalFromPayroll = toNumber(employee.total_gross_salary);
    const totalFromComponents = basic
        + housing
        + transport
        + toNumber(employee.food_allowance || employee.food)
        + toNumber(employee.miscellaneous_allowance || employee.misc)
        + toNumber(employee.cashier_allowance || employee.cashier)
        + toNumber(employee.fuel_allowance || employee.fuel)
        + toNumber(employee.telephone_allowance || employee.tel)
        + toNumber(employee.other_allowance || employee.other)
        + toNumber(employee.guard_allowance || employee.guard);

    return {
        basic,
        housing,
        food,
        transport,
        totalGross: totalFromPayroll > 0 ? totalFromPayroll : totalFromComponents
    };
}

function formatPayrollImportComputedValue(value) {
    const numeric = Number(value);
    if (!Number.isFinite(numeric) || numeric <= 0) {
        return '';
    }
    return numeric.toFixed(2);
}

function computePayrollImportBenefitValue(empId, benefitType, hours) {
    const normalizedType = normalizePayrollImportBenefitType(benefitType || '');
    const parsedHours = Number(hours);
    if (!Number.isFinite(parsedHours) || parsedHours <= 0) {
        return '';
    }

    const comp = getPayrollImportEmployeeCompensation(empId);
    if (comp.totalGross <= 0) {
        return '';
    }

    let amount = 0;
    if (normalizedType === 'overtime_basic' || normalizedType === 'by_hours') {
        amount = (((comp.basic / 240) / 2) + (comp.totalGross / 240)) * parsedHours;
    } else if (normalizedType === 'overtime_total') {
        amount = (comp.totalGross / 240) * parsedHours;
    } else {
        return '';
    }

    return formatPayrollImportComputedValue(amount);
}

function computePayrollImportDeductionValue(empId, deductionType, hours, days) {
    const normalizedType = normalizePayrollImportDeductionType(deductionType || '');
    const parsedHours = Number(hours);
    const parsedDays = Number(days);
    const comp = getPayrollImportEmployeeCompensation(empId);
    const deductibleSalary = Math.max(comp.totalGross - comp.housing - comp.food, 0);

    if (deductibleSalary <= 0) {
        return '';
    }

    const hourlyRate = deductibleSalary / 240;
    let effectiveHours = 0;

    if (normalizedType === 'hourly_deduction') {
        effectiveHours = Number.isFinite(parsedHours) && parsedHours > 0 ? parsedHours : 0;
    } else if (normalizedType === 'daily_deduction') {
        const dayCount = Number.isFinite(parsedDays) && parsedDays > 0 ? parsedDays : 0;
        effectiveHours = dayCount * 8;
    } else {
        return '';
    }

    if (effectiveHours <= 0) {
        return '';
    }

    return formatPayrollImportComputedValue(hourlyRate * effectiveHours);
}

function updatePayrollImportDeductionColumnsVisibility() {
    const table = document.getElementById('payrollImportReviewTable');
    if (!table) {
        return;
    }

    const setColumnVisibility = (columnIndex, visible) => {
        table.querySelectorAll(`thead th:nth-child(${columnIndex}), tbody td:nth-child(${columnIndex})`).forEach((cell) => {
            cell.style.display = visible ? '' : 'none';
        });
    };

    // Table columns are 1-based:
    // 8 => deduction hours, 9 => deduction days.
    setColumnVisibility(8, true);
    setColumnVisibility(9, true);

    if (payrollImportReviewTable && typeof payrollImportReviewTable.columns === 'function') {
        payrollImportReviewTable.columns.adjust().draw(false);
    }
}

function applyPayrollImportRowTypeRules(row) {
    if (!row) {
        return;
    }

    const getInput = (field) => row.querySelector(`.payroll-import-review-input[data-field="${field}"]`);
    const empIdInput = getInput('emp_id');
    const empId = empIdInput ? String(empIdInput.value || '').trim() : '';

    const benefitTypeInput = getInput('benefit_type');
    const benefitValueInput = getInput('overtime_value');
    const benefitHoursInput = getInput('overtime_hours');
    const benefitReasonInput = getInput('overtime_reason');
    const benefitTypeRaw = benefitTypeInput ? String(benefitTypeInput.value || '').trim() : '';
    const benefitType = benefitTypeRaw === '' ? '' : normalizePayrollImportBenefitType(benefitTypeRaw);

    if (benefitValueInput && benefitHoursInput) {
        if (benefitType === '') {
            benefitHoursInput.value = '';
            benefitValueInput.value = '';
            benefitHoursInput.readOnly = true;
            benefitValueInput.readOnly = true;
            benefitHoursInput.classList.add('bg-light');
            benefitValueInput.classList.add('bg-light');

            if (benefitReasonInput) {
                benefitReasonInput.value = '';
                benefitReasonInput.readOnly = true;
                benefitReasonInput.classList.add('bg-light');
            }
        } else {
        const isBenefitCalculated = benefitType !== '' && benefitType !== 'fixed';
        benefitHoursInput.readOnly = !isBenefitCalculated;
        benefitHoursInput.classList.toggle('bg-light', !isBenefitCalculated);

        if (!isBenefitCalculated) {
            benefitHoursInput.value = '';
            benefitValueInput.readOnly = false;
            benefitValueInput.classList.remove('bg-light');

            if (benefitReasonInput) {
                const hasBenefitValue = Number(benefitValueInput.value || 0) > 0;
                benefitReasonInput.readOnly = !hasBenefitValue;
                benefitReasonInput.classList.toggle('bg-light', !hasBenefitValue);
                if (!hasBenefitValue) {
                    benefitReasonInput.value = '';
                }
            }
        } else {
            const enteredHours = parseFloat(benefitHoursInput.value || 0);
            benefitValueInput.readOnly = true;
            benefitValueInput.classList.add('bg-light');
            if (Number.isFinite(enteredHours) && enteredHours > 0) {
                benefitValueInput.value = computePayrollImportBenefitValue(empId, benefitType, benefitHoursInput.value);
                if (benefitReasonInput) {
                    benefitReasonInput.value = `Calculated benefit (${enteredHours} hours)`;
                }
            } else {
                benefitValueInput.value = '';
                if (benefitReasonInput) {
                    benefitReasonInput.value = '';
                }
            }

            if (benefitReasonInput) {
                benefitReasonInput.readOnly = true;
                benefitReasonInput.classList.add('bg-light');
            }
        }
        }
    }

    const deductionTypeInput = getInput('deduction_type');
    const deductionValueInput = getInput('deduction_value');
    const deductionHoursInput = getInput('deduction_hours');
    const deductionDaysInput = getInput('deduction_days');
    const deductionReasonInput = getInput('deduction_reason');
    const deductionTypeRaw = deductionTypeInput ? String(deductionTypeInput.value || '').trim() : '';
    const deductionType = deductionTypeRaw === '' ? '' : normalizePayrollImportDeductionType(deductionTypeRaw);
    const hasDeductionReason = deductionReasonInput ? String(deductionReasonInput.value || '').trim() !== '' : false;

    if (deductionValueInput && deductionHoursInput && deductionDaysInput) {
        if (deductionType === '') {
            deductionHoursInput.value = '';
            deductionDaysInput.value = '';
            deductionValueInput.value = '';
            deductionHoursInput.readOnly = true;
            deductionDaysInput.readOnly = true;
            deductionValueInput.readOnly = true;
            deductionHoursInput.style.display = '';
            deductionDaysInput.style.display = '';
            deductionHoursInput.classList.add('bg-light');
            deductionDaysInput.classList.add('bg-light');
            deductionValueInput.classList.add('bg-light');

            if (deductionReasonInput) {
                deductionReasonInput.value = '';
                deductionReasonInput.readOnly = true;
                deductionReasonInput.classList.add('bg-light');
            }
        } else if (deductionType === 'fixed') {
            deductionHoursInput.value = '';
            deductionDaysInput.value = '';
            deductionHoursInput.readOnly = true;
            deductionDaysInput.readOnly = true;
            deductionValueInput.readOnly = false;
            deductionHoursInput.style.display = '';
            deductionDaysInput.style.display = '';
            deductionHoursInput.classList.add('bg-light');
            deductionDaysInput.classList.add('bg-light');
            deductionValueInput.classList.remove('bg-light');

            if (deductionReasonInput) {
                deductionReasonInput.readOnly = false;
                deductionReasonInput.classList.remove('bg-light');
            }
        } else if (deductionType === 'hourly_deduction') {
            deductionDaysInput.value = '';
            deductionHoursInput.readOnly = false;
            deductionDaysInput.readOnly = true;
            deductionHoursInput.style.display = '';
            deductionDaysInput.style.display = '';
            deductionHoursInput.classList.remove('bg-light');
            deductionDaysInput.classList.add('bg-light');

            const enteredHours = parseFloat(deductionHoursInput.value || 0);
            deductionValueInput.readOnly = true;
            deductionValueInput.classList.add('bg-light');
            if (Number.isFinite(enteredHours) && enteredHours > 0) {
                deductionValueInput.value = computePayrollImportDeductionValue(empId, deductionType, deductionHoursInput.value, '');
                if (deductionReasonInput) {
                    deductionReasonInput.value = `Hourly deduction (${enteredHours} hours)`;
                }
            } else {
                deductionValueInput.value = '';
                if (deductionReasonInput) {
                    deductionReasonInput.value = '';
                }
            }

            if (deductionReasonInput) {
                deductionReasonInput.readOnly = true;
                deductionReasonInput.classList.add('bg-light');
            }
        } else {
            deductionHoursInput.value = '';
            deductionHoursInput.readOnly = true;
            deductionDaysInput.readOnly = false;
            deductionHoursInput.style.display = '';
            deductionDaysInput.style.display = '';
            deductionHoursInput.classList.add('bg-light');
            deductionDaysInput.classList.remove('bg-light');

            const enteredDays = parseFloat(deductionDaysInput.value || 0);
            deductionValueInput.readOnly = true;
            deductionValueInput.classList.add('bg-light');
            if (Number.isFinite(enteredDays) && enteredDays > 0) {
                deductionValueInput.value = computePayrollImportDeductionValue(empId, deductionType, '', deductionDaysInput.value);
                if (deductionReasonInput) {
                    deductionReasonInput.value = `Daily deduction (${enteredDays} days)`;
                }
            } else {
                deductionValueInput.value = '';
                if (deductionReasonInput) {
                    deductionReasonInput.value = '';
                }
            }

            if (deductionReasonInput) {
                deductionReasonInput.readOnly = true;
                deductionReasonInput.classList.add('bg-light');
            }
        }
    }

    updatePayrollImportDeductionColumnsVisibility();
}

function normalizeReviewedPayrollImportRow(row) {
    const normalizedRow = {
        checkpoint_code: String(row.checkpoint_code || '').trim(),
        emp_id: String(row.emp_id || '').trim(),
        month: String(row.month || '').trim(),
        benefit_type: normalizePayrollImportBenefitType(row.benefit_type || row.overtime_type || ''),
        overtime_value: String(row.overtime_value || '').trim(),
        overtime_hours: String(row.overtime_hours || '').trim(),
        overtime_reason: String(row.overtime_reason || '').trim(),
        deduction_type: normalizePayrollImportDeductionType(row.deduction_type || ''),
        deduction_value: String(row.deduction_value || '').trim(),
        deduction_hours: String(row.deduction_hours || '').trim(),
        deduction_days: String(row.deduction_days || '').trim(),
        deduction_reason: String(row.deduction_reason || '').trim(),
    };

    if (isPayrollImportGosiReason(normalizedRow.deduction_reason)) {
        normalizedRow.deduction_type = 'fixed';
        normalizedRow.deduction_value = '';
        normalizedRow.deduction_hours = '';
        normalizedRow.deduction_days = '';
        normalizedRow.deduction_reason = '';
    }

    if (normalizedRow.benefit_type === 'fixed') {
        if (!normalizedRow.overtime_value || Number(normalizedRow.overtime_value) <= 0 || normalizedRow.overtime_reason === '') {
            normalizedRow.overtime_value = '';
            normalizedRow.overtime_hours = '';
            normalizedRow.overtime_reason = '';
        }
        normalizedRow.overtime_hours = '';
    } else {
        normalizedRow.overtime_value = Number(normalizedRow.overtime_value) > 0 ? normalizedRow.overtime_value : '';
        if (!normalizedRow.overtime_hours || Number(normalizedRow.overtime_hours) <= 0 || normalizedRow.overtime_reason === '') {
            normalizedRow.overtime_value = '';
            normalizedRow.overtime_hours = '';
            normalizedRow.overtime_reason = '';
        }
    }

    if (normalizedRow.deduction_type === 'fixed') {
        if (!normalizedRow.deduction_value || Number(normalizedRow.deduction_value) <= 0 || normalizedRow.deduction_reason === '') {
            normalizedRow.deduction_value = '';
            normalizedRow.deduction_hours = '';
            normalizedRow.deduction_days = '';
            normalizedRow.deduction_reason = '';
        }
        normalizedRow.deduction_hours = '';
        normalizedRow.deduction_days = '';
    } else if (normalizedRow.deduction_type === 'hourly_deduction') {
        normalizedRow.deduction_value = Number(normalizedRow.deduction_value) > 0 ? normalizedRow.deduction_value : '';
        if (!normalizedRow.deduction_hours || Number(normalizedRow.deduction_hours) <= 0 || normalizedRow.deduction_reason === '') {
            normalizedRow.deduction_value = '';
            normalizedRow.deduction_hours = '';
            normalizedRow.deduction_days = '';
            normalizedRow.deduction_reason = '';
        }
        normalizedRow.deduction_days = '';
    } else {
        normalizedRow.deduction_value = Number(normalizedRow.deduction_value) > 0 ? normalizedRow.deduction_value : '';
        if (!normalizedRow.deduction_days || Number(normalizedRow.deduction_days) <= 0 || normalizedRow.deduction_reason === '') {
            normalizedRow.deduction_value = '';
            normalizedRow.deduction_hours = '';
            normalizedRow.deduction_days = '';
            normalizedRow.deduction_reason = '';
        }
        normalizedRow.deduction_hours = '';
    }

    if (normalizedRow.deduction_hours !== '' && Number(normalizedRow.deduction_hours) <= 0) {
        normalizedRow.deduction_hours = '';
    }

    if (normalizedRow.deduction_days !== '' && Number(normalizedRow.deduction_days) <= 0) {
        normalizedRow.deduction_days = '';
    }

    if (normalizedRow.overtime_hours !== '' && Number(normalizedRow.overtime_hours) <= 0) {
        normalizedRow.overtime_hours = '';
    }

    if (normalizedRow.deduction_hours !== '' && normalizedRow.deduction_days !== '') {
        normalizedRow.deduction_hours = '';
        normalizedRow.deduction_days = '';
    }

    return normalizedRow;
}

function rowHasReviewedPayrollImportEntry(row) {
    const hasBenefitEntry = row.benefit_type === 'fixed'
        ? (!!row.overtime_value && Number(row.overtime_value) > 0)
        : (!!row.overtime_hours && Number(row.overtime_hours) > 0);

    const hasDeductionEntry = row.deduction_type === 'fixed'
        ? (!!row.deduction_value && Number(row.deduction_value) > 0)
        : row.deduction_type === 'hourly_deduction'
            ? (!!row.deduction_hours && Number(row.deduction_hours) > 0)
            : (!!row.deduction_days && Number(row.deduction_days) > 0);

    return hasBenefitEntry || hasDeductionEntry;
}

function buildPayrollImportReviewTable(rows) {
    const checkpointCode = rows.length > 0 ? String(rows[0].checkpoint_code || '').trim() : '';
    const monthValue = rows.length > 0 ? String(rows[0].month || '').trim() : '';
    const bodyRows = rows.map((row, index) => {
        const employeeMeta = getPayrollImportEmployeeMeta(row.emp_id || '');
        const hasBenefitData = Number(row.overtime_value || 0) > 0 || Number(row.overtime_hours || 0) > 0 || String(row.overtime_reason || '').trim() !== '';
        const hasDeductionData = Number(row.deduction_value || 0) > 0 || Number(row.deduction_hours || 0) > 0 || Number(row.deduction_days || 0) > 0 || String(row.deduction_reason || '').trim() !== '';
        const selectedBenefitType = hasBenefitData ? String(row.benefit_type || '').trim() : '';
        const selectedDeductionType = hasDeductionData ? String(row.deduction_type || '').trim() : '';

        return `
        <tr data-row-index="${index}">
            <td class="text-center">${index + 1}</td>
            <td>
                <input type="hidden" class="payroll-import-review-input" data-field="checkpoint_code" value="${escapePayrollImportHtml(row.checkpoint_code || '')}">
                <div class="payroll-import-review-employee-cell">
                    <span class="payroll-import-review-employee-id">${escapePayrollImportHtml(employeeMeta.emp_id || '')}</span>
                    <span class="payroll-import-review-employee-name">${escapePayrollImportHtml(employeeMeta.parsed_name || '')}</span>
                </div>
                <input type="hidden" class="payroll-import-review-input" data-field="emp_id" value="${escapePayrollImportHtml(row.emp_id || '')}">
                <input type="hidden" class="payroll-import-review-input" data-field="month" value="${escapePayrollImportHtml(row.month || '')}">
            </td>
            <td>
                <select class="form-control form-control-sm payroll-import-review-input" data-field="benefit_type">
                    <option value="" ${selectedBenefitType === '' ? 'selected' : ''}>${escapePayrollImportHtml(__('select_type_option') || 'Select Type')}</option>
                    <option value="fixed" ${selectedBenefitType === 'fixed' ? 'selected' : ''}>${escapePayrollImportHtml(__('fixed_amount_option') || 'Fixed')}</option>
                    <option value="by_hours" ${selectedBenefitType === 'by_hours' ? 'selected' : ''}>${escapePayrollImportHtml(__('benefit_by_hour_option') || __('by_hour_option') || 'By Hour')}</option>
                    <option value="overtime_total" ${selectedBenefitType === 'overtime_total' ? 'selected' : ''}>${escapePayrollImportHtml(__('overtime') || 'Overtime')} (Total)</option>
                    <option value="overtime_basic" ${selectedBenefitType === 'overtime_basic' ? 'selected' : ''}>${escapePayrollImportHtml(__('overtime') || 'Overtime')} (Basic)</option>
                </select>
            </td>
            <td>
                <input type="text" class="form-control form-control-sm payroll-import-review-input payroll-import-hours-input" data-field="overtime_hours" min="0" step="0.01" value="${escapePayrollImportHtml(row.overtime_hours || '')}">
            </td>
            <td>
                <input type="text" class="form-control form-control-sm payroll-import-review-input" data-field="overtime_value" min="0" step="0.01" value="${escapePayrollImportHtml(row.overtime_value || '')}">
            </td>
            <td>
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control form-control-sm payroll-import-review-input" data-field="overtime_reason" value="${escapePayrollImportHtml(row.overtime_reason || '')}">
                    <div class="input-group-append">
                        <button type="button" class="btn btn-outline-danger payroll-import-clear-entry-btn" data-entry-type="overtime" title="${escapePayrollImportHtml(__('remove') || 'Remove')}">
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                </div>
            </td>
            <td>
                <select class="form-control form-control-sm payroll-import-review-input" data-field="deduction_type">
                    <option value="" ${selectedDeductionType === '' ? 'selected' : ''}>${escapePayrollImportHtml(__('select_type_option') || 'Select Type')}</option>
                    <option value="fixed" ${selectedDeductionType === 'fixed' ? 'selected' : ''}>${escapePayrollImportHtml(__('fixed_amount_option') || 'Fixed')}</option>
                    <option value="hourly_deduction" ${selectedDeductionType === 'hourly_deduction' ? 'selected' : ''}>${escapePayrollImportHtml(__('deduction_by_hour_option') || 'By Hour')}</option>
                    <option value="daily_deduction" ${selectedDeductionType === 'daily_deduction' ? 'selected' : ''}>${escapePayrollImportHtml(__('deduction_by_day_option') || 'By Day')}</option>
                </select>
            </td>
            <td>
                <input type="text" class="form-control form-control-sm payroll-import-review-input payroll-import-hours-input" data-field="deduction_hours" min="0" step="0.01" value="${escapePayrollImportHtml(row.deduction_hours || '')}">
            </td>
            <td>
                <input type="text" class="form-control form-control-sm payroll-import-review-input payroll-import-hours-input" data-field="deduction_days" min="0" step="0.01" value="${escapePayrollImportHtml(row.deduction_days || '')}">
            </td>
            <td>
                <input type="text" class="form-control form-control-sm payroll-import-review-input" data-field="deduction_value" min="0" step="0.01" value="${escapePayrollImportHtml(row.deduction_value || '')}">
            </td>
            <td>
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control form-control-sm payroll-import-review-input" data-field="deduction_reason" value="${escapePayrollImportHtml(row.deduction_reason || '')}">
                    <div class="input-group-append">
                        <button type="button" class="btn btn-outline-danger payroll-import-clear-entry-btn" data-entry-type="deduction" title="${escapePayrollImportHtml(__('remove') || 'Remove')}">
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                </div>
            </td>
        </tr>
    `;
    }).join('');

    return `
        <div style="text-align:left;">
            <div class="payroll-import-review-summary">
                <div><strong>${escapePayrollImportHtml(__('review_imported_rows') || 'Review Imported Rows')}</strong></div>
                <div>${escapePayrollImportHtml(__('processed_rows_label') || 'Processed Rows')}: <strong>${rows.length}</strong></div>
                <div>${escapePayrollImportHtml(__('month') || 'Month')}: <strong>${escapePayrollImportHtml(monthValue || '--')}</strong></div>
                <div>${escapePayrollImportHtml(__('checkpoint_code') || 'Checkpoint Code')}: <strong>${escapePayrollImportHtml(checkpointCode || '--')}</strong></div>
                <div>${escapePayrollImportHtml(__('payroll_import_review_hint') || 'Review and adjust overtime and deduction values before the final save.')}</div>
            </div>
            <div class="payroll-import-review-table-wrap">
                <table id="payrollImportReviewTable" class="table table-bordered table-sm payroll-import-review-table">
                    <thead>
                        <tr>
                            <th style="width:60px;">#</th>
                            <th style="width:190px;">${escapePayrollImportHtml(__('employee') || 'Employee')}</th>
                            <th style="width:170px;">${escapePayrollImportHtml(__('type') || 'Type')}</th>
                            <th style="width:95px;">${escapePayrollImportHtml(__('hours') || 'Hours')}</th>
                            <th style="width:150px;">${escapePayrollImportHtml(__('benefits_section') || 'Benefits')}</th>
                            <th style="width:260px;">${escapePayrollImportHtml(__('overtime_reason') || 'Overtime Reason')}</th>
                            <th style="width:170px;">${escapePayrollImportHtml(__('type') || 'Type')}</th>
                            <th style="width:95px;">${escapePayrollImportHtml(__('hours') || 'Hours')}</th>
                            <th style="width:95px;">${escapePayrollImportHtml(__('days') || 'Days')}</th>
                            <th style="width:150px;">${escapePayrollImportHtml(__('deduction') || 'Deduction')}</th>
                            <th style="width:260px;">${escapePayrollImportHtml(__('deduction_reason') || 'Deduction Reason')}</th>
                        </tr>
                    </thead>
                    <tbody>${bodyRows}</tbody>
                </table>
            </div>
        </div>
    `;
}

function collectReviewedPayrollImportRows() {
    const tableElement = $('#payrollImportReviewTable');
    const rowElements = payrollImportReviewTable
        ? payrollImportReviewTable.rows().nodes().toArray()
        : tableElement.find('tbody tr').toArray();

    return rowElements.map((row) => {
        const getFieldValue = (field) => {
            const input = row.querySelector(`.payroll-import-review-input[data-field="${field}"]`);
            return input ? String(input.value || '').trim() : '';
        };

        return normalizeReviewedPayrollImportRow({
            checkpoint_code: getFieldValue('checkpoint_code'),
            emp_id: getFieldValue('emp_id'),
            month: getFieldValue('month'),
            benefit_type: getFieldValue('benefit_type'),
            overtime_value: getFieldValue('overtime_value'),
            overtime_hours: getFieldValue('overtime_hours'),
            overtime_reason: getFieldValue('overtime_reason'),
            deduction_type: getFieldValue('deduction_type'),
            deduction_value: getFieldValue('deduction_value'),
            deduction_hours: getFieldValue('deduction_hours'),
            deduction_days: getFieldValue('deduction_days'),
            deduction_reason: getFieldValue('deduction_reason'),
        });
    });
}

function collectPayrollImportSkippedDetails() {
    const tableElement = $('#payrollImportReviewTable');
    const rowElements = payrollImportReviewTable
        ? payrollImportReviewTable.rows().nodes().toArray()
        : tableElement.find('tbody tr').toArray();

    return rowElements.flatMap((row, index) => {
        const getFieldValue = (field) => {
            const input = row.querySelector(`.payroll-import-review-input[data-field="${field}"]`);
            return input ? String(input.value || '').trim() : '';
        };

        const rowNumber = index + 2;
        const empId = getFieldValue('emp_id');
        const employeeLabel = empId ? `Row ${rowNumber} Employee ${empId}: ` : `Row ${rowNumber}: `;
        const benefitType = normalizePayrollImportBenefitType(getFieldValue('benefit_type'));
        const overtimeValue = getFieldValue('overtime_value');
        const overtimeHours = getFieldValue('overtime_hours');
        const overtimeReason = getFieldValue('overtime_reason');
        const deductionType = normalizePayrollImportDeductionType(getFieldValue('deduction_type'));
        const deductionValue = getFieldValue('deduction_value');
        const deductionHours = getFieldValue('deduction_hours');
        const deductionDays = getFieldValue('deduction_days');
        const deductionReason = getFieldValue('deduction_reason');
        const skippedDetails = [];

        if (benefitType === 'fixed') {
            if ((overtimeValue !== '' && Number(overtimeValue) > 0 && overtimeReason === '') || (overtimeValue === '' && overtimeReason !== '')) {
                skippedDetails.push(employeeLabel + 'Fixed benefit requires both value and reason.');
            }
        } else if ((overtimeHours !== '' && Number(overtimeHours) > 0 && overtimeReason === '') || (overtimeHours === '' && overtimeReason !== '')) {
            skippedDetails.push(employeeLabel + 'Calculated benefit requires hours and reason.');
        }

        if (deductionValue !== '' && Number(deductionValue) > 0 && isPayrollImportGosiReason(deductionReason)) {
            skippedDetails.push(employeeLabel + 'System-managed GOSI deduction was ignored from import.');
        } else if (deductionType === 'fixed') {
            if ((deductionValue !== '' && Number(deductionValue) > 0 && deductionReason === '') || (deductionValue === '' && deductionReason !== '')) {
                skippedDetails.push(employeeLabel + 'Fixed deduction requires both value and reason.');
            }
        } else if (deductionType === 'hourly_deduction') {
            if ((deductionHours !== '' && Number(deductionHours) > 0 && deductionReason === '') || (deductionHours === '' && deductionReason !== '')) {
                skippedDetails.push(employeeLabel + 'Hourly deduction requires hours and reason.');
            }
        } else if ((deductionDays !== '' && Number(deductionDays) > 0 && deductionReason === '') || (deductionDays === '' && deductionReason !== '')) {
            skippedDetails.push(employeeLabel + 'Daily deduction requires days and reason.');
        }

        if ((deductionHours !== '' && Number(deductionHours) > 0) && (deductionDays !== '' && Number(deductionDays) > 0)) {
            skippedDetails.push(employeeLabel + 'Deduction hours and deduction days cannot both be filled in the same row.');
        }

        if (((deductionHours !== '' && Number(deductionHours) > 0) || (deductionDays !== '' && Number(deductionDays) > 0)) && (deductionValue === '' || Number(deductionValue) <= 0 || deductionReason === '')) {
            skippedDetails.push(employeeLabel + 'Deduction hours/days were ignored because deduction requires both value and reason.');
        }

        return skippedDetails;
    });
}

function applyPayrollImportDeductionGuards(row) {
    if (!row) {
        return;
    }

    const deductionValueInput = row.querySelector('.payroll-import-review-input[data-field="deduction_value"]');
    const deductionReasonInput = row.querySelector('.payroll-import-review-input[data-field="deduction_reason"]');

    if (!deductionReasonInput || !isPayrollImportGosiReason(deductionReasonInput.value || '')) {
        return;
    }

    if (deductionValueInput) {
        deductionValueInput.value = '';
    }

    const deductionHoursInput = row.querySelector('.payroll-import-review-input[data-field="deduction_hours"]');
    const deductionDaysInput = row.querySelector('.payroll-import-review-input[data-field="deduction_days"]');
    if (deductionHoursInput) {
        deductionHoursInput.value = '';
    }
    if (deductionDaysInput) {
        deductionDaysInput.value = '';
    }

    const deductionTypeInput = row.querySelector('.payroll-import-review-input[data-field="deduction_type"]');
    if (deductionTypeInput) {
        deductionTypeInput.value = 'fixed';
    }

    deductionReasonInput.value = '';
}

function applyPayrollImportNumericGuards(rootElement) {
    if (!rootElement || typeof restrictToNumbers !== 'function') {
        return;
    }

    rootElement.querySelectorAll('.payroll-import-review-input[data-field="overtime_value"], .payroll-import-review-input[data-field="overtime_hours"], .payroll-import-review-input[data-field="deduction_value"], .payroll-import-review-input[data-field="deduction_hours"], .payroll-import-review-input[data-field="deduction_days"]').forEach((input) => {
        if (input.dataset.numericGuardApplied === '1') {
            return;
        }

        restrictToNumbers(input, {
            allowDecimal: true,
            allowNegative: false,
            minValue: 0
        });
        input.dataset.numericGuardApplied = '1';
    });
}

async function openPayrollImportReviewModal(rows, defaultMonth) {
    const reviewResult = await Swal.fire({
        title: __('review_imported_rows') || 'Review Imported Rows',
        html: buildPayrollImportReviewTable(rows),
        width: '92%',
        showCancelButton: true,
        confirmButtonText: __('save_after_review') || 'Save After Review',
        cancelButtonText: __('cancel') || 'Cancel',
        confirmButtonColor: '#2563eb',
        allowOutsideClick: false,
        focusConfirm: false,
        didOpen: () => {
            const reviewTableElement = $('#payrollImportReviewTable');

            const enforceDeductionGuards = function() {
                const row = this.closest('tr');
                applyPayrollImportDeductionGuards(row);
                applyPayrollImportRowTypeRules(row);
            };

            reviewTableElement.off('click', '.payroll-import-clear-entry-btn').on('click', '.payroll-import-clear-entry-btn', function() {
                const entryType = String(this.dataset.entryType || '').trim();
                const row = this.closest('tr');
                if (!row || !entryType) {
                    return;
                }

                const valueInput = row.querySelector(`.payroll-import-review-input[data-field="${entryType}_value"]`);
                const hoursInput = row.querySelector(`.payroll-import-review-input[data-field="${entryType}_hours"]`);
                const daysInput = row.querySelector(`.payroll-import-review-input[data-field="${entryType}_days"]`);
                const reasonInput = row.querySelector(`.payroll-import-review-input[data-field="${entryType}_reason"]`);
                const typeField = entryType === 'overtime' ? 'benefit_type' : `${entryType}_type`;
                const typeInput = row.querySelector(`.payroll-import-review-input[data-field="${typeField}"]`);

                if (valueInput) {
                    valueInput.value = '';
                    valueInput.dispatchEvent(new Event('change', { bubbles: true }));
                }

                if (hoursInput) {
                    hoursInput.value = '';
                    hoursInput.dispatchEvent(new Event('change', { bubbles: true }));
                }

                if (daysInput) {
                    daysInput.value = '';
                    daysInput.dispatchEvent(new Event('change', { bubbles: true }));
                }

                if (reasonInput) {
                    reasonInput.value = '';
                    reasonInput.dispatchEvent(new Event('change', { bubbles: true }));
                }

                if (typeInput) {
                    typeInput.value = '';
                    typeInput.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });

            reviewTableElement
                .off('input', '.payroll-import-review-input[data-field="deduction_reason"]', enforceDeductionGuards)
                .on('input', '.payroll-import-review-input[data-field="deduction_reason"]', enforceDeductionGuards);

            reviewTableElement
                .off('change', '.payroll-import-review-input[data-field="deduction_reason"]', enforceDeductionGuards)
                .on('change', '.payroll-import-review-input[data-field="deduction_reason"]', enforceDeductionGuards);

            reviewTableElement
                .off('input change', '.payroll-import-review-input[data-field="overtime_reason"], .payroll-import-review-input[data-field="deduction_reason"]')
                .on('input change', '.payroll-import-review-input[data-field="overtime_reason"], .payroll-import-review-input[data-field="deduction_reason"]', function() {
                    const row = this.closest('tr');
                    applyPayrollImportRowTypeRules(row);
                });

            reviewTableElement
                .off('change', '.payroll-import-review-input[data-field="benefit_type"], .payroll-import-review-input[data-field="deduction_type"]')
                .on('change', '.payroll-import-review-input[data-field="benefit_type"], .payroll-import-review-input[data-field="deduction_type"]', function() {
                    const row = this.closest('tr');
                    applyPayrollImportRowTypeRules(row);
                });

            reviewTableElement
                .off('input change', '.payroll-import-review-input[data-field="overtime_hours"], .payroll-import-review-input[data-field="deduction_hours"], .payroll-import-review-input[data-field="deduction_days"]')
                .on('input change', '.payroll-import-review-input[data-field="overtime_hours"], .payroll-import-review-input[data-field="deduction_hours"], .payroll-import-review-input[data-field="deduction_days"]', function() {
                    const row = this.closest('tr');
                    applyPayrollImportRowTypeRules(row);
                });

            reviewTableElement
                .off('input change', '.payroll-import-review-input[data-field="overtime_value"], .payroll-import-review-input[data-field="deduction_value"]')
                .on('input change', '.payroll-import-review-input[data-field="overtime_value"], .payroll-import-review-input[data-field="deduction_value"]', function() {
                    const row = this.closest('tr');
                    applyPayrollImportRowTypeRules(row);
                });

            reviewTableElement.find('tbody tr').each(function() {
                applyPayrollImportDeductionGuards(this);
                applyPayrollImportRowTypeRules(this);
            });

            if (reviewTableElement.length) {
                applyPayrollImportNumericGuards(reviewTableElement.get(0));
            }

            if ($.fn.DataTable && reviewTableElement.length) {
                if ($.fn.DataTable.isDataTable(reviewTableElement)) {
                    reviewTableElement.DataTable().destroy();
                }

                payrollImportReviewTable = reviewTableElement.DataTable({
                    pageLength: 10,
                    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, __('all') || 'All']],
                    order: [[1, 'asc']],
                    autoWidth: false,
                    responsive: false,
                    scrollX: true,
                    destroy: true,
                    language: {
                        search: `<span>${__('search') || 'Search'}:</span> _INPUT_`,
                        searchPlaceholder: __('search_employee_placeholder') || 'Search employee...',
                        lengthMenu: `${__('show') || 'Show'} _MENU_ ${__('entries') || 'entries'}`,
                        info: `${__('showing') || 'Showing'} _START_ ${__('to') || 'to'} _END_ ${__('of') || 'of'} _TOTAL_ ${__('entries') || 'entries'}`,
                        infoEmpty: `${__('showing') || 'Showing'} 0 ${__('to') || 'to'} 0 ${__('of') || 'of'} 0 ${__('entries') || 'entries'}`,
                        infoFiltered: `(${__('filtered_from') || 'filtered from'} _MAX_ ${__('total_entries') || 'total entries'})`,
                        paginate: {
                            first: __('first') || 'First',
                            last: __('last') || 'Last',
                            next: __('next') || 'Next',
                            previous: __('previous') || 'Previous'
                        },
                        zeroRecords: __('no_matching_records_found') || 'No matching records found',
                        emptyTable: __('no_data_available_in_table') || 'No data available in table'
                    },
                    columnDefs: [
                        { targets: 0, searchable: false, orderable: false, width: '60px' },
                        { targets: 1, width: '190px' },
                        { targets: [2, 3, 4, 5, 6, 7, 8, 9, 10], orderable: false }
                    ]
                });

                payrollImportReviewTable.columns.adjust().draw(false);

                reviewTableElement.find('tbody tr').each(function() {
                    applyPayrollImportNumericGuards(this);
                });
            }
        },
        willClose: () => {
            $('#payrollImportReviewTable').off('click', '.payroll-import-clear-entry-btn');
            $('#payrollImportReviewTable').off('input', '.payroll-import-review-input[data-field="deduction_reason"]');
            $('#payrollImportReviewTable').off('change', '.payroll-import-review-input[data-field="deduction_reason"]');
            $('#payrollImportReviewTable').off('input change', '.payroll-import-review-input[data-field="overtime_reason"], .payroll-import-review-input[data-field="deduction_reason"]');
            $('#payrollImportReviewTable').off('change', '.payroll-import-review-input[data-field="benefit_type"], .payroll-import-review-input[data-field="deduction_type"]');
            $('#payrollImportReviewTable').off('input change', '.payroll-import-review-input[data-field="overtime_hours"], .payroll-import-review-input[data-field="deduction_hours"], .payroll-import-review-input[data-field="deduction_days"]');
            $('#payrollImportReviewTable').off('input change', '.payroll-import-review-input[data-field="overtime_value"], .payroll-import-review-input[data-field="deduction_value"]');
            payrollImportReviewTable = null;
        },
        preConfirm: () => {
            const skippedDetails = collectPayrollImportSkippedDetails();
            const reviewedRows = collectReviewedPayrollImportRows();
            const rowsToSave = reviewedRows.filter(rowHasReviewedPayrollImportEntry);

            if (!Array.isArray(rowsToSave) || rowsToSave.length === 0) {
                Swal.showValidationMessage(__('no_valid_rows_found') || 'No valid rows were found to save.');
                return false;
            }

            const checkpointCodes = Array.from(new Set(rowsToSave.map(row => String(row.checkpoint_code || '').trim()).filter(Boolean)));
            if (checkpointCodes.length === 0) {
                Swal.showValidationMessage(__('payroll_import_invalid_file') || 'The selected file is not valid. Please upload a valid payroll import file.');
                return false;
            }

            if (checkpointCodes.length > 1) {
                Swal.showValidationMessage(__('payroll_import_invalid_file') || 'The selected file is not valid. Please upload a valid payroll import file.');
                return false;
            }

            const invalidRowIndex = rowsToSave.findIndex((row) => {
                return row.checkpoint_code === '' || row.emp_id === '' || row.month === '';
            });

            if (invalidRowIndex !== -1) {
                Swal.showValidationMessage((__('payroll_import_review_validation') || 'Each row must have Employee ID, Month, and at least one overtime or deduction entry.') + ' #' + (invalidRowIndex + 1));
                return false;
            }

            return {
                rows: rowsToSave,
                defaultMonth,
                skippedDetails
            };
        }
    });

    if (!reviewResult.isConfirmed || !reviewResult.value || !Array.isArray(reviewResult.value.rows)) {
        return null;
    }

    return reviewResult.value;
}

async function openPayrollExcelImportModal() {
    const defaultMonth = $('#payrollMonth').val() || getCurrentPayrollMonthValue();
    const importState = {
        rows: null,
        fileSignature: '',
        validationToken: 0,
        isValidating: false
    };

    const result = await Swal.fire({
        title: __('upload_payroll_excel') || 'Upload Payroll Excel',
        html: `
            <div style="text-align:left;">
                <p class="mb-2">${__('payroll_import_modal_hint') || 'Upload one Excel file to import benefits and deductions into generated payroll records.'}</p>
                <ol class="pl-3 mb-3 text-danger" style="font-size:13px;">
                    <li>${__('payroll_import_required_columns') || 'Required columns from row 1: emp_id, benefit type, benefit value, benefit hours, benefit reason, deduction type, deduction value, deduction hours, deduction days, deduction reason.'}</li>
                    <li>${__('payroll_import_type_options_hint') || 'Type options: benefit_type = fixed/by_hours/overtime_total/overtime_basic, deduction_type = fixed/hourly_deduction/daily_deduction.'}</li>
                    <li>${__('payroll_import_file_reuse_hint') || 'Each downloaded template stores the month with hidden one-time validation data. After one successful upload, the same file cannot be uploaded again.'}</li>
                    <li>${__('payroll_import_generate_first_hint') || 'Payroll must already be generated for the employee and month before import.'}</li>
                </ol>
                <div class="custom-file mb-3">
                    <input type="file" class="custom-file-input" id="payrollImportFile" accept=".xlsx,.xls">
                    <label class="custom-file-label" for="payrollImportFile">${__('choose_excel_file') || 'Choose Excel file'}</label>
                </div>
                <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap:8px;">
                    <small class="text-muted">${(__('current_month_label') || 'Current page month') + ': ' + defaultMonth}</small>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="downloadPayrollImportTemplateBtn">
                        <i class="fa fa-solid fa-download"></i> ${__('download_template') || 'Download Template'}
                    </button>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: __('yes_upload_it') || 'Upload',
        cancelButtonText: __('cancel') || 'Cancel',
        confirmButtonColor: '#2563eb',
        allowOutsideClick: false,
        didOpen: () => {
            const fileInput = document.getElementById('payrollImportFile');
            const fileLabel = document.querySelector('label[for="payrollImportFile"]');
            const templateBtn = document.getElementById('downloadPayrollImportTemplateBtn');
            const confirmButton = Swal.getConfirmButton();

            const resetImportState = () => {
                importState.rows = null;
                importState.fileSignature = '';
                importState.isValidating = false;
                if (confirmButton) {
                    confirmButton.disabled = true;
                }
            };

            const setFileValidationError = (message) => {
                resetImportState();
                if (fileInput) {
                    fileInput.value = '';
                }
                if (fileLabel) {
                    fileLabel.textContent = __('choose_excel_file') || 'Choose Excel file';
                }
                Swal.showValidationMessage(message);
            };

            resetImportState();

            if (fileInput && fileLabel) {
                fileInput.addEventListener('change', async function() {
                    const file = this.files && this.files[0] ? this.files[0] : null;
                    const fileName = file ? file.name : __('choose_excel_file') || 'Choose Excel file';
                    fileLabel.textContent = fileName;

                    if (!file) {
                        resetImportState();
                        return;
                    }

                    const currentValidationToken = ++importState.validationToken;
                    const fileSignature = getPayrollImportFileSignature(file);
                    importState.isValidating = true;
                    importState.rows = null;
                    importState.fileSignature = fileSignature;

                    if (confirmButton) {
                        confirmButton.disabled = true;
                    }

                    Swal.resetValidationMessage();

                    try {
                        const rows = await parsePayrollImportFile(file, defaultMonth);
                        const checkpointCode = rows.length > 0 ? String(rows[0].checkpoint_code || '').trim() : '';
                        await validatePayrollImportCheckpoint(checkpointCode);

                        if (currentValidationToken !== importState.validationToken) {
                            return;
                        }

                        importState.rows = rows;
                        importState.fileSignature = fileSignature;
                        importState.isValidating = false;
                        if (confirmButton) {
                            confirmButton.disabled = false;
                        }
                    } catch (error) {
                        if (currentValidationToken !== importState.validationToken) {
                            return;
                        }

                        setFileValidationError(error.message || (__('failed_to_parse_excel') || 'Failed to parse the selected Excel file.'));
                    }
                });
            }

            if (templateBtn) {
                templateBtn.addEventListener('click', async () => {
                    try {
                        await downloadPayrollImportTemplate();
                    } catch (error) {
                        Swal.showValidationMessage(error.message || (__('payroll_import_checkpoint_generation_failed') || 'Unable to generate a payroll import template right now. Please try again.'));
                    }
                });
            }
        },
        preConfirm: async () => {
            const fileInput = document.getElementById('payrollImportFile');
            if (!fileInput || !fileInput.files || !fileInput.files[0]) {
                Swal.showValidationMessage(__('please_select_excel_file') || 'Please select an Excel file first.');
                return false;
            }

            if (importState.isValidating) {
                Swal.showValidationMessage(__('validating_selected_file') || 'Please wait while the selected file is being validated.');
                return false;
            }

            const fileSignature = getPayrollImportFileSignature(fileInput.files[0]);

            if (importState.rows && importState.fileSignature === fileSignature) {
                return { rows: importState.rows, defaultMonth };
            }

            try {
                const rows = await parsePayrollImportFile(fileInput.files[0], defaultMonth);
                const checkpointCode = rows.length > 0 ? String(rows[0].checkpoint_code || '').trim() : '';
                await validatePayrollImportCheckpoint(checkpointCode);
                importState.rows = rows;
                importState.fileSignature = fileSignature;
                return { rows, defaultMonth };
            } catch (error) {
                Swal.showValidationMessage(error.message || (__('failed_to_parse_excel') || 'Failed to parse the selected Excel file.'));
                return false;
            }
        }
    });

    if (!result.isConfirmed || !result.value || !Array.isArray(result.value.rows)) {
        return;
    }

    const reviewedImport = await openPayrollImportReviewModal(result.value.rows, result.value.defaultMonth);
    if (!reviewedImport || !Array.isArray(reviewedImport.rows)) {
        return;
    }

    Swal.fire({
        title: __('uploading_payroll_import_title') || 'Importing Payroll Excel',
        html: __('please_wait_fetching_data') || 'Please wait...',
        didOpen: () => Swal.showLoading(),
        allowOutsideClick: false,
        allowEscapeKey: false
    });

    try {
        const response = await fetch('./includes/api/import_payroll_excel.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                rows: reviewedImport.rows,
                default_month: reviewedImport.defaultMonth
            })
        });

        const payload = await response.json();
        if (payload.status !== 'success') {
            const failedItems = Array.isArray(payload.errors) ? payload.errors.slice(0, 10) : [];
            const failedHtml = failedItems.length > 0
                ? `<div class="mt-3 text-left"><strong>${__('import_errors_label') || 'Rows with issues'}:</strong><ul class="pl-3 mb-0">${failedItems.map(item => `<li>${item}</li>`).join('')}</ul></div>`
                : '';

            await Swal.fire({
                icon: 'error',
                title: __('error') || 'Error',
                html: `
                    <div style="text-align:left;">
                        <div>${payload.message || (__('payroll_import_failed') || 'Payroll import failed.')}</div>
                        ${failedHtml}
                    </div>
                `,
                confirmButtonColor: '#2563eb',
                confirmButtonText: __('close') || 'Close',
                allowOutsideClick: false,
                width: '60%'
            });
            return;
        }

        const combinedSkippedItems = Array.from(new Set([
            ...(Array.isArray(reviewedImport.skippedDetails) ? reviewedImport.skippedDetails : []),
            ...(Array.isArray(payload.skipped_details) ? payload.skipped_details : [])
        ]));
        const visibleSkippedItems = combinedSkippedItems.slice(0, 12);
        const skippedHtml = visibleSkippedItems.length > 0
            ? `
                <div class="payroll-import-result-skipped">
                    <div class="payroll-import-result-skipped-title">${escapePayrollImportHtml((__('skipped') || 'Skipped') + ' ' + (__('entries') || 'Entries'))}</div>
                    <ul class="payroll-import-result-skipped-list">${visibleSkippedItems.map(item => `<li>${escapePayrollImportHtml(item)}</li>`).join('')}</ul>
                </div>
            `
            : '';
        const skippedCount = combinedSkippedItems.length;

        await Swal.fire({
            icon: skippedCount > 0 ? 'warning' : 'success',
            title: __('payroll_import_completed_title') || 'Payroll Import Completed',
            html: `
                <div style="text-align:left;">
                    <div class="payroll-import-result-summary">
                        <div class="payroll-import-result-stat">
                            <span class="payroll-import-result-stat-label">${escapePayrollImportHtml(__('processed_rows_label') || 'Processed Rows')}</span>
                            <span class="payroll-import-result-stat-value">${payload.processed_rows || 0}</span>
                        </div>
                        <div class="payroll-import-result-stat">
                            <span class="payroll-import-result-stat-label">${escapePayrollImportHtml(__('benefits_section') || 'Benefits')}</span>
                            <span class="payroll-import-result-stat-value">${payload.imported_benefits || 0}</span>
                        </div>
                        <div class="payroll-import-result-stat">
                            <span class="payroll-import-result-stat-label">${escapePayrollImportHtml(__('deductions_section') || 'Deductions')}</span>
                            <span class="payroll-import-result-stat-value">${payload.imported_deductions || 0}</span>
                        </div>
                        <div class="payroll-import-result-stat">
                            <span class="payroll-import-result-stat-label">${escapePayrollImportHtml(__('updated_records_label') || 'Updated Payrolls')}</span>
                            <span class="payroll-import-result-stat-value">${payload.updated_payrolls || 0}</span>
                        </div>
                        <div class="payroll-import-result-stat">
                            <span class="payroll-import-result-stat-label">${escapePayrollImportHtml(__('skipped') || 'Skipped')}</span>
                            <span class="payroll-import-result-stat-value">${skippedCount}</span>
                        </div>
                    </div>
                    ${skippedHtml}
                </div>
            `,
            confirmButtonColor: '#2563eb',
            confirmButtonText: __('ok') || 'OK',
            allowOutsideClick: false,
            width: skippedCount > 0 ? '78%' : '46%'
        });

        fetchEmployees();
    } catch (error) {
        console.error('Error importing payroll Excel:', error);
        showError(__('error') || 'Error', error.message || (__('payroll_import_failed') || 'Payroll import failed.'));
    }
}

const getSkippedEmployeesForDisplay = (skippedEmployees = []) => {
    if (!Array.isArray(skippedEmployees) || skippedEmployees.length === 0) {
        return [];
    }

    return skippedEmployees.map((item) => {
        const empId = String(item && item.emp_id ? item.emp_id : '').trim();
        const employeeMeta = Array.isArray(allEmployeesData)
            ? (allEmployeesData.find(emp => String(emp.emp_id || '').trim() === empId) || {})
            : {};
        const parsedName = String(employeeMeta.parsed_name || '').trim();
        const fallbackName = String(employeeMeta.name || item.name || '').trim();

        return {
            emp_id: empId,
            name: parsedName || fallbackName,
            department_name: employeeMeta.department_name || '',
            comp_name: employeeMeta.comp_name || '',
            sponsor: employeeMeta.sponsor || '',
            reason: String(item && item.reason ? item.reason : ''),
            blocked_month_label: String(item && item.blocked_month_label ? item.blocked_month_label : '')
        };
    });
};

const buildSkippedEmployeesHtml = (skippedEmployees = []) => {
    if (!skippedEmployees.length) return '';
    const tableId = 'skippedEmployeesTable';
    const title = `${__('employees_skipped_list') || 'Skipped Employees'}`;
    return `
        <hr>
        <div style="text-align:left;">
            <strong class="text-warning">${title} (${skippedEmployees.length})</strong>
            <div class="table-responsive" style="margin-top:10px;">
                <table id="${tableId}" class="table table-sm table-striped" style="width:100%;">
                    <thead>
                        <tr>
                            <th>${__('emp_id')}</th>
                            <th>${__('name')}</th>
                            <th>${__('department') || 'Department'}</th>
                            <th>${__('company_label') || 'Company'}</th>
                            <th>${__('sponsor') || 'Sponsor'}</th>
                            <th>${__('reason') || 'Reason'}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>`;
};

const initSkippedEmployeesTable = (skippedEmployees = []) => {
    if (!Array.isArray(skippedEmployees) || skippedEmployees.length === 0) {
        return;
    }

    if (!$.fn || !$.fn.DataTable || !$('#skippedEmployeesTable').length) {
        return;
    }

    if ($.fn.DataTable.isDataTable('#skippedEmployeesTable')) {
        $('#skippedEmployeesTable').DataTable().destroy();
    }

    $('#skippedEmployeesTable').DataTable({
        data: skippedEmployees,
        columns: [
            { data: 'emp_id' },
            { data: 'name', defaultContent: '-' },
            { data: 'department_name', defaultContent: '-' },
            { data: 'comp_name', defaultContent: '-' },
            { data: 'sponsor', defaultContent: '-' },
            {
                data: null,
                render: function(data) {
                    const reason = String(data.reason || '').trim();
                    const blockedMonth = String(data.blocked_month_label || '').trim();
                    return blockedMonth ? `${reason} - ${blockedMonth}` : reason;
                }
            }
        ],
        paging: true,
        searching: true,
        info: false,
        lengthChange: false,
        ordering: true,
        destroy: true
    });
};

async function showPayrollApprovalRequired(result) {
    const requestInvNo = result.request_inv_no || '';
    const approverName = result.pending_approver && result.pending_approver.approver_name
        ? result.pending_approver.approver_name
        : '';

    const infoHtml = `
        <div style="text-align:left;line-height:1.6;">
            <p>${(result.message || 'Payroll generation requires chain approval first.').replace(/\n/g, '<br>')}</p>
            ${requestInvNo ? `<p><strong>${__('request_id') || 'Request ID'}:</strong> ${requestInvNo}</p>` : ''}
            ${approverName ? `<p><strong>${__('pending_with') || 'Pending With'}:</strong> ${approverName}</p>` : ''}
            <p class="mb-0">${__('open_payroll_approvals_hint') || 'Open payroll approvals page to approve or track this request.'}</p>
        </div>
    `;

    const res = await Swal.fire({
        icon: 'info',
        title: __('payroll_approval_required_title') || 'Payroll Approval Required',
        html: infoHtml,
        showCancelButton: true,
        confirmButtonColor: '#6366f1',
        cancelButtonColor: '#6c757d',
        confirmButtonText: __('open_payroll_approvals') || 'Open Payroll Approvals',
        cancelButtonText: __('close') || 'Close',
        allowOutsideClick: false
    });

    if (res.isConfirmed) {
        window.location.href = './all_payroll_approvals.php';
        return;
    }
}

async function startPayrollApproval(monthOverride = null) {
    const generatedMonths = generatedPayrollMonthsCache.length > 0
        ? generatedPayrollMonthsCache
        : await getGeneratedPayrollMonths();

    generatedPayrollMonthsCache = generatedMonths;

    if (!generatedMonths.length) {
        $('#actionStartApprovalBtn').addClass('hidden').hide();
        showWarning(__('no_data_available_in_table') || 'No Data', __('no_generated_payroll_months_found') || 'No generated payroll months found.');
        return;
    }

    const defaultMonth = generatedMonths.some(m => m.value === monthOverride)
        ? monthOverride
        : (generatedMonths.some(m => m.value === $('#payrollMonth').val()) ? $('#payrollMonth').val() : generatedMonths[0].value);

    const monthOptionsHtml = generatedMonths.map(month => (
        `<option value="${month.value}" ${month.value === defaultMonth ? 'selected' : ''}>${month.label}</option>`
    )).join('');

    const monthSelection = await Swal.fire({
        title: __('start_approval', 'Start Approval'),
        html: `
            <div style="text-align:left;">
                <label for="startApprovalMonthSelect" style="font-weight:600; margin-bottom:8px; display:block;">${__('select_month_label') || 'Select Month'}</label>
                <select id="startApprovalMonthSelect" class="form-control">${monthOptionsHtml}</select>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#6c757d',
        confirmButtonText: __('start_approval', 'Start Approval'),
        cancelButtonText: __('cancel') || 'Cancel',
        allowOutsideClick: false,
        preConfirm: () => {
            const selectedMonth = $('#startApprovalMonthSelect').val();
            if (!selectedMonth) {
                Swal.showValidationMessage(__('please_select_month_for_report_validation') || 'Please select a month');
                return false;
            }
            return selectedMonth;
        }
    });

    if (!monthSelection.isConfirmed) {
        return;
    }

    const payrollMonth = monthSelection.value;

    Swal.fire({
        title: __('start_approval', 'Start Approval'),
        html: __('please_wait_fetching_data') || 'Please wait...',
        didOpen: () => Swal.showLoading(),
        allowOutsideClick: false,
        allowEscapeKey: false
    });

    try {
        const response = await fetch('./includes/api/start_payroll_approval.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ month: payrollMonth }),
        });

        const result = await response.json();

        if (result.status === 'success' || result.status === 'info') {
            await Swal.fire({
                icon: result.status === 'success' ? 'success' : 'info',
                title: __('payroll_approval_required_title') || 'Payroll Approval',
                html: `${(result.message || '').replace(/\n/g, '<br>')}${result.request_inv_no ? `<br><br><strong>${__('request_id') || 'Request ID'}:</strong> ${result.request_inv_no}` : ''}`,
                showCancelButton: true,
                confirmButtonColor: '#6366f1',
                cancelButtonColor: '#6c757d',
                confirmButtonText: __('open_payroll_approvals') || 'Open Payroll Approvals',
                cancelButtonText: __('close') || 'Close',
                allowOutsideClick: false
            }).then((res) => {
                if (res.isConfirmed) {
                    window.location.href = './all_payroll_approvals.php';
                }
            });
            return;
        }

        throw new Error(result.message || 'Failed to start payroll approval.');
    } catch (error) {
        console.error('Error starting payroll approval:', error);
        showError(__('error') || 'Error', error.message || 'Failed to start payroll approval.');
    }
}

async function regeneratePayroll() {
    // Get ALL employees (no checkbox filtering) - will regenerate for all non-hold employees
    const allEmployeeIds = Array.isArray(allEmployeesData)
        ? allEmployeesData.filter(emp => {
            const pt = parseInt(emp.payment_type || 1, 10);
            return pt !== 3; // Skip hold employees (payment_type = 3)
          })
          .map(emp => emp.emp_id)
        : [];

    const payrollMonth = $('#payrollMonth').val();

    // Validate that a payroll month is selected
    if (!payrollMonth) {
        showWarning(__('month_not_selected_warning_title'), __('please_select_payroll_month_warning'));
        return;
    }

    if (allEmployeeIds.length === 0) {
        showWarning(__('no_employees_available_warning_title') || 'No Employees', __('no_active_employees_available_for_regeneration') || 'No active (non-hold) employees available for regeneration.');
        return;
    }

    // Fetch existing benefits and deductions for the month
    let existingBenefitsDeductions = { has_benefits: false, has_deductions: false, count: 0 };
    try {
        const checkResponse = await fetch(`./includes/api/check_payroll_benefits_deductions.php?month=${payrollMonth}`);
        if (checkResponse.ok) {
            existingBenefitsDeductions = await checkResponse.json();
        }
    } catch (e) {
        console.warn('Could not check existing benefits/deductions:', e);
    }

    // Build warning message if benefits/deductions exist
    let warningHtml = `<div style="text-align:left;">${__('regenerate_payroll_confirmation_message') || 'This will update payroll for all non-hold employees and skip those on hold. Continue?'}</div>`;
    if (existingBenefitsDeductions.has_benefits || existingBenefitsDeductions.has_deductions) {
        const items = [];
        if (existingBenefitsDeductions.has_benefits) {
            items.push(`<i class="fas fa-gift text-info"></i> ${__('benefits_section')}`);
        }
        if (existingBenefitsDeductions.has_deductions) {
            items.push(`<i class="fas fa-minus-circle text-danger"></i> ${__('deductions_section')}`);
        }
        warningHtml += `<div style="margin-top:15px; padding:10px; background-color:#fff3cd; border-left:4px solid #ffc107; border-radius:4px;">
            <strong style="color:#856404;">⚠️ ${__('warning_existing_benefits_deductions') || 'Warning: Existing Benefits/Deductions'}</strong>
            <div style="margin-top:8px; color:#856404;">
                ${items.join('<br>')}
            </div>
            <small style="display:block; margin-top:8px; color:#856404;">${__('these_will_be_replaced_with_freshly_calculated_values')}</small>
        </div>`;
    }

    // Show confirmation dialog with warning
    const confirmation = await Swal.fire({
        title: __('regenerate_payroll_confirmation_title') || 'Regenerate Payroll?',
        html: warningHtml,
        icon: existingBenefitsDeductions.has_benefits || existingBenefitsDeductions.has_deductions ? 'warning' : 'info',
        showCancelButton: true,
        confirmButtonColor: '#ff9800',
        cancelButtonColor: '#6c757d',
        confirmButtonText: __('yes_regenerate_button') || 'Yes, Regenerate',
        cancelButtonText: __('cancel'),
        allowOutsideClick: false
    });

    if (!confirmation.isConfirmed) return;

    // Show a loading indicator while processing
    Swal.fire({
        title: __('regenerating_payroll_title') || 'Regenerating Payroll',
        html: __('please_wait_regenerating_payroll') || 'Please wait while payroll is being regenerated...',
        didOpen: () => Swal.showLoading(),
        allowOutsideClick: false,
        allowEscapeKey: false
    });

    try {
        // Send the request to the server to regenerate payroll
        const response = await fetch('./includes/api/process_payroll.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                employee_ids: allEmployeeIds,
                month: payrollMonth,
                is_regenerate: true
            }),
        });
        const result = await response.json();

        // If the server responds with 'warning', show a warning message (some employees skipped)
        if (result.status === 'warning') {
            const skippedEmployees = getSkippedEmployeesForDisplay(result.skipped_employees || []);
            const skippedEmployeesHtml = buildSkippedEmployeesHtml(skippedEmployees);
            Swal.fire({
                icon: 'warning',
                title: __('processing_completed_with_warnings') || 'Processing Completed with Warnings',
                html: `${(result.message || '').replace(/\n/g, '<br>')}${skippedEmployeesHtml}`,
                confirmButtonColor: '#ffc107',
                confirmButtonText: __('ok'),
                allowOutsideClick: false,
                width: '85%',
                didOpen: () => {
                    initSkippedEmployeesTable(skippedEmployees);
                }
            });
            fetchEmployees(); // Refresh employee list to update status
        }
        // If the server responds with 'success', show a success message
        else if (result.status === 'success') {
            const skippedEmployees = getSkippedEmployeesForDisplay(result.skipped_employees || []);
            const skippedEmployeesHtml = buildSkippedEmployeesHtml(skippedEmployees);
            Swal.fire({
                icon: 'success',
                title: __('payroll_regenerated_success_title') || 'Payroll Regenerated Successfully',
                html: `${(result.message || '').replace(/\n/g, '<br>')}${skippedEmployeesHtml}`,
                confirmButtonColor: '#6366f1',
                confirmButtonText: __('ok'),
                allowOutsideClick: false,
                width: skippedEmployees.length > 0 ? '85%' : undefined,
                didOpen: () => {
                    initSkippedEmployeesTable(skippedEmployees);
                }
            });
            await fetchEmployees(); // Refresh employee list to update status and action visibility
        } else {
            // If the server responds with an error, throw an error
            throw new Error(result.message || 'An unexpected error occurred.');
        }
    } catch (error) {
        // Catch any errors from the fetch or from the server's response and display them
        console.error('Error:', error);
        // The error message from the PHP script will be displayed here
        showError(__('error_regenerating_payroll_title') || 'Error Regenerating Payroll', error.message);
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
                    <option value="">${__('select_month') || 'Select Month'}</option>
                    <!-- Options will be loaded dynamically -->
                </select>
            </div>
            <div class="text-left mb-4">
                <label for="reportCompanySelect" class="block text-gray-700 text-sm font-bold mb-2">
                    ${__('company_filter_label') || 'Select Company (Optional)'}
                </label>
                <select id="reportCompanySelect" class="custom-select shadow px-3">
                    <option value="">All Companies</option>
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
            const selectedCompany = $('#reportCompanySelect').val();
            if (!selectedMonth) {
                Swal.showValidationMessage(__('please_select_month_for_report_validation'));
            }
            return { month: selectedMonth, company: selectedCompany }; // Return both month and company
        },
        // didOpen callback: executed after the modal is opened
        didOpen: async () => {
            const reportMonthSelect = document.getElementById('reportMonthSelect');
            const reportCompanySelect = document.getElementById('reportCompanySelect');
            const confirmButton = Swal.getConfirmButton();

            const setConfirmButtonLoadingState = (isLoading, canSubmit = false) => {
                if (!confirmButton) {
                    return;
                }

                if (isLoading) {
                    confirmButton.disabled = true;
                    Swal.showLoading(confirmButton);
                    return;
                }

                Swal.hideLoading();
                confirmButton.disabled = !canSubmit;
            };

            const setCompanyFilterLoadingState = (isLoading, canSelect = false) => {
                if (!reportCompanySelect) {
                    return;
                }

                const shouldDisable = isLoading || !canSelect;
                reportCompanySelect.disabled = shouldDisable;
                $(reportCompanySelect).prop('disabled', shouldDisable).trigger('change.select2');
            };

            let companiesLoadedSuccessfully = false;

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
                    // Initialize Select2 for the month select element
                    $(reportMonthSelect).select2({
                        placeholder: __('select_month') || 'Select Month',
                        allowClear: true,
                        width: '100%',
                        dropdownParent: $('.swal2-container')
                    });
                    
                    // Initialize Select2 for the company select element
                    $(reportCompanySelect).select2({
                        placeholder: __('all_companies_option') || 'All Companies',
                        allowClear: true,
                        width: '100%',
                        dropdownParent: $('.swal2-container')
                    });

                    setCompanyFilterLoadingState(true, false);

                    const populateReportCompaniesForMonth = async (monthValue, showMonthValidation = true) => {
                        companiesLoadedSuccessfully = false;
                        setConfirmButtonLoadingState(true, false);
                        setCompanyFilterLoadingState(true, false);

                        reportCompanySelect.innerHTML = '';

                        const defaultOption = document.createElement('option');
                        defaultOption.value = '';
                        defaultOption.textContent = __('all_companies_option') || 'All Companies';
                        reportCompanySelect.appendChild(defaultOption);

                        if (!monthValue) {
                            if (showMonthValidation) {
                                Swal.showValidationMessage(__('please_select_month_for_report_validation'));
                            } else {
                                Swal.resetValidationMessage();
                            }
                            $(reportCompanySelect).val('').trigger('change');
                            setConfirmButtonLoadingState(false, false);
                            setCompanyFilterLoadingState(false, false);
                            return;
                        }

                        try {
                            const reportResponse = await fetch(`./includes/api/get_payroll_report.php?month=${encodeURIComponent(monthValue)}`);
                            if (!reportResponse.ok) {
                                throw new Error(__('failed_to_fetch_report_data') || 'Failed to load report data');
                            }

                            const reportPayload = await reportResponse.json();
                            const reportRows = (reportPayload && reportPayload.status === 'success' && Array.isArray(reportPayload.report))
                                ? reportPayload.report
                                : [];

                            const companies = Array.from(new Set(
                                reportRows
                                    .map(row => String((row && row.comp_name) || '').trim())
                                    .filter(Boolean)
                            )).sort();

                            Swal.resetValidationMessage();
                            const currentLang = getCurrentLanguage();
                            companies.forEach((compName) => {
                                const option = document.createElement('option');
                                option.value = compName;
                                option.textContent = compName;

                                translateName(compName, 'en', currentLang, function(translatedName) {
                                    option.textContent = translatedName || compName;
                                    $(reportCompanySelect).trigger('change');
                                });

                                reportCompanySelect.appendChild(option);
                            });

                            companiesLoadedSuccessfully = true;
                        } catch (companyErr) {
                            console.error('Error loading report companies:', companyErr);
                            Swal.showValidationMessage(`${__('failed_to_fetch_report_data') || 'Failed to load report data'}: ${companyErr.message || ''}`);
                            companiesLoadedSuccessfully = false;
                        }

                        $(reportCompanySelect).val('').trigger('change');
                        setConfirmButtonLoadingState(false, companiesLoadedSuccessfully);
                        setCompanyFilterLoadingState(false, companiesLoadedSuccessfully);
                    };

                    await populateReportCompaniesForMonth(reportMonthSelect.value, false);
                    $(reportMonthSelect).off('change.reportCompany').on('change.reportCompany', async function() {
                        await populateReportCompaniesForMonth(String(this.value || ''));
                    });
                    
                    Swal.hideLoading(); // Hide loading indicator
                } else {
                    Swal.hideLoading();
                    // If no months are found, show a validation message and disable the confirm button
                    Swal.showValidationMessage(__('no_generated_payroll_months_found'));
                    setConfirmButtonLoadingState(false, false);
                    setCompanyFilterLoadingState(false, false);
                }
            } catch (error) {
                console.error('Error loading report months:', error);
                Swal.hideLoading();
                Swal.showValidationMessage(`${__('error_loading_report_months')} ${error.message}`);
                setConfirmButtonLoadingState(false, false); // Disable button on error
                setCompanyFilterLoadingState(false, false);
            }
        }
    }).then(async (result) => {
        // After the user confirms the month and company selection
        if (result.isConfirmed) {
            const selectedMonth = result.value.month;
            const selectedCompany = result.value.company;
            // Proceed to fetch and display the payroll report for the selected month and company
            await fetchAndDisplayPayrollReport(selectedMonth, selectedCompany);
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

async function savePayrollChanges(empId, month, updatedBenefits, updatedDeductions, paymentType) {
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
            // Attempt to update employee payment type as part of save flow
            try {
                const ptResp = await fetch('./includes/api/update_payment_type.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ emp_id: empId, payment_type: paymentType })
                });
                const ptResult = await ptResp.json();
                if (ptResult.status !== 'success') {
                    console.warn('Payment type update warning:', ptResult.message || ptResult);
                }
            } catch (ptErr) {
                console.warn('Payment type update failed:', ptErr);
            }

            Swal.fire({
                icon: 'success',
                title: __('changes_saved_success_title'),
                text: result.message,
                confirmButtonColor: '#6366f1',
                confirmButtonText: __('ok'),
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
        const response = await fetch(`./includes/api/get_payroll_details.php?emp_id=${empId}&month=${month}&_=${Date.now()}`);
        const data = await response.json();

        if (data.status === 'success') {
            payroll = data.payroll; // Set global payroll object
            const employee = data.employee;
            const benefits = data.benefits;
            let deductions = data.deductions;
            const feedbacks = Array.isArray(data.feedbacks) ? data.feedbacks : [];
            const gosiAmnt = (employee.gosi || 0) / 100;
            const navigationState = getEmployeeNavigationState(empId);
            const currentPosition = navigationState.currentIndex >= 0 ? navigationState.currentIndex + 1 : null;

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
            const escapeHtml = (value) => String(value == null ? '' : value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');

            const feedbackRowsHtml = feedbacks.length > 0
                ? feedbacks.map(item => {
                    const createdAt = item.created_at
                        ? new Date(item.created_at.replace(' ', 'T')).toLocaleString()
                        : '-';
                    const resolvedAt = item.resolved_at
                        ? new Date(item.resolved_at.replace(' ', 'T')).toLocaleString()
                        : '';
                    const statusClass = String(item.status || '').toLowerCase() === 'resolved' ? 'success' : 'warning';
                    const isResolved = String(item.status || '').toLowerCase() === 'resolved';
                    return `
                        <div class="border rounded p-2 mb-2 bg-light">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <strong><i class="fas fa-user-check"></i> ${escapeHtml(item.approver_name || '-')}</strong>
                                <span class="badge badge-${statusClass}">${escapeHtml(item.status || 'open')}</span>
                            </div>
                            <div class="small text-muted mb-1">
                                <i class="far fa-calendar-alt"></i> ${escapeHtml(item.payroll_month || '-')}
                                &nbsp; | &nbsp;
                                <i class="far fa-clock"></i> ${escapeHtml(createdAt)}
                            </div>
                            <div>${escapeHtml(item.feedback_note || '')}</div>
                            <div class="mt-2 d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    ${isResolved ? `<i class="fas fa-check-circle text-success"></i> ${__('resolved_by', 'Resolved By')}: ${escapeHtml(item.resolved_by_name || '-')} ${resolvedAt ? ('| ' + escapeHtml(resolvedAt)) : ''}` : ''}
                                </small>
                                ${!isResolved ? `<button type="button" class="btn btn-sm btn-outline-success mark-feedback-resolved-btn" data-feedback-id="${escapeHtml(item.id)}"><i class="fas fa-check"></i> ${__('mark_resolved', 'Mark Resolved')}</button>` : ''}
                            </div>
                        </div>
                    `;
                }).join('')
                : '';

            const hasOpenFeedback = feedbacks.some(item => String(item.status || '').toLowerCase() === 'open');
            const feedbackInitiallyHiddenClass = hasOpenFeedback ? '' : ' d-none';
            const feedbackToggleHtml = feedbacks.length > 0 && !hasOpenFeedback
                ? `
                    <div class="mb-2 text-end">
                        <button type="button" id="toggleFeedbackBlockBtn" class="btn btn-sm btn-outline-warning">
                            <i class="fas fa-eye"></i> ${__('show_approver_feedback', 'Show Approver Feedback')}
                        </button>
                    </div>
                `
                : '';

            const feedbackHtml = feedbacks.length > 0 ? `
                ${feedbackToggleHtml}
                <div id="approverFeedbackBlock" class="card border-warning shadow-sm mb-3${feedbackInitiallyHiddenClass}">
                    <div class="card-header d-flex justify-content-between align-items-center" style="background:#fff8e1;">
                        <h6 class="mb-0 text-warning"><i class="fas fa-comment-dots"></i> ${__('approver_feedback', 'Approver Feedback')}</h6>
                        <span class="badge badge-warning">${feedbacks.length}</span>
                    </div>
                    <div class="card-body py-2" style="max-height: 180px; overflow-y: auto;">
                        ${feedbackRowsHtml}
                    </div>
                </div>
            ` : '';

            const modalHtml = `
                <div class="payroll-details-container">
                    ${feedbackHtml}
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
                    
                    <!-- Payment Type Tabs -->
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-body py-2">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div class="small text-muted fw-bold">${__('salary_payment_type_label') || 'Payment Type'}</div>
                                <div class="btn-group btn-group-sm" id="payment-type-tabs" role="group" aria-label="Payment Type">
                                    <button type="button" class="btn btn-outline-info${Number(employee.payment_type||1)===1?' active':''}" data-paytype="1">
                                        <i class="fas fa-university"></i> ${__('bank_option') || 'Bank'}
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary${Number(employee.payment_type||1)===2?' active':''}" data-paytype="2">
                                        <i class="fas fa-money-bill-wave"></i> ${__('cash_option') || 'Cash'}
                                    </button>
                                    <button type="button" class="btn btn-outline-warning${Number(employee.payment_type||1)===3?' active':''}" data-paytype="3">
                                        <i class="fas fa-pause-circle"></i> ${__('hold_option') || 'Hold'}
                                    </button>
                                </div>
                            </div>
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

            const modalFooterHtml = `
                <div class="payroll-nav-footer text-center">
                    <div class="payroll-nav-actions">
                        <button type="button" id="prevEmployeeBtn" class="btn btn-sm payroll-nav-btn payroll-nav-btn-prev" ${navigationState.previousEmployee ? '' : 'disabled'}>
                            <i class="fas fa-arrow-left"></i>
                            <span>${__('previous') || 'Previous'}</span>
                        </button>
                        ${currentPosition ? `<small class="payroll-nav-counter mb-0">${currentPosition} / ${navigationState.employees.length}</small>` : '<span></span>'}
                        <button type="button" id="nextEmployeeBtn" class="btn btn-sm payroll-nav-btn payroll-nav-btn-next" ${navigationState.nextEmployee ? '' : 'disabled'}>
                            <span>${__('next') || 'Next'}</span>
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            `;

            Swal.fire({
                html: modalHtml,
                footer: modalFooterHtml,
                width: '900px',
                showCancelButton: true,
                confirmButtonText: __('save_changes_button'),
                confirmButtonColor: '#6366f1',
                cancelButtonText: __('close'),
                allowOutsideClick: false,
                didOpen: () => {
                    const originalGrossSalary = parseFloat(payroll.total_gross_salary);
                    const updateDynamicNetSalary = () => updateNetSalaryDisplay(originalGrossSalary);
                    const initialModalState = collectPayrollModalData(employee);

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
                        if (!element) {
                            return;
                        }
                        element.addEventListener(event, handler);
                        currentEventListeners.push(() => element.removeEventListener(event, handler));
                    };

                    addDynamicEventListener(document.getElementById('prevEmployeeBtn'), 'click', () => {
                        navigatePayrollEmployee(navigationState.previousEmployee, employee, month, initialModalState);
                    });

                    addDynamicEventListener(document.getElementById('nextEmployeeBtn'), 'click', () => {
                        navigatePayrollEmployee(navigationState.nextEmployee, employee, month, initialModalState);
                    });

                    document.querySelectorAll('.mark-feedback-resolved-btn').forEach(btn => {
                        addDynamicEventListener(btn, 'click', async function() {
                            const feedbackId = this.getAttribute('data-feedback-id');
                            if (!feedbackId) {
                                return;
                            }

                            const confirmResult = await Swal.fire({
                                title: __('confirm_action', 'Confirm Action'),
                                text: __('mark_feedback_resolved_confirm', 'Mark this feedback as resolved?'),
                                icon: 'question',
                                showCancelButton: true,
                                confirmButtonText: __('yes_mark_resolved', 'Yes, mark resolved'),
                                cancelButtonText: __('cancel', 'Cancel'),
                                confirmButtonColor: '#28a745'
                            });

                            if (!confirmResult.isConfirmed) {
                                return;
                            }

                            try {
                                Swal.fire({
                                    title: __('processing', 'Processing'),
                                    html: `${__('please_wait_processing', 'Please wait while processing...')}<br><small>${__('sending_email_notification', 'Sending email notification...')}</small>`,
                                    allowOutsideClick: false,
                                    allowEscapeKey: false,
                                    showConfirmButton: false,
                                    didOpen: () => Swal.showLoading()
                                });

                                await new Promise((resolve) => {
                                    requestAnimationFrame(() => requestAnimationFrame(resolve));
                                });

                                const updateResponse = await fetch('./includes/api/update_payroll_feedback_status.php', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify({
                                        feedback_id: parseInt(feedbackId, 10),
                                        status: 'resolved'
                                    })
                                });

                                const updateResult = await updateResponse.json();
                                if (updateResult.status !== 'success') {
                                    throw new Error(updateResult.message || 'Failed to update feedback status');
                                }

                                Swal.close();

                                const resolvedEmployee = updateResult.resolved_employee || {};
                                const resolvedEmployeeHtml = resolvedEmployee.emp_id
                                    ? `
                                        <div class="swal-payroll-details" style="margin-top:12px; text-align:left;">
                                            <div class="swal-details-header"><i class="fas fa-user-check"></i> ${__('resolved_employee_info', 'Resolved Employee Information')}</div>
                                            <div class="swal-details-body">
                                                <div class="swal-detail-item"><span class="swal-detail-label">${__('emp_id', 'Employee ID')}</span><span class="swal-detail-value">${resolvedEmployee.emp_id || 'N/A'}</span></div>
                                                <div class="swal-detail-item"><span class="swal-detail-label">${__('employee_name', 'Employee Name')}</span><span class="swal-detail-value">${resolvedEmployee.employee_name || 'N/A'}</span></div>
                                                <div class="swal-detail-item"><span class="swal-detail-label">${__('iqama', 'Iqama')}</span><span class="swal-detail-value">${resolvedEmployee.iqama || 'N/A'}</span></div>
                                                <div class="swal-detail-item"><span class="swal-detail-label">${__('payroll_month', 'Payroll Month')}</span><span class="swal-detail-value">${resolvedEmployee.payroll_month || month || 'N/A'}</span></div>
                                            </div>
                                        </div>
                                    `
                                    : '';

                                await Swal.fire({
                                    icon: updateResult.email_sent === false ? 'warning' : 'success',
                                    title: updateResult.email_sent === false
                                        ? __('warning', 'Warning')
                                        : __('success', 'Success'),
                                    html: `<div>${updateResult.message || __('feedback_marked_resolved', 'Feedback marked as resolved.')}</div>${resolvedEmployeeHtml}`,
                                    confirmButtonText: __('ok', 'OK'),
                                    showConfirmButton: true,
                                    allowOutsideClick: false
                                });

                                await fetchEmployees();
                                Swal.close();
                                showPayrollDetails(empId, empName, month);
                            } catch (err) {
                                Swal.close();
                                Swal.fire(__('error', 'Error'), err.message, 'error');
                            }
                        });
                    });

                    const toggleFeedbackBtn = document.getElementById('toggleFeedbackBlockBtn');
                    if (toggleFeedbackBtn) {
                        addDynamicEventListener(toggleFeedbackBtn, 'click', function() {
                            const block = document.getElementById('approverFeedbackBlock');
                            if (!block) {
                                return;
                            }
                            const isHidden = block.classList.contains('d-none');
                            if (isHidden) {
                                block.classList.remove('d-none');
                                this.innerHTML = `<i class="fas fa-eye-slash"></i> ${__('hide_approver_feedback', 'Hide Approver Feedback')}`;
                            } else {
                                block.classList.add('d-none');
                                this.innerHTML = `<i class="fas fa-eye"></i> ${__('show_approver_feedback', 'Show Approver Feedback')}`;
                            }
                        });
                    }

                    // Payment Type tabs behavior
                    document.querySelectorAll('#payment-type-tabs .btn').forEach(btn => {
                        addDynamicEventListener(btn, 'click', function() {
                            document.querySelectorAll('#payment-type-tabs .btn').forEach(b => b.classList.remove('active'));
                            this.classList.add('active');
                        });
                    });

                    // Benefit Type Rules: keep behavior aligned with calculation_type.
                    const applyBenefitTypeRules = function(selectElement) {
                        const select = $(selectElement);
                        if (!select.length) {
                            return;
                        }

                        const selectedOption = select.find('option:selected');
                        const calculationType = String(selectedOption.data('calculation') || 'fixed').toLowerCase();
                        const row = select.closest('.benefit-row');
                        const hoursContainer = row.find('.benefit-hours-slot').first();
                        const amountInput = row.find('.benefit-amount');
                        const isCalculated = ['overtime_basic', 'overtime_total', 'by_hours'].includes(calculationType);

                        if (isCalculated) {
                            if (hoursContainer.find('.benefit-hours').length === 0) {
                                const storedHours = parseFloat(hoursContainer.attr('data-hours') || 0) || 0;
                                hoursContainer.html(`
                                    <div class="input-group input-group-sm">
                                        <input type="text" min="0" class="form-control benefit-hours" value="${storedHours}" placeholder="Hours">
                                        <span class="input-group-text bg-light rounded-left-0" style="font-size:12px !important;">hrs</span>
                                    </div>
                                `);
                                const newHoursInput = hoursContainer.find('.benefit-hours')[0];
                                addDynamicEventListener(newHoursInput, 'input', calculateOvertime);
                            }
                            amountInput.prop('readonly', true);
                        } else {
                            hoursContainer.empty();
                            amountInput.prop('readonly', false);
                        }

                        calculateOvertime.call(selectElement);
                        updateDynamicNetSalary();
                    };

                    const handleBenefitTypeChange = function() {
                        applyBenefitTypeRules(this);
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
                            if (noteInput.length) {
                                noteInput.val(`Overtime (${hours} hours)`);
                            }

                        } else if (benefitType === 'overtime_total' || benefitType === 'by_hours') {
                            // This calculation remains the same, using only the total salary
                            const totalSalary = parseFloat(payroll.total_gross_salary);
                            const amount = ((totalSalary / 240) * hours).toFixed(2);
                            amountInput.val(amount).prop('readonly', true);
                            if (noteInput.length) {
                                noteInput.val(`Overtime (${hours} hours)`);
                            }

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
                    // Add event listeners for existing benefit type selects and apply rules on modal load.
                    document.querySelectorAll('.benefit-type').forEach(select => {
                        addDynamicEventListener(select, 'change', handleBenefitTypeChange);
                        applyBenefitTypeRules(select);
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
                            <div class="col-md-2 benefit-hours-slot"></div>
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
                                        
                                        // Show success message and reopen the edit modal
                                        await Swal.fire({
                                            title: __('deleted_success_title'),
                                            text: __('benefit_deleted_success_msg'),
                                            icon: 'success',
                                            timer: 1500,
                                            showConfirmButton: false
                                        });
                                        
                                        // Reopen the edit modal after deletion
                                        Swal.close();
                                        showPayrollDetails(empId, empName, month);
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
                                        
                                        // Show success message and reopen the edit modal
                                        await Swal.fire({
                                            title: __('deleted_success_title'),
                                            text: __('deduction_deleted_success_msg'),
                                            icon: 'success',
                                            timer: 1500,
                                            showConfirmButton: false
                                        });
                                        
                                        // Reopen the edit modal after deletion
                                        Swal.close();
                                        showPayrollDetails(empId, empName, month);
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
                    const applyDeductionTypeRules = function(selectElement) {
                        const row = $(selectElement).closest('.deduction-row');
                        const deductionType = row.find('.deduction-type').val() || 'fixed';
                        const periodSlot = row.find('.deduction-period-slot');
                        const amountInput = row.find('.deduction-amount');
                        const nameInput = row.find('.deduction-name');
                        const isFixed = deductionType === 'fixed';

                        periodSlot.toggleClass('deduction-period-input-empty', isFixed);
                        row.find('.deduction-hours').toggle(deductionType === 'hourly_deduction');
                        row.find('.deduction-days').toggle(deductionType === 'daily_deduction');
                        row.find('.deduction-period-unit')
                            .text(deductionType === 'hourly_deduction' ? 'hrs' : 'days')
                            .toggle(!isFixed);

                        if (isFixed) {
                            row.find('.deduction-hours, .deduction-days').val('');
                            amountInput.prop('readonly', false);
                            nameInput.show();
                        } else {
                            amountInput.prop('readonly', true);
                            nameInput.hide();
                            calculateDeductionAmount.call(selectElement);
                        }

                        updateDynamicNetSalary();
                    };

                    $(swalContainer).find('.deduction-row .deduction-type').each(function() {
                        applyDeductionTypeRules(this);
                    });

                    $(swalContainer).on('change', '.deduction-type', function() {
                        const row = $(this).closest('.deduction-row');
                        const isGosiRow = row.find('.gosi-deduction-name').length > 0;
                        if (isGosiRow) {
                            return;
                        }
                        applyDeductionTypeRules(this);
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
                    return collectPayrollModalData(employee);
                }
            }).then((result) => {
                addEventListeners();
                if (result.isConfirmed) {
                    savePayrollChanges(empId, month, result.value.updatedBenefits, result.value.updatedDeductions, result.value.paymentType);
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
        async function fetchAndDisplayPayrollReport(selectedMonth, selectedCompany = '') {
            const companyText = selectedCompany ? ` - ${selectedCompany}` : '';
            Swal.fire({
                title: __('generating_report_title'),
                html: `${__('fetching_payroll_data_for_month')} ${new Date(selectedMonth + '-01').toLocaleString('default', { month: 'long', year: 'numeric' })}${companyText}. ${__('please_wait_fetching_data')}`,
                didOpen: () => Swal.showLoading(),
                allowOutsideClick: false,
                allowEscapeKey: false
            });

            try {
                // Fetch the payroll report data for the chosen month and optional company filter
                const companyParam = selectedCompany ? `&company=${encodeURIComponent(selectedCompany)}` : '';
                const response = await fetch(`./includes/api/get_payroll_report.php?month=${selectedMonth}${companyParam}`);
                if (!response.ok) {
                    const errorText = await response.text();
                    throw new Error(`Server responded with status ${response.status}: ${errorText}`);
                }
                const data = await response.json();

                if (data.status === 'success') {
                const reportData = data.report;
                const vacationEmployeesData = Array.isArray(data.vacation_employees) ? data.vacation_employees : [];
                const bankFileReady = !!data.bank_file_ready;
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
                            <button id="exportExcelBtn" class="btn btn-success" style="display:${bankFileReady ? 'inline-block' : 'none'};"><i class="fas fa-file-excel"></i> ${__('bank_excel_button')}</button>
                            <button id="exportDetailedExcelBtn" class="btn btn-info"><i class="fas fa-file-excel"></i> Detailed Excel</button>
                            <div id="bankExcelPendingNote" class="mt-2 text-muted" style="display:${bankFileReady ? 'none' : 'block'}; font-size: 13px; font-weight: 600;">
                                Finance review notification is pending. Bank EXCEL will be available after finance officer clicks "Notify HR Payroll Review Completed".
                            </div>
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
                            order: [[1, 'asc']],
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
                        $('#exportDetailedExcelBtn').on('click', () => exportDetailedExcelReport(reportData, selectedMonth, vacationEmployeesData));
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

        // --- Helper: PDF font handler (skip custom fonts due to jsPDF compatibility) ---
        async function ensurePdfArabicFont(doc) {
            // jsPDF will use default Helvetica font
            // Custom font loading was causing parser errors; using built-in font instead
            console.info('PDF report using default font (Helvetica). Arabic characters may not render.');
            return false;
        }

        // --- PDF Export Function (MODIFIED FOR DETAILED REPORT) ---
        async function exportPdfReport(reportData, selectedMonth) {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF({
                orientation: 'landscape',
                unit: 'pt', // Use points for better control over font sizes and margins
                format: 'a4'
            });
            const arabicFontName = await ensurePdfArabicFont(doc);
            if (arabicFontName) {
                doc.setFont(arabicFontName);
            }
            const reportTitle = `${__('employee_payroll_report_for_month')} ${new Date(selectedMonth + '-01').toLocaleString('default', { month: 'long', year: 'numeric' })}`;

            // Define the two-part header structure
            const head = [
                [
                    { content: '#', rowSpan: 2, styles: { valign: 'middle', halign: 'center' } },
                    { content: __('emp_id'), rowSpan: 2, styles: { valign: 'middle', halign: 'center' } },
                    { content: __('employee_name'), rowSpan: 2, styles: { valign: 'middle' } },
                    { content: __('salary_payment_type_label'), rowSpan: 2, styles: { valign: 'middle', halign: 'center' } },
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

            // Filter out cash and hold employees for detailed exports
            const filteredReportData = Array.isArray(reportData)
                ? reportData.filter(p => {
                    const pt = parseInt(p.payment_type || 1, 10);
                    return pt !== 2 && pt !== 3; // exclude cash (2) and hold (3)
                })
                : [];

            if (filteredReportData.length === 0) {
                showWarning(__('no_data_available_in_table'), __('no_records_to_export'));
                return;
            }

            // Sort the reportData array by emp_id in ascending order before mapping
            filteredReportData.sort((a, b) => a.emp_id.localeCompare(b.emp_id, undefined, { numeric: true }));

            // Prepare table body with one row per employee, matching the sub-headers
            const body = filteredReportData.map((p, index) => {
                // Format benefits list into a multi-line string with hours/days support
                const benefitsDetails = p.benefits_list && p.benefits_list.length > 0
                    ? p.benefits_list.map(b => {
                        const amount = parseFloat(b.note || 0).toFixed(2);
                        const hoursVal = parseFloat(b.hours || 0);
                        const daysVal = parseFloat(b.days || 0);
                        
                        // Determine format based on calculation type
                        let detailsText = '';
                        
                        if (b.calculation_type === 'by_days') {
                            // Show days and amount
                            if (daysVal > 0) {
                                detailsText = `${daysVal} Days: ${amount}`;
                            } else {
                                detailsText = `${b.benefit || 'Benefit'}: ${amount}`;
                            }
                        } else if (b.calculation_type === 'by_hours' || b.calculation_type === 'overtime_basic' || b.calculation_type === 'overtime_total') {
                            // Show hours and amount
                            if (hoursVal > 0) {
                                detailsText = `${hoursVal} Hours: ${amount}`;
                            } else {
                                detailsText = `${b.benefit || 'Benefit'}: ${amount}`;
                            }
                        } else {
                            // For fixed or other types, show name and amount
                            detailsText = `${b.benefit || 'Benefit'}: ${amount}`;
                        }
                        
                        return detailsText;
                    }).join('\n')
                    : 'N/A';

                // Format deductions list into a multi-line string with hours/days support
                const deductionsDetails = p.deductions_list && p.deductions_list.length > 0
                    ? p.deductions_list.map(d => {
                        const amount = parseFloat(d.note || 0).toFixed(2);
                        const hoursVal = parseFloat(d.hours || 0);
                        const daysVal = parseFloat(d.days || 0);
                        
                        // Determine format based on calculation type
                        let detailsText = '';
                        
                        if (d.calculation_type === 'daily_deduction') {
                            // Show days and amount
                            if (daysVal > 0) {
                                detailsText = `${daysVal} Days: ${amount}`;
                            } else {
                                detailsText = `${d.deduction || 'Deduction'}: ${amount}`;
                            }
                        } else if (d.calculation_type === 'hourly_deduction' || d.calculation_type === 'hourly') {
                            // Show hours and amount
                            if (hoursVal > 0) {
                                detailsText = `${hoursVal} Hours: ${amount}`;
                            } else {
                                detailsText = `${d.deduction || 'Deduction'}: ${amount}`;
                            }
                        } else {
                            // For fixed or other types, show name and amount
                            detailsText = `${d.deduction || 'Deduction'}: ${amount}`;
                        }
                        
                        return detailsText;
                    }).join('\n')
                    : 'N/A';

                // Map numeric payment_type to label for display
                const pt = parseInt(p.payment_type || 1, 10);
                const ptLabelMap = { 1: (__('bank_option') || 'Bank'), 2: (__('cash_option') || 'Cash'), 3: (__('hold_option') || 'Hold') };
                const paymentTypeLabel = ptLabelMap[pt] || String(pt);

                // Return a single array for the table row with all components
                return [
                    index + 1,
                    p.emp_id,
                    p.employee_name,
                    paymentTypeLabel,
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
                    font: arabicFontName || undefined,
                    halign: 'center',
                    fontSize: 8
                },
                styles: {
                    fontSize: 6.5,
                    cellPadding: 3,
                    valign: 'middle',
                    font: arabicFontName || undefined
                },
                columnStyles: {
                    0: { halign: 'center' }, // #
                    1: { halign: 'center' }, // Emp ID
                    3: { halign: 'center' }, // Payment Type
                    // Salary & Allowances Columns (right-aligned)
                    4: { halign: 'right' }, 5: { halign: 'right' }, 6: { halign: 'right' },
                    7: { halign: 'right' }, 8: { halign: 'right' }, 9: { halign: 'right' },
                    10: { halign: 'right' }, 11: { halign: 'right' }, 12: { halign: 'right' },
                    13: { halign: 'right' },
                    14: { halign: 'right', fontStyle: 'bold' }, // Gross Salary
                    // Benefits Columns
                    15: { halign: 'left', valign: 'top', overflow: 'linebreak' }, // Details with line breaks
                    16: { halign: 'right' }, // Total
                    // Deductions Columns
                    17: { halign: 'left', valign: 'top', overflow: 'linebreak' }, // Details with line breaks
                    18: { halign: 'right' }, // Total
                    // Net Salary
                    19: { halign: 'right', fontStyle: 'bold' } 
                },
                didDrawPage: function (data) {
                    // Footer
                    let str = `${__('page_footer')} ` + doc.internal.getNumberOfPages();
                    doc.setFontSize(7);
                    doc.text(str, data.settings.margin.left, doc.internal.pageSize.height - 10);
                }
            });

            // Save the PDF
            const now = new Date();
            const mm = String(now.getMonth() + 1).padStart(2, '0');
            const dd = String(now.getDate()).padStart(2, '0');
            const yy = String(now.getFullYear()).slice(-2);
            const hh = String(now.getHours()).padStart(2, '0');
            const mins = String(now.getMinutes()).padStart(2, '0');
            const ss = String(now.getSeconds()).padStart(2, '0');
            const pdfFilename = `details_payroll_report_${mm}${dd}${yy}${hh}${mins}${ss}.pdf`;
            doc.save(pdfFilename);
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

            // Filter out CASH (payment_type = 2) for Bank Excel
            const bankRows = Array.isArray(reportData)
                ? reportData.filter(p => parseInt(p.payment_type || 1, 10) !== 2)
                : [];

            // 2. Map reportData to the desired row format, converting strings to numbers
            // By processing the data first, we can separate logic from the sheet creation step.
            const dataRows = bankRows.map((p, index) => {
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
            const now = new Date();
            const mm = String(now.getMonth() + 1).padStart(2, '0');
            const dd = String(now.getDate()).padStart(2, '0');
            const yy = String(now.getFullYear()).slice(-2);
            const hh = String(now.getHours()).padStart(2, '0');
            const mins = String(now.getMinutes()).padStart(2, '0');
            const ss = String(now.getSeconds()).padStart(2, '0');
            const fileName = `bank_payroll_${mm}${dd}${yy}${hh}${mins}${ss}.xlsx`;
            XLSX.writeFile(wb, fileName);
        }

        // --- Detailed Excel Export Function (MATCHES PDF REPORT) ---
        async function exportDetailedExcelReport(reportData, selectedMonth, vacationEmployeesData = []) {
            // Ensure the XLSX library is available
            if (typeof XLSX === 'undefined') {
                console.error("The XLSX library (SheetJS) is not loaded. Please include it in your project.");
                showError('Error', 'XLSX library not loaded. Cannot export to Excel.');
                return;
            }

            const payrollRows = Array.isArray(reportData) ? [...reportData] : [];

            // Keep the first/current sheet exactly as before
            const filteredReportData = payrollRows.filter(p => {
                const pt = parseInt(p.payment_type || 1, 10);
                return pt !== 2 && pt !== 3; // exclude cash (2) and hold (3)
            });

            if (filteredReportData.length === 0) {
                showWarning(__('no_data_available_in_table'), __('no_records_to_export'));
                return;
            }

            filteredReportData.sort((a, b) => a.emp_id.localeCompare(b.emp_id, undefined, { numeric: true }));

            const wb = XLSX.utils.book_new();

            const getNumeric = (value) => {
                const parsed = parseFloat(value);
                return Number.isFinite(parsed) ? parsed : 0;
            };

            const getText = (value, fallback = '') => {
                if (value === null || value === undefined || value === '') {
                    return fallback;
                }
                return String(value);
            };

            // 1. Create header rows (current sheet unchanged)
            const headerRow1 = [
                '#', 'Emp ID', 'Employee Name', 'Company', 'Department', 'Payment Type',
                'Basic Salary', 'Housing', 'Transport', 'Food', 'Miscellaneous', 'Cashier', 'Fuel', 'Telephone', 'Other', 'Guard', 'Total Gross',
                'Benefits Details', 'Total Benefits',
                'Deductions Details', 'Total Deductions',
                'Net Salary'
            ];

            const dataRows = filteredReportData.map((p, index) => {
                const benefitsDetails = p.benefits_list && p.benefits_list.length > 0
                    ? p.benefits_list.map(b => {
                        const amount = getNumeric(b.note || 0).toFixed(2);
                        const hoursVal = getNumeric(b.hours || 0);
                        const daysVal = getNumeric(b.days || 0);
                        let detailsText = '';

                        if (b.calculation_type === 'by_days') {
                            detailsText = daysVal > 0 ? `${daysVal} Days: ${amount}` : `${b.benefit || 'Benefit'}: ${amount}`;
                        } else if (b.calculation_type === 'by_hours' || b.calculation_type === 'overtime_basic' || b.calculation_type === 'overtime_total') {
                            detailsText = hoursVal > 0 ? `${hoursVal} Hours: ${amount}` : `${b.benefit || 'Benefit'}: ${amount}`;
                        } else {
                            detailsText = `${b.benefit || 'Benefit'}: ${amount}`;
                        }

                        return detailsText;
                    }).join(' | ')
                    : '';

                const deductionsDetails = p.deductions_list && p.deductions_list.length > 0
                    ? p.deductions_list.map(d => {
                        const amount = getNumeric(d.note || 0).toFixed(2);
                        const hoursVal = getNumeric(d.hours || 0);
                        const daysVal = getNumeric(d.days || 0);
                        let detailsText = '';

                        if (d.calculation_type === 'daily_deduction') {
                            detailsText = daysVal > 0 ? `${daysVal} Days: ${amount}` : `${d.deduction || 'Deduction'}: ${amount}`;
                        } else if (d.calculation_type === 'hourly_deduction' || d.calculation_type === 'hourly') {
                            detailsText = hoursVal > 0 ? `${hoursVal} Hours: ${amount}` : `${d.deduction || 'Deduction'}: ${amount}`;
                        } else {
                            detailsText = `${d.deduction || 'Deduction'}: ${amount}`;
                        }

                        return detailsText;
                    }).join(' | ')
                    : '';

                const pt = parseInt(p.payment_type || 1, 10);
                const ptLabelMap = { 1: 'Bank', 2: 'Cash', 3: 'Hold' };
                const paymentTypeLabel = ptLabelMap[pt] || String(pt);

                return [
                    index + 1,
                    p.emp_id,
                    p.employee_name,
                    p.comp_name || 'N/A',
                    p.department_name || 'N/A',
                    paymentTypeLabel,
                    getNumeric(p.basic_salary),
                    getNumeric(p.housing_allowance),
                    getNumeric(p.transport_allowance),
                    getNumeric(p.food_allowance),
                    getNumeric(p.miscellaneous_allowance),
                    getNumeric(p.cashier_allowance),
                    getNumeric(p.fuel_allowance),
                    getNumeric(p.telephone_allowance),
                    getNumeric(p.other_allowance),
                    getNumeric(p.guard_allowance),
                    getNumeric(p.total_gross_salary),
                    benefitsDetails,
                    getNumeric(p.total_benefits),
                    deductionsDetails,
                    getNumeric(p.total_deductions),
                    getNumeric(p.net_salary)
                ];
            });

            const allRows = [headerRow1, ...dataRows];
            const ws = XLSX.utils.aoa_to_sheet(allRows);

            const colWidths = [
                { wch: 5 }, { wch: 10 }, { wch: 25 }, { wch: 20 }, { wch: 20 }, { wch: 14 },
                { wch: 12 }, { wch: 10 }, { wch: 10 }, { wch: 10 }, { wch: 12 }, { wch: 10 },
                { wch: 10 }, { wch: 10 }, { wch: 10 }, { wch: 10 }, { wch: 12 }, { wch: 40 },
                { wch: 12 }, { wch: 40 }, { wch: 14 }, { wch: 12 }
            ];
            ws['!cols'] = colWidths;

            const numberFormat = '#,##0.00';
            const numericColumns = ['G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'S', 'U', 'V'];

            for (let i = 1; i <= dataRows.length; i++) {
                numericColumns.forEach(colLetter => {
                    const cellAddress = colLetter + (i + 1);
                    if (ws[cellAddress]) {
                        ws[cellAddress].z = numberFormat;
                    }
                });

                const rCell = 'R' + (i + 1);
                const tCell = 'T' + (i + 1);

                if (ws[rCell] && ws[rCell].v) {
                    ws[rCell].s = {
                        alignment: { horizontal: 'left', vertical: 'top', wrapText: true, shrinkToFit: false },
                        font: { size: 10 }
                    };
                }

                if (ws[tCell] && ws[tCell].v) {
                    ws[tCell].s = {
                        alignment: { horizontal: 'left', vertical: 'top', wrapText: true, shrinkToFit: false },
                        font: { size: 10 }
                    };
                }
            }

            const headerRange = XLSX.utils.decode_range(ws['!ref']);
            for (let col = headerRange.s.c; col <= headerRange.e.c; col++) {
                const cellAddress = XLSX.utils.encode_cell({ r: 0, c: col });
                if (!ws[cellAddress]) continue;

                ws[cellAddress].s = {
                    font: { bold: true, color: { rgb: 'FFFFFF' } },
                    fill: { fgColor: { rgb: '2980B9' } },
                    alignment: { horizontal: 'center', vertical: 'center', wrapText: true }
                };
            }

            XLSX.utils.book_append_sheet(wb, ws, 'Detailed Payroll Report');

            // Additional sheets start here (sheet 2 to 6)
            const toMonthValue = (dateValue) => {
                if (!dateValue) {
                    return '';
                }

                const rawValue = String(dateValue).trim();
                const directMatch = rawValue.match(/^(\d{4})[-/](\d{2})/);
                if (directMatch) {
                    return `${directMatch[1]}-${directMatch[2]}`;
                }

                const parsedTimestamp = Date.parse(rawValue.replace(' ', 'T'));
                if (Number.isNaN(parsedTimestamp)) {
                    return '';
                }

                const parsedDate = new Date(parsedTimestamp);
                return `${parsedDate.getFullYear()}-${String(parsedDate.getMonth() + 1).padStart(2, '0')}`;
            };

            const isSamePayrollMonth = (dateValue) => toMonthValue(dateValue) === selectedMonth;

            const formatDateValue = (dateValue) => {
                if (!dateValue) {
                    return '';
                }

                const parsedDate = new Date(String(dateValue).replace(' ', 'T'));
                if (Number.isNaN(parsedDate.getTime())) {
                    return String(dateValue);
                }

                return parsedDate.toLocaleDateString('en-GB');
            };

            const getPaymentTypeLabel = (paymentType) => {
                const typeId = parseInt(paymentType || 1, 10);
                const paymentTypeLabels = {
                    1: __('bank_option') || 'Bank',
                    2: __('cash_option') || 'Cash',
                    3: __('hold_option') || 'Hold'
                };
                return paymentTypeLabels[typeId] || String(paymentType || '');
            };

            const getPayrollStatusLabel = (status) => {
                const normalized = String(status || '').toLowerCase();
                if (!normalized) {
                    return __('skipped') || 'Skipped';
                }

                const statusMap = {
                    generated: __('generated_badge') || 'Generated',
                    paid: __('paid_badge') || 'Paid',
                    hold: __('payroll_on_hold') || 'On Hold',
                    skipped: __('skipped') || 'Skipped'
                };

                return statusMap[normalized] || `${normalized.charAt(0).toUpperCase()}${normalized.slice(1)}`;
            };

            const getEmployeeName = (item) => getText(item?.employee_name || item?.name, 'N/A');

            const uniqueByEmpId = (items) => {
                const map = new Map();
                (Array.isArray(items) ? items : []).forEach(item => {
                    if (!item || !item.emp_id) {
                        return;
                    }

                    const key = String(item.emp_id);
                    const existing = map.get(key) || {};
                    map.set(key, { ...existing, ...item });
                });
                return Array.from(map.values());
            };

            const sortByEmpId = (items) => uniqueByEmpId(items).sort((a, b) => (
                getText(a.emp_id).localeCompare(getText(b.emp_id), undefined, { numeric: true, sensitivity: 'base' })
            ));

            let employeesForMonth = [];
            try {
                const response = await fetch(`./includes/api/get_employees.php?month=${encodeURIComponent(selectedMonth)}`);
                if (response.ok) {
                    const data = await response.json();
                    if (data.status === 'success' && Array.isArray(data.employees)) {
                        employeesForMonth = data.employees;
                    }
                }
            } catch (error) {
                console.warn('Unable to load supplemental employee data for Detailed Excel export:', error);
            }

            const mergedMap = new Map();
            payrollRows.forEach(item => {
                if (!item || !item.emp_id) {
                    return;
                }
                mergedMap.set(String(item.emp_id), { ...item });
            });

            employeesForMonth.forEach(item => {
                if (!item || !item.emp_id) {
                    return;
                }

                const key = String(item.emp_id);
                const existing = mergedMap.get(key) || {};
                mergedMap.set(key, {
                    ...existing,
                    ...item,
                    employee_name: item.name || existing.employee_name || ''
                });
            });

            const mergedEmployees = sortByEmpId(Array.from(mergedMap.values()));
            const onHoldEmployees = sortByEmpId(mergedEmployees.filter(emp => parseInt(emp.payment_type || 1, 10) === 3));
            const vacationEmployees = sortByEmpId(
                (Array.isArray(vacationEmployeesData) && vacationEmployeesData.length > 0)
                    ? vacationEmployeesData.filter(emp => {
                        const vacationTypeText = `${String(emp.vacation_type || '')} ${String(emp.vacation_note || '')}`.toLowerCase();
                        return !vacationTypeText.includes('excuse');
                    })
                    : mergedEmployees.filter(emp => {
                        const vacationStatus = String(emp.vacation_status || '').toLowerCase();
                        const vacationTypeText = `${String(emp.vacation_type || '')} ${String(emp.vacation_note || '')}`.toLowerCase();
                        return !vacationTypeText.includes('excuse') && (
                            ['approved', 'completed'].includes(vacationStatus)
                            || Boolean(emp.vacation_start_date || emp.vacation_return_date || emp.vacation_type)
                        );
                    })
            );
            const cashEmployees = sortByEmpId(mergedEmployees.filter(emp => parseInt(emp.payment_type || 1, 10) === 2));
            const newJoiners = sortByEmpId(mergedEmployees.filter(emp => isSamePayrollMonth(emp.joining_date)));
            const terminatedOrLeaveEmployees = sortByEmpId(payrollRows.filter(emp => {
                const employeeStatus = String(emp.employee_status ?? '').trim();
                return employeeStatus === '0' && isSamePayrollMonth(emp.last_working_day);
            }));

            const appendSummarySheet = (sheetName, items) => {
                const headers = [
                    '#', 'Emp ID', 'Employee Name', 'Company', 'Department', 'Joining Date', 'Payment Type', 'Payroll Status',
                    'Vacation Type', 'Vacation Start', 'Vacation Return', 'Last Working Day', 'Resignation Status', 'Net Salary', 'Salary', 'Sponsor'
                ];

                const rows = items.length > 0
                    ? items.map((item, index) => [
                        index + 1,
                        getText(item.emp_id),
                        getEmployeeName(item),
                        getText(item.comp_name, 'N/A'),
                        getText(item.department_name, 'N/A'),
                        formatDateValue(item.joining_date),
                        getPaymentTypeLabel(item.payment_type),
                        getPayrollStatusLabel(item.payroll_status || item.status),
                        getText(item.vacation_type),
                        formatDateValue(item.vacation_start_date),
                        formatDateValue(item.vacation_return_date),
                        formatDateValue(item.last_working_day),
                        getText(item.resignation_status),
                        getNumeric(item.net_salary),
                        getNumeric(item.salary || item.total_gross_salary),
                        getText(item.sponsor)
                    ])
                    : [[`No records found for ${selectedMonth}`, '', '', '', '', '', '', '', '', '', '', '', '', '', '', '']];

                const worksheet = XLSX.utils.aoa_to_sheet([headers, ...rows]);
                worksheet['!cols'] = [
                    { wch: 5 }, { wch: 10 }, { wch: 24 }, { wch: 18 }, { wch: 18 }, { wch: 12 }, { wch: 12 }, { wch: 12 },
                    { wch: 16 }, { wch: 12 }, { wch: 12 }, { wch: 13 }, { wch: 14 }, { wch: 12 }, { wch: 12 }, { wch: 18 }
                ];

                const numericIndexes = [13, 14];
                for (let rowIndex = 1; rowIndex <= rows.length; rowIndex++) {
                    numericIndexes.forEach(columnIndex => {
                        const cellAddress = XLSX.utils.encode_cell({ r: rowIndex, c: columnIndex });
                        if (worksheet[cellAddress] && typeof worksheet[cellAddress].v === 'number') {
                            worksheet[cellAddress].z = '#,##0.00';
                        }
                    });
                }

                const range = XLSX.utils.decode_range(worksheet['!ref']);
                for (let col = range.s.c; col <= range.e.c; col++) {
                    const cellAddress = XLSX.utils.encode_cell({ r: 0, c: col });
                    if (!worksheet[cellAddress]) continue;
                    worksheet[cellAddress].s = {
                        font: { bold: true, color: { rgb: 'FFFFFF' } },
                        fill: { fgColor: { rgb: '1D4ED8' } },
                        alignment: { horizontal: 'center', vertical: 'center', wrapText: true }
                    };
                }

                XLSX.utils.book_append_sheet(wb, worksheet, sheetName);
            };

            appendSummarySheet('On Hold Employees', onHoldEmployees);
            appendSummarySheet('On Vacation', vacationEmployees);
            appendSummarySheet('Cash Employees', cashEmployees);
            appendSummarySheet('New Joiners', newJoiners);
            appendSummarySheet('Terminated or Left', terminatedOrLeaveEmployees);

            const now = new Date();
            const mm = String(now.getMonth() + 1).padStart(2, '0');
            const dd = String(now.getDate()).padStart(2, '0');
            const yy = String(now.getFullYear()).slice(-2);
            const hh = String(now.getHours()).padStart(2, '0');
            const mins = String(now.getMinutes()).padStart(2, '0');
            const ss = String(now.getSeconds()).padStart(2, '0');
            const fileName = `details_payroll_report_${mm}${dd}${yy}${hh}${mins}${ss}.xlsx`;
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
                    updatePayrollMonthLabel(data.months[0].value);
                    // Crucially, call fetchEmployees here to load data for the initially selected month
                    fetchEmployees(); 
                    $('#actionGenerateReportBtn').prop('disabled', false); // Enable report button
                } else {
                    // If no months are available, show a message and disable the report button
                    monthSelect.append($('<option>', {
                        value: '',
                        text: 'No months available for payroll',
                        disabled: true,
                        selected: true
                    }));
                    $('#actionGenerateReportBtn').prop('disabled', true);
                    showInfo('No Payroll Months', 'No generated payroll months found. Please generate payrolls first.');
                }
            } catch (error) {
                console.error('Error loading available months for main page:', error);
                showError('Error', 'Could not load available months for the main filter: ' + error.message);
                $('#actionGenerateReportBtn').prop('disabled', true); // Disable button on error
            }
        }

        async function updatePayrollStatus(payrollIds, status, successCallback = null) {
            if (!payrollIds || payrollIds.length === 0) {
                showWarning(__('no_records_selected_warning_title'), __('please_select_one_record_to_update_warning'));
                return;
            }
            const confirmation = await Swal.fire({
                title: __('mark_records_as_status_q_title').replace('{0}', payrollIds.length).replace('{1}', __(status)),
                text: __('action_cannot_be_undone'),
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                confirmButtonText: __('yes_mark_as_status_button').replace('{0}', __(status)),
                allowOutsideClick: false,
                cancelButtonColor: '#d33',
                cancelButtonText: __('cancel')
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
                    Swal.fire({ 
                        icon: 'success', 
                        title: __('status_updated_success_title'), 
                        text: result.message, 
                        allowOutsideClick: false, 
                        confirmButtonText: __('ok') 
                    }).then(() => successCallback && successCallback());
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
