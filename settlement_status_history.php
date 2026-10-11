<?php
require_once __DIR__ . '/includes/init.php';
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
    && !user_has_special_access($conDB, $empid ?? '', 'access_settlement_status_history', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)
) {
    header("Location: ./profile.php");
    exit();
}

// Input
$request_inv_no = isset($_GET['request_inv_no']) ? trim($_GET['request_inv_no']) : '';
if ($request_inv_no === '') {
    srh_die('Settlement request invoice number not provided.');
}

// Settlement details
$settlement = null;
ensureSettlementOverrideSnapshotColumn($conDB);
$sql = "SELECT s.*,
               e.name AS employee_name, e.avatar, e.dept, e.passport_number, e.passport_exp, e.gosi, e.country as country_id, e.allow_vacation_salary_below_min_days,
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
    srh_die('Settlement request not found.');
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
    // Frozen at settlement creation; the employee's current setting no longer applies.
    $allow_vacation_salary_below_min_days = isset($settlement['vac_salary_below_min_override'])
        ? ((string)$settlement['vac_salary_below_min_override'] === '1')
        : ((string)($settlement['allow_vacation_salary_below_min_days'] ?? '0') === '1');
    $vacation_salary_type = resolveVacationSalaryType($fly_type, $approved_days, $vacation_salary_type, $allow_vacation_salary_below_min_days);
    $is_settlement_payable_vacation = isSettlementPayableVacation(
        $vac_type,
        $fly_type,
        $settlement['country_id'] ?? 0,
        $approved_days,
        $settlement['is_deductible'] ?? 0,
        $vacation_salary_type,
        $allow_vacation_salary_below_min_days
    );
    
    $non_payable_leave_types = ['Sick Leave', 'Casual Leave', 'Maternity Leave', 'Compassionate Leave', 'Business Trip', 'Compensatory Leave'];
    $is_non_payable_leave = in_array($vac_type, $non_payable_leave_types);
    
    // Emergency vacations allowed through - working-days-before-departure payout only,
    // never vacation_salary/GOSI (still gated on $is_settlement_payable_vacation,
    // unaffected and still false for Emergency).
    $calculate_payments = !$is_non_payable_leave && ($is_emergency || $is_settlement_payable_vacation);
    
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
            
            // Calculate working days salary for deductible payable vacations, and for
            // Emergency (days actually worked before departure).
            if (($is_settlement_payable_vacation || $is_emergency) && !empty($settlement['start_date'])) {
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
            
            // Calculate total payable. Emergency included (working-days-only payout, no
            // vacation_salary/GOSI - both stay 0 since their gates above are unaffected).
            if ($is_encashment) {
                $payableAmount = 0;
            } elseif ($is_settlement_payable_vacation || $is_emergency) {
                $payableAmount = round(($working_days_salary + $vacation_salary) + $overtime_amount + $other_earnings - $deduction_amount - $gosi_deduction);
            }
        }
    }
}

// Amount stored at settlement creation is final; calculation is only a fallback when it is 0.
if ((float)($settlement['settlement_amount'] ?? 0) > 0 || $payableAmount <= 0) {
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

/// Use getAvatarImagePath function for proper gender-based default avatars
$avatar_path = getAvatarImagePath($settlement['avatar'] ?? '', $settlement['sex'] ?? 1);
$dept_label = ($is_rtl ?? false) ? ($settlement['department_name_ar'] ?? '') : ($settlement['department_name'] ?? '');

// Attachments: plain links (open in a new tab). Single file or JSON array.
$attachments = [];
$attachments_html = '';
if (!empty($settlement['attachment'])) {
    $decoded = json_decode($settlement['attachment'], true);
    $attachments = is_array($decoded) ? $decoded : [$settlement['attachment']];
    foreach ($attachments as $path) {
        $attachments_html .= '<a class="srh-file" href="' . sr_h($path) . '" target="_blank" rel="noopener"><i class="mdi mdi-paperclip"></i> ' . sr_h(basename((string)$path)) . '</a>';
    }
}

$has_deductions = (!empty($settlement['deduction_hours']) && $settlement['deduction_hours'] > 0)
    || (!empty($settlement['deduction_days']) && $settlement['deduction_days'] > 0)
    || (!empty($settlement['other_deductions']) && $settlement['other_deductions'] > 0);

$chain_rows = array_map(function ($c) {
    $st = in_array($c['status'], ['approved', 'rejected'], true) ? $c['status'] : 'pending';
    return ['level' => $c['approval_level'], 'name' => getDisplayName($c['approver_name']), 'role' => $c['user_type'] ?? '', 'status' => $st, 'date' => $c['action_date'], 'note' => ''];
}, $chain);
$history_rows = array_map(function ($e) {
    return ['status' => $e['status'] ?? '', 'by' => getDisplayName($e['emp_name'] ?? ''), 'date' => $e['created_at'] ?? null, 'note' => getDisplayName($e['note'] ?? '')];
}, $history);

srh_page_open(__('settlement_approval_history'));
echo srh_header(__('settlement_approval_history'), __('complete_approval_timeline'), 'all_settlements.php');

echo srh_hero(getDisplayName($settlement['employee_name']), $avatar_path, [
    [__('emp_id'), $settlement['emp_id'], 'mdi-account-card-details'],
    [__('department'), $dept_label, 'mdi-domain'],
    [__('invoice_no'), $settlement['request_inv_no'], 'mdi-barcode'],
], srh_status_pill($settlement['settlement_status']));

echo srh_tiles([
    [__('amount'), srh_money(round($payableAmount)), 'green', sr_h(ucwords(__($settlement['request_type'] ?? 'N/A')))],
    [__('deductions'), $has_deductions ? '-' . srh_money($deduction_amount) : '-', $has_deductions ? 'red' : '', ''],
    [__('created_date'), sr_h(format_safe_date($settlement['created_at'], 'd M Y')), '', ''],
    [__('settlement_date', 'Settlement Date'), sr_h(!empty($settlement['settlement_date']) ? format_safe_date($settlement['settlement_date'], 'd M Y') : '-'), 'indigo', ''],
]);
?>
<div class="srh-grid">
    <div>
        <?= srh_card('mdi-file-document', __('settlement_details'), srh_kv([
            [__('invoice_no'), '<span class="sr-mono">' . sr_h($settlement['request_inv_no']) . '</span>', 'mdi-barcode'],
            [__('amount'), '<strong style="color: var(--tone-green-fg);">' . srh_money(round($payableAmount)) . '</strong>', 'mdi-cash'],
            [__('created_date'), sr_h(format_safe_date($settlement['created_at'], 'd M Y')), 'mdi-calendar'],
            [__('settlement_type'), sr_h(ucwords(__($settlement['request_type'] ?? 'N/A'))), 'mdi-tag'],
            [__('status'), srh_status_pill($settlement['settlement_status']), 'mdi-flag'],
            [__('attachments') . ' (' . count($attachments) . ')', $attachments_html !== '' ? '<div class="srh-files">' . $attachments_html . '</div>' : null, 'mdi-paperclip'],
        ])) ?>
        <?php if (!empty($settlement['notes'])): ?>
            <?= srh_card('mdi-comment-text-outline', __('notes'), '<div class="srh-note">' . sr_h(getDisplayName($settlement['notes'])) . '</div>') ?>
        <?php endif; ?>
    </div>
    <div>
        <?= srh_card('mdi-cash-multiple', __('settlement_information'), srh_kv([
            [__('reason'), !empty($settlement['reason']) ? nl2br(sr_h(getDisplayName($settlement['reason']))) : null, 'mdi-note-text'],
            [__('basic_salary'), !empty($settlement['salary_basic']) ? srh_money($settlement['salary_basic']) : null, 'mdi-cash'],
            [__('overtime_hours'), (!empty($settlement['overtime_hours']) && $settlement['overtime_hours'] > 0) ? number_format((float)$settlement['overtime_hours'], 2) : null, 'mdi-clock'],
            [__('other_earnings'), (!empty($settlement['other_earnings']) && $settlement['other_earnings'] > 0) ? '<span style="color: var(--tone-green-fg);">+' . srh_money($settlement['other_earnings']) . '</span>' : null, 'mdi-plus-circle'],
            [__('deductions'), $has_deductions ? '<span style="color: var(--tone-red-fg);">-' . srh_money($deduction_amount) . '</span>' : null, 'mdi-minus-circle'],
            [__('settlement_amount'), !empty($settlement['settlement_amount']) ? '<strong style="color: var(--tone-green-fg);">' . srh_money(round($payableAmount)) . '</strong>' : null, 'mdi-cash-multiple'],
            [__('deductions'), !empty($settlement['deductions']) ? srh_money($settlement['deductions']) : null, 'mdi-minus-circle'],
            [__('final_amount'), !empty($settlement['final_amount']) ? '<strong style="color: var(--sr-accent-strong);">' . srh_money($settlement['final_amount']) . '</strong>' : null, 'mdi-check-all'],
            [__('settlement_date', 'Settlement Date'), !empty($settlement['settlement_date']) ? sr_h(format_safe_date($settlement['settlement_date'], 'd M Y')) : null, 'mdi-calendar'],
        ])) ?>
        <?= srh_card('mdi-sitemap', __('approval_chain'), srh_chain($chain_rows), count($chain_rows) . ' ' . sr_h(__('levels', 'levels'))) ?>
    </div>
</div>
<?= srh_card('mdi-history', __('status_history_timeline'), srh_timeline($history_rows)) ?>
<?php
srh_page_close();
$conDB->close();
