-- Add manual full-access override setting to app_settings
-- You can edit this from App Settings UI after it exists.

INSERT INTO `app_settings` (`setting_name`, `setting_value`, `setting_group`, `description`, `input_type`, `options`)
VALUES (
    'full_access_emp_ids',
    '',
    'security',
    'full_access_employee_ids_comma_separated_or_json_emp_id_values',
    'text',
    NULL
)
ON DUPLICATE KEY UPDATE
    `setting_group` = VALUES(`setting_group`),
    `description` = VALUES(`description`),
    `input_type` = VALUES(`input_type`);
