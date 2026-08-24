-- Adds tracking columns that control visibility of the "Update Salary" button
-- (includes/emp_top_info.php):
--   salary_update_pending      -> set to 1 automatically when a Salary Increment
--                                  request reaches final approval; cleared back to 0
--                                  once HR completes the Update Salary form (hrHandler.php).
--   force_show_update_salary_btn -> manual override toggle set from Edit Employee page,
--                                  gated by the 'manage_update_salary_button_visibility'
--                                  Special Access key (App Settings -> Special Access).
ALTER TABLE `employees`
    ADD COLUMN `salary_update_pending` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Set to 1 when a salary increment request is finally approved; cleared to 0 after HR updates the salary breakdown via the Update Salary button',
    ADD COLUMN `force_show_update_salary_btn` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Manual override to force-show the Update Salary button regardless of salary_update_pending; toggled from Edit Employee page, requires special access';
