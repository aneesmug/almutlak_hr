// Employee Memos - employee_memos.php (compose, drafts, history, Manage Templates)
// and view_employee.php (Memos tab, via EmployeeMemos.initHistoryTable / .view).
// Server: includes/ajaxFile/employeeMemoHandler.php
//
// A template has English + Arabic subject/body and its own detail fields. HR fills
// the fields; the memo is built here (live preview) as a two-column table - English
// left, Arabic (RTL) right - and that HTML is what gets stored and emailed.
(function ($) {
    'use strict';

    var HANDLER = './includes/ajaxFile/employeeMemoHandler.php';
    var COMPOSE_PAGE = 'employee_memos.php';

    function t(key, fallback) {
        return (window.lang && window.lang[key]) || fallback;
    }

    function esc(v) {
        return $('<div>').text(v == null ? '' : String(v)).html();
    }

    function decode(html) {
        return $('<textarea>').html(html == null ? '' : String(html)).val();
    }

    function failMsg(xhr, fallback) {
        return (xhr && xhr.responseJSON && xhr.responseJSON.message) || fallback;
    }

    function statusBadge(status) {
        if (status === 'sent') return '<span class="badge badge-success">' + esc(t('sent', 'Sent')) + '</span>';
        if (status === 'draft') return '<span class="badge badge-warning">' + esc(t('draft', 'Draft')) + '</span>';
        return '<span class="badge badge-danger">' + esc(t('failed', 'Failed')) + '</span>';
    }

    // Summernote's own icon font doesn't load here (blank toolbar buttons) - use the
    // app's Font Awesome instead, which every page already has.
    function editorOptions(height) {
        var fa = function (name) { return 'fa-solid fa-' + name; };
        return {
            height: height,
            dialogsInBody: true, // link/table dialogs also work inside the Manage Templates modal
            icons: {
                magic: fa('heading'), bold: fa('bold'), italic: fa('italic'), underline: fa('underline'),
                eraser: fa('eraser'), unorderedlist: fa('list-ul'), orderedlist: fa('list-ol'),
                align: fa('align-left'), alignLeft: fa('align-left'), alignCenter: fa('align-center'),
                alignRight: fa('align-right'), alignJustify: fa('align-justify'),
                indent: fa('indent'), outdent: fa('outdent'), table: fa('table'), link: fa('link'),
                unlink: fa('link-slash'), code: fa('code'), caret: fa('caret-down'),
                rowAbove: fa('arrow-up'), rowBelow: fa('arrow-down'), colBefore: fa('arrow-left'),
                colAfter: fa('arrow-right'), rowRemove: fa('minus'), colRemove: fa('minus'), trash: fa('trash'),
                menuCheck: fa('check'), close: fa('xmark'), arrowsAlt: fa('expand')
            },
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'clear']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link']],
                ['view', ['codeview']]
            ]
        };
    }

    function rtlEditor($textarea) {
        $textarea.next('.note-editor').find('.note-editable').attr('dir', 'rtl').css('text-align', 'right');
    }

    // ---------- filling + bilingual layout ----------
    function formatValue(field, raw) {
        var v = $.trim(raw == null ? '' : String(raw));
        if (v === '') return '';
        if (field.type === 'date') {
            var m = v.match(/^(\d{4})-(\d{2})-(\d{2})$/);
            return m ? m[3] + '/' + m[2] + '/' + m[1] : v;
        }
        if (field.type === 'number') {
            var n = parseFloat(v);
            return isNaN(n) ? v : n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
        return v;
    }

    function fieldValue(field, values, lang) {
        var v = values[field.key];
        if (field.bilingual) {
            v = v && typeof v === 'object' ? v : { en: v || '', ar: '' };
            // Arabic falls back to the English text when left empty.
            return formatValue(field, lang === 'ar' && $.trim(v.ar || '') !== '' ? v.ar : v.en);
        }
        return formatValue(field, v && typeof v === 'object' ? v.en : v);
    }

    // {{salary_fields_table}} / {{salary_fields_total}}: built from the template's own
    // number fields whose key starts with sal_ (job offer - no employee salary record yet).
    function salaryFields(tpl, values, lang) {
        var total = 0;
        var rows = (tpl.fields || []).filter(function (f) {
            return f.type === 'number' && f.key.indexOf('sal_') === 0;
        }).map(function (f) {
            var raw = values[f.key];
            var n = parseFloat(raw && typeof raw === 'object' ? raw.en : raw);
            return { label: (lang === 'ar' && f.label_ar) ? f.label_ar : f.label, amount: isNaN(n) ? 0 : n };
        }).filter(function (r) { return r.amount > 0; });
        rows.forEach(function (r) { total += r.amount; });
        return { rows: rows, total: total };
    }

    function money(n, lang) {
        var v = n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        return lang === 'ar' ? v + ' ريال' : 'SAR ' + v;
    }

    function salaryFieldsTable(tpl, values, lang) {
        var s = salaryFields(tpl, values, lang);
        var cell = 'padding:5px 9px;border:1px solid #ddd';
        var amt = cell + ';text-align:' + (lang === 'ar' ? 'left' : 'right');
        var html = s.rows.map(function (r) {
            return '<tr><td style="' + cell + '">' + esc(r.label) + '</td><td style="' + amt + '">' + money(r.amount, lang) + '</td></tr>';
        }).join('');
        html += '<tr><td style="' + cell + '"><strong>' + (lang === 'ar' ? 'الإجمالي' : 'Total') + '</strong></td><td style="' + amt + '"><strong>' + money(s.total, lang) + '</strong></td></tr>';
        return '<table style="border-collapse:collapse;margin:8px 0 14px"' + (lang === 'ar' ? ' dir="rtl"' : '') + '>' + html + '</table>';
    }

    // mode: 'preview' (missing fields highlighted), 'final' (missing -> empty), 'subject' (plain text)
    function fillText(text, lang, tpl, data, values, mode) {
        var fields = {};
        (tpl.fields || []).forEach(function (f) { fields[f.key] = f; });
        return String(text || '')
            .replace(/\{\{\s*f:([a-z0-9_]+)\s*\}\}/gi, function (all, key) {
                var field = fields[key.toLowerCase()];
                if (!field) return mode === 'final' ? '' : all;
                var val = fieldValue(field, values, lang);
                if (val === '') {
                    var label = (lang === 'ar' && field.label_ar) ? field.label_ar : field.label;
                    if (mode === 'preview') return '<span class="memo-missing">[' + esc(label) + ']</span>';
                    return mode === 'subject' ? '[' + label + ']' : '';
                }
                return mode === 'subject' ? val : esc(val).replace(/\n/g, '<br>');
            })
            .replace(/\{\{\s*([a-z_]+)\s*\}\}/gi, function (all, key) {
                if (key === 'salary_fields_table') return mode === 'subject' ? '' : salaryFieldsTable(tpl, values, lang);
                if (key === 'salary_fields_total') return money(salaryFields(tpl, values, lang).total, lang);
                var d = data[lang] || {};
                if (!(key in d)) return all;
                return mode === 'subject' ? decode(d[key]) : d[key];
            });
    }

    function buildBody(tpl, data, values, mode) {
        var en = fillText(tpl.body, 'en', tpl, data, values, mode);
        var ar = $.trim(tpl.body_ar || '') ? fillText(tpl.body_ar, 'ar', tpl, data, values, mode) : '';
        if (!ar) return '<div dir="ltr" style="text-align:left">' + en + '</div>';
        return '<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;width:100%"><tr>'
            + '<td width="50%" valign="top" dir="ltr" style="width:50%;vertical-align:top;padding:0 16px 0 0;text-align:left;border-right:1px solid #e5e7eb">' + en + '</td>'
            + '<td width="50%" valign="top" dir="rtl" style="width:50%;vertical-align:top;padding:0 0 0 16px;text-align:right;direction:rtl;font-family:Tahoma,Arial,sans-serif">' + ar + '</td>'
            + '</tr></table>';
    }

    function buildSubject(tpl, data, values) {
        var en = $.trim(fillText(tpl.subject, 'en', tpl, data, values, 'subject'));
        var ar = $.trim(fillText(tpl.subject_ar || '', 'ar', tpl, data, values, 'subject'));
        return ar && ar !== en ? en + ' | ' + ar : en;
    }

    // ---------- view / drafts / history (shared with view_employee.php) ----------
    function view(id) {
        $.post(HANDLER, { action: 'get_memo', id: id }, null, 'json').done(function (res) {
            var m = res.data;
            var meta = '<div class="text-left small mb-2">'
                + '<div><strong>' + esc(t('employee', 'Employee')) + ':</strong> ' + esc(m.employee_display) + '</div>'
                + '<div><strong>' + esc(t('memo_type', 'Memo Type')) + ':</strong> ' + esc(m.memo_type_label) + ' &nbsp; ' + statusBadge(m.status) + '</div>'
                + '<div><strong>' + esc(t('to', 'To')) + ':</strong> ' + esc(m.sent_to || '-') + (m.cc ? ' &nbsp; <strong>CC:</strong> ' + esc(m.cc) : '') + '</div>'
                + '<div><strong>' + esc(t('reference_no', 'Reference No.')) + ':</strong> ' + esc(m.reference_no || '-') + ' &nbsp; <strong>' + esc(t('date', 'Date')) + ':</strong> ' + esc(m.updated_at || m.created_at) + '</div>'
                + '<div><strong>' + esc(m.status === 'draft' ? t('saved_by', 'Saved By') : t('sent_by', 'Sent By')) + ':</strong> ' + esc(m.sent_by_name || '-') + '</div>'
                + (m.public_url ? '<div><strong>' + esc(t('public_link', 'Public link')) + ':</strong> <a href="' + esc(m.public_url) + '" target="_blank" rel="noopener">' + esc(m.public_url) + '</a></div>'
                    + '<div><strong>' + esc(t('opened_by_candidate', 'Opened by candidate')) + ':</strong> ' + (m.viewed_at ? esc(m.viewed_at) : '<span class="text-muted">' + esc(t('not_opened_yet', 'Not opened yet')) + '</span>') + '</div>' : '')
                + (m.error_message ? '<div class="text-danger"><strong>' + esc(t('error', 'Error')) + ':</strong> ' + esc(m.error_message) + '</div>' : '')
                + '</div>';
            Swal.fire({
                title: esc(m.subject),
                // body_html was cleaned server-side (memo_clean_html) before it was stored.
                // ad-keep: the dark theme leaves it as the light letter that was emailed.
                html: meta + '<div class="memo-view-body ad-keep">' + m.body_html + '</div>',
                width: 1000,
                showCloseButton: true,
                showDenyButton: m.status === 'draft',
                denyButtonText: '<i class="fa fa-pen"></i> ' + t('open_draft', 'Open Draft'),
                confirmButtonText: t('close', 'Close')
            }).then(function (r) {
                if (r.isDenied) openDraft(m.id);
            });
        }).fail(function (xhr) {
            Swal.fire(t('error', 'Error'), failMsg(xhr, 'Could not load memo.'), 'error');
        });
    }

    // On the compose page, load the draft in place; elsewhere (employee master) go there.
    function openDraft(id) {
        if (window.EmployeeMemos.loadDraft) {
            window.EmployeeMemos.loadDraft(id);
        } else {
            window.location.href = COMPOSE_PAGE + '?draft_id=' + encodeURIComponent(id);
        }
    }

    function deleteDraft(id, onDone) {
        Swal.fire({
            title: t('delete_draft', 'Delete Draft') + '?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: t('delete', 'Delete')
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.post(HANDLER, { action: 'delete_draft', id: id }, null, 'json')
                .done(function (res) {
                    Swal.fire({ icon: 'success', title: res.message, timer: 1400, showConfirmButton: false });
                    if (onDone) onDone(id);
                })
                .fail(function (xhr) { Swal.fire(t('error', 'Error'), failMsg(xhr, 'Could not delete draft.'), 'error'); });
        });
    }

    function initHistoryTable(selector, empId) {
        var showEmployee = !empId;
        var columns = [
            { data: null, render: function (r) { return esc(r.updated_at || r.created_at); } }
        ];
        if (showEmployee) {
            columns.push({ data: null, render: function (r) {
                if (r.emp_id) return esc(r.employee_name) + ' <small class="text-muted">(' + esc(r.emp_id) + ')</small>';
                // Candidate letter (job offer): no employee record; eye = the candidate opened the link.
                return esc(r.employee_name || '-') + ' <span class="badge badge-info">' + esc(t('candidate', 'Candidate')) + '</span>'
                    + (r.viewed_at ? ' <i class="fa fa-eye text-success" title="' + esc(t('opened_by_candidate', 'Opened by candidate')) + ': ' + esc(r.viewed_at) + '"></i>' : '');
            } });
        }
        columns.push(
            { data: 'memo_type_label', render: esc },
            { data: 'subject', render: esc },
            { data: 'reference_no', render: function (v) { return esc(v || '-'); } },
            { data: 'status', render: statusBadge },
            { data: 'sent_by_name', render: function (v) { return esc(v || '-'); } },
            { data: null, orderable: false, render: function (r) {
                var html = '<button type="button" class="btn btn-sm btn-outline-primary memo-view-btn" data-id="' + r.id + '" title="' + esc(t('view', 'View')) + '"><i class="fa fa-eye"></i></button>';
                if (r.status === 'draft') {
                    html += '<button type="button" class="btn btn-sm btn-outline-warning memo-open-draft-btn" data-id="' + r.id + '" title="' + esc(t('open_draft', 'Open Draft')) + '"><i class="fa fa-pen"></i></button>'
                        + '<button type="button" class="btn btn-sm btn-outline-danger memo-delete-draft-btn" data-id="' + r.id + '" title="' + esc(t('delete_draft', 'Delete Draft')) + '"><i class="fa fa-trash"></i></button>';
                }
                return '<div class="btn-group text-nowrap" role="group">' + html + '</div>';
            } }
        );
        var table = $(selector).DataTable({
            ajax: {
                url: HANDLER, type: 'POST',
                data: { action: 'list_memos', emp_id: empId || '' },
                dataSrc: function (res) { return (res && res.data) || []; }
            },
            columns: columns,
            order: [[0, 'desc']],
            responsive: true,
            pageLength: 10,
            language: { emptyTable: t('no_memos_yet', 'No memos yet.') }
        });
        $(selector)
            .on('click', '.memo-view-btn', function () { view($(this).data('id')); })
            .on('click', '.memo-open-draft-btn', function () { openDraft($(this).data('id')); })
            .on('click', '.memo-delete-draft-btn', function () {
                deleteDraft($(this).data('id'), function (id) {
                    table.ajax.reload(null, false);
                    if (window.EmployeeMemos.onDraftDeleted) window.EmployeeMemos.onDraftDeleted(id);
                });
            });
        return table;
    }

    window.EmployeeMemos = { view: view, initHistoryTable: initHistoryTable };

    // ---------- compose page ----------
    $(function () {
        if (!$('#memoEmployee').length) {
            return;
        }
        var selectedType = '';
        var draftId = 0;
        var templates = [];
        var state = { tpl: null, data: null, values: {}, options: {} };
        var manual = false;
        var subjectTouched = false;
        var $compose = $('#memoCompose');
        var historyTable = initHistoryTable('#memoHistory', '');

        $('#memoEmployee').select2({
            placeholder: t('search_employee', 'Search employee by ID or name...'),
            allowClear: true,
            minimumInputLength: 1,
            ajax: {
                url: HANDLER,
                dataType: 'json',
                delay: 250,
                data: function (params) { return { action: 'search_employees', q: params.term }; },
                processResults: function (data) { return { results: (data && data.results) || [] }; }
            },
            // Name (ID) with department and company as badges in the dropdown list.
            templateResult: function (item) {
                if (item.loading || !item.name) return item.text;
                return $('<span>').append(
                    $('<strong>').text(item.name),
                    $('<span class="text-muted">').text(' (' + item.id + ')'),
                    item.dept ? $('<span class="badge badge-light ml-2">').text(item.dept) : '',
                    item.company ? $('<span class="badge badge-info ml-1">').text(item.company) : ''
                );
            }
        });

        $('#memoBody').summernote(editorOptions(420));

        function setDraft(id) {
            draftId = id || 0;
            $('#memoDraftFlag').toggleClass('show', draftId > 0).find('span').text(draftId || '');
        }

        // Candidate letter (job offer): written to someone who is not an employee yet, so
        // no employee is picked - the name and terms come from the Details fields.
        function isCandidateType(key) {
            return templates.some(function (tpl) { return tpl.key === key && tpl.candidate; });
        }

        function selectType(key) {
            selectedType = key || '';
            var cand = isCandidateType(selectedType);
            $('#memoEmployee').prop('disabled', cand);
            $('#memoCandidateNote').toggle(cand);
            $('#memoTypeGrid .memo-type-btn').removeClass('active')
                .filter(function () { return $(this).data('type') === selectedType; }).addClass('active');
        }

        function renderGrid() {
            var html = templates.map(function (tpl) {
                return '<button type="button" class="memo-type-btn" data-type="' + esc(tpl.key) + '" title="' + esc(tpl.label_ar || '') + '">'
                    + '<i class="fa-solid ' + esc(tpl.icon) + '"></i><span>' + esc(tpl.label) + '</span></button>';
            }).join('');
            $('#memoTypeGrid').html(html || '<div class="text-muted">' + esc(t('no_active_templates', 'No active templates - add one from Manage Templates.')) + '</div>');
            selectType(selectedType);
        }

        function loadTemplates() {
            return $.post(HANDLER, { action: 'list_templates' }, null, 'json').done(function (res) {
                templates = res.data || [];
                renderGrid();
            });
        }

        // ----- detail fields -----
        // Dropdown fed from the database (job titles, departments, locations...).
        // The chosen option fills both sides: {id, en, ar}.
        function selectInput(field, value) {
            if (field.source === 'locations') return locationInput(field, value);
            var opts = (state.options && state.options[field.source]) || [];
            var cur = value && typeof value === 'object' ? String(value.id || '') : '';
            var html = '<option value=""></option>' + opts.map(function (o) {
                return '<option value="' + esc(o.id) + '"' + (String(o.id) === cur ? ' selected' : '') + '>' + esc(o.en) + (o.ar && o.ar !== o.en ? ' | ' + esc(o.ar) : '') + '</option>';
            }).join('');
            return '<select class="form-control memo-select" id="mf_' + esc(field.key) + '" data-key="' + esc(field.key) + '" data-source="' + esc(field.source) + '">' + html + '</select>';
        }

        // ----- Location fields: Company > City > Location, three linked select2 boxes -----
        function locTree() {
            var tr = state.options && state.options.locations;
            return tr && tr.companies ? tr : { companies: [], cities: [], locations: [] };
        }

        function optHtml(list, cur) {
            return '<option value=""></option>' + list.map(function (o) {
                return '<option value="' + esc(o.id) + '"' + (String(o.id) === String(cur || '') ? ' selected' : '') + '>'
                    + esc(o.en) + (o.ar && o.ar !== o.en ? ' | ' + esc(o.ar) : '') + '</option>';
            }).join('');
        }

        function citiesFor(companyId) {
            var tr = locTree();
            if (!companyId) return [];
            var comp = tr.companies.filter(function (c) { return String(c.id) === String(companyId); })[0];
            var ids = comp && comp.city_ids && comp.city_ids.length ? comp.city_ids.map(String) : null;
            // A company with no staff placed yet can go to any city.
            return ids ? tr.cities.filter(function (c) { return ids.indexOf(String(c.id)) !== -1; }) : tr.cities;
        }

        function locationsFor(cityId) {
            if (!cityId) return [];
            return locTree().locations.filter(function (l) { return String(l.city_id) === String(cityId); });
        }

        function locationInput(field, value) {
            var v = value && typeof value === 'object' ? value : {};
            var tr = locTree();
            var cityId = v.city_id || '';
            if (v.id && !cityId) {
                var l = tr.locations.filter(function (x) { return String(x.id) === String(v.id); })[0];
                cityId = l ? l.city_id : '';
            }
            var cities = citiesFor(v.company_id);
            if (cityId && !cities.some(function (c) { return String(c.id) === String(cityId); })) cities = tr.cities;
            var k = esc(field.key);
            var box = function (cls, lbl, html, disabled) {
                return '<div><small class="text-muted d-block mb-1">' + lbl + '</small>'
                    + '<select class="form-control ' + cls + '" data-key="' + k + '"' + (disabled ? ' disabled' : '') + '>' + html + '</select></div>';
            };
            return '<div class="memo-loc-cascade" id="mf_' + k + '">'
                + box('memo-loc-company', t('company', 'Company') + ' | الشركة', optHtml(tr.companies, v.company_id), false)
                + box('memo-loc-city', t('city_label', 'City') + ' | المدينة', optHtml(v.company_id ? cities : [], cityId), !v.company_id)
                + box('memo-loc-location', t('location_label', 'Location') + ' | الموقع', optHtml(locationsFor(cityId), v.id), !cityId)
                + '</div>';
        }

        function initLocSelect($s, ph) {
            if ($s.hasClass('select2-hidden-accessible')) $s.select2('destroy');
            $s.select2({ width: '100%', allowClear: true, placeholder: ph });
        }

        function initLocationCascades() {
            $('#memoFields .memo-loc-company').each(function () { initLocSelect($(this), t('select_company', 'Select company...')); });
            $('#memoFields .memo-loc-city').each(function () { initLocSelect($(this), t('select_city', 'Select city...')); });
            $('#memoFields .memo-loc-location').each(function () { initLocSelect($(this), t('select_location', 'Select location...')); });
        }

        function storeLocation(key) {
            var $w = $('#mf_' + key);
            var tr = locTree();
            var find = function (list, id) { return list.filter(function (x) { return String(x.id) === String(id); })[0]; };
            var comp = find(tr.companies, $w.find('.memo-loc-company').val());
            var city = find(tr.cities, $w.find('.memo-loc-city').val());
            var loc = find(tr.locations, $w.find('.memo-loc-location').val());
            if (!loc) {
                state.values[key] = comp ? { company_id: comp.id, city_id: city ? city.id : '', en: '', ar: '' } : '';
            } else {
                // Memo text: "Filters, Jeddah - FILTER" / "فلاتر، جدة - فلتر"
                var en = loc.en + (city ? ', ' + city.en : '') + (comp ? ' - ' + comp.en : '');
                var ar = loc.ar + (city ? '، ' + city.ar : '') + (comp ? ' - ' + comp.ar : '');
                state.values[key] = { id: loc.id, company_id: comp ? comp.id : '', city_id: city ? city.id : '', en: en, ar: ar };
            }
            refresh();
        }

        $('#memoFields').on('change', '.memo-loc-company', function () {
            var $w = $(this).closest('.memo-loc-cascade');
            var compId = $(this).val();
            var $city = $w.find('.memo-loc-city');
            var $loc = $w.find('.memo-loc-location');
            $city.html(optHtml(citiesFor(compId), '')).prop('disabled', !compId);
            $loc.html(optHtml([], '')).prop('disabled', true);
            initLocSelect($city, t('select_city', 'Select city...'));
            initLocSelect($loc, t('select_location', 'Select location...'));
            storeLocation($(this).data('key'));
        });

        $('#memoFields').on('change', '.memo-loc-city', function () {
            var $w = $(this).closest('.memo-loc-cascade');
            var cityId = $(this).val();
            var $loc = $w.find('.memo-loc-location');
            $loc.html(optHtml(locationsFor(cityId), '')).prop('disabled', !cityId);
            initLocSelect($loc, t('select_location', 'Select location...'));
            storeLocation($(this).data('key'));
        });

        $('#memoFields').on('change', '.memo-loc-location', function () {
            storeLocation($(this).data('key'));
        });

        // Breadcrumb pills for a dropdown option: Company > City > Location (current one highlighted).
        function optionTrail(o, fallback) {
            if (!o) return fallback;
            var segs = (o.path || []).map(function (p) {
                var parts = p.en.split(', ');
                var txt = parts.length > 2 ? parts[0] + ' +' + (parts.length - 1) : p.en;
                return '<span class="memo-crumb" title="' + esc(p.en) + (p.ar && p.ar !== p.en ? ' | ' + esc(p.ar) : '') + '">' + esc(txt) + '</span>';
            });
            var name = o.name_en || o.en;
            var nameAr = o.name_ar || o.ar;
            segs.push('<span class="memo-crumb memo-crumb-current">' + esc(name) + (nameAr && nameAr !== name ? ' <span class="memo-crumb-ar">' + esc(nameAr) + '</span>' : '') + '</span>');
            return $('<span class="memo-trail">' + segs.join('<i class="fa fa-angle-right memo-crumb-sep"></i>') + '</span>');
        }

        function fieldInput(field, lang, value) {
            var id = 'mf_' + field.key + (lang ? '_' + lang : '');
            var attrs = ' id="' + id + '" class="form-control memo-field-input" data-key="' + esc(field.key) + '"' + (lang ? ' data-lang="' + lang + '"' : '')
                + (lang === 'ar' ? ' dir="rtl"' : '');
            if (field.type === 'textarea' || field.type === 'assets') {
                return '<textarea rows="3"' + attrs + '>' + esc(value) + '</textarea>';
            }
            if (field.type === 'date') {
                // The app's bootstrap-datepicker (initialised in renderFields), same as other pages.
                return '<div class="input-group"><input type="text" autocomplete="off" placeholder="yyyy-mm-dd"' + attrs.replace('class="form-control', 'class="form-control memo-date') + ' value="' + esc(value) + '">'
                    + '<div class="input-group-append"><span class="input-group-text"><i class="fa fa-calendar"></i></span></div></div>';
            }
            var type = field.type === 'number' ? 'number' : 'text';
            return '<input type="' + type + '"' + (type === 'number' ? ' step="0.01"' : '') + attrs + ' value="' + esc(value) + '">';
        }

        function renderFields() {
            var tpl = state.tpl;
            // Arabic text already there (e.g. an opened draft) is kept until HR clicks Translate.
            arManual = {};
            Object.keys(state.values).forEach(function (k) {
                var v = state.values[k];
                if (v && typeof v === 'object' && !v.id && $.trim(v.ar || '') !== '') arManual[k] = true;
            });
            if (!tpl || !(tpl.fields || []).length) {
                $('#memoFields').html('<div class="text-muted small">' + esc(t('memo_no_fields', 'This memo has no detail fields - it is filled from the employee record.')) + '</div>');
                return;
            }
            salShown = {};
            var salaryDone = false;
            var html = '<div class="memo-fields-grid">' + tpl.fields.map(function (f) {
                if (isSalaryField(f)) {
                    // All salary elements share one box, drawn where the first one is.
                    if (salaryDone) return '';
                    salaryDone = true;
                    return salaryCard();
                }
                var req = f.required ? ' <span class="text-danger">*</span>' : '';
                var label = esc(f.label) + (f.label_ar ? ' <span class="text-muted">| ' + esc(f.label_ar) + '</span>' : '') + req;
                var v = state.values[f.key];
                var body;
                if (f.type === 'select') {
                    body = selectInput(f, v);
                } else if (f.bilingual) {
                    v = v && typeof v === 'object' ? v : { en: v || '', ar: '' };
                    // Asset list field: a search box that adds inventory lines to both text boxes below.
                    body = (f.type === 'assets' ? '<select class="form-control memo-asset-pick" data-key="' + esc(f.key) + '"></select>'
                        + '<small class="form-text text-muted mb-2">' + esc(t('memo_asset_pick_hint', 'Pick from the asset list to add a line below - you can still edit the text.')) + '</small>' : '')
                        + '<div class="memo-field-pair">'
                        + '<div><span class="lang-tag">English</span>' + fieldInput(f, 'en', v.en) + '</div>'
                        + '<div dir="rtl"><span class="lang-tag">العربية</span>'
                        + ' <a href="#" class="memo-tr-btn small mr-2" data-key="' + esc(f.key) + '" title="' + esc(t('translate_from_english', 'Translate from English')) + '"><i class="fa fa-language"></i> ' + esc(t('translate', 'Translate')) + '</a>'
                        + '<span class="memo-tr-status small text-muted mr-2" data-key="' + esc(f.key) + '"></span>'
                        + fieldInput(f, 'ar', v.ar) + '</div></div>';
                } else {
                    body = fieldInput(f, '', v && typeof v === 'object' ? v.en : (v || ''));
                }
                return '<div class="memo-field-card' + (f.type === 'textarea' || f.type === 'assets' || (f.type === 'select' && f.source === 'locations') ? ' memo-field-wide' : '') + '"><label>' + label + '</label>' + body + '</div>';
            }).join('') + '</div>';
            $('#memoFields').html(html);
            $('#memoFields .memo-select').each(function () {
                var opts = (state.options && state.options[$(this).data('source')]) || [];
                var byId = {};
                opts.forEach(function (o) { byId[String(o.id)] = o; });
                $(this).select2({
                    width: '100%', allowClear: true, placeholder: t('select', 'Select...'),
                    templateResult: function (item) { return optionTrail(byId[String(item.id)], item.text); },
                    templateSelection: function (item) { return optionTrail(byId[String(item.id)], item.text); },
                    // Search also matches the company / city / parent names in the trail.
                    matcher: function (params, item) {
                        var q = $.trim(params.term || '').toLowerCase();
                        if (!q) return item;
                        var o = byId[String(item.id)];
                        var hay = (item.text + ' ' + (o && o.path ? o.path.map(function (p) { return p.en + ' ' + p.ar; }).join(' ') : '')).toLowerCase();
                        return q.split(/\s+/).every(function (w) { return hay.indexOf(w) !== -1; }) ? item : null;
                    }
                });
            });
            initLocationCascades();
            initAssetPickers();
            updateSalaryTotal();
            if ($.fn.datepicker) {
                $('#memoFields .memo-date').datepicker({
                    format: 'yyyy-mm-dd',
                    autoclose: true,
                    todayHighlight: true,
                    clearBtn: true
                });
            }
        }

        // ----- Asset list fields: search the inventory by serial no. or asset name -----
        function initAssetPickers() {
            $('#memoFields .memo-asset-pick').select2({
                width: '100%',
                placeholder: t('memo_asset_search', 'Search asset by serial / plate no. or name...'),
                ajax: {
                    url: HANDLER,
                    dataType: 'json',
                    delay: 250,
                    data: function (params) { return { action: 'search_assets', q: params.term || '', emp_id: $('#memoEmployee').val() || '' }; },
                    processResults: function (data) { return { results: (data && data.results) || [] }; }
                },
                templateResult: function (item) {
                    if (item.loading || !item.name) return item.text;
                    var badge = item.status === 'Available'
                        ? $('<span class="badge badge-success ml-2">').text(t('available', 'Available'))
                        : $('<span class="badge ml-2">').addClass(item.own ? 'badge-info' : 'badge-warning').text(item.holder || item.status);
                    return $('<span>').append(
                        $('<strong>').text(item.name),
                        item.serial ? $('<span class="ml-2">').text(item.serial) : '',
                        item.desc ? $('<span class="text-muted ml-2">').text(item.desc) : '',
                        badge
                    );
                }
            });
        }

        $('#memoFields').on('select2:select', '.memo-asset-pick', function (e) {
            var key = $(this).data('key');
            var item = e.params.data;
            $(this).val(null).trigger('change');
            if (!item || !item.en) return;
            var cur = state.values[key] && typeof state.values[key] === 'object' ? state.values[key] : { en: '', ar: '' };
            var en = String(cur.en || '').replace(/\s+$/, '');
            if (en.split('\n').indexOf(item.en) !== -1) return; // already listed
            // Empty Arabic box = same as English so far; keep those lines.
            var ar = (String(cur.ar || '').replace(/\s+$/, '')) || en;
            cur.en = (en ? en + '\n' : '') + item.en;
            cur.ar = (ar ? ar + '\n' : '') + (item.ar || item.en);
            state.values[key] = cur;
            // Both sides are set here: drop any pending auto-translation of the English box.
            clearTimeout(trTimers[key]);
            trSeq[key] = (trSeq[key] || 0) + 1;
            $('.memo-tr-status[data-key="' + key + '"]').text('');
            $('#mf_' + key + '_en').val(cur.en);
            $('#mf_' + key + '_ar').val(cur.ar);
            refresh();
        });

        // ----- Salary box: the template's sal_ number fields (salary elements) -----
        // Required elements are always listed; the others are added one at a time with
        // the Add button, so HR only sees the elements this offer actually has.
        var salShown = {}; // key -> true for an element added but not filled yet

        function isSalaryField(f) {
            return f.type === 'number' && f.key.indexOf('sal_') === 0;
        }

        function salaryValue(key) {
            var v = state.values[key];
            return $.trim(v && typeof v === 'object' ? v.en : (v == null ? '' : v));
        }

        function salaryCard() {
            var fields = (state.tpl.fields || []).filter(isSalaryField);
            var name = function (f) { return esc(f.label) + (f.label_ar ? ' <span class="text-muted">| ' + esc(f.label_ar) + '</span>' : ''); };
            var visible = fields.filter(function (f) { return f.required || salShown[f.key] || salaryValue(f.key) !== ''; });
            var hidden = fields.filter(function (f) { return visible.indexOf(f) === -1; });
            var rows = visible.map(function (f) {
                return '<div class="memo-sal-row">'
                    + '<div class="memo-sal-name">' + name(f) + (f.required ? ' <span class="text-danger">*</span>' : '') + '</div>'
                    + '<div class="memo-sal-amount">' + fieldInput(f, '', salaryValue(f.key)) + '</div>'
                    + (f.required ? '<span class="memo-sal-del"></span>'
                        : '<button type="button" class="btn btn-sm btn-outline-danger memo-sal-del memo-sal-del-btn" data-key="' + esc(f.key) + '" title="' + esc(t('remove', 'Remove')) + '"><i class="fa fa-xmark"></i></button>')
                    + '</div>';
            }).join('');
            var add = hidden.length
                ? '<div class="memo-sal-add"><select class="form-control memo-sal-pick"><option value="">' + esc(t('select_salary_element', 'Select salary element...')) + '</option>'
                    + hidden.map(function (f) { return '<option value="' + esc(f.key) + '">' + esc(f.label) + (f.label_ar ? ' | ' + esc(f.label_ar) : '') + '</option>'; }).join('')
                    + '</select><button type="button" class="btn btn-primary memo-sal-add-btn"><i class="fa fa-plus mr-1"></i>' + esc(t('add', 'Add')) + '</button></div>'
                : '';
            return '<div class="memo-field-card memo-field-wide" id="memoSalaryCard"><label>' + esc(t('salary', 'Salary')) + ' <span class="text-muted">| الراتب</span></label>'
                + rows + add
                + '<div class="memo-sal-total">' + esc(t('total_salary', 'Total Salary')) + ' | الإجمالي: <strong id="memoSalTotal"></strong></div></div>';
        }

        function updateSalaryTotal() {
            if (!state.tpl || !$('#memoSalTotal').length) return;
            $('#memoSalTotal').text(money(salaryFields(state.tpl, state.values, 'en').total, 'en'));
        }

        function redrawSalary() {
            $('#memoSalaryCard').replaceWith(salaryCard());
            updateSalaryTotal();
        }

        $('#memoFields').on('click', '.memo-sal-add-btn', function () {
            var key = $('#memoSalaryCard .memo-sal-pick').val();
            if (!key) {
                $('#memoSalaryCard .memo-sal-pick').focus();
                return;
            }
            salShown[key] = true;
            redrawSalary();
            $('#mf_' + key).focus();
        });

        $('#memoFields').on('click', '.memo-sal-del-btn', function () {
            var key = $(this).data('key');
            delete salShown[key];
            state.values[key] = '';
            redrawSalary();
            refresh();
        });

        // Clicking the calendar icon opens the picker too.
        $('#memoFields').on('click', '.input-group-text', function () {
            $(this).closest('.input-group').find('.memo-date').datepicker('show');
        });

        $('#memoFields').on('input change', '.memo-field-input', function (e) {
            var key = $(this).data('key');
            var lang = $(this).data('lang');
            if (lang) {
                var cur = state.values[key] && typeof state.values[key] === 'object' ? state.values[key] : { en: '', ar: '' };
                cur[lang] = $(this).val();
                state.values[key] = cur;
                if (lang === 'ar' && e.type === 'input' && !$(this).data('autoFilling')) {
                    // HR typed in the Arabic box: stop overwriting it - unless they cleared it.
                    arManual[key] = $.trim($(this).val()) !== '';
                }
                if (lang === 'en' && e.type === 'input') {
                    scheduleTranslate(key);
                }
            } else {
                state.values[key] = $(this).val();
            }
            refresh();
        });

        $('#memoFields').on('change', '.memo-select', function () {
            var key = $(this).data('key');
            var id = $(this).val();
            var opt = ((state.options && state.options[$(this).data('source')]) || []).filter(function (o) { return String(o.id) === String(id); })[0];
            state.values[key] = opt ? { id: opt.id, en: opt.en, ar: opt.ar } : '';
            refresh();
        });

        // ----- English -> Arabic auto translation for bilingual fields -----
        var arManual = {};    // key -> true once HR edited the Arabic box themselves
        var trTimers = {};
        var trSeq = {};

        function scheduleTranslate(key, force) {
            clearTimeout(trTimers[key]);
            if (arManual[key] && !force) return;
            trTimers[key] = setTimeout(function () { translateField(key, force); }, force ? 0 : 700);
        }

        function translateField(key, force) {
            var $en = $('#mf_' + key + '_en');
            var $ar = $('#mf_' + key + '_ar');
            var $status = $('.memo-tr-status[data-key="' + key + '"]');
            var text = $.trim($en.val() || '');
            if (!$ar.length || (arManual[key] && !force)) return;
            var seq = (trSeq[key] || 0) + 1;
            trSeq[key] = seq;
            if (text === '') {
                setArabic(key, '');
                $status.text('');
                return;
            }
            $status.text(t('translating', 'Translating...'));
            $.post(HANDLER, { action: 'translate', q: text }, null, 'json')
                .done(function (res) {
                    // Ignore late answers for text that has changed since, or if HR took over.
                    if (seq !== trSeq[key] || (arManual[key] && !force)) return;
                    setArabic(key, res.text || '');
                    if (force) arManual[key] = false;
                    $status.text(res.translated === false ? t('translation_unavailable', 'No translation - please type the Arabic') : '');
                })
                .fail(function () {
                    if (seq === trSeq[key]) $status.text(t('translation_failed', 'Translation failed - please type the Arabic'));
                });
        }

        function setArabic(key, text) {
            var $ar = $('#mf_' + key + '_ar');
            $ar.data('autoFilling', true).val(text).trigger('input').data('autoFilling', false);
        }

        $('#memoFields').on('click', '.memo-tr-btn', function (e) {
            e.preventDefault();
            var key = $(this).data('key');
            if (!$.trim($('#mf_' + key + '_en').val() || '')) return;
            scheduleTranslate(key, true);
        });

        function refresh() {
            if (!state.tpl) return;
            if (!subjectTouched) {
                $('#memoSubject').val(buildSubject(state.tpl, state.data, state.values));
            }
            if (!manual) {
                $('#memoPreview').html(buildBody(state.tpl, state.data, state.values, 'preview'));
            }
            updateSalaryTotal();
        }

        $('#memoSubject').on('input', function () { subjectTouched = true; });

        function setManual(on, html) {
            manual = !!on;
            $('#memoManual').prop('checked', manual);
            $('#memoManualWrap').toggle(manual);
            $('#memoPreview').toggle(!manual);
            if (manual) {
                $('#memoBody').summernote('code', html != null ? html : buildBody(state.tpl, state.data, state.values, 'final'));
            } else {
                refresh();
            }
        }

        $('#memoManual').on('change', function () {
            var on = $(this).is(':checked');
            if (on) {
                setManual(true);
                return;
            }
            Swal.fire({
                title: t('memo_leave_manual', 'Rebuild the memo from the fields?'),
                text: t('memo_leave_manual_text', 'Your manual text edits will be lost.'),
                icon: 'warning',
                showCancelButton: true
            }).then(function (r) { setManual(!r.isConfirmed); });
        });

        function isDirty() {
            if ($compose.hasClass('memo-compose-disabled')) return false;
            if (manual) return true;
            return Object.keys(state.values).some(function (k) {
                var v = state.values[k];
                return v && typeof v === 'object' ? $.trim(v.en || '') || $.trim(v.ar || '') : $.trim(v || '');
            });
        }

        // Fetch template + employee data. keepValues: switching employee keeps what was typed.
        function loadTemplate(keepValues) {
            var candidate = isCandidateType(selectedType);
            var empId = candidate ? '' : $('#memoEmployee').val();
            if (!selectedType || (!empId && !candidate)) {
                $compose.addClass('memo-compose-disabled');
                return $.Deferred().reject().promise();
            }
            return $.post(HANDLER, { action: 'render_template', emp_id: empId, memo_type: selectedType }, null, 'json')
                .done(function (res) {
                    var sameTpl = state.tpl && state.tpl.key === res.template.key;
                    state.tpl = res.template;
                    state.data = res.data;
                    state.options = res.options || {};
                    if (!(keepValues && sameTpl)) state.values = {};
                    subjectTouched = false;
                    $('#memoRef').val(res.reference_no);
                    // Reference number is part of the filled text.
                    state.data.en.reference_no = state.data.ar.reference_no = esc(res.reference_no);
                    $('#memoTo').val(res.email || '');
                    var hint = '';
                    if (res.candidate) {
                        hint = esc(t('candidate_email_hint', 'Enter the candidate\'s email address - the offer and its print link are sent there.'));
                    } else if (!res.email) {
                        hint = '<span class="text-danger">' + esc(t('employee_has_no_email', 'This employee has no email on file - enter one.')) + '</span>';
                    } else if (res.personal_email && res.personal_email !== res.email) {
                        hint = esc(t('personal_email', 'Personal email')) + ': ' + esc(res.personal_email);
                    }
                    $('#memoToHint').html(hint);
                    renderFields();
                    setManual(false);
                    $compose.removeClass('memo-compose-disabled');
                })
                .fail(function (xhr) {
                    Swal.fire(t('error', 'Error'), failMsg(xhr, 'Could not load template.'), 'error');
                });
        }

        $('#memoRef').on('input', function () {
            if (!state.data) return;
            state.data.en.reference_no = state.data.ar.reference_no = esc($(this).val());
            refresh();
        });

        // Switching employee/type can replace the text - ask first if there's unsaved work.
        function confirmReplace() {
            if (!isDirty()) return $.Deferred().resolve(true).promise();
            var d = $.Deferred();
            Swal.fire({
                title: t('replace_memo_text', 'Replace the current memo?'),
                text: draftId ? t('replace_memo_draft', 'The opened draft stays saved; unsaved changes will be lost.') : t('replace_memo_unsaved', 'What you entered will be lost. Save a draft first if you need it.'),
                icon: 'warning',
                showCancelButton: true
            }).then(function (r) { d.resolve(r.isConfirmed); });
            return d.promise();
        }

        $('#memoTypeGrid').on('click', '.memo-type-btn', function () {
            var key = $(this).data('type');
            if (key === selectedType && state.tpl) return;
            confirmReplace().then(function (ok) {
                if (!ok) return;
                setDraft(0);
                selectType(key);
                if (!isCandidateType(key) && !$('#memoEmployee').val()) {
                    $('#memoEmployee').select2('open');
                    return;
                }
                loadTemplate(false);
            });
        });

        var lastEmp = $('#memoEmployee').val();
        var suppressEmpChange = false; // programmatic changes (loading a draft, undo) skip the confirm
        $('#memoEmployee').on('change', function () {
            if (suppressEmpChange) return;
            lastEmp = $(this).val();
            // Keep the typed details; only the employee's own data changes.
            loadTemplate(true);
        });

        $('#memoNew').on('click', function () {
            confirmReplace().then(function (ok) {
                if (!ok) return;
                setDraft(0);
                selectType('');
                state = { tpl: null, data: null, values: {}, options: {} };
                $('#memoSubject, #memoRef, #memoTo, #memoCc').val('');
                $('#memoToHint').html('');
                $('#memoFields, #memoPreview').html('');
                setManual(false);
                $compose.addClass('memo-compose-disabled');
            });
        });

        function missingRequired() {
            return (state.tpl.fields || []).filter(function (f) {
                return f.required && fieldValue(f, state.values, 'en') === '';
            }).map(function (f) { return f.label; });
        }

        function payload(action) {
            return {
                action: action,
                draft_id: draftId,
                emp_id: isCandidateType(selectedType) ? '' : $('#memoEmployee').val(),
                memo_type: selectedType,
                to_email: $.trim($('#memoTo').val()),
                cc: $.trim($('#memoCc').val()),
                reference_no: $.trim($('#memoRef').val()),
                subject: $.trim($('#memoSubject').val()),
                body: manual ? $('#memoBody').summernote('code') : buildBody(state.tpl, state.data, state.values, 'final'),
                field_values: JSON.stringify(state.values),
                is_manual: manual ? 1 : 0
            };
        }

        $('#memoSaveDraft').on('click', function () {
            if (!state.tpl) return;
            var data = payload('save_draft');
            if (!data.subject) {
                Swal.fire(t('error', 'Error'), t('memo_subject_required', 'Subject is required.'), 'warning');
                return;
            }
            $.post(HANDLER, data, null, 'json')
                .done(function (res) {
                    setDraft(res.memo_id);
                    $('#memoRef').val(res.reference_no);
                    Swal.fire({ icon: 'success', title: res.message, timer: 1400, showConfirmButton: false });
                    historyTable.ajax.reload(null, false);
                })
                .fail(function (xhr) { Swal.fire(t('error', 'Error'), failMsg(xhr, 'Could not save draft.'), 'error'); });
        });

        $('#memoSend').on('click', function () {
            if (!state.tpl) return;
            var missing = manual ? [] : missingRequired();
            if (missing.length) {
                Swal.fire(t('memo_fill_fields', 'Please fill in the details'), '<ul class="text-left mb-0"><li>' + missing.map(esc).join('</li><li>') + '</li></ul>', 'warning');
                return;
            }
            var data = payload('send_memo');
            if (!data.to_email || !data.subject) {
                Swal.fire(t('error', 'Error'), t('memo_required_fields', 'Email and subject are required.'), 'warning');
                return;
            }
            Swal.fire({
                title: t('send_memo', 'Send Memo') + '?',
                html: esc(t('send_memo_to', 'Send to')) + ' <strong>' + esc(data.to_email) + '</strong>',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: t('send', 'Send')
            }).then(function (r) {
                if (!r.isConfirmed) return;
                Swal.fire({ title: t('sending', 'Sending...'), allowOutsideClick: false, didOpen: function () { Swal.showLoading(); } });
                $.post(HANDLER, data, null, 'json')
                    .done(function (res) {
                        Swal.fire({
                            icon: 'success', title: t('success', 'Success'),
                            html: esc(res.message) + (res.public_url
                                ? '<div class="mt-2 small">' + esc(t('offer_link_note', 'The candidate can open and print the letter here:')) + '<br><a href="' + esc(res.public_url) + '" target="_blank" rel="noopener" style="word-break:break-all">' + esc(res.public_url) + '</a></div>'
                                : '')
                        });
                        historyTable.ajax.reload(null, false);
                        setDraft(0);
                        loadTemplate(false); // fresh form + reference number for the next memo
                    })
                    .fail(function (xhr) {
                        Swal.fire(t('error', 'Error'), failMsg(xhr, 'Sending failed.'), 'error');
                        historyTable.ajax.reload(null, false);
                        // A failed send of an opened draft consumed that draft (now "failed").
                        if (xhr.responseJSON && xhr.responseJSON.memo_id) setDraft(0);
                    });
            });
        });

        window.EmployeeMemos.loadDraft = function (id) {
            $.post(HANDLER, { action: 'get_memo', id: id }, null, 'json').done(function (res) {
                var m = res.data;
                if (m.status !== 'draft') {
                    Swal.fire(t('error', 'Error'), t('not_a_draft', 'This memo was already sent.'), 'info');
                    return;
                }
                if (m.emp_id) { // a candidate letter has no employee
                    suppressEmpChange = true;
                    var $sel = $('#memoEmployee');
                    if (!$sel.find('option[value="' + m.emp_id + '"]').length) {
                        $sel.append(new Option(m.employee_display, m.emp_id, true, true));
                    }
                    $sel.val(m.emp_id).trigger('change');
                    lastEmp = m.emp_id;
                    suppressEmpChange = false;
                }

                selectType(m.memo_type);
                state.tpl = null;
                loadTemplate(false).done(function () {
                    state.values = m.field_values && typeof m.field_values === 'object' ? m.field_values : {};
                    renderFields();
                    $('#memoTo').val(m.sent_to || $('#memoTo').val());
                    $('#memoCc').val(m.cc || '');
                    $('#memoRef').val(m.reference_no || '').trigger('input');
                    setManual(+m.is_manual === 1, m.body_html);
                    $('#memoSubject').val(m.subject);
                    subjectTouched = true;
                    setDraft(m.id);
                    $('html, body').animate({ scrollTop: $compose.offset().top - 90 }, 250);
                });
            }).fail(function (xhr) {
                Swal.fire(t('error', 'Error'), failMsg(xhr, 'Could not load draft.'), 'error');
            });
        };
        window.EmployeeMemos.onDraftDeleted = function (id) {
            if (id === draftId) setDraft(0);
        };

        // ---------- Manage Templates ----------
        var $tplModal = $('#memoTplModal');
        var tplEditorReady = false;
        var allTemplates = [];
        var employeePlaceholders = {};
        var lookupSources = {};

        function renderPlaceholderChips() {
            var chips = Object.keys(employeePlaceholders).map(function (k) {
                return '<button type="button" class="memo-ph-chip" data-ph="' + esc(k) + '" title="' + esc(employeePlaceholders[k]) + '">{{' + esc(k) + '}}</button>';
            });
            readFieldRows().forEach(function (f) {
                chips.push('<button type="button" class="memo-ph-chip" style="background:#fef3c7;color:#92400e" data-ph="f:' + esc(f.key) + '" title="' + esc(f.label) + '">{{f:' + esc(f.key) + '}}</button>');
            });
            $('#tplPlaceholders').html(chips.join(''));
        }

        function fieldRow(f) {
            f = f || { key: '', label: '', label_ar: '', type: 'text', source: '', bilingual: true, required: true };
            var typeLabels = { text: 'Text', textarea: 'Long text', date: 'Date', number: 'Number', select: 'Dropdown (from database)', assets: 'Asset list (from inventory)' };
            var types = Object.keys(typeLabels).map(function (tp) {
                return '<option value="' + tp + '"' + (f.type === tp ? ' selected' : '') + '>' + typeLabels[tp] + '</option>';
            }).join('');
            var sources = '<option value="">-</option>' + Object.keys(lookupSources).map(function (k) {
                return '<option value="' + esc(k) + '"' + (f.source === k ? ' selected' : '') + '>' + esc(lookupSources[k]) + '</option>';
            }).join('');
            return '<tr>'
                + '<td><input type="text" class="form-control form-control-sm fr-key" value="' + esc(f.key) + '" placeholder="e.g. violation"></td>'
                + '<td><input type="text" class="form-control form-control-sm fr-label" value="' + esc(f.label) + '"></td>'
                + '<td><input type="text" class="form-control form-control-sm fr-label-ar" dir="rtl" value="' + esc(f.label_ar) + '"></td>'
                + '<td><select class="form-control form-control-sm fr-type">' + types + '</select>'
                + '<select class="form-control form-control-sm fr-source mt-1"' + (f.type === 'select' ? '' : ' style="display:none"') + '>' + sources + '</select></td>'
                + '<td class="text-center"><input type="checkbox" class="fr-bi"' + (f.bilingual ? ' checked' : '') + '></td>'
                + '<td class="text-center"><input type="checkbox" class="fr-req"' + (f.required ? ' checked' : '') + '></td>'
                + '<td><button type="button" class="btn btn-sm btn-outline-danger fr-del"><i class="fa fa-xmark"></i></button></td>'
                + '</tr>';
        }

        function readFieldRows() {
            return $('#tplFieldRows tr').map(function () {
                var $r = $(this);
                var key = $.trim($r.find('.fr-key').val()).toLowerCase().replace(/[^a-z0-9_]/g, '_');
                if (!key) return null;
                var type = $r.find('.fr-type').val();
                return {
                    key: key,
                    label: $.trim($r.find('.fr-label').val()) || key,
                    label_ar: $.trim($r.find('.fr-label-ar').val()),
                    type: type,
                    source: type === 'select' ? $r.find('.fr-source').val() : '',
                    bilingual: type === 'select' || type === 'assets' || ((type === 'text' || type === 'textarea') && $r.find('.fr-bi').is(':checked')),
                    required: $r.find('.fr-req').is(':checked')
                };
            }).get();
        }

        function showTplList() {
            $('#memoTplEditView, #memoTplEditButtons').hide();
            $('#memoTplListView, #memoTplClose').show();
            $.post(HANDLER, { action: 'list_templates', include_inactive: 1 }, null, 'json').done(function (res) {
                allTemplates = res.data || [];
                employeePlaceholders = res.placeholders || {};
                lookupSources = res.sources || {};
                $('#memoTplRows').html(allTemplates.map(function (tpl) {
                    return '<tr class="' + (tpl.is_active ? '' : 'memo-tpl-inactive') + '">'
                        + '<td><i class="fa-solid ' + esc(tpl.icon) + ' mr-1"></i>' + esc(tpl.label) + (tpl.label_ar ? '<br><small class="text-muted">' + esc(tpl.label_ar) + '</small>' : '') + (tpl.is_builtin ? '' : ' <span class="badge badge-info">' + esc(t('custom', 'Custom')) + '</span>') + '</td>'
                        + '<td class="small">' + esc(tpl.subject) + '</td>'
                        + '<td><div class="custom-control custom-switch"><input type="checkbox" class="custom-control-input memo-tpl-toggle" id="tplAct_' + esc(tpl.key) + '" data-key="' + esc(tpl.key) + '"' + (tpl.is_active ? ' checked' : '') + '><label class="custom-control-label" for="tplAct_' + esc(tpl.key) + '"></label></div></td>'
                        + '<td class="small text-muted">' + esc(tpl.updated_at ? tpl.updated_at + (tpl.updated_by ? ' - ' + tpl.updated_by : '') : '-') + '</td>'
                        + '<td class="text-right text-nowrap"><div class="btn-group" role="group">'
                        + '<button type="button" class="btn btn-sm btn-outline-primary memo-tpl-edit" data-key="' + esc(tpl.key) + '"><i class="fa fa-pen"></i></button>'
                        + (tpl.is_builtin
                            ? '<button type="button" class="btn btn-sm btn-outline-secondary memo-tpl-reset" data-key="' + esc(tpl.key) + '" title="' + esc(t('reset_to_default', 'Reset to default')) + '"><i class="fa fa-rotate-left"></i></button>'
                            : '<button type="button" class="btn btn-sm btn-outline-danger memo-tpl-delete" data-key="' + esc(tpl.key) + '" title="' + esc(t('delete', 'Delete')) + '"><i class="fa fa-trash"></i></button>')
                        + '</div></td></tr>';
                }).join(''));
            });
        }

        function showTplEdit(tpl) {
            if (!tplEditorReady) {
                $('#tplBody').summernote(editorOptions(320));
                $('#tplBodyAr').summernote(editorOptions(320));
                rtlEditor($('#tplBodyAr'));
                tplEditorReady = true;
            }
            $('#tplKey').val(tpl ? tpl.key : '');
            $('#tplLabel').val(tpl ? tpl.label : '');
            $('#tplLabelAr').val(tpl ? tpl.label_ar : '');
            $('#tplIcon').val(tpl ? tpl.icon : 'fa-envelope').trigger('input');
            $('#tplSubject').val(tpl ? tpl.subject : '');
            $('#tplSubjectAr').val(tpl ? tpl.subject_ar : '');
            $('#tplBody').summernote('code', tpl ? tpl.body : '<p>Dear {{employee_name}},</p><p>{{f:details}}</p><p>Regards,<br><strong>{{sender_name}}</strong><br>Human Resources Department<br>{{company}}</p>');
            $('#tplBodyAr').summernote('code', tpl ? (tpl.body_ar || '') : '<p>السيد/ {{employee_name}} المحترم،</p><p>{{f:details}}</p><p>وتفضلوا بقبول فائق الاحترام،<br><strong>{{sender_name}}</strong><br>إدارة الموارد البشرية<br>{{company}}</p>');
            var fields = tpl ? (tpl.fields || []) : [{ key: 'details', label: 'Details', label_ar: 'التفاصيل', type: 'textarea', bilingual: true, required: true }];
            $('#tplFieldRows').html(fields.map(fieldRow).join(''));
            renderPlaceholderChips();
            $('#memoTplListView, #memoTplClose').hide();
            $('#memoTplEditView, #memoTplEditButtons').show();
        }

        $('#memoManageTpl').on('click', function () {
            showTplList();
            $tplModal.modal('show');
        });
        $tplModal.on('hidden.bs.modal', loadTemplates);

        $('#memoTplAdd').on('click', function () { showTplEdit(null); });
        $('#memoTplBack').on('click', showTplList);
        $('#tplFieldAdd').on('click', function () {
            $('#tplFieldRows').append(fieldRow(null));
            renderPlaceholderChips();
        });
        $('#tplFieldRows')
            .on('click', '.fr-del', function () { $(this).closest('tr').remove(); renderPlaceholderChips(); })
            .on('change', '.fr-key, .fr-label', renderPlaceholderChips)
            .on('change', '.fr-type', function () {
                $(this).closest('td').find('.fr-source').toggle($(this).val() === 'select');
            });

        $('#memoTplRows')
            .on('click', '.memo-tpl-edit', function () {
                var key = $(this).data('key');
                showTplEdit(allTemplates.filter(function (x) { return x.key === key; })[0]);
            })
            .on('change', '.memo-tpl-toggle', function () {
                var $cb = $(this);
                $.post(HANDLER, { action: 'toggle_template', template_key: $cb.data('key'), is_active: $cb.is(':checked') ? 1 : 0 }, null, 'json')
                    .done(function () { $cb.closest('tr').toggleClass('memo-tpl-inactive', !$cb.is(':checked')); })
                    .fail(function (xhr) { $cb.prop('checked', !$cb.is(':checked')); Swal.fire(t('error', 'Error'), failMsg(xhr, 'Could not update.'), 'error'); });
            })
            .on('click', '.memo-tpl-reset', function () {
                var key = $(this).data('key');
                Swal.fire({ title: t('reset_to_default', 'Reset to default') + '?', text: t('reset_template_confirm', 'The template goes back to its original wording and fields.'), icon: 'warning', showCancelButton: true })
                    .then(function (r) {
                        if (!r.isConfirmed) return;
                        $.post(HANDLER, { action: 'reset_template', template_key: key }, null, 'json').done(showTplList)
                            .fail(function (xhr) { Swal.fire(t('error', 'Error'), failMsg(xhr, 'Could not reset.'), 'error'); });
                    });
            })
            .on('click', '.memo-tpl-delete', function () {
                var key = $(this).data('key');
                Swal.fire({ title: t('delete', 'Delete') + '?', icon: 'warning', showCancelButton: true })
                    .then(function (r) {
                        if (!r.isConfirmed) return;
                        $.post(HANDLER, { action: 'delete_template', template_key: key }, null, 'json').done(showTplList)
                            .fail(function (xhr) { Swal.fire(t('error', 'Error'), failMsg(xhr, 'Could not delete.'), 'error'); });
                    });
            });

        $('#tplIcon').on('input', function () {
            $('#tplIconPreview').attr('class', 'fa-solid ' + ($.trim($(this).val()) || 'fa-envelope'));
        });

        // Insert a placeholder where the cursor last was: one of the subjects or bodies.
        var lastTplFocus = '#tplBody';
        $('#tplSubject, #tplSubjectAr').on('focus', function () { lastTplFocus = '#' + this.id; });
        $('#tplBody').on('summernote.focus', function () { lastTplFocus = '#tplBody'; });
        $('#tplBodyAr').on('summernote.focus', function () { lastTplFocus = '#tplBodyAr'; });
        $('#tplPlaceholders').on('mousedown', '.memo-ph-chip', function (e) {
            e.preventDefault(); // keep the caret where it is
            var token = '{{' + $(this).data('ph') + '}}';
            if (lastTplFocus === '#tplSubject' || lastTplFocus === '#tplSubjectAr') {
                var el = $(lastTplFocus)[0];
                var s = el.selectionStart == null ? el.value.length : el.selectionStart;
                var en = el.selectionEnd == null ? el.value.length : el.selectionEnd;
                el.value = el.value.slice(0, s) + token + el.value.slice(en);
                el.selectionStart = el.selectionEnd = s + token.length;
            } else {
                $(lastTplFocus).summernote('editor.insertText', token);
            }
        });

        $('#memoTplSave').on('click', function () {
            var data = {
                action: 'save_template',
                template_key: $('#tplKey').val(),
                label: $.trim($('#tplLabel').val()),
                label_ar: $.trim($('#tplLabelAr').val()),
                icon: $.trim($('#tplIcon').val()),
                subject: $.trim($('#tplSubject').val()),
                subject_ar: $.trim($('#tplSubjectAr').val()),
                body: $('#tplBody').summernote('code'),
                body_ar: $('#tplBodyAr').summernote('code'),
                fields_json: JSON.stringify(readFieldRows())
            };
            if (!data.label || !data.subject || !$('<div>').html(data.body).text().trim()) {
                Swal.fire(t('error', 'Error'), t('template_required', 'Name, English subject and English body are required.'), 'warning');
                return;
            }
            var noSource = readFieldRows().filter(function (f) { return f.type === 'select' && !f.source; });
            if (noSource.length) {
                Swal.fire(t('error', 'Error'), t('memo_select_source', 'Choose where the dropdown options come from for') + ': ' + noSource.map(function (f) { return f.label; }).join(', '), 'warning');
                return;
            }
            // Warn about {{f:x}} used in the text but not defined as a field.
            var defined = {};
            readFieldRows().forEach(function (f) { defined[f.key] = true; });
            var used = (data.subject + data.subject_ar + data.body + data.body_ar).match(/\{\{\s*f:([a-z0-9_]+)\s*\}\}/gi) || [];
            var unknown = used.map(function (u) { return u.replace(/[{}\s]|f:/gi, '').toLowerCase(); })
                .filter(function (k, i, a) { return !defined[k] && a.indexOf(k) === i; });
            var go = unknown.length
                ? Swal.fire({ title: t('memo_unknown_fields', 'Undefined fields'), html: esc(t('memo_unknown_fields_text', 'These are used in the text but have no field:')) + '<br><strong>' + unknown.map(esc).join(', ') + '</strong>', icon: 'warning', showCancelButton: true, confirmButtonText: t('save_anyway', 'Save anyway') })
                : Promise.resolve({ isConfirmed: true });
            go.then(function (r) {
                if (!r.isConfirmed) return;
                $.post(HANDLER, data, null, 'json')
                    .done(function (res) {
                        Swal.fire({ icon: 'success', title: res.message, timer: 1200, showConfirmButton: false });
                        showTplList();
                    })
                    .fail(function (xhr) { Swal.fire(t('error', 'Error'), failMsg(xhr, 'Could not save template.'), 'error'); });
            });
        });

        // ---------- start ----------
        loadTemplates().then(function () {
            if (window.MEMO_OPEN_DRAFT_ID) {
                window.EmployeeMemos.loadDraft(window.MEMO_OPEN_DRAFT_ID);
            }
        });
    });
})(jQuery);
