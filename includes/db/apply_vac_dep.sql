-- phpMyAdmin SQL Dump
-- version 3.2.0.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Dec 12, 2019 at 08:20 AM
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
-- Table structure for table `apply_vac_dep`
--

CREATE TABLE IF NOT EXISTS `apply_vac_dep` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `emp_id` varchar(100) NOT NULL,
  `emp_name` varchar(120) NOT NULL,
  `dept` varchar(50) NOT NULL,
  `status` varchar(50) NOT NULL,
  `vac_strt_date` varchar(255) NOT NULL,
  `return_date` varchar(255) NOT NULL,
  `jion_date` varchar(255) NOT NULL,
  `last_vac_date` varchar(255) NOT NULL,
  `next_vac_date` varchar(255) NOT NULL,
  `vac_type` varchar(100) NOT NULL,
  `review` varchar(10) NOT NULL,
  `replacement_per` varchar(255) NOT NULL,
  `ticket_pay` varchar(255) NOT NULL,
  `permit_fee` varchar(100) NOT NULL,
  `empgid` varchar(150) NOT NULL,
  `hr_note` varchar(255) NOT NULL,
  `gm_note` varchar(255) NOT NULL,
  `date_reg` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=81 ;

--
-- Dumping data for table `apply_vac_dep`
--

INSERT INTO `apply_vac_dep` (`id`, `emp_id`, `emp_name`, `dept`, `status`, `vac_strt_date`, `return_date`, `jion_date`, `last_vac_date`, `next_vac_date`, `vac_type`, `review`, `replacement_per`, `ticket_pay`, `permit_fee`, `empgid`, `hr_note`, `gm_note`, `date_reg`) VALUES
(2, '1075', 'Kuttiman Pettikal', 'Maintenance', 'approve', '15/04/2019', '15/05/2019', '01/10/2002', '18/07/2016', '15/04/2020', 'annual', 'C', 'Mohammed Syed Hussain', '2100', '200', '20', '', '', '2019-04-16T14:12:56+03:00'),
(9, '4519', 'Yousuf Salem Dosh Al Harithy', 'Production ', 'approve', '20/04/2019', '20/05/2019', '09/02/2017', '', '20/04/2020', 'annual', 'C', 'Ahmed Hussain Ahmed Habtour', '', '', '64', '', '', '2019-04-30T10:55:28+03:00'),
(8, '4453', 'Noura Saleh Bashinini', 'Production ', 'approve', '20/04/2019', '20/05/2019', '24/04/2018', '', '20/04/2020', 'annual', 'C', 'Maha Ahmed Al Khasami', '', '', '78', '', '', '2019-04-30T10:50:01+03:00'),
(10, '4455', 'Awatif Saad Al Amri', 'Production ', 'approve', '27/04/2019', '27/05/2019', '06/05/2018', '', '27/04/2020', 'annual', 'C', 'Huda Saad Al Amri', '', '', '80', '', '', '2019-04-30T10:56:32+03:00'),
(11, '1064', 'Mohammed Imtiaz Idris ', 'POS', 'approve', '06/05/2019', '04/06/2019', '13/10/2002', '', '06/05/2021', 'annual', 'C', 'Omer Faruk', '2100', '300', '17', '', '', '2019-05-02T14:07:58+03:00'),
(12, '1168', 'Air Ahmed Hussain', 'POS', 'approve', '06/05/2019', '05/07/2019', '23/09/2005', '', '06/05/2021', 'annual', 'C', 'Deni Rohyaman', '2100', '300', '36', '', '', '2019-05-02T14:12:09+03:00'),
(13, '1198', 'Abul kasim Ismail', 'POS', 'approve', '06/05/2019', '05/07/2019', '02/04/2007', '', '06/05/2021', 'annual', 'C', 'Mohammed Jamaluddin', '2100', '300', '39', '', '', '2019-05-02T14:14:56+03:00'),
(14, '1101', 'Shaheen Ahmed Mannan', 'POS', 'approve', '06/05/2019', '05/07/2019', '05/04/2003', '', '06/05/2021', 'annual', 'C', 'M.Hassan Khursheed', '2100', '300', '25', '', '', '2019-05-02T14:24:53+03:00'),
(15, '1133', 'Zulfu Miah Siraj Miah', 'POS', 'approve', '06/05/2019', '05/07/2019', '04/05/2004', '', '05/07/2021', 'annual', 'C', 'Mamoon Babar Ali', '2100', '300', '31', '', '', '2019-05-02T14:29:03+03:00'),
(16, '1117', 'Mujib ul haq', 'POS', 'approve', '06/05/2019', '05/07/2019', '15/12/2003', '', '08/05/2021', 'annual', 'C', 'Md. Rabiul Islam', '2600', '300', '29', '', '', '2019-05-02T14:31:04+03:00'),
(17, '4415', 'Fatimah Kamiran Disumimba', 'POS', 'approve', '07/05/2019', '06/07/2019', '28/09/2013', '', '07/05/2021', 'annual', 'A', 'Shabbir Ahmed Mannan', '2950', '300', '68', '', '', '2019-05-02T14:34:01+03:00'),
(18, '4416', 'Rosaline Aguilar Cabayao', 'POS', 'approve', '07/05/2019', '06/07/2019', '28/09/2013', '', '01/05/2021', 'annual', 'C', 'Anwar Hussain', '2950', '300', '69', '', '', '2019-05-02T14:35:10+03:00'),
(19, '1088', 'Mujeeb Rehman Koziyan', 'POS', 'approve', '06/05/2019', '05/07/2019', '05/01/2003', '', '05/05/2021', 'annual', 'C', 'Aslam Umar Kutty ', '2250', '300', '23', '', '', '2019-05-02T14:37:27+03:00'),
(20, '4435', 'Nahlah Ali Attiyah Al Harbi', 'POS', 'approve', '06/05/2019', '05/06/2019', '18/03/2015', '', '24/04/2020', 'annual', 'C', 'Mazin Tafazzul', '', '', '125', '', '', '2019-05-05T11:33:52+03:00'),
(21, '4425', 'Wijdan Mohammed Ayedh Al Salmi', 'POS', 'approve', '06/05/2019', '05/06/2019', '18/08/2014', '', '24/04/2020', 'annual', 'C', 'Atabur Rahman Taib', '', '', '71', '', '', '2019-05-05T11:36:37+03:00'),
(22, '4448', 'Rahaf Al Jedani', 'POS', 'approve', '06/05/2019', '05/06/2019', '06/12/2017', '', '24/04/2020', 'annual', 'C', 'Haris Vadekkepurath', '', '', '72', '', '', '2019-05-05T11:57:53+03:00'),
(23, '4403', 'Hatoon Ehab Yousuf Jan', 'POS', 'approve', '06/05/2019', '05/06/2019', '21/02/2013', '', '24/04/2020', 'annual', 'C', 'Abul Kasim Abdul Kader', '', '', '67', '', '', '2019-05-05T11:59:33+03:00'),
(24, '4420', 'Tihani Mohammed Sultan Al Qarni', 'POS', 'approve', '06/05/2019', '05/06/2019', '22/12/2013', '', '24/04/2020', 'annual', 'C', 'Jahangir Hussain', '', '', '70', '', '', '2019-05-05T12:00:59+03:00'),
(25, '4456', 'Roua Hadi Hassan Muallim', 'POS', 'approve', '06/05/2019', '05/06/2019', '10/10/2018', '', '24/04/2020', 'annual', 'C', 'Ramjan Maih', '', '', '74', '', '', '2019-05-05T12:02:58+03:00'),
(26, '1003', 'Abul Bashar Abdul Matin', 'Sales Department', 'approve', '03/06/2019', '08/07/2019', '06/03/2000', '', '30/05/2021', 'annual', 'C', 'Yasser Halaby', '2200', '200', '2', '', '', '2019-05-22T12:32:20+03:00'),
(44, '4522', 'Ahmed Hussain Ahmed Habtour', 'Production ', 'approve', '16/06/2019', '15/07/2019', '19/09/2017', '', '16/06/2020', 'annual', 'A', 'Siddiq Kallingal', '', '', '65', 'He return back from his vacation', '', '2019-06-13T11:23:24+03:00'),
(48, '1402', 'Nurul Islam Abdul Hakim', 'Transportation', 'approve', '26/06/2019', '', '24/06/2013', '01/07/2017', '23/06/2021', 'Encashed', 'C', '', '', '', '55', 'he need the salary of the vacation ', '', '2019-06-20T12:22:59+03:00'),
(47, '1024', 'Yousuf Abul Fayaz', 'Maintenance', 'approve', '01/07/2019', '', '07/06/2002', '', '01/07/2020', 'Encashed', 'A', '', '', '', '11', '', '', '2019-06-19T11:27:47+03:00'),
(49, '1400', 'Mohammed Syed Hussain', 'Maintenance', 'approve', '01/10/2019', '', '01/05/2013', '', '01/07/2021', 'Encashed', 'A', '', '', '', '54', '', '', '2019-06-25T13:18:36+03:00'),
(50, '1119', 'Abul Kashem Amin', 'POS', 'approve', '15/07/2019', '13/09/2019', '21/12/2003', '', '31/08/2021', 'annual', 'A', 'Abdul Rahim', '2400', '200', '30', '', '', '2019-07-02T09:30:14+03:00'),
(51, '1178', 'Salem Noor Hussain', 'Maintenance', 'approve', '10/12/2019', '', '25/11/2007', '', '10/12/2022', 'Encashed', 'A', '', '', '', '37', '', '', '2019-08-01T12:25:58+03:00'),
(57, '1113', 'Nur ul Huda Zain al Abidin', 'POS', 'approve', '02/10/2019', '04/12/2019', '05/06/2003', '', '01/10/2021', 'annual', 'A', 'Aslam Umar Kutty ', '2500', '300', '27', '', '', '2019-09-10T14:18:56+03:00'),
(53, '29', 'Abdul Malik Shahul', 'Purchase', 'approve', '19/09/2019', '27/10/2019', '03/06/2006', '', '19/09/2020', 'annual', 'C', 'Mohammed Khairuddin', '2700', '200', '85', '', '', '2019-08-25T12:26:05+03:00'),
(58, '1410', 'Shafar Ajim Azeemullah', 'Warehouse', 'approve', '20/10/2019', '19/12/2019', '28/09/2013', '20/07/2017', '20/10/2021', 'annual', 'A', 'Subair Kappen', '2400', '200', '59', '', '', '2019-10-01T09:48:35+03:00'),
(59, '4517', 'Othman Ahmed Bashanum', 'POS', 'approve', '01/11/2019', '01/12/2019', '12/10/2016', '01/11/2018', '01/11/2020', '', 'A', 'Azam Esam Mohammed Daly', '', '', '63', '', '', '2019-10-06T11:19:42+03:00'),
(60, '1021', 'Basheer Vellaran', 'POS', 'approve', '01/11/2019', '01/12/2019', '08/05/2002', '18/10/2018', '01/11/2020', 'annual', 'C', 'Sajjad Hussain ', '2297', '200', '8', '', '', '2019-10-06T11:23:26+03:00'),
(61, '1359', 'Saepollah Sudin Yani', 'POS', 'app_hr', '21/10/2019', '20/12/2019', '01/04/2013', '14/06/2015', '25/10/2021', 'annual', 'A', 'Ajger Ali Mohammed', '2650', '300', '51', '', '', '2019-10-21T11:40:06+03:00'),
(62, '1252', 'Mohammed Ibrahim Khalil', 'POS', 'app_hr', '25/11/2019', '23/01/2020', '07/11/2008', '31/07/2017', '25/11/2022', 'annual', 'A', 'Abul kasim Ismail', '2696', '300', '45', '', '', '2019-11-06T12:28:29+03:00'),
(63, '1116', 'Shabbir Ahmed Mannan', 'POS', 'not_approve', '08/12/2019', '05/02/2020', '', '', '', 'annual', 'C', 'Nur ul Huda Zain al Abidin', '', '', '5', 'Rejected from HR', '', '2019-11-06T13:28:31+03:00'),
(72, '1405', 'Abdullah Fazlur Rahim', 'Transportation', 'app_hr', '30/12/2019', '26/02/2020', '21/07/2013', '30/09/2017', '30/12/2022', 'annual', 'A', 'Azhar Ali Jamali', '2000', '300', '57', '', '', '2019-11-24T15:49:52+03:00'),
(65, '1411', 'Ishak Abdulmatloob', 'Warehouse', 'app_hr', '30/11/2019', '29/01/2020', '05/01/2014', '01/09/2017', '30/11/2022', 'Encashed', 'A', '', '', '', '60', 'vacation encashment ', '', '2019-11-20T09:53:38+03:00'),
(73, '4620', 'Sultan Yassin Ahmed Al Delame', 'HRD and Housing', 'app_hr', '15/12/2019', '11/01/2020', '20/08/2015', '02/12/2018', '02/12/2020', 'annual', 'A', 'Abeer Hassan Al Gamdi', '', '', '93', '', '', '2019-11-27T12:13:15+03:00'),
(74, '1412', 'Abdul HaiAbdul Matloob', 'Warehouse', 'apply', '15/12/2019', '', '', '', '', 'Encashed', 'A', '', '', '', '61', '', '', '2019-12-03T10:59:33+03:00'),
(76, '147', 'Abdul Razak Al Fadl', 'Public Relation', 'app_hr', '04/12/2019', '', '01/09/2016', '04/12/2018', '04/12/2020', 'Encashed', 'A', '', '', '', '95', '', '', '2019-12-05T11:52:07+03:00');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
