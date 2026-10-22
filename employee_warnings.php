<?php
/**
 * Employee Warnings Display Page
 * Shows all warnings/disciplinary records for a specific employee
 */

require_once("./includes/session_check.php");
require_once __DIR__ . '/includes/sr_history_helpers.php';
include('./includes/MainClass.php');
include("./includes/Hijri_GregorianConvert.php");
$DateConv = new Hijri_GregorianConvert;
$format = "YYYY-MM-DD";

// Get employee ID securely from session
// ?emp_id=X opens another employee's records (from their profile) - access-checked in profile_target.php.
require_once __DIR__ . '/includes/profile_target.php';
$emp_id = $profile_target_empid;
if (empty($emp_id)) {
    header("Location: ./profile.php");
    exit();
}
// Fetch employee data
$emp_query = "SELECT * FROM employees WHERE emp_id = ?";
$stmt = $conDB->prepare($emp_query);
$stmt->bind_param("s", $emp_id);
$stmt->execute();
$emprow = $stmt->get_result()->fetch_assoc();

if (!$emprow) {
    die("Employee not found.");
}

// Fetch warnings/disciplinary records from emp_notice table
$warnings_result = null;

try {
    $warnings_query = "SELECT * FROM emp_notice 
                      WHERE emp_id = ? AND is_deleted = 0
                      ORDER BY created_at DESC";
    $stmt = $conDB->prepare($warnings_query);
    $stmt->bind_param("i", $emp_id);
    $stmt->execute();
    $warnings_result = $stmt->get_result();
} catch (Exception $e) {
    // Table doesn't exist or query failed, create empty result
    $warnings_result = null;
}

// Create empty result if query failed
if (!$warnings_result) {
    $warnings_result = new stdClass();
    $warnings_result->num_rows = 0;
}
$notes = [];
if ($warnings_result && is_object($warnings_result) && !($warnings_result instanceof stdClass) && $warnings_result->num_rows > 0) {
    while ($w = $warnings_result->fetch_assoc()) $notes[] = $w;
}
// note_type -> [tone, icon]
$note_style = function ($type) {
    switch ($type) {
        case 'warning': case 'late_arrival': return ['amber', 'mdi-alert'];
        case 'disciplinary_action': case 'violation': case 'absence': return ['red', 'mdi-alert-octagon'];
        case 'appreciation': case 'promotion': return ['green', 'mdi-star-circle'];
        case 'sick_leave': case 'medical_report': return ['sky', 'mdi-hospital'];
        case 'performance_review': case 'training': return ['indigo', 'mdi-school'];
        case 'salary_adjustment': return ['sky', 'mdi-cash'];
        default: return ['slate', 'mdi-information'];
    }
};
$counts = ['negative' => 0, 'positive' => 0, 'other' => 0];
foreach ($notes as $n) {
    $tone = $note_style($n['note_type'] ?? 'general')[0];
    if ($tone === 'red' || $tone === 'amber') $counts['negative']++;
    elseif ($tone === 'green') $counts['positive']++;
    else $counts['other']++;
}

srh_page_open(__('employee_warnings', 'Employee Warnings'), srh_portal_head());
echo srh_header(__('employee_warnings', 'Employee Warnings'), '', srh_profile_url($emprow));
echo srh_emp_hero($emprow);
echo srh_tiles([
    [__('total_records', 'Total Records'), count($notes), 'indigo', ''],
    [__('warnings', 'Warnings'), $counts['negative'], $counts['negative'] ? 'red' : '', ''],
    [__('appreciation', 'Appreciation'), $counts['positive'], 'green', ''],
    [__('other', 'Other'), $counts['other'], '', ''],
]);

if (!$notes) {
    $body = '<div class="sr-notice tone-green" style="margin: 0;"><i class="mdi mdi-check-circle"></i><div>' . sr_h(__('no_warnings_found', 'No warnings or disciplinary records found. Employee has a clean record.')) . '</div></div>';
} else {
    $body = '<ul class="sr-activity srh-steps">';
    foreach ($notes as $w) {
        $type = $w['note_type'] ?? 'general';
        [$tone, $icon] = $note_style($type);
        $body .= '<li><span class="sr-activity-icon tone-' . $tone . '"><i class="mdi ' . $icon . '"></i></span><div class="sr-activity-body">'
            . '<div class="sr-activity-title">' . sr_pill($tone, __($type, str_replace('_', ' ', $type)), true) . '</div>'
            . '<div class="srh-note" style="margin-top: 6px;">' . sr_h($w['note'] ?? 'Notice') . '</div>'
            . '<div class="sr-activity-meta"><span><i class="mdi mdi-calendar"></i> ' . sr_h(format_safe_date($w['created_at'] ?? null, 'd M Y')) . '</span>'
            . '<span>' . sr_h($DateConv->GregorianToHijri($w['created_at'], $format)) . '</span>'
            . (!empty($w['attachment']) ? '<a class="srh-file" href="' . sr_h($w['attachment']) . '" target="_blank" rel="noopener"><i class="mdi mdi-paperclip"></i> ' . sr_h(basename((string)$w['attachment'])) . '</a>' : '')
            . '</div></div></li>';
    }
    $body .= '</ul>';
}
echo srh_card('mdi-alert-circle', __('warnings_and_notices', 'Warnings & Notices'), $body, count($notes) ? count($notes) . ' ' . sr_h(__('records', 'records')) : '');

srh_page_close('<script src="assets/js/employee_profile.js"></script>');
