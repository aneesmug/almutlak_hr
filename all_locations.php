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
        <title><?= $site_title ?> - <?=__('all_locations_title')?></title>
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

        <link href="./plugins/bootstrap-timepicker/hijri_css/bootstrap-datetimepicker.css" rel="stylesheet">
        <link href="./plugins/bootstrap-timepicker/hijri_css/bootstrap-datetimepicker.min.css" rel="stylesheet">

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
                                    <h4 class="m-t-0 header-title"><?=__('all_registered_locations_header')?></h4>
                                    <table id="employee_vac" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                        <thead>
                                            <tr>
                                                <th><?=__('sr_header')?></th>
                                                <th></th>
                                                <th><?=__('section_name_header')?></th>
                                                <th><?=__('department_header')?></th>
                                                <th><?=__('building_base_header')?></th>
                                                <th><?=__('building_size_header')?></th>
                                                <th><?=__('address_header')?></th>
                                                <th><?=__('devices_header')?></th>
                                                <?php if ($user_type == $access1) { ?>
                                                    <th><?=__('status_header')?></th>
                                                <?php } ?>
                                                <th width="60"><?=__('action_header')?></th>
                                            </tr>
                                        </thead>
                                        <!-- <h4 class="m-t-0 header-title"><?= $access2 ?></h4> -->
                                        <tbody>
                                            <?php
                                            if ($user_type == $access1 or $user_type == $access2) {
                                                $sql_loc = "SELECT `section`.*, COUNT(`machines`.`location_id`) AS `totalDvc`, `location_img`.`out_img` FROM `section` LEFT JOIN `machines` ON  `machines`.`location_id`= `section`.`id` LEFT JOIN `location_img` ON  `location_img`.`location_id`= `section`.`id` GROUP BY `section`.`id`";
                                                $query_loc = mysqli_query($conDB, $sql_loc);
                                            } else {
                                                $sql_loc = "SELECT `section`.*, COUNT(`machines`.`location_id`) AS `totalDvc`, `location_img`.`out_img` FROM `section` LEFT JOIN `machines` ON `machines`.`location_id`= `section`.`id` LEFT JOIN `location_img` ON  `location_img`.`location_id`= `section`.`id` WHERE `section`.`dept` = 'POS' OR `section`.`dept` = 'Maintenance' OR `section`.`dept` = 'Warehouse' AND `section`.`status`='A' GROUP BY `section`.`id` ORDER BY `section`.`section_name` REGEXP '^[^A-Za-z]' ASC, `section`.section_name";
                                            }
                                            $query_loc = mysqli_query($conDB, $sql_loc);

                                            while ($rec = mysqli_fetch_array($query_loc)) {
                                                $id = $rec["id"];
                                                $section_name = $rec["section_name"];
                                                $out_img = $rec["out_img"];
                                                $dept = $rec["dept"];
                                                $location_name = $rec["location_name"];
                                                $totalDvc = $rec["totalDvc"];
                                                $status = $rec["status"];
                                                $bulding_base = $rec["bulding_base"];
                                                $bulding_size = $rec["bulding_size"];

                                                //	$times_reg = strtotime("$date_emp");
                                                //	$datevac = date('d, M Y', $times_reg);

                                            ?>

                                                <tr <?= ($status != "1") ? "class='table-danger'" : false; ?>>
                                                    <td><?= $id ?></td>
                                                    <td><?= ($out_img) ? "<img src='$out_img' class='rounded-circle bx-shadow-lg' width='50'>" : __('no_image_text'); ?></td>
                                                    <td><?= $section_name ?></td>
                                                    <td><?= $dept; ?></td>
                                                    <td><?= $bulding_base; ?></td>
                                                    <td><?= $bulding_size; ?></td>
                                                    <td><?= $location_name; ?></td>
                                                    <td><?= $totalDvc; ?></td>
                                                    <?php if ($user_type == $access1) { ?>

                                                        <td><?php /* echo ($status == "A") ? "<a href='./includes/update_stus_loca.php?status=C&id={$id}'><span class='badge badge-success'>".__('active_status')."</span></a>" : "<a href='./includes/update_stus_loca.php?status=A&id={$id}'><span class='badge badge-danger'>".__('closed_status')."</span></a>" ; */ ?>
                                                            <?= ($status == "1") ? "<a href='javascript:void:(0);' data-status='0' data-id={$id} class='statusUpd'><span class='badge-border badge-border-success'>".__('active_status')."</span></a>" : "<a href='javascript:void:(0);' data-status='1' data-id={$id} class='statusUpd'><span class='badge-border badge-border-danger'>".__('closed_status')."</span></a>"; ?>
                                                        </td>
                                                    <?php } ?>
                                                    <td>

                                                        <div class='btn-group dropdown'>
                                                            <a href='javascript: void(0);' class='table-action-btn dropdown-toggle arrow-none btn btn-light btn-sm' data-toggle='dropdown' aria-expanded='false'><i class='mdi mdi-dots-horizontal'></i></a>
                                                            <div class='dropdown-menu dropdown-menu-right' x-placement='bottom-end'>
                                                                <a class='dropdown-item text-dark' href='./view_location.php?id=<?= $id ?>'><i class='mdi mdi-eye-outline mr-2 font-18 vertical-middle'></i><?=__('open_link')?></a>
                                                                <?php if ($user_type == $access1) { ?>
                                                                    <a href='javascript:void(0);' class='dropdown-item  text-danger deleteAjax' data-id='<?= $id ?>' data-tbl='section' data-file='0'><i class='fa fa-trash mr-2 font-18 vertical-middle'></i><?=__('delete_link')?></a>
                                                                <?php } ?>
                                                            </div>
                                                        </div>

                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th><?=__('sr_header')?></th>
                                                <th></th>
                                                <th><?=__('section_name_header')?></th>
                                                <th><?=__('department_header')?></th>
                                                <th><?=__('building_base_header')?></th>
                                                <th><?=__('building_size_header')?></th>
                                                <th><?=__('address_header')?></th>
                                                <th><?=__('devices_header')?></th>
                                                <?php if ($user_type == $access1) { ?>
                                                    <th><?=__('status_header')?></th>
                                                <?php } ?>
                                                <th width="60"><?=__('action_header')?></th>
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
        <script src="./plugins/bootstrap-timepicker/bootstrap-timepicker.js"></script>
        <script src="./plugins/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js"></script>
        <script src="./plugins/clockpicker/js/bootstrap-clockpicker.min.js"></script>
        <script src="./plugins/bootstrap-daterangepicker/daterangepicker.js"></script>
        <script src="./plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>

        <script src="./plugins/bootstrap-timepicker/hijri/bootstrap-hijri-datetimepicker.js"></script>
        <script src="./plugins/bootstrap-timepicker/hijri/bootstrap-hijri-datetimepicker.min.js"></script>
        <script src="./plugins/bootstrap-timepicker/hijri/bootstrap-hijri-datetimepickermin.js"></script>

        <!-- App js -->
        <script src="assets/pages/jquery.form-pickers.init.js"></script>
        <script src="assets/pages/jquery.form-hijri-pickers.init.js"></script>

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

        <!-- <link href="./plugins/sweet-alert/sweetalert2.min.css" rel="stylesheet" type="text/css" />
        <script src="./plugins/sweet-alert/sweetalert2.min.js"></script>
        <script src="assets/pages/jquery.sweet-alert.init.js"></script> -->


        <!-- App js -->
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js"></script>

        <script type="text/javascript">
            $(document).ready(function() {
                $('input[type="checkbox"]').click(function() {
                    var status = ($(this).is(':checked')) ? '1' : '0';
                    var id = $(this).val();
                    $.ajax({
                        url: "./includes/ajaxFile/update_loc_stus.php",
                        method: "POST",
                        data: {
                            status: status,
                            id: id,
                        },
                        success: function(data) {
                            swal({
                                title: "Updated!",
                                text: "Successfully update this user.",
                                type: "success",
                                allowOutsideClick: false
                            }).then(function(isConfirm) {
                                (isConfirm) ? location.reload(): ""
                            });
                        },
                    });
                });
            });
        </script>

        <script type="text/javascript">
            $(document).ready(function() {

                var buttonConfig = [];
                var exportTitle = "<?=__('all_locations_title')?>"
                buttonConfig.push({
                    extend: 'excel',
                    exportOptions: {
                        columns: [0, 1, 2]
                    },
                    title: exportTitle,
                    className: 'btn-success'
                });
                buttonConfig.push({
                    extend: 'pdf',
                    exportOptions: {
                        columns: [0, 1, 2]
                    },
                    title: exportTitle,
                    className: 'btn-danger'
                });
                buttonConfig.push({
                    extend: 'print',
                    exportOptions: {
                        columns: [0, 1, 2]
                    },
                    title: exportTitle,
                    className: 'btn-dark'
                });
                buttonConfig.push({
                    text: '<i class="fa fa-plus"></i> <?=__('add_location_button')?>',
                    action: function(e, dt, button, config) {
                        addlocarionFunc()
                    },
                    className: 'btn-info'
                });

                $('form').parsley();

                //Buttons examples
                var table = $('#employee_vac').DataTable({
                    lengthChange: false,
                    buttons: buttonConfig,
                    order: [
                        [0, "asc"]
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

            $(document).on('click', '.statusUpd', function(e) {
                e.preventDefault();
                var itemId = $(this).data('id');
                var status = $(this).data('status');
                Swal.fire({
                    title: '<?=__('update_status_confirm_title')?>',
                    text: "<?=__('update_status_confirm_text')?>",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: '<?=__('yes_update_button')?>',
                    showLoaderOnConfirm: true,
                    preConfirm: function() {
                        return new Promise(function(resolve) {
                            $.ajax({
                                    url: './includes/ajaxFile/update_loc_stus.php',
                                    type: 'GET',
                                    data: {
                                        id: itemId,
                                        status: status
                                    },
                                    cache: false,
                                    dataType: "json",
                                })
                                .done(function(response) {
                                    Swal.fire({
                                        title: response.title,
                                        text: response.message,
                                        icon: response.type,
                                        allowOutsideClick: false
                                    }).then(function(isConfirm) {
                                        (isConfirm) ? location.reload(): ""
                                    });
                                })
                                .fail(function() {
                                    Swal.fire(response.title, response.message, response.type);
                                });
                        });
                    },

                    allowOutsideClick: false
                })
            });
        </script>

    </body>

    </html>
<?php } ?>