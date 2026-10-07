<?php
/**
 * Employees > Document Expiry: ID/Iqama (= work permit for expats) and passport expiry
 * for active employees, with quick date update and a manual "Send alerts now".
 * Alerts themselves: includes/doc_expiry_helper.php (sent daily from zk_sync_import.php).
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/helper_functions.php';
require_once __DIR__ . '/includes/page_access_helper.php';
require_once __DIR__ . '/includes/special_access_helper.php';
require_once __DIR__ . '/includes/sr_list_helpers.php';
require_once __DIR__ . '/includes/doc_expiry_helper.php';

// Same rule as the sidebar (App Settings > Page Access), checked here too because the
// AJAX actions below run before main_menu.php's guard.
$doc_page_roles = get_page_access_map($conDB)['document_expiry.php'] ?? [];
$can_doc_expiry = !empty($is_system_admin)
    || in_array($user_role ?? '', $doc_page_roles, true)
    || in_array($user_type ?? '', $doc_page_roles, true)
    || user_has_special_access($conDB, $empid ?? '', 'access_document_expiry', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
$is_ajax = $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']);
if (!$can_doc_expiry) {
    if ($is_ajax) {
        http_response_code(403);
        die(json_encode(['type' => 'error', 'title' => __('error'), 'message' => __('access_denied', 'Access denied')]));
    }
    header("Location: ./profile.php");
    exit();
}

$scope_sql = getCompanyFilterSQL('e.comp_no', true) . getDepartmentFilterSQL('e.dept', true) . getEmployeeFilterSQL('e.emp_id', true);

if ($is_ajax) {
    header('Content-Type: application/json; charset=UTF-8');
    $reply = function ($type, $message, $extra = []) {
        die(json_encode(['type' => $type, 'title' => $type === 'success' ? __('updated', 'Updated') : __('error', 'Error'), 'message' => $message] + $extra));
    };

    if ($_POST['action'] === 'update_date') {
        $id = (int) ($_POST['id'] ?? 0);
        $doc_key = (string) ($_POST['doc_key'] ?? '');
        $date = doc_expiry_normalize_date($_POST['expiry'] ?? '');
        if ($id <= 0 || !in_array($doc_key, ['iqama', 'passport'], true) || $date === null) {
            $reply('error', __('fill_mandatory_fields', 'Please fill all mandatory fields.'));
        }
        // Only employees inside this user's company/department/employee scope.
        $chk = mysqli_query($conDB, "SELECT e.id FROM employees e WHERE e.id = " . $id . " " . $scope_sql);
        if (!$chk || !mysqli_num_rows($chk)) {
            $reply('error', __('access_denied', 'Access denied'));
        }
        if ($doc_key === 'iqama') {
            include_once __DIR__ . '/includes/Hijri_GregorianConvert.php';
            $hijri = (new Hijri_GregorianConvert)->GregorianToHijri($date, 'YYYY-MM-DD');
            $stmt = $conDB->prepare("UPDATE employees SET iqama_exp_g = ?, iqama_exp = ? WHERE id = ?");
            $stmt->bind_param('ssi', $date, $hijri, $id);
        } else {
            $stmt = $conDB->prepare("UPDATE employees SET passport_exp = ? WHERE id = ?");
            $stmt->bind_param('si', $date, $id);
        }
        $ok = $stmt->execute();
        $stmt->close();
        $ok ? $reply('success', __('this_record_has_been_updated_successfully', 'This record has been updated successfully.'))
            : $reply('error', __('record_not_updated_because_there_are_some_error', 'Record not updated.'));
    }

    if ($_POST['action'] === 'run_alerts') {
        $s = doc_expiry_run_daily($conDB);
        if (!$s['due'] && !$s['expired_due']) {
            $reply('success', __('doc_expiry_nothing_due', 'Nothing new to send today. Every alert due has already been sent.'), ['summary' => $s]);
        }
        if (!$s['emails_sent']) {
            $reply('error', __('doc_expiry_send_failed', 'No email could be sent. Check App Settings > Email.') . ($s['errors'] ? ' (' . $s['errors'][0] . ')' : ''), ['summary' => $s]);
        }
        $reply('success', sprintf(__('doc_expiry_sent_summary', '%d email(s) sent for %d expiring and %d expired document(s).'), $s['emails_sent'], $s['due'], $s['expired_due'])
            . ($s['emails_failed'] ? ' ' . sprintf(__('doc_expiry_failed_count', '%d email(s) failed.'), $s['emails_failed']) : ''), ['summary' => $s]);
    }

    $reply('error', 'Unknown action');
}

$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='" . mysqli_real_escape_string($conDB, $username) . "'");
if (mysqli_num_rows($query) != 1) {
    header("Location: ./profile.php");
    exit();
}
include("./includes/avatar_select.php");

$h = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES); };
$is_ar = ($current_lang ?? 'en') === 'ar';

// Everything up to 90 days out, plus anything already expired.
$rows = doc_expiry_collect($conDB, $scope_sql, 90);
$last_alerts = doc_expiry_last_alerts($conDB);

$count = ['all' => count($rows), 'expired' => 0, 'd30' => 0, 'd60' => 0, 'd90' => 0];
$stale = 0;
$depts = [];
foreach ($rows as &$r) {
    $d = $r['days_left'];
    $r['key'] = $d < 0 ? 'expired' : ($d <= 30 ? 'd30' : ($d <= 60 ? 'd60' : 'd90'));
    $count[$r['key']]++;
    if ($d < -90) {
        $stale++;
    }
    $dept_name = $is_ar && $r['dept_ar'] !== '' ? $r['dept_ar'] : $r['dept'];
    $r['dept_display'] = $dept_name;
    if ($dept_name !== '') {
        $depts[$dept_name] = true;
    }
}
unset($r);
ksort($depts);
usort($rows, function ($a, $b) { return $a['days_left'] <=> $b['days_left']; });

$tone = ['expired' => 'red', 'd30' => 'amber', 'd60' => 'sky', 'd90' => 'slate'];
$can_import_iqama = !empty($is_system_admin) || in_array($user_role ?? '', get_page_access_map($conDB)['import_iqama_exp.php'] ?? [], true);
?>
    <!doctype html>
    <html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>
    <head>
        <meta charset="utf-8" />
        <title><?= $site_title ?> - <?= __('document_expiry', 'Document Expiry') ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">

        <link href="./plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
        <link href="./plugins/datatables/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" />
        <link href="./plugins/datatables/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />

        <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/smart_request.css?v=<?= @filemtime(__DIR__ . '/assets/css/smart_request.css') ?>" rel="stylesheet" type="text/css" />
        <script src="assets/js/modernizr.min.js"></script>
        <?php if ($is_rtl): ?>
            <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
        <?php endif; ?>
        <script>
            window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;
        </script>

        <style type="text/css">
            .sr-page .sr-tiles.dx-tiles { grid-template-columns: repeat(5, minmax(0, 1fr)); }
            @media (max-width: 991px) { .sr-page .sr-tiles.dx-tiles { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
            @media (max-width: 575px) { .sr-page .sr-tiles.dx-tiles { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
            .dx-select {
                height: 34px; padding: 0 30px 0 12px; border-radius: 8px; font-size: 12px; font-weight: 600;
                border: 1px solid var(--sr-border-strong); background-color: var(--sr-surface); color: var(--sr-text-2);
            }
            .dx-notice-actions { margin-top: 8px; }
        </style>
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
                                <h1><?= __('document_expiry', 'Document Expiry') ?></h1>
                                <p><?= __('document_expiry_subtitle', 'ID / Iqama, work permit and passport expiry for active employees. Reminders are emailed at 60, 30, 14 and 7 days.') ?></p>
                            </div>
                            <div class="sr-head-actions">
                                <button type="button" class="sr-btn sr-btn-primary" id="dxRunAlerts"><i class="mdi mdi-email-outline"></i> <?= __('send_alerts_now', 'Send alerts now') ?></button>
                            </div>
                        </div>

                        <div class="sr-tiles dx-tiles" id="dxTiles">
                            <button type="button" class="sr-tile active" data-key="">
                                <span class="sr-tile-label"><span class="sr-dot dot-all"></span><?= __('all', 'All') ?></span>
                                <span class="sr-tile-value"><?= $count['all'] ?></span>
                            </button>
                            <button type="button" class="sr-tile" data-key="expired">
                                <span class="sr-tile-label"><span class="sr-dot dot-red"></span><?= __('expired', 'Expired') ?></span>
                                <span class="sr-tile-value"><?= $count['expired'] ?></span>
                            </button>
                            <button type="button" class="sr-tile" data-key="d30">
                                <span class="sr-tile-label"><span class="sr-dot dot-amber"></span><?= __('within_30_days', 'Within 30 days') ?></span>
                                <span class="sr-tile-value"><?= $count['d30'] ?></span>
                            </button>
                            <button type="button" class="sr-tile" data-key="d60">
                                <span class="sr-tile-label"><span class="sr-dot dot-sky"></span><?= __('days_31_60', '31 - 60 days') ?></span>
                                <span class="sr-tile-value"><?= $count['d60'] ?></span>
                            </button>
                            <button type="button" class="sr-tile" data-key="d90">
                                <span class="sr-tile-label"><span class="sr-dot dot-slate"></span><?= __('days_61_90', '61 - 90 days') ?></span>
                                <span class="sr-tile-value"><?= $count['d90'] ?></span>
                            </button>
                        </div>

                        <?php if ($stale > 0): ?>
                        <div class="sr-notice tone-amber">
                            <i class="mdi mdi-alert-outline"></i>
                            <div>
                                <strong><?= sprintf(__('doc_expiry_stale_title', '%d document(s) expired more than 90 days ago'), $stale) ?></strong><br>
                                <?= __('doc_expiry_stale_text', 'Most of these were probably renewed but the new date was never entered. Update them here, or import the latest Iqama expiry list, so HR only gets real alerts.') ?>
                                <?php if ($can_import_iqama): ?>
                                    <div class="dx-notice-actions"><a href="import_iqama_exp.php" class="sr-btn sr-btn-sm sr-btn-ghost"><i class="mdi mdi-file-import"></i> <?= __('import_iqama_exp', 'Import Iqama Expiry') ?></a></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="sr-card">
                            <div class="sr-toolbar">
                                <div class="sr-search">
                                    <i class="mdi mdi-magnify"></i>
                                    <input type="search" id="dxSearch" placeholder="<?= __('search_by_name_id', 'Search by name or ID') ?>..." autocomplete="off" aria-label="<?= __('search') ?>">
                                </div>
                                <div class="sr-toolbar-right">
                                    <select id="dxDoc" class="dx-select" aria-label="<?= __('document', 'Document') ?>">
                                        <option value=""><?= __('document', 'Document') ?>: <?= __('all', 'All') ?></option>
                                        <option value="iqama"><?= __('id_iqama_work_permit', 'ID / Iqama / Work Permit') ?></option>
                                        <option value="passport"><?= __('passport', 'Passport') ?></option>
                                    </select>
                                    <select id="dxDept" class="dx-select" aria-label="<?= __('department') ?>">
                                        <option value=""><?= __('department') ?>: <?= __('all', 'All') ?></option>
                                        <?php foreach (array_keys($depts) as $dn): ?>
                                            <option value="<?= $h($dn) ?>"><?= $h($dn) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div id="dxExportButtons"></div>
                                </div>
                            </div>

                            <div class="sr-table-wrap">
                                <table id="dx_table" class="table sr-table dt-responsive nowrap" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th><?= __('employee', 'Employee') ?></th>
                                            <th><?= __('department') ?></th>
                                            <th><?= __('document', 'Document') ?></th>
                                            <th><?= __('expiry_date', 'Expiry date') ?></th>
                                            <th><?= __('status') ?></th>
                                            <th><?= __('last_alert', 'Last alert') ?></th>
                                            <th class="text-right"><?= __('action') ?></th>
                                            <th>key</th>
                                            <th>doc</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($rows as $r):
                                            $d = $r['days_left'];
                                            $name = getDisplayName($r['name']);
                                            $doc_label = $is_ar ? $r['doc_label_ar'] : $r['doc_label'];
                                            if ($d < 0) {
                                                $status = sprintf(__('expired_days_ago', 'Expired %d days ago'), abs($d));
                                            } elseif ($d === 0) {
                                                $status = __('expires_today', 'Expires today');
                                            } else {
                                                $status = sprintf(__('days_left_n', '%d days left'), $d);
                                            }
                                            $alert = $last_alerts[$r['emp_id'] . '|' . $r['doc_key'] . '|' . $r['expiry']] ?? null;
                                            $alert_label = $alert ? (strpos($alert['bucket'], 'expired') === 0 ? __('expired', 'Expired') : sprintf(__('n_day_alert', '%s-day alert'), $alert['bucket'])) : '';
                                        ?>
                                            <tr>
                                                <td data-order="<?= $h($name) ?>" data-export="<?= $h($name . ' (' . $r['emp_id'] . ')') ?>">
                                                    <a href="view_employee.php?emp_id=<?= urlencode($r['emp_id']) ?>" target="_blank" style="color: inherit;"><?= sr_person_cell($name, $r['emp_id']) ?></a>
                                                </td>
                                                <td data-export="<?= $h($r['dept_display']) ?>"><?= $h($r['dept_display']) ?></td>
                                                <td data-export="<?= $h($doc_label . ' ' . $r['doc_no']) ?>">
                                                    <span class="sr-cell-title"><i class="mdi <?= $r['doc_key'] === 'passport' ? 'mdi-passport' : 'mdi-account-card-details' ?>"></i> <?= $h($doc_label) ?></span>
                                                    <span class="sr-cell-sub sr-mono"><?= $h($r['doc_no']) ?></span>
                                                </td>
                                                <td data-order="<?= $h($r['expiry']) ?>" data-export="<?= $h($r['expiry']) ?>">
                                                    <span class="sr-cell-title"><?= date('d M Y', strtotime($r['expiry'])) ?></span>
                                                    <?php if ($r['expiry_hijri'] !== ''): ?><span class="sr-cell-sub"><?= $h($r['expiry_hijri']) ?> <?= __('hijri_suffix', 'H') ?></span><?php endif; ?>
                                                </td>
                                                <td data-order="<?= $d ?>" data-export="<?= $h($status) ?>">
                                                    <span class="sr-pill tone-<?= $tone[$r['key']] ?>"><span class="sr-dot"></span><?= $h($status) ?></span>
                                                </td>
                                                <td data-order="<?= $h($alert['sent_at'] ?? '') ?>" data-export="<?= $h($alert ? $alert_label . ' ' . $alert['sent_at'] : '') ?>">
                                                    <?php if ($alert): ?>
                                                        <span class="sr-cell-title"><?= $h($alert_label) ?></span>
                                                        <span class="sr-cell-sub"><?= $h(date('d M Y', strtotime($alert['sent_at']))) ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">&ndash;</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-right">
                                                    <div class="sr-actions">
                                                        <a href="javascript:void(0);" class="sr-open-btn js-update" data-id="<?= (int)$r['id'] ?>" data-doc="<?= $h($r['doc_key']) ?>" data-name="<?= $h($name) ?>" data-label="<?= $h($doc_label) ?>" data-expiry="<?= $h($r['expiry']) ?>"><i class="mdi mdi-calendar-clock"></i> <?= __('update_expiry', 'Update expiry') ?></a>
                                                    </div>
                                                </td>
                                                <td><?= $r['key'] ?></td>
                                                <td><?= $r['doc_key'] ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
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
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="./plugins/datatables/jquery.dataTables.min.js"></script>
        <script src="./plugins/datatables/dataTables.bootstrap4.min.js"></script>
        <script src="./plugins/datatables/dataTables.buttons.min.js"></script>
        <script src="./plugins/datatables/buttons.bootstrap4.min.js"></script>
        <script src="./plugins/datatables/jszip.min.js"></script>
        <script src="./plugins/datatables/buttons.html5.min.js"></script>
        <script src="./plugins/datatables/buttons.print.min.js"></script>
        <script src="./plugins/datatables/dataTables.responsive.min.js"></script>
        <script src="./plugins/datatables/responsive.bootstrap4.min.js"></script>
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>
        <script src="assets/js/sr_forms.js?v=<?= @filemtime(__DIR__ . '/assets/js/sr_forms.js') ?>"></script>

        <script>
            const F = window.SRForm, esc = F.esc;
            const T = <?= json_encode([
                'update' => __('update_expiry', 'Update expiry'),
                'new_date' => __('new_expiry_date', 'New expiry date'),
                'date_req' => __('select_date_required', 'Please select the date'),
                'hint_iqama' => __('doc_expiry_hint_iqama', 'Gregorian date. The Hijri date is filled automatically.'),
                'run_q' => __('send_alerts_now_q', 'Send expiry alerts now?'),
                'run_txt' => __('send_alerts_now_txt', 'Sends any alert that is due today and has not been sent yet. Nothing is sent twice.'),
                'run_yes' => __('yes_send', 'Yes, send'),
                'export' => __('document_expiry', 'Document Expiry'),
                'print' => __('print', 'Print'),
            ]) ?>;

            $(function() {
                const KEY_COL = 7, DOC_COL = 8, DEPT_COL = 1;
                const exportOptions = {
                    columns: [0, 1, 2, 3, 4, 5],
                    format: { body: function(data, row, col, node) { return $(node).attr('data-export') || $(node).text().trim(); } }
                };
                const table = $('#dx_table').DataTable({
                    dom: 'Brtip',
                    pageLength: 25,
                    responsive: true,
                    order: [[4, 'asc']],
                    buttons: [
                        { extend: 'excel', text: '<i class="mdi mdi-file-excel"></i> Excel', exportOptions: exportOptions, title: T.export },
                        { extend: 'print', text: '<i class="mdi mdi-printer"></i> ' + esc(T.print), exportOptions: exportOptions, title: T.export }
                    ],
                    columnDefs: [
                        { targets: [KEY_COL, DOC_COL], visible: false },
                        { targets: [6], orderable: false }
                    ],
                    language: {
                        info: `${__('showing')} _START_ ${__('to')} _END_ ${__('of')} _TOTAL_ ${__('entries')}`,
                        infoEmpty: `${__('showing')} 0 ${__('to')} 0 ${__('of')} 0 ${__('entries')}`,
                        infoFiltered: '',
                        paginate: { first: __('first'), last: __('last'), next: '<i class="mdi mdi-chevron-right"></i>', previous: '<i class="mdi mdi-chevron-left"></i>' },
                        emptyTable: `<div class="sr-empty"><i class="mdi mdi-account-card-details"></i>${__('no_data_available_in_table')}</div>`,
                        zeroRecords: `<div class="sr-empty"><i class="mdi mdi-magnify"></i>${__('no_matching_records_found')}</div>`
                    }
                });
                table.buttons().container().appendTo('#dxExportButtons');

                const exact = v => v ? '^' + $.fn.dataTable.util.escapeRegex(v) + '$' : '';
                $('#dxTiles').on('click', 'button.sr-tile', function() {
                    $('#dxTiles .sr-tile').removeClass('active');
                    $(this).addClass('active');
                    table.column(KEY_COL).search(exact($(this).data('key')), true, false).draw();
                });
                $('#dxSearch').on('input', function() { table.search(this.value).draw(); });
                $('#dxDoc').on('change', function() { table.column(DOC_COL).search(exact(this.value), true, false).draw(); });
                $('#dxDept').on('change', function() { table.column(DEPT_COL).search(exact(this.value), true, false).draw(); });

                $('#dx_table').on('click', '.js-update', function() {
                    const b = $(this).data();
                    const html = '<form id="dxForm" class="sr-page sr-form text-left" autocomplete="off" novalidate>' +
                        F.section('mdi-calendar-clock', b.name + ' - ' + b.label,
                            F.field({ col: 12, name: 'expiry', id: 'dxDate', label: T.new_date, req: true, msg: T.date_req, value: b.expiry,
                                hint: b.doc === 'iqama' ? esc(T.hint_iqama) : '' })) +
                        '</form>';
                    F.open({
                        title: T.update, html: html, width: '520px',
                        extra: { customClass: { popup: 'sr-addline-popup sr-page' } },
                        didOpen: function() { F.datepicker($('#dxDate')); F.liveClear($('#dxForm')); },
                        preConfirm: function() {
                            const msg = F.validate($('#dxForm'));
                            if (msg) { Swal.showValidationMessage(msg); return false; }
                            return F.post('document_expiry.php', { action: 'update_date', id: b.id, doc_key: b.doc, expiry: $('#dxDate').val() });
                        }
                    }).then(r => F.done(r));
                });

                $('#dxRunAlerts').on('click', function() {
                    F.open({
                        title: T.run_q, icon: 'mdi-email-outline', confirm: T.run_yes, width: '480px',
                        html: '<div class="sr-page"><p style="margin:0;">' + esc(T.run_txt) + '</p></div>',
                        preConfirm: function() { return F.post('document_expiry.php', { action: 'run_alerts' }); }
                    }).then(r => F.done(r));
                });
            });
        </script>
    </body>
    </html>
