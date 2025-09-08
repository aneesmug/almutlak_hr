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
        <title><?=$site_title ?> - All Users</title>
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
        <link href="./plugins/datatables/buttons.dataTables.min.css" rel="stylesheet" type="text/css" />

        <!-- <link href="./plugins/datatables/jquery.dataTables.min.css" rel="stylesheet" type="text/css" />
        <link href="./plugins/datatables/select.dataTables.min.css" rel="stylesheet" type="text/css" />
        <link href="./plugins/datatables/editor.dataTables.min.css" rel="stylesheet" type="text/css" />
        <link href="./plugins/datatables/dataTables.dateTime.min.css" rel="stylesheet" type="text/css" />
        <link href="./plugins/datatables/dataTables.dateTime.min.css" rel="stylesheet" type="text/css" />
        <link href="./plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
        <link href="./plugins/datatables/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" /> -->

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

        <style type="text/css">.swal-wide{ width:850px !important;} .dt-button-down-arrow{display:none !important;}</style>

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
  <?=$msg?>
  <div class="card-box table-responsive">
   <h4 class="m-t-0 header-title">All Registerd Users</h4>
   
<div id="response"></div>

<div class="d-flex justify-content-between align-items-center row pb-2 gap-3 gap-md-0">
    <div class="col-md-3 user_status mb-2"></div>
    <div class="col-md-3 user_type mb-2"></div>
    <div class="col-md-3 user_department mb-2">
        <select class="custom-select form-select input-sm" id="user_department"><option value=""> All Departments </option></select>
    </div>
    <div class="col-md-3 mb-2">
        <input type="search" name="search" class="form-control" placeholder="Search:" id="search" autocomplete="off">
    </div>
</div>


<table id="employee_vac" class="table table-striped table-bordered dt-responsive nowrap employee_vac" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
 <thead>
  <tr>
   <th>ID</th>
   <th>Employee Name</th>
   <th>Department</th>
   <th>Username</th>
   <th>Mobile</th>
   <th>Email</th>
   <th>User Type</th>
   <th>Status</th>
   <th style="width: 30px">Action</th>
  </tr>
 </thead>
 <?php /* ?>
 <tbody>
<?php
 // $sql_emp_vac = "SELECT * FROM `admin_login` WHERE `id` > 1 ORDER BY `id` DESC";
 $sql_emp_vac = "
    SELECT `employees`.*, `employees`.`name` AS `efullname`, `admin_login`.`id` AS `lid`, `admin_login`.* 
    FROM `employees` 
    LEFT JOIN `admin_login` 
        ON `employees`.`emp_id` = `admin_login`.`emp_id`
    WHERE `admin_login`.`id` > 1 
    -- AND `employees`.`status` = 1 
    ORDER BY `admin_login`.`id` DESC";

$query_emp_vac = mysqli_query($conDB, $sql_emp_vac);
while ($rec = mysqli_fetch_array($query_emp_vac)) {
    $id_user_usr = $rec["lid"];
    $firstnme_usr = $rec["efullname"];
    $usernme_usr = $rec["username"];
    $usrty_usr = $rec["user_type"];
    $dept_usr = $rec["dept"];
    $email_usr = $rec["email"];
    $mobile_usr = $rec["mobile"];
    $status_usr = $rec["status"];
    $password_usr = $rec["password"];
    $avatar_usr = $rec["avatar"];

    // $usertype_usr = 
    //     ($usrty_usr == "hr" ? "Human Resource":
    //         ($usrty_usr == "dept_user" ? "Department Manager":
    //             ($usrty_usr == "assistant" ? "Assistant Manager":
    //                 ($usrty_usr == "employee" ? "Employee":
    //                     ($usrty_usr == "gm" ? "General Manager":"")
    //                 )
    //             )
    //         )
    //     );
?>
    <tr>
        <th><?=$id_user_usr; ?></th>
        <th><input type="checkbox" name="status" id="id" value="<?=$id_user_usr ?>" <?=($status_usr == 1)?"checked":"" ?> /></th>
        <th><?=$firstnme_usr ?></th>
        <th><?=$dept_usr; ?></th>
        <th><?=$usernme_usr; ?></th>
        <th><?=$mobile_usr; ?></th>
        <th><?=$usrty_usr; ?></th>
        <th><?=$status_usr;
            // ($status_usr == 1)?"<span class='badge badge-success'>Active</span>" : "<span class='badge badge-danger'>Inactive</span>";
        ?>
        </th>
        <th>
            <div class='btn-group dropdown'>
                <a href='javascript: void(0);' class='table-action-btn dropdown-toggle arrow-none btn btn-light btn-sm' data-toggle='dropdown' aria-expanded='false'><i class='mdi mdi-dots-horizontal'></i></a>
                <div class='dropdown-menu dropdown-menu-right' x-placement='bottom-end' >
                <a href='javascript:void(0);' class='dropdown-item text-custom editUserAttr updateUserAjax' data-id="<?=$rec['id']?>" data-fullname="<?=$rec['fullname']?>" data-dept="<?=$rec['dept']?>" data-username="<?=$rec['username']?>" data-email="<?=$rec['email']?>" data-mobile="<?=$rec['mobile']?>" data-status="<?=$rec['status']?>" data-user_type="<?=$rec['user_type']?>" data-email_pass="<?=$rec['email_pass']?>" data-oldpass="<?=$rec['bk_password']?>" ><i class='fa fa-edit mr-2 font-18 vertical-middle'></i>Edit</a>
                    <?php if($user_type == $access1){ ?>
                    <a href='javascript:void(0);' class='dropdown-item  text-danger deleteAjax' data-id='<?=$id_user_usr?>' data-tbl='admin_login' data-file='0' ><i class='fa fa-trash mr-2 font-18 vertical-middle'></i>Delete</a>
                    <?php } ?>
                </div>
            </div>
        </th>
    </tr>
<?php } ?>
          </tbody> <?php */ ?>
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
        <script src="./plugins/datatables/dataTables.editor.min.js"></script>
        <script src="./plugins/datatables/dataTables.select.min.js"></script>
        <script src="./plugins/datatables/dataTables.dateTime.min.js"></script>
        <script src="./plugins/datatables/dataTables.colReorder.min.js"></script>
        <script src="./plugins/datatables/dataTables.buttons.min.js"></script>
        <script src="./plugins/datatables/jszip.min.js"></script>
        <script src="./plugins/datatables/pdfmake.min.js"></script>
        <script src="./plugins/datatables/vfs_fonts.js"></script>
        <script src="./plugins/datatables/buttons.html5.min.js"></script>
        <script src="./plugins/datatables/buttons.print.min.js"></script>
        <script src="./plugins/datatables/dataTables.bootstrap4.min.js"></script>
        <script src="./plugins/datatables/buttons.bootstrap4.min.js"></script>

        <!-- Buttons examples -->

        <!-- Key Tables -->
        <script src="./plugins/datatables/dataTables.keyTable.min.js"></script>

        <!-- Responsive examples -->
        <script src="./plugins/datatables/dataTables.responsive.min.js"></script>
        <script src="./plugins/datatables/responsive.bootstrap4.min.js"></script>

        <!-- Selection table -->
    
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.0/jquery.validate.js"></script>


        <!-- App js -->
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js"></script>


<!--     <script type="text/javascript">
        $(document).ready(function(){
           $('input[type="checkbox"]').click(function(){
                var status = ($(this).is(':checked')) ? '1' : '0';
                var id = $(this).val();
                $.ajax({
                     url:"update_user.php",
                     method:"POST",
                     data:{status:status,id:id,},
                     success: function(data){
                        Swal.fire({
                            title:"Updated!",
                            text:"Successfully update this user.",
                            icon:"success",
                            allowOutsideClick:false
                        }).then(function(isConfirm){(isConfirm)?location.reload():""});
                    },
               });
           });
        });
    </script> -->


<script type="text/javascript">
    $(document).ready(function() {

        //Buttons examples
        var buttonConfig = [];
        var columnNum = [2, 3, 4, 5, 6];
        buttonConfig.push({extend: 'copy',text: '<i class="mdi mdi-content-copy text-info mr-1"></i>Copy',exportOptions: {columns: columnNum}});
        buttonConfig.push({extend: 'excel',text: '<i class="mdi mdi-file-excel text-success mr-1"></i>Excel',exportOptions: {columns: columnNum}});
        buttonConfig.push({extend: 'csv',text: '<i class="mdi mdi-file-document mr-1"></i>CSV',exportOptions: {columns: columnNum}});
        buttonConfig.push({extend: 'pdf',text: '<i class="mdi mdi-file-pdf text-danger mr-1"></i>PDF',exportOptions: {columns: columnNum}});
        buttonConfig.push({extend: 'print',text: '<i class="mdi mdi-printer text-primary mr-1"></i>Print',exportOptions: {columns: columnNum}});
        // Variable declaration for table
        var statusObj = {
            0: { title: 'Inactive', class: 'badge-border-danger' },
            1: { title: 'Active', class: 'badge-border-success' },
        };
        var employeeTypeObj = {
            'hr'        : { title: 'Human Resource'},
            'dept_user' : { title: 'Department Manager'},
            'assistant' : { title: 'Assistant Manager'},
            'employee'  : { title: 'Employee'},
            'gm'        : { title: 'General Manager'},
        };
        var table = $('#employee_vac').DataTable({
            'lengthChange': false,
            'dom': 'Bfrtip',
            "processing": true,
            "serverSide": true,
            'buttons': [
                {extend: 'collection',className: 'btn-dark',text: '<i class="icon-share-alt me-1 ti-xs"></i> Export',buttons: [buttonConfig]}
            ],
            'order': [[ 0, "desc" ]],
            'columnDefs': [
                {
                    targets:0,
                    visible: false,
                    searchable: false,
                    render: function (data, type, full, meta) {
                        return full['id'];
                    }
                },
                {
                    targets: 7,
                    render: function ( data, type, row, meta ) {
                        return (`<span class="badge-border ${statusObj[data].class}" text-capitalized>${statusObj[data].title}</span>`);
                    }
                },
                {
                    targets: 6,
                    render: function ( data, type, row, full ) {
                        return employeeTypeObj[row['user_type']].title;
                    }
                },
                {
                    targets: -1,
                    title: 'Action',
                    render: function ( data, type, row, full ) {
                        return (`
            <div class='btn-group dropdown'>
                <a href='javascript: void(0);' class='table-action-btn dropdown-toggle arrow-none btn btn-light btn-sm' data-toggle='dropdown' aria-expanded='false'><i class='mdi mdi-dots-horizontal'></i></a>
                <div class='dropdown-menu dropdown-menu-right' x-placement='bottom-end' >
                <a href='javascript:void(0);' class='dropdown-item text-custom editUserAttr updateUserAjax' data-id="${row['id']}" data-fullname="${row['fullname']}" data-dept="${row['dept']}" data-username="${row['username']}" data-email="${row['email']}" data-mobile="${row['mobile']}" data-status="${row['status']}" data-user_type="${row['user_type']}" data-email_pass="${row['email_pass']}" data-oldpass="${row['bk_password']}" ><i class='fa fa-edit mr-2 font-18 vertical-middle'></i>Edit</a>
                    <a href='javascript:void(0);' class='dropdown-item  text-danger deleteAjax' data-id='${row['id']}' data-tbl='admin_login' data-file='0' ><i class='fa fa-trash mr-2 font-18 vertical-middle'></i>Delete</a>
                </div>
            </div>
                            `);
                    }
                },
            ],
            'ajax': {
                url:'./includes/ajaxFile/ajaxAdminUser.php',
                type: "POST", // or "GET", depending on your server-side script
                data: function (d) {
                    d.user_status       = $('#user_status').val();
                    d.user_department   = $('#user_department').val();
                    d.user_type         = $('#user_type').val();
                    d.search            = $('#search').val();
                },
            },
            'columns': [
                { 'data': 'lid'},
                { 'data': 'efullname'},
                { 'data': 'dept'},
                { 'data': 'username'},
                { 'data': 'mobile'},
                { 'data': 'email'},
                { 'data': 'user_type'},
                { 'data': 'status'},
                { 'data': ''},
            ],
            'language': {
                "processing": 'Loading data...'
            },
            'initComplete': function () {
                $.ajax({
                    url: './includes/ajaxFile/ajaxLocation.php',
                    dataType: 'JSON',
                    type: 'POST',
                    data: {ajaxType: 'loc_department'},
                    /*beforeSend : function(){
                        $('#loading-image').css("visibility", "visible");
                    },*/
                    success: function(res) {
                        if (res.status == 200) {
                            let options = '';
                            for (let i in res.data)
                                options += `<option value="${res.data[i].dep_nme}">${res.data[i].dep_nme}</option>`;
                            $('#user_department').append(options);
                        }
                    },
                    /*complete: function(){
                        $('#loading-image').css("visibility", "hidden");
                    },*/
                    error: function(j, e) {
                        errorHandling(j, e)
                    },
                });
                // Adding status filter once table initialized
                this.api().columns(7).every(function () {
                    var user_status = `<select class="custom-select form-select input-sm" id="user_status"><option value=""> All Status </option></select>`;
                    var column = this;
                    var select = $(user_status).appendTo('.user_status').on('change', function () {
                        var val = $.fn.dataTable.util.escapeRegex($(this).val());
                        column.search(val ? '^' + val + '$' : '', true, false).draw();
                    });
                    column.data().unique().sort().each(function (d, j) {
                        select.append(`<option value="${d}" class="text-capitalize">${statusObj[d].title}</option>`);
                    });
                });
                // Adding user type filter once table initialized
                this.api().columns(6).every(function () {
                    var user_type = `<select class="custom-select form-select input-sm" id="user_type"><option value=""> All User Type </option></select>`;
                    var column = this;
                    var select = $(user_type).appendTo('.user_type').on('change', function () {
                        var val = $.fn.dataTable.util.escapeRegex($(this).val());
                        column.search(val ? '^' + val + '$' : '', true, false).draw();
                    });
                    column.data().unique().sort().each(function (d, j) {
                        select.append(`<option value="${d}">${d}</option>`);
                    });
                });
            }
        });

    $('#user_status').change(function () {
        table.draw();
    });
    $('#user_department').change(function () {
        table.draw();
    });
    $('#user_type').change(function () {
        table.draw();
    });
    $('#search').keyup(function(){
        table.search($(this).val()).draw() ;
    });
    $('#employee_vac_filter').remove();

    table.buttons().container().appendTo('#employee_vac_wrapper .col-md-6:eq(0)');
    });
 
</script>

<script type="text/javascript">

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
            });
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