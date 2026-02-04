-- Add translation for loan request approval chain description
-- Original text: "Employee loan application approval chain (regular, emergency, end of service, housing, advance salary)"
-- Normalized key: "employee_loan_application_approval_chain_regular_emergency_end_of_service_housing_advance_salary"

-- Insert English translation
INSERT INTO `translations` (`lang_key`, `lang_code`, `translation`) 
VALUES ('employee_loan_application_approval_chain_regular_emergency_end_of_service_housing_advance_salary', 'en', 'Employee loan application approval chain (regular, emergency, end of service, housing, advance salary)')
ON DUPLICATE KEY UPDATE `translation` = 'Employee loan application approval chain (regular, emergency, end of service, housing, advance salary)';

-- Insert Arabic translation
INSERT INTO `translations` (`lang_key`, `lang_code`, `translation`) 
VALUES ('employee_loan_application_approval_chain_regular_emergency_end_of_service_housing_advance_salary', 'ar', 'سلسلة الموافقة على طلب القرض للموظف (عادي، طارئ، نهاية الخدمة، سكن، سلفة راتب)')
ON DUPLICATE KEY UPDATE `translation` = 'سلسلة الموافقة على طلب القرض للموظف (عادي، طارئ، نهاية الخدمة، سكن، سلفة راتب)';

-- Verify the translations were added
SELECT `lang_key`, `lang_code`, `translation` 
FROM `translations` 
WHERE `lang_key` = 'employee_loan_application_approval_chain_regular_emergency_end_of_service_housing_advance_salary'
ORDER BY `lang_code`;
