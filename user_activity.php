<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';

// Only allow system administrators to access this page
if (!$is_system_admin) {
    header("Location: dashboard.php");
    exit();
}

$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='" . $username . "'");
if (mysqli_num_rows($query) == 1) {
    include("./includes/avatar_select.php");
?>
    <!doctype html>
    <html lang="en">

    <head>
        <meta charset="utf-8" />
        <title><?= $site_title ?> - User Activity Log</title>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta content="Anees Afzal" name="author" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />

        <!-- App favicon -->
        <link rel="shortcut icon" href="<?= get_setting($conDB, 'favicon') ?>">

        <!-- DataTables -->
        <link href="./plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
        <link href="./plugins/datatables/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" />
        <link href="./plugins/datatables/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />

        <!-- App css -->
        <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
        <script src="assets/js/modernizr.min.js"></script>

        <style type="text/css">
            .activity-card {
                border-left: 4px solid #5b73e8;
                margin-bottom: 1rem;
            }

            .device-icon {
                font-size: 2rem;
                color: #5b73e8;
            }

            .location-badge {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 0.25rem 0.75rem;
                border-radius: 20px;
                font-size: 0.85rem;
            }

            .status-active {
                background-color: #1abc9c;
            }

            .status-logged-out {
                background-color: #95a5a6;
            }

            .status-timeout {
                background-color: #e74c3c;
            }

            .info-label {
                font-weight: 600;
                color: #6c757d;
                font-size: 0.85rem;
            }

            .info-value {
                color: #343a40;
                font-size: 0.9rem;
            }

            .dt-button-down-arrow {
                display: none !important;
            }

            .badge-border-success {
                border: 2px solid #1abc9c;
                color: #1abc9c;
                background: transparent;
                padding: 0.35rem 0.65rem;
                border-radius: 4px;
                font-weight: 600;
            }

            .badge-border-danger {
                border: 2px solid #e74c3c;
                color: #e74c3c;
                background: transparent;
                padding: 0.35rem 0.65rem;
                border-radius: 4px;
                font-weight: 600;
            }

            .badge-border-secondary {
                border: 2px solid #95a5a6;
                color: #95a5a6;
                background: transparent;
                padding: 0.35rem 0.65rem;
                border-radius: 4px;
                font-weight: 600;
            }
        </style>
        <?php if ($is_rtl): ?>
            <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
        <?php endif; ?>
        <script>
            window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;
        </script>
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
                                <img src="<?= get_setting($conDB, 'logo') ?>" alt="" height="22">
                            </span>
                            <i>
                                <img src="<?= get_setting($conDB, 'white_logo') ?>" alt="" height="28">
                            </i>
                        </a>
                    </div>

                    <!--- Sidemenu -->
                    <?php include("./includes/main_menu.php"); ?>
                    <!-- Sidebar -->

                    <div class="clearfix"></div>
                </div>
            </div>
            <!-- Left Sidebar End -->

            <!-- Start right Content here -->
            <div class="content-page">

                <!-- Top Bar Start -->
                <?php include("./includes/topbar.php"); ?>
                <!-- Top Bar End -->

                <!-- Start Page content -->
                <div class="content">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-12">
                                <div class="card-box">
                                    <h4 class="m-t-0 header-title">
                                        <i class="mdi mdi-shield-account-outline mr-2"></i>
                                        <?= __('user_activity_log') ?? 'User Activity Log' ?>
                                    </h4>
                                    <p class="text-muted font-14 mb-4">
                                        <?= __('user_activity_description') ?? 'Track user login sessions, location, device information, and browsing details.' ?>
                                    </p>

                                    <div class="row mb-3">
                                        <div class="col-md-3">
                                            <div class="card mini-stat bg-primary text-white">
                                                <div class="card-body">
                                                    <div class="mb-4">
                                                        <div class="float-left mini-stat-img mr-4">
                                                            <i class="mdi mdi-account-check font-40"></i>
                                                        </div>
                                                        <h5 class="font-16 text-uppercase text-white-50 mt-0">
                                                            <?= __('active_sessions') ?? 'Active Sessions' ?>
                                                        </h5>
                                                        <h4 class="font-500" id="active-sessions-count">
                                                            <i class="mdi mdi-spin mdi-loading"></i>
                                                        </h4>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="card mini-stat bg-success text-white">
                                                <div class="card-body">
                                                    <div class="mb-4">
                                                        <div class="float-left mini-stat-img mr-4">
                                                            <i class="mdi mdi-calendar-today font-40"></i>
                                                        </div>
                                                        <h5 class="font-16 text-uppercase text-white-50 mt-0">
                                                            <?= __('today_logins') ?? 'Today\'s Logins' ?>
                                                        </h5>
                                                        <h4 class="font-500" id="today-logins-count">
                                                            <i class="mdi mdi-spin mdi-loading"></i>
                                                        </h4>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="card mini-stat bg-warning text-white">
                                                <div class="card-body">
                                                    <div class="mb-4">
                                                        <div class="float-left mini-stat-img mr-4">
                                                            <i class="mdi mdi-earth font-40"></i>
                                                        </div>
                                                        <h5 class="font-16 text-uppercase text-white-50 mt-0">
                                                            <?= __('unique_locations') ?? 'Unique Locations' ?>
                                                        </h5>
                                                        <h4 class="font-500" id="unique-locations-count">
                                                            <i class="mdi mdi-spin mdi-loading"></i>
                                                        </h4>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="card mini-stat bg-info text-white">
                                                <div class="card-body">
                                                    <div class="mb-4">
                                                        <div class="float-left mini-stat-img mr-4">
                                                            <i class="mdi mdi-devices font-40"></i>
                                                        </div>
                                                        <h5 class="font-16 text-uppercase text-white-50 mt-0">
                                                            <?= __('device_types') ?? 'Device Types' ?>
                                                        </h5>
                                                        <h4 class="font-500" id="device-types-count">
                                                            <i class="mdi mdi-spin mdi-loading"></i>
                                                        </h4>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center row pb-2 gap-3 gap-md-0">
                                        <div class="col-md-3 mb-2">
                                            <label class="font-weight-bold mb-1"><?= __('filter_by_user', 'Filter by User') ?>:</label>
                                            <div class="user_filter"></div>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <label class="font-weight-bold mb-1"><?= __('filter_by_status', 'Filter by Status') ?>:</label>
                                            <div class="status_filter"></div>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <label class="font-weight-bold mb-1"><?= __('filter_by_device', 'Filter by Device') ?>:</label>
                                            <div class="device_filter"></div>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <label class="font-weight-bold mb-1"><?= __('filter_by_location', 'Filter by Location') ?>:</label>
                                            <div class="location_filter"></div>
                                        </div>
                                    </div>

                                    <div class="table-responsive">
                                        <table id="activity_table" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th><?= __('user') ?? 'User' ?></th>
                                                    <th><?= __('login_time') ?? 'Login Time' ?></th>
                                                    <th><?= __('logout_time') ?? 'Logout Time' ?></th>
                                                    <th><?= __('duration') ?? 'Duration' ?></th>
                                                    <th><?= __('ip_address') ?? 'IP Address' ?></th>
                                                    <th><?= __('location') ?? 'Location' ?></th>
                                                    <th><?= __('device') ?? 'Device' ?></th>
                                                    <th><?= __('browser') ?? 'Browser' ?></th>
                                                    <th><?= __('os') ?? 'OS' ?></th>
                                                    <th><?= __('screen') ?? 'Screen' ?></th>
                                                    <th><?= __('status') ?? 'Status' ?></th>
                                                    <th><?= __('action') ?? 'Action' ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Data will be loaded via AJAX/Server-side -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <footer class="footer">
                    <?= $site_footer ?>
                </footer>

            </div>
        </div>
        <!-- END wrapper -->

        <!-- jQuery  -->
        <script src="assets/js/jquery.min.js"></script>
        <script src="assets/js/bootstrap.bundle.min.js"></script>
        <script src="assets/js/metisMenu.min.js"></script>
        <script src="assets/js/waves.js"></script>
        <script src="assets/js/jquery.slimscroll.js"></script>

        <!-- Required datatable js -->
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

        <script type="text/javascript">
            $(document).ready(function() {
                // Load statistics
                loadStatistics();

                // Button configuration
                var buttonConfig = [];
                var columnNum = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11];
                
                buttonConfig.push({
                    extend: 'copy',
                    text: '<i class="mdi mdi-content-copy text-info mr-1"></i>Copy',
                    exportOptions: { columns: columnNum }
                });
                buttonConfig.push({
                    extend: 'excel',
                    text: '<i class="mdi mdi-file-excel text-success mr-1"></i>Excel',
                    exportOptions: { columns: columnNum },
                    title: 'User Activity Log'
                });
                buttonConfig.push({
                    extend: 'csv',
                    text: '<i class="mdi mdi-file-document mr-1"></i>CSV',
                    exportOptions: { columns: columnNum },
                    title: 'User Activity Log'
                });
                buttonConfig.push({
                    extend: 'pdf',
                    text: '<i class="mdi mdi-file-pdf text-danger mr-1"></i>PDF',
                    exportOptions: { columns: columnNum },
                    title: 'User Activity Log',
                    orientation: 'landscape',
                    pageSize: 'A4'
                });
                buttonConfig.push({
                    extend: 'print',
                    text: '<i class="mdi mdi-printer text-primary mr-1"></i>Print',
                    exportOptions: { columns: columnNum },
                    title: 'User Activity Log'
                });

                // Status object for rendering
                var statusObj = {
                    'active': { title: 'Active', class: 'badge-border-success' },
                    'logged_out': { title: 'Logged Out', class: 'badge-border-secondary' },
                    'timeout': { title: 'Timeout', class: 'badge-border-danger' }
                };

                // Initialize DataTable
                var table = $('#activity_table').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: './includes/ajaxFile/ajaxUserActivity.php',
                        type: 'POST',
                        data: function(d) {
                            d.ajaxType = 'get_activity_log';
                        }
                    },
                    lengthChange: true,
                    dom: 'Bfrtip',
                    buttons: buttonConfig,
                    order: [[0, "desc"]],
                    pageLength: 10,
                    searchCols: [
                        null, // ID
                        null, // Username
                        null, // Login time
                        null, // Logout time
                        null, // Duration
                        null, // IP
                        null, // Location
                        null, // Device
                        null, // Browser
                        null, // OS
                        null, // Screen
                        null, // Status
                        null  // Action
                    ],
                    columnDefs: [
                        {
                            targets: 0,
                            visible: false,
                            searchable: false
                        },
                        {
                            // Duration column
                            targets: 4,
                            orderable: false,
                            render: function(data, type, row, meta) {
                                if (!row[3]) return '<span class="badge badge-success">Active</span>';
                                return data;
                            }
                        },
                        {
                            // Status column
                            targets: 11,
                            render: function(data, type, row, meta) {
                                return `<span class="badge-border ${statusObj[data].class}">${statusObj[data].title}</span>`;
                            }
                        },
                        {
                            // Action column
                            targets: 12,
                            orderable: false,
                            searchable: false,
                            render: function(data, type, row, meta) {
                                return `<button class="btn btn-sm btn-info view-details" data-id="${row[0]}">
                                    <i class="mdi mdi-eye"></i> Details
                                </button>`;
                            }
                        }
                    ],
                    initComplete: function() {
                        var api = this.api();
                        var selectOpt = `<select class="custom-select form-select input-sm"><option value=""> All </option></select>`;
                        
                        // User filter (column 1)
                        api.columns(1).every(function() {
                            var column = this;
                            var select = $(selectOpt).appendTo('.user_filter').on('change', function() {
                                var val = $(this).val();
                                column.search(val).draw();
                            });
                            column.data().unique().sort().each(function(d, j) {
                                select.append(`<option value="${d}">${d}</option>`);
                            });
                        });

                        // Status filter (column 11)
                        api.columns(11).every(function() {
                            var column = this;
                            var select = $(selectOpt).appendTo('.status_filter').on('change', function() {
                                var val = $(this).val();
                                column.search(val).draw();
                            });
                            column.data().unique().sort().each(function(d, j) {
                                select.append(`<option value="${d}">${statusObj[d].title}</option>`);
                            });
                        });

                        // Device filter (column 7)
                        api.columns(7).every(function() {
                            var column = this;
                            var select = $(selectOpt).appendTo('.device_filter').on('change', function() {
                                var val = $(this).val();
                                column.search(val).draw();
                            });
                            column.data().unique().sort().each(function(d, j) {
                                if (d) select.append(`<option value="${d}">${d}</option>`);
                            });
                        });

                        // Location filter (column 6)
                        api.columns(6).every(function() {
                            var column = this;
                            var select = $(selectOpt).appendTo('.location_filter').on('change', function() {
                                var val = $(this).val();
                                column.search(val).draw();
                            });
                            var seen = {};
                            column.data().unique().sort().each(function(d, j) {
                                if (d && !seen[d]) {
                                    seen[d] = true;
                                    select.append(`<option value="${d}">${d}</option>`);
                                }
                            });
                        });
                    }
                });

                table.buttons().container().appendTo('#activity_table_wrapper .col-md-6:eq(0)');

                // Auto-refresh table every 30 seconds
                setInterval(function() {
                    table.ajax.reload(null, false); // Reload without resetting pagination
                    loadStatistics(); // Also refresh statistics
                }, 30000);

                // View details handler
                $(document).on('click', '.view-details', function() {
                    var activityId = $(this).data('id');
                    loadActivityDetails(activityId);
                });
            });

            function loadStatistics() {
                $.ajax({
                    url: './includes/ajaxFile/ajaxUserActivity.php',
                    type: 'POST',
                    dataType: 'json',
                    data: { ajaxType: 'get_statistics' },
                    success: function(res) {
                        if (res.status === 200) {
                            $('#active-sessions-count').text(res.data.active_sessions);
                            $('#today-logins-count').text(res.data.today_logins);
                            $('#unique-locations-count').text(res.data.unique_locations);
                            $('#device-types-count').text(res.data.device_types);
                        }
                    }
                });
            }

            function loadActivityDetails(activityId) {
                $.ajax({
                    url: './includes/ajaxFile/ajaxUserActivity.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        ajaxType: 'get_activity_details',
                        activity_id: activityId
                    },
                    success: function(res) {
                        if (res.status === 200) {
                            var data = res.data;
                            var html = `
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><span class="info-label">User:</span> <span class="info-value">${data.username}</span></p>
                                        <p><span class="info-label">Employee Name:</span> <span class="info-value">${data.emp_name || 'N/A'}</span></p>
                                        <p><span class="info-label">Login Time:</span> <span class="info-value">${data.login_time}</span></p>
                                        <p><span class="info-label">Logout Time:</span> <span class="info-value">${data.logout_time || 'Still Active'}</span></p>
                                        <p><span class="info-label">Session Duration:</span> <span class="info-value">${data.duration}</span></p>
                                        <p><span class="info-label">IP Address:</span> <span class="info-value">${data.ip_address}</span></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><span class="info-label">Country:</span> <span class="info-value">${data.country}</span></p>
                                        <p><span class="info-label">Region/City:</span> <span class="info-value">${data.region}, ${data.city}</span></p>
                                        <p><span class="info-label">ISP:</span> <span class="info-value">${data.isp}</span></p>
                                        <p><span class="info-label">Browser:</span> <span class="info-value">${data.browser} ${data.browser_version}</span></p>
                                        <p><span class="info-label">Operating System:</span> <span class="info-value">${data.os} ${data.os_version}</span></p>
                                        <p><span class="info-label">Device:</span> <span class="info-value">${data.device_type}</span></p>
                                        <p><span class="info-label">Screen Resolution:</span> <span class="info-value">${data.screen_width}x${data.screen_height}</span></p>
                                    </div>
                                    <div class="col-12 mt-3">
                                        <p><span class="info-label">User Agent:</span><br><small class="text-muted">${data.user_agent}</small></p>
                                    </div>
                                </div>
                            `;
                            
                            Swal.fire({
                                title: 'Activity Details',
                                html: html,
                                width: '800px',
                                showCloseButton: true,
                                showConfirmButton: false,
                                cancelButtonText: 'Close'
                            });
                        }
                    }
                });
            }
        </script>

    </body>
    </html>
<?php } ?>
