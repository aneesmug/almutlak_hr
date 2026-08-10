-- Register Salary Increment Request Type in Approval System
-- Intentionally does NOT hardcode the id column - let AUTO_INCREMENT assign it,
-- and always look it up by type_name at runtime (never hardcode the id in PHP).

INSERT IGNORE INTO `approval_request_types` (`type_name`, `main_table_name`, `description`, `is_default`, `is_active`, `created_at`)
VALUES ('salary_increment', 'emp_salary_increment', 'Employee Salary Increment Request', 0, 1, NOW());

-- Empty chain by default - configured by the admin via App Settings > Approval Chain Configuration.
INSERT IGNORE INTO `app_settings`
(`setting_name`, `setting_group`, `input_type`, `setting_value`, `description`)
VALUES (
    'approval_chain_salary_increment',
    'approval',
    'json',
    '[]',
    'Salary Increment Request Approval Chain Configuration - Configurable through Application Settings interface'
);

-- Verify creation
SELECT 'Salary Increment Type Registered' as status, COUNT(*) as count FROM `approval_request_types` WHERE type_name = 'salary_increment';
SELECT 'Approval Chain Configured' as status, COUNT(*) as count FROM `app_settings` WHERE setting_name = 'approval_chain_salary_increment';
