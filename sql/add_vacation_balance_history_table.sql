-- ============================================================================
-- VACATION BALANCE HISTORY TABLE
-- 
-- Purpose: Track daily vacation balance changes for audit and troubleshooting
-- This table stores snapshots of every vacation balance calculation run
-- 
-- Key Features:
-- - Tracks balance changes per employee per day
-- - Stores all calculation components (earned, used, carryover, available)
-- - Allows detection of calculation mismatches
-- - Supports trend analysis and history review
-- ============================================================================

CREATE TABLE IF NOT EXISTS `emp_vacation_balance_history` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `emp_id` VARCHAR(20) NOT NULL COMMENT 'Employee ID (matches employees.emp_id)',
  `vac_id` INT COMMENT 'Reference to emp_vacation_balance.vac_id',
  `contract_id` INT COMMENT 'Reference to emp_vacation_balance.contract_id',
  `balance_record_id` INT COMMENT 'Reference to emp_vacation_balance.id',
  
  -- Balance values BEFORE update
  `old_available_balance` DECIMAL(5, 2) DEFAULT NULL COMMENT 'Available balance before update',
  `old_used_days` DECIMAL(5, 2) DEFAULT NULL COMMENT 'Used days before update',
  `old_remaining_balance` DECIMAL(5, 2) DEFAULT NULL COMMENT 'Remaining balance before update',
  
  -- Balance values AFTER update (actual/live values)
  `new_available_balance` DECIMAL(5, 2) NOT NULL COMMENT 'Available balance after update',
  `new_used_days` DECIMAL(5, 2) NOT NULL COMMENT 'Used days after update',
  `new_remaining_balance` DECIMAL(5, 2) NOT NULL COMMENT 'Remaining balance after update',
  `carryover_days` DECIMAL(5, 2) DEFAULT NULL COMMENT 'Days carried over from previous period',
  
  -- Period information
  `total_days` DECIMAL(5, 2) DEFAULT NULL COMMENT 'Total days allocated for period',
  `period_start` DATE DEFAULT NULL COMMENT 'Start date of vacation period',
  `period_end` DATE DEFAULT NULL COMMENT 'End date of vacation period',
  
  -- Change tracking
  `balance_changed` BOOLEAN DEFAULT FALSE COMMENT 'True if available_balance changed',
  `change_amount` DECIMAL(5, 2) DEFAULT NULL COMMENT 'Amount of change (new - old)',
  `change_reason` VARCHAR(255) DEFAULT NULL COMMENT 'Reason for change (request ID, manual, refund, etc)',
  
  -- Status and notes
  `calculation_status` ENUM('success', 'warning', 'error', 'manual') DEFAULT 'success' COMMENT 'Status of calculation',
  `notes` TEXT DEFAULT NULL COMMENT 'Any calculation notes or warnings',
  
  -- Metadata
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'When this history record was created',
  `snapshot_date` DATE NOT NULL COMMENT 'Date snapshot was taken (YYYY-MM-DD)',
  `snapshot_time` DATETIME DEFAULT NULL COMMENT 'Exact time snapshot was taken',
  
  -- Indexes for fast queries
  KEY `idx_emp_id` (`emp_id`),
  KEY `idx_snapshot_date` (`snapshot_date`),
  KEY `idx_emp_date` (`emp_id`, `snapshot_date`),
  KEY `idx_balance_changed` (`balance_changed`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_balance_record` (`balance_record_id`)
  
  -- FOREIGN KEY CONSTRAINTS (optional - add if needed)
  -- CONSTRAINT `fk_emp_vacation_balance_history_emp` 
  --   FOREIGN KEY (`emp_id`) 
  --   REFERENCES `employees` (`emp_id`) 
  --   ON DELETE CASCADE
  -- 
  -- CONSTRAINT `fk_emp_vacation_balance_history_balance` 
  --   FOREIGN KEY (`balance_record_id`) 
  --   REFERENCES `emp_vacation_balance` (`id`) 
  --   ON DELETE SET NULL
  
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
  COMMENT='Historical record of vacation balance changes for audit trail and troubleshooting';

-- ============================================================================
-- SAMPLE QUERIES FOR HISTORICAL ANALYSIS
-- ============================================================================

-- Query 1: View employee balance history for specific date range
-- SELECT * FROM emp_vacation_balance_history 
-- WHERE emp_id = '1061' AND update_date BETWEEN '2026-01-01' AND '2026-01-15'
-- ORDER BY update_date DESC;

-- Query 2: Find all days with negative balances (potential errors)
-- SELECT * FROM emp_vacation_balance_history 
-- WHERE new_available_balance < 0 
-- ORDER BY update_date DESC, emp_id;

-- Query 3: Find employees with balance mismatches
-- SELECT emp_id, update_date, old_available_balance, new_available_balance, change_amount
-- FROM emp_vacation_balance_history 
-- WHERE balance_changed = TRUE 
-- ORDER BY update_date DESC 
-- LIMIT 100;

-- Query 4: Daily summary of all changes
-- SELECT 
--   update_date,
--   COUNT(*) AS total_updates,
--   SUM(CASE WHEN balance_changed THEN 1 ELSE 0 END) AS balances_changed,
--   SUM(CASE WHEN calculation_status = 'error' THEN 1 ELSE 0 END) AS errors,
--   AVG(new_available_balance) AS avg_balance
-- FROM emp_vacation_balance_history 
-- WHERE update_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
-- GROUP BY update_date 
-- ORDER BY update_date DESC;

-- Query 5: Track a specific employee's balance trend
-- SELECT 
--   update_date, 
--   old_available_balance, 
--   new_available_balance, 
--   earned_days, 
--   used_days,
--   carryover_days,
--   change_amount,
--   balance_changed
-- FROM emp_vacation_balance_history 
-- WHERE emp_id = '1061'
-- ORDER BY update_date DESC 
-- LIMIT 30;
