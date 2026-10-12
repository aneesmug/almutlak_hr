<?php
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/special_access_helper.php';
require_once __DIR__ . '/includes/sr_history_helpers.php';
if (file_exists(__DIR__ . '/includes/functions.php')) {
    require_once __DIR__ . '/includes/functions.php';
}

// Load translations
$current_lang = $_SESSION['lang'] ?? 'en';
load_language($current_lang);

// Input
$request_inv_no = isset($_GET['request_inv_no']) ? trim($_GET['request_inv_no']) : '';
if ($request_inv_no === '') {
    srh_die('Salary increment request invoice number not provided.');
}

// Salary increment details
$req = null;
$sql = "SELECT si.*,
               e.name AS employee_name, e.avatar, e.sex, e.dept,
               d.dep_nme AS department_name,
               d.dep_nme_ar AS department_name_ar,
               sup.name AS submitted_by_name
        FROM emp_salary_increment si
        JOIN employees e ON si.emp_id = e.emp_id
        LEFT JOIN department d ON e.dept = d.id
        LEFT JOIN employees sup ON si.submitted_by = sup.emp_id
        WHERE si.request_inv_no = ?
        LIMIT 1";
$stmt = $conDB->prepare($sql);
$stmt->bind_param('s', $request_inv_no);
$stmt->execute();
$res = $stmt->get_result();
if ($res && $res->num_rows === 1) {
    $req = $res->fetch_assoc();
}
$stmt->close();
if (!$req) {
    srh_die('Salary increment request not found.');
}

// Current salary information (only non-zero components are shown)
$salary_info = null;
$salary_stmt = $conDB->prepare("SELECT basic, housing, transport, food, misc, cashier, fuel, tel, other, guard,
        (basic + housing + transport + food + misc + cashier + fuel + tel + other + guard) AS total_salary
    FROM emp_salary WHERE emp_id = ? AND status = 1 ORDER BY id DESC LIMIT 1");
if ($salary_stmt) {
    $salary_stmt->bind_param('s', $req['emp_id']);
    $salary_stmt->execute();
    $salary_res = $salary_stmt->get_result();
    if ($salary_res && ($salary_row = $salary_res->fetch_assoc())) {
        $salary_info = $salary_row;
    }
    $salary_stmt->close();
}
$salary_components = [
    ['basic_salary', 'Basic Salary', $salary_info['basic'] ?? 0],
    ['housing_allowance', 'Housing Allowance', $salary_info['housing'] ?? 0],
    ['transport_allowance', 'Transport Allowance', $salary_info['transport'] ?? 0],
    ['food_allowance', 'Food Allowance', $salary_info['food'] ?? 0],
    ['miscellaneous_allowance', 'Miscellaneous Allowance', $salary_info['misc'] ?? 0],
    ['cashier_allowance', 'Cashier Allowance', $salary_info['cashier'] ?? 0],
    ['fuel_allowance', 'Fuel Allowance', $salary_info['fuel'] ?? 0],
    ['telephone_allowance', 'Telephone Allowance', $salary_info['tel'] ?? 0],
    ['other_allowance', 'Others', $salary_info['other'] ?? 0],
    ['guard_allowance', 'Guard Allowance', $salary_info['guard'] ?? 0],
];

// Restrict access: Employees cannot view this detailed report page for someone
// else's request, unless explicitly granted via app_settings -> Special Access.
// Viewing their own salary increment history is always allowed.
$isSelfRecord = (string)($req['emp_id'] ?? '') === (string)($empid ?? '');
if (
    isset($isEmployee) && $isEmployee === true && !$isSelfRecord
    && !user_has_special_access($conDB, $empid ?? '', 'access_salary_increment_status_history', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)
) {
    header("Location: ./profile.php");
    exit();
}

// Enforce department scoping: Only HR and System Admin can view other departments,
// otherwise allow if the user is part of the approval chain, the submitting supervisor,
// or the request is about their own record.
$canSeeAll = ($is_system_admin ?? false) || ($isHR ?? false);
if (!$canSeeAll && !$isSelfRecord) {
    $userDeptId = $user_dept ?? null;
    $sameDept = ($userDeptId !== null) ? ((int)$req['dept'] === (int)$userDeptId) : false;
    $isSubmitter = ((string)($req['submitted_by'] ?? '') === (string)$empid);
    $inChain = false;
    if (!$sameDept && !$isSubmitter) {
        if ($chk = $conDB->prepare("SELECT 1 FROM request_approvers ra JOIN approval_request_types t ON t.id = ra.request_type_id AND t.type_name = 'salary_increment' WHERE ra.request_inv_no = ? AND ra.approver_id IN (?, {$approver_alt_id}) LIMIT 1")) {
            $chk->bind_param('si', $request_inv_no, $empid);
            if ($chk->execute()) {
                $resChk = $chk->get_result();
                $inChain = ($resChk && $resChk->num_rows > 0);
            }
            $chk->close();
        }
    }
    if (!$sameDept && !$isSubmitter && !$inChain) {
        http_response_code(403);
        srh_die('Access denied: You are not authorized to view this request.');
    }
}

// Status history - Build from smt_request_status
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

// Resolve request_type_id for 'salary_increment' dynamically
$request_type_id = 0;
if ($typeStmt = $conDB->prepare("SELECT id FROM approval_request_types WHERE type_name = 'salary_increment' LIMIT 1")) {
    $typeStmt->execute();
    $typeRes = $typeStmt->get_result();
    if ($typeRow = $typeRes->fetch_assoc()) {
        $request_type_id = (int)$typeRow['id'];
    }
    $typeStmt->close();
}

if ($request_type_id > 0) {
    $stmt = $conDB->prepare("SELECT ra.approval_level, ra.status, ra.action_date,
                                    COALESCE(e.name, al.fullname, al.username) AS approver_name,
                                    al.user_type, ra.note
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
}

// Use getAvatarImagePath function for proper gender-based default avatars
$avatar_path = getAvatarImagePath($req['avatar'] ?? '', $req['sex'] ?? 1);
$dept_label = ($is_rtl ?? false) ? ($req['department_name_ar'] ?? '') : ($req['department_name'] ?? '');

$gm_name = '';
foreach ($chain as $c) {
    if (strtolower((string)($c['user_type'] ?? '')) === 'gm' && ($c['status'] ?? '') === 'approved') $gm_name = (string)$c['approver_name'];
}

$salary_rows = [];
if ($salary_info) {
    foreach ($salary_components as [$key, $fallback, $value]) {
        if ((float)$value > 0) $salary_rows[] = [__($key, $fallback), srh_money($value)];
    }
}
$salary_html = $salary_info
    ? srh_kv($salary_rows) . '<div class="srh-total"><span>' . sr_h(__('total_salary', 'Total salary')) . '</span>' . srh_money($salary_info['total_salary']) . '</div>'
    : sr_empty_state(__('no_data_found', 'No data found'));

$chain_rows = array_map(function ($c) {
    return ['level' => $c['approval_level'], 'name' => getDisplayName($c['approver_name']), 'role' => $c['user_type'] ?? '', 'status' => $c['status'] ?? 'awaiting', 'date' => $c['action_date'], 'note' => $c['note'] ?? ''];
}, $chain);
$history_rows = array_map(function ($e) {
    return ['status' => $e['status'] ?? '', 'by' => getDisplayName($e['emp_name'] ?? ''), 'date' => $e['created_at'] ?? null, 'note' => $e['note'] ?? ''];
}, $history);

srh_page_open(__('salary_increment_approval_history', 'Salary Increment Approval History'));
echo srh_header(__('salary_increment_approval_history', 'Salary Increment Approval History'), __('complete_approval_timeline'), 'all_applied_salary_increment.php');

echo srh_hero(getDisplayName($req['employee_name']), $avatar_path, [
    [__('emp_id'), $req['emp_id'], 'mdi-account-card-details'],
    [__('department'), $dept_label, 'mdi-domain'],
    [__('request_id'), $req['request_inv_no'], 'mdi-barcode'],
], srh_status_pill($req['current_status']));

echo srh_tiles([
    [__('increment_amount', 'Increment Amount'), srh_money($req['increment_amount']), '', sr_h(__('requested_by_supervisor', 'Requested by supervisor'))],
    [__('approved_amount', 'Approved Amount'), $req['approved_amount'] !== null ? srh_money($req['approved_amount']) : '-', $req['approved_amount'] !== null ? 'green' : '', $gm_name !== '' ? 'GM: ' . sr_h(getDisplayName($gm_name)) : ''],
    [__('increment_effective_date', 'Increment Effective Date'), sr_h(!empty($req['last_increment_date']) ? format_safe_date($req['last_increment_date'], 'd M Y') : '-'), 'indigo', ''],
    [__('evaluation_score', 'Evaluation Score'), $req['evaluation_score'] !== null ? number_format((float)$req['evaluation_score'], 2) : '-', '', ''],
]);
?>
<div class="srh-grid">
    <div>
        <?= srh_card('mdi-file-document', __('request_information'), srh_kv([
            [__('request_id'), '<span class="sr-mono">' . sr_h($req['request_inv_no']) . '</span>', 'mdi-barcode'],
            [__('status'), srh_status_pill($req['current_status']), 'mdi-flag'],
            [__('approval_level'), (int)($req['current_approval_level'] ?? 0), 'mdi-stairs'],
            [__('submitted_by', 'Submitted By'), sr_h(getDisplayName((string)($req['submitted_by_name'] ?? $req['submitted_by']))), 'mdi-account-check'],
            [__('submitted_date'), sr_h(isset($req['created_at']) ? format_safe_date($req['created_at'], 'd M Y H:i') : 'N/A'), 'mdi-calendar'],
        ])) ?>
        <?= srh_card('mdi-note-text', __('reason', 'Reason'), '<div class="srh-note">' . sr_h(getDisplayName($req['reason'] ?? 'N/A')) . '</div>') ?>
    </div>
    <div>
        <?= srh_card('mdi-cash-multiple', __('salary_information', 'Salary Information'), $salary_html) ?>
        <?= srh_card('mdi-sitemap', __('approval_chain'), srh_chain($chain_rows), count($chain_rows) . ' ' . sr_h(__('levels', 'levels'))) ?>
    </div>
</div>
<?= srh_card('mdi-history', __('status_history_timeline'), srh_timeline($history_rows)) ?>
<?php
srh_page_close();
$conDB->close();
