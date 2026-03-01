/**
 * IMPLEMENTATION VERIFICATION SCRIPT
 * 
 * Run these queries to verify the holiday company assignment system is working correctly
 * This script checks:
 * 1. Database schema is correct
 * 2. Tables exist and have data
 * 3. Foreign keys are working
 * 4. Holiday filtering is functional
 */

-- ============================================
-- VERIFICATION 1: Check if tables exist
-- ============================================
SHOW TABLES LIKE 'holiday_companies';
SHOW TABLES LIKE 'emp_holidays';
SHOW TABLES LIKE 'companies';

-- ============================================
-- VERIFICATION 2: Check table structure
-- ============================================
DESCRIBE holiday_companies;

-- Expected columns:
-- id, holiday_id, company_id, created_at

-- Check foreign keys
SELECT CONSTRAINT_NAME, COLUMN_NAME, TABLE_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME 
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
WHERE TABLE_NAME = 'holiday_companies';

-- ============================================
-- VERIFICATION 3: Count records
-- ============================================
SELECT 'emp_holidays' as table_name, COUNT(*) as total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active
FROM emp_holidays;

SELECT 'holiday_companies' as table_name, COUNT(*) as total
FROM holiday_companies;

SELECT 'companies' as table_name, COUNT(*) as total
FROM companies;

-- ============================================
-- VERIFICATION 4: Show holidays with company assignment
-- ============================================
SELECT 
    h.id,
    h.holiday_name,
    h.start_date,
    h.end_date,
    h.total_days,
    h.is_active,
    COUNT(hc.company_id) as assigned_companies,
    GROUP_CONCAT(c.comp_name SEPARATOR ', ') as company_names
FROM emp_holidays h
LEFT JOIN holiday_companies hc ON h.id = hc.holiday_id
LEFT JOIN companies c ON hc.company_id = c.id
GROUP BY h.id
ORDER BY h.start_date DESC;

-- ============================================
-- VERIFICATION 5: Check for holidays without company assignment
-- ============================================
SELECT 
    h.id,
    h.holiday_name,
    h.start_date,
    h.end_date,
    'NO COMPANIES ASSIGNED' as warning
FROM emp_holidays h
LEFT JOIN holiday_companies hc ON h.id = hc.holiday_id
WHERE hc.holiday_id IS NULL
AND h.is_active = 1
ORDER BY h.start_date;

-- ============================================
-- VERIFICATION 6: Test vacation deduction for each company
-- ============================================
-- This query simulates what happens when an employee takes vacation

-- For each company, show:
-- 1. Employee in that company
-- 2. Their upcoming holidays
-- 3. Sample vacation calculation

SELECT 
    e.emp_id,
    e.name,
    e.comp_no,
    c.comp_name,
    GROUP_CONCAT(h.holiday_name SEPARATOR ', ') as holidays,
    SUM(h.total_days) as total_holiday_days
FROM employees e
JOIN companies c ON e.comp_no = c.id
LEFT JOIN holiday_companies hc ON c.id = hc.company_id
LEFT JOIN emp_holidays h ON hc.holiday_id = h.id AND h.is_active = 1
WHERE e.status = 1
GROUP BY e.emp_id
LIMIT 10;

-- ============================================
-- VERIFICATION 7: Test the holiday filtering query
-- ============================================
-- This is the exact query used in vacation_calculator.php
-- Test it with specific dates

SET @emp_id = 'EMP001';  -- Change to a real employee ID
SET @vac_start = '2026-02-26';
SET @vac_end = '2026-03-10';
SET @company_id = 1;  -- Change to test different companies

-- Get employee's company
SELECT @emp_company := comp_no FROM employees WHERE emp_id = @emp_id LIMIT 1;

-- Show holidays that would be used for deduction
SELECT h.id, h.holiday_name, h.start_date, h.end_date, h.total_days
FROM emp_holidays h
LEFT JOIN holiday_companies hc ON h.id = hc.holiday_id
WHERE h.is_active = 1 
AND h.start_date <= @vac_end 
AND h.end_date >= @vac_start
AND (hc.company_id = @emp_company OR hc.holiday_id IS NULL);

-- ============================================
-- VERIFICATION 8: Indexes check
-- ============================================
SHOW INDEXES FROM holiday_companies;

-- Expected indexes:
-- PRIMARY (id)
-- UNIQUE unique_holiday_company (holiday_id, company_id)
-- idx_holiday_company_lookup (holiday_id, company_id)
-- idx_company_holiday_lookup (company_id, holiday_id)

-- ============================================
-- VERIFICATION 9: Test referential integrity
-- ============================================
-- Try to insert a record with invalid holiday_id (should fail)
-- INSERT INTO holiday_companies (holiday_id, company_id) VALUES (99999, 1);

-- Try to insert a record with invalid company_id (should fail)
-- INSERT INTO holiday_companies (holiday_id, company_id) VALUES (1, 99999);

-- ============================================
-- VERIFICATION 10: Data quality checks
-- ============================================
-- Check for orphaned records (shouldn't exist due to foreign keys)
SELECT COUNT(*) as orphaned_holiday_records
FROM holiday_companies hc
WHERE NOT EXISTS (
  SELECT 1 FROM emp_holidays h WHERE h.id = hc.holiday_id
);

SELECT COUNT(*) as orphaned_company_records
FROM holiday_companies hc
WHERE NOT EXISTS (
  SELECT 1 FROM companies c WHERE c.id = hc.company_id
);

-- ============================================
-- IF ALL CHECKS PASS:
-- ============================================
-- 1. Tables exist ✓
-- 2. Foreign keys created ✓
-- 3. Indexes created ✓
-- 4. Data consistency ✓
-- 5. Filtering query works ✓
-- 
-- Your holiday company assignment system is ready to use!
-- ============================================
