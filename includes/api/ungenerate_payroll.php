<?php
/**
 * Removes generated (not yet paid) payroll rows for a month, so it can be
 * generated again from scratch. Never touches rows already marked 'paid'.
 * Manually added payroll_benefits/payroll_deductions rows are left untouched
 * (they're keyed by emp_id+month, not tied to the payrolls row) so a later
 * Generate/Re-generate for the same month picks them back up.
 */
header('Content-Type: application/json');

require_once("./../../includes/db.php");
require_once("./../../includes/session_check.php");
require_once("./../../includes/special_access_helper.php");

$canUngeneratePayroll = user_has_special_access($conDB, $empid ?? '', 'ungenerate_payroll', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
if (!$canUngeneratePayroll) {
    echo json_encode(['status' => 'error', 'message' => __('access_denied', 'Access denied')]);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$monthYear = trim((string)($input['month'] ?? ''));

if ($monthYear === '' || !preg_match('/^\d{4}-\d{2}$/', $monthYear)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid month format. Expected YYYY-MM.']);
    exit();
}

$pdo = getDbConnection();

try {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM payrolls WHERE month_year = :month_year AND status = 'generated'");
    $countStmt->execute([':month_year' => $monthYear]);
    $count = (int)$countStmt->fetchColumn();

    if ($count <= 0) {
        echo json_encode(['status' => 'error', 'message' => __('no_generated_payroll_for_selected_month', 'Selected month does not contain generated payroll.')]);
        exit();
    }

    $deleteStmt = $pdo->prepare("DELETE FROM payrolls WHERE month_year = :month_year AND status = 'generated'");
    $deleteStmt->execute([':month_year' => $monthYear]);

    echo json_encode([
        'status' => 'success',
        'message' => sprintf(__('payroll_ungenerated_success', 'Removed generated payroll for %d employee(s). Manually added benefits/deductions were kept.'), $count),
        'removed_count' => $count,
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
