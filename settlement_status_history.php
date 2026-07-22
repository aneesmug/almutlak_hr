<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/special_access_helper.php';
if (file_exists(__DIR__ . '/includes/functions.php')) {
    require_once __DIR__ . '/includes/functions.php';
}

// Load translations
$current_lang = $_SESSION['lang'] ?? 'en';
load_language($current_lang);

// Restrict access: Employees cannot view this detailed report page,
// unless explicitly granted via app_settings -> Special Access.
if (
    isset($isEmployee) && $isEmployee === true
    && !user_has_special_access($conDB, $empid ?? '', 'access_settlement_status_history', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)
) {
    header("Location: ./profile.php");
    exit();
}

// Input
$request_inv_no = isset($_GET['request_inv_no']) ? trim($_GET['request_inv_no']) : '';
if ($request_inv_no === '') {
    die('<div style="padding:16px;margin:16px;border:1px solid #f5c2c7;background:#f8d7da;color:#842029;border-radius:8px;">ERROR: Settlement request invoice number not provided.</div>');
}

// Settlement details
$settlement = null;
$sql = "SELECT s.*, 
               e.name AS employee_name, e.avatar, e.dept, e.passport_number, e.passport_exp, e.gosi, e.country as country_id,
               d.dep_nme AS department_name,
               d.dep_nme_ar AS department_name_ar,
               v.vac_type, v.fly_type, v.vacdays, v.start_date, v.vacation_salary_type, v.is_deductible, v.auto_gosi_deduction,
               v.overtime_hours, v.deduction_hours, v.deduction_days, v.other_earnings, v.other_deductions,
               (SELECT basic FROM emp_salary WHERE emp_id = s.emp_id AND status = 1 ORDER BY id DESC LIMIT 1) AS salary_basic,
               (SELECT housing FROM emp_salary WHERE emp_id = s.emp_id AND status = 1 ORDER BY id DESC LIMIT 1) AS salary_housing,
               (SELECT transport FROM emp_salary WHERE emp_id = s.emp_id AND status = 1 ORDER BY id DESC LIMIT 1) AS salary_transport,
               (SELECT food FROM emp_salary WHERE emp_id = s.emp_id AND status = 1 ORDER BY id DESC LIMIT 1) AS salary_food,
               (SELECT misc FROM emp_salary WHERE emp_id = s.emp_id AND status = 1 ORDER BY id DESC LIMIT 1) AS salary_misc,
               (SELECT cashier FROM emp_salary WHERE emp_id = s.emp_id AND status = 1 ORDER BY id DESC LIMIT 1) AS salary_cashier,
               (SELECT fuel FROM emp_salary WHERE emp_id = s.emp_id AND status = 1 ORDER BY id DESC LIMIT 1) AS salary_fuel,
               (SELECT tel FROM emp_salary WHERE emp_id = s.emp_id AND status = 1 ORDER BY id DESC LIMIT 1) AS salary_tel,
               (SELECT other FROM emp_salary WHERE emp_id = s.emp_id AND status = 1 ORDER BY id DESC LIMIT 1) AS salary_other,
               (SELECT guard FROM emp_salary WHERE emp_id = s.emp_id AND status = 1 ORDER BY id DESC LIMIT 1) AS salary_guard
        FROM settlement_records s
        JOIN employees e ON s.emp_id = e.emp_id
        LEFT JOIN department d ON e.dept = d.id
        LEFT JOIN emp_vacation v ON v.request_inv_no = SUBSTR(s.request_inv_no, 6)
        WHERE s.request_inv_no = ?
        LIMIT 1";
$stmt = $conDB->prepare($sql);
$stmt->bind_param('s', $request_inv_no);
$stmt->execute();
$res = $stmt->get_result();
if ($res && $res->num_rows === 1) {
    $settlement = $res->fetch_assoc();
}
$stmt->close();
if (!$settlement) {
    die('<div style="padding:16px;margin:16px;border:1px solid #f5c2c7;background:#f8d7da;color:#842029;border-radius:8px;">ERROR: Settlement request not found.</div>');
}

// Calculate payable amount from vacation data (same logic as vacation_report_details.php)
$payableAmount = 0;
if (!empty($settlement['vac_type']) && !empty($settlement['salary_basic'])) {
    $vac_type = $settlement['vac_type'];
    $fly_type = $settlement['fly_type'];
    $vacation_salary_type = $settlement['vacation_salary_type'] ?? 'payroll';
    $approved_days = (float)($settlement['vacdays'] ?? 0);
    
    $is_encashment = (trim(strtolower($vac_type)) === 'encashed');
    $is_emergency = ($fly_type === 'emergency');
    $is_settlement_payable_vacation = isSettlementPayableVacation(
        $vac_type,
        $fly_type,
        $settlement['country_id'] ?? 0,
        $approved_days,
        $settlement['is_deductible'] ?? 0,
        $vacation_salary_type
    );
    
    $non_payable_leave_types = ['Sick Leave', 'Casual Leave', 'Maternity Leave', 'Compassionate Leave', 'Business Trip', 'Compensatory Leave'];
    $is_non_payable_leave = in_array($vac_type, $non_payable_leave_types);
    
    $calculate_payments = !$is_non_payable_leave && !$is_emergency && $is_settlement_payable_vacation;
    
    if ($calculate_payments) {
        $basic_salary = (float)($settlement['salary_basic'] ?? 0);
        $total_monthly_salary = $basic_salary + ($settlement['salary_housing'] ?? 0) + ($settlement['salary_transport'] ?? 0) + ($settlement['salary_food'] ?? 0) + ($settlement['salary_misc'] ?? 0) + ($settlement['salary_cashier'] ?? 0) + ($settlement['salary_fuel'] ?? 0) + ($settlement['salary_tel'] ?? 0) + ($settlement['salary_other'] ?? 0) + ($settlement['salary_guard'] ?? 0);
        
        if ($total_monthly_salary > 0) {
            // Keep 30-day basis for all payroll calculations except Working Days Salary
            $days_in_month = 30;
            $working_days_month_days = 30;
            if (!empty($settlement['start_date'])) {
                $start_ts = strtotime($settlement['start_date']);
                if ($start_ts !== false) {
                    $working_days_month_days = (int)date('t', $start_ts);
                }
            }
            $daily_rate = round($total_monthly_salary / $days_in_month, 2);
            $working_daily_rate = ($working_days_month_days > 0) ? round($total_monthly_salary / $working_days_month_days, 2) : 0;
            
            $dailyRateDeduction = round($total_monthly_salary / $days_in_month, 2);
            $hourlyRateDeduction = round($dailyRateDeduction / 8, 2);
            $overtimeHourlyRate = round((($basic_salary / 240) / 2) + ($total_monthly_salary / 240), 2);
            
            $working_days_salary = 0;
            $vacation_salary = 0;
            $gosi_deduction = 0;
            $overtime_amount = 0;
            $other_earnings = 0;
            $deduction_amount = 0;
            
            // Calculate working days salary for deductible payable vacations.
            if ($is_settlement_payable_vacation && !empty($settlement['start_date'])) {
                try {
                    $start_date_obj = new DateTime($settlement['start_date']);
                    // Exclude the start day from working-days salary (working days BEFORE departure)
                    $working_days = (int)$start_date_obj->format('d') - 1;
                    if ($working_days_month_days > 0 && $working_days >= $working_days_month_days) {
                        $working_days_salary = round($total_monthly_salary);
                    } else {
                        $working_days_salary = round(($total_monthly_salary / 30) * $working_days);
                    }
                } catch (Exception $e) {
                    $working_days_salary = 0;
                }
            }
            
            // Calculate vacation salary for deductible payable vacations with payroll type.
            if ($is_settlement_payable_vacation && $vacation_salary_type === 'payroll') {
                $vacation_salary = round($daily_rate * $approved_days);
            }
            
            // Calculate overtime
            if (!empty($settlement['overtime_hours']) && $settlement['overtime_hours'] > 0) {
                $overtime_amount = round($overtimeHourlyRate * $settlement['overtime_hours']);
            }

            // Other earnings
            if (!empty($settlement['other_earnings']) && $settlement['other_earnings'] > 0) {
                $other_earnings = round((float)$settlement['other_earnings'], 2);
            }
            
            // Calculate deductions
            $ded_hours = !empty($settlement['deduction_hours']) ? $settlement['deduction_hours'] : 0;
            $ded_days = !empty($settlement['deduction_days']) ? $settlement['deduction_days'] : 0;
            $other_ded = !empty($settlement['other_deductions']) ? $settlement['other_deductions'] : 0;
            
            if ($ded_hours > 0 || $ded_days > 0 || $other_ded > 0) {
                $deduction_hours_amount = round($hourlyRateDeduction * $ded_hours);
                $deduction_days_amount = round($dailyRateDeduction * $ded_days);
                $deduction_amount = round($deduction_hours_amount + $deduction_days_amount + $other_ded);
            }
            
            // Calculate GOSI
            // Check auto_gosi_deduction flag: if 1 (enabled), apply GOSI; if 0 (disabled), skip
            $auto_gosi_deduction = (int)($settlement['auto_gosi_deduction'] ?? 1);  // Default to 1 for backward compatibility
            
            if ($auto_gosi_deduction && $settlement['country_id'] == 191 && !empty($settlement['gosi']) && is_numeric($settlement['gosi'])) {
                $gosi_percentage = (float)$settlement['gosi'];
                if ($is_settlement_payable_vacation && $vacation_salary_type === 'payroll') {
                    // Match payroll config: GOSI is based on basic + housing salary components.
                    $gosi_base = (float)$basic_salary + (float)($settlement['salary_housing'] ?? 0);
                    $gosi_deduction = round(($gosi_base * $gosi_percentage) / 100, 2);
                }
            }
            
            // Calculate total payable
            if ($is_encashment) {
                $payableAmount = 0;
            } elseif ($is_settlement_payable_vacation) {
                $payableAmount = round(($working_days_salary + $vacation_salary) + $overtime_amount + $other_earnings - $deduction_amount - $gosi_deduction);
            }
        }
    }
}

// Fallback to stored settlement_amount if calculated is 0
if ($payableAmount <= 0) {
    $payableAmount = (float)($settlement['settlement_amount'] ?? 0);
}

// Enforce department scoping: Only HR and System Admin can view other departments,
// otherwise allow if the user is part of the approval chain for this request
$canSeeAll = ($is_system_admin ?? false) || ($isHR ?? false);
if (!$canSeeAll) {
    $userDeptId = $user_dept ?? null;
    $sameDept = ($userDeptId !== null) ? ((int)$settlement['dept'] === (int)$userDeptId) : false;
    $inChain = false;
    if (!$sameDept) {
        // Check if current user appears in approval chain for this settlement
        if ($chk = $conDB->prepare("SELECT 1 FROM request_approvers ra JOIN approval_request_types t ON t.id = ra.request_type_id AND t.type_name = 'settlement' WHERE ra.request_inv_no = ? AND ra.approver_id = ? LIMIT 1")) {
            $chk->bind_param('si', $request_inv_no, $empid);
            if ($chk->execute()) {
                $resChk = $chk->get_result();
                $inChain = ($resChk && $resChk->num_rows > 0);
            }
            $chk->close();
        }
    }
    if (!$sameDept && !$inChain) {
        http_response_code(403);
        die("<div style=\"padding:16px;margin:16px;border:1px solid #f5c2c7;background:#f8d7da;color:#842029;border-radius:8px;\">Access denied: You are not authorized to view this request.</div>");
    }
}

// Status history - Build from both smt_request_status and request_approvers
$history = [];

// First, try to get history from smt_request_status
$stmt = $conDB->prepare("SELECT status, note, emp_name, created_at FROM smt_request_status WHERE inv_no = ? ORDER BY created_at ASC");
$stmt->bind_param('s', $request_inv_no);
$stmt->execute();
$hr = $stmt->get_result();
while ($row = $hr->fetch_assoc()) {
    $history[] = $row;
}
$stmt->close();

// Status history
$history = [];
$stmt = $conDB->prepare("SELECT status, note, emp_name, created_at FROM smt_request_status WHERE inv_no = ? ORDER BY created_at ASC");
$stmt->bind_param('s', $request_inv_no);
$stmt->execute();
$hr = $stmt->get_result();
while ($row = $hr->fetch_assoc()) {
    $history[] = $row;
}
$stmt->close();


// Approval chain
$chain = [];

// Resolve request_type_id for 'settlement' dynamically (fallback to 1)
$request_type_id = 1;
if ($typeStmt = $conDB->prepare("SELECT id FROM approval_request_types WHERE type_name = 'settlement' LIMIT 1")) {
    $typeStmt->execute();
    $typeRes = $typeStmt->get_result();
    if ($typeRow = $typeRes->fetch_assoc()) {
        $request_type_id = (int)$typeRow['id'];
    }
    $typeStmt->close();
}

$stmt = $conDB->prepare("SELECT ra.approval_level, ra.status, ra.action_date,
                                COALESCE(e.name, al.fullname, al.username) AS approver_name,
                                al.user_type
                         FROM request_approvers ra
                         LEFT JOIN employees e ON ra.approver_id = e.emp_id
                         LEFT JOIN admin_login al ON al.emp_id = ra.approver_id
                         WHERE ra.request_inv_no = ? AND ra.request_type_id = ?
                         ORDER BY ra.approval_level ASC");
$stmt->bind_param('si', $request_inv_no, $request_type_id);
$stmt->execute();
$cr = $stmt->get_result();
while ($row = $cr->fetch_assoc()) {
    $chain[] = $row;
}
$stmt->close();

// Helpers
$status_class = 'secondary';
$status_icon = 'fa-circle';
if ($settlement['settlement_status'] === 'approved') { $status_class = 'success'; $status_icon = 'fa-check-circle'; }
elseif ($settlement['settlement_status'] === 'rejected') { $status_class = 'danger'; $status_icon = 'fa-times-circle'; }
elseif ($settlement['settlement_status'] === 'pending' || $settlement['settlement_status'] === 'pending_approval') { $status_class = 'warning'; $status_icon = 'fa-clock'; }
elseif ($settlement['settlement_status'] === 'completed') { $status_class = 'primary'; $status_icon = 'fa-check-circle'; }

// Use getAvatarImagePath function for proper gender-based default avatars
$avatar_path = getAvatarImagePath($settlement['avatar'] ?? '', $settlement['sex'] ?? 1);

?>
<!doctype html>
<html lang="<?= isset($current_lang) ? $current_lang : 'en' ?>" <?= (isset($is_rtl) && $is_rtl) ? 'dir="rtl"' : '' ?>>
<head>
    <meta charset="utf-8" />
    <title><?= isset($site_title) ? $site_title : 'Al-Mutlak' ?> - Settlement Approval History</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="<?= function_exists('get_setting') ? (get_setting($conDB, 'favicon') ?? 'assets/images/favicon.ico') : 'assets/images/favicon.ico' ?>">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .page-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 30px 0; margin-bottom: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); text-align: <?= $is_rtl ?? false ? 'right' : 'left' ?>; }
        .page-header h3 { margin: 0; font-weight: 600; }
        .info-card { background: #fff; border-radius: 10px; padding: 25px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); text-align: <?= $is_rtl ?? false ? 'right' : 'left' ?>; }
        .info-card h5 { color: #667eea; margin-bottom: 20px; font-weight: 600; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px; }
        .employee-header { display: flex; align-items: center; margin-bottom: 20px; text-align: <?= $is_rtl ?? false ? 'right' : 'left' ?>; }
        .employee-avatar { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; <?= $is_rtl ?? false ? 'margin-left' : 'margin-right' ?>: 20px; border: 3px solid #667eea; }
        .info-row { display: flex; padding: 12px 0; border-bottom: 1px solid #f0f0f0; }
        .info-row:last-child { border-bottom: none; }
        .info-label { font-weight: 600; color: #666; width: 40%; display: flex; align-items: center; }
        .info-label i { margin-right: 8px; color: #667eea; width: 20px; }
        .info-value { color: #333; width: 60%; }
        .chain-table thead { background: #f8f9fa; }
        .chain-table td, .chain-table th { padding: 15px; vertical-align: middle; }
        .level-badge { display: inline-block; width: 40px; height: 40px; line-height: 40px; text-align: center; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; font-weight: 700; }
        .attachment-link { display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; background: #f0f4ff; border-radius: 6px; color: #667eea; text-decoration: none; font-weight: 500; }
        .attachment-link:hover { background: #667eea; color: #fff; text-decoration: none; }
        
        /* Modern Timeline Design */
        .timeline { position: relative; padding: 10px 0; }
        .timeline:before { content: ''; position: absolute; left: 19px; top: 0; bottom: 0; width: 2px; background: #eaedf1; }
        .timeline-item { position: relative; padding-left: 50px; margin-bottom: 20px; }
        .timeline-item:last-child { margin-bottom: 0; }
        .timeline-marker { 
            position: absolute;
            top: 15px; 
            width: 24px; 
            height: 24px; 
            border-radius: 50%; background: #fff; border: 2px solid #adb5bd; 
            z-index: 1; display:flex; align-items:center; justify-content:center; 
            font-size: 10px; color: #adb5bd; transition: all 0.3s ease;
        }
        .timeline-item.approved .timeline-marker, .timeline-item.completed .timeline-marker { border-color: #28a745; color: #28a745; background-color: #f6ffed; }
        .timeline-item.rejected .timeline-marker { border-color: #dc3545; color: #dc3545; background-color: #fff1f0; }
        .timeline-item.pending .timeline-marker { border-color: #ffc107; color: #ffc107; background-color: #fffbe6; }
        
        .timeline-content { 
            background: #fff; padding: 15px; border-radius: 8px; 
            border: 1px solid #e3e8ee; box-shadow: 0 1px 3px rgba(0,0,0,0.05); 
            position: relative; transition: all 0.2s ease-in-out;
            text-align: <?= $is_rtl ?? false ? 'right' : 'left' ?>;
        }
        .timeline-content:hover { border-color: #667eea; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.1); }
        .status-badge { padding: 4px 10px; border-radius: 4px; font-size: 10px; font-weight: 700; text-transform: uppercase; }

        /* RTL Adjustments */
        [dir="rtl"] .timeline:before { left: auto; right: 19px; }
        [dir="rtl"] .timeline-item { padding-left: 0; padding-right: 50px; }
        [dir="rtl"] .timeline-marker { left: auto; right: 8px; }
        .btn-back { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; color: #fff; padding: 12px 30px; border-radius: 25px; font-weight: 600; }
    </style>
</head>
<body class="enlarged">
    <div class="page-header">
        <div class="container">
            <h3><i class="fas fa-history"></i> <?= __('settlement_approval_history') ?></h3>
            <p class="mb-0"><?= __('complete_approval_timeline') ?></p>
        </div>
    </div>

    <div class="container" style="margin-bottom:30px;">
        <!-- Employee Info -->
        <div class="info-card">
            <div class="employee-header">
                <img src="<?= htmlspecialchars($avatar_path) ?>" class="employee-avatar" alt="avatar" />
                <div>
                    <h4 style="margin:0;"><?= getDisplayName($settlement['employee_name'])?></h4>
                    <div class="text-muted" style="margin-top:6px;">
                        <strong><?= __('emp_id') ?>:</strong> <?= htmlspecialchars($settlement['emp_id']) ?>
                        &nbsp; | &nbsp;
                        <strong><?= __('department') ?>:</strong> <?= ($is_rtl ?? false ? $settlement['department_name_ar'] ?? 'N/A' : $settlement['department_name'] ?? 'N/A') ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="info-card">
                    <h5><i class="fas fa-file-invoice"></i> <?= __('settlement_details')?></h5>
                    <div class="info-row"><div class="info-label"><i class="fas fa-barcode"></i> <?= __('invoice_no') ?>:</div><div class="info-value"><code><?= htmlspecialchars($settlement['request_inv_no']) ?></code></div></div>
                    <div class="info-row"><div class="info-label"><i class="fas fa-coins"></i> <?= __('amount')?>:</div><div class="info-value"><strong style="color: #28a745;"><?= number_format(round($payableAmount), 2) ?> SAR</strong></div></div>
                    <div class="info-row"><div class="info-label"><i class="fas fa-calendar-alt"></i> <?= __('created_date') ?>:</div><div class="info-value"><?= htmlspecialchars(date('d M Y', strtotime($settlement['created_at']))) ?></div></div>
                    <div class="info-row"><div class="info-label"><i class="fas fa-info-circle"></i> <?= __('settlement_type') ?>:</div><div class="info-value"><strong><?= ucwords(__($settlement['request_type'] ?? 'N/A')) ?></strong></div></div>
                    <div class="info-row"><div class="info-label"><i class="fas fa-info-circle"></i> <?= __('status') ?>:</div><div class="info-value"><span class="badge badge-<?= $status_class ?>" style="font-size:14px;padding:8px 16px;"><i class="fas <?= $status_icon ?>"></i> <?= getDisplayName(ucwords(str_replace('_', ' ', $settlement['settlement_status']))) ?></span></div></div>
                    <?php if (!empty($settlement['notes'])): ?>
                    <div class="info-row"><div class="info-label"><i class="fas fa-comment"></i> <?= __('notes') ?>:</div><div class="info-value"><?= (nl2br(htmlspecialchars(getDisplayName($settlement['notes'])))) ?></div></div>
                    <?php endif; ?>
                    <?php if (!empty($settlement['attachment'])): 
                        // Handle attachment - could be single file or JSON array
                        $attachments = [];
                        if (is_array(json_decode($settlement['attachment'], true))) {
                            $attachments = json_decode($settlement['attachment'], true);
                        } else {
                            $attachments = [$settlement['attachment']];
                        }
                    ?>
                    <div class="info-row">
                        <div class="info-label"><i class="fas fa-paperclip"></i> <?= __('attachments') ?> (<?= count($attachments) ?>):</div>
                        <div class="info-value">
                            <button type="button" class="btn btn-sm btn-info" onclick='showAttachmentsModal(<?= json_encode($attachments) ?>, "<?= __('attachments') ?>")' style="padding: 6px 16px; font-weight: 500;">
                                <i class="fa fa-eye"></i> <?= __('view_attachments') ?>
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-md-6">
                <div class="info-card mb-3">
                    <h5><i class="fas fa-money-bill-wave"></i> <?= __('settlement_information') ?></h5>
                    <?php if (!empty($settlement['reason'])): ?>
                    <div class="info-row"><div class="info-label"><i class="fas fa-reason"></i> <?= __('reason') ?>:</div><div class="info-value"><?= nl2br(htmlspecialchars(getDisplayName($settlement['reason']))) ?></div></div>
                    <?php endif; ?>
                    <?php if (!empty($settlement['salary_basic'])): ?>
                    <div class="info-row"><div class="info-label"><i class="fas fa-money-bill"></i> <?= __('basic_salary') ?>:</div><div class="info-value"><strong>SAR <?= number_format($settlement['salary_basic'], 2) ?></strong></div></div>
                    <?php endif; ?>
                    <?php if (!empty($settlement['overtime_hours']) && $settlement['overtime_hours'] > 0): ?>
                    <div class="info-row"><div class="info-label"><i class="fas fa-clock"></i> <?= __('overtime_hours') ?>:</div><div class="info-value"><strong><?= number_format((float)$settlement['overtime_hours'], 2) ?></strong></div></div>
                    <?php endif; ?>
                    <?php if (!empty($settlement['other_earnings']) && $settlement['other_earnings'] > 0): ?>
                    <div class="info-row"><div class="info-label"><i class="fas fa-plus-circle"></i> <?= __('other_earnings') ?>:</div><div class="info-value"><strong style="color:#28a745;">+SAR <?= number_format((float)$settlement['other_earnings'], 2) ?></strong></div></div>
                    <?php endif; ?>
                    <?php if ((!empty($settlement['deduction_hours']) && $settlement['deduction_hours'] > 0) || (!empty($settlement['deduction_days']) && $settlement['deduction_days'] > 0) || (!empty($settlement['other_deductions']) && $settlement['other_deductions'] > 0)): ?>
                    <div class="info-row"><div class="info-label"><i class="fas fa-minus-circle"></i> <?= __('deductions') ?>:</div><div class="info-value"><strong style="color:#dc3545;">-SAR <?= number_format((float)$deduction_amount, 2) ?></strong></div></div>
                    <?php endif; ?>
                    <?php if (!empty($settlement['settlement_amount'])): ?>
                    <div class="info-row"><div class="info-label"><i class="fas fa-coins"></i> <?= __('settlement_amount') ?>:</div><div class="info-value"><strong style="color:#28a745;">SAR <?= number_format(round($payableAmount), 2) ?></strong></div></div>
                    <?php endif; ?>
                    <?php if (!empty($settlement['deductions'])): ?>
                    <div class="info-row"><div class="info-label"><i class="fas fa-minus-circle"></i> <?= __('deductions') ?>:</div><div class="info-value"><strong>SAR <?= number_format($settlement['deductions'], 2) ?></strong></div></div>
                    <?php endif; ?>
                    <?php if (!empty($settlement['final_amount'])): ?>
                    <div class="info-row"><div class="info-label"><i class="fas fa-check-double"></i> <?= __('final_amount') ?>:</div><div class="info-value"><strong style="color:#667eea;">SAR <?= number_format($settlement['final_amount'], 2) ?></strong></div></div>
                    <?php endif; ?>
                    <?php if (!empty($settlement['settlement_date'])): ?>
                    <div class="info-row"><div class="info-label"><i class="fas fa-calendar"></i> <?= __('settlement_date') ?>:</div><div class="info-value"><?= htmlspecialchars(date('d M Y', strtotime($settlement['settlement_date']))) ?></div></div>
                    <?php endif; ?>
                </div>

                <div class="info-card">
                    <h5><i class="fas fa-users-cog"></i> <?= __('approval_chain') ?></h5>
                    <table class="table table-sm chain-table table-hover">
                        <thead><tr><th width="70" class="text-center"><?= __('level') ?></th><th><?= __('approver') ?></th><th><?= __('status') ?></th></tr></thead>
                        <tbody>
                        <?php foreach ($chain as $approver): ?>
                            <tr>
                                <td class="text-center"><span class="level-badge"><?= $approver['approval_level'] ?></span></td>
                                <td><strong><?= getDisplayName($approver['approver_name']) ?></strong><br/><small class="text-muted"><?= htmlspecialchars($approver['user_type'] ?? '') ?></small></td>
                                <td>
                                    <?php if ($approver['status'] === 'approved'): ?>
                                        <span class="badge badge-success"><i class="fas fa-check"></i> <?= __('approved') ?></span>
                                    <?php elseif ($approver['status'] === 'rejected'): ?>
                                        <span class="badge badge-danger"><i class="fas fa-times"></i> <?= __('rejected') ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-warning"><i class="fas fa-hourglass-half"></i> <?= __('pending') ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($approver['action_date'])): ?>
                                        <br/><small class="text-muted"><?= htmlspecialchars(date('d M Y H:i', strtotime($approver['action_date']))) ?></small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="info-card">
            <h5><i class="fas fa-history"></i> <?= __('status_history_timeline') ?></h5>
            <?php if (empty($history)): ?>
                <div class="alert alert-info"><i class="fas fa-info-circle"></i> <?= __('no_history') ?></div>
            <?php else: ?>
                <div class="timeline">
                    <?php foreach ($history as $item): 
                        $status_clean = strtolower($item['status']);    
                        $item_class = 'pending'; $icon = 'fa-clock'; $badge = 'warning';
                        if (strpos($status_clean, 'approved') !== false || strpos($status_clean, 'completed') !== false) {
                            $item_class = 'approved'; $icon = 'fa-check'; $badge = 'success';
                        } elseif (strpos($status_clean, 'rejected') !== false) {
                            $item_class = 'rejected'; $icon = 'fa-times'; $badge = 'danger';
                        }
                    ?>
                    <div class="timeline-item <?= $item_class ?>">
                        <div class="timeline-marker" style="<?= ($is_rtl ?? false) ? 'right' : 'left' ?> : -11px !important;"><i class="fas <?= $icon ?>"></i></div>
                        <div class="timeline-content">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="status-badge bg-<?= $badge ?> text-white"><?= getDisplayName(ucwords(str_replace('_', ' ', $item['status']))) ?></span>
                                <small class="text-muted"><i class="far fa-clock"></i> <?= date('d M Y, H:i', strtotime($item['created_at'])) ?></small>
                            </div>
                            <p class="mb-1"><strong><?= nl2br(htmlspecialchars(getDisplayName(($item['note']) ?? 'No notes'))) ?></strong></p>
                            <small class="text-muted"><i class="far fa-user"></i> <?= getDisplayName($item['emp_name']) ?></small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="text-center mt-4">
                <a href="all_settlements.php" class="btn btn-back"><i class="fas fa-arrow-left"></i> <?= __('back_to_settlements') ?></a>
            </div>
        </div>
    </div>

    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/jquery.slimscroll.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>
</body>
</html>
<?php $conDB->close(); ?>
