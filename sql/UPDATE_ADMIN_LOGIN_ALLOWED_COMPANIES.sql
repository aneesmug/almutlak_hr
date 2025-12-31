-- UPDATE STATEMENTS FOR admin_login.allowed_companies
-- Purpose: Grant company access based on each employee's comp_no from employees table
-- Generated for non-employee users only
-- Format: Single company assignment as JSON array per employee

-- First, add the column if it doesn't exist
ALTER TABLE `admin_login` ADD COLUMN `allowed_companies` TEXT NULL DEFAULT NULL;

-- Update statements based on emp_id to comp_no mapping from employees table
-- Each user gets access to the single company assigned to their employee record

UPDATE `admin_login` SET `allowed_companies` = '[3]' WHERE `emp_id` = '4120' AND `user_type` != 'employee';  -- GAMAL ABDELRAHMAN - Finance Manager - Company 3

UPDATE `admin_login` SET `allowed_companies` = '[3]' WHERE `emp_id` = '5430' AND `user_type` != 'employee';  -- Anees Mughal - Administrator - Company 3

UPDATE `admin_login` SET `allowed_companies` = '[4]' WHERE `emp_id` = '1574' AND `user_type` != 'employee';  -- EDUARDO PRANADA - Finance Officer - Company 4

UPDATE `admin_login` SET `allowed_companies` = '[4]' WHERE `emp_id` = '3928' AND `user_type` != 'employee';  -- MAHER THABET - GM - Company 4

UPDATE `admin_login` SET `allowed_companies` = '[5]' WHERE `emp_id` = '3431' AND `user_type` != 'employee';  -- LEANDRO BUNAG SANTIAGO - HR Payroll - Company 5

UPDATE `admin_login` SET `allowed_companies` = '[3]' WHERE `emp_id` = '5127' AND `user_type` != 'employee';  -- MAKARAN JAVAID - IT - Company 3

UPDATE `admin_login` SET `allowed_companies` = '[3]' WHERE `emp_id` = '5111' AND `user_type` != 'employee';  -- ABDULRAHMAN MOHAMMED ALSALHI - Assistant - Company 3

UPDATE `admin_login` SET `allowed_companies` = '[3]' WHERE `emp_id` = '5115' AND `user_type` != 'employee';  -- ROUA AHMED SENDI - HR Recruitment - Company 3

UPDATE `admin_login` SET `allowed_companies` = '[3]' WHERE `emp_id` = '5423' AND `user_type` != 'employee';  -- ABRAR MOHAMMED ALSAHBI - HR Operations - Company 3

UPDATE `admin_login` SET `allowed_companies` = '[3]' WHERE `emp_id` = '5422' AND `user_type` != 'employee';  -- ABDULRAHMAN SAMEER MALKI - HR Recruitment - Company 3

UPDATE `admin_login` SET `allowed_companies` = '[3]' WHERE `emp_id` = '5426' AND `user_type` != 'employee';  -- MONA IBRAHIM ALSAHER - Assistant - Company 3

UPDATE `admin_login` SET `allowed_companies` = '[4]' WHERE `emp_id` = '3061' AND `user_type` != 'employee';  -- AHMED ABDELHAY A SOLIMAN - Finance Officer - Company 4

UPDATE `admin_login` SET `allowed_companies` = '[7]' WHERE `emp_id` = '3015' AND `user_type` != 'employee';  -- AHMED SABER AHMED AYTALLAH - Finance Officer - Company 8

UPDATE `admin_login` SET `allowed_companies` = '[1]' WHERE `emp_id` = '2975' AND `user_type` != 'employee';  -- JALAL OSMAN ALI - Manager - Company 4

UPDATE `admin_login` SET `allowed_companies` = '[1]' WHERE `emp_id` = '5414' AND `user_type` != 'employee';  -- Finance Officer - Company 1

UPDATE `admin_login` SET `allowed_companies` = '[11]' WHERE `emp_id` = '4473' AND `user_type` != 'employee';  -- MOHAMMED ISMAIL MOHAMMED AYUB - Manager - Company 15

UPDATE `admin_login` SET `allowed_companies` = '[3]' WHERE `emp_id` = '3294' AND `user_type` != 'employee';  -- KAMAL NASER EID ABED HUSSEIN - Manager - Company 15

UPDATE `admin_login` SET `allowed_companies` = '[3]' WHERE `emp_id` = '5071' AND `user_type` != 'employee';  -- Manager - Company 8

UPDATE `admin_login` SET `allowed_companies` = '[2]' WHERE `emp_id` = '5337' AND `user_type` != 'employee';  -- Manager - Company 2

UPDATE `admin_login` SET `allowed_companies` = '[7]' WHERE `emp_id` = '4825' AND `user_type` != 'employee';  -- OSAMA OTHMAN SAYED AHMED - Manager - Company 8

UPDATE `admin_login` SET `allowed_companies` = '[3]' WHERE `emp_id` = '4119' AND `user_type` != 'employee';  -- ABDELHAMIED ALI HAMED AHMED - Manager - Company 1

UPDATE `admin_login` SET `allowed_companies` = '[3]' WHERE `emp_id` = '4136' AND `user_type` != 'employee';  -- HAITHAM SALAMA AL SHAIR - Manager - Company 10

UPDATE `admin_login` SET `allowed_companies` = '[4]' WHERE `emp_id` = '4768' AND `user_type` != 'employee';  -- WALEED ABDULLAH G ALGHAMDI - Manager - Company 4

UPDATE `admin_login` SET `allowed_companies` = '[3]' WHERE `emp_id` = '5345' AND `user_type` != 'employee';  -- Manager - Company 3

UPDATE `admin_login` SET `allowed_companies` = '[3]' WHERE `emp_id` = '3618' AND `user_type` != 'employee';  -- FAHD MOHAMMED FAHD ALQUWAYI - Manager - Company 11

UPDATE `admin_login` SET `allowed_companies` = '[3]' WHERE `emp_id` = '5261' AND `user_type` != 'employee';  -- Manager - Company 3

UPDATE `admin_login` SET `allowed_companies` = '[3]' WHERE `emp_id` = '5293' AND `user_type` != 'employee';  -- Manager - Company 14

UPDATE `admin_login` SET `allowed_companies` = '[3]' WHERE `emp_id` = '5401' AND `user_type` != 'employee';  -- Department User - Company 3

UPDATE `admin_login` SET `allowed_companies` = '[3]' WHERE `emp_id` = '2539' AND `user_type` != 'employee';  -- KAMRUL HASAN SAHAR ALI - Manager - Company 14

UPDATE `admin_login` SET `allowed_companies` = '[11]' WHERE `emp_id` = '4133' AND `user_type` != 'employee';  -- ISSA TAMOTOMA - Manager - Company 5

UPDATE `admin_login` SET `allowed_companies` = '[3]' WHERE `emp_id` = '5455' AND `user_type` != 'employee';  -- HAIFAA SAEED ALMALKI - HR Senior BP - Company 5

UPDATE `admin_login` SET `allowed_companies` = '[1]' WHERE `emp_id` = '5456' AND `user_type` != 'employee';  -- LINA ABDULRAHMAN ALMUTLAQ - IT Manager - Company 3

UPDATE `admin_login` SET `allowed_companies` = '[3]' WHERE `emp_id` = '5454' AND `user_type` != 'employee';  -- MOHAMMED ABDO ALI HANTOOL - HR Manager - Company 3

UPDATE `admin_login` SET `allowed_companies` = '[3]' WHERE `emp_id` = '5021' AND `user_type` != 'employee';  -- GR Officer - Company 1

-- Verification query to check updated records
-- SELECT `id`, `emp_id`, `fullname`, `user_type`, `allowed_companies` FROM `admin_login` 
-- WHERE `user_type` != 'employee' AND `allowed_companies` IS NOT NULL ORDER BY `emp_id`;

