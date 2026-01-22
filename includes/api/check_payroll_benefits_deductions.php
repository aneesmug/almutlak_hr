<?php
/**
 * check_payroll_benefits_deductions.php
 * 
 * Checks if there are any existing benefits or deductions for a given month
 * Returns a JSON response indicating the presence of benefits/deductions
 */

header('Content-Type: application/json');
require_once("./../../includes/db.php");

$monthYear = $_GET['month'] ?? null;

if (!$monthYear) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Month parameter is required.',
        'has_benefits' => false,
        'has_deductions' => false,
        'count' => 0
    ]);
    exit;
}

try {
    $pdo = getDbConnection();

    // Check for benefits
    $stmtBenefits = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM payroll_benefits 
        WHERE month = :month_year AND status = 1
    ");
    $stmtBenefits->execute([':month_year' => $monthYear]);
    $benefitCount = $stmtBenefits->fetchColumn();

    // Check for deductions
    $stmtDeductions = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM payroll_deductions 
        WHERE month = :month_year AND status = 1
    ");
    $stmtDeductions->execute([':month_year' => $monthYear]);
    $deductionCount = $stmtDeductions->fetchColumn();

    $hasBenefits = $benefitCount > 0;
    $hasDeductions = $deductionCount > 0;
    $totalCount = $benefitCount + $deductionCount;

    echo json_encode([
        'status' => 'success',
        'has_benefits' => $hasBenefits,
        'has_deductions' => $hasDeductions,
        'benefit_count' => $benefitCount,
        'deduction_count' => $deductionCount,
        'count' => $totalCount
    ]);

} catch (PDOException $e) {
    error_log('Error checking benefits/deductions: ' . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error occurred.',
        'has_benefits' => false,
        'has_deductions' => false,
        'count' => 0
    ]);
} catch (Exception $e) {
    error_log('General error in check_payroll_benefits_deductions.php: ' . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'An unexpected error occurred.',
        'has_benefits' => false,
        'has_deductions' => false,
        'count' => 0
    ]);
}
?>
