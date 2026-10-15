<?php
// Employee info update requests (mobile, email, passport, photo, documents...) waiting for HR.
// Same look as the Smart Request pages (assets/css/smart_request.css). Review posts to
// includes/ajaxFile/hrHandler.php (ajaxType=emp_temp_contant); delete: .deleteAjax in jquery.app.js.
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';
$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='" . $username . "' ");
if (mysqli_num_rows($query) == 1) {
    include("./includes/avatar_select.php");

    $requests = [];
    $counts = ['Pending' => 0, 'Approved' => 0, 'Rejected' => 0];
    // Current employee values are loaded too, so the review popup can show Current -> Requested
    $sql = "SELECT `employee_temp_contants`.*, `employees`.`name`, `department`.`dep_nme`,
            `employees`.`mobile` AS `cur_mobile`, `employees`.`email` AS `cur_email`, `employees`.`passport_number` AS `cur_passport_number`,
            `employees`.`passport_exp` AS `cur_passport_exp`, `employees`.`address` AS `cur_address`, `employees`.`avatar` AS `cur_avatar`
        FROM `employee_temp_contants`
        LEFT JOIN `employees` ON `employees`.`emp_id` = `employee_temp_contants`.`emp_id`
        LEFT JOIN `department` ON `department`.`id` = `employees`.`dept`
        WHERE `employees`.`status` = 1
        ORDER BY (`employee_temp_contants`.`status` = 'Pending') DESC, `employee_temp_contants`.`id` DESC";
    $q = mysqli_query($conDB, $sql);
    while ($q && ($r = mysqli_fetch_assoc($q))) {
        $requests[] = $r;
        if (isset($counts[$r['status']])) $counts[$r['status']]++;
    }
    $can_delete = ($user_type == $access1);
    $h = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES); };
    $type_icon = function ($type) {
        $t = strtolower((string)$type);
        if (strpos($t, 'mobile') !== false) return 'mdi-cellphone';
        if (strpos($t, 'email') !== false) return 'mdi-email';
        if (strpos($t, 'address') !== false) return 'mdi-map-marker';
        if (strpos($t, 'passport') !== false) return 'mdi-passport';
        if (strpos($t, 'picture') !== false || strpos($t, 'photo') !== false) return 'mdi-account-box';
        if (strpos($t, 'document') !== false) return 'mdi-file-document';
        return 'mdi-pencil';
    };
    // Current value of the field a request wants to change (same mapping as hrHandler.php approve)
    $current_value = function ($rec) {
        $map = ['Mobile' => 'cur_mobile', 'Email' => 'cur_email', 'Passport No' => 'cur_passport_number',
                'Passport Exp' => 'cur_passport_exp', 'Address' => 'cur_address', 'Profile Picture' => 'cur_avatar'];
        return isset($map[$rec['type']]) ? (string)($rec[$map[$rec['type']]] ?? '') : '';
    };
    $initials = function ($name) {
        $p = preg_split('/\s+/', trim((string)$name), -1, PREG_SPLIT_NO_EMPTY);
        return $p ? mb_strtoupper(mb_substr($p[0], 0, 1) . (isset($p[1]) ? mb_substr($p[1], 0, 1) : '')) : '?';
    };
?>
<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?> - <?= __('employee_info_update_request', 'Info update requests') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="Anees Afzal" name="author" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

    <link rel="shortcut icon" href="<?= get_setting($conDB, 'favicon') ?>">
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
    <style>
        .tc-value { max-width: 260px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: inline-block; vertical-align: middle; font-weight: 600; color: var(--sr-text); }
        /* Review popup: employee header, Current -> Requested comparison, decision */
        .tc-head { display: flex; align-items: center; gap: 12px; padding: 14px 16px; border: 1px solid var(--sr-border); border-radius: 12px; background: var(--sr-surface-2); margin-bottom: 14px; }
        .tc-head .sr-avatar { width: 44px; height: 44px; font-size: 15px; }
        .tc-head-main { flex: 1 1 auto; min-width: 0; }
        .tc-head-meta { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 6px; }
        .tc-head-type { flex: 0 0 auto; display: inline-flex; align-items: center; justify-content: center; width: 44px; height: 44px; border-radius: 12px;
            background: var(--tone-indigo-bg); color: var(--tone-indigo-fg); font-size: 22px; }
        .tc-compare { display: grid; grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr); align-items: stretch; gap: 10px; margin-bottom: 14px; }
        .tc-side { min-width: 0; padding: 12px 14px; border-radius: 12px; border: 1px solid var(--sr-border); background: var(--sr-surface); }
        .tc-side.is-new { border-color: var(--tone-green-bd); background: var(--tone-green-bg); }
        .tc-side-label { display: flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; color: var(--sr-muted); margin-bottom: 6px; }
        .tc-side.is-new .tc-side-label { color: var(--tone-green-fg); }
        .tc-side-value { font-size: 16px; font-weight: 700; color: var(--sr-text); word-break: break-word; unicode-bidi: plaintext; }
        .tc-side.is-new .tc-side-value { color: var(--tone-green-fg); }
        .tc-side-value.is-empty { font-size: 13px; font-weight: 500; color: var(--sr-muted); font-style: italic; }
        .tc-arrow { display: flex; align-items: center; justify-content: center; color: var(--sr-muted); font-size: 22px; }
        [dir="rtl"] .tc-arrow i { transform: scaleX(-1); }
        .tc-img { display: block; width: 100%; max-height: 220px; object-fit: contain; border-radius: 10px; border: 1px solid var(--sr-border); background: var(--sr-surface); }
        .tc-file { display: flex; align-items: center; gap: 12px; padding: 12px 14px; margin-bottom: 14px; border-radius: 12px; border: 1px dashed var(--sr-border-strong); background: var(--sr-surface-2); }
        .tc-file i { font-size: 28px; color: var(--tone-indigo-fg); }
        .tc-file-main { flex: 1 1 auto; min-width: 0; }
        @media (max-width: 575px) { .tc-compare { grid-template-columns: 1fr; } .tc-arrow i { transform: rotate(90deg); } }
    </style>
    <script> window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;</script>
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
                            <h1><?= __('employee_info_update_request', 'Employee info update requests') ?></h1>
                            <p><?= __('temp_contant_subtitle', 'Changes employees asked for (mobile, email, passport, photo, documents) waiting for HR approval.') ?></p>
                        </div>
                    </div>

                    <?php
                    require_once __DIR__ . '/includes/sr_list_helpers.php'; // sr_scard_foot()
                    // [status, tone, icon, label, hint, count]
                    $tc_tiles = [
                        ['Pending', 'amber', 'mdi-timer-sand', __('pending', 'Pending'), __('tc_hint_pending', 'Waiting for HR approval'), $counts['Pending']],
                        ['Approved', 'green', 'mdi-check-circle', __('approved', 'Approved'), __('tc_hint_approved', 'Applied to the profile'), $counts['Approved']],
                        ['Rejected', 'red', 'mdi-close-circle', __('rejected', 'Rejected'), __('tc_hint_rejected', 'Declined changes'), $counts['Rejected']],
                        ['', 'indigo', 'mdi-format-list-bulleted', __('all', 'All'), __('tc_hint_all', 'Every update request'), count($requests)],
                    ];
                    ?>
                    <div class="sr-scards" id="tcTiles" style="--sr-scard-cols: 4;">
                        <?php foreach ($tc_tiles as [$tc_status, $tc_tone, $tc_icon, $tc_label, $tc_hint, $tc_count]): ?>
                        <button type="button" class="sr-tile sr-scard st-<?= $tc_tone ?><?= $tc_status === 'Pending' ? ' active' : '' ?><?= ($tc_status === 'Pending' && $tc_count > 0) ? ' sr-scard-alert' : '' ?>" data-status="<?= $tc_status ?>" title="<?= htmlspecialchars($tc_hint, ENT_QUOTES) ?>">
                            <span class="sr-scard-ico"><i class="mdi <?= $tc_icon ?>"></i></span>
                            <span class="sr-scard-body">
                                <span class="sr-scard-label"><?= htmlspecialchars($tc_label) ?></span>
                                <span class="sr-scard-hint"><?= htmlspecialchars($tc_hint) ?></span>
                            </span>
                            <span class="sr-scard-value"><?= number_format($tc_count) ?></span>
                            <?= sr_scard_foot($tc_count, count($requests)) ?>
                        </button>
                        <?php endforeach; ?>
                    </div>

                    <div class="sr-card">
                        <div class="sr-toolbar">
                            <div class="sr-search">
                                <i class="mdi mdi-magnify"></i>
                                <input type="search" id="tcSearch" placeholder="<?= __('search') ?>..." autocomplete="off" aria-label="<?= __('search') ?>">
                            </div>
                            <div class="sr-toolbar-right"><div id="tcButtons"></div></div>
                        </div>
                        <div class="sr-table-wrap">
                            <table id="orders_tbl" class="table sr-table dt-responsive nowrap" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th>id</th>
                                        <th><?= __('employee', 'Employee') ?></th>
                                        <th><?= __('department', 'Department') ?></th>
                                        <th><?= __('request_type', 'Request type') ?></th>
                                        <th><?= __('new_value', 'New value') ?></th>
                                        <th><?= __('date', 'Date') ?></th>
                                        <th><?= __('status', 'Status') ?></th>
                                        <th class="text-right"><?= __('action', 'Action') ?></th>
                                        <th>status-key</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($requests as $rec):
                                        $pending = ($rec['status'] === 'Pending');
                                        $tone = $pending ? 'amber' : ($rec['status'] === 'Approved' ? 'green' : 'red');
                                        $date = $rec['created_at'] ? strtotime($rec['created_at']) : 0;
                                    ?>
                                        <tr data-id="<?= (int)$rec['id'] ?>">
                                            <td><?= (int)$rec['id'] ?></td>
                                            <td data-order="<?= $h($rec['name']) ?>">
                                                <div class="sr-person">
                                                    <span class="sr-avatar"><?= $h($initials($rec['name'])) ?></span>
                                                    <div style="min-width: 0;">
                                                        <span class="sr-cell-title"><?= $h($rec['name']) ?></span>
                                                        <span class="sr-cell-sub sr-mono"><?= $h($rec['emp_id']) ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?= $rec['dep_nme'] ? '<span class="sr-chip">' . $h($rec['dep_nme']) . '</span>' : '<span class="text-muted">&ndash;</span>' ?></td>
                                            <td><span class="sr-chip"><i class="mdi <?= $type_icon($rec['type']) ?>"></i><?= $h($rec['type']) ?></span></td>
                                            <td>
                                                <?php if ($rec['path']): ?>
                                                    <a href="<?= $h($rec['path']) ?>" target="_blank" rel="noopener" class="sr-open-btn"><i class="mdi mdi-paperclip"></i> <?= __('show_attachment', 'Attachment') ?></a>
                                                    <?php if ($rec['new_value']): ?><span class="sr-cell-sub"><?= $h(mb_strimwidth($rec['new_value'], 0, 40, '…')) ?></span><?php endif; ?>
                                                <?php else: ?>
                                                    <span class="tc-value" title="<?= $h($rec['new_value']) ?>"><?= $h($rec['new_value']) ?: '&ndash;' ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td data-order="<?= $date ?>"><span class="sr-date"><?= $date ? date('d M Y', $date) : '' ?><small><?= $date ? date('h:i a', $date) : '' ?></small></span></td>
                                            <td>
                                                <span class="sr-pill sr-pill-xs tone-<?= $tone ?>"><span class="sr-dot"></span><?= $h(__(strtolower($rec['status']), $rec['status'])) ?></span>
                                                <?php if (!$pending && $rec['notes']): ?><span class="sr-cell-sub" title="<?= $h($rec['notes']) ?>"><i class="mdi mdi-comment-text"></i><?= $h(mb_strimwidth($rec['notes'], 0, 30, '…')) ?></span><?php endif; ?>
                                            </td>
                                            <td class="text-right">
                                                <div class="sr-actions">
                                                    <?php if ($pending): ?>
                                                        <a href="javascript:void(0);" class="sr-open-btn contantChk" data-emp_id="<?= $h($rec['emp_id']) ?>" data-name="<?= $h($rec['name']) ?>" data-id="<?= (int)$rec['id'] ?>" data-path="<?= $h($rec['path']) ?>" data-type="<?= $h($rec['type']) ?>" data-new_value="<?= $h($rec['new_value']) ?>" data-current="<?= $h($current_value($rec)) ?>" data-dept="<?= $h($rec['dep_nme']) ?>" data-date="<?= $date ? $h(date('d M Y, h:i a', $date)) : '' ?>" data-icon="<?= $type_icon($rec['type']) ?>"><i class="mdi mdi-check"></i> <?= __('review', 'Review') ?></a>
                                                    <?php endif; ?>
                                                    <?php if ($can_delete): ?>
                                                        <a href="javascript:void(0);" class="sr-more-btn deleteAjax" title="<?= __('delete') ?>" data-id="<?= (int)$rec['id'] ?>" data-tbl="employee_temp_contants" data-file="0"><i class="mdi mdi-delete text-danger"></i></a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td><?= $h($rec['status']) ?></td>
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
    <script src="assets/js/jquery.core.js"></script>
    <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>
    <script src="assets/js/sr_forms.js?v=<?= @filemtime(__DIR__ . '/assets/js/sr_forms.js') ?>"></script>

    <script type="text/javascript">
        $(document).ready(function() {
            const F = window.SRForm, t = F.t, esc = F.esc;
            const STATUS_COL = 8;
            const exportTitle = <?= json_encode(__('employee_info_update_request', 'Employee info update requests')) ?>;
            const exportOptions = { columns: [1, 2, 3, 4, 5, 6] };

            const table = $('#orders_tbl').DataTable({
                dom: 'Brtip',
                pageLength: 15,
                responsive: true,
                order: [[0, 'desc']],
                buttons: [
                    { extend: 'excel', text: '<i class="mdi mdi-file-excel"></i> Excel', exportOptions: exportOptions, title: exportTitle },
                    { extend: 'pdf', text: '<i class="mdi mdi-file-pdf"></i> PDF', exportOptions: exportOptions, title: exportTitle },
                    { extend: 'print', text: '<i class="mdi mdi-printer"></i> ' + t('print', 'Print'), exportOptions: exportOptions, title: exportTitle }
                ],
                columnDefs: [
                    { targets: [0, STATUS_COL], visible: false },
                    { targets: [4, 7], orderable: false }
                ],
                language: {
                    info: `${__('showing')} _START_ ${__('to')} _END_ ${__('of')} _TOTAL_ ${__('entries')}`,
                    infoEmpty: `${__('showing')} 0 ${__('to')} 0 ${__('of')} 0 ${__('entries')}`,
                    infoFiltered: '',
                    paginate: { first: __('first'), last: __('last'), next: '<i class="mdi mdi-chevron-right"></i>', previous: '<i class="mdi mdi-chevron-left"></i>' },
                    emptyTable: `<div class="sr-empty"><i class="mdi mdi-inbox"></i>${__('no_data_available_in_table')}</div>`,
                    zeroRecords: `<div class="sr-empty"><i class="mdi mdi-check-all"></i>${t('nothing_here', 'Nothing here.')}</div>`
                }
            });
            table.buttons().container().appendTo('#tcButtons');
            const byStatus = s => table.column(STATUS_COL).search(s ? '^' + s + '$' : '', true, false).draw();
            byStatus('Pending');

            $('#tcSearch').on('input', function() { table.search(this.value).draw(); });
            $('#tcTiles').on('click', '.sr-tile', function() {
                $('#tcTiles .sr-tile').removeClass('active');
                $(this).addClass('active');
                byStatus($(this).data('status'));
            });

            // Review a pending request
            $(document).on('click', '.contantChk', function(e) {
                e.preventDefault();
                const d = $(this).data();
                const path = d.path || '', isImg = /\.(jpe?g|png|gif|webp)$/i.test(path);
                const ini = String(d.name || '?').trim().split(/\s+/).slice(0, 2).map(w => w[0]).join('').toUpperCase();

                const current = String(d.current || '').trim(), isAvatar = d.type === 'Profile Picture';
                const side = (cls, icon, label, body) =>
                    `<div class="tc-side ${cls}"><div class="tc-side-label"><i class="mdi ${icon}"></i>${esc(label)}</div>${body}</div>`;
                const valueBox = (v) => v ? `<div class="tc-side-value">${esc(v)}</div>`
                    : `<div class="tc-side-value is-empty">${esc(t('not_set', 'Not set'))}</div>`;
                const imgBox = (src) => src ? `<a href="${esc(src)}" target="_blank" rel="noopener"><img class="tc-img" src="${esc(src)}" alt=""></a>`
                    : `<div class="tc-side-value is-empty">${esc(t('no_picture', 'No picture'))}</div>`;

                // What changes: picture -> side-by-side images; file -> attachment card; value -> Current / Requested
                let change;
                if (isAvatar || (path && isImg && current)) {
                    change = '<div class="tc-compare">' +
                        side('', 'mdi-history', t('current', 'Current'), imgBox(current)) +
                        '<div class="tc-arrow"><i class="mdi mdi-arrow-right-bold"></i></div>' +
                        side('is-new', 'mdi-new-box', t('requested', 'Requested'), imgBox(path)) +
                        '</div>';
                } else if (path) {
                    change = (isImg ? `<div class="tc-compare" style="grid-template-columns:1fr;">${side('is-new', 'mdi-new-box', t('requested', 'Requested'), imgBox(path))}</div>` : '') +
                        `<div class="tc-file"><i class="mdi mdi-file-document"></i>
                            <div class="tc-file-main"><span class="sr-cell-title">${esc(d.new_value || d.type)}</span>
                                <span class="sr-cell-sub sr-mono">${esc(path.split('/').pop())}</span></div>
                            <a href="${esc(path)}" target="_blank" rel="noopener" class="sr-btn sr-btn-sm"><i class="mdi mdi-open-in-new"></i> ${esc(t('open', 'Open'))}</a></div>`;
                } else {
                    change = '<div class="tc-compare">' +
                        side('', 'mdi-history', t('current', 'Current'), valueBox(current)) +
                        '<div class="tc-arrow"><i class="mdi mdi-arrow-right-bold"></i></div>' +
                        side('is-new', 'mdi-new-box', t('requested', 'Requested'), valueBox(String(d.new_value || ''))) +
                        '</div>';
                }

                const html = '<form id="submitEmployeeTempContantForm" class="sr-page sr-form text-left" autocomplete="off" novalidate>' +
                    `<div class="tc-head">
                        <span class="sr-avatar">${esc(ini)}</span>
                        <div class="tc-head-main">
                            <span class="sr-cell-title">${esc(d.name)}</span>
                            <span class="sr-cell-sub sr-mono">${esc(d.emp_id)}</span>
                            <div class="tc-head-meta">
                                <span class="sr-chip"><i class="mdi ${esc(d.icon || 'mdi-pencil')}"></i>${esc(d.type)}</span>
                                ${d.dept ? `<span class="sr-chip"><i class="mdi mdi-domain"></i>${esc(d.dept)}</span>` : ''}
                                ${d.date ? `<span class="sr-chip"><i class="mdi mdi-calendar-clock"></i>${esc(d.date)}</span>` : ''}
                            </div>
                        </div>
                        <span class="tc-head-type"><i class="mdi ${esc(d.icon || 'mdi-pencil')}"></i></span>
                    </div>` +
                    change +
                    F.section('mdi-gavel', t('decision', 'Decision'),
                        F.field({ col: 12, name: 'contant_check', html:
                            '<div class="sr-attach-choice">' +
                                `<label class="sr-choice sr-choice-approve"><input type="radio" name="contant_check" value="approve" data-required="1" data-msg="${esc(t('select_action_validation', 'Select an action'))}"><span><i class="mdi mdi-check-circle"></i> ${esc(t('approve_and_apply', 'Approve & apply'))}</span></label>` +
                                `<label class="sr-choice sr-choice-reject"><input type="radio" name="contant_check" value="not_approve"><span><i class="mdi mdi-close-circle"></i> ${esc(t('reject_request', 'Reject'))}</span></label>` +
                            '</div>',
                            hint: esc(t('tc_approve_hint', 'Approving copies the requested value into the employee profile.')) }) +
                        F.field({ col: 12, name: 'notes', id: 'tcNotes', type: 'textarea', rows: 3, label: t('notes', 'Notes'), ph: t('optional_notes_placeholder', 'Optional notes') }).replace('sr-fcol c-12', 'sr-fcol c-12 js-notes" style="display:none;')
                    ) +
                    `<input type="hidden" name="id" value="${esc(d.id)}"><input type="hidden" name="empid" value="${esc(d.emp_id)}">` +
                '</form>';

                F.open({
                    title: t('employee_info_update_request', 'Info update request'),
                    html: html,
                    width: '720px',
                    icon: 'mdi-check',
                    confirm: t('submit_action', 'Submit'),
                    didOpen: function() {
                        const $form = $('#submitEmployeeTempContantForm');
                        F.liveClear($form);
                        $form.on('change', 'input[name="contant_check"]', function() {
                            const reject = this.value === 'not_approve';
                            $form.find('.js-notes').show().find('label').html(esc(reject ? t('rejection_reason', 'Rejection reason') : t('approval_notes', 'Approval notes (optional)')) + (reject ? ' <span class="text-danger">*</span>' : ''));
                            $('#tcNotes').attr('data-required', reject ? '1' : null)
                                .attr('data-msg', t('enter_rejection_reason_validation', 'Enter the rejection reason'))
                                .attr('placeholder', reject ? t('provide_rejection_reason_placeholder', 'Why is it rejected?') : t('optional_notes_placeholder', 'Optional notes'));
                            $('.swal2-confirm').css('background-color', reject ? '#dc2626' : '');
                        });
                    },
                    preConfirm: function() {
                        const $form = $('#submitEmployeeTempContantForm');
                        const msg = F.validate($form);
                        if (msg) { Swal.showValidationMessage(msg); return false; }
                        return F.post('./includes/ajaxFile/hrHandler.php', $form.serialize() + '&' + $.param({ ajaxType: 'emp_temp_contant' }));
                    }
                }).then(function(r) { F.done(r); });
            });

            // Whole row opens the review (pending only)
            $('#orders_tbl tbody').on('click', 'tr', function(e) {
                if ($(e.target).closest('a, button, .dtr-control').length) return;
                $(this).find('.contantChk').trigger('click');
            });
        });
    </script>
</body>
</html>
<?php } ?>
