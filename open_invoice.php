<?php
 require_once __DIR__ . '/includes/db.php';
 require_once __DIR__ . '/includes/session_check.php';
 include("./includes/convertNumbersToWords.php");
 $query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
 if(mysqli_num_rows($query) == 1){
 include("./includes/avatar_select.php");
 
 include("./includes/Hijri_GregorianConvert.php");
 $DateConv=new Hijri_GregorianConvert;
 $format="DD/MM/YYYY";
 
 $getquery = mysqli_query($conDB, "
    SELECT `employees`.*, `emp_inv_attachment`.`id` AS `lid`, `emp_inv_attachment`.`status` AS `invstatus`, `emp_inv_attachment`.* 
    FROM `emp_inv_attachment` 
    LEFT JOIN `employees` 
    ON `emp_inv_attachment`.`emp_id` = `employees`.`emp_id`
    WHERE 
        `emp_inv_attachment`.`srno` = '$_GET[srno]' 
        AND `employees`.`emp_id` = '$_GET[verification]'
        AND `emp_inv_attachment`.`deleted` = '0'
 ");
$record_count = mysqli_num_rows($getquery);
if($record_count !== 0){
    while($row = mysqli_fetch_assoc($getquery)){
        $id_inv = $row["lid"];
        $srno_inv = $row["srno"];
        $name_inv = $row["name"];
        $dept_inv = $row["dept"];
        $status_inv = $row["invstatus"];
        $amount_inv = $row["total_amount"];
        $apprv_amount_inv = $row["apprv_amount"];
        $note_inv = $row["note"];
        $inv_count_inv = $row["inv_count"];
        $updated_at_inv = date('d M Y H:ia', strtotime($row["updated_at"]));
        $created_at_inv = date('d M Y H:ia', strtotime($row["created_at"]));
        $statusinv =     
            ($status_inv == "draft" ? "<span class='badge badge-warning'>Under Process</span>":
                ($status_inv == "approve" ? "<span class='badge badge-success'>Invoice Approved</span>":
                    ($status_inv == "reject" ? "<span class='badge badge-danger'>Rejected</span>":"")
                )
            );
    }
} else {
//when the id not equals id show database
    header("Location: ./all_user_invoices.php");
}

/*if ($amount_inv == "0.00") {
    echo "<script>Swal.fire('Heres a message!')</script>";
}*/

?>
<!doctype html> 
<html lang="en">

    <head>
        <meta charset="utf-8" />
        <title><?= $site_title ?> - Open Invoices</title>
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

        <link href="./plugins/bootstrap-xeditable/css/bootstrap-editable.css" rel="stylesheet" />

        <!-- App css -->
        <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
        <script src="assets/js/modernizr.min.js"></script>
  
    </head>
    <!-- <body class="enlarged" data-keep-enlarged="true" onLoad="javascript:window.print()"> -->
    <body class="enlarged" data-keep-enlarged="true">
    <!-- <body> -->

        <!-- Begin page -->
        <div id="wrapper">

            <!-- ========== Left Sidebar Start ========== -->
            <div class="left side-menu">

                <div class="slimscroll-menu" id="remove-scroll">

                    <!-- LOGO -->
                    <div class="topbar-left">
                        <a href="dashboard.php" class="logo">
                            <span>
                                <img src="assets/images/logo.png" alt="" height="20">
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
                            <div class="col-md-12" id="DataContact">
                                <div class="card-box">

                                	<?php if ($note_inv AND $status_inv == 'reject'): ?>
                                	<div class="alert alert-danger bg-danger text-white border-0" role="alert">
                                        This is a request rejected due to <strong> "<?= $note_inv ?>"  </strong>
                                    </div>
                                	<?php endif; ?>
                                    <div class="clearfix">
                                        <div class="float-left mb-3">
                                            <img src="assets/images/logo.png" alt="" height="50">
                                        </div>
                                        <div class="float-right">
                                            <h4 class="m-0 d-print-none">Open Submited Invoice</h4>
                                        </div>
                                    </div>


                                    <div class="row">
                                        <div class="col-6">
                                          <div class="mt-3 float-left">
                                                <p class="m-b-10"><strong>Request Date: </strong> <?= $created_at_inv ?></p>    
                                                <?php if ($status_inv !== 'draft'): ?>
                                                <p class="m-b-10"><strong>Status Update At: </strong><?= $updated_at_inv ?></p>
                                                <?php endif ?>
                                                <p class="m-b-10"><strong>Request Status: </strong><?= $statusinv ?></p>
                                                <?php if ($noteget): ?>
                                                	<p class="m-b-10" id="smtnoteprint" style="display: none;">
                                                		<strong>Reject Note: </strong><?= $noteget ?>
                                                	</p>
                                                <?php endif ?>
                                                <?php if ($status_inv == 'draft'): ?>
                                                <p class="m-b-10"><strong>Apply Status: </strong>
                                                    <a href="javascript:void(0);" id="status_chk" data-type="select" data-pk="1" data-srno="<?=$_GET['srno']?>" data-value="" data-title="Select option"></a>
                                                </p>
                                                <?php endif ?>
                                                <?php if ($amount_inv != "0.00"): ?>
                                                    <p class="m-b-10"><strong>Total Invoice's Amount: </strong><?=$amount_inv?> SR</p>
                                                <?php endif ?>
                                                
                                                <?php if ( $inv_count_inv > $record_count AND $status_inv == "approve" AND $amount_inv == $apprv_amount_inv ): ?>
                                                   <div class="approv" onload="approveInvoiceAmount('<?=$_GET['srno']?>','<?=$amount_inv?>')"></div>
                                                <?php endif ?>

                                                <?php if ( $apprv_amount_inv !== $amount_inv ): ?>
                                                    <p class="m-b-10">
                                                        <strong>Rejected amount: </strong>
                                                        <span class='badge badge-danger'><?=$apprv_amount_inv - $amount_inv ?> SR</span>
                                                    </p>
                                                    <p class="m-b-10">
                                                        <strong>Approved invoice's amount: </strong>
                                                        <span class='badge badge-success'><?=$apprv_amount_inv?> SR</span>
                                                    </p>
                                                <?php endif ?>
                                            </div>
                                        </div><!-- end col -->
                                        <div class="col-6">
                                            <div class="mt-3 float-right">
                                                <p class="m-b-10"><strong>Request ID: </strong> <?= $srno_inv ?></p>
                                                <p class="m-b-10"><strong>Department: </strong> <?= $dept_inv ?></p>
                                                <p class="m-b-10"><strong>Prepared by: </strong> <?= $name_inv ?></p>
                                            </div>
                                        </div><!-- end col -->
                                    </div>
                                    <!-- end row -->
                                    <?php if ($status_inv == 'approve'): ?>
                                        <div class="alert alert-success bg-success text-white border-0" role="alert">
                                                These below invoices are approved.
                                        </div>
                                    <?php endif ?>

                                    <div class="row">
                                        <?php
                                            $queryempdocu = mysqli_query($conDB, "SELECT * FROM `emp_inv_attachment` WHERE `srno`='$_GET[srno]' AND `deleted`='0' ");
                                                while($recempdoc = mysqli_fetch_assoc($queryempdocu)){
                                                    $id_empdoc_get = $recempdoc["id"];
                                                    $attachment_get = $recempdoc["attachment"];
                                                    $docu_ext_get = $recempdoc["docu_ext"];
                                                    $doc_date_reg_get = $recempdoc["created_at"];
                                                    $times_reg = strtotime("$doc_date_reg_get");
                                                    $doc_date_reg_get = date('d, M Y h:ia', $times_reg);
                                                    $fileIcon = ($docu_ext_get == "pdf" ? "pdf" : ($docu_ext_get == "xls" ? "excel" : ($docu_ext_get == "tif" ? "tif" : "" )));
                                        ?>
                                            <div class="col-lg-2 col-xl-2">
                                                <div class="file-man-box">
                                                    
                                                    <?php if ($status_inv == 'draft' OR $_SESSION['user_type'] == $access1): ?>
                                                    <a href="javascript:void(0);" class="file-close deleteAjax" data-id='<?=$id_empdoc_get?>' data-tbl='emp_inv_attachment' data-file='1' data-column='attachment' ><i class="mdi mdi-close-circle"></i></a>
                                                    <?php else: ?>
                                                    <a href="javascript:void(0);" class="file-close deleteInvAjax" data-id='<?=$id_empdoc_get?>' data-tbl='emp_inv_attachment' ><i class="mdi mdi-close-circle"></i></a>
                                                    <?php endif ?>

                                                    <div class="file-img-box" style="cursor: pointer;" onclick="javascript:displayPopup('./assets/invo_attach_emp/<?=$attachment_get?>')">
                                                        <?php if ($docu_ext_get == "pdf"):?>
                                                            <img src="assets/images/file_icons/<?=$fileIcon?>.svg" />
                                                        <?php else:?>
                                                            <img src="./assets/invo_attach_emp/<?=$attachment_get?>" />
                                                        <?php endif?>
                                                    </div>

                                                    <a href="./downloadFile.php?file=./assets/invo_attach_emp/<?=$attachment_get?>" class="file-download"><i class="mdi mdi-download"></i></a>
                                                    <div class="file-man-title">
                                                        <p class="mb-0"><small><?=$doc_date_reg_get?></small></p>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php } ?>
                                    </div>
                                </div>
                                <?php
                                
                                $queryinv_rej = mysqli_query($conDB, "SELECT * FROM `emp_inv_attachment` WHERE `srno`='$_GET[srno]' AND `deleted`='1' ");
                                $record_count = mysqli_num_rows($queryinv_rej);
                                    if ($record_count !== 0): ?>
                                <div class="card-box">
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="alert alert-danger bg-danger text-white border-0" role="alert">
                                                These below invoices are rejected.
                                            </div>
                                            <div class="row">
                                        <?php
                                                while($recempdoc = mysqli_fetch_assoc($queryinv_rej)){
                                                    $id_empdoc_get = $recempdoc["id"];
                                                    $attachment_get = $recempdoc["attachment"];
                                                    $docu_ext_get = $recempdoc["docu_ext"];
                                                    $doc_date_reg_get = $recempdoc["created_at"];
                                                    $times_reg = strtotime("$doc_date_reg_get");
                                                    $doc_date_reg_get = date('d, M Y h:ia', $times_reg);
                                                    $fileIcon = ($docu_ext_get == "pdf" ? "pdf" : ($docu_ext_get == "xls" ? "excel" : ($docu_ext_get == "tif" ? "tif" : "" )));
                                        ?>
                                            <div class="col-lg-2 col-xl-2">
                                                <div class="file-man-box">
                                                    
                                                    <?php if ($status_inv == 'draft' OR $_SESSION['user_type'] == $access1): ?>
                                                    <a href="javascript:void(0);" class="file-close deleteAjax" data-id='<?=$id_empdoc_get?>' data-tbl='emp_inv_attachment' data-file='1' data-column='attachment' ><i class="mdi mdi-close-circle"></i></a>
                                                    <?php else: ?>
                                                    <a href="javascript:void(0);" class="file-close deleteInvAjax" data-id='<?=$id_empdoc_get?>' data-tbl='emp_inv_attachment' ><i class="mdi mdi-close-circle"></i></a>
                                                    <?php endif ?>

                                                    <div class="file-img-box" style="cursor: pointer;" onclick="javascript:displayPopup('./assets/invo_attach_emp/<?=$attachment_get?>')">
                                                        <?php if ($docu_ext_get == "pdf"):?>
                                                            <img src="assets/images/file_icons/<?=$fileIcon?>.svg" />
                                                        <?php else:?>
                                                            <img src="./assets/invo_attach_emp/<?=$attachment_get?>" />
                                                        <?php endif?>
                                                    </div>

                                                    <a href="./downloadFile.php?file=./assets/invo_attach_emp/<?=$attachment_get?>" class="file-download"><i class="mdi mdi-download"></i></a>
                                                    <div class="file-man-title">
                                                        <p class="mb-0"><small><?=$doc_date_reg_get?></small></p>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif ?>

                            </div>
                        </div>
                        <!-- end row -->

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
<!--        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.2.61/jspdf.min.js"></script>-->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.3.4/jspdf.min.js" integrity="sha256-vIL0pZJsOKSz76KKVCyLxzkOT00vXs+Qz4fYRVMoDhw=" crossorigin="anonymous"></script>
        <script src="assets/js/bootstrap.bundle.min.js"></script>
        <script src="assets/js/metisMenu.min.js"></script>
        <script src="assets/js/waves.js"></script>
        <script src="assets/js/jquery.slimscroll.js"></script>


        <!-- Modal-Effect -->
        <script type="text/javascript" src="./plugins/parsleyjs/parsley.min.js"></script>
        <!-- <script src="./plugins/bootstrap-inputmask/bootstrap-inputmask.min.js" type="text/javascript"></script> -->
        <script src="./plugins/autoNumeric/autoNumeric.js" type="text/javascript"></script>


        <!-- Xeditable -->
        <script src="./plugins/moment/moment.js" type="text/javascript"></script>
        <script src="./plugins/bootstrap-xeditable/js/bootstrap-editable.min.js" type="text/javascript"></script>
        <script src="./assets/pages/jquery.mockjax.js" type="text/javascript"></script>

        <script src="./plugins/bootstrap-timepicker/bootstrap-timepicker.js"></script>
        <script src="./plugins/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js"></script>
        <script src="./plugins/clockpicker/js/bootstrap-clockpicker.min.js"></script>
        <script src="./plugins/bootstrap-daterangepicker/daterangepicker.js"></script>
        <script src="./plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>

        <!-- 
        <script src="./plugins/sweet-alert/v11/sweetalert2.js"></script>
        <script src="./plugins/sweet-alert/v11/sweetalert2.min.js"></script>
        <script src="./plugins/sweet-alert/v11/sweetalert2.all.js"></script>
        <script src="./plugins/sweet-alert/v11/sweetalert2.all.min.js"></script>
         -->


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

        <?php if ($amount_inv == "0.00"): ?>
            <script>
                $(window).on('load', function(){
                    updateInvoiceAmount("<?=$_GET['srno']?>");
                });
            </script>
        <?php endif ?>

    </body>
</html>
<?php } ?>