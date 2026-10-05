/**
 * D365 payroll sync: employees whose payroll could not be sent because they are not registered in D365.
 * Used by generate_payroll.php and d365_payroll_push.php after a month sync.
 *
 * d365MissingWorkers.open({
 *     missing: [{ id, name }],          // from sync_chunk results with missing_worker = true
 *     month:   'YYYY-MM',
 *     post:    function (data) -> Promise<json>,   // POSTs to d365_payroll_push.php with the CSRF token
 *     onSync:  function (ids)  -> void,            // sync the payroll of the newly registered employees
 *     onClose: function ()     -> void (optional)
 * })
 */
(function () {
    function esc(s) {
        var d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    var STYLE = '<style>'
        + '.d365mw-wrap{max-height:52vh;overflow:auto;text-align:left;border:1px solid #e2e8f0;border-radius:8px}'
        + '.d365mw-table{width:100%;border-collapse:collapse;font-size:13px}'
        + '.d365mw-table th{position:sticky;top:0;background:#f8fafc;font-weight:600;padding:7px 8px;border-bottom:1px solid #e2e8f0;text-align:left}'
        + '.d365mw-table td{padding:6px 8px;border-bottom:1px solid #f1f5f9;vertical-align:middle}'
        + '.d365mw-table select{padding:3px 6px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px}'
        + '.d365mw-st{font-size:12px;white-space:nowrap}.d365mw-ok{color:#16a34a}.d365mw-err{color:#dc2626}'
        + '.d365mw-sub{color:#64748b;font-size:12px}'
        + '</style>';

    function open(opts) {
        var missing = opts.missing || [];
        if (!missing.length) return;
        Swal.fire({
            title: 'Loading ' + missing.length + ' employees...',
            allowOutsideClick: false,
            didOpen: function () { Swal.showLoading(); }
        });
        opts.post({ action: 'missing_workers', emp_ids: missing.map(function (m) { return m.id; }) }).then(function (res) {
            if (res.error) throw new Error(res.error);
            showList(opts, res);
        }).catch(function (err) {
            Swal.fire({ icon: 'error', title: 'Cannot load employees', text: err.message }).then(function () { if (opts.onClose) opts.onClose(); });
        });
    }

    function showList(opts, res) {
        var entities = res.entities || [];
        var rows = res.rows || [];
        var html = STYLE
            + '<p style="font-size:13px;margin:0 0 10px;text-align:left">These employees have <b>paid payroll for ' + esc(opts.month) + '</b> but are <b>not registered in D365</b>, so their payroll was not sent. '
            + 'Register them (worker + employment in the chosen company), then sync their payroll.</p>'
            + '<div class="d365mw-wrap"><table class="d365mw-table"><thead><tr>'
            + '<th><input type="checkbox" id="d365mwAll" checked></th><th>Employee</th><th>App company</th><th>D365 company</th><th></th>'
            + '</tr></thead><tbody>'
            + rows.map(function (r, i) {
                var sel = '<option value="">- choose -</option>' + entities.map(function (en) {
                    return '<option value="' + esc(en) + '"' + (en === r.suggest ? ' selected' : '') + '>' + esc(en) + '</option>';
                }).join('');
                return '<tr data-i="' + i + '">'
                    + '<td><input type="checkbox" class="d365mw-chk" checked></td>'
                    + '<td><b>' + esc(r.emp_id) + '</b><div class="d365mw-sub">' + esc(r.name) + '</div></td>'
                    + '<td class="d365mw-sub">' + esc(r.app_company) + '</td>'
                    + '<td><select class="d365mw-co">' + sel + '</select></td>'
                    + '<td class="d365mw-st"></td></tr>';
            }).join('')
            + '</tbody></table></div>'
            + (res.can_write ? '' : '<p class="d365mw-err" style="font-size:12px;margin-top:8px">Writes to D365 are off (App Settings &gt; D365 Config &gt; Allow Writes).</p>');

        var registered = [];
        var failed = 0;
        Swal.fire({
            title: rows.length + ' employee' + (rows.length === 1 ? '' : 's') + ' not in D365',
            html: html,
            width: 760,
            showCancelButton: true,
            confirmButtonText: 'Register selected in D365',
            cancelButtonText: 'Close',
            allowOutsideClick: false,
            didOpen: function () {
                var all = document.getElementById('d365mwAll');
                all.addEventListener('change', function () {
                    document.querySelectorAll('.d365mw-chk').forEach(function (c) { if (!c.disabled) c.checked = all.checked; });
                });
                if (!res.can_write) Swal.getConfirmButton().disabled = true;
            },
            preConfirm: function () {
                var todo = [];
                document.querySelectorAll('.d365mw-table tbody tr').forEach(function (tr) {
                    var chk = tr.querySelector('.d365mw-chk');
                    if (!chk.checked || chk.disabled) return;
                    todo.push({ tr: tr, row: rows[Number(tr.getAttribute('data-i'))], company: tr.querySelector('.d365mw-co').value });
                });
                if (!todo.length) { Swal.showValidationMessage('Select at least one employee'); return false; }
                var noCompany = todo.filter(function (t) { return !t.company; });
                if (noCompany.length) { Swal.showValidationMessage('Choose the D365 company for ' + noCompany.map(function (t) { return t.row.emp_id; }).join(', ')); return false; }

                Swal.getConfirmButton().disabled = true;
                Swal.getCancelButton().disabled = true;
                // one at a time: each creates a person, worker and employment in D365
                return todo.reduce(function (p, t) {
                    return p.then(function () {
                        var st = t.tr.querySelector('.d365mw-st');
                        st.innerHTML = '<span class="d365mw-sub">Registering...</span>';
                        return opts.post({ action: 'register_worker', emp_id: t.row.emp_id, company: t.company }).then(function (r) {
                            if (r.ok) {
                                registered.push(t.row.emp_id);
                                st.innerHTML = '<span class="d365mw-ok">&#10004; ' + esc(t.company) + '</span>';
                                var chk = t.tr.querySelector('.d365mw-chk');
                                chk.checked = false;
                                chk.disabled = true;
                            } else {
                                failed++;
                                st.innerHTML = '<span class="d365mw-err" title="' + esc(r.error) + '">&#10006; ' + esc(String(r.error || 'Failed').slice(0, 60)) + '</span>';
                            }
                        }).catch(function (err) {
                            failed++;
                            st.innerHTML = '<span class="d365mw-err">&#10006; ' + esc(err.message) + '</span>';
                        });
                    });
                }, Promise.resolve()).then(function () {
                    if (failed) {
                        // keep the list open so the errors can be read / companies changed and retried
                        Swal.getConfirmButton().disabled = false;
                        Swal.getCancelButton().disabled = false;
                        Swal.showValidationMessage(registered.length + ' registered, ' + failed + ' failed - hover the error for details, fix and retry, or close');
                        failed = 0;
                        return false;
                    }
                    return true;
                });
            }
        }).then(function (r) {
            if (!registered.length) {
                if (opts.onClose) opts.onClose();
                return;
            }
            Swal.fire({
                icon: 'success',
                title: registered.length + ' registered in D365',
                html: 'Send their <b>' + esc(opts.month) + '</b> payroll to D365 now?',
                showCancelButton: true,
                confirmButtonText: 'Sync their payroll',
                cancelButtonText: 'Later',
                allowOutsideClick: false
            }).then(function (s) {
                if (s.isConfirmed) opts.onSync(registered.slice());
                else if (opts.onClose) opts.onClose();
            });
        });
    }

    window.d365MissingWorkers = { open: open };
})();
