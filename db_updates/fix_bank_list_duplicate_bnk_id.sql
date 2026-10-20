-- ============================================================================
-- Fix: bank_list had 4 banks saved with bnk_id = 0 (2026-10-20)
--
-- employees.bank_name stores bank_list.bnk_id. SABB, Banque Saudi Fransi, Saudi
-- Hollandi and Bank Al-Bilad all had bnk_id = 0, so:
--   * every query "LEFT JOIN bank_list bl ON bl.bnk_id = e.bank_name" returned 4 rows
--     for an employee with bank_name = 0 -> employee repeated 4x in the payroll
--     report, bank Excel, PDF and totals (e.g. emp 5514 in Oct 2026);
--   * choosing any of those 4 banks in Add/Edit Employee saved 0.
--
-- This gives the 4 banks their own bnk_id (10-13) and repoints employees whose
-- bank_name = 0 to the right bank using the IBAN bank code (IBAN chars 5-6):
--   45 = SABB, 15 = Bank Al-Bilad, 55 = Banque Saudi Fransi, 50 = Saudi Hollandi (Alawwal)
-- Employees with bank_name = 0 and another/no IBAN code are left as-is (check them by hand).
-- Safe to run more than once.
-- ============================================================================

-- 1. Unique bnk_id for the 4 banks (matched by SWIFT, not by row id).
UPDATE `bank_list` SET `bnk_id` = 10 WHERE `bnk_id` = 0 AND `bank_name_s` = 'SABBSARIXXX';
UPDATE `bank_list` SET `bnk_id` = 11 WHERE `bnk_id` = 0 AND `bank_name_s` = 'ALBISARIXXX';
UPDATE `bank_list` SET `bnk_id` = 12 WHERE `bnk_id` = 0 AND `bank_name_s` = 'BSFRSARIXXX';
UPDATE `bank_list` SET `bnk_id` = 13 WHERE `bnk_id` = 0 AND `bank_name_s` LIKE 'AAALSARI%';

-- 2. Repoint employees that were saved with bank_name = 0, by IBAN bank code.
UPDATE `employees` SET `bank_name` = '10'
 WHERE (`bank_name` = '0') AND SUBSTRING(REPLACE(`iban`, ' ', ''), 5, 2) = '45';
UPDATE `employees` SET `bank_name` = '11'
 WHERE (`bank_name` = '0') AND SUBSTRING(REPLACE(`iban`, ' ', ''), 5, 2) = '15';
UPDATE `employees` SET `bank_name` = '12'
 WHERE (`bank_name` = '0') AND SUBSTRING(REPLACE(`iban`, ' ', ''), 5, 2) = '55';
UPDATE `employees` SET `bank_name` = '13'
 WHERE (`bank_name` = '0') AND SUBSTRING(REPLACE(`iban`, ' ', ''), 5, 2) = '50';

-- 3. Check: both should return no rows.
SELECT `bnk_id`, COUNT(*) AS c FROM `bank_list` GROUP BY `bnk_id` HAVING c > 1;
SELECT `emp_id`, `name`, `iban` FROM `employees` WHERE `bank_name` = '0';
