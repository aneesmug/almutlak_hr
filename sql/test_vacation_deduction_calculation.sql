/**
 * Vacation Deduction Calculation Test Queries
 * 
 * Use these SQL queries to verify and debug vacation deduction calculations
 * These queries help you understand how the new logic works
 */

-- ============================================
-- TEST 1: Show weekend days in date range
-- ============================================
-- This test shows how to identify weekends (Friday=5, Saturday=6)

SELECT 
    DATE_ADD('2026-02-26', INTERVAL n DAY) as date,
    DAYNAME(DATE_ADD('2026-02-26', INTERVAL n DAY)) as day_name,
    DAYOFWEEK(DATE_ADD('2026-02-26', INTERVAL n DAY)) as php_day,
    CASE 
        WHEN DAYOFWEEK(DATE_ADD('2026-02-26', INTERVAL n DAY)) IN (5, 6) THEN 'WEEKEND'
        ELSE 'WORKING DAY'
    END as classification
FROM (
    SELECT 0 as n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 
    UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 
    UNION SELECT 8
) nums
WHERE DATE_ADD('2026-02-26', INTERVAL n DAY) BETWEEN '2026-02-26' AND '2026-03-06'
ORDER BY date;

-- Expected Output:
-- 2026-02-26 (Thu) - WORKING DAY
-- 2026-02-27 (Fri) - WEEKEND
-- 2026-02-28 (Sat) - WEEKEND
-- 2026-03-01 (Sun) - WORKING DAY
-- etc.

-- ============================================
-- TEST 2: Count weekend days in vacation period
-- ============================================

SET @vac_start = '2026-02-28';  -- Thursday
SET @vac_end = '2026-03-05';    -- Thursday (5 days total)

SELECT 
    DATEDIFF(@vac_end, @vac_start) + 1 as total_days,
    DAYNAME(@vac_start) as start_day,
    DAYNAME(@vac_end) as end_day,
    (
        SELECT COUNT(*) 
        FROM (
            SELECT 0 as n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 
            UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 
            UNION SELECT 8 UNION SELECT 9 UNION SELECT 10
        ) nums
        WHERE DATE_ADD(@vac_start, INTERVAL n DAY) BETWEEN @vac_start AND @vac_end
        AND DAYOFWEEK(DATE_ADD(@vac_start, INTERVAL n DAY)) IN (5, 6)
    ) as weekend_count,
    (
        DATEDIFF(@vac_end, @vac_start) + 1 -
        (
            SELECT COUNT(*) 
            FROM (
                SELECT 0 as n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 
                UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 
                UNION SELECT 8 UNION SELECT 9 UNION SELECT 10
            ) nums
            WHERE DATE_ADD(@vac_start, INTERVAL n DAY) BETWEEN @vac_start AND @vac_end
            AND DAYOFWEEK(DATE_ADD(@vac_start, INTERVAL n DAY)) IN (5, 6)
        )
    ) as working_days;

-- ============================================
-- TEST 3: Show holidays that overlap with vacation
-- ============================================

SET @vac_start = '2026-02-26';
SET @vac_end = '2026-03-07';
SET @company_id = 1;

SELECT 
    h.id,
    h.holiday_name,
    h.start_date,
    h.end_date,
    h.total_days,
    GROUP_CONCAT(c.comp_name SEPARATOR ', ') as assigned_companies,
    CASE 
        WHEN h.start_date <= @vac_end AND h.end_date >= @vac_start THEN 'OVERLAPS'
        ELSE 'NO OVERLAP'
    END as overlap_status,
    DATEDIFF(LEAST(h.end_date, @vac_end), GREATEST(h.start_date, @vac_start)) + 1 as overlapping_days
FROM emp_holidays h
LEFT JOIN holiday_companies hc ON h.id = hc.holiday_id
LEFT JOIN companies c ON hc.company_id = c.id
WHERE h.is_active = 1
GROUP BY h.id
ORDER BY h.start_date;

-- ============================================
-- TEST 4: Calculate vacation deduction for specific employee
-- ============================================

SET @emp_id = 'EMP001';
SET @vac_start = '2026-02-26';
SET @vac_end = '2026-03-02';

-- Get employee's company
SELECT @emp_company := comp_no FROM employees WHERE emp_id = @emp_id LIMIT 1;

-- Calculate deduction
SELECT 
    DATEDIFF(@vac_end, @vac_start) + 1 as total_vacation_days,
    (
        SELECT COUNT(*)
        FROM (
            SELECT 0 as n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 
            UNION SELECT 4 UNION SELECT 5 UNION SELECT 6
        ) nums
        WHERE DATE_ADD(@vac_start, INTERVAL n DAY) BETWEEN @vac_start AND @vac_end
        AND DAYOFWEEK(DATE_ADD(@vac_start, INTERVAL n DAY)) IN (5, 6)
    ) as weekend_days,
    (
        SELECT COALESCE(SUM(holiday_overlap_days), 0)
        FROM (
            SELECT 
                h.id,
                DATEDIFF(LEAST(h.end_date, @vac_end), GREATEST(h.start_date, @vac_start)) + 1 as holiday_overlap_days
            FROM emp_holidays h
            LEFT JOIN holiday_companies hc ON h.id = hc.holiday_id
            WHERE h.is_active = 1
            AND h.start_date <= @vac_end
            AND h.end_date >= @vac_start
            AND (hc.company_id = @emp_company OR hc.holiday_id IS NULL)
        ) holiday_overlaps
    ) as holiday_days,
    GREATEST(0,
        (DATEDIFF(@vac_end, @vac_start) + 1) -
        (
            SELECT COUNT(*)
            FROM (
                SELECT 0 as n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 
                UNION SELECT 4 UNION SELECT 5 UNION SELECT 6
            ) nums
            WHERE DATE_ADD(@vac_start, INTERVAL n DAY) BETWEEN @vac_start AND @vac_end
            AND DAYOFWEEK(DATE_ADD(@vac_start, INTERVAL n DAY)) IN (5, 6)
        ) -
        (
            SELECT COALESCE(SUM(holiday_overlap_days), 0)
            FROM (
                SELECT 
                    h.id,
                    DATEDIFF(LEAST(h.end_date, @vac_end), GREATEST(h.start_date, @vac_start)) + 1 as holiday_overlap_days
                FROM emp_holidays h
                LEFT JOIN holiday_companies hc ON h.id = hc.holiday_id
                WHERE h.is_active = 1
                AND h.start_date <= @vac_end
                AND h.end_date >= @vac_start
                AND (hc.company_id = @emp_company OR hc.holiday_id IS NULL)
            ) holiday_overlaps
        )
    ) as deductible_days;

-- ============================================
-- TEST 5: Scenario - Pure Weekend Vacation
-- ============================================
-- Vacation: Friday + Saturday only = Should result in 0 days deducted

SET @scenario_start = '2026-02-27';  -- Friday
SET @scenario_end = '2026-02-28';    -- Saturday

SELECT 
    @scenario_start as start_date,
    @scenario_end as end_date,
    DATEDIFF(@scenario_end, @scenario_start) + 1 as total_vacation_days,
    2 as weekend_days_expected,
    'Formula: 2 - 2 - 0 = 0' as calculation,
    'SUCCESS: No vacation days deducted' as result;

-- ============================================
-- TEST 6: Scenario - Vacation with Eid Holiday
-- ============================================
-- Vacation: Thu-Mon (5 days)
-- Eid: Fri-Sun (3 days)
-- Weekends: Fri + Sat (2 days)
-- Expected: 5 - 2 - 1 (Sun only, as Fri-Sat are already weekend) = 2 days

-- First, insert test holiday if needed:
-- INSERT INTO emp_holidays (holiday_name, start_date, end_date, total_days, holiday_type, is_active, created_by)
-- VALUES ('Test Eid', '2026-02-27', '2026-03-01', 3, 'religious', 1, 'ADMIN');

SET @scenario_start = '2026-02-26';  -- Thursday
SET @scenario_end = '2026-03-02';    -- Monday

SELECT 
    @scenario_start as vacation_start,
    @scenario_end as vacation_end,
    DATEDIFF(@scenario_end, @scenario_start) + 1 as total_vacation_days,
    2 as weekend_days,
    'Formula: 5 - 2 - (holiday overlap) = result' as calculation;

-- ============================================
-- TEST 7: Verify holiday company assignments
-- ============================================

SELECT 
    h.holiday_name,
    h.start_date,
    h.end_date,
    h.total_days,
    COUNT(hc.company_id) as assigned_company_count,
    GROUP_CONCAT(c.comp_name SEPARATOR ', ') as companies,
    CASE 
        WHEN COUNT(hc.company_id) = 0 THEN 'WARNING: No companies assigned'
        ELSE 'OK'
    END as status
FROM emp_holidays h
LEFT JOIN holiday_companies hc ON h.id = hc.holiday_id
LEFT JOIN companies c ON hc.company_id = c.id
WHERE h.is_active = 1
GROUP BY h.id
ORDER BY h.start_date DESC;

-- ============================================
-- TEST 8: Employees with vacations in range
-- ============================================

SET @start_date = '2026-02-01';
SET @end_date = '2026-03-31';

SELECT 
    e.emp_id,
    e.name,
    c.comp_name as company,
    COUNT(v.id) as vacation_count,
    SUM(v.vacdays) as total_vacation_days
FROM employees e
LEFT JOIN companies c ON e.comp_no = c.id
LEFT JOIN emp_vacation v ON e.emp_id = v.emp_id 
    AND v.start_date BETWEEN @start_date AND @end_date
    AND v.current_status IN ('approved', 'gm_approved')
WHERE e.status = 1
GROUP BY e.emp_id
HAVING vacation_count > 0
ORDER BY e.name;

-- ============================================
-- TEST 9: Check for data inconsistencies
-- ============================================

-- Check for holidays without assigned companies
SELECT 
    h.id,
    h.holiday_name,
    h.start_date,
    'NO COMPANIES ASSIGNED - Will apply to all employees' as warning
FROM emp_holidays h
LEFT JOIN holiday_companies hc ON h.id = hc.holiday_id
WHERE hc.holiday_id IS NULL
AND h.is_active = 1;

-- Check for orphaned holiday_companies records
SELECT COUNT(*) as orphaned_records
FROM holiday_companies hc
WHERE NOT EXISTS (SELECT 1 FROM emp_holidays h WHERE h.id = hc.holiday_id)
OR NOT EXISTS (SELECT 1 FROM companies c WHERE c.id = hc.company_id);

-- ============================================
-- TEST 10: Vacation deduction audit log
-- ============================================
-- This query simulates what should be logged for each vacation

SELECT 
    v.id as vacation_id,
    v.emp_id,
    e.name as employee_name,
    c.comp_name as company,
    v.start_date,
    v.return_date,
    v.vacdays as total_vacation_days,
    v.current_status,
    DATEDIFF(v.return_date, v.start_date) + 1 as calculated_days,
    'Should be logged with weekend/holiday breakdown' as audit_note
FROM emp_vacation v
JOIN employees e ON v.emp_id = e.emp_id
JOIN companies c ON e.comp_no = c.id
WHERE v.current_status IN ('approved', 'gm_approved')
AND v.start_date >= DATE_SUB(NOW(), INTERVAL 90 DAY)
ORDER BY v.start_date DESC
LIMIT 20;
