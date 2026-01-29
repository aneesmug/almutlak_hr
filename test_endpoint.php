<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';

echo "=== ENDPOINT TEST ===\n";
echo "Current User empid: " . var_export($empid, true) . "\n";
echo "Type: " . gettype($empid) . "\n";
echo "Session empid: " . var_export($_SESSION['empid'], true) . "\n";

// Test the approval query logic
$settlementInvNo = 'SETL-VAC-20260127135547-5127-e5eb';
$typeId = 8;
$currentUserId = $empid;

echo "\nQuerying for pending approver...\n";
$currentQry = mysqli_query($conDB, "
    SELECT ra.*, r.settlement_status 
    FROM request_approvers ra
    JOIN settlement_records r ON r.request_inv_no = ra.request_inv_no
    WHERE ra.request_inv_no = '$settlementInvNo' 
    AND ra.request_type_id = $typeId
    AND ra.status = 'pending'
    LIMIT 1
");

if ($currentQry && mysqli_num_rows($currentQry) > 0) {
    $current = mysqli_fetch_assoc($currentQry);
    echo "Found pending approver:\n";
    echo json_encode($current, JSON_PRETTY_PRINT) . "\n";
    echo "\nComparison:\n";
    echo "  Approver ID in DB: " . var_export($current['approver_id'], true) . " (type: " . gettype($current['approver_id']) . ")\n";
    echo "  Current User ID: " . var_export($currentUserId, true) . " (type: " . gettype($currentUserId) . ")\n";
    echo "  Comparison result (!=): " . var_export($current['approver_id'] != $currentUserId, true) . "\n";
    echo "  Comparison result (==): " . var_export($current['approver_id'] == $currentUserId, true) . "\n";
    echo "  Strict comparison (===): " . var_export($current['approver_id'] === $currentUserId, true) . "\n";
} else {
    echo "No pending approver found\n";
    if (!$currentQry) {
        echo "Query error: " . mysqli_error($conDB) . "\n";
    }
}
?>
