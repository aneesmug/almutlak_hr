<?php
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/helper_functions.php';
require_once __DIR__ . '/includes/special_access_helper.php';

// Verify user is logged in and is allowed to view rejoin approvals
if (empty($_SESSION['empid'])) {
    header('Location: index.php');
    exit;
}

$supervisor_emp_id = $_SESSION['empid'];

$can_cancel_rejoin_requests = (
    !empty($is_system_admin)
    || user_has_special_access($conDB, $supervisor_emp_id ?? '', 'cancel_rejoin_requests', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)
);

// HR Payroll, HR Senior BP, and Administrator can pick ANY rejoin adjustment date
// (including back dates) - everyone else stays limited to the +/-3 day window.
$rejoin_elevated_roles = ['hr_payroll', 'hr_senior_bp', 'administrator'];
$is_rejoin_elevated = in_array(strtolower(trim((string)($user_type ?? ''))), $rejoin_elevated_roles, true);
?>
<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>
<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?> - <?= __('rejoin_approvals', 'Rejoin Approvals') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

    <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">
    <link href="./plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="./plugins/datatables/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="./plugins/bootstrap-datepicker/css/bootstrap-datepicker.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/smart_request.css?v=<?= @filemtime(__DIR__ . '/assets/css/smart_request.css') ?>" rel="stylesheet" type="text/css" />
    <?php if ($is_rtl): ?>
        <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
    <?php endif; ?>
    <style>
        .sr-page .sr-tiles.rj-tiles { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .rj-pane { display: none; }
        .rj-pane.active { display: block; }
        .rj-reason { max-width: 280px; white-space: normal; font-size: 12.5px; color: var(--sr-text-2); }
        .rj-diff { font-size: 11px; font-weight: 700; }
    </style>
    <script> window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;</script>
    <script> window.isRejoinElevated = <?= $is_rejoin_elevated ? 'true' : 'false' ?>;</script>
    <script> window.canCancelRejoinRequests = <?= $can_cancel_rejoin_requests ? 'true' : 'false' ?>;</script>
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
            </div>
        </div>
        <div class="content-page">
            <?php include("./includes/topbar.php"); ?>

            <div class="content sr-page">
                <div class="container-fluid">

                    <div class="sr-head">
                        <div>
                            <h1><?= __('rejoin_approval_requests', 'Rejoin Approval Requests') ?></h1>
                            <p><?= __('rejoin_approvals_subtitle', 'Employees back from vacation asking to confirm their rejoin date.') ?></p>
                        </div>
                    </div>

                    <div class="sr-tiles rj-tiles" id="rjTiles">
                        <button type="button" class="sr-tile active" data-pane="pending">
                            <span class="sr-tile-label"><span class="sr-dot dot-amber"></span><?= __('pending_requests', 'Pending') ?></span>
                            <span class="sr-tile-value" id="pending-count">&ndash;</span>
                        </button>
                        <button type="button" class="sr-tile" data-pane="approved">
                            <span class="sr-tile-label"><span class="sr-dot dot-green"></span><?= __('approved_requests', 'Approved') ?></span>
                            <span class="sr-tile-value" id="approved-count">&ndash;</span>
                        </button>
                        <button type="button" class="sr-tile" data-pane="rejected">
                            <span class="sr-tile-label"><span class="sr-dot dot-red"></span><?= __('rejected_requests', 'Rejected') ?></span>
                            <span class="sr-tile-value" id="rejected-count">&ndash;</span>
                        </button>
                    </div>

                    <div class="sr-card">
                        <div class="sr-toolbar">
                            <div class="sr-search">
                                <i class="mdi mdi-magnify"></i>
                                <input type="search" id="rjSearch" placeholder="<?= __('search') ?>..." autocomplete="off" aria-label="<?= __('search') ?>">
                            </div>
                        </div>

                        <div class="rj-pane active" id="pending">
                            <div class="sr-table-wrap">
                                <table id="pendingRequestsTable" class="table sr-table dt-responsive nowrap" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th><?= __('employee_name', 'Employee') ?></th>
                                            <th>ID</th>
                                            <th><?= __('planned_return_date', 'Planned return') ?></th>
                                            <th><?= __('requested_rejoin_date', 'Requested rejoin') ?></th>
                                            <th><?= __('reason', 'Reason') ?></th>
                                            <th><?= __('submitted_date', 'Submitted') ?></th>
                                            <th class="text-right"><?= __('actions', 'Actions') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                        <div class="rj-pane" id="approved">
                            <div class="sr-table-wrap">
                                <table id="approvedRequestsTable" class="table sr-table dt-responsive nowrap" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th><?= __('employee_name', 'Employee') ?></th>
                                            <th>ID</th>
                                            <th><?= __('approved_date', 'Approved date') ?></th>
                                            <th><?= __('approval_note', 'Approval note') ?></th>
                                            <th><?= __('approved_at', 'Approved at') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                        <div class="rj-pane" id="rejected">
                            <div class="sr-table-wrap">
                                <table id="rejectedRequestsTable" class="table sr-table dt-responsive nowrap" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th><?= __('employee_name', 'Employee') ?></th>
                                            <th>ID</th>
                                            <th><?= __('rejection_reason', 'Rejection reason') ?></th>
                                            <th><?= __('rejected_at', 'Rejected at') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
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
    <script src="./plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/metisMenu.min.js"></script>
    <script src="assets/js/waves.js"></script>
    <script src="assets/js/jquery.slimscroll.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="./plugins/datatables/jquery.dataTables.min.js"></script>
    <script src="./plugins/datatables/dataTables.bootstrap4.min.js"></script>
    <script src="./plugins/datatables/dataTables.responsive.min.js"></script>
    <script src="./plugins/datatables/responsive.bootstrap4.min.js"></script>
    <script src="assets/js/jquery.core.js"></script>
    <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>
    <script src="assets/js/sr_forms.js?v=<?= @filemtime(__DIR__ . '/assets/js/sr_forms.js') ?>"></script>
    <script>
        const F = window.SRForm, esc = F.esc;
        const LOCALE = '<?= ($current_lang ?? 'en') === 'ar' ? 'ar-SA' : 'en-GB' ?>';
        const T = {
            review: <?= json_encode(__('review', 'Review')) ?>,
            cancel: <?= json_encode(__('cancel', 'Cancel')) ?>,
            fly: <?= json_encode(__('fly_vacation', 'Fly vacation')) ?>,
            local: <?= json_encode(__('local_vacation', 'Local vacation')) ?>,
            daysLate: <?= json_encode(__('days_after_plan', 'd after plan')) ?>,
            daysEarly: <?= json_encode(__('days_before_plan', 'd before plan')) ?>,
            onTime: <?= json_encode(__('on_time', 'On time')) ?>
        };
        let pendingTable, approvedTable, rejectedTable;

        function fmtDate(d) {
            if (!d) return '<span class="text-muted">&ndash;</span>';
            const dt = new Date(String(d).replace(' ', 'T'));
            return isNaN(dt) ? esc(d) : dt.toLocaleDateString(LOCALE, { day: '2-digit', month: 'short', year: 'numeric' });
        }
        function person(name, id) {
            const ini = String(name || '?').trim().split(/\s+/).slice(0, 2).map(w => w[0]).join('').toUpperCase();
            return `<div class="sr-person"><span class="sr-avatar">${esc(ini)}</span><div style="min-width:0;"><span class="sr-cell-title">${esc(name || '-')}</span><span class="sr-cell-sub sr-mono">${esc(id)}</span></div></div>`;
        }
        function longText(v) { return v ? `<div class="rj-reason">${esc(v)}</div>` : '<span class="text-muted">&ndash;</span>'; }
        const language = {
            info: `${__('showing')} _START_ ${__('to')} _END_ ${__('of')} _TOTAL_ ${__('entries')}`,
            infoEmpty: `${__('showing')} 0 ${__('to')} 0 ${__('of')} 0 ${__('entries')}`,
            infoFiltered: '',
            paginate: { first: __('first'), last: __('last'), next: '<i class="mdi mdi-chevron-right"></i>', previous: '<i class="mdi mdi-chevron-left"></i>' },
            emptyTable: `<div class="sr-empty"><i class="mdi mdi-inbox"></i>${__('no_data_available_in_table')}</div>`,
            zeroRecords: `<div class="sr-empty"><i class="mdi mdi-magnify"></i>${__('no_matching_records_found')}</div>`,
            processing: __('processing', 'Processing...')
        };
        function makeTable(sel, status, columns, order) {
            return $(sel).DataTable({
                dom: 'rtip', processing: true, serverSide: true, responsive: true, pageLength: 10, order: order,
                ajax: {
                    url: './includes/get_rejoin_requests.php',
                    type: 'POST',
                    data: d => { d.status = status; },
                    dataSrc: json => { $('#' + status + '-count').text(Number((json && json.recordsTotal) || 0)); return json.data || []; }
                },
                columns: columns,
                language: language
            });
        }

        function loadRejoinRequests() {
            [pendingTable, approvedTable, rejectedTable].forEach(t => t && t.ajax.reload(null, false));
        }

        $(document).ready(function() {
            // Column indexes are used for sorting by includes/get_rejoin_requests.php - keep emp_id at index 1
            pendingTable = makeTable('#pendingRequestsTable', 'pending', [
                { data: 'emp_name', render: (d, t, r) => person(d, r.emp_id) },
                { data: 'emp_id', visible: false },
                { data: 'return_date', render: fmtDate },
                { data: 'requested_rejoin_date', render: function(d, t, r) {
                    if (!d) return '<span class="text-muted">&ndash;</span>';
                    let diff = '';
                    if (r.return_date) {
                        const n = Math.round((new Date(d) - new Date(r.return_date)) / 86400000);
                        diff = n === 0 ? `<small class="rj-diff text-success">${esc(T.onTime)}</small>`
                            : `<small class="rj-diff ${n > 0 ? 'text-danger' : 'text-primary'}">${Math.abs(n)}${esc(n > 0 ? T.daysLate : T.daysEarly)}</small>`;
                    }
                    return `<span class="sr-date"><span class="sr-pill sr-pill-xs tone-amber">${fmtDate(d)}</span><br>${diff}</span>`;
                } },
                { data: 'requested_reason', orderable: false, render: longText },
                { data: 'requested_at', render: fmtDate },
                { data: null, orderable: false, searchable: false, className: 'text-right', render: function(d, t, r) {
                    let html = `<a href="javascript:void(0);" class="sr-open-btn js-review" data-id="${esc(r.rejoin_request_id)}" data-emp="${esc(r.emp_id)}" data-date="${esc(r.requested_rejoin_date)}" data-name="${esc(r.emp_name)}" data-vac="${esc(r.vac_type || '')}"><i class="mdi mdi-check"></i> ${esc(T.review)}</a>`;
                    if (window.canCancelRejoinRequests) {
                        html += `<a href="javascript:void(0);" class="sr-more-btn js-cancel" title="${esc(T.cancel)}" data-id="${esc(r.rejoin_request_id)}" data-name="${esc(r.emp_name)}"><i class="mdi mdi-cancel text-danger"></i></a>`;
                    }
                    return `<div class="sr-actions">${html}</div>`;
                } }
            ], [[5, 'desc']]);

            approvedTable = makeTable('#approvedRequestsTable', 'approved', [
                { data: 'emp_name', render: (d, t, r) => person(d, r.emp_id) },
                { data: 'emp_id', visible: false },
                { data: 'final_approved_date', render: (d, t, r) => (d || r.approved_date) ? `<span class="sr-pill sr-pill-xs tone-green"><span class="sr-dot"></span>${fmtDate(d || r.approved_date)}</span>` : '<span class="text-muted">&ndash;</span>' },
                { data: 'approval_note', orderable: false, render: longText },
                { data: 'approved_at', render: fmtDate }
            ], [[4, 'desc']]);

            rejectedTable = makeTable('#rejectedRequestsTable', 'rejected', [
                { data: 'emp_name', render: (d, t, r) => person(d, r.emp_id) },
                { data: 'emp_id', visible: false },
                { data: 'rejection_reason', orderable: false, render: longText },
                { data: 'approved_at', render: fmtDate }
            ], [[3, 'desc']]);

            let timer;
            $('#rjSearch').on('input', function() {
                clearTimeout(timer);
                const v = this.value;
                timer = setTimeout(() => [pendingTable, approvedTable, rejectedTable].forEach(t => t.search(v).draw()), 350);
            });

            $('#rjTiles').on('click', '.sr-tile', function() {
                $('#rjTiles .sr-tile').removeClass('active');
                $(this).addClass('active');
                $('.rj-pane').removeClass('active');
                $('#' + $(this).data('pane')).addClass('active');
                $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust().responsive.recalc();
            });

            $(document).on('click', '.js-review', function() {
                const d = $(this).data();
                viewAndApproveRequest(d.id, d.emp, d.date, d.name, d.vac);
            });
            $(document).on('click', '.js-cancel', function() {
                cancelRejoinRequestAdmin($(this).data('id'), $(this).data('name'));
            });
        });

        function viewAndApproveRequest(rejoinRequestId, empId, rejoinDate, empName, vacationType) {
            const isFly = String(vacationType || '').toLowerCase() === 'fly';
            const reqDate = new Date(rejoinDate), toDate = new Date(reqDate);
            toDate.setDate(toDate.getDate() + 3);
            const rangeText = window.isRejoinElevated
                ? <?= json_encode(__("adjustment_window_unrestricted", "You can select any date, including back dates")) ?>
                : <?= json_encode(__("adjustment_window", "Employee can select date between")) ?> + ' ' + reqDate.toLocaleDateString(LOCALE) + ' ' + <?= json_encode(__("and", "and")) ?> + ' ' + toDate.toLocaleDateString(LOCALE);

            const ini = String(empName || '?').trim().split(/\s+/).slice(0, 2).map(w => w[0]).join('').toUpperCase();
            const html = '<form id="rjForm" class="sr-page sr-form text-left" autocomplete="off" novalidate>' +
                `<div class="sr-fsec"><div class="sr-fgrid" style="align-items:center;">
                    <div class="sr-fcol c-7"><div class="sr-person"><span class="sr-avatar" style="width:42px;height:42px;">${esc(ini)}</span>
                        <div style="min-width:0;"><span class="sr-cell-title">${esc(empName)}</span><span class="sr-cell-sub sr-mono">${esc(empId)}</span></div></div></div>
                    <div class="sr-fcol c-5" style="text-align:end;">
                        <span class="sr-chip"><i class="mdi ${isFly ? 'mdi-airplane' : 'mdi-map-marker'}"></i>${esc(isFly ? T.fly : T.local)}</span>
                        <span class="sr-chip"><i class="mdi mdi-calendar"></i>${esc(rejoinDate)}</span>
                    </div>
                </div></div>` +
                F.section('mdi-gavel', <?= json_encode(__("action", "Action")) ?>,
                    F.field({ col: 12, name: 'action', html:
                        '<div class="sr-attach-choice" style="grid-template-columns: repeat(3, minmax(0, 1fr));">' +
                            `<label class="sr-choice sr-choice-approve"><input type="radio" name="action" value="approve" checked><span><i class="mdi mdi-check-circle"></i> ${esc(<?= json_encode(__("approve_immediately", "Approve immediately")) ?>)}</span></label>` +
                            `<label class="sr-choice"><input type="radio" name="action" value="adjust"><span><i class="mdi mdi-calendar-range"></i> ${esc(<?= json_encode(__("allow_adjustment", "Allow ±3 days adjustment")) ?>)}</span></label>` +
                            `<label class="sr-choice sr-choice-reject"><input type="radio" name="action" value="reject"><span><i class="mdi mdi-close-circle"></i> ${esc(<?= json_encode(__("reject_request", "Reject request")) ?>)}</span></label>` +
                        '</div>' }) +
                    F.field({ col: 12, name: 'approval_note', id: 'approvalNote', type: 'textarea', rows: 2, label: <?= json_encode(__("approval_note", "Approval note") . ' (' . __("optional", "Optional") . ')') ?>, ph: <?= json_encode(__("add_note", "Add a note...")) ?> }).replace('sr-fcol c-12', 'sr-fcol c-12 js-approve') +
                    F.field({ col: 5, name: 'adjustment_date', id: 'adjustmentDate', label: <?= json_encode(__("select_adjustment_date", "Adjustment date")) ?>, req: true, ph: 'YYYY-MM-DD', attrs: ' readonly', hint: esc(rangeText),
                        msg: <?= json_encode(__("adjustment_date_required", "Please select an adjustment date")) ?> }).replace('sr-fcol c-5', 'sr-fcol c-5 js-adjust') +
                    F.field({ col: 7, name: 'adjustment_note', id: 'adjustmentNote', type: 'textarea', rows: 2, label: <?= json_encode(__("adjustment_note", "Adjustment note") . ' (' . __("optional", "Optional") . ')') ?>, ph: <?= json_encode(__("explain_adjustment_window", "Explain the adjustment window...")) ?> }).replace('sr-fcol c-7', 'sr-fcol c-7 js-adjust') +
                    F.field({ col: 12, name: 'rejection_reason', id: 'rejectionReason', type: 'textarea', rows: 3, label: <?= json_encode(__("rejection_reason", "Rejection reason")) ?>, req: true, ph: <?= json_encode(__("reason_required", "Please provide a reason...")) ?>,
                        msg: <?= json_encode(__("rejection_reason_required", "Rejection reason is required")) ?> }).replace('sr-fcol c-12', 'sr-fcol c-12 js-reject')
                ) +
            '</form>';

            F.open({
                title: <?= json_encode(__("approve_rejoin_request", "Approve rejoin request")) ?>,
                html: html,
                width: '760px',
                icon: 'mdi-check',
                confirm: <?= json_encode(__("submit_rejoin", "Submit")) ?>,
                didOpen: function() {
                    const $form = $('#rjForm');
                    F.liveClear($form);
                    const opts = { format: 'yyyy-mm-dd', todayHighlight: true, autoclose: true };
                    if (!window.isRejoinElevated) { opts.startDate = reqDate; opts.endDate = toDate; }
                    F.datepicker($('#adjustmentDate'), opts);
                    function sync() {
                        const a = $form.find('input[name="action"]:checked').val();
                        $form.find('.js-approve').toggle(a === 'approve');
                        $form.find('.js-adjust').toggle(a === 'adjust');
                        $form.find('.js-reject').toggle(a === 'reject');
                        $('#adjustmentDate').attr('data-required', a === 'adjust' ? '1' : null);
                        $('#rejectionReason').attr('data-required', a === 'reject' ? '1' : null);
                        $('.swal2-confirm').css('background-color', a === 'reject' ? '#dc2626' : '');
                    }
                    $form.on('change', 'input[name="action"]', sync);
                    sync();
                },
                preConfirm: function() {
                    const $form = $('#rjForm');
                    const msg = F.validate($form);
                    if (msg) { Swal.showValidationMessage(msg); return false; }
                    return {
                        action: $form.find('input[name="action"]:checked').val(),
                        approval_note: $('#approvalNote').val(),
                        adjustment_date: $('#adjustmentDate').val(),
                        adjustment_note: $('#adjustmentNote').val(),
                        rejection_reason: $('#rejectionReason').val()
                    };
                }
            }).then(function(result) {
                if (result.isConfirmed && result.value) processRejoinApproval(rejoinRequestId, result.value);
            });
        }

        function cancelRejoinRequestAdmin(rejoinRequestId, empName) {
            const html = '<form id="rjCancelForm" class="sr-page sr-form text-left" novalidate>' +
                `<div class="sr-notice tone-amber" style="margin-bottom:12px;"><i class="mdi mdi-alert"></i><div>${esc(<?= json_encode(__('cancel_rejoin_confirm', 'Cancel the rejoin request for')) ?>)} <strong>${esc(empName)}</strong>?</div></div>` +
                F.section('mdi-comment-text', <?= json_encode(__('cancellation_reason', 'Cancellation reason')) ?>,
                    F.field({ col: 12, name: 'note', id: 'rjCancelNote', type: 'textarea', rows: 3, req: true, ph: <?= json_encode(__('enter_cancellation_reason', 'Enter reason for cancelling this request')) ?>,
                        msg: <?= json_encode(__('cancellation_reason_required', 'Cancellation reason is required.')) ?> })) +
            '</form>';
            F.open({
                title: <?= json_encode(__('cancel_rejoin_request', 'Cancel rejoin request')) ?>,
                html: html,
                width: '560px',
                icon: 'mdi-cancel',
                confirm: <?= json_encode(__('yes_cancel', 'Yes, cancel')) ?>,
                confirmColor: '#dc2626',
                didOpen: () => F.liveClear($('#rjCancelForm')),
                preConfirm: function() {
                    const msg = F.validate($('#rjCancelForm'));
                    if (msg) { Swal.showValidationMessage(msg); return false; }
                    return F.post('./includes/ajaxFile/leaveHandler.php', { ajaxType: 'cancelRejoinRequestAdmin', rejoin_request_id: rejoinRequestId, cancellation_note: $('#rjCancelNote').val() });
                }
            }).then(function(r) { F.done(r, loadRejoinRequests); });
        }

        function processRejoinApproval(rejoinRequestId, data) {
            Swal.fire({
                title: <?= json_encode(__('processing', 'Processing...')) ?>,
                html: <?= json_encode(__('processing_rejoin_and_emails', 'Processing rejoin approval and sending notification emails...')) ?>,
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => Swal.showLoading()
            });
            $.ajax({
                url: './includes/ajaxFile/leaveHandler.php',
                type: 'POST',
                dataType: 'JSON',
                data: {
                    ajaxType: 'processRejoinApproval',
                    rejoin_request_id: rejoinRequestId,
                    action: data.action,
                    approval_note: data.approval_note,
                    adjustment_date: data.adjustment_date,
                    adjustment_note: data.adjustment_note,
                    rejection_reason: data.rejection_reason
                }
            }).done(function(response) {
                Swal.fire({
                    icon: response.type === 'success' ? 'success' : 'error',
                    title: response.title || response.type,
                    text: response.message,
                    confirmButtonText: <?= json_encode(__("ok", "OK")) ?>,
                    allowOutsideClick: false
                }).then(() => { if (response.type === 'success') loadRejoinRequests(); });
            }).fail(function() {
                Swal.fire({ icon: 'error', title: <?= json_encode(__("error", "Error")) ?>, text: <?= json_encode(__("request_failed", "Request failed")) ?>, allowOutsideClick: false });
            });
        }
    </script>
</body>
</html>
