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

// Employees may only open their own trips (checked once the trip is loaded below),
// unless explicitly granted via app_settings -> Special Access.
$employee_own_only = isset($isEmployee) && $isEmployee === true
    && !user_has_special_access($conDB, $empid ?? '', 'access_business_trip_status_history', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);

// Input
$request_inv_no = isset($_GET['request_inv_no']) ? trim($_GET['request_inv_no']) : '';
if ($request_inv_no === '') {
    srh_die('Business trip request invoice number not provided.');
}

// Business trip details
$trip = null;
$sql = "SELECT bt.*, 
               e.name AS employee_name, e.avatar, e.sex, e.dept,
               d.dep_nme AS department_name,
               d.dep_nme_ar AS department_name_ar,
               fc.name_en as from_city_name_en, fc.name_ar as from_city_name_ar,
               tc.name_en as to_city_name_en, tc.name_ar as to_city_name_ar,
               c.name as destination_country_name
        FROM emp_business_trip bt
        JOIN employees e ON bt.emp_id = e.emp_id
        LEFT JOIN department d ON e.dept = d.id
        LEFT JOIN saudi_cities fc ON bt.from_city_id = fc.id
        LEFT JOIN saudi_cities tc ON bt.to_city_id = tc.id
        LEFT JOIN countries c ON bt.destination_country = c.name
        WHERE bt.request_inv_no = ?
        LIMIT 1";
$stmt = $conDB->prepare($sql);
$stmt->bind_param('s', $request_inv_no);
$stmt->execute();
$res = $stmt->get_result();
if ($res && $res->num_rows === 1) {
    $trip = $res->fetch_assoc();
}
$stmt->close();
if (!$trip) {
    srh_die('Business trip request not found.');
}

if ($employee_own_only && (string)$trip['emp_id'] !== (string)($empid ?? '')) {
    header("Location: ./profile.php");
    exit();
}

// Enforce department scoping: Only HR and System Admin can view other departments,
// otherwise allow if the user is part of the approval chain for this request
$canSeeAll = ($is_system_admin ?? false) || ($isHR ?? false);
if (!$canSeeAll) {
    $userDeptId = $user_dept ?? null;
    $sameDept = ($userDeptId !== null) ? ((int)$trip['dept'] === (int)$userDeptId) : false;
    $inChain = false;
    if (!$sameDept) {
        // Check if current user appears in approval chain for this business trip
        if ($chk = $conDB->prepare("SELECT 1 FROM request_approvers ra JOIN approval_request_types t ON t.id = ra.request_type_id AND t.type_name = 'business_trip' WHERE ra.request_inv_no = ? AND ra.approver_id IN (?, {$approver_alt_id}) LIMIT 1")) {
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

// Approval chain
$chain = [];

// Resolve request_type_id for 'business_trip' dynamically
$request_type_id = 0;
if ($typeStmt = $conDB->prepare("SELECT id FROM approval_request_types WHERE type_name = 'business_trip' LIMIT 1")) {
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
                                    e.avatar AS approver_avatar, e.sex AS approver_sex,
                                    (SELECT dep_nme FROM department WHERE id = e.dept) AS approver_dept_name,
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

/// Use getAvatarImagePath function for proper gender-based default avatars
$avatar_path = getAvatarImagePath($trip['avatar'] ?? '', $trip['sex'] ?? 1);
$dept_label = ($is_rtl ?? false) ? ($trip['department_name_ar'] ?? '') : ($trip['department_name'] ?? '');

// Trip destination display
if (strtolower($trip['trip_type'] ?? '') === 'international') {
    $destination_display = (string)($trip['destination_country_name'] ?? $trip['destination_country'] ?? 'N/A');
} else {
    $destination_display = trim((string)($trip['from_city_name_en'] ?? '')) . ' → ' . trim((string)($trip['to_city_name_en'] ?? ''));
}
$trip_days = '';
if (!empty($trip['trip_start_date']) && !empty($trip['trip_end_date'])) {
    $d1 = date_create($trip['trip_start_date']);
    $d2 = date_create($trip['trip_end_date']);
    if ($d1 && $d2) $trip_days = $d1->diff($d2)->days + 1;
}

$history_rows = array_map(function ($e) {
    return ['status' => $e['status'] ?? '', 'by' => getDisplayName($e['emp_name'] ?? ''), 'date' => $e['created_at'] ?? null, 'note' => $e['note'] ?? ''];
}, $history);

$report_btn = '<a href="request_report.php?type=business_trip&id=' . (int)$trip['id'] . '&emp_id=' . urlencode($trip['emp_id']) . '" target="_blank" class="sr-btn"><i class="mdi mdi-file-pdf"></i> ' . sr_h(__('view_report')) . '</a>';

srh_page_open(__('business_trip_approval_history'));
echo srh_header(__('business_trip_approval_history'), __('complete_approval_timeline'), 'all_applied_business_trip.php', $report_btn);

echo srh_hero(getDisplayName($trip['employee_name']), $avatar_path, [
    [__('emp_id'), $trip['emp_id'], 'mdi-account-card-details'],
    [__('department'), $dept_label, 'mdi-domain'],
    [__('request_id'), $trip['request_inv_no'], 'mdi-barcode'],
], srh_status_pill($trip['current_status']));

echo srh_tiles([
    [__('destination'), sr_h(getDisplayName($destination_display)), 'indigo', sr_h(getDisplayName(ucfirst(str_replace('_', ' ', $trip['trip_type'] ?? 'domestic')))), 'mdi-map-marker'],
    [__('start_date'), sr_h(!empty($trip['trip_start_date']) ? format_safe_date($trip['trip_start_date'], 'd M Y') : '-'), '', '', 'mdi-airplane-takeoff'],
    [__('end_date'), sr_h(!empty($trip['trip_end_date']) ? format_safe_date($trip['trip_end_date'], 'd M Y') : '-'), '', '', 'mdi-airplane-landing'],
    [__('days', 'Days'), $trip_days !== '' ? (int)$trip_days : '-', 'green', '', 'mdi-calendar-clock'],
]);
?>
<div class="srh-grid">
    <div>
        <?= srh_card('mdi-airplane', __('trip_details'), srh_kv([
            [__('request_id'), '<span class="sr-mono">' . sr_h($trip['request_inv_no']) . '</span>', 'mdi-barcode'],
            [__('destination'), sr_h(getDisplayName($destination_display)), 'mdi-map-marker'],
            [__('trip_type'), sr_h(getDisplayName(ucfirst(str_replace('_', ' ', $trip['trip_type'] ?? 'domestic')))), 'mdi-tag'],
            [__('transportation_type', 'Transportation'), sr_h(getDisplayName(ucfirst(str_replace('_', ' ', (string)($trip['transportation_type'] ?? ''))))), 'mdi-car'],
            [__('visa_required', 'Visa Required'), isset($trip['visa_required']) && $trip['visa_required'] !== '' ? sr_h(in_array(strtolower((string)$trip['visa_required']), ['1', 'yes', 'true'], true) ? __('yes', 'Yes') : __('no', 'No')) : null, 'mdi-passport'],
            [__('start_date'), sr_h($trip['trip_start_date'] ?? 'N/A'), 'mdi-calendar'],
            [__('end_date'), sr_h($trip['trip_end_date'] ?? 'N/A'), 'mdi-calendar-check'],
        ])) ?>
        <?= srh_card('mdi-note-text', __('purpose'), '<div class="srh-note">' . sr_h(getDisplayName($trip['trip_purpose'] ?? 'N/A')) . '</div>'
            . (!empty($trip['request_notes']) ? '<div class="srh-note" style="margin-top: 10px;">' . sr_h($trip['request_notes']) . '</div>' : '')) ?>
    </div>
    <div>
        <?= srh_card('mdi-file-document', __('request_information'), srh_kv([
            [__('status'), srh_status_pill($trip['current_status']), 'mdi-flag'],
            [__('approval_level'), (int)($trip['current_approval_level'] ?? 0), 'mdi-stairs'],
            [__('submitted_date'), sr_h(isset($trip['created_at']) ? format_safe_date($trip['created_at'], 'd M Y H:i') : 'N/A'), 'mdi-calendar'],
        ])) ?>
        <?= srh_approval_chain($chain, $trip['current_status']) ?>
    </div>
</div>
<?= srh_card('mdi-history', __('status_history_timeline'), srh_timeline($history_rows)) ?>
<?php
srh_page_close();
$conDB->close();
