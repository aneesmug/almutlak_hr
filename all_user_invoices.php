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
        <title><?=$site_title ?> - All Invoice's</title>
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

        <!-- Dropzone css -->
        <link href="./plugins/dropzone/dropzone.css" rel="stylesheet" type="text/css" />

        <!-- App css -->
        <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
        <script src="assets/js/modernizr.min.js"></script>

        <style type="text/css">
            .swal-wide{ width:850px !important;}
            /*.dz-remove{
                    display:inline-block !important;
                    width:1.2em;
                    height:1.2em;
                    position:absolute;
                    top:5px;
                    right:5px;
                    z-index:1000;
                    font-size:1.2em !important;
                    line-height:1em;
                    text-align:center;
                    font-weight:bold;
                    border:1px solid gray !important;
                    border-radius:1.2em;
                    color:gray;
                    background-color:white;
                    opacity:.5;
                }*/
                .dz-remove:hover{
                    text-decoration:none !important;
                    opacity:1;
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
            <h4 class="header-title m-t-0">Dropzone File Upload</h4>
            <p class="text-muted font-14 m-b-10">
                Upload your Invoice here with capture image.
            </p>
            <form action="#" class="dropzone" id="dropzone">
                <div class="fallback">
                    <input name="file" type="file" multiple />
                </div>
            </form>
            <br />
            <button type="button" class="btn btn-primary btn-block waves-effect waves-light btn-lg" id="startUpload"><i class="dripicons-cloud-upload"></i> Upload</button>
        </div>
    </div>

 <div class="col-12">
  <?=$msg?>
  <div class="card-box table-responsive">
   <h4 class="m-t-0 header-title">All Registerd Invoices</h4>
   
   <div id="response"></div>

<table id="invoices_tbl" class="table table-striped table-bordered dt-responsive nowrap invoices_tbl" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
 <thead>
  <tr>
   <th>ID</th>
   <th>Sr No.</th>
   <th>Total Amount</th>
   <th>Employee Name</th>
   <th>Department</th>
   <th>Status</th>
   <th>Added On</th>
   <th style="width: 30px">Action</th>
  </tr>
 </thead>
 <tbody>
<?php

if($user_type == 'administrator'){
    $sql_emp_vac = "
    SELECT 
        `employees`.`name`, 
        `employees`.`dept`, 
        `emp_inv_attachment`.`id` AS `lid`, 
        `emp_inv_attachment`.`status` AS `invstatus`, 
        `emp_inv_attachment`.* 
    FROM `emp_inv_attachment` 
    LEFT JOIN `employees` 
        ON `emp_inv_attachment`.`emp_id` = `employees`.`emp_id`
    GROUP BY `emp_inv_attachment`.`srno`
    ";
}elseif($user_type == 'employee'){
    $sql_emp_vac = "
    SELECT 
        `employees`.`name`, 
        `employees`.`dept`, 
        `emp_inv_attachment`.`id` AS `lid`, 
        `emp_inv_attachment`.`status` AS `invstatus`, 
        `emp_inv_attachment`.* 
    FROM `emp_inv_attachment` 
    LEFT JOIN `employees` 
        ON `emp_inv_attachment`.`emp_id` = `employees`.`emp_id`
    WHERE `employees`.`emp_id` = '$_SESSION[empid]'
    GROUP BY `emp_inv_attachment`.`srno`
    ";
}elseif($user_type == 'hr'){
    $sql_emp_vac = "
    SELECT 
        `employees`.`name`, 
        `employees`.`dept`, 
        `emp_inv_attachment`.`id` AS `lid`, 
        `emp_inv_attachment`.`status` AS `invstatus`, 
        `emp_inv_attachment`.* 
    FROM `emp_inv_attachment` 
    LEFT JOIN `employees` 
        ON `emp_inv_attachment`.`emp_id` = `employees`.`emp_id`
    WHERE 
        `employees`.`dept` = 'HR and Housing' OR 
        `employees`.`dept` = 'Transportation'
    GROUP BY `emp_inv_attachment`.`srno`
    ";
}else{
    $sql_emp_vac = "
    SELECT 
        `employees`.`name`, 
        `employees`.`dept`, 
        `emp_inv_attachment`.`id` AS `lid`, 
        `emp_inv_attachment`.`status` AS `invstatus`, 
        `emp_inv_attachment`.* 
    FROM `emp_inv_attachment` 
    LEFT JOIN `employees` 
        ON `emp_inv_attachment`.`emp_id` = `employees`.`emp_id`
    WHERE `employees`.`dept` = '$_SESSION[user_dept]'
    GROUP BY `emp_inv_attachment`.`srno`
    ";
}

$query_emp_vac = mysqli_query($conDB, $sql_emp_vac);
while ($rec = mysqli_fetch_array($query_emp_vac)) {
    $id_inv = $rec["lid"];
    $empid_inv = $rec["emp_id"];
    $amount_inv = $rec["total_amount"];
    $srno_inv = $rec["srno"];
    $name_inv = $rec["name"];
    $dept_inv = $rec["dept"];
    $status_inv = $rec["invstatus"];
    $created_at_inv = date('d M Y H:ia', strtotime($rec["created_at"]));

    $statusinv =     
        ($status_inv == "draft" ? "<span class='badge-border badge-border-warning'>Under Process</span>":
            ($status_inv == "approve" ? "<span class='badge-border badge-border-success'>Invoice Approved</span>":
                ($status_inv == "reject" ? "<span class='badge-border badge-border-danger'>Rejected</span>":"")
            )
        );
?>
    <tr>
        <th><?=$id_inv; ?></th>
        <th><?=$srno_inv ?></th>
        <th><?=$amount_inv ?></th>
        <th><?=$name_inv; ?></th>
        <th><?=$dept_inv; ?></th>
        <th><?=$statusinv; ?></th>
        <th><?=$created_at_inv; ?></th>
        <th>
            <div class="btn-group" role="group" aria-label="Edit Button">
            <a href='open_invoice.php?srno=<?=$srno_inv?>&verification=<?=$empid_inv?>' class='btn btn-sm btn-dark waves-effect' ><i class='fa fa-eye mr-2'></i>Open</a>
                <?php if($user_type == $access1){ ?>
                <a href='javascript:void(0);' class='btn btn-sm btn-danger waves-effect deleteAjax' data-id='<?=$id_inv?>' data-tbl='emp_inv_attachment' data-file='1' data-column='attachment'><i class='fa fa-trash mr-2'></i></a>
                <?php } ?>
            </div>
        </th>
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
                    <?=$site_footer ?>
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

        <!-- Dropzone js -->
        <!-- <script src="./plugins/dropzone/dropzone.js"></script> -->
    
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.0/jquery.validate.js"></script>


        <!-- App js -->
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js"></script>

    
        <script type="text/javascript">
            //Disabling autoDiscover
            Dropzone.autoDiscover = false;
            $(function() {
                var myDropzone = new Dropzone(".dropzone", {
                    url: "./includes/ajaxFile/ajaxInvoStatus.php",
                    paramName: "file",
                    maxFilesize: 10,
                    maxFiles: 15,
                    addRemoveLinks: true,
                    acceptedFiles: "image/png,image/jpeg,image/jpg,image/bmp,application/pdf",
                    parallelUploads: 15,
                    autoProcessQueue: false,
                    init: function() {
                        this.on("addedfile", function(file) {
                            $(".dz-remove").html(`<div class="mt-2"><span class="icon-close text-danger" style="font-size: 1.5em; cursor: pointer"></span></div>`);
                        });
                        this.on('queuecomplete', function () {
                            addInvoiceAmount();
                        });
                    }
                })
                myDropzone.on('sending', function(file, xhr, formData){
                    formData.append('empid', '<?=$_SESSION['empid']?>');
                    formData.append('getinv_no', '<?="INV"."".$empid."".date('ymdis')?>');
                    formData.append('ajaxType', 'upload_invo_att_user');
                    formData.append('count', myDropzone.getAcceptedFiles().length );
                })
                $('#startUpload').click(function(){           
                    myDropzone.processQueue();
                });
            });
        </script>


  <script type="text/javascript">
            $(document).ready(function() {
        var buttonConfig = [];
        var exportTitle = "All Invoices"
        buttonConfig.push({extend:'excel',exportOptions: {columns: [ 0, 1, 2, 3 ]} ,title: exportTitle,className: 'btn-success'});
        buttonConfig.push({extend:'pdf',exportOptions: {columns: [ 0, 1, 2, 3 ]} ,title: exportTitle,className: 'btn-danger'});
        buttonConfig.push({extend:'print' ,exportOptions: {columns: [ 0, 1, 2, 3 ]} ,title: exportTitle,className: 'btn-dark'});
    
    //Buttons examples
        var table = $('#invoices_tbl').DataTable({
            lengthChange: false,
            buttons: buttonConfig,
            order: [[ 0, "desc" ]],
              "columnDefs": [
                {
                  targets: [ 0 ],
                  visible: false,
                  searchable: false
                },
              ],
        });
    
     table.buttons().container()
     .appendTo('#invoices_tbl_wrapper .col-md-6:eq(0)');
        });


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
    //   $("#pet_id_box").show();
    $("#note").hide();
  });
</script>

<script type="text/javascript">
    /*$(document).on('click', '.deleteAjax', function (e) {
        e.preventDefault();
        var itemId = $(this).data('id');
        var tbl = $(this).data('tbl');
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
                        url: './includes/ajaxFile/deleteAjax.php',
                        type: 'POST',
                        data: {id:itemId,tbl:tbl},
                        cache: false,
                        dataType: "json",
                    })
                    .done(function(response){
                        Swal.fire({
                            title:response.title,text:response.message,icon:response.type,allowOutsideClick:false
                        }).then(function(isConfirm){(isConfirm)?location.reload():""});
                    })
                    .fail(function(){
                        Swal.fire(response.title, response.message, response.type);
                    });
                });
            },
            allowOutsideClick: false
        })
    });*/


        $(document).ready(function(){

            var buttonConfig = [];
            var columnsConfig = [ 1, 2, 3, 4, 5, 6, 7, 8 ];
            var exportTitle = "All Expiry ID Employees";

            buttonConfig.push({extend:'excel',exportOptions: {columns: columnsConfig} ,title: exportTitle,className: 'btn-success'});
            buttonConfig.push({extend:'pdf',exportOptions: {columns: columnsConfig} ,title: exportTitle,className: 'btn-danger'});
            buttonConfig.push({extend:'print' ,exportOptions: {columns: columnsConfig} ,title: exportTitle,className: 'btn-dark'});

            $('.fileexport').DataTable( {
                dom: 'Bfrtip',
                responsive: true,
                buttons: buttonConfig,
                order: [[ 0, "desc" ]],
                    "columnDefs": [
                                 {
                                 targets: [ 0 ],
                                 visible: false,
                                 searchable: false
                                 },
                             ],
            } );
        });

    </script>


    <script type="text/javascript">
      $(document).ready(function() {
        // Edit user information START
        $("#submitForm").click(function() {
            var id = $('input[name=id]').val();
            var fullname = $('input[name=fullname]').val();
            var username  = $('input[name=username]').val();
            if (fullname == "" || username == "" ) {
              $("#response").fadeIn();
              $("#response").html("<div class='alert alert-danger'><strong>Error!</strong> All fields are required.</div>");
            } else {
              /*$("#response").html($('#submitEditUserForm').serialize());
              var formData = {id: id, fullname: fullname, username: username };
              console.log(formData);*/
              $.ajax({
                url: "./includes/ajaxFile/edit_user.php",
                type: "POST",
                data: $('#submitEditUserForm').serialize(),
                dataType: "json",
                // data: formData,
                success: function(res){
                  setTimeout(function () { 
                      // swal('Updated!','This user has been update successfully.', 'success')
                    Swal.fire({title:res.title,text:res.message,icon:res.type,allowOutsideClick:false}).then(function(isConfirm){(isConfirm)?location.reload():""});
                  },1);
                  /*if (res.status == 'success') {
                      window.setTimeout(function(){ 
                        location.reload(true);
                      } ,2000); 
                    }*/
                }
              });
            }
        });
        // Edit user Password
        /*$("#edit_user_password").click(function() {
            var id = $('input[name=id]').val();
            var password = $('input[name=password]').val();
            if (password == "") {
              $("#responsePass").fadeIn();
              $("#responsePass").html("<div class='alert alert-danger'><strong>Error!</strong> All fields are required.</div>");
            } else {
              $.ajax({
                url: "./includes/ajaxFile/edit_user_password.php",
                type: "POST",
                data: {ajax: 1, id: id, password: password},
                dataType: "json",
                success: function(res){
                  setTimeout(function () {
                    swal({title:res.title,text:res.message,icon:res.type,allowOutsideClick:false}).then(function(isConfirm){(isConfirm)?location.reload():""});
                  },1);
                }
              });
            }
        });*/

      });
    </script>

    </body>
</html>
<?php } ?>