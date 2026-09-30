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

// newEmpDatePickerModal / newEmpWireDatepicker / newEmpWireHijriPair / newEmpHijriCalendarHtml /
// newEmpWireHijriCalendar now live in jquery.app.js (loaded on every admin page), so any page
// can wire a date field the same way this form does, not just this modal.

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

function newEmpEnsureJqueryBrowserShim() {
    // bootstrap-inputmask.min.js reads the legacy jQuery.browser.msie property (removed in
    // jQuery 1.9+). jquery.app.js normally shims this in with `jQuery.browser = {}`, but since
    // this plugin now loads asynchronously it can fire before that shim runs (or after jQuery
    // gets re-assigned elsewhere), leaving jQuery.browser undefined and throwing on focus.
    if (window.jQuery && !jQuery.browser) {
        jQuery.browser = {};
    }
}

function newEmpApplyMasks(iqamaId, mobileId) {
    if (window.jQuery && typeof jQuery.fn.inputmask === 'function') {
        newEmpEnsureJqueryBrowserShim();
        if (iqamaId) $('#' + iqamaId).inputmask({ mask: '9999999999' });
        if (mobileId) $('#' + mobileId).inputmask({ mask: '0599999999' });
    }
}

function newEmpApplyIbanMask(ibanId) {
    if (window.jQuery && typeof jQuery.fn.inputmask === 'function' && ibanId) {
        newEmpEnsureJqueryBrowserShim();
        $('#' + ibanId).inputmask({ mask: 'SA99 9999 9999 9999 9999 9999' });
    }
}

function newEmpApplyAutoNumeric() {
    if (window.jQuery && typeof jQuery.fn.autoNumeric === 'function') {
        $('.autonumber').autoNumeric('init');
    }
}

// autoNumeric formats these fields with a thousands separator (e.g. "5,505"). If that
// formatted string is fed back into .val() when a step is re-rendered (Back/Next), autoNumeric's
// own re-init logic mistakes the "," for a decimal separator and mangles the value (5,505 -> 6).
// Reading through autoNumeric('get') returns the raw unformatted number instead, so re-rendering
// stays safe.
function newEmpGetNumeric(id) {
    const $el = $('#' + id);
    if (window.jQuery && typeof jQuery.fn.autoNumeric === 'function' && $el.data('autoNumeric')) {
        return $el.autoNumeric('get');
    }
    return $el.val();
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
// Draft employees (server table `employee_drafts`, shared by all HR users)
//
// Every employee form is its own draft row: as soon as something is typed into it, it's saved
// (debounced) and keeps saving on every edit, so Next/Back, the date picker re-renders, Cancel
// and a page reload never lose entries - and several employees can be kept as drafts side by
// side. Each draft reserves its emp_id on the server (new forms get the following number), is
// listed in the type picker, and its row is deleted by the server once registered.
// "Save as Draft" just marks it as saved.
// ---------------------------------------------------------------------------
const NEW_EMP_AJAX_URL = './includes/ajaxFile/ajaxEmployeeCreateModal.php';

async function newEmpPost(params) {
    const response = await fetch(NEW_EMP_AJAX_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
        body: new URLSearchParams(params).toString()
    });
    return response.json();
}

// 'register' or 'draft' - chosen with the Register / Save as Draft radio at the top of the form
// (defaults to Register for every new form or opened draft). In draft mode only the name is
// required and the last button saves the form as a draft instead of registering.
let newEmpMode = 'register';

function newEmpIsDraftMode() {
    return newEmpMode === 'draft';
}

// Always rendered (hidden in register mode) so the mode radio can toggle it in place.
function newEmpModeBadge() {
    return ` <span class="badge badge-warning new-emp-mode-badge" style="font-size:14px;vertical-align:middle;${newEmpIsDraftMode() ? '' : 'display:none;'}">${__('draft', 'Draft')}</span>`;
}

function newEmpModeToggleHtml() {
    return `<div class="mb-3 d-flex justify-content-center">
        ${newEmpTabGroup('newEmpMode', [
            { value: 'register', label: __('register', 'Register') },
            { value: 'draft', label: __('save_as_draft', 'Save as Draft') }
        ], newEmpMode)}
    </div>`;
}

// Draft mode turns the footer into a single "Save as Draft" button (Next/Register, Back and
// Cancel hidden); Register mode restores the step's own buttons. confirmText: the step's
// confirm label in register mode ("Next" or "Register").
function newEmpWireModeToggle(confirmText) {
    const $deny = $(Swal.getDenyButton());
    const $cancel = $(Swal.getCancelButton());
    const hasDeny = $deny.is(':visible');
    const hasCancel = $cancel.is(':visible');
    const apply = () => {
        const isDraft = newEmpIsDraftMode();
        $(Swal.getTitle()).find('.new-emp-mode-badge').toggle(isDraft);
        Swal.getConfirmButton().textContent = isDraft ? __('save_as_draft', 'Save as Draft') : confirmText;
        if (hasDeny) $deny.toggle(!isDraft);
        if (hasCancel) $cancel.toggle(!isDraft);
    };
    $(Swal.getPopup()).find('input[name=newEmpMode]').on('change', function() {
        newEmpMode = this.value;
        Swal.resetValidationMessage();
        apply();
    });
    apply();
}

// Back from the first screen of a form to the type picker: saves what's typed so far first
// (it stays in the Draft Employees list as auto-saved), so the picker's draft count is current.
async function newEmpBackToPicker(type, w, collect) {
    Object.assign(w, collect());
    newEmpDraftSave(type, w);
    await newEmpDraftFlush(type, w);
    return true;
}

// "Save as Draft" from any step: keeps what's on screen, requires only the employee name (so
// the draft is recognizable in the list) and saves the draft right away.
async function newEmpConfirmDraft(type, w, collect) {
    Object.assign(w, collect());
    if (!String(w.name || '').trim()) {
        Swal.showValidationMessage(`${__('employee_name', 'Employee Name')} ${__('is_required', 'is required')}`);
        return false;
    }
    try {
        await newEmpDraftSaveNow(type, w, true);
    } catch (e) {
        Swal.showValidationMessage(e.message || 'Failed to save draft.');
        return false;
    }
    return { draft: true };
}

function newEmpNextEmpId(data) {
    return String(data.next_emp_id || '1');
}

// True once the form holds something worth keeping (the defaults alone don't create a draft).
function newEmpDraftHasContent(w) {
    return Object.keys(w).some(k => !['emp_id', 'sex', 'mar_status', 'avatarFile'].includes(k) && !k.startsWith('_')
        && w[k] !== null && w[k] !== undefined && String(w[k]) !== '');
}

// Form values only - internal "_" flags and the avatar File stay out of the stored JSON.
function newEmpDraftPayload(w) {
    const out = {};
    Object.keys(w).forEach(k => {
        if (!k.startsWith('_') && k !== 'avatarFile') out[k] = w[k];
    });
    return out;
}

// The server may hand a draft a different emp_id (the typed one was taken meanwhile) - show it.
function newEmpSyncEmpIdField(empId) {
    const $ce = $('#ceEmpId');
    if ($ce.length) {
        if (window.jQuery && typeof jQuery.fn.autoNumeric === 'function' && $ce.data('autoNumeric')) $ce.autoNumeric('set', empId);
        else $ce.val(empId);
    }
    const $mp = $('#mpEmpId');
    if ($mp.length && !$mp.is(':focus')) $mp.val(empId);
}

// Per-form save state: a pending debounce timer, and a promise chain so saves run one at a
// time (the first save creates the row - a second one must wait for its draft_id).
const newEmpSaveStates = new WeakMap();

function newEmpSaveState(w) {
    if (!newEmpSaveStates.has(w)) newEmpSaveStates.set(w, { timer: null, chain: Promise.resolve() });
    return newEmpSaveStates.get(w);
}

function newEmpDraftSaveNow(type, w, isSaved) {
    const st = newEmpSaveState(w);
    clearTimeout(st.timer);
    st.timer = null;
    const run = st.chain.then(async () => {
        if (w._done) return;
        if (!w._draft_id && !isSaved && !newEmpDraftHasContent(w)) return;
        const res = await newEmpPost({
            action: 'draft_save',
            draft_id: w._draft_id || '',
            emp_type: type,
            is_saved: isSaved ? 1 : 0,
            form_data: JSON.stringify(newEmpDraftPayload(w))
        });
        if (res.status !== 'success') throw new Error(res.message || 'Failed to save draft.');
        w._draft_id = res.draft_id;
        if (String(res.emp_id) !== String(w.emp_id)) {
            w.emp_id = String(res.emp_id);
            newEmpSyncEmpIdField(w.emp_id);
        }
    });
    st.chain = run.catch(() => {});
    return run;
}

function newEmpDraftSave(type, w) {
    if (w._done) return;
    const st = newEmpSaveState(w);
    clearTimeout(st.timer);
    st.timer = setTimeout(() => {
        newEmpDraftSaveNow(type, w, false).catch(e => console.error('Draft autosave failed:', e));
    }, 800);
}

// Runs a pending debounced save now and waits for every save in flight.
function newEmpDraftFlush(type, w) {
    const st = newEmpSaveState(w);
    if (st.timer) return newEmpDraftSaveNow(type, w, false).catch(() => {});
    return st.chain;
}

// Call right before sending the register request: waits for any save in flight (so the
// draft_id is known and sent along - the server deletes that row on success), then stops
// autosaving so a late blur/change can't recreate the draft after registration.
async function newEmpDraftBeforeRegister(type, w) {
    await newEmpDraftFlush(type, w);
    w._done = true;
    return w._draft_id || '';
}

// Saves `w` now (it may carry values from the previous step or the date picker) and again on
// every edit. Bound on the step's own <form> (replaced on each render) rather than the Swal
// popup, which SweetAlert2 reuses across steps - a popup-level handler would run an old step's
// collect() against the new step's DOM and overwrite `w` with undefined.
function newEmpWireDraft(formId, type, w, collect) {
    newEmpDraftSave(type, w);
    $('#' + formId).on('input change keyup blur', 'input, select', function() {
        Object.assign(w, collect());
        newEmpDraftSave(type, w);
    });
}

// SweetAlert2 toast. It is still the single SweetAlert2 popup, so it replaces whatever is
// open - await it before reopening another popup.
function newEmpToast(icon, title) {
    return Swal.fire({
        toast: true,
        position: 'top-end',
        icon,
        title,
        showConfirmButton: false,
        timer: 1800,
        timerProgressBar: true
    });
}

function newEmpDraftSavedToast(w) {
    const who = `${w.emp_id || ''} ${w.name || ''}`.trim();
    return newEmpToast('success', __('saved_as_draft', 'Saved as draft') + (who ? `: ${who}` : ''));
}

// Drafts kept in this browser's localStorage by the earlier version of this modal - uploaded
// once to the server table, then removed locally.
async function newEmpMigrateLocalDrafts() {
    let items = [];
    try {
        const list = JSON.parse(localStorage.getItem('newEmpDrafts') || '[]');
        if (Array.isArray(list)) list.forEach(d => { if (d && d.w) items.push({ type: d.type, w: d.w, saved: !!d.saved }); });
        ['company', 'man_power'].forEach(type => {
            const w = JSON.parse(localStorage.getItem('newEmpDraft_' + type) || 'null');
            if (w && typeof w === 'object') items.push({ type, w, saved: false });
        });
    } catch (e) {
        items = [];
    }
    if (!items.length) return;
    for (const item of items) {
        if (!newEmpDraftHasContent(item.w)) continue;
        try {
            await newEmpPost({
                action: 'draft_save',
                emp_type: item.type === 'man_power' ? 'man_power' : 'company',
                is_saved: item.saved ? 1 : 0,
                form_data: JSON.stringify(newEmpDraftPayload(item.w))
            });
        } catch (e) {
            return; // server unreachable - keep them locally and retry next time
        }
    }
    try {
        ['newEmpDrafts', 'newEmpDraft_company', 'newEmpDraft_man_power'].forEach(k => localStorage.removeItem(k));
    } catch (e) { /* ignore */ }
}

function newEmpDraftsTableHtml(drafts) {
    const typeLabel = t => t === 'company' ? __('almutlak_co_employee', 'Company Employee') : __('manpower_employee', 'Man Power');
    const rows = drafts.map(d => {
        const taken = String(d.taken) === '1';
        return `<tr data-draft-id="${escapeHtml(d.id)}">
            <td>${escapeHtml(d.emp_id)}${taken ? ` <span class="badge badge-danger" title="${escapeHtml(__('draft_emp_id_taken', 'This ID is already registered - a new ID will be assigned'))}">${__('taken', 'Taken')}</span>` : ''}</td>
            <td>${escapeHtml(d.name || '-')}${String(d.is_saved) === '1' ? '' : ` <span class="badge badge-secondary" title="${escapeHtml(__('draft_auto_saved_hint', 'Closed without saving - kept automatically'))}">${__('auto_saved', 'Auto-saved')}</span>`}</td>
            <td>${escapeHtml(typeLabel(d.emp_type))}</td>
            <td class="small">${escapeHtml(d.created_by_name || '-')}</td>
            <td class="small text-muted">${escapeHtml(d.updated_at || '')}</td>
            <td class="text-right text-nowrap">
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-primary new-emp-draft-open"><i class="fa fa-edit"></i> ${__('continue_draft', 'Continue')}</button>
                    <button type="button" class="btn btn-danger new-emp-draft-delete" title="${escapeHtml(__('delete', 'Delete'))}"><i class="fa fa-trash"></i></button>
                </div>
            </td>
        </tr>`;
    }).join('');
    return `<div class="table-responsive text-left" style="max-height:60vh;overflow-y:auto;">
        <table class="table table-sm table-hover mb-0">
            <thead><tr>
                <th>${__('employee_id', 'Employee ID')}</th>
                <th>${__('employee_name', 'Employee Name')}</th>
                <th>${__('type', 'Type')}</th>
                <th>${__('created_by', 'Created By')}</th>
                <th>${__('updated', 'Updated')}</th>
                <th></th>
            </tr></thead>
            <tbody>${rows}</tbody>
        </table>
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
        await newEmpMigrateLocalDrafts();
        const data = await newEmpPost({ action: 'get_form_data' });
        Swal.close();

        if (data.status !== 'success') {
            throw new Error(data.message || 'Failed to load form data.');
        }
        window.NEW_EMP_FORM_DATA = data;

        let selectedType = null;
        newEmpMode = 'register';
        const drafts = data.drafts || [];
        const cardCol = drafts.length ? 'col-4' : 'col-6';
        const cardStyle = 'cursor:pointer;color:#fff;padding:24px;text-align:center;border-radius:8px;height:100%;';
        await Swal.fire({
            title: __('add_new_employee_modal_title', 'Add New Employee'),
            html: `
                <div class="row text-left">
                    <div class="${cardCol}">
                        <div class="card-box new-emp-type-card" id="newEmpTypeCompany" style="${cardStyle}background:#727cf5;">
                            <i class="fa fa-building" style="font-size:28px;"></i>
                            <h5 class="mt-2 mb-0" style="color:#fff;">${__('almutlak_co_employee', 'Company Employee')}</h5>
                        </div>
                    </div>
                    <div class="${cardCol}">
                        <div class="card-box new-emp-type-card" id="newEmpTypeManPower" style="${cardStyle}background:#7a6fbe;">
                            <i class="fa fa-people-carry" style="font-size:28px;"></i>
                            <h5 class="mt-2 mb-0" style="color:#fff;">${__('manpower_employee', 'Man Power')}</h5>
                        </div>
                    </div>
                    ${drafts.length ? `
                    <div class="${cardCol}">
                        <div class="card-box new-emp-type-card" id="newEmpTypeDrafts" style="${cardStyle}background:#f7b84b;">
                            <i class="fa fa-file-alt" style="font-size:28px;"></i>
                            <h5 class="mt-2 mb-0" style="color:#fff;">${__('draft_employees', 'Draft Employees')} <span class="badge badge-light">${drafts.length}</span></h5>
                        </div>
                    </div>` : ''}
                </div>
            `,
            width: drafts.length ? '55%' : '40%',
            showConfirmButton: false,
            showCancelButton: true,
            cancelButtonText: __('cancel', 'Cancel'),
            allowOutsideClick: false,
            didOpen: () => {
                document.getElementById('newEmpTypeCompany').addEventListener('click', () => { selectedType = 'company'; Swal.close(); });
                document.getElementById('newEmpTypeManPower').addEventListener('click', () => { selectedType = 'man_power'; Swal.close(); });
                const draftsCard = document.getElementById('newEmpTypeDrafts');
                if (draftsCard) draftsCard.addEventListener('click', () => { selectedType = 'drafts'; Swal.close(); });
            }
        });

        if (selectedType === 'company') {
            openCompanyEmployeeModal(data);
        } else if (selectedType === 'man_power') {
            openManPowerEmployeeModal(data, { emp_id: newEmpNextEmpId(data) });
        } else if (selectedType === 'drafts') {
            await openNewEmpDraftsModal(data);
        }
    } catch (error) {
        Swal.close();
        await Swal.fire(__('error', 'Error'), error.message || 'Failed to load employee form.', 'error');
    }
}

// ---------------------------------------------------------------------------
// Draft Employees list (opened from the Draft card on the type picker)
// ---------------------------------------------------------------------------
async function openNewEmpDraftsModal(data) {
    let selectedDraftId = null;
    let deleteTarget = null;
    const result = await Swal.fire({
        title: __('draft_employees', 'Draft Employees'),
        html: newEmpDraftsTableHtml(data.drafts || []),
        width: '75%',
        showConfirmButton: false,
        showDenyButton: true,
        showCancelButton: true,
        denyButtonText: __('back', 'Back'),
        cancelButtonText: __('cancel', 'Cancel'),
        allowOutsideClick: false,
        didOpen: () => {
            const popup = Swal.getPopup();
            $(popup).on('click', '.new-emp-draft-open', function() {
                selectedDraftId = $(this).closest('tr').data('draft-id');
                Swal.close();
            });
            // SweetAlert2 shows one popup at a time, so the delete confirmation replaces this
            // list; the list is reopened afterwards (see below).
            $(popup).on('click', '.new-emp-draft-delete', function() {
                const $row = $(this).closest('tr');
                deleteTarget = {
                    id: $row.data('draft-id'),
                    label: [$row.children().eq(0).text().trim(), $row.children().eq(1).text().trim()].filter(Boolean).join(' - ')
                };
                Swal.close();
            });
        }
    });

    if (deleteTarget) {
        const confirm = await Swal.fire({
            icon: 'warning',
            title: __('confirm_delete_draft', 'Delete this draft?'),
            text: deleteTarget.label,
            showCancelButton: true,
            confirmButtonColor: '#fa5c7c',
            confirmButtonText: '<i class="fa fa-trash"></i> ' + __('delete', 'Delete'),
            cancelButtonText: __('cancel', 'Cancel'),
            reverseButtons: true,
            allowOutsideClick: false,
            showLoaderOnConfirm: true,
            preConfirm: async () => {
                try {
                    const res = await newEmpPost({ action: 'draft_delete', draft_id: deleteTarget.id });
                    if (res.status !== 'success') throw new Error(res.message || 'Failed to delete draft.');
                    return true;
                } catch (e) {
                    Swal.showValidationMessage(e.message || 'Request failed.');
                    return false;
                }
            }
        });
        if (confirm.isConfirmed) {
            data.drafts = (data.drafts || []).filter(d => String(d.id) !== String(deleteTarget.id));
            await newEmpToast('success', `${__('draft_deleted', 'Draft deleted')}: ${deleteTarget.label}`);
        }
        // Last one gone - nothing left to list, go back to the type picker.
        if (!(data.drafts || []).length) {
            openNewEmployeeTypeModal();
            return;
        }
        return openNewEmpDraftsModal(data);
    }

    if (result.isDenied) {
        openNewEmployeeTypeModal();
        return;
    }
    if (!selectedDraftId) return;

    Swal.fire({
        title: __('loading', 'Loading'),
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => Swal.showLoading()
    });
    const res = await newEmpPost({ action: 'draft_get', draft_id: selectedDraftId });
    if (res.status !== 'success') throw new Error(res.message || 'Failed to load draft.');
    const w = Object.assign({}, res.form_data, { _draft_id: res.draft_id });
    if (res.emp_type === 'man_power') {
        openManPowerEmployeeModal(data, w);
    } else {
        openCompanyStep1(data, w);
    }
}

// ---------------------------------------------------------------------------
// Company Employee - 3-step wizard (Basic / Employment / Other Information)
// ---------------------------------------------------------------------------
function openCompanyEmployeeModal(data) {
    const w = { emp_id: newEmpNextEmpId(data) };
    openCompanyStep1(data, w);
}

function newEmpCollectBasicInfo() {
    return {
        name: $('#ceName').val(),
        emp_id: newEmpGetNumeric('ceEmpId'),
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
    ${newEmpModeToggleHtml()}
    <form id="newCompEmpFormStep1" class="text-left">
        <div class="card-box">
        <div class="form-row">
            ${newEmpFieldset('employee_id', 'Employee ID', `<input type="text" id="ceEmpId" class="form-control autonumber readonly-data" data-v-max="9999" data-v-min="0" value="${escapeHtml(w.emp_id || data.next_emp_id)}" required readonly style="">`, 'col-md-2', 'fa-id-badge', true)}
            ${newEmpFieldset('employee_name', 'Employee Name', `<input type="text" id="ceName" class="form-control" value="${escapeHtml(w.name || '')}" required>`, 'col-md-4', 'fa-user', true)}
            ${newEmpFieldset('iqama_id', 'Iqama id', `<input type="text" id="ceIqama" class="form-control" value="${escapeHtml(w.iqama || '')}" required>`, 'col-md-2', 'fa-id-card', true)}
            ${newEmpFieldCard(`${__('iqama_id_expiry', 'Iqama / ID expiry')} <span class="text-danger">${__('in_gregorian', 'In Gregorian')} *</span>`, 'col-md-2', 'fa-calendar-alt', `<input type="text" id="ceIqamaExpG" class="form-control" value="${escapeHtml(w.iqama_exp_g || '')}" required>`)}
            ${newEmpFieldCard(`${__('iqama_id_expiry', 'Iqama / ID expiry')} <span class="text-danger">${__('in_hijri', 'In Hijri')} *</span>`, 'col-md-2', 'fa-calendar-alt', `<input type="text" id="ceIqamaExp" class="form-control" value="${escapeHtml(w.iqama_exp || '')}" required>`)}
            ${newEmpFieldset('passport_no', 'Passport no', `<input type="text" id="cePassportNumber" class="form-control" value="${escapeHtml(w.passport_number || '')}">`, 'col-md-3', 'fa-passport')}
            ${newEmpFieldset('passport_expiry', 'Passport expiry', `<input type="text" id="cePassportExp" class="form-control" value="${escapeHtml(w.passport_exp || '')}">`, 'col-md-3', 'fa-calendar-alt')}
            ${newEmpFieldset('mobile', 'Mobile', `<input type="text" id="ceMobile" class="form-control" value="${escapeHtml(w.mobile || '')}" required>`, 'col-md-3', 'fa-phone', true)}
            ${newEmpFieldset('emergency_mobile_no_label', 'Emergency Mobile No.', `<input type="text" id="ceEmgMobile" class="form-control" value="${escapeHtml(w.emg_mobile || '')}">`, 'col-md-3', 'fa-phone-alt')}
            ${newEmpFieldset('emergency_contact_name_label', 'Emergency Contact Name', `<input type="text" id="ceEmgName" class="form-control" value="${escapeHtml(w.emg_name || '')}">`, 'col-md-3', 'fa-user-friends')}
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
        title: __('almutlak_co_employee', 'Company Employee') + ' - ' + __('basic_information', 'Basic Information') + ' (1/3)' + newEmpModeBadge(),
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
            const ctx1 = { data, w, collect: newEmpCollectBasicInfo, reopen: openCompanyStep1 };
            newEmpWireHijriPair('ceIqamaExpG', 'ceIqamaExp', 'iqama_exp_g', 'iqama_exp', ctx1);
            newEmpWireHijriPair('ceDob', 'ceDobH', 'dob', 'dob_h', ctx1);
            newEmpWireDatepicker('cePassportExp', 'passport_exp', ctx1);
            newEmpApplyMasks('ceIqama', 'ceMobile');
            newEmpApplyAutoNumeric();
            newEmpFixMaskCaret('ceIqama');
            newEmpFixMaskCaret('ceMobile');
            newEmpWireModeToggle(__('next', 'Next'));
            newEmpWireDraft('newCompEmpFormStep1', 'company', w, newEmpCollectBasicInfo);
        },
        preConfirm: () => {
            if (newEmpIsDraftMode()) return newEmpConfirmDraft('company', w, newEmpCollectBasicInfo);
            if (!newEmpValidateRequired({
                ceName: __('employee_name', 'Employee Name'),
                ceEmpId: __('employee_id', 'Employee ID'),
                ceIqama: __('iqama_id', 'Iqama id'),
                ceIqamaExpG: __('iqama_id_expiry', 'Iqama / ID expiry'),
                ceIqamaExp: __('iqama_id_expiry', 'Iqama / ID expiry'),
                ceMobile: __('mobile', 'Mobile'),
                ceCountry: __('nationality', 'Nationality'),
                ceDob: __('date_of_birth', 'Date of birth'),
                ceDobH: __('date_of_birth', 'Date of birth')
            })) return false;
            return newEmpCollectBasicInfo();
        },
        preDeny: () => newEmpBackToPicker('company', w, newEmpCollectBasicInfo)
    }).then((result) => {
        if (result.isDenied) {
            openNewEmployeeTypeModal();
            return;
        }
        if (!result.isConfirmed) return;
        if (result.value && result.value.draft) {
            newEmpDraftSavedToast(w);
            return;
        }
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
    ${newEmpModeToggleHtml()}
    <form id="newCompEmpFormStep2" class="text-left">
        <div class="card-box">
        <div class="form-row">
            ${newEmpFieldset('department', 'Department', `<select id="ceDept" class="form-control new-emp-select2" required>${newEmpOptionsHtml(data.departments, 'id', 'dep_nme', 'dep_nme_ar', true, w.department)}</select>`, 'col-md-3', 'fa-sitemap', true)}
            ${newEmpFieldset('city_label', 'City', `<select id="ceCityId" class="form-control new-emp-select2" required>${newEmpOptionsHtml(data.cities, 'id', 'name_en', 'name_ar', true, w.city_id)}</select>`, 'col-md-3', 'fa-city', true)}
            ${newEmpFieldset('location_label', 'Location', `<select id="ceLocationId" class="form-control new-emp-select2" required><option value="">${__('select_a_city_first', 'Select a City First')}</option></select>`, 'col-md-3', 'fa-map-marker-alt', true)}
            ${newEmpFieldset('sub_department_label', 'Sub-Department', `<select id="ceSubDeptId" class="form-control new-emp-select2"><option value="">${__('select_a_department_first', 'Select a Department First')}</option></select>`, 'col-md-3', 'fa-sitemap')}
            ${newEmpFieldset('employee_type_label', 'Employee Type', `<select id="ceEmptype" class="form-control new-emp-select2" required><option value="">${__('select_option', 'Select')}</option>${emptypeOptions}</select>`, 'col-md-3', 'fa-user-tag', true)}
            ${newEmpFieldset('direct_supervisor', 'Direct Supervisor', `<select id="ceSupervisorId" class="form-control new-emp-select2" required><option value="">${__('select_option', 'Select')}</option>${(data.supervisors || []).map(s => `<option value="${String(s.emp_id).replace(/"/g, '&quot;')}" ${String(w.supervisor_id) === String(s.emp_id) ? 'selected' : ''}>${escapeHtml(s.name)} (${escapeHtml(s.emptype)})</option>`).join('')}</select>`, 'col-md-3', 'fa-user-tie', true)}
            ${newEmpFieldset('joining_date', 'Joining date', `<input type="text" id="ceJoiningDate" class="form-control" value="${escapeHtml(w.joining_date || '')}" required>`, 'col-md-3', 'fa-calendar-alt', true)}
            ${newEmpFieldset('sponsorship', 'Sponsorship', `<select id="ceEmpSupType" class="form-control new-emp-select2" required>${newEmpOptionsHtml(data.sponsorships, 'id', 'sponsor', 'sponsor_ar', true, w.emp_sup_type)}</select>`, 'col-md-3', 'fa-handshake', true)}
            ${newEmpFieldset('company_label', 'Company', `<select id="ceCompNo" class="form-control new-emp-select2" required>${newEmpOptionsHtml(data.companies, 'comp_id', 'comp_name', 'comp_name_ar', true, w.comp_no)}</select>`, 'col-md-3', 'fa-building', true)}
            ${newEmpFieldset('actual_job', 'Position', `<select id="ceActualJob" class="form-control new-emp-select2" required>${newEmpOptionsHtml(data.jobs, 'id', 'job', 'job_ar', true, w.actual_Job)}</select>`, 'col-md-3', 'fa-briefcase', true)}
            ${newEmpFieldset('contract_period', 'Contract period', `<select id="ceVacPeriod" class="form-control new-emp-select2" required>${newEmpOptionsHtml(data.contract_periods, 'id', 'period', 'period', true, w.vac_period)}</select>`, 'col-md-3', 'fa-file-contract', true)}
            ${newEmpFieldset('vacation_days', 'Vacation Days', `<input type="text" id="ceVacationDays" class="form-control" value="${escapeHtml(w.vacation_days || '')}" readonly required>`, 'col-md-3', 'fa-umbrella-beach', true)}
            ${newEmpFieldset('probation_period_label', 'Probation Period', `<select id="ceProbation" class="form-control new-emp-select2" required><option value="">${__('select_option', 'Select')}</option><option value="3" ${parseInt(w.probation, 10) === 3 ? 'selected' : ''}>3 ${__('months', 'Months')}</option><option value="6" ${parseInt(w.probation, 10) === 6 ? 'selected' : ''}>6 ${__('months', 'Months')}</option></select>`, 'col-md-3', 'fa-hourglass-half', true)}
        </div>
        </div>
    </form>`;

    Swal.fire({
        title: __('almutlak_co_employee', 'Company Employee') + ' - ' + __('employment_information', 'Employment Information') + ' (2/3)' + newEmpModeBadge(),
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
            newEmpWireDatepicker('ceJoiningDate', 'joining_date', { data, w, collect: newEmpCollectEmploymentInfo, reopen: openCompanyStep2 });
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
                    success: (res) => {
                        $('#ceVacationDays').val(res);
                        w.vacation_days = res;
                        newEmpDraftSave('company', w);
                    }
                });
            });
            newEmpWireModeToggle(__('next', 'Next'));
            newEmpWireDraft('newCompEmpFormStep2', 'company', w, newEmpCollectEmploymentInfo);
        },
        preConfirm: () => {
            if (newEmpIsDraftMode()) return newEmpConfirmDraft('company', w, newEmpCollectEmploymentInfo);
            if (!newEmpValidateRequired({
                ceDept: __('department', 'Department'),
                ceCityId: __('city_label', 'City'),
                ceLocationId: __('location_label', 'Location'),
                ceEmptype: __('employee_type_label', 'Employee Type'),
                ceSupervisorId: __('direct_supervisor', 'Direct Supervisor'),
                ceJoiningDate: __('joining_date', 'Joining date'),
                ceEmpSupType: __('sponsorship', 'Sponsorship'),
                ceCompNo: __('company_label', 'Company'),
                ceActualJob: __('actual_job', 'Position'),
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
        if (result.value && result.value.draft) {
            newEmpDraftSavedToast(w);
            return;
        }
        Object.assign(w, result.value);
        openCompanyStep3(data, w);
    });
}

function openCompanyStep3(data, w) {
    const isSaudi = w.country == 191;
    const collectOther = () => ({
        salary: newEmpGetNumeric('ceSalary'),
        bank_name: $('#ceBankName').val(),
        iban: $('#ceIban').val(),
        email: $('#ceEmail').val(),
        payment_type: $('#cePaymentType').val(),
        address: $('#ceAddress').val(),
        gosi: isSaudi ? $('#ceGosi').val() : ''
    });

    const html = `
    ${newEmpModeToggleHtml()}
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
        title: __('almutlak_co_employee', 'Company Employee') + ' - ' + __('other_information', 'Other Information') + ' (3/3)' + newEmpModeBadge(),
        html,
        width: '90%',
        showCancelButton: true,
        showDenyButton: true,
        confirmButtonColor: '#28a745',
        confirmButtonText: newEmpIsDraftMode() ? __('save_as_draft', 'Save as Draft') : __('yes_register', 'Register'),
        denyButtonText: __('back', 'Back'),
        cancelButtonText: __('cancel', 'Cancel'),
        allowOutsideClick: false,
        showLoaderOnConfirm: true,
        didOpen: () => {
            newEmpInitSelect2();
            newEmpApplyAutoNumeric();
            newEmpApplyIbanMask('ceIban');
            newEmpFixMaskCaret('ceIban');
            newEmpWireModeToggle(__('yes_register', 'Register'));
            newEmpWireDraft('newCompEmpFormStep3', 'company', w, collectOther);
        },
        preDeny: () => collectOther(),
        preConfirm: async () => {
            if (newEmpIsDraftMode()) return newEmpConfirmDraft('company', w, collectOther);
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

            Object.assign(w, collectOther());
            const draftId = await newEmpDraftBeforeRegister('company', w);
            const payload = Object.assign({}, newEmpDraftPayload(w), { action: 'create_company_employee', draft_id: draftId });

            try {
                const res = await newEmpPost(payload);
                if (res.status !== 'success') {
                    w._done = false; // registration refused - keep autosaving the draft
                    Swal.showValidationMessage(res.message || 'Failed to register employee.');
                    return false;
                }
                return res;
            } catch (e) {
                w._done = false;
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
        if (result.isConfirmed && result.value && result.value.draft) {
            newEmpDraftSavedToast(w);
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
function newEmpCollectManPower() {
    const avatarFile = document.getElementById('mpAvatar').files[0];
    return {
        name: $('#mpName').val(),
        emp_id: $('#mpEmpId').val(),
        iqama: $('#mpIqama').val(),
        iqama_exp_g: $('#mpIqamaExpG').val(),
        iqama_exp_hijri: $('#mpIqamaExpHijri').val(),
        country: $('#mpCountry').val(),
        department: $('#mpDepartment').val(),
        comp_no: $('#mpCompNo').val(),
        city_id: $('#mpCityId').val(),
        location_id: $('#mpLocationId').val(),
        sub_dept_id: $('#mpSubDeptId').val(),
        mobile: $('#mpMobile').val(),
        joining_date: $('#mpJoiningDate').val(),
        salary: newEmpGetNumeric('mpSalary'),
        dob: $('#mpDob').val(),
        sex: $('input[name=mpSex]:checked').val(),
        // File objects can't be reassigned into a fresh <input type="file">'s value for
        // security reasons; keep the picked File itself and restore it via DataTransfer
        // (see didOpen below) whenever this modal gets re-fired (e.g. after using the
        // date picker), so choosing an avatar doesn't get wiped out.
        avatarFile: avatarFile || (window.NEW_EMP_MP_AVATAR_FILE || null)
    };
}

function openManPowerEmployeeModal(data, w) {
    w = w || {};
    window.NEW_EMP_MP_AVATAR_FILE = w.avatarFile || null;
    const html = `
    ${newEmpModeToggleHtml()}
    <form id="newManPowerForm" class="text-left" enctype="multipart/form-data">
        <div class="card-box">
        <div class="form-row">
            ${newEmpFieldset('employee_name', 'Employee Name', `<input type="text" id="mpName" class="form-control" value="${escapeHtml(w.name || '')}" required>`, 'col-md-3', 'fa-user', true)}
            ${newEmpFieldset('employee_id', 'Employee ID', `<input type="text" id="mpEmpId" class="form-control" value="${escapeHtml(w.emp_id || data.next_emp_id)}" required>`, 'col-md-2', 'fa-id-badge', true)}
            ${newEmpFieldset('iqama_id', 'Iqama', `<input type="text" id="mpIqama" class="form-control" value="${escapeHtml(w.iqama || '')}" required>`, 'col-md-2', 'fa-id-card', true)}
            ${newEmpFieldCard(`${__('iqama_id_expiry', 'Iqama / ID expiry')} <span class="text-danger">${__('in_hijri', 'In Hijri')}</span>`, 'col-md-2', 'fa-calendar-alt', `<input type="text" id="mpIqamaExpHijri" class="form-control" value="${escapeHtml(w.iqama_exp_hijri || '')}">`)}
            ${newEmpFieldset('nationality', 'Nationality', `<select id="mpCountry" class="form-control new-emp-select2">${newEmpOptionsHtml(data.countries, 'id', 'name', 'name', true, w.country)}</select>`, 'col-md-3', 'fa-flag')}
            ${newEmpFieldset('department', 'Department', `<select id="mpDepartment" class="form-control new-emp-select2" required>${newEmpOptionsHtml(data.departments, 'id', 'dep_nme', 'dep_nme_ar', true, w.department)}</select>`, 'col-md-3', 'fa-sitemap', true)}
            ${newEmpFieldset('company_label', 'Company', `<select id="mpCompNo" class="form-control new-emp-select2" required>${newEmpOptionsHtml(data.companies, 'comp_id', 'comp_name', 'comp_name_ar', true, w.comp_no)}</select>`, 'col-md-3', 'fa-building', true)}
            ${newEmpFieldset('city_label', 'City', `<select id="mpCityId" class="form-control new-emp-select2">${newEmpOptionsHtml(data.cities, 'id', 'name_en', 'name_ar', true, w.city_id)}</select>`, 'col-md-3', 'fa-city')}
            ${newEmpFieldset('location_label', 'Location', `<select id="mpLocationId" class="form-control new-emp-select2"><option value="">${__('select_a_city_first', 'Select a City First')}</option></select>`, 'col-md-3', 'fa-map-marker-alt')}
            ${newEmpFieldset('sub_department_label', 'Sub-Department', `<select id="mpSubDeptId" class="form-control new-emp-select2"><option value="">${__('select_a_department_first', 'Select a Department First')}</option></select>`, 'col-md-3', 'fa-sitemap')}
            ${newEmpFieldset('mobile', 'Mobile No.', `<input type="text" id="mpMobile" class="form-control" value="${escapeHtml(w.mobile || '')}">`, 'col-md-3', 'fa-phone')}
            ${newEmpFieldset('joining_date', 'Joining Date', `<input type="text" id="mpJoiningDate" class="form-control" value="${escapeHtml(w.joining_date || '')}">`, 'col-md-3', 'fa-calendar-alt')}
            ${newEmpFieldset('salary', 'Salary', `<input type="text" id="mpSalary" class="form-control autonumber" data-v-max="25000" data-v-min="0" value="${escapeHtml(w.salary || '')}" required>`, 'col-md-3', 'fa-money-bill-wave', true)}
            ${newEmpFieldset('date_of_birth', 'Date of Birth', `<input type="text" id="mpDob" class="form-control" value="${escapeHtml(w.dob || '')}">`, 'col-md-3', 'fa-calendar-alt')}
            ${newEmpFieldCard(`${__('gender', 'Gender')} <span class="text-danger">*</span>`, 'col-md-3', 'fa-venus-mars', newEmpTabGroup('mpSex', [{ value: 'male', label: __('male', 'Male') }, { value: 'female', label: __('female', 'Female') }], w.sex === 'female' ? 'female' : 'male'))}
            ${newEmpFieldset('add_employee_picture', 'Add Employee Picture', `<input type="file" id="mpAvatar" class="form-control" accept="image/png,image/jpeg,image/gif">`, 'col-md-3', 'fa-camera')}
        </div>
        </div>
        <input type="hidden" id="mpIqamaExpG" value="${escapeHtml(w.iqama_exp_g || '')}">
    </form>`;

    Swal.fire({
        title: __('manpower_employee', 'Man Power') + newEmpModeBadge(),
        html,
        width: '75%',
        showCancelButton: true,
        showDenyButton: true,
        confirmButtonColor: '#28a745',
        confirmButtonText: newEmpIsDraftMode() ? __('save_as_draft', 'Save as Draft') : __('register', 'Register'),
        denyButtonText: __('back', 'Back'),
        cancelButtonText: __('cancel', 'Cancel'),
        preDeny: () => newEmpBackToPicker('man_power', w, newEmpCollectManPower),
        allowOutsideClick: false,
        showLoaderOnConfirm: true,
        didOpen: () => {
            newEmpInitSelect2();
            const mpCtx = { data, w, collect: newEmpCollectManPower, reopen: openManPowerEmployeeModal };
            newEmpWireHijriPair('mpIqamaExpG', 'mpIqamaExpHijri', 'iqama_exp_g', 'iqama_exp_hijri', mpCtx);
            newEmpWireDatepicker('mpJoiningDate', 'joining_date', mpCtx);
            newEmpApplyMasks('mpIqama', 'mpMobile');
            newEmpApplyAutoNumeric();
            newEmpFixMaskCaret('mpIqama');
            newEmpFixMaskCaret('mpMobile');

            if (w.city_id) newEmpPopulateLocations(w.city_id, $('#mpLocationId'), w.location_id);
            if (w.department) newEmpPopulateSubDepts(w.department, $('#mpSubDeptId'), w.sub_dept_id);
            $('#mpCityId').on('change', function() { newEmpPopulateLocations($(this).val(), $('#mpLocationId'), ''); });
            $('#mpDepartment').on('change', function() { newEmpPopulateSubDepts($(this).val(), $('#mpSubDeptId'), ''); });

            if (w.avatarFile && window.DataTransfer) {
                try {
                    const dt = new DataTransfer();
                    dt.items.add(w.avatarFile);
                    document.getElementById('mpAvatar').files = dt.files;
                } catch (e) { /* ignore: browser without DataTransfer file support */ }
            }
            newEmpWireModeToggle(__('register', 'Register'));
            newEmpWireDraft('newManPowerForm', 'man_power', w, newEmpCollectManPower);
        },
        preConfirm: async () => {
            if (newEmpIsDraftMode()) return newEmpConfirmDraft('man_power', w, newEmpCollectManPower);
            if (!newEmpValidateRequired({
                mpName: __('employee_name', 'Employee Name'),
                mpEmpId: __('employee_id', 'Employee ID'),
                mpIqama: __('iqama_id', 'Iqama'),
                mpDepartment: __('department', 'Department'),
                mpCompNo: __('company_label', 'Company'),
                mpSalary: __('salary', 'Salary')
            })) return false;
            Object.assign(w, newEmpCollectManPower());
            const draftId = await newEmpDraftBeforeRegister('man_power', w);

            const formData = new FormData();
            formData.append('action', 'create_man_power_employee');
            formData.append('draft_id', draftId);
            formData.append('name', $('#mpName').val());
            formData.append('emp_id', $('#mpEmpId').val());
            formData.append('iqama', $('#mpIqama').val());
            formData.append('iqama_exp_g', $('#mpIqamaExpG').val());
            formData.append('mobile', $('#mpMobile').val());
            formData.append('salary', newEmpGetNumeric('mpSalary'));
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
                const response = await fetch(NEW_EMP_AJAX_URL, {
                    method: 'POST',
                    body: formData
                });
                const res = await response.json();
                if (res.status !== 'success') {
                    w._done = false; // registration refused - keep autosaving the draft
                    Swal.showValidationMessage(res.message || 'Failed to register employee.');
                    return false;
                }
                return res;
            } catch (e) {
                w._done = false;
                Swal.showValidationMessage(e.message || 'Request failed.');
                return false;
            }
        }
    }).then((result) => {
        if (result.isDenied) {
            openNewEmployeeTypeModal();
            return;
        }
        if (result.isConfirmed && result.value && result.value.draft) {
            newEmpDraftSavedToast(w);
            return;
        }
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
