-- One-time cleanup: switch every existing loan currently set to automatic
-- payroll deduction over to manual. Newly created loans still default to
-- 'automatic' (schema default unchanged) - this only touches existing rows.
-- Does NOT delete any already-generated payroll_deductions rows - those stay
-- as-is and are simply no longer auto-regenerated/modified going forward.
UPDATE `emp_loan` SET `deduction_mode` = 'manual' WHERE `deduction_mode` = 'automatic';
