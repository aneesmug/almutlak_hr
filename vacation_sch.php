<?php
	require_once __DIR__ . '/includes/db.php';
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

            <div class="content-page" style="margin: 0 auto !important; width: 950px !important; ">

                <!-- Top Bar Start -->
                 <div class="topbar">

	<nav class="navbar-custom">

				

		<ul class="list-inline menu-left mb-0">
			<li>
				<div class="page-title-box">
					<h4 class="page-title">Human Resource</h4>
					<ol class="breadcrumb">
						<li class="breadcrumb-item active">Welcome to Mochachino Admin Panel !</li>
					</ol>
				</div>
			</li>

		</ul>

	</nav>

</div>
                <!-- Top Bar End -->


                <!-- Start Page content -->
                <div class="content">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card-box">
                                    <h4 class="m-t-0 header-title">Please Enter your Mochachino Employee ID.</h4>
                                    <form action="vacation_details_sch.php" method="get">
										<?php echo $msg; ?>
                                        <div class="form-row">
											<div class="form-group col-md-12">
                                                <label for="emp_id" class="col-form-label">Please Enter your Employee ID.<span class="text-danger">*</span></label>
                                                <input type="text" name="emp_id" parsley-trigger="change" required class="form-control" id="emp_id" autocomplete="off" style="padding: 35px !important; font-size: 30px !important; border-radius: 60px !important;" />
                                            </div>
                                        </div>
										<div class="btn-group" role="group" aria-label="Edit Button">
                                        <button type="submit" name="submit" class="btn btn-primary"><i class="mdi mdi-table-edit"></i> Search ID</button>
										</div>
                                    </form>
                                    <?php
//										echo $flydime = date('M d Y', strtotime(str_replace('/', '-', "26/06/2017")));
//										echo "<br>";
//										echo date('d/m/Y', strtotime($flydime. ' + 3 days'));
									?>
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
                    buttons: ['copy', 'excel', 'pdf', 'print'],
					order: [[ 7, "desc" ]],
					"columnDefs": [
									{
									targets: [ 7 ],
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
	
//$(document).ready(function(){
//  $("input[name$='note']").click(function(){	  
//  var value = $(this).val();
//  if(value=='Encashed') {
//    $("#return_date").show();
//    $("#note").hide();
//	$("#note2").hide();
//	$("#return_date").removeAttr('required');
//	$("#permit_no").removeAttr('required');
//  }
//  else if(value=='Fly') {
//	//document.getElementById("pet_id").required = true;
//	$("#return_date").attr('required', '');
//	$("#permit_no").attr('required', '');
//   	$("#note").show();
//	$("#note2").hide();
////    $("#pet_id_box").hide();
//   }
//  else if(value=='Local Vacation') {
//	//document.getElementById("pet_id").required = true;
//	$("#return_date_v").attr('required', '');
//   	$("#note2").show();
//	$("#note").hide();
////    $("#pet_id_box").hide();
//   }
//  });
//  	$("#return_date").removeAttr('required');
//  	$("#return_date_v").removeAttr('required');
////  	$("#pet_id_box").show();
//  	$("#note").hide();
//	$("#note2").hide();
//});
        </script>

    </body>
</html>