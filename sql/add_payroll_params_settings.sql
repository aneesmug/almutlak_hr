-- Payroll configurable parameters: loan %, vacation/payroll thresholds, overtime formula, deduction base.
-- Safe to re-run: app_settings uses ON DUPLICATE KEY UPDATE on setting_name; deduction_types uses INSERT IGNORE.

INSERT INTO `app_settings` (`setting_name`, `setting_value`, `setting_group`, `description`, `input_type`, `options`) VALUES
('loan_max_pct_eos', '40', 'loan_settings', 'Maximum End-of-Service loan as % of calculated EOS benefit', 'text', NULL),
('loan_max_pct_advance', '50', 'loan_settings', 'Maximum advance salary loan as % of total monthly salary', 'text', NULL),
('loan_max_installments', '12', 'loan_settings', 'Maximum installments allowed for EOS / Housing loans', 'text', NULL),
('loan_installment_edit_max', '60', 'loan_settings', 'Maximum installments allowed when HR edits an existing loan plan', 'text', NULL),
('vacation_gosi_local_min_days', '20', 'vacation_payroll', 'Minimum Local Vacation days that triggers auto-GOSI-via-vacation payout', 'text', NULL),
('vacation_payroll_dropout_days', '30', 'vacation_payroll', 'Vacation days after which an employee is dropped from that month''s payroll generation', 'text', NULL),
('overtime_monthly_hours', '240', 'overtime_settings', 'Standard monthly working hours used as the divisor for overtime hourly rate', 'text', NULL),
('overtime_extra_multiplier', '0.5', 'overtime_settings', 'Extra multiplier applied to the basic-salary hourly portion of overtime pay', 'text', NULL),
('deduction_base_components', '["basic_salary","housing_allowance","transport_allowance","miscellaneous_allowance","cashier_allowance","fuel_allowance","telephone_allowance","other_allowance","guard_allowance"]', 'deduction_settings', 'Salary components summed as the base for percentage-based deductions (e.g. GOSI)', 'text', NULL)
ON DUPLICATE KEY UPDATE
    `setting_group` = VALUES(`setting_group`),
    `description` = VALUES(`description`),
    `input_type` = VALUES(`input_type`);

-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `deduction_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `counts_in_net` tinyint(1) NOT NULL DEFAULT 1,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `deduction_types` (`name`, `counts_in_net`, `status`) VALUES
('GOSI', 1, 1),
('Loan Installment', 1, 1),
('Joining Date Deduction', 1, 1),
('Absence', 1, 1),
('Late Deduction', 1, 1),
('Other', 1, 1);
