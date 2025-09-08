<?php
	require_once __DIR__ . '/includes/db.php';
	require_once __DIR__ . '/includes/session_check.php';
	$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
	if(mysqli_num_rows($query) == 1){
	include("./includes/avatar_select.php");


    $getdb = $_GET['find_birthday'];

    $dobget = date("Y/m/d", strtotime($getdb));
    $dobbymonth = date("F", strtotime($getdb));

?>
<!doctype html>
<html lang="en">

    <head>
        <meta charset="utf-8" />
        <title><?php echo $site_title ?> - Birthday by <?php echo $dobbymonth ?></title>
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
	<div class="col-12">
        <div class="card-box">
            <h3 class="m-t-0">Search Birthday by Month "<?php echo $dobbymonth ?>"</h3>
            <form action="find_birthday.php" method="get">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="datepickerdob" class="col-form-label">Select month<span class="text-danger">*</span></label>
                    <input type="text" name="find_birthday" parsley-trigger="change" required autocomplete="off" placeholder="Select any date of month" class="form-control" id="datepickerdob">
                </div>
            </div>                          
            <button type="submit" name="submit" class="btn btn-primary"><i class="mdi mdi-update"></i> Search Birthday</button>
            </form>
        </div>
		<div class="card-box table-responsive">
			<h4 class="m-t-0 header-title">All Employees Birthday of "<?php echo $dobbymonth ?>"</h4>
<table id="employee_vac" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
	<thead>
		<tr>
			<th>Employee ID</th>
            <th>Employee Name</th>
			<th>Department</th>
			<th>Date of Birth</th>
            <th>Country</th>
			<th>ID</th>
		</tr>
	</thead>
	<tbody>
<?php

	$sql_emp_vac = "SELECT * FROM `employees` WHERE MONTH(`dob`) = MONTH('".$dobget."') AND `status` = 1 ";
    $query_emp_vac = mysqli_query($conDB, $sql_emp_vac);

while ($rec = mysqli_fetch_array($query_emp_vac)) {
    $id_get = $rec["id"];
    $name_get = $rec["name"];
    $emp_id_get = $rec["emp_id"];
    $iqama_get = $rec["iqama"];
    $mobile_get = $rec["mobile"];
    $dial_code_get = $rec["dial_code"];
    $emg_mobile_get = $rec["emg_mobile"];
    $salary_get = $rec["salary"];
    $vacation_days_get = $rec["vacation_days"];
    $joining_date_get = $rec["joining_date"];
    $date_reg_get = $rec["created_at"];
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

    $iqama_exp_get = $rec['iqama_exp'];
    $passport_number_get = $rec['passport_number'];
    $passport_exp_get = $rec['passport_exp'];
    $emg_mobile_get = $rec['emg_mobile'];
    $emg_name_get = $rec['emg_name'];
    $blood_type_get = $rec['blood_type'];
    $t_shirt_size_get = $rec['t_shirt_size'];
    $address_get = $rec['address'];
    $emp_email_get = $rec['email'];

    $insurance_no_get = $rec['insurance_no'];
    $insurance_exp_get = $rec['insurance_exp'];
	
//	$times_reg = strtotime("$date_emp");
//	$datevac = date('d, M Y', $times_reg);
 $datevac = date('d, F Y', strtotime($dob_get));
	
?>
				<tr>
					<th><?php echo $emp_id_get ?></th>
                    <th><?php echo $name_get ?></th>
					<th><?php echo $dept_get; ?></th>
					<th><?php echo $datevac; ?></th>
                    <th><?php echo $country_get; ?></th>
					<th><?php echo date('d', strtotime($dob_get)); ?></th>
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

        <!-- App js -->
		<script src="assets/pages/jquery.form-pickers.init.js"></script>
		
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
                $('form').parsley();
				
				//Buttons examples
                var table = $('#employee_vac').DataTable({
                    lengthChange: false,

                    // buttons: ['copy', 'excel', 'pdf', 'print'],

                   buttons: [
                            { extend: 'excel',exportOptions: {columns: [ 0, 1, 2, 3, 4 ]} },
                            { extend: 'pdf',exportOptions: {columns: [ 0, 1, 2, 3, 4 ]} },
                            { extend: 'print',exportOptions: {columns: [ 0, 1, 2, 3, 4 ]} },
                         ],


					order: [[ 5, "asc" ]],
					"columnDefs": [
									{
									targets: [ 5 ],
									visible: false,
									searchable: false
									},
								],
					});
				
					table.buttons().container()
					.appendTo('#employee_vac_wrapper .col-md-6:eq(0)');
				
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
	
$(document).ready(function(){
  $("input[name$='note']").click(function(){	  
  var value = $(this).val();
  if(value=='Encashed') {
    $("#return_date").show();
    $("#note").hide();
	$("#return_date").removeAttr('required');
	$("#permit_no").removeAttr('required');
  }
  else if(value=='Fly') {
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