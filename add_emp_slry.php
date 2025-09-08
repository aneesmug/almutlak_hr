<?php
	require_once __DIR__ . '/includes/db.php';

	require_once __DIR__ . '/includes/session_check.php';
	$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
		if(mysqli_num_rows($query) == 1){
		include("./includes/avatar_select.php");
	}
	require("./includes/emp_query.php");
	if(mysqli_num_rows($get_emp_data) !== 0){
		$allRecords = mysqli_fetch_all($get_emp_data, MYSQLI_ASSOC);
		foreach ($allRecords as $rec) {
			$emprow = $rec;
		}
		if($emprow["status"] == "0" && $emprow["note"] == "expired"){
			$note_get = "Expired";
		} elseif($emprow["status"] == "0" && $emprow["note"] == "terminat"){
			$note_get = "Terminated";
		}	
	} else {
			//when the id not equals id show database
		header("Location: ./reg_employee.php");
	}

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
        try {
            // Validate total salary match
            $salaryData = $_POST;
            $postedTotal = (float)$_POST['totalsal'];
            $expectedTotal = (float)$emprow['salary'];
            if (abs($postedTotal - $expectedTotal) > 0.01) {
                throw new Exception(__('total_salary_mismatch')." ".__('expected'). ": {$expectedTotal}, " . __('submitted'). ": {$postedTotal}");
            }
            // Define allowed salary components (whitelist)
            $allowedFields = [
                'basic', 'housing', 'transport', 'food', 'misc',
                'cashier', 'fuel', 'tel', 'other', 'guard'
            ];
            // Process dynamic fields
            $salaryData = [':emp_id' => $emprow['empid']];
            $columns = ['emp_id'];
            $placeholders = [':emp_id'];
            foreach ($allowedFields as $field) {
                if (isset($_POST[$field])) {
                    $value = (float)$_POST[$field];
                    $salaryData[":$field"] = $value;
                    $columns[] = $field;
                    $placeholders[] = ":$field";
                }
            }
            // Verify we have data to insert
            if (count($columns) <= 1) {
                throw new Exception(__('no_valid_salary_components_provided'));
            }
            // Calculate sum of components for verification
            $componentsSum = array_sum(array_slice($salaryData, 1)); // Skip emp_id
            if (abs($componentsSum - $postedTotal) > 0.01) {
                throw new Exception(__('salary_components_dont_add_up_to_the_total'));
            }
            // Begin transaction
            $pdo->beginTransaction();
            // 1. Check if record exists and update status to 0 if it does
            $checkStmt = $pdo->prepare("SELECT id FROM emp_salary WHERE emp_id = :emp_id AND status = 1");
            $checkStmt->execute([':emp_id' => $emprow['empid']]);
            $existingRecord = $checkStmt->fetch();
            if ($existingRecord) {
                $updateStmt = $pdo->prepare("UPDATE emp_salary SET status = 0 WHERE id = :id");
                $updateStmt->execute([':id' => $existingRecord['id']]);
            }
            // 2. Insert new record with status = 1
            $columns[] = 'status'; // Add status column
            $placeholders[] = ':status'; // Add status placeholder
            $salaryData[':status'] = 1; // Set status to active
            $sql = "INSERT INTO emp_salary (" . implode(', ', $columns) . ") 
                    VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($salaryData);
            // Commit transaction
            $pdo->commit();
            salert(__('success_title'), __("salary_details_saved_successfully!"), "success", "view_employee.php?emp_id={$emprow['empid']}", __('ok'));
        } catch (PDOException $e) {
            // Only roll back if a transaction is active
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            salert(__('error_title'), __('database_error').' '.$e->getMessage(), "error", "add_emp_slry.php?emp_id={$emprow['empid']}" , __('ok'));

        } catch (Exception $e) {
            // Only roll back if a transaction is active
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            salert(__('error_title'), ' '.$e->getMessage().' ', "error", "add_emp_slry.php?emp_id={$emprow['empid']}", __('ok'));
        }
    }

?>

<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

    <head>
        <meta charset="utf-8" />
        <title><?= $site_title ?> - Employee View</title>
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
    <body class="enlarged" data-keep-enlarged="true" data-page="add_emp_slry">

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
                    
						<?php include("./includes/emp_top_info.php"); ?>
                        <!-- end row -->

                        <div class="row">
                            <div class="col-md-12">
                                <div class="card-box">

                                    <h4 class="m-t-0 header-title"><?=__('edit_employee_salary') ?></h4>
                                    <form action="<?= $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data" class="registration">
                                        <div class="form-row">
											<div class="form-group col-md-2">
                                                <label for="basic" class="col-form-label"><?=__('basic') ?><span class="text-danger">*</span></label>
                                                <input type="text" name="basic" class="form-control" id="basic" required value="<?= $emprow['basic'] ?>" />
                                            </div>
                                            <div class="form-group col-md-2">
                                                <label for="housing" class="col-form-label"><?=__('housing') ?></label>
                                                <input type="text" name="housing" class="form-control" id="housing" required value="<?= $emprow['housing'] ?>" />
                                            </div>
                                            <div class="form-group col-md-2">
                                                <label for="transport" class="col-form-label"><?=__('transport') ?></label>
                                                <input type="text" name="transport" class="form-control" id="transport" required value="<?= $emprow['transport'] ?>" />
                                            </div>
                                            <div class="form-group col-md-2">
                                                <label for="food" class="col-form-label"><?=__('food') ?></label>
                                                <input type="text" name="food" class="form-control" id="food" required value="<?=(!$emprow['food'])?0:$emprow['food'] ?>" />
                                            </div>
                                            <div class="form-group col-md-2">
                                                <label for="misc" class="col-form-label"><?=__('misc') ?></label>
                                                <input type="text" name="misc" class="form-control" id="misc" required value="<?=(!$emprow['misc'])?0:$emprow['misc'] ?>" />
                                            </div>
                                            <div class="form-group col-md-2">
                                                <label for="cashier" class="col-form-label"><?=__('cashier') ?></label>
                                                <input type="text" name="cashier" class="form-control" id="cashier" required value="<?=(!$emprow['cashier'])?0:$emprow['cashier'] ?>" />
                                            </div>
                                            <div class="form-group col-md-2">
                                                <label for="fuel" class="col-form-label"><?=__('fuel') ?></label>
                                                <input type="text" name="fuel" class="form-control" id="fuel" required value="<?=(!$emprow['fuel'])?0:$emprow['fuel'] ?>" />
                                            </div>
                                            <div class="form-group col-md-2">
                                                <label for="tel" class="col-form-label"><?=__('tel') ?></label>
                                                <input type="text" name="tel" class="form-control" id="tel" required value="<?=(!$emprow['tel'])?0:$emprow['tel'] ?>" />
                                            </div>
                                            <div class="form-group col-md-2">
                                                <label for="other" class="col-form-label"><?=__('others') ?></label>
                                                <input type="text" name="other" class="form-control" id="other" required value="<?=(!$emprow['other'])?0:$emprow['other'] ?>" />
                                            </div>
                                            <div class="form-group col-md-2">
                                                <label for="guard" class="col-form-label"><?=__('guard') ?></label>
                                                <input type="text" name="guard" class="form-control" id="guard" required value="<?=(!$emprow['guard'])?0:$emprow['guard'] ?>" />
                                            </div>
                                            <div class="form-group col-md-2">
                                                <label for="total" class="col-form-label"><?=__('total_salary') ?></label>
                                                <input type="text" name="totalsal" class="form-control" id="total" readonly>
                                            </div>
                                        	
                                        </div>										

										<div class="btn-group" role="group" aria-label="Edit Button">
                                        
										<a href="view_employee.php?id=<?= $_GET['emp_id']; ?>" class="btn btn-dark"><i class="fa fa-angle-double-left"></i> <?=__('back_button') ?></a>
                                        <button type="submit" name="submit" class="btn btn-primary"><i class="mdi mdi-account-edit"></i><?=__('yes_register') ?></button>
											
										</div>
											
                                    </form>
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
<!-- /.modal -->

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
		
        <script src="./plugins/bootstrap-filestyle/js/bootstrap-filestyle.min.js" type="text/javascript"></script>

        <!-- App js -->
		<script src="assets/pages/jquery.form-pickers.init.js"></script>
		
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js"></script>
<script type="text/javascript">
	$(document).ready(function() {
		$('form').parsley();
	});
	jQuery(function($) {
		$('.autonumber').autoNumeric('init');
	});
	jQuery.browser = {};
	(function () {
		jQuery.browser.msie = false;
		jQuery.browser.version = 0;
		if (navigator.userAgent.match(/MSIE ([0-9]+)\./)) {
			jQuery.browser.msie = true;
			jQuery.browser.version = RegExp.$1;
		}
	})();
	/***************************/
// $(function () {
//   $("#basic, #housing, #transport, #food, #misc, #cashier, #fuel, #tel, #other, #guard").keyup(function () {
//     $("#total").val(+$("#basic").val() + +$("#housing").val()+ +$("#transport").val()+ +$("#food").val()+ +$("#misc").val()+ +$("#cashier").val()+ +$("#fuel").val()+ +$("#tel").val()+ +$("#other").val()+ +$("#guard").val()  );
//   });
// });
$(function () {
  // Calculate total (handles empty inputs safely)
  function calculateTotal() {
    const total = 
      (+$("#basic").val() || 0) +
      (+$("#housing").val() || 0) +
      (+$("#transport").val() || 0) +
      (+$("#food").val() || 0) +
      (+$("#misc").val() || 0) +
      (+$("#cashier").val() || 0) +
      (+$("#fuel").val() || 0) +
      (+$("#tel").val() || 0) +
      (+$("#other").val() || 0) +
      (+$("#guard").val() || 0);
    $("#total").val(total);
  }
  // Initial calculation
  calculateTotal();
  // Auto-update on any change (typing, paste, etc.)
  $("input").on("input change", calculateTotal);
});
</script>

    </body>
</html>