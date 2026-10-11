<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/sr_history_helpers.php';
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
    srh_die('Payroll request invoice number not provided.');
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
    srh_die('Unable to prepare payroll history query.');
}

$stmt->bind_param('s', $requestInvNo);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows === 1) {
    $payrollRequest = $result->fetch_assoc();
}
$stmt->close();

if (!$payrollRequest) {
    srh_die('Payroll approval request not found.');
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
        srh_die('Access denied: You are not authorized to view this payroll request.');
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
$avatarPath = getAvatarImagePath($payrollRequest['requester_avatar'] ?? '', $payrollRequest['requester_sex'] ?? 1);
$departmentName = ($is_rtl ?? false)
    ? ($payrollRequest['department_name_ar'] ?? $payrollRequest['department_name'] ?? __('not_available', 'N/A'))
    : ($payrollRequest['department_name'] ?? $payrollRequest['department_name_ar'] ?? __('not_available', 'N/A'));
$na = __('not_available', 'N/A');
$fmt = function ($v) use ($na) { return !empty($v) ? format_safe_date($v, 'd M Y, H:i') : $na; };

// Approval chain - hr_payroll accounts show as "HR Payroll" instead of their name.
$chain_rows = array_map(function ($link) {
    $isHrPayroll = strtolower(trim((string)($link['user_type'] ?? ''))) === 'hr_payroll';
    return [
        'level' => (int)($link['approval_level'] ?? 0),
        'name' => $isHrPayroll ? __('hr_payroll', 'HR Payroll') : getDisplayName(parseName($link['approver_name']) ?? ($link['approver_name'] ?? __('not_available', 'N/A'))),
        'role' => $isHrPayroll ? '' : ($link['user_type'] ?? ''),
        'status' => strtolower((string)($link['status'] ?? 'pending')),
        'date' => $link['action_date'] ?? null,
        'note' => !empty($link['note']) ? getDisplayName($link['note']) : '',
    ];
}, $chain);
$history_rows = array_map(function ($item) {
    return [
        'status' => strtolower((string)($item['status'] ?? 'pending_approval')),
        'by' => payrollStatusHistoryDisplayName($item['emp_name'] ?? __('system', 'System'), $item['user_type'] ?? ''),
        'date' => $item['created_at'] ?? null,
        'note' => getDisplayName($item['note'] ?? ''),
    ];
}, $history);

// Finance verification table
$finance_html = '';
if (!empty($financeVerification)) {
    $finance_html = '<div class="sr-table-wrap"><table class="sr-table" style="margin: 0;"><thead><tr>'
        . '<th>' . sr_h(__('finance_manager', 'Finance Manager')) . '</th><th>' . sr_h(__('finance_officer', 'Finance Officer')) . '</th>'
        . '<th>' . sr_h(__('assigned_companies', 'Assigned Companies')) . '</th><th>' . sr_h(__('status')) . '</th></tr></thead><tbody>';
    foreach ($financeVerification as $v) {
        $isApproved = !empty($v['officer_approved']);
        $isConfirmed = !empty($v['is_confirmed']);
        if ($isApproved) { $pill = sr_pill('green', __('approved', 'Approved'), true, 'mdi-check-circle'); }
        elseif ($isConfirmed) { $pill = sr_pill('amber', __('pending_officer_approval', 'Pending Officer Approval'), true, 'mdi-clock'); }
        else { $pill = sr_pill('slate', __('not_confirmed', 'Not Confirmed'), true); }
        $companies = '';
        foreach ($v['company_names'] as $companyName) $companies .= '<span class="sr-chip" style="margin: 2px;">' . sr_h($companyName) . '</span>';
        $finance_html .= '<tr><td>' . sr_h(getDisplayName($v['manager_name'] ?: ($v['finance_manager_emp_id'] ?? $na))) . '</td>'
            . '<td>' . sr_h(getDisplayName($v['officer_name'] ?: ($v['finance_officer_emp_id'] ?? $na))) . '</td>'
            . '<td>' . ($companies !== '' ? $companies : '<span class="text-muted">' . sr_h($na) . '</span>') . '</td>'
            . '<td>' . $pill . ($isApproved && !empty($v['officer_approved_at']) ? '<span class="sr-cell-sub">' . sr_h(format_safe_date($v['officer_approved_at'], 'd M Y, H:i')) . '</span>' : '') . '</td></tr>';
    }
    $finance_html .= '</tbody></table></div>';
}

srh_page_open(__('payroll_approval_history', 'Payroll Approval History'));
echo srh_header(__('payroll_approval_history', 'Payroll Approval History'), __('complete_approval_timeline', 'Complete approval timeline and approver list'), 'all_payroll_approvals.php');

echo srh_hero(payrollStatusHistoryDisplayName($payrollRequest['requester_name'] ?: __('system', 'System'), $payrollRequest['requester_user_type'] ?? ''), $avatarPath, [
    [__('request_id', 'Request ID'), $payrollRequest['request_inv_no'], 'mdi-barcode'],
    [__('department'), getDisplayName($departmentName), 'mdi-domain'],
    [__('payroll_month'), $payrollRequest['payroll_month'] ?? '', 'mdi-calendar'],
], srh_status_pill($status));

echo srh_tiles([
    [__('payroll_month'), sr_h($payrollRequest['payroll_month'] ?? $na), 'indigo', ''],
    [__('employees', 'Employees'), (int)($payrollRequest['employee_count'] ?? 0), '', ''],
    [__('total_net', 'Total Net'), srh_money($payrollRequest['total_net_salary'] ?? 0), 'green', ''],
    [__('paid', 'Paid'), (int)($payrollRequest['paid_count'] ?? 0), '', sr_h(__('generated', 'Generated')) . ': ' . (int)($payrollRequest['generated_count'] ?? 0)],
]);
?>
<div class="srh-grid">
    <div>
        <?= srh_card('mdi-file-document', __('payroll_request_details', 'Payroll Request Details'), srh_kv([
            [__('request_id', 'Request ID'), '<span class="sr-mono">' . sr_h($payrollRequest['request_inv_no']) . '</span>', 'mdi-barcode'],
            [__('status'), srh_status_pill($status), 'mdi-flag'],
            [__('payroll_month'), sr_h($payrollRequest['payroll_month'] ?? $na), 'mdi-calendar'],
            [__('submitted', 'Submitted'), sr_h($fmt($payrollRequest['created_at'] ?? null)), 'mdi-send'],
            [__('approved_at', 'Approved At'), sr_h($fmt($payrollRequest['approved_at'] ?? null)), 'mdi-check-circle'],
            [__('processed_at', 'Processed At'), sr_h($fmt($payrollRequest['processed_at'] ?? null)), 'mdi-settings'],
        ])) ?>
        <?= srh_card('mdi-chart-pie', __('payroll_summary', 'Payroll Summary'), srh_kv([
            [__('employees', 'Employees'), (int)($payrollRequest['employee_count'] ?? 0), 'mdi-account-multiple'],
            [__('total_net', 'Total Net'), srh_money($payrollRequest['total_net_salary'] ?? 0), 'mdi-cash-multiple'],
            [__('generated', 'Generated'), (int)($payrollRequest['generated_count'] ?? 0), 'mdi-file-document'],
            [__('paid', 'Paid'), (int)($payrollRequest['paid_count'] ?? 0), 'mdi-check-all'],
            [__('approved_by', 'Approved By'), sr_h(payrollStatusHistoryDisplayName($payrollRequest['approved_by_name'] ?: $na, $payrollRequest['approved_by_user_type'] ?? '')), 'mdi-account-check'],
            [__('processed_by', 'Processed By'), sr_h(payrollStatusHistoryDisplayName($payrollRequest['processed_by_name'] ?: $na, $payrollRequest['processed_by_user_type'] ?? '')), 'mdi-account'],
        ])) ?>
    </div>
    <div>
        <?= srh_card('mdi-sitemap', __('approval_chain', 'Approval Chain'), srh_chain($chain_rows), count($chain_rows) . ' ' . sr_h(__('levels', 'levels'))) ?>
    </div>
</div>
<?php if ($finance_html !== ''): ?>
    <?= srh_card('mdi-account-check', __('finance_verification_status', 'Finance Verification Status'), $finance_html) ?>
<?php endif; ?>
<?= srh_card('mdi-history', __('status_history_timeline', 'Status History Timeline'), srh_timeline($history_rows)) ?>
<?php
srh_page_close();
