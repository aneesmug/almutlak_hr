-- ZKTeco ADMS device integration: device registry + raw punch log.
-- Run once (phpMyAdmin import or `mysql almutlak_db < add_zk_devices_tables.sql`).
-- Safe to re-run: uses IF NOT EXISTS / INSERT IGNORE throughout.

CREATE TABLE IF NOT EXISTS `zk_devices` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `serial_number` VARCHAR(50) NOT NULL,
  `device_name` VARCHAR(100) NOT NULL,
  `area` VARCHAR(100) DEFAULT NULL,
  `device_ip` VARCHAR(45) DEFAULT NULL,
  `state` ENUM('online','offline') NOT NULL DEFAULT 'offline',
  `last_activity` DATETIME DEFAULT NULL,
  `user_qty` INT NOT NULL DEFAULT 0,
  `fp_qty` INT NOT NULL DEFAULT 0,
  `face_qty` INT NOT NULL DEFAULT 0,
  `palm_qty` INT NOT NULL DEFAULT 0,
  `transaction_qty` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_serial_number` (`serial_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `zk_attendance_raw` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `serial_number` VARCHAR(50) NOT NULL,
  `pin` VARCHAR(20) NOT NULL,
  `punch_time` DATETIME NOT NULL,
  `status` VARCHAR(5) DEFAULT NULL,
  `verify` VARCHAR(5) DEFAULT NULL,
  `work_code` VARCHAR(20) DEFAULT NULL,
  `received_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_punch` (`serial_number`, `pin`, `punch_time`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Clean, dedicated reporting table for live device punches. Deliberately NOT
-- written into the existing `attendance` table: that table's real live
-- schema (id, uid UNIQUE NOT NULL, emp_id, state, date, time_in, time_out,
-- type, note, created_at) has no emp_name/punch_time/punch_state/uptime
-- columns at all - the code that assumes those (import_csv/uploadCsv.php,
-- includes/ajaxFile/attendanceEmpAjaxfile.php) is legacy/unwired, not the
-- real live path. This table is employee-resolved (joined against
-- `employees` at insert time) and shaped for direct reporting use.
CREATE TABLE IF NOT EXISTS `zk_attendance` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `emp_id` VARCHAR(255) NOT NULL,
  `emp_name` VARCHAR(255) NOT NULL,
  `serial_number` VARCHAR(50) NOT NULL,
  `punch_datetime` DATETIME NOT NULL,
  `punch_date` DATE NOT NULL,
  `punch_time` TIME NOT NULL,
  `punch_state` VARCHAR(10) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_emp_date` (`emp_id`, `punch_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `app_settings` (`setting_name`, `setting_value`, `setting_group`, `description`, `input_type`, `options`)
VALUES ('device_offline_threshold_minutes', '3', 'device_monitor', 'Minutes of silence before a ZK device is marked offline', 'text', NULL);
