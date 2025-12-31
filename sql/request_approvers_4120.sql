-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Dec 30, 2025 at 03:22 PM
-- Server version: 10.11.15-MariaDB
-- PHP Version: 8.4.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `almutlak_hr_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `request_approvers`
--

CREATE TABLE `request_approvers` (
  `id` int(11) NOT NULL,
  `request_inv_no` varchar(255) NOT NULL COMMENT 'Links to inv_no in smart_request or other tables',
  `request_type_id` int(11) NOT NULL,
  `approver_id` int(11) NOT NULL COMMENT 'The emp_id of the approver',
  `approval_level` int(11) NOT NULL COMMENT '1 for 1st, 2 for 2nd, etc.',
  `status` enum('pending','approved','rejected','awaiting') NOT NULL DEFAULT 'awaiting',
  `note` text DEFAULT NULL,
  `payment_amount` decimal(10,2) DEFAULT NULL COMMENT 'Amount paid by the payer (for payer role)',
  `payment_proof_path` varchar(500) DEFAULT NULL COMMENT 'Path to payment proof document',
  `action_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `request_approvers`
--

INSERT INTO `request_approvers` (`id`, `request_inv_no`, `request_type_id`, `approver_id`, `approval_level`, `status`, `note`, `payment_amount`, `payment_proof_path`, `action_date`) VALUES
(491, 'VAC-20251227182618-5235-235b', 3, 4120, 4, 'awaiting', NULL, NULL, NULL, NULL),
(509, 'VAC-20251228135026-4315-d082', 3, 4120, 4, 'pending', NULL, NULL, NULL, NULL),
(522, 'VAC-20251229135925-5138-aa64', 3, 4120, 4, 'awaiting', NULL, NULL, NULL, NULL),
(526, 'VAC-20251229153344-3767-aeef', 3, 4120, 4, 'awaiting', NULL, NULL, NULL, NULL),
(530, 'VAC-20251229155244-5264-e0c7', 3, 4120, 4, 'awaiting', NULL, NULL, NULL, NULL),
(534, 'VAC-20251229155357-5406-226c', 3, 4120, 4, 'awaiting', NULL, NULL, NULL, NULL),
(538, 'VAC-20251229155509-5033-3a96', 3, 4120, 4, 'awaiting', NULL, NULL, NULL, NULL),
(542, 'VAC-20251229155701-4957-a6a2', 3, 4120, 4, 'awaiting', NULL, NULL, NULL, NULL),
(546, 'VAC-20251229155806-4949-e30b', 3, 4120, 4, 'awaiting', NULL, NULL, NULL, NULL),
(558, 'VAC-20251230085928-3015-78df', 3, 4120, 4, 'awaiting', NULL, NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `request_approvers`
--
ALTER TABLE `request_approvers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_inv_no` (`request_inv_no`),
  ADD KEY `request_type_id` (`request_type_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `request_approvers`
--
ALTER TABLE `request_approvers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=559;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `request_approvers`
--
ALTER TABLE `request_approvers`
  ADD CONSTRAINT `fk_request_type` FOREIGN KEY (`request_type_id`) REFERENCES `approval_request_types` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
