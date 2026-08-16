-- Moves Medical Class from employee_additional_info into employee_medical_insurance,
-- since it's insurance-related and renews yearly along with Insurance No / Amount / Expiry.
-- Run this AFTER add_employee_medical_insurance.sql and AFTER deploying the updated code
-- (view_employee.php, employee_profile.php, profile.php, employeeAdditionalInfoHandler.php,
-- employeeMedicalInsuranceHandler.php).

-- Step 1: add the column.
ALTER TABLE employee_medical_insurance ADD COLUMN medical_class ENUM('CLT','C','B','A','A+') DEFAULT NULL AFTER medical_expiry;

-- Step 2a: copy medical_class into any existing ACTIVE insurance row.
UPDATE employee_medical_insurance mi
JOIN employee_additional_info a ON a.emp_id = mi.emp_id
SET mi.medical_class = a.medical_class
WHERE mi.status = 'active' AND a.medical_class IS NOT NULL AND a.medical_class != '';

-- Step 2b: for employees with a medical_class but no active insurance row yet, create a
-- class-only active row (insurance_no/med_insurance/medical_expiry stay NULL until an
-- actual renewal is entered).
INSERT INTO employee_medical_insurance (emp_id, medical_class, status, created_by)
SELECT a.emp_id, a.medical_class, 'active', 'MIGRATION'
FROM employee_additional_info a
WHERE a.medical_class IS NOT NULL AND a.medical_class != ''
  AND NOT EXISTS (SELECT 1 FROM employee_medical_insurance mi WHERE mi.emp_id = a.emp_id AND mi.status = 'active');

-- Step 3: verify before dropping - these two counts should match.
-- SELECT COUNT(*) FROM employee_additional_info WHERE medical_class IS NOT NULL AND medical_class != '';
-- SELECT COUNT(*) FROM employee_medical_insurance WHERE status = 'active' AND medical_class IS NOT NULL;

-- Step 4: ONLY after verifying, drop the old column (irreversible).
-- ALTER TABLE employee_additional_info DROP COLUMN medical_class;

-- Step 5: register the new Special Access key's label so it shows in App Settings ->
-- Special Access (get_special_access_page_labels() in special_access_helper.php already
-- returns it dynamically once the code is deployed - no separate DB row needed here).
