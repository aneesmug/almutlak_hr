<?php
// Vacation Asset Clearance Migration (Run via browser or CLI)
// Adds vacation_asset_decisions table used to route asset-return clearance
// (Keep / Return, decided by the direct manager) to the correct department
// (IT for laptops, Administration for mobiles/SIMs/cars, etc.) based on
// assets.clearance_dept_id.

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

if (!isset($pdo) || $pdo === null) {
    try {
        $pdo = getDbConnection();
    } catch (Throwable $e) {
        http_response_code(500);
        echo "DB Error: " . htmlspecialchars($e->getMessage());
        exit;
    }
}

$pdo->exec("CREATE TABLE IF NOT EXISTS vacation_asset_decisions (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Widen status enum for tables created before "needs_assignment" existed
// (sys-admin must pick a clearance handler when the department manager is
// the same person as the direct manager who already approved).
try {
    $pdo->exec("ALTER TABLE vacation_asset_decisions MODIFY status ENUM('n_a','pending','needs_assignment','cleared') NOT NULL DEFAULT 'n_a'");
} catch (Throwable $e) {
    echo "Warning: status enum widen issue: " . htmlspecialchars($e->getMessage()) . "\n";
}

// Per-asset condition recorded at clearance time (tables created before this existed).
try {
    $pdo->exec("ALTER TABLE vacation_asset_decisions ADD COLUMN condition_on_return ENUM('Good','Damage','Lost','Buy','Other') NULL AFTER cleared_at");
} catch (Throwable $e) {
    // Column already exists - fine.
}

// Cars have no condition field yet (employee_assets already has asset_condition) -
// add the equivalent so a returned car's condition can be recorded too.
try {
    $pdo->exec("SET SESSION sql_mode = ''"); // cars_drv has a pre-existing invalid created_at default that blocks ALTER under strict mode
    $pdo->exec("ALTER TABLE cars_drv ADD COLUMN car_condition ENUM('Good','Damage','Lost','Buy','Other') NULL AFTER rtn_date");
} catch (Throwable $e) {
    // Column already exists - fine.
}

// Links each decision to the specific chain step (request_approvers row) it
// belongs to. Needed because the same person can hold two separate duties on
// one request (e.g. directly clearing a laptop as IT manager, while also being
// the fallback administrator who must ASSIGN a handler for a conflicted
// Administration asset) - without this, both duties' items would surface
// together regardless of which chain step is actually their current turn.
try {
    $pdo->exec("ALTER TABLE vacation_asset_decisions ADD COLUMN request_approver_id INT NULL AFTER approver_id");
} catch (Throwable $e) {
    // Column already exists - fine.
}

// One row per (asset type, handler) - an asset type can have several eligible
// handlers (backup coverage); resolution picks the first still-active one.
$pdo->exec("CREATE TABLE IF NOT EXISTS asset_clearance_handlers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    asset_id INT UNSIGNED NOT NULL,
    handler_emp_id INT NOT NULL,
    updated_by INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_asset_handler (asset_id, handler_emp_id),
    INDEX idx_asset (asset_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Widen older single-handler-per-asset tables to allow multiple.
try {
    $pdo->exec("ALTER TABLE asset_clearance_handlers DROP INDEX uniq_asset");
    $pdo->exec("ALTER TABLE asset_clearance_handlers ADD UNIQUE KEY uniq_asset_handler (asset_id, handler_emp_id)");
} catch (Throwable $e) {
    // Already migrated, or index never existed under the old name - fine either way.
}

// Correct Car's clearance department: Transportation (17) has no active staff,
// and Administration already handles Mobile/SIM per business rule - Cars route
// there too instead of into a department nobody is assigned to.
try {
    $pdo->exec("UPDATE assets SET clearance_dept_id = 1 WHERE name = 'Car' AND clearance_dept_id = 17");
} catch (Throwable $e) {
    echo "Warning: Car clearance_dept_id fix issue: " . htmlspecialchars($e->getMessage()) . "\n";
}

header('Content-Type: text/plain');
echo "Vacation asset clearance migration executed successfully.\n";
