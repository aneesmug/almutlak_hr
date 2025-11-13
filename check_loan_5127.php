<?php
require_once __DIR__ . '/includes/db.php';

echo "=== Checking Loan Data for Emp ID 5127 ===\n\n";

// Find the loan
$loan_query = "SELECT * FROM emp_loan WHERE emp_id = '5127' ORDER BY id DESC LIMIT 1";
$loan_result = mysqli_query($conDB, $loan_query);

if ($loan_result && mysqli_num_rows($loan_result) > 0) {
    $loan = mysqli_fetch_assoc($loan_result);
    echo "Loan Found:\n";
    echo "  ID: {$loan['id']}\n";
    echo "  INV_NO: " . ($loan['inv_no'] ?? 'NULL') . "\n";
    echo "  Status: {$loan['status']}\n";
    echo "  Amount: {$loan['loan_amount']}\n";
    echo "  Employee: {$loan['emp_id']}\n\n";
    
    // Check approval chain
    if (!empty($loan['inv_no'])) {
        $chain_query = "SELECT ra.*, e.name as approver_name 
                        FROM request_approvers ra
                        LEFT JOIN employees e ON ra.approver_id = e.emp_id
                        LEFT JOIN admin_login a ON ra.approver_id = a.id_iqama
                        WHERE ra.request_inv_no = '{$loan['inv_no']}'
                        AND ra.request_type_id = (SELECT id FROM approval_request_types WHERE type_name = 'loan_request')
                        ORDER BY ra.approval_level";
        $chain_result = mysqli_query($conDB, $chain_query);
        
        if ($chain_result && mysqli_num_rows($chain_result) > 0) {
            echo "Approval Chain:\n";
            while ($chain = mysqli_fetch_assoc($chain_result)) {
                $approver_name = $chain['approver_name'] ?? 'Unknown';
                echo "  Level {$chain['approval_level']}: Approver {$chain['approver_id']} ({$approver_name}), Status: {$chain['status']}\n";
            }
        } else {
            echo "❌ NO APPROVAL CHAIN FOUND for inv_no: {$loan['inv_no']}\n";
        }
    } else {
        echo "❌ LOAN HAS NULL inv_no - Cannot link to approval chain\n";
    }
} else {
    echo "❌ No loan found for employee 5127\n";
}

echo "\n=== Check Complete ===\n";
?>
