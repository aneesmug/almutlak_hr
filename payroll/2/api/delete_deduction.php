<?php
header('Content-Type: application/json');
require_once("./../../../includes/db.php");

$input = json_decode(file_get_contents('php://input'), true);
$deductionId = $input['deduction_id'] ?? '';
$empId = $input['emp_id'] ?? '';
$month = $input['month'] ?? '';

if (empty($deductionId) || empty($empId) || empty($month)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required parameters.']);
    exit();
}

$pdo = getDbConnection();

try {
    $stmt = $pdo->prepare("DELETE FROM payroll_deductions WHERE id = :id AND emp_id = :emp_id AND month = :month");
    $stmt->execute([
        ':id' => $deductionId,
        ':emp_id' => $empId,
        ':month' => $month
    ]);
    
    echo json_encode(['status' => 'success', 'message' => 'Deduction deleted successfully.']);
} catch (PDOException $e) {
    error_log('Error deleting deduction: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Failed to delete deduction.']);
}
?>