<?php
	// require_once __DIR__ . '/includes/db.php';
	// Initialize the application and load translations
	require_once __DIR__ . '/includes/session_check.php';	
	/*require_once('./includes/auth.php');
	include('./includes/MainClass.php');*/
	$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
	if(mysqli_num_rows($query) == 1){
	
	include("./includes/avatar_select.php");
	include("./includes/Hijri_GregorianConvert.php");
	$DateConv = new Hijri_GregorianConvert;
	$format="DD/MM/YYYY";

	/****************Employee Allow Page*****************/
    // Only regular employees (not team members) should be redirected to profile
    if ($user_role == 'Employee') {
        header("Location: ./profile.php");
        exit();
    }
    /****************Employee Allow Page*****************/

// DEPARTMENT & COMPANY-BASED ACCESS CONTROL FOR DASHBOARD COUNTS
// Determine if user can see all employees or only their department
$can_see_all_employees = (
    $is_system_admin || 
    $user_type == 'administrator' ||
    $user_dept == 5 || // HR Department
    $isHR || 
    $isDeptHr ||
	$user_dept == 1 // Administration Department
);

// Build department filter for queries
$dept_filter = "";
if (!$can_see_all_employees && isset($user_dept)) {
	$dept_filter = " AND `dept`='".$user_dept."' ";
}

// Add company filter if user has company restrictions
// Use 'comp_no' directly since simple queries don't alias the table
$company_filter = getCompanyFilterSQL('comp_no', true);

// Count Active Employees (Status=1, Not on vacation)
$sql_count_active = mysqli_query($conDB, "SELECT COUNT(*) AS `activeusr`, `id` FROM `employees` WHERE `status`=1 AND `fly`=0 ".$dept_filter.$company_filter);
while($rec = mysqli_fetch_assoc($sql_count_active)){$status_cont_active = $rec["activeusr"];}

// Count Terminated Employees
$sql_count_ter = mysqli_query($conDB, "SELECT COUNT(*) AS `ter`, `id` FROM `employees` WHERE `status`=0 ".$dept_filter.$company_filter);
while($rec = mysqli_fetch_assoc($sql_count_ter)){$status_cont_ter = $rec["ter"];}

// Count Employees on Departed Vacation (approved, latest vacation vac_type='Fly', employee fly=1)
$sql_count_fly = mysqli_query($conDB, "SELECT COUNT(*) AS `flying` FROM `employees` e INNER JOIN (SELECT emp_id, MAX(id) latest_id FROM emp_vacation WHERE current_status='approved' GROUP BY emp_id) lv ON lv.emp_id = e.emp_id INNER JOIN emp_vacation ev ON ev.id = lv.latest_id WHERE e.fly=1 AND ev.vac_type='Fly' ".$dept_filter.$company_filter);
while($rec = mysqli_fetch_assoc($sql_count_fly)){$status_cont_fly = $rec["flying"];}

// Count Employees on Local Vacation (approved, latest vacation vac_type IN ('Local Vacation','Encashed'), employee fly=1)
$sql_count_local_vac = mysqli_query($conDB, "SELECT COUNT(*) AS `local_vac` FROM `employees` e INNER JOIN (SELECT emp_id, MAX(id) latest_id FROM emp_vacation WHERE current_status='approved' GROUP BY emp_id) lv ON lv.emp_id = e.emp_id INNER JOIN emp_vacation ev ON ev.id = lv.latest_id WHERE e.fly=1 AND ev.vac_type IN ('Local Vacation','Encashed') ".$dept_filter.$company_filter);
while($rec = mysqli_fetch_assoc($sql_count_local_vac)){$status_cont_local_vac = $rec["local_vac"];}

// Count Total Employees
$sql_count_tot = mysqli_query($conDB, "SELECT COUNT(*) AS `tot`, `id` FROM `employees` WHERE 1=1 ".$dept_filter.$company_filter);
while($rec = mysqli_fetch_assoc($sql_count_tot)){$status_cont_tot = $rec["tot"];}

// Count Man Power Employees
$sql_count_man_power = mysqli_query($conDB, "SELECT COUNT(*) AS `manpwr`, `id` FROM `employees` WHERE `emp_sup_type`='man_power' AND `status`=1 ".$dept_filter.$company_filter);
while($rec = mysqli_fetch_assoc($sql_count_man_power)){$status_cont_man_power = $rec["manpwr"];}

// Percentages for dashboard progress bars
$totalEmployees = max(1, (int)($status_cont_tot ?? 0));
$pct_active      = round((($status_cont_active ?? 0) / $totalEmployees) * 100, 1);
$pct_man_power   = round((($status_cont_man_power ?? 0) / $totalEmployees) * 100, 1);
$pct_on_job      = round(((($status_cont_man_power ?? 0) + ($status_cont_active ?? 0)) / $totalEmployees) * 100, 1);
$pct_fly         = round((($status_cont_fly ?? 0) / $totalEmployees) * 100, 1);
$pct_local_vac   = round((($status_cont_local_vac ?? 0) / $totalEmployees) * 100, 1);
$pct_terminated  = round((($status_cont_ter ?? 0) / $totalEmployees) * 100, 1);
$pct_total       = 100.0;

if(isset($_POST['submit'])){
	if($_POST['iqama_exp']){
		$iqama_exp_gup = $DateConv->HijriToGregorian($_POST['iqama_exp'], $format);
		$iqama_exp_g_up = date("Y/m/d", strtotime($iqama_exp_gup));
		mysqli_query($conDB, "UPDATE `employees` SET `iqama_exp`='".$_POST['iqama_exp']."', `iqama_exp_g`='".$iqama_exp_g_up."' WHERE `id`='".$_POST['iid']."' ") or die();
		$message = "<div class='alert alert-success bg-success text-white border-0'><strong>Successfully!</strong> Your car details will be successfully edit!</div>";
		header( "refresh:1 ; url= ./dashboard.php" );

	} else {
		$message = "<div class=\"alert alert-danger bg-danger text-white border-0\" role=\"alert\">Please select expiry date!</div>";
	}
}


?>
<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>
    <head>
        <meta charset="utf-8" />
        <title><?=$site_title ?> - <?=__('dashboard') ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<!--        <meta content="A fully featured admin theme which can be used to build CRM, CMS, etc." name="description" />-->
        <meta content="Anees Afzal" name="author" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />

        <!-- App favicon -->
        <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">
		
		<!-- DataTables -->
        <link href="./plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
        <link href="./plugins/datatables/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" />
        <!-- Responsive datatable examples -->
        <link href="./plugins/datatables/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />

        <!-- Multi Item Selection examples -->
        <link href="./plugins/datatables/select.bootstrap4.min.css" rel="stylesheet" type="text/css" />

        <link href="./plugins/bootstrap-timepicker/bootstrap-timepicker.min.css" rel="stylesheet">
        <link href="./plugins/bootstrap-colorpicker/css/bootstrap-colorpicker.min.css" rel="stylesheet">
        <link href="./plugins/bootstrap-datepicker/css/bootstrap-datepicker.min.css" rel="stylesheet">
        <link href="./plugins/clockpicker/css/bootstrap-clockpicker.min.css" rel="stylesheet">
        <link href="./plugins/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">

		<link href="./plugins/bootstrap-timepicker/hijri_css/bootstrap-datetimepicker.css" rel="stylesheet">
        <link href="./plugins/bootstrap-timepicker/hijri_css/bootstrap-datetimepicker.min.css" rel="stylesheet">

        <!-- App css -->
        <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <!-- <link href="assets/css/icons.css" rel="stylesheet" type="text/css" /> -->
        <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
		<link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />

        <script src="assets/js/modernizr.min.js"></script>

		<style>
			/* Modern Stats Card Design - Matching dashbydepart.php */
			.stats-card {
				border-radius: 16px;
				box-shadow: 0 2px 12px rgba(0,0,0,0.08);
				padding: 32px 24px 24px 24px;
				margin-bottom: 28px;
				transition: box-shadow 0.2s, transform 0.2s;
				border: none;
				position: relative;
				min-height: 180px;
				display: flex;
				flex-direction: row;
				align-items: center;
				background: var(--card-gradient, linear-gradient(90deg,#2196f3 0%,#21cbf3 100%));
				color: #fff;
				overflow: hidden;
			}
			/* Gradient backgrounds for each color */
			.stats-card[data-color="primary"] {
				--card-gradient: linear-gradient(90deg,#556ee6 0%,#50a5f1 100%);
			}
			.stats-card[data-color="success"] {
				--card-gradient: linear-gradient(90deg,#34c38f 0%,#43e97b 100%);
			}
			.stats-card[data-color="info"] {
				--card-gradient: linear-gradient(90deg,#50a5f1 0%,#2196f3 100%);
			}
			.stats-card[data-color="danger"] {
				--card-gradient: linear-gradient(90deg,#f46a6a 0%,#ff6a88 100%);
			}
			.stats-card[data-color="warning"] {
				--card-gradient: linear-gradient(90deg,#f1b44c 0%,#ffde7d 100%);
			}
			.stats-card[data-color="secondary"] {
				--card-gradient: linear-gradient(90deg,#6c757d 0%,#343a40 100%);
			}
			.stats-card[data-color="dark"] {
				--card-gradient: linear-gradient(90deg,#343a40 0%,#232526 100%);
			}
			/* Dashboard-specific colors */
			.stats-card[data-color="custom"] {
				--card-gradient: linear-gradient(90deg,#0097a7 0%,#26c6da 100%);
			}
			.stats-card[data-color="purple"] {
				--card-gradient: linear-gradient(90deg,#6f42c1 0%,#8e6be8 100%);
			}
			.stats-card:hover {
				box-shadow: 0 8px 32px rgba(0,0,0,0.18);
				transform: translateY(-4px) scale(1.02);
			}
			.stats-card-icon {
				background: rgba(255,255,255,0.18);
				border-radius: 50%;
				width: 72px;
				height: 72px;
				display: flex;
				align-items: center;
				justify-content: center;
				font-size: 40px;
				margin-right: 28px;
				box-shadow: 0 2px 16px rgba(0,0,0,0.12);
				position: relative;
				transition: transform 0.2s;
				flex-direction: column;
			}
			.stats-card-count-circle {
				background: #fff;
				color: #2196f3;
				border-radius: 50%;
				width: 38px;
				height: 38px;
				display: flex;
				align-items: center;
				justify-content: center;
				font-size: 20px;
				font-weight: 700;
				margin-bottom: 6px;
				box-shadow: 0 2px 8px rgba(0,0,0,0.10);
			}
			.stats-card-icon i {
				font-size: 28px;
				color: #2196f3;
			}
			.stats-card-icon[data-color="primary"] i { color: #556ee6; }
			.stats-card-icon[data-color="success"] i { color: #34c38f; }
			.stats-card-icon[data-color="info"] i { color: #50a5f1; }
			.stats-card-icon[data-color="danger"] i { color: #f46a6a; }
			.stats-card-icon[data-color="warning"] i { color: #f1b44c; }
			.stats-card-icon[data-color="secondary"] i { color: #6c757d; }
			.stats-card-icon[data-color="dark"] i { color: #343a40; }
			.stats-card-icon[data-color="custom"] i { color: #0097a7; }
			.stats-card-icon[data-color="purple"] i { color: #6f42c1; }
			.stats-card-icon:hover {
				transform: scale(1.10) rotate(-6deg);
			}
			.stats-card-icon .stats-card-tooltip {
				display: none;
				position: absolute;
				top: -32px;
				left: 50%;
				transform: translateX(-50%);
				background: #222;
				color: #fff;
				padding: 4px 10px;
				border-radius: 6px;
				font-size: 12px;
				white-space: nowrap;
				z-index: 10;
			}
			.stats-card-icon:hover .stats-card-tooltip {
				display: block;
			}
			.stats-card-content {
				flex: 1;
				display: flex;
				flex-direction: column;
				align-items: flex-start;
				position: relative;
			}
			.stats-card-label {
				font-size: 18px;
				font-weight: 700;
				color: #fff;
				margin-bottom: 12px;
				letter-spacing: 0.5px;
			}
			.stats-card-footer {
				display: flex;
				align-items: center;
				gap: 18px;
			}
			.stats-card-percentage {
				font-size: 15px;
				font-weight: 600;
				display: flex;
				align-items: center;
			}
			.stats-card-percentage.positive {
				color: #34c38f;
			}
			.stats-card-percentage.negative {
				color: #f46a6a;
			}
			.stats-card-percentage i {
				font-size: 18px;
				margin-right: 4px;
			}
			@media (max-width: 767px) {
				.stats-card {
					flex-direction: column;
					align-items: flex-start;
					padding: 16px 8px;
					min-height: 160px;
				}
				.stats-card-icon {
					margin-bottom: 10px;
					margin-right: 0;
				}
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
                                <img src="<?=get_setting($conDB, 'white_logo')?>	" alt="" height="28">
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
						<div class="card-box">

						<?php 
							$message;
							if (isset($_SESSION['error_msg'])) {
								echo $_SESSION['error_msg'];
								unset($_SESSION['error_msg']); // Clear after displaying
								header("refresh:3 ; ./dashboard.php");
							}
						?>
						<div class="row text-center">
							<div class="col-sm-4 col-xl-4" onclick="window.location.href='dashbydepart.php'" style="cursor: pointer;">
							<div class="stats-card" data-color="custom">
								<div class="stats-card-icon" data-color="custom">
									<div class="stats-card-count-circle"><?=$status_cont_active ?></div>
									<span class="stats-card-tooltip">Active Employees</span>
									<i class="fa fa-house-chimney-user"></i>
								</div>
								<div class="stats-card-content">
									<div class="stats-card-label" style="color:#fff;opacity:0.95;"><?=__('almutlak_employees') ?></div>
									<div class="stats-card-footer">
										<span class="stats-card-percentage positive">
											<i class="mdi mdi-trending-up"></i>
										</span>
									</div>
									<div style="width:100%;margin-top:18px;">
										<div style="background:rgba(255,255,255,0.25);border-radius:8px;height:12px;overflow:hidden;">
											<div style="height:12px;border-radius:8px;width:<?=$pct_active ?>%;background:rgba(255,255,255,0.9);box-shadow:0 0 8px rgba(255,255,255,0.6);transition:width 0.6s;"></div>
										</div>
										<div style="font-size:13px;color:#fff;opacity:0.85;margin-top:4px;">
											<?=$pct_active ?>% <?=__('of_total_employees') ?>
										</div>
									</div>
								</div>
							</div>
							</div>
							<div class="col-sm-4 col-xl-4" onclick="window.location.href='filter_employee.php?page=1&status=1&fly=no&emp_sup_type=man_power'" style="cursor: pointer;">
							<div class="stats-card" data-color="purple">
								<div class="stats-card-icon" data-color="purple">
									<div class="stats-card-count-circle"><?php if($status_cont_man_power > 0){ echo $status_cont_man_power;}else{echo "0";} ?></div>
									<span class="stats-card-tooltip">Man Power</span>
									<i class="fa fa-users-rays"></i>
								</div>
								<div class="stats-card-content">
									<div class="stats-card-label" style="color:#fff;opacity:0.95;"><?=__('man_power_employees') ?></div>
									<div class="stats-card-footer">
										<span class="stats-card-percentage positive">
											<i class="mdi mdi-trending-up"></i>
										</span>
									</div>
									<div style="width:100%;margin-top:18px;">
										<div style="background:rgba(255,255,255,0.25);border-radius:8px;height:12px;overflow:hidden;">
											<div style="height:12px;border-radius:8px;width:<?=$pct_man_power ?>%;background:rgba(255,255,255,0.9);box-shadow:0 0 8px rgba(255,255,255,0.6);transition:width 0.6s;"></div>
										</div>
										<div style="font-size:13px;color:#fff;opacity:0.85;margin-top:4px;">
											<?=$pct_man_power ?>% <?=__('of_total_employees') ?>
										</div>
									</div>
								</div>
							</div>
							</div>
							<div class="col-sm-4 col-xl-4" <?php if($status_cont_fly > 0){ ?> onclick="window.location.href='dashbydepart.php'" style="cursor: pointer;" <?php } ?> >
							<div class="stats-card" data-color="primary">
								<div class="stats-card-icon" data-color="primary">
									<div class="stats-card-count-circle"><?=$status_cont_man_power + $status_cont_active ?></div>
									<span class="stats-card-tooltip">Total On Job</span>
									<i class="fa fa-plane-departure"></i>
								</div>
								<div class="stats-card-content">
									<div class="stats-card-label" style="color:#fff;opacity:0.95;"><?=__('total_on_job_employees') ?></div>
									<div class="stats-card-footer">
										<span class="stats-card-percentage positive">
											<i class="mdi mdi-trending-up"></i>
										</span>
									</div>
									<div style="width:100%;margin-top:18px;">
										<div style="background:rgba(255,255,255,0.25);border-radius:8px;height:12px;overflow:hidden;">
											<div style="height:12px;border-radius:8px;width:<?=$pct_on_job ?>%;background:rgba(255,255,255,0.9);box-shadow:0 0 8px rgba(255,255,255,0.6);transition:width 0.6s;"></div>
										</div>
										<div style="font-size:13px;color:#fff;opacity:0.85;margin-top:4px;">
											<?=$pct_on_job ?>% <?=__('of_total_employees') ?>
										</div>
									</div>
								</div>
							</div>
							</div>
							<div class="col-sm-3 col-xl-3" <?php if($status_cont_fly > 0){ ?> onclick="window.location.href='filter_employee.php?page=1&departed=1'" style="cursor: pointer;" <?php } ?> >
							<div class="stats-card" data-color="warning">
								<div class="stats-card-icon" data-color="warning">
									<div class="stats-card-count-circle"><?=$status_cont_fly ?></div>
									<span class="stats-card-tooltip">Departed Employees</span>
									<i class="fa fa-plane-departure"></i>
								</div>
								<div class="stats-card-content">
									<div class="stats-card-label" style="color:#fff;opacity:0.95;"><?=__('departed_employees') ?></div>
									<div class="stats-card-footer">
										<span class="stats-card-percentage positive">
											<i class="mdi mdi-trending-up"></i>
										</span>
									</div>
									<div style="width:100%;margin-top:18px;">
										<div style="background:rgba(255,255,255,0.25);border-radius:8px;height:12px;overflow:hidden;">
											<div style="height:12px;border-radius:8px;width:<?=$pct_fly ?>%;background:rgba(255,255,255,0.9);box-shadow:0 0 8px rgba(255,255,255,0.6);transition:width 0.6s;"></div>
										</div>
										<div style="font-size:13px;color:#fff;opacity:0.85;margin-top:4px;">
											<?=$pct_fly ?>% <?=__('of_total_employees') ?>
										</div>
									</div>
								</div>
							</div>
							</div>
							<div class="col-sm-3 col-xl-3" <?php if(($status_cont_local_vac ?? 0) > 0){ ?> onclick="window.location.href='filter_employee.php?page=1&local_vac=1'" style="cursor: pointer;" <?php } ?> >
							<div class="stats-card" data-color="info">
								<div class="stats-card-icon" data-color="info">
									<div class="stats-card-count-circle"><?=$status_cont_local_vac ?? 0 ?></div>
									<span class="stats-card-tooltip">Local Vacation</span>
									<i class="fa fa-umbrella-beach"></i>
								</div>
								<div class="stats-card-content">
									<div class="stats-card-label" style="color:#fff;opacity:0.95;"><?=__('local_vacation_employees') ?></div>
									<div class="stats-card-footer">
										<span class="stats-card-percentage positive">
											<i class="mdi mdi-trending-up"></i>
										</span>
									</div>
									<div style="width:100%;margin-top:18px;">
										<div style="background:rgba(255,255,255,0.25);border-radius:8px;height:12px;overflow:hidden;">
											<div style="height:12px;border-radius:8px;width:<?=$pct_local_vac ?>%;background:rgba(255,255,255,0.9);box-shadow:0 0 8px rgba(255,255,255,0.6);transition:width 0.6s;"></div>
										</div>
										<div style="font-size:13px;color:#fff;opacity:0.85;margin-top:4px;">
											<?=$pct_local_vac ?>% <?=__('of_total_employees') ?>
										</div>
									</div>
								</div>
							</div>
							</div>
							<div class="col-sm-3 col-xl-3" onclick="window.location.href='filter_employee.php?page=1&status=0&fly=0'" style="cursor: pointer;">
							<div class="stats-card" data-color="danger">
								<div class="stats-card-icon" data-color="danger">
									<div class="stats-card-count-circle"><?=$status_cont_ter ?></div>
									<span class="stats-card-tooltip">Terminated Employees</span>
									<i class="fa fa-users-slash"></i>
								</div>
								<div class="stats-card-content">
									<div class="stats-card-label" style="color:#fff;opacity:0.95;"><?=__('terminated_employees') ?></div>
									<div class="stats-card-footer">
										<span class="stats-card-percentage positive">
											<i class="mdi mdi-trending-up"></i>
										</span>
									</div>
									<div style="width:100%;margin-top:18px;">
										<div style="background:rgba(255,255,255,0.25);border-radius:8px;height:12px;overflow:hidden;">
											<div style="height:12px;border-radius:8px;width:<?=$pct_terminated ?>%;background:rgba(255,255,255,0.9);box-shadow:0 0 8px rgba(255,255,255,0.6);transition:width 0.6s;"></div>
										</div>
										<div style="font-size:13px;color:#fff;opacity:0.85;margin-top:4px;">
											<?=$pct_terminated ?>% <?=__('of_total_employees') ?>
										</div>
									</div>
								</div>
							</div>
							</div>
							<div class="col-sm-3 col-xl-3" onclick="window.location.href='reg_employee.php'" style="cursor: pointer;">
							<div class="stats-card" data-color="success">
								<div class="stats-card-icon" data-color="success">
									<div class="stats-card-count-circle"><?=$status_cont_tot ?></div>
									<span class="stats-card-tooltip">Total Employees</span>
									<i class="fa fa-users-viewfinder"></i>
								</div>
								<div class="stats-card-content">
									<div class="stats-card-label" style="color:#fff;opacity:0.95;"><?=__('total_employees') ?></div>
									<div class="stats-card-footer">
										<span class="stats-card-percentage positive">
											<i class="mdi mdi-trending-up"></i>
										</span>
									</div>
									<div style="width:100%;margin-top:18px;">
										<div style="background:rgba(255,255,255,0.25);border-radius:8px;height:12px;overflow:hidden;">
											<div style="height:12px;border-radius:8px;width:<?=$pct_total ?>%;background:rgba(255,255,255,0.9);box-shadow:0 0 8px rgba(255,255,255,0.6);transition:width 0.6s;"></div>
										</div>
										<div style="font-size:13px;color:#fff;opacity:0.85;margin-top:4px;">
											<?=$pct_total ?>% <?=__('of_total_employees') ?>
										</div>
									</div>
								</div>
							</div>
							</div>
						</div>                        
                        </div>
                        <?php /* if($user_type == $access1 OR $user_type == $access2){ ?>
                        <div class="card-box">
                        	<h4 class="m-t-0 header-title">Search Birthday by Month</h4>
                        	<form action="find_birthday.php" method="get">
							<?=$msg ?>
							<div class="form-row">
								<div class="form-group col-md-6">
									<label for="datepickerdob" class="col-form-label">Section Name<span class="text-danger">*</span></label>
									<input type="text" name="find_birthday" parsley-trigger="change" required placeholder="Select any date of month" class="form-control" id="datepickerdob">
								</div>
							</div>							
							<button type="submit" name="submit" class="btn btn-primary"><i class="mdi mdi-update"></i> Search Birthday</button>
							</form>ى
                        </div>
                        <?php } */?>
							<?php

							// Show expiry notifications for: Administrators, HR team, Finance team, IT team, and department managers
							if ($is_system_admin || $isHR || $isDeptHr ):
								// Use the same access control pattern for expiry notifications
								if(!$can_see_all_employees && isset($user_dept)){
									$result=mysqli_query($conDB, "SELECT 
									`e`.*, 
									`d`.`dep_nme`,
									`d`.`dep_nme_ar`,
									`c`.`name` AS `countryname_en`,
									`c`.`name_ar` AS `countryname_ar`
									FROM `employees` as `e`
									LEFT JOIN `department` `d` ON `d`.`id` = `e`.`dept`
									LEFT JOIN `countries` `c` ON `c`.`id` = `e`.`country`
									WHERE `e`.`status`=1 AND `e`.`iqama_exp_g` BETWEEN CURDATE() and DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND `e`.`dept`='".$user_dept."' ");
								}else{
									$result=mysqli_query($conDB, "SELECT 
									`e`.*, 
									`d`.`dep_nme`,
									`d`.`dep_nme_ar`,
									`c`.`name` AS `countryname_en`,
									`c`.`name_ar` AS `countryname_ar`
									FROM `employees` as `e`
									LEFT JOIN `department` `d` ON `d`.`id` = `e`.`dept`
									LEFT JOIN `countries` `c` ON `c`.`id` = `e`.`country`
									WHERE `e`.`status`=1 AND `e`.`iqama_exp_g` BETWEEN CURDATE() and DATE_ADD(CURDATE(), INTERVAL 30 DAY) ");
								}

								if( mysqli_num_rows($result) != 0 ){
							?>
						<div class="card-box">
							<h4 class="m-t-0 header-title"><?=__('coming_soon_expiry_with_30_days') ?></h4>
							<table id="datatable" class="table table-striped dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
								<thead class="thead-dark">
								<tr>
									<th><?=__('employee_id') ?></th>
									<th><?=__('name') ?></th>
									<th><?=__('iqama_id') ?></th>
									<th><?=__('department') ?></th>
									<th><?=__('country') ?></th>
									<th><?=__('iqama_id_expiry') ?></th>
									<th><?=__('days_of_expiry') ?></th>
									<th width="200"><?=__('action') ?></th>
								</tr>
								</thead>

								<tbody>
								<?php
									while($row = mysqli_fetch_assoc($result)){	
										if(strtotime(date("Y/m/d")) < strtotime($row['iqama_exp_g'])){

											$from = strtotime(date("d-m-Y", strtotime($row['iqama_exp_g'])));
											$today = time();
											$difference = $from - $today;
											$daystoexp = floor($difference / 86400);  // (60 * 60 * 24)
											
											if($daystoexp <= "10"){
												$color = "table-danger";
											}elseif($daystoexp <= "20"){
												$color = "table-warning";
											}elseif($daystoexp <= "30"){
												$color = "table-info";
											}
											
//													 echo "<div style='background-color: {$color};'>Expires in {$when} day(s)</div>";
								?>
									
								<tr class="<?=$color; ?>">
									<td><?=$row['emp_id']; ?></td>
									<td><?=getDisplayName($row['name']); ?></td>
									<td><span class='copyToClipboard'><?=$row['iqama']?></span> <i class='fa fa-clipboard'></i></td>
									<td><?=($is_rtl ?? false ? $row['dep_nme_ar']:$row['dep_nme'])?></td>
									<td><?=($is_rtl ?? false? $row['countryname_ar'] : $row['countryname_en']);?></td>
									<td><?=$row['iqama_exp']; ?></td>
									<td align="center"><?=$daystoexp; ?> <?=__('days')?></td>
									<td>
										<div class="btn-group" role="group" aria-label="Edit Button">
											<a href="javascript:void(0);" class="btn btn-primary m-t-20 btn-rounded waves-effect w-md waves-light btn-sm iqama_exp_hijri" data-id="<?=$row['id']?>" >
												<i class="mdi mdi-account-card-details"></i> <?=__('update_expiry')?>
											</a>
										</div>
									</td>
								</tr>
								<?php } } ?>
								</tbody>
							</table>
						</div>
						<?php } ?>
						<?php
							// Query for expired Iqamas - use same access control
							if(!$can_see_all_employees && isset($user_dept)){
									// Query for department-restricted users (commented out in original)
									// Keep it commented as the else block is used for all cases
							}
							// Always use the full query, filtered by department if needed
							$expired_filter = $can_see_all_employees ? "" : " AND `e`.`dept`='".$user_dept."'";
							$result=mysqli_query($conDB, "SELECT 
								`e`.*, 
								`d`.`dep_nme`,
								`d`.`dep_nme_ar`,
								`c`.`name` AS `countryname_en`,
								`c`.`name_ar` AS `countryname_ar`
								FROM `employees` as `e`
								LEFT JOIN `department` `d` ON `d`.`id` = `e`.`dept`
								LEFT JOIN `countries` `c` ON `c`.`id` = `e`.`country`
								WHERE `e`.`status`=1 AND DATEDIFF(`e`.`iqama_exp_g`, NOW()) <= 1".$expired_filter."
							");
							
							if( mysqli_num_rows($result) != 0 ){

						?>
						<div class="card-box">
							<h4 class="m-t-0 header-title"><?=__('expired') ?></h4>

							<table id="datatable_exp" class="table table-striped dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
								<thead class="thead-dark">
								<tr>
									<th><?=__('employee_id') ?></th>
									<th><?=__('name') ?></th>
									<th><?=__('iqama_id') ?></th>
									<th><?=__('department') ?></th>
									<th><?=__('country') ?></th>
									<th><?=__('iqama_id_expiry') ?></th>
									<th><?=__('days_of_expiry') ?></th>
									<th width="200"><?=__('action') ?></th>
								</tr>
								</thead>

								<tbody>
								<?php
									while($row = mysqli_fetch_assoc($result)){
										if(strtotime(date("Y/m/d")) > strtotime($row['iqama_exp_g'])){
											$from = strtotime(date("d-m-Y", strtotime($row['iqama_exp_g'])));
											$today = time();
											$difference = $from - $today;
											$daystoexp = floor($difference / 86400);  // (60 * 60 * 24)
									?>
								<tr>
									<td><?=$row['emp_id']; ?></td>
									<td><?=getDisplayName($row['name']); ?></td>
									<td><span class='copyToClipboard'><?=$row['iqama']?></span> <i class='fa fa-clipboard'></i></td>
									<td><?=($is_rtl ?? false ? $row['dep_nme_ar']:$row['dep_nme'])?></td>
									<td><?=($is_rtl ?? false? $row['countryname_ar'] : $row['countryname_en']);?></td>
									<td><?=date("d/m/Y", strtotime($row['iqama_exp_g'])); ?></td>
									<td align="center"><?=$daystoexp; ?> <?=__('days') ?></td>
									<td>
										<div class="btn-group" role="group" aria-label="Edit Button">
											<a href="javascript:void(0);" class="btn btn-danger m-t-20 btn-rounded waves-effect w-md waves-light btn-sm iqama_exp_hijri" data-id="<?=$row['id']?>" >
												<i class="fa fa-images-user"></i> <?=__('update_expiry') ?>
											</a>
										</div>
									</td>
								</tr>
								<?php } } ?>
								</tbody>
							</table>
						</div>
						<?php } ?>

					<?php endif; ?>
							
                    </div> <!-- container -->

                </div> <!-- content -->

                <footer class="footer">
                    <?=$site_footer ?>
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

		<script src="./plugins/moment/moment.js"></script>
        <script src="./plugins/bootstrap-timepicker/bootstrap-timepicker.js"></script>

        <script src="./plugins/bootstrap-timepicker/hijri/bootstrap-hijri-datetimepicker.js"></script>
        <script src="./plugins/bootstrap-timepicker/hijri/bootstrap-hijri-datetimepicker.min.js"></script>
        <script src="./plugins/bootstrap-timepicker/hijri/bootstrap-hijri-datetimepickermin.js"></script>

        <script src="./plugins/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js"></script>
        <script src="./plugins/clockpicker/js/bootstrap-clockpicker.min.js"></script>
        <script src="./plugins/bootstrap-daterangepicker/daterangepicker.js"></script>
        <script src="./plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>
		
        <script src="./plugins/bootstrap-filestyle/js/bootstrap-filestyle.min.js" type="text/javascript"></script>

        <!-- App js -->
		<script src="assets/pages/jquery.form-pickers.init.js"></script>
		
        <!-- App js -->
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js"></script>

		<!-- Make sure this is on EVERY page -->
    	<script src="assets/js/notifications.js"></script>

        <!-- Dashboard Init -->
        <!-- <script src="assets/pages/jquery.dashboard.init.js"></script> -->
		
		<script type="text/javascript">

			$('.updateExpID').click(function() {
	            $('#iid')      .val($(this).data('iid'));
	        });

            $(document).ready(function() {

                // Default Datatable
                $('#datatable').DataTable({
						lengthChange: false,
						order: [[ 5, "asc" ]],
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
					}
				);
                $('#datatable_exp').DataTable({
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

                //Buttons examples
                var table = $('#datatable-buttons').DataTable({
                    lengthChange: false,
                    buttons: ['copy', 'excel', 'pdf']
                });

                // Key Tables
                $('#key-table').DataTable({
                    keys: true
                });

                // Responsive Datatable
                $('#responsive-datatable').DataTable();

                // Multi Selection Datatable
                $('#selection-datatable').DataTable({
                    select: {
                        style: 'multi'
                    }
                });
                table.buttons().container()
                        .appendTo('#datatable-buttons_wrapper .col-md-6:eq(0)');
            } );

			$(document).on('click', '.iqama_exp_hijri', function (e) {
				e.preventDefault();
				var emid = $(this).data('id');
				Swal.fire({
					title: __('update_id_expiry_info'),
					html: id_exp_HTML(),
					showCancelButton: true,
					confirmButtonColor: '#3085d6',
					cancelButtonColor: '#d33',
					cancelButtonText: __('cancel'),
					confirmButtonText: __('yes_update'),
					showLoaderOnConfirm: true,
					allowOutsideClick: false,
					willOpen: function() {
						$('input[name="emid"]').val(emid);
						$("#iq_id_exp_hijri").hijriDatePicker({
							locale: "<?= ($is_rtl ?? false) ? 'ar-sa' : 'en-us' ?>",
							hijri:true,
							showSwitcher:false,
							hijriFormat:"iYYYY-iMM-iDD",
							hijriDayViewHeaderFormat: "iMMMM iYYYY",
							// showTodayButton: true,
							inline: true,
							ignoreReadonly: true,
						});
						jQuery('#iq_id_exp_greg').hijriDatePicker({
							locale: "<?= ($is_rtl ?? false) ? 'ar-sa' : 'en-us' ?>",
							hijri: false,
							format: "YYYY-MM-DD",
							dayViewHeaderFormat: "MMMM YYYY",
							// showTodayButton: true,
							inline: true,
							ignoreReadonly: true,
							showSwitcher: false
						});
						$("input[name$='note']").click(function(){
							var value = $(this).val();
							if(value=='hijri') {
								$("#hijriDiv").show();
								$("#gregorianDiv").hide();
								$("#iq_id_exp_hijri").attr('name', 'iqama_exp');
								$("#iq_id_exp_greg").removeAttr('name');
							} else if(value=='gregorian') {
								$("#hijriDiv").hide();
								$("#gregorianDiv").show();
								$("#iq_id_exp_hijri").removeAttr('name');
								$("#iq_id_exp_greg").attr('name', 'iqama_exp_g');
							}
						});
					},
					preConfirm: function() {
						var iqama_exp = $('input[name="iqama_exp"]').val();
						var iqama_exp_g = $('input[name="iqama_exp_g"]').val();
						var inputCheck = $("input[name$='note']:checked").is(':checked');
						if(inputCheck == false){
							Swal.showValidationMessage(__("select_id_iqama_expiry_validation"))
						}
						return new Promise(function(reject, resolve) {
							if( inputCheck == false ){
								reject(__("fill_mandatory_fields"));
								return false;
							}
							$.ajax({
								url: './includes/ajaxFile/ajaxEmployee.php',
								type: 'POST',
								dataType: "JSON",
								data: {'id': emid, 'iqama_exp':iqama_exp, 'iqama_exp_g':iqama_exp_g, ajaxType:'id_iqama_update' },
							})
							.done(function(response){
								// console.log(response.title)
								Swal.fire({
									title:response.title,text:response.message,icon:response.type,allowOutsideClick:false, confirmButtonText: __("ok")
								}).then(function(isConfirm){(isConfirm)?location.reload():""});
							})
							.fail(function(jqXHR, textStatus, errorThrown) {
							
								reject(handleAjaxFailure(jqXHR, textStatus).message);
							});
						});
					},
				})
			});

        </script>

    </body>
</html>
<?php } ?>