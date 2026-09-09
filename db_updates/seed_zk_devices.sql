-- Seeds the 18 existing BioTime devices into zk_devices so their live state can
-- be tracked once repointed to this app's ADMS endpoints. Safe to re-run
-- (INSERT IGNORE keyed on serial_number). Run AFTER add_zk_devices_tables.sql.

INSERT IGNORE INTO `zk_devices` (`serial_number`, `device_name`, `area`, `device_ip`) VALUES
('6620220200001', 'alRiyadh alouayda', 'alRiyadh alouayda', '192.168.0.131'),
('6620220200012', 'Metal Factory', 'Metal HO', '192.168.1.15'),
('CKJW204960084', 'Sanam', 'Sanam', '192.168.4.14'),
('CKJW204960129', 'Alriyadh Filter', 'Alriyadh Filter', '192.168.0.150'),
('CKJW204960137', 'K8', 'K8', '192.168.0.14'),
('CKJW204960178', 'Aljwoharh', 'Aljwoharh Store', '192.168.5.15'),
('CKJW204960192', 'Aljoharah SPD', 'Aljoharah SPD', '192.168.8.14'),
('CKJW204960195', 'Alraedah Tyers', 'Alraedah Tyers', '192.168.0.14'),
('CKJW204960198', 'Refeer', 'Refeer', '192.168.6.15'),
('CKJW204960200', 'Damam', 'Dammam', '192.168.0.15'),
('CKJW204960201', 'Ryiadh Tyers', 'Ryiadh Tyers', '192.168.0.100'),
('CKJW204960251', 'Metal HO', 'Metal HO', '192.168.1.14'),
('CKJW204960252', 'Metal Finishing', 'Metal Finishing', '192.168.27.14'),
('CKJW204960271', 'Filter Factory', 'Filter Factory', '192.168.1.23'),
('CKJW204960274', 'Camp', 'Camp', '192.168.100.114'),
('CKJW204960281', 'Filter Factory Laides', 'Filter Factory Laides', '192.168.1.21'),
('CKJW204960878', 'Filter HO', 'Filter HO', '192.168.1.22'),
('CKJW204960938', 'H.O', 'H.O', '192.168.9.14');
