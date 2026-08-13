/**
 * Employee Transfer Request
 *
 * Lets someone request an employee (from any company/section) be transferred to
 * a new Direct Supervisor - temporarily (date range) or permanently. The request
 * auto-routes first to the employee's current direct supervisor for approval,
 * then follows the chain configured in App Settings -> Approval
 * (approval_chain_employee_transfer_request).
 *
 * Dependencies: jQuery, Select2, SweetAlert2 (Swal), bootstrap-daterangepicker,
 * moment.js, translation.js (__ function), APP_COLORS (global)
 *
 * Usage:
 *  openEmployeeTransferModal(requesterEmpId)
 *  openEmployeeTransferModal(requesterEmpId, presetTargetEmpId) - target already known
 */

function employeeTransferForm_HTML() {
    return `
    <form id="submitEmployeeTransferForm">
        <div class="vacation-form-container et-compact">

            <!-- Optional: pull in the employee whose profile this modal was opened from -->
            <div id="et_fetch_current_row" class="d-none mb-3">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="et_fetch_current_employee">
                    <label class="custom-control-label" for="et_fetch_current_employee" style="cursor: pointer;">
                        <i class="fa fa-user-check"></i> ${__('fetch_current_employee', 'Fetch current employee')}
                    </label>
                </div>
            </div>

            <div class="row">
                <!-- Step 1: Job title -->
                <div class="col-md-6">
                    <div id="et_job_step" class="vacation-card">
                        <div class="vacation-card-header">
                            <i class="fa fa-id-badge"></i>
                            ${__('job_title', 'Job Title')}<span class="text-danger">*</span>
                        </div>
                        <select id="et_job_id" class="form-control">
                            <option value="">${__('select_job_title', 'Select a job title')}</option>
                        </select>
                    </div>
                </div>

                <!-- Step 2: Employee (filtered by job, all companies) -->
                <div class="col-md-6">
                    <div id="et_employee_step" class="vacation-card">
                        <div class="vacation-card-header">
                            <i class="fa fa-user"></i>
                            ${__('employee', 'Employee')}<span class="text-danger">*</span>
                        </div>
                        <select id="et_target_emp_id" class="form-control" disabled>
                            <option value="">${__('select_job_title_first', 'Select a job title first')}</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Employee summary card -->
            <div id="et_employee_summary" class="vacation-card d-none">
                <div class="vacation-card-header">
                    <i class="fa fa-info-circle"></i>
                    ${__('employee_information', 'Employee Information')}
                </div>
                <div class="row" id="et_employee_summary_list"></div>
            </div>

            <div class="row">
                <!-- Step 3: New Direct Supervisor -->
                <div class="col-md-6">
                    <div class="vacation-card">
                        <div class="vacation-card-header">
                            <i class="fa fa-user-tie"></i>
                            ${__('new_direct_supervisor', 'New Direct Supervisor')}<span class="text-danger">*</span>
                        </div>
                        <select id="et_to_supervisor_id" class="form-control">
                            <option value="">${__('select_new_supervisor', 'Select the new direct supervisor')}</option>
                        </select>
                        <small class="text-muted">${__('new_supervisor_email_note', 'Only active accounts with a company (@almutlak.com) email can be selected.')}</small>
                    </div>
                </div>

                <!-- Step 4: Temporary / Permanent -->
                <div class="col-md-6">
                    <div class="vacation-card">
                        <div class="vacation-card-header">
                            <i class="fa fa-exchange-alt"></i>
                            ${__('transfer_type', 'Transfer Type')}<span class="text-danger">*</span>
                        </div>
                        <div class="vac-radio-group">
                            <div class="vac-radio-option">
                                <input type="radio" id="et_type_temporary" name="et_transfer_type" value="temporary" checked>
                                <label for="et_type_temporary" class="vac-radio-label">
                                    <i class="fa fa-clock"></i>
                                    <span>${__('temporary', 'Temporary')}</span>
                                </label>
                            </div>
                            <div class="vac-radio-option">
                                <input type="radio" id="et_type_permanent" name="et_transfer_type" value="permanent">
                                <label for="et_type_permanent" class="vac-radio-label">
                                    <i class="fa fa-infinity"></i>
                                    <span>${__('permanent', 'Permanent')}</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Temporary date range -->
                <div id="et_temporary_dates_section" class="col-md-6">
                    <div class="vacation-card">
                        <div class="vacation-card-header">
                            <i class="fa fa-calendar"></i>
                            ${__('transfer_dates', 'Transfer Dates')}<span class="text-danger">*</span>
                        </div>
                        <div class="info-row">
                            <div class="info-field">
                                <label class="form-label">${__('transfer_dates', 'Transfer Dates')}</label>
                                <input type="text" id="et_date_range" class="form-control et-date-trigger" placeholder="${__('click_to_select_dates', 'Click to select dates')}" readonly>
                            </div>
                            <div class="info-field">
                                <label class="form-label">${__('duration', 'Duration')}</label>
                                <input type="text" id="et_days_count" class="form-control" readonly>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Permanent effective date -->
                <div id="et_permanent_date_section" class="col-md-6 d-none">
                    <div class="vacation-card">
                        <div class="vacation-card-header">
                            <i class="fa fa-calendar-check"></i>
                            ${__('effective_date', 'Effective Date')}<span class="text-danger">*</span>
                        </div>
                        <input type="text" id="et_effective_date" class="form-control et-date-trigger" placeholder="${__('click_to_select_date', 'Click to select a date')}" readonly>
                    </div>
                </div>

                <!-- Notes -->
                <div class="col-md-6" id="et_notes_col">
                    <div class="vacation-card">
                        <div class="vacation-card-header">
                            <i class="fa fa-sticky-note"></i>
                            ${__('additional_notes', 'Additional Notes')}
                        </div>
                        <textarea id="et_request_notes" class="form-control" rows="2" placeholder="${__('enter_any_notes', 'Enter any notes for the approver')}"></textarea>
                    </div>
                </div>
            </div>

            <div class="swal-approval-chain text-left mt-2">
                <hr>
                <p class="text-info mb-0">
                    <i class="fa fa-info-circle"></i>
                    ${__('transfer_first_approver_note', "This request will first be sent to the employee's current direct supervisor for approval. Further approval steps are configured in App Settings.")}
                </p>
            </div>

            <input type="hidden" id="et_requester_emp_id">
        </div>
    </form>`;
}

function openEmployeeTransferModal(requesterEmpId, presetTargetEmpId) {
    let jobsList = [];
    let employeesByJob = {}; // emp_id -> row data, cached from the last emp_by_job / target-info fetch

    // Snapshot of the main form's fields, captured right before it's closed to show the date
    // picker sub-modal, and used to restore everything when the main modal reopens after.
    let formState = null;

    const captureFormState = (swalModal) => ({
        jobId: $(swalModal).find('#et_job_id').val() || '',
        targetEmpId: $(swalModal).find('#et_target_emp_id').val() || '',
        toSupervisorId: $(swalModal).find('#et_to_supervisor_id').val() || '',
        transferType: $(swalModal).find('input[name="et_transfer_type"]:checked').val() || '',
        dateRange: $(swalModal).find('#et_date_range').val() || '',
        effectiveDate: $(swalModal).find('#et_effective_date').val() || '',
        requestNotes: $(swalModal).find('#et_request_notes').val() || ''
    });

    // --- Date picker sub-modal: shown on its own so the calendar never has to overlap the
    // compact main form. Closing the main modal first also sidesteps any positioning/overflow
    // issues from anchoring the plugin's dropdown inside a small popup. ---
    const openDatePickerModal = (mode) => {
        const isRange = mode === 'range';

        // Range mode uses two independent single-date fields (Start / End) instead of one
        // dual-calendar range picker - a range picker always treats the NEXT click as a new
        // start date once a full range is selected, so re-opening it to nudge just the end
        // date silently resets the start too. Two separate pickers avoid that entirely: each
        // field only ever changes when its own picker is used.
        const rangeHtml = `
            <div class="row">
                <div class="col-6">
                    <label class="form-label" style="font-weight:600;font-size:12px;color:#858796;text-transform:uppercase;">${__('start_date', 'Start Date')}</label>
                    <input type="text" id="et_range_start_input" class="form-control et-date-trigger" style="text-align:center;font-weight:600;" readonly>
                </div>
                <div class="col-6">
                    <label class="form-label" style="font-weight:600;font-size:12px;color:#858796;text-transform:uppercase;">${__('end_date', 'End Date')}</label>
                    <input type="text" id="et_range_end_input" class="form-control et-date-trigger" style="text-align:center;font-weight:600;" readonly>
                </div>
            </div>
            <div id="et_range_days_badge" class="et-picker-live-days d-none"><i class="fa fa-calendar-day"></i> <span></span></div>`;
        const singleHtml = '<input type="text" id="et_date_picker_input" class="form-control" style="text-align:center;font-weight:600;" readonly>';

        Swal.fire({
            title: '<i class="fa fa-calendar" style="margin-right: 8px;"></i> ' + (isRange ? __('transfer_dates', 'Transfer Dates') : __('effective_date', 'Effective Date')),
            html: isRange ? rangeHtml : singleHtml,
            showCancelButton: true,
            confirmButtonText: '<i class="fa fa-check"></i> ' + __('select', 'Select'),
            cancelButtonText: '<i class="fa fa-times"></i> ' + __('cancel', 'Cancel'),
            confirmButtonColor: (typeof APP_COLORS !== 'undefined') ? APP_COLORS.primary : '#3085d6',
            cancelButtonColor: (typeof APP_COLORS !== 'undefined') ? APP_COLORS.danger_dark : '#aaa',
            width: isRange ? '35%' : '25%',
            padding: '20px',
            allowOutsideClick: false,
            customClass: {
                popup: 'vacation-modal-popup',
                title: 'vacation-modal-title',
                confirmButton: 'btn-modern-confirm',
                cancelButton: 'btn-modern-cancel'
            },
            didOpen: () => {
                const canUseDateRangePicker = (typeof $.fn.daterangepicker === 'function' && typeof moment === 'function');
                const popup = Swal.getPopup();

                if (!isRange) {
                    const $input = $('#et_date_picker_input');
                    if (!canUseDateRangePicker) {
                        $input.prop('readonly', false).attr('placeholder', 'MM/DD/YYYY');
                        if (formState && formState.effectiveDate) {
                            $input.val(formState.effectiveDate);
                        }
                        return;
                    }
                    try {
                        $input.daterangepicker({
                            singleDatePicker: true,
                            locale: { format: 'MM/DD/YYYY' },
                            autoUpdateInput: true,
                            // Pass minDate as a string, not a moment object - this page loads a
                            // second, older moment.js (visible in the console stack trace as
                            // moment.js/2.18.1 from cdnjs) alongside the one bundled in
                            // plugins/moment/, and daterangepicker.js captured whichever loaded
                            // first internally. Handing it a live moment *object* built from
                            // window.moment (whichever instance that resolves to at call time)
                            // crashes deep in its clone logic when the two don't match; a plain
                            // string sidesteps the whole cross-version object problem.
                            minDate: moment().format('MM/DD/YYYY'),
                            startDate: (formState && formState.effectiveDate) ? formState.effectiveDate : moment().format('MM/DD/YYYY'),
                            parentEl: popup || document.body
                        });
                        $input.trigger('click');
                        const picker = $input.data('daterangepicker');
                        if (picker && picker._outsideClickProxy) {
                            $(document).off('mousedown.daterangepicker touchend.daterangepicker focusin.daterangepicker', picker._outsideClickProxy);
                        }
                    } catch (err) {
                        console.error('employeeTransfer.js: single date picker init failed, falling back to manual entry.', err);
                        $input.prop('readonly', false).attr('placeholder', 'MM/DD/YYYY');
                        if (formState && formState.effectiveDate) {
                            $input.val(formState.effectiveDate);
                        }
                    }
                    return;
                }

                // --- Range mode: two independent single-date pickers ---
                let startVal = moment().add(1, 'day').format('MM/DD/YYYY');
                let endVal = startVal;
                if (formState && formState.dateRange) {
                    const parts = formState.dateRange.split(' - ');
                    if (parts.length === 2) {
                        startVal = parts[0].trim();
                        endVal = parts[1].trim();
                    }
                }

                const $startInput = $('#et_range_start_input');
                const $endInput = $('#et_range_end_input');
                const $daysBadge = $('#et_range_days_badge');

                if (!canUseDateRangePicker) {
                    $startInput.prop('readonly', false).attr('placeholder', 'MM/DD/YYYY').val(startVal);
                    $endInput.prop('readonly', false).attr('placeholder', 'MM/DD/YYYY').val(endVal);
                    return;
                }

                const renderRangeBadge = () => {
                    const m1 = moment($startInput.val(), 'MM/DD/YYYY');
                    const m2 = moment($endInput.val(), 'MM/DD/YYYY');
                    if (m1.isValid() && m2.isValid()) {
                        const diffDays = m2.diff(m1, 'days') + 1;
                        $daysBadge.find('span').text((diffDays > 0 ? diffDays : 1) + ' ' + __('days', 'days'));
                        $daysBadge.removeClass('d-none');
                    } else {
                        $daysBadge.addClass('d-none');
                    }
                };

                const unbindOutsideClick = ($el) => {
                    const picker = $el.data('daterangepicker');
                    if (picker && picker._outsideClickProxy) {
                        $(document).off('mousedown.daterangepicker touchend.daterangepicker focusin.daterangepicker', picker._outsideClickProxy);
                    }
                    return picker;
                };

                // $.fn.daterangepicker already removes/replaces any existing instance on the
                // element itself, so init doesn't need to do that manually first.
                const initEndPicker = (minVal, presetVal) => {
                    $endInput.daterangepicker({
                        singleDatePicker: true,
                        locale: { format: 'MM/DD/YYYY' },
                        autoUpdateInput: true,
                        minDate: minVal,
                        startDate: presetVal,
                        parentEl: popup || document.body
                    });
                    unbindOutsideClick($endInput);
                    $endInput.off('apply.daterangepicker.employeeTransfer').on('apply.daterangepicker.employeeTransfer', renderRangeBadge);
                };

                // Picker setup is wrapped defensively - if the plugin throws for any reason,
                // fall back to plain manual-entry text fields instead of leaving the modal
                // stuck with two dead, unclickable inputs.
                try {
                    try {
                        $startInput.daterangepicker({
                            singleDatePicker: true,
                            locale: { format: 'MM/DD/YYYY' },
                            autoUpdateInput: true,
                            // String, not a moment object - see the comment on the single-date
                            // picker above for why passing a live moment object breaks here.
                            minDate: moment().format('MM/DD/YYYY'),
                            startDate: startVal,
                            parentEl: popup || document.body
                        });
                    } catch (err) {
                        console.error('employeeTransfer.js: START picker construction threw.', err);
                        throw err;
                    }

                    try {
                        unbindOutsideClick($startInput);
                    } catch (err) {
                        console.error('employeeTransfer.js: START unbindOutsideClick threw.', err);
                        throw err;
                    }

                    try {
                        initEndPicker(startVal, endVal);
                    } catch (err) {
                        console.error('employeeTransfer.js: END picker construction threw.', err);
                        throw err;
                    }

                    try {
                        renderRangeBadge();
                    } catch (err) {
                        console.error('employeeTransfer.js: renderRangeBadge threw.', err);
                        throw err;
                    }

                    // Start date changed - keep the end picker's own minDate (and, if it would
                    // now precede the new start, its value) in sync, without ever touching the
                    // start field itself. This is the reset the range-picker version couldn't avoid.
                    $startInput.off('apply.daterangepicker.employeeTransfer').on('apply.daterangepicker.employeeTransfer', function(ev, picker) {
                        const newStart = picker.startDate.format('MM/DD/YYYY');
                        const currentEndVal = $endInput.val();
                        const currentEndMoment = moment(currentEndVal, 'MM/DD/YYYY');
                        const newEndPreset = (currentEndMoment.isValid() && !currentEndMoment.isBefore(picker.startDate, 'day'))
                            ? currentEndVal
                            : newStart;
                        initEndPicker(newStart, newEndPreset);
                        renderRangeBadge();
                    });
                } catch (err) {
                    console.error('employeeTransfer.js: date range picker init failed, falling back to manual entry.', err);
                    $startInput.prop('readonly', false).attr('placeholder', 'MM/DD/YYYY').val(startVal);
                    $endInput.prop('readonly', false).attr('placeholder', 'MM/DD/YYYY').val(endVal);
                }
            },
            preConfirm: () => {
                if (isRange) {
                    const startStr = $('#et_range_start_input').val();
                    const endStr = $('#et_range_end_input').val();
                    const m1 = moment(startStr, 'MM/DD/YYYY');
                    const m2 = moment(endStr, 'MM/DD/YYYY');
                    if (!m1.isValid() || !m2.isValid() || m2.isBefore(m1, 'day')) {
                        Swal.showValidationMessage(__('please_select_valid_date_range', 'Please select a valid date range'));
                        return false;
                    }
                    return startStr + ' - ' + endStr;
                }

                const val = $('#et_date_picker_input').val();
                if (!val || !moment(val, 'MM/DD/YYYY').isValid()) {
                    Swal.showValidationMessage(__('please_select_valid_start_date', 'Please select a valid effective date'));
                    return false;
                }
                return val;
            }
        }).then((res) => {
            if (!formState) {
                formState = {};
            }
            if (res.isConfirmed) {
                if (isRange) {
                    formState.dateRange = res.value;
                } else {
                    formState.effectiveDate = res.value;
                }
            }
            openFormModal();
        });
    };

    const openFormModal = () => Swal.fire({
        title: '<i class="fa fa-people-arrows" style="margin-right: 8px;"></i> ' + __('request_employee_transfer', 'Request Employee Transfer'),
        html: employeeTransferForm_HTML(),
        showCancelButton: true,
        confirmButtonColor: (typeof APP_COLORS !== 'undefined') ? APP_COLORS.primary : '#3085d6',
        cancelButtonColor: (typeof APP_COLORS !== 'undefined') ? APP_COLORS.danger_dark : '#aaa',
        confirmButtonText: '<i class="fa fa-paper-plane"></i> ' + __('submit_request', 'Submit Request'),
        cancelButtonText: '<i class="fa fa-times"></i> ' + __('cancel', 'Cancel'),
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        width: '65%',
        padding: '20px',
        scrollbarPadding: false,
        customClass: {
            popup: 'vacation-modal-popup',
            title: 'vacation-modal-title',
            confirmButton: 'btn-modern-confirm',
            cancelButton: 'btn-modern-cancel'
        },
        willOpen: () => {
            const swalModal = Swal.getHtmlContainer();
            $('#et_requester_emp_id').val(requesterEmpId);

            const getParent = () => {
                const popup = Swal.getPopup();
                return popup ? $(popup) : $(document.body);
            };

            const initSelect2 = (selector, placeholder) => {
                const $el = $(selector);
                if ($el.hasClass('select2-hidden-accessible')) {
                    $el.select2('destroy');
                }
                $el.select2({
                    placeholder: placeholder,
                    allowClear: true,
                    width: '100%',
                    dropdownParent: getParent()
                });
            };

            initSelect2('#et_job_id', __('select_job_title', 'Select a job title'));
            initSelect2('#et_target_emp_id', __('select_job_title_first', 'Select a job title first'));
            initSelect2('#et_to_supervisor_id', __('select_new_supervisor', 'Select the new direct supervisor'));

            // Job Title and Employee both load empty, regardless of entry point (including the
            // per-employee "More Actions" shortcut) - the requester always picks a Job Title
            // manually first, which then loads that job's employees into the Employee list.
            const $empSelect = $(swalModal).find('#et_target_emp_id');

            // --- Load matching employees (all companies, status = 1) for one or more ac_jobs ids ---
            const loadEmployeesForJob = (jobIds, onLoaded) => {
                $(swalModal).find('#et_employee_summary').addClass('d-none');
                employeesByJob = {};
                $empSelect.prop('disabled', true).html('<option value="">' + __('loading', 'Loading...') + '</option>').trigger('change.select2');

                $.ajax({
                    url: './includes/ajaxFile/hrHandler.php',
                    type: 'POST',
                    dataType: 'JSON',
                    data: { ajaxType: 'emp_by_job', job_id: jobIds, requester_emp_id: requesterEmpId },
                    success: function(res) {
                        let html = '<option value="">' + __('select_employee', 'Select an employee') + '</option>';
                        if (res.status === 200 && Array.isArray(res.data) && res.data.length > 0) {
                            res.data.forEach(row => {
                                employeesByJob[row.emp_id] = row;
                                html += `<option value="${row.emp_id}">${row.emp_id} - ${row.name}</option>`;
                            });
                            $empSelect.prop('disabled', false);
                        } else {
                            html = '<option value="">' + __('no_employees_found_for_this_job', 'No eligible employees found for this job') + '</option>';
                        }
                        $empSelect.html(html).trigger('change.select2');
                        if (typeof onLoaded === 'function') {
                            onLoaded();
                        }
                    }
                });
            };

            // --- Job change -> load matching employees ---
            $(swalModal).on('change', '#et_job_id', function() {
                const jobId = $(this).val();
                if (!jobId) {
                    $(swalModal).find('#et_employee_summary').addClass('d-none');
                    employeesByJob = {};
                    $empSelect.prop('disabled', true).html('<option value="">' + __('select_job_title_first', 'Select a job title first') + '</option>').trigger('change.select2');
                    return;
                }
                loadEmployeesForJob(jobId, function() {
                    if (formState && formState.targetEmpId && employeesByJob[formState.targetEmpId]) {
                        $empSelect.val(formState.targetEmpId).trigger('change');
                    }
                });
            });

            // --- Load job titles (deduplicated by title), restoring the previous selection
            // when reopening after the date picker sub-modal. ---
            $.ajax({
                url: './includes/ajaxFile/hrHandler.php',
                type: 'POST',
                dataType: 'JSON',
                data: { ajaxType: 'get_all_jobs' },
                success: function(res) {
                    if (res.status === 200 && Array.isArray(res.data)) {
                        jobsList = res.data;
                        let html = '<option value="">' + __('select_job_title', 'Select a job title') + '</option>';
                        jobsList.forEach(job => {
                            html += `<option value="${job.id}">${job.job_name}</option>`;
                        });
                        $(swalModal).find('#et_job_id').html(html);
                    }

                    if (formState && formState.jobId) {
                        $(swalModal).find('#et_job_id').val(formState.jobId).trigger('change');
                    }
                }
            });

            // --- "Fetch current employee" checkbox: only shown when this modal was opened
            // from a specific employee's own "More Actions" menu. Off by default - checking
            // it looks up that employee's job, selects it (which loads its employees), and
            // auto-picks that employee once the list arrives. Unchecking clears back to empty. ---
            if (presetTargetEmpId) {
                $(swalModal).find('#et_fetch_current_row').removeClass('d-none');
            }
            $(swalModal).on('change', '#et_fetch_current_employee', function() {
                const $jobSelect = $(swalModal).find('#et_job_id');
                if (!this.checked) {
                    $jobSelect.val('').trigger('change');
                    return;
                }
                if (!presetTargetEmpId) {
                    return;
                }
                $.ajax({
                    url: './includes/ajaxFile/hrHandler.php',
                    type: 'POST',
                    dataType: 'JSON',
                    data: { ajaxType: 'get_employee_transfer_target_info', emp_id: presetTargetEmpId },
                    success: function(targetRes) {
                        if (targetRes.status !== 200 || !targetRes.data || !targetRes.data.actual_job) {
                            return;
                        }
                        const targetJobId = String(targetRes.data.actual_job);
                        const matchedJob = jobsList.find(job => String(job.id).split(',').indexOf(targetJobId) !== -1);
                        if (!matchedJob) {
                            return;
                        }
                        formState = formState || {};
                        formState.targetEmpId = presetTargetEmpId;
                        $jobSelect.val(matchedJob.id).trigger('change');
                    }
                });
            });

            // --- Employee change -> show summary card + reload supervisor candidates ---
            $(swalModal).on('change', '#et_target_emp_id', function() {
                const empId = $(this).val();
                const $summary = $(swalModal).find('#et_employee_summary');
                const $list = $(swalModal).find('#et_employee_summary_list');

                if (!empId || !employeesByJob[empId]) {
                    $summary.addClass('d-none');
                    return;
                }

                const row = employeesByJob[empId];
                const rows = [
                    [__('employee_id', 'Employee ID'), row.emp_id],
                    [__('name', 'Name'), row.name],
                    [__('department', 'Department'), row.deptnme || 'N/A'],
                    [__('company', 'Company'), row.compnme || 'N/A'],
                    [__('current_direct_supervisor', 'Current Direct Supervisor'), row.supervisor_name || __('none', 'None')]
                ];
                $list.html(rows.map(r => `<div class="col-md-4 et-summary-item"><label>${r[0]}</label><span>${r[1]}</span></div>`).join(''));
                $summary.removeClass('d-none');

                loadSupervisorCandidates(empId, function() {
                    if (formState && formState.toSupervisorId) {
                        $(swalModal).find('#et_to_supervisor_id').val(formState.toSupervisorId).trigger('change.select2');
                    }
                });
            });

            // --- Load "New Direct Supervisor" candidates (@almutlak.com accounts only) ---
            function loadSupervisorCandidates(excludeEmpId, onLoaded) {
                const $supSelect = $(swalModal).find('#et_to_supervisor_id');
                $.ajax({
                    url: './includes/ajaxFile/hrHandler.php',
                    type: 'POST',
                    dataType: 'JSON',
                    data: { ajaxType: 'get_transfer_supervisor_candidates', exclude_emp_id: excludeEmpId || '' },
                    success: function(res) {
                        let html = '<option value="">' + __('select_new_supervisor', 'Select the new direct supervisor') + '</option>';
                        if (res.status === 200 && Array.isArray(res.data)) {
                            res.data.forEach(sup => {
                                html += `<option value="${sup.emp_id}">${sup.emp_id} - ${sup.name} (${sup.email})</option>`;
                            });
                        }
                        $supSelect.html(html).trigger('change.select2');
                        if (typeof onLoaded === 'function') {
                            onLoaded();
                        }
                    }
                });
            }
            loadSupervisorCandidates(null, function() {
                if (formState && formState.toSupervisorId && !formState.targetEmpId) {
                    $(swalModal).find('#et_to_supervisor_id').val(formState.toSupervisorId).trigger('change.select2');
                }
            });

            // --- Transfer type toggle ---
            $(swalModal).on('change', 'input[name="et_transfer_type"]', function() {
                const isTemporary = $(this).val() === 'temporary';
                $(swalModal).find('#et_temporary_dates_section').toggleClass('d-none', !isTemporary);
                $(swalModal).find('#et_permanent_date_section').toggleClass('d-none', isTemporary);
            });

            // --- Date fields open a dedicated date-picker sub-modal instead of an inline
            // dropdown, so the calendar never overlaps the rest of this compact form. ---
            const recomputeDaysCount = (rangeVal) => {
                const parts = String(rangeVal || '').split(' - ');
                if (parts.length === 2 && typeof moment === 'function') {
                    const m1 = moment(parts[0].trim(), 'MM/DD/YYYY');
                    const m2 = moment(parts[1].trim(), 'MM/DD/YYYY');
                    if (m1.isValid() && m2.isValid()) {
                        $(swalModal).find('#et_days_count').val((m2.diff(m1, 'days') + 1) + ' ' + __('days', 'days'));
                    }
                }
            };
            $(swalModal).on('click', '#et_date_range, #et_effective_date', function() {
                formState = captureFormState(swalModal);
                const mode = $(this).attr('id') === 'et_date_range' ? 'range' : 'single';
                Swal.close();
                openDatePickerModal(mode);
            });

            // --- Restore everything captured before the date picker sub-modal took over ---
            if (formState) {
                if (formState.transferType) {
                    $(swalModal).find('input[name="et_transfer_type"][value="' + formState.transferType + '"]').prop('checked', true).trigger('change');
                }
                $(swalModal).find('#et_request_notes').val(formState.requestNotes || '');
                if (formState.dateRange) {
                    $(swalModal).find('#et_date_range').val(formState.dateRange);
                    recomputeDaysCount(formState.dateRange);
                }
                if (formState.effectiveDate) {
                    $(swalModal).find('#et_effective_date').val(formState.effectiveDate);
                }
            }
        },
        preConfirm: () => {
            const swalModal = Swal.getHtmlContainer();
            const targetEmpId = $(swalModal).find('#et_target_emp_id').val();
            const toSupervisorId = $(swalModal).find('#et_to_supervisor_id').val();
            const transferType = $(swalModal).find('input[name="et_transfer_type"]:checked').val();
            const requestNotes = $(swalModal).find('#et_request_notes').val() || '';

            if (!targetEmpId) {
                Swal.showValidationMessage(__('please_select_employee', 'Please select an employee to transfer'));
                return false;
            }
            if (!toSupervisorId) {
                Swal.showValidationMessage(__('please_select_new_supervisor', 'Please select the new Direct Supervisor'));
                return false;
            }
            if (!transferType) {
                Swal.showValidationMessage(__('please_select_transfer_type', 'Please select Temporary or Permanent'));
                return false;
            }

            let startDate = '';
            let endDate = '';

            if (transferType === 'temporary') {
                const rangeVal = $(swalModal).find('#et_date_range').val();
                const parts = String(rangeVal || '').split(' - ');
                if (parts.length !== 2) {
                    Swal.showValidationMessage(__('please_select_valid_date_range', 'Please select a valid date range'));
                    return false;
                }
                const m1 = moment(parts[0].trim(), 'MM/DD/YYYY');
                const m2 = moment(parts[1].trim(), 'MM/DD/YYYY');
                if (!m1.isValid() || !m2.isValid()) {
                    Swal.showValidationMessage(__('please_select_valid_date_range', 'Please select a valid date range'));
                    return false;
                }
                startDate = m1.format('YYYY-MM-DD');
                endDate = m2.format('YYYY-MM-DD');
            } else {
                const effVal = $(swalModal).find('#et_effective_date').val();
                const m1 = moment(effVal, 'MM/DD/YYYY');
                if (!m1.isValid()) {
                    Swal.showValidationMessage(__('please_select_valid_start_date', 'Please select a valid effective date'));
                    return false;
                }
                startDate = m1.format('YYYY-MM-DD');
            }

            return new Promise((resolve, reject) => {
                $.ajax({
                    url: './includes/ajaxFile/ajaxEmployeeTransfer.php',
                    type: 'POST',
                    dataType: 'JSON',
                    data: {
                        ajaxType: 'submitEmployeeTransferRequest',
                        target_emp_id: targetEmpId,
                        to_supervisor_id: toSupervisorId,
                        transfer_type: transferType,
                        start_date: startDate,
                        end_date: endDate,
                        request_notes: requestNotes
                    }
                })
                .done(function(response) {
                    resolve(response);
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    reject({
                        status: 'error',
                        title: __('submission_failed', 'Submission Failed'),
                        message: 'Error: ' + errorThrown,
                        type: 'error'
                    });
                });
            });
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            Swal.fire({
                title: result.value.title || (result.value.status === 'success' ? __('success', 'Success') : __('error', 'Error')),
                text: result.value.message,
                icon: result.value.type || (result.value.status === 'success' ? 'success' : 'error'),
                allowOutsideClick: false,
                confirmButtonColor: (typeof APP_COLORS !== 'undefined') ? APP_COLORS.primary : '#3085d6'
            }).then(() => {
                if (result.value.status === 'success') {
                    location.reload();
                }
            });
        }
    });

    openFormModal();
}
