-- ========================================
-- Complete Migration Script: Old emp_vacation to New Structure
-- Created: November 5, 2025
-- ========================================

-- STEP 1: Backup existing emp_vacation table (if exists)
DROP TABLE IF EXISTS `emp_vacation_backup_20251105`;
CREATE TABLE `emp_vacation_backup_20251105` LIKE `emp_vacation`;
INSERT INTO `emp_vacation_backup_20251105` SELECT * FROM `emp_vacation`;

-- STEP 2: Drop old emp_vacation table
DROP TABLE IF EXISTS `emp_vacation`;

-- STEP 3: Create new emp_vacation table structure
CREATE TABLE `emp_vacation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_inv_no` varchar(50) DEFAULT NULL COMMENT 'Unique ID to link to request_approvers',
  `current_status` enum('draft','pending_approval','approved','rejected') NOT NULL DEFAULT 'draft' COMMENT 'Overall status of the request',
  `current_approval_level` int(11) DEFAULT NULL COMMENT 'The current level pending approval',
  `emp_id` varchar(255) NOT NULL,
  `start_date` varchar(255) NOT NULL,
  `user_update` varchar(255) NOT NULL,
  `return_date` varchar(50) NOT NULL,
  `vacdays` int(50) NOT NULL,
  `vac_type` varchar(50) NOT NULL,
  `fly_type` enum('annual','emergency') DEFAULT NULL,
  `arrived_date` varchar(100) NOT NULL,
  `permit_no` varchar(100) NOT NULL,
  `remarks` varchar(255) NOT NULL,
  `vacation_salary_type` enum('payroll','end_of_service') NOT NULL DEFAULT 'payroll' COMMENT 'Determines when vacation salary is paid: with payroll or at end of service',
  `attachment_path` varchar(255) DEFAULT NULL,
  `is_deductible` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = Deductible from annual balance, 0 = Not deductible',
  `review` varchar(50) DEFAULT NULL,
  `note` varchar(255) NOT NULL,
  `replacement_person` varchar(100) DEFAULT NULL,
  `last_vac_date` date DEFAULT NULL,
  `next_vac_date` date DEFAULT NULL,
  `ticket_pay` decimal(10,2) DEFAULT NULL,
  `permit_fee` decimal(10,2) DEFAULT NULL,
  `encashment_amount` decimal(10,2) DEFAULT NULL COMMENT 'Encashed vacation days salary amount',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `emp_id` (`emp_id`),
  KEY `request_inv_no` (`request_inv_no`),
  KEY `current_status` (`current_status`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- STEP 4: Import and transform data from backup
INSERT INTO `emp_vacation` (
    `id`,
    `request_inv_no`,
    `current_status`,
    `current_approval_level`,
    `emp_id`,
    `start_date`,
    `user_update`,
    `return_date`,
    `vacdays`,
    `vac_type`,
    `fly_type`,
    `arrived_date`,
    `permit_no`,
    `remarks`,
    `vacation_salary_type`,
    `attachment_path`,
    `is_deductible`,
    `review`,
    `note`,
    `replacement_person`,
    `last_vac_date`,
    `next_vac_date`,
    `ticket_pay`,
    `permit_fee`,
    `encashment_amount`,
    `created_at`
)
SELECT 
    `id`,
    CONCAT('VAC-', LPAD(`id`, 6, '0')) as request_inv_no,
    CASE 
        WHEN `approval_status` = 'apply' THEN 'draft'
        WHEN `approval_status` IN ('dept_manager_pending', 'pending', 'hr_assistant_approved', 'it_pending', 'hr_manager_approved') THEN 'pending_approval'
        WHEN `approval_status` = 'gm_approved' THEN 'approved'
        WHEN `approval_status` = 'rejected' THEN 'rejected'
        ELSE 'draft'
    END as current_status,
    CASE 
        WHEN `approval_status` = 'apply' THEN NULL
        WHEN `approval_status` = 'dept_manager_pending' THEN 1
        WHEN `approval_status` IN ('pending', 'hr_assistant_approved') THEN 2
        WHEN `approval_status` = 'it_pending' THEN 3
        WHEN `approval_status` = 'hr_manager_approved' THEN 4
        ELSE NULL
    END as current_approval_level,
    `emp_id`,
    `start_date`,
    `user_update`,
    `return_date`,
    `vacdays`,
    `vac_type`,
    `fly_type`,
    `arrived_date`,
    `permit_no`,
    `remarks`,
    'payroll' as vacation_salary_type,
    `attachment_path`,
    `is_deductible`,
    `review`,
    `note`,
    `replacement_person`,
    `last_vac_date`,
    `next_vac_date`,
    `ticket_pay`,
    `permit_fee`,
    NULL as encashment_amount,
    `created_at`
FROM `emp_vacation_backup_20251105`;

-- STEP 5: Reset AUTO_INCREMENT to next available ID
SET @max_id = (SELECT IFNULL(MAX(id), 0) + 1 FROM `emp_vacation`);
SET @sql = CONCAT('ALTER TABLE `emp_vacation` AUTO_INCREMENT = ', @max_id);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- STEP 6: Verify the migration
SELECT 
    COUNT(*) as total_records,
    SUM(CASE WHEN current_status = 'draft' THEN 1 ELSE 0 END) as draft_count,
    SUM(CASE WHEN current_status = 'pending_approval' THEN 1 ELSE 0 END) as pending_count,
    SUM(CASE WHEN current_status = 'approved' THEN 1 ELSE 0 END) as approved_count,
    SUM(CASE WHEN current_status = 'rejected' THEN 1 ELSE 0 END) as rejected_count
FROM `emp_vacation`;

-- SUCCESS! 
-- Your emp_vacation table now has the new structure with transformed data.
-- The old table is backed up as emp_vacation_backup_20251105
