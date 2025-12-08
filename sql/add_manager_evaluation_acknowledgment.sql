-- Add Manager Evaluation Acknowledgment/Objection Tracking
-- This allows managers to acknowledge or object to employee evaluations with optional notes

ALTER TABLE `emp_evaluations` ADD COLUMN `manager_acknowledgment_status` ENUM('pending', 'acknowledged', 'objected') DEFAULT 'pending' COMMENT 'Manager acknowledgment status: pending, acknowledged, or objected';

ALTER TABLE `emp_evaluations` ADD COLUMN `manager_objection_note` LONGTEXT NULL COMMENT 'Manager objection note/reason for objection';

ALTER TABLE `emp_evaluations` ADD COLUMN `manager_acknowledgment_date` DATETIME NULL COMMENT 'Date when manager acknowledged or objected to evaluation';

ALTER TABLE `emp_evaluations` ADD COLUMN `manager_acknowledged_by` INT NULL COMMENT 'Employee ID of the manager who acknowledged/objected';

-- Add indexes for better query performance
ALTER TABLE `emp_evaluations` ADD INDEX `idx_manager_acknowledgment_status` (`manager_acknowledgment_status`);
ALTER TABLE `emp_evaluations` ADD INDEX `idx_manager_acknowledged_by` (`manager_acknowledged_by`);

-- Add foreign key constraint (optional, uncomment if needed)
-- ALTER TABLE `emp_evaluations` ADD CONSTRAINT `fk_manager_acknowledged_by` 
-- FOREIGN KEY (`manager_acknowledged_by`) REFERENCES `employees`(`emp_id`) ON DELETE SET NULL;
-- Add Manager Evaluation Acknowledgment/Objection Tracking
-- This allows managers to acknowledge or object to employee evaluations with optional notes

ALTER TABLE `emp_evaluations` ADD COLUMN `manager_acknowledgment_status` ENUM('pending', 'acknowledged', 'objected') DEFAULT 'pending' COMMENT 'Manager acknowledgment status: pending, acknowledged, or objected';

ALTER TABLE `emp_evaluations` ADD COLUMN `manager_objection_note` LONGTEXT NULL COMMENT 'Manager objection note/reason for objection';

ALTER TABLE `emp_evaluations` ADD COLUMN `manager_acknowledgment_date` DATETIME NULL COMMENT 'Date when manager acknowledged or objected to evaluation';

ALTER TABLE `emp_evaluations` ADD COLUMN `manager_acknowledged_by` INT NULL COMMENT 'Employee ID of the manager who acknowledged/objected';

-- Add indexes for better query performance
ALTER TABLE `emp_evaluations` ADD INDEX `idx_manager_acknowledgment_status` (`manager_acknowledgment_status`);
ALTER TABLE `emp_evaluations` ADD INDEX `idx_manager_acknowledged_by` (`manager_acknowledged_by`);

-- Add foreign key constraint (optional, uncomment if needed)
-- ALTER TABLE `emp_evaluations` ADD CONSTRAINT `fk_manager_acknowledged_by` 
-- FOREIGN KEY (`manager_acknowledged_by`) REFERENCES `employees`(`emp_id`) ON DELETE SET NULL;
