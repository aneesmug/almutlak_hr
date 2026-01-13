-- ============================================================================
-- VACATION BALANCE HISTORY - IMPLEMENTATION GUIDE
-- ============================================================================

-- STEP 1: Create the history table
-- Run this SQL in your database

CREATE TABLE IF NOT EXISTS `emp_vacation_balance_history` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `emp_id` VARCHAR(50) NOT NULL COMMENT 'Employee ID',
  `update_date` DATE NOT NULL COMMENT 'Date the update was performed (YYYY-MM-DD)',
  `balance_record_id` INT COMMENT 'Reference to emp_vacation_balance.id',
  
  -- Balance values BEFORE update
  `old_available_balance` DECIMAL(10, 2) DEFAULT NULL COMMENT 'Available balance before update',
  `old_used_days` DECIMAL(10, 2) DEFAULT NULL COMMENT 'Used days before update',
  `old_remaining_balance` DECIMAL(10, 2) DEFAULT NULL COMMENT 'Remaining balance before update',
  
  -- Balance values AFTER update (calculated/live values)
  `new_available_balance` DECIMAL(10, 2) NOT NULL COMMENT 'Available balance after update',
  `earned_days` DECIMAL(10, 2) DEFAULT NULL COMMENT 'Days earned in period (calculated)',
  `used_days` DECIMAL(10, 2) DEFAULT NULL COMMENT 'Days used/deducted (calculated)',
  `carryover_days` DECIMAL(10, 2) DEFAULT NULL COMMENT 'Days carried over from previous period',
  
  -- Period information
  `total_days` DECIMAL(10, 2) DEFAULT NULL COMMENT 'Total days allocated for period',
  `period_start` DATE DEFAULT NULL COMMENT 'Start date of current vacation period',
  `period_end` DATE DEFAULT NULL COMMENT 'End date of current vacation period',
  
  -- Change tracking
  `balance_changed` BOOLEAN DEFAULT FALSE COMMENT 'True if available_balance changed',
  `change_amount` DECIMAL(10, 2) DEFAULT NULL COMMENT 'Amount of change (new - old)',
  `change_reason` VARCHAR(255) DEFAULT NULL COMMENT 'Reason for change (manual entry)',
  
  -- Status and notes
  `calculation_status` ENUM('success', 'warning', 'error', 'manual') DEFAULT 'success' COMMENT 'Status of calculation',
  `notes` TEXT DEFAULT NULL COMMENT 'Any calculation notes or warnings',
  
  -- Metadata
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'When this record was created',
  `run_timestamp` DATETIME DEFAULT NULL COMMENT 'Exact time the cron job ran',
  
  -- Indexes for fast queries
  KEY `idx_emp_id` (`emp_id`),
  KEY `idx_update_date` (`update_date`),
  KEY `idx_emp_date` (`emp_id`, `update_date`),
  KEY `idx_balance_changed` (`balance_changed`),
  KEY `idx_created_at` (`created_at`),
  
  CONSTRAINT `fk_emp_vacation_balance_history_emp` 
    FOREIGN KEY (`emp_id`) 
    REFERENCES `employees` (`emp_id`) 
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
  COMMENT='Historical record of vacation balance updates for audit trail';


-- ============================================================================
-- USEFUL QUERIES FOR ANALYSIS
-- ============================================================================

-- Query 1: View complete history for a specific employee
-- Shows all balance changes and calculations for employee 1061
SELECT 
  update_date,
  old_available_balance,
  new_available_balance,
  change_amount,
  balance_changed,
  earned_days,
  used_days,
  carryover_days,
  period_start,
  period_end,
  calculation_status,
  run_timestamp
FROM emp_vacation_balance_history 
WHERE emp_id = '1061'
ORDER BY update_date DESC;

-- Query 2: Find all negative balance records (CRITICAL ISSUES)
-- These indicate calculation problems that need fixing
SELECT 
  id,
  update_date,
  emp_id,
  (SELECT name FROM employees WHERE emp_id = emp_vacation_balance_history.emp_id) AS emp_name,
  old_available_balance,
  new_available_balance,
  change_amount,
  earned_days,
  used_days,
  carryover_days,
  calculation_status
FROM emp_vacation_balance_history 
WHERE new_available_balance < 0
ORDER BY update_date DESC, emp_id;

-- Query 3: Day-by-day summary of all changes
-- Shows statistics for each day the cron job ran
SELECT 
  update_date,
  COUNT(*) AS total_updates,
  COUNT(DISTINCT emp_id) AS unique_employees,
  SUM(CASE WHEN balance_changed THEN 1 ELSE 0 END) AS balances_changed,
  SUM(CASE WHEN new_available_balance < 0 THEN 1 ELSE 0 END) AS negative_balances,
  SUM(CASE WHEN calculation_status = 'error' THEN 1 ELSE 0 END) AS error_count,
  AVG(new_available_balance) AS avg_balance,
  MAX(change_amount) AS max_increase,
  MIN(change_amount) AS max_decrease
FROM emp_vacation_balance_history 
GROUP BY update_date
ORDER BY update_date DESC;

-- Query 4: Employees with recurring changes (potential pattern issues)
-- Shows employees whose balance changes every day (might indicate calculation problem)
SELECT 
  emp_id,
  (SELECT name FROM employees WHERE emp_id = emp_vacation_balance_history.emp_id) AS emp_name,
  COUNT(*) AS change_count,
  COUNT(DISTINCT update_date) AS days_changed,
  AVG(change_amount) AS avg_daily_change,
  MIN(new_available_balance) AS min_balance,
  MAX(new_available_balance) AS max_balance
FROM emp_vacation_balance_history 
WHERE balance_changed = TRUE
GROUP BY emp_id
HAVING COUNT(*) >= 10
ORDER BY change_count DESC;

-- Query 5: Compare balances between specific dates
-- Find what changed for an employee between two dates
SELECT 
  h1.update_date AS date1,
  h1.new_available_balance AS balance_on_date1,
  h2.update_date AS date2,
  h2.new_available_balance AS balance_on_date2,
  (h2.new_available_balance - h1.new_available_balance) AS total_change
FROM emp_vacation_balance_history h1
JOIN emp_vacation_balance_history h2 
  ON h1.emp_id = h2.emp_id 
  AND h1.update_date < h2.update_date
WHERE h1.emp_id = '1061'
  AND h1.update_date = '2026-01-06'
  AND h2.update_date = '2026-01-10'
ORDER BY h1.update_date;

-- Query 6: Find calculation errors by comparing with database records
-- Shows where history records differ from current emp_vacation_balance
SELECT 
  h.emp_id,
  h.update_date,
  h.new_available_balance AS history_balance,
  evb.available_balance AS current_balance,
  (evb.available_balance - h.new_available_balance) AS discrepancy,
  CASE 
    WHEN evb.available_balance < 0 THEN 'CRITICAL'
    WHEN ABS(evb.available_balance - h.new_available_balance) > 0.01 THEN 'MISMATCH'
    ELSE 'OK'
  END AS status
FROM emp_vacation_balance_history h
LEFT JOIN emp_vacation_balance evb ON h.emp_id = evb.emp_id
WHERE h.update_date = CURDATE()
  AND (evb.available_balance < 0 OR ABS(evb.available_balance - h.new_available_balance) > 0.01)
ORDER BY h.emp_id;

-- Query 7: Identify calculation method changes
-- Shows when earned_days calculation changed significantly
SELECT 
  h1.emp_id,
  h1.update_date,
  h1.earned_days,
  h2.update_date AS next_day,
  h2.earned_days,
  (h2.earned_days - h1.earned_days) AS daily_accrual,
  CASE 
    WHEN h2.earned_days - h1.earned_days > 0.3 THEN 'UNUSUAL_JUMP'
    WHEN h2.earned_days - h1.earned_days < 0.05 THEN 'LOWER_ACCRUAL'
    ELSE 'NORMAL'
  END AS accrual_status
FROM emp_vacation_balance_history h1
JOIN emp_vacation_balance_history h2 
  ON h1.emp_id = h2.emp_id 
  AND DATE_ADD(h1.update_date, INTERVAL 1 DAY) = h2.update_date
WHERE h1.emp_id = '1061'
ORDER BY h1.update_date DESC;

-- Query 8: Monthly balance summary
-- See beginning and ending balance for each month
SELECT 
  emp_id,
  (SELECT name FROM employees WHERE emp_id = emp_vacation_balance_history.emp_id) AS emp_name,
  YEAR(update_date) AS year,
  MONTH(update_date) AS month,
  (SELECT new_available_balance 
   FROM emp_vacation_balance_history h2 
   WHERE h2.emp_id = emp_vacation_balance_history.emp_id 
     AND YEAR(h2.update_date) = YEAR(emp_vacation_balance_history.update_date)
     AND MONTH(h2.update_date) = MONTH(emp_vacation_balance_history.update_date)
   ORDER BY h2.update_date ASC LIMIT 1) AS start_balance,
  (SELECT new_available_balance 
   FROM emp_vacation_balance_history h3 
   WHERE h3.emp_id = emp_vacation_balance_history.emp_id 
     AND YEAR(h3.update_date) = YEAR(emp_vacation_balance_history.update_date)
     AND MONTH(h3.update_date) = MONTH(emp_vacation_balance_history.update_date)
   ORDER BY h3.update_date DESC LIMIT 1) AS end_balance
FROM emp_vacation_balance_history
WHERE emp_id = '1061'
GROUP BY emp_id, YEAR(update_date), MONTH(update_date)
ORDER BY year DESC, month DESC;


-- ============================================================================
-- MAINTENANCE QUERIES
-- ============================================================================

-- Clean up old history records (keep last 90 days)
-- DELETE FROM emp_vacation_balance_history 
-- WHERE update_date < DATE_SUB(CURDATE(), INTERVAL 90 DAY);

-- Archive history to separate table before cleaning
-- INSERT INTO emp_vacation_balance_history_archive 
-- SELECT * FROM emp_vacation_balance_history 
-- WHERE update_date < DATE_SUB(CURDATE(), INTERVAL 365 DAY);

-- Get table size
SELECT 
  ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
FROM information_schema.TABLES 
WHERE table_schema = 'almutlak_db' 
  AND table_name = 'emp_vacation_balance_history';
