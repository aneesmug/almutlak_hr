<?php
/**
 * ============================================================================
 * APPROVAL COMMENTS TABLE CREATION SCRIPT
 * ============================================================================
 * 
 * This script creates a new table to store approval comments/reviews.
 * Since the system supports chain approvals, each approver can add their own
 * comment at each approval stage.
 * 
 * Structure:
 * - Separate record for each approval comment
 * - Linked to request by inv_no + request_type
 * - Records approver details and timestamp
 * - Stores the actual comment text
 * 
 * ============================================================================
 */

require_once __DIR__ . '/../includes/db.php';

$success_count = 0;
$error_count = 0;
$errors = [];

// SQL to create approval_comments table
$sql_create_table = "
CREATE TABLE IF NOT EXISTS `approval_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_inv_no` varchar(255) NOT NULL COMMENT 'Invoice number of the request',
  `request_type` enum('vacation','loan','smart_request','resignation','rejoin') NOT NULL COMMENT 'Type of request',
  `approval_action` enum('approved','rejected','hold','adjusted') DEFAULT 'approved' COMMENT 'Action taken by approver',
  `approver_emp_id` int(11) NULL COMMENT 'Employee ID of the approver',
  `approver_admin_id` int(11) NULL COMMENT 'Admin/User ID of the approver if not employee',
  `approver_name` varchar(255) NOT NULL COMMENT 'Name of the approver (for reference)',
  `approval_level` int(11) DEFAULT 0 COMMENT 'Approval level in the chain',
  `comment_text` longtext NULL COMMENT 'Approver review/comment',
  `comment_date` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'When comment was added',
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_request` (`request_inv_no`, `request_type`),
  KEY `idx_approver` (`approver_emp_id`, `approver_admin_id`),
  KEY `idx_action` (`approval_action`),
  KEY `idx_date` (`comment_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores approval comments from each approver in the chain';
";

echo "
================================================================
CREATING APPROVAL COMMENTS TABLE
================================================================\n\n";

// Execute table creation
if (mysqli_query($conDB, $sql_create_table)) {
    echo "✅ SUCCESS: Table 'approval_comments' created successfully!\n\n";
    $success_count++;
} else {
    echo "❌ ERROR: Failed to create table\n";
    echo "Error: " . mysqli_error($conDB) . "\n\n";
    $error_count++;
    $errors[] = mysqli_error($conDB);
}

// Verify table structure
echo "================================================================\n";
echo "TABLE STRUCTURE VERIFICATION\n";
echo "================================================================\n\n";

$verify_result = mysqli_query($conDB, "SHOW COLUMNS FROM approval_comments");
if ($verify_result && mysqli_num_rows($verify_result) > 0) {
    echo "Table columns:\n";
    echo str_pad("Field", 25) . " | " . str_pad("Type", 35) . " | Comment\n";
    echo str_repeat("-", 100) . "\n";
    while ($row = mysqli_fetch_assoc($verify_result)) {
        echo str_pad($row['Field'], 25) . " | " 
           . str_pad($row['Type'], 35) . " | " 
           . ($row['Comment'] ?? '') . "\n";
    }
    echo "\n";
} else {
    echo "❌ Could not verify table structure\n\n";
}

// Show sample queries for testing
echo "================================================================\n";
echo "SAMPLE USAGE QUERIES\n";
echo "================================================================\n\n";

echo "1. INSERT a new approval comment:\n";
echo "   INSERT INTO approval_comments 
            (request_inv_no, request_type, approval_action, approver_emp_id, 
             approver_name, approval_level, comment_text)
    VALUES ('REQ-2025-001', 'vacation', 'approved', 123, 'John Manager', 1, 
            'Approved - All documents verified');
\n\n";

echo "2. FETCH all comments for a specific request:\n";
echo "   SELECT * FROM approval_comments 
    WHERE request_inv_no = 'REQ-2025-001' 
    AND request_type = 'vacation' 
    ORDER BY comment_date ASC;
\n\n";

echo "3. FETCH comments by approver:\n";
echo "   SELECT * FROM approval_comments 
    WHERE approver_emp_id = 123 
    ORDER BY comment_date DESC;
\n\n";

echo "4. COUNT total comments for a request:\n";
echo "   SELECT COUNT(*) as total_comments FROM approval_comments 
    WHERE request_inv_no = 'REQ-2025-001';
\n\n";

echo "5. FETCH latest comment (most recent approval action):\n";
echo "   SELECT * FROM approval_comments 
    WHERE request_inv_no = 'REQ-2025-001' 
    AND request_type = 'vacation'
    ORDER BY comment_date DESC LIMIT 1;
\n\n";

echo "6. UPDATE a comment:\n";
echo "   UPDATE approval_comments 
    SET comment_text = 'Updated comment text' 
    WHERE id = 1;
\n\n";

echo "7. DELETE comments for a request (use carefully):\n";
echo "   DELETE FROM approval_comments 
    WHERE request_inv_no = 'REQ-2025-001' 
    AND request_type = 'vacation';
\n\n";

// Summary
echo "================================================================\n";
echo "MIGRATION SUMMARY\n";
echo "================================================================\n";
echo "✅ Successful: " . $success_count . "\n";
echo "❌ Errors: " . $error_count . "\n";

if (count($errors) > 0) {
    echo "\nErrors encountered:\n";
    foreach ($errors as $error) {
        echo "  - " . $error . "\n";
    }
}

echo "\n================================================================\n";
echo "NEXT STEPS:\n";
echo "================================================================\n";
echo "1. Include approval_comment_form.php in your approval pages\n";
echo "2. Call openApprovalCommentForm() when approving\n";
echo "3. Use saveApprovalComment.php AJAX to save comments\n";
echo "4. Use getApprovalComments.php to fetch and display comments\n";
echo "\n";

mysqli_close($conDB);
?>
