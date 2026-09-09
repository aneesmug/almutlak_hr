-- Adds direct-socket polling target columns to zk_devices. Devices stay
-- pointed at BioTime (ADMS push, unchanged) - these columns are ONLY for the
-- separate direct-socket online/offline check (port 4370, port-forwarded per
-- device through the router at the local site to 212.118.124.212).
-- Safe to re-run.

ALTER TABLE `zk_devices`
  ADD COLUMN IF NOT EXISTS `pull_host` VARCHAR(45) DEFAULT '212.118.124.212' AFTER `device_ip`,
  ADD COLUMN IF NOT EXISTS `pull_port` INT DEFAULT NULL AFTER `pull_host`,
  ADD COLUMN IF NOT EXISTS `comm_key` INT DEFAULT 0 AFTER `pull_port`;
