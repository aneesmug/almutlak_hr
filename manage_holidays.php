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
            $company_ids = $_POST['company_ids'] ?? [];
            
            // Validation
            if (empty($holiday_name) || empty($date_range)) {
                die(json_encode(['status' => 'error', 'message' => 'Holiday name and date range are required']));
            }
            
            if (empty($company_ids)) {
                die(json_encode(['status' => 'error', 'message' => 'At least one company must be selected']));
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
            
            // ===== CHECK FOR DUPLICATE HOLIDAY =====
            // Prevent duplicate entries with same start_date, end_date, holiday_type, and holiday_name
            // Check for both ACTIVE and ARCHIVED versions
            $check_dup_stmt = $pdo->prepare("
                SELECT id, is_active FROM emp_holidays 
                WHERE holiday_name = ? 
                AND start_date = ? 
                AND end_date = ? 
                AND holiday_type = ?
                LIMIT 1
            ");
            $check_dup_stmt->execute([$holiday_name, $start_date_db, $end_date_db, $holiday_type]);
            $existing_holiday = $check_dup_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing_holiday) {
                if ($existing_holiday['is_active'] == 1) {
                    // Active duplicate exists - reject
                    die(json_encode([
                        'status' => 'error', 
                        'message' => 'A holiday with the same name, dates, and type already exists and is active. Please use the edit function to modify it or archive it first.'
                    ]));
                } else {
                    // Archived version exists - offer to reactivate
                    die(json_encode([
                        'status' => 'archived',
                        'message' => 'A holiday with the same name, dates, and type was previously archived. Would you like to reactivate it?',
                        'holiday_id' => $existing_holiday['id']
                    ]));
                }
            }
            // ===== END DUPLICATE CHECK =====
            
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
            
            $holiday_id = $pdo->lastInsertId();
            
            // Assign companies to the holiday
            $company_stmt = $pdo->prepare("INSERT INTO holiday_companies (holiday_id, company_id) VALUES (?, ?)");
            foreach ($company_ids as $comp_id) {
                $comp_id = (int)$comp_id;
                try {
                    $company_stmt->execute([$holiday_id, $comp_id]);
                } catch (PDOException $e) {
                    // Skip duplicate entries if transaction fails
                    continue;
                }
            }
            
            die(json_encode([
                'status' => 'success',
                'message' => 'Holiday added successfully',
                'holiday_id' => $holiday_id
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
            $company_ids = $_POST['company_ids'] ?? [];
            
            if (empty($holiday_id) || empty($holiday_name) || empty($date_range)) {
                die(json_encode(['status' => 'error', 'message' => 'All fields are required']));
            }
            
            if (empty($company_ids)) {
                die(json_encode(['status' => 'error', 'message' => 'At least one company must be selected']));
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
            
            // ===== CHECK FOR DUPLICATE HOLIDAY (EXCLUDING CURRENT RECORD) =====
            // Prevent duplicate entries with same start_date, end_date, holiday_type, and holiday_name
            // but allow editing the same record
            $check_dup_stmt = $pdo->prepare("
                SELECT id FROM emp_holidays 
                WHERE holiday_name = ? 
                AND start_date = ? 
                AND end_date = ? 
                AND holiday_type = ?
                AND id != ?
                AND is_active = 1
                LIMIT 1
            ");
            $check_dup_stmt->execute([$holiday_name, $start_date_db, $end_date_db, $holiday_type, $holiday_id]);
            $existing_holiday = $check_dup_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing_holiday) {
                die(json_encode([
                    'status' => 'error', 
                    'message' => 'A holiday with the same name, dates, and type already exists. Please check your entries or archive the old record first.'
                ]));
            }
            // ===== END DUPLICATE CHECK =====
            
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
            
            // Update company assignments: delete old and insert new
            $delete_stmt = $pdo->prepare("DELETE FROM holiday_companies WHERE holiday_id = ?");
            $delete_stmt->execute([$holiday_id]);
            
            // Insert new company assignments
            $company_stmt = $pdo->prepare("INSERT INTO holiday_companies (holiday_id, company_id) VALUES (?, ?)");
            foreach ($company_ids as $comp_id) {
                $comp_id = (int)$comp_id;
                try {
                    $company_stmt->execute([$holiday_id, $comp_id]);
                } catch (PDOException $e) {
                    // Skip duplicate entries
                    continue;
                }
            }
            
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
            
            // Soft delete - set is_active to 0 for this specific record ONLY by ID
            // This ensures only the selected holiday is archived, not any duplicates
            $stmt = $pdo->prepare("UPDATE emp_holidays SET is_active = 0, updated_by = ? WHERE id = ?");
            $stmt->execute([$empid, $holiday_id]);
            
            die(json_encode(['status' => 'success', 'message' => 'Holiday archived successfully']));
            
        } catch (Exception $e) {
            die(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }

    // ===== UNARCHIVE HOLIDAY =====
    // Allows reactivating archived holidays (helpful for preventing duplicates)
    if ($action === 'unarchive' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            $holiday_id = (int)($_POST['holiday_id'] ?? 0);
            
            if (empty($holiday_id)) {
                die(json_encode(['status' => 'error', 'message' => 'Holiday ID is required']));
            }
            
            // Set is_active back to 1 to reactivate archived holiday
            $stmt = $pdo->prepare("UPDATE emp_holidays SET is_active = 1, updated_by = ? WHERE id = ?");
            $stmt->execute([$empid, $holiday_id]);
            
            die(json_encode(['status' => 'success', 'message' => 'Holiday reactivated successfully']));
            
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
            
            // Get assigned companies for this holiday
            $comp_stmt = $pdo->prepare("
                SELECT hc.company_id, c.comp_name 
                FROM holiday_companies hc
                JOIN companies c ON hc.company_id = c.id
                WHERE hc.holiday_id = ?
            ");
            $comp_stmt->execute([$holiday_id]);
            $companies = $comp_stmt->fetchAll(PDO::FETCH_ASSOC);
            $holiday['assigned_companies'] = $companies;
            $holiday['company_ids'] = array_column($companies, 'company_id');
            
            die(json_encode(['status' => 'success', 'data' => $holiday]));
            
        } catch (Exception $e) {
            die(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }
    
    // ===== GET COMPANIES LIST =====
    if ($action === 'get_companies') {
        try {
            $stmt = $pdo->prepare("
                SELECT id, comp_name, comp_id 
                FROM companies 
                WHERE 1=1 
                ORDER BY comp_name ASC
            ");
            $stmt->execute();
            $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            die(json_encode(['status' => 'success', 'data' => $companies]));
            
        } catch (Exception $e) {
            die(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
        }
    }

    // Determine filter from GET/POST or default to '1' (Active)
    $status_filter = $_GET['status'] ?? $_POST['status_filter'] ?? '1';
    $where = '';
    if ($status_filter === '1') {
        $where = 'WHERE h.is_active = 1';
    } elseif ($status_filter === '0') {
        $where = 'WHERE h.is_active = 0';
    } // else show all

    // Fetch holidays matching filter
    $stmt = $pdo->prepare("
        SELECT h.* FROM emp_holidays h
        $where
        ORDER BY h.start_date DESC
    ");
    $stmt->execute();
    $holidays = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch company assignments for each holiday
    foreach ($holidays as &$holiday) {
        $comp_stmt = $pdo->prepare("
            SELECT hc.company_id, c.comp_name 
            FROM holiday_companies hc
            JOIN companies c ON hc.company_id = c.id
            WHERE hc.holiday_id = ?
        ");
        $comp_stmt->execute([$holiday['id']]);
        $holiday['assigned_companies'] = $comp_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    unset($holiday);
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
                                    
                                    <!-- Info Box: Vacation Deduction Logic -->
                                    <div class="alert alert-info alert-styled-left" style="margin-bottom: 20px; background-color: #e3f2fd; border-left: 4px solid #2196F3;">
                                        <strong>💡 How Vacation Deduction Works:</strong>
                                        <br>
                                        <small>
                                            <strong>Formula:</strong> Deductible Days = Total Vacation Days − Weekend Days − Holiday Days
                                            <br>
                                            <strong>Weekend Rules (Company-Specific):</strong>
                                            <ul style="margin: 5px 0 5px 20px; font-size: 0.9rem;">
                                                <li><strong>Head Office (Company 4):</strong> Friday & Saturday off (all departments)</li>
                                                <li><strong>Head Office EXCEPT:</strong> Sales (Dept 14) & Purchase (Dept 13) = Friday only</li>
                                                <li><strong>All Other Companies (1,2,3,5,6,7,8,9,10,11):</strong> Friday only</li>
                                            </ul>
                                            <strong>Example:</strong> 5-day vacation from Thursday to Monday (Head Office, Regular Dept):
                                            <ul style="margin: 5px 0 5px 20px; font-size: 0.9rem;">
                                                <li>Total vacation: 5 days (Thu, Fri, Sat, Sun, Mon)</li>
                                                <li>Weekends (Fri, Sat): 2 days (NOT deducted per Head Office rules)</li>
                                                <li>Holiday during period: 0 days</li>
                                                <li><strong>Result: 5 − 2 − 0 = 3 days deducted</strong> ✓</li>
                                            </ul>
                                            <strong>Note:</strong> Holidays are filtered by employee's company. Weekend calculation is based on employee's company and department assignment.
                                        </small>
                                    </div>
                                    
                                    <div style="margin-bottom: 20px;">
                                        <button class="btn btn-primary waves-effect" onclick="openAddHolidayModal()" style="margin-right: 10px;">
                                            <i class="mdi mdi-plus"></i> Add Holiday
                                        </button>
                                        <select class="form-control" name="status_filter" id="status_filter" style="max-width: 200px; display: inline-block;">
                                            <option value="" <?= $status_filter === '' ? 'selected' : '' ?>>All Records</option>
                                            <option value="1" <?= $status_filter === '1' ? 'selected' : '' ?>>Active</option>
                                            <option value="0" <?= $status_filter === '0' ? 'selected' : '' ?>>Inactive</option>
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
                                                        <th>Companies</th>
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
                                                                <?php if (!empty($holiday['assigned_companies'])): ?>
                                                                    <?php foreach ($holiday['assigned_companies'] as $company): ?>
                                                                        <span class="badge badge-primary" style="margin: 2px;"><?= htmlspecialchars($company['comp_name']) ?></span>
                                                                    <?php endforeach; ?>
                                                                <?php else: ?>
                                                                    <span class="badge badge-danger">No Companies</span>
                                                                <?php endif; ?>
                                                            </td>
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
                                                                <?php if ((int)$holiday['is_active'] === 1): ?>
                                                                    <span class="badge badge-success">Active</span>
                                                                <?php else: ?>
                                                                    <span class="badge badge-danger">Inactive</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?php if ((int)$holiday['is_active'] === 1): ?>
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
                                                                    <a href='javascript:void(0);' class='btn btn-primary btn-sm' onclick="unarchiveHoliday(<?= $holiday['id'] ?>, '<?= htmlspecialchars($holiday['holiday_name']) ?>')">
                                                                        <i class='mdi mdi-restore mr-1'></i>Unarchive
                                                                    </a>
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
            
            function loadCompaniesForSelect(selectElement, selectedIds = []) {
                $.ajax({
                    url: 'manage_holidays.php',
                    type: 'GET',
                    data: { action: 'get_companies' },
                    dataType: 'json',
                    success: function(res) {
                        if (res.status === 'success') {
                            const $select = $(selectElement);
                            $select.empty();
                            
                            // Add default option
                            $select.append('<option></option>');
                            
                            res.data.forEach(function(company) {
                                $select.append(
                                    '<option value=\"' + company.id + '\">' + 
                                    $('<div/>').text(company.comp_name).html() + 
                                    '</option>'
                                );
                            });
                            
                            // Initialize or reinitialize Select2
                            if ($select.hasClass('select2-hidden-accessible')) {
                                $select.select2('destroy');
                            }
                            
                            $select.select2({
                                allowClear: true,
                                placeholder: 'Select one or more companies',
                                width: '100%'
                            });
                            
                            // Pre-select values if provided
                            if (Array.isArray(selectedIds) && selectedIds.length > 0) {
                                $select.val(selectedIds).trigger('change');
                            }
                        } else {
                            console.error('Error loading companies:', res.message);
                            Swal.fire('Error', 'Failed to load companies', 'error');
                        }
                    },
                    error: function() {
                        console.error('Error loading companies');
                        Swal.fire('Error', 'Error loading companies', 'error');
                    }
                });
            }

            $(document).ready(function() {
                console.log('Page loaded - initializing holidays table');
                
                // Get status parameter from URL, default to '1' (Active) to show only active records
                const urlParams = new URLSearchParams(window.location.search);
                const statusParam = urlParams.get('status') || '1';
                
                console.log('Status filter:', statusParam);
                
                // Set filter dropdown
                $('#status_filter').val(statusParam);
                
                // IMPORTANT: Apply filter BEFORE DataTable initialization
                // This ensures only the correct records are in the DOM before DataTable processes them
                applyStatusFilter(statusParam);
                
                // Initialize DataTable
                $('#holidays_table').DataTable({
                    responsive: true,
                    paging: true,
                    searching: true,
                    ordering: true,
                    drawCallback: function(settings) {
                        console.log('DataTable rows drawn');
                    }
                });

                // Status filter - reload page with correct status parameter
                $('#status_filter').on('change', function() {
                    var status = $(this).val();
                    var newUrl = new URL(window.location.href);
                    if (status === '' || status === 'all') {
                        newUrl.searchParams.set('status', 'all');
                    } else {
                        newUrl.searchParams.set('status', status);
                    }
                    window.location.href = newUrl.toString();
                });
            });

            // Function to apply status filter
            function applyStatusFilter(status) {
                console.log('Filtering with status:', status);
                
                // Convert status to string for comparison
                const filterValue = String(status);
                
                // Get all holiday rows
                const rows = $('.holiday-row');
                console.log('Total rows:', rows.length);
                
                let visibleCount = 0;
                
                rows.each(function() {
                    const rowStatus = String($(this).attr('data-status'));
                    console.log('Row data-status:', rowStatus, 'Filter value:', filterValue);
                    
                    if (filterValue === 'all' || filterValue === '') {
                        // Show all records
                        $(this).show();
                        visibleCount++;
                    } else if (filterValue === rowStatus) {
                        // Show matching records
                        $(this).show();
                        visibleCount++;
                    } else {
                        // Hide non-matching records
                        $(this).hide();
                    }
                });
                
                console.log('Visible rows after filter:', visibleCount);
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
                                <label for="companies_select_add" class="text-left">Assign to Companies <span class="text-danger">*</span></label>
                                <select class="form-control select2-multi" id="companies_select_add" multiple="multiple" data-placeholder="Select one or more companies"></select>
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
                        // Load companies
                        loadCompaniesForSelect('#companies_select_add');
                        
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
                        const companies = $('#companies_select_add').val();
                        
                        if (!holidayName) {
                            Swal.showValidationMessage('Please enter holiday name');
                            return false;
                        }
                        
                        if (!dateRange) {
                            Swal.showValidationMessage('Please select date range');
                            return false;
                        }
                        
                        if (!companies || companies.length === 0) {
                            Swal.showValidationMessage('Please select at least one company');
                            return false;
                        }
                        
                        return {
                            holiday_id: '',
                            holiday_name: holidayName,
                            daterangepicker: dateRange,
                            holiday_type: document.getElementById('holiday_type').value,
                            remarks: document.getElementById('remarks').value.trim(),
                            company_ids: companies
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
                                            <label for="companies_select_edit" class="text-left">Assign to Companies <span class="text-danger">*</span></label>
                                            <select class="form-control select2-multi" id="companies_select_edit" multiple="multiple" data-placeholder="Select one or more companies"></select>
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
                                    // Load companies and pre-select assigned ones
                                    loadCompaniesForSelect('#companies_select_edit', data.company_ids || []);
                                    
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
                                    const companies = $('#companies_select_edit').val();
                                    
                                    if (!holidayName) {
                                        Swal.showValidationMessage('Please enter holiday name');
                                        return false;
                                    }
                                    
                                    if (!dateRange) {
                                        Swal.showValidationMessage('Please select date range');
                                        return false;
                                    }
                                    
                                    if (!companies || companies.length === 0) {
                                        Swal.showValidationMessage('Please select at least one company');
                                        return false;
                                    }
                                    
                                    return {
                                        holiday_id: data.id,
                                        holiday_name: holidayName,
                                        daterangepicker: dateRange,
                                        holiday_type: document.getElementById('holiday_type_edit').value,
                                        remarks: document.getElementById('remarks_edit').value.trim(),
                                        company_ids: companies
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
                
                // Append company IDs
                if (data.company_ids && Array.isArray(data.company_ids)) {
                    data.company_ids.forEach(function(companyId) {
                        formData.append('company_ids[]', companyId);
                    });
                } else if (data.company_ids) {
                    formData.append('company_ids[]', data.company_ids);
                }
                
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
                        } else if (res.status === 'archived') {
                            // Archived duplicate found - offer to reactivate
                            Swal.fire({
                                title: 'Holiday Already Exists (Archived)',
                                html: res.message,
                                icon: 'question',
                                showCancelButton: true,
                                confirmButtonText: 'Yes, Reactivate It',
                                cancelButtonText: 'No, Cancel',
                                allowOutsideClick: false
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    // Reactivate the archived holiday
                                    reactivateHoliday(res.holiday_id);
                                }
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

            function reactivateHoliday(holidayId) {
                $.ajax({
                    url: 'manage_holidays.php',
                    type: 'POST',
                    dataType: 'json',
                    data: { action: 'unarchive', holiday_id: holidayId },
                    success: function(res) {
                        if (res.status === 'success') {
                            Swal.fire({
                                title: 'Success!',
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
                        Swal.fire('Error', 'Error reactivating holiday', 'error');
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

            function unarchiveHoliday(holidayId, holidayName) {
                Swal.fire({
                    title: 'Reactivate Holiday?',
                    text: 'Do you want to reactivate "' + holidayName + '"?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Reactivate',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: 'manage_holidays.php',
                            type: 'POST',
                            dataType: 'json',
                            data: { action: 'unarchive', holiday_id: holidayId },
                            success: function(res) {
                                if (res.status === 'success') {
                                    Swal.fire({
                                        title: 'Success!',
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
                                Swal.fire('Error', 'Error reactivating holiday', 'error');
                            }
                        });
                    }
                });
            }
        </script>
    </body>
    </html>
<?php } ?>
