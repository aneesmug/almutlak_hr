-- Migration to Generic Approval Chain System for Loans
-- This aligns the loan system with vacation/smart_request approval pattern
-- Generated on 2025-11-11

-- Step 1: Add inv_no column to emp_loan table (unique identifier like VAC-xxx or LOAN-xxx)
ALTER TABLE `emp_loan`
ADD COLUMN `inv_no` varchar(255) DEFAULT NULL COMMENT 'Unique loan request identifier (LOAN-YYYYMMDD-EMPID-HASH)' AFTER `id`,
ADD UNIQUE KEY `idx_inv_no` (`inv_no`);

-- Step 2: Ensure approval_request_types has loan_request entry
INSERT INTO `approval_request_types` (`id`, `type_name`, `main_table_name`) 
VALUES (2, 'loan_request', 'emp_loan')
ON DUPLICATE KEY UPDATE `type_name` = 'loan_request', `main_table_name` = 'emp_loan';

-- Step 3: Generate inv_no for existing loans that don't have one
UPDATE `emp_loan` 
SET `inv_no` = CONCAT(
    'LOAN-',
    DATE_FORMAT(`created_at`, '%Y%m%d'),
    '-',
    `emp_id`,
    '-',
    SUBSTRING(MD5(CONCAT(`id`, `emp_id`, `created_at`)), 1, 4)
)
WHERE `inv_no` IS NULL;

-- Step 4: Create approval chains for existing pending loans
-- This will populate request_approvers based on the employee's department and hierarchy

DELIMITER //

CREATE PROCEDURE migrate_existing_loans_to_approval_chain()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE loan_id_var INT;
    DECLARE emp_id_var VARCHAR(20);
    DECLARE inv_no_var VARCHAR(255);
    DECLARE dept_var INT;
    DECLARE supervisor_id_var VARCHAR(20);
    
    DECLARE loan_cursor CURSOR FOR 
        SELECT l.id, l.emp_id, l.inv_no, e.dept, e.supervisor_id
        FROM emp_loan l
        JOIN employees e ON l.emp_id = e.emp_id
        WHERE l.status LIKE '%pending%'
        AND NOT EXISTS (
            SELECT 1 FROM request_approvers ra 
            WHERE ra.request_inv_no = l.inv_no 
            AND ra.request_type_id = 2
        );
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN loan_cursor;
    
    read_loop: LOOP
        FETCH loan_cursor INTO loan_id_var, emp_id_var, inv_no_var, dept_var, supervisor_id_var;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- Level 1: Department Manager or Supervisor
        IF supervisor_id_var IS NOT NULL AND supervisor_id_var != '' THEN
            INSERT INTO request_approvers (request_inv_no, request_type_id, approver_id, approval_level, status)
            VALUES (inv_no_var, 2, supervisor_id_var, 1, 'pending');
        ELSE
            -- Get department manager if no supervisor
            INSERT INTO request_approvers (request_inv_no, request_type_id, approver_id, approval_level, status)
            SELECT inv_no_var, 2, e.emp_id, 1, 'pending'
            FROM employees e
            WHERE e.dept = dept_var 
            AND e.emptype = 'Manager' 
            AND e.status = 1
            LIMIT 1;
        END IF;
        
        -- Level 2: HR Assistant (dept 5, emptype Assistant)
        INSERT INTO request_approvers (request_inv_no, request_type_id, approver_id, approval_level, status)
        SELECT inv_no_var, 2, al.id_iqama, 2, 'awaiting'
        FROM admin_login al
        WHERE al.user_type = 'assistant' 
        AND al.dept = 5
        AND al.status = 1
        LIMIT 1;
        
        -- Level 3: HR Manager (dept 5, user_type hr)
        INSERT INTO request_approvers (request_inv_no, request_type_id, approver_id, approval_level, status)
        SELECT inv_no_var, 2, al.id_iqama, 3, 'awaiting'
        FROM admin_login al
        WHERE al.user_type = 'hr'
        AND al.status = 1
        LIMIT 1;
        
        -- Level 4: Finance Manager (dept 2, emptype Manager)
        INSERT INTO request_approvers (request_inv_no, request_type_id, approver_id, approval_level, status)
        SELECT inv_no_var, 2, e.emp_id, 4, 'awaiting'
        FROM employees e
        WHERE e.dept = 2 
        AND e.emptype = 'Manager'
        AND e.status = 1
        LIMIT 1;
        
        -- Level 5: GM (user_type gm)
        INSERT INTO request_approvers (request_inv_no, request_type_id, approver_id, approval_level, status)
        SELECT inv_no_var, 2, al.id_iqama, 5, 'awaiting'
        FROM admin_login al
        WHERE al.user_type = 'gm'
        AND al.status = 1
        LIMIT 1;
        
        -- Level 6: Finance Assistant (dept 2, user_type assistant)
        INSERT INTO request_approvers (request_inv_no, request_type_id, approver_id, approval_level, status)
        SELECT inv_no_var, 2, al.id_iqama, 6, 'awaiting'
        FROM admin_login al
        WHERE al.user_type = 'assistant'
        AND al.dept = 2
        AND al.status = 1
        LIMIT 1;
        
    END LOOP;
    
    CLOSE loan_cursor;
END //

DELIMITER ;

-- Run the migration procedure
CALL migrate_existing_loans_to_approval_chain();

-- Clean up the procedure
DROP PROCEDURE IF EXISTS migrate_existing_loans_to_approval_chain;

-- Step 5: Update emp_loan status field to use generic statuses
-- Map old statuses to new approval level based statuses
UPDATE emp_loan SET status = 'pending_level_1' WHERE status = 'dept_manager_pending';
UPDATE emp_loan SET status = 'pending_level_2' WHERE status = 'hr_assistant_pending';
UPDATE emp_loan SET status = 'pending_level_3' WHERE status = 'hr_manager_pending';
UPDATE emp_loan SET status = 'pending_level_4' WHERE status = 'finance_manager_pending';
UPDATE emp_loan SET status = 'pending_level_5' WHERE status = 'gm_pending';
UPDATE emp_loan SET status = 'pending_level_6' WHERE status = 'finance_assistant_pending';

-- Keep these as-is
-- UPDATE emp_loan SET status = 'approved' WHERE status = 'approved';
-- UPDATE emp_loan SET status = 'rejected' WHERE status = 'rejected';
-- UPDATE emp_loan SET status = 'paid' WHERE status = 'paid';

-- Step 6: Update status enum to match the generic pattern
ALTER TABLE `emp_loan`
MODIFY COLUMN `status` varchar(50) NOT NULL DEFAULT 'pending_level_1' 
COMMENT 'Status: pending_level_1 to pending_level_6, approved, rejected, paid';

-- Step 7: Add index for faster lookups
CREATE INDEX `idx_status_level` ON `emp_loan` (`status`, `current_approval_level`);

-- Display results
SELECT 'Migration Complete!' as message;
SELECT COUNT(*) as total_loans, status FROM emp_loan GROUP BY status;
SELECT COUNT(*) as total_approval_chains FROM request_approvers WHERE request_type_id = 2;
