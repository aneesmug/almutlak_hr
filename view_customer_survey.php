<?php
	require_once __DIR__ . '/includes/db.php';
	require_once __DIR__ . '/includes/session_check.php';
	$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
	if(mysqli_num_rows($query) == 1){
	include("./includes/avatar_select.php");


    $getquery = mysqli_query($conDB, "SELECT * FROM `survey` WHERE `id`='".$_GET['id']."' ");

    if(mysqli_num_rows($getquery) !== 0){
        while($rec = mysqli_fetch_assoc($getquery)){

            $id_cust = $rec["id"];
            $full_name = $rec["full_name"];
            $email = $rec["email"];
            $mobile = $rec["mobile"];
            $age = $rec["age"]; 
            $gender = $rec["gender"];
            $location = $rec["location"];
            $question_1 = $rec["question_1"];
            $add_msg_1 = $rec["add_msg_1"];
            $question_2 = $rec["question_2"];
            $add_msg_2 = $rec["add_msg_2"];
            $question_3 = $rec["question_3"];
            $add_msg_3 = $rec["add_msg_3"];
            $question_4 = $rec["question_4"];
            $add_msg_4 = $rec["add_msg_4"];
            $question_5 = $rec["question_5"];
            $add_msg_5 = $rec["add_msg_5"];
            $add_msg_6 = $rec["add_msg_6"];
            $datereg = $rec["date_reg"];

        //  $times_reg = strtotime("$date_emp");
        //  $datevac = date('d, M Y', $times_reg);

            $timestamp_reg = strtotime("$datereg");
            $date_reg = date('d, M Y', $timestamp_reg);

    }

} else {
        //when the id not equals id show database
        header("Location: ./customers_survey.php");
    }

?>
<!doctype html>
<html lang="en">

    <head>
        <meta charset="utf-8" />
        <title><?php echo $site_title ?> - Registerd Surveys</title>
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
        <style type="text/css">
            tr.disableLoc{
                background-color: #f1556c !important;
                color: #fff;
            }
            tr.disableLoc:hover{
                background-color: #ef3d58 !important;
            }
        </style>
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
                                <div class="profile-user-box card-box bg-custom">
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="media-body text-white">
                                                <h4 class="mt-1 mb-1 font-18">Customer Name: <?php echo $full_name ?></h4>
                                                <p class="text-light mb-0">Email: <?php echo $email ?></p>
                                                <p class="text-light mb-0">Age: <?php echo $age ?></p>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="text-left text-white">
                                                <h4 class="mt-1 mb-1 font-18">Mobile: <?php echo $mobile ?></h4>
                                                <p class="text-light mb-0">Gender: <?php echo $gender ?></p>
                                                <p class="text-light mb-0">Date of survey: <?php echo $date_reg ?></p>
                                            </div>

                                            <div class="text-right">
                                            <?php if($status == "A"){ ?>
                                                <div class="btn-group" role="group" aria-label="Edit Button">
                                                
                                                <a href="add_cust_card.php?id=<?php echo $id_cust ?>" class="btn btn-sm btn-dark waves-effect">
                                                    <i class="mdi mdi-credit-card-plus"></i> Add New Card
                                                </a>
                                                
                                                <?php if ($injazat_no != 0 ) { ?>
                                                <a href="update_cust_card.php?id=<?php echo $id_cust ?>" class="btn btn-sm btn-primary waves-effect">
                                                    <i class="mdi mdi-credit-card-multiple"></i> Update Card
                                                </a>
                                                <!-- <a href="print_customer_envp.php?id=<?php echo $id_cust ?>" class="btn btn-sm btn-purple waves-effect"> -->
                                                <!-- <a href="./print_customer_envp.php?id=<?php echo $id_cust; ?>" class="btn btn-sm btn-purple waves-effect" target="_blank">
                                                    <i class="mdi mdi-printer"></i> Print Envelope
                                                </a> -->
                                                <?php } ?>
                                                <a href="edit_machine.php?id=<?php echo $id_cust ?>" class="btn btn-sm btn-light waves-effect">
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
                                <div class="">
                                    <div class="timeline">
                                        <article class="timeline-item alt">
                                            <div class="text-right">
                                                <div class="time-show first">
                                                    <a href="javascript:void(0)" class="btn btn-custom w-lg">Survey from <b><?php echo $location ?></b></a>
                                                </div>
                                            </div>
                                        </article>
                                        <article class="timeline-item alt">
                                            <div class="timeline-desk">
                                                <div class="panel">
                                                    <div class="timeline-box">
                                                        <span class="arrow-alt"></span>
                                                        <span class="timeline-icon bg-custom"><i class="mdi mdi-adjust"></i></span>
                                                        <h4 class="text-custom">How was the service provided?</h4>
                                                        <p class="timeline-date text-muted"><?php echo $question_1 ?></p>
                                                        <p><?php echo $add_msg_1 ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </article>
                                        <article class="timeline-item ">
                                            <div class="timeline-desk">
                                                <div class="panel">
                                                    <div class="timeline-box">
                                                        <span class="arrow"></span>
                                                        <span class="timeline-icon bg-custom"><i class="mdi mdi-adjust"></i></span>
                                                        <h4 class="text-custom">Are you satisfied with the quality of the service provided?</h4>
                                                        <p class="timeline-date text-muted"><?php echo $question_2 ?></p>
                                                        <p><?php echo $add_msg_2 ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </article>
                                        
                                       <!--  <article class="timeline-item alt">
                                            <div class="text-right">
                                                <div class="time-show">
                                                    <a href="#" class="btn btn-custom w-lg">Last Month</a>
                                                </div>
                                            </div>
                                        </article> -->

                                        <article class="timeline-item alt">
                                            <div class="timeline-desk">
                                                <div class="panel">
                                                    <div class="timeline-box">
                                                        <span class="arrow-alt"></span>
                                                        <span class="timeline-icon bg-custom"><i class="mdi mdi-adjust"></i></span>
                                                        <h4 class="text-custom">The speed of completion service? </h4>
                                                        <p class="timeline-date text-muted"><?php echo $question_3 ?></p>
                                                        <p><?php echo $add_msg_3 ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </article>

                                        <article class="timeline-item">
                                            <div class="timeline-desk">
                                                <div class="panel">
                                                    <div class="timeline-box">
                                                        <span class="arrow"></span>
                                                        <span class="timeline-icon bg-custom"><i class="mdi mdi-adjust"></i></span>
                                                        <h4 class="text-custom">Was this your first experience with us? </h4>
                                                        <p class="timeline-date text-muted"><?php echo $question_4 ?></p>
                                                        <p><?php echo $add_msg_4 ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </article>

                                        <article class="timeline-item alt">
                                            <div class="timeline-desk">
                                                <div class="panel">
                                                    <div class="timeline-box">
                                                        <span class="arrow-alt"></span>
                                                        <span class="timeline-icon bg-custom"><i class="mdi mdi-adjust"></i></span>
                                                        <h4 class="text-custom">Could your experience be better? If yes. How ? </h4>
                                                        <p class="timeline-date text-muted"><?php echo $question_5 ?></p>
                                                        <p><?php echo $add_msg_5 ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </article>
                                        <?php if ($add_msg_6 != "") { ?>
                                        <article class="timeline-item">
                                            <div class="timeline-desk">
                                                <div class="panel">
                                                    <div class="timeline-box">
                                                        <span class="arrow"></span>
                                                        <span class="timeline-icon bg-custom"><i class="mdi mdi-adjust"></i></span>
                                                        <h4 class="text-custom">What can we do to improve, add or change? What's your suggestion? </h4>
                                                        <p><?php echo $add_msg_6 ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </article>
                                    <?php } ?>
                                    </div>
                                    <!-- end timeline -->
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


        $(document).ready(function(){

        
/* For Export Buttons available inside jquery-datatable "server side processing" - Start
- due to "server side processing" jquery datatble doesn't support all data to be exported
- below function makes the datatable to export all records when "server side processing" is on */

function newexportaction(e, dt, button, config) {
    var self = this;
    var oldStart = dt.settings()[0]._iDisplayStart;
    dt.one('preXhr', function (e, s, data) {
        // Just this once, load all data from the server...
        data.start = 0;
        data.length = 2147483647;
        dt.one('preDraw', function (e, settings) {
            // Call the original action function
            if (button[0].className.indexOf('buttons-copy') >= 0) {
                $.fn.dataTable.ext.buttons.copyHtml5.action.call(self, e, dt, button, config);
            } else if (button[0].className.indexOf('buttons-excel') >= 0) {
                $.fn.dataTable.ext.buttons.excelHtml5.available(dt, config) ?
                    $.fn.dataTable.ext.buttons.excelHtml5.action.call(self, e, dt, button, config) :
                    $.fn.dataTable.ext.buttons.excelFlash.action.call(self, e, dt, button, config);
            } else if (button[0].className.indexOf('buttons-csv') >= 0) {
                $.fn.dataTable.ext.buttons.csvHtml5.available(dt, config) ?
                    $.fn.dataTable.ext.buttons.csvHtml5.action.call(self, e, dt, button, config) :
                    $.fn.dataTable.ext.buttons.csvFlash.action.call(self, e, dt, button, config);
            } else if (button[0].className.indexOf('buttons-pdf') >= 0) {
                $.fn.dataTable.ext.buttons.pdfHtml5.available(dt, config) ?
                    $.fn.dataTable.ext.buttons.pdfHtml5.action.call(self, e, dt, button, config) :
                    $.fn.dataTable.ext.buttons.pdfFlash.action.call(self, e, dt, button, config);
            } else if (button[0].className.indexOf('buttons-print') >= 0) {
                $.fn.dataTable.ext.buttons.print.action(e, dt, button, config);
            }
            dt.one('preXhr', function (e, s, data) {
                // DataTables thinks the first item displayed is index 0, but we're not drawing that.
                // Set the property to what it was before exporting.
                settings._iDisplayStart = oldStart;
                data.start = oldStart;
            });
            // Reload the grid with the original page. Otherwise, API functions like table.cell(this) don't work properly.
            setTimeout(dt.ajax.reload, 0);
            // Prevent rendering of the full data to the DOM
            return false;
        });
    });
    // Requery the server with the new one-time export settings
    dt.ajax.reload();
};
//For Export Buttons available inside jquery-datatable "server side processing" - End

        var buttonConfig = [];
        var exportTitle = "All Locations"
        buttonConfig.push({extend:'excel',exportOptions: {columns: [ 0, 1, 2, 3, 4, 5, 6 ]} ,title: exportTitle,className: 'btn-success', action: newexportaction});
        buttonConfig.push({extend:'pdf',exportOptions: {columns: [ 0, 1, 2, 3, 4, 5, 6 ]} ,title: exportTitle,className: 'btn-danger', action: newexportaction});
        buttonConfig.push({extend:'print' ,exportOptions: {columns: [ 0, 1, 2, 3, 4, 5, 6 ]} ,title: exportTitle,className: 'btn-dark', action: newexportaction});
        buttonConfig.push({text: '<i class="fa fa-plus"></i> Add Customer', action: function ( e, dt, button, config ) {window.location = './add_customer.php' } ,className: 'btn-info'});

            $('#customers_tbl').DataTable({
                
                dom: "Bfrtip",
                'serverSide': true,
                lengthMenu: [[10, 100, -1], [10, 100, "All"]],

                buttons: buttonConfig,
                // 'lengthChange': true,
                'processing': true,
                'serverMethod': 'post',
                'responsive': true,
                'paging': true,
                // 'pageLength': 10,

                'ajax': {
                    'url':'CustomerAjaxfile.php'
                },

                'columns': [
                    { data: 'full_name' },
                    { data: 'injazat_no' },
                    { data: 'acc_no' },
                    { data: 'mobile' },
                    { data: 'issue_date' },
                    { data: 'exp_date' },
                    { data: 'status' },
                    { data: 'action' },
                    // { data: null, defaultContent: "<div class='btn-group' role='group' aria-label='Edit Button'><a href='./view_location.php?id=idcus' class='btn btn-sm btn-dark waves-effect'><i class='mdi mdi-eye-outline'></i></a><a href='./edit_location.php?id=id' class='btn btn-sm btn-custom waves-effect'><i class='fa fa-edit'></i></a></div>" }
                ],

            });
                // t.on( 'order.dt search.dt', function () {
                //     t.column(0, {search:'applied', order:'applied'}).nodes().each( function (cell, i) {
                //         cell.innerHTML = i+1;
                //     } );
                // } ).draw();
                // table.buttons().container()
                //     .appendTo('#customers_tbl_wrapper .col-md-6:eq(0)');
        });


<?php /* ?>        $(document).ready(function() {

        var buttonConfig = [];
        var exportTitle = "All Locations"
        buttonConfig.push({extend:'excel',exportOptions: {columns: [ 0, 1, 2 ]} ,title: exportTitle,className: 'btn-success'});
        buttonConfig.push({extend:'pdf',exportOptions: {columns: [ 0, 1, 2 ]} ,title: exportTitle,className: 'btn-danger'});
        buttonConfig.push({extend:'print' ,exportOptions: {columns: [ 0, 1, 2 ]} ,title: exportTitle,className: 'btn-dark'});
        buttonConfig.push({text: '<i class="fa fa-plus"></i> Add Location', action: function ( e, dt, button, config ) {window.location = './add_location.php' } ,className: 'btn-info'});

                $('form').parsley();
				
				//Buttons examples
                var table = $('#employee_vac').DataTable({
                    lengthChange: false,
                    //buttons: buttonConfig,

                    lengthMenu: [10, 25, 50, 75, 100 ],
                    paging: true,
                    deferRender: true,
                    responsive: true,
                    order: [],
                    "processing": true,

					// order: [[ 0, "desc" ]],
					// "columnDefs": [
					// 				{
					// 				targets: [ 3 ],
					// 				visible: false,
					// 				searchable: false
					// 				},
					// 			],
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
<?php */ ?>
        </script>

    </body>
</html>
<?php } ?>