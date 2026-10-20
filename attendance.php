<?php
// Attendance records. Same look as the Smart Request pages (assets/css/smart_request.css).
// Data + save: includes/ajaxFile/attendanceAjax.php (list_attendance, add_edit_attendance); delete: .deleteAjax in jquery.app.js.
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/special_access_helper.php';
// Attendance switched off in App Settings > Integrations
if (!attendance_enabled($conDB)) {
    http_response_code(403);
    die('Attendance is turned off (App Settings > Integrations)');
}
include(__DIR__ . '/includes/avatar_select.php');

$isSystemAdmin = $is_system_admin ?? false;
$canManageAttendance = $isSystemAdmin || user_has_special_access($conDB, $empid ?? '', 'manage_attendance', $user_role ?? '', $user_type ?? '', $isSystemAdmin);
if (!$canManageAttendance) {
    header('Location: dashboard.php');
    exit;
}
// "Add Record" (manual entry) is its own Special Access key; editing existing rows stays under manage_attendance.
$canAddAttendance = $isSystemAdmin || user_has_special_access($conDB, $empid ?? '', 'add_manual_attendance', $user_role ?? '', $user_type ?? '', $isSystemAdmin);

require_once __DIR__ . '/includes/attendance_ui.php'; // shared status tiles + date filter
?>

<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?> - <?= __('attendance_record', 'Attendance Record') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="Al-Mutlak" name="author" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

    <link rel="shortcut icon" href="<?= get_setting($conDB, 'favicon') ?>">

    <link href="./plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
    <link href="./plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="./plugins/datatables/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/smart_request.css?v=<?= @filemtime(__DIR__ . '/assets/css/smart_request.css') ?>" rel="stylesheet" type="text/css" />
    <script src="assets/js/modernizr.min.js"></script>
    <?php if ($is_rtl ?? false): ?>
        <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
    <?php endif; ?>
    <style>
        .att-filters { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
        .att-filters .att-emp { width: 300px; max-width: 100%; }
        .att-filters .select2-container--default .select2-selection--single {
            height: 38px; border-radius: 10px; border-color: var(--sr-border-strong); background: var(--sr-surface-2);
        }
        .att-filters .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 36px; color: var(--sr-text); padding-inline: 12px 40px; font-size: 13px; }
        .att-filters .select2-container--default .select2-selection--single .select2-selection__arrow { height: 36px; }
        .att-time { display: inline-flex; align-items: center; gap: 5px; font-variant-numeric: tabular-nums; font-weight: 600; color: var(--sr-text); }
        .att-time i { font-size: 14px; }
        .att-time.in i { color: #16a34a; }
        .att-time.out i { color: #dc2626; }
        .att-hours { font-variant-numeric: tabular-nums; font-weight: 600; }
        .att-note { max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: inline-block; vertical-align: middle; }
        .sr-form .att-punch .sr-seg-opt.checked span { border-color: var(--sr-accent); background: var(--sr-accent-soft); color: var(--sr-accent-strong); box-shadow: 0 0 0 1px var(--sr-accent) inset; }
    </style>
    <script>
        window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;
    </script>
</head>

<body class="enlarged" data-keep-enlarged="true">
    <div id="wrapper">
        <div class="left side-menu">
            <div class="slimscroll-menu" id="remove-scroll">
                <div class="topbar-left">
                    <a href="dashboard.php" class="logo">
                        <span><img src="<?= get_setting($conDB, 'logo') ?>" alt="" height="22"></span>
                        <i><img src="<?= get_setting($conDB, 'white_logo') ?>" alt="" height="28"></i>
                    </a>
                </div>
                <?php include("./includes/main_menu.php"); ?>
                <div class="clearfix"></div>
            </div>
        </div>

        <div class="content-page">
            <?php include("./includes/topbar.php"); ?>

            <div class="content sr-page">
                <div class="container-fluid">

                    <div class="sr-head">
                        <div>
                            <h1><?= __('attendance_record', 'Attendance Record') ?></h1>
                            <p><?= __('attendance_subtitle', 'Daily check-in / check-out from the devices and manual entries.') ?></p>
                        </div>
                        <?php if ($canAddAttendance): ?>
                        <div class="sr-head-actions">
                            <button id="btn-add-attendance" type="button" class="sr-btn sr-btn-primary"><i class="fa fa-plus"></i> <?= __('add_attendance', 'Add Record') ?></button>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?= attendance_tiles_html('attTiles', attendance_total_tile_html(true)) /* Total hours: shown only when one employee is selected */ ?>

                    <div class="sr-card">
                        <div class="sr-toolbar">
                            <div class="sr-search">
                                <i class="mdi mdi-magnify"></i>
                                <input type="search" id="attSearch" placeholder="<?= __('search') ?>..." autocomplete="off" aria-label="<?= __('search') ?>">
                            </div>
                            <div class="att-filters">
                                <div class="att-emp"><select id="filterEmployee" style="width: 100%;"></select></div>
                                <?= attendance_datefilter_html(['range' => 'attRange', 'from' => 'filterFromDate', 'to' => 'filterToDate']) ?>
                            </div>
                        </div>

                        <div class="sr-table-wrap">
                            <table id="attendance_table" class="table sr-table dt-responsive nowrap" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th><?= __('employee', 'Employee') ?></th>
                                        <th><?= __('date') ?></th>
                                        <th><?= __('check_in', 'Check In') ?></th>
                                        <th><?= __('check_out', 'Check Out') ?></th>
                                        <th><?= __('hours', 'Hours') ?></th>
                                        <th><?= __('state', 'State') ?></th>
                                        <th><?= __('source', 'Source') ?></th>
                                        <th><?= __('note') ?></th>
                                        <th class="text-right"><?= __('action_header', 'Action') ?></th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>

            <footer class="footer">
                <?= $site_footer ?? '' ?>
            </footer>
        </div>
    </div>

    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/metisMenu.min.js"></script>
    <script src="assets/js/waves.js"></script>
    <script src="assets/js/jquery.slimscroll.js"></script>

    <script src="./plugins/datatables/jquery.dataTables.min.js"></script>
    <script src="./plugins/datatables/dataTables.bootstrap4.min.js"></script>
    <script src="./plugins/datatables/dataTables.responsive.min.js"></script>
    <script src="./plugins/datatables/responsive.bootstrap4.min.js"></script>
    <script src="./plugins/select2/js/select2.min.js"></script>

    <script src="assets/js/jquery.core.js"></script>
    <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>
    <script src="assets/js/sr_forms.js?v=<?= @filemtime(__DIR__ . '/assets/js/sr_forms.js') ?>"></script>
    <script src="assets/js/attendance_ui.js?v=<?= @filemtime(__DIR__ . '/assets/js/attendance_ui.js') ?>"></script>

    <script>
    $(document).ready(function() {
        const F = window.SRForm, t = F.t, esc = F.esc;
        const AJAX_URL = './includes/ajaxFile/attendanceAjax.php';
        const STATES = ['Present', 'Late', 'Early Leave', 'Late & Early Leave', 'Incomplete', 'Late & Incomplete', 'Half Day', 'Leave', 'Day Off', 'Absent'];
        let stateGroup = '';

        function stateTone(s) {
            s = String(s || '');
            if (s === 'Present') return 'green';
            if (s.indexOf('Incomplete') > -1 || s === 'Absent') return 'red';
            if (s.indexOf('Late') === 0) return 'amber';
            if (s.indexOf('Early Leave') > -1 || s === 'Half Day') return 'indigo';
            return 'slate';
        }
        function stateLabel(s) { return t(String(s || '').toLowerCase().replace(/ & /g, '_and_').replace(/ /g, '_'), s); }
        function minutes(hm) { const m = /^(\d{1,2}):(\d{2})/.exec(hm || ''); return m ? (+m[1]) * 60 + (+m[2]) : null; }

        // Employee search (shared by the filter and the form)
        function empAjax() {
            return {
                url: './includes/ajaxFile/hrHandler.php',
                type: 'POST',
                dataType: 'json',
                delay: 250,
                data: params => ({ ajaxType: 'emp_search_select2', search: params.term }),
                processResults: function(response) {
                    if (response.status === 200 && response.data) {
                        return { results: response.data.map(emp => ({ id: emp.emp_id, text: emp.emp_id + ' - ' + emp.name + (emp.department ? ' (' + emp.department + ')' : '') })) };
                    }
                    return { results: [] };
                }
            };
        }

        $('#filterEmployee').select2({
            placeholder: t('all_employees', 'All employees'),
            allowClear: true,
            width: '100%',
            ajax: empAjax()
        });

        const table = $('#attendance_table').DataTable({
            dom: 'rtip',
            processing: true,
            serverSide: true,
            responsive: true,
            pageLength: 25,
            order: [[1, 'desc']],
            ajax: {
                url: AJAX_URL,
                type: 'POST',
                data: function(d) {
                    d.action = 'list_attendance';
                    d.emp_id = $('#filterEmployee').val() || '';
                    d.from_date = $('#filterFromDate').val() || '';
                    d.to_date = $('#filterToDate').val() || '';
                    d.state_group = stateGroup;
                },
                dataSrc: function(json) {
                    const c = json.counts || {};
                    $('#attTiles [data-count]').each(function() {
                        const v = c[$(this).data('count')];
                        $(this).text(v == null ? 0 : Number(v).toLocaleString('en-US'));
                    });
                    // "Total hours" tile: only when one employee is selected (server sends total_hours then)
                    const th = json.total_hours;
                    const dur = m => Math.floor(m / 60).toLocaleString('en-US') + 'h ' + String(m % 60).padStart(2, '0') + 'm';
                    $('#attTiles').toggleClass('has-total', !!th).find('.att-total').toggle(!!th);
                    if (th) {
                        $('#attTotalHours').text(dur(th.minutes || 0));
                        $('#attTotalAvg').text(th.days ? `${th.days} ${t('days', 'days')} · ${t('avg', 'Avg')} ${dur(Math.round(th.minutes / th.days))}` : ' ');
                    }
                    return json.data || [];
                }
            },
            columns: [
                { data: 'name', orderable: false, render: function(name, type, row) {
                    return `<div class="sr-person">
                        <span class="sr-avatar">${esc(String(name || '?').trim().split(/\s+/).slice(0, 2).map(w => w[0]).join('').toUpperCase())}</span>
                        <div style="min-width:0;"><span class="sr-cell-title">${esc(name || '-')}</span><span class="sr-cell-sub sr-mono">${esc(row.emp_id)}</span></div>
                    </div>`;
                } },
                { data: 'date', render: function(d) {
                    const dt = new Date(d + 'T00:00:00');
                    if (isNaN(dt)) return esc(d);
                    return `<span class="sr-date">${dt.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })}<small>${dt.toLocaleDateString('en-US', { weekday: 'long' })}</small></span>`;
                } },
                { data: 'time_in', orderable: false, render: v => v ? `<span class="att-time in"><i class="mdi mdi-login"></i>${esc(v)}</span>` : '<span class="text-muted">&ndash;</span>' },
                { data: 'time_out', orderable: false, render: v => v ? `<span class="att-time out"><i class="mdi mdi-logout"></i>${esc(v)}</span>` : '<span class="text-muted">&ndash;</span>' },
                { data: null, orderable: false, render: function(d, type, row) {
                    const a = minutes(row.time_in), b = minutes(row.time_out);
                    if (a === null || b === null) return '<span class="text-muted">&ndash;</span>';
                    let m = b - a; if (m < 0) m += 1440;
                    return `<span class="att-hours">${Math.floor(m / 60)}h ${('0' + (m % 60)).slice(-2)}m</span>`;
                } },
                { data: 'state', orderable: false, render: s => `<span class="sr-pill sr-pill-xs tone-${stateTone(s)}"><span class="sr-dot"></span>${esc(stateLabel(s))}</span>` },
                { data: 'source', orderable: false, render: s => s === 'manual'
                    ? `<span class="sr-chip"><i class="mdi mdi-pencil"></i>${esc(t('manual', 'Manual'))}</span>`
                    : `<span class="sr-chip"><i class="mdi mdi-fingerprint"></i>${esc(t('device', 'Device'))}</span>` },
                { data: 'note', orderable: false, render: n => n ? `<span class="att-note" title="${esc(n)}">${esc(n)}</span>` : '<span class="text-muted">&ndash;</span>' },
                { data: 'action', orderable: false, searchable: false, className: 'text-right', render: function(html) {
                    // Server sends a Bootstrap dropdown; restyle its toggle like the other sr pages.
                    return String(html || '').replace('table-action-btn dropdown-toggle arrow-none btn btn-light btn-sm', 'sr-more-btn dropdown-toggle arrow-none')
                        .replace('mdi-dots-horizontal', 'mdi-dots-vertical');
                } }
            ],
            language: {
                info: `${__('showing')} _START_ ${__('to')} _END_ ${__('of')} _TOTAL_ ${__('entries')}`,
                infoEmpty: `${__('showing')} 0 ${__('to')} 0 ${__('of')} 0 ${__('entries')}`,
                infoFiltered: '',
                paginate: { first: __('first'), last: __('last'), next: '<i class="mdi mdi-chevron-right"></i>', previous: '<i class="mdi mdi-chevron-left"></i>' },
                emptyTable: `<div class="sr-empty"><i class="mdi mdi-calendar-blank"></i>${__('no_data_available_in_table')}</div>`,
                zeroRecords: `<div class="sr-empty"><i class="mdi mdi-magnify"></i>${__('no_matching_records_found')}</div>`,
                processing: t('processing', 'Processing...')
            }
        });

        const reload = () => table.ajax.reload();
        let searchTimer;
        $('#attSearch').on('input', function() {
            clearTimeout(searchTimer);
            const v = this.value;
            searchTimer = setTimeout(() => table.search(v).draw(), 350);
        });
        $('#filterEmployee').on('change', reload);
        // Status tiles + date filter: shared with the Employee Profile attendance tab (assets/js/attendance_ui.js)
        AttUI.bindTiles('#attTiles', function(g) { stateGroup = g; reload(); });
        AttUI.bindDateFilter({ range: '#attRange', from: '#filterFromDate', to: '#filterToDate', active: '', onChange: reload });

        /* ---------------- Add / edit form ---------------- */

        const hm = v => String(v || '').slice(0, 5); // 'HH:MM:SS' -> 'HH:MM' for the time pickers

        function formHtml(v) {
            const isEdit = !!v.empId;
            let punch = 'both';
            if (v.timeIn && !v.timeOut) punch = 'in';
            else if (!v.timeIn && v.timeOut) punch = 'out';
            const states = STATES.indexOf(v.state) > -1 || !v.state ? STATES : STATES.concat([v.state]);
            const seg = (val, icon, label) => `<label class="sr-seg-opt ${punch === val ? 'checked' : ''}"><input type="radio" name="punch" value="${val}" ${punch === val ? 'checked' : ''}><span><i class="mdi ${icon}"></i> ${esc(label)}</span></label>`;
            return '<form id="attForm" class="sr-page sr-form text-left" autocomplete="off" novalidate>' +
                F.section('mdi-account', t('employee', 'Employee'),
                    F.field({ col: 8, name: 'emp_id', label: t('employee', 'Employee'), req: !isEdit,
                        html: isEdit
                            ? `<input type="text" class="form-control" value="${esc(v.empLabel)}" readonly>`
                            : `<select id="attEmpId" name="emp_id" data-required="1" data-msg="${esc(t('select_employee', 'Select the employee'))}"></select>` }) +
                    F.field({ col: 4, name: 'date', id: 'attDate', label: t('date', 'Date'), req: !isEdit, value: v.date || F.today(), ph: 'YYYY-MM-DD',
                        attrs: isEdit ? ' readonly' : '', msg: t('select_date', 'Select the date') })
                ) +
                F.section('mdi-clock', t('punches', 'Punches'),
                    F.field({ col: 12, name: 'punch', label: t('punch_type', 'Punch type'),
                        html: '<div class="sr-seg att-punch">' +
                            seg('both', 'mdi-swap-horizontal', t('punch_both', 'Check in & out')) +
                            seg('in', 'mdi-login', t('punch_in_only', 'Check in only')) +
                            seg('out', 'mdi-logout', t('punch_out_only', 'Check out only')) +
                        '</div>' }) +
                    F.field({ col: 4, name: 'time_in', id: 'attTimeIn', label: t('check_in', 'Check in'), req: true, value: hm(v.timeIn), ph: 'HH:MM',
                        msg: t('enter_check_in', 'Enter the check-in time') }).replace('sr-fcol c-4', 'sr-fcol c-4 js-in') +
                    F.field({ col: 4, name: 'time_out', id: 'attTimeOut', label: t('check_out', 'Check out'), req: true, value: hm(v.timeOut), ph: 'HH:MM',
                        msg: t('enter_check_out', 'Enter the check-out time') }).replace('sr-fcol c-4', 'sr-fcol c-4 js-out') +
                    F.field({ col: 4, name: '_hours', label: t('hours', 'Hours'), html: '<div class="sr-fstats" style="grid-template-columns:1fr;"><div><span>' + esc(t('worked', 'Worked')) + '</span><b class="js-hours">&ndash;</b></div></div>' })
                ) +
                F.section('mdi-information-outline', t('details', 'Details'),
                    F.field({ col: 5, name: 'state', label: t('state', 'State'), req: true,
                        html: F.select({ name: 'state', id: 'attState', req: true, options: states.map(s => ({ v: s, l: stateLabel(s) })) }) }) +
                    F.field({ col: 7, name: 'note', id: 'attNote', label: t('note', 'Note'), value: v.note || '', ph: t('optional', 'Optional') })
                ) +
            '</form>';
        }

        function openForm(v) {
            const isEdit = !!v.empId;
            F.open({
                title: isEdit ? t('edit_attendance', 'Edit record') : t('add_attendance', 'Add record'),
                html: formHtml(v),
                width: '760px',
                confirm: t('save', 'Save'),
                didOpen: function() {
                    const $form = $('#attForm');
                    F.liveClear($form);
                    $('#attState').val(v.state || 'Present');
                    if (!isEdit) {
                        F.select2($('#attEmpId'), { placeholder: t('search_employee', 'Search by name or ID'), ajax: empAjax() });
                        const pre = $('#filterEmployee').select2('data')[0];
                        if (pre && pre.id) $('#attEmpId').append(new Option(pre.text, pre.id, true, true)).trigger('change');
                    }
                    function refresh() {
                        const p = $form.find('input[name="punch"]:checked').val();
                        $form.find('.js-in').toggle(p !== 'out').find('input').attr('data-required', p !== 'out' ? '1' : null);
                        $form.find('.js-out').toggle(p !== 'in').find('input').attr('data-required', p !== 'in' ? '1' : null);
                        const a = p === 'out' ? null : minutes($('#attTimeIn').val()), b = p === 'in' ? null : minutes($('#attTimeOut').val());
                        let txt = '–';
                        if (a !== null && b !== null) { let m = b - a; if (m < 0) m += 1440; txt = Math.floor(m / 60) + 'h ' + ('0' + (m % 60)).slice(-2) + 'm'; }
                        $form.find('.js-hours').text(txt);
                    }
                    // AppDate calendar for the date (no future days); clock-face pickers (same as Timetables) for the punches
                    if (window.AppDate && !isEdit) AppDate.single('#attDate', { maxDate: 'today' });
                    // Empty Check In / Check Out clocks open on the employee's scheduled times for that day
                    F.clockDefault($('#attTimeIn'), '08:00');
                    F.clockDefault($('#attTimeOut'), '17:00');
                    F.clock($('#attTimeIn, #attTimeOut'));
                    const loadScheduled = function() {
                        const emp = isEdit ? v.empId : $('#attEmpId').val();
                        const day = isEdit ? v.date : $('#attDate').val();
                        if (!emp || !day) return;
                        $.post(AJAX_URL, { action: 'timetable_for', emp_id: emp, date: day }, null, 'json').done(function(r) {
                            if (!r || r.status !== 'success') return;
                            F.clockDefault($('#attTimeIn'), r.check_in);
                            F.clockDefault($('#attTimeOut'), r.check_out);
                        });
                    };
                    $form.on('change', '#attEmpId, #attDate', loadScheduled);
                    loadScheduled();
                    $form.on('change', 'input[name="punch"]', refresh);
                    $form.on('input change', '#attTimeIn, #attTimeOut', refresh);
                    refresh();
                },
                preConfirm: function() {
                    const $form = $('#attForm');
                    const msg = F.validate($form);
                    if (msg) { Swal.showValidationMessage(msg); return false; }
                    const p = $form.find('input[name="punch"]:checked').val();
                    return $.ajax({
                        url: AJAX_URL, type: 'POST', dataType: 'json',
                        data: {
                            action: 'add_edit_attendance',
                            emp_id: isEdit ? v.empId : $('#attEmpId').val(),
                            date: isEdit ? v.date : $('#attDate').val(),
                            time_in: p === 'out' ? '' : $('#attTimeIn').val(),
                            time_out: p === 'in' ? '' : $('#attTimeOut').val(),
                            state: $('#attState').val(),
                            note: $('#attNote').val()
                        }
                    }).then(function(res) {
                        if (!res || res.status !== 'success') throw new Error((res && res.message) || 'Error');
                        return res;
                    }).catch(function(err) {
                        Swal.showValidationMessage((err && err.message) || t('unexpected_error', 'Unexpected error.'));
                    });
                }
            }).then(function(result) {
                if (!(result.isConfirmed && result.value)) return;
                Swal.fire({ title: t('saved', 'Saved'), text: result.value.message, icon: 'success', timer: 1400, showConfirmButton: false, allowOutsideClick: false });
                table.ajax.reload(null, false);
            });
        }

        $('#btn-add-attendance').on('click', function() { openForm({}); });

        $(document).on('click', '.btn-edit-attendance', function() {
            const d = $(this).data();
            openForm({
                empId: d.empId, empLabel: d.empLabel, date: d.date,
                timeIn: d.timeIn || '', timeOut: d.timeOut || '', state: d.state, note: d.note || ''
            });
        });
    });
    </script>
</body>
</html>
