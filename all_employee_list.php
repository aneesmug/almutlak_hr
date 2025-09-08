<?php
	require_once __DIR__ . '/includes/db.php';

	require_once __DIR__ . '/includes/session_check.php';
	$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
	if(mysqli_num_rows($query) == 1){
	include("./includes/avatar_select.php");

?>
<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title><?= $site_title ?> - Dashboard</title>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<!--        <meta content="A fully featured admin theme which can be used to build CRM, CMS, etc." name="description" />-->
        <meta content="Anees Afzal" name="author" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />

        <!-- App favicon -->
        <link rel="shortcut icon" href="assets/images/favicon.ico">

        <!-- App css -->
        <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
		<link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />

        <!-- DataTables -->
        <link href="./plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
        <link href="./plugins/datatables/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" />
        <!-- Responsive datatable examples -->
        <link href="./plugins/datatables/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />


        <script src="assets/js/modernizr.min.js"></script>
    </head>
    <body class="enlarged" data-keep-enlarged="true">

        <!-- Begin page -->
        <div id="wrapper">

            <!-- ========== Left Sidebar Start ========== -->
            <div class="left side-menu">

                <div class="slimScrollDiv active" id="remove-scroll">

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

                        <div class="col-xl-12">
                                <div class="card-box">
                                    <h4 class="header-title m-t-0 m-b-30">All Employees</h4>
                                    <div class="tab-content">
                                        
                                    
                                        <!-- <div class="tab-pane active show" id="bylist-b1"> -->
<table id="employee_vac" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
    <thead>
        <tr>
            <th>Emp. ID</th>
            <th>Employee Name</th>
            <th>Department</th>
            <th>sectin_nme</th>
            <th>country</th>
            <th>CIVIL STATUS</th>
            <th>Sex</th>
            <th>dob</th>
            <th>CONTRACT YEAR</th>
            <th>vacation_days</th>
            <th>Iqama / ID</th>
            <th>Mobile</th>
            <th>joining_date</th>
            
        </tr>
    </thead>
    <tbody>
<?php
    $sql = "SELECT * FROM `employees` WHERE `status`=1 AND `emp_sup_type`='mocha' "; 
    $query = mysqli_query($conDB, $sql);

    while ($rec = mysqli_fetch_array($query)) {
        $id = $rec["id"];
        $name = $rec["name"];
        $emp_id = $rec["emp_id"];
        $iqama = $rec["iqama"];
        $mobile = $rec["mobile"];
        $salary = $rec["salary"];
        $vacation_days = $rec["vacation_days"];
        $joining_date = $rec["joining_date"];
        $date_reg = $rec["date_reg"];
        $emp_avatar = $rec["avatar"];
        $emp_status = $rec["status"];
        $emp_status_fly = $rec["fly"];
        $emptype = $rec["emptype"];        
        $sectin_nme = $rec["sectin_nme"];
        $country = $rec["country"];
        $mar_status = $rec["mar_status"];
        $sex = $rec["sex"];
        $dob = $rec["dob"];
        $vac_period = $rec["vac_period"];
        $dept = $rec["dept"];
        $vacation_days = $rec["vacation_days"];
        $joining_date = $rec["joining_date"];

?>
                <tr>
                    <th><?= $emp_id; ?></th>
                    <th><?= $name; ?></th>
                    <th><?= $dept; ?></th>
                    <th><?= $sectin_nme; ?></th>
                    <th><?= $country; ?></th>
                    <th><?= $mar_status; ?></th>
                    <th><?= $sex; ?></th>
                    <th><?= $dob; ?></th>
                    <th><?= $vac_period; ?></th>
                    <th><?= $vacation_days; ?></th>
                    <th><?= $iqama; ?></th>
                    <th><?= $mobile; ?></th>
                    <th><?= $joining_date; ?></th>
                   
                    
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

         <!-- jQuery  -->
        <script src="assets/js/jquery.min.js"></script>
        <script src="assets/js/bootstrap.bundle.min.js"></script>
        <script src="assets/js/metisMenu.min.js"></script>
        <script src="assets/js/waves.js"></script>
        <script src="assets/js/jquery.slimscroll.js"></script>


        <!-- Modal-Effect -->
        <script type="text/javascript" src="./plugins/parsleyjs/parsley.min.js"></script>
        <script src="./plugins/moment/moment.js"></script>

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
        var buttonConfig = [];
        var exportTitle = "All Users"
        buttonConfig.push({extend:'excel' ,title: exportTitle,className: 'btn-success'});
        buttonConfig.push({extend:'pdf',exportOptions: {columns: [ 0, 1, 2, 3 ]} ,title: exportTitle,className: 'btn-danger'});
        buttonConfig.push({extend:'print' ,exportOptions: {columns: [ 0, 1, 2, 3 ]} ,title: exportTitle,className: 'btn-dark'});
        // buttonConfig.push({text: '<i class="fa fa-plus"></i> Add Machine', action: function ( e, dt, button, config ) {window.location = './add_machine.php' } ,className: 'btn-info'});
                $('form').parsley();
                
                //Buttons examples
                var table = $('#employee_vac').DataTable({
                    lengthChange: false,
                    buttons: buttonConfig,
                    // order: [[ 4, "desc" ]],
                    // "columnDefs": [
                    //                 {
                    //                 targets: [ 4 ],
                    //                 visible: false,
                    //                 searchable: false
                    //                 },
                    //             ],
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
        </script>


    </body>
</html>
<?php } ?>
