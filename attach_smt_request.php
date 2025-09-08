<?php
require_once __DIR__ . '/includes/db.php';
//     require_once __DIR__ . '/includes/session_check.php';
//     include("./includes/convertNumbersToWords.php");
//     $query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
//         if(mysqli_num_rows($query) == 1){
//         include("./includes/avatar_select.php");
//     }

//     if ($_SESSION['user_type'] == 'administrator' OR ($emptypeget == "Manager" AND $_SESSION['user_dept'] == "Finance") OR ($emptypeget == "Manager" AND $_SESSION['user_dept'] == "Management")) {
//         $getquery = mysqli_query($conDB, "SELECT *, SUM(total_cost) as subtotal, SUM(vat_val) as vat_val FROM `smart_request` WHERE `inv_no`='".$_GET['id']."' ");
//     } else {
//         $getquery = mysqli_query($conDB, "SELECT *, SUM(total_cost) as subtotal, SUM(vat_val) as vat_val FROM `smart_request`  WHERE `inv_no`='".$_GET['id']."' AND `department`='".$user_dept."' ");
//         //"SELECT *, SUM(`smart_request`.total_cost) as subtotal, SUM(`smart_request`.vat_val) as vat_val FROM `smart_request` LEFT JOIN `smt_request_status` ON `smart_request`.`inv_no` = `smt_request_status`.`inv_no` WHERE `smart_request`.`inv_no`='".$_GET['id']."' AND `smart_request`.`department`='".$user_dept."' "
//         //"SELECT `employees`.*,`admin_login`.* FROM `employees` LEFT JOIN `admin_login` ON `employees`.`emp_id` = `admin_login`.`emp_id` WHERE `admin_login`.`username`='".$username."'"
//     }    
//         while($row = mysqli_fetch_assoc($getquery)){
//             $idno = $row["id"];
//             $invnoget = $row["inv_no"];
//             $tally_id_get = $row["tally_id"];
//             $injazat_id_get = $row["injazat_id"];
//             $total_costget = $row['subtotal'];
//             $vat_get = $row['vat_val'];
//             $discount_get = $row["discount"];
//             $location_get = $row["location"];
//             $sub_type_get = $row["sub_type"];
//             $sub_title_get = $row["sub_title"];
//             $approv_by_get = $row["approv_by"];
//             $prep_by_get = (explode(" ",$row["prep_by"])[0])." ".(explode(" ",$row["prep_by"])[1]);
//             $department_get = $row["department"];
//             $remarks_get = $row["remarks"];

//             // $vat_get = $total_costget * 15 / 100;
//             $total_cost_get = $total_costget - $vat_get;
//             $total = $total_cost_get + $vat_get;
//             $gtotal = $total - $discount_get;
//         }

// if(isset($_POST['submit'])){
//     // $query = mysqli_query($conDB, "SELECT * FROM `appointment_info` WHERE `rq_time`='".$rq_time."' AND `rq_date`='".$rq_date."' AND (NOT `status` <=> 'Cancel') ");
//     $querycheck = mysqli_query($conDB, "
//             SELECT * , `smt_request_status`.`status` AS `smtstatus` , `smt_request_status`.`note` AS `smtnote`
//             FROM `smt_request_status`
//             LEFT JOIN `employees` ON `smt_request_status`.`emp_id` = `employees`.`emp_id`
//             WHERE `inv_no` = '$_GET[id]'
//             AND `smt_request_status`.`status` = '$_POST[status]'
//             ORDER BY `smt_request_status`.`id` DESC
//             LIMIT 1 
//         ");
//         if(mysqli_num_rows($querycheck) > 0 ) { //check if there is already an entry for that appointment
//                 $msg = "
//                 <div class=\"alert alert-danger bg-danger text-white border-0\" role=\"alert\">Sorry this request <strong>".$_GET['id']."</strong> you already submited.</div>
//                 ";
//             } else {
//     // Get multiple input field's value 
//     if(!empty($_POST['approv_by'])){

//             $inv_no_po = $_POST['inv_no'];
//             $tally_id_po = $_POST['tally_id'];
//             $injazat_id_po = $_POST['injazat_id'];
//             $location_po = $_POST['location'];
//             $sub_type_po = $_POST['sub_type'];
//             $item_name_array = $_POST['item_name'];
//             $quantity_array = $_POST['quantity'];
//             $product_price_array = $_POST['product_price'];
//             $itmvalue_array = $_POST['itmvalue'];
//             $vat_rate_array = $_POST['vat_rate'];
//             $vat_val_array = $_POST['vat_val'];
//             $amount_array = $_POST['amount'];
//             $idiscount_array = $_POST['idiscount'];
//             $total_cost_array = $_POST['total_cost'];
//             $discount_po = $_POST['discount'];
//             $approv_by_po = $_POST['approv_by'];
//             $note_po = $_POST['note'];

//             $qappby = mysqli_query($conDB, "SELECT * FROM `employees` WHERE `emp_id`='".$approv_by_po."' LIMIT 1");
//                 while($row = mysqli_fetch_assoc($qappby)){
//                 /*echo*/ $app_emp_dept = $row["dept"];
//             }

//             /*exit();*/


//             if ($app_emp_dept == 'Finance') {
//                 // $status_po = "finance";
//                 mysqli_query($conDB, "INSERT INTO `smt_request_status` (`emp_id`, `inv_no`, `emp_name`, `status` ) VALUES ('".$empid."', '".$inv_no_po."', '".$userwel."', '".$_POST['status']."' )");
//                 mysqli_query($conDB, "UPDATE `smart_request` SET `discount`='".$discount_po."' WHERE `inv_no`='".$_GET['id']."' ") or die (mysqli_error());
//             } elseif ($emptypeget == "Manager" AND $_SESSION['user_dept'] == "Finance" ) {
//                 // $status_po = "not_approved";
//                 mysqli_query($conDB, "INSERT INTO `smt_request_status` (`emp_id`, `inv_no`, `emp_name`, `status`, `note`) VALUES ('".$empid."', '".$inv_no_po."', '".$userwel."', '".$_POST['status']."', '".$note_po."' )");
//             } elseif ($_SESSION['user_type'] == "gm" ) {
//                 mysqli_query($conDB, "INSERT INTO `smt_request_status` (`emp_id`, `inv_no`, `emp_name`, `status`, `note` ) VALUES ('".$empid."', '".$inv_no_po."', '".$userwel."', '".$_POST['status']."', '".$note_po."' )");
//             } else {
//                 // $status_po = "Manager";
//                 mysqli_query($conDB, "INSERT INTO `smt_request_status` (`emp_id`, `inv_no`, `emp_name`, `status` ) VALUES ('".$empid."', '".$inv_no_po."', '".$userwel."', '".$_POST['status']."' )");
//                 mysqli_query($conDB, "UPDATE `smart_request` SET `discount`='".$discount_po."' WHERE `inv_no`='".$_GET['id']."' ") or die (mysqli_error());
//             }


//         /************log************/
//         //mysqli_query($conDB, "INSERT INTO `activity_log` (`user_editor`,`page`,`pg_id`,`reg_date`) VALUES ('".$_COOKIE['user']."','".$pgname."','".$_POST['maker_name']."','".date("c")."')") or die (mysqli_error());
//         /************log************/
//         $msg = '<div class="alert alert-success bg-success text-white border-0" role="alert">Request submited Seccssfully!</div>';
//          header( "refresh:2 ; url=open_request.php?id=$_GET[id]" );

//     } else {
//         $msg = "<div class=\"alert alert-danger bg-danger text-white border-0\" role=\"alert\">Please fill out the form!</div>";
//     }

//     }

// } 

// if(isset($_POST['submit_edit'])){
//         $item_name_up = $_POST['item_name'];
//         $location_up = $_POST['location'];
//         $quantity_up = $_POST['quantity'];
//         $product_price_up = $_POST['product_price'];
//         $itmvalue_up = $_POST['itmvalue'];
//         $vat_rate_up = $_POST['vat_rate'];
//         $vat_val_up = $_POST['vat_val'];
//         $amount_up = $_POST['amount'];
//         $idiscount_up = $_POST['idiscount'];
//         $total_cost_up = $_POST['total_cost'];
//         $remarks_po = $_POST['remarks'];

//     if($item_name_up){
//         mysqli_query($conDB, "UPDATE `smart_request` SET `item_name`='".$item_name_up."', `location`='".$location_up."', `quantity`='".$quantity_up."', `product_price`='".$product_price_up."',`itmvalue`='".$itmvalue_up."', `vat_rate`='".$vat_rate_up."', `vat_val`='".$vat_val_up."', `amount`='".$amount_up."', `idiscount`='".$idiscount_up."', `total_cost`='".$total_cost_up."' WHERE `id`='".$_POST['itemid']."' ") or die (mysqli_error());
//         $msg = "<div class=\"alert alert-success bg-success text-white border-0\" role=\"alert\">Item Modified Seccssfully!</div>
//         ";      
//         header( "refresh:1 ; url=open_request.php?id=$_GET[id]" );
//     } else {
//         $msg = "<div class=\"alert alert-danger bg-danger text-white border-0\" role=\"alert\">Please fill out the form!</div>";
//     }
// }



// if(isset($_POST['req_edit_submit'])){
//         $sub_type_up = $_POST['sub_type'];
//         $sub_title_up = $_POST['sub_title'];
//         $tally_id_up = $_POST['tally_id'];
//         $injazat_id_up = $_POST['injazat_id'];
//         $remarks_up = $_POST['remarks'];

//     if($sub_type_up){
//         mysqli_query($conDB, "UPDATE `smart_request` SET `sub_type`='".$sub_type_up."', `sub_title`='".$sub_title_up."', `tally_id`='".$tally_id_up."', `injazat_id`='".$injazat_id_up."',`remarks`='".$remarks_up."' WHERE `inv_no`='".$_POST['reqid']."' ") or die (mysqli_error());
//         $msg = "<div class=\"alert alert-success bg-success text-white border-0\" role=\"alert\">Request Modified Seccssfully!</div>
//         ";      
//         header( "refresh:0 ; url=open_request.php?id=$_GET[id]" );
//     } else {
//         $msg = "<div class=\"alert alert-danger bg-danger text-white border-0\" role=\"alert\">Please fill out the form!</div>";
//     }
// }


//     if($emptypeget == "Manager" AND $_SESSION['user_dept'] <> "Finance" ){
//         $query_apprv = mysqli_query($conDB, "SELECT * FROM `employees` WHERE `dept`='Finance' AND `status`=1 AND `emptype`='Manager' ");
//     } elseif ($emptypeget == "Manager" AND $_SESSION['user_dept'] == "Finance") {
//         $query_apprv = mysqli_query($conDB, "SELECT * FROM `employees` WHERE `emp_id`='2' ");  
//     } elseif ($user_type == "gm") {
//         $query_apprv = mysqli_query($conDB, "SELECT * FROM `employees` WHERE `emp_id`='2' ");
//     } else {
//         $query_apprv = mysqli_query($conDB, "SELECT * FROM `employees` WHERE `dept`='".$user_dept."' AND `status`=1 AND `emptype`='Manager' ");
//         /*$query_apprv = mysqli_query($conDB, "SELECT * FROM `employees` WHERE `status`=1 AND `emptype`='Manager' ");*/
//     }

//     /*$getstatus = mysqli_query($conDB, "SELECT * FROM `smt_request_status` WHERE `inv_no`='".$_GET['id']."' ORDER BY id DESC LIMIT 1");*/
//     $getstatus = mysqli_query($conDB, "
//         SELECT *, `smt_request_status`.`status` AS `smtstatus`, `smt_request_status`.`note` AS `smtnote`
//         FROM `smt_request_status`
//         LEFT JOIN `employees` ON `smt_request_status`.`emp_id` = `employees`.`emp_id`
//         WHERE `inv_no` = '$_GET[id]'
//         ORDER BY `smt_request_status`.`id` DESC
//         LIMIT 1 
//     ");
//     while($row = mysqli_fetch_assoc($getstatus)){
//         $statusget = $row["smtstatus"];
//         $invnoget = $row["inv_no"];
//         $deptget = $row["dept"];
//         $noteget = $row["smtnote"];
//     }


// while($rec = mysqli_fetch_array($query_apprv)){
//     $applst[] = "<option value=\"".$rec['emp_id']."\">".$rec['name']."</option>";
// }
// $applist = implode(",",$applst);

// $lstapp = 
// <<<LISTAPP
// <div class="input-group-prepend">
//     <div class="input-group-text">Approved from *</div>
// </div>
// <select class="form-control" name="approv_by" required>
// <option value="">Select</option>
//     $applist
// </select>
// LISTAPP;

//     if ($statusget == "draft") {
//         $status_get = "<a href='javascript:void(0)' class='btn btn-secondary waves-effect'>Not Submited</a>";
//     } elseif ($statusget == "Manager") {
//         $status_get = "<a href='javascript:void(0)' class='btn btn-custom waves-effect'>Waiting from ".$deptget." Manager</a>";
//     } elseif ($statusget == "Finance") {
//         $status_get = "<a href='javascript:void(0)' class='btn btn-warning waves-effect'>Waiting from Finance</a>";
//     } elseif ($statusget == "Management") {
//         $status_get = "<a href='javascript:void(0)' class='btn btn-primary waves-effect'>Waiting from Managment</a>";
//     } elseif ($statusget == "approve") {
//         $status_get = "<a href='javascript:void(0)' class='btn btn-success waves-effect'>Approved from Managment</a>";
//     } elseif ($statusget == "reject" AND $deptget == "Management") {
//         $status_get = "<a href='javascript:void(0)' class='btn btn-danger waves-effect'>Reject from Managment</a>";
//     } elseif ($statusget == "reject" AND $deptget == "Finance") {
//         $status_get = "<a href='javascript:void(0)' class='btn btn-danger waves-effect'>Reject from Finance</a>";
//     }

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

    <link href="./plugins/bootstrap-tagsinput/css/bootstrap-tagsinput.css" rel="stylesheet" />
    <link href="./plugins/bootstrap-select/css/bootstrap-select.min.css" rel="stylesheet" />
    <link href="./plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="./plugins/switchery/switchery.min.css" />

    <!-- App css -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
    <style type="text/css">
        .noneDIV {
            display: none;
        }
        .showDIV {
            display: block;
        }
    </style>

    <!-- Dropzone css -->
    <link href="./plugins/dropzone/dropzone.css" rel="stylesheet" type="text/css" />

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
                <?php //include("./includes/main_menu.php"); 
                ?>
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
            <?php //include("./includes/topbar.php"); 
            ?>
            <!-- Top Bar End -->


            <!-- Start Page content -->
            <div class="content">
                <div class="container-fluid">
                    <div class="row">

                        <div class="col-md-12" id="DataContact">
                            <div class="card-box">
                                <?php echo $msg ?>
                                <div class="row">
                                    <div class="col-12">
                                        <div class="card-box">
                                            <h4 class="header-title m-t-0">Dropzone File Upload</h4>
                                            <p class="text-muted font-14 m-b-10">
                                                Your awesome text goes here.
                                            </p>
                                            <form action="#" class="dropzone" id="dropzone">
                                                <div class="fallback">
                                                    <input name="file" type="file" multiple />
                                                </div>
                                            </form>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-danger waves-effect" onclick="window.history.go(-1); return false;">Close</button>
                                            <button type="button" class="btn btn-success waves-effect waves-light" id="startUpload"><i class="mdi mdi-backup-restore"></i> Upload</button>
                                        </div>
                                    </div>
                                </div>
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
    <!--        <script src="./plugins/bootstrap-inputmask/bootstrap-inputmask.min.js" type="text/javascript"></script>-->
    <script src="./plugins/bootstrap-inputmask/jquery.inputmask.min.js" type="text/javascript"></script>
    <!--        <script src="https://cdn.jsdelivr.net/gh/RobinHerbots/jquery.inputmask@5.0.0-beta.87/dist/jquery.inputmask.min.js" type="text/javascript"></script>-->


    <script src="./plugins/autoNumeric/autoNumeric.js" type="text/javascript"></script>

    <script src="./plugins/switchery/switchery.min.js"></script>
    <script src="./plugins/bootstrap-tagsinput/js/bootstrap-tagsinput.min.js"></script>
    <script src="./plugins/select2/js/select2.min.js" type="text/javascript"></script>
    <script src="./plugins/bootstrap-select/js/bootstrap-select.js" type="text/javascript"></script>
    <script src="./plugins/bootstrap-filestyle/js/bootstrap-filestyle.min.js" type="text/javascript"></script>
    <script src="./plugins/bootstrap-maxlength/bootstrap-maxlength.js" type="text/javascript"></script>


    <script type="text/javascript" src="./plugins/autocomplete/jquery.mockjax.js"></script>
    <script type="text/javascript" src="./plugins/autocomplete/jquery.autocomplete.min.js"></script>
    <script type="text/javascript" src="./plugins/autocomplete/countries.js"></script>
    <!--        <script type="text/javascript" src="assets/pages/jquery.autocomplete.init.js"></script>-->

    <!-- App js -->
    <script src="assets/pages/jquery.form-pickers.init.js"></script>
    <!-- <script type="text/javascript" src="assets/pages/jquery.form-advanced.init.js"></script> -->

    <!-- App js -->
    <script src="assets/js/jquery.core.js"></script>
    <script src="assets/js/jquery.app.js"></script>
    <script src="assets/js/num-word.js"></script>

    <link href="./plugins/sweet-alert/sweetalert2.min.css" rel="stylesheet" type="text/css" />
    <script src="./plugins/sweet-alert/sweetalert2.min.js"></script>
    <script src="assets/pages/jquery.sweet-alert.init.js"></script>

    <!-- Dropzone js -->
    <script src="./plugins/dropzone/dropzone.js"></script>

    <script>
        /***************************/
        /* jQuery('.showAttach').on('click', function(event) { 
          var id = $(this).data('id');  
          var img = $(this).data('i_attachment');
          $(".previewImg").empty().append("<iframe src="+"./assets/assets/smt_attachment/"+img+" frameborder='0' scrolling='no' id='iFramePreview'></iframe");
            jQuery('.preview').show('slow');
                $("#DataContact").addClass('col-md-8');
                $("#DataContact").removeClass('col-md-12');
            });
            jQuery('#closeTab').on('click', function(event) { 
                jQuery('.preview').hide('slow');
                $("#DataContact").removeClass('col-md-8');
                $("#DataContact").addClass('col-md-12');
            });*/
        /****************************/

        //Disabling autoDiscover
        Dropzone.autoDiscover = false;
        $(function() {
            var myDropzone = new Dropzone(".dropzone", {
                url: "./includes/ajaxFile/upload_smt_attachments.php",
                paramName: "file",
                maxFilesize: 8,
                maxFiles: 10,
                acceptedFiles: "image/png,image/jpeg,image/jpg,image/bmp,application/pdf",
                parallelUploads: 10,
                autoProcessQueue: false,
                init: function() {
                    this.on('success', function() {
                        if (this.getQueuedFiles().length == 0 && this.getUploadingFiles().length == 0) {
                            swal({
                                title: "Uploaded!",
                                text: "Your files bas been uploaded successfully.",
                                type: 'success',
                                allowOutsideClick: false
                            }).then(function(isConfirm) {
                                (isConfirm) ? window.history.back(): ""
                            });
                        }
                    });
                }
            })
            myDropzone.on('sending', function(file, xhr, formData) {
                formData.append('id', '<?= $_GET['id'] ?>');
            })
            $('#startUpload').click(function() {
                myDropzone.processQueue();
            });
        });

        function showAttachment() {
            $("#attachmentDIV").removeClass("noneDIV");
            // $("#checkatt").removeClass("noneDIV");
            $("#checkatt").prop('required', true);
        }

        function hideAttachment() {
            $("#attachmentDIV").addClass("noneDIV");
            // $("#checkatt").addClass("noneDIV");
            $("#checkatt").prop('required', null);
        }

        $('.checkattach').click(function() {
            $('#checkatt').val($(this).data('attach'));
        });


        $("#close_page").click(function() {
            var confirm_result = confirm("Are you sure you want to quit?");
            if (confirm_result == true) {
                window.close();
            }
        });




        //  $(document).ready(function(){
        //     $("#statlist").change(function(){
        //         $( "#statlist option:selected").each(function(){
        //             if($(this).attr("value")=="reject"){
        //                 $("#RejectDIV").show();
        //                 $("#ApproveDIV").hide();
        //                 $("#RejectInput").prop('required',true);
        //                 $("#ApproveInput").prop('required',null);
        //                 $("input#RejectInput:text").attr('name','note');
        //                 $("select#ApproveInput").removeAttr('name');
        //             }

        //             if($(this).attr("value")=="approve"){
        //                 $("#ApproveDIV").hide();
        //                 $("#RejectDIV").hide();
        //                 $("#ApproveInput").prop('required',null);
        //                 $("#RejectInput").prop('required',null);
        //                 $("input#RejectInput:text").removeAttr('name');
        //                 $("select#ApproveInput").removeAttr('name');   
        //             }

        //             if($(this).attr("value")=="Management"){
        //                 $("#ApproveDIV").show();
        //                 $("#RejectDIV").hide();
        //                 $("#ApproveInput").prop('required',true);
        //                 $("#RejectInput").prop('required',null);
        //                 $("input#RejectInput:text").removeAttr('name');
        //                 $("select#ApproveInput").attr('name','approv_by');   
        //             }

        //             if ($(this).attr("value") == ""){
        //                 $("#RejectDIV").hide();
        //                 $("#ApproveDIV").hide();
        //                 $("#RejectInput").prop('required',null);
        //                 $("#ApproveInput").prop('required',null);
        //                 $("input#RejectInput:text").removeAttr('name');
        //                 $("select#ApproveInput").removeAttr('name');
        //             }
        //         });
        //     }).change();
        // });
    </script>
    <script>
        // $(function() {
        //   $('input').on('input', function() {
        //     // Find the closest set and recalculate it
        //     var set = $(this).closest('.set');
        //     // Get your values
        //     var n1 = parseInt(set.find('.n1').val() || 0);
        //     var n2 = parseInt(set.find('.n2').val() || 0);
        //     // Set their result
        //     set.find('.result').val(n1 * n2);
        //     set.find('.resultPlus').val(n1 + n2);
        //     set.find('.resultDiv').val(n1 / n2);
        //   });
        // });
        /*document.addEventListener('keydown', (e) => {
            if (e.key.toLowerCase() === '+' && e.ctrlKey && e.shiftKey) {
                e.preventDefault();

                // Add your code here
                alert('S key pressed with ctrl');
            }
        });*/
    </script>

    <script type="text/javascript">
        /*only allow numeric input*/
        /*function isNumberKey(evt, obj) {

            var charCode = (evt.which) ? evt.which : event.keyCode
            var value = obj.value;
            var dotcontains = value.indexOf(".") != -1;
            if (dotcontains)
                if (charCode == 46) return false;
            if (charCode == 46) return true;
            if (charCode > 31 && (charCode < 48 || charCode > 57))
                return false;
            return true;
        }*/
        /*only allow numeric input*/
    </script>

    <!-- <script type="text/javascript">
        // $(document).ready(function() {
        $(document).ready(function() {
            /*var rowCount = 1;
        $('#add').click(function() {
            rowCount++;
            $('#orders').append('<tr id="row'+rowCount+'">'+
                '<td><input type="text" class="form-control rowid" value="'+rowCount+'" readonly></td>'+
                '<td><input type="text" name="item_name[]" placeholder="Enter item name" class="form-control" id="item_name" required autocomplete="off"></td>'+
                '<td><input class="form-control quantity" type="text" id="quantity_'+rowCount+'" name="quantity[]" for="'+rowCount+'" required onkeypress="return isNumberKey(event,this)" /></td>'+
                '<td><input class="form-control product_price" type="text" data-type="product_price" id="product_price_'+rowCount+'" name="product_price[]" for="'+rowCount+'" required onkeypress="return isNumberKey(event,this)" /></td>'+
                '<td><input class="form-control itmvalue" type="text" data-type="itmvalue" id="itmvalue_'+rowCount+'" name="itmvalue[]" for="'+rowCount+'" readonly /></td>'+
                '<td><input class="form-control vat_rate" type="text" data-type="vat_rate" id="vat_rate_'+rowCount+'" name="vat_rate[]" for="'+rowCount+'" required value="15" onkeypress="return isNumberKey(event,this)" /></td>'+
                '<td><input class="form-control vat_val" type="text" data-type="vat_val" id="vat_val_'+rowCount+'" name="vat_val[]" for="'+rowCount+'" readonly /></td>'+
                '<td><input class="form-control amount" type="text" data-type="amount" id="amount_'+rowCount+'" name="amount[]" for="'+rowCount+'" readonly /></td>'+
                '<td><input class="form-control idiscount" type="text" data-type="idiscount" id="idiscount_'+rowCount+'" name="idiscount[]" for="'+rowCount+'" required value="0" onkeypress="return isNumberKey(event,this)" /></td>'+
                '<td class="text-right"><input class="form-control total_cost" type="text" id="total_cost_'+rowCount+'" name="total_cost[]" for="'+rowCount+'" readonly /></td>'+
                '<td class="text-right"><a href="javascript:void(0);" class="btn_remove btn btn-danger btn-sm bbtn" id="'+rowCount+'" title="Remove field"><i class="mdi mdi-database-minus"></a></td></tr>'+
                '');
        });
*/
        // Add a generic event listener for any change on quantity or price classed inputs
        $("#orders").on('input', 'input.quantity,input.product_price,input.vat_rate,input.idiscount', function() {
          getTotalCost($(this).attr("for"));
        });
        // Using a new index rather than your global variable i
        function getTotalCost(ind) {
            var qty = $('#quantity').val();
            var price = $('#product_price').val();
            var itmvalue = (qty * price);
            $('#itmvalue').val( round(itmvalue,2) );
            var ivat = $('#vat_rate').val();
            var idesc = $('#idiscount').val();
            var totNumber = (qty * price);
            var vatValue = (totNumber * ivat / 100);
            // var tot = totNumber.toFixed(2);
            var sub_tot = (vatValue + totNumber)

            $('#vat_val').val( round(vatValue,2) );
            $('#amount').val( round(sub_tot,2) );
            $('#total_cost').val( round(sub_tot - idesc,2) );

            // calculateSubTotal();
        }


        /*$("#gtotal").on('input', 'input.discount', function() {
          calculateSubTotal($(this).attr("for"));
        });
        function calculateSubTotal() {
            var subtotal = 0;
            var totalvat = 0;

            var disc = $('#discount').val();
            $('.total_cost').each(function() {
                subtotal += parseFloat($(this).val());
            });
            $('.vat_val').each(function() {
                totalvat += parseFloat($(this).val());
            });

            $('#subtotal').val( round(subtotal - totalvat, 2) );
            $('#total').val( round(subtotal, 2) );
            $('#vat').val( round(totalvat,2) );
            $('#grandtotal').val( round(subtotal - disc, 2) );
            // $('#grandtotal').val( toWordsconver(subtotal - disc) );
        }*/

        function round(value, decimals) {
            return Number(Math.round(value +'e'+ decimals) +'e-'+ decimals).toFixed(decimals);
        }

});

        $('.editItemAttr').click(function() {
            $('#itemid')      .val($(this).data('id'));
            $('.item_name')     .val($(this).data('i_item_name'));
            $('.quantity')     .val($(this).data('i_quantity'));
            $('.product_price')     .val($(this).data('i_product_price'));
            $('.vat_rate')     .val($(this).data('i_vat_rate'));
            $('.itmvalue')     .val($(this).data('i_itmvalue'));
            $('.i_vat_val')     .val($(this).data('i_vat_val'));
            $('.i_amount')     .val($(this).data('i_amount'));
            $('.idiscount')     .val($(this).data('i_idiscount'));
            $('.i_total_cost')     .val($(this).data('i_total_cost'));
            var ilocation       = $(this).data('i_location');
            $('.i_location option[value="'+ilocation+'"]').prop("selected", "selected");
        });

        $('.editReqAttr').click(function() {
            var subtype       = $(this).data('type');
            $('.subtype option[value="'+subtype+'"]').prop("selected", "selected");
        });

    </script> -->




    <!-- <script type="text/javascript">
    $(document).ready(function() {
        $('form').parsley();
});
</script> -->


</body>

</html>