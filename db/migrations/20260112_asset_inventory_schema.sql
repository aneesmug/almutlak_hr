-- Asset Inventory Schema Migration (SQL version of 20260112_asset_inventory_schema.php)
-- Safe to run directly (phpMyAdmin / mysql CLI). Idempotent: re-running does nothing extra.

CREATE TABLE IF NOT EXISTS assets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(191) NOT NULL,
    category VARCHAR(120) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uniq_asset_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS asset_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    asset_id INT UNSIGNED NOT NULL,
    tracking_id VARCHAR(120) NOT NULL UNIQUE,
    serial_number VARCHAR(120) NULL,
    description TEXT NULL,
    status ENUM('Available','Assigned','Lost','Damaged','Retired') NOT NULL DEFAULT 'Available',
    assigned_emp_id INT NULL,
    assigned_date DATE NULL,
    return_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_tracking (tracking_id),
    INDEX idx_asset_id (asset_id),
    INDEX idx_status (status),
    INDEX idx_assigned_emp (assigned_emp_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS employee_assets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    emp_id INT NOT NULL,
    asset_id INT UNSIGNED NOT NULL,
    serial_number VARCHAR(120) NOT NULL,
    description TEXT NULL,
    assigned_date DATE NOT NULL,
    return_date DATE NULL,
    asset_condition ENUM('Good', 'Damage', 'Lost', 'Buy', 'Other') NULL,
    status ENUM('Assigned','Returned','Lost','Damaged','Retired') NOT NULL DEFAULT 'Assigned',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_emp (emp_id),
    INDEX idx_serial (serial_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- asset_items.tracking_id column + index (only if table pre-existed without it)
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'asset_items' AND COLUMN_NAME = 'tracking_id');
SET @sql := IF(@exist = 0, 'ALTER TABLE asset_items ADD COLUMN tracking_id VARCHAR(120) UNIQUE AFTER asset_id, ADD INDEX idx_tracking (tracking_id)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- asset_items.serial_number made nullable
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'asset_items' AND COLUMN_NAME = 'serial_number' AND IS_NULLABLE = 'NO');
SET @sql := IF(@exist > 0, 'ALTER TABLE asset_items MODIFY COLUMN serial_number VARCHAR(120) NULL', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- employee_assets.asset_condition column
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employee_assets' AND COLUMN_NAME = 'asset_condition');
SET @sql := IF(@exist = 0, "ALTER TABLE employee_assets ADD COLUMN asset_condition ENUM('Good', 'Damage', 'Lost', 'Buy', 'Other') NULL AFTER return_date", 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- assets.clearance_dept_id column (table may have pre-existed with only id/name/created_at,
-- in which case CREATE TABLE IF NOT EXISTS above no-ops and this column never gets added)
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'assets' AND COLUMN_NAME = 'clearance_dept_id');
SET @sql := IF(@exist = 0, 'ALTER TABLE assets ADD COLUMN clearance_dept_id INT NULL', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- assets.category column
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'assets' AND COLUMN_NAME = 'category');
SET @sql := IF(@exist = 0, 'ALTER TABLE assets ADD COLUMN category VARCHAR(120) NULL', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- assets.is_active column
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'assets' AND COLUMN_NAME = 'is_active');
SET @sql := IF(@exist = 0, 'ALTER TABLE assets ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- assets.name unique key (table may have pre-existed without it)
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'assets' AND INDEX_NAME = 'uniq_asset_name');
SET @sql := IF(@exist = 0, 'ALTER TABLE assets ADD UNIQUE KEY uniq_asset_name (name)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Seed the 4 standard asset types with their clearance department if the table is empty
-- (Mobile Phone/SIM Card -> Administration(1), Laptop -> IT(6), Car -> Administration(1),
-- matching what the vacation asset clearance feature expects to route to).
INSERT INTO assets (name, category, is_active, clearance_dept_id)
SELECT * FROM (
    SELECT 'Mobile Phone' AS name, NULL AS category, 1 AS is_active, 1 AS clearance_dept_id
    UNION ALL SELECT 'Laptop', NULL, 1, 6
    UNION ALL SELECT 'SIM Card', NULL, 1, 1
    UNION ALL SELECT 'Car', NULL, 1, 1
) seed
WHERE NOT EXISTS (SELECT 1 FROM assets);

-- Backfill tracking_id for any pre-existing asset_items rows that have none
-- (original PHP looped per-row generating TRACK-YYYYMMDD-#### sequentially; this
-- does the same in one pass using ROW_NUMBER, fine since it only runs once on empty values)
UPDATE asset_items ai
JOIN (
    SELECT id, CONCAT('TRACK-', DATE_FORMAT(NOW(), '%Y%m%d'), '-', LPAD(ROW_NUMBER() OVER (ORDER BY id), 4, '0')) AS new_tid
    FROM asset_items
    WHERE tracking_id IS NULL OR tracking_id = ''
) t ON ai.id = t.id
SET ai.tracking_id = t.new_tid;
