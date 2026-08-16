-- Run this AFTER add_employee_medical_insurance.sql and AFTER deploying the updated code
-- (view_employee.php, employee_profile.php, profile.php, edit_employee.php,
-- new_comp_employee.php, graphical_reports.php, reports.php, ajaxReports.php, find_birthday.php).
--
-- Order matters:
--   1. Migrate existing employees.insurance_* data into the new tables (below).
--   2. Verify the counts (queries included).
--   3. Only then run the DROP COLUMN statement at the bottom.
--
-- BACK UP the `employees` table before running the DROP - this step is irreversible.

-- Step 1a: Insurance No / Expiry -> employee_medical_insurance (as the initial 'active' record).
-- Skips employees who already have an active record there (won't clobber anything entered
-- through the new UI since this was rolled out).
INSERT INTO employee_medical_insurance (emp_id, insurance_no, medical_expiry, status, created_by)
SELECT e.emp_id, NULLIF(e.insurance_no, ''),
       CASE WHEN e.insurance_exp REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$' THEN e.insurance_exp ELSE NULL END,
       'active', 'MIGRATION'
FROM employees e
WHERE (NULLIF(e.insurance_no, '') IS NOT NULL OR (e.insurance_exp REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$'))
  AND NOT EXISTS (SELECT 1 FROM employee_medical_insurance mi WHERE mi.emp_id = e.emp_id AND mi.status = 'active');

-- Step 1b: Insurance Class -> employee_additional_info.medical_class.
-- Legacy 'VIP' maps to 'A+' (matches the Grade 8 "A+VIP" combined class).
-- Inserts a new row only if the employee has none yet in employee_additional_info.
INSERT INTO employee_additional_info (emp_id, medical_class, updated_by)
SELECT e.emp_id, CASE WHEN e.insurance_class = 'VIP' THEN 'A+' ELSE e.insurance_class END, 'MIGRATION'
FROM employees e
WHERE e.insurance_class IN ('CLT','C','B','A','VIP')
  AND NOT EXISTS (SELECT 1 FROM employee_additional_info a WHERE a.emp_id = e.emp_id);

-- Step 1c: same, but for employees who already have an employee_additional_info row with
-- medical_class still empty (don't overwrite anything already set through the new UI).
UPDATE employee_additional_info a
JOIN employees e ON e.emp_id = a.emp_id
SET a.medical_class = CASE WHEN e.insurance_class = 'VIP' THEN 'A+' ELSE e.insurance_class END
WHERE e.insurance_class IN ('CLT','C','B','A','VIP')
  AND (a.medical_class IS NULL OR a.medical_class = '');

-- Step 2: Verify before dropping - these two counts should match.
-- SELECT COUNT(*) FROM employees WHERE insurance_class IN ('CLT','C','B','A','VIP');
-- SELECT COUNT(*) FROM employee_additional_info WHERE medical_class IS NOT NULL AND medical_class != '';

-- Step 3: ONLY after verifying the counts above match, run this (irreversible):
-- ALTER TABLE employees DROP COLUMN insurance_no, DROP COLUMN insurance_exp, DROP COLUMN insurance_class;
