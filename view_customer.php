<?php
	require_once __DIR__ . '/includes/db.php';
	require_once __DIR__ . '/includes/session_check.php';
	$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
	if(mysqli_num_rows($query) == 1){
	include("./includes/avatar_select.php");

$getquery = mysqli_query($conDB, "SELECT * FROM `customer` WHERE `id`='".$_GET['id']."' ");

	if(mysqli_num_rows($getquery) !== 0){
		while($rec = mysqli_fetch_assoc($getquery)){
			$id_cust = $rec["id"];
			$injazat_no = $rec["injazat_no"];
			$acc_no = $rec["acc_no"];
			$full_name = $rec["full_name"];
			$mobile = $rec["mobile"]; 
			$status = $rec["status"];
            $sectin_nme = $rec["sectin_nme"];
			$expdate = $rec["exp_date"];
			$datereg = $rec["created_at"];

			$times_reg = strtotime($expdate);
			$exp_date = date('d-m-Y', $times_reg);

			$timestamp_reg = strtotime("$datereg");
			$date_reg = date('d, M Y', $timestamp_reg);

           $query_cust_card_count = mysqli_query($conDB, "SELECT *, COUNT(`id`) AS `upd_cust` FROM `cust_card_update` WHERE `cust_no`='".$id_cust."' ");
            while ($rec = mysqli_fetch_array($query_cust_card_count)) {
                $upd_status = $rec["status"];
                $upd_cust = $rec["upd_cust"];
            }

	}

} else {
		//when the id not equals id show database
		header("Location: ./all_customers.php");
	}
	
?>
<!doctype html>
<html lang="en">

    <head>
        <meta charset="utf-8" />
        <title><?= $site_title ?> - <?= $full_name ?></title>
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
        <link href="./plugins/bootstrap-select/css/bootstrap-select.min.css" rel="stylesheet" />
        <link href="./plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
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
                                <div class="profile-user-box card-box <?= ($status == "A") ? "bg-dark" : "bg-danger"; ?>">
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="media-body text-white">
                                                <h4 class="mt-1 mb-1 font-18">Customer Name: <?= $full_name ?></h4>
                                                <p class="text-light mb-0">No of Renewals: <?= $upd_cust ?></p>
                                                <p class="text-light mb-0">Mobile: <?= $mobile ?></p>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="text-left text-white">
												<h4 class="mt-1 mb-1 font-18">Injazat No.: <span class="copyToClipboard"><?= $injazat_no ?></span> <i class="fa fa-clipboard"></i></h4>
                                                <p class="text-light mb-0">Account No.: <span class="copyToClipboard"><?= $acc_no ?></span> <i class="fa fa-clipboard"></i></p>
                                                <p class="text-light mb-0">Expiry Date: <span class="copyToClipboard"><?= $exp_date ?></span> <i class="fa fa-clipboard"></i></p>
                                            </div>

											<div class="text-right">
											<?php if($status == "A"){ ?>
												<div class="btn-group" role="group" aria-label="Edit Button">
                                                
                                                <a href='javascript:voic(0);' class='btn btn-sm btn-success waves-effect cardAddAttr' data-id='<?=$id_cust?>' data-acc_no='<?=$acc_no?>'><i class='fa fa-plus'></i> Add New Card</a>
                                                
                                                <?php if ($injazat_no != 0 ) { ?>
                                                
                                                <a href='javascript:void(0);' class='btn btn-sm btn-primary waves-effect cardUpdateAttr' data-id='<?=$id_cust?>' data-injazat_no='<?=$injazat_no?>' data-acc_no='<?=$acc_no?>' ><i class='mdi mdi-account-convert'></i> Update Card</a>

                                                <?php } ?>
												<a href="javascript:void(0);" class="btn btn-sm btn-light waves-effect editCustomerAtter" data-id='<?=$id_cust?>' data-full_name='<?=$full_name?>' data-mobile='<?=$mobile?>' data-acc_no='<?=$acc_no?>' data-location='<?=$sectin_nme?>' data-injazat_no='<?=$injazat_no?>' data-card_exp='<?=$exp_date?>'>
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
						
<div class="row">
	<div class="col-12">
		<div class="card-box table-responsive">
			<h4 class="m-t-0 header-title"><?= $name_mach ?> Card's Details</h4>
<table id="mac_trans" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
	<thead>
		<tr>
			<th>Injazat No.</th>
			<th>Expiry Date</th>
            <th>Received from Shop</th>
			<th>Date of Registration</th>
			<th>Status</th>
			<th>ID</th>
			<?php if($user_type == $access1){ ?>
			<th>Action</th>
			<?php } ?>
		</tr>
	</thead>
	<tbody>
<?php
	
$query_cust_card = mysqli_query($conDB, "SELECT * FROM `cust_card_update` WHERE `cust_no`='".$id_cust."' ");
while ($rec = mysqli_fetch_array($query_cust_card)) {
	$id_cust_crd = $rec["id"];
	$injazat_no_lst = $rec["injazat_no"];
    $expdatelst = $rec["exp_date"];
	$status_get = $rec["status"];
    $dateregtrn = $rec["created_at"];
    $sectin_nme_get = $rec["sectin_nme"];

    $exp_date_lst =   date('d, M Y', strtotime($expdatelst));
    $date_reg_trn =  date('d, M Y', strtotime($dateregtrn));
	
	
?>
					<tr>
					    <th><?= $injazat_no_lst; ?></th>
						<th><?= $exp_date_lst; ?></th>
                        <th><?= $sectin_nme_get; ?></th>
						<th><?= $date_reg_trn; ?></th>
						<th><?= ($status_get == "A") ? "<span class='badge badge-success'>Active</span>" : "<span class='badge badge-danger'>Suspended</span>" ?></th>
						<th><?= $id_mac_trn; ?></th>
						<?php if($user_type == $access1 ){ ?>
						<th width="60">

        <div class='btn-group dropdown'>
            <a href='javascript: void(0);' class='table-action-btn dropdown-toggle arrow-none btn btn-light btn-sm' data-toggle='dropdown' aria-expanded='false'><i class='mdi mdi-dots-horizontal'></i></a>
            <div class='dropdown-menu dropdown-menu-right' x-placement='bottom-end' >
                <a href='javascript:void(0);' class='dropdown-item text-danger deleteAjax' data-id='<?=$rec["id"]?>' data-tbl='cust_card_update' data-file='0' ><i class='fa fa-trash mr-2 font-18 vertical-middle'></i>Delete</a>
            </div>
        </div>
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
                    <?= $site_footer ?>
                </footer>

            </div>

            <!-- ============================================================== -->
            <!-- End Right content here -->
            <!-- ============================================================== -->
        </div>
        <!-- END wrapper -->
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</form>
</div>

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

        <script src="./plugins/select2/js/select2.min.js" type="text/javascript"></script>
        <script src="./plugins/bootstrap-select/js/bootstrap-select.js" type="text/javascript"></script>

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
        var exportTitle = "Serial No.: <?= $serial ?> | ID.: <?= $m_id ?>"
        var exportTitleP = "<h4>Serial No.: <?= $serial ?> | ID.: <?= $m_id ?></h4>"
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
        var exportTitle = "Serial No.: <?= $serial ?> | ID.: <?= $m_id ?>"
        var exportTitleP = "<h4>Serial No.: <?= $serial ?> | ID.: <?= $m_id ?></h4>"
        buttonConfig.push({extend:'excel',exportOptions: {columns: [ 0, 1, 2, 3, 4 ]} ,title: exportTitle,className: 'btn-success'});
        buttonConfig.push({extend:'pdf',exportOptions: {columns: [ 0, 1, 2, 3, 4 ]} ,title: exportTitle,className: 'btn-danger'});
        buttonConfig.push({extend:'print' ,exportOptions: {columns: [ 0, 1, 2, 3, 4 ]} ,title: exportTitleP,className: 'btn-dark'});

                $('form').parsley();
				
				//Buttons examples
                var table = $('#mac_trans').DataTable({
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