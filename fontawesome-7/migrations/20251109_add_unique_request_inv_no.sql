-- Migration: Add UNIQUE constraint to emp_vacation.request_inv_no and backfill existing NULLs
-- Run order:
-- 1) Backfill legacy rows
-- 2) Alter column to NOT NULL & enlarge length if needed
-- 3) Add unique index

START TRANSACTION;

-- Backfill any NULL / empty values with deterministic legacy IDs
UPDATE emp_vacation 
SET request_inv_no = CONCAT('LEGACY-', id)
WHERE (request_inv_no IS NULL OR request_inv_no = '');

-- Ensure no duplicates accidentally produced (unlikely unless LEGACY-* existed)
-- You can inspect with:
-- SELECT request_inv_no, COUNT(*) c FROM emp_vacation GROUP BY request_inv_no HAVING c > 1;

-- Modify column to be NOT NULL and slightly larger
ALTER TABLE emp_vacation 
  MODIFY request_inv_no VARCHAR(64) NOT NULL COMMENT 'Unique ID to link to request_approvers';

-- Add unique index (if one does not already exist)
ALTER TABLE emp_vacation 
  ADD UNIQUE KEY uniq_emp_vacation_request_inv_no (request_inv_no);

COMMIT;
