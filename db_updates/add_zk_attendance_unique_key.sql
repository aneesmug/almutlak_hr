-- Fixes a real data-loss bug: zk_attendance had no unique constraint, so
-- zk_sync_import.php only ever attempted to insert a punch into it ONCE - the
-- exact moment that punch's row was first inserted into zk_attendance_raw
-- (protected by zk_attendance_raw's own uniq_punch key). If the employee
-- wasn't yet active/didn't exist in `employees` at that exact moment (e.g. a
-- device flushing weeks/months of offline-cached punches for someone HR only
-- just added), the punch was logged as skipped and PERMANENTLY lost - the raw
-- row's dedup key meant it could never be retried again on any future sync.
--
-- Adding this unique key lets zk_insert_live_attendance() use INSERT IGNORE,
-- so it's safe to attempt the insert on every sync a punch is re-sent within
-- (not just the one time its raw row was new) - self-healing once the
-- employee becomes active, without ever creating a duplicate row.
ALTER TABLE `zk_attendance`
  ADD UNIQUE KEY IF NOT EXISTS `uniq_emp_punch` (`emp_id`, `punch_datetime`);
