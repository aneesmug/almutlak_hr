<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';

// Allow access to administrators, GM, and IT team
$can_view_user_activity = ($is_system_admin ?? false) || ($isGM ?? false) || ($isItTeam ?? false);
if (!$can_view_user_activity) {
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

        <!-- Leaflet map -->
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
        <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css" />
        <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css" />

        <link href="assets/css/smart_request.css?v=<?= @filemtime(__DIR__ . '/assets/css/smart_request.css') ?>" rel="stylesheet" type="text/css" />
        <style type="text/css">
            .ua-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-bottom: 20px; }
            @media (max-width: 991px) { .ua-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
            .ua-stats .sr-stat { background: var(--sr-surface); }
            .ua-filters { border-bottom: 1px solid var(--sr-border); }
            .dt-button-down-arrow { display: none !important; }
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
                <div class="content sr-page">
                    <div class="container-fluid">
                        <div class="sr-head">
                            <div>
                                <h1><?= __('user_activity_log') ?? 'User Activity Log' ?></h1>
                                <p><?= __('user_activity_description') ?? 'Track user login sessions, location, device information, and browsing details.' ?></p>
                            </div>
                        </div>

                        <div class="ua-stats">
                            <div class="sr-stat is-green">
                                <div class="sr-stat-label"><?= __('active_sessions') ?? 'Active Sessions' ?> <i class="mdi mdi-account-check"></i></div>
                                <div class="sr-stat-value" id="active-sessions-count"><i class="mdi mdi-spin mdi-loading"></i></div>
                            </div>
                            <div class="sr-stat is-sky">
                                <div class="sr-stat-label"><?= __('today_logins') ?? 'Today\'s Logins' ?> <i class="mdi mdi-calendar-today"></i></div>
                                <div class="sr-stat-value" id="today-logins-count"><i class="mdi mdi-spin mdi-loading"></i></div>
                            </div>
                            <div class="sr-stat is-amber">
                                <div class="sr-stat-label"><?= __('unique_locations') ?? 'Unique Locations' ?> <i class="mdi mdi-earth"></i></div>
                                <div class="sr-stat-value" id="unique-locations-count"><i class="mdi mdi-spin mdi-loading"></i></div>
                            </div>
                            <div class="sr-stat">
                                <div class="sr-stat-label"><?= __('device_types') ?? 'Device Types' ?> <i class="mdi mdi-cellphone-link"></i></div>
                                <div class="sr-stat-value" id="device-types-count"><i class="mdi mdi-spin mdi-loading"></i></div>
                            </div>
                        </div>

                        <div class="sr-card">
                            <div class="sr-card-head">
                                <h2 class="sr-card-title"><i class="mdi mdi-map-marker-radius"></i> <?= __('login_map') ?? 'Login Map' ?></h2>
                                <span class="sr-card-sub"><?= __('login_map_desc') ?? 'Geographic view of recent user login locations (last 500 records with coordinates).' ?></span>
                            </div>
                            <div class="sr-card-body">
                                <div id="activity-map" style="height:420px; border-radius:12px; overflow:hidden; position:relative;">
                                    <div id="map-loading" style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); z-index:1000; display:none;">
                                        <i class="mdi mdi-spin mdi-loading" style="font-size:48px; color:var(--sr-accent);"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="sr-card">
                            <div class="sr-toolbar">
                                <div class="sr-search">
                                    <i class="mdi mdi-magnify"></i>
                                    <input type="search" id="uaSearch" placeholder="<?= __('search_placeholder') ?>" autocomplete="off" aria-label="<?= __('search') ?>">
                                </div>
                                <div class="sr-toolbar-right">
                                    <div id="srExportButtons"></div>
                                </div>
                            </div>
                            <div class="sr-filter-grid ua-filters">
                                <div>
                                    <label><?= __('filter_by_user', 'Filter by User') ?></label>
                                    <div class="user_filter"></div>
                                </div>
                                <div>
                                    <label><?= __('filter_by_status', 'Filter by Status') ?></label>
                                    <div class="status_filter"></div>
                                </div>
                                <div>
                                    <label><?= __('filter_by_device', 'Filter by Device') ?></label>
                                    <div class="device_filter"></div>
                                </div>
                                <div>
                                    <label><?= __('filter_by_location', 'Filter by Location') ?></label>
                                    <div class="location_filter"></div>
                                </div>
                            </div>
                            <div class="sr-table-wrap">
                                <table id="activity_table" class="table sr-table dt-responsive nowrap" style="width: 100%;">
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
                                            <th class="text-right"><?= __('action') ?? 'Action' ?></th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
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

        <!-- Leaflet map JS -->
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
        <script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>

        <!-- App js -->
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>

        <script type="text/javascript">
            var activityMap = null;
            var markerClusterGroup = null;
            var isInitialMapLoad = true;

            $(document).ready(function() {
                // Load statistics
                loadStatistics();
                initActivityMap();
                loadMapMarkers();
                isInitialMapLoad = false;

                // Button configuration
                var buttonConfig = [];
                var columnNum = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11];
                
                buttonConfig.push({
                    extend: 'copy',
                    text: '<i class="mdi mdi-content-copy"></i> Copy',
                    exportOptions: { columns: columnNum }
                });
                buttonConfig.push({
                    extend: 'excel',
                    text: '<i class="mdi mdi-file-excel"></i> Excel',
                    exportOptions: { columns: columnNum },
                    title: 'User Activity Log'
                });
                buttonConfig.push({
                    extend: 'csv',
                    text: '<i class="mdi mdi-file-document"></i> CSV',
                    exportOptions: { columns: columnNum },
                    title: 'User Activity Log'
                });
                buttonConfig.push({
                    extend: 'pdf',
                    text: '<i class="mdi mdi-file-pdf"></i> PDF',
                    exportOptions: { columns: columnNum },
                    title: 'User Activity Log',
                    orientation: 'landscape',
                    pageSize: 'A4'
                });
                buttonConfig.push({
                    extend: 'print',
                    text: '<i class="mdi mdi-printer"></i> Print',
                    exportOptions: { columns: columnNum },
                    title: 'User Activity Log'
                });

                // Status object for rendering
                var statusObj = {
                    'active': { title: 'Active', tone: 'tone-green' },
                    'logged_out': { title: 'Logged Out', tone: 'tone-slate' },
                    'timeout': { title: 'Timeout', tone: 'tone-red' }
                };
                function initials(name) {
                    return String(name || '').trim().split(/\s+/).slice(0, 2).map(function(w) { return w.charAt(0); }).join('').toUpperCase();
                }

                // Initialize DataTable
                var table = $('#activity_table').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: './includes/ajaxFile/ajaxUserActivity.php',
                        type: 'POST',
                        data: function(d) {
                            d.ajaxType = 'get_activity_log';
                        },
                        // dataSrc: function(json) {
                        //     // If the session validator responded with 403, redirect to login
                        //     if (json && json.status === 403 && json.redirect) {
                        //         window.location.href = json.redirect;
                        //         return [];
                        //     }
                        //     // Fallback: DataTables expects an array
                        //     return json && json.data ? json.data : [];
                        // }
                    },
                    lengthChange: true,
                    dom: 'Brtip',
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
                            // User column
                            targets: 1,
                            render: function(data, type) {
                                if (type !== 'display') return data;
                                return '<div class="sr-person"><span class="sr-avatar sr-avatar-sm">' + escapeHtml(initials(data)) + '</span><span class="sr-person-name">' + escapeHtml(data) + '</span></div>';
                            }
                        },
                        {
                            // Duration column
                            targets: 4,
                            orderable: false,
                            render: function(data, type, row, meta) {
                                if (!row[3]) return '<span class="sr-pill sr-pill-xs tone-green"><span class="sr-dot"></span>Active</span>';
                                return data;
                            }
                        },
                        {
                            // Status column
                            targets: 11,
                            render: function(data, type, row, meta) {
                                var st = statusObj[data] || { title: data, tone: 'tone-slate' };
                                if (type !== 'display') return st.title;
                                return '<span class="sr-pill ' + st.tone + '"><span class="sr-dot"></span>' + escapeHtml(st.title) + '</span>';
                            }
                        },
                        {
                            // Action column
                            targets: 12,
                            orderable: false,
                            searchable: false,
                            className: 'text-right',
                            render: function(data, type, row, meta) {
                                var isActive = row[11] === 'active';
                                return '<div class="sr-actions">'
                                    + '<button type="button" class="sr-open-btn border-0 view-details" data-id="' + escapeHtml(row[0]) + '" title="View Details"><i class="mdi mdi-eye-outline"></i> Details</button>'
                                    + (isActive
                                        ? '<button type="button" class="sr-btn sr-btn-sm sr-btn-danger signout-user" data-id="' + escapeHtml(row[0]) + '" data-username="' + escapeHtml(row[1]) + '" title="Sign Out User"><i class="mdi mdi-logout-variant"></i> Sign Out</button>'
                                        : '')
                                    + '</div>';
                            }
                        }
                    ],
                    initComplete: function() {
                        var api = this.api();
                        var selectOpt = `<select class="form-control"><option value=""> All </option></select>`;
                        
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
                                select.append($('<option>').val(d).text(statusObj[d] ? statusObj[d].title : d));
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

                table.buttons().container().appendTo('#srExportButtons');
                $('#uaSearch').on('input', function() { table.search(this.value).draw(); });

                // Auto-refresh table every 30 seconds
                setInterval(function() {
                    table.ajax.reload(null, false); // Reload without resetting pagination
                    loadStatistics(); // Also refresh statistics
                    loadMapMarkers();
                }, 30000);

                // View details handler
                $(document).on('click', '.view-details', function() {
                    var activityId = $(this).data('id');
                    loadActivityDetails(activityId);
                });

                // Sign out user handler
                $(document).on('click', '.signout-user', function() {
                    if ($(this).hasClass('disabled')) return;
                    
                    var activityId = $(this).data('id');
                    var username = $(this).data('username');
                    
                    Swal.fire({
                        title: '<?= __("confirm_user_signout") ?? "Sign Out User?" ?>',
                        text: `<?= __("confirm_user_signout_message") ?? "Are you sure you want to sign out" ?> ${username}?`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: APP_COLORS.danger,
                        cancelButtonColor: APP_COLORS.secondary,
                        confirmButtonText: '<?= __("yes_sign_out") ?? "Yes, Sign Out" ?>',
                        cancelButtonText: '<?= __("cancel") ?? "Cancel" ?>',
                        allowOutsideClick: false
                    }).then((result) => {
                        if (result.isConfirmed) {
                            signOutUser(activityId);
                        }
                    });
                });
            });

            function initActivityMap() {
                if (activityMap) return;
                $('#map-loading').show();
                activityMap = L.map('activity-map', {
                    minZoom: 2,
                    maxZoom: 18,
                    preferCanvas: true, // Better performance for many markers
                    zoomControl: true
                });
                const defaultCenter = [21.5433, 39.1728]; // Center on Jeddah, Saudi Arabia by default
                activityMap.setView(defaultCenter, 6);
                
                // Use tile layer with caching and faster servers
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    minZoom: 2,
                    maxZoom: 18,
                    attribution: '&copy; OpenStreetMap contributors',
                    updateWhenIdle: true, // Only update tiles when map stops moving
                    updateWhenZooming: false, // Don't update during zoom animation
                    keepBuffer: 2, // Keep tiles in buffer for faster pan
                    maxNativeZoom: 18,
                    subdomains: ['a', 'b', 'c'] // Use multiple subdomains for parallel loading
                }).addTo(activityMap).on('load', function() {
                    $('#map-loading').fadeOut(300);
                });
                
                markerClusterGroup = L.markerClusterGroup({
                    maxClusterRadius: 80,
                    spiderfyOnMaxZoom: true,
                    showCoverageOnHover: false,
                    zoomToBoundsOnClick: true,
                    disableClusteringAtZoom: 18, // Don't cluster at max zoom
                    chunkedLoading: true, // Load markers in chunks for better performance
                    chunkInterval: 200,
                    chunkDelay: 50
                });
                activityMap.addLayer(markerClusterGroup);
            }

            function loadMapMarkers() {
                if (!activityMap) return;
                $('#map-loading').show();
                $.ajax({
                    url: './includes/ajaxFile/ajaxUserActivity.php',
                    type: 'POST',
                    dataType: 'json',
                    data: { ajaxType: 'get_location_markers' },
                    success: function(res) {
                        // Handle session termination responses
                        if (!res || res.status === 403) {
                            $('#map-loading').fadeOut(300);
                            if (res && res.redirect) {
                                window.location.href = res.redirect;
                            }
                            return;
                        }
                        // Guard against unexpected payloads
                        if (res.status !== 200 || !Array.isArray(res.data)) {
                            console.warn('Map markers: invalid response payload');
                            $('#map-loading').fadeOut(300);
                            return;
                        }
                        markerClusterGroup.clearLayers();
                        var bounds = [];
                        var validMarkers = 0;
                        res.data.forEach(function(item) {
                            var lat = parseFloat(item.lat);
                            var lng = parseFloat(item.lng);
                            // console.log('Marker data:', item.username, 'lat:', lat, 'lng:', lng);
                            if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                                console.log('Skipping invalid coordinates for:', item.username);
                                return;
                            }
                            var popupHtml = `<strong>${escapeHtml(item.username)}</strong><br>${escapeHtml(item.city)}, ${escapeHtml(item.country)}<br>${escapeHtml(item.login_time)}<br>Status: ${escapeHtml(item.status)}`;
                            var marker = L.marker([lat, lng]).bindPopup(popupHtml);
                            markerClusterGroup.addLayer(marker);
                            bounds.push([lat, lng]);
                            validMarkers++;
                        });
                        // console.log('Valid markers added:', validMarkers);
                        if (bounds.length && isInitialMapLoad) {
                            activityMap.fitBounds(bounds, { padding: [30, 30] });
                        } else {
                            // console.log('No valid markers with coordinates found');
                            if (res.debug && res.debug.total_records > 0 && res.debug.records_with_coords === 0) {
                                console.warn('Database has login records but none have latitude/longitude. Geolocation may not be working during login.');
                            }
                        }
                        $('#map-loading').fadeOut(300);
                    },
                    error: function(xhr, status, error) {
                        var message = error || status || 'Unknown error';
                        var responseText = xhr && xhr.responseText ? xhr.responseText.trim() : '';
                        try {
                            var parsed = responseText ? JSON.parse(responseText) : null;
                            if (parsed && parsed.message) {
                                message = parsed.message;
                            }
                        } catch (e) {
                            // Leave message as-is if response isn't JSON
                        }
                        console.error('Failed to load map markers:', message, 'status:', xhr ? xhr.status : 'n/a', 'response:', responseText);
                        $('#map-loading').fadeOut(300);
                    }
                });
            }

            function escapeHtml(value) {
                if (value === null || value === undefined) return '';
                return String(value).replace(/[&<>'"]/g, function(c) {
                    return {
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        "'": '&#39;',
                        '"': '&quot;'
                    }[c];
                });
            }

            function signOutUser(activityId) {
                $.ajax({
                    url: './includes/ajaxFile/ajaxUserActivity.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        ajaxType: 'force_signout_user',
                        activity_id: activityId
                    },
                    success: function(res) {
                        if (res.status === 200) {
                            Swal.fire({
                                title: 'Success',
                                text: res.message || 'User has been signed out successfully.',
                                icon: 'success',
                                confirmButtonColor: APP_COLORS.primary
                            }).then(() => {
                                // Reload the table
                                $('#activity_table').DataTable().ajax.reload(null, false);
                                loadMapMarkers();
                            });
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: res.message || 'Failed to sign out user.',
                                icon: 'error',
                                confirmButtonColor: APP_COLORS.danger
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        Swal.fire({
                            title: 'Error',
                            text: 'Failed to sign out user: ' + error,
                            icon: 'error',
                            confirmButtonColor: APP_COLORS.danger
                        });
                    }
                });
            }

            function loadStatistics() {
                $.ajax({
                    url: './includes/ajaxFile/ajaxUserActivity.php',
                    type: 'POST',
                    dataType: 'json',
                    data: { ajaxType: 'get_statistics' },
                    success: function(res) {
                        if (!res || res.status === 403) {
                            if (res && res.redirect) {
                                window.location.href = res.redirect;
                            }
                            return;
                        }
                        if (res.status === 200 && res.data) {
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
                        if (!res || res.status === 403) {
                            if (res && res.redirect) {
                                window.location.href = res.redirect;
                            }
                            return;
                        }
                        if (res.status === 200) {
                            var data = res.data;
                            var e = escapeHtml;
                            var kv = function(label, value) {
                                return '<div class="row-kv"><dt>' + e(label) + '</dt><dd>' + e(value == null || value === '' ? 'N/A' : value) + '</dd></div>';
                            };
                            var html = '<div class="sr-page text-left"><div class="row">'
                                + '<div class="col-md-6"><dl class="sr-kv">'
                                + kv('User', data.username) + kv('Employee Name', data.emp_name)
                                + kv('Login Time', data.login_time) + kv('Logout Time', data.logout_time || 'Still Active')
                                + kv('Session Duration', data.duration) + kv('IP Address', data.ip_address)
                                + '</dl></div><div class="col-md-6"><dl class="sr-kv">'
                                + kv('Country', data.country) + kv('Region/City', [data.region, data.city].filter(Boolean).join(', '))
                                + kv('ISP', data.isp) + kv('Browser', [data.browser, data.browser_version].filter(Boolean).join(' '))
                                + kv('Operating System', [data.os, data.os_version].filter(Boolean).join(' ')) + kv('Device', data.device_type)
                                + kv('Screen Resolution', data.screen_width && data.screen_height ? data.screen_width + 'x' + data.screen_height : '')
                                + '</dl></div>'
                                + '<div class="col-12 mt-3"><div class="sr-field-label">User Agent</div><div class="sr-mono small text-muted" style="word-break: break-all;">' + e(data.user_agent) + '</div></div>'
                                + '</div></div>';

                            Swal.fire({
                                title: 'Activity Details',
                                html: html,
                                width: '800px',
                                showCloseButton: true,
                                showConfirmButton: false,
                                allowOutsideClick: false,
                                customClass: { popup: 'sr-addline-popup' }
                            });
                        }
                    }
                });
            }
        </script>

    </body>
    </html>
<?php } ?>
