<?php
// Test pending loan restriction feature
include('includes/db.php');

echo "<h2>Testing Pending Loan Check</h2>";

// Check for employees with pending loans
$result = $conDB->query("SELECT emp_id, COUNT(*) as cnt FROM emp_loan WHERE status IN ('pending', 'awaiting') GROUP BY emp_id LIMIT 5");
echo "<h3>Employees with Pending/Awaiting Loans:</h3>";
$found_pending = false;
while($row = $result->fetch_assoc()) {
    $found_pending = true;
    echo "Employee ID: " . $row['emp_id'] . " has " . $row['cnt'] . " pending loans<br>";
    
    // Get details of the pending loan
    $detail = $conDB->query("SELECT inv_no, loan_type, loan_amount, status, created_at FROM emp_loan WHERE emp_id = '" . $row['emp_id'] . "' AND status IN ('pending', 'awaiting') LIMIT 1");
    if($d = $detail->fetch_assoc()) {
        echo "&nbsp;&nbsp;- Invoice: " . $d['inv_no'] . "<br>";
        echo "&nbsp;&nbsp;- Type: " . $d['loan_type'] . "<br>";
        echo "&nbsp;&nbsp;- Amount: " . $d['loan_amount'] . "<br>";
        echo "&nbsp;&nbsp;- Created: " . $d['created_at'] . "<br>";
    }
    echo "<hr>";
}

if(!$found_pending) {
    echo "<p style='color: blue;'>No pending/awaiting loans found in database</p>";
}

echo "<h3>Test Scenario:</h3>";
echo "<p>When an employee with a pending loan tries to apply for another loan:</p>";
echo "<ul>";
echo "<li>The system will check the emp_loan table for status IN ('pending', 'awaiting')</li>";
echo "<li>If found, it fetches the approval chain from request_approvers table</li>";
echo "<li>Shows SweetAlert2 modal with current approval status</li>";
echo "<li>Displays 'Cannot apply now' message</li>";
echo "</ul>";

echo "<h3>Approval Chain Query Example:</h3>";
echo "<pre>SELECT ra.approval_level, ra.status, 
       COALESCE(e.name, al.fullname, al.username) as approver_name
FROM request_approvers ra
LEFT JOIN employees e ON ra.approver_id = e.emp_id
LEFT JOIN admin_login al ON ra.approver_id = al.id_iqama
WHERE ra.request_inv_no = ? AND ra.request_type_id = 2
ORDER BY ra.approval_level ASC</pre>";

echo "<h3>Code Changes Made:</h3>";
echo "<ul>";
echo "<li>✅ Added pending/awaiting check to ajaxLoan.php apply_for_loan() function</li>";
echo "<li>✅ Updated loanHandling.js to handle 'pending_request' type response</li>";
echo "<li>✅ Created SweetAlert2 modal display with approval chain</li>";
echo "<li>✅ Shows pending approver name and request details</li>";
echo "</ul>";
?>
