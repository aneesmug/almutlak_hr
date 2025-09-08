<?php
/*******************************************************************************************************************
 * MODIFICATION SUMMARY (006-employee_profile.php):
 * 1. MODERNIZED LAYOUT: The report has been redesigned with a clean, single-column, card-based layout to improve
 * visual organization and readability. Each major section is now in its own distinct card.
 * 2. PROFILE HEADER: A new profile header section has been added at the top, featuring the employee's photo, name,
 * and job title, followed by a two-column summary of key personal and employment details.
 * 3. ENHANCED FONT SIZE: The base font size has been increased again for better legibility on-screen and in print.
 * 4. LOGICAL GROUPING: Information is grouped into logical cards: "Personal & Employment", "Financial Details",
 * "Assigned Assets", "Loan History", "Vacation History", etc., making the report easier to navigate.
 * 5. PRINT OPTIMIZATION: The single-column layout is more robust for printing and will flow more naturally if it
 * needs to span more than one page.
 *******************************************************************************************************************/
	require_once __DIR__ . '/includes/db.php';
	require_once __DIR__ . '/includes/session_check.php';
	$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
	if(mysqli_num_rows($query) == 1){
	include("./includes/avatar_select.php");
	
	include("./includes/Hijri_GregorianConvert.php");
	$DateConv=new Hijri_GregorianConvert;
	$format="YYYY/MM/DD";
	
	require("./includes/emp_query.php");

	if(mysqli_num_rows($get_emp_data) !== 0){
		$allRecords = mysqli_fetch_all($get_emp_data, MYSQLI_ASSOC);
		foreach ($allRecords as $rec) {
			$emprow = $rec;
		}
		$salary_get = str_replace(',', '', ($emprow['basic'] + $emprow['housing'] + $emprow['transport'] + $emprow["food"] + $emprow["misc"] + $emprow["cashier"] + $emprow["fuel"] + $emprow["tel"] + $emprow["other"] + $emprow["guard"]));
		$hours_in_day   = 24;
		$minutes_in_hour= 60;
		$seconds_in_mins= 60;
		$birth_date     = new DateTime($emprow["dob"]);
		$current_date   = new DateTime();
		$diff           = $birth_date->diff($current_date);
		$years	   		= $diff->y . " " . __('years');
		$vacyear_get = preg_replace("/[^0-9]/","",$emprow["period"]);
	}	
	
    // New queries for the full report
    // Query for Assigned Car
    $car_info = null;
    if (!empty($emprow["car_id"])) {
        $car_info = car_get_info($emprow["car_id"]);
    }

    // Query for Assigned Assets
    $assets_query = mysqli_query($conDB, "SELECT ea.*, a.name as asset_name 
                                        FROM `employee_assets` ea 
                                        JOIN `assets` a ON ea.asset_id = a.id 
                                        WHERE ea.emp_id = '{$emprow['empid']}' AND ea.status = 'Assigned'
                                        ORDER BY ea.assigned_date DESC");
    $assigned_assets = mysqli_fetch_all($assets_query, MYSQLI_ASSOC);

    // Query for Loan History
    $loans_query = mysqli_query($conDB, "SELECT * FROM `emp_loan` WHERE `emp_id` = '{$emprow['empid']}' ORDER BY `id` DESC");
    $loan_history = mysqli_fetch_all($loans_query, MYSQLI_ASSOC);

    // Query for Vacation History
    $vacations_query = mysqli_query($conDB, "SELECT * FROM `emp_vacation` WHERE `emp_id`='" . $emprow['empid'] . "' ORDER BY `id` DESC");
    $vacation_history = mysqli_fetch_all($vacations_query, MYSQLI_ASSOC);

    // Query for Documents
    $documents_query = mysqli_query($conDB, "SELECT * FROM `emp_docu` WHERE `emp_id`='" . $emprow['empid'] . "' ORDER BY `id` DESC ");
    $employee_documents = mysqli_fetch_all($documents_query, MYSQLI_ASSOC);

    // Query for Notes
    $notes_query = mysqli_query($conDB, "SELECT n.* FROM `emp_notice` n WHERE n.emp_id = '{$emprow['empid']}' AND n.is_deleted = 0 ORDER BY n.id DESC");
    $employee_notes = mysqli_fetch_all($notes_query, MYSQLI_ASSOC);

	} else {
		//when the id not equals id show database
		header("Location: ./reg_employee.php");
	}

?>
<!doctype html> 
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

    <head>
        <meta charset="utf-8" />
        <title><?=$site_title ?> - <?=__('all_employees')?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
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
        <script src="assets/js/modernizr.min.js"></script>
		<style>
            body {
                font-size: 16px;
            }
            .table-sm td, .table-sm th {
                padding: .5rem;
            }
            h4 {
                font-size: 1.25rem;
            }
            h5 {
                font-size: 1.1rem;
            }
             .card-box {
                page-break-inside: avoid;
            }
            @media print {
                body {
                    font-size: 12px;
                }
                .content-page, .content {
                    padding: 0 !important;
                    margin: 0 !important;
                }
                .card-box {
                    box-shadow: none !important;
                    border: 1px solid #dee2e6 !important;
                }
                .table {
                    margin-bottom: 0.5rem;
                }
                .thead-dark th {
                    background-color: #343a40 !important;
                    color: #fff !important;
                    -webkit-print-color-adjust: exact;
                }
                .badge{
                    border: 1px solid #000;
                }
                .no-print {
                    display: none;
                }
            }
        </style>
		<?php if ($is_rtl): ?>
            <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
        <?php endif; ?>
		<script> window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;</script>
    </head>
    <body class="enlarged" data-keep-enlarged="true" onLoad="javascript:window.print()">

        <!-- Begin page -->
        <div id="wrapper">

            <!-- ========== Left Sidebar Start ========== -->
            <div class="left side-menu no-print" style="display:none;">

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
                    <!--- Sidemenu -->
                    <?php include("./includes/main_menu.php"); ?>
                    <!-- Sidebar -->
                    <div class="clearfix"></div>
                </div>
                <!-- Sidebar -left -->

            </div>
            <!-- Left Sidebar End -->
            <div class="content-page">

                <!-- Top Bar Start -->
                <?php include("./includes/topbar.php"); ?>
                <!-- Top Bar End -->

                <!-- Start Page content -->
                <div class="content">
                    <div class="container-fluid">

                        <div class="row">
                            <div class="col-md-12">
                                <div class="card-box" id="nodeToRenderAsPDF">
                                    <div class="clearfix mb-3">
                                        <div class="float-left">
                                            <img src="assets/images/logo.png" alt="" height="120">
                                        </div>
										<div class="float-right items-right text-right justify-content-center align-items-center">
                                            <h3 class="m-0"><?=__('personal_employment_details_header')?></h3>
                                            <p><?=__('date_label')?>: <?= date('d M, Y') ?></p>
											<p><strong><?=__('status_label')?>:</strong> <span class="badge badge-<?=($emprow['status'] == 1)?'success':'danger';?>"><?=($emprow['status'] == 1)?__('active_status'):__('terminated_status');?></span></p>
                                        </div>
                                    </div>
                                    <hr/>
                                    <!-- Profile Header -->
                                    <div class="card-box bg-light">
                                        <div class="row">
                                            <div class="col-md-3 text-center">
                                                <img src="<?=$emprow['avatar'] ?>" class="img-thumbnail rounded-circle" alt="employee-image" style="width:150px; height:150px;">
                                                <h4 class="mt-2 mb-0"><?=$emprow['name']?></h4>
                                                <p class="text-muted"><?=($is_rtl ?? false ? $emprow['jobname_ar']:$emprow['jobname'])?> - <?=($is_rtl ?? false ? $emprow['deptnme_ar']:$emprow['deptnme'])?></p>
                                            </div>
                                            <div class="col-md-9">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <h5><?=__('personal_information_header')?></h5>
                                                        <p><strong><?=__('employee_id_label')?>:</strong> <?=$emprow['empid']; ?></p>
                                                        <p><strong><?=__('iqama_id_label')?>:</strong> <?=$emprow['iqama']; ?> (<?=__('expires_label')?>: <?=$emprow['iqama_exp']; ?>)</p>
                                                        <p><strong><?=__('passport_label')?>:</strong> <?=$emprow['passport_number']; ?> (<?=__('expires_label')?>: <?php if (!empty($emprow['passport_exp'])) echo $emprow['passport_exp']; ?>)</p>
                                                        <p><strong><?=__('dob_label')?>:</strong> <?=$emprow['dob']; ?> (<?=$years?>)</p>
                                                        <p><strong><?=__('nationality_label')?>:</strong> <?=($is_rtl ?? false ? $emprow['country_name_ar']:$emprow['country_name']); ?></p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h5><?=__('employment_information_header')?></h5>
                                                        <p><strong><?=__('department_label')?>:</strong> <?=$emprow['deptnme']; ?> (<?=$emprow['sectin_nme']; ?>)</p>
                                                        <p><strong><?=__('date_hired_label')?>:</strong> <?=$emprow['joining_date']; ?></p>
                                                        <p><strong><?=__('working_period')?>:</strong> <?=ageDOB($emprow['joining_date']) ?></p>
                                                        <p><strong><?=__('contract_period_label')?>:</strong> <?=formatPeriod($emprow["period"])?></p>
                                                        <p><strong><?=__('contact_label')?>:</strong> <?=$emprow['mobile']; ?> | <?=$emprow['c_email']; ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Financial Details -->
                                    <div class="card-box mt-3">
                                        <h4 class="header-title mt-0 mb-3 text-center"><?=__('financial_details_header')?></h4>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h5><?=__('salary_breakdown_header')?></h5>
                                                <table class="table table-sm">
                                                    <tbody>
                                                    <?php
                                                    $salaryItems = ['basic','housing','transport','food','misc','cashier','fuel','tel','other','guard'];
                                                    foreach ($salaryItems as $item) {
                                                        if (!empty($emprow[$item]) && $emprow[$item] != "0") {
                                                            echo "<tr><th style='width:150px;'>" . __(ucfirst($item).'_label') . ":</th><td>" . number_format($emprow[$item], 2) . " " . __('sar_currency') . "</td></tr>";
                                                        }
                                                    }
                                                    ?>
                                                    <tr class="bg-light"><th class="font-weight-bold"><?=__('total_salary_label')?>:</th><td class="font-weight-bold"><?=number_format($salary_get, 2); ?> <?=__('sar_currency')?></td></tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="col-md-6">
                                                <h5 ><?=__('bank_insurance_header')?></h5>
                                                <table class="table table-sm">
                                                    <tbody>
                                                        <tr><th style='width:150px;'><?=__('bank_name_label')?>:</th><td><?=($is_rtl ?? false ? $emprow['b_name_ar'] : $emprow['b_name'])?></td></tr>
                                                        <tr><th><?=__('iban_label')?>:</th><td><?=$emprow['iban']; ?></td></tr>
                                                        <tr><th><?=__('gosi_no_label')?>:</th><td><?=$emprow['gosi_no']; ?></td></tr>
                                                        <tr><th><?=__('gosi_payment_label')?>:</th><td><?=$emprow['amount']; ?></td></tr>
                                                        <tr><th><?=__('insurance_no_label')?>:</th><td><?=$emprow['insurance_no'] ?></td></tr>
                                                        <tr><th><?=__('insurance_class_label')?>:</th><td><?=$emprow['insurance_class'] ?></td></tr>
                                                        <tr><th><?=__('insurance_expiry_label')?>:</th><td><?=$emprow['insurance_exp'] ?></td></tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Assets -->
                                    <?php if ($car_info || !empty($assigned_assets)): ?>
                                    <div class="card-box mt-3">
                                        <h4 class="header-title mt-0 mb-3 text-center"><?=__('assigned_assets_header')?></h4>
                                        <div class="row">
                                            <?php if ($car_info): ?>
                                            <div class="col-md-6">
                                                <h5><?=__('assigned_car_header')?></h5>
                                                <table class="table table-sm">
                                                    <tbody>
                                                        <tr><th style="width:150px;"><?=__('maker_model_label')?>:</th><td><?= $car_info['maker_name'] ?> - <?= $car_info['model'] ?> (<?= $car_info['made_year'] ?>)</td></tr>
                                                        <tr><th><?=__('plate_no_label')?>:</th><td><?= $car_info['plate_no'] ?></td></tr>
                                                        <tr><th><?=__('receive_date_label')?>:</th><td><?= date('d, M Y', strtotime($emprow['rcv_date'])) ?></td></tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <?php endif; ?>
                                            <?php if (!empty($assigned_assets)): ?>
                                            <div class="col-md-6">
                                                <h5><?=__('other_assets_header')?></h5>
                                                <table class="table table-sm">
                                                    <?php foreach ($assigned_assets as $asset): ?>
                                                    <tr>
                                                        <td style="width:150px;"><?= htmlspecialchars($asset['asset_name']); ?></td>
                                                        <td><?= htmlspecialchars($asset['serial_number']); ?></td>
                                                        <td><?= date('d M Y', strtotime($asset['assigned_date'])); ?></td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </table>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <!-- Loan History -->
                                    <?php if (!empty($loan_history)): ?>
                                    <div class="card-box mt-3">
                                        <h4 class="header-title mt-0 mb-3 text-center"><?=__('loan_history_header')?></h4>
                                        <table class="table table-sm table-bordered">
                                            <thead class="thead-light">
                                                <tr><th><?=__('amount_header')?></th><th><?=__('deduction_header')?></th><th><?=__('balance_header')?></th><th><?=__('start_header')?></th><th><?=__('end_header')?></th><th><?=__('type_header')?></th><th><?=__('status_header')?></th></tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($loan_history as $loan): 
                                                    $loan_id_hist = $loan['id'];
                                                    $total_payable_hist = $loan['total_payable'];
                                                    $sql_total_paid_hist = "SELECT COALESCE(SUM(amount), 0) as total_paid FROM `emp_loan_payments` WHERE `loan_id` = '$loan_id_hist'";
                                                    $query_total_paid_hist = mysqli_query($conDB, $sql_total_paid_hist);
                                                    $paid_rec_hist = mysqli_fetch_assoc($query_total_paid_hist);
                                                    $total_paid_hist = $paid_rec_hist['total_paid'];
                                                    $remaining_balance_hist = $total_payable_hist - $total_paid_hist;
                                                ?>
                                                <tr>
                                                    <td><?= number_format($loan['loan_amount'], 2); ?></td>
                                                    <td><?= number_format($loan['monthly_deduction'], 2); ?></td>
                                                    <td class="font-weight-bold <?= ($remaining_balance_hist > 0) ? 'text-danger' : 'text-success' ?>"><?= number_format($remaining_balance_hist, 2); ?></td>
                                                    <td><?= date('d M Y', strtotime($loan['start_date'])); ?></td>
                                                    <td><?= date('d M Y', strtotime($loan['end_date'])); ?></td>
                                                    <td><span class="badge badge-<?= ($loan['loan_type'] == 'emergency' ? 'warning' : 'info') ?>"><?= __($loan['loan_type']); ?></span></td>
                                                    <td><span class="badge badge-<?= ($loan['status'] == 'approved' ? 'success' : ($loan['status'] == 'paid' ? 'primary' : ($loan['status'] == 'rejected' ? 'danger' : 'warning'))) ?>"><?= __($loan['status']); ?></span></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php endif; ?>

                                    <!-- Vacation History -->
                                    <?php if (!empty($vacation_history)): ?>
                                    <div class="card-box mt-3">
                                        <h4 class="header-title mt-0 mb-3"><?=__('vacation_history_header')?></h4>
                                        <table class="table table-sm table-bordered">
                                            <thead class="thead-light">
                                                <tr><th><?=__('type_header')?></th><th><?=__('start_date_header')?></th><th><?=__('return_date_header')?></th><th><?=__('days_header')?></th><th><?=__('permit_no_header')?></th><th><?=__('status_header')?></th><th><?=__('arrived_header')?></th></tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($vacation_history as $vac): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($vac['note']); ?></td>
                                                    <td><?= date('d M Y', strtotime($vac['start_date'])); ?></td>
                                                    <td><?= date('d M Y', strtotime($vac['return_date'])); ?></td>
                                                    <td><?= htmlspecialchars($vac['vacdays']); ?></td>
                                                    <td><?= htmlspecialchars($vac['permit_no']); ?></td>
                                                    <td><?= ($vac["review"] == 'A') ? __('approved') : (($vac["review"] == 'C') ? __('completed') : __('pending')); ?></td>
                                                    <td><?= ($vac["arrived_date"] == "") ? __('not_yet_text') : date('d M Y', strtotime($vac['arrived_date'])); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- Notes -->
                                     <?php if (!empty($employee_notes)): ?>
                                    <div class="card-box mt-3">
                                        <h4 class="header-title mt-0 mb-3"><?=__('notes_notices_header')?></h4>
                                        <table class="table table-sm table-bordered">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th style="width:150px;"><?=__('date_header')?></th>
                                                    <th><?=__('note_header')?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($employee_notes as $note): ?>
                                                <tr>
                                                    <td><?= date('d, M Y', strtotime($note['created_at'])); ?></td>
                                                    <td><?= htmlspecialchars($note['note']); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php endif; ?>

                                    <!-- Documents -->
                                    <?php if (!empty($employee_documents)): ?>
                                    <div class="card-box mt-3">
                                        <h4 class="header-title mt-0 mb-3"><?=__('employee_documents_header')?></h4>
                                        <div class="row">
                                            <?php foreach ($employee_documents as $doc):
                                                $fileIcon = ($doc["docu_ext"] == "pdf" ? "pdf" : ($doc["docu_ext"] == "xls" ? "excel" : ($doc["docu_ext"] == "tif" ? "tif" : "")));
                                            ?>
                                            <div class="col-lg-3 col-xl-2">
                                                <div class="file-man-box text-center">
                                                    <div class="file-img-box">
                                                        <?php if ($doc["docu_ext"] == "pdf" or $doc["docu_ext"] == "xls" or $doc["docu_ext"] == "tif"): ?>
                                                            <img src="assets/images/file_icons/<?= $fileIcon ?>.svg" alt="icon" style="width: 64px; height: 64px;">
                                                        <?php else: ?>
                                                            <img src="./assets/emp_documents/<?= $doc['path'] ?>" alt="document" class="img-thumbnail" style="max-height: 80px;">
                                                        <?php endif ?>
                                                    </div>
                                                    <div class="file-man-title">
                                                        <h6 class="mb-0 text-overflow" style="font-size:10px"><?= $doc['docu_typ'] ?></h6>
                                                        <p class="mb-0"><small><?= date('d-M-y', strtotime($doc['created_at'])) ?></small></p>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                </div>
                            </div>
                        </div>
                        <!-- end row -->

                    </div> <!-- container -->
                </div> <!-- content -->

                <footer class="footer no-print">
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

        <!-- App js -->
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js"></script>

		<script type="text/javascript">
            //	window.print();
            // 	setTimeout(window.close, 0);
        </script>

    </body>
</html>