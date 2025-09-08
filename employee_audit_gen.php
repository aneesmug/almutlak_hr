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
        <title><?= $site_title ?> - All Employees Bank Details</title>
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
                                    <h4 class="m-t-0 header-title"><?=__('employees_bank_details') ?></h4>
                                    <!-- <table id="employee_vac" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;"> -->
                                    <?php

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
                                    JOIN department AS d ON e.dept = d.id";
                                    $query = mysqli_query($conDB, $sql);
                                    $company_query = mysqli_query($conDB, "SELECT * FROM companies ORDER BY comp_name ASC");

                                    ?>

                                    <table id="employee_vac" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
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
                                        <tfoot>
                                            <tr>
                                                <!-- These footer columns will be replaced by dropdown filters -->
                                                <th></th>
                                                <th></th>
                                                <th></th>
                                                <th><?=__('bank_name') ?></th>
                                                <th></th>
                                                <th><?=__('department') ?></th>
                                                <th><?=__('company') ?></th>
                                            </tr>
                                        </tfoot>
                                        <tbody>
                                            <?php
                                            if (mysqli_num_rows($query) > 0) {
                                                while ($row = mysqli_fetch_assoc($query)) {
                                            ?>
                                                    <tr>
                                                        <td><?=$row['emp_id']; ?></td>
                                                        <td><?=$row['name']; ?></td>
                                                        <td><?=$row['iban']; ?></td>
                                                        <td><?=($is_rtl ?? false ? $row['bank_name_ar'] : $row['bank_name'])?></td>
                                                        <td><?=$row['bank_name_s']; ?></td>
                                                        <td><?=($is_rtl ?? false ? $row['dep_nme_ar'] : $row['dep_nme'])?></td>
                                                        <td><?=$row['comp_name']; ?></td>
                                                    </tr>
                                            <?php
                                                }
                                            } else {
                                                echo "<tr><td colspan='7'>No employees found</td></tr>";
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
                var exportTitle = "Generated for All Employees Bank Details - " + new Date().toLocaleDateString();
                buttonConfig.push({
                    extend: 'excel',
                    exportOptions: {
                        columns: [1, 2, 3, 4, 5, 6]
                    },
                    title: exportTitle,
                    className: 'btn-success',
                    footer: true
                });
                buttonConfig.push({
                    extend: 'pdf',
                    exportOptions: {
                        columns: [1, 2, 3, 4, 5, 6]
                    },
                    title: exportTitle,
                    className: 'btn-danger',
                    footer: true
                });
                buttonConfig.push({
                    extend: 'print',
                    exportOptions: {
                        columns: [1, 2, 3, 4, 5, 6]
                    },
                    title: exportTitle,
                    className: 'btn-dark',
                    footer: true
                });

                //Buttons examples
                var table = $('#employee_vac').DataTable({
                    lengthChange: false,
                    buttons: buttonConfig,
                    order: [[1, 'asc']], // Order by Employee Name
                    initComplete: function() {
                        // This function adds the dropdown filters to the footer
                        this.api().columns([3, 5, 6]).every(function() { // Target Bank, Dept, Company columns
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
                                    // Removed the .substr(0, 30) to show the full text
                                    select.append('<option value="' + d + '">' + d + '</option>')
                                }
                            });
                            // --- Initialize Select2 ---
                            $(select).select2({
                                // placeholder: 'Select an option',
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
                    .appendTo('#employee_vac_wrapper .col-md-6:eq(0)');

            });
        </script>

    </body>

    </html>
<?php } ?>