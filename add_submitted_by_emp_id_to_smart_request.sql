-- Adds a column to track who submitted (applied) the smart request
-- This is added across all rows identified by inv_no; table is denormalized so each row can carry the submitter

ALTER TABLE `smart_request`
ADD COLUMN `submitted_by_emp_id` INT NULL DEFAULT NULL AFTER `prep_by`;
