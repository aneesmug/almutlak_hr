-- Adds support for:
--  1) Temporary, employee-specific timetables with an active date range
--     (overrides the employee's company timetable while active).
--  2) A locked "assigned elsewhere" guard on company timetables (enforced in
--     PHP; no schema change needed for that part).

ALTER TABLE `timetables`
  ADD COLUMN IF NOT EXISTS `is_temporary` TINYINT(1) NOT NULL DEFAULT 0 AFTER `days_off`,
  ADD COLUMN IF NOT EXISTS `start_date` DATE NULL DEFAULT NULL AFTER `is_temporary`,
  ADD COLUMN IF NOT EXISTS `end_date` DATE NULL DEFAULT NULL AFTER `start_date`;

CREATE TABLE IF NOT EXISTS `timetable_employees` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `timetable_id` INT NOT NULL,
  `emp_id` INT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_timetable_emp` (`timetable_id`, `emp_id`),
  KEY `idx_emp` (`emp_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
