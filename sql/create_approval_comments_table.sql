-- ============================================================
-- APPROVAL COMMENTS TABLE
-- Stores comments from all approval actions across request types
-- ============================================================

CREATE TABLE IF NOT EXISTS `approval_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_inv_no` varchar(255) NOT NULL COMMENT 'Invoice number of the request',
  `request_type` enum('vacation_request','loan','smart_request','resignation','rejoin','excuse_leave','general_request','settlement') NOT NULL COMMENT 'Type of request',
  `approval_action` enum('approved','rejected','hold','adjusted') DEFAULT 'approved' COMMENT 'Action taken by approver',
  `approver_emp_id` int(11) DEFAULT NULL COMMENT 'Employee ID of the approver',
  `approver_admin_id` int(11) DEFAULT NULL COMMENT 'Admin/User ID of the approver if not employee',
  `approver_name` varchar(255) NOT NULL COMMENT 'Name of the approver (for reference)',
  `approval_level` int(11) DEFAULT 0 COMMENT 'Approval level in the chain',
  `comment_text` longtext DEFAULT NULL COMMENT 'Approver review/comment',
  `comment_date` datetime DEFAULT current_timestamp() COMMENT 'When comment was added',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_request_inv_no` (`request_inv_no`),
  KEY `idx_request_type` (`request_type`),
  KEY `idx_approver_emp_id` (`approver_emp_id`),
  KEY `idx_approval_action` (`approval_action`),
  KEY `idx_comment_date` (`comment_date`),
  KEY `idx_search` (`request_inv_no`, `request_type`, `approver_emp_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores approval comments from each approver in the chain';

-- ============================================================
-- INDEXES FOR QUICK LOOKUPS
-- ============================================================
-- Get all comments for a request:
-- SELECT * FROM approval_comments WHERE request_inv_no = 'VAC-...' ORDER BY comment_date DESC;

-- Get all approvals by an employee:
-- SELECT * FROM approval_comments WHERE approver_emp_id = 5160 AND approval_action = 'approved';

-- Get rejected requests with comments:
-- SELECT * FROM approval_comments WHERE approval_action = 'rejected' AND request_type = 'settlement';
