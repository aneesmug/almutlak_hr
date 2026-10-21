-- attendance.php "Transactions" tab lists zk_attendance by punch_datetime (newest first, optional date range,
-- often without an employee). Without this index every page load scans the whole table.
ALTER TABLE `zk_attendance`
  ADD KEY IF NOT EXISTS `idx_punch_datetime` (`punch_datetime`);
