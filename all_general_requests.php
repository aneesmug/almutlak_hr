<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/special_access_helper.php';

// Restrict access to non-employee users only - unless the employee holds an explicit
// 'Access Page: General Requests' Special Access grant.
if ($user_type == 'employee' && !user_has_special_access($conDB, $empid ?? '', 'access_all_general_requests', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)) {
    header('Location: dashboard.php');
    exit;
}

// General requests are globally blocked - hide this page entirely (matches sidebar link hiding).
if (is_employee_request_blocked($conDB, $empid ?? '', 'general_request')['blocked']) {
    header('Location: dashboard.php');
    exit;
}

$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
if(mysqli_num_rows($query) == 1){
    include("./includes/avatar_select.php");
}

// Summary tiles: status value => [label, dot colour class]. '' = all statuses.
$gr_status_tiles = [
    ''                     => [__('all_statuses_option'), 'dot-all'],
    'draft'                => [__('draft_status'), 'dot-slate'],
    'pending_approval'     => [__('pending_approval'), 'dot-amber'],
    'approved'             => [__('approved'), 'dot-indigo'],
    'waiting_for_delivery' => [__('waiting_for_delivery', 'Waiting for Delivery'), 'dot-sky'],
    'completed'            => [__('completed', 'Completed'), 'dot-green'],
    'rejected'             => [__('rejected'), 'dot-red'],
    'cancelled'            => [__('cancelled', 'Cancelled'), 'dot-slate'],
];
// Opens on "Pending approval" unless a link asks for another status (?status=...).
$gr_default_status = (isset($_GET['status']) && array_key_exists((string)$_GET['status'], $gr_status_tiles)) ? (string)$_GET['status'] : 'pending_approval';
?>
<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>
<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?> - <?=__('all_general_requests', 'All General Requests')?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="Anees Afzal" name="author" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

    <!-- App favicon -->
    <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">

    <!-- Modal -->
    <link href="./plugins/custombox/css/custombox.min.css" rel="stylesheet">

    <!-- Plugins css -->
    <link href="./plugins/bootstrap-select/css/bootstrap-select.min.css" rel="stylesheet" />
    <link href="./plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
    
    <!-- DataTables -->
    <link href="./plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="./plugins/datatables/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="./plugins/datatables/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="./plugins/datatables/select.bootstrap4.min.css" rel="stylesheet" type="text/css" />

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
    <div id="wrapper">
        <!-- Left Sidebar -->
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

        <!-- Content Page -->
        <div class="content-page">
            <?php include("./includes/topbar.php"); ?>
            
            <div class="content sr-page">
                <div class="container-fluid">
                    <div class="sr-head">
                        <div>
                            <h1><?=__('all_general_requests', 'All General Requests')?></h1>
                            <p><?= __('general_requests_subtitle', 'Requests sent to other departments, from approval to delivery.') ?></p>
                        </div>
                        <div class="sr-head-actions">
                            <a href="new_general_request.php?id=<?= htmlspecialchars((string)($newinvgr ?? ''), ENT_QUOTES) ?>" class="sr-btn sr-btn-primary"><i class="fa fa-plus"></i> <?=__('new_request_button', 'New Request')?></a>
                        </div>
                    </div>

                    <?php if (isset($_GET['error']) && $_GET['error'] === 'request_not_found'): ?>
                        <div class="sr-notice tone-red" role="alert">
                            <i class="mdi mdi-alert-circle-outline"></i>
                            <div><?= __('error_request_not_found', 'The requested item was not found or the link is invalid.') ?></div>
                        </div>
                    <?php endif; ?>

                    <!-- Status summary tiles (also act as the status filter) -->
                    <div class="sr-tiles" id="srTiles" role="tablist">
                        <?php foreach ($gr_status_tiles as $tile_status => $tile): ?>
                            <button type="button" class="sr-tile" data-status="<?= $tile_status ?>" role="tab">
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
                            <select class="d-none" name="status_filter" id="statusFilter" aria-hidden="true" tabindex="-1">
                                <?php foreach ($gr_status_tiles as $tile_status => $tile): ?>
                                    <option value="<?= $tile_status ?>"><?= htmlspecialchars($tile[0]) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="sr-table-wrap">
                            <table id="generalRequestsTbl" class="table sr-table dt-responsive nowrap" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th><?=__('id')?></th>
                                        <th><?=__('request_number', 'Request No.')?></th>
                                        <th><?=__('request_title', 'Title')?></th>
                                        <th><?=__('target_department', 'Target Dept.')?></th>
                                        <th><?=__('category')?></th>
                                        <th><?=__('priority')?></th>
                                        <th><?=__('requester', 'Requester')?></th>
                                        <th><?=__('created_at')?></th>
                                        <th><?=__('status')?></th>
                                        <th class="text-right"><?=__('action')?></th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <footer class="footer"><?=$site_footer?></footer>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/metisMenu.min.js"></script>
    <script src="assets/js/waves.js"></script>
    <script src="assets/js/jquery.slimscroll.js"></script>

    <!-- DataTables -->
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

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script type="text/javascript">
        $(document).ready(function(){
            var table = null;
            var exportTitle = "<?=__('all_general_requests', 'All General Requests')?>";
            var columnNum = [ 1, 2, 3, 4, 5, 6, 7, 8 ];

            function esc(s) {
                return String(s === null || s === undefined ? '' : s)
                    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
            }
            function initials(name) {
                return String(name || '').trim().split(/\s+/).slice(0, 2).map(function (w) { return w.charAt(0); }).join('').toUpperCase();
            }

            // Status / priority -> pill tone
            var statusObj = {
                'draft':                { title: '<?=__('draft_status')?>', tone: 'tone-slate' },
                'pending_approval':     { title: '<?=__('pending_approval')?>', tone: 'tone-amber' },
                'approved':             { title: '<?=__('approved')?>', tone: 'tone-indigo' },
                'waiting_for_delivery': { title: '<?=__('waiting_for_delivery', 'Waiting for Delivery')?>', tone: 'tone-sky' },
                'completed':            { title: '<?=__('completed', 'Completed')?>', tone: 'tone-green' },
                'rejected':             { title: '<?=__('rejected')?>', tone: 'tone-red' },
                'cancelled':            { title: '<?=__('cancelled', 'Cancelled')?>', tone: 'tone-slate' }
            };
            var priorityObj = {
                'low':    { title: '<?=__('low_priority', 'Low')?>', tone: 'tone-sky' },
                'medium': { title: '<?=__('medium_priority', 'Medium')?>', tone: 'tone-indigo' },
                'high':   { title: '<?=__('high_priority', 'High')?>', tone: 'tone-amber' },
                'urgent': { title: '<?=__('urgent_priority', 'Urgent')?>', tone: 'tone-red' }
            };

            function setStatus(status, redraw) {
                $('#statusFilter').val(status);
                $('#srTiles .sr-tile').removeClass('active').attr('aria-selected', 'false')
                    .filter('[data-status="' + status + '"]').addClass('active').attr('aria-selected', 'true');
                var label = $('#statusFilter option:selected').text();
                $('#srActiveFilter').html(status ? <?= json_encode(__('showing')) ?> + ': <strong>' + esc(label) + '</strong>' : '');
                if (redraw !== false && table) table.draw();
            }
            setStatus(<?= json_encode($gr_default_status) ?>, false);

            // Initialize DataTable
            table = $('#generalRequestsTbl').DataTable({
                dom: "Brtip",
                serverSide: true,
                ordering: false, // server always lists newest first
                pageLength: 15,
                buttons: [
                    { extend: 'excel', text: '<i class="mdi mdi-file-excel"></i> Excel', exportOptions: { columns: columnNum, orthogonal: 'export' }, title: exportTitle },
                    { extend: 'pdf',   text: '<i class="mdi mdi-file-pdf"></i> PDF',     exportOptions: { columns: columnNum, orthogonal: 'export' }, title: exportTitle },
                    { extend: 'print', text: '<i class="mdi mdi-printer"></i> ' + <?= json_encode(__('print')) ?>, exportOptions: { columns: columnNum, orthogonal: 'export' }, title: exportTitle }
                ],
                processing: true,
                responsive: true,
                ajax: {
                    type: "POST",
                    url: './includes/ajaxFile/generalRequestAjaxTbl.php',
                    data: function (d) {
                        d.user_type = '<?=$user_type?>';
                        d.user_dept = '<?=$user_dept?>';
                        d.emptype   = '<?=$emptypeget?>';
                        d.emp_id    = '<?=$empid?>';
                        d.status    = $('#statusFilter').val();
                        d.search    = $('#search').val();
                        d.withCounts = 1;
                    },
                    dataSrc: function (json) {
                        var counts = json.counts || {};
                        $('#srTiles [data-count]').each(function () {
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
                        data: 'request_title',
                        render: function (data, type, row) {
                            if (type !== 'display') return data;
                            return '<span class="sr-cell-title" title="' + esc(data) + '">' + esc(data) + '</span>'
                                + (row.request_category ? '<span class="sr-cell-sub"><i class="mdi mdi-tag-outline"></i>' + esc(row.request_category) + '</span>' : '');
                        }
                    },
                    { data: 'department_to', render: function (data, type) { return type === 'display' ? esc(data) : data; } },
                    { data: 'request_category', visible: false },
                    {
                        data: 'priority',
                        render: function (data, type) {
                            var p = (data in priorityObj) ? priorityObj[data] : { title: data, tone: 'tone-slate' };
                            if (type !== 'display') return p.title;
                            return '<span class="sr-pill sr-pill-xs ' + p.tone + '">' + esc(p.title) + '</span>';
                        }
                    },
                    {
                        data: 'emp_name',
                        render: function (data, type) {
                            if (type !== 'display') return data;
                            return '<div class="sr-person"><span class="sr-avatar sr-avatar-sm">' + esc(initials(data)) + '</span>'
                                + '<span class="sr-person-name">' + esc(data) + '</span></div>';
                        }
                    },
                    { data: 'created_at', render: function (data, type) { return type === 'display' ? '<span class="sr-date">' + esc(data) + '</span>' : data; } },
                    {
                        data: 'current_status',
                        render: function (data, type, row) {
                            var title = (data in statusObj) ? statusObj[data].title : data;
                            var tone = (data in statusObj) ? statusObj[data].tone : 'tone-slate';
                            if (data === 'pending_approval' && row.current_approval_level) {
                                title += ' · ' + __('level') + ' ' + row.current_approval_level;
                            }
                            if (type !== 'display') return title;
                            return '<span class="sr-pill ' + tone + '"><span class="sr-dot"></span>' + esc(title) + '</span>';
                        }
                    },
                    { data: 'action', className: 'text-right', searchable: false },
                    { data: 'current_approval_level', visible: false, searchable: false }
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
            $('#generalRequestsTbl tbody').on('click', 'tr', function (e) {
                if ($(e.target).closest('a, button, .dropdown-menu, .dtr-control').length) return;
                if ($(this).hasClass('child')) return;
                var row = table.row(this).data();
                if (row && row.inv_no) window.location = 'view_general_request.php?id=' + encodeURIComponent(row.inv_no);
            });

            $('#srTiles').on('click', '.sr-tile', function () {
                setStatus($(this).data('status') + '');
            });

            var searchTimer = null;
            $('#search').on('input', function () {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(function () { table.draw(); }, 300);
            });

            // Delete request handler
            $(document).on('click', '.deleteRequest', function() {
                const inv_no = $(this).data('id');
                
                Swal.fire({
                    title: '<?=__('are_you_sure', 'Are you sure?')?>',
                    text: '<?=__('confirm_delete_request', 'This will permanently delete the request and all its data!')?>',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: APP_COLORS.danger_dark,
                    cancelButtonColor: APP_COLORS.primary,
                    confirmButtonText: '<?=__('yes_delete_it', 'Yes, delete it!')?>',
                    cancelButtonText: '<?=__('cancel')?>'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: './includes/ajaxFile/ajaxGeneralRequest.php',
                            type: 'POST',
                            data: {
                                action: 'delete_general_request',
                                inv_no: inv_no
                            },
                            dataType: 'json',
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: '<?=__('deleted')?>',
                                        text: response.message,
                                        timer: 2000,
                                        showConfirmButton: false
                                    });
                                    table.draw();
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: '<?=__('error')?>',
                                        text: response.message
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire({
                                    icon: 'error',
                                    title: '<?=__('error')?>',
                                    text: '<?=__('error_occurred', 'An error occurred')?>'
                                });
                            }
                        });
                    }
                });
            });

            // Self-cancel handler (pending_approval / approved requests owned by the current user)
            $(document).on('click', '.cancelGeneralRequestSelf', function() {
                const inv_no = $(this).data('id');

                Swal.fire({
                    title: '<?=__('are_you_sure', 'Are you sure?')?>',
                    text: '<?=__('are_you_sure_cancel_request', 'Are you sure you want to cancel this request?')?>',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: APP_COLORS.danger_dark,
                    cancelButtonColor: APP_COLORS.primary,
                    confirmButtonText: '<?=__('yes_cancel_request', 'Yes, Cancel It')?>',
                    cancelButtonText: '<?=__('keep_request', 'No')?>'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: './includes/ajaxFile/ajaxGeneralRequest.php',
                            type: 'POST',
                            data: {
                                action: 'cancel_general_request_self',
                                inv_no: inv_no
                            },
                            dataType: 'json',
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: '<?=__('cancelled', 'Cancelled')?>',
                                        text: response.message,
                                        timer: 2000,
                                        showConfirmButton: false
                                    });
                                    table.draw();
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: '<?=__('error')?>',
                                        text: response.message
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire({
                                    icon: 'error',
                                    title: '<?=__('error')?>',
                                    text: '<?=__('error_occurred', 'An error occurred')?>'
                                });
                            }
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>
