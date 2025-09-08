<?php
	require_once __DIR__ . '/includes/db.php';
	require_once __DIR__ . '/includes/session_check.php';
	$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
	if(mysqli_num_rows($query) == 1){
	include("./includes/avatar_select.php");
	
	include("./includes/Hijri_GregorianConvert.php");
	$DateConv=new Hijri_GregorianConvert;
	$format="DD/MM/YYYY";
	
	// $getquery = mysqli_query($conDB, "SELECT * FROM `section` WHERE `id`='".$_GET['location_id']."' ");
	$getquery = mysqli_query($conDB, "
		SELECT *,
    COUNT(`machines`.`location_id`) AS `location_count`,
    `section`.`id` AS `lid`,
    `section`.`status` AS `locstatus`,
    `location_img`.`id` AS `limgid`
FROM `section`
    LEFT JOIN `location_img` ON `section`.`id` = `location_img`.`location_id`
    LEFT JOIN `location_docu` ON `section`.`id` = `location_docu`.`location_id`
    LEFT JOIN `location_contract` ON `section`.`id` = `location_contract`.`location_id`
    LEFT JOIN `machines` ON `section`.`id` = `machines`.`location_id`
WHERE `section`.`id` ='".$_GET['location_id']."'
	");

	if(mysqli_num_rows($getquery) !== 0){
		while($rec = mysqli_fetch_assoc($getquery)){
			$id_loc = $rec["id"];
            $section_name = $rec["section_name"];
            $dept = $rec["dept"];
            $location_owner = $rec["location_owner"];
            $camera_in = $rec["camera_in"];
            $camera_out = $rec["camera_out"];
            $b_license_exp = $rec["b_license_exp"];
            $b_license_no = $rec["b_license_no"];
            $location_dist = $rec["location_dist"];
            $bulding_base = $rec["bulding_base"];
            $bulding_size = $rec["bulding_size"];
            $t_bulding_size = $rec["t_bulding_size"];
            $latitude = $rec["latitude"];
            $longitude = $rec["longitude"];
            $location_name = $rec["location_name"];
            $municipality = $rec["municipality"];
            $sub_municipality = $rec["sub_municipality"];
            
            $loc_address = $rec["location_name"];
            $status = $rec["locstatus"];
            $in_img = $rec["in_img"];
            $out_img = $rec["out_img"];
            $id_img = $rec["limgid"];

            $owner_name = $rec["owner_name"];
			$owner_number = $rec["owner_number"];
			$owner_email = $rec["owner_email"];
			$contract_no = $rec["contract_no"];
			$start_cont_date = $rec["start_cont_date"];
			$end_cont_date = $rec["end_cont_date"];
			$rent = $rec["rent"];
			$others = $rec["others"];
			$service = $rec["service"];
			$elect_prc = $rec["elect_prc"];
			$water_prc = $rec["water_prc"];
			$incuranse_prc = $rec["incuranse_prc"];

			$tamount = $rent + $others + $service + $elect_prc + $water_prc;
			$totvat = $tamount / 100 * 15;

            /*$status = $rec["status"];*/
			
			
			
			// $salary_get = str_replace(',', '', $salary_get);
		
			// $joining_date_get = date('M d Y', strtotime(str_replace('/', '-', $joining_date_get)));
			// $datebirth_get = date('M d Y', strtotime(str_replace('/', '-', $dob_get)));
			// $ter_date_get = date('M d Y', strtotime(str_replace('/', '-', $ter_date_get)));
				
			// $birth_date = date('Y-m-d', strtotime(str_replace('/', '-', $dob_get)));
			// $hours_in_day   = 24;
			// $minutes_in_hour= 60;
			// $seconds_in_mins= 60;
			// $birth_date     = new DateTime($birth_date);
			// $current_date   = new DateTime();
			// $diff           = $birth_date->diff($current_date);
			// $years	   = $diff->y . " Years";
			

	}	
		$getqueryaply = mysqli_query($conDB, "SELECT * FROM `location_contract` WHERE `location_id`='".$id_loc."' ");
			while($recaply = mysqli_fetch_assoc($getqueryaply)){
				$id_get = $recaply["id"];
				$location_id = $recaply["location_id"];
				$owner_name = $recaply["owner_name"];
				$owner_number = $recaply["owner_number"];
				$owner_email = $recaply["owner_email"];
				$contract_no = $recaply["contract_no"];
				$start_cont_date = $recaply["start_cont_date"];
				$end_cont_date = $recaply["end_cont_date"];
				$rent = $recaply["rent"];
				$others = $recaply["others"];
				$service = $recaply["service"];
				$elect_prc = $recaply["elect_prc"];
				$water_prc = $recaply["water_prc"];
				$incuranse_prc = $recaply["incuranse_prc"];
				$reg_date = $recaply["created_at"];
				
				// $timestamp_reg = strtotime($date_reg_vac_get);
				// $date_reg_vac_get = date('d, M Y', $timestamp_reg);
				
				// $flydatetime = strtotime(date('M d Y', strtotime(date('M d Y', strtotime(str_replace('/', '-', $vac_strt_date_vac_get))))));
				// $returndatetime = strtotime(date('M d Y', strtotime(date('M d Y', strtotime(str_replace('/', '-', $return_date_vac_get))))));	
				// $secs = $returndatetime - $flydatetime;// == <seconds between the two times>
				// $vacdays = $secs / 86400;

				$tamount = $rent + $others + $service + $elect_prc + $water_prc;

				$totvat = $tamount / 100 * 15;

			}
		/*$getquerylgn = mysqli_query($conDB, "SELECT * FROM `location_img` WHERE `location_id`='".$id_loc."' ORDER BY id DESC LIMIT 1 ");
			while($recaply = mysqli_fetch_assoc($getquerylgn)){
				$in_img_get = $recaply["in_img"];
				$out_img_get = $recaply["out_img"];;
			}*/
		
		
} else {
		//when the id not equals id show database
		header("Location: ./all_locations.php");
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
    <body class="enlarged" data-keep-enlarged="true" onLoad="javascript:window.print()">
    <!-- <body class="enlarged" data-keep-enlarged="true">			 -->

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
                                <div class="card-box" id="nodeToRenderAsPDF">
                                    <div class="clearfix">
                                            <center><img src="assets/images/logo.png" alt="" height="80"></center>
										
                                       
                                    </div>
                                    
                                    <div class="row">
                                       	<div class="col-6">
                                       	<div class="card-box">
											<img src="<?php echo $in_img ?>" alt="<?php echo $section_name ?>" class="img-thumbnail" style="height: 160px !important; margin-left: 15px !important;" />
											<img src="<?php echo $out_img ?>" alt="<?php echo $section_name ?>" class="img-thumbnail" style="height: 160px !important; margin-left: 15px !important;" />
										</div>
										</div>
										<div class="col-6">
                                       	<div class="card-box float-right">
                                       		<h4 class="m-0 text-right">Profile for <?php echo $section_name ?></h4>
											<img src="qrconfig_geo_location.php?glocation=<?php echo $latitude.",".$longitude ?>" alt="<?php echo $section_name ?>" class="img-thumbnail" style="height: 160px !important; margin-left: 15px !important;" />
										</div>
										</div>
										

                                    </div>
                                    <!-- end row -->
									<div class="row">

		<div class="col-12">
				<div class="card-box">

				<table class="table table-hover mb-0">
					<thead class="thead-dark">
						<tr>
							<th colspan="4"><center><h4>Location Information</h4></center></th>
						</tr>
					</thead>
			<tbody>
			<tr>
				<th>Name of Location:</th>
				<td><?php echo $section_name; ?></td>
				<th>Total Bulding Size <small>(in Metters)</small>:</th>
				<td><?php echo $t_bulding_size; ?></td>
			</tr>
			<tr>
				<th>Balady License No.:</th>
				<td><?php echo $b_license_no; ?></td>
				<th>Balady License Expiry <small>(in Hijri)</small>:</th>
				<td><?php echo $b_license_exp; ?><span class="badge badge-info float-right"><?php echo $DateConv->HijriToGregorian($b_license_exp, $format); ?></span></td>
			</tr>
			<tr>
				<th>Municipality:</th>
				<td><?php echo ucfirst($municipality); ?></td>
				<th>Sub-Municipality:</th>
				<td><?php echo ucfirst($sub_municipality); ?></td>
			</tr>
			<tr>
				<th>Address:</th>
				<td><?php echo $location_name; ?></td>
				<th>District:</th>
				<td><?php echo $location_dist; ?></td>
			</tr>
			<tr>
				<th>Bulding Base.:</th>
				<td><?php echo $bulding_base; ?></td>
				<th>Bulding Size <small>(L * W)</small>:</th>
				<td><?php echo $bulding_size; ?></td>
			</tr>
			<tr>
				<th>Camera Inside.:</th>
				<td><?php echo $camera_in; ?></td>
				<th>Camera Outside.:</th>
				<td><?php echo $camera_out; ?></td>
			</tr>
			<tr>
				<th>Latitude:</th>
				<td><?php echo $latitude; ?></td>
				<th>Longitude:</th>
				<td><?php echo $longitude; ?></td>
			</tr>

			<?php /* if($emp_status_get == "no"){ ?>
			<tfoot class="thead-danger">
				<tr style="background: #F1556C;">
					<th>Termination Date:</th>
					<td><?php echo $ter_date_get; ?></td>
					<th>Termination Reason(s):</th>
					<td><?php if($ter_note_get <> ""){echo $ter_note_get;}else{echo "No termination note";} ?></td>
				</tr>
			</tfoot>
			<?php } */ ?>

			</tbody>
		</table>
		
		<table class="table table-hover mb-0">
			<tbody>
			<thead class="thead-dark">
				<tr>
					<th colspan="4"><center><h4>Contract Information</h4></center></th>
				</tr>
			</thead>
			<tbody>
			<tr>
				<th>Owner of Location:</th>
				<td><?php echo $owner_name; ?></td>
				<th>Location Contact No:</th>
				<td><?php echo $owner_number; ?></td>
			</tr>
			<tr>
				<th>Owner Email:</th>
				<td><?php echo $owner_email; ?></td>
				<th>Contract No:</th>
				<td><?php echo $contract_no; ?></td>
			</tr>
			<tr>
				<th>Contract Start <small>(in Hijri)</small>:</th>
				<td><?php echo $start_cont_date; ?><span class="badge badge-info float-right"><?php echo $DateConv->HijriToGregorian($start_cont_date, $format); ?></span></td>
				<th>Contract End <small>(in Hijri)</small>:</th>
				<td><?php echo $end_cont_date; ?><span class="badge badge-info float-right"><?php echo $DateConv->HijriToGregorian($end_cont_date, $format); ?></span>
				</td>
			</tr>
			</tbody>
			<thead class="thead-dark">
				<tr>
					<th colspan="4"><center><h4>Contract Payment Information</h4></center></th>
				</tr>
			</thead>
			
			<tr>
				<th>Amount of Rent:</th>
				<td><?php echo number_format($rent, 2); ?> - SAR</td>
				<th>Amount of Service:</th>
				<td><?php echo number_format($service ,2); ?> - SAR</td>
			</tr>
			<tr>
				<th>Amount of Electric City:</th>
				<td><?php echo number_format($elect_prc, 2); ?> - SAR</td>
				<th>Amount of Water:</th>
				<td><?php echo number_format($water_prc, 2); ?> - SAR</td>
			</tr>
			<tr>
				<th>Amount of Incuranse:</th>
				<td><?php echo number_format($incuranse_prc, 2); ?> - SAR</td>
				<th>Others:</th>
				<td><?php echo number_format($others, 2); ?> - SAR</td>
			</tr>
			<tr>
				<th>Total VAT <small>(15%)</small> :</th>
				<td><?php echo number_format($totvat, 2); ?> - SAR</td>
				<th>Total Amount with VAT <small>(15%)</small> :</th>
				<td><?php echo number_format($tamount + $totvat, 2); ?> - SAR</td>
			</tr>
			</tbody>
		</table>
					
				</div>
<!--										<a href="javascript:window.print()">Link</a>-->
										</div>
									</div>
                                    
                                </div>

                            </div>

                        </div>
                        <!-- end row -->

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
<!--        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.2.61/jspdf.min.js"></script>-->
		<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.3.4/jspdf.min.js" integrity="sha256-vIL0pZJsOKSz76KKVCyLxzkOT00vXs+Qz4fYRVMoDhw=" crossorigin="anonymous"></script>
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
					order: [[ 4, "desc" ]],
					"columnDefs": [
									{
									targets: [ 4 ],
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
/***************************/
	jQuery('#terminat_emp').on('click', function(event) {  
	   $("#ter_note").attr('required', '');
		jQuery('#content').toggle('show');
	});
/****************************/
//	window.print();
// 	setTimeout(window.close, 0);
</script>

    </body>
</html>
<?php } ?>