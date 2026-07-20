-- Old vacation-replacement role-swap implementation is replaced by
-- emp_temp_role_assignments (see create_emp_temp_role_assignments.sql).
-- The old table physically swapped admin_login.user_type; the new one is read
-- dynamically by date window and never mutates admin_login. Run this after
-- confirming no historical data from temp_vacation_role_assignments is needed.
DROP TABLE IF EXISTS `temp_vacation_role_assignments`;
