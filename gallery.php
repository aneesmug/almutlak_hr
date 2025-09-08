<?php
 require_once __DIR__ . '/includes/db.php';
 require_once __DIR__ . '/includes/session_check.php';
 $query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
 if(mysqli_num_rows($query) == 1){
 include("./includes/avatar_select.php");

    $q_post = mysqli_query($conDB, "SELECT * FROM `menu_category` ORDER BY `id` DESC LIMIT 1");
    while ($row = mysqli_fetch_assoc($q_post)) {
        $lastid =  $row['id'];
    }

if(isset($_POST['submit_register'])){

        $name_eng = $_POST['name_eng'];
        $name_ar = $_POST['name_ar'];
        $desc_eng = $_POST['desc_eng'];
        $desc_ar = $_POST['desc_ar'];
        
    if($name_eng){
        
        $query="INSERT INTO `menu_category` (`name_eng`, `name_ar`,`desc_eng`,`desc_ar`) VALUES ('".$name_eng."','".$name_ar."','".$desc_eng."','".$desc_ar."')";
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

if(isset($_POST['submit_edit'])){

        $name_eng_up = $_POST['name_eng'];
        $name_ar_up = $_POST['name_ar'];
        $desc_eng_up = $_POST['desc_eng'];
        $desc_ar_up = $_POST['desc_ar'];
        $status_up = $_POST['status'];
        
    if($name_eng_up){
        
        // $query="INSERT INTO `menu_category` (`name_eng`, `name_ar`,`desc_eng`,`desc_ar`) VALUES ('".$name_eng_up."','".$name_ar_up."','".$desc_eng_up."','".$desc_ar_up."')";
        // mysqli_query($conDB, $query);
        mysqli_query($conDB, "UPDATE `menu_category` SET `name_eng`='".$name_eng_up."', `name_ar`='".$name_ar_up."', `desc_eng`='".$desc_eng_up."', `desc_ar`='".$desc_ar_up."', `status`='".$status_up."' WHERE `id`='".$_POST['id']."' ") or die (mysqli_error());

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


if(isset($_POST['submit_item_edit'])){

        $name_eng_up = $_POST['name_eng'];
        $name_ar_up = $_POST['name_ar'];
        $big_price_up = $_POST['big_price'];
        $small_price_up = $_POST['small_price'];
        $big_cal_up = $_POST['big_cal'];
        $small_cal_up = $_POST['small_cal'];
        $category_id_up = $_POST['category_id'];
        $price_level_up = $_POST['price_level'];
        $status_up = $_POST['status'];
        $image_up = $_POST['iimage'];

        
    if($name_eng_up){

        if($_FILES['file']['name'] != "") {
            $uploadDir = "./QR_MENU/images/item_img/";
            $temp = explode(".", $_FILES["file"]["name"]);
            $tmp_name = $_FILES['file']['tmp_name'];
            $newfilename = round(microtime(true)) . '.' . end($temp);
            $uploadFilePath = $uploadDir.$newfilename;
            move_uploaded_file($tmp_name, $uploadFilePath);
        } else {
            $newfilename = $image_up;
        }

        mysqli_query($conDB, "UPDATE `menu_item` SET `name_eng`='".$name_eng_up."', `name_ar`='".$name_ar_up."', `big_price`='".$big_price_up."', `small_price`='".$small_price_up."',`big_cal`='".$big_cal_up."',`small_cal`='".$small_cal_up."', `price_level`='".$price_level_up."', `category_id`='".$category_id_up."',`image`='".$newfilename."', `status`='".$status_up."' WHERE `id`='".$_POST['itemid']."' ") or die (mysqli_error());

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
        <title><?php echo $site_title ?> - All Locations</title>
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

        <link href="./plugins/dropzone/dropzone.css" rel="stylesheet" type="text/css" />
        
        <script src="assets/js/modernizr.min.js"></script>
        <style type="text/css">
            tr.disableLoc{
                background-color: #f1556c !important;
                color: #fff;
            }
            tr.disableLoc:hover{
                background-color: #ef3d58 !important;
            }
            .swal-wide{
                width:850px !important;
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
   <h4 class="m-t-0 header-title">All Registerd Items</h4>
<table id="employee_vac" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
 <thead>
  <tr>
   <th width="110">Image</th>
   <th>Details</th>
   <th width="80">Status</th>
   <th width="60">Action</th>
  </tr>
 </thead>
 <tbody>
<?php
    $sql_loc = "SELECT * FROM `gallery` ";
    $query_loc = mysqli_query($conDB, $sql_loc);

while ($rec = mysqli_fetch_array($query_loc)) {
    $id = $rec["id"];
    $details = $rec["details"];
    $status = $rec["status"];     
    // $times_reg = strtotime("$date_emp");
    // $datevac = date('d, M Y', $times_reg);
?>

    <tr <?php echo ($status != "1") ? "class='table-danger'" : false ; ?> >
  <th><img src="./assets/gallery/<?=$rec['image'];?>" class="rounded-circle bx-shadow-lg" width="100" height="100" /></th>
        <th><?php echo $details?></th>
  <td><?php echo ($status == "1") ? "<span class='badge-border badge-border-success'>Active</span>" : "<span class='badge-border badge-border-danger'>Inactive</span>" ; ?>
        </td>
  <td>

    <div class='btn-group dropdown'>
        <a href='javascript: void(0);' class='table-action-btn dropdown-toggle arrow-none btn btn-light btn-sm' data-toggle='dropdown' aria-expanded='false'><i class='mdi mdi-dots-horizontal'></i></a>
        <div class='dropdown-menu dropdown-menu-right' x-placement='bottom-end' >
            <a href='javascript:void(0);' class='dropdown-item text-custom updateAjax' data-id="<?=$rec["id"]?>" data-details="<?=$details?>" data-status="<?=$rec['status']?>"><i class='fa fa-edit mr-2 font-18 vertical-middle'></i>Edit</a>
            <?php if($user_type == $access1){ ?>
            <a href='javascript:void(0);' class='dropdown-item  text-danger deleteAjax' data-id='<?=$rec["id"]?>' data-tbl='gallery' data-file='1' data-column='image' ><i class='fa fa-trash mr-2 font-18 vertical-middle'></i>Delete</a>
            <?php } ?>
        </div>
    </div>

  </td>
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

        <script src="./plugins/dropzone/dropzone.js"></script>

  <script type="text/javascript">
            $(document).ready(function() {

        var buttonConfig = [];
        var exportTitle = "All Menu Items"
        buttonConfig.push({extend:'excel',exportOptions: {columns: [ 1, 2, 3, 4, 5, 6, 7 ]} ,title: exportTitle,className: 'btn-success'});
        buttonConfig.push({extend:'pdf',exportOptions: {columns: [ 1, 2, 3, 4, 5, 6, 7 ]} ,title: exportTitle,className: 'btn-danger'});
        buttonConfig.push({extend:'print' ,exportOptions: {columns: [ 1, 2, 3, 4, 5, 6, 7 ]} ,title: exportTitle,className: 'btn-dark'});

        buttonConfig.push({text: '<i class="fa fa-plus"></i> Add Images', action: function ( e, dt, button, config ) { deleteConfirmed() } ,className: 'btn-info'});

                $('form').parsley();

    //Buttons examples
                var table = $('#employee_vac').DataTable({
                    pageLength: 5,
                    lengthChange: false,
                    buttons: buttonConfig,
     // order: [[ 0, "desc" ]],
     // "columnDefs": [
     //     {
     //     targets: [ 3 ],
     //     visible: false,
     //     searchable: false
     //     },
     //    ],
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
<script type="text/javascript">

///////////////////////////////////////// $(document).on('click', '#smt_attachment', function (e) {

function deleteConfirmed(){
    Swal.fire({
        title: 'Dropzone File Upload',
        html: '<form action="#" class="attform">'+
                '<div class="fallback">'+
                    '<input name="file" type="file" multiple />'+
                '</div>'+
            '</form>',
        icon: 'info',
        showCancelButton: true,
        cancelButtonColor: '#d33',
        confirmButtonColor: '#3085d6',
        confirmButtonText: 'Yes, Upload it!',
        showLoaderOnConfirm: true,
        customClass: 'swal-wide',
        // willOpen : () => {
        willOpen : () => {
            $('form.attform').attr('id','dropzone').addClass('dropzone');
            const myDropzone = new Dropzone('#dropzone', {
                url: "./includes/ajaxFile/upload_gallery.php",
                paramName: "file",
                maxFilesize: 8,
                maxFiles: 50,
                acceptedFiles: "image/png,image/jpeg,image/jpg,image/bmp",
                parallelUploads: 50,
                autoProcessQueue: false,
                // autoProcessQueue: true,
                init: function() {
                    this.on('success', function(){
                        if (myDropzone.getQueuedFiles().length == 0 && myDropzone.getQueuedFiles().length == 0) {
                            Swal.fire({
                                title:"Uploaded!",
                                text:"Your files bas been uploaded successfully.",
                                icon:'success',allowOutsideClick:false
                            }).then(function(isConfirm){(isConfirm)?location.reload():""});
                        }
                    });
                }
            })
        },
        preConfirm: function() {
            return new Promise(function(resolve) {
                var myDropzone = Dropzone.forElement("#dropzone");
                myDropzone.processQueue();
            });
        },
        allowOutsideClick: false
    })
}

/////////////////////////////////////////

</script>
<script type="text/javascript">
    $(document).on('click', '.updateAjax', function (e) {
        e.preventDefault();
        var e_iduser    = $(this).data('id'); 
        var e_details   = $(this).data('details');
        var status      = $(this).data('status');
        
        Swal.fire({
            title: 'Update Image information',
            html: edit_user_HTML(),
            text: "You won't be able to revert this!",
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, Update!',
            showLoaderOnConfirm: true,
            customClass: 'swal-wide',
            onOpen: function() {
                $('#iduser')    .val(e_iduser); 
                $('#details')  .val(e_details);
                $('input[name="status"][value="'+status+'"]').prop('checked', true);
            },
            preConfirm: function() {
                return new Promise(function(reject) {
                    if($('#details').val() == "" )
                    {reject("Please fill all mendatory(*) fields first!");}
                    $.ajax({
                            url: './includes/ajaxFile/edit_galary_images.php',
                            type: 'POST',
                            data: $('#submitEditUserForm').serialize(),
                            /*data: {"id":e_iduser,'fullname':$('#fullname').val(),'username':$('#username').val(),'dept':$('#dept').val(),'email':$('#email').val(),'email_pass':$('#email_pass').val(),'mobile':$('#mobile').val(),'status':$("input[type='radio'][name='status']:checked").val(),'user_type' :$('#user_type').val()
                            },*/
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
    });

function edit_user_HTML(){
    var strView =
    '<form id="submitEditUserForm">'+
    '<div class="form-row customSweetAlertMLR">'+        
        
        '<div class="form-group col-md-6">'+
            '<label for="name">Image Details</label>'+
            '<input type="text" id="details" name="details" class="form-control" placeholder="Please add image information...">'+
        '</div>'+
        
        '<div class="form-group col-md-6">'+
            '<br><br>'+
            '<div class="d-inline-block custom-control custom-radio mr-1">'+
                '<input type="radio" class="custom-control-input" name="status" id="radio1" value="1">'+
                '<label class="custom-control-label" for="radio1">Active</label>'+
            '</div>'+
            '<div class="d-inline-block custom-control custom-radio mr-1">'+
                '<input type="radio" class="custom-control-input" name="status" id="radio2" value="0">'+
                '<label class="custom-control-label" for="radio2">Inactive</label>'+
            '</div>'+
        '</div>'+

    '<input type="hidden" id="iduser" name="id"></div></form>';
        return strView;
}
</script>

    </body>
</html>
<?php } ?>