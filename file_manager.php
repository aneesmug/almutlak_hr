<?php

    require_once __DIR__ . '/includes/db.php';
SELECT * FROM `admin_login` WHERE `id_iqama`
    require_once __DIR__ . '/includes/session_check.php';

    $query = mysqli_query($conDB, "SELECT * FROM admin_login WHERE username='".$username."'");

    if(mysqli_num_rows($query) == 1){

    include("./includes/avatar_select.php");



// header('Access-Control-Allow-Origin: *');

// header('Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS'); 

 

// if(array_key_exists('HTTP_ACCESS_CONTROL_REQUEST_HEADERS', $_SERVER)) {

//     header('Access-Control-Allow-Headers: '

//           . $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']);

// } else {

//     header('Access-Control-Allow-Headers: origin, x-requested-with, content-type, cache-control');

// }

 

// if($_SERVER['REQUEST_METHOD']=='OPTIONS') die();





    function ExtType($type)

    {

        if ($type == "zip")

        {

            // $type = "<i class=\"fa fa-file-archive-o\"></i>";

            $type = '<div class="file-img-box"><img src="./assets/images/file_icons/zip.svg" width="50"></div>';

        }

        elseif ($type == "rar")

        {

            $type = '<div class="file-img-box"><img src="./assets/images/file_icons/rar.svg" width="50"></div>';

        }

        elseif ($type == "exe")

        {

            $type = '<div class="file-img-box"><img src="./assets/images/file_icons/dll.svg" width="50"></div>';

        }

        elseif ($type == "txt")

        {

            $type = '<div class="file-img-box"><img src="./assets/images/file_icons/txt.svg" width="50"></div>';

        }

        elseif ($type == "xls" OR $type == "xlsx" OR $type == "xlsm")

        {

            $type = '<div class="file-img-box"><img src="./assets/images/file_icons/excel.svg" width="50"></div>';

        }

        elseif ($type == "doc" OR $type == "docx")

        {

            $type = '<div class="file-img-box"><img src="./assets/images/file_icons/winword.svg" width="50"></div>';

        }

        elseif ($type == "sql")

        {

            $type = '<div class="file-img-box"><img src="./assets/images/file_icons/sql.svg" width="50"></div>';

        }

        else

        {

            $type = '<div class="file-img-box"><img src="./assets/images/file_icons/code.svg" width="50"></div>';

        }



        return $type;

    }



    function Size($path)

    {

        $bytes = sprintf('%u', filesize($path));

        if ($bytes > 0)

        {

            $unit = intval(log($bytes, 1024));

            $units = array('B', 'KB', 'MB', 'GB');



            if (array_key_exists($unit, $units) === true)

            {

                return sprintf('%d %s', $bytes / pow(1024, $unit), $units[$unit]);

            }

        }

        return $bytes;

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



        <!-- Bootstrap fileupload css -->

        <link href="./plugins/bootstrap-fileupload/bootstrap-fileupload.css" rel="stylesheet" />



        <!-- Dropzone css -->

        <link href="./plugins/dropzone/dropzone.css" rel="stylesheet" type="text/css" />



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



        <link href="./plugins/sweet-alert/sweetalert2.min.css" rel="stylesheet" type="text/css" />



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

            <h4 class="m-t-0 header-title">All Registerd Users</h4>

<table id="employee_vac" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">

    <thead>

        <tr>

            <th width="50">Ext.</th>

            <th>Section Name</th>

            <th width="60">File Size</th>

            <th width="60">Action</th>

        </tr>

    </thead>

    <tbody>

<?php

/*$filepath = opendir('./soft');

if ($handle = $filepath) {

    while (false !== ($file = readdir($handle))) {

        if ($file != "." && $file != "..") { */ 



if (isset($_GET['delete'])) {

    unlink("./file_manager/".$_GET['delete']);

    header("Location: ./file_manager.php");

}



if ($handle = opendir("./file_manager")) {

    while (false !== ($file = readdir($handle))) {

        $pos = strpos( $file, '.' );

        if ($file != "." && $file != ".." && is_numeric($pos)  ) {

?>

    <tr>

        

        <td><a href="./file_manager/<?php echo $file ?>"><?php echo ExtType(pathinfo("./file_manager/".$file, PATHINFO_EXTENSION) ) ?></a></td>

        <td><a href="./file_manager/<?php echo $file ?>"><?php echo $file ?></a></td>

        <td><?php echo Size("./file_manager/".$file) ?></td>

        <td>

        <div class="btn-group" role="group" aria-label="Edit Button">

        <a href="./file_manager/<?php echo $file ?>" class="btn btn-sm btn-dark waves-effect">

            <i class="mdi mdi-download"></i>

        </a>

        <a href="javascript:void(0);" class="btn btn-sm btn-danger waves-effect delete" data-id="<?=$file?>">

            <i class="fa fa-trash"></i>

        </a>

        </div>

        </td>

    </tr>

<?php 

        }

    }

    closedir($handle);

}

?>

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





<div class="modal fade" id="addModuleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">

    <div class="modal-backdrop">

        <div class="modal-dialog modal-lg" role="document">

            <div class="modal-content">

                <section class="contact-form">



                        <div class="modal-header">

                            <h4 class="modal-title" id="myModalLabel17">Dropzone File Upload</h4>

                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">

                                <span aria-hidden="true">&times;</span>

                            </button>

                        </div>

                        <div class="modal-body">

                            <div class="card-box">

                                <form action="#" class="dropzone" id="dropzone">

                                    <div class="fallback">

                                        <input name="file" type="file" multiple />

                                    </div>



                                </form>

                            </div>

                        </div>

                        <div class="modal-footer">

                            <!-- <input type="hidden" id="idmud" name="id"> -->

                            <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>

                            <button type="button" class="btn btn-success waves-effect waves-light" id="startUpload"><i class="mdi mdi-backup-restore"></i> Upload</button>

                        </div>

                    

                </section>

            </div>

        </div>

    </div>

</div>



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

        <!--<script src="./plugins/bootstrap-timepicker/bootstrap-timepicker.js"></script>-->

        <!--<script src="./plugins/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js"></script>-->

        <!--<script src="./plugins/clockpicker/js/bootstrap-clockpicker.min.js"></script>-->

        <!--<script src="./plugins/bootstrap-daterangepicker/daterangepicker.js"></script>-->

        <!--<script src="./plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>-->



        <!-- App js -->

        <!--<script src="assets/pages/jquery.form-pickers.init.js"></script>-->

        

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



        <!-- Bootstrap fileupload js -->

        <script src="./plugins/bootstrap-fileupload/bootstrap-fileupload.js"></script>



        <!-- Dropzone js -->

        <script src="./plugins/dropzone/dropzone.js"></script>

        

        <!-- Sweet Alert Js  -->

        <script src="./plugins/sweet-alert/sweetalert2.min.js"></script>

        <script src="assets/pages/jquery.sweet-alert.init.js"></script>



        <!-- App js -->

        <script src="assets/js/jquery.core.js"></script>

        <script src="assets/js/jquery.app.js"></script>





<script>



    $(document).on('click', '.delete', function (e) {

        e.preventDefault();

        var itemId = $(this).data('id');

        swal({

            title: 'Are you sure?',

            text: "You won't be able to revert this!",

            type: 'warning',

            showCancelButton: true,

            confirmButtonColor: '#3085d6',

            cancelButtonColor: '#d33',

            confirmButtonText: 'Yes, delete it!',

            showLoaderOnConfirm: true,

            preConfirm: function() {

                return new Promise(function(resolve) {

                    $.ajax({

                        url: '?delete='+itemId,

                        type: 'POST',

                        data: {id:itemId},

                        cache: false,

                    })

                    .done(function(response){

                        swal({

                            title:"Deleted!",

                            text:"Your file has been deleted successfully.",

                            type:"success",

                            allowOutsideClick:false

                        }).then(function(isConfirm){(isConfirm)?location.reload():""});

                    })

                    .fail(function(){

                        swal(response.title, response.message, response.type);

                    });

                });

            },



            allowOutsideClick: false

        })

    });



//Disabling autoDiscover

Dropzone.autoDiscover = false;

$(function() {

    //Dropzone class

    var myDropzone = new Dropzone(".dropzone", {

        url: "./includes/ajaxFile/upload_files.php",

        paramName: "file",

        maxFilesize: 512,

        maxFiles: 10,

        // acceptedFiles: "image/*,application/pdf",

        autoProcessQueue: false,

        init: function() {

            this.on('success', function(){

                if (this.getQueuedFiles().length == 0 && this.getUploadingFiles().length == 0) {

                    swal({

                        title:"Uploaded!",

                        text:"Your files are uploaded successfully.",

                        type:'success',allowOutsideClick:false

                    }).then(function(isConfirm){(isConfirm)?location.reload():""});

                }

            });

        }

    });

    $('#startUpload').click(function(){           

        myDropzone.processQueue();

    });

});

</script>



        <script type="text/javascript">

            $(document).ready(function() {



        var buttonConfig = [];

        var exportTitle = "All Locations"

        buttonConfig.push({extend:'excel',exportOptions: {columns: [ 0, 1, 2 ]} ,title: exportTitle,className: 'btn-success'});

        buttonConfig.push({extend:'pdf',exportOptions: {columns: [ 0, 1, 2 ]} ,title: exportTitle,className: 'btn-danger'});

        buttonConfig.push({extend:'print' ,exportOptions: {columns: [ 0, 1, 2 ]} ,title: exportTitle,className: 'btn-dark'});

        buttonConfig.push({text: '<i class="fa fa-plus"></i> Add File', action: function ( e, dt, button, config ) {$('#addModuleModal').modal('show') } ,className: 'btn-info'});



                $('form').parsley();

                

                //Buttons examples

                var table = $('#employee_vac').DataTable({

                    lengthChange: false,

                    buttons: buttonConfig,

                    // order: [[ 0, "desc" ]],

                    // "columnDefs": [

                    //              {

                    //              targets: [ 3 ],

                    //              visible: false,

                    //              searchable: false

                    //              },

                    //          ],

                    });

                

                    table.buttons().container()

                    .appendTo('#employee_vac_wrapper .col-md-6:eq(0)');

                

            });



        </script>



    </body>

</html>

<?php } ?>