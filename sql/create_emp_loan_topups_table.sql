-- Audit trail for manual loan top-ups (increasing an existing active loan's
-- amount instead of creating a second loan record). Rendered as an extra
-- "Manual Top-up" line in the employee's Loan History table so admins can see
-- when/how much was added on top of an existing loan.
CREATE TABLE IF NOT EXISTS `emp_loan_topups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `loan_id` int(11) NOT NULL,
  `emp_id` varchar(20) NOT NULL,
  `additional_amount` decimal(10,2) NOT NULL,
  `previous_loan_amount` decimal(10,2) NOT NULL,
  `new_loan_amount` decimal(10,2) NOT NULL,
  `new_monthly_deduction` decimal(10,2) NOT NULL,
  `added_by_emp_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_loan_id` (`loan_id`),
  KEY `idx_emp_id` (`emp_id`),
  CONSTRAINT `fk_emp_loan_topups_loan` FOREIGN KEY (`loan_id`) REFERENCES `emp_loan` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
