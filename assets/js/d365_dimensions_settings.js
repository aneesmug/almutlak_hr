/**
 * App Settings > D365 Config > Account Templates / Employee Dimensions (system admins only).
 * Backend: includes/ajaxFile/d365_dimensions.php (CSRF window.D365_DIM_CSRF).
 *
 * Account Templates: which D365 financial dimensions each company books payroll with
 *   (MainAccount is always first; segment order inside the account follows the D365 ledger format).
 * Employee Dimensions: the value of every template dimension per employee - payroll sync uses only these.
 */
(function () {
    'use strict';

    var URL = './includes/ajaxFile/d365_dimensions.php';
    var state = { meta: null, dimValues: {}, templates: {} };

    function t(key, def) { return (typeof window.__ === 'function') ? window.__(key, def) : def; }
    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function post(action, data) {
        var body = new URLSearchParams(Object.assign({ action: action, csrf: window.D365_DIM_CSRF || '' }, data || {}));
        return fetch(URL, { method: 'POST', body: body, credentials: 'same-origin' })
            .then(function (r) {
                if (r.redirected) throw new Error(t('d365_signed_out', 'You were signed out - reload the page'));
                return r.json();
            })
            .then(function (j) {
                if (!j.ok) throw new Error(j.error || 'Request failed');
                return j;
            });
    }

    function loading(host, text) {
        host.innerHTML = '<div class="sr-empty" style="padding:40px 0"><div class="loader" style="margin:0 auto 10px"></div>' + esc(text || t('loading', 'Loading...')) + '</div>';
    }

    function fail(host, err) {
        host.innerHTML = '<div class="sr-notice tone-red"><i class="mdi mdi-alert-circle-outline"></i> ' + esc(err.message || err) + '</div>';
    }

    function loadMeta(force) {
        if (state.meta && !force) return Promise.resolve(state.meta);
        return post('load').then(function (j) { state.meta = j; return j; });
    }

    function companyName(code) {
        var c = (state.meta.companies || []).find(function (x) { return x.code === code; });
        return c && c.name ? c.name : '';
    }

    function chip(dim, extraClass) {
        return '<span class="sr-chip ' + (extraClass || '') + '" style="margin:2px">' + esc(dim) + '</span>';
    }

    function warningsHtml(meta) {
        return (meta.warnings || []).map(function (w) {
            return '<div class="sr-notice tone-amber mb-2"><i class="mdi mdi-alert-outline"></i> ' + esc(w) + '</div>';
        }).join('');
    }

    // ------------------------------------------------------------ Account Templates

    function renderTemplates(host) {
        loading(host);
        loadMeta(true).then(function (meta) {
            var ledger = meta.ledger || [];
            var rows = (meta.companies || []).map(function (c) {
                var tpl = meta.templates[c.code] || [];
                var count = meta.counts[c.code] || 0;
                var tplHtml = tpl.length
                    ? chip('MainAccount', 'tone-slate') + tpl.map(function (d) {
                        return chip(d.dimension + (d.default ? ' = ' + d.default : ''), 'tone-indigo');
                    }).join('')
                    : '<span class="sr-pill tone-slate"><span class="sr-dot"></span>' + esc(t('d365_no_template', 'No template - uses D365 employment dims')) + '</span>';
                var structure = (c.structure || []).length
                    ? c.structure.map(function (d) { return chip(d, ledger.indexOf(d) === -1 ? 'tone-amber' : ''); }).join('')
                    : '<span class="text-muted">-</span>';
                return '<tr>' +
                    '<td><div class="sr-cell-title sr-mono">' + esc(c.code) + '</div><div class="sr-cell-sub">' + esc(c.name) + '</div></td>' +
                    '<td>' + tplHtml + '</td>' +
                    '<td>' + structure + '</td>' +
                    '<td class="text-center">' + count + '</td>' +
                    '<td class="text-right" style="white-space:nowrap"><button type="button" class="sr-btn sr-btn-sm sr-btn-ghost js-edit-tpl" data-company="' + esc(c.code) + '"><i class="mdi mdi-pencil"></i> ' + esc(t('edit', 'Edit')) + '</button>' +
                    '<button type="button" class="sr-btn sr-btn-sm sr-btn-ghost text-danger js-remove-co" data-company="' + esc(c.code) + '" data-count="' + count + '" title="' + esc(t('d365_remove_company_hint', 'Remove (closed company) - hidden in the app, D365 is not changed')) + '"><i class="mdi mdi-delete-outline"></i> ' + esc(t('remove', 'Remove')) + '</button></td>' +
                    '</tr>';
            }).join('');

            host.innerHTML = warningsHtml(meta) +
                '<div class="sr-notice tone-sky mb-3"><i class="mdi mdi-information-outline"></i> ' +
                esc(t('d365_templates_help', 'Choose the dimensions each company books payroll with. For a company with a template, payroll lines use ONLY the values set per employee under Employee Dimensions (Worker = employee ID automatically); a missing value stops that employee\'s sync with a message. Companies without a template keep using the D365 employment dimensions.')) +
                '<br><small>' + esc(t('d365_ledger_format', 'D365 ledger dimension format')) + ' (' + esc(meta.environment) + '): <b class="sr-mono">MainAccount-' + esc(ledger.join('-')) + '</b> - ' +
                esc(t('d365_ledger_format_order', 'account segments always follow this order.')) + '</small></div>' +
                '<div class="sr-table-wrap"><table class="sr-table"><thead><tr>' +
                '<th>' + esc(t('company', 'Company')) + '</th>' +
                '<th>' + esc(t('d365_account_template', 'Account template')) + '</th>' +
                '<th>' + esc(t('d365_structure_dims', 'Dimensions in D365 account structure')) + '</th>' +
                '<th class="text-center">' + esc(t('employees', 'Employees')) + '</th><th></th>' +
                '</tr></thead><tbody>' + (rows || '<tr><td colspan="5"><div class="sr-empty">' + esc(t('no_data', 'No companies')) + '</div></td></tr>') + '</tbody></table></div>' +
                ((meta.hidden || []).length
                    ? '<div class="sr-notice tone-slate mt-3" style="display:flex;flex-wrap:wrap;align-items:center;gap:6px"><i class="mdi mdi-eye-off-outline"></i> ' +
                      esc(t('d365_removed_companies', 'Removed companies')) + ': ' +
                      meta.hidden.map(function (code) {
                          return '<span class="sr-chip">' + esc(code) + ' <a href="#" class="js-restore-co" data-company="' + esc(code) + '" style="margin-left:4px">' + esc(t('restore', 'Restore')) + '</a></span>';
                      }).join('') + '</div>'
                    : '');

            host.querySelectorAll('.js-edit-tpl').forEach(function (b) {
                b.addEventListener('click', function () { editTemplate(this.dataset.company, host); });
            });
            host.querySelectorAll('.js-remove-co').forEach(function (b) {
                b.addEventListener('click', function () { removeCompany(this.dataset.company, +this.dataset.count, host); });
            });
            host.querySelectorAll('.js-restore-co').forEach(function (a) {
                a.addEventListener('click', function (e) {
                    e.preventDefault();
                    post('company_restore', { company: this.dataset.company })
                        .then(function () { renderTemplates(host); })
                        .catch(function (err) { Swal.fire('Error', err.message, 'error'); });
                });
            });
        }).catch(function (e) { fail(host, e); });
    }

    /** Remove a (closed) company from the app: hidden from templates and company pickers, its template deleted */
    function removeCompany(code, count, host) {
        Swal.fire({
            icon: 'warning',
            title: t('remove', 'Remove') + ' ' + esc(code) + '?',
            html: '<div class="text-left" style="font-size:14px">' +
                esc(t('d365_remove_company_info', 'The company is hidden from Account Templates, Employee Dimensions, Edit Employee and the transfer popups, and its account template is deleted. Nothing is changed in D365. You can restore it later.')) +
                (count ? '<div class="sr-notice tone-amber mt-2"><i class="mdi mdi-alert-outline"></i> ' + count + ' ' +
                    esc(t('d365_remove_company_emps', 'active employee(s) still have this company - move them first (Bulk change company on the D365 Employee Check page).')) + '</div>' : '') +
                '</div>',
            showCancelButton: true,
            confirmButtonText: t('remove', 'Remove'),
            confirmButtonColor: '#dc2626',
            cancelButtonText: t('cancel', 'Cancel'),
            allowOutsideClick: false,
            showLoaderOnConfirm: true,
            preConfirm: function () {
                return post('company_remove', { company: code }).catch(function (e) { Swal.showValidationMessage(e.message); });
            }
        }).then(function (r) {
            if (!r.isConfirmed || !r.value) return;
            Swal.fire({ icon: 'success', title: esc(code) + ' ' + t('removed', 'removed'), timer: 1400, showConfirmButton: false });
            renderTemplates(host);
        });
    }

    function editTemplate(code, host) {
        var meta = state.meta;
        var company = (meta.companies || []).find(function (c) { return c.code === code; }) || { code: code, structure: [] };
        var tpl = meta.templates[code] || [];
        var byDim = {};
        tpl.forEach(function (d) { byDim[d.dimension.toLowerCase()] = d; });
        // saved template order first (drag order), then the other dimensions in ledger format order
        var dims = tpl.map(function (d) { return d.dimension; });
        (meta.ledger || []).forEach(function (d) { if (dims.indexOf(d) === -1) dims.push(d); });

        var rows = dims.map(function (dim, i) {
            var cur = byDim[dim.toLowerCase()];
            var auto = dim.toLowerCase() === 'worker';
            var inStructure = (company.structure || []).indexOf(dim) !== -1;
            var inLedger = (meta.ledger || []).indexOf(dim) !== -1;
            return '<tr class="js-tpl-row" draggable="true">' +
                '<td style="width:78px;white-space:nowrap"><i class="mdi mdi-drag-vertical js-drag" style="cursor:grab;font-size:18px;color:var(--sr-muted);vertical-align:middle" title="' + esc(t('d365_drag_sort', 'Drag to sort')) + '"></i> ' +
                '<input type="checkbox" class="js-tpl-on" data-dim="' + esc(dim) + '" id="tplDim' + i + '"' + (cur ? ' checked' : '') + ' style="vertical-align:middle"></td>' +
                '<td><label for="tplDim' + i + '" class="mb-0"><b>' + esc(dim) + '</b></label>' +
                (inStructure ? ' <span class="sr-pill tone-green" title="' + esc(t('d365_in_structure', 'In this company\'s D365 account structure')) + '"><span class="sr-dot"></span>' + esc(t('d365_structure', 'structure')) + '</span>' : '') +
                (!inLedger ? ' <span class="sr-pill tone-red"><span class="sr-dot"></span>' + esc(t('d365_not_in_ledger_format', 'not in ledger format')) + '</span>' : '') +
                '</td>' +
                '<td>' + (auto
                    ? '<span class="text-muted">' + esc(t('d365_worker_auto', 'Automatic = employee ID')) + '</span>'
                    : '<input type="text" class="form-control form-control-sm js-tpl-default" data-dim="' + esc(dim) + '" maxlength="40" placeholder="' + esc(t('d365_default_optional', 'Default (optional)')) + '" value="' + esc(cur ? cur.default : '') + '">') +
                '</td><td style="width:64px;white-space:nowrap">' +
                '<button type="button" class="sr-btn sr-btn-sm sr-btn-ghost js-up" style="padding:2px 6px" title="' + esc(t('move_up', 'Move up')) + '"><i class="mdi mdi-arrow-up"></i></button>' +
                '<button type="button" class="sr-btn sr-btn-sm sr-btn-ghost js-down" style="padding:2px 6px" title="' + esc(t('move_down', 'Move down')) + '"><i class="mdi mdi-arrow-down"></i></button>' +
                '</td></tr>';
        }).join('');

        Swal.fire({
            title: esc(code) + ' - ' + esc(t('d365_account_template', 'Account template')),
            html: '<div class="sr-page text-left">' +
                '<p class="text-muted" style="font-size:13px">' + esc(t('d365_template_popup_help', 'MainAccount is always included. Tick the dimensions this company uses; a default fills employees that have no own value (e.g. Company = 01). Drag the rows (or use the arrows) to set the order of the dimensions for this company.')) + '</p>' +
                '<div class="sr-table-wrap"><table class="sr-table"><thead><tr><th></th><th>' + esc(t('dimension', 'Dimension')) + '</th><th>' + esc(t('default', 'Default')) + '</th><th></th></tr></thead>' +
                '<tbody><tr><td><i class="mdi mdi-lock-outline" style="font-size:16px;color:var(--sr-muted);vertical-align:middle"></i> <input type="checkbox" checked disabled style="vertical-align:middle"></td><td><b>MainAccount</b></td><td><span class="text-muted">' + esc(t('d365_from_mapping', 'From payroll account mapping')) + '</span></td><td></td></tr></tbody>' +
                '<tbody id="tplSortable">' + rows + '</tbody></table></div>' +
                ((company.structure || []).length ? '<button type="button" class="sr-btn sr-btn-sm sr-btn-ghost mt-2" id="tplUseStructure"><i class="mdi mdi-auto-fix"></i> ' + esc(t('d365_use_structure', 'Tick dimensions of the D365 account structure')) + '</button>' : '') +
                '</div>',
            width: 720,
            customClass: { popup: 'sr-addline-popup' },
            showCancelButton: true,
            showDenyButton: tpl.length > 0,
            denyButtonText: t('d365_remove_template', 'Remove template'),
            confirmButtonText: t('save', 'Save'),
            cancelButtonText: t('cancel', 'Cancel'),
            allowOutsideClick: false,
            didOpen: function (popup) {
                // drag & drop (HTML5) + arrow buttons to sort the dimension rows; saved order = row order
                var body = popup.querySelector('#tplSortable');
                var dragging = null;
                body.querySelectorAll('.js-tpl-row').forEach(function (tr) {
                    tr.addEventListener('dragstart', function (e) {
                        if (/^(INPUT|BUTTON)$/.test(e.target.tagName)) { e.preventDefault(); return; }
                        dragging = tr;
                        tr.style.opacity = '.45';
                        e.dataTransfer.effectAllowed = 'move';
                        try { e.dataTransfer.setData('text/plain', ''); } catch (x) {}
                    });
                    tr.addEventListener('dragend', function () { tr.style.opacity = ''; dragging = null; });
                    tr.addEventListener('dragover', function (e) {
                        if (!dragging || dragging === tr) return;
                        e.preventDefault();
                        var r = tr.getBoundingClientRect();
                        body.insertBefore(dragging, (e.clientY - r.top) > r.height / 2 ? tr.nextSibling : tr);
                    });
                    tr.querySelector('.js-up').addEventListener('click', function () {
                        if (tr.previousElementSibling) body.insertBefore(tr, tr.previousElementSibling);
                    });
                    tr.querySelector('.js-down').addEventListener('click', function () {
                        if (tr.nextElementSibling) body.insertBefore(tr.nextElementSibling, tr);
                    });
                });
                var btn = popup.querySelector('#tplUseStructure');
                if (btn) btn.addEventListener('click', function () {
                    popup.querySelectorAll('.js-tpl-on').forEach(function (cb) {
                        cb.checked = (company.structure || []).indexOf(cb.dataset.dim) !== -1;
                    });
                });
            },
            preConfirm: function () {
                var popup = Swal.getPopup();
                var out = [];
                try {
                    popup.querySelectorAll('.js-tpl-on').forEach(function (cb) {
                        if (!cb.checked) return;
                        var def = popup.querySelector('.js-tpl-default[data-dim="' + cb.dataset.dim + '"]');
                        var v = def ? def.value.trim() : '';
                        if (v.indexOf('-') !== -1) throw new Error(t('d365_no_dash', 'Values cannot contain "-"') + ': ' + v);
                        out.push({ dimension: cb.dataset.dim, default: v });
                    });
                } catch (e) {
                    Swal.showValidationMessage(e.message);
                    return false;
                }
                if (!out.length) {
                    Swal.showValidationMessage(t('d365_pick_dimension', 'Tick at least one dimension, or use Remove template'));
                    return false;
                }
                return post('save_template', { company: code, dims: JSON.stringify(out) })
                    .catch(function (e) { Swal.showValidationMessage(e.message); });
            }
        }).then(function (r) {
            if (r.isConfirmed) {
                Swal.fire({ icon: 'success', title: t('saved', 'Saved'), timer: 1200, showConfirmButton: false });
                renderTemplates(host);
            } else if (r.isDenied) {
                Swal.fire({
                    icon: 'warning',
                    title: t('d365_remove_template_q', 'Remove the template of') + ' ' + esc(code) + '?',
                    text: t('d365_remove_template_info', 'Payroll of this company goes back to the D365 employment dimensions. Employee values stay saved.'),
                    showCancelButton: true,
                    confirmButtonText: t('d365_remove_template', 'Remove template'),
                    cancelButtonText: t('cancel', 'Cancel'),
                    allowOutsideClick: false
                }).then(function (c) {
                    if (!c.isConfirmed) return;
                    post('save_template', { company: code, dims: '[]' })
                        .then(function () { renderTemplates(host); })
                        .catch(function (e) { Swal.fire('Error', e.message, 'error'); });
                });
            }
        });
    }

    // ------------------------------------------------------------ Employee Dimensions
    // All active employees in one list. Each row: payroll company select + inputs for the dimensions
    // of THAT company's template (changing the company redraws the inputs). Filter by company on top.

    function loadDimValues(dim) {
        if (state.dimValues[dim]) return Promise.resolve(state.dimValues[dim]);
        return post('dim_values', { dimension: dim })
            .then(function (j) { state.dimValues[dim] = j.values || []; return state.dimValues[dim]; })
            .catch(function () { state.dimValues[dim] = []; return []; });
    }

    function isWorker(dim) { return String(dim).toLowerCase() === 'worker'; }

    function renderEmployees(host) {
        loading(host);
        loadMeta(true).then(function (meta) {
            // The list shows only employees whose payroll company has a template ("applied");
            // the rest are added through the "Add employees" popup.
            var tplCodes = Object.keys(meta.templates || {}).sort();
            var saved = '*';
            try { saved = localStorage.getItem('d365_dim_company') || '*'; } catch (e) {}
            if (saved !== '*' && tplCodes.indexOf(saved) === -1) saved = '*';
            var applied = 0, unapplied = 0;
            Object.keys(meta.counts || {}).forEach(function (k) {
                if (tplCodes.indexOf(k) !== -1) applied += meta.counts[k]; else unapplied += meta.counts[k];
            });
            var opt = function (v, label) { return '<option value="' + esc(v) + '"' + (v === saved ? ' selected' : '') + '>' + esc(label) + '</option>'; };
            var options = opt('*', t('d365_all_applied', 'All companies with a template') + ' (' + applied + ')') +
                tplCodes.map(function (code) {
                    return opt(code, code + (companyName(code) ? ' - ' + companyName(code) : '') + ' (' + (meta.counts[code] || 0) + ')');
                }).join('');
            var noTpl = !Object.keys(meta.templates || {}).length
                ? '<div class="sr-notice tone-amber mb-2"><i class="mdi mdi-alert-outline"></i> ' +
                  esc(t('d365_no_templates_yet', 'No account template yet - create one under Account Templates first.')) +
                  ' <button type="button" class="sr-btn sr-btn-sm sr-btn-primary ml-2" id="dimGoTemplates"><i class="mdi mdi-arrow-right"></i> ' +
                  esc(t('d365_account_templates', 'Account Templates')) + '</button></div>'
                : '';
            var autoNote = meta.auto_filled
                ? '<div class="sr-notice tone-green mb-2"><i class="mdi mdi-auto-fix"></i> ' + meta.auto_filled + ' ' +
                  esc(t('d365_pc_auto_filled', 'employees got their Payroll Company automatically (D365 employment company, else the company mapped to their app company). Change any of them in the Payroll Company column.')) + '</div>'
                : '';
            host.innerHTML = warningsHtml(meta) + autoNote + noTpl +
                '<div class="sr-toolbar mb-2" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center">' +
                '<select class="form-control form-control-sm" id="dimCompany" style="max-width:340px">' + options + '</select>' +
                '<div class="sr-search" style="flex:1;min-width:180px"><i class="mdi mdi-magnify"></i><input type="search" id="dimSearch" placeholder="' + esc(t('search', 'Search')) + '..."></div>' +
                '<label class="sr-check mb-0"><input type="checkbox" id="dimOnlyMissing"> ' + esc(t('d365_only_not_ready', 'Only not ready')) + '</label>' +
                '<button type="button" class="sr-btn sr-btn-sm sr-btn-success" id="dimAdd"' + (tplCodes.length ? '' : ' disabled') + '><i class="mdi mdi-account-plus"></i> ' +
                esc(t('d365_add_employees', 'Add employees')) + ' <span class="sr-chip" style="margin-left:4px" title="' + esc(t('d365_not_applied', 'Without an applied template')) + '">' + unapplied + '</span></button>' +
                '<button type="button" class="sr-btn sr-btn-sm sr-btn-ghost" id="dimFill"><i class="mdi mdi-cloud-download-outline"></i> ' + esc(t('d365_fill_blanks', 'Fill blanks from D365')) + '</button>' +
                '<button type="button" class="sr-btn sr-btn-sm sr-btn-success" id="dimExcel"' + (tplCodes.length ? '' : ' disabled') + '><i class="mdi mdi-file-excel-outline"></i> ' + esc(t('d365_excel_import', 'Excel Import')) + '</button>' +
                '<button type="button" class="sr-btn sr-btn-sm sr-btn-primary" id="dimSaveAll" disabled><i class="mdi mdi-content-save"></i> ' + esc(t('d365_save_changed', 'Save changed')) + ' (<span id="dimDirtyCount">0</span>)</button>' +
                '</div><div id="dimGrid"></div>';
            var go = host.querySelector('#dimGoTemplates');
            if (go) go.addEventListener('click', function () {
                var tab = document.querySelector('#d365-config-sub-nav a[data-sub-tab="templates"]');
                if (tab) tab.click();
            });
            var grid = host.querySelector('#dimGrid');
            var sel = host.querySelector('#dimCompany');
            sel.addEventListener('change', function () {
                if (grid.querySelector('tr.is-dirty') && !confirm(t('d365_discard_changes', 'Discard unsaved changes?'))) {
                    this.value = grid.dataset.company;
                    return;
                }
                try { localStorage.setItem('d365_dim_company', this.value); } catch (e) {}
                loadGrid(host, grid, this.value);
            });
            host.querySelector('#dimSearch').addEventListener('input', function () { filterGrid(host); });
            host.querySelector('#dimOnlyMissing').addEventListener('change', function () { filterGrid(host); });
            host.querySelector('#dimSaveAll').addEventListener('click', function () { saveAll(host); });
            host.querySelector('#dimFill').addEventListener('click', function () { fillFromD365(host, grid, sel.value); });
            host.querySelector('#dimExcel').addEventListener('click', function () {
                if (grid.querySelector('tr.is-dirty')) {
                    Swal.fire('', t('d365_save_first', 'Save or discard your changes first'), 'info');
                    return;
                }
                excelDialog(host);
            });
            host.querySelector('#dimAdd').addEventListener('click', function () {
                if (grid.querySelector('tr.is-dirty')) {
                    Swal.fire('', t('d365_save_first', 'Save or discard your changes first'), 'info');
                    return;
                }
                addEmployees(host, sel.value !== '*' ? sel.value : (tplCodes[0] || ''));
            });
            loadGrid(host, grid, saved);
        }).catch(function (e) { fail(host, e); });
    }

    /**
     * "Add employees" popup: employees whose payroll company has NO template yet (none known, or a company
     * without template). Pick a company with a template + its dimension values, tick employees, save.
     */
    function addEmployees(host, firstCompany) {
        var tplCodes = Object.keys(state.meta.templates || {}).sort();
        Swal.fire({ title: t('loading', 'Loading...'), allowOutsideClick: false, didOpen: function () { Swal.showLoading(); } });
        post('employees', { company: '*', scope: 'unapplied' }).then(function (j) {
            state.templates = j.templates || state.templates;
            var dims = {};
            tplCodes.forEach(function (c) {
                (state.templates[c] || []).forEach(function (d) { if (!isWorker(d.dimension)) dims[d.dimension] = true; });
            });
            return Promise.all(Object.keys(dims).map(loadDimValues)).then(function () { return j.employees; });
        }).then(function (emps) {
            var reason = function (r) {
                if (!r.company) return '<span class="sr-pill tone-slate"><span class="sr-dot"></span>' + esc(t('d365_no_company_short', 'No company')) + '</span>';
                return '<span class="sr-pill tone-amber"><span class="sr-dot"></span>' + esc(r.company + (r.payroll_company ? '' : ' (' + t('payroll_company_auto_short', 'Auto') + ')') + ' - ' + t('d365_no_template_short', 'no template')) + '</span>';
            };
            var rows = emps.map(function (r) {
                return '<tr data-search="' + esc((r.emp_id + ' ' + r.name + ' ' + r.company).toLowerCase()) + '">' +
                    '<td style="width:34px"><input type="checkbox" class="js-add-emp" value="' + esc(r.emp_id) + '"></td>' +
                    '<td><div class="sr-cell-title">' + esc(r.name) + '</div><div class="sr-cell-sub sr-mono">' + esc(r.emp_id) + '</div></td>' +
                    '<td>' + reason(r) + '</td></tr>';
            }).join('');
            var companyOpts = tplCodes.map(function (c) {
                return '<option value="' + esc(c) + '"' + (c === firstCompany ? ' selected' : '') + '>' + esc(c + (companyName(c) ? ' - ' + companyName(c) : '')) + '</option>';
            }).join('');

            Swal.fire({
                title: t('d365_add_employees', 'Add employees'),
                width: 860,
                customClass: { popup: 'sr-addline-popup' },
                allowOutsideClick: false,
                showCancelButton: true,
                confirmButtonText: t('d365_add_selected', 'Add selected'),
                cancelButtonText: t('cancel', 'Cancel'),
                html: '<div class="sr-page text-left">' + datalists() +
                    '<div class="sr-fsec" style="margin-bottom:10px">' +
                    '<label class="sr-cell-sub mb-1">' + esc(t('payroll_company_label', 'Payroll Company')) + '</label>' +
                    '<select class="form-control form-control-sm" id="addCompany">' + companyOpts + '</select>' +
                    '<div id="addDims" style="margin-top:8px"></div>' +
                    '<small class="text-muted">' + esc(t('d365_add_values_help', 'Values are applied to every ticked employee; leave blank to fill per employee later (blank never clears an existing value).')) + '</small>' +
                    '</div>' +
                    '<div style="display:flex;gap:8px;align-items:center;margin-bottom:6px">' +
                    '<div class="sr-search" style="flex:1"><i class="mdi mdi-magnify"></i><input type="search" id="addSearch" placeholder="' + esc(t('search', 'Search')) + '..."></div>' +
                    '<label class="sr-check mb-0"><input type="checkbox" id="addAll"> ' + esc(t('d365_select_visible', 'Select visible')) + '</label>' +
                    '<span class="sr-chip" id="addCount">0 ' + esc(t('selected', 'selected')) + '</span></div>' +
                    '<div class="sr-table-wrap" style="max-height:340px;overflow:auto"><table class="sr-table"><thead><tr><th></th><th>' + esc(t('employee', 'Employee')) + '</th><th>' +
                    esc(t('d365_why_not_applied', 'Why no template')) + ' (' + emps.length + ')</th></tr></thead><tbody>' +
                    (rows || '<tr><td colspan="3"><div class="sr-empty">' + esc(t('d365_all_applied_done', 'Every active employee already has an applied template')) + '</div></td></tr>') +
                    '</tbody></table></div></div>',
                didOpen: function (popup) {
                    var drawDims = function () {
                        var tpl = state.templates[popup.querySelector('#addCompany').value] || [];
                        var box = popup.querySelector('#addDims');
                        if (window.jQuery && jQuery.fn.select2) jQuery(box).find('select.select2-hidden-accessible').select2('destroy');
                        box.innerHTML = '<div style="display:flex;flex-wrap:wrap;gap:8px">' + tpl.map(function (d) {
                            if (isWorker(d.dimension)) return '<div><div class="sr-cell-sub">Worker</div><span class="sr-chip">' + esc(t('d365_worker_auto', 'Automatic = employee ID')) + '</span></div>';
                            return '<div style="width:220px"><div class="sr-cell-sub">' + esc(d.dimension) + '</div><select class="form-control form-control-sm js-add-dim" style="width:220px"' +
                                ' data-dim="' + esc(d.dimension) + '">' + dimOptions(d.dimension, '', d.default) + '</select></div>';
                        }).join('') + '</div>';
                        initDimSelects(box, popup, function () {});
                    };
                    var count = function () {
                        popup.querySelector('#addCount').textContent = popup.querySelectorAll('.js-add-emp:checked').length + ' ' + t('selected', 'selected');
                    };
                    popup.querySelector('#addCompany').addEventListener('change', drawDims);
                    popup.querySelector('#addSearch').addEventListener('input', function () {
                        var q = this.value.toLowerCase();
                        popup.querySelectorAll('tbody tr[data-search]').forEach(function (tr) {
                            tr.style.display = tr.dataset.search.indexOf(q) !== -1 ? '' : 'none';
                        });
                        popup.querySelector('#addAll').checked = false;
                    });
                    popup.querySelector('#addAll').addEventListener('change', function () {
                        var on = this.checked;
                        popup.querySelectorAll('tbody tr[data-search]').forEach(function (tr) {
                            if (tr.style.display !== 'none') tr.querySelector('.js-add-emp').checked = on;
                        });
                        count();
                    });
                    popup.querySelectorAll('.js-add-emp').forEach(function (cb) { cb.addEventListener('change', count); });
                    drawDims();
                },
                preConfirm: function () {
                    var popup = Swal.getPopup();
                    var ids = Array.prototype.map.call(popup.querySelectorAll('.js-add-emp:checked'), function (cb) { return cb.value; });
                    if (!ids.length) {
                        Swal.showValidationMessage(t('d365_pick_employee', 'Tick at least one employee'));
                        return false;
                    }
                    var values = {};
                    var bad = null;
                    popup.querySelectorAll('.js-add-dim').forEach(function (i) {
                        var v = i.value.trim();
                        if (v.indexOf('-') !== -1) bad = v;
                        if (v) values[i.dataset.dim] = v;
                    });
                    if (bad) {
                        Swal.showValidationMessage(t('d365_no_dash', 'Values cannot contain "-"') + ': ' + bad);
                        return false;
                    }
                    return post('save_employees', {
                        emp_ids: JSON.stringify(ids),
                        payroll_company: popup.querySelector('#addCompany').value,
                        values: JSON.stringify(values)
                    }).catch(function (e) { Swal.showValidationMessage(e.message); });
                }
            }).then(function (r) {
                if (!r.isConfirmed || !r.value) return;
                Swal.fire({ icon: 'success', title: t('saved', 'Saved'), text: r.value.saved + ' ' + t('d365_employees_added', 'employees added'), timer: 1600, showConfirmButton: false });
                renderEmployees(host);
            });
        }).catch(function (e) { Swal.fire('Error', e.message, 'error'); });
    }

    function companySelect(row) {
        var html = '<option value="">' + esc(t('payroll_company_auto_short', 'Auto') + (row.employment_company ? ' (' + row.employment_company + ')' : ' - ' + t('d365_unknown', 'unknown'))) + '</option>';
        (state.meta.companies || []).forEach(function (c) {
            html += '<option value="' + esc(c.code) + '"' + (row.payroll_company === c.code ? ' selected' : '') + '>' + esc(c.code) + '</option>';
        });
        return '<select class="form-control form-control-sm js-pc" style="min-width:110px">' + html + '</select>';
    }

    /** Company whose template applies to a row right now (selected payroll company, else D365 employment company) */
    function rowCompany(tr) {
        return tr.querySelector('.js-pc').value || tr.dataset.empCompany || '';
    }

    /** Inputs for the template of the row's company; keeps typed values (also of other dims) in tr._values */
    function dimsHtml(tr) {
        var company = rowCompany(tr);
        var tpl = state.templates[company] || [];
        if (!company) return '<span class="text-muted">' + esc(t('d365_pick_company', 'Pick the payroll company')) + '</span>';
        if (!tpl.length) return '<span class="text-muted">' + esc(company + ': ' + t('d365_no_template', 'No template - uses D365 employment dims')) + '</span>';
        return '<div style="display:flex;flex-wrap:wrap;gap:6px">' + tpl.map(function (d) {
            if (isWorker(d.dimension)) {
                return '<div><div class="sr-cell-sub">Worker</div><span class="sr-chip sr-mono">' + esc(tr.dataset.emp) + '</span></div>';
            }
            var v = tr._values[d.dimension] || '';
            return '<div style="width:200px"><div class="sr-cell-sub">' + esc(d.dimension) + '</div>' +
                '<select class="form-control form-control-sm js-dim" style="width:200px" data-dim="' + esc(d.dimension) + '">' +
                dimOptions(d.dimension, v, d.default) + '</select></div>';
        }).join('') + '</div>';
    }

    /** <option>s of a dimension from D365 (suspended ones only when already selected); '' = blank / template default */
    function dimOptions(dim, selected, def) {
        var found = false;
        var html = '<option value="">' + esc(def ? t('default', 'Default') + ': ' + def : '') + '</option>' +
            (state.dimValues[dim] || []).filter(function (v) { return v.active || v.value === selected; }).map(function (v) {
                var sel = v.value === selected;
                found = found || sel;
                return '<option value="' + esc(v.value) + '"' + (sel ? ' selected' : '') + '>' + esc(v.value + (v.name ? ' - ' + v.name : '')) + '</option>';
            }).join('');
        if (selected && !found) { // value D365 no longer has
            html += '<option value="' + esc(selected) + '" selected>' + esc(selected + ' (' + t('d365_not_in_d365', 'not in D365') + ')') + '</option>';
        }
        return html;
    }

    /** select2 on dimension selects; onChange(select) after every pick/clear */
    function initDimSelects(scope, parent, onChange) {
        if (!window.jQuery || !jQuery.fn.select2) {
            scope.querySelectorAll('select.js-dim, select.js-add-dim').forEach(function (s) { s.addEventListener('change', function () { onChange(s); }); });
            return;
        }
        jQuery(scope).find('select.js-dim, select.js-add-dim').each(function () {
            var $s = jQuery(this);
            $s.select2({
                width: $s.css('width') || '200px',
                placeholder: t('select', 'Select...'),
                allowClear: true,
                dropdownParent: parent ? jQuery(parent) : jQuery(document.body)
            }).on('change', function () { onChange(this); });
        });
    }

    function datalists() {
        return Object.keys(state.dimValues).map(function (dim) {
            return '<datalist id="dl_' + esc(dim) + '">' + (state.dimValues[dim] || []).filter(function (v) { return v.active; }).map(function (v) {
                return '<option value="' + esc(v.value) + '">' + esc(v.name) + '</option>';
            }).join('') + '</datalist>';
        }).join('');
    }

    function loadGrid(host, grid, company) {
        grid.dataset.company = company;
        loading(grid);
        post('employees', { company: company, scope: 'applied' }).then(function (j) {
            state.templates = j.templates || {};
            var dims = {};
            Object.keys(state.templates).forEach(function (c) {
                state.templates[c].forEach(function (d) { if (!isWorker(d.dimension)) dims[d.dimension] = true; });
            });
            return Promise.all(Object.keys(dims).map(loadDimValues)).then(function () {
                var head = '<th>' + esc(t('employee', 'Employee')) + '</th><th>' + esc(t('payroll_company_label', 'Payroll Company')) + '</th>' +
                    '<th>' + esc(t('d365_dimensions', 'D365 Dimensions')) + '</th><th>' + esc(t('status', 'Status')) + '</th><th></th>';
                var body = j.employees.map(function (r) {
                    return '<tr data-emp="' + esc(r.emp_id) + '" data-pc="' + esc(r.payroll_company) + '" data-emp-company="' + esc(r.employment_company) + '" data-search="' + esc((r.emp_id + ' ' + r.name).toLowerCase()) + '">' +
                        '<td><div class="sr-cell-title">' + esc(r.name) + '</div><div class="sr-cell-sub sr-mono"><a href="view_employee.php?emp_id=' + encodeURIComponent(r.emp_id) + '" target="_blank">' + esc(r.emp_id) + '</a></div></td>' +
                        '<td>' + companySelect(r) + '</td><td class="js-dims"></td><td class="js-status"></td>' +
                        '<td><button type="button" class="sr-btn sr-btn-sm sr-btn-success js-save" style="visibility:hidden" title="' + esc(t('save', 'Save')) + '"><i class="mdi mdi-check"></i></button></td></tr>';
                }).join('');
                grid.innerHTML = datalists() + '<div class="sr-table-wrap"><table class="sr-table"><thead><tr>' + head + '</tr></thead><tbody>' +
                    (body || '<tr><td colspan="5"><div class="sr-empty">' + esc(t('d365_no_employees_company', 'No active employees here')) + '</div></td></tr>') +
                    '</tbody></table></div>';
                j.employees.forEach(function (r) {
                    var tr = grid.querySelector('tr[data-emp="' + CSS.escape(r.emp_id) + '"]');
                    if (!tr) return;
                    tr._orig = Object.assign({}, r.values);
                    tr._values = Object.assign({}, r.values);
                    drawRow(host, tr);
                    bindRow(host, tr);
                });
                filterGrid(host);
            });
        }).catch(function (e) { fail(grid, e); });
    }

    function drawRow(host, tr) {
        var cell = tr.querySelector('.js-dims');
        if (window.jQuery && jQuery.fn.select2) {
            jQuery(cell).find('select.select2-hidden-accessible').select2('destroy');
        }
        cell.innerHTML = dimsHtml(tr);
        initDimSelects(cell, null, function (sel) {
            tr._values[sel.dataset.dim] = (sel.value || '').trim();
            updateStatus(tr);
            markDirty(host, tr);
        });
        updateStatus(tr);
    }

    function rowState(tr) {
        var company = rowCompany(tr);
        var tpl = state.templates[company] || [];
        if (!company) return { cls: 'tone-slate', text: t('d365_no_company_short', 'No company'), ready: false };
        if (!tpl.length) return { cls: 'tone-amber', text: t('d365_no_template_short', 'No template'), ready: false };
        var missing = tpl.filter(function (d) {
            return !isWorker(d.dimension) && !d.default && !(tr._values[d.dimension] || '').trim();
        }).map(function (d) { return d.dimension; });
        return missing.length
            ? { cls: 'tone-red', text: t('d365_missing', 'Missing') + ': ' + missing.join(', '), ready: false }
            : { cls: 'tone-green', text: t('d365_ready', 'Ready'), ready: true };
    }

    function updateStatus(tr) {
        var s = rowState(tr);
        tr.dataset.ready = s.ready ? '1' : '0';
        tr.querySelector('.js-status').innerHTML = '<span class="sr-pill ' + s.cls + '"><span class="sr-dot"></span>' + esc(s.text) + '</span>';
    }

    function changedValues(tr) {
        var out = {};
        Object.keys(tr._values).forEach(function (k) {
            if ((tr._values[k] || '') !== (tr._orig[k] || '')) out[k] = tr._values[k] || '';
        });
        return out;
    }

    function markDirty(host, tr) {
        var dirty = tr.querySelector('.js-pc').value !== tr.dataset.pc || Object.keys(changedValues(tr)).length > 0;
        tr.classList.toggle('is-dirty', dirty);
        tr.querySelector('.js-save').style.visibility = dirty ? 'visible' : 'hidden';
        var n = host.querySelectorAll('#dimGrid tr.is-dirty').length;
        host.querySelector('#dimDirtyCount').textContent = n;
        host.querySelector('#dimSaveAll').disabled = n === 0;
    }

    function bindRow(host, tr) {
        tr.querySelector('.js-pc').addEventListener('change', function () {
            drawRow(host, tr); // inputs follow the template of the newly chosen company
            markDirty(host, tr);
        });
        tr.querySelector('.js-save').addEventListener('click', function () {
            saveRow(host, tr).catch(function (e) { Swal.fire('Error', e.message, 'error'); });
        });
    }

    function saveRow(host, tr) {
        var values = changedValues(tr);
        var bad = Object.keys(values).map(function (k) { return values[k]; }).find(function (v) { return v.indexOf('-') !== -1; });
        if (bad) return Promise.reject(new Error(t('d365_no_dash', 'Values cannot contain "-"') + ': ' + bad));
        var pc = tr.querySelector('.js-pc').value;
        var data = { emp_id: tr.dataset.emp, values: JSON.stringify(values) };
        if (pc !== tr.dataset.pc) data.payroll_company = pc;
        return post('save_employee', data).then(function (j) {
            tr._orig = Object.assign({}, j.values || {});
            tr._values = Object.assign({}, j.values || {});
            tr.dataset.pc = pc;
            drawRow(host, tr);
            markDirty(host, tr);
        });
    }

    function saveAll(host) {
        var rows = Array.prototype.slice.call(host.querySelectorAll('#dimGrid tr.is-dirty'));
        var errors = [];
        var chain = Promise.resolve();
        rows.forEach(function (tr) {
            chain = chain.then(function () {
                return saveRow(host, tr).catch(function (e) { errors.push(tr.dataset.emp + ': ' + e.message); });
            });
        });
        Swal.fire({ title: t('saving', 'Saving...'), allowOutsideClick: false, didOpen: function () { Swal.showLoading(); } });
        chain.then(function () {
            if (errors.length) {
                Swal.fire({ icon: 'error', title: t('error', 'Error'), html: errors.map(esc).join('<br>') });
            } else {
                Swal.fire({ icon: 'success', title: t('saved', 'Saved') + ' (' + rows.length + ')', timer: 1400, showConfirmButton: false });
            }
        });
    }

    function filterGrid(host) {
        var q = (host.querySelector('#dimSearch').value || '').toLowerCase();
        var onlyMissing = host.querySelector('#dimOnlyMissing').checked;
        host.querySelectorAll('#dimGrid tbody tr[data-emp]').forEach(function (tr) {
            var show = tr.dataset.search.indexOf(q) !== -1;
            if (show && onlyMissing) show = tr.dataset.ready !== '1';
            tr.style.display = show ? '' : 'none';
        });
    }

    function fillFromD365(host, grid, company) {
        if (company === '*' || company === '-' || !(state.templates[company] || []).length) {
            Swal.fire('', t('d365_fill_pick_company', 'Pick one company that has a template in the filter first'), 'info');
            return;
        }
        if (grid.querySelector('tr.is-dirty')) {
            Swal.fire('', t('d365_save_first', 'Save or discard your changes first'), 'info');
            return;
        }
        Swal.fire({
            icon: 'question',
            title: t('d365_fill_blanks', 'Fill blanks from D365') + ' - ' + esc(company),
            text: t('d365_fill_blanks_info', 'Copies the dimensions D365 already has on each employee\'s employment into EMPTY values only (Department falls back to the department map). Nothing you typed is overwritten. Takes up to a minute.'),
            showCancelButton: true,
            confirmButtonText: t('d365_fill', 'Fill'),
            cancelButtonText: t('cancel', 'Cancel'),
            allowOutsideClick: false,
            showLoaderOnConfirm: true,
            preConfirm: function () {
                return post('fill_from_d365', { company: company }).catch(function (e) { Swal.showValidationMessage(e.message); });
            }
        }).then(function (r) {
            if (!r.isConfirmed || !r.value) return;
            Swal.fire({ icon: 'success', title: t('done', 'Done'),
                text: r.value.filled + ' ' + t('d365_values_filled', 'values filled for') + ' ' + r.value.employees + ' / ' + r.value.checked + ' ' + t('employees', 'employees') });
            loadGrid(host, grid, company);
        });
    }

    // ------------------------------------------------------------ Excel bulk upload
    // Download: every active employee with current values (one column per template dimension).
    // Upload: preview first (dry run), then save every valid row at once. Blank cells keep the current value.

    function postFile(action, file, data) {
        var body = new FormData();
        body.append('action', action);
        body.append('csrf', window.D365_DIM_CSRF || '');
        Object.keys(data || {}).forEach(function (k) { body.append(k, data[k]); });
        if (file) body.append('file', file);
        return fetch(URL, { method: 'POST', body: body, credentials: 'same-origin' })
            .then(function (r) {
                if (r.redirected) throw new Error(t('d365_signed_out', 'You were signed out - reload the page'));
                return r.json();
            })
            .then(function (j) {
                if (!j.ok) throw new Error(j.error || 'Request failed');
                return j;
            });
    }

    function downloadExcel(btn) {
        btn.disabled = true;
        var body = new URLSearchParams({ action: 'import_template', csrf: window.D365_DIM_CSRF || '' });
        fetch(URL, { method: 'POST', body: body, credentials: 'same-origin' })
            .then(function (r) {
                if (r.redirected) throw new Error(t('d365_signed_out', 'You were signed out - reload the page'));
                if ((r.headers.get('Content-Type') || '').indexOf('json') !== -1) {
                    return r.json().then(function (j) { throw new Error(j.error || 'Request failed'); });
                }
                var name = /filename="([^"]+)"/.exec(r.headers.get('Content-Disposition') || '');
                return r.blob().then(function (b) { return { blob: b, name: name ? name[1] : 'employee_dimensions.xlsx' }; });
            })
            .then(function (f) {
                var a = document.createElement('a');
                a.href = window.URL.createObjectURL(f.blob);
                a.download = f.name;
                document.body.appendChild(a);
                a.click();
                setTimeout(function () { window.URL.revokeObjectURL(a.href); a.remove(); }, 1000);
            })
            .catch(function (e) { Swal.fire('Error', e.message, 'error'); })
            .then(function () { btn.disabled = false; });
    }

    /** "Excel Import" popup: summary, explanation of every column, rules, Download template + Upload buttons */
    function excelDialog(host) {
        var meta = state.meta || {};
        var templates = meta.templates || {};
        var tplCodes = Object.keys(templates).sort();
        var applied = 0, unapplied = 0;
        Object.keys(meta.counts || {}).forEach(function (k) {
            if (tplCodes.indexOf(k) !== -1) applied += meta.counts[k]; else unapplied += meta.counts[k];
        });
        // dimension -> companies whose template uses it (template order, Worker excluded)
        var dims = [], usedBy = {};
        tplCodes.forEach(function (code) {
            (templates[code] || []).forEach(function (d) {
                if (isWorker(d.dimension)) return;
                if (!usedBy[d.dimension]) { usedBy[d.dimension] = []; dims.push(d.dimension); }
                usedBy[d.dimension].push(code + (d['default'] ? ' (' + t('default', 'default') + ': ' + d['default'] + ')' : ''));
            });
        });
        var dimInfo = {
            costcenter: t('d365_col_costcenter', 'Cost center code. Saved on the employee record (same field as Edit Employee).'),
            department: t('d365_col_department', 'D365 department code (see the Departments tab for the app → D365 mapping).'),
            branch: t('d365_col_branch', 'D365 branch code of the employee.'),
            company: t('d365_col_company', 'Company financial dimension value (not the payroll company).')
        };
        var badge = function (text, tone) { return '<span class="sr-chip ' + (tone || '') + '" style="margin:1px 2px">' + esc(text) + '</span>'; };
        var colRow = function (name, need, desc) {
            return '<tr><td style="white-space:nowrap"><b>' + esc(name) + '</b></td><td style="white-space:nowrap">' + need + '</td><td>' + desc + '</td></tr>';
        };
        var cols = colRow('Emp ID', badge(t('required', 'Required'), 'tone-red'),
                esc(t('d365_col_empid', 'Employee number of an ACTIVE employee. Each employee only once in the file.'))) +
            colRow('Name', badge(t('info_only', 'Info only'), 'tone-slate'),
                esc(t('d365_col_name', 'Employee name, only to help you read the file - ignored on upload.'))) +
            colRow('Payroll Company', badge(t('optional', 'Optional'), ''),
                esc(t('d365_col_company_payroll', 'D365 company that pays the employee - decides which template (columns) applies. Blank keeps the current company.')) +
                '<br>' + tplCodes.map(function (c) { return badge(c + (companyName(c) ? ' - ' + companyName(c) : ''), 'tone-green'); }).join('')) +
            dims.map(function (d) {
                return colRow(d, badge(t('d365_needed_for', 'Needed for'), 'tone-amber') + '<br>' + usedBy[d].map(function (c) { return badge(c); }).join(''),
                    esc(dimInfo[d.toLowerCase()] || t('d365_col_dim', 'Value of this D365 financial dimension.')) +
                    ' ' + esc(t('d365_col_dim_blank', 'Blank keeps the current value.')));
            }).join('');
        var html = '<div style="text-align:left;font-size:13px">' +
            '<div class="sr-notice tone-sky mb-2"><i class="mdi mdi-information-outline"></i> ' +
            esc(t('d365_excel_summary', 'Set the D365 dimensions of many employees at once: download the Excel (all active employees with their current values), edit it, then upload it back. You see a preview with every change and error before anything is saved.')) + '</div>' +
            '<div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:10px">' +
            badge(t('d365_companies_with_template', 'Companies with a template') + ': ' + tplCodes.length, 'tone-green') +
            badge(t('d365_emps_applied', 'Employees in those companies') + ': ' + applied, '') +
            badge(t('d365_not_applied', 'Without an applied template') + ': ' + unapplied, 'tone-amber') +
            badge(t('d365_dimension_columns', 'Dimension columns') + ': ' + dims.length, 'tone-slate') +
            '</div>' +
            '<div style="font-weight:600;margin:6px 0 4px">' + esc(t('d365_excel_columns', 'Columns')) + '</div>' +
            '<div style="max-height:300px;overflow:auto;border:1px solid rgba(0,0,0,.08);border-radius:6px">' +
            '<table class="table table-sm mb-0" style="font-size:12px"><thead><tr><th>' + esc(t('column', 'Column')) + '</th><th>' +
            esc(t('status', 'Status')) + '</th><th>' + esc(t('description', 'Description')) + '</th></tr></thead><tbody>' + cols + '</tbody></table></div>' +
            '<div style="font-weight:600;margin:10px 0 4px">' + esc(t('d365_excel_rules', 'Rules')) + '</div>' +
            '<ul style="margin:0 0 10px 18px;padding:0">' +
            '<li>' + esc(t('d365_rule_blank', 'Blank cells never clear anything - they keep the current value.')) + '</li>' +
            '<li>' + esc(t('d365_rule_dash', 'Values may not contain a dash (-): D365 uses it as the account separator.')) + '</li>' +
            '<li>' + esc(t('d365_rule_errors', 'Rows with errors (unknown/inactive Emp ID, repeated Emp ID, unknown payroll company, invalid value) are skipped; all other rows are saved.')) + '</li>' +
            '<li>' + esc(t('d365_rule_warn', 'Warnings (value not in D365, company without template) do not stop the row.')) + '</li>' +
            '<li>' + esc(t('d365_rule_format', 'Keep the header row. Column order does not matter. Formats: .xlsx, .xls, .csv - max 5 MB. Worker is filled automatically.')) + '</li>' +
            '</ul>' +
            '<div style="display:flex;flex-wrap:wrap;gap:8px;justify-content:center;padding-top:6px;border-top:1px solid rgba(0,0,0,.08)">' +
            '<button type="button" class="sr-btn sr-btn-ghost" id="xlDownload"><i class="mdi mdi-download"></i> ' + esc(t('d365_download_excel', 'Download Excel')) + '</button>' +
            '<button type="button" class="sr-btn sr-btn-success" id="xlUpload"><i class="mdi mdi-upload"></i> ' + esc(t('d365_upload_excel', 'Upload Excel')) + '</button>' +
            '<input type="file" id="xlFile" accept=".xlsx,.xls,.csv" style="display:none">' +
            '</div></div>';
        Swal.fire({
            title: '<i class="mdi mdi-file-excel-outline" style="color:#1d6f42"></i> ' + esc(t('d365_excel_import', 'Excel Import')) + ' - ' + esc(t('d365_employee_dimensions', 'Employee Dimensions')),
            html: html,
            width: 860,
            showConfirmButton: false,
            showCloseButton: true,
            didOpen: function (popup) {
                var file = popup.querySelector('#xlFile');
                popup.querySelector('#xlDownload').addEventListener('click', function () { downloadExcel(this); });
                popup.querySelector('#xlUpload').addEventListener('click', function () { file.value = ''; file.click(); });
                file.addEventListener('change', function () {
                    if (this.files && this.files[0]) uploadExcel(host, this.files[0]);
                });
            }
        });
    }

    function importReport(j) {
        var html = '<div style="text-align:left">' +
            '<div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px">' +
            '<span class="sr-chip tone-green">' + esc(t('d365_import_employees', 'Employees to update')) + ': ' + j.employees + '</span>' +
            '<span class="sr-chip">' + esc(t('d365_import_changes', 'Values changed')) + ': ' + j.changes + '</span>' +
            '<span class="sr-chip tone-slate">' + esc(t('d365_import_unchanged', 'Unchanged')) + ': ' + j.unchanged + '</span>' +
            (j.errors.length ? '<span class="sr-chip tone-red">' + esc(t('errors', 'Errors')) + ': ' + j.errors.length + '</span>' : '') +
            (j.warning_count ? '<span class="sr-chip tone-amber">' + esc(t('warnings', 'Warnings')) + ': ' + j.warning_count + '</span>' : '') +
            '</div>' +
            '<div class="small text-muted mb-2">' + esc(t('d365_import_columns', 'Columns read')) + ': ' +
            (j.company_column ? chip('Payroll Company', 'tone-slate') : '') + (j.columns || []).map(function (c) { return chip(c); }).join('') +
            (j.ignored && j.ignored.length ? '<br>' + esc(t('d365_import_ignored', 'Ignored columns')) + ': ' + esc(j.ignored.join(', ')) : '') + '</div>';
        var list = j.errors.map(function (e) {
            return '<tr><td>' + e.row + '</td><td>' + esc(e.emp_id) + '</td><td class="text-danger">' + esc(e.error) + '</td></tr>';
        }).concat(j.warnings.map(function (w) {
            return '<tr><td>' + w.row + '</td><td>' + esc(w.emp_id) + '</td><td style="color:#b26a00">' + esc(w.warning) + '</td></tr>';
        }));
        if (list.length) {
            html += '<div style="max-height:260px;overflow:auto;border:1px solid rgba(0,0,0,.08);border-radius:6px">' +
                '<table class="table table-sm mb-0" style="font-size:12px"><thead><tr><th>' + esc(t('row', 'Row')) + '</th><th>' +
                esc(t('emp_id', 'Emp ID')) + '</th><th>' + esc(t('message', 'Message')) + '</th></tr></thead><tbody>' + list.join('') + '</tbody></table></div>' +
                (j.errors.length ? '<div class="small text-muted mt-1">' + esc(t('d365_import_error_rows_skipped', 'Rows with errors are skipped - the rest is saved.')) + '</div>' : '');
        }
        return html + '</div>';
    }

    function uploadExcel(host, file) {
        Swal.fire({ title: t('loading', 'Loading...'), allowOutsideClick: false, didOpen: function () { Swal.showLoading(); } });
        postFile('import', file, { dry_run: 1 }).then(function (j) {
            Swal.fire({
                title: t('d365_upload_excel', 'Upload Excel') + ' - ' + esc(file.name),
                html: importReport(j),
                width: 760,
                icon: j.employees ? 'question' : 'info',
                showCancelButton: !!j.employees,
                showConfirmButton: true,
                confirmButtonText: j.employees ? t('d365_import_save', 'Save') + ' (' + j.employees + ')' : t('close', 'Close'),
                cancelButtonText: t('cancel', 'Cancel'),
                allowOutsideClick: false,
                showLoaderOnConfirm: true,
                preConfirm: function () {
                    if (!j.employees) return true;
                    return postFile('import', file, { dry_run: 0 }).catch(function (e) { Swal.showValidationMessage(e.message); });
                }
            }).then(function (r) {
                if (!r.isConfirmed || !j.employees || !r.value) return;
                Swal.fire({ icon: 'success', title: t('done', 'Done'),
                    text: r.value.saved + ' ' + t('d365_import_saved', 'employees updated') });
                renderEmployees(host);
            });
        }).catch(function (e) { Swal.fire('Error', e.message, 'error'); });
    }

    // ------------------------------------------------------------ Departments (app -> D365)
    // Each app department -> an existing D365 department, or a new one created in D365 with the code typed here.
    // "Assign to employees" then sets every active employee's Department dimension from their app department.

    var NEW = '__new__';

    function renderDepartments(host, refresh) {
        loading(host);
        post('dept_load', refresh ? { refresh: 1 } : {}).then(function (j) {
            var d365 = j.d365 || [];
            var known = {};
            d365.forEach(function (v) { known[v.value] = v; });
            var rows = j.departments.map(function (d) {
                var mapped = d.d365 || '';
                var opts = '<option value="">' + esc(t('d365_not_mapped', '- not mapped -')) + '</option>' +
                    d365.filter(function (v) { return v.active || v.value === mapped; }).map(function (v) {
                        return '<option value="' + esc(v.value) + '"' + (v.value === mapped ? ' selected' : '') + '>' + esc(v.value + (v.name ? ' - ' + v.name : '')) + '</option>';
                    }).join('') +
                    (mapped && !known[mapped] ? '<option value="' + esc(mapped) + '" selected>' + esc(mapped + ' (' + t('d365_not_in_d365', 'not in D365') + ')') + '</option>' : '') +
                    '<option value="' + NEW + '">+ ' + esc(t('d365_create_new', 'Create new in D365...')) + '</option>';
                return '<tr data-id="' + esc(d.id) + '" data-orig="' + esc(mapped) + '">' +
                    '<td><div class="sr-cell-title">' + esc(d.dep_nme) + '</div><div class="sr-cell-sub"><span class="sr-chip sr-mono" style="font-size:10px" title="' +
                    esc(t('d365_app_dept_id', 'App department ID - used as the D365 code when creating it')) + '">ID ' + esc(d.id) + '</span> ' + esc(d.dep_nme_ar || '') + '</div>' +
                    (!mapped && known[String(d.id)] ? '<div class="sr-cell-sub text-warning">' + esc(t('d365_same_id_exists', 'D365 already has code') + ' ' + d.id + (known[String(d.id)].name ? ' (' + known[String(d.id)].name + ')' : '') + ' - ' + t('d365_pick_it', 'pick it from the list')) + '</div>' : '') +
                    '</td>' +
                    '<td class="text-center">' + esc(d.employees) + '</td>' +
                    '<td><select class="form-control form-control-sm js-dept" style="min-width:220px">' + opts + '</select>' +
                    '<div class="js-new" style="display:none;gap:6px;margin-top:6px">' +
                    '<input type="text" class="form-control form-control-sm sr-mono js-code" maxlength="20" style="width:90px" value="' + esc(d.id) + '" placeholder="' + esc(t('code', 'Code')) + '" title="' + esc(t('d365_code_is_app_id', 'Same as the app department ID')) + '">' +
                    '<input type="text" class="form-control form-control-sm js-name" maxlength="60" value="' + esc(d.dep_nme_ar || d.dep_nme) + '" placeholder="' + esc(t('name', 'Name')) + '">' +
                    '</div></td>' +
                    '<td class="js-st"></td></tr>';
            }).join('');

            host.innerHTML =
                '<div class="sr-notice tone-sky mb-3"><i class="mdi mdi-information-outline"></i> ' +
                esc(t('d365_dept_help', 'Map every app department to its D365 department. Pick "Create new in D365" for a department D365 does not have yet - its code is filled with the app department ID (you can change it) - Create in D365 adds it as a D365 department, which also makes it a Department dimension value. Then Assign to employees sets each active employee\'s Department (Employee Dimensions) from their app department.')) +
                '<br><small>' + esc(t('d365_environment', 'Environment')) + ': <b>' + esc(j.environment) + '</b>' +
                (j.can_write ? '' : ' - <span class="text-danger">' + esc(t('d365_writes_off', 'writes are OFF, creating departments is disabled')) + '</span>') + '</small></div>' +
                '<div class="sr-toolbar mb-2" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center">' +
                '<button type="button" class="sr-btn sr-btn-sm sr-btn-primary" id="deptSave"><i class="mdi mdi-content-save"></i> ' + esc(t('d365_save_mapping', 'Save mapping')) + '</button>' +
                '<button type="button" class="sr-btn sr-btn-sm sr-btn-success" id="deptCreate"' + (j.can_write ? '' : ' disabled') + '><i class="mdi mdi-cloud-upload-outline"></i> ' +
                esc(t('d365_create_in_d365', 'Create in D365')) + ' (<span id="deptNewCount">0</span>)</button>' +
                '<button type="button" class="sr-btn sr-btn-sm sr-btn-ghost" id="deptAssign"><i class="mdi mdi-account-multiple-check"></i> ' + esc(t('d365_assign_employees', 'Assign to employees')) + '</button>' +
                '<button type="button" class="sr-btn sr-btn-sm sr-btn-ghost" id="deptRefresh"><i class="mdi mdi-refresh"></i> ' + esc(t('d365_refresh_d365', 'Reload D365 list')) + '</button>' +
                '</div>' +
                '<div class="sr-table-wrap"><table class="sr-table"><thead><tr><th>' + esc(t('app_department', 'App department')) + '</th><th class="text-center">' +
                esc(t('active_employees', 'Active employees')) + '</th><th>' + esc(t('d365_department', 'D365 department')) + '</th><th>' + esc(t('status', 'Status')) + '</th></tr></thead><tbody>' +
                rows + '</tbody></table></div>';

            var refreshRow = function (tr) {
                var v = tr.querySelector('.js-dept').value;
                tr.querySelector('.js-new').style.display = v === NEW ? 'flex' : 'none';
                var st;
                if (v === NEW) st = ['tone-indigo', t('d365_will_create', 'Create in D365')];
                else if (!v) st = ['tone-slate', t('d365_not_mapped_short', 'Not mapped')];
                else if (v !== tr.dataset.orig) st = ['tone-amber', t('d365_unsaved', 'Unsaved')];
                else st = ['tone-green', t('d365_mapped', 'Mapped')];
                tr.querySelector('.js-st').innerHTML = '<span class="sr-pill ' + st[0] + '"><span class="sr-dot"></span>' + esc(st[1]) + '</span>';
                host.querySelector('#deptNewCount').textContent = host.querySelectorAll('.js-dept option[value="' + NEW + '"]:checked').length;
            };
            host.querySelectorAll('tbody tr[data-id]').forEach(function (tr) {
                tr.querySelector('.js-dept').addEventListener('change', function () { refreshRow(tr); });
                refreshRow(tr);
            });

            var currentMap = function () {
                var map = {};
                host.querySelectorAll('tbody tr[data-id]').forEach(function (tr) {
                    var v = tr.querySelector('.js-dept').value;
                    map[tr.dataset.id] = v === NEW ? (tr.dataset.orig || '') : v;
                });
                return map;
            };

            host.querySelector('#deptRefresh').addEventListener('click', function () { renderDepartments(host, true); });
            host.querySelector('#deptSave').addEventListener('click', function () {
                post('dept_save_map', { map: JSON.stringify(currentMap()) }).then(function () {
                    Swal.fire({ icon: 'success', title: t('saved', 'Saved'), timer: 1200, showConfirmButton: false });
                    renderDepartments(host);
                }).catch(function (e) { Swal.fire('Error', e.message, 'error'); });
            });
            host.querySelector('#deptCreate').addEventListener('click', function () { createDepartments(host, currentMap()); });
            host.querySelector('#deptAssign').addEventListener('click', function () { assignDepartments(host); });
        }).catch(function (e) { fail(host, e); });
    }

    function createDepartments(host, map) {
        var jobs = [];
        var bad = null;
        host.querySelectorAll('tbody tr[data-id]').forEach(function (tr) {
            if (tr.querySelector('.js-dept').value !== NEW) return;
            var code = tr.querySelector('.js-code').value.trim();
            var name = tr.querySelector('.js-name').value.trim();
            if (!/^[A-Za-z0-9_]{1,20}$/.test(code) || !name) bad = bad || tr.querySelector('.sr-cell-title').textContent;
            jobs.push({ id: tr.dataset.id, code: code, name: name, label: tr.querySelector('.sr-cell-title').textContent });
        });
        if (!jobs.length) {
            Swal.fire('', t('d365_pick_create', 'Pick "Create new in D365" for at least one department'), 'info');
            return;
        }
        if (bad) {
            Swal.fire('', t('d365_code_required', 'Type a code (letters/digits, no dashes) and a name for') + ' ' + bad, 'warning');
            return;
        }
        Swal.fire({
            icon: 'warning',
            title: t('d365_create_in_d365', 'Create in D365') + ' (' + jobs.length + ')',
            html: '<div class="text-left">' + jobs.map(function (j) { return '<b class="sr-mono">' + esc(j.code) + '</b> - ' + esc(j.name) + ' <span class="text-muted">(' + esc(j.label) + ')</span>'; }).join('<br>') +
                '<p class="mt-2 text-muted" style="font-size:13px">' + esc(t('d365_create_warn', 'These departments are added to D365 (organization + Department dimension). Other mapping changes are saved first.')) + '</p></div>',
            showCancelButton: true,
            confirmButtonText: t('d365_create', 'Create'),
            cancelButtonText: t('cancel', 'Cancel'),
            allowOutsideClick: false
        }).then(function (r) {
            if (!r.isConfirmed) return;
            Swal.fire({ title: t('d365_creating', 'Creating...'), allowOutsideClick: false, didOpen: function () { Swal.showLoading(); } });
            var results = [];
            var chain = post('dept_save_map', { map: JSON.stringify(map) });
            jobs.forEach(function (j) {
                chain = chain.then(function () {
                    return post('dept_create', { dept_id: j.id, code: j.code, name: j.name })
                        .then(function (res) { results.push('<span class="text-success">&#10003;</span> ' + esc(j.label) + ' = <b>' + esc(res.code) + '</b>' + (res.renumbered ? ' (' + esc(t('d365_numbered_by_d365', 'numbered by D365')) + ')' : '')); })
                        .catch(function (e) { results.push('<span class="text-danger">&#10007;</span> ' + esc(j.label) + ': ' + esc(e.message)); });
                });
            });
            chain.then(function () {
                delete state.dimValues.Department; // Employee Dimensions reloads the list with the new departments
                Swal.fire({ title: t('done', 'Done'), html: '<div class="text-left">' + results.join('<br>') + '</div>', allowOutsideClick: false });
                renderDepartments(host, true);
            }).catch(function (e) { Swal.fire('Error', e.message, 'error'); });
        });
    }

    function assignDepartments(host) {
        Swal.fire({
            icon: 'question',
            title: t('d365_assign_employees', 'Assign to employees'),
            html: '<div class="text-left" style="font-size:14px">' +
                esc(t('d365_assign_help', 'Sets the Department (Employee Dimensions) of every active employee from their app department, using the SAVED mapping. Payroll uses it for companies whose template has Department.')) +
                '<label class="sr-check mt-3 d-block"><input type="checkbox" id="deptOverwrite"> ' +
                esc(t('d365_overwrite', 'Also replace departments already set on employees')) + '</label></div>',
            showCancelButton: true,
            confirmButtonText: t('d365_assign', 'Assign'),
            cancelButtonText: t('cancel', 'Cancel'),
            allowOutsideClick: false,
            showLoaderOnConfirm: true,
            preConfirm: function () {
                var overwrite = Swal.getPopup().querySelector('#deptOverwrite').checked;
                return post('dept_assign', overwrite ? { overwrite: 1 } : {}).catch(function (e) { Swal.showValidationMessage(e.message); });
            }
        }).then(function (r) {
            if (!r.isConfirmed || !r.value) return;
            var v = r.value;
            Swal.fire({ icon: 'success', title: t('done', 'Done'),
                html: '<b>' + v.set + '</b> ' + esc(t('d365_dept_set', 'employees updated')) + '<br>' + v.kept + ' ' + esc(t('d365_dept_kept', 'already set / unchanged')) +
                    (v.unmapped ? '<br><span class="text-danger">' + v.unmapped + ' ' + esc(t('d365_dept_unmapped', 'in departments without a mapping')) + '</span>' : '') });
        });
    }

    // ------------------------------------------------------------ Companies (app company -> D365 company)
    // Picked once here; the new-employee modal and "Add to D365" then use it - no second company select.

    function renderCompanies(host) {
        loading(host);
        post('comp_load').then(function (j) {
            var rows = j.app.map(function (c) {
                var opts = '<option value="">' + esc(t('d365_not_mapped', '- not mapped -')) + '</option>' +
                    j.companies.map(function (d) {
                        return '<option value="' + esc(d.code) + '"' + (d.code === c.d365 ? ' selected' : '') + '>' + esc(d.code + (d.name ? ' - ' + d.name : '')) + '</option>';
                    }).join('') +
                    (c.d365 && !j.companies.some(function (d) { return d.code === c.d365; })
                        ? '<option value="' + esc(c.d365) + '" selected>' + esc(c.d365 + ' (' + t('d365_removed_or_unknown', 'removed / unknown') + ')') + '</option>' : '');
                return '<tr data-id="' + esc(c.comp_id) + '" data-orig="' + esc(c.d365) + '" data-suggest="' + esc(c.suggest) + '">' +
                    '<td><div class="sr-cell-title">' + esc(c.comp_name) + '</div><div class="sr-cell-sub">' + esc(c.comp_name_ar || '') + '</div></td>' +
                    '<td class="text-center">' + esc(c.employees) + '</td>' +
                    '<td><select class="form-control form-control-sm js-comp" style="min-width:260px">' + opts + '</select>' +
                    (c.suggest && c.suggest !== c.d365 ? '<div class="sr-cell-sub mt-1">' + esc(t('d365_suggested', 'Suggested')) + ': <b>' + esc(c.suggest) + '</b> <span class="text-muted">(' + esc(t('d365_suggested_from', 'where most of its employees are in D365')) + ')</span></div>' : '') +
                    '</td><td class="js-st"></td></tr>';
            }).join('');
            host.innerHTML =
                '<div class="sr-notice tone-sky mb-3"><i class="mdi mdi-information-outline"></i> ' +
                esc(t('d365_companies_help', 'Map every app company to its D365 company once. New employees are then registered in D365 in the mapped company automatically (no second company select), and "Add to D365" preselects it.')) + '</div>' +
                '<div class="sr-toolbar mb-2" style="display:flex;flex-wrap:wrap;gap:8px">' +
                '<button type="button" class="sr-btn sr-btn-sm sr-btn-primary" id="compSave"><i class="mdi mdi-content-save"></i> ' + esc(t('d365_save_mapping', 'Save mapping')) + '</button>' +
                '<button type="button" class="sr-btn sr-btn-sm sr-btn-ghost" id="compSuggest"><i class="mdi mdi-auto-fix"></i> ' + esc(t('d365_fill_suggested', 'Fill empty with suggestions')) + '</button>' +
                '</div>' +
                '<div class="sr-table-wrap"><table class="sr-table"><thead><tr><th>' + esc(t('app_company', 'App company')) + '</th><th class="text-center">' +
                esc(t('active_employees', 'Active employees')) + '</th><th>' + esc(t('d365_company_label', 'D365 Company')) + '</th><th>' + esc(t('status', 'Status')) + '</th></tr></thead><tbody>' +
                rows + '</tbody></table></div>';

            var refreshRow = function (tr) {
                var v = tr.querySelector('.js-comp').value;
                var st = !v ? ['tone-slate', t('d365_not_mapped_short', 'Not mapped')]
                    : (v !== tr.dataset.orig ? ['tone-amber', t('d365_unsaved', 'Unsaved')] : ['tone-green', t('d365_mapped', 'Mapped')]);
                tr.querySelector('.js-st').innerHTML = '<span class="sr-pill ' + st[0] + '"><span class="sr-dot"></span>' + esc(st[1]) + '</span>';
            };
            host.querySelectorAll('tbody tr[data-id]').forEach(function (tr) {
                tr.querySelector('.js-comp').addEventListener('change', function () { refreshRow(tr); });
                refreshRow(tr);
            });
            host.querySelector('#compSuggest').addEventListener('click', function () {
                host.querySelectorAll('tbody tr[data-id]').forEach(function (tr) {
                    var sel = tr.querySelector('.js-comp');
                    if (!sel.value && tr.dataset.suggest && sel.querySelector('option[value="' + tr.dataset.suggest + '"]')) {
                        sel.value = tr.dataset.suggest;
                        refreshRow(tr);
                    }
                });
            });
            host.querySelector('#compSave').addEventListener('click', function () {
                var map = {};
                host.querySelectorAll('tbody tr[data-id]').forEach(function (tr) { map[tr.dataset.id] = tr.querySelector('.js-comp').value; });
                post('comp_save_map', { map: JSON.stringify(map) }).then(function () {
                    Swal.fire({ icon: 'success', title: t('saved', 'Saved'), timer: 1200, showConfirmButton: false });
                    renderCompanies(host);
                }).catch(function (e) { Swal.fire('Error', e.message, 'error'); });
            });
        }).catch(function (e) { fail(host, e); });
    }

    window.D365DimensionsSettings = {
        render: function (key, host) {
            if (key === 'templates') renderTemplates(host);
            else if (key === 'departments') renderDepartments(host);
            else if (key === 'companies') renderCompanies(host);
            else renderEmployees(host);
        }
    };
})();
