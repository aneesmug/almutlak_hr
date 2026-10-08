<?php
    require_once __DIR__ . '/includes/session_check.php';
    require_once __DIR__ . '/includes/special_access_helper.php';
    require_once __DIR__ . '/includes/screen_settings_helper.php';
    $canAccessDepartmentsTab = $is_system_admin || user_has_special_access($conDB, $empid ?? '', 'manage_department_settings', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
    $canAccessJobTitlesTab = $is_system_admin || user_has_special_access($conDB, $empid ?? '', 'manage_job_title_settings', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
    $canAccessLocationsTab = $is_system_admin || user_has_special_access($conDB, $empid ?? '', 'manage_location_settings', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
    $canAccessCompaniesTab = $is_system_admin || user_has_special_access($conDB, $empid ?? '', 'manage_company_settings', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
    $canAccessSubDepartmentsTab = $is_system_admin || user_has_special_access($conDB, $empid ?? '', 'manage_sub_department_settings', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
    $canAccessRequestBlocksTab = $is_system_admin || user_has_special_access($conDB, $empid ?? '', 'manage_global_request_blocks', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
    $canAccessLoanSettingsTab = $is_system_admin || user_has_special_access($conDB, $empid ?? '', 'manage_loan_settings', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
    $canAccessVacationPayrollTab = $is_system_admin || user_has_special_access($conDB, $empid ?? '', 'manage_vacation_payroll_settings', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
    $canAccessOvertimeSettingsTab = $is_system_admin || user_has_special_access($conDB, $empid ?? '', 'manage_overtime_settings', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
    $canAccessDeductionSettingsTab = $is_system_admin || user_has_special_access($conDB, $empid ?? '', 'manage_deduction_settings', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
    $canAccessSalaryIncrementSettingsTab = $is_system_admin || user_has_special_access($conDB, $empid ?? '', 'manage_salary_increment_settings', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
    $canAccessResignationSettingsTab = $is_system_admin || user_has_special_access($conDB, $empid ?? '', 'manage_resignation_settings', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
    $canAccessAttendanceConfigTab = $is_system_admin || user_has_special_access($conDB, $empid ?? '', 'manage_attendance_config', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
    // Screen Settings tab: system admins get the full per-user table; a plain user
    // granted 'manage_own_screen_settings' only ever sees/edits their own single row
    // (see canManageOwnScreenSettings in the JS permissions payload below).
    $canAccessScreenSettingsTab = $is_system_admin || user_has_special_access($conDB, $empid ?? '', 'manage_own_screen_settings', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
    $ownScreenSettings = $canAccessScreenSettingsTab ? get_user_screen_settings($conDB, $empid ?? '') : null;
    $canAccessVacationBlackoutTab = $is_system_admin || user_has_special_access($conDB, $empid ?? '', 'manage_vacation_blackout_dates', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
    $canAccessTempRoleTransferTab = $is_system_admin || user_has_special_access($conDB, $empid ?? '', 'manage_temp_role_transfer', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
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
    <link href="./plugins/clockpicker/css/bootstrap-clockpicker.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
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
        /* Page Access sub-group headers - deliberately bigger/bolder/colored than
           .special-access-group-label above (that one's small-muted style, used for the
           assigned-users summary badges, was unreadable when reused for these). */
        .page-access-group-label {
            font-size: .95rem;
            font-weight: 700;
            color: #4fa0e3;
            margin: 1.1rem 0 .5rem;
            padding-bottom: .3rem;
            border-bottom: 2px solid #e3eefb;
        }
        .page-access-group-label:first-child {
            margin-top: 0;
        }
        .page-access-group-label i {
            width: 20px;
            text-align: center;
        }
        /* Distinguishes Report Access badges (a different underlying map) from ability
           badges (badge-info/blue) at a glance in the assigned-users list. */
        .badge-purple {
            background-color: #7c5cf0;
            color: #fff;
        }
    </style>
    <link href="assets/css/smart_request.css?v=<?= @filemtime(__DIR__ . '/assets/css/smart_request.css') ?>" rel="stylesheet" type="text/css" />
    <style>
        /* ---------- New GUI shell (sr-* design system). The tab contents are built by
           assets/js/app_settings.js with Bootstrap markup, so they are re-skinned here. ---------- */
        .sr-page .loader { border-color: var(--sr-surface-3); border-top-color: var(--sr-accent); }
        .as-layout { display: grid; grid-template-columns: 260px minmax(0, 1fr); gap: 16px; align-items: start; }
        @media (max-width: 991px) { .as-layout { grid-template-columns: 1fr; } }
        .as-nav-card { position: sticky; top: 86px; padding: 10px; max-height: calc(100vh - 110px); overflow-y: auto; }
        @media (max-width: 991px) { .as-nav-card { position: static; max-height: 320px; } }
        .as-nav-search { max-width: none; margin-bottom: 8px; }
        .as-nav-search input { height: 34px; }
        .sr-page #settings-nav { gap: 2px; }
        .sr-page #settings-nav .nav-link {
            display: flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 9px;
            font-size: 13px; font-weight: 600; color: var(--sr-text-2); background: transparent; transition: background .15s, color .15s;
        }
        .sr-page #settings-nav .nav-link:hover { background: var(--sr-surface-3); color: var(--sr-text); }
        .sr-page #settings-nav .nav-link.active { background: var(--sr-accent-soft); color: var(--sr-accent-strong); box-shadow: inset 3px 0 0 var(--sr-accent); }
        [dir="rtl"] .sr-page #settings-nav .nav-link.active { box-shadow: inset -3px 0 0 var(--sr-accent); }
        .as-main-card { padding: 18px 20px; min-width: 0; }
        .sr-page #settings-container { min-height: 300px; max-height: none; overflow: visible; padding: 0; border: 0; }

        /* Sub-tab pills inside a settings tab */
        .sr-page #settings-container .nav-pills:not(.flex-column),
        .sr-page #settings-container .nav-tabs {
            display: flex; flex-wrap: wrap; gap: 4px; padding: 6px; margin-bottom: 16px !important;
            border: 1px solid var(--sr-border); border-radius: 12px; background: var(--sr-surface-2);
        }
        .sr-page #settings-container .nav-pills:not(.flex-column) .nav-link,
        .sr-page #settings-container .nav-tabs .nav-link {
            border: 0; border-radius: 8px; padding: 7px 14px; font-size: 13px; font-weight: 600; color: var(--sr-muted); background: transparent;
        }
        .sr-page #settings-container .nav-pills:not(.flex-column) .nav-link:hover,
        .sr-page #settings-container .nav-tabs .nav-link:hover { color: var(--sr-text); background: var(--sr-surface-3); }
        .sr-page #settings-container .nav-pills:not(.flex-column) .nav-link.active,
        .sr-page #settings-container .nav-tabs .nav-link.active { color: var(--sr-accent-strong); background: var(--sr-surface); box-shadow: var(--sr-shadow); }

        /* Forms */
        .sr-page #settings-container label { font-size: 12.5px; font-weight: 600; color: var(--sr-text-2); }
        .sr-page #settings-container .form-control,
        .sr-page #settings-container .custom-select {
            min-height: 38px; border-radius: 10px; font-size: 13px;
            border: 1px solid var(--sr-border-strong); background-color: var(--sr-surface-2); color: var(--sr-text);
        }
        .sr-page #settings-container .form-control:focus,
        .sr-page #settings-container .custom-select:focus { background-color: var(--sr-surface); border-color: var(--sr-accent); box-shadow: 0 0 0 3px rgba(99, 102, 241, .16); }
        .sr-page #settings-container textarea.form-control { min-height: 76px; }
        .sr-page #settings-container .form-text, .sr-page #settings-container small.text-muted { color: var(--sr-muted) !important; font-size: 11.5px; }
        .sr-page #settings-container .custom-control-input:checked ~ .custom-control-label::before { background-color: var(--sr-accent); border-color: var(--sr-accent); }
        .sr-page #settings-container h4, .sr-page #settings-container h5, .sr-page #settings-container h6 { color: var(--sr-text); font-weight: 700; }
        .sr-page #settings-container hr { border-color: var(--sr-border); }
        .sr-page .select2-container .select2-selection--single,
        .sr-page .select2-container--default .select2-selection--multiple { border-color: var(--sr-border-strong) !important; border-radius: 10px !important; background: var(--sr-surface-2); }

        /* Cards / tables built by the JS */
        .sr-page #settings-container .card { border: 1px solid var(--sr-border); border-radius: 12px; box-shadow: none; background: var(--sr-surface); overflow: hidden; }
        .sr-page #settings-container .card-header { background: var(--sr-surface-2) !important; border-bottom: 1px solid var(--sr-border); font-weight: 700; color: var(--sr-text); }
        .sr-page #settings-container .table { color: var(--sr-text-2); margin-bottom: 0; }
        .sr-page #settings-container .table thead th {
            border: 0 !important; border-bottom: 1px solid var(--sr-border) !important; background: var(--sr-surface-2);
            color: var(--sr-muted); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; white-space: nowrap;
        }
        .sr-page #settings-container .table td { border-color: var(--sr-border) !important; vertical-align: middle; font-size: 13px; }
        .sr-page #settings-container .table-bordered { border-color: var(--sr-border); }
        .sr-page #settings-container .table-hover tbody tr:hover { background: var(--sr-surface-2); }
        .sr-page #settings-container .table-responsive { border: 1px solid var(--sr-border); border-radius: 12px; }

        /* Buttons */
        .sr-page #settings-container .btn { border-radius: 9px; font-weight: 600; }
        .sr-page #settings-container .btn-primary { background: var(--sr-accent); border-color: var(--sr-accent); }
        .sr-page #settings-container .btn-primary:hover { background: var(--sr-accent-strong); border-color: var(--sr-accent-strong); }
        .sr-page #settings-container .btn-outline-primary { color: var(--sr-accent-strong); border-color: var(--sr-accent); }
        .sr-page #settings-container .btn-outline-primary:hover { background: var(--sr-accent); color: #fff; }
        /* Button groups (Edit/Delete, Select All/Clear All, ...): the 9px radius above beats Bootstrap's
           .btn-group rules, so re-join them - only the outer corners stay rounded */
        .sr-page #settings-container .btn-group > .btn:hover, .sr-page #settings-container .btn-group > .btn:focus { z-index: 1; }
        .sr-page #settings-container .btn-group > .btn:not(:last-child) { border-top-right-radius: 0; border-bottom-right-radius: 0; }
        .sr-page #settings-container .btn-group > .btn:not(:first-child) { border-top-left-radius: 0; border-bottom-left-radius: 0; margin-left: -1px; }
        [dir="rtl"] .sr-page #settings-container .btn-group > .btn:not(:last-child) { border-radius: 0 9px 9px 0; }
        [dir="rtl"] .sr-page #settings-container .btn-group > .btn:not(:first-child) { border-radius: 9px 0 0 9px; margin-left: 0; margin-right: -1px; }
        [dir="rtl"] .sr-page #settings-container .btn-group > .btn:not(:first-child):not(:last-child) { border-radius: 0; }
        /* Input + button groups (email lists, recipients): same join */
        .sr-page #settings-container .input-group > .form-control:not(:last-child) { border-top-right-radius: 0; border-bottom-right-radius: 0; }
        .sr-page #settings-container .input-group-append > .btn { border-top-left-radius: 0; border-bottom-left-radius: 0; }
        [dir="rtl"] .sr-page #settings-container .input-group > .form-control:not(:last-child) { border-radius: 0 10px 10px 0; }
        [dir="rtl"] .sr-page #settings-container .input-group-append > .btn { border-radius: 9px 0 0 9px; }
        .sr-page #settings-container .alert { border-radius: 12px; font-size: 13px; }
        .sr-page .special-access-category, .sr-page .special-access-user-card { border-color: var(--sr-border); background: var(--sr-surface); }
        .sr-page .special-access-category-header { background: var(--sr-surface-2); border-color: var(--sr-border); color: var(--sr-text); }
        .sr-page .special-access-user-card { border-left-color: var(--sr-accent); }
        .sr-page .special-access-category-header i, .sr-page .page-access-group-label { color: var(--sr-accent); }
        .sr-page .page-access-group-label { border-bottom-color: var(--sr-accent-soft); }
        html.app-dark .sr-page #special-access-loading-overlay { background: rgba(15, 23, 42, .6); }

        /* ---------- Approval tab (renderApprovalChainUI in assets/js/app_settings.js) ---------- */
        .ac-head { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 12px; margin-bottom: 16px; }
        .sr-page #settings-container .ac-title { margin: 0; font-size: 17px; display: flex; align-items: center; gap: 8px; }
        .ac-title i { color: var(--sr-accent); font-size: 20px; }
        .ac-sub { margin: 4px 0 0; font-size: 12.5px; color: var(--sr-muted); }
        .ac-head-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
        .ac-search { flex: 0 1 240px; }
        .ac-search input { height: 32px; }
        .ac-stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin-bottom: 16px; }
        @media (max-width: 575px) { .ac-stats { grid-template-columns: 1fr; } }
        .ac-stat { display: flex; align-items: center; gap: 12px; padding: 12px 14px; border: 1px solid var(--sr-border); border-radius: 12px; background: var(--sr-surface-2); }
        .ac-stat-ico { width: 38px; height: 38px; border-radius: 10px; border: 1px solid; display: inline-flex; align-items: center; justify-content: center; font-size: 18px; flex: 0 0 auto; }
        .ac-stat-val { display: block; font-size: 20px; font-weight: 700; line-height: 1.1; color: var(--sr-text); font-variant-numeric: tabular-nums; }
        .ac-stat-lbl { display: block; font-size: 11.5px; font-weight: 600; color: var(--sr-muted); }
        .ac-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 16px; align-items: start; }
        @media (max-width: 575px) { .ac-grid { grid-template-columns: 1fr; } }
        .ac-card { margin: 0; display: flex; flex-direction: column; overflow: hidden; }
        .ac-card .sr-card-head { align-items: flex-start; padding: 14px 16px; }
        .ac-card .sr-card-body { padding: 14px 16px; flex: 1; }
        .ac-card-id { display: flex; align-items: flex-start; gap: 10px; min-width: 0; }
        .ac-card-text { min-width: 0; }
        .ac-card .sr-card-sub { margin-top: 2px; line-height: 1.4; }
        .ac-ico { width: 34px; height: 34px; border-radius: 10px; flex: 0 0 auto; display: inline-flex; align-items: center; justify-content: center; font-size: 17px; background: var(--sr-accent-soft); color: var(--sr-accent-strong); }
        .ac-card-foot { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 10px 16px; border-top: 1px solid var(--sr-border); background: var(--sr-surface-2); }
        .ac-hint { font-size: 11.5px; color: var(--sr-muted); display: inline-flex; align-items: center; gap: 2px; }
        .ac-loading { padding: 14px 0; text-align: center; font-size: 12.5px; color: var(--sr-muted); }
        .ac-empty { display: flex; flex-direction: column; align-items: center; gap: 4px; padding: 18px 12px; text-align: center; font-size: 12.5px; color: var(--sr-muted); border: 1px dashed var(--sr-border-strong); border-radius: 12px; }
        .ac-empty i { font-size: 26px; color: var(--sr-border-strong); }
        .ac-error { margin: 0; }
        /* Step list: numbered nodes joined by a vertical connector */
        .ac-steps { list-style: none; margin: 0; padding: 0; position: relative; }
        .ac-step {
            position: relative; display: flex; align-items: center; gap: 10px; padding: 8px 8px 8px 4px; margin-bottom: 8px;
            border: 1px solid var(--sr-border); border-radius: 10px; background: var(--sr-surface);
            cursor: grab; transition: border-color .15s, box-shadow .15s, background .15s;
        }
        .ac-step:last-child { margin-bottom: 0; }
        .ac-step:hover { border-color: var(--sr-accent); box-shadow: 0 0 0 3px rgba(99, 102, 241, .08); }
        .ac-step:active { cursor: grabbing; }
        .ac-step.dragging { opacity: .45; border-style: dashed; border-color: var(--sr-accent); background: var(--sr-accent-soft); }
        .ac-steps.is-sorting .ac-step:not(.dragging):hover { box-shadow: none; }
        .ac-step:not(:last-child) .ac-num::after {
            content: ''; position: absolute; top: 100%; inset-inline-start: 50%; width: 2px; height: 30px;
            margin-inline-start: -1px; background: var(--sr-border-strong); pointer-events: none;
        }
        .ac-handle { color: var(--sr-muted); font-size: 18px; line-height: 1; opacity: .6; }
        .ac-step:hover .ac-handle { opacity: 1; color: var(--sr-accent); }
        .ac-num {
            position: relative; width: 28px; height: 28px; border-radius: 50%; flex: 0 0 auto;
            display: inline-flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700;
            background: var(--sr-accent-soft); color: var(--sr-accent-strong); border: 1px solid var(--tone-indigo-bd);
        }
        .ac-step.is-final .ac-num { background: var(--tone-green-bg); color: var(--tone-green-fg); border-color: var(--tone-green-bd); font-size: 14px; }
        .ac-step-main { flex: 1; min-width: 0; }
        .ac-step-role { font-size: 13px; font-weight: 600; color: var(--sr-text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .ac-step-sub { font-size: 11.5px; color: var(--sr-muted); }
        .ac-final { color: var(--tone-green-fg); font-weight: 600; }
        .sr-page .ac-remove { color: var(--sr-muted); }
        .sr-page .ac-remove:hover { background: var(--tone-red-bg); color: var(--tone-red-fg); }
        /* Add approver popup: current chain preview */
        .ac-preview { display: flex; flex-wrap: wrap; align-items: center; gap: 4px; padding: 10px; border: 1px dashed var(--sr-border-strong); border-radius: 10px; min-height: 42px; }
        .ac-arrow { color: var(--sr-muted); }
        .ac-muted { font-size: 12.5px; color: var(--sr-muted); }

        /* ---------- Asset Clearance tab (renderAssetClearanceSettings) - reuses the .ac-* blocks above ---------- */
        #group-asset_clearance .sr-notice { margin-bottom: 16px; }
        .aca-card { margin: 0; overflow: visible; }
        .aca-table-wrap { padding: 0 6px 6px; overflow-x: auto; }
        .sr-page table.aca-table tbody tr { transition: background .15s; }
        .sr-page table.aca-table tbody tr:last-child td { border-bottom: 0 !important; }
        .sr-page table.aca-table tbody tr.is-dirty td { background: var(--tone-amber-bg); }
        .sr-page table.aca-table tbody tr.is-dirty td:first-child { box-shadow: inset 3px 0 0 var(--tone-amber-fg); }
        [dir="rtl"] .sr-page table.aca-table tbody tr.is-dirty td:first-child { box-shadow: inset -3px 0 0 var(--tone-amber-fg); }
        .aca-asset { display: flex; align-items: center; gap: 10px; }
        .aca-handler { min-width: 300px; width: 45%; }
        .aca-actions { white-space: nowrap; text-align: end; width: 1%; }
        .aca-actions .sr-btn + .sr-btn { margin-inline-start: 4px; }
        .aca-actions .sr-btn:disabled { opacity: .45; cursor: not-allowed; }
        .aca-handler .select2-container--default .select2-selection--multiple { min-height: 36px; }

        /* ---------- Org Structure sub-tabs (renderOrgCrud) ---------- */
        .sr-page #settings-container .nav-pills:not(.flex-column) .nav-link i { margin-inline-end: 4px; font-size: 15px; }
        .aca-card .sr-toolbar { padding: 12px 16px; }
        .org-name { display: flex; align-items: center; gap: 10px; min-width: 0; }
        .org-name-text { min-width: 0; display: flex; flex-direction: column; }
        .org-ar, .org-ar-cell { font-size: 12.5px; color: var(--sr-text-2); text-align: start; }
        .org-name .org-ar { color: var(--sr-muted); font-size: 12px; }
        .org-table th.aca-actions { text-align: end; }
        .org-color { display: inline-flex; align-items: center; gap: 7px; font-size: 12.5px; font-weight: 600; color: var(--sr-text-2); text-transform: capitalize; }
        .org-swatch { width: 14px; height: 14px; border-radius: 4px; flex: 0 0 auto; box-shadow: inset 0 0 0 1px rgba(15, 23, 42, .12); }
        .org-swatches { display: flex; flex-wrap: wrap; gap: 8px; }
        .org-swatch-opt { margin: 0; cursor: pointer; }
        .org-swatch-opt input { position: absolute; opacity: 0; pointer-events: none; }
        .org-swatch-opt > span {
            display: inline-flex; align-items: center; gap: 8px; padding: 7px 14px; border-radius: 10px; text-transform: capitalize;
            border: 1px solid var(--sr-border-strong); background: var(--sr-surface-2); font-size: 13px; font-weight: 600; color: var(--sr-text-2);
            transition: border-color .15s, box-shadow .15s, background .15s;
        }
        .org-swatch-opt:hover > span { border-color: var(--sr-accent); }
        .org-swatch-opt input:checked + span { border-color: var(--sr-accent); background: var(--sr-accent-soft); color: var(--sr-accent-strong); box-shadow: 0 0 0 2px rgba(99, 102, 241, .2); }
        .org-swatch-opt input:focus-visible + span { box-shadow: 0 0 0 3px rgba(99, 102, 241, .3); }
        .sr-addline-popup .select2-container.is-invalid .select2-selection { border-color: #dc2626 !important; box-shadow: 0 0 0 3px rgba(220, 38, 38, .12); }

        /* ---------- Shared settings building blocks (generic field renderer + custom tabs) ---------- */
        .as-subnav .nav-link i { margin-inline-end: 4px; font-size: 15px; }
        .sr-page #settings-nav .nav-link i { font-size: 17px; width: 20px; text-align: center; color: var(--sr-muted); flex: 0 0 auto; }
        .sr-page #settings-nav .nav-link.active i, .sr-page #settings-nav .nav-link:hover i { color: var(--sr-accent); }
        .as-section { margin: 0 0 16px; overflow: hidden; }
        .as-section .sr-card-head { align-items: flex-start; }
        .as-section .sr-card-head > button, .as-section .sr-card-head > .sr-pill { flex: 0 0 auto; }
        .as-section .sr-card-sub { margin-top: 3px; line-height: 1.45; }
        .as-section-body { padding: 16px 18px; }
        .as-section-foot { display: flex; align-items: center; justify-content: flex-end; gap: 10px; padding: 12px 18px; border-top: 1px solid var(--sr-border); background: var(--sr-surface-2); }

        /* Label / control rows */
        .as-fields { border: 1px solid var(--sr-border); border-radius: 12px; background: var(--sr-surface); overflow: hidden; }
        .as-section .as-fields { border: 0; border-radius: 0; }
        .as-field { display: grid; grid-template-columns: minmax(180px, 34%) minmax(0, 1fr); gap: 8px 20px; align-items: center; padding: 14px 18px; border-bottom: 1px solid var(--sr-border); }
        .as-field:last-child { border-bottom: 0; }
        .as-field:hover { background: var(--sr-surface-2); }
        @media (max-width: 767px) { .as-field { grid-template-columns: 1fr; } }
        .sr-page #settings-container .as-field-label { margin: 0; font-size: 13px; font-weight: 600; color: var(--sr-text); line-height: 1.4; }
        .as-field-control { min-width: 0; }
        .as-field-control .form-text { margin-top: 5px; }
        .as-field-action { background: var(--sr-surface-2); }
        .as-num-input { max-width: 220px; font-variant-numeric: tabular-nums; }
        .as-image-field { display: flex; align-items: center; gap: 14px; }
        .as-image-box { width: 120px; height: 64px; flex: 0 0 auto; display: inline-flex; align-items: center; justify-content: center; padding: 8px; border: 1px dashed var(--sr-border-strong); border-radius: 10px; background: var(--sr-surface-2); }
        .as-image-box img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .sr-page #settings-container .as-image-field .form-control-file { font-size: 12.5px; color: var(--sr-text-2); }
        .sr-page #settings-container .email-item .btn-outline-danger,
        .sr-page #settings-container .announcement-recipient-remove { color: var(--tone-red-fg); border-color: var(--sr-border-strong); background: var(--sr-surface); }
        .sr-page #settings-container .email-item .btn-outline-danger:hover:not(:disabled),
        .sr-page #settings-container .announcement-recipient-remove:hover { background: var(--tone-red-bg); border-color: var(--tone-red-bd); }
        .as-btn-row { display: inline-flex; flex-wrap: wrap; gap: 6px; }
        .sr-page #settings-container .input-group-append .btn-outline-secondary { border-color: var(--sr-border-strong); color: var(--sr-muted); background: var(--sr-surface); }

        /* Notices inside tabs */
        .as-notice { margin: 12px 0 0; }
        .as-notice-top { margin-bottom: 16px; }
        .as-notice-row { align-items: center; }
        .as-notice-row > div { flex: 1; }

        /* Checkbox tiles (deduction base components) */
        .as-check-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 8px; }
        .as-check-tile { margin: 0; cursor: pointer; }
        .as-check-tile input { position: absolute; opacity: 0; pointer-events: none; }
        .as-check-tile > span {
            display: flex; align-items: center; gap: 8px; padding: 9px 12px; border-radius: 10px; height: 100%;
            border: 1px solid var(--sr-border-strong); background: var(--sr-surface-2); font-size: 13px; font-weight: 600; color: var(--sr-text-2);
            transition: border-color .15s, background .15s, color .15s;
        }
        .as-check-tile > span i { width: 18px; height: 18px; border-radius: 5px; flex: 0 0 auto; display: inline-flex; align-items: center; justify-content: center;
            border: 1px solid var(--sr-border-strong); background: var(--sr-surface); color: transparent; font-size: 13px; }
        .as-check-tile:hover > span { border-color: var(--sr-accent); }
        .as-check-tile input:checked + span { border-color: var(--sr-accent); background: var(--sr-accent-soft); color: var(--sr-accent-strong); }
        .as-check-tile input:checked + span i { background: var(--sr-accent); border-color: var(--sr-accent); color: #fff; }
        .as-check-tile input:focus-visible + span { box-shadow: 0 0 0 3px rgba(99, 102, 241, .3); }
        .as-switch-row { margin-top: 14px; padding-top: 14px; border-top: 1px dashed var(--sr-border-strong); }

        /* Request type block tiles */
        .as-block-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 10px; }
        .as-block-tile {
            display: flex; align-items: center; justify-content: space-between; gap: 12px; margin: 0; cursor: pointer;
            padding: 11px 14px; border: 1px solid var(--sr-border); border-radius: 10px; background: var(--sr-surface);
            transition: border-color .15s, background .15s;
        }
        .as-block-tile:hover { border-color: var(--sr-border-strong); }
        .as-block-tile.is-blocked { border-color: var(--tone-red-bd); background: var(--tone-red-bg); }
        .as-block-tile .custom-switch { margin: 0; padding-inline-start: 2.25rem; }
        .as-block-name { font-size: 13px; font-weight: 600; color: var(--sr-text); }
        .as-block-tile.is-blocked .as-block-name { color: var(--tone-red-fg); }
        .sr-page #settings-container .as-block-tile.is-blocked .custom-control-input:checked ~ .custom-control-label::before { background-color: #dc2626; border-color: #dc2626; }

        /* Segmented filter */
        .as-seg { display: inline-flex; padding: 3px; gap: 2px; border: 1px solid var(--sr-border); border-radius: 10px; background: var(--sr-surface-2); }
        .as-seg-btn { border: 0; background: transparent; padding: 5px 12px; border-radius: 7px; font-size: 12px; font-weight: 600; color: var(--sr-muted); cursor: pointer; }
        .as-seg-btn:hover { color: var(--sr-text); }
        .as-seg-btn.is-on { background: var(--sr-surface); color: var(--sr-accent-strong); box-shadow: var(--sr-shadow); }

        /* Tables shared by the custom tabs */
        .sr-page table.aca-table tbody tr.is-inactive td { opacity: .62; }
        .sr-page table.aca-table tbody tr.is-inactive td.aca-actions { opacity: 1; }
        .as-pill-stack, .as-chip-wrap { display: flex; flex-wrap: wrap; gap: 4px; }
        .as-assigned { font-size: 12.5px; color: var(--sr-text-2); max-width: 260px; }
        .tt-week { display: flex; flex-wrap: wrap; gap: 3px; max-width: 360px; }
        .tt-day { display: inline-flex; align-items: center; gap: 3px; padding: 2px 6px; border-radius: 6px; font-size: 11px; white-space: nowrap;
            background: var(--sr-surface-3); color: var(--sr-text-2); font-variant-numeric: tabular-nums; }
        .tt-day b { font-weight: 700; color: var(--sr-text); }
        .tt-day.is-off { background: var(--tone-red-bg); color: var(--tone-red-fg); font-weight: 700; }
        .btn-toggle-timetable-active.is-activate { color: var(--tone-green-fg); }
        .as-role-hint { margin-top: 6px; min-height: 4px; }

        /* Integrations */
        .as-int-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 16px; }
        .as-int-card { margin: 0; padding: 16px 18px; display: flex; flex-direction: column; gap: 10px; }
        .as-int-card.is-on { border-color: var(--tone-green-bd); }
        .as-int-top { display: flex; align-items: center; gap: 12px; }
        .as-int-title { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
        .as-int-title b { font-size: 14px; color: var(--sr-text); }
        .as-int-hint { margin: 0; font-size: 12.5px; color: var(--sr-muted); line-height: 1.5; flex: 1; }
        .as-int-foot { padding-top: 10px; border-top: 1px solid var(--sr-border); }
        .as-ms-logo { display: grid; grid-template-columns: 13px 13px; gap: 2px; padding: 4px; flex: none; }
        .as-ms-logo i { width: 13px; height: 13px; display: block; }

        /* License */
        .as-license-status { display: flex; flex-wrap: wrap; align-items: center; gap: 14px; padding: 16px 18px; margin-bottom: 16px; border: 1px solid; border-radius: 12px; }
        .as-license-status.is-valid { background: var(--tone-green-bg); border-color: var(--tone-green-bd); color: var(--tone-green-fg); }
        .as-license-status.is-invalid { background: var(--tone-red-bg); border-color: var(--tone-red-bd); color: var(--tone-red-fg); }
        .as-license-ico { font-size: 30px; line-height: 1; }
        .as-license-text { flex: 1; min-width: 220px; }
        .as-license-title { font-size: 15px; font-weight: 700; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .as-license-msg { font-size: 12.5px; margin-top: 2px; opacity: .9; }
        .as-license-dates { display: flex; gap: 18px; }
        .as-license-dates span { display: block; font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; opacity: .75; }
        .as-license-dates b { font-size: 12.5px; }

        /* User picker + assigned-user cards (Special Access / Screen Settings) */
        .as-subhead { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 14px; }
        .as-subhead .ac-sub { margin: 0; }
        .as-picker { display: flex; align-items: center; gap: 14px; padding: 16px 18px; }
        .as-picker-ico { width: 42px; height: 42px; border-radius: 12px; flex: 0 0 auto; display: inline-flex; align-items: center; justify-content: center; font-size: 20px; background: var(--sr-accent-soft); color: var(--sr-accent-strong); }
        .as-picker-body { flex: 1; min-width: 0; }
        .as-list-search { flex: 0 1 260px; }
        .as-list-search input { height: 32px; }
        .as-user-list { padding: 12px; display: grid; gap: 10px; }
        .as-user-list > .sr-empty, .as-user-list > .ac-loading { grid-column: 1 / -1; }
        .as-user-card { border: 1px solid var(--sr-border); border-radius: 12px; background: var(--sr-surface); overflow: hidden; transition: border-color .15s, box-shadow .15s; }
        .as-user-card:hover { border-color: var(--sr-border-strong); box-shadow: var(--sr-shadow); }
        .as-user-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 10px 12px; background: var(--sr-surface-2); border-bottom: 1px solid var(--sr-border); }
        .as-user-head .sr-cell-title { display: inline-flex; align-items: center; gap: 6px; max-width: none; }
        .as-user-actions { display: flex; gap: 2px; flex: 0 0 auto; }
        .as-user-body { padding: 10px 12px 12px; }
        .as-grant-group + .as-grant-group { margin-top: 8px; }
        .sr-page .as-grant-group .special-access-group-label { margin: 0 0 4px; font-size: 10.5px; color: var(--sr-muted); }
        .as-grant-chip { display: inline-flex; align-items: center; padding: 3px 9px; border-radius: 7px; font-size: 11.5px; font-weight: 600;
            background: var(--tone-indigo-bg); color: var(--tone-indigo-fg); border: 1px solid var(--tone-indigo-bd); }
        .as-grant-chip.is-report { background: var(--tone-sky-bg); color: var(--tone-sky-fg); border-color: var(--tone-sky-bd); }
        .as-grant-chip.is-none { background: var(--tone-red-bg); color: var(--tone-red-fg); border-color: var(--tone-red-bd); }

        /* Special Access edit modal (sae-*) on the sr tokens */
        .sr-page .sae-layout { border-color: var(--sr-border); background: var(--sr-surface); }
        .sr-page .sae-sidebar { background: var(--sr-surface-2); border-color: var(--sr-border); }
        [dir="rtl"] .sr-page .sae-sidebar { border-right: 0; border-left: 1px solid var(--sr-border); }
        .sr-page .sae-tab { color: var(--sr-text-2); }
        .sr-page .sae-tab:hover { background: var(--sr-surface-3); }
        .sr-page .sae-tab.active { background: var(--sr-surface); border-left-color: var(--sr-accent); color: var(--sr-accent-strong); }
        [dir="rtl"] .sr-page .sae-tab { border-left: 0; border-right: 3px solid transparent; }
        [dir="rtl"] .sr-page .sae-tab.active { border-right-color: var(--sr-accent); }
        .sr-page .sae-tab span:first-child i { color: var(--sr-accent); }
        .sr-page .sae-panel-title { color: var(--sr-text); }
        .sr-page .sae-search-hit-cat { color: var(--sr-muted); border-color: var(--sr-border); }
        .sr-page .sae-empty-state { color: var(--sr-muted); }
        .sr-page .special-access-cat-count.badge-primary { background: var(--sr-accent); }
        .sr-page .custom-control-input:checked ~ .custom-control-label::before { background-color: var(--sr-accent); border-color: var(--sr-accent); }

        /* Every popup on the page: legacy Bootstrap form markup inside sr popups */
        .sr-addline-popup .form-group { text-align: start; }
        .sr-addline-popup .form-group > label, .sr-addline-popup .sr-form label { font-size: 12.5px; font-weight: 600; color: var(--sr-text-2); }
        .sr-addline-popup textarea.form-control { height: auto; min-height: 70px; }
        .sr-addline-popup .form-control-sm, .sr-addline-popup .input-group-sm > .form-control { height: 32px; font-size: 12.5px; }
        .sr-addline-popup .select2-container--default .select2-selection--single,
        .sr-addline-popup .select2-container--default .select2-selection--multiple { border-color: var(--sr-border-strong); border-radius: 9px; background: var(--sr-surface-2); min-height: 38px; }
        .sr-addline-popup .select2-container--default .select2-selection--single { height: 38px; }
        .sr-addline-popup .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 36px; color: var(--sr-text); text-align: start; }
        .sr-addline-popup .select2-container--default .select2-selection--single .select2-selection__arrow { height: 36px; }
        .sr-addline-popup .swal2-title { font-size: 19px; color: var(--sr-text); }
        .sr-addline-popup .swal2-validation-message { border-radius: 10px; font-size: 13px; }
        html.app-dark .sr-addline-popup .swal2-html-container, html.app-dark .sr-addline-popup .swal2-title { color: var(--sr-text-2); }
        .aca-handler .select2-container--default .select2-selection--multiple .select2-selection__choice {
            border: 1px solid var(--tone-indigo-bd); background: var(--tone-indigo-bg); color: var(--tone-indigo-fg); border-radius: 7px; font-size: 12px; font-weight: 600;
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
            // Microsoft Dynamics 365 master switch (D365 Config tab) - off hides every D365 option on this page
            'd365Enabled' => d365_enabled($conDB),
            // Attendance master switch (Integrations tab) - off hides Attendance Config and attendance permissions
            'attendanceEnabled' => attendance_enabled($conDB),
            'canAccessDepartmentsTab' => (bool) $canAccessDepartmentsTab,
            'canAccessJobTitlesTab' => (bool) $canAccessJobTitlesTab,
            'canAccessLocationsTab' => (bool) $canAccessLocationsTab,
            'canAccessCompaniesTab' => (bool) $canAccessCompaniesTab,
            'canAccessSubDepartmentsTab' => (bool) $canAccessSubDepartmentsTab,
            'canAccessRequestBlocksTab' => (bool) $canAccessRequestBlocksTab,
            'canAccessLoanSettingsTab' => (bool) $canAccessLoanSettingsTab,
            'canAccessVacationPayrollTab' => (bool) $canAccessVacationPayrollTab,
            'canAccessOvertimeSettingsTab' => (bool) $canAccessOvertimeSettingsTab,
            'canAccessDeductionSettingsTab' => (bool) $canAccessDeductionSettingsTab,
            'canAccessSalaryIncrementSettingsTab' => (bool) $canAccessSalaryIncrementSettingsTab,
            'canAccessResignationSettingsTab' => (bool) $canAccessResignationSettingsTab,
            'canAccessAttendanceConfigTab' => (bool) $canAccessAttendanceConfigTab,
            'canAccessScreenSettingsTab' => (bool) $canAccessScreenSettingsTab,
            'canAccessTempRoleTransferTab' => (bool) $canAccessTempRoleTransferTab,
            'canAccessVacationBlackoutTab' => (bool) $canAccessVacationBlackoutTab,
        ]) ?>;
        // Only meaningful for a non-admin 'manage_own_screen_settings' holder (prefills
        // their self-only form); full admins load every user's data via ajax instead.
        window.APP_SETTINGS_OWN_SCREEN_SETTINGS = <?= json_encode($ownScreenSettings ?? default_screen_settings()) ?>;
        // Lets the Screen Settings save handlers apply the change to THIS tab immediately
        // (no reload needed) when the emp_id being saved is whoever is currently logged in.
        window.APP_SETTINGS_CURRENT_EMP_ID = <?= json_encode((string) ($empid ?? '')) ?>;
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
            <div class="content sr-page">
                <div class="container-fluid">

                    <form id="settingsForm">
                        <div class="sr-head">
                            <div>
                                <h1><?= __("application_settings") ?></h1>
                                <p><?= __("manage_your_application_s_configuration") ?></p>
                            </div>
                            <div class="sr-head-actions" id="saveBtnWrapper">
                                <button type="submit" id="saveBtn" class="sr-btn sr-btn-primary"><i class="mdi mdi-content-save"></i> <?= __("save_changes") ?></button>
                            </div>
                        </div>

                        <div class="as-layout">
                            <div class="sr-card as-nav-card">
                                <div class="sr-search as-nav-search">
                                    <i class="mdi mdi-magnify"></i>
                                    <input type="search" id="asNavFilter" placeholder="<?= __('search') ?>..." autocomplete="off" aria-label="<?= __('search') ?>">
                                </div>
                                <ul id="settings-nav" class="nav nav-pills flex-column" role="tablist">
                                    <!-- Nav items will be injected here by JavaScript -->
                                    <div class="d-flex justify-content-center align-items-center" style="height: 100px;">
                                        <div class="loader"></div>
                                    </div>
                                </ul>
                            </div>
                            <div class="sr-card as-main-card">
                                <div id="settings-container" class="tab-content">
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
                        <!-- Screen Settings self-saves straight to its own dedicated actions
                             (get_screen_settings_data/update_screen_settings_map/update_own_screen_settings)
                             - no hidden field needed here, unlike special_access_by_user above. -->
                    </form>

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
    <?php if ($is_system_admin): ?>
    <?php if (empty($_SESSION['d365_csrf'])) { $_SESSION['d365_csrf'] = bin2hex(random_bytes(16)); } ?>
    <script>window.D365_DIM_CSRF = <?= json_encode($_SESSION['d365_csrf']) ?>;</script>
    <script src="assets/js/d365_dimensions_settings.js?v=<?= @filemtime(__DIR__ . '/assets/js/d365_dimensions_settings.js') ?>"></script>
    <?php endif; ?>
    <script src="assets/js/app_settings.js?v=<?= filemtime(__DIR__ . '/assets/js/app_settings.js') ?>"></script>

    <script>
    $('#asNavFilter').on('input', function() {
        var q = this.value.toLowerCase();
        $('#settings-nav > li').each(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(q) !== -1);
        });
    });
    </script>
</body>
</html>