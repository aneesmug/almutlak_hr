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

// "All Requests" card (every employee, any status) - read-only reference view,
// granted per user via App Settings > Special Access > 'view_all_rejoin_requests'.
$can_view_all_rejoin_requests = user_has_special_access($conDB, $supervisor_emp_id ?? '', 'view_all_rejoin_requests', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);

// "Add Manual Rejoin" button - records a rejoin for a vacation that has no rejoin history,
// granted per user via App Settings > Special Access > 'add_manual_rejoin_record'.
$can_add_manual_rejoin = user_has_special_access($conDB, $supervisor_emp_id ?? '', 'add_manual_rejoin_record', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);

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
    <?php if ($can_add_manual_rejoin): ?>
    <link href="./plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
    <?php endif; ?>
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
        /* Status tiles - icon chip, label + hint, count; each tile carries its own tone */
        .sr-page .sr-tiles.rj-tiles { grid-template-columns: repeat(var(--rj-cols, 4), minmax(0, 1fr)); gap: 12px; }
        @media (max-width: 1199px) { .sr-page .sr-tiles.rj-tiles { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        @media (max-width: 767px) { .sr-page .sr-tiles.rj-tiles { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 420px) { .sr-page .sr-tiles.rj-tiles { grid-template-columns: 1fr; } }
        .rj-tile { --rj-fg: var(--tone-slate-fg); --rj-bg: var(--tone-slate-bg); --rj-bd: var(--tone-slate-bd);
            display: flex; align-items: center; gap: 12px; padding-block: 14px; padding-inline: 18px 16px; overflow: hidden; }
        .rj-tile.t-indigo { --rj-fg: var(--tone-indigo-fg); --rj-bg: var(--tone-indigo-bg); --rj-bd: var(--tone-indigo-bd); }
        .rj-tile.t-red    { --rj-fg: var(--tone-red-fg);    --rj-bg: var(--tone-red-bg);    --rj-bd: var(--tone-red-bd); }
        .rj-tile.t-amber  { --rj-fg: var(--tone-amber-fg);  --rj-bg: var(--tone-amber-bg);  --rj-bd: var(--tone-amber-bd); }
        .rj-tile.t-green  { --rj-fg: var(--tone-green-fg);  --rj-bg: var(--tone-green-bg);  --rj-bd: var(--tone-green-bd); }
        .rj-tile::before { content: ''; position: absolute; inset-block: 10px; inset-inline-start: 0; width: 3px;
            border-radius: 3px; background: var(--rj-fg); opacity: 0; transition: opacity .15s; }
        .rj-tile-icon { position: relative; flex: 0 0 auto; display: inline-flex; align-items: center; justify-content: center;
            width: 40px; height: 40px; border-radius: 11px; background: var(--rj-bg); color: var(--rj-fg); font-size: 20px; }
        .rj-tile-body { flex: 1 1 auto; min-width: 0; }
        .rj-tile-label { display: block; font-size: 13px; font-weight: 700; color: var(--sr-text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .rj-tile-hint { display: block; margin-top: 2px; font-size: 11.5px; color: var(--sr-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .rj-tile .sr-tile-value { flex: 0 0 auto; margin: 0; font-size: 26px; font-weight: 800; letter-spacing: -.5px; }
        .rj-tile:hover { border-color: var(--rj-bd); }
        .rj-tile.active { background: var(--rj-bg); border-color: var(--rj-bd); box-shadow: 0 0 0 1px var(--rj-bd) inset, var(--sr-shadow); }
        .rj-tile.active::after { display: none; }
        .rj-tile.active::before { opacity: 1; }
        .rj-tile.active .rj-tile-icon { background: var(--rj-fg); color: var(--sr-surface); }
        .rj-tile.active .sr-tile-value { color: var(--rj-fg); }
        /* Missing Rejoin with open items: count in tone + pulsing marker on the icon */
        .rj-tile.rj-alert .sr-tile-value { color: var(--rj-fg); }
        .rj-tile.rj-alert .rj-tile-icon::after { content: ''; position: absolute; top: -3px; inset-inline-end: -3px; width: 10px; height: 10px;
            border-radius: 50%; background: var(--rj-fg); border: 2px solid var(--sr-surface); animation: rj-pulse 1.6s ease-out infinite; }
        @keyframes rj-pulse { 0% { box-shadow: 0 0 0 0 var(--rj-bd); } 100% { box-shadow: 0 0 0 7px transparent; } }
        @media (prefers-reduced-motion: reduce) { .rj-tile.rj-alert .rj-tile-icon::after { animation: none; } }
        .rj-pane { display: none; }
        .rj-pane.active { display: block; }
        .rj-reason { max-width: 280px; white-space: normal; font-size: 12.5px; color: var(--sr-text-2); }
        .rj-diff { font-size: 11px; font-weight: 700; }
        .sr-toolbar .sr-search.rj-range { flex: 0 1 280px; }
        .mrj-vinfo { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px 16px; margin-top: 10px; padding: 12px 14px;
            border: 1px solid var(--sr-border); border-radius: 10px; background: var(--sr-surface-2, rgba(99, 102, 241, .04)); }
        .mrj-vinfo:empty { display: none; }
        .mrj-vinfo .k { display: block; font-size: 11px; color: var(--sr-muted); text-transform: uppercase; letter-spacing: .3px; }
        .mrj-vinfo .v { display: block; font-size: 13px; font-weight: 600; color: var(--sr-text); word-break: break-word; }
        .mrj-vinfo .wide { grid-column: 1 / -1; }
        @media (max-width: 575px) { .mrj-vinfo { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    </style>
    <script> window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;</script>
    <script> window.isRejoinElevated = <?= $is_rejoin_elevated ? 'true' : 'false' ?>;</script>
    <script> window.canViewAllRejoinRequests = <?= $can_view_all_rejoin_requests ? 'true' : 'false' ?>;</script>
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
                        <?php if ($can_add_manual_rejoin): ?>
                        <div class="sr-head-actions">
                            <button type="button" class="sr-btn sr-btn-primary" id="btnManualRejoin"><i class="mdi mdi-plus"></i> <?= __('add_manual_rejoin', 'Add manual rejoin') ?></button>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php
                    // [pane, tone, icon, label, hint, active] - pane id doubles as the count element prefix
                    $rj_tiles = [];
                    if ($can_view_all_rejoin_requests) {
                        $rj_tiles[] = ['all', 't-indigo', 'mdi-format-list-bulleted', __('all_requests', 'All Requests'), __('all_requests_hint', 'Every employee, any status'), false];
                    }
                    $rj_tiles[] = ['missing', 't-red', 'mdi-clock-alert', __('missing_rejoin_requests', 'Missing Rejoin'), __('missing_rejoin_short_hint', 'Return due, no request yet'), false];
                    $rj_tiles[] = ['pending', 't-amber', 'mdi-timer-sand', __('pending_requests', 'Pending'), __('pending_rejoin_hint', 'Waiting for approval'), true];
                    $rj_tiles[] = ['approved', 't-green', 'mdi-check-circle-outline', __('approved_requests', 'Approved'), __('approved_rejoin_hint', 'Rejoin confirmed'), false];
                    $rj_tiles[] = ['rejected', 't-slate', 'mdi-close-circle-outline', __('rejected_requests', 'Rejected'), __('rejected_rejoin_hint', 'Not accepted'), false];
                    ?>
                    <div class="sr-tiles rj-tiles" id="rjTiles" style="--rj-cols: <?= count($rj_tiles) ?>;">
                        <?php foreach ($rj_tiles as [$pane, $tone, $icon, $label, $hint, $active]): ?>
                        <button type="button" class="sr-tile rj-tile <?= $tone ?><?= $active ? ' active' : '' ?>" data-pane="<?= $pane ?>" title="<?= htmlspecialchars($hint, ENT_QUOTES) ?>">
                            <span class="rj-tile-icon"><i class="mdi <?= $icon ?>"></i></span>
                            <span class="rj-tile-body">
                                <span class="rj-tile-label"><?= htmlspecialchars($label) ?></span>
                                <span class="rj-tile-hint"><?= htmlspecialchars($hint) ?></span>
                            </span>
                            <span class="sr-tile-value" id="<?= $pane ?>-count">&ndash;</span>
                        </button>
                        <?php endforeach; ?>
                    </div>

                    <div class="sr-card">
                        <div class="sr-toolbar">
                            <div class="sr-search">
                                <i class="mdi mdi-magnify"></i>
                                <input type="search" id="rjSearch" placeholder="<?= __('search') ?>..." autocomplete="off" aria-label="<?= __('search') ?>">
                            </div>
                            <div class="sr-toolbar-right">
                                <div class="sr-search rj-range" title="<?= __('rejoin_date_range_hint', 'Filter by rejoin date (approved date, else requested date)') ?>">
                                    <i class="mdi mdi-calendar-range"></i>
                                    <input type="text" id="rjDateRange" placeholder="<?= __('rejoin_date_range', 'Rejoin date range') ?>" autocomplete="off" readonly aria-label="<?= __('rejoin_date_range', 'Rejoin date range') ?>">
                                </div>
                                <button type="button" class="sr-btn" id="rjDateClear" style="display:none;"><i class="mdi mdi-close"></i> <?= __('clear', 'Clear') ?></button>
                            </div>
                        </div>

                        <?php if ($can_view_all_rejoin_requests): ?>
                        <div class="rj-pane" id="all">
                            <div class="sr-table-wrap">
                                <table id="allRequestsTable" class="table sr-table dt-responsive nowrap" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th><?= __('employee_name', 'Employee') ?></th>
                                            <th>ID</th>
                                            <th><?= __('planned_return_date', 'Planned return') ?></th>
                                            <th><?= __('requested_rejoin_date', 'Requested rejoin') ?></th>
                                            <th><?= __('final_rejoin_date', 'Final rejoin') ?></th>
                                            <th><?= __('status', 'Status') ?></th>
                                            <th><?= __('submitted_date', 'Submitted') ?></th>
                                            <th><?= __('action_date', 'Action date') ?></th>
                                            <th><?= __('action_by', 'Action by') ?></th>
                                            <th><?= __('note', 'Note') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="rj-pane" id="missing">
                            <div class="sr-notice tone-indigo" style="margin: 0 0 12px;">
                                <i class="mdi mdi-information-outline"></i>
                                <div><?= __('missing_rejoin_notice', 'Employees whose vacation return date has passed or is within the next 7 days, but no rejoin request has been submitted. Ask the employee or the direct manager to submit it.') ?></div>
                            </div>
                            <div class="sr-table-wrap">
                                <table id="missingRequestsTable" class="table sr-table dt-responsive nowrap" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th><?= __('employee_name', 'Employee') ?></th>
                                            <th>ID</th>
                                            <th><?= __('vacation_type', 'Vacation type') ?></th>
                                            <th><?= __('start_date', 'Start date') ?></th>
                                            <th><?= __('return_date', 'Return date') ?></th>
                                            <th><?= __('status', 'Status') ?></th>
                                            <th><?= __('direct_manager', 'Direct manager') ?></th>
                                            <th><?= __('last_rejoin_request', 'Last rejoin request') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
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
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/metisMenu.min.js"></script>
    <script src="assets/js/waves.js"></script>
    <script src="assets/js/jquery.slimscroll.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="./plugins/datatables/jquery.dataTables.min.js"></script>
    <script src="./plugins/datatables/dataTables.bootstrap4.min.js"></script>
    <script src="./plugins/datatables/dataTables.responsive.min.js"></script>
    <script src="./plugins/datatables/responsive.bootstrap4.min.js"></script>
    <?php if ($can_add_manual_rejoin): ?>
    <script src="./plugins/select2/js/select2.min.js"></script>
    <?php endif; ?>
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
            onTime: <?= json_encode(__('on_time', 'On time')) ?>,
            overdue: <?= json_encode(__('overdue_days', 'Overdue %d d')) ?>,
            dueToday: <?= json_encode(__('due_today', 'Due today')) ?>,
            dueIn: <?= json_encode(__('due_in_days', 'Due in %d d')) ?>,
            noRequest: <?= json_encode(__('no_request_yet', 'None')) ?>,
            rejoinBlocked: <?= json_encode(__('rejoin_request_blocked', 'Rejoin requests blocked')) ?>,
            status: {
                pending: <?= json_encode(__('pending', 'Pending')) ?>,
                approved: <?= json_encode(__('approved', 'Approved')) ?>,
                adjusted: <?= json_encode(__('adjusted', 'Adjusted')) ?>,
                rejected: <?= json_encode(__('rejected', 'Rejected')) ?>,
                cancelled: <?= json_encode(__('cancelled', 'Cancelled')) ?>
            }
        };
        const STATUS_TONE = { pending: 'tone-amber', approved: 'tone-green', adjusted: 'tone-sky', rejected: 'tone-red', cancelled: 'tone-slate' };
        let pendingTable, approvedTable, rejectedTable, allTable;
        let missingTable;
        const allTables = () => [allTable, missingTable, pendingTable, approvedTable, rejectedTable].filter(Boolean);

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
        // Rejoin date range filter (project AppDate picker); empty = no filter
        function dateRange() {
            return ($('#rjDateRange').val() && window.AppDate) ? AppDate.rangeValues('#rjDateRange') : ['', ''];
        }
        function makeTable(sel, status, columns, order) {
            return $(sel).DataTable({
                dom: 'rtip', processing: true, serverSide: true, responsive: true, pageLength: 10, order: order,
                ajax: {
                    url: './includes/get_rejoin_requests.php',
                    type: 'POST',
                    data: d => {
                        d.status = status;
                        const [from, to] = dateRange();
                        if (from && to) { d.date_from = from; d.date_to = to; }
                    },
                    dataSrc: json => {
                        const n = Number((json && json.recordsTotal) || 0);
                        $('#' + status + '-count').text(n);
                        if (status === 'missing') $('#missing-count').closest('.rj-tile').toggleClass('rj-alert', n > 0);
                        return json.data || [];
                    }
                },
                columns: columns,
                language: language
            });
        }

        function loadRejoinRequests() {
            allTables().forEach(t => t.ajax.reload(null, false));
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

            // Column order must match $sort_columns in includes/get_rejoin_requests.php (status=missing)
            missingTable = makeTable('#missingRequestsTable', 'missing', [
                { data: 'emp_name', render: (d, t, r) => person(d, r.emp_id) },
                { data: 'emp_id', visible: false },
                { data: 'fly_type', render: (d, t, r) => `<span class="sr-chip"><i class="mdi mdi-airplane"></i>${esc(String(d || r.vac_type || '').replace(/^./, c => c.toUpperCase()))}</span>` },
                { data: 'start_date', render: fmtDate },
                { data: 'return_date', render: fmtDate },
                { data: 'days_overdue', render: function(d, t, r) {
                    const n = parseInt(d, 10) || 0;
                    const blocked = r.rejoin_blocked ? `<br><small class="text-danger"><i class="mdi mdi-block-helper"></i> ${esc(T.rejoinBlocked)}</small>` : '';
                    if (n > 0) return `<span class="sr-pill sr-pill-xs tone-red"><span class="sr-dot"></span>${esc(T.overdue.replace('%d', n))}</span>${blocked}`;
                    if (n === 0) return `<span class="sr-pill sr-pill-xs tone-amber"><span class="sr-dot"></span>${esc(T.dueToday)}</span>${blocked}`;
                    return `<span class="sr-pill sr-pill-xs tone-sky"><span class="sr-dot"></span>${esc(T.dueIn.replace('%d', -n))}</span>${blocked}`;
                } },
                { data: 'supervisor_name', render: (d, t, r) => d ? person(d, r.supervisor_id) : '<span class="text-muted">&ndash;</span>' },
                { data: 'last_rejoin_status', render: d => d
                    ? `<span class="sr-pill sr-pill-xs ${STATUS_TONE[String(d).toLowerCase()] || 'tone-slate'}"><span class="sr-dot"></span>${esc(T.status[String(d).toLowerCase()] || d)}</span>`
                    : `<span class="text-muted">${esc(T.noRequest)}</span>` }
            ], [[4, 'asc']]);

            if (window.canViewAllRejoinRequests) {
                // Column order must match $sort_columns in includes/get_rejoin_requests.php (status=all)
                allTable = makeTable('#allRequestsTable', 'all', [
                    { data: 'emp_name', render: (d, t, r) => person(d, r.emp_id) },
                    { data: 'emp_id', visible: false },
                    { data: 'return_date', render: fmtDate },
                    { data: 'requested_rejoin_date', render: fmtDate },
                    { data: 'final_approved_date', render: fmtDate },
                    { data: 'status', render: function(d) {
                        const k = String(d || '').toLowerCase();
                        return `<span class="sr-pill sr-pill-xs ${STATUS_TONE[k] || 'tone-slate'}"><span class="sr-dot"></span>${esc(T.status[k] || d || '-')}</span>`;
                    } },
                    { data: 'requested_at', render: fmtDate },
                    { data: 'approved_at', render: fmtDate },
                    { data: 'approved_by_name', render: (d, t, r) => d ? esc(d) : (r.approved_by_emp_id ? `<span class="sr-mono">${esc(r.approved_by_emp_id)}</span>` : '<span class="text-muted">&ndash;</span>') },
                    { data: null, orderable: false, render: (d, t, r) => longText(r.status === 'rejected' ? r.rejection_reason : (r.approval_note || r.requested_reason)) }
                ], [[6, 'desc']]);
            }

            let timer;
            $('#rjSearch').on('input', function() {
                clearTimeout(timer);
                const v = this.value;
                timer = setTimeout(() => allTables().forEach(t => t.search(v).draw()), 350);
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
            $('#btnManualRejoin').on('click', addManualRejoin);

            let lastRange = '';
            function applyRange() {
                const key = dateRange().join('|');
                $('#rjDateClear').toggle(!!$('#rjDateRange').val());
                if (key === lastRange) return;
                lastRange = key;
                allTables().forEach(t => t.ajax.reload()); // back to page 1 for the new range
            }
            if (window.AppDate) {
                AppDate.range('#rjDateRange', {
                    onClose: function(dates) { if (dates.length === 2) applyRange(); }
                });
            }
            $('#rjDateClear').on('click', function() {
                const fp = window.AppDate && AppDate.get('#rjDateRange');
                if (fp) fp.clear(); else $('#rjDateRange').val('');
                applyRange();
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
                    // Non-elevated users: days outside the leave window are blocked.
                    const opts = {};
                    if (!window.isRejoinElevated) { opts.minDate = reqDate; opts.maxDate = toDate; }
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

        // Manual rejoin record for a Fly vacation that has no rejoin history (special access 'add_manual_rejoin_record')
        function addManualRejoin() {
            const H = './includes/ajaxFile/leaveHandler.php';
            const MT = {
                selectEmpFirst: <?= json_encode(__('select_employee_first', 'Select an employee first')) ?>,
                searchEmp: <?= json_encode(__('search_employee', 'Search employee by name or ID')) ?>,
                loading: <?= json_encode(__('loading', 'Loading...')) ?>,
                selectVac: <?= json_encode(__('select_vacation', 'Select vacation')) ?>,
                noVac: <?= json_encode(__('no_vacations_without_rejoin', 'No vacations without rejoin record')) ?>,
                open: <?= json_encode(__('vacation_open_away', 'Open (employee still away)')) ?>,
                openShort: <?= json_encode(__('open', 'Open')) ?>,
                closed: <?= json_encode(__('closed', 'Closed')) ?>,
                planned: <?= json_encode(__('planned_return_date', 'Planned return')) ?>,
                requestNo: <?= json_encode(__('request_no', 'Request no.')) ?>,
                type: <?= json_encode(__('type', 'Type')) ?>,
                status: <?= json_encode(__('status', 'Status')) ?>,
                start: <?= json_encode(__('start_date', 'Start date')) ?>,
                days: <?= json_encode(__('days', 'Days')) ?>,
                settlement: <?= json_encode(__('settlement', 'Settlement')) ?>,
                paidOn: <?= json_encode(__('paid', 'Paid')) ?>,
                failed: <?= json_encode(__('request_failed', 'Request failed')) ?>
            };
            const html = '<form id="mrjForm" class="sr-page sr-form text-left" autocomplete="off" novalidate>' +
                F.section('mdi-account', <?= json_encode(__('employee', 'Employee')) ?>,
                    F.field({ col: 12, name: 'emp_id', id: 'mrjEmp', label: <?= json_encode(__('employee', 'Employee')) ?>, req: true,
                        html: F.select({ name: 'emp_id', id: 'mrjEmp', req: true, placeholder: MT.searchEmp,
                            msg: <?= json_encode(__('please_select_employee', 'Please select an employee')) ?> }),
                        hint: esc(<?= json_encode(__('manual_rejoin_emp_hint', 'Only employees with a Fly vacation that has no rejoin record are listed.')) ?>) })
                ) +
                F.section('mdi-airplane', <?= json_encode(__('vacation', 'Vacation')) ?>,
                    F.field({ col: 12, name: 'vacation_id', id: 'mrjVac', label: <?= json_encode(__('applied_vacations', 'Applied vacations')) ?>, req: true,
                        html: F.select({ name: 'vacation_id', id: 'mrjVac', req: true, placeholder: MT.selectEmpFirst,
                            msg: <?= json_encode(__('please_select_vacation', 'Please select a vacation')) ?> }) + '<div class="mrj-vinfo" id="mrjVacInfo"></div>' }) +
                    F.field({ col: 5, name: 'rejoin_date', id: 'mrjDate', label: <?= json_encode(__('rejoin_date', 'Rejoin date')) ?>, req: true, ph: 'YYYY-MM-DD', attrs: ' readonly',
                        msg: <?= json_encode(__('rejoin_date_required', 'Please select the rejoin date')) ?> }) +
                    F.field({ col: 7, name: 'details', id: 'mrjDetails', type: 'textarea', rows: 3, label: <?= json_encode(__('manual_rejoin_details', 'Why is this rejoin added manually?')) ?>, req: true,
                        ph: <?= json_encode(__('manual_rejoin_details_ph', 'e.g. Employee returned before rejoin requests existed in the system')) ?>,
                        msg: <?= json_encode(__('manual_rejoin_details_required', 'Please write the details')) ?> })
                ) +
            '</form>';

            F.open({
                title: <?= json_encode(__('add_manual_rejoin', 'Add manual rejoin')) ?>,
                html: html,
                width: '760px',
                icon: 'mdi-content-save',
                confirm: <?= json_encode(__('save', 'Save')) ?>,
                didOpen: function() {
                    const $form = $('#mrjForm'), $vac = $('#mrjVac'), $date = $('#mrjDate');
                    let vacs = {};
                    const opt = text => '<option value="">' + esc(text) + '</option>';
                    const day = d => {
                        const dt = d ? new Date(String(d) + 'T00:00:00') : null;
                        return dt && !isNaN(dt) ? dt.toLocaleDateString(LOCALE, { day: '2-digit', month: 'short', year: 'numeric' }) : '-';
                    };
                    const cap = t => String(t || '').replace(/^./, c => c.toUpperCase());
                    function showVacInfo(v) {
                        if (!v) { $('#mrjVacInfo').empty(); return; }
                        const isOpen = v.review === 'A';
                        const item = (k, val, cls) => `<div${cls ? ` class="${cls}"` : ''}><span class="k">${esc(k)}</span><span class="v">${val}</span></div>`;
                        $('#mrjVacInfo').html(
                            item(MT.requestNo, `<span class="sr-mono">${esc(v.request_inv_no || ('#' + v.id))}</span>`, 'wide') +
                            item(MT.type, esc(cap(v.vac_type) + ' · ' + cap(v.fly_type))) +
                            item(MT.status, `<span class="sr-pill sr-pill-xs ${isOpen ? 'tone-amber' : 'tone-green'}"><span class="sr-dot"></span>${esc(isOpen ? MT.open : MT.closed)}</span>`) +
                            item(MT.days, esc(v.vacdays || 0)) +
                            item(MT.start, esc(day(v.start_date))) +
                            item(MT.planned, esc(day(v.return_date))) +
                            item(MT.settlement, v.settlement_paid_on ? `<span class="sr-pill sr-pill-xs tone-green">${esc(MT.paidOn + ' ' + day(v.settlement_paid_on))}</span>` : '<span class="text-muted">&ndash;</span>')
                        );
                    }
                    F.liveClear($form);
                    F.select2($('#mrjEmp'), {
                        placeholder: MT.searchEmp,
                        ajax: {
                            url: H, type: 'POST', dataType: 'json', delay: 250,
                            data: p => ({ ajaxType: 'searchManualRejoinEmployees', q: p.term || '' }),
                            processResults: d => ({ results: (d && d.results) || [] })
                        }
                    });
                    // Rejoin date can't be before the selected vacation's start date
                    function resetDate(minDate) {
                        if (window.AppDate) AppDate.destroy($date[0]);
                        $date.val('');
                        F.datepicker($date, minDate ? { minDate: minDate } : {});
                    }
                    resetDate();
                    $('#mrjEmp').on('change', function() {
                        const emp = $(this).val();
                        vacs = {};
                        showVacInfo(null);
                        resetDate();
                        if (!emp) { $vac.html(opt(MT.selectEmpFirst)); return; }
                        $vac.html(opt(MT.loading));
                        $.post(H, { ajaxType: 'getManualRejoinVacations', emp_id: emp }, null, 'json').done(function(r) {
                            const list = (r && r.vacations) || [];
                            let opts = opt(list.length ? MT.selectVac : MT.noVac);
                            list.forEach(v => {
                                vacs[v.id] = v;
                                // Short label - full details show in #mrjVacInfo once picked
                                const flag = v.review === 'A' ? ' · ' + MT.openShort : '';
                                opts += `<option value="${esc(v.id)}">${esc(cap(v.fly_type || v.vac_type) + ' · ' + day(v.start_date) + ' – ' + day(v.return_date) + flag)}</option>`;
                            });
                            $vac.html(opts);
                        }).fail(function(x) {
                            $vac.html(opt((x.responseJSON && x.responseJSON.message) || MT.failed));
                        });
                    });
                    $vac.on('change', function() {
                        const v = vacs[$(this).val()];
                        resetDate(v && v.start_date ? v.start_date : null);
                        showVacInfo(v);
                    });
                },
                preConfirm: function() {
                    const msg = F.validate($('#mrjForm'));
                    if (msg) { Swal.showValidationMessage(msg); return false; }
                    return F.post(H, {
                        ajaxType: 'addManualRejoin',
                        emp_id: $('#mrjEmp').val(),
                        vacation_id: $('#mrjVac').val(),
                        rejoin_date: $('#mrjDate').val(),
                        details: $('#mrjDetails').val()
                    });
                }
            }).then(function(r) { F.done(r, loadRejoinRequests); });
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
