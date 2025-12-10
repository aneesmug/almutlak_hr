<?php
/**
 * Database Migration: Add Rejoin Approval System
 * 
 * This migration adds tables and columns to support the employee rejoin approval workflow.
 * Allows supervisors to approve/adjust/reject employee rejoin dates after vacation.
 * 
 * Created: December 2025
 */

require_once __DIR__ . '/../db.php';

try {
    $pdo = getDbConnection();
    
    // 1. Create rejoin_requests table for audit trail
    $createRejoinTable = "
        CREATE TABLE IF NOT EXISTS `rejoin_requests` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `emp_id` VARCHAR(20) NOT NULL,
            `vacation_id` INT UNSIGNED NOT NULL,
            `requested_rejoin_date` DATE NOT NULL COMMENT 'Date employee is requesting to rejoin',
            `requested_reason` TEXT DEFAULT NULL COMMENT 'Reason given by employee for date change',
            `requested_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `requested_by_emp_id` VARCHAR(20) DEFAULT NULL COMMENT 'Employee who submitted request (usually emp_id itself)',
            
            `status` ENUM('pending', 'approved', 'adjusted', 'rejected') DEFAULT 'pending',
            `approved_at` DATETIME DEFAULT NULL,
            `approved_by_emp_id` VARCHAR(20) DEFAULT NULL COMMENT 'Supervisor emp_id who approved',
            `approval_note` TEXT DEFAULT NULL COMMENT 'Note from supervisor on approval',
            
            `rejection_reason` TEXT DEFAULT NULL COMMENT 'If rejected, reason for rejection',
            
            `adjustment_allowed` BOOLEAN DEFAULT FALSE COMMENT 'If true, employee can adjust within date range',
            `adjustment_from_date` DATE DEFAULT NULL COMMENT 'Earliest date for adjustment',
            `adjustment_to_date` DATE DEFAULT NULL COMMENT 'Latest date for adjustment',
            `adjustment_reason_text` TEXT DEFAULT NULL COMMENT 'Supervisor reason for allowing adjustment',
            `adjustment_submitted_date` DATE DEFAULT NULL COMMENT 'Final date employee chose after adjustment',
            `adjustment_submitted_at` DATETIME DEFAULT NULL COMMENT 'When employee submitted adjusted date',
            
            `final_approved_date` DATE DEFAULT NULL COMMENT 'Final approved rejoin date',
            `final_approved_at` DATETIME DEFAULT NULL,
            
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX `idx_emp_id` (`emp_id`),
            INDEX `idx_vacation_id` (`vacation_id`),
            INDEX `idx_status` (`status`),
            INDEX `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='Tracks employee rejoin requests and approval workflow'
    ";
    $pdo->exec($createRejoinTable);
    
    // 2. Verify approval_request_types and request_approvers tables exist
    // Note: These tables should already exist in the system
    // This migration just verifies the rejoin_request type is configured
    
    $checkRequestType = $pdo->prepare("
        SELECT id FROM approval_request_types WHERE type_name = 'rejoin_request' AND main_table_name = 'rejoin_request'
    ");
    $checkRequestType->execute();
    $requestType = $checkRequestType->fetch(PDO::FETCH_ASSOC);
    
    if (!$requestType) {
        echo json_encode([
            'status' => 'warning',
            'message' => 'Please ensure approval_request_types table has entry for rejoin_request (id=5, type_name=\'rejoin_request\', main_table_name=\'rejoin_request\')'
        ]);
    }
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Rejoin approval system created successfully. Only rejoin_requests table added - using unified request_approvers for approval tracking.'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Migration failed: ' . $e->getMessage()
    ]);
}
?>
