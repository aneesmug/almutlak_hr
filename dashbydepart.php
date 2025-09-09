<?php
require_once __DIR__ . '/includes/db.php';

require_once __DIR__ . '/includes/session_check.php';
$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='" . $username . "'");
if (mysqli_num_rows($query) == 1) {
    include("./includes/avatar_select.php");

$color = array("primary", "success", "info", "warning", "danger", "dark");

?>
    <!doctype html>
    <html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

    <head>
        <meta charset="utf-8" />
        <title><?= $site_title ?> - Dashboard</title>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <!--        <meta content="A fully featured admin theme which can be used to build CRM, CMS, etc." name="description" />-->
        <meta content="Anees Afzal" name="author" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />

        <!-- App favicon -->
        <link rel="shortcut icon" href="assets/images/favicon.ico">

        <!-- App css -->
        <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />

        <!-- DataTables -->
        <link href="./plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
        <link href="./plugins/datatables/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" />
        <!-- Responsive datatable examples -->
        <link href="./plugins/datatables/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />
        <script src="assets/js/modernizr.min.js"></script>

        <style>
            /* Ensure table header and body align */
            .dataTables_wrapper .dataTables_scroll {
                overflow: auto;
                width: 100% !important;
            }

            /* Fix header row overlap */
            .dataTables_scrollHeadInner {
                width: 100% !important;
            }

            .dataTables_scrollHeadInner table {
                width: 100% !important;
            }

            /* Prevent body content from overflowing */
            .dataTables_scrollBody {
                width: 100% !important;
                overflow: hidden !important;
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

                <div class="slimScrollDiv active" id="remove-scroll">

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

                        <div class="col-xl-12">
                            <div class="card-box">
                                <h4 class="header-title m-t-0 m-b-30"><?= __('all_employees_grouping') ?></h4>
                                <ul class="nav nav-tabs tabs-bordered">
                                    <li class="nav-item">
                                        <a href="#bydepartment-b1" data-toggle="tab" aria-expanded="false" class="nav-link active show">
                                            <i class="fi-monitor mr-2"></i> <?= __('departments') ?>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="#bycompany-b1" data-toggle="tab" aria-expanded="false" class="nav-link">
                                            <i class="fa fa-layer-group mr-2"></i> <?= __('companies') ?>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="#bylist-b1" data-toggle="tab" aria-expanded="true" class="nav-link">
                                            <i class="fi-head mr-2"></i> <?= __('employees_list') ?>
                                        </a>
                                    </li>
                                </ul>
                                <div class="tab-content">
                                    <div class="tab-pane active show" id="bydepartment-b1">
                                        <!-- <div class="tab-pane" id="bydepartment-b1"> -->
                                        <?php  ?><div class="row text-center">

                                            <?php
                                            if ($user_type == "dept_user") {
                                                $querygrp = mysqli_query($conDB, "SELECT 
                                                    count(`employees`.`dept`) AS `empcountgrp`,
                                                    `employees`.`dept`, 
                                                    `department`.`dep_nme`,
                                                    `department`.`dep_nme_ar`,
                                                    `dept_clr`.`color`
                                                    FROM `employees` 
                                                    LEFT JOIN `dept_clr` ON `dept_clr`.`dept_name` = `employees`.`dept`
                                                    LEFT JOIN `department` ON `department`.`id` = `dept_clr`.`dept_name`
                                                    WHERE `employees`.`emp_sup_type`='mocha' 
                                                    AND `employees`.`status` = 1 
                                                    AND `employees`.`dept` = '" . $user_dept . "' 
                                                    GROUP BY `employees`.`dept`");
                                            } elseif ($user_type == 'administrator' or $user_type == 'hr' or $user_dept == 5) {
                                                $querygrp = mysqli_query($conDB, "SELECT 
                                                    count(`employees`.`dept`) AS `empcountgrp`,
                                                    `employees`.`dept`, 
                                                    `department`.`dep_nme`, 
                                                    `department`.`dep_nme_ar`,
                                                    `dept_clr`.`color`
                                                    FROM `employees` 
                                                    LEFT JOIN `dept_clr` ON `dept_clr`.`dept_name` = `employees`.`dept`
                                                    LEFT JOIN `department` ON `department`.`id` = `dept_clr`.`dept_name`
                                                    WHERE `employees`.`status` = 1
                                                    GROUP BY `employees`.`dept`");
                                            }
                                            // $querygrp = mysqli_query($conDB, "SELECT count(`dept`) AS `empcountgrp`,`dept` FROM `employees` WHERE `emp_sup_type`='mocha' AND `status` = 1 GROUP BY `dept`");
                                            while ($rec = mysqli_fetch_array($querygrp)) {
                                            ?>
                                                <div class="col-sm-4 col-xl-4" onclick="window.location.href='filter_employee_by_dept.php?page=1&status=1&dept=<?= $rec["dept"] ?>'" style="cursor: pointer;">
                                                    <?php  ?><div class="card-box widget-flat border-<?= $rec["color"] ?> bg-<?= $rec["color"] ?> text-white">
                                                        <?php /* ?><div class="card-box widget-flat bg-<?=$color[rand(0,5)]?> text-white" ><?php */ ?>
                                                        <i class="mdi mdi-account-convert"></i>
                                                        <h3 class="m-b-10"><?= $rec["empcountgrp"] ?></h3>
                                                        <p class="text-uppercase m-b-5 font-13 font-600"><?= ($is_rtl ?? false) ? $rec["dep_nme_ar"] : $rec["dep_nme"] ?></p>
                                                    </div>
                                                </div>
                                            <?php } //} 
                                            ?>

                                        </div>
                                    </div>
                                    <div class="tab-pane" id="bycompany-b1">
                                        <!-- <div class="tab-pane" id="bydepartment-b1"> -->
                                        <?php  ?><div class="row text-center">

                                            <?php
                                            if ($user_type == "dept_user") {
                                                $querygrp = mysqli_query($conDB, "SELECT 
                                                    count(`employees`.`dept`) AS `empcountgrp`,
                                                    `employees`.`comp_no`, 
                                                    `companies`.`comp_name`, 
                                                    `companies`.`comp_name_ar`, 
                                                    `companies`.`comp_id`
                                                    FROM `employees` 
                                                    LEFT JOIN `companies` ON `companies`.`comp_id` = `employees`.`comp_no`
                                                    WHERE `employees`.`status` = 1
                                                    AND `employees`.`dept` = '{$user_dept}'
                                                    GROUP BY `employees`.`comp_no`");
                                            } elseif ($user_type == 'administrator' or $user_type == 'hr' or $user_dept == 5) {
                                                $querygrp = mysqli_query($conDB, "SELECT 
                                                    count(`employees`.`dept`) AS `empcountgrp`,
                                                    `employees`.`comp_no`, 
                                                    `companies`.`comp_name`, 
                                                    `companies`.`comp_name_ar`, 
                                                    `companies`.`comp_id`
                                                    FROM `employees` 
                                                    LEFT JOIN `companies` ON `companies`.`comp_id` = `employees`.`comp_no`
                                                    WHERE `employees`.`status` = 1
                                                    GROUP BY `employees`.`comp_no`");
                                            }
                                            // $querygrp = mysqli_query($conDB, "SELECT count(`dept`) AS `empcountgrp`,`dept` FROM `employees` WHERE `emp_sup_type`='mocha' AND `status` = 1 GROUP BY `dept`");
                                            while ($rec = mysqli_fetch_array($querygrp)) {
                                            ?>
                                                <div class="col-sm-4 col-xl-4" onclick="window.location.href='filter_employee_by_comp.php?page=1&status=1&comp=<?= $rec["comp_no"] ?>'" style="cursor: pointer;">
                                                    <?php  ?><div class="card-box widget-flat border-<?=$color[rand(0,5)]?> bg-<?=$color[rand(0,5)]?> text-white">
                                                        <?php /* ?><div class="card-box widget-flat bg-<?=$color[rand(0,5)]?> text-white" ><?php */ ?>
                                                        <i class="mdi mdi-account-convert"></i>
                                                        <h3 class="m-b-10"><?= $rec["empcountgrp"] ?></h3>
                                                        <p class="text-uppercase m-b-5 font-13 font-600"><?= ($is_rtl ?? false) ? $rec["comp_name_ar"] : $rec["comp_name"] ?></p>
                                                    </div>
                                                </div>
                                            <?php } //} 
                                            ?>

                                        </div>
                                    </div>
                                    <div class="tab-pane" id="bylist-b1">
                                        <table id="employee_vac" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                            <thead>
                                                <tr>
                                                    <th width="50"><?= __('emp_id') ?></th>
                                                    <th><?= __('employee_name') ?></th>
                                                    <th><?= __('department') ?></th>
                                                    <th><?= __('iqama_id') ?></th>
                                                    <th><?= __('mobile') ?></th>
                                                    <th><?= __('date_of_birth') ?></th>
                                                    <th><?= __('sponsorship') ?></th>
                                                    <th><?= __('blood_group') ?></th>
                                                    <th><?= __('gender') ?></th>
                                                    <th width="80"><?= __('country') ?></th>
                                                    <th><?= __('joining_date') ?></th>
                                                    <th width="80"><?= __('action') ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                if ($user_type == "dept_user") {
                                                    $sql = "SELECT * FROM `employees` WHERE `status`=1 AND `fly`=0 AND `dept`='" . $user_dept . "' ";

                                                    //$_SERVER['emp_sup_type'] = $_GET['emp_sup_type'];
                                                } else {
                                                    $sql = "SELECT 
                                                            `emp`.*,
                                                            CASE 
                                                                WHEN `emp`.`sex` = 1 THEN 'male' 
                                                                WHEN `emp`.`sex` = 2 THEN 'female'
                                                                ELSE ''
                                                            END AS `sex`,  
                                                            `countries`.`name` AS `country_name`,
                                                            `department`.`dep_nme`,
                                                            `department`.`dep_nme_ar`,
                                                            `sponsorship`.`sponsor` AS `emp_sup_type`
                                                            FROM `employees` `emp`
                                                            LEFT JOIN `department` ON `department`.`id` = `emp`.`dept` 
                                                            LEFT JOIN `countries` ON `countries`.`id` = `emp`.`country` 
                                                            LEFT JOIN `sponsorship` ON `sponsorship`.`id` = `emp`.`emp_sup_type` 
                                                            WHERE `emp`.`status`=1 AND `emp`.`fly`=0 ";
                                                }
                                                $query = mysqli_query($conDB, $sql);

                                                while ($rec = mysqli_fetch_array($query)) {
                                                    $id = $rec["id"];
                                                    $name = $rec["name"];
                                                    $emp_id = $rec["emp_id"];
                                                    $iqama = $rec["iqama"];
                                                    $mobile = $rec["mobile"];
                                                    $email_gt = $rec["email"];
                                                    $salary = $rec["salary"];
                                                    $vacation_days = $rec["vacation_days"];
                                                    $joining_date = $rec["joining_date"];
                                                    $emp_avatar = $rec["avatar"];
                                                    $emp_status = $rec["status"];
                                                    $emp_status_fly = $rec["fly"];
                                                    $emptype = $rec["emptype"];
                                                    $dept = $rec["dep_nme"];
                                                    $dept_ar = $rec["dep_nme_ar"];
                                                    $blood_type = $rec["blood_type"];
                                                    $emp_sup_type = $rec["emp_sup_type"];
                                                    $date_dob_get = $rec["dob"];
                                                    $country_get = $rec["country_name"];
                                                    $sex_get = $rec["sex"];
                                                    $mar_status_get = $rec["mar_status"];


                                                    $sql_count = mysqli_query($conDB, "SELECT COUNT(*) `emp_id` FROM `emp_vacation` WHERE `emp_id`='" . $emp_id . "' ");
                                                    $status_cont = mysqli_fetch_array($sql_count)[0];

                                                    $sql_count_fly = mysqli_query($conDB, "SELECT COUNT(*) `emp_id` FROM `emp_vacation` WHERE `emp_id`='" . $emp_id . "' && `note`='Fly' ");
                                                    $cont_fly = mysqli_fetch_array($sql_count_fly)[0];

                                                    $sql_count_encashed = mysqli_query($conDB, "SELECT COUNT(*) `emp_id` FROM `emp_vacation` WHERE `emp_id`='" . $emp_id . "' && `note`='Encashed' ");
                                                    $cont_fly = mysqli_fetch_array($sql_count_encashed)[0];

                                                    $checkGander = ($sex_get == 'male') ? './assets/emp_pics/defult.png' : './assets/emp_pics/defultFemale.jpg';
                                                    $emp_avatar = (file_exists("./assets/emp_pics/" . explode("/", $emp_avatar)[3])) ? $emp_avatar : $checkGander;
                                                ?>
                                                    <tr>
                                                        <td><?= $emp_id; ?></td>
                                                        <td>
                                                            <img src="<?= $emp_avatar; ?>" class="rounded-circle bx-shadow-lg" width="50">
                                                             <span class='copyToClipboard'><?= (explode(" ", $rec["name"])[0]) . " " . (explode(" ", $rec["name"])[1]); ?></span> <i class='fa fa-clipboard'></i>
                                                        </td>
                                                        <td><?= ($is_rtl ?? false) ? $dept_ar : $dept ?></td>
                                                        <td><span class='copyToClipboard'><?= $iqama; ?></span> <i class='fa fa-clipboard'></i></td>
                                                        <td><span class='copyToClipboard'><?= $mobile; ?></span> <i class='fa fa-clipboard'></i></td>
                                                        <td><?= $date_dob_get; ?></td>
                                                        <td><?= $emp_sup_type; ?></td>
                                                        <td><?= $blood_type; ?></td>
                                                        <td><?= __($sex_get); ?></td>
                                                        <td><?= $country_get; ?></td>
                                                        <td><?= $joining_date; ?></td>
                                                        <td>
                                                            <div class='btn-group dropdown'>
                                                                <a href='javascript: void(0);' class='table-action-btn dropdown-toggle arrow-none btn btn-light btn-sm' data-toggle='dropdown' aria-expanded='false'><i class='mdi mdi-dots-horizontal'></i></a>
                                                                <div class='dropdown-menu dropdown-menu-right' x-placement='bottom-end'>
                                                                    <a class='dropdown-item text-dark' href='view_employee.php?emp_id=<?= $emp_id ?>'><i class='mdi mdi-eye-outline mr-2 font-18 vertical-middle'></i><?= __('open') ?></a>
                                                                    <?php
                                                                    if ($emp_status == "1") {
                                                                        if ($user_type <> "dept_user") {
                                                                    ?>
                                                                            <a href='edit_employee.php?emp_id=<?= $emp_id ?>' class='dropdown-item text-custom'><i class='fa fa-edit mr-2 font-18 vertical-middle'></i><?= __('edit') ?></a>
                                                                        <?php }
                                                                    }
                                                                    if ($user_type == $access1) { ?>
                                                                        <a href='javascript:void(0);' class='dropdown-item  text-danger' data-toggle="modal" data-target=".del_modal_sm_<?= $id ?>"><i class='fa fa-trash mr-2 font-18 vertical-middle'></i><?= __('vouchers') ?></a>
                                                                    <?php } ?>
                                                                </div>
                                                            </div>

                                                        </td>
                                                    </tr>
                                                <?php } ?>
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <th width="50"><?= __('emp_id') ?></th>
                                                    <th><?= __('employee_name') ?></th>
                                                    <th><?= __('department') ?></th>
                                                    <th><?= __('iqama_id') ?></th>
                                                    <th><?= __('mobile') ?></th>
                                                    <th><?= __('date_of_birth') ?></th>
                                                    <th><?= __('sponsorship') ?></th>
                                                    <th><?= __('blood_group') ?></th>
                                                    <th><?= __('gender') ?></th>
                                                    <th width="80"><?= __('country') ?></th>
                                                    <th><?= __('joining_date') ?></th>
                                                    <th width="80"><?= __('action') ?></th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
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
                // --- 1. Your Button Configuration ---
                // This section defines the export buttons (Excel, PDF, Print) for the table.
                var buttonConfig = [];
                var exportTitle = "Employee List"; // A title for the exported files.
                buttonConfig.push({
                    extend: 'excel',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10] // All columns except 'Action'
                    },
                    title: exportTitle,
                    className: 'btn-success'
                });
                buttonConfig.push({
                    extend: 'pdf',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10]
                    },
                    title: exportTitle,
                    className: 'btn-danger'
                });
                buttonConfig.push({
                    extend: 'print',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10]
                    },
                    title: exportTitle,
                    className: 'btn-dark'
                });

                // --- 2. Initialize the DataTable ---
                var table = $('#employee_vac').DataTable({
                    // --- ALL OPTIONS ARE NOW CORRECTLY PLACED INSIDE THIS OBJECT ---

                    lengthChange: false, // Hides the "Show X entries" dropdown.
                    buttons: buttonConfig, // Assigns the button configuration from above.

                    // --- MERGED AND CORRECTED initComplete ---
                    // All initialization logic is now in a single, correct function.
                    initComplete: function() {
                        var api = this.api();

                        // A) Create Column Filtering Dropdowns
                        // Targets Department, Sponsorship, Blood G., and Gender columns.
                        api.columns([2, 6, 7, 8, 9]).every(function() {
                            var column = this;
                            // The text from the <tfoot> is used as a placeholder initially.
                            var title = $(column.footer()).text();
                            var select = $('<select class="form-control form-control-sm"><option value="">' + title + ' (All)</option></select>')
                                .appendTo($(column.footer()).empty()) // Clears the footer cell and adds the select dropdown.
                                .on('change', function() {
                                    // Perform an exact-match search on column change.
                                    var val = $.fn.dataTable.util.escapeRegex($(this).val());
                                    column.search(val ? '^' + val + '$' : '', true, false).draw();
                                });

                            // B) Populate the Select Options from table data.
                            column.data().unique().sort().each(function(d, j) {
                                if (d) { // Make sure data is not empty
                                    select.append('<option value="' + d + '">' + d + '</option>');
                                }
                            });
                        });

                        // C) Adjust columns after initialization.
                        api.columns.adjust().draw();
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

                // --- 3. Place the Buttons in the DOM ---
                // Moves the generated buttons container to the top-left of the table wrapper.
                table.buttons().container()
                    .appendTo('#employee_vac_wrapper .col-md-6:eq(0)');

            });
        </script>

    </body>

    </html>
<?php } ?>