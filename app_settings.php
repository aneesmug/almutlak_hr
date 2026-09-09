<?php
    require_once __DIR__ . '/includes/session_check.php';
    require_once __DIR__ . '/includes/special_access_helper.php';
    $canAccessDepartmentsTab = $is_system_admin || user_has_special_access($conDB, $empid ?? '', 'manage_department_settings', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
    $canAccessJobTitlesTab = $is_system_admin || user_has_special_access($conDB, $empid ?? '', 'manage_job_title_settings', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
    $canAccessLocationsTab = $is_system_admin || user_has_special_access($conDB, $empid ?? '', 'manage_location_settings', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
    $canAccessSubDepartmentsTab = $is_system_admin || user_has_special_access($conDB, $empid ?? '', 'manage_sub_department_settings', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
    $canAccessRequestBlocksTab = $is_system_admin || user_has_special_access($conDB, $empid ?? '', 'manage_global_request_blocks', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
    $canAccessLoanSettingsTab = $is_system_admin || user_has_special_access($conDB, $empid ?? '', 'manage_loan_settings', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
    $canAccessVacationPayrollTab = $is_system_admin || user_has_special_access($conDB, $empid ?? '', 'manage_vacation_payroll_settings', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
    $canAccessOvertimeSettingsTab = $is_system_admin || user_has_special_access($conDB, $empid ?? '', 'manage_overtime_settings', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
    $canAccessDeductionSettingsTab = $is_system_admin || user_has_special_access($conDB, $empid ?? '', 'manage_deduction_settings', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
    $canAccessSalaryIncrementSettingsTab = $is_system_admin || user_has_special_access($conDB, $empid ?? '', 'manage_salary_increment_settings', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
    $canAccessAttendanceConfigTab = $is_system_admin || user_has_special_access($conDB, $empid ?? '', 'manage_attendance_config', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
    $query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
    if(mysqli_num_rows($query) == 1){
        include("./includes/avatar_select.php");
    }
?>
<!DOCTYPE html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>
<head>
    <meta charset="utf-8" />
    <title><?=$site_title ?? __('Application Settings'); ?> - <?= __('App Settings') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="Anees Afzal" name="author" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

    <!-- App favicon -->
    <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">

    <!-- Plugins -->
    <link href="./plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
    <link href="./plugins/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
    <link href="./plugins/clockpicker/css/bootstrap-clockpicker.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        /* Keep the date-range calendars side-by-side inside SweetAlert modals - same override manage_holidays.php uses */
        .swal2-container .daterangepicker { z-index: 2200 !important; min-width: 650px; }
        .swal2-container .daterangepicker .drp-calendar { max-width: none; }
        .swal2-container .daterangepicker.show-calendar .drp-calendar.left,
        .swal2-container .daterangepicker.show-calendar .drp-calendar.right { display: inline-block; float: none; vertical-align: top; }
        @media (max-width: 767px) {
            .swal2-container .daterangepicker { min-width: 0; width: 100%; }
        }
    </style>

    <!-- App css -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />

    <script src="assets/js/modernizr.min.js"></script>
    <style>
        .loader {
            border: 4px solid #f3f3f3;
            border-radius: 50%;
            border-top: 4px solid #4fa0e3;
            width: 40px;
            height: 40px;
            animation: spin 2s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .nav-pills .nav-link.active, .nav-pills .show>.nav-link {
            color: #fff;
            background-color: #4fa0e3;
        }
        .preview-image {
            max-height: 50px;
            max-width: 150px;
            border: 1px solid #ddd;
            padding: 5px;
            border-radius: 4px;
            background-color: #f8f9fa;
        }

        /* --- Settings Form Layout --- */
        #settings-container {
            min-height: 300px;
            max-height: 65vh;
            overflow-y: auto;
            overflow-x: hidden;
            padding-right: 10px;
        }
        
        #settings-container .tab-pane {
            display: block !important;
            opacity: 1 !important;
        }
        
        /* Ensure scrollbar styling */
        #settings-container::-webkit-scrollbar {
            width: 8px;
        }
        
        #settings-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        #settings-container::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }
        
        #settings-container::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* --- Approval Chain Styles --- */
        .approval-chain-container {
            min-height: 60px;
        }
        .approval-step {
            cursor: move;
            transition: all 0.2s ease;
        }
        .approval-step:hover {
            background-color: #f0f8ff !important;
            border-color: #4fa0e3 !important;
        }
        .approval-step.dragging {
            opacity: 0.4;
        }
        .approval-steps {
            position: relative;
        }
        .approval-step .badge {
            font-size: 0.75rem;
            padding: 0.35em 0.6em;
        }

        /* --- Select2 Bootstrap 4 Style Fixes --- */
        .select2-container {
            width: 100% !important;
        }
        .select2-container .select2-selection--single {
            height: 38px !important; /* Match Bootstrap's form-control height */
            border: 1px solid #ced4da;
            border-radius: .25rem;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px; /* Vertically center text */
            padding-left: .75rem;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
            right: 5px;
        }

        .select2-dropdown {
             border: 1px solid #ced4da;
             border-radius: .25rem;
             z-index: 1050; /* Ensure dropdown appears above other content */
        }

        /* Special Access - grouped-by-category checkbox grid (used both inline and inside the Swal edit modal) */
        .special-access-category {
            border: 1px solid #e9ecef;
            border-radius: .5rem;
            overflow: hidden;
            background: #fff;
        }
        .special-access-category-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #f4f6f9;
            padding: .55rem .9rem;
            border-bottom: 1px solid #e9ecef;
            font-size: .92rem;
        }
        .special-access-category-header i {
            color: #4fa0e3;
            width: 18px;
            text-align: center;
        }
        .special-access-category-body {
            padding: .75rem .9rem .25rem;
        }
        /* CSS grid instead of Bootstrap's .row/.col-* - avoids the negative-margin
           overflow that .row causes inside a constrained container like a Swal popup. */
        .special-access-checkbox-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: .35rem 1rem;
        }
        .special-access-item {
            min-width: 0;
        }
        .special-access-item .custom-control-label {
            word-break: break-word;
        }
        .special-access-cat-count {
            font-weight: 600;
            transition: background-color .15s ease, color .15s ease;
        }
        /* Special Access edit modal - sidebar-tab layout (replaces the old single long
           scrolling stack of every category, which made a specific setting hard to find). */
        .sae-layout {
            display: flex;
            border: 1px solid #e9ecef;
            border-radius: .5rem;
            overflow: hidden;
        }
        .sae-sidebar {
            width: 240px;
            flex: 0 0 240px;
            background: #f8f9fb;
            border-right: 1px solid #e9ecef;
            overflow-y: auto;
            max-height: 55vh;
        }
        .sae-tab {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
            padding: .6rem .9rem;
            cursor: pointer;
            font-size: .84rem;
            border-left: 3px solid transparent;
            color: #495057;
        }
        .sae-tab:hover {
            background: #eef1f5;
        }
        .sae-tab.active {
            background: #fff;
            border-left-color: #4fa0e3;
            color: #1c1c1c;
            font-weight: 600;
        }
        .sae-tab span:first-child {
            display: flex;
            align-items: center;
            gap: .5rem;
            min-width: 0;
        }
        .sae-tab span:first-child i {
            width: 16px;
            text-align: center;
            color: #4fa0e3;
            flex-shrink: 0;
        }
        .sae-content {
            flex: 1;
            min-width: 0;
            overflow-y: auto;
            max-height: 55vh;
            padding: .9rem 1.1rem;
        }
        .sae-panel {
            display: none;
        }
        .sae-panel.sae-panel-visible {
            display: block;
        }
        .sae-panel-title {
            font-size: .95rem;
            font-weight: 600;
            margin-bottom: .75rem;
            color: #343a40;
        }
        .sae-search-hit-cat {
            font-size: .7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .03em;
            color: #8a94a6;
            margin: 1rem 0 .4rem;
            padding-bottom: .25rem;
            border-bottom: 1px dashed #e9ecef;
        }
        .sae-search-hit-cat:first-child {
            margin-top: 0;
        }
        .sae-empty-state {
            color: #8a94a6;
            text-align: center;
            padding: 2.5rem 1rem;
            font-size: .85rem;
        }
        .special-access-user-card {
            border: 1px solid #e9ecef;
            border-left: 3px solid #4fa0e3;
            border-radius: .5rem;
            padding: .9rem 1rem;
            margin-bottom: .75rem;
            background: #fff;
            box-shadow: 0 1px 3px rgba(15, 23, 42, .04);
            transition: box-shadow .15s ease;
        }
        .special-access-user-card:hover {
            box-shadow: 0 4px 12px rgba(15, 23, 42, .08);
        }
        .special-access-empty-state {
            text-align: center;
            padding: 2rem 1rem;
            color: #8792a2;
        }
        .special-access-empty-state i {
            font-size: 2rem;
            margin-bottom: .5rem;
            display: block;
            color: #c3cad6;
        }
        #special-access-panel {
            position: relative;
        }
        #special-access-loading-overlay {
            position: absolute;
            inset: 0;
            background: rgba(255, 255, 255, .75);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 10;
            border-radius: .5rem;
        }
        .special-access-group-label {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #8792a2;
            font-weight: 700;
            margin: .4rem 0 .25rem;
        }
        .special-access-group-label:first-child {
            margin-top: 0;
        }
        /* Distinguishes Report Access badges (a different underlying map) from ability
           badges (badge-info/blue) at a glance in the assigned-users list. */
        .badge-purple {
            background-color: #7c5cf0;
            color: #fff;
        }
    </style>
    <?php if ($is_rtl): ?>
        <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
    <?php endif; ?>
    <script>
        window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;
        // Per-request permission flags assets/js/app_settings.js needs but can't compute
        // itself (it's a genuinely static file) - kept to just booleans, nothing sensitive.
        window.APP_SETTINGS_PERMISSIONS = <?= json_encode([
            'isFullSettingsAdmin' => (bool) $is_system_admin,
            'canAccessDepartmentsTab' => (bool) $canAccessDepartmentsTab,
            'canAccessJobTitlesTab' => (bool) $canAccessJobTitlesTab,
            'canAccessLocationsTab' => (bool) $canAccessLocationsTab,
            'canAccessSubDepartmentsTab' => (bool) $canAccessSubDepartmentsTab,
            'canAccessRequestBlocksTab' => (bool) $canAccessRequestBlocksTab,
            'canAccessLoanSettingsTab' => (bool) $canAccessLoanSettingsTab,
            'canAccessVacationPayrollTab' => (bool) $canAccessVacationPayrollTab,
            'canAccessOvertimeSettingsTab' => (bool) $canAccessOvertimeSettingsTab,
            'canAccessDeductionSettingsTab' => (bool) $canAccessDeductionSettingsTab,
            'canAccessSalaryIncrementSettingsTab' => (bool) $canAccessSalaryIncrementSettingsTab,
            'canAccessAttendanceConfigTab' => (bool) $canAccessAttendanceConfigTab,
        ]) ?>;
    </script>
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
                        <span><img src="<?=get_setting($conDB, 'logo')?>" alt="" height="22"></span>
                        <i><img src="<?=get_setting($conDB, 'white_logo')?>" alt="" height="28"></i>
                    </a>
                </div>
                <!--- Sidemenu -->
                <?php include("./includes/main_menu.php"); ?>
                <!-- Sidebar -->
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
                                <h4 class="m-t-0 header-title"><?= __("application_settings") ?></h4>
                                <p class="text-muted m-b-30 font-14"><?= __("manage_your_application_s_configuration") ?></p>

                                <form id="settingsForm">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <ul id="settings-nav" class="nav nav-pills flex-column" role="tablist">
                                                <!-- Nav items will be injected here by JavaScript -->
                                                <div class="d-flex justify-content-center align-items-center" style="height: 100px;">
                                                    <div class="loader"></div>
                                                </div>
                                            </ul>
                                        </div>
                                        <div class="col-md-9">
                                            <div id="settings-container" class="tab-content p-3 border">
                                                <!-- Tab content will be injected here by JavaScript -->
                                                <div class="d-flex justify-content-center align-items-center" style="height: 200px;">
                                                    <div class="loader"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Persistent (not tab-content) so it survives switching to another
                                         settings tab before Save - see loadSettings()/renderSpecialAccessSettings(). -->
                                    <input type="hidden" id="setting-special_access_by_user" name="special_access_by_user" value="{}">
                                    <!-- Report access is now managed from inside the Special Access tab too (see
                                         "Report Access" group in renderSpecialAccessSettings) instead of its own tab,
                                         so this hidden field must survive tab switches the same way. -->
                                    <input type="hidden" id="setting-report_visibility_by_user" name="report_visibility_by_user" value="{}">

                                    <div class="form-group text-right m-t-20" id="saveBtnWrapper">
                                        <button type="submit" id="saveBtn" class="btn btn-primary waves-effect waves-light">
                                            <?= __("save_changes") ?>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div> <!-- container -->
            </div> <!-- content -->

            <footer class="footer">
                <?=$site_footer ?>
            </footer>
        </div>
    </div>
    <!-- END wrapper -->

    <!-- jQuery  -->
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/metisMenu.min.js"></script>
    <script src="assets/js/waves.js"></script>
    <script src="assets/js/jquery.slimscroll.js"></script>

    <!-- Plugins -->
    <script src="./plugins/select2/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- App js -->
    <script src="assets/js/jquery.core.js"></script>
    <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>

    <script>
    // Debug net: if the big settings script below fails to parse/run (JS error),
    // the nav/container spinners spin forever with no visible cause. Surface it.
    window.addEventListener('error', function(e) {
        var msg = 'JS error: ' + e.message + ' (' + e.filename + ':' + e.lineno + ':' + e.colno + ')';
        console.error(msg, e.error);
        var nav = document.getElementById('settings-nav');
        var container = document.getElementById('settings-container');
        if (nav) nav.innerHTML = '<li class="text-danger p-2" style="font-size:12px;white-space:pre-wrap;">' + msg.replace(/</g, '&lt;') + '</li>';
        if (container) container.innerHTML = '<pre class="text-danger p-2" style="white-space:pre-wrap;">' + msg.replace(/</g, '&lt;') + '</pre>';
    });
    </script>
    <script src="assets/js/app_settings.js?v=<?= filemtime(__DIR__ . '/assets/js/app_settings.js') ?>"></script>

</body>
</html>