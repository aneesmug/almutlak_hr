-- Fixes "Unknown column 'gosi' in 'field list'" when creating/editing an employee
-- (new_comp_employee.php, edit_employee.php) on an environment that hasn't picked up
-- this column yet.
--
-- `employees.gosi` (GOSI/social-insurance percentage) replaced the older
-- insurance_no/insurance_class/insurance_exp fields on the employee create/edit forms.
-- Those old columns are left in place if they still exist - only `gosi` is added here.
--
-- Safe to run more than once.

ALTER TABLE `employees`
    ADD COLUMN IF NOT EXISTS `gosi` decimal(10,2) DEFAULT NULL AFTER `address`;
