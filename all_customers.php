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
        <title><?php echo $site_title ?> - All Customers</title>
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
	<div class="col-12">
		<div class="card-box table-responsive">
            <!-- <a href="add_location.php" class="btn btn-primary waves-effect"><i class="mdi mdi-settings"></i> Add New Location</a> -->
			<h4 class="m-t-0 header-title">All Registerd Customers</h4>
<table id="customers_tbl" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
	<thead>
		<tr>
			<th>Customer Name</th>
			<th>Injazat No</th>
			<th>Acc. No.</th>
            <th>Mobile</th>
            <th>IssueDate</th>
            <th>ExpDate</th>
			<th>Status</th>
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

<div class="modal fade" id="cardPrintModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div id="print_style"></div>
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <section class="contact-form">
                <form class="contact-input">
                    <div class="modal-header">
                        <h4 class="modal-title" id="myModalLabel17">Print Customer Card</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" id="printBody">
                        <table class="cardbody card">
                            <tr>
                                <td id="pfull_name" colspan="3" style="font-size: 13px !important;"></td>
                            </tr>
                            <tr>
                                <td></td>
                                <td id="pacc_no"></td>
                            </tr>
                            <tr>
                                <td></td>
                                <td id="pissdate"></td>
                                <td id="pexpdate" class="exp"></td>
                            </tr>
                        </table>
                    </div>
                        <div id="response"></div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-info" onclick="printdiv('printBody')" class="printbtn" data-dismiss="modal">Print</button>
                        <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</div>

<?php /* ?>
<div class="modal fade" id="addCustomerModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <section class="contact-form">
                <form action="" id="add_customer_form">
                    <div class="modal-header">
                        <h4 class="modal-title" id="myModalLabel17">Add Category</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="full_name" class="col-form-label">Customer Name</label>
                                <input type="text" name="full_name" parsley-trigger="change" required="" class="form-control" id="full_name" autocomplete="off" style="text-transform: uppercase !important;" >
                            </div>
                            <div class="form-group col-md-4">
                                <label for="injazat_no" class="col-form-label">Injazat No.</label>
                                <input type="text" name="injazat_no" data-v-max="999999" data-v-min="0" parsley-trigger="change" required="" class="form-control autonumber" id="injazat_no" autocomplete="off">
                            </div>
                            <div class="form-group col-md-4">
                                <label for="mobile" class="col-form-label">Mobile No.</label>
                                <input type="text" name="mobile" data-mask="0599999999" parsley-trigger="change" required="" class="form-control" id="mobile" autocomplete="off">
                            </div>
                            <div class="form-group col-md-4">
                                <label for="acc_no" class="col-form-label">Account No.</label>
                                <input type="text" name="acc_no" parsley-trigger="change" required="" class="form-control" id="acc_no" autocomplete="off" style="text-transform: uppercase !important;" >
                            </div>
                            <div class="form-group col-md-4">
                                <label for="passport_exp" class="col-form-label">Card Expire</label>
                                <input type="text" name="card_exp" parsley-trigger="change" class="form-control" id="passport_exp" autocomplete="off">
                            </div>
                            <div class="form-group col-md-4">
                                <label for="passport_exp" class="col-form-label">For Shop</label>
                                <select class="form-control" name="location">
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
                        <div id="response"></div>
                    <div class="modal-footer">
                        <!-- <input type="hidden" id="idmud" name="id"> -->
                        <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                        <button type="button" name="submit_register" class="btn btn-info" id="submitForm">Add Customer</button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</div>
<?php */ ?>

<div class="modal fade" id="cardUpdateModalX" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <section class="contact-form">
                <form class="contact-input">
                    <div class="modal-header">
                        <h4 class="modal-title" id="myModalLabel17">Customer Card info</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-row">
                        <div class="form-group col-md-6">
                            <a href="javascript:void:(0);" class="btn btn-dark waves-effect btn-block" data-toggle='modal' data-target='#addVipCardModal' data-backdrop='static' data-dismiss="modal"><i class="mdi mdi-credit-card-plus"></i> Add New Card</a>
                        </div>
                        <div class="form-group col-md-6">
                            <div id="card_upd_div" style="display: none !important">
                                <a href="javascript:void(0);" data-toggle='modal' data-target='#updVipCardModal' data-backdrop='static' data-dismiss="modal" class="btn btn-primary waves-effect btn-block">
                                    <i class="mdi mdi-credit-card-multiple"></i> Update Card
                                </a>
                            </div>
                        </div>
                    </div>
                        <div id="response"></div>
                    <!-- <input type="text" id="cid" name="cid"> -->
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</div>

<?php /* ?>
<div class="modal fade" id="addVipCardModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <section class="contact-form">
                <form class="contact-input" id="submitAddCustVipForm">
                    <div class="modal-header">
                        <h4 class="modal-title" id="myModalLabel17">Add VIP Customer Card.</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label for="injazat_no" class="col-form-label">New Injazat No.</label>
                                <input type="text" name="injazat_no_add" data-v-max="999999" data-v-min="0" parsley-trigger="change" class="form-control autonumber" required="" autocomplete="off">
                            </div>
                            <div class="form-group col-md-3">
                                <label for="acc_no" class="col-form-label">Account No.</label>
                                <input type="text" name="acc_no_add" parsley-trigger="change" id="cstacc_no" class="form-control" required="" autocomplete="off">
                            </div>
                            <div class="form-group col-md-3">
                                <label for="card_exp" class="col-form-label">Card Expire</label>
                                <input type="text" name="card_exp_add" parsley-trigger="change" class="form-control card_exp" required=""autocomplete="off">
                            </div>
                            <div class="form-group col-md-3">
                                <label for="sectin_nme" class="col-form-label">For Shop</label>
                                <select class="form-control" name="sectinName_add" required="">
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
                        <div id="responseAddVIP"></div>
                    <input type="hidden" id="cstid" name="id">
                    <!-- <input type="hidden" id="injazatno" name="injazatno"> -->
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                        <button type="button" id="submitAddVip" class="btn btn-primary">Register VIP</button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</div>

<div class="modal fade" id="updVipCardModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <section class="contact-form">
                <form class="contact-input" id="submitUpdCustVipForm">
                    <div class="modal-header">
                        <h4 class="modal-title" id="myModalLabel17">Update VIP Customer Card.</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="card_exp" class="col-form-label">Card Expire</label>
                                <input type="text" name="card_exp_upd" parsley-trigger="change" class="form-control card_exp" required=""autocomplete="off">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="sectin_nme" class="col-form-label">For Shop</label>
                                <select class="form-control" name="sectinName_upd" required="">
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
                        <div id="responseUpdVIP"></div>
                    <input type="hidden" id="updcstid" name="id">
                    <!-- <input type="hidden" id="injazatno" name="injazatno"> -->
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                        <button type="button" id="submitUpdVip" class="btn btn-primary">Update Card</button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</div>
<?php */ ?>

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
		<!-- <script src="assets/pages/jquery.form-pickers.init.js"></script> -->
		
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

        <script src="./plugins/select2/js/select2.min.js" type="text/javascript"></script>
        <script src="./plugins/bootstrap-select/js/bootstrap-select.js" type="text/javascript"></script>

        <!-- Selection table -->
        <script src="./plugins/datatables/dataTables.select.min.js"></script>
		
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.0/jquery.validate.js"></script>
        <script src="./assets/js/jquery.custom.validation.js"></script>

        <!-- <link href="./plugins/sweet-alert/sweetalert2.min.css" rel="stylesheet" type="text/css" />
        <script src="./plugins/sweet-alert/sweetalert2.min.js"></script>
        <script src="assets/pages/jquery.sweet-alert.init.js"></script> -->

        <!-- App js -->
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js"></script>

        <script type="text/javascript">
            function printdiv(elem)
                {
                var mywindow = window.open('', 'PRINT', 'height=400,width=600');
                mywindow.document.write('<html><head>');
                mywindow.document.write("<link href=\"./assets/css/cust_print_style.css\" rel=\"stylesheet\" />")
                mywindow.document.write('</head><body >');
                mywindow.document.write(document.getElementById('printBody').innerHTML);
                mywindow.document.write('</body></html>');
                mywindow.document.close(); // necessary for IE >= 10
                mywindow.focus(); // necessary for IE >= 10*/
                setTimeout(function () {
                    mywindow.print();
                    mywindow.close();
                }, 1000)
                return true;
                }
        </script>

<script type="text/javascript">

    jQuery(document).ready(function () {
        $('.card_exp').datepicker({
            format: "dd-mm-yyyy",
            autoclose: true,
            todayHighlight: true,
            startDate: '+2y',
        });
    });

    $(document).on('click', '.cardUpdateAttrX', function (e) {
        e.preventDefault();
            var id          = $(this).data('id');
            var injazat_no  = $(this).data('injazat_no');
            $('#cstid').val($(this).data('id'));
            $('#updcstid').val($(this).data('id'));
            $('#cstacc_no').val($(this).data('acc_no'));
            if (injazat_no !== 0) {
                $("#card_upd_div").show();
            } else{
                $("#card_upd_div").hide();
            }
    });

    $(document).on('click', '.cardPrintAttr', function (e) {
        e.preventDefault();
            var id          = $(this).data('id');
            $('#pfull_name').html($(this).data('full_name'));
            $('#pacc_no').html($(this).data('acc_no'));
            $('#pissdate').html($(this).data('created_at'));
            $('#pexpdate').html($(this).data('exp_date'));
    });

    /*$(document).on('click', '.cardUpdateAttr', function (e) {
        e.preventDefault();
            var id          = $(this).data('id');
            var injazat_no  = $(this).data('injazat_no');
            $('#cstid').val($(this).data('id'));
            $('#updcstid').val($(this).data('id'));
    });*/

</script>

<script type="text/javascript">
    // Add New Customer
    $(document).ready(function() {
        // Edit user information START
        $("#submitForm").click(function() {
            // var id = $('input[name=id]').val();
            var fullname = $('input[name=fullname]').val();
            var injazat_no  = $('input[name=injazat_no]').val();
            if (fullname == "" || injazat_no == "" ) {
              $("#response").fadeIn();
              $("#response").html("<div class='alert alert-danger'><strong>Error!</strong> All fields are required.</div>");
            } else {
              // $("#response").html($('#add_customer_form').serialize());
              // var formData = {id: id, fullname: fullname, username: username };
              // console.log(formData);
              $.ajax({
                url: "./includes/ajaxFile/add_customer.php",
                type: "POST",
                data: $('#add_customer_form').serialize(),
                dataType: "json",
                // data: formData,
                success: function(res){
                  setTimeout(function () { 
                      // swal('Updated!','This user has been update successfully.', 'success')
                    Swal.fire({
                        title:res.title,
                        text:res.message,
                        type:res.type,
                        allowOutsideClick:false
                    }).then(function(isConfirm){(isConfirm)? [$('#addCustomerModal').modal('hide'), $('#customers_tbl').DataTable().ajax.reload()] :"" });
                  },1);
                }
              });
            }
        });
    });
    // Add Customer VIP Card
    $(document).ready(function() {
        // Edit user information START
        $("#submitAddVip").click(function() {
            var id = $('input[name=id]').val();
            // var id          = $(this).data('id');
            // $('#injazatno').val($(this).data('injazatno'));
            var injazat_no  = $('input[name=injazat_no_add]').val();
            var acc_no      = $('input[name=acc_no_add]').val();
            var card_exp    = $('input[name=card_exp_add]').val();

            if (injazat_no == "" ||  acc_no == "" ||  card_exp == "") {
              $("#responseAddVIP").fadeIn();
              $("#responseAddVIP").html("<div class='alert alert-danger'><strong>Error!</strong> All fields are required.</div>");
            } else {
              // $("#responseAddVIP").html($('#submitAddCustVipForm').serialize());
              // var formData = {id: id, fullname: fullname, username: username };
              // console.log(id);
              $.ajax({
                url: "./includes/ajaxFile/add_cust_vip_card.php",
                type: "POST",
                data: $('#submitAddCustVipForm').serialize(),
                dataType: "json",
                // data: formData,
                success: function(res){
                  setTimeout(function () { 
                      // swal('Updated!','This user has been update successfully.', 'success')
                    Swal.fire({
                        title:res.title,
                        text:res.message,
                        type:res.type,
                        allowOutsideClick:false
                    }).then(function(isConfirm){(isConfirm)? [
                        $('#addVipCardModal').modal('hide'),
                        window.location.href = './view_customer.php?id='+id
                        ] :"" });
                  },1);
                }
              });
            }
        });
    });
    // Update Customer VIP Card
    $(document).ready(function() {
        // Edit user information START
        $("#submitUpdVip").click(function() {
            var id = $('input[name=id]').val();
            // var id          = $(this).data('id');
            // $('#injazatno').val($(this).data('injazatno'));
            var card_exp = $('input[name=card_exp_upd]').val();
            if (card_exp == "") {
              $("#responseUpdVIP").fadeIn();
              $("#responseUpdVIP").html("<div class='alert alert-danger'><strong>Error!</strong> All fields are required.</div>");
            } else {
              // $("#responseUpdVIP").html($('#submitUpdCustVipForm').serialize());
              // var formData = {id: id, fullname: fullname, username: username };
              // console.log(id);
              $.ajax({
                url: "./includes/ajaxFile/upd_cust_vip_card.php",
                type: "POST",
                data: $('#submitUpdCustVipForm').serialize(),
                dataType: "json",
                // data: formData,
                success: function(res){
                  setTimeout(function () { 
                      // swal('Updated!','This user has been update successfully.', 'success')
                    Swal.fire({
                        title:res.title,
                        text:res.message,
                        type:res.type,
                        allowOutsideClick:false
                    }).then(function(isConfirm){(isConfirm)? [
                        $('#UpdVipCardModal').modal('hide'),
                        // $('#cardPrintModal').modal('show')
                        window.location.href = './view_customer.php?id='+id
                        ] :"" });
                  },1);
                }
              });
            }
        });
    });
</script>

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
        buttonConfig.push({text: '<i class="fa fa-plus"></i> Add Customer', action: function ( e, dt, button, config ) { addCustomerAtter() } ,className: 'btn-info'});
        // buttonConfig.push({text: '<i class="fa fa-plus"></i> Add Customer', action: function ( e, dt, button, config ) { $('#addCustomerModal').modal({backdrop: 'static', show: true}) } ,className: 'btn-info'});
        /*buttonConfig.push({text: '<i class="fa fa-plus"></i> Add Customer', action: function ( e, dt, button, config ) {window.location = './add_customer.php' } ,className: 'btn-info'});*/

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
                    'url':'./includes/ajaxFile/CustomerAjaxTbl.php'
                },

                'columns': [
                    { data: 'full_name' },
                    { data: 'injazat_no' },
                    { data: 'acc_no' },
                    { data: 'mobile' },
                    { data: 'created_at' },
                    { data: 'exp_date' },
                    { data: 'status' },
                    { data: 'action' },
                ],

            });
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