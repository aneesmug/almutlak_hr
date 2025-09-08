<?php
	require_once __DIR__ . '/includes/db.php';
	require_once __DIR__ . '/includes/session_check.php';
	$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
	if(mysqli_num_rows($query) == 1){
	include("./includes/avatar_select.php");
		
	$sql_count_wait = mysqli_query($conDB, "SELECT COUNT(*) `id` FROM `apply_vac_dep` WHERE `status`='app_hr' AND `review`='A'");
	$status_cont_wait = mysqli_fetch_array($sql_count_wait)[0];
	$sql_count_appr = mysqli_query($conDB, "SELECT COUNT(*) `id` FROM `apply_vac_dep` WHERE `status`='approve'");
	$status_cont_appr = mysqli_fetch_array($sql_count_appr)[0];
	$sql_count_not_appr = mysqli_query($conDB, "SELECT COUNT(*) `id` FROM `apply_vac_dep` WHERE `status`='not_approve'");
	$status_cont_not_appr = mysqli_fetch_array($sql_count_not_appr)[0];

if($_SESSION['user_type'] == "administrator"){
        $getquery = mysqli_query($conDB, "
SELECT * , `smart_request`.`inv_no`
FROM `smart_request`
LEFT JOIN `smt_request_status` ON `smart_request`.`inv_no` = `smt_request_status`.`inv_no`
    AND `smt_request_status`.`status` = (
    SELECT `smt_request_status`.`status`
    FROM `smt_request_status`
    WHERE `smart_request`.`inv_no` = `smt_request_status`.`inv_no`
    ORDER BY `smt_request_status`.`id` DESC
    LIMIT 1 )
GROUP BY `smart_request`.`inv_no`
");
    } elseif ($_SESSION['user_type'] == "gm") {
        $getquery = mysqli_query($conDB, "
SELECT * , `smart_request`.`inv_no`
FROM `smart_request`
LEFT JOIN `smt_request_status` ON `smart_request`.`inv_no` = `smt_request_status`.`inv_no`
WHERE `smt_request_status`.`status` = 'Management'
    AND `smt_request_status`.`status` = (
    SELECT `smt_request_status`.`status`
    FROM `smt_request_status`
    WHERE `smart_request`.`inv_no` = `smt_request_status`.`inv_no`
    ORDER BY `smt_request_status`.`id` DESC
    LIMIT 1 )
GROUP BY `smart_request`.`inv_no`
");
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

                        <div class="row text-center">
                            <div class="col-sm-4 col-xl-4" onclick="window.location.href='gm_emp_list.php?page=1&status=app_hr'" style="cursor: pointer;">
                                <div class="card-box widget-flat border-warning bg-warning text-white">
                                    <i class="mdi mdi-account-convert"></i>
                                    <h3 class="m-b-10"><?php echo $status_cont_wait ?></h3>
                                    <p class="text-uppercase m-b-5 font-13 font-600">Waiting for Approval</p>
                                </div>
                            </div>
                            <div class="col-sm-4 col-xl-4" onclick="window.location.href='gm_emp_list.php?page=1&status=approve'" style="cursor: pointer;">
                                <div class="card-box widget-flat border-success bg-success text-white">
                                    <i class="mdi mdi-account-check"></i>
                                    <h3 class="m-b-10"><?php echo $status_cont_appr ?></h3>
                                    <p class="text-uppercase m-b-5 font-13 font-600">Approved Applications</p>
                                </div>
                            </div>
                            <div class="col-sm-4 col-xl-4" onclick="window.location.href='gm_emp_list.php?page=1&status=not_approve'" style="cursor: pointer;">
                                <div class="card-box bg-danger widget-flat border-danger text-white">
                                    <i class="mdi mdi-account-remove"></i>
                                    <h3 class="m-b-10"><?php echo $status_cont_not_appr ?></h3>
                                    <p class="text-uppercase m-b-5 font-13 font-600">Rejected Applications</p>
                                </div>
                            </div>
													
                        </div>

                        <?php
                            
                            $result=mysqli_query($conDB, "
                                SELECT * , `smart_request`.`inv_no`
                                FROM `smart_request`
                                LEFT JOIN `smt_request_status` ON `smart_request`.`inv_no` = `smt_request_status`.`inv_no`
                                WHERE `smt_request_status`.`status` = 'Management'
                                    AND `smt_request_status`.`status` = (
                                    SELECT `smt_request_status`.`status`
                                    FROM `smt_request_status`
                                    WHERE `smart_request`.`inv_no` = `smt_request_status`.`inv_no`
                                    ORDER BY `smt_request_status`.`id` DESC
                                    LIMIT 1 )
                                GROUP BY `smart_request`.`inv_no`
                                ");
                                if( mysqli_num_rows($result) != 0 ){

                        ?>
                        <div class="card-box">
                            <h4 class="m-t-0 header-title">Waiting for Action (Approve / Reject)</h4>

                            <table id="smt_request" class="table table-striped dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                <thead class="thead-dark">
                                <tr>
                                    <th>id</th>
                                    <th>Invoice No.</th>
                                    <th>Tally ID</th>
                                    <th>Injazat ID</th>
                                    <th>Item name</th>
                                    <th>Subject</th>
                                    <th>Location</th>
                                    <th>Approved by</th>
                                    <th>Prepared by</th>
                                    <th>Created At</th>
                                    <th>Status</th>
                                    <th width="100">Action</th>
                                    
                                </tr>
                                </thead>

                                <tbody>
                                <?php
                                    while($row = mysqli_fetch_assoc($getquery)){
                                        $idno = $row["id"];
                                        $inv_no_get = $row["inv_no"];
                                        $tally_id_get = $row["tally_id"];
                                        $injazat_id_get = $row["injazat_id"];
                                        $item_name_get = $row["item_name"];
                                        $sub_type_get = $row["sub_type"];
                                        $location_get = $row["location"];
                                        $prep_by_get = $row["prep_by"];
                                        $approv_by_get = $row["approv_by"];
                                        $datereg = $row["created_at"];
                                        $timestamp_reg = strtotime("$datereg");
                                        $date_reg = date('d, M Y', $timestamp_reg);
                                        
                                        $status = $row["status"];
                                        $approv_by_get = $row["emp_name"];


                                        if ($status == "draft") {
                                            $status_get = "<span class='btn btn-sm btn-secondary waves-effect'>Not Submited</span>";
                                        } elseif ($status == "Manager") {
                                            $status_get = "<span class='btn btn-sm btn-custom waves-effect'>Waiting from Manager</span>";
                                        } elseif ($status == "Finance") {
                                            $status_get = "<span class='btn btn-sm btn-warning waves-effect'>Waiting from Finance</span>";
                                        } elseif ($status == "Management") {
                                            $status_get = "<span class='btn btn-sm btn-primary waves-effect'>Waiting from GM</span>";
                                        } elseif ($status == "approve") {
                                            $status_get = "<span class='btn btn-sm btn-success waves-effect'>GM Approved</span>";
                                        } elseif ($status == "reject") {
                                            $status_get = "<span class='btn btn-sm btn-danger waves-effect'>Reject from GM</span>";
                                        }
                                    ?>
                                <tr>
                                    <th><?php echo $idno; ?></th>
                                    <th><?php echo $inv_no_get; ?></th>
                                    <th><?php echo $tally_id_get; ?></th>
                                    <th><?php echo $injazat_id_get; ?></th>
                                    <th><?php echo $item_name_get; ?></th>
                                    <th><?php echo $sub_type_get; ?></th>
                                    <th><?php echo $location_get; ?></th>
                                    <th><?php echo $approv_by_get; ?></th>
                                    <th><?php echo $prep_by_get; ?></th>
                                    <th><?php echo $date_reg; ?></th>
                                    <th><?php echo $status_get; ?></th>

                                    <th>
                                    <div class="btn-group" role="group" aria-label="Edit Button">
                                        <a href="./open_request.php?id=<?php echo $inv_no_get ?>" class="btn btn-sm btn-dark waves-effect">
                                            Open <i class="mdi mdi-eye-outline"></i>
                                        </a>
                                    </div>
                                    </th>
                                </tr>
                                <?php }  ?>
                                </tbody>
                            </table>
                        </div>
                        <?php } ?>
                        
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
                var table = $('#smt_request').DataTable({
                    lengthChange: false,
                    buttons: ['copy', 'excel', 'pdf', 'print'],
					order: [[ 0, "desc" ]],
					"columnDefs": [
									{
									targets: [ 0 ],
									visible: false,
									searchable: false
									},
								],
					});
				
					table.buttons().container()
					.appendTo('#smt_request_wrapper .col-md-6:eq(0)');
				
            });

            $('#datatable_exp').DataTable();

                //Buttons examples
                var table = $('#datatable-buttons').DataTable({
                    lengthChange: false,
                    buttons: ['copy', 'excel', 'pdf']
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