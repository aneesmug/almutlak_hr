<?php
require_once __DIR__ . '/includes/db.php';

require_once __DIR__ . '/includes/session_check.php';
$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='" . $username . "'");
if (mysqli_num_rows($query) == 1) {
    include("./includes/avatar_select.php");

    // Include the new dynamic pagination function
    require_once __DIR__ . '/includes/dynamic_pagination.php';

    // --- Configuration & Dynamic Query Building ---
    $table_name = 'employees';
    $per_page = 12; // Number of records per page
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    $pageLimit = ($page * $per_page) - $per_page; // Calculate the starting record for the query

    $where_conditions = []; // Start with an empty array for WHERE clauses
    $url_params = []; // Store parameters for pagination links

    // Add filter for 'status' if it exists in the URL
    if (isset($_GET['status'])) {
        $status = mysqli_real_escape_string($conDB, $_GET['status']);
        $where_conditions[] = "`status` = '{$status}'";
        $url_params['status'] = $_GET['status']; // Add to URL for pagination links
    }

    // Add filter for 'fly' if it exists in the URL
    if (isset($_GET['fly'])) {
        $fly = mysqli_real_escape_string($conDB, $_GET['fly']);
        $where_conditions[] = "`fly` = '{$fly}'";
        $url_params['fly'] = $_GET['fly']; // Add to URL for pagination links
    }

    // Add filter for 'local_vac' if requested (current local vacation employees)
    if (isset($_GET['local_vac'])) {
        $local_vac = strtolower(trim($_GET['local_vac']));
        if ($local_vac === '1' || $local_vac === 'yes' || $local_vac === 'true') {
            // Employees currently on approved encashed vacation (regardless of fly field)
                $where_conditions[] = "`fly`='1'";
                $where_conditions[] = "EXISTS (SELECT 1 FROM emp_vacation ev1 WHERE ev1.emp_id = employees.emp_id AND ev1.current_status='approved' AND ev1.vac_type IN ('Local Vacation','Encashed') AND ev1.id = (SELECT MAX(ev2.id) FROM emp_vacation ev2 WHERE ev2.emp_id = employees.emp_id AND ev2.current_status='approved'))";
            $url_params['local_vac'] = '1';
        }
    }
    
        // Add filter for 'departed' (current fly vacation employees - latest approved vac_type='Fly')
        if (isset($_GET['departed'])) {
            $departed = strtolower(trim($_GET['departed']));
            if ($departed === '1' || $departed === 'yes' || $departed === 'true') {
                $where_conditions[] = "`fly`='1'";
                $where_conditions[] = "EXISTS (SELECT 1 FROM emp_vacation ev1 WHERE ev1.emp_id = employees.emp_id AND ev1.current_status='approved' AND ev1.vac_type='Fly' AND ev1.id = (SELECT MAX(ev2.id) FROM emp_vacation ev2 WHERE ev2.emp_id = employees.emp_id AND ev2.current_status='approved'))";
                $url_params['departed'] = '1';
            }
        }

?>
    <!doctype html>
    <html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

    <head>
        <meta charset="utf-t" />
        <title><?= $site_title ?> - All Employees</title>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <!--        <meta content="A fully featured admin theme which can be used to build CRM, CMS, etc." name="description" />-->
        <meta content="Anees Afzal" name="author" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />

        <!-- App favicon -->
        <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">

        <!-- Modal -->
        <link href="./plugins/custombox/css/custombox.min.css" rel="stylesheet">

        <!-- App css -->
        <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
        <script src="assets/js/modernizr.min.js"></script>
        <style type="text/css">
            .card-box.bg-light,
            .card-box.bg-warning,
            .card-box.bg-danger {
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.15);
                transition: all 0.3s ease-in-out;
                border-radius: 10px !important;
            }

            .card-box.bg-light:hover,
            .card-box.bg-warning:hover,
            .card-box.bg-danger:hover {
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
                transform: scale(1.005);
                cursor: pointer;
            }
        </style>
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

                            <?php
                            // Handle department-specific user access from the session
                            if (isset($user_type) && $user_type == "dept_user" && isset($_SESSION['user_dept'])) {
                                $user_dept = mysqli_real_escape_string($conDB, $_SESSION['user_dept']);
                                $where_conditions[] = "`dept` = '{$user_dept}'";
                            }

                            // --- Construct Final Queries ---
                            $where_sql = "";
                            // Only add WHERE clause if there are conditions
                            if (!empty($where_conditions)) {
                                $where_sql = " WHERE " . implode(' AND ', $where_conditions);
                            }

                            // The COUNT query needed by the pagination function
                            $count_query = "SELECT COUNT(*) as totalCount FROM `{$table_name}` {$where_sql}";

                            // The query to get the actual data for the current page
                            $sql = "SELECT * FROM `{$table_name}` {$where_sql} LIMIT {$pageLimit} , {$per_page}";

                            $query = mysqli_query($conDB, $sql);


                            while ($rec = mysqli_fetch_array($query)) {
                                $id = $rec["id"];
                                $name = $rec["name"];
                                $emp_id = $rec["emp_id"];
                                $iqama = $rec["iqama"];
                                $mobile = $rec["mobile"];
                                $salary = $rec["salary"];
                                $vacation_days = $rec["vacation_days"];
                                $joining_date = $rec["joining_date"];
                                $date_reg = $rec["created_at"];
                                $emp_avatar = $rec["avatar"];
                                $emp_status = $rec["status"];
                                $emp_status_fly = $rec["fly"];
                                $emptype = $rec["emptype"];
                                $sex_get = $rec["sex"];
                                

                                $sql_count = mysqli_query($conDB, "SELECT COUNT(*) `emp_id` FROM `emp_vacation` WHERE `emp_id`='" . $emp_id . "' ");
                                $status_cont = mysqli_fetch_array($sql_count)[0];

                                $sql_count_fly = mysqli_query($conDB, "SELECT COUNT(*) `emp_id` FROM `emp_vacation` WHERE `emp_id`='" . $emp_id . "' && `note`='Fly' ");
                                $cont_fly = mysqli_fetch_array($sql_count_fly)[0];

                                $sql_count_encashed = mysqli_query($conDB, "SELECT COUNT(*) `emp_id` FROM `emp_vacation` WHERE `emp_id`='" . $emp_id . "' && `note`='Encashed' ");
                                $cont_encashed = mysqli_fetch_array($sql_count_encashed)[0];

                                $emp_avatar = getAvatarImagePath($rec["avatar"] ?? '', $sex_get);

                                // Determine card status class
                                $status_class = '';
                                if ($emp_status == 1 && $emp_status_fly == 0) {
                                    $status_class = 'status-active';
                                } elseif ($emp_status_fly == 1) {
                                    $status_class = 'status-fly';
                                } else {
                                    $status_class = 'status-inactive';
                                }

                            ?>
                                <?php include("./includes/employee_card.php"); ?>
                            <?php } ?>
                        </div>


                        <div class="row">
                            <div class="col-12">
                                <div class="text-center">
                                    <?php
                                    // Call the new dynamic pagination function
                                    if (isset($count_query)) {
                                        echo generatePagination($conDB, $count_query, $per_page, $page, $url_params);
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        <!-- end row -->

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
        <script src="./plugins/custombox/js/custombox.min.js"></script>
        <script src="./plugins/custombox/js/legacy.min.js"></script>

        <!-- App js -->
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js"></script>

    </body>

    </html>
<?php } ?>