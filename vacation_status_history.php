<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';
if (file_exists(__DIR__ . '/includes/functions.php')) {
    require_once __DIR__ . '/includes/functions.php';
}

// Restrict access: Employees cannot view this detailed report page
if (isset($isEmployee) && $isEmployee === true) {
    header("Location: ./profile.php");
    exit();
}

// Input
$request_inv_no = isset($_GET['request_inv_no']) ? trim($_GET['request_inv_no']) : '';
if ($request_inv_no === '') {
    die('<div style="padding:16px;margin:16px;border:1px solid #f5c2c7;background:#f8d7da;color:#842029;border-radius:8px;">ERROR: Vacation request invoice number not provided.</div>');
}

// Vacation details
$vacation = null;
$sql = "SELECT v.*, 
               e.name AS employee_name, e.avatar, e.dept, e.passport_number, e.passport_exp,
               d.dep_nme AS department_name,
               d.dep_nme_ar AS department_name_ar,
               b.remaining_balance, b.available_balance,
               (SELECT basic FROM emp_salary WHERE emp_id = v.emp_id AND status = 1 ORDER BY id DESC LIMIT 1) AS salary_basic,
               (SELECT housing FROM emp_salary WHERE emp_id = v.emp_id AND status = 1 ORDER BY id DESC LIMIT 1) AS salary_housing,
               (SELECT transport FROM emp_salary WHERE emp_id = v.emp_id AND status = 1 ORDER BY id DESC LIMIT 1) AS salary_transport,
               (SELECT food FROM emp_salary WHERE emp_id = v.emp_id AND status = 1 ORDER BY id DESC LIMIT 1) AS salary_food,
               (SELECT misc FROM emp_salary WHERE emp_id = v.emp_id AND status = 1 ORDER BY id DESC LIMIT 1) AS salary_misc,
               (SELECT cashier FROM emp_salary WHERE emp_id = v.emp_id AND status = 1 ORDER BY id DESC LIMIT 1) AS salary_cashier,
               (SELECT fuel FROM emp_salary WHERE emp_id = v.emp_id AND status = 1 ORDER BY id DESC LIMIT 1) AS salary_fuel,
               (SELECT tel FROM emp_salary WHERE emp_id = v.emp_id AND status = 1 ORDER BY id DESC LIMIT 1) AS salary_tel,
               (SELECT other FROM emp_salary WHERE emp_id = v.emp_id AND status = 1 ORDER BY id DESC LIMIT 1) AS salary_other,
               (SELECT guard FROM emp_salary WHERE emp_id = v.emp_id AND status = 1 ORDER BY id DESC LIMIT 1) AS salary_guard
        FROM emp_vacation v
        JOIN employees e ON v.emp_id = e.emp_id
        LEFT JOIN department d ON e.dept = d.id
        LEFT JOIN emp_vacation_balance b ON v.id = b.vac_id
        WHERE v.request_inv_no = ?
        LIMIT 1";
$stmt = $conDB->prepare($sql);
$stmt->bind_param('s', $request_inv_no);
$stmt->execute();
$res = $stmt->get_result();
if ($res && $res->num_rows === 1) {
    $vacation = $res->fetch_assoc();
}
$stmt->close();
if (!$vacation) {
    die('<div style="padding:16px;margin:16px;border:1px solid #f5c2c7;background:#f8d7da;color:#842029;border-radius:8px;">ERROR: Vacation request not found.</div>');
}

// Enforce department scoping: Only HR and System Admin can view other departments,
// otherwise allow if the user is part of the approval chain for this request
$canSeeAll = ($is_system_admin ?? false) || ($isHR ?? false);
if (!$canSeeAll) {
    $userDeptId = $user_dept ?? null;
    $sameDept = ($userDeptId !== null) ? ((int)$vacation['dept'] === (int)$userDeptId) : false;
    $inChain = false;
    if (!$sameDept) {
        // Check if current user appears in approval chain for this vacation
        if ($chk = $conDB->prepare("SELECT 1 FROM request_approvers ra JOIN approval_request_types t ON t.id = ra.request_type_id AND t.type_name = 'vacation_request' WHERE ra.request_inv_no = ? AND ra.approver_id = ? LIMIT 1")) {
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
        die('<div style="padding:16px;margin:16px;border:1px solid #f5c2c7;background:#f8d7da;color:#842029;border-radius:8px;">Access denied: You are not authorized to view this request.</div>');
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

// * If no history from smt_request_status, build from request_approvers + initial creation
/* if (empty($history)) {
    // Add initial "Submitted" status
    $history[] = [
        'status' => 'submitted',
        'note' => 'Vacation request submitted for approval',
        'emp_name' => $vacation['employee_name'],
        'created_at' => $vacation['created_at'] ?? date('Y-m-d H:i:s')
    ];
    
    // Get approval actions from request_approvers
    $typeStmt = $conDB->prepare("SELECT id FROM approval_request_types WHERE type_name = 'vacation_request' LIMIT 1");
    $typeStmt->execute();
    $typeRes = $typeStmt->get_result();
    $request_type_id = 1;
    if ($typeRow = $typeRes->fetch_assoc()) {
        $request_type_id = (int)$typeRow['id'];
    }
    $typeStmt->close();
    
    $approvalStmt = $conDB->prepare("
        SELECT ra.status, ra.action_date, ra.note,
               COALESCE(e.name, al.fullname, al.username) AS emp_name
        FROM request_approvers ra
        LEFT JOIN employees e ON ra.approver_id = e.emp_id
        LEFT JOIN admin_login al ON al.emp_id = ra.approver_id
        WHERE ra.request_inv_no = ? AND ra.request_type_id = ? AND ra.action_date IS NOT NULL
        ORDER BY ra.action_date ASC
    ");
    $approvalStmt->bind_param('si', $request_inv_no, $request_type_id);
    $approvalStmt->execute();
    $approvalRes = $approvalStmt->get_result();
    while ($row = $approvalRes->fetch_assoc()) {
        $history[] = [
            'status' => $row['status'] === 'approved' ? 'approved' : 'rejected',
            'note' => $row['note'] ?? ($row['status'] === 'approved' ? 'Approved' : 'Rejected'),
            'emp_name' => $row['emp_name'],
            'created_at' => $row['action_date']
        ];
    }
    $approvalStmt->close();
} */


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

// Resolve request_type_id for 'vacation_request' dynamically (fallback to 1)
$request_type_id = 1;
if ($typeStmt = $conDB->prepare("SELECT id FROM approval_request_types WHERE type_name = 'vacation_request' LIMIT 1")) {
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
if ($vacation['current_status'] === 'approved') { $status_class = 'success'; $status_icon = 'fa-check-circle'; }
elseif ($vacation['current_status'] === 'rejected') { $status_class = 'danger'; $status_icon = 'fa-times-circle'; }
elseif (strpos($vacation['current_status'], 'pending') !== false) { $status_class = 'warning'; $status_icon = 'fa-clock'; }
elseif (strpos($vacation['current_status'], 'completed') !== false) { $status_class = 'primary'; $status_icon = 'fa-check-circle'; }

$avatar_path = 'assets/images/users/avatar-1.jpg';
if (!empty($vacation['avatar'])) {
    $avatar_candidate = $vacation['avatar'];
    if (is_file(__DIR__ . '/' . $avatar_candidate)) {
        $avatar_path = $avatar_candidate;
    }
}

// Translate fly_type
$fly_type_display = '';
if ($vacation['fly_type'] === 'annual') {
    $fly_type_display = function_exists('__') ? __('annual_vacation') : 'Annual Vacation';
} elseif ($vacation['fly_type'] === 'emergency') {
    $fly_type_display = function_exists('__') ? __('emergency_vacation') : 'Emergency Vacation';
}
?>
<!doctype html>
<html lang="<?= isset($current_lang) ? $current_lang : 'en' ?>" <?= (isset($is_rtl) && $is_rtl) ? 'dir="rtl"' : '' ?>>
<head>
    <meta charset="utf-8" />
    <title><?= isset($site_title) ? $site_title : 'Al-Mutlak' ?> - Vacation Approval History</title>
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
            position: absolute; left: -11px; top: 15px; width: 24px; height: 24px; 
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
            <h3><i class="fas fa-history"></i> <?= __('vacation_approval_history') ?></h3>
            <p class="mb-0"><?= __('complete_approval_timeline') ?></p>
        </div>
    </div>

    <div class="container" style="margin-bottom:30px;">
        <!-- Employee Info -->
        <div class="info-card">
            <div class="employee-header">
                <img src="<?= htmlspecialchars($avatar_path) ?>" class="employee-avatar" alt="avatar" />
                <div>
                    <h4 style="margin:0;"><?= getDisplayName($vacation['employee_name'])?></h4>
                    <div class="text-muted" style="margin-top:6px;">
                        <strong><?= __('emp_id') ?>:</strong> <?= htmlspecialchars($vacation['emp_id']) ?>
                        &nbsp; | &nbsp;
                        <strong><?= __('department') ?>:</strong> <?= ($is_rtl ?? false ? $vacation['department_name_ar'] ?? 'N/A' : $vacation['department_name'] ?? 'N/A') ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="info-card">
                    <h5><i class="fas fa-suitcase-rolling"></i> <?= __('vacation_details')?></h5>
                    <div class="info-row"><div class="info-label"><i class="fas fa-barcode"></i> <?= __('invoice_no') ?>:</div><div class="info-value"><code><?= htmlspecialchars($vacation['request_inv_no']) ?></code></div></div>
                    <div class="info-row"><div class="info-label"><i class="fas fa-tag"></i> <?= __('type')?>:</div><div class="info-value"><strong><?= getDisplayName($vacation['vac_type']) ?></strong></div></div>
                    <?php if (!empty($fly_type_display)): ?>
                    <div class="info-row"><div class="info-label"><i class="fas fa-plane"></i> <?= __('fly_type') ?>:</div><div class="info-value"><strong><?= htmlspecialchars($fly_type_display) ?></strong></div></div>
                    <?php endif; ?>
                    <div class="info-row"><div class="info-label"><i class="fas fa-calendar-alt"></i> <?= __('start_date') ?>:</div><div class="info-value"><?= htmlspecialchars($vacation['start_date'] ?? 'N/A') ?></div></div>
                    <div class="info-row"><div class="info-label"><i class="fas fa-calendar-check"></i> <?= __('return_date') ?>:</div><div class="info-value"><?= htmlspecialchars($vacation['return_date'] ?? 'N/A') ?></div></div>
                    <?php if (!empty($vacation['departure_date']) && $vacation['vac_type'] === 'Fly' && $vacation['fly_type'] === 'annual'): ?>
                    <div class="info-row"><div class="info-label"><i class="fas fa-plane-departure"></i> <?= __('departure_date') ?>:</div><div class="info-value"><?= htmlspecialchars($vacation['departure_date']) ?></div></div>
                    <?php endif; ?>
                    <?php if (!empty($vacation['arrival_date']) && $vacation['vac_type'] === 'Fly' && $vacation['fly_type'] === 'annual'): ?>
                    <div class="info-row"><div class="info-label"><i class="fas fa-plane-arrival"></i> <?= __('arrival_date') ?>:</div><div class="info-value"><?= htmlspecialchars($vacation['arrival_date']) ?></div></div>
                    <?php endif; ?>
                    <div class="info-row"><div class="info-label"><i class="fas fa-sun"></i> <?= __('total_days') ?>:</div><div class="info-value"><strong><?= (int)$vacation['vacdays'] ?></strong> <?= __('days') ?></div></div>
                    <?php if (!empty($vacation['replacement_person'])): ?>
                    <div class="info-row"><div class="info-label"><i class="fas fa-user-friends"></i> <?= __('replacement') ?>:</div><div class="info-value"><?= getDisplayName($vacation['replacement_person']) ?></div></div>
                    <?php endif; ?>
                    <div class="info-row"><div class="info-label"><i class="fas fa-info-circle"></i> <?= __('status') ?>:</div><div class="info-value"><span class="badge badge-<?= $status_class ?>" style="font-size:14px;padding:8px 16px;"><i class="fas <?= $status_icon ?>"></i> <?= getDisplayName(ucwords(str_replace('_', ' ', $vacation['current_status']))) ?></span></div></div>
                    <?php if (!empty($vacation['remarks'])): ?>
                    <div class="info-row"><div class="info-label"><i class="fas fa-comment"></i> <?= __('remarks') ?>:</div><div class="info-value"><?= (nl2br(htmlspecialchars(getDisplayName($vacation['remarks'])))) ?></div></div>
                    <?php endif; ?>
                    <?php if (!empty($vacation['attachment_path'])): 
                        // Decode JSON array of attachments
                        $attachments = json_decode($vacation['attachment_path'], true);
                        if (!is_array($attachments)) {
                            // Fallback for old single file format
                            $attachments = [$vacation['attachment_path']];
                        }
                    ?>
                    <div class="info-row">
                        <div class="info-label"><i class="fas fa-paperclip"></i> <?= __('attachments') ?> (<?= count($attachments) ?>):</div>
                        <div class="info-value">
                            <?php foreach ($attachments as $index => $attachment): ?>
                                <a href="<?= htmlspecialchars($attachment) ?>" target="_blank" class="attachment-link" style="margin-bottom: 8px; display: inline-flex;">
                                    <i class="fas fa-file-<?= pathinfo($attachment, PATHINFO_EXTENSION) === 'pdf' ? 'pdf' : 'image' ?>"></i> 
                                    <?= __('document') ?> <?= $index + 1 ?>
                                </a>
                                <?php if ($index < count($attachments) - 1): ?>&nbsp;&nbsp;<?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-md-6">
                <?php if ($vacation['current_status'] == 'approved' && (isset($vacation['remaining_balance']) || isset($vacation['available_balance']))): ?>
                <div class="info-card mb-3">
                    <h5><i class="fas fa-wallet"></i> <?= __('vacation_balance') ?></h5>
                    <?php if (isset($vacation['remaining_balance'])): ?>
                    <div class="info-row"><div class="info-label"><i class="fas fa-hourglass-half"></i> <?= __('remaining') ?>:</div><div class="info-value"><strong style="color:#28a745;"><?= number_format($vacation['remaining_balance'], 2) ?></strong> <?= __('days') ?></div></div>
                    <?php endif; ?>
                    <?php if (isset($vacation['available_balance'])): ?>
                    <div class="info-row"><div class="info-label"><i class="fas fa-check-double"></i> <?= __('available') ?>:</div><div class="info-value"><strong style="color:#667eea;"><?= number_format($vacation['available_balance'], 2) ?></strong> <?= __('days') ?></div></div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php 
                // Show payment information for Fly vacations OR if any payment-related fields have values
                $has_payment_info = ($vacation['vac_type'] == 'Fly') || 
                                   !empty($vacation['ticket_pay']) || 
                                   !empty($vacation['permit_fee']) ||
                                   !empty($vacation['overtime_hours']) ||
                                   !empty($vacation['deduction_hours']) ||
                                   !empty($vacation['deduction_days']) ||
                                   !empty($vacation['other_deductions']) ||
                                   !empty($vacation['other_earnings']);
                
                if ($has_payment_info): 
                ?>
                <div class="info-card mb-3">
                    <h5><i class="fas fa-money-bill-wave"></i> <?= __('payment_information') ?></h5>
                    
                    <?php 
                    // Calculate salary-based amounts
                    $working_days_salary = 0;
                    $vacation_salary = 0;
                    $is_fly_annual = ($vacation['vac_type'] === 'Fly' && $vacation['fly_type'] === 'annual');
                    $other_earnings = (float)($vacation['other_earnings'] ?? 0);
                    
                    $days_in_month = 30; // Fixed 30 days for all calculations
                    
                    // Calculate working days and vacation salary if salary_basic is available
                    if (!empty($vacation['salary_basic'])) {
                        $basic_salary = (float)$vacation['salary_basic'];
                        $total_monthly_salary = $basic_salary + 
                            (float)($vacation['salary_housing'] ?? 0) + 
                            (float)($vacation['salary_transport'] ?? 0) + 
                            (float)($vacation['salary_food'] ?? 0) + 
                            (float)($vacation['salary_misc'] ?? 0) + 
                            (float)($vacation['salary_cashier'] ?? 0) + 
                            (float)($vacation['salary_fuel'] ?? 0) + 
                            (float)($vacation['salary_tel'] ?? 0) + 
                            (float)($vacation['salary_other'] ?? 0) + 
                            (float)($vacation['salary_guard'] ?? 0);
                        
                        $daily_rate = $total_monthly_salary / $days_in_month;
                        
                        // Working days salary (days before vacation starts) - Only for Fly Annual
                        if ($is_fly_annual && !empty($vacation['start_date'])) {
                            $start_date_obj = new DateTime($vacation['start_date']);
                            $working_days = (int)$start_date_obj->format('d') - 1;
                            if ($working_days > 0) {
                                $working_days_salary = $daily_rate * $working_days;
                            }
                        }
                        
                        // Vacation salary (approved days) - Only for Fly Annual with vacation_salary_type = payroll
                        if ($is_fly_annual) {
                            $vacation_salary_type = $vacation['vacation_salary_type'] ?? 'payroll';
                            if ($vacation_salary_type === 'payroll') {
                                $approved_days = (float)($vacation['vacdays'] ?? 0);
                                if ($approved_days > 0) {
                                    $vacation_salary = $daily_rate * $approved_days;
                                }
                            }
                        }
                    }
                    
                    $overtime_amount = 0;
                    $deduction_amount = 0;
                    
                    if (!empty($vacation['salary_basic'])) {
                        $basic_salary = (float)$vacation['salary_basic'];
                        $total_monthly_salary = $basic_salary + 
                            (float)($vacation['salary_housing'] ?? 0) + 
                            (float)($vacation['salary_transport'] ?? 0) + 
                            (float)($vacation['salary_food'] ?? 0) + 
                            (float)($vacation['salary_misc'] ?? 0) + 
                            (float)($vacation['salary_cashier'] ?? 0) + 
                            (float)($vacation['salary_fuel'] ?? 0) + 
                            (float)($vacation['salary_tel'] ?? 0) + 
                            (float)($vacation['salary_other'] ?? 0) + 
                            (float)($vacation['salary_guard'] ?? 0);
                        
                        // OVERTIME CALCULATION (per EOS file): per-hour overtime rate = (basic/240)/2 + (full/240)
                        $overtimeHourlyRate = (($basic_salary / 240) / 2) + ($total_monthly_salary / 240);
                        
                        // DEDUCTION CALCULATION (EOS Logic)
                        $DEDUCTION_BASE = $total_monthly_salary;
                        $dailyRateDeduction = $DEDUCTION_BASE / $days_in_month;
                        $hourlyRateDeduction = $dailyRateDeduction / 8;
                        
                        if (!empty($vacation['overtime_hours'])) {
                            $overtime_amount = (float)$vacation['overtime_hours'] * $overtimeHourlyRate;
                        }
                        
                        if (!empty($vacation['deduction_hours']) || !empty($vacation['deduction_days']) || !empty($vacation['other_deductions'])) {
                            $deduction_hours_amount = (float)($vacation['deduction_hours'] ?? 0) * $hourlyRateDeduction;
                            $deduction_days_amount = (float)($vacation['deduction_days'] ?? 0) * $dailyRateDeduction;
                            $deduction_amount = $deduction_hours_amount + $deduction_days_amount + (float)($vacation['other_deductions'] ?? 0);
                        }
                    }
                    
                    // Debug information (remove after testing)
                    echo "<!-- DEBUG: vac_type={$vacation['vac_type']}, fly_type={$vacation['fly_type']}, ";
                    echo "salary_basic={$vacation['salary_basic']}, total_monthly_salary=$total_monthly_salary, ";
                    echo "vacation_salary_type={$vacation['vacation_salary_type']}, ";
                    echo "start_date={$vacation['start_date']}, vacdays={$vacation['vacdays']}, ";
                    echo "other_earnings={$vacation['other_earnings']}, ";
                    echo "working_days_salary=$working_days_salary, vacation_salary=$vacation_salary -->";
                    ?>
                    
                    <!-- Ticket and Permit Fees -->
                    <?php if ($vacation['ticket_pay'] > 0): ?>
                    <div class="info-row"><div class="info-label"><i class="fas fa-plane"></i> <?= __('ticket_payment') ?>:</div><div class="info-value"><strong>SAR <?= number_format($vacation['ticket_pay'], 2) ?></strong></div></div>
                    <?php endif; ?>
                    <?php if ($vacation['permit_fee'] > 0): ?>
                    <div class="info-row"><div class="info-label"><i class="fas fa-passport"></i> <?= __('permit_fee') ?>:</div><div class="info-value"><strong>SAR <?= number_format($vacation['permit_fee'], 2) ?></strong></div></div>
                    <?php endif; ?>
                    
                    <!-- Salary Components -->
                    <?php if ($working_days_salary > 0): ?>
                    <div class="info-row"><div class="info-label"><i class="fas fa-calendar-days"></i> <?= __('working_days_salary') ?>:</div><div class="info-value"><strong>SAR <?= number_format($working_days_salary, 2) ?></strong></div></div>
                    <?php endif; ?>
                    <?php if ($vacation_salary > 0): ?>
                    <div class="info-row"><div class="info-label"><i class="fas fa-sun"></i> <?= __('vacation_salary') ?>:</div><div class="info-value"><strong>SAR <?= number_format($vacation_salary, 2) ?></strong></div></div>
                    <?php endif; ?>
                    <?php if ($other_earnings > 0): ?>
                    <div class="info-row"><div class="info-label"><i class="fas fa-coins"></i> <?= __('other_earnings') ?>:</div><div class="info-value"><strong>SAR <?= number_format($other_earnings, 2) ?></strong></div></div>
                    <?php endif; ?>
                    
                    <!-- Payroll Adjustments -->
                    <?php if (!empty($vacation['overtime_hours'])): ?>
                    <div class="info-row">
                        <div class="info-label"><i class="fas fa-clock"></i> <?= __('overtime_payment') ?>:</div>
                        <div class="info-value">
                            <strong>SAR <?= number_format($overtime_amount, 2) ?></strong>
                            <small class="text-muted d-block"><?= number_format((float)$vacation['overtime_hours'], 2) ?> hrs</small>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($vacation['deduction_hours']) || !empty($vacation['deduction_days']) || !empty($vacation['other_deductions'])): ?>
                    <div class="info-row">
                        <div class="info-label"><i class="fas fa-minus-circle"></i> <?= __('deductions') ?>:</div>
                        <div class="info-value">
                            <div style="margin-bottom: 5px;">-SAR <?= number_format($deduction_amount, 2) ?></div>
                            <?php if (!empty($vacation['deduction_hours'])): ?>
                            <small class="text-muted d-block"><i class="fas fa-hourglass-half"></i> Hours: <?= number_format((float)$vacation['deduction_hours'], 2) ?></small>
                            <?php endif; ?>
                            <?php if (!empty($vacation['deduction_days'])): ?>
                            <small class="text-muted d-block"><i class="fas fa-calendar-alt"></i> Days: <?= number_format((float)$vacation['deduction_days'], 2) ?></small>
                            <?php endif; ?>
                            <?php if (!empty($vacation['other_deductions'])): ?>
                            <small class="text-muted d-block"><i class="fas fa-list"></i> Other: SAR <?= number_format((float)$vacation['other_deductions'], 2) ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($vacation['payroll_note'])): ?>
                    <div class="info-row">
                        <div class="info-label"><i class="fas fa-sticky-note"></i> <?= __('note') ?>:</div>
                        <div class="info-value"><small><?= nl2br(htmlspecialchars(getDisplayName($vacation['payroll_note']))) ?></small></div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <div class="info-card">
                    <h5><i class="fas fa-users-cog"></i> <?= __('approval_chain') ?></h5>
                    <table class="table table-sm chain-table table-hover">
                        <thead><tr><th width="70" class="text-center"><?= __('level') ?></th><th><?= __('approver') ?></th><th><?= __('status') ?></th></tr></thead>
                        <tbody>
                        <?php if (empty($chain)): ?>
                            <tr><td colspan="3" class="text-center text-muted"><i class="fas fa-info-circle"></i> <?= __('no_approval_chain') ?></td></tr>
                        <?php else: 
                            // Check if request was rejected
                            $is_vacation_rejected = isset($vacation['current_status']) && $vacation['current_status'] === 'rejected';
                            
                            foreach ($chain as $link): 
                                // If request is rejected, skip pending/awaiting approvers
                                if ($is_vacation_rejected && in_array($link['status'], ['pending', 'awaiting'])) {
                                    continue;
                                }
                                
                                $cClass = 'secondary'; $cIcon = 'fa-circle';
                                if ($link['status']==='approved') { $cClass='success'; $cIcon='fa-check-circle'; }
                                elseif ($link['status']==='rejected') { $cClass='danger'; $cIcon='fa-times-circle'; }
                                elseif ($link['status']==='pending') { $cClass='warning'; $cIcon='fa-clock'; }
                        ?>
                            <tr>
                                <td class="text-center"><span class="level-badge"><?= (int)$link['approval_level'] ?></span></td>
                                <td><strong><?= getDisplayName(parseName($link['approver_name']) ?? 'N/A') ?></strong><br><small class="text-muted"><i class="fas fa-user-tag"></i> <?= getDisplayName(getRoleLabel($link['user_type'])) ?></small></td>
                                <td>
                                    <span class="badge badge-<?= $cClass ?>"><i class="fas <?= $cIcon ?>"></i> <?= getDisplayName(ucfirst($link['status'])) ?></span>
                                    <?php if (!empty($link['action_date'])): ?><br><small class="text-muted"><i class="far fa-calendar-alt"></i> <?= date('d M Y, H:i', strtotime($link['action_date'])) ?></small><?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
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
                <a href="all_applied_vac.php" class="btn btn-back"><i class="fas fa-arrow-left"></i> <?= __('back_to_vacations') ?></a>
                <a href="vacation_report_details.php?id=<?= (int)$vacation['id'] ?>&emp_id=<?= urlencode($vacation['emp_id']) ?>" target="_blank" class="btn btn-back ml-2"><i class="fas fa-file-pdf"></i> <?= __('view_report') ?></a>
            </div>
        </div>
    </div>

    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conDB->close(); ?>
