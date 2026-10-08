/**
 * app_settings.php's page-behavior script - a genuinely static file, cache-busted
 * from app_settings.php via a filemtime()-based ?v= (see that file). It needs two
 * small bits of per-request PHP data it can't have statically - the translation
 * dictionary and this user's permission flags - both injected by a tiny inline
 * bootstrap <script> in app_settings.php (window.lang / window.APP_SETTINGS_PERMISSIONS)
 * BEFORE this file loads. Everything else here is plain JS.
 */

// Client-side mirror of PHP's __() (includes/translation_functions.php) - same
// fallback rule: translated string if present and non-empty, else the given
// default, else the raw key. Relies on window.lang already being populated.
function __(key, def) {
    def = def || '';
    if (window.lang && window.lang[key]) return window.lang[key];
    return def !== '' ? def : key;
}

    document.addEventListener('DOMContentLoaded', function() {
        const isFullSettingsAdmin = window.APP_SETTINGS_PERMISSIONS.isFullSettingsAdmin;
        const canAccessDepartmentsTab = window.APP_SETTINGS_PERMISSIONS.canAccessDepartmentsTab;
        const canAccessJobTitlesTab = window.APP_SETTINGS_PERMISSIONS.canAccessJobTitlesTab;
        const canAccessLocationsTab = window.APP_SETTINGS_PERMISSIONS.canAccessLocationsTab;
        const canAccessSubDepartmentsTab = window.APP_SETTINGS_PERMISSIONS.canAccessSubDepartmentsTab;
        const canAccessRequestBlocksTab = window.APP_SETTINGS_PERMISSIONS.canAccessRequestBlocksTab;
        const canAccessLoanSettingsTab = window.APP_SETTINGS_PERMISSIONS.canAccessLoanSettingsTab;
        const canAccessVacationPayrollTab = window.APP_SETTINGS_PERMISSIONS.canAccessVacationPayrollTab;
        const canAccessOvertimeSettingsTab = window.APP_SETTINGS_PERMISSIONS.canAccessOvertimeSettingsTab;
        const canAccessDeductionSettingsTab = window.APP_SETTINGS_PERMISSIONS.canAccessDeductionSettingsTab;
        const canAccessSalaryIncrementSettingsTab = window.APP_SETTINGS_PERMISSIONS.canAccessSalaryIncrementSettingsTab;
        const canAccessResignationSettingsTab = window.APP_SETTINGS_PERMISSIONS.canAccessResignationSettingsTab;
        const canAccessAttendanceConfigTab = window.APP_SETTINGS_PERMISSIONS.canAccessAttendanceConfigTab;
        const canAccessScreenSettingsTab = window.APP_SETTINGS_PERMISSIONS.canAccessScreenSettingsTab;
        const canAccessCompaniesTab = window.APP_SETTINGS_PERMISSIONS.canAccessCompaniesTab;
        const canAccessTempRoleTransferTab = window.APP_SETTINGS_PERMISSIONS.canAccessTempRoleTransferTab;
        const canAccessVacationBlackoutTab = window.APP_SETTINGS_PERMISSIONS.canAccessVacationBlackoutTab;
        const requestTypeBlockLabels = {
            smart_request: __('smart_request', 'Smart Request'),
            loan_request: __('loan_request', 'Loan Request'),
            vacation_annual: __('vacation_annual_request_type', 'Vacation - Fly (Annual)'),
            vacation_emergency: __('vacation_emergency_request_type', 'Vacation - Fly (Emergency)'),
            vacation_local: __('vacation_local_request_type', 'Vacation - Local Vacation'),
            vacation_encashed: __('vacation_encashed_request_type', 'Vacation - Encashed'),
            excuse_leave: __('excuse_leave_request_type', 'Leave / Excuse (Sick, Marriage, Hajj, etc.)'),
            resignation_request: __('resignation_request', 'Resignation Request'),
            rejoin_request: __('rejoin_request', 'Rejoin Request'),
            general_request: __('general_request', 'General Request'),
            business_trip: __('business_trip', 'Business Trip'),
            salary_increment: __('salary_increment', 'Salary Increment'),
        };
        let appSettings = [];
        let groupedSettings = {};
        let fullAccessCandidates = null;
        let specialAccessUsersRaw = null;
        let reportPermissionMap = {};
        let specialAccessEligibleUsers = [];
        let specialAccessMap = {};
        let screenSettingsUsersRaw = null;
        let screenSettingsMap = {};
        let screenSettingsDefaults = { scale: 100, width: 1920, height: 1080, fullscreen: 0, theme: 'default' };
        let companiesTimetablesCache = [];
        const settingsContainer = document.getElementById('settings-container');
        // Microsoft Dynamics 365 master switch (D365 Config tab). While off, D365 permissions / reports are
        // hidden here but kept in the saved data, so switching it back on restores every grant.
        const D365_ON = (window.APP_SETTINGS_PERMISSIONS || {}).d365Enabled !== false;
        const isD365Key = key => /(^|_)d365(_|$)/.test(String(key || ''));
        // Attendance master switch (Integrations tab): same idea for the attendance permissions / report
        const ATTENDANCE_ON = (window.APP_SETTINGS_PERMISSIONS || {}).attendanceEnabled !== false;
        const ATTENDANCE_KEYS = ['manage_device_monitor', 'manage_attendance', 'manage_attendance_config', 'view_employee_attendance_tab', 'attendance'];
        // Keys / report types of a switched-off integration: hidden in the UI, kept when saving
        const isHiddenFeatureKey = key => (!D365_ON && isD365Key(key)) || (!ATTENDANCE_ON && ATTENDANCE_KEYS.includes(String(key || '')));
        const featureVisible = key => !isHiddenFeatureKey(key);
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
            return String(value == null ? '' : value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        /* ---------- sr-* design helpers shared by every tab ---------- */

        // Every SweetAlert on this page gets the sr popup look unless it brings its own
        // customClass (toasts excluded). Also turns the short Swal.fire(title, html, icon)
        // form into the object form so it picks up the same class.
        (function patchSwalLook() {
            if (!window.Swal || Swal.__srPatched) return;
            const origFire = Swal.fire.bind(Swal);
            Swal.fire = function(...args) {
                if (typeof args[0] === 'string') {
                    args = [{ title: args[0], html: args[1], icon: args[2] }];
                }
                const opts = args[0];
                if (opts && typeof opts === 'object' && !opts.toast && !opts.customClass) {
                    args[0] = Object.assign({}, opts, { customClass: { popup: 'sr-addline-popup sr-page' } });
                }
                return origFire(...args);
            };
            Swal.__srPatched = true;
        })();

        // Icon + one-line description per top-level settings group (outer nav + tab header)
        const SETTINGS_GROUP_META = {
            general: { icon: 'mdi-settings', sub: __('settings_general_sub', 'Application name, language, time zone, sessions and VAT.') },
            developer: { icon: 'mdi-code-tags', sub: __('settings_developer_sub', 'Error reporting, developer mode and API keys.') },
            email: { icon: 'mdi-email-outline', sub: __('settings_email_sub', 'SMTP server used for system emails and announcements.') },
            announcement_config: { icon: 'mdi-send', sub: __('settings_announcement_config_sub', 'Separate SMTP account used only to send announcements.') },
            D365_Config: { icon: 'mdi-cloud', sub: __('settings_d365_sub', 'Microsoft Dynamics 365 connection, templates and dimension mapping.') },
            approval: { icon: 'mdi-sitemap' },
            asset_clearance: { icon: 'mdi-package-variant-closed' },
            attendance_config: { icon: 'mdi-fingerprint', sub: __('settings_attendance_sub', 'Timetables and biometric device monitoring.') },
            integrations: { icon: 'mdi-puzzle' },
            license: { icon: 'mdi-key-variant' },
            org_structure: { icon: 'mdi-file-tree' },
            payroll_settings: { icon: 'mdi-cash-multiple', sub: __('settings_payroll_sub', 'Loan, vacation payroll, overtime, deduction, increment and resignation rules.') },
            'request type blocks': { icon: 'mdi-block-helper' },
            request_type_blocks: { icon: 'mdi-block-helper' },
            special_access: { icon: 'mdi-account-key' },
            temp_role_transfer: { icon: 'mdi-account-switch' },
            theme_config: { icon: 'mdi-theme-light-dark', sub: __('settings_theme_sub', 'Menu theme, logos and per-user screen settings.') },
            vacation_blackout_dates: { icon: 'mdi-calendar-remove' }
        };
        function groupMeta(group) {
            return SETTINGS_GROUP_META[group] || SETTINGS_GROUP_META[String(group).replace(/ /g, '_')] || { icon: 'mdi-tune' };
        }

        // Tab header: icon + title + subtitle, optional actions on the right
        function srTabHead(icon, title, sub, actionsHtml = '') {
            return `<div class="ac-head">
                        <div>
                            <h5 class="ac-title"><i class="mdi ${icon}"></i> ${escapeHtml(title)}</h5>
                            ${sub ? `<p class="ac-sub">${escapeHtml(sub)}</p>` : ''}
                        </div>
                        ${actionsHtml ? `<div class="ac-head-actions">${actionsHtml}</div>` : ''}
                    </div>`;
        }

        // Assigned-user card head (Special Access / Screen Settings): avatar + name + id + role
        function srUserCardHead(name, empId, role, extraHtml, actionsHtml) {
            const initials = String(name || '?').trim().split(/\s+/).slice(0, 2).map(w => w.charAt(0)).join('').toUpperCase();
            return `<div class="as-user-head">
                        <div class="org-name">
                            <span class="sr-avatar sr-avatar-sm">${escapeHtml(initials)}</span>
                            <div class="org-name-text">
                                <span class="sr-cell-title">${escapeHtml(name)} ${extraHtml || ''}</span>
                                <span class="sr-cell-sub"><span class="sr-mono">#${escapeHtml(empId)}</span>${role ? ` · ${escapeHtml(formatRoleLabel(role))}` : ''}</span>
                            </div>
                        </div>
                        <div class="as-user-actions">${actionsHtml}</div>
                    </div>`;
        }

        // Search box filtering the assigned-user cards in a list container
        function bindUserCardSearch(inputId, containerId) {
            const input = document.getElementById(inputId);
            if (!input) return;
            input.addEventListener('keydown', e => { if (e.key === 'Enter') e.preventDefault(); });
            input.addEventListener('input', () => {
                const q = input.value.trim().toLowerCase();
                document.querySelectorAll(`#${containerId} .as-user-card`).forEach(card => {
                    card.style.display = (!q || card.textContent.toLowerCase().includes(q)) ? '' : 'none';
                });
            });
        }

        function setAssignedCountPill(id, n) {
            const pill = document.getElementById(id);
            if (!pill) return;
            pill.className = `sr-pill tone-${n ? 'indigo' : 'slate'}`;
            pill.innerHTML = `<span class="sr-dot"></span>${n} ${__('assigned', 'assigned')}`;
        }

        // Sub-tab pills used by every hub (tabs: [{key, label, icon}])
        function srSubNav(id, tabs, activeKey) {
            return `<ul class="nav nav-pills mb-3 as-subnav" id="${id}">
                ${tabs.map((tab, idx) => `<li class="nav-item">
                    <a class="nav-link ${(activeKey ? tab.key === activeKey : idx === 0) ? 'active' : ''}" href="#" data-sub-tab="${tab.key}">${tab.icon ? `<i class="mdi ${tab.icon}"></i> ` : ''}${tab.label}</a>
                </li>`).join('')}
            </ul>`;
        }

        function getReportTypeCatalog() {
            return [
                { value: 'employee', label: '' + __('employee_report') + '' },
                { value: 'vacation', label: '' + __('vacation_report') + '' },
                { value: 'loan', label: '' + __('loan_report') + '' },
                { value: 'salary_increment', label: '' + __('salary_increment_report', 'Salary Increment Report') + '' },
                { value: 'salary', label: '' + __('salary_report') + '' },
                { value: 'payroll', label: '' + __('payroll_report') + '' },
                { value: 'attendance', label: '' + __('attendance_report') + '' },
                { value: 'document', label: '' + __('document_report') + '' },
                { value: 'assets', label: '' + __('assets_report') + '' },
                { value: 'assets_list', label: '' + __('assets_list') + '' },
                { value: 'evaluation', label: '' + __('evaluation_report') + '' },
                { value: 'resignation', label: '' + __('resignation_report') + '' },
                { value: 'terminated_employees', label: '' + __('terminated_employees') + '' },
                { value: 'eos', label: '' + __('calculate_end_of_service') + '' },
                { value: 'dept_comparison', label: '' + __('dept_comparison_report') + '' },
                { value: 'rejoin', label: '' + __('employee_rejoin_report', 'Employee Rejoin Report') + '' },
                { value: 'country_company_comparison', label: '' + __('country_company_comparison_report', 'Country & Company Comparison Report') + '' },
                { value: 'd365', label: '' + __('d365_employee_report', 'D365 Employee Report') + '' },
                { value: 'custom', label: '' + __('custom_report') + '' }
            ];
        }

        function getSpecialAccessCatalog() {
            return [{"value":"cancel_vacation_requests","label":"Cancel Submitted Vacation Requests"},{"value":"cancel_smart_requests","label":"Cancel Submitted Smart Requests"},{"value":"cancel_general_requests","label":"Cancel Submitted General Requests"},{"value":"cancel_loan_requests","label":"Cancel Submitted Loan Requests"},{"value":"cancel_resignation_requests","label":"Cancel Submitted Resignation Requests"},{"value":"cancel_rejoin_requests","label":"Cancel Submitted Rejoin Requests"},{"value":"cancel_business_trip_requests","label":"Cancel Submitted Business Trip Requests"},{"value":"cancel_salary_increment_requests","label":"Cancel Submitted Salary Increment Requests"},{"value":"add_business_trip_manual_allowance","label":"Business Trip: Add Manual Allowance (Taxi, Parking, etc.)"},{"value":"view_vacation_balance_history","label":"View Vacation Balance History"},{"value":"view_remaining_balance_in_report","label":"Show Remaining Balance in Vacation Report"},{"value":"manage_employee_request_block","label":"Block\/Unblock Employee from All Requests"},{"value":"manage_employee_request_type_block","label":"Block Employee by Specific Request Type"},{"value":"manage_global_request_blocks","label":"Manage Request Type Blocks (Global, All Employees)"},{"value":"manage_department_settings","label":"Access App Settings - Departments Tab"},{"value":"manage_job_title_settings","label":"Access App Settings - Job Titles Tab"},{"value":"manage_location_settings","label":"Access App Settings - Locations Tab"},{"value":"manage_sub_department_settings","label":"Access App Settings - Sub-Departments Tab"},{"value":"payroll_checklist_upload_excel","label":"Payroll Checklist Report: Upload Payroll Excel Button"},{"value":"payroll_checklist_review_import","label":"Payroll Checklist Report: Review Manager File & Import Button"},{"value":"payroll_checklist_export_excel","label":"Payroll Checklist Report: Export Excel Button"},{"value":"direct_rejoin_bypass_approval","label":"Directly Rejoin Employee From Active Vacation (Bypass Approval Chain)"},{"value":"ungenerate_payroll","label":"Payroll: Un-Generate Payroll Button"},{"value":"assign_payroll_supervisor","label":"Payroll: Assign Direct Supervisor for Payroll Button"},{"value":"manage_loan_settings","label":"Access App Settings - Loan Settings Tab"},{"value":"manage_vacation_payroll_settings","label":"Access App Settings - Vacation Payroll Settings Tab"},{"value":"manage_overtime_settings","label":"Access App Settings - Overtime Settings Tab"},{"value":"manage_deduction_settings","label":"Access App Settings - Deduction Settings Tab"},{"value":"manage_salary_increment_settings","label":"Access App Settings - Salary Increment Settings Tab"},{"value":"manage_resignation_settings","label":"Access App Settings - Resignation Settings Tab"},{"value":"manage_vacation_salary_below_min_days","label":"Employee Master: Allow Vacation Salary Payout Below Minimum Days"},{"value":"view_employee_eos_value","label":"Employee Master: View End of Service (EOS) Estimated Value"},{"value":"view_employee_salary_value","label":"Employee Master: View Salary"},{"value":"manage_update_salary_button_visibility","label":"Employee Master: Force Show\/Hide Update Salary Button"},{"value":"view_employee_additional_info","label":"Employee Master: View Additional Information Tab"},{"value":"view_employee_other_income","label":"Employee Master: View & Manage Other Income (Scheduled Bonus\/Income)"},{"value":"access_ctc_report","label":"Reports: CTC (Cost To Company) Report"},{"value":"view_all_employees","label":"View All Employees (Cross-Department\/Company Access)"},{"value":"view_inactive_employees","label":"Employee Master: Show Inactive Employees in All Employees List"},{"value":"view_employee_banking_details","label":"Employee Master: View Banking\/IBAN\/GOSI Details"},{"value":"view_employee_documents","label":"Employee Master: View Uploaded Documents (Passport\/Iqama, etc.)"},{"value":"request_employee_transfer","label":"Employee Master: Request Employee Transfer (Bypass Direct-Supervisor Requirement)"},{"value":"cars_add","label":"Cars: Add New Car"},{"value":"cars_edit","label":"Cars: Edit Car"},{"value":"cars_delete","label":"Cars: Delete Car"},{"value":"locations_add","label":"Locations: Add New Location"},{"value":"locations_edit","label":"Locations: Edit Location"},{"value":"locations_delete","label":"Locations: Delete Location"},{"value":"asset_inventory_add","label":"Asset Inventory: Add New Asset"},{"value":"asset_inventory_edit","label":"Asset Inventory: Edit Asset"},{"value":"asset_inventory_delete","label":"Asset Inventory: Delete Asset"},{"value":"apply_loan_with_active_loan","label":"Loan: Allow Applying for a New Loan While Another Is Pending\/Awaiting"},{"value":"manage_device_monitor","label":"Biometric Devices: View & Manage Devices Page"},{"value":"manage_attendance","label":"Attendance Record: View & Manage Attendance Page"},{"value":"manage_attendance_config","label":"Attendance: View & Manage Attendance Config (Timetables) Page"},{"value":"view_employee_attendance_tab","label":"Attendance: View Employee Profile's Attendance Record Tab"},{"value":"access_all_applied_vac","label":"Access Page: All Applied Vacations"},{"value":"access_all_applied_loan","label":"Access Page: All Applied Loans"},{"value":"access_all_applied_business_trip","label":"Access Page: All Applied Business Trips"},{"value":"access_all_resignations","label":"Access Page: All Resignations"},{"value":"access_all_settlements","label":"Access Page: All Settlements"},{"value":"access_all_payroll_approvals","label":"Access Page: All Payroll Approvals"},{"value":"access_payroll_checklist_report","label":"Access Page: Payroll Checklist Report"},{"value":"access_payroll_status_history","label":"Access Page: Payroll Status History"},{"value":"access_loan_report_details","label":"Access Page: Loan Report Details"},{"value":"access_settlement_status_history","label":"Access Page: Settlement Status History"},{"value":"access_vacation_status_history","label":"Access Page: Vacation Status History"},{"value":"access_business_trip_status_history","label":"Access Page: Business Trip Status History"},{"value":"access_all_applied_salary_increment","label":"Access Page: All Applied Salary Increments"},{"value":"access_salary_increment_status_history","label":"Access Page: Salary Increment Status History"},{"value":"access_edit_employee","label":"Access Page: Edit Employee"},{"value":"access_all_applied_employee_transfers","label":"Access Page: All Employee Transfer Requests"},{"value":"access_import_medical_insurance","label":"Access Page: Import Medical Insurance"},{"value":"access_import_loan_opening_balance","label":"Access Page: Import Loan Opening Balance"},{"value":"access_import_iqama_exp","label":"Access Page: Import Iqama Expiry"},{"value":"access_dashboard","label":"Access Page: Dashboard"},{"value":"access_dashboardgm","label":"Access Page: GM Dashboard"},{"value":"access_add_new_employee","label":"Access Page: Add New Employee"},{"value":"access_reg_employee","label":"Access Page: All Employees"},{"value":"access_emp_temp_contant","label":"Access Page: Temporary Contracts \/ Content Updates"},{"value":"access_employee_audit_gen","label":"Access Page: Yearly EOS Audit"},{"value":"access_employee_salary_report","label":"Access Page: Employee Salary Report"},{"value":"access_generate_payroll","label":"Access Page: Generate Payroll"},{"value":"access_rejoin_approvals","label":"Access Page: Rejoin Approvals"},{"value":"access_add_manual_loan","label":"Access Page: Add Manual Loan"},{"value":"access_all_cars","label":"Access Page: Cars Management"},{"value":"access_view_car","label":"Access Page: Car Details View"},{"value":"access_all_locations","label":"Access Page: Locations Management"},{"value":"access_all_machines","label":"Access Page: Machines Management"},{"value":"access_asset_inventory","label":"Access Page: Asset Inventory"},{"value":"access_all_menu_item","label":"Access Page: Menu Items"},{"value":"access_all_requests","label":"Access Page: Smart Requests"},{"value":"access_all_general_requests","label":"Access Page: General Requests"},{"value":"access_send_announcement","label":"Access Page: Send Announcement"},{"value":"access_vouchers","label":"Access Page: Vouchers"},{"value":"access_all_user_invoices","label":"Access Page: User Invoices"},{"value":"access_all_users","label":"Access Page: System Users"},{"value":"access_file_manager","label":"Access Page: File Manager"},{"value":"access_gallery","label":"Access Page: Gallery"},{"value":"access_language","label":"Access Page: Language Manager"},{"value":"access_log_activity","label":"Access Page: Activity Log (legacy)"},{"value":"access_view_activity_logs","label":"Access Page: Activity Logs"},{"value":"access_manual_vacation","label":"Access Page: Import Vacation Balance"},{"value":"access_employee_evaluation","label":"Access Page: Employee Evaluation"},{"value":"access_all_employee_evaluations","label":"Access Page: All Employee Evaluations"},{"value":"access_reports","label":"Access Page: Reports"},{"value":"access_manage_employee_supervisors","label":"Access Page: Manage Employee Supervisors"},{"value":"access_manage_holidays","label":"Access Page: Manage Holidays"},{"value":"access_vacation_dates_by_inv","label":"Access Page: Vacation Dates Editor"},{"value":"access_diagnose_double_deduction","label":"Access Page: Diagnose Double Deduction"},{"value":"access_fix_double_deduction","label":"Access Page: Fix Double Deduction"},{"value":"manage_own_screen_settings","label":"Screen Settings: Allow User to Edit Own Scale\/Resolution\/Fullscreen"},{"value":"manage_company_settings","label":"Access App Settings - Companies Tab"},{"value":"manage_temp_role_transfer","label":"Access App Settings - Temporary Role Transfer Tab"},{"value":"manage_vacation_blackout_dates","label":"Access App Settings - Vacation Blackout Dates Tab (Block Vacation Requests on Specific Dates)"},{"value":"access_import_excel_dynamic","label":"Tools: Dynamic Excel Import (Import Excel Into Any Table)"},{"value":"manage_memo_templates","label":"Employee Memos: Add \/ Edit \/ Delete Memo Templates"},{"value":"access_employee_memos","label":"Access Page: Send Employee Memo"},{"value":"access_d365_employee_compare","label":"Access Page: D365 Employee Compare"},{"value":"d365_sync_employee","label":"D365: Sync to D365 Button (Employee Header Widget & Payrolls Tab)"},{"value":"d365_register_employee","label":"D365: Register Missing Employees & Bulk Change Company (D365 Employee Check Page)"},{"value":"view_employee_d365_tab","label":"D365: View Employee Profile's D365 Tab"},{"value":"d365_sync_payroll","label":"D365: Sync Payroll to D365 (Generate Payroll Actions & D365 Payroll Sync Page)"},{"value":"d365_employee_dimensions","label":"D365: Employee Dimensions Page (Set Payroll Company & Dimension Values per Employee)"}];
        }

        // Purely presentational grouping (icon + ordered keys) for the Special Access
        // checkbox grid - mirrors includes/special_access_helper.php::get_special_access_categories()
        // so both stay in sync. Any catalog key not listed in any category here still shows,
        // just bucketed under a trailing "Other" group by buildSpecialAccessPanelData().
        function getSpecialAccessCategories() {
            return [{"name":"Cancel Submitted Requests","icon":"fa-ban","keys":["cancel_vacation_requests","cancel_smart_requests","cancel_general_requests","cancel_loan_requests","cancel_resignation_requests","cancel_rejoin_requests","cancel_business_trip_requests","cancel_salary_increment_requests"]},{"name":"Page Access","icon":"fa-door-open","keys":["access_all_applied_vac","access_all_applied_loan","access_all_applied_business_trip","access_all_resignations","access_all_settlements","access_all_payroll_approvals","access_payroll_checklist_report","access_payroll_status_history","access_loan_report_details","access_settlement_status_history","access_vacation_status_history","access_business_trip_status_history","access_all_applied_salary_increment","access_salary_increment_status_history","access_edit_employee","access_all_applied_employee_transfers","access_import_medical_insurance","access_import_loan_opening_balance","access_import_iqama_exp","access_dashboard","access_dashboardgm","access_add_new_employee","access_reg_employee","access_emp_temp_contant","access_employee_audit_gen","access_employee_salary_report","access_generate_payroll","access_rejoin_approvals","access_add_manual_loan","access_all_cars","access_view_car","access_all_locations","access_all_machines","access_asset_inventory","access_all_menu_item","access_all_requests","access_all_general_requests","access_send_announcement","access_vouchers","access_all_user_invoices","access_all_users","access_file_manager","access_gallery","access_language","access_log_activity","access_view_activity_logs","access_manual_vacation","access_employee_evaluation","access_all_employee_evaluations","access_reports","access_manage_employee_supervisors","access_manage_holidays","access_vacation_dates_by_inv","access_diagnose_double_deduction","access_fix_double_deduction","access_employee_memos","access_d365_employee_compare","manage_device_monitor","manage_attendance","manage_attendance_config","view_employee_attendance_tab"]},{"name":"Employee Master","icon":"fa-id-badge","keys":["view_all_employees","view_inactive_employees","view_employee_eos_value","view_employee_salary_value","view_employee_additional_info","view_employee_other_income","access_ctc_report","view_employee_banking_details","view_employee_documents","manage_vacation_salary_below_min_days","manage_employee_request_block","manage_employee_request_type_block","request_employee_transfer","manage_update_salary_button_visibility","manage_memo_templates"]},{"name":"Vacation Visibility","icon":"fa-umbrella-beach","keys":["view_vacation_balance_history","view_remaining_balance_in_report"]},{"name":"Payroll Checklist Report","icon":"fa-clipboard-check","keys":["payroll_checklist_upload_excel","payroll_checklist_review_import","payroll_checklist_export_excel"]},{"name":"App Settings Tabs","icon":"fa-cogs","keys":["manage_department_settings","manage_job_title_settings","manage_location_settings","manage_sub_department_settings","manage_global_request_blocks","manage_loan_settings","manage_vacation_payroll_settings","manage_overtime_settings","manage_deduction_settings","manage_salary_increment_settings","manage_resignation_settings","manage_own_screen_settings","manage_company_settings","manage_temp_role_transfer","manage_vacation_blackout_dates"]},{"name":"Business Trip","icon":"fa-plane","keys":["add_business_trip_manual_allowance"]},{"name":"Loan","icon":"fa-hand-holding-usd","keys":["apply_loan_with_active_loan"]},{"name":"Cars, Locations & Assets","icon":"fa-warehouse","keys":["cars_add","cars_edit","cars_delete","locations_add","locations_edit","locations_delete","asset_inventory_add","asset_inventory_edit","asset_inventory_delete"]},{"name":"D365","icon":"fa-cloud","keys":["d365_sync_employee","d365_register_employee","view_employee_d365_tab","d365_sync_payroll","d365_employee_dimensions"]},{"name":"Other Special Actions","icon":"fa-star","keys":["direct_rejoin_bypass_approval","ungenerate_payroll","assign_payroll_supervisor","access_import_excel_dynamic"]}];
        }

        // Builds the grouped, collapsible-by-category checkbox grid markup shared by the
        // inline "select a user" panel and the SweetAlert2 edit modal. idPrefix keeps
        // checkbox/count element ids unique between the two contexts.
        // Builds the category list (id/name/icon/keys) used by both the sidebar nav and
        // the per-category panels - each key appears in exactly one category, with anything
        // not listed in getSpecialAccessCategories() falling back into a trailing "Other" bucket.
        function buildSpecialAccessPanelData() {
            const categories = getSpecialAccessCategories();
            const catalog = getSpecialAccessCatalog().filter(item => featureVisible(item.value));
            const labelByKey = new Map(catalog.map(item => [item.value, item.label]));
            const placedKeys = new Set();
            const panels = [];

            categories.forEach((cat, catIndex) => {
                const keysInCat = cat.keys.filter(k => labelByKey.has(k));
                if (!keysInCat.length) return;
                keysInCat.forEach(k => placedKeys.add(k));
                panels.push({ id: `cat-${catIndex}`, name: cat.name, icon: cat.icon, keys: keysInCat });
            });

            const uncategorized = catalog.filter(item => !placedKeys.has(item.value)).map(item => item.value);
            if (uncategorized.length) {
                panels.push({ id: 'cat-other', name: '' + __('other', 'Other') + '', icon: 'fa-ellipsis-h', keys: uncategorized });
            }

            return { panels, labelByKey };
        }

        // Sub-grouping used ONLY inside the "Page Access" panel's checkbox grid (50+ keys -
        // too many to scan flat). Mirrors includes/special_access_helper.php::get_page_access_subgroups() -
        // presentational only, same tab, no new top-level category.
        function getPageAccessSubgroups() {
            return [
                { name: 'Dashboard', icon: 'fa-gauge-high', keys: ['access_dashboard', 'access_dashboardgm'] },
                { name: 'Employee Management', icon: 'fa-users', keys: ['access_add_new_employee', 'access_reg_employee', 'access_edit_employee', 'access_emp_temp_contant', 'access_employee_audit_gen', 'access_manage_employee_supervisors', 'access_all_applied_employee_transfers', 'access_employee_evaluation', 'access_all_employee_evaluations', 'access_employee_memos'] },
                { name: 'Vacation & Leave', icon: 'fa-umbrella-beach', keys: ['access_all_applied_vac', 'access_vacation_status_history', 'access_manual_vacation', 'access_vacation_dates_by_inv', 'access_manage_holidays'] },
                { name: 'Payroll & Salary', icon: 'fa-money-bill-wave', keys: ['access_all_payroll_approvals', 'access_payroll_checklist_report', 'access_payroll_status_history', 'access_generate_payroll', 'access_employee_salary_report', 'access_all_applied_salary_increment', 'access_salary_increment_status_history', 'access_diagnose_double_deduction', 'access_fix_double_deduction'] },
                { name: 'Loan', icon: 'fa-hand-holding-usd', keys: ['access_all_applied_loan', 'access_loan_report_details', 'access_add_manual_loan'] },
                { name: 'Business Trip', icon: 'fa-plane', keys: ['access_all_applied_business_trip', 'access_business_trip_status_history'] },
                { name: 'Resignation, Rejoin & Settlement', icon: 'fa-file-signature', keys: ['access_all_resignations', 'access_all_settlements', 'access_settlement_status_history', 'access_rejoin_approvals'] },
                { name: 'Requests', icon: 'fa-inbox', keys: ['access_all_requests', 'access_all_general_requests'] },
                { name: 'Assets & Fleet', icon: 'fa-warehouse', keys: ['access_all_cars', 'access_view_car', 'access_all_locations', 'access_all_machines', 'access_asset_inventory', 'access_all_menu_item', 'manage_device_monitor'] },
                { name: 'Import Tools', icon: 'fa-file-import', keys: ['access_import_medical_insurance', 'access_import_loan_opening_balance', 'access_import_iqama_exp'] },
                { name: 'Communication & Content', icon: 'fa-bullhorn', keys: ['access_send_announcement', 'access_vouchers', 'access_all_user_invoices', 'access_file_manager', 'access_gallery', 'access_language'] },
                { name: 'System Administration', icon: 'fa-user-shield', keys: ['access_all_users', 'access_log_activity', 'access_view_activity_logs', 'access_d365_employee_compare'] },
                { name: 'Attendance', icon: 'fa-calendar-check', keys: ['manage_attendance', 'manage_attendance_config', 'view_employee_attendance_tab'] },
                { name: 'Reports', icon: 'fa-chart-bar', keys: ['access_reports'] }
            ];
        }

        // Renders the Page Access panel's checkboxes split into named sub-sections instead
        // of one flat 50+ item grid - still the same single "Page Access" tab/panel, just
        // organized. Any key not covered by getPageAccessSubgroups() still shows, under a
        // trailing "Other" bucket, so a newly-added page is never silently hidden.
        function renderGroupedCheckboxGrid(idPrefix, subgroups, keysInPanel, labelByKey, selectedSet) {
            const keysSet = new Set(keysInPanel);
            const placed = new Set();
            let html = '';

            subgroups.forEach(group => {
                const keysHere = group.keys.filter(k => keysSet.has(k));
                if (!keysHere.length) return;
                keysHere.forEach(k => placed.add(k));
                html += `<div class="page-access-group-label"><i class="fa ${group.icon || 'fa-folder'} mr-2"></i>${escapeHtml(group.name)}</div>`;
                html += renderSpecialAccessCheckboxGrid(idPrefix, keysHere, labelByKey, selectedSet);
            });

            const leftover = keysInPanel.filter(k => !placed.has(k));
            if (leftover.length) {
                html += `<div class="page-access-group-label"><i class="fa fa-ellipsis-h mr-2"></i>${__('other', 'Other')}</div>`;
                html += renderSpecialAccessCheckboxGrid(idPrefix, leftover, labelByKey, selectedSet);
            }

            return html;
        }

        function renderSpecialAccessCheckboxGrid(idPrefix, keys, labelByKey, selectedSet) {
            let html = '<div class="special-access-checkbox-grid">';
            keys.forEach(key => {
                const label = labelByKey.get(key) || key;
                const checkboxId = `${idPrefix}-${key}`;
                html += `
                    <div class="special-access-item" data-search-label="${escapeHtml(label).toLowerCase()}">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input special-access-checkbox" id="${checkboxId}" value="${escapeHtml(key)}" data-access-key="${escapeHtml(key)}" ${selectedSet.has(key) ? 'checked' : ''}>
                            <label class="custom-control-label" for="${checkboxId}">${escapeHtml(label)}</label>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            return html;
        }

        // Recomputes one sidebar tab's "checked/total" badge after a checkbox inside its
        // panel changes.
        function updateSpecialAccessTabCount(popup, panelId) {
            const panel = popup.querySelector(`.sae-panel[data-panel-id="${panelId}"]`);
            const badge = popup.querySelector(`.sae-tab-count[data-count-for="${panelId}"]`);
            if (!panel || !badge) return;
            const total = parseInt(badge.getAttribute('data-total'), 10) || 0;
            const checked = panel.querySelectorAll('.special-access-checkbox:checked').length;
            badge.textContent = `${checked}/${total}`;
            badge.classList.toggle('badge-success', checked > 0);
            badge.classList.toggle('badge-light', checked === 0);
        }

        // Report Access lives inside the Special Access editor's Swal modal as one more
        // grouped block, but it's backed by its own map
        // (reportPermissionMap / report_visibility_by_user) with different semantics: no
        // explicit entry for a user means "sees ALL report types" (backward-compatible
        // default from get_allowed_report_types_for_user()), not "sees none" like every
        // other Special Access key. That's why it gets its own builder/wiring instead of
        // reusing the special-access panel builders - the "no entry yet" starting state, the
        // Custom/All(default) mode badge, and the "Reset to Default" action are all specific
        // to this map's semantics. It renders nothing actionable for plain employees, since
        // get_report_permission_users() never included them in the first place (report
        // pages aren't reachable by that role).
        function buildReportAccessBlockHtml(idPrefix, empId, includeHeader = true) {
            const user = (specialAccessEligibleUsers || []).find(u => String(u.emp_id || '').trim() === empId);
            const userType = user ? String(user.user_type || '').trim().toLowerCase() : '';

            if (userType === 'employee') {
                const msg = `<p class="text-muted mb-0" style="font-size:.85rem;">${__('report_access_not_applicable_for_employees', "Report access doesn't apply to plain employee accounts.")}</p>`;
                if (!includeHeader) return msg;
                let html = `<div class="special-access-category mb-3" data-report-access-block="1">`;
                html += `<div class="special-access-category-header"><span><i class="fa fa-chart-bar"></i> <strong>${__('report_access', 'Report Access')}</strong></span>`;
                html += `<span class="badge badge-light">${__('not_applicable', 'N/A')}</span></div>`;
                html += `<div class="special-access-category-body">${msg}</div></div>`;
                return html;
            }

            const catalog = getReportTypeCatalog().filter(item => featureVisible(item.value));
            const allTypeValues = getReportTypeCatalog().map(item => item.value);
            const hasExplicit = Object.prototype.hasOwnProperty.call(reportPermissionMap, empId);
            const selectedSet = new Set(hasExplicit ? normalizeReportTypeList(reportPermissionMap[empId]) : allTypeValues);

            let html = '';
            if (includeHeader) {
                html += `<div class="special-access-category mb-3" data-report-access-block="1">`;
                html += `<div class="special-access-category-header"><span><i class="fa fa-chart-bar"></i> <strong>${__('report_access', 'Report Access')}</strong></span>`;
                html += `<span class="badge ${hasExplicit ? 'badge-info' : 'badge-light'}" id="${idPrefix}-report-mode">${hasExplicit ? '' + __('custom', 'Custom') + '' : '' + __('all_default', 'All (default)') + ''}</span></div>`;
                html += `<div class="special-access-category-body">`;
            }
            html += `<div class="d-flex justify-content-between align-items-center mb-2 flex-wrap">`;
            html += `<small class="text-muted">${__('check_reports_user_can_view')}</small>`;
            html += `<div class="as-btn-row">`;
            html += `<button type="button" class="sr-btn sr-btn-sm report-access-select-all" data-target="${idPrefix}">${__('select_all')}</button>`;
            html += `<button type="button" class="sr-btn sr-btn-sm report-access-clear-all" data-target="${idPrefix}">${__('clear_all')}</button>`;
            html += `<button type="button" class="sr-btn sr-btn-sm sr-btn-ghost report-access-reset-default" data-target="${idPrefix}">${__('reset_default')}</button>`;
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
            html += `</div>`;
            if (includeHeader) {
                html += `</div></div>`;
            }
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
                modeBadge.textContent = isCustom ? '' + __('custom', 'Custom') + '' : '' + __('all_default', 'All (default)') + '';
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
                    throw new Error('' + __('failed_to_load_employees') + '');
                }

                const data = await response.json();
                if (!data.success || !Array.isArray(data.employees)) {
                    throw new Error(data.message || '' + __('failed_to_load_employees') + '');
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
                    placeholder: '' + __('select_employees') + '',
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

        function renderSettingsGroup(groupName, hostEl) {
            hostEl = hostEl || settingsContainer;
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
            const SELF_SAVING_GROUPS = ['org_structure', 'approval', 'request_type_blocks', 'payroll_settings', 'special_access', 'license', 'asset_clearance', 'temp_role_transfer', 'vacation_blackout_dates', 'integrations'];
            const saveBtnWrapper = document.getElementById('saveBtnWrapper');
            if (saveBtnWrapper) {
                saveBtnWrapper.style.display = SELF_SAVING_GROUPS.includes(normalizedGroupName) ? 'none' : '';
            }

            if (!settings) {
                 settingsContainer.innerHTML = '<p class="text-center text-danger">' + __('Group not found.') + '</p>';
                 return;
            }

            // Special handling for approval chain configuration
            if (normalizedGroupName === 'approval') {
                renderApprovalChainSettings();
                return;
            }

            // Special handling for asset clearance handler assignment (who clears
            // Laptop/Mobile/SIM/Car returns during vacation approval)
            if (normalizedGroupName === 'asset_clearance') {
                renderAssetClearanceSettings();
                return;
            }

            // Departments / Job Titles / Locations / Sub-Departments share one top-level
            // tab (org_structure), each as its own canAccess()-gated sub-tab - see
            // renderOrgStructureHub.
            if (normalizedGroupName === 'org_structure') {
                renderOrgStructureHub();
                return;
            }

            // Special handling for special access configuration
            if (normalizedGroupName === 'special_access') {
                renderSpecialAccessSettings();
                return;
            }

            // Theme Config: Menu Theme (sidebar_theme/sidebar_icon_style app_settings
            // rows) and Screen Settings (own scale/resolution/fullscreen) as sub-tabs -
            // see renderThemeConfigHub. Guarded to the outer nav call only (hostEl ===
            // settingsContainer) - the hub's own Menu Theme sub-tab re-enters this same
            // function with the same group name to render the generic fields into its
            // sub-content div, which must fall through below instead of re-triggering the
            // hub (that recursed forever - see renderEmailSettingsHub for the same guard).
            if (normalizedGroupName === 'theme_config' && hostEl === settingsContainer) {
                renderThemeConfigHub();
                return;
            }

            // Special handling for the Temporary Role Transfer tab (HR-initiated,
            // vacation-independent version of the "Transfer Role (Temp)" mechanism)
            if (normalizedGroupName === 'temp_role_transfer') {
                renderTempRoleTransferSettings();
                return;
            }

            // Vacation Blackout Dates: specific date ranges where no vacation can be applied for
            if (normalizedGroupName === 'vacation_blackout_dates') {
                renderVacationBlackoutSettings();
                return;
            }

            // Integrations: on/off switches for external systems (Microsoft Dynamics 365)
            if (normalizedGroupName === 'integrations') {
                renderIntegrationsSettings();
                return;
            }

            // Special handling for the product license key
            if (normalizedGroupName === 'license') {
                renderLicenseSettings();
                return;
            }

            // D365 Config hub: Connection (plain app_settings rows, outer Save button) +
            // Account Templates / Employee Dimensions (self-saving, assets/js/d365_dimensions_settings.js).
            // Guarded to the outer nav call - the Connection sub-tab re-enters with the same group name.
            if (normalizedGroupName === 'D365_Config' && hostEl === settingsContainer && window.D365DimensionsSettings) {
                const tabs = [
                    { key: 'connection', icon: 'mdi-lan-connect', label: __('d365_connection', 'Connection') },
                    { key: 'templates', icon: 'mdi-content-copy', label: __('d365_account_templates', 'Account Templates') },
                    { key: 'employees', icon: 'mdi-account-multiple-outline', label: __('d365_employee_dimensions', 'Employee Dimensions') },
                    { key: 'departments', icon: 'mdi-domain', label: __('d365_departments', 'Departments') },
                    { key: 'companies', icon: 'mdi-office', label: __('d365_companies', 'Companies') },
                ];
                settingsContainer.innerHTML = `
                    <div class="tab-pane active" id="group-D365_Config" role="tabpanel">
                        ${srTabHead(groupMeta('D365_Config').icon, __('D365_Config', 'D365 Config'), groupMeta('D365_Config').sub || '')}
                        ${srSubNav('d365-config-sub-nav', tabs, 'none')}
                        <div id="d365-config-sub-content"></div>
                    </div>
                `;
                const subContent = document.getElementById('d365-config-sub-content');
                const renderD365SubTab = (key) => {
                    document.querySelectorAll('#d365-config-sub-nav a').forEach(a => a.classList.toggle('active', a.dataset.subTab === key));
                    try { localStorage.setItem('d365_config_sub_tab', key); } catch (e) {}
                    if (key === 'connection') {
                        renderSettingsGroup('D365_Config', subContent);
                    } else {
                        if (saveBtnWrapper) saveBtnWrapper.style.display = 'none';
                        window.D365DimensionsSettings.render(key, subContent);
                    }
                };
                document.querySelectorAll('#d365-config-sub-nav a').forEach(a => a.addEventListener('click', function(e) {
                    e.preventDefault();
                    renderD365SubTab(this.dataset.subTab);
                }));
                let firstKey = 'connection';
                try { firstKey = localStorage.getItem('d365_config_sub_tab') || 'connection'; } catch (e) {}
                renderD365SubTab(tabs.some(t => t.key === firstKey) ? firstKey : 'connection');
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

            // Special handling for Attendance Config: Timetables (own tables, not
            // app_settings rows) plus Device Monitor (zk_sync_secret_key / offline
            // threshold) as a second sub-tab - see renderAttendanceConfigHub.
            if (normalizedGroupName === 'attendance_config') {
                renderAttendanceConfigHub();
                return;
            }

            // Email + Announcement Config share one top-level "Email" tab as sub-tabs -
            // see renderEmailSettingsHub. Both still hit the generic field renderer below
            // (guarded to only fire from the outer nav, not the hub's own sub-tab calls
            // into this same function, which pass their own hostEl and must fall through).
            if (normalizedGroupName === 'email' && hostEl === settingsContainer) {
                renderEmailSettingsHub();
                return;
            }

            formHtml += `<div class="tab-pane active" id="group-${groupName}" role="tabpanel">`;
            // Hub sub-tabs (Email, Menu Theme, D365 Connection...) already sit under the hub's own nav
            if (hostEl === settingsContainer) {
                const meta = groupMeta(groupName);
                formHtml += srTabHead(meta.icon, translateText(displayGroupName), meta.sub || '');
            }
            formHtml += `<div class="as-fields">`;
            settings.forEach(setting => {
                const id = `setting-${setting.setting_name}`;
                const label = translateText(setting.description);
                const isImagePath = setting.setting_name.includes('logo') || setting.setting_name.includes('favicon');
                const isEmailList = setting.setting_name === 'traveling_company_email';
                const isSessionTimeout = setting.setting_name === 'session_timeout';

                formHtml += `<div class="as-field">`;
                formHtml += `<label for="${id}" class="as-field-label">${label}</label>`;
                formHtml += `<div class="as-field-control">`;
                
                if (isImagePath) {
                    formHtml += `<div class="as-image-field">`;
                    formHtml += `<span class="as-image-box"><img id="preview-${setting.setting_name}" src="${escapeHtml(setting.setting_value || 'assets/images/placeholder.png')}" alt="Preview" class="preview-image"></span>`;
                    formHtml += `<div class="flex-grow-1">`;
                    formHtml += `<input type="file" id="${id}" name="${setting.setting_name}" accept="image/*" class="form-control-file">`;
                    formHtml += `<small class="form-text text-muted">${__('current')} <span class="sr-mono">${escapeHtml(setting.setting_value || __('not_set'))}</span></small>`;
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
                                    <input type="email" class="form-control email-input" placeholder="email@example.com" value="${escapeHtml(email)}">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-danger remove-email-btn"><i class="mdi mdi-delete"></i></button>
                                    </div>
                                </div>
                            </div>`;
                        });
                    }
                    formHtml += `</div>`;
                    formHtml += `<button type="button" class="sr-btn sr-btn-sm mt-1" id="add-email-btn"><i class="mdi mdi-plus"></i> ${__('add_email')}</button>`;
                    formHtml += `<input type="hidden" id="${id}" name="${setting.setting_name}" value="">`;
                } else if (isSessionTimeout) {
                    formHtml += `<div>`;
                    formHtml += `<input type="text" id="${id}" name="${setting.setting_name}" class="form-control session-timeout-input sr-mono" value="${escapeHtml(setting.setting_value || '')}" placeholder="${__('e.g., 3600 or 60*60*2')}">`;
                    formHtml += `<small class="form-text text-muted">${__('session_note')}</small>`;
                    formHtml += `<div id="timeout-result-${setting.setting_name}" class="mt-2" style="display:none;">`;
                    formHtml += `<span class="sr-pill tone-green"><span class="sr-dot"></span>${__('evaluated_as')}: <span class="timeout-seconds"></span> ${__('seconds')}</span>`;
                    formHtml += `</div>`;
                    formHtml += `</div>`;
                } else if (setting.setting_name === 'announcement_recipients') {
                    formHtml += renderAnnouncementRecipientsField(setting, id);
                } else if (setting.setting_name === 'announcement_allow_other_recipient') {
                    const allowOther = setting.setting_value !== '0';
                    formHtml += `<div class="custom-control custom-switch mt-2">
                        <input type="checkbox" class="custom-control-input" id="announcement-allow-other-toggle" ${allowOther ? 'checked' : ''}>
                        <label class="custom-control-label" for="announcement-allow-other-toggle">${__('announcement_allow_other_label', 'Show the "Other (enter email for testing)" option on the Send Announcement page')}</label>
                    </div>`;
                    formHtml += `<input type="hidden" id="${id}" name="${setting.setting_name}" value="${allowOther ? '1' : '0'}">`;
                } else if (setting.setting_name === 'full_access_emp_ids') {
                    const selectedList = parseEmpIdList(setting.setting_value || '');
                    formHtml += `<select id="${id}" name="${setting.setting_name}" class="form-control select2" multiple data-selected='${JSON.stringify(selectedList)}'></select>`;
                    formHtml += `<small class="form-text text-muted">${__('select_users_for_full_employee_access')}</small>`;
                } else {
                    let inputHtml = '';
                    switch (setting.input_type) {
                        case 'select':
                            let options = JSON.parse(setting.options || '{}');
                            // Note: We still use form-control for layout, but our custom CSS will target .select2-container for styling.
                            inputHtml = `<select id="${id}" name="${setting.setting_name}" class="form-control select2">`;
                            for (const [value, text] of Object.entries(options)) {
                                inputHtml += `<option value="${escapeHtml(value)}" ${setting.setting_value == value ? 'selected' : ''}>${escapeHtml(text)}</option>`;
                            }
                            inputHtml += `</select>`;
                            break;
                        case 'password':
                            // Secrets (e.g. D365 client secret): masked, with a show/hide toggle
                            inputHtml = `<div class="input-group">
                                <input type="password" id="${id}" name="${setting.setting_name}" class="form-control" autocomplete="new-password" value="${escapeHtml(setting.setting_value || '')}">
                                <div class="input-group-append"><button type="button" class="btn btn-outline-secondary" title="${__('show_hide', 'Show / hide')}" onclick="const i=document.getElementById('${id}'); i.type = i.type === 'password' ? 'text' : 'password'; this.querySelector('i').className = 'mdi ' + (i.type === 'password' ? 'mdi-eye' : 'mdi-eye-off');"><i class="mdi mdi-eye"></i></button></div>
                            </div>`;
                            break;
                        default:
                            inputHtml = `<input type="text" id="${id}" name="${setting.setting_name}" class="form-control" value="${escapeHtml(setting.setting_value || '')}">`;
                            break;
                    }
                    formHtml += inputHtml;
                }
                
                formHtml += `</div></div>`;
            });

            if (normalizedGroupName === 'email' || normalizedGroupName === 'announcement_config') {
                formHtml += `
                    <div class="as-field as-field-action">
                        <div class="as-field-label">${__('send_test_email', 'Send Test Email')}</div>
                        <div class="as-field-control">
                            <button type="button" id="testEmailConfigBtn" class="sr-btn sr-btn-sm">
                                <i class="mdi mdi-send"></i> ${__('send_test_email', 'Send Test Email')}
                            </button>
                            <small class="form-text text-muted">${__('send_test_email_hint', 'Sends a test email to the Default From Email Address above, using the SMTP settings currently entered in this form (works even if not saved yet).')}</small>
                            <div id="testEmailResult" class="mt-2"></div>
                        </div>
                    </div>
                `;
            }

            formHtml += `</div></div>`;
            hostEl.innerHTML = formHtml;

            // Initialize Select2 with a width setting for better Bootstrap integration.
            $('.select2').select2({
                width: '100%'
            });

            initializeFullAccessEmployeeSelect();

            attachPreviewListeners();
            attachEmailListListeners();
            attachAnnouncementRecipientListeners();
            attachSessionTimeoutListeners();

            if (normalizedGroupName === 'email') {
                attachTestEmailListener();
            } else if (normalizedGroupName === 'announcement_config') {
                attachTestEmailListener('announcement_');
            }
        }

        // --- Payroll Settings Hub ---
        // A single top-level "Payroll Settings" tab holds Loan / Vacation Payroll /
        // Overtime / Deduction as inner sub-tabs, so these parameters stay grouped
        // together and never get interleaved alphabetically with unrelated tabs
        // (Departments, Email, Security, ...) in the outer nav.
        // Rendered as a toggle switch instead of a text input in renderPayrollParamGroup() -
        // these are '0'/'1' flags (see includes/payroll_settings_handler.php's defaults),
        // not numeric thresholds like the rest of the Overtime/Deduction settings.
        const PAYROLL_BOOLEAN_SETTING_NAMES = ['overtime_auto_attendance_enabled', 'deduction_auto_attendance_enabled'];

        const PAYROLL_SETTINGS_SUB_TABS = [
            { key: 'loan_settings', icon: 'mdi-cash', label: '' + __('loan_settings', 'Loan Settings') + '', canAccess: () => canAccessLoanSettingsTab },
            { key: 'vacation_payroll', icon: 'mdi-beach', label: '' + __('vacation_payroll_settings', 'Vacation Payroll Settings') + '', canAccess: () => canAccessVacationPayrollTab },
            { key: 'overtime_settings', icon: 'mdi-timer', label: '' + __('overtime_settings', 'Overtime Settings') + '', canAccess: () => canAccessOvertimeSettingsTab },
            { key: 'deduction_settings', icon: 'mdi-minus-circle-outline', label: '' + __('deduction_settings', 'Deduction Settings') + '', canAccess: () => canAccessDeductionSettingsTab },
            { key: 'salary_increment_settings', icon: 'mdi-trending-up', label: '' + __('salary_increment_settings', 'Salary Increment Settings') + '', canAccess: () => canAccessSalaryIncrementSettingsTab },
            { key: 'resignation_settings', icon: 'mdi-exit-to-app', label: '' + __('resignation_settings', 'Resignation Settings') + '', canAccess: () => canAccessResignationSettingsTab },
        ];

        function renderPayrollSettingsHub() {
            const visibleTabs = PAYROLL_SETTINGS_SUB_TABS.filter(tab => tab.canAccess());

            const navHtml = srSubNav('payroll-settings-sub-nav', visibleTabs);

            settingsContainer.innerHTML = `
                <div class="tab-pane active" id="group-payroll_settings" role="tabpanel">
                    ${srTabHead(groupMeta('payroll_settings').icon, __('payroll_settings', 'Payroll Settings'), groupMeta('payroll_settings').sub || '')}
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
                subContent.innerHTML = '<p class="text-center text-danger">' + __('access_denied', 'Access denied') + '</p>';
            }
        }

        // These render independently of the main settings form/save button: each posts
        // straight to includes/payroll_settings_handler.php, which is permission-checked
        // per group (admin OR the matching Special Access key), so a restricted grantee
        // only ever sees and edits the one group they were given.
        async function renderPayrollParamGroup(group, title, hostEl) {
            const tabIcon = (PAYROLL_SETTINGS_SUB_TABS.find(t => t.key === group) || {}).icon || 'mdi-tune';
            hostEl.innerHTML = `
                <div class="sr-card as-section">
                    <div class="sr-card-head"><div class="sr-card-title"><i class="mdi ${tabIcon}"></i> ${escapeHtml(title)}</div></div>
                    <div id="payroll-param-fields-${group}">
                        <div class="ac-loading"><span class="spinner-border spinner-border-sm" role="status"></span> ${__('loading')}</div>
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
                    container.innerHTML = `<div class="as-section-body"><div class="sr-notice tone-red ac-error"><i class="mdi mdi-alert-circle-outline"></i><div>${escapeHtml(data.message || __('access_denied', 'Access denied'))}</div></div></div>`;
                    return;
                }

                let formHtml = '<div class="as-fields">';
                data.settings.forEach(setting => {
                    const id = `payroll-param-${setting.setting_name}`;
                    if (!ATTENDANCE_ON && setting.setting_name.includes('_auto_attendance_')) {
                        return; // attendance switched off (Integrations) - kept as saved, not shown
                    }
                    if (PAYROLL_BOOLEAN_SETTING_NAMES.includes(setting.setting_name)) {
                        formHtml += `
                            <div class="as-field">
                                <label class="as-field-label" for="${id}">${translateText(setting.description)}</label>
                                <div class="as-field-control">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input payroll-param-input" id="${id}" data-setting-name="${setting.setting_name}" ${setting.setting_value === '1' ? 'checked' : ''}>
                                        <label class="custom-control-label" for="${id}">${__('enabled', 'Enabled')}</label>
                                    </div>
                                </div>
                            </div>
                        `;
                        return;
                    }
                    formHtml += `
                        <div class="as-field">
                            <label for="${id}" class="as-field-label">${translateText(setting.description)}</label>
                            <div class="as-field-control">
                                <input type="text" inputmode="decimal" id="${id}" data-setting-name="${setting.setting_name}" class="form-control payroll-param-input as-num-input" value="${escapeHtml(setting.setting_value ?? '')}">
                            </div>
                        </div>
                    `;
                });
                formHtml += `</div>
                    <div class="as-section-foot">
                        <button type="button" class="sr-btn sr-btn-primary sr-btn-sm" id="btn-save-${group}"><i class="mdi mdi-content-save"></i> ${__('save', 'Save')}</button>
                    </div>`;
                container.innerHTML = formHtml;

                document.getElementById(`btn-save-${group}`).addEventListener('click', async function() {
                    const btn = this;
                    const payload = new URLSearchParams({ action: 'update_payroll_settings', group });
                    container.querySelectorAll('.payroll-param-input').forEach(input => {
                        payload.append(input.dataset.settingName, input.type === 'checkbox' ? (input.checked ? '1' : '0') : input.value.trim());
                    });

                    btn.disabled = true;
                    try {
                        const saveResponse = await fetch('./includes/payroll_settings_handler.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: payload
                        });
                        const saveResult = await saveResponse.json();
                        if (saveResult.success) {
                            acToast('success', __('your_settings_have_been_updated_successfully'));
                        } else {
                            Swal.fire('' + __('error') + '', saveResult.message || '' + __('could_not_save_settings') + '', 'error');
                        }
                    } catch (error) {
                        Swal.fire('' + __('request_failed') + '', error.message, 'error');
                    } finally {
                        btn.disabled = false;
                    }
                });
            } catch (error) {
                document.getElementById(`payroll-param-fields-${group}`).innerHTML = `<div class="as-section-body"><div class="sr-notice tone-red ac-error"><i class="mdi mdi-alert-circle-outline"></i><div>${escapeHtml(error.message)}</div></div></div>`;
            }
        }

        // --- Org Structure Hub ---
        // Departments / Job Titles / Locations / Companies / Sub-Departments each have
        // their own special-access key (canAccessDepartmentsTab / canAccessJobTitlesTab /
        // canAccessLocationsTab / canAccessCompaniesTab / canAccessSubDepartmentsTab), so
        // a restricted grantee may only have one or two of them - same canAccess()-gated
        // sub-tab pattern as PAYROLL_SETTINGS_SUB_TABS above.
        const ORG_STRUCTURE_SUB_TABS = [
            { key: 'departments', icon: 'mdi-domain', label: '' + __('departments', 'Departments') + '', canAccess: () => canAccessDepartmentsTab },
            { key: 'sub_departments', icon: 'mdi-source-fork', label: '' + __('sub_departments', 'Sub Departments') + '', canAccess: () => canAccessSubDepartmentsTab },
            { key: 'job_titles', icon: 'mdi-briefcase', label: '' + __('job_titles', 'Job Titles') + '', canAccess: () => canAccessJobTitlesTab },
            { key: 'locations', icon: 'mdi-map-marker', label: '' + __('locations', 'Locations') + '', canAccess: () => canAccessLocationsTab },
            { key: 'companies', icon: 'mdi-office', label: '' + __('companies', 'Companies') + '', canAccess: () => canAccessCompaniesTab },
        ];

        function renderOrgStructureHub() {
            const visibleTabs = ORG_STRUCTURE_SUB_TABS.filter(tab => tab.canAccess());

            const navHtml = srSubNav('org-structure-sub-nav', visibleTabs);

            settingsContainer.innerHTML = `
                <div class="tab-pane active" id="group-org_structure" role="tabpanel">
                    ${navHtml}
                    <div id="org-structure-sub-content"></div>
                </div>
            `;

            const subContent = document.getElementById('org-structure-sub-content');

            function renderSubTab(key) {
                document.querySelectorAll('#org-structure-sub-nav a').forEach(a => {
                    a.classList.toggle('active', a.dataset.subTab === key);
                });
                if (key === 'departments') renderDepartmentsSettings(subContent);
                else if (key === 'sub_departments') renderSubDepartmentsSettings(subContent);
                else if (key === 'job_titles') renderJobTitlesSettings(subContent);
                else if (key === 'locations') renderLocationsSettings(subContent);
                else if (key === 'companies') renderCompaniesSettings(subContent);
            }

            document.querySelectorAll('#org-structure-sub-nav a').forEach(a => {
                a.addEventListener('click', function(e) {
                    e.preventDefault();
                    renderSubTab(this.dataset.subTab);
                });
            });

            if (visibleTabs.length > 0) {
                renderSubTab(visibleTabs[0].key);
            } else {
                subContent.innerHTML = '<p class="text-center text-danger">' + __('access_denied', 'Access denied') + '</p>';
            }
        }

        // --- Email Settings Hub ---
        // Announcement Config (announcement_smtp_*) is its own SMTP block used only for
        // the announcement-sending feature, kept as a sub-tab next to the main Email/SMTP
        // settings instead of its own top-level tab. Both sub-tabs render through the
        // generic renderSettingsGroup() field renderer (see the hostEl-guarded 'email'
        // branch there) and save through the same outer "Save Changes" button/form -
        // switching sub-tabs before saving discards unsaved edits in the one left, same
        // as switching any other top-level tab today.
        const EMAIL_SETTINGS_SUB_TABS = [
            { key: 'email', icon: 'mdi-email-outline', label: '' + __('email', 'Email') + '' },
            { key: 'announcement_config', icon: 'mdi-send', label: '' + __('announcement_config', 'Announcement Config') + '' },
            { key: 'announcement_recipients', icon: 'mdi-email-open', label: '' + __('announcement_recipients', 'Announcement Recipients') + '' },
        ];

        function renderEmailSettingsHub() {
            const navHtml = srSubNav('email-settings-sub-nav', EMAIL_SETTINGS_SUB_TABS);

            settingsContainer.innerHTML = `
                <div class="tab-pane active" id="group-email" role="tabpanel">
                    ${srTabHead(groupMeta('email').icon, __('email', 'Email'), groupMeta('email').sub || '')}
                    ${navHtml}
                    <div id="email-settings-sub-content"></div>
                </div>
            `;

            const subContent = document.getElementById('email-settings-sub-content');

            function renderSubTab(key) {
                document.querySelectorAll('#email-settings-sub-nav a').forEach(a => {
                    a.classList.toggle('active', a.dataset.subTab === key);
                });
                renderSettingsGroup(key, subContent);
            }

            document.querySelectorAll('#email-settings-sub-nav a').forEach(a => {
                a.addEventListener('click', function(e) {
                    e.preventDefault();
                    renderSubTab(this.dataset.subTab);
                });
            });

            renderSubTab(EMAIL_SETTINGS_SUB_TABS[0].key);
        }

        // --- Attendance Config Hub ---
        // Timetables is the main attendance-config surface, restricted grantees
        // (canAccessAttendanceConfigTab) get this alone. Device Monitor (zk_sync_secret_key
        // / device_offline_threshold_minutes) is a second sub-tab, admin-only since it's a
        // shared secret key, not per-grantee attendance config.
        function renderAttendanceConfigHub() {
            const showDeviceMonitor = isFullSettingsAdmin;
            const attTabs = [{ key: 'timetables', icon: 'mdi-calendar-clock', label: __('timetables', 'Timetables') }];
            if (showDeviceMonitor) {
                attTabs.push(
                    { key: 'device_monitor', icon: 'mdi-monitor', label: __('device_monitor', 'Device Monitor') },
                    { key: 'attendance_retention', icon: 'mdi-database', label: __('data_retention', 'Data Retention') },
                    { key: 'sync_settings', icon: 'mdi-cloud-sync', label: __('sync_settings', 'Sync Settings') }
                );
            }
            const navHtml = srSubNav('attendance-config-sub-nav', attTabs);

            settingsContainer.innerHTML = `
                <div class="tab-pane active" id="group-attendance_config" role="tabpanel">
                    ${srTabHead(groupMeta('attendance_config').icon, __('attendance_config', 'Attendance Config'), groupMeta('attendance_config').sub || '')}
                    ${navHtml}
                    <div id="attendance-config-sub-content"></div>
                </div>
            `;

            const subContent = document.getElementById('attendance-config-sub-content');
            const saveBtnWrapper = document.getElementById('saveBtnWrapper');

            // Last IP zk_sync_import.php blocked (written server-side by
            // zk_record_sync_rejected_ip) - shown with a one-click "add" so a
            // changed office public IP can be allowed without reading logs.
            function renderSyncRejectedIpNotice(hostEl) {
                const row = (groupedSettings['zk_sync_status'] || []).find(s => s.setting_name === 'zk_sync_last_rejected_ip');
                let info = null;
                try { info = row ? JSON.parse(row.setting_value || 'null') : null; } catch (e) { info = null; }
                if (!info || !info.ip) return;

                const input = hostEl.querySelector('[name="zk_sync_allowed_ip"]');
                const current = input ? input.value.split(/[\s,;]+/).filter(Boolean) : [];
                if (!input || current.length === 0 || current.includes(info.ip)) return;

                hostEl.insertAdjacentHTML('beforeend', `<div class="sr-notice tone-amber as-notice as-notice-row">
                    <i class="mdi mdi-alert-outline"></i>
                    <div>${__('sync_last_rejected_ip', 'Last blocked sync request came from')} <strong>${escapeHtml(info.ip)}</strong> (${escapeHtml(info.at || '')})</div>
                    <button type="button" class="sr-btn sr-btn-sm" id="addRejectedSyncIpBtn"><i class="mdi mdi-plus"></i> ${__('sync_add_ip', 'Add to allowed IPs')}</button>
                </div>`);
                document.getElementById('addRejectedSyncIpBtn').addEventListener('click', function() {
                    input.value = current.concat(info.ip).join(', ');
                    this.disabled = true;
                    this.innerHTML = `<i class="mdi mdi-check"></i> ${__('sync_ip_added_save', 'Added - press Save')}`;
                });
            }

            function renderSubTab(key) {
                document.querySelectorAll('#attendance-config-sub-nav a').forEach(a => {
                    a.classList.toggle('active', a.dataset.subTab === key);
                });
                if (saveBtnWrapper) {
                    // Timetables self-saves per-row via its own modal; Device Monitor / Data
                    // Retention / Sync Settings use the generic outer Save button.
                    saveBtnWrapper.style.display = (key === 'device_monitor' || key === 'attendance_retention' || key === 'sync_settings') ? '' : 'none';
                }
                if (key === 'device_monitor' || key === 'attendance_retention' || key === 'sync_settings') {
                    renderSettingsGroup(key, subContent);
                    if (key === 'attendance_retention') {
                        subContent.insertAdjacentHTML('beforeend', `<div class="sr-notice tone-amber as-notice"><i class="mdi mdi-alert-outline"></i><div>${__('attendance_retention_warning', 'Attendance, punch and raw punch records older than this many days are permanently deleted (once a day, when the device sync runs), and older punches are no longer stored. Payroll auto Late/Early/Overtime deductions read these records, so keep at least the months you may still need to regenerate. Minimum 7 days.')}</div></div>`);
                    }
                    if (key === 'sync_settings') {
                        subContent.insertAdjacentHTML('beforeend', `<div class="sr-notice tone-sky as-notice"><i class="mdi mdi-information-outline"></i><div>${__('sync_settings_hint_multi', 'Restricts which IP addresses may push attendance punches to zk_sync_import.php from the local BioTime server. Separate several IPs with commas. Leave blank to allow any IP - the shared secret key still gates the endpoint either way.')}</div></div>`);
                        renderSyncRejectedIpNotice(subContent);
                    }
                } else {
                    renderAttendanceConfigGroup(subContent);
                }
            }

            document.querySelectorAll('#attendance-config-sub-nav a').forEach(a => {
                a.addEventListener('click', function(e) {
                    e.preventDefault();
                    renderSubTab(this.dataset.subTab);
                });
            });

            renderSubTab('timetables');
        }

        // Attendance Config: named Timetables (office hours + weekly days-off), each
        // assignable to one or more companies - see includes/ajaxFile/timetableAjax.php
        // and includes/attendance_helpers.php's attendance_resolve_timetable(). This is
        // rendered as the default sub-tab of the Attendance Config hub above (see
        // renderAttendanceConfigHub) since it's backed by its own `timetables`/
        // `companies.timetable_id` tables, not app_settings rows.
        const DAY_OFF_LABELS = {
            1: '' + __('monday', 'Monday') + '', 2: '' + __('tuesday', 'Tuesday') + '', 3: '' + __('wednesday', 'Wednesday') + '',
            4: '' + __('thursday', 'Thursday') + '', 5: '' + __('friday', 'Friday') + '', 6: '' + __('saturday', 'Saturday') + '',
            7: '' + __('sunday', 'Sunday') + '',
        };

        async function renderAttendanceConfigGroup(hostEl) {
            hostEl.innerHTML = `
                <div class="sr-card as-section">
                    <div class="sr-card-head">
                        <div>
                            <div class="sr-card-title"><i class="mdi mdi-calendar-clock"></i> ${__('timetables', 'Timetables')} <span class="sr-count" id="timetables-count">-</span></div>
                            <div class="sr-card-sub">${__('attendance_config_hint', 'Timetables define office hours and weekly days off. Assign each company to a timetable - every employee of that company follows it. A Temporary timetable instead targets specific employees for a limited date range, overriding their company\'s timetable while it\'s active.')}</div>
                        </div>
                        <button type="button" class="sr-btn sr-btn-success sr-btn-sm flex-shrink-0" id="btn-add-timetable"><i class="mdi mdi-plus"></i> ${__('add_new', 'Add New')}</button>
                    </div>
                    <div id="timetables-container">
                        <div class="ac-loading"><span class="spinner-border spinner-border-sm" role="status"></span> ${__('loading')}</div>
                    </div>
                </div>
            `;

            loadTimetables();
            document.getElementById('btn-add-timetable').addEventListener('click', () => showTimetableModal(null));
        }

        async function loadTimetables() {
            const container = document.getElementById('timetables-container');
            if (!container) return;
            const errorHtml = msg => `<div class="as-section-body"><div class="sr-notice tone-red ac-error"><i class="mdi mdi-alert-circle-outline"></i><div>${escapeHtml(msg)}</div></div></div>`;
            try {
                const response = await fetch('./includes/ajaxFile/timetableAjax.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ action: 'list_timetables' })
                });
                const data = await response.json();
                if (data.status !== 'success') {
                    container.innerHTML = errorHtml(data.message || __('access_denied', 'Access denied'));
                    return;
                }

                const timetables = Array.isArray(data.timetables) ? data.timetables : [];
                const countEl = document.getElementById('timetables-count');
                if (countEl) countEl.textContent = timetables.length;
                if (!timetables.length) {
                    container.innerHTML = `<div class="sr-empty"><i class="mdi mdi-calendar-clock"></i>${__('no_data_available', 'No data available')}</div>`;
                    return;
                }

                const WEEKDAY_ABBR = { 1: 'Mon', 2: 'Tue', 3: 'Wed', 4: 'Thu', 5: 'Fri', 6: 'Sat', 7: 'Sun' };
                const todayStr = new Date().toISOString().slice(0, 10);
                const pill = (tone, text) => `<span class="sr-pill tone-${tone}"><span class="sr-dot"></span>${escapeHtml(text)}</span>`;
                let rowsHtml = '';

                timetables.forEach(t => {
                    const isTemporary = Number(t.is_temporary) === 1;
                    let typeHtml;
                    let assignedTo;
                    if (isTemporary) {
                        const hasRange = !!(t.start_date && t.end_date);
                        const inRange = hasRange && todayStr >= t.start_date && todayStr <= t.end_date;
                        const rangePill = !hasRange
                            ? pill('green', __('permanent', 'Permanent'))
                            : pill(inRange ? 'green' : 'slate', inRange ? __('active', 'Active') : __('expired', 'Expired'));
                        typeHtml = `<div class="as-pill-stack">${pill('indigo', __('employee_wise', 'Employee-wise'))}${rangePill}</div>`;
                        const empNames = (t.employees || []).map(e => escapeHtml(e.name)).join(', ') || `<span class="ac-muted">${__('none', 'None')}</span>`;
                        assignedTo = `<div class="as-assigned">${empNames}</div>` + (hasRange ? `<div class="sr-cell-sub sr-mono">${escapeHtml(t.start_date)} &rarr; ${escapeHtml(t.end_date)}</div>` : '');
                    } else {
                        typeHtml = pill('sky', __('company', 'Company'));
                        const companyList = t.company_list || [];
                        assignedTo = companyList.length
                            ? `<div class="as-chip-wrap">${companyList.map(c => `<span class="sr-chip" title="${escapeHtml(c.comp_name)}"><i class="mdi mdi-office"></i> ${escapeHtml(c.comp_name)}</span>`).join('')}</div>`
                            : `<span class="ac-muted">${__('none', 'None')}</span>`;
                    }

                    const days = t.days || {};
                    const scheduleHtml = [1, 2, 3, 4, 5, 6, 7].map(w => {
                        const d = days[w] || days[String(w)];
                        if (!d || Number(d.is_off) === 1) {
                            return `<span class="tt-day is-off" title="${__('day_off', 'Day off')}">${WEEKDAY_ABBR[w]}</span>`;
                        }
                        const tip = `${d.check_in_start} - ${d.check_in_end} / ${d.check_out_start} - ${d.check_out_end} (${d.standard_hours}h)`;
                        return `<span class="tt-day" title="${escapeHtml(tip)}"><b>${WEEKDAY_ABBR[w]}</b> ${escapeHtml(d.check_in)}-${escapeHtml(d.check_out)}</span>`;
                    }).join('');

                    const isActive = Number(t.is_active) === 1;
                    // Default (id 1) is always active and locked - every other timetable
                    // is a draft (built with its days/companies/employees) until
                    // explicitly activated, and going active is guarded server-side
                    // so it can never leave a company/employee covered by two
                    // active timetables at once (see toggle_timetable_active()).
                    const isDefault = Number(t.id) === 1;
                    const name = escapeHtml(t.name);
                    const toggleBtnHtml = isDefault ? '' : `<button type="button" class="sr-btn sr-btn-ghost sr-btn-sm sr-btn-icon btn-toggle-timetable-active ${isActive ? '' : 'is-activate'}" data-id="${escapeHtml(t.id)}" data-name="${name}" data-active="${isActive ? 0 : 1}" title="${isActive ? __('deactivate', 'Deactivate') : __('activate', 'Activate')}">
                            <i class="mdi ${isActive ? 'mdi-toggle-switch-off' : 'mdi-toggle-switch'}"></i>
                        </button>`;
                    rowsHtml += `
                        <tr${isActive ? '' : ' class="is-inactive"'}>
                            <td>
                                <div class="org-name">
                                    <span class="ac-ico"><i class="mdi mdi-calendar-clock"></i></span>
                                    <div class="org-name-text">
                                        <span class="sr-cell-title">${name}</span>
                                        ${isDefault ? `<span class="sr-cell-sub">${__('default', 'Default')}</span>` : ''}
                                    </div>
                                </div>
                            </td>
                            <td>${typeHtml}</td>
                            <td>${pill(isActive ? 'green' : 'slate', isActive ? __('active', 'Active') : __('inactive', 'Inactive'))}</td>
                            <td><div class="tt-week">${scheduleHtml}</div></td>
                            <td>${assignedTo}</td>
                            <td class="aca-actions">
                                ${toggleBtnHtml}
                                <button type="button" class="sr-btn sr-btn-ghost sr-btn-sm sr-btn-icon btn-edit-timetable" data-id="${escapeHtml(t.id)}" title="${__('edit')}"><i class="mdi mdi-pencil"></i></button>
                                ${isDefault ? '' : `<button type="button" class="sr-btn sr-btn-ghost sr-btn-sm sr-btn-icon ac-remove btn-delete-timetable" data-id="${escapeHtml(t.id)}" data-name="${name}" title="${__('delete')}"><i class="mdi mdi-delete"></i></button>`}
                            </td>
                        </tr>
                    `;
                });

                container.innerHTML = `
                    <div class="sr-table-wrap aca-table-wrap">
                        <table class="sr-table aca-table org-table tt-table">
                            <thead>
                                <tr>
                                    <th>${__('name')}</th>
                                    <th>${__('type', 'Type')}</th>
                                    <th>${__('status', 'Status')}</th>
                                    <th>${__('weekly_schedule', 'Weekly Schedule')}</th>
                                    <th>${__('assigned_to', 'Assigned To')}</th>
                                    <th class="aca-actions">${__('actions')}</th>
                                </tr>
                            </thead>
                            <tbody>${rowsHtml}</tbody>
                        </table>
                    </div>`;

                container.querySelectorAll('.btn-edit-timetable').forEach(btn => {
                    btn.addEventListener('click', () => showTimetableModal(timetables.find(t => Number(t.id) === Number(btn.dataset.id))));
                });
                container.querySelectorAll('.btn-delete-timetable').forEach(btn => {
                    btn.addEventListener('click', () => deleteTimetable(btn.dataset.id, btn.dataset.name));
                });
                container.querySelectorAll('.btn-toggle-timetable-active').forEach(btn => {
                    btn.addEventListener('click', () => toggleTimetableActive(btn.dataset.id, btn.dataset.name, btn.dataset.active === '1'));
                });
            } catch (error) {
                container.innerHTML = errorHtml(error.message);
            }
        }

        async function showTimetableModal(timetable) {
            let companies = [];
            try {
                const response = await fetch('./includes/ajaxFile/timetableAjax.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ action: 'list_companies' })
                });
                const data = await response.json();
                companies = data.status === 'success' ? data.companies : [];
            } catch (error) {
                companies = [];
            }

            const timetablesRes = await fetch('./includes/ajaxFile/timetableAjax.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ action: 'list_timetables' })
            });
            const timetablesData = await timetablesRes.json();
            const timetableNameById = {};
            const timetableActiveById = {};
            (timetablesData.timetables || []).forEach(t => {
                timetableNameById[t.id] = t.name;
                timetableActiveById[t.id] = Number(t.id) === 1 || Number(t.is_active) === 1;
            });

            const isEdit = !!timetable;
            const t = timetable || { id: 0, name: '', days: {}, is_temporary: 0, start_date: '', end_date: '', employees: [] };
            const tId = Number(t.id);
            const isTemporary = Number(t.is_temporary) === 1;
            const assignedCompanyIds = new Set(companies.filter(c => Number(c.timetable_id) === tId).map(c => Number(c.comp_id)));

            const defaultDayFor = iso => ({
                is_off: (iso === 5 || iso === 6) ? 1 : 0,
                check_in: '08:00', check_in_start: '07:45', check_in_end: '08:15',
                check_out: '17:00', check_out_start: '16:45', check_out_end: '17:15',
                standard_hours: 8,
            });

            // Landscape layout: one compact table row per weekday instead of a
            // stacked card per day - keeps the modal wide/short instead of tall.
            // Day Off just greys/disables that row's time fields in place rather
            // than hiding/reflowing them, so every row keeps the same column shape.
            const timeCell = (cls, d, disabled) => `
                <td>
                    <div class="input-group input-group-sm clockpicker" style="min-width: 92px; ${disabled ? 'opacity: 0.5; pointer-events: none;' : ''}">
                        <input type="text" class="form-control ${cls} tt-time-input" autocomplete="off" ${disabled ? 'disabled' : ''} value="${d[cls.replace('tt-', '')] || ''}">
                        <span class="input-group-addon"><i class="mdi mdi-clock-outline"></i></span>
                    </div>
                </td>`;
            let dayRowsHtml = '';
            [1, 2, 3, 4, 5, 6, 7].forEach(iso => {
                const d = (t.days && (t.days[iso] || t.days[String(iso)])) || defaultDayFor(iso);
                const isOff = Number(d.is_off) === 1;
                dayRowsHtml += `
                    <tr class="tt-day-row" data-weekday="${iso}">
                        <td class="align-middle"><strong>${DAY_OFF_LABELS[iso]}</strong></td>
                        <td class="align-middle text-center">
                            <input type="checkbox" class="tt-day-off" id="tt-day-off-${iso}" style="width: 18px; height: 18px; margin: 0;" ${isOff ? 'checked' : ''}>
                        </td>
                        ${timeCell('tt-check_in_start', d, isOff)}
                        ${timeCell('tt-check_in', d, isOff)}
                        ${timeCell('tt-check_in_end', d, isOff)}
                        ${timeCell('tt-check_out_start', d, isOff)}
                        ${timeCell('tt-check_out', d, isOff)}
                        ${timeCell('tt-check_out_end', d, isOff)}
                        <td><input type="text" class="form-control form-control-sm tt-standard_hours" style="min-width: 60px;" readonly value="${d.standard_hours}"></td>
                    </tr>
                `;
            });

            // A company already locked to a different custom (non-Default) timetable
            // can't be checked here - it has to be freed from that timetable first.
            // That only applies while the other timetable is actually active though -
            // an inactive/draft timetable isn't governing that company's attendance
            // right now, so there's nothing live to conflict with.
            let companyCheckboxesHtml = '';
            companies.forEach(c => {
                const compTimetableId = Number(c.timetable_id) || 1;
                const currentlyOn = timetableNameById[c.timetable_id] || 'Default';
                const lockedElsewhere = compTimetableId !== 1 && compTimetableId !== tId && timetableActiveById[compTimetableId];
                companyCheckboxesHtml += `
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input tt-company" id="tt-company-${c.comp_id}" value="${c.comp_id}"
                            ${assignedCompanyIds.has(Number(c.comp_id)) ? 'checked' : ''} ${lockedElsewhere ? 'disabled' : ''}>
                        <label class="custom-control-label" for="tt-company-${c.comp_id}">${c.comp_name}
                            <small class="text-muted">(${__('currently', 'currently')}: ${currentlyOn})</small>
                            ${lockedElsewhere ? `<i class="mdi mdi-lock text-warning" title="${__('remove_from_other_timetable_first', 'Assigned elsewhere - remove it there first')}"></i>` : ''}
                        </label>
                    </div>
                `;
            });

            const result = await Swal.fire({
                title: isEdit ? '' + __('edit_timetable', 'Edit Timetable') + '' : '' + __('add_timetable', 'Add Timetable') + '',
                width: '90%',
                html: `
                    <div class="form-group text-left">
                        <label>${__('name')} *</label>
                        <input type="text" id="tt-name" class="form-control" value="${t.name}" ${tId === 1 ? 'readonly' : ''}>
                    </div>
                    <ul class="nav nav-tabs mb-3">
                        <li class="nav-item"><a href="javascript:void(0)" class="nav-link active tt-tab-link" data-target="tt-tab-assignment">${__('assignment', 'Assignment')}</a></li>
                        <li class="nav-item"><a href="javascript:void(0)" class="nav-link tt-tab-link" data-target="tt-tab-schedule">${__('weekly_schedule', 'Weekly Schedule')}</a></li>
                    </ul>
                    <div id="tt-tab-assignment" class="tt-tab-pane">
                        ${tId !== 1 ? `
                        <div class="form-group text-left">
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" name="tt-assign-type" id="tt-assign-company" class="custom-control-input" value="company" ${!isTemporary ? 'checked' : ''}>
                                <label class="custom-control-label" for="tt-assign-company">${__('company_wise', 'Company-wise')}</label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" name="tt-assign-type" id="tt-assign-employee" class="custom-control-input" value="employee" ${isTemporary ? 'checked' : ''}>
                                <label class="custom-control-label" for="tt-assign-employee">${__('employee_wise', 'Employee-wise')}</label>
                            </div>
                            <small class="form-text text-muted">${__('employee_wise_hint', "Employee-wise overrides those specific employees' company timetable directly - permanently, or optionally only for a date range.")}</small>
                        </div>
                        ` : ''}
                        <div id="tt-company-section" class="form-group text-left">
                            <label>${__('assigned_companies', 'Assigned Companies')}</label>
                            <div style="max-height: 260px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 4px; padding: 8px;">${companyCheckboxesHtml}</div>
                        </div>
                        <div id="tt-employee-section" class="form-group text-left" style="display:none;">
                            <label>${__('assigned_employees', 'Assigned Employees')} *</label>
                            <select id="tt-employees" class="form-control" multiple style="width: 100%;"></select>
                            <div class="custom-control custom-checkbox mt-2">
                                <input type="checkbox" class="custom-control-input" id="tt-use-daterange" ${(t.start_date && t.end_date) ? 'checked' : ''}>
                                <label class="custom-control-label" for="tt-use-daterange">${__('limit_to_date_range', 'Limit to a date range (leave unchecked to always apply)')}</label>
                            </div>
                            <div id="tt-daterange-wrap" class="mt-2" style="${(t.start_date && t.end_date) ? '' : 'display:none;'}">
                                <input type="text" id="tt-daterange" class="form-control" readonly placeholder="${__('select_date_range', 'Select date range')}" value="${t.start_date && t.end_date ? `${t.start_date} - ${t.end_date}` : ''}">
                                <small class="form-text text-muted" id="tt-daterange-duration"></small>
                            </div>
                        </div>
                    </div>
                    <div id="tt-tab-schedule" class="tt-tab-pane" style="display:none;">
                        <div class="form-group text-left">
                            <div class="table-responsive" style="border: 1px solid #dee2e6; border-radius: 4px;">
                                <table class="table table-sm table-bordered mb-0 align-middle">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>${__('day', 'Day')}</th>
                                            <th class="text-center">${__('day_off', 'Day Off')}</th>
                                            <th>${__('check_in_start', 'Check-In Earliest')}</th>
                                            <th>${__('check_in', 'Check-In')}</th>
                                            <th>${__('check_in_end', 'Late After')}</th>
                                            <th>${__('check_out_start', 'Early Before')}</th>
                                            <th>${__('check_out', 'Check-Out')}</th>
                                            <th>${__('check_out_end', 'Check-Out Latest')}</th>
                                            <th>${__('hours', 'Hours')}</th>
                                        </tr>
                                    </thead>
                                    <tbody>${dayRowsHtml}</tbody>
                                </table>
                            </div>
                            <small class="form-text text-muted">${__('standard_hours_auto_hint', 'Hours column auto-calculates from Check-In / Check-Out.')}</small>
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonColor: APP_COLORS.primary,
                cancelButtonColor: APP_COLORS.danger_dark,
                confirmButtonText: '' + __('save', 'Save') + '',
                cancelButtonText: '' + __('cancel') + '',
                showLoaderOnConfirm: true,
                allowOutsideClick: false,
                didOpen: () => {
                    const $companySection = $('#tt-company-section');
                    const $employeeSection = $('#tt-employee-section');
                    const $empSelect = $('#tt-employees');

                    $empSelect.select2({
                        theme: 'bootstrap4',
                        dropdownParent: $(Swal.getPopup()),
                        placeholder: '' + __('search_employees', 'Search employees...') + '',
                        ajax: {
                            url: './includes/ajaxFile/timetableAjax.php',
                            type: 'POST',
                            dataType: 'json',
                            delay: 250,
                            data: params => ({ action: 'search_employees', search: params.term }),
                            processResults: response => ({ results: response.status === 'success' ? response.results : [] })
                        }
                    });
                    (t.employees || []).forEach(emp => {
                        $empSelect.append(new Option(`${emp.emp_id} - ${emp.name}`, emp.emp_id, true, true));
                    });
                    $empSelect.trigger('change');

                    if (window.AppDate && typeof moment !== 'undefined') {
                        const $range = $('#tt-daterange');
                        const $duration = $('#tt-daterange-duration');
                        const fmt = 'YYYY-MM-DD';
                        const startVal = t.start_date ? moment(t.start_date, fmt) : moment();
                        const endVal = t.end_date ? moment(t.end_date, fmt) : moment();

                        const updateDuration = (start, end) => {
                            // Calendar-aware breakdown (e.g. Sep 8 -> Oct 10 = "1 month, 3 days"),
                            // not a flat 30-day-per-month estimate - both endpoints count as active days.
                            const inclusiveEnd = end.clone().add(1, 'day');
                            const months = inclusiveEnd.diff(start, 'months');
                            const remainderStart = start.clone().add(months, 'months');
                            const days = inclusiveEnd.diff(remainderStart, 'days');
                            const totalDays = inclusiveEnd.diff(start, 'days');
                            const parts = [];
                            if (months > 0) parts.push(`${months} ${__('month_s', 'month(s)')}`);
                            if (days > 0 || months === 0) parts.push(`${days} ${__('day_s', 'day(s)')}`);
                            $duration.text(`${__('duration', 'Duration')}: ${parts.join(', ')} (${totalDays} ${__('days_total', 'days total')})`);
                        };

                        // Value stays "YYYY-MM-DD - YYYY-MM-DD" (read back on save).
                        AppDate.range($range, {
                            defaultDate: (t.start_date && t.end_date) ? [t.start_date, t.end_date] : null,
                            onChange: (dates) => {
                                if (dates.length === 2) updateDuration(moment(dates[0]), moment(dates[1]));
                            }
                        });
                        if (!t.start_date || !t.end_date) {
                            $duration.text('');
                        } else {
                            updateDuration(startVal, endVal);
                        }
                    }

                    const syncSections = () => {
                        const checkedRadio = document.querySelector('input[name="tt-assign-type"]:checked');
                        const on = tId === 1 ? false : (checkedRadio ? checkedRadio.value === 'employee' : isTemporary);
                        $companySection.toggle(!on);
                        $employeeSection.toggle(on);
                    };
                    $(Swal.getPopup()).on('change', 'input[name="tt-assign-type"]', syncSections);
                    syncSections();

                    $('#tt-use-daterange').on('change', function () {
                        $('#tt-daterange-wrap').toggle(this.checked);
                        if (!this.checked) {
                            if (window.AppDate && AppDate.get('#tt-daterange')) AppDate.get('#tt-daterange').clear();
                            $('#tt-daterange').val('');
                            $('#tt-daterange-duration').text('');
                        }
                    });

                    const $popup = $(Swal.getPopup());
                    $popup.on('click', '.tt-tab-link', function () {
                        $popup.find('.tt-tab-link').removeClass('active');
                        $(this).addClass('active');
                        $popup.find('.tt-tab-pane').hide();
                        $popup.find('#' + $(this).data('target')).show();
                    });
                    if ($.fn.clockpicker) {
                        $popup.find('.clockpicker').clockpicker({ autoclose: true, twelvehour: false, placement: 'bottom', align: 'left' });
                    }
                    const calcStandardHours = (inVal, outVal) => {
                        if (!/^\d{2}:\d{2}$/.test(inVal) || !/^\d{2}:\d{2}$/.test(outVal)) return '';
                        const [ih, im] = inVal.split(':').map(Number);
                        const [oh, om] = outVal.split(':').map(Number);
                        let diff = (oh * 60 + om) - (ih * 60 + im);
                        if (diff < 0) diff += 24 * 60;
                        return (diff / 60).toFixed(1);
                    };
                    $popup.on('change input', '.tt-check_in, .tt-check_out', function () {
                        const $row = $(this).closest('.tt-day-row');
                        $row.find('.tt-standard_hours').val(calcStandardHours($row.find('.tt-check_in').val(), $row.find('.tt-check_out').val()));
                    });
                    $popup.on('change', '.tt-day-off', function () {
                        const $row = $(this).closest('.tt-day-row');
                        const off = this.checked;
                        $row.find('.tt-time-input').prop('disabled', off);
                        $row.find('.clockpicker').css({ opacity: off ? 0.5 : 1, pointerEvents: off ? 'none' : 'auto' });
                    });
                },
                preConfirm: () => {
                    const name = document.getElementById('tt-name').value.trim();
                    if (!name) {
                        Swal.showValidationMessage('' + __('fill_required_fields', 'Please fill all required fields.') + '');
                        return false;
                    }
                    const assignTypeEl = document.querySelector('input[name="tt-assign-type"]:checked');
                    const useTemporary = tId !== 1 && !!assignTypeEl && assignTypeEl.value === 'employee';

                    const payload = new URLSearchParams({
                        action: 'add_edit_timetable',
                        id: t.id,
                        name,
                        is_temporary: useTemporary ? '1' : '0',
                    });

                    const days = {};
                    document.querySelectorAll('.tt-day-row').forEach(row => {
                        const iso = row.dataset.weekday;
                        const isOff = row.querySelector('.tt-day-off').checked;
                        days[iso] = {
                            is_off: isOff ? 1 : 0,
                            check_in: row.querySelector('.tt-check_in').value,
                            check_in_start: row.querySelector('.tt-check_in_start').value,
                            check_in_end: row.querySelector('.tt-check_in_end').value,
                            check_out: row.querySelector('.tt-check_out').value,
                            check_out_start: row.querySelector('.tt-check_out_start').value,
                            check_out_end: row.querySelector('.tt-check_out_end').value,
                            standard_hours: row.querySelector('.tt-standard_hours').value || '0',
                        };
                    });
                    payload.append('days', JSON.stringify(days));

                    if (useTemporary) {
                        const empIds = $('#tt-employees').val() || [];
                        if (empIds.length === 0) {
                            Swal.showValidationMessage('' + __('select_at_least_one_employee', 'Select at least one employee.') + '');
                            return false;
                        }
                        let startDate = '';
                        let endDate = '';
                        if (document.getElementById('tt-use-daterange').checked) {
                            const rangeValue = document.getElementById('tt-daterange').value.trim();
                            const rangeParts = rangeValue.split(' - ');
                            startDate = rangeParts[0] || '';
                            endDate = rangeParts[1] || '';
                            if (!startDate || !endDate) {
                                Swal.showValidationMessage('' + __('fill_required_fields', 'Please fill all required fields.') + '');
                                return false;
                            }
                        }
                        payload.append('start_date', startDate);
                        payload.append('end_date', endDate);
                        empIds.forEach(id => payload.append('employee_ids[]', id));
                    } else {
                        document.querySelectorAll('.tt-company:checked').forEach(cb => payload.append('company_ids[]', cb.value));
                    }

                    return fetch('./includes/ajaxFile/timetableAjax.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: payload
                    }).then(r => r.json()).then(res => {
                        if (res.status !== 'success') {
                            Swal.showValidationMessage(res.message || '' + __('could_not_save_settings') + '');
                            return false;
                        }
                        return res;
                    }).catch(err => {
                        Swal.showValidationMessage(err.message);
                        return false;
                    });
                },
            });

            if (result.isConfirmed) {
                loadTimetables();
                const recalculated = (result.value && result.value.recalculated) || 0;
                const recalcNote = recalculated > 0
                    ? ` ${recalculated} ${__('recalculated_days_note', 'attendance day(s) recalculated to match.')}`
                    : '';
                Swal.fire('' + __('saved', 'Saved') + '', `${__('timetable_saved', 'Timetable saved.')}${recalcNote}`, 'success');
            }
        }

        function deleteTimetable(id, name) {
            Swal.fire({
                title: '' + __('are_you_sure') + '',
                text: name,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: APP_COLORS.danger_dark,
                cancelButtonColor: APP_COLORS.primary,
                confirmButtonText: '' + __('yes_delete_it') + '',
                cancelButtonText: '' + __('cancel') + '',
            }).then(result => {
                if (!result.isConfirmed) return;
                fetch('./includes/ajaxFile/timetableAjax.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ action: 'delete_timetable', id })
                }).then(r => r.json()).then(res => {
                    if (res.status === 'success') {
                        loadTimetables();
                    } else {
                        Swal.fire('' + __('error') + '', res.message || '' + __('could_not_save_settings') + '', 'error');
                    }
                });
            });
        }

        function toggleTimetableActive(id, name, activate) {
            const doToggle = () => {
                fetch('./includes/ajaxFile/timetableAjax.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ action: 'toggle_timetable_active', id, active: activate ? 1 : 0 })
                }).then(r => r.json()).then(res => {
                    if (res.status === 'success') {
                        loadTimetables();
                    } else {
                        Swal.fire('' + __('error') + '', res.message || '' + __('could_not_save_settings') + '', 'error');
                    }
                });
            };

            if (!activate) {
                Swal.fire({
                    title: '' + __('are_you_sure') + '',
                    text: name,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: APP_COLORS.danger_dark,
                    cancelButtonColor: APP_COLORS.primary,
                    confirmButtonText: '' + __('deactivate', 'Deactivate') + '',
                    cancelButtonText: '' + __('cancel') + '',
                }).then(result => {
                    if (result.isConfirmed) doToggle();
                });
                return;
            }
            doToggle();
        }

        // Salary components an admin can pick from for the deduction base (e.g. GOSI base).
        const DEDUCTION_BASE_COMPONENT_LABELS = {
            basic_salary: '' + __('basic_salary', 'Basic Salary') + '',
            housing_allowance: '' + __('housing_allowance', 'Housing Allowance') + '',
            transport_allowance: '' + __('transport_allowance', 'Transportation Allowance') + '',
            food_allowance: '' + __('food_allowance', 'Food Allowance') + '',
            miscellaneous_allowance: '' + __('miscellaneous_allowance', 'Miscellaneous Allowance') + '',
            cashier_allowance: '' + __('cashier_allowance', 'Cashier Allowance') + '',
            fuel_allowance: '' + __('fuel_allowance', 'Fuel Allowance') + '',
            telephone_allowance: '' + __('telephone_allowance', 'Telephone Allowance') + '',
            other_allowance: '' + __('other_allowance', 'Other Allowance') + '',
            guard_allowance: '' + __('guard_allowance', 'Guard Allowance') + '',
        };

        async function renderDeductionSettingsGroup(hostEl) {
            hostEl.innerHTML = `
                <div class="sr-card as-section">
                    <div class="sr-card-head">
                        <div>
                            <div class="sr-card-title"><i class="mdi mdi-percent"></i> ${__('deduction_base_components', 'Deduction Base Components')}</div>
                            <div class="sr-card-sub">${__('deduction_base_components_hint', 'Select which salary components are summed as the base for percentage-based deductions (e.g. GOSI).')}</div>
                        </div>
                    </div>
                    <div id="deduction-base-components-fields">
                        <div class="ac-loading"><span class="spinner-border spinner-border-sm" role="status"></span> ${__('loading')}</div>
                    </div>
                </div>
                <div class="sr-card as-section">
                    <div class="sr-card-head">
                        <div class="sr-card-title"><i class="mdi mdi-minus-circle-outline"></i> ${__('deduction_types', 'Deduction Types')} <span class="sr-count" id="deduction-types-count">-</span></div>
                        <button type="button" class="sr-btn sr-btn-success sr-btn-sm" id="btn-add-deduction-type"><i class="mdi mdi-plus"></i> ${__('add_new', 'Add New')}</button>
                    </div>
                    <div id="deduction-types-container">
                        <div class="ac-loading"><span class="spinner-border spinner-border-sm" role="status"></span> ${__('loading')}</div>
                    </div>
                </div>
            `;

            loadDeductionBaseComponents();
            loadDeductionTypes();

            document.getElementById('btn-add-deduction-type').addEventListener('click', () => showDeductionTypeModal(null));
        }

        function deductionError(message) {
            return `<div class="as-section-body"><div class="sr-notice tone-red ac-error"><i class="mdi mdi-alert-circle-outline"></i><div>${escapeHtml(message)}</div></div></div>`;
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
                    container.innerHTML = deductionError(data.message || __('access_denied', 'Access denied'));
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
                const autoAttendanceSetting = data.settings.find(s => s.setting_name === 'deduction_auto_attendance_enabled');
                const autoAttendanceEnabled = autoAttendanceSetting && autoAttendanceSetting.setting_value === '1';
                const autoAttendanceLabel = autoAttendanceSetting ? translateText(autoAttendanceSetting.description) : '' + __('deduction_auto_attendance_enabled_label', 'Automatically add Late/Early-Leave deductions to payroll from attendance') + '';

                let html = '<div class="as-section-body"><div class="as-check-grid">';
                Object.entries(DEDUCTION_BASE_COMPONENT_LABELS).forEach(([key, label]) => {
                    html += `
                        <label class="as-check-tile">
                            <input type="checkbox" class="deduction-base-component-checkbox" id="dbc-${key}" value="${key}" ${selected.includes(key) ? 'checked' : ''}>
                            <span><i class="mdi mdi-check"></i>${escapeHtml(label)}</span>
                        </label>
                    `;
                });
                html += '</div>';
                if (ATTENDANCE_ON) { // attendance switched off (Integrations) - switch hidden, saved value kept
                    html += `
                        <div class="as-switch-row">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="deduction-auto-attendance-toggle" ${autoAttendanceEnabled ? 'checked' : ''}>
                                <label class="custom-control-label" for="deduction-auto-attendance-toggle">${escapeHtml(autoAttendanceLabel)}</label>
                            </div>
                        </div>
                    `;
                }
                html += `</div>
                    <div class="as-section-foot">
                        <button type="button" class="sr-btn sr-btn-primary sr-btn-sm" id="btn-save-deduction-base"><i class="mdi mdi-content-save"></i> ${__('save', 'Save')}</button>
                    </div>`;
                container.innerHTML = html;

                document.getElementById('btn-save-deduction-base').addEventListener('click', async function() {
                    const btn = this;
                    const chosen = Array.from(container.querySelectorAll('.deduction-base-component-checkbox:checked')).map(cb => cb.value);
                    const autoAttendanceToggle = document.getElementById('deduction-auto-attendance-toggle');
                    btn.disabled = true;
                    try {
                        const saveResponse = await fetch('./includes/payroll_settings_handler.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: new URLSearchParams(Object.assign({
                                action: 'update_payroll_settings',
                                group: 'deduction_settings',
                                deduction_base_components: JSON.stringify(chosen)
                            }, autoAttendanceToggle ? { deduction_auto_attendance_enabled: autoAttendanceToggle.checked ? '1' : '0' } : {}))
                        });
                        const saveResult = await saveResponse.json();
                        if (saveResult.success) {
                            acToast('success', __('your_settings_have_been_updated_successfully'));
                        } else {
                            Swal.fire('' + __('error') + '', saveResult.message || '' + __('could_not_save_settings') + '', 'error');
                        }
                    } catch (error) {
                        Swal.fire('' + __('request_failed') + '', error.message, 'error');
                    } finally {
                        btn.disabled = false;
                    }
                });
            } catch (error) {
                container.innerHTML = deductionError(error.message);
            }
        }

        async function loadDeductionTypes() {
            const container = document.getElementById('deduction-types-container');
            if (!container) return;
            const countEl = document.getElementById('deduction-types-count');
            try {
                const response = await fetch('./includes/payroll_settings_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ action: 'get_deduction_types' })
                });
                const data = await response.json();

                if (!data.success) {
                    container.innerHTML = deductionError(data.message || __('access_denied', 'Access denied'));
                    return;
                }

                const types = Array.isArray(data.deduction_types) ? data.deduction_types : [];
                if (countEl) countEl.textContent = types.length;
                if (types.length === 0) {
                    container.innerHTML = `<div class="sr-empty"><i class="mdi mdi-minus-circle-outline"></i>${__('no_deduction_types_configured_yet', 'No deduction types configured yet')}</div>`;
                    return;
                }

                let rowsHtml = '';
                types.forEach(type => {
                    const active = Number(type.status) === 1;
                    rowsHtml += `
                        <tr data-id="${escapeHtml(type.id)}" data-name="${escapeHtml(type.name)}"${active ? '' : ' class="is-inactive"'}>
                            <td>
                                <div class="org-name">
                                    <span class="ac-ico"><i class="mdi mdi-minus-circle-outline"></i></span>
                                    <span class="sr-cell-title">${escapeHtml(type.name)}</span>
                                </div>
                            </td>
                            <td>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input deduction-type-counts-toggle" id="dt-counts-${escapeHtml(type.id)}" data-id="${escapeHtml(type.id)}" ${Number(type.counts_in_net) === 1 ? 'checked' : ''}>
                                    <label class="custom-control-label" for="dt-counts-${escapeHtml(type.id)}"></label>
                                </div>
                            </td>
                            <td>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input deduction-type-status-toggle" id="dt-status-${escapeHtml(type.id)}" data-id="${escapeHtml(type.id)}" ${active ? 'checked' : ''}>
                                    <label class="custom-control-label" for="dt-status-${escapeHtml(type.id)}"></label>
                                </div>
                            </td>
                            <td class="aca-actions">
                                <button type="button" class="sr-btn sr-btn-ghost sr-btn-sm sr-btn-icon edit-deduction-type-btn" data-id="${escapeHtml(type.id)}" title="${__('edit')}"><i class="mdi mdi-pencil"></i></button>
                                <button type="button" class="sr-btn sr-btn-ghost sr-btn-sm sr-btn-icon ac-remove delete-deduction-type-btn" data-id="${escapeHtml(type.id)}" title="${__('delete')}"><i class="mdi mdi-delete"></i></button>
                            </td>
                        </tr>
                    `;
                });
                container.innerHTML = `
                    <div class="sr-table-wrap aca-table-wrap">
                        <table class="sr-table aca-table org-table">
                            <thead>
                                <tr>
                                    <th>${__('name')}</th>
                                    <th>${__('counts_in_net_pay', 'Counts in Net Pay')}</th>
                                    <th>${__('active')}</th>
                                    <th class="aca-actions">${__('actions')}</th>
                                </tr>
                            </thead>
                            <tbody>${rowsHtml}</tbody>
                        </table>
                    </div>`;

                container.querySelectorAll('.deduction-type-counts-toggle, .deduction-type-status-toggle').forEach(toggle => {
                    toggle.addEventListener('change', function() {
                        saveDeductionTypeToggle(this.dataset.id);
                    });
                });
                container.querySelectorAll('.edit-deduction-type-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        showDeductionTypeModal(this.closest('tr'));
                    });
                });
                container.querySelectorAll('.delete-deduction-type-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        deleteDeductionType(this.dataset.id, this.closest('tr').dataset.name);
                    });
                });
            } catch (error) {
                container.innerHTML = deductionError(error.message);
            }
        }

        function deductionTypePost(params) {
            return fetch('./includes/payroll_settings_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams(params)
            }).then(r => r.json());
        }

        async function saveDeductionTypeToggle(id) {
            const row = document.querySelector(`#deduction-types-container tr[data-id="${id}"]`);
            const countsInNet = row.querySelector('.deduction-type-counts-toggle').checked;
            const status = row.querySelector('.deduction-type-status-toggle').checked;
            row.classList.toggle('is-inactive', !status);

            try {
                const result = await deductionTypePost({
                    action: 'update_deduction_type',
                    id, name: row.dataset.name,
                    counts_in_net: countsInNet ? '1' : '0',
                    status: status ? '1' : '0'
                });
                if (result.success) {
                    acToast('success', __('saved', 'Saved'));
                } else {
                    Swal.fire('' + __('error') + '', result.message || '' + __('could_not_save_settings') + '', 'error');
                    loadDeductionTypes();
                }
            } catch (error) {
                Swal.fire('' + __('request_failed') + '', error.message, 'error');
                loadDeductionTypes();
            }
        }

        // Add (row = null) or rename an existing deduction type row
        function showDeductionTypeModal(row) {
            const isEdit = !!row;
            Swal.fire({
                title: isEdit ? __('edit_deduction_type', 'Edit Deduction Type') : __('add_new_deduction_type', 'Add New Deduction Type'),
                html: `
                    <form class="sr-form" onsubmit="return false">
                        <div class="sr-fsec">
                            <div class="sr-fsec-head"><span><i class="mdi mdi-minus-circle-outline"></i> ${__('deduction_types', 'Deduction Types')}</span></div>
                            <div class="sr-fgrid">
                                <div class="sr-fcol c-12">
                                    <label for="deduction-type-name">${__('name')} <span class="text-danger">*</span></label>
                                    <input type="text" id="deduction-type-name" class="form-control" autocomplete="off" value="${isEdit ? escapeHtml(row.dataset.name) : ''}" placeholder="${__('e.g. Late Deduction')}">
                                </div>
                                ${isEdit ? '' : `<div class="sr-fcol c-12">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="deduction-type-counts" checked>
                                        <label class="custom-control-label" for="deduction-type-counts">${__('counts_in_net_pay', 'Counts in Net Pay')}</label>
                                    </div>
                                </div>`}
                            </div>
                        </div>
                    </form>`,
                width: '520px',
                allowOutsideClick: false,
                showCancelButton: true,
                confirmButtonText: isEdit ? `<i class="mdi mdi-content-save"></i> ${__('save', 'Save')}` : `<i class="mdi mdi-plus"></i> ${__('add', 'Add')}`,
                cancelButtonText: __('cancel'),
                showLoaderOnConfirm: true,
                didOpen: () => {
                    const input = document.getElementById('deduction-type-name');
                    input.addEventListener('input', () => input.classList.remove('is-invalid'));
                    input.focus();
                },
                preConfirm: async () => {
                    const input = document.getElementById('deduction-type-name');
                    const name = input.value.trim();
                    if (!name) {
                        input.classList.add('is-invalid');
                        Swal.showValidationMessage('' + __('name_is_required', 'Name is required') + '');
                        return false;
                    }
                    const params = isEdit
                        ? {
                            action: 'update_deduction_type', id: row.dataset.id, name,
                            counts_in_net: row.querySelector('.deduction-type-counts-toggle').checked ? '1' : '0',
                            status: row.querySelector('.deduction-type-status-toggle').checked ? '1' : '0'
                        }
                        : { action: 'add_deduction_type', name, counts_in_net: document.getElementById('deduction-type-counts').checked ? '1' : '0' };
                    try {
                        const result = await deductionTypePost(params);
                        if (!result.success) throw new Error(result.message || __('could_not_save_settings'));
                        return true;
                    } catch (error) {
                        Swal.showValidationMessage(error.message);
                        return false;
                    }
                }
            }).then(result => {
                if (result.isConfirmed && result.value) {
                    acToast('success', __('saved', 'Saved'));
                    loadDeductionTypes();
                }
            });
        }

        function deleteDeductionType(id, name) {
            Swal.fire({
                icon: 'warning',
                title: '' + __('are_you_sure', 'Are you sure?') + '',
                html: `<div class="sr-form"><div class="sr-notice tone-red is-compact"><i class="mdi mdi-alert-outline"></i>
                        <div>${name ? `<strong>${escapeHtml(name)}</strong><br>` : ''}${__('this_action_cannot_be_undone', 'This action cannot be undone.')}</div></div></div>`,
                width: '480px',
                allowOutsideClick: false,
                showCancelButton: true,
                confirmButtonText: `<i class="mdi mdi-delete"></i> ${__('delete')}`,
                cancelButtonText: __('cancel'),
                confirmButtonColor: '#dc2626',
                showLoaderOnConfirm: true,
                preConfirm: async () => {
                    try {
                        const result = await deductionTypePost({ action: 'delete_deduction_type', id });
                        if (!result.success) throw new Error(result.message || __('could_not_save_settings'));
                        return true;
                    } catch (error) {
                        Swal.showValidationMessage(error.message);
                        return false;
                    }
                }
            }).then(result => {
                if (result.isConfirmed && result.value) {
                    acToast('success', __('deleted', 'Deleted'));
                    loadDeductionTypes();
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

        function attachTestEmailListener(prefix = '') {
            const btn = document.getElementById('testEmailConfigBtn');
            if (!btn) return;

            btn.addEventListener('click', async function() {
                const resultDiv = document.getElementById('testEmailResult');
                const getVal = (name) => (document.getElementById(`setting-${prefix}${name}`)?.value || '').trim();
                const fromKey = (name) => (prefix ? 'smtp_' + name : name);

                const host = getVal('smtp_host');
                const port = getVal('smtp_port');
                const user = getVal('smtp_user');
                const pass = getVal('smtp_pass');
                const encryption = getVal('smtp_encryption') || 'tls';
                const fromEmail = getVal(fromKey('from_email'));
                const fromName = getVal(fromKey('from_name'));
                const adminEmail = prefix ? '' : getVal('admin_email');

                if (!host || !port || !user || !pass || !fromEmail) {
                    resultDiv.innerHTML = '';
                    Swal.fire('' + __('missing_fields', 'Missing Fields') + '', '' + __('fill_smtp_host_port_username_password_and_default_from_email_before_testing', 'Please fill Host, Port, Username, Password and Default From Email Address before testing.') + '', 'warning');
                    return;
                }

                btn.disabled = true;
                resultDiv.innerHTML = `<small class="text-muted"><div class="spinner-border spinner-border-sm" role="status"></div> ${__('sending_test_email', 'Sending test email...')}</small>`;

                try {
                    const response = await fetch('./includes/settings_handler.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({
                            action: 'test_email_settings',
                            smtp_host: host,
                            smtp_port: port,
                            smtp_user: user,
                            smtp_pass: pass,
                            smtp_encryption: encryption,
                            from_email: fromEmail,
                            from_name: fromName,
                            admin_email: adminEmail
                        })
                    });
                    const result = await response.json();
                    btn.disabled = false;

                    if (result.success) {
                        resultDiv.innerHTML = `<small class="text-success"><i class="mdi mdi-check-circle"></i> ${escapeHtml(result.message)}</small>`;
                        Swal.fire('' + __('success', 'Success') + '', result.message, 'success');
                    } else {
                        resultDiv.innerHTML = `<small class="text-danger"><i class="mdi mdi-alert-circle"></i> ${escapeHtml(result.message || '' + __('could_not_send_test_email', 'Could not send test email.') + '')}</small>`;
                        Swal.fire('' + __('failed', 'Failed') + '', result.message || '' + __('could_not_send_test_email', 'Could not send test email.') + '', 'error');
                    }
                } catch (error) {
                    btn.disabled = false;
                    resultDiv.innerHTML = `<small class="text-danger">${escapeHtml(error.message)}</small>`;
                    Swal.fire('' + __('request_failed') + '', error.message, 'error');
                }
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
                    throw new Error('' + __('failed_to_load_users') + '');
                }

                const data = await response.json();
                if (!data.success || !Array.isArray(data.users)) {
                    throw new Error(data.message || '' + __('failed_to_load_users') + '');
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

        // Report Access no longer has its own tab - it's rendered inside the Special Access
        // editor by buildReportAccessBlockHtml()/wireReportAccessBlock() (see
        // openSpecialAccessEditModal below), and its assigned-user summary is folded into
        // renderAssignedSpecialAccessSummary().

        function updateSpecialAccessHiddenValue() {
            const hidden = document.getElementById('setting-special_access_by_user');
            if (hidden) {
                hidden.value = JSON.stringify(specialAccessMap);
            }
        }

        // Self-saves specialAccessMap/reportPermissionMap immediately, via the same generic
        // 'update_settings' action the page-level Save Changes button uses (scoped to just
        // these two JSON settings). This tab hides that button entirely (see
        // SELF_SAVING_GROUPS in renderSettingsGroup) - every add/edit/remove here must go
        // through this instead, or the change is silently lost.
        async function saveSpecialAccessSettings() {
            const response = await fetch('./includes/settings_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'update_settings',
                    special_access_by_user: JSON.stringify(specialAccessMap),
                    report_visibility_by_user: JSON.stringify(reportPermissionMap)
                })
            });
            const data = await response.json();
            if (!data.success) {
                throw new Error(data.message || '' + __('could_not_save_settings') + '');
            }
        }

        function setSpecialAccessPanelLoading(isLoading) {
            const overlay = document.getElementById('special-access-loading-overlay');
            if (overlay) overlay.style.display = isLoading ? 'flex' : 'none';
        }

        // Dims the whole panel (select-user card + assigned-users table) behind a spinner
        // overlay for a moment, then redraws the table - so after confirming the
        // "Updated"/"Removed" message the admin sees a clear loading state, visible proof
        // the save applied, not just an instant silent swap. A real timeout (not
        // requestAnimationFrame) is used deliberately - rAF's callback fires and resolves
        // its promise in a microtask that runs BEFORE the browser paints, so an
        // immediate show-then-hide across a single rAF tick never actually renders a
        // visible frame at all.
        async function refreshSpecialAccessTableWithLoader() {
            setSpecialAccessPanelLoading(true);
            await new Promise(resolve => setTimeout(resolve, 500));
            renderAssignedSpecialAccessSummary(specialAccessEligibleUsers);
            setSpecialAccessPanelLoading(false);
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

            setAssignedCountPill('special-access-total-users-badge', assignedEmpIds.length);

            if (!assignedEmpIds.length) {
                container.innerHTML = `<div class="sr-empty"><i class="mdi mdi-account-key"></i>${__('no_assigned_users_yet')}</div>`;
                return;
            }

            // Renders one user's granted keys as badges, grouped under a small uppercase
            // category label (same categories as the edit grid) instead of one flat run -
            // makes it scannable at a glance instead of a wall of identical blue badges.
            function buildGroupedBadges(grantedKeys) {
                grantedKeys = grantedKeys.filter(featureVisible);
                const grantedSet = new Set(grantedKeys);
                const placed = new Set();
                let out = '';

                categories.forEach(cat => {
                    const keysHere = cat.keys.filter(k => grantedSet.has(k));
                    if (!keysHere.length) return;
                    keysHere.forEach(k => placed.add(k));
                    out += `<div class="as-grant-group"><div class="special-access-group-label"><i class="fa ${cat.icon} mr-1"></i>${escapeHtml(cat.name)}</div>`;
                    out += `<div class="as-chip-wrap">${keysHere.map(key => `<span class="as-grant-chip">${escapeHtml(catalogMap.get(key) || key)}</span>`).join('')}</div></div>`;
                });

                const leftover = grantedKeys.filter(k => !placed.has(k));
                if (leftover.length) {
                    out += `<div class="as-grant-group"><div class="special-access-group-label">${__('other', 'Other')}</div>`;
                    out += `<div class="as-chip-wrap">${leftover.map(key => `<span class="as-grant-chip">${escapeHtml(catalogMap.get(key) || key)}</span>`).join('')}</div></div>`;
                }
                return out;
            }

            // Report access only gets a line here when the user has an EXPLICIT entry - if
            // they've never been touched they're on the "sees everything" default and there's
            // nothing to call out.
            function buildReportAccessBadges(empId) {
                if (!Object.prototype.hasOwnProperty.call(reportPermissionMap, empId)) return '';
                const grantedTypes = normalizeReportTypeList(reportPermissionMap[empId]).filter(featureVisible);
                let out = `<div class="as-grant-group"><div class="special-access-group-label"><i class="fa fa-chart-bar mr-1"></i>${__('report_access', 'Report Access')}</div><div class="as-chip-wrap">`;
                if (!grantedTypes.length) {
                    out += `<span class="as-grant-chip is-none">${__('no_reports', 'No reports')}</span>`;
                } else {
                    out += grantedTypes.map(type => `<span class="as-grant-chip is-report">${escapeHtml(reportCatalogMap.get(type) || type)}</span>`).join('');
                }
                return out + '</div></div>';
            }

            let html = '';

            assignedEmpIds.forEach(empId => {
                const user = userMap.get(empId);
                const name = user ? ((user.name || '').trim() || empId) : empId;
                const role = user ? ((user.user_type || '').trim()) : '';
                const grantedKeys = normalizeSpecialAccessList(specialAccessMap[empId]);
                const badgeCount = grantedKeys.length + (Object.prototype.hasOwnProperty.call(reportPermissionMap, empId) ? 1 : 0);

                html += '<div class="as-user-card">';
                html += srUserCardHead(name, empId, role, `<span class="sr-count">${badgeCount}</span>`, `
                    <button type="button" class="sr-btn sr-btn-ghost sr-btn-sm sr-btn-icon edit-assigned-special-access-user" data-emp-id="${escapeHtml(empId)}" title="${__('edit')}"><i class="mdi mdi-pencil"></i></button>
                    <button type="button" class="sr-btn sr-btn-ghost sr-btn-sm sr-btn-icon ac-remove remove-assigned-special-access-user" data-emp-id="${escapeHtml(empId)}" title="${__('remove')}"><i class="mdi mdi-delete"></i></button>`);
                html += `<div class="as-user-body">${buildGroupedBadges(grantedKeys)}${buildReportAccessBadges(empId)}</div>`;
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
                        title: '' + __('remove_user_assignment') + '',
                        text: '' + __('this_will_remove_special_access_and_report_access_for_this_user', 'This will remove all special access AND report access customizations for this user.') + '',
                        icon: 'warning',
                        allowOutsideClick: false,
                        confirmButtonColor: '#dc2626',
                        showCancelButton: true,
                        confirmButtonText: '' + __('yes_remove_it') + '',
                        cancelButtonText: '' + __('cancel') + ''
                    }).then(async (result) => {
                        if (!result.isConfirmed) return;

                        delete specialAccessMap[empId];
                        updateSpecialAccessHiddenValue();
                        delete reportPermissionMap[empId];
                        updateReportPermissionHiddenValue();
                        renderAssignedSpecialAccessSummary(specialAccessEligibleUsers);

                        Swal.fire({
                            title: '' + __('saving', 'Saving...') + '',
                            allowOutsideClick: false,
                            didOpen: () => Swal.showLoading()
                        });

                        try {
                            await saveSpecialAccessSettings();
                            await Swal.fire({
                                icon: 'success',
                                title: '' + __('removed') + '',
                                text: '' + __('your_settings_have_been_updated_successfully') + '',
                                confirmButtonText: '' + __('ok', 'OK') + ''
                            });
                            await refreshSpecialAccessTableWithLoader();
                        } catch (error) {
                            Swal.fire('' + __('error') + '', error.message, 'error');
                        }
                    });
                });
            });
        }

        // Single entry point for adding/editing a user's special access + report access:
        // a SweetAlert2 modal, opened either by picking a user from the top select or by
        // the "Edit" button on an assigned-user card. No inline editor - every change goes
        // through this modal's explicit Save/Cancel.
        function openSpecialAccessEditModal(empId) {
            const targetEmpId = String(empId || '').trim();
            if (!targetEmpId) return;

            const user = (specialAccessEligibleUsers || []).find(u => String(u.emp_id || '').trim() === targetEmpId);
            const displayName = user ? ((user.name || '').trim() || targetEmpId) : targetEmpId;
            const userType = user ? String(user.user_type || '').trim().toLowerCase() : '';
            const reportAccessApplicable = userType !== 'employee';

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

            const { panels, labelByKey } = buildSpecialAccessPanelData();
            const defaultPanelId = panels.length ? panels[0].id : 'report-access';

            let navHtml = '';
            panels.forEach((p, i) => {
                const granted = p.keys.filter(k => selectedSet.has(k)).length;
                navHtml += `
                    <div class="sae-tab${i === 0 ? ' active' : ''}" data-panel-target="${p.id}">
                        <span><i class="fa ${p.icon}"></i> ${escapeHtml(p.name)}</span>
                        <span class="badge ${granted ? 'badge-success' : 'badge-light'} sae-tab-count" data-count-for="${p.id}" data-total="${p.keys.length}">${granted}/${p.keys.length}</span>
                    </div>
                `;
            });
            if (reportAccessApplicable) {
                navHtml += `
                    <div class="sae-tab${panels.length === 0 ? ' active' : ''}" data-panel-target="report-access">
                        <span><i class="fa fa-chart-bar"></i> ${__('report_access', 'Report Access')}</span>
                        <span class="badge ${reportHasExplicit ? 'badge-info' : 'badge-light'}" id="swal-special-access-report-mode">${reportHasExplicit ? '' + __('custom', 'Custom') + '' : '' + __('all_default', 'All (default)') + ''}</span>
                    </div>
                `;
            }

            let panelsHtml = '';
            panels.forEach((p, i) => {
                panelsHtml += `<div class="sae-panel${i === 0 ? ' sae-panel-visible' : ''}" data-panel-id="${p.id}">`;
                panelsHtml += `<div class="sae-panel-title"><i class="fa ${p.icon} mr-1"></i> ${escapeHtml(p.name)}</div>`;
                panelsHtml += (p.name === 'Page Access')
                    ? renderGroupedCheckboxGrid('swal-special-access', getPageAccessSubgroups(), p.keys, labelByKey, selectedSet)
                    : renderSpecialAccessCheckboxGrid('swal-special-access', p.keys, labelByKey, selectedSet);
                panelsHtml += `</div>`;
            });
            if (reportAccessApplicable) {
                panelsHtml += `<div class="sae-panel${panels.length === 0 ? ' sae-panel-visible' : ''}" data-panel-id="report-access">`;
                panelsHtml += `<div class="sae-panel-title"><i class="fa fa-chart-bar mr-1"></i> ${__('report_access', 'Report Access')}</div>`;
                panelsHtml += buildReportAccessBlockHtml('swal-special-access', targetEmpId, false);
                panelsHtml += `</div>`;
            }

            let gridHtml = '<div class="text-left">';
            gridHtml += `<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">`;
            gridHtml += `<p class="text-muted mb-0 mr-2" style="font-size:.85rem;">${__('currently_granted', 'Currently granted')}: <span class="badge badge-${grantedCount ? 'success' : 'light'}" id="swal-special-access-total-badge">${grantedCount}</span></p>`;
            gridHtml += '<input type="text" class="form-control form-control-sm" id="swal-special-access-search" style="max-width:260px;" placeholder="' + __('search') + '...">';
            gridHtml += '</div>';
            gridHtml += '<div class="sae-layout">';
            gridHtml += `<div class="sae-sidebar" id="swal-special-access-sidebar">${navHtml}</div>`;
            gridHtml += `<div class="sae-content" id="swal-special-access-content">`;
            gridHtml += panelsHtml;
            gridHtml += `<div class="sae-empty-state" id="swal-special-access-no-results" style="display:none;">${__('no_matching_settings', 'No matching settings.')}</div>`;
            gridHtml += `</div>`;
            gridHtml += '</div>';
            gridHtml += '<div class="d-flex justify-content-end mt-2">';
            gridHtml += '<div class="as-btn-row">';
            gridHtml += '<button type="button" class="sr-btn sr-btn-sm" id="swal-special-access-select-all">' + __('select_all_visible', 'Select All (visible)') + '</button>';
            gridHtml += '<button type="button" class="sr-btn sr-btn-sm" id="swal-special-access-clear-all">' + __('clear_all_visible', 'Clear All (visible)') + '</button>';
            gridHtml += '</div>';
            gridHtml += '</div>';
            gridHtml += '</div>';

            Swal.fire({
                title: displayName,
                html: gridHtml,
                width: '78%',
                showCancelButton: true,
                confirmButtonText: '' + __('save_changes') + '',
                cancelButtonText: '' + __('cancel') + '',
                focusConfirm: false,
                didOpen: () => {
                    const popup = Swal.getPopup();
                    const sidebar = popup.querySelector('#swal-special-access-sidebar');
                    const totalBadge = popup.querySelector('#swal-special-access-total-badge');
                    let activePanelId = defaultPanelId;
                    let searching = false;

                    panels.forEach(p => updateSpecialAccessTabCount(popup, p.id));

                    function updateTotalBadge() {
                        if (!totalBadge) return;
                        const total = popup.querySelectorAll('.special-access-checkbox:checked').length;
                        totalBadge.textContent = String(total);
                        totalBadge.classList.toggle('badge-success', total > 0);
                        totalBadge.classList.toggle('badge-light', total === 0);
                    }

                    function showTab(panelId) {
                        activePanelId = panelId;
                        popup.querySelectorAll('.sae-tab').forEach(tab => {
                            tab.classList.toggle('active', tab.getAttribute('data-panel-target') === panelId);
                        });
                        popup.querySelectorAll('.sae-panel').forEach(panel => {
                            panel.classList.toggle('sae-panel-visible', panel.getAttribute('data-panel-id') === panelId);
                        });
                    }

                    if (sidebar) {
                        sidebar.querySelectorAll('.sae-tab').forEach(tab => {
                            tab.addEventListener('click', () => {
                                if (searching) return;
                                showTab(tab.getAttribute('data-panel-target'));
                            });
                        });
                    }

                    popup.querySelectorAll('.special-access-checkbox').forEach(checkbox => {
                        checkbox.addEventListener('change', () => {
                            const panel = checkbox.closest('.sae-panel');
                            if (panel) updateSpecialAccessTabCount(popup, panel.getAttribute('data-panel-id'));
                            updateTotalBadge();
                        });
                    });

                    wireReportAccessBlock(popup, 'swal-special-access', (mode, values) => {
                        reportAccessMode = mode;
                        reportAccessValues = values;
                    });

                    // Searching temporarily reveals every panel (ignoring the active tab) and
                    // hides only the non-matching items within them, so a setting buried in a
                    // category the user hasn't clicked into is still one keystroke away.
                    const searchInput = popup.querySelector('#swal-special-access-search');
                    const noResultsEl = popup.querySelector('#swal-special-access-no-results');
                    if (searchInput) {
                        searchInput.addEventListener('input', function() {
                            const term = this.value.trim().toLowerCase();
                            searching = term !== '';

                            if (!searching) {
                                if (noResultsEl) noResultsEl.style.display = 'none';
                                popup.querySelectorAll('.special-access-item').forEach(item => { item.style.display = ''; });
                                showTab(activePanelId);
                                return;
                            }

                            popup.querySelectorAll('.sae-tab').forEach(tab => tab.classList.remove('active'));
                            let anyVisible = false;
                            popup.querySelectorAll('.sae-panel').forEach(panel => {
                                if (panel.getAttribute('data-panel-id') === 'report-access') {
                                    panel.classList.remove('sae-panel-visible');
                                    return;
                                }
                                let panelHasMatch = false;
                                panel.querySelectorAll('.special-access-item').forEach(item => {
                                    const matches = (item.getAttribute('data-search-label') || '').includes(term);
                                    item.style.display = matches ? '' : 'none';
                                    if (matches) panelHasMatch = true;
                                });
                                panel.classList.toggle('sae-panel-visible', panelHasMatch);
                                if (panelHasMatch) anyVisible = true;
                            });
                            if (noResultsEl) noResultsEl.style.display = anyVisible ? 'none' : '';
                        });
                    }

                    const selectAllBtn = popup.querySelector('#swal-special-access-select-all');
                    if (selectAllBtn) {
                        selectAllBtn.addEventListener('click', () => {
                            popup.querySelectorAll('.sae-panel.sae-panel-visible .special-access-checkbox').forEach(el => {
                                if (el.closest('.special-access-item').style.display !== 'none') el.checked = true;
                            });
                            panels.forEach(p => updateSpecialAccessTabCount(popup, p.id));
                            updateTotalBadge();
                        });
                    }
                    const clearAllBtn = popup.querySelector('#swal-special-access-clear-all');
                    if (clearAllBtn) {
                        clearAllBtn.addEventListener('click', () => {
                            popup.querySelectorAll('.sae-panel.sae-panel-visible .special-access-checkbox').forEach(el => {
                                if (el.closest('.special-access-item').style.display !== 'none') el.checked = false;
                            });
                            panels.forEach(p => updateSpecialAccessTabCount(popup, p.id));
                            updateTotalBadge();
                        });
                    }
                },
                preConfirm: () => {
                    const popup = Swal.getPopup();
                    const abilities = Array.from(popup.querySelectorAll('.special-access-checkbox:checked')).map(el => el.value);
                    return { abilities, reportAccessApplicable, reportAccessMode, reportAccessValues };
                }
            }).then(async (result) => {
                if (!result.isConfirmed) return;

                const { abilities, reportAccessApplicable, reportAccessMode: finalMode, reportAccessValues: finalValues } = result.value || {};
                // Switched-off integration (D365 / Attendance): its keys were not shown - keep the ones this user already had
                if (!D365_ON || !ATTENDANCE_ON) {
                    abilities.push(...(specialAccessMap[targetEmpId] || []).filter(isHiddenFeatureKey));
                    if (Array.isArray(finalValues) && Object.prototype.hasOwnProperty.call(reportPermissionMap, targetEmpId)) {
                        finalValues.push(...(reportPermissionMap[targetEmpId] || []).filter(isHiddenFeatureKey));
                    }
                }

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

                Swal.fire({
                    title: '' + __('saving', 'Saving...') + '',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                try {
                    await saveSpecialAccessSettings();

                    await Swal.fire({
                        icon: 'success',
                        title: '' + __('updated', 'Updated') + '',
                        text: '' + __('your_settings_have_been_updated_successfully') + '',
                        confirmButtonText: '' + __('ok', 'OK') + ''
                    });
                    await refreshSpecialAccessTableWithLoader();
                } catch (error) {
                    Swal.fire('' + __('error') + '', error.message, 'error');
                }
            });
        }

        function renderRequestTypeBlocksSettings() {
            const meta = groupMeta('request_type_blocks');
            settingsContainer.innerHTML = `
                <div class="tab-pane active" id="group-request_type_blocks" role="tabpanel">
                    ${srTabHead(meta.icon, __('manage_request_type_blocks', 'Manage Request Type Blocks'), '')}
                    <div class="sr-notice tone-sky as-notice-top"><i class="mdi mdi-information-outline"></i><div>
                        ${__('manage_request_type_blocks_hint', 'Blocking a request type here disables it for every employee at once. To exempt a specific employee from a global block (or to block just one employee for a type that isn\'t globally blocked), use the "Block Specific Request Types" section on that employee\'s Edit Employee page.')}
                    </div></div>
                    <div class="sr-card as-section">
                        <div class="sr-card-head">
                            <div class="sr-card-title"><i class="mdi mdi-block-helper"></i> ${__('request_types', 'Request types')}</div>
                            <span class="sr-pill tone-slate" id="requestTypeBlockCount"><span class="sr-dot"></span>-</span>
                        </div>
                        <div id="requestTypeBlockList" class="as-section-body">
                            <div class="ac-loading"><span class="spinner-border spinner-border-sm" role="status"></span> ${__('loading')}</div>
                        </div>
                        <div class="as-section-foot">
                            <button type="button" id="saveRequestTypeBlocksBtn" class="sr-btn sr-btn-primary sr-btn-sm">
                                <i class="mdi mdi-content-save"></i> ${__('save_changes')}
                            </button>
                        </div>
                    </div>
                </div>
            `;

            const listContainer = document.getElementById('requestTypeBlockList');
            const saveBtn = document.getElementById('saveRequestTypeBlocksBtn');

            function updateCount() {
                const n = listContainer.querySelectorAll('.request-type-block-checkbox:checked').length;
                const pill = document.getElementById('requestTypeBlockCount');
                pill.className = `sr-pill tone-${n ? 'red' : 'green'}`;
                pill.innerHTML = `<span class="sr-dot"></span>${n ? `${n} ${__('blocked', 'Blocked')}` : __('none_blocked', 'None blocked')}`;
            }

            function renderCheckboxes(blockedTypes) {
                const blockedSet = new Set(blockedTypes || []);
                let html = '<div class="as-block-grid">';
                Object.keys(requestTypeBlockLabels).forEach((key) => {
                    const checked = blockedSet.has(key);
                    html += `
                        <label class="as-block-tile${checked ? ' is-blocked' : ''}" for="blockType_${key}">
                            <span class="as-block-name">${escapeHtml(requestTypeBlockLabels[key])}</span>
                            <span class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input request-type-block-checkbox" id="blockType_${key}" value="${key}" ${checked ? 'checked' : ''}>
                                <span class="custom-control-label"></span>
                            </span>
                        </label>
                    `;
                });
                html += '</div>';
                listContainer.innerHTML = html;
                listContainer.querySelectorAll('.request-type-block-checkbox').forEach(cb => {
                    cb.addEventListener('change', () => {
                        cb.closest('.as-block-tile').classList.toggle('is-blocked', cb.checked);
                        updateCount();
                    });
                });
                updateCount();
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
                        throw new Error(data.message || '' + __('failed_to_load_current_blocks', 'Failed to load current blocks.') + '');
                    }
                    renderCheckboxes(data.blocked_types);
                })
                .catch((error) => {
                    listContainer.innerHTML = `<div class="sr-notice tone-red ac-error"><i class="mdi mdi-alert-circle-outline"></i><div>${escapeHtml(error.message)}</div></div>`;
                });
            }

            saveBtn.addEventListener('click', () => {
                const checked = Array.from(document.querySelectorAll('.request-type-block-checkbox:checked')).map((el) => el.value);
                saveBtn.disabled = true;

                fetch('./includes/ajaxFile/globalRequestBlockHandler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ action: 'update_global_blocked_types', blocked_types: JSON.stringify(checked) })
                })
                .then((response) => response.json())
                .then((data) => {
                    if (!data.success) {
                        throw new Error(data.message || '' + __('failed_to_save', 'Failed to save.') + '');
                    }
                    acToast('success', __('settings_updated_successfully', 'Settings updated successfully.'));
                    renderCheckboxes(data.blocked_types);
                })
                .catch((error) => {
                    Swal.fire('' + __('Error!') + '', error.message, 'error');
                })
                .finally(() => { saveBtn.disabled = false; });
            });

            loadBlockedTypes();
        }

        // --- Vacation Blackout Dates tab ---
        // Date ranges during which no employee can apply for a vacation; enforced
        // server-side in leaveHandler.php 'applyVacation'. See vacationBlackoutHandler.php.
        function renderVacationBlackoutSettings() {
            const meta = groupMeta('vacation_blackout_dates');
            settingsContainer.innerHTML = `
                <div class="tab-pane active" id="group-vacation_blackout_dates" role="tabpanel">
                    ${srTabHead(meta.icon, __('vacation_blackout_dates', 'Vacation Blackout Dates'),
                        __('vacation_blackout_hint', 'No employee can submit a vacation request that overlaps a blocked date range below. Encashed vacations (no time off) are not affected. Already-submitted requests are not changed.'),
                        `<button type="button" id="vbd-add-btn" class="sr-btn sr-btn-success sr-btn-sm"><i class="mdi mdi-plus"></i> ${__('block_vacation_dates', 'Block Vacation Dates')}</button>`)}
                    <div class="sr-card aca-card">
                        <div id="vbd-list-container">
                            <div class="ac-loading"><span class="spinner-border spinner-border-sm" role="status"></span> ${__('loading')}</div>
                        </div>
                    </div>
                </div>
            `;

            const listContainer = document.getElementById('vbd-list-container');
            const endpoint = './includes/ajaxFile/vacationBlackoutHandler.php';

            function post(params) {
                return fetch(endpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams(params)
                }).then(r => r.json());
            }

            function dayCount(start, end) {
                const ms = new Date(end + 'T00:00:00') - new Date(start + 'T00:00:00');
                return isNaN(ms) ? '' : Math.round(ms / 86400000) + 1;
            }

            function loadList() {
                post({ ajaxType: 'listVacationBlackouts' })
                .then(data => {
                    if (data.status !== 'success') {
                        throw new Error(data.message || __('failed_to_load', 'Failed to load.'));
                    }
                    const rows = data.results || [];
                    if (rows.length === 0) {
                        listContainer.innerHTML = `<div class="sr-empty"><i class="mdi mdi-calendar-blank"></i>${__('no_vacation_blackouts', 'No blocked vacation dates. Employees can apply for any dates.')}</div>`;
                        return;
                    }
                    const today = new Date().toISOString().slice(0, 10);
                    let html = `<div class="sr-table-wrap aca-table-wrap"><table class="sr-table aca-table org-table">
                        <thead><tr>
                            <th>${__('blocked_period', 'Blocked Period (start - end)')}</th>
                            <th>${__('reason', 'Reason')}</th>
                            <th>${__('status', 'Status')}</th>
                            <th>${__('added_by', 'Added By')}</th>
                            <th class="aca-actions">${__('actions', 'Actions')}</th>
                        </tr></thead><tbody>`;
                    rows.forEach(row => {
                        const past = row.end_date < today;
                        const current = !past && row.start_date <= today;
                        const days = dayCount(row.start_date, row.end_date);
                        const status = past
                            ? `<span class="sr-pill tone-slate"><span class="sr-dot"></span>${__('past', 'Past')}</span>`
                            : current
                                ? `<span class="sr-pill tone-red"><span class="sr-dot"></span>${__('blocked_now', 'Blocked now')}</span>`
                                : `<span class="sr-pill tone-amber"><span class="sr-dot"></span>${__('upcoming', 'Upcoming')}</span>`;
                        html += `<tr${past ? ' class="is-inactive"' : ''}>
                            <td>
                                <div class="org-name">
                                    <span class="ac-ico"><i class="mdi mdi-calendar-remove"></i></span>
                                    <div class="org-name-text">
                                        <span class="sr-cell-title sr-mono">${escapeHtml(row.start_date)} &rarr; ${escapeHtml(row.end_date)}</span>
                                        ${days ? `<span class="sr-cell-sub">${days} ${__('day_s', 'day(s)')}</span>` : ''}
                                    </div>
                                </div>
                            </td>
                            <td>${row.reason ? escapeHtml(row.reason) : '<span class="ac-muted">-</span>'}</td>
                            <td>${status}</td>
                            <td>${escapeHtml(row.created_by_name || row.created_by_emp_id || '-')}</td>
                            <td class="aca-actions"><button type="button" class="sr-btn sr-btn-ghost sr-btn-sm sr-btn-icon ac-remove vbd-remove-btn" data-id="${escapeHtml(row.id)}" data-period="${escapeHtml(row.start_date + ' → ' + row.end_date)}" title="${__('remove', 'Remove')}"><i class="mdi mdi-delete"></i></button></td>
                        </tr>`;
                    });
                    html += '</tbody></table></div>';
                    listContainer.innerHTML = html;
                    listContainer.querySelectorAll('.vbd-remove-btn').forEach(btn => {
                        btn.addEventListener('click', () => removeBlackout(btn.dataset.id, btn.dataset.period));
                    });
                })
                .catch(err => {
                    listContainer.innerHTML = `<div class="as-section-body"><div class="sr-notice tone-red ac-error"><i class="mdi mdi-alert-circle-outline"></i><div>${escapeHtml(err.message)}</div></div></div>`;
                });
            }

            function removeBlackout(id, period) {
                Swal.fire({
                    title: '' + __('are_you_sure', 'Are you sure?') + '',
                    html: `<div class="sr-form"><div class="sr-notice tone-amber is-compact"><i class="mdi mdi-alert-outline"></i>
                            <div><strong class="sr-mono">${escapeHtml(period || '')}</strong><br>${__('remove_blackout_confirm', 'Employees will be able to apply for vacations on these dates again.')}</div></div></div>`,
                    icon: 'warning',
                    width: '480px',
                    allowOutsideClick: false,
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    confirmButtonText: `<i class="mdi mdi-delete"></i> ${__('yes_remove_it', 'Yes, remove it')}`,
                    cancelButtonText: '' + __('cancel') + '',
                    showLoaderOnConfirm: true,
                    preConfirm: () => post({ ajaxType: 'removeVacationBlackout', id: id })
                        .then(data => {
                            if (data.type !== 'success') throw new Error(data.message || __('failed_to_remove', 'Failed to remove.'));
                            return data;
                        })
                        .catch(err => { Swal.showValidationMessage(err.message); return false; })
                }).then(result => {
                    if (!result.isConfirmed || !result.value) return;
                    acToast('success', result.value.message || __('success'));
                    loadList();
                });
            }

            function openAddModal() {
                Swal.fire({
                    title: '' + __('block_vacation_dates', 'Block Vacation Dates') + '',
                    html: `
                        <form class="sr-form" onsubmit="return false">
                            <div class="sr-fsec">
                                <div class="sr-fsec-head"><span><i class="mdi mdi-calendar-remove"></i> ${__('blocked_period', 'Blocked Period (start - end)')}</span><span class="sr-chip" id="vbd-duration">-</span></div>
                                <div class="sr-fgrid">
                                    <div class="sr-fcol c-12">
                                        <label for="vbd-daterange">${__('blocked_period', 'Blocked Period (start - end)')} <span class="text-danger">*</span></label>
                                        <input type="text" id="vbd-daterange" class="form-control" readonly>
                                    </div>
                                    <div class="sr-fcol c-12">
                                        <label for="vbd-reason">${__('reason', 'Reason')} <small class="text-muted">(${__('optional', 'optional')})</small></label>
                                        <input type="text" id="vbd-reason" class="form-control" maxlength="255" autocomplete="off" placeholder="${__('vacation_blackout_reason_placeholder', 'e.g. Peak season, company event')}">
                                    </div>
                                </div>
                            </div>
                        </form>
                    `,
                    width: 520,
                    showCancelButton: true,
                    confirmButtonText: `<i class="mdi mdi-block-helper"></i> ${__('block', 'Block')}`,
                    cancelButtonText: '' + __('cancel') + '',
                    allowOutsideClick: false,
                    showLoaderOnConfirm: true,
                    didOpen: () => {
                        if (window.AppDate && typeof moment !== 'undefined') {
                            const $duration = $('#vbd-duration');
                            const updateDuration = (s, e) => {
                                const days = moment(e).add(1, 'day').diff(moment(s), 'days');
                                $duration.text(`${__('duration', 'Duration')}: ${days} ${__('day_s', 'day(s)')}`);
                            };
                            // Past days are blocked
                            AppDate.range('#vbd-daterange', {
                                minDate: 'today',
                                defaultDate: ['today', 'today'],
                                onChange: (dates) => { if (dates.length === 2) updateDuration(dates[0], dates[1]); }
                            });
                            updateDuration(new Date(), new Date());
                        }
                    },
                    preConfirm: () => {
                        const [startDate, endDate] = window.AppDate ? AppDate.rangeValues('#vbd-daterange') : ['', ''];
                        if (!startDate || !endDate) {
                            document.getElementById('vbd-daterange').classList.add('is-invalid');
                            Swal.showValidationMessage('' + __('select_blocked_period', 'Please select a period.') + '');
                            return false;
                        }
                        return post({ ajaxType: 'addVacationBlackout', start_date: startDate, end_date: endDate, reason: $('#vbd-reason').val().trim() })
                            .then(data => {
                                if (data.type !== 'success') throw new Error(data.message || __('failed_to_save', 'Failed to save.'));
                                return data;
                            })
                            .catch(err => { Swal.showValidationMessage(err.message); return false; });
                    }
                }).then(result => {
                    if (!result.isConfirmed || !result.value) return;
                    acToast('success', result.value.message || __('success'));
                    loadList();
                });
            }

            document.getElementById('vbd-add-btn').addEventListener('click', openAddModal);
            loadList();
        }

        // --- Temporary Role Transfer tab ---
        // HR-initiated, vacation-independent version of the "Transfer Role (Temp)"
        // mechanism on view_employee.php: HR picks a direct supervisor (or any
        // role-holder) going on leave, a replacement employee, and a date window.
        // Backed by the same emp_temp_role_assignments table / session_check.php
        // override as the vacation-based flow - see includes/ajaxFile/tempRoleHandler.php.
        function renderTempRoleTransferSettings() {
            const meta = groupMeta('temp_role_transfer');
            settingsContainer.innerHTML = `
                <div class="tab-pane active" id="group-temp_role_transfer" role="tabpanel">
                    ${srTabHead(meta.icon, __('temp_role_transfer', 'Temporary Role Transfer'),
                        __('temp_role_transfer_hint', 'Temporarily hand a supervisor/manager\'s role to a replacement employee for a fixed period (e.g. while they are on vacation). Access reverts to the original employee automatically once the end date passes. The HR user who grants this will get an email reminder one day before it expires.'),
                        `<button type="button" id="trt-add-btn" class="sr-btn sr-btn-success sr-btn-sm"><i class="mdi mdi-plus"></i> ${__('grant_new_temp_role', 'Grant New Temporary Role')}</button>`)}
                    <div class="sr-card aca-card">
                        <div class="sr-toolbar">
                            <div class="sr-search">
                                <i class="mdi mdi-magnify"></i>
                                <input type="search" id="trt-search" placeholder="${__('search')}..." autocomplete="off" aria-label="${__('search')}">
                            </div>
                            <div class="as-seg" id="trt-status-filter">
                                <button type="button" class="as-seg-btn is-on" data-status="">${__('all', 'All')}</button>
                                <button type="button" class="as-seg-btn" data-status="active">${__('active', 'Active')}</button>
                                <button type="button" class="as-seg-btn" data-status="expired">${__('expired', 'Expired')}</button>
                                <button type="button" class="as-seg-btn" data-status="revoked">${__('revoked', 'Revoked')}</button>
                            </div>
                        </div>
                        <div id="trt-list-container">
                            <div class="ac-loading"><span class="spinner-border spinner-border-sm" role="status"></span> ${__('loading')}</div>
                        </div>
                    </div>
                </div>
            `;

            const listContainer = document.getElementById('trt-list-container');
            const searchInput = document.getElementById('trt-search');
            let statusFilter = '';

            function statusPill(status) {
                const tone = { active: 'green', expired: 'slate', revoked: 'red' }[status] || 'slate';
                return `<span class="sr-pill tone-${tone}"><span class="sr-dot"></span>${escapeHtml(__(status, status))}</span>`;
            }
            function personCell(name) {
                const initials = String(name || '?').trim().split(/\s+/).slice(0, 2).map(w => w.charAt(0)).join('').toUpperCase();
                return `<div class="org-name"><span class="sr-avatar sr-avatar-sm">${escapeHtml(initials)}</span><span class="sr-cell-title">${escapeHtml(name)}</span></div>`;
            }

            function applyFilter() {
                const q = searchInput.value.trim().toLowerCase();
                let shown = 0;
                listContainer.querySelectorAll('tbody tr').forEach(tr => {
                    const hit = (!q || tr.dataset.search.includes(q)) && (!statusFilter || tr.dataset.status === statusFilter);
                    tr.style.display = hit ? '' : 'none';
                    if (hit) shown++;
                });
                const noRes = listContainer.querySelector('.org-no-results');
                const wrap = listContainer.querySelector('.aca-table-wrap');
                if (noRes) noRes.style.display = shown ? 'none' : '';
                if (wrap) wrap.style.display = shown ? '' : 'none';
            }
            searchInput.addEventListener('keydown', e => { if (e.key === 'Enter') e.preventDefault(); });
            searchInput.addEventListener('input', applyFilter);
            document.querySelectorAll('#trt-status-filter .as-seg-btn').forEach(btn => btn.addEventListener('click', () => {
                document.querySelectorAll('#trt-status-filter .as-seg-btn').forEach(b => b.classList.toggle('is-on', b === btn));
                statusFilter = btn.dataset.status;
                applyFilter();
            }));

            function loadList() {
                fetch('./includes/ajaxFile/tempRoleHandler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ ajaxType: 'listTempRoleAssignments' })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.status !== 'success') {
                        throw new Error(data.message || __('failed_to_load', 'Failed to load.'));
                    }
                    const rows = data.results || [];
                    if (rows.length === 0) {
                        listContainer.innerHTML = `<div class="sr-empty"><i class="mdi mdi-account-switch"></i>${__('no_temp_role_transfers_found', 'No temporary role transfers found.')}</div>`;
                        return;
                    }
                    let html = `<div class="sr-table-wrap aca-table-wrap"><table class="sr-table aca-table org-table">
                        <thead><tr>
                            <th>${__('original_role_holder', 'Original Role Holder')}</th>
                            <th>${__('covered_by', 'Covered By')}</th>
                            <th>${__('role_transferred', 'Role')}</th>
                            <th>${__('coverage_period', 'Coverage Period (start - end)')}</th>
                            <th>${__('status', 'Status')}</th>
                            <th>${__('granted_by', 'Granted By')}</th>
                            <th class="aca-actions">${__('actions', 'Actions')}</th>
                        </tr></thead><tbody>`;
                    rows.forEach(row => {
                        const holder = row.employee_name || row.employee_emp_id;
                        const cover = row.replacement_name || row.replacement_emp_id;
                        const search = [holder, cover, formatRoleLabel(row.granted_role), row.valid_from, row.valid_to, row.granted_by_name].join(' ').toLowerCase();
                        html += `<tr data-status="${escapeHtml(row.status)}" data-search="${escapeHtml(search)}"${row.status === 'active' ? '' : ' class="is-inactive"'}>
                            <td>${personCell(holder)}</td>
                            <td>${personCell(cover)}</td>
                            <td><span class="sr-chip"><i class="mdi mdi-account-key"></i> ${escapeHtml(formatRoleLabel(row.granted_role))}</span></td>
                            <td><span class="sr-mono">${escapeHtml(row.valid_from)} &rarr; ${escapeHtml(row.valid_to)}</span></td>
                            <td>${statusPill(row.status)}</td>
                            <td>${escapeHtml(row.granted_by_name || row.granted_by_emp_id || '-')}</td>
                            <td class="aca-actions">${row.status === 'active' ? `<button type="button" class="sr-btn sr-btn-sm trt-revoke-btn" data-id="${escapeHtml(row.id)}" data-who="${escapeHtml(holder + ' → ' + cover)}"><i class="mdi mdi-close-circle"></i> ${__('revoke', 'Revoke')}</button>` : ''}</td>
                        </tr>`;
                    });
                    html += `</tbody></table></div>
                        <div class="sr-empty org-no-results" style="display:none"><i class="mdi mdi-magnify"></i>${__('no_results_found', 'No results found')}</div>`;
                    listContainer.innerHTML = html;

                    listContainer.querySelectorAll('.trt-revoke-btn').forEach(btn => {
                        btn.addEventListener('click', () => revokeAssignment(btn.dataset.id, btn.dataset.who));
                    });
                    applyFilter();
                })
                .catch(err => {
                    listContainer.innerHTML = `<div class="as-section-body"><div class="sr-notice tone-red ac-error"><i class="mdi mdi-alert-circle-outline"></i><div>${escapeHtml(err.message)}</div></div></div>`;
                });
            }

            function revokeAssignment(id, who) {
                Swal.fire({
                    title: '' + __('are_you_sure', 'Are you sure?') + '',
                    html: `<div class="sr-form"><div class="sr-notice tone-amber is-compact"><i class="mdi mdi-alert-outline"></i>
                            <div>${who ? `<strong>${escapeHtml(who)}</strong><br>` : ''}${__('revoke_temp_role_confirm', 'This will immediately return the role to the original employee.')}</div></div></div>`,
                    icon: 'warning',
                    width: '480px',
                    allowOutsideClick: false,
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    confirmButtonText: `<i class="mdi mdi-close-circle"></i> ${__('yes_revoke_it', 'Yes, revoke it')}`,
                    cancelButtonText: '' + __('cancel') + '',
                    showLoaderOnConfirm: true,
                    preConfirm: () => fetch('./includes/ajaxFile/tempRoleHandler.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: new URLSearchParams({ ajaxType: 'revokeManualTempRole', id: id })
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data.type !== 'success') throw new Error(data.message || __('failed_to_revoke', 'Failed to revoke.'));
                            return data;
                        })
                        .catch(err => { Swal.showValidationMessage(err.message); return false; })
                }).then(result => {
                    if (!result.isConfirmed || !result.value) return;
                    acToast('success', result.value.message || __('success'));
                    loadList();
                });
            }

            function openGrantModal() {
                Swal.fire({
                    title: '' + __('grant_new_temp_role', 'Grant New Temporary Role') + '',
                    html: `
                        <form class="sr-form" onsubmit="return false">
                            <div class="sr-fsec">
                                <div class="sr-fsec-head"><span><i class="mdi mdi-account-switch"></i> ${__('temp_role_transfer', 'Temporary Role Transfer')}</span></div>
                                <div class="sr-fgrid">
                                    <div class="sr-fcol c-12">
                                        <label for="trt-employee">${__('employee_on_leave', 'Employee Going on Leave (role to hand over)')} <span class="text-danger">*</span></label>
                                        <select id="trt-employee" class="form-control" style="width:100%;"></select>
                                        <div id="trt-employee-role" class="as-role-hint"></div>
                                    </div>
                                    <div class="sr-fcol c-12">
                                        <label for="trt-replacement">${__('replacement_employee', 'Replacement Employee')} <span class="text-danger">*</span></label>
                                        <select id="trt-replacement" class="form-control" style="width:100%;"></select>
                                    </div>
                                </div>
                            </div>
                            <div class="sr-fsec">
                                <div class="sr-fsec-head"><span><i class="mdi mdi-calendar-range"></i> ${__('coverage_period', 'Coverage Period (start - end)')}</span><span class="sr-chip" id="trt-duration">-</span></div>
                                <div class="sr-fgrid">
                                    <div class="sr-fcol c-12">
                                        <input type="text" id="trt-daterange" class="form-control" readonly aria-label="${__('coverage_period', 'Coverage Period (start - end)')}">
                                    </div>
                                </div>
                            </div>
                        </form>
                    `,
                    width: 600,
                    showCancelButton: true,
                    confirmButtonText: `<i class="mdi mdi-check"></i> ${__('grant', 'Grant')}`,
                    cancelButtonText: '' + __('cancel') + '',
                    allowOutsideClick: false,
                    showLoaderOnConfirm: true,
                    didOpen: () => {
                        const $popup = $(Swal.getPopup());
                        const empSelectOpts = {
                            dropdownParent: $popup,
                            placeholder: '' + __('search_employees', 'Search employees...') + '',
                            width: '100%',
                            ajax: {
                                url: './includes/ajaxFile/timetableAjax.php',
                                type: 'POST',
                                dataType: 'json',
                                delay: 250,
                                data: params => ({ action: 'search_employees', search: params.term }),
                                processResults: response => ({ results: response.status === 'success' ? response.results : [] })
                            }
                        };
                        $('#trt-employee').select2(empSelectOpts);
                        $('#trt-replacement').select2(empSelectOpts);

                        const $roleHint = $('#trt-employee-role');
                        const setHint = (tone, text) => {
                            $roleHint.html(text ? `<span class="sr-pill tone-${tone}"><span class="sr-dot"></span>${escapeHtml(text)}</span>` : '');
                        };
                        $('#trt-employee').on('select2:select', function() {
                            const empId = $(this).val();
                            setHint('slate', __('loading') + '...');
                            fetch('./includes/ajaxFile/tempRoleHandler.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: new URLSearchParams({ ajaxType: 'getEmployeeCurrentRole', emp_id: empId })
                            })
                            .then(r => r.json())
                            .then(data => {
                                if (data.status !== 'success') {
                                    setHint('red', data.message || __('failed_to_load', 'Failed to load.'));
                                    return;
                                }
                                const noRole = !data.role || data.role.toLowerCase() === 'employee';
                                setHint(noRole ? 'red' : 'green', `${__('current_role', 'Current Role')}: ${data.role_label}` + (noRole ? ` (${__('no_role_to_hand_over', 'no elevated role to hand over')})` : ''));
                            })
                            .catch(() => setHint('red', __('failed_to_load', 'Failed to load.')));
                        });
                        $('#trt-employee').on('select2:clear', () => setHint('', ''));

                        if (window.AppDate && typeof moment !== 'undefined') {
                            const start = moment();
                            const end = moment().add(1, 'days');
                            const $duration = $('#trt-duration');

                            const updateDuration = (s, e) => {
                                const inclusiveEnd = e.clone().add(1, 'day');
                                const months = inclusiveEnd.diff(s, 'months');
                                const remainderStart = s.clone().add(months, 'months');
                                const days = inclusiveEnd.diff(remainderStart, 'days');
                                const parts = [];
                                if (months > 0) parts.push(`${months} ${__('month_s', 'month(s)')}`);
                                if (days > 0 || months === 0) parts.push(`${days} ${__('day_s', 'day(s)')}`);
                                $duration.text(`${__('duration', 'Duration')}: ${parts.join(', ')}`);
                            };

                            // Past days are blocked
                            AppDate.range('#trt-daterange', {
                                minDate: 'today',
                                defaultDate: [start.toDate(), end.toDate()],
                                onChange: (dates) => { if (dates.length === 2) updateDuration(moment(dates[0]), moment(dates[1])); }
                            });
                            updateDuration(start, end);
                        }
                    },
                    preConfirm: () => {
                        const employee_emp_id = $('#trt-employee').val();
                        const replacement_emp_id = $('#trt-replacement').val();
                        const [validFrom, validTo] = window.AppDate ? AppDate.rangeValues('#trt-daterange') : ['', ''];
                        if (!employee_emp_id || !replacement_emp_id) {
                            Swal.showValidationMessage('' + __('select_both_employees', 'Please select both employees.') + '');
                            return false;
                        }
                        if (employee_emp_id === replacement_emp_id) {
                            Swal.showValidationMessage('' + __('replacement_cannot_match', 'Replacement cannot be the same employee.') + '');
                            return false;
                        }
                        if (!validFrom || !validTo) {
                            Swal.showValidationMessage('' + __('select_coverage_period', 'Please select a coverage period.') + '');
                            return false;
                        }
                        return fetch('./includes/ajaxFile/tempRoleHandler.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: new URLSearchParams({ ajaxType: 'grantManualTempRole', employee_emp_id, replacement_emp_id, valid_from: validFrom, valid_to: validTo })
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data.type !== 'success') throw new Error(data.message || __('failed_to_grant', 'Failed to grant.'));
                            return data;
                        })
                        .catch(err => { Swal.showValidationMessage(err.message); return false; });
                    }
                }).then(result => {
                    if (!result.isConfirmed || !result.value) return;
                    acToast('success', result.value.message || __('success'));
                    loadList();
                });
            }

            document.getElementById('trt-add-btn').addEventListener('click', openGrantModal);
            loadList();
        }

        // --- Integrations tab (system admins) ---
        // One switch per module / external system; saves straight away and reloads, because menus,
        // tabs and permissions across the app follow the switch.
        function renderIntegrationsSettings() {
            const msLogo = `<span class="as-ms-logo" aria-hidden="true"><i style="background:#f25022"></i><i style="background:#7fba00"></i><i style="background:#00a4ef"></i><i style="background:#ffb900"></i></span>`;
            const integrations = [
                {
                    setting: 'd365_enabled', on: D365_ON, icon: msLogo,
                    name: 'Microsoft Dynamics 365',
                    label: __('d365_enable_label', 'Enable Microsoft Dynamics 365'),
                    onHint: __('d365_enabled_hint', 'On - D365 tabs, status, sync buttons, reports and pages are shown to the people allowed to see them.'),
                    offHint: __('d365_disabled_hint', 'Off - every D365 tab, status, sync button, report, menu link and page is hidden, and nothing is sent to D365. Settings and permissions are kept.'),
                    confirmOn: __('d365_enable_confirm_text', 'D365 tabs, status, sync buttons, reports and pages come back for the people allowed to see them.'),
                    confirmOff: __('d365_disable_confirm_text', 'All D365 tabs, status, sync buttons, reports, menu links and pages are hidden for everyone and nothing is sent to D365. Settings and permissions are kept.'),
                },
                {
                    setting: 'attendance_enabled', on: ATTENDANCE_ON,
                    icon: '<span class="ac-ico as-int-ico"><i class="mdi mdi-fingerprint"></i></span>',
                    name: __('attendance', 'Attendance'),
                    label: __('attendance_enable_label', 'Enable Attendance'),
                    onHint: __('attendance_enabled_hint', 'On - attendance records, biometric devices, timetables, the employee Attendance tab and the attendance report are shown to the people allowed to see them.'),
                    offHint: __('attendance_disabled_hint', 'Off - attendance pages, menu links, the employee Attendance tab, the attendance report and Attendance Config are hidden; devices keep their punches until it is switched back on; payroll skips automatic attendance deductions / overtime. Settings and permissions are kept.'),
                    confirmOn: __('attendance_enable_confirm_text', 'Attendance pages, devices, the Attendance tab and the report come back. Devices then send the punches they kept while it was off.'),
                    confirmOff: __('attendance_disable_confirm_text', 'Attendance pages, menu links, the employee Attendance tab, the attendance report and Attendance Config are hidden for everyone. Devices stop being accepted (they keep their punches), and payroll skips automatic attendance deductions / overtime. Settings and permissions are kept.'),
                },
            ];
            const meta = groupMeta('integrations');
            settingsContainer.innerHTML = `
                <div class="tab-pane active" id="group-integrations" role="tabpanel">
                    ${srTabHead(meta.icon, __('integrations', 'Integrations'), __('integrations_hint', 'Turn modules and connections to external systems on or off for the whole app.'))}
                    <div class="as-int-grid">
                    ${integrations.map(it => `
                        <div class="sr-card as-int-card${it.on ? ' is-on' : ''}">
                            <div class="as-int-top">
                                ${it.icon}
                                <div class="as-int-title">
                                    <b>${escapeHtml(it.name)}</b>
                                    <span class="sr-pill tone-${it.on ? 'green' : 'slate'}"><span class="sr-dot"></span>${it.on ? __('enabled', 'Enabled') : __('disabled', 'Disabled')}</span>
                                </div>
                            </div>
                            <p class="as-int-hint">${escapeHtml(it.on ? it.onHint : it.offHint)}</p>
                            <div class="as-int-foot">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input integration-toggle" id="integration-${it.setting}" data-setting="${it.setting}" ${it.on ? 'checked' : ''}>
                                    <label class="custom-control-label" for="integration-${it.setting}">${escapeHtml(it.label)}</label>
                                </div>
                            </div>
                        </div>`).join('')}
                    </div>
                </div>
            `;
            settingsContainer.querySelectorAll('.integration-toggle').forEach(toggle => toggle.addEventListener('change', async function () {
                const it = integrations.find(x => x.setting === toggle.dataset.setting);
                const on = toggle.checked;
                const answer = await Swal.fire({
                    icon: on ? 'question' : 'warning',
                    title: (on ? __('turn_on_q', 'Turn on') : __('turn_off_q', 'Turn off')) + ' ' + it.name + '?',
                    text: on ? it.confirmOn : it.confirmOff,
                    allowOutsideClick: false,
                    showCancelButton: true,
                    confirmButtonColor: on ? undefined : '#dc2626',
                    confirmButtonText: on ? __('turn_on', 'Turn on') : __('turn_off', 'Turn off'),
                    cancelButtonText: __('cancel', 'Cancel')
                });
                if (!answer.isConfirmed) { toggle.checked = !on; return; }
                try {
                    const res = await fetch('./includes/settings_handler.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({ action: 'update_settings', [it.setting]: on ? '1' : '0' })
                    });
                    const data = await res.json();
                    if (!data.success) throw new Error(data.message || __('error', 'Error'));
                    location.reload(); // menus, tabs and permissions all follow the switch
                } catch (e) {
                    toggle.checked = !on;
                    Swal.fire(__('error', 'Error'), e.message, 'error');
                }
            }));
        }

        async function renderLicenseSettings() {
            const licenseSettings = groupedSettings['license'] || [];
            const getVal = (name) => {
                const row = licenseSettings.find(s => s.setting_name === name);
                return row ? row.setting_value : '';
            };

            const apiUrl = getVal('license_api_url');
            const serialKey = getVal('license_serial_key');
            const token = getVal('license_token');
            const maskedKey = serialKey.length > 8 ? (serialKey.slice(0, 4) + '...' + serialKey.slice(-4)) : serialKey;
            const maskedToken = token.length > 8 ? (token.slice(0, 4) + '...' + token.slice(-4)) : token;
            const cacheValid = getVal('license_cache_valid') === '1';
            const cacheStatus = getVal('license_cache_status') || 'not_configured';
            const cacheMessage = getVal('license_cache_message') || __('license_not_configured', 'No license key configured yet.');
            const cacheExpiresAt = getVal('license_cache_expires_at');
            const lastVerifiedAt = getVal('license_last_verified_at');
            const meta = groupMeta('license');

            settingsContainer.innerHTML = `
                <div class="tab-pane active" id="group-license" role="tabpanel">
                    ${srTabHead(meta.icon, __('license', 'License'), __('license_sub', 'Product license key and verification status.'))}

                    <div class="as-license-status ${cacheValid ? 'is-valid' : 'is-invalid'}" id="license-status-banner">
                        <span class="as-license-ico"><i class="mdi ${cacheValid ? 'mdi-check-circle' : 'mdi-close-circle'}"></i></span>
                        <div class="as-license-text">
                            <div class="as-license-title">${cacheValid ? __('license_active', 'License Active') : __('license_not_active', 'License Not Active')} <span class="sr-chip sr-mono">${escapeHtml(cacheStatus)}</span></div>
                            <div class="as-license-msg">${escapeHtml(cacheMessage)}</div>
                        </div>
                        <div class="as-license-dates">
                            ${cacheExpiresAt ? `<div><span>${__('expires', 'Expires')}</span><b class="sr-mono">${escapeHtml(cacheExpiresAt)}</b></div>` : ''}
                            ${lastVerifiedAt ? `<div><span>${__('last_verified', 'Last verified')}</span><b class="sr-mono">${escapeHtml(lastVerifiedAt)}</b></div>` : ''}
                        </div>
                    </div>

                    <div class="sr-card as-section">
                        <div class="sr-card-head"><div class="sr-card-title"><i class="mdi mdi-key"></i> ${__('license_key', 'License Key')}</div></div>
                        <div class="as-fields">
                            <div class="as-field">
                                <div class="as-field-label">${__('current_key', 'Current Key')}</div>
                                <div class="as-field-control"><input type="text" class="form-control sr-mono" value="${escapeHtml(maskedKey)}" readonly disabled></div>
                            </div>
                            <div class="as-field">
                                <div class="as-field-label">${__('current_token', 'Current Token')}</div>
                                <div class="as-field-control"><input type="text" class="form-control sr-mono" value="${escapeHtml(maskedToken)}" readonly disabled></div>
                            </div>
                            <div class="as-field">
                                <label class="as-field-label" for="license-serial-key-input">${__('new_serial_key', 'New Serial Key')}</label>
                                <div class="as-field-control"><input type="text" id="license-serial-key-input" class="form-control sr-mono" value="" autocomplete="off" placeholder="${__('license_key_keep_hint', 'Leave blank to keep the current key - only fill in to replace it')}"></div>
                            </div>
                            <div class="as-field">
                                <label class="as-field-label" for="license-verify-url-input">${__('new_verify_url', 'New Verify URL')}</label>
                                <div class="as-field-control"><input type="text" id="license-verify-url-input" class="form-control sr-mono" value="" autocomplete="off" placeholder="${__('license_url_keep_hint', 'Leave blank to keep current - paste the full Verify URL from the license admin panel to replace it')}"></div>
                            </div>
                        </div>
                        <div class="as-section-foot">
                            <span id="license-verify-spinner" style="display:none;"><span class="spinner-border spinner-border-sm" role="status"></span></span>
                            <button type="button" class="sr-btn sr-btn-primary sr-btn-sm" id="license-save-verify-btn">
                                <i class="mdi mdi-check-circle"></i> ${__('save_and_verify', 'Save & Verify')}
                            </button>
                        </div>
                    </div>
                </div>
            `;

            document.getElementById('license-save-verify-btn').addEventListener('click', async function () {
                const key = document.getElementById('license-serial-key-input').value.trim() || serialKey;
                const verifyUrlRaw = document.getElementById('license-verify-url-input').value.trim();

                let url = apiUrl;
                let tok = token;
                if (verifyUrlRaw) {
                    try {
                        const parsed = new URL(verifyUrlRaw);
                        tok = parsed.searchParams.get('token');
                        url = parsed.origin + parsed.pathname;
                    } catch (e) {
                        Swal.fire(__('invalid_url', 'Invalid URL'), __('verify_url_invalid', 'Verify URL is not a valid URL.'), 'warning');
                        return;
                    }
                    if (!tok) {
                        Swal.fire(__('invalid_url', 'Invalid URL'), __('verify_url_needs_token', 'Verify URL must contain a ?token=... parameter - paste the full URL from the license admin panel.'), 'warning');
                        return;
                    }
                }
                if (!url || !key || !tok) {
                    Swal.fire(__('missing_info', 'Missing info'), __('license_missing_info', 'Enter a serial key and Verify URL (server has no license configured yet).'), 'warning');
                    return;
                }
                const $btn = this;
                $btn.disabled = true;
                document.getElementById('license-verify-spinner').style.display = 'inline-block';

                try {
                    const response = await fetch('./includes/ajaxFile/ajaxLicenseCheck.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({ api_url: url, serial_key: key, token: tok })
                    });
                    const data = await response.json();
                    if (!data.success) {
                        Swal.fire(__('error', 'Error'), escapeHtml(data.message || __('license_save_failed', 'Could not save the key.')), 'error');
                        return;
                    }
                    if (data.valid) {
                        await Swal.fire(__('license_active', 'License Active'), escapeHtml(data.message || __('license_verified', 'License verified successfully.')), 'success');
                    } else {
                        Swal.fire(__('license_not_active', 'License Not Active'), escapeHtml(data.message || __('license_key_invalid', 'This key is not valid.')), 'error');
                    }
                    await loadSettings();
                    renderSettingsGroup('license');
                } catch (err) {
                    Swal.fire(__('error', 'Error'), __('request_failed', 'Request failed') + ': ' + escapeHtml(err.message), 'error');
                } finally {
                    $btn.disabled = false;
                    document.getElementById('license-verify-spinner').style.display = 'none';
                }
            });
        }

        // --- Theme Config hub: Menu Theme + Company Logo (both plain app_settings
        // fields, saved via the outer Save Changes button) + Screen Settings (own
        // scale/resolution/fullscreen, self-saving) as sub-tabs - same pattern as
        // renderAttendanceConfigHub's Timetables/Device Monitor/Data Retention.
        function renderThemeConfigHub() {
            // sidebar_theme/sidebar_icon_style/logo/favicon rows only ever load into
            // groupedSettings for a full settings admin.
            const showMenuTheme = isFullSettingsAdmin;
            const showCompanyLogo = isFullSettingsAdmin;
            let firstTab = null;
            const themeTabs = [];
            if (showMenuTheme) themeTabs.push({ key: 'menu_theme', icon: 'mdi-menu', label: __('menu_theme', 'Menu Theme') });
            if (showCompanyLogo) themeTabs.push({ key: 'company_logo', icon: 'mdi-image', label: __('company_logo', 'Company Logo') });
            if (canAccessScreenSettingsTab) themeTabs.push({ key: 'screen_settings', icon: 'mdi-monitor-multiple', label: __('screen_settings', 'Screen Settings') });
            firstTab = themeTabs.length ? themeTabs[0].key : null;
            const navHtml = srSubNav('theme-config-sub-nav', themeTabs);

            settingsContainer.innerHTML = `
                <div class="tab-pane active" id="group-theme_config" role="tabpanel">
                    ${srTabHead(groupMeta('theme_config').icon, __('theme_config', 'Theme Config'), groupMeta('theme_config').sub || '')}
                    ${navHtml}
                    <div id="theme-config-sub-content"></div>
                </div>
            `;

            const subContent = document.getElementById('theme-config-sub-content');
            const saveBtnWrapper = document.getElementById('saveBtnWrapper');

            function renderSubTab(key) {
                document.querySelectorAll('#theme-config-sub-nav a').forEach(a => {
                    a.classList.toggle('active', a.dataset.subTab === key);
                });
                if (key === 'menu_theme') {
                    // Menu Theme rows are plain app_settings fields - the generic renderer
                    // wires them into the outer form, so the outer Save Changes button saves them.
                    renderSettingsGroup('theme_config', subContent);
                } else if (key === 'company_logo') {
                    renderSettingsGroup('theme_config_logo', subContent);
                } else {
                    if (saveBtnWrapper) saveBtnWrapper.style.display = 'none'; // Screen Settings self-saves via its own button(s)
                    renderScreenSettingsSettings(subContent);
                }
            }

            document.querySelectorAll('#theme-config-sub-nav a').forEach(a => {
                a.addEventListener('click', function(e) {
                    e.preventDefault();
                    renderSubTab(this.dataset.subTab);
                });
            });

            if (firstTab) {
                renderSubTab(firstTab);
            } else {
                subContent.innerHTML = '<p class="text-center text-danger">' + __('access_denied', 'Access denied') + '</p>';
            }
        }

        // --- Screen Settings tab (per-user Scale % / reference Resolution / Fullscreen) ---
        // Mirrors the Special Access tab's UX: search-select a user, edit just that one
        // user's settings in a modal, see only users with a saved override listed below -
        // never a table pre-populated with every user.
        // Per-user color theme (stored as 'theme' in screen_settings_by_user):
        // 'default' follows the global Theme Config > App theme, 'light'/'dark' override it
        // for that user only - see includes/theme_dark.php.
        function screenThemeOptions(selected) {
            const current = selected || 'default';
            return [
                ['default', __('theme_default_follow_app', 'Default (follow App theme)')],
                ['light', __('theme_light', 'Light')],
                ['dark', __('theme_dark', 'Dark')]
            ].map(([value, label]) => `<option value="${value}" ${current === value ? 'selected' : ''}>${label}</option>`).join('');
        }

        function screenThemeLabel(theme) {
            if (theme === 'dark') return __('theme_dark', 'Dark');
            if (theme === 'light') return __('theme_light', 'Light');
            return __('theme_default', 'Default');
        }

        async function renderScreenSettingsSettings(hostEl) {
            hostEl = hostEl || settingsContainer;
            if (!isFullSettingsAdmin) {
                const own = window.APP_SETTINGS_OWN_SCREEN_SETTINGS || screenSettingsDefaults;
                hostEl.innerHTML = `
                    <div class="tab-pane active" id="group-screen_settings" role="tabpanel">
                        <div class="sr-card as-section">
                            <div class="sr-card-head">
                                <div>
                                    <div class="sr-card-title"><i class="mdi mdi-monitor-multiple"></i> ${__('screen_settings', 'Screen Settings')}</div>
                                    <div class="sr-card-sub">${__('own_screen_settings_desc_scale', 'Adjust the display scale, fullscreen behavior and color theme for your own account.')}</div>
                                </div>
                            </div>
                            <div class="as-fields">
                                <div class="as-field">
                                    <label class="as-field-label" for="own-screen-scale">${__('screen_scale', 'Scale %')}</label>
                                    <div class="as-field-control"><input type="number" min="25" max="300" step="5" class="form-control as-num-input" id="own-screen-scale" value="${escapeHtml(own.scale)}"></div>
                                </div>
                                <div class="as-field">
                                    <label class="as-field-label" for="own-screen-fullscreen">${__('open_in_fullscreen', 'Open in Fullscreen')}</label>
                                    <div class="as-field-control">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="own-screen-fullscreen" ${own.fullscreen ? 'checked' : ''}>
                                            <label class="custom-control-label" for="own-screen-fullscreen">${__('enabled', 'Enabled')}</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="as-field">
                                    <label class="as-field-label" for="own-screen-theme">${__('theme_color', 'Theme')}</label>
                                    <div class="as-field-control"><select class="form-control" id="own-screen-theme">${screenThemeOptions(own.theme)}</select></div>
                                </div>
                            </div>
                            <div class="as-section-foot">
                                <button type="button" class="sr-btn sr-btn-primary sr-btn-sm" id="saveOwnScreenSettingsBtn"><i class="mdi mdi-content-save"></i> ${__('save_changes', 'Save Changes')}</button>
                            </div>
                        </div>
                    </div>
                `;
                document.getElementById('saveOwnScreenSettingsBtn').addEventListener('click', saveOwnScreenSettings);
                return;
            }

            hostEl.innerHTML = `
                <div class="tab-pane active" id="group-screen_settings" role="tabpanel">
                    <div class="as-subhead">
                        <p class="ac-sub">${__('screen_settings_admin_desc_scale', 'Search a user and set their display scale, fullscreen behavior and color theme (Light / Dark).')}</p>
                        <span class="sr-pill tone-slate" id="screen-settings-total-badge"><span class="sr-dot"></span>-</span>
                    </div>

                    <div class="sr-card as-section as-picker">
                        <div class="as-picker-ico"><i class="mdi mdi-account-plus"></i></div>
                        <div class="as-picker-body">
                            <label for="screen-settings-user-select">${__('select_user')}</label>
                            <select id="screen-settings-user-select" class="form-control select2"></select>
                            <small class="sr-fhint">${__('picking_a_user_opens_the_access_editor', 'Picking a user opens the access editor.')}</small>
                        </div>
                    </div>

                    <div class="sr-card as-section">
                        <div class="sr-toolbar">
                            <div class="sr-card-title"><i class="mdi mdi-account-multiple-outline"></i> ${__('assigned_users', 'Assigned Users')}</div>
                            <div class="sr-search as-list-search">
                                <i class="mdi mdi-magnify"></i>
                                <input type="search" id="screen-settings-assigned-search" placeholder="${__('search')}..." autocomplete="off" aria-label="${__('search')}">
                            </div>
                        </div>
                        <div id="screen-settings-assigned-users-list" class="as-user-list">
                            <div class="ac-loading"><span class="spinner-border spinner-border-sm" role="status"></span> ${__('loading')}</div>
                        </div>
                    </div>
                </div>
            `;

            await new Promise(resolve => setTimeout(resolve, 300));

            bindUserCardSearch('screen-settings-assigned-search', 'screen-settings-assigned-users-list');
            const { users } = await fetchScreenSettingsData();
            const select = document.getElementById('screen-settings-user-select');
            if (!select) return;

            if (!users.length) {
                select.innerHTML = `<option value="">${__('no_users_found')}</option>`;
                renderAssignedScreenSettingsSummary(users);
                return;
            }

            let options = `<option value="">${__('select_user')}</option>`;
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
            $select.off('change.screenSettings select2:select.screenSettings select2:clear.screenSettings');
            $select.on('change.screenSettings select2:select.screenSettings select2:clear.screenSettings', function () {
                const selectedEmpId = String($select.val() || '').trim();
                if (!selectedEmpId) return;
                openScreenSettingsEditModal(selectedEmpId, users);
                // Reset to placeholder - the modal is the single source of truth for editing.
                $select.val('').trigger('change.select2');
            });

            renderAssignedScreenSettingsSummary(users);
        }

        // Applies a Screen Settings change to THIS tab right now, no reload required -
        // used right after a save succeeds for whichever emp_id matches the currently
        // logged-in user (see window.APP_SETTINGS_CURRENT_EMP_ID). Mirrors the same zoom/
        // fullscreen logic includes/main_menu.php injects server-side on every page load,
        // just applied live instead of waiting for the next navigation.
        function applyScreenSettingsLive(settings) {
            if (!settings) return;
            try {
                document.documentElement.style.zoom = (settings.scale || 100) + '%';
            } catch (e) { /* ignore - unsupported browser */ }

            if (settings.fullscreen) {
                // Keep retrying on every click/keydown (capture phase, so an in-between
                // element's stopPropagation() can't block it) until requestFullscreen()
                // actually succeeds - see the matching comment in includes/main_menu.php
                // for why a naive one-shot { once: true } listener was flaky.
                function cleanup() {
                    document.removeEventListener('click', tryEnterFullscreen, true);
                    document.removeEventListener('keydown', tryEnterFullscreen, true);
                }
                function tryEnterFullscreen() {
                    if (document.fullscreenElement || document.webkitFullscreenElement) {
                        cleanup();
                        return;
                    }
                    const el = document.documentElement;
                    const request = el.requestFullscreen || el.webkitRequestFullscreen || el.msRequestFullscreen;
                    if (!request) { cleanup(); return; }
                    let result;
                    try {
                        result = request.call(el);
                    } catch (e) {
                        return;
                    }
                    if (result && typeof result.then === 'function') {
                        result.then(cleanup).catch(function () { /* declined - try again next time */ });
                    } else {
                        cleanup();
                    }
                }
                document.addEventListener('click', tryEnterFullscreen, true);
                document.addEventListener('keydown', tryEnterFullscreen, true);
            }
        }

        async function fetchScreenSettingsData() {
            if (Array.isArray(screenSettingsUsersRaw)) {
                return { users: screenSettingsUsersRaw, map: screenSettingsMap, defaults: screenSettingsDefaults };
            }

            try {
                const response = await fetch('./includes/settings_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ action: 'get_screen_settings_data' })
                });
                const data = await response.json();
                if (!data.success || !Array.isArray(data.users)) {
                    throw new Error(data.message || __('failed_to_load_users'));
                }
                screenSettingsUsersRaw = data.users;
                // Guard against an empty map ever coming back as a JSON array ([] instead
                // of {}) - assigning an emp_id-keyed property onto a real JS Array turns it
                // into a sparse array (numeric-looking keys become indices), which then
                // serializes as a huge array of nulls on save and corrupts the stored map.
                screenSettingsMap = (data.map && typeof data.map === 'object' && !Array.isArray(data.map)) ? data.map : {};
                screenSettingsDefaults = data.defaults || screenSettingsDefaults;
            } catch (error) {
                console.error('Failed loading screen settings:', error);
                screenSettingsUsersRaw = [];
            }

            return { users: screenSettingsUsersRaw, map: screenSettingsMap, defaults: screenSettingsDefaults };
        }

        async function saveScreenSettingsMap() {
            const response = await fetch('./includes/settings_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'update_screen_settings_map',
                    screen_settings_by_user: JSON.stringify(screenSettingsMap)
                })
            });
            const data = await response.json();
            if (!data.success) {
                throw new Error(data.message || __('could_not_save_settings'));
            }
        }

        function renderAssignedScreenSettingsSummary(users) {
            const container = document.getElementById('screen-settings-assigned-users-list');
            if (!container) return;

            const userMap = new Map((users || []).map(u => [String(u.emp_id || ''), u]));
            const assignedEmpIds = Object.keys(screenSettingsMap || {});

            setAssignedCountPill('screen-settings-total-badge', assignedEmpIds.length);

            if (!assignedEmpIds.length) {
                container.innerHTML = `<div class="sr-empty"><i class="mdi mdi-monitor-multiple"></i>${__('no_assigned_users_yet')}</div>`;
                return;
            }

            let html = '';
            assignedEmpIds.forEach(empId => {
                const user = userMap.get(empId);
                const name = user ? ((user.name || '').trim() || empId) : empId;
                const role = user ? ((user.user_type || '').trim()) : '';
                const s = screenSettingsMap[empId] || screenSettingsDefaults;

                html += '<div class="as-user-card">';
                html += srUserCardHead(name, empId, role, '', `
                    <button type="button" class="sr-btn sr-btn-ghost sr-btn-sm sr-btn-icon edit-assigned-screen-settings-user" data-emp-id="${escapeHtml(empId)}" title="${__('edit')}"><i class="mdi mdi-pencil"></i></button>
                    <button type="button" class="sr-btn sr-btn-ghost sr-btn-sm sr-btn-icon ac-remove remove-assigned-screen-settings-user" data-emp-id="${escapeHtml(empId)}" title="${__('remove')}"><i class="mdi mdi-delete"></i></button>`);
                html += `<div class="as-user-body as-chip-wrap">
                    <span class="sr-pill tone-indigo"><i class="mdi mdi-monitor"></i> ${__('screen_scale', 'Scale')}: ${escapeHtml(s.scale)}%</span>
                    <span class="sr-pill tone-${s.fullscreen ? 'green' : 'slate'}"><span class="sr-dot"></span>${s.fullscreen ? __('fullscreen_on', 'Fullscreen: On') : __('fullscreen_off', 'Fullscreen: Off')}</span>
                    <span class="sr-pill tone-${s.theme === 'dark' ? 'indigo' : (s.theme === 'light' ? 'amber' : 'slate')}"><i class="mdi mdi-theme-light-dark"></i> ${__('theme_color', 'Theme')}: ${escapeHtml(screenThemeLabel(s.theme))}</span>
                </div>`;
                html += '</div>';
            });

            container.innerHTML = html;

            container.querySelectorAll('.edit-assigned-screen-settings-user').forEach(btn => {
                btn.addEventListener('click', function () {
                    const empId = String(this.dataset.empId || '');
                    if (!empId) return;
                    openScreenSettingsEditModal(empId, users);
                });
            });

            container.querySelectorAll('.remove-assigned-screen-settings-user').forEach(btn => {
                btn.addEventListener('click', function () {
                    const empId = String(this.dataset.empId || '');
                    if (!empId) return;
                    Swal.fire({
                        title: __('remove_user_assignment'),
                        text: __('this_will_remove_screen_settings_for_this_user', 'This will remove the custom screen settings for this user (they revert to the 100% / no-fullscreen default).'),
                        icon: 'warning',
                        allowOutsideClick: false,
                        confirmButtonColor: '#dc2626',
                        showCancelButton: true,
                        confirmButtonText: __('yes_remove_it'),
                        cancelButtonText: __('cancel')
                    }).then(async (result) => {
                        if (!result.isConfirmed) return;

                        delete screenSettingsMap[empId];

                        Swal.fire({
                            title: __('saving', 'Saving...'),
                            allowOutsideClick: false,
                            didOpen: () => Swal.showLoading()
                        });

                        try {
                            await saveScreenSettingsMap();
                            await Swal.fire({
                                icon: 'success',
                                title: __('removed'),
                                confirmButtonText: __('ok', 'OK')
                            });
                            renderAssignedScreenSettingsSummary(users);
                        } catch (error) {
                            Swal.fire(__('error'), error.message, 'error');
                        }
                    });
                });
            });
        }

        // Single entry point for adding/editing one user's Screen Settings: a SweetAlert2
        // modal, opened either by picking a user from the select above or the "Edit" button
        // on an assigned-user card - mirrors openSpecialAccessEditModal's flow.
        function openScreenSettingsEditModal(empId, users) {
            const targetEmpId = String(empId || '').trim();
            if (!targetEmpId) return;

            const user = (users || []).find(u => String(u.emp_id || '').trim() === targetEmpId);
            const name = user ? ((user.name || '').trim() || targetEmpId) : targetEmpId;
            const s = screenSettingsMap[targetEmpId] || screenSettingsDefaults;

            Swal.fire({
                title: `${__('screen_settings', 'Screen Settings')}: ${escapeHtml(name)}`,
                html: `
                    <form class="sr-form" onsubmit="return false">
                        <div class="sr-fsec">
                            <div class="sr-fsec-head"><span><i class="mdi mdi-monitor-multiple"></i> ${__('screen_settings', 'Screen Settings')}</span><span class="sr-chip sr-mono">#${escapeHtml(targetEmpId)}</span></div>
                            <div class="sr-fgrid">
                                <div class="sr-fcol c-6">
                                    <label for="swal-ss-scale">${__('screen_scale', 'Scale %')}</label>
                                    <input type="number" min="25" max="300" step="5" id="swal-ss-scale" class="form-control" value="${escapeHtml(s.scale)}">
                                </div>
                                <div class="sr-fcol c-6">
                                    <label for="swal-ss-theme">${__('theme_color', 'Theme')}</label>
                                    <select id="swal-ss-theme" class="form-control">${screenThemeOptions(s.theme)}</select>
                                </div>
                                <div class="sr-fcol c-12">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="swal-ss-fullscreen" ${s.fullscreen ? 'checked' : ''}>
                                        <label class="custom-control-label" for="swal-ss-fullscreen">${__('open_in_fullscreen', 'Open in Fullscreen')}</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                `,
                width: '560px',
                allowOutsideClick: false,
                showCancelButton: true,
                confirmButtonText: `<i class="mdi mdi-content-save"></i> ${__('save_changes', 'Save Changes')}`,
                cancelButtonText: __('cancel'),
                focusConfirm: false,
                preConfirm: () => ({
                    scale: parseInt(document.getElementById('swal-ss-scale').value, 10) || 100,
                    fullscreen: document.getElementById('swal-ss-fullscreen').checked ? 1 : 0,
                    theme: document.getElementById('swal-ss-theme').value || 'default'
                })
            }).then(async (result) => {
                if (!result.isConfirmed) return;

                screenSettingsMap[targetEmpId] = result.value;

                Swal.fire({
                    title: __('saving', 'Saving...'),
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                try {
                    await saveScreenSettingsMap();
                    // If the admin just edited their OWN row, apply it to this tab right now
                    // instead of making them reload (or worse, hard-refresh) to see it.
                    const isEditingSelf = window.APP_SETTINGS_CURRENT_EMP_ID && targetEmpId === String(window.APP_SETTINGS_CURRENT_EMP_ID);
                    if (isEditingSelf) {
                        applyScreenSettingsLive(result.value);
                    }
                    await Swal.fire({
                        icon: 'success',
                        title: __('saved', 'Saved'),
                        text: isEditingSelf ? __('screen_settings_applied_now', 'Applied to this session immediately.') : '',
                        confirmButtonText: __('ok', 'OK')
                    });
                    renderAssignedScreenSettingsSummary(users);
                    // The theme's CSS/JS are injected server-side into <head>
                    // (includes/theme_dark.php), so the admin's own theme change only
                    // shows after a reload. Other users see it on their next page load.
                    if (isEditingSelf) {
                        window.location.reload();
                    }
                } catch (error) {
                    Swal.fire(__('error'), error.message, 'error');
                }
            });
        }

        async function saveOwnScreenSettings() {
            try {
                const response = await fetch('./includes/settings_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'update_own_screen_settings',
                        scale: document.getElementById('own-screen-scale').value,
                        fullscreen: document.getElementById('own-screen-fullscreen').checked ? 1 : 0,
                        theme: document.getElementById('own-screen-theme').value || 'default'
                    })
                });
                const data = await response.json();
                if (data.success) {
                    // Self-service always edits the logged-in user's own row - apply it to
                    // this tab immediately, no reload (hard or otherwise) needed.
                    applyScreenSettingsLive(data.settings);
                    await Swal.fire(__('success', 'Success'), __('screen_settings_applied_now', 'Applied to this session immediately.'), 'success');
                    // Theme is injected server-side (includes/theme_dark.php) - reload to show it.
                    window.location.reload();
                } else {
                    Swal.fire(__('error', 'Error'), data.message || __('generic_error_message', 'An unexpected error occurred.'), 'error');
                }
            } catch (error) {
                Swal.fire(__('error', 'Error'), __('generic_error_message', 'An unexpected error occurred.'), 'error');
            }
        }

        async function renderSpecialAccessSettings() {
            settingsContainer.innerHTML = `
                <div class="tab-pane active" id="group-special-access" role="tabpanel">
                    ${srTabHead(groupMeta('special_access').icon, __('special_access_by_user'),
                        __('select_a_user_and_grant_them_specific_admin_hr_abilities') + ' ' + __('report_access_is_also_managed_here', 'Report access (which reports a user can view) is also managed here, per user.'),
                        '<span class="sr-pill tone-slate" id="special-access-total-users-badge"><span class="sr-dot"></span>-</span>')}

                    <div id="special-access-panel">
                        <div class="sr-card as-section as-picker">
                            <div class="as-picker-ico"><i class="mdi mdi-account-plus"></i></div>
                            <div class="as-picker-body">
                                <label for="special-access-user-select">${__('select_user')}</label>
                                <select id="special-access-user-select" class="form-control select2"></select>
                                <small class="sr-fhint">${__('picking_a_user_opens_the_access_editor', 'Picking a user opens the access editor.')}</small>
                            </div>
                        </div>

                        <div class="sr-card as-section">
                            <div class="sr-toolbar">
                                <div class="sr-card-title"><i class="mdi mdi-account-multiple-outline"></i> ${__('assigned_users')}</div>
                                <div class="sr-search as-list-search">
                                    <i class="mdi mdi-magnify"></i>
                                    <input type="search" id="special-access-assigned-search" placeholder="${__('search')}..." autocomplete="off" aria-label="${__('search')}">
                                </div>
                            </div>
                            <div id="special-access-assigned-users-list" class="as-user-list">
                                <div class="ac-loading"><span class="spinner-border spinner-border-sm" role="status"></span> ${__('loading')}</div>
                            </div>
                        </div>

                        <div id="special-access-loading-overlay">
                            <div class="spinner-border text-primary" role="status"></div>
                        </div>
                    </div>
                </div>
            `;

            // Guarantee the loading spinner above actually paints before it gets overwritten
            // below - fetchSpecialAccessUsers() usually resolves from cache on the very next
            // microtask, which can otherwise skip straight past the loading frame unnoticed.
            // A real timeout is used (not requestAnimationFrame) since rAF's promise resolves
            // in a microtask that runs before the browser paints, so it wouldn't force a
            // visible frame here either.
            await new Promise(resolve => setTimeout(resolve, 300));

            // NOTE: specialAccessMap is intentionally NOT re-initialized from appSettings here.
            // It's seeded once in loadSettings() right after fetch, and the hidden input that
            // carries it to Save now lives outside #settings-container (persists across tab
            // switches). Re-parsing from appSettings on every render of this tab would silently
            // discard any grant/removal the admin made before navigating to another tab and back.

            // Plain employees are included here on purpose - the "Access Page: ..." special
            // access keys exist specifically to grant a single employee access to a page that's
            // normally blocked for their role (see get_special_access_page_labels()).
            bindUserCardSearch('special-access-assigned-search', 'special-access-assigned-users-list');
            const users = await fetchSpecialAccessUsers();
            specialAccessEligibleUsers = users;
            const select = document.getElementById('special-access-user-select');

            if (!select) return;

            if (!users.length) {
                select.innerHTML = '<option value="">' + __('no_users_found') + '</option>';
                renderAssignedSpecialAccessSummary(specialAccessEligibleUsers);
                return;
            }

            let options = `<option value="">${__('select_user')}</option>`;
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
                if (!selectedEmpId) return;
                openSpecialAccessEditModal(selectedEmpId);
                // Reset back to the placeholder - the modal is the single source of truth for
                // editing, this select is only an entry point (works for add and edit alike).
                $select.val('').trigger('change.select2');
            });

            renderAssignedSpecialAccessSummary(specialAccessEligibleUsers);
        }

        /**
         * =================================================================
         * == ORG STRUCTURE SUB-TABS (Departments, Sub-Departments, Job Titles,
         * == Locations, Companies) - one config-driven list + add/edit/delete
         * == renderer (renderOrgCrud) in the sr-* design (.ac-* / .org-* rules in
         * == app_settings.php). Each config only describes its handler, columns
         * == and form fields; the handler actions/params are unchanged.
         * =================================================================
         */
        const ORG_DEPT_COLORS = [
            { v: 'custom', l: 'custom', hex: '#02c0ce' },
            { v: 'purple', l: 'purple', hex: '#777edd' },
            { v: 'primary', l: 'primary', hex: '#2d7bf4' },
            { v: 'success', l: 'success', hex: '#0acf97' }
        ];
        let citiesListCache = null;
        let departmentsListCache = null;

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

        // Name cell: English title + Arabic underneath
        function orgNameCell(icon, en, ar) {
            return `<div class="org-name">
                        <span class="ac-ico"><i class="mdi ${icon}"></i></span>
                        <div class="org-name-text">
                            <span class="sr-cell-title">${escapeHtml(en || 'N/A')}</span>
                            ${ar ? `<span class="sr-cell-sub org-ar" dir="rtl">${escapeHtml(ar)}</span>` : ''}
                        </div>
                    </div>`;
        }

        function orgColorPill(value) {
            const c = ORG_DEPT_COLORS.find(x => x.v === String(value || '').trim().toLowerCase()) || ORG_DEPT_COLORS[0];
            return `<span class="org-color"><span class="org-swatch" style="background:${c.hex}"></span>${escapeHtml(c.l)}</span>`;
        }

        const ORG_CRUD = {
            departments: {
                pane: 'group-departments', prefix: 'department', url: './includes/departments_handler.php',
                icon: 'mdi-domain',
                title: __('department_management', 'Department Management'),
                sub: __('manage_departments_in_english_and_arabic', 'Manage departments in English and Arabic.'),
                addLabel: __('add_new_department', 'Add New Department'),
                editTitle: __('edit_department', 'Edit Department'),
                deleteTitle: __('delete_department', 'Delete Department'),
                searchPh: __('search_departments_english_or_arabic', 'Search departments (English or Arabic)...'),
                actions: { list: 'get_departments', get: 'get_department', add: 'add_department', update: 'update_department', del: 'delete_department' },
                idParam: 'department_id', listKey: 'departments', getKey: 'department',
                nameOf: r => r.dep_nme,
                empty: __('no_departments_configured_yet', 'No departments configured yet'),
                noMatch: __('no_departments_match_your_search', 'No departments match your search'),
                msgs: {
                    added: __('department_added_successfully', 'Department added successfully'),
                    updated: __('department_updated_successfully', 'Department updated successfully'),
                    deleted: __('department_deleted_successfully', 'Department deleted successfully'),
                    notFound: __('department_not_found', 'Department not found')
                },
                columns: [
                    { label: __('department', 'Department'), cell: r => orgNameCell('mdi-domain', r.dep_nme, r.dep_nme_ar) },
                    { label: __('department_color', 'Color'), cell: r => orgColorPill(r.dept_clr) }
                ],
                search: r => [r.dep_nme, r.dep_nme_ar, r.dept_clr],
                fields: [
                    { name: 'department_en', key: 'dep_nme', label: __('department_english', 'Department (English)'), ph: __('enter_department_in_english', 'Enter department in English'), col: 6,
                      msg: __('department_in_english_is_required', 'Department name in English is required') },
                    { name: 'department_ar', key: 'dep_nme_ar', label: __('department_arabic', 'Department (Arabic)'), ph: __('enter_department_in_arabic', 'Enter department in Arabic'), col: 6, rtl: true,
                      msg: __('department_in_arabic_is_required', 'Department name in Arabic is required') },
                    { name: 'department_color', key: 'dept_clr', label: __('department_color', 'Color'), type: 'swatch', col: 12, options: ORG_DEPT_COLORS,
                      msg: __('invalid_department_color', 'Please select a valid department color') }
                ],
                // Sub-Departments' department dropdown caches this list
                onChange: () => { departmentsListCache = null; }
            },
            sub_departments: {
                pane: 'group-sub_departments', prefix: 'sub-department', url: './includes/sub_departments_handler.php',
                icon: 'mdi-source-fork',
                title: __('sub_departments_management', 'Sub-Departments Management'),
                sub: __('manage_sub_departments_by_department', 'Manage sub-departments and assign each one to a department'),
                addLabel: __('add_new_sub_department', 'Add New Sub-Department'),
                editTitle: __('edit_sub_department', 'Edit Sub-Department'),
                deleteTitle: __('delete_sub_department', 'Delete Sub-Department'),
                searchPh: __('search_sub_departments', 'Search sub-departments or departments'),
                actions: { list: 'get_sub_departments', get: 'get_sub_department', add: 'add_sub_department', update: 'update_sub_department', del: 'delete_sub_department' },
                idParam: 'sub_dept_id', listKey: 'sub_departments', getKey: 'sub_department',
                nameOf: r => r.name_en,
                empty: __('no_sub_departments_configured_yet', 'No sub-departments configured yet.'),
                noMatch: __('no_sub_departments_match_your_search', 'No sub-departments match your search'),
                msgs: {
                    added: __('sub_department_added_successfully', 'Sub-department added successfully'),
                    updated: __('sub_department_updated_successfully', 'Sub-department updated successfully'),
                    deleted: __('sub_department_deleted_successfully', 'Sub-department deleted successfully'),
                    notFound: __('sub_department_not_found', 'Sub-department not found')
                },
                columns: [
                    { label: __('sub_department', 'Sub-Department'), cell: r => orgNameCell('mdi-source-fork', r.name_en, r.name_ar) },
                    { label: __('department_label'), cell: r => r.dep_nme ? `<span class="sr-chip"><i class="mdi mdi-domain"></i> ${escapeHtml(r.dep_nme)}</span>` : '<span class="ac-muted">-</span>' }
                ],
                search: r => [r.name_en, r.name_ar, r.dep_nme],
                fields: [
                    { name: 'department_id', key: 'department_id', label: __('department_label'), type: 'select', col: 12,
                      placeholder: __('select_department', 'Select Department'),
                      options: async () => (await fetchDepartmentsList()).map(d => ({ v: d.id, l: d.dep_nme })),
                      msg: __('please_select_a_department', 'Please select a department') },
                    { name: 'name_en', key: 'name_en', label: __('sub_department_name_english', 'Sub-Department (English)'), col: 6,
                      msg: __('sub_department_name_in_english_is_required', 'Sub-department name in English is required') },
                    { name: 'name_ar', key: 'name_ar', label: __('sub_department_name_arabic', 'Sub-Department (Arabic)'), col: 6, rtl: true,
                      msg: __('sub_department_name_in_arabic_is_required', 'Sub-department name in Arabic is required') }
                ]
            },
            job_titles: {
                pane: 'group-job', prefix: 'job', url: './includes/job_titles_handler.php',
                icon: 'mdi-briefcase',
                title: __('job_titles_management'),
                sub: __('manage_job_titles_in_english_and_arabic'),
                addLabel: __('add_new_job_title'),
                editTitle: __('edit_job_title'),
                deleteTitle: __('delete_job_title'),
                searchPh: __('search_job_titles_english_or_arabic'),
                actions: { list: 'get_job_titles', get: 'get_job_title', add: 'add_job_title', update: 'update_job_title', del: 'delete_job_title' },
                idParam: 'job_id', listKey: 'jobs', getKey: 'job',
                nameOf: r => r.job,
                empty: __('No job titles configured yet.'),
                noMatch: __('no_job_titles_match_your_search'),
                msgs: {
                    added: __('job_title_added_successfully'),
                    updated: __('job_title_updated_successfully'),
                    deleted: __('job_title_deleted_successfully'),
                    notFound: __('job_title_not_found')
                },
                columns: [
                    { label: __('job_title_english'), cell: r => orgNameCell('mdi-briefcase', r.job, '') },
                    { label: __('job_title_arabic'), cell: r => r.job_ar ? `<span class="org-ar-cell" dir="rtl">${escapeHtml(r.job_ar)}</span>` : '<span class="ac-muted">N/A</span>' }
                ],
                search: r => [r.job, r.job_ar],
                fields: [
                    { name: 'job_title_en', key: 'job', label: __('job_title_english'), ph: __('enter_job_title_in_english'), col: 6,
                      msg: __('job_title_in_english_is_required') },
                    { name: 'job_title_ar', key: 'job_ar', label: __('job_title_arabic'), ph: __('enter_job_title_in_arabic'), col: 6, rtl: true,
                      msg: __('job_title_in_arabic_is_required') }
                ]
            },
            locations: {
                pane: 'group-locations', prefix: 'location', url: './includes/locations_handler.php',
                icon: 'mdi-map-marker',
                title: __('locations_management', 'Locations Management'),
                sub: __('manage_locations_by_city', 'Manage locations and assign each one to a city'),
                addLabel: __('add_new_location', 'Add New Location'),
                editTitle: __('edit_location', 'Edit Location'),
                deleteTitle: __('delete_location', 'Delete Location'),
                searchPh: __('search_locations', 'Search locations or cities'),
                actions: { list: 'get_locations', get: 'get_location', add: 'add_location', update: 'update_location', del: 'delete_location' },
                idParam: 'location_id', listKey: 'locations', getKey: 'location',
                nameOf: r => r.name_en,
                empty: __('no_locations_configured_yet', 'No locations configured yet.'),
                noMatch: __('no_locations_match_your_search', 'No locations match your search'),
                msgs: {
                    added: __('location_added_successfully', 'Location added successfully'),
                    updated: __('location_updated_successfully', 'Location updated successfully'),
                    deleted: __('location_deleted_successfully', 'Location deleted successfully'),
                    notFound: __('location_not_found', 'Location not found')
                },
                columns: [
                    { label: __('location', 'Location'), cell: r => orgNameCell('mdi-map-marker', r.name_en, r.name_ar) },
                    { label: __('city'), cell: r => r.city_name_en ? `<span class="sr-chip"><i class="mdi mdi-city"></i> ${escapeHtml(r.city_name_en)}</span>` : '<span class="ac-muted">-</span>' }
                ],
                search: r => [r.name_en, r.name_ar, r.city_name_en],
                fields: [
                    { name: 'city_id', key: 'city_id', label: __('city'), type: 'select', col: 12,
                      placeholder: __('select_city', 'Select City'),
                      options: async () => (await fetchCitiesList()).map(c => ({ v: c.id, l: c.name_en })),
                      msg: __('please_select_a_city', 'Please select a city') },
                    { name: 'name_en', key: 'name_en', label: __('location_name_english', 'Location (English)'), col: 6,
                      msg: __('location_name_in_english_is_required', 'Location name in English is required') },
                    { name: 'name_ar', key: 'name_ar', label: __('location_name_arabic', 'Location (Arabic)'), col: 6, rtl: true,
                      msg: __('location_name_in_arabic_is_required', 'Location name in Arabic is required') }
                ]
            },
            // Company Code = comp_id (the legacy numeric code employees.comp_no matches
            // against); Default Timetable = companies.timetable_id (list comes with get_companies).
            companies: {
                pane: 'group-companies', prefix: 'company', url: './includes/companies_handler.php',
                icon: 'mdi-office',
                title: __('company_management', 'Company Management'),
                sub: __('manage_companies_in_english_and_arabic', 'Manage companies in English and Arabic.'),
                addLabel: __('add_new_company', 'Add New Company'),
                editTitle: __('edit_company', 'Edit Company'),
                deleteTitle: __('delete_company', 'Delete Company'),
                searchPh: __('search_companies_english_or_arabic', 'Search companies (English or Arabic)...'),
                actions: { list: 'get_companies', get: 'get_company', add: 'add_company', update: 'update_company', del: 'delete_company' },
                idParam: 'company_id', listKey: 'companies', getKey: 'company',
                nameOf: r => r.comp_name,
                empty: __('no_companies_configured_yet', 'No companies configured yet'),
                noMatch: __('no_companies_match_your_search', 'No companies match your search'),
                msgs: {
                    added: __('company_added_successfully', 'Company added successfully'),
                    updated: __('company_updated_successfully', 'Company updated successfully'),
                    deleted: __('company_deleted_successfully', 'Company deleted successfully'),
                    notFound: __('company_not_found', 'Company not found')
                },
                onList: data => { companiesTimetablesCache = Array.isArray(data.timetables) ? data.timetables : []; },
                columns: [
                    { label: __('company', 'Company'), cell: r => orgNameCell('mdi-office', r.comp_name, r.comp_name_ar) },
                    { label: __('company_code', 'Company Code'), cell: r => `<span class="sr-chip sr-mono">${escapeHtml(String(r.comp_id ?? ''))}</span>` },
                    { label: __('default_timetable', 'Default Timetable'), cell: r => r.timetable_name
                        ? `<span class="sr-pill tone-sky"><i class="mdi mdi-calendar-clock"></i> ${escapeHtml(r.timetable_name)}</span>`
                        : `<span class="ac-muted">${__('none', 'None')}</span>` }
                ],
                search: r => [r.comp_name, r.comp_name_ar, r.comp_id, r.timetable_name],
                fields: [
                    { name: 'company_en', key: 'comp_name', label: __('company_english', 'Company (English)'), ph: __('enter_company_in_english', 'Enter company in English'), col: 6,
                      msg: __('company_in_english_is_required', 'Company name in English is required') },
                    { name: 'company_ar', key: 'comp_name_ar', label: __('company_arabic', 'Company (Arabic)'), ph: __('enter_company_in_arabic', 'Enter company in Arabic'), col: 6, rtl: true,
                      msg: __('company_in_arabic_is_required', 'Company name in Arabic is required') },
                    { name: 'comp_id', key: 'comp_id', label: __('company_code', 'Company Code'), type: 'number', ph: __('enter_company_code', 'Enter a unique numeric company code'), col: 6, mono: true,
                      msg: __('valid_company_code_is_required', 'A valid Company Code is required'),
                      check: v => parseInt(v, 10) > 0 },
                    { name: 'timetable_id', key: 'timetable_id', label: __('default_timetable', 'Default Timetable'), type: 'select', col: 6, optional: true,
                      placeholder: __('no_default_timetable', 'No default timetable'),
                      options: async () => companiesTimetablesCache.map(t => ({ v: t.id, l: t.name })) }
                ]
            }
        };

        function renderDepartmentsSettings(hostEl) { renderOrgCrud(ORG_CRUD.departments, hostEl); }
        function renderSubDepartmentsSettings(hostEl) { renderOrgCrud(ORG_CRUD.sub_departments, hostEl); }
        function renderJobTitlesSettings(hostEl) { renderOrgCrud(ORG_CRUD.job_titles, hostEl); }
        function renderLocationsSettings(hostEl) { renderOrgCrud(ORG_CRUD.locations, hostEl); }
        function renderCompaniesSettings(hostEl) { renderOrgCrud(ORG_CRUD.companies, hostEl); }

        function orgPost(cfg, params) {
            return fetch(cfg.url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams(params)
            }).then(response => {
                if (!response.ok) throw new Error(`${__('error')} (${response.status})`);
                return response.json();
            });
        }

        function renderOrgCrud(cfg, hostEl) {
            hostEl = hostEl || settingsContainer;
            const p = cfg.prefix;
            hostEl.innerHTML = `
                <div class="tab-pane active" id="${cfg.pane}" role="tabpanel">
                    <div class="ac-head">
                        <div>
                            <h5 class="ac-title"><i class="mdi ${cfg.icon}"></i> ${escapeHtml(cfg.title)}</h5>
                            <p class="ac-sub">${escapeHtml(cfg.sub)}</p>
                        </div>
                        <div class="ac-head-actions">
                            <button type="button" class="sr-btn sr-btn-success sr-btn-sm" id="btn-add-${p}"><i class="mdi mdi-plus"></i> ${escapeHtml(cfg.addLabel)}</button>
                        </div>
                    </div>
                    <div class="sr-card aca-card">
                        <div class="sr-toolbar">
                            <div class="sr-search">
                                <i class="mdi mdi-magnify"></i>
                                <input type="search" id="${p}-search-input" placeholder="${escapeHtml(cfg.searchPh)}" autocomplete="off" aria-label="${__('search')}">
                            </div>
                            <span class="sr-chip" id="${p}-count-chip"><i class="mdi mdi-format-list-bulleted"></i> <span id="${p}-count">-</span></span>
                        </div>
                        <div id="${p}-container">
                            <div class="ac-loading"><span class="spinner-border spinner-border-sm" role="status"></span> ${__('loading')}</div>
                        </div>
                    </div>
                </div>`;

            document.getElementById(`btn-add-${p}`).addEventListener('click', () => openOrgForm(cfg, null));

            const searchInput = document.getElementById(`${p}-search-input`);
            // Inside #settingsForm - Enter must not submit the settings form
            searchInput.addEventListener('keydown', e => { if (e.key === 'Enter') e.preventDefault(); });
            searchInput.addEventListener('input', () => filterOrgRows(cfg));

            loadOrgRows(cfg);
        }

        async function loadOrgRows(cfg) {
            const p = cfg.prefix;
            const container = document.getElementById(`${p}-container`);
            if (!container) return;
            try {
                const data = await orgPost(cfg, { action: cfg.actions.list });
                if (cfg.onList) cfg.onList(data);
                const rows = (data.success && Array.isArray(data[cfg.listKey])) ? data[cfg.listKey] : [];
                const countEl = document.getElementById(`${p}-count`);
                if (countEl) countEl.textContent = rows.length;

                if (rows.length === 0) {
                    container.innerHTML = `<div class="sr-empty"><i class="mdi ${cfg.icon}"></i>${escapeHtml(cfg.empty)}</div>`;
                    return;
                }

                let body = '';
                rows.forEach(r => {
                    const search = cfg.search(r).filter(v => v != null).join(' ').toLowerCase();
                    body += `<tr data-search="${escapeHtml(search)}">
                        ${cfg.columns.map(c => `<td>${c.cell(r)}</td>`).join('')}
                        <td class="aca-actions">
                            <button type="button" class="sr-btn sr-btn-ghost sr-btn-sm sr-btn-icon org-edit-btn" data-id="${escapeHtml(r.id)}" title="${__('edit')}"><i class="mdi mdi-pencil"></i></button>
                            <button type="button" class="sr-btn sr-btn-ghost sr-btn-sm sr-btn-icon ac-remove org-delete-btn" data-id="${escapeHtml(r.id)}" data-name="${escapeHtml(cfg.nameOf(r) || '')}" title="${__('delete')}"><i class="mdi mdi-delete"></i></button>
                        </td>
                    </tr>`;
                });

                container.innerHTML = `
                    <div class="sr-table-wrap aca-table-wrap">
                        <table class="sr-table aca-table org-table">
                            <thead><tr>${cfg.columns.map(c => `<th>${escapeHtml(c.label)}</th>`).join('')}<th class="aca-actions">${__('actions')}</th></tr></thead>
                            <tbody>${body}</tbody>
                        </table>
                    </div>
                    <div class="sr-empty org-no-results" style="display:none"><i class="mdi mdi-magnify"></i>${escapeHtml(cfg.noMatch)}</div>`;

                container.querySelectorAll('.org-edit-btn').forEach(btn => {
                    btn.addEventListener('click', () => editOrgRow(cfg, btn.dataset.id));
                });
                container.querySelectorAll('.org-delete-btn').forEach(btn => {
                    btn.addEventListener('click', () => deleteOrgRow(cfg, btn.dataset.id, btn.dataset.name));
                });

                filterOrgRows(cfg);
            } catch (error) {
                console.error(`Error loading ${cfg.listKey}:`, error);
                container.innerHTML = `<div class="sr-notice tone-red ac-error"><i class="mdi mdi-alert-circle-outline"></i><div>${escapeHtml(error.message)}</div></div>`;
            }
        }

        function filterOrgRows(cfg) {
            const p = cfg.prefix;
            const container = document.getElementById(`${p}-container`);
            const input = document.getElementById(`${p}-search-input`);
            if (!container || !input) return;
            const q = input.value.trim().toLowerCase();
            let shown = 0;
            container.querySelectorAll('tbody tr').forEach(tr => {
                const hit = !q || tr.dataset.search.includes(q);
                tr.style.display = hit ? '' : 'none';
                if (hit) shown++;
            });
            const noRes = container.querySelector('.org-no-results');
            const wrap = container.querySelector('.aca-table-wrap');
            if (noRes) noRes.style.display = shown ? 'none' : '';
            if (wrap) wrap.style.display = shown ? '' : 'none';
        }

        async function editOrgRow(cfg, id) {
            try {
                const data = await orgPost(cfg, { action: cfg.actions.get, [cfg.idParam]: id });
                if (!data.success || !data[cfg.getKey]) throw new Error(cfg.msgs.notFound);
                openOrgForm(cfg, data[cfg.getKey], id);
            } catch (error) {
                Swal.fire({ title: __('error'), text: error.message, icon: 'error', customClass: { popup: 'sr-addline-popup sr-page' } });
            }
        }

        async function openOrgForm(cfg, record, id) {
            const isEdit = !!record;
            const p = cfg.prefix;
            const fid = f => `org-${p}-${f.name}`;

            // Async option lists (cities, departments, timetables) are loaded first
            const optionLists = {};
            try {
                for (const f of cfg.fields) {
                    if (f.type === 'select') optionLists[f.name] = await f.options();
                }
            } catch (error) {
                Swal.fire({ title: __('error'), text: error.message, icon: 'error', customClass: { popup: 'sr-addline-popup sr-page' } });
                return;
            }

            const valueOf = f => {
                if (!record) return f.type === 'swatch' ? f.options[0].v : '';
                const v = record[f.key];
                if (f.type === 'swatch') {
                    const norm = String(v || '').trim().toLowerCase();
                    return f.options.some(o => o.v === norm) ? norm : f.options[0].v;
                }
                return v == null ? '' : String(v);
            };

            const fieldsHtml = cfg.fields.map(f => {
                const val = valueOf(f);
                const req = f.optional ? '' : ' <span class="text-danger">*</span>';
                let control;
                if (f.type === 'select') {
                    control = `<select id="${fid(f)}" class="form-control">
                        <option value="">${escapeHtml(f.placeholder || __('select', 'Select'))}</option>
                        ${optionLists[f.name].map(o => `<option value="${escapeHtml(o.v)}"${String(o.v) === val ? ' selected' : ''}>${escapeHtml(o.l)}</option>`).join('')}
                    </select>`;
                } else if (f.type === 'swatch') {
                    control = `<div class="org-swatches" id="${fid(f)}">
                        ${f.options.map(o => `<label class="org-swatch-opt">
                            <input type="radio" name="${fid(f)}" value="${o.v}"${o.v === val ? ' checked' : ''}>
                            <span><span class="org-swatch" style="background:${o.hex}"></span>${escapeHtml(o.l)}</span>
                        </label>`).join('')}
                    </div>`;
                } else {
                    control = `<input type="${f.type === 'number' ? 'number' : 'text'}" id="${fid(f)}" class="form-control${f.mono ? ' sr-mono' : ''}"${f.type === 'number' ? ' min="1"' : ''}${f.rtl ? ' dir="rtl"' : ''}
                        autocomplete="off" value="${escapeHtml(val)}" placeholder="${escapeHtml(f.ph || '')}">`;
                }
                return `<div class="sr-fcol c-${f.col || 12}"><label for="${fid(f)}">${escapeHtml(f.label)}${req}</label>${control}</div>`;
            }).join('');

            const result = await Swal.fire({
                title: isEdit ? cfg.editTitle : cfg.addLabel,
                html: `<form class="sr-form" id="org-form-${p}" onsubmit="return false">
                        <div class="sr-fsec">
                            <div class="sr-fsec-head"><span><i class="mdi ${cfg.icon}"></i> ${escapeHtml(isEdit ? (cfg.nameOf(record) || cfg.editTitle) : cfg.addLabel)}</span></div>
                            <div class="sr-fgrid">${fieldsHtml}</div>
                        </div>
                    </form>`,
                width: '640px',
                allowOutsideClick: false,
                showCancelButton: true,
                confirmButtonText: isEdit ? `<i class="mdi mdi-content-save"></i> ${__('update')}` : `<i class="mdi mdi-plus"></i> ${__('add')}`,
                cancelButtonText: __('cancel'),
                showLoaderOnConfirm: true,
                customClass: { popup: 'sr-addline-popup sr-page' },
                didOpen: () => {
                    const popup = Swal.getPopup();
                    cfg.fields.filter(f => f.type === 'select').forEach(f => {
                        $(`#${fid(f)}`).select2({ width: '100%', dropdownParent: $(popup) })
                            .on('change', function() { $(this).next('.select2-container').removeClass('is-invalid'); });
                    });
                    popup.querySelector('form').addEventListener('input', e => e.target.classList.remove('is-invalid'));
                    const first = popup.querySelector('input.form-control');
                    if (first && !isEdit) first.focus();
                },
                preConfirm: async () => {
                    const params = { action: isEdit ? cfg.actions.update : cfg.actions.add };
                    if (isEdit) params[cfg.idParam] = id;
                    for (const f of cfg.fields) {
                        let v;
                        if (f.type === 'swatch') {
                            const checked = document.querySelector(`input[name="${fid(f)}"]:checked`);
                            v = checked ? checked.value : '';
                        } else {
                            v = document.getElementById(fid(f)).value.trim();
                        }
                        const bad = (!f.optional && !v) || (v && f.check && !f.check(v));
                        if (bad) {
                            const el = document.getElementById(fid(f));
                            if (f.type === 'select') $(el).next('.select2-container').addClass('is-invalid');
                            else el.classList.add('is-invalid');
                            if (f.type !== 'select' && f.type !== 'swatch') el.focus();
                            Swal.showValidationMessage(f.msg);
                            return false;
                        }
                        params[f.name] = v;
                    }
                    try {
                        const data = await orgPost(cfg, params);
                        if (!data.success) throw new Error(data.message || __('could_not_save_settings'));
                        return true;
                    } catch (error) {
                        Swal.showValidationMessage(error.message);
                        return false;
                    }
                }
            });

            if (result.isConfirmed && result.value) {
                if (cfg.onChange) cfg.onChange();
                acToast('success', isEdit ? cfg.msgs.updated : cfg.msgs.added);
                loadOrgRows(cfg);
            }
        }

        async function deleteOrgRow(cfg, id, name) {
            const result = await Swal.fire({
                title: cfg.deleteTitle,
                html: `<div class="sr-form"><div class="sr-notice tone-red is-compact"><i class="mdi mdi-alert-outline"></i>
                        <div>${name ? `<strong>${escapeHtml(name)}</strong><br>` : ''}${__('this_action_cannot_be_undone')}</div></div></div>`,
                icon: 'warning',
                width: '480px',
                allowOutsideClick: false,
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                confirmButtonText: `<i class="mdi mdi-delete"></i> ${__('yes_delete_it')}`,
                cancelButtonText: __('cancel'),
                showLoaderOnConfirm: true,
                customClass: { popup: 'sr-addline-popup sr-page' },
                preConfirm: async () => {
                    try {
                        const data = await orgPost(cfg, { action: cfg.actions.del, [cfg.idParam]: id });
                        if (!data.success) throw new Error(data.message || __('error'));
                        return true;
                    } catch (error) {
                        Swal.showValidationMessage(error.message);
                        return false;
                    }
                }
            });

            if (result.isConfirmed && result.value) {
                if (cfg.onChange) cfg.onChange();
                acToast('success', cfg.msgs.deleted);
                loadOrgRows(cfg);
            }
        }

        /**
         * =================================================================
         * == APPROVAL CHAIN SETTINGS (sr-* design: assets/css/smart_request.css
         * == + the .ac-* rules in app_settings.php)
         * =================================================================
         */
        const APPROVER_ROLES = [
            { v: 'administrator', l: 'administrator' },
            { v: 'gm', l: 'general_manager_gm' },
            { v: 'hr_senior_bp', l: 'hr_senior_bp' },
            { v: 'hr_operations', l: 'hr_operations' },
            { v: 'hr_supervisor', l: 'hr_supervisor' },
            { v: 'hr_recruitment', l: 'hr_recruitment' },
            { v: 'hr_payroll', l: 'hr_payroll' },
            { v: 'hr', l: 'hr_manager' },
            { v: 'finance_officer', l: 'finance_officer' },
            { v: 'finance', l: 'finance_manager' },
            { v: 'auditor', l: 'auditor' },
            { v: 'gr_officer', l: 'gr_officer' },
            { v: 'it', l: 'it_manager' },
            { v: 'dept_user', l: 'department_user' },
            { v: 'assistant', l: 'assistant' },
            { v: 'direct_supervisor', l: 'direct_supervisor' },
            { v: 'dept_manager', l: 'department_manager' },
            { v: 'admin_manager', l: 'admin_manager' },
            { v: 'transportation_manager', l: 'transportation_manager' }
        ];

        // Loaded chain per request type: { [typeId]: [{level, user_type, role_label}] | null (failed) }
        let approvalChains = {};

        const acToast = (icon, title) => Swal.fire({
            toast: true, position: 'top-end', icon, title,
            showConfirmButton: false, timer: 2200, timerProgressBar: true
        });

        function renderApprovalChainSettings() {
            fetch('./includes/approval_chain_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ action: 'get_all_request_types' })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success || !Array.isArray(data.types)) throw new Error(data.message || __('failed_to_load_approval_chain'));
                // Request types handled elsewhere (their own approval logic) are not shown here
                const skipRequestTypes = ['smart_request', 'general_request'];
                renderApprovalChainUI(data.types.filter(type => !skipRequestTypes.includes(type.id)));
            })
            .catch(error => {
                console.error('Error loading request types:', error);
                settingsContainer.innerHTML = `<div class="tab-pane active" id="group-approval" role="tabpanel">
                    <div class="sr-notice tone-red"><i class="mdi mdi-alert-circle-outline"></i><div>${escapeHtml(error.message)}</div></div></div>`;
            });
        }

        function renderApprovalChainUI(requestTypes) {
            approvalChains = {};

            let cardsHtml = '';
            requestTypes.forEach(requestType => {
                const name = translateText(requestType.name);
                const desc = requestType.description ? translateText(requestType.description) : '';
                cardsHtml += `
                    <div class="sr-card ac-card" data-request-type="${escapeHtml(requestType.id)}" data-search="${escapeHtml((name + ' ' + desc + ' ' + requestType.id).toLowerCase())}">
                        <div class="sr-card-head">
                            <div class="ac-card-id">
                                <span class="ac-ico"><i class="mdi mdi-file-tree"></i></span>
                                <div class="ac-card-text">
                                    <div class="sr-card-title">${escapeHtml(name)}</div>
                                    ${desc ? `<div class="sr-card-sub">${escapeHtml(desc)}</div>` : ''}
                                </div>
                            </div>
                            <span id="approval-status-${escapeHtml(requestType.id)}" class="sr-pill tone-slate"><span class="sr-dot"></span>${__('loading')}</span>
                        </div>
                        <div class="sr-card-body">
                            <div id="approval-chain-${escapeHtml(requestType.id)}" class="approval-chain-container">
                                <div class="ac-loading"><span class="spinner-border spinner-border-sm" role="status"></span> ${__('loading')}</div>
                            </div>
                        </div>
                        <div class="ac-card-foot">
                            <span class="ac-hint"><i class="mdi mdi-drag-vertical"></i> ${__('drag_to_reorder', 'Drag to reorder')}</span>
                            <button type="button" class="sr-btn sr-btn-sm add-approver-btn" data-request-type="${escapeHtml(requestType.id)}" data-name="${escapeHtml(name)}">
                                <i class="mdi mdi-account-plus"></i> ${__('add_approver')}
                            </button>
                        </div>
                    </div>`;
            });

            settingsContainer.innerHTML = `
                <div class="tab-pane active" id="group-approval" role="tabpanel">
                    <div class="ac-head">
                        <div>
                            <h5 class="ac-title"><i class="mdi mdi-sitemap"></i> ${__('approval_chain_configuration')}</h5>
                            <p class="ac-sub">${__('configure_approval_workflow')}</p>
                        </div>
                        <div class="ac-head-actions">
                            <div class="sr-search ac-search">
                                <i class="mdi mdi-magnify"></i>
                                <input type="search" id="acFilter" placeholder="${__('search')}..." autocomplete="off" aria-label="${__('search')}">
                            </div>
                            <button type="button" class="sr-btn sr-btn-success sr-btn-sm" id="btn-add-request-type"><i class="mdi mdi-plus"></i> ${__('add_new_request_type')}</button>
                        </div>
                    </div>
                    <div class="ac-stats">
                        <div class="ac-stat"><span class="ac-stat-ico tone-indigo"><i class="mdi mdi-file-tree"></i></span><div><span class="ac-stat-val" id="acStatTypes">${requestTypes.length}</span><span class="ac-stat-lbl">${__('request_types', 'Request types')}</span></div></div>
                        <div class="ac-stat"><span class="ac-stat-ico tone-green"><i class="mdi mdi-account-check"></i></span><div><span class="ac-stat-val" id="acStatSteps">-</span><span class="ac-stat-lbl">${__('approval_steps', 'Approval steps')}</span></div></div>
                        <div class="ac-stat"><span class="ac-stat-ico tone-amber"><i class="mdi mdi-alert-outline"></i></span><div><span class="ac-stat-val" id="acStatEmpty">-</span><span class="ac-stat-lbl">${__('not_configured', 'Not configured')}</span></div></div>
                    </div>
                    <div class="ac-grid" id="acGrid">${cardsHtml}</div>
                    <div class="sr-empty" id="acNoResults" style="display:none"><i class="mdi mdi-magnify"></i>${__('no_results_found', 'No results found')}</div>
                    ${requestTypes.length ? '' : `<div class="sr-empty"><i class="mdi mdi-file-tree"></i>${__('no_data_available', 'No data available')}</div>`}
                </div>`;

            // Load approval chains one at a time (parallel fetches trip the
            // per-IP concurrency limit in db.php and fail with a 429)
            (async () => {
                for (const requestType of requestTypes) {
                    await loadApprovalChain(requestType.id);
                }
            })();

            settingsContainer.querySelectorAll('.add-approver-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    showAddApproverModal(this.dataset.requestType, this.dataset.name);
                });
            });

            const btnAddRequestType = document.getElementById('btn-add-request-type');
            if (btnAddRequestType) btnAddRequestType.addEventListener('click', showAddNewRequestTypeModal);

            const filter = document.getElementById('acFilter');
            if (filter) {
                // Inside #settingsForm - Enter must not submit the settings form
                filter.addEventListener('keydown', e => { if (e.key === 'Enter') e.preventDefault(); });
                filter.addEventListener('input', function() {
                    const q = this.value.trim().toLowerCase();
                    let shown = 0;
                    settingsContainer.querySelectorAll('.ac-card').forEach(card => {
                        const hit = !q || card.dataset.search.includes(q);
                        card.style.display = hit ? '' : 'none';
                        if (hit) shown++;
                    });
                    document.getElementById('acNoResults').style.display = (requestTypes.length && !shown) ? '' : 'none';
                });
            }
        }

        function updateApprovalStats() {
            const loaded = Object.values(approvalChains).filter(Array.isArray);
            const stepsEl = document.getElementById('acStatSteps');
            const emptyEl = document.getElementById('acStatEmpty');
            if (stepsEl) stepsEl.textContent = loaded.reduce((sum, chain) => sum + chain.length, 0);
            if (emptyEl) emptyEl.textContent = loaded.filter(chain => chain.length === 0).length;
        }

        function setApprovalStatus(requestType, tone, text) {
            const pill = document.getElementById(`approval-status-${requestType}`);
            if (pill) {
                pill.className = `sr-pill tone-${tone}`;
                pill.innerHTML = `<span class="sr-dot"></span>${escapeHtml(text)}`;
            }
        }

        async function loadApprovalChain(requestType) {
            const container = document.getElementById(`approval-chain-${requestType}`);
            if (!container) return;
            try {
                const response = await fetch('./includes/approval_chain_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'get_approval_chain',
                        request_type: requestType
                    })
                });

                if (!response.ok) throw new Error('' + __('failed_to_load_approval_chain') + '');
                const data = await response.json();
                const chain = (data.success && Array.isArray(data.chain)) ? data.chain : [];
                approvalChains[requestType] = chain;
                updateApprovalStats();

                if (chain.length === 0) {
                    setApprovalStatus(requestType, 'amber', __('not_configured', 'Not configured'));
                    container.innerHTML = `<div class="ac-empty"><i class="mdi mdi-playlist-check"></i><span>${__('no_approval_steps_configured_yet')}</span></div>`;
                    return;
                }

                setApprovalStatus(requestType, 'indigo', `${chain.length} ${chain.length === 1 ? __('level') : __('levels', 'Levels')}`);

                let chainHtml = '<ol class="approval-steps ac-steps">';
                chain.forEach((step, index) => {
                    const isFinal = index === chain.length - 1;
                    const label = translateText(step.role_label);
                    chainHtml += `
                        <li class="approval-step ac-step${isFinal ? ' is-final' : ''}" draggable="true" data-level="${escapeHtml(step.level)}" data-role="${escapeHtml(step.user_type)}">
                            <span class="ac-handle" title="${__('drag_to_reorder', 'Drag to reorder')}"><i class="mdi mdi-drag-vertical"></i></span>
                            <span class="ac-num">${isFinal ? '<i class="mdi mdi-flag-checkered"></i>' : escapeHtml(step.level)}</span>
                            <div class="ac-step-main">
                                <div class="ac-step-role">${escapeHtml(label)}</div>
                                <div class="ac-step-sub">${__('level')} ${escapeHtml(step.level)}${isFinal ? ` &middot; <span class="ac-final">${__('final_approval', 'Final approval')}</span>` : ''}</div>
                            </div>
                            <button type="button" class="sr-btn sr-btn-ghost sr-btn-sm sr-btn-icon ac-remove remove-approver-btn" data-request-type="${escapeHtml(requestType)}" data-level="${escapeHtml(step.level)}" data-label="${escapeHtml(label)}" title="${__('remove_approval_step')}">
                                <i class="mdi mdi-delete"></i>
                            </button>
                        </li>`;
                });
                chainHtml += '</ol>';
                container.innerHTML = chainHtml;

                container.querySelectorAll('.remove-approver-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        removeApprovalStep(this.dataset.requestType, this.dataset.level, this.dataset.label);
                    });
                });

                enableApprovalDragReorder(container, requestType);

            } catch (error) {
                console.error('Error loading approval chain:', error);
                approvalChains[requestType] = null;
                setApprovalStatus(requestType, 'red', __('error'));
                container.innerHTML = `<div class="sr-notice tone-red ac-error"><i class="mdi mdi-alert-circle-outline"></i><div>${escapeHtml(error.message)}</div></div>`;
            }
        }

        // Native HTML5 drag-and-drop reorder for the approval-step rows: reorders
        // the DOM live as you drag over other rows, then persists via
        // 'update_approval_order' (approval_chain_handler.php) and reloads for
        // fresh level numbers on drop.
        function enableApprovalDragReorder(container, requestType) {
            const stepsWrap = container.querySelector('.approval-steps');
            if (!stepsWrap) return;
            let draggedEl = null;
            let startOrder = '';
            const currentOrder = () => [...stepsWrap.querySelectorAll('.approval-step')].map(el => el.dataset.role).join(',');

            const getDragAfterElement = (y) => {
                const els = [...stepsWrap.querySelectorAll('.approval-step:not(.dragging)')];
                return els.reduce((closest, child) => {
                    const box = child.getBoundingClientRect();
                    const offset = y - box.top - box.height / 2;
                    if (offset < 0 && offset > closest.offset) {
                        return { offset, element: child };
                    }
                    return closest;
                }, { offset: -Infinity, element: null }).element;
            };

            stepsWrap.querySelectorAll('.approval-step').forEach(step => {
                step.addEventListener('dragstart', () => {
                    draggedEl = step;
                    startOrder = currentOrder();
                    stepsWrap.classList.add('is-sorting');
                    // Deferred so the drag ghost image is captured before the class changes it.
                    setTimeout(() => step.classList.add('dragging'), 0);
                });
                step.addEventListener('dragend', () => {
                    step.classList.remove('dragging');
                    stepsWrap.classList.remove('is-sorting');
                    if (draggedEl) {
                        draggedEl = null;
                        // Dropped back where it started - nothing to save
                        if (currentOrder() !== startOrder) persistApprovalOrder(requestType, stepsWrap);
                    }
                });
            });

            stepsWrap.addEventListener('dragover', (e) => {
                e.preventDefault();
                if (!draggedEl) return;
                const afterElement = getDragAfterElement(e.clientY);
                if (afterElement == null) {
                    stepsWrap.appendChild(draggedEl);
                } else {
                    stepsWrap.insertBefore(draggedEl, afterElement);
                }
            });
        }

        async function persistApprovalOrder(requestType, stepsWrap) {
            const order = [...stepsWrap.querySelectorAll('.approval-step')].map(el => el.dataset.role);
            const params = new URLSearchParams({ action: 'update_approval_order', request_type: requestType });
            order.forEach(userType => params.append('order[]', userType));

            try {
                const response = await fetch('./includes/approval_chain_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: params
                });
                const data = await response.json();
                if (!data.success) throw new Error(data.message || '' + __('could_not_save_settings') + '');
                acToast('success', __('order_saved', 'Order saved'));
                await loadApprovalChain(requestType);
            } catch (error) {
                Swal.fire({ title: __('error'), text: error.message, icon: 'error', customClass: { popup: 'sr-addline-popup sr-page' } });
                await loadApprovalChain(requestType);
            }
        }

        function showAddApproverModal(requestType, typeName) {
            const chain = Array.isArray(approvalChains[requestType]) ? approvalChains[requestType] : [];
            const used = chain.map(step => step.user_type);
            const options = APPROVER_ROLES.map(role => {
                const isUsed = used.includes(role.v);
                return `<option value="${role.v}"${isUsed ? ' disabled' : ''}>${escapeHtml(__(role.l))}${isUsed ? ` (${__('already_in_chain', 'already in chain')})` : ''}</option>`;
            }).join('');
            const chainPreview = chain.length
                ? chain.map(step => `<span class="sr-chip">${escapeHtml(step.level)}. ${escapeHtml(translateText(step.role_label))}</span>`).join('<i class="mdi mdi-chevron-right ac-arrow"></i>')
                : `<span class="ac-muted">${__('no_approval_steps_configured_yet')}</span>`;

            Swal.fire({
                title: __('add_approver'),
                html: `
                    <form class="sr-form" onsubmit="return false">
                        <div class="sr-fsec">
                            <div class="sr-fsec-head"><span><i class="mdi mdi-file-tree"></i> ${escapeHtml(typeName || requestType)}</span><span class="sr-chip">${__('level')} ${chain.length + 1}</span></div>
                            <div class="sr-fgrid">
                                <div class="sr-fcol c-12">
                                    <label for="approver-role">${__('select_approver_role')} <span class="text-danger">*</span></label>
                                    <select id="approver-role" class="form-control">
                                        <option value="">-- ${__('select_role')} --</option>
                                        ${options}
                                    </select>
                                    <small class="sr-fhint">${__('approver_added_at_end_hint', 'The approver is added at the end of the chain; drag the steps afterwards to change the order.')}</small>
                                </div>
                                <div class="sr-fcol c-12">
                                    <label>${__('approval_steps_in_order')}</label>
                                    <div class="ac-preview">${chainPreview}</div>
                                </div>
                            </div>
                        </div>
                    </form>`,
                width: '560px',
                allowOutsideClick: false,
                showCancelButton: true,
                confirmButtonText: `<i class="mdi mdi-account-plus"></i> ${__('add')}`,
                cancelButtonText: __('cancel'),
                showLoaderOnConfirm: true,
                customClass: { popup: 'sr-addline-popup sr-page' },
                didOpen: () => {
                    const sel = document.getElementById('approver-role');
                    sel.addEventListener('change', () => sel.classList.remove('is-invalid'));
                },
                preConfirm: async () => {
                    const sel = document.getElementById('approver-role');
                    if (!sel.value) {
                        sel.classList.add('is-invalid');
                        Swal.showValidationMessage(__('please_select_a_role'));
                        return false;
                    }
                    return await addApprovalStep(requestType, sel.value);
                }
            }).then(result => {
                if (result.isConfirmed && result.value) {
                    acToast('success', __('approval_step_added_successfully'));
                    loadApprovalChain(requestType);
                }
            });
        }

        // Resolves true on success; shows the error inside the open popup otherwise.
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

                if (!response.ok) throw new Error('' + __('failed_to_add_approval_step') + '');
                const data = await response.json();
                if (!data.success) throw new Error(data.message || '' + __('failed_to_add_approval_step') + '');
                return true;
            } catch (error) {
                Swal.showValidationMessage(error.message);
                return false;
            }
        }

        async function removeApprovalStep(requestType, level, label) {
            const result = await Swal.fire({
                title: __('remove_approval_step'),
                html: `<div class="sr-form"><div class="sr-notice tone-red is-compact"><i class="mdi mdi-alert-outline"></i>
                        <div><strong>${__('level')} ${escapeHtml(level)} &middot; ${escapeHtml(label || '')}</strong><br>${__('this_will_remove_this_approval_level_from_the_chain')}</div></div></div>`,
                icon: 'warning',
                width: '480px',
                allowOutsideClick: false,
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                confirmButtonText: `<i class="mdi mdi-delete"></i> ${__('yes_remove_it')}`,
                cancelButtonText: __('cancel'),
                showLoaderOnConfirm: true,
                customClass: { popup: 'sr-addline-popup sr-page' },
                preConfirm: async () => {
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
                        if (!response.ok) throw new Error('' + __('failed_to_remove_approval_step') + '');
                        const data = await response.json();
                        if (!data.success) throw new Error(data.message || '' + __('failed_to_remove_approval_step') + '');
                        return true;
                    } catch (error) {
                        Swal.showValidationMessage(error.message);
                        return false;
                    }
                }
            });

            if (result.isConfirmed && result.value) {
                acToast('success', __('approval_step_removed_successfully'));
                loadApprovalChain(requestType);
            }
        }

        async function showAddNewRequestTypeModal() {
            const result = await Swal.fire({
                title: __('add_new_request_type'),
                html: `
                    <form class="sr-form" id="acNewTypeForm" onsubmit="return false">
                        <div class="sr-fsec">
                            <div class="sr-fsec-head"><span><i class="mdi mdi-file-tree"></i> ${__('request_type', 'Request type')}</span></div>
                            <div class="sr-fgrid">
                                <div class="sr-fcol c-6">
                                    <label for="new-request-type-id">${__('request_type_id')} <span class="text-danger">*</span></label>
                                    <input type="text" id="new-request-type-id" class="form-control sr-mono" autocomplete="off" placeholder="${__('e.g., travel_request, business_trip')}">
                                    <small class="sr-fhint">${__('use_lowercase_letters_and_underscores_only')}</small>
                                </div>
                                <div class="sr-fcol c-6">
                                    <label for="new-request-type-name">${__('request_type_name')} <span class="text-danger">*</span></label>
                                    <input type="text" id="new-request-type-name" class="form-control" autocomplete="off" placeholder="${__('e.g., Travel Request')}">
                                </div>
                                <div class="sr-fcol c-12">
                                    <label for="new-main-table-name">${__('main_table_name')} <small class="text-muted">(${__('optional')})</small></label>
                                    <input type="text" id="new-main-table-name" class="form-control sr-mono" autocomplete="off" placeholder="${__('e.g., travel_requests')}">
                                </div>
                                <div class="sr-fcol c-12">
                                    <label for="new-request-type-description">${__('description')}</label>
                                    <textarea id="new-request-type-description" class="form-control" rows="2" placeholder="${__('brief_description_of_this_request_type')}"></textarea>
                                </div>
                            </div>
                        </div>
                    </form>`,
                width: '640px',
                allowOutsideClick: false,
                showCancelButton: true,
                confirmButtonText: `<i class="mdi mdi-plus"></i> ${__('create')}`,
                cancelButtonText: __('cancel'),
                customClass: { popup: 'sr-addline-popup sr-page' },
                didOpen: () => {
                    const form = document.getElementById('acNewTypeForm');
                    form.addEventListener('input', e => e.target.classList.remove('is-invalid'));
                    // Type the ID the way it must be stored
                    const idInput = document.getElementById('new-request-type-id');
                    idInput.addEventListener('input', () => { idInput.value = idInput.value.toLowerCase().replace(/[\s-]+/g, '_'); });
                    idInput.focus();
                },
                preConfirm: () => {
                    const idEl = document.getElementById('new-request-type-id');
                    const nameEl = document.getElementById('new-request-type-name');
                    const id = idEl.value.trim().toLowerCase();
                    const name = nameEl.value.trim();
                    const mainTable = document.getElementById('new-main-table-name').value.trim();
                    const description = document.getElementById('new-request-type-description').value.trim();

                    const fail = (el, msg) => { el.classList.add('is-invalid'); el.focus(); Swal.showValidationMessage(msg); return false; };
                    if (!id) return fail(idEl, __('request_type_id_is_required'));
                    if (!/^[a-z_]+$/.test(id)) return fail(idEl, __('request_type_id_must_contain_only_lowercase_letters_and_underscores'));
                    if (!name) return fail(nameEl, __('request_type_name_is_required'));
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
                    Swal.fire({
                        title: __('Created!'),
                        text: `${__('New request type')} "${requestTypeName}" ${__('has been added successfully. You can now configure its approval chain.')}`,
                        icon: 'success',
                        allowOutsideClick: false,
                        customClass: { popup: 'sr-addline-popup sr-page' }
                    }).then(() => {
                        renderApprovalChainSettings();
                    });
                } else {
                    throw new Error(data.message || '' + __('Failed to create request type') + '');
                }
            } catch (error) {
                Swal.fire({ title: __('Error!'), text: error.message, icon: 'error', customClass: { popup: 'sr-addline-popup sr-page' } });
            }
        }

        /**
         * =================================================================
         * == ASSET CLEARANCE HANDLERS SETTINGS
         * == Lets an administrator pre-assign, per asset type (Laptop, Mobile,
         * == SIM, Car...), who clears its return during vacation approval -
         * == used by processAssetKeepReturnDecision in leaveHandler.php.
         * =================================================================
         */
        async function renderAssetClearanceSettings() {
            settingsContainer.innerHTML = `
                <div id="group-asset_clearance" class="tab-pane active">
                    <div class="ac-head">
                        <div>
                            <h5 class="ac-title"><i class="mdi mdi-package-variant-closed"></i> ${__('asset_clearance_handlers', 'Asset Clearance Handlers')}</h5>
                            <p class="ac-sub">${__('asset_clearance_handlers_sub', 'Who confirms each asset type is returned when an employee goes on vacation.')}</p>
                        </div>
                        <div class="ac-head-actions">
                            <div class="sr-search ac-search">
                                <i class="mdi mdi-magnify"></i>
                                <input type="search" id="acAssetFilter" placeholder="${__('search')}..." autocomplete="off" aria-label="${__('search')}">
                            </div>
                        </div>
                    </div>
                    <div class="sr-notice tone-sky"><i class="mdi mdi-information-outline"></i><div>${__('asset_clearance_handlers_note', "Assign who confirms an asset's return during vacation approval. If left unassigned, the department's manager handles it automatically (or a system administrator, if that manager is the employee's own direct manager).")}</div></div>
                    <div class="ac-stats">
                        <div class="ac-stat"><span class="ac-stat-ico tone-indigo"><i class="mdi mdi-package-variant"></i></span><div><span class="ac-stat-val" id="acaStatTypes">-</span><span class="ac-stat-lbl">${__('asset_types', 'Asset types')}</span></div></div>
                        <div class="ac-stat"><span class="ac-stat-ico tone-green"><i class="mdi mdi-account-multiple"></i></span><div><span class="ac-stat-val" id="acaStatAssigned">-</span><span class="ac-stat-lbl">${__('handlers_assigned', 'Handlers assigned')}</span></div></div>
                        <div class="ac-stat"><span class="ac-stat-ico tone-slate"><i class="mdi mdi-autorenew"></i></span><div><span class="ac-stat-val" id="acaStatAuto">-</span><span class="ac-stat-lbl">${__('automatic_dept_manager', 'Automatic (dept. manager)')}</span></div></div>
                    </div>
                    <div class="sr-card aca-card">
                        <div id="asset-clearance-table-wrapper">
                            <div class="ac-loading"><span class="spinner-border spinner-border-sm" role="status"></span> ${__('loading')}</div>
                        </div>
                    </div>
                </div>
            `;

            try {
                const response = await fetch('./includes/approval_chain_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ action: 'get_asset_clearance_handlers' })
                });
                const data = await response.json();
                if (!data.success) throw new Error(data.message || 'Failed to load asset clearance handlers');
                renderAssetClearanceTable(data.assets || []);
            } catch (error) {
                document.getElementById('asset-clearance-table-wrapper').innerHTML =
                    `<div class="sr-notice tone-red ac-error"><i class="mdi mdi-alert-circle-outline"></i><div>${escapeHtml(error.message)}</div></div>`;
            }
        }

        function renderAssetClearanceTable(assets) {
            const wrapper = document.getElementById('asset-clearance-table-wrapper');
            // Saved handler ids per asset - drives the unsaved-changes state and the stats
            const savedIds = {};
            assets.forEach(a => {
                savedIds[a.asset_id] = (Array.isArray(a.handlers) ? a.handlers : []).map(h => String(h.emp_id)).sort();
            });

            const updateStats = () => {
                const ids = Object.values(savedIds);
                const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
                set('acaStatTypes', ids.length);
                set('acaStatAssigned', ids.filter(list => list.length).length);
                set('acaStatAuto', ids.filter(list => !list.length).length);
            };
            const modePill = (count) => count
                ? `<span class="sr-pill tone-green"><span class="sr-dot"></span>${count} ${count === 1 ? __('handler', 'Handler') : __('handlers', 'Handlers')}</span>`
                : `<span class="sr-pill tone-slate"><span class="sr-dot"></span>${__('automatic', 'Automatic')}</span>`;
            const assetIcon = (name) => {
                const n = String(name || '').toLowerCase();
                if (n.includes('laptop') || n.includes('computer')) return 'mdi-laptop';
                if (n.includes('mobile') || n.includes('phone')) return 'mdi-cellphone';
                if (n.includes('sim')) return 'mdi-sim';
                if (n.includes('car') || n.includes('vehicle')) return 'mdi-car';
                return 'mdi-package-variant';
            };

            updateStats();

            if (assets.length === 0) {
                wrapper.innerHTML = `<div class="sr-empty"><i class="mdi mdi-package-variant"></i>${__('no_asset_types_configured', 'No asset types configured yet.')}</div>`;
                return;
            }

            let rowsHtml = '';
            assets.forEach(asset => {
                const name = escapeHtml(asset.asset_name);
                rowsHtml += `
                    <tr data-asset-id="${escapeHtml(asset.asset_id)}" data-dept-id="${escapeHtml(asset.clearance_dept_id || '')}" data-search="${escapeHtml(((asset.asset_name || '') + ' ' + (asset.dept_name || '')).toLowerCase())}">
                        <td>
                            <div class="aca-asset">
                                <span class="ac-ico"><i class="mdi ${assetIcon(asset.asset_name)}"></i></span>
                                <span class="sr-cell-title">${name}</span>
                            </div>
                        </td>
                        <td>${asset.dept_name ? `<span class="sr-chip">${escapeHtml(asset.dept_name)}</span>` : '<span class="ac-muted">-</span>'}</td>
                        <td class="aca-mode">${modePill(savedIds[asset.asset_id].length)}</td>
                        <td class="aca-handler">
                            <select class="form-control form-control-sm asset-handler-select" multiple></select>
                        </td>
                        <td class="aca-actions">
                            <button type="button" class="sr-btn sr-btn-sm sr-btn-ghost sr-btn-icon asset-handler-undo" title="${__('undo', 'Undo')}" disabled><i class="mdi mdi-undo"></i></button>
                            <button type="button" class="sr-btn sr-btn-sm sr-btn-primary asset-handler-save" disabled><i class="mdi mdi-content-save"></i> ${__('save', 'Save')}</button>
                        </td>
                    </tr>
                `;
            });

            wrapper.innerHTML = `
                <div class="sr-table-wrap aca-table-wrap">
                    <table class="sr-table aca-table">
                        <thead>
                            <tr>
                                <th>${__('asset_type', 'Asset Type')}</th>
                                <th>${__('department', 'Department')}</th>
                                <th>${__('mode', 'Mode')}</th>
                                <th>${__('handler', 'Handler')}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>${rowsHtml}</tbody>
                    </table>
                </div>
                <div class="sr-empty" id="acaNoResults" style="display:none"><i class="mdi mdi-magnify"></i>${__('no_results_found', 'No results found')}</div>
            `;

            const currentIds = (tr) => Array.from(tr.querySelector('.asset-handler-select').selectedOptions).map(o => o.value).sort();
            const refreshRow = (tr) => {
                const dirty = currentIds(tr).join(',') !== savedIds[tr.dataset.assetId].join(',');
                tr.classList.toggle('is-dirty', dirty);
                tr.querySelector('.asset-handler-save').disabled = !dirty;
                tr.querySelector('.asset-handler-undo').disabled = !dirty;
            };
            const fillSelect = (tr, employees) => {
                const select = tr.querySelector('.asset-handler-select');
                const assigned = savedIds[tr.dataset.assetId];
                if ($(select).data('select2')) $(select).select2('destroy');
                select.innerHTML = employees.map(emp =>
                    `<option value="${escapeHtml(emp.emp_id)}"${assigned.includes(String(emp.emp_id)) ? ' selected' : ''}>${escapeHtml(emp.name)} (${escapeHtml(emp.emp_id)})</option>`
                ).join('');
                $(select).select2({
                    width: '100%',
                    multiple: true,
                    allowClear: true,
                    placeholder: __('automatic', 'Automatic (department manager)')
                }).off('change.aca').on('change.aca', () => refreshRow(tr));
                refreshRow(tr);
            };

            // One shared employee list for every row - the handler pool is not
            // restricted by department, so there's nothing to fetch per-row.
            let employees = [];
            fetch('./includes/ajaxFile/leaveHandler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ ajaxType: 'get_asset_department_employees' })
            })
            .then(r => r.json())
            .then(res => {
                employees = Array.isArray(res.employees) ? res.employees : (Array.isArray(res.data) ? res.data : []);
                // Keep already-assigned handlers selectable even if the list no longer returns them
                assets.forEach(a => (a.handlers || []).forEach(h => {
                    if (!employees.some(e => String(e.emp_id) === String(h.emp_id))) employees.push({ emp_id: h.emp_id, name: h.name });
                }));
                wrapper.querySelectorAll('tr[data-asset-id]').forEach(tr => fillSelect(tr, employees));
            })
            .catch(() => {
                wrapper.querySelectorAll('tr[data-asset-id]').forEach(tr => fillSelect(tr, []));
            });

            wrapper.querySelectorAll('.asset-handler-undo').forEach(btn => {
                btn.addEventListener('click', () => fillSelect(btn.closest('tr'), employees));
            });

            wrapper.querySelectorAll('.asset-handler-save').forEach(btn => {
                btn.addEventListener('click', async () => {
                    const tr = btn.closest('tr');
                    const assetId = tr.dataset.assetId;
                    const select = tr.querySelector('.asset-handler-select');
                    const handlerEmpIds = Array.from(select.selectedOptions).map(o => o.value);

                    const params = new URLSearchParams({ action: 'set_asset_clearance_handler', asset_id: assetId });
                    handlerEmpIds.forEach(id => params.append('handler_emp_id[]', id));

                    btn.disabled = true;
                    btn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status"></span> ${__('save', 'Save')}`;
                    try {
                        const response = await fetch('./includes/approval_chain_handler.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: params
                        });
                        const data = await response.json();
                        if (!data.success) throw new Error(data.message || 'Failed to save');
                        savedIds[assetId] = handlerEmpIds.slice().sort();
                        tr.querySelector('.aca-mode').innerHTML = modePill(handlerEmpIds.length);
                        updateStats();
                        acToast('success', data.message || __('saved', 'Saved'));
                    } catch (error) {
                        Swal.fire({ title: __('Error!', 'Error!'), text: error.message, icon: 'error', customClass: { popup: 'sr-addline-popup sr-page' } });
                    } finally {
                        btn.innerHTML = `<i class="mdi mdi-content-save"></i> ${__('save', 'Save')}`;
                        refreshRow(tr);
                    }
                });
            });

            const filter = document.getElementById('acAssetFilter');
            if (filter) {
                // Inside #settingsForm - Enter must not submit the settings form
                filter.addEventListener('keydown', e => { if (e.key === 'Enter') e.preventDefault(); });
                filter.addEventListener('input', function() {
                    const q = this.value.trim().toLowerCase();
                    let shown = 0;
                    wrapper.querySelectorAll('tr[data-asset-id]').forEach(tr => {
                        const hit = !q || tr.dataset.search.includes(q);
                        tr.style.display = hit ? '' : 'none';
                        if (hit) shown++;
                    });
                    document.getElementById('acaNoResults').style.display = shown ? 'none' : '';
                });
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

        // --- Announcement Recipients (Email hub sub-tab) ---
        // The "Recipients" choices on send_announcement.php: a JSON array of {key, name, email}
        // kept in the hidden #setting-announcement_recipients input, which the outer
        // "Save Changes" form posts like any other setting. `key` is what a sent circular
        // stores as its recipient, so it stays fixed once a row exists.
        function announcementRecipientRowHtml(recipient) {
            const key = recipient.key || ('r' + Date.now().toString(36) + Math.random().toString(36).slice(2, 6));
            return `
                <div class="form-row announcement-recipient-row mb-2" data-key="${escapeHtml(key)}">
                    <div class="col-md-5 mb-1">
                        <input type="text" class="form-control announcement-recipient-name" placeholder="${escapeHtml(__('name', 'Name'))}" value="${escapeHtml(recipient.name)}">
                    </div>
                    <div class="col-md-6 mb-1">
                        <input type="email" class="form-control announcement-recipient-email" placeholder="email@example.com" value="${escapeHtml(recipient.email)}">
                    </div>
                    <div class="col-md-1 mb-1">
                        <button type="button" class="btn btn-outline-danger announcement-recipient-remove"><i class="mdi mdi-delete"></i></button>
                    </div>
                </div>
            `;
        }

        function renderAnnouncementRecipientsField(setting, id) {
            let recipients = [];
            try {
                const parsed = JSON.parse(setting.setting_value || '[]');
                if (Array.isArray(parsed)) {
                    recipients = parsed.filter(item => item && typeof item === 'object');
                }
            } catch (e) {
                recipients = [];
            }

            let html = `<div id="announcement-recipient-list">${recipients.map(announcementRecipientRowHtml).join('')}</div>`;
            html += `<button type="button" class="sr-btn sr-btn-sm mt-1" id="add-announcement-recipient-btn"><i class="mdi mdi-plus"></i> ${__('add_recipient', 'Add Recipient')}</button>`;
            html += `<small class="form-text text-muted">${__('announcement_recipients_hint', 'These appear as the Recipients choices on the Send Announcement page. Use a mailing-list address to reach a whole group.')}</small>`;
            html += `<input type="hidden" id="${id}" name="${setting.setting_name}" value="">`;
            return html;
        }

        function updateAnnouncementRecipientsHiddenField() {
            const hidden = document.getElementById('setting-announcement_recipients');
            if (!hidden) return;
            const recipients = [];
            document.querySelectorAll('#announcement-recipient-list .announcement-recipient-row').forEach(row => {
                const email = row.querySelector('.announcement-recipient-email').value.trim();
                if (email === '') return;
                recipients.push({
                    key: row.dataset.key,
                    name: row.querySelector('.announcement-recipient-name').value.trim() || email,
                    email: email
                });
            });
            hidden.value = JSON.stringify(recipients);
        }

        // False when a row has a name but no/invalid email - flags the bad inputs.
        function validateAnnouncementRecipients() {
            let valid = true;
            document.querySelectorAll('#announcement-recipient-list .announcement-recipient-row').forEach(row => {
                const nameInput = row.querySelector('.announcement-recipient-name');
                const emailInput = row.querySelector('.announcement-recipient-email');
                const email = emailInput.value.trim();
                const bad = (email === '' && nameInput.value.trim() !== '') || (email !== '' && !emailInput.checkValidity());
                emailInput.classList.toggle('is-invalid', bad);
                if (bad) valid = false;
            });
            return valid;
        }

        function attachAnnouncementRecipientListeners() {
            const list = document.getElementById('announcement-recipient-list');
            if (list) {
                list.addEventListener('input', updateAnnouncementRecipientsHiddenField);
                list.addEventListener('click', function(e) {
                    const removeBtn = e.target.closest('.announcement-recipient-remove');
                    if (!removeBtn) return;
                    removeBtn.closest('.announcement-recipient-row').remove();
                    updateAnnouncementRecipientsHiddenField();
                });
                document.getElementById('add-announcement-recipient-btn').addEventListener('click', function() {
                    list.insertAdjacentHTML('beforeend', announcementRecipientRowHtml({}));
                    list.lastElementChild.querySelector('.announcement-recipient-name').focus();
                });
                updateAnnouncementRecipientsHiddenField();
            }

            const allowOtherToggle = document.getElementById('announcement-allow-other-toggle');
            if (allowOtherToggle) {
                allowOtherToggle.addEventListener('change', function() {
                    document.getElementById('setting-announcement_allow_other_recipient').value = this.checked ? '1' : '0';
                });
            }
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
                        Swal.fire('' + __('Notice') + '', '' + __('At least one email field must remain') + '', 'info');
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
                    if (canAccessDepartmentsTab || canAccessSubDepartmentsTab || canAccessJobTitlesTab || canAccessLocationsTab || canAccessCompaniesTab) {
                        groupedSettings['org_structure'] = [];
                    }
                    if (canAccessRequestBlocksTab) {
                        groupedSettings['request type blocks'] = [];
                    }
                    if (canAccessLoanSettingsTab || canAccessVacationPayrollTab || canAccessOvertimeSettingsTab || canAccessDeductionSettingsTab || canAccessSalaryIncrementSettingsTab || canAccessResignationSettingsTab) {
                        groupedSettings['payroll_settings'] = [];
                    }
                    if (canAccessAttendanceConfigTab && ATTENDANCE_ON) {
                        groupedSettings['attendance_config'] = [];
                    }
                    if (canAccessScreenSettingsTab) {
                        groupedSettings['theme_config'] = [];
                    }
                    if (canAccessTempRoleTransferTab) {
                        groupedSettings['temp_role_transfer'] = [];
                    }
                    if (canAccessVacationBlackoutTab) {
                        groupedSettings['vacation_blackout_dates'] = [];
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
                                    <i class="mdi ${groupMeta(group).icon}"></i><span class="text-capitalize">${translatedGroup}</span>
                                </a>
                            </li>
                        `;
                    });
                    settingsNav.innerHTML = restrictedNavHtml;

                    const restrictedInitialGroup = (savedGroup && groups.includes(savedGroup)) ? savedGroup : groups[0];
                    if (restrictedInitialGroup) {
                        renderSettingsGroup(restrictedInitialGroup);
                    } else {
                        settingsContainer.innerHTML = '<p class="text-center">' + __('No settings found.') + '</p>';
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
                if (!response.ok) throw new Error(`${__('Network response was not ok:')} ${response.statusText}`);

                const data = await response.json();
                if (!data.success) throw new Error(data.message || '' + __('Failed to retrieve settings.') + '');

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
                // 'social' (facebook_url/twitter_url/instagram_url/linkedin_url) isn't read
                // anywhere else in the app - dead tab, hidden here. Rows are left in the DB
                // untouched in case the feature comes back.
                // 'office_hours_settings' is superseded by the default company Timetable
                // under Attendance Config - hidden the same way, rows untouched.
                // 'screen_settings' (screen_settings_by_user, a raw JSON blob) now lives only
                // as the Screen Settings sub-tab inside the Theme Config hub (renderThemeConfigHub),
                // backed by its own AJAX handler (get_screen_settings_data/update_screen_settings_map)
                // - not the generic field renderer. Drop the raw row the same way.
                const hiddenGroups = ['page_role_access', 'report_permissions', 'social', 'office_hours_settings', 'screen_settings'];
                appSettings = data.settings.filter(s => !payrollSubGroupKeys.includes(s.setting_group) && !hiddenGroups.includes(s.setting_group));
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
                if (!groupedSettings['org_structure']) {
                    groupedSettings['org_structure'] = [];
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
                if (!groupedSettings['attendance_config']) {
                    groupedSettings['attendance_config'] = [];
                }
                if (!groupedSettings['theme_config']) {
                    groupedSettings['theme_config'] = [];
                }
                if (isFullSettingsAdmin) {
                if (!groupedSettings['integrations']) {
                    groupedSettings['integrations'] = [];
                }
                if (!groupedSettings['license']) {
                    groupedSettings['license'] = [];
                }
                if (!groupedSettings['asset_clearance']) {
                    groupedSettings['asset_clearance'] = [];
                }
                }
                if (!groupedSettings['temp_role_transfer']) {
                    groupedSettings['temp_role_transfer'] = [];
                }
                if (!groupedSettings['vacation_blackout_dates']) {
                    groupedSettings['vacation_blackout_dates'] = [];
                }

                // Restore last active group from localStorage if available
                const savedGroup = localStorage.getItem('app_settings_active_group');
                // 'announcement_config' renders only as a sub-tab inside the Email hub, and
                // 'device_monitor' only inside the Attendance Config hub (see
                // renderEmailSettingsHub / renderAttendanceConfigHub) - keep both out of the
                // outer nav so they don't also show up as their own top-level tabs.
                const HUB_ONLY_GROUPS = ['announcement_config', 'announcement_recipients', 'device_monitor', 'attendance_retention', 'sync_settings', 'zk_sync_status', 'theme_config_logo'];
                // D365 Config is hidden while Microsoft Dynamics 365 is switched off (Integrations tab)
                const groups = Object.keys(groupedSettings).filter(g => !HUB_ONLY_GROUPS.includes(g) && (D365_ON || g !== 'D365_Config') && (ATTENDANCE_ON || g !== 'attendance_config')).sort(); // Sort groups alphabetically

                let navHtml = '';
                groups.forEach((group) => {
                    const isActive = (savedGroup === group);
                    const displayGroup = group.replace(/_/g, ' '); // Display with spaces instead of underscores
                    const translatedGroup = translateText(displayGroup); // Translate the group name
                    navHtml += `
                        <li class="nav-item">
                            <a class="nav-link ${isActive ? 'active' : ''}" data-toggle="pill" href="#group-${group}" role="tab" data-group="${group}">
                                <i class="mdi ${groupMeta(group).icon}"></i><span class="text-capitalize">${translatedGroup}</span>
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
                    settingsContainer.innerHTML = '<p class="text-center">' + __('No settings found.') + '</p>';
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
                Swal.fire('' + __('Error!') + '', `${__('Could not load settings:')} ${error.message}`, 'error');
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
                Swal.fire('' + __('Validation Error') + '', '' + __('Please enter valid email addresses') + '', 'error');
                return;
            }
            
            if (!validateAnnouncementRecipients()) {
                Swal.fire('' + __('Validation Error') + '', '' + __('announcement_recipient_email_required', 'Each announcement recipient needs a valid email address') + '', 'error');
                return;
            }

            // Update hidden field one more time before submission
            updateEmailListHiddenField();
            updateAnnouncementRecipientsHiddenField();
            
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
                                throw new Error(`${__('Invalid session timeout expression:')} "${value}". ${__('Please use only numbers and operators (+, -, *, /, parentheses).')}`);
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
                title: '' + __('saving') + '',
                text: '' + __('your_settings_are_being_updated') + '',
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
                        title: '' + __('saved') + '',
                        text: '' + __('your_settings_have_been_updated_successfully') + '',
                        icon: 'success',
                        confirmButtonText: '' + __('ok') + '',
                        allowOutsideClick: false
                    }).then(() => window.location.reload());
                } else {
                    Swal.fire('' + __('error') + '', result.message || '' + __('could_not_save_settings') + '', 'error');
                }

            } catch (error) {
                Swal.close();
                Swal.fire('' + __('request_failed') + '', `${__('an_error_occurred')} ${error.message}`, 'error');
            }
        });

        loadSettings();
    });
