<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/helper_functions.php';
require_once __DIR__ . '/includes/payroll_approval_helpers.php';
require_once __DIR__ . '/includes/special_access_helper.php';

if (file_exists(__DIR__ . '/includes/functions.php')) {
    require_once __DIR__ . '/includes/functions.php';
}

$current_lang = $_SESSION['lang'] ?? 'en';
if (function_exists('load_language')) {
    load_language($current_lang);
}

// Restrict access: Employees cannot view this page, unless explicitly
// granted via app_settings -> Special Access.
if (
    isset($isEmployee) && $isEmployee === true
    && !user_has_special_access($conDB, $empid ?? '', 'access_payroll_status_history', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)
) {
    header('Location: ./profile.php');
    exit();
}

$requestInvNo = trim((string)($_GET['request_inv_no'] ?? $_GET['inv_no'] ?? ''));
if ($requestInvNo === '') {
    die('<div style="padding:16px;margin:16px;border:1px solid #f5c2c7;background:#f8d7da;color:#842029;border-radius:8px;">ERROR: Payroll request invoice number not provided.</div>');
}

$pdo = getDbConnection();
ensurePayrollApprovalTable($pdo);
$requestTypeId = ensurePayrollApprovalRequestType($pdo);

$payrollRequest = null;
$requestSql = "SELECT
        pr.*,
        req_emp.name AS requester_name,
        req_emp.dept AS requester_dept,
        req_emp.avatar AS requester_avatar,
        req_emp.sex AS requester_sex,
        req_al.user_type AS requester_user_type,
        d.dep_nme AS department_name,
        d.dep_nme_ar AS department_name_ar,
        approved_emp.name AS approved_by_name,
        approved_al.user_type AS approved_by_user_type,
        processed_emp.name AS processed_by_name,
        processed_al.user_type AS processed_by_user_type,
        payroll_summary.employee_count,
        payroll_summary.total_net_salary,
        payroll_summary.generated_count,
        payroll_summary.paid_count
    FROM payroll_approval_requests pr
    LEFT JOIN employees req_emp ON req_emp.emp_id = pr.requested_by
    LEFT JOIN admin_login req_al ON req_al.emp_id = pr.requested_by
    LEFT JOIN department d ON d.id = req_emp.dept
    LEFT JOIN employees approved_emp ON approved_emp.emp_id = pr.approved_by
    LEFT JOIN admin_login approved_al ON approved_al.emp_id = pr.approved_by
    LEFT JOIN employees processed_emp ON processed_emp.emp_id = pr.processed_by
    LEFT JOIN admin_login processed_al ON processed_al.emp_id = pr.processed_by
    LEFT JOIN (
        SELECT
            p.month_year,
            COUNT(CASE WHEN COALESCE(e.payment_type, 1) <> 3 THEN p.emp_id END) AS employee_count,
            SUM(CASE WHEN COALESCE(e.payment_type, 1) <> 3 THEN p.net_salary ELSE 0 END) AS total_net_salary,
            SUM(CASE WHEN p.status = 'generated' THEN 1 ELSE 0 END) AS generated_count,
            SUM(CASE WHEN p.status = 'paid' THEN 1 ELSE 0 END) AS paid_count
        FROM payrolls p
        INNER JOIN employees e ON e.emp_id = p.emp_id
        GROUP BY p.month_year
    ) payroll_summary ON payroll_summary.month_year = pr.payroll_month
    WHERE pr.request_inv_no = ?
    LIMIT 1";

$stmt = $conDB->prepare($requestSql);
if (!$stmt) {
    die('<div style="padding:16px;margin:16px;border:1px solid #f5c2c7;background:#f8d7da;color:#842029;border-radius:8px;">ERROR: Unable to prepare payroll history query.</div>');
}

$stmt->bind_param('s', $requestInvNo);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows === 1) {
    $payrollRequest = $result->fetch_assoc();
}
$stmt->close();

if (!$payrollRequest) {
    die('<div style="padding:16px;margin:16px;border:1px solid #f5c2c7;background:#f8d7da;color:#842029;border-radius:8px;">ERROR: Payroll approval request not found.</div>');
}

$isFinanceRole = isset($user_type) && stripos((string)$user_type, 'finance') !== false;
$canSeeAll = ($is_system_admin ?? false) || ($isHR ?? false) || $isFinanceRole;
if (!$canSeeAll) {
    $userDeptId = $user_dept ?? null;
    $sameDept = ($userDeptId !== null && $payrollRequest['requester_dept'] !== null)
        ? ((int)$payrollRequest['requester_dept'] === (int)$userDeptId)
        : false;
    $inChain = false;

    if (!$sameDept) {
        $chainAccessStmt = $conDB->prepare('SELECT 1 FROM request_approvers WHERE request_inv_no = ? AND request_type_id = ? AND approver_id = ? LIMIT 1');
        if ($chainAccessStmt) {
            $currentEmpId = (string)($empid ?? $_SESSION['empid'] ?? '');
            $chainAccessStmt->bind_param('sis', $requestInvNo, $requestTypeId, $currentEmpId);
            if ($chainAccessStmt->execute()) {
                $chainAccessResult = $chainAccessStmt->get_result();
                $inChain = $chainAccessResult && $chainAccessResult->num_rows > 0;
            }
            $chainAccessStmt->close();
        }
    }

    if (!$sameDept && !$inChain) {
        http_response_code(403);
        die('<div style="padding:16px;margin:16px;border:1px solid #f5c2c7;background:#f8d7da;color:#842029;border-radius:8px;">Access denied: You are not authorized to view this payroll request.</div>');
    }
}

// Any account whose role is hr_payroll shows just "HR Payroll" instead of their name.
function payrollStatusHistoryIsHrPayrollRole($userType): bool
{
    return strtolower(trim((string)$userType)) === 'hr_payroll';
}
function payrollStatusHistoryDisplayName($name, $userType)
{
    return payrollStatusHistoryIsHrPayrollRole($userType) ? __('hr_payroll', 'HR Payroll') : getDisplayName($name);
}

$history = [];
$historyStmt = $conDB->prepare('SELECT s.status, s.note, s.emp_name, s.created_at, al.user_type
    FROM smt_request_status s
    LEFT JOIN admin_login al ON al.emp_id = s.emp_id
    WHERE s.inv_no = ? ORDER BY s.created_at ASC');
if ($historyStmt) {
    $historyStmt->bind_param('s', $requestInvNo);
    $historyStmt->execute();
    $historyResult = $historyStmt->get_result();
    while ($row = $historyResult->fetch_assoc()) {
        $history[] = $row;
    }
    $historyStmt->close();
}

$chain = [];
// Some requests have duplicate request_approvers rows per (level, approver) from a past
// double-insert bug - collapse to the latest row per (level, approver) so each approver
// shows once instead of twice.
$chainStmt = $conDB->prepare("SELECT
        ra.approval_level,
        ra.status,
        ra.action_date,
        ra.note,
        COALESCE(e.name, al.fullname, al.username) AS approver_name,
        COALESCE(al.user_type, '') AS user_type
    FROM request_approvers ra
    INNER JOIN (
        SELECT approval_level, approver_id, MAX(id) AS max_id
        FROM request_approvers
        WHERE request_inv_no = ? AND request_type_id = ?
        GROUP BY approval_level, approver_id
    ) latest ON latest.max_id = ra.id
    LEFT JOIN employees e ON e.emp_id = ra.approver_id
    LEFT JOIN admin_login al ON al.emp_id = ra.approver_id
    WHERE ra.request_inv_no = ? AND ra.request_type_id = ?
    ORDER BY ra.approval_level ASC");
if ($chainStmt) {
    $chainStmt->bind_param('sisi', $requestInvNo, $requestTypeId, $requestInvNo, $requestTypeId);
    $chainStmt->execute();
    $chainResult = $chainStmt->get_result();
    while ($row = $chainResult->fetch_assoc()) {
        $chain[] = $row;
    }
    $chainStmt->close();
}

$financeVerification = [];
$financeVerificationStmt = $conDB->prepare("SELECT
        v.finance_manager_emp_id,
        v.finance_officer_emp_id,
        v.selected_company_ids,
        v.is_confirmed,
        v.confirmed_at,
        v.officer_approved,
        v.officer_approved_at,
        COALESCE(mgr.name, mgr_al.fullname, mgr_al.username) AS manager_name,
        COALESCE(officer.name, officer_al.fullname, officer_al.username) AS officer_name
    FROM payroll_finance_verification v
    LEFT JOIN employees mgr ON mgr.emp_id = v.finance_manager_emp_id
    LEFT JOIN admin_login mgr_al ON mgr_al.emp_id = v.finance_manager_emp_id
    LEFT JOIN employees officer ON officer.emp_id = v.finance_officer_emp_id
    LEFT JOIN admin_login officer_al ON officer_al.emp_id = v.finance_officer_emp_id
    WHERE v.request_inv_no = ? AND v.payroll_month = ?
    ORDER BY v.finance_manager_emp_id ASC, v.id ASC");
if ($financeVerificationStmt) {
    $financeVerificationMonth = (string)($payrollRequest['payroll_month'] ?? '');
    $financeVerificationStmt->bind_param('ss', $requestInvNo, $financeVerificationMonth);
    $financeVerificationStmt->execute();
    $financeVerificationResult = $financeVerificationStmt->get_result();

    $allCompanyNamesById = [];
    $companyLookupResult = mysqli_query($conDB, "SELECT comp_id, comp_name FROM companies");
    if ($companyLookupResult) {
        while ($companyRow = mysqli_fetch_assoc($companyLookupResult)) {
            $allCompanyNamesById[(string)$companyRow['comp_id']] = (string)$companyRow['comp_name'];
        }
    }

    while ($row = $financeVerificationResult->fetch_assoc()) {
        $companyIds = json_decode((string)($row['selected_company_ids'] ?? '[]'), true);
        if (!is_array($companyIds)) {
            $companyIds = [];
        }
        $row['company_names'] = array_map(static function ($companyId) use ($allCompanyNamesById) {
            $companyId = trim((string)$companyId);
            return $allCompanyNamesById[$companyId] ?? $companyId;
        }, $companyIds);
        $financeVerification[] = $row;
    }
    $financeVerificationStmt->close();
}

if (empty($history)) {
    $history[] = [
        'status' => 'pending_approval',
        'note' => 'Payroll approval started for month ' . ($payrollRequest['payroll_month'] ?? ''),
        'emp_name' => $payrollRequest['requester_name'] ?: 'System',
        'user_type' => $payrollRequest['requester_user_type'] ?? '',
        'created_at' => $payrollRequest['created_at']
    ];
}

$historyStatuses = array_map(static function ($item) {
    return strtolower((string)($item['status'] ?? ''));
}, $history);

if (($payrollRequest['status'] ?? '') === 'completed' && !empty($payrollRequest['processed_at']) && !in_array('completed', $historyStatuses, true)) {
    $history[] = [
        'status' => 'completed',
        'note' => 'Payroll processing completed for month ' . ($payrollRequest['payroll_month'] ?? ''),
        'emp_name' => $payrollRequest['processed_by_name'] ?: 'System',
        'user_type' => $payrollRequest['processed_by_user_type'] ?? '',
        'created_at' => $payrollRequest['processed_at']
    ];
}

usort($history, static function ($left, $right) {
    return strtotime((string)($left['created_at'] ?? '')) <=> strtotime((string)($right['created_at'] ?? ''));
});

$status = (string)($payrollRequest['status'] ?? 'pending_approval');
$statusClass = 'secondary';
$statusIcon = 'fa-circle';
if ($status === 'approved') {
    $statusClass = 'success';
    $statusIcon = 'fa-check-circle';
} elseif ($status === 'rejected') {
    $statusClass = 'danger';
    $statusIcon = 'fa-times-circle';
} elseif ($status === 'completed') {
    $statusClass = 'primary';
    $statusIcon = 'fa-badge-check';
} elseif (strpos($status, 'pending') !== false) {
    $statusClass = 'warning';
    $statusIcon = 'fa-clock';
}

$avatarPath = getAvatarImagePath($payrollRequest['requester_avatar'] ?? '', $payrollRequest['requester_sex'] ?? 1);
$departmentName = ($is_rtl ?? false)
    ? ($payrollRequest['department_name_ar'] ?? $payrollRequest['department_name'] ?? __('not_available', 'N/A'))
    : ($payrollRequest['department_name'] ?? $payrollRequest['department_name_ar'] ?? __('not_available', 'N/A'));
?>
<!doctype html>
<html lang="<?= htmlspecialchars($current_lang) ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>
<head>
    <meta charset="utf-8" />
    <title><?= htmlspecialchars($site_title ?? 'Al-Mutlak') ?> - <?= __('payroll_approval_history', 'Payroll Approval History') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="<?= function_exists('get_setting') ? htmlspecialchars((string)(get_setting($conDB, 'favicon') ?? 'assets/images/favicon.ico')) : 'assets/images/favicon.ico' ?>">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f6f8fb; }
        .page-header {
            background: linear-gradient(135deg, #1f6aa5 0%, #0b8f7a 100%);
            color: #fff;
            padding: 30px 0;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            text-align: <?= ($is_rtl ?? false) ? 'right' : 'left' ?>;
        }
        .page-header h3 { margin: 0; font-weight: 600; }
        .info-card {
            background: #fff;
            border-radius: 14px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(19, 44, 72, 0.06);
            text-align: <?= ($is_rtl ?? false) ? 'right' : 'left' ?>;
        }
        .info-card h5 {
            color: #1f6aa5;
            margin-bottom: 18px;
            font-weight: 600;
            border-bottom: 2px solid #eef3f8;
            padding-bottom: 10px;
        }
        .request-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }
        .requester-summary {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .requester-avatar {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #d8e9f7;
        }
        .stats-grid,
        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 14px;
        }
        .stat-box,
        .detail-box {
            background: #f8fbfe;
            border: 1px solid #e6eef5;
            border-radius: 12px;
            padding: 14px 16px;
        }
        .detail-label,
        .stat-label {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #73839b;
            margin-bottom: 6px;
        }
        .detail-value,
        .stat-value {
            font-size: 18px;
            font-weight: 600;
            color: #24364b;
        }
        .chain-table td,
        .chain-table th {
            vertical-align: top;
        }
        .level-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #eaf4fb;
            color: #1f6aa5;
            font-weight: 700;
        }
        .timeline { position: relative; padding: 6px 0; }
        .timeline:before {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            left: 18px;
            width: 2px;
            background: #dbe6f0;
        }
        .timeline-item {
            position: relative;
            padding-left: 54px;
            margin-bottom: 18px;
        }
        .timeline-marker {
            position: absolute;
            top: 14px;
            left: 7px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #fff;
            border: 2px solid #aebfd0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: #7f8fa4;
            z-index: 1;
        }
        .timeline-item.approved .timeline-marker { border-color: #28a745; color: #28a745; background: #f3fff7; }
        .timeline-item.rejected .timeline-marker { border-color: #dc3545; color: #dc3545; background: #fff5f5; }
        .timeline-item.pending .timeline-marker { border-color: #ffc107; color: #d39b00; background: #fffdf1; }
        .timeline-item.completed .timeline-marker { border-color: #007bff; color: #007bff; background: #f2f8ff; }
        .timeline-content {
            background: #fff;
            border: 1px solid #e5edf5;
            border-radius: 12px;
            padding: 16px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
        }
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .badge-soft-success { background: #eaf8ee; color: #1f8b3c; }
        .badge-soft-danger { background: #fdecec; color: #c43636; }
        .badge-soft-warning { background: #fff6dc; color: #b27d00; }
        .badge-soft-primary { background: #e8f1ff; color: #1c63d5; }
        .badge-soft-secondary { background: #edf1f5; color: #6c7c90; }
        .btn-back {
            background: linear-gradient(135deg, #1f6aa5 0%, #0b8f7a 100%);
            border: none;
            color: #fff;
            padding: 12px 28px;
            border-radius: 999px;
            font-weight: 600;
        }
        .btn-back:hover { color: #fff; opacity: 0.95; }
        [dir="rtl"] .timeline:before {
            left: auto;
            right: 18px;
        }
        [dir="rtl"] .timeline-item {
            padding-left: 0;
            padding-right: 54px;
        }
        [dir="rtl"] .timeline-marker {
            left: auto;
            right: 7px;
        }
        @media (max-width: 767px) {
            .requester-summary { align-items: flex-start; }
            .detail-value,
            .stat-value { font-size: 16px; }
        }
    </style>
</head>
<body>
    <div class="page-header">
        <div class="container">
            <h3><i class="fas fa-history"></i> <?= __('payroll_approval_history', 'Payroll Approval History') ?></h3>
            <p class="mb-0"><?= __('complete_approval_timeline', 'Complete approval timeline and approver list') ?></p>
        </div>
    </div>

    <div class="container" style="margin-bottom:30px;">
        <div class="info-card">
            <div class="request-header">
                <div class="requester-summary">
                    <img src="<?= htmlspecialchars($avatarPath) ?>" class="requester-avatar" alt="requester avatar">
                    <div>
                        <h4 style="margin:0;"><?= htmlspecialchars(payrollStatusHistoryDisplayName($payrollRequest['requester_name'] ?: __('system', 'System'), $payrollRequest['requester_user_type'] ?? '')) ?></h4>
                        <div class="text-muted" style="margin-top:6px;">
                            <strong><?= __('request_id', 'Request ID') ?>:</strong> <?= htmlspecialchars($payrollRequest['request_inv_no']) ?>
                            &nbsp; | &nbsp;
                            <strong><?= __('department') ?>:</strong> <?= htmlspecialchars(getDisplayName($departmentName)) ?>
                        </div>
                    </div>
                </div>
                <div>
                    <span class="badge badge-<?= htmlspecialchars($statusClass) ?> p-2"><i class="fas <?= htmlspecialchars($statusIcon) ?>"></i> <?= htmlspecialchars(getDisplayName(ucwords(str_replace('_', ' ', $status)))) ?></span>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-7">
                <div class="info-card">
                    <h5><i class="fas fa-file-invoice-dollar"></i> <?= __('payroll_request_details', 'Payroll Request Details') ?></h5>
                    <div class="details-grid">
                        <div class="detail-box">
                            <div class="detail-label"><?= __('payroll_month') ?></div>
                            <div class="detail-value"><?= htmlspecialchars($payrollRequest['payroll_month'] ?? __('not_available', 'N/A')) ?></div>
                        </div>
                        <div class="detail-box">
                            <div class="detail-label"><?= __('employees', 'Employees') ?></div>
                            <div class="detail-value"><?= (int)($payrollRequest['employee_count'] ?? 0) ?></div>
                        </div>
                        <div class="detail-box">
                            <div class="detail-label"><?= __('total_net', 'Total Net') ?></div>
                            <div class="detail-value"><i class="icon-saudi_riyal"></i> <?= number_format((float)($payrollRequest['total_net_salary'] ?? 0), 2) ?></div>
                        </div>
                        <div class="detail-box">
                            <div class="detail-label"><?= __('submitted', 'Submitted') ?></div>
                            <div class="detail-value"><?= !empty($payrollRequest['created_at']) ? htmlspecialchars(date('d M Y, H:i', strtotime($payrollRequest['created_at']))) : __('not_available', 'N/A') ?></div>
                        </div>
                        <div class="detail-box">
                            <div class="detail-label"><?= __('approved_at', 'Approved At') ?></div>
                            <div class="detail-value"><?= !empty($payrollRequest['approved_at']) ? htmlspecialchars(date('d M Y, H:i', strtotime($payrollRequest['approved_at']))) : __('not_available', 'N/A') ?></div>
                        </div>
                        <div class="detail-box">
                            <div class="detail-label"><?= __('processed_at', 'Processed At') ?></div>
                            <div class="detail-value"><?= !empty($payrollRequest['processed_at']) ? htmlspecialchars(date('d M Y, H:i', strtotime($payrollRequest['processed_at']))) : __('not_available', 'N/A') ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="info-card">
                    <h5><i class="fas fa-chart-pie"></i> <?= __('payroll_summary', 'Payroll Summary') ?></h5>
                    <div class="stats-grid">
                        <div class="stat-box">
                            <div class="stat-label"><?= __('generated', 'Generated') ?></div>
                            <div class="stat-value"><?= (int)($payrollRequest['generated_count'] ?? 0) ?></div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-label"><?= __('paid', 'Paid') ?></div>
                            <div class="stat-value"><?= (int)($payrollRequest['paid_count'] ?? 0) ?></div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-label"><?= __('approved_by', 'Approved By') ?></div>
                            <div class="stat-value" style="font-size:15px;"><?= htmlspecialchars(payrollStatusHistoryDisplayName($payrollRequest['approved_by_name'] ?: __('not_available', 'N/A'), $payrollRequest['approved_by_user_type'] ?? '')) ?></div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-label"><?= __('processed_by', 'Processed By') ?></div>
                            <div class="stat-value" style="font-size:15px;"><?= htmlspecialchars(payrollStatusHistoryDisplayName($payrollRequest['processed_by_name'] ?: __('not_available', 'N/A'), $payrollRequest['processed_by_user_type'] ?? '')) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="info-card">
            <h5><i class="fas fa-users-cog"></i> <?= __('approval_chain', 'Approval Chain') ?></h5>
            <div class="table-responsive">
                <table class="table table-sm chain-table table-hover mb-0">
                    <thead>
                        <tr>
                            <th width="70" class="text-center"><?= __('level') ?></th>
                            <th><?= __('approver', 'Approver') ?></th>
                            <th><?= __('status') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($chain)): ?>
                        <tr>
                            <td colspan="3" class="text-center text-muted"><i class="fas fa-info-circle"></i> <?= __('no_approval_chain', 'No approval chain found') ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($chain as $link): ?>
                            <?php
                            $chainStatus = strtolower((string)($link['status'] ?? 'pending'));
                            $chainBadgeClass = 'secondary';
                            $chainIcon = 'fa-circle';
                            if ($chainStatus === 'approved') {
                                $chainBadgeClass = 'success';
                                $chainIcon = 'fa-check-circle';
                            } elseif ($chainStatus === 'rejected') {
                                $chainBadgeClass = 'danger';
                                $chainIcon = 'fa-times-circle';
                            } elseif ($chainStatus === 'pending' || $chainStatus === 'awaiting') {
                                $chainBadgeClass = 'warning';
                                $chainIcon = 'fa-clock';
                            }
                            ?>
                            <?php $isHrPayrollApproverRow = strtolower(trim((string)($link['user_type'] ?? ''))) === 'hr_payroll'; ?>
                            <tr>
                                <td class="text-center"><span class="level-badge"><?= (int)($link['approval_level'] ?? 0) ?></span></td>
                                <td>
                                    <?php if ($isHrPayrollApproverRow): ?>
                                        <strong><?= __('hr_payroll', 'HR Payroll') ?></strong>
                                    <?php else: ?>
                                        <strong><?= htmlspecialchars(getDisplayName(parseName($link['approver_name']) ?? ($link['approver_name'] ?? __('not_available', 'N/A')))) ?></strong>
                                        <?php if (!empty($link['user_type'])): ?>
                                            <br><small class="text-muted"><i class="fas fa-user-tag"></i> <?= htmlspecialchars(getDisplayName(str_replace("_", " ", $link['user_type']))) ?></small>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?= htmlspecialchars($chainBadgeClass) ?>"><i class="fas <?= htmlspecialchars($chainIcon) ?>"></i> <?= htmlspecialchars(getDisplayName(ucfirst($chainStatus))) ?></span>
                                    <?php if (!empty($link['action_date'])): ?>
                                        <br><small class="text-muted"><i class="far fa-calendar-alt"></i> <?= htmlspecialchars(date('d M Y, H:i', strtotime($link['action_date']))) ?></small>
                                    <?php endif; ?>
                                    <?php if (!empty($link['note'])): ?>
                                        <br><small style="background:#f5f8fb;padding:6px 10px;border-radius:6px;display:block;margin-top:8px;border-<?= ($is_rtl ?? false) ? 'right' : 'left' ?>:3px solid #1f6aa5;"><strong><?= __('note') ?>:</strong> <?= nl2br(htmlspecialchars(getDisplayName($link['note']))) ?></small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if (!empty($financeVerification)): ?>
        <div class="info-card">
            <h5><i class="fas fa-user-check"></i> <?= __('finance_verification_status', 'Finance Verification Status') ?></h5>
            <div class="table-responsive">
                <table class="table table-sm chain-table table-hover mb-0">
                    <thead>
                        <tr>
                            <th><?= __('finance_manager', 'Finance Manager') ?></th>
                            <th><?= __('finance_officer', 'Finance Officer') ?></th>
                            <th><?= __('assigned_companies', 'Assigned Companies') ?></th>
                            <th><?= __('status') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($financeVerification as $verificationRow): ?>
                            <?php
                            $isApproved = !empty($verificationRow['officer_approved']);
                            $isConfirmed = !empty($verificationRow['is_confirmed']);
                            if ($isApproved) {
                                $verificationBadgeClass = 'success';
                                $verificationIcon = 'fa-check-circle';
                                $verificationLabel = __('approved', 'Approved');
                            } elseif ($isConfirmed) {
                                $verificationBadgeClass = 'warning';
                                $verificationIcon = 'fa-clock';
                                $verificationLabel = __('pending_officer_approval', 'Pending Officer Approval');
                            } else {
                                $verificationBadgeClass = 'secondary';
                                $verificationIcon = 'fa-circle';
                                $verificationLabel = __('not_confirmed', 'Not Confirmed');
                            }
                            ?>
                            <tr>
                                <td><?= htmlspecialchars(getDisplayName($verificationRow['manager_name'] ?: ($verificationRow['finance_manager_emp_id'] ?? __('not_available', 'N/A')))) ?></td>
                                <td><?= htmlspecialchars(getDisplayName($verificationRow['officer_name'] ?: ($verificationRow['finance_officer_emp_id'] ?? __('not_available', 'N/A')))) ?></td>
                                <td>
                                    <?php if (!empty($verificationRow['company_names'])): ?>
                                        <?php foreach ($verificationRow['company_names'] as $companyName): ?>
                                            <span class="status-badge badge-soft-secondary" style="margin:2px;"><?= htmlspecialchars($companyName) ?></span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="text-muted"><?= __('not_available', 'N/A') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?= htmlspecialchars($verificationBadgeClass) ?>"><i class="fas <?= htmlspecialchars($verificationIcon) ?>"></i> <?= htmlspecialchars($verificationLabel) ?></span>
                                    <?php if ($isApproved && !empty($verificationRow['officer_approved_at'])): ?>
                                        <br><small class="text-muted"><i class="far fa-calendar-alt"></i> <?= htmlspecialchars(date('d M Y, H:i', strtotime($verificationRow['officer_approved_at']))) ?></small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <div class="info-card">
            <h5><i class="fas fa-history"></i> <?= __('status_history_timeline', 'Status History Timeline') ?></h5>
            <?php if (empty($history)): ?>
                <div class="alert alert-info"><i class="fas fa-info-circle"></i> <?= __('no_history', 'No history found') ?></div>
            <?php else: ?>
                <div class="timeline">
                    <?php foreach ($history as $item): ?>
                        <?php
                        $historyStatus = strtolower((string)($item['status'] ?? 'pending_approval'));
                        $timelineClass = 'pending';
                        $timelineIcon = 'fa-clock';
                        $timelineBadgeClass = 'badge-soft-warning';

                        if (strpos($historyStatus, 'approved') !== false) {
                            $timelineClass = 'approved';
                            $timelineIcon = 'fa-check';
                            $timelineBadgeClass = 'badge-soft-success';
                        } elseif (strpos($historyStatus, 'rejected') !== false) {
                            $timelineClass = 'rejected';
                            $timelineIcon = 'fa-times';
                            $timelineBadgeClass = 'badge-soft-danger';
                        } elseif (strpos($historyStatus, 'completed') !== false) {
                            $timelineClass = 'completed';
                            $timelineIcon = 'fa-badge-check';
                            $timelineBadgeClass = 'badge-soft-primary';
                        } elseif (strpos($historyStatus, 'pending') !== false) {
                            $timelineClass = 'pending';
                            $timelineIcon = 'fa-clock';
                            $timelineBadgeClass = 'badge-soft-warning';
                        } else {
                            $timelineBadgeClass = 'badge-soft-secondary';
                        }
                        ?>
                        <div class="timeline-item <?= htmlspecialchars($timelineClass) ?>">
                            <div class="timeline-marker"><i class="fas <?= htmlspecialchars($timelineIcon) ?>"></i></div>
                            <div class="timeline-content">
                                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap" style="gap:8px;">
                                    <span class="status-badge <?= htmlspecialchars($timelineBadgeClass) ?>"><?= htmlspecialchars(getDisplayName(ucwords(str_replace('_', ' ', $historyStatus)))) ?></span>
                                    <small class="text-muted"><i class="far fa-clock"></i> <?= !empty($item['created_at']) ? htmlspecialchars(date('d M Y, H:i', strtotime($item['created_at']))) : __('not_available', 'N/A') ?></small>
                                </div>
                                <p class="mb-1"><strong><?= nl2br(htmlspecialchars(getDisplayName($item['note'] ?? __('no_notes', 'No notes')))) ?></strong></p>
                                <small class="text-muted"><i class="far fa-user"></i> <?= htmlspecialchars(payrollStatusHistoryDisplayName($item['emp_name'] ?? __('system', 'System'), $item['user_type'] ?? '')) ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="text-center mt-4">
                <a href="all_payroll_approvals.php" class="btn btn-back"><i class="fas fa-arrow-left"></i> <?= __('back_to_payroll_approvals', 'Back to Payroll Approvals') ?></a>
            </div>
        </div>
    </div>

    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conDB->close(); ?>