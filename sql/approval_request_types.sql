-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 22, 2025 at 11:49 AM
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
-- Table structure for table `approval_request_types`
--

CREATE TABLE `approval_request_types` (
  `id` int(11) NOT NULL,
  `type_name` varchar(100) NOT NULL,
  `main_table_name` varchar(100) NOT NULL COMMENT 'e.g., smart_request, loan_request'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `approval_request_types`
--

INSERT INTO `approval_request_types` (`id`, `type_name`, `main_table_name`) VALUES
(1, 'smart_request', 'smart_request'),
(2, 'loan_request', 'emp_loan'),
(3, 'vacation_request', 'emp_vacation'),
(4, 'resignation_request', 'resignation_request'),
(5, 'rejoin_request', 'rejoin_request'),
(6, 'general_request', 'general_requests');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `approval_request_types`
--
ALTER TABLE `approval_request_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `type_name` (`type_name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `approval_request_types`
--
ALTER TABLE `approval_request_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
