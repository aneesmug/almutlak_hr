-- ========================================================================
-- ALTER TABLE: Store payroll hours with minutes/decimals
-- Purpose: Preserve overtime and deduction minutes by allowing decimal hours
-- Date: 2026-07-01
-- ========================================================================

SET @schema_name = DATABASE();

SET @payroll_benefits_hours_type = (
    SELECT DATA_TYPE
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'payroll_benefits'
      AND COLUMN_NAME = 'hours'
    LIMIT 1
);

SET @payroll_benefits_hours_scale = (
    SELECT NUMERIC_SCALE
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'payroll_benefits'
      AND COLUMN_NAME = 'hours'
    LIMIT 1
);

SET @sql = IF(
    @payroll_benefits_hours_type IS NOT NULL
    AND NOT (@payroll_benefits_hours_type = 'decimal' AND COALESCE(@payroll_benefits_hours_scale, 0) = 2),
    'ALTER TABLE `payroll_benefits` MODIFY COLUMN `hours` DECIMAL(10,2) DEFAULT NULL',
    'SELECT "payroll_benefits.hours already supports decimals"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @payroll_deductions_hours_type = (
    SELECT DATA_TYPE
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'payroll_deductions'
      AND COLUMN_NAME = 'hours'
    LIMIT 1
);

SET @payroll_deductions_hours_scale = (
    SELECT NUMERIC_SCALE
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'payroll_deductions'
      AND COLUMN_NAME = 'hours'
    LIMIT 1
);

SET @sql = IF(
    @payroll_deductions_hours_type IS NOT NULL
    AND NOT (@payroll_deductions_hours_type = 'decimal' AND COALESCE(@payroll_deductions_hours_scale, 0) = 2),
    'ALTER TABLE `payroll_deductions` MODIFY COLUMN `hours` DECIMAL(10,2) DEFAULT NULL',
    'SELECT "payroll_deductions.hours already supports decimals"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ========================================================================
-- RESULT:
-- - 1 hour 30 minutes will now save as 1.50
-- - 2 hours 15 minutes will now save as 2.25
-- - Existing integer hours remain valid
-- ========================================================================