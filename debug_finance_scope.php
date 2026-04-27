<?php
require_once __DIR__ . '/includes/db.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    die('Database connection is not available.');
}

$empId = 4120;

echo "<h2>Checking Finance Verification Records for EMP ID: " . htmlspecialchars($empId) . "</h2>";

// Check manager assignments
$stmt = $pdo->prepare("SELECT * FROM payroll_finance_verification 
    WHERE finance_manager_emp_id = :emp_id 
    ORDER BY id DESC LIMIT 5");
$stmt->execute([':emp_id' => $empId]);
$managerRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>As Finance Manager:</h3>";
echo "<pre>" . json_encode($managerRecords, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";

// Check officer assignments
$stmt = $pdo->prepare("SELECT * FROM payroll_finance_verification 
    WHERE finance_officer_emp_id = :emp_id 
    ORDER BY id DESC LIMIT 5");
$stmt->execute([':emp_id' => $empId]);
$officerRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>As Finance Officer:</h3>";
echo "<pre>" . json_encode($officerRecords, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";

// Check payroll records in company 3
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM payrolls gp 
    JOIN employees e ON gp.emp_id = e.emp_id
    WHERE e.comp_no = 3 AND gp.month_year = '2026-04'");
$stmt->execute();
$company3Payroll = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<h3>Payroll Records in Company 3 for Month 2026-04:</h3>";
echo "<pre>" . json_encode($company3Payroll, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
?>
