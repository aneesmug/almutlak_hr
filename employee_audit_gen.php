<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';
$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='" . $username . "'");
if (mysqli_num_rows($query) == 1) {
    include("./includes/avatar_select.php");

?>
    <!doctype html>
    <html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

    <head>
        <meta charset="utf-8" />
        <title><?= $site_title ?> - <?=__('employees_bank_details') ?></title>
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
        <!-- DataTables -->
        <link href="./plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
        <link href="./plugins/datatables/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" />
        <!-- Responsive datatable examples -->
        <link href="./plugins/datatables/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />

        <!-- Multi Item Selection examples -->
        <link href="./plugins/datatables/select.bootstrap4.min.css" rel="stylesheet" type="text/css" />
        <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />

        <!-- App css -->
        <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/smart_request.css?v=<?= @filemtime(__DIR__ . '/assets/css/smart_request.css') ?>" rel="stylesheet" type="text/css" />
        <style>
            .eag-stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin-bottom: 20px; }
            @media (max-width: 767px) { .eag-stats { grid-template-columns: 1fr; } }
            .eag-stats .sr-stat { background: var(--sr-surface); }
        </style>
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
                        <?php
                        // Add company, department, and employee filters based on user's access
                        $company_filter = getCompanyFilterSQL('e.comp_no', true);
                        $department_filter = getDepartmentFilterSQL('e.dept', true);
                        $employee_filter = getEmployeeFilterSQL('e.emp_id', true);

                        $sql = "SELECT
                            e.emp_id,
                            e.name,
                            e.iban,
                            bl.name AS bank_name,
                            bl.bank_name_ar AS bank_name_ar,
                            bl.bank_name_s,
                            d.dep_nme,
                            d.dep_nme_ar,
                            c.comp_name,
                            e.comp_no
                        FROM employees AS e
                        JOIN bank_list AS bl ON e.bank_name = bl.bnk_id
                        JOIN companies AS c ON e.comp_no = c.comp_id
                        JOIN department AS d ON e.dept = d.id
                        WHERE 1=1".$company_filter.$department_filter.$employee_filter;
                        $query = mysqli_query($conDB, $sql);
                        $eag_rows = [];
                        $eag_banks = [];
                        while ($query && ($row = mysqli_fetch_assoc($query))) {
                            $eag_rows[] = $row;
                            $eag_banks[$row['bank_name']] = true;
                        }
                        $eag_missing_iban = count(array_filter($eag_rows, function ($r) { return trim((string)$r['iban']) === ''; }));
                        ?>
                        <div class="sr-head">
                            <div>
                                <h1><?=__('employees_bank_details') ?></h1>
                                <p><?= __('employees_bank_details_sub', 'IBAN and bank of every employee, ready for payroll transfer files.') ?></p>
                            </div>
                        </div>

                        <div class="eag-stats">
                            <div class="sr-stat is-sky">
                                <div class="sr-stat-label"><?= __('employees', 'Employees') ?> <i class="mdi mdi-account-multiple"></i></div>
                                <div class="sr-stat-value"><?= number_format(count($eag_rows)) ?></div>
                            </div>
                            <div class="sr-stat is-green">
                                <div class="sr-stat-label"><?= __('banks', 'Banks') ?> <i class="mdi mdi-bank"></i></div>
                                <div class="sr-stat-value"><?= number_format(count($eag_banks)) ?></div>
                            </div>
                            <div class="sr-stat <?= $eag_missing_iban ? 'is-red' : '' ?>">
                                <div class="sr-stat-label"><?= __('missing_iban', 'Missing IBAN') ?> <i class="mdi mdi-alert-circle-outline"></i></div>
                                <div class="sr-stat-value"><?= number_format($eag_missing_iban) ?></div>
                            </div>
                        </div>

                        <div class="sr-card">
                            <div class="sr-filter-grid" id="eagFilters" style="border-bottom: 1px solid var(--sr-border);">
                                <div><label><?=__('bank_name') ?></label><div data-col="3"></div></div>
                                <div><label><?=__('department') ?></label><div data-col="5"></div></div>
                                <div><label><?=__('company') ?></label><div data-col="6"></div></div>
                            </div>
                            <div class="sr-toolbar">
                                <div class="sr-search">
                                    <i class="mdi mdi-magnify"></i>
                                    <input type="search" id="eagSearch" placeholder="<?= __('search_placeholder') ?>" autocomplete="off">
                                </div>
                                <div class="sr-toolbar-right">
                                    <div id="srExportButtons"></div>
                                </div>
                            </div>
                            <div class="sr-table-wrap">
                                <table id="employee_vac" class="table sr-table dt-responsive nowrap" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th><?=__('employee_id') ?></th>
                                            <th><?=__('name') ?></th>
                                            <th><?=__('iban') ?></th>
                                            <th><?=__('bank_name') ?></th>
                                            <th><?=__('bank_swift_code') ?></th>
                                            <th><?=__('department') ?></th>
                                            <th><?=__('company') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($eag_rows as $row): ?>
                                            <tr>
                                                <td><span class="sr-chip sr-mono"><?= htmlspecialchars($row['emp_id']); ?></span></td>
                                                <td><span class="sr-person-name"><?= htmlspecialchars($row['name']); ?></span></td>
                                                <td><?php if (trim((string)$row['iban']) !== ''): ?><span class="sr-mono copyToClipboard" title="Copy"><?= htmlspecialchars($row['iban']); ?></span><?php else: ?><span class="sr-pill sr-pill-xs tone-red"><?= __('missing_iban', 'Missing IBAN') ?></span><?php endif; ?></td>
                                                <td><?= htmlspecialchars(($is_rtl ?? false) ? $row['bank_name_ar'] : $row['bank_name']) ?></td>
                                                <td><span class="sr-mono"><?= htmlspecialchars($row['bank_name_s']); ?></span></td>
                                                <td><?= htmlspecialchars(($is_rtl ?? false) ? $row['dep_nme_ar'] : $row['dep_nme']) ?></td>
                                                <td><?= htmlspecialchars($row['comp_name']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
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
        <script src="./plugins/bootstrap-timepicker/bootstrap-timepicker.js"></script>
        <script src="./plugins/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js"></script>
        <script src="./plugins/clockpicker/js/bootstrap-clockpicker.min.js"></script>
        <script src="./plugins/bootstrap-daterangepicker/daterangepicker.js"></script>
        <script src="./plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>

        <!-- App js -->
        <script src="assets/pages/jquery.form-pickers.init.js"></script>

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

        <!-- Key Tables -->
        <script src="./plugins/datatables/dataTables.keyTable.min.js"></script>

        <!-- Responsive examples -->
        <script src="./plugins/datatables/dataTables.responsive.min.js"></script>
        <script src="./plugins/datatables/responsive.bootstrap4.min.js"></script>

        <!-- Selection table -->
        <script src="./plugins/datatables/dataTables.select.min.js"></script>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

        <!-- App js -->
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>


        <script type="text/javascript">
            $(document).ready(function() {
                var exportTitle = "Generated for All Employees Bank Details - " + new Date().toLocaleDateString();
                var cols = [1, 2, 3, 4, 5, 6];
                function txt(html) { return $('<div>').html(html).text(); }

                var table = $('#employee_vac').DataTable({
                    dom: 'Brtip',
                    lengthChange: false,
                    pageLength: 25,
                    responsive: true,
                    buttons: [
                        { extend: 'excel', text: '<i class="mdi mdi-file-excel"></i> Excel', exportOptions: { columns: cols }, title: exportTitle },
                        { extend: 'pdf', text: '<i class="mdi mdi-file-pdf"></i> PDF', exportOptions: { columns: cols }, title: exportTitle },
                        { extend: 'print', text: '<i class="mdi mdi-printer"></i> ' + __('print'), exportOptions: { columns: cols }, title: exportTitle }
                    ],
                    order: [[1, 'asc']], // Order by Employee Name
                    initComplete: function() {
                        // Dropdown filters for Bank, Dept, Company columns
                        this.api().columns([3, 5, 6]).every(function() {
                            var column = this;
                            var select = $('<select class="form-control"><option value="">All</option></select>')
                                .appendTo($('#eagFilters [data-col="' + column.index() + '"]').empty())
                                .on('change', function() {
                                    var val = $.fn.dataTable.util.escapeRegex($(this).val());
                                    column.search(val, true, false).draw();
                                });
                            column.data().unique().sort().each(function(d) {
                                if (d) select.append($('<option>').val(txt(d)).text(txt(d)));
                            });
                            $(select).select2({ allowClear: false, width: '100%' });
                        });
                    },
                    language: {
                        info: `${__('showing')} _START_ ${__('to')} _END_ ${__('of')} _TOTAL_ ${__('entries')}`,
                        infoEmpty: `${__('showing')} 0 ${__('to')} 0 ${__('of')} 0 ${__('entries')}`,
                        infoFiltered: '',
                        paginate: {
                            first: __('first'),
                            last: __('last'),
                            next: '<i class="mdi mdi-chevron-right"></i>',
                            previous: '<i class="mdi mdi-chevron-left"></i>'
                        },
                        emptyTable: `<div class="sr-empty"><i class="mdi mdi-bank"></i>No employees found</div>`,
                        zeroRecords: `<div class="sr-empty"><i class="mdi mdi-magnify"></i>${__('no_matching_records_found')}</div>`
                    }
                });

                table.buttons().container().appendTo('#srExportButtons');
                $('#eagSearch').on('input', function() { table.search(this.value).draw(); });
            });
        </script>

    </body>

    </html>
<?php } ?>