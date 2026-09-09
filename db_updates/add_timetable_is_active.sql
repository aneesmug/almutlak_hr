-- Lets a timetable be built/edited as a draft and only take effect once
-- explicitly activated (see includes/ajaxFile/timetableAjax.php's
-- 'toggle_timetable_active' action and attendance_resolve_timetable() in
-- includes/attendance_helpers.php, which falls back to the Default
-- timetable's schedule whenever the resolved timetable is inactive).
ALTER TABLE `timetables`
  ADD COLUMN IF NOT EXISTS `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `end_date`;

-- New timetables created after this point default to inactive (draft) via
-- application code (see add_edit_timetable()) - this backfill only makes
-- sure every timetable already in use (and the Default row) keeps working
-- exactly as before the column existed.
UPDATE `timetables` SET `is_active` = 1;
