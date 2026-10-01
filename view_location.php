<?php
// Location detail. Same look as the Smart Request pages (assets/css/smart_request.css).
// Edit / upload documents / add contract / change photos / delete keep using the shared
// handlers in assets/js/jquery.app.js (.editLocationAttr, .upldLocDocuAttr,
// .addLocContractAttr, .upload_img, .deleteAjax) - keep their class names + data-* attributes.
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/special_access_helper.php';
$can_edit_location = !empty($is_system_admin) || user_has_special_access($conDB, $empid ?? '', 'locations_edit', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='" . $username . "'");
if (mysqli_num_rows($query) == 1) {
    include("./includes/avatar_select.php");

    $location_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
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
WHERE `section`.`id` = " . $location_id . " GROUP BY `section`.`id`");

    if ($getquery && mysqli_num_rows($getquery) !== 0) {
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
            $status = $rec["locstatus"];
            $in_img = $rec["in_img"];
            $out_img = $rec["out_img"];
            $id_img = $rec["limgid"];
            $location_count = (int)$rec["location_count"];

            if (!$id_img) {
                // First visit: give the location default inside/outside photos, then reload.
                $defult_img = "./assets/location_content/default_in.jpg";
                mysqli_query($conDB, "INSERT INTO `location_img` (`location_id`,`in_img`,`out_img`,`created_at`) VALUES ('" . (int)$id_loc . "','" . $defult_img . "','" . $defult_img . "','" . date('Y-m-d H:i:s') . "')") or die();
                header("refresh:1 ; url=view_location.php?id=" . (int)$id_loc);
                $in_img = $out_img = $defult_img;
            }
        }
    } else {
        header("Location: ./all_locations.php");
        exit;
    }

    // Documents, contracts, machines
    $documents = [];
    $queryempdocu = mysqli_query($conDB, "SELECT * FROM `location_docu` WHERE `location_id`='" . (int)$id_loc . "' ORDER BY `id` DESC ");
    while ($queryempdocu && ($r = mysqli_fetch_assoc($queryempdocu))) { $documents[] = $r; }

    $contracts = [];
    $query_loc_cont = mysqli_query($conDB, "SELECT * FROM `location_contract` WHERE `location_id`='" . (int)$id_loc . "' ");
    while ($query_loc_cont && ($r = mysqli_fetch_assoc($query_loc_cont))) { $contracts[] = $r; }

    $machines = [];
    // Machines are linked by location_id (used for the count) and, on older rows, only by name.
    $query_mactrn = mysqli_query($conDB, "SELECT * FROM `machines` WHERE `location_id`='" . (int)$id_loc . "' OR `location`='" . escape_string($section_name) . "' ORDER BY `id` ASC");
    while ($query_mactrn && ($r = mysqli_fetch_assoc($query_mactrn))) { $machines[] = $r; }

    $is_active = ($status == 1);
    $has_coords = is_numeric($latitude) && is_numeric($longitude) && ((float)$latitude != 0 || (float)$longitude != 0);
    $can_delete_children = ($user_type == $access1 or $user_type == $access2);
    $h = function ($v) { return htmlspecialchars((string)$v); };
    $val = function ($v) { $v = trim((string)$v); return $v === '' ? '<span class="text-muted">&ndash;</span>' : htmlspecialchars($v); };
?>
    <!doctype html>
    <html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

    <head>
        <meta charset="utf-8" />
        <title><?= $site_title ?> - <?= $h($section_name) ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta content="Anees Afzal" name="author" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />

        <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">

        <!-- DataTables -->
        <link href="./plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
        <link href="./plugins/datatables/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" />
        <link href="./plugins/datatables/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />
        <link rel="stylesheet" href="./plugins/croppie/croppie.css">
        <link href="./plugins/bootstrap-datepicker/css/bootstrap-datepicker.min.css" rel="stylesheet">
        <link href="./plugins/bootstrap-timepicker/hijri_css/bootstrap-datetimepicker.min.css" rel="stylesheet">
        <link href="./plugins/dropzone/dropzone.css" rel="stylesheet" type="text/css" />

        <!-- App css -->
        <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/smart_request.css?v=<?= @filemtime(__DIR__ . '/assets/css/smart_request.css') ?>" rel="stylesheet" type="text/css" />
        <script src="assets/js/modernizr.min.js"></script>
        <style>
            .loc-hero-img { width: 76px; height: 76px; border-radius: 16px; object-fit: cover; flex: 0 0 auto; border: 1px solid var(--sr-border); background: var(--sr-surface-3); }
            .loc-stats { display: flex; gap: 10px; flex-wrap: wrap; }
            .loc-stat { min-width: 96px; padding: 10px 14px; border-radius: 12px; border: 1px solid var(--sr-border); background: var(--sr-surface-2); text-align: center; }
            .loc-stat b { display: block; font-size: 22px; font-weight: 800; color: var(--sr-text); line-height: 1.1; }
            .loc-stat span { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; color: var(--sr-muted); }
            .sr-meta.loc-meta { grid-template-columns: repeat(4, minmax(0, 1fr)); }
            .sr-meta.loc-meta .sr-meta-item { border-bottom: 1px solid var(--sr-border); }
            .sr-meta.loc-meta .sr-meta-value { white-space: normal; overflow-wrap: anywhere; }
            @media (max-width: 991px) { .sr-meta.loc-meta { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
            .sr-meta.loc-meta .sr-meta-item:nth-child(4n) { border-inline-end: 0; }
            #map { height: 340px; width: 100%; border-radius: 0 0 var(--sr-radius) var(--sr-radius); }
            .loc-map-empty { height: 340px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; color: var(--sr-muted); }
            .loc-map-empty i { font-size: 34px; color: var(--sr-border-strong); }
            .loc-photos { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
            .loc-photo { position: relative; display: block; border-radius: 12px; overflow: hidden; border: 1px solid var(--sr-border); background: var(--sr-surface-3); height: 300px; text-decoration: none !important; }
            .loc-photo img { width: 100%; height: 100%; object-fit: cover; transition: transform .25s; }
            .loc-photo:hover img { transform: scale(1.03); }
            .loc-photo .cap {
                position: absolute; inset-inline: 0; bottom: 0; padding: 10px 12px; color: #fff; font-size: 13px; font-weight: 700;
                background: linear-gradient(transparent, rgba(15, 23, 42, .78)); display: flex; justify-content: space-between; align-items: center;
            }
            .loc-photo .cap i { font-size: 16px; opacity: .9; }
            @media (max-width: 575px) { .loc-photos { grid-template-columns: 1fr; } .loc-photo { height: 220px; } }
            .sr-file-thumb img.sr-file-icon { width: 46px; height: 46px; }
        </style>
        <?php if ($is_rtl): ?>
            <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
        <?php endif; ?>
        <script>
            window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;
        </script>
    </head>

    <body class="enlarged" data-keep-enlarged="true">
        <div id="wrapper">
            <div class="left side-menu">
                <div class="slimscroll-menu" id="remove-scroll">
                    <div class="topbar-left">
                        <a href="dashboard.php" class="logo">
                            <span><img src="<?=get_setting($conDB, 'logo')?>" alt="" height="22"></span>
                            <i><img src="<?=get_setting($conDB, 'white_logo')?>" alt="" height="28"></i>
                        </a>
                    </div>
                    <?php include("./includes/main_menu.php"); ?>
                    <div class="clearfix"></div>
                </div>
            </div>

            <div class="content-page">
                <?php include("./includes/topbar.php"); ?>

                <div class="content sr-page">
                    <div class="container-fluid">

                        <!-- ===== Header ===== -->
                        <div class="sr-card sr-hero">
                            <a href="./all_locations.php" class="sr-back"><i class="mdi mdi-arrow-left"></i> <?= __('all_locations_title') ?></a>
                            <div class="sr-hero-top">
                                <div class="d-flex align-items-center" style="gap: 16px; min-width: 0; flex: 1 1 420px;">
                                    <img src="<?= $h($out_img ?: $in_img) ?>" class="loc-hero-img" alt="">
                                    <div style="min-width: 0;">
                                        <div class="sr-hero-tags">
                                            <span class="sr-chip sr-mono"><i class="mdi mdi-pound"></i><?= (int)$id_loc ?></span>
                                            <span class="sr-pill sr-pill-lg <?= $is_active ? 'tone-green' : 'tone-red' ?>"><span class="sr-dot"></span><?= $is_active ? __('active_status') : __('closed_status') ?></span>
                                            <?php if ($dept): ?><span class="sr-chip"><i class="mdi mdi-domain"></i><?= $h($dept) ?></span><?php endif; ?>
                                        </div>
                                        <h1 class="sr-hero-title"><?= $h($section_name) ?></h1>
                                        <div class="sr-card-sub"><i class="mdi mdi-map-marker"></i> <?= $h(trim($location_name . ($location_dist ? ' - ' . $location_dist : ''))) ?: '-' ?></div>
                                    </div>
                                </div>
                                <div class="loc-stats">
                                    <div class="loc-stat"><b><?= max($location_count, count($machines)) ?></b><span><?= __('total_machines') ?></span></div>
                                    <div class="loc-stat"><b><?= count($contracts) ?></b><span><?= __('contracts', 'Contracts') ?></span></div>
                                    <div class="loc-stat"><b><?= count($documents) ?></b><span><?= __('documents', 'Documents') ?></span></div>
                                </div>
                            </div>

                            <div class="sr-meta loc-meta">
                                <div class="sr-meta-item"><div class="sr-meta-label"><i class="mdi mdi-account"></i> <?= __('owner_name') ?></div><div class="sr-meta-value"><?= $val($location_owner) ?></div></div>
                                <div class="sr-meta-item"><div class="sr-meta-label"><i class="mdi mdi-file-document"></i> <?= __('license_no') ?></div><div class="sr-meta-value"><?= $val($b_license_no) ?></div></div>
                                <div class="sr-meta-item"><div class="sr-meta-label"><i class="mdi mdi-calendar"></i> <?= __('license_exp') ?></div><div class="sr-meta-value"><?= $val($b_license_exp) ?></div></div>
                                <div class="sr-meta-item"><div class="sr-meta-label"><i class="mdi mdi-city"></i> <?= __('municipality') ?></div><div class="sr-meta-value"><?= $val($municipality) ?><?= $sub_municipality ? ' &middot; ' . $h($sub_municipality) : '' ?></div></div>
                                <div class="sr-meta-item"><div class="sr-meta-label"><i class="mdi mdi-home"></i> <?= __('building_base') ?></div><div class="sr-meta-value"><?= $val($bulding_base) ?></div></div>
                                <div class="sr-meta-item"><div class="sr-meta-label"><i class="mdi mdi-ruler"></i> <?= __('building_size') ?></div><div class="sr-meta-value"><?= $val($bulding_size) ?></div></div>
                                <div class="sr-meta-item"><div class="sr-meta-label"><i class="mdi mdi-ruler"></i> <?= __('total_building_size') ?></div><div class="sr-meta-value"><?= $t_bulding_size !== '' && $t_bulding_size !== null ? $h($t_bulding_size) . ' (M)' : $val('') ?></div></div>
                                <div class="sr-meta-item"><div class="sr-meta-label"><i class="mdi mdi-video"></i> <?= __('camera_in') ?> / <?= __('camera_out') ?></div><div class="sr-meta-value"><?= $val($camera_in) ?> / <?= $val($camera_out) ?></div></div>
                            </div>

                            <div class="sr-hero-actions">
                                <?php if ($is_active): ?>
                                    <a href="javascript:void(0);" class="sr-btn sr-btn-sm upldLocDocuAttr" data-id="<?= (int)$id_loc ?>"><i class="mdi mdi-cloud-upload"></i> <?= __('upload_documents_button') ?></a>
                                    <a href="javascript:void(0);" class="sr-btn sr-btn-sm addLocContractAttr" data-id="<?= (int)$id_loc ?>"><i class="mdi mdi-clipboard-text"></i> <?= __('add_contract_button') ?></a>
                                <?php endif; ?>
                                <?php if ($can_edit_location): ?>
                                    <a href="javascript:void(0);" class="sr-btn sr-btn-sm editLocationAttr" data-id="<?= (int)$id_loc ?>" data-section_name="<?= $h($section_name) ?>" data-dept="<?= $h($dept) ?>" data-location_owner="<?= $h($location_owner) ?>" data-camera_in="<?= $h($camera_in) ?>" data-camera_out="<?= $h($camera_out) ?>" data-b_license_exp="<?= $h($b_license_exp) ?>" data-b_license_no="<?= $h($b_license_no) ?>" data-location_dist="<?= $h($location_dist) ?>" data-bulding_base="<?= $h($bulding_base) ?>" data-bulding_size="<?= $h($bulding_size) ?>" data-t_bulding_size="<?= $h($t_bulding_size) ?>" data-latitude="<?= $h($latitude) ?>" data-longitude="<?= $h($longitude) ?>" data-location_name="<?= $h($location_name) ?>" data-municipality="<?= $h($municipality) ?>" data-sub_municipality="<?= $h($sub_municipality) ?>" data-status="<?= $h($status) ?>">
                                        <i class="mdi mdi-pencil"></i> <?= __('edit_button') ?>
                                    </a>
                                <?php endif; ?>
                                <span class="sr-spacer"></span>
                                <?php if ($has_coords): ?>
                                    <a href="https://www.google.com/maps?q=<?= rawurlencode($latitude . ',' . $longitude) ?>" target="_blank" rel="noopener" class="sr-btn sr-btn-sm"><i class="mdi mdi-google-maps"></i> <?= __('open_in_google_maps', 'Open in Google Maps') ?></a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Map -->
                            <div class="col-xl-6">
                                <div class="sr-card">
                                    <div class="sr-card-head">
                                        <h5 class="sr-card-title"><i class="mdi mdi-map"></i> <?= __('location_google_map_header') ?></h5>
                                        <?php if ($has_coords): ?><span class="sr-card-sub sr-mono"><?= $h($latitude) ?>, <?= $h($longitude) ?></span><?php endif; ?>
                                    </div>
                                    <?php if ($has_coords): ?>
                                        <div id="map"></div>
                                    <?php else: ?>
                                        <div class="loc-map-empty"><i class="mdi mdi-map-marker-off"></i><?= __('no_coordinates', 'No latitude / longitude saved for this location.') ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <!-- Photos -->
                            <div class="col-xl-6">
                                <div class="sr-card">
                                    <div class="sr-card-head">
                                        <h5 class="sr-card-title"><i class="mdi mdi-image-multiple"></i> <?= __('photos', 'Photos') ?></h5>
                                        <span class="sr-card-sub"><?= __('click_photo_to_change', 'Click a photo to change it') ?></span>
                                    </div>
                                    <div class="sr-card-body">
                                        <div class="loc-photos">
                                            <a href="javascript:void(0);" class="loc-photo upload_img" data-id="<?= (int)$id_loc ?>" data-img="<?= $h($in_img) ?>" data-section="<?= $h($section_name) ?>" data-postion="in">
                                                <img src="<?= $h($in_img) ?>" alt="<?= __('no_uploaded_image_inside') ?>">
                                                <span class="cap"><?= __('inside_image_header') ?> <i class="mdi mdi-camera"></i></span>
                                            </a>
                                            <a href="javascript:void(0);" class="loc-photo upload_img" data-id="<?= (int)$id_loc ?>" data-img="<?= $h($out_img) ?>" data-section="<?= $h($section_name) ?>" data-postion="out">
                                                <img src="<?= $h($out_img) ?>" alt="<?= __('no_uploaded_image_inside') ?>">
                                                <span class="cap"><?= __('outside_image_header') ?> <i class="mdi mdi-camera"></i></span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Documents -->
                        <div class="sr-card">
                            <div class="sr-card-head">
                                <h5 class="sr-card-title"><i class="mdi mdi-paperclip"></i> <?= __('documents_for_location') ?> <?= $h($section_name) ?> <span class="sr-count"><?= count($documents) ?></span></h5>
                                <?php if ($is_active): ?>
                                    <a href="javascript:void(0);" class="sr-btn sr-btn-sm sr-btn-ghost upldLocDocuAttr" data-id="<?= (int)$id_loc ?>"><i class="mdi mdi-cloud-upload"></i> <?= __('upload_documents_button') ?></a>
                                <?php endif; ?>
                            </div>
                            <div class="sr-card-body">
                                <?php if (empty($documents)): ?>
                                    <div class="sr-empty-files"><i class="mdi mdi-file"></i> <?= __('no_data_available_in_table') ?></div>
                                <?php else: ?>
                                    <div class="sr-files">
                                        <?php foreach ($documents as $doc):
                                            $ext = strtolower((string)$doc["docu_ext"]);
                                            $icon = in_array($ext, ['jpg', 'jpeg']) ? 'jpg' : ($ext === 'png' ? 'png' : ($ext === 'pdf' ? 'pdf' : 'blank'));
                                            $is_image = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                            $file_url = './assets/location_content/' . $doc["file_name"];
                                        ?>
                                            <div class="sr-file">
                                                <?php if ($can_delete_children): ?>
                                                    <a href="javascript:void(0);" class="sr-file-del deleteAjax" title="<?= __('delete_link') ?>" data-id="<?= (int)$doc["id"] ?>" data-tbl="location_docu" data-file="1" data-column="file_name"><i class="mdi mdi-close"></i></a>
                                                <?php endif; ?>
                                                <div class="sr-file-thumb" role="button" tabindex="0" onclick="displayPopup(<?= $h(json_encode($file_url)) ?>)">
                                                    <?php if ($is_image): ?>
                                                        <img src="<?= $h($file_url) ?>" alt="" loading="lazy">
                                                    <?php else: ?>
                                                        <img class="sr-file-icon" src="assets/images/file_icons/<?= $icon ?>.svg" alt="<?= $h($ext) ?>">
                                                    <?php endif; ?>
                                                </div>
                                                <div class="sr-file-foot">
                                                    <div style="min-width: 0;">
                                                        <div class="sr-file-name" title="<?= $h($doc["file_name"]) ?>"><?= $h($doc["file_name"]) ?></div>
                                                        <div class="sr-file-date"><?= $doc["date_reg"] ? date('d M Y, h:ia', strtotime($doc["date_reg"])) : '' ?></div>
                                                    </div>
                                                    <a href="./downloadFile.php?file=<?= urlencode($file_url) ?>" class="sr-file-dl" title="<?= __('download', 'Download') ?>"><i class="mdi mdi-download"></i></a>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Contracts -->
                        <div class="sr-card">
                            <div class="sr-card-head">
                                <h5 class="sr-card-title"><i class="mdi mdi-clipboard-text"></i> <?= $h($section_name) ?> <?= __('contract_detail_header') ?> <span class="sr-count"><?= count($contracts) ?></span></h5>
                                <div class="d-flex align-items-center" style="gap: 8px;">
                                    <div id="contractButtons" class="sr-toolbar" style="padding: 0; border: 0;"></div>
                                    <?php if ($is_active): ?>
                                        <a href="javascript:void(0);" class="sr-btn sr-btn-sm sr-btn-ghost addLocContractAttr" data-id="<?= (int)$id_loc ?>"><i class="mdi mdi-plus"></i> <?= __('add_contract_button') ?></a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="sr-table-wrap">
                                <table id="location_countrt" class="table sr-table dt-responsive nowrap" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th><?= __('sr_header') ?></th>
                                            <th><?= __('owner_name_header') ?></th>
                                            <th><?= __('contact_header') ?></th>
                                            <th><?= __('email_header') ?></th>
                                            <th><?= __('contract_no_header') ?></th>
                                            <th><?= __('start_date_header') ?></th>
                                            <th><?= __('end_date_header') ?></th>
                                            <th class="text-right"><?= __('rent_header') ?></th>
                                            <th class="text-right"><?= __('action_header') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $x = 1; foreach ($contracts as $c): ?>
                                            <tr>
                                                <td><span class="sr-line-no"><?= $x++ ?></span></td>
                                                <td><span class="sr-person-name"><?= $h($c["owner_name"]) ?></span></td>
                                                <td><?= $h($c["owner_number"]) ?></td>
                                                <td><?= $h($c["owner_email"]) ?></td>
                                                <td><span class="sr-chip sr-mono"><?= $h($c["contract_no"]) ?></span></td>
                                                <td><?= $h($c["start_cont_date"]) ?></td>
                                                <td><?= $h($c["end_cont_date"]) ?></td>
                                                <td class="text-right"><span class="sr-money"><?= is_numeric($c["rent"]) ? number_format((float)$c["rent"], 2) : $h($c["rent"]) ?></span></td>
                                                <td class="text-right">
                                                    <div class="sr-actions">
                                                        <a href="./location_profile.php?location_id=<?= (int)$id_loc ?>" target="_blank" class="sr-open-btn"><i class="mdi mdi-eye-outline"></i> <?= __('open_link') ?></a>
                                                        <?php if ($can_delete_children): ?>
                                                            <a href="javascript:void(0);" class="sr-more-btn deleteAjax" title="<?= __('delete_link') ?>" data-id="<?= (int)$c["id"] ?>" data-tbl="location_contract" data-file="0"><i class="mdi mdi-delete text-danger"></i></a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Machines -->
                        <div class="sr-card">
                            <div class="sr-card-head">
                                <h5 class="sr-card-title"><i class="mdi mdi-cellphone-link"></i> <?= $h($section_name) ?> <?= __('machines_detail_header') ?> <span class="sr-count"><?= count($machines) ?></span></h5>
                                <div id="machineButtons" class="sr-toolbar" style="padding: 0; border: 0;"></div>
                            </div>
                            <div class="sr-table-wrap">
                                <table id="mac_trans" class="table sr-table dt-responsive nowrap" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th><?= __('sr_header') ?></th>
                                            <th><?= __('machine_name_header') ?></th>
                                            <th><?= __('m_id_header') ?></th>
                                            <th><?= __('serial_header') ?></th>
                                            <th><?= __('model_header') ?></th>
                                            <th><?= __('issue_date_header') ?></th>
                                            <th><?= __('remarks_header') ?></th>
                                            <th class="text-right"><?= __('action_header') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $x = 1; foreach ($machines as $m): ?>
                                            <tr data-href="./view_machine.php?id=<?= (int)$m["id"] ?>">
                                                <td><span class="sr-line-no"><?= $x++ ?></span></td>
                                                <td><span class="sr-person-name"><?= $h($m["name_mach"]) ?></span></td>
                                                <td><span class="sr-chip sr-mono"><?= $h($m["m_id"]) ?></span></td>
                                                <td class="sr-mono"><?= $h($m["serial"]) ?></td>
                                                <td><?= $h($m["maker_name"]) ?></td>
                                                <td><?= $h($m["made_year"]) ?></td>
                                                <td><?= $h($m["remarks"]) ?></td>
                                                <td class="text-right"><a href="./view_machine.php?id=<?= (int)$m["id"] ?>" class="sr-open-btn"><i class="mdi mdi-eye-outline"></i> <?= __('open_link') ?></a></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>

                <footer class="footer">
                    <?= $site_footer ?>
                </footer>
            </div>
        </div>

        <!-- jQuery  -->
        <script src="assets/js/jquery.min.js"></script>
        <script src="assets/js/bootstrap.bundle.min.js"></script>
        <script src="assets/js/metisMenu.min.js"></script>
        <script src="assets/js/waves.js"></script>
        <script src="assets/js/jquery.slimscroll.js"></script>
        <script type="text/javascript" src="./plugins/parsleyjs/parsley.min.js"></script>
        <script src="./plugins/autoNumeric/autoNumeric.js" type="text/javascript"></script>
        <script src="./plugins/moment/moment.js"></script>
        <script src="./plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>
        <script src="./plugins/bootstrap-timepicker/hijri/bootstrap-hijri-datetimepicker.min.js"></script>

        <!-- Required datatable js -->
        <script src="./plugins/datatables/jquery.dataTables.min.js"></script>
        <script src="./plugins/datatables/dataTables.bootstrap4.min.js"></script>
        <script src="./plugins/datatables/dataTables.buttons.min.js"></script>
        <script src="./plugins/datatables/buttons.bootstrap4.min.js"></script>
        <script src="./plugins/datatables/jszip.min.js"></script>
        <script src="./plugins/datatables/pdfmake.min.js"></script>
        <script src="./plugins/datatables/vfs_fonts.js"></script>
        <script src="./plugins/datatables/buttons.html5.min.js"></script>
        <script src="./plugins/datatables/buttons.print.min.js"></script>
        <script src="./plugins/datatables/dataTables.responsive.min.js"></script>
        <script src="./plugins/datatables/responsive.bootstrap4.min.js"></script>

        <!-- Dropzone js -->
        <script src="./plugins/dropzone/dropzone.js"></script>

        <!-- App js -->
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>
        <script src="assets/js/location_forms.js?v=<?= @filemtime(__DIR__ . '/assets/js/location_forms.js') ?>"></script>

        <?php if ($has_coords): ?>
        <script>
            function initMap() {
                var pos = { lat: <?= (float)$latitude ?>, lng: <?= (float)$longitude ?> };
                var map = new google.maps.Map(document.getElementById('map'), { zoom: 16, center: pos });
                var marker = new google.maps.Marker({ position: pos, map: map, icon: 'assets/images/map-maker/map-maker.png' });
                var information = new google.maps.InfoWindow({ content: $('<h5>').text(<?= json_encode((string)$section_name) ?>)[0] });
                marker.addListener('click', function() { information.open(map, marker); });
            }
        </script>
        <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAAmpMDQXVtsHabQM2U1NqP1rhls03ZxMc&callback=initMap" async defer></script>
        <?php endif; ?>

        <script type="text/javascript">
            $(document).ready(function() {
                const exportTitle = <?= json_encode(__('location_no_label') . ': ' . $section_name) ?>;
                const language = {
                    info: `${__('showing')} _START_ ${__('to')} _END_ ${__('of')} _TOTAL_ ${__('entries')}`,
                    infoEmpty: `${__('showing')} 0 ${__('to')} 0 ${__('of')} 0 ${__('entries')}`,
                    infoFiltered: '',
                    paginate: { first: __('first'), last: __('last'), next: '<i class="mdi mdi-chevron-right"></i>', previous: '<i class="mdi mdi-chevron-left"></i>' },
                    emptyTable: `<div class="sr-empty"><i class="mdi mdi-inbox"></i>${__('no_data_available_in_table')}</div>`,
                    zeroRecords: `<div class="sr-empty"><i class="mdi mdi-magnify"></i>${__('no_matching_records_found')}</div>`
                };
                function exportButtons(columns) {
                    return [
                        { extend: 'excel', text: '<i class="mdi mdi-file-excel"></i> Excel', exportOptions: { columns: columns }, title: exportTitle },
                        { extend: 'pdf', text: '<i class="mdi mdi-file-pdf"></i> PDF', exportOptions: { columns: columns }, title: exportTitle },
                        { extend: 'print', text: '<i class="mdi mdi-printer"></i> ' + <?= json_encode(__('print')) ?>, exportOptions: { columns: columns }, title: exportTitle }
                    ];
                }

                const contracts = $('#location_countrt').DataTable({
                    dom: 'Brtip', pageLength: 10, responsive: true, ordering: false,
                    buttons: exportButtons([0, 1, 2, 3, 4, 5, 6, 7]), language: language
                });
                contracts.buttons().container().appendTo('#contractButtons');

                const machines = $('#mac_trans').DataTable({
                    dom: 'Brtip', pageLength: 10, responsive: true, ordering: false,
                    buttons: exportButtons([0, 1, 2, 3, 4, 5, 6]), language: language
                });
                machines.buttons().container().appendTo('#machineButtons');

                $('#mac_trans tbody').on('click', 'tr', function(e) {
                    if ($(e.target).closest('a, button, .dtr-control').length) return;
                    const href = $(this).data('href');
                    if (href) window.location = href;
                });
            });
        </script>
    </body>
    </html>
<?php } ?>
