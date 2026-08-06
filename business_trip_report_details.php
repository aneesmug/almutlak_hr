<?php
/****************************************************************
 * Business Trip Report - Detail View
 * 
 * Displays comprehensive business trip request details with:
 * - Trip information (dates, destination, purpose, etc.)
 * - Employee and department info
 * - Approval chain timeline
 * - Print-friendly formatting
 * 
 * Similar structure to vacation_report_details.php
 ****************************************************************/

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';

$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='" . $username . "'");
if (mysqli_num_rows($query) == 1) {
    $viewer_admin = mysqli_fetch_assoc($query);
    $viewer_user_type = strtolower((string)($viewer_admin['user_type'] ?? ''));
    $is_hr_payroll_viewer = (strpos($viewer_user_type, 'hr_payroll') !== false);
    include("./includes/avatar_select.php");

    // 1. Get and validate the IDs from the URL
    $trip_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $emp_id = isset($_GET['emp_id']) ? $_GET['emp_id'] : '';

    if ($trip_id === 0 || empty($emp_id)) {
        die("Invalid request parameters.");
    }

    // 2. Get Request Type ID for business_trip
    $type_query = mysqli_query($conDB, "SELECT id FROM approval_request_types WHERE type_name = 'business_trip' LIMIT 1");
    if (!$type_query || mysqli_num_rows($type_query) == 0) {
        die("CRITICAL ERROR: 'business_trip' type not found in `approval_request_types` table.");
    }
    $request_type_id = (int)mysqli_fetch_assoc($type_query)['id'];
    mysqli_free_result($type_query);
    $sql = "SELECT 
                bt.*,
                e.name as employee_name,
                e.avatar,
                e.emp_id,
                d.dep_nme AS dept_name,
                d.dep_nme_ar AS dept_name_ar,
                s.section_name,
                fc.name_en as from_city_name_en,
                fc.name_ar as from_city_name_ar,
                tc.name_en as to_city_name_en,
                tc.name_ar as to_city_name_ar,
                c.name AS destination_country_name
            FROM emp_business_trip bt
            JOIN employees e ON bt.emp_id = e.emp_id
            LEFT JOIN department d ON e.dept = d.id
            LEFT JOIN section s ON e.sectin_nme = s.id
            LEFT JOIN saudi_cities fc ON bt.from_city_id = fc.id
            LEFT JOIN saudi_cities tc ON bt.to_city_id = tc.id
            LEFT JOIN countries c ON bt.destination_country = c.name
            WHERE bt.id = ? AND bt.emp_id = ?";

    $stmt = $conDB->prepare($sql);
    $stmt->bind_param("is", $trip_id, $emp_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $request = $result->fetch_assoc();
    $stmt->close();

    if (!$request) {
        die("Business trip request not found.");
    }

    $trip_allowance_days = 0;
    try {
        $trip_start = new DateTime((string)($request['trip_start_date'] ?? ''));
        $trip_end = new DateTime((string)($request['trip_end_date'] ?? ''));
        if ($trip_end >= $trip_start) {
            $trip_allowance_days = (int)$trip_start->diff($trip_end)->format('%a') + 1;
        }
    } catch (Exception $e) {
        $trip_allowance_days = 0;
    }

    $basic_salary = 0.0;
    $salary_sql = "SELECT basic FROM emp_salary WHERE emp_id = ? AND status = 1 ORDER BY id DESC LIMIT 1";
    $stmt_salary = $conDB->prepare($salary_sql);
    if ($stmt_salary) {
        $stmt_salary->bind_param("s", $emp_id);
        $stmt_salary->execute();
        $salary_result = $stmt_salary->get_result();
        if ($salary_result && ($salary_row = $salary_result->fetch_assoc())) {
            $basic_salary = (float)($salary_row['basic'] ?? 0);
        }
        $stmt_salary->close();
    }

    $salary_scales = [
        ['level' => 1, 'min' => 500, 'max' => 2700, 'hotel' => 125, 'transport' => 50],
        ['level' => 2, 'min' => 2850, 'max' => 5950, 'hotel' => 200, 'transport' => 75],
        ['level' => 3, 'min' => 6150, 'max' => 7950, 'hotel' => 350, 'transport' => 100],
        ['level' => 4, 'min' => 8300, 'max' => 11450, 'hotel' => 450, 'transport' => 150],
        ['level' => 5, 'min' => 12350, 'max' => 20450, 'hotel' => 650, 'transport' => 200],
    ];

    $matched_scale = null;
    foreach ($salary_scales as $scale) {
        if ($basic_salary >= $scale['min'] && $basic_salary <= $scale['max']) {
            $matched_scale = $scale;
            break;
        }
    }

    $calc_hotel_daily = (float)($matched_scale['hotel'] ?? 0);
    $calc_transport_daily = (float)($matched_scale['transport'] ?? 0);
    // Hotel nights = trip days minus 1 (no overnight stay needed on the last day).
    $hotel_allowance_days = max(0, $trip_allowance_days - 1);
    $calc_hotel_total = $calc_hotel_daily * $hotel_allowance_days;
    $calc_transport_total = $calc_transport_daily * max(0, $trip_allowance_days);

    $week1_days = min(max($trip_allowance_days, 0), 7);
    $week2_days = min(max($trip_allowance_days - 7, 0), 7);
    $week3_days = max($trip_allowance_days - 14, 0);

    $week1_daily_rate = ($basic_salary > 0) ? ($basic_salary / 60) : 0;
    $week2_daily_rate = ($basic_salary > 0) ? ($basic_salary / 90) : 0;
    $week3_daily_rate = ($basic_salary > 0) ? ($basic_salary / 120) : 0;

    $week1_daily_total = round($week1_daily_rate * $week1_days);
    $week2_daily_total = round($week2_daily_rate * $week2_days);
    $week3_daily_total = round($week3_daily_rate * $week3_days);
    $calc_daily_allowance_total = round($week1_daily_total + $week2_daily_total + $week3_daily_total);

    // 3.5 Fetch allowance summary and line items (if any)
    $allowance_summary = null;
    $allowance_items = [];

    $allowance_sql = "SELECT * FROM emp_business_trip_allowances WHERE trip_id = ? LIMIT 1";
    $stmt_allowance = $conDB->prepare($allowance_sql);
    if ($stmt_allowance) {
        $stmt_allowance->bind_param("i", $trip_id);
        $stmt_allowance->execute();
        $allowance_result = $stmt_allowance->get_result();
        $allowance_summary = $allowance_result ? $allowance_result->fetch_assoc() : null;
        $stmt_allowance->close();
    }

    if (!empty($allowance_summary['id'])) {
        $allowance_id = (int)$allowance_summary['id'];
        $items_sql = "SELECT allowance_type, description, unit_amount, qty, line_total
                      FROM emp_business_trip_allowance_items
                      WHERE allowance_id = ?
                      ORDER BY line_order ASC, id ASC";
        $stmt_items = $conDB->prepare($items_sql);
        if ($stmt_items) {
            $stmt_items->bind_param("i", $allowance_id);
            $stmt_items->execute();
            $items_result = $stmt_items->get_result();
            while ($items_result && ($item_row = $items_result->fetch_assoc())) {
                $allowance_items[] = $item_row;
            }
            $stmt_items->close();
        }
    }

    $ticket_fares_total = (float)($allowance_summary['ticket_fares'] ?? 0);
    $other_allowance_total = (float)($allowance_summary['other_allowance'] ?? 0);
    $report_grand_total = $ticket_fares_total + $calc_hotel_total + $calc_transport_total + $calc_daily_allowance_total + $other_allowance_total;

    // 4. Fetch approval chain
    $approval_chain = [];
    $request_inv_no = $request['request_inv_no'] ?? '';
    
    if (!empty($request_inv_no)) {
        // Fetch the approval chain using the request_type_id we already retrieved
        $chain_sql = "SELECT ra.*,
                       e.name AS approver_name,
                       e.emp_id AS approver_emp_id,
                       al.user_type AS approver_role,
                       d2.dep_nme AS approver_dept_name
                   FROM request_approvers ra
                   LEFT JOIN employees e ON ra.approver_id = e.emp_id
                   LEFT JOIN admin_login al ON e.emp_id = al.emp_id
                   LEFT JOIN department d2 ON e.dept = d2.id
                   WHERE ra.request_inv_no = ? AND ra.request_type_id = ?
                   ORDER BY ra.approval_level ASC";
        $stmt_chain = $conDB->prepare($chain_sql);
        if ($stmt_chain) {
            $stmt_chain->bind_param("si", $request_inv_no, $request_type_id);
            $stmt_chain->execute();
            $chain_result = $stmt_chain->get_result();
            while ($chain_row = $chain_result->fetch_assoc()) {
                $approval_chain[] = $chain_row;
            }
            $stmt_chain->close();
        }
    }

    // 5. Format trip destination display
    $trip_type = $request['trip_type'] ?? 'domestic';
    $destination_display = '';
    
    if ($trip_type === 'international') {
        $destination_display = htmlspecialchars((string)($request['destination_country_name'] ?? $request['destination_country'] ?? 'N/A'));
    } else {
        $from_city = ($is_rtl ?? false) ? ($request['from_city_name_ar'] ?? '') : ($request['from_city_name_en'] ?? '');
        $to_city = ($is_rtl ?? false) ? ($request['to_city_name_ar'] ?? '') : ($request['to_city_name_en'] ?? '');
        $destination_display = htmlspecialchars(trim($from_city) . ' → ' . trim($to_city));
    }

    // 6. Get current request status details
    $current_status = $request['current_status'] ?? 'pending_approval';
    $status_badge_class = 'secondary';
    if ($current_status === 'pending_approval') $status_badge_class = 'warning';
    if ($current_status === 'approved') $status_badge_class = 'success';
    if ($current_status === 'rejected') $status_badge_class = 'danger';
    if ($current_status === 'completed') $status_badge_class = 'info';

?>

<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>
<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?> - <?= __('business_trip_report') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="shortcut icon" href="<?= get_setting($conDB, 'favicon') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
    <style>
        :root {
            --primary-color: #4a90e2;
            --text-color: #333;
            --muted-color: #6c757d;
            --border-color: #e9ecef;
            --background-light: #f8f9fa;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
        }
        body.enlarged {
                background-color: #f4f7f6;
        }
        .report-wrapper {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            max-width: 800px; /* Reduced width */
            margin: 1rem auto; /* Reduced margin */
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,.08);
            color: var(--text-color);
            font-size: 14px; /* Base font size */
        }
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem; /* Reduced padding */
            border-bottom: 1px solid var(--border-color);
        }
        .report-header .logo-container img { max-height: 40px; } /* Reduced logo size */
        .report-header .report-meta { text-align: right; }
        .report-header .report-title { font-size: 1.1rem; font-weight: 600; margin: 0; }
        .report-header .report-subtitle { font-size: 0.8rem; color: var(--muted-color); margin: 0; }
        
        /* RTL Overrides */
        [dir="rtl"] .report-header { flex-direction: row-reverse; }
        [dir="rtl"] .report-header .report-meta { text-align: left; }
        
        .report-body { padding: 1.5rem; } /* Reduced padding */
        
        .employee-banner { display: flex; align-items: center; background-color: var(--background-light); padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; border: 1px solid var(--border-color); }
        .employee-banner .avatar { width: 60px; height: 60px; border-radius: 50%; margin-right: 1rem; }
        .employee-banner .info { flex: 1; }
        .employee-banner .info h4 { font-weight: 600; margin: 0 0 0.2rem 0; font-size: 1.1rem; }
        .employee-banner .info p { color: var(--muted-color); margin: 0; font-size: 0.85rem; }
        
        /* RTL Overrides */
        [dir="rtl"] .employee-banner { flex-direction: row-reverse; }
        [dir="rtl"] .employee-banner .avatar { margin-right: 0; margin-left: 1rem; order: 2; }
        [dir="rtl"] .employee-banner .info { order: 1; text-align: right; }

        .report-section { margin-bottom: 1.5rem; } /* Reduced margin */
        .section-title { font-weight: 600; color: var(--primary-color); margin-bottom: 1rem; font-size: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; }
        .section-title i { margin-right: 0.5rem; }
        [dir="rtl"] .section-title i { margin-right: 0; margin-left: 0.5rem; }

        .grid-details { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; }
        .detail-item .label { font-size: 0.8rem; color: var(--muted-color); margin-bottom: 0.1rem; }
        .detail-item .value { font-weight: 500; font-size: 0.9rem; }
        .detail-item .value.highlight { font-weight: 700; color: var(--success-color); }
        .detail-item .value.font-monospace { font-family: 'Courier New', monospace; letter-spacing: 0.5px; font-size: 0.85rem; }

        .info-box { background-color: #e8f4f8; border-left: 4px solid var(--info-color); padding: 1rem; border-radius: 4px; font-size: 0.85rem; margin-bottom: 1rem; }
        [dir="rtl"] .info-box { border-left: none; border-right: 4px solid var(--info-color); }

        .payment-summary { background-color: var(--background-light); border-radius: 6px; padding: 1rem; border: 1px solid var(--border-color); }
        .payment-summary ul { list-style: none; padding-left: 0; margin-bottom: 0;}
        .payment-summary li { display: flex; justify-content: space-between; align-items: center; padding: .5rem 0; font-size: 0.9rem; border-bottom: 1px solid #e9ecef; }
        .payment-summary li:last-child { border-bottom: none; }
        .payment-summary .total-payable { background-color: #e9f5ec; margin: 1rem -1rem -1rem; padding: 1rem 1rem; border-top: 1px solid #c3e6cb; }
        .payment-summary .total-payable .label { font-weight: 700; color: #155724; }
        .payment-summary .total-payable .value { font-size: 1.1rem; font-weight: 700; color: #155724; }
        
        [dir="rtl"] .payment-summary li { flex-direction: row-reverse; }
        
        .approval-timeline { position: relative; padding-left: 5px; }
        .timeline-item { position: relative; padding-bottom: 1rem; padding-left: 30px; min-height: 20px; }
        .timeline-item:last-child { padding-bottom: 0; }
        .timeline-item::before { content: ''; position: absolute; left: 0; top: 10px; bottom: 0; width: 2px; background: var(--border-color); }
        .timeline-item:last-child::before { display: none; }
        .timeline-item .icon { position: absolute; left: -9px; top: 0; width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 10px; border: 2px solid #fff; }
        .timeline-item.approved .icon { background-color: var(--success-color); }
        .timeline-item.pending .icon { background-color: var(--warning-color); }
        .timeline-item.rejected .icon { background-color: var(--danger-color); }
        .timeline-item.future .icon, .timeline-item .icon { background-color: #ced4da; }
        .timeline-item .status { font-weight: 600; line-height: 20px; font-size: 0.9rem; }
        
        [dir="rtl"] .approval-timeline { padding-left: 0; padding-right: 5px; }
        [dir="rtl"] .timeline-item { padding-left: 0; padding-right: 30px; }
        [dir="rtl"] .timeline-item::before { left: auto; right: 0; }
        [dir="rtl"] .timeline-item .icon { left: auto; right: -9px; }

        .approval-flat-list { padding: 0; }
        .approval-flat-item { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; padding: 8px 0; }
        .approval-flat-item i { width: 16px; text-align: center; font-size: 1rem; }
        .approval-flat-item strong { font-size: 0.95rem; }
        .approval-flat-note { flex-basis: 100%; margin-left: 24px; font-style: italic; color: var(--danger-color); font-size: 0.85rem; }
        [dir="rtl"] .approval-flat-note { margin-left: 0; margin-right: 24px; }

        .notes-section { background-color: #fff9e6; border-left: 4px solid var(--warning-color); padding: 1rem; border-radius: 4px; font-size: 0.85rem; }
        
        [dir="rtl"] .notes-section { border-left: none; border-right: 4px solid var(--warning-color); }
        
        .toggle-approval-btn { margin: 2rem 0 1rem 0; }
        #approvalStatusContainer { display: flex; transition: all 0.3s ease-in-out; }
        
        .report-footer { padding: 1rem 1.5rem; border-top: 1px solid var(--border-color); margin-top: 1.5rem; }
        .signature-area { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; text-align: center; margin-top: 2.5rem; }
        .signature-box { border-top: 1px solid var(--muted-color); padding-top: 0.5rem; }
        .signature-box p { margin: 0; color: var(--muted-color); font-size: 0.8rem; }

        .report-footer { padding: 1rem 1.5rem; border-top: 1px solid var(--border-color); margin-top: 1.5rem; text-align: center; color: var(--muted-color); font-size: 0.8rem; }

        @media print {
            @page { size: A4; margin: 0.5cm; }
            body { background-color: #fff !important; font-size: 12px; }
            .no-print, .left.side-menu, .footer, .topbar { display: none !important; }
            #wrapper, .content-page, .content, .container-fluid { padding: 0 !important; margin: 0 !important; }
            .report-wrapper { max-width: 100%; margin: 0; box-shadow: none; border: none; border-radius: 0; }
            .report-body { padding: 1cm 0.5cm; }
            .employee-banner, .payment-summary, .notes-section { background-color: #f8f9fa !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .signature-area { margin-top: 3rem; }
            .report-section { margin-bottom: 1rem; }
        }
    </style>
</head>

<body class="enlarged" data-keep-enlarged="true">
    <div id="wrapper">
        <div class="left side-menu no-print">
            <div class="slimscroll-menu" id="remove-scroll">
                <div class="topbar-left">
                    <a href="dashboard.php" class="logo">
                        <span><img src="<?= get_setting($conDB, 'logo') ?>" alt="" height="22"></span>
                        <i><img src="<?= get_setting($conDB, 'white_logo') ?>" alt="" height="28"></i>
                    </a>
                </div>
                <?php include("./includes/main_menu.php"); ?>
                <div class="clearfix"></div>
            </div>
        </div>

        <div class="content-page">
            <?php include("./includes/topbar.php"); ?>
            <div class="content">
                <div class="container-fluid">
                    <div class="text-right no-print mb-3">
                        <a href="javascript:void(0);" onclick="window.print()" class="btn btn-primary waves-effect waves-light">
                            <i class="fa fa-print mr-1"></i> Print Report
                        </a>
                    </div>

                    <div class="report-wrapper">
                        <!-- Report Header -->
                        <div class="report-header">
                            <div class="logo-container">
                                <img src="<?= get_setting($conDB, 'logo') ?>" alt="Company Logo">
                            </div>
                            <div class="report-meta">
                                <p class="report-title"><?= __('business_trip_report') ?></p>
                                <p class="report-subtitle"><?= __('request_id') ?>: #<?= display_or_na($request['id'] ?? null); ?></p>
                            </div>
                        </div>

                        <!-- Report Body -->
                        <div class="report-body">
                            <!-- Employee Banner -->
                            <div class="employee-banner">
                                <img src="<?= getAvatarImagePath($request['avatar'] ?? '', $request['sex'] ?? 1); ?>" alt="Employee Avatar" class="avatar">
                                <div class="info">
                                    <h4><?= getDisplayName($request['employee_name'] ?? null); ?></h4>
                                    <p><?= __('employee_id') ?>: <?= display_or_na($request['emp_id'] ?? null); ?> | <?= ($is_rtl ?? false ? $request['dept_name_ar'] : $request['dept_name']); ?><?= getDisplayName($request['section_name']) ? ' / ' . getDisplayName($request['section_name']) : '' ?></p>
                                </div>
                            </div>

                            <!-- Trip Information Section -->
                            <div class="report-section">
                                <h5 class="section-title">
                                    <i class="fa fa-briefcase"></i> <?= __('trip_information') ?>
                                </h5>
                                <div class="grid-details">
                                    <div class="detail-item">
                                        <div class="label"><?= __('trip_type') ?></div>
                                        <div class="value"><?= __((string)$trip_type) ?></div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="label"><?= __('status') ?></div>
                                        <div class="value"><span class="badge badge-<?= $status_badge_class ?>"><?= __((string)$current_status) ?></span></div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="label"><?= __('submitted_on') ?></div>
                                        <div class="value"><?= htmlspecialchars((string)$request['created_at']) ?></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Trip Details Section -->
                            <div class="report-section">
                                <h5 class="section-title">
                                    <i class="fa fa-map-marker"></i> <?= __('trip_details') ?>
                                </h5>
                                <div class="grid-details">
                                    <div class="detail-item">
                                        <div class="label"><?= __('destination') ?></div>
                                        <div class="value"><?= $destination_display ?></div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="label"><?= __('start_date') ?></div>
                                        <div class="value"><?= htmlspecialchars((string)$request['trip_start_date']) ?></div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="label"><?= __('end_date') ?></div>
                                        <div class="value"><?= htmlspecialchars((string)$request['trip_end_date']) ?></div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="label"><?= __('duration') ?></div>
                                        <div class="value">
                                            <?php
                                            $start = new DateTime($request['trip_start_date']);
                                            $end = new DateTime($request['trip_end_date']);
                                            $diff = $start->diff($end);
                                            echo (int)($diff->format('%a') ?? 0) + 1 . ' ' . __('days');
                                            ?>
                                        </div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="label"><?= __('purpose') ?></div>
                                        <div class="value"><?= getDisplayName((string)$request['trip_purpose']) ?></div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="label"><?= __('transportation') ?></div>
                                        <div class="value">
                                            <?php 
                                            $transport_type = $request['transportation_type'] ?? '';
                                            if ($transport_type === 'own_car') {
                                                echo '<i class="fa fa-car" style="margin-right: 8px;"></i>' . __('own_car');
                                            } elseif ($transport_type === 'rental') {
                                                echo '<i class="fa fa-taxi" style="margin-right: 8px;"></i>' . __('rental_car');
                                            } elseif ($transport_type === 'by_air') {
                                                echo '<i class="fa fa-plane" style="margin-right: 8px;"></i>' . __('by_air');
                                            } else {
                                                echo __($transport_type);
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Additional Details (International Trips) -->
                            <?php if ($trip_type === 'international'): ?>
                            <div class="report-section">
                                <h5 class="section-title">
                                    <i class="fa fa-plane"></i> <?= __('international_trip_details') ?>
                                </h5>
                                <div class="grid-details">
                                    <div class="detail-item">
                                        <div class="label"><?= __('destination_country') ?></div>
                                        <div class="value"><?= getDisplayName((string)$request['destination_country']) ?></div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="label"><?= __('visa_required') ?></div>
                                        <div class="value"><?= ((int)($request['visa_required'] ?? 0) === 1) ? __('yes') : __('no') ?></div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Allowances & Ticket Fares Section -->
                            <?php if (!empty($allowance_summary) || !empty($allowance_items) || $trip_allowance_days > 0): ?>
                            <div class="report-section">
                                <h5 class="section-title">
                                    <i class="fa fa-money-bill-wave"></i> <?= __('allowances_and_ticket_fares', 'Allowances and Ticket Fares') ?>
                                </h5>

                                <?php 
                                // Get transportation type to check if tickets should be shown
                                $transport_type_for_tickets = $request['transportation_type'] ?? '';
                                $show_ticket_fares = ($transport_type_for_tickets === 'by_air');
                                
                                // Filter out zero-value "others" items and ticket_fares if not by_air
                                $filtered_allowance_items = array_filter($allowance_items, function($item) use ($show_ticket_fares) {
                                    $type = (string)($item['allowance_type'] ?? 'others');
                                    $total = (float)($item['line_total'] ?? 0);
                                    // Hide zero-value others
                                    if ($type === 'others' && $total == 0) {
                                        return false;
                                    }
                                    // Hide ticket_fares if not by_air transportation
                                    if ($type === 'ticket_fares' && !$show_ticket_fares) {
                                        return false;
                                    }
                                    return true;
                                });
                                ?>
                                <?php if (!empty($filtered_allowance_items)): ?>
                                    <div class="table-responsive mb-3">
                                        <table class="table table-sm table-bordered mb-0">
                                            <thead style="background-color: var(--background-light);">
                                                <tr>
                                                    <th><?= __('allowance_type', 'Allowance Type') ?></th>
                                                    <th><?= __('comment', 'Comment') ?></th>
                                                    <th class="text-center"><?= __('amount', 'Amount') ?></th>
                                                    <th class="text-center"><?= __('qty', 'Qty') ?></th>
                                                    <th class="text-center"><?= __('total', 'Total') ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($filtered_allowance_items as $allowance_item): ?>
                                                    <?php
                                                    $line_type = (string)($allowance_item['allowance_type'] ?? 'others');
                                                    $line_type_label_map = [
                                                        'ticket_fares' => __('ticket_fares', 'Ticket Fares'),
                                                        'hotel_allowance' => __('hotel_allowance', 'Hotel Allowance'),
                                                        'transportation_allowance' => __('transportation_allowance', 'Transportation Allowance'),
                                                        'others' => __('other_allowance', 'Others')
                                                    ];
                                                    $line_type_label = $line_type_label_map[$line_type] ?? ucwords(str_replace('_', ' ', $line_type));
                                                    $line_description = (string)($allowance_item['description'] ?? '');
                                                    $line_amount = (float)($allowance_item['unit_amount'] ?? 0);
                                                    $line_qty = (int)($allowance_item['qty'] ?? 0);
                                                    $line_total = (float)($allowance_item['line_total'] ?? 0);
                                                    ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars((string)$line_type_label) ?></td>
                                                        <td><?= $line_description !== '' ? htmlspecialchars($line_description) : '&mdash;' ?></td>
                                                        <td class="text-center"><?= number_format($line_amount, 2) ?></td>
                                                        <td class="text-center"><?= $line_qty ?></td>
                                                        <td class="text-center"><strong><?= number_format($line_total, 2) ?></strong></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>

                                <div class="grid-details">
                                    <?php if ($show_ticket_fares): ?>
                                    <div class="detail-item">
                                        <div class="label"><?= __('ticket_fares', 'Ticket Fares') ?></div>
                                        <div class="value\"><?= number_format($ticket_fares_total, 2) ?></div>
                                    </div>
                                    <?php endif; ?>
                                    <div class="detail-item">
                                        <div class="label\"><?= __('hotel_allowance', 'Hotel Allowance') ?> (<?= __('calculated', 'Calculated') ?>)</div>
                                        <div class="value\"><?= number_format($calc_hotel_total, 2) ?></div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="label\"><?= __('transportation_allowance', 'Transportation Allowance') ?> (<?= __('calculated', 'Calculated') ?>)</div>
                                        <div class="value\"><?= number_format($calc_transport_total, 2) ?></div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="label\"><?= __('daily_allowance', 'Daily Allowance') ?> (<?= __('calculated', 'Calculated') ?>)</div>
                                        <div class="value\"><?= number_format($calc_daily_allowance_total, 2) ?></div>
                                    </div>
                                    <?php if ($other_allowance_total > 0): ?>
                                    <div class="detail-item">
                                        <div class="label"><?= __('other_allowance', 'Others') ?></div>
                                        <div class="value\"><?= number_format($other_allowance_total, 2) ?></div>
                                    </div>
                                    <?php endif; ?>
                                    <div class="detail-item">
                                        <div class="label"><?= __('grand_total', 'Grand Total') ?></div>
                                        <div class="value highlight\"><?= number_format($report_grand_total, 2) ?></div>
                                    </div>
                                </div>

                                <?php if (!empty($allowance_summary['notes'])): ?>
                                    <div class="info-box" style="margin-top: 1rem;">
                                        <strong><?= __('notes', 'Notes') ?>:</strong>
                                        <?= getDisplayName(htmlspecialchars((string)$allowance_summary['notes'])) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>

                            <?php if ($is_hr_payroll_viewer || $is_system_admin): ?>
                            <div class="report-section">
                                <h5 class="section-title">
                                    <i class="fa fa-calculator"></i> <?= __('troubleshooting_calculation', 'Troubleshooting Calculation (HR Payroll)') ?>
                                </h5>
                                <div class="info-box">
                                    <div><strong><?= __('basic_salary', 'Basic Salary') ?>:</strong> <?= number_format($basic_salary, 2) ?></div>
                                    <div><strong><?= __('allowance_days', 'Allowance Days') ?>:</strong> <?= (int)$trip_allowance_days ?></div>
                                    <div><strong><?= __('salary_level', 'Salary Level') ?>:</strong> <?= $matched_scale ? ('Level ' . (int)$matched_scale['level'] . ' (' . (int)$matched_scale['min'] . ' - ' . (int)$matched_scale['max'] . ')') : __('out_of_scale', 'Out of defined scale') ?></div>
                                    <hr style="margin:8px 0;">
                                    <div><strong><?= __('hotel_allowance', 'Hotel Allowance') ?>:</strong> <?= number_format($calc_hotel_daily, 2) ?> × <?= (int)$hotel_allowance_days ?> (<?= __('days', 'days') ?> - 1) = <?= number_format($calc_hotel_total, 2) ?></div>
                                    <div><strong><?= __('transportation_allowance', 'Transportation Allowance') ?>:</strong> <?= number_format($calc_transport_daily, 2) ?> × <?= (int)$trip_allowance_days ?> = <?= number_format($calc_transport_total, 2) ?></div>
                                    <div><strong><?= __('daily_allowance', 'Daily Allowance') ?>:</strong> <?= number_format($calc_daily_allowance_total, 0) ?></div>
                                    <div style="padding-left: 12px;">
                                        1st week: (<?= number_format($basic_salary, 2) ?> / 60) × <?= (int)$week1_days ?> = <?= number_format($week1_daily_total, 0) ?>
                                    </div>
                                    <div style="padding-left: 12px;">
                                        2nd week: (<?= number_format($basic_salary, 2) ?> / 90) × <?= (int)$week2_days ?> = <?= number_format($week2_daily_total, 0) ?>
                                    </div>
                                    <div style="padding-left: 12px;">
                                        3rd week: (<?= number_format($basic_salary, 2) ?> / 120) × <?= (int)$week3_days ?> = <?= number_format($week3_daily_total, 0) ?>
                                    </div>
                                    <div><strong><?= __('other_allowance', 'Others') ?>:</strong> <?= number_format($other_allowance_total, 2) ?></div>
                                    <?php if ($show_ticket_fares): ?>
                                    <div><strong><?= __('ticket_fares', 'Ticket Fares') ?>:</strong> <?= number_format($ticket_fares_total, 2) ?></div>
                                    <?php endif; ?>
                                    <hr style="margin:8px 0;">
                                    <?php if ($show_ticket_fares): ?>
                                    <div><strong><?= __('grand_total', 'Grand Total') ?>:</strong> <?= number_format($ticket_fares_total, 2) ?> + <?= number_format($calc_hotel_total, 2) ?> + <?= number_format($calc_transport_total, 2) ?> + <?= number_format($calc_daily_allowance_total, 2) ?> + <?= number_format($other_allowance_total, 2) ?> = <?= number_format($report_grand_total, 2) ?></div>
                                    <?php else: ?>
                                    <div><strong><?= __('grand_total', 'Grand Total') ?>:</strong> <?= number_format($calc_hotel_total, 2) ?> + <?= number_format($calc_transport_total, 2) ?> + <?= number_format($calc_daily_allowance_total, 2) ?> + <?= number_format($other_allowance_total, 2) ?> = <?= number_format($report_grand_total, 2) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Notes Section -->
                            <?php if (!empty($request['request_notes'])): ?>
                            <div class="report-section">
                                <h5 class="section-title">
                                    <i class="fa fa-sticky-note"></i> <?= __('additional_notes') ?>
                                </h5>
                                <div class="info-box">
                                    <?= getDisplayName(htmlspecialchars((string)$request['request_notes'])) ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- View Approval Status Button -->
                            <div class="text-center no-print" style="margin: 2rem 0;">
                                <button type="button" class="btn btn-outline-primary waves-effect waves-light" id="toggleApprovalBtn" onclick="toggleApprovalStatus()">
                                    <i class="fa fa-eye mr-2"></i> <?= __('view_approval_status') ?>
                                </button>
                            </div>

                            <!-- Approval Chain Timeline - Hidden by default -->
                            <div id="approvalStatusContainer" style="display: none;">
                                <div class="report-section">
                                    <h5 class="section-title">
                                        <i class="fa fa-tasks"></i> <?= __('approval_status') ?>
                                    </h5>
                                    <?php if (!empty($approval_chain)): ?>
                                        <div class="approval-flat-list">
                                            <?php foreach ($approval_chain as $step): ?>
                                                <?php
                                                $step_status = $step['status'] ?? 'pending';
                                                $status_color = 'text-muted';
                                                if ($step_status === 'approved') $status_color = 'text-success';
                                                elseif ($step_status === 'rejected') $status_color = 'text-danger';
                                                elseif ($step_status === 'pending') $status_color = 'text-warning';

                                                $role_icon = 'fa-user';
                                                if (!empty($step['approver_role']) && stripos($step['approver_role'], 'hr') !== false) {
                                                    $role_icon = 'fa-user-shield';
                                                } elseif (!empty($step['approver_role']) && (stripos($step['approver_role'], 'manager') !== false || stripos($step['approver_role'], 'administrator') !== false || stripos($step['approver_role'], 'gm') !== false)) {
                                                    $role_icon = 'fa-user-tie';
                                                }

                                                $approver_display_name = getDisplayName((string)($step['approver_name'] ?? ('Emp#' . (int)($step['approver_id'] ?? 0))));
                                                $level_scope = !empty($step['approver_dept_name']) ? getDisplayName($step['approver_dept_name']) : ucwords(str_replace('_', ' ', (string)($step['approver_role'] ?? '')));
                                                ?>
                                                <div class="approval-flat-item">
                                                    <i class="fa <?= $role_icon ?> <?= $status_color ?>"></i>
                                                    <strong><?= htmlspecialchars($approver_display_name) ?></strong>
                                                    <small class="text-muted">(<?= __('level') ?> <?= (int)($step['approval_level'] ?? 0) ?><?= $level_scope !== '' ? ': ' . htmlspecialchars((string)$level_scope) : '' ?>)</small>
                                                    <?php if ($step_status === 'rejected' && !empty($step['note'])): ?>
                                                        <div class="approval-flat-note"><?= getDisplayName(htmlspecialchars((string)$step['note'])) ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="info-box">
                                            No approval chain found for this request.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Signature Lines -->
                            <div class="report-footer">
                                <div class="signature-area">
                                    <div class="signature-box"><p><?= __('compensation_signature') ?></p></div>
                                    <div class="signature-box"><p><?= __('hr_manager_signature') ?></p></div>
                                    <div class="signature-box"><p><?= __('general_manager_signature') ?></p></div>
                                </div>
                            </div>

                        </div>

                        <!-- Report Footer -->
                        <div class="report-footer">
                            <p>Automatically generated on <?= date('Y-m-d H:i:s') ?> by <?= $site_title ?> - <?= __('business_trip_report') ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <footer class="footer no-print"><?= $site_footer ?></footer>
        </div>
    </div>

    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/metisMenu.min.js"></script>
    <script src="assets/js/waves.js"></script>
    <script src="assets/js/jquery.slimscroll.js"></script>
    <script src="assets/js/jquery.core.js"></script>
    <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>

    <script>
    function toggleApprovalStatus() {
        const container = document.getElementById('approvalStatusContainer');
        const btn = document.getElementById('toggleApprovalBtn');
        
        if (container.style.display === 'none' || container.style.display === '') {
            container.style.display = 'block';
            btn.innerHTML = '<i class="fa fa-eye-slash mr-2"></i> <?= __('hide_approval_status') ?>';
        } else {
            container.style.display = 'none';
            btn.innerHTML = '<i class="fa fa-eye mr-2"></i> <?= __('view_approval_status') ?>';
        }
    }
    </script>
</body>
</html>

<?php
    $conDB->close();
}
?>
