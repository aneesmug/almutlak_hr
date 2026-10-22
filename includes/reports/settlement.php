<?php
/**
 * Settlement report - body part of request_report.php?type=settlement&id=<settlement_records.id>
 * Header, employee banner, approval chain, signatures and footer come from includes/report_layout.php.
 * Data matches settlement_handler.php (get_settlement_details); visibility matches all_settlements.php.
 */

include './includes/avatar_select.php';

$settlement_id = (int)($_GET['id'] ?? 0);
if ($settlement_id <= 0) {
    die('Invalid request parameters.');
}

$stmt = $conDB->prepare("SELECT s.*, e.dept AS employee_dept, e.supervisor_id, p.name AS payer_name
    FROM settlement_records s
    JOIN employees e ON e.emp_id = s.emp_id
    LEFT JOIN employees p ON p.emp_id = s.settlement_approver
    WHERE s.id = ? LIMIT 1");
$stmt->bind_param('i', $settlement_id);
$stmt->execute();
$st = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$st) {
    die('Settlement not found.');
}

$type_row = mysqli_fetch_assoc(mysqli_query($conDB, "SELECT id FROM approval_request_types WHERE type_name = 'settlement' LIMIT 1"));
$request_type_id = (int)($type_row['id'] ?? 0);

// Same visibility as all_settlements.php: admin/HR/finance see all departments;
// others their department, their direct reports, or settlements they approve.
$current_user_id = (int)($_SESSION['empid'] ?? 0);
$can_view = !empty($is_system_admin) || !empty($isHR) || stripos((string)($user_type ?? ''), 'finance') !== false
    || (isset($user_dept) && (string)$st['employee_dept'] === (string)$user_dept)
    || (int)$st['supervisor_id'] === $current_user_id;
if (!$can_view) {
    $delegated_from_id = (int)(getDelegatedFromEmpId($conDB, $current_user_id) ?? $current_user_id);
    $acc = $conDB->prepare("SELECT 1 FROM request_approvers WHERE request_inv_no = ? AND request_type_id = ? AND approver_id IN (?, ?) LIMIT 1");
    $acc->bind_param('siii', $st['request_inv_no'], $request_type_id, $current_user_id, $delegated_from_id);
    $acc->execute();
    $can_view = $acc->get_result()->num_rows > 0;
    $acc->close();
}
if (!$can_view) {
    header('Location: error403.php?page=all_settlements.php');
    exit();
}

// Related request (settlement no. = SETL-<original request no.>) and its report link
$type_lc = strtolower((string)$st['request_type']);
$original_inv_no = preg_replace('/^SETL-/', '', (string)$st['request_inv_no']);
$related = null; // [label, url]
foreach (['vacation' => ['emp_vacation', 'vacation', __('view_vacation_report', 'View Vacation Report')],
          'loan' => ['emp_loan', 'loan', __('view_loan_report', 'View Loan Report')],
          'business_trip' => ['emp_business_trip', 'business_trip', __('business_trip_report', 'Business Trip Report')]] as $needle => [$table, $rtype, $label]) {
    if (strpos($type_lc, $needle) === false) continue;
    $q = $conDB->prepare("SELECT id FROM `$table` WHERE request_inv_no = ? LIMIT 1");
    $q->bind_param('s', $original_inv_no);
    $q->execute();
    $rid = (int)($q->get_result()->fetch_assoc()['id'] ?? 0);
    $q->close();
    if ($rid > 0) $related = [$label, 'request_report.php?type=' . $rtype . '&id=' . $rid . '&emp_id=' . urlencode((string)$st['emp_id'])];
}
if (!$related && strpos($type_lc, 'resignation') !== false) {
    $related = [__('view_end_of_service_report', 'View End of Service Report'), 'eos_pdf.php?emp_id=' . urlencode((string)$st['emp_id'])];
}

// Approval chain - latest row per (level, approver); levels after the pending one show as awaiting
$approval_chain = [];
$stmt = $conDB->prepare("SELECT ra.*, e.name AS approver_name, d.dep_nme AS approver_dept_name, e.avatar AS approver_avatar, e.sex AS approver_sex
    FROM request_approvers ra
    LEFT JOIN employees e ON e.emp_id = ra.approver_id
    LEFT JOIN department d ON d.id = e.dept
    WHERE ra.request_inv_no = ? AND ra.request_type_id = ?
    ORDER BY ra.approval_level ASC, ra.id DESC");
$stmt->bind_param('si', $st['request_inv_no'], $request_type_id);
$stmt->execute();
$res = $stmt->get_result();
$seen = [];
$pending_found = false;
while ($row = $res->fetch_assoc()) {
    $key = $row['approval_level'] . '_' . $row['approver_id'];
    if (isset($seen[$key])) continue;
    $seen[$key] = true;
    if ($pending_found) {
        $row['status'] = 'awaiting';
        $row['action_date'] = null;
    } elseif (in_array($row['status'], ['pending', 'awaiting'], true)) {
        $pending_found = true;
    }
    $approval_chain[] = $row;
}
$stmt->close();

// Attachments (same path rules as the settlement popup)
$attachments = [];
$stmt = $conDB->prepare("SELECT file_path, uploaded_at FROM settlement_attachments WHERE settlement_id = ? OR request_inv_no = ? ORDER BY id");
$stmt->bind_param('is', $settlement_id, $st['request_inv_no']);
$stmt->execute();
$res = $stmt->get_result();
while ($att = $res->fetch_assoc()) {
    $path = (string)$att['file_path'];
    if ($path === '') continue;
    if (strpos($path, '/') === false) {
        $path = 'uploads/settlement_attachments/' . (!empty($att['uploaded_at']) ? date('Y/m', strtotime($att['uploaded_at'])) . '/' : '') . $path;
    }
    $attachments[] = $path;
}
$stmt->close();
$attachments = array_values(array_unique($attachments));

$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$amount = (float)$st['settlement_amount'];
$type_label = __($type_lc, ucwords(str_replace('_', ' ', $type_lc)));

// Header actions (screen only): related report, payment proof, attachments
$actions = [];
if ($related) $actions[] = ['label' => $related[0], 'icon' => 'mdi-file-document', 'href' => $related[1]];
if (!empty($st['payment_reference'])) $actions[] = ['label' => __('payment_proof', 'Payment Proof'), 'icon' => 'mdi-download', 'href' => (string)$st['payment_reference']];
if ($attachments) {
    $actions[] = ['label' => __('attachments', 'Attachments'), 'icon' => 'mdi-paperclip', 'count' => count($attachments),
        'onclick' => report_attachments_js($attachments, __('attachments', 'Attachments'))];
}

// ---- Shared report layout (includes/report_layout.php) ----
$report = [
    'title'      => __('settlement_report', 'Settlement Report'),
    'request_no' => $st['request_inv_no'],
    'status'     => (string)$st['settlement_status'],
    'dates'      => [__('created', 'Created') => $st['created_at'] ?? null, __('payment_date', 'Payment Date') => $st['payment_date'] ?? null],
    'emp_id'     => (string)$st['emp_id'],
    'back_url'   => 'all_settlements.php',
    'actions'    => $actions,
];
include dirname(__DIR__) . '/report_open.php';
?>
                            <div class="report-section">
                                <h5 class="section-title"><i class="mdi mdi-cash-multiple"></i> <?= __('settlement_information', 'Settlement Information') ?></h5>
                                <div class="grid-details">
                                    <div class="detail-item">
                                        <span class="label"><?= __('settlement_amount', 'Settlement Amount') ?></span>
                                        <span class="value <?= $amount < 0 ? 'text-danger' : 'highlight' ?>"><?= number_format(round($amount), 2) ?> SAR</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label"><?= __('settlement_method', 'Settlement Method') ?></span>
                                        <span class="value"><?= $h(__((string)$st['settlement_method'], ucwords(str_replace('_', ' ', (string)$st['settlement_method'])))) ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label"><?= __('request_type', 'Request Type') ?></span>
                                        <span class="value"><?= $h($type_label) ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label"><?= __('related_request', 'Related Request') ?></span>
                                        <span class="value font-monospace"><?= $h($original_inv_no) ?></span>
                                    </div>
                                    <?php if (!empty($st['payer_name'])): ?>
                                    <div class="detail-item">
                                        <span class="label"><?= __('paid_by', 'Paid By') ?></span>
                                        <span class="value"><?= $h(getDisplayName(parseName((string)$st['payer_name']))) ?></span>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($related): ?>
                                    <div class="detail-item no-print">
                                        <span class="label"><?= __('related_request', 'Related Request') ?></span>
                                        <span class="value"><a href="<?= $h($related[1]) ?>" target="_blank" class="rpt-inline-btn"><i class="mdi mdi-file-document"></i> <?= $h($related[0]) ?></a></span>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($attachments): ?>
                                    <div class="detail-item no-print">
                                        <span class="label"><?= __('attachments', 'Attachments') ?></span>
                                        <span class="value"><button type="button" class="rpt-inline-btn" onclick="<?= $h(report_attachments_js($attachments, __('attachments', 'Attachments'))) ?>"><i class="mdi mdi-paperclip"></i> <?= __('attachments', 'Attachments') ?> (<?= count($attachments) ?>)</button></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if (trim((string)($st['notes'] ?? '')) !== ''): ?>
                            <div class="report-section">
                                <h5 class="section-title"><i class="mdi mdi-note-text"></i> <?= __('notes', 'Notes') ?></h5>
                                <div class="info-box" style="white-space: pre-line;"><?= $h(getDisplayName((string)$st['notes'])) ?></div>
                            </div>
                            <?php endif; ?>
<?php
$report_end = [
    'chain'        => $approval_chain,
    'chain_status' => (string)$st['settlement_status'],
    'signatures'   => ['hr_manager_signature', 'finance_manager_signature', 'general_manager_signature'],
];
include dirname(__DIR__) . '/report_close.php';
$conDB->close();
