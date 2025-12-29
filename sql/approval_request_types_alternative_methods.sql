-- Alternative SQL Methods to Fix Error #1005
-- Try these if the main migration still fails

-- ============================================================================
-- METHOD 1: Disable Foreign Key Checks (Most Reliable)
-- ============================================================================

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `approval_request_types`;

CREATE TABLE `approval_request_types` (
  `id` varchar(64) NOT NULL,
  `type_name` varchar(255) NOT NULL,
  `description` longtext,
  `is_default` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

SET FOREIGN_KEY_CHECKS=1;

ALTER TABLE `approval_request_types` 
ADD INDEX `idx_is_default` (`is_default`),
ADD INDEX `idx_is_active` (`is_active`);

INSERT IGNORE INTO `approval_request_types` VALUES
('vacation_request', 'Vacation Request', 'Annual vacation approval', 1, 1, NOW(), NULL),
('excuse_leave', 'Excuse Leave', 'Sick leave approval', 1, 1, NOW(), NULL),
('loan_request', 'Loan Request', 'Loan application approval', 1, 1, NOW(), NULL),
('resignation_request', 'Resignation Request', 'Resignation approval', 1, 1, NOW(), NULL),
('rejoin_request', 'Rejoin Request', 'Rejoin approval', 1, 1, NOW(), NULL);

SELECT * FROM `approval_request_types`;

-- ============================================================================
-- METHOD 2: MyISAM Engine (Avoids InnoDB Issues)
-- ============================================================================
-- Use this if method 1 fails

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `approval_request_types`;

CREATE TABLE `approval_request_types` (
  `id` varchar(64) PRIMARY KEY,
  `type_name` varchar(255) NOT NULL,
  `description` longtext,
  `is_default` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_is_default` (`is_default`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=MyISAM CHARSET=latin1;

SET FOREIGN_KEY_CHECKS=1;

INSERT IGNORE INTO `approval_request_types` VALUES
('vacation_request', 'Vacation Request', 'Annual vacation approval', 1, 1, NOW(), NULL),
('excuse_leave', 'Excuse Leave', 'Sick leave approval', 1, 1, NOW(), NULL),
('loan_request', 'Loan Request', 'Loan application approval', 1, 1, NOW(), NULL),
('resignation_request', 'Resignation Request', 'Resignation approval', 1, 1, NOW(), NULL),
('rejoin_request', 'Rejoin Request', 'Rejoin approval', 1, 1, NOW(), NULL);

VERIFY: SELECT COUNT(*) FROM `approval_request_types`;

-- ============================================================================
-- METHOD 3: Completely Reset Database (Nuclear Option)
-- ============================================================================
-- Use ONLY if other methods fail and you want to completely clean up

SET FOREIGN_KEY_CHECKS=0;

-- Drop everything related
DROP TABLE IF EXISTS `approval_request_types`;

-- Create simple version
CREATE TABLE `approval_request_types` (
  id VARCHAR(64) PRIMARY KEY,
  type_name VARCHAR(255),
  description LONGTEXT,
  is_default TINYINT DEFAULT 0,
  is_active TINYINT DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM CHARSET=latin1;

SET FOREIGN_KEY_CHECKS=1;

-- Insert
INSERT INTO `approval_request_types` VALUES
('vacation_request', 'Vacation Request', 'Annual vacation approval', 1, 1, NOW(), NULL),
('excuse_leave', 'Excuse Leave', 'Sick leave approval', 1, 1, NOW(), NULL),
('loan_request', 'Loan Request', 'Loan approval', 1, 1, NOW(), NULL),
('resignation_request', 'Resignation Request', 'Resignation approval', 1, 1, NOW(), NULL),
('rejoin_request', 'Rejoin Request', 'Rejoin approval', 1, 1, NOW(), NULL);

-- Verify
SELECT * FROM `approval_request_types` ORDER BY id;

-- ============================================================================
-- DIAGNOSTIC QUERIES - Run these if still having issues
-- ============================================================================

-- Check if table exists
SHOW TABLES LIKE 'approval_request_types';

-- Check table structure
DESCRIBE `approval_request_types`;

-- Show table creation syntax
SHOW CREATE TABLE `approval_request_types`;

-- Check for foreign key constraints in database
SELECT * FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
WHERE REFERENCED_TABLE_NAME = 'approval_request_types';

-- Check InnoDB status (if using InnoDB)
-- SHOW ENGINE INNODB STATUS;

-- Check MySQL error log
-- SHOW ERRORS;

-- ============================================================================
-- TROUBLESHOOTING STEPS
-- ============================================================================
/*
1. Copy one method above (1, 2, or 3)
2. Open phpMyAdmin SQL editor
3. Paste the entire method
4. Click "Go"
5. Wait for completion
6. Verify with: SELECT COUNT(*) FROM `approval_request_types`;
7. Should return: 5

If you still get error #1005:
- Try METHOD 2 (MyISAM engine)
- If that fails, try METHOD 3 (Nuclear option)
- If that fails, contact support with full error message

Method 1: Most reliable, handles InnoDB constraints
Method 2: Simpler, uses MyISAM engine
Method 3: Most aggressive, clears everything
*/
