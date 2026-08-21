function openSalaryIncrementApplyModal(empid, iqama, name, deptName, joiningDate) {

    const escapeHtml = (value) => String(value == null ? '' : value)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#39;');

    const getStatusMeta = (statusValue) => {
        const normalized = String(statusValue || '').toLowerCase().replace(/_/g, ' ').trim();
        const toTitle = (value) => String(value || '').replace(/\b\w/g, ch => ch.toUpperCase());

        if (normalized.includes('approved')) {
            return { icon: 'fa-check-circle', cls: 'text-success', bg: '#e9f9ee', label: __('approved', 'Approved') };
        }
        if (normalized.includes('rejected')) {
            return { icon: 'fa-times-circle', cls: 'text-danger', bg: '#fdeceb', label: __('rejected', 'Rejected') };
        }
        if (normalized.includes('cancelled')) {
            return { icon: 'fa-ban', cls: 'text-secondary', bg: '#f1f2f4', label: __('cancelled', 'Cancelled') };
        }
        if (normalized.includes('pending')) {
            return { icon: 'fa-hourglass-half', cls: 'text-warning', bg: '#fff8e6', label: __('pending', 'Pending') };
        }
        if (normalized.includes('awaiting')) {
            return { icon: 'fa-pause-circle', cls: 'text-info', bg: '#e8f4fd', label: __('awaiting', 'Awaiting') };
        }
        return { icon: 'fa-circle', cls: 'text-secondary', bg: '#f1f2f4', label: toTitle(normalized || __('unknown', 'Unknown')) };
    };

    const buildActiveRequestHtml = (res) => {
        const req = res && res.existing_request ? res.existing_request : null;
        const chain = res && Array.isArray(res.approval_chain) ? res.approval_chain : [];

        if (!req) {
            return '<div class="vacation-form-container"><div class="vacation-card"><div class="vacation-card-header"><i class="fa fa-info-circle"></i> ' + __('applied_information', 'Applied Information') + '</div><div style="padding:12px 4px;color:#555;">' + ((res && res.message) || '') + '</div></div></div>';
        }

        const statusMeta = getStatusMeta(req.current_status);
        const submittedDate = req.created_at ? new Date(req.created_at.replace(' ', 'T')) : null;
        const submittedLabel = submittedDate && !isNaN(submittedDate.getTime()) ? submittedDate.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' }) : (req.created_at || '-');

        let chainHtml = '<div style="padding:6px 2px;color:#8a94a6;font-size:13px;">' + __('no_data_found', 'No data found') + '</div>';
        if (chain.length > 0) {
            chainHtml = '<div style="display:flex;flex-direction:column;gap:8px;">' + chain.map(c => {
                const cMeta = getStatusMeta(c.status);
                const approverName = escapeHtml((c.approver_name && String(c.approver_name).trim() !== '') ? c.approver_name : ('Emp#' + c.approver_id));
                return '<div style="display:flex;align-items:center;justify-content:space-between;padding:8px 12px;background:' + cMeta.bg + ';border-radius:8px;">'
                    + '<span style="font-size:13px;color:#333;"><strong style="color:#667eea;">' + __('level', 'Level') + ' ' + c.level + '</strong> &nbsp;' + approverName + '</span>'
                    + '<span class="' + cMeta.cls + '" style="font-size:12px;font-weight:600;white-space:nowrap;"><i class="fa ' + cMeta.icon + '"></i> ' + cMeta.label + '</span>'
                    + '</div>';
            }).join('') + '</div>';
        }

        return `
            <div class="vacation-form-container" style="text-align:left;">
                <div class="vacation-card" style="border-left:4px solid #f0ad4e;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <i class="fa fa-triangle-exclamation" style="font-size:20px;color:#f0ad4e;"></i>
                        <span style="font-size:14px;color:#555;line-height:1.5;">${__('you_already_have_active_salary_increment', 'You already have an active salary increment request for this employee. A new request is not allowed until the current one is completed.')}</span>
                    </div>
                </div>

                <div class="vacation-card">
                    <div class="vacation-card-header"><i class="fa fa-file-alt"></i> ${__('applied_information', 'Applied Information')}</div>
                    <div style="display:flex;flex-direction:column;gap:10px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid #eef0f4;">
                            <span style="color:#8a94a6;font-size:13px;">${__('request_id', 'Request ID')}</span>
                            <code style="font-size:13px;">${escapeHtml(req.request_inv_no)}</code>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid #eef0f4;">
                            <span style="color:#8a94a6;font-size:13px;">${__('status', 'Status')}</span>
                            <span class="${statusMeta.cls}" style="font-weight:600;font-size:13px;background:${statusMeta.bg};padding:4px 10px;border-radius:20px;"><i class="fa ${statusMeta.icon}"></i> ${statusMeta.label}</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid #eef0f4;">
                            <span style="color:#8a94a6;font-size:13px;">${__('increment_amount', 'Increment Amount')}</span>
                            <strong style="font-size:14px;"><i class="icon-saudi_riyal"></i> ${Number(req.increment_amount).toFixed(2)}</strong>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid #eef0f4;">
                            <span style="color:#8a94a6;font-size:13px;">${__('submitted_date', 'Submitted Date')}</span>
                            <span style="font-size:13px;">${submittedLabel}</span>
                        </div>
                        ${req.reason ? `<div style="padding:8px 0;">
                            <div style="color:#8a94a6;font-size:13px;margin-bottom:4px;">${__('reason', 'Reason')}</div>
                            <div style="font-size:13px;color:#333;background:#f8f9fb;padding:8px 10px;border-radius:6px;">${escapeHtml(req.reason)}</div>
                        </div>` : ''}
                    </div>
                </div>

                <div class="vacation-card">
                    <div class="vacation-card-header"><i class="fa fa-sitemap"></i> ${__('request_chain', 'Request Chain')}</div>
                    ${chainHtml}
                </div>
            </div>
        `;
    };

    const showActiveRequestModal = (res) => {
        const modalWidth = (window.innerWidth && window.innerWidth < 768) ? '95%' : '40rem';
        Swal.fire({
            title: '<i class="fa fa-arrow-trend-up" style="margin-right: 8px;"></i> ' + (res.title || __('active_request_exists', 'Active Request Exists')),
            html: buildActiveRequestHtml(res),
            showConfirmButton: false,
            showCancelButton: true,
            cancelButtonColor: (typeof APP_COLORS !== 'undefined') ? APP_COLORS.danger_dark : '#dc3545',
            cancelButtonText: '<i class="fa fa-times"></i> ' + (__('close', 'Close')),
            allowOutsideClick: false,
            width: modalWidth,
            padding: '20px',
            scrollbarPadding: false,
            customClass: {
                popup: 'vacation-modal-popup',
                title: 'vacation-modal-title',
                cancelButton: 'btn-modern-cancel'
            }
        });
    };

    // Years of service, computed from joining date (supports YYYY-MM-DD and similar formats)
    const yearsOfService = (() => {
        const parsed = joiningDate ? new Date(joiningDate) : null;
        if (!parsed || isNaN(parsed.getTime())) return null;
        const now = new Date();
        let years = now.getFullYear() - parsed.getFullYear();
        const monthDiff = now.getMonth() - parsed.getMonth();
        if (monthDiff < 0 || (monthDiff === 0 && now.getDate() < parsed.getDate())) years--;
        return years;
    })();

    const maxIncrementAmount = (typeof window.SALARY_INCREMENT_MAX_AMOUNT === 'number' && window.SALARY_INCREMENT_MAX_AMOUNT > 0)
        ? window.SALARY_INCREMENT_MAX_AMOUNT
        : 2000;

    const salaryIncrementForm_HTML = () => `
        <div class="vacation-form-container" style="text-align:left;">
            <div class="vacation-card">
                <div class="vacation-card-header"><i class="fa fa-id-badge"></i> ${__('employee_information', 'Employee Information')}</div>
                <div class="row" style="margin:0;">
                    <div class="col-md-6"><strong>${__('employee_id', 'Emp ID')}:</strong> ${empid || '-'}</div>
                    <div class="col-md-6"><strong>${__('iqama', 'Iqama')}:</strong> ${iqama || '-'}</div>
                    <div class="col-md-6"><strong>${__('department', 'Department')}:</strong> ${deptName || '-'}</div>
                    <div class="col-md-6"><strong>${__('employee_name', 'Employee Name')}:</strong> ${name || '-'}</div>
                    <div class="col-md-6"><strong>${__('joining_date', 'Date of Joining')}:</strong> ${joiningDate || '-'}</div>
                    <div class="col-md-6"><strong>${__('years_of_service', 'Years of Service')}:</strong> ${yearsOfService !== null ? yearsOfService + ' ' + __('years', 'years') : '-'}</div>
                </div>
            </div>

            <div class="vacation-card">
                <div class="vacation-card-header"><i class="fa fa-history"></i> ${__('last_increment', 'Last Increment')}</div>
                <div id="si_last_increment_status">
                    <span class="text-muted"><i class="fa fa-spinner fa-spin"></i> ${__('checking', 'Checking...')}</span>
                </div>
            </div>

            <div class="vacation-card">
                <div class="vacation-card-header"><i class="fa fa-arrow-trend-up"></i> ${__('increment_details', 'Increment Details')}</div>
                <div class="row" style="margin:0;">
                    <div class="col-md-6 form-group">
                        <label>${__('increment_amount', 'Increment Amount')} <span class="text-danger">*</span></label>
                        <input type="number" id="si_increment_amount" class="form-control" min="1" max="${maxIncrementAmount}" step="0.01" placeholder="${__('max', 'Max')} ${maxIncrementAmount}" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>${__('current_year_evaluation', 'Current Year Evaluation')} <span class="text-danger">*</span></label>
                        <div id="si_evaluation_status">
                            <span class="text-muted"><i class="fa fa-spinner fa-spin"></i> ${__('checking', 'Checking...')}</span>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>${__('reason', 'Reason')} <span class="text-danger">*</span></label>
                    <textarea id="si_reason" class="form-control" rows="3" required></textarea>
                </div>
            </div>
        </div>
    `;

    // Current-year evaluation score (by this supervisor) for this employee, fetched from
    // the server - never typed manually. Submit stays locked until this is found.
    let fetchedScore = null;
    let hasScore = false;

    // Last approved increment eligibility (only one increment allowed per year) - fetched
    // from the server, submit stays locked while a prior increment is under 1 year old.
    let lastIncrementEligible = true;
    let lastIncrementChecked = false;

    const setConfirmEnabled = (enabled) => {
        const btn = Swal.getConfirmButton();
        if (!btn) return;
        btn.disabled = !enabled;
        btn.style.opacity = enabled ? '1' : '0.5';
        btn.style.cursor = enabled ? 'pointer' : 'not-allowed';
    };

    const isFormValid = () => {
        const increment_amount = parseFloat($('#si_increment_amount').val());
        const reason = $('#si_reason').val() ? $('#si_reason').val().trim() : '';

        const amountValid = !isNaN(increment_amount) && increment_amount > 0 && increment_amount <= maxIncrementAmount;
        const reasonValid = reason !== '';

        return amountValid && reasonValid && hasScore && lastIncrementEligible;
    };

    const updateSubmitState = () => setConfirmEnabled(isFormValid());

    const renderEvaluationStatus = () => {
        const $status = $('#si_evaluation_status');
        if (hasScore) {
            $status.html(
                '<span class="text-success"><i class="fa fa-check-circle"></i> ' +
                (__('evaluated_score', 'Evaluated') + ': ') +
                '<strong>' + fetchedScore + '</strong></span>' +
                '<input type="hidden" id="si_evaluation_score" value="' + fetchedScore + '">'
            );
        } else {
            $status.html(
                '<div class="alert alert-warning" style="padding:6px 10px;margin-bottom:6px;">' +
                __('no_current_year_evaluation_hint', 'No current-year evaluation found for this employee. Please evaluate them first.') +
                '</div>' +
                '<button type="button" id="si_goto_evaluation_btn" class="btn btn-info btn-sm"><i class="fa fa-clipboard-check"></i> ' + __('go_to_evaluation_page', 'Go to Evaluation Page') + '</button> ' +
                '<button type="button" id="si_recheck_evaluation_btn" class="btn btn-outline-secondary btn-sm"><i class="fa fa-rotate"></i> ' + __('recheck', 'Recheck') + '</button>'
            );
        }
        updateSubmitState();
    };

    const fetchLatestScore = () => {
        $('#si_evaluation_status').html('<span class="text-muted"><i class="fa fa-spinner fa-spin"></i> ' + __('checking', 'Checking...') + '</span>');
        $.ajax({
            url: './includes/ajaxFile/ajaxSalaryIncrement.php',
            dataType: 'JSON',
            type: 'POST',
            data: { ajaxType: 'getEmployeeEvaluationLatest', emp_id: empid },
            success: function (res) {
                hasScore = !!(res && res.status === 'success' && res.has_score);
                fetchedScore = hasScore ? res.evaluation_score : null;
                renderEvaluationStatus();
            },
            error: function () {
                hasScore = false;
                fetchedScore = null;
                renderEvaluationStatus();
            }
        });
    };

    const renderLastIncrementStatus = (res) => {
        const $status = $('#si_last_increment_status');
        const hasLast = !!(res && res.has_last_increment && res.last_increment);

        if (!hasLast) {
            $status.html('<span class="text-muted"><i class="fa fa-circle-info"></i> ' + __('no_previous_increment', 'No previous increment on record.') + '</span>');
            lastIncrementEligible = true;
        } else {
            const amount = res.last_increment.amount;
            const dateStr = res.last_increment.date;
            const displayDate = new Date(dateStr);
            const dateLabel = isNaN(displayDate.getTime()) ? dateStr : displayDate.toLocaleDateString();
            lastIncrementEligible = !!res.eligible;

            if (lastIncrementEligible) {
                $status.html(
                    '<span class="text-success"><i class="fa fa-check-circle"></i> ' +
                    __('last_increment_on', 'Last increment') + ': <strong>' + amount + '</strong> ' + __('on', 'on') + ' ' + dateLabel +
                    '</span>'
                );
            } else {
                $status.html(
                    '<div class="alert alert-danger" style="padding:6px 10px;margin-bottom:0;">' +
                    '<i class="fa fa-triangle-exclamation"></i> ' +
                    __('last_increment_too_recent', 'This employee already received an increment of') + ' <strong>' + amount + '</strong> ' +
                    __('on', 'on') + ' ' + dateLabel + '. ' +
                    __('next_increment_eligible_in', 'Another increment is not allowed for about') + ' ' + (res.months_remaining || 0) + ' ' + __('more_months', 'more month(s).') +
                    '</div>'
                );
            }
        }

        updateSubmitState();
    };

    const fetchLastIncrementInfo = () => {
        $('#si_last_increment_status').html('<span class="text-muted"><i class="fa fa-spinner fa-spin"></i> ' + __('checking', 'Checking...') + '</span>');
        $.ajax({
            url: './includes/ajaxFile/ajaxSalaryIncrement.php',
            dataType: 'JSON',
            type: 'POST',
            data: { ajaxType: 'getLastIncrementInfo', emp_id: empid },
            success: function (res) {
                lastIncrementChecked = true;
                renderLastIncrementStatus(res && res.status === 'success' ? res : null);
            },
            error: function () {
                lastIncrementChecked = true;
                lastIncrementEligible = true;
                renderLastIncrementStatus(null);
            }
        });
    };

    const openApplyFormModal = () => Swal.fire({
        title: '<i class="fa fa-arrow-trend-up" style="margin-right: 8px;"></i> ' + (__('apply_salary_increment', 'Apply Salary Increment')),
        html: salaryIncrementForm_HTML(),
        showCancelButton: true,
        confirmButtonColor: (typeof APP_COLORS !== 'undefined') ? APP_COLORS.primary : '#0d6efd',
        cancelButtonColor: (typeof APP_COLORS !== 'undefined') ? APP_COLORS.danger_dark : '#dc3545',
        confirmButtonText: '<i class="fa fa-check"></i> ' + (__('submit_salary_increment_request', 'Submit')),
        cancelButtonText: '<i class="fa fa-times"></i> ' + (__('cancel', 'Cancel')),
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        width: '55%',
        padding: '20px',
        scrollbarPadding: false,
        customClass: {
            popup: 'vacation-modal-popup',
            title: 'vacation-modal-title',
            confirmButton: 'btn-modern-confirm',
            cancelButton: 'btn-modern-cancel'
        },
        willOpen: () => {
            // Hard-clamp increment amount to the configured max as the user types (not just on submit)
            $(document).off('input.salaryIncrementAmount').on('input.salaryIncrementAmount', '#si_increment_amount', function () {
                const val = parseFloat($(this).val());
                if (!isNaN(val) && val > maxIncrementAmount) {
                    $(this).val(maxIncrementAmount);
                }
                updateSubmitState();
            });

            $(document).off('input.salaryIncrementReason').on('input.salaryIncrementReason', '#si_reason', updateSubmitState);

            $(document).off('click.salaryIncrementGotoEval').on('click.salaryIncrementGotoEval', '#si_goto_evaluation_btn', function () {
                window.open('employee_evaluation.php', '_blank');
            });

            $(document).off('click.salaryIncrementRecheckEval').on('click.salaryIncrementRecheckEval', '#si_recheck_evaluation_btn', function () {
                fetchLatestScore();
            });
        },
        didOpen: () => {
            setConfirmEnabled(false);
            fetchLatestScore();
            fetchLastIncrementInfo();
        },
        preConfirm: () => {
            const increment_amount = parseFloat($('#si_increment_amount').val());
            const reason = $('#si_reason').val() ? $('#si_reason').val().trim() : '';

            if (!isFormValid()) {
                const msg = !lastIncrementEligible
                    ? __('last_increment_too_recent_short', 'This employee received an increment less than a year ago. Not eligible yet.')
                    : __('evaluation_required_hint', 'This employee must have a current-year evaluation from you before you can submit this request.');
                Swal.showValidationMessage(msg);
                return false;
            }

            return $.ajax({
                url: './includes/ajaxFile/ajaxSalaryIncrement.php',
                dataType: 'JSON',
                type: 'POST',
                data: {
                    ajaxType: 'submitSalaryIncrement',
                    emp_id: empid,
                    increment_amount: increment_amount,
                    reason: reason
                }
            }).then(response => {
                if (!response || response.status !== 'success') {
                    Swal.showValidationMessage((response && response.message) || __('submission_failed', 'Submission failed.'));
                    return false;
                }
                return response;
            }).catch(() => {
                Swal.showValidationMessage(__('submission_failed', 'Submission failed.'));
                return false;
            });
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            Swal.fire({
                icon: 'success',
                title: __('submitted', 'Submitted'),
                text: result.value.message || __('salary_increment_submitted', 'Salary increment request submitted successfully.')
            }).then(() => {
                if (typeof location !== 'undefined') location.reload();
            });
        }
    });

    const showNotEligibleModal = (res) => {
        const amount = res && res.last_increment ? res.last_increment.amount : null;
        const dateStr = res && res.last_increment ? res.last_increment.date : null;
        const displayDate = dateStr ? new Date(dateStr) : null;
        const dateLabel = displayDate && !isNaN(displayDate.getTime()) ? displayDate.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' }) : dateStr;
        const monthsRemaining = (res && res.months_remaining) || 0;

        let eligibleDateLabel = '-';
        if (displayDate && !isNaN(displayDate.getTime())) {
            const eligibleDate = new Date(displayDate);
            eligibleDate.setFullYear(eligibleDate.getFullYear() + 1);
            eligibleDateLabel = eligibleDate.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
        }

        const detailRows = (amount !== null)
            ? `<div style="display:flex;flex-direction:column;gap:10px;">
                <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid #eef0f4;">
                    <span style="color:#8a94a6;font-size:13px;">${__('last_increment_on', 'Last increment')}</span>
                    <span style="font-size:13px;"><strong>${amount}</strong> &nbsp;${__('on', 'on')} ${dateLabel}</span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid #eef0f4;">
                    <span style="color:#8a94a6;font-size:13px;">${__('next_eligible_date', 'Next Eligible Date')}</span>
                    <strong style="font-size:13px;color:#28a745;">${eligibleDateLabel}</strong>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;">
                    <span style="color:#8a94a6;font-size:13px;">${__('time_remaining', 'Time Remaining')}</span>
                    <span style="font-size:13px;">${monthsRemaining} ${__('more_months', 'more month(s).')}</span>
                </div>
            </div>`
            : `<div style="padding:8px 0;color:#555;font-size:13px;">${__('last_increment_too_recent_short', 'This employee received an increment less than a year ago. Not eligible yet.')}</div>`;

        const html = `
            <div class="vacation-form-container" style="text-align:left;">
                <div class="vacation-card" style="border-left:4px solid #f0ad4e;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <i class="fa fa-triangle-exclamation" style="font-size:20px;color:#f0ad4e;"></i>
                        <span style="font-size:14px;color:#555;line-height:1.5;">${__('last_increment_too_recent_notice', 'This employee is not yet eligible for a new salary increment. Only one increment is allowed per year.')}</span>
                    </div>
                </div>
                <div class="vacation-card">
                    <div class="vacation-card-header"><i class="fa fa-history"></i> ${__('last_increment', 'Last Increment')}</div>
                    ${detailRows}
                </div>
            </div>
        `;

        Swal.fire({
            title: '<i class="fa fa-arrow-trend-up" style="margin-right: 8px;"></i> ' + __('not_eligible_for_increment', 'Not Eligible Yet'),
            html: html,
            showConfirmButton: false,
            showCancelButton: true,
            cancelButtonColor: (typeof APP_COLORS !== 'undefined') ? APP_COLORS.danger_dark : '#dc3545',
            cancelButtonText: '<i class="fa fa-times"></i> ' + (__('close', 'Close')),
            allowOutsideClick: false,
            width: (window.innerWidth && window.innerWidth < 768) ? '95%' : '40rem',
            padding: '20px',
            scrollbarPadding: false,
            customClass: {
                popup: 'vacation-modal-popup',
                title: 'vacation-modal-title',
                cancelButton: 'btn-modern-cancel'
            }
        });
    };

    // Check for an already-active request first, then the 1-year cooldown, before
    // opening the full apply form - both are hard blockers, so fail fast with a
    // clear message instead of letting the supervisor fill the whole form first.
    $.ajax({
        url: './includes/ajaxFile/ajaxSalaryIncrement.php',
        dataType: 'JSON',
        type: 'POST',
        data: { ajaxType: 'checkActiveSalaryIncrement', emp_id: empid },
        success: function (res) {
            if (res && res.status === 'success' && res.has_active_request) {
                showActiveRequestModal(res);
                return;
            }

            $.ajax({
                url: './includes/ajaxFile/ajaxSalaryIncrement.php',
                dataType: 'JSON',
                type: 'POST',
                data: { ajaxType: 'getLastIncrementInfo', emp_id: empid },
                success: function (liRes) {
                    if (liRes && liRes.status === 'success' && liRes.has_last_increment && !liRes.eligible) {
                        showNotEligibleModal(liRes);
                    } else {
                        openApplyFormModal();
                    }
                },
                error: function () {
                    openApplyFormModal();
                }
            });
        },
        error: function () {
            openApplyFormModal();
        }
    });
}
