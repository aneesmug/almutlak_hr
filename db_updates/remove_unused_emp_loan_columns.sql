-- ========================================
-- Remove Unused Columns from emp_loan Table
-- Date: 2025-11-12
-- ========================================

-- COLUMNS TO BE REMOVED:
-- 1. approved_by_user_ids - Approval tracking is now done via request_approvers table
-- 2. modified_by - Not used in current implementation
-- 3. modification_note - Not used in current implementation  
-- 4. original_amount - Not used in current implementation
-- 5. original_installments - Not used in current implementation

-- BACKUP FIRST (recommended)
-- CREATE TABLE emp_loan_backup_20251112 AS SELECT * FROM emp_loan;

-- Remove unused columns
ALTER TABLE `emp_loan`
    DROP COLUMN `approved_by_user_ids`,
    DROP COLUMN `modified_by`,
    DROP COLUMN `modification_note`,
    DROP COLUMN `original_amount`,
    DROP COLUMN `original_installments`;

-- Verify the changes
DESCRIBE `emp_loan`;
