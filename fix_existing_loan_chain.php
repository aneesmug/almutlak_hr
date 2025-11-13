<?php
/**
 * Fix existing loan request by adding missing approval levels
 * This will add the missing approvers to loan LN-20251111-2534-f1n7
 */

require_once __DIR__ . '/includes/db.php';

$loan_inv_no = 'LN-20251111-2534-f1n7';
$request_type_id = 2; // loan_request

echo "<h2>Fixing Loan Request: {$loan_inv_no}</h2>";

// Check current approval chain
echo "<h3>Current Approval Chain:</h3>";
$check_sql = "SELECT ra.*, al.fullname, al.user_type 
              FROM request_approvers ra 
              LEFT JOIN admin_login al ON ra.approver_id = al.emp_id 
              WHERE ra.request_inv_no = ? AND ra.request_type_id = ?
              ORDER BY ra.approval_level";
$stmt = $conDB->prepare($check_sql);
$stmt->bind_param("si", $loan_inv_no, $request_type_id);
$stmt->execute();
$current_chain = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Level</th><th>Approver ID</th><th>Name</th><th>User Type</th><th>Status</th></tr>";
foreach ($current_chain as $row) {
    echo "<tr>";
    echo "<td>{$row['approval_level']}</td>";
    echo "<td>{$row['approver_id']}</td>";
    echo "<td>{$row['fullname']}</td>";
    echo "<td>{$row['user_type']}</td>";
    echo "<td>{$row['status']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<p><strong>Current levels:</strong> " . count($current_chain) . "</p>";

// Get the missing approvers
echo "<hr><h3>Adding Missing Approval Levels:</h3>";

$missing_approvers = [];
$next_level = 5; // Start from level 5 (after current level 4)

// Level 5: GM
$stmt = $conDB->prepare("SELECT emp_id, fullname FROM admin_login WHERE user_type = 'gm' AND emp_id IS NOT NULL AND status = 1 LIMIT 1");
$stmt->execute();
$gm = $stmt->get_result()->fetch_assoc();
$stmt->close();
if ($gm && !empty($gm['emp_id'])) {
    $missing_approvers[$next_level] = ['emp_id' => $gm['emp_id'], 'name' => $gm['fullname'], 'role' => 'GM'];
    echo "<p>✅ Level {$next_level}: GM - {$gm['fullname']} (emp_id: {$gm['emp_id']})</p>";
    $next_level++;
}

// Level 6: Finance Manager
$stmt = $conDB->prepare("SELECT emp_id, fullname FROM admin_login WHERE user_type = 'finance' AND emp_id IS NOT NULL AND status = 1 LIMIT 1");
$stmt->execute();
$finance_mgr = $stmt->get_result()->fetch_assoc();
$stmt->close();
if ($finance_mgr && !empty($finance_mgr['emp_id'])) {
    $missing_approvers[$next_level] = ['emp_id' => $finance_mgr['emp_id'], 'name' => $finance_mgr['fullname'], 'role' => 'Finance Manager'];
    echo "<p>✅ Level {$next_level}: Finance Manager - {$finance_mgr['fullname']} (emp_id: {$finance_mgr['emp_id']})</p>";
    $next_level++;
}

// Level 7: Finance Officer (Payer)
$stmt = $conDB->prepare("SELECT emp_id, fullname FROM admin_login WHERE user_type = 'finance_officer' AND emp_id IS NOT NULL AND status = 1 LIMIT 1");
$stmt->execute();
$payer = $stmt->get_result()->fetch_assoc();
$stmt->close();
if ($payer && !empty($payer['emp_id'])) {
    $missing_approvers[$next_level] = ['emp_id' => $payer['emp_id'], 'name' => $payer['fullname'], 'role' => 'Finance Officer (Payer)'];
    echo "<p>✅ Level {$next_level}: Finance Officer - {$payer['fullname']} (emp_id: {$payer['emp_id']})</p>";
    $next_level++;
}

// Insert missing approvers
if (!empty($missing_approvers)) {
    echo "<hr><h3>Inserting Missing Approvers...</h3>";
    
    $conDB->begin_transaction();
    try {
        foreach ($missing_approvers as $level => $approver_data) {
            $ins_sql = "INSERT INTO request_approvers (request_inv_no, request_type_id, approver_id, approval_level, status) 
                        VALUES (?, ?, ?, ?, 'awaiting')";
            $stmt = $conDB->prepare($ins_sql);
            $stmt->bind_param("siii", $loan_inv_no, $request_type_id, $approver_data['emp_id'], $level);
            $stmt->execute();
            $stmt->close();
            
            echo "<p>✅ Inserted Level {$level}: {$approver_data['role']} ({$approver_data['name']})</p>";
        }
        
        $conDB->commit();
        echo "<p style='color:green; font-weight:bold;'>✅ SUCCESS! All missing approvers have been added!</p>";
        
    } catch (Exception $e) {
        $conDB->rollback();
        echo "<p style='color:red; font-weight:bold;'>❌ ERROR: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color:orange;'>⚠️ No missing approvers found. Chain might already be complete.</p>";
}

// Show updated chain
echo "<hr><h3>Updated Approval Chain:</h3>";
$stmt = $conDB->prepare($check_sql);
$stmt->bind_param("si", $loan_inv_no, $request_type_id);
$stmt->execute();
$updated_chain = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Level</th><th>Approver ID</th><th>Name</th><th>User Type</th><th>Status</th></tr>";
foreach ($updated_chain as $row) {
    $highlight = ($row['status'] == 'pending') ? 'background-color: yellow;' : '';
    echo "<tr style='{$highlight}'>";
    echo "<td>{$row['approval_level']}</td>";
    echo "<td>{$row['approver_id']}</td>";
    echo "<td>{$row['fullname']}</td>";
    echo "<td>{$row['user_type']}</td>";
    echo "<td><strong>{$row['status']}</strong></td>";
    echo "</tr>";
}
echo "</table>";

echo "<p><strong>Total levels:</strong> " . count($updated_chain) . "</p>";

$conDB->close();
?>
