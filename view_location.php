<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';
$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='" . $username . "'");
if (mysqli_num_rows($query) == 1) {
    include("./includes/avatar_select.php");

    $getquery = mysqli_query($conDB, "
SELECT *,
    (SELECT COUNT(*) FROM `machines` WHERE `machines`.`location_id` = `section`.`id`) AS `location_count`,
    `section`.`id` AS `lid`,
    `section`.`status` AS `locstatus`,
    `location_img`.`id` AS `limgid`
FROM `section`
    LEFT JOIN `location_img` ON `section`.`id` = `location_img`.`location_id`
    LEFT JOIN `location_docu` ON `section`.`id` = `location_docu`.`location_id`
    LEFT JOIN `location_contract` ON `section`.`id` = `location_contract`.`location_id`
WHERE `section`.`id` ='" . $_GET['id'] . "' GROUP BY `section`.`id`");

    if (mysqli_num_rows($getquery) !== 0) {
        while ($rec = mysqli_fetch_assoc($getquery)) {
            $id_loc = $rec["lid"];
            $section_name = $rec["section_name"];
            $dept = $rec["dept"];
            $location_owner = $rec["location_owner"];
            $camera_in = $rec["camera_in"];
            $camera_out = $rec["camera_out"];
            $b_license_exp = $rec["b_license_exp"];
            $b_license_no = $rec["b_license_no"];
            $location_dist = $rec["location_dist"];
            $bulding_base = $rec["bulding_base"];
            $bulding_size = $rec["bulding_size"];
            $t_bulding_size = $rec["t_bulding_size"];
            $latitude = $rec["latitude"];
            $longitude = $rec["longitude"];
            $location_name = $rec["location_name"];
            $municipality = $rec["municipality"];
            $sub_municipality = $rec["sub_municipality"];
            $loc_address = $rec["location_name"];
            $status = $rec["locstatus"];
            $in_img = $rec["in_img"];
            $out_img = $rec["out_img"];
            $id_img = $rec["limgid"];

            if (!$id_img) {
                $defult_img = "./assets/location_content/default_in.jpg";
                mysqli_query($conDB, "INSERT INTO `location_img` (`location_id`,`in_img`,`out_img`,`created_at`) VALUES ('" . $id_loc . "','" . $defult_img . "','" . $defult_img . "','" . date('Y-m-d H:i:s') . "')") or die();
                header("refresh:1 ; url=view_location.php?id=$_GET[id]");
                // return false;
            }
        }

        $query_mac_count = mysqli_query($conDB, "SELECT count(`location_id`) AS `location_count` FROM `machines` WHERE `location_id`='" . $id_loc . "'");
        while ($rec = mysqli_fetch_array($query_mac_count)) {
            $location_count = $rec["location_count"];
        }
    } else {
        //when the id not equals id show database
        header("Location: ./all_locations.php");
    }

    /*$in_img = ($id_loc !== $id_img ) ? "./assets/location_content/default_in.jpg" : $in_img ;
    $out_img = ($id_loc !== $id_img ) ? "./assets/location_content/default_in.jpg" : $out_img ;*/

?>
    <!doctype html>
    <html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

    <head>
        <meta charset="utf-8" />
        <title><?= $site_title ?> - <?= $section_name ?></title>
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
        <link rel="stylesheet" href="./plugins/croppie/croppie.css">

        <link href="./plugins/bootstrap-timepicker/hijri_css/bootstrap-datetimepicker.css" rel="stylesheet">
        <link href="./plugins/bootstrap-timepicker/hijri_css/bootstrap-datetimepicker.min.css" rel="stylesheet">

        <!-- App css -->
        <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
        <script src="assets/js/modernizr.min.js"></script>

        <!-- Bootstrap fileupload css -->
        <link href="./plugins/bootstrap-fileupload/bootstrap-fileupload.css" rel="stylesheet" />

        <!-- Dropzone css -->
        <link href="./plugins/dropzone/dropzone.css" rel="stylesheet" type="text/css" />

        <style>
            /* Set the size of the div element that contains the map */
            #map {
                height: 400px;
                /* The height is 400 pixels */
                width: 100%;
                /* The width is the width of the web page */
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
                            <div class="col-xl-12">
                                <!-- meta -->
                                <div class="profile-user-box card-box <?= ($status == 1) ? "bg-custom-mocha" : "bg-danger"; ?>">
                                    <div class="row">
                                        <div class="col-sm-4">
                                            <div class="media-body text-white">
                                                <h4 class="mt-1 mb-1 font-18"><?= __('location_name') ?>: <?= $section_name ?></h4>
                                                <p class="text-light mb-0"><?= __('total_building_size') ?>: <?= $t_bulding_size ?> (M)</p>
                                                <p class="text-light mb-0"><?= __('district') ?>: <?= $location_name ?></p>
                                                <p class="text-light mb-0"><?= __('camera_in') ?>: <?= $camera_in ?></p>
                                                <p class="text-light mb-0"><?= __('camera_out') ?>: <?= $camera_out ?></p>

                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="media-body text-white">
                                                <p class="text-light mb-0"><?= __('owner_name') ?>: <?= $location_owner ?></p>
                                                <p class="text-light mb-0"><?= __('license_no') ?>: <?= $b_license_no ?></p>
                                                <p class="text-light mb-0"><?= __('license_exp') ?>: <?= $b_license_exp ?></p>
                                                <p class="text-light mb-0"><?= __('address') ?>: <?= $location_name ?> - <?= $location_dist ?></p>
                                                <p class="text-light mb-0"><?= __('municipality') ?>: <?= $municipality ?></p>
                                                <p class="text-light mb-0"><?= __('sub_municipality') ?>: <?= $sub_municipality ?></p>
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="text-left text-white">
                                                <p class="text-light mb-0"><?= __('total_machines') ?>: <?= $location_count ?></p>
                                                <p class="text-light mb-0"><?= __('latitude') ?>: <?= $latitude ?></p>
                                                <p class="text-light mb-0"><?= __('longitude') ?>: <?= $longitude ?></p>
                                                <p class="text-light mb-0"><?= __('building_base') ?>: <?= $bulding_base ?></p>
                                                <p class="text-light mb-0"><?= __('building_size') ?>: <?= $bulding_size ?></p>
                                            </div>

                                            <div class="text-right">
                                                <div class="btn-group" role="group" aria-label="Edit Button">

                                                    <!-- <a href="add_mac_transfer.php?id=<?php //echo $id_car 
                                                                                            ?>" class="btn btn-sm btn-primary waves-effect">
                                                    <i class="mdi mdi-transfer"></i> Transfer
                                                </a> -->
                                                    <?php if ($status == 1) { ?>
                                                        <a href="javascript:void(0);" class="btn btn-sm btn-custom waves-effect waves-light upldLocDocuAttr" data-id="<?= $id_loc ?>">
                                                            <i class="mdi mdi-cloud-upload "></i></i> <?= __('upload_documents_button') ?>
                                                        </a>
                                                        <a href="javascript:void(0);" data-id="<?= $id_loc ?>" class="btn btn-sm btn-primary waves-effect waves-light addLocContractAttr">
                                                            <i class="mdi mdi-clipboard-text"></i></i> <?= __('add_contract_button') ?>
                                                        </a>
                                                    <?php } ?>
                                                    <a href="javascript:void(0);" class="btn btn-sm btn-light waves-effect editLocationAttr" data-id="<?= $id_loc ?>" data-id="<?= $id_loc ?>" data-section_name="<?= $section_name ?>" data-dept="<?= $dept ?>" data-location_owner="<?= $location_owner ?>" data-camera_in="<?= $camera_in ?>" data-camera_out="<?= $camera_out ?>" data-b_license_exp="<?= $b_license_exp ?>" data-b_license_no="<?= $b_license_no ?>" data-location_dist="<?= $location_dist ?>" data-bulding_base="<?= $bulding_base ?>" data-bulding_size="<?= $bulding_size ?>" data-t_bulding_size="<?= $t_bulding_size ?>" data-latitude="<?= $latitude ?>" data-longitude="<?= $longitude ?>" data-location_name="<?= $location_name ?>" data-municipality="<?= $municipality ?>" data-sub_municipality="<?= $sub_municipality ?>" data-status="<?= $status ?>">
                                                        <i class="fa fa-edit"></i> <?= __('edit_button') ?>
                                                    </a>

                                                </div>

                                            </div>

                                        </div>
                                    </div>
                                </div>
                                <!--/ meta -->

                            </div>


                        </div>

                        <div class="row">
                            <div class="col-6">
                                <div class="card-box table-responsive">
                                    <h4 class="m-t-0 header-title"><?= __('location_google_map_header') ?></h4>
                                    <!-- <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3709.809497175475!2d39.10374531494239!3d21.59335798569526!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMjHCsDM1JzM2LjEiTiAzOcKwMDYnMjEuNCJF!5e0!3m2!1sen!2ssa!4v1602506255704!5m2!1sen!2ssa" width="600" height="450" frameborder="0" style="border:0;" allowfullscreen="" aria-hidden="false" tabindex="0"></iframe> -->

                                    <div class='contact-form-wrapper'>
                                        <div class='contact-form-content right'>
                                            <div id='map' class='map-contact-style'></div>
                                        </div>
                                    </div>

                                    <!-- <iframe src="https://www.google.com/maps?q=21.593358, 39.105934"></iframe> -->
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="card-box table-responsive" style="height: 470px !important;">
                                    <h4 class="m-t-0 header-title"><?= __('inside_image_header') ?></h4>
                                    <a href="javascript:void(0);" class="image-popup upload_img" data-id="<?= $id_loc ?>" data-img="<?= $in_img ?>" data-section="<?= $section_name ?>" data-postion="in">
                                        <div class="portfolio-masonry-box">
                                            <div class="portfolio-masonry-img">
                                                <img src="<?= $in_img; ?>" class="thumb-img img-fluid" alt="<?= __('no_uploaded_image_inside') ?>">
                                            </div>
                                            <div class="portfolio-masonry-detail">
                                                <h4 class="font-18"><?= __('inside_image_of') ?> <?= $section_name ?></h4>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="card-box table-responsive" style="height: 470px !important;">
                                    <h4 class="m-t-0 header-title"><?= __('outside_image_header') ?></h4>
                                    <a href="javascript:void(0);" class="image-popup upload_img" data-id="<?= $id_loc ?>" data-img="<?= $out_img ?>" data-section="<?= $section_name ?>" data-postion="out">
                                        <div class="portfolio-masonry-box">
                                            <div class="portfolio-masonry-img">
                                                <img src="<?= $out_img; ?>" class="thumb-img img-fluid" alt="<?= __('no_uploaded_image_inside') ?>">
                                            </div>
                                            <div class="portfolio-masonry-detail">
                                                <h4 class="font-18"><?= __('outside_image_of') ?> <?= $section_name ?></h4>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>



                        <div class="row">
                            <div class="col-12">
                                <div class="card-box">
                                    <h4 class="header-title m-b-30"><?= __('documents_for_location') ?> <?= $section_name ?></h4>
                                    <div class="row">
                                        <?php
                                        $queryempdocu = mysqli_query($conDB, "SELECT * FROM `location_docu` WHERE `location_id`='" . $id_loc . "' ORDER BY `id` DESC ");
                                        while ($recempdoc = mysqli_fetch_assoc($queryempdocu)) {
                                            $id_empdoc_get = $recempdoc["id"];
                                            $file_name_get = $recempdoc["file_name"];
                                            $docu_ext_get = $recempdoc["docu_ext"];
                                            $doc_date_reg_get = $recempdoc["date_reg"];

                                            $times_reg = strtotime("$doc_date_reg_get");
                                            $doc_date_reg_get = date('d, M Y h:ia', $times_reg);

                                            $ext = ($docu_ext_get == "jpg" ? "jpg" : ($docu_ext_get == "jpeg" ? "jpg" : ($docu_ext_get == "png" ? "png" : ($docu_ext_get == "pdf" ? "pdf" : ""))));

                                        ?>
                                            <div class="col-lg-3 col-xl-2">
                                                <div class="file-man-box">
                                                    <?php if ($user_type == "administrator" or $user_type == "hr") { ?>
                                                        <a href="javascript:void(0);" data-id="<?= $id_empdoc_get ?>" data-tbl="location_docu" data-file="1" data-column='file_name' class="file-close deleteAjax"><i class="mdi mdi-close-circle"></i></a>
                                                    <?php } ?>
                                                    <div class="file-img-box" onclick="javascript:displayPopup('./assets/location_content/<?= $file_name_get ?>')" style="cursor: pointer;">
                                                        <img src="assets/images/file_icons/<?= $ext ?>.svg" alt="icon">
                                                    </div>
                                                    <div class="file-man-title">
                                                        <h5 class="mb-0 text-overflow"><?= $file_name_get ?></h5>
                                                        <p class="mb-0"><small><?= $doc_date_reg_get ?></small></p>
                                                    </div>

                                                    <!-- <div id="articleContent"></div> -->
                                                </div>


                                            </div>
                                        <?php } ?>

                                    </div>

                                </div>
                            </div>
                        </div>


                        <div class="row">
                            <div class="col-12">
                                <div class="card-box table-responsive">
                                    <h4 class="m-t-0 header-title"><?= $section_name ?> <?= __('contract_detail_header') ?></h4>
                                    <table id="location_countrt" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                        <thead>
                                            <tr>
                                                <th><?= __('sr_header') ?></th>
                                                <th><?= __('owner_name_header') ?></th>
                                                <th><?= __('contact_header') ?></th>
                                                <th><?= __('email_header') ?></th>
                                                <th><?= __('contract_no_header') ?></th>
                                                <th><?= __('start_date_header') ?></th>
                                                <th><?= __('end_date_header') ?></th>
                                                <th><?= __('rent_header') ?></th>
                                                <th width="20"><?= __('action_header') ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php

                                            // SELECT *, SUM(`qty`*`price`) / 100 * 15 + SUM(`qty`*`price`) AS total FROM machine_inv WHERE `location`='JM-01' Group By `location`

                                            $x = 1;

                                            $query_loc_cont = mysqli_query($conDB, "SELECT * FROM `location_contract` WHERE `location_id`='" . $id_loc . "' ");
                                            while ($rec = mysqli_fetch_array($query_loc_cont)) {
                                                $id = $rec["id"];
                                                $owner_name = $rec["owner_name"];
                                                $owner_number = $rec["owner_number"];
                                                $owner_email = $rec["owner_email"];
                                                $contract_no = $rec["contract_no"];
                                                $start_cont_date = $rec["start_cont_date"];
                                                $end_cont_date = $rec["end_cont_date"];
                                                $rent = $rec["rent"];
                                                $datereg = $rec["date_reg"];
                                                //  $times_reg = strtotime("$date_emp");
                                                //  $datevac = date('d, M Y', $times_reg);
                                                $timestamp_reg = strtotime("$datereg");
                                                $date_reg = date('d, M Y', $timestamp_reg);

                                            ?>
                                                <tr>
                                                    <th><?= $x++; ?></th>
                                                    <th><?= $owner_name; ?></th>
                                                    <th><?= $owner_number; ?></th>
                                                    <th><?= $owner_email; ?></th>
                                                    <th><?= $contract_no; ?></th>
                                                    <th><?= $start_cont_date; ?></th>
                                                    <th><?= $end_cont_date; ?></th>
                                                    <th><?= $rent; ?></th>
                                                    <th>
                                                        <div class='btn-group dropdown'>
                                                            <a href='javascript: void(0);' class='table-action-btn dropdown-toggle arrow-none btn btn-light btn-sm' data-toggle='dropdown' aria-expanded='false'><i class='mdi mdi-dots-horizontal'></i></a>
                                                            <div class='dropdown-menu dropdown-menu-right' x-placement='bottom-end'>
                                                                <a href='./location_profile.php?location_id=<?= $id_loc ?>' target="blank" class='dropdown-item text-dark'><i class='fa fa-eye mr-2 font-18 vertical-middle'></i><?= __('open_link') ?></a>
                                                                <?php if ($user_type == $access1 or $user_type == $access2) { ?>
                                                                    <a href='javascript:void(0);' class='dropdown-item text-danger deleteAjax' data-id='<?= $rec["id"] ?>' data-tbl='location_contract' data-file='0'><i class='fa fa-trash mr-2 font-18 vertical-middle'></i><?= __('delete_link') ?></a>
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

                        <div class="row">
                            <div class="col-12">
                                <div class="card-box table-responsive">
                                    <h4 class="m-t-0 header-title"><?= $section_name ?> <?= __('machines_detail_header') ?></h4>
                                    <table id="mac_trans" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                        <thead>
                                            <tr>
                                                <th><?= __('sr_header') ?></th>
                                                <th><?= __('machine_name_header') ?></th>
                                                <th><?= __('m_id_header') ?></th>
                                                <th><?= __('serial_header') ?></th>
                                                <th><?= __('model_header') ?></th>
                                                <th><?= __('issue_date_header') ?></th>
                                                <th><?= __('remarks_header') ?></th>
                                                <th width="20"><?= __('action_header') ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php

                                            // SELECT *, SUM(`qty`*`price`) / 100 * 15 + SUM(`qty`*`price`) AS total FROM machine_inv WHERE `location`='JM-01' Group By `location`

                                            $x = 1;

                                            $query_mactrn = mysqli_query($conDB, "SELECT * FROM `machines` WHERE `location`='" . $section_name . "' ");
                                            while ($rec = mysqli_fetch_array($query_mactrn)) {
                                                $id = $rec["id"];
                                                $name_mach = $rec["name_mach"];
                                                $m_id = $rec["m_id"];
                                                $maker_name = $rec["maker_name"];
                                                $location = $rec["location"];
                                                $serial = $rec["serial"];
                                                $remarks = $rec["remarks"];
                                                $datereg = $rec["date_reg"];
                                                $made_year = $rec["made_year"];
                                                //  $times_reg = strtotime("$date_emp");
                                                //  $datevac = date('d, M Y', $times_reg);
                                                $timestamp_reg = strtotime("$datereg");
                                                $date_reg = date('d, M Y', $timestamp_reg);

                                            ?>
                                                <tr>
                                                    <th><?= $x++; ?></th>
                                                    <th><?= $name_mach; ?></th>
                                                    <th><?= $m_id; ?></th>
                                                    <th><?= $serial; ?></th>
                                                    <th><?= $maker_name; ?></th>
                                                    <th><?= $made_year; ?></th>
                                                    <th><?= $remarks; ?></th>
                                                    <th>
                                                        <div class="btn-group" role="group" aria-label="Edit Button">
                                                            <a href="./view_machine.php?id=<?= $id ?>" class="btn btn-sm btn-dark waves-effect">
                                                                <i class="mdi mdi-eye-outline"></i>
                                                            </a>
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

        <?php /* ?>
<div class="modal fade upload_documents" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" style="display: none;">
<!--    <div class="modal-dialog modal-lg" style="max-width: 1450px !important">-->
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #2D7BF4 !important; color: #fff !important;">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h4 class="modal-title" id="myLargeModalLabel">
                    <i class="mdi mdi-image-filter-tilt-shift "></i> 
                    <?=__('upload_documents_for')?> <?=$section_name ?>
                </h4>
            </div>
            <div class="modal-body">
<!---->
                
        <div class="row">

            <div class="col-12">
            <div class="card-box">
                <h4 class="header-title m-t-0"><?=__('dropzone_file_upload')?></h4>
                <p class="text-muted font-14 m-b-10">
                    Your awesome text goes here.
                </p>
                <form action="#" class="dropzone" id="dropzone">
                    <div class="fallback">
                        <input name="file" type="file" multiple />
                    </div>

                </form>
            </div>
            </div>
            
        </div>
                
<!---->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger waves-effect" data-dismiss="modal"><?=__('close')?></button>
                <button type="button" class="btn btn-success waves-effect waves-light" id="startUpload"><i class="mdi mdi-backup-restore"></i> <?=__('upload_button')?></button>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<?php */ ?>

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
        <script src="assets/pages/jquery.form-hijri-pickers.init.js"></script>
        <script src="./plugins/bootstrap-timepicker/hijri/bootstrap-hijri-datetimepicker.js"></script>
        <script src="./plugins/bootstrap-timepicker/hijri/bootstrap-hijri-datetimepicker.min.js"></script>
        <script src="./plugins/bootstrap-timepicker/hijri/bootstrap-hijri-datetimepickermin.js"></script>

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

        <!-- Bootstrap fileupload js -->
        <!-- <script src="./plugins/bootstrap-fileupload/bootstrap-fileupload.js"></script> -->

        <!-- Dropzone js -->
        <script src="./plugins/dropzone/dropzone.js"></script>


        <!-- App js -->
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js"></script>


        <!-- <script src='https://maps.googleapis.com/maps/api/js?key=AIzaSyAAmpMDQXVtsHabQM2U1NqP1rhls03ZxMc&amp;sensor=false'></script>
        <script src='assets/js/contact.js'></script> -->


        <script>
            //Disabling autoDiscover
            //Dropzone.autoDiscover = false;

            /*$(function() {
                //Dropzone class
                var myDropzone = new Dropzone(".dropzone", {
                    url: "upload_documents.php?location_id=<?= $id_loc ?>",
                    paramName: "file",
                    maxFilesize: 5,
                    maxFiles: 10,
                    acceptedFiles: "image/*,application/pdf",
                    autoProcessQueue: false
                });
                
                $('#startUpload').click(function(){           
                    myDropzone.processQueue();
                });
            });*/
        </script>

        <script>
            function initMap() {
                var options = {
                    zoom: 16,
                    center: {
                        lat: <?= $latitude; ?>,
                        lng: <?= $longitude; ?>
                    } //Coordinates of New York 
                }
                var map = new google.maps.Map(document.getElementById('map'), options);
                var marker = new google.maps.Marker({
                    position: {
                        lat: <?= $latitude; ?>,
                        lng: <?= $longitude; ?>
                    }, // Brooklyn Coordinates
                    map: map, //Map that we need to add
                    icon: 'assets/images/map-maker/map-maker.png',
                    draggarble: false // If set to true you can drag the marker
                });
                var information = new google.maps.InfoWindow({
                    content: '<h5><?= $section_name; ?></h5>'
                });
                marker.addListener('click', function() {
                    information.open(map, marker);
                });
            }
        </script>
        <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAAmpMDQXVtsHabQM2U1NqP1rhls03ZxMc&callback=initMap"></script>


        <script type="text/javascript">
            jQuery(function($) {
                $('.autonumber').autoNumeric('init');
            });
            $(document).ready(function() {

                var buttonConfig = [];
                var exportTitle = "<?= __('serial_no_label') ?>: <?= $serial ?> | <?= __('id_no_label') ?>: <?= $m_id ?>"
                var exportTitleP = "<h4><?= __('serial_no_label') ?>: <?= $serial ?> | <?= __('id_no_label') ?>: <?= $m_id ?></h4>"
                buttonConfig.push({
                    extend: 'excel',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4]
                    },
                    title: exportTitle,
                    className: 'btn-success'
                });
                buttonConfig.push({
                    extend: 'pdf',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4]
                    },
                    title: exportTitle,
                    className: 'btn-danger'
                });
                buttonConfig.push({
                    extend: 'print',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4]
                    },
                    title: exportTitleP,
                    className: 'btn-dark'
                });
                // buttonConfig.push({text: '<i class="fa fa-plus"></i> Add Machine', action: function ( e, dt, button, config ) {window.location = './add_machine.php' } ,className: 'btn-info'});

                $('form').parsley();

                //Buttons examples
                var table = $('#cars_docu').DataTable({
                    lengthChange: false,
                    buttons: buttonConfig,
                    order: [
                        [5, "desc"]
                    ],
                    "columnDefs": [{
                        targets: [5],
                        visible: false,
                        searchable: false
                    }, ],

                    "footerCallback": function(row, data, start, end, display) {
                        var api = this.api(),
                            data;

                        // Remove the formatting to get integer data for summation
                        var intVal = function(i) {
                            return typeof i === 'string' ?
                                i.replace(/[\$,]/g, '') * 1 :
                                typeof i === 'number' ?
                                i : 0;
                        };

                        // Total over all pages
                        total = api
                            .column(4)
                            .data()
                            .reduce(function(a, b) {
                                return intVal(a) + intVal(b);
                            }, 0);

                        // Total over this page
                        pageTotal = api
                            .column(4, {
                                page: 'current'
                            })
                            .data()
                            .reduce(function(a, b) {
                                return intVal(a) + intVal(b);
                            }, 0);

                        // Update footer
                        $(api.column(4).footer()).html(
                            'SAR ' + pageTotal + ''
                            // 'SAR '+pageTotal +' ( SAR'+ total +' total)'
                        );
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
                    .appendTo('#cars_docu_wrapper .col-md-6:eq(0)');

            });


            $(document).ready(function() {

                var buttonConfig = [];
                var exportTitle = "<?= __('location_no_label') ?>: <?= $section_name ?>"
                var exportTitleP = "<h4><?= __('location_no_label') ?>: <?= $section_name ?></h4>"
                buttonConfig.push({
                    extend: 'excel',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6]
                    },
                    title: exportTitle,
                    className: 'btn-success'
                });
                buttonConfig.push({
                    extend: 'pdf',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6]
                    },
                    title: exportTitle,
                    className: 'btn-danger'
                });
                buttonConfig.push({
                    extend: 'print',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6]
                    },
                    title: exportTitleP,
                    className: 'btn-dark'
                });

                $('form').parsley();

                //Buttons examples
                var table = $('#mac_trans').DataTable({
                    lengthChange: false,
                    buttons: buttonConfig,
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
                    .appendTo('#mac_trans_wrapper .col-md-6:eq(0)');

            });


            $(document).ready(function() {

                var buttonConfig = [];
                var exportTitle = "<?= __('location_no_label') ?>: <?= $section_name ?>"
                var exportTitleP = "<h4><?= __('location_no_label') ?>: <?= $section_name ?></h4>"
                buttonConfig.push({
                    extend: 'excel',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6]
                    },
                    title: exportTitle,
                    className: 'btn-success'
                });
                buttonConfig.push({
                    extend: 'pdf',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6]
                    },
                    title: exportTitle,
                    className: 'btn-danger'
                });
                buttonConfig.push({
                    extend: 'print',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6]
                    },
                    title: exportTitleP,
                    className: 'btn-dark'
                });

                $('form').parsley();

                //Buttons examples
                var table = $('#location_countrt').DataTable({
                    lengthChange: false,
                    buttons: buttonConfig,
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
                    .appendTo('#location_countrt_wrapper .col-md-6:eq(0)');

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