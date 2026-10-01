<?php
// System users. Same look as the Smart Request pages (assets/css/smart_request.css).
// Data: includes/ajaxFile/getAllUsersData.php (server side, column indexes 5/7/8/9/10/11 are its filters).
// Add / edit forms: .createUserDeptAjax / .updateUserAjax in assets/js/jquery.app.js; status: update_user.php.
require_once __DIR__ . '/includes/session_check.php';
$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='" . $username . "'");
if (mysqli_num_rows($query) == 1) {
    include("./includes/avatar_select.php");
?>
    <!doctype html>
    <html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

    <head>
        <meta charset="utf-8" />
        <title><?= $site_title ?> - <?= __('all_users', 'All Users') ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta content="Anees Afzal" name="author" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />

        <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">
        <link href="./plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
        <link href="./plugins/datatables/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" />
        <link href="./plugins/datatables/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />
        <link href="./plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/smart_request.css?v=<?= @filemtime(__DIR__ . '/assets/css/smart_request.css') ?>" rel="stylesheet" type="text/css" />
        <script src="assets/js/modernizr.min.js"></script>
        <style type="text/css">
            /* Edit / add user popups (jquery.app.js) */
            .swal-wide { width: 850px !important; }
            .swal-landscape { width: 1200px !important; max-width: 95% !important; }
            .swal-landscape .swal2-html-container { max-height: 70vh !important; overflow-y: auto !important; }
            .employee-badge { display: inline-block; padding: 3px 9px; margin: 2px; border-radius: 8px; font-size: 12px; font-weight: 600; background: #eef2ff; color: #4338ca; white-space: nowrap; }
            .all-employees-badge { display: inline-block; padding: 3px 9px; border-radius: 8px; font-size: 12px; font-weight: 600; background: #f1f5f9; color: #475569; }
            .employee-badges-container { display: flex; flex-wrap: wrap; gap: 4px; }
            .allowed-employees-card { background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 12px; padding: 14px; margin-bottom: 14px; }
            .allowed-employees-card-title { font-weight: 700; color: #64748b; margin-bottom: 10px; font-size: 12px; text-transform: uppercase; letter-spacing: .4px; }
            .allowed-employees-card-content { display: flex; flex-wrap: wrap; gap: 6px; }

            .sr-page .sr-tiles.usr-tiles { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .usr-filters { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
            .usr-filters .usr-f { width: 190px; }
            .usr-more { display: none; padding: 12px 18px; border-bottom: 1px solid var(--sr-border); background: var(--sr-surface-2); }
            .usr-more.show { display: flex; }
            .usr-filters .select2-container--default .select2-selection--single {
                height: 36px; border-radius: 9px; border-color: var(--sr-border-strong); background: var(--sr-surface);
            }
            .usr-filters .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 34px; color: var(--sr-text); font-size: 12.5px; padding-inline: 10px 40px; }
            .usr-filters .select2-container--default .select2-selection--single .select2-selection__arrow { height: 34px; }
            .sr-page .usr-access { display: flex; flex-wrap: wrap; gap: 4px; max-width: 260px; white-space: normal; }
            .sr-page .usr-access .employee-badge, .sr-page .usr-access .all-employees-badge { margin: 0; padding: 2px 8px; font-size: 11px; background: var(--sr-accent-soft); color: var(--sr-accent-strong); }
            .sr-page .usr-access .all-employees-badge { background: var(--sr-surface-3); color: var(--sr-muted); }
            .sr-page .usr-access .usr-more-chip { padding: 2px 8px; font-size: 11px; border-radius: 8px; font-weight: 700; background: var(--sr-surface-3); color: var(--sr-text-2); cursor: help; }
            .usr-access-row { display: flex; gap: 6px; align-items: flex-start; margin: 2px 0; }
            .usr-access-row > i { color: var(--sr-muted); font-size: 14px; margin-top: 2px; }
            /* Status switch */
            .usr-switch { position: relative; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; margin: 0; }
            .usr-switch input { position: absolute; opacity: 0; pointer-events: none; }
            .usr-switch .track { width: 36px; height: 20px; border-radius: 999px; background: var(--sr-border-strong); position: relative; transition: background .15s; }
            .usr-switch .track::after { content: ''; position: absolute; top: 2px; inset-inline-start: 2px; width: 16px; height: 16px; border-radius: 50%; background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,.2); transition: inset-inline-start .15s; }
            .usr-switch input:checked + .track { background: #22c55e; }
            .usr-switch input:checked + .track::after { inset-inline-start: 18px; }
            .usr-switch input:disabled + .track { opacity: .5; }
            .usr-switch .lbl { font-size: 12px; font-weight: 600; color: var(--sr-text-2); }
        </style>
        <?php if ($is_rtl): ?>
            <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
        <?php endif; ?>
        <script> window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;</script>
    </head>

    <body class="enlarged" data-keep-enlarged="true">
        <div id="wrapper">
            <div class="left side-menu">
                <div class="slimscroll-menu" id="remove-scroll">
                    <div class="topbar-left">
                        <a href="dashboard.php" class="logo">
                            <span><img src="<?=get_setting($conDB, 'logo')?>" alt="" height="22"></span>
                            <i><img src="<?=get_setting($conDB, 'white_logo')?>" alt="" height="28"></i>
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
                                <h1><?= __('all_registered_users', 'System users') ?></h1>
                                <p><?= __('users_subtitle', 'Who can log in, their role and which companies, departments and employees they can see.') ?></p>
                            </div>
                            <div class="sr-head-actions">
                                <button type="button" class="sr-btn sr-btn-primary createUserDeptAjax"><i class="fa fa-plus"></i> <?= __('add_user', 'Add user') ?></button>
                            </div>
                        </div>
                        <div id="response"></div>

                        <div class="sr-tiles usr-tiles" id="usrTiles">
                            <button type="button" class="sr-tile active" data-status="">
                                <span class="sr-tile-label"><span class="sr-dot dot-all"></span><?= __('all', 'All') ?></span>
                                <span class="sr-tile-value" data-count="all">&ndash;</span>
                            </button>
                            <button type="button" class="sr-tile" data-status="Active">
                                <span class="sr-tile-label"><span class="sr-dot dot-green"></span><?= __('active', 'Active') ?></span>
                                <span class="sr-tile-value" data-count="active">&ndash;</span>
                            </button>
                            <button type="button" class="sr-tile" data-status="Inactive">
                                <span class="sr-tile-label"><span class="sr-dot dot-slate"></span><?= __('inactive', 'Inactive') ?></span>
                                <span class="sr-tile-value" data-count="inactive">&ndash;</span>
                            </button>
                        </div>

                        <div class="sr-card">
                            <div class="sr-toolbar">
                                <div class="sr-search">
                                    <i class="mdi mdi-magnify"></i>
                                    <input type="search" id="usrSearch" placeholder="<?= __('search') ?>..." autocomplete="off" aria-label="<?= __('search') ?>">
                                </div>
                                <div class="sr-toolbar-right usr-filters">
                                    <div class="usr-f user_role"></div>
                                    <div class="usr-f user_department"></div>
                                    <button type="button" class="sr-btn sr-btn-sm" id="usrMoreBtn"><i class="mdi mdi-filter-variant"></i> <?= __('more_filters', 'More filters') ?></button>
                                    <div id="usrButtons"></div>
                                </div>
                            </div>
                            <div class="usr-more usr-filters" id="usrMore">
                                <div class="usr-f user_company"></div>
                                <div class="usr-f user_allowed_dept"></div>
                                <div class="usr-f user_allowed_emp"></div>
                            </div>

                            <div class="sr-table-wrap">
                                <table id="employee_vac" class="table sr-table dt-responsive nowrap" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>-</th>
                                            <th><?= __('id_iqama', 'ID/Iqama') ?></th>
                                            <th>Emp</th>
                                            <th><?= __('user', 'User') ?></th>
                                            <th><?= __('department', 'Department') ?></th>
                                            <th><?= __('mobile', 'Mobile') ?></th>
                                            <th><?= __('user_type', 'User type') ?></th>
                                            <th><?= __('access_scope', 'Access scope') ?></th>
                                            <th>depts</th>
                                            <th>emps</th>
                                            <th><?= __('status', 'Status') ?></th>
                                            <th class="text-right"><?= __('action', 'Action') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>
                <footer class="footer">
                    <?= $site_footer ?>
                </footer>
            </div>
        </div>

        <script src="assets/js/jquery.min.js"></script>
        <script src="assets/js/bootstrap.bundle.min.js"></script>
        <script src="assets/js/metisMenu.min.js"></script>
        <script src="assets/js/waves.js"></script>
        <script src="assets/js/jquery.slimscroll.js"></script>
        <script type="text/javascript" src="./plugins/parsleyjs/parsley.min.js"></script>
        <script src="./plugins/datatables/jquery.dataTables.min.js"></script>
        <script src="./plugins/datatables/dataTables.bootstrap4.min.js"></script>
        <script src="./plugins/datatables/dataTables.buttons.min.js"></script>
        <script src="./plugins/datatables/buttons.bootstrap4.min.js"></script>
        <script src="./plugins/datatables/jszip.min.js"></script>
        <script src="./plugins/datatables/pdfmake.min.js"></script>
        <script src="./plugins/datatables/vfs_fonts.js"></script>
        <script src="./plugins/datatables/buttons.html5.min.js"></script>
        <script src="./plugins/datatables/buttons.print.min.js"></script>
        <script src="./plugins/datatables/dataTables.responsive.min.js"></script>
        <script src="./plugins/datatables/responsive.bootstrap4.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.0/jquery.validate.js"></script>
        <script src="./plugins/select2/js/select2.min.js" type="text/javascript"></script>
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>
        <script type="text/javascript">
            var userTable; // used by the edit popup callbacks to refresh the list
            $(document).ready(function() {
                const esc = s => $('<div>').text(s == null ? '' : String(s)).html();
                const IS_ADMIN = <?= json_encode($user_type == $access1) ?>;
                const ROLES = {
                    administrator: 'Administrator', hr: 'Human Resource', hr_senior_bp: 'HR Senior BP', dept_user: 'Department Manager',
                    assistant: 'Assistant Manager', employee: 'Employee', general_manager: 'General Manager', archiving: 'Archiving', gm: 'General Manager'
                };
                const ROLE_TONE = { administrator: 'red', hr: 'sky', hr_senior_bp: 'sky', dept_user: 'amber', assistant: 'green', general_manager: 'indigo', gm: 'indigo', employee: 'slate', archiving: 'slate' };
                const roleLabel = r => ROLES[r] || (r ? r.charAt(0).toUpperCase() + r.slice(1).replace(/_/g, ' ') : '-');

                // Server sends badge HTML; show the first two and "+N" with the rest in a tooltip.
                function compact(html) {
                    const $h = $('<div>').html(html || '');
                    const $b = $h.find('.employee-badge');
                    if (!$b.length) return $h.find('.all-employees-badge').length ? $h.find('.all-employees-badge')[0].outerHTML : '';
                    const names = $b.map(function() { return $(this).text(); }).get();
                    let out = names.slice(0, 2).map(n => `<span class="employee-badge">${esc(n)}</span>`).join('');
                    if (names.length > 2) out += `<span class="usr-more-chip" title="${esc(names.slice(2).join(', '))}">+${names.length - 2}</span>`;
                    return out;
                }

                const exportCols = [2, 3, 4, 5, 6, 7, 8, 9, 10, 11];
                const exportOptions = {
                    columns: exportCols,
                    format: { body: (data, row, col, node) => $('<div>').html(data).text().replace(/\s+/g, ' ').trim() }
                };
                const table = $('#employee_vac').DataTable({
                    dom: 'Brtip',
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    pageLength: 15,
                    ajax: {
                        url: './includes/ajaxFile/getAllUsersData.php',
                        type: 'POST',
                        dataType: 'json',
                        dataSrc: function(json) {
                            const c = json.counts || {};
                            $('#usrTiles [data-count]').each(function() { $(this).text(c[$(this).data('count')] ?? 0); });
                            return json.data || [];
                        }
                    },
                    buttons: [
                        { extend: 'excel', text: '<i class="mdi mdi-file-excel"></i> Excel', exportOptions: exportOptions, title: 'Users' },
                        { extend: 'print', text: '<i class="mdi mdi-printer"></i> ' + __('print'), exportOptions: exportOptions, title: 'Users' }
                    ],
                    order: [[0, 'desc']],
                    columnDefs: [
                        { targets: [0, 1, 2, 3, 6, 9, 10], visible: false },
                        { targets: '_all', orderable: false },
                        { targets: 4, render: function(data, type, row) {
                            const name = $('<div>').html(data).text();
                            if (type !== 'display') return name;
                            const ini = name.trim().split(/\s+/).slice(0, 2).map(w => w[0] || '').join('').toUpperCase() || '?';
                            const sub = [row[3], $('<div>').html(row[2]).text(), $('<div>').html(row[6]).text()].filter(Boolean).map(esc).join(' &middot; ');
                            return `<div class="sr-person"><span class="sr-avatar">${esc(ini)}</span><div style="min-width:0;"><span class="sr-cell-title">${esc(name || '-')}</span><span class="sr-cell-sub sr-mono">${sub}</span></div></div>`;
                        } },
                        { targets: 5, render: d => d ? `<span class="sr-chip">${d}</span>` : '<span class="text-muted">&ndash;</span>' },
                        { targets: 7, render: (d, type) => type === 'display' ? `<span class="sr-pill sr-pill-xs tone-${ROLE_TONE[d] || 'slate'}">${esc(roleLabel(d))}</span>` : roleLabel(d) },
                        { targets: 8, render: function(d, type, row) {
                            if (type !== 'display') return d;
                            return `<div class="usr-access-row"><i class="mdi mdi-domain" title="${esc(__('allowed_companies', 'Companies'))}"></i><div class="usr-access">${compact(row[8])}</div></div>
                                <div class="usr-access-row"><i class="mdi mdi-sitemap" title="${esc(__('allowed_departments', 'Departments'))}"></i><div class="usr-access">${compact(row[9])}</div></div>
                                <div class="usr-access-row"><i class="mdi mdi-account-multiple" title="${esc(__('allowed_employees', 'Employees'))}"></i><div class="usr-access">${compact(row[10])}</div></div>`;
                        } },
                        { targets: 11, render: function(d, type, row) {
                            const on = String(d) === '1';
                            if (type !== 'display') return on ? 'Active' : 'Inactive';
                            return `<label class="usr-switch"><input type="checkbox" class="user-status-checkbox" value="${esc(row[0])}" ${on ? 'checked' : ''}><span class="track"></span><span class="lbl">${esc(on ? __('active', 'Active') : __('inactive', 'Inactive'))}</span></label>`;
                        } },
                        { targets: 12, className: 'text-right', render: function(data, type, row) {
                            const r = row[13] || {};
                            const a = s => esc(s || '');
                            let html = `<div class="sr-actions"><a href="javascript:void(0);" class="sr-open-btn editUserAttr updateUserAjax"
                                data-id="${a(r.lid)}" data-fullname="${a(r.efullname)}" data-dept="${a(r.deptnme)}" data-email="${a(r.email)}"
                                data-user_type="${a(r.user_type)}" data-status="${a(r.status || 0)}"
                                data-allowed_companies="${a(r.allowed_companies)}" data-allowed_departments="${a(r.allowed_departments)}" data-allowed_employees="${a(r.allowed_employees)}">
                                <i class="mdi mdi-pencil"></i> ${esc(__('edit', 'Edit'))}</a>`;
                            if (IS_ADMIN) {
                                html += `<a href="javascript:void(0);" class="sr-more-btn deleteAjax" title="${esc(__('delete', 'Delete'))}" data-id="${a(row[0])}" data-tbl="admin_login" data-file="0"><i class="mdi mdi-delete text-danger"></i></a>`;
                            }
                            return html + '</div>';
                        } }
                    ],
                    language: {
                        info: `${__('showing')} _START_ ${__('to')} _END_ ${__('of')} _TOTAL_ ${__('entries')}`,
                        infoEmpty: `${__('showing')} 0 ${__('to')} 0 ${__('of')} 0 ${__('entries')}`,
                        infoFiltered: '',
                        paginate: { first: __('first'), last: __('last'), next: '<i class="mdi mdi-chevron-right"></i>', previous: '<i class="mdi mdi-chevron-left"></i>' },
                        emptyTable: `<div class="sr-empty"><i class="mdi mdi-account-off"></i>${__('no_data_available_in_table')}</div>`,
                        zeroRecords: `<div class="sr-empty"><i class="mdi mdi-magnify"></i>${__('no_matching_records_found')}</div>`,
                        processing: __('processing', 'Processing...')
                    },
                    initComplete: function() {
                        const api = this.api();
                        const mk = (holder, col, placeholder, regex) => {
                            const $s = $('<select style="width:100%;"><option value=""></option></select>').appendTo(holder)
                                .on('change', function() { api.column(col).search($(this).val() || '', !!regex, false).draw(); });
                            return { $s, ph: placeholder };
                        };
                        const role = mk('.user_role', 7, __('user_type', 'User type'), true);
                        Object.keys(ROLES).filter(k => k !== 'gm').forEach(k => role.$s.append(`<option value="${k}">${esc(ROLES[k])}</option>`));
                        role.$s.select2({ placeholder: role.ph, allowClear: true, width: '100%', minimumResultsForSearch: Infinity });

                        const dept = mk('.user_department', 5, __('department', 'Department'), true);
                        const comp = mk('.user_company', 8, __('allowed_companies', 'Allowed company'));
                        const adept = mk('.user_allowed_dept', 9, __('allowed_departments', 'Allowed department'));
                        const aemp = mk('.user_allowed_emp', 10, __('allowed_employees', 'Allowed employee'));

                        // Options for the other filters come from one full fetch
                        $.post('./includes/ajaxFile/getAllUsersData.php', { draw: 1, start: 0, length: 5000, search: { value: '' } }, null, 'json').done(function(res) {
                            const sets = { 5: new Set(), 8: new Set(), 9: new Set(), 10: new Set() };
                            (res.data || []).forEach(function(r) {
                                const d = $('<div>').html(r[5] || '').text().trim();
                                if (d) sets[5].add(d);
                                [8, 9, 10].forEach(i => $('<div>').html(r[i] || '').find('.employee-badge').each(function() { sets[i].add($(this).text().trim()); }));
                            });
                            [[dept, 5], [comp, 8], [adept, 9], [aemp, 10]].forEach(function(p) {
                                Array.from(sets[p[1]]).sort().forEach(v => p[0].$s.append($('<option>').val(v).text(v)));
                                p[0].$s.select2({ placeholder: p[0].ph, allowClear: true, width: '100%' });
                            });
                        });
                    }
                });
                table.buttons().container().appendTo('#usrButtons');
                userTable = table;

                let timer;
                $('#usrSearch').on('input', function() {
                    clearTimeout(timer);
                    const v = this.value;
                    timer = setTimeout(() => table.search(v).draw(), 350);
                });
                $('#usrTiles').on('click', '.sr-tile', function() {
                    $('#usrTiles .sr-tile').removeClass('active');
                    $(this).addClass('active');
                    table.column(11).search($(this).data('status') || '').draw();
                });
                $('#usrMoreBtn').on('click', function() { $('#usrMore').toggleClass('show'); });

                // Status switch
                $(document).on('change', 'input.user-status-checkbox', function() {
                    const $cb = $(this), on = $cb.is(':checked');
                    const failed = function(msg) {
                        $cb.prop('checked', !on);
                        Swal.fire({ title: __('error', 'Error'), text: msg || __('request_failed', 'Request failed'), icon: 'error', allowOutsideClick: false });
                    };
                    $cb.prop('disabled', true);
                    $.post('update_user.php', { id: $cb.val(), status: on ? '1' : '0' }, null, 'json')
                        .done(function(res) {
                            if (res && res.type === 'success') { table.draw(false); return; }
                            failed(res && res.message);
                        })
                        .fail(xhr => failed(xhr.responseJSON && xhr.responseJSON.message))
                        .always(() => $cb.prop('disabled', false));
                });
            });
        </script>
    </body>
    </html>
<?php } ?>
