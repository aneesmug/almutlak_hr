-- Upgrades an existing payroll_supervisor_assignments table (one row per employee,
-- meaning "supervisor, always") to be month-aware, so HR can change an employee's
-- payroll-reporting supervisor starting from a future month without losing the
-- assignment that's still in effect for the current/past months.
--
-- Existing rows get effective_month = '2000-01', a sentinel low enough to keep
-- applying to every month until HR explicitly assigns a new supervisor from some
-- later month onward.
--
-- Safe to run more than once - skip if effective_month already exists.

ALTER TABLE payroll_supervisor_assignments
    ADD COLUMN IF NOT EXISTS effective_month VARCHAR(7) NOT NULL DEFAULT '2000-01' AFTER supervisor_emp_id;

ALTER TABLE payroll_supervisor_assignments
    DROP INDEX uniq_emp_assignment;

ALTER TABLE payroll_supervisor_assignments
    ADD UNIQUE KEY uniq_emp_month_assignment (emp_id, effective_month);
