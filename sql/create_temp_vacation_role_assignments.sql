CREATE TABLE IF NOT EXISTS `temp_vacation_role_assignments` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `vacation_id` INT NOT NULL,
    `request_inv_no` VARCHAR(64) DEFAULT NULL,
    `employee_emp_id` VARCHAR(50) NOT NULL,
    `replacement_emp_id` VARCHAR(50) NOT NULL,
    `employee_user_type` VARCHAR(50) NOT NULL,
    `replacement_original_user_type` VARCHAR(50) NOT NULL,
    `status` ENUM('active', 'restored') NOT NULL DEFAULT 'active',
    `assigned_by_emp_id` VARCHAR(50) DEFAULT NULL,
    `assigned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `restored_by_emp_id` VARCHAR(50) DEFAULT NULL,
    `restored_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_vacation_status` (`vacation_id`, `status`),
    KEY `idx_request_inv_no` (`request_inv_no`),
    KEY `idx_replacement_emp_id` (`replacement_emp_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
