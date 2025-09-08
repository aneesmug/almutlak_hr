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
        <link rel="shortcut icon" href="assets/images/favicon.ico">

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

        <style type="text/css">
            tr.disableLoc {
                background-color: #f1556c !important;
                color: #fff;
            }

            tr.disableLoc:hover {
                background-color: #ef3d58 !important;
            }
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
                                <img src="assets/images/logo.png" alt="" height="22">
                            </span>
                            <i>
                                <img src="assets/images/logo_sm.png" alt="" height="28">
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
                <div class="content">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-12">
                                <div class="card-box table-responsive">
                                    <!-- <a href="add_location.php" class="btn btn-primary waves-effect"><i class="mdi mdi-settings"></i> Add New Location</a> -->
                                    <h4 class="m-t-0 header-title"><?=__('all_registered_items_header')?></h4>
                                    <div class="col-2 pull-right">
                                        <div class="form-group">
                                            <input type="search" name="search" class="form-control" placeholder="<?=__('search_placeholder')?>" id="search" autocomplete="off">
                                        </div>
                                    </div>
                                    <div class="col-1 pull-right">
                                        <div class="form-group" style="margin-bottom: 0 !important">
                                            <select class="form-control" name="payment_type" id="paymenttype">
                                                <option value=""><?=__('all_option')?></option>
                                                <option value="receipt"><?=__('receipt_option')?></option>
                                                <option value="payment"><?=__('payment_option')?></option>
                                            </select>
                                        </div>
                                    </div>
                                    <table id="vouchers_vac" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                        <thead>
                                            <tr>
                                                <th> </th>
                                                <th><?=__('voucher_no_header')?></th>
                                                <th><?=__('voucher_from_header')?></th>
                                                <th><?=__('to_employee_header')?></th>
                                                <th><?=__('voucher_type_header')?></th>
                                                <th><?=__('amount_header')?></th>
                                                <th><?=__('details_header')?></th>
                                                <th><?=__('created_at_header')?></th>
                                                <th width="60"><?=__('action')?></th>
                                            </tr>
                                        </thead>
                                        <tfoot>
                                            <tr>
                                                <th> </th>
                                                <th><?=__('voucher_no_header')?></th>
                                                <th><?=__('voucher_from_header')?></th>
                                                <th><?=__('to_employee_header')?></th>
                                                <th><?=__('voucher_type_header')?></th>
                                                <th><?=__('amount_header')?></th>
                                                <th><?=__('details_header')?></th>
                                                <th><?=__('created_at_header')?></th>
                                                <th width="60"><?=__('action')?></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
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
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js"></script>

        <script type="text/javascript">
            $(document).ready(function() {
                function newexportaction(e, dt, button, config) {
                    var self = this;
                    var oldStart = dt.settings()[0]._iDisplayStart;
                    dt.one('preXhr', function(e, s, data) {
                        data.start = 0;
                        data.length = 2147483647;
                        dt.one('preDraw', function(e, settings) {
                            if (button[0].className.indexOf('buttons-copy') >= 0) {
                                $.fn.dataTable.ext.buttons.copyHtml5.action.call(self, e, dt, button, config);
                            } else if (button[0].className.indexOf('buttons-excel') >= 0) {
                                $.fn.dataTable.ext.buttons.excelHtml5.available(dt, config) ?
                                    $.fn.dataTable.ext.buttons.excelHtml5.action.call(self, e, dt, button, config) :
                                    $.fn.dataTable.ext.buttons.excelFlash.action.call(self, e, dt, button, config);
                            } else if (button[0].className.indexOf('buttons-csv') >= 0) {
                                $.fn.dataTable.ext.buttons.csvHtml5.available(dt, config) ?
                                    $.fn.dataTable.ext.buttons.csvHtml5.action.call(self, e, dt, button, config) :
                                    $.fn.dataTable.ext.buttons.csvFlash.action.call(self, e, dt, button, config);
                            } else if (button[0].className.indexOf('buttons-pdf') >= 0) {
                                $.fn.dataTable.ext.buttons.pdfHtml5.available(dt, config) ?
                                    $.fn.dataTable.ext.buttons.pdfHtml5.action.call(self, e, dt, button, config) :
                                    $.fn.dataTable.ext.buttons.pdfFlash.action.call(self, e, dt, button, config);
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

                var buttonConfig = [];
                var exportTitle = "<?=__('all_vouchers_title')?>";
                var columnNum = [1, 2, 3, 4, 5, 6, 7];
                buttonConfig.push({
                    extend: 'excel',
                    exportOptions: {
                        columns: columnNum
                    },
                    title: exportTitle,
                    className: 'btn-success',
                    action: newexportaction
                });
                buttonConfig.push({
                    extend: 'pdf',
                    exportOptions: {
                        columns: columnNum
                    },
                    title: exportTitle,
                    className: 'btn-danger',
                    action: newexportaction
                });
                buttonConfig.push({
                    extend: 'print',
                    exportOptions: {
                        columns: columnNum
                    },
                    title: exportTitle,
                    className: 'btn-dark',
                    action: newexportaction
                });
                buttonConfig.push({
                    text: '<i class="fa fa-plus"></i> <?=__('add_voucher_button')?>',
                    action: function(e, dt, button, config) {
                        addVoucherFunc(<?= $empid ?>)
                    },
                    className: 'btn-info'
                });

                var statusObj = {
                    'payment': {
                        title: '<?=__('payment_option')?>',
                        class: 'badge-border-danger',
                        icon: '<i class="fa fa-up-right"></i>'
                    },
                    'receipt': {
                        title: '<?=__('receipt_option')?>',
                        class: 'badge-border-success',
                        icon: '<i class="fa fa-down-left"></i>'
                    },
                };
                var table = $('#vouchers_vac').DataTable({

                    dom: "Bfrtip",
                    serverSide: true,
                    lengthMenu: [
                        [10, 100, -1],
                        [10, 100, "All"]
                    ],
                    buttons: buttonConfig,
                    order: [
                        [0, "desc"]
                    ],
                    columnDefs: [{
                            targets: [0, 1],
                            visible: false,
                            searchable: true
                        },
                        {
                            targets: 4,
                            render: function(data, type, row, meta) {
                                return (`
                                <span class="badge-border ${statusObj[data].class}" text-capitalized>
                                    ${statusObj[data].icon}
                                    ${statusObj[data].title}
                                </span>
                            `);
                            }
                        },
                    ],
                    // 'lengthChange': true,
                    processing: true,
                    serverMethod: 'post',
                    responsive: true,
                    paging: true,
                    // 'pageLength': 10,
                    ajax: {
                        type: "POST",
                        url: './includes/ajaxFile/vouchersAjaxfile.php',
                        data: function(d) {
                            d.user_type = '<?= $user_type ?>';
                            d.user_dept = '<?= $user_dept ?>';
                            d.payment_type = $('#paymenttype').val();
                            d.search = $('#search').val();
                        },
                    },
                    columns: [{
                            data: 'id'
                        },
                        {
                            data: 'voucher_no'
                        },
                        {
                            data: 'emp_from'
                        },
                        {
                            data: 'name'
                        },
                        {
                            data: 'voucher_type'
                        },
                        {
                            data: 'voucher_amount'
                        },
                        {
                            data: 'details'
                        },
                        {
                            data: 'created_at'
                        },
                        {
                            data: 'action'
                        },
                    ],
                    language: {
                        search: `<span>${__('search')}:</span> _INPUT_`,
                        searchPlaceholder: `${__('search')}...`,
                        lengthMenu: `${__('show')} _MENU_ ${__('entries')}`,
                        info: `${__('showing')} _START_ ${__('to')} _END_ ${__('of')} _TOTAL_ ${__('entries')}`,
                        infoEmpty: `${__('showing')} 0 ${__('to')} 0 ${__('of')} 0 ${__('entries')}`,
                        infoFiltered: `(${__('filtered_from')} _MAX_ ${__('total_entries')})`,
                        paginate: {
                            first: __('first'),
                            last: __('last'),
                            next: __('next'),
                            previous: __('previous')
                        },
                        emptyTable: __('no_data_available_in_table'),
                        zeroRecords: __('no_matching_records_found'),
                        processing: `<div class="spinner-border text-primary" role="status"><span class="visually-hidden">${__('loading')}...</span></div>`
                    }

                });
                $('#paymenttype').change(function() {
                    table.draw();
                });
                $('#search').keyup(function() {
                    table.search($(this).val()).draw();
                });

                $('#vouchers_vac_filter').remove();

                /* buildSelect(table);
                 table.on('draw', function() {
                     buildSelect(table);
                 });*/

            });


            jQuery(function($) {
                $('.autonumber').autoNumeric('init');
            });
            jQuery.browser = {};
            (function() {
                jQuery.browser.msie = false;
                jQuery.browser.version = 0;
                if (navigator.userAgent.match(/MSIE ([0-9]+)\./)) {
                    jQuery.browser.msie = true;
                    jQuery.browser.version = RegExp.$1;
                }
            })();


            function buildSelect(table) {
                var counter = 0;
                table.columns([0, 1, 2]).every(function() {
                    var column = table.column(this, {
                        search: 'applied'
                    });
                    counter++;
                    var select = $('<select><option value=""></option></select>')
                        .appendTo($('#dropdown' + counter).empty())
                        .on('change', function() {
                            var val = $.fn.dataTable.util.escapeRegex(
                                $(this).val()
                            );
                            column
                                .search(val ? '^' + val + '$' : '', true, false)
                                .draw();
                        });
                    column.data().unique().sort().each(function(d, j) {
                        select.append('<option value="' + d + '">' + d + '</option>');
                    });
                    var currSearch = column.search();
                    if (currSearch) {
                        select.val(column.data().unique().toArray().find((e) => e.match(new RegExp(currSearch))));
                    }
                });
            }
        </script>

    </body>

    </html>
<?php } ?>
x