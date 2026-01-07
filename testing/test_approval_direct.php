<?php
/**
 * Direct test of approval system for loan LN-20260105-3359-mhv0
 * This bypasses the UI and tests the core logic directly
 */

session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/ApprovalChainManager.php';

$inv_no = 'LN-20260105-3359-mhv0';
$approver_emp_id = $_SESSION['empid'] ?? '5456';  // Supervisor's emp_id

echo "<h2>Direct Approval System Test</h2>";
echo "<p><strong>Invoice:</strong> $inv_no</p>";
echo "<p><strong>Approver emp_id (from session):</strong> $approver_emp_id (Type: " . gettype($approver_emp_id) . ")</p>";

echo "<hr>";
echo "<h3>Step 1: Check request_approvers table</h3>";

$stmt = $conDB->prepare("
    SELECT id, approver_id, approval_level, status 
    FROM request_approvers 
    WHERE request_inv_no = ? 
    ORDER BY approval_level ASC
");
$stmt->bind_param("s", $inv_no);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<p style='color: red;'><strong>ERROR:</strong> No approval chain found in request_approvers table!</p>";
} else {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Level</th><th>Approver ID</th><th>Approver ID Type</th><th>Status</th></tr>";
    $first_approver = null;
    while ($row = $result->fetch_assoc()) {
        if ($row['approval_level'] == 1) {
            $first_approver = $row;
        }
        echo "<tr>";
        echo "<td>" . $row['approval_level'] . "</td>";
        echo "<td>" . $row['approver_id'] . "</td>";
        echo "<td>" . gettype($row['approver_id']) . "</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    if ($first_approver) {
        echo "<hr>";
        echo "<h3>Step 2: First Approver Comparison</h3>";
        echo "<p><strong>Approver ID from DB:</strong> {$first_approver['approver_id']} (Type: " . gettype($first_approver['approver_id']) . ")</p>";
        echo "<p><strong>Current User emp_id:</strong> $approver_emp_id (Type: " . gettype($approver_emp_id) . ")</p>";
        
        // Test the comparison
        $db_approver_id = $first_approver['approver_id'];
        $match_loose = ($db_approver_id == $approver_emp_id);
        $match_strict = ($db_approver_id === $approver_emp_id);
        $match_int = ((int)$db_approver_id === (int)$approver_emp_id);
        
        echo "<p><strong>Loose Comparison (==):</strong> " . ($match_loose ? "✅ TRUE" : "❌ FALSE") . "</p>";
        echo "<p><strong>Strict Comparison (===):</strong> " . ($match_strict ? "✅ TRUE" : "❌ FALSE") . "</p>";
        echo "<p><strong>INT Comparison ((int) === (int)):</strong> " . ($match_int ? "✅ TRUE" : "❌ FALSE") . "</p>";
    }
}
$stmt->close();

echo "<hr>";
echo "<h3>Step 3: Test ApprovalChainManager::verifyApprover()</h3>";

try {
    $chainManager = new ApprovalChainManager($conDB, $pdo, new ActivityLogger());
    $result = $chainManager->verifyApprover($inv_no, $approver_emp_id);
    
    echo "<p><strong>Result:</strong> " . json_encode($result) . "</p>";
    
    if ($result['authorized']) {
        echo "<p style='color: green;'><strong>✅ AUTHORIZED</strong></p>";
    } else {
        echo "<p style='color: red;'><strong>❌ NOT AUTHORIZED</strong></p>";
        echo "<p><strong>Message:</strong> " . $result['message'] . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>ERROR:</strong> " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>Step 4: Check error log (last 30 lines)</h3>";
echo "<pre style='background-color: #f0f0f0; padding: 10px; border: 1px solid #ccc; font-size: 12px;'>";
$error_log = shell_exec("tail -n 30 D:\\xampp\\apache\\logs\\error.log 2>&1");
if ($error_log) {
    echo htmlspecialchars($error_log);
} else {
    echo "No error log available or tail command failed";
}
echo "</pre>";

?>
