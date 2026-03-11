-- Table for business trip ticket and allowance entries
-- Run once if you prefer manual DB setup instead of auto-create in ajaxBusinessTrip.php

CREATE TABLE IF NOT EXISTS emp_business_trip_allowances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trip_id INT NOT NULL,
    request_inv_no VARCHAR(120) DEFAULT NULL,
    emp_id VARCHAR(50) DEFAULT NULL,
    ticket_fares DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    other_allowance DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    allowance_days INT NOT NULL DEFAULT 0,
    total_allowance DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    notes TEXT DEFAULT NULL,
    created_by INT DEFAULT NULL,
    updated_by INT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_trip_id (trip_id),
    KEY idx_emp_id (emp_id),
    KEY idx_request_inv_no (request_inv_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS emp_business_trip_allowance_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    allowance_id INT NOT NULL,
    trip_id INT NOT NULL,
    allowance_type VARCHAR(50) NOT NULL,
    unit_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    qty INT NOT NULL DEFAULT 1,
    line_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    line_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_allowance_id (allowance_id),
    KEY idx_trip_id (trip_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;