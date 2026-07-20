CREATE TABLE IF NOT EXISTS `emp_temp_role_assignments` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `vacation_id` INT NOT NULL,
    `request_inv_no` VARCHAR(64) DEFAULT NULL,
    `employee_emp_id` VARCHAR(50) NOT NULL,
    `replacement_emp_id` VARCHAR(50) NOT NULL,
    `granted_role` VARCHAR(50) NOT NULL,
    `valid_from` DATE NOT NULL,
    `valid_to` DATE NOT NULL,
    `status` ENUM('active', 'expired', 'revoked') NOT NULL DEFAULT 'active',
    `granted_by_emp_id` VARCHAR(50) DEFAULT NULL,
    `granted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `closed_by_emp_id` VARCHAR(50) DEFAULT NULL,
    `closed_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_vacation_status` (`vacation_id`, `status`),
    KEY `idx_replacement_status_dates` (`replacement_emp_id`, `status`, `valid_from`, `valid_to`),
    KEY `idx_request_inv_no` (`request_inv_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
