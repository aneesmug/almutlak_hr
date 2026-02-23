<?php

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/helper_functions.php';

// Restrict access to HR and System Admin only
if (!($isHR || $is_system_admin)) {
    header("Location: ./profile.php");
    exit();
}

// Check if user is authenticated
$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='" . $username . "'");
if (mysqli_num_rows($query) == 1) {
    include("./includes/avatar_select.php");
    
    // Get action from POST/GET
    $action = $_GET['action'] ?? $_POST['action'] ?? null;

    // ===== ADD HOLIDAY =====
    if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            $holiday_name = trim($_POST['holiday_name'] ?? '');
            $date_range = trim($_POST['daterangepicker'] ?? '');
            $holiday_type = trim($_POST['holiday_type'] ?? 'other');
            $remarks = trim($_POST['remarks'] ?? '');
            
            // Validation
            if (empty($holiday_name) || empty($date_range)) {
                die(json_encode(['status' => 'error', 'message' => 'Holiday name and date range are required']));
            }
            
            // Parse date range (format: "startdate - enddate")
            $dates = explode(' - ', $date_range);
            if (count($dates) !== 2) {
                die(json_encode(['status' => 'error', 'message' => 'Invalid date range format']));
            }
            
            $start_date = trim($dates[0]);
            $end_date = trim($dates[1]);
            
            // Validate dates
            $start = DateTime::createFromFormat('m/d/Y', $start_date);
            $end = DateTime::createFromFormat('m/d/Y', $end_date);
            
            if (!$start || !$end) {
                die(json_encode(['status' => 'error', 'message' => 'Invalid date format']));
            }
            
            if ($start > $end) {
                die(json_encode(['status' => 'error', 'message' => 'End date must be after or equal to start date']));
            }
            
            // Convert to Y-m-d format for database
            $start_date_db = $start->format('Y-m-d');
            $end_date_db = $end->format('Y-m-d');
            
            // Calculate total days
            $interval = $start->diff($end);
            $total_days = $interval->days + 1; // +1 to include both start and end dates
            
            // Insert holiday using PDO
            $stmt = $pdo->prepare("
                INSERT INTO emp_holidays 
                (holiday_name, start_date, end_date, total_days, holiday_type, remarks, created_by, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, 1)
            ");
            
            $stmt->execute([
                $holiday_name,
                $start_date_db,
                $end_date_db,
                $total_days,
                $holiday_type,
                $remarks,
                $empid
            ]);
            
            die(json_encode([
                'status' => 'success',
                'message' => 'Holiday added successfully',
                'holiday_id' => $pdo->lastInsertId()
            ]));
            
        } catch (Exception $e) {
            die(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }

    // ===== EDIT HOLIDAY =====
    if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            $holiday_id = (int)($_POST['holiday_id'] ?? 0);
            $holiday_name = trim($_POST['holiday_name'] ?? '');
            $date_range = trim($_POST['daterangepicker'] ?? '');
            $holiday_type = trim($_POST['holiday_type'] ?? 'other');
            $remarks = trim($_POST['remarks'] ?? '');
            
            if (empty($holiday_id) || empty($holiday_name) || empty($date_range)) {
                die(json_encode(['status' => 'error', 'message' => 'All fields are required']));
            }
            
            // Parse date range
            $dates = explode(' - ', $date_range);
            if (count($dates) !== 2) {
                die(json_encode(['status' => 'error', 'message' => 'Invalid date range format']));
            }
            
            $start_date = trim($dates[0]);
            $end_date = trim($dates[1]);
            
            // Validate dates
            $start = DateTime::createFromFormat('m/d/Y', $start_date);
            $end = DateTime::createFromFormat('m/d/Y', $end_date);
            
            if (!$start || !$end || $start > $end) {
                die(json_encode(['status' => 'error', 'message' => 'Invalid dates']));
            }
            
            // Convert to Y-m-d format for database
            $start_date_db = $start->format('Y-m-d');
            $end_date_db = $end->format('Y-m-d');
            
            // Calculate total days
            $interval = $start->diff($end);
            $total_days = $interval->days + 1;
            
            // Update holiday
            $stmt = $pdo->prepare("
                UPDATE emp_holidays 
                SET holiday_name = ?, start_date = ?, end_date = ?, total_days = ?, 
                    holiday_type = ?, remarks = ?, updated_by = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $holiday_name,
                $start_date_db,
                $end_date_db,
                $total_days,
                $holiday_type,
                $remarks,
                $empid,
                $holiday_id
            ]);
            
            die(json_encode(['status' => 'success', 'message' => 'Holiday updated successfully']));
            
        } catch (Exception $e) {
            die(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }

    // ===== DELETE HOLIDAY =====
    if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            $holiday_id = (int)($_POST['holiday_id'] ?? 0);
            
            if (empty($holiday_id)) {
                die(json_encode(['status' => 'error', 'message' => 'Holiday ID is required']));
            }
            
            // Soft delete - set is_active to 0
            $stmt = $pdo->prepare("UPDATE emp_holidays SET is_active = 0, updated_by = ? WHERE id = ?");
            $stmt->execute([$empid, $holiday_id]);
            
            die(json_encode(['status' => 'success', 'message' => 'Holiday archived successfully']));
            
        } catch (Exception $e) {
            die(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }

    // ===== GET HOLIDAYS =====
    if ($action === 'get_list') {
        try {
            // Get active holidays
            $stmt = $pdo->prepare("
                SELECT * FROM emp_holidays 
                WHERE is_active = 1 
                ORDER BY start_date ASC
            ");
            $stmt->execute();
            $holidays = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            die(json_encode([
                'status' => 'success',
                'data' => $holidays,
                'count' => count($holidays)
            ]));
            
        } catch (Exception $e) {
            die(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }

    // ===== GET SINGLE HOLIDAY =====
    if ($action === 'get_single' && isset($_GET['id'])) {
        try {
            $holiday_id = (int)$_GET['id'];
            
            $stmt = $pdo->prepare("SELECT * FROM emp_holidays WHERE id = ? LIMIT 1");
            $stmt->execute([$holiday_id]);
            $holiday = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$holiday) {
                die(json_encode(['status' => 'error', 'message' => 'Holiday not found']));
            }
            
            die(json_encode(['status' => 'success', 'data' => $holiday]));
            
        } catch (Exception $e) {
            die(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }

    // Page is loading - fetch holidays for display (both active and inactive)
    $stmt = $pdo->prepare("
        SELECT * FROM emp_holidays 
        ORDER BY start_date DESC
    ");
    $stmt->execute();
    $holidays = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
    <!doctype html>
    <html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>
    <head>
        <meta charset="utf-8" />
        <title><?= $site_title ?> - Holiday Management</title>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />

        <!-- App favicon -->
        <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">

        <!-- Plugins css -->
        <link href="./plugins/bootstrap-datepicker/css/bootstrap-datepicker.min.css" rel="stylesheet">
        <link href="./plugins/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
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
        <script>
            window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;
        </script>

        <style type="text/css">
            .holiday-type-badge {
                display: inline-block;
                padding: 0.25rem 0.5rem;
                border-radius: 0.25rem;
                font-size: 0.875rem;
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
                                <div class="card-box">
                                    <h4 class="m-t-0 header-title">Holiday Management</h4>
                                    <p class="text-muted">Manage company holidays for vacation deduction calculations</p>
                                    
                                    <div style="margin-bottom: 20px;">
                                        <button class="btn btn-primary waves-effect" onclick="openAddHolidayModal()" style="margin-right: 10px;">
                                            <i class="mdi mdi-plus"></i> Add Holiday
                                        </button>
                                        <select class="form-control" name="status_filter" id="status_filter" style="max-width: 200px; display: inline-block;">
                                            <option value="">All Records</option>
                                            <option value="1" selected>Active</option>
                                            <option value="0">Inactive</option>
                                        </select>
                                    </div>

                                    <?php if (empty($holidays)): ?>
                                        <div class="alert alert-info">
                                            <i class="mdi mdi-information"></i> No holidays added yet. Add your first holiday using the button above.
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table id="holidays_table" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                                <thead>
                                                    <tr>
                                                        <th>Holiday Name</th>
                                                        <th>Start Date</th>
                                                        <th>End Date</th>
                                                        <th>Days</th>
                                                        <th>Type</th>
                                                        <th>Status</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($holidays as $holiday): ?>
                                                        <tr class="holiday-row" data-status="<?= $holiday['is_active'] ?>">
                                                            <td><strong><?= htmlspecialchars($holiday['holiday_name']) ?></strong></td>
                                                            <td><?= date('M d, Y', strtotime($holiday['start_date'])) ?></td>
                                                            <td><?= date('M d, Y', strtotime($holiday['end_date'])) ?></td>
                                                            <td><span class="badge badge-info"><?= $holiday['total_days'] ?> days</span></td>
                                                            <td>
                                                                <?php 
                                                                    $type_classes = [
                                                                        'religious' => 'badge badge-info',
                                                                        'national' => 'badge badge-success',
                                                                        'other' => 'badge badge-secondary'
                                                                    ];
                                                                    $type = $holiday['holiday_type'] ?? 'other';
                                                                    $class = $type_classes[$type] ?? 'badge badge-secondary';
                                                                ?>
                                                                <span class="<?= $class ?>"><?= ucfirst($type) ?></span>
                                                            </td>
                                                            <td>
                                                                <?php if ($holiday['is_active'] == 1): ?>
                                                                    <span class="badge badge-success">Active</span>
                                                                <?php else: ?>
                                                                    <span class="badge badge-danger">Inactive</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?php if ($holiday['is_active'] == 1): ?>
                                                                    <div class='btn-group dropdown'>
                                                                        <a href='javascript: void(0);' class='table-action-btn dropdown-toggle arrow-none btn btn-light btn-sm' data-toggle='dropdown' aria-expanded='false'><i class='mdi mdi-dots-horizontal'></i></a>
                                                                        <div class='dropdown-menu dropdown-menu-right' x-placement='bottom-end'>
                                                                            <a href='javascript:void(0);' class='dropdown-item' onclick="editHoliday(<?= $holiday['id'] ?>)">
                                                                                <i class='mdi mdi-pencil mr-2'></i>Edit
                                                                            </a>
                                                                            <a href='javascript:void(0);' class='dropdown-item text-danger' onclick="deleteHoliday(<?= $holiday['id'] ?>)">
                                                                                <i class='mdi mdi-delete mr-2'></i>Archive
                                                                            </a>
                                                                        </div>
                                                                    </div>
                                                                <?php else: ?>
                                                                    <span class="badge badge-secondary">No Actions</span>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
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

        <!-- SweetAlert2 -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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

        <!-- Date Range Picker -->
        <script src="./plugins/moment/moment.js"></script>
        <script src="./plugins/bootstrap-daterangepicker/daterangepicker.js"></script>
        <script src="./plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>

        <script src="./plugins/select2/js/select2.min.js" type="text/javascript"></script>
        <script src="./plugins/bootstrap-select/js/bootstrap-select.js" type="text/javascript"></script>

        <!-- Key Tables -->
        <script src="./plugins/datatables/dataTables.keyTable.min.js"></script>

        <!-- Responsive examples -->
        <script src="./plugins/datatables/dataTables.responsive.min.js"></script>
        <script src="./plugins/datatables/responsive.bootstrap4.min.js"></script>

        <!-- Selection table -->
        <script src="./plugins/datatables/dataTables.select.min.js"></script>

        <!-- App js -->
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>

        <script>
            // Helper function to calculate and update days count
            function updateDaysCount(countElementId, dateInputId) {
                const dateRangeValue = document.getElementById(dateInputId).value.trim();
                
                if (!dateRangeValue) {
                    document.getElementById(countElementId).textContent = '0';
                    return;
                }
                
                const dates = dateRangeValue.split(' - ');
                if (dates.length === 2) {
                    const startDate = moment(dates[0], 'MM/DD/YYYY');
                    const endDate = moment(dates[1], 'MM/DD/YYYY');
                    
                    if (startDate.isValid() && endDate.isValid()) {
                        const daysCount = endDate.diff(startDate, 'days') + 1; // +1 to include both start and end dates
                        document.getElementById(countElementId).textContent = daysCount;
                    }
                }
            }

            $(document).ready(function() {
                // Initialize DataTable
                $('#holidays_table').DataTable({
                    responsive: true,
                    paging: true,
                    searching: true,
                    ordering: true
                });

                // Get status parameter from URL, default to '1' (Active)
                const urlParams = new URLSearchParams(window.location.search);
                const statusParam = urlParams.get('status') || '1';
                
                // Set filter dropdown to URL parameter value
                $('#status_filter').val(statusParam);

                // Apply filter based on URL parameter
                applyStatusFilter(statusParam);

                // Status filter - update URL when changed
                $('#status_filter').on('change', function() {
                    const status = $(this).val();
                    
                    // Update URL with status parameter
                    const newUrl = new URL(window.location);
                    if (status === '') {
                        newUrl.searchParams.set('status', 'all');
                    } else {
                        newUrl.searchParams.set('status', status);
                    }
                    window.history.pushState({}, '', newUrl);
                    
                    // Apply filter
                    applyStatusFilter(status);
                });
            });

            // Function to apply status filterthis
            function applyStatusFilter(status) {
                if (status === 'all' || status === '') {
                    // Show all records
                    $('.holiday-row').show();
                } else {
                    // Show only matching status
                    $('.holiday-row').each(function() {
                        const rowStatus = $(this).data('status').toString();
                        if (rowStatus === status) {
                            $(this).show();
                        } else {
                            $(this).hide();
                        }
                    });
                }
            }

            function openAddHolidayModal() {
                Swal.fire({
                    title: 'Add Holiday',
                    html: `
                        <div class="text-left">
                            <div class="form-group">
                                <label for="holiday_name" class="text-left">Holiday Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="holiday_name" placeholder="e.g., Eid al-Fitr">
                            </div>
                            
                            <div class="form-group">
                                <label for="daterangepicker_add" class="text-left">Date Range <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="daterangepicker_add" placeholder="Select date range">
                            </div>

                            <div class="form-group">
                                <label class="text-left"><strong>Selected Days: <span id="days_count_add">0</span> days</strong></label>
                            </div>
                            
                            <div class="form-group">
                                <label for="holiday_type" class="text-left">Holiday Type</label>
                                <select class="form-control" id="holiday_type">
                                    <option value="religious">Religious</option>
                                    <option value="national">National</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="remarks" class="text-left">Remarks</label>
                                <textarea class="form-control" id="remarks" rows="3" placeholder="Enter any additional remarks"></textarea>
                            </div>
                        </div>
                    `,
                    didOpen: function() {
                        // Initialize date range picker after modal is shown
                        $('#daterangepicker_add').daterangepicker({
                            locale: {
                                format: 'MM/DD/YYYY'
                            },
                            startDate: moment(),
                            endDate: moment()
                        });

                        // Calculate and display days on load
                        updateDaysCount('days_count_add', 'daterangepicker_add');

                        // Update days count when date range changes
                        $('#daterangepicker_add').on('apply.daterangepicker', function() {
                            updateDaysCount('days_count_add', 'daterangepicker_add');
                        });
                    },
                    showCancelButton: true,
                    confirmButtonText: 'Save Holiday',
                    cancelButtonText: 'Cancel',
                    allowOutsideClick: false,
                    preConfirm: function() {
                        const holidayName = document.getElementById('holiday_name').value.trim();
                        const dateRange = document.getElementById('daterangepicker_add').value.trim();
                        
                        if (!holidayName) {
                            Swal.showValidationMessage('Please enter holiday name');
                            return false;
                        }
                        
                        if (!dateRange) {
                            Swal.showValidationMessage('Please select date range');
                            return false;
                        }
                        
                        return {
                            holiday_id: '',
                            holiday_name: holidayName,
                            daterangepicker: dateRange,
                            holiday_type: document.getElementById('holiday_type').value,
                            remarks: document.getElementById('remarks').value.trim()
                        };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        saveHoliday(result.value, 'add');
                    }
                });
            }

            function editHoliday(holidayId) {
                $.ajax({
                    url: 'manage_holidays.php',
                    type: 'GET',
                    data: { action: 'get_single', id: holidayId },
                    dataType: 'json',
                    success: function(res) {
                        if (res.status === 'success') {
                            const data = res.data;
                            const startDate = moment(data.start_date);
                            const endDate = moment(data.end_date);
                            const dateRangeString = startDate.format('MM/DD/YYYY') + ' - ' + endDate.format('MM/DD/YYYY');
                            
                            Swal.fire({
                                title: 'Edit Holiday',
                                html: `
                                    <div class="text-left">
                                        <div class="form-group">
                                            <label for="holiday_name_edit" class="text-left">Holiday Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="holiday_name_edit" value="${data.holiday_name}">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="daterangepicker_edit" class="text-left">Date Range <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="daterangepicker_edit" value="${dateRangeString}">
                                        </div>

                                        <div class="form-group">
                                            <label class="text-left"><strong>Selected Days: <span id="days_count_edit">${data.total_days}</span> days</strong></label>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="holiday_type_edit" class="text-left">Holiday Type</label>
                                            <select class="form-control" id="holiday_type_edit">
                                                <option value="religious" ${data.holiday_type === 'religious' ? 'selected' : ''}>Religious</option>
                                                <option value="national" ${data.holiday_type === 'national' ? 'selected' : ''}>National</option>
                                                <option value="other" ${data.holiday_type === 'other' ? 'selected' : ''}>Other</option>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="remarks_edit" class="text-left">Remarks</label>
                                            <textarea class="form-control" id="remarks_edit" rows="3">${data.remarks || ''}</textarea>
                                        </div>
                                    </div>
                                `,
                                didOpen: function() {
                                    // Initialize date range picker after modal is shown
                                    $('#daterangepicker_edit').daterangepicker({
                                        startDate: startDate,
                                        endDate: endDate,
                                        locale: {
                                            format: 'MM/DD/YYYY'
                                        }
                                    });

                                    // Update days count when date range changes
                                    $('#daterangepicker_edit').on('apply.daterangepicker', function() {
                                        updateDaysCount('days_count_edit', 'daterangepicker_edit');
                                    });
                                },
                                showCancelButton: true,
                                confirmButtonText: 'Update Holiday',
                                cancelButtonText: 'Cancel',
                                allowOutsideClick: false,
                                preConfirm: function() {
                                    const holidayName = document.getElementById('holiday_name_edit').value.trim();
                                    const dateRange = document.getElementById('daterangepicker_edit').value.trim();
                                    
                                    if (!holidayName) {
                                        Swal.showValidationMessage('Please enter holiday name');
                                        return false;
                                    }
                                    
                                    if (!dateRange) {
                                        Swal.showValidationMessage('Please select date range');
                                        return false;
                                    }
                                    
                                    return {
                                        holiday_id: data.id,
                                        holiday_name: holidayName,
                                        daterangepicker: dateRange,
                                        holiday_type: document.getElementById('holiday_type_edit').value,
                                        remarks: document.getElementById('remarks_edit').value.trim()
                                    };
                                }
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    saveHoliday(result.value, 'edit');
                                }
                            });
                        } else {
                            Swal.fire('Error', res.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Error loading holiday data', 'error');
                    }
                });
            }

            function saveHoliday(data, action) {
                const formData = new FormData();
                formData.append('action', action);
                formData.append('holiday_id', data.holiday_id);
                formData.append('holiday_name', data.holiday_name);
                formData.append('daterangepicker', data.daterangepicker);
                formData.append('holiday_type', data.holiday_type);
                formData.append('remarks', data.remarks);
                
                $.ajax({
                    url: 'manage_holidays.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        if (res.status === 'success') {
                            Swal.fire({
                                title: 'Success',
                                text: res.message,
                                icon: 'success'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', res.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Error saving holiday', 'error');
                    }
                });
            }

            function deleteHoliday(holidayId) {
                Swal.fire({
                    title: 'Archive Holiday?',
                    text: 'This holiday will be archived and no longer used in calculations',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Archive it',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: 'manage_holidays.php',
                            type: 'POST',
                            dataType: 'json',
                            data: { action: 'delete', holiday_id: holidayId },
                            success: function(res) {
                                if (res.status === 'success') {
                                    Swal.fire({
                                        title: 'Archived!',
                                        text: res.message,
                                        icon: 'success'
                                    }).then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire('Error', res.message, 'error');
                                }
                            },
                            error: function() {
                                Swal.fire('Error', 'Error deleting holiday', 'error');
                            }
                        });
                    }
                });
            }
        </script>
    </body>
    </html>
<?php } ?>
