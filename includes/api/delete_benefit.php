<?php
header('Content-Type: application/json');
require_once("./../../includes/db.php");
require_once("./../../includes/session_check.php");

// This endpoint deletes payroll benefit line items and previously had no auth check
// at all. Restrict to system admin / HR Payroll.
if (!(($is_system_admin ?? false) || ($isHR_Payroll ?? false))) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Access denied.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$benefitId = $input['benefit_id'] ?? '';
$empId = $input['emp_id'] ?? '';
$month = $input['month'] ?? '';

if (empty($benefitId) || empty($empId) || empty($month)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required parameters.']);
    exit();
}

$pdo = getDbConnection();

try {
    $stmt = $pdo->prepare("DELETE FROM payroll_benefits WHERE id = :id AND emp_id = :emp_id AND month = :month");
    $stmt->execute([
        ':id' => $benefitId,
        ':emp_id' => $empId,
        ':month' => $month
    ]);
    
    echo json_encode(['status' => 'success', 'message' => 'Benefit deleted successfully.']);
} catch (PDOException $e) {
    error_log('Error deleting benefit: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Failed to delete benefit.']);
}
?>