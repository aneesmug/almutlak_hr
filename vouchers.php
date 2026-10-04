<?php

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';

$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='" . $username . "'");
if (mysqli_num_rows($query) == 1) {
    include("./includes/avatar_select.php");

    $q_post = mysqli_query($conDB, "SELECT * FROM `menu_category` ORDER BY `id` DESC LIMIT 1");
    while ($row = mysqli_fetch_assoc($q_post)) {
        $lastid =  $row['id'];
    }
?>
    <!doctype html>
    <html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

    <head>
        <meta charset="utf-8" />
        <title><?= $site_title ?> - <?=__('all_vouchers_title')?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <!--        <meta content="A fully featured admin theme which can be used to build CRM, CMS, etc." name="description" />-->
        <meta content="Anees Afzal" name="author" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />

        <!-- App favicon -->
        <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">

        <!-- Modal -->
        <link href="./plugins/custombox/css/custombox.min.css" rel="stylesheet">

        <!-- Plugins css -->
        <link href="./plugins/bootstrap-timepicker/bootstrap-timepicker.min.css" rel="stylesheet">
        <link href="./plugins/bootstrap-colorpicker/css/bootstrap-colorpicker.min.css" rel="stylesheet">
        <link href="./plugins/bootstrap-datepicker/css/bootstrap-datepicker.min.css" rel="stylesheet">
        <link href="./plugins/clockpicker/css/bootstrap-clockpicker.min.css" rel="stylesheet">
        <link href="./plugins/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
        <link href="./plugins/bootstrap-select/css/bootstrap-select.min.css" rel="stylesheet" />
        <link href="./plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
        <!-- DataTables -->
        <link href="./plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
        <link href="./plugins/datatables/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" />
        <!-- Responsive datatable examples -->
        <link href="./plugins/datatables/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />

        <!-- Multi Item Selection examples -->
        <link href="./plugins/datatables/select.bootstrap4.min.css" rel="stylesheet" type="text/css" />

        <!-- App css -->
        <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
        <script src="assets/js/modernizr.min.js"></script>

        <link href="assets/css/smart_request.css?v=<?= @filemtime(__DIR__ . '/assets/css/smart_request.css') ?>" rel="stylesheet" type="text/css" />
        <style type="text/css">
            .sr-page .sr-tiles.vou-tiles { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            @media (max-width: 575px) { .sr-page .sr-tiles.vou-tiles { grid-template-columns: 1fr; } }
            .vou-tile-amt { display: block; margin-top: 4px; font-size: 12px; font-weight: 600; color: var(--sr-muted); font-variant-numeric: tabular-nums; }
            .vou-tile-amt .icon-saudi_riyal { font-size: .85em !important; }
        </style>
        <?php if ($is_rtl): ?>
            <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
        <?php endif; ?>
        <script>
            window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;
        </script>
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
                            <span>
                                <img src="<?=get_setting($conDB, 'logo')?>" alt="" height="22">
                            </span>
                            <i>
                                <img src="<?=get_setting($conDB, 'white_logo')?>" alt="" height="28">
                            </i>
                        </a>
                    </div>

                    <!-- User box -->

                    <!--- Sidemenu -->
                    <?php include("./includes/main_menu.php"); ?>
                    <!-- Sidebar -->

                    <div class="clearfix"></div>

                </div>
                <!-- Sidebar -left -->

            </div>
            <!-- Left Sidebar End -->



            <!-- ============================================================== -->
            <!-- Start right Content here -->
            <!-- ============================================================== -->

            <div class="content-page">

                <!-- Top Bar Start -->
                <?php include("./includes/topbar.php"); ?>
                <!-- Top Bar End -->


                <!-- Start Page content -->
                <div class="content sr-page">
                    <div class="container-fluid">
                        <div class="sr-head">
                            <div>
                                <h1><?=__('all_vouchers_title')?></h1>
                                <p><?= __('vouchers_subtitle', 'Payment vouchers and receipts issued to employees.') ?></p>
                            </div>
                            <div class="sr-head-actions">
                                <button type="button" class="sr-btn sr-btn-primary" id="addVoucherBtn"><i class="fa fa-plus"></i> <?=__('add_voucher_button')?></button>
                            </div>
                        </div>

                        <!-- Type summary tiles (also act as the type filter) -->
                        <div class="sr-tiles vou-tiles" id="srTiles" role="tablist">
                            <?php foreach ([
                                '' => [__('all_option'), 'dot-all'],
                                'receipt' => [__('receipt_option'), 'dot-green'],
                                'payment' => [__('payment_option'), 'dot-red'],
                            ] as $tile_type => $tile): ?>
                                <button type="button" class="sr-tile<?= $tile_type === '' ? ' active' : '' ?>" data-type="<?= $tile_type ?>" role="tab">
                                    <span class="sr-tile-label"><span class="sr-dot <?= $tile[1] ?>"></span><?= htmlspecialchars($tile[0]) ?></span>
                                    <span class="sr-tile-value" data-count="<?= $tile_type === '' ? 'all' : $tile_type ?>">&ndash;</span>
                                    <span class="vou-tile-amt" data-amount="<?= $tile_type === '' ? 'all' : $tile_type ?>"></span>
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
                                    <div id="srExportButtons"></div>
                                </div>
                                <input type="hidden" name="payment_type" id="paymenttype" value="">
                            </div>
                            <div class="sr-table-wrap">
                                <table id="vouchers_vac" class="table sr-table dt-responsive nowrap" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th> </th>
                                            <th><?=__('voucher_no_header')?></th>
                                            <th><?=__('voucher_from_header')?></th>
                                            <th><?=__('to_employee_header')?></th>
                                            <th><?=__('voucher_type_header')?></th>
                                            <th class="text-right"><?=__('amount_header')?></th>
                                            <th><?=__('details_header')?></th>
                                            <th><?=__('created_at_header')?></th>
                                            <th class="text-right"><?=__('action')?></th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div> <!-- container -->
                </div> <!-- content -->

                <footer class="footer">
                    <?= $site_footer ?>
                </footer>

            </div>

            <!-- ============================================================== -->
            <!-- End Right content here -->
            <!-- ============================================================== -->
        </div>
        <!-- END wrapper -->
        <!-- jQuery  -->
        <script src="assets/js/jquery.min.js"></script>
        <script src="assets/js/bootstrap.bundle.min.js"></script>
        <script src="assets/js/metisMenu.min.js"></script>
        <script src="assets/js/waves.js"></script>
        <script src="assets/js/jquery.slimscroll.js"></script>

        <!-- Modal-Effect -->
        <script type="text/javascript" src="./plugins/parsleyjs/parsley.min.js"></script>
        <script src="./plugins/bootstrap-inputmask/bootstrap-inputmask.min.js" type="text/javascript"></script>
        <script src="./plugins/autoNumeric/autoNumeric.js" type="text/javascript"></script>
        <script src="./plugins/moment/moment.js"></script>
        <!-- Required datatable js -->
        <script src="./plugins/datatables/jquery.dataTables.min.js"></script>
        <script src="./plugins/datatables/dataTables.bootstrap4.min.js"></script>
        <!-- Buttons examples -->
        <script src="./plugins/datatables/dataTables.buttons.min.js"></script>
        <script src="./plugins/datatables/buttons.bootstrap4.min.js"></script>
        <script src="./plugins/datatables/jszip.min.js"></script>
        <script src="./plugins/datatables/pdfmake.min.js"></script>
        <script src="./plugins/datatables/vfs_fonts.js"></script>
        <script src="./plugins/datatables/buttons.html5.min.js"></script>
        <script src="./plugins/datatables/buttons.print.min.js"></script>

        <script src="./plugins/select2/js/select2.min.js" type="text/javascript"></script>
        <script src="./plugins/bootstrap-select/js/bootstrap-select.js" type="text/javascript"></script>

        <!-- Key Tables -->
        <script src="./plugins/datatables/dataTables.keyTable.min.js"></script>

        <!-- Responsive examples -->
        <script src="./plugins/datatables/dataTables.responsive.min.js"></script>
        <script src="./plugins/datatables/responsive.bootstrap4.min.js"></script>

        <!-- Selection table -->
        <script src="./plugins/datatables/dataTables.select.min.js"></script>

        <!-- App js -->
        <script src="assets/js/sr_forms.js?v=<?= @filemtime(__DIR__ . '/assets/js/sr_forms.js') ?>"></script>
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>

        <script type="text/javascript">
            $(document).ready(function() {
                function newexportaction(e, dt, button, config) {
                    var self = this;
                    var oldStart = dt.settings()[0]._iDisplayStart;
                    dt.one('preXhr', function(e, s, data) {
                        data.start = 0;
                        data.length = 2147483647;
                        dt.one('preDraw', function(e, settings) {
                            if (button[0].className.indexOf('buttons-excel') >= 0) {
                                $.fn.dataTable.ext.buttons.excelHtml5.action.call(self, e, dt, button, config);
                            } else if (button[0].className.indexOf('buttons-pdf') >= 0) {
                                $.fn.dataTable.ext.buttons.pdfHtml5.action.call(self, e, dt, button, config);
                            } else if (button[0].className.indexOf('buttons-print') >= 0) {
                                $.fn.dataTable.ext.buttons.print.action(e, dt, button, config);
                            }
                            dt.one('preXhr', function(e, s, data) {
                                settings._iDisplayStart = oldStart;
                                data.start = oldStart;
                            });
                            setTimeout(dt.ajax.reload, 0);
                            return false;
                        });
                    });
                    dt.ajax.reload();
                };

                var esc = SRForm.esc;
                function initials(name) {
                    return String(name || '').trim().split(/\s+/).slice(0, 2).map(function(w) { return w.charAt(0); }).join('').toUpperCase();
                }
                function money(v) {
                    var n = parseFloat(v) || 0;
                    return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                }
                function person(name) {
                    if (!name) return '<span class="text-muted">&ndash;</span>';
                    return '<div class="sr-person"><span class="sr-avatar sr-avatar-sm">' + esc(initials(name)) + '</span><span class="sr-person-name">' + esc(name) + '</span></div>';
                }

                var exportTitle = "<?=__('all_vouchers_title')?>";
                var columnNum = [1, 2, 3, 4, 5, 6, 7];
                var typeObj = {
                    'payment': { title: '<?=__('payment_option')?>', tone: 'tone-red', icon: 'mdi-arrow-top-right' },
                    'receipt': { title: '<?=__('receipt_option')?>', tone: 'tone-green', icon: 'mdi-arrow-bottom-left' }
                };

                var table = $('#vouchers_vac').DataTable({
                    dom: "Brtip",
                    serverSide: true,
                    lengthMenu: [
                        [10, 100, -1],
                        [10, 100, "All"]
                    ],
                    buttons: [
                        { extend: 'excel', text: '<i class="mdi mdi-file-excel"></i> Excel', exportOptions: { columns: columnNum, orthogonal: 'export' }, title: exportTitle, action: newexportaction },
                        { extend: 'pdf',   text: '<i class="mdi mdi-file-pdf"></i> PDF',     exportOptions: { columns: columnNum, orthogonal: 'export' }, title: exportTitle, action: newexportaction },
                        { extend: 'print', text: '<i class="mdi mdi-printer"></i> ' + <?= json_encode(__('print')) ?>, exportOptions: { columns: columnNum, orthogonal: 'export' }, title: exportTitle, action: newexportaction }
                    ],
                    order: [
                        [0, "desc"]
                    ],
                    processing: true,
                    serverMethod: 'post',
                    responsive: true,
                    paging: true,
                    ajax: {
                        type: "POST",
                        url: './includes/ajaxFile/vouchersAjaxfile.php',
                        data: function(d) {
                            d.user_type = '<?= $user_type ?>';
                            d.user_dept = '<?= $user_dept ?>';
                            d.payment_type = $('#paymenttype').val();
                            d.search = $('#search').val();
                        },
                        dataSrc: function(json) {
                            var counts = json.counts || {};
                            $('#srTiles [data-count]').each(function() {
                                var c = counts[$(this).data('count')];
                                $(this).text(c ? c.count : 0);
                            });
                            $('#srTiles [data-amount]').each(function() {
                                var c = counts[$(this).data('amount')];
                                $(this).html(money(c ? c.amount : 0) + ' <i class="icon-saudi_riyal"></i>');
                            });
                            return json.aaData;
                        }
                    },
                    columns: [
                        { data: 'id', visible: false, searchable: true },
                        { data: 'voucher_no', render: function(data, type) { return type === 'display' ? '<span class="sr-chip sr-mono">' + esc(data) + '</span>' : data; } },
                        { data: 'emp_from', render: function(data, type) { return type === 'display' ? person(data) : data; } },
                        { data: 'name', render: function(data, type) { return type === 'display' ? person(data) : data; } },
                        {
                            data: 'voucher_type',
                            render: function(data, type) {
                                var o = typeObj[data] || { title: data, tone: 'tone-slate', icon: 'mdi-swap-horizontal' };
                                if (type !== 'display') return o.title;
                                return '<span class="sr-pill ' + o.tone + '"><i class="mdi ' + o.icon + '"></i>' + esc(o.title) + '</span>';
                            }
                        },
                        {
                            data: 'voucher_amount',
                            className: 'text-right',
                            render: function(data, type) {
                                if (type !== 'display') return money(data);
                                return '<span class="sr-money">' + money(data) + ' <i class="icon-saudi_riyal"></i></span>';
                            }
                        },
                        { data: 'details', render: function(data, type) { return type === 'display' ? '<span class="sr-cell-title" title="' + esc(data) + '">' + esc(data) + '</span>' : data; } },
                        { data: 'created_at', render: function(data, type) { return type === 'display' ? '<span class="sr-date">' + esc(data) + '</span>' : data; } },
                        { data: 'action', className: 'text-right', orderable: false, searchable: false },
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

                table.buttons().container().appendTo('#srExportButtons');

                // Whole row opens the print view (except clicks on links/buttons/dropdowns)
                $('#vouchers_vac tbody').on('click', 'tr', function(e) {
                    if ($(e.target).closest('a, button, .dropdown-menu, .dtr-control').length) return;
                    if ($(this).hasClass('child')) return;
                    var row = table.row(this).data();
                    if (row && row.id) window.open('voucher_print.php?id=' + encodeURIComponent(row.id), '_blank');
                });

                $('#srTiles').on('click', '.sr-tile', function() {
                    $('#srTiles .sr-tile').removeClass('active');
                    $(this).addClass('active');
                    $('#paymenttype').val($(this).data('type') + '');
                    table.draw();
                });

                var searchTimer = null;
                $('#search').on('input', function() {
                    clearTimeout(searchTimer);
                    searchTimer = setTimeout(function() { table.draw(); }, 300);
                });

                $('#addVoucherBtn').on('click', function() { srAddVoucher(<?= json_encode((string)$empid) ?>); });
            });

            // New voucher popup (posts the same fields as the old addVoucherFunc -> ajaxVoucher.php add_voucher)
            function srAddVoucher(empid) {
                var F = SRForm;
                var html = '<form id="srVoucherForm" class="sr-form" autocomplete="off" novalidate>' +
                    '<input type="hidden" name="empid" value="' + F.esc(empid) + '">' +
                    F.section('mdi-account', <?= json_encode(__('select_employee')) ?>,
                        F.field({ name: 'emp_v_user', label: <?= json_encode(__('select_employee')) ?>, req: true, col: 12,
                            html: F.select({ name: 'emp_v_user', id: 'emp_v_user', req: true, msg: <?= json_encode(__('select_employee_validation')) ?> }) })) +
                    F.section('mdi-receipt', <?= json_encode(__('voucher_type_header')) ?>,
                        '<div class="sr-fcol c-12">' + F.choices('voucher_type', [
                            { v: 'receipt', l: <?= json_encode(__('payment_receipt')) ?>, icon: 'mdi-arrow-bottom-left' },
                            { v: 'payment', l: <?= json_encode(__('payment_voucher')) ?>, icon: 'mdi-arrow-top-right' }
                        ], true, <?= json_encode(__('select_voucher_type_validation')) ?>) + '</div>' +
                        F.field({ name: 'amount', label: <?= json_encode(__('amount')) ?>, req: true, col: 6, type: 'number', attrs: ' step="0.01" min="0"', msg: <?= json_encode(__('enter_voucher_amount_validation')) ?> }) +
                        F.field({ name: 'details', label: <?= json_encode(__('details')) ?>, req: true, col: 6, msg: <?= json_encode(__('enter_voucher_details_validation')) ?> }) +
                        F.field({ name: 'acc_no', label: <?= json_encode(__('account_no')) ?>, col: 6 }) +
                        F.field({ name: 'chq_no', label: <?= json_encode(__('cheque_no')) ?>, col: 6 })) +
                    F.section('mdi-paperclip', <?= json_encode(__('attachment')) ?>,
                        '<div class="sr-fcol c-12">' + F.filePicker({ id: 'checkatt', name: 'file', accept: '.pdf,.jpg,.jpeg,.png', hint: 'PDF / JPG / PNG - max 8 MB' }) + '</div>') +
                    '</form>';

                F.open({
                    title: <?= json_encode(__('add_new_voucher_title')) ?>,
                    html: html,
                    confirm: <?= json_encode(__('yes_register')) ?>,
                    didOpen: function() {
                        var $form = $('#srVoucherForm');
                        F.liveClear($form);
                        F.bindFilePicker($form);
                        var $sel = $('#emp_v_user');
                        F.select2($sel);
                        $.post('./includes/ajaxFile/hrHandler.php', { ajaxType: 'emp_search' }, null, 'json').done(function(res) {
                            if (res && res.status == 200) {
                                (res.data || []).forEach(function(e) {
                                    var n = String(e.name || '').split(' ').slice(0, 2).join(' ');
                                    $sel.append(new Option(n + ' (' + e.emp_id + ')', e.emp_id));
                                });
                            }
                        });
                    },
                    preConfirm: function() {
                        var $form = $('#srVoucherForm');
                        var file = $('#checkatt')[0];
                        var msg = F.validate($form, function() {
                            var err = F.checkFile(file, ['pdf', 'jpg', 'jpeg', 'png'], 8);
                            return err ? { el: $form.find('.sr-filepick')[0], msg: err } : null;
                        });
                        if (msg) { Swal.showValidationMessage(msg); return false; }
                        var fd = new FormData($form[0]);
                        fd.append('ajaxType', 'add_voucher');
                        return F.post('./includes/ajaxFile/ajaxVoucher.php', fd, true);
                    }
                }).then(function(result) {
                    F.done(result, function() { $('#vouchers_vac').DataTable().ajax.reload(); });
                });
            }
        </script>

    </body>

    </html>
<?php } ?>
