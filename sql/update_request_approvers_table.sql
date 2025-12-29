-- ============================================================================
-- UPDATE request_approvers TABLE FOR NEW approval_request_types SCHEMA
-- ============================================================================
-- This script updates the request_approvers table to work with the new
-- approval_request_types schema where id is INT AUTO_INCREMENT
-- ============================================================================

-- Step 1: Drop the existing foreign key constraint
ALTER TABLE `request_approvers` 
DROP FOREIGN KEY IF EXISTS `fk_request_type`;

-- Step 2: Ensure request_type_id column is INT (should already be, but making sure)
ALTER TABLE `request_approvers` 
MODIFY COLUMN `request_type_id` int(11) NOT NULL;

-- Step 3: Update existing data to use the new numeric IDs
-- Map old request_type_id values to new ones based on type_name
-- This assumes the following mapping (verify with your data):
-- Old system might have had different values, adjust as needed

-- Update any orphaned/invalid request_type_id values to a valid one
-- First, let's update based on the request_inv_no prefix patterns:

-- Excuse Leave requests (LV- prefix) -> excuse_leave (id=7)
UPDATE `request_approvers` ra
SET ra.`request_type_id` = 7
WHERE ra.`request_inv_no` LIKE 'LV-%'
AND ra.`request_type_id` NOT IN (SELECT `id` FROM `approval_request_types`);

-- Vacation requests (VAC- prefix) -> vacation_request (id=3)
UPDATE `request_approvers` ra
SET ra.`request_type_id` = 3
WHERE ra.`request_inv_no` LIKE 'VAC-%'
AND ra.`request_type_id` NOT IN (SELECT `id` FROM `approval_request_types`);

-- Rejoin requests (RR- prefix) -> rejoin_request (id=5)
UPDATE `request_approvers` ra
SET ra.`request_type_id` = 5
WHERE ra.`request_inv_no` LIKE 'RR-%'
AND ra.`request_type_id` NOT IN (SELECT `id` FROM `approval_request_types`);

-- Loan requests (LOAN- prefix or similar) -> loan_request (id=2)
UPDATE `request_approvers` ra
SET ra.`request_type_id` = 2
WHERE ra.`request_inv_no` LIKE 'LOAN-%'
AND ra.`request_type_id` NOT IN (SELECT `id` FROM `approval_request_types`);

-- Smart requests (SMT- prefix or similar) -> smart_request (id=1)
UPDATE `request_approvers` ra
SET ra.`request_type_id` = 1
WHERE ra.`request_inv_no` LIKE 'SMT-%'
AND ra.`request_type_id` NOT IN (SELECT `id` FROM `approval_request_types`);

-- Resignation requests (RES- prefix or similar) -> resignation_request (id=4)
UPDATE `request_approvers` ra
SET ra.`request_type_id` = 4
WHERE ra.`request_inv_no` LIKE 'RES-%'
AND ra.`request_type_id` NOT IN (SELECT `id` FROM `approval_request_types`);

-- General requests (GEN- prefix or similar) -> general_request (id=6)
UPDATE `request_approvers` ra
SET ra.`request_type_id` = 6
WHERE ra.`request_inv_no` LIKE 'GEN-%'
AND ra.`request_type_id` NOT IN (SELECT `id` FROM `approval_request_types`);

-- Step 4: Convert approval_request_types to InnoDB (MyISAM doesn't support foreign keys)
ALTER TABLE `approval_request_types` ENGINE=InnoDB;

-- Step 4b: Ensure id column has AUTO_INCREMENT and PRIMARY KEY
ALTER TABLE `approval_request_types` 
MODIFY COLUMN `id` int(11) NOT NULL AUTO_INCREMENT,
ADD PRIMARY KEY IF NOT EXISTS (`id`);

-- Step 4c: Re-add the foreign key constraint
ALTER TABLE `request_approvers` 
ADD CONSTRAINT `fk_request_type` 
FOREIGN KEY (`request_type_id`) 
REFERENCES `approval_request_types` (`id`) 
ON DELETE CASCADE;

-- Step 5: Add indexes for better performance
ALTER TABLE `request_approvers`
ADD INDEX IF NOT EXISTS `idx_request_inv_no` (`request_inv_no`),
ADD INDEX IF NOT EXISTS `idx_request_type_id` (`request_type_id`),
ADD INDEX IF NOT EXISTS `idx_approver_id` (`approver_id`),
ADD INDEX IF NOT EXISTS `idx_status` (`status`);

-- ============================================================================
-- VERIFICATION QUERIES (Run these to check the update was successful)
-- ============================================================================

-- Check for any orphaned request_type_id values
SELECT ra.id, ra.request_inv_no, ra.request_type_id, 'ORPHANED - NOT IN approval_request_types' as issue
FROM `request_approvers` ra
WHERE ra.`request_type_id` NOT IN (SELECT `id` FROM `approval_request_types`)
LIMIT 20;

-- Count records by request type
SELECT 
    art.id,
    art.type_name,
    COUNT(ra.id) as approval_count
FROM `approval_request_types` art
LEFT JOIN `request_approvers` ra ON art.id = ra.request_type_id
GROUP BY art.id, art.type_name
ORDER BY art.id;

-- Check recent approvals
SELECT 
    ra.id,
    ra.request_inv_no,
    art.type_name,
    ra.approver_id,
    ra.approval_level,
    ra.status
FROM `request_approvers` ra
JOIN `approval_request_types` art ON ra.request_type_id = art.id
ORDER BY ra.id DESC
LIMIT 10;
