<?php
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/sr_history_helpers.php';
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
    && !user_has_special_access($conDB, $empid ?? '', 'access_vacation_status_history', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)
) {
    header("Location: ./profile.php");
    exit();
}

// Input
$request_inv_no = isset($_GET['request_inv_no']) ? trim($_GET['request_inv_no']) : '';
if ($request_inv_no === '') {
    srh_die('Vacation request invoice number not provided.');
}

// Vacation details
$vacation = null;
$sql = "SELECT v.*, 
               e.name AS employee_name, e.avatar, e.dept, e.country AS country_id, e.allow_vacation_salary_below_min_days, e.passport_number, e.passport_exp,
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
    srh_die('Vacation request not found.');
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
        srh_die('Access denied: You are not authorized to view this request.');
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

/// Use getAvatarImagePath function for proper gender-based default avatars
$avatar_path = getAvatarImagePath($vacation['avatar'] ?? '', $vacation['sex'] ?? 1);
$dept_label = ($is_rtl ?? false) ? ($vacation['department_name_ar'] ?? '') : ($vacation['department_name'] ?? '');

// Translate fly_type
$fly_type_display = '';
if ($vacation['fly_type'] === 'annual') {
    $fly_type_display = __('annual_vacation');
} elseif ($vacation['fly_type'] === 'emergency') {
    $fly_type_display = __('emergency_vacation');
}
$is_fly_annual = ($vacation['vac_type'] === 'Fly' && $vacation['fly_type'] === 'annual');

// ---- Payment information (same calculation as before) ----
$has_payment_info = ($vacation['vac_type'] == 'Fly') ||
                   ($vacation['vac_type'] == 'Local Vacation' && $vacation['fly_type'] == 'annual') ||
                   !empty($vacation['ticket_pay']) ||
                   !empty($vacation['permit_fee']) ||
                   !empty($vacation['overtime_hours']) ||
                   !empty($vacation['deduction_hours']) ||
                   !empty($vacation['deduction_days']) ||
                   !empty($vacation['other_deductions']) ||
                   !empty($vacation['other_earnings']);
$working_days_salary = 0;
$vacation_salary = 0;
$overtime_amount = 0;
$deduction_amount = 0;
$other_earnings = (float)($vacation['other_earnings'] ?? 0);
if ($has_payment_info) {
    $vacation_salary_type = $vacation['vacation_salary_type'] ?? 'payroll';
    $allow_vacation_salary_below_min_days = ((string)($vacation['allow_vacation_salary_below_min_days'] ?? '0') === '1');
    // Once a settlement exists, use the value frozen at settlement creation.
    $allow_vacation_salary_below_min_days = resolveSettlementVacSalaryOverride($conDB, $vacation['request_inv_no'] ?? '', $allow_vacation_salary_below_min_days);
    $vacation_salary_type = resolveVacationSalaryType($vacation['fly_type'] ?? '', (float)($vacation['vacdays'] ?? 0), $vacation_salary_type, $allow_vacation_salary_below_min_days);
    $is_local_annual_removed_from_payroll = isLocalAnnualRemovedFromPayroll(
        $vacation['vac_type'] ?? '',
        $vacation['fly_type'] ?? '',
        $vacation['country_id'] ?? 0,
        (float)($vacation['vacdays'] ?? 0),
        $vacation['is_deductible'] ?? 0,
        $vacation_salary_type,
        $allow_vacation_salary_below_min_days
    );
    $is_payable_vacation = ($is_fly_annual || $is_local_annual_removed_from_payroll);

    $days_in_month = 30; // Fixed 30 days for all calculations
    $working_days_month_days = 30;
    if (!empty($vacation['start_date'])) {
        $start_ts = strtotime($vacation['start_date']);
        if ($start_ts !== false) {
            $working_days_month_days = (int)date('t', $start_ts);
        }
    }

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

        // Working days salary (days before vacation starts) for payable vacations.
        if ($is_payable_vacation && !empty($vacation['start_date'])) {
            $start_date_obj = new DateTime($vacation['start_date']);
            // Exclude the start day from working-days salary (working days BEFORE departure)
            $working_days = (int)$start_date_obj->format('d') - 1;
            if ($working_days > 0) {
                if ($working_days_month_days > 0 && $working_days >= $working_days_month_days) {
                    $working_days_salary = round($total_monthly_salary, 2);
                } else {
                    $working_days_salary = round(($total_monthly_salary / 30) * $working_days, 2);
                }
            }
        }

        // Vacation salary (approved days) for payable vacations with payroll type.
        if ($is_payable_vacation && $vacation_salary_type === 'payroll') {
            $approved_days = (float)($vacation['vacdays'] ?? 0);
            if ($approved_days > 0) {
                $vacation_salary = $daily_rate * $approved_days;
            }
        }

        // OVERTIME CALCULATION (per EOS file): per-hour overtime rate = (basic/240)/2 + (full/240)
        $overtimeHourlyRate = (($basic_salary / 240) / 2) + ($total_monthly_salary / 240);
        // DEDUCTION CALCULATION (EOS Logic)
        $dailyRateDeduction = $total_monthly_salary / $days_in_month;
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
}

$payment_rows = [];
if ($has_payment_info) {
    if ($vacation['ticket_pay'] > 0) $payment_rows[] = [__('ticket_payment'), srh_money($vacation['ticket_pay']), 'mdi-airplane'];
    if ($vacation['permit_fee'] > 0) $payment_rows[] = [__('permit_fee'), srh_money($vacation['permit_fee']), 'mdi-passport'];
    if ($working_days_salary > 0) $payment_rows[] = [__('working_days_salary'), srh_money($working_days_salary), 'mdi-calendar'];
    if ($vacation_salary > 0) $payment_rows[] = [__('vacation_salary'), srh_money($vacation_salary), 'mdi-white-balance-sunny'];
    if ($other_earnings > 0) $payment_rows[] = [__('other_earnings'), srh_money($other_earnings), 'mdi-cash'];
    if (!empty($vacation['overtime_hours'])) {
        $payment_rows[] = [__('overtime_payment'), srh_money($overtime_amount) . '<small class="sr-cell-sub">' . number_format((float)$vacation['overtime_hours'], 2) . ' hrs</small>', 'mdi-clock'];
    }
    if (!empty($vacation['deduction_hours']) || !empty($vacation['deduction_days']) || !empty($vacation['other_deductions'])) {
        $sub = [];
        if (!empty($vacation['deduction_hours'])) $sub[] = 'Hours: ' . number_format((float)$vacation['deduction_hours'], 2);
        if (!empty($vacation['deduction_days'])) $sub[] = 'Days: ' . number_format((float)$vacation['deduction_days'], 2);
        if (!empty($vacation['other_deductions'])) $sub[] = 'Other: ' . number_format((float)$vacation['other_deductions'], 2);
        $payment_rows[] = [__('deductions'), '<span style="color: var(--tone-red-fg);">-' . srh_money($deduction_amount) . '</span><small class="sr-cell-sub">' . sr_h(implode(' · ', $sub)) . '</small>', 'mdi-minus-circle'];
    }
    if (!empty($vacation['payroll_note'])) $payment_rows[] = [__('note'), nl2br(sr_h(getDisplayName($vacation['payroll_note']))), 'mdi-note-text'];
}

// ---- Attachments: plain links (open in a new tab) ----
$attachments = [];
$attachments_html = '';
if (!empty($vacation['attachment_path'])) {
    $attachments = json_decode($vacation['attachment_path'], true);
    if (!is_array($attachments)) $attachments = [$vacation['attachment_path']];
    foreach ($attachments as $path) {
        $attachments_html .= '<a class="srh-file" href="' . sr_h($path) . '" target="_blank" rel="noopener"><i class="mdi mdi-paperclip"></i> ' . sr_h(basename((string)$path)) . '</a>';
    }
}

// ---- Approval chain (rejected request: hide approvers that never acted) ----
$is_vacation_rejected = ($vacation['current_status'] ?? '') === 'rejected';
$chain_rows = [];
foreach ($chain as $link) {
    if ($is_vacation_rejected && in_array($link['status'], ['pending', 'awaiting'], true)) continue;
    $chain_rows[] = ['level' => $link['approval_level'], 'name' => getDisplayName(parseName($link['approver_name']) ?? 'N/A'), 'role' => getRoleLabel($link['user_type']), 'status' => $link['status'], 'date' => $link['action_date'], 'note' => ''];
}
$history_rows = array_map(function ($e) {
    return ['status' => $e['status'] ?? '', 'by' => getDisplayName($e['emp_name'] ?? ''), 'date' => $e['created_at'] ?? null, 'note' => getDisplayName($e['note'] ?? '')];
}, $history);

$report_btn = '<a href="vacation_report_details.php?id=' . (int)$vacation['id'] . '&emp_id=' . urlencode($vacation['emp_id']) . '" target="_blank" class="sr-btn"><i class="mdi mdi-file-pdf"></i> ' . sr_h(__('view_report')) . '</a>';

srh_page_open(__('vacation_approval_history'));
echo srh_header(__('vacation_approval_history'), __('complete_approval_timeline'), 'all_applied_vac.php', $report_btn);

echo srh_hero(getDisplayName($vacation['employee_name']), $avatar_path, [
    [__('emp_id'), $vacation['emp_id'], 'mdi-account-card-details'],
    [__('department'), $dept_label, 'mdi-domain'],
    [__('invoice_no'), $vacation['request_inv_no'], 'mdi-barcode'],
], srh_status_pill($vacation['current_status']));

echo srh_tiles([
    [__('type'), sr_h(getDisplayName($vacation['vac_type'])), 'indigo', sr_h($fly_type_display)],
    [__('start_date'), sr_h(!empty($vacation['start_date']) ? format_safe_date($vacation['start_date'], 'd M Y') : '-'), '', ''],
    [__('return_date'), sr_h(!empty($vacation['return_date']) ? format_safe_date($vacation['return_date'], 'd M Y') : '-'), '', ''],
    [__('total_days'), (int)$vacation['vacdays'] . ' ' . sr_h(__('days')), 'green', ''],
]);
?>
<div class="srh-grid">
    <div>
        <?= srh_card('mdi-briefcase', __('vacation_details'), srh_kv([
            [__('invoice_no'), '<span class="sr-mono">' . sr_h($vacation['request_inv_no']) . '</span>', 'mdi-barcode'],
            [__('type'), sr_h(getDisplayName($vacation['vac_type'])), 'mdi-tag'],
            [__('fly_type'), $fly_type_display !== '' ? sr_h($fly_type_display) : null, 'mdi-airplane'],
            [__('start_date'), sr_h($vacation['start_date'] ?? 'N/A'), 'mdi-calendar'],
            [__('return_date'), sr_h($vacation['return_date'] ?? 'N/A'), 'mdi-calendar-check'],
            [__('departure_date'), ($is_fly_annual && !empty($vacation['departure_date'])) ? sr_h($vacation['departure_date']) : null, 'mdi-airplane-takeoff'],
            [__('arrival_date'), ($is_fly_annual && !empty($vacation['arrival_date'])) ? sr_h($vacation['arrival_date']) : null, 'mdi-airplane-landing'],
            [__('total_days'), '<strong>' . (int)$vacation['vacdays'] . '</strong> ' . sr_h(__('days')), 'mdi-white-balance-sunny'],
            [__('replacement'), !empty($vacation['replacement_person']) ? sr_h(getDisplayName($vacation['replacement_person'])) : null, 'mdi-account-switch'],
            [__('status'), srh_status_pill($vacation['current_status']), 'mdi-flag'],
            [__('attachments') . ' (' . count($attachments) . ')', $attachments_html !== '' ? '<div class="srh-files">' . $attachments_html . '</div>' : null, 'mdi-paperclip'],
        ])) ?>
        <?php if (!empty($vacation['remarks'])): ?>
            <?= srh_card('mdi-comment-text-outline', __('remarks'), '<div class="srh-note">' . sr_h(getDisplayName($vacation['remarks'])) . '</div>') ?>
        <?php endif; ?>
    </div>
    <div>
        <?php if ($vacation['current_status'] == 'approved' && (isset($vacation['remaining_balance']) || isset($vacation['available_balance']))): ?>
            <?= srh_card('mdi-wallet', __('vacation_balance'), srh_kv([
                [__('remaining'), isset($vacation['remaining_balance']) ? '<strong style="color: var(--tone-green-fg);">' . number_format($vacation['remaining_balance'], 2) . '</strong> ' . sr_h(__('days')) : null, 'mdi-timer-sand'],
                [__('available'), isset($vacation['available_balance']) ? '<strong style="color: var(--sr-accent-strong);">' . number_format($vacation['available_balance'], 2) . '</strong> ' . sr_h(__('days')) : null, 'mdi-check-all'],
            ])) ?>
        <?php endif; ?>
        <?php if ($has_payment_info): ?>
            <?= srh_card('mdi-cash-multiple', __('payment_information'), $payment_rows ? srh_kv($payment_rows) : sr_empty_state(__('no_data_found', 'No data found'))) ?>
        <?php endif; ?>
        <?= srh_card('mdi-sitemap', __('approval_chain'), srh_chain($chain_rows), count($chain_rows) . ' ' . sr_h(__('levels', 'levels'))) ?>
    </div>
</div>
<?= srh_card('mdi-history', __('status_history_timeline'), srh_timeline($history_rows)) ?>
<?php
srh_page_close();
$conDB->close();
