<?php
/**
 * Test Status History for Loan Requests
 * Shows all status history entries in smt_request_status table for loan requests
 */

require_once __DIR__ . '/includes/db.php';

echo "<h2>Loan Request Status History in smt_request_status</h2>";

// Get all loan invoice numbers
$loan_sql = "SELECT inv_no, emp_id, loan_type, loan_amount, status, created_at 
             FROM emp_loan 
             WHERE inv_no IS NOT NULL 
             ORDER BY created_at DESC 
             LIMIT 10";
$loans = mysqli_query($conDB, $loan_sql);

echo "<h3>Recent Loan Requests (Last 10)</h3>";
echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #f0f0f0;'><th>Invoice No</th><th>Emp ID</th><th>Loan Type</th><th>Amount</th><th>Status</th><th>Created</th><th>History Count</th></tr>";

while ($loan = mysqli_fetch_assoc($loans)) {
    // Count history entries for this loan
    $count_sql = "SELECT COUNT(*) as count FROM smt_request_status WHERE inv_no = ?";
    $stmt = $conDB->prepare($count_sql);
    $stmt->bind_param("s", $loan['inv_no']);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc()['count'];
    $stmt->close();
    
    $count_color = ($count > 0) ? 'green' : 'red';
    
    echo "<tr>";
    echo "<td><code>{$loan['inv_no']}</code></td>";
    echo "<td>{$loan['emp_id']}</td>";
    echo "<td>{$loan['loan_type']}</td>";
    echo "<td>SAR " . number_format($loan['loan_amount'], 2) . "</td>";
    echo "<td><strong>{$loan['status']}</strong></td>";
    echo "<td>{$loan['created_at']}</td>";
    echo "<td style='color: {$count_color}; font-weight: bold;'>{$count} entries</td>";
    echo "</tr>";
}

echo "</table>";

echo "<hr>";

// Show detailed history for each loan
echo "<h3>Detailed Status History</h3>";

$loans = mysqli_query($conDB, $loan_sql); // Re-query
while ($loan = mysqli_fetch_assoc($loans)) {
    echo "<h4>Loan: {$loan['inv_no']} (Emp: {$loan['emp_id']}, Type: {$loan['loan_type']}, Amount: SAR " . number_format($loan['loan_amount'], 2) . ")</h4>";
    
    $history_sql = "SELECT * FROM smt_request_status WHERE inv_no = ? ORDER BY created_at ASC";
    $stmt = $conDB->prepare($history_sql);
    $stmt->bind_param("s", $loan['inv_no']);
    $stmt->execute();
    $history = $stmt->get_result();
    $stmt->close();
    
    if (mysqli_num_rows($history) > 0) {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>";
        echo "<tr style='background: #e0e0e0;'><th>#</th><th>Employee ID</th><th>Employee Name</th><th>Note</th><th>Status</th><th>Created At</th></tr>";
        
        $row_num = 1;
        while ($row = mysqli_fetch_assoc($history)) {
            $status_color = 'black';
            if (strpos($row['status'], 'approved') !== false) $status_color = 'green';
            elseif (strpos($row['status'], 'rejected') !== false) $status_color = 'red';
            elseif (strpos($row['status'], 'pending') !== false) $status_color = 'orange';
            
            echo "<tr>";
            echo "<td>{$row_num}</td>";
            echo "<td>{$row['emp_id']}</td>";
            echo "<td>{$row['emp_name']}</td>";
            echo "<td>{$row['note']}</td>";
            echo "<td style='color: {$status_color}; font-weight: bold;'>{$row['status']}</td>";
            echo "<td>{$row['created_at']}</td>";
            echo "</tr>";
            
            $row_num++;
        }
        
        echo "</table>";
    } else {
        echo "<p style='color: red;'><strong>⚠️ No status history found for this loan!</strong></p>";
        echo "<p>This loan was created before status history tracking was implemented, or there was an error.</p>";
        echo "<hr style='margin: 20px 0;'>";
    }
}

echo "<hr>";
echo "<h3>Summary</h3>";

// Count total status entries
$total_sql = "SELECT COUNT(*) as total FROM smt_request_status WHERE inv_no LIKE 'LN-%'";
$total = mysqli_query($conDB, $total_sql);
$total_count = mysqli_fetch_assoc($total)['total'];

echo "<p><strong>Total loan status history entries:</strong> {$total_count}</p>";

if ($total_count > 0) {
    echo "<p style='color: green; font-weight: bold;'>✅ Status history is being tracked!</p>";
} else {
    echo "<p style='color: orange; font-weight: bold;'>⚠️ No status history found yet. Create a new loan request to test.</p>";
}

echo "<hr>";
echo "<h3>Test Instructions</h3>";
echo "<ol>";
echo "<li>Create a new loan request</li>";
echo "<li>Approve it at level 1</li>";
echo "<li>Approve it at level 2</li>";
echo "<li>Refresh this page to see the status history</li>";
echo "<li>Or visit: <a href='loan_status_history.php?inv_no=LN-XXXXXX' target='_blank'>loan_status_history.php?inv_no=LN-XXXXXX</a></li>";
echo "</ol>";

$conDB->close();
?>
