-- Login security hardening (Oct 2026)
-- Both changes are also applied automatically by includes/login_security.php on first use;
-- run this manually only if you prefer to apply them up front.

-- 1. Failed/successful login, OTP, registration and reset attempts (brute-force lockout + audit)
CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `identifier` VARCHAR(64) NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `kind` VARCHAR(20) NOT NULL,
    `success` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_identifier_time` (`identifier`, `created_at`),
    KEY `idx_ip_time` (`ip_address`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Forces a password change after an HR password reset (skip if the column already exists)
ALTER TABLE `admin_login` ADD COLUMN `must_change_password` TINYINT(1) NOT NULL DEFAULT 0;
