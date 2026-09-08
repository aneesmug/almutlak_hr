-- Vacation Asset Clearance Migration (SQL version of 20260907_vacation_asset_clearance.php)
-- Safe to run directly (phpMyAdmin / mysql CLI). Idempotent: re-running does nothing extra.

CREATE TABLE IF NOT EXISTS vacation_asset_decisions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vacation_id INT NOT NULL,
    request_inv_no VARCHAR(64) NOT NULL,
    emp_id VARCHAR(255) NOT NULL,
    source ENUM('employee_asset','car') NOT NULL,
    ref_id INT NOT NULL,
    asset_name VARCHAR(191) NULL,
    decision ENUM('keep','return') NOT NULL,
    clearance_dept_id INT NULL,
    approver_id INT NULL,
    request_approver_id INT NULL,
    status ENUM('n_a','pending','needs_assignment','cleared') NOT NULL DEFAULT 'n_a',
    decided_by INT NULL,
    decided_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    cleared_by INT NULL,
    cleared_at DATETIME NULL,
    condition_on_return ENUM('Good','Damage','Lost','Buy','Other') NULL,
    INDEX idx_vacation (vacation_id),
    INDEX idx_inv_no (request_inv_no),
    INDEX idx_dept_status (clearance_dept_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Widen status enum for tables created before "needs_assignment" existed
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'vacation_asset_decisions' AND COLUMN_NAME = 'status' AND COLUMN_TYPE NOT LIKE '%needs_assignment%');
SET @sql := IF(@exist > 0, "ALTER TABLE vacation_asset_decisions MODIFY status ENUM('n_a','pending','needs_assignment','cleared') NOT NULL DEFAULT 'n_a'", 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Per-asset condition recorded at clearance time
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'vacation_asset_decisions' AND COLUMN_NAME = 'condition_on_return');
SET @sql := IF(@exist = 0, "ALTER TABLE vacation_asset_decisions ADD COLUMN condition_on_return ENUM('Good','Damage','Lost','Buy','Other') NULL AFTER cleared_at", 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Cars have no condition field yet - add the equivalent so a returned car's condition can be recorded too.
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cars_drv' AND COLUMN_NAME = 'car_condition');
SET @sql := IF(@exist = 0, "ALTER TABLE cars_drv ADD COLUMN car_condition ENUM('Good','Damage','Lost','Buy','Other') NULL AFTER rtn_date", 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Links each decision to the specific chain step (request_approvers row) it belongs to
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'vacation_asset_decisions' AND COLUMN_NAME = 'request_approver_id');
SET @sql := IF(@exist = 0, 'ALTER TABLE vacation_asset_decisions ADD COLUMN request_approver_id INT NULL AFTER approver_id', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- One row per (asset type, handler) - an asset type can have several eligible handlers.
CREATE TABLE IF NOT EXISTS asset_clearance_handlers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    asset_id INT UNSIGNED NOT NULL,
    handler_emp_id INT NOT NULL,
    updated_by INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_asset_handler (asset_id, handler_emp_id),
    INDEX idx_asset (asset_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Widen older single-handler-per-asset tables to allow multiple (only if the old uniq_asset index exists).
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'asset_clearance_handlers' AND INDEX_NAME = 'uniq_asset');
SET @sql := IF(@exist > 0, 'ALTER TABLE asset_clearance_handlers DROP INDEX uniq_asset, ADD UNIQUE KEY uniq_asset_handler (asset_id, handler_emp_id)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Correct Car's clearance department: route to Administration (1) instead of an
-- empty Transportation (17) department.
UPDATE assets SET clearance_dept_id = 1 WHERE name = 'Car' AND clearance_dept_id = 17;
