<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';

// Restrict access to non-employee users only
if ($user_type == 'employee') {
    header('Location: dashboard.php');
    exit;
}

$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
if(mysqli_num_rows($query) == 1){
    include("./includes/avatar_select.php");
}

?>
<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>
<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?> - <?=__('all_general_requests', 'All General Requests')?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="Anees Afzal" name="author" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

    <!-- App favicon -->
    <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">

    <!-- Modal -->
    <link href="./plugins/custombox/css/custombox.min.css" rel="stylesheet">

    <!-- Plugins css -->
    <link href="./plugins/bootstrap-select/css/bootstrap-select.min.css" rel="stylesheet" />
    <link href="./plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
    
    <!-- DataTables -->
    <link href="./plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="./plugins/datatables/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="./plugins/datatables/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="./plugins/datatables/select.bootstrap4.min.css" rel="stylesheet" type="text/css" />

    <!-- App css -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
    <script src="assets/js/modernizr.min.js"></script>
    
    <?php if ($is_rtl): ?>
        <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
    <?php endif; ?>
    <script> window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;</script>
</head>

<body class="enlarged" data-keep-enlarged="true">
    <div id="wrapper">
        <!-- Left Sidebar -->
        <div class="left side-menu">
            <div class="slimscroll-menu" id="remove-scroll">
                <div class="topbar-left">
                    <a href="dashboard.php" class="logo">
                        <span><img src="<?=get_setting($conDB, 'logo')?>" alt="" height="22"></span>
                        <i><img src="<?=get_setting($conDB, 'white_logo')?>" alt="" height="28"></i>
                    </a>
                </div>
                <?php include("./includes/main_menu.php"); ?>
                <div class="clearfix"></div>
            </div>
        </div>

        <!-- Content Page -->
        <div class="content-page">
            <?php include("./includes/topbar.php"); ?>
            
            <div class="content">
                <div class="container-fluid">
                    <?php if (isset($_GET['error']) && $_GET['error'] === 'request_not_found'): ?>
                        <div class="alert alert-danger bg-danger text-white border-0" role="alert">
                            <?= __('error_request_not_found', 'The requested item was not found or the link is invalid.') ?>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-12">
                            <div class="card-box table-responsive">
                                <h4 class="m-t-0 header-title">
                                    <i class="mdi mdi-file-document-box-multiple-outline mr-2"></i>
                                    <?=__('all_general_requests', 'All General Requests')?>
                                </h4>

                                <!-- Filters -->
                                <div class="row mb-3">
                                    <div class="col-md-2 offset-md-8">
                                        <div class="form-group" style="margin-bottom: 0 !important">
                                            <select class="form-control" name="status_filter" id="statusFilter">
                                                <option value=""><?=__('all_statuses_option')?></option>
                                                <option value="draft"><?=__('draft_status')?></option>
                                                <option value="pending_approval" selected><?=__('pending_approval')?></option>
                                                <option value="approved"><?=__('approved')?></option>
                                                <option value="rejected"><?=__('rejected')?></option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <input type="search" name="search" class="form-control" placeholder="<?=__('search_placeholder')?>" id="search" autocomplete="off">
                                        </div>
                                    </div>
                                </div>

                                <!-- DataTable -->
                                <table id="generalRequestsTbl" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                    <thead>
                                        <tr>
                                            <th><?=__('id')?></th>
                                            <th><?=__('request_number', 'Request No.')?></th>
                                            <th><?=__('request_title', 'Title')?></th>
                                            <th><?=__('target_department', 'Target Dept.')?></th>
                                            <th><?=__('category')?></th>
                                            <th><?=__('priority')?></th>
                                            <th><?=__('requester', 'Requester')?></th>
                                            <th><?=__('created_at')?></th>
                                            <th><?=__('status')?></th>
                                            <th width="60"><?=__('action')?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Data will be loaded via AJAX -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <footer class="footer"><?=$site_footer?></footer>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/metisMenu.min.js"></script>
    <script src="assets/js/waves.js"></script>
    <script src="assets/js/jquery.slimscroll.js"></script>

    <!-- DataTables -->
    <script src="./plugins/datatables/jquery.dataTables.min.js"></script>
    <script src="./plugins/datatables/dataTables.bootstrap4.min.js"></script>
    <script src="./plugins/datatables/dataTables.buttons.min.js"></script>
    <script src="./plugins/datatables/buttons.bootstrap4.min.js"></script>
    <script src="./plugins/datatables/jszip.min.js"></script>
    <script src="./plugins/datatables/pdfmake.min.js"></script>
    <script src="./plugins/datatables/vfs_fonts.js"></script>
    <script src="./plugins/datatables/buttons.html5.min.js"></script>
    <script src="./plugins/datatables/buttons.print.min.js"></script>
    <script src="./plugins/datatables/dataTables.responsive.min.js"></script>
    <script src="./plugins/datatables/responsive.bootstrap4.min.js"></script>

    <!-- App js -->
    <script src="assets/js/jquery.core.js"></script>
    <script src="assets/js/jquery.app.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script type="text/javascript">
        $(document).ready(function(){
            var buttonConfig = [];
            var exportTitle = "<?=__('all_general_requests', 'All General Requests')?>";
            var columnNum = [ 1, 2, 3, 4, 5, 6, 7, 8 ];
            
            // Status object for badges
            var statusObj = {
                'draft':            { title: '<?=__('draft_status')?>', class: 'badge-secondary' },
                'pending_approval': { title: '<?=__('pending_approval')?>', class: 'badge-warning' },
                'approved':         { title: '<?=__('approved')?>', class: 'badge-success' },
                'rejected':         { title: '<?=__('rejected')?>', class: 'badge-danger' }
            };

            // Priority object for badges
            var priorityObj = {
                'low':    { title: '<?=__('low_priority', 'Low')?>', class: 'badge-info' },
                'medium': { title: '<?=__('medium_priority', 'Medium')?>', class: 'badge-primary' },
                'high':   { title: '<?=__('high_priority', 'High')?>', class: 'badge-warning' },
                'urgent': { title: '<?=__('urgent_priority', 'Urgent')?>', class: 'badge-danger' }
            };

            // Export buttons
            buttonConfig.push({
                extend: 'excel',
                exportOptions: { columns: columnNum },
                title: exportTitle,
                className: 'btn-success'
            });
            buttonConfig.push({
                extend: 'pdf',
                exportOptions: { columns: columnNum },
                title: exportTitle,
                className: 'btn-danger'
            });
            buttonConfig.push({
                extend: 'print',
                exportOptions: { columns: columnNum },
                title: exportTitle,
                className: 'btn-dark'
            });

            // New Request button
            buttonConfig.push({
                text: '<i class="fa fa-plus"></i> <?=__('new_request_button', 'New Request')?>',
                action: function ( e, dt, button, config ) {
                    window.location = 'new_general_request.php?id=<?=$newinvgr ?>';
                },
                className: 'btn-info'
            });

            // Initialize DataTable
            var table = $('#generalRequestsTbl').DataTable({
                dom: "Bfrtip",
                serverSide: true,
                buttons: buttonConfig,
                order: [[ 0, "desc" ]],
                columnDefs: [
                    {
                        targets: [ 0 ],
                        visible: false,
                        searchable: false
                    },
                    {
                        targets: 5, // Priority column
                        render: function ( data, type, row, meta ) {
                            let title = (data in priorityObj) ? priorityObj[data].title : data;
                            let className = (data in priorityObj) ? priorityObj[data].class : 'badge-secondary';
                            return `<span class="badge ${className}">${title}</span>`;
                        }
                    },
                    {
                        targets: 8, // Status column
                        render: function ( data, type, row, meta ) {
                            let title = (data in statusObj) ? statusObj[data].title : data;
                            let className = (data in statusObj) ? statusObj[data].class : 'badge-secondary';
                            
                            if (data === 'pending_approval' && row.current_approval_level) {
                                title = title + ' (L' + row.current_approval_level + ')';
                            }
                            
                            return `<span class="badge ${className}" text-capitalized>${title}</span>`;
                        }
                    }
                ],
                processing: true,
                responsive: true,
                ajax: {
                    type: "POST",
                    url: './includes/ajaxFile/generalRequestAjaxTbl.php',
                    data: function (d) {
                        d.user_type = '<?=$user_type?>';
                        d.user_dept = '<?=$user_dept?>';
                        d.emptype   = '<?=$emptypeget?>';
                        d.emp_id    = '<?=$empid?>';
                        d.status    = $('#statusFilter').val();
                        d.search    = $('#search').val();
                    },
                },
                columns: [
                    { data: 'id' },
                    { data: 'inv_no' },
                    { data: 'request_title' },
                    { data: 'department_to' },
                    { data: 'request_category' },
                    { data: 'priority' },
                    { data: 'emp_name' },
                    { data: 'created_at' },
                    { data: 'current_status' },
                    { data: 'action' },
                    { data: 'current_approval_level', visible: false, searchable: false }
                ],
                language: {
                    search: `<span>${__('search')}:</span> _INPUT_`,
                    searchPlaceholder: `${__('search')}...`,
                    lengthMenu: `${__('show')} _MENU_ ${__('entries')}`,
                    info: `${__('showing')} _START_ ${__('to')} _END_ ${__('of')} _TOTAL_ ${__('entries')}`,
                    infoEmpty: `${__('showing')} 0 ${__('to')} 0 ${__('of')} 0 ${__('entries')}`,
                    infoFiltered: `(${__('filtered_from')} _MAX_ ${__('total_entries')})`,
                    paginate: {
                        first: __('first'),
                        last: __('last'),
                        next: __('next'),
                        previous: __('previous')
                    },
                    emptyTable: __('no_data_available_in_table'),
                    zeroRecords: __('no_matching_records_found'),
                    processing: `<div class="spinner-border text-primary" role="status"><span class="visually-hidden">${__('loading')}...</span></div>`
                }
            });
            
            // Status filter change
            $('#statusFilter').change(function () {
                table.draw();
            });

            // Search functionality
            $('#search').keyup(function(){
                table.search($(this).val()).draw();
            });

            // Remove default search box
            $('#generalRequestsTbl_filter').remove();
            
            // Delete request handler
            $(document).on('click', '.deleteRequest', function() {
                const inv_no = $(this).data('id');
                
                Swal.fire({
                    title: '<?=__('are_you_sure', 'Are you sure?')?>',
                    text: '<?=__('confirm_delete_request', 'This will permanently delete the request and all its data!')?>',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: '<?=__('yes_delete_it', 'Yes, delete it!')?>',
                    cancelButtonText: '<?=__('cancel')?>'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: './includes/ajaxFile/ajaxGeneralRequest.php',
                            type: 'POST',
                            data: {
                                action: 'delete_general_request',
                                inv_no: inv_no
                            },
                            dataType: 'json',
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: '<?=__('deleted')?>',
                                        text: response.message,
                                        timer: 2000,
                                        showConfirmButton: false
                                    });
                                    table.draw();
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: '<?=__('error')?>',
                                        text: response.message
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire({
                                    icon: 'error',
                                    title: '<?=__('error')?>',
                                    text: '<?=__('error_occurred', 'An error occurred')?>'
                                });
                            }
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>
