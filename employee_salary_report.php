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
        <title><?= $site_title ?> - Active Employees Salary Report</title>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
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
            .esr-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-bottom: 20px; }
            @media (max-width: 991px) { .esr-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
            .esr-stats .sr-stat { background: var(--sr-surface); }
            .esr-stats .icon-saudi_riyal { font-size: .7em !important; opacity: .7; }
            .sr-page table.sr-table tfoot th { padding: 12px 14px !important; border: 0 !important; border-top: 2px solid var(--sr-border) !important; background: var(--sr-surface-2) !important; color: var(--sr-text); font-weight: 700; white-space: nowrap; }
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
                        $sql = "SELECT
                            e.emp_id,
                            e.name,
                            s.sponsor,
                            es.basic,
                            es.housing,
                            es.transport,
                            es.food,
                            es.misc,
                            es.cashier,
                            es.fuel,
                            es.tel,
                            es.other,
                            es.guard,
                            (es.basic + es.housing + es.transport + es.food + es.misc + es.cashier + es.fuel + es.tel + es.other + es.guard) AS total_salary,
                            bl.name AS bank_name,
                            bl.bank_name_ar AS bank_name_ar,
                            d.dep_nme,
                            d.dep_nme_ar,
                            c.comp_name
                        FROM employees AS e
                        LEFT JOIN emp_salary AS es ON e.emp_id = es.emp_id AND es.status = 1
                        LEFT JOIN sponsorship AS s ON e.emp_sup_type = s.id
                        LEFT JOIN bank_list AS bl ON e.bank_name = bl.bnk_id
                        LEFT JOIN department AS d ON e.dept = d.id
                        LEFT JOIN companies AS c ON e.comp_no = c.comp_id
                        WHERE e.status = 1" . getCompanyFilterSQL('e.comp_no', true) . getDepartmentFilterSQL('e.dept', true) . getEmployeeFilterSQL('e.emp_id', true) . "
                        ORDER BY e.name ASC";
                        $query = mysqli_query($conDB, $sql);
                        $esr_rows = [];
                        $esr_total = 0.0;
                        $esr_basic = 0.0;
                        while ($query && ($row = mysqli_fetch_assoc($query))) {
                            $esr_rows[] = $row;
                            $esr_total += (float)($row['total_salary'] ?? 0);
                            $esr_basic += (float)($row['basic'] ?? 0);
                        }
                        $esr_count = count($esr_rows);
                        $esr_cols = ['basic', 'housing', 'transport', 'food', 'misc', 'cashier', 'fuel', 'tel', 'other', 'guard'];
                        ?>
                        <div class="sr-head">
                            <div>
                                <h1><?=__('active_employees_salary_report') ?? 'Active Employees Salary Report' ?></h1>
                                <p><?= __('salary_report_sub', 'Current salary breakdown of every active employee.') ?></p>
                            </div>
                        </div>

                        <div class="esr-stats">
                            <div class="sr-stat is-sky">
                                <div class="sr-stat-label"><?= __('employees', 'Employees') ?> <i class="mdi mdi-account-multiple"></i></div>
                                <div class="sr-stat-value"><?= number_format($esr_count) ?></div>
                            </div>
                            <div class="sr-stat is-green">
                                <div class="sr-stat-label"><?= __('total_salary') ?? 'Total Salary' ?> <i class="mdi mdi-cash-multiple"></i></div>
                                <div class="sr-stat-value"><?= number_format($esr_total, 2) ?> <i class="icon-saudi_riyal"></i></div>
                            </div>
                            <div class="sr-stat">
                                <div class="sr-stat-label"><?= __('basic') ?? 'Basic' ?> <i class="mdi mdi-cash"></i></div>
                                <div class="sr-stat-value"><?= number_format($esr_basic, 2) ?> <i class="icon-saudi_riyal"></i></div>
                            </div>
                            <div class="sr-stat is-amber">
                                <div class="sr-stat-label"><?= __('average_salary', 'Average Salary') ?> <i class="mdi mdi-scale-balance"></i></div>
                                <div class="sr-stat-value"><?= number_format($esr_count ? $esr_total / $esr_count : 0, 2) ?> <i class="icon-saudi_riyal"></i></div>
                            </div>
                        </div>

                        <div class="sr-card">
                            <div class="sr-filter-grid esr-filters" id="esrFilters" style="border-bottom: 1px solid var(--sr-border);">
                                <div><label><?=__('sponsor') ?? 'Sponsor' ?></label><div data-col="2"></div></div>
                                <div><label><?=__('bank_name') ?? 'Bank Name' ?></label><div data-col="14"></div></div>
                                <div><label><?=__('department') ?? 'Department' ?></label><div data-col="15"></div></div>
                                <div><label><?=__('company') ?? 'Company' ?></label><div data-col="16"></div></div>
                            </div>
                            <div class="sr-toolbar">
                                <div class="sr-search">
                                    <i class="mdi mdi-magnify"></i>
                                    <input type="search" id="esrSearch" placeholder="<?= __('search_placeholder') ?>" autocomplete="off">
                                </div>
                                <div class="sr-toolbar-right">
                                    <div id="srExportButtons"></div>
                                </div>
                            </div>

                            <div class="sr-table-wrap sr-table-scroll">
                                <table id="employee_salary_table" class="table sr-table nowrap" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th><?=__('employee_id') ?? 'Emp ID' ?></th>
                                            <th><?=__('name') ?? 'Name' ?></th>
                                            <th><?=__('sponsor') ?? 'Sponsor' ?></th>
                                            <th class="text-right"><?=__('basic') ?? 'Basic' ?></th>
                                            <th class="text-right"><?=__('housing') ?? 'Housing' ?></th>
                                            <th class="text-right"><?=__('transport') ?? 'Transport' ?></th>
                                            <th class="text-right"><?=__('food') ?? 'Food' ?></th>
                                            <th class="text-right"><?=__('misc') ?? 'Misc' ?></th>
                                            <th class="text-right"><?=__('cashier') ?? 'Cashier' ?></th>
                                            <th class="text-right"><?=__('fuel') ?? 'Fuel' ?></th>
                                            <th class="text-right"><?=__('tel') ?? 'Tel' ?></th>
                                            <th class="text-right"><?=__('other') ?? 'Other' ?></th>
                                            <th class="text-right"><?=__('guard') ?? 'Guard' ?></th>
                                            <th class="text-right"><?=__('total_salary') ?? 'Total Salary' ?></th>
                                            <th><?=__('bank_name') ?? 'Bank Name' ?></th>
                                            <th><?=__('department') ?? 'Department' ?></th>
                                            <th><?=__('company') ?? 'Company' ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($esr_rows as $row): ?>
                                            <tr>
                                                <td><span class="sr-chip sr-mono"><?= htmlspecialchars($row['emp_id'] ?? ''); ?></span></td>
                                                <td><span class="sr-person-name"><?= htmlspecialchars($row['name'] ?? ''); ?></span></td>
                                                <td><?= htmlspecialchars($row['sponsor'] ?? 'N/A'); ?></td>
                                                <?php foreach ($esr_cols as $col): ?>
                                                    <td class="sr-num"><?= number_format((float)($row[$col] ?? 0), 2); ?></td>
                                                <?php endforeach; ?>
                                                <td class="sr-num"><span class="sr-money"><?= number_format((float)($row['total_salary'] ?? 0), 2); ?></span></td>
                                                <td><?= htmlspecialchars(($is_rtl ?? false) ? ($row['bank_name_ar'] ?? $row['bank_name'] ?? 'N/A') : ($row['bank_name'] ?? 'N/A')); ?></td>
                                                <td><?= htmlspecialchars(($is_rtl ?? false) ? ($row['dep_nme_ar'] ?? $row['dep_nme'] ?? 'N/A') : ($row['dep_nme'] ?? 'N/A')); ?></td>
                                                <td><?= htmlspecialchars($row['comp_name'] ?? 'N/A'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="esr-total">
                                            <th><?= __('total', 'Total') ?></th><th></th><th></th>
                                            <?php for ($i = 3; $i <= 13; $i++): ?><th class="sr-num"></th><?php endfor; ?>
                                            <th></th>
                                            <th></th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
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
                var exportTitle = "Active Employees Salary Report - " + new Date().toLocaleDateString();
                var allCols = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16];
                function num(v) { return parseFloat(String(v).replace(/<[^>]*>/g, '').replace(/,/g, '')) || 0; }
                function money(n) { return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }

                var table = $('#employee_salary_table').DataTable({
                    dom: 'Brtip',
                    lengthChange: false,
                    pageLength: 25,
                    buttons: [
                        { extend: 'excel', text: '<i class="mdi mdi-file-excel"></i> Excel', exportOptions: { columns: allCols }, title: exportTitle, footer: true },
                        { extend: 'pdf', text: '<i class="mdi mdi-file-pdf"></i> PDF', exportOptions: { columns: [0, 1, 2, 13, 14, 15, 16] }, title: exportTitle, footer: true, orientation: 'landscape', pageSize: 'A4' },
                        { extend: 'print', text: '<i class="mdi mdi-printer"></i> ' + __('print'), exportOptions: { columns: allCols }, title: exportTitle, footer: true }
                    ],
                    order: [[1, 'asc']], // Order by Employee Name
                    columnDefs: [{ targets: [3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13], type: 'num-fmt' }],
                    // Column totals of the filtered rows
                    footerCallback: function() {
                        var api = this.api();
                        for (var c = 3; c <= 13; c++) {
                            var sum = api.column(c, { search: 'applied' }).data().reduce(function(a, b) { return a + num(b); }, 0);
                            $(api.column(c).footer()).text(money(sum));
                        }
                    },
                    initComplete: function() {
                        // Dropdown filters for Sponsor, Bank, Dept, Company columns
                        this.api().columns([2, 14, 15, 16]).every(function() {
                            var column = this;
                            var select = $('<select class="form-control"><option value="">All</option></select>')
                                .appendTo($('#esrFilters [data-col="' + column.index() + '"]').empty())
                                .on('change', function() {
                                    var val = $.fn.dataTable.util.escapeRegex($(this).val());
                                    column.search(val, true, false).draw();
                                });
                            column.data().unique().sort().each(function(d) {
                                if (d) select.append($('<option>').val($('<div>').html(d).text()).text($('<div>').html(d).text()));
                            });
                            $(select).select2({ allowClear: false, width: '100%' });
                        });
                    },
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
                        emptyTable: `<div class="sr-empty"><i class="mdi mdi-account-off"></i>No active employees found</div>`,
                        zeroRecords: `<div class="sr-empty"><i class="mdi mdi-magnify"></i>${__('no_matching_records_found')}</div>`,
                        processing: `<div class="spinner-border text-primary" role="status"><span class="sr-only">${__('loading')}...</span></div>`
                    }
                });

                table.buttons().container().appendTo('#srExportButtons');
                $('#esrSearch').on('input', function() { table.search(this.value).draw(); });
            });
        </script>

    </body>

    </html>
<?php } ?>
