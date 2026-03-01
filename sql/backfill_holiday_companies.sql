/**
 * BONUS: HOLIDAY BACKFILL SCRIPT
 * 
 * This script can be used to assign existing holidays to all companies
 * Use ONLY if you want all holidays to apply to all companies (global holidays)
 * 
 * WARNING: Only run this ONCE. Running it multiple times will create duplicate entries.
 * If you need to redo this, delete all records from holiday_companies first.
 * 
 * Step 1: Verify the data BEFORE running
 * Step 2: Backup your database
 * Step 3: Uncomment and run the INSERT statement
 */

-- First, check how many records will be created
-- This query shows you the count of (holiday, company) pairs that will be inserted
SELECT COUNT(*) as records_to_insert
FROM `emp_holidays` h 
CROSS JOIN `companies` c 
WHERE h.`is_active` = 1
AND NOT EXISTS (
  SELECT 1 FROM `holiday_companies` hc 
  WHERE hc.`holiday_id` = h.`id`
  AND hc.`company_id` = c.`id`
);

-- Preview the data that will be inserted
SELECT 
    h.`id` as holiday_id,
    h.`holiday_name`,
    c.`id` as company_id,
    c.`comp_name`
FROM `emp_holidays` h 
CROSS JOIN `companies` c 
WHERE h.`is_active` = 1
AND NOT EXISTS (
  SELECT 1 FROM `holiday_companies` hc 
  WHERE hc.`holiday_id` = h.`id`
  AND hc.`company_id` = c.`id`
)
ORDER BY h.`id`, c.`id`;

-- **UNCOMMENT THE LINE BELOW TO RUN THE BACKFILL**
-- **THIS WILL INSERT ALL EXISTING HOLIDAYS TO ALL COMPANIES**
/*
INSERT INTO `holiday_companies` (`holiday_id`, `company_id`) 
SELECT h.`id`, c.`id` 
FROM `emp_holidays` h 
CROSS JOIN `companies` c 
WHERE h.`is_active` = 1
AND NOT EXISTS (
  SELECT 1 FROM `holiday_companies` hc 
  WHERE hc.`holiday_id` = h.`id`
  AND hc.`company_id` = c.`id`
);
*/

-- After running the backfill, verify the results
-- SELECT COUNT(*) FROM holiday_companies;
-- SELECT h.holiday_name, GROUP_CONCAT(c.comp_name) as companies
-- FROM holiday_companies hc
-- JOIN emp_holidays h ON hc.holiday_id = h.id
-- JOIN companies c ON hc.company_id = c.id
-- GROUP BY h.id
-- ORDER BY h.holiday_name;
