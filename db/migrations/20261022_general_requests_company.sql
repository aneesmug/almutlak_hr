-- General Requests: company the request is raised for (companies.comp_id, same as employees.comp_no).
-- Also added automatically on first use by gr_ensure_company_column() (includes/general_request_helpers.php).
-- Idempotent.
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'general_requests' AND COLUMN_NAME = 'comp_no');
SET @sql := IF(@exist = 0, 'ALTER TABLE general_requests ADD COLUMN comp_no INT NULL DEFAULT NULL AFTER user_dept, ADD INDEX idx_comp_no (comp_no)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Back-fill existing requests with the requester's company
UPDATE general_requests gr JOIN employees e ON e.emp_id = gr.emp_id SET gr.comp_no = e.comp_no WHERE gr.comp_no IS NULL;
