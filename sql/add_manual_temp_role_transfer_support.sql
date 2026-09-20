-- Extends emp_temp_role_assignments (previously vacation-only "Transfer Role (Temp)"
-- feature, see create_emp_temp_role_assignments.sql) to also support HR-initiated
-- manual temporary role transfers that aren't tied to an approved vacation record -
-- e.g. HR assigning a direct supervisor's role to a replacement employee for a fixed
-- number of days/months while the supervisor is on leave, independent of the
-- vacation approval workflow. See app_settings.php's "Temporary Role Transfer" tab.
ALTER TABLE `emp_temp_role_assignments`
    MODIFY `vacation_id` INT NULL,
    ADD COLUMN `source` VARCHAR(20) NOT NULL DEFAULT 'vacation' AFTER `granted_role`,
    ADD COLUMN `notes` VARCHAR(255) DEFAULT NULL AFTER `source`,
    ADD COLUMN `expiry_notified_at` DATETIME DEFAULT NULL AFTER `closed_at`,
    ADD KEY `idx_status_valid_to` (`status`, `valid_to`),
    ADD KEY `idx_employee_status` (`employee_emp_id`, `status`);
