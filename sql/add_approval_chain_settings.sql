-- SQL Script to add Approval Chain Configuration to app_settings table
-- Run this script to enable approval chain management in Application Settings

-- Add placeholder entries for approval chain configurations
-- These will be populated through the UI in app_seetings.php

INSERT INTO `app_settings` (`setting_name`, `setting_value`, `setting_group`, `description`, `input_type`, `options`) VALUES
('approval_chain_vacation_request', '[]', 'approval', 'Approval chain for vacation requests', 'json', NULL),
('approval_chain_excuse_leave', '[]', 'approval', 'Approval chain for excuse leave requests', 'json', NULL),
('approval_chain_loan_request', '[]', 'approval', 'Approval chain for loan requests', 'json', NULL),
('approval_chain_resignation_request', '[]', 'approval', 'Approval chain for resignation requests', 'json', NULL),
('approval_chain_rejoin_request', '[]', 'approval', 'Approval chain for rejoin requests', 'json', NULL)
ON DUPLICATE KEY UPDATE 
    `setting_group` = VALUES(`setting_group`),
    `description` = VALUES(`description`),
    `input_type` = VALUES(`input_type`);

-- Add a general setting to enable/disable approval chain feature
INSERT INTO `app_settings` (`setting_name`, `setting_value`, `setting_group`, `description`, `input_type`, `options`) VALUES
('enable_approval_chains', '1', 'approval', 'Enable Approval Chain Management', 'select', '{"1":"Enabled", "0":"Disabled"}')
ON DUPLICATE KEY UPDATE 
    `setting_group` = VALUES(`setting_group`),
    `description` = VALUES(`description`),
    `input_type` = VALUES(`input_type`),
    `options` = VALUES(`options`);
