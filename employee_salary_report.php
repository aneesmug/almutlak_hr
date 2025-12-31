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
                <div class="content">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-12">
                                <div class="card-box table-responsive">
                                    <h4 class="m-t-0 header-title"><?=__('active_employees_salary_report') ?? 'Active Employees Salary Report' ?></h4>
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
                                    WHERE e.status = 1" . getCompanyFilterSQL('e.comp_no', true) . "
                                    ORDER BY e.name ASC";
                                    $query = mysqli_query($conDB, $sql);

                                    ?>

                                    <table id="employee_salary_table" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                        <thead>
                                            <tr>
                                                <th><?=__('employee_id') ?? 'Emp ID' ?></th>
                                                <th><?=__('name') ?? 'Name' ?></th>
                                                <th><?=__('sponsor') ?? 'Sponsor' ?></th>
                                                <th><?=__('basic') ?? 'Basic' ?></th>
                                                <th><?=__('housing') ?? 'Housing' ?></th>
                                                <th><?=__('transport') ?? 'Transport' ?></th>
                                                <th><?=__('food') ?? 'Food' ?></th>
                                                <th><?=__('misc') ?? 'Misc' ?></th>
                                                <th><?=__('cashier') ?? 'Cashier' ?></th>
                                                <th><?=__('fuel') ?? 'Fuel' ?></th>
                                                <th><?=__('tel') ?? 'Tel' ?></th>
                                                <th><?=__('other') ?? 'Other' ?></th>
                                                <th><?=__('guard') ?? 'Guard' ?></th>
                                                <th><?=__('total_salary') ?? 'Total Salary' ?></th>
                                                <th><?=__('bank_name') ?? 'Bank Name' ?></th>
                                                <th><?=__('department') ?? 'Department' ?></th>
                                                <th><?=__('company') ?? 'Company' ?></th>
                                            </tr>
                                        </thead>
                                        <tfoot>
                                            <tr>
                                                <th></th>
                                                <th></th>
                                                <th><?=__('sponsor') ?? 'Sponsor' ?></th>
                                                <th></th>
                                                <th></th>
                                                <th></th>
                                                <th></th>
                                                <th></th>
                                                <th></th>
                                                <th></th>
                                                <th></th>
                                                <th></th>
                                                <th></th>
                                                <th></th>
                                                <th><?=__('bank_name') ?? 'Bank Name' ?></th>
                                                <th><?=__('department') ?? 'Department' ?></th>
                                                <th><?=__('company') ?? 'Company' ?></th>
                                            </tr>
                                        </tfoot>
                                        <tbody>
                                            <?php
                                            if (mysqli_num_rows($query) > 0) {
                                                while ($row = mysqli_fetch_assoc($query)) {
                                            ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($row['emp_id'] ?? ''); ?></td>
                                                        <td><?= htmlspecialchars($row['name'] ?? ''); ?></td>
                                                        <td><?= htmlspecialchars($row['sponsor'] ?? 'N/A'); ?></td>
                                                        <td><?= number_format($row['basic'] ?? 0, 2); ?></td>
                                                        <td><?= number_format($row['housing'] ?? 0, 2); ?></td>
                                                        <td><?= number_format($row['transport'] ?? 0, 2); ?></td>
                                                        <td><?= number_format($row['food'] ?? 0, 2); ?></td>
                                                        <td><?= number_format($row['misc'] ?? 0, 2); ?></td>
                                                        <td><?= number_format($row['cashier'] ?? 0, 2); ?></td>
                                                        <td><?= number_format($row['fuel'] ?? 0, 2); ?></td>
                                                        <td><?= number_format($row['tel'] ?? 0, 2); ?></td>
                                                        <td><?= number_format($row['other'] ?? 0, 2); ?></td>
                                                        <td><?= number_format($row['guard'] ?? 0, 2); ?></td>
                                                        <td><strong><?= number_format($row['total_salary'] ?? 0, 2); ?></strong></td>
                                                        <td><?= htmlspecialchars(($is_rtl ?? false) ? ($row['bank_name_ar'] ?? $row['bank_name'] ?? 'N/A') : ($row['bank_name'] ?? 'N/A')); ?></td>
                                                        <td><?= htmlspecialchars(($is_rtl ?? false) ? ($row['dep_nme_ar'] ?? $row['dep_nme'] ?? 'N/A') : ($row['dep_nme'] ?? 'N/A')); ?></td>
                                                        <td><?= htmlspecialchars($row['comp_name'] ?? 'N/A'); ?></td>
                                                    </tr>
                                            <?php
                                                }
                                            } else {
                                                echo "<tr><td colspan='17'>No active employees found</td></tr>";
                                            }
                                            ?>
                                        </tbody>
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
        <script src="assets/js/jquery.app.js"></script>


        <script type="text/javascript">
            $(document).ready(function() {
                var buttonConfig = [];
                var exportTitle = "Active Employees Salary Report - " + new Date().toLocaleDateString();
                buttonConfig.push({
                    extend: 'excel',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16]
                    },
                    title: exportTitle,
                    className: 'btn-success',
                    footer: true
                });
                buttonConfig.push({
                    extend: 'pdf',
                    exportOptions: {
                        columns: [0, 1, 2, 13, 14, 15, 16]
                    },
                    title: exportTitle,
                    className: 'btn-danger',
                    footer: true,
                    orientation: 'landscape',
                    pageSize: 'A4'
                });
                buttonConfig.push({
                    extend: 'print',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16]
                    },
                    title: exportTitle,
                    className: 'btn-dark',
                    footer: true
                });

                //Buttons examples
                var table = $('#employee_salary_table').DataTable({
                    lengthChange: false,
                    buttons: buttonConfig,
                    order: [[1, 'asc']], // Order by Employee Name
                    initComplete: function() {
                        // This function adds the dropdown filters to the footer
                        this.api().columns([2, 14, 15, 16]).every(function() { // Target Sponsor, Bank, Dept, Company columns
                            var column = this;
                            // Create a select dropdown
                            var select = $('<select class="form-control form-control-sm"><option value="">All</option></select>')
                                .appendTo($(column.footer()).empty()) // Append it to the column footer
                                .on('change', function() {
                                    // On change, perform the search
                                    var val = $.fn.dataTable.util.escapeRegex($(this).val());
                                    // Perform a "contains" (LIKE) search using regex.
                                    column.search(val, true, false).draw();
                                });
                            // Populate the dropdown with unique values from the column
                            column.data().unique().sort().each(function(d, j) {
                                if(d) { // Ensure empty values aren't added
                                    select.append('<option value="' + d + '">' + d + '</option>')
                                }
                            });
                            // --- Initialize Select2 ---
                            $(select).select2({
                                allowClear: false
                            });
                            // --- End Select2 Initialization ---
                        });
                    },
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

                table.buttons().container()
                    .appendTo('#employee_salary_table_wrapper .col-md-6:eq(0)');

            });
        </script>

    </body>

    </html>
<?php } ?>
