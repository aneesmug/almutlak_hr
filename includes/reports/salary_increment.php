<?php
/**
 * Salary increment report - body part of request_report.php?type=salary_increment&inv=<request_inv_no>
 * Header, employee banner, approval chain, signatures and footer come from includes/report_layout.php.
 * Data + per-request visibility match ajaxSalaryIncrement.php (getSalaryIncrementReport).
 */

include './includes/avatar_select.php';

$request_inv_no = trim((string)($_GET['inv'] ?? ''));
if ($request_inv_no === '') {
    die('Invalid request parameters.');
}

$stmt = $conDB->prepare("SELECT si.*, e.name AS employee_name, e.dept AS employee_dept, e.joining_date,
        sup.name AS submitted_by_name, dsup.name AS direct_supervisor_name, co.name AS nationality, l.name_en AS location_name
    FROM emp_salary_increment si
    JOIN employees e ON si.emp_id = e.emp_id
    LEFT JOIN employees sup ON si.submitted_by = sup.emp_id
    LEFT JOIN employees dsup ON e.supervisor_id = dsup.emp_id
    LEFT JOIN countries co ON co.id = e.country
    LEFT JOIN locations l ON l.id = e.location_id
    WHERE si.request_inv_no = ? LIMIT 1");
$stmt->bind_param('s', $request_inv_no);
$stmt->execute();
$si = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$si) {
    die('Salary increment request not found.');
}

$type_row = mysqli_fetch_assoc(mysqli_query($conDB, "SELECT id FROM approval_request_types WHERE type_name = 'salary_increment' LIMIT 1"));
$request_type_id = (int)($type_row['id'] ?? 0);

// Same visibility as all_applied_salary_increment.php: admin/HR/finance see all;
// others only their department, their own submissions or requests they approve.
$current_user_id = $_SESSION['empid'] ?? 0;
$can_view = !empty($is_system_admin) || !empty($isHR) || stripos((string)($user_type ?? ''), 'finance') !== false
    || (string)$si['submitted_by'] === (string)$current_user_id
    || (isset($user_dept) && (string)$si['employee_dept'] === (string)$user_dept);
if (!$can_view) {
    $delegated_from_id = (int)(getDelegatedFromEmpId($conDB, $current_user_id) ?? $current_user_id);
    $acc = $conDB->prepare("SELECT 1 FROM request_approvers WHERE request_inv_no = ? AND request_type_id = ? AND approver_id IN (?, ?) LIMIT 1");
    $acc->bind_param('siii', $request_inv_no, $request_type_id, $current_user_id, $delegated_from_id);
    $acc->execute();
    $can_view = $acc->get_result()->num_rows > 0;
    $acc->close();
}
if (!$can_view) {
    header('Location: error403.php?page=all_applied_salary_increment.php');
    exit();
}

// Current active salary breakdown
$salary = null;
$stmt = $conDB->prepare("SELECT basic, housing, transport, food, misc, cashier, fuel, tel, other, guard,
        (basic + housing + transport + food + misc + cashier + fuel + tel + other + guard) AS total_salary
    FROM emp_salary WHERE emp_id = ? AND status = 1 ORDER BY id DESC LIMIT 1");
$stmt->bind_param('s', $si['emp_id']);
$stmt->execute();
$salary = $stmt->get_result()->fetch_assoc();
$stmt->close();
$salary_rows = [
    'basic' => __('basic', 'Basic'), 'housing' => __('housing', 'Housing'), 'transport' => __('transport', 'Transport'),
    'food' => __('food', 'Food'), 'misc' => __('misc', 'Misc'), 'cashier' => __('cashier', 'Cashier'),
    'fuel' => __('fuel', 'Fuel'), 'tel' => __('tel', 'Telephone'), 'other' => __('others', 'Other'), 'guard' => __('guard', 'Guard'),
];

// Approval chain
$approval_chain = [];
$stmt = $conDB->prepare("SELECT ra.*, e.name AS approver_name, d.dep_nme AS approver_dept_name, e.avatar AS approver_avatar, e.sex AS approver_sex,
        (SELECT al.user_type FROM admin_login al WHERE al.emp_id = ra.approver_id LIMIT 1) AS approver_role
    FROM request_approvers ra
    LEFT JOIN employees e ON e.emp_id = ra.approver_id
    LEFT JOIN department d ON d.id = e.dept
    WHERE ra.request_inv_no = ? AND ra.request_type_id = ?
    ORDER BY ra.approval_level ASC, ra.id ASC");
$stmt->bind_param('si', $request_inv_no, $request_type_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $approval_chain[] = $row;
}
$stmt->close();

$gm_step = null;
foreach ($approval_chain as $ap) {
    if (strtolower((string)$ap['approver_role']) === 'gm' && $ap['status'] === 'approved') $gm_step = $ap;
}

$service = '-';
if (!empty($si['joining_date']) && $si['joining_date'] !== '0000-00-00') {
    try {
        $diff = (new DateTime($si['joining_date']))->diff(new DateTime('today'));
        $service = $diff->y . ' ' . __('years', 'Years') . ' ' . $diff->m . ' ' . __('months', 'Months');
    } catch (Exception $e) {
    }
}
$money = fn($v) => number_format((float)$v, 2) . ' SAR';
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

// ---- Shared report layout (includes/report_layout.php) ----
$report = [
    'title'      => __('salary_increment_report', 'Salary Increment Report'),
    'request_no' => $si['request_inv_no'],
    'status'     => (string)$si['current_status'],
    'dates'      => [__('submitted_date', 'Submitted Date') => $si['created_at'] ?? null],
    'emp_id'     => (string)$si['emp_id'],
    'back_url'   => 'all_applied_salary_increment.php',
];
include dirname(__DIR__) . '/report_open.php';
?>
                            <div class="report-section">
                                <h5 class="section-title"><i class="mdi mdi-trending-up"></i> <?= __('increment_details', 'Increment Details') ?></h5>
                                <div class="grid-details">
                                    <div class="detail-item">
                                        <span class="label"><?= __('increment_amount', 'Increment Amount') ?></span>
                                        <span class="value"><?= $money($si['increment_amount']) ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label"><?= __('approved_amount', 'Approved Amount') ?><?= $gm_step ? ' · ' . __('gm', 'GM') : '' ?></span>
                                        <span class="value highlight"><?= $si['approved_amount'] !== null ? $money($si['approved_amount']) : '-' ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label"><?= __('increment_effective_date', 'Increment Effective Date') ?></span>
                                        <span class="value"><?= $h(format_safe_date($si['last_increment_date'] ?? null, 'd M Y', '-')) ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label"><?= __('evaluation_score', 'Evaluation Score') ?></span>
                                        <span class="value"><?= $si['evaluation_score'] !== null ? number_format((float)$si['evaluation_score'], 2) : '-' ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="report-section">
                                <h5 class="section-title"><i class="mdi mdi-account-card-details"></i> <?= __('employee_information', 'Employee Information') ?></h5>
                                <div class="grid-details">
                                    <div class="detail-item"><span class="label"><?= __('nationality', 'Nationality') ?></span><span class="value"><?= $h(getDisplayName((string)($si['nationality'] ?? '')) ?: '-') ?></span></div>
                                    <div class="detail-item"><span class="label"><?= __('location', 'Location') ?></span><span class="value"><?= $h(getDisplayName((string)($si['location_name'] ?? '')) ?: '-') ?></span></div>
                                    <div class="detail-item"><span class="label"><?= __('years_of_service', 'Years of Service') ?></span><span class="value"><?= $h($service) ?></span></div>
                                    <div class="detail-item"><span class="label"><?= __('direct_supervisor', 'Direct Supervisor') ?></span><span class="value"><?= $h(getDisplayName(parseName((string)($si['direct_supervisor_name'] ?? ''))) ?: '-') ?></span></div>
                                </div>
                            </div>

                            <div class="rpt-cols">
                                <div class="report-section">
                                    <h5 class="section-title"><i class="mdi mdi-cash-multiple"></i> <?= __('current_salary', 'Current Salary') ?></h5>
                                    <?php if ($salary): ?>
                                        <table class="table">
                                            <thead><tr><th><?= __('salary_component', 'Salary Component') ?></th><th class="text-right"><?= __('amount', 'Amount') ?></th></tr></thead>
                                            <tbody>
                                                <?php foreach ($salary_rows as $key => $label): if ((float)$salary[$key] <= 0) continue; ?>
                                                    <tr><td><?= $h($label) ?></td><td class="text-right"><?= $money($salary[$key]) ?></td></tr>
                                                <?php endforeach; ?>
                                                <tr class="bg-light"><td><?= __('total_salary', 'Total Salary') ?></td><td class="text-right"><?= $money($salary['total_salary']) ?></td></tr>
                                            </tbody>
                                        </table>
                                    <?php else: ?>
                                        <div class="info-box"><?= __('no_salary_breakdown', 'No active salary breakdown on record.') ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="report-section">
                                    <h5 class="section-title"><i class="mdi mdi-file-document"></i> <?= __('request_information', 'Request Information') ?></h5>
                                    <table class="info-table">
                                        <tr><td class="label"><?= __('submitted_by', 'Submitted By') ?></td><td class="value"><?= $h(getDisplayName(parseName((string)($si['submitted_by_name'] ?? $si['submitted_by'])))) ?></td></tr>
                                        <tr><td class="label"><?= __('submitted_date', 'Submitted Date') ?></td><td class="value"><?= $h(format_safe_date($si['created_at'] ?? null, 'd M Y, H:i', '-')) ?></td></tr>
                                        <?php if ($gm_step): ?>
                                        <tr><td class="label"><?= __('general_manager', 'General Manager') ?></td><td class="value"><?= $h(getDisplayName(parseName((string)$gm_step['approver_name']))) ?></td></tr>
                                        <?php endif; ?>
                                    </table>
                                    <?php if (!empty($si['reason'])): ?>
                                        <div class="info-box" style="margin-top: 8px; white-space: pre-line;"><strong><?= __('reason', 'Reason') ?>:</strong> <?= $h(getDisplayName((string)$si['reason'])) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
<?php
$report_end = [
    'chain'        => $approval_chain,
    'chain_status' => (string)$si['current_status'],
    'signatures'   => ['direct_supervisor_signature', 'hr_manager_signature', 'general_manager_signature'],
];
include dirname(__DIR__) . '/report_close.php';
$conDB->close();
