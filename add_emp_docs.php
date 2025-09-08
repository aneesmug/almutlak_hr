<?php
	require_once __DIR__ . '/includes/db.php';

	require_once __DIR__ . '/includes/session_check.php';
	$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
		if(mysqli_num_rows($query) == 1){
		include("./includes/avatar_select.php");
	}
$getquery = mysqli_query($conDB, "SELECT * FROM `employees` WHERE `id`='".$_GET['id']."' ");

	if(mysqli_num_rows($getquery) !== 0){
		while($rec = mysqli_fetch_assoc($getquery)){
			$id_get = $rec["id"];
			$name_get = $rec["name"];
			$emp_id_get = $rec["emp_id"];
			$iqama_get = $rec["iqama"];
			$mobile_get = $rec["mobile"];
			$salary_get = $rec["salary"];
			$vacation_days_get = $rec["vacation_days"];
			$joining_date_get = $rec["joining_date"];
			$date_reg_get = $rec["date_reg"];
			$emp_avatar_get = $rec["avatar"];
			$emp_status_get = $rec["status"];
			$emp_ter_date_get = $rec["ter_date"];
			$note_get = $rec["note"];
			$dept_get = $rec["dept"];
			$fly_get = $rec["fly"];
			$emptype_get = $rec["emptype"];
			$sectin_nme_get = $rec["sectin_nme"];
			$country_get = $rec["country"];
			$bank_name_get = $rec["bank_name"];
			$iban_get = $rec["iban"];
			$dob_get = $rec["dob"];
			$vac_period_get = $rec["vac_period"];
			$sex_get = $rec["sex"];
			$mar_status_get = $rec["mar_status"];
			$emp_sup_type_get = $rec["emp_sup_type"];
			
			$salary_get = str_replace(',', '', $salary_get);
			
			
			$birth_date = date('Y-m-d', strtotime(str_replace('/', '-', $dob_get)));
			$hours_in_day   = 24;
			$minutes_in_hour= 60;
			$seconds_in_mins= 60;
			$birth_date     = new DateTime($birth_date);
			$current_date   = new DateTime();
			$diff           = $birth_date->diff($current_date);
			$years	   = $diff->y . " Years";
			
	}	
	$sql_count_fly = mysqli_query($conDB, "SELECT COUNT(*) AS `flystus`, `emp_id` FROM `emp_vacation` WHERE `emp_id`='".$emp_id_get."' && `note`='Fly' ");
	while($rec = mysqli_fetch_assoc($sql_count_fly)){$cont_fly = $rec["flystus"];}
	$sql_count_encashed = mysqli_query($conDB, "SELECT COUNT(*) AS `encashstus`, `emp_id` FROM `emp_vacation` WHERE `emp_id`='".$emp_id_get."' && `note`='Encashed' ");
	while($rec = mysqli_fetch_assoc($sql_count_encashed)){$cont_encashed = $rec["encashstus"];}
	
	
			
	if($emp_status_get == "no" && $note_get == "expired"){
		$note_get = "Expired";
	} elseif($emp_status_get == "no" && $note_get == "terminat"){
		$note_get = "Terminated";
	}
		
	$getqueryaply = mysqli_query($conDB, "SELECT * FROM `apply_vac_dep` WHERE `emp_id`='".$emp_id_get."' AND `review`='A' ORDER BY `id` DESC ");
		while($recaply = mysqli_fetch_assoc($getqueryaply)){
			$id_vac_get = $recaply["id"];
			$emp_id_vac_get = $recaply["emp_id"];
			$status_vac_get = $recaply["status"];
			$review_vac_get = $recaply["review"];
		}
	$getquerylgn = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `emp_id`='".$emp_id_get."' ");
		while($recaply = mysqli_fetch_assoc($getquerylgn)){
			$emp_id_lgn_get = $recaply["emp_id"];
			$dept_lgn_get = $recaply["dept"];;
		}
	$getqueryempvac = mysqli_query($conDB, "SELECT * FROM `emp_vacation` WHERE `emp_id`='".$emp_id_get."' AND `review`='A' ORDER BY `id` DESC ");
		while($recempvac = mysqli_fetch_assoc($getqueryempvac)){
			$id_empvac_get = $recempvac["id"];
			$emp_id_empvac_get = $recempvac["emp_id"];
		}
	$getqueryslry = mysqli_query($conDB, "SELECT * FROM `salary_emp` WHERE `emp_id`='".$emp_id_get."' ORDER BY `id` DESC LIMIT 1 ");
		while($recslry = mysqli_fetch_assoc($getqueryslry)){
			$emp_id_slry_get = $recslry["emp_id"];
			$basic_slry_get = $recslry["basic"];;
			$housing_slry_get = $recslry["housing"];;
			$transport_slry_get = $recslry["transport"];;
			$other_slry_get = $recslry["other_pay"];;
		}
	$getquerygosi = mysqli_query($conDB, "SELECT * FROM `gosi_emp` WHERE `emp_id`='".$emp_id_get."' ORDER BY `id` DESC LIMIT 1 ");
		while($recgosi = mysqli_fetch_assoc($getquerygosi)){
			$gosi_no_get = $recgosi["gosi_no"];
			$amount_gosi_get = $recgosi["amount"];;
			$date_greg_get = $recgosi["date_greg"];;
			$date_hijri_get = $recgosi["date_hijri"];;
		}
		
} else {
		//when the id not equals id show database
		header("Location: ./reg_employee.php");
	}

if(isset($_POST['submit'])){
	$emp_id_po = $_POST['emp_id'];
	$docu_typ_po = $_POST['docu_typ'];
	$docu_id_po = $_GET['id'];
//	$attachment_po = $_POST['attachment'];
	$date_reg = date("c");
	
	/*****************/
	
		$nameava = $_FILES['attachment']['name'];
		$tmp_name = $_FILES['attachment']['tmp_name'];
		$timestamp = date("YmdHis");
		//exploding the file based on . operator
		$file_ext = explode('.',$nameava);
		//count taken (if more than one . exist; files like abc.fff.2013.pdf
		$file_ext_count=count($file_ext);
		//minus 1 to make the offset correct
		$cnt=$file_ext_count-1;
		// the variable will have a value pdf as per the sample file name mentioned above.
		$file_extension= $file_ext[$cnt];
		$attachment_po = "./assets/emp_documents/".$emp_id_po."_".$iqama_get."_".$docu_typ_po."_".$timestamp.".".$file_extension."";
	
//	$image_move = "./assets/emp_pics/".$iqama.".".$nameava." ";
	
//	header( "Refresh:5; url= profile", true, 303);
	
	/*****************/

	if($docu_typ_po){
		if($file_extension == "jpg" OR $file_extension == "pdf" OR $file_extension == "jpeg"){
		move_uploaded_file($tmp_name, $attachment_po);
			
		$query = "INSERT INTO `emp_docu` (`emp_id`, `docu_typ`, `attachment`, `date_reg`, `docu_ext`, `pgid`) VALUES ('".$emp_id_po."', '".$docu_typ_po."', '".$attachment_po."', '".date("c")."','".$file_extension."','".$docu_id_po."')";
		mysqli_query($conDB, $query);
		/************log************/
		mysqli_query($conDB, "INSERT INTO `activity_log` (`user_editor`,`page`,`pg_id`,`reg_date`) VALUES ('".$_COOKIE['user']."','".$pgname."','".$_POST['docu_typ']."','".date("c")."')") or die (mysqli_error());
		/************log************/
		$msg = "<div class=\"alert alert-success bg-success text-white border-0\" role=\"alert\">Add Seccssfully!</div>
		";
		header( "refresh:2 ; url=view_employee.php?id=$_GET[id]" );
	}else{
			$msg = "<div class=\"alert alert-danger bg-danger text-white border-0\" role=\"alert\"><strong>ERROR:</strong> JPG/JPEG, PDF are Accepted only!</div>";
		} 
	} else {
		$msg = "<div class=\"alert alert-danger bg-danger text-white border-0\" role=\"alert\">Please fill out the form!</div>";
	}

	
}

?>

<!doctype html>
<html lang="en">

    <head>
        <meta charset="utf-8" />
        <title><?php echo $site_title ?> - All Employees</title>
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
                            <div class="col-md-12">
                               <?php include("./includes/emp_top_info.php"); ?>
                                <div class="card-box">
                                    <h4 class="m-t-0 header-title">Register New Employee Documents</h4>
                                    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data">
										<?php echo $msg ?>
                                        <div class="form-row">
                                            <div class="form-group col-md-4">
												<label for="emp_id">Employee ID.<span class="text-danger">*</span></label>
												<input type="text" name="emp_id" value="<?php echo $emp_id_get ?>" required class="form-control" id="emp_id" readonly />
                                        	</div>
                                        	
                                        	<div class="form-group col-md-4">
											<label for="docu_typ">Select Documents Type<span class="text-danger">*</span></label>
											<select class="form-control" name="docu_typ" required>
												<option value="">Select</option>
											<?php
												$query_docu_type = mysqli_query($conDB, "SELECT * FROM `docu_type` ORDER BY `duc_type` REGEXP '^[^A-Za-z]' ASC, duc_type");
												while($rec_con = mysqli_fetch_assoc($query_docu_type)){
													$docnme = $rec_con["duc_type"];
											?>
												<option value="<?php echo $docnme ?>"><?php echo $docnme ?></option>
											<?php } ?>
												<option value="Others">Others</option>
											</select>
											</div>
											<div class="form-group col-md-4">
												<label for="attachment">Select Document</label>
												<input type="file" class="filestyle" name="attachment" data-btnClass="btn-primary" accept="image/x-png,image/jpeg,application/pdf" required>
                                            </div>
                                        </div>
										<div class="btn-group" role="group" aria-label="Edit Button">
										<a href="view_employee.php?id=<?php echo $_GET['id']?>" class="btn btn-dark"><i class="fa fa-angle-double-left"></i> Back</a>
                                        <button type="submit" name="submit" class="btn btn-primary">
											<i class="mdi mdi-cloud-upload"></i> Add Document file</button>
										</div>
                                    </form>
                                </div>
                            </div>
                        </div>
						

                    </div> <!-- container -->

                </div> <!-- content -->

                <footer class="footer">
                    <?php echo $site_footer ?>
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
        </script>

    </body>
</html>