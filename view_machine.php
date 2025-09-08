<?php
	require_once __DIR__ . '/includes/db.php';
	require_once __DIR__ . '/includes/session_check.php';
	$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
	if(mysqli_num_rows($query) == 1){
	include("./includes/avatar_select.php");

$getquery = mysqli_query($conDB, "SELECT * FROM `machines` WHERE `id`='".$_GET['id']."' ");

	if(mysqli_num_rows($getquery) !== 0){
		while($rec = mysqli_fetch_assoc($getquery)){
			$id_car = $rec["id"];
            $m_id = $rec["m_id"];
			$name_mach = $rec["name_mach"];
			$maker_name = $rec["maker_name"];
			$location = $rec["location"];
			$remarks = $rec["remarks"];
			$status = $rec["status"];
			$serial = $rec["serial"];
			$datereg = $rec["created_at"];

		//	$times_reg = strtotime("$date_emp");
		//	$datevac = date('d, M Y', $times_reg);

			$timestamp_reg = strtotime("$datereg");
			$date_reg = date('d, M Y', $timestamp_reg);

		$query_mactrny = mysqli_query($conDB, "SELECT * FROM `machine_trans` WHERE `mid`='".$_GET['id']."' ORDER BY `id` DESC LIMIT 1");
            while ($rec = mysqli_fetch_array($query_mactrny)) {
                $new_location_trn = $rec["location"];
                $old_location_trn = $rec["old_location"];
            }

           $old_location = ($new_location_trn == "") ? $location : $new_location_trn; 



	}

} else {
		//when the id not equals id show database
		header("Location: ./all_machines.php");
	}
	
?>
<!doctype html>
<html lang="en">

    <head>
        <meta charset="utf-8" />
        <title><?php echo $site_title ?> - <?php echo $name_mach ?></title>
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
                            <div class="col-xl-12">
                                <!-- meta -->
                                <div class="profile-user-box card-box <?php echo ($status == "1") ? "bg-custom" : "bg-danger"; ?>">
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="media-body text-white">
                                                <h4 class="mt-1 mb-1 font-18">Machine Name: <?php echo $name_mach ?></h4>
                                                <p class="text-light mb-0">Location: <?php echo $old_location ?></p>
                                                <p class="text-light mb-0">Remarks: <?php echo $remarks ?></p>
                                                <p class="text-light mb-0">Machine ID: <?php echo $m_id ?></p>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="text-left text-white">
												<h4 class="mt-1 mb-1 font-18">Model: <?php echo $maker_name ?></h4>
                                                <p class="text-light mb-0">Serial: <?php echo $serial ?></p>
                                                <p class="text-light mb-0">Date Reg.: <?php echo $date_reg ?></p>
                                            </div>

											<div class="text-right">
											<?php if($status == "1"){ ?>
												<div class="btn-group" role="group" aria-label="Edit Button">
											
												<a href="add_replace_item.php?id=<?php echo $id_car ?>" class="btn btn-sm btn-primary waves-effect">
                                                    <i class="mdi mdi-database-plus"></i> Add Replace Item
                                                </a>
											
												<a href="add_mac_transfer.php?id=<?php echo $id_car ?>" class="btn btn-sm btn-primary waves-effect">
                                                    <i class="mdi mdi-transfer"></i> Transfer
                                                </a>
											
												<a href="edit_machine.php?id=<?php echo $id_car ?>" class="btn btn-sm btn-light waves-effect">
                                                    <i class="fa fa-edit"></i> Edit
                                                </a>
                                            	</div>
                                            <?php } ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!--/ meta -->

                            </div>
							
							
                        </div>
						
						<?php /* ?>
					<div class="row">
						<div class="col-xl-12">
							<div class="row">

									<div class="col-sm-3">
                                        <div class="card-box tilebox-one <?php if($cont_drv < 1){echo "bg-danger";}else{echo "bg-success";} ?>">
                                            <i class="mdi mdi-car-sports float-right"></i>
                                            <h4 class="text-uppercase mt-0">Driver</h4>
                                            <h5 class="m-b-20" data-plugin="counterup"><?php if($cont_drv < 1){echo "<h2>No Driver</h2>";}else{echo $car_drv_nme;} ?></h5>
											Receive Date: <div class="float-right"><?php echo $car_rcv_date ?></div>
                                        </div>
                                    </div><!-- end col -->

									<div class="col-sm-3">
                                        <div class="card-box tilebox-one <?php if(15 > $licdays){echo "bg-danger";}elseif(30 >= $licdays){echo "bg-warning";}else{echo "bg-success";} ?>">
                                            <i class="dripicons-wallet float-right"></i>
                                            <h4 class="text-uppercase mt-0">Licence</h4>
                                            <h2 class="m-b-20" data-plugin="counterup"><?php if($exp_date_lic == ""){echo "0";}else{echo $licdays;} ?> Day(s)</h2>
											Expiry Date: <div class="float-right"><?php echo $exp_lic ?></div>
                                        </div>
                                    </div><!-- end col -->

                                    <div class="col-sm-3">
                                        <div class="card-box tilebox-one <?php if(15 > $incdays){echo "bg-danger";}elseif(30 >= $incdays){echo "bg-warning";}else{echo "bg-success";} ?>">
                                            <i class="mdi mdi-clipboard-text float-right"></i>
                                            <h4 class="text-uppercase mt-0">Insurance</h4>
                                            <h2 class="m-b-20"><span data-plugin="counterup"><?php if($exp_date_inc == ""){echo "0";}else{echo $incdays;} ?> Day(s)</span></h2>
											Expiry Date: <div class="float-right"><?php echo $exp_inc ?></div>
                                        </div>
                                    </div><!-- end col -->
									
                                    <div class="col-sm-3">
                                        <div class="card-box tilebox-one <?php if(15 > $mvpidays){echo "bg-danger";}elseif(30 >= $mvpidays){echo "bg-warning";}else{echo "bg-success";} ?>">
                                            <i class="mdi mdi-autorenew float-right"></i>
                                            <h4 class="text-uppercase mt-0">MVPI</h4>
                                            <h2 class="m-b-20" data-plugin="counterup"><?php if($exp_date_mvpi == ""){echo "0";}else{echo $mvpidays;} ?> Day(s)</h2>
											Expiry Date: <div class="float-right"><?php echo $exp_mvpi ?></div>
                                        </div>
                                    </div><!-- end col -->

                                </div>
						</div>
					</div>
					<?php */ ?>
						
<div class="row">
	<div class="col-12">
		<div class="card-box table-responsive">
			<h4 class="m-t-0 header-title"><?php echo $name_mach ?> Replaced Items Detail</h4>
<table id="cars_docu" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
	<thead>
        <tr>
            <th colspan="7"><?php echo $serial ?></th>
        </tr>
		<tr>
			<th>Invoice No.</th>
			<th>Details</th>
			<th>Location</th>
            <th>Date of Invoice</th>
			<th>Total + 15 %Vat</th>
            <th>ID</th>
			<th width="60">Action</th>
			
		</tr>
	</thead>
	<tbody>
<?php
	
	$query_cardoc = mysqli_query($conDB, "SELECT *, SUM(`qty`*`price`) AS `total` FROM `machine_inv` WHERE `mid`=".$_GET['id']." Group By `inv_no` ");
while ($rec = mysqli_fetch_array($query_cardoc)) {
	$id_inv_get = $rec["id"];
    $mid_inv_get = $rec["mid"];
	$inv_no_inv_get = $rec["inv_no"];
	$item_inv_get = $rec["item"];
	$total_inv_get = $rec["total"]; 
    $location_get = $rec["location"]; 
	$price_inv_get = $rec["price"];
    $datereginv = $rec["date_reg"];

    $totalinvget = $total_inv_get / 100 * 15 + $total_inv_get;	
	$date_reg_inv_get = date('d, F Y', strtotime($datereginv));
    $location_get = str_replace(' ', '', $location_get);
?>
					<tr>
						<th><?php echo $inv_no_inv_get; ?></th>
						<th><?php echo $item_inv_get; ?></th>
                        <th><?php echo $location_get ; ?></th>
                        <th><?php echo $date_reg_inv_get; ?></th>
						<th><?php echo $totalinvget ; ?></th>
						<th><?php echo $id_inv_get; ?></th>
						<th>
                            <div class="btn-group" role="group" aria-label="Edit Button">
                                <a href="./view_invoice.php?invo=<?php echo $inv_no_inv_get."&id=".$mid_inv_get ?>" class="btn btn-sm btn-dark waves-effect">
                                    <i class="mdi mdi-eye-outline"></i>
                                </a>
                                <!-- <a href="./edit_car.php?id=<?php //echo $id_car ?>" class="btn btn-sm btn-custom waves-effect">
                                    <i class="fa fa-edit"></i>
                                </a> -->
                                <?php if($user_type == $access1){ ?>
    							<a href="javascript:void(0);" class="btn btn-sm btn-danger waves-effect deleteAjax" data-id='<?=$id_inv_get?>' data-tbl='machine_inv' data-file='0'>
                                    <i class="dripicons-tag-delete"></i>
                                </a>
                                <?php } ?>
                            </div>
						</th>

					</tr>
<?php } ?>
										</tbody>
                                        <tfoot>
                                        <tr>
                                            <th></th>
                                            <th></th>
                                            <th></th>
                                            <th style="text-align:right">Total:</th>
                                            <th></th>
                                            <th>Total</th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
<div class="row">
	<div class="col-12">
		<div class="card-box table-responsive">
			<h4 class="m-t-0 header-title"><?php echo $name_mach ?> Locations Details</h4>
<table id="mac_trans" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
	<thead>
		<tr>
			<th>New Location</th>
			<th>Old Location</th>
			<th>Transfer Date</th>
			<th>Status</th>
			<th>ID</th>
			<?php if($user_type == $access1){ ?>
			<th>Action</th>
			<?php } ?>
		</tr>
	</thead>
	<tbody>
<?php
	
$query_mactrn = mysqli_query($conDB, "SELECT * FROM `machine_trans` WHERE `mid`='".$_GET['id']."' ");
while ($rec = mysqli_fetch_array($query_mactrn)) {
	$id_mac_trn = $rec["id"];
	$new_location_trnt = $rec["location"];
	$old_location_trn = $rec["old_location"];
	$dateregtrn = $rec["date_reg"];

	$date_reg_trn =  date('d, M Y', strtotime($dateregtrn));
	
	
?>
					<tr>
					<th><?php echo ($new_location_trnt == $new_location_trn) ? "<span class='badge badge-success'>{$new_location_trnt} | Current location</span>" : $new_location_trnt; ?></th>
						<th><?php echo $old_location_trn; ?></th>
						<th><?php echo $date_reg_trn; ?></th>
						<th><?php echo ($new_location_trnt == $new_location_trn) ? "<span class='badge badge-success'>Active</span>" : "<span class='badge badge-danger'>Transfred</span>" ?></th>
						<th><?php echo $id_mac_trn; ?></th>
						<?php if($user_type == $access1 ){ ?>
						<th>
							<a href="javascript:void(0);" class="btn btn-sm btn-danger waves-effect deleteAjax" data-id='<?=$id_mac_trn?>' data-tbl='machine_trans' data-file='0'>
								<i class="dripicons-tag-delete"></i>
							</a>
						</th>
						<?php } ?>

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
			jQuery(function($) {
                $('.autonumber').autoNumeric('init');
            });
			$(document).ready(function() {

        var buttonConfig = [];
        var exportTitle = "Serial No.: <?php echo $serial ?> | ID.: <?php echo $m_id ?>"
        var exportTitleP = "<h4>Serial No.: <?php echo $serial ?> | ID.: <?php echo $m_id ?></h4>"
        buttonConfig.push({extend:'excel',exportOptions: {columns: [ 0, 1, 2, 3, 4 ]} ,title: exportTitle,className: 'btn-success'});
        buttonConfig.push({extend:'pdf',exportOptions: {columns: [ 0, 1, 2, 3, 4 ]} ,title: exportTitle,className: 'btn-danger'});
        buttonConfig.push({extend:'print' ,exportOptions: {columns: [ 0, 1, 2, 3, 4 ]} ,title: exportTitleP,className: 'btn-dark'});
        // buttonConfig.push({text: '<i class="fa fa-plus"></i> Add Machine', action: function ( e, dt, button, config ) {window.location = './add_machine.php' } ,className: 'btn-info'});

                $('form').parsley();
				
				//Buttons examples
                var table = $('#cars_docu').DataTable({
                    lengthChange: false,
                    buttons:buttonConfig,
					order: [[ 5, "desc" ]],
					"columnDefs": [ 
									{
									targets: [ 5 ],
									visible: false,
									searchable: false
									},
								],

                        "footerCallback": function ( row, data, start, end, display ) {
            var api = this.api(), data;
 
            // Remove the formatting to get integer data for summation
            var intVal = function ( i ) {
                return typeof i === 'string' ?
                    i.replace(/[\$,]/g, '')*1 :
                    typeof i === 'number' ?
                        i : 0;
            };
 
            // Total over all pages
            total = api
                .column( 4 )
                .data()
                .reduce( function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0 );
 
            // Total over this page
            pageTotal = api
                .column( 4, { page: 'current'} )
                .data()
                .reduce( function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0 );
 
            // Update footer
            $( api.column( 4 ).footer() ).html(
                'SAR '+pageTotal +''
                // 'SAR '+pageTotal +' ( SAR'+ total +' total)'
            );
        }

					});
				
					table.buttons().container()
					.appendTo('#cars_docu_wrapper .col-md-6:eq(0)');

				
            });
			$(document).ready(function() {

        var buttonConfig = [];
        var exportTitle = "Serial No.: <?php echo $serial ?> | ID.: <?php echo $m_id ?>"
        var exportTitleP = "<h4>Serial No.: <?php echo $serial ?> | ID.: <?php echo $m_id ?></h4>"
        buttonConfig.push({extend:'excel',exportOptions: {columns: [ 0, 1, 2, 3 ]} ,title: exportTitle,className: 'btn-success'});
        buttonConfig.push({extend:'pdf',exportOptions: {columns: [ 0, 1, 2, 3 ]} ,title: exportTitle,className: 'btn-danger'});
        buttonConfig.push({extend:'print' ,exportOptions: {columns: [ 0, 1, 2, 3 ]} ,title: exportTitleP,className: 'btn-dark'});

                $('form').parsley();
				
				//Buttons examples
                var table = $('#mac_trans').DataTable({
                    lengthChange: false,
                    buttons:buttonConfig,
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
					.appendTo('#mac_trans_wrapper .col-md-6:eq(0)');
				
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