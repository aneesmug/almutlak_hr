-- Step 1: Check request #2108 (Fly + Annual, 15 days, below the old 20-day gate).
-- Expect vac_type='Fly', fly_type='annual', vacdays=15, vacation_salary_type currently
-- likely 'end_of_service' because of the bug (day-count gate wrongly applied to Fly too).
SELECT id, emp_id, vac_type, fly_type, vacdays, vacation_salary_type, current_status, review, request_inv_no
FROM emp_vacation
WHERE id = 2108;

-- Step 2: ONLY run this if Step 1 confirms vac_type='Fly', fly_type='annual',
-- vacation_salary_type='end_of_service'. This corrects the stored value back to the
-- payroll default so the employee's vacation salary pays out with this month's payroll
-- instead of being deferred to end-of-service.
-- Do NOT run this if vacation_salary_type is already 'payroll', or if the employee/HR
-- actually intended 'end_of_service' through some other path.
-- UPDATE emp_vacation SET vacation_salary_type = 'payroll' WHERE id = 2108 AND vac_type = 'Fly' AND fly_type = 'annual';
