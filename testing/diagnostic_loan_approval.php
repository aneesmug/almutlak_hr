<?php
/**
 * Diagnostic script to debug loan approval authorization issues
 * Usage: Access directly in browser: /diagnostic_loan_approval.php?inv_no=LN-XXXXX&emp_id=XXXX
 */

require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/ApprovalChainManager.php';

// Allow GET params for diagnostic purposes
$inv_no = isset($_GET['inv_no']) ? mysqli_real_escape_string($conDB, $_GET['inv_no']) : null;
$emp_id = isset($_GET['emp_id']) ? mysqli_real_escape_string($conDB, $_GET['emp_id']) : null;

if (!$inv_no || !$emp_id) {
    die("Usage: diagnostic_loan_approval.php?inv_no=LN-XXXXX&emp_id=XXXX");
}

echo "<h2>Loan Approval Diagnostic Report</h2>";
echo "<pre>";

// 1. Get loan details
echo "=== 1. LOAN DETAILS ===\n";
$loan_stmt = $conDB->prepare("SELECT * FROM emp_loan WHERE inv_no = ? LIMIT 1");
$loan_stmt->bind_param("s", $inv_no);
$loan_stmt->execute();
$loan_result = $loan_stmt->get_result();
$loan = $loan_result->fetch_assoc();
$loan_stmt->close();

if (!$loan) {
    die("Loan not found: $inv_no\n");
}

echo "Invoice: $inv_no\n";
echo "Employee ID: {$loan['emp_id']}\n";
echo "Status: {$loan['status']}\n";
echo "Amount: {$loan['loan_amount']}\n";
echo "\n";

// 2. Get employee details
echo "=== 2. LOAN APPLICANT (EMPLOYEE) DETAILS ===\n";
$emp_stmt = $conDB->prepare("SELECT emp_id, name, supervisor_id FROM employees WHERE emp_id = ? LIMIT 1");
$emp_stmt->bind_param("s", $loan['emp_id']);
$emp_stmt->execute();
$emp_result = $emp_stmt->get_result();
$employee = $emp_result->fetch_assoc();
$emp_stmt->close();

if ($employee) {
    echo "Employee ID: {$employee['emp_id']}\n";
    echo "Name: {$employee['name']}\n";
    echo "Supervisor ID: {$employee['supervisor_id']}\n";
    echo "\n";
} else {
    echo "ERROR: Employee not found!\n\n";
}

// 3. Get supervisor details
echo "=== 3. DIRECT SUPERVISOR DETAILS ===\n";
if (!empty($employee['supervisor_id'])) {
    $sup_stmt = $conDB->prepare("
        SELECT e.emp_id, e.name, al.id_iqama, al.user_type, al.emp_id as admin_emp_id
        FROM employees e
        LEFT JOIN admin_login al ON e.emp_id = al.emp_id
        WHERE e.emp_id = ? LIMIT 1
    ");
    $sup_stmt->bind_param("s", $employee['supervisor_id']);
    $sup_stmt->execute();
    $sup_result = $sup_stmt->get_result();
    $supervisor = $sup_result->fetch_assoc();
    $sup_stmt->close();
    
    if ($supervisor) {
        echo "Supervisor ID: {$supervisor['emp_id']}\n";
        echo "Name: {$supervisor['name']}\n";
        echo "User Type: {$supervisor['user_type']}\n";
        echo "Has Admin Login: " . (!empty($supervisor['admin_emp_id']) ? 'YES' : 'NO') . "\n";
        echo "\n";
    } else {
        echo "ERROR: Supervisor record not found for ID {$employee['supervisor_id']}!\n\n";
    }
} else {
    echo "ERROR: Employee has NO supervisor assigned!\n\n";
}

// 4. Get approval chain from request_approvers
echo "=== 4. APPROVAL CHAIN IN DATABASE ===\n";
$chain_stmt = $conDB->prepare("
    SELECT id, approver_id, approval_level, status 
    FROM request_approvers 
    WHERE request_inv_no = ? 
    ORDER BY approval_level ASC
");
$chain_stmt->bind_param("s", $inv_no);
$chain_stmt->execute();
$chain_result = $chain_stmt->get_result();

if ($chain_result->num_rows === 0) {
    echo "NO APPROVAL CHAIN FOUND!\n\n";
} else {
    echo "Total Levels: {$chain_result->num_rows}\n";
    while ($approver = $chain_result->fetch_assoc()) {
        echo "\nLevel {$approver['approval_level']}:\n";
        echo "  Approver ID: {$approver['approver_id']}\n";
        echo "  Status: {$approver['status']}\n";
        
        // Get approver details
        $app_detail_stmt = $conDB->prepare("
            SELECT name, user_type FROM employees e
            LEFT JOIN admin_login al ON e.emp_id = al.emp_id
            WHERE e.emp_id = ?
        ");
        $app_detail_stmt->bind_param("s", $approver['approver_id']);
        $app_detail_stmt->execute();
        $app_detail_result = $app_detail_stmt->get_result();
        $app_detail = $app_detail_result->fetch_assoc();
        $app_detail_stmt->close();
        
        if ($app_detail) {
            echo "  Name: {$app_detail['name']}\n";
            echo "  User Type: {$app_detail['user_type']}\n";
        }
    }
    echo "\n";
}
$chain_stmt->close();

// 5. Current user trying to approve
echo "=== 5. CURRENT USER ATTEMPTING APPROVAL ===\n";
$current_emp_id = $_SESSION['empid'] ?? 'NOT SET';
echo "Current User Emp ID (from session): $current_emp_id\n";

if ($current_emp_id !== 'NOT SET') {
    $curr_user_stmt = $conDB->prepare("
        SELECT e.emp_id, e.name, al.user_type 
        FROM employees e
        LEFT JOIN admin_login al ON e.emp_id = al.emp_id
        WHERE e.emp_id = ?
    ");
    $curr_user_stmt->bind_param("s", $current_emp_id);
    $curr_user_stmt->execute();
    $curr_user_result = $curr_user_stmt->get_result();
    $curr_user = $curr_user_result->fetch_assoc();
    $curr_user_stmt->close();
    
    if ($curr_user) {
        echo "Name: {$curr_user['name']}\n";
        echo "User Type: {$curr_user['user_type']}\n";
    }
}
echo "\n";

// 6. Verification check
echo "=== 6. VERIFICATION LOGIC ===\n";

// Get next pending approver
$next_stmt = $conDB->prepare("
    SELECT approver_id, approval_level 
    FROM request_approvers 
    WHERE request_inv_no = ? AND status IN ('pending', 'awaiting')
    ORDER BY approval_level ASC
    LIMIT 1
");
$next_stmt->bind_param("s", $inv_no);
$next_stmt->execute();
$next_result = $next_stmt->get_result();
$next_row = $next_result->fetch_assoc();
$next_stmt->close();

if (!$next_row) {
    echo "NO PENDING OR AWAITING APPROVALS FOUND\n";
} else {
    echo "Next Pending Approver ID: {$next_row['approver_id']}\n";
    echo "Approval Level: {$next_row['approval_level']}\n";
    echo "Current User Emp ID: $current_emp_id\n";
    
    if ((string)$next_row['approver_id'] === (string)$current_emp_id) {
        echo "\n✓ AUTHORIZED: Current user is the next approver!\n";
    } else {
        echo "\n✗ NOT AUTHORIZED: Current user is NOT the next approver\n";
        echo "   Expected approver ID: {$next_row['approver_id']}\n";
        echo "   Current user ID: $current_emp_id\n";
    }
}

echo "\n</pre>";

// Summary
echo "<h3>SUMMARY</h3>";
echo "<ul>";
echo "<li>Loan: $inv_no</li>";
echo "<li>Employee: " . ($employee['name'] ?? 'NOT FOUND') . " ({$employee['emp_id']})</li>";
echo "<li>Supervisor: " . ($supervisor['name'] ?? 'NOT FOUND') . " ({$employee['supervisor_id']})</li>";
echo "<li>Current User: " . ($curr_user['name'] ?? 'NOT SET') . " ($current_emp_id)</li>";
echo "<li>Can Approve: " . (($next_row && (string)$next_row['approver_id'] === (string)$current_emp_id) ? 'YES ✓' : 'NO ✗') . "</li>";
echo "</ul>";

?>
