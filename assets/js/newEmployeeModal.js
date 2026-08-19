/**
 * "Add New Employee" SweetAlert2 modal - Company Employee / Man Power.
 * Triggered from the sidebar (includes/main_menu.php, #newEmployeeMenuLink).
 * Loaded globally via jquery.app.js's loadResource() call.
 */

function newEmpIsRtl() {
    return (document.documentElement.getAttribute('dir') || '').toLowerCase() === 'rtl';
}

function newEmpOptionsHtml(items, valueKey, labelEnKey, labelArKey, includeBlank, selectedValue) {
    const isRtl = newEmpIsRtl();
    let html = includeBlank ? `<option value="">${__('select_option', 'Select')}</option>` : '';
    (items || []).forEach(item => {
        const label = isRtl && item[labelArKey] ? item[labelArKey] : item[labelEnKey];
        const selected = selectedValue !== undefined && selectedValue !== null && String(item[valueKey]) === String(selectedValue) ? ' selected' : '';
        html += `<option value="${String(item[valueKey]).replace(/"/g, '&quot;')}"${selected}>${escapeHtml(String(label ?? ''))}</option>`;
    });
    return html;
}

function newEmpInitSelect2() {
    if (window.jQuery && typeof jQuery.fn.select2 === 'function') {
        jQuery('.new-emp-select2').each(function() {
            if (!jQuery(this).hasClass('select2-hidden-accessible')) {
                jQuery(this).select2({ width: '100%', dropdownParent: jQuery('.swal2-popup') });
            }
        });
    }
}

function newEmpPopulateLocations(cityId, $target, selectedId) {
    if (!cityId) {
        $target.html(`<option value="">${__('select_a_city_first', 'Select a City First')}</option>`);
    } else {
        const isRtl = newEmpIsRtl();
        let options = `<option value="">${__('select_option', 'Select')}</option>`;
        (window.NEW_EMP_FORM_DATA.all_locations || []).filter(l => String(l.city_id) === String(cityId)).forEach(loc => {
            const selected = String(loc.id) === String(selectedId) ? 'selected' : '';
            const label = isRtl ? loc.name_ar : loc.name_en;
            options += `<option value="${loc.id}" ${selected}>${escapeHtml(String(label ?? ''))}</option>`;
        });
        $target.html(options);
    }
    if ($target.hasClass('select2-hidden-accessible')) $target.trigger('change');
}

function newEmpPopulateSubDepts(departmentId, $target, selectedId) {
    if (!departmentId) {
        $target.html(`<option value="">${__('select_a_department_first', 'Select a Department First')}</option>`);
    } else {
        const isRtl = newEmpIsRtl();
        let options = `<option value="">${__('select_option', 'Select')}</option>`;
        (window.NEW_EMP_FORM_DATA.all_sub_departments || []).filter(sd => String(sd.department_id) === String(departmentId)).forEach(sd => {
            const selected = String(sd.id) === String(selectedId) ? 'selected' : '';
            const label = isRtl ? sd.name_ar : sd.name_en;
            options += `<option value="${sd.id}" ${selected}>${escapeHtml(String(label ?? ''))}</option>`;
        });
        $target.html(options);
    }
    if ($target.hasClass('select2-hidden-accessible')) $target.trigger('change');
}

function newEmpWireHijriPair(gregorianId, hijriId) {
    const $g = $('#' + gregorianId);
    const $h = $('#' + hijriId);
    if (!$g.length || !$h.length || typeof $g.datepicker !== 'function' || typeof $h.hijriDatePicker !== 'function' || typeof moment === 'undefined') {
        return;
    }
    $g.datepicker({ format: 'yyyy-mm-dd', autoclose: true, todayHighlight: true }).on('changeDate', function(e) {
        const hijriDate = moment(e.date).format('iYYYY-iMM-iDD');
        $h.val(hijriDate).hijriDatePicker('setDate', hijriDate);
    });
    $h.hijriDatePicker({ format: 'iYYYY-iMM-iDD', hijri: true, showSwitcher: false }).on('dp.change', function(e) {
        if (e.date) {
            const gregorianDate = moment(e.date.format('iYYYY-iMM-iDD'), 'iYYYY-iMM-iDD').format('YYYY-MM-DD');
            $g.val(gregorianDate).datepicker('update', gregorianDate);
        }
    });
}

function newEmpFieldset(labelKey, fallback, inputHtml, colClass, icon, required) {
    return `<div class="form-group ${colClass || 'col-md-3'}">
        <div class="emp-field-card">
            <label class="emp-field-label">${__(labelKey, fallback)}${required ? ' <span class="text-danger">*</span>' : ''}</label>
            <div class="emp-field-control">
                <span class="emp-field-icon"><i class="fa ${icon || 'fa-pen'}"></i></span>
                ${inputHtml}
            </div>
        </div>
    </div>`;
}

function newEmpWireDatepicker(id) {
    const $el = $('#' + id);
    if ($el.length && typeof $el.datepicker === 'function') {
        $el.datepicker({ format: 'yyyy-mm-dd', autoclose: true, todayHighlight: true });
    }
}

function newEmpFixMaskCaret(id) {
    const el = document.getElementById(id);
    if (!el) return;
    $(el).on('click', function() {
        const val = el.value || '';
        const underscoreIdx = val.indexOf('_');
        const targetPos = underscoreIdx === -1 ? val.length : underscoreIdx;
        setTimeout(function() {
            if (el.setSelectionRange) el.setSelectionRange(targetPos, targetPos);
        }, 0);
    });
}

function newEmpValidateRequired(fields) {
    for (const id in fields) {
        const $el = $('#' + id);
        const val = $el.length ? $el.val() : ($(`input[name=${id}]:checked`).val() || '');
        if (!val) {
            Swal.showValidationMessage(`${fields[id]} ${__('is_required', 'is required')}`);
            return false;
        }
    }
    return true;
}

function newEmpApplyMasks(iqamaId, mobileId) {
    if (window.jQuery && typeof jQuery.fn.inputmask === 'function') {
        if (iqamaId) $('#' + iqamaId).inputmask({ mask: '9999999999' });
        if (mobileId) $('#' + mobileId).inputmask({ mask: '0599999999' });
    }
}

function newEmpApplyIbanMask(ibanId) {
    if (window.jQuery && typeof jQuery.fn.inputmask === 'function' && ibanId) {
        $('#' + ibanId).inputmask({ mask: 'SA99 9999 9999 9999 9999 9999' });
    }
}

function newEmpApplyAutoNumeric() {
    if (window.jQuery && typeof jQuery.fn.autoNumeric === 'function') {
        $('.autonumber').autoNumeric('init');
    }
}

function newEmpTabGroup(name, options, selectedValue) {
    const items = options.map((opt, idx) => {
        const id = `${name}_${opt.value}`;
        const checked = selectedValue !== undefined ? String(selectedValue) === String(opt.value) : idx === 0;
        return `<input type="radio" class="emp-tab-input" name="${name}" id="${id}" value="${opt.value}" ${checked ? 'checked' : ''}><label class="emp-tab-label" for="${id}">${escapeHtml(opt.label)}</label>`;
    }).join('');
    return `<div class="emp-tab-group">${items}</div>`;
}

function newEmpFieldCard(labelHtml, colClass, icon, innerHtml) {
    return `<div class="form-group ${colClass || 'col-md-3'}">
        <div class="emp-field-card">
            <label class="emp-field-label">${labelHtml}</label>
            <div class="emp-field-control">
                <span class="emp-field-icon"><i class="fa ${icon || 'fa-pen'}"></i></span>
                ${innerHtml}
            </div>
        </div>
    </div>`;
}

// ---------------------------------------------------------------------------
// Type picker
// ---------------------------------------------------------------------------
async function openNewEmployeeTypeModal() {
    Swal.fire({
        title: __('loading', 'Loading'),
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => Swal.showLoading()
    });

    try {
        const response = await fetch('./includes/ajaxFile/ajaxEmployeeCreateModal.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
            body: new URLSearchParams({ action: 'get_form_data' }).toString()
        });
        const data = await response.json();
        Swal.close();

        if (!response.ok || data.status !== 'success') {
            throw new Error(data.message || 'Failed to load form data.');
        }
        window.NEW_EMP_FORM_DATA = data;

        let selectedType = null;
        await Swal.fire({
            title: __('add_new_employee_modal_title', 'Add New Employee'),
            html: `
                <div class="row text-left">
                    <div class="col-6">
                        <div class="card-box new-emp-type-card" id="newEmpTypeCompany" style="cursor:pointer;background:#727cf5;color:#fff;padding:24px;text-align:center;border-radius:8px;">
                            <i class="fa fa-building" style="font-size:28px;"></i>
                            <h5 class="mt-2 mb-0" style="color:#fff;">${__('almutlak_co_employee', 'Company Employee')}</h5>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card-box new-emp-type-card" id="newEmpTypeManPower" style="cursor:pointer;background:#7a6fbe;color:#fff;padding:24px;text-align:center;border-radius:8px;">
                            <i class="fa fa-people-carry" style="font-size:28px;"></i>
                            <h5 class="mt-2 mb-0" style="color:#fff;">${__('manpower_employee', 'Man Power')}</h5>
                        </div>
                    </div>
                </div>
            `,
            width: '40%',
            showConfirmButton: false,
            showCancelButton: true,
            cancelButtonText: __('cancel', 'Cancel'),
            didOpen: () => {
                document.getElementById('newEmpTypeCompany').addEventListener('click', () => { selectedType = 'company'; Swal.close(); });
                document.getElementById('newEmpTypeManPower').addEventListener('click', () => { selectedType = 'man_power'; Swal.close(); });
            }
        });

        if (selectedType === 'company') {
            openCompanyEmployeeModal(data);
        } else if (selectedType === 'man_power') {
            openManPowerEmployeeModal(data);
        }
    } catch (error) {
        Swal.close();
        await Swal.fire(__('error', 'Error'), error.message || 'Failed to load employee form.', 'error');
    }
}

// ---------------------------------------------------------------------------
// Company Employee - 3-step wizard (Basic / Employment / Other Information)
// ---------------------------------------------------------------------------
function openCompanyEmployeeModal(data) {
    openCompanyStep1(data, {});
}

function newEmpCollectBasicInfo() {
    return {
        name: $('#ceName').val(),
        emp_id: $('#ceEmpId').val(),
        iqama: $('#ceIqama').val(),
        iqama_exp_g: $('#ceIqamaExpG').val(),
        iqama_exp: $('#ceIqamaExp').val(),
        passport_number: $('#cePassportNumber').val(),
        passport_exp: $('#cePassportExp').val(),
        mobile: $('#ceMobile').val(),
        emg_mobile: $('#ceEmgMobile').val(),
        emg_name: $('#ceEmgName').val(),
        country: $('#ceCountry').val(),
        dob: $('#ceDob').val(),
        dob_h: $('#ceDobH').val(),
        t_shirt_size: $('#ceTShirtSize').val(),
        sex: $('input[name=ceSex]:checked').val(),
        mar_status: $('input[name=ceMarStatus]:checked').val(),
        blood_type: $('#ceBloodType').val()
    };
}

function openCompanyStep1(data, w) {
    const html = `
    <form id="newCompEmpFormStep1" class="text-left">
        <div class="card-box">
        <div class="form-row">
            ${newEmpFieldset('employee_name', 'Employee Name', `<input type="text" id="ceName" class="form-control" value="${escapeHtml(w.name || '')}" required>`, 'col-md-4', 'fa-user', true)}
            ${newEmpFieldset('employee_id', 'Employee ID', `<input type="text" id="ceEmpId" class="form-control autonumber" data-v-max="9999" data-v-min="0" value="${escapeHtml(w.emp_id || data.next_emp_id)}" required>`, 'col-md-2', 'fa-id-badge', true)}
            ${newEmpFieldset('iqama_id', 'Iqama id', `<input type="text" id="ceIqama" class="form-control" value="${escapeHtml(w.iqama || '')}" required>`, 'col-md-2', 'fa-id-card', true)}
            ${newEmpFieldCard(`${__('iqama_id_expiry', 'Iqama / ID expiry')} <span class="text-danger">${__('in_gregorian', 'In Gregorian')} *</span>`, 'col-md-2', 'fa-calendar-alt', `<input type="text" id="ceIqamaExpG" class="form-control" value="${escapeHtml(w.iqama_exp_g || '')}" required>`)}
            ${newEmpFieldCard(`${__('iqama_id_expiry', 'Iqama / ID expiry')} <span class="text-danger">${__('in_hijri', 'In Hijri')} *</span>`, 'col-md-2', 'fa-calendar-alt', `<input type="text" id="ceIqamaExp" class="form-control" value="${escapeHtml(w.iqama_exp || '')}" required>`)}
            ${newEmpFieldset('passport_no', 'Passport no', `<input type="text" id="cePassportNumber" class="form-control" value="${escapeHtml(w.passport_number || '')}">`, 'col-md-3', 'fa-passport')}
            ${newEmpFieldset('passport_expiry', 'Passport expiry', `<input type="text" id="cePassportExp" class="form-control" value="${escapeHtml(w.passport_exp || '')}">`, 'col-md-3', 'fa-calendar-alt')}
            ${newEmpFieldset('mobile', 'Mobile', `<input type="text" id="ceMobile" class="form-control" value="${escapeHtml(w.mobile || '')}" required>`, 'col-md-3', 'fa-phone', true)}
            ${newEmpFieldset('emergency_mobile_no_label', 'Emergency Mobile No.', `<input type="text" id="ceEmgMobile" class="form-control" value="${escapeHtml(w.emg_mobile || '')}" required>`, 'col-md-3', 'fa-phone-alt', true)}
            ${newEmpFieldset('emergency_contact_name_label', 'Emergency Contact Name', `<input type="text" id="ceEmgName" class="form-control" value="${escapeHtml(w.emg_name || '')}" required>`, 'col-md-3', 'fa-user-friends', true)}
            ${newEmpFieldset('nationality', 'Nationality', `<select id="ceCountry" class="form-control new-emp-select2" required>${newEmpOptionsHtml(data.countries, 'id', 'name', 'name', true, w.country)}</select>`, 'col-md-3', 'fa-flag', true)}
            ${newEmpFieldCard(`${__('date_of_birth', 'Date of birth')} <span class="text-danger">${__('in_gregorian', 'In Gregorian')} *</span>`, 'col-md-3', 'fa-calendar-alt', `<input type="text" id="ceDob" class="form-control" value="${escapeHtml(w.dob || '')}" required>`)}
            ${newEmpFieldCard(`${__('date_of_birth', 'Date of birth')} <span class="text-danger">${__('in_hijri', 'In Hijri')} *</span>`, 'col-md-3', 'fa-calendar-alt', `<input type="text" id="ceDobH" class="form-control" value="${escapeHtml(w.dob_h || '')}" required>`)}
            ${newEmpFieldset('t_shirt_size', 'T-Size', `<select id="ceTShirtSize" class="form-control new-emp-select2">${newEmpOptionsHtml(['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'].map(s => ({ v: s })), 'v', 'v', 'v', true, w.t_shirt_size)}</select>`, 'col-md-3', 'fa-tshirt')}
            ${newEmpFieldCard(`${__('gender', 'Gender')} <span class="text-danger">*</span>`, 'col-md-3', 'fa-venus-mars', newEmpTabGroup('ceSex', [{ value: '1', label: __('male', 'Male') }, { value: '2', label: __('female', 'Female') }], w.sex === '2' ? '2' : '1'))}
            ${newEmpFieldCard(__('marital_status', 'Marital status'), 'col-md-3', 'fa-ring', newEmpTabGroup('ceMarStatus', [{ value: 'married', label: __('married', 'Married') }, { value: 'single', label: __('single', 'Single') }], w.mar_status === 'married' ? 'married' : 'single'))}
            ${newEmpFieldset('blood_group', 'Blood group', `<select id="ceBloodType" class="form-control new-emp-select2">${newEmpOptionsHtml(['A+', 'B+', 'O+', 'AB+', 'A-', 'B-', 'O-', 'AB-'].map(b => ({ v: b })), 'v', 'v', 'v', true, w.blood_type)}</select>`, 'col-md-3', 'fa-tint')}
        </div>
        </div>
    </form>`;

    Swal.fire({
        title: __('almutlak_co_employee', 'Company Employee') + ' - ' + __('basic_information', 'Basic Information') + ' (1/3)',
        html,
        width: '90%',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        confirmButtonText: __('next', 'Next'),
        cancelButtonText: __('cancel', 'Cancel'),
        allowOutsideClick: false,
        didOpen: () => {
            newEmpInitSelect2();
            newEmpWireHijriPair('ceIqamaExpG', 'ceIqamaExp');
            newEmpWireHijriPair('ceDob', 'ceDobH');
            newEmpApplyMasks('ceIqama', 'ceMobile');
            newEmpApplyAutoNumeric();
            newEmpWireDatepicker('cePassportExp');
            newEmpFixMaskCaret('ceIqama');
            newEmpFixMaskCaret('ceMobile');
        },
        preConfirm: () => {
            if (!newEmpValidateRequired({
                ceName: __('employee_name', 'Employee Name'),
                ceEmpId: __('employee_id', 'Employee ID'),
                ceIqama: __('iqama_id', 'Iqama id'),
                ceIqamaExpG: __('iqama_id_expiry', 'Iqama / ID expiry'),
                ceIqamaExp: __('iqama_id_expiry', 'Iqama / ID expiry'),
                ceMobile: __('mobile', 'Mobile'),
                ceEmgMobile: __('emergency_mobile_no_label', 'Emergency Mobile No.'),
                ceEmgName: __('emergency_contact_name_label', 'Emergency Contact Name'),
                ceCountry: __('nationality', 'Nationality'),
                ceDob: __('date_of_birth', 'Date of birth'),
                ceDobH: __('date_of_birth', 'Date of birth')
            })) return false;
            return newEmpCollectBasicInfo();
        }
    }).then((result) => {
        if (!result.isConfirmed) return;
        Object.assign(w, result.value);
        openCompanyStep2(data, w);
    });
}

function newEmpCollectEmploymentInfo() {
    return {
        department: $('#ceDept').val(),
        city_id: $('#ceCityId').val(),
        location_id: $('#ceLocationId').val(),
        sub_dept_id: $('#ceSubDeptId').val(),
        emptype: $('#ceEmptype').val(),
        supervisor_id: $('#ceSupervisorId').val(),
        joining_date: $('#ceJoiningDate').val(),
        emp_sup_type: $('#ceEmpSupType').val(),
        comp_no: $('#ceCompNo').val(),
        actual_Job: $('#ceActualJob').val(),
        vac_period: $('#ceVacPeriod').val(),
        vacation_days: $('#ceVacationDays').val(),
        probation: $('#ceProbation').val()
    };
}

function openCompanyStep2(data, w) {
    const emptypeOptions = ['Manager', 'Supervisor', 'Supporter'].map(v => `<option value="${v}" ${w.emptype === v ? 'selected' : ''}>${v}</option>`).join('');

    const html = `
    <form id="newCompEmpFormStep2" class="text-left">
        <div class="card-box">
        <div class="form-row">
            ${newEmpFieldset('department', 'Department', `<select id="ceDept" class="form-control new-emp-select2" required>${newEmpOptionsHtml(data.departments, 'id', 'dep_nme', 'dep_nme_ar', true, w.department)}</select>`, 'col-md-3', 'fa-sitemap', true)}
            ${newEmpFieldset('city_label', 'City', `<select id="ceCityId" class="form-control new-emp-select2" required>${newEmpOptionsHtml(data.cities, 'id', 'name_en', 'name_ar', true, w.city_id)}</select>`, 'col-md-3', 'fa-city', true)}
            ${newEmpFieldset('location_label', 'Location', `<select id="ceLocationId" class="form-control new-emp-select2" required><option value="">${__('select_a_city_first', 'Select a City First')}</option></select>`, 'col-md-3', 'fa-map-marker-alt', true)}
            ${newEmpFieldset('sub_department_label', 'Sub-Department', `<select id="ceSubDeptId" class="form-control new-emp-select2"><option value="">${__('select_a_department_first', 'Select a Department First')}</option></select>`, 'col-md-3', 'fa-sitemap')}
            ${newEmpFieldset('employee_type_label', 'Employee Type', `<select id="ceEmptype" class="form-control new-emp-select2" required><option value="">${__('select_option', 'Select')}</option>${emptypeOptions}</select>`, 'col-md-3', 'fa-user-tag', true)}
            ${newEmpFieldset('direct_supervisor', 'Direct Supervisor', `<select id="ceSupervisorId" class="form-control new-emp-select2"><option value="">${__('select_option', 'Select')}</option>${(data.supervisors || []).map(s => `<option value="${String(s.emp_id).replace(/"/g, '&quot;')}" ${String(w.supervisor_id) === String(s.emp_id) ? 'selected' : ''}>${escapeHtml(s.name)} (${escapeHtml(s.emptype)})</option>`).join('')}</select>`, 'col-md-3', 'fa-user-tie')}
            ${newEmpFieldset('joining_date', 'Joining date', `<input type="text" id="ceJoiningDate" class="form-control" value="${escapeHtml(w.joining_date || '')}" required>`, 'col-md-3', 'fa-calendar-alt', true)}
            ${newEmpFieldset('sponsorship', 'Sponsorship', `<select id="ceEmpSupType" class="form-control new-emp-select2" required>${newEmpOptionsHtml(data.sponsorships, 'id', 'sponsor', 'sponsor_ar', true, w.emp_sup_type)}</select>`, 'col-md-3', 'fa-handshake', true)}
            ${newEmpFieldset('company_label', 'Company', `<select id="ceCompNo" class="form-control new-emp-select2" required>${newEmpOptionsHtml(data.companies, 'comp_id', 'comp_name', 'comp_name_ar', true, w.comp_no)}</select>`, 'col-md-3', 'fa-building', true)}
            ${newEmpFieldset('actual_job', 'Actual job', `<select id="ceActualJob" class="form-control new-emp-select2" required>${newEmpOptionsHtml(data.jobs, 'id', 'job', 'job_ar', true, w.actual_Job)}</select>`, 'col-md-3', 'fa-briefcase', true)}
            ${newEmpFieldset('contract_period', 'Contract period', `<select id="ceVacPeriod" class="form-control new-emp-select2" required>${newEmpOptionsHtml(data.contract_periods, 'id', 'period', 'period', true, w.vac_period)}</select>`, 'col-md-3', 'fa-file-contract', true)}
            ${newEmpFieldset('vacation_days', 'Vacation Days', `<input type="text" id="ceVacationDays" class="form-control" value="${escapeHtml(w.vacation_days || '')}" readonly required>`, 'col-md-3', 'fa-umbrella-beach', true)}
            ${newEmpFieldset('probation_period_label', 'Probation Period', `<select id="ceProbation" class="form-control new-emp-select2" required><option value="">${__('select_option', 'Select')}</option><option value="3 Months" ${w.probation === '3 Months' ? 'selected' : ''}>3 ${__('months', 'Months')}</option><option value="6 Months" ${w.probation === '6 Months' ? 'selected' : ''}>6 ${__('months', 'Months')}</option></select>`, 'col-md-3', 'fa-hourglass-half', true)}
        </div>
        </div>
    </form>`;

    Swal.fire({
        title: __('almutlak_co_employee', 'Company Employee') + ' - ' + __('employment_information', 'Employment Information') + ' (2/3)',
        html,
        width: '90%',
        showCancelButton: true,
        showDenyButton: true,
        confirmButtonColor: '#28a745',
        confirmButtonText: __('next', 'Next'),
        denyButtonText: __('back', 'Back'),
        cancelButtonText: __('cancel', 'Cancel'),
        allowOutsideClick: false,
        didOpen: () => {
            newEmpInitSelect2();
            newEmpWireDatepicker('ceJoiningDate');
            if (w.city_id) newEmpPopulateLocations(w.city_id, $('#ceLocationId'), w.location_id);
            if (w.department) newEmpPopulateSubDepts(w.department, $('#ceSubDeptId'), w.sub_dept_id);

            $('#ceCityId').on('change', function() { newEmpPopulateLocations($(this).val(), $('#ceLocationId'), ''); });
            $('#ceDept').on('change', function() { newEmpPopulateSubDepts($(this).val(), $('#ceSubDeptId'), ''); });

            $('#ceVacPeriod').on('change', function() {
                const val = $(this).val();
                if (!val) { $('#ceVacationDays').val(''); return; }
                $.ajax({
                    type: 'GET',
                    url: './includes/ContractPeriodSelect.php',
                    data: { vac_period: val },
                    success: (res) => $('#ceVacationDays').val(res)
                });
            });
        },
        preConfirm: () => {
            if (!newEmpValidateRequired({
                ceDept: __('department', 'Department'),
                ceCityId: __('city_label', 'City'),
                ceLocationId: __('location_label', 'Location'),
                ceEmptype: __('employee_type_label', 'Employee Type'),
                ceJoiningDate: __('joining_date', 'Joining date'),
                ceEmpSupType: __('sponsorship', 'Sponsorship'),
                ceCompNo: __('company_label', 'Company'),
                ceActualJob: __('actual_job', 'Actual job'),
                ceVacPeriod: __('contract_period', 'Contract period'),
                ceVacationDays: __('vacation_days', 'Vacation Days'),
                ceProbation: __('probation_period_label', 'Probation Period')
            })) return false;
            return newEmpCollectEmploymentInfo();
        },
        preDeny: () => newEmpCollectEmploymentInfo()
    }).then((result) => {
        if (result.isDenied) {
            Object.assign(w, result.value);
            openCompanyStep1(data, w);
            return;
        }
        if (!result.isConfirmed) return;
        Object.assign(w, result.value);
        openCompanyStep3(data, w);
    });
}

function openCompanyStep3(data, w) {
    const isSaudi = w.country == 191;

    const html = `
    <form id="newCompEmpFormStep3" class="text-left">
        <div class="card-box">
        <div class="form-row">
            ${newEmpFieldset('salary', 'Salary', `<input type="text" id="ceSalary" class="form-control autonumber" data-v-max="2500000" data-v-min="0" value="${escapeHtml(w.salary || '')}" required>`, 'col-md-3', 'fa-money-bill-wave', true)}
            ${newEmpFieldset('bank_name', 'Bank name', `<select id="ceBankName" class="form-control new-emp-select2" required>${newEmpOptionsHtml(data.banks, 'id', 'name', 'bank_name_ar', true, w.bank_name)}</select>`, 'col-md-3', 'fa-university', true)}
            ${newEmpFieldset('iban', 'IBAN', `<input type="text" id="ceIban" class="form-control" value="${escapeHtml(w.iban || '')}" required>`, 'col-md-3', 'fa-hashtag', true)}
            ${newEmpFieldset('email', 'Email', `<input type="email" id="ceEmail" class="form-control" value="${escapeHtml(w.email || '')}">`, 'col-md-3', 'fa-envelope')}
            ${newEmpFieldset('salary_payment_type_label', 'Salary Payment type', `<select id="cePaymentType" class="form-control new-emp-select2" required><option value="">${__('select_option', 'Select')}</option><option value="1" ${w.payment_type === '1' ? 'selected' : ''}>${__('bank_option', 'Bank')}</option><option value="2" ${w.payment_type === '2' ? 'selected' : ''}>${__('cash_option', 'Cash')}</option></select>`, 'col-md-3', 'fa-credit-card', true)}
            ${newEmpFieldCard(`${__('address', 'Address')} <span class="text-danger">*</span>`, 'col-md-4', 'fa-map-marked-alt', `<input type="text" id="ceAddress" class="form-control" value="${escapeHtml(w.address || '')}" required>`)}
            <div class="form-group col-md-2 ${isSaudi ? '' : 'd-none'}" id="ceGosiDiv">
                <div class="emp-field-card">
                    <label class="emp-field-label">${__('gosi', 'GOSI')} <span class="text-danger">*</span></label>
                    <div class="emp-field-control">
                        <span class="emp-field-icon">%</span>
                        <input type="text" id="ceGosi" class="form-control" value="${escapeHtml(w.gosi || '')}">
                    </div>
                </div>
            </div>
        </div>
        </div>
    </form>`;

    Swal.fire({
        title: __('almutlak_co_employee', 'Company Employee') + ' - ' + __('other_information', 'Other Information') + ' (3/3)',
        html,
        width: '90%',
        showCancelButton: true,
        showDenyButton: true,
        confirmButtonColor: '#28a745',
        confirmButtonText: __('yes_register', 'Register'),
        denyButtonText: __('back', 'Back'),
        cancelButtonText: __('cancel', 'Cancel'),
        allowOutsideClick: false,
        showLoaderOnConfirm: true,
        didOpen: () => {
            newEmpInitSelect2();
            newEmpApplyAutoNumeric();
            newEmpApplyIbanMask('ceIban');
            newEmpFixMaskCaret('ceIban');
        },
        preDeny: () => {
            return {
                salary: $('#ceSalary').val(),
                bank_name: $('#ceBankName').val(),
                iban: $('#ceIban').val(),
                email: $('#ceEmail').val(),
                payment_type: $('#cePaymentType').val(),
                address: $('#ceAddress').val(),
                gosi: isSaudi ? $('#ceGosi').val() : ''
            };
        },
        preConfirm: async () => {
            if (!newEmpValidateRequired({
                ceSalary: __('salary', 'Salary'),
                ceBankName: __('bank_name', 'Bank name'),
                ceIban: __('iban', 'IBAN'),
                cePaymentType: __('salary_payment_type_label', 'Salary Payment type'),
                ceAddress: __('address', 'Address')
            })) return false;
            if (isSaudi && !$('#ceGosi').val()) {
                Swal.showValidationMessage(__('gosi', 'GOSI') + ' ' + __('is_required', 'is required'));
                return false;
            }

            const payload = Object.assign({}, w, {
                action: 'create_company_employee',
                salary: $('#ceSalary').val(),
                bank_name: $('#ceBankName').val(),
                iban: $('#ceIban').val(),
                email: $('#ceEmail').val(),
                payment_type: $('#cePaymentType').val(),
                address: $('#ceAddress').val(),
                gosi: isSaudi ? $('#ceGosi').val() : ''
            });

            try {
                const response = await fetch('./includes/ajaxFile/ajaxEmployeeCreateModal.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
                    body: new URLSearchParams(payload).toString()
                });
                const res = await response.json();
                if (res.status !== 'success') {
                    Swal.showValidationMessage(res.message || 'Failed to register employee.');
                    return false;
                }
                return res;
            } catch (e) {
                Swal.showValidationMessage(e.message || 'Request failed.');
                return false;
            }
        }
    }).then((result) => {
        if (result.isDenied) {
            Object.assign(w, result.value);
            openCompanyStep2(data, w);
            return;
        }
        if (result.isConfirmed && result.value && result.value.emp_id) {
            window.location.href = 'view_employee.php?emp_id=' + encodeURIComponent(result.value.emp_id);
        }
    });
}

// ---------------------------------------------------------------------------
// Man Power
// ---------------------------------------------------------------------------
function openManPowerEmployeeModal(data) {
    const html = `
    <form id="newManPowerForm" class="text-left" enctype="multipart/form-data">
        <div class="card-box">
        <div class="form-row">
            ${newEmpFieldset('employee_name', 'Employee Name', `<input type="text" id="mpName" class="form-control" required>`, 'col-md-3', 'fa-user', true)}
            ${newEmpFieldset('employee_id', 'Employee ID', `<input type="text" id="mpEmpId" class="form-control" value="${escapeHtml(data.next_emp_id)}" required>`, 'col-md-2', 'fa-id-badge', true)}
            ${newEmpFieldset('iqama_id', 'Iqama', `<input type="text" id="mpIqama" class="form-control" required>`, 'col-md-2', 'fa-id-card', true)}
            ${newEmpFieldCard(`${__('iqama_id_expiry', 'Iqama / ID expiry')} <span class="text-danger">${__('in_hijri', 'In Hijri')}</span>`, 'col-md-2', 'fa-calendar-alt', `<input type="text" id="mpIqamaExpHijri" class="form-control">`)}
            ${newEmpFieldset('nationality', 'Nationality', `<select id="mpCountry" class="form-control new-emp-select2">${newEmpOptionsHtml(data.countries, 'id', 'name', 'name', true)}</select>`, 'col-md-3', 'fa-flag')}
            ${newEmpFieldset('department', 'Department', `<select id="mpDepartment" class="form-control new-emp-select2" required>${newEmpOptionsHtml(data.departments, 'id', 'dep_nme', 'dep_nme_ar', true)}</select>`, 'col-md-3', 'fa-sitemap', true)}
            ${newEmpFieldset('company_label', 'Company', `<select id="mpCompNo" class="form-control new-emp-select2" required>${newEmpOptionsHtml(data.companies, 'comp_id', 'comp_name', 'comp_name_ar', true)}</select>`, 'col-md-3', 'fa-building', true)}
            ${newEmpFieldset('city_label', 'City', `<select id="mpCityId" class="form-control new-emp-select2">${newEmpOptionsHtml(data.cities, 'id', 'name_en', 'name_ar', true)}</select>`, 'col-md-3', 'fa-city')}
            ${newEmpFieldset('location_label', 'Location', `<select id="mpLocationId" class="form-control new-emp-select2"><option value="">${__('select_a_city_first', 'Select a City First')}</option></select>`, 'col-md-3', 'fa-map-marker-alt')}
            ${newEmpFieldset('sub_department_label', 'Sub-Department', `<select id="mpSubDeptId" class="form-control new-emp-select2"><option value="">${__('select_a_department_first', 'Select a Department First')}</option></select>`, 'col-md-3', 'fa-sitemap')}
            ${newEmpFieldset('mobile', 'Mobile No.', `<input type="text" id="mpMobile" class="form-control">`, 'col-md-3', 'fa-phone')}
            ${newEmpFieldset('joining_date', 'Joining Date', `<input type="text" id="mpJoiningDate" class="form-control">`, 'col-md-3', 'fa-calendar-alt')}
            ${newEmpFieldset('salary', 'Salary', `<input type="text" id="mpSalary" class="form-control autonumber" data-v-max="25000" data-v-min="0" required>`, 'col-md-3', 'fa-money-bill-wave', true)}
            ${newEmpFieldset('date_of_birth', 'Date of Birth', `<input type="text" id="mpDob" class="form-control">`, 'col-md-3', 'fa-calendar-alt')}
            ${newEmpFieldCard(`${__('gender', 'Gender')} <span class="text-danger">*</span>`, 'col-md-3', 'fa-venus-mars', newEmpTabGroup('mpSex', [{ value: 'male', label: __('male', 'Male') }, { value: 'female', label: __('female', 'Female') }], 'male'))}
            ${newEmpFieldset('add_employee_picture', 'Add Employee Picture', `<input type="file" id="mpAvatar" class="form-control" accept="image/png,image/jpeg,image/gif">`, 'col-md-3', 'fa-camera')}
        </div>
        </div>
        <input type="hidden" id="mpIqamaExpG">
    </form>`;

    Swal.fire({
        title: __('manpower_employee', 'Man Power'),
        html,
        width: '75%',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        confirmButtonText: __('register', 'Register'),
        cancelButtonText: __('cancel', 'Cancel'),
        allowOutsideClick: false,
        showLoaderOnConfirm: true,
        didOpen: () => {
            newEmpInitSelect2();
            newEmpWireHijriPair('mpIqamaExpG', 'mpIqamaExpHijri');
            newEmpApplyMasks('mpIqama', 'mpMobile');
            newEmpApplyAutoNumeric();
            newEmpFixMaskCaret('mpIqama');
            newEmpFixMaskCaret('mpMobile');
            newEmpWireDatepicker('mpJoiningDate');

            $('#mpCityId').on('change', function() { newEmpPopulateLocations($(this).val(), $('#mpLocationId'), ''); });
            $('#mpDepartment').on('change', function() { newEmpPopulateSubDepts($(this).val(), $('#mpSubDeptId'), ''); });
        },
        preConfirm: async () => {
            const requiredMap = { mpName: 'Employee name', mpEmpId: 'Employee ID', mpIqama: 'Iqama', mpDepartment: 'Department', mpCompNo: 'Company', mpSalary: 'Salary' };
            for (const id in requiredMap) {
                if (!$('#' + id).val()) {
                    Swal.showValidationMessage(`${requiredMap[id]} ${__('is_required', 'is required')}`);
                    return false;
                }
            }

            const formData = new FormData();
            formData.append('action', 'create_man_power_employee');
            formData.append('name', $('#mpName').val());
            formData.append('emp_id', $('#mpEmpId').val());
            formData.append('iqama', $('#mpIqama').val());
            formData.append('iqama_exp_g', $('#mpIqamaExpG').val());
            formData.append('mobile', $('#mpMobile').val());
            formData.append('salary', $('#mpSalary').val());
            formData.append('joining_date', $('#mpJoiningDate').val());
            formData.append('department', $('#mpDepartment').val());
            formData.append('comp_no', $('#mpCompNo').val());
            formData.append('city_id', $('#mpCityId').val());
            formData.append('location_id', $('#mpLocationId').val());
            formData.append('sub_dept_id', $('#mpSubDeptId').val());
            formData.append('country', $('#mpCountry').val());
            formData.append('dob', $('#mpDob').val());
            formData.append('sex', $('input[name=mpSex]:checked').val());
            const avatarFile = document.getElementById('mpAvatar').files[0];
            if (avatarFile) formData.append('avatar', avatarFile);

            try {
                const response = await fetch('./includes/ajaxFile/ajaxEmployeeCreateModal.php', {
                    method: 'POST',
                    body: formData
                });
                const res = await response.json();
                if (res.status !== 'success') {
                    Swal.showValidationMessage(res.message || 'Failed to register employee.');
                    return false;
                }
                return res;
            } catch (e) {
                Swal.showValidationMessage(e.message || 'Request failed.');
                return false;
            }
        }
    }).then((result) => {
        if (result.isConfirmed && result.value && result.value.emp_id) {
            window.location.href = 'view_employee.php?emp_id=' + encodeURIComponent(result.value.emp_id);
        }
    });
}

// ---------------------------------------------------------------------------
// Entry point
// ---------------------------------------------------------------------------
$(document).on('click', '#newEmployeeMenuLink', function(e) {
    e.preventDefault();
    openNewEmployeeTypeModal();
});
