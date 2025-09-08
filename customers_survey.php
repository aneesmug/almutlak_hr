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
        <meta http-equiv="content-type" content="text/html; charset=UTF-8">
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    	<meta charset="utf-8">

        <title><?php echo $site_title ?> - All Registerd Surveys</title>
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
                            <div class="col-md-6">
                                <div class="card-box">
                                    <h4 class="header-title">How was the service provided?</h4>
                                    <div class="chart mt-6" id="How_was_the_service_provided"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card-box">
                                    <h4 class="header-title">Was this your first experience with us?</h4>
                                    <div class="chart mt-6" id="first_experience_with_us"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card-box">
                                    <h4 class="header-title">Are you satisfied with the quality of the service provided?</h4>
                                    <div class="chart mt-6" id="quality_of_the_service_provided"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card-box">
                                    <h4 class="header-title">The speed of completion service?</h4>
                                    <div class="chart mt-6" id="speed_of_completion_service"></div>
                                </div>
                            </div>
                        </div>
                </div> 
                <!-- container -->


<div class="container-fluid">
<div class="row">
	<div class="col-12">
		<div class="card-box table-responsive">
            <!-- <a href="add_location.php" class="btn btn-primary waves-effect"><i class="mdi mdi-settings"></i> Add New Location</a> -->
			<h4 class="m-t-0 header-title">All Registerd Surveys</h4>
<table id="customers_tbl" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
	<thead>
		<tr>
			<th>Customer Name</th>
            <th>Email</th>
            <th>Mobile</th>
            <th>Gender</th>
            <th>Location</th>
			<th width="60">Action</th>
		</tr>
	</thead>
	<?php /* ?>
    <tbody>
<?php /*
    //$sql_loc = "SELECT * FROM `customer` ORDER BY `section_name` REGEXP '^[^A-Za-z]' ASC, section_name";
    $sql_loc = "SELECT * FROM `customer` LIMIT 0 , 500 ";
    $query_loc = mysqli_query($conDB, $sql_loc);

while ($rec = mysqli_fetch_array($query_loc)) {
	$id = $rec["id"];
    $Injazat_no = $rec["Injazat_no"];
	$full_name = $rec["full_name"];
	$acc_no = $rec["acc_no"];
    $status = $rec["status"];
    $mobile = $rec["mobile"];
    $ExpDate = $rec["ExpDate"];
	
//	$times_reg = strtotime("$date_emp");
//	$datevac = date('d, M Y', $times_reg);
	*/
?>

                <?php /* ?><tr <?php echo ($status != "A") ? "class='table-danger'" : false ; ?> > <?php */ ?>
                <?php /* ?>
                <tr>
					<td><?php echo $Injazat_no ?></td>
					<td ><?php echo $full_name; ?></td>
					<td><?php echo $acc_no; ?></td>
                    <td><?php echo $mobile; ?></td>
                    <td><?php echo $ExpDate; ?></td>
                     <?php if($user_type == $access1){ ?>

					<td><?php echo ($status == "A") ? "<a href='./includes/update_stus_loca.php?status=C&id={$id}'><span class='badge badge-success'>Active</span></a>" : "<a href='./includes/update_stus_loca.php?status=A&id={$id}'><span class='badge badge-danger'>Closed</span></a>" ; ?>               
                    </td>
                    <?php } ?>
					<td>
					<div class="btn-group" role="group" aria-label="Edit Button">
                    <a href="./view_location.php?id=<?php echo $id ?>" class="btn btn-sm btn-dark waves-effect">
                        <i class="mdi mdi-eye-outline"></i>
                    </a>
					<a href="./edit_location.php?id=<?php echo $id ?>" class="btn btn-sm btn-custom waves-effect">
                        <i class="fa fa-edit"></i>
                    </a>
                    <?php if($user_type == $access1){ ?>
                    <a href="./includes/delete.php?tbl=cars&id=<?php echo $id_user_usr ?>" class="btn btn-sm btn-danger waves-effect">
                        <i class="dripicons-cross"></i>
                    </a>
					<?php } ?>
					</div>
					</td>
				</tr>
<?php } ?>
										</tbody>
                                        <?php */ ?>

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
		

        <!-- Google Charts js -->
        <script type="text/javascript" src="https://www.google.com/jsapi"></script>
        <!-- Init -->
        
<script type="text/javascript">

google.load("visualization", "1.1", {packages:["bar"]});
google.setOnLoadCallback(drawChart);
google.setOnLoadCallback(drawChart2);
google.setOnLoadCallback(drawChart3);
google.setOnLoadCallback(drawChart4);

function drawChart() {
    var data = google.visualization.arrayToDataTable([
         ['Dates', "Very Good", "Good", "Average", "Bad", "Very Bad"],
        <?php
                $sql_cus_q1 = mysqli_query($conDB, "SELECT
                    DATE_FORMAT(`date_reg`, '%d-%m-%Y') as Days,
                    COUNT(*) AS TOTAL,
                    COUNT(IF(`question_1`='Very Good',1,null)) AS `VeryGood`,
                    COUNT(IF(`question_1`='Good',1,null)) AS `Good`,
                    COUNT(IF(`question_1`='Average',1,null)) AS `Average`,
                    COUNT(IF(`question_1`='Bad',1,null)) AS `Bad`,
                    COUNT(IF(`question_1`='Very bad',1,null)) AS `Verybad`
                    FROM `survey`
                    /*WHERE YEAR(`date_reg`) = YEAR(NOW()) AND MONTH(`date_reg`)=MONTH(NOW())*/
                    /*WHERE `date_reg` between date_sub(now(),INTERVAL 1 WEEK) and now()*/
                    /*WHERE `date_reg` >= curdate() - INTERVAL DAYOFWEEK(curdate())+5 DAY AND `date_reg` < curdate() - INTERVAL DAYOFWEEK(curdate())-2 DAY*/
                    /*WHERE WEEK(`date_reg`) = WEEK(NOW()) - 1*/
                    /*WHERE DATE_SUB(CURDATE(), INTERVAL 7 DAY) <= DATE(`date_reg`)*/
                    GROUP BY DATE_FORMAT(`date_reg`, '%d/%m/%Y') ");
                    while ($rec = mysqli_fetch_array($sql_cus_q1)) {
                        $Days_get_q1 = $rec["Days"];
                        $TOTAL_get_q1 = $rec["TOTAL"];
                        $VeryGood_get_q1 = $rec["VeryGood"];
                        $Good_get_q1 = $rec["Good"];
                        $Average_get_q1 = $rec["Average"];
                        $Bad_get_q1 = $rec["Bad"];
                        $Verybad_get_q1 = $rec["Verybad"];
            ?>
            ['<?php echo $Days_get_q1 ?>',<?php echo $VeryGood_get_q1.",".$Good_get_q1.",".$Average_get_q1.",".$Bad_get_q1.",".$Verybad_get_q1 ?>],
            <?php } ?>
    ]);

    var options = {
            chart: { title: 'Customers Survey Count.', subtitle: 'from last 7 days', },
            fontName: 'Roboto', height: 400, fontSize: 12,
            chartArea: { left: '8%', width: '90%', height: 350},
            tooltip: {textStyle: { fontName: 'Roboto', fontSize: 12 }},
            vAxis: {titleTextStyle: {fontSize: 12,italic: false}, gridlines:{ color: '#f5f5f5', count: 10 }, minValue: 0 },
            legend: { position: 'top', alignment: 'center', textStyle: { fontSize: 13 } },
            colors: ['#4ccd6a','#68cd7f', '#94b49b', '#ff5f7f', '#ff0033'],

    };

    var chart = new google.charts.Bar(document.getElementById('How_was_the_service_provided'));

    chart.draw(data, options);
}

function drawChart2() {
    var data = google.visualization.arrayToDataTable([
         ['Dates', "Statified", "Not Statified", "Don't know"],
        <?php
                $sql_cus_q2 = mysqli_query($conDB, "SELECT
                    /*DATE_FORMAT(`date_reg`, '%Y-%m-%d') as Days,*/
                    DATE_FORMAT(`date_reg`, '%d-%m-%Y') as Days,
                    COUNT(*) AS TOTAL,
                    COUNT(IF(`question_2`='Statified',1,null)) AS `Statified`,
                    COUNT(IF(`question_2`='Not Statified',1,null)) AS `NotStatified`,
                    COUNT(IF(`question_2`='Dont know',1,null)) AS `Dontknow`
                    FROM `survey`
                    /*WHERE DATE_SUB(CURDATE(), INTERVAL 7 DAY) <= DATE(`date_reg`)*/
                    GROUP BY DATE_FORMAT(`date_reg`, '%d/%m/%Y') ");
                    while ($rec = mysqli_fetch_array($sql_cus_q2)) {
                        $Days_get_q2 = $rec["Days"];
                        $TOTAL_get_q2 = $rec["TOTAL"];
                        $Statified_get_q2 = $rec["Statified"];
                        $NotStatified_get_q2 = $rec["NotStatified"];
                        $Dontknow_get_q2 = $rec["Dontknow"];
            ?>
            ['<?php echo $Days_get_q2 ?>',<?php echo $Statified_get_q2.",".$NotStatified_get_q2.",".$Dontknow_get_q2 ?>],
            <?php } ?>
    ]);

    var options = {
            chart: { title: 'Customers Survey Count.', subtitle: 'from last 7 days', },
            fontName: 'Roboto', height: 400, fontSize: 12,
            chartArea: { left: '8%', width: '90%', height: 350},
            tooltip: {textStyle: { fontName: 'Roboto', fontSize: 12 }},
            vAxis: {titleTextStyle: {fontSize: 12,italic: false}, gridlines:{ color: '#f5f5f5', count: 10 }, minValue: 0 },
            legend: { position: 'top', alignment: 'center', textStyle: { fontSize: 13 } },
            colors: ['#4ccd6a', '#ff0033','#f4b400'],

    };

    var chart = new google.charts.Bar(document.getElementById('quality_of_the_service_provided'));

    chart.draw(data, options);
}

function drawChart3() {
    var data = google.visualization.arrayToDataTable([
         ['Dates', "Statified", "Not Statified", "Don't know"],
        <?php
                $sql_cus_q3 = mysqli_query($conDB, "SELECT
                    /*DATE_FORMAT(`date_reg`, '%Y-%m-%d') as Days,*/
                    DATE_FORMAT(`date_reg`, '%d-%m-%Y') as Days,
                    COUNT(*) AS TOTAL,
                    COUNT(IF(`question_3`='Statified',1,null)) AS `Statified`,
                    COUNT(IF(`question_3`='Not Statified',1,null)) AS `NotStatified`,
                    COUNT(IF(`question_3`='Dont know',1,null)) AS `Dontknow`
                    FROM `survey`
                    /*WHERE DATE_SUB(CURDATE(), INTERVAL 7 DAY) <= DATE(`date_reg`)*/
                    GROUP BY DATE_FORMAT(`date_reg`, '%d/%m/%Y') ");
                    while ($rec = mysqli_fetch_array($sql_cus_q3)) {
                        $Days_get_q3 = $rec["Days"];
                        $TOTAL_get_q3 = $rec["TOTAL"];
                        $Statified_get_q3 = $rec["Statified"];
                        $NotStatified_get_q3 = $rec["NotStatified"];
                        $Dontknow_get_q3 = $rec["Dontknow"];
            ?>
            ['<?php echo $Days_get_q3 ?>',<?php echo $Statified_get_q3.",".$NotStatified_get_q3.",".$Dontknow_get_q3 ?>],
            <?php } ?>
    ]);

    var options = {
            chart: { title: 'Customers Survey Count.', subtitle: 'from last 7 days', },
            fontName: 'Roboto', height: 400, fontSize: 12,
            chartArea: { left: '8%', width: '90%', height: 350},
            tooltip: {textStyle: { fontName: 'Roboto', fontSize: 12 }},
            vAxis: {titleTextStyle: {fontSize: 12,italic: false}, gridlines:{ color: '#f5f5f5', count: 10 }, minValue: 0 },
            legend: { position: 'top', alignment: 'center', textStyle: { fontSize: 13 } },
            colors: ['#4ccd6a', '#ff0033','#f4b400'],

    };

    var chart = new google.charts.Bar(document.getElementById('speed_of_completion_service'));

    chart.draw(data, options);
}

function drawChart4() {
    var data = google.visualization.arrayToDataTable([
         ['Dates', "Yes", "No"],
        <?php
                $sql_cus_q4 = mysqli_query($conDB, "SELECT
                    /*DATE_FORMAT(`date_reg`, '%Y-%m-%d') as Days,*/
                    DATE_FORMAT(`date_reg`, '%d-%m-%Y') as Days,
                    COUNT(*) AS TOTAL,
                    COUNT(IF(`question_4`='Yes',1,null)) AS `Yes`,
                    COUNT(IF(`question_4`='No',1,null)) AS `No`
                    FROM `survey`
                    /*WHERE DATE_SUB(CURDATE(), INTERVAL 7 DAY) <= DATE(`date_reg`)*/
                    GROUP BY DATE_FORMAT(`date_reg`, '%d/%m/%Y') ");
                    while ($rec = mysqli_fetch_array($sql_cus_q4)) {
                        $Days_get_q4 = $rec["Days"];
                        $TOTAL_get_q4 = $rec["TOTAL"];
                        $Yes_get_q4 = $rec["Yes"];
                        $No_get_q4 = $rec["No"];
            ?>
            ['<?php echo $Days_get_q4 ?>',<?php echo $Yes_get_q4.",".$No_get_q4 ?>],
            <?php } ?>
    ]);

    var options = {
            chart: { title: 'Customers Survey Count.', subtitle: 'from last 7 days', },
            fontName: 'Roboto', height: 400, fontSize: 12,
            chartArea: { left: '8%', width: '90%', height: 350},
            tooltip: {textStyle: { fontName: 'Roboto', fontSize: 12 }},
            vAxis: {titleTextStyle: {fontSize: 12,italic: false}, gridlines:{ color: '#f5f5f5', count: 10 }, minValue: 0 },
            legend: { position: 'top', alignment: 'center', textStyle: { fontSize: 13 } },
            colors: ['#4ccd6a', '#ff0033'],

    };

    var chart = new google.charts.Bar(document.getElementById('first_experience_with_us'));

    chart.draw(data, options);
}
</script>
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
        data.start = 0;x
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
        var exportTitle = "All Customer Surveys"
        buttonConfig.push({extend:'excel',exportOptions: {columns: [ 0, 1, 2, 3, 4, 5, 6 ]} ,title: exportTitle,className: 'btn-success', action: newexportaction});
        buttonConfig.push({extend:'pdf',exportOptions: {columns: [ 0, 1, 2, 3, 4, 5, 6 ]} ,title: exportTitle,className: 'btn-danger', action: newexportaction});
        buttonConfig.push({extend:'print' ,exportOptions: {columns: [ 0, 1, 2, 3, 4, 5, 6 ]} ,title: exportTitle,className: 'btn-dark', action: newexportaction});

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
                    'url':'SurveyCustomerAjaxfile.php'
                },

                'columns': [
                    { data: 'full_name' },
                    { data: 'email' },
                    { data: 'mobile' },
                    { data: 'gender' },
                    { data: 'location' },
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