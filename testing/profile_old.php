<?php

/****************************************************************
 * MODIFICATION SUMMARY (002-profile.php):
 * 1. UNIFIED SESSION HANDLING: Replaced conditional logic with a single, unconditional call to `session_check.php`. This ensures that all required user data and security checks are consistently performed for every user, fixing potential errors when an employee accesses the page directly.
 * 2. REMOVED REDUNDANT CODE: Deleted a duplicate database query for user data that was already being handled within `session_check.php` and its corresponding `if` block that wrapped the entire page.
 * 3. SIMPLIFIED DATA FETCHING: Replaced a `fetch_all` and `foreach` loop with a more direct `mysqli_fetch_assoc` to retrieve the detailed employee record from `emp_query.php`, making the code cleaner and more efficient.
 ****************************************************************/
require_once("./includes/session_check.php");
include('./includes/MainClass.php');
include("./includes/avatar_select.php");
include("./includes/Hijri_GregorianConvert.php");
$DateConv = new Hijri_GregorianConvert;
// $format="DD/MM/YYYY";
$format = "YYYY-MM-DD";

require("./includes/emp_query.php");

// Fetch the detailed employee record and overwrite the basic $emprow from session_check
$emprow = mysqli_fetch_assoc($get_emp_data);

// Check if employee data was actually found before proceeding
if (!$emprow) {
    // Handle case where employee with the given ID doesn't exist
    // For now, we can just exit to prevent errors. A proper error page would be better.
    die("Employee data not found.");
}

$hours_in_day       = 24;
$minutes_in_hour    = 60;
$seconds_in_mins    = 60;
$birth_date         = new DateTime($emprow['dob']);
$current_date       = new DateTime();
$diff               = $birth_date->diff($current_date);
$years                   = $diff->y . " " . __('years');

if ($emprow['status'] == 0 && $note_get == "expired") {
    $note_get = "Expired";
} elseif ($emprow['status'] == 0 && $note_get == "terminat") {
    $note_get = "Terminated";
}

$date = $DateConv->HijriToGregorian($emprow['iqama_exp'], $format);
$exprydte = date('m-', strtotime($date)); //
$today = date('m');
//      if($exprydte == $today){
//          $thismonthexp = "<div class='alert alert-danger bg-danger text-white border-0' role='alert'>Upcomming Iqama Expiry <strong>".date('d-m',strtotime($date))."</strong> alert—check it out!</div>";
//      }

$salaryItems = ['basic', 'housing', 'transport', 'food', 'misc', 'cashier', 'fuel', 'tel', 'other', 'guard'];
$shownItems = [];
foreach ($salaryItems as $item) {
    if (!empty($emprow[$item]) && $emprow[$item] != "0") {
        $shownItems[] = $item;
    }
}
$countItems = count($shownItems); // Salary items only
$totalBoxes = $countItems + 1; // +1 for Total Salary box
$colsm = "col-sm-" . floor(12 / $totalBoxes); // Default column for all boxes
// Special case: if 5 items, give Total Salary more space (col-sm-4)
$totalColsm = ($countItems == 4) ? "col-sm-4" : $colsm;


$all_statuses = [
    'apply' => 'Applied',
    'pending' => 'Assistant Pending',
    'hr_assistant_approved' => 'HR Assistant Approved',
    'hr_manager_approved' => 'HR Manager Approved',
    'gm_approved' => 'GM Approved',
    'rejected' => 'Rejected'
];

?>
<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?> - View Employee <?= $emprow['name'] ?> Details</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!--        <meta content="A fully featured admin theme which can be used to build CRM, CMS, etc." name="description" />-->
    <meta content="Anees Afzal" name="author" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <!-- App favicon -->
    <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">

    <!-- Modal -->
    <link href="./plugins/custombox/css/custombox.min.css" rel="stylesheet">

    <!-- Plugins css -->
    <link href="./plugins/bootstrap-timepicker/bootstrap-timepicker.min.css" rel="stylesheet">
    <link href="./plugins/bootstrap-datepicker/css/bootstrap-datepicker.min.css" rel="stylesheet">
    <link href="./plugins/clockpicker/css/bootstrap-clockpicker.min.css" rel="stylesheet">
    <link href="./plugins/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">

    <link href="./plugins/bootstrap-timepicker/hijri_css/bootstrap-datetimepicker.css" rel="stylesheet">
    <link href="./plugins/bootstrap-timepicker/hijri_css/bootstrap-datetimepicker.min.css" rel="stylesheet">

    <link href="./plugins/bootstrap-select/css/bootstrap-select.min.css" rel="stylesheet" />
    <link href="./plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />

    <!-- DataTables -->
    <link href="./plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="./plugins/datatables/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <!-- Responsive datatable examples -->
    <link href="./plugins/datatables/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />

    <!-- Multi Item Selection examples -->
    <link href="./plugins/datatables/select.bootstrap4.min.css" rel="stylesheet" type="text/css" />

    <link href="./plugins/summernote/summernote.min.css" rel="stylesheet" />


    <!-- App css -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <!-- <link href="assets/css/icons.css" rel="stylesheet" type="text/css" /> -->
    <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
    <script src="assets/js/modernizr.min.js"></script>

    <link rel="stylesheet" href="./plugins/croppie/croppie.css">
    <style type="text/css">
        /* ============================================
           PROFILE PAGE MODERN MOBILE-FIRST DESIGN
           ============================================ */

        /* Base card styling */
        .card-box.social {
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.15);
            transition: all 0.2s ease-in-out;
            border-radius: 10px !important;
        }

        .card-box.social:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            transform: scale(1.005);
            cursor: pointer;
        }

        /* Info items */
        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.85rem 0.5rem;
            border-bottom: 1px solid #f1f5f7;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #6c757d;
            font-weight: 500;
        }

        .info-value {
            font-weight: 500;
        }

        .edit-btn {
            font-size: 0.8rem;
            padding: .2rem .5rem;
        }

        /* ============================================
           MOBILE-FRIENDLY PROFILE CARD SYSTEM
           ============================================ */

        .profile-cards-container {
            padding: 0;
        }

        .profile-info-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f7;
            flex-wrap: wrap;
            gap: 12px;
            transition: background-color 0.2s ease;
        }

        .profile-info-row:hover {
            background-color: #f8f9fa;
        }

        .profile-info-row:last-child {
            border-bottom: none;
        }

        .profile-info-row .label {
            font-weight: 600;
            color: #495057;
            min-width: 140px;
            flex: 0 0 140px;
            font-size: 14px;
        }

        .profile-info-row .value {
            flex: 1;
            color: #212529;
            word-break: break-word;
            text-align: right;
            font-size: 14px;
        }

        /* Card styling */
        .card {
            border: none;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
            border-radius: 8px;
            transition: all 0.3s ease;
            margin-bottom: 20px;
            overflow: hidden;
        }

        .card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
            transform: translateY(-2px);
        }

        .card-header {
            border-radius: 0 !important;
            border-bottom: 2px solid rgba(0,0,0,0.1) !important;
            padding: 14px 16px !important;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-header h6 {
            font-size: 14px;
            margin: 0;
            font-weight: 600;
        }

        .card-header i {
            font-size: 16px;
        }

        .card-body {
            padding: 0 !important;
        }

        /* ============================================
           EMPLOYEE INFORMATION SECTION CARDS
           ============================================ */

        .employee-cards-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .info-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .info-card:hover {
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
            transform: translateY(-4px);
        }

        .info-card .card-icon {
            font-size: 40px;
            margin-bottom: 12px;
            display: inline-block;
        }

        .info-card .card-body {
            padding: 24px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .info-card .card-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #212529;
        }

        .info-card .card-text {
            font-size: 13px;
            color: #6c757d;
            margin-bottom: 16px;
            flex: 1;
        }

        .info-card .btn {
            align-self: flex-start;
            font-size: 13px;
            padding: 8px 16px;
        }

        /* ============================================
           RESPONSIVE DESIGN - MOBILE FIRST
           ============================================ */

        /* Mobile devices (< 576px) */
        @media (max-width: 575.98px) {
            .profile-info-row {
                flex-direction: column;
                gap: 6px;
                padding: 12px 14px;
            }

            .profile-info-row .label {
                flex: 0 0 100%;
                min-width: auto;
                font-size: 13px;
                margin-bottom: 2px;
                font-weight: 700;
            }

            .profile-info-row .value {
                flex: 0 0 100%;
                font-size: 13px;
                text-align: left;
            }

            .card-header {
                padding: 12px 14px !important;
            }

            .card-header h6 {
                font-size: 14px;
            }

            .card-header i {
                font-size: 16px;
            }

            .card {
                margin-bottom: 16px !important;
            }

            .employee-cards-section {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .info-card .card-body {
                padding: 18px;
            }

            .info-card .card-icon {
                font-size: 36px;
            }

            .profile-user-box {
                padding: 15px !important;
            }
        }

        /* Tablets (576px - 768px) */
        @media (min-width: 576px) and (max-width: 767.98px) {
            .profile-info-row {
                padding: 12px 14px;
            }

            .profile-info-row .label {
                flex: 0 0 130px;
                min-width: 130px;
                font-size: 13px;
            }

            .profile-info-row .value {
                font-size: 13px;
            }

            .card-header {
                padding: 12px 14px !important;
            }

            .card-header h6 {
                font-size: 14px;
            }

            .employee-cards-section {
                grid-template-columns: repeat(2, 1fr);
                gap: 16px;
            }
        }

        /* Desktop (> 768px) */
        @media (min-width: 768px) {
            .profile-info-row {
                padding: 14px 16px;
            }

            .profile-info-row .label {
                flex: 0 0 160px;
                min-width: 160px;
                font-size: 14px;
            }

            .profile-info-row .value {
                font-size: 14px;
            }

            .employee-cards-section {
                grid-template-columns: repeat(5, 1fr);
                gap: 20px;
            }
        }

        /* ============================================
           RTL (Arabic) SUPPORT
           ============================================ */

        [dir="rtl"] .profile-info-row {
            flex-direction: row-reverse;
        }

        [dir="rtl"] .profile-info-row .label {
            text-align: right;
            border-right: 3px solid #007bff;
            border-left: none;
            padding-right: 10px;
            padding-left: 0;
        }

        [dir="rtl"] .profile-info-row .value {
            text-align: right;
        }

        [dir="rtl"] .card-header {
            flex-direction: row-reverse;
        }

        @media (max-width: 575.98px) {
            [dir="rtl"] .profile-info-row .label {
                border-right: 3px solid #007bff;
                border-left: none;
                padding-right: 10px;
                padding-left: 0;
            }
        }

        /* ============================================
           UTILITY CLASSES
           ============================================ */

        /* Copy to clipboard effect */
        .copyToClipboard {
            cursor: pointer;
            position: relative;
            transition: all 0.2s ease;
            padding: 2px 6px;
            border-radius: 4px;
        }

        .copyToClipboard:hover {
            background-color: #e7f3ff;
            color: #0056b3;
        }

        .copyToClipboard i {
            margin-left: 4px;
            font-size: 12px;
            opacity: 0.7;
        }

        /* Font utilities */
        .font-weight-bold {
            font-weight: 600;
        }

        .text-success {
            color: #28a745 !important;
        }

        .text-right {
            text-align: right;
        }

        .font-40 {
            font-size: 40px;
        }

        /* Date batch styling */
        .date-batch-g,
        .date-batch-h {
            display: block;
            line-height: 1.4;
        }

        .date-batch-h {
            color: #6c757d;
            font-size: 12px;
        }

        /* ============================================
           BUTTON STYLING
           ============================================ */

        .btn-outline-primary,
        .btn-outline-success,
        .btn-outline-info,
        .btn-outline-warning,
        .btn-outline-danger {
            font-weight: 500;
            font-size: 12px;
            border-width: 1.5px;
            transition: all 0.2s ease;
        }

        .btn-outline-primary:hover {
            background-color: #007bff;
            border-color: #007bff;
            color: white;
        }

        .btn-outline-success:hover {
            background-color: #28a745;
            border-color: #28a745;
            color: white;
        }

        .btn-outline-info:hover {
            background-color: #17a2b8;
            border-color: #17a2b8;
            color: white;
        }

        .btn-outline-warning:hover {
            background-color: #ffc107;
            border-color: #ffc107;
            color: white;
        }

        .btn-outline-danger:hover {
            background-color: #dc3545;
            border-color: #dc3545;
            color: white;
        }

        /* ============================================
           PROFILE USER BOX (Header Section)
           ============================================ */

        .profile-user-box {
            margin-bottom: 24px;
        }

        .profile-user-box .thumb-lg {
            width: 80px;
            height: 80px;
            object-fit: cover;
        }

        /* ============================================
           SALARY BOXES
           ============================================ */

        .tilebox-one {
            padding: 20px;
            border-radius: 8px;
            text-align: left;
        }

        @media (max-width: 575.98px) {
            .tilebox-one {
                padding: 16px;
            }

            .tilebox-one i {
                font-size: 32px !important;
            }

            .tilebox-one h2 {
                font-size: 20px !important;
            }

            .tilebox-one h6 {
                font-size: 12px !important;
            }
        }

        /* ============================================
           SECTION HEADERS
           ============================================ */

        h5 {
            font-weight: 600;
            color: #212529;
        }

        h5 i {
            margin-right: 8px;
        }

        /* ============================================
           ALERT STYLING
           ============================================ */

        .alert-custom-mocha {
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 24px;
        }

        @media (max-width: 575.98px) {
            .alert-custom-mocha {
                padding: 16px;
            }

            .alert-custom-mocha > div {
                font-size: 18px !important;
            }
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

    <!-- This hidden file input is used by the croppie modal -->
    <input type="file" name="image" class="image" hidden id="img-crop-input" accept="image/*">

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
                <?php include("./includes/employee_menu.php"); ?>
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

                    <!-- /*************************************************/ -->
                    <?php

                    $current_page_name = basename($_SERVER['PHP_SELF']);

                    $file = "./assets/qrcodes/" . $emprow['eid'] . $emprow['empid'] . ".png";
                    (file_exists($file)) ? "" : header("Location: qrconfig_employee.php?hashcode=" . $emprow['empid'] . "&verification=" . $emprow['eid']);
                    $checkGander = ($emprow['sex'] == 1) ? './assets/emp_pics/defult.png' : './assets/emp_pics/defultFemale.jpg';
                    $emp_avatar_get = (file_exists("./assets/emp_pics/" . explode("/", $emprow['avatar'])[3])) ? $emprow['avatar'] : $checkGander;

                    ?>

                    <div class="row">
                        <div class="col-xl-12">
                            <!-- meta -->
                            <div class="profile-user-box card-box <?= ($emprow['status'] == 1 && $emprow['fly'] == 0 ? "bg-dark" : ($emprow['fly'] == 1 ? "bg-warning" : "bg-danger")) ?>">
                                <div class="row">
                                    <div class="col-sm-1">
                                        <div>
                                            <img src="<?= $emprow['avatar'] ?>" alt="<?= $emprow['name'] ?>" class="thumb-lg rounded-circle emp_avat_img">
                                        </div>
                                        <input type="file" name="image" class="image" hidden="" id="img-crop" accept="image/*">
                                    </div>
                                    <div class="col-sm-5">
                                        <div class="media-body text-white">
                                            <h4 class="mt-1 mb-1 font-18"><?= __('name') ?>: <span class="copyToClipboard"><?= htmlspecialchars($emprow['name']) ?></span> <i class="fa fa-clipboard"></i></h4>
                                            <p class="text-light mb-0"><?= __('joining_date') ?>: <?= date('M d Y', strtotime(str_replace('/', '-', $emprow['joining_date']))) ?></p>
                                            <p class="text-light mb-0"><?= __('mobile') ?>: <span class='copyToClipboard'><?= htmlspecialchars($emprow['mobile']) ?></span> <i class='fa fa-clipboard'></i></p>
                                            <p class="text-light mb-0"><?= __('vacation_days') ?>: <?= htmlspecialchars($emprow['vacation_days']) ?></p>
                                        </div>
                                    </div>
                                    <div class="col-sm-2">
                                        <div class="media-body text-white float-right">
                                            <a href="./emp_card/index.php?hashcode=<?= $emprow['empid'] ?>&verification=<?= $emprow['eid'] ?>" target="_blank">
                                                <img src="./assets/qrcodes/<?= $emprow['eid'] . $emprow['empid'] . ".png" ?>" />
                                            </a>
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="text-left">
                                            <p class="text-light mb-0">
                                                <?= __('iqama_id') . ": <span class='copyToClipboard'>" . htmlspecialchars($emprow['iqama']) . "</span> <i class='fa fa-clipboard'></i>"; ?>
                                            </p>
                                            <p class="text-light mb-0"><?= __('employee_no') ?>: <span class='copyToClipboard'><?= htmlspecialchars($emprow['empid']) ?></span> <i class='fa fa-clipboard'></i></p>
                                            <p class="text-light mb-0"><?= __('department') ?>: <?= htmlspecialchars(($is_rtl ?? false ? $emprow["deptnme_ar"] : $emprow["deptnme"]) . " - " . $emprow["sectin_nme"]) ?></p>
                                            <p class="text-light mb-0"><?= __('nationality') ?>: <?= ($is_rtl ?? false ? $emprow["country_name_ar"] : $emprow["country_name"]) ?></p>
                                            <p class="text-light mb-0"><?= __('balance_vacations') ?>:
                                                <?php
                                                $finalvacd = $emprow["total_remaining_leave"];
                                                echo $finalvacd < 0 ? "<span class='badge badge-danger badge-pill'>" . $finalvacd . __('days') . " </span>" : $finalvacd . " " . __('days');
                                                ?>
                                            </p>
                                            <?php if ($emprow["status"] == 0) : ?>
                                                <p class="text-light mb-0">
                                                    <?= $note_get . __('date') . " " . date('d M Y', strtotime($emprow["ter_date"])); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>

                                        <?php if (!in_array($current_page_name, ["apply_vac_emp_dept.php", "add_vac_emp.php", "add_emp_docs.php"])) : ?>
                                            <div class="text-right">
                                                <?php if ($emprow["status"] == 1) : ?>
                                                    <div class="btn-group" role="group" aria-label="Edit Button">
                                                        <button type="button" class="btn btn-sm btn-light dropdown-toggle waves-effect" data-toggle="dropdown" aria-expanded="false">
                                                            <i class="fa fa-chart-simple-horizontal font-18 vertical-middle"></i> <?= __('more') ?>
                                                        </button>
                                                        <div class="dropdown-menu">
                                                            <?php /*if (empty($emprow['has_active_regular_loan'])) : ?>
                                                                <a href="javascript:void(0);" class="text-warning dropdown-item applyLoan" data-emp_id="<?= $emprow['empid'] ?>">
                                                                    <i class="fa fa-money-bill-trend-up"></i> <?= __('apply_loan') ?>
                                                                </a>
                                                            <?php endif; ?>
                                                            <?php if (empty($emprow['has_active_emergency_loan'])) : ?>
                                                                <a href="javascript:void(0);" class="text-info dropdown-item applyEmergencyLoan" data-emp_id="<?= $emprow['empid'] ?>">
                                                                    <i class="fa fa-money-bill-wheat"></i> <?= __('emergency_loan') ?>
                                                                </a>
                                                            <?php endif; ?>
                                                            <?php */ ?>
                                                            <?php if ($isEmployee || $isAssistant) : ?>
                                                                <a href="javascript:void(0);" id="startUpdateRequest" data-avatar="<?= $emprow['avatar'] ?>" data-empid="<?= $emprow['empid'] ?>" class="text-primary dropdown-item">
                                                                    <i class="fa fa-user-pen"></i> <?= __('update_information') ?>
                                                                </a>
                                                            <?php endif; ?>
                                                            <?php ?>
                                                            <?php if ($emprow['apd_status'] != 'approve' && $emprow["fly"] == 0) : ?>
                                                                <a href="javascript:void(0);" data-empid="<?= $emprow['empid'] ?>" data-dept="<?= $emprow['dept'] ?>" data-country="<?= $emprow['country'] ?>" class="text-dark dropdown-item applyvacationAtter d-flex align-items-center">
                                                                    <i class="fa fa-user-chart mr-2"></i> <?= __('apply_annual_vacation') ?>
                                                                </a>
                                                            <?php endif; /* ?>
                                                            <?php if ($emprow['apd_review'] == "A" && $emprow['apd_status'] == "apply") : ?>
                                                                <?php
                                                                $status_text = $all_statuses[$emprow['apd_status']] ?? 'Unknown';
                                                                $badge_class = 'secondary';
                                                                switch ($req['approval_status']) {
                                                                    case 'apply':
                                                                        $badge_class = 'info';
                                                                        break;
                                                                    case 'pending':
                                                                        $badge_class = 'warning';
                                                                        break;
                                                                    case 'gm_approved':
                                                                        $badge_class = 'success';
                                                                        break;
                                                                    case 'rejected':
                                                                        $badge_class = 'danger';
                                                                        break;
                                                                    default:
                                                                        $badge_class = 'primary';
                                                                        break;
                                                                }
                                                                ?>
                                                                <a class="text-warning dropdown-item">
                                                                    <i class="fa fa-user-check"></i> <?= htmlspecialchars($status_text) ?>
                                                                </a>
                                                            <?php endif; */ ?>
                                                            <a href="javascript:void(0);" data-empid="<?= $emprow['empid'] ?>" class="text-info dropdown-item applyLeaveRequest">
                                                                <i class="fa fa-user-clock"></i> <?= __('apply_leave') ?>
                                                            </a>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>

                                    </div>
                                </div>

                            </div>
                            <!--/ meta -->
                            <?php /*if (mysqli_num_rows($getquerysocial) >= 1): ?>

    <div class="row">
    <?php
        $socquery = mysqli_query($conDB, "SELECT `social_list`.*, `social`.*, `social`.`id` AS `eslid` FROM `social_list` LEFT JOIN `social` ON `social`.`social_id` = `social_list`.`id` WHERE `social`.`emp_id`='".$emprow['empid']."' ORDER BY `social_list`.`id` ASC ");
        while($rec = mysqli_fetch_assoc($socquery)){
            $mainlink = parse_url($rec['link']);
            $social = explode('//',$mainlink['host'])[0];
            $link = ucfirst(explode('.',$social)[0]);
    ?>
        <div class="col-md-2 col-xl-2">
        <div class="card-box tilebox-one social">
        <?php if ($emprow['user_type'] == $access1 AND $current_page_name <> "view_employee.php"): ?> 
            <a href="javascript:void(0);" style="margin-top:-15px; margin-right: -15px;" class="float-right text-danger deleteAjax" data-id="<?=$rec['eslid']?>" data-tbl='social' data-file='0'>
                <i class='fa fa-minus-circle font-18 vertical-middle'></i>
            </a>
        <?php endif ?>
            <div onclick="window.open('<?=$rec["link"].$rec["s_link"]?>', '_blank')">                   
                <i class="<?=$rec['icon']?> float-right" style="color:<?=$rec['color']?>; font-size: 48px"></i>
                <h6 class="text-uppercase mt-0" style="color:<?=$rec['color']?>" ><?=$link?></h6>
                <a href="javascript:void(0);" class="text-muted" style="font-size: 10px;">@<?=$rec['s_link']?></a>
            </div>
        </div>
    </div>
    <?php } ?>
    </div>
<?php endif  ?>

<div class="row">
    <div class="col-sm-6">
        
    </div>
    <div class="col-sm-6">
        <div class="btn-group float-right" role="group" aria-label="Edit Button">
        <?php if ($emprow['status'] == "1"): ?>
            <?php if ($empsocialcount_get < 9): ?>
                <a href="javascript:void(0);" class="btn-sm btn btn-info waves-effect btn-rounded addSocial" data-emp_id="<?=$emprow['empid']?>">
                    <i class="mdi mdi-link-variant"></i> Add Social Media
                </a>
            <?php endif ?>
            <?php if (!$description_get): ?>
                <a href="javascript:void(0);" class="btn-sm btn btn-dark waves-effect btn-rounded addPortfolio" data-emp_id="<?=$emprow['empid']?>">
                    <i class="mdi mdi mdi-account-card-details"></i> Add Portfolio Dedails
                </a>
            <?php endif ?>
        <?php endif ?>
        </div>
    </div>
</div>

<?php */ ?>

                            <br>
                        </div>
                    </div>

                    <!-- /*************************************************/ -->
                    <div class="alert alert-custom-mocha alert-dismissible bg-custom-mocha text-white border-0 fade show" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                        <div style="color: #fff; font-size: 23px;"> <?= __('happy_life_with_us') . " " . ageDOB($emprow['joining_date']) ?></div>
                    </div>
                    <!-- /*************************************************/ -->


                    <div class="row">
                        <div class="col-xl-12">

                            <div class="row">
                                <?php foreach ($shownItems as $item): ?>
                                    <div class="<?= $colsm ?>">
                                        <div class="card-box tilebox-one">
                                            <?php
                                            $icons = [
                                                'basic' => 'fa-money-bill-alt duotone-success',
                                                'housing' => 'fa-home duotone-info',
                                                'transport' => 'fa-car duotone-danger',
                                                'food' => 'fa-money-bill-wheat duotone-info',
                                                'misc' => 'fa-diamond-half duotone-dark',
                                                'cashier' => 'fa-cash-register duotone-success',
                                                'fuel' => 'fa-car-wash duotone-info',
                                                'tel' => 'fa-user-headset duotone-info',
                                                'other' => 'fa-person-carry duotone-dark',
                                                'guard' => 'fa-hands-holding-diamond duotone-success',
                                            ];
                                            $icon = $icons[$item] ?? 'fa-money-bill duotone-secondary';
                                            ?>
                                            <i class="fad <?= $icon ?> float-right"></i>
                                            <h6 class="text-muted text-uppercase mt-0"><?= __($item, ucfirst($item)) ?></h6>
                                            <h2 class="m-b-20" data-plugin="counterup"><?= $emprow[$item] ?> <i class="icon-saudi_riyal"></i></h2>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <div class="<?= $totalColsm ?>">
                                    <div class="card-box tilebox-one">
                                        <i class="fad fa-money-bill-trend-up float-right duotone-success"></i>
                                        <h6 class="text-muted text-uppercase mt-0"><?= __('total_salary') ?></h6>
                                        <h2 class="m-b-20" data-plugin="counterup"><?= (!$salary_get) ? $emprow["salary"] : $salary_get ?> <i class="icon-saudi_riyal"></i></h2>
                                    </div>
                                </div>
                            </div><!-- end row -->

                            <?php if ($emprow["emp_sup_type"] <> "man_power") { ?>

                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="card-box tilebox-one">
                                            <i class="fad fa-truck-plane float-right duotone-info"></i>
                                            <h6 class="text-uppercase mt-0 text-muted">
                                                <?php
                                                if ($emprow["country"] == 191 or $emprow["country"] == 150) {
                                                    echo __('encashed');
                                                } else {
                                                    echo __('flys');
                                                }
                                                ?>
                                            </h6>
                                            <h2 class="m-b-20" data-plugin="counterup"><?= $emprow['flystus'] ?></h2>
                                        </div>
                                    </div><!-- end col -->
                                    <div class="col-sm-6">
                                        <div class="card-box tilebox-one">
                                            <i class="fad fa-money-from-bracket float-right duotone-info"></i>
                                            <h6 class="text-uppercase mt-0 text-muted"><?= __('encashed') ?></h6>
                                            <h2 class="m-b-20"><span data-plugin="counterup"><?= $emprow['encashstus'] ?></span></h2>
                                        </div>
                                    </div><!-- end col -->
                                </div><!-- end col -->

                            <?php } ?>


                        </div>
                    </div>




                    <div class="row">
                        <div class="col-12">
                            <div class="card-box">
                                <h4 class="header-title m-t-0 m-b-30"><?= __('employee_information') ?></h4>

                            <!-- MOBILE-FRIENDLY PROFILE CARDS LAYOUT -->
                            <div class="profile-cards-container">
                                
                                <!-- PERSONAL INFORMATION SECTION -->
                                <div class="card mb-3">
                                    <div class="card-header bg-primary text-white">
                                        <h6 class="mb-0"><i class="fa fa-user-circle mr-2"></i> Personal Information</h6>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="profile-info-row">
                                            <span class="label">Name:</span>
                                            <span class="value copyToClipboard"><?= htmlspecialchars($emprow['name']) ?> <i class="fa fa-clipboard"></i></span>
                                        </div>
                                        <div class="profile-info-row">
                                            <span class="label">Employee ID:</span>
                                            <span class="value copyToClipboard"><?= htmlspecialchars($emprow['empid']) ?> <i class="fa fa-clipboard"></i></span>
                                        </div>
                                        <div class="profile-info-row">
                                            <span class="label">Email:</span>
                                            <span class="value">
                                                <?php if ($emprow['c_email']): ?>
                                                    <small><b>Personal:</b> <span class="copyToClipboard"><?= htmlspecialchars($emprow['email']) ?></span> <i class="fa fa-clipboard"></i></small><br>
                                                    <small><b>Company:</b> <span class="copyToClipboard"><?= htmlspecialchars($emprow['c_email']) ?></span> <i class="fa fa-clipboard"></i></small>
                                                <?php else: ?>
                                                    <span class="copyToClipboard"><?= htmlspecialchars($emprow['email']) ?> <i class="fa fa-clipboard"></i></span>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                        <div class="profile-info-row">
                                            <span class="label">Date of Birth:</span>
                                            <span class="value">
                                                <small class="date-batch-g"><?= $emprow["dob"] ?></small><br>
                                                <small class="text-muted date-batch-h"><?= $DateConv->GregorianToHijri($emprow["dob"], $format) ?></small>
                                            </span>
                                        </div>
                                        <div class="profile-info-row">
                                            <span class="label">Age:</span>
                                            <span class="value"><?= ($emprow["dob"] <> "") ? $years : "N/A" ?></span>
                                        </div>
                                        <div class="profile-info-row">
                                            <span class="label">Gender | Blood:</span>
                                            <span class="value"><?= ucfirst(__($emprow["sex"])) . " | " . $emprow['blood_type'] ?></span>
                                        </div>
                                        <div class="profile-info-row">
                                            <span class="label">Marital Status:</span>
                                            <span class="value"><?= ucfirst(__($emprow["mar_status"])) ?></span>
                                        </div>
                                        <div class="profile-info-row">
                                            <span class="label">Mobile:</span>
                                            <span class="value copyToClipboard"><?= htmlspecialchars($emprow['mobile']) ?> <i class="fa fa-clipboard"></i></span>
                                        </div>
                                        <div class="profile-info-row">
                                            <span class="label">Country:</span>
                                            <span class="value"><?= ($is_rtl ?? false) ? $emprow["country_name_ar"] : $emprow["country_name"] ?></span>
                                        </div>
                                        <div class="profile-info-row">
                                            <span class="label">Address:</span>
                                            <span class="value"><?= ucfirst($emprow['address']) ?></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- IDENTIFICATION SECTION -->
                                <div class="card mb-3">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0"><i class="fa fa-id-card mr-2"></i> Identification Documents</h6>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="profile-info-row">
                                            <span class="label">Iqama ID:</span>
                                            <span class="value copyToClipboard"><?= htmlspecialchars($emprow['iqama']) ?> <i class="fa fa-clipboard"></i></span>
                                        </div>
                                        <div class="profile-info-row">
                                            <span class="label">ID Expiry:</span>
                                            <span class="value">
                                                <small class="date-batch-h"><?= $emprow['iqama_exp'] ?></small><br>
                                                <small class="text-muted date-batch-g"><?= $DateConv->HijriToGregorian($emprow['iqama_exp'], $format) ?></small>
                                            </span>
                                        </div>
                                        <div class="profile-info-row">
                                            <span class="label">Passport No:</span>
                                            <span class="value copyToClipboard"><?= htmlspecialchars($emprow['passport_number']) ?> <i class="fa fa-clipboard"></i></span>
                                        </div>
                                        <div class="profile-info-row">
                                            <span class="label">Passport Expiry:</span>
                                            <span class="value">
                                                <?php if ($emprow['passport_exp']): ?>
                                                    <small class="date-batch-g"><?= $emprow['passport_exp'] ?></small><br>
                                                    <small class="text-muted date-batch-h"><?= $DateConv->GregorianToHijri($emprow['passport_exp'], $format) ?></small>
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                        <div class="profile-info-row">
                                            <span class="label">Insurance No:</span>
                                            <span class="value"><?= htmlspecialchars($emprow['insurance_no']) . " | " . htmlspecialchars($emprow['insurance_class']) ?></span>
                                        </div>
                                        <div class="profile-info-row">
                                            <span class="label">Insurance Expiry:</span>
                                            <span class="value">
                                                <?php if ($emprow['insurance_exp']): ?>
                                                    <small class="date-batch-g"><?= $emprow['insurance_exp'] ?></small><br>
                                                    <small class="text-muted date-batch-h"><?= $DateConv->GregorianToHijri($emprow['insurance_exp'], $format) ?></small>
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- EMPLOYMENT INFORMATION SECTION -->
                                <div class="card mb-3">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0"><i class="fa fa-briefcase mr-2"></i> Employment Information</h6>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="profile-info-row">
                                            <span class="label">Joining Date:</span>
                                            <span class="value">
                                                <small class="date-batch-g"><?= $emprow["joining_date"] ?></small><br>
                                                <small class="text-muted date-batch-h"><?= $DateConv->GregorianToHijri($emprow["joining_date"], $format) ?></small>
                                            </span>
                                        </div>
                                        <div class="profile-info-row">
                                            <span class="label">Department:</span>
                                            <span class="value"><?= ($is_rtl ?? false) ? $emprow["deptnme_ar"] : $emprow["deptnme"] ?></span>
                                        </div>
                                        <div class="profile-info-row">
                                            <span class="label">Section:</span>
                                            <span class="value"><?= htmlspecialchars($emprow["sectin_nme"]) ?></span>
                                        </div>
                                        <div class="profile-info-row">
                                            <span class="label">Position/Job:</span>
                                            <span class="value"><?= ($is_rtl ?? false ? $emprow["jobname_ar"] : $emprow["jobname"]) ?></span>
                                        </div>
                                        <div class="profile-info-row">
                                            <span class="label">Direct Supervisor:</span>
                                            <span class="value">
                                                <?php 
                                                    if (!empty($emprow['supervisor_id'])) {
                                                        $sup_query = "SELECT name FROM employees WHERE empid = ?";
                                                        $sup_stmt = $conDB->prepare($sup_query);
                                                        $sup_stmt->bind_param("s", $emprow['supervisor_id']);
                                                        $sup_stmt->execute();
                                                        $sup_result = $sup_stmt->get_result()->fetch_assoc();
                                                        echo htmlspecialchars($sup_result['name'] ?? 'N/A');
                                                    } else {
                                                        echo 'N/A';
                                                    }
                                                ?>
                                            </span>
                                        </div>
                                        <div class="profile-info-row">
                                            <span class="label">Contract Period:</span>
                                            <span class="value"><?= formatPeriod($emprow["period"]) ?></span>
                                        </div>
                                        <div class="profile-info-row">
                                            <span class="label">T-Shirt Size:</span>
                                            <span class="value"><?= ucfirst($emprow['t_shirt_size']) ?></span>
                                        </div>
                                        <div class="profile-info-row">
                                            <span class="label">Probation Period:</span>
                                            <span class="value"><?= $probationStatus ?></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- SALARY & PAYMENT SECTION -->
                                <div class="card mb-3">
                                    <div class="card-header bg-warning text-white">
                                        <h6 class="mb-0"><i class="fa fa-money-bill mr-2"></i> Salary & Payment</h6>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="profile-info-row">
                                            <span class="label">Total Salary:</span>
                                            <span class="value font-weight-bold text-success"><?= number_format($emprow['salary'], 2) ?> <i class="icon-saudi_riyal"></i></span>
                                        </div>
                                        <div class="profile-info-row">
                                            <span class="label">Payment Type:</span>
                                            <span class="value">
                                                <?= ($emprow['payment_type'] == 1 ? 'Bank Transfer' : ($emprow['payment_type'] == 2 ? 'Cash Payment' : 'To Be Determined')) ?>
                                            </span>
                                        </div>
                                        <div class="profile-info-row">
                                            <span class="label">Bank Name:</span>
                                            <span class="value"><?= ($is_rtl ?? false) ? $emprow["b_name_ar"] : $emprow["b_name"] ?></span>
                                        </div>
                                        <div class="profile-info-row">
                                            <span class="label">IBAN:</span>
                                            <span class="value copyToClipboard"><?= htmlspecialchars($emprow["iban"]) ?> <i class="fa fa-clipboard"></i></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- GOSI & SPONSORSHIP SECTION -->
                                <div class="card mb-3">
                                    <div class="card-header bg-secondary text-white">
                                        <h6 class="mb-0"><i class="fa fa-shield mr-2"></i> GOSI & Sponsorship</h6>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="profile-info-row">
                                            <span class="label">GOSI Status:</span>
                                            <span class="value"><?= htmlspecialchars($emprow["gosi"]) ?></span>
                                        </div>
                                        <div class="profile-info-row">
                                            <span class="label">GOSI No:</span>
                                            <span class="value copyToClipboard"><?= htmlspecialchars($emprow["gosi_no"]) ?> <i class="fa fa-clipboard"></i></span>
                                        </div>
                                        <div class="profile-info-row">
                                            <span class="label">GOSI Expiry:</span>
                                            <span class="value">
                                                <?php if ($emprow["date_hijri"]): ?>
                                                    <small class="date-batch-h"><?= htmlspecialchars($emprow["date_hijri"]) ?></small><br>
                                                    <small class="text-muted date-batch-g"><?= htmlspecialchars($emprow["date_greg"]) ?></small>
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                        <div class="profile-info-row">
                                            <span class="label">Sponsorship:</span>
                                            <span class="value"><?= htmlspecialchars($emprow['sponsor']) ?></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- VEHICLE & ASSETS SECTION -->
                                <?php if (car_get_info($emprow["car_id"])) { ?>
                                <div class="card mb-3">
                                    <div class="card-header bg-dark text-white">
                                        <h6 class="mb-0"><i class="fa fa-car mr-2"></i> Vehicle Information</h6>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="profile-info-row">
                                            <span class="label">Car Maker:</span>
                                            <span class="value"><?= htmlspecialchars(car_get_info($emprow["car_id"])['maker_name']) ?></span>
                                        </div>
                                        <div class="profile-info-row">
                                            <span class="label">Car Model:</span>
                                            <span class="value"><?= htmlspecialchars(car_get_info($emprow["car_id"])['model']) ?></span>
                                        </div>
                                        <div class="profile-info-row">
                                            <span class="label">Year:</span>
                                            <span class="value"><?= htmlspecialchars(car_get_info($emprow["car_id"])['made_year']) ?></span>
                                        </div>
                                    </div>
                                </div>
                                <?php } ?>

                                <!-- EMERGENCY CONTACT SECTION -->
                                <div class="card mb-3">
                                    <div class="card-header bg-danger text-white">
                                        <h6 class="mb-0"><i class="fa fa-phone-volume mr-2"></i> Emergency Contact</h6>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="profile-info-row">
                                            <span class="label">Name:</span>
                                            <span class="value"><?= htmlspecialchars($emprow['emg_name']) ?></span>
                                        </div>
                                        <div class="profile-info-row">
                                            <span class="label">Phone:</span>
                                            <span class="value copyToClipboard"><?= htmlspecialchars($emprow["emg_mobile"]) ?> <i class="fa fa-clipboard"></i></span>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            </div>

                        </div>
                    </div>

                    <!-- Employee Related Pages Section -->
                    <div class="row mt-5 mb-4">
                        <div class="col-12">
                            <div class="mb-4">
                                <h5 class="mb-1" style="font-size: 18px; font-weight: 600;">
                                    <i class="fa fa-folder-open mr-2 text-primary"></i>Employee Information & Records
                                </h5>
                                <p class="text-muted mb-0">Quick access to your employment records and documents</p>
                            </div>
                        </div>
                    </div>

                    <div class="employee-cards-section">
                        <!-- Vacation History Card -->
                        <div class="info-card border-left border-primary" style="border-left: 4px solid #007bff;">
                            <div class="card-body">
                                <div class="card-icon text-primary">
                                    <i class="fa fa-calendar-check"></i>
                                </div>
                                <h6 class="card-title">Vacation History</h6>
                                <p class="card-text">View all vacation requests, approvals, and remaining balance</p>
                                <a href="employee_vacation_history.php?emp_id=<?= $emprow['empid'] ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fa fa-arrow-right mr-1"></i>View
                                </a>
                            </div>
                        </div>

                        <!-- Loan History Card -->
                        <div class="info-card border-left border-success" style="border-left: 4px solid #28a745;">
                            <div class="card-body">
                                <div class="card-icon text-success">
                                    <i class="fa fa-money-bill"></i>
                                </div>
                                <h6 class="card-title">Loan History</h6>
                                <p class="card-text">Track all loan applications, approvals, and payment status</p>
                                <a href="employee_loan_history.php?emp_id=<?= $emprow['empid'] ?>" class="btn btn-sm btn-outline-success">
                                    <i class="fa fa-arrow-right mr-1"></i>View
                                </a>
                            </div>
                        </div>

                        <!-- Assigned Assets Card -->
                        <div class="info-card border-left border-info" style="border-left: 4px solid #17a2b8;">
                            <div class="card-body">
                                <div class="card-icon text-info">
                                    <i class="fa fa-car"></i>
                                </div>
                                <h6 class="card-title">Assigned Assets</h6>
                                <p class="card-text">View all assigned vehicles and equipment details</p>
                                <a href="employee_assigned_assets.php?emp_id=<?= $emprow['empid'] ?>" class="btn btn-sm btn-outline-info">
                                    <i class="fa fa-arrow-right mr-1"></i>View
                                </a>
                            </div>
                        </div>

                        <!-- Payroll Slips Card -->
                        <div class="info-card border-left border-warning" style="border-left: 4px solid #ffc107;">
                            <div class="card-body">
                                <div class="card-icon text-warning">
                                    <i class="fa fa-file-invoice-dollar"></i>
                                </div>
                                <h6 class="card-title">Payroll Slips</h6>
                                <p class="card-text">Download and view your monthly salary slips</p>
                                <a href="employee_payroll_slip.php?emp_id=<?= $emprow['empid'] ?>" class="btn btn-sm btn-outline-warning">
                                    <i class="fa fa-arrow-right mr-1"></i>View
                                </a>
                            </div>
                        </div>

                        <!-- Warnings & Discipline Card -->
                        <div class="info-card border-left border-danger" style="border-left: 4px solid #dc3545;">
                            <div class="card-body">
                                <div class="card-icon text-danger">
                                    <i class="fa fa-exclamation-circle"></i>
                                </div>
                                <h6 class="card-title">Warnings</h6>
                                <p class="card-text">View any disciplinary records and warnings on file</p>
                                <a href="employee_warnings.php?emp_id=<?= $emprow['empid'] ?>" class="btn btn-sm btn-outline-danger">
                                    <i class="fa fa-arrow-right mr-1"></i>View
                                </a>
                            </div>
                        </div>
                    </div>
                    <!-- End Employee Related Pages Section -->

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
    <!--        <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>-->
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/metisMenu.min.js"></script>
    <script src="assets/js/waves.js"></script>
    <script src="assets/js/jquery.slimscroll.js"></script>


    <!-- Modal-Effect -->
    <script type="text/javascript" src="./plugins/parsleyjs/parsley.min.js"></script>
    <script src="./plugins/bootstrap-inputmask/bootstrap-inputmask.min.js" type="text/javascript"></script>
    <script src="./plugins/autoNumeric/autoNumeric.js" type="text/javascript"></script>


    <!-- <script src="./plugins/moment/moment.js"></script> -->
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.18.1/moment.min.js"></script>

    <script src="./plugins/bootstrap-timepicker/bootstrap-timepicker.js"></script>
    <script src="./plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>

    <!-- <script src="./assets/pages/jquery.form-pickers.init.js"></script> -->
    <script src="./plugins/croppie/croppie.js" type="text/javascript"></script>
    <script src="./plugins/croppie/croppie.min.js" type="text/javascript"></script>
    <script src="./plugins/croppie/exif.js" type="text/javascript"></script>

    <script src="./plugins/bootstrap-timepicker/hijri/bootstrap-hijri-datetimepicker.js"></script>
    <script src="./plugins/bootstrap-timepicker/hijri/bootstrap-hijri-datetimepicker.min.js"></script>
    <script src="./plugins/bootstrap-timepicker/hijri/bootstrap-hijri-datetimepickermin.js"></script>

    <script src="./plugins/bootstrap-inputmask/bootstrap-inputmask.min.js" type="text/javascript"></script>

    <!-- App js -->

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
    <script src="assets/js/jquery.app.js"></script>

    <script src="./plugins/summernote/summernote.min.js"></script>
    <!-- <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script> -->
    <script src="assets/js/loanHandling.js"></script>

    <!--/***************************************/-->

    <script type="text/javascript">
        $(document).ready(function() {

            var buttonConfig = [];
            var exportTitle = "Name: <?= $emprow['name'] ?>"
            buttonConfig.push({
                extend: 'excel',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7]
                },
                title: exportTitle,
                className: 'btn-success'
            });
            buttonConfig.push({
                extend: 'pdf',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7]
                },
                title: exportTitle,
                className: 'btn-danger'
            });
            buttonConfig.push({
                extend: 'print',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7]
                },
                title: exportTitle,
                className: 'btn-dark'
            });
            // buttonConfig.push({text: '<i class="fa fa-plus"></i> Add Machine', action: function ( e, dt, button, config ) {window.location = './add_machine.php' } ,className: 'btn-info'});
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

        $(document).on('click', '.empAvatarShowProfile', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            var emp_id = $(this).data('emp_id');
            var emp_name = $(this).data('emp_name');
            var img = $(this).data('img');
            var emptype = $(this).data('emptype');
            Swal.fire({
                title: 'Chnage Employee Image',
                html: `
        <div class="row customSweetAlertMLR" >
            <div class="col-md-6 text-center">
                <div id="emp-img" style="width:350px"></div>
            </div>
            <div class="col-md-6" style="align-items: center; display: grid; justify-content: center;">
                <img src="${img}" style="width:200px;height:200px" />
            </div>
        </div>`,
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, Update!',
                showLoaderOnConfirm: true,
                allowOutsideClick: false,
                width: '40%',
                willOpen: function(e) {
                    $('.image').trigger('click');
                    var reader, file;
                    $uploadCrop = $('#emp-img').croppie({
                        enableExif: true,
                        viewport: {
                            width: 300,
                            height: 300,
                            type: 'circle', //type: 'circle',square
                        },
                        boundary: {
                            width: 350,
                            height: 350,
                        }
                    });
                    $('#img-crop').on('change', function() {
                        var reader = new FileReader();
                        reader.onload = function(e) {
                            $uploadCrop.croppie('bind', {
                                url: e.target.result
                            }).then(function() {
                                console.log('jQuery bind complete');
                            }).catch(function(error) {
                                Swal.fire({
                                    title: "File error..",
                                    text: 'Please select JPG format only.',
                                    icon: 'error',
                                    allowOutsideClick: false
                                })
                            });
                        };
                        reader.readAsDataURL(this.files[0]);
                    });
                },
                preConfirm: function() {
                    return new Promise(function(resolve) {
                        $uploadCrop.croppie('result', {
                            type: 'canvas',
                            format: 'jpeg' | 'png' | 'webp',
                            size: 'viewport'
                        }).then(function(resp) {
                            $.ajax({
                                url: "./includes/ajaxFile/hrHandler.php",
                                type: "POST",
                                dataType: "JSON",
                                data: {
                                    "image": resp,
                                    "id": id,
                                    "emp_id": emp_id,
                                    "emp_name": emp_name,
                                    "emptype": emptype,
                                    ajaxType: 'avatar'
                                },
                                success: function(response) {
                                    Swal.fire({
                                        title: response.title,
                                        text: response.message,
                                        icon: response.type,
                                        allowOutsideClick: false
                                    }).then(function(isConfirm) {
                                        (isConfirm) ? location.reload(): ""
                                    });
                                }
                            });
                        });
                    });
                },
            })
        });
    </script>

</body>

</html>