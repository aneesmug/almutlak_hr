-- Quick Installation Script for General Requests System
-- Run this script in your MySQL database to set up all required tables and data

-- Step 1: Create general_requests table
CREATE TABLE IF NOT EXISTS `general_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `inv_no` varchar(255) NOT NULL COMMENT 'Request invoice number',
  `request_title` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'Title/subject of the request',
  `department_to` varchar(100) NOT NULL COMMENT 'Target department (IT, HR, Transportation, etc.)',
  `request_category` varchar(100) NOT NULL COMMENT 'Category of request (Equipment, Service, etc.)',
  `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `description` text CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL COMMENT 'Additional details/notes',
  `emp_id` int(11) NOT NULL COMMENT 'Requesting employee ID',
  `emp_name` varchar(255) NOT NULL COMMENT 'Requesting employee name',
  `user_dept` varchar(255) NOT NULL COMMENT 'Requester department',
  `current_status` varchar(50) NOT NULL DEFAULT 'draft',
  `current_approval_level` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `inv_no` (`inv_no`),
  KEY `emp_id` (`emp_id`),
  KEY `current_status` (`current_status`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Step 2: Create general_request_items table
CREATE TABLE IF NOT EXISTS `general_request_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_inv_no` varchar(255) NOT NULL,
  `item_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `item_type` varchar(100) DEFAULT NULL COMMENT 'Type/category of item',
  `quantity` int(11) NOT NULL DEFAULT 1,
  `specifications` text CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL COMMENT 'Additional specifications or notes about the item',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `request_inv_no` (`request_inv_no`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Step 3: Create general_request_attachments table
CREATE TABLE IF NOT EXISTS `general_request_attachments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_inv_no` varchar(255) NOT NULL,
  `attachment` varchar(255) NOT NULL,
  `docu_ext` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `request_inv_no` (`request_inv_no`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Step 4: Insert request type into approval_request_types
-- Use INSERT IGNORE to prevent duplicate key errors if entry already exists
INSERT IGNORE INTO `approval_request_types` (`type_name`, `main_table_name`) 
VALUES ('General Request', 'general_requests');

-- Alternative: Use ON DUPLICATE KEY UPDATE if you want to ensure the mapping is correct
-- INSERT INTO `approval_request_types` (`type_name`, `main_table_name`) 
-- VALUES ('General Request', 'general_requests')
-- ON DUPLICATE KEY UPDATE `main_table_name` = 'general_requests';

-- Step 5: Verify installation
SELECT 'General Requests System Tables Created Successfully!' as Status;

-- Optional: Check if tables were created
SHOW TABLES LIKE 'general_request%';

-- Optional: Verify approval_request_types entry
SELECT * FROM `approval_request_types` WHERE `main_table_name` = 'general_requests';

-- Installation Complete!
-- Next Steps:
-- 1. Create upload directory: mkdir assets/general_request_attachments && chmod 777 assets/general_request_attachments
-- 2. Verify all PHP files are in place (see GENERAL_REQUESTS_README.md)
-- 3. Add menu entry to includes/main_menu.php (optional)
-- 4. Test by creating a new general request
