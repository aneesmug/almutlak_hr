CREATE TABLE IF NOT EXISTS payroll_supervisor_report_dispatch (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_inv_no VARCHAR(255) NOT NULL,
    payroll_month VARCHAR(7) NOT NULL,
    supervisor_emp_id VARCHAR(50) NOT NULL,
    supervisor_email VARCHAR(255) NOT NULL,
    sent_by VARCHAR(50) NOT NULL,
    sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    included_supervisor_ids LONGTEXT DEFAULT NULL,
    merged_into_supervisor_emp_id VARCHAR(50) DEFAULT NULL,
    UNIQUE KEY uniq_supervisor_dispatch (request_inv_no, payroll_month, supervisor_emp_id),
    INDEX idx_request_month (request_inv_no, payroll_month),
    INDEX idx_supervisor_emp_id (supervisor_emp_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
