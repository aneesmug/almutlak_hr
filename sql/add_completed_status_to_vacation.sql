-- SQL Migration: Add 'completed' status to emp_vacation table
-- This allows marking vacations as completed so employees can apply for new vacations

ALTER TABLE `emp_vacation` 
MODIFY `current_status` enum('draft','pending_approval','approved','rejected','completed') 
NOT NULL DEFAULT 'draft' 
COMMENT 'Overall status of the request';
