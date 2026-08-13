-- Employee Transfer Request Table Migration
-- Stores supervisor-to-supervisor employee transfer requests (temporary or permanent)
-- with the same multi-level approval workflow used by other request types.

-- NOTE ON COLUMN NAMING: the generic chain-approval engine (handle_approval_action
-- in includes/helper_functions.php) assumes `<main_table>.emp_id` identifies the
-- REQUEST CREATOR, and uses it to decide who gets the "your request was approved/
-- rejected" notification + email. The creator here is whoever fills out the form
-- (a supervisor acting for themselves, or HR/admin acting on someone else's behalf) -
-- NOT necessarily the new supervisor, so `to_supervisor_id` is a separate, explicitly
-- selected field.
CREATE TABLE IF NOT EXISTS `emp_transfers` (
    `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `request_inv_no` VARCHAR(100) NOT NULL UNIQUE COMMENT 'Employee Transfer Request Unique ID (ET-YYYYMMDDHHMMSS-EMPID-xxxx)',
    `emp_id` VARCHAR(255) NOT NULL COMMENT 'Request creator (may or may not be the new supervisor)',
    `target_emp_id` VARCHAR(255) NOT NULL COMMENT 'Employee being transferred',
    `from_supervisor_id` VARCHAR(255) NOT NULL COMMENT 'Current/old direct supervisor of target_emp_id at request time',
    `to_supervisor_id` VARCHAR(255) NOT NULL COMMENT 'New direct supervisor, explicitly selected in the request form (must be an @almutlak.com account)',
    `transfer_type` ENUM('temporary', 'permanent') NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE DEFAULT NULL COMMENT 'Required for temporary transfers, NULL for permanent',

    -- Approval Workflow
    `current_status` ENUM('pending_approval', 'rejected', 'approved', 'completed', 'cancelled') DEFAULT 'pending_approval',
    `current_approval_level` INT(11) DEFAULT 1,

    -- Notes
    `request_notes` LONGTEXT COMMENT 'Additional notes from the requester',
    `created_by` INT(11) COMMENT 'User who created the request',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Reversion tracking (temporary transfers only)
    `reverted_at` DATETIME DEFAULT NULL COMMENT 'Set by cron_revert_temp_transfers.php when a temporary transfer auto-reverts',

    KEY `idx_emp_id` (`emp_id`),
    KEY `idx_target_emp_id` (`target_emp_id`),
    KEY `idx_request_inv_no` (`request_inv_no`),
    KEY `idx_status` (`current_status`),
    KEY `idx_from_supervisor` (`from_supervisor_id`),
    KEY `idx_to_supervisor` (`to_supervisor_id`),

    CONSTRAINT `fk_emp_transfers_emp` FOREIGN KEY (`emp_id`) REFERENCES `employees` (`emp_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_emp_transfers_target` FOREIGN KEY (`target_emp_id`) REFERENCES `employees` (`emp_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_emp_transfers_to_supervisor` FOREIGN KEY (`to_supervisor_id`) REFERENCES `employees` (`emp_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci COMMENT='Employee Transfer Requests with Multi-Level Approval';

-- Register the new request type with the generic chain-approval engine
INSERT IGNORE INTO `approval_request_types`
(`type_name`, `main_table_name`, `description`, `is_default`, `is_active`)
VALUES
(
    'employee_transfer_request',
    'emp_transfers',
    'Employee transfer from one direct supervisor to another (temporary or permanent)',
    0,
    1
);

-- Default approval chain: level 1 auto-resolves to the target employee's current
-- direct supervisor. Admin can add further levels from App Settings -> Approval.
INSERT IGNORE INTO `app_settings`
(`setting_name`, `setting_value`, `setting_group`, `input_type`, `description`)
VALUES
(
    'approval_chain_employee_transfer_request',
    '[{"level":1,"user_type":"direct_supervisor","role_label":"Direct Supervisor"}]',
    'approval',
    'json',
    'Employee Transfer Request Approval Chain Configuration'
);
