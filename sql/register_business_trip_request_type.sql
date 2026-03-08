-- Register Business Trip Request Type in Approval System
-- This ensures business_trip requests are properly handled by the approval workflow

-- Add Business Trip to approval_request_types if not exists
INSERT IGNORE INTO `approval_request_types` (id, type_name, main_table_name, description) 
VALUES (6, 'business_trip', 'emp_business_trip', 'Employee Business Trip Request');

-- Ensure business_trip approval chain is configured
INSERT IGNORE INTO `app_settings` 
(`setting_name`, `setting_group`, `input_type`, `setting_value`, `description`) 
VALUES (
    'approval_chain_business_trip',
    'approval',
    'json',
    '[{"level":1,"user_type":"direct_supervisor","role_label":"Direct Supervisor"},{"level":2,"user_type":"hr_senior_bp","role_label":"HR Senior BP"},{"level":3,"user_type":"finance_officer","role_label":"Finance Officer"}]',
    'Business Trip Request Approval Chain Configuration - Configurable through Application Settings interface'
);

-- Verify creation
SELECT 'Business Trip Type Registered' as status, COUNT(*) as count FROM `approval_request_types` WHERE type_name = 'business_trip';
SELECT 'Approval Chain Configured' as status, COUNT(*) as count FROM `app_settings` WHERE setting_name = 'approval_chain_business_trip';
