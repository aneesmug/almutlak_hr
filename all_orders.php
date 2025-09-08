<?php
	require_once __DIR__ . '/includes/db.php';
	require_once __DIR__ . '/includes/session_check.php';
	$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."' ");
	if(mysqli_num_rows($query) == 1){
	include("./includes/avatar_select.php");
?>
<!doctype html>
<html lang="en">

    <head>
        <meta charset="utf-8" />
        <title><?php echo $site_title ?> - All Orders</title>
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
            .swal2-html-container{
                padding: 10px !important;
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
			<h4 class="m-t-0 header-title">All Orders</h4>
<table id="orders_tbl" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
	<thead>
		<tr>
			<th>Customer Name</th>
			<th>Order No.</th>
			<th>Qty</th>
            <th>Mobile</th>
            <th>City</th>
            <th>Order Date</th>
			<th>Status</th>
			<th width="60">Action</th>
		</tr>
	</thead>
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

        <!-- Selection table -->
        <script src="./plugins/datatables/dataTables.select.min.js"></script>
		
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.0/jquery.validate.js"></script>
        <script src="./assets/js/jquery.custom.validation.js"></script>

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

    $(document).on('click', '.cardUpdateAttr', function (e) {
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
                    swal({
                        title:res.title,
                        text:res.message,
                        type:res.type,
                        allowOutsideClick:false
                    }).then(function(isConfirm){(isConfirm)? [$('#addCustomerModal').modal('hide'), $('#orders_tbl').DataTable().ajax.reload()] :"" });
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
                    swal({
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
                    swal({
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
        /*buttonConfig.push({text: '<i class="fa fa-plus"></i> Add Customer', action: function ( e, dt, button, config ) {$('#addCustomerModal').modal({backdrop: 'static', show: true}) } ,className: 'btn-info'});*/
        /*buttonConfig.push({text: '<i class="fa fa-plus"></i> Add Customer', action: function ( e, dt, button, config ) {window.location = './add_customer.php' } ,className: 'btn-info'});*/

            $('#orders_tbl').DataTable({
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
                    'url':'./includes/ajaxFile/OrderAjaxfile.php'
                },
                'columns': [
                    { data: 'fullname' },
                    { data: 'order_id' },
                    { data: 'qty' },
                    { data: 'mobile' },
                    { data: 'city' },
                    { data: 'odrDate' },
                    { data: 'statusOrder' },
                    { data: 'action' },
                ],

            });
        });

    $(document).on('click', '.deleteOrder', function (e) {
        e.preventDefault();
        var itemId = $(this).data('order_id');
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!',
            showLoaderOnConfirm: true,
            preConfirm: function() {
                return new Promise(function(resolve) {
                    $.ajax({
                        url: "./includes/ajaxFile/deleteOrderAjax.php",
                        type: 'POST',
                        data: {id:itemId},
                        cache: false,
                        dataType: "json",
                    })
                    .done(function(response){
                        // alert(response.title);
                        Swal.fire({
                            title:response.title,text:response.message,icon:response.type,allowOutsideClick:false,confirmButtonClass: 'btn btn-lg',buttonsStyling: false,
                        }).then(function(isConfirm){(isConfirm)?location.reload():""});
                    })
                    .fail(function(){
                        // Swal.fire(response.title, response.message, response.type);
                        Swal.fire("Error", "There's some error", "error");
                    });
                });
            },
            allowOutsideClick: false
        })
    });
    $(document).on('click', '.updateOrder', function (e) {
        e.preventDefault();
        var order_id    = $(this).data('order_id');
        var uid         = $(this).data('uid');
        var status      = $(this).data('status');
        
        Swal.fire({
            title: 'Update order Status',
            // text: "You won't be able to revert this!",
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, Update!',
            showLoaderOnConfirm: true,
            allowOutsideClick: false,
            showCancelButton: true,
            html: 
                '<form id="submitEditUserForm">'+
                    '<div class="form-row" style="text-align: left !important;">'+
                        '<div class="form-group col-md-12">'+
                            '<label for="name">Select status option<span class="text-danger">*</span></label>'+
                            '<select name="updateOrderStatus" id="uStatus" class="custom-select">'+
                                '<option value="">Select status</option>'+
                                '<option value="preparing">Preparing Order</option>'+
                                '<option value="u_shipping">Under Shipping</option>'+
                                '<option value="complete">Completed</option>'+
                                '<option value="cancel">Cancel</option>'+
                            '</select><br />'+
                        '</div>'+
                        '<div class="form-group col-md-12" id="dateDIV">'+
                            '<label for="uDateD">Date for Delivery</label>'+
                            '<input type="text" name="note" placeholder="Plase select date for delivery" class="form-control delivery_date" />'+   
                        '</div>'+
                        '<div class="form-group col-md-12" id="noteDIV">'+
                            '<label for="uNote">Receiver Name</label>'+
                            '<input name="note" placeholder="Plase enter receiver name." class="form-control" id="uNote" />'+   
                        '</div>'+
                    '</div>'+
                '</form>',
            willOpen: function(el) {
                $('#uStatus option[value="'+status+'"]').prop("selected", "selected");
                $("#noteDIV").hide();
                $("#dateDIV").hide();
                jQuery('.delivery_date').datepicker({
                    format: "yyyy-mm-dd",
                    todayHighlight: true,
                    startDate: '+0d',
                    toggleActive: true,
                });

                $('#uStatus').click(function(){      
                    var value = $(this).val();
                    if(value=='complete') {
                        $("#noteDIV").show();
                        $("#uNote").attr('name', 'note');
                        $(".delivery_date").removeAttr('name');
                        $("#dateDIV").hide();
                    } else if(value=='u_shipping'){
                        $("#dateDIV").show();
                        $(".delivery_date").attr('name', 'note');
                        $("#uNote").removeAttr('name');
                        $("#noteDIV").hide();
                    } else {
                        // $("#permit_no").attr('required', '');
                        $("#noteDIV").hide();
                        $("#dateDIV").hide();
                    }

                });

            },
            preConfirm: function() {
                const uStatus = $('#uStatus').val();
                const uNote = $('input[name=note]').val();
                const uDate = $('.delivery_date').val();
                if (!uStatus) {
                    Swal.showValidationMessage(`Please select status option`)
                } else if (uStatus == "complete" && uNote == "") {
                    Swal.showValidationMessage(`Please enter notes.`)
                } else if (status == uStatus) {
                    Swal.showValidationMessage(`Already updated.`)
                } else if (uStatus == 'u_shipping' && uDate == "") {
                    Swal.showValidationMessage(`Please select date for delivery.`)
                }
                return new Promise(function(reject, resolve) {
                    if( uStatus == "" ){
                        reject("Please fill all mendatory(*) fields first!");
                        return false;
                    } else if (uStatus == "complete" && uNote == "") {
                        reject("Please enter package receiver name.");
                        return false;
                    } else if (status == uStatus) {
                        reject("Already updated.");
                        return false;
                    } else if (uStatus == 'u_shipping' && uDate == "") {
                        reject("Please select date for delivery.");
                        return false;
                    }
                    $.ajax({
                        url: "./includes/ajaxFile/updateStatusOrderAjax.php",
                        type: 'POST',
                        data: {
                            id:order_id,
                            status:uStatus,
                            emp_name:'<?=$userwel?>',
                            uid:uid,
                            notes:uNote
                        },
                        cache: false,
                        dataType: "JSON",
                    })
                    .done(function(response){
                        // console.log(response.title);
                        Swal.fire({
                            title:response.title,
                            text:response.message,
                            icon:response.type,
                            allowOutsideClick:false,
                            confirmButtonClass: 'btn btn-lg',
                            buttonsStyling: false,
                        }).then(function(isConfirm){(isConfirm)?location.reload():""});
                    })
                    .fail(function(){
                        Swal.fire("Error", "There's some error", "error");
                    });
                });
            },            
        })
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