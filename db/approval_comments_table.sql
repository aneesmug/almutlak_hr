-- ============================================================================
-- APPROVAL COMMENTS TABLE CREATION
-- ============================================================================
-- 
-- This SQL script creates the approval_comments table which stores
-- approval comments/reviews from each approver in the chain.
-- 
-- Since the system supports chain approvals, each approver can add their own
-- comment at each approval stage.
-- 
-- ============================================================================

-- CREATE the approval_comments table
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

-- ============================================================================
-- VERIFICATION QUERIES
-- ============================================================================

-- Check if table was created
SHOW TABLES LIKE 'approval_comments';

-- View table structure
DESCRIBE approval_comments;

-- Show full column info
SHOW COLUMNS FROM approval_comments;

-- Show table creation statement
SHOW CREATE TABLE approval_comments;

-- ============================================================================
-- SAMPLE DATA INSERTION (for testing)
-- ============================================================================

-- Test data: Vacation approval with comments
-- INSERT INTO approval_comments 
-- (request_inv_no, request_type, approval_action, approver_emp_id, approver_name, approval_level, comment_text)
-- VALUES ('VAC-2025-001', 'vacation', 'approved', 123, 'Ahmed Manager', 1, 'Approved - All documents in order');

-- Test data: Loan rejection with reason
-- INSERT INTO approval_comments 
-- (request_inv_no, request_type, approval_action, approver_emp_id, approver_name, approval_level, comment_text)
-- VALUES ('LOAN-2025-001', 'loan', 'rejected', 456, 'Fatima Finance Manager', 2, 'Insufficient balance in department budget');

-- Test data: Request on hold
-- INSERT INTO approval_comments 
-- (request_inv_no, request_type, approval_action, approver_emp_id, approver_name, approval_level, comment_text)
-- VALUES ('REQ-2025-001', 'smart_request', 'hold', 789, 'Mohammed GM', 3, 'Awaiting budget approval from head office');

-- ============================================================================
-- USEFUL QUERIES
-- ============================================================================

-- 1. Get all comments for a specific request
-- SELECT * FROM approval_comments 
-- WHERE request_inv_no = 'VAC-2025-001' AND request_type = 'vacation'
-- ORDER BY comment_date ASC;

-- 2. Get the latest (most recent) comment for a request
-- SELECT * FROM approval_comments 
-- WHERE request_inv_no = 'VAC-2025-001' AND request_type = 'vacation'
-- ORDER BY comment_date DESC 
-- LIMIT 1;

-- 3. Count comments by approval action
-- SELECT 
--     approval_action,
--     COUNT(*) as count
-- FROM approval_comments
-- WHERE request_inv_no = 'VAC-2025-001'
-- GROUP BY approval_action;

-- 4. Get all comments from a specific approver
-- SELECT * FROM approval_comments
-- WHERE approver_emp_id = 123
-- ORDER BY comment_date DESC;

-- 5. Get comments by action type
-- SELECT * FROM approval_comments
-- WHERE request_type = 'vacation' AND approval_action = 'rejected'
-- ORDER BY comment_date DESC;

-- 6. Find all rejections with reasons
-- SELECT 
--     request_inv_no,
--     request_type,
--     approver_name,
--     comment_text,
--     comment_date
-- FROM approval_comments
-- WHERE approval_action = 'rejected'
-- ORDER BY comment_date DESC;

-- 7. Statistics: Average comments per request
-- SELECT 
--     request_type,
--     AVG(comment_count) as avg_comments,
--     MAX(comment_count) as max_comments,
--     MIN(comment_count) as min_comments
-- FROM (
--     SELECT 
--         request_type,
--         request_inv_no,
--         COUNT(*) as comment_count
--     FROM approval_comments
--     GROUP BY request_type, request_inv_no
-- ) stats
-- GROUP BY request_type;

-- 8. Get approval timeline for a request
-- SELECT 
--     approver_name,
--     approval_action,
--     approval_level,
--     comment_text,
--     comment_date
-- FROM approval_comments
-- WHERE request_inv_no = 'VAC-2025-001'
-- ORDER BY approval_level ASC, comment_date ASC;

-- 9. Update a comment
-- UPDATE approval_comments 
-- SET comment_text = 'Updated comment text' 
-- WHERE id = 1;

-- 10. Delete comments for a specific request
-- DELETE FROM approval_comments 
-- WHERE request_inv_no = 'VAC-2025-001' 
-- AND request_type = 'vacation';

-- ============================================================================
-- BACKUP AND MAINTENANCE
-- ============================================================================

-- Export comments as CSV
-- SELECT * FROM approval_comments 
-- INTO OUTFILE '/tmp/approval_comments_backup.csv'
-- FIELDS TERMINATED BY ',' ENCLOSED BY '"' LINES TERMINATED BY '\n';

-- Count total comments in database
-- SELECT COUNT(*) as total_comments FROM approval_comments;

-- Find large comments (over 1000 characters)
-- SELECT id, request_inv_no, request_type, LENGTH(comment_text) as char_count
-- FROM approval_comments
-- WHERE CHAR_LENGTH(comment_text) > 1000
-- ORDER BY CHAR_LENGTH(comment_text) DESC;

-- Delete old comments (over 1 year old)
-- DELETE FROM approval_comments 
-- WHERE comment_date < DATE_SUB(NOW(), INTERVAL 1 YEAR);

-- Archive comments to backup table
-- CREATE TABLE approval_comments_archive LIKE approval_comments;
-- INSERT INTO approval_comments_archive SELECT * FROM approval_comments WHERE comment_date < DATE_SUB(NOW(), INTERVAL 1 YEAR);
-- DELETE FROM approval_comments WHERE comment_date < DATE_SUB(NOW(), INTERVAL 1 YEAR);

-- ============================================================================
