function openSalaryIncrementApplyModal(empid, iqama, name, deptName, joiningDate) {

    // New GUI popups need smart_request.css (jquery.app.js provides srEnsureCss on most pages)
    if (typeof window.srEnsureCss === 'function') {
        window.srEnsureCss();
    } else if (!document.querySelector('link[href*="smart_request.css"]')) {
        $('head').append('<link rel="stylesheet" href="assets/css/smart_request.css">');
    }

    const escapeHtml = (value) => String(value == null ? '' : value)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#39;');

    const getStatusMeta = (statusValue) => {
        const normalized = String(statusValue || '').toLowerCase().replace(/_/g, ' ').trim();
        const toTitle = (value) => String(value || '').replace(/\b\w/g, ch => ch.toUpperCase());

        if (normalized.includes('approved')) {
            return { icon: 'fa-check-circle', cls: 'text-success', bg: '#e9f9ee', tone: 'tone-green', label: __('approved', 'Approved') };
        }
        if (normalized.includes('rejected')) {
            return { icon: 'fa-times-circle', cls: 'text-danger', bg: '#fdeceb', tone: 'tone-red', label: __('rejected', 'Rejected') };
        }
        if (normalized.includes('cancelled')) {
            return { icon: 'fa-ban', cls: 'text-secondary', bg: '#f1f2f4', tone: 'tone-slate', label: __('cancelled', 'Cancelled') };
        }
        if (normalized.includes('pending')) {
            return { icon: 'fa-hourglass-half', cls: 'text-warning', bg: '#fff8e6', tone: 'tone-amber', label: __('pending', 'Pending') };
        }
        if (normalized.includes('awaiting')) {
            return { icon: 'fa-pause-circle', cls: 'text-info', bg: '#e8f4fd', tone: 'tone-sky', label: __('awaiting', 'Awaiting') };
        }
        return { icon: 'fa-circle', cls: 'text-secondary', bg: '#f1f2f4', tone: 'tone-slate', label: toTitle(normalized || __('unknown', 'Unknown')) };
    };

    const kvRow = (label, valueHtml) => '<div class="row-kv"><dt>' + label + '</dt><dd>' + valueHtml + '</dd></div>';

    const buildActiveRequestHtml = (res) => {
        const req = res && res.existing_request ? res.existing_request : null;
        const chain = res && Array.isArray(res.approval_chain) ? res.approval_chain : [];

        if (!req) {
            return '<div class="sr-page"><div class="sr-form"><div class="sr-notice tone-sky"><i class="mdi mdi-information-outline"></i><div>' + escapeHtml((res && res.message) || '') + '</div></div></div></div>';
        }

        const statusMeta = getStatusMeta(req.current_status);
        const submittedDate = req.created_at ? new Date(req.created_at.replace(' ', 'T')) : null;
        const submittedLabel = submittedDate && !isNaN(submittedDate.getTime()) ? submittedDate.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' }) : (req.created_at || '-');

        let chainHtml = '<span class="sr-fhint">' + __('no_data_found', 'No data found') + '</span>';
        if (chain.length > 0) {
            chainHtml = '<div class="sr-levels">' + chain.map(c => {
                const cMeta = getStatusMeta(c.status);
                const approverName = escapeHtml((c.approver_name && String(c.approver_name).trim() !== '') ? c.approver_name : ('Emp#' + c.approver_id));
                return '<div class="sr-level-row">'
                    + '<span><b>' + __('level', 'Level') + ' ' + escapeHtml(c.level) + '</b>' + approverName + '</span>'
                    + '<span class="sr-pill sr-pill-xs ' + cMeta.tone + '"><i class="fa ' + cMeta.icon + '"></i> ' + cMeta.label + '</span>'
                    + '</div>';
            }).join('') + '</div>';
        }

        return `
            <div class="sr-page">
            <div class="sr-form">
                <div class="sr-notice tone-amber" style="margin-bottom:12px;">
                    <i class="mdi mdi-alert"></i>
                    <div>${__('you_already_have_active_salary_increment', 'You already have an active salary increment request for this employee. A new request is not allowed until the current one is completed.')}</div>
                </div>

                <div class="sr-fsec">
                    <div class="sr-fsec-head"><span><i class="mdi mdi-file-document"></i> ${__('applied_information', 'Applied Information')}</span></div>
                    <div class="sr-fsec-body">
                        <dl class="sr-kv">
                            ${kvRow(__('request_id', 'Request ID'), '<span class="sr-mono">' + escapeHtml(req.request_inv_no) + '</span>')}
                            ${kvRow(__('status', 'Status'), '<span class="sr-pill sr-pill-xs ' + statusMeta.tone + '"><i class="fa ' + statusMeta.icon + '"></i> ' + statusMeta.label + '</span>')}
                            ${kvRow(__('increment_amount', 'Increment Amount'), '<i class="icon-saudi_riyal"></i> ' + Number(req.increment_amount).toFixed(2))}
                            ${kvRow(__('submitted_date', 'Submitted Date'), escapeHtml(submittedLabel))}
                        </dl>
                        ${req.reason ? `<label class="mt-2">${__('reason', 'Reason')}</label><div class="sr-chain-note mt-0">${escapeHtml(req.reason)}</div>` : ''}
                    </div>
                </div>

                <div class="sr-fsec mb-0">
                    <div class="sr-fsec-head"><span><i class="mdi mdi-sitemap"></i> ${__('request_chain', 'Request Chain')}</span></div>
                    <div class="sr-fsec-body">${chainHtml}</div>
                </div>
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
            customClass: { popup: 'sr-addline-popup' }
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

    const checkingHtml = '<span class="sr-status-line tone-muted"><i class="fa fa-spinner fa-spin"></i> ' + __('checking', 'Checking...') + '</span>';

    const salaryIncrementForm_HTML = () => `
        <div class="sr-page">
        <div class="sr-form">
            <div class="sr-fsec">
                <div class="sr-fsec-head"><span><i class="mdi mdi-account-card-details"></i> ${__('employee_information', 'Employee Information')}</span></div>
                <div class="sr-fgrid">
                    <div class="sr-fcol c-6">
                        <dl class="sr-kv">
                            ${kvRow(__('employee_name', 'Employee Name'), escapeHtml(name || '-'))}
                            ${kvRow(__('employee_id', 'Emp ID'), escapeHtml(empid || '-'))}
                            ${kvRow(__('iqama', 'Iqama'), escapeHtml(iqama || '-'))}
                        </dl>
                    </div>
                    <div class="sr-fcol c-6">
                        <dl class="sr-kv">
                            ${kvRow(__('department', 'Department'), escapeHtml(deptName || '-'))}
                            ${kvRow(__('joining_date', 'Date of Joining'), escapeHtml(joiningDate || '-'))}
                            ${kvRow(__('years_of_service', 'Years of Service'), yearsOfService !== null ? yearsOfService + ' ' + __('years', 'years') : '-')}
                        </dl>
                    </div>
                </div>
            </div>

            <div class="sr-fsec">
                <div class="sr-fsec-head"><span><i class="mdi mdi-history"></i> ${__('last_increment', 'Last Increment')}</span></div>
                <div class="sr-fsec-body" id="si_last_increment_status">${checkingHtml}</div>
            </div>

            <div class="sr-fsec mb-0">
                <div class="sr-fsec-head"><span><i class="mdi mdi-trending-up"></i> ${__('increment_details', 'Increment Details')}</span></div>
                <div class="sr-fgrid">
                    <div class="sr-fcol c-6">
                        <label for="si_increment_amount">${__('increment_amount', 'Increment Amount')} <span class="text-danger">*</span></label>
                        <input type="number" id="si_increment_amount" class="form-control" min="1" max="${maxIncrementAmount}" step="0.01" placeholder="${__('max', 'Max')} ${maxIncrementAmount}" required>
                        <span class="sr-fhint">${__('max', 'Max')} ${maxIncrementAmount} <i class="icon-saudi_riyal"></i></span>
                    </div>
                    <div class="sr-fcol c-6">
                        <label>${__('current_year_evaluation', 'Current Year Evaluation')} <span class="text-danger">*</span></label>
                        <div id="si_evaluation_status">${checkingHtml}</div>
                    </div>
                    <div class="sr-fcol c-12">
                        <label for="si_reason">${__('reason', 'Reason')} <span class="text-danger">*</span></label>
                        <textarea id="si_reason" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
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
                '<span class="sr-status-line tone-green" style="min-height:38px;"><i class="mdi mdi-check-circle"></i> ' +
                (__('evaluated_score', 'Evaluated') + ': ') +
                '<strong>' + escapeHtml(fetchedScore) + '</strong></span>' +
                '<input type="hidden" id="si_evaluation_score" value="' + escapeHtml(fetchedScore) + '">'
            );
        } else {
            $status.html(
                '<div class="sr-notice tone-amber is-compact"><i class="mdi mdi-alert"></i><div>' +
                __('no_current_year_evaluation_hint', 'No current-year evaluation found for this employee. Please evaluate them first.') +
                '</div></div>' +
                '<div class="sr-actions">' +
                '<button type="button" id="si_goto_evaluation_btn" class="sr-btn sr-btn-sm sr-btn-primary"><i class="fa fa-clipboard-check"></i> ' + __('go_to_evaluation_page', 'Go to Evaluation Page') + '</button>' +
                '<button type="button" id="si_recheck_evaluation_btn" class="sr-btn sr-btn-sm"><i class="fa fa-rotate"></i> ' + __('recheck', 'Recheck') + '</button>' +
                '</div>'
            );
        }
        updateSubmitState();
    };

    const fetchLatestScore = () => {
        $('#si_evaluation_status').html(checkingHtml);
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
            $status.html('<span class="sr-status-line tone-muted"><i class="mdi mdi-information-outline"></i> ' + __('no_previous_increment', 'No previous increment on record.') + '</span>');
            lastIncrementEligible = true;
        } else {
            const amount = res.last_increment.amount;
            const dateStr = res.last_increment.date;
            const displayDate = new Date(dateStr);
            const dateLabel = isNaN(displayDate.getTime()) ? dateStr : displayDate.toLocaleDateString();
            lastIncrementEligible = !!res.eligible;

            if (lastIncrementEligible) {
                $status.html(
                    '<span class="sr-status-line tone-green"><i class="mdi mdi-check-circle"></i> ' +
                    __('last_increment_on', 'Last increment') + ': <strong>' + escapeHtml(amount) + '</strong> ' + __('on', 'on') + ' ' + escapeHtml(dateLabel) +
                    '</span>'
                );
            } else {
                $status.html(
                    '<div class="sr-notice tone-red is-compact mb-0"><i class="mdi mdi-alert-circle-outline"></i><div>' +
                    __('last_increment_too_recent', 'This employee already received an increment of') + ' <strong>' + amount + '</strong> ' +
                    __('on', 'on') + ' ' + dateLabel + '. ' +
                    __('next_increment_eligible_in', 'Another increment is not allowed for about') + ' ' + (res.months_remaining || 0) + ' ' + __('more_months', 'more month(s).') +
                    '</div></div>'
                );
            }
        }

        updateSubmitState();
    };

    const fetchLastIncrementInfo = () => {
        $('#si_last_increment_status').html(checkingHtml);
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
        width: (window.innerWidth && window.innerWidth < 768) ? '95%' : '760px',
        scrollbarPadding: false,
        customClass: { popup: 'sr-addline-popup' },
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
            ? `<dl class="sr-kv">
                ${kvRow(__('last_increment_on', 'Last increment'), '<strong>' + escapeHtml(amount) + '</strong> ' + __('on', 'on') + ' ' + escapeHtml(dateLabel))}
                ${kvRow(__('next_eligible_date', 'Next Eligible Date'), '<span class="sr-pill sr-pill-xs tone-green">' + escapeHtml(eligibleDateLabel) + '</span>')}
                ${kvRow(__('time_remaining', 'Time Remaining'), monthsRemaining + ' ' + __('more_months', 'more month(s).'))}
            </dl>`
            : `<span class="sr-fhint">${__('last_increment_too_recent_short', 'This employee received an increment less than a year ago. Not eligible yet.')}</span>`;

        const html = `
            <div class="sr-page">
            <div class="sr-form">
                <div class="sr-notice tone-amber" style="margin-bottom:12px;">
                    <i class="mdi mdi-alert"></i>
                    <div>${__('last_increment_too_recent_notice', 'This employee is not yet eligible for a new salary increment. Only one increment is allowed per year.')}</div>
                </div>
                <div class="sr-fsec mb-0">
                    <div class="sr-fsec-head"><span><i class="mdi mdi-history"></i> ${__('last_increment', 'Last Increment')}</span></div>
                    <div class="sr-fsec-body">${detailRows}</div>
                </div>
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
            customClass: { popup: 'sr-addline-popup' }
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
