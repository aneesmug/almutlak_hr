<?php

require_once __DIR__ . '/includes/session_check.php';
$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='" . $username . "'");
if (mysqli_num_rows($query) == 1) {
    include("./includes/avatar_select.php");
?>
    <!doctype html>
    <html lang="en">

    <head>
        <meta charset="utf-8" />
        <title><?= $site_title ?> - All Users</title>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <!--        <meta content="A fully featured admin theme which can be used to build CRM, CMS, etc." name="description" />-->
        <meta content="Anees Afzal" name="author" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />

        <!-- App favicon -->
        <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">

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

        <!-- Select2 CSS -->
        <link href="./plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />

        <!-- App css -->
        <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
        <script src="assets/js/modernizr.min.js"></script>

        <style type="text/css">
            .swal-wide {
                width: 850px !important;
            }
            
            .swal-landscape {
                width: 1200px !important;
                max-width: 95% !important;
            }
            
            .swal-landscape .swal2-html-container {
                max-height: 70vh !important;
                overflow-y: auto !important;
            }

            .dt-button-down-arrow {
                display: none !important;
            }
        </style>
        <?php if ($is_rtl): ?>
            <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
        <?php endif; ?>
		<script> window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;</script>

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
                                <img src="<?=get_setting($conDB, 'logo')?>" alt="" height="22">
                            </span>
                            <i>
                                <img src="<?=get_setting($conDB, 'white_logo')?>" alt="" height="28">
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
                                <?//= $msg ?>
                                <div class="card-box table-responsive">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h4 class="m-t-0 header-title">All Registerd Users</h4>
                                        <button type="button" class="btn btn-primary btn-sm waves-effect waves-light createUserDeptAjax">
                                            <i class="mdi mdi-plus-circle mr-2"></i>Add User
                                        </button>
                                    </div>

                                    <div id="response"></div>

                                    <div class="row pb-3 border-bottom">
                                        <div class="col-md-3 mb-2">
                                            <label for="filterStatus" class="form-label small font-weight-bold d-block mb-1">Filter by Status</label>
                                            <div class="user_status"></div>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <label for="filterDept" class="form-label small font-weight-bold d-block mb-1">Filter by Department</label>
                                            <div class="user_department"></div>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <label for="filterRole" class="form-label small font-weight-bold d-block mb-1">Filter by Role</label>
                                            <div class="user_role"></div>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <label for="filterCompany" class="form-label small font-weight-bold d-block mb-1">Filter by Company</label>
                                            <div class="user_company"></div>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <label for="filterAllowedDept" class="form-label small font-weight-bold d-block mb-1">Filter by Allowed Department</label>
                                            <div class="user_allowed_dept"></div>
                                        </div>
                                    </div>


                                    <table id="employee_vac" class="table table-striped table-bordered dt-responsive nowrap employee_vac" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th></th>
                                                <th>ID/IQAMA</th>
                                                <th>Employee ID</th>
                                                <th>Employee Name</th>
                                                <th>Department</th>
                                                <th>Mobile</th>
                                                <th>User Type</th>
                                                <th>Allowed Companies</th>
                                                <th>Allowed Departments</th>
                                                <th>Status</th>
                                                <th style="width: 30px">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>


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
        <script src="./plugins/datatables/dataTables.select.min.js"></script>
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

        <!-- Select2 JS -->
        <script src="./plugins/select2/js/select2.min.js" type="text/javascript"></script>

        <!-- App js -->
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js"></script>

        <script type="text/javascript">
            var userTable; // Global variable to store table instance
            $(document).ready(function() {
                $('form').parsley();
            });
        </script>
        <script type="text/javascript">
            $('.editAttr').click(function() {
                var id = $(this).data('id');
                var name = $(this).data('name');
                $('#id').val(id);
                $('#name').val(name);
            });
        </script>

        <script type="text/javascript">
            $(document).ready(function() {
                // Use event delegation for dynamically created checkboxes
                $(document).on('change', 'input[type="checkbox"].user-status-checkbox', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    var status = ($(this).is(':checked')) ? '1' : '0';
                    var id = $(this).val();
                    
                    console.log('Checkbox changed - Status: ' + status + ', ID: ' + id);
                    
                    $.ajax({
                        url: "update_user.php",
                        method: "POST",
                        data: {
                            status: status,
                            id: id,
                        },
                        dataType: "json",
                        timeout: 10000,
                        success: function(data) {
                            console.log('AJAX Success:', data);
                            Swal.fire({
                                title: "Updated!",
                                text: "Successfully update this user.",
                                icon: "success",
                                allowOutsideClick: false,
                                confirmButtonText: 'OK',
                                willClose: function() {
                                    console.log('Modal closed');
                                    // Explicitly prevent any default behavior
                                    return false;
                                }
                            }).then(function(result) {
                                console.log('Result:', result);
                                if (result.isConfirmed && typeof userTable !== 'undefined') {
                                    console.log('Calling table.draw()');
                                    userTable.draw(false); // Refresh table without resetting pagination
                                }
                            });
                        },
                        error: function(xhr, status, error) {
                            console.log('AJAX Error:', error, xhr.status, xhr.responseText);
                            Swal.fire({
                                title: "Error!",
                                text: "Failed to update user status. " + error,
                                icon: "error",
                                allowOutsideClick: false
                            });
                        }
                    });
                    
                    return false;
                });
            });
        </script>


        <script type="text/javascript">
            $(document).ready(function() {
                //Buttons examples
                var buttonConfig = [];
                var columnNum = [2, 3, 4, 5, 6, 7, 8, 9];
                buttonConfig.push({
                    extend: 'copy',
                    text: '<i class="mdi mdi-content-copy text-info mr-1"></i>Copy',
                    exportOptions: {
                        columns: columnNum
                    }
                });
                buttonConfig.push({
                    extend: 'excel',
                    text: '<i class="mdi mdi-file-excel text-success mr-1"></i>Excel',
                    exportOptions: {
                        columns: columnNum
                    }
                });
                buttonConfig.push({
                    extend: 'csv',
                    text: '<i class="mdi mdi-file-document mr-1"></i>CSV',
                    exportOptions: {
                        columns: columnNum
                    }
                });
                buttonConfig.push({
                    extend: 'pdf',
                    text: '<i class="mdi mdi-file-pdf text-danger mr-1"></i>PDF',
                    exportOptions: {
                        columns: columnNum
                    }
                });
                buttonConfig.push({
                    extend: 'print',
                    text: '<i class="mdi mdi-printer text-primary mr-1"></i>Print',
                    exportOptions: {
                        columns: columnNum
                    }
                });
                // Variable declaration for table
                var statusObj = {
                    0: {
                        title: 'Inactive',
                        class: 'badge-border-danger'
                    },
                    1: {
                        title: 'Active',
                        class: 'badge-border-success'
                    },
                };
                var employeeTypeObj = {
                    'administrator': {
                        title: 'Administrator'
                    },
                    'hr': {
                        title: 'Human Resource'
                    },
                    'hr_senior_bp': {
                        title: 'HR Senior BP'
                    },
                    'dept_user': {
                        title: 'Department Manager'
                    },
                    'assistant': {
                        title: 'Assistant Manager'
                    },
                    'employee': {
                        title: 'Employee'
                    },
                    'general_manager': {
                        title: 'General Manager'
                    },
                    // Legacy compatibility
                    'gm': {
                        title: 'General Manager'
                    },
                };
                // Store table instance globally so it can be accessed from callbacks
                var table = $('#employee_vac').DataTable({
                    lengthChange: false,
                    dom: 'Bfrtip',
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: './includes/ajaxFile/getAllUsersData.php',
                        type: 'POST',
                        dataType: 'json'
                    },
                    buttons: [{
                        extend: 'collection',
                        className: 'btn-dark',
                        text: '<i class="icon-share-alt me-1 ti-xs"></i> Export',
                        buttons: [buttonConfig]
                    }],
                    order: [
                        [0, "desc"]
                    ],
                    columnDefs: [{
                            targets: 0,
                            visible: false,
                            searchable: false,
                            render: function(data, type, full, meta) {
                                return '';
                            }
                        },
                        {
                            // Checkbox column
                            targets: 1,
                            render: function(data, type, row, meta) {
                                return `<input type="checkbox" name="status" class="user-status-checkbox" value="${row[0]}" />`;
                            }
                        },
                        {
                            // User Status - Updated to column 10
                            targets: 10,
                            render: function(data, type, row, meta) {
                                if (statusObj[data]) {
                                    return (`<span class="badge-border ${statusObj[data].class}" text-capitalized>${statusObj[data].title}</span>`);
                                }
                                return data;
                            }
                        },
                        {
                            // User Type/Role
                            targets: 7,
                            render: function(data, type, row, meta) {
                                // Check if user type exists in our mapping, otherwise show the raw value
                                if (employeeTypeObj[data]) {
                                    return employeeTypeObj[data].title;
                                }
                                // Fallback for unknown types
                                return data.charAt(0).toUpperCase() + data.slice(1).replace(/_/g, ' ');
                            }
                        },
                        {
                            // Action column - Updated to column 11
                            targets: 11,
                            render: function(data, type, row, meta) {
                                var recordData = row[12];  // Updated index for full record data
                                var html = `<div class='btn-group dropdown'>
                                    <a href='javascript: void(0);' class='table-action-btn dropdown-toggle arrow-none btn btn-light btn-sm' data-toggle='dropdown' aria-expanded='false'><i class='mdi mdi-dots-horizontal'></i></a>
                                    <div class='dropdown-menu dropdown-menu-right' x-placement='bottom-end'>
                                        <a href='javascript:void(0);' class='dropdown-item text-custom editUserAttr updateUserAjax' 
                                            data-id="${recordData.lid}" 
                                            data-fullname="${recordData.efullname || ''}" 
                                            data-dept="${recordData.deptnme || ''}" 
                                            data-email="${recordData.email || ''}" 
                                            data-user_type="${recordData.user_type}" 
                                            data-status="${recordData.status || 0}" 
                                            data-allowed_companies="${(recordData.allowed_companies || '').replace(/"/g, '&quot;')}"
                                            data-allowed_departments="${(recordData.allowed_departments || '').replace(/"/g, '&quot;')}">
                                            <i class='fa fa-edit mr-2 font-18 vertical-middle'></i>Edit
                                        </a>`;
                                
                                // Add delete option if user is admin
                                var userType = '<?= $user_type ?>';
                                var accessLevel = '<?= $access1 ?>';
                                if (userType == accessLevel) {
                                    html += `<a href='javascript:void(0);' class='dropdown-item text-danger deleteAjax' 
                                        data-id='${row[0]}' 
                                        data-tbl='admin_login' 
                                        data-file='0'>
                                        <i class='fa fa-trash mr-2 font-18 vertical-middle'></i>Delete
                                    </a>`;
                                }
                                
                                html += `</div></div>`;
                                return html;
                            }
                        }
                    ],
                    initComplete: function() {
                        var table = this.api();
                        var selectOpt = `<select class="form-control select2-single" style="width: 100%;"><option value=""></option></select>`;
                        
                        // Get unique values for filters from server-side data
                        var statusOptions = ['Active', 'Inactive'];
                        var roleOptions = Object.keys(employeeTypeObj);
                        var deptOptions = new Set();
                        var companyOptions = new Set();
                        
                        // Adding status filter - Column 10 (updated from 8)
                        var statusSelect = $(selectOpt).appendTo('.user_status').on('change', function() {
                            var val = $(this).val();
                            table.column(10).search(val, true, false).draw();
                        });
                        statusOptions.forEach(function(status) {
                            statusSelect.append(`<option value="${status}">${status}</option>`);
                        });
                        statusSelect.select2({
                            placeholder: 'Select Status',
                            allowClear: true,
                            width: '100%'
                        });
                        
                        // Adding department filter - Column 5
                        var deptSelect = $(selectOpt).appendTo('.user_department').on('change', function() {
                            var val = $(this).val();
                            table.column(5).search(val, true, false).draw();
                        });
                        
                        // Adding role filter - Column 7
                        var roleSelect = $(selectOpt).appendTo('.user_role').on('change', function() {
                            var val = $(this).val();
                            table.column(7).search(val, true, false).draw();
                        });
                        roleOptions.forEach(function(role) {
                            var displayText = employeeTypeObj[role].title;
                            roleSelect.append(`<option value="${role}">${displayText}</option>`);
                        });
                        roleSelect.select2({
                            placeholder: 'Select User Type',
                            allowClear: true,
                            width: '100%'
                        });
                        
                        // Adding company filter - Column 8
                        var companySelect = $(selectOpt).appendTo('.user_company').on('change', function() {
                            var val = $(this).val();
                            table.column(8).search(val, false, false).draw();
                        });
                        
                        // Adding allowed department filter - Column 9
                        var allowedDeptSelect = $(selectOpt).appendTo('.user_allowed_dept').on('change', function() {
                            var val = $(this).val();
                            table.column(9).search(val, false, false).draw();
                        });
                        
                        // Fetch all data to populate departments, companies, and allowed departments
                        $.ajax({
                            url: './includes/ajaxFile/getAllUsersData.php',
                            type: 'POST',
                            data: {
                                draw: 1,
                                start: 0,
                                length: 5000,
                                search: { value: '' },
                                columns: [
                                    {data: 'id', search: {value: ''}},
                                    {data: 'checkbox', search: {value: ''}},
                                    {data: 'iqama', search: {value: ''}},
                                    {data: 'emp_id', search: {value: ''}},
                                    {data: 'name', search: {value: ''}},
                                    {data: 'dept', search: {value: ''}},
                                    {data: 'mobile', search: {value: ''}},
                                    {data: 'user_type', search: {value: ''}},
                                    {data: 'allowed_companies', search: {value: ''}},
                                    {data: 'allowed_departments', search: {value: ''}},
                                    {data: 'status', search: {value: ''}},
                                    {data: 'action', search: {value: ''}}
                                ]
                            },
                            dataType: 'json',
                            success: function(response) {
                                var allowedDeptOptions = new Set();
                                if (response.data && response.data.length > 0) {
                                    response.data.forEach(function(row) {
                                        // Collect unique departments from column 5
                                        if (row[5] && row[5].trim()) {
                                            deptOptions.add(row[5].trim());
                                        }
                                        // Collect unique companies from column 8
                                        if (row[8] && row[8].trim()) {
                                            var companies = row[8].split(',').map(c => c.trim()).filter(c => c);
                                            companies.forEach(function(company) {
                                                companyOptions.add(company);
                                            });
                                        }
                                        // Collect unique allowed departments from column 9
                                        if (row[9] && row[9].trim()) {
                                            var depts = row[9].split(',').map(d => d.trim()).filter(d => d);
                                            depts.forEach(function(dept) {
                                                allowedDeptOptions.add(dept);
                                            });
                                        }
                                    });
                                }
                                
                                // Populate department select
                                Array.from(deptOptions).sort().forEach(function(dept) {
                                    if (dept) {
                                        deptSelect.append(`<option value="${dept}">${dept}</option>`);
                                    }
                                });
                                
                                // Initialize Select2 for department (after options added)
                                deptSelect.select2({
                                    placeholder: 'Select Department',
                                    allowClear: true,
                                    width: '100%'
                                });
                                
                                // Populate company select
                                Array.from(companyOptions).sort().forEach(function(company) {
                                    companySelect.append(`<option value="${company}">${company}</option>`);
                                });
                                
                                // Initialize Select2 for company (after options added)
                                companySelect.select2({
                                    placeholder: 'Select Company',
                                    allowClear: true,
                                    width: '100%'
                                });
                                
                                // Populate allowed department select
                                Array.from(allowedDeptOptions).sort().forEach(function(dept) {
                                    allowedDeptSelect.append(`<option value="${dept}">${dept}</option>`);
                                });
                                
                                // Initialize Select2 for allowed department (after options added)
                                allowedDeptSelect.select2({
                                    placeholder: 'Select Allowed Department',
                                    allowClear: true,
                                    width: '100%'
                                });
                            }
                        });
                    }
                });
                table.buttons().container().appendTo('#employee_vac_wrapper .col-md-6:eq(0)');
                userTable = table; // Store globally for use in callbacks
            });
            jQuery(function($) {
                $('.autonumber').autoNumeric('init');
            });
            jQuery.browser = {};
            (function() {
                jQuery.browser.msie = false;
                jQuery.browser.version = 0;
                if (navigator.userAgent.match(/MSIE ([0-9]+)\./)) {
                    jQuery.browser.msie = true;
                    jQuery.browser.version = RegExp.$1;
                }
            })();
        </script>

        <script type="text/javascript">
            $(document).ready(function() {
                $("input[name$='note']").click(function() {
                    var value = $(this).val();
                    if (value == 'Encashed') {
                        $("#return_date").show();
                        $("#note").hide();
                        $("#return_date").removeAttr('required');
                        $("#permit_no").removeAttr('required');
                    } else if (value == 'Fly') {
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


            $(document).ready(function() {

                var buttonConfig = [];
                var columnsConfig = [1, 2, 3, 4, 5, 6, 7, 8];
                var exportTitle = "All Expiry ID Employees";

                buttonConfig.push({
                    extend: 'excel',
                    exportOptions: {
                        columns: columnsConfig
                    },
                    title: exportTitle,
                    className: 'btn-success'
                });
                buttonConfig.push({
                    extend: 'pdf',
                    exportOptions: {
                        columns: columnsConfig
                    },
                    title: exportTitle,
                    className: 'btn-danger'
                });
                buttonConfig.push({
                    extend: 'print',
                    exportOptions: {
                        columns: columnsConfig
                    },
                    title: exportTitle,
                    className: 'btn-dark'
                });

                $('.fileexport').DataTable({
                    dom: 'Bfrtip',
                    responsive: true,
                    buttons: buttonConfig,
                    order: [
                        [0, "desc"]
                    ],
                    "columnDefs": [{
                        targets: [0],
                        visible: false,
                        searchable: false
                    }, ],
                });
            });
        </script>


        <script type="text/javascript">
            $(document).ready(function() {
                // Edit user information START
                $(document).on('click', "#submitForm", function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    var id = $('input[name=id]').val();
                    var fullname = $('input[name=fullname]').val();
                    var username = $('input[name=username]').val();
                    
                    console.log('Submit form clicked - ID: ' + id);
                    
                    if (fullname == "" || username == "") {
                        $("#response").fadeIn();
                        $("#response").html("<div class='alert alert-danger'><strong>Error!</strong> All fields are required.</div>");
                    } else {
                        $.ajax({
                            url: "./includes/ajaxFile/edit_user.php",
                            type: "POST",
                            data: $('#submitEditUserForm').serialize(),
                            dataType: "json",
                            timeout: 10000,
                            success: function(res) {
                                console.log('Edit AJAX Success:', res);
                                setTimeout(function() {
                                    Swal.fire({
                                        title: res.title,
                                        text: res.message,
                                        icon: res.type,
                                        allowOutsideClick: false,
                                        confirmButtonText: 'OK',
                                        willClose: function() {
                                            console.log('Edit Modal closed');
                                            return false;
                                        }
                                    }).then(function(result) {
                                        console.log('Edit Result:', result);
                                        if (result.isConfirmed && typeof userTable !== 'undefined') {
                                            console.log('Calling table.draw() from edit form');
                                            userTable.draw(false); // Refresh table without resetting pagination
                                        }
                                    });
                                }, 1);
                            },
                            error: function(xhr, status, error) {
                                console.log('Edit AJAX Error:', error, xhr.status, xhr.responseText);
                                Swal.fire({
                                    title: "Error!",
                                    text: "Failed to update user. " + error,
                                    icon: "error",
                                    allowOutsideClick: false
                                });
                            }
                        });
                    }
                    
                    return false;
                });
            });
        </script>

    </body>

    </html>
<?php } ?>