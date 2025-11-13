-- Clean up test vacation for employee 2539
-- This will allow you to test the new vacation/leave application system

-- Option 1: Delete the test vacation completely
DELETE FROM emp_vacation WHERE request_inv_no = 'LV-20251112-2539-7e50';

-- If you need to check other test vacations for today:
-- SELECT request_inv_no, emp_id, vac_type, start_date, return_date, current_status 
-- FROM emp_vacation 
-- WHERE DATE(start_date) = '2025-11-12' 
-- ORDER BY id DESC;

-- To see all vacation/leave requests by prefix:
-- SELECT 
--     CASE 
--         WHEN request_inv_no LIKE 'VAC-%' THEN 'Annual Vacation'
--         WHEN request_inv_no LIKE 'LV-%' THEN 'Leave Request'
--         ELSE 'Other'
--     END as request_type,
--     COUNT(*) as total
-- FROM emp_vacation
-- GROUP BY request_type;
