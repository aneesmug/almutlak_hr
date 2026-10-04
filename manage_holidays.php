<?php

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/helper_functions.php';

// Restrict access to HR and System Admin only
// (or an explicit 'Access Page: Manage Holidays' Special Access grant)
if (!($isHR || $is_system_admin || user_has_special_access($conDB, $empid ?? '', 'access_manage_holidays', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false))) {
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

    // ===== PAGE DATA =====
    // All holidays are loaded; the tiles filter Active / Archived / Upcoming on the client.
    // ?status=1 (default) | 0 | all picks the tile that is selected first.
    $status_filter = $_GET['status'] ?? '1';
    $initial_key = $status_filter === '0' ? 'archived' : (($status_filter === 'all' || $status_filter === '') ? '' : 'active');

    $holidays = $pdo->query("SELECT h.* FROM emp_holidays h ORDER BY h.start_date DESC")->fetchAll(PDO::FETCH_ASSOC);

    // Company assignments for every holiday in one query
    $assigned = [];
    $comp_rows = $pdo->query("
        SELECT hc.holiday_id, hc.company_id, c.comp_name
        FROM holiday_companies hc
        JOIN companies c ON hc.company_id = c.id
        ORDER BY c.comp_name ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($comp_rows as $cr) {
        $assigned[(int)$cr['holiday_id']][] = $cr;
    }

    $today = date('Y-m-d');
    $year = date('Y');
    $count = ['all' => 0, 'active' => 0, 'archived' => 0, 'upcoming' => 0, 'year_days' => 0];
    foreach ($holidays as &$holiday) {
        $holiday['assigned_companies'] = $assigned[(int)$holiday['id']] ?? [];
        $holiday['is_on'] = ((int)$holiday['is_active'] === 1);
        $holiday['is_upcoming'] = $holiday['is_on'] && $holiday['end_date'] >= $today;
        $count['all']++;
        $count[$holiday['is_on'] ? 'active' : 'archived']++;
        if ($holiday['is_upcoming']) $count['upcoming']++;
        if ($holiday['is_on'] && substr($holiday['start_date'], 0, 4) === $year) $count['year_days'] += (int)$holiday['total_days'];
    }
    unset($holiday);

    $h = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES); };
    $type_meta = [
        'religious' => ['tone' => 'sky',   'icon' => 'mdi-star-circle',    'label' => __('religious', 'Religious')],
        'national'  => ['tone' => 'green', 'icon' => 'mdi-flag',           'label' => __('national', 'National')],
        'other'     => ['tone' => 'slate', 'icon' => 'mdi-calendar-blank', 'label' => __('other', 'Other')],
    ];
?>
    <!doctype html>
    <html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>
    <head>
        <meta charset="utf-8" />
        <title><?= $site_title ?> - <?= __('holiday_management', 'Holiday Management') ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />

        <!-- App favicon -->
        <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">

        <!-- Plugins css -->
        <link href="./plugins/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
        <link href="./plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
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
        <link href="assets/css/smart_request.css?v=<?= @filemtime(__DIR__ . '/assets/css/smart_request.css') ?>" rel="stylesheet" type="text/css" />
        <script src="assets/js/modernizr.min.js"></script>

        <?php if ($is_rtl): ?>
            <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
        <?php endif; ?>
        <script>
            window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;
        </script>

        <style type="text/css">
            .sr-page .sr-tiles.hol-tiles { grid-template-columns: repeat(5, minmax(0, 1fr)); }
            @media (max-width: 991px) { .sr-page .sr-tiles.hol-tiles { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
            @media (max-width: 575px) { .sr-page .sr-tiles.hol-tiles { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
            .sr-page .sr-tile.is-static { cursor: default; }
            .sr-page .sr-tile.is-static:hover { transform: none; border-color: var(--sr-border); }
            .hol-avatar { width: 40px; height: 40px; border-radius: 10px; font-size: 20px; }
            .hol-avatar.tone-sky   { background: var(--tone-sky-bg);   color: var(--tone-sky-fg); }
            .hol-avatar.tone-green { background: var(--tone-green-bg); color: var(--tone-green-fg); }
            .hol-avatar.tone-slate { background: var(--sr-surface-3);  color: var(--sr-muted); }
            .sr-page table.sr-table tbody tr.is-inactive td { opacity: .62; }
            .sr-page table.sr-table tbody tr.is-inactive:hover td { opacity: 1; }
            .hol-companies { display: flex; flex-wrap: wrap; gap: 4px; max-width: 340px; white-space: normal; }
            .hol-type-select {
                height: 34px; padding: 0 30px 0 12px; border-radius: 8px; font-size: 12px; font-weight: 600;
                border: 1px solid var(--sr-border-strong); background-color: var(--sr-surface); color: var(--sr-text-2);
            }
            /* Deduction rules notice */
            .hol-info { flex: 1; min-width: 0; }
            .hol-info summary { cursor: pointer; font-weight: 700; outline: 0; }
            .hol-info-body { margin-top: 10px; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
            @media (max-width: 767px) { .hol-info-body { grid-template-columns: 1fr; } }
            .hol-info-body h6 { margin: 0 0 6px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; color: inherit; }
            .hol-info-body ul { margin: 0; padding-inline-start: 18px; }
            .hol-info-body li { margin-bottom: 3px; }
            .hol-formula { display: inline-block; margin-top: 6px; padding: 4px 10px; border-radius: 8px; background: rgba(255, 255, 255, .55); font-weight: 600; }
            html.app-dark .hol-formula { background: rgba(0, 0, 0, .2); }

            /* Keep date-range calendars side-by-side inside SweetAlert modals */
            .swal2-container .daterangepicker {
                z-index: 2200 !important;
                min-width: 650px;
            }
            .swal2-container .daterangepicker .drp-calendar { max-width: none; }
            .swal2-container .daterangepicker.show-calendar .drp-calendar.left,
            .swal2-container .daterangepicker.show-calendar .drp-calendar.right {
                display: inline-block;
                float: none;
                vertical-align: top;
            }
            @media (max-width: 767px) {
                .swal2-container .daterangepicker { min-width: 0; width: 100%; }
            }
        </style>
    </head>

    <body class="enlarged" data-keep-enlarged="true">

        <!-- Begin page -->
        <div id="wrapper">

            <!-- ========== Left Sidebar Start ========== -->
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
            <!-- Left Sidebar End -->

            <div class="content-page">

                <?php include("./includes/topbar.php"); ?>

                <div class="content sr-page">
                    <div class="container-fluid">

                        <div class="sr-head">
                            <div>
                                <h1><?= __('holiday_management', 'Holiday Management') ?></h1>
                                <p><?= __('holiday_management_subtitle', 'Company holidays used when calculating vacation deductions.') ?></p>
                            </div>
                            <div class="sr-head-actions">
                                <button type="button" class="sr-btn sr-btn-primary" id="addHolidayBtn"><i class="mdi mdi-plus"></i> <?= __('add_holiday', 'Add holiday') ?></button>
                            </div>
                        </div>

                        <div class="sr-tiles hol-tiles" id="holTiles">
                            <button type="button" class="sr-tile<?= $initial_key === '' ? ' active' : '' ?>" data-key="">
                                <span class="sr-tile-label"><span class="sr-dot dot-all"></span><?= __('all', 'All') ?></span>
                                <span class="sr-tile-value"><?= $count['all'] ?></span>
                            </button>
                            <button type="button" class="sr-tile<?= $initial_key === 'active' ? ' active' : '' ?>" data-key="active">
                                <span class="sr-tile-label"><span class="sr-dot dot-green"></span><?= __('active', 'Active') ?></span>
                                <span class="sr-tile-value"><?= $count['active'] ?></span>
                            </button>
                            <button type="button" class="sr-tile" data-key="upcoming">
                                <span class="sr-tile-label"><span class="sr-dot dot-sky"></span><?= __('upcoming', 'Upcoming') ?></span>
                                <span class="sr-tile-value"><?= $count['upcoming'] ?></span>
                            </button>
                            <button type="button" class="sr-tile<?= $initial_key === 'archived' ? ' active' : '' ?>" data-key="archived">
                                <span class="sr-tile-label"><span class="sr-dot dot-slate"></span><?= __('archived', 'Archived') ?></span>
                                <span class="sr-tile-value"><?= $count['archived'] ?></span>
                            </button>
                            <div class="sr-tile is-static">
                                <span class="sr-tile-label"><span class="sr-dot dot-amber"></span><?= __('holiday_days_in', 'Holiday days in') ?> <?= $year ?></span>
                                <span class="sr-tile-value"><?= $count['year_days'] ?></span>
                            </div>
                        </div>

                        <!-- How vacation deduction works -->
                        <div class="sr-notice tone-sky">
                            <i class="mdi mdi-information-outline"></i>
                            <details class="hol-info">
                                <summary><?= __('how_vacation_deduction_works', 'How vacation deduction works') ?></summary>
                                <span class="hol-formula"><?= __('deductible_days', 'Deductible days') ?> = <?= __('total_vacation_days', 'Total vacation days') ?> &minus; <?= __('weekend_days', 'Weekend days') ?> &minus; <?= __('holiday_days', 'Holiday days') ?></span>
                                <div class="hol-info-body">
                                    <div>
                                        <h6><?= __('weekend_rules', 'Weekend rules (per company)') ?></h6>
                                        <ul>
                                            <li><strong>Head Office (Company 4):</strong> Friday &amp; Saturday off (all departments)</li>
                                            <li><strong>Head Office except:</strong> Sales (Dept 14) &amp; Purchase (Dept 13) = Friday only</li>
                                            <li><strong>All other companies (1,2,3,5,6,7,8,9,10,11):</strong> Friday only</li>
                                        </ul>
                                    </div>
                                    <div>
                                        <h6><?= __('example', 'Example') ?></h6>
                                        <ul>
                                            <li>5-day vacation Thursday &rarr; Monday (Head Office, regular department)</li>
                                            <li>Weekends (Fri, Sat): 2 days, not deducted</li>
                                            <li>Holidays during the period: 0 days</li>
                                            <li><strong>Result: 5 &minus; 2 &minus; 0 = 3 days deducted</strong></li>
                                        </ul>
                                    </div>
                                </div>
                                <div style="margin-top: 8px;"><small>Holidays are filtered by the employee's company. Weekends follow the employee's company and department.</small></div>
                            </details>
                        </div>

                        <div class="sr-card">
                            <div class="sr-toolbar">
                                <div class="sr-search">
                                    <i class="mdi mdi-magnify"></i>
                                    <input type="search" id="holSearch" placeholder="<?= __('search') ?>..." autocomplete="off" aria-label="<?= __('search') ?>">
                                </div>
                                <div class="sr-toolbar-right">
                                    <select id="holType" class="hol-type-select" aria-label="<?= __('type') ?>">
                                        <option value=""><?= __('type') ?>: <?= __('all', 'All') ?></option>
                                        <?php foreach ($type_meta as $tk => $tm): ?>
                                            <option value="<?= $tk ?>"><?= $h($tm['label']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div id="holExportButtons"></div>
                                </div>
                            </div>

                            <div class="sr-table-wrap">
                                <table id="holidays_table" class="table sr-table dt-responsive nowrap" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th><?= __('holiday', 'Holiday') ?></th>
                                            <th><?= __('period', 'Period') ?></th>
                                            <th><?= __('days', 'Days') ?></th>
                                            <th><?= __('companies', 'Companies') ?></th>
                                            <th><?= __('type') ?></th>
                                            <th><?= __('status') ?></th>
                                            <th class="text-right"><?= __('action') ?></th>
                                            <th>key</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($holidays as $holiday):
                                            $id = (int)$holiday['id'];
                                            $type = isset($type_meta[$holiday['holiday_type']]) ? $holiday['holiday_type'] : 'other';
                                            $tm = $type_meta[$type];
                                            $keys = [$holiday['is_on'] ? 'active' : 'archived'];
                                            if ($holiday['is_upcoming']) $keys[] = 'upcoming';
                                            $start_ts = strtotime($holiday['start_date']);
                                            $end_ts = strtotime($holiday['end_date']);
                                            $same_day = $holiday['start_date'] === $holiday['end_date'];
                                            // When: ongoing / in N days / past
                                            if ($holiday['start_date'] <= $today && $holiday['end_date'] >= $today) {
                                                $when = '<span class="sr-pill sr-pill-xs tone-green"><span class="sr-dot"></span>' . __('ongoing', 'Ongoing') . '</span>';
                                            } elseif ($holiday['start_date'] > $today) {
                                                $in_days = (int)round(($start_ts - strtotime($today)) / 86400);
                                                $when = '<span class="sr-cell-sub"><i class="mdi mdi-calendar-clock"></i>' . __('in', 'in') . ' ' . $in_days . ' ' . __('days', 'days') . '</span>';
                                            } else {
                                                $when = '<span class="sr-cell-sub">' . __('past', 'Past') . '</span>';
                                            }
                                            $comp_names = array_column($holiday['assigned_companies'], 'comp_name');
                                        ?>
                                            <tr class="<?= $holiday['is_on'] ? '' : 'is-inactive' ?>">
                                                <td data-order="<?= $h($holiday['holiday_name']) ?>" data-export="<?= $h($holiday['holiday_name']) ?>">
                                                    <div class="sr-person">
                                                        <span class="sr-avatar hol-avatar tone-<?= $holiday['is_on'] ? $tm['tone'] : 'slate' ?>"><i class="mdi <?= $tm['icon'] ?>"></i></span>
                                                        <div style="min-width: 0;">
                                                            <span class="sr-cell-title"><?= $h($holiday['holiday_name']) ?></span>
                                                            <?php if (!empty($holiday['remarks'])): ?>
                                                                <span class="sr-cell-sub" title="<?= $h($holiday['remarks']) ?>"><?= $h(mb_strimwidth($holiday['remarks'], 0, 48, '…')) ?></span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td data-order="<?= $h($holiday['start_date']) ?>" data-export="<?= date('d M Y', $start_ts) . ($same_day ? '' : ' - ' . date('d M Y', $end_ts)) ?>">
                                                    <span class="sr-cell-title"><?= date('d M Y', $start_ts) ?><?php if (!$same_day): ?> &rarr; <?= date('d M Y', $end_ts) ?><?php endif; ?></span>
                                                    <?= $holiday['is_on'] ? $when : '' ?>
                                                </td>
                                                <td data-order="<?= (int)$holiday['total_days'] ?>" data-export="<?= (int)$holiday['total_days'] ?>">
                                                    <span class="sr-chip"><i class="mdi mdi-calendar-range"></i><?= (int)$holiday['total_days'] ?> <?= __('days', 'days') ?></span>
                                                </td>
                                                <td data-export="<?= $h(implode(', ', $comp_names)) ?>">
                                                    <?php if ($comp_names): ?>
                                                        <span class="hol-companies">
                                                            <?php foreach ($comp_names as $cn): ?>
                                                                <span class="sr-chip"><i class="mdi mdi-domain"></i><?= $h($cn) ?></span>
                                                            <?php endforeach; ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="sr-pill sr-pill-xs tone-red"><span class="sr-dot"></span><?= __('no_companies', 'No companies') ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td data-search="<?= $type ?>" data-export="<?= $h($tm['label']) ?>">
                                                    <span class="sr-pill tone-<?= $tm['tone'] ?>"><span class="sr-dot"></span><?= $h($tm['label']) ?></span>
                                                </td>
                                                <td data-export="<?= $holiday['is_on'] ? __('active', 'Active') : __('archived', 'Archived') ?>">
                                                    <?= $holiday['is_on']
                                                        ? '<span class="sr-pill tone-green"><span class="sr-dot"></span>' . __('active', 'Active') . '</span>'
                                                        : '<span class="sr-pill tone-slate"><span class="sr-dot"></span>' . __('archived', 'Archived') . '</span>' ?>
                                                </td>
                                                <td class="text-right">
                                                    <div class="sr-actions">
                                                        <?php if ($holiday['is_on']): ?>
                                                            <a href="javascript:void(0);" class="sr-open-btn js-edit" data-id="<?= $id ?>"><i class="mdi mdi-pencil"></i> <?= __('edit') ?></a>
                                                            <div class="btn-group dropdown">
                                                                <a href="javascript:void(0);" class="sr-more-btn dropdown-toggle arrow-none" data-toggle="dropdown" aria-expanded="false"><i class="mdi mdi-dots-vertical"></i></a>
                                                                <div class="dropdown-menu dropdown-menu-right">
                                                                    <a href="javascript:void(0);" class="dropdown-item text-danger js-archive" data-id="<?= $id ?>" data-name="<?= $h($holiday['holiday_name']) ?>"><i class="mdi mdi-archive mr-2"></i><?= __('archive', 'Archive') ?></a>
                                                                </div>
                                                            </div>
                                                        <?php else: ?>
                                                            <a href="javascript:void(0);" class="sr-open-btn js-restore" data-id="<?= $id ?>" data-name="<?= $h($holiday['holiday_name']) ?>"><i class="mdi mdi-restore"></i> <?= __('reactivate', 'Reactivate') ?></a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td><?= implode(' ', $keys) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div> <!-- container -->
                </div> <!-- content -->

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

        <!-- SweetAlert2 -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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

        <!-- Date Range Picker -->
        <script src="./plugins/moment/moment.js"></script>
        <script src="./plugins/bootstrap-daterangepicker/daterangepicker.js"></script>
        <script src="./plugins/select2/js/select2.min.js" type="text/javascript"></script>

        <!-- App js -->
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>
        <script src="assets/js/sr_forms.js?v=<?= @filemtime(__DIR__ . '/assets/js/sr_forms.js') ?>"></script>

        <script>
            const F = window.SRForm, esc = F.esc;
            const T = <?= json_encode([
                'add' => __('add_holiday', 'Add holiday'),
                'edit' => __('edit_holiday', 'Edit holiday'),
                'save' => __('save_holiday', 'Save holiday'),
                'update' => __('update_holiday', 'Update holiday'),
                'details' => __('holiday_details', 'Holiday details'),
                'name' => __('holiday_name', 'Holiday name'),
                'name_ph' => __('holiday_name_ph', 'e.g. Eid al-Fitr'),
                'name_req' => __('holiday_name_required', 'Please enter the holiday name'),
                'range' => __('date_range', 'Date range'),
                'range_req' => __('date_range_required', 'Please select the date range'),
                'type' => __('holiday_type', 'Holiday type'),
                'religious' => __('religious', 'Religious'),
                'national' => __('national', 'National'),
                'other' => __('other', 'Other'),
                'companies' => __('assign_to_companies', 'Assign to companies'),
                'companies_ph' => __('select_companies', 'Select one or more companies'),
                'companies_req' => __('select_company_required', 'Please select at least one company'),
                'companies_hint' => __('holiday_companies_hint', 'Only employees of these companies get this holiday excluded from their vacation.'),
                'select_all' => __('select_all', 'Select all'),
                'remarks' => __('remarks', 'Remarks'),
                'remarks_ph' => __('remarks_ph', 'Any additional remarks'),
                'days' => __('days', 'days'),
                'archive_q' => __('archive_holiday_q', 'Archive holiday?'),
                'archive_txt' => __('archive_holiday_txt', 'It will no longer be used in vacation calculations.'),
                'archive_yes' => __('yes_archive', 'Yes, archive it'),
                'restore_q' => __('reactivate_holiday_q', 'Reactivate holiday?'),
                'restore_yes' => __('yes_reactivate', 'Yes, reactivate'),
                'exists_q' => __('holiday_archived_exists', 'Holiday already exists (archived)'),
                'load_err' => __('error_loading_data', 'Error loading data'),
                'export' => __('holidays', 'Holidays'),
                'print' => __('print', 'Print'),
            ]) ?>;
            const DATE_FMT = 'MM/DD/YYYY'; // server parses m/d/Y

            /* ---------- table ---------- */
            $(function() {
                const KEY_COL = 7, TYPE_COL = 4;
                const exportOptions = {
                    columns: [0, 1, 2, 3, 4, 5],
                    format: { body: function(data, row, col, node) { return $(node).attr('data-export') || $(node).text().trim(); } }
                };

                const table = $('#holidays_table').DataTable({
                    dom: 'Brtip',
                    pageLength: 15,
                    responsive: true,
                    order: [[1, 'desc']],
                    buttons: [
                        { extend: 'excel', text: '<i class="mdi mdi-file-excel"></i> Excel', exportOptions: exportOptions, title: T.export },
                        { extend: 'print', text: '<i class="mdi mdi-printer"></i> ' + esc(T.print), exportOptions: exportOptions, title: T.export }
                    ],
                    columnDefs: [
                        { targets: [KEY_COL], visible: false },
                        { targets: [3, 6], orderable: false }
                    ],
                    language: {
                        info: `${__('showing')} _START_ ${__('to')} _END_ ${__('of')} _TOTAL_ ${__('entries')}`,
                        infoEmpty: `${__('showing')} 0 ${__('to')} 0 ${__('of')} 0 ${__('entries')}`,
                        infoFiltered: '',
                        paginate: { first: __('first'), last: __('last'), next: '<i class="mdi mdi-chevron-right"></i>', previous: '<i class="mdi mdi-chevron-left"></i>' },
                        emptyTable: `<div class="sr-empty"><i class="mdi mdi-calendar-blank"></i>${__('no_data_available_in_table')}</div>`,
                        zeroRecords: `<div class="sr-empty"><i class="mdi mdi-magnify"></i>${__('no_matching_records_found')}</div>`
                    }
                });
                table.buttons().container().appendTo('#holExportButtons');

                function applyKey(key) {
                    table.column(KEY_COL).search(key ? '\\b' + key + '\\b' : '', true, false).draw();
                }
                applyKey($('#holTiles .sr-tile.active').data('key') || '');

                $('#holTiles').on('click', 'button.sr-tile', function() {
                    $('#holTiles .sr-tile').removeClass('active');
                    $(this).addClass('active');
                    applyKey($(this).data('key'));
                });

                $('#holSearch').on('input', function() { table.search(this.value).draw(); });

                $('#holType').on('change', function() {
                    const v = this.value;
                    table.column(TYPE_COL).search(v ? '^' + $.fn.dataTable.util.escapeRegex(v) + '$' : '', true, false).draw();
                });

                $('#addHolidayBtn').on('click', function() { openHolidayForm(null); });

                $('#holidays_table').on('click', '.js-edit', function() { editHoliday($(this).data('id')); })
                    .on('click', '.js-archive', function() { archiveHoliday($(this).data('id'), $(this).data('name')); })
                    .on('click', '.js-restore', function() { restoreHoliday($(this).data('id'), $(this).data('name')); });
            });

            /* ---------- add / edit popup ---------- */
            function holidayFormHtml(d) {
                const typeItems = [
                    { v: 'religious', l: T.religious, icon: 'mdi-star-circle' },
                    { v: 'national', l: T.national, icon: 'mdi-flag' },
                    { v: 'other', l: T.other, icon: 'mdi-calendar-blank' }
                ];
                const daysChip = `<span class="sr-chip"><i class="mdi mdi-calendar-range"></i><b id="hDays">0</b> ${esc(T.days)}</span>`;
                const selectAllBtn = `<button type="button" class="sr-btn sr-btn-sm sr-btn-ghost" id="hSelectAll"><i class="mdi mdi-check-all"></i> ${esc(T.select_all)}</button>`;

                return '<form id="holForm" class="sr-page sr-form text-left" autocomplete="off" novalidate>' +
                    F.section('mdi-calendar-check', T.details,
                        F.field({ col: 7, name: 'holiday_name', id: 'hName', label: T.name, req: true, ph: T.name_ph, msg: T.name_req, value: d.holiday_name || '' }) +
                        F.field({ col: 5, name: 'daterangepicker', id: 'hRange', label: T.range, req: true, msg: T.range_req, attrs: ' readonly style="background-color: var(--sr-surface);"' }) +
                        '<div class="sr-fcol c-12"><label>' + esc(T.type) + '</label>' + F.choices('holiday_type', typeItems) + '</div>',
                        daysChip) +
                    F.section('mdi-domain', T.companies,
                        '<div class="sr-fcol c-12">' +
                            `<select name="company_ids" id="hCompanies" class="form-control" multiple data-required="1" data-msg="${esc(T.companies_req)}"></select>` +
                            `<small class="sr-fhint">${esc(T.companies_hint)}</small>` +
                        '</div>',
                        selectAllBtn) +
                    F.section('mdi-note-text', T.remarks,
                        F.field({ col: 12, name: 'remarks', id: 'hRemarks', type: 'textarea', rows: 2, ph: T.remarks_ph, value: d.remarks || '' })) +
                '</form>';
            }

            function updateDays() {
                const parts = String($('#hRange').val() || '').split(' - ');
                let n = 0;
                if (parts.length === 2) {
                    const s = moment(parts[0], DATE_FMT), e = moment(parts[1], DATE_FMT);
                    if (s.isValid() && e.isValid()) n = e.diff(s, 'days') + 1;
                }
                $('#hDays').text(n);
            }

            function initRange(start, end) {
                const $input = $('#hRange');
                if (!$.fn.daterangepicker || typeof moment === 'undefined') return;
                const s = start && moment(start, ['YYYY-MM-DD', DATE_FMT]).isValid() ? moment(start, ['YYYY-MM-DD', DATE_FMT]) : moment();
                const e = end && moment(end, ['YYYY-MM-DD', DATE_FMT]).isValid() ? moment(end, ['YYYY-MM-DD', DATE_FMT]) : s.clone();
                $input.daterangepicker({
                    locale: { format: DATE_FMT },
                    autoUpdateInput: true,
                    parentEl: '.swal2-popup',
                    opens: 'center',
                    drops: 'down',
                    startDate: s,
                    endDate: e
                });
                $input.on('apply.daterangepicker', function() { $(this).removeClass('is-invalid'); updateDays(); });
                updateDays();
            }

            function loadCompanies(selectedIds) {
                const $sel = $('#hCompanies');
                $.getJSON('manage_holidays.php', { action: 'get_companies' }).done(function(res) {
                    if (!(res && res.status === 'success' && Array.isArray(res.data))) {
                        Swal.showValidationMessage(T.load_err);
                        return;
                    }
                    $sel.html(res.data.map(c => `<option value="${esc(c.id)}">${esc(c.comp_name || '')}</option>`).join(''));
                    F.select2($sel, { placeholder: T.companies_ph, allowClear: true });
                    if (Array.isArray(selectedIds) && selectedIds.length) $sel.val(selectedIds.map(String)).trigger('change');
                }).fail(function() { Swal.showValidationMessage(T.load_err); });
            }

            function openHolidayForm(d) {
                const isEdit = !!(d && d.id);
                d = d || {};
                F.open({
                    title: isEdit ? T.edit : T.add,
                    html: holidayFormHtml(d),
                    width: '780px',
                    icon: isEdit ? 'mdi-content-save' : 'mdi-plus',
                    confirm: isEdit ? T.update : T.save,
                    didOpen: function() {
                        const $form = $('#holForm');
                        F.liveClear($form);
                        $form.find(`input[name="holiday_type"][value="${d.holiday_type || 'religious'}"]`).prop('checked', true);
                        initRange(d.start_date, d.end_date);
                        loadCompanies(d.company_ids || []);
                        $('#hSelectAll').on('click', function() {
                            const $sel = $('#hCompanies');
                            $sel.val($sel.find('option').map(function() { return this.value; }).get()).trigger('change').removeClass('is-invalid');
                        });
                        $('#hCompanies').on('change', function() { $(this).removeClass('is-invalid'); });
                    },
                    preConfirm: function() {
                        const $form = $('#holForm');
                        const msg = F.validate($form);
                        if (msg) { Swal.showValidationMessage(msg); return false; }
                        return $.ajax({
                            url: 'manage_holidays.php',
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                action: isEdit ? 'edit' : 'add',
                                holiday_id: d.id || '',
                                holiday_name: $.trim($('#hName').val()),
                                daterangepicker: $.trim($('#hRange').val()),
                                holiday_type: $form.find('input[name="holiday_type"]:checked').val() || 'other',
                                remarks: $.trim($('#hRemarks').val()),
                                company_ids: $('#hCompanies').val() || []
                            }
                        }).then(function(res) {
                            if (res && (res.status === 'success' || res.status === 'archived')) return res;
                            throw new Error((res && res.message) || 'Error');
                        }).catch(function(err) {
                            Swal.showValidationMessage((err && err.message) || (err && err.statusText) || 'Error');
                        });
                    }
                }).then(function(result) {
                    if (!(result.isConfirmed && result.value)) return;
                    const res = result.value;
                    if (res.status === 'archived') {
                        // Same holiday exists but is archived - offer to bring it back
                        Swal.fire({
                            title: T.exists_q,
                            text: res.message,
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonColor: window.APP_COLORS && APP_COLORS.primary,
                            confirmButtonText: T.restore_yes,
                            cancelButtonText: F.t('cancel', 'Cancel'),
                            allowOutsideClick: false
                        }).then(r => { if (r.isConfirmed) postAction('unarchive', res.holiday_id); });
                        return;
                    }
                    F.done({ isConfirmed: true, value: { type: 'success', message: res.message } });
                });
            }

            function editHoliday(id) {
                $.getJSON('manage_holidays.php', { action: 'get_single', id: id }).done(function(res) {
                    if (res && res.status === 'success') openHolidayForm(res.data);
                    else Swal.fire({ title: F.t('error', 'Error'), text: (res && res.message) || T.load_err, icon: 'error', allowOutsideClick: false });
                }).fail(function() {
                    Swal.fire({ title: F.t('error', 'Error'), text: T.load_err, icon: 'error', allowOutsideClick: false });
                });
            }

            /* ---------- archive / reactivate ---------- */
            function postAction(action, id) {
                return $.post('manage_holidays.php', { action: action, holiday_id: id }, null, 'json').done(function(res) {
                    if (res && res.status === 'success') {
                        F.done({ isConfirmed: true, value: { type: 'success', message: res.message } });
                    } else {
                        Swal.fire({ title: F.t('error', 'Error'), text: (res && res.message) || 'Error', icon: 'error', allowOutsideClick: false });
                    }
                }).fail(function(xhr) {
                    Swal.fire({ title: F.t('error', 'Error'), text: xhr.statusText || 'Error', icon: 'error', allowOutsideClick: false });
                });
            }

            function confirmAction(o) {
                Swal.fire({
                    title: o.title,
                    html: `<div class="sr-page"><div class="sr-notice ${o.tone}" style="margin: 0; text-align: start;"><i class="mdi ${o.icon}"></i><div><strong>${esc(o.name)}</strong><br>${esc(o.text || '')}</div></div></div>`,
                    showCancelButton: true,
                    confirmButtonColor: o.color,
                    cancelButtonColor: window.APP_COLORS && APP_COLORS.danger_dark,
                    confirmButtonText: o.confirm,
                    cancelButtonText: F.t('cancel', 'Cancel'),
                    allowOutsideClick: false,
                    customClass: { popup: 'sr-addline-popup' }
                }).then(r => { if (r.isConfirmed) postAction(o.action, o.id); });
            }

            function archiveHoliday(id, name) {
                confirmAction({ action: 'delete', id: id, name: name, title: T.archive_q, text: T.archive_txt,
                    tone: 'tone-amber', icon: 'mdi-archive', color: '#dc2626', confirm: '<i class="mdi mdi-archive"></i> ' + esc(T.archive_yes) });
            }

            function restoreHoliday(id, name) {
                confirmAction({ action: 'unarchive', id: id, name: name, title: T.restore_q, text: '',
                    tone: 'tone-green', icon: 'mdi-restore', color: '#16a34a', confirm: '<i class="mdi mdi-restore"></i> ' + esc(T.restore_yes) });
            }
        </script>
    </body>
    </html>
<?php } ?>
