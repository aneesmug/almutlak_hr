<?php
/**
 * COMPREHENSIVE Loan Approval Authorization Diagnostic Tool
 * 
 * This diagnostic script provides a complete view of the loan approval system to identify
 * exactly why supervisors cannot approve loans.
 * 
 * Usage: Access in browser: http://your-domain/almutlak/system/diagnostic_approval_complete.php?inv_no=LN-XXXXX
 * Example: http://localhost/almutlak/system/diagnostic_approval_complete.php?inv_no=LN-20260105-2403-3334
 */

require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/ApprovalChainManager.php';

// Styling
echo "<style>
  body { font-family: 'Courier New', monospace; background: #f5f5f5; padding: 20px; margin: 0; }
  .container { max-width: 1200px; margin: 0 auto; }
  .diagnostic-box { background: white; padding: 20px; margin: 15px 0; border-radius: 5px; border-left: 5px solid #2196F3; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
  .error { border-left-color: #f44336; background-color: #ffebee; }
  .success { border-left-color: #4caf50; background-color: #e8f5e9; }
  .warning { border-left-color: #ff9800; background-color: #fff3e0; }
  h1 { color: #1a1a1a; border-bottom: 3px solid #2196F3; padding-bottom: 10px; }
  h2 { color: #333; margin-top: 0; font-size: 18px; }
  h3 { color: #666; margin-top: 0; font-size: 14px; }
  table { width: 100%; border-collapse: collapse; margin: 10px 0; }
  td, th { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
  th { background-color: #f0f0f0; font-weight: bold; }
  tr:hover { background-color: #f9f9f9; }
  .mismatch { background-color: #ffcccc; color: #c62828; font-weight: bold; }
  .match { background-color: #ccffcc; color: #2e7d32; font-weight: bold; }
  .label { font-weight: bold; color: #555; width: 200px; }
  pre { background-color: #272822; color: #f8f8f2; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px; }
  .code-line { margin: 3px 0; }
  .highlight { background-color: yellow; padding: 2px 5px; }
</style>";

echo "<div class='container'>";
echo "<h1>🔍 Loan Approval Authorization Diagnostic</h1>";

// Get invoice number from GET
$inv_no = isset($_GET['inv_no']) ? mysqli_real_escape_string($conDB, $_GET['inv_no']) : null;

if (!$inv_no) {
    echo "<div class='diagnostic-box error'>";
    echo "<h2>Usage Required</h2>";
    echo "<p>Please provide an invoice number in the URL:</p>";
    echo "<pre>diagnostic_approval_complete.php?inv_no=LN-XXXXX</pre>";
    echo "<p><strong>Example:</strong> diagnostic_approval_complete.php?inv_no=LN-20260105-2403-3334</p>";
    echo "</div>";
    die("</div>");
}

// ==================== 1. LOAN DETAILS ====================
echo "<div class='diagnostic-box'>";
echo "<h2>1️⃣ LOAN DETAILS</h2>";
$loan_stmt = $conDB->prepare("SELECT * FROM emp_loan WHERE inv_no = ? LIMIT 1");
$loan_stmt->bind_param("s", $inv_no);
$loan_stmt->execute();
$loan_result = $loan_stmt->get_result();
$loan = $loan_result->fetch_assoc();
$loan_stmt->close();

if (!$loan) {
    echo "<div class='error'><strong>❌ ERROR: Loan not found for invoice: $inv_no</strong></div>";
    die("</div></div>");
}

echo "<table>";
echo "<tr><td class='label'>Invoice Number</td><td><strong>$inv_no</strong></td></tr>";
echo "<tr><td class='label'>Applicant Employee ID</td><td><strong>{$loan['emp_id']}</strong></td></tr>";
echo "<tr><td class='label'>Loan Status</td><td><strong>{$loan['status']}</strong></td></tr>";
echo "<tr><td class='label'>Loan Amount</td><td><strong>SAR " . number_format($loan['loan_amount'], 2) . "</strong></td></tr>";
echo "<tr><td class='label'>Loan Type</td><td><strong>{$loan['loan_type']}</strong></td></tr>";
echo "</table>";
echo "</div>";

// ==================== 2. EMPLOYEE DETAILS ====================
echo "<div class='diagnostic-box'>";
echo "<h2>2️⃣ LOAN APPLICANT DETAILS</h2>";
$emp_stmt = $conDB->prepare("SELECT emp_id, name, supervisor_id, comp_no FROM employees WHERE emp_id = ? LIMIT 1");
$emp_stmt->bind_param("s", $loan['emp_id']);
$emp_stmt->execute();
$emp_result = $emp_stmt->get_result();
$employee = $emp_result->fetch_assoc();
$emp_stmt->close();

if (!$employee) {
    echo "<div class='error'><strong>❌ ERROR: Employee record not found!</strong></div>";
} else {
    echo "<table>";
    echo "<tr><td class='label'>Employee ID</td><td><strong>{$employee['emp_id']}</strong></td></tr>";
    echo "<tr><td class='label'>Full Name</td><td><strong>{$employee['name']}</strong></td></tr>";
    echo "<tr><td class='label'>Supervisor ID</td><td>";
    if (empty($employee['supervisor_id'])) {
        echo "<span class='mismatch'>❌ NULL/EMPTY - NO SUPERVISOR ASSIGNED!</span>";
    } else {
        echo "<span class='match'>✓ {$employee['supervisor_id']}</span>";
    }
    echo "</td></tr>";
    echo "<tr><td class='label'>Company</td><td><strong>{$employee['comp_no']}</strong></td></tr>";
    echo "</table>";
}
echo "</div>";

// ==================== 3. SUPERVISOR DETAILS ====================
echo "<div class='diagnostic-box'>";
echo "<h2>3️⃣ DIRECT SUPERVISOR DETAILS</h2>";
if (!empty($employee['supervisor_id'])) {
    $sup_stmt = $conDB->prepare("
        SELECT e.emp_id, e.name, e.comp_no, al.id_iqama, al.user_type, al.emp_id as admin_emp_id
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
        echo "<table>";
        echo "<tr><td class='label'>Supervisor ID</td><td><strong>{$supervisor['emp_id']}</strong></td></tr>";
        echo "<tr><td class='label'>Full Name</td><td><strong>{$supervisor['name']}</strong></td></tr>";
        echo "<tr><td class='label'>Company</td><td>";
        if ($supervisor['comp_no'] != $employee['comp_no']) {
            echo "<span class='mismatch'>❌ DIFFERENT! Applicant: {$employee['comp_no']}, Supervisor: {$supervisor['comp_no']}</span>";
        } else {
            echo "<span class='match'>✓ {$supervisor['comp_no']}</span>";
        }
        echo "</td></tr>";
        echo "<tr><td class='label'>User Type</td><td><strong>" . ($supervisor['user_type'] ?? 'NOT SET') . "</strong></td></tr>";
        echo "<tr><td class='label'>Has Admin Login</td><td>";
        if (empty($supervisor['admin_emp_id'])) {
            echo "<span class='mismatch'>❌ NO - CANNOT LOGIN!</span>";
        } else {
            echo "<span class='match'>✓ YES (emp_id: {$supervisor['admin_emp_id']})</span>";
        }
        echo "</td></tr>";
        echo "</table>";
    } else {
        echo "<div class='error'><strong>❌ ERROR: Supervisor record not found for ID {$employee['supervisor_id']}!</strong></div>";
    }
} else {
    echo "<div class='error'><strong>❌ CRITICAL: Employee has NO supervisor assigned! This is why approval fails.</strong></div>";
    echo "<p><strong>SOLUTION:</strong> Go to Employee Management, open this employee, assign a direct supervisor, and save.</p>";
}
echo "</div>";

// ==================== 4. APPROVAL CHAIN ====================
echo "<div class='diagnostic-box'>";
echo "<h2>4️⃣ APPROVAL CHAIN IN DATABASE</h2>";
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
    echo "<div class='error'><strong>❌ NO APPROVAL CHAIN FOUND! The loan was submitted without creating an approval chain.</strong></div>";
} else {
    echo "<p><strong>Total Approval Levels: {$chain_result->num_rows}</strong></p>";
    echo "<table>";
    echo "<tr><th>Level</th><th>Approver ID</th><th>Approver Name</th><th>Status</th><th>Data Type</th></tr>";
    $has_pending = false;
    while ($approver = $chain_result->fetch_assoc()) {
        if (in_array($approver['status'], ['pending', 'awaiting'])) {
            $has_pending = true;
        }
        
        $app_detail_stmt = $conDB->prepare("
            SELECT name, comp_no FROM employees 
            WHERE emp_id = ?
        ");
        $app_detail_stmt->bind_param("s", $approver['approver_id']);
        $app_detail_stmt->execute();
        $app_detail_result = $app_detail_stmt->get_result();
        $app_detail = $app_detail_result->fetch_assoc();
        $app_detail_stmt->close();
        
        $row_class = in_array($approver['status'], ['pending', 'awaiting']) ? 'match' : '';
        echo "<tr class='$row_class'>";
        echo "<td><strong>{$approver['approval_level']}</strong></td>";
        echo "<td><strong>{$approver['approver_id']}</strong></td>";
        echo "<td>" . ($app_detail['name'] ?? '❌ NOT FOUND') . "</td>";
        echo "<td><strong>{$approver['status']}</strong></td>";
        echo "<td>" . gettype($approver['approver_id']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    if (!$has_pending) {
        echo "<div class='warning'><strong>⚠️ WARNING: No pending or awaiting approvals found. All approvals may be complete.</strong></div>";
    }
}
$chain_stmt->close();
echo "</div>";

// ==================== 5. CURRENT USER ====================
echo "<div class='diagnostic-box'>";
echo "<h2>5️⃣ CURRENT USER (SUPERVISOR TRYING TO APPROVE)</h2>";
$current_emp_id = $_SESSION['empid'] ?? 'NOT SET';
$current_user_type = $_SESSION['user_type'] ?? 'NOT SET';

echo "<table>";
echo "<tr><td class='label'>Session emp_id</td><td><strong>$current_emp_id</strong> (type: " . gettype($current_emp_id) . ")</td></tr>";
echo "<tr><td class='label'>User Type</td><td><strong>$current_user_type</strong></td></tr>";
echo "</table>";

if ($current_emp_id !== 'NOT SET') {
    $curr_user_stmt = $conDB->prepare("
        SELECT e.emp_id, e.name, e.comp_no, al.user_type 
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
        echo "<br><table>";
        echo "<tr><td class='label'>Full Name</td><td><strong>{$curr_user['name']}</strong></td></tr>";
        echo "<tr><td class='label'>Database emp_id</td><td><strong>{$curr_user['emp_id']}</strong></td></tr>";
        echo "<tr><td class='label'>Database User Type</td><td><strong>" . ($curr_user['user_type'] ?? 'NOT SET') . "</strong></td></tr>";
        echo "<tr><td class='label'>Company</td><td><strong>{$curr_user['comp_no']}</strong></td></tr>";
        echo "</table>";
    } else {
        echo "<div class='error'><strong>❌ Employee record not found for current user!</strong></div>";
    }
} else {
    echo "<div class='error'><strong>❌ Current user emp_id not set in session!</strong></div>";
}
echo "</div>";

// ==================== 6. VERIFICATION CHECK ====================
echo "<div class='diagnostic-box'>";
echo "<h2>6️⃣ VERIFICATION LOGIC (WHY APPROVAL FAILS)</h2>";

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
    echo "<div class='error'><strong>❌ NO PENDING OR AWAITING APPROVALS FOUND</strong></div>";
} else {
    $approver_int = (int)$next_row['approver_id'];
    $current_int = (int)$current_emp_id;
    
    echo "<p><strong>Next Pending Approver ID:</strong> {$next_row['approver_id']} (type: " . gettype($next_row['approver_id']) . ", as INT: $approver_int)</p>";
    echo "<p><strong>Current User emp_id:</strong> $current_emp_id (type: " . gettype($current_emp_id) . ", as INT: $current_int)</p>";
    
    echo "<p><strong>Comparison:</strong></p>";
    echo "<pre class='code-line'>if ((int)$approver_int !== (int)$current_int) {</pre>";
    echo "<pre class='code-line'>  // Not authorized</pre>";
    echo "<pre class='code-line'>}</pre>";
    
    echo "<hr>";
    echo "<p><strong>Result:</strong> $approver_int !== $current_int = " . (($approver_int !== $current_int) ? 'TRUE' : 'FALSE') . "</p>";
    
    if ($approver_int === $current_int) {
        echo "<div class='success'><h3>✅ SUCCESS: User IS authorized to approve!</h3>";
        echo "<p>This user should be able to approve the loan.</p>";
        echo "</div>";
    } else {
        echo "<div class='error'><h3>❌ FAILURE: User is NOT authorized to approve!</h3>";
        echo "<p><strong>Expected Approver ID:</strong> <span class='highlight'>$approver_int</span></p>";
        echo "<p><strong>Current User ID:</strong> <span class='highlight'>$current_int</span></p>";
        echo "<p><strong>Reason:</strong> The IDs do not match.</p>";
        echo "<p><strong>Why this happens:</strong></p>";
        echo "<ul>";
        echo "<li>❌ Employee has wrong supervisor_id</li>";
        echo "<li>❌ Supervisor is not in the approval chain</li>";
        echo "<li>❌ Employee has no supervisor assigned</li>";
        echo "<li>❌ Current user's emp_id doesn't match database</li>";
        echo "<li>❌ Approval chain wasn't created properly</li>";
        echo "</ul>";
        echo "</div>";
    }
}

echo "</div>";

// ==================== 7. TROUBLESHOOTING ====================
echo "<div class='diagnostic-box warning'>";
echo "<h2>⚠️ TROUBLESHOOTING STEPS</h2>";
echo "<ol>";
echo "<li><strong>Verify Employee Has Supervisor:</strong>";
echo "<pre>SELECT emp_id, name, supervisor_id FROM employees WHERE emp_id = '{$loan['emp_id']}';</pre>";
echo "</li>";
echo "<li><strong>Verify Supervisor Has Admin Access:</strong>";
echo "<pre>SELECT emp_id, user_type FROM admin_login WHERE emp_id = " . (isset($employee['supervisor_id']) ? $employee['supervisor_id'] : 'SUPERVISOR_ID') . ";</pre>";
echo "</li>";
echo "<li><strong>Check Approval Chain:</strong>";
echo "<pre>SELECT * FROM request_approvers WHERE request_inv_no = '$inv_no' ORDER BY approval_level ASC;</pre>";
echo "</li>";
echo "<li><strong>Check Current User Session:</strong>";
echo "<pre>SELECT emp_id, name FROM employees WHERE emp_id = '$current_emp_id';</pre>";
echo "</li>";
echo "</ol>";
echo "</div>";

echo "</div>"; // Close container
?>
