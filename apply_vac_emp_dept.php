<?php
	require_once __DIR__ . '/includes/db.php';
	require_once __DIR__ . '/includes/session_check.php';
	$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
		if(mysqli_num_rows($query) == 1){
		include("./includes/avatar_select.php");

		require("./includes/emp_query.php");
		require("./includes/vacation_processor.php");

	if(mysqli_num_rows($get_emp_data) !== 0){
		$allRecords = mysqli_fetch_all($get_emp_data, MYSQLI_ASSOC);
		foreach ($allRecords as $rec) {
			$emprow = $rec;
		}
	$vacyear_get = preg_replace("/[^0-9]/","",$emprow['vac_period']);
	$getqueryempvac_bal = mysqli_query($conDB, "SELECT *, SUM(`vacdays`) AS SUMDAYS FROM `emp_vacation` WHERE `created_at` BETWEEN CURDATE() and DATE_ADD(CURDATE(), INTERVAL '".$vacyear_get."' YEAR) AND `emp_id`='".$emprow['empid']."' ");
		while($recempvac_bal = mysqli_fetch_assoc($getqueryempvac_bal)){
			// $emp_vacdays_get = $recempvac_bal["vacdays"];
			$emp_vacdays_get = $recempvac_bal["SUMDAYS"];
			$emp_vacdate_get = $recempvac_bal["date"] ? date('Y', strtotime(str_replace('/', '-', $recempvac_bal["date"]))): null; // or your preferred default value
			$vacdaysget = $recempvac_bal['vacdays'];
		}
		$vacdays_get = $emprow['vacation_days'] - $vacdaysget;
	$getqueryaply = mysqli_query($conDB, "SELECT * FROM `apply_vac_dep` WHERE `emp_id`='".$emprow['empid']."' AND `review`='A' ORDER BY `id` DESC ");
		while($recaply = mysqli_fetch_assoc($getqueryaply)){
			$id_vac_get = $recaply["id"];
			$emp_id_vac_get = $recaply["emp_id"];
			$status_vac_get = $recaply["status"];
			$review_vac_get = $recaply["review"];
			$fly_type_get = $recaply["fly_type"];
			$vac_type_get = $recaply["vac_type"];
		}
} else {
		//when the id not equals id show database
		header("Location: ./reg_employee.php");
	}


$vacation_id_to_approve = 26; 
$current_user_role = 'HR_Assistant'; // This would come from your session or user management system.

// Instantiate the processor
$vacationProcessor = new VacationProcessor($conDB);

// Call the approval method, passing the role
$result = $vacationProcessor->approveVacationRequest($vacation_id_to_approve, $current_user_role, '1898', '300');

if ($result['success']) {
    echo "Vacation approved successfully!";
    // If the role was 'GM', the vacation balance has also been updated automatically.
} else {
    echo "Error: " . $result['message'];
}
debug($result);

if(isset($_POST['submit'])){

	$vacationProcessor = new VacationProcessor($conDB);
		$request_data = [
		'start_date' => $_POST['date'],
		'end_date' => $_POST['return_date'],
		'vac_type' => $_POST['vac_type'],
		'fly_type' => $_POST['fly_type'],
		'remarks' => 'Family vacation',
		'note' => '',
		'replacement_person' => $_POST['replacement_person'],
		'last_vac_date' => '2024-07-15',
		// 'ticket_pay' => 500.00,
		// 'permit_fee' => 100.00
	];
	$result = $vacationProcessor->submitVacationRequest('5430', $request_data);
	debug($result);

	// if($_POST['date']){
	// 	$replacement_person_up = mysqli_real_escape_string($conDB, $_POST['replacement_person']);
	// 	$date_up = $_POST['date'];
	// 	$return_date_up = $_POST['return_date'];
	// 	$vac_type_up = htmlentities($_POST['vac_type']);
	// 	$fly_type_up = htmlentities($_POST['fly_type']);
	// 		$nextvacdatepo = date('Y-m-d', strtotime($date_up.' + ' .$emprow['vac_period'] . ' days'));
	// 	if ($fly_type_up !== 'emergency') {
	// 		$flydatetime = strtotime(date('M d Y', strtotime(date('M d Y', strtotime(str_replace('/', '-', $date_up))))));
	// 		$returndatetime = strtotime(date('M d Y', strtotime(date('M d Y', strtotime(str_replace('/', '-', $return_date_up))))));	
	// 		$secs = $returndatetime - $flydatetime;// == <seconds between the two times>
	// 		$vacdays = $secs / 86400;
	// 		if ($vac_type_up == "Encashed") {
	// 			$vacdays = $emprow['vacation_days'];
	// 		}
	// 	}
	// 	// if already take vacation for remaning balance check
	// 	if( $vacdays <= $vacdays_get ) {
	// 	if( $vacdays <= $emprow['vacation_days'] ){
	// 	if($fly_type_up == "Encashed"){
	// 		$vac_type_up = "Encashed";
	// 	}
	// 	mysqli_query($conDB, "INSERT INTO `apply_vac_dep` (`emp_id`,`emp_name`,`dept`,`date_reg`,`status`,`review`,`replacement_person`,`vac_strt_date`,`return_date`,`vac_type`,`empgid`,`next_vac_date`,`fly_type`,`vacdays`) VALUES ('".$emprow['empid']."','".$emprow['name']."','".$emprow['dept']."','".date("c")."','apply','A','".$replacement_person_up."','".$date_up."','".$return_date_up."','".$vac_type_up."','".$_GET['emp_id']."','".$nextvacdatepo."','".$fly_type_up."','".$vacdays."' )") or die ();

	// 	$error_1 = "<div class='alert alert-success'><strong>Successfully!</strong> Employee Vacation applied for <strong>$vacdays</strong> days.</div>";		
	// 	header("refresh:3; ./view_employee.php?emp_id=".$_GET['emp_id']."");
	// } else {
	// 	$error_1 = "<div class=\"alert alert-danger bg-danger text-white border-0\" role=\"alert\">Your vacation selective days ($vacdays) are not matched with contract verified vacation days ($emprow[vacation_days]).</div>";
	// } 
	//  } else {
	// 	$error_1 = "<div class=\"alert alert-danger bg-danger text-white border-0\" role=\"alert\">Your vacation selective days ($vacdays) are not matched from remaining vacation days ($vacdays_get).</div>";
	//  }
	
	// } else {
	// 	$error_1 = "<div class=\"alert alert-danger bg-danger text-white border-0\" role=\"alert\">Please fill out the form!</div>";
	// }
}
	
		/***********************************************/
	
	//$cus_email = "aneesmug2007@yahoo.com";
	//$cus_name = "Anees Afzal";

	// Always set content-type when sending HTML email

	$headers = "MIME-Version: 1.0\r\n";
	//$headers .= "CC: info@hpvetclinic.com\r\n";
	$headers .= "Content-type: text/html; charset=UTF-8\r\n";
	$headers .= 'From: '.$fname.' <'.$emailusr.'>' . "\r\n" . 'Reply-To: ' . $emailusr;
		
	/***********phpMailer***************/
	require './includes/PHPMailerMaster/PHPMailerAutoload.php';
	$mail = new PHPMailer;
	$mail->isSMTP();
	$mail->SMTPDebug = 0;
	$mail->Debugoutput = 'html';
	$mail->IsHTML(true); // send as HTML
	$mail->CharSet="utf-8"; // use utf-8 character encoding
	/***********************************/
	$mail->Host = "mail.mochachino.co";
	$mail->Port = 465;
	$mail->SMTPAuth = true;
	$mail->SMTPSecure = 'ssl';
	$mail->Username = $emailusr;
	$mail->Password = $email_pass;
	$mail->setFrom($emailusr, $fname);
	$mail->addAddress('vac@mochachino.co', 'Anees Mughal');
//	$mail->addReplyTo('aneesmug2007@yahoo.com', 'Happy Pet Vet. Clinic');
	/***********************************/
//	$mail->addBCC('info@hpvetclinic.com', 'Happy Pet Clinic');
	//$mail->addBCC('aneesmug2007@hotmail.com', 'First Last');
	$mail->Subject = "Appointment Information from $fname";
//	$body = $mail->msgHTML(file_get_contents('./email-templates/apply_email_dep.php'), dirname(__FILE__));
	$body = preg_replace('/\\\\/','', $body);
	$body = str_replace('$user_dept', $user_dept, $body);
	$body = str_replace('$pet_name', $pet_name, $body);
	$body = str_replace('$species', $species, $body);
	$body = str_replace('$yearapp', $yearapp, $body);
	$body = str_replace('$rq_time', $rq_time, $body);
	$body = str_replace('$svr_required', $svr_required, $body);
	$body = str_replace('$cus_mobile', $cus_mobile, $body);
	$body = str_replace('$cus_email', $cus_email, $body);
	$body = str_replace('$breed', $breed, $body);
	$body = str_replace('$date', $date, $body);
	$body = str_replace('$currentyear', $currentyear, $body);
	$body = str_replace('$cus_message', $cus_message, $body);
	$mail->Body=$body;
	$mail->AltBody = 'This is a plain-text message body';
//	if (!$mail->send()) {
//		echo "Mailer Error: " . $mail->ErrorInfo;
//	} else {
////		header("Location: index.php?page=thankyoucontactus");
//	}
/***********phpMailer***************/
		
	/***********************************************/

?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title><?=$site_title ?> - All Employees</title>
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

    <link href="./plugins/bootstrap-timepicker/hijri_css/bootstrap-datetimepicker.css" rel="stylesheet">
    <link href="./plugins/bootstrap-timepicker/hijri_css/bootstrap-datetimepicker.min.css" rel="stylesheet">

    <!-- App css -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
    <style type="text/css">
    .noneDIV {
        display: none;
    }

    .showDIV {
        display: block;
    }
    </style>
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
                    <?php include("./includes/emp_top_info.php"); ?>
                    <!-- end row -->

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card-box">

                                <h4 class="m-t-0 header-title">Apply Vacation</h4>
                                <form action="<?=$_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data">
                                    <?=$error_1." ".$return_date_up ?>
                                    <div class="form-row">
                                        <div class="form-group col-md-4">
                                            <label for="name">Employee Name</label>
                                            <input type="text" value="<?=$emprow['name'] ?>" class="form-control"
                                                readonly>
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="emp_id">Employee ID.</label>
                                            <input type="text" value="<?=$emprow['empid'] ?>" class="form-control"
                                                readonly>
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="type">Department</label>
                                            <input type="text" value="<?=$emprow['deptnme'] ?>" class="form-control"
                                                readonly>
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="date_select" class="col-form-label">Vacation Date<span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="date" parsley-trigger="change" required
                                                placeholder="YYYY-MM-DD" class="form-control" id="date_select"
                                                autocomplete="off">
                                        </div>

                                        <div class="form-group col-md-4">
                                            <label for="inlineRadio3" class="col-form-label radioalign">Remarks<span
                                                    class="text-danger">*</span></label>
                                            <div class="radio radio-info form-check-inline">
                                                <input type="radio" id="inlineRadio3" value="Local Vacation"
                                                    name="vac_type" onclick="showLocalVacation()">
                                                <label for="inlineRadio3" class="atch"><i
                                                        class="fa fa-odnoklassniki"></i> Local Vacation</label>
                                            </div>
                                            <?php if($emprow['country_name'] <> "Saudi Arabia" && $emprow['country_name'] <> "Myanmar" ){?>
                                            <div class="radio radio-info form-check-inline">
                                                <input type="radio" id="inlineRadio1" value="Fly" name="vac_type"
                                                    onclick="showFly()">
                                                <label for="inlineRadio1" class="atch"><i
                                                        class="mdi mdi-airplane-takeoff"></i> Fly </label>
                                            </div>
                                            <?php } ?>
                                            <div class="radio radio-info form-check-inline">
                                                <input type="radio" id="inlineRadio2" value="Encashed" name="vac_type"
                                                    onclick="showEncashed()">
                                                <label for="inlineRadio2" class="atch"><i
                                                        class="mdi mdi-square-inc-cash"></i> Encashed</label>
                                            </div>
                                        </div>

                                    </div>
                                    <div class="form-row noneDIV" id="LocalVacationDIV">
                                        <div class="form-group col-md-4">
                                            <label for="type" class="col-form-label">Replacement Person!<span
                                                    class="text-danger">*</span></label>
                                            <select class="form-control" name="replacement_person"
                                                id="replacement_person_local">
                                                <option value="">Select</option>
                                                <?php
												$query_emp_apl_nme = mysqli_query($conDB, "SELECT * FROM `employees` WHERE `dept`='".$emprow['dept']."' AND `dept`<>'' AND `status`=1 ORDER BY `name` REGEXP '^[^A-Za-z]' ASC, name");
												while($rec = mysqli_fetch_assoc($query_emp_apl_nme)){
													$emp_apl_nme = $rec["name"];
											?>
                                                <option value="<?=$emp_apl_nme ?>"><?=$emp_apl_nme ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="return_date_v" class="col-form-label">Return Date<span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="return_date" id="return_date_v"
                                                parsley-trigger="change" placeholder="YYYY-MM-DD" class="form-control"
                                                autocomplete="off">
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="remarks" class="col-form-label">Notes<span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="remarks" parsley-trigger="change"
                                                class="form-control" id="remarks" autocomplete="off">
                                        </div>
                                    </div>

                                    <div class="form-row noneDIV" id="FlyDIV">

                                        <div class="form-group col-md-4">
                                            <label for="type" class="col-form-label">Replacement Person!<span
                                                    class="text-danger">*</span></label>
                                            <select class="form-control" name="replacement_person"
                                                id="replacement_person_fly">
                                                <option value="">Select</option>
                                                <?php
												$query_emp_apl_nme = mysqli_query($conDB, "SELECT * FROM `employees` WHERE `dept`='".$emprow['dept']."' AND `dept`<>'' AND `status`=1 ORDER BY `name` REGEXP '^[^A-Za-z]' ASC, name");
												while($rec = mysqli_fetch_assoc($query_emp_apl_nme)){
													$emp_apl_nme = $rec["name"];
											?>
                                                <option value="<?=$emp_apl_nme ?>"><?=$emp_apl_nme ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>

                                        <div class="form-group col-md-4">
                                            <label for="return_dated" class="col-form-label">Return Date<span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="return_date" parsley-trigger="change"
                                                placeholder="YYYY-MM-DD" class="form-control" id="return_dated"
                                                autocomplete="off" required>
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="type" class="col-form-label radioalign">Select Vacation
                                                Type<span class="text-danger">*</span></label>
                                            <div class="radio radio-info form-check-inline">
                                                <input type="radio" id="vac_type1" value="annual" name="fly_type">
                                                <label for="vac_type1" class="atch"><i
                                                        class="mdi mdi-all-inclusive"></i> Annual Vacation</label>
                                            </div>
                                            <div class="radio radio-info form-check-inline">
                                                <input type="radio" id="vac_type2" value="emergency" name="fly_type">
                                                <label for="vac_type2" class="atch"><i
                                                        class="mdi mdi-chemical-weapon"></i> Emergency Vacation</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="btn-group" role="group" aria-label="Edit Button">

                                        <a href="view_employee.php?id=<?=$_GET['id']; ?>" class="btn btn-dark"><i
                                                class="fa fa-angle-double-left"></i> Back</a>
                                        <button type="submit" name="submit" class="btn btn-primary"><i
                                                class="mdi mdi-near-me"></i> Apply Vacation</button>

                                    </div>

                                </form>
                            </div>
                        </div>
                    </div>


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
    <div class="modal fade terminat" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true"
        style="display: none;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background-color: brown !important; color: #fff !important;">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                    <h4 class="modal-title" id="mySmallModalLabel">
                        <i class="mdi mdi-delete-circle"></i>
                        Are you sure!
                    </h4>
                </div>
                <div class="modal-body">
                    <h3>You need to Terminat!</h3>
                    <h4><strong style="font-size: 30px; "><?=$emprow['name'] ?></strong></h4>
                    <div class="form-row" id="content" style="display:none;">
                        <form action="./includes/terminat_emp.php" method="get">
                            <!--	<a href="" class="btn btn-danger waves-effect waves-light" ><i class="mdi mdi-account-off"></i> Terminat</a>-->
                            <input type="hidden" name="id" value="<?=$id_get ?>">
                            <input type="hidden" name="note" value="terminat">
                            <div class="input-group">
                                <input type="text" id="ter_note" name="ter_note" class="form-control"
                                    aria-describedby="basic-addon2">
                                <div class="input-group-append">
                                    <button type="submit" class="btn btn-danger waves-effect waves-light"><i
                                            class="mdi mdi-account-off"></i> Terminat Submit</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-dark waves-effect" data-dismiss="modal">Close</button>
                    <a href="./includes/terminat_emp.php?id=<?=$id_get ?>&note=expired"
                        class="btn btn-light waves-effect waves-light"><i class="mdi mdi-account-star"></i> Expired</a>
                    <button type="button" id="terminat_emp" class="btn btn-danger waves-effect waves-light"><i
                            class="mdi mdi-account-off"></i> Terminat</button>

                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->

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
    (function() {
        jQuery.browser.msie = false;
        jQuery.browser.version = 0;
        if (navigator.userAgent.match(/MSIE ([0-9]+)\./)) {
            jQuery.browser.msie = true;
            jQuery.browser.version = RegExp.$1;
        }
    })();
    /***************************/
    function showLocalVacation() {
        $("#LocalVacationDIV").removeClass("noneDIV");
        $("#LocalVacationDIV").addClass("showDIV");

        //Make sure schoolDIV is not visible
        $("#FlyDIV").removeClass("showDIV");
        $("#EncashedDIV").removeClass("showDIV");
        $("#FlyDIV").addClass("noneDIV");
        $("#EncashedDIV").addClass("noneDIV");

        $("#LocalVacationDIV :input").prop('required', true);
        $("#FlyDIV :input").prop('required', null);
        $("#EncashedDIV :input").prop('required', null);

        $("input#return_date_v:text").attr('name', 'return_date');
        $("input#return_dated:text").removeAttr('name');
        $("select#replacement_person_fly").removeAttr('name');
        $("select#replacement_person_local").attr('name', 'replacement_person');

    }

    function showFly() {
        $("#FlyDIV").removeClass("noneDIV");
        $("#FlyDIV").addClass("showDIV");

        //Make sure bankDIV is not visible
        $("#LocalVacationDIV").removeClass("showDIV");
        $("#EncashedDIV").removeClass("showDIV");
        $("#LocalVacationDIV").addClass("noneDIV");
        $("#EncashedDIV").addClass("noneDIV");

        $("#FlyDIV :input").prop('required', true);
        $("#LocalVacationDIV :input").prop('required', null);
        $("#EncashedDIV :input").prop('required', null);

        $("input#return_dated:text").attr('name', 'return_date');
        $("input#return_date_v:text").removeAttr('name');
        $("select#replacement_person_local").removeAttr('name');
        $("select#replacement_person_fly").attr('name', 'replacement_person');
    }

    function showEncashed() {
        $("#EncashedDIV").removeClass("noneDIV");
        $("#EncashedDIV").addClass("showDIV");

        //Make sure bankDIV is not visible
        $("#FlyDIV").removeClass("showDIV");
        $("#LocalVacationDIV").removeClass("showDIV");
        $("#FlyDIV").addClass("noneDIV");
        $("#LocalVacationDIV").addClass("noneDIV");

        $("#FlyDIV :input").prop('required', null);
        $("#LocalVacationDIV :input").prop('required', null);

        $("input#return_dated:text").removeAttr('name');
        $("input#return_date_v:text").removeAttr('name');
        $("select#replacement_person_local").removeAttr('name');
        $("select#replacement_person_fly").removeAttr('name');
    }
    /***************************/
    /***************************/
    //$(document).ready(function(){
    //  $("input[name$='note']").click(function(){	  
    //  var value = $(this).val();
    //  if(value=='Encashed') {
    //	$("#flyed").hide();
    //	$("#note2").hide();
    //	$("#return_dated").hide();
    //	$("#return_date_v").hide();
    //	$("#return_date_v").removeAttr('required');
    //	$("#return_dated").removeAttr('required');
    //	$("#permit_no").removeAttr('required');
    //	$("#replacement_person_fly").removeAttr('required');
    //	$("#replacement_person_local").removeAttr('required');
    //	$("#remarks").removeAttr('required');
    //	$("#vac_type").removeAttr('required');
    //  }
    //  else if(value=='Fly') {
    //	//document.getElementById("pet_id").required = true;
    //	$("#replacement_person_fly").attr('required', '');
    //	$("#replacement_person_fly").show();
    //	$("#replacement_person_local").hide();
    //	$("#return_dated").show();
    //	$("#return_dated").attr('required', '');
    //	$("#permit_no").attr('required', '');
    //	$("#vac_type").attr('required', '');
    //	$("#return_date_v").removeAttr('required', '');
    //	$("#replacement_person_local").removeAttr('required', '');
    //	$("#remarks").removeAttr('required', '');
    //   	$("#flyed").show();
    //	$("#note2").remove();
    ////    $("#pet_id_box").hide();
    //   }
    //  else if(value=='Local Vacation') {
    //	//document.getElementById("pet_id").required = true;
    //	$("#replacement_person_local").show();
    //	$("#replacement_person_fly").hide();
    //	$("#replacement_person_fly").hide();
    //	$("#return_dated").hide();
    //	$("#replacement_person_local").attr('required', '');
    //	$("#return_date_v").attr('required', '');
    //	$("#remarks").attr('required', '');
    //	$("#return_dated").removeAttr('required');
    //	$("#replacement_person_fly").removeAttr('required');
    //	$("#vac_type").removeAttr('required');
    //   	$("#return_date_v").show();
    //   	$("#note2").show();
    //	$("#flyed").remove();
    ////    $("#pet_id_box").hide();
    //   }
    //  });
    //  	$("#replacement_person_local").removeAttr('required');
    //  	$("#replacement_person_fly").removeAttr('required');
    //  	$("#remarks").removeAttr('required');
    //  	$("#return_date_v").removeAttr('required');
    ////  	$("#pet_id_box").show();
    //  	$("#return_date_v").hide();
    //  	$("#flyed").hide();
    //	$("#note2").hide();
    //});
    /***************************/

    jQuery('#terminat_emp').on('click', function(event) {
        $("#ter_note").attr('required', '');
        jQuery('#content').toggle('show');
    });
    </script>

</body>

</html>
<?php } ?>