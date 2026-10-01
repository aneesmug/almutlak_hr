<?php
// Smart Requests list. Server-side DataTable fed by includes/ajaxFile/smartRequestAjaxTbl.php,
// which applies the role-based visibility rules and also returns per-status counts for the
// summary tiles (withCounts=1). Styling: assets/css/smart_request.css (shared with open_request.php).

 require_once __DIR__ . '/includes/db.php';
 require_once __DIR__ . '/includes/session_check.php';
 require_once __DIR__ . '/includes/special_access_helper.php';

 // Smart requests are globally blocked - hide this page entirely (matches sidebar link hiding).
 if (is_employee_request_blocked($conDB, $empid ?? '', 'smart_request')['blocked']) {
     header('Location: dashboard.php');
     exit;
 }
 $query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
 if(mysqli_num_rows($query) == 1){
 include("./includes/avatar_select.php");

    // Detect if current user is an assigned payer with pending payments
    $is_payer_with_pending = false;
    if (!empty($empid)) {
        $empid_safe = (int)$empid;
        $sql_payer_pending = "SELECT COUNT(*) AS cnt FROM `smart_request` WHERE `current_status`='pending_payment' AND `payable_by_emp_id` = {$empid_safe}";
        if ($res_pp = mysqli_query($conDB, $sql_payer_pending)) {
            $row_pp = mysqli_fetch_assoc($res_pp);
            $is_payer_with_pending = ((int)($row_pp['cnt'] ?? 0) > 0);
            mysqli_free_result($res_pp);
        }
    }

    // Summary tiles: status value => [label, dot colour class]. '' = all statuses.
    $status_tiles = [
        ''                 => [__('all_statuses_option'), 'dot-all'],
        'draft'            => [__('draft_status'), 'dot-slate'],
        'pending_approval' => [__('pending_approval'), 'dot-amber'],
        'approved'         => [__('approved'), 'dot-indigo'],
        'pending_payment'  => [__('ready_for_payment', 'Ready for Payment'), 'dot-sky'],
        'paid'             => [__('paid_status'), 'dot-green'],
        'rejected'         => [__('rejected'), 'dot-red'],
        'cancelled'        => [__('cancelled', 'Cancelled'), 'dot-slate'],
    ];
    // Notification links open the list on a given status (e.g. ?status=pending_approval).
    $requested_status = (isset($_GET['status']) && array_key_exists((string)$_GET['status'], $status_tiles)) ? (string)$_GET['status'] : null;

    // Department name shown in the New Request modal
    $new_req_dept_name = '';
    $dept_res = mysqli_query($conDB, "SELECT `dep_nme` FROM `department` WHERE `id` = " . (int)$user_dept . " LIMIT 1");
    if ($dept_res && ($dept_row = mysqli_fetch_assoc($dept_res))) {
        $new_req_dept_name = $dept_row['dep_nme'];
    }

?>
<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

    <head>
        <meta charset="utf-8" />
        <title><?= $site_title ?> - <?=__('all_requests_title')?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta content="Anees Afzal" name="author" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />

        <!-- App favicon -->
        <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">

        <!-- DataTables -->
        <link href="./plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
        <link href="./plugins/datatables/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" />
        <link href="./plugins/datatables/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />

        <!-- App css -->
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
		<script> window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;</script>

    </head>
    <body class="enlarged" data-keep-enlarged="true">

        <!-- Begin page -->
        <div id="wrapper">

            <!-- ========== Left Sidebar Start ========== -->
            <div class="left side-menu">
                <div class="slimscroll-menu" id="remove-scroll">
                    <!-- LOGO -->
                    <div class="topbar-left">
                        <a href="dashboard.php" class="logo">
                            <span><img src="<?=get_setting($conDB, 'logo')?>" alt="" height="22"></span>
                            <i><img src="<?=get_setting($conDB, 'white_logo')?>" alt="" height="28"></i>
                        </a>
                    </div>
                    <!--- Sidemenu -->
                    <?php include("./includes/main_menu.php"); ?>
                    <div class="clearfix"></div>
                </div>
            </div>
            <!-- Left Sidebar End -->

            <div class="content-page">

                <!-- Top Bar Start -->
                <?php include("./includes/topbar.php"); ?>
                <!-- Top Bar End -->

                <!-- Start Page content -->
                <div class="content sr-page">
                    <div class="container-fluid">

                        <div class="sr-head">
                            <div>
                                <h1><?=__('all_smart_requests_header')?></h1>
                                <p><?= __('smart_requests_subtitle', 'Track purchase and payment requests through approval and payment.') ?></p>
                            </div>
                            <div class="sr-head-actions">
                                <?php if ($user_type == "administrator"): ?>
                                    <a href="all_smt_req_status.php" class="sr-btn"><i class="mdi mdi-atom"></i> <?=__('all_status_logs_button')?></a>
                                <?php endif; ?>
                                <?php if ($user_type <> "gm"): ?>
                                    <button type="button" class="sr-btn sr-btn-primary newRequestBtn"><i class="fa fa-plus"></i> <?=__('new_request_button')?></button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if (isset($_GET['error']) && $_GET['error'] === 'request_not_found'): ?>
                            <div class="sr-notice tone-red" role="alert">
                                <i class="mdi mdi-alert-circle-outline"></i>
                                <div><?= __('error_request_not_found', 'The requested item was not found or the link is invalid.') ?></div>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($_GET['action']) && $_GET['action'] === 'success'): ?>
                            <div class="sr-notice tone-green" role="status">
                                <i class="mdi mdi-check-circle-outline"></i>
                                <div><?= __('request_action_saved', 'Your action on the request was saved successfully.') ?></div>
                            </div>
                        <?php endif; ?>

                        <!-- Status summary tiles (also act as the status filter) -->
                        <div class="sr-tiles" id="srTiles" role="tablist">
                            <?php foreach ($status_tiles as $tile_status => $tile): ?>
                                <button type="button" class="sr-tile<?= $tile_status === '' ? ' active' : '' ?>" data-status="<?= $tile_status ?>" role="tab">
                                    <span class="sr-tile-label"><span class="sr-dot <?= $tile[1] ?>"></span><?= htmlspecialchars($tile[0]) ?></span>
                                    <span class="sr-tile-value" data-count="<?= $tile_status === '' ? 'all' : $tile_status ?>">&ndash;</span>
                                </button>
                            <?php endforeach; ?>
                        </div>

                        <div class="sr-card">
                            <div class="sr-toolbar">
                                <div class="sr-search">
                                    <i class="mdi mdi-magnify"></i>
                                    <input type="search" name="search" placeholder="<?=__('search_placeholder')?>" id="search" autocomplete="off" aria-label="<?=__('search')?>">
                                </div>
                                <div class="sr-toolbar-right">
                                    <span class="sr-active-filter" id="srActiveFilter"></span>
                                    <div id="srExportButtons"></div>
                                </div>
                                <!-- Kept as the single source of the status filter; the tiles drive it. -->
                                <select class="d-none" name="smt_status" id="smtStatus" aria-hidden="true" tabindex="-1">
                                    <?php foreach ($status_tiles as $tile_status => $tile): ?>
                                        <option value="<?= $tile_status ?>"><?= htmlspecialchars($tile[0]) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="sr-table-wrap">
                                <table id="smartRequestTbl" class="table sr-table dt-responsive nowrap" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th><?=__('id')?></th>
                                            <th><?=__('invoice_no_header')?></th>
                                            <th><?=__('subject_title_header')?></th>
                                            <th><?=__('type')?></th>
                                            <th><?=__('department')?></th>
                                            <th><?=__('prepared_by_header')?></th>
                                            <th><?=__('created_at')?></th>
                                            <th class="text-right"><?=__('grand_total')?></th>
                                            <th><?=__('status')?></th>
                                            <th class="text-right"><?=__('action')?></th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>

                    </div> <!-- container -->
                </div> <!-- content -->

                <footer class="footer">
                    <?=$site_footer?>
                </footer>

            </div>
        </div>
        <!-- END wrapper -->

    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/metisMenu.min.js"></script>
    <script src="assets/js/waves.js"></script>
    <script src="assets/js/jquery.slimscroll.js"></script>

    <!-- Required datatable js -->
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

    <!-- App js -->
    <script src="assets/js/jquery.core.js"></script>
    <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>
    <?php include __DIR__ . '/includes/smart_request_lines_js.php'; ?>

    <script type="text/javascript">
    $(document).ready(function(){

        var exportTitle = <?= json_encode(__('all_requests')) ?>;
        var exportColumns = [ 1, 2, 3, 4, 5, 6, 7, 8 ];

        // status => [label, tone] (tone classes live in smart_request.css)
        var statusObj = {
            'draft':            { title: <?= json_encode(__('draft_status')) ?>, tone: 'tone-slate' },
            'pending_approval': { title: <?= json_encode(__('pending_approval')) ?>, tone: 'tone-amber' },
            'approved':         { title: <?= json_encode(__('approved')) ?>, tone: 'tone-indigo' },
            'pending_payment':  { title: <?= json_encode(__('ready_for_payment', 'Ready for Payment')) ?>, tone: 'tone-sky' },
            'rejected':         { title: <?= json_encode(__('rejected')) ?>, tone: 'tone-red' },
            'paid':             { title: <?= json_encode(__('paid_status')) ?>, tone: 'tone-green' },
            'cancelled':        { title: <?= json_encode(__('cancelled', 'Cancelled')) ?>, tone: 'tone-slate' },
            // Legacy statuses
            'pending_dept_manager_approval': { title: <?= json_encode(__('pending_dept_manager_status')) ?>, tone: 'tone-amber' },
            'pending_finance_approval':      { title: <?= json_encode(__('pending_finance_status')) ?>, tone: 'tone-amber' },
            'pending_gm_approval':           { title: <?= json_encode(__('pending_gm_status')) ?>, tone: 'tone-amber' }
        };

        var table = null;

        function esc(str) {
            return $('<div>').text(str == null ? '' : String(str)).html();
        }
        function initials(name) {
            var parts = String(name || '').trim().split(/\s+/).filter(Boolean);
            if (!parts.length) return '?';
            return ((parts[0][0] || '') + (parts.length > 1 ? parts[1][0] : '')).toUpperCase();
        }
        function shortName(name) {
            var parts = String(name || '').trim().split(/\s+/).filter(Boolean);
            return parts.slice(0, 2).join(' ');
        }
        function money(v) {
            var n = parseFloat(v) || 0;
            return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        var requestedStatus = <?= json_encode($requested_status) ?>;
        // Default filter by role.
        // Highest priority: assigned payer with pending payments sees those first.
        window.__isPayerPending = <?= $is_payer_with_pending ? 'true' : 'false' ?>;
        var defaultStatus = '';
        if (requestedStatus !== null) {
            defaultStatus = requestedStatus;
        }
        else if (window.__isPayerPending) {
            defaultStatus = 'pending_payment';
        }
        // Finance Manager (dept 2, manager) -> pending approval (for them)
        else if ('<?=$emptypeget?>' == "Manager" && '<?=$user_dept?>' == 2) {
            defaultStatus = 'pending_approval';
        }
        // Finance Assistant/Supporter (dept 2, not manager) -> approved (ready for payment)
        else if ('<?=$emptypeget?>' != "Manager" && '<?=$user_dept?>' == 2) {
            defaultStatus = 'approved';
        }
        // GM -> pending approval (for them)
        else if ('<?=$user_type?>' == "gm") {
            defaultStatus = 'pending_approval';
        }
        // Department Manager (manager, not dept 2) -> pending approval (for them)
        else if ('<?=$emptypeget?>' == "Manager" && '<?=$user_dept?>' != 2) {
            defaultStatus = 'pending_approval';
        }
        setStatus(defaultStatus, false);

        table = $('#smartRequestTbl').DataTable({
            dom: "Brtip",
            serverSide: true,
            processing: true,
            responsive: true,
            ordering: false, // server always lists newest first
            pageLength: 15,
            buttons: [
                { extend: 'excel', text: '<i class="mdi mdi-file-excel"></i> Excel', exportOptions: { columns: exportColumns, orthogonal: 'export' }, title: exportTitle },
                { extend: 'pdf',   text: '<i class="mdi mdi-file-pdf"></i> PDF',     exportOptions: { columns: exportColumns, orthogonal: 'export' }, title: exportTitle },
                { extend: 'print', text: '<i class="mdi mdi-printer"></i> ' + <?= json_encode(__('print')) ?>, exportOptions: { columns: exportColumns, orthogonal: 'export' }, title: exportTitle }
            ],
            ajax: {
                type: "POST",
                url: './includes/ajaxFile/smartRequestAjaxTbl.php',
                data: function (d) {
                    d.user_type = '<?=$user_type?>';
                    d.user_dept = '<?=$user_dept?>';
                    d.emptype   = '<?=$emptypeget?>';
                    d.emp_id    = '<?=$empid?>';
                    d.smtStatus = $('#smtStatus').val();
                    d.search    = $('#search').val();
                    // Restrict to my assigned payments when applicable
                    d.payerOnly = (window.__isPayerPending === true && $('#smtStatus').val() === 'pending_payment') ? 1 : 0;
                    d.isPayer   = window.__isPayerPending === true ? 1 : 0;
                    d.withCounts = 1;
                },
                dataSrc: function (json) {
                    var counts = json.counts || {};
                    $('[data-count]').each(function () {
                        var key = $(this).data('count');
                        $(this).text(counts[key] !== undefined ? counts[key] : '0');
                    });
                    return json.data;
                }
            },
            columns: [
                { data: 'id', visible: false, searchable: false },
                {
                    data: 'inv_no',
                    render: function (data, type) {
                        if (type !== 'display') return data;
                        return '<span class="sr-chip sr-mono">' + esc(data) + '</span>';
                    }
                },
                {
                    data: 'sub_title',
                    render: function (data, type, row) {
                        if (type !== 'display') return data;
                        var lines = parseInt(row.line_count, 10) || 0;
                        return '<span class="sr-cell-title" title="' + esc(data) + '">' + esc(data) + '</span>'
                            + '<span class="sr-cell-sub"><i class="mdi mdi-tag-outline"></i>' + esc(row.sub_type)
                            + ' &middot; ' + lines + ' ' + (lines === 1 ? <?= json_encode(__('item', 'item')) ?> : <?= json_encode(__('items', 'items')) ?>) + '</span>';
                    }
                },
                { data: 'sub_type', visible: false },
                { data: 'department', render: function (data, type) { return type === 'display' ? esc(data) : data; } },
                {
                    data: 'prep_by',
                    render: function (data, type) {
                        if (type !== 'display') return data;
                        return '<div class="sr-person"><span class="sr-avatar sr-avatar-sm">' + esc(initials(data)) + '</span>'
                            + '<span class="sr-person-name">' + esc(shortName(data)) + '</span></div>';
                    }
                },
                {
                    data: 'created_at',
                    render: function (data, type, row) {
                        if (type !== 'display') return data;
                        return '<div class="sr-date">' + esc(data) + (row.created_ago ? '<small>' + esc(row.created_ago) + '</small>' : '') + '</div>';
                    }
                },
                {
                    data: 'grand_total',
                    className: 'text-right',
                    render: function (data, type) {
                        if (type !== 'display') return money(data);
                        return '<span class="sr-money">' + money(data) + ' <i class="icon-saudi_riyal"></i></span>';
                    }
                },
                {
                    data: 'status',
                    render: function (data, type, row) {
                        var title = (data in statusObj) ? statusObj[data].title : data;
                        var tone = (data in statusObj) ? statusObj[data].tone : 'tone-slate';
                        if (data === 'pending_approval') {
                            if (row.is_current_approver === 1) {
                                title += ' · ' + __('level') + ' ' + row.current_approval_level;
                                tone = 'tone-amber';
                            } else if (row.user_approval_level && row.user_approval_level > row.current_approval_level) {
                                // Upcoming approval for this user
                                title += ' · ' + __('level') + ' ' + row.current_approval_level + ' → ' + row.user_approval_level;
                                tone = 'tone-slate';
                            }
                        }
                        if (type !== 'display') return title;
                        return '<span class="sr-pill ' + tone + '"><span class="sr-dot"></span>' + esc(title) + '</span>';
                    }
                },
                { data: 'action', className: 'text-right', searchable: false },
                { data: 'current_approval_level', visible: false, searchable: false },
                { data: 'user_approval_level', visible: false, searchable: false },
                { data: 'is_current_approver', visible: false, searchable: false }
            ],
            language: {
                lengthMenu: `${__('show')} _MENU_ ${__('entries')}`,
                info: `${__('showing')} _START_ ${__('to')} _END_ ${__('of')} _TOTAL_ ${__('entries')}`,
                infoEmpty: `${__('showing')} 0 ${__('to')} 0 ${__('of')} 0 ${__('entries')}`,
                infoFiltered: '',
                paginate: {
                    first: __('first'),
                    last: __('last'),
                    next: '<i class="mdi mdi-chevron-right"></i>',
                    previous: '<i class="mdi mdi-chevron-left"></i>'
                },
                emptyTable: `<div class="sr-empty"><i class="mdi mdi-inbox"></i>${__('no_data_available_in_table')}</div>`,
                zeroRecords: `<div class="sr-empty"><i class="mdi mdi-magnify"></i>${__('no_matching_records_found')}</div>`,
                processing: `<div class="spinner-border text-primary" role="status"><span class="sr-only">${__('loading')}...</span></div>`
            }
        });

        // Export buttons live in the toolbar
        table.buttons().container().appendTo('#srExportButtons');

        // Whole row opens the request (except clicks on links/buttons/dropdowns)
        $('#smartRequestTbl tbody').on('click', 'tr', function (e) {
            if ($(e.target).closest('a, button, .dropdown-menu, .dtr-control').length) return;
            if ($(this).hasClass('child')) return;
            var row = table.row(this).data();
            if (row && row.inv_no) window.location = 'open_request.php?id=' + encodeURIComponent(row.inv_no);
        });

        function setStatus(status, redraw) {
            $('#smtStatus').val(status);
            $('#srTiles .sr-tile').removeClass('active').attr('aria-selected', 'false')
                .filter('[data-status="' + status + '"]').addClass('active').attr('aria-selected', 'true');
            var label = $('#smtStatus option:selected').text();
            $('#srActiveFilter').html(status ? <?= json_encode(__('showing')) ?> + ': <strong>' + esc(label) + '</strong>' : '');
            if (redraw !== false && table) table.draw();
        }

        $('#srTiles').on('click', '.sr-tile', function () {
            setStatus($(this).data('status') + '');
        });

        var searchTimer = null;
        $('#search').on('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () { table.draw(); }, 300);
        });

    });

    // New Request modal (line editor: assets/js/smart_request_lines.js; saved by
    // ajaxSmartRequest.php ajaxType=request_create, then the new request is opened).
    (function() {
        const T = {
            title: <?= json_encode(__('create_new_request')) ?>,
            subTitle: <?= json_encode(__('subject_title')) ?>,
            subType: <?= json_encode(__('subject_type')) ?>,
            remarks: <?= json_encode(__('remarks')) ?>,
            items: <?= json_encode(__('items', 'Items')) ?>,
            details: <?= json_encode(__('request_details', 'Request details')) ?>,
            date: <?= json_encode(__('invoice_date')) ?>,
            dept: <?= json_encode(__('department')) ?>,
            prepBy: <?= json_encode(__('prepared_by')) ?>,
            totalBefore: <?= json_encode(__('total_before_disc')) ?>,
            reqDiscount: <?= json_encode(__('discount')) ?>,
            grand: <?= json_encode(__('grand_total')) ?>,
            save: <?= json_encode(__('save_as_draft', 'Save as Draft')) ?>,
            cancel: <?= json_encode(__('cancel', 'Cancel')) ?>,
            select: <?= json_encode(__('select')) ?>,
            required: <?= json_encode(__('fill_required_fields_validation')) ?>,
            failed: <?= json_encode(__('request_failed')) ?>,
            nextStep: <?= json_encode(__('new_request_next_step', 'Saved as a draft. Next: add attachments and choose approvers.')) ?>
        };
        const INFO = {
            date: <?= json_encode(date('d M Y')) ?>,
            dept: <?= json_encode($new_req_dept_name) ?>,
            prepBy: <?= json_encode($userwel ?? '') ?>
        };
        let subTypeOptions = null;

        function loadSubTypes() {
            if (subTypeOptions !== null) return $.Deferred().resolve(subTypeOptions).promise();
            return $.ajax({
                url: './includes/ajaxFile/ajaxSmartRequest.php',
                dataType: 'JSON', type: 'POST',
                data: { ajaxType: 'sub_type' }
            }).then(function(res) {
                let opts = '<option value="">' + SRLines.esc(T.select) + '</option>';
                if (res && res.status == 200 && res.data) {
                    opts += res.data.map(item => '<option value="' + SRLines.esc(item.sub_type) + '">' + SRLines.esc(item.sub_type) + '</option>').join('');
                }
                subTypeOptions = opts;
                return opts;
            }, function() {
                subTypeOptions = '<option value="">' + SRLines.esc(T.select) + '</option>';
                return $.Deferred().resolve(subTypeOptions).promise();
            });
        }

        function modalHTML() {
            const E = SRLines.esc, L = SRLines.label;
            return `
            <form id="srNewReqForm" class="sr-page text-left" autocomplete="off" novalidate>
                <div class="sr-newreq-info">
                    <span><i class="mdi mdi-calendar"></i> ${E(T.date)}: <b>${E(INFO.date)}</b></span>
                    <span><i class="mdi mdi-domain"></i> ${E(T.dept)}: <b>${E(INFO.dept)}</b></span>
                    <span><i class="mdi mdi-account"></i> ${E(T.prepBy)}: <b>${E(INFO.prepBy)}</b></span>
                </div>
                <div class="sr-addline sr-newreq-head">
                    <div class="sr-addline-grid">
                        <div class="g-item"><label>${E(T.subTitle)} <span class="text-danger">*</span></label><input type="text" name="sub_title" class="form-control" maxlength="250" required></div>
                        <div class="g-loc"><label>${E(T.subType)} <span class="text-danger">*</span></label><select name="sub_type" class="form-control" required>${subTypeOptions || ''}</select></div>
                        <div class="g-ref"><label>${E(T.remarks)}</label><input type="text" name="remarks" class="form-control"></div>
                    </div>
                </div>
                <div class="sr-newreq-section">${E(T.items)}</div>
                <div id="srNewLines" class="sr-addlines"></div>
                ${SRLines.addButtonHTML()}
                <div class="sr-addline-summary sr-newreq-summary">
                    <div><span>${L('net')}</span><b id="srNewNet">0.00</b></div>
                    <div><span>${L('vat')}</span><b id="srNewVat">0.00</b></div>
                    <div><span>${E(T.totalBefore)}</span><b id="srNewTotal">0.00</b></div>
                    <div><span>${E(T.reqDiscount)}</span><input type="number" step="0.01" min="0" name="discount" value="0" class="form-control js-recalc sr-newreq-disc"></div>
                    <div class="accent"><span>${E(T.grand)}</span><b id="srNewGrand">0.00</b></div>
                </div>
            </form>`;
        }

        function openNewRequest() {
            Swal.fire({
                title: T.title,
                html: '<div class="py-4 text-center"><div class="spinner-border text-primary" role="status"></div></div>',
                width: '1040px',
                showCancelButton: true,
                confirmButtonText: '<i class="mdi mdi-content-save"></i> ' + SRLines.esc(T.save),
                cancelButtonText: T.cancel,
                confirmButtonColor: APP_COLORS.primary,
                cancelButtonColor: APP_COLORS.danger_dark,
                showLoaderOnConfirm: true,
                allowOutsideClick: false,
                customClass: { popup: 'sr-addline-popup' },
                didOpen: () => {
                    Swal.disableButtons();
                    $.when(loadSubTypes(), SRLines.loadLocations()).always(function() {
                        $(Swal.getHtmlContainer()).html(modalHTML());
                        Swal.enableButtons();
                        const $form = $('#srNewReqForm');
                        const editor = SRLines.bind($form, $('#srNewLines'), function(sum) {
                            const disc = parseFloat($form.find('[name="discount"]').val()) || 0;
                            $('#srNewNet').text(SRLines.money(sum.net));
                            $('#srNewVat').text(SRLines.money(sum.vat));
                            $('#srNewTotal').text(SRLines.money(sum.total));
                            $('#srNewGrand').text(SRLines.money(sum.total - disc));
                        });
                        editor.addLine(false);
                        $form.find('[name="sub_title"]').trigger('focus');
                    });
                },
                preConfirm: () => {
                    const $form = $('#srNewReqForm');
                    if (!$form.length) return false;
                    if (SRLines.validate($form)) {
                        Swal.showValidationMessage(T.required);
                        return false;
                    }
                    return $.ajax({
                        url: './includes/ajaxFile/ajaxSmartRequest.php',
                        type: 'POST', dataType: 'JSON',
                        data: $form.serialize() + '&' + $.param({ ajaxType: 'request_create' })
                    }).then(response => {
                        if (response.type !== 'success' || !response.inv_no) {
                            throw new Error(response.message || 'Save failed');
                        }
                        return response;
                    }).catch(error => {
                        Swal.showValidationMessage(T.failed + ': ' + (error.message || error.statusText || 'Error'));
                    });
                }
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    Swal.fire({ allowOutsideClick: false, title: result.value.title, text: T.nextStep, icon: 'success', timer: 1800, showConfirmButton: false })
                        .then(() => { window.location = 'open_request.php?id=' + encodeURIComponent(result.value.inv_no); });
                }
            });
        }

        $(document).on('click', '.newRequestBtn', function(e) {
            e.preventDefault();
            openNewRequest();
        });

        // Old new_request.php links redirect here with ?new=1
        $(function() {
            if (new URLSearchParams(window.location.search).get('new') === '1' && $('.newRequestBtn').length) {
                const waitSwal = setInterval(function() {
                    if (window.Swal) { clearInterval(waitSwal); openNewRequest(); }
                }, 100);
            }
        });
    })();
    </script>

    </body>
</html>
<?php } ?>
