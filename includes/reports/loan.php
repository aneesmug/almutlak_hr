<?php
/**
 * Loan request report - body part of request_report.php?type=loan&id=..&emp_id=..
 * Header, employee banner, approval chain, signatures and footer come from includes/report_layout.php.
 * (Built from loan_report_details.php.)
 */

/****************************************************************
 * MODIFICATION SUMMARY:
 * 1. NEW FILE: This file was created to display a detailed, printable report for a single loan request.
 * 2. DATA FETCHING: It securely fetches all relevant data for the loan, the employee, and the loan's approval and payment history.
 * 3. DYNAMIC LAYOUT: The report displays employee information, loan details, a full payment history with payment methods, and a step-by-step approval timeline.
 * 4. PRINT STYLES: Includes CSS to ensure the report is formatted correctly for printing.
 ****************************************************************/

require_once dirname(__DIR__) . '/special_access_helper.php';

// Ensure UTF-8 encoding for database connection
mysqli_set_charset($conDB, "utf8mb4");

// Restrict access: Employees cannot view this detailed report page,
// unless explicitly granted via app_settings -> Special Access.
if (
    isset($isEmployee) && $isEmployee === true
    && !user_has_special_access($conDB, $empid ?? '', 'access_loan_report_details', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)
) {
    header("Location: ./profile.php");
    exit();
}

$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='" . $username . "'");
if (mysqli_num_rows($query) == 1) {
    include("./includes/avatar_select.php");
    // Fetch request identifiers
    $loan_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $emp_id  = isset($_GET['emp_id']) ? $_GET['emp_id'] : '';

    if ($loan_id === 0 || empty($emp_id)) {
        die(__('invalid_request_parameters'));
    }

    // Fetch main loan and employee data
    $sql = "SELECT 
                l.*, 
                e.name as employee_name,
                e.avatar,
                e.joining_date,
                e.dept AS dept_id,
                e.bank_name,
                e.iban,
                bl.name as bank_name_en,
                bl.bank_name_ar,
                d.dep_nme AS `deptname`,
                d.dep_nme_ar AS `deptname_ar`,
                comp.comp_name,
                comp.comp_name_ar,
                c.name AS `country_name`,
                rejected_by_emp.name as rejected_by_name,
                ela.notes as rejection_notes,
                ela.status as rejection_action,
                ela.created_at as rejection_timestamp,
                approver_emp.name as rejection_approver_name
            FROM emp_loan l
            JOIN employees e ON l.emp_id = e.emp_id
            LEFT JOIN bank_list bl ON e.bank_name = bl.bnk_id
            LEFT JOIN department d ON e.dept = d.id
            LEFT JOIN companies comp ON e.comp_no = comp.comp_id
            LEFT JOIN countries c ON e.country = c.id
            LEFT JOIN employees rejected_by_emp ON l.rejected_by = rejected_by_emp.emp_id
            LEFT JOIN emp_loan_approvals ela ON ela.loan_id = l.id AND ela.status = 'rejected'
            LEFT JOIN employees approver_emp ON ela.approver_id = approver_emp.emp_id
            WHERE l.id = ? AND l.emp_id = ?";

    $stmt = $conDB->prepare($sql);
    $stmt->bind_param("is", $loan_id, $emp_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $loan_details = $result->fetch_assoc();
    $stmt->close();

    if (!$loan_details) {
        die(__('loan_request_not_found'));
    }

    // Department access scoping: allow HR/System Admin or approvers
    $canSeeAll = ($is_system_admin ?? false) || ($isHR ?? false);
    if (!$canSeeAll) {
        $userDeptId = $user_dept ?? null;
        $sameDept = ($userDeptId !== null && isset($loan_details['dept_id'])) ? ((int)$loan_details['dept_id'] === (int)$userDeptId) : false;
        $inChain = false;
        if (!$sameDept && !empty($loan_details['inv_no'])) {
            if ($chk = $conDB->prepare("SELECT 1 FROM request_approvers ra JOIN approval_request_types t ON t.id = ra.request_type_id AND t.type_name = 'loan_request' WHERE ra.request_inv_no = ? AND ra.approver_id IN (?, {$approver_alt_id}) LIMIT 1")) {
                $inv = $loan_details['inv_no'];
                $chk->bind_param('si', $inv, $empid);
                if ($chk->execute()) {
                    $resChk = $chk->get_result();
                    $inChain = ($resChk && $resChk->num_rows > 0);
                }
                $chk->close();
            }
        }
        if (!$sameDept && !$inChain) {
            http_response_code(403);
            die('<div style="padding:16px;margin:16px;border:1px solid #f5c2c7;background:#f8d7da;color:#842029;border-radius:8px;">Access denied: You are not authorized to view this loan report.</div>');
        }
    }

    // Fetch Payment History
    $payment_history = [];
    $stmt_payments = $conDB->prepare("SELECT * FROM `emp_loan_payments` WHERE `loan_id` = ? ORDER BY `payment_date` ASC");
    $stmt_payments->bind_param("i", $loan_id);
    $stmt_payments->execute();
    $payments_result = $stmt_payments->get_result();
    while ($row = $payments_result->fetch_assoc()) {
        $payment_history[] = $row;
    }
    $stmt_payments->close();

    // Fetch Approval Chain from request_approvers table and merge with emp_loan_approvals notes
    $approval_chain = [];
    if (!empty($loan_details['inv_no'])) {
        $chain_sql = "SELECT ra.*,
                      COALESCE(e.name, al.fullname, al.username) as approver_name,
                      al.user_type as approver_role,
                      d2.dep_nme AS approver_dept_name,
                      e.avatar AS approver_avatar, e.sex AS approver_sex
                      FROM request_approvers ra
                      LEFT JOIN employees e ON ra.approver_id = e.emp_id
                      LEFT JOIN admin_login al ON ra.approver_id = al.id_iqama
                      LEFT JOIN department d2 ON e.dept = d2.id
                      WHERE ra.request_inv_no = ? AND ra.request_type_id = 2
                      ORDER BY ra.approval_level";
        $stmt_chain = $conDB->prepare($chain_sql);
        $stmt_chain->bind_param("s", $loan_details['inv_no']);
        $stmt_chain->execute();
        $chain_result = $stmt_chain->get_result();
        while ($row = $chain_result->fetch_assoc()) {
            $approval_chain[] = $row;
        }
        $stmt_chain->close();
    }

    // Status text mapping (display-friendly)
    $all_loan_statuses = [
        'pending'                => __('pending'),
        'dept_manager_pending'   => __('pending_department_manager'),
        'hr_manager_pending'     => __('pending_hr_manager'),
        'finance_manager_pending'=> __('pending_finance_manager'),
        'gm_pending'             => __('pending_gm'),
        'finance_assistant_pending' => __('pending_final_processing'),
        'approved'               => __('approved_and_processed'),
        'paid'                   => __('paid_and_closed'),
        'rejected'               => __('rejected')
    ];
    $raw_status = strtolower((string)($loan_details['status'] ?? ''));
    if ($raw_status === '') {
        $status_text = __('unknown_status');
    } else {
        $status_text = $all_loan_statuses[$raw_status] ?? ucwords(str_replace('_', ' ', $raw_status));
    }

    // Payment proof + payment receipts open from the screen header bar (not part of the printed report)
    $loan_actions = [];
    if (!empty($loan_details['payment_proof_file'])) {
        $loan_actions[] = ['label' => __('payment_proof', 'Payment Proof'), 'icon' => 'mdi-download',
            'href' => './assets/loan_payment_proofs/' . rawurlencode((string)$loan_details['payment_proof_file'])];
    }
    $loan_receipts = [];
    foreach ($payment_history as $payment) {
        if (!empty($payment['attachment'])) {
            $dir = (($payment['payment_method'] ?? '') === 'manual') ? './assets/loan_manual_payments/' : './assets/loan_receipts/';
            $loan_receipts[] = $dir . $payment['attachment'];
        }
    }
    if ($loan_receipts) {
        $loan_actions[] = ['label' => __('payment_receipts', 'Payment Receipts'), 'icon' => 'mdi-paperclip', 'count' => count($loan_receipts),
            'onclick' => 'showAttachmentsModal(' . json_encode($loan_receipts) . ', ' . json_encode(__('payment_receipts', 'Payment Receipts')) . ')'];
    }

    // ---- Shared report layout (includes/report_layout.php) ----
    $report = [
        'title'      => __('loan_request_report_header'),
        'request_no' => !empty($loan_details['inv_no']) ? $loan_details['inv_no'] : ('#' . (int)$loan_details['id']),
        'status'     => (string)($loan_details['status'] ?? ''),
        'dates'      => [__('submitted_date', 'Submitted Date') => $loan_details['created_at'] ?? null, __('approved_at', 'Approved at') => $loan_details['approved_at'] ?? null],
        'emp_id'     => (string)$loan_details['emp_id'],
        'actions'    => $loan_actions,
    ];
    include dirname(__DIR__) . '/report_open.php';
?>
                                <div class="report-section">
                                    <h5 class="section-title"><i class="fa fa-money-check-alt"></i><?= __('loan_details_header') ?></h5>
                                    <div class="grid-details">
                                        <div class="detail-item"><span class="label"><?= __('invoice_no') ?></span> <span class="value"><code><?= htmlspecialchars($loan_details['inv_no'] ?? 'N/A'); ?></code></span></div>
                                        <div class="detail-item"><span class="label"><?= __('loan_type_label') ?></span> <span class="value"><?= getDisplayName(ucwords(str_replace('_', ' ', $loan_details['loan_type'] ?? 'N/A'))); ?></span></div>
                                        <div class="detail-item"><span class="label"><?= __('applied_loan_amount') ?></span> <span class="value"><?= number_format($loan_details['loan_amount'], 2); ?> <?= __('sar_currency') ?></span></div>
                                        <?php if (!empty($loan_details['approved_amount']) && $loan_details['approved_amount'] != $loan_details['loan_amount']): ?>
                                        <div class="detail-item"><span class="label"><?= __('approved_loan_amount') ?></span> <span class="value highlight" style="color: #28a745;"><?= number_format($loan_details['approved_amount'], 2); ?> <?= __('sar_currency') ?> <small class="text-muted">(<?= __('modified_by') ?>: <?= htmlspecialchars($loan_details['approved_by_emp_id'] ?? 'N/A') ?>)</small></span></div>
                                        <?php endif; ?>
                                        <div class="detail-item"><span class="label"><?= __('loan_installments_label') ?></span> <span class="value"><?= htmlspecialchars($loan_details['installments'] ?? '1'); ?> <?= __('months') ?></span></div>
                                        <div class="detail-item"><span class="label"><?= __('total_payable_label') ?></span> <span class="value highlight"><?= number_format($loan_details['total_payable'], 2); ?> <?= __('sar_currency') ?></span></div>
                                        <div class="detail-item"><span class="label"><?= __('monthly_deduction_label') ?></span> <span class="value"><?= number_format($loan_details['monthly_deduction'], 2); ?> <?= __('sar_currency') ?></span></div>
                                        <div class="detail-item"><span class="label"><?= __('start_date_label') ?></span> <span class="value"><?php $loanStartDate = $loan_details['start_date'] ?? ''; echo (!empty($loanStartDate) && $loanStartDate !== '0000-00-00') ? format_safe_date($loanStartDate, 'd M Y') : '-'; ?></span></div>
                                        <div class="detail-item"><span class="label"><?= __('end_date_label') ?></span> <span class="value"><?php $loanEndDate = $loan_details['end_date'] ?? ''; echo (!empty($loanEndDate) && $loanEndDate !== '0000-00-00') ? format_safe_date($loanEndDate, 'd M Y') : '-'; ?></span></div>
                                        <div class="detail-item"><span class="label"><?= __('current_status_label') ?></span> <span class="value highlight text-primary"><?= $status_text ?></span></div>
                                    </div>
                                </div>
                                            <div class="report-section">
                                                <h5 class="section-title"><i class="fa fa-history"></i> <?= __('payment_history_header') ?></h5>
                                                <?php if (empty($payment_history)): ?>
                                                    <div class="alert alert-info">
                                                        <i class="fa fa-info-circle"></i> <?= __('no_payments_recorded_yet') ?>
                                                    </div>
                                                <?php else: 
                                                    $total_paid = 0;
                                                    foreach ($payment_history as $payment) {
                                                        $total_paid += $payment['amount'];
                                                    }
                                                    $remaining_balance = $loan_details['total_payable'] - $total_paid;
                                                ?>
                                                    <div class="payment-summary">
                                                        <ul>
                                                            <li>
                                                                <span class="label"><?= __('total_paid') ?></span>
                                                                <span class="value text-success">+<?= number_format($total_paid, 2); ?> SAR</span>
                                                            </li>
                                                            <li>
                                                                <span class="label"><?= __('remaining_balance') ?></span>
                                                                <span class="value text-<?= $remaining_balance > 0 ? 'danger' : 'success' ?>"><?= number_format($remaining_balance, 2); ?> SAR</span>
                                                            </li>
                                                            <li class="total-payable">
                                                                <span class="label"><?= __('total_payable_label') ?></span>
                                                                <span class="value"><?= number_format($loan_details['total_payable'], 2); ?> SAR</span>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                    <div class="table-responsive">
                                                        <table class="table table-sm table-bordered">
                                                            <thead class="thead-light">
                                                                <tr>
                                                                    <th><?= __('date_header') ?></th>
                                                                    <th><?= __('method_header') ?></th>
                                                                    <th><?= __('receipt_id') ?></th>
                                                                    <th class="text-right"><?= __('amount_header') ?></th>
                                                                    <th class="text-center no-print"><?= __('attachment') ?></th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php foreach ($payment_history as $payment): 
                                                                    $payment_method = $payment['payment_method'] ?? 'auto';
                                                                    $payment_method_badge = '';
                                                                    $badge_icon = '';
                                                                    switch($payment_method) {
                                                                        case 'manual':
                                                                            $payment_method_badge = 'badge-success';
                                                                            $badge_icon = 'fa-hand-paper-o';
                                                                            $method_text = __('manual');
                                                                            break;
                                                                        case 'payroll':
                                                                            $payment_method_badge = 'badge-primary';
                                                                            $badge_icon = 'fa-calendar';
                                                                            $method_text = __('payroll');
                                                                            break;
                                                                        default:
                                                                            $payment_method_badge = 'badge-info';
                                                                            $badge_icon = 'fa-cog';
                                                                            $method_text = __('auto');
                                                                    }
                                                                ?>
                                                                <tr>
                                                                    <td><?= format_safe_date($payment['payment_date'] ?? null, 'd M Y'); ?></td>
                                                                    <td>
                                                                        <span class="badge <?= $payment_method_badge ?>">
                                                                            <i class="fa <?= $badge_icon ?>"></i> <?= $method_text ?>
                                                                        </span>
                                                                    </td>
                                                                    <td>
                                                                        <?php if (!empty($payment['receipt_id'])): ?>
                                                                            <small><?= htmlspecialchars($payment['receipt_id']); ?></small>
                                                                        <?php else: ?>
                                                                            <span class="text-muted">-</span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                    <td class="text-right font-weight-bold"><?= number_format($payment['amount'], 2); ?> SAR</td>
                                                                    <td class="text-center no-print">
                                                                        <?php if (!empty($payment['attachment'])):
                                                                            $file_path = ($payment_method === 'manual' ? './assets/loan_manual_payments/' : './assets/loan_receipts/') . htmlspecialchars($payment['attachment']);
                                                                        ?>
                                                                            <a href="<?= $file_path; ?>" target="_blank" class="rpt-inline-btn"><i class="mdi mdi-eye"></i></a>
                                                                        <?php else: ?>
                                                                            <span class="text-muted">-</span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                </tr>
                                                                <?php if (!empty($payment['note'])): ?>
                                                                <tr>
                                                                    <td colspan="5" class="bg-light">
                                                                        <small class="text-muted">
                                                                            <i class="fa fa-comment"></i> <strong><?= __('note_label') ?>:</strong> <?= htmlspecialchars($payment['note'], ENT_QUOTES, 'UTF-8'); ?>
                                                                        </small>
                                                                    </td>
                                                                </tr>
                                                                <?php endif; ?>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                            <tfoot class="font-weight-bold bg-light">
                                                                <tr>
                                                                    <td colspan="3" class="text-right"><?= __('total_paid') ?>:</td>
                                                                    <td class="text-right text-success"><?= number_format($total_paid, 2); ?> SAR</td>
                                                                    <td class="no-print"></td>
                                                                </tr>
                                                            </tfoot>
                                                        </table>
                                                    </div>
                                                    <?php if ($remaining_balance <= 0): ?>
                                                    <div class="alert alert-success mt-2 mb-0">
                                                        <i class="fa fa-check-circle"></i> <strong><?= __('loan_fully_paid') ?></strong>
                                                    </div>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <?php 
                                                // Show bank payment information only if:
                                                // 1. Loan is approved AND
                                                // 2. Current user is finance officer OR system admin
                                                $show_bank_info = false;
                                                if ($loan_details['status'] === 'approved' || $loan_details['status'] === 'paid') {
                                                    $show_bank_info = ($isFinance_Officer ?? false) || ($is_system_admin ?? false) || ($isHR_Payroll ?? false);
                                                }
                                            ?>

                                            <?php if ($show_bank_info): ?>
                                            <div class="report-section">
                                                <h5 class="section-title"><i class="fa fa-university"></i><?= __('bank_payment_information') ?? 'Bank Payment Information' ?></h5>
                                                <div class="grid-details">
                                                    <div class="detail-item">
                                                        <span class="label"><?= __('bank_name') ?></span>
                                                        <span class="value">
                                                            <?php 
                                                                $bank_display = '';
                                                                if (!empty($loan_details['bank_name_en']) || !empty($loan_details['bank_name_ar'])) {
                                                                    // Prefer bank_list data
                                                                    $bank_display = ($is_rtl ?? false) ? $loan_details['bank_name_ar'] : $loan_details['bank_name_en'];
                                                                } elseif (!empty($loan_details['bank_name'])) {
                                                                    // Fallback to employee bank_name field
                                                                    $bank_display = $loan_details['bank_name'];
                                                                }
                                                                echo !empty($bank_display) ? htmlspecialchars($bank_display) : '<em class="text-muted">' . __('not_provided') . '</em>';
                                                            ?>
                                                        </span>
                                                    </div>
                                                    <div class="detail-item">
                                                        <span class="label"><?= __('iban') ?? 'IBAN' ?></span>
                                                        <span class="value font-monospace">
                                                            <?= !empty($loan_details['iban']) ? htmlspecialchars($loan_details['iban']) : '<em class="text-muted">' . __('not_provided') . '</em>' ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                            

                                            <?php if (!empty($loan_details['payment_proof_file']) || !empty($loan_details['final_approved_amount'])): ?>
                                            <div class="report-section">
                                                <h5 class="section-title"><i class="fa fa-file-invoice-dollar"></i> <?= __('Payment Proof') ?></h5>
                                                <div class="grid-details">
                                                    <?php if (!empty($loan_details['final_approved_amount'])): ?>
                                                    <div class="detail-item">
                                                        <span class="label text-primary"><?= __('Final Approved Amount') ?>:</span>
                                                        <span class="value highlight text-success"><?= number_format($loan_details['final_approved_amount'], 2); ?> SAR</span>
                                                    </div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($loan_details['payment_proof_file'])): ?>
                                                    <div class="detail-item no-print">
                                                        <span class="label"><?= __('payment_proof', 'Payment proof') ?></span>
                                                        <span class="value"><a href="./assets/loan_payment_proofs/<?= htmlspecialchars($loan_details['payment_proof_file']); ?>" target="_blank" class="rpt-inline-btn"><i class="mdi mdi-download"></i> <?= __('View/Download Payment Proof') ?></a></span>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <?php endif; ?>
<?php
    $report_end = [
        'chain'        => $approval_chain,
        'chain_status' => (string)($loan_details['status'] ?? ''),
        'signatures'   => ['employee_signature', 'hr_manager_signature', 'general_manager_signature'],
    ];
    include dirname(__DIR__) . '/report_close.php';
?>
<?php
    $conDB->close();
}
?>

