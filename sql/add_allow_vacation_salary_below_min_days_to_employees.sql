-- Migration: Add per-employee override flag for Local Annual (Saudi) vacation salary payout
-- Date: 2026-07-29
-- Purpose: Local Annual (Saudi) vacations only pay the vacation salary / remove the
--          employee from active payroll when the approved days meet the minimum
--          threshold (see getLocalAnnualPayrollRemovalRuleConfig(), currently 20 days).
--          This flag lets HR/admin explicitly allow a specific employee to still
--          receive the vacation salary payout even when the approved days are below
--          that minimum. Consumed by matchesLocalAnnualPayrollRemovalRule() /
--          isLocalAnnualRemovedFromPayroll() / isSettlementPayableVacation() in
--          includes/helper_functions.php, and affects both the vacation report and
--          the settlement payable-amount calculation.

SET @db_name = DATABASE();

SET @column_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'employees'
      AND COLUMN_NAME = 'allow_vacation_salary_below_min_days'
);

SET @ddl = IF(
    @column_exists = 0,
    'ALTER TABLE `employees` ADD COLUMN `allow_vacation_salary_below_min_days` TINYINT(1) NOT NULL DEFAULT 0 AFTER `allow_emergency_vacation`',
    'SELECT ''Column allow_vacation_salary_below_min_days already exists in employees'' AS message'
);

PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
