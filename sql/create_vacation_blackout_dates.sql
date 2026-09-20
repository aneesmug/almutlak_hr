-- Date ranges during which no employee can apply for a vacation (see leaveHandler.php
-- 'applyVacation' and App Settings > "Vacation Blackout Dates" tab, gated by the
-- 'manage_vacation_blackout_dates' Special Access key). Rows are soft-deleted
-- (is_active = 0) so there's a record of past blackouts.
CREATE TABLE IF NOT EXISTS `vacation_blackout_dates` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `reason` VARCHAR(255) DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by_emp_id` VARCHAR(50) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `removed_by_emp_id` VARCHAR(50) DEFAULT NULL,
    `removed_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_active_dates` (`is_active`, `start_date`, `end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
