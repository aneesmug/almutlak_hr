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
    document.addEventListener('DOMContentLoaded', function() {
        const isFullSettingsAdmin = <?= $is_system_admin ? 'true' : 'false' ?>;
        const canAccessDepartmentsTab = <?= $canAccessDepartmentsTab ? 'true' : 'false' ?>;
        const canAccessJobTitlesTab = <?= $canAccessJobTitlesTab ? 'true' : 'false' ?>;
        const canAccessLocationsTab = <?= $canAccessLocationsTab ? 'true' : 'false' ?>;
        const canAccessSubDepartmentsTab = <?= $canAccessSubDepartmentsTab ? 'true' : 'false' ?>;
        const canAccessRequestBlocksTab = <?= $canAccessRequestBlocksTab ? 'true' : 'false' ?>;
        const canAccessLoanSettingsTab = <?= $canAccessLoanSettingsTab ? 'true' : 'false' ?>;
        const canAccessVacationPayrollTab = <?= $canAccessVacationPayrollTab ? 'true' : 'false' ?>;
        const canAccessOvertimeSettingsTab = <?= $canAccessOvertimeSettingsTab ? 'true' : 'false' ?>;
        const canAccessDeductionSettingsTab = <?= $canAccessDeductionSettingsTab ? 'true' : 'false' ?>;
        const canAccessSalaryIncrementSettingsTab = <?= $canAccessSalaryIncrementSettingsTab ? 'true' : 'false' ?>;
        const requestTypeBlockLabels = <?= json_encode(get_blockable_request_type_labels(), JSON_UNESCAPED_UNICODE) ?>;
        let appSettings = [];
        let groupedSettings = {};
        let fullAccessCandidates = null;
        let specialAccessUsersRaw = null;
        let reportPermissionMap = {};
        let specialAccessEligibleUsers = [];
        let specialAccessMap = {};
        let currentSpecialAccessEmpId = '';
        const settingsContainer = document.getElementById('settings-container');
        const settingsNav = document.getElementById('settings-nav');
        const settingsForm = document.getElementById('settingsForm');

        function parseEmpIdList(value) {
            if (!value) return [];
            try {
                const parsed = JSON.parse(value);
                if (Array.isArray(parsed)) {
                    return parsed.map(v => String(v).trim()).filter(v => v !== '');
                }
            } catch (e) {
                // Not JSON, fallback to CSV
            }
            return String(value)
                .split(',')
                .map(v => v.trim())
                .filter(v => v !== '');
        }

        // 'hr_payroll' -> 'Hr Payroll' for display (user_type values are DB slugs, not labels).
        function formatRoleLabel(role) {
            return String(role || '')
                .split('_')
                .filter(Boolean)
                .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
                .join(' ');
        }

        function escapeHtml(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function getReportTypeCatalog() {
            return [
                { value: 'employee', label: '<?= __('employee_report') ?>' },
                { value: 'vacation', label: '<?= __('vacation_report') ?>' },
                { value: 'loan', label: '<?= __('loan_report') ?>' },
                { value: 'salary_increment', label: '<?= __('salary_increment_report', 'Salary Increment Report') ?>' },
                { value: 'salary', label: '<?= __('salary_report') ?>' },
                { value: 'payroll', label: '<?= __('payroll_report') ?>' },
                { value: 'attendance', label: '<?= __('attendance_report') ?>' },
                { value: 'document', label: '<?= __('document_report') ?>' },
                { value: 'assets', label: '<?= __('assets_report') ?>' },
                { value: 'assets_list', label: '<?= __('assets_list') ?>' },
                { value: 'evaluation', label: '<?= __('evaluation_report') ?>' },
                { value: 'resignation', label: '<?= __('resignation_report') ?>' },
                { value: 'terminated_employees', label: '<?= __('terminated_employees') ?>' },
                { value: 'eos', label: '<?= __('calculate_end_of_service') ?>' },
                { value: 'dept_comparison', label: '<?= __('dept_comparison_report') ?>' },
                { value: 'rejoin', label: '<?= __('employee_rejoin_report', 'Employee Rejoin Report') ?>' },
                { value: 'country_company_comparison', label: '<?= __('country_company_comparison_report', 'Country & Company Comparison Report') ?>' },
                { value: 'custom', label: '<?= __('custom_report') ?>' }
            ];
        }

        function getSpecialAccessCatalog() {
            return [
                <?php foreach (get_special_access_labels() as $key => $label): ?>
                { value: '<?= $key ?>', label: '<?= addslashes($label) ?>' },
                <?php endforeach; ?>
            ];
        }

        // Purely presentational grouping (icon + ordered keys) for the Special Access
        // checkbox grid - mirrors includes/special_access_helper.php::get_special_access_categories()
        // so both stay in sync. Any catalog key not listed in any category here still shows,
        // just bucketed under a trailing "Other" group by buildSpecialAccessGroupedHtml().
        function getSpecialAccessCategories() {
            return [
                <?php foreach (get_special_access_categories() as $categoryName => $meta): ?>
                { name: '<?= addslashes($categoryName) ?>', icon: '<?= addslashes($meta['icon']) ?>', keys: <?= json_encode(array_values($meta['keys'])) ?> },
                <?php endforeach; ?>
            ];
        }

        // Builds the grouped, collapsible-by-category checkbox grid markup shared by the
        // inline "select a user" panel and the SweetAlert2 edit modal. idPrefix keeps
        // checkbox/count element ids unique between the two contexts.
        function buildSpecialAccessGroupedHtml(idPrefix, selectedSet) {
            const categories = getSpecialAccessCategories();
            const catalog = getSpecialAccessCatalog();
            const labelByKey = new Map(catalog.map(item => [item.value, item.label]));
            const placedKeys = new Set();

            function renderCategoryBlock(catIndex, name, icon, keys) {
                let block = `<div class="special-access-category mb-3" data-cat-index="${catIndex}">`;
                block += `<div class="special-access-category-header">`;
                block += `<span><i class="fa ${icon}"></i> <strong>${escapeHtml(name)}</strong></span>`;
                block += `<span class="badge badge-light special-access-cat-count" id="${idPrefix}-catcount-${catIndex}" data-total="${keys.length}">0/${keys.length}</span>`;
                block += `</div>`;
                block += `<div class="special-access-category-body"><div class="special-access-checkbox-grid">`;
                keys.forEach(key => {
                    const label = labelByKey.get(key) || key;
                    const checkboxId = `${idPrefix}-${key}`;
                    block += `
                        <div class="special-access-item" data-search-label="${escapeHtml(label).toLowerCase()}">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input special-access-checkbox" id="${checkboxId}" value="${escapeHtml(key)}" data-access-key="${escapeHtml(key)}" data-cat-index="${catIndex}" ${selectedSet.has(key) ? 'checked' : ''}>
                                <label class="custom-control-label" for="${checkboxId}">${escapeHtml(label)}</label>
                            </div>
                        </div>
                    `;
                });
                block += `</div></div></div>`;
                return block;
            }

            let html = '';
            categories.forEach((cat, catIndex) => {
                const keysInCat = cat.keys.filter(k => labelByKey.has(k));
                if (!keysInCat.length) return;
                keysInCat.forEach(k => placedKeys.add(k));
                html += renderCategoryBlock(catIndex, cat.name, cat.icon, keysInCat);
            });

            const uncategorized = catalog.filter(item => !placedKeys.has(item.value)).map(item => item.value);
            if (uncategorized.length) {
                html += renderCategoryBlock(categories.length, '<?= __('other', 'Other') ?>', 'fa-ellipsis-h', uncategorized);
            }
            return html;
        }

        // Recomputes each category's "checked/total" badge after checkboxes change.
        function updateSpecialAccessCategoryCounts(container) {
            if (!container) return;
            container.querySelectorAll('.special-access-category').forEach(block => {
                const catIndex = block.getAttribute('data-cat-index');
                const countBadge = block.querySelector(`.special-access-cat-count[id$="-catcount-${catIndex}"]`);
                if (!countBadge) return;
                const total = parseInt(countBadge.getAttribute('data-total'), 10) || 0;
                const checked = block.querySelectorAll('.special-access-checkbox:checked').length;
                countBadge.textContent = `${checked}/${total}`;
                countBadge.classList.toggle('badge-success', checked > 0);
                countBadge.classList.toggle('badge-light', checked === 0);
            });
        }

        // Report Access lives inside the Special Access editor (both the inline panel and
        // the Swal edit modal) as one more grouped block, but it's backed by its own map
        // (reportPermissionMap / report_visibility_by_user) with different semantics: no
        // explicit entry for a user means "sees ALL report types" (backward-compatible
        // default from get_allowed_report_types_for_user()), not "sees none" like every
        // other Special Access key. That's why it gets its own builder/wiring instead of
        // reusing buildSpecialAccessGroupedHtml - the "no entry yet" starting state, the
        // Custom/All(default) mode badge, and the "Reset to Default" action are all specific
        // to this map's semantics. It renders nothing actionable for plain employees, since
        // get_report_permission_users() never included them in the first place (report
        // pages aren't reachable by that role).
        function buildReportAccessBlockHtml(idPrefix, empId) {
            const user = (specialAccessEligibleUsers || []).find(u => String(u.emp_id || '').trim() === empId);
            const userType = user ? String(user.user_type || '').trim().toLowerCase() : '';

            let html = `<div class="special-access-category mb-3" data-report-access-block="1">`;
            html += `<div class="special-access-category-header"><span><i class="fa fa-chart-bar"></i> <strong><?= __('report_access', 'Report Access') ?></strong></span>`;

            if (userType === 'employee') {
                html += `<span class="badge badge-light"><?= __('not_applicable', 'N/A') ?></span></div>`;
                html += `<div class="special-access-category-body"><p class="text-muted mb-0" style="font-size:.85rem;"><?= __('report_access_not_applicable_for_employees', "Report access doesn't apply to plain employee accounts.") ?></p></div></div>`;
                return html;
            }

            const catalog = getReportTypeCatalog();
            const allTypeValues = catalog.map(item => item.value);
            const hasExplicit = Object.prototype.hasOwnProperty.call(reportPermissionMap, empId);
            const selectedSet = new Set(hasExplicit ? normalizeReportTypeList(reportPermissionMap[empId]) : allTypeValues);

            html += `<span class="badge ${hasExplicit ? 'badge-info' : 'badge-light'}" id="${idPrefix}-report-mode">${hasExplicit ? '<?= __('custom', 'Custom') ?>' : '<?= __('all_default', 'All (default)') ?>'}</span></div>`;
            html += `<div class="special-access-category-body">`;
            html += `<div class="d-flex justify-content-between align-items-center mb-2 flex-wrap">`;
            html += `<small class="text-muted"><?= __('check_reports_user_can_view') ?></small>`;
            html += `<div>`;
            html += `<button type="button" class="btn btn-sm btn-outline-primary mr-1 report-access-select-all" data-target="${idPrefix}"><?= __('select_all') ?></button>`;
            html += `<button type="button" class="btn btn-sm btn-outline-secondary mr-1 report-access-clear-all" data-target="${idPrefix}"><?= __('clear_all') ?></button>`;
            html += `<button type="button" class="btn btn-sm btn-outline-warning report-access-reset-default" data-target="${idPrefix}"><?= __('reset_default') ?></button>`;
            html += `</div></div>`;
            html += `<div class="special-access-checkbox-grid">`;
            catalog.forEach(item => {
                const checkboxId = `${idPrefix}-report-${item.value}`;
                html += `
                    <div class="special-access-item" data-search-label="${escapeHtml(item.label).toLowerCase()}">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input report-type-checkbox" id="${checkboxId}" value="${escapeHtml(item.value)}" data-report-type="${escapeHtml(item.value)}" data-target="${idPrefix}" ${selectedSet.has(item.value) ? 'checked' : ''}>
                            <label class="custom-control-label" for="${checkboxId}">${escapeHtml(item.label)}</label>
                        </div>
                    </div>
                `;
            });
            html += `</div></div></div>`;
            return html;
        }

        // Wires the checkboxes/buttons rendered by buildReportAccessBlockHtml. onChange(mode,
        // values) fires on every change with mode 'custom' (values = the checked list) or
        // 'default' (Reset to Default was clicked) - the caller decides whether that means
        // "write straight to reportPermissionMap" (inline panel) or "hold until Save" (modal).
        function wireReportAccessBlock(root, idPrefix, onChange) {
            const modeBadge = root.querySelector(`#${idPrefix}-report-mode`);
            function setMode(isCustom) {
                if (!modeBadge) return;
                modeBadge.textContent = isCustom ? '<?= __('custom', 'Custom') ?>' : '<?= __('all_default', 'All (default)') ?>';
                modeBadge.classList.toggle('badge-info', isCustom);
                modeBadge.classList.toggle('badge-light', !isCustom);
            }
            function currentChecked() {
                return Array.from(root.querySelectorAll(`.report-type-checkbox[data-target="${idPrefix}"]`))
                    .filter(el => el.checked)
                    .map(el => el.value);
            }

            root.querySelectorAll(`.report-type-checkbox[data-target="${idPrefix}"]`).forEach(cb => {
                cb.addEventListener('change', () => {
                    setMode(true);
                    onChange('custom', currentChecked());
                });
            });

            const selectAllBtn = root.querySelector(`.report-access-select-all[data-target="${idPrefix}"]`);
            if (selectAllBtn) {
                selectAllBtn.addEventListener('click', () => {
                    root.querySelectorAll(`.report-type-checkbox[data-target="${idPrefix}"]`).forEach(el => { el.checked = true; });
                    setMode(true);
                    onChange('custom', currentChecked());
                });
            }

            const clearAllBtn = root.querySelector(`.report-access-clear-all[data-target="${idPrefix}"]`);
            if (clearAllBtn) {
                clearAllBtn.addEventListener('click', () => {
                    root.querySelectorAll(`.report-type-checkbox[data-target="${idPrefix}"]`).forEach(el => { el.checked = false; });
                    setMode(true);
                    onChange('custom', currentChecked());
                });
            }

            const resetBtn = root.querySelector(`.report-access-reset-default[data-target="${idPrefix}"]`);
            if (resetBtn) {
                resetBtn.addEventListener('click', () => {
                    root.querySelectorAll(`.report-type-checkbox[data-target="${idPrefix}"]`).forEach(el => { el.checked = true; });
                    setMode(false);
                    onChange('default', getReportTypeCatalog().map(item => item.value));
                });
            }
        }

        function parseSpecialAccessMap(rawValue) {
            if (!rawValue) return {};

            try {
                const parsed = JSON.parse(rawValue);
                if (!parsed || typeof parsed !== 'object' || Array.isArray(parsed)) {
                    return {};
                }
                const normalized = {};
                Object.keys(parsed).forEach(empId => {
                    const key = String(empId || '').trim();
                    if (!key) return;
                    normalized[key] = normalizeSpecialAccessList(parsed[empId]);
                });
                return normalized;
            } catch (e) {
                console.warn('Invalid special access JSON, resetting:', e);
                return {};
            }
        }

        function normalizeSpecialAccessList(values) {
            const allowed = new Set(getSpecialAccessCatalog().map(item => item.value));
            if (!Array.isArray(values)) return [];
            return [...new Set(values.map(v => String(v || '').trim()).filter(v => allowed.has(v)))];
        }

        function parseReportPermissionMap(rawValue) {
            if (!rawValue) return {};

            try {
                const parsed = JSON.parse(rawValue);
                if (!parsed || typeof parsed !== 'object' || Array.isArray(parsed)) {
                    return {};
                }
                const normalized = {};
                Object.keys(parsed).forEach(empId => {
                    const key = String(empId || '').trim();
                    if (!key) return;
                    normalized[key] = normalizeReportTypeList(parsed[empId]);
                });
                return normalized;
            } catch (e) {
                console.warn('Invalid report permission JSON, resetting:', e);
                return {};
            }
        }

        function normalizeReportTypeList(values) {
            const allowed = new Set(getReportTypeCatalog().map(item => item.value));
            if (!Array.isArray(values)) return [];
            return [...new Set(values.map(v => String(v || '').trim()).filter(v => allowed.has(v)))];
        }

        async function fetchFullAccessCandidates() {
            if (Array.isArray(fullAccessCandidates)) {
                return fullAccessCandidates;
            }

            try {
                const response = await fetch('./includes/settings_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ action: 'get_full_access_candidates' })
                });

                if (!response.ok) {
                    throw new Error('<?= __('failed_to_load_employees') ?>');
                }

                const data = await response.json();
                if (!data.success || !Array.isArray(data.employees)) {
                    throw new Error(data.message || '<?= __('failed_to_load_employees') ?>');
                }

                fullAccessCandidates = data.employees;
                return fullAccessCandidates;
            } catch (error) {
                console.error('Failed loading full access candidates:', error);
                fullAccessCandidates = [];
                return fullAccessCandidates;
            }
        }

        async function initializeFullAccessEmployeeSelect() {
            const element = document.getElementById('setting-full_access_emp_ids');
            if (!element) return;

            const selectedIds = parseEmpIdList(element.dataset.selected || '');
            const candidates = await fetchFullAccessCandidates();

            let optionsHtml = '';
            candidates.forEach(emp => {
                const empId = String(emp.emp_id || '').trim();
                if (!empId) return;
                const empName = (emp.name || '').trim();
                const selected = selectedIds.includes(empId) ? 'selected' : '';
                const label = `${empName} (${empId})`;
                optionsHtml += `<option value="${empId}" ${selected}>${label}</option>`;
            });

            element.innerHTML = optionsHtml;

            if ($(element).hasClass('select2-hidden-accessible')) {
                $(element).trigger('change.select2');
            } else {
                $(element).select2({
                    width: '100%',
                    placeholder: '<?= __('select_employees') ?>',
                    allowClear: true
                });
            }
        }

        /**
         * Translate text using window.lang object (from PHP __() function)
         */
        function translateText(key) {
            if (!key) return key;
            // Try the key as-is first
            if (window.lang && window.lang[key]) {
                return window.lang[key];
            }
            // Try normalized version: remove HTML tags, special chars, replace spaces with underscores, lowercase
            const normalizedKey = key
                .replace(/<[^>]*>/g, '')           // Remove HTML tags like <br>, <small>, etc.
                .replace(/[()[\]{}<%>,/]/g, '')    // Remove special characters: () [] {} < > % , /
                .replace(/\s+/g, '_')              // Replace spaces with underscores
                .toLowerCase();
            
            // Debug: Log for missing translations
            if (window.lang && window.lang[normalizedKey]) {
                return window.lang[normalizedKey];
            } else if (key !== normalizedKey && normalizedKey.length > 0) {
                console.warn(`Translation key not found: "${normalizedKey}" (original: "${key}")`);
            }
            
            // Return the original key if not found
            return key;
        }

        /**
         * Safely evaluate mathematical expressions for settings like session timeout
         * Only allows numbers, spaces, and basic arithmetic operators: + - * / ( )
         */
        function evaluateExpression(expression) {
            if (!expression) return '';
            expression = expression.trim();
            
            // Check if it's a simple number
            if (/^\d+$/.test(expression)) {
                return expression;
            }
            
            // Validate expression - only allow numbers, operators, and parentheses
            if (!/^[\d\s\+\-\*\/\(\)]+$/.test(expression)) {
                return null; // Invalid expression
            }
            
            try {
                // Use Function instead of eval for safer evaluation
                const result = Function('"use strict"; return (' + expression + ')')();
                if (typeof result === 'number' && result > 0 && Number.isInteger(result)) {
                    return result.toString();
                }
                return null;
            } catch (e) {
                return null;
            }
        }

        /**
         * Convert seconds to human-readable format
         * e.g., 7200 -> "2 hours", 1800 -> "30 minutes", 86400 -> "1 day"
         */
        function formatSecondsReadable(seconds) {
            seconds = parseInt(seconds, 10);
            if (isNaN(seconds) || seconds <= 0) return '';
            
            const units = [
                { name: 'day', value: 86400 },
                { name: 'hour', value: 3600 },
                { name: 'minute', value: 60 },
                { name: 'second', value: 1 }
            ];
            
            let result = [];
            let remaining = seconds;
            
            for (let unit of units) {
                if (remaining >= unit.value) {
                    const count = Math.floor(remaining / unit.value);
                    remaining = remaining % unit.value;
                    result.push(count + ' ' + unit.name + (count > 1 ? 's' : ''));
                }
            }
            
            if (result.length === 0) return seconds + ' second' + (seconds > 1 ? 's' : '');
            if (result.length === 1) return result[0];
            
            // Join with commas and 'and' before last item
            return result.slice(0, -1).join(', ') + ' and ' + result[result.length - 1]
        }

        function renderSettingsGroup(groupName) {
            let formHtml = '';
            // Normalize group name to use underscores for comparison
            const normalizedGroupName = groupName.replace(/ /g, '_');
            // Try to get settings with original groupName first, then with underscores replaced
            const displayGroupName = groupName.replace(/_/g, ' ');
            const settings = groupedSettings[groupName] || groupedSettings[displayGroupName];

            // These groups render their own dedicated Save button(s) and post straight to
            // their own handler (bypassing the outer settings form entirely) - the generic
            // bottom-right "Save Changes" button does nothing for them and only misleads
            // users into thinking their change was saved when it wasn't. Hide it here.
            const SELF_SAVING_GROUPS = ['departments', 'job_titles', 'locations', 'sub_departments', 'approval', 'request_type_blocks', 'payroll_settings'];
            const saveBtnWrapper = document.getElementById('saveBtnWrapper');
            if (saveBtnWrapper) {
                saveBtnWrapper.style.display = SELF_SAVING_GROUPS.includes(normalizedGroupName) ? 'none' : '';
            }

            if (!settings) {
                 settingsContainer.innerHTML = '<p class="text-center text-danger"><?= __('Group not found.') ?></p>';
                 return;
            }

            // Special handling for approval chain configuration
            if (normalizedGroupName === 'approval') {
                renderApprovalChainSettings();
                return;
            }

            // Special handling for job titles configuration
            if (normalizedGroupName === 'job_titles') {
                renderJobTitlesSettings();
                return;
            }

            // Special handling for departments configuration
            if (normalizedGroupName === 'departments') {
                renderDepartmentsSettings();
                return;
            }

            // Special handling for locations configuration
            if (normalizedGroupName === 'locations') {
                renderLocationsSettings();
                return;
            }

            // Special handling for sub-departments configuration
            if (normalizedGroupName === 'sub_departments') {
                renderSubDepartmentsSettings();
                return;
            }

            // Special handling for special access configuration
            if (normalizedGroupName === 'special_access') {
                renderSpecialAccessSettings();
                return;
            }

            // Special handling for global request type blocks
            if (normalizedGroupName === 'request_type_blocks') {
                renderRequestTypeBlocksSettings();
                return;
            }

            // Special handling for the Payroll Settings hub (loan / vacation / overtime /
            // deduction all live as sub-tabs INSIDE this one entry, so they never get
            // interleaved alphabetically with unrelated tabs like Departments or Email).
            if (normalizedGroupName === 'payroll_settings') {
                renderPayrollSettingsHub();
                return;
            }

            formHtml += `<div class="tab-pane active" id="group-${groupName}" role="tabpanel">`;
            settings.forEach(setting => {
                const id = `setting-${setting.setting_name}`;
                const label = translateText(setting.description);
                const isImagePath = setting.setting_name.includes('logo') || setting.setting_name.includes('favicon');
                const isEmailList = setting.setting_name === 'traveling_company_email';
                const isSessionTimeout = setting.setting_name === 'session_timeout';

                formHtml += `<div class="form-group row">`;
                formHtml += `<label for="${id}" class="col-sm-3 col-form-label">${label}</label>`;
                formHtml += `<div class="col-sm-9">`;
                
                if (isImagePath) {
                    formHtml += `<div class="d-flex align-items-center">`;
                    formHtml += `<img id="preview-${setting.setting_name}" src="${setting.setting_value || 'assets/images/placeholder.png'}" alt="Preview" class="preview-image mr-3">`;
                    formHtml += `<div class="flex-grow-1">`;
                    formHtml += `<input type="file" id="${id}" name="${setting.setting_name}" accept="image/*" class="form-control-file">`;
                    formHtml += `<small class="form-text text-muted"><?= __('current') ?> ${setting.setting_value || '<?= __('not_set') ?>'}</small>`;
                    formHtml += `</div></div>`;
                } else if (isEmailList) {
                    // Special handling for email list
                    let emails = [];
                    try {
                        const parsed = JSON.parse(setting.setting_value || '[]');
                        emails = Array.isArray(parsed) ? parsed : [setting.setting_value].filter(e => e);
                    } catch (e) {
                        emails = setting.setting_value ? [setting.setting_value] : [];
                    }
                    
                    formHtml += `<div id="email-list-container">`;
                    if (emails.length === 0) {
                        formHtml += `<div class="email-item mb-2">
                            <div class="input-group">
                                <input type="email" class="form-control email-input" placeholder="email@example.com" value="">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-danger remove-email-btn" disabled><i class="mdi mdi-delete"></i></button>
                                </div>
                            </div>
                        </div>`;
                    } else {
                        emails.forEach((email, idx) => {
                            formHtml += `<div class="email-item mb-2">
                                <div class="input-group">
                                    <input type="email" class="form-control email-input" placeholder="email@example.com" value="${email}">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-danger remove-email-btn"><i class="mdi mdi-delete"></i></button>
                                    </div>
                                </div>
                            </div>`;
                        });
                    }
                    formHtml += `</div>`;
                    formHtml += `<button type="button" class="btn btn-sm btn-outline-primary mt-2" id="add-email-btn"><i class="mdi mdi-plus"></i> <?= __('add_email') ?></button>`;
                    formHtml += `<input type="hidden" id="${id}" name="${setting.setting_name}" value="">`;
                } else if (isSessionTimeout) {
                    formHtml += `<div>`;
                    formHtml += `<input type="text" id="${id}" name="${setting.setting_name}" class="form-control session-timeout-input" value="${setting.setting_value || ''}" placeholder="<?= __('e.g., 3600 or 60*60*2') ?>">`;
                    formHtml += `<small class="form-text text-muted"><?= __('session_note') ?></small>`;
                    formHtml += `<div id="timeout-result-${setting.setting_name}" class="mt-2" style="display:none;">`;
                    formHtml += `<small class="text-success"><strong><?= __('evaluated_as') ?>:</strong> <span class="timeout-seconds"></span> <?= __('seconds') ?></small>`;
                    formHtml += `</div>`;
                    formHtml += `</div>`;
                } else if (setting.setting_name === 'full_access_emp_ids') {
                    const selectedList = parseEmpIdList(setting.setting_value || '');
                    formHtml += `<select id="${id}" name="${setting.setting_name}" class="form-control select2" multiple data-selected='${JSON.stringify(selectedList)}'></select>`;
                    formHtml += `<small class="form-text text-muted"><?= __('select_users_for_full_employee_access') ?></small>`;
                } else {
                    let inputHtml = '';
                    switch (setting.input_type) {
                        case 'select':
                            let options = JSON.parse(setting.options || '{}');
                            // Note: We still use form-control for layout, but our custom CSS will target .select2-container for styling.
                            inputHtml = `<select id="${id}" name="${setting.setting_name}" class="form-control select2">`;
                            for (const [value, text] of Object.entries(options)) {
                                inputHtml += `<option value="${value}" ${setting.setting_value == value ? 'selected' : ''}>${text}</option>`;
                            }
                            inputHtml += `</select>`;
                            break;
                        default:
                            inputHtml = `<input type="text" id="${id}" name="${setting.setting_name}" class="form-control" value="${setting.setting_value || ''}">`;
                            break;
                    }
                    formHtml += inputHtml;
                }
                
                formHtml += `</div></div>`;
            });
            formHtml += `</div>`;
            settingsContainer.innerHTML = formHtml;
            
            // Initialize Select2 with a width setting for better Bootstrap integration.
            $('.select2').select2({
                width: '100%'
            });

            initializeFullAccessEmployeeSelect();

            attachPreviewListeners();
            attachEmailListListeners();
            attachSessionTimeoutListeners();
        }

        // --- Payroll Settings Hub ---
        // A single top-level "Payroll Settings" tab holds Loan / Vacation Payroll /
        // Overtime / Deduction as inner sub-tabs, so these parameters stay grouped
        // together and never get interleaved alphabetically with unrelated tabs
        // (Departments, Email, Security, ...) in the outer nav.
        const PAYROLL_SETTINGS_SUB_TABS = [
            { key: 'loan_settings', label: '<?= __('loan_settings', 'Loan Settings') ?>', canAccess: () => canAccessLoanSettingsTab },
            { key: 'vacation_payroll', label: '<?= __('vacation_payroll_settings', 'Vacation Payroll Settings') ?>', canAccess: () => canAccessVacationPayrollTab },
            { key: 'overtime_settings', label: '<?= __('overtime_settings', 'Overtime Settings') ?>', canAccess: () => canAccessOvertimeSettingsTab },
            { key: 'deduction_settings', label: '<?= __('deduction_settings', 'Deduction Settings') ?>', canAccess: () => canAccessDeductionSettingsTab },
            { key: 'salary_increment_settings', label: '<?= __('salary_increment_settings', 'Salary Increment Settings') ?>', canAccess: () => canAccessSalaryIncrementSettingsTab },
        ];

        function renderPayrollSettingsHub() {
            const visibleTabs = PAYROLL_SETTINGS_SUB_TABS.filter(tab => tab.canAccess());

            let navHtml = '<ul class="nav nav-pills mb-3" id="payroll-settings-sub-nav">';
            visibleTabs.forEach((tab, idx) => {
                navHtml += `
                    <li class="nav-item">
                        <a class="nav-link ${idx === 0 ? 'active' : ''}" href="#" data-sub-tab="${tab.key}">${tab.label}</a>
                    </li>
                `;
            });
            navHtml += '</ul>';

            settingsContainer.innerHTML = `
                <div class="tab-pane active" id="group-payroll_settings" role="tabpanel">
                    ${navHtml}
                    <div id="payroll-settings-sub-content"></div>
                </div>
            `;

            const subContent = document.getElementById('payroll-settings-sub-content');

            function renderSubTab(key) {
                document.querySelectorAll('#payroll-settings-sub-nav a').forEach(a => {
                    a.classList.toggle('active', a.dataset.subTab === key);
                });
                if (key === 'deduction_settings') {
                    renderDeductionSettingsGroup(subContent);
                } else {
                    const tab = PAYROLL_SETTINGS_SUB_TABS.find(t => t.key === key);
                    renderPayrollParamGroup(key, tab.label, subContent);
                }
            }

            document.querySelectorAll('#payroll-settings-sub-nav a').forEach(a => {
                a.addEventListener('click', function(e) {
                    e.preventDefault();
                    renderSubTab(this.dataset.subTab);
                });
            });

            if (visibleTabs.length > 0) {
                renderSubTab(visibleTabs[0].key);
            } else {
                subContent.innerHTML = '<p class="text-center text-danger"><?= __('access_denied', 'Access denied') ?></p>';
            }
        }

        // These render independently of the main settings form/save button: each posts
        // straight to includes/payroll_settings_handler.php, which is permission-checked
        // per group (admin OR the matching Special Access key), so a restricted grantee
        // only ever sees and edits the one group they were given.
        async function renderPayrollParamGroup(group, title, hostEl) {
            hostEl.innerHTML = `
                <h5 class="mb-3">${title}</h5>
                <div id="payroll-param-fields-${group}">
                    <div class="text-center text-muted">
                        <div class="spinner-border spinner-border-sm" role="status"></div>
                        <span class="ml-2"><?= __('loading') ?></span>
                    </div>
                </div>
            `;

            try {
                const response = await fetch('./includes/payroll_settings_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ action: 'get_payroll_settings', group })
                });
                const data = await response.json();
                const container = document.getElementById(`payroll-param-fields-${group}`);

                if (!data.success) {
                    container.innerHTML = `<p class="text-danger"><i class="mdi mdi-alert"></i> ${data.message || '<?= __('access_denied', 'Access denied') ?>'}</p>`;
                    return;
                }

                let formHtml = '';
                data.settings.forEach(setting => {
                    const id = `payroll-param-${setting.setting_name}`;
                    formHtml += `
                        <div class="form-group row">
                            <label for="${id}" class="col-sm-4 col-form-label">${translateText(setting.description)}</label>
                            <div class="col-sm-8">
                                <input type="text" inputmode="decimal" id="${id}" data-setting-name="${setting.setting_name}" class="form-control payroll-param-input" value="${setting.setting_value ?? ''}">
                            </div>
                        </div>
                    `;
                });
                formHtml += `<button type="button" class="btn btn-primary mt-2" id="btn-save-${group}"><?= __('save', 'Save') ?></button>`;
                container.innerHTML = formHtml;

                document.getElementById(`btn-save-${group}`).addEventListener('click', async function() {
                    const payload = new URLSearchParams({ action: 'update_payroll_settings', group });
                    container.querySelectorAll('.payroll-param-input').forEach(input => {
                        payload.append(input.dataset.settingName, input.value.trim());
                    });

                    try {
                        const saveResponse = await fetch('./includes/payroll_settings_handler.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: payload
                        });
                        const saveResult = await saveResponse.json();
                        if (saveResult.success) {
                            Swal.fire('<?= __('saved', 'Saved') ?>', '<?= __('your_settings_have_been_updated_successfully') ?>', 'success');
                        } else {
                            Swal.fire('<?= __('error') ?>', saveResult.message || '<?= __('could_not_save_settings') ?>', 'error');
                        }
                    } catch (error) {
                        Swal.fire('<?= __('request_failed') ?>', error.message, 'error');
                    }
                });
            } catch (error) {
                document.getElementById(`payroll-param-fields-${group}`).innerHTML = `<p class="text-danger"><i class="mdi mdi-alert"></i> ${error.message}</p>`;
            }
        }

        // Salary components an admin can pick from for the deduction base (e.g. GOSI base).
        const DEDUCTION_BASE_COMPONENT_LABELS = {
            basic_salary: '<?= __('basic_salary', 'Basic Salary') ?>',
            housing_allowance: '<?= __('housing_allowance', 'Housing Allowance') ?>',
            transport_allowance: '<?= __('transport_allowance', 'Transportation Allowance') ?>',
            food_allowance: '<?= __('food_allowance', 'Food Allowance') ?>',
            miscellaneous_allowance: '<?= __('miscellaneous_allowance', 'Miscellaneous Allowance') ?>',
            cashier_allowance: '<?= __('cashier_allowance', 'Cashier Allowance') ?>',
            fuel_allowance: '<?= __('fuel_allowance', 'Fuel Allowance') ?>',
            telephone_allowance: '<?= __('telephone_allowance', 'Telephone Allowance') ?>',
            other_allowance: '<?= __('other_allowance', 'Other Allowance') ?>',
            guard_allowance: '<?= __('guard_allowance', 'Guard Allowance') ?>',
        };

        async function renderDeductionSettingsGroup(hostEl) {
            hostEl.innerHTML = `
                <h5 class="mb-3"><?= __('deduction_base_components', 'Deduction Base Components') ?></h5>
                <p class="text-muted"><?= __('deduction_base_components_hint', 'Select which salary components are summed as the base for percentage-based deductions (e.g. GOSI).') ?></p>
                <div id="deduction-base-components-fields">
                    <div class="text-center text-muted">
                        <div class="spinner-border spinner-border-sm" role="status"></div>
                        <span class="ml-2"><?= __('loading') ?></span>
                    </div>
                </div>
                <hr class="my-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0"><?= __('deduction_types', 'Deduction Types') ?></h5>
                    <button type="button" class="btn btn-sm btn-success" id="btn-add-deduction-type"><i class="mdi mdi-plus"></i> <?= __('add_new', 'Add New') ?></button>
                </div>
                <div id="deduction-types-container" class="border rounded p-3 bg-light">
                    <div class="text-center text-muted">
                        <div class="spinner-border spinner-border-sm" role="status"></div>
                        <span class="ml-2"><?= __('loading') ?></span>
                    </div>
                </div>
            `;

            loadDeductionBaseComponents();
            loadDeductionTypes();

            document.getElementById('btn-add-deduction-type').addEventListener('click', showAddDeductionTypeModal);
        }

        async function loadDeductionBaseComponents() {
            const container = document.getElementById('deduction-base-components-fields');
            try {
                const response = await fetch('./includes/payroll_settings_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ action: 'get_payroll_settings', group: 'deduction_settings' })
                });
                const data = await response.json();
                if (!data.success) {
                    container.innerHTML = `<p class="text-danger"><i class="mdi mdi-alert"></i> ${data.message || '<?= __('access_denied', 'Access denied') ?>'}</p>`;
                    return;
                }

                const setting = data.settings.find(s => s.setting_name === 'deduction_base_components');
                let selected = [];
                try {
                    const parsed = JSON.parse(setting ? setting.setting_value : '[]');
                    selected = Array.isArray(parsed) ? parsed : [];
                } catch (e) {
                    selected = [];
                }

                let checkboxesHtml = '<div class="row">';
                Object.entries(DEDUCTION_BASE_COMPONENT_LABELS).forEach(([key, label]) => {
                    const checked = selected.includes(key) ? 'checked' : '';
                    checkboxesHtml += `
                        <div class="col-sm-6 col-md-4 mb-2">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input deduction-base-component-checkbox" id="dbc-${key}" value="${key}" ${checked}>
                                <label class="custom-control-label" for="dbc-${key}">${label}</label>
                            </div>
                        </div>
                    `;
                });
                checkboxesHtml += '</div>';
                checkboxesHtml += `<button type="button" class="btn btn-primary mt-2" id="btn-save-deduction-base"><?= __('save', 'Save') ?></button>`;
                container.innerHTML = checkboxesHtml;

                document.getElementById('btn-save-deduction-base').addEventListener('click', async function() {
                    const chosen = Array.from(container.querySelectorAll('.deduction-base-component-checkbox:checked')).map(cb => cb.value);
                    try {
                        const saveResponse = await fetch('./includes/payroll_settings_handler.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: new URLSearchParams({
                                action: 'update_payroll_settings',
                                group: 'deduction_settings',
                                deduction_base_components: JSON.stringify(chosen)
                            })
                        });
                        const saveResult = await saveResponse.json();
                        if (saveResult.success) {
                            Swal.fire('<?= __('saved', 'Saved') ?>', '<?= __('your_settings_have_been_updated_successfully') ?>', 'success');
                        } else {
                            Swal.fire('<?= __('error') ?>', saveResult.message || '<?= __('could_not_save_settings') ?>', 'error');
                        }
                    } catch (error) {
                        Swal.fire('<?= __('request_failed') ?>', error.message, 'error');
                    }
                });
            } catch (error) {
                container.innerHTML = `<p class="text-danger"><i class="mdi mdi-alert"></i> ${error.message}</p>`;
            }
        }

        async function loadDeductionTypes() {
            const container = document.getElementById('deduction-types-container');
            try {
                const response = await fetch('./includes/payroll_settings_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ action: 'get_deduction_types' })
                });
                const data = await response.json();

                if (!data.success) {
                    container.innerHTML = `<p class="text-danger"><i class="mdi mdi-alert"></i> ${data.message || '<?= __('access_denied', 'Access denied') ?>'}</p>`;
                    return;
                }

                if (!data.deduction_types || data.deduction_types.length === 0) {
                    container.innerHTML = '<p class="text-muted mb-0"><?= __('no_deduction_types_configured_yet', 'No deduction types configured yet') ?></p>';
                    return;
                }

                let tableHtml = `<div class="table-responsive"><table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th><?= __('name') ?></th>
                            <th><?= __('counts_in_net_pay', 'Counts in Net Pay') ?></th>
                            <th><?= __('active') ?></th>
                            <th><?= __('actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>`;

                data.deduction_types.forEach(type => {
                    tableHtml += `
                        <tr data-id="${type.id}">
                            <td><strong>${type.name}</strong></td>
                            <td>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input deduction-type-counts-toggle" id="dt-counts-${type.id}" data-id="${type.id}" ${Number(type.counts_in_net) === 1 ? 'checked' : ''}>
                                    <label class="custom-control-label" for="dt-counts-${type.id}"></label>
                                </div>
                            </td>
                            <td>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input deduction-type-status-toggle" id="dt-status-${type.id}" data-id="${type.id}" ${Number(type.status) === 1 ? 'checked' : ''}>
                                    <label class="custom-control-label" for="dt-status-${type.id}"></label>
                                </div>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-primary edit-deduction-type-btn" data-id="${type.id}" data-name="${type.name}" title="<?= __('edit') ?>">
                                    <i class="mdi mdi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger delete-deduction-type-btn" data-id="${type.id}" title="<?= __('delete') ?>">
                                    <i class="mdi mdi-delete"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });
                tableHtml += '</tbody></table></div>';
                container.innerHTML = tableHtml;

                container.querySelectorAll('.deduction-type-counts-toggle, .deduction-type-status-toggle').forEach(toggle => {
                    toggle.addEventListener('change', function() {
                        saveDeductionTypeToggle(this.dataset.id);
                    });
                });
                container.querySelectorAll('.edit-deduction-type-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        showEditDeductionTypeModal(this.dataset.id, this.dataset.name);
                    });
                });
                container.querySelectorAll('.delete-deduction-type-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        deleteDeductionType(this.dataset.id);
                    });
                });
            } catch (error) {
                container.innerHTML = `<p class="text-danger"><i class="mdi mdi-alert"></i> ${error.message}</p>`;
            }
        }

        async function saveDeductionTypeToggle(id) {
            const row = document.querySelector(`#deduction-types-container tr[data-id="${id}"]`);
            const name = row.querySelector('td strong').textContent;
            const countsInNet = row.querySelector('.deduction-type-counts-toggle').checked;
            const status = row.querySelector('.deduction-type-status-toggle').checked;

            try {
                const response = await fetch('./includes/payroll_settings_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'update_deduction_type',
                        id, name,
                        counts_in_net: countsInNet ? '1' : '0',
                        status: status ? '1' : '0'
                    })
                });
                const result = await response.json();
                if (!result.success) {
                    Swal.fire('<?= __('error') ?>', result.message || '<?= __('could_not_save_settings') ?>', 'error');
                    loadDeductionTypes();
                }
            } catch (error) {
                Swal.fire('<?= __('request_failed') ?>', error.message, 'error');
                loadDeductionTypes();
            }
        }

        function showAddDeductionTypeModal() {
            Swal.fire({
                icon: 'info',
                title: '<?= __('add_new_deduction_type', 'Add New Deduction Type') ?>',
                html: `
                    <div class="form-group text-left">
                        <label for="deduction-type-name"><?= __('name') ?></label>
                        <input type="text" id="deduction-type-name" class="form-control" placeholder="<?= __('e.g. Late Deduction') ?>">
                    </div>
                    <div class="form-group text-left custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="deduction-type-counts" checked>
                        <label class="custom-control-label" for="deduction-type-counts"><?= __('counts_in_net_pay', 'Counts in Net Pay') ?></label>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: '<?= __('add', 'Add') ?>',
                preConfirm: () => {
                    const name = document.getElementById('deduction-type-name').value.trim();
                    if (!name) {
                        Swal.showValidationMessage('<?= __('name_is_required', 'Name is required') ?>');
                        return false;
                    }
                    return {
                        name,
                        counts_in_net: document.getElementById('deduction-type-counts').checked
                    };
                }
            }).then(async (result) => {
                if (!result.isConfirmed) return;
                try {
                    const response = await fetch('./includes/payroll_settings_handler.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({
                            action: 'add_deduction_type',
                            name: result.value.name,
                            counts_in_net: result.value.counts_in_net ? '1' : '0'
                        })
                    });
                    const addResult = await response.json();
                    if (addResult.success) {
                        loadDeductionTypes();
                    } else {
                        Swal.fire('<?= __('error') ?>', addResult.message || '<?= __('could_not_save_settings') ?>', 'error');
                    }
                } catch (error) {
                    Swal.fire('<?= __('request_failed') ?>', error.message, 'error');
                }
            });
        }

        function showEditDeductionTypeModal(id, currentName) {
            Swal.fire({
                icon: 'info',
                title: '<?= __('edit_deduction_type', 'Edit Deduction Type') ?>',
                html: `
                    <div class="form-group text-left">
                        <label for="deduction-type-edit-name"><?= __('name') ?></label>
                        <input type="text" id="deduction-type-edit-name" class="form-control" value="${currentName}">
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: '<?= __('save', 'Save') ?>',
                preConfirm: () => {
                    const name = document.getElementById('deduction-type-edit-name').value.trim();
                    if (!name) {
                        Swal.showValidationMessage('<?= __('name_is_required', 'Name is required') ?>');
                        return false;
                    }
                    return name;
                }
            }).then(async (result) => {
                if (!result.isConfirmed) return;
                const row = document.querySelector(`#deduction-types-container tr[data-id="${id}"]`);
                const countsInNet = row.querySelector('.deduction-type-counts-toggle').checked;
                const status = row.querySelector('.deduction-type-status-toggle').checked;
                try {
                    const response = await fetch('./includes/payroll_settings_handler.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({
                            action: 'update_deduction_type',
                            id,
                            name: result.value,
                            counts_in_net: countsInNet ? '1' : '0',
                            status: status ? '1' : '0'
                        })
                    });
                    const updateResult = await response.json();
                    if (updateResult.success) {
                        loadDeductionTypes();
                    } else {
                        Swal.fire('<?= __('error') ?>', updateResult.message || '<?= __('could_not_save_settings') ?>', 'error');
                    }
                } catch (error) {
                    Swal.fire('<?= __('request_failed') ?>', error.message, 'error');
                }
            });
        }

        function deleteDeductionType(id) {
            Swal.fire({
                icon: 'warning',
                title: '<?= __('are_you_sure', 'Are you sure?') ?>',
                text: '<?= __('this_action_cannot_be_undone', 'This action cannot be undone.') ?>',
                showCancelButton: true,
                confirmButtonText: '<?= __('delete') ?>',
                confirmButtonColor: '#dc3545'
            }).then(async (result) => {
                if (!result.isConfirmed) return;
                try {
                    const response = await fetch('./includes/payroll_settings_handler.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({ action: 'delete_deduction_type', id })
                    });
                    const deleteResult = await response.json();
                    if (deleteResult.success) {
                        loadDeductionTypes();
                    } else {
                        Swal.fire('<?= __('error') ?>', deleteResult.message || '<?= __('could_not_save_settings') ?>', 'error');
                    }
                } catch (error) {
                    Swal.fire('<?= __('request_failed') ?>', error.message, 'error');
                }
            });
        }

        function attachSessionTimeoutListeners() {
            const timeoutInputs = document.querySelectorAll('.session-timeout-input');
            timeoutInputs.forEach(input => {
                input.addEventListener('input', function() {
                    const value = this.value.trim();
                    const resultDiv = document.getElementById(`timeout-result-${this.name}`);
                    
                    if (value) {
                        const evaluated = evaluateExpression(value);
                        if (evaluated !== null) {
                            const readableFormat = formatSecondsReadable(evaluated);
                            resultDiv.style.display = 'block';
                            resultDiv.querySelector('.timeout-seconds').textContent = readableFormat + ' (' + evaluated + ' seconds)';
                            this.classList.remove('is-invalid');
                            this.classList.add('is-valid');
                        } else {
                            resultDiv.style.display = 'none';
                            this.classList.add('is-invalid');
                            this.classList.remove('is-valid');
                        }
                    } else {
                        resultDiv.style.display = 'none';
                        this.classList.remove('is-invalid', 'is-valid');
                    }
                });
                
                // Trigger input event on load to show current value
                input.dispatchEvent(new Event('input'));
            });
        }

        // Superset of the old report-permission user list (includes user_type='employee'
        // accounts too) - Special Access is also the mechanism for unlocking a normally-blocked
        // page for one employee, and Report Access now piggybacks on this same picker/list.
        async function fetchSpecialAccessUsers() {
            if (Array.isArray(specialAccessUsersRaw)) {
                return specialAccessUsersRaw;
            }

            try {
                const response = await fetch('./includes/settings_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ action: 'get_special_access_users' })
                });

                if (!response.ok) {
                    throw new Error('<?= __('failed_to_load_users') ?>');
                }

                const data = await response.json();
                if (!data.success || !Array.isArray(data.users)) {
                    throw new Error(data.message || '<?= __('failed_to_load_users') ?>');
                }

                specialAccessUsersRaw = data.users;
                return specialAccessUsersRaw;
            } catch (error) {
                console.error('Failed loading special access users:', error);
                specialAccessUsersRaw = [];
                return specialAccessUsersRaw;
            }
        }

        function updateReportPermissionHiddenValue() {
            const hidden = document.getElementById('setting-report_visibility_by_user');
            if (hidden) {
                hidden.value = JSON.stringify(reportPermissionMap);
            }
        }

        // Report Access no longer has its own tab/select-driven panel - it's rendered inside
        // the Special Access editor by buildReportAccessBlockHtml()/wireReportAccessBlock()
        // (see renderSpecialAccessCheckboxes and openSpecialAccessEditModal below), and its
        // assigned-user summary is folded into renderAssignedSpecialAccessSummary().

        function updateSpecialAccessHiddenValue() {
            const hidden = document.getElementById('setting-special_access_by_user');
            if (hidden) {
                hidden.value = JSON.stringify(specialAccessMap);
            }
        }

        function renderAssignedSpecialAccessSummary(users) {
            const container = document.getElementById('special-access-assigned-users-list');
            if (!container) return;

            const userMap = new Map((users || []).map(user => [String(user.emp_id || ''), user]));
            const catalogMap = new Map(getSpecialAccessCatalog().map(item => [item.value, item.label]));
            const reportCatalogMap = new Map(getReportTypeCatalog().map(item => [item.value, item.label]));
            const categories = getSpecialAccessCategories();

            // A user counts as "assigned" if they have ability grants OR an explicit report
            // access override (even one that grants zero reports - that's still a deliberate
            // restriction worth surfacing here, not the same as "never touched").
            const abilityEmpIds = Object.keys(specialAccessMap || {}).filter(empId => (specialAccessMap[empId] || []).length > 0);
            const reportEmpIds = Object.keys(reportPermissionMap || {});
            const assignedEmpIds = [...new Set([...abilityEmpIds, ...reportEmpIds])];

            const totalBadge = document.getElementById('special-access-total-users-badge');
            if (totalBadge) {
                totalBadge.textContent = assignedEmpIds.length + ' <?= __('assigned', 'assigned') ?>';
                totalBadge.classList.toggle('badge-primary', assignedEmpIds.length > 0);
                totalBadge.classList.toggle('badge-light', assignedEmpIds.length === 0);
            }

            if (!assignedEmpIds.length) {
                container.innerHTML = `<div class="special-access-empty-state"><i class="fas fa-user-shield"></i><?= __('no_assigned_users_yet') ?></div>`;
                return;
            }

            // Renders one user's granted keys as badges, grouped under a small uppercase
            // category label (same categories as the edit grid) instead of one flat run -
            // makes it scannable at a glance instead of a wall of identical blue badges.
            function buildGroupedBadges(grantedKeys) {
                const grantedSet = new Set(grantedKeys);
                const placed = new Set();
                let out = '';

                categories.forEach(cat => {
                    const keysHere = cat.keys.filter(k => grantedSet.has(k));
                    if (!keysHere.length) return;
                    keysHere.forEach(k => placed.add(k));
                    out += `<div class="special-access-group-label"><i class="fa ${cat.icon} mr-1"></i>${escapeHtml(cat.name)}</div>`;
                    out += keysHere.map(key => `<span class="badge badge-info mr-1 mb-1">${escapeHtml(catalogMap.get(key) || key)}</span>`).join('');
                });

                const leftover = grantedKeys.filter(k => !placed.has(k));
                if (leftover.length) {
                    out += `<div class="special-access-group-label"><?= __('other', 'Other') ?></div>`;
                    out += leftover.map(key => `<span class="badge badge-info mr-1 mb-1">${escapeHtml(catalogMap.get(key) || key)}</span>`).join('');
                }
                return out;
            }

            // Report access only gets a line here when the user has an EXPLICIT entry - if
            // they've never been touched they're on the "sees everything" default and there's
            // nothing to call out.
            function buildReportAccessBadges(empId) {
                if (!Object.prototype.hasOwnProperty.call(reportPermissionMap, empId)) return '';
                const grantedTypes = normalizeReportTypeList(reportPermissionMap[empId]);
                let out = `<div class="special-access-group-label"><i class="fa fa-chart-bar mr-1"></i><?= __('report_access', 'Report Access') ?></div>`;
                if (!grantedTypes.length) {
                    out += `<span class="badge badge-danger mr-1 mb-1"><?= __('no_reports', 'No reports') ?></span>`;
                } else {
                    out += grantedTypes.map(type => `<span class="badge badge-purple mr-1 mb-1">${escapeHtml(reportCatalogMap.get(type) || type)}</span>`).join('');
                }
                return out;
            }

            let html = '';

            assignedEmpIds.forEach(empId => {
                const user = userMap.get(empId);
                const name = user ? ((user.name || '').trim() || empId) : empId;
                const role = user ? ((user.user_type || '').trim()) : '';
                const grantedKeys = normalizeSpecialAccessList(specialAccessMap[empId]);
                const badgeCount = grantedKeys.length + (Object.prototype.hasOwnProperty.call(reportPermissionMap, empId) ? 1 : 0);

                html += '<div class="special-access-user-card">';
                html += '<div class="d-flex justify-content-between align-items-start">';
                html += `<div>
                    <strong>${escapeHtml(name)}</strong>
                    <span class="text-muted ml-1">#${escapeHtml(empId)}</span>
                    ${role ? `<span class="badge badge-primary ml-1">${escapeHtml(formatRoleLabel(role))}</span>` : ''}
                    <span class="badge badge-pill badge-secondary ml-2">${badgeCount}</span>
                </div>`;
                html += `<div class="text-nowrap">
                    <button type="button" class="btn btn-sm btn-outline-primary edit-assigned-special-access-user" data-emp-id="${escapeHtml(empId)}" title="<?= __('edit') ?>"><i class="fas fa-edit"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-assigned-special-access-user ml-1" data-emp-id="${escapeHtml(empId)}" title="<?= __('remove') ?>"><i class="fas fa-trash-alt"></i></button>
                </div>`;
                html += '</div>';
                html += `<div class="mt-2">${buildGroupedBadges(grantedKeys)}${buildReportAccessBadges(empId)}</div>`;
                html += '</div>';
            });

            container.innerHTML = html;

            container.querySelectorAll('.edit-assigned-special-access-user').forEach(btn => {
                btn.addEventListener('click', function() {
                    const empId = String(this.dataset.empId || '');
                    if (!empId) return;
                    openSpecialAccessEditModal(empId);
                });
            });

            container.querySelectorAll('.remove-assigned-special-access-user').forEach(btn => {
                btn.addEventListener('click', function() {
                    const empId = String(this.dataset.empId || '');
                    if (!empId) return;
                    Swal.fire({
                        title: '<?= __('remove_user_assignment') ?>',
                        text: '<?= __('this_will_remove_special_access_and_report_access_for_this_user', 'This will remove all special access AND report access customizations for this user.') ?>',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: '<?= __('yes_remove_it') ?>',
                        cancelButtonText: '<?= __('cancel') ?>'
                    }).then((result) => {
                        if (!result.isConfirmed) return;

                        delete specialAccessMap[empId];
                        updateSpecialAccessHiddenValue();
                        delete reportPermissionMap[empId];
                        updateReportPermissionHiddenValue();
                        renderAssignedSpecialAccessSummary(specialAccessEligibleUsers);

                        const select = document.getElementById('special-access-user-select');
                        if (select && select.value === empId) {
                            renderSpecialAccessCheckboxes(empId);
                        }
                    });
                });
            });
        }

        function renderSpecialAccessCheckboxes(empId) {
            const container = document.getElementById('special-access-checkboxes');
            if (!container) return;

            currentSpecialAccessEmpId = String(empId || '').trim();

            if (!currentSpecialAccessEmpId) {
                container.innerHTML = '<div class="special-access-empty-state"><i class="fas fa-hand-pointer"></i><?= __('select_user_to_configure_special_access') ?></div>';
                renderAssignedSpecialAccessSummary(specialAccessEligibleUsers);
                return;
            }

            const hasExplicit = Object.prototype.hasOwnProperty.call(specialAccessMap, currentSpecialAccessEmpId);
            // Unlike report permissions, an employee with no explicit entry has NO special access by default.
            const selectedTypes = hasExplicit ? normalizeSpecialAccessList(specialAccessMap[currentSpecialAccessEmpId]) : [];
            const selectedSet = new Set(selectedTypes);
            const safeEmpId = String(currentSpecialAccessEmpId || '').replace(/[^a-zA-Z0-9_-]/g, '_');

            let html = '<div class="d-flex justify-content-between align-items-center mb-2">';
            html += '<small class="text-muted"><?= __('check_the_special_abilities_this_user_should_have') ?></small>';
            html += '<div>';
            html += '<button type="button" class="btn btn-sm btn-outline-primary mr-1" id="special-access-select-all-btn"><?= __('select_all') ?></button>';
            html += '<button type="button" class="btn btn-sm btn-outline-secondary" id="special-access-clear-all-btn"><?= __('clear_all') ?></button>';
            html += '</div></div>';
            html += `<div class="form-group mb-2">
                <input type="text" class="form-control form-control-sm" id="special-access-search-input" placeholder="<?= __('search') ?>...">
            </div>`;
            html += `<div id="special-access-checkbox-grid">${buildSpecialAccessGroupedHtml('special-access-' + safeEmpId, selectedSet)}</div>`;
            html += '<p class="text-muted mb-0 mt-2" id="special-access-no-match" style="display:none;"><?= __('no_matching_records_found', 'No matching records found') ?></p>';
            html += buildReportAccessBlockHtml('special-access-' + safeEmpId, currentSpecialAccessEmpId);
            container.innerHTML = html;

            updateSpecialAccessCategoryCounts(container);
            wireReportAccessBlock(container, 'special-access-' + safeEmpId, (mode, values) => {
                const activeEmpId = String(currentSpecialAccessEmpId || '').trim();
                if (!activeEmpId) return;
                if (mode === 'default') {
                    delete reportPermissionMap[activeEmpId];
                } else {
                    reportPermissionMap[activeEmpId] = normalizeReportTypeList(values);
                }
                updateReportPermissionHiddenValue();
                renderAssignedSpecialAccessSummary(specialAccessEligibleUsers);
            });

            const searchInput = document.getElementById('special-access-search-input');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const term = this.value.trim().toLowerCase();
                    let visibleCount = 0;
                    container.querySelectorAll('.special-access-item').forEach(item => {
                        const matches = term === '' || (item.getAttribute('data-search-label') || '').includes(term);
                        item.style.display = matches ? '' : 'none';
                        if (matches) visibleCount++;
                    });
                    const noMatch = document.getElementById('special-access-no-match');
                    if (noMatch) noMatch.style.display = visibleCount === 0 ? '' : 'none';
                });
            }

            function persistFromCheckboxes() {
                const activeEmpId = String(currentSpecialAccessEmpId || '').trim();
                if (!activeEmpId) return;
                const checkedValues = Array.from(container.querySelectorAll('.special-access-checkbox:checked'))
                    .map(el => el.value);
                specialAccessMap[activeEmpId] = normalizeSpecialAccessList(checkedValues);
                updateSpecialAccessHiddenValue();
                updateSpecialAccessCategoryCounts(container);
                renderAssignedSpecialAccessSummary(specialAccessEligibleUsers);
            }

            container.querySelectorAll('.special-access-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', persistFromCheckboxes);
            });

            const selectAllBtn = document.getElementById('special-access-select-all-btn');
            const clearAllBtn = document.getElementById('special-access-clear-all-btn');

            if (selectAllBtn) {
                selectAllBtn.addEventListener('click', function() {
                    container.querySelectorAll('.special-access-checkbox').forEach(el => { el.checked = true; });
                    persistFromCheckboxes();
                });
            }

            if (clearAllBtn) {
                clearAllBtn.addEventListener('click', function() {
                    container.querySelectorAll('.special-access-checkbox').forEach(el => { el.checked = false; });
                    persistFromCheckboxes();
                });
            }

            renderAssignedSpecialAccessSummary(specialAccessEligibleUsers);
        }

        // Edit button on the "Assigned users" table opens the checkbox grid inside a
        // SweetAlert2 modal (self-contained popup) instead of scrolling the admin up to
        // the top select-driven panel. Reuses the same catalog/state as
        // renderSpecialAccessCheckboxes but keeps its own DOM ids so the two never clash
        // if both happen to be present at once.
        function openSpecialAccessEditModal(empId) {
            const targetEmpId = String(empId || '').trim();
            if (!targetEmpId) return;

            const user = (specialAccessEligibleUsers || []).find(u => String(u.emp_id || '').trim() === targetEmpId);
            const displayName = user ? ((user.name || '').trim() || targetEmpId) : targetEmpId;

            const hasExplicit = Object.prototype.hasOwnProperty.call(specialAccessMap, targetEmpId);
            const selectedSet = new Set(hasExplicit ? normalizeSpecialAccessList(specialAccessMap[targetEmpId]) : []);
            const grantedCount = selectedSet.size;

            // Report Access state is tracked locally until Save (mirrors how abilities are only
            // read from the DOM on preConfirm) - seeded from the current reportPermissionMap so
            // closing without touching it doesn't silently reset anything.
            const reportHasExplicit = Object.prototype.hasOwnProperty.call(reportPermissionMap, targetEmpId);
            let reportAccessMode = reportHasExplicit ? 'custom' : 'default';
            let reportAccessValues = reportHasExplicit
                ? normalizeReportTypeList(reportPermissionMap[targetEmpId])
                : getReportTypeCatalog().map(item => item.value);

            let gridHtml = '<div class="text-left">';
            gridHtml += `<p class="text-muted mb-3" style="font-size:.85rem;"><?= __('currently_granted', 'Currently granted') ?>: <span class="badge badge-${grantedCount ? 'success' : 'light'}">${grantedCount}</span></p>`;
            gridHtml += '<div class="d-flex justify-content-between align-items-center mb-3">';
            gridHtml += '<input type="text" class="form-control form-control-sm mr-2" id="swal-special-access-search" placeholder="<?= __('search') ?>...">';
            gridHtml += '<div class="text-nowrap">';
            gridHtml += '<button type="button" class="btn btn-sm btn-outline-primary mr-1" id="swal-special-access-select-all"><?= __('select_all') ?></button>';
            gridHtml += '<button type="button" class="btn btn-sm btn-outline-secondary" id="swal-special-access-clear-all"><?= __('clear_all') ?></button>';
            gridHtml += '</div></div>';
            gridHtml += `<div id="swal-special-access-grid" style="max-height:55vh;overflow-y:auto;">`;
            gridHtml += buildSpecialAccessGroupedHtml('swal-special-access', selectedSet);
            gridHtml += buildReportAccessBlockHtml('swal-special-access', targetEmpId);
            gridHtml += `</div>`;
            gridHtml += '</div>';

            Swal.fire({
                title: displayName,
                html: gridHtml,
                width: '70%',
                showCancelButton: true,
                confirmButtonText: '<?= __('save_changes') ?>',
                cancelButtonText: '<?= __('cancel') ?>',
                focusConfirm: false,
                didOpen: () => {
                    const popup = Swal.getPopup();
                    updateSpecialAccessCategoryCounts(popup);

                    popup.querySelectorAll('.special-access-checkbox').forEach(checkbox => {
                        checkbox.addEventListener('change', () => updateSpecialAccessCategoryCounts(popup));
                    });

                    wireReportAccessBlock(popup, 'swal-special-access', (mode, values) => {
                        reportAccessMode = mode;
                        reportAccessValues = values;
                    });

                    const searchInput = popup.querySelector('#swal-special-access-search');
                    if (searchInput) {
                        searchInput.addEventListener('input', function() {
                            const term = this.value.trim().toLowerCase();
                            popup.querySelectorAll('.special-access-item').forEach(item => {
                                const matches = term === '' || (item.getAttribute('data-search-label') || '').includes(term);
                                item.style.display = matches ? '' : 'none';
                            });
                            popup.querySelectorAll('.special-access-category').forEach(block => {
                                const anyVisible = Array.from(block.querySelectorAll('.special-access-item')).some(el => el.style.display !== 'none');
                                block.style.display = anyVisible ? '' : 'none';
                            });
                        });
                    }
                    const selectAllBtn = popup.querySelector('#swal-special-access-select-all');
                    if (selectAllBtn) {
                        selectAllBtn.addEventListener('click', () => {
                            popup.querySelectorAll('.special-access-checkbox').forEach(el => { el.checked = true; });
                            updateSpecialAccessCategoryCounts(popup);
                        });
                    }
                    const clearAllBtn = popup.querySelector('#swal-special-access-clear-all');
                    if (clearAllBtn) {
                        clearAllBtn.addEventListener('click', () => {
                            popup.querySelectorAll('.special-access-checkbox').forEach(el => { el.checked = false; });
                            updateSpecialAccessCategoryCounts(popup);
                        });
                    }
                },
                preConfirm: () => {
                    const popup = Swal.getPopup();
                    const abilities = Array.from(popup.querySelectorAll('.special-access-checkbox:checked')).map(el => el.value);
                    const reportAccessApplicable = !!popup.querySelector('.report-type-checkbox, .report-access-select-all');
                    return { abilities, reportAccessApplicable, reportAccessMode, reportAccessValues };
                }
            }).then((result) => {
                if (!result.isConfirmed) return;

                const { abilities, reportAccessApplicable, reportAccessMode: finalMode, reportAccessValues: finalValues } = result.value || {};

                specialAccessMap[targetEmpId] = normalizeSpecialAccessList(abilities || []);
                updateSpecialAccessHiddenValue();

                if (reportAccessApplicable) {
                    if (finalMode === 'default') {
                        delete reportPermissionMap[targetEmpId];
                    } else {
                        reportPermissionMap[targetEmpId] = normalizeReportTypeList(finalValues || []);
                    }
                    updateReportPermissionHiddenValue();
                }

                renderAssignedSpecialAccessSummary(specialAccessEligibleUsers);

                // Keep the top select-driven panel in sync if it's currently showing this user.
                if (currentSpecialAccessEmpId === targetEmpId) {
                    renderSpecialAccessCheckboxes(targetEmpId);
                }
            });
        }

        function renderRequestTypeBlocksSettings() {
            settingsContainer.innerHTML = `
                <div class="tab-pane active" id="group-request_type_blocks" role="tabpanel">
                    <h5 class="mb-0"><?= __('manage_request_type_blocks', 'Manage Request Type Blocks') ?></h5>
                    <p class="text-muted font-14 mt-2">
                        <?= __('manage_request_type_blocks_hint', 'Blocking a request type here disables it for every employee at once. To exempt a specific employee from a global block (or to block just one employee for a type that isn\'t globally blocked), use the "Block Specific Request Types" section on that employee\'s Edit Employee page.') ?>
                    </p>
                    <div id="requestTypeBlockList" class="mt-4">
                        <div class="text-center text-muted">
                            <div class="spinner-border spinner-border-sm" role="status"></div>
                            <span class="ml-2"><?= __('loading') ?></span>
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="button" id="saveRequestTypeBlocksBtn" class="btn btn-primary waves-effect waves-light">
                            <i class="fa fa-save"></i> <?= __('save_changes') ?>
                        </button>
                    </div>
                </div>
            `;

            const listContainer = document.getElementById('requestTypeBlockList');
            const saveBtn = document.getElementById('saveRequestTypeBlocksBtn');

            function renderCheckboxes(blockedTypes) {
                const blockedSet = new Set(blockedTypes || []);
                let html = '<div class="row">';
                Object.keys(requestTypeBlockLabels).forEach((key) => {
                    const label = requestTypeBlockLabels[key];
                    const checked = blockedSet.has(key) ? 'checked' : '';
                    html += `
                        <div class="col-md-6 mb-3">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input request-type-block-checkbox" id="blockType_${key}" value="${key}" ${checked}>
                                <label class="custom-control-label" for="blockType_${key}">${label}</label>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                listContainer.innerHTML = html;
            }

            function loadBlockedTypes() {
                fetch('./includes/ajaxFile/globalRequestBlockHandler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ action: 'get_global_blocked_types' })
                })
                .then((response) => response.json())
                .then((data) => {
                    if (!data.success) {
                        throw new Error(data.message || '<?= __('failed_to_load_current_blocks', 'Failed to load current blocks.') ?>');
                    }
                    renderCheckboxes(data.blocked_types);
                })
                .catch((error) => {
                    listContainer.innerHTML = `<p class="text-danger">${error.message}</p>`;
                });
            }

            saveBtn.addEventListener('click', () => {
                const checked = Array.from(document.querySelectorAll('.request-type-block-checkbox:checked')).map((el) => el.value);

                fetch('./includes/ajaxFile/globalRequestBlockHandler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ action: 'update_global_blocked_types', blocked_types: JSON.stringify(checked) })
                })
                .then((response) => response.json())
                .then((data) => {
                    if (!data.success) {
                        throw new Error(data.message || '<?= __('failed_to_save', 'Failed to save.') ?>');
                    }
                    Swal.fire('<?= __('success') ?>', '<?= __('settings_updated_successfully', 'Settings updated successfully.') ?>', 'success');
                    renderCheckboxes(data.blocked_types);
                })
                .catch((error) => {
                    Swal.fire('<?= __('Error!') ?>', error.message, 'error');
                });
            });

            loadBlockedTypes();
        }

        async function renderSpecialAccessSettings() {
            settingsContainer.innerHTML = `
                <div class="tab-pane active" id="group-special-access" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <h5 class="mb-0"><i class="fas fa-user-shield mr-2 text-primary"></i><?= __('special_access_by_user') ?></h5>
                        <span class="badge badge-pill badge-light" id="special-access-total-users-badge"></span>
                    </div>
                    <p class="text-muted mb-3"><?= __('select_a_user_and_grant_them_specific_admin_hr_abilities') ?> <?= __('report_access_is_also_managed_here', 'Report access (which reports a user can view) is also managed here, per user.') ?></p>

                    <div class="card mb-3">
                        <div class="card-body">
                            <label for="special-access-user-select" class="font-weight-bold mb-2"><i class="fas fa-search mr-1 text-muted"></i><?= __('select_user') ?></label>
                            <select id="special-access-user-select" class="form-control select2"></select>
                            <div id="special-access-checkboxes" class="mt-3">
                                <div class="text-center text-muted">
                                    <div class="spinner-border spinner-border-sm" role="status"></div>
                                    <span class="ml-2"><?= __('loading') ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h6 class="mb-2"><i class="fas fa-users mr-1 text-muted"></i><?= __('assigned_users') ?></h6>
                    <div id="special-access-assigned-users-list">
                        <div class="text-center text-muted">
                            <div class="spinner-border spinner-border-sm" role="status"></div>
                            <span class="ml-2"><?= __('loading') ?></span>
                        </div>
                    </div>
                </div>
            `;

            // NOTE: specialAccessMap is intentionally NOT re-initialized from appSettings here.
            // It's seeded once in loadSettings() right after fetch, and the hidden input that
            // carries it to Save now lives outside #settings-container (persists across tab
            // switches). Re-parsing from appSettings on every render of this tab would silently
            // discard any grant/removal the admin made before navigating to another tab and back.

            // Plain employees are included here on purpose - the "Access Page: ..." special
            // access keys exist specifically to grant a single employee access to a page that's
            // normally blocked for their role (see get_special_access_page_labels()).
            const users = await fetchSpecialAccessUsers();
            specialAccessEligibleUsers = users;
            const select = document.getElementById('special-access-user-select');
            const checkboxContainer = document.getElementById('special-access-checkboxes');

            if (!select || !checkboxContainer) return;

            if (!users.length) {
                select.innerHTML = '<option value=""><?= __('no_users_found') ?></option>';
                checkboxContainer.innerHTML = '<p class="text-muted mb-0"><?= __('no_users_found') ?></p>';
                return;
            }

            let options = `<option value=""><?= __('select_user') ?></option>`;
            users.forEach(user => {
                const empId = String(user.emp_id || '').trim();
                if (!empId) return;
                const displayName = (user.name || '').trim() || empId;
                const role = (user.user_type || '').trim();
                options += `<option value="${escapeHtml(empId)}">${escapeHtml(displayName)} (${escapeHtml(empId)})${role ? ' - ' + escapeHtml(formatRoleLabel(role)) : ''}</option>`;
            });
            select.innerHTML = options;

            if ($(select).hasClass('select2-hidden-accessible')) {
                $(select).trigger('change.select2');
            } else {
                $(select).select2({ width: '100%' });
            }

            const $select = $(select);
            $select.off('change.specialAccess select2:select.specialAccess select2:clear.specialAccess');
            $select.on('change.specialAccess select2:select.specialAccess select2:clear.specialAccess', function() {
                const selectedEmpId = String($select.val() || '').trim();
                currentSpecialAccessEmpId = selectedEmpId;
                renderSpecialAccessCheckboxes(selectedEmpId);
            });

            currentSpecialAccessEmpId = '';
            renderSpecialAccessCheckboxes('');
        }

        function renderJobTitlesSettings() {
            let formHtml = `<div class="tab-pane active" id="group-job" role="tabpanel">`;
            formHtml += `<div class="d-flex justify-content-between align-items-center mb-3">`;
            formHtml += `<h5 class="mb-0"><?= __('job_titles_management') ?></h5>`;
            formHtml += `<button type="button" class="btn btn-sm btn-success" id="btn-add-job-title"><i class="mdi mdi-plus"></i> <?= __('add_new_job_title') ?></button>`;
            formHtml += `</div>`;
            formHtml += `<p class="text-muted mb-4"><?= __('manage_job_titles_in_english_and_arabic') ?></p>`;
            
            // Search field
            formHtml += `<div class="form-group mb-3">`;
            formHtml += `<input type="text" id="job-search-input" class="form-control" placeholder="<?= __('search_job_titles_english_or_arabic') ?>" style="max-width: 400px;">`;
            formHtml += `<small class="form-text text-muted mt-1"><?= __('search_by_job_title_in_english_or_arabic') ?></small>`;
            formHtml += `</div>`;
            
            formHtml += `<div id="job-titles-container" class="border rounded p-3 bg-light">`;
            formHtml += `<div class="text-center text-muted">`;
            formHtml += `<div class="spinner-border spinner-border-sm" role="status"></div>`;
            formHtml += `<span class="ml-2"><?= __('loading') ?></span>`;
            formHtml += `</div>`;
            formHtml += `</div>`;
            formHtml += `</div>`;
            settingsContainer.innerHTML = formHtml;

            // Load job titles
            loadJobTitles();

            // Attach event listener for "Add Job Title" button
            const btnAddJobTitle = document.getElementById('btn-add-job-title');
            if (btnAddJobTitle) {
                btnAddJobTitle.addEventListener('click', showAddJobTitleModal);
            }
            
            // Attach event listener for search input
            const searchInput = document.getElementById('job-search-input');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    filterJobTitles(this.value);
                });
            }
        }

        async function loadJobTitles() {
            try {
                const response = await fetch('./includes/job_titles_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ action: 'get_job_titles' })
                });

                if (!response.ok) throw new Error('<?= __('Failed to load job titles') ?>');
                const data = await response.json();

                const container = document.getElementById('job-titles-container');
                if (!data.success || !data.jobs || data.jobs.length === 0) {
                    container.innerHTML = '<p class="text-muted mb-0"><i class="mdi mdi-information-outline"></i> <?= __('No job titles configured yet.') ?></p>';
                    return;
                }

                let jobsHtml = '<div class="table-responsive"><table class="table table-hover mb-0"><thead class="bg-light"><tr><th><?= __('job_title_english') ?></th><th><?= __('job_title_arabic') ?></th><th><?= __('actions') ?></th></tr></thead><tbody>';
                data.jobs.forEach((job) => {
                    jobsHtml += `
                        <tr>
                            <td><strong>${job.job || 'N/A'}</strong></td>
                            <td><strong>${job.job_ar || 'N/A'}</strong></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-primary edit-job-btn" data-job-id="${job.id}" title="<?= __('edit') ?>">
                                    <i class="mdi mdi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger delete-job-btn" data-job-id="${job.id}" title="<?= __('delete') ?>">
                                    <i class="mdi mdi-delete"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });
                jobsHtml += '</tbody></table></div>';
                container.innerHTML = jobsHtml;

                // Attach event listeners
                container.querySelectorAll('.edit-job-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        showEditJobTitleModal(this.dataset.jobId);
                    });
                });

                container.querySelectorAll('.delete-job-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        deleteJobTitle(this.dataset.jobId);
                    });
                });

            } catch (error) {
                console.error('Error loading job titles:', error);
                const container = document.getElementById('job-titles-container');
                container.innerHTML = `<p class="text-danger"><i class="mdi mdi-alert"></i> <?= __('Error:') ?> ${error.message}</p>`;
            }
        }

        function showAddJobTitleModal() {
            Swal.fire({
                icon: 'info',
                title: '<?= __('add_new_job_title') ?>',
                html: `
                    <div class="form-group text-left">
                        <label for="job-title-en"><?= __('job_title_english') ?></label>
                        <input type="text" id="job-title-en" class="form-control" placeholder="<?= __('enter_job_title_in_english') ?>">
                    </div>
                    <div class="form-group text-left">
                        <label for="job-title-ar"><?= __('job_title_arabic') ?></label>
                        <input type="text" id="job-title-ar" class="form-control" placeholder="<?= __('enter_job_title_in_arabic') ?>">
                    </div>
                `,
                allowOutsideClick: false,
                showCancelButton: true,
                confirmButtonText: '<?= __('add') ?>',
                cancelButtonText: '<?= __('cancel') ?>',
                preConfirm: () => {
                    const titleEn = document.getElementById('job-title-en').value.trim();
                    const titleAr = document.getElementById('job-title-ar').value.trim();
                    
                    if (!titleEn) {
                        Swal.showValidationMessage('<?= __('job_title_in_english_is_required') ?>');
                        return false;
                    }
                    if (!titleAr) {
                        Swal.showValidationMessage('<?= __('job_title_in_arabic_is_required') ?>');
                        return false;
                    }
                    return { titleEn, titleAr };
                }
            }).then(async (result) => {
                if (result.isConfirmed) {
                    await addJobTitle(result.value.titleEn, result.value.titleAr);
                }
            });
        }

        async function addJobTitle(titleEn, titleAr) {
            try {
                const response = await fetch('./includes/job_titles_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ 
                        action: 'add_job_title',
                        job_title_en: titleEn,
                        job_title_ar: titleAr
                    })
                });

                if (!response.ok) throw new Error('<?= __('failed_to_add_job_title') ?>');
                const data = await response.json();

                if (data.success) {
                    Swal.fire('<?= __('added') ?>', '<?= __('job_title_added_successfully') ?>', 'success');
                    loadJobTitles(); // Reload the list
                } else {
                    throw new Error(data.message || '<?= __('failed_to_add_job_title') ?>');
                }
            } catch (error) {
                Swal.fire('<?= __('error') ?>', error.message, 'error');
            }
        }

        async function showEditJobTitleModal(jobId) {
            try {
                const response = await fetch('./includes/job_titles_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ 
                        action: 'get_job_title',
                        job_id: jobId
                    })
                });

                if (!response.ok) throw new Error('<?= __('failed_to_load_job_title') ?>');
                const data = await response.json();

                if (!data.success || !data.job) {
                    Swal.fire('<?= __('error') ?>', '<?= __('job_title_not_found') ?>', 'error');
                    return;
                }

                const job = data.job;
                const result = await Swal.fire({
                    icon: 'info',
                    title: '<?= __('edit_job_title') ?>',
                    html: `
                        <div class="form-group text-left">
                            <label for="edit-job-title-en"><?= __('job_title_english') ?></label>
                            <input type="text" id="edit-job-title-en" class="form-control" value="${job.job || ''}" placeholder="<?= __('enter_job_title_in_english') ?>">
                        </div>
                        <div class="form-group text-left">
                            <label for="edit-job-title-ar"><?= __('job_title_arabic') ?></label>
                            <input type="text" id="edit-job-title-ar" class="form-control" value="${job.job_ar || ''}" placeholder="<?= __('enter_job_title_in_arabic') ?>">
                        </div>
                    `,
                    allowOutsideClick: false,
                    showCancelButton: true,
                    confirmButtonText: '<?= __('update') ?>',
                    cancelButtonText: '<?= __('cancel') ?>',
                    preConfirm: () => {
                        const titleEn = document.getElementById('edit-job-title-en').value.trim();
                        const titleAr = document.getElementById('edit-job-title-ar').value.trim();
                        
                        if (!titleEn) {
                            Swal.showValidationMessage('<?= __('job_title_in_english_is_required') ?>');
                            return false;
                        }
                        if (!titleAr) {
                            Swal.showValidationMessage('<?= __('job_title_in_arabic_is_required') ?>');
                            return false;
                        }
                        return { titleEn, titleAr };
                    }
                });

                if (result.isConfirmed) {
                    await updateJobTitle(jobId, result.value.titleEn, result.value.titleAr);
                }
            } catch (error) {
                Swal.fire('<?= __('error') ?>', error.message, 'error');
            }
        }

        async function updateJobTitle(jobId, titleEn, titleAr) {
            try {
                const response = await fetch('./includes/job_titles_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ 
                        action: 'update_job_title',
                        job_id: jobId,
                        job_title_en: titleEn,
                        job_title_ar: titleAr
                    })
                });

                if (!response.ok) throw new Error('<?= __('failed_to_update_job_title') ?>');
                const data = await response.json();

                if (data.success) {
                    Swal.fire('<?= __('updated') ?>', '<?= __('job_title_updated_successfully') ?>', 'success');
                    loadJobTitles(); // Reload the list
                } else {
                    throw new Error(data.message || '<?= __('failed_to_update_job_title') ?>');
                }
            } catch (error) {
                Swal.fire('<?= __('error') ?>', error.message, 'error');
            }
        }

        async function deleteJobTitle(jobId) {
            const result = await Swal.fire({
                title: '<?= __('delete_job_title') ?>',
                text: '<?= __('this_action_cannot_be_undone') ?>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '<?= __('yes_delete_it') ?>',
                cancelButtonText: '<?= __('cancel') ?>'
            });

            if (!result.isConfirmed) return;

            try {
                const response = await fetch('./includes/job_titles_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ 
                        action: 'delete_job_title',
                        job_id: jobId
                    })
                });

                if (!response.ok) throw new Error('<?= __('failed_to_delete_job_title') ?>');
                const data = await response.json();

                if (data.success) {
                    Swal.fire('<?= __('deleted') ?>', '<?= __('job_title_deleted_successfully') ?>', 'success');
                    loadJobTitles(); // Reload the list
                } else {
                    throw new Error(data.message || '<?= __('failed_to_delete_job_title') ?>');
                }
            } catch (error) {
                Swal.fire('<?= __('error') ?>', error.message, 'error');
            }
        }

        function filterJobTitles(searchTerm) {
            const rows = document.querySelectorAll('#job-titles-container tbody tr');
            let visibleCount = 0;

            rows.forEach(row => {
                const jobEn = row.cells[0].textContent.toLowerCase();
                const jobAr = row.cells[1].textContent.toLowerCase();
                const searchLower = searchTerm.toLowerCase();

                if (jobEn.includes(searchLower) || jobAr.includes(searchLower)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            // Show "no results" message if nothing matches
            const container = document.getElementById('job-titles-container');
            let noResultsMsg = container.querySelector('.no-results-msg');
            
            if (visibleCount === 0 && searchTerm.trim() !== '') {
                if (!noResultsMsg) {
                    noResultsMsg = document.createElement('div');
                    noResultsMsg.className = 'alert alert-info no-results-msg mt-2';
                    noResultsMsg.innerHTML = `<i class="mdi mdi-information-outline"></i> <?= __('no_job_titles_match_your_search') ?>`;
                    container.appendChild(noResultsMsg);
                }
            } else if (noResultsMsg) {
                noResultsMsg.remove();
            }
        }

        let citiesListCache = null;

        function renderLocationsSettings() {
            let formHtml = `<div class="tab-pane active" id="group-locations" role="tabpanel">`;
            formHtml += `<div class="d-flex justify-content-between align-items-center mb-3">`;
            formHtml += `<h5 class="mb-0"><?= __('locations_management', 'Locations Management') ?></h5>`;
            formHtml += `<button type="button" class="btn btn-sm btn-success" id="btn-add-location"><i class="mdi mdi-plus"></i> <?= __('add_new_location', 'Add New Location') ?></button>`;
            formHtml += `</div>`;
            formHtml += `<p class="text-muted mb-4"><?= __('manage_locations_by_city', 'Manage locations and assign each one to a city') ?></p>`;

            formHtml += `<div class="form-group mb-3">`;
            formHtml += `<input type="text" id="location-search-input" class="form-control" placeholder="<?= __('search_locations', 'Search locations or cities') ?>" style="max-width: 400px;">`;
            formHtml += `</div>`;

            formHtml += `<div id="locations-container" class="border rounded p-3 bg-light">`;
            formHtml += `<div class="text-center text-muted"><div class="spinner-border spinner-border-sm" role="status"></div><span class="ml-2"><?= __('loading') ?></span></div>`;
            formHtml += `</div>`;
            formHtml += `</div>`;
            settingsContainer.innerHTML = formHtml;

            loadLocations();

            const btnAddLocation = document.getElementById('btn-add-location');
            if (btnAddLocation) btnAddLocation.addEventListener('click', showAddLocationModal);

            const searchInput = document.getElementById('location-search-input');
            if (searchInput) searchInput.addEventListener('input', function() { filterLocations(this.value); });
        }

        async function fetchCitiesList() {
            if (citiesListCache) return citiesListCache;
            const response = await fetch('./includes/locations_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ action: 'get_cities' })
            });
            const data = await response.json();
            citiesListCache = (data.success && data.cities) ? data.cities : [];
            return citiesListCache;
        }

        function citySelectOptionsHtml(cities, selectedCityId) {
            let opts = `<option value=""><?= __('select_city', 'Select City') ?></option>`;
            cities.forEach(city => {
                const selected = (String(city.id) === String(selectedCityId)) ? 'selected' : '';
                opts += `<option value="${city.id}" ${selected}>${city.name_en}</option>`;
            });
            return opts;
        }

        async function loadLocations() {
            try {
                const response = await fetch('./includes/locations_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ action: 'get_locations' })
                });

                if (!response.ok) throw new Error('<?= __('failed_to_load_locations', 'Failed to load locations') ?>');
                const data = await response.json();

                const container = document.getElementById('locations-container');
                if (!data.success || !data.locations || data.locations.length === 0) {
                    container.innerHTML = '<p class="text-muted mb-0"><i class="mdi mdi-information-outline"></i> <?= __('no_locations_configured_yet', 'No locations configured yet.') ?></p>';
                    return;
                }

                let html = '<div class="table-responsive"><table class="table table-hover mb-0"><thead class="bg-light"><tr><th><?= __('city') ?></th><th><?= __('location_name_english', 'Location (English)') ?></th><th><?= __('location_name_arabic', 'Location (Arabic)') ?></th><th><?= __('actions') ?></th></tr></thead><tbody>';
                data.locations.forEach((loc) => {
                    html += `
                        <tr>
                            <td>${loc.city_name_en || 'N/A'}</td>
                            <td><strong>${loc.name_en || 'N/A'}</strong></td>
                            <td><strong>${loc.name_ar || 'N/A'}</strong></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-primary edit-location-btn" data-location-id="${loc.id}" title="<?= __('edit') ?>"><i class="mdi mdi-pencil"></i></button>
                                <button type="button" class="btn btn-sm btn-outline-danger delete-location-btn" data-location-id="${loc.id}" title="<?= __('delete') ?>"><i class="mdi mdi-delete"></i></button>
                            </td>
                        </tr>
                    `;
                });
                html += '</tbody></table></div>';
                container.innerHTML = html;

                container.querySelectorAll('.edit-location-btn').forEach(btn => {
                    btn.addEventListener('click', function() { showEditLocationModal(this.dataset.locationId); });
                });
                container.querySelectorAll('.delete-location-btn').forEach(btn => {
                    btn.addEventListener('click', function() { deleteLocation(this.dataset.locationId); });
                });
            } catch (error) {
                console.error('Error loading locations:', error);
                document.getElementById('locations-container').innerHTML = `<p class="text-danger"><i class="mdi mdi-alert"></i> <?= __('Error:') ?> ${error.message}</p>`;
            }
        }

        async function showAddLocationModal() {
            const cities = await fetchCitiesList();
            Swal.fire({
                icon: 'info',
                title: '<?= __('add_new_location', 'Add New Location') ?>',
                html: `
                    <div class="form-group text-left">
                        <label for="location-city"><?= __('city') ?></label>
                        <select id="location-city" class="form-control">${citySelectOptionsHtml(cities, '')}</select>
                    </div>
                    <div class="form-group text-left">
                        <label for="location-name-en"><?= __('location_name_english', 'Location (English)') ?></label>
                        <input type="text" id="location-name-en" class="form-control">
                    </div>
                    <div class="form-group text-left">
                        <label for="location-name-ar"><?= __('location_name_arabic', 'Location (Arabic)') ?></label>
                        <input type="text" id="location-name-ar" class="form-control">
                    </div>
                `,
                allowOutsideClick: false,
                showCancelButton: true,
                confirmButtonText: '<?= __('add') ?>',
                cancelButtonText: '<?= __('cancel') ?>',
                didOpen: () => {
                    $('#location-city').select2({ width: '100%', dropdownParent: $(Swal.getPopup()) });
                },
                preConfirm: () => {
                    const cityId = document.getElementById('location-city').value;
                    const nameEn = document.getElementById('location-name-en').value.trim();
                    const nameAr = document.getElementById('location-name-ar').value.trim();
                    if (!cityId) { Swal.showValidationMessage('<?= __('please_select_a_city', 'Please select a city') ?>'); return false; }
                    if (!nameEn) { Swal.showValidationMessage('<?= __('location_name_in_english_is_required', 'Location name in English is required') ?>'); return false; }
                    if (!nameAr) { Swal.showValidationMessage('<?= __('location_name_in_arabic_is_required', 'Location name in Arabic is required') ?>'); return false; }
                    return { cityId, nameEn, nameAr };
                }
            }).then(async (result) => {
                if (result.isConfirmed) await addLocation(result.value.cityId, result.value.nameEn, result.value.nameAr);
            });
        }

        async function addLocation(cityId, nameEn, nameAr) {
            try {
                const response = await fetch('./includes/locations_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ action: 'add_location', city_id: cityId, name_en: nameEn, name_ar: nameAr })
                });
                const data = await response.json();
                if (data.success) {
                    Swal.fire('<?= __('added') ?>', '<?= __('location_added_successfully', 'Location added successfully') ?>', 'success');
                    loadLocations();
                } else {
                    throw new Error(data.message || '<?= __('failed_to_add_location', 'Failed to add location') ?>');
                }
            } catch (error) {
                Swal.fire('<?= __('error') ?>', error.message, 'error');
            }
        }

        async function showEditLocationModal(locationId) {
            try {
                const [cities, response] = await Promise.all([
                    fetchCitiesList(),
                    fetch('./includes/locations_handler.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({ action: 'get_location', location_id: locationId })
                    })
                ]);

                const data = await response.json();
                if (!data.success || !data.location) {
                    Swal.fire('<?= __('error') ?>', '<?= __('location_not_found', 'Location not found') ?>', 'error');
                    return;
                }

                const loc = data.location;
                const result = await Swal.fire({
                    icon: 'info',
                    title: '<?= __('edit_location', 'Edit Location') ?>',
                    html: `
                        <div class="form-group text-left">
                            <label for="edit-location-city"><?= __('city') ?></label>
                            <select id="edit-location-city" class="form-control">${citySelectOptionsHtml(cities, loc.city_id)}</select>
                        </div>
                        <div class="form-group text-left">
                            <label for="edit-location-name-en"><?= __('location_name_english', 'Location (English)') ?></label>
                            <input type="text" id="edit-location-name-en" class="form-control" value="${loc.name_en || ''}">
                        </div>
                        <div class="form-group text-left">
                            <label for="edit-location-name-ar"><?= __('location_name_arabic', 'Location (Arabic)') ?></label>
                            <input type="text" id="edit-location-name-ar" class="form-control" value="${loc.name_ar || ''}">
                        </div>
                    `,
                    allowOutsideClick: false,
                    showCancelButton: true,
                    confirmButtonText: '<?= __('update') ?>',
                    cancelButtonText: '<?= __('cancel') ?>',
                    didOpen: () => {
                        $('#edit-location-city').select2({ width: '100%', dropdownParent: $(Swal.getPopup()) });
                    },
                    preConfirm: () => {
                        const cityId = document.getElementById('edit-location-city').value;
                        const nameEn = document.getElementById('edit-location-name-en').value.trim();
                        const nameAr = document.getElementById('edit-location-name-ar').value.trim();
                        if (!cityId) { Swal.showValidationMessage('<?= __('please_select_a_city', 'Please select a city') ?>'); return false; }
                        if (!nameEn) { Swal.showValidationMessage('<?= __('location_name_in_english_is_required', 'Location name in English is required') ?>'); return false; }
                        if (!nameAr) { Swal.showValidationMessage('<?= __('location_name_in_arabic_is_required', 'Location name in Arabic is required') ?>'); return false; }
                        return { cityId, nameEn, nameAr };
                    }
                });

                if (result.isConfirmed) {
                    await updateLocation(locationId, result.value.cityId, result.value.nameEn, result.value.nameAr);
                }
            } catch (error) {
                Swal.fire('<?= __('error') ?>', error.message, 'error');
            }
        }

        async function updateLocation(locationId, cityId, nameEn, nameAr) {
            try {
                const response = await fetch('./includes/locations_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ action: 'update_location', location_id: locationId, city_id: cityId, name_en: nameEn, name_ar: nameAr })
                });
                const data = await response.json();
                if (data.success) {
                    Swal.fire('<?= __('updated') ?>', '<?= __('location_updated_successfully', 'Location updated successfully') ?>', 'success');
                    loadLocations();
                } else {
                    throw new Error(data.message || '<?= __('failed_to_update_location', 'Failed to update location') ?>');
                }
            } catch (error) {
                Swal.fire('<?= __('error') ?>', error.message, 'error');
            }
        }

        async function deleteLocation(locationId) {
            const result = await Swal.fire({
                title: '<?= __('delete_location', 'Delete Location') ?>',
                text: '<?= __('this_action_cannot_be_undone') ?>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '<?= __('yes_delete_it') ?>',
                cancelButtonText: '<?= __('cancel') ?>'
            });
            if (!result.isConfirmed) return;

            try {
                const response = await fetch('./includes/locations_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ action: 'delete_location', location_id: locationId })
                });
                const data = await response.json();
                if (data.success) {
                    Swal.fire('<?= __('deleted') ?>', '<?= __('location_deleted_successfully', 'Location deleted successfully') ?>', 'success');
                    loadLocations();
                } else {
                    throw new Error(data.message || '<?= __('failed_to_delete_location', 'Failed to delete location') ?>');
                }
            } catch (error) {
                Swal.fire('<?= __('error') ?>', error.message, 'error');
            }
        }

        function filterLocations(searchTerm) {
            const rows = document.querySelectorAll('#locations-container tbody tr');
            const searchLower = searchTerm.toLowerCase();
            let visibleCount = 0;
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchLower)) { row.style.display = ''; visibleCount++; }
                else { row.style.display = 'none'; }
            });
            const container = document.getElementById('locations-container');
            let noResultsMsg = container.querySelector('.no-results-msg');
            if (visibleCount === 0 && searchTerm.trim() !== '') {
                if (!noResultsMsg) {
                    noResultsMsg = document.createElement('div');
                    noResultsMsg.className = 'alert alert-info no-results-msg mt-2';
                    noResultsMsg.innerHTML = `<i class="mdi mdi-information-outline"></i> <?= __('no_locations_match_your_search', 'No locations match your search') ?>`;
                    container.appendChild(noResultsMsg);
                }
            } else if (noResultsMsg) {
                noResultsMsg.remove();
            }
        }

        let departmentsListCache = null;

        function renderSubDepartmentsSettings() {
            let formHtml = `<div class="tab-pane active" id="group-sub_departments" role="tabpanel">`;
            formHtml += `<div class="d-flex justify-content-between align-items-center mb-3">`;
            formHtml += `<h5 class="mb-0"><?= __('sub_departments_management', 'Sub-Departments Management') ?></h5>`;
            formHtml += `<button type="button" class="btn btn-sm btn-success" id="btn-add-sub-department"><i class="mdi mdi-plus"></i> <?= __('add_new_sub_department', 'Add New Sub-Department') ?></button>`;
            formHtml += `</div>`;
            formHtml += `<p class="text-muted mb-4"><?= __('manage_sub_departments_by_department', 'Manage sub-departments and assign each one to a department') ?></p>`;

            formHtml += `<div class="form-group mb-3">`;
            formHtml += `<input type="text" id="sub-department-search-input" class="form-control" placeholder="<?= __('search_sub_departments', 'Search sub-departments or departments') ?>" style="max-width: 400px;">`;
            formHtml += `</div>`;

            formHtml += `<div id="sub-departments-container" class="border rounded p-3 bg-light">`;
            formHtml += `<div class="text-center text-muted"><div class="spinner-border spinner-border-sm" role="status"></div><span class="ml-2"><?= __('loading') ?></span></div>`;
            formHtml += `</div>`;
            formHtml += `</div>`;
            settingsContainer.innerHTML = formHtml;

            loadSubDepartments();

            const btnAdd = document.getElementById('btn-add-sub-department');
            if (btnAdd) btnAdd.addEventListener('click', showAddSubDepartmentModal);

            const searchInput = document.getElementById('sub-department-search-input');
            if (searchInput) searchInput.addEventListener('input', function() { filterSubDepartments(this.value); });
        }

        async function fetchDepartmentsList() {
            if (departmentsListCache) return departmentsListCache;
            const response = await fetch('./includes/sub_departments_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ action: 'get_departments' })
            });
            const data = await response.json();
            departmentsListCache = (data.success && data.departments) ? data.departments : [];
            return departmentsListCache;
        }

        function departmentSelectOptionsHtml(departments, selectedDeptId) {
            let opts = `<option value=""><?= __('select_department', 'Select Department') ?></option>`;
            departments.forEach(dept => {
                const selected = (String(dept.id) === String(selectedDeptId)) ? 'selected' : '';
                opts += `<option value="${dept.id}" ${selected}>${dept.dep_nme}</option>`;
            });
            return opts;
        }

        async function loadSubDepartments() {
            try {
                const response = await fetch('./includes/sub_departments_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ action: 'get_sub_departments' })
                });

                if (!response.ok) throw new Error('<?= __('failed_to_load_sub_departments', 'Failed to load sub-departments') ?>');
                const data = await response.json();

                const container = document.getElementById('sub-departments-container');
                if (!data.success || !data.sub_departments || data.sub_departments.length === 0) {
                    container.innerHTML = '<p class="text-muted mb-0"><i class="mdi mdi-information-outline"></i> <?= __('no_sub_departments_configured_yet', 'No sub-departments configured yet.') ?></p>';
                    return;
                }

                let html = '<div class="table-responsive"><table class="table table-hover mb-0"><thead class="bg-light"><tr><th><?= __('department_label') ?></th><th><?= __('sub_department_name_english', 'Sub-Department (English)') ?></th><th><?= __('sub_department_name_arabic', 'Sub-Department (Arabic)') ?></th><th><?= __('actions') ?></th></tr></thead><tbody>';
                data.sub_departments.forEach((sd) => {
                    html += `
                        <tr>
                            <td>${sd.dep_nme || 'N/A'}</td>
                            <td><strong>${sd.name_en || 'N/A'}</strong></td>
                            <td><strong>${sd.name_ar || 'N/A'}</strong></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-primary edit-sub-department-btn" data-sub-dept-id="${sd.id}" title="<?= __('edit') ?>"><i class="mdi mdi-pencil"></i></button>
                                <button type="button" class="btn btn-sm btn-outline-danger delete-sub-department-btn" data-sub-dept-id="${sd.id}" title="<?= __('delete') ?>"><i class="mdi mdi-delete"></i></button>
                            </td>
                        </tr>
                    `;
                });
                html += '</tbody></table></div>';
                container.innerHTML = html;

                container.querySelectorAll('.edit-sub-department-btn').forEach(btn => {
                    btn.addEventListener('click', function() { showEditSubDepartmentModal(this.dataset.subDeptId); });
                });
                container.querySelectorAll('.delete-sub-department-btn').forEach(btn => {
                    btn.addEventListener('click', function() { deleteSubDepartment(this.dataset.subDeptId); });
                });
            } catch (error) {
                console.error('Error loading sub-departments:', error);
                document.getElementById('sub-departments-container').innerHTML = `<p class="text-danger"><i class="mdi mdi-alert"></i> <?= __('Error:') ?> ${error.message}</p>`;
            }
        }

        async function showAddSubDepartmentModal() {
            const departments = await fetchDepartmentsList();
            Swal.fire({
                icon: 'info',
                title: '<?= __('add_new_sub_department', 'Add New Sub-Department') ?>',
                html: `
                    <div class="form-group text-left">
                        <label for="sub-department-dept"><?= __('department_label') ?></label>
                        <select id="sub-department-dept" class="form-control">${departmentSelectOptionsHtml(departments, '')}</select>
                    </div>
                    <div class="form-group text-left">
                        <label for="sub-department-name-en"><?= __('sub_department_name_english', 'Sub-Department (English)') ?></label>
                        <input type="text" id="sub-department-name-en" class="form-control">
                    </div>
                    <div class="form-group text-left">
                        <label for="sub-department-name-ar"><?= __('sub_department_name_arabic', 'Sub-Department (Arabic)') ?></label>
                        <input type="text" id="sub-department-name-ar" class="form-control">
                    </div>
                `,
                allowOutsideClick: false,
                showCancelButton: true,
                confirmButtonText: '<?= __('add') ?>',
                cancelButtonText: '<?= __('cancel') ?>',
                didOpen: () => {
                    $('#sub-department-dept').select2({ width: '100%', dropdownParent: $(Swal.getPopup()) });
                },
                preConfirm: () => {
                    const departmentId = document.getElementById('sub-department-dept').value;
                    const nameEn = document.getElementById('sub-department-name-en').value.trim();
                    const nameAr = document.getElementById('sub-department-name-ar').value.trim();
                    if (!departmentId) { Swal.showValidationMessage('<?= __('please_select_a_department', 'Please select a department') ?>'); return false; }
                    if (!nameEn) { Swal.showValidationMessage('<?= __('sub_department_name_in_english_is_required', 'Sub-department name in English is required') ?>'); return false; }
                    if (!nameAr) { Swal.showValidationMessage('<?= __('sub_department_name_in_arabic_is_required', 'Sub-department name in Arabic is required') ?>'); return false; }
                    return { departmentId, nameEn, nameAr };
                }
            }).then(async (result) => {
                if (result.isConfirmed) await addSubDepartment(result.value.departmentId, result.value.nameEn, result.value.nameAr);
            });
        }

        async function addSubDepartment(departmentId, nameEn, nameAr) {
            try {
                const response = await fetch('./includes/sub_departments_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ action: 'add_sub_department', department_id: departmentId, name_en: nameEn, name_ar: nameAr })
                });
                const data = await response.json();
                if (data.success) {
                    Swal.fire('<?= __('added') ?>', '<?= __('sub_department_added_successfully', 'Sub-department added successfully') ?>', 'success');
                    loadSubDepartments();
                } else {
                    throw new Error(data.message || '<?= __('failed_to_add_sub_department', 'Failed to add sub-department') ?>');
                }
            } catch (error) {
                Swal.fire('<?= __('error') ?>', error.message, 'error');
            }
        }

        async function showEditSubDepartmentModal(subDeptId) {
            try {
                const [departments, response] = await Promise.all([
                    fetchDepartmentsList(),
                    fetch('./includes/sub_departments_handler.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({ action: 'get_sub_department', sub_dept_id: subDeptId })
                    })
                ]);

                const data = await response.json();
                if (!data.success || !data.sub_department) {
                    Swal.fire('<?= __('error') ?>', '<?= __('sub_department_not_found', 'Sub-department not found') ?>', 'error');
                    return;
                }

                const sd = data.sub_department;
                const result = await Swal.fire({
                    icon: 'info',
                    title: '<?= __('edit_sub_department', 'Edit Sub-Department') ?>',
                    html: `
                        <div class="form-group text-left">
                            <label for="edit-sub-department-dept"><?= __('department_label') ?></label>
                            <select id="edit-sub-department-dept" class="form-control">${departmentSelectOptionsHtml(departments, sd.department_id)}</select>
                        </div>
                        <div class="form-group text-left">
                            <label for="edit-sub-department-name-en"><?= __('sub_department_name_english', 'Sub-Department (English)') ?></label>
                            <input type="text" id="edit-sub-department-name-en" class="form-control" value="${sd.name_en || ''}">
                        </div>
                        <div class="form-group text-left">
                            <label for="edit-sub-department-name-ar"><?= __('sub_department_name_arabic', 'Sub-Department (Arabic)') ?></label>
                            <input type="text" id="edit-sub-department-name-ar" class="form-control" value="${sd.name_ar || ''}">
                        </div>
                    `,
                    allowOutsideClick: false,
                    showCancelButton: true,
                    confirmButtonText: '<?= __('update') ?>',
                    cancelButtonText: '<?= __('cancel') ?>',
                    didOpen: () => {
                        $('#edit-sub-department-dept').select2({ width: '100%', dropdownParent: $(Swal.getPopup()) });
                    },
                    preConfirm: () => {
                        const departmentId = document.getElementById('edit-sub-department-dept').value;
                        const nameEn = document.getElementById('edit-sub-department-name-en').value.trim();
                        const nameAr = document.getElementById('edit-sub-department-name-ar').value.trim();
                        if (!departmentId) { Swal.showValidationMessage('<?= __('please_select_a_department', 'Please select a department') ?>'); return false; }
                        if (!nameEn) { Swal.showValidationMessage('<?= __('sub_department_name_in_english_is_required', 'Sub-department name in English is required') ?>'); return false; }
                        if (!nameAr) { Swal.showValidationMessage('<?= __('sub_department_name_in_arabic_is_required', 'Sub-department name in Arabic is required') ?>'); return false; }
                        return { departmentId, nameEn, nameAr };
                    }
                });

                if (result.isConfirmed) {
                    await updateSubDepartment(subDeptId, result.value.departmentId, result.value.nameEn, result.value.nameAr);
                }
            } catch (error) {
                Swal.fire('<?= __('error') ?>', error.message, 'error');
            }
        }

        async function updateSubDepartment(subDeptId, departmentId, nameEn, nameAr) {
            try {
                const response = await fetch('./includes/sub_departments_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ action: 'update_sub_department', sub_dept_id: subDeptId, department_id: departmentId, name_en: nameEn, name_ar: nameAr })
                });
                const data = await response.json();
                if (data.success) {
                    Swal.fire('<?= __('updated') ?>', '<?= __('sub_department_updated_successfully', 'Sub-department updated successfully') ?>', 'success');
                    loadSubDepartments();
                } else {
                    throw new Error(data.message || '<?= __('failed_to_update_sub_department', 'Failed to update sub-department') ?>');
                }
            } catch (error) {
                Swal.fire('<?= __('error') ?>', error.message, 'error');
            }
        }

        async function deleteSubDepartment(subDeptId) {
            const result = await Swal.fire({
                title: '<?= __('delete_sub_department', 'Delete Sub-Department') ?>',
                text: '<?= __('this_action_cannot_be_undone') ?>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '<?= __('yes_delete_it') ?>',
                cancelButtonText: '<?= __('cancel') ?>'
            });
            if (!result.isConfirmed) return;

            try {
                const response = await fetch('./includes/sub_departments_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ action: 'delete_sub_department', sub_dept_id: subDeptId })
                });
                const data = await response.json();
                if (data.success) {
                    Swal.fire('<?= __('deleted') ?>', '<?= __('sub_department_deleted_successfully', 'Sub-department deleted successfully') ?>', 'success');
                    loadSubDepartments();
                } else {
                    throw new Error(data.message || '<?= __('failed_to_delete_sub_department', 'Failed to delete sub-department') ?>');
                }
            } catch (error) {
                Swal.fire('<?= __('error') ?>', error.message, 'error');
            }
        }

        function filterSubDepartments(searchTerm) {
            const rows = document.querySelectorAll('#sub-departments-container tbody tr');
            const searchLower = searchTerm.toLowerCase();
            let visibleCount = 0;
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchLower)) { row.style.display = ''; visibleCount++; }
                else { row.style.display = 'none'; }
            });
            const container = document.getElementById('sub-departments-container');
            let noResultsMsg = container.querySelector('.no-results-msg');
            if (visibleCount === 0 && searchTerm.trim() !== '') {
                if (!noResultsMsg) {
                    noResultsMsg = document.createElement('div');
                    noResultsMsg.className = 'alert alert-info no-results-msg mt-2';
                    noResultsMsg.innerHTML = `<i class="mdi mdi-information-outline"></i> <?= __('no_sub_departments_match_your_search', 'No sub-departments match your search') ?>`;
                    container.appendChild(noResultsMsg);
                }
            } else if (noResultsMsg) {
                noResultsMsg.remove();
            }
        }

        function renderDepartmentsSettings() {
            let formHtml = `<div class="tab-pane active" id="group-departments" role="tabpanel">`;
            formHtml += `<div class="d-flex justify-content-between align-items-center mb-3">`;
            formHtml += `<h5 class="mb-0"><?= __('department_management', 'Department Management') ?></h5>`;
            formHtml += `<button type="button" class="btn btn-sm btn-success" id="btn-add-department"><i class="mdi mdi-plus"></i> <?= __('add_new_department', 'Add New Department') ?></button>`;
            formHtml += `</div>`;
            formHtml += `<p class="text-muted mb-4"><?= __('manage_departments_in_english_and_arabic', 'Manage departments in English and Arabic.') ?></p>`;

            formHtml += `<div class="form-group mb-3">`;
            formHtml += `<input type="text" id="department-search-input" class="form-control" placeholder="<?= __('search_departments_english_or_arabic', 'Search departments (English or Arabic)...') ?>" style="max-width: 400px;">`;
            formHtml += `<small class="form-text text-muted mt-1"><?= __('search_by_department_in_english_or_arabic', 'Search by department in English or Arabic') ?></small>`;
            formHtml += `</div>`;

            formHtml += `<div id="departments-container" class="border rounded p-3 bg-light">`;
            formHtml += `<div class="text-center text-muted">`;
            formHtml += `<div class="spinner-border spinner-border-sm" role="status"></div>`;
            formHtml += `<span class="ml-2"><?= __('loading') ?></span>`;
            formHtml += `</div>`;
            formHtml += `</div>`;
            formHtml += `</div>`;
            settingsContainer.innerHTML = formHtml;

            loadDepartments();

            const btnAddDepartment = document.getElementById('btn-add-department');
            if (btnAddDepartment) {
                btnAddDepartment.addEventListener('click', showAddDepartmentModal);
            }

            const searchInput = document.getElementById('department-search-input');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    filterDepartments(this.value);
                });
            }
        }

        async function loadDepartments() {
            try {
                const response = await fetch('./includes/departments_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ action: 'get_departments' })
                });

                if (!response.ok) throw new Error('<?= __('failed_to_load_departments', 'Failed to load departments') ?>');
                const data = await response.json();

                const container = document.getElementById('departments-container');
                if (!data.success || !data.departments || data.departments.length === 0) {
                    container.innerHTML = '<p class="text-muted mb-0"><i class="mdi mdi-information-outline"></i> <?= __('no_departments_configured_yet', 'No departments configured yet') ?></p>';
                    return;
                }

                let departmentsHtml = '<div class="table-responsive"><table class="table table-hover mb-0"><thead class="bg-light"><tr><th><?= __('department_english', 'Department (English)') ?></th><th><?= __('department_arabic', 'Department (Arabic)') ?></th><th><?= __('department_color', 'Color') ?></th><th><?= __('actions') ?></th></tr></thead><tbody>';
                data.departments.forEach((department) => {
                    const rawColor = String(department.dept_clr || '').trim();
                    const allowedColors = ['custom', 'purple', 'primary', 'success'];
                    const safeColorClass = allowedColors.includes(rawColor.toLowerCase()) ? rawColor.toLowerCase() : 'custom';
                    departmentsHtml += `
                        <tr>
                            <td><strong>${department.dep_nme || 'N/A'}</strong></td>
                            <td><strong>${department.dep_nme_ar || 'N/A'}</strong></td>
                            <td>
                                <span class="badge ${safeColorClass}">${safeColorClass}</span>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-primary edit-department-btn" data-department-id="${department.id}" title="<?= __('edit') ?>">
                                    <i class="mdi mdi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger delete-department-btn" data-department-id="${department.id}" title="<?= __('delete') ?>">
                                    <i class="mdi mdi-delete"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });
                departmentsHtml += '</tbody></table></div>';
                container.innerHTML = departmentsHtml;

                container.querySelectorAll('.edit-department-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        showEditDepartmentModal(this.dataset.departmentId);
                    });
                });

                container.querySelectorAll('.delete-department-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        deleteDepartment(this.dataset.departmentId);
                    });
                });

            } catch (error) {
                console.error('Error loading departments:', error);
                const container = document.getElementById('departments-container');
                container.innerHTML = `<p class="text-danger"><i class="mdi mdi-alert"></i> <?= __('Error:') ?> ${error.message}</p>`;
            }
        }

        function showAddDepartmentModal() {
            Swal.fire({
                icon: 'info',
                title: '<?= __('add_new_department', 'Add New Department') ?>',
                html: `
                    <div class="form-group text-left">
                        <label for="department-title-en"><?= __('department_english', 'Department (English)') ?></label>
                        <input type="text" id="department-title-en" class="form-control" placeholder="<?= __('enter_department_in_english', 'Enter department in English') ?>">
                    </div>
                    <div class="form-group text-left">
                        <label for="department-title-ar"><?= __('department_arabic', 'Department (Arabic)') ?></label>
                        <input type="text" id="department-title-ar" class="form-control" placeholder="<?= __('enter_department_in_arabic', 'Enter department in Arabic') ?>">
                    </div>
                    <div class="form-group text-left">
                        <label for="department-color"><?= __('department_color', 'Color') ?></label>
                        <select id="department-color" class="form-control">
                            <option value="custom">custom</option>
                            <option value="purple">purple</option>
                            <option value="primary">primary</option>
                            <option value="success">success</option>
                        </select>
                    </div>
                `,
                allowOutsideClick: false,
                showCancelButton: true,
                confirmButtonText: '<?= __('add') ?>',
                cancelButtonText: '<?= __('cancel') ?>',
                preConfirm: () => {
                    const titleEn = document.getElementById('department-title-en').value.trim();
                    const titleAr = document.getElementById('department-title-ar').value.trim();
                    const color = (document.getElementById('department-color').value || '').trim().toLowerCase();

                    if (!titleEn) {
                        Swal.showValidationMessage('<?= __('department_in_english_is_required', 'Department name in English is required') ?>');
                        return false;
                    }
                    if (!titleAr) {
                        Swal.showValidationMessage('<?= __('department_in_arabic_is_required', 'Department name in Arabic is required') ?>');
                        return false;
                    }
                    if (!['custom', 'purple', 'primary', 'success'].includes(color)) {
                        Swal.showValidationMessage('<?= __('invalid_department_color', 'Please select a valid department color') ?>');
                        return false;
                    }
                    return { titleEn, titleAr, color };
                }
            }).then(async (result) => {
                if (result.isConfirmed) {
                    await addDepartment(result.value.titleEn, result.value.titleAr, result.value.color);
                }
            });
        }

        async function addDepartment(titleEn, titleAr, color) {
            try {
                const response = await fetch('./includes/departments_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'add_department',
                        department_en: titleEn,
                        department_ar: titleAr,
                        department_color: color
                    })
                });

                if (!response.ok) throw new Error('<?= __('failed_to_add_department', 'Failed to add department') ?>');
                const data = await response.json();

                if (data.success) {
                    Swal.fire('<?= __('added') ?>', '<?= __('department_added_successfully', 'Department added successfully') ?>', 'success');
                    loadDepartments();
                } else {
                    throw new Error(data.message || '<?= __('failed_to_add_department', 'Failed to add department') ?>');
                }
            } catch (error) {
                Swal.fire('<?= __('error') ?>', error.message, 'error');
            }
        }

        async function showEditDepartmentModal(departmentId) {
            try {
                const response = await fetch('./includes/departments_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'get_department',
                        department_id: departmentId
                    })
                });

                if (!response.ok) throw new Error('<?= __('failed_to_load_department', 'Failed to load department') ?>');
                const data = await response.json();

                if (!data.success || !data.department) {
                    Swal.fire('<?= __('error') ?>', '<?= __('department_not_found', 'Department not found') ?>', 'error');
                    return;
                }

                const department = data.department;
                const result = await Swal.fire({
                    icon: 'info',
                    title: '<?= __('edit_department', 'Edit Department') ?>',
                    html: `
                        <div class="form-group text-left">
                            <label for="edit-department-title-en"><?= __('department_english', 'Department (English)') ?></label>
                            <input type="text" id="edit-department-title-en" class="form-control" value="${department.dep_nme || ''}" placeholder="<?= __('enter_department_in_english', 'Enter department in English') ?>">
                        </div>
                        <div class="form-group text-left">
                            <label for="edit-department-title-ar"><?= __('department_arabic', 'Department (Arabic)') ?></label>
                            <input type="text" id="edit-department-title-ar" class="form-control" value="${department.dep_nme_ar || ''}" placeholder="<?= __('enter_department_in_arabic', 'Enter department in Arabic') ?>">
                        </div>
                        <div class="form-group text-left">
                            <label for="edit-department-color"><?= __('department_color', 'Color') ?></label>
                            <select id="edit-department-color" class="form-control">
                                <option value="custom" ${(String(department.dept_clr || '').trim().toLowerCase() === 'custom' || !String(department.dept_clr || '').trim()) ? 'selected' : ''}>custom</option>
                                <option value="purple" ${String(department.dept_clr || '').trim().toLowerCase() === 'purple' ? 'selected' : ''}>purple</option>
                                <option value="primary" ${String(department.dept_clr || '').trim().toLowerCase() === 'primary' ? 'selected' : ''}>primary</option>
                                <option value="success" ${String(department.dept_clr || '').trim().toLowerCase() === 'success' ? 'selected' : ''}>success</option>
                            </select>
                        </div>
                    `,
                    allowOutsideClick: false,
                    showCancelButton: true,
                    confirmButtonText: '<?= __('update') ?>',
                    cancelButtonText: '<?= __('cancel') ?>',
                    preConfirm: () => {
                        const titleEn = document.getElementById('edit-department-title-en').value.trim();
                        const titleAr = document.getElementById('edit-department-title-ar').value.trim();
                        const color = (document.getElementById('edit-department-color').value || '').trim().toLowerCase();

                        if (!titleEn) {
                            Swal.showValidationMessage('<?= __('department_in_english_is_required', 'Department name in English is required') ?>');
                            return false;
                        }
                        if (!titleAr) {
                            Swal.showValidationMessage('<?= __('department_in_arabic_is_required', 'Department name in Arabic is required') ?>');
                            return false;
                        }
                        if (!['custom', 'purple', 'primary', 'success'].includes(color)) {
                            Swal.showValidationMessage('<?= __('invalid_department_color', 'Please select a valid department color') ?>');
                            return false;
                        }
                        return { titleEn, titleAr, color };
                    }
                });

                if (result.isConfirmed) {
                        await updateDepartment(departmentId, result.value.titleEn, result.value.titleAr, result.value.color);
                }
            } catch (error) {
                Swal.fire('<?= __('error') ?>', error.message, 'error');
            }
        }

            async function updateDepartment(departmentId, titleEn, titleAr, color) {
            try {
                const response = await fetch('./includes/departments_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'update_department',
                        department_id: departmentId,
                        department_en: titleEn,
                            department_ar: titleAr,
                            department_color: color
                    })
                });

                if (!response.ok) throw new Error('<?= __('failed_to_update_department', 'Failed to update department') ?>');
                const data = await response.json();

                if (data.success) {
                    Swal.fire('<?= __('updated') ?>', '<?= __('department_updated_successfully', 'Department updated successfully') ?>', 'success');
                    loadDepartments();
                } else {
                    throw new Error(data.message || '<?= __('failed_to_update_department', 'Failed to update department') ?>');
                }
            } catch (error) {
                Swal.fire('<?= __('error') ?>', error.message, 'error');
            }
        }

        async function deleteDepartment(departmentId) {
            const result = await Swal.fire({
                title: '<?= __('delete_department', 'Delete Department') ?>',
                text: '<?= __('this_action_cannot_be_undone') ?>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '<?= __('yes_delete_it') ?>',
                cancelButtonText: '<?= __('cancel') ?>'
            });

            if (!result.isConfirmed) return;

            try {
                const response = await fetch('./includes/departments_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'delete_department',
                        department_id: departmentId
                    })
                });

                if (!response.ok) throw new Error('<?= __('failed_to_delete_department', 'Failed to delete department') ?>');
                const data = await response.json();

                if (data.success) {
                    Swal.fire('<?= __('deleted') ?>', '<?= __('department_deleted_successfully', 'Department deleted successfully') ?>', 'success');
                    loadDepartments();
                } else {
                    throw new Error(data.message || '<?= __('failed_to_delete_department', 'Failed to delete department') ?>');
                }
            } catch (error) {
                Swal.fire('<?= __('error') ?>', error.message, 'error');
            }
        }

        function filterDepartments(searchTerm) {
            const rows = document.querySelectorAll('#departments-container tbody tr');
            let visibleCount = 0;

            rows.forEach(row => {
                const depEn = row.cells[0].textContent.toLowerCase();
                const depAr = row.cells[1].textContent.toLowerCase();
                const depColor = row.cells[2].textContent.toLowerCase();
                const searchLower = searchTerm.toLowerCase();

                if (depEn.includes(searchLower) || depAr.includes(searchLower) || depColor.includes(searchLower)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            const container = document.getElementById('departments-container');
            let noResultsMsg = container.querySelector('.no-results-msg');

            if (visibleCount === 0 && searchTerm.trim() !== '') {
                if (!noResultsMsg) {
                    noResultsMsg = document.createElement('div');
                    noResultsMsg.className = 'alert alert-info no-results-msg mt-2';
                    noResultsMsg.innerHTML = `<i class="mdi mdi-information-outline"></i> <?= __('no_departments_match_your_search', 'No departments match your search') ?>`;
                    container.appendChild(noResultsMsg);
                }
            } else if (noResultsMsg) {
                noResultsMsg.remove();
            }
        }

        function renderApprovalChainSettings() {
            //* const defaultRequestTypes = [
            //*     { id: 'vacation_request', name: '<?//= __('vacation_request') ?>', description: '<?//= __('annual_vacation_and_fly_vacation_approval_chain') ?>' },
            //*     { id: 'excuse_leave', name: '<?//= __('excuse_leave') ?>', description: '<?//= __('sick_leave_exam_leave_and_other_excuse_types') ?>' },
            //*     { id: 'loan_request', name: '<?//= __('loan_request') ?>', description: '<?//= __('employee_loan_application_approval_chain') ?>' },
            //*     { id: 'settlement', name: '<?//= __('settlement_payment') ?>', description: '<?//= __('settlement_payment_processing_approval_chain_after_request_final_approval') ?>' },
            //*     { id: 'resignation_request', name: '<?//= __('resignation_request') ?>', description: '<?//= __('employee_resignation_approval_chain') ?>' },
            //*     { id: 'rejoin_request', name: '<?//= __('rejoin_request') ?>', description: '<?//= __('employee_rejoin_after_resignation_approval_chain') ?>' }
            //* ];

            // Fetch all request types including custom ones
            fetch('./includes/approval_chain_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ action: 'get_all_request_types' })
            })
            .then(response => response.json())
            .then(data => {
                const requestTypes = data.success && Array.isArray(data.types) ? data.types : defaultRequestTypes;
                //* const requestTypes = data.success && Array.isArray(data.types) ? data.types : defaultRequestTypes;
                // Filter out request types you want to skip from the UI
                const skipRequestTypes = ['smart_request', 'general_request']; // Add any request types to skip
                const filteredTypes = requestTypes.filter(type => !skipRequestTypes.includes(type.id));
                
                renderApprovalChainUI(filteredTypes);
            })
            .catch(error => {
                console.error('Error loading request types:', error);
                //* renderApprovalChainUI(defaultRequestTypes);
            });
        }

        function renderApprovalChainUI(requestTypes) {
            let formHtml = `<div class="tab-pane active" id="group-approval" role="tabpanel">`;
            formHtml += `<div class="d-flex justify-content-between align-items-center mb-3">`;
            formHtml += `<h5 class="mb-0"><?= __('approval_chain_configuration') ?></h5>`;
            formHtml += `<button type="button" class="btn btn-sm btn-success" id="btn-add-request-type"><i class="mdi mdi-plus"></i> <?= __('add_new_request_type') ?></button>`;
            formHtml += `</div>`;
            formHtml += `<p class="text-muted mb-4"><?= __('configure_approval_workflow') ?></p>`;

            requestTypes.forEach(requestType => {
                formHtml += `
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">
                                <i class="mdi mdi-check-circle-outline mr-2"></i>${translateText(requestType.name)}
                                <small class="text-muted ml-2">${translateText(requestType.description)}</small>
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label><?= __('approval_steps_in_order') ?></label>
                                <div id="approval-chain-${requestType.id}" class="approval-chain-container border rounded p-3 bg-light">
                                    <div class="text-center text-muted">
                                        <div class="spinner-border spinner-border-sm" role="status"></div>
                                        <span class="ml-2"><?= __('loading') ?></span>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary mt-2 add-approver-btn" data-request-type="${requestType.id}">
                                    <i class="mdi mdi-plus"></i> <?= __('add_approver') ?>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });

            formHtml += `</div>`;
            settingsContainer.innerHTML = formHtml;

            // Load approval chains for each request type
            requestTypes.forEach(requestType => {
                loadApprovalChain(requestType.id);
            });

            // Attach event listeners for "Add Approver" buttons
            document.querySelectorAll('.add-approver-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const requestType = this.dataset.requestType;
                    showAddApproverModal(requestType);
                });
            });

            // Attach event listener for "Add New Request Type" button
            const btnAddRequestType = document.getElementById('btn-add-request-type');
            if (btnAddRequestType) {
                btnAddRequestType.addEventListener('click', showAddNewRequestTypeModal);
            }
        }

        async function loadApprovalChain(requestType) {
            try {
                const response = await fetch('./includes/approval_chain_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ 
                        action: 'get_approval_chain', 
                        request_type: requestType 
                    })
                });

                if (!response.ok) throw new Error('<?= __('failed_to_load_approval_chain') ?>');
                const data = await response.json();

                const container = document.getElementById(`approval-chain-${requestType}`);
                if (!data.success || !data.chain || data.chain.length === 0) {
                    container.innerHTML = '<p class="text-muted mb-0"><i class="mdi mdi-information-outline"></i> <?= __('no_approval_steps_configured_yet') ?></p>';
                    return;
                }

                let chainHtml = '<div class="approval-steps">';
                data.chain.forEach((step, index) => {
                    chainHtml += `
                        <div class="approval-step d-flex align-items-center justify-content-between p-2 mb-2 bg-white border rounded" data-level="${step.level}" data-role="${step.user_type}">
                            <div class="d-flex align-items-center">
                                <span class="badge badge-primary mr-2"><?= __('level') ?> ${step.level}</span>
                                <span class="font-weight-bold">${translateText(step.role_label)}</span>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger remove-approver-btn" data-request-type="${requestType}" data-level="${step.level}">
                                <i class="mdi mdi-delete"></i>
                            </button>
                        </div>
                    `;
                });
                chainHtml += '</div>';
                container.innerHTML = chainHtml;

                // Attach remove button listeners
                container.querySelectorAll('.remove-approver-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        removeApprovalStep(this.dataset.requestType, this.dataset.level);
                    });
                });

            } catch (error) {
                console.error('Error loading approval chain:', error);
                const container = document.getElementById(`approval-chain-${requestType}`);
                container.innerHTML = `<p class="text-danger"><i class="mdi mdi-alert"></i> <?= __('Error:') ?> ${error.message}</p>`;
            }
        }

        function showAddApproverModal(requestType) {
            Swal.fire({
                icon: 'info',
                title: '<?= __('add_approver') ?>',
                html: `
                    <div class="form-group text-left">
                        <label for="approver-role"><?= __('select_approver_role') ?></label>
                        <select id="approver-role" class="form-control">
                            <option value="">-- <?= __('select_role') ?> --</option>
                            <option value="administrator"><?= __('administrator') ?></option>
                            <option value="gm"><?= __('general_manager_gm') ?></option>
                            <option value="hr_senior_bp"><?= __('hr_senior_bp') ?></option>
                            <option value="hr_operations"><?= __('hr_operations') ?></option>
                            <option value="hr_supervisor"><?= __('hr_supervisor') ?></option>
                            <option value="hr_recruitment"><?= __('hr_recruitment') ?></option>
                            <option value="hr_payroll"><?= __('hr_payroll') ?></option>
                            <option value="hr"><?= __('hr_manager') ?></option>
                            <option value="finance_officer"><?= __('finance_officer') ?></option>
                            <option value="finance"><?= __('finance_manager') ?></option>
                            <option value="auditor"><?= __('auditor') ?></option>
                            <option value="gr_officer"><?= __('gr_officer') ?></option>
                            <option value="it"><?= __('it_manager') ?></option>
                            <option value="dept_user"><?= __('department_user') ?></option>
                            <option value="assistant"><?= __('assistant') ?></option>
                            <option value="direct_supervisor"><?= __('direct_supervisor') ?></option>
                            <option value="dept_manager"><?= __('department_manager') ?></option>
                            <option value="admin_manager"><?= __('admin_manager') ?></option>
                            <option value="transportation_manager"><?= __('transportation_manager') ?></option>
                        </select>
                    </div>
                `,
                allowOutsideClick: false,
                showCancelButton: true,
                confirmButtonText: '<?= __('add') ?>',
                cancelButtonText: '<?= __('cancel') ?>',
                preConfirm: () => {
                    const role = document.getElementById('approver-role').value;
                    if (!role) {
                        Swal.showValidationMessage('<?= __('please_select_a_role') ?>');
                        return false;
                    }
                    return { role };
                }
            }).then(async (result) => {
                if (result.isConfirmed) {
                    await addApprovalStep(requestType, result.value.role);
                }
            });
        }

        async function addApprovalStep(requestType, userType) {
            try {
                const response = await fetch('./includes/approval_chain_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ 
                        action: 'add_approval_step', 
                        request_type: requestType,
                        user_type: userType
                    })
                });

                if (!response.ok) throw new Error('<?= __('failed_to_add_approval_step') ?>');
                const data = await response.json();

                if (data.success) {
                    Swal.fire('<?= __('added') ?>', '<?= __('approval_step_added_successfully') ?>', 'success');
                    loadApprovalChain(requestType); // Reload the chain
                } else {
                    throw new Error(data.message || '<?= __('failed_to_add_approval_step') ?>');
                }
            } catch (error) {
                Swal.fire('<?= __('error') ?>', error.message, 'error');
            }
        }

        async function removeApprovalStep(requestType, level) {
            const result = await Swal.fire({
                title: '<?= __('remove_approval_step') ?>',
                text: '<?= __('this_will_remove_this_approval_level_from_the_chain') ?>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '<?= __('yes_remove_it') ?>',
                cancelButtonText: '<?= __('cancel') ?>'
            });

            if (!result.isConfirmed) return;

            try {
                const response = await fetch('./includes/approval_chain_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ 
                        action: 'remove_approval_step', 
                        request_type: requestType,
                        level: level
                    })
                });

                if (!response.ok) throw new Error('<?= __('failed_to_remove_approval_step') ?>');
                const data = await response.json();

                if (data.success) {
                    Swal.fire('<?= __('removed') ?>', '<?= __('approval_step_removed_successfully') ?>', 'success');
                    loadApprovalChain(requestType); // Reload the chain
                } else {
                    throw new Error(data.message || '<?= __('failed_to_remove_approval_step') ?>');
                }
            } catch (error) {
                Swal.fire('<?= __('error') ?>', error.message, 'error');
            }
        }

        async function showAddNewRequestTypeModal() {
            const result = await Swal.fire({
                icon: 'info',
                title: '<?= __('add_new_request_type') ?>',
                html: `
                    <div class="text-left">
                        <div class="form-group">
                            <label for="new-request-type-id"><?= __('request_type_id') ?> <small class="text-danger">(<?= __('lowercase, underscores') ?>)</small></label>
                            <input type="text" id="new-request-type-id" class="form-control" placeholder="<?= __('e.g., travel_request, business_trip') ?>" pattern="[a-z_]+" title="<?= __('use_lowercase_letters_and_underscores_only') ?>">
                        </div>
                        <div class="form-group">
                            <label for="new-request-type-name"><?= __('request_type_name') ?></label>
                            <input type="text" id="new-request-type-name" class="form-control" placeholder="<?= __('e.g., Travel Request') ?>">
                        </div>
                        <div class="form-group">
                            <label for="new-main-table-name"><?= __('main_table_name') ?> <small class="text-muted">(<?= __('optional') ?>)</small></label>
                            <input type="text" id="new-main-table-name" class="form-control" placeholder="<?= __('e.g., travel_requests') ?>">
                        </div>
                        <div class="form-group">
                            <label for="new-request-type-description"><?= __('description') ?></label>
                            <textarea id="new-request-type-description" class="form-control" rows="2" placeholder="<?= __('brief_description_of_this_request_type') ?>"></textarea>
                        </div>
                    </div>
                `,
                allowOutsideClick: false,
                showCancelButton: true,
                confirmButtonText: '<?= __('create') ?>',
                cancelButtonText: '<?= __('cancel') ?>',
                preConfirm: () => {
                    const id = document.getElementById('new-request-type-id').value.trim().toLowerCase();
                    const name = document.getElementById('new-request-type-name').value.trim();
                    const mainTable = document.getElementById('new-main-table-name').value.trim();
                    const description = document.getElementById('new-request-type-description').value.trim();

                    if (!id) {
                        Swal.showValidationMessage('<?= __('request_type_id_is_required') ?>');
                        return false;
                    }
                    if (!name) {
                        Swal.showValidationMessage('<?= __('request_type_name_is_required') ?>');
                        return false;
                    }
                    if (!/^[a-z_]+$/.test(id)) {
                        Swal.showValidationMessage('<?= __('request_type_id_must_contain_only_lowercase_letters_and_underscores') ?>');
                        return false;
                    }
                    return { id, name, mainTable, description };
                }
            });

            if (result.isConfirmed) {
                await addNewRequestType(result.value.id, result.value.name, result.value.mainTable, result.value.description);
            }
        }

        async function addNewRequestType(requestTypeId, requestTypeName, mainTableName, description) {
            try {
                const response = await fetch('./includes/approval_chain_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ 
                        action: 'create_new_request_type',
                        request_type_id: requestTypeId,
                        request_type_name: requestTypeName,
                        main_table_name: mainTableName || '',
                        request_type_description: description
                    })
                });

                if (!response.ok) throw new Error('Failed to create request type');
                const data = await response.json();

                if (data.success) {
                    Swal.fire('<?= __('Created!') ?>', `<?= __('New request type') ?> "${requestTypeName}" <?= __('has been added successfully. You can now configure its approval chain.') ?>`, 'success')
                        .then(() => {
                            renderApprovalChainSettings(); // Reload the approval chain settings
                        });
                } else {
                    throw new Error(data.message || '<?= __('Failed to create request type') ?>');
                }
            } catch (error) {
                Swal.fire('<?= __('Error!') ?>', error.message, 'error');
            }
        }

        function attachPreviewListeners() {
            appSettings.forEach(setting => {
                const isImagePath = setting.setting_name.includes('logo') || setting.setting_name.includes('favicon');
                if (isImagePath) {
                    const inputEl = document.getElementById(`setting-${setting.setting_name}`);
                    const previewEl = document.getElementById(`preview-${setting.setting_name}`);
                    if (inputEl && previewEl) {
                        inputEl.addEventListener('change', (e) => {
                            const file = e.target.files[0];
                            if (file) {
                                previewEl.src = URL.createObjectURL(file);
                            }
                        });
                        previewEl.addEventListener('error', () => {
                            previewEl.src = 'assets/images/placeholder.png';
                        });
                    }
                }
            });
        }

        function attachEmailListListeners() {
            const addEmailBtn = document.getElementById('add-email-btn');
            if (addEmailBtn) {
                addEmailBtn.addEventListener('click', function() {
                    const container = document.getElementById('email-list-container');
                    const newEmailHtml = `
                        <div class="email-item mb-2">
                            <div class="input-group">
                                <input type="email" class="form-control email-input" placeholder="email@example.com" value="">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-danger remove-email-btn"><i class="mdi mdi-delete"></i></button>
                                </div>
                            </div>
                        </div>
                    `;
                    container.insertAdjacentHTML('beforeend', newEmailHtml);
                    attachRemoveEmailListeners();
                    updateEmailListHiddenField();
                });
            }
            
            attachRemoveEmailListeners();
            updateEmailListHiddenField();
        }

        function attachRemoveEmailListeners() {
            document.querySelectorAll('.remove-email-btn').forEach(btn => {
                btn.replaceWith(btn.cloneNode(true)); // Remove old listeners
            });
            
            document.querySelectorAll('.remove-email-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const emailItems = document.querySelectorAll('.email-item');
                    if (emailItems.length > 1) {
                        this.closest('.email-item').remove();
                        updateEmailListHiddenField();
                    } else {
                        Swal.fire('<?= __('Notice') ?>', '<?= __('At least one email field must remain') ?>', 'info');
                    }
                });
            });

            // Update hidden field on email input change
            document.querySelectorAll('.email-input').forEach(input => {
                input.removeEventListener('input', updateEmailListHiddenField);
                input.addEventListener('input', updateEmailListHiddenField);
            });
        }

        function updateEmailListHiddenField() {
            const emailInputs = document.querySelectorAll('.email-input');
            const emails = Array.from(emailInputs)
                .map(input => input.value.trim())
                .filter(email => email !== '');
            
            const hiddenField = document.getElementById('setting-traveling_company_email');
            if (hiddenField) {
                hiddenField.value = JSON.stringify(emails);
            }
        }

        async function loadSettings() {
            try {
                // Restricted (non-admin) special-access users only ever get Departments/Job
                // Titles tabs, which are backed by their own handlers, not app_settings rows.
                // Skip fetching the full settings payload entirely so sensitive settings
                // (SMTP credentials, other employees' special-access grants, etc.) never
                // reach a browser that only has partial access.
                if (!isFullSettingsAdmin) {
                    groupedSettings = {};
                    if (canAccessDepartmentsTab) {
                        groupedSettings['departments'] = [];
                    }
                    if (canAccessJobTitlesTab) {
                        groupedSettings['job titles'] = [];
                    }
                    if (canAccessLocationsTab) {
                        groupedSettings['locations'] = [];
                    }
                    if (canAccessSubDepartmentsTab) {
                        groupedSettings['sub_departments'] = [];
                    }
                    if (canAccessRequestBlocksTab) {
                        groupedSettings['request type blocks'] = [];
                    }
                    if (canAccessLoanSettingsTab || canAccessVacationPayrollTab || canAccessOvertimeSettingsTab || canAccessDeductionSettingsTab || canAccessSalaryIncrementSettingsTab) {
                        groupedSettings['payroll_settings'] = [];
                    }

                    const savedGroup = localStorage.getItem('app_settings_active_group');
                    const groups = Object.keys(groupedSettings).sort();

                    let restrictedNavHtml = '';
                    groups.forEach((group) => {
                        const isActive = (savedGroup === group);
                        const translatedGroup = translateText(group.replace(/_/g, ' '));
                        restrictedNavHtml += `
                            <li class="nav-item">
                                <a class="nav-link ${isActive ? 'active' : ''}" data-toggle="pill" href="#group-${group}" role="tab" data-group="${group}">
                                    <span class="text-capitalize">${translatedGroup}</span>
                                </a>
                            </li>
                        `;
                    });
                    settingsNav.innerHTML = restrictedNavHtml;

                    const restrictedInitialGroup = (savedGroup && groups.includes(savedGroup)) ? savedGroup : groups[0];
                    if (restrictedInitialGroup) {
                        renderSettingsGroup(restrictedInitialGroup);
                    } else {
                        settingsContainer.innerHTML = '<p class="text-center"><?= __('No settings found.') ?></p>';
                    }

                    settingsNav.querySelectorAll('a').forEach(link => {
                        link.addEventListener('click', (e) => {
                            e.preventDefault();
                            const group = link.dataset.group;
                            renderSettingsGroup(group);
                            localStorage.setItem('app_settings_active_group', group);
                            settingsNav.querySelectorAll('a').forEach(a => a.classList.remove('active'));
                            link.classList.add('active');
                        });
                    });
                    return;
                }

                const response = await fetch('./includes/settings_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ action: 'get_settings' })
                });
                if (!response.ok) throw new Error(`<?= __('Network response was not ok:') ?> ${response.statusText}`);

                const data = await response.json();
                if (!data.success) throw new Error(data.message || '<?= __('Failed to retrieve settings.') ?>');

                // These 4 groups are rendered only as sub-tabs inside the single "Payroll
                // Settings" hub (see renderPayrollSettingsHub / PAYROLL_SETTINGS_SUB_TABS),
                // never as their own top-level nav entries - drop their raw rows here so
                // they don't also show up mixed in alphabetically with unrelated tabs.
                const payrollSubGroupKeys = PAYROLL_SETTINGS_SUB_TABS.map(t => t.key);
                // 'page_role_access' stores its data as a raw JSON blob (page -> allowed roles)
                // with no dedicated UI here - it only has a generic text input via the default
                // renderer, which is unusable for editing and not meant to be admin-facing on
                // this screen. Drop it so it doesn't show up as its own tab; the setting itself
                // (read by includes/page_access_helper.php) and its save path in
                // settings_handler.php are untouched.
                // 'report_permissions' (report_visibility_by_user) no longer gets its own tab -
                // it's now managed per-user from inside the Special Access tab (see the "Report
                // Access" group in renderSpecialAccessSettings). Drop its raw row the same way;
                // get_allowed_report_types_for_user() and settings_handler.php are untouched.
                appSettings = data.settings.filter(s => !payrollSubGroupKeys.includes(s.setting_group) && s.setting_group !== 'page_role_access' && s.setting_group !== 'report_permissions');
                groupedSettings = appSettings.reduce((acc, setting) => {
                    const group = setting.setting_group;
                    if (!acc[group]) acc[group] = [];
                    acc[group].push(setting);
                    return acc;
                }, {});

                // Seed specialAccessMap from the real DB value as soon as settings load - not
                // only when the Special Access tab happens to be rendered. The hidden input
                // that carries this to Save is now a persistent field outside #settings-container
                // (see form skeleton), so it survives switching to other settings tabs. Without
                // this early seed, saving before ever opening the Special Access tab would submit
                // an empty "{}" and wipe out every existing grant.
                const specialAccessSetting = appSettings.find(s => s.setting_name === 'special_access_by_user');
                specialAccessMap = parseSpecialAccessMap(specialAccessSetting ? specialAccessSetting.setting_value : '{}');
                updateSpecialAccessHiddenValue();

                // Same early-seed as above, for report access. Its row was just filtered out of
                // appSettings (its old standalone tab is gone), so read it from the unfiltered
                // data.settings instead - otherwise this would always seed as "{}" and wipe every
                // existing per-user report restriction on first Save.
                const reportVisibilitySetting = data.settings.find(s => s.setting_name === 'report_visibility_by_user');
                reportPermissionMap = parseReportPermissionMap(reportVisibilitySetting ? reportVisibilitySetting.setting_value : '{}');
                updateReportPermissionHiddenValue();

                // Ensure custom management tabs always exist
                if (!groupedSettings['job titles']) {
                    groupedSettings['job titles'] = [];
                }
                if (!groupedSettings['departments']) {
                    groupedSettings['departments'] = [];
                }
                if (!groupedSettings['locations']) {
                    groupedSettings['locations'] = [];
                }
                if (!groupedSettings['sub_departments']) {
                    groupedSettings['sub_departments'] = [];
                }
                if (!groupedSettings['approval']) {
                    groupedSettings['approval'] = [];
                }
                if (!groupedSettings['request type blocks']) {
                    groupedSettings['request type blocks'] = [];
                }
                if (!groupedSettings['payroll_settings']) {
                    groupedSettings['payroll_settings'] = [];
                }

                // Restore last active group from localStorage if available
                const savedGroup = localStorage.getItem('app_settings_active_group');
                const groups = Object.keys(groupedSettings).sort(); // Sort groups alphabetically

                let navHtml = '';
                groups.forEach((group) => {
                    const isActive = (savedGroup === group);
                    const displayGroup = group.replace(/_/g, ' '); // Display with spaces instead of underscores
                    const translatedGroup = translateText(displayGroup); // Translate the group name
                    navHtml += `
                        <li class="nav-item">
                            <a class="nav-link ${isActive ? 'active' : ''}" data-toggle="pill" href="#group-${group}" role="tab" data-group="${group}">
                                <span class="text-capitalize">${translatedGroup}</span>
                            </a>
                        </li>
                    `;
                });
                settingsNav.innerHTML = navHtml;

                // Determine initial group to render
                const initialGroup = (savedGroup && groups.includes(savedGroup)) ? savedGroup : groups[0];
                if(initialGroup) {
                    renderSettingsGroup(initialGroup);
                } else {
                    settingsContainer.innerHTML = '<p class="text-center"><?= __('No settings found.') ?></p>';
                }

                // Click handlers: render and persist active group, update nav active class
                settingsNav.querySelectorAll('a').forEach(link => {
                    link.addEventListener('click', (e) => {
                        e.preventDefault();
                        const group = link.dataset.group;
                        renderSettingsGroup(group);
                        localStorage.setItem('app_settings_active_group', group);
                        // Toggle active class on nav links
                        settingsNav.querySelectorAll('a').forEach(a => a.classList.remove('active'));
                        link.classList.add('active');
                    });
                });

            } catch (error) {
                settingsContainer.innerHTML = `<p class="text-danger text-center">${error.message}</p>`;
                Swal.fire('<?= __('Error!') ?>', `<?= __('Could not load settings:') ?> ${error.message}`, 'error');
            }
        }

        settingsForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            
            // Validate email list before submitting
            const emailInputs = document.querySelectorAll('.email-input');
            let hasInvalidEmail = false;
            emailInputs.forEach(input => {
                if (input.value.trim() !== '' && !input.checkValidity()) {
                    hasInvalidEmail = true;
                    input.classList.add('is-invalid');
                } else {
                    input.classList.remove('is-invalid');
                }
            });
            
            if (hasInvalidEmail) {
                Swal.fire('<?= __('Validation Error') ?>', '<?= __('Please enter valid email addresses') ?>', 'error');
                return;
            }
            
            // Update hidden field one more time before submission
            updateEmailListHiddenField();
            
            const formData = new FormData();
            formData.append('action', 'update_settings');

            appSettings.forEach(setting => {
                const element = document.getElementById(`setting-${setting.setting_name}`);
                if (element) {
                    const isImagePath = setting.setting_name.includes('logo') || setting.setting_name.includes('favicon');
                    const isSessionTimeout = setting.setting_name === 'session_timeout';
                    
                    if (isImagePath) {
                        if (element.files.length > 0) {
                            formData.append(setting.setting_name, element.files[0]);
                        }
                    } else if (isSessionTimeout) {
                        // Evaluate session timeout expression before sending
                        let value = element.value.trim();
                        if (value) {
                            const evaluated = evaluateExpression(value);
                            if (evaluated !== null) {
                                formData.append(setting.setting_name, evaluated);
                            } else {
                                throw new Error(`<?= __('Invalid session timeout expression:') ?> "${value}". <?= __('Please use only numbers and operators (+, -, *, /, parentheses).') ?>`);
                            }
                        }
                    } else if (setting.setting_name === 'full_access_emp_ids') {
                        const selected = $(`#setting-${setting.setting_name}`).val() || [];
                        formData.append(setting.setting_name, JSON.stringify(selected));
                    } else {
                        // Simplified logic: this works for both standard inputs and select2.
                        formData.append(setting.setting_name, element.value);
                    }
                }
            });

            Swal.fire({
                title: '<?= __('saving') ?>',
                text: '<?= __('your_settings_are_being_updated') ?>',
                allowOutsideClick: false,
                onBeforeOpen: () => { Swal.showLoading(); }
            });

            try {
                const response = await fetch('./includes/settings_handler.php', {
                    method: 'POST',
                    body: formData
                });
                if (!response.ok) throw new Error(await response.text());
                
                const result = await response.json();
                Swal.close();

                if (result.success) {
                    Swal.fire({
                        title: '<?= __('saved') ?>',
                        text: '<?= __('your_settings_have_been_updated_successfully') ?>',
                        icon: 'success',
                        confirmButtonText: '<?= __('ok') ?>',
                        allowOutsideClick: false
                    }).then(() => window.location.reload());
                } else {
                    Swal.fire('<?= __('error') ?>', result.message || '<?= __('could_not_save_settings') ?>', 'error');
                }

            } catch (error) {
                Swal.close();
                Swal.fire('<?= __('request_failed') ?>', `<?= __('an_error_occurred') ?> ${error.message}`, 'error');
            }
        });

        loadSettings();
    });
    </script>

</body>
</html>