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
        <title><?= $site_title ?> - All Cars</title>
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
        <link href="./plugins/bootstrap-select/css/bootstrap-select.min.css" rel="stylesheet" />
        <link href="./plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
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
                                    <!-- <a href="add_car.php" class="btn btn-primary waves-effect"><i class="mdi mdi-car"></i> Add New Car</a> -->
                                    <h4 class="m-t-0 header-title">All Registerd Cars</h4>
                                    <table id="employee_vac" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                        <thead>
                                            <tr>
                                                <th><?=__('more') ?>ID</th>
                                                <th><?=__('maker_name') ?></th>
                                                <th><?=__('model') ?></th>
                                                <th><?=__('made_year') ?></th>
                                                <th><?=__('plate_no') ?></th>
                                                <th><?=__('type') ?></th>
                                                <th><?=__('remarks') ?></th>
                                                <th width="50"><?=__('action') ?></th>

                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $sql_emp_vac = "
SELECT `cars`.*, `car_maker`.`maker` AS `maker_name`, `car_maker`.`logo_pos`, `car_model`.`model`, `car_model`.`id` AS `mdid`, `car_maker`.`id` AS `mkid`
FROM `cars`
LEFT JOIN `car_maker` ON `car_maker`.`id` = `cars`.`maker_name`
LEFT JOIN `car_model` ON `car_model`.`id` = `cars`.`model` 
WHERE `cars`.`status` = '1';
";
                                            $query_emp_vac = mysqli_query($conDB, $sql_emp_vac);

                                            while ($rec = mysqli_fetch_array($query_emp_vac)) {
                                                $id_car = $rec["id"];
                                                $maker_name = $rec["maker_name"];
                                                $model = $rec["model"];
                                                $made_year = $rec["made_year"];
                                                $plate_no = $rec["plate_no"];
                                                $type = $rec["type"];
                                                $remarks = $rec["remarks"];
                                                $status = $rec["status"];
                                                $mdid = $rec["mdid"];
                                                $mkid = $rec["mkid"];

                                                //	$times_reg = strtotime("$date_emp");
                                                //	$datevac = date('d, M Y', $times_reg);

                                                $timestamp_reg = strtotime("$datereg");
                                                $date_reg = date('d, M Y', $timestamp_reg);

                                            ?>
                                                <tr <?= ($status != "1") ? "class='table-danger'" : false; ?>>
                                                    <th><?= $id_car; ?></th>
                                                    <th><?= $maker_name; ?></th>
                                                    <th><?= $model; ?></th>
                                                    <th><?= $made_year; ?></th>
                                                    <th><?= $plate_no; ?></th>
                                                    <th><?=__(strtolower($type)) ?></th>
                                                    <th><?= $remarks; ?></th>
                                                    <th>
                                                        <div class='btn-group dropdown'>
                                                            <a href='javascript: void(0);' class='table-action-btn dropdown-toggle arrow-none btn btn-light btn-sm' data-toggle='dropdown' aria-expanded='false'><i class='mdi mdi-dots-horizontal'></i></a>
                                                            <div class='dropdown-menu dropdown-menu-right' x-placement='bottom-end'>
                                                                <a href='./view_car.php?id=<?= $rec['id'] ?>' class='dropdown-item text-dark'><i class="mdi mdi-eye-outline mr-2"></i></i><?= __('open') ?></a>
                                                                <a href='javascript:void(0);' class='dropdown-item text-custom editCarAttr' data-id="<?= $rec['id'] ?>" data-maker_name="<?= $rec['mkid'] ?>" data-model="<?= $rec['mdid'] ?>" data-made_year="<?= $rec['made_year'] ?>" data-plate_no="<?= $rec['plate_no'] ?>" data-type="<?= $rec['type'] ?>" data-remarks="<?= $rec['remarks'] ?>" data-status="<?= $rec['status'] ?>"><i class='fa fa-edit mr-2 font-18 vertical-middle'></i><?= __('edit') ?></a>
                                                                <?php if ($user_type == $access1) { ?>
                                                                    <a href='javascript:void(0);' class='dropdown-item  text-danger deleteAjax' data-id='<?= $rec['id'] ?>' data-tbl='cars' data-file='0'><i class='fa fa-trash mr-2 font-18 vertical-middle'></i><?= __('delete') ?></a>
                                                                <?php } ?>
                                                            </div>
                                                        </div>
                                                    </th>
                                                </tr>
                                            <?php } ?>
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
        <!-- <script src="./plugins/bootstrap-inputmask/bootstrap-inputmask.min.js" type="text/javascript"></script> -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/3.3.4/jquery.inputmask.bundle.min.js" type="text/javascript"></script>
        <script src="./plugins/autoNumeric/autoNumeric.js" type="text/javascript"></script>


        <script src="./plugins/moment/moment.js"></script>
        <script src="./plugins/bootstrap-timepicker/bootstrap-timepicker.js"></script>
        <script src="./plugins/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js"></script>
        <script src="./plugins/clockpicker/js/bootstrap-clockpicker.min.js"></script>
        <script src="./plugins/bootstrap-daterangepicker/daterangepicker.js"></script>
        <script src="./plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>

        <!-- App js -->
        <!-- <script src="assets/pages/jquery.form-pickers.init.js"></script> -->

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

        <script src="./plugins/select2/js/select2.min.js" type="text/javascript"></script>
        <script src="./plugins/bootstrap-select/js/bootstrap-select.js" type="text/javascript"></script>

        <!-- Responsive examples -->
        <script src="./plugins/datatables/dataTables.responsive.min.js"></script>
        <script src="./plugins/datatables/responsive.bootstrap4.min.js"></script>

        <script type="text/javascript" src="./plugins/autocomplete/jquery.autocomplete.min.js"></script>

        <!-- Selection table -->
        <script src="./plugins/datatables/dataTables.select.min.js"></script>


        <!-- App js -->
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js"></script>

        <script type="text/javascript">
            $(document).ready(function() {

                var buttonConfig = [];
                var exportTitle = "All Cars"
                buttonConfig.push({
                    extend: 'excel',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5]
                    },
                    title: exportTitle,
                    className: 'btn-success'
                });
                buttonConfig.push({
                    extend: 'pdf',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5]
                    },
                    title: exportTitle,
                    className: 'btn-danger'
                });
                buttonConfig.push({
                    extend: 'print',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5]
                    },
                    title: exportTitle,
                    className: 'btn-dark'
                });
                buttonConfig.push({
                    text: '<i class="mdi mdi-car-connected"></i> Add Car',
                    action: function(e, dt, button, config) {
                        addCarFunc()
                    },
                    className: 'btn-info'
                });
                /*
                        buttonConfig.push({text: '<i class="mdi mdi-library-plus"></i> Add Car Model', action: function ( e, dt, button, config ) { addCarFunc() } ,className: 'btn-dark'});*/

                $('form').parsley();

                //Buttons examples
                var table = $('#employee_vac').DataTable({
                    lengthChange: false,
                    buttons: buttonConfig,
                    order: [
                        [0, "desc"]
                    ],
                    "columnDefs": [{
                        targets: [0],
                        visible: false,
                        searchable: false
                    }, ],
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

            $(document).ready(function() {
                $("input[name$='note']").click(function() {
                    var value = $(this).val();
                    if (value == 'Encashed') {
                        $("#return_date").show();
                        $("#note").hide();
                        $("#return_date").removeAttr('required');
                        $("#permit_no").removeAttr('required');
                    } else if (value == 'Fly') {
                        //document.getElementById("pet_id").required = true;
                        $("#return_date").attr('required', '');
                        $("#permit_no").attr('required', '');
                        $("#note").show();
                        //    $("#pet_id_box").hide();
                    }
                });
                $("#return_date").removeAttr('required');
                //  	$("#pet_id_box").show();
                $("#note").hide();
            });
        </script>

    </body>

    </html>
<?php } ?>