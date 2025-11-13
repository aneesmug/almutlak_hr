-- phpMyAdmin SQL Dump
-- version 3.2.0.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: May 19, 2020 at 02:37 PM
-- Server version: 5.1.37
-- PHP Version: 5.3.0

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `hr_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `department`
--

CREATE TABLE IF NOT EXISTS `department` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dep_nme` varchar(255) NOT NULL,
  `mobile` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `dep_mang` varchar(255) NOT NULL,
  `designation` varchar(255) NOT NULL,
  `reg_date` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=16 ;

--
-- Dumping data for table `department`
--

INSERT INTO `department` (`id`, `dep_nme`, `mobile`, `email`, `dep_mang`, `designation`, `reg_date`) VALUES
(1, 'Administration', '0505614919', 'mem@mochachino.co', '', 'General Manager', ''),
(2, 'Finance', '0509365756', 'abackar@mochachino.co', '', 'Finance Manager', ''),
(3, 'HRD and Housing', '', '', '', '', ''),
(4, 'Public Relation', '', '', '', '', ''),
(5, 'Sales Department', '', '', '', '', ''),
(6, 'Inspection', '', '', '', '', ''),
(7, 'Purchase', '', '', '', '', ''),
(8, 'IT', '0599723451', 'anees@mochachino.co', '', 'IT Manager', ''),
(9, 'Production ', '', '', '', '', ''),
(10, 'Transportation', '', '', '', '', ''),
(11, 'Warehouse', '', '', '', '', ''),
(12, 'Maintenance', '', '', '', '', ''),
(13, 'Management', '', '', '', '', ''),
(14, 'POS', '', '', '', '', ''),
(15, 'General', '', '', '', '', '');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
