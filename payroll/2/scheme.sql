CREATE TABLE `generated_payrolls` (
  `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `emp_id` VARCHAR(255) NOT NULL,
  `month_year` VARCHAR(50) NOT NULL, -- e.g., "YYYY-MM" (e.g., "2024-06")
  `basic_salary` DECIMAL(10, 2) NOT NULL,
  `housing_allowance` DECIMAL(10, 2) NOT NULL,
  `transport_allowance` DECIMAL(10, 2) NOT NULL,
  `food_allowance` DECIMAL(10, 2) NOT NULL,
  `miscellaneous_allowance` DECIMAL(10, 2) NOT NULL,
  `cashier_allowance` DECIMAL(10, 2) NOT NULL,
  `fuel_allowance` DECIMAL(10, 2) NOT NULL,
  `telephone_allowance` DECIMAL(10, 2) NOT NULL,
  `other_allowance` DECIMAL(10, 2) NOT NULL,
  `guard_allowance` DECIMAL(10, 2) NOT NULL,
  `total_gross_salary` DECIMAL(10, 2) NOT NULL,
  `total_benefits` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `total_deductions` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `net_salary` DECIMAL(10, 2) NOT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'generated', -- e.g., 'generated', 'approved', 'paid'
  `generated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `idx_emp_month` (`emp_id`, `month_year`) -- Ensures only one payroll entry per employee per month
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;