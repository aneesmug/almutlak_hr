-- Business Trip Request Table Migration
-- This table stores all business trip requests with approval workflow

CREATE TABLE IF NOT EXISTS `emp_business_trip` (
    `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `request_inv_no` VARCHAR(100) NOT NULL UNIQUE COMMENT 'Business Trip Request Unique ID (BT-YYYYMMDD-xxxxx)',
    `emp_id` VARCHAR(255) NOT NULL COMMENT 'Employee ID making the request',
    `company_id` INT(11) COMMENT 'Company ID for multi-company support',
    
    -- Trip Type & Destination
    `trip_type` ENUM('domestic', 'international') NOT NULL DEFAULT 'domestic' COMMENT 'Local/Domestic or International trip',
    `trip_purpose` VARCHAR(255) COMMENT 'Purpose of the business trip',
    
    -- Domestic Trip Specific
    `transportation_type` ENUM('own_car', 'rental') COMMENT 'Own car or rental (for domestic trips only)',
    `rental_car_details` TEXT COMMENT 'Rental car specification if applicable',
    
    -- Trip Dates & Time
    `trip_start_date` DATE NOT NULL COMMENT 'Travel departure date and time',
    `trip_start_time` TIME COMMENT 'Departure time',
    `trip_end_date` DATE NOT NULL COMMENT 'Return date',
    `trip_end_time` TIME COMMENT 'Return time',
    
    -- Cities
    `from_city_id` INT(11) COMMENT 'Departure city ID from saudi_cities table',
    `from_city_name` VARCHAR(255) COMMENT 'Departure city name (cached)',
    `to_city_id` INT(11) COMMENT 'Destination city ID from saudi_cities table',
    `to_city_name` VARCHAR(255) COMMENT 'Destination city name (cached)',
    
    -- International Trip Specific
    `destination_country` VARCHAR(100) COMMENT 'Destination country for international trips',
    `visa_required` TINYINT(1) DEFAULT 0 COMMENT 'Whether visa is required',
    
    -- Approval Workflow
    `current_status` ENUM('pending_approval', 'rejected', 'approved', 'completed', 'cancelled') DEFAULT 'pending_approval' COMMENT 'Current workflow status',
    `current_approval_level` INT(11) DEFAULT 1 COMMENT 'Current approval stage (1, 2, 3, etc.)',
    
    -- Notes & Attachments
    `request_notes` LONGTEXT COMMENT 'Additional notes or requirements',
    `created_by` INT(11) COMMENT 'User who created the request',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Request submission timestamp',
    
    -- Tracking
    `last_modified` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last modification timestamp',
    `modified_by` INT(11) COMMENT 'User who last modified the request',
    
    -- Indexes for performance
    KEY `idx_emp_id` (`emp_id`),
    KEY `idx_request_inv_no` (`request_inv_no`),
    KEY `idx_status` (`current_status`),
    KEY `idx_approval_level` (`current_approval_level`),
    KEY `idx_company_id` (`company_id`),
    KEY `idx_created_at` (`created_at`),
    
    -- Foreign Keys
    CONSTRAINT `fk_emp_business_trip_emp` FOREIGN KEY (`emp_id`) REFERENCES `employees` (`emp_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci COMMENT='Business Trip Requests with Multi-Level Approval';

-- Ensure approval_chain_business_trip setting exists in app_settings
INSERT IGNORE INTO `app_settings` 
(`setting_name`, `setting_value`, `setting_group`, `input_type`, `description`) 
VALUES 
(
    'approval_chain_business_trip',
    '[{"level":1,"user_type":"direct_supervisor","role_label":"Direct Supervisor"},{"level":2,"user_type":"hr_senior_bp","role_label":"HR Senior BP"},{"level":3,"user_type":"finance_officer","role_label":"Finance Officer"}]',
    'approval',
    'json',
    'Business Trip Request Approval Chain Configuration'
);
