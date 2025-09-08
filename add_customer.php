<?php
	require_once __DIR__ . '/includes/db.php';
	require_once __DIR__ . '/includes/session_check.php';
	$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
	if(mysqli_num_rows($query) == 1){
	include("./includes/avatar_select.php");


	if(isset($_POST['submit'])){

        $injazat_no_up = $_POST['injazat_no'];
        $acc_no_up = strtoupper($_POST['acc_no']);
		$full_name_up = strtoupper($_POST['full_name']);
        $mobile_up = $_POST['mobile'];
        $card_exp_up = $_POST['card_exp'];
        $status_up = "A";
        $sectin_nme_up = $_POST['sectin_nme'];
		$date_reg_up = date("c");

        $injazat_no_up = str_replace(',', '', $injazat_no_up); 

	if($card_exp_up){
        $newquery="INSERT INTO `customer` (`full_name`,`injazat_no`, `exp_date`,`status`, `date_reg`, `sectin_nme`, `mobile`, `acc_no`) VALUES ('".$full_name_up."', '".$injazat_no_up."', '".$card_exp_up."', '".$status_up."', '".$date_reg_up."', '".$sectin_nme_up."', '".$mobile_up."', '".$acc_no_up."')";
		mysqli_query($conDB, $newquery);

        $lastentryquery = mysqli_query($conDB, "SELECT * FROM `customer` ORDER BY `id` DESC LIMIT 1");
            while($rec = mysqli_fetch_assoc($lastentryquery)){
                 $lastentryid = $rec['id'];
            }
            $id_cust = $lastentryid;

		$query="INSERT INTO `cust_card_update` (`cust_no`,`injazat_no`, `exp_date`,`status`, `date_reg`, `sectin_nme`) VALUES ('".$id_cust."', '".$injazat_no_up."', '".$card_exp_up."', '".$status_up."', '".$date_reg_up."', '".$sectin_nme_up."')";
        mysqli_query($conDB, $query);


        // $upd_stat = "UPDATE `cust_card_update` SET `status`='I' WHERE `id`='".$upd_status_lst."' ";
        // mysqli_query($conDB, $upd_stat) or die (mysqli_error());
        // $upd_inj_cst = "UPDATE `customer` SET `injazat_no`='".$injazat_no_up."', `exp_date`='".$card_exp_up."' WHERE `id`='".$id_cust."' ";
        // mysqli_query($conDB, $upd_inj_cst) or die (mysqli_error());

		/************log************/
		//mysqli_query($conDB, "INSERT INTO `activity_log` (`user_editor`,`page`,`pg_id`,`reg_date`) VALUES ('".$_COOKIE['user']."','".$pgname."','".$_POST['drv_name']."','".date("c")."')") or die (mysqli_error());
		/************log************/
		$msg = "<div class=\"alert alert-success bg-success text-white border-0\" role=\"alert\">Add Seccssfully!</div>
		";		
		header( "refresh:2 ; url= ./view_customer.php?id=".$_GET['id']." " );
	} else {
		$msg = "<div class=\"alert alert-danger bg-danger text-white border-0\" role=\"alert\">Please fill out the form!</div>";
	}

	
}


?>
<!doctype html>
<html lang="en">

    <head>
        <meta charset="utf-8" />
        <title><?php echo $site_title ?> - Add Customer</title>
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
		<link href="./plugins/bootstrap-tagsinput/css/bootstrap-tagsinput.css" rel="stylesheet" />
        <link href="./plugins/bootstrap-select/css/bootstrap-select.min.css" rel="stylesheet" />
        <link href="./plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
        <link rel="stylesheet" href="./plugins/switchery/switchery.min.css" />


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
				<div class="col-md-12">
					<div class="card-box" style="height: 300px;">
						<h4 class="m-t-0 header-title">Register New Customer</h4>
						<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
							<?php echo $msg ?>
							<div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="full_name" class="col-form-label">Customer Name</label>
                                    <input type="text" name="full_name" parsley-trigger="change" class="form-control" required="" id="full_name" autocomplete="off" style="text-transform: uppercase !important;" >
                                </div>
								<div class="form-group col-md-4">
									<label for="injazat_no" class="col-form-label">Injazat No.</label>
                                    <input type="text" name="injazat_no" data-v-max="999999" data-v-min="0" parsley-trigger="change" class="form-control  required=""autonumber" id="injazat_no" autocomplete="off">
								</div>
                                <div class="form-group col-md-4">
                                    <label for="mobile" class="col-form-label">Mobile No.</label>
                                    <input type="text" name="mobile" data-mask="0599999999" parsley-trigger="change" class="form-control" required="" id="mobile" autocomplete="off">
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="acc_no" class="col-form-label">Account No.</label>
                                    <input type="text" name="acc_no" parsley-trigger="change" class="form-control" required="" id="acc_no" autocomplete="off" style="text-transform: uppercase !important;" >
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="passport_exp" class="col-form-label">Card Expire</label>
                                    <input type="text" name="card_exp" parsley-trigger="change" class="form-control" required="" id="passport_exp" autocomplete="off">
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="passport_exp" class="col-form-label">For Shop</label>
                                    <select class="form-control" required="" name="sectin_nme">
                                            <option value="">Select</option>
                                        <?php
                                            $query_sectin_nme = mysqli_query($conDB, "SELECT * FROM `section` ORDER BY `section_name` REGEXP '^[^A-Za-z]' ASC, section_name");
                                            while($rec = mysqli_fetch_assoc($query_sectin_nme)){
                                                $sectin_nme = $rec["section_name"];
                                        ?>
                                            <option value="<?php echo $sectin_nme ?>"><?php echo str_replace(' ', '', $sectin_nme) ?></option>
                                        <?php } ?>
                                        </select>
                                </div>

							</div>
							<div class="btn-group" role="group" aria-label="Edit Button">
							<a href="./view_customer.php?id=<?php echo $_GET['id'] ?>" class="btn btn-dark"><i class="fa fa-angle-double-left"></i> Back</a>
							<button type="submit" name="submit" class="btn btn-primary"><i class="mdi mdi-gender-transgender"></i> Register</button>
							</div>
						</form>
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

        <script src="./plugins/select2/js/select2.min.js" type="text/javascript"></script>
        <script src="./plugins/bootstrap-select/js/bootstrap-select.js" type="text/javascript"></script>


        <script src="./plugins/moment/moment.js"></script>
        <script src="./plugins/bootstrap-timepicker/bootstrap-timepicker.js"></script>
        
        <script src="./plugins/bootstrap-timepicker/hijri/bootstrap-hijri-datetimepicker.js"></script>
        <script src="./plugins/bootstrap-timepicker/hijri/bootstrap-hijri-datetimepicker.min.js"></script>
        <script src="./plugins/bootstrap-timepicker/hijri/bootstrap-hijri-datetimepickermin.js"></script>
        
        <script src="./plugins/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js"></script>
        <script src="./plugins/clockpicker/js/bootstrap-clockpicker.min.js"></script>
        <script src="./plugins/bootstrap-daterangepicker/daterangepicker.js"></script>
        <script src="./plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>
        
        <script src="./plugins/bootstrap-filestyle/js/bootstrap-filestyle.min.js" type="text/javascript"></script>

        <!-- App js -->
        <script src="assets/pages/jquery.form-pickers.init.js"></script>
        
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js"></script>
        
        <script defer src="./plugins/imask.js"></script>
        
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.0/jquery.validate.js"></script>
        <script src="./assets/js/jquery.custom.validation.js"></script>
<script type="text/javascript">
$(function() {
 
$("#vac_period").bind("change", function() {
     $.ajax({
         type: "GET", 
         url: "./includes/ContractPeriodSelect.php",
         data: "vac_period="+$("#vac_period").val(),
         success: function(val) {
             $("#vacation_days").val(val);
         }
     });
 });

$("#department").bind("change", function() {
     $.ajax({
         type: "GET", 
         url: "./includes/DepartmentSelect.php",
         data: "department="+$("#department").val(),
         success: function(html) {
             $("#sectin_nme").html(html);
         }
     });
 });
 
});
</script>
        <script type="text/javascript">
            $(document).ready(function() {
                $('form').parsley();



//var dateMask = IMask(
//  document.getElementById('iqama_exp'),{
//  mask: Date,  // enable date mask
//  // other options are optional
//  pattern: 'd{.}`m{.}`Y',  // Pattern mask with defined blocks, default is 'd{.}`m{.}`Y'
//  // you can provide your own blocks definitions, default blocks for date mask are:
//  blocks: {
//    d: {
//      mask: IMask.MaskedRange,
//      from: 1,
//      to: 31,
//      maxLength: 2,
//    },
//    m: {
//      mask: IMask.MaskedRange,
//      from: 1,
//      to: 12,
//      maxLength: 2,
//    },
//    Y: {
//      mask: IMask.MaskedRange,
//      from: 1299,
//      to: 1699,
//    }
//  },
//  autofix: true,  // defaults to `false`
//  // also Pattern options can be set
//  lazy: false,
//  // and other common options
//  overwrite: true  // defaults to `false`
//});
//
//var dateMask = IMask(
//  document.getElementById('passport_exp'),{
//  mask: Date,  // enable date mask
//  // other options are optional
//  pattern: 'd{.}`m{.}`Y',  // Pattern mask with defined blocks, default is 'd{.}`m{.}`Y'
//  // you can provide your own blocks definitions, default blocks for date mask are:
//  blocks: {
//    d: {
//      mask: IMask.MaskedRange,
//      from: 1,
//      to: 31,
//      maxLength: 2,
//    },
//    m: {
//      mask: IMask.MaskedRange,
//      from: 1,
//      to: 12,
//      maxLength: 2,
//    },
//    Y: {
//      mask: IMask.MaskedRange,
//      from: 2015,
//      to: 3000,
//    }
//  },
//  autofix: true,  // defaults to `false`
//  // also Pattern options can be set
//  lazy: false,
//  // and other common options
//  overwrite: true  // defaults to `false`
//});
//
//var dateMask = IMask(
//  document.getElementById('insurance_exp'),{
//  mask: Date,  // enable date mask
//  // other options are optional
//  pattern: 'd{.}`m{.}`Y',  // Pattern mask with defined blocks, default is 'd{.}`m{.}`Y'
//  // you can provide your own blocks definitions, default blocks for date mask are:
//  blocks: {
//    d: {
//      mask: IMask.MaskedRange,
//      from: 1,
//      to: 31,
//      maxLength: 2,
//    },
//    m: {
//      mask: IMask.MaskedRange,
//      from: 1,
//      to: 12,
//      maxLength: 2,
//    },
//    Y: {
//      mask: IMask.MaskedRange,
//      from: 2015,
//      to: 3000,
//    }
//  },
//  autofix: true,  // defaults to `false`
//  // also Pattern options can be set
//  lazy: false,
//  // and other common options
//  overwrite: true  // defaults to `false`
//});   

                
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
    

//window.onload = function() {
//    var src = document.getElementById("iqama_exp_hijri"),
//        dst = document.getElementById("iqama_exp_g");
//    src.addEventListener('input', function() {
//        dst.value = src.value;
//    });
//};
            
/*
$(document).ready(function(){
$('#iqama_exp_hijri').blur( function(){
     $('#iqama_exp_g').val($(this).val());
});
});     
*/
            
//$(function () {
//    var $src = $('#iqama_exp_hijri'),
//        $dst = $('#iqama_exp_g');
//    $src.on('input', function () {
//        $dst.val($src.val());
//    });
//});

</script>

    </body>
</html>
<?php } ?>