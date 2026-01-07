<?php
/**
 * DIAGNOSTIC REPORT: Vacation Deduction Fix Status
 * 
 * This script generates a comprehensive report showing:
 * 1. Whether the fix has been applied
 * 2. If balances are correct
 * 3. If any zero-balance issues exist
 */

date_default_timezone_set('Asia/Riyadh');

$conDB = new mysqli('localhost', 'root', 'admin123', 'almutlak_db');
if ($conDB->connect_error) {
    die("Connection failed: " . $conDB->connect_error);
}

$report = [
    'timestamp' => date('Y-m-d H:i:s'),
    'status' => 'UNKNOWN',
    'issues' => [],
    'statistics' => [],
    'recommendations' => []
];

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║   VACATION DEDUCTION FIX - DIAGNOSTIC REPORT                   ║\n";
echo "║   Generated: " . date('Y-m-d H:i:s') . "                                  ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// Check 1: Look for zero balances (indicator of bug)
echo "CHECK 1: Scanning for Zero Balance Issues...\n";
$sql_zeros = "SELECT COUNT(*) as zero_count FROM emp_vacation_balance 
              WHERE (total_days = 0 OR available_balance = 0)";
$result = $conDB->query($sql_zeros);
$zero_count = $result->fetch_assoc()['zero_count'];

if ($zero_count > 0) {
    echo "❌ FOUND $zero_count records with ZERO balances\n";
    $report['issues'][] = "Zero balance records detected - bug may not be fully resolved";
    $report['status'] = 'CRITICAL';
    
    // Show details of zero balance records
    $sql_zero_details = "SELECT ev.request_inv_no, e.name, evb.total_days, evb.available_balance, ev.vacdays
                         FROM emp_vacation_balance evb
                         JOIN emp_vacation ev ON ev.id = evb.vac_id
                         JOIN employees e ON e.emp_id = ev.emp_id
                         WHERE evb.total_days = 0 OR evb.available_balance = 0
                         LIMIT 10";
    $details = $conDB->query($sql_zero_details);
    echo "\nFirst 10 Zero Balance Records:\n";
    while ($row = $details->fetch_assoc()) {
        echo "  - Request: " . $row['request_inv_no'] . " | Employee: " . $row['name'] . "\n";
        echo "    Applied: " . $row['vacdays'] . " days | total_days: " . $row['total_days'] . " | available: " . $row['available_balance'] . "\n";
    }
} else {
    echo "✅ NO zero balance records found (GOOD SIGN)\n";
}

echo "\n";

// Check 2: Verify calculation consistency
echo "CHECK 2: Verifying Calculation Consistency...\n";
$sql_calc = "SELECT COUNT(*) as inconsistent_count,
             SUM(CASE 
               WHEN (total_days + carryover_days - used_days) != available_balance THEN 1 
               ELSE 0 
             END) as calc_errors
             FROM emp_vacation_balance
             WHERE total_days > 0";
$result = $conDB->query($sql_calc);
$calc_row = $result->fetch_assoc();
$inconsistent = $calc_row['calc_errors'] ?? 0;

if ($inconsistent > 0) {
    echo "❌ FOUND $inconsistent records with calculation mismatches\n";
    echo "   Formula: total_days + carryover - used != available_balance\n";
    $report['issues'][] = "Calculation consistency errors detected";
    if ($report['status'] !== 'CRITICAL') $report['status'] = 'WARNING';
} else {
    echo "✅ All balance calculations are consistent\n";
}

echo "\n";

// Check 3: Check for double deductions
echo "CHECK 3: Checking for Double Deduction Patterns...\n";
$sql_double = "SELECT ev.emp_id, COUNT(ev.id) as vacation_count, SUM(ev.vacdays) as total_applied
               FROM emp_vacation ev
               WHERE ev.current_status IN ('approved', 'completed')
               GROUP BY ev.emp_id
               HAVING vacation_count > 5";
$result = $conDB->query($sql_double);
$high_count = $result->num_rows;

if ($high_count > 0) {
    echo "⚠️  Found " . $high_count . " employees with 5+ approved vacations\n";
    echo "   (Need to verify they're not double-counted)\n";
    
    $result->data_seek(0);
    $sample = $result->fetch_assoc();
    if ($sample) {
        $sample_emp = $sample['emp_id'];
        $sql_verify = "SELECT SUM(ev.vacdays) as total_used, evb.used_days
                       FROM emp_vacation ev
                       LEFT JOIN emp_vacation_balance evb ON ev.id = evb.vac_id
                       WHERE ev.emp_id = ? AND ev.current_status IN ('approved', 'completed')";
        $stmt = $conDB->prepare($sql_verify);
        $stmt->bind_param('i', $sample_emp);
        $stmt->execute();
        $verify = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        echo "   Sample verification for emp_id $sample_emp:\n";
        echo "     Total vacation days applied: " . $verify['total_used'] . "\n";
        echo "     Used days in balance: " . $verify['used_days'] . "\n";
    }
} else {
    echo "✅ No suspicious patterns of double deduction detected\n";
}

echo "\n";

// Check 4: Contract allocation preservation
echo "CHECK 4: Verifying Contract Allocation Preservation...\n";
$sql_contract = "SELECT COUNT(DISTINCT cp.vac_period) as unique_contracts,
                 COUNT(DISTINCT evb.total_days) as unique_total_days,
                 AVG(cp.vac_period) as avg_contract,
                 AVG(evb.total_days) as avg_total_days
                 FROM emp_vacation_balance evb
                 JOIN emp_vacation ev ON ev.id = evb.vac_id
                 JOIN employees e ON e.emp_id = ev.emp_id
                 JOIN contract_period cp ON e.vac_period = cp.id";
$result = $conDB->query($sql_contract);
$contract_data = $result->fetch_assoc();

$contract_avg = round($contract_data['avg_contract'], 2);
$total_days_avg = round($contract_data['avg_total_days'], 2);

if ($contract_avg === $total_days_avg || abs($contract_avg - $total_days_avg) < 1) {
    echo "✅ Contract allocations match total_days values (CORRECT)\n";
    echo "   Average contract: $contract_avg days\n";
    echo "   Average total_days: $total_days_avg days\n";
} else {
    echo "⚠️  Possible mismatch between contract and total_days\n";
    echo "   Average contract: $contract_avg days\n";
    echo "   Average total_days: $total_days_avg days\n";
    echo "   Difference: " . abs($contract_avg - $total_days_avg) . " days\n";
}

echo "\n";

// Check 5: Recent approvals
echo "CHECK 5: Analyzing Recent Approvals (Last 7 days)...\n";
$sql_recent = "SELECT COUNT(*) as recent_approvals,
                SUM(CASE WHEN evb.id IS NULL THEN 1 ELSE 0 END) as missing_balance_records
                FROM emp_vacation ev
                LEFT JOIN emp_vacation_balance evb ON ev.id = evb.vac_id
                WHERE ev.current_status IN ('approved', 'completed')
                AND ev.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
$result = $conDB->query($sql_recent);
$recent = $result->fetch_assoc();

echo "Recent approvals (7 days): " . $recent['recent_approvals'] . "\n";
echo "Missing balance records: " . $recent['missing_balance_records'] . "\n";

if ($recent['missing_balance_records'] > 0) {
    echo "⚠️  Some approved vacations don't have balance records yet\n";
    echo "   (This is normal if HR_PAYROLL hasn't processed them)\n";
} else if ($recent['recent_approvals'] > 0) {
    echo "✅ All recent approvals have corresponding balance records\n";
}

echo "\n";

// Statistics
echo "STATISTICS:\n";
$sql_stats = "SELECT 
              COUNT(DISTINCT ev.emp_id) as total_employees,
              COUNT(ev.id) as total_vacations,
              COUNT(evb.id) as processed_by_hrpayroll,
              AVG(evb.total_days) as avg_total_days,
              AVG(evb.available_balance) as avg_available,
              MIN(evb.available_balance) as min_balance,
              MAX(evb.available_balance) as max_balance
              FROM emp_vacation ev
              LEFT JOIN emp_vacation_balance evb ON ev.id = evb.vac_id
              WHERE ev.current_status IN ('approved', 'completed')";
$result = $conDB->query($sql_stats);
$stats = $result->fetch_assoc();

echo "  Total Employees: " . $stats['total_employees'] . "\n";
echo "  Total Vacations: " . $stats['total_vacations'] . "\n";
echo "  Processed by HR_PAYROLL: " . $stats['processed_by_hrpayroll'] . "\n";
echo "  Average Total Days: " . round($stats['avg_total_days'], 2) . "\n";
echo "  Average Available: " . round($stats['avg_available'], 2) . "\n";
echo "  Min Balance: " . round($stats['min_balance'], 2) . "\n";
echo "  Max Balance: " . round($stats['max_balance'], 2) . "\n";

echo "\n";

// Final Status
echo "╔════════════════════════════════════════════════════════════════╗\n";
if (count($report['issues']) === 0) {
    echo "║ FINAL STATUS: ✅ SYSTEM APPEARS HEALTHY                          ║\n";
    $report['status'] = 'HEALTHY';
} elseif ($report['status'] === 'CRITICAL') {
    echo "║ FINAL STATUS: ❌ CRITICAL ISSUES DETECTED                         ║\n";
} else {
    echo "║ FINAL STATUS: ⚠️  WARNING - REVIEW NEEDED                         ║\n";
}
echo "╚════════════════════════════════════════════════════════════════╝\n";

if (count($report['issues']) > 0) {
    echo "\nISSUES FOUND:\n";
    foreach ($report['issues'] as $issue) {
        echo "  • $issue\n";
    }
    
    echo "\nRECOMMENDATIONS:\n";
    echo "  1. Review vacation balance records with total_days = 0\n";
    echo "  2. Run: php cron_update_vacation_balances.php --force\n";
    echo "  3. Verify calculations with SQL checks in VACATION_DEDUCTION_FIX_COMPLETE.md\n";
    echo "  4. Contact system administrator if issues persist\n";
}

$conDB->close();

// Save report to file
$report_json = json_encode($report, JSON_PRETTY_PRINT);
file_put_contents(__DIR__ . '/cron_logs/deduction_diagnostic_' . date('Y-m-d_Hi') . '.json', $report_json);

echo "\n📋 Report saved to: cron_logs/deduction_diagnostic_" . date('Y-m-d_Hi') . ".json\n";
?>
