<?php
	require_once __DIR__ . '/includes/db.php';
	require_once __DIR__ . '/includes/session_check.php';
	$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
	if(mysqli_num_rows($query) == 1){
	include("./includes/avatar_select.php");
		
	if(isset($_POST['submit'])){

        $category_id_up = $_POST['category_id'];
        $price_level_up = $_POST['price_level'];
        $name_eng_up = $_POST['name_eng'];
        $name_ar_up = $_POST['name_ar'];
        $big_price_up = $_POST['big_price'];
        $small_price_up = $_POST['small_price'];
        $big_cal_up = $_POST['big_cal'];
        $small_cal_up = $_POST['small_cal'];


        $uploadDir = "./QR_MENU/images/item_img/";
        $temp = explode(".", $_FILES["file"]["name"]);
        $tmp_name = $_FILES['file']['tmp_name'];
        $newfilename = round(microtime(true)) . '.' . end($temp);
        // $fileName = basename($_FILES['file']['name']);
        $uploadFilePath = $uploadDir.$newfilename;

	if($category_id_up){
        move_uploaded_file($tmp_name, $uploadFilePath);
		$query="INSERT INTO `menu_item` (`category_id`,`price_level`,`name_eng`, `name_ar`,`big_price`,`small_price`,`big_cal`,`small_cal`,`image`) VALUES ('".$category_id_up."','".$price_level_up."','".$name_eng_up."','".$name_ar_up."','".$big_price_up."','".$small_price_up."','".$big_cal_up."','".$small_cal_up."','".$newfilename."')";
		mysqli_query($conDB, $query);

		/************log************/
		//mysqli_query($conDB, "INSERT INTO `activity_log` (`user_editor`,`page`,`pg_id`,`reg_date`) VALUES ('".$_COOKIE['user']."','".$pgname."','".$_POST['drv_name']."','".date("c")."')") or die (mysqli_error());
		/************log************/
		$msg = "<div class=\"alert alert-success bg-success text-white border-0\" role=\"alert\">Add Seccssfully!</div>
		";		
		header( "refresh:1 ; url= ./all_menu_item.php" );
	} else {
		$msg = "<div class=\"alert alert-danger bg-danger text-white border-0\" role=\"alert\">Please fill out the form!</div>";
	}

	
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
                    
						
				<div class="row">
				<div class="col-md-12">
					<div class="card-box" style="height: 300px;">
						<h4 class="m-t-0 header-title">Register New Item</h4>
						<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data">
							<?php echo $msg ?>
							<div class="form-row">
                                <div class="form-group col-md-3">
                                    <label for="name_eng" class="col-form-label">Name in English</label>
                                    <input type="text" name="name_eng" parsley-trigger="change" class="form-control" id="name_eng" autocomplete="off" required="" >
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="name_ar" class="col-form-label">Name in Arabic</label>
                                    <input type="text" name="name_ar" parsley-trigger="change" class="form-control" id="name_ar" autocomplete="off" required="" >
                                </div>
								<div class="form-group col-md-2">
									<label for="big_price" class="col-form-label">Larg Price</label>
                                    <input type="text" name="big_price" parsley-trigger="change" class="form-control " id="big_price" autocomplete="off">
								</div>
                                <div class="form-group col-md-2">
                                    <label for="small_price" class="col-form-label">Small Price</label>
                                    <input type="text" name="small_price" parsley-trigger="change" class="form-control " id="small_price" autocomplete="off">
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="big_cal" class="col-form-label">Larg Calories</label>
                                    <input type="text" name="big_cal" parsley-trigger="change" class="form-control " id="big_cal" autocomplete="off">
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="small_cal" class="col-form-label">Small Calories</label>
                                    <input type="text" name="small_cal"  parsley-trigger="change" class="form-control " id="small_cal" autocomplete="off">
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="price_level" class="col-form-label">Select Price Type</label>
                                    <select class="form-control" name="price_level" required="">
                                        <option value="">Select</option>
                                        <option value="1">Drive Thru</option>
                                        <option value="2">University</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="category_id" class="col-form-label">Select Category</label>
                                    <select class="form-control" name="category_id" required="">
                                            <option value="">Select</option>
                                        <?php
                                            $query_sectin_nme = mysqli_query($conDB, "SELECT * FROM `menu_category` ORDER BY `name_eng` REGEXP '^[^A-Za-z]' ASC, `name_eng`");
                                                while($rec = mysqli_fetch_assoc($query_sectin_nme)){
                                        ?>
                                            <option value="<?php echo $rec["id"] ?>"><?php echo str_replace(' ', '', $rec["name_eng"]) ?></option>
                                        <?php } ?>
                                        </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="category_id" class="col-form-label">Select Item Image</label>
                                    <input type="file" name="file" class="filestyle" data-btnClass="btn-primary" required accept="image/*">
                                </div>
							</div>
							<div class="btn-group" role="group" aria-label="Edit Button">
							<a href="all_menu_item.php" class="btn btn-dark"><i class="fa fa-angle-double-left"></i> Back</a>
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