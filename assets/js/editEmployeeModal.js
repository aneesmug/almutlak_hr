/**
 * "Edit Employee" SweetAlert2 modal - same field cards as the "Add New Employee" modal
 * (newEmployeeModal.js helpers: newEmpFieldset, newEmpTabGroup, newEmpOptionsHtml, ...), with the
 * sections as tabs in the header so one section can be opened and edited directly.
 *
 * Open with openEditEmployeeModal(empId, { tab: 'employment' }) or any element carrying
 * data-edit-employee="<emp_id>" (optional data-edit-tab). edit_employee.php now redirects to
 * view_employee.php?emp_id=X&edit=1, which opens this modal on load.
 * Loaded globally via jquery.app.js's loadResource() call.
 */

const EDIT_EMP_AJAX_URL = './includes/ajaxFile/ajaxEmployeeEditModal.php';

async function editEmpPost(params) {
    const response = await fetch(EDIT_EMP_AJAX_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
        body: new URLSearchParams(params).toString()
    });
    return response.json();
}

function editEmpTabs(perm) {
    const tabs = [
        { key: 'basic', icon: 'fa-id-card', label: __('basic_information', 'Basic Information') },
        { key: 'employment', icon: 'fa-briefcase', label: __('employment_information', 'Employment Information') },
        { key: 'other', icon: 'fa-wallet', label: __('other_information', 'Other Information') },
        { key: 'options', icon: 'fa-sliders', label: __('employee_options', 'Employee Options') }
    ];
    if (perm.request_block || perm.request_type_block) {
        tabs.push({ key: 'restrictions', icon: 'fa-ban', label: __('request_restrictions', 'Request Restrictions') });
    }
    return tabs;
}

// Yes / No option card (same radio values as the old page: 1 / 0)
function editEmpYesNo(name, title, hint, value, icon) {
    const group = newEmpTabGroup(name, [
        { value: '1', label: __('yes', 'Yes') },
        { value: '0', label: __('no', 'No') }
    ], String(value) === '1' ? '1' : '0');
    return `<div class="form-group col-md-4">
        <div class="emp-field-card ee-opt-card">
            <label class="emp-field-label">${escapeHtml(title)}</label>
            <div class="emp-field-control">
                <span class="emp-field-icon"><i class="fa ${icon}"></i></span>
                <div>${group}</div>
            </div>
            ${hint ? `<small class="ee-opt-hint">${escapeHtml(hint)}</small>` : ''}
        </div>
    </div>`;
}

function editEmpPopulate($target, items, parentKey, parentId, selectedId, emptyText) {
    const isRtl = newEmpIsRtl();
    let html;
    if (!parentId) {
        html = `<option value="">${emptyText}</option>`;
    } else {
        html = `<option value="">${__('select_option', 'Select')}</option>`;
        (items || []).filter(i => String(i[parentKey]) === String(parentId)).forEach(i => {
            const label = isRtl && i.name_ar ? i.name_ar : i.name_en;
            html += `<option value="${escapeHtml(String(i.id))}"${String(i.id) === String(selectedId) ? ' selected' : ''}>${escapeHtml(String(label ?? ''))}</option>`;
        });
    }
    $target.html(html);
    if ($target.hasClass('select2-hidden-accessible')) $target.trigger('change.select2');
}

function editEmpBuildHtml(d) {
    const e = d.employee;
    const perm = d.perm;
    const isRtl = newEmpIsRtl();
    const v = k => (e[k] === null || e[k] === undefined) ? '' : String(e[k]);
    const req = true;
    const tabs = editEmpTabs(perm);

    // T-shirt / blood: keep a stored value that isn't in the fixed list
    const listWith = (list, cur) => (cur && !list.includes(cur) ? [cur].concat(list) : list).map(x => ({ v: x }));
    const status = d.status_info;
    const statusOpts = [
        { value: '1', label: __('active', 'Active') },
        { value: '2', label: __('draft', 'Draft') },
        { value: '0', label: __('inactive', 'Inactive') }
    ];
    const supervisorOptions = `<option value="">${__('no_supervisor', 'No Direct Supervisor')}</option>` + (d.supervisors || []).map(s => {
        const dept = isRtl && s.dep_nme_ar ? s.dep_nme_ar : (s.dep_nme || '');
        return `<option value="${escapeHtml(String(s.emp_id))}"${String(s.emp_id) === v('supervisor_id') ? ' selected' : ''}>${escapeHtml(s.name)} (${escapeHtml(String(s.emp_id))}) - ${escapeHtml(s.emptype)}${dept ? ' - ' + escapeHtml(dept) : ''}</option>`;
    }).join('');
    const countries = (d.countries || []).map(c => ({ id: c.id, name: isRtl && c.name_ar ? c.name_ar : c.name }));

    const basic = `
        ${newEmpFieldset('employee_id', 'Employee ID', `<input type="text" id="eeEmpId" class="form-control readonly-data" value="${escapeHtml(v('emp_id'))}" readonly>`, 'col-md-2', 'fa-id-badge')}
        ${newEmpFieldset('employee_name', 'Employee Name', `<input type="text" id="eeName" data-field="name" class="form-control" value="${escapeHtml(v('name'))}">`, 'col-md-4', 'fa-user', req)}
        ${newEmpFieldset('iqama_id', 'Iqama id', `<input type="text" id="eeIqama" data-field="iqama" class="form-control" value="${escapeHtml(v('iqama'))}">`, 'col-md-2', 'fa-id-card', req)}
        ${newEmpFieldCard(`${__('iqama_id_expiry', 'Iqama / ID expiry')} <span class="text-danger">${__('in_gregorian', 'In Gregorian')}</span>`, 'col-md-2', 'fa-calendar-days', `<input type="text" id="eeIqamaExpG" data-field="iqama_exp_g" class="form-control" value="${escapeHtml(v('iqama_exp_g'))}">`)}
        ${newEmpFieldCard(`${__('iqama_id_expiry', 'Iqama / ID expiry')} <span class="text-danger">${__('in_hijri', 'In Hijri')}</span>`, 'col-md-2', 'fa-calendar-days', `<input type="text" id="eeIqamaExp" data-field="iqama_exp" class="form-control" value="${escapeHtml(v('iqama_exp'))}">`)}
        ${newEmpFieldset('passport_no', 'Passport no', `<input type="text" id="eePassportNumber" data-field="passport_number" class="form-control" value="${escapeHtml(v('passport_number'))}">`, 'col-md-3', 'fa-passport')}
        ${newEmpFieldset('passport_expiry', 'Passport expiry', `<input type="text" id="eePassportExp" data-field="passport_exp" class="form-control" value="${escapeHtml(v('passport_exp'))}">`, 'col-md-3', 'fa-calendar-days')}
        ${newEmpFieldset('mobile', 'Mobile', `<input type="text" id="eeMobile" data-field="mobile" class="form-control" value="${escapeHtml(v('mobile'))}">`, 'col-md-3', 'fa-phone', req)}
        ${newEmpFieldset('emergency_mobile_no_label', 'Emergency Mobile No.', `<input type="text" id="eeEmgMobile" data-field="emg_mobile" class="form-control" value="${escapeHtml(v('emg_mobile'))}">`, 'col-md-3', 'fa-phone-volume')}
        ${newEmpFieldset('emergency_contact_name_label', 'Emergency Contact Name', `<input type="text" id="eeEmgName" data-field="emg_name" class="form-control" value="${escapeHtml(v('emg_name'))}">`, 'col-md-3', 'fa-user-group')}
        ${newEmpFieldset('nationality', 'Nationality', `<select id="eeCountry" data-field="country" class="form-control new-emp-select2">${newEmpOptionsHtml(countries, 'id', 'name', 'name', true, v('country'))}</select>`, 'col-md-3', 'fa-flag', req)}
        ${newEmpFieldCard(`${__('date_of_birth', 'Date of birth')} <span class="text-danger">${__('in_gregorian', 'In Gregorian')} *</span>`, 'col-md-3', 'fa-cake-candles', `<input type="text" id="eeDob" data-field="dob" class="form-control" value="${escapeHtml(v('dob'))}">`)}
        ${newEmpFieldCard(`${__('date_of_birth', 'Date of birth')} <span class="text-danger">${__('in_hijri', 'In Hijri')} *</span>`, 'col-md-3', 'fa-cake-candles', `<input type="text" id="eeDobH" data-field="dob_h" class="form-control" value="${escapeHtml(v('dob_h'))}">`)}
        ${newEmpFieldset('t_shirt_size', 'T-Size', `<select id="eeTShirtSize" data-field="t_shirt_size" class="form-control new-emp-select2">${newEmpOptionsHtml(listWith(['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'], v('t_shirt_size')), 'v', 'v', 'v', true, v('t_shirt_size'))}</select>`, 'col-md-2', 'fa-shirt')}
        ${newEmpFieldCard(`${__('gender', 'Gender')} <span class="text-danger">*</span>`, 'col-md-3', 'fa-venus-mars', newEmpTabGroup('eeSex', [{ value: '1', label: __('male', 'Male') }, { value: '2', label: __('female', 'Female') }], v('sex')))}
        ${newEmpFieldCard(__('marital_status', 'Marital status'), 'col-md-3', 'fa-ring', newEmpTabGroup('eeMarStatus', [{ value: 'married', label: __('married', 'Married') }, { value: 'single', label: __('single', 'Single') }], v('mar_status')))}
        ${newEmpFieldset('blood_group', 'Blood group', `<select id="eeBloodType" data-field="blood_type" class="form-control new-emp-select2">${newEmpOptionsHtml(listWith(['A+', 'B+', 'O+', 'AB+', 'A-', 'B-', 'O-', 'AB-'], v('blood_type')), 'v', 'v', 'v', true, v('blood_type'))}</select>`, 'col-md-2', 'fa-droplet')}
    `;

    const employment = `
        ${newEmpFieldset('department', 'Department', `<select id="eeDept" data-field="department" class="form-control new-emp-select2">${newEmpOptionsHtml(d.departments, 'id', 'dep_nme', 'dep_nme_ar', true, v('dept'))}</select>`, 'col-md-3', 'fa-sitemap', req)}
        ${newEmpFieldset('sub_department_label', 'Sub-Department', `<select id="eeSubDeptId" data-field="sub_dept_id" class="form-control new-emp-select2"></select>`, 'col-md-3', 'fa-diagram-project')}
        ${newEmpFieldset('city_label', 'City', `<select id="eeCityId" data-field="city_id" class="form-control new-emp-select2">${newEmpOptionsHtml(d.cities, 'id', 'name_en', 'name_ar', true, v('city_id'))}</select>`, 'col-md-3', 'fa-city', req)}
        ${newEmpFieldset('location_label', 'Location', `<select id="eeLocationId" data-field="location_id" class="form-control new-emp-select2"></select>`, 'col-md-3', 'fa-location-dot', req)}
        ${newEmpFieldset('company_label', 'Company', `<select id="eeCompNo" data-field="comp_no" class="form-control new-emp-select2">${newEmpOptionsHtml(d.companies, 'comp_id', 'comp_name', 'comp_name_ar', true, v('comp_no'))}</select>`, 'col-md-3', 'fa-building', req)}
        ${d.payroll_company_html ? newEmpFieldset('payroll_company_label', 'Payroll Company (D365)', `<select id="eePayrollCompany" data-field="payroll_company" class="form-control new-emp-select2">${d.payroll_company_html}</select>`, 'col-md-3', 'fa-building-columns') : ''}
        ${newEmpFieldset('sponsorship', 'Sponsorship', `<select id="eeEmpSupType" data-field="emp_sup_type" class="form-control new-emp-select2">${newEmpOptionsHtml(d.sponsorships, 'id', 'sponsor', 'sponsor_ar', true, v('emp_sup_type'))}</select>`, 'col-md-3', 'fa-handshake', req)}
        ${newEmpFieldset('actual_job', 'Position', `<select id="eeActualJob" data-field="actual_job" class="form-control new-emp-select2">${newEmpOptionsHtml(d.jobs, 'id', 'job', 'job_ar', true, v('actual_job'))}</select>`, 'col-md-3', 'fa-briefcase', req)}
        ${newEmpFieldset('employee_type_label', 'Employee Type', `<select id="eeEmptype" data-field="emptype" class="form-control new-emp-select2"><option value="">${__('select_option', 'Select')}</option>${['Manager', 'Supervisor', 'Supporter'].map(t => `<option value="${t}"${v('emptype') === t ? ' selected' : ''}>${escapeHtml(__(t.toLowerCase() + '_option', t))}</option>`).join('')}</select>`, 'col-md-3', 'fa-user-tag', req)}
        ${newEmpFieldset('direct_supervisor', 'Direct Supervisor', `<select id="eeSupervisorId" data-field="supervisor_id" class="form-control new-emp-select2">${supervisorOptions}</select>`, 'col-md-4', 'fa-user-tie')}
        ${newEmpFieldset('joining_date', 'Joining date', `<input type="text" id="eeJoiningDate" data-field="joining_date" class="form-control" value="${escapeHtml(v('joining_date'))}">`, 'col-md-2', 'fa-calendar-check', req)}
        ${newEmpFieldset('contract_period', 'Contract period', `<select id="eeVacPeriod" data-field="vac_period" class="form-control new-emp-select2">${newEmpOptionsHtml(d.contract_periods, 'id', 'period', 'period', true, v('vac_period'))}</select>`, 'col-md-2', 'fa-file-contract', req)}
        ${newEmpFieldset('vacation_days', 'Vacation Days', `<input type="text" id="eeVacationDays" data-field="vacation_days" class="form-control" value="${escapeHtml(v('vacation_days'))}" readonly>`, 'col-md-2', 'fa-umbrella-beach', req)}
        ${newEmpFieldset('probation_period_label', 'Probation Period', `<select id="eeProbation" data-field="probation" class="form-control new-emp-select2">
            <option value="">${__('no_probation_period_option', 'No Probation Period')}</option>
            <option value="3"${parseInt(v('probation'), 10) === 3 ? ' selected' : ''}>3 ${__('months', 'Months')}</option>
            <option value="6"${parseInt(v('probation'), 10) === 6 ? ' selected' : ''}>6 ${__('months', 'Months')}</option>
        </select>`, 'col-md-2', 'fa-hourglass-half')}
    `;

    const other = `
        ${newEmpFieldset('salary', 'Salary', `<input type="text" id="eeSalary" data-field="salary" class="form-control autonumber" data-v-max="2500000" data-v-min="0" value="${escapeHtml(v('salary'))}">`, 'col-md-3', 'fa-money-bill-wave', req)}
        ${newEmpFieldset('salary_payment_type_label', 'Salary Payment type', `<select id="eePaymentType" data-field="payment_type" class="form-control new-emp-select2">
            <option value="">${__('select_option', 'Select')}</option>
            <option value="1"${v('payment_type') === '1' ? ' selected' : ''}>${__('bank_option', 'Bank')}</option>
            <option value="2"${v('payment_type') === '2' ? ' selected' : ''}>${__('cash_option', 'Cash')}</option>
            <option value="3"${v('payment_type') === '3' ? ' selected' : ''}>${__('hold_option', 'Hold')}</option>
        </select>`, 'col-md-3', 'fa-credit-card', req)}
        ${newEmpFieldset('bank_name', 'Bank name', `<select id="eeBankName" data-field="bank_name" class="form-control new-emp-select2">${newEmpOptionsHtml(d.banks, 'bnk_id', 'name', 'bank_name_ar', true, v('bank_name'))}</select>`, 'col-md-3', 'fa-building-columns', req)}
        ${newEmpFieldset('iban', 'IBAN', `<input type="text" id="eeIban" data-field="iban" class="form-control" value="${escapeHtml(v('iban'))}">`, 'col-md-3', 'fa-hashtag')}
        ${newEmpFieldset('employee_email_label', 'Employee Email', `<input type="email" id="eeEmail" data-field="email" class="form-control" value="${escapeHtml(v('email'))}">`, 'col-md-3', 'fa-envelope')}
        ${newEmpFieldset('company_email_label', 'Company Email', `<input type="email" id="eeCEmail" data-field="c_email" class="form-control" value="${escapeHtml(v('c_email'))}">`, 'col-md-3', 'fa-at')}
        ${newEmpFieldset('address', 'Address', `<input type="text" id="eeAddress" data-field="address" class="form-control" value="${escapeHtml(v('address'))}">`, 'col-md-4', 'fa-map-location-dot')}
        <div class="form-group col-md-2" id="eeGosiWrap">
            <div class="emp-field-card">
                <label class="emp-field-label">${__('gosi', 'GOSI')} <span class="text-danger">*</span></label>
                <div class="emp-field-control">
                    <span class="emp-field-icon">%</span>
                    <input type="text" id="eeGosi" data-field="gosi" class="form-control" value="${escapeHtml(v('gosi'))}">
                </div>
            </div>
        </div>
    `;

    const minDays = String(d.vac_salary_min_days || 20);
    const options = `
        ${editEmpYesNo('ee_is_overtime_eligible', __('eligible_for_overtime', 'Eligible For Overtime'), '', v('is_overtime_eligible'), 'fa-clock')}
        ${editEmpYesNo('ee_allow_emergency_vacation', __('allow_emergency_vacation', 'Allow Emergency Vacation'), __('allow_emergency_vacation_hint', 'When Yes, this employee can apply for Emergency Vacation even with a healthy balance (>1 day)'), v('allow_emergency_vacation'), 'fa-truck-medical')}
        ${editEmpYesNo('ee_allow_vacation_salary_below_min_days', __('allow_vacation_salary_below_min_days', 'Allow Vacation Salary Below Min Days'), __('allow_vacation_salary_below_min_days_hint', `When Yes, this employee can receive the vacation salary payout for a Local Vacation even with fewer than ${minDays} approved days`).replace('{days}', minDays), v('allow_vacation_salary_below_min_days'), 'fa-umbrella-beach')}
        ${editEmpYesNo('ee_other_income_enabled', __('other_income_enabled', 'Show Other Income Block'), __('other_income_enabled_hint', "When Yes, the Other Income section is available on this employee's profile. When No, it is hidden for everyone regardless of Special Access."), e.other_income_enabled === null || e.other_income_enabled === undefined ? '1' : v('other_income_enabled'), 'fa-coins')}
        ${perm.force_show_update_salary ? editEmpYesNo('ee_force_show_update_salary_btn', __('force_show_update_salary_btn', 'Force Show Update Salary Button'), __('force_show_update_salary_btn_hint', 'The Update Salary button is normally hidden until a Salary Increment request is finally approved for this employee. Set to Yes to force it visible for a special case.'), v('force_show_update_salary_btn'), 'fa-eye') : ''}
        ${perm.loan_override ? editEmpYesNo('ee_allow_loan_with_active_loan', __('allow_loan_with_active_loan', 'Allow New Loan While a Loan Is Active'), __('allow_loan_with_active_loan_hint', 'When Yes, a new loan request can be submitted for this employee even while another loan is still pending approval or being repaid.'), v('allow_loan_with_active_loan'), 'fa-hand-holding-dollar') : ''}
    `;

    const globalBlocked = d.global_blocked_types || [];
    const typeIcons = {
        smart_request: 'fa-wand-magic-sparkles', loan_request: 'fa-hand-holding-dollar', vacation_annual: 'fa-plane-departure',
        vacation_emergency: 'fa-truck-medical', vacation_local: 'fa-umbrella-beach', vacation_encashed: 'fa-money-bill-transfer',
        excuse_leave: 'fa-notes-medical', resignation_request: 'fa-door-open', rejoin_request: 'fa-person-walking-arrow-loop-left',
        general_request: 'fa-file-lines', business_trip: 'fa-suitcase-rolling', salary_increment: 'fa-arrow-trend-up'
    };
    const blockAll = v('requests_blocked') === '1';
    const blockAllOn = __('block_all_on_hint', 'This employee cannot submit any request. The per-type settings below are kept but not used while this is on.');
    const blockAllOff = __('block_all_off_hint', 'Turn on to stop this employee from submitting any request at all.');
    const restrictions = `
        ${perm.request_block ? `<div class="form-group col-12">
            <div class="ee-blockall${blockAll ? ' is-on' : ''}" id="eeBlockAll">
                <span class="ee-blockall-ico"><i class="fa fa-ban"></i></span>
                <div class="ee-blockall-text">
                    <div class="ee-blockall-title">${__('block_from_all_requests', 'Block From All Requests')}</div>
                    <div class="ee-blockall-sub" data-on="${escapeHtml(blockAllOn)}" data-off="${escapeHtml(blockAllOff)}">${escapeHtml(blockAll ? blockAllOn : blockAllOff)}</div>
                </div>
                <div class="ee-blockall-toggle">${newEmpTabGroup('ee_requests_blocked', [
                    { value: '1', label: __('yes', 'Yes') },
                    { value: '0', label: __('no', 'No') }
                ], blockAll ? '1' : '0')}</div>
            </div>
        </div>` : ''}
        ${perm.request_type_block ? `<div class="form-group col-12">
            <div class="ee-rt${blockAll ? ' is-locked' : ''}" id="eeRequestTypes">
                <div class="ee-rt-head">
                    <div>
                        <div class="ee-rt-title"><i class="fa fa-list-check"></i> ${__('block_specific_request_types', 'Block Specific Request Types')}</div>
                        <div class="ee-rt-sub">${escapeHtml(__('block_specific_request_types_short', 'Click a request type to block or allow it for this employee. Types blocked for everyone (Manage Request Type Blocks) can be allowed here as an exception.'))}</div>
                    </div>
                    <div class="ee-rt-count"><b id="eeRtBlocked">0</b> / ${(d.request_types || []).length} <span>${__('blocked', 'Blocked')}</span></div>
                </div>
                <div class="ee-rt-legend">
                    <span><i class="ee-dot is-allowed"></i>${__('allowed', 'Allowed')}</span>
                    <span><i class="ee-dot is-blocked"></i>${__('blocked_for_employee', 'Blocked for this employee')}</span>
                    <span><i class="ee-dot is-global"></i>${__('blocked_for_everyone', 'Blocked for everyone')}</span>
                    <span><i class="ee-dot is-exempt"></i>${__('exempt_allowed', 'Exception - allowed')}</span>
                </div>
                <div class="ee-rt-lock"><i class="fa fa-lock"></i> ${__('all_requests_blocked_notice', 'All requests are blocked for this employee')}</div>
                <div class="ee-rt-grid">
                    ${(d.request_types || []).map(t => {
                        const isGlobal = globalBlocked.includes(t.key);
                        return `<label class="ee-rt-item${isGlobal ? ' is-global' : ''}">
                            <input type="checkbox" class="ee-block-type" value="${escapeHtml(t.key)}"${(d.blocked_types || []).includes(t.key) ? ' checked' : ''}>
                            <span class="ee-rt-ico"><i class="fa ${typeIcons[t.key] || 'fa-file'}"></i></span>
                            <span class="ee-rt-name">${escapeHtml(t.label)}</span>
                            <span class="ee-rt-state"></span>
                        </label>`;
                    }).join('')}
                </div>
            </div>
        </div>` : ''}
    `;

    const panes = { basic, employment, other, options, restrictions };

    return `
    <div class="ee-modal text-left">
        <div class="ee-head">
            <div class="ee-head-who">
                <span class="ee-head-ico"><i class="fa fa-user-pen"></i></span>
                <div>
                    <div class="ee-head-name">${escapeHtml(v('name'))}</div>
                    <div class="ee-head-id">#${escapeHtml(v('emp_id'))}</div>
                </div>
            </div>
            <div class="ee-head-status${status.locked ? ' is-locked' : ''}" title="${status.locked ? escapeHtml(status.lock_reason) : ''}">
                <span class="ee-head-status-label"><i class="fa fa-user-check"></i> ${__('employee_status', 'Employee Status')}</span>
                ${newEmpTabGroup('eeStatus', statusOpts, status.value)}
                ${status.locked ? `<small class="ee-head-lock"><i class="fa fa-lock"></i> ${escapeHtml(status.lock_reason)}</small>` : ''}
            </div>
        </div>
        <div class="ee-status-panel" id="eeStatusPanel" hidden></div>
        <div class="ee-tabs" role="tablist">
            ${tabs.map(t => `<button type="button" class="ee-tab" role="tab" data-tab="${t.key}"><i class="fa ${t.icon}"></i><span>${escapeHtml(t.label)}</span><b class="ee-tab-dot" title="${escapeHtml(__('changed', 'Changed'))}"></b></button>`).join('')}
        </div>
        <form id="editEmpForm" onsubmit="return false;">
            ${tabs.map(t => `<div class="ee-pane card-box" data-pane="${t.key}" role="tabpanel" hidden><div class="form-row">${panes[t.key]}</div></div>`).join('')}
        </form>
    </div>`;
}

// Reads every field of the form into { column: value } (same keys edit_employee.php posted)
function editEmpCollect(perm) {
    const out = {};
    $('#editEmpForm [data-field]').each(function() {
        const key = this.getAttribute('data-field');
        out[key] = key === 'salary' ? newEmpGetNumeric(this.id) : ($(this).val() ?? '');
    });
    // Left unpicked when never stored - not sent then, so the column keeps its value
    const sex = $('input[name=eeSex]:checked').val();
    const mar = $('input[name=eeMarStatus]:checked').val();
    if (sex) out.sex = sex;
    if (mar) out.mar_status = mar;
    ['is_overtime_eligible', 'allow_emergency_vacation', 'allow_vacation_salary_below_min_days', 'other_income_enabled',
        'force_show_update_salary_btn', 'allow_loan_with_active_loan', 'requests_blocked'].forEach(k => {
        const $r = $(`input[name=ee_${k}]`);
        if ($r.length) out[k] = $r.filter(':checked').val() || '0';
    });
    if (perm.request_type_block) {
        out.blocked_request_types = JSON.stringify($('.ee-block-type:checked').map(function() { return this.value; }).get());
    }
    if (String(out.country) !== '191') delete out.gosi;
    return out;
}

// Restrictions tab: each request type shows what actually applies (blocked for everyone XOR this
// employee's checkbox), and "Block From All Requests" locks every type as blocked.
function editEmpSyncRestrictions() {
    const blockAll = $('input[name=ee_requests_blocked]:checked').val() === '1';
    const $all = $('#eeBlockAll');
    $all.toggleClass('is-on', blockAll);
    const $sub = $all.find('.ee-blockall-sub');
    $sub.text($sub.data(blockAll ? 'on' : 'off') || '');
    $('#eeRequestTypes').toggleClass('is-locked', blockAll);

    const labels = {
        allowed: ['fa-circle-check', __('allowed', 'Allowed')],
        blocked: ['fa-ban', __('blocked', 'Blocked')],
        global: ['fa-globe', __('blocked_for_everyone_short', 'Blocked (all)')],
        exempt: ['fa-shield-halved', __('exempt_allowed_short', 'Allowed (exception)')],
        locked: ['fa-lock', __('blocked', 'Blocked')]
    };
    let blocked = 0;
    $('.ee-rt-item').each(function() {
        const box = this.querySelector('.ee-block-type');
        box.disabled = blockAll; // value kept - saved as is, just not used while everything is blocked
        let state;
        if (blockAll) state = 'locked';
        else if (this.classList.contains('is-global')) state = box.checked ? 'exempt' : 'global';
        else state = box.checked ? 'blocked' : 'allowed';
        this.dataset.state = state;
        this.querySelector('.ee-rt-state').innerHTML = `<i class="fa ${labels[state][0]}"></i> ${escapeHtml(labels[state][1])}`;
        if (state === 'blocked' || state === 'global' || state === 'locked') blocked++;
    });
    $('#eeRtBlocked').text(blocked);
}

async function openEditEmployeeModal(empId, opts) {
    opts = opts || {};
    Swal.fire({
        title: __('loading', 'Loading'),
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => Swal.showLoading()
    });

    let d;
    try {
        d = await editEmpPost({ action: 'get_edit_data', emp_id: empId });
        if (d.status !== 'success') throw new Error(d.message || 'Failed to load employee.');
    } catch (error) {
        await Swal.fire(__('error', 'Error'), error.message || 'Failed to load employee.', 'error');
        return;
    }

    const perm = d.perm;
    const tabs = editEmpTabs(perm);
    const initialStatus = String(d.status_info.value);
    const rehire = d.status_info.rehire || null;
    let initial = null;

    // Required fields -> tab they live on (matches the old page's required set)
    const required = [
        ['eeName', 'basic', __('employee_name', 'Employee Name')],
        ['eeIqama', 'basic', __('iqama_id', 'Iqama id')],
        ['eeMobile', 'basic', __('mobile', 'Mobile')],
        ['eeCountry', 'basic', __('nationality', 'Nationality')],
        ['eeDob', 'basic', __('date_of_birth', 'Date of birth')],
        ['eeDobH', 'basic', __('date_of_birth', 'Date of birth')],
        ['eeDept', 'employment', __('department', 'Department')],
        ['eeCityId', 'employment', __('city_label', 'City')],
        ['eeLocationId', 'employment', __('location_label', 'Location')],
        ['eeCompNo', 'employment', __('company_label', 'Company')],
        ['eeEmpSupType', 'employment', __('sponsorship', 'Sponsorship')],
        ['eeActualJob', 'employment', __('actual_job', 'Position')],
        ['eeEmptype', 'employment', __('employee_type_label', 'Employee Type')],
        ['eeJoiningDate', 'employment', __('joining_date', 'Joining date')],
        ['eeVacPeriod', 'employment', __('contract_period', 'Contract period')],
        ['eeVacationDays', 'employment', __('vacation_days', 'Vacation Days')],
        ['eeSalary', 'other', __('salary', 'Salary')],
        ['eePaymentType', 'other', __('salary_payment_type_label', 'Salary Payment type')],
        ['eeBankName', 'other', __('bank_name', 'Bank name')]
    ];

    const showTab = key => {
        if (!tabs.some(t => t.key === key)) key = tabs[0].key;
        $('.ee-tab').each(function() {
            const on = this.dataset.tab === key;
            this.classList.toggle('active', on);
            this.setAttribute('aria-selected', on ? 'true' : 'false');
        });
        $('.ee-pane').each(function() { this.hidden = this.dataset.pane !== key; });
        try { sessionStorage.setItem('editEmpTab', key); } catch (e) { /* ignore */ }
    };

    // Dot on every tab that holds a changed field
    const paneOfKey = k => {
        if (k === 'sex' || k === 'mar_status') return 'basic';
        if (k === 'requests_blocked' || k === 'blocked_request_types') return 'restrictions';
        if ($(`input[name="ee_${k}"]`).length) return 'options';
        return $(`#editEmpForm [data-field="${k}"]`).closest('.ee-pane').data('pane') || null;
    };
    const markChanged = () => {
        if (!initial) return;
        const now = editEmpCollect(perm);
        const changedTabs = new Set();
        Object.keys(now).forEach(k => {
            if (String(now[k] ?? '') !== String(initial[k] ?? '')) changedTabs.add(paneOfKey(k));
        });
        $('.ee-tab').each(function() { this.classList.toggle('is-changed', changedTabs.has(this.dataset.tab)); });
    };

    const renderStatusPanel = () => {
        const val = $('input[name=eeStatus]:checked').val();
        const $p = $('#eeStatusPanel');
        if (val === '0' && initialStatus !== '0') {
            const today = new Date().toISOString().slice(0, 10);
            if (!$p.find('#eeInactiveReason').length) {
                $p.html(`
                    <div class="ee-status-note tone-amber"><i class="fa fa-triangle-exclamation"></i>
                        <span>${escapeHtml(__('set_inactive_warning', 'The employee and the login account will be deactivated. No End of Service settlement is created - use the End of Service page for that.'))}</span>
                    </div>
                    <div class="form-row">
                        ${newEmpFieldset('inactive_type', 'Type', `<select id="eeInactiveType" class="form-control"><option value="terminat">${escapeHtml(__('terminated', 'Terminated'))}</option><option value="expired">${escapeHtml(__('expired', 'Expired'))}</option></select>`, 'col-md-3', 'fa-user-xmark', true)}
                        ${newEmpFieldset('inactive_date', 'Inactive Date', `<input type="text" id="eeInactiveDate" class="form-control" value="${today}">`, 'col-md-3', 'fa-calendar-xmark', true)}
                        ${newEmpFieldset('reason', 'Reason', `<input type="text" id="eeInactiveReason" class="form-control">`, 'col-md-6', 'fa-comment', true)}
                    </div>`);
                if (window.AppDate) AppDate.single('#eeInactiveDate', { static: true });
            }
            $p.prop('hidden', false);
        } else if (initialStatus === '0' && val !== '0' && rehire) {
            // Left through End of Service: re-hire as a new employment from a new joining date
            if (!$p.find('#eeRehireDate').length) {
                const eosLine = __('rehire_eos_line', 'Left through End of Service on {date} (net payment {amount}).')
                    .replace('{date}', rehire.end_date || '-').replace('{amount}', Number(rehire.net_payment || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                $p.html(`
                    <div class="ee-status-note tone-green"><i class="fa fa-user-plus"></i>
                        <span><b>${escapeHtml(__('rehire_title', 'Re-hire as a new employment'))}</b><br>
                        ${escapeHtml(eosLine)}
                        ${escapeHtml(__('rehire_text', 'This record and its settlement stay as they are. A new employee record is registered from the new joining date - service, vacation and the next End of Service count from there.'))}</span>
                    </div>
                    <div class="form-row">
                        ${newEmpFieldset('new_employee_id', 'New Employee ID', `<input type="text" class="form-control readonly-data" value="${escapeHtml(String(rehire.next_emp_id || ''))}" readonly title="${escapeHtml(__('new_emp_id_hint', 'Next free number - assigned on save'))}">`, 'col-md-3', 'fa-id-badge')}
                        ${newEmpFieldset('new_joining_date', 'New Joining Date', `<input type="text" id="eeRehireDate" class="form-control" value="">`, 'col-md-4', 'fa-calendar-plus', true)}
                    </div>`);
                if (window.AppDate) {
                    const minDay = rehire.end_date ? new Date(new Date(rehire.end_date + 'T00:00:00').getTime() + 86400000).toISOString().slice(0, 10) : undefined;
                    AppDate.single('#eeRehireDate', { static: true, minDate: minDay });
                }
            }
            $p.addClass('is-rehire').prop('hidden', false);
        } else if (initialStatus === '0' && val !== '0') {
            $p.html(`<div class="ee-status-note tone-green"><i class="fa fa-user-check"></i><span>${escapeHtml(val === '1'
                ? __('reactivate_active_text', 'Status will be set to Active, the inactive type, reason and date will be cleared and the login account enabled again.')
                : __('reactivate_draft_text', 'Status will be set to Draft and the inactive type, reason and date will be cleared.'))}</span></div>`).prop('hidden', false);
        } else {
            $p.empty().removeClass('is-rehire').prop('hidden', true);
        }
    };

    let startTab = opts.tab;
    if (!startTab) {
        try { startTab = sessionStorage.getItem('editEmpTab'); } catch (e) { startTab = null; }
    }

    const result = await Swal.fire({
        title: __('edit_employee_title', 'Edit Employee'),
        html: editEmpBuildHtml(d),
        width: '90%',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        confirmButtonText: `<i class="fa fa-floppy-disk"></i> ${__('save_edit_button', 'Save')}`,
        cancelButtonText: __('cancel', 'Cancel'),
        allowOutsideClick: false,
        showLoaderOnConfirm: true,
        customClass: { popup: 'ee-popup' },
        didOpen: () => {
            const popup = Swal.getPopup();
            const e = d.employee;
            newEmpInitSelect2();

            editEmpPopulate($('#eeLocationId'), d.all_locations, 'city_id', e.city_id, e.location_id, __('select_a_city_first', 'Select a City First'));
            editEmpPopulate($('#eeSubDeptId'), d.all_sub_departments, 'department_id', e.dept, e.sub_dept_id, __('select_a_department_first', 'Select a Department First'));
            $('#eeCityId').on('change', function() { editEmpPopulate($('#eeLocationId'), d.all_locations, 'city_id', $(this).val(), '', __('select_a_city_first', 'Select a City First')); });
            $('#eeDept').on('change', function() { editEmpPopulate($('#eeSubDeptId'), d.all_sub_departments, 'department_id', $(this).val(), '', __('select_a_department_first', 'Select a Department First')); });

            // GOSI only for Saudi nationals
            const syncGosi = () => $('#eeGosiWrap').toggle(String($('#eeCountry').val()) === '191');
            $('#eeCountry').on('change', syncGosi);
            syncGosi();

            $('#eeVacPeriod').on('change', function() {
                const val = $(this).val();
                if (!val) { $('#eeVacationDays').val(''); return; }
                $.get('./includes/ContractPeriodSelect.php', { vac_period: val }, res => {
                    $('#eeVacationDays').val(res);
                    markChanged();
                });
            });

            if (window.AppDate) {
                AppDate.hijriPair('#eeIqamaExpG', '#eeIqamaExp', { static: true, onChange: markChanged });
                AppDate.hijriPair('#eeDob', '#eeDobH', { static: true, maxDate: 'today', onChange: markChanged });
                AppDate.single('#eePassportExp', { static: true, onChange: markChanged });
                AppDate.single('#eeJoiningDate', { static: true, onChange: markChanged });
            }
            newEmpApplyMasks('eeIqama', null);
            newEmpApplyIbanMask('eeIban');
            newEmpFixMaskCaret('eeIqama');
            newEmpFixMaskCaret('eeIban');
            newEmpApplyAutoNumeric();

            editEmpSyncRestrictions();
            $('input[name=ee_requests_blocked], .ee-block-type').on('change', editEmpSyncRestrictions);

            if (d.status_info.locked) $('input[name=eeStatus]').prop('disabled', true);
            $('input[name=eeStatus]').on('change', () => { Swal.resetValidationMessage(); renderStatusPanel(); });

            // Bound on the modal body (new on every open), not the reused SweetAlert2 popup
            const $modal = $(popup).find('.ee-modal');
            $modal.on('click', '.ee-tab', function() { showTab(this.dataset.tab); });
            $modal.on('input change', 'input, select', () => {
                $modal.find('.is-invalid').removeClass('is-invalid');
                markChanged();
            });

            showTab(startTab || tabs[0].key);
            initial = editEmpCollect(perm);
        },
        preConfirm: async () => {
            $('#editEmpForm .is-invalid, .ee-modal .is-invalid').removeClass('is-invalid');
            const fail = (id, tab, label) => {
                if (tab) showTab(tab);
                const $el = $('#' + id);
                $el.addClass('is-invalid');
                if ($el.hasClass('select2-hidden-accessible')) $el.next('.select2-container').find('.select2-selection').addClass('is-invalid');
                Swal.showValidationMessage(`${label} ${__('is_required', 'is required')}`);
                return false;
            };
            for (const [id, tab, label] of required) {
                if (!String($('#' + id).val() || '').trim()) return fail(id, tab, label);
            }
            if (String($('#eeCountry').val()) === '191' && !String($('#eeGosi').val() || '').trim()) {
                return fail('eeGosi', 'other', __('gosi', 'GOSI'));
            }

            const form = editEmpCollect(perm);
            if (!d.status_info.locked) {
                const st = $('input[name=eeStatus]:checked').val();
                if (st !== initialStatus) {
                    form.emp_status = st;
                    if (st === '0') {
                        const date = String($('#eeInactiveDate').val() || '').trim();
                        const reason = String($('#eeInactiveReason').val() || '').trim();
                        if (!/^\d{4}-\d{2}-\d{2}$/.test(date)) {
                            $('#eeInactiveDate').addClass('is-invalid');
                            Swal.showValidationMessage(__('please_select_date', 'Please select a date'));
                            return false;
                        }
                        if (!reason) {
                            $('#eeInactiveReason').addClass('is-invalid').trigger('focus');
                            Swal.showValidationMessage(__('please_enter_reason', 'Please enter a reason'));
                            return false;
                        }
                        form.inactive_type = $('#eeInactiveType').val();
                        form.inactive_date = date;
                        form.inactive_reason = reason;
                    } else if (initialStatus === '0' && rehire) {
                        const rd = String($('#eeRehireDate').val() || '').trim();
                        if (!/^\d{4}-\d{2}-\d{2}$/.test(rd)) {
                            $('#eeRehireDate').addClass('is-invalid');
                            Swal.showValidationMessage(__('rehire_joining_date_required', 'Enter the new joining date for the re-hire.'));
                            return false;
                        }
                        if (rehire.end_date && rd <= rehire.end_date) {
                            $('#eeRehireDate').addClass('is-invalid');
                            Swal.showValidationMessage(__('rehire_date_after_eos', 'The new joining date must be after the End of Service date ({date}).').replace('{date}', rehire.end_date));
                            return false;
                        }
                        form.rehire_joining_date = rd;
                        form.joining_date = rd;
                    }
                }
            }

            try {
                const res = await editEmpPost({ action: 'update_employee', emp_id: empId, form_data: JSON.stringify(form) });
                if (res.status !== 'success') {
                    Swal.showValidationMessage(res.message || 'Failed to save.');
                    return false;
                }
                return res;
            } catch (e) {
                Swal.showValidationMessage(e.message || 'Request failed.');
                return false;
            }
        }
    });

    if (!result.isConfirmed || !result.value) return;
    const res = result.value;
    await Swal.fire({
        icon: res.kind === 'none' ? 'info' : 'success',
        title: res.title || '',
        text: res.message || '',
        timer: res.kind === 'none' ? 1800 : (res.kind === 'rehired' ? 2600 : 1400),
        showConfirmButton: false
    });
    if (res.kind === 'none') return;
    if (res.kind === 'rehired' && res.new_emp_id) {
        window.location.href = 'view_employee.php?emp_id=' + encodeURIComponent(res.new_emp_id);
        return;
    }
    if (typeof opts.onSaved === 'function') {
        opts.onSaved(res);
    } else {
        window.location.reload();
    }
}

// ---------------------------------------------------------------------------
// Entry points
// ---------------------------------------------------------------------------
$(document).on('click', '[data-edit-employee]', function(e) {
    e.preventDefault();
    openEditEmployeeModal(String($(this).data('edit-employee')), { tab: $(this).data('edit-tab') || undefined });
});

// view_employee.php?emp_id=X&edit=1[&edit_tab=other] (edit_employee.php redirects here)
$(function() {
    const params = new URLSearchParams(window.location.search);
    if (params.get('edit') !== '1' || !params.get('emp_id')) return;
    const empId = params.get('emp_id');
    const tab = params.get('edit_tab') || undefined;
    params.delete('edit');
    params.delete('edit_tab');
    const qs = params.toString();
    window.history.replaceState(null, '', window.location.pathname + (qs ? '?' + qs : '') + window.location.hash);
    // SweetAlert2 / the shared newEmp* helpers load asynchronously - wait for both
    let tries = 0;
    (function start() {
        if (window.Swal && typeof window.newEmpFieldset === 'function') {
            openEditEmployeeModal(empId, { tab });
        } else if (tries++ < 50) {
            setTimeout(start, 100);
        }
    })();
});
