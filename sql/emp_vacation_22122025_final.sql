-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 22, 2025 at 10:43 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `almutlak_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `emp_vacation`
--

CREATE TABLE `emp_vacation` (
  `id` int(11) NOT NULL,
  `request_inv_no` varchar(64) NOT NULL COMMENT 'Unique ID to link to request_approvers',
  `current_status` enum('draft','pending_approval','approved','rejected','completed') NOT NULL DEFAULT 'draft' COMMENT 'Overall status of the request',
  `current_approval_level` int(11) DEFAULT NULL COMMENT 'The current level pending approval',
  `emp_id` varchar(255) NOT NULL,
  `submitted_by_emp_id` int(11) DEFAULT NULL,
  `start_date` varchar(255) NOT NULL,
  `user_update` varchar(255) NOT NULL,
  `return_date` varchar(50) NOT NULL,
  `departure_date` date DEFAULT NULL COMMENT 'Flight departure date (for Fly + Annual vacations only)',
  `arrival_date` date DEFAULT NULL COMMENT 'Flight arrival date (for Fly + Annual vacations only)',
  `travel_email_sent` tinyint(1) DEFAULT 0 COMMENT 'Flag to track if travel company email has been sent (0=not sent, 1=sent)',
  `vacdays` int(50) NOT NULL,
  `vac_type` varchar(50) NOT NULL,
  `fly_type` enum('annual','emergency') DEFAULT NULL,
  `arrived_date` varchar(100) NOT NULL,
  `permit_no` varchar(100) NOT NULL,
  `remarks` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `vacation_salary_type` enum('payroll','end_of_service') DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `is_deductible` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = Deductible from annual balance, 0 = Not deductible',
  `review` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `note` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `replacement_person` varchar(100) DEFAULT NULL,
  `last_vac_date` date DEFAULT NULL,
  `next_vac_date` date DEFAULT NULL,
  `ticket_pay` decimal(10,2) DEFAULT NULL,
  `permit_fee` decimal(10,2) DEFAULT NULL,
  `encashment_amount` decimal(10,2) DEFAULT NULL COMMENT 'Encashed vacation days salary amount',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `overtime_hours` decimal(10,2) DEFAULT NULL COMMENT 'Overtime hours during vacation period',
  `deduction_hours` decimal(10,2) DEFAULT NULL COMMENT 'Deduction hours during vacation period',
  `deduction_days` decimal(10,2) DEFAULT NULL COMMENT 'Deduction days during vacation period',
  `payroll_note` text DEFAULT NULL COMMENT 'Payroll notes and remarks',
  `payment_status` enum('pending_payment','paid','needs_modification') DEFAULT 'pending_payment' COMMENT 'Payment status for final HR Payroll approval step',
  `payment_date` datetime DEFAULT NULL COMMENT 'Timestamp when payment was processed',
  `payment_modified_date` datetime DEFAULT NULL COMMENT 'Timestamp when payment was last modified',
  `payment_modified_by` varchar(50) DEFAULT NULL COMMENT 'Employee ID of user who modified payment',
  `is_payment_completed` tinyint(1) DEFAULT 0 COMMENT 'Flag: 0=payment pending, 1=payment processing complete',
  `accommodation_provided` enum('yes','no') DEFAULT NULL COMMENT 'Is accommodation provided by the company (Business Trip)',
  `transportation_provided` enum('yes','no') DEFAULT NULL COMMENT 'Is transportation provided by the company (Business Trip)'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `emp_vacation`
--
ALTER TABLE `emp_vacation`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_emp_vacation_request_inv_no` (`request_inv_no`),
  ADD KEY `idx_request_inv_no` (`request_inv_no`),
  ADD KEY `idx_vacation_salary_type` (`vacation_salary_type`),
  ADD KEY `idx_travel_email_sent` (`travel_email_sent`),
  ADD KEY `idx_payment_status` (`payment_status`);

-- --------------------------------------------------------

--
-- ALTER TABLE statements to ADD payment-related columns
--

ALTER TABLE `emp_vacation`
  ADD COLUMN `payment_status` enum('pending_payment','paid','needs_modification') DEFAULT 'pending_payment' COMMENT 'Payment status for final HR Payroll approval step' AFTER `payroll_note`,
  ADD COLUMN `payment_date` datetime DEFAULT NULL COMMENT 'Timestamp when payment was processed' AFTER `payment_status`,
  ADD COLUMN `payment_modified_date` datetime DEFAULT NULL COMMENT 'Timestamp when payment was last modified' AFTER `payment_date`,
  ADD COLUMN `payment_modified_by` varchar(50) DEFAULT NULL COMMENT 'Employee ID of user who modified payment' AFTER `payment_modified_date`,
  ADD COLUMN `is_payment_completed` tinyint(1) DEFAULT 0 COMMENT 'Flag: 0=payment pending, 1=payment processing complete' AFTER `payment_modified_by`,
  ADD INDEX `idx_payment_date` (`payment_date`),
  ADD INDEX `idx_payment_modified_date` (`payment_modified_date`),
  ADD INDEX `idx_is_payment_completed` (`is_payment_completed`);

-- --------------------------------------------------------

--
-- ALTER TABLE statements to REMOVE payment-related columns
-- (Uncomment these if you need to remove the columns)
--

-- ALTER TABLE `emp_vacation`
--   DROP COLUMN `payment_status`,
--   DROP COLUMN `payment_date`,
--   DROP COLUMN `payment_modified_date`,
--   DROP COLUMN `payment_modified_by`,
--   DROP COLUMN `is_payment_completed`,
--   DROP INDEX `idx_payment_date`,
--   DROP INDEX `idx_payment_modified_date`,
--   DROP INDEX `idx_is_payment_completed`;

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `emp_vacation`
--
ALTER TABLE `emp_vacation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
