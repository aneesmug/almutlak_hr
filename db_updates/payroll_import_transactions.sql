-- ============================================================================
-- Payroll manager-file import: transaction tracking / rollback / re-upload
-- Date: 2026-10-19
--
-- Every payroll_benefits / payroll_deductions line written by a manager Excel
-- import is logged here (with the row's previous values when it overwrote an
-- existing line), so HR can:
--   * see imported lines per file  (payroll_checklist_report.php -> Imported Transactions tab)
--   * delete one line / all lines of a file (insert = delete, update = restore previous)
--   * re-upload the corrected Excel with the same checkpoint (old lines are replaced)
--
-- The app also creates this table automatically on first use
-- (ensurePayrollImportTransactionsTable() in includes/payroll_approval_helpers.php),
-- so running this file is only needed for servers where the DB user cannot CREATE.
-- Safe to run more than once.
--
-- No other tables were altered. Upload "rolled back / replaced" status is kept in
-- the per-request JSON meta files under assets/payroll_manager_uploads/, not in DB.
-- ============================================================================

CREATE TABLE IF NOT EXISTS `payroll_import_transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `checkpoint_code` VARCHAR(255) NOT NULL,
    `source_file_id` VARCHAR(100) DEFAULT NULL,
    `request_inv_no` VARCHAR(255) DEFAULT NULL,
    `emp_id` VARCHAR(50) NOT NULL,
    `month_year` VARCHAR(7) NOT NULL,
    `record_type` ENUM('benefit','deduction') NOT NULL,
    `record_id` INT NOT NULL,
    `record_name` VARCHAR(255) NOT NULL,
    `calculation_type` VARCHAR(50) DEFAULT NULL,
    `hours` INT DEFAULT NULL,
    `minutes` INT DEFAULT NULL,
    `days` DECIMAL(10,2) DEFAULT NULL,
    `amount` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `action` ENUM('insert','update') NOT NULL,
    `previous_data` LONGTEXT DEFAULT NULL,
    `imported_by` VARCHAR(50) DEFAULT NULL,
    `imported_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `rolled_back_at` DATETIME DEFAULT NULL,
    `rolled_back_by` VARCHAR(50) DEFAULT NULL,
    INDEX `idx_checkpoint` (`checkpoint_code`),
    INDEX `idx_source_file` (`source_file_id`),
    INDEX `idx_request_month` (`request_inv_no`, `month_year`),
    INDEX `idx_record` (`record_type`, `record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
