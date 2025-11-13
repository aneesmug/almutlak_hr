<?php
/**
 * Test script: Verify loan invoice number generation
 */

require_once __DIR__ . '/includes/db.php';

/**
 * Generate unique loan invoice number
 * Format: LN-YYYYMMDD-####-XXXX
 */
function test_generate_loan_inv_no($conDB) {
    $max_attempts = 10;
    for ($attempt = 0; $attempt < $max_attempts; $attempt++) {
        // Date part: YYYYMMDD
        $date_part = date('Ymd');
        
        // Sequential part: 4-digit random number
        $seq_part = str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
        
        // Random suffix: 4-character alphanumeric (lowercase)
        $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $suffix = '';
        for ($i = 0; $i < 4; $i++) {
            $suffix .= $chars[rand(0, strlen($chars) - 1)];
        }
        
        // Combine: LN-YYYYMMDD-####-XXXX
        $inv_no = "LN-{$date_part}-{$seq_part}-{$suffix}";
        
        // Check if already exists
        $check_stmt = $conDB->prepare("SELECT id FROM emp_loan WHERE inv_no = ? LIMIT 1");
        $check_stmt->bind_param("s", $inv_no);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $exists = $check_result->num_rows > 0;
        $check_stmt->close();
        
        if (!$exists) {
            return $inv_no;
        }
    }
    
    // Fallback: use timestamp-based unique ID
    return "LN-" . date('Ymd') . "-" . uniqid();
}

echo "=== Testing Loan Invoice Number Generation ===\n\n";

// Test the function
echo "Generating 5 test invoice numbers:\n";
for ($i = 1; $i <= 5; $i++) {
    $inv_no = test_generate_loan_inv_no($conDB);
    echo "{$i}. {$inv_no}\n";
    
    // Verify format
    if (preg_match('/^LN-\d{8}-\d{4}-[a-z0-9]{4}$/', $inv_no)) {
        echo "   ✅ Format valid\n";
    } else {
        echo "   ❌ Format INVALID (might be fallback uniqid format)\n";
    }
    
    // Small delay to ensure uniqueness
    usleep(10000); // 10ms
}

echo "\n=== Test Complete ===\n";
echo "Invoice number format: LN-YYYYMMDD-####-XXXX\n";
echo "  - LN: Prefix for Loan\n";
echo "  - YYYYMMDD: Current date (e.g., " . date('Ymd') . ")\n";
echo "  - ####: Random 4-digit number\n";
echo "  - XXXX: Random 4-character alphanumeric suffix\n";
?>
