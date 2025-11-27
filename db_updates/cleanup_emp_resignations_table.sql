-- =====================================================================
-- CLEANUP: Remove Unused Columns from emp_resignations Table
-- =====================================================================
-- Date: 2025-11-26
-- Purpose: Remove redundant/unused columns since we're using request_approvers
--          table for all approval tracking instead
-- =====================================================================

-- Check current table structure before running this script
-- DESC emp_resignations;

ALTER TABLE `emp_resignations` 
DROP COLUMN `approved_by`,
DROP COLUMN `approval_date`,
DROP COLUMN `hr_notes`,
DROP COLUMN `final_settlement_completed`,
DROP COLUMN `clearance_completed`,
DROP COLUMN `rejected_by`;

-- =====================================================================
-- Explanation of Removed Columns:
-- =====================================================================
-- 1. `approved_by` - NOT USED
--    Approval tracking is now done via request_approvers.approver_id
--
-- 2. `approval_date` - NOT USED
--    Approval timestamp is tracked in request_approvers.action_date
--
-- 3. `hr_notes` - NOT USED
--    HR notes/comments are stored in request_approvers.note
--
-- 4. `final_settlement_completed` - NOT USED
--    No code references this column anywhere in the system
--
-- 5. `clearance_completed` - NOT USED
--    No code references this column anywhere in the system
--
-- 6. `rejected_by` - NOT USED
--    Rejection info is tracked in request_approvers with status='rejected'
--    and the rejector is identified via approver_id
--
-- =====================================================================
-- Retained Columns in emp_resignations:
-- =====================================================================
-- id - Primary key
-- request_inv_no - Reference to request approval chain
-- emp_id - Employee ID
-- last_working_day - Last working day of employee
-- submission_date - When resignation was submitted
-- status - Main status (pending/approved/rejected/cancelled/withdrawn)
-- rejection_reason - Reason if rejected (still used for employee info)
-- created_at - Timestamp created
-- updated_at - Timestamp updated
-- needs_replacement - Whether replacement is needed
-- replacement_data - JSON data for replacement (HR Operations step)
-- =====================================================================
