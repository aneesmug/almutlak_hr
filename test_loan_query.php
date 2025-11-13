<?php
require_once __DIR__ . '/includes/db.php';

echo "=== Testing Query for Loan Display ===\n\n";

// Get request type ID
$type_query = mysqli_query($conDB, "SELECT `id` FROM `approval_request_types` WHERE `type_name` = 'loan_request' LIMIT 1");
$type_row = mysqli_fetch_assoc($type_query);
$request_type_id = $type_row['id'];

echo "Request Type ID: $request_type_id\n\n";

// Check if inv_no column exists
$has_inv_no = true;

// Run the actual query
$sql = "SELECT 
    l.*, 
    l.inv_no AS request_inv_no,
    e.name as employee_name,
    e.dept,
    ra_pending.approver_id as current_approver_id, 
    ra_pending.approval_level as current_approval_level, 
    COALESCE(approver_emp.name, approver_admin.fullname, approver_admin.username) as current_approver_name,
    l.loan_amount,
    l.monthly_deduction,
    l.start_date,
    l.end_date
FROM emp_loan l 
JOIN employees e ON l.emp_id = e.emp_id
LEFT JOIN request_approvers ra_pending ON ra_pending.request_inv_no = l.inv_no 
    AND ra_pending.request_type_id = ? 
    AND ra_pending.status = 'pending' 
LEFT JOIN employees approver_emp ON ra_pending.approver_id = approver_emp.emp_id 
LEFT JOIN admin_login approver_admin ON ra_pending.approver_id = approver_admin.id_iqama
WHERE l.emp_id = '5127'
ORDER BY l.id DESC
LIMIT 1";

$stmt = mysqli_prepare($conDB, $sql);
mysqli_stmt_bind_param($stmt, "i", $request_type_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && mysqli_num_rows($result) > 0) {
    $loan = mysqli_fetch_assoc($result);
    echo "Query Results:\n";
    echo "  Loan ID: {$loan['id']}\n";
    echo "  INV_NO: {$loan['inv_no']}\n";
    echo "  Status: {$loan['status']}\n";
    echo "  Current Approver ID: " . ($loan['current_approver_id'] ?? 'NULL') . "\n";
    echo "  Current Approval Level: " . ($loan['current_approval_level'] ?? 'NULL') . "\n";
    echo "  Current Approver Name: " . ($loan['current_approver_name'] ?? 'NULL') . "\n";
    echo "  Employee Name: {$loan['employee_name']}\n";
    
    echo "\n✅ Approver information " . (empty($loan['current_approver_name']) ? "NOT FOUND" : "FOUND") . "\n";
} else {
    echo "❌ Query returned no results\n";
    echo "SQL Error: " . mysqli_error($conDB) . "\n";
}

echo "\n=== Test Complete ===\n";
?>
