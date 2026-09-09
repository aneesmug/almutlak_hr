-- Some companies (e.g. Sanam) don't run the same hours every day - a short
-- Thursday, Friday off, and normal Sat-Wed hours all on the SAME timetable.
-- The old model (one check_in/check_out/standard_hours/days_off set per
-- timetable) can only express "same hours every working day" - not this.
--
-- Moves the actual schedule into a per-weekday child table: one row per
-- timetable per ISO weekday (1=Mon..7=Sun), each with its own is_off flag and
-- its own check-in/check-out window/hours. `timetables` itself keeps only
-- identity (name, is_temporary, start_date, end_date) - the day-level columns
-- it used to carry are migrated into 7 timetable_days rows per timetable,
-- then dropped.
CREATE TABLE IF NOT EXISTS `timetable_days` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `timetable_id` INT NOT NULL,
  `weekday` TINYINT NOT NULL, -- ISO: 1=Monday .. 7=Sunday, matches PHP date('N')
  `is_off` TINYINT(1) NOT NULL DEFAULT 0,
  `check_in` VARCHAR(5) NOT NULL DEFAULT '08:00',
  `check_in_start` VARCHAR(5) NOT NULL DEFAULT '07:45',
  `check_in_end` VARCHAR(5) NOT NULL DEFAULT '08:15',
  `check_out` VARCHAR(5) NOT NULL DEFAULT '17:00',
  `check_out_start` VARCHAR(5) NOT NULL DEFAULT '16:45',
  `check_out_end` VARCHAR(5) NOT NULL DEFAULT '17:15',
  `standard_hours` DECIMAL(4,1) NOT NULL DEFAULT 8,
  UNIQUE KEY `uniq_timetable_weekday` (`timetable_id`, `weekday`),
  CONSTRAINT `fk_timetable_days_timetable` FOREIGN KEY (`timetable_id`) REFERENCES `timetables` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Carry every existing timetable's single schedule forward as its 7 daily
-- rows (is_off derived from the old comma-list days_off column).
INSERT INTO timetable_days (timetable_id, weekday, is_off, check_in, check_in_start, check_in_end, check_out, check_out_start, check_out_end, standard_hours)
SELECT t.id, w.weekday,
  IF(FIND_IN_SET(w.weekday, t.days_off) > 0, 1, 0),
  t.check_in, t.check_in_start, t.check_in_end, t.check_out, t.check_out_start, t.check_out_end, t.standard_hours
FROM timetables t
JOIN (SELECT 1 AS weekday UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7) w
WHERE NOT EXISTS (SELECT 1 FROM timetable_days td WHERE td.timetable_id = t.id AND td.weekday = w.weekday);

ALTER TABLE `timetables`
  DROP COLUMN IF EXISTS `check_in`,
  DROP COLUMN IF EXISTS `check_in_start`,
  DROP COLUMN IF EXISTS `check_in_end`,
  DROP COLUMN IF EXISTS `check_out`,
  DROP COLUMN IF EXISTS `check_out_start`,
  DROP COLUMN IF EXISTS `check_out_end`,
  DROP COLUMN IF EXISTS `standard_hours`,
  DROP COLUMN IF EXISTS `days_off`;
