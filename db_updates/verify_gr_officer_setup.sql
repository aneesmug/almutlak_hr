-- Verify GR Officer Setup

-- 1. Check if GR Officer user exists
SELECT 
    e.emp_id,
    e.name as employee_name,
    al.username,
    al.user_type,
    al.emp_type,
    e.status as employee_status
FROM employees e
JOIN admin_login al ON e.emp_id = al.emp_id
WHERE al.user_type = 'gr_officer';

-- 2. Check if GR Officer is being added to Fly | Annual vacation approval chains
SELECT 
    v.request_inv_no,
    v.emp_id,
    e.name as employee_name,
    v.vac_type as remarks,
    v.fly_type,
    v.current_status,
    ra.approver_id,
    emp_approver.name as approver_name,
    ra.approval_level,
    ra.status as approver_status,
    al.user_type as approver_role
FROM emp_vacation v
JOIN employees e ON v.emp_id = e.emp_id
JOIN request_approvers ra ON v.request_inv_no = ra.request_inv_no
JOIN employees emp_approver ON ra.approver_id = emp_approver.emp_id
JOIN admin_login al ON emp_approver.emp_id = al.emp_id
WHERE v.vac_type = 'Fly'
  AND v.fly_type = 'annual'
  AND v.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)  -- Last 7 days
  AND al.user_type = 'gr_officer'
ORDER BY v.created_at DESC, ra.approval_level ASC;

-- 3. Check GR Officer's pending approvals
SELECT 
    v.id,
    v.request_inv_no,
    v.emp_id,
    e.name as employee_name,
    v.vac_type as remarks,
    v.fly_type,
    v.current_status,
    v.current_approval_level,
    ra.approval_level as gr_officer_level,
    ra.status as gr_officer_status,
    ra.approver_id as gr_officer_emp_id
FROM emp_vacation v
JOIN employees e ON v.emp_id = e.emp_id
JOIN request_approvers ra ON v.request_inv_no = ra.request_inv_no
JOIN admin_login al ON ra.approver_id = al.emp_id
WHERE al.user_type = 'gr_officer'
  AND ra.status IN ('pending', 'awaiting')
  AND v.current_status NOT IN ('rejected', 'completed')
ORDER BY v.created_at DESC;

-- 4. Verify request_type_id for vacation_request
SELECT id, type_name 
FROM approval_request_types 
WHERE type_name = 'vacation_request';

-- 5. Sample query to see full approval chain for a Fly | Annual vacation
-- Replace 'VAC-XXXX' with actual request number
/*
SELECT 
    ra.approval_level,
    e.name as approver_name,
    al.user_type as role,
    ra.status,
    ra.action_date,
    ra.note
FROM request_approvers ra
JOIN employees e ON ra.approver_id = e.emp_id
JOIN admin_login al ON e.emp_id = al.emp_id
WHERE ra.request_inv_no = 'VAC-2026-0001'  -- Change this
  AND ra.request_type_id = 3  -- vacation_request type ID
ORDER BY ra.approval_level ASC;
*/
