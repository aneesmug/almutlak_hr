-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 13, 2025 at 06:51 AM
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
-- Table structure for table `emp_loan`
--

CREATE TABLE `emp_loan` (
  `id` int(11) NOT NULL,
  `inv_no` varchar(255) DEFAULT NULL COMMENT 'Unique loan request identifier (LOAN-YYYYMMDD-EMPID-HASH)',
  `emp_id` varchar(20) NOT NULL,
  `loan_type` enum('regular','emergency','end_of_service','housing','advance_salary') NOT NULL DEFAULT 'end_of_service',
  `loan_amount` decimal(10,2) NOT NULL COMMENT 'Principal loan amount requested',
  `installments` int(11) NOT NULL DEFAULT 1 COMMENT 'Number of monthly installments',
  `payment_proof_file` varchar(255) DEFAULT NULL COMMENT 'Payment proof uploaded by finance officer',
  `final_approved_amount` decimal(10,2) DEFAULT NULL COMMENT 'Final amount approved and paid by finance officer',
  `reason` text DEFAULT NULL COMMENT 'Reason for loan application',
  `interest_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `total_payable` decimal(10,2) NOT NULL COMMENT 'Total amount to be repaid (same as loan_amount for new loans)',
  `monthly_deduction` decimal(10,2) NOT NULL COMMENT 'Amount deducted per month',
  `start_date` date NOT NULL COMMENT 'Date when deductions start (first day of next month)',
  `end_date` date DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'pending_level_1' COMMENT 'Status: pending_level_1 to pending_level_6, approved, rejected, paid',
  `current_approval_level` int(11) DEFAULT 1 COMMENT 'Current approval level in chain',
  `rejected_by` varchar(50) DEFAULT NULL COMMENT 'User ID who rejected',
  `rejection_reason` text DEFAULT NULL COMMENT 'Reason for rejection',
  `rejection_date` datetime DEFAULT NULL COMMENT 'Date of rejection',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `disbursement_receipt_id` varchar(255) DEFAULT NULL,
  `disbursement_attachment` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `emp_loan`
--
ALTER TABLE `emp_loan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_inv_no` (`inv_no`),
  ADD KEY `idx_loan_type` (`loan_type`),
  ADD KEY `idx_emp_status` (`emp_id`,`status`),
  ADD KEY `idx_current_approval` (`current_approval_level`),
  ADD KEY `idx_status_level` (`status`,`current_approval_level`),
  ADD KEY `idx_payment_proof` (`payment_proof_file`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `emp_loan`
--
ALTER TABLE `emp_loan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
