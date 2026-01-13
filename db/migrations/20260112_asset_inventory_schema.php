<?php
// Asset Inventory Schema Migration (Run via browser or CLI)
// Moves DDL out of runtime endpoints and centralizes schema management.

if (!defined('SKIP_PAGE_ACCESS_CONTROL')) {
    define('SKIP_PAGE_ACCESS_CONTROL', true);
}

try {
    require_once __DIR__ . '/../includes/init.php';
    require_once __DIR__ . '/../includes/helper_functions.php';
} catch (Throwable $e) {
    http_response_code(500);
    echo "Include error: " . htmlspecialchars($e->getMessage());
    exit;
}

// Use PDO for migrations
if (!isset($pdo) || $pdo === null) {
    try {
        $pdo = getDbConnection();
    } catch (Throwable $e) {
        http_response_code(500);
        echo "DB Error: " . htmlspecialchars($e->getMessage());
        exit;
    }
}

function generate_tracking_id(PDO $pdo): string {
    $datePart = date('Ymd');
    $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM asset_items WHERE tracking_id LIKE :pattern");
    $stmt->execute(['pattern' => 'TRACK-' . $datePart . '-%']);
    $count = (int) $stmt->fetchColumn();
    $next = str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    return 'TRACK-' . $datePart . '-' . $next;
}

// 1) Create tables if missing
$pdo->exec("CREATE TABLE IF NOT EXISTS assets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(191) NOT NULL,
    category VARCHAR(120) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uniq_asset_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$pdo->exec("CREATE TABLE IF NOT EXISTS asset_items (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$pdo->exec("CREATE TABLE IF NOT EXISTS employee_assets (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// 2) Migrations: asset_items.tracking_id + index + serial_number nullable
try {
    $col = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'asset_items' AND COLUMN_NAME = 'tracking_id'")->fetch();
    if (!$col) {
        $pdo->exec("ALTER TABLE asset_items ADD COLUMN tracking_id VARCHAR(120) UNIQUE AFTER asset_id");
        $pdo->exec("ALTER TABLE asset_items ADD INDEX idx_tracking (tracking_id)");
    }
} catch (Throwable $e) {
    echo "Warning: tracking_id migration issue: " . htmlspecialchars($e->getMessage()) . "\n";
}

try {
    $nullableCheck = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'asset_items' AND COLUMN_NAME = 'serial_number' AND IS_NULLABLE = 'NO'")->fetch();
    if ($nullableCheck) {
        $pdo->exec("ALTER TABLE asset_items MODIFY COLUMN serial_number VARCHAR(120) NULL");
    }
} catch (Throwable $e) {
    echo "Warning: serial_number nullable migration issue: " . htmlspecialchars($e->getMessage()) . "\n";
}

// 3) Migrations: employee_assets.asset_condition
try {
    $condCol = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'employee_assets' AND COLUMN_NAME = 'asset_condition'")->fetch();
    if (!$condCol) {
        $pdo->exec("ALTER TABLE employee_assets ADD COLUMN asset_condition ENUM('Good', 'Damage', 'Lost', 'Buy', 'Other') NULL AFTER return_date");
    }
} catch (Throwable $e) {
    echo "Warning: asset_condition migration issue: " . htmlspecialchars($e->getMessage()) . "\n";
}

// 4) Populate tracking_id for existing asset_items if NULL
try {
    $stmt = $pdo->query("SELECT id FROM asset_items WHERE tracking_id IS NULL OR tracking_id = ''");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $tid = generate_tracking_id($pdo);
        $upd = $pdo->prepare("UPDATE asset_items SET tracking_id = :tid WHERE id = :id");
        $upd->execute(['tid' => $tid, 'id' => $r['id']]);
    }
} catch (Throwable $e) {
    echo "Warning: tracking_id population issue: " . htmlspecialchars($e->getMessage()) . "\n";
}

header('Content-Type: text/plain');
echo "Asset Inventory migrations executed successfully.\n";
