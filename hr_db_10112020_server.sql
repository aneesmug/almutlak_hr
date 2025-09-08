-- phpMyAdmin SQL Dump
-- version 3.2.0.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Nov 10, 2020 at 08:27 AM
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
-- Table structure for table `activity_log`
--

CREATE TABLE IF NOT EXISTS `activity_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_editor` varchar(100) NOT NULL,
  `page` varchar(255) NOT NULL,
  `pg_id` varchar(255) NOT NULL,
  `reg_date` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=27 ;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `user_editor`, `page`, `pg_id`, `reg_date`) VALUES
(1, 'daniah', 'add_emp_docs.php', 'BaldiaCard', '2020-10-15T12:14:45+03:00'),
(2, 'daniah', 'add_emp_docs.php', 'BaldiaCard', '2020-10-15T12:16:10+03:00'),
(3, 'daniah', 'add_emp_docs.php', 'BaldiaCard', '2020-10-15T12:16:59+03:00'),
(4, 'daniah', 'add_emp_docs.php', 'BaldiaCard', '2020-10-15T12:17:39+03:00'),
(5, 'daniah', 'add_emp_docs.php', 'BaldiaCard', '2020-10-15T12:18:11+03:00'),
(6, 'daniah', 'add_emp_docs.php', 'BaldiaCard', '2020-10-15T12:18:42+03:00'),
(7, 'daniah', 'add_emp_docs.php', 'Iqama', '2020-10-15T12:22:13+03:00'),
(8, 'daniah', 'apply_vac_emp_dept.php', '', '2020-10-15T12:28:21+03:00'),
(9, 'daniah', 'open_vac_aply.php', '117', '2020-10-15T12:28:31+03:00'),
(10, 'daniah', 'vac_app_emp.php', '117', '2020-10-15T12:28:46+03:00'),
(11, 'daniah', 'add_vac_emp.php', '98', '2020-10-15T12:29:03+03:00'),
(12, 'daniah', 'arrived_emp.php', '98', '2020-10-15T12:29:15+03:00'),
(13, 'daniah', 'login.php', 'login', '2020-10-25T11:10:02+03:00'),
(14, 'daniah', 'add_emp_docs.php', 'Others', '2020-10-26T12:29:12+03:00'),
(15, 'daniah', 'arrived_emp.php', '225', '2020-10-27T09:01:14+03:00'),
(16, 'daniah', 'terminat_emp.php', '96', '2020-11-02T11:10:40+03:00'),
(17, 'daniah', 'arrived_emp.php', '125', '2020-11-02T11:18:10+03:00'),
(18, 'sajjad', 'login.php', 'login', '2020-11-02T11:24:20+03:00'),
(19, 'sajjad', 'apply_vac_emp_dept.php', '', '2020-11-02T11:25:41+03:00'),
(20, 'daniah', 'terminat_emp.php', '225', '2020-11-03T15:59:19+03:00'),
(21, 'daniah', 'terminat_emp.php', '227', '2020-11-03T15:59:46+03:00'),
(22, 'daniah', 'open_vac_aply.php', '118', '2020-11-03T16:05:37+03:00'),
(23, 'daniah', 'vac_app_emp.php', '118', '2020-11-03T16:05:56+03:00'),
(24, 'daniah', 'add_vac_emp.php', '221', '2020-11-03T16:06:24+03:00'),
(25, 'sajjad', 'apply_vac_emp_dept.php', '', '2020-11-09T11:32:27+03:00'),
(26, 'sajjad', 'apply_vac_emp_dept.php', '', '2020-11-09T11:35:53+03:00');

-- --------------------------------------------------------

--
-- Table structure for table `admin_login`
--

CREATE TABLE IF NOT EXISTS `admin_login` (
  `id` int(255) NOT NULL AUTO_INCREMENT,
  `emp_id` int(11) NOT NULL,
  `firstname` varchar(255) NOT NULL,
  `lastname` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `user_type` varchar(255) NOT NULL COMMENT '(administrator, hr, gm, dept_user)',
  `dept` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_pass` varchar(100) NOT NULL,
  `mobile` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `avatar` varchar(255) NOT NULL,
  `date_reg` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=19 ;

--
-- Dumping data for table `admin_login`
--

INSERT INTO `admin_login` (`id`, `emp_id`, `firstname`, `lastname`, `username`, `user_type`, `dept`, `email`, `email_pass`, `mobile`, `password`, `avatar`, `date_reg`) VALUES
(1, 152, 'Anees', 'Mughal', 'root', 'administrator', 'IT', 'anees@mochachino.co', 'Hain6539306', '0599723451', '036d0ef7567a20b5a4ad24a354ea4a945ddab676', 'assets/emp_pics/2337318717.29512160_2101570866527042_5368874645299751559_n.jpg', '2019-04-11T11:22:53+03:00'),
(5, 2, 'Mohammed Medher', '', 'gm', 'gm', 'Management', 'vac@mochachino.co', 'Hain6539306', '0505614919', '6d5224517d04f3f196dec6c9af735ca7775be044', './assets/emp_pics/1042798981.55640004_001.jpg ', '2019-04-11T11:22:53+03:00'),
(13, 42, 'Sajjad Hussain ', '', 'sajjad', 'dept_user', 'POS', 's.hussain@mochachino.co', 'MocH#980', '0552779588', '6534b594f46dee264b2523ad9412c97d8b0ed4bb', './assets/emp_pics/2124096906.Sajjad.jpg', '2019-05-01T11:22:36+03:00'),
(17, 42, 'Sajjad Hussain', '', 'sajjad.h', 'dept_user', 'Maintenance', 's.hussain@mochachino.co', 'MocH#980', '0552779588', '6534b594f46dee264b2523ad9412c97d8b0ed4bb', './assets/emp_pics/2124096906.Sajjad.jpg', '2019-05-01T11:22:36+03:00'),
(9, 120, 'Hatim Shafi Felemban', '', 'hatim', 'dept_user', 'Sales Department', 'hatim@mochachino.co', 'MocH#698', '0505810469', 'b077402b04ee9d2214e2a0bca649a46a569a7752', './assets/emp_pics/1037099320.001001-0-0-0120-1.jpg ', '2019-05-01T09:55:45+03:00'),
(10, 1410, 'Shafar Ajim Azeemullah', '', 'shafer', 'dept_user', 'Warehouse', 'shafar@mochachino.co', 'MocH#670', '0537281483', '5ffb3c8c4c945b4fd2b473188c5f7e78137a95e6', './assets/emp_pics/2397222379.Shafar.jpg', '2019-05-01T09:57:11+03:00'),
(11, 10, 'Aboo Backer Kappil', '', 'accounts', 'dept_user', 'Finance', 'abackar@mochachino.co', 'MocH#9906', '0509365756', '6bcf86f65f63f32a4af4163f1d9f81f85a551dac', './assets/emp_pics/2182274023.001001-0-0-0010-1.jpg ', '2019-05-01T10:01:06+03:00'),
(12, 148, 'Mohammed Al Khayyat', '', 'mohammed', 'dept_user', 'Purchase', 'mkhayat@mochachino.co', 'MocH#976', '0505613569', '7b0b077b483ad7aaf6bf68b38a77009342a0aff6', './assets/emp_pics/1004145692.Mr. Khayat.jpg ', '2019-05-01T10:02:39+03:00'),
(14, 49, 'Abdul Wahab A. Ghafoor', '', 'awahab', 'dept_user', 'Inspection', 'awahab@mochachino.co', 'MocH#080', '0501497585', 'ad954369ed79fcfc9bc57af53c0deeb668456e87', './assets/emp_pics/2016697217.AbdulWahab.jpg ', '2019-05-01T14:20:25+03:00'),
(16, 1038, 'Siddiq Kallingal', '', 'siddiq', 'dept_user', 'Production ', 'siddiq@mochachino.co', 'MocH#976', '0561839446', '05c62d0f61e6bdfa34520e31d72fd24bd8a3d23e', './assets/emp_pics/2142998554.001001-0-0-1038-1.jpg ', '2019-05-01T14:24:02+03:00'),
(18, 156, 'Daniah', 'Mohammed', 'daniah', 'hr', 'HRD and Housing', 'hr@mochachino.co', 'MocH#988', '0565335759', '036d0ef7567a20b5a4ad24a354ea4a945ddab676', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQ3_GjrS6Rp3xhYawMfiedooiV7m6PHHB7zaaZKx4QcWFxFTt1w', '2019-04-11T11:22:53+03:00');

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
  `fly_type` varchar(100) NOT NULL,
  `review` varchar(10) NOT NULL,
  `replacement_per` varchar(255) NOT NULL,
  `ticket_pay` varchar(255) NOT NULL,
  `permit_fee` varchar(100) NOT NULL,
  `empgid` varchar(150) NOT NULL,
  `hr_note` varchar(255) NOT NULL,
  `gm_note` varchar(255) NOT NULL,
  `date_reg` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=121 ;

--
-- Dumping data for table `apply_vac_dep`
--

INSERT INTO `apply_vac_dep` (`id`, `emp_id`, `emp_name`, `dept`, `status`, `vac_strt_date`, `return_date`, `jion_date`, `last_vac_date`, `next_vac_date`, `vac_type`, `fly_type`, `review`, `replacement_per`, `ticket_pay`, `permit_fee`, `empgid`, `hr_note`, `gm_note`, `date_reg`) VALUES
(2, '1075', 'Kuttiman Pettikal', 'Maintenance', 'approve', '15/04/2019', '15/05/2019', '01/10/2002', '18/07/2016', '15/04/2020', 'annual', '', 'C', 'Mohammed Syed Hussain', '2100', '200', '20', '', '', '2019-04-16T14:12:56+03:00'),
(9, '4519', 'Yousuf Salem Dosh Al Harithy', 'Production ', 'approve', '20/04/2019', '20/05/2019', '09/02/2017', '', '20/04/2020', 'annual', '', 'C', 'Ahmed Hussain Ahmed Habtour', '', '', '64', '', '', '2019-04-30T10:55:28+03:00'),
(8, '4453', 'Noura Saleh Bashinini', 'Production ', 'approve', '20/04/2019', '20/05/2019', '24/04/2018', '', '20/04/2020', 'annual', '', 'C', 'Maha Ahmed Al Khasami', '', '', '78', '', '', '2019-04-30T10:50:01+03:00'),
(10, '4455', 'Awatif Saad Al Amri', 'Production ', 'approve', '27/04/2019', '27/05/2019', '06/05/2018', '', '27/04/2020', 'annual', '', 'C', 'Huda Saad Al Amri', '', '', '80', '', '', '2019-04-30T10:56:32+03:00'),
(11, '1064', 'Mohammed Imtiaz Idris ', 'POS', 'approve', '06/05/2019', '04/06/2019', '13/10/2002', '', '06/05/2021', 'annual', '', 'C', 'Omer Faruk', '2100', '300', '17', '', '', '2019-05-02T14:07:58+03:00'),
(12, '1168', 'Air Ahmed Hussain', 'POS', 'approve', '06/05/2019', '05/07/2019', '23/09/2005', '', '06/05/2021', 'annual', '', 'C', 'Deni Rohyaman', '2100', '300', '36', '', '', '2019-05-02T14:12:09+03:00'),
(13, '1198', 'Abul kasim Ismail', 'POS', 'approve', '06/05/2019', '05/07/2019', '02/04/2007', '', '06/05/2021', 'annual', '', 'C', 'Mohammed Jamaluddin', '2100', '300', '39', '', '', '2019-05-02T14:14:56+03:00'),
(14, '1101', 'Shaheen Ahmed Mannan', 'POS', 'approve', '06/05/2019', '05/07/2019', '05/04/2003', '', '06/05/2021', 'annual', '', 'C', 'M.Hassan Khursheed', '2100', '300', '25', '', '', '2019-05-02T14:24:53+03:00'),
(15, '1133', 'Zulfu Miah Siraj Miah', 'POS', 'approve', '06/05/2019', '05/07/2019', '04/05/2004', '', '05/07/2021', 'annual', '', 'C', 'Mamoon Babar Ali', '2100', '300', '31', '', '', '2019-05-02T14:29:03+03:00'),
(16, '1117', 'Mujib ul haq', 'POS', 'approve', '06/05/2019', '05/07/2019', '15/12/2003', '', '08/05/2021', 'annual', '', 'C', 'Md. Rabiul Islam', '2600', '300', '29', '', '', '2019-05-02T14:31:04+03:00'),
(17, '4415', 'Fatimah Kamiran Disumimba', 'POS', 'approve', '07/05/2019', '06/07/2019', '28/09/2013', '', '07/05/2021', 'annual', '', 'C', 'Shabbir Ahmed Mannan', '2950', '300', '68', '', '', '2019-05-02T14:34:01+03:00'),
(18, '4416', 'Rosaline Aguilar Cabayao', 'POS', 'approve', '07/05/2019', '06/07/2019', '28/09/2013', '', '01/05/2021', 'annual', '', 'C', 'Anwar Hussain', '2950', '300', '69', '', '', '2019-05-02T14:35:10+03:00'),
(19, '1088', 'Mujeeb Rehman Koziyan', 'POS', 'approve', '06/05/2019', '05/07/2019', '05/01/2003', '', '05/05/2021', 'annual', '', 'C', 'Aslam Umar Kutty ', '2250', '300', '23', '', '', '2019-05-02T14:37:27+03:00'),
(20, '4435', 'Nahlah Ali Attiyah Al Harbi', 'POS', 'approve', '06/05/2019', '05/06/2019', '18/03/2015', '', '24/04/2020', 'annual', '', 'C', 'Mazin Tafazzul', '', '', '125', '', '', '2019-05-05T11:33:52+03:00'),
(21, '4425', 'Wijdan Mohammed Ayedh Al Salmi', 'POS', 'approve', '06/05/2019', '05/06/2019', '18/08/2014', '', '24/04/2020', 'annual', '', 'C', 'Atabur Rahman Taib', '', '', '71', '', '', '2019-05-05T11:36:37+03:00'),
(22, '4448', 'Rahaf Al Jedani', 'POS', 'approve', '06/05/2019', '05/06/2019', '06/12/2017', '', '24/04/2020', 'annual', '', 'C', 'Haris Vadekkepurath', '', '', '72', '', '', '2019-05-05T11:57:53+03:00'),
(23, '4403', 'Hatoon Ehab Yousuf Jan', 'POS', 'approve', '06/05/2019', '05/06/2019', '21/02/2013', '', '24/04/2020', 'annual', '', 'C', 'Abul Kasim Abdul Kader', '', '', '67', '', '', '2019-05-05T11:59:33+03:00'),
(24, '4420', 'Tihani Mohammed Sultan Al Qarni', 'POS', 'approve', '06/05/2019', '05/06/2019', '22/12/2013', '', '24/04/2020', 'annual', '', 'C', 'Jahangir Hussain', '', '', '70', '', '', '2019-05-05T12:00:59+03:00'),
(25, '4456', 'Roua Hadi Hassan Muallim', 'POS', 'approve', '06/05/2019', '05/06/2019', '10/10/2018', '', '24/04/2020', 'annual', '', 'C', 'Ramjan Maih', '', '', '74', '', '', '2019-05-05T12:02:58+03:00'),
(26, '1003', 'Abul Bashar Abdul Matin', 'Sales Department', 'approve', '03/06/2019', '08/07/2019', '06/03/2000', '', '30/05/2021', 'annual', '', 'C', 'Yasser Halaby', '2200', '200', '2', '', '', '2019-05-22T12:32:20+03:00'),
(44, '4522', 'Ahmed Hussain Ahmed Habtour', 'Production ', 'approve', '16/06/2019', '15/07/2019', '19/09/2017', '', '16/06/2020', 'annual', '', 'A', 'Siddiq Kallingal', '', '', '65', 'He return back from his vacation', '', '2019-06-13T11:23:24+03:00'),
(48, '1402', 'Nurul Islam Abdul Hakim', 'Transportation', 'approve', '26/06/2019', '', '24/06/2013', '01/07/2017', '23/06/2021', 'Encashed', '', 'C', '', '', '', '55', 'he need the salary of the vacation ', '', '2019-06-20T12:22:59+03:00'),
(47, '1024', 'Yousuf Abul Fayaz', 'Maintenance', 'approve', '01/07/2019', '', '07/06/2002', '', '01/07/2020', 'Encashed', '', 'C', '', '', '', '11', '', '', '2019-06-19T11:27:47+03:00'),
(49, '1400', 'Mohammed Syed Hussain', 'Maintenance', 'approve', '01/10/2019', '', '01/05/2013', '', '01/07/2021', 'Encashed', '', 'C', '', '', '', '54', '', '', '2019-06-25T13:18:36+03:00'),
(50, '1119', 'Abul Kashem Amin', 'POS', 'approve', '15/07/2019', '13/09/2019', '21/12/2003', '', '31/08/2021', 'annual', '', 'C', 'Abdul Rahim', '2400', '200', '30', '', '', '2019-07-02T09:30:14+03:00'),
(51, '1178', 'Salem Noor Hussain', 'Maintenance', 'approve', '10/12/2019', '', '25/11/2007', '', '10/12/2022', 'Encashed', '', 'C', '', '', '', '37', '', '', '2019-08-01T12:25:58+03:00'),
(57, '1113', 'Nur ul Huda Zain al Abidin', 'POS', 'approve', '02/10/2019', '04/12/2019', '05/06/2003', '', '01/10/2021', 'annual', '', 'A', 'Aslam Umar Kutty ', '2500', '300', '27', '', '', '2019-09-10T14:18:56+03:00'),
(53, '29', 'Abdul Malik Shahul', 'Purchase', 'approve', '19/09/2019', '27/10/2019', '03/06/2006', '', '19/09/2020', 'annual', '', 'C', 'Mohammed Khairuddin', '2700', '200', '85', '', '', '2019-08-25T12:26:05+03:00'),
(113, '4403', 'Hatoon Ehab Yousuf Jan', 'POS', 'approve', '01/09/2020', '30/09/2020', '21/02/2013', '06/05/2019', '01/09/2021', 'Local Vacation', '', 'C', 'Nahlah Ali Attiyah Al Harbi', '', '', '67', '', '', '2020-08-30T14:08:22+03:00'),
(59, '4517', 'Othman Ahmed Bashanum', 'POS', 'approve', '01/11/2019', '01/12/2019', '12/10/2016', '01/11/2018', '01/11/2020', '', '', 'C', 'Azam Esam Mohammed Daly', '', '', '63', '', '', '2019-10-06T11:19:42+03:00'),
(60, '1021', 'Basheer Vellaran', 'POS', 'approve', '01/11/2019', '01/12/2019', '08/05/2002', '18/10/2018', '01/11/2020', 'annual', '', 'C', 'Sajjad Hussain ', '2297', '200', '8', '', '', '2019-10-06T11:23:26+03:00'),
(61, '1359', 'Saepollah Sudin Yani', 'POS', 'approve', '21/10/2019', '20/12/2019', '01/04/2013', '14/06/2015', '25/10/2021', 'annual', '', 'C', 'Ajger Ali Mohammed', '2650', '300', '51', '', '', '2019-10-21T11:40:06+03:00'),
(62, '1252', 'Mohammed Ibrahim Khalil', 'POS', 'approve', '25/11/2019', '23/01/2020', '07/11/2008', '31/07/2017', '25/11/2022', 'annual', '', 'C', 'Abul kasim Ismail', '2696', '300', '45', '', '', '2019-11-06T12:28:29+03:00'),
(63, '1116', 'Shabbir Ahmed Mannan', 'POS', 'not_approve', '08/12/2019', '05/02/2020', '', '', '', 'annual', '', 'C', 'Nur ul Huda Zain al Abidin', '', '', '5', 'Rejected from HR', '', '2019-11-06T13:28:31+03:00'),
(72, '1405', 'Abdullah Fazlur Rahim', 'Transportation', 'approve', '30/12/2019', '26/02/2020', '21/07/2013', '30/09/2017', '30/12/2022', 'annual', '', 'C', 'Azhar Ali Jamali', '2000', '300', '57', '', '', '2019-11-24T15:49:52+03:00'),
(65, '1411', 'Ishak Abdulmatloob', 'Warehouse', 'approve', '30/11/2019', '29/01/2020', '05/01/2014', '01/09/2017', '30/11/2022', 'Encashed', '', 'C', '', '', '', '60', 'vacation encashment ', '', '2019-11-20T09:53:38+03:00'),
(73, '4620', 'Sultan Yassin Ahmed Al Delame', 'HRD and Housing', 'approve', '15/12/2019', '11/01/2020', '20/08/2015', '02/12/2018', '02/12/2020', 'annual', '', 'C', 'Abeer Hassan Al Gamdi', '', '', '93', '', '', '2019-11-27T12:13:15+03:00'),
(76, '147', 'Abdul Razak Al Fadl', 'Public Relation', 'approve', '04/12/2019', '', '01/09/2016', '04/12/2018', '04/12/2020', 'Encashed', '', 'C', '', '', '', '95', '', '', '2019-12-05T11:52:07+03:00'),
(84, '1020', 'Subair Kappen', 'Warehouse', 'approve', '02/01/2020', '01/02/2020', '30/04/2002', '16/01/2019', '02/01/2021', 'annual', '', 'A', 'Shafar Ajim Azeemullah', '2371', '200', '7', '', '', '2019-12-15T16:33:28+03:00'),
(81, '148', 'Mohammed Al Khayyat', 'Purchase', 'approve', '17/12/2019', '17/12/2019', '05/02/2017', '03/02/2019', '02/02/2020', 'Encashed', '', 'C', 'Abdul Malik Shahul', '', '', '96', 'encashment 8 days only', '', '2019-12-15T14:52:54+03:00'),
(82, '120', 'Hatim Shafi Felemban', 'Sales Department', 'approve', '15/12/2019', '', '15/02/2015', '25/06/2018', '25/06/2020', 'Encashed', '', 'C', '', '', '', '92', '', '', '2019-12-15T15:08:29+03:00'),
(83, '1402', 'Nurul Islam Abdul Hakim', 'Transportation', 'approve', '20/12/2019', '', '24/06/2013', '01/07/2017', '01/07/2021', 'Encashed', '', 'C', '', '', '', '55', '', '', '2019-12-15T15:33:46+03:00'),
(85, '1412', 'Abdul HaiAbdul Matloob', 'Warehouse', 'approve', '20/12/2019', '', '05/01/2014', '06/05/2017', '20/12/2021', 'Encashed', '', 'C', '', '', '', '61', '', '', '2019-12-19T10:35:48+03:00'),
(86, '1024', 'Yousuf Abul Fayaz', 'Maintenance', 'approve', '25/12/2019', '', '07/06/2002', '18/07/2018', '25/12/2020', 'Encashed', '', 'C', '', '', '', '11', '', '', '2019-12-25T14:16:58+03:00'),
(87, '1011', 'Deni Rohyaman', 'POS', 'approve', '25/12/2019', '', '01/02/2001', '10/09/2017', '25/12/2021', 'Encashed', '', 'A', '', '', '', '6', '', '', '2019-12-25T14:18:20+03:00'),
(88, '1070', 'Saif ur Rahman M. Asar', 'POS', 'approve', '25/12/2019', '', '24/10/2002', '15/05/2015', '25/12/2021', 'Encashed', '', 'A', '', '', '', '19', '', '', '2019-12-25T14:19:28+03:00'),
(89, '4523', 'Abdulhameed Essam Dali', 'POS', 'approve', '25/12/2019', '', '22/10/2018', '22/10/2018', '25/12/2020', 'Encashed', '', 'C', '', '', '', '66', '', '', '2019-12-25T14:20:26+03:00'),
(90, '1098', 'Mohammed Jamaluddin', 'POS', 'not_approve', '23/01/2020', '23/03/2020', '', '', '23/01/2022', 'annual', '', 'C', 'Saepollah Sudin Yani', '', '', '24', 'Rejected from HR', '', '2019-12-29T15:13:21+03:00'),
(91, '42', 'Sajjad Hussain ', 'POS', 'approve', '19/01/2020', '17/02/2020', '03/12/2007', '01/01/2019', '19/01/2021', 'annual', '', 'A', 'Basheer Vellaran', '2362', '200', '86', '', '', '2020-01-05T10:51:44+03:00'),
(93, '1248', 'Mohammed Ikhlaq A. Rouf', 'Production ', 'approve', '02/02/2020', '01/04/2020', '05/11/2008', '01/02/2018', '02/02/2022', 'annual', '', 'A', 'Yousuf Yakub Ali', '2476', '300', '43', '', '', '2020-01-08T09:51:15+03:00'),
(94, '1146', 'Anwar Hussain', 'POS', 'approve', '20/02/2020', '20/04/2020', '08/08/2004', '16/05/2018', '20/02/2022', 'annual', '', 'A', 'Nur ul Huda Zain al Abidin', '2378', '300', '33', '', '', '2020-02-04T10:44:32+03:00'),
(95, '1114', 'Mukhtar Hussain', 'Warehouse', 'approve', '01/03/2020', '20/04/2020', '26/07/2003', '01/07/2018', '01/03/2022', 'annual', 'fly', 'C', 'Mohammed Khalid', '2249', '300', '28', '', '', '2020-02-04T12:57:57+03:00'),
(96, '1178', 'Salem Noor Hussain', 'Maintenance', 'approve', '10/02/2020', '31/03/2020', '25/11/2007', '03/02/2018', '10/02/2022', '', '', 'C', 'Abdul Rahman Hassan Matloob', '', '', '37', '', '', '2020-02-06T11:05:20+03:00'),
(97, '1098', 'Mohammed Jamaluddin', 'POS', 'approve', '20/02/2020', '20/04/2020', '17/03/2003', '24/07/2017', '20/02/2022', 'annual', '', 'C', 'Abul Kashem Amin', '2300', '300', '24', '', '', '2020-02-06T11:10:23+03:00'),
(111, '1026', 'Saleem Irshadullah', 'Production ', 'approve', '01/09/2020', '20/10/2020', '12/06/2002', '01/04/2018', '01/04/2022', 'Local Vacation', '', 'C', 'Mohammed Ikhlaq A. Rouf', '', '', '12', '', '', '2020-08-19T09:56:35+03:00'),
(99, '1026', 'Saleem Irshadullah', 'Production ', 'not_approve', '01/04/2020', '20/05/2020', '12/06/2002', '01/04/2018', '01/04/2022', '', '', 'C', 'Mohammed Ikhlaq A. Rouf', '', '', '12', '', 'corona issue', '2020-03-22T11:06:22+03:00'),
(101, '4525', 'Azam Esam Mohammed Daly', 'POS', 'not_approve', '01/07/2020', '31/07/2020', '', '', '01/07/2021', '', '', 'C', 'Zulfu Miah Siraj Miah', '', '', '221', 'Rejected from HR', '', '2020-07-05T10:53:12+03:00'),
(102, '4403', 'Hatoon Ehab Yousuf Jan', 'POS', 'not_approve', '01/07/2020', '31/07/2020', '', '', '01/07/2021', '', '', 'C', 'Rosaline Aguilar Cabayao', '', '', '67', 'Rejected from HR', '', '2020-07-05T10:54:34+03:00'),
(103, '4435', 'Nahlah Ali Attiyah Al Harbi', 'POS', 'not_approve', '01/07/2020', '31/07/2020', '', '', '01/07/2021', '', '', 'C', 'Fatimah Kamiran Disumimba', '', '', '125', 'Rejected from HR', '', '2020-07-05T11:01:54+03:00'),
(104, '4523', 'Abdulhameed Essam Dali', 'POS', 'not_approve', '01/07/2020', '31/07/2020', '', '', '01/07/2021', '', '', 'C', 'Rezaul Karim', '', '', '66', 'Rejected from HR', '', '2020-07-05T11:18:38+03:00'),
(105, '4517', 'Othman Ahmed Bashanum', 'POS', 'not_approve', '01/07/2020', '31/07/2020', '', '', '01/07/2021', '', '', 'C', 'Faruque Ahmed Md. Giashuddin', '', '', '63', 'Rejected from HR', '', '2020-07-05T11:31:19+03:00'),
(106, '82', 'Yasser Halaby', 'Sales Department', 'approve', '01/07/2020', '', '30/04/2012', '01/06/2018', '01/07/2022', 'Encashed', '', 'C', '', '', '', '89', 'incash ', '', '2020-07-06T10:55:00+03:00'),
(107, '0157', 'Abeer Hassan Al Gamdi', 'HRD and Housing', 'approve', '18/03/2020', '26/03/2020', '08/09/2019', '', '18/03/2021', 'Local Vacation', '', 'C', 'Abeer Hassan Al Gamdi', '', '', '225', '', '', '2020-07-12T13:33:18+03:00'),
(108, '148', 'Mohammed Al Khayyat', 'Purchase', 'approve', '01/02/2020', '01/03/2020', '05/02/2017', '17/12/2019', '01/02/2021', 'Local Vacation', '', 'C', 'Mohammed Khairuddin', '', '', '96', '', '', '2020-07-12T13:45:24+03:00'),
(109, '147', 'Abdul Razak Al Fadl', 'Public Relation', 'approve', '01/09/2020', '', '01/09/2016', '04/12/2019', '01/09/2021', 'Encashed', '', 'C', '', '', '', '95', '', '', '2020-08-18T11:25:49+03:00'),
(110, '0156', 'Daniah Mohammed Sadeeq Abushoshah', 'HRD and Housing', 'approve', '01/09/2020', '', '01/09/2019', '01/09/2019', '01/09/2021', 'Encashed', '', 'C', '', '', '', '224', '', '', '2020-08-18T12:26:08+03:00'),
(112, '152', 'Anees Afzal', 'IT', 'approve', '30/09/2020', '', '30/09/2018', '25/12/2019', '30/09/2021', 'Encashed', '', 'C', '', '', '', '98', '', '', '2020-08-19T10:26:42+03:00'),
(114, '4435', 'Nahlah Ali Attiyah Al Harbi', 'POS', 'approve', '01/10/2020', '31/10/2020', '18/03/2015', '06/05/2019', '01/10/2021', 'Local Vacation', '', 'C', 'Hatoon Ehab Yousuf Jan', '', '', '125', 'start vacation 04/10/2020', '', '2020-09-22T11:19:34+03:00'),
(115, '4517', 'Othman Ahmed Bashanum', 'POS', 'not_approve', '01/10/2020', '31/10/2020', '', '', '01/10/2021', 'Local Vacation', '', 'C', 'Azam Esam Mohammed Daly', '', '', '63', 'Rejected from HR', '', '2020-09-22T11:21:44+03:00'),
(116, '0157', 'Abeer Hassan Al Gamdi', 'HRD and Housing', 'approve', '01/10/2020', '30/10/2020', '08/09/2019', '08/09/2019', '08/09/2021', 'Local Vacation', '', 'C', 'Daniah Mohammed Sadeeq Abushoshah', '', '', '225', '', '', '2020-10-13T15:10:24+03:00'),
(119, '1021', 'Basheer Vellaran', 'POS', 'apply', '01/12/2020', '31/12/2020', '', '', '01/12/2021', 'Fly', 'annual', 'A', 'Sajjad Hussain ', '', '', '8', '', '', '2020-11-09T11:32:27+03:00'),
(118, '4525', 'Azam Esam Mohammed Daly', 'POS', 'approve', '01/11/2020', '30/11/2020', '24/07/2019', '24/07/2019', '01/07/2021', 'Local Vacation', '', 'A', 'Othman Ahmed Bashanum', '', '', '221', 'annual vacation year 2020', '', '2020-11-02T11:25:41+03:00'),
(120, '4523', 'Abdulhameed Essam Dali', 'POS', 'apply', '15/11/2020', '15/12/2020', '', '', '15/11/2021', 'Local Vacation', '', 'A', 'Anwar Hussain', '', '', '66', '', '', '2020-11-09T11:35:53+03:00');

-- --------------------------------------------------------

--
-- Table structure for table `bank_list`
--

CREATE TABLE IF NOT EXISTS `bank_list` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `date_reg` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=13 ;

--
-- Dumping data for table `bank_list`
--

INSERT INTO `bank_list` (`id`, `name`, `date_reg`) VALUES
(1, 'The National Commercial Bank', ''),
(2, 'The Saudi British Bank', ''),
(3, 'Saudi Investment Bank', ''),
(4, 'Alinma Bank', ''),
(5, 'Banque Saudi Fransi', ''),
(6, 'Riyad Bank', ''),
(7, 'Samba Financial Group', ''),
(8, 'Saudi Hollandi Bank', ''),
(9, 'Al Rajhi Bank', ''),
(10, 'Arab National Bank', ''),
(11, 'Bank Al-Bilad', ''),
(12, 'Bank AlJazira', '');

-- --------------------------------------------------------

--
-- Table structure for table `brand_name`
--

CREATE TABLE IF NOT EXISTS `brand_name` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `descr` varchar(255) NOT NULL,
  `date_reg` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=37 ;

--
-- Dumping data for table `brand_name`
--

INSERT INTO `brand_name` (`id`, `name`, `descr`, `date_reg`) VALUES
(1, 'Topper', '', ''),
(2, 'Diament', '', ''),
(3, 'New Diamond', '', ''),
(4, 'Wag', '', ''),
(5, 'Ditting', '', ''),
(6, 'San Tung', '', ''),
(7, 'Tupack', '', ''),
(8, 'SMI Pack', '', ''),
(9, 'Italdibipack', '', ''),
(10, 'Chung Shan', '', ''),
(11, 'Hitachi', '', ''),
(12, 'Bazzera', '', ''),
(13, 'Beko', '', ''),
(14, 'Gree', '', ''),
(15, 'Hisense', '', ''),
(16, 'Unknown', '', ''),
(23, 'Ugolini', '', ''),
(24, 'Cimbali', '', ''),
(26, 'CITROCASA', '', '2020-10-12T12:07:41+03:00'),
(27, 'MIGEL', '', '2020-10-12T12:28:45+03:00'),
(28, 'FLOJET', '', '2020-10-12T12:29:37+03:00'),
(29, 'GENERAL SUPER', '', '2020-10-12T12:30:41+03:00'),
(30, 'TEFAL', '', '2020-10-12T12:32:49+03:00'),
(31, 'ZKTECO', '', '2020-10-12T12:33:38+03:00'),
(32, 'YORK', '', '2020-10-12T12:34:02+03:00'),
(33, 'SAMNAN', '', '2020-10-12T12:34:30+03:00'),
(34, 'SANYO', '', '2020-10-12T13:55:21+03:00'),
(35, 'ICCUME', '', '2020-10-12T13:56:17+03:00'),
(36, 'GEEPAS', '', '2020-10-12T14:06:33+03:00');

-- --------------------------------------------------------

--
-- Table structure for table `cars`
--

CREATE TABLE IF NOT EXISTS `cars` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `maker_name` varchar(255) NOT NULL,
  `model` varchar(255) NOT NULL,
  `made_year` varchar(255) NOT NULL,
  `plate_no` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `status` varchar(10) NOT NULL,
  `remarks` varchar(255) NOT NULL,
  `date_reg` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=13 ;

--
-- Dumping data for table `cars`
--

INSERT INTO `cars` (`id`, `maker_name`, `model`, `made_year`, `plate_no`, `type`, `status`, `remarks`, `date_reg`) VALUES
(1, 'Toyota', 'Hiace', '2014', '1660-AUJ', 'Bus', 'active', '', '2019-01-17T17:56:17+03:00'),
(2, 'Toyota', 'Hiace', '2011', '1696-AKT', 'Bus', 'active', 'damage', '2019-01-17T17:57:18+03:00'),
(3, 'Hyundai', 'Truck', '2011', '1799-AEK', 'Dyna', 'active', '', '2019-01-17T17:59:34+03:00'),
(4, 'Toyota', 'Yaris', '2011', '1948-BGN', 'Car', 'active', 'damage', '2019-01-17T18:01:45+03:00'),
(5, 'Nissan', 'Rich', '2015', '2319-BAl', 'Pick Up', 'active', '', '2019-01-17T18:29:39+03:00'),
(6, 'Toyota', 'Hiace', '2011', '2690-AGL', 'Bus', 'active', '', '2019-01-17T18:31:22+03:00'),
(7, 'Honda', 'Odyssey', '2018', '3409-DST', 'Jeep', 'active', '', '2019-01-17T18:48:27+03:00'),
(8, 'Toyota', 'SINGLE CAB', '2010', '5270-ATY', 'Pick Up', 'active', '', '2019-01-17T18:55:24+03:00'),
(9, 'Chevrolet', 'Impala', '2018', '5955-DJG', 'Car', 'active', '', '2019-01-17T18:57:00+03:00'),
(10, 'Toyota', 'Hiace', '2012', '6038-ALT', 'Bus', 'active', '', '2019-01-17T19:00:45+03:00'),
(11, 'Isuzu', 'D-Max', '2013', '6065-ANH', 'Dyna', 'active', '', '2019-01-17T19:02:51+03:00'),
(12, 'FIAT', 'FoodTruck', '2013', '7892-AUS', 'Van', 'active', 'damage', '2019-01-17T19:05:21+03:00');

-- --------------------------------------------------------

--
-- Table structure for table `cars_docu`
--

CREATE TABLE IF NOT EXISTS `cars_docu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `car_id` int(100) NOT NULL,
  `doc_type` varchar(100) NOT NULL,
  `issue_date` varchar(100) NOT NULL,
  `exp_date` varchar(100) NOT NULL,
  `date_reg` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `cars_docu`
--


-- --------------------------------------------------------

--
-- Table structure for table `cars_drv`
--

CREATE TABLE IF NOT EXISTS `cars_drv` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `car_id` int(100) NOT NULL,
  `drv_name` varchar(255) NOT NULL,
  `rcv_date` varchar(255) NOT NULL,
  `rtn_date` varchar(255) NOT NULL,
  `status` varchar(10) NOT NULL,
  `date_reg` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=5 ;

--
-- Dumping data for table `cars_drv`
--

INSERT INTO `cars_drv` (`id`, `car_id`, `drv_name`, `rcv_date`, `rtn_date`, `status`, `date_reg`) VALUES
(4, 7, 'Azhar Ali Jamali', '01/09/2018', '', 'yes', '2019-09-12T14:30:03+03:00'),
(3, 1, 'Abdullah Fazlur Rahim', '1/8/2019', '', 'yes', '2019-09-12T14:05:12+03:00');

-- --------------------------------------------------------

--
-- Table structure for table `cars_maint`
--

CREATE TABLE IF NOT EXISTS `cars_maint` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `car_id` int(100) NOT NULL,
  `meter` varchar(100) NOT NULL,
  `car_user` varchar(100) NOT NULL,
  `remarks` varchar(255) NOT NULL,
  `date_reg` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `cars_maint`
--


-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE IF NOT EXISTS `comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `photo_id` varchar(11) NOT NULL,
  `author` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `reg_date` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `photo_id` (`photo_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=17 ;

--
-- Triggers `comments`
--
DROP TRIGGER IF EXISTS `hr_db`.`a_i_comments`;
DELIMITER //
CREATE TRIGGER `hr_db`.`a_i_comments` AFTER INSERT ON `hr_db`.`comments`
 FOR EACH ROW BEGIN 						SET @time_mark = DATE_ADD(NOW(), INTERVAL -3576 SECOND); 						SET @tbl_name = 'cvb'; 						SET @tbl_name = 'comments'; 						SET @pk_d = CONCAT('<id>',NEW.`id`,'</id>'); 						SET @rec_state = 1;						UPDATE `history_store` SET `pk_date_dest` = `pk_date_src` WHERE `table_name` = @tbl_name AND `pk_date_dest` = @pk_d AND (`record_state` = 2 OR `record_state` = 1); 						DELETE FROM `history_store` WHERE `table_name` = @tbl_name AND `pk_date_dest` = @pk_d; 						INSERT INTO `history_store`( `timemark`, `table_name`, `pk_date_src`,`pk_date_dest`,`record_state` ) 						VALUES (@time_mark, @tbl_name, @pk_d, @pk_d, @rec_state); 						END
//
DELIMITER ;
DROP TRIGGER IF EXISTS `hr_db`.`a_u_comments`;
DELIMITER //
CREATE TRIGGER `hr_db`.`a_u_comments` AFTER UPDATE ON `hr_db`.`comments`
 FOR EACH ROW BEGIN						SET @time_mark = DATE_ADD(NOW(), INTERVAL -3576 SECOND); 						SET @tbl_name = 'comments';						SET @pk_d_old = CONCAT('<id>',OLD.`id`,'</id>');						SET @pk_d = CONCAT('<id>',NEW.`id`,'</id>');						SET @rec_state = 2;						SET @rs = 0;						SELECT `record_state` INTO @rs FROM `history_store` WHERE `table_name` = @tbl_name AND `pk_date_src` = @pk_d_old;						IF @rs = 0 THEN 						INSERT INTO `history_store`( `timemark`, `table_name`, `pk_date_src`,`pk_date_dest`, `record_state` ) VALUES (@time_mark, @tbl_name, @pk_d,@pk_d_old, @rec_state );						ELSE 						UPDATE `history_store` SET `timemark` = @time_mark, `pk_date_src` = @pk_d WHERE `table_name` = @tbl_name AND `pk_date_src` = @pk_d_old;						END IF; END
//
DELIMITER ;
DROP TRIGGER IF EXISTS `hr_db`.`a_d_comments`;
DELIMITER //
CREATE TRIGGER `hr_db`.`a_d_comments` AFTER DELETE ON `hr_db`.`comments`
 FOR EACH ROW BEGIN						SET @time_mark = DATE_ADD(NOW(), INTERVAL -3576 SECOND); 						SET @tbl_name = 'comments';						SET @pk_d = CONCAT('<id>',OLD.`id`,'</id>');						SET @rec_state = 3;						SET @rs = 0;						SELECT `record_state` INTO @rs FROM `history_store` WHERE  `table_name` = @tbl_name AND `pk_date_src` = @pk_d;						IF @rs = 1 THEN 						DELETE FROM `history_store` WHERE `table_name` = @tbl_name AND `pk_date_src` = @pk_d; 						END IF; 						IF @rs > 1 THEN 						UPDATE `history_store` SET `timemark` = @time_mark, `record_state` = 3, `pk_date_src` = `pk_date_dest` WHERE `table_name` = @tbl_name AND `pk_date_src` = @pk_d; 						END IF; 						IF @rs = 0 THEN 						INSERT INTO `history_store`( `timemark`, `table_name`, `pk_date_src`,`pk_date_dest`, `record_state` ) VALUES (@time_mark, @tbl_name, @pk_d,@pk_d, @rec_state ); 						END IF; END
//
DELIMITER ;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `photo_id`, `author`, `body`, `reg_date`) VALUES
(5, '8', 'Anees', 'Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.', '2020-09-15T11:22:49+04:00'),
(2, '8', 'Waqar', 'it''s too Good', '2020-09-15T10:57:10+04:00'),
(3, '8', 'Muhammad', 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod\r\ntempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam,\r\nquis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo\r\nconsequat. Duis aute irure dolor in reprehenderit in voluptate velit esse\r\ncillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non\r\nproident, sunt in culpa qui officia deserunt mollit anim id est laborum.', '2020-09-15T11:13:08+04:00'),
(16, '9', 'Waqar', 'oooooook', '2020-09-20T13:46:03+04:00'),
(15, '7', 'Anees', 'It''s too good', '2020-09-15T11:59:26+04:00');

-- --------------------------------------------------------

--
-- Table structure for table `contract_period`
--

CREATE TABLE IF NOT EXISTS `contract_period` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `period` varchar(100) NOT NULL,
  `vac_period` varchar(100) NOT NULL,
  `reg_date` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

--
-- Dumping data for table `contract_period`
--

INSERT INTO `contract_period` (`id`, `period`, `vac_period`, `reg_date`) VALUES
(1, '1 Year', '30', ''),
(2, '2 Years', '60', '');

-- --------------------------------------------------------

--
-- Table structure for table `country`
--

CREATE TABLE IF NOT EXISTS `country` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(3) NOT NULL,
  `name` varchar(150) NOT NULL,
  `dial_code` int(11) NOT NULL,
  `currency_name` varchar(20) NOT NULL,
  `currency_symbol` varchar(20) NOT NULL,
  `currency_code` varchar(20) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=248 ;

--
-- Dumping data for table `country`
--

INSERT INTO `country` (`ID`, `code`, `name`, `dial_code`, `currency_name`, `currency_symbol`, `currency_code`) VALUES
(1, 'AF', 'Afghanistan', 93, 'Afghan afghani', '؋', 'AFN'),
(2, 'AL', 'Albania', 355, 'Albanian lek', 'L', 'ALL'),
(3, 'DZ', 'Algeria', 213, 'Algerian dinar', 'د.ج', 'DZD'),
(4, 'AS', 'American Samoa', 1684, '', '', ''),
(5, 'AD', 'Andorra', 376, 'Euro', '€', 'EUR'),
(6, 'AO', 'Angola', 244, 'Angolan kwanza', 'Kz', 'AOA'),
(7, 'AI', 'Anguilla', 1264, 'East Caribbean dolla', '$', 'XCD'),
(8, 'AQ', 'Antarctica', 0, '', '', ''),
(9, 'AG', 'Antigua And Barbuda', 1268, 'East Caribbean dolla', '$', 'XCD'),
(10, 'AR', 'Argentina', 54, 'Argentine peso', '$', 'ARS'),
(11, 'AM', 'Armenia', 374, 'Armenian dram', '', 'AMD'),
(12, 'AW', 'Aruba', 297, 'Aruban florin', 'ƒ', 'AWG'),
(13, 'AU', 'Australia', 61, 'Australian dollar', '$', 'AUD'),
(14, 'AT', 'Austria', 43, 'Euro', '€', 'EUR'),
(15, 'AZ', 'Azerbaijan', 994, 'Azerbaijani manat', '', 'AZN'),
(16, 'BS', 'Bahamas The', 1242, '', '', ''),
(17, 'BH', 'Bahrain', 973, 'Bahraini dinar', '.د.ب', 'BHD'),
(18, 'BD', 'Bangladesh', 880, 'Bangladeshi taka', '৳', 'BDT'),
(19, 'BB', 'Barbados', 1246, 'Barbadian dollar', '$', 'BBD'),
(20, 'BY', 'Belarus', 375, 'Belarusian ruble', 'Br', 'BYR'),
(21, 'BE', 'Belgium', 32, 'Euro', '€', 'EUR'),
(22, 'BZ', 'Belize', 501, 'Belize dollar', '$', 'BZD'),
(23, 'BJ', 'Benin', 229, 'West African CFA fra', 'Fr', 'XOF'),
(24, 'BM', 'Bermuda', 1441, 'Bermudian dollar', '$', 'BMD'),
(25, 'BT', 'Bhutan', 975, 'Bhutanese ngultrum', 'Nu.', 'BTN'),
(26, 'BO', 'Bolivia', 591, 'Bolivian boliviano', 'Bs.', 'BOB'),
(27, 'BA', 'Bosnia and Herzegovina', 387, 'Bosnia and Herzegovi', 'KM or КМ', 'BAM'),
(28, 'BW', 'Botswana', 267, 'Botswana pula', 'P', 'BWP'),
(29, 'BV', 'Bouvet Island', 0, '', '', ''),
(30, 'BR', 'Brazil', 55, 'Brazilian real', 'R$', 'BRL'),
(31, 'IO', 'British Indian Ocean Territory', 246, 'United States dollar', '$', 'USD'),
(32, 'BN', 'Brunei', 673, 'Brunei dollar', '$', 'BND'),
(33, 'BG', 'Bulgaria', 359, 'Bulgarian lev', 'лв', 'BGN'),
(34, 'BF', 'Burkina Faso', 226, 'West African CFA fra', 'Fr', 'XOF'),
(35, 'BI', 'Burundi', 257, 'Burundian franc', 'Fr', 'BIF'),
(36, 'KH', 'Cambodia', 855, 'Cambodian riel', '៛', 'KHR'),
(37, 'CM', 'Cameroon', 237, 'Central African CFA ', 'Fr', 'XAF'),
(38, 'CA', 'Canada', 1, 'Canadian dollar', '$', 'CAD'),
(39, 'CV', 'Cape Verde', 238, 'Cape Verdean escudo', 'Esc or $', 'CVE'),
(40, 'KY', 'Cayman Islands', 1345, 'Cayman Islands dolla', '$', 'KYD'),
(41, 'CF', 'Central African Republic', 236, 'Central African CFA ', 'Fr', 'XAF'),
(42, 'TD', 'Chad', 235, 'Central African CFA ', 'Fr', 'XAF'),
(43, 'CL', 'Chile', 56, 'Chilean peso', '$', 'CLP'),
(44, 'CN', 'China', 86, 'Chinese yuan', '¥ or 元', 'CNY'),
(45, 'CX', 'Christmas Island', 61, '', '', ''),
(46, 'CC', 'Cocos (Keeling) Islands', 672, 'Australian dollar', '$', 'AUD'),
(47, 'CO', 'Colombia', 57, 'Colombian peso', '$', 'COP'),
(48, 'KM', 'Comoros', 269, 'Comorian franc', 'Fr', 'KMF'),
(49, 'CG', 'Congo', 242, '', '', ''),
(50, 'CD', 'Congo The Democratic Republic Of The', 242, '', '', ''),
(51, 'CK', 'Cook Islands', 682, 'New Zealand dollar', '$', 'NZD'),
(52, 'CR', 'Costa Rica', 506, 'Costa Rican colón', '₡', 'CRC'),
(53, 'CI', 'Cote D''Ivoire (Ivory Coast)', 225, '', '', ''),
(54, 'HR', 'Croatia (Hrvatska)', 385, '', '', ''),
(55, 'CU', 'Cuba', 53, 'Cuban convertible pe', '$', 'CUC'),
(56, 'CY', 'Cyprus', 357, 'Euro', '€', 'EUR'),
(57, 'CZ', 'Czech Republic', 420, 'Czech koruna', 'Kč', 'CZK'),
(58, 'DK', 'Denmark', 45, 'Danish krone', 'kr', 'DKK'),
(59, 'DJ', 'Djibouti', 253, 'Djiboutian franc', 'Fr', 'DJF'),
(60, 'DM', 'Dominica', 1767, 'East Caribbean dolla', '$', 'XCD'),
(61, 'DO', 'Dominican Republic', 1809, 'Dominican peso', '$', 'DOP'),
(62, 'TP', 'East Timor', 670, 'United States dollar', '$', 'USD'),
(63, 'EC', 'Ecuador', 593, 'United States dollar', '$', 'USD'),
(64, 'EG', 'Egypt', 20, 'Egyptian pound', '£ or ج.م', 'EGP'),
(65, 'SV', 'El Salvador', 503, 'United States dollar', '$', 'USD'),
(66, 'GQ', 'Equatorial Guinea', 240, 'Central African CFA ', 'Fr', 'XAF'),
(67, 'ER', 'Eritrea', 291, 'Eritrean nakfa', 'Nfk', 'ERN'),
(68, 'EE', 'Estonia', 372, 'Euro', '€', 'EUR'),
(69, 'ET', 'Ethiopia', 251, 'Ethiopian birr', 'Br', 'ETB'),
(70, 'XA', 'External Territories of Australia', 61, '', '', ''),
(71, 'FK', 'Falkland Islands', 500, 'Falkland Islands pou', '£', 'FKP'),
(72, 'FO', 'Faroe Islands', 298, 'Danish krone', 'kr', 'DKK'),
(73, 'FJ', 'Fiji Islands', 679, '', '', ''),
(74, 'FI', 'Finland', 358, 'Euro', '€', 'EUR'),
(75, 'FR', 'France', 33, 'Euro', '€', 'EUR'),
(76, 'GF', 'French Guiana', 594, '', '', ''),
(77, 'PF', 'French Polynesia', 689, 'CFP franc', 'Fr', 'XPF'),
(78, 'TF', 'French Southern Territories', 0, '', '', ''),
(79, 'GA', 'Gabon', 241, 'Central African CFA ', 'Fr', 'XAF'),
(80, 'GM', 'Gambia The', 220, '', '', ''),
(81, 'GE', 'Georgia', 995, 'Georgian lari', 'ლ', 'GEL'),
(82, 'DE', 'Germany', 49, 'Euro', '€', 'EUR'),
(83, 'GH', 'Ghana', 233, 'Ghana cedi', '₵', 'GHS'),
(84, 'GI', 'Gibraltar', 350, 'Gibraltar pound', '£', 'GIP'),
(85, 'GR', 'Greece', 30, 'Euro', '€', 'EUR'),
(86, 'GL', 'Greenland', 299, '', '', ''),
(87, 'GD', 'Grenada', 1473, 'East Caribbean dolla', '$', 'XCD'),
(88, 'GP', 'Guadeloupe', 590, '', '', ''),
(89, 'GU', 'Guam', 1671, '', '', ''),
(90, 'GT', 'Guatemala', 502, 'Guatemalan quetzal', 'Q', 'GTQ'),
(91, 'XU', 'Guernsey and Alderney', 44, '', '', ''),
(92, 'GN', 'Guinea', 224, 'Guinean franc', 'Fr', 'GNF'),
(93, 'GW', 'Guinea-Bissau', 245, 'West African CFA fra', 'Fr', 'XOF'),
(94, 'GY', 'Guyana', 592, 'Guyanese dollar', '$', 'GYD'),
(95, 'HT', 'Haiti', 509, 'Haitian gourde', 'G', 'HTG'),
(96, 'HM', 'Heard and McDonald Islands', 0, '', '', ''),
(97, 'HN', 'Honduras', 504, 'Honduran lempira', 'L', 'HNL'),
(98, 'HK', 'Hong Kong S.A.R.', 852, '', '', ''),
(99, 'HU', 'Hungary', 36, 'Hungarian forint', 'Ft', 'HUF'),
(100, 'IS', 'Iceland', 354, 'Icelandic króna', 'kr', 'ISK'),
(101, 'IN', 'India', 91, 'Indian rupee', '₹', 'INR'),
(102, 'ID', 'Indonesia', 62, 'Indonesian rupiah', 'Rp', 'IDR'),
(103, 'IR', 'Iran', 98, 'Iranian rial', '﷼', 'IRR'),
(104, 'IQ', 'Iraq', 964, 'Iraqi dinar', 'ع.د', 'IQD'),
(105, 'IE', 'Ireland', 353, 'Euro', '€', 'EUR'),
(106, 'IL', 'Israel', 972, 'Israeli new shekel', '₪', 'ILS'),
(107, 'IT', 'Italy', 39, 'Euro', '€', 'EUR'),
(108, 'JM', 'Jamaica', 1876, 'Jamaican dollar', '$', 'JMD'),
(109, 'JP', 'Japan', 81, 'Japanese yen', '¥', 'JPY'),
(110, 'XJ', 'Jersey', 44, 'British pound', '£', 'GBP'),
(111, 'JO', 'Jordan', 962, 'Jordanian dinar', 'د.ا', 'JOD'),
(112, 'KZ', 'Kazakhstan', 7, 'Kazakhstani tenge', '', 'KZT'),
(113, 'KE', 'Kenya', 254, 'Kenyan shilling', 'Sh', 'KES'),
(114, 'KI', 'Kiribati', 686, 'Australian dollar', '$', 'AUD'),
(115, 'KP', 'Korea North', 850, '', '', ''),
(116, 'KR', 'Korea South', 82, '', '', ''),
(117, 'KW', 'Kuwait', 965, 'Kuwaiti dinar', 'د.ك', 'KWD'),
(118, 'KG', 'Kyrgyzstan', 996, 'Kyrgyzstani som', 'лв', 'KGS'),
(119, 'LA', 'Laos', 856, 'Lao kip', '₭', 'LAK'),
(120, 'LV', 'Latvia', 371, 'Euro', '€', 'EUR'),
(121, 'LB', 'Lebanon', 961, 'Lebanese pound', 'ل.ل', 'LBP'),
(122, 'LS', 'Lesotho', 266, 'Lesotho loti', 'L', 'LSL'),
(123, 'LR', 'Liberia', 231, 'Liberian dollar', '$', 'LRD'),
(124, 'LY', 'Libya', 218, 'Libyan dinar', 'ل.د', 'LYD'),
(125, 'LI', 'Liechtenstein', 423, 'Swiss franc', 'Fr', 'CHF'),
(126, 'LT', 'Lithuania', 370, 'Euro', '€', 'EUR'),
(127, 'LU', 'Luxembourg', 352, 'Euro', '€', 'EUR'),
(128, 'MO', 'Macau S.A.R.', 853, '', '', ''),
(129, 'MK', 'Macedonia', 389, '', '', ''),
(130, 'MG', 'Madagascar', 261, 'Malagasy ariary', 'Ar', 'MGA'),
(131, 'MW', 'Malawi', 265, 'Malawian kwacha', 'MK', 'MWK'),
(132, 'MY', 'Malaysia', 60, 'Malaysian ringgit', 'RM', 'MYR'),
(133, 'MV', 'Maldives', 960, 'Maldivian rufiyaa', '.ރ', 'MVR'),
(134, 'ML', 'Mali', 223, 'West African CFA fra', 'Fr', 'XOF'),
(135, 'MT', 'Malta', 356, 'Euro', '€', 'EUR'),
(136, 'XM', 'Man (Isle of)', 44, '', '', ''),
(137, 'MH', 'Marshall Islands', 692, 'United States dollar', '$', 'USD'),
(138, 'MQ', 'Martinique', 596, '', '', ''),
(139, 'MR', 'Mauritania', 222, 'Mauritanian ouguiya', 'UM', 'MRO'),
(140, 'MU', 'Mauritius', 230, 'Mauritian rupee', '₨', 'MUR'),
(141, 'YT', 'Mayotte', 269, '', '', ''),
(142, 'MX', 'Mexico', 52, 'Mexican peso', '$', 'MXN'),
(143, 'FM', 'Micronesia', 691, 'Micronesian dollar', '$', ''),
(144, 'MD', 'Moldova', 373, 'Moldovan leu', 'L', 'MDL'),
(145, 'MC', 'Monaco', 377, 'Euro', '€', 'EUR'),
(146, 'MN', 'Mongolia', 976, 'Mongolian tögrög', '₮', 'MNT'),
(147, 'MS', 'Montserrat', 1664, 'East Caribbean dolla', '$', 'XCD'),
(148, 'MA', 'Morocco', 212, 'Moroccan dirham', 'د.م.', 'MAD'),
(149, 'MZ', 'Mozambique', 258, 'Mozambican metical', 'MT', 'MZN'),
(150, 'MM', 'Myanmar', 95, 'Burmese kyat', 'Ks', 'MMK'),
(151, 'NA', 'Namibia', 264, 'Namibian dollar', '$', 'NAD'),
(152, 'NR', 'Nauru', 674, 'Australian dollar', '$', 'AUD'),
(153, 'NP', 'Nepal', 977, 'Nepalese rupee', '₨', 'NPR'),
(154, 'AN', 'Netherlands Antilles', 599, '', '', ''),
(155, 'NL', 'Netherlands The', 31, '', '', ''),
(156, 'NC', 'New Caledonia', 687, 'CFP franc', 'Fr', 'XPF'),
(157, 'NZ', 'New Zealand', 64, 'New Zealand dollar', '$', 'NZD'),
(158, 'NI', 'Nicaragua', 505, 'Nicaraguan córdoba', 'C$', 'NIO'),
(159, 'NE', 'Niger', 227, 'West African CFA fra', 'Fr', 'XOF'),
(160, 'NG', 'Nigeria', 234, 'Nigerian naira', '₦', 'NGN'),
(161, 'NU', 'Niue', 683, 'New Zealand dollar', '$', 'NZD'),
(162, 'NF', 'Norfolk Island', 672, '', '', ''),
(163, 'MP', 'Northern Mariana Islands', 1670, '', '', ''),
(164, 'NO', 'Norway', 47, 'Norwegian krone', 'kr', 'NOK'),
(165, 'OM', 'Oman', 968, 'Omani rial', 'ر.ع.', 'OMR'),
(166, 'PK', 'Pakistan', 92, 'Pakistani rupee', '₨', 'PKR'),
(167, 'PW', 'Palau', 680, 'Palauan dollar', '$', ''),
(168, 'PS', 'Palestinian Territory Occupied', 970, '', '', ''),
(169, 'PA', 'Panama', 507, 'Panamanian balboa', 'B/.', 'PAB'),
(170, 'PG', 'Papua new Guinea', 675, 'Papua New Guinean ki', 'K', 'PGK'),
(171, 'PY', 'Paraguay', 595, 'Paraguayan guaraní', '₲', 'PYG'),
(172, 'PE', 'Peru', 51, 'Peruvian nuevo sol', 'S/.', 'PEN'),
(173, 'PH', 'Philippines', 63, 'Philippine peso', '₱', 'PHP'),
(174, 'PN', 'Pitcairn Island', 0, '', '', ''),
(175, 'PL', 'Poland', 48, 'Polish złoty', 'zł', 'PLN'),
(176, 'PT', 'Portugal', 351, 'Euro', '€', 'EUR'),
(177, 'PR', 'Puerto Rico', 1787, '', '', ''),
(178, 'QA', 'Qatar', 974, 'Qatari riyal', 'ر.ق', 'QAR'),
(179, 'RE', 'Reunion', 262, '', '', ''),
(180, 'RO', 'Romania', 40, 'Romanian leu', 'lei', 'RON'),
(181, 'RU', 'Russia', 70, 'Russian ruble', '', 'RUB'),
(182, 'RW', 'Rwanda', 250, 'Rwandan franc', 'Fr', 'RWF'),
(183, 'SH', 'Saint Helena', 290, 'Saint Helena pound', '£', 'SHP'),
(184, 'KN', 'Saint Kitts And Nevis', 1869, 'East Caribbean dolla', '$', 'XCD'),
(185, 'LC', 'Saint Lucia', 1758, 'East Caribbean dolla', '$', 'XCD'),
(186, 'PM', 'Saint Pierre and Miquelon', 508, '', '', ''),
(187, 'VC', 'Saint Vincent And The Grenadines', 1784, 'East Caribbean dolla', '$', 'XCD'),
(188, 'WS', 'Samoa', 684, 'Samoan tālā', 'T', 'WST'),
(189, 'SM', 'San Marino', 378, 'Euro', '€', 'EUR'),
(190, 'ST', 'Sao Tome and Principe', 239, 'São Tomé and Príncip', 'Db', 'STD'),
(191, 'SA', 'Saudi Arabia', 966, 'Saudi riyal', 'ر.س', 'SAR'),
(192, 'SN', 'Senegal', 221, 'West African CFA fra', 'Fr', 'XOF'),
(193, 'RS', 'Serbia', 381, 'Serbian dinar', 'дин. or din.', 'RSD'),
(194, 'SC', 'Seychelles', 248, 'Seychellois rupee', '₨', 'SCR'),
(195, 'SL', 'Sierra Leone', 232, 'Sierra Leonean leone', 'Le', 'SLL'),
(196, 'SG', 'Singapore', 65, 'Brunei dollar', '$', 'BND'),
(197, 'SK', 'Slovakia', 421, 'Euro', '€', 'EUR'),
(198, 'SI', 'Slovenia', 386, 'Euro', '€', 'EUR'),
(199, 'XG', 'Smaller Territories of the UK', 44, '', '', ''),
(200, 'SB', 'Solomon Islands', 677, 'Solomon Islands doll', '$', 'SBD'),
(201, 'SO', 'Somalia', 252, 'Somali shilling', 'Sh', 'SOS'),
(202, 'ZA', 'South Africa', 27, 'South African rand', 'R', 'ZAR'),
(203, 'GS', 'South Georgia', 0, '', '', ''),
(204, 'SS', 'South Sudan', 211, 'South Sudanese pound', '£', 'SSP'),
(205, 'ES', 'Spain', 34, 'Euro', '€', 'EUR'),
(206, 'LK', 'Sri Lanka', 94, 'Sri Lankan rupee', 'Rs or රු', 'LKR'),
(207, 'SD', 'Sudan', 249, 'Sudanese pound', 'ج.س.', 'SDG'),
(208, 'SR', 'Suriname', 597, 'Surinamese dollar', '$', 'SRD'),
(209, 'SJ', 'Svalbard And Jan Mayen Islands', 47, '', '', ''),
(210, 'SZ', 'Swaziland', 268, 'Swazi lilangeni', 'L', 'SZL'),
(211, 'SE', 'Sweden', 46, 'Swedish krona', 'kr', 'SEK'),
(212, 'CH', 'Switzerland', 41, 'Swiss franc', 'Fr', 'CHF'),
(213, 'SY', 'Syria', 963, 'Syrian pound', '£ or ل.س', 'SYP'),
(214, 'TW', 'Taiwan', 886, 'New Taiwan dollar', '$', 'TWD'),
(215, 'TJ', 'Tajikistan', 992, 'Tajikistani somoni', 'ЅМ', 'TJS'),
(216, 'TZ', 'Tanzania', 255, 'Tanzanian shilling', 'Sh', 'TZS'),
(217, 'TH', 'Thailand', 66, 'Thai baht', '฿', 'THB'),
(218, 'TG', 'Togo', 228, 'West African CFA fra', 'Fr', 'XOF'),
(219, 'TK', 'Tokelau', 690, '', '', ''),
(220, 'TO', 'Tonga', 676, 'Tongan paʻanga', 'T$', 'TOP'),
(221, 'TT', 'Trinidad And Tobago', 1868, 'Trinidad and Tobago ', '$', 'TTD'),
(222, 'TN', 'Tunisia', 216, 'Tunisian dinar', 'د.ت', 'TND'),
(223, 'TR', 'Turkey', 90, 'Turkish lira', '', 'TRY'),
(224, 'TM', 'Turkmenistan', 7370, 'Turkmenistan manat', 'm', 'TMT'),
(225, 'TC', 'Turks And Caicos Islands', 1649, 'United States dollar', '$', 'USD'),
(226, 'TV', 'Tuvalu', 688, 'Australian dollar', '$', 'AUD'),
(227, 'UG', 'Uganda', 256, 'Ugandan shilling', 'Sh', 'UGX'),
(228, 'UA', 'Ukraine', 380, 'Ukrainian hryvnia', '₴', 'UAH'),
(229, 'AE', 'United Arab Emirates', 971, 'United Arab Emirates', 'د.إ', 'AED'),
(230, 'GB', 'United Kingdom', 44, 'British pound', '£', 'GBP'),
(231, 'US', 'United States', 1, 'United States dollar', '$', 'USD'),
(232, 'UM', 'United States Minor Outlying Islands', 1, '', '', ''),
(233, 'UY', 'Uruguay', 598, 'Uruguayan peso', '$', 'UYU'),
(234, 'UZ', 'Uzbekistan', 998, 'Uzbekistani som', '', 'UZS'),
(235, 'VU', 'Vanuatu', 678, 'Vanuatu vatu', 'Vt', 'VUV'),
(236, 'VA', 'Vatican City State (Holy See)', 39, '', '', ''),
(237, 'VE', 'Venezuela', 58, 'Venezuelan bolívar', 'Bs F', 'VEF'),
(238, 'VN', 'Vietnam', 84, 'Vietnamese đồng', '₫', 'VND'),
(239, 'VG', 'Virgin Islands (British)', 1284, '', '', ''),
(240, 'VI', 'Virgin Islands (US)', 1340, '', '', ''),
(241, 'WF', 'Wallis And Futuna Islands', 681, '', '', ''),
(242, 'EH', 'Western Sahara', 212, '', '', ''),
(243, 'YE', 'Yemen', 967, 'Yemeni rial', '﷼', 'YER'),
(244, 'YU', 'Yugoslavia', 38, '', '', ''),
(245, 'ZM', 'Zambia', 260, 'Zambian kwacha', 'ZK', 'ZMW'),
(246, 'ZW', 'Zimbabwe', 263, 'Botswana pula', 'P', 'BWP'),
(247, '', 'Misplaced Tribes', 0, '', '', '');

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
(3, 'HR and Housing', '', '', '', '', ''),
(4, 'Public Relation', '', '', '', '', ''),
(5, 'Sales', '', '', '', '', ''),
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

-- --------------------------------------------------------

--
-- Table structure for table `dept_clr`
--

CREATE TABLE IF NOT EXISTS `dept_clr` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dept_name` varchar(100) NOT NULL,
  `color` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=15 ;

--
-- Dumping data for table `dept_clr`
--

INSERT INTO `dept_clr` (`id`, `dept_name`, `color`) VALUES
(1, 'Administration', 'custom'),
(2, 'Finance', 'purple'),
(3, 'HRD and Housing', 'primary'),
(4, 'Inspection', 'success'),
(5, 'IT', 'custom'),
(6, 'Maintenance', 'purple'),
(7, 'Management', 'primary'),
(8, 'POS', 'success'),
(9, 'Production', 'custom'),
(10, 'Public Relation', 'purple'),
(11, 'Purchase', 'primary'),
(12, 'Sales Department', 'success'),
(13, 'Transportation', 'custom'),
(14, 'Warehouse', 'purple');

-- --------------------------------------------------------

--
-- Table structure for table `docu_type`
--

CREATE TABLE IF NOT EXISTS `docu_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `duc_type` varchar(150) NOT NULL,
  `date_reg` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=9 ;

--
-- Dumping data for table `docu_type`
--

INSERT INTO `docu_type` (`id`, `duc_type`, `date_reg`) VALUES
(1, 'Iqama', ''),
(2, 'Passport-1', ''),
(3, 'Passport-2', ''),
(4, 'Passport-3', ''),
(5, 'CompanyContract', ''),
(7, 'BaldiaCard', ''),
(8, 'BaldiaCertificate ', '');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE IF NOT EXISTS `employees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `emp_id` varchar(255) NOT NULL,
  `iqama` varchar(255) NOT NULL,
  `iqama_exp` varchar(255) NOT NULL,
  `iqama_exp_g` varchar(100) NOT NULL,
  `mobile` varchar(255) NOT NULL,
  `passport_number` varchar(255) NOT NULL,
  `passport_exp` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `g_number` varchar(255) NOT NULL,
  `dial_code` varchar(50) NOT NULL,
  `emg_mobile` varchar(255) NOT NULL,
  `emg_name` varchar(100) NOT NULL,
  `salary` varchar(255) NOT NULL,
  `dept` varchar(50) NOT NULL,
  `sectin_nme` varchar(50) NOT NULL,
  `emptype` varchar(50) NOT NULL,
  `country` varchar(150) NOT NULL,
  `vacation_days` varchar(255) NOT NULL,
  `joining_date` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL,
  `login_usr` varchar(50) NOT NULL,
  `fly` varchar(10) NOT NULL,
  `bank_name` varchar(255) NOT NULL,
  `iban` varchar(255) NOT NULL,
  `note` varchar(100) NOT NULL,
  `ter_note` varchar(255) NOT NULL,
  `ter_date` varchar(50) NOT NULL,
  `dob` varchar(255) NOT NULL,
  `vac_period` varchar(100) NOT NULL,
  `sex` varchar(50) NOT NULL,
  `blood_type` varchar(255) NOT NULL,
  `mar_status` varchar(10) NOT NULL,
  `t_shirt_size` varchar(100) NOT NULL,
  `emp_sup_type` varchar(20) NOT NULL,
  `avatar` varchar(255) NOT NULL,
  `address` text CHARACTER SET utf8 NOT NULL,
  `insurance_no` varchar(100) NOT NULL,
  `insurance_exp` varchar(100) NOT NULL,
  `date_reg` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=238 ;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `name`, `emp_id`, `iqama`, `iqama_exp`, `iqama_exp_g`, `mobile`, `passport_number`, `passport_exp`, `email`, `g_number`, `dial_code`, `emg_mobile`, `emg_name`, `salary`, `dept`, `sectin_nme`, `emptype`, `country`, `vacation_days`, `joining_date`, `status`, `login_usr`, `fly`, `bank_name`, `iban`, `note`, `ter_note`, `ter_date`, `dob`, `vac_period`, `sex`, `blood_type`, `mar_status`, `t_shirt_size`, `emp_sup_type`, `avatar`, `address`, `insurance_no`, `insurance_exp`, `date_reg`) VALUES
(1, 'Abul Kasim Abdul Kader', '1002', '2111103756', '02/01/1443', '2021/08/11', '0502431457', '', '', '', '', '', '0', '0', '2091', 'POS', 'Head Office', 'Supporter', 'Bangladesh', '60', '06/03/2000', 'active', '', 'no', 'The National Commercial Bank', 'SA8210000066847846000104', '', '', '', '11/05/1970', '2 Years', 'male', 'A+', 'single', '0', 'mocha', './assets/emp_pics/10021Abul Kasim Abdul Kader1594716503.png', '0', '', '', '2019-01-08T19:09:02+03:00'),
(2, 'Abul Bashar Abdul Matin', '1003', '2153590688', '', '', '0504595288', '', '', '', '', '', '', '', '4651', 'Sales Department', 'Head Office', 'Supporter', 'Bangladesh', '50', '06/03/2000', 'no', '', 'no', 'The National Commercial Bank', 'SA1110000013347224006301', 'terminat', 'END OF SERVICE ', '2019-12-24T11:55:22+03:00', '01/01/1973', '2 Years', 'male', '', 'married', '', 'mocha', './assets/emp_pics/2153590688.001001-0-0-1003-1.jpg ', '', '', '', '2019-01-08T19:09:38+03:00'),
(3, 'Shah Alam Noor ul Haq', '1005', '', '', '', '', '', '', '', '', '', '', '', '', 'POS', 'Head Office', 'Supporter', '', '', '09/12/2000', 'no', '', 'no', '', '', 'terminat', '09/04/2019', '2019-04-09T09:19:41+03:00', '', '', '', '', '', '', 'mocha', './assets/emp_pics/defult.png', '', '', '', '2019-01-08T19:10:33+03:00'),
(4, 'Abdul Jamal Devakana', '1007', '2132481017', '10/05/1442', '2020/12/25', '0507968869', '0', '0', 'JAMALAZAD123@GMAIL.COM', '', '', '0507625281', 'friend ', '2700', 'Administration', 'Head Office', 'Supporter', 'India', '60', '09/12/2000', 'active', '', 'no', 'The National Commercial Bank', 'SA8710000018107922000108', '', '', '', '15/04/1971', '2 Years', 'male', 'A+', 'single', 'M', 'mocha', './assets/emp_pics/10074Abdul Jamal Devakana1594714303.png', 'albasteen ', '', '', '2019-01-08T19:11:12+03:00'),
(5, 'Shabbir Ahmed Mannan', '1116', '2149281301', '10/11/1442', '2021/06/20', '0503425239', '', '', '', '', '', '', '', '3200', 'POS', 'Head Office', 'Supporter', 'Bangladesh', '60', '09/12/2000', 'active', '', 'no', 'The National Commercial Bank', 'SA0310000013300000954409', '', '', '', '01/01/1973', '1 Year', 'male', '', 'married', '', 'mocha', './assets/emp_pics/11165Shabbir Ahmed Mannan1594716531.png', '', '', '', '2019-01-08T19:11:43+03:00'),
(6, 'Deni Rohyaman', '1011', '2157965142', '05/02/1442', '2020/09/23', '0534621985', '', '', '', '', '', '', '', '2053', 'POS', 'JMUM 01', 'Supporter', 'Indonesia', '60', '01/02/2001', 'active', '', 'no', 'The National Commercial Bank', 'SA6810000066847787000106', '', '', '', '03/01/1979', '2 Years', 'male', '', 'married', '', 'mocha', './assets/emp_pics/10116Deni Rohyaman1594716563.png', '', '', '', '2019-01-08T19:12:11+03:00'),
(7, 'Subair Kappen', '1020', '2175359427', '', '', '0561539625', '', '', '', '', '', '', '', '5594', 'Warehouse', 'Store -Coffee', 'Manager', 'India', '30', '30/04/2002', 'no', '', 'yes', 'The National Commercial Bank', 'SA3210000011473615000106', 'terminat', 'final exit', '2020-03-17T10:57:47+03:00', '22/02/1978', '1 Year', 'male', '', 'married', '', 'mocha', './assets/emp_pics/2175359427.001001-0-0-1020-1.jpg ', '', '', '', '2019-01-08T19:12:27+03:00'),
(8, 'Basheer Vellaran', '1021', '2176291157', '03/02/1442', '2020/09/21', '0506350521', '', '', '', '', '', '', '', '5600', 'POS', 'Head Office', 'Supporter', 'India', '30', '08/05/2002', 'active', '', 'no', 'The National Commercial Bank', '', '', '', '', '28/06/1980', '1 Year', 'male', '', 'married', '', 'mocha', './assets/emp_pics/10218Basheer Vellaran1594716607.png', '', '', '', '2019-01-08T19:12:46+03:00'),
(9, 'Assianar Irani Pparamban', '1022', '2181049046', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '21/05/2002', 'no', '', 'no', '', '', 'terminat', 'exit', '2019-04-08T15:26:20+03:00', '', '', '', '', '', '', 'mocha', './assets/emp_pics/defult.png', '', '', '', '2019-01-08T19:13:03+03:00'),
(10, 'Aslam Umar Kutty ', '1023', '2172289262', '29/11/1442', '2021/07/09', '0506367438', '', '', '', '', '', '', '', '3350', 'POS', 'Head Office', 'Supporter', 'India', '60', '15/05/2002', 'active', '', 'no', 'The National Commercial Bank', 'SA3210000000785390000100', '', '', '', '30/05/1978', '2 Years', 'male', '', 'married', '', 'mocha', './assets/emp_pics/102310Aslam Umar Kutty 1594716624.png', '', '', '', '2019-01-08T19:14:46+03:00'),
(11, 'Yousuf Abul Fayaz', '1024', '2016033686', '26/11/1442', '2021/07/06', '0505583246', '0', '0', 'yousuf@mochachino.co', '', '', '0505483246', 'brather', '5150', 'Maintenance', 'Head Office', 'Supporter', 'Myanmar', '30', '07/06/2002', 'active', '', 'no', 'The National Commercial Bank', 'SA2010000010100009802603', '', '', '', '14/09/1981', '1 Year', 'male', 'A+', 'married', 'xxxl', 'mocha', './assets/emp_pics/102411Yousuf Abul Fayaz1594714545.png', 'makkah -alomra aljadedah - 4415-makkah 7532-24418  bulding number  101', '0', '12/02/2021', '2019-01-08T19:15:02+03:00'),
(12, 'Saleem Irshadullah', '1026', '2187081001', '05/09/1442', '2021/04/17', '0552559361', '', '', '', '', '', '', '', '4000', 'Production ', 'Coffee Factory', 'Supporter', 'Myanmar', '50', '12/06/2002', 'active', '', 'no', 'The National Commercial Bank', 'SA0410000012674648000110', '', '', '', '27/05/1983', '2 Years', 'male', '', 'married', '', 'mocha', './assets/emp_pics/102612Saleem Irshadullah1594714864.png', '', '', '', '2019-01-08T19:15:21+03:00'),
(13, 'Siddiq Kallingal', '1038', '2142998554', '06/02/1442', '2020/09/24', '0561839446', '', '', 'siddiq@mochachino.co', '', '', '0539047060', 'friend fadel alrhman ', '9325', 'Production ', 'Coffee Factory', 'Manager', 'India', '30', '02/03/2001', 'active', '', 'no', 'The National Commercial Bank', 'SA1610000011473719000108', '', '', '', '15/01/1977', '1 Year', 'male', 'O+', 'single', 'M', 'mocha', './assets/emp_pics/103813Siddiq Kallingal1594714916.png', 'ziad bin khatib - 2409-23216 jeddah ', '18941328', '12/01/2021', '2019-01-08T19:15:33+03:00'),
(14, 'Atabur Rahman Taib', '1055', '2144217102', '21/05/1442', '2021/01/05', '0500751540', '', '', '', '', '', '', '', '4021', 'POS', 'Head Office', 'Supporter', 'Bangladesh', '60', '08/10/2002', 'active', '', 'no', 'The National Commercial Bank', 'SA5310000035271218000110', '', '', '', '03/06/1973', '2 Years', 'male', '', 'married', '', 'mocha', './assets/emp_pics/105514Atabur Rahman Taib1594716643.png', '', '', '', '2019-01-08T19:15:50+03:00'),
(15, 'Shafiq Ul Islam Sikandar', '1061', '2174357323', '27/06/1442', '2021/02/10', '0562328469', '', '', '', '', '', '', '', '1992', 'POS', 'JM 01', 'Supporter', 'Bangladesh', '60', '13/10/2002', 'active', '', 'no', 'The National Commercial Bank', 'SA6110000066847849000110', '', '', '', '01/02/1976', '2 Years', 'male', '', 'married', '', 'mocha', './assets/emp_pics/106115Shafiq Ul Islam Sikandar1594716692.png', '', '', '', '2019-01-08T19:16:04+03:00'),
(16, 'Joynal Khan Rahman Khan', '1062', '2174356630', '', '', '0552442566', '', '', '', '', '', '', '', '1773', 'POS', 'JM 38', 'Supporter', 'Bangladesh', '60', '13/10/2002', 'no', '', 'no', 'The National Commercial Bank', 'SA4710000018576272000104', 'terminat', 'final exit', '2020-02-20T12:07:34+03:00', '10/10/1978', '2 Years', 'male', '', 'married', '', 'mocha', './assets/emp_pics/2174356630.1062.jpg ', '', '', '', '2019-01-08T19:16:19+03:00'),
(17, 'Mohammed Imtiaz Idris ', '1064', '2174359097', '27/06/1442', '2021/02/10', '0545570445', '', '', '', '', '', '', '', '1956', 'POS', 'JM 14', 'Supporter', 'Bangladesh', '60', '13/10/2002', 'active', '', 'no', 'The National Commercial Bank', 'SA8410000018173241000106', '', '', '', '05/05/1978', '2 Years', 'male', '', 'married', '', 'mocha', './assets/emp_pics/106417Mohammed Imtiaz Idris 1594716716.png', '', '', '', '2019-01-08T19:16:34+03:00'),
(18, 'Alamgir Gulam Rasoul', '1067', '2167976543', '04/02/1442', '2020/09/22', '0534822760', '', '', '', '', '', '', '', '2053', 'POS', 'JM 15', 'Supporter', 'Bangladesh', '60', '15/10/2002', 'active', '', 'no', 'The National Commercial Bank', 'SA2110000018647016007204', '', '', '', '03/02/1971', '2 Years', 'male', '', 'married', '', 'mocha', './assets/emp_pics/106718Alamgir Gulam Rasoul1594716737.png', '', '', '', '2019-01-08T19:16:54+03:00'),
(19, 'Saif Ur Rahman M. Asar', '1070', '2184146468', '06/11/1442', '2021/06/16', '0504170052', '', '', '', '', '', '', '', '2377', 'POS', 'JM 18', 'Supporter', 'Bangladesh', '60', '24/10/2002', 'active', '', 'no', 'The National Commercial Bank', 'SA7810000066848858000109', '', '', '', '08/08/1975', '2 Years', 'male', '', 'married', '', 'mocha', './assets/emp_pics/107019Saif ur Rahman M. Asar1594716801.png', '', '', '', '2019-01-08T19:17:06+03:00'),
(20, 'Kuttiman Pettikal', '1075', '2177757628', '08/01/1443', '2021/08/17', '0501472856', 'S9759728', '24/10/2028', 'p.kman@mochachino.co', '', '', '0551052629', 'WIFE', '4651', 'Maintenance', 'Head Office', 'Supporter', 'India', '30', '01/10/2002', 'active', '', 'no', 'The National Commercial Bank', 'SA9110000011974358000104', '', '', '', '20/03/1967', '1 Year', 'male', 'O+', 'single', 'large ', 'mocha', './assets/emp_pics/107520Kuttiman Pettikal1594714615.png', 'sharafyah 6210', '', '', '2019-01-08T19:17:18+03:00'),
(21, 'Mohd. Washimuddin', '1077', '2182548723', '23/06/1442', '2021/02/06', '0541020142', '-', '-', 'washim@mochachino.co', '', '', '0556691645', '', '4021', 'Inspection', 'Head Office', 'Supporter', 'Bangladesh', '50', '24/11/2002', 'active', '', 'no', 'The National Commercial Bank', 'SA7310000013300000343407', '', '', '', '25/04/1977', '2 Years', 'male', 'A+', 'married', 'large ', 'mocha', './assets/emp_pics/107721Mohd. Washimuddin1594714401.png', 'alzahrah , ', '', '', '2019-01-08T19:17:57+03:00'),
(22, 'Ajger Ali Mohammed', '1084', '2184638415', '', '', '0570854454', '', '', '', '', '', '', '', '1773', 'POS', 'JM 16', 'Supporter', 'Bangladesh', '60', '29/12/2002', 'no', '', 'no', 'The National Commercial Bank', 'SA6510000062751533000103', 'terminat', 'final exit', '2020-07-19T10:03:58+03:00', '01/01/1978', '2 Years', 'male', '', 'married', '', 'mocha', './assets/emp_pics/108422Ajger Ali Mohammed1594716834.png', '', '', '', '2019-01-08T19:18:13+03:00'),
(23, 'Mujeeb Rehman Koziyan', '1088', '2184329353', '21/07/1442', '2021/03/05', '0504530088', '', '', '', '', '', '', '', '5050', 'POS', 'Head Office', 'Supporter', 'India', '60', '05/01/2003', 'active', '', 'no', 'The National Commercial Bank', 'SA1510000011473648000200', '', '', '', '24/03/1973', '2 Years', 'male', '', 'married', '', 'mocha', './assets/emp_pics/108823Mujeeb Rehman Koziyan1594717004.png', '', '', '', '2019-01-08T19:18:25+03:00'),
(24, 'Mohammed Jamaluddin', '1098', '2181563509', '17/05/1442', '2021/01/01', '0568936578', '', '', '', '', '', '', '', '1773', 'POS', 'JM 19', 'Supporter', 'Bangladesh', '60', '17/03/2003', 'active', '', 'no', 'The National Commercial Bank', 'SA8010000018109518000210', '', '', '', '15/07/1976', '2 Years', 'male', '', 'married', '', 'mocha', './assets/emp_pics/109824Mohammed Jamaluddin1594716859.png', '', '', '', '2019-01-08T19:19:01+03:00'),
(25, 'Shaheen Ahmed Mannan', '1101', '2188048330', '05/02/1442', '2020/09/23', '0557259127', '', '', '', '', '', '', '', '1773', 'POS', 'JM 03', 'Supporter', 'Bangladesh', '60', '05/04/2003', 'active', '', 'no', 'The National Commercial Bank', 'SA8310000018109315000205', '', '', '', '12/06/1978', '2 Years', 'male', '', 'married', '', 'mocha', './assets/emp_pics/110125Shaheen Ahmed Mannan1594716892.png', '', '', '', '2019-01-08T19:19:20+03:00'),
(26, 'Mohd. Refon Miah', '1112', '2187484551', '13/01/1442', '2020/09/01', '0563144255', '', '', '', '', '', '', '', '1773', 'POS', 'JM 39', 'Supporter', 'Bangladesh', '60', '20/05/2003', 'active', '', 'no', 'The National Commercial Bank', 'SA5110000018647018009908', '', '', '', '10/04/1973', '2 Years', 'male', '', 'married', '', 'mocha', './assets/emp_pics/111226Mohd. Refon Miah1594716945.png', '', '', '', '2019-01-08T19:19:34+03:00'),
(27, 'Nur ul Huda Zain al Abidin', '1113', '2188662031', '', '', '0550399856', '', '', '', '', '', '', '', '4021', 'POS', 'Head Office', 'Supporter', 'Bangladesh', '60', '05/06/2003', 'no', '', 'yes', 'The National Commercial Bank', 'SA9710000013347257009100', 'terminat', 'transfer to other company', '2020-07-12T12:10:43+03:00', '03/03/1972', '2 Years', 'male', '', 'married', '', 'mocha', './assets/emp_pics/2188662031.Nurul Huda.jpg ', '', '', '', '2019-01-08T19:19:51+03:00'),
(28, 'Mukhtar Hussain', '1114', '2154834499', '06/07/1442', '2021/02/18', '0540711579', '', '', '', '', '', '', '', '2750', 'Warehouse', 'Store -Sugar', 'Supporter', 'Bangladesh', '50', '26/07/2003', 'active', '', 'no', 'Riyad Bank', 'SA1520000001201517049940', '', '', '', '26/02/1972', '2 Years', 'male', '', 'married', '', 'mocha', './assets/emp_pics/111428Mukhtar Hussain1594716235.png', '', '', '', '2019-01-08T19:20:03+03:00'),
(29, 'Mujib Ul Haq', '1117', '2173242369', '14/07/1442', '2021/02/26', '0534358429', '', '', '', '', '', '', '', '1773', 'POS', 'JM 20', 'Supporter', 'Bangladesh', '60', '15/12/2003', 'active', '', 'no', 'The National Commercial Bank', 'SA9710000015762592000104', '', '', '', '15/03/1976', '2 Years', 'male', '', 'married', '', 'mocha', './assets/emp_pics/111729Mujib ul haq1594717068.png', '', '', '', '2019-01-08T19:20:15+03:00'),
(30, 'Abul Kashem Amin', '1119', '2181986023', '23/05/1442', '2021/01/07', '0507823226', '', '', '', '', '', '', '', '1773', 'POS', 'JM 11', 'Supporter', 'Bangladesh', '60', '21/12/2003', 'active', '', 'no', 'The National Commercial Bank', 'SA7010000066849115000104', '', '', '', '21/11/1975', '2 Years', 'male', '', 'married', '', 'mocha', './assets/emp_pics/111930Abul Kashem Amin1594717090.png', '', '', '', '2019-01-08T19:20:27+03:00'),
(31, 'Zulfu Miah Siraj Miah', '1133', '2204432575', '29/01/1442', '2020/09/17', '0502664350', '', '', '', '', '', '', '', '1773', 'POS', 'MM 01', 'Supporter', 'Bangladesh', '60', '04/05/2004', 'active', '', 'no', 'The National Commercial Bank', 'SA8210000016459371000103', '', '', '', '16/05/1973', '2 Years', 'male', '', 'married', '', 'mocha', './assets/emp_pics/113331Zulfu Miah Siraj Miah1594717115.png', '', '', '', '2019-01-08T19:20:42+03:00'),
(32, 'Munir Kursila Kandy', '1136', '2205551639', '19/05/1442', '2021/01/03', '0556924241', '', '', '', '', '', '', '', '4729', 'POS', 'Head Office', 'Supporter', 'India', '60', '10/06/2004', 'active', '', 'no', 'The National Commercial Bank', 'SA1810000000777815000207', '', '', '', '21/04/1981', '2 Years', 'male', '', 'married', '', 'mocha', './assets/emp_pics/113632Munir Kursila Kandy1594717211.png', '', '', '', '2019-01-08T19:20:56+03:00'),
(33, 'Anwar Hussain', '1146', '2207518677', '20/07/1442', '2021/03/04', '0506603134', '', '', '', '', '', '', '', '4490', 'POS', 'Head Office', 'Supporter', 'India', '60', '08/08/2004', 'active', '', 'yes', 'The National Commercial Bank', 'SA1910000016058979000104', '', '', '', '05/04/1974', '2 Years', 'male', '', 'married', '', 'mocha', './assets/emp_pics/114633Anwar Hussain1594717229.png', '', '', '', '2019-01-08T19:21:15+03:00'),
(34, 'Jahangir Hussain', '1148', '2198026128', '26/12/1442', '2021/08/05', '0507507726', '', '', '', '', '', '', '', '1773', 'POS', 'JMUM 03', 'Supporter', 'Bangladesh', '60', '08/08/2004', 'active', '', 'no', 'The National Commercial Bank', 'SA8910000016459323000109', '', '', '', '30/06/1979', '2 Years', 'male', '', 'married', '', 'mocha', './assets/emp_pics/114834Jahangir Hussain1594717290.png', '', '', '', '2019-01-08T19:21:29+03:00'),
(35, 'Nur Mohammed Mofiz', '1163', '2199300571', '27/11/1442', '2021/07/07', '0550373094', '', '', '', '', '', '', '', '1773', 'POS', 'JM 08', 'Supporter', 'Bangladesh', '60', '02/11/2004', 'active', '', 'no', 'The National Commercial Bank', 'SA2310000065752698000100', '', '', '', '10/09/1978', '2 Years', 'male', '', 'married', '', 'mocha', './assets/emp_pics/116335Nur Mohammed Mofiz1594717402.png', '', '', '', '2019-01-08T19:21:44+03:00'),
(36, 'Air Ahmed Hussain', '1168', '2207727815', '01/07/1442', '2021/02/13', '0561063500', '', '', '', '', '', '', '', '1773', 'POS', 'JM 08', 'Supporter', 'Bangladesh', '60', '23/09/2005', 'active', '', 'no', 'The National Commercial Bank', 'SA2710000034561016000102', '', '', '', '01/12/1974', '2 Years', 'male', '', 'married', '', 'mocha', './assets/emp_pics/116836Air Ahmed Hussain1594717352.png', '', '', '', '2019-01-08T19:21:59+03:00'),
(37, 'Salem Noor Hussain', '1178', '2008856599', '02/01/1443', '2021/08/11', '0555697937', '', '', 'salemnoor.7937@gmail.com', '', '', '0508615576', 'wife', '3100', 'Maintenance', 'Head Office', 'Supporter', 'Myanmar', '60', '25/11/2007', 'active', '', 'no', 'Al Rajhi Bank', 'SA6480000458608010135082', '', '', '', '19/02/1985', '2 Years', 'male', 'A+', 'single', 'M', 'mocha', './assets/emp_pics/117837Salem Noor Hussain1594714645.png', 'guzain - alkhumra ', '', '', '2019-01-08T19:22:20+03:00'),
(38, 'Mazin Tafazzul', '1193', '2227368541', '20/07/1442', '2021/03/04', '0531985030', '', '', '', '', '', '', '', '1806', 'POS', 'JMUM 02', 'Supporter', 'Bangladesh', '60', '26/02/2007', 'active', '', 'no', 'The National Commercial Bank', 'SA7110000018576256000104', '', '', '', '01/02/1971', '2 Years', 'male', '', 'married', '', 'mocha', './assets/emp_pics/119338Mazin Tafazzul1594717324.png', '', '', '', '2019-01-08T19:22:36+03:00'),
(39, 'Abul Kasim Ismail', '1198', '2166375507', '24/07/1442', '2021/03/08', '0508690226', '', '', '', '', '', '', '', '1689', 'POS', 'JM 19', 'Supporter', 'Bangladesh', '60', '02/04/2007', 'active', '', 'no', 'The National Commercial Bank', 'SA3810000015959243000108', '', '', '', '02/04/1972', '2 Years', 'male', '', 'married', '', 'mocha', './assets/emp_pics/119839Abul kasim Ismail1594717449.png', '', '', '', '2019-01-08T19:22:48+03:00'),
(40, 'Md. Jashimuddin', '1217', '2112613365', '26/03/1442', '2020/11/12', '0551523757', '', '', '', '', '', '', '', '1689', 'POS', 'JM 32', 'Supporter', 'Bangladesh', '60', '15/09/2007', 'active', '', 'no', 'The National Commercial Bank', 'SA6710000034095504000110', '', '', '', '13/04/1969', '2 Years', 'male', '', 'married', '', 'mocha', './assets/emp_pics/121740Md. Jashimuddin1594717470.png', '', '', '', '2019-01-08T19:22:59+03:00'),
(41, 'Mohammed Ali Pullichola', '1243', '2256186673', '16/12/1442', '2021/07/26', '0532503117', '', '', '', '', '', '', '', '2586', 'Warehouse', 'Store -Sugar', 'Supporter', 'India', '50', '30/07/2008', 'active', '', 'no', 'The National Commercial Bank', 'SA4310000066847696000103', '', '', '', '01/10/1980', '2 Years', 'male', '', 'married', '', 'mocha', './assets/emp_pics/124341Mohammed Ali Pullichola1594716282.png', '', '', '', '2019-01-08T19:23:09+03:00'),
(42, 'Rafeek Madasseri', '1244', '2241093653', '18/01/1442', '2020/09/06', '0548638239', '', '', '', '', '', '', '', '2421', 'Production ', 'Sugar Factory', 'Supporter', 'India', '50', '20/07/2008', 'active', '', 'no', 'The National Commercial Bank', 'SA3610000066847697000105', '', '', '', '01/03/1979', '2 Years', 'male', '', 'married', '', 'mocha', './assets/emp_pics/124442Rafeek Madasseri1594714954.png', '', '', '', '2019-01-08T19:23:25+03:00'),
(43, 'Mohammed Ikhlaq A. Rouf', '1248', '2259239578', '21/11/1442', '2021/07/01', '0535079697', '', '', '', '', '', '', '', '2712', 'Production ', 'Coffee Factory', 'Supporter', 'India', '50', '05/11/2008', 'active', '', 'no', 'The National Commercial Bank', 'SA3910000018110803000106', '', '', '', '05/07/1978', '2 Years', 'male', '', 'married', '', 'mocha', './assets/emp_pics/124843Mohammed Ikhlaq A. Rouf1594715004.png', '', '', '', '2019-01-08T19:23:47+03:00'),
(44, 'Quaisar Azeem', '1249', '2255427375', '01/01/1442', '', '0537280308', '', '', '', '', '', '', '', '2312', 'Production ', 'Sugar Factory', 'Supporter', 'India', '50', '05/11/2008', 'no', '', 'no', 'The National Commercial Bank', 'SA4510000066847824000103', 'terminat', 'END OF SERVICE ', '2020-01-15T12:48:06+03:00', '01/09/1984', '2 Years', 'male', '', 'married', '', 'mocha', './assets/emp_pics/2255427375.001001-0-0-1249-1.jpg ', '', '', '', '2019-01-08T19:24:04+03:00'),
(45, 'Mohammed Ibrahim Khalil', '1252', '2244022485', '03/03/1442', '2020/10/20', '0561355975', '', '', '', '', '', '', '', '1689', 'POS', 'JM 18', 'Supporter', 'Bangladesh', '60', '07/11/2008', 'active', '', 'no', 'The National Commercial Bank', 'SA1010000018647046009502', '', '', '', '07/02/1981', '2 Years', 'male', '', 'married', '', 'mocha', './assets/emp_pics/125245Mohammed Ibrahim Khalil1594717510.png', '', '', '', '2019-01-08T19:24:15+03:00'),
(46, 'Md. Jashimuddin Nur Ahmed', '1256', '2254206309', '', '', '0532690353', '', '', '', '', '', '', '', '1689', 'POS', 'JM 03', 'Supporter', 'Bangladesh', '60', '13/02/2009', 'no', '', 'no', 'The National Commercial Bank', 'SA5510000018647050007905', 'terminat', '23/04/2019', '2019-04-24T14:49:34+03:00', '01/03/1981', '2 Years', 'male', '', 'married', '', 'mocha', './assets/emp_pics/2254206309.1256.jpg ', '', '', '', '2019-01-08T19:24:27+03:00'),
(47, 'Abdul Rahman Hassan Matloob', '1323', '2124306487', '22/12/1441', '2020/08/12', '0538885886', '', '', 'abomd33@gmail.com', '', '', '0577171420', 'wife', '3000', 'Maintenance', 'Head Office', 'Supporter', 'Myanmar', '60', '25/10/2010', 'active', '', 'no', 'Al Rajhi Bank', 'SA6980000378608010214652', '', '', '', '01/11/1991', '2 Years', 'male', 'A+', 'married', 'xl ', 'mocha', './assets/emp_pics/132347Abdul Rahman Hassan Matloob1594714671.png', 'almuntzhar kilo 14', '', '', '2019-01-08T19:24:40+03:00'),
(48, 'Dukiman Samad', '1331', '2304893536', '15/06/1442', '2021/01/29', '0556445706', '', '', '', '', '', '', '', '1642', 'POS', 'JM 29', 'Supporter', 'Indonesia', '60', '18/02/2011', 'active', '', 'no', 'The National Commercial Bank', 'SA9010000066847783000109', '', '', '', '12/03/1979', '2 Years', 'male', '', 'married', '', 'mocha', './assets/emp_pics/133148Dukiman Samad1594717576.png', '', '', '', '2019-01-08T19:35:03+03:00'),
(49, 'Haris Vadekkepurath', '1351', '2309590335', '26/01/1442', '2020/09/14', '0534654628', '', '', '', '', '', '', '', '1992', 'POS', 'JMUM 02', 'Supporter', 'India', '60', '25/06/2011', 'active', '', 'no', 'The National Commercial Bank', 'SA6710000018657128000102', '', '', '', '30/07/1989', '2 Years', 'male', '', 'married', '', 'mocha', './assets/emp_pics/135149Haris Vadekkepurath1594717599.png', '', '', '', '2019-01-08T19:35:20+03:00'),
(50, 'Shah Alam Noor Ul Haq', '1357', '2113245498', '23/12/1441', '2020/08/13', '0557237125', '', '', '', '', '', '', '', '3664', 'POS', 'Head Office', 'Supporter', 'Bangladesh', '60', '17/07/2012', 'active', '', 'no', 'The National Commercial Bank', 'SA2510000011473652000203', '', '', '', '01/02/1968', '2 Years', 'male', '', 'married', '', 'mocha', './assets/emp_pics/135750Shah Alam Noor ul Haq1594717776.png', '', '', '', '2019-01-08T19:35:40+03:00'),
(51, 'Saepollah Sudin Yani', '1359', '2349820668', '21/11/1442', '2021/07/01', '0538011627', '', '', '', '', '', '', '', '1642', 'POS', 'JM 38', 'Supporter', 'Indonesia', '60', '01/04/2013', 'active', '', 'no', 'The National Commercial Bank', 'SA8210000066847785000102', '', '', '', '11/09/1984', '2 Years', 'male', '', 'married', '', 'mocha', './assets/emp_pics/135951Saepollah Sudin Yani1594717624.png', '', '', '', '2019-01-08T19:35:51+03:00'),
(52, 'Faruque Ahmed Md. Giashuddin', '1362', '2252228271', '19/05/1442', '2021/01/03', '0557960076', '', '', '', '', '', '', '', '1700', 'POS', 'MM 05', 'Supporter', 'Bangladesh', '60', '03/09/2013', 'active', '', 'no', 'The National Commercial Bank', 'SA6310000018173589000109', '', '', '', '13/04/1983', '2 Years', 'male', '', 'married', '', 'mocha', './assets/emp_pics/136252Faruque Ahmed Md. Giashuddin1594717641.png', '', '', '', '2019-01-08T19:36:02+03:00'),
(53, 'Faisal Abdul Rahim A. Hadi', '1395', '2164226629', '', '', '0577285936', '', '', '', '', '', '', '', '2000', 'Production ', 'Sugar Factory', 'Supporter', 'Myanmar', '50', '12/10/2012', 'no', '', 'no', 'Riyad Bank', 'SA2320000001900784069940', 'terminat', 'END OF SERVICE ', '2019-12-11T10:49:38+03:00', '18/05/1996', '2 Years', 'male', '', 'married', '', 'mocha', './assets/emp_pics/2164226629.001001-0-0-1395-1.jpg ', '', '', '', '2019-01-08T19:36:39+03:00'),
(54, 'Mohammed Syed Hussain', '1400', '2123750586', '29/10/1442', '2021/06/10', '0538866626', '3000015574', '16/08/2021', 'hammoody2012@gmail.com', '', '', '0534302037', '', '2000', 'Maintenance', 'Head Office', 'Supporter', 'Myanmar', '50', '01/05/2013', 'active', '', 'no', 'Al Rajhi Bank', 'SA1080000425608010517362', '', '', '', '10/06/1994', '2 Years', 'male', 'AB+', 'married', 'xl ', 'mocha', './assets/emp_pics/140054Mohammed Syed Hussain1594714702.png', 'muntzahat 8625- no 01- box 2424-22352 jeddah ', '0', '12/02/2021', '2019-01-08T19:36:50+03:00'),
(55, 'Nurul Islam Abdul Hakim', '1402', '2130209543', '28/11/1441', '2020/07/19', '0581863237', '', '', '', '', '', '0576603050', 'wife', '2200', 'Transportation', 'Head Office', 'Supporter', 'Myanmar', '60', '24/06/2013', 'active', '', 'no', 'Bank Al-Bilad', 'SA4815000618122957690008', '', '', '', '28/10/1981', '2 Years', 'male', 'O+', 'married', 'xl ', 'mocha', './assets/emp_pics/140255Nurul Islam Abdul Hakim1594715890.png', 'harazat - wadi eshair ', '', '', '2019-01-08T19:37:03+03:00'),
(56, 'Yousuf Yakub Ali', '1403', '2227062656', '04/11/1442', '2021/06/14', '0558427893', '', '', '', '', '', '', '', '2042', 'Production ', 'Coffee Factory', 'Supporter', 'Bangladesh', '50', '07/07/2013', 'active', '', 'no', 'The National Commercial Bank', 'SA6510000066847692000106', '', '', '', '10/03/1975', '2 Years', 'male', '', 'married', '', 'mocha', './assets/emp_pics/140356Yousuf Yakub Ali1594715075.png', '', '', '', '2019-01-08T19:37:14+03:00'),
(57, 'Abdullah Fazlur Rahim', '1405', '2346578780', '02/02/1442', '2020/09/20', '0568154622', '', '', '', '', '', '', '', '3200', 'Transportation', 'Head Office', 'Supporter', 'Pakistan', '50', '21/07/2013', 'active', '', 'no', 'The National Commercial Bank', 'SA0810000013347266005706', '', '', '', '01/01/1965', '2 Years', 'male', '', 'married', '', 'mocha', './assets/emp_pics/140557Abdullah Fazlur Rahim1594716019.png', '', '', '', '2019-01-08T19:37:26+03:00'),
(58, 'Mohammed Khalid', '1406', '2311040493', '22/01/1442', '2020/09/10', '0534035933', '', '', '', '', '', '', '', '2242', 'Warehouse', 'Store -Coffee', 'Supporter', 'India', '50', '01/08/2013', 'active', '', 'no', 'The National Commercial Bank', 'SA5110000066847694000110', '', '', '', '14/03/1972', '2 Years', 'male', '', 'married', '', 'mocha', './assets/emp_pics/140658Mohammed Khalid1594716389.png', '', '', '', '2019-01-08T19:37:37+03:00'),
(59, 'Shafar Ajim Azeemullah', '1410', '2397222379', '', '', '0537281483', '', '', '', '', '', '', '', '2500', 'Warehouse', 'Store -Coffee', 'Supporter', 'India', '50', '28/09/2013', 'no', '', 'no', 'The National Commercial Bank', 'SA7910000066847690000102', 'terminat', 'termiinat', '2020-08-12T12:43:01+03:00', '15/06/1990', '2 Years', 'male', '', 'married', '', 'mocha', './assets/emp_pics/141059Shafar Ajim Azeemullah1594716413.png', '', '', '', '2019-01-08T19:37:47+03:00'),
(60, 'Ishak Abdulmatloob', '1411', '2125035200', '17/07/1443', '2022/02/19', '0577636299', '', '', '', '', '', '', '', '2500', 'Warehouse', 'Store -Coffee', 'Supporter', 'Myanmar', '50', '05/01/2014', 'active', '', 'no', 'Riyad Bank', 'SA6920000001201517909940', '', '', '', '27/02/1973', '2 Years', 'male', '', 'married', '', 'mocha', './assets/emp_pics/141160Ishak Abdulmatloob1594716435.png', '', '', '', '2019-01-08T19:38:00+03:00'),
(61, 'Abdul HaiAbdul Matloob', '1412', '2124306479', '17/07/1443', '2022/02/19', '0534035933', '', '', '', '', '', '', '', '2500', 'Warehouse', 'Store -Sugar', 'Supporter', 'Myanmar', '50', '05/01/2014', 'active', '', 'no', 'Al Rajhi Bank', 'SA6680000378608012011049', '', '', '', '25/08/1987', '2 Years', 'male', '', 'married', '', 'mocha', './assets/emp_pics/141261Abdul HaiAbdul Matloob1594716462.png', '', '', '', '2019-01-08T19:38:10+03:00'),
(62, 'Haroon Sayd Hossain Malik', '1413', '2123106466', '03/01/1442', '2020/08/22', '0557717946', '', '', 'haroon17946@gmail.com', '', '', '0558942708', 'MAHA', '2000', 'Transportation', 'Head Office', 'Supporter', 'Myanmar', '60', '05/02/2014', 'active', '', 'no', 'Al Rajhi Bank', 'SA7380000425608010518071', '', '', '', '01/07/1992', '2 Years', 'male', 'A+', 'single', 'M', 'mocha', './assets/emp_pics/141362Haroon Sayd Hossain Malik1594716084.png', 'JEDDAH 30 MAKKAH', '', '', '2019-01-08T19:38:21+03:00'),
(63, 'Othman Ahmed Bashanum', '4517', '1077440772', '', '', '0593848337', '', '', '', '', '', '', '', '4000', 'POS', 'MM 01', 'Supporter', 'Saudi Arabia', '30', '12/10/2016', 'active', '', 'no', 'The National Commercial Bank', 'SA9710000000367194000101', '', '', '', '24/07/1992', '1 Year', 'male', '', 'single', '', 'mocha', './assets/emp_pics/451763Othman Ahmed Bashanum1594717662.png', '', '', '', '2019-01-08T19:38:39+03:00'),
(64, 'Yousuf Salem Dosh Al Harithy', '4519', '1073172569', '-', '', '0540567134', '-', '-', 'y0502135501@icloud.com', '', '', '0507677811', '', '4000', 'Production ', 'Sugar Factory', 'Supporter', 'Saudi Arabia', '30', '09/02/2017', 'active', '', 'no', 'The National Commercial Bank', 'SA7210000010866300000108', '', '', '', '25/07/1994', '1 Year', 'male', 'O+', 'married', 'large ', 'mocha', './assets/emp_pics/451964Yousuf Salem Dosh Al Harithy1594715276.png', 'alrawabi , Opposite khalid medical center ', '', '', '2019-01-08T19:38:53+03:00'),
(65, 'Ahmed Hussain Ahmed Habtour', '4522', '1079626972', '', '', '0557077791', '', '', '', '', '', '', '', '4500', 'Production ', 'Sugar Factory', 'Manager', 'Saudi Arabia', '30', '19/09/2017', 'no', '', 'yes', 'The National Commercial Bank', 'SA0510000011963774000106', 'terminat', 'termiinat', '2020-02-20T12:04:04+03:00', '27/09/1992', '1 Year', 'male', '', 'married', '', 'mocha', './assets/emp_pics/1079626972.001001-0-0-4522-1.jpg ', '', '', '', '2019-01-08T19:39:09+03:00'),
(66, 'Abdulhameed Essam Dali', '4523', '1092018744', '14/11/1445', '2024/05/22', '0567731645', '0', '13/07/2020', 'medo222d@hotmail.com', '', '', '0545601637', 'brather', '5370', 'POS', 'Head Office', 'Supporter', 'Saudi Arabia', '30', '22/10/2018', 'active', '', 'no', 'The National Commercial Bank', 'SA7810000001300000021101', '', '', '', '02/08/1995', '1 Year', 'male', 'B+', 'single', 'large ', 'mocha', './assets/emp_pics/452366Abdulhameed Essam Dali1594717679.png', 'meccah , albuhayrat ', '0', '13/01/2021', '2019-01-08T19:39:21+03:00'),
(67, 'Hatoon Ehab Yousuf Jan', '4403', '1073589895', '20/08/1444', '2023/03/13', '0592622414', '0', '13/07/2020', 'tooona__@hotmail.com', '', '', '0543197462', '', '4000', 'POS', 'JMUF 01', 'Supporter', 'Saudi Arabia', '30', '21/02/2013', 'active', '', 'no', 'Al Rajhi Bank', 'SA7780000451608010238503', '', '', '', '01/10/1992', '1 Year', 'female', 'O+', 'single', 'large ', 'mocha', './assets/emp_pics/440367Hatoon Ehab Yousuf Jan1594717694.png', 'alharamain 7 abdurahman alkhuzae street ', '0', '13/01/2021', '2019-01-08T19:39:33+03:00'),
(68, 'Fatimah Kamiran Disumimba', '4415', '2329487140', '10/02/1442', '2020/09/28', '0599626147', '', '', 'patdisumimba1491@gmail.com', '', '', '0572773096', 'father zkaria ', '2500', 'POS', 'Head Office', 'Supporter', 'Philippines', '60', '28/09/2013', 'active', '', 'no', 'The National Commercial Bank', 'SA7010000018647012007109', '', '', '', '15/02/1985', '2 Years', 'female', 'A+', 'single', 'M', 'mocha', './assets/emp_pics/441568Fatimah Kamiran Disumimba1594717753.png', 'aljamaa road ', '', '', '2019-01-08T19:39:45+03:00'),
(69, 'Rosaline Aguilar Cabayao', '4416', '2276385016', '17/01/1442', '2020/09/05', '0559035364', '', '', 'rosalyncabayao@gmail.com', '', '', '0559035364', 'fatmah ', '2500', 'POS', 'Head Office', 'Supporter', 'Philippines', '60', '28/09/2013', 'active', '', 'no', 'The National Commercial Bank', 'SA4310000018647012007207', '', '', '', '19/08/1987', '2 Years', 'female', 'A+', 'single', 'small', 'mocha', './assets/emp_pics/441669Rosaline Aguilar Cabayao1594717819.png', 'aljamaa road ', '', '', '2019-01-08T19:39:56+03:00'),
(70, 'Tihani Mohammed Sultan Al Qarni', '4420', '1094104971', '', '', '0536498707', '', '', '', '', '', '', '', '4000', 'POS', 'JMUF 01', 'Supporter', 'Saudi Arabia', '30', '22/12/2013', 'no', '', 'no', 'The National Commercial Bank', 'SA5710000011370092000110', 'terminat', '30/09/2019', '2019-10-03T10:12:15+03:00', '12/03/1992', '1 Year', 'female', '', 'single', '', 'mocha', './assets/emp_pics/1094104971.001001-0-0-4420-1.jpg ', '', '', '', '2019-01-08T19:40:08+03:00'),
(71, 'Wijdan Mohammed Ayedh Al Salmi', '4425', '1090517366', '', '', '0569599917', '', '', '', '', '', '', '', '4500', 'POS', 'JMUBT 01', 'Supporter', 'Saudi Arabia', '30', '18/08/2014', 'no', '', 'no', 'The National Commercial Bank', 'SA8410000015571672000210', 'terminat', 'resignation ', '2019-11-03T12:59:32+03:00', '03/07/1994', '1 Year', 'female', '', 'single', '', 'mocha', './assets/emp_pics/1090517366.001001-0-0-4425-1.jpg ', '', '', '', '2019-01-08T19:40:58+03:00'),
(72, 'Rahaf Al Jedani', '4448', '1104898786', '', '', '0551204923', '', '', '', '', '', '', '', '4000', 'POS', 'JMUF 03', 'Supporter', 'Saudi Arabia', '30', '06/12/2017', 'no', '', 'no', 'The National Commercial Bank', 'SA5910000010300000169209', 'terminat', 'resignation ', '2019-10-03T10:11:20+03:00', '28/04/1997', '1 Year', 'female', '', 'single', '', 'mocha', './assets/emp_pics/1104898786.Rahaf.jpg ', '', '', '', '2019-01-08T19:41:19+03:00'),
(73, 'Razan Hani Ibrahim Kudusi', '4449', '1112883325', '', '', '0599380661', '', '', '', '', '', '', '', '4000', 'POS', 'JMUF 01', 'Supporter', 'Saudi Arabia', '30', '30/09/2018', 'no', '', 'no', 'Bank AlJazira', 'SA0660100011480108680001', 'terminat', '30/09/2019', '2019-10-03T10:13:01+03:00', '12/05/2000', '1 Year', 'female', '', 'single', '', 'mocha', './assets/emp_pics/1112883325.Razan.jpg ', '', '', '', '2019-01-08T19:41:34+03:00'),
(74, 'Roua Hadi Hassan Muallim', '4456', '1104835150', '', '', '0534532249', '', '', '', '', '', '', '', '4000', 'POS', 'JMUBTF 01', 'Supporter', 'Saudi Arabia', '30', '10/10/2018', 'no', '', 'no', 'The National Commercial Bank', 'SA1710000010300000214100', 'terminat', 'resignation ', '2019-10-27T10:30:29+03:00', '09/02/1998', '1 Year', 'female', '', 'single', '', 'mocha', './assets/emp_pics/1104835150.Roua.jpg ', '', '', '', '2019-01-08T19:41:44+03:00'),
(75, 'Sameera Radad Al Maliki', '4450', '1071600306', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '24/04/2018', 'no', '', 'no', '', '', 'terminat', 'Retire 27/3/2019', '2019-04-08T16:18:06+03:00', '', '', '', '', '', '', 'mocha', './assets/emp_pics/defult.png', '', '', '', '2019-01-08T19:41:55+03:00'),
(76, 'Maha Ahmed Al Khasami', '4451', '1087439418', '', '', '0552123852', '', '', '', '', '', '', '', '4000', 'Production ', 'Sugar Factory', 'Supporter', 'Saudi Arabia', '30', '24/04/2018', 'no', '', 'no', 'The National Commercial Bank', 'SA1510000010347184009205', 'terminat', 'resignation ', '2019-10-03T09:59:55+03:00', '02/02/1994', '1 Year', 'female', '', 'single', '', 'mocha', './assets/emp_pics/1087439418.Maha.jpg ', '', '', '', '2019-01-08T19:42:09+03:00'),
(77, 'Huda Saad Al Amri', '4452', '1075141463', '', '', '0507116972', '', '', '', '', '', '', '', '4000', 'Production ', 'Sugar Factory', 'Supporter', 'Saudi Arabia', '30', '06/05/2018', 'no', '', 'no', 'Bank Al-Bilad', 'SA2315000604112586330005', 'terminat', 'resignation ', '2019-10-03T09:59:35+03:00', '14/07/1989', '1 Year', 'female', '', 'single', '', 'mocha', './assets/emp_pics/1075141463.Huda.jpg ', '', '', '', '2019-01-08T19:42:21+03:00'),
(78, 'Noura Saleh Bashinini', '4453', '1092230765', '', '', '0534554557', '', '', '', '', '', '', '', '4000', 'Production ', 'Sugar Factory', 'Supporter', 'Saudi Arabia', '30', '24/04/2018', 'no', '', 'no', 'The National Commercial Bank', 'SA3710000014183156000100', 'terminat', 'resignation', '2019-10-03T09:58:56+03:00', '31/08/1996', '1 Year', 'female', '', 'single', '', 'mocha', './assets/emp_pics/1092230765.Noura.jpg ', '', '', '', '2019-01-08T19:42:32+03:00'),
(79, 'Amani Mohammed Masrahi', '4454', '1093369733', '', '', '0541841703', '', '', '', '', '', '', '', '4000', 'Production ', 'Sugar Factory', 'Supporter', 'Saudi Arabia', '30', '24/04/2018', 'no', '', 'no', 'Al Rajhi Bank', 'SA2180000176608010731956', 'terminat', 'termiinat', '2020-01-30T12:00:49+03:00', '30/08/1988', '1 Year', 'female', '', 'single', '', 'mocha', './assets/emp_pics/1093369733.Amani.jpg ', '', '', '', '2019-01-08T19:42:42+03:00'),
(80, 'Awatif Saad Al Amri', '4455', '1075141471', '', '', '0564014172', '', '', '', '', '', '', '', '4000', 'Production ', 'Sugar Factory', 'Supporter', 'Saudi Arabia', '30', '06/05/2018', 'no', '', 'no', 'Bank Al-Bilad', 'SA9015000604112585310008', 'terminat', 'end of service ', '2019-10-27T10:30:09+03:00', '05/10/1991', '1 Year', 'female', '', 'single', '', 'mocha', './assets/emp_pics/1075141471.Awatif.jpg ', '', '', '', '2019-01-08T19:42:53+03:00'),
(81, 'Mr. Mohammed Medher', '2', '1042798981', '12/06/1457', '2035/08/17', '0505614919', '', '', 'mem@mochachino.co', '', '', '0532057070', 'huda medher ', '28617', 'Management', 'Head Office', 'Manager', 'Saudi Arabia', '30', '06/03/2000', 'active', '', 'no', 'The Saudi British Bank', 'SA6645000000264050006001', '', '', '', '08/01/1965', '1 Year', 'male', 'A+', 'single', 'larage', 'mocha', './assets/emp_pics/281Mr. Mohammed Medher1594714793.png', '7351 Abdul Rahman Ibn Abi Bakr As Sedik&Atilde;&cent;', '', '', '2019-01-08T19:43:12+03:00'),
(82, 'Mohammed Imtiaz Ali ', '9', '2183975982', '', '', '0508578233', '', '', '', '', '', '', '', '11170', 'HRD and Housing', 'Head Office', 'Manager', 'India', '30', '14/09/2002', 'no', '', 'no', 'The National Commercial Bank', 'SA9610000013395929000109', 'terminat', '05/08/2019', '2019-10-03T10:13:39+03:00', '01/03/1969', '1 Year', 'male', '', 'married', '', 'mocha', './assets/emp_pics/2183975982.001001-0-0-0009-1.jpg ', '', '', '', '2019-01-08T19:43:28+03:00'),
(83, 'Aboo Backer Kappil', '10', '2182274023', '31/03/1442', '2020/11/17', '0509365756', '', '', 'ABACKAR@MOCHACHINO.CO', '', '', '0563030439', 'SHAMERA', '19940', 'Finance', 'Head Office', 'Manager', 'India', '30', '08/08/2002', 'active', '', 'no', 'The National Commercial Bank', 'SA9210000011473108000105', '', '', '', '05/03/1974', '1 Year', 'male', 'AB+', 'married', 'M', 'mocha', './assets/emp_pics/1083Aboo Backer Kappil1594714154.png', 'AL WROOD 2ND ', '', '', '2019-01-08T19:43:47+03:00'),
(84, 'Rasheed Mambrathodi', '18', '2184414726', '05/07/1442', '2021/02/17', '0567603725', 'N8507114', '30/04/2026', 'mrasheed@mochachino.co', '', '', '0500211276', '', '4808', 'Finance', 'Head Office', 'Supporter', 'India', '30', '02/03/2003', 'active', '', 'no', 'The National Commercial Bank', 'SA8010000013347270007309', '', '', '', '27/05/1977', '1 Year', 'male', 'O+', 'married', 'large ', 'mocha', './assets/emp_pics/1884Rasheed Mambrathodi1594714202.png', ' sharafyah 6210', '', '', '2019-01-08T19:44:01+03:00'),
(85, 'Abdul Malik Shahul', '29', '2153858895', '03/05/1442', '2020/12/18', '0501261229', 'P0039509', '23/07/2026', 'amalik@mochachino.co', '', '', '0569912455', '', '7204', 'Purchase', 'Head Office', 'Supporter', 'India', '30', '03/06/2006', 'active', '', 'no', 'The National Commercial Bank', 'SA9310000013347088008206', '', '', '', '03/03/1974', '1 Year', 'male', 'A+', 'married', 'xl ', 'mocha', './assets/emp_pics/2985Abdul Malik Shahul1594715449.png', 'alslama road behind refat supermarket ', '', '', '2019-01-08T19:44:12+03:00'),
(86, 'Sajjad Hussain ', '42', '2124096906', '01/06/1442', '2021/01/15', '0552779588', 'WS6898952', '12/12/2026', 's.hussain@mochachino.co', '', '', '0583121341', '', '6700', 'POS', 'Head Office', 'Manager', 'Pakistan', '30', '03/12/2007', 'active', '', 'no', 'The National Commercial Bank', 'SA5310000013347270007407', '', '', '', '30/06/1978', '1 Year', 'male', 'B+', 'married', 'larage', 'mocha', './assets/emp_pics/4286Sajjad Hussain 1594717839.png', 'alamariah  road ', '', '', '2019-01-08T19:44:24+03:00'),
(87, 'Mohammed Samer Medani', '47', '2200481154', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '18/03/2008', 'no', '', 'no', '', '', 'terminat', '09/04/2019', '2019-04-09T09:10:37+03:00', '', '', '', '', '', '', 'mocha', './assets/emp_pics/defult.png', '', '', '', '2019-01-08T19:44:35+03:00'),
(88, 'Abdul Wahab A. Ghafoor', '49', '2016697217', '21/03/1442', '2020/11/07', '0501497585', '0', '07/07/2020', 'awahab@mochachino.co', '', '', '0562124142', 'brother yonees', '4276', 'Inspection', 'Head Office', 'Manager', 'Pakistan', '60', '25/01/2009', 'active', '', 'no', 'Saudi Investment Bank', 'SA9265000000235456381001', '', '', '', '30/09/1978', '2 Years', 'male', 'O+', 'single', 'XXXL', 'mocha', './assets/emp_pics/4988Abdul Wahab A. Ghafoor1594714436.png', 'fasaliah almakroona street ', '0', '08/07/2020', '2019-01-08T19:44:46+03:00'),
(89, 'Yasser Halaby', '82', '2322341864', '10/12/1442', '2021/07/20', '0541079090', 'A24091089', '11/01/2026', 'yhalaby@mochachino.co', '', '', '0547286061', '', '5350', 'Sales Department', 'Store -Riyadh', 'Supporter', 'Egypt', '50', '30/04/2012', 'active', '', 'no', 'The National Commercial Bank', 'SA9810000036776725000110', '', '', '', '31/12/1965', '2 Years', 'male', 'B+', 'married', 'xl ', 'mocha', './assets/emp_pics/8289Yasser Halaby1594715715.png', 'albawadi road behind alslama medical balding no 7 ', '00', '13/04/2021', '2019-01-08T19:44:57+03:00'),
(90, 'Khalil Reza Farouk Shokr', '84', '2283938922', '', '', '0583640255', '', '', '', '', '', '', '', '3660', 'Sales Department', 'Store -Makkah', 'Supporter', 'Egypt', '50', '08/08/2012', 'no', '', 'no', 'The National Commercial Bank', 'SA5110000032900000133400', 'terminat', '29/06/2019', '2019-10-13T11:05:00+03:00', '30/11/1987', '2 Years', 'male', '', 'married', '', 'mocha', './assets/emp_pics/2283938922.001001-0-0-0084-1.jpg ', '', '', '', '2019-01-08T19:45:06+03:00'),
(91, 'Mohammed Awais ', '114', '2372565842', '', '', '0541545690', '', '', '', '', '', '', '', '4501', 'Finance', 'Head Office', 'Supporter', 'Pakistan', '30', '23/11/2014', 'no', '', 'no', 'The National Commercial Bank', 'SA9310000013347182007802', 'terminat', 'not come back from vacation', '2019-06-11T10:35:59+03:00', '20/08/1989', '1 Year', 'male', '', 'married', '', 'mocha', './assets/emp_pics/2372565842.001001-0-0-0114-1.jpg ', '', '', '', '2019-01-08T19:45:22+03:00'),
(92, 'Hatim Shafi Felemban', '120', '1037099320', '21/05/1444', '2022/12/15', '0505810469', '0', '15/12/2022', 'hatim@mochachino.co', '', '', '0501304080', '', '13900', 'Sales Department', 'Head Office', 'Manager', 'Saudi Arabia', '30', '15/02/2015', 'active', '', 'no', 'Saudi Investment Bank', 'SA7565000000212310775001', '', '', '', '05/01/1969', '1 Year', 'male', 'B+', 'married', 'M', 'mocha', './assets/emp_pics/12092Hatim Shafi Felemban1594715825.png', ' 6769, King Fahd district, Makkah Al-Mukarramah, 24341-4901', '0', '13/01/2021', '2019-01-08T19:45:32+03:00'),
(93, 'Sultan Yassin Ahmed Al Delame', '4620', '1069466991', '-', '', '0540544559', '-', '-', 'sdelame@mochachino.co', '', '', '0567990268', '', '8052', 'HRD and Housing', 'Head Office', 'Supporter', 'Saudi Arabia', '30', '20/08/2015', 'active', '', 'no', 'Banque Saudi Fransi', 'SA5655000000077222600160', '', '', '', '09/08/1985', '1 Year', 'male', 'A+', 'married', 'large ', 'mocha', './assets/emp_pics/462093Sultan Yassin Ahmed Al Delame1594714336.png', '6836 muhammed ibn malik - Ar rabwah dist', '', '', '2019-01-08T19:45:46+03:00'),
(94, 'Mohammed Khairuddin', '141', '2400728917', '10/05/1442', '2020/12/25', '0571644513', '0', '16/12/2020', 'm.khairuddin@mochachino.co', '', '', '0571157348', 'Azam', '3100', 'Warehouse', 'Head Office', 'Supporter', 'India', '60', '22/11/2015', 'active', '', 'no', 'The National Commercial Bank', 'SA1010000013300000397406', '', '', '', '05/11/1994', '2 Years', 'male', 'B+', 'single', 'M', 'mocha', './assets/emp_pics/14194Mohammed Khairuddin1594715473.png', 'alsharafya ', '0', '13/01/2021', '2019-01-08T19:45:57+03:00'),
(95, 'Abdul Razak Al Fadl', '147', '', '', '', '0508600067', '', '', '', '', '', '', '', '8000', 'Public Relation', 'Head Office', 'Supporter', 'Misplaced Tribes', '30', '01/09/2016', 'active', '', 'no', '', '', '', '', '', '', '', '', '', '', '', 'mocha', './assets/emp_pics/14795Abdul Razak Al Fadl1594715364.png', '', '', '', '2019-01-08T19:46:14+03:00'),
(96, 'Mohammed Al Khayyat', '148', '1004145692', '-', '1970/01/01', '0505613569', '-', '-', 'mkhayat@mochachino.co', '', '', '0555613569', 'wife', '7000', 'Purchase', 'Head Office', 'Manager', 'Saudi Arabia', '30', '05/02/2017', 'no', '', 'no', 'The National Commercial Bank', 'SA7010000014649007000101', 'terminat', '31.10.2020', '2020-11-02T11:10:40+03:00', '22/02/1977', '1 Year', 'male', 'A+', 'single', 'large ', 'mocha', './assets/emp_pics/14896Mohammed Al Khayyat1594715499.png', '2893 abo bahyah alghadary -  alzahrah dist', '', '', '2019-01-08T19:46:24+03:00'),
(97, 'Mohammed Sameer Halwani', '149', '1101664355', '24/01/2023', '', '0552535450', '00', '00', 'mhalawani@mochachino.co', '', '', '0552030504', '', '4000', 'Inspection', 'Head Office', 'Supporter', 'Saudi Arabia', '30', '28/05/2017', 'active', '', 'no', 'Arab National Bank', 'SA7130400108065682880013', '', '', '', '14/07/1998', '1 Year', 'male', 'A+', 'single', 'xxl', 'mocha', './assets/emp_pics/14997Mohammed Sameer Halwani1594714507.png', 'alfisaliyah road , ', '', '', '2019-01-08T19:46:35+03:00'),
(98, 'Anees Afzal', '152', '2337318717', '16/03/1443', '2021/10/23', '0599723451', 'EY1514452', '08/08/2027', 'anees@mochachino.co', '', '92', '03456539306', 'Muhammad Afzal', '5000', 'IT', 'Head Office', 'Manager', 'Pakistan', '30', '30/09/2018', 'active', '', 'no', 'Al Rajhi Bank', 'SA2480000358608010178729', '', '', '', '14/04/1989', '1 Year', 'male', 'B+', 'single', 'XXL', 'mocha', './assets/emp_pics/15298Anees Afzal1594714056.png', 'Jeddah , An Nuzaha, Building Number 3585', '18941429', '12/02/2021', '2019-01-08T19:46:52+03:00'),
(99, 'Sultan Talal Moumenah', '153', '1101883302', '', '', '', '', '', '', '', '', '', '', '3000', 'Public Relation', 'Head Office', 'Supporter', 'Saudi Arabia', '30', '25/10/2018', 'no', '', 'no', 'Banque Saudi Fransi', '', 'terminat', 'termiinat', '2020-08-12T11:32:12+03:00', '23/04/1997', '1 Year', 'male', '', 'single', '', 'mocha', './assets/emp_pics/defult.png', '', '', '', '2019-01-08T19:47:26+03:00'),
(100, 'Mujibul Haq Shafeeq', '1013', '2111142069', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '18/07/2001', 'no', '', 'no', '', '', 'expired', '', '2019-01-12T20:07:37+03:00', '', '', '', '', '', '', 'mocha', './assets/emp_pics/defult.png', '', '', '', '2019-01-09T20:33:40+03:00'),
(101, 'Waqar Ul Samad Hafiz', '1041', '2168125819', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '09/04/2002', 'no', '', 'no', '', '', 'terminat', '', '2019-01-12T20:09:36+03:00', '', '', '', '', '', '', 'mocha', './assets/emp_pics/defult.png', '', '', '', '2019-01-12T19:45:42+03:00'),
(102, 'Basheer Shankalayil', '1042', '2185261571', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '23/05/2002', 'no', '', 'no', '', '', 'terminat', '', '2019-01-12T20:12:45+03:00', '', '', '', '', '', '', 'mocha', './assets/emp_pics/defult.png', '', '', '', '2019-01-12T20:10:21+03:00'),
(103, 'Mohd. Saleem Abu Ahmed', '1051', '2178179202', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '13/08/2002', 'no', '', 'no', '', '', 'terminat', '', '2019-01-12T20:19:12+03:00', '', '', '', '', '', '', 'mocha', './assets/emp_pics/defult.png', '', '', '', '2019-01-12T20:17:29+03:00'),
(104, 'Deen Mohammed Nazeer ', '1082', '2188112060', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '22/12/2002', 'no', '', 'no', '', '', 'terminat', '', '2019-01-12T20:23:15+03:00', '', '', '', '', '', '', 'mocha', './assets/emp_pics/defult.png', '', '', '', '2019-01-12T20:19:56+03:00'),
(105, 'Asad uz Zaman Mujeeb ', '1100', '2181548815', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '25/03/2003', 'no', '', 'no', '', '', 'terminat', 'final exit', '2019-01-12T21:06:09+03:00', '', '', '', '', '', '', 'mocha', './assets/emp_pics/defult.png', '', '', '', '2019-01-12T20:27:46+03:00'),
(106, 'Badar Ahmed Abdullah', '1126', '2202614356', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '28/01/2004', 'no', '', 'no', '', '', 'terminat', 'Sponsorship Transfer', '2019-01-12T21:10:26+03:00', '', '', '', '', '', '', 'mocha', './assets/emp_pics/defult.png', '', '', '', '2019-01-12T21:08:50+03:00'),
(107, 'Mainuddin Mustafa', '1145', '2207466000', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '08/08/2004', 'no', '', 'no', '', '', 'terminat', 'Final Exit', '2019-01-12T21:12:32+03:00', '', '', '', '', '', '', 'mocha', './assets/emp_pics/defult.png', '', '', '', '2019-01-12T21:10:50+03:00'),
(108, 'Hashim Rehajuddin', '1157', '2208531182', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '27/08/2004', 'no', '', 'no', '', '', 'terminat', 'Final Exit', '2019-01-12T21:19:20+03:00', '', '', '', '', '', '', 'mocha', './assets/emp_pics/defult.png', '', '', '', '2019-01-12T21:16:46+03:00'),
(109, 'Abdul Rahman Syed Alawi', '1209', '2237580663', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '03/07/2007', 'no', '', 'no', '', '', 'terminat', 'Final Exit', '2019-01-12T21:21:10+03:00', '', '', '', '', '', '', 'mocha', './assets/emp_pics/defult.png', '', '', '', '2019-01-12T21:20:01+03:00');
INSERT INTO `employees` (`id`, `name`, `emp_id`, `iqama`, `iqama_exp`, `iqama_exp_g`, `mobile`, `passport_number`, `passport_exp`, `email`, `g_number`, `dial_code`, `emg_mobile`, `emg_name`, `salary`, `dept`, `sectin_nme`, `emptype`, `country`, `vacation_days`, `joining_date`, `status`, `login_usr`, `fly`, `bank_name`, `iban`, `note`, `ter_note`, `ter_date`, `dob`, `vac_period`, `sex`, `blood_type`, `mar_status`, `t_shirt_size`, `emp_sup_type`, `avatar`, `address`, `insurance_no`, `insurance_exp`, `date_reg`) VALUES
(110, 'Nazeer Abdullah Abdo Ali', '1245', '2255443448', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '26/07/2008', 'no', '', 'no', '', '', 'terminat', 'Sponsorship Transfer', '2019-01-12T21:22:33+03:00', '', '', '', '', '', '', 'mocha', './assets/emp_pics/defult.png', '', '', '', '2019-01-12T21:21:34+03:00'),
(111, 'Irsadul Ibad Bn Ahmad Ismi', '1332', '2304892637', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '26/02/2011', 'no', '', 'no', '', '', 'terminat', 'Final Exit', '2019-01-12T21:23:59+03:00', '', '', '', '', '', '', 'mocha', './assets/emp_pics/defult.png', '', '', '', '2019-01-12T21:23:14+03:00'),
(112, 'Firos Thanikkal', '1350', '2318631005', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '25/06/2011', 'no', '', 'no', '', '', 'terminat', 'Final Exit', '2019-01-12T21:24:49+03:00', '', '', '', '', '', '', 'mocha', './assets/emp_pics/defult.png', '', '', '', '2019-01-12T21:24:15+03:00'),
(113, 'Mohammed Kaprakadan', '1373', '2309589253', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '19/06/2011', 'no', '', 'no', '', '', 'terminat', 'Final Exit', '2019-01-12T21:25:45+03:00', '', '', '', '', '', '', 'mocha', './assets/emp_pics/defult.png', '', '', '', '2019-01-12T21:25:11+03:00'),
(114, 'Mohammed Hassan Ba-Aqeel', '1382', '2058218567', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '14/02/2012', 'no', '', 'no', '', '', 'terminat', 'Transfer', '2019-01-12T21:27:07+03:00', '', '', '', '', '', '', 'mocha', './assets/emp_pics/defult.png', '', '', '', '2019-01-12T21:26:07+03:00'),
(115, 'Mohamed El Said Faraj', '1389', '2310485707', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '18/06/2012', 'no', '', 'no', '', '', 'terminat', 'Transfer', '2019-01-12T21:28:13+03:00', '', '', '', '', '', '', 'mocha', './assets/emp_pics/defult.png', '', '', '', '2019-01-12T21:27:31+03:00'),
(116, 'Yousuf Devakana', '1401', '2273537197', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '20/06/2013', 'no', '', 'no', '', '', 'terminat', 'Did not return from vacation', '2019-01-12T21:29:31+03:00', '', '', '', '', '', '', 'mocha', './assets/emp_pics/defult.png', '', '', '', '2019-01-12T21:28:34+03:00'),
(117, 'Rayed Fayez Marshad', '4514', '1086308952', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '29/10/2015', 'no', '', 'no', '', '', 'terminat', 'Resigned', '2019-01-12T21:30:56+03:00', '', '', '', '', '', '', 'mocha', './assets/emp_pics/defult.png', '', '', '', '2019-01-12T21:30:30+03:00'),
(118, 'Ahmed Mohammed Makdoom', '4515', '1093450748', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '10/07/2016', 'no', '', 'no', '', '', 'terminat', 'NONE', '2019-01-12T21:31:38+03:00', '', '', '', '', '', '', 'mocha', './assets/emp_pics/defult.png', '', '', '', '2019-01-12T21:31:17+03:00'),
(119, 'Abdul Rahman Halawani', '4516', '1091487569', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '12/07/2016', 'no', '', 'no', '', '', 'terminat', 'NONE', '2019-01-12T21:32:24+03:00', '', '', '', '', '', '', 'mocha', './assets/emp_pics/defult.png', '', '', '', '2019-01-12T21:31:57+03:00'),
(120, 'Ali Omar Ahmed Al Maashi', '4518', '1115541078', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '09/02/2017', 'no', '', 'no', '', '', 'terminat', 'Left', '2019-01-12T21:33:11+03:00', '', '', '', '', '', '', 'mocha', './assets/emp_pics/defult.png', '', '', '', '2019-01-12T21:32:48+03:00'),
(121, 'Talal Salem Dosh Al Harithy', '4520', '1080387226', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '09/02/2017', 'no', '', 'no', '', '', 'terminat', 'NONE', '2019-01-12T21:33:43+03:00', '', '', '', '', '', '', 'mocha', './assets/emp_pics/defult.png', '', '', '', '2019-01-12T21:33:30+03:00'),
(122, 'Louai Ahmed Adnan Fadan', '4521', '1090971621', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '01/05/2017', 'no', '', 'no', '', '', 'terminat', 'NONE', '2019-01-12T21:34:14+03:00', '', '', '', '', '', '', 'mocha', './assets/emp_pics/defult.png', '', '', '', '2019-01-12T21:33:58+03:00'),
(123, 'Hadeel Omar Ibrahim Al Tawash', '4423', '1089918823', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '06/08/2014', 'no', '', 'no', '', '', 'terminat', 'Resigned', '2019-01-12T21:35:11+03:00', '', '', '', '', '', '', 'mocha', './assets/emp_pics/defult.png', '', '', '', '2019-01-12T21:34:34+03:00'),
(124, 'Rasha Mohammed Saleh Al Omari', '4431', '0000000000', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '01/09/2014', 'no', '', 'no', '', '', 'terminat', 'NONE', '2019-01-12T21:35:58+03:00', '', '', '', '', '', '', 'mocha', './assets/emp_pics/defult.png', '', '', '', '2019-01-12T21:35:38+03:00'),
(125, 'Nahlah Ali Attiyah Al Harbi', '4435', '1072816521', '18/02/1443', '2021/09/26', '0535506154', '', '', 'nahlaaliharbi@hotmail.com', '', '', '0502727477', 'mother ', '4000', 'POS', 'JMUM 02', 'Supporter', 'Saudi Arabia', '30', '18/03/2015', 'active', '', 'no', 'Riyad Bank', 'SA5720000001252554699940', '', '', '', '06/10/1989', '1 Year', 'female', 'B+', 'single', 'xs 54', 'mocha', './assets/emp_pics/4435125Nahlah Ali Attiyah Al Harbi1594717861.png', 'alnuzha alshargia hisham bin hakim road ', '', '', '2019-01-12T21:37:39+03:00'),
(126, 'Wafa Hadi Mohammed Asiri', '4444', '1068946324', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '07/08/2016', 'no', '', 'no', '', '', 'terminat', 'NONE', '2019-01-12T21:38:40+03:00', '', '', '', '', '', '', 'mocha', './assets/emp_pics/defult.png', '', '', '', '2019-01-12T21:38:20+03:00'),
(127, 'Ghadi Abdulhakeem Abbas Muteer', '4445', '1083370369', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '14/08/2016', 'no', '', 'no', '', '', 'terminat', 'Resigned', '2019-01-12T21:39:34+03:00', '', '', '', '', '', '', 'mocha', './assets/emp_pics/defult.png', '', '', '', '2019-01-12T21:38:55+03:00'),
(128, 'Meshal Meshael Al Salmi', '4446', '1071632663', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '14/08/2016', 'no', '', 'no', '', '', 'terminat', 'NONE', '2019-01-12T21:40:33+03:00', '', '', '', '', '', '', 'mocha', './assets/emp_pics/defult.png', '', '', '', '2019-01-12T21:39:51+03:00'),
(129, 'Afnan Nadher', '4447', '1081001982', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '10/11/2016', 'no', '', 'no', '', '', 'terminat', 'NONE', '2019-01-12T21:41:10+03:00', '', '', '', '', '', '', 'mocha', './assets/emp_pics/defult.png', '', '', '', '2019-01-12T21:40:55+03:00'),
(130, 'Najood Mohammed Ali Al Zahrani', '4430', '1092501954', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '11/09/2017', 'no', '', 'no', '', '', 'terminat', 'NONE', '2019-01-12T21:41:48+03:00', '', '', '', '', '', '', 'mocha', './assets/emp_pics/defult.png', '', '', '', '2019-01-12T21:41:27+03:00'),
(131, 'El Hassan Anbi', '5', '2071912550', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '24/07/2002', 'no', '', 'no', '', '', 'terminat', 'Sponsorship Transfer', '2019-01-12T21:44:08+03:00', '', '', '', '', '', '', 'mocha', './assets/emp_pics/defult.png', '', '', '', '2019-01-12T21:42:11+03:00'),
(132, 'Montaser Farooq', '22', '2202825556', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '10/04/2004', 'no', '', 'no', '', '', 'terminat', 'Sponsorship Transfer', '2019-01-12T21:45:44+03:00', '', '', '', '', '', '', 'mocha', './assets/emp_pics/defult.png', '', '', '', '2019-01-12T21:44:31+03:00'),
(133, 'Mahfooz Zainuddin', '31', '2085471742', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '27/06/2006', 'no', '', 'no', '', '', 'terminat', 'Sponsorship Transfer', '2019-01-12T21:47:35+03:00', '', '', '', '', '', '', 'mocha', './assets/emp_pics/defult.png', '', '', '', '2019-01-12T21:46:05+03:00'),
(134, 'Fahad Manzoor Ahmed', '36', '2019884333', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '26/01/2007', 'no', '', 'no', '', '', 'terminat', 'Sponsorship Transfer', '2019-01-13T17:16:03+03:00', '', '', '', '', '', '', 'mocha', './assets/emp_pics/defult.png', '', '', '', '2019-01-13T17:13:48+03:00'),
(135, 'Karim Md. Samir Matar', '53', '2264172061', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '25/12/2009', 'no', '', 'no', '', '', 'terminat', 'Resigned and Transfer of Sponsorship', '2019-01-13T17:17:54+03:00', '', '', '', '', '', '', 'mocha', './assets/emp_pics/defult.png', '', '', '', '2019-01-13T17:16:39+03:00'),
(136, 'Mohammed Ali K.', '79', '2311036467', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '27/02/2012', 'no', '', 'no', '', '', 'terminat', 'Final Exit', '2019-01-13T17:18:52+03:00', '', '', '', '', '', '', 'mocha', './assets/emp_pics/defult.png', '', '', '', '2019-01-13T17:18:11+03:00'),
(137, 'Karam Abdul  Majeed', '106', '2259855266', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '01/06/2014', 'no', '', 'no', '', '', 'terminat', 'NONE', '2019-01-13T17:19:41+03:00', '', '', '', '', '', '', 'mocha', './assets/emp_pics/defult.png', '', '', '', '2019-01-13T17:19:10+03:00'),
(138, 'Ahsan Ishtiaq Amjad', '129', '2072676931', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '01/09/2015', 'no', '', 'no', '', '', 'terminat', 'Sponsorship Transfer', '2019-01-13T17:20:21+03:00', '', '', '', '', '', '', 'mocha', './assets/emp_pics/defult.png', '', '', '', '2019-01-13T17:19:58+03:00'),
(139, 'Mousa Ayedh Al Maliki', '137', '1010604385', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '05/10/2015', 'no', '', 'no', '', '', 'terminat', 'NONE', '2019-01-13T17:21:16+03:00', '', '', '', '', '', '', 'mocha', './assets/emp_pics/defult.png', '', '', '', '2019-01-13T17:20:49+03:00'),
(140, 'Fahad Abdulrahim Bashaikh', '144', '1102540513', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '18/09/2016', 'no', '', 'no', '', '', 'terminat', 'NONE', '2019-01-13T17:21:56+03:00', '', '', '', '', '', '', 'mocha', './assets/emp_pics/defult.png', '', '', '', '2019-01-13T17:21:35+03:00'),
(141, 'Abdulrahman Jalal Yaghmour', '145', '1098127259', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '09/10/2016', 'no', '', 'no', '', '', 'terminat', 'NONE', '2019-01-13T17:22:40+03:00', '', '', '', '', '', '', 'mocha', './assets/emp_pics/defult.png', '', '', '', '2019-01-13T17:22:17+03:00'),
(142, 'Omar Hisham Al Kudus', '146', '1038164099', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '23/10/2016', 'no', '', 'no', '', '', 'terminat', 'NONE', '2019-01-13T17:23:32+03:00', '', '', '', '', '', '', 'mocha', './assets/emp_pics/defult.png', '', '', '', '2019-01-13T17:23:02+03:00'),
(143, 'Turki Kasim AlAhdal', '150', '0000000000', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '18/03/2018', 'no', '', 'no', '', '', 'terminat', 'NONE', '2019-01-13T17:24:34+03:00', '', '', '', '', '', '', 'mocha', './assets/emp_pics/defult.png', '', '', '', '2019-01-13T17:24:18+03:00'),
(144, 'Jamal Khalid Ottraji', '151', '0000000000', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '23/04/2018', 'no', '', 'no', '', '', 'terminat', 'NONE', '2019-01-13T17:25:08+03:00', '', '', '', '', '', '', 'mocha', './assets/emp_pics/defult.png', '', '', '', '2019-01-13T17:24:59+03:00'),
(145, 'Mutaz Mohammed A. Mufti', '0154', '1065265918', '', '', '0545431431', '', '', '', '', '', '', '', '5000', 'HRD and Housing', 'Head Office', 'Supporter', 'Saudi Arabia', '30', '10/02/2019', 'no', '', 'no', 'The National Commercial Bank', 'SA9010000001357910000100', 'terminat', 'end of service ', '2019-10-15T10:20:20+03:00', '15/02/1990', '1 Year', 'male', '', 'single', '', 'mocha', './assets/emp_pics/1065265918.UserImageRender.jpg ', '', '', '', '2019-02-10T15:23:21+03:00'),
(146, 'Fawaz Abulfayaz Khalilur Rahman', '1383', '2016033694', '09/02/1442', '2020/09/27', '0558635568', '', '', '', '', '', '', '', '2000', 'Transportation', 'Store -Sugar', 'Supporter', 'Myanmar', '50', '08/05/2018', 'active', '', 'no', 'The National Commercial Bank', 'SA1410000016459348000105', '', '', '', '12/11/1982', '2 Years', 'male', '', 'married', '', 'mocha', './assets/emp_pics/1383146Fawaz Abulfayaz Khalilur Rahman1594716116.png', '', '', '', '2019-04-11T09:23:48+03:00'),
(147, 'Mohammed Bahanshal', '0155', '1108103720', '', '', '0556823000', '', '', '', '', '', '', '', '3500', 'Inspection', 'Head Office', 'Supporter', 'Saudi Arabia', '30', '09/04/2019', 'active', '', 'no', 'Bank AlJazira', 'SA2760100002180242863001', '', '', '', '15/02/1990', '1 Year', 'male', '', 'single', '', 'mocha', './assets/emp_pics/defult.png', '', '', '', '2019-04-11T10:39:04+03:00'),
(152, 'Mohammed Sohel', '4701', '', '', '', '0538497725', '', '', '', '', '', '', '', '2600', 'POS', 'JM 15', 'Supporter', 'Bangladesh', '', '17/12/2018', 'active', '', 'no', '', '', '', '', '', '', '', 'male', '', '', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T12:58:52+03:00'),
(153, 'M.Hassan Khursheed', '4703', '', '', '', '0576268390', '', '', '', '', '', '', '', '2600', 'POS', 'JM 21', '', '', '', '13/01/2019', 'active', '', 'no', '', '', '', '', '', '', '', 'male', '', '', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:03:43+03:00'),
(154, 'Rafiq Islam', '4704', '', '', '', '', '', '', '', '', '', '', '', '2,600', 'POS', 'JMUBT 02', '', '', '', '13/01/2019', 'active', '', 'no', '', '', '', '', '', '', '', 'male', '', '', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:06:01+03:00'),
(155, 'Ashraful Islam', '4705', '', '', '', '', '', '', '', '', '', '', '', '2600', 'POS', '', '', 'Jamaica', '', '13/01/2019', 'active', '', 'no', '', '', '', '', '', '', '', 'male', '', '', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:10:10+03:00'),
(156, 'Rasedullah', '4706', '', '', '', '', '', '', '', '', '', '', '', '2,600', 'POS', 'JMUM 02', '', '', '', '13/01/2019', 'active', '', 'no', '', '', '', '', '', '', '', 'male', '', '', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:11:33+03:00'),
(157, 'Moinul Hasan Riman', '4707', '', '', '', '', '', '', '', '', '', '', '', '2,600', 'POS', 'JMUM 04', '', '', '', '13/01/2019', 'active', '', 'no', '', '', '', '', '', '', '', 'male', '', '', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:12:01+03:00'),
(158, 'M. Ashikur Rahman', '4709', '', '', '', '', '', '', '', '', '', '', '', '2,600', 'POS', 'Head Office', '', '', '', '02/04/2019', 'active', '', 'no', '', '', '', '', '', '', '', 'male', '', '', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:12:40+03:00'),
(159, 'Kaiser Ahmed', '4710', '', '', '', '', '', '', '', '', '', '', '', '2,600', 'POS', 'JM 31', '', '', '', '02/04/2019', 'active', '', 'no', '', '', '', '', '', '', '', 'male', '', '', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:13:05+03:00'),
(160, 'M. Faruq Hussain', '4711', '', '', '', '', '', '', '', '', '', '', '', '2,600', 'POS', 'JM 08', '', '', '', '02/04/2019', 'active', '', 'no', '', '', '', '', '', '', '', 'male', '', '', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:13:24+03:00'),
(161, 'Miraz', '4712', '', '', '', '', '', '', '', '', '', '', '', '2,600', 'POS', 'JM 24', '', '', '', '16/02/2019', 'active', '', 'no', '', '', '', '', '', '', '', 'male', '', '', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:13:50+03:00'),
(162, 'Amdadul', '4713', '', '', '', '', '', '', '', '', '', '', '', '2,600', 'POS', 'JM 26', '', '', '', '16/02/2019', 'active', '', 'no', '', '', '', '', '', '', '', 'male', '', '', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:14:17+03:00'),
(163, 'Belayat', '4714', '', '', '', '0594314929', '', '', '', '', '', '', '', '2600', 'POS', 'JM 10', 'Supporter', 'Bangladesh', '', '16/02/2019', 'active', '', 'no', '', '', '', '', '', '', '', 'male', '', '', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:14:36+03:00'),
(164, 'Omer Faruk', '4715', '', '', '', '', '', '', '', '', '', '', '', '2,600', 'POS', 'JM 18', '', '', '', '18/03/2019', 'active', '', 'no', '', '', '', '', '', '', '', 'male', '', '', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:14:59+03:00'),
(165, 'Mamoon Babar Ali', '4716', '', '', '', '', '', '', '', '', '', '', '', '2,600', 'POS', 'MM 01', '', '', '', '14/03/2019', 'active', '', 'no', '', '', '', '', '', '', '', 'male', '', '', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:15:34+03:00'),
(166, 'Jobayer Shammi', '4717', '', '', '', '', '', '', '', '', '', '', '', '2,600', 'POS', 'JM 14', '', '', '', '26/03/2019', 'active', '', 'no', '', '', '', '', '', '', '', 'male', '', '', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:15:55+03:00'),
(167, 'Kamal Mobarok H', '4301', '2233522073', '15/02/1442', '2020/10/03', '0557490797', '', '', '', '', '', '', '', '3000', 'POS', 'JM 38', 'Supporter', 'Bangladesh', '', '11/06/2017', 'active', '', 'no', '', '', '', '', '', '15/10/1981', '', 'male', '', 'single', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:24:23+03:00'),
(168, 'Moynal Hossain', '4302', '2314012200', '18/12/1442', '2021/07/28', '0538977956', '', '', '', '', '', '', '', '3000', 'POS', 'JM 28', 'Supporter', 'Bangladesh', '', '23/02/2015', 'active', '', 'no', '', '', '', '', '', '05/07/1986', '', 'male', '', 'single', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:26:08+03:00'),
(169, 'Md Sohid Ali Akbar', '4303', '2320964733', '09/03/1441', '2019/11/07', '0573031017', '', '', '', '', '', '', '', '3000', 'POS', 'JM 24', 'Supporter', 'Bangladesh', '', '23/02/2015', 'active', '', 'no', '', '', '', '', '', '29/11/1986', '', 'male', '', 'single', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:28:11+03:00'),
(170, 'Ramjan Maih', '4305', '2314012713', '18/12/1441', '2020/08/08', '0570545707', '', '', '', '', '', '', '', '3000', 'POS', 'JM 26', 'Supporter', 'Bangladesh', '', '23/09/2016', 'active', '', 'no', '', '', '', '', '', '12/06/1982', '', 'male', '', 'single', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:29:00+03:00'),
(171, 'Eliyas Abdul Kader', '4306', '2227746936', '12/12/1442', '2021/07/22', '0571873694', '', '', '', '', '', '', '', '3000', 'POS', 'YM 01', 'Supporter', 'Bangladesh', '', '23/02/2015', 'active', '', 'no', '', '', '', '', '', '02/01/1982', '', 'male', '', 'single', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:29:19+03:00'),
(172, 'Sulaiman Zinna', '4307', '2218566780', '01/11/1441', '2020/06/22', '0530043395', '', '', '', '', '', '', '', '3000', 'POS', 'JM 15', 'Supporter', 'Bangladesh', '', '12/10/2017', 'active', '', 'no', '', '', '', '', '', '15/06/1981', '', 'male', '', 'single', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:29:40+03:00'),
(173, 'Milon Mohammed', '4310', '2148908946', '15/12/1441', '2020/08/05', '0572531229', '', '', '', '', '', '', '', '3000', 'POS', 'JM 23', 'Supporter', 'Bangladesh', '', '23/02/2015', 'active', '', 'no', '', '', '', '', '', '01/01/1981', '', 'male', '', 'single', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:30:01+03:00'),
(174, 'Jasim Abdus Salam', '4311', '2235267214', '07/05/1442', '2020/12/22', '0559083244', '', '', '', '', '', '', '', '3000', 'POS', 'JM 29', 'Supporter', 'Bangladesh', '', '23/02/2015', 'active', '', 'no', '', '', '', '', '', '01/01/1981', '', 'male', '', 'single', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:30:25+03:00'),
(175, 'Shah Poran Md', '4314', '2314012101', '18/12/1442', '2021/07/28', '0532093705', '', '', '', '', '', '', '', '3000', 'POS', 'YM 02', 'Supporter', 'Bangladesh', '', '23/02/2015', 'active', '', 'no', '', '', '', '', '', '01/01/1983', '', 'male', '', 'single', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:31:05+03:00'),
(176, 'Miohammed Zahir', '4315', '2218526214', '24/11/1442', '2021/07/04', '0531135176', '', '', '', '', '', '', '', '3000', 'POS', 'JM 39', 'Supporter', 'Bangladesh', '', '23/02/2015', 'active', '', 'no', '', '', '', '', '', '01/01/1982', '', 'male', '', 'single', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:31:25+03:00'),
(177, 'Rezaul Karim', '4316', '2320860972', '16/01/1442', '2020/09/04', '0534026692', '', '', '', '', '', '', '', '3000', 'POS', 'MM 03', 'Supporter', 'Bangladesh', '', '23/02/2015', 'active', '', 'no', '', '', '', '', '', '01/01/1990', '', 'male', '', 'single', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:31:49+03:00'),
(178, 'Shaikat Rayan Ali', '4318', '2320860667', '16/01/1442', '2020/09/04', '0552872618', '', '', '', '', '', '', '', '3000', 'POS', 'JM 33', 'Supporter', 'Bangladesh', '', '23/02/2015', 'active', '', 'no', '', '', '', '', '', '01/01/1984', '', 'male', '', 'single', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:32:02+03:00'),
(179, 'Usman Goni Abdu Salam', '4319', '2233523568', '15/02/1442', '2020/10/03', '0571437080', '', '', '', '', '', '', '', '3000', 'POS', 'JM 22', 'Supporter', 'Bangladesh', '', '24/06/2016', 'active', '', 'no', '', '', '', '', '', '20/03/1984', '', 'male', '', 'single', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:32:15+03:00'),
(180, 'Md. Rabiul Islam', '4325', '2227746555', '11/11/1442', '2021/06/21', '0538660261', '', '', '', '', '', '', '', '3000', 'POS', 'JM 14', 'Supporter', 'Bangladesh', '', '22/03/2015', 'active', '', 'no', '', '', '', '', '', '03/01/1981', '', 'male', '', 'single', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:32:30+03:00'),
(181, 'Akbar Ali Ansar', '4326', '2236881088', '06/06/1442', '2021/01/20', '0552383042', '', '', '', '', '', '', '', '3000', 'POS', 'JM 16', 'Supporter', 'Bangladesh', '', '22/03/2015', 'active', '', 'no', '', '', '', '', '', '', '', 'male', '', 'single', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:32:48+03:00'),
(182, 'Abdul Motin Mustafa', '4328', '', '', '', '0570098323', '', '', '', '', '', '', '', '3000', 'POS', 'JM 24', 'Supporter', 'Bangladesh', '', '11/06/2018', 'active', '', 'no', '', '', '', '', '', '12/04/1988', '', 'male', '', 'single', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:33:00+03:00'),
(183, 'Jasim Uddin Rohis Miah', '4329', '2233449251', '15/02/1441', '2019/10/15', '0502240500', '', '', '', '', '', '', '', '3000', 'POS', 'JM 23', 'Supporter', 'Bangladesh', '', '29/08/2015', 'active', '', 'no', '', '', '', '', '', '25/06/1972', '', 'male', '', 'single', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:33:12+03:00'),
(184, 'Khairul Miah', '4338', '2236885915', '18/04/1443', '2021/11/24', '0554480473', '', '', '', '', '', '', '', '3000', 'POS', 'JMUM 05', 'Supporter', 'Bangladesh', '', '13/07/2018', 'active', '', 'no', '', '', '', '', '', '02/02/1977', '', 'male', '', 'single', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:33:35+03:00'),
(185, 'M.Abul Khair', '4343', '2227746407', '11/11/1442', '2021/06/21', '0534552238', '', '', '', '', '', '', '', '3000', 'POS', 'JM 17', 'Supporter', 'Bangladesh', '', '17/09/2015', 'active', '', 'no', '', '', '', '', '', '', '', 'male', '', 'single', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:33:48+03:00'),
(186, 'Mekdam Saiks Makul ', '4344', '2397236221', '06/04/1442', '2020/11/22', '0539440768', '', '', '', '', '', '', '', '3000', 'POS', 'JMUBT 02 ', 'Supporter', 'India', '', '20/10/2015', 'active', '', 'no', '', '', '', '', '', '29/12/1991', '', 'male', '', 'single', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:34:10+03:00'),
(187, 'M.Asad Noor ', '4346', '2397981875', '20/04/1442', '2020/12/06', '0567731645', '', '', '', '', '', '', '', '3000', 'POS', 'MM 01', 'Supporter', 'India', '', '04/11/2015', 'active', '', 'no', '', '', '', '', '', '09/04/1991', '', 'male', '', 'single', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:34:31+03:00'),
(188, 'Khurshid Alam', '4351', '2398217949', '22/04/1442', '2020/12/08', '0562146099', '', '', '', '', '', '', '', '3000', 'POS', 'JM 34', 'Supporter', 'India', '', '05/11/2015', 'active', '', 'no', '', '', '', '', '', '21/12/1989', '', 'male', '', 'single', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:34:47+03:00'),
(189, 'Minar Hussain', '4355', '2399196332', '07/05/1442', '2020/12/22', '0572637469', '', '', '', '', '', '', '', '3000', 'POS', 'JM 17', 'Supporter', 'India', '', '20/11/2015', 'active', '', 'no', '', '', '', '', '', '', '', 'male', '', 'single', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:35:03+03:00'),
(190, 'Atiqur Rahamn', '4368', '2306115466', '07/07/1442', '2021/02/19', '0571836013', '', '', '', '', '', '', '', '3000', 'POS', 'JM 20', 'Supporter', 'India', '', '23/12/2015', 'active', '', 'no', '', '', '', '', '', '01/01/1988', '', 'male', '', 'single', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:35:18+03:00'),
(191, 'Kazi Tanzim Hasan', '4372', '2422341509', '07/04/1442', '2020/11/23', '0571163268', '', '', '', '', '', '', '', '3000', 'POS', 'YUM 01', 'Supporter', 'Bangladesh', '', '21/11/2016', 'active', '', 'no', '', '', '', '', '', '10/08/1990', '', 'male', '', 'single', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:37:12+03:00'),
(192, 'Nazrul Korom', '4373', '2423454954', '22/04/1442', '2020/12/08', '0572052987', '', '', '', '', '', '', '', '3000', 'POS', 'YM 03', 'Supporter', 'Bangladesh', '', '21/11/2016', 'active', '', 'no', '', '', '', '', '', '01/01/1990', '', 'male', '', 'single', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:37:34+03:00'),
(193, 'Abu Musa', '4374', '', '', '', '0572929843', '', '', '', '', '', '', '', '3000', 'POS', 'YM 01', 'Supporter', 'Bangladesh', '', '16/07/2017', 'active', '', 'no', '', '', '', '', '', '02/01/1980', '', 'male', '', 'single', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:37:48+03:00'),
(194, 'Mamoon A.Malik', '4375', '2235700214', '28/05/1442', '2021/01/12', '0571546922', '', '', '', '', '', '', '', '3000', 'POS', 'JM 28', 'Supporter', 'Bangladesh', '', '12/09/2017', 'active', '', 'no', '', '', '', '', '', '01/02/1980', '', 'male', '', 'single', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:38:03+03:00'),
(195, 'Tarqol Islam', '4376', '', '', '', '0508762859', '', '', '', '', '', '', '', '3000', 'POS', 'JM 06', 'Supporter', 'Bangladesh', '', '17/09/2017', 'active', '', 'no', '', '', '', '', '', '', '', 'male', '', '', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:38:18+03:00'),
(196, 'M.Shahadat', '4378', '', '', '', '0571945303', '', '', '', '', '', '', '', '3000', 'POS', 'JM 10', 'Supporter', 'Bangladesh', '', '07/10/2017', 'active', '', 'no', '', '', '', '', '', '', '', 'male', '', '', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:38:29+03:00'),
(197, 'M.Momen Mian', '4379', '', '', '', '0544358198', '', '', '', '', '', '', '', '3000', 'POS', 'JMUM 04', 'Supporter', 'Bangladesh', '', '07/10/2017', 'active', '', 'no', '', '', '', '', '', '10/12/1990', '', 'male', '', 'single', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:38:48+03:00'),
(198, 'Maarof Aosin', '4380', '', '', '', '0572813968', '', '', '', '', '', '', '', '3000', 'POS', 'MM 02', 'Supporter', 'Bangladesh', '', '09/11/2017', 'active', '', 'no', '', '', '', '', '', '', '', 'male', '', 'single', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:39:01+03:00'),
(199, 'Mohammed Sharif', '4382', '', '', '', '0550171098', '', '', '', '', '', '', '', '3000', 'POS', 'YM 02', 'Supporter', 'Bangladesh', '', '08/02/2018', 'active', '', 'no', '', '', '', '', '', '01/01/1990', '', 'male', '', 'single', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:39:14+03:00'),
(200, 'Nazrul  Islam ', '4383', '', '', '', '0572251299', '', '', '', '', '', '', '', '3000', 'POS', 'JM 01', 'Supporter', 'Bangladesh', '', '08/02/2018', 'active', '', 'no', '', '', '', '', '', '01/01/1990', '', 'male', '', 'single', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:40:00+03:00'),
(201, 'Masum Billah', '4384', '2438101848', '20/01/1442', '2020/09/08', '0504226560', '', '', '', '', '', '', '', '3000', 'POS', 'MM 02', 'Supporter', 'Bangladesh', '', '10/04/2018', 'active', '', 'no', '', '', '', '', '', '01/01/1998', '', 'male', '', 'single', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:40:53+03:00'),
(202, 'Razim Molla', '4385', '', '', '', '0572302981', '', '', '', '', '', '', '', '3000', 'POS', 'JM 14', 'Supporter', 'Bangladesh', '', '14/06/2018', 'active', '', 'no', '', '', '', '', '', '', '', 'male', '', 'single', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:41:08+03:00'),
(203, 'Abdul Salam Ansari', '4386', '', '', '', '0572671676', '', '', '', '', '', '', '', '3000', 'POS', 'JM 34', 'Supporter', 'India', '', '13/07/2018', 'active', '', 'no', '', '', '', '', '', '09/06/1988', '', 'male', '', 'single', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:41:26+03:00'),
(204, 'M. Rakib Hossain', '4387', '2444635169', '29/05/1442', '2021/01/13', '0581273296', '', '', '', '', '', '', '', '3000', 'POS', 'JM 18', 'Supporter', 'Bangladesh', '', '01/08/2018', 'active', '', 'no', '', '', '', '', '', '01/01/1990', '', 'male', '', 'single', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:41:37+03:00'),
(205, 'Kawsar Bepary', '4388', '2443652637', '13/05/1442', '2020/12/28', '0571729506', '', '', '', '', '', '', '', '3000', 'POS', 'JM 18', 'Supporter', 'India', '', '18/08/2018', 'active', '', 'no', '', '', '', '', '', '01/01/1990', '', 'male', '', 'single', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:41:53+03:00'),
(206, 'Md. Sagor Ahammed', '4389', '2446396299', '28/06/1442', '2021/02/11', '0572270452', '', '', '', '', '', '', '', '3000', 'POS', 'JMUBT 03', 'Supporter', 'Bangladesh', '', '10/09/2018', 'active', '', 'no', '', '', '', '', '', '', '', 'male', '', 'single', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:42:16+03:00'),
(207, 'Jowel Rana', '4390', '2313142412', '09/03/1442', '2020/10/26', '0533468756', '', '', '', '', '', '', '', '3000', 'POS', 'JM 08', 'Supporter', 'Bangladesh', '', '10/09/2018', 'active', '', 'no', '', '', '', '', '', '20/02/1989', '', 'male', '', 'single', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:42:26+03:00'),
(208, 'Alauddin Sukur', '4391', '2408947725', '07/11/1442', '2021/06/17', '0531958425', '', '', '', '', '', '', '', '3000', 'POS', 'MM 03', 'Supporter', 'India', '', '18/09/2018', 'active', '', 'no', '', '', '', '', '', '01/08/1989', '', 'male', '', 'single', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:43:10+03:00'),
(209, 'Shahjahan Mridha', '4398', '', '', '', '', '', '', '', '', '', '', '', '3,000', 'Production ', 'Coffee Factory', '', '', '', '10/08/2017', 'active', '', 'no', '', '', '', '', '', '', '', 'male', '', '', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:43:58+03:00'),
(210, 'Abdul Rahim', '4377', '', '', '', '', '', '', '', '', '', '', '', '1,200', 'POS', 'MM 01', '', '', '', '07/10/2017', 'active', '', 'no', '', '', '', '', '', '', '', 'male', '', '', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:45:52+03:00'),
(211, 'Nurull Islam Joyal', '4352', '2398507265', '28/04/1442', '2020/12/14', '0507507639', '', '', '', '', '', '', '', '2100', 'POS', 'MM 01', 'Supporter', 'India', '', '11/11/2015', 'active', '', 'no', '', '', '', '', '', '12/03/1989', '', 'male', '', 'single', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:46:58+03:00'),
(212, 'Habib Abdul Hashim', '4371', '2233527395', '11/02/1442', '2020/09/29', '0580435921', '', '', '', '', '', '', '', '2100', 'POS', 'YM 01', 'Supporter', 'Bangladesh', '', '17/11/2016', 'active', '', 'no', '', '', '', '', '', '05/06/1974', '', 'male', '', 'single', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:47:39+03:00'),
(213, 'Ujjal Miah Shahlam', '4309', '2313064665', '09/03/1442', '2020/10/26', '0540280518', '', '', '', '', '', '', '', '2300', 'POS', 'JM 26', 'Supporter', 'Bangladesh', '', '23/02/2015', 'active', '', 'no', '', '', '', '', '', '', '', 'male', '', '', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:48:56+03:00'),
(214, 'M. Masood Ur A.Manan', '4313', '2313063121', '09/03/1442', '2020/10/26', '0532191965', '', '', '', '', '', '', '', '2300', 'POS', 'JM 06', 'Supporter', 'Bangladesh', '', '23/02/2015', 'active', '', 'no', '', '', '', '', '', '', '', 'male', '', '', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:49:16+03:00'),
(215, 'Azhar Ali Jamali', '4396', '2339667673', '29/04/1442', '2020/12/15', '0591619540', '', '', '', '', '', '', '', '4100', 'Transportation', 'Head Office', 'Supporter', 'Pakistan', '60', '11/03/2018', 'active', '', 'no', '', '', '', '', '', '01/01/1990', '2 Years', 'male', '', 'married', '', 'man_power', './assets/emp_pics/4396215Azhar Ali Jamali1597301286.png', '', '', '', '2019-04-30T13:52:39+03:00'),
(216, 'Asif Khan', '4397', '2430342366', '', '', '0572821005', '', '', '', '', '', '', '', '4100', 'Transportation', 'Head Office', 'Supporter', 'India', '60', '24/05/2017', 'no', '', 'no', '', '', 'terminat', 'termiinat', '2020-08-12T11:31:17+03:00', '01/02/1993', '2 Years', 'male', '', 'single', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:53:39+03:00'),
(217, 'Ravinder Yadav', '4399', '2430342275', '08/12/1442', '2021/07/18', '0573078902', '', '', '', '', '', '', '', '4100', 'Transportation', 'Head Office', 'Supporter', 'India', '60', '07/11/2017', 'active', '', 'no', '', '', '', '', '', '01/07/1978', '', 'male', '', '', '', 'man_power', './assets/emp_pics/defult.png', '', '', '', '2019-04-30T13:54:05+03:00'),
(219, 'Feras Hatim Felemban', '4524', '1106922956', '', '', '0551304070', '', '', '', '', '', '', '', '3500', 'POS', 'MM 05', 'Supporter', 'Saudi Arabia', '30', '27/05/2019', 'active', '', 'no', 'The National Commercial Bank', 'SA3710000001300000326807', '', '', '', '28/02/2000', '1 Year', 'male', '', 'single', '', 'mocha', './assets/emp_pics/4524219Feras Hatim Felemban1594717929.png', '', '', '', '2019-05-27T14:54:35+03:00'),
(220, 'Ummat Ali Usman Ali', '1325', '2338982057', '', '', '0570053682', '', '', '', '', '', '', '', '2,500', 'Maintenance', '', 'Supporter', 'India', '30', '20/05/2019', 'no', '', 'no', 'The National Commercial Bank', 'SA45 1000 0018 1000 0080 1802', 'terminat', 'Transfer to another company', '2020-07-14T11:19:09+03:00', '01/01/1984', '1 Year', 'male', '', 'single', '', 'mocha', './assets/emp_pics/2338982057.Photo.jpg ', '', '', '', '2019-06-23T14:14:43+03:00'),
(221, 'Azam Esam Mohammed Daly', '4525', '1108228238', '', '', '0565245897', '', '', '', '', '', '', '', '4000', 'POS', 'MM 01', 'Supporter', 'Saudi Arabia', '30', '24/07/2019', 'active', '', 'yes', 'The National Commercial Bank', 'SA0410000000300000716906', '', '', '', '10/01/2000', '1 Year', 'male', '', 'single', '', 'mocha', './assets/emp_pics/4525221Azam Esam Mohammed Daly1594717960.png', '', '', '', '2019-09-09T15:18:52+03:00'),
(227, 'Ishraq Fouad Mohammed Shaker ', '4627', '1073200089', '03/01/1445', '2023/07/21', '0566984440', '-', '07/07/2020', 'sh00.sh0090@live.com', '', '', '0568121672', 'husband mostafaa', '4000', 'Administration', 'Head Office', 'Supporter', 'Saudi Arabia', '30', '06/11/2019', 'no', '', 'no', 'Al Rajhi Bank', 'SA6680000443608010287808', 'terminat', '02-11-2020', '2020-11-03T15:59:46+03:00', '28/05/1990', '1 Year', 'female', 'B+', 'married', 'M', 'mocha', './assets/emp_pics/4627227Ishraq Fouad Mohammed Shaker 1594714121.png', 'mahamdiyah 2 - alhakem al abassi street -', '0', '07/07/2020', '2019-12-10T10:28:05+03:00'),
(224, 'Daniah Mohammed Sadeeq Abushoshah', '0156', '1066927987', '15/05/1443', '2021/12/20', '0565335759', 'R831685', '08/12/2020', 'daniah@mochachino.co', '', '', '0502859315', '0506386051', '7500', 'HRD and Housing', 'Head Office', 'Manager', 'Saudi Arabia', '30', '01/09/2019', 'active', '', 'no', 'The National Commercial Bank', 'SA3210000014180878000101', '', '', '', '21/11/1988', '1 Year', 'female', 'B+', 'single', 'small', 'mocha', './assets/emp_pics/0156224Daniah Mohammed Sadeeq Abushoshah1594714358.png', 'alfalaki road villa 24', '18941432', '12/01/2021', '2019-09-30T12:01:13+03:00'),
(225, 'Abeer Hassan Al Gamdi', '0157', '1068416096', '__/__/1___', '', '0546958066', '-', '-', 'abeer@mochachino.com', '', '', '0542199308', '', '6075', 'HRD and Housing', 'Head Office', 'Supporter', 'Saudi Arabia', '30', '08/09/2019', 'no', '', 'no', 'Al Rajhi Bank', 'SA3480000314608010602408', 'terminat', '31/11/2020', '2020-11-03T15:59:19+03:00', '28/10/1989', '1 Year', 'female', 'B+', 'single', 'M', 'mocha', './assets/emp_pics/defultFemale.jpg', 'balbeed road villa 8913', '0', '13/01/2021', '2019-09-30T12:07:37+03:00'),
(226, 'Azam Ali Mohammed Imtiaz ', '4630', '2199014248', '02/07/1442', '2021/02/14', '0571157348', 'P2975702', '02/02/2022', 'azam@mochachino.co', '', '', '0572680687', '', '3000', 'Finance', 'Head Office', 'Supporter', 'India', '60', '01/11/2019', 'active', '', 'no', 'Arab National Bank', 'SA6530400108071849930019', '', '', '', '14/09/1999', '2 Years', 'male', 'B+', 'single', 'xl ', 'mocha', './assets/emp_pics/4630226Azam Ali Mohammed Imtiaz 1594714224.png', '3156 alfarsi -ash sharafiyah dist - jeddah 23216-6584', '', '', '2019-12-02T16:24:39+03:00'),
(228, 'Omran Talal Saqati', '4632', '1113575128', '13/07/1442', '2021/02/25', '0507989152', '0', '13/07/2020', 'emran6e6@gmail.com', '', '', '0508206731', '', '4000', 'POS', 'JM 21', 'Supporter', 'Saudi Arabia', '30', '03/12/2019', 'active', '', 'no', 'Al Rajhi Bank', 'SA6480000598608016000365', '', '', '', '28/08/2001', '1 Year', 'male', 'B+', 'single', 's', 'mocha', './assets/emp_pics/defult.png', 'alhamdaniy ', '0', '13/01/2021', '2019-12-11T15:12:15+03:00'),
(229, 'Abdulaziz Adeel Thabet ', '4631', '1114040213', '', '', '0590266822', '', '', '', '', '', '', '', '4,000', 'POS', '', 'Supporter', 'Saudi Arabia', '30', '20/11/2019', 'active', '', 'no', 'The National Commercial Bank', 'SA75 1000 0015 4000 0016 7502', '', '', '', '14/02/2001', '1 Year', 'male', '', 'single', '', 'mocha', './assets/emp_pics/defult.png', '', '', '', '2019-12-11T15:27:55+03:00'),
(230, 'Khalid Mohammed Rara', '4628', '1105594566', '', '', '0566200309', '', '', '', '', '', '', '', '4,000', 'POS', '', 'Supporter', 'Saudi Arabia', '30', '17/11/2019', 'no', '', 'no', 'The National Commercial Bank', 'SA88 1000 0015 5000 0011 4010', 'terminat', 'termiinat', '2020-03-19T12:56:58+03:00', '20/09/1999', '1 Year', 'male', '', 'single', '', 'mocha', './assets/emp_pics/defult.png', '', '', '', '2019-12-11T15:35:40+03:00'),
(235, 'Huda Mohammed Essam Medher ', '4635', '1114247735', '08/06/1443', '2022/01/12', '0532057070', '0', '26/07/2020', 'hudame.3@gmail.com', '', '', '0505614919', 'Mohammed Essam Medher', '4000', 'Purchase', 'Head Office', 'Supporter', 'Saudi Arabia', '30', '14/07/2020', 'active', '', 'no', 'The National Commercial Bank', 'SA6910000011400000850108', '', '', '', '27/11/2001', '1 Year', 'female', 'A+', 'single', 'M', 'mocha', './assets/emp_pics/4635235huda mohammed essam medher 1595755138.png', 'Abdul Rahman Ibn Abi Bakr As Sedik- albasateen -building 7351', '0', '26/07/2020', '2020-07-26T12:16:27+03:00'),
(236, 'Ebtihal adnan sait ', '4636', '1054934318', '10/09/1443', '2022/04/12', '0562234423', '', '', 'xebtihal88x@hotmail.com', '', '', '0555616985', 'adnan sait', '4504', 'Purchase', 'Head Office', 'Supporter', 'Saudi Arabia', '30', '02/09/2020', 'active', '', 'no', 'Samba Financial Group', 'SA6740000000002810224609', '', '', '', '16/06/1988', '1 Year', 'female', 'O+', 'single', 'M', 'mocha', './assets/emp_pics/defultFemale.jpg', 'obhur alshamalya', '', '', '2020-09-02T10:54:05+03:00'),
(237, 'ammar shaker', '4637', '1096545635', '02/11/1443', '2022/06/01', '0561717975', '', '', 'ammar.shaker.1@hotmail.com', '', '', '0566984440', 'ishraq shaker', '4000', 'POS', 'MM 01', 'Supporter', 'Saudi Arabia', '30', '02/09/2020', 'active', '', 'no', 'The National Commercial Bank', 'SA20 1000 0000 5747 7500 0102', '', '', '', '23/12/1995', '1 Year', 'male', 'AB+', 'single', 'M', 'mocha', './assets/emp_pics/defult.png', 'abdullah oraif makkah', '', '', '2020-09-02T14:51:59+03:00');

-- --------------------------------------------------------

--
-- Table structure for table `emp_docu`
--

CREATE TABLE IF NOT EXISTS `emp_docu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `emp_id` varchar(255) NOT NULL,
  `pgid` int(11) NOT NULL,
  `docu_typ` varchar(100) NOT NULL,
  `docu_ext` varchar(10) NOT NULL,
  `attachment` varchar(255) NOT NULL,
  `date_reg` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=142 ;

--
-- Dumping data for table `emp_docu`
--

INSERT INTO `emp_docu` (`id`, `emp_id`, `pgid`, `docu_typ`, `docu_ext`, `attachment`, `date_reg`) VALUES
(1, '49', 88, 'Iqama', 'jpg', './assets/emp_documents/49_2016697217_Iqama_1556021263.jpg', '2019-04-23T15:07:43+03:00'),
(8, '9', 82, 'Iqama', 'jpg', './assets/emp_documents/9_2183975982_Iqama_20190611155700.jpg', '2019-06-11T15:57:00+03:00'),
(3, '152', 98, 'Passport-1', 'jpg', './assets/emp_documents/152_2337318717_Passport-1_1556106712.jpg', '2019-04-24T14:51:52+03:00'),
(9, '9', 82, 'Passport-1', 'jpg', './assets/emp_documents/9_2183975982_Passport-1_20190611155730.jpg', '2019-06-11T15:57:30+03:00'),
(7, '152', 98, 'Iqama', 'jpg', './assets/emp_documents/152_2337318717_Iqama_20190428141511.jpg', '2019-04-28T14:15:11+03:00'),
(10, '9', 82, 'Passport-2', 'jpg', './assets/emp_documents/9_2183975982_Passport-2_20190611155753.jpg', '2019-06-11T15:57:53+03:00'),
(11, '9', 82, 'Passport-2', 'jpg', './assets/emp_documents/9_2183975982_Passport-2_20190611155903.jpg', '2019-06-11T15:59:03+03:00'),
(12, '1198', 39, 'Iqama', 'jpg', './assets/emp_documents/1198_2166375507_Iqama_20190611160156.jpg', '2019-06-11T16:01:56+03:00'),
(13, '1198', 39, 'Passport-1', 'jpg', './assets/emp_documents/1198_2166375507_Passport-1_20190611160214.jpg', '2019-06-11T16:02:14+03:00'),
(14, '1119', 30, 'Iqama', 'jpg', './assets/emp_documents/1119_2181986023_Iqama_20190611160313.jpg', '2019-06-11T16:03:13+03:00'),
(15, '1119', 30, 'Passport-1', 'jpg', './assets/emp_documents/1119_2181986023_Passport-1_20190611160337.jpg', '2019-06-11T16:03:37+03:00'),
(16, '10', 83, 'Iqama', 'jpg', './assets/emp_documents/10_2182274023_Iqama_20190611160455.jpg', '2019-06-11T16:04:55+03:00'),
(17, '10', 83, 'Passport-1', 'jpg', './assets/emp_documents/10_2182274023_Passport-1_20190611160511.jpg', '2019-06-11T16:05:11+03:00'),
(18, '10', 83, 'Passport-2', 'jpg', './assets/emp_documents/10_2182274023_Passport-2_20190611160526.jpg', '2019-06-11T16:05:26+03:00'),
(19, '1002', 1, 'Iqama', 'jpg', './assets/emp_documents/1002_2111103756_Iqama_20190611160702.jpg', '2019-06-11T16:07:02+03:00'),
(20, '1002', 1, 'Passport-1', 'jpg', './assets/emp_documents/1002_2111103756_Passport-1_20190611160728.jpg', '2019-06-11T16:07:28+03:00'),
(23, '1411', 60, 'Iqama', 'jpg', './assets/emp_documents/1411_2125035200_Iqama_20190613101245.jpg', '2019-06-13T10:12:45+03:00'),
(22, '1411', 60, 'Passport-1', 'jpg', './assets/emp_documents/1411_2125035200_Passport-1_20190611161544.jpg', '2019-06-11T16:15:44+03:00'),
(24, '1011', 6, 'Iqama', 'jpg', './assets/emp_documents/1011_2157965142_Iqama_20190613112923.jpg', '2019-06-13T11:29:23+03:00'),
(25, '1011', 6, 'Passport-1', 'jpg', './assets/emp_documents/1011_2157965142_Passport-1_20190613112944.jpg', '2019-06-13T11:29:44+03:00'),
(26, '1020', 7, 'Iqama', 'jpg', './assets/emp_documents/1020_2175359427_Iqama_20190613113152.jpg', '2019-06-13T11:31:52+03:00'),
(27, '1020', 7, 'Passport-1', 'jpg', './assets/emp_documents/1020_2175359427_Passport-1_20190613113214.jpg', '2019-06-13T11:32:14+03:00'),
(28, '1020', 7, 'Passport-2', 'jpg', './assets/emp_documents/1020_2175359427_Passport-2_20190613113234.jpg', '2019-06-13T11:32:34+03:00'),
(29, '1021', 8, 'Iqama', 'jpg', './assets/emp_documents/1021_2176291157_Iqama_20190613113331.jpg', '2019-06-13T11:33:31+03:00'),
(30, '1021', 8, 'Passport-1', 'jpg', './assets/emp_documents/1021_2176291157_Passport-1_20190613113347.jpg', '2019-06-13T11:33:47+03:00'),
(31, '1021', 8, 'Passport-2', 'jpg', './assets/emp_documents/1021_2176291157_Passport-2_20190613113403.jpg', '2019-06-13T11:34:03+03:00'),
(32, '1021', 8, 'Passport-3', 'jpg', './assets/emp_documents/1021_2176291157_Passport-3_20190613113418.jpg', '2019-06-13T11:34:18+03:00'),
(33, '1084', 22, 'Iqama', 'jpg', './assets/emp_documents/1084_2184638415_Iqama_20190613113534.jpg', '2019-06-13T11:35:34+03:00'),
(34, '1084', 22, 'Passport-1', 'jpg', './assets/emp_documents/1084_2184638415_Passport-1_20190613113551.jpg', '2019-06-13T11:35:51+03:00'),
(35, '1062', 16, 'Iqama', 'jpg', './assets/emp_documents/1062_2174356630_Iqama_20190613113651.jpg', '2019-06-13T11:36:51+03:00'),
(36, '1062', 16, 'Passport-1', 'jpg', './assets/emp_documents/1062_2174356630_Passport-1_20190613113708.jpg', '2019-06-13T11:37:08+03:00'),
(37, '1325', 220, 'Iqama', 'jpg', './assets/emp_documents/1325_2338982057_Iqama_20190623141605.jpg', '2019-06-23T14:16:05+03:00'),
(38, '1007', 4, 'Iqama', 'jpg', './assets/emp_documents/1007_2132481017_Iqama_20190625085141.jpg', '2019-06-25T08:51:41+03:00'),
(39, '1007', 4, 'Passport-1', 'jpg', './assets/emp_documents/1007_2132481017_Passport-1_20190625085211.jpg', '2019-06-25T08:52:11+03:00'),
(40, '1007', 4, 'Passport-1', 'jpg', './assets/emp_documents/1007_2132481017_Passport-1_20190625085243.jpg', '2019-06-25T08:52:43+03:00'),
(41, '1009', 5, 'Iqama', 'jpg', './assets/emp_documents/1009_2149281301_Iqama_20190625090229.jpg', '2019-06-25T09:02:29+03:00'),
(42, '1383', 146, 'Iqama', 'jpg', './assets/emp_documents/1383_2016033694_Iqama_20190625093907.jpg', '2019-06-25T09:39:07+03:00'),
(43, '1383', 146, 'Passport-1', 'jpg', './assets/emp_documents/1383_2016033694_Passport-1_20190625094148.jpg', '2019-06-25T09:41:48+03:00'),
(44, '1383', 146, 'Others', 'jpg', './assets/emp_documents/1383_2016033694_Others_20190625094213.jpg', '2019-06-25T09:42:13+03:00'),
(45, '4620', 93, 'Iqama', 'jpg', './assets/emp_documents/4620_1069466991_Iqama_20190731111903.jpg', '2019-07-31T11:19:03+03:00'),
(46, '1061', 15, 'Iqama', 'jpg', './assets/emp_documents/1061_2174357323_Iqama_20191013093053.jpg', '2019-10-13T09:30:53+03:00'),
(47, '1061', 15, 'Passport-1', 'jpg', './assets/emp_documents/1061_2174357323_Passport-1_20191013093112.jpg', '2019-10-13T09:31:12+03:00'),
(51, '1064', 17, 'Passport-1', 'jpg', './assets/emp_documents/1064_2174359097_Passport-1_20191013093455.jpg', '2019-10-13T09:34:55+03:00'),
(50, '1064', 17, 'Iqama', 'jpg', './assets/emp_documents/1064_2174359097_Iqama_20191013093435.jpg', '2019-10-13T09:34:35+03:00'),
(141, '1024', 11, 'Others', 'pdf', './assets/emp_documents/1024_2016033686_Others_20201026122912.pdf', '2020-10-26T12:29:12+03:00'),
(53, '1067', 18, 'Passport-1', 'jpg', './assets/emp_documents/1067_2167976543_Passport-1_20191013093905.jpg', '2019-10-13T09:39:05+03:00'),
(54, '1070', 19, 'Iqama', 'jpg', './assets/emp_documents/1070_2184146468_Iqama_20191013094520.jpg', '2019-10-13T09:45:20+03:00'),
(55, '1070', 19, 'Passport-1', 'jpg', './assets/emp_documents/1070_2184146468_Passport-1_20191013094538.jpg', '2019-10-13T09:45:38+03:00'),
(56, '1077', 21, 'Iqama', 'jpg', './assets/emp_documents/1077_2182548723_Iqama_20191013094907.jpg', '2019-10-13T09:49:07+03:00'),
(57, '1077', 21, 'Passport-1', 'jpg', './assets/emp_documents/1077_2182548723_Passport-1_20191013094920.jpg', '2019-10-13T09:49:20+03:00'),
(58, '1088', 23, 'Iqama', 'jpg', './assets/emp_documents/1088_2184329353_Iqama_20191013095133.jpg', '2019-10-13T09:51:33+03:00'),
(59, '1088', 23, 'Passport-1', 'jpg', './assets/emp_documents/1088_2184329353_Passport-1_20191013095148.jpg', '2019-10-13T09:51:48+03:00'),
(60, '1088', 23, 'Passport-2', 'jpg', './assets/emp_documents/1088_2184329353_Passport-2_20191013095205.jpg', '2019-10-13T09:52:05+03:00'),
(61, '1098', 24, 'Iqama', 'jpg', './assets/emp_documents/1098_2181563509_Iqama_20191013095307.jpg', '2019-10-13T09:53:07+03:00'),
(62, '1098', 24, 'Passport-1', 'jpg', './assets/emp_documents/1098_2181563509_Passport-1_20191013095323.jpg', '2019-10-13T09:53:23+03:00'),
(63, '1101', 25, 'Iqama', 'jpg', './assets/emp_documents/1101_2188048330_Iqama_20191013095428.jpg', '2019-10-13T09:54:28+03:00'),
(64, '1101', 25, 'Passport-1', 'jpg', './assets/emp_documents/1101_2188048330_Passport-1_20191013095444.jpg', '2019-10-13T09:54:44+03:00'),
(65, '1112', 26, 'Iqama', 'jpg', './assets/emp_documents/1112_2187484551_Iqama_20191013095536.jpg', '2019-10-13T09:55:36+03:00'),
(66, '1112', 26, 'Passport-1', 'jpg', './assets/emp_documents/1112_2187484551_Passport-1_20191013095553.jpg', '2019-10-13T09:55:53+03:00'),
(67, '1114', 28, 'Iqama', 'jpg', './assets/emp_documents/1114_2154834499_Iqama_20191013102857.jpg', '2019-10-13T10:28:57+03:00'),
(68, '1114', 28, 'Passport-1', 'jpg', './assets/emp_documents/1114_2154834499_Passport-1_20191013102915.jpg', '2019-10-13T10:29:15+03:00'),
(69, '1117', 29, 'Iqama', 'jpg', './assets/emp_documents/1117_2173242369_Iqama_20191013102955.jpg', '2019-10-13T10:29:55+03:00'),
(70, '1117', 29, 'Passport-1', 'jpg', './assets/emp_documents/1117_2173242369_Passport-1_20191013103010.jpg', '2019-10-13T10:30:10+03:00'),
(71, '1136', 32, 'Iqama', 'jpg', './assets/emp_documents/1136_2205551639_Iqama_20191013103144.jpg', '2019-10-13T10:31:44+03:00'),
(72, '1136', 32, 'Passport-1', 'jpg', './assets/emp_documents/1136_2205551639_Passport-1_20191013103159.jpg', '2019-10-13T10:31:59+03:00'),
(73, '1148', 34, 'Iqama', 'jpg', './assets/emp_documents/1148_2198026128_Iqama_20191013103344.jpg', '2019-10-13T10:33:44+03:00'),
(74, '1148', 34, 'Passport-1', 'jpg', './assets/emp_documents/1148_2198026128_Passport-1_20191013103359.jpg', '2019-10-13T10:33:59+03:00'),
(75, '1163', 35, 'Iqama', 'jpg', './assets/emp_documents/1163_2199300571_Iqama_20191013103435.jpg', '2019-10-13T10:34:35+03:00'),
(76, '1193', 38, 'Iqama', 'jpg', './assets/emp_documents/1193_2227368541_Iqama_20191013103619.jpg', '2019-10-13T10:36:19+03:00'),
(77, '1193', 38, 'Passport-1', 'jpg', './assets/emp_documents/1193_2227368541_Passport-1_20191013103632.jpg', '2019-10-13T10:36:32+03:00'),
(78, '1243', 41, 'Iqama', 'jpg', './assets/emp_documents/1243_2256186673_Iqama_20191013103832.jpg', '2019-10-13T10:38:32+03:00'),
(79, '1243', 41, 'Passport-1', 'jpg', './assets/emp_documents/1243_2256186673_Passport-1_20191013103846.jpg', '2019-10-13T10:38:46+03:00'),
(80, '1243', 41, 'Passport-1', 'jpg', './assets/emp_documents/1243_2256186673_Passport-1_20191013103859.jpg', '2019-10-13T10:38:59+03:00'),
(81, '1252', 45, 'Iqama', 'jpg', './assets/emp_documents/1252_2244022485_Iqama_20191013104122.jpg', '2019-10-13T10:41:22+03:00'),
(82, '1252', 45, 'Passport-1', 'jpg', './assets/emp_documents/1252_2244022485_Passport-1_20191013104138.jpg', '2019-10-13T10:41:38+03:00'),
(83, '1331', 48, 'Iqama', 'jpg', './assets/emp_documents/1331_2304893536_Iqama_20191013104240.jpg', '2019-10-13T10:42:40+03:00'),
(84, '1331', 48, 'Passport-1', 'jpg', './assets/emp_documents/1331_2304893536_Passport-1_20191013104309.jpg', '2019-10-13T10:43:09+03:00'),
(85, '1351', 49, 'Iqama', 'jpg', './assets/emp_documents/1351_2309590335_Iqama_20191013104435.jpg', '2019-10-13T10:44:35+03:00'),
(86, '1351', 49, 'Passport-1', 'jpg', './assets/emp_documents/1351_2309590335_Passport-1_20191013104450.jpg', '2019-10-13T10:44:50+03:00'),
(87, '1351', 49, 'Passport-2', 'jpg', './assets/emp_documents/1351_2309590335_Passport-2_20191013104504.jpg', '2019-10-13T10:45:04+03:00'),
(88, '1357', 50, 'Iqama', 'pdf', './assets/emp_documents/1357_2113245498_Iqama_20191013104604.pdf', '2019-10-13T10:46:04+03:00'),
(89, '1357', 50, 'Passport-1', 'pdf', './assets/emp_documents/1357_2113245498_Passport-1_20191013104618.pdf', '2019-10-13T10:46:18+03:00'),
(90, '1359', 51, 'Iqama', 'jpg', './assets/emp_documents/1359_2349820668_Iqama_20191013104700.jpg', '2019-10-13T10:47:00+03:00'),
(91, '1359', 51, 'Passport-1', 'jpg', './assets/emp_documents/1359_2349820668_Passport-1_20191013104713.jpg', '2019-10-13T10:47:13+03:00'),
(92, '1402', 55, 'Iqama', 'jpg', './assets/emp_documents/1402_2130209543_Iqama_20191013104958.jpg', '2019-10-13T10:49:58+03:00'),
(93, '1406', 58, 'Iqama', 'jpg', './assets/emp_documents/1406_2311040493_Iqama_20191013105122.jpg', '2019-10-13T10:51:22+03:00'),
(94, '1406', 58, 'Passport-1', 'jpg', './assets/emp_documents/1406_2311040493_Passport-1_20191013105136.jpg', '2019-10-13T10:51:36+03:00'),
(95, '1406', 58, 'Passport-2', 'jpg', './assets/emp_documents/1406_2311040493_Passport-2_20191013105151.jpg', '2019-10-13T10:51:51+03:00'),
(97, '1410', 59, 'Iqama', 'jpg', './assets/emp_documents/1410_2397222379_Iqama_20191013105738.jpg', '2019-10-13T10:57:38+03:00'),
(98, '1410', 59, 'Passport-1', 'jpg', './assets/emp_documents/1410_2397222379_Passport-1_20191013105756.jpg', '2019-10-13T10:57:56+03:00'),
(99, '1410', 59, 'Passport-2', 'jpg', './assets/emp_documents/1410_2397222379_Passport-2_20191013105808.jpg', '2019-10-13T10:58:08+03:00'),
(100, '18', 84, 'Iqama', 'jpg', './assets/emp_documents/18_2184414726_Iqama_20191013110138.jpg', '2019-10-13T11:01:38+03:00'),
(101, '18', 84, 'Passport-1', 'jpg', './assets/emp_documents/18_2184414726_Passport-1_20191013110153.jpg', '2019-10-13T11:01:53+03:00'),
(102, '18', 84, 'Passport-2', 'jpg', './assets/emp_documents/18_2184414726_Passport-2_20191013110208.jpg', '2019-10-13T11:02:08+03:00'),
(103, '0156', 224, 'Iqama', 'pdf', './assets/emp_documents/0156_1066927987_Iqama_20200602122509.pdf', '2020-06-02T12:25:09+03:00'),
(104, '0156', 224, 'Others', 'pdf', './assets/emp_documents/0156_1066927987_Others_20200602122545.pdf', '2020-06-02T12:25:45+03:00'),
(105, '0156', 224, 'Others', 'pdf', './assets/emp_documents/0156_1066927987_Others_20200602122623.pdf', '2020-06-02T12:26:23+03:00'),
(106, '0156', 224, 'Others', 'pdf', './assets/emp_documents/0156_1066927987_Others_20200602122732.pdf', '2020-06-02T12:27:32+03:00'),
(107, '0156', 224, 'Others', 'pdf', './assets/emp_documents/0156_1066927987_Others_20200602125548.pdf', '2020-06-02T12:55:48+03:00'),
(108, '0156', 224, 'Others', 'pdf', './assets/emp_documents/0156_1066927987_Others_20200602125609.pdf', '2020-06-02T12:56:09+03:00'),
(109, '0156', 224, 'Others', 'pdf', './assets/emp_documents/0156_1066927987_Others_20200602125627.pdf', '2020-06-02T12:56:27+03:00'),
(110, '0156', 224, 'Others', 'pdf', './assets/emp_documents/0156_1066927987_Others_20200602125641.pdf', '2020-06-02T12:56:41+03:00'),
(111, '0156', 224, 'Others', 'pdf', './assets/emp_documents/0156_1066927987_Others_20200602125703.pdf', '2020-06-02T12:57:03+03:00'),
(112, '0156', 224, 'Others', 'pdf', './assets/emp_documents/0156_1066927987_Others_20200602125721.pdf', '2020-06-02T12:57:21+03:00'),
(113, '0156', 224, 'Others', 'pdf', './assets/emp_documents/0156_1066927987_Others_20200602125736.pdf', '2020-06-02T12:57:36+03:00'),
(114, '0156', 224, 'Others', 'pdf', './assets/emp_documents/0156_1066927987_Others_20200602125750.pdf', '2020-06-02T12:57:50+03:00'),
(115, '0156', 224, 'Others', 'pdf', './assets/emp_documents/0156_1066927987_Others_20200602125811.pdf', '2020-06-02T12:58:11+03:00'),
(116, '0156', 224, 'Others', 'pdf', './assets/emp_documents/0156_1066927987_Others_20200602125826.pdf', '2020-06-02T12:58:26+03:00'),
(117, '0156', 224, 'Others', 'pdf', './assets/emp_documents/0156_1066927987_Others_20200602125843.pdf', '2020-06-02T12:58:43+03:00'),
(118, '0156', 224, 'Others', 'pdf', './assets/emp_documents/0156_1066927987_Others_20200602125855.pdf', '2020-06-02T12:58:55+03:00'),
(119, '1024', 11, 'Iqama', 'pdf', './assets/emp_documents/1024_2016033686_Iqama_20200722093314.pdf', '2020-07-22T09:33:14+03:00'),
(120, '4523', 66, 'Others', 'pdf', './assets/emp_documents/4523_1092018744_Others_20200901133138.pdf', '2020-09-01T13:31:38+03:00'),
(121, '4523', 66, 'Others', 'pdf', './assets/emp_documents/4523_1092018744_Others_20200901133208.pdf', '2020-09-01T13:32:08+03:00'),
(124, '0156', 224, 'Others', 'pdf', './assets/emp_documents/0156_1066927987_Others_20200909084136.pdf', '2020-09-09T08:41:36+03:00'),
(125, '4523', 66, 'Others', 'pdf', './assets/emp_documents/4523_1092018744_Others_20200909092912.pdf', '2020-09-09T09:29:12+03:00'),
(126, '4523', 66, 'Iqama', 'pdf', './assets/emp_documents/4523_1092018744_Iqama_20200909101325.pdf', '2020-09-09T10:13:25+03:00'),
(127, '4523', 66, 'Others', 'pdf', './assets/emp_documents/4523_1092018744_Others_20200909101348.pdf', '2020-09-09T10:13:48+03:00'),
(128, '4523', 66, 'Others', 'pdf', './assets/emp_documents/4523_1092018744_Others_20200909101404.pdf', '2020-09-09T10:14:04+03:00'),
(129, '147', 95, 'Iqama', 'jpg', './assets/emp_documents/147__Iqama_20200910090903.jpg', '2020-09-10T09:09:03+03:00'),
(130, '147', 95, 'Others', 'jpg', './assets/emp_documents/147__Others_20200910090918.jpg', '2020-09-10T09:09:18+03:00'),
(131, '4637', 237, 'BaldiaCard', 'jpg', './assets/emp_documents/4637_1096545635_BaldiaCard_20200910095112.jpg', '2020-09-10T09:51:12+03:00'),
(132, '4637', 237, 'Others', 'jpg', './assets/emp_documents/4637_1096545635_Others_20200910095141.jpg', '2020-09-10T09:51:41+03:00'),
(133, '2', 81, 'Iqama', 'pdf', './assets/emp_documents/2_1042798981_Iqama_20201011100907.pdf', '2020-10-11T10:09:07+03:00'),
(134, '1026', 12, 'BaldiaCard', 'jpeg', './assets/emp_documents/1026_2187081001_BaldiaCard_20201015121445.jpeg', '2020-10-15T12:14:45+03:00'),
(135, '1038', 13, 'BaldiaCard', 'jpeg', './assets/emp_documents/1038_2142998554_BaldiaCard_20201015121610.jpeg', '2020-10-15T12:16:10+03:00'),
(136, '1403', 56, 'BaldiaCard', 'jpeg', './assets/emp_documents/1403_2227062656_BaldiaCard_20201015121659.jpeg', '2020-10-15T12:16:59+03:00'),
(137, '1248', 43, 'BaldiaCard', 'jpeg', './assets/emp_documents/1248_2259239578_BaldiaCard_20201015121739.jpeg', '2020-10-15T12:17:39+03:00'),
(138, '1244', 42, 'BaldiaCard', 'jpeg', './assets/emp_documents/1244_2241093653_BaldiaCard_20201015121811.jpeg', '2020-10-15T12:18:11+03:00'),
(139, '4398', 209, 'BaldiaCard', 'jpeg', './assets/emp_documents/4398__BaldiaCard_20201015121842.jpeg', '2020-10-15T12:18:42+03:00'),
(140, '1067', 18, 'Iqama', 'pdf', './assets/emp_documents/1067_2167976543_Iqama_20201015122213.pdf', '2020-10-15T12:22:13+03:00');

-- --------------------------------------------------------

--
-- Table structure for table `emp_vacation`
--

CREATE TABLE IF NOT EXISTS `emp_vacation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `emp_id` varchar(255) NOT NULL,
  `date` varchar(255) NOT NULL,
  `user_update` varchar(255) NOT NULL,
  `return_date` varchar(50) NOT NULL,
  `vacdays` varchar(50) NOT NULL,
  `arrived_date` varchar(100) NOT NULL,
  `permit_no` varchar(100) NOT NULL,
  `remarks` varchar(255) NOT NULL,
  `review` varchar(50) NOT NULL,
  `note` varchar(255) NOT NULL,
  `date_reg` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=672 ;

--
-- Dumping data for table `emp_vacation`
--

INSERT INTO `emp_vacation` (`id`, `emp_id`, `date`, `user_update`, `return_date`, `vacdays`, `arrived_date`, `permit_no`, `remarks`, `review`, `note`, `date_reg`) VALUES
(1, '1002', '05/03/2004', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T18:49:28+03:00'),
(2, '1002', '06/04/2006', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T18:50:09+03:00'),
(3, '1002', '30/06/2008', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T18:50:24+03:00'),
(4, '1002', '18/05/2011', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T18:50:29+03:00'),
(5, '1002', '19/11/2013', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T18:52:02+03:00'),
(6, '1002', '11/04/2016', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T18:52:08+03:00'),
(7, '1002', '18/05/2018', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T18:52:26+03:00'),
(8, '1003', '10/03/2003', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T18:56:09+03:00'),
(9, '1003', '24/03/2005', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T18:56:13+03:00'),
(10, '1003', '16/03/2007', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T18:56:18+03:00'),
(11, '1003', '16/05/2008', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T18:56:22+03:00'),
(12, '1003', '09/01/2019', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T18:56:28+03:00'),
(13, '1003', '26/06/2011', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T18:56:36+03:00'),
(14, '1003', '01/06/2013', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T18:56:40+03:00'),
(15, '1003', '20/09/2014', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T18:56:44+03:00'),
(16, '1003', '20/10/2015', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T18:56:50+03:00'),
(17, '1005', '12/02/2005', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T19:06:25+03:00'),
(18, '1005', '12/02/2006', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T19:06:48+03:00'),
(19, '1005', '29/11/2007', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T19:06:53+03:00'),
(20, '1005', '09/04/2009', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T19:07:11+03:00'),
(21, '1005', '28/11/2010', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T19:07:16+03:00'),
(22, '1005', '22/01/2012', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T19:07:22+03:00'),
(23, '1005', '18/09/2014', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T19:07:26+03:00'),
(24, '1007', '07/03/2003', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T19:11:31+03:00'),
(25, '1007', '23/08/2005', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T19:11:36+03:00'),
(26, '1007', '28/10/2007', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T19:11:43+03:00'),
(27, '1007', '14/03/2009', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T19:11:47+03:00'),
(28, '1007', '07/01/2011', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T19:11:57+03:00'),
(29, '1007', '27/07/2012', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T19:12:05+03:00'),
(30, '1007', '03/08/2014', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T19:12:11+03:00'),
(31, '1007', '07/06/2016', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T19:12:19+03:00'),
(32, '1007', '30/05/2017', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T19:12:23+03:00'),
(33, '1007', '20/05/2018', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T19:12:30+03:00'),
(34, '1009', '30/04/2004', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T19:12:50+03:00'),
(35, '1009', '22/11/2006', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T19:12:56+03:00'),
(36, '1009', '15/11/2008', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T19:12:59+03:00'),
(37, '1009', '25/03/2010', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T19:13:43+03:00'),
(38, '1009', '26/12/2011', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T19:13:51+03:00'),
(39, '1009', '25/01/2013', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T19:13:55+03:00'),
(40, '1009', '04/11/2014', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T19:14:01+03:00'),
(41, '1009', '09/02/2015', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T19:14:06+03:00'),
(42, '1009', '15/01/2018', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T19:52:08+03:00'),
(43, '1011', '20/12/2002', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T19:53:34+03:00'),
(44, '1011', '07/09/2005', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T19:53:39+03:00'),
(45, '1011', '11/05/2008', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T19:53:46+03:00'),
(46, '1011', '01/08/2011', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T19:53:53+03:00'),
(47, '1011', '09/04/2013', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T19:54:40+03:00'),
(48, '1011', '25/08/2013', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T19:54:55+03:00'),
(49, '1011', '16/06/2015', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T19:54:59+03:00'),
(50, '1011', '27/05/2017', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T19:55:04+03:00'),
(51, '1020', '25/03/2004', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T19:56:33+03:00'),
(52, '1020', '21/02/2006', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T19:56:39+03:00'),
(53, '1020', '18/10/2007', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T19:56:46+03:00'),
(54, '1020', '04/05/2009', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T19:56:52+03:00'),
(55, '1020', '19/11/2010', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T19:56:58+03:00'),
(56, '1020', '14/09/2011', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T19:57:04+03:00'),
(57, '1020', '16/03/2012', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T19:57:14+03:00'),
(58, '1020', '25/01/2013', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T19:57:21+03:00'),
(59, '1020', '10/08/2014', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T19:57:28+03:00'),
(60, '1020', '29/08/2015', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T19:57:37+03:00'),
(61, '1020', '18/07/2016', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T19:57:43+03:00'),
(62, '1020', '25/06/2017', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T19:57:51+03:00'),
(63, '1020', '18/03/2018', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T19:57:56+03:00'),
(64, '1021', '09/09/2004', ' Mohammed Imtiaz ', '', '', '', '', '', '', 'Fly', '2019-01-09T20:04:56+03:00'),
(65, '1021', '11/04/2006', ' Mohammed Imtiaz ', '', '', '', '', '', '', 'Fly', '2019-01-09T20:05:01+03:00'),
(66, '1021', '29/01/2008', ' Mohammed Imtiaz ', '', '', '', '', '', '', 'Fly', '2019-01-09T20:05:06+03:00'),
(67, '1021', '29/01/2008', ' Mohammed Imtiaz ', '', '', '', '', '', '', 'Fly', '2019-01-09T20:05:11+03:00'),
(68, '1021', '02/03/2012', ' Mohammed Imtiaz ', '', '', '', '', '', '', 'Fly', '2019-01-09T20:05:15+03:00'),
(69, '1021', '16/01/2014', ' Mohammed Imtiaz ', '', '', '', '', '', '', 'Fly', '2019-01-09T20:05:20+03:00'),
(70, '1021', '16/01/2016', 'Mohammed Imtiaz ', '', '', '', '', '', '', 'Encashed', '2019-01-09T20:05:42+03:00'),
(71, '1021', '15/05/2017', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:07:44+03:00'),
(72, '1021', '18/10/2018', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:07:49+03:00'),
(73, '1022', '23/09/2004', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:08:07+03:00'),
(74, '1022', '03/05/2007', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:08:11+03:00'),
(75, '1022', '25/06/2009', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:08:16+03:00'),
(76, '1022', '16/09/2011', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:08:20+03:00'),
(77, '1022', '11/04/2014', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:08:25+03:00'),
(78, '1022', '01/05/2016', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:08:29+03:00'),
(79, '1023', '08/09/2004', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:09:55+03:00'),
(80, '1023', '16/11/2006', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:10:00+03:00'),
(81, '1023', '25/03/2008', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:10:05+03:00'),
(82, '1023', '01/02/2010', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:10:09+03:00'),
(83, '1023', '04/07/2011', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:10:14+03:00'),
(84, '1023', '09/06/2013', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:10:19+03:00'),
(85, '1023', '01/08/2015', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:10:23+03:00'),
(86, '1023', '23/07/2017', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:10:29+03:00'),
(87, '1026', '20/11/2004', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:11:35+03:00'),
(88, '1026', '01/11/2006', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:11:47+03:00'),
(89, '1026', '01/11/2008', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T20:12:16+03:00'),
(90, '1026', '19/08/2010', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:12:21+03:00'),
(91, '1026', '01/06/2011', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:12:28+03:00'),
(92, '1026', '09/05/2016', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:12:32+03:00'),
(93, '1026', '08/04/2018', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:12:36+03:00'),
(95, '1038', '31/05/2003', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:15:32+03:00'),
(96, '1038', '07/02/2005', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:15:37+03:00'),
(97, '1038', '25/09/2007', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:15:41+03:00'),
(98, '1038', '06/03/2009', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:15:45+03:00'),
(99, '1038', '06/04/2010', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T20:16:07+03:00'),
(100, '1038', '18/02/2011', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:16:16+03:00'),
(101, '1038', '10/01/2012', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:16:22+03:00'),
(102, '1038', '19/12/2012', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:16:26+03:00'),
(103, '1038', '10/05/2014', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:16:31+03:00'),
(104, '1038', '15/07/2015', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:16:36+03:00'),
(105, '1038', '05/09/2016', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:16:42+03:00'),
(106, '1038', '03/12/2017', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:16:52+03:00'),
(107, '1055', '10/02/2005', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:17:19+03:00'),
(108, '1055', '10/02/2006', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T20:17:45+03:00'),
(109, '1055', '15/11/2008', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:17:50+03:00'),
(110, '1055', '18/01/2012', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:17:55+03:00'),
(111, '1055', '12/06/2014', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:18:00+03:00'),
(112, '1055', '27/05/2017', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:18:07+03:00'),
(113, '1061', '13/10/2005', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T20:18:58+03:00'),
(114, '1061', '13/10/2007', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T20:20:07+03:00'),
(115, '1061', '13/10/2010', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T20:20:32+03:00'),
(116, '1061', '23/10/2011', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:20:38+03:00'),
(117, '1061', '12/09/2014', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:20:43+03:00'),
(118, '1062', '06/09/2006', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:21:41+03:00'),
(119, '1062', '06/01/2009', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:21:45+03:00'),
(120, '1062', '15/07/2011', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:21:50+03:00'),
(121, '1062', '15/11/2011', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T20:22:15+03:00'),
(122, '1062', '31/07/2013', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:22:19+03:00'),
(123, '1062', '31/08/2015', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:22:24+03:00'),
(124, '1064', '13/10/2005', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T20:23:06+03:00'),
(125, '1064', '23/02/2009', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:23:11+03:00'),
(126, '1064', '14/09/2012', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:23:15+03:00'),
(127, '1064', '11/02/2013', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T20:23:34+03:00'),
(128, '1064', '02/03/2017', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:23:40+03:00'),
(129, '1067', '15/10/2004', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T20:24:23+03:00'),
(130, '1067', '15/10/2007', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T20:25:54+03:00'),
(131, '1067', '06/01/2009', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:25:59+03:00'),
(132, '1067', '18/05/2011', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:26:03+03:00'),
(133, '1067', '20/12/2012', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:26:08+03:00'),
(134, '1067', '20/10/2015', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:26:12+03:00'),
(135, '1067', '18/05/2018', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:26:17+03:00'),
(136, '1070', '24/10/2004', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T20:27:17+03:00'),
(137, '1070', '24/10/2006', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T20:27:28+03:00'),
(138, '1070', '24/10/2009', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T20:27:37+03:00'),
(139, '1070', '03/08/2011', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:27:52+03:00'),
(140, '1070', '28/08/2013', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:27:57+03:00'),
(141, '1070', '11/11/2015', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:28:01+03:00'),
(142, '1075', '18/01/2005', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:41:27+03:00'),
(143, '1075', '18/01/2006', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T20:41:33+03:00'),
(144, '1075', '18/01/2008', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T20:41:40+03:00'),
(145, '1075', '21/08/2010', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:41:44+03:00'),
(146, '1075', '01/12/2013', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:41:49+03:00'),
(147, '1075', '09/04/2015', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:41:53+03:00'),
(148, '1075', '18/07/2016', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:41:57+03:00'),
(149, '1075', '25/09/2017', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T20:42:53+03:00'),
(150, '1077', '06/09/2006', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:43:56+03:00'),
(151, '1077', '12/04/2008', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:44:01+03:00'),
(152, '1077', '12/10/2011', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:44:05+03:00'),
(153, '1077', '12/11/2012', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:44:10+03:00'),
(154, '1077', '13/05/2015', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:44:25+03:00'),
(155, '1077', '10/07/2018', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:44:30+03:00'),
(156, '1084', '30/01/2006', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:44:46+03:00'),
(157, '1084', '02/05/2009', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:44:50+03:00'),
(158, '1084', '26/12/2011', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:44:54+03:00'),
(159, '1084', '01/08/2013', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:44:59+03:00'),
(160, '1084', '27/04/2015', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:45:03+03:00'),
(161, '1084', '01/07/2016', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:45:07+03:00'),
(162, '1084', '20/07/2018', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:45:12+03:00'),
(163, '1088', '04/05/2005', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:45:44+03:00'),
(164, '1088', '16/08/2007', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:45:48+03:00'),
(165, '1088', '02/03/2009', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:45:53+03:00'),
(166, '1088', '02/02/2011', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:45:57+03:00'),
(167, '1088', '10/11/2012', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:46:02+03:00'),
(168, '1088', '01/04/2015', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:46:06+03:00'),
(169, '1088', '31/07/2017', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:46:10+03:00'),
(170, '1098', '17/03/2005', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T20:47:20+03:00'),
(171, '1098', '17/03/2007', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T20:47:29+03:00'),
(172, '1098', '17/03/2009', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T20:47:39+03:00'),
(173, '1098', '12/10/2011', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:47:43+03:00'),
(174, '1098', '11/02/2015', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:47:47+03:00'),
(175, '1098', '27/05/2017', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:47:52+03:00'),
(176, '1101', '05/04/2005', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T20:48:59+03:00'),
(177, '1101', '19/03/2008', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:49:04+03:00'),
(178, '1101', '23/09/2011', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:49:10+03:00'),
(179, '1101', '26/02/2013', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T20:49:32+03:00'),
(180, '1101', '18/04/2014', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:49:40+03:00'),
(181, '1101', '16/05/2016', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:49:44+03:00'),
(182, '1112', '15/02/2006', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:50:23+03:00'),
(183, '1112', '11/05/2008', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:50:27+03:00'),
(184, '1112', '18/05/2011', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:50:31+03:00'),
(185, '1112', '12/11/2012', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:50:35+03:00'),
(186, '1112', '18/02/2015', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:50:40+03:00'),
(187, '1112', '03/10/2017', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:50:44+03:00'),
(188, '1113', '29/01/2007', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:51:10+03:00'),
(189, '1113', '06/07/2008', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:51:14+03:00'),
(190, '1113', '20/02/2011', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:51:19+03:00'),
(191, '1113', '04/01/2012', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T20:51:40+03:00'),
(192, '1113', '16/03/2013', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:51:45+03:00'),
(193, '1113', '12/07/2015', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:51:49+03:00'),
(194, '1113', '07/04/2018', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:51:54+03:00'),
(195, '1114', '29/11/2005', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:52:34+03:00'),
(196, '1114', '06/08/2007', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:52:38+03:00'),
(197, '1114', '16/11/2009', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:52:42+03:00'),
(198, '1114', '23/01/2012', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:52:46+03:00'),
(199, '1114', '14/01/2014', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:52:51+03:00'),
(200, '1114', '13/04/2016', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:52:55+03:00'),
(201, '1114', '29/07/2018', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:53:00+03:00'),
(202, '1117', '25/12/2008', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:53:17+03:00'),
(203, '1117', '02/05/2011', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T20:54:16+03:00'),
(204, '1117', '13/12/2014', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:54:21+03:00'),
(205, '1119', '12/03/2007', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:54:41+03:00'),
(206, '1119', '13/01/2010', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:54:46+03:00'),
(207, '1119', '05/06/2012', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:54:50+03:00'),
(208, '1119', '01/03/2015', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:54:54+03:00'),
(209, '1119', '06/03/2017', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:54:59+03:00'),
(210, '1133', '02/09/2007', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:55:17+03:00'),
(211, '1133', '12/12/2010', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:55:21+03:00'),
(212, '1133', '22/05/2011', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T20:55:34+03:00'),
(213, '1133', '17/12/2012', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:55:39+03:00'),
(214, '1133', '20/11/2015', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:55:44+03:00'),
(215, '1136', '01/11/2006', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:56:09+03:00'),
(216, '1136', '08/01/2009', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:56:14+03:00'),
(217, '1136', '01/05/2010', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:56:18+03:00'),
(218, '1136', '20/04/2011', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:56:22+03:00'),
(219, '1136', '02/10/2013', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:56:26+03:00'),
(220, '1136', '13/12/2015', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:56:31+03:00'),
(221, '1136', '19/07/2018', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:58:44+03:00'),
(222, '1146', '04/11/2006', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:59:01+03:00'),
(223, '1146', '26/07/2008', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:59:05+03:00'),
(224, '1146', '06/08/2010', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:59:10+03:00'),
(225, '1146', '15/05/2012', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:59:14+03:00'),
(226, '1146', '24/09/2013', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:59:19+03:00'),
(227, '1146', '02/04/2014', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:59:24+03:00'),
(228, '1146', '22/02/2015', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:59:28+03:00'),
(229, '1146', '22/02/2016', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:59:32+03:00'),
(230, '1146', '16/05/2018', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T20:59:37+03:00'),
(231, '1148', '08/08/2006', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T21:00:02+03:00'),
(232, '1148', '15/12/2009', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:00:06+03:00'),
(233, '1148', '16/03/2012', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:00:10+03:00'),
(234, '1148', '14/11/2014', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:00:15+03:00'),
(235, '1148', '10/06/2016', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:00:19+03:00'),
(236, '1148', '18/05/2018', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:00:23+03:00'),
(237, '1163', '02/11/2006', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T21:01:28+03:00'),
(238, '1163', '24/10/2010', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:01:33+03:00'),
(239, '1163', '01/06/2011', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T21:01:51+03:00'),
(240, '1163', '03/02/2013', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:01:57+03:00'),
(241, '1163', '15/05/2015', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:02:01+03:00'),
(242, '1168', '23/01/2008', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:02:19+03:00'),
(243, '1168', '09/05/2011', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:02:23+03:00'),
(244, '1168', '31/07/2013', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:02:27+03:00'),
(245, '1168', '25/11/2015', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:02:32+03:00'),
(246, '1178', '25/11/2009', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T21:03:01+03:00'),
(247, '1178', '10/01/2011', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:03:56+03:00'),
(248, '1178', '17/09/2013', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T21:04:11+03:00'),
(249, '1178', '11/03/2018', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:04:15+03:00'),
(250, '1193', '26/02/2009', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T21:04:33+03:00'),
(251, '1193', '26/02/2011', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T21:04:45+03:00'),
(252, '1193', '11/02/2013', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T21:04:59+03:00'),
(253, '1193', '28/08/2015', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:05:05+03:00'),
(254, '1193', '18/05/2018', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:05:09+03:00'),
(255, '1198', '03/09/2009', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:05:28+03:00'),
(256, '1198', '18/01/2012', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:05:33+03:00'),
(257, '1198', '06/03/2016', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:05:37+03:00'),
(258, '1217', '19/08/2009', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:06:01+03:00'),
(259, '1217', '16/04/2012', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:06:05+03:00'),
(260, '1217', '31/07/2017', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:06:09+03:00'),
(261, '1243', '16/10/2010', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:06:30+03:00'),
(262, '1243', '25/12/2012', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:06:35+03:00'),
(263, '1243', '25/01/2015', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:06:39+03:00'),
(264, '1243', '23/12/2016', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:06:44+03:00'),
(265, '1243', '02/10/2018', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:06:48+03:00'),
(266, '1244', '04/09/2010', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:07:30+03:00'),
(267, '1244', '22/12/2012', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:07:50+03:00'),
(268, '1244', '15/10/2014', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:07:58+03:00'),
(269, '1244', '24/01/2017', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:08:03+03:00'),
(270, '1244', '17/10/2018', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:08:08+03:00'),
(271, '1248', '15/03/2011', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:08:23+03:00'),
(272, '1248', '01/01/2013', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:08:27+03:00'),
(273, '1248', '16/11/2014', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:08:32+03:00'),
(274, '1248', '02/12/2016', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:08:36+03:00'),
(275, '1248', '17/11/2018', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:08:40+03:00'),
(276, '1249', '05/11/2010', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T21:08:53+03:00'),
(277, '1249', '22/10/2012', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:09:31+03:00'),
(278, '1249', '12/01/2015', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:09:35+03:00'),
(279, '1249', '15/05/2017', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:09:40+03:00'),
(280, '1252', '07/11/2010', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T21:10:01+03:00'),
(281, '1252', '03/01/2012', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T21:10:14+03:00'),
(282, '1252', '09/01/2016', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:10:19+03:00'),
(283, '1252', '31/07/2017', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:10:23+03:00'),
(284, '1256', '12/03/2011', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T21:11:13+03:00'),
(285, '1256', '29/11/2012', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T21:11:23+03:00'),
(286, '1256', '06/03/2016', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:11:28+03:00'),
(287, '1256', '21/11/2018', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:11:32+03:00'),
(288, '1323', '05/05/2012', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:11:56+03:00'),
(289, '1323', '08/04/2014', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T21:12:16+03:00'),
(290, '1323', '01/12/2016', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:12:20+03:00'),
(291, '1331', '11/02/2013', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T21:13:01+03:00'),
(292, '1331', '16/05/2016', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:13:05+03:00'),
(293, '1331', '16/05/2018', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:13:10+03:00'),
(294, '1351', '26/02/2014', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:13:49+03:00'),
(295, '1351', '26/10/2015', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:13:53+03:00'),
(296, '1351', '12/04/2018', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:14:06+03:00'),
(297, '1357', '18/07/2014', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:14:22+03:00'),
(298, '1357', '03/09/2016', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:14:27+03:00'),
(299, '1359', '16/06/2015', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:14:45+03:00'),
(300, '1362', '01/01/2016', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:14:58+03:00'),
(301, '1362', '18/05/2018', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:15:03+03:00'),
(302, '1395', '01/10/2017', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:15:26+03:00'),
(303, '1400', '25/09/2017', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T21:16:08+03:00'),
(304, '1402', '01/07/2017', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:16:22+03:00'),
(305, '1403', '17/09/2015', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:16:40+03:00'),
(306, '1403', '10/05/2018', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:16:44+03:00'),
(307, '1405', '16/10/2015', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:17:19+03:00'),
(308, '1405', '30/09/2017', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:17:23+03:00'),
(309, '1406', '21/03/2015', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:17:39+03:00'),
(310, '1406', '01/03/2017', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:17:43+03:00'),
(311, '1410', '21/06/2015', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:17:54+03:00'),
(312, '1410', '20/07/2017', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:17:58+03:00'),
(313, '1411', '01/09/2017', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:18:20+03:00'),
(314, '1412', '06/05/2017', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:18:35+03:00'),
(315, '1413', '08/05/2016', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:18:48+03:00'),
(316, '1413', '08/05/2018', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T21:18:59+03:00'),
(317, '4517', '01/11/2017', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:19:28+03:00'),
(318, '4519', '01/06/2018', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:19:48+03:00'),
(319, '4403', '21/02/2015', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T21:20:50+03:00'),
(320, '4403', '27/05/2017', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:21:01+03:00'),
(321, '4403', '17/06/2018', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:21:05+03:00'),
(322, '4415', '10/06/2015', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:21:30+03:00'),
(323, '4415', '24/08/2017', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T21:21:41+03:00'),
(324, '4416', '10/06/2015', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:22:01+03:00'),
(325, '4416', '24/08/2017', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T21:22:11+03:00'),
(326, '4420', '27/05/2017', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:22:25+03:00'),
(327, '4420', '17/06/2018', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:22:33+03:00'),
(328, '4425', '27/05/2017', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:23:13+03:00'),
(329, '4425', '17/06/2018', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:23:17+03:00'),
(330, '2', '26/10/2005', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:24:30+03:00'),
(331, '2', '20/06/2007', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:24:34+03:00'),
(332, '2', '05/09/2010', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:24:38+03:00'),
(333, '2', '03/07/2011', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:24:43+03:00'),
(334, '9', '14/09/2004', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T21:25:15+03:00'),
(335, '9', '04/08/2006', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:25:20+03:00'),
(336, '9', '04/08/2007', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T21:25:28+03:00'),
(337, '9', '26/07/2008', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:25:33+03:00'),
(338, '9', '26/07/2009', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T21:26:00+03:00'),
(339, '9', '26/07/2010', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T21:26:09+03:00'),
(340, '9', '03/09/2011', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:26:15+03:00'),
(341, '9', '01/04/2012', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:26:20+03:00'),
(342, '9', '13/08/2013', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:26:24+03:00'),
(343, '9', '23/07/2014', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:26:29+03:00'),
(344, '4448', '17/06/2018', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:28:09+03:00'),
(345, '10', '15/09/2004', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:28:50+03:00'),
(346, '10', '18/02/2006', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:28:54+03:00'),
(347, '10', '25/07/2006', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:29:01+03:00'),
(348, '10', '01/04/2007', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:29:07+03:00'),
(349, '10', '04/04/2008', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:29:12+03:00'),
(350, '10', '23/05/2009', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:29:16+03:00'),
(351, '10', '29/06/2010', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:29:20+03:00'),
(352, '10', '09/07/2011', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:29:28+03:00'),
(353, '10', '07/07/2012', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:29:33+03:00'),
(354, '10', '26/06/2013', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:29:38+03:00'),
(355, '10', '29/06/2014', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:29:45+03:00'),
(356, '10', '27/06/2016', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:29:51+03:00'),
(357, '10', '07/08/2017', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:29:57+03:00'),
(358, '10', '15/07/2018', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:30:03+03:00'),
(359, '9', '23/07/2015', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T21:30:55+03:00'),
(360, '9', '23/07/2016', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T21:31:07+03:00'),
(361, '9', '23/07/2017', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T21:31:30+03:00'),
(362, '9', '23/07/2018', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T21:31:40+03:00'),
(363, '18', '04/03/2004', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:32:10+03:00'),
(364, '18', '03/04/2006', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:32:14+03:00'),
(365, '18', '20/09/2008', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:32:19+03:00'),
(366, '18', '26/02/2010', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:32:23+03:00'),
(367, '18', '12/12/2012', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T21:32:32+03:00'),
(368, '18', '22/09/2012', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:32:42+03:00'),
(369, '18', '10/08/2014', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:32:46+03:00'),
(370, '18', '21/04/2015', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:32:50+03:00'),
(371, '18', '10/08/2016', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:32:55+03:00'),
(372, '18', '06/07/2017', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:32:59+03:00'),
(373, '18', '01/10/2018', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T21:33:11+03:00'),
(374, '29', '09/05/2008', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:33:39+03:00'),
(375, '29', '01/01/2010', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:33:44+03:00'),
(376, '29', '11/02/2011', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:33:48+03:00'),
(377, '29', '11/02/2012', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:33:52+03:00'),
(378, '29', '09/04/2013', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:33:59+03:00'),
(379, '29', '06/08/2014', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:34:03+03:00'),
(380, '29', '04/08/2015', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:34:07+03:00'),
(381, '29', '11/09/2016', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:34:12+03:00'),
(382, '29', '07/10/2017', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:34:16+03:00'),
(383, '29', '16/09/2018', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:34:21+03:00'),
(384, '42', '24/11/2009', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:34:37+03:00'),
(385, '42', '03/11/2011', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:34:41+03:00'),
(386, '42', '11/10/2013', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:34:45+03:00'),
(387, '42', '15/02/2015', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:34:51+03:00'),
(388, '42', '04/02/2016', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:34:53+03:00'),
(389, '42', '02/03/2017', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:34:58+03:00'),
(390, '42', '29/01/2018', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:35:02+03:00'),
(391, '47', '12/01/2010', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:35:34+03:00'),
(392, '47', '01/04/2013', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:35:38+03:00'),
(393, '47', '29/10/2014', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T21:35:48+03:00'),
(394, '47', '23/05/2016', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:35:52+03:00'),
(395, '49', '05/09/2012', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T21:36:09+03:00'),
(396, '49', '02/11/2014', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T21:36:18+03:00'),
(397, '49', '02/11/2016', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T21:36:32+03:00'),
(398, '49', '20/03/2017', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:36:38+03:00'),
(399, '82', '29/10/2014', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T21:37:24+03:00'),
(400, '82', '29/10/2016', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T21:37:47+03:00'),
(401, '84', '12/10/2014', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:38:20+03:00'),
(402, '84', '11/09/2016', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:38:24+03:00'),
(403, '84', '02/09/2018', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:39:20+03:00'),
(404, '114', '23/02/2016', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:39:31+03:00'),
(405, '114', '16/03/2017', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:39:36+03:00'),
(406, '114', '01/10/2018', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T21:39:55+03:00'),
(407, '120', '08/03/2016', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:40:10+03:00'),
(408, '120', '02/04/2017', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:40:14+03:00'),
(409, '120', '25/06/2018', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:40:18+03:00'),
(410, '4620', '04/12/2016', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:40:24+03:00'),
(411, '4620', '03/12/2017', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:40:29+03:00'),
(412, '4620', '02/12/2018', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:40:49+03:00'),
(413, '141', '22/03/2017', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:41:19+03:00'),
(414, '147', '24/08/2017', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T21:41:47+03:00'),
(415, '147', '01/10/2018', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Encashed', '2019-01-09T21:41:58+03:00'),
(416, '148', '02/04/2018', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:42:16+03:00'),
(417, '148', '01/07/2018', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:42:21+03:00'),
(418, '149', '30/07/2017', 'Mohammed Imtiaz', '', '', '', '', '', '', 'Fly', '2019-01-09T21:42:31+03:00'),
(581, '1357', '09/01/2019', 'Mohammed Imtiaz', '08/03/2019', '58', '', '00', 'Vacation', '', 'Fly', '2019-01-16T11:24:43+03:00'),
(420, '1013', '25/08/2003', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T20:05:56+03:00'),
(421, '1013', '22/03/2007', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T20:06:18+03:00'),
(422, '1013', '31/05/2009', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T20:06:25+03:00'),
(423, '1013', '21/05/2011', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Encashed', '2019-01-12T20:06:42+03:00'),
(424, '1013', '07/04/2012', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T20:06:49+03:00'),
(425, '1013', '30/10/2014', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T20:06:55+03:00'),
(426, '1013', '27/05/2017', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T20:07:03+03:00'),
(427, '1041', '06/04/2004', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T20:08:04+03:00'),
(428, '1041', '03/02/2006', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T20:08:09+03:00'),
(429, '1041', '05/12/2007', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T20:08:15+03:00'),
(430, '1041', '14/02/2010', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T20:08:23+03:00'),
(431, '1041', '11/04/2011', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T20:08:29+03:00'),
(432, '1041', '03/05/2012', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T20:08:37+03:00'),
(433, '1041', '10/03/2013', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T20:08:42+03:00'),
(434, '1041', '06/10/2014', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T20:08:47+03:00'),
(435, '1041', '21/04/2015', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T20:08:53+03:00'),
(436, '1041', '31/03/2016', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T20:08:58+03:00'),
(437, '1041', '10/03/2017', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T20:09:07+03:00'),
(438, '1042', '22/04/2004', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T20:10:47+03:00'),
(439, '1042', '05/02/2006', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T20:10:53+03:00'),
(440, '1042', '15/12/2007', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T20:11:01+03:00'),
(441, '1042', '05/02/2010', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T20:11:06+03:00'),
(442, '1042', '25/02/2012', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T20:11:11+03:00'),
(443, '1042', '18/06/2014', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T20:11:16+03:00'),
(444, '1042', '16/04/2015', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T20:11:23+03:00'),
(445, '1042', '31/03/2016', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T20:11:28+03:00'),
(446, '1042', '11/04/2017', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T20:11:35+03:00'),
(447, '1051', '13/08/2006', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Encashed', '2019-01-12T20:18:02+03:00'),
(448, '1051', '09/03/2008', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T20:18:11+03:00'),
(449, '1051', '27/09/2010', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T20:18:16+03:00'),
(450, '1051', '04/01/2012', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Encashed', '2019-01-12T20:18:33+03:00'),
(451, '1051', '16/12/2013', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T20:18:39+03:00'),
(452, '1051', '25/11/2015', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T20:18:45+03:00'),
(453, '1082', '22/12/2005', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Encashed', '2019-01-12T20:20:19+03:00'),
(454, '1082', '22/12/2007', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Encashed', '2019-01-12T20:20:28+03:00'),
(455, '1082', '22/12/2009', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Encashed', '2019-01-12T20:21:04+03:00'),
(456, '1082', '22/12/2011', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Encashed', '2019-01-12T20:21:20+03:00'),
(457, '1082', '03/02/2013', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T20:22:36+03:00'),
(458, '1082', '10/05/2015', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T20:22:52+03:00'),
(459, '1100', '21/11/2005', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:04:42+03:00'),
(460, '1100', '16/03/2008', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:04:49+03:00'),
(461, '1100', '15/07/2011', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:04:54+03:00'),
(462, '1100', '15/11/2011', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Encashed', '2019-01-12T21:05:05+03:00'),
(463, '1100', '19/06/2013', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:05:11+03:00'),
(464, '1100', '05/09/2015', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:05:18+03:00'),
(465, '1126', '24/07/2005', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:09:02+03:00');
INSERT INTO `emp_vacation` (`id`, `emp_id`, `date`, `user_update`, `return_date`, `vacdays`, `arrived_date`, `permit_no`, `remarks`, `review`, `note`, `date_reg`) VALUES
(466, '1126', '24/07/2007', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Encashed', '2019-01-12T21:09:12+03:00'),
(467, '1126', '04/07/2009', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:09:17+03:00'),
(468, '1126', '07/05/2010', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:09:23+03:00'),
(469, '1126', '28/12/2011', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:09:28+03:00'),
(470, '1126', '25/08/2012', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:09:33+03:00'),
(471, '1126', '07/10/2013', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Encashed', '2019-01-12T21:09:45+03:00'),
(472, '1126', '28/09/2014', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Encashed', '2019-01-12T21:09:55+03:00'),
(473, '1126', '19/11/2016', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:10:01+03:00'),
(474, '1145', '28/11/2007', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:11:07+03:00'),
(475, '1145', '20/02/2010', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:11:13+03:00'),
(476, '1145', '03/06/2012', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:11:17+03:00'),
(477, '1145', '06/10/2012', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Encashed', '2019-01-12T21:11:43+03:00'),
(478, '1145', '05/09/2014', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:11:51+03:00'),
(479, '1145', '05/06/2016', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:12:14+03:00'),
(480, '1157', '27/08/2006', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Encashed', '2019-01-12T21:17:09+03:00'),
(481, '1157', '27/08/2009', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Encashed', '2019-01-12T21:17:18+03:00'),
(482, '1157', '05/06/2011', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:17:24+03:00'),
(483, '1157', '12/08/2012', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:17:30+03:00'),
(484, '1157', '08/01/2015', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:17:37+03:00'),
(485, '1157', '27/05/2017', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:17:43+03:00'),
(486, '1209', '15/09/2009', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:20:15+03:00'),
(487, '1209', '09/10/2011', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:20:21+03:00'),
(488, '1209', '13/08/2013', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:20:26+03:00'),
(489, '1209', '05/09/2015', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:20:31+03:00'),
(491, '1245', '26/07/2012', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Encashed', '2019-01-12T21:22:05+03:00'),
(492, '1245', '01/01/2014', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:22:10+03:00'),
(493, '1245', '01/10/2016', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:22:17+03:00'),
(494, '1332', '11/02/2013', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Encashed', '2019-01-12T21:23:35+03:00'),
(495, '1332', '12/12/2015', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:23:41+03:00'),
(496, '1350', '16/02/2014', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:24:31+03:00'),
(497, '1350', '10/02/2016', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:24:36+03:00'),
(498, '1373', '02/09/2013', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:25:23+03:00'),
(499, '1373', '15/08/2015', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:25:28+03:00'),
(500, '1382', '13/04/2014', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:26:18+03:00'),
(501, '1382', '15/05/2016', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:26:44+03:00'),
(502, '1389', '02/11/2014', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:27:41+03:00'),
(503, '1389', '04/12/2016', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:27:46+03:00'),
(504, '1401', '21/04/2015', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:29:01+03:00'),
(505, '1401', '06/03/2017', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:29:06+03:00'),
(506, '4516', '20/08/2017', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:32:09+03:00'),
(507, '4518', '01/03/2018', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:32:58+03:00'),
(508, '4423', '27/05/2017', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:34:44+03:00'),
(509, '4423', '17/06/2018', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:34:49+03:00'),
(510, '4435', '27/05/2017', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:37:49+03:00'),
(511, '4444', '27/05/2017', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:38:28+03:00'),
(512, '4445', '27/05/2017', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:39:07+03:00'),
(513, '4445', '28/10/2017', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:39:12+03:00'),
(514, '4445', '17/06/2018', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:39:17+03:00'),
(515, '4446', '27/05/2017', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:40:04+03:00'),
(516, '4446', '17/06/2018', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:40:14+03:00'),
(517, '4430', '17/06/2018', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:41:37+03:00'),
(518, '5', '10/09/2003', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:42:20+03:00'),
(519, '5', '16/05/2005', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:42:26+03:00'),
(520, '5', '03/08/2006', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:42:32+03:00'),
(521, '5', '18/02/2007', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:42:36+03:00'),
(522, '5', '21/08/2008', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:43:12+03:00'),
(523, '5', '13/06/2009', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:43:16+03:00'),
(524, '5', '03/04/2010', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:43:21+03:00'),
(525, '5', '03/05/2011', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:43:26+03:00'),
(526, '5', '24/03/2012', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:43:30+03:00'),
(527, '5', '05/05/2013', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:43:35+03:00'),
(528, '5', '06/05/2014', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:43:40+03:00'),
(529, '5', '10/05/2015', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:43:44+03:00'),
(530, '5', '07/08/2016', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:43:49+03:00'),
(531, '22', '23/06/2006', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:44:38+03:00'),
(532, '22', '11/07/2008', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:44:42+03:00'),
(533, '22', '01/05/2010', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:44:47+03:00'),
(534, '22', '02/07/2011', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:44:52+03:00'),
(535, '22', '15/08/2012', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:44:56+03:00'),
(536, '22', '13/06/2013', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:45:01+03:00'),
(537, '22', '08/06/2014', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:45:05+03:00'),
(538, '22', '30/06/2016', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:45:09+03:00'),
(539, '22', '30/06/2016', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:45:14+03:00'),
(540, '22', '21/05/2017', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:45:18+03:00'),
(541, '22', '10/09/2018', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:45:23+03:00'),
(542, '31', '24/08/2008', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:46:15+03:00'),
(543, '31', '24/08/2010', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Encashed', '2019-01-12T21:46:25+03:00'),
(544, '31', '01/03/2012', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:46:34+03:00'),
(545, '31', '15/01/2014', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:46:39+03:00'),
(546, '31', '13/07/2014', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:46:44+03:00'),
(547, '31', '12/11/2015', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-12T21:47:06+03:00'),
(548, '31', '17/10/2016', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Encashed', '2019-01-12T21:47:16+03:00'),
(549, '36', '15/02/2009', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-13T17:13:59+03:00'),
(550, '36', '08/01/2012', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-13T17:14:04+03:00'),
(551, '36', '27/10/2013', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Encashed', '2019-01-13T17:14:27+03:00'),
(552, '36', '20/01/2015', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Encashed', '2019-01-13T17:14:38+03:00'),
(553, '36', '20/01/2016', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Encashed', '2019-01-13T17:15:08+03:00'),
(554, '36', '20/01/2017', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Encashed', '2019-01-13T17:15:22+03:00'),
(555, '53', '01/06/2012', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-13T17:16:52+03:00'),
(556, '53', '23/01/2013', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-13T17:16:57+03:00'),
(557, '53', '28/04/2014', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-13T17:17:18+03:00'),
(558, '53', '28/06/2016', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-13T17:17:23+03:00'),
(559, '79', '26/02/2014', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-13T17:18:33+03:00'),
(560, '79', '15/04/2016', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-13T17:18:39+03:00'),
(561, '106', '01/07/2016', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-13T17:19:18+03:00'),
(562, '106', '26/06/2017', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-13T17:19:23+03:00'),
(563, '129', '02/03/2017', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-13T17:20:08+03:00'),
(564, '137', '16/10/2016', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-13T17:21:00+03:00'),
(565, '144', '01/10/2017', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-13T17:21:44+03:00'),
(566, '146', '04/03/2018', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-13T17:23:13+03:00'),
(567, '146', '09/09/2018', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-13T17:23:19+03:00'),
(568, '150', '04/04/2018', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Fly', '2019-01-13T17:24:26+03:00'),
(582, '1020', '16/01/2019', 'Mohammed Imtiaz', '02/02/2019', '17', '', '00', 'Emergency Vacation on own account', '', 'Fly', '2019-01-16T11:26:10+03:00'),
(583, '1323', '09/02/2019', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Encashed', '2019-02-10T14:38:00+03:00'),
(586, '1061', '15/02/2019', 'Mohammed Imtiaz', '15/04/2019', '59', '15/04/2019', '150475640', '', 'C', 'Fly', '2019-02-25T15:27:00+03:00'),
(585, '148', '03/02/2019', 'Mohammed Imtiaz', '24/02/2019', '21', '', '', '', '', 'Fly', '2019-02-10T15:11:56+03:00'),
(587, '42', '05/03/2019', 'Mohammed Imtiaz', '02/04/2019', '28', '', '150724688', '', '', 'Fly', '2019-03-05T16:24:33+03:00'),
(588, '1406', '05/03/2019', 'Mohammed Imtiaz', '23/04/2019', '49', '26/04/2019', '150749270', '', 'C', 'Fly', '2019-03-05T16:25:49+03:00'),
(589, '1249', '05/03/2019', 'Mohammed Imtiaz', '23/04/2019', '49', '29/04/2019', '150749165', '', 'C', 'Fly', '2019-03-05T16:29:00+03:00'),
(590, '49', '06/03/2019', 'Mohammed Imtiaz', 'N/A', 'N/A', '', 'N/A', '', '', 'Encashed', '2019-03-10T11:50:23+03:00'),
(591, '114', '01/04/2019', 'Mohammed Imtiaz', '30/04/2019', '29', '', '', '', 'C', 'Fly', '2019-04-01T09:26:35+03:00'),
(592, '1023', '03/04/2019', 'Mohammed Imtiaz', '02/06/2019', '60', '03/06/2019', '', '', 'C', 'Fly', '2019-04-04T16:20:50+03:00'),
(602, '1075', '15/04/2019', 'Mohammed Imtiaz', '15/05/2019', '30', '15/05/2019', '151770500', '', 'C', 'Fly', '2019-04-16T14:18:34+03:00'),
(608, '4455', '27/04/2019', 'Anees Mughal', '27/05/2019', '30', '27/05/2019', 'N/A', 'Local Vacation', 'C', 'Local Vacation', '2019-04-30T11:14:46+03:00'),
(609, '4519', '20/04/2019', 'Anees Mughal', '20/05/2019', '30', '21/05/2019', 'N/A', 'Local Vacation', 'C', 'Local Vacation', '2019-04-30T11:14:57+03:00'),
(610, '4453', '20/04/2019', 'Anees Mughal', '20/05/2019', '30', '20/05/2019', 'N/A', 'Local Vacation', 'C', 'Local Vacation', '2019-04-30T11:15:06+03:00'),
(611, '4456', '06/05/2019', 'Mohammed Imtiaz ', '05/06/2019', '30', '09/06/2019', 'N/A', 'Annual Vacation', 'C', 'Local Vacation', '2019-05-22T15:13:06+03:00'),
(612, '1088', '06/05/2019', 'Mohammed Imtiaz ', '05/07/2019', '60', '06/07/2019', '152449164', '', 'C', 'Fly', '2019-05-22T15:19:48+03:00'),
(613, '4420', '06/05/2019', 'Mohammed Imtiaz ', '05/06/2019', '30', '09/06/2019', 'N/A', '', 'C', 'Local Vacation', '2019-05-28T11:00:26+03:00'),
(614, '4403', '06/05/2019', 'Mohammed Imtiaz ', '05/06/2019', '30', '09/06/2019', 'N/A', '', 'C', 'Local Vacation', '2019-05-28T11:00:53+03:00'),
(615, '4448', '06/05/2019', 'Mohammed Imtiaz ', '05/06/2019', '30', '09/06/2019', 'N/A', '', 'C', 'Local Vacation', '2019-05-28T11:01:15+03:00'),
(616, '4425', '06/05/2019', 'Mohammed Imtiaz ', '05/06/2019', '30', '09/06/2019', 'N/A', '', 'C', 'Local Vacation', '2019-05-28T11:01:33+03:00'),
(617, '4435', '06/05/2019', 'Mohammed Imtiaz ', '05/06/2019', '30', '09/06/2019', 'N/A', '', 'C', 'Local Vacation', '2019-05-28T11:01:43+03:00'),
(618, '4416', '07/05/2019', 'Mohammed Imtiaz ', '06/07/2019', '60', '02/07/2019', 'N/A', '', 'C', 'Local Vacation', '2019-05-28T11:01:57+03:00'),
(619, '4415', '07/05/2019', 'Mohammed Imtiaz ', '06/07/2019', '60', '06/07/2019', 'N/A', '', 'C', 'Local Vacation', '2019-05-28T11:02:19+03:00'),
(620, '1117', '06/05/2019', 'Mohammed Imtiaz ', '05/07/2019', '60', '06/05/2019', '152451736', '', 'C', 'Fly', '2019-05-28T15:36:17+03:00'),
(621, '1168', '06/05/2019', 'Mohammed Imtiaz ', '05/07/2019', '60', '06/05/2019', '152520372', '', 'C', 'Fly', '2019-05-28T15:37:29+03:00'),
(622, '1133', '06/05/2019', 'Mohammed Imtiaz ', '05/07/2019', '60', '06/05/2019', '152451982', '', 'C', 'Fly', '2019-05-28T15:38:01+03:00'),
(623, '1101', '06/05/2019', 'Mohammed Imtiaz ', '05/07/2019', '60', '23/07/2019', '152453968', '', 'C', 'Fly', '2019-05-28T15:38:42+03:00'),
(624, '1198', '06/05/2019', 'Mohammed Imtiaz ', '05/07/2019', '60', '06/05/2019', '152450621', '', 'C', 'Fly', '2019-05-28T15:39:19+03:00'),
(625, '1064', '06/05/2019', 'Mohammed Imtiaz ', '04/06/2019', '29', '08/06/2019', '152449840', '', 'C', 'Fly', '2019-05-28T15:39:59+03:00'),
(632, '1003', '03/06/2019', 'Anees Mughal', '08/07/2019', '35', '20/07/2019', 'N/A', '', 'C', 'annual', '2019-06-13T13:09:41+03:00'),
(633, '1402', '26/06/2019', 'Mutaz Mufti', 'N/A', 'N/A', '', 'N/A', '', 'C', 'Encashed', '2019-10-10T09:56:31+03:00'),
(634, '29', '15/09/2019', 'Abeer Hassan', '26/10/2019', '42', '26/10/2019', '156585498', '', 'C', 'annual', '2019-11-14T11:37:36+03:00'),
(635, '152', '25/12/2019', 'Anees Mughal', 'N/A', 'N/A', '', 'N/A', '', 'C', 'Encashed', '2019-11-20T12:54:49+03:00'),
(639, '4523', '25/12/2019', 'Anees Mughal', 'N/A', 'N/A', '', 'N/A', '', 'C', 'Encashed', '2020-07-05T11:13:27+03:00'),
(640, '4517', '01/11/2019', 'Anees Mughal', 'N/A', 'N/A', '31/10/2019', 'N/A', '', 'C', '', '2020-07-05T11:14:22+03:00'),
(641, '120', '15/12/2019', 'Anees Mughal', 'N/A', 'N/A', '', 'N/A', '', 'C', 'Encashed', '2020-07-06T11:01:38+03:00'),
(642, '0157', '18/03/2020', 'Abeer Hassan', '26/03/2020', '8', '26/06/2020', 'N/A', '', 'C', 'Local Vacation', '2020-07-12T13:37:36+03:00'),
(643, '148', '17/12/2019', 'Abeer Hassan', 'N/A', '0', '', 'N/A', '', 'C', 'Encashed', '2020-07-12T13:39:30+03:00'),
(644, '148', '01/02/2020', 'Abeer Hassan', '01/03/2020', '29', '01/03/2020', 'N/A', '', 'C', 'Local Vacation', '2020-07-12T13:47:08+03:00'),
(645, '82', '01/07/2020', 'Abeer Hassan', 'N/A', '0', '', 'N/A', '', 'C', 'Encashed', '2020-07-12T13:47:52+03:00'),
(646, '147', '04/12/2019', 'Abeer Hassan', 'N/A', '-18234', '', 'N/A', '', 'C', 'Encashed', '2020-08-18T11:24:52+03:00'),
(647, '1119', '15/07/2019', 'Anees Mughal', 'N/A', '60', '13/09/2019', 'N/A', '', 'C', 'annual', '2020-07-19T15:03:41+03:00'),
(648, '0156', '01/09/2020', 'Daniah Mohammed', 'N/A', '-18506', '', 'N/A', '', 'C', 'Encashed', '2020-09-09T08:43:18+03:00'),
(649, '4403', '01/09/2020', 'Daniah Mohammed', '30/09/2020', '29', '04/10/2020', 'N/A', 'annual vacation year 2020', 'C', 'Local Vacation', '2020-09-09T08:46:47+03:00'),
(650, '147', '01/09/2020', 'Daniah Mohammed', 'N/A', '-18506', '', 'N/A', '', 'C', 'Encashed', '2020-09-09T08:48:07+03:00'),
(651, '1026', '01/09/2020', 'Daniah Mohammed', '20/10/2020', '49', '01/10/2020', 'N/A', '', 'C', 'Local Vacation', '2020-09-09T08:48:47+03:00'),
(652, '1098', '20/02/2020', 'Daniah Mohammed', 'N/A', '60', '05/10/2020', 'N/A', '', 'C', 'annual', '2020-09-09T08:50:56+03:00'),
(653, '1146', '20/02/2020', 'Daniah Mohammed', 'N/A', '60', '', 'N/A', '', 'A', 'annual', '2020-09-09T08:51:11+03:00'),
(654, '1114', '01/03/2020', 'Daniah Mohammed', 'N/A', '50', '29/04/2020', 'N/A', '', 'C', 'annual', '2020-09-09T08:54:11+03:00'),
(655, '1178', '10/02/2020', 'Daniah Mohammed', 'N/A', '50', '09/04/2020', 'N/A', '', 'C', '', '2020-09-09T08:54:32+03:00'),
(656, '1024', '25/12/2019', 'Daniah Mohammed', 'N/A', '-18255', '', 'N/A', '', 'C', 'Encashed', '2020-09-09T08:55:12+03:00'),
(657, '1020', '02/01/2020', 'Daniah Mohammed', 'N/A', '30', '', 'N/A', '', 'A', 'annual', '2020-09-09T08:55:29+03:00'),
(658, '1402', '20/12/2019', 'Daniah Mohammed', 'N/A', '-18250', '', 'N/A', '', 'C', 'Encashed', '2020-09-09T08:55:53+03:00'),
(659, '1412', '20/12/2019', 'Daniah Mohammed', 'N/A', '-18250', '', 'N/A', '', 'C', 'Encashed', '2020-09-09T08:56:07+03:00'),
(660, '4620', '15/12/2019', 'Daniah Mohammed', 'N/A', '27', '15/01/2020', 'N/A', '', 'C', 'annual', '2020-09-09T08:56:43+03:00'),
(661, '1405', '30/12/2019', 'Daniah Mohammed', 'N/A', '58', '26/02/2020', 'N/A', '', 'C', 'annual', '2020-09-09T08:57:06+03:00'),
(662, '1411', '30/11/2019', 'Daniah Mohammed', 'N/A', '60', '', 'N/A', '', 'C', 'Encashed', '2020-09-09T08:57:46+03:00'),
(663, '1359', '21/10/2019', 'Daniah Mohammed', 'N/A', '60', '20/12/2019', 'N/A', '', 'C', 'annual', '2020-09-09T08:58:11+03:00'),
(664, '1252', '25/11/2019', 'Daniah Mohammed', 'N/A', '59', '23/01/2020', 'N/A', '', 'C', 'annual', '2020-09-09T08:58:31+03:00'),
(665, '4522', '16/06/2019', 'Daniah Mohammed', 'N/A', '29', '', 'N/A', '', 'A', 'annual', '2020-09-09T08:58:51+03:00'),
(666, '1113', '02/10/2019', 'Daniah Mohammed', 'N/A', '63', '', 'N/A', '', 'A', 'annual', '2020-09-09T08:59:08+03:00'),
(667, '152', '30/09/2020', 'Daniah Mohammed', 'N/A', '-18535', '', 'N/A', '', 'C', 'Encashed', '2020-09-20T09:18:34+03:00'),
(668, '4435', '01/10/2020', 'Daniah Mohammed', '31/10/2020', '30', '01/11/2020', 'N/A', '04/10/2020', 'C', 'Local Vacation', '2020-10-06T08:36:39+03:00'),
(669, '0157', '01/10/2020', 'Daniah Mohammed', '30/10/2020', '29', '31/10/2020', 'N/A', '', 'C', 'Local Vacation', '2020-10-13T15:12:23+03:00'),
(671, '4525', '01/11/2020', 'Daniah Mohammed', '30/11/2020', '29', '', 'N/A', 'anuual vacation year 2020', 'A', 'Local Vacation', '2020-11-03T16:06:24+03:00');

-- --------------------------------------------------------

--
-- Table structure for table `expiration`
--

CREATE TABLE IF NOT EXISTS `expiration` (
  `firstname` varchar(50) NOT NULL,
  `lastname` varchar(50) NOT NULL,
  `mail` varchar(255) NOT NULL,
  `registerdate` text NOT NULL,
  `expirationdate` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `expiration`
--

INSERT INTO `expiration` (`firstname`, `lastname`, `mail`, `registerdate`, `expirationdate`) VALUES
('Enrique ', 'Iglesias', 'enrique@iglesias.com', '2018/07/26', '2019/07/26'),
('John', 'Cena', 'john@cena.com', '2018/07/26', '2020/07/26'),
('Justin', 'Bieber', 'justin@bieber.com', '2018/07/26', '2017/07/26');

-- --------------------------------------------------------

--
-- Table structure for table `gosi_emp`
--

CREATE TABLE IF NOT EXISTS `gosi_emp` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `emp_id` varchar(150) NOT NULL,
  `gosi_no` varchar(100) NOT NULL,
  `amount` varchar(100) NOT NULL,
  `date_greg` varchar(100) NOT NULL,
  `date_hijri` varchar(100) NOT NULL,
  `date_reg` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=102 ;

--
-- Dumping data for table `gosi_emp`
--

INSERT INTO `gosi_emp` (`id`, `emp_id`, `gosi_no`, `amount`, `date_greg`, `date_hijri`, `date_reg`) VALUES
(1, '82', '930821470', '4000', '', '01/01/1434', '2019-04-22T15:01:09+03:00'),
(2, '84', '932201062', '2813', '', '17/08/1434', '2019-04-22T15:05:47+03:00'),
(3, '1011', '836984633', '1250', '', '22/01/1422', '2019-04-22T15:15:26+03:00'),
(4, '1331', '905443798', '1250', '', '01/01/1433', '2019-04-22T15:17:22+03:00'),
(5, '1359', '945992085', '1250', '', '01/07/1434', '2019-04-22T15:18:19+03:00'),
(6, '1323', '629398074', '2063', '01/06/2018', '17/09/1439', '2019-04-22T15:20:28+03:00'),
(7, '1411', '904505897', '1700', '', '01/03/1437', '2019-04-22T15:24:40+03:00'),
(8, '4416', '889333901', '2250', '', '25/11/1434', '2019-04-22T15:26:03+03:00'),
(9, '4415', '962007171', '2250', '', '01/12/1434', '2019-04-22T15:27:10+03:00'),
(10, '1002', '820400869', '1250', '', '22/01/1422', '2019-04-22T15:28:20+03:00'),
(11, '1357', '836984080', '1700', '', '22/01/1422', '2019-04-22T15:29:37+03:00'),
(12, '1003', '836984293', '2813', '', '22/01/1422', '2019-04-22T15:31:07+03:00'),
(13, '1077', '845177449', '2063', '', '01/01/1424', '2019-04-22T15:33:59+03:00'),
(14, '1055', '845178143', '2063', '', '01/01/1424', '2019-04-22T15:41:26+03:00'),
(15, '1067', '845178577', '1250', '', '01/01/1424', '2019-04-22T15:42:37+03:00'),
(16, '1062', '845178712', '1250', '', '01/01/1424', '2019-04-22T15:44:15+03:00'),
(17, '1061', '845178739', '1250', '', '01/01/1424', '2019-04-22T15:45:11+03:00'),
(18, '1064', '845178755', '1250', '', '01/01/1424', '2019-04-22T15:46:17+03:00'),
(19, '1070', '845187401', '1650', '', '01/01/1424', '2019-04-22T15:47:17+03:00'),
(20, '1148', '848486914', '1250', '', '01/07/1434', '2019-04-22T15:49:15+03:00'),
(21, '2', '362910005', '26015', '', '01/06/1424', '2019-04-22T15:52:36+03:00'),
(22, '120', '369109022', '12500', '', '25/04/1436', '2019-04-22T15:53:41+03:00'),
(23, '148', '373617040', '6481', '', '02/05/1438', '2019-04-22T15:54:49+03:00'),
(24, '153', '407770870', '2778', '25/10/2018', '16/02/1440', '2019-04-22T16:01:42+03:00'),
(25, '4620', '379157297', '7460', '', '05/11/1436', '2019-04-22T16:03:27+03:00'),
(26, '0154', '381977390', '4629', '10/02/2019', '05/06/1440', '2019-04-22T16:05:15+03:00'),
(27, '4517', '384611583', '3704', '', '16/12/1437', '2019-04-22T16:08:30+03:00'),
(28, '4522', '385802579', '3704', '', '19/12/1438', '2019-04-22T16:11:10+03:00'),
(29, '4425', '388624949', '4166', '', '16/10/1435', '2019-04-22T16:21:06+03:00'),
(30, '4519', '389539694', '3704', '', '02/05/1438', '2019-04-22T16:23:13+03:00'),
(31, '4403', '393953845', '4000', '', '01/05/1434', '2019-04-22T16:24:32+03:00'),
(32, '4523', '398008243', '3704', '14/10/2018', '05/02/1440', '2019-04-22T16:26:22+03:00'),
(33, '4435', '398106199', '3704', '', '02/06/1436', '2019-04-22T16:27:32+03:00'),
(34, '4420', '400746397', '3704', '', '01/06/1435', '2019-04-22T16:28:42+03:00'),
(35, '4453', '407354087', '3704', '28/03/2018', '11/07/1439', '2019-04-22T16:30:19+03:00'),
(36, '4448', '409148840', '3704', '', '08/02/1439', '2019-04-22T16:31:18+03:00'),
(37, '149', '411955028', '3704', '', '01/10/1438', '2019-04-23T16:00:25+03:00'),
(38, '4452', '413574048', '3704', '28/03/2018', '11/07/1439', '2019-04-23T16:04:46+03:00'),
(39, '4455', '413574714', '3704', '28/03/2018', '11/07/1439', '2019-04-23T16:07:14+03:00'),
(40, '4451', '414834612', '3704', '28/03/2018', '11/07/1439', '2019-04-23T16:08:54+03:00'),
(41, '4454', '414834795', '3704', '28/03/2018', '11/07/1439', '2019-04-23T16:10:02+03:00'),
(42, '4456', '416939454', '3704', '01/10/2018', '21/01/1440', '2019-04-23T16:12:31+03:00'),
(43, '4449', '416939551', '3704', '29/09/2018', '19/01/1440', '2019-04-23T16:14:14+03:00'),
(44, '0155', '419983926', '3241', '08/04/2019', '03/08/1440', '2019-04-23T16:15:50+03:00'),
(45, '1114', '864531547', '2250', '', '01/04/1428', '2019-04-23T16:17:22+03:00'),
(46, '1101', '864531792', '1250', '', '01/04/1428', '2019-04-23T16:22:46+03:00'),
(47, '1163', '864532160', '1250', '', '01/04/1428', '2019-04-23T16:24:08+03:00'),
(48, '1113', '864532535', '2971', '', '01/04/1428', '2019-04-23T16:26:01+03:00'),
(49, '1168', '864533035', '1250', '', '01/04/1428', '2019-04-23T16:26:59+03:00'),
(50, '1133', '864536514', '1250', '', '01/04/1428', '2019-04-23T16:27:59+03:00'),
(51, '1112', '864537944', '1250', '', '01/01/1428', '2019-04-23T16:29:51+03:00'),
(52, '1098', '864543944', '1250', '', '01/04/1428', '2019-04-23T16:30:55+03:00'),
(53, '1119', '864544495', '1250', '', '01/04/1428', '2019-04-23T16:31:51+03:00'),
(54, '1117', '864544592', '1250', '', '01/04/1428', '2019-04-23T16:32:46+03:00'),
(55, '1198', '876233029', '1250', '', '01/11/1429', '2019-04-23T16:34:18+03:00'),
(56, '1217', '876244454', '1250', '', '01/11/1429', '2019-04-23T16:35:22+03:00'),
(57, '1362', '909313538', '1250', '', '15/12/1434', '2019-04-24T14:50:47+03:00'),
(58, '1193', '918134301', '1250', '', '16/07/1434', '2019-04-24T14:51:38+03:00'),
(59, '1252', '937656416', '1250', '', '08/08/1434', '2019-04-24T14:52:39+03:00'),
(60, '1403', '962434126', '1650', '', '01/12/1434', '2019-04-24T14:53:29+03:00'),
(61, '1007', '836984153', '1650', '', '22/01/1422', '2019-04-24T14:54:23+03:00'),
(62, '1038', '842119383', '6875', '', '01/06/1423', '2019-04-25T09:34:00+03:00'),
(63, '1021', '842119421', '2063', '', '01/06/1423', '2019-04-25T09:35:14+03:00'),
(64, '9', '845177260', '8125', '', '01/01/1424', '2019-04-25T09:38:04+03:00'),
(65, '1088', '845177473', '2813', '', '01/01/1424', '2019-04-25T09:40:14+03:00'),
(66, '1084', '845177538', '1250', '', '01/01/1424', '2019-04-25T09:41:24+03:00'),
(67, '1020', '845178828', '3750', '', '01/01/1424', '2019-04-25T09:42:32+03:00'),
(68, '10', '845179190', '8125', '', '01/01/1424', '2019-04-25T09:43:44+03:00'),
(69, '1023', '845181349', '2250', '', '01/01/1424', '2019-04-25T09:44:36+03:00'),
(70, '1075', '845185409', '2813', '', '01/01/1424', '2019-04-25T09:45:54+03:00'),
(71, '18', '864526071', '3750', '', '01/04/1428', '2019-04-25T09:47:28+03:00'),
(72, '1136', '864538576', '1650', '', '01/04/1428', '2019-04-25T09:48:31+03:00'),
(73, '1146', '864544398', '2063', '', '01/04/1428', '2019-04-25T09:49:22+03:00'),
(74, '1249', '896742116', '1650', '', '01/01/1434', '2019-04-25T09:50:11+03:00'),
(75, '1351', '905451995', '1642', '', '01/01/1433', '2019-04-25T09:51:04+03:00'),
(76, '29', '906577046', '5625', '', '01/01/1434', '2019-04-25T09:51:57+03:00'),
(77, '1248', '930817236', '1650', '', '01/01/1434', '2019-04-25T09:52:54+03:00'),
(78, '1243', '930818054', '2186', '', '01/01/1434', '2019-04-25T09:53:48+03:00'),
(79, '1406', '949722104', '1700', '', '20/11/1434', '2019-04-25T09:54:39+03:00'),
(80, '1244', '960653149', '1650', '', '01/11/1434', '2019-04-25T09:55:37+03:00'),
(81, '1410', '991336788', '1250', '', '02/11/1436', '2019-04-25T09:56:43+03:00'),
(82, '141', '998481880', '2063', '', '01/03/1437', '2019-04-25T09:57:42+03:00'),
(83, '42', '876244578', '3750', '', '01/11/1429', '2019-04-25T09:58:48+03:00'),
(84, '1178', '923809376', '2063', '', '01/03/1437', '2019-04-25T09:59:28+03:00'),
(85, '152', '929902351', '4629', '25/10/2018', '16/02/1440', '2019-04-25T10:00:48+03:00'),
(86, '49', '930819220', '2063', '', '01/01/1434', '2019-04-25T10:07:03+03:00'),
(87, '1405', '959418446', '2125', '', '01/01/1434', '2019-04-25T10:07:48+03:00'),
(88, '114', '978579027', '2813', '', '06/07/1436', '2019-04-25T10:08:44+03:00'),
(89, '1400', '601556154', '1250', '', '01/04/1437', '2019-04-25T10:09:31+03:00'),
(90, '1402', '601556278', '1250', '', '01/04/1437', '2019-04-25T10:10:15+03:00'),
(91, '1395', '997999924', '1250', '', '01/03/1437', '2019-04-25T10:10:56+03:00'),
(92, '1413', '986201904', '1250', '', '01/05/1436', '2019-04-25T10:11:47+03:00'),
(93, '1024', '997999932', '2813', '', '01/03/1437', '2019-04-25T10:12:24+03:00'),
(94, '1412', '903067810', '1700', '', '01/03/1437', '2019-04-25T10:13:11+03:00'),
(95, '1026', '986191933', '2063', '', '01/05/1436', '2019-04-25T10:13:47+03:00'),
(96, '4525', '412561368', '3704', '24/07/2019', '21/10/1440', '2019-09-09T15:38:46+03:00'),
(97, '156', '389351199', '450', '', '02/01/1441', '2019-09-24T15:46:39+03:00'),
(98, '4632', '419581917', '370', '03/12/2019', '06/04/1441', '2019-12-11T15:53:27+03:00'),
(99, '4628', '422496564', '370', '04/11/2019', '07/03/1441', '2019-12-11T15:55:46+03:00'),
(100, '4631', '422299920', '370', '07/11/2019', '10/03/1441', '2019-12-11T15:57:38+03:00'),
(101, '0156', '389351199', '450', '01/09/2019', 'NaN/12/1355', '2020-06-02T12:06:23+03:00');

-- --------------------------------------------------------

--
-- Table structure for table `history_store`
--

CREATE TABLE IF NOT EXISTS `history_store` (
  `timemark` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `table_name` tinytext COLLATE utf8_bin NOT NULL,
  `pk_date_src` text COLLATE utf8_bin NOT NULL,
  `pk_date_dest` text COLLATE utf8_bin NOT NULL,
  `record_state` int(11) NOT NULL,
  PRIMARY KEY (`table_name`(100),`pk_date_dest`(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Dumping data for table `history_store`
--


-- --------------------------------------------------------

--
-- Table structure for table `machines`
--

CREATE TABLE IF NOT EXISTS `machines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `m_id` varchar(255) NOT NULL,
  `name_mach` varchar(255) NOT NULL,
  `maker_name` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `made_year` varchar(255) NOT NULL,
  `remarks` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'A',
  `serial` varchar(255) NOT NULL,
  `date_reg` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=137 ;

--
-- Dumping data for table `machines`
--

INSERT INTO `machines` (`id`, `m_id`, `name_mach`, `maker_name`, `location`, `made_year`, `remarks`, `status`, `serial`, `date_reg`) VALUES
(1, 'EM2H001', 'ESPRESSO MACHINE 2 Hole', 'Bazzera', 'JM 01', '2003', '', 'A', 'AN00732613', '2020-10-07T10:49:09+03:00'),
(2, 'SM3H004', 'SLUSH MACHINE 3 hls', 'Ugolini', 'JM 01', '2003', '', 'A', '447875', '2020-10-07T11:03:45+03:00'),
(3, 'EM2H001', 'ESPRESSO MACHINE 2 Hole', 'Bazzera', 'JM 03', '2003', '', 'A', 'AN005615305', '2020-10-07T12:34:32+03:00'),
(4, 'SM3H004', 'SLUSH MACHINE 3 hls', 'Ugolini', 'JM 38', '2003', '', 'A', '466002', '2020-10-07T12:42:41+03:00'),
(5, 'EM3H002', 'ESPRESSO MACHINE 3 Hole', 'Bazzera', 'JM 06', '2002', '', 'A', '22082', '2020-10-07T12:43:53+03:00'),
(6, 'SM3H004', 'SLUSH MACHINE 3 hls', 'Ugolini', 'JM 19', '2003', '', 'A', '176890', '2020-10-07T12:45:39+03:00'),
(7, 'SM2H003', 'SLUSH MACHINE 2 hls', 'Ugolini', 'JM 06', '2002', '', 'A', '153504', '2020-10-07T12:47:24+03:00'),
(8, 'EM3H002', 'ESPRESSO MACHINE 3 Hole', 'Bazzera', 'JM 08', '2002', '', 'A', 'ANCC2000-17', '2020-10-07T12:48:39+03:00'),
(9, 'EM2H001', 'ESPRESSO MACHINE 2 Hole', 'Bazzera', 'JM 10', '2004', '', 'A', 'AM005363004', '2020-10-07T12:49:24+03:00'),
(10, 'EM3H002', 'ESPRESSO MACHINE 3 Hole', 'Bazzera', 'JM 11', '2007', '', 'A', 'AM006055307', '2020-10-07T12:50:26+03:00'),
(11, 'EM3H002', 'ESPRESSO MACHINE 3 Hole', 'Bazzera', 'JM 12', '2005', '', 'A', '44814', '2020-10-07T13:09:18+03:00'),
(12, 'EM2H001', 'ESPRESSO MACHINE 2 Hole', 'Bazzera', 'JM 14', '2001', '', 'A', 'AM007731313', '2020-10-07T13:12:54+03:00'),
(13, 'EM2H001', 'ESPRESSO MACHINE 2 Hole', 'Bazzera', 'JM 15', '2002', '', 'A', 'ANCC2002-11', '2020-10-07T14:08:13+03:00'),
(14, 'EM3H002', 'ESPRESSO MACHINE 3 Hole', 'Bazzera', 'JM 16', '2002', '', 'A', '44819', '2020-10-07T14:10:11+03:00'),
(15, 'EM2H001', 'ESPRESSO MACHINE 2 Hole', 'Bazzera', 'JM 17', '2005', '', 'A', 'AM007987214', '2020-10-07T14:12:52+03:00'),
(16, 'EM2H001', 'ESPRESSO MACHINE 2 Hole', 'Bazzera', 'JM 18', '2001', '', 'A', 'AN007731313', '2020-10-07T14:18:11+03:00'),
(17, 'EM3H002', 'ESPRESSO MACHINE 3 Hole', 'Bazzera', 'JM 19', '2009', '', 'A', '48119', '2020-10-07T14:19:19+03:00'),
(18, 'EM3H002', 'ESPRESSO MACHINE 3 Hole', 'Bazzera', 'JM 20', '2002', 'comp.srv.done fm hls.July.2019', 'A', '44815', '2020-10-07T14:21:16+03:00'),
(19, 'EM2H001', 'ESPRESSO MACHINE 2 Hole', 'Bazzera', 'JM 21', '2003', 'comp.srv.done fm hls.Feb.2019', 'A', 'AM005945506', '2020-10-07T14:24:08+03:00'),
(20, 'EM3H002', 'ESPRESSO MACHINE 3 Hole', 'Bazzera', 'JM 22', '2002', '', 'A', '49225', '2020-10-07T14:42:52+03:00'),
(21, 'EM3H002', 'ESPRESSO MACHINE 3 Hole', 'Bazzera', 'JM 23', '2006', '', 'A', '50903', '2020-10-07T14:44:06+03:00'),
(22, 'EM3H002', 'ESPRESSO MACHINE 3 Hole', 'Bazzera', 'JM 24', '2004', '', 'A', '50915', '2020-10-07T14:52:05+03:00'),
(23, 'EM3H002', 'ESPRESSO MACHINE 3 Hole', 'Bazzera', 'JM 26', '2002', '', 'A', 'ANCC200217', '2020-10-07T14:53:54+03:00'),
(24, 'EM2H001', 'ESPRESSO MACHINE 2 Hole', 'Bazzera', 'JM 28', '2002', '', 'A', '46440', '2020-10-07T14:55:26+03:00'),
(25, 'EM3H002', 'ESPRESSO MACHINE 3 Hole', 'Bazzera', 'JM 29', '2002', '', 'A', 'ANCC200229', '2020-10-07T14:58:19+03:00'),
(26, 'EM2H001', 'ESPRESSO MACHINE 2 Hole', 'Bazzera', 'JM 31', '2007', '', 'A', 'AM0063535808', '2020-10-07T15:11:08+03:00'),
(27, 'EM3H002', 'ESPRESSO MACHINE 3 Hole', 'Bazzera', 'JM 32', '2001', '', 'A', 'ANCC2009-17', '2020-10-07T15:13:36+03:00'),
(28, 'EM2H001', 'ESPRESSO MACHINE 2 Hole', 'Bazzera', 'JM 33', '2001', '', 'A', 'AM00545606', '2020-10-07T15:15:53+03:00'),
(29, 'EM2H001', 'ESPRESSO MACHINE 2 Hole', 'Bazzera', 'JM 34', '2002', '', 'A', 'AM005614905', '2020-10-07T15:17:46+03:00'),
(30, 'EM3H002', 'ESPRESSO MACHINE 3 Hole', 'Bazzera', 'JM 38', '2001', '', 'A', '50928', '2020-10-07T15:18:52+03:00'),
(31, 'EM3H002', 'ESPRESSO MACHINE 3 Hole', 'Bazzera', 'JM 39', '2006', '', 'A', '43222', '2020-10-07T15:20:03+03:00'),
(32, 'EM2H001', 'ESPRESSO MACHINE 2 Hole', 'Bazzera', 'JMUM 01', '2002', '', 'A', 'AM005363004', '2020-10-07T15:21:33+03:00'),
(33, 'EM3H002', 'ESPRESSO MACHINE 3 Hole', 'Bazzera', 'JMUM 02', '2006', '', 'A', 'M2/5520512', '2020-10-07T15:24:56+03:00'),
(34, 'EM3H002', 'ESPRESSO MACHINE 3 Hole', 'Bazzera', 'JMUM 03', '2006', '', 'A', '40053', '2020-10-07T15:26:55+03:00'),
(35, 'EM3H002', 'ESPRESSO MACHINE 3 Hole', 'Bazzera', 'JMUM 05', '2006', '', 'A', '46457', '2020-10-07T15:30:20+03:00'),
(36, 'EM3H002', 'ESPRESSO MACHINE 3 Hole', 'Cimbali', 'JMUM 04', '2012', '', 'A', '1297427', '2020-10-07T15:31:16+03:00'),
(37, 'EM3H002', 'ESPRESSO MACHINE 3 Hole', 'Bazzera', 'JMUBTF 01', '2005', '', 'A', '40246', '2020-10-07T15:32:45+03:00'),
(38, 'EM2H001', 'ESPRESSO MACHINE 2 Hole', 'Bazzera', 'JMUF 03', '2003', '', 'A', 'AM007987914', '2020-10-07T15:34:24+03:00'),
(39, 'EM3H002', 'ESPRESSO MACHINE 3 Hole', 'Bazzera', 'JMUBT 01', '2003', '', 'A', 'AM007730113', '2020-10-08T11:37:42+03:00'),
(40, 'EM3H002', 'ESPRESSO MACHINE 3 Hole', 'Bazzera', 'JMUBT 02 ', '2003', '', 'A', 'AM0077325', '2020-10-08T11:39:37+03:00'),
(41, 'EM3H002', 'ESPRESSO MACHINE 3 Hole', 'Bazzera', 'JMUBT 03', '2004', '', 'A', 'AM007626613', '2020-10-08T11:40:58+03:00'),
(42, 'EM2H001', 'ESPRESSO MACHINE 2 Hole', 'Bazzera', 'JM 04', '2007', '', 'A', 'AM0079881014', '2020-10-08T11:42:24+03:00'),
(43, 'EM3H002', 'ESPRESSO MACHINE 3 Hole', 'Bazzera', 'MM 01', '2002', '', 'A', '40250.220', '2020-10-08T11:44:08+03:00'),
(44, 'EM2H001', 'ESPRESSO MACHINE 2 Hole', 'Bazzera', 'MM 02', '2009', 'Srvce.done 2018', 'A', 'AM006556309', '2020-10-08T11:47:45+03:00'),
(45, 'EM2H001', 'ESPRESSO MACHINE 2 Hole', 'Bazzera', 'MM 03', '2009', '', 'A', 'AM006556109', '2020-10-08T11:49:22+03:00'),
(46, 'EM3H002', 'ESPRESSO MACHINE 3 Hole', 'Bazzera', 'MM 05', '2002', 'Srvce.done 2018', 'A', '44816.220', '2020-10-08T11:50:57+03:00'),
(47, 'EM3H002', 'ESPRESSO MACHINE 3 Hole', 'Bazzera', 'YM 01', '2005', '', 'A', 'AM005992307', '2020-10-08T11:57:16+03:00'),
(48, 'EM2H001', 'ESPRESSO MACHINE 2 Hole', 'Bazzera', 'YM 02', '2001', '', 'A', '206433250', '2020-10-08T11:58:54+03:00'),
(49, 'EM2H001', 'ESPRESSO MACHINE 2 Hole', 'Bazzera', 'YM 03', '2001', '', 'A', '260943750', '2020-10-08T12:00:23+03:00'),
(50, 'EM2H001', 'ESPRESSO MACHINE 2 Hole', 'Bazzera', 'YM 04', '2002', 'Ym04 closed shop as a spare in yanbu', 'A', '261123750', '2020-10-08T12:32:33+03:00'),
(51, 'EM2H001', 'ESPRESSO MACHINE 2 Hole', 'Bazzera', 'YUM 01', '2005', 'Transfer to Jeddah Coffee Store', 'A', 'AM007625813', '2020-10-08T12:34:14+03:00'),
(53, 'ORG', 'Orange Machine', 'CITROCASA', 'JM 01', '2013', '', 'A', '100-727', '2020-10-12T12:09:01+03:00'),
(54, 'ICM', 'Ice Cub Machine ', 'MIGEL', 'JM 01', '2013', '', 'A', 'NO1328234', '2020-10-12T12:52:54+03:00'),
(55, 'ICMWP', 'Ice Machine Water Pump', 'FLOJET', 'JM 01', '2013', '', 'A', 'AJ1PUMP', '2020-10-12T12:57:44+03:00'),
(57, 'RFGR', ' Refrigerator  ', 'GENERAL SUPER', 'JM 01', '2010', '', 'A', 'AJ1TRFGR', '2020-10-12T13:52:53+03:00'),
(58, 'RFGR', ' Refrigerator  ', 'SANYO', 'JM 01', '2013', '', 'A', '10701260', '2020-10-12T13:58:15+03:00'),
(59, 'HTR', 'Heater', 'GEEPAS', 'JM 01', '2018', '', 'A', '5B15050629', '2020-10-12T14:08:17+03:00'),
(60, 'TSTR', 'Toaster', 'TEFAL', 'JM 01', '2015', '', 'A', 'AJ1TSTR', '2020-10-12T14:10:14+03:00'),
(61, 'GRDR ', 'Grinder Machine', 'Bazzera', 'JM 01', '2010', '', 'A', 'MD001021418', '2020-10-14T14:53:26+03:00'),
(62, 'EM2H001', 'ESPRESSO MACHINE 2 Hole', 'Bazzera', 'JM 02', '2000', 'Srvce.done 2020', 'A', '26112', '2020-10-14T15:07:14+03:00'),
(63, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'JM 02', '2003', 'Transfered from UM3 to JM2 16-5-19(Repaired Cost sr 2296)', 'A', '291004', '2020-10-14T15:08:37+03:00'),
(64, 'ORG', 'Orange Machine', 'CITROCASA', 'JM 02', '2012', '', 'A', '100570', '2020-10-14T15:09:31+03:00'),
(65, 'DRFGR', ' Display Refrigerator 142*80*142', 'Hisense', 'JM 02', '2012', '', 'A', 'BC100BHE52', '2020-10-14T15:15:51+03:00'),
(66, 'HTR', 'Heater', 'Unknown', 'JM 02', '2019', '', 'A', 'K1S91F9E', '2020-10-14T15:17:13+03:00'),
(67, 'OVNS', 'Oven Small ', 'Penasonic', 'JM 02', '2019', '', 'A', '1007688', '2020-10-14T15:19:53+03:00'),
(68, 'FLC', 'Fly Catcher', 'ICCUME', 'JM 02', '2019', '', 'A', '10328', '2020-10-14T15:21:16+03:00'),
(69, 'WMAPE', ' Water Motor,Auto Pump ESP.', 'SAMNAN', 'JM 02', '2019', '', 'A', 'AJ2WMAPE', '2020-10-14T15:24:20+03:00'),
(70, 'WMAPW', ' Water Motor,Auto Pump WTR.', 'SAMNAN', 'JM 02', '2019', '', 'A', 'AJ2WMAPW', '2020-10-14T15:26:31+03:00'),
(71, 'SU.AC', 'Air Condition ', 'Unknown', 'JM 02', '2019', '', 'A', 'AJ2SU.AC', '2020-10-14T15:31:20+03:00'),
(73, 'EM2H001', 'ESPRESSO MACHINE 2 Hole', 'Bazzera', 'MM 04', '1-9-2020', '', 'A', 'AM009935819', '2020-10-19T12:40:24+03:00'),
(74, 'SM3H004', 'SLUSH MACHINE 3 Hls', 'Ugolini', 'MM 04', '1-9-2020', '', 'A', '2.001E+09', '2020-10-19T15:03:29+03:00'),
(75, 'GRDR', 'Grinder Machine', 'Unknown', 'MM 04', '2008', '', 'A', 'BB090ATOR05', '2020-10-19T15:05:06+03:00'),
(76, 'SM3H004', 'SLUSH MACHINE 3 Hls', 'Ugolini', 'JM 08', '2006', 'Transfered from UM1 to JM8 16-6-19 (repaired cost sr 4141)', 'A', '219601', '2020-10-19T15:15:04+03:00'),
(77, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Unknown', 'JM 11', '2006', '', 'A', '445609', '2020-10-19T15:16:18+03:00'),
(78, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'JM 12', '2006', 'Transfered from UBT2 to JM12 10-6-19(Repaired Cost sr 1885)', 'A', '398702', '2020-10-19T15:17:22+03:00'),
(79, 'SM3H004', 'SLUSH MACHINE 3 Hls', 'Ugolini', 'JM 15', '2009', '', 'A', '447878', '2020-10-19T15:18:39+03:00'),
(80, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'JM 16', '2002', '', 'A', '441929', '2020-10-19T15:20:40+03:00'),
(81, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'JM 16', '2002', '', 'A', '153499', '2020-10-19T15:22:00+03:00'),
(82, 'SM3H004', 'SLUSH MACHINE 3 Hls', 'Ugolini', 'JM 17', '2005', '', 'A', '350699', '2020-10-19T15:22:42+03:00'),
(83, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'JM 18', '2002', '', 'A', '323288', '2020-10-19T15:23:24+03:00'),
(84, 'SM3H004', 'SLUSH MACHINE 3 Hls', 'Ugolini', 'JM 18', '2004', '', 'A', '377662', '2020-10-19T15:23:58+03:00'),
(85, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'JM 20', '2003', '', 'A', '123620', '2020-10-19T15:32:07+03:00'),
(86, 'SM3H004', 'SLUSH MACHINE 3 Hls', 'Ugolini', 'JM 21', '2008', '', 'A', '523625', '2020-10-19T15:32:53+03:00'),
(87, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'JM 22', '2002', '', 'A', '176634', '2020-10-19T15:34:07+03:00'),
(88, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'JM 22', '2002', '', 'A', '175533', '2020-10-19T15:34:44+03:00'),
(89, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'JM 23', '2003', '', 'A', '201754', '2020-10-19T15:44:02+03:00'),
(90, 'SM3H004', 'SLUSH MACHINE 3 Hls', 'Ugolini', 'JM 24', '2005', '', 'A', '219614', '2020-10-19T15:44:36+03:00'),
(91, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'JM 26', '2010', '', 'A', '470214', '2020-10-19T15:45:29+03:00'),
(92, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'JM 28', '2001', '', 'A', 'FAEM.MD2.SM.01', '2020-10-19T15:46:21+03:00'),
(93, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'JM 29', '2005', 'Repaired date 22-11-18 &  Cost sr 860', 'A', '445580', '2020-10-19T15:47:32+03:00'),
(94, 'SM3H004', 'SLUSH MACHINE 3 Hls', 'Ugolini', 'JM 32', '2010', '', 'A', '176847', '2020-10-19T15:48:24+03:00'),
(95, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'JM 33', '2005', '', 'A', '398703', '2020-10-19T15:49:01+03:00'),
(96, 'SM3H004', 'SLUSH MACHINE 3 Hls', 'Ugolini', 'JM 34', '2009', '', 'A', '470314', '2020-10-19T15:49:43+03:00'),
(97, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'Store Coffee', '2002', '', 'A', '465988', '2020-10-19T15:50:27+03:00'),
(98, 'SM3H004', 'SLUSH MACHINE 3 Hls', 'Ugolini', 'Store Coffee', '2004', '', 'A', '1219617', '2020-10-19T15:51:06+03:00'),
(99, 'SM3H004', 'SLUSH MACHINE 3 Hls', 'Ugolini', 'JM 39', '2010', '', 'A', '447867', '2020-10-19T15:51:57+03:00'),
(100, 'SM3H004', 'SLUSH MACHINE 3 Hls', 'Ugolini', 'JM 03', '2003', '', 'A', '240102', '2020-10-27T11:16:45+03:00'),
(101, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'JM 12', '2006', '', 'A', '445641', '2020-10-27T11:27:11+03:00'),
(102, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'JM 29', '2005', '', 'A', '176771', '2020-10-27T11:45:10+03:00'),
(103, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'MM 01', '2006', '', 'A', '155392', '2020-10-27T12:06:15+03:00'),
(104, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'MM 01', '2006', '', 'A', '166490', '2020-10-27T12:07:04+03:00'),
(105, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'MM 02', '2003', '', 'A', '155314', '2020-10-27T12:08:15+03:00'),
(106, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'MM 02', '2003', '', 'A', '412826', '2020-10-27T12:09:07+03:00'),
(107, 'SM3H004', 'SLUSH MACHINE 3 Hls', 'Ugolini', 'MM 03', '2003', '', 'A', '146561', '2020-10-27T12:10:02+03:00'),
(108, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'MM 05', '2007', '', 'A', '214168', '2020-10-27T12:12:50+03:00'),
(109, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'MM 05', '2007', '', 'A', '412833', '2020-10-27T12:13:40+03:00'),
(110, 'SM3H004', 'SLUSH MACHINE 3 Hls', 'Ugolini', 'YM 01', '2004', '', 'A', '465925', '2020-10-27T12:38:38+03:00'),
(111, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'YM 02', '2004', '', 'A', '127112', '2020-10-27T12:39:56+03:00'),
(112, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'YM 02', '2006', '', 'A', '454659', '2020-10-27T12:40:39+03:00'),
(113, 'SM3H004', 'SLUSH MACHINE 3 Hls', 'Ugolini', 'YM 03', '2006', '', 'A', '447877', '2020-10-27T12:41:29+03:00'),
(114, 'SM3H004', 'SLUSH MACHINE 3 Hls', 'Ugolini', 'Store Coffee', '2002', 'out of order from UM2', 'A', '211682', '2020-10-27T12:47:57+03:00'),
(115, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'Store Coffee', '2008', 'UM2 out of order', 'A', '454656', '2020-10-27T12:49:05+03:00'),
(116, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'JMUM 02', '2004', 'from Um3 ', 'A', '412889', '2020-10-27T12:51:07+03:00'),
(117, 'SM3H004', 'SLUSH MACHINE 3 Hls', 'Ugolini', 'JMUM 05', '2004', '', 'A', '410486', '2020-10-27T13:15:50+03:00'),
(118, 'SM3H004', 'SLUSH MACHINE 3 Hls', 'Ugolini', 'JMUM 01', '2004', '', 'A', '410502', '2020-10-27T14:20:04+03:00'),
(119, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'JMUM 03', '2007', '', 'A', '454658', '2020-10-27T14:23:53+03:00'),
(120, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'JMUM 04', '2001', '', 'A', '83728', '2020-10-27T14:59:33+03:00'),
(121, 'SM3H004', 'SLUSH MACHINE 3 Hls', 'Ugolini', 'JMUM 04', '2006', '', 'A', '447876', '2020-11-01T13:02:30+03:00'),
(122, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'Store Coffee', '2003', 'OUT OF ORDER', 'A', '398775', '2020-11-03T11:31:11+03:00'),
(123, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'Store Coffee', '2002', 'OUT OF ORDER', 'A', '082686', '2020-11-03T11:32:01+03:00'),
(124, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'Store Coffee', '2004', 'OUT OF ORDER', 'A', '214179', '2020-11-03T11:32:44+03:00'),
(125, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'Store Coffee', '2003', 'OUT OF ORDER', 'A', '287783', '2020-11-03T11:35:02+03:00'),
(126, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'Store Coffee', '2001', 'OUT OF ORDE FROM JM15', 'A', 'JM.15003SL2', '2020-11-03T11:36:35+03:00'),
(127, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'Store Coffee', '2003', 'OUT OF ORDER', 'A', '155367', '2020-11-03T11:39:29+03:00'),
(128, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'Store Coffee', '2004', 'OUT OF ORDER', 'A', '172063', '2020-11-03T11:40:17+03:00'),
(129, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'Store Coffee', '2005', 'OUT OF ORDER', 'A', '194183', '2020-11-03T11:41:10+03:00'),
(130, 'ORG', 'Orange Machine', 'CITROCASA', 'JM 03', '2010', '', 'A', '100.476', '2020-11-03T14:37:03+03:00'),
(131, 'ICM', 'Ice Cub Machine ', 'MIGEL', 'JM 03', '2010', '', 'A', '1528882', '2020-11-03T14:39:01+03:00'),
(132, 'ICMWP', 'Ice Machine Water Pump', 'FLOJET', 'JM 03', '2010', '', 'A', 'J3ICMWP', '2020-11-03T14:42:09+03:00'),
(133, 'RFGR', ' Refrigerator  ', 'SAMSUNG', 'JM 03', '2012', '', 'A', '06FW43CF300451R', '2020-11-03T14:50:54+03:00'),
(134, 'RFGR', ' Refrigerator  ', 'SAMSUNG', 'JM 03', '2005', '', 'A', '40944DAC102387H', '2020-11-03T14:53:29+03:00'),
(135, 'HTR', 'Heater', 'GEEPAS', 'JM 03', '2014', '', 'A', 'J3HTR', '2020-11-03T14:54:42+03:00'),
(136, 'OVNS', 'Oven Small ', 'Penasonic', 'JM 03', '2009', '', 'A', 'J3OVNS', '2020-11-03T14:56:01+03:00');

-- --------------------------------------------------------

--
-- Table structure for table `machine_inv`
--

CREATE TABLE IF NOT EXISTS `machine_inv` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mid` varchar(255) NOT NULL,
  `inv_no` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `item` varchar(255) NOT NULL,
  `qty` varchar(255) NOT NULL,
  `price` varchar(255) NOT NULL,
  `date_reg` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=8 ;

--
-- Dumping data for table `machine_inv`
--

INSERT INTO `machine_inv` (`id`, `mid`, `inv_no`, `location`, `item`, `qty`, `price`, `date_reg`) VALUES
(2, '42', '268', 'JM 04', 'cup handle 2pc, over load,conical washer 2 pcs , Gasket etc.service done by our teachnicion ', '1', '1228', '2020-10-26T14:58:40+03:00'),
(3, '1', '21304', 'JM 01', 'Water pump ', '1', '840', '2020-10-26T15:03:30+03:00'),
(4, '8', '21304', 'JM 08', 'Pressure guage', '1', '197', '2020-10-26T15:06:22+03:00'),
(5, '27', '21304', 'JM 32', 'solinoid valve for water (steam valve)', '1', '240', '2020-10-26T15:09:31+03:00'),
(6, '41', '313', 'JMUBT 03', 'make complete service with new water pumpe on date 10-11-2020', '1', '1624', '2020-11-08T14:32:13+03:00'),
(7, '41', '4919', 'JMUBT 03', 'Elecronic Board Repaired on date 3-11-20', '1', '304', '2020-11-08T14:50:42+03:00');

-- --------------------------------------------------------

--
-- Table structure for table `machine_trans`
--

CREATE TABLE IF NOT EXISTS `machine_trans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `m_id` varchar(255) NOT NULL,
  `mid` int(50) NOT NULL,
  `location` varchar(255) NOT NULL,
  `old_location` varchar(255) NOT NULL,
  `date_reg` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=7 ;

--
-- Dumping data for table `machine_trans`
--

INSERT INTO `machine_trans` (`id`, `m_id`, `mid`, `location`, `old_location`, `date_reg`) VALUES
(1, 'SM3H004', 6, 'JM 19', 'JM 06', '2020-10-19T15:27:53+03:00'),
(2, 'EM2H001', 42, 'JM 04', 'JMUF 01', '2020-10-26T14:51:49+03:00'),
(3, 'SM3H004', 4, 'JM 38', 'JM 03', '2020-10-27T11:18:16+03:00'),
(4, 'SM3H004', 100, 'JM 03', 'JMUF 01', '2020-10-27T11:19:11+03:00'),
(5, 'SM2H003', 97, 'Store Coffee', 'JM 38', '2020-10-27T11:54:03+03:00'),
(6, 'SM3H004', 98, 'Coffee Factory', 'JM 38', '2020-10-27T11:54:49+03:00');

-- --------------------------------------------------------

--
-- Table structure for table `payroll`
--

CREATE TABLE IF NOT EXISTS `payroll` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chk_id` int(11) NOT NULL,
  `emp_id` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `salary` varchar(100) NOT NULL,
  `month` varchar(20) NOT NULL,
  `reg_date` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `payroll`
--


-- --------------------------------------------------------

--
-- Table structure for table `photos`
--

CREATE TABLE IF NOT EXISTS `photos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `size` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=17 ;

--
-- Triggers `photos`
--
DROP TRIGGER IF EXISTS `hr_db`.`a_i_photos`;
DELIMITER //
CREATE TRIGGER `hr_db`.`a_i_photos` AFTER INSERT ON `hr_db`.`photos`
 FOR EACH ROW BEGIN 						SET @time_mark = DATE_ADD(NOW(), INTERVAL -3576 SECOND); 						SET @tbl_name = 'cvb'; 						SET @tbl_name = 'photos'; 						SET @pk_d = CONCAT('<id>',NEW.`id`,'</id>'); 						SET @rec_state = 1;						UPDATE `history_store` SET `pk_date_dest` = `pk_date_src` WHERE `table_name` = @tbl_name AND `pk_date_dest` = @pk_d AND (`record_state` = 2 OR `record_state` = 1); 						DELETE FROM `history_store` WHERE `table_name` = @tbl_name AND `pk_date_dest` = @pk_d; 						INSERT INTO `history_store`( `timemark`, `table_name`, `pk_date_src`,`pk_date_dest`,`record_state` ) 						VALUES (@time_mark, @tbl_name, @pk_d, @pk_d, @rec_state); 						END
//
DELIMITER ;
DROP TRIGGER IF EXISTS `hr_db`.`a_u_photos`;
DELIMITER //
CREATE TRIGGER `hr_db`.`a_u_photos` AFTER UPDATE ON `hr_db`.`photos`
 FOR EACH ROW BEGIN						SET @time_mark = DATE_ADD(NOW(), INTERVAL -3576 SECOND); 						SET @tbl_name = 'photos';						SET @pk_d_old = CONCAT('<id>',OLD.`id`,'</id>');						SET @pk_d = CONCAT('<id>',NEW.`id`,'</id>');						SET @rec_state = 2;						SET @rs = 0;						SELECT `record_state` INTO @rs FROM `history_store` WHERE `table_name` = @tbl_name AND `pk_date_src` = @pk_d_old;						IF @rs = 0 THEN 						INSERT INTO `history_store`( `timemark`, `table_name`, `pk_date_src`,`pk_date_dest`, `record_state` ) VALUES (@time_mark, @tbl_name, @pk_d,@pk_d_old, @rec_state );						ELSE 						UPDATE `history_store` SET `timemark` = @time_mark, `pk_date_src` = @pk_d WHERE `table_name` = @tbl_name AND `pk_date_src` = @pk_d_old;						END IF; END
//
DELIMITER ;
DROP TRIGGER IF EXISTS `hr_db`.`a_d_photos`;
DELIMITER //
CREATE TRIGGER `hr_db`.`a_d_photos` AFTER DELETE ON `hr_db`.`photos`
 FOR EACH ROW BEGIN						SET @time_mark = DATE_ADD(NOW(), INTERVAL -3576 SECOND); 						SET @tbl_name = 'photos';						SET @pk_d = CONCAT('<id>',OLD.`id`,'</id>');						SET @rec_state = 3;						SET @rs = 0;						SELECT `record_state` INTO @rs FROM `history_store` WHERE  `table_name` = @tbl_name AND `pk_date_src` = @pk_d;						IF @rs = 1 THEN 						DELETE FROM `history_store` WHERE `table_name` = @tbl_name AND `pk_date_src` = @pk_d; 						END IF; 						IF @rs > 1 THEN 						UPDATE `history_store` SET `timemark` = @time_mark, `record_state` = 3, `pk_date_src` = `pk_date_dest` WHERE `table_name` = @tbl_name AND `pk_date_src` = @pk_d; 						END IF; 						IF @rs = 0 THEN 						INSERT INTO `history_store`( `timemark`, `table_name`, `pk_date_src`,`pk_date_dest`, `record_state` ) VALUES (@time_mark, @tbl_name, @pk_d,@pk_d, @rec_state ); 						END IF; END
//
DELIMITER ;

--
-- Dumping data for table `photos`
--

INSERT INTO `photos` (`id`, `title`, `description`, `filename`, `type`, `size`) VALUES
(9, 'Cooke', '', 'CookiesVanilla.JPG', 'image/jpeg', '4295546'),
(7, 'Orange Juice 9oz', '', 'OrangeJuicepage-001.jpg', 'image/jpeg', '223900'),
(8, 'Croissant', 'Description', 'CroissantCheese.png', 'image/png', '732568'),
(10, 'ICE Mocha', '', 'MochaRose.jpg', 'image/jpeg', '1681586'),
(11, 'Latte', 'too goods', 'Latte.jpg', 'image/jpeg', '74315'),
(12, 'Coffee', '', 'CoffeeBagYellow.jpg', 'image/jpeg', '44983'),
(13, 'Cake', '', 'EnglishcakeMarble-vanilla.JPG', 'image/jpeg', '5370623'),
(14, 'Mini Crosent', '', 'MINI-CROISSANT-PARFAIT_52359.png', 'image/png', '32015'),
(15, 'Expresoo', '', 'Espresso.jpg', 'image/jpeg', '38107'),
(16, 'hmmmmmmmm', 'Good Taste', 'SandwichTandouri.JPG', 'image/jpeg', '4512457');

-- --------------------------------------------------------

--
-- Table structure for table `salary_emp`
--

CREATE TABLE IF NOT EXISTS `salary_emp` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `emp_id` varchar(120) NOT NULL,
  `basic` int(100) NOT NULL,
  `housing` int(100) NOT NULL,
  `transport` int(100) NOT NULL,
  `other_pay` int(150) NOT NULL,
  `date_reg` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=124 ;

--
-- Dumping data for table `salary_emp`
--

INSERT INTO `salary_emp` (`id`, `emp_id`, `basic`, `housing`, `transport`, `other_pay`, `date_reg`) VALUES
(1, '152', 3703, 926, 371, 0, '2019-04-08T13:22:55+03:00'),
(2, '0154', 3703, 926, 371, 0, '2019-04-09T13:06:31+03:00'),
(3, '1007', 2500, 0, 0, 200, '2019-04-10T10:49:47+03:00'),
(4, '1002', 2091, 0, 0, 0, '2019-04-10T10:52:11+03:00'),
(5, '1003', 3601, 900, 0, 150, '2019-04-10T10:54:32+03:00'),
(6, '1020', 3755, 939, 800, 100, '2019-04-10T10:58:18+03:00'),
(7, '1021', 3680, 920, 900, 100, '2019-04-10T10:59:23+03:00'),
(8, '1023', 3200, 0, 0, 150, '2019-04-10T11:00:08+03:00'),
(9, '1024', 4000, 1000, 0, 150, '2019-04-10T11:01:07+03:00'),
(10, '1026', 3200, 800, 0, 0, '2019-04-10T11:02:16+03:00'),
(11, '1038', 6700, 1675, 800, 150, '2019-04-10T11:03:10+03:00'),
(12, '1055', 2377, 594, 900, 150, '2019-04-10T11:04:12+03:00'),
(13, '1061', 1992, 0, 0, 0, '2019-04-10T11:04:51+03:00'),
(14, '1062', 1773, 0, 0, 0, '2019-04-10T11:05:36+03:00'),
(15, '1064', 1956, 0, 0, 0, '2019-04-10T11:06:22+03:00'),
(16, '1067', 2053, 0, 0, 0, '2019-04-10T11:07:02+03:00'),
(17, '1070', 2377, 0, 0, 0, '2019-04-10T11:08:35+03:00'),
(18, '1075', 3601, 900, 0, 150, '2019-04-10T11:09:44+03:00'),
(19, '1077', 2377, 594, 900, 150, '2019-04-10T11:11:36+03:00'),
(20, '1084', 1773, 0, 0, 0, '2019-04-10T11:12:13+03:00'),
(21, '1088', 3200, 800, 900, 150, '2019-04-10T11:13:09+03:00'),
(22, '1098', 1773, 0, 0, 0, '2019-04-10T11:13:56+03:00'),
(23, '1101', 1773, 0, 0, 0, '2019-04-10T11:14:33+03:00'),
(24, '1112', 1773, 0, 0, 0, '2019-04-10T11:15:18+03:00'),
(25, '1113', 2377, 594, 900, 150, '2019-04-10T11:16:26+03:00'),
(26, '1114', 2750, 0, 0, 0, '2019-04-10T11:17:25+03:00'),
(27, '1117', 1773, 0, 0, 0, '2019-04-10T11:18:12+03:00'),
(28, '1119', 1773, 0, 0, 0, '2019-04-10T11:18:54+03:00'),
(29, '1133', 1773, 0, 0, 0, '2019-04-10T11:24:07+03:00'),
(30, '1136', 2943, 736, 900, 150, '2019-04-10T11:31:00+03:00'),
(31, '1146', 2752, 688, 900, 150, '2019-04-10T11:32:42+03:00'),
(32, '1148', 1773, 0, 0, 0, '2019-04-10T11:34:22+03:00'),
(33, '1163', 1773, 0, 0, 0, '2019-04-10T11:35:38+03:00'),
(34, '1168', 1773, 0, 0, 0, '2019-04-10T11:36:46+03:00'),
(35, '1178', 2400, 600, 0, 100, '2019-04-10T11:38:40+03:00'),
(36, '1193', 1806, 0, 0, 0, '2019-04-10T11:40:43+03:00'),
(37, '1198', 1689, 0, 0, 0, '2019-04-10T11:41:27+03:00'),
(38, '1217', 1689, 0, 0, 0, '2019-04-10T11:42:51+03:00'),
(39, '1243', 2186, 0, 0, 400, '2019-04-10T11:43:52+03:00'),
(40, '1244', 2421, 0, 0, 0, '2019-04-10T11:45:07+03:00'),
(41, '1248', 2712, 0, 0, 0, '2019-04-10T11:46:12+03:00'),
(42, '1249', 2312, 0, 0, 0, '2019-04-10T11:47:20+03:00'),
(43, '1252', 1689, 0, 0, 0, '2019-04-10T11:48:15+03:00'),
(44, '1256', 1689, 0, 0, 0, '2019-04-10T11:54:29+03:00'),
(45, '1323', 3000, 0, 0, 0, '2019-04-10T11:55:30+03:00'),
(46, '1331', 1642, 0, 0, 0, '2019-04-10T11:57:03+03:00'),
(47, '1351', 1642, 250, 100, 0, '2019-04-10T11:59:16+03:00'),
(48, '1357', 3185, 0, 0, 479, '2019-04-10T12:01:14+03:00'),
(49, '1359', 1642, 0, 0, 0, '2019-04-10T12:03:54+03:00'),
(50, '1362', 1500, 0, 0, 200, '2019-04-10T12:08:01+03:00'),
(51, '1395', 1773, 0, 0, 227, '2019-04-10T12:10:33+03:00'),
(52, '1400', 1773, 0, 0, 227, '2019-04-10T12:14:26+03:00'),
(53, '1402', 2200, 0, 0, 0, '2019-04-10T12:18:44+03:00'),
(54, '1403', 2042, 0, 0, 0, '2019-04-10T12:19:52+03:00'),
(55, '1405', 2500, 500, 0, 200, '2019-04-10T12:20:59+03:00'),
(56, '1406', 1942, 0, 0, 300, '2019-04-10T12:23:36+03:00'),
(57, '1410', 2000, 0, 0, 500, '2019-04-10T12:25:26+03:00'),
(58, '1411', 2500, 0, 0, 0, '2019-04-10T12:26:16+03:00'),
(59, '1412', 2500, 0, 0, 0, '2019-04-10T12:26:51+03:00'),
(60, '1413', 1773, 0, 0, 227, '2019-04-10T12:27:26+03:00'),
(61, '4517', 2963, 741, 296, 0, '2019-04-10T12:28:25+03:00'),
(62, '4519', 2963, 741, 296, 0, '2019-04-10T12:29:32+03:00'),
(63, '4522', 2963, 741, 296, 500, '2019-04-10T12:32:01+03:00'),
(64, '4523', 2963, 741, 296, 0, '2019-04-10T12:34:15+03:00'),
(65, '4403', 3200, 800, 0, 0, '2019-04-10T12:35:00+03:00'),
(66, '4415', 2000, 500, 0, 0, '2019-04-10T12:35:40+03:00'),
(67, '4416', 2000, 500, 0, 0, '2019-04-10T12:36:27+03:00'),
(68, '4420', 2963, 741, 296, 0, '2019-04-10T12:37:13+03:00'),
(69, '4425', 3333, 833, 334, 0, '2019-04-10T12:42:16+03:00'),
(70, '4448', 2963, 741, 296, 0, '2019-04-10T12:43:10+03:00'),
(71, '4449', 2963, 741, 296, 0, '2019-04-10T12:52:54+03:00'),
(72, '4456', 2963, 741, 296, 0, '2019-04-10T13:00:05+03:00'),
(73, '4451', 2963, 741, 296, 0, '2019-04-10T13:15:33+03:00'),
(74, '4452', 2963, 741, 296, 0, '2019-04-10T13:26:37+03:00'),
(75, '4453', 2963, 741, 296, 0, '2019-04-10T13:27:52+03:00'),
(76, '4454', 2963, 741, 296, 0, '2019-04-10T13:28:35+03:00'),
(77, '4455', 2963, 741, 296, 0, '2019-04-10T13:29:30+03:00'),
(78, '9', 8163, 2041, 816, 150, '2019-04-10T13:33:57+03:00'),
(79, '10', 14659, 3665, 1466, 150, '2019-04-10T13:37:13+03:00'),
(80, '18', 3562, 890, 356, 0, '2019-04-10T13:38:11+03:00'),
(81, '29', 5262, 1316, 526, 100, '2019-04-10T13:39:20+03:00'),
(82, '42', 4815, 1204, 481, 200, '2019-04-10T13:42:05+03:00'),
(83, '49', 2621, 655, 900, 100, '2019-04-10T13:43:17+03:00'),
(84, '82', 3200, 800, 1100, 250, '2019-04-10T13:44:51+03:00'),
(85, '84', 2600, 650, 260, 150, '2019-04-10T13:46:10+03:00'),
(86, '4435', 2963, 741, 296, 0, '2019-04-10T13:51:03+03:00'),
(87, '114', 3334, 833, 334, 0, '2019-04-10T13:51:46+03:00'),
(88, '120', 10000, 2500, 900, 200, '2019-04-10T13:52:50+03:00'),
(89, '4620', 5980, 1480, 592, 0, '2019-04-10T13:54:44+03:00'),
(90, '141', 2222, 555, 223, 100, '2019-04-10T13:55:54+03:00'),
(91, '147', 4444, 1111, 445, 2000, '2019-04-10T13:57:29+03:00'),
(92, '148', 5185, 1296, 519, 150, '2019-04-10T13:58:26+03:00'),
(93, '149', 2963, 741, 296, 0, '2019-04-10T13:59:21+03:00'),
(94, '1011', 2053, 0, 0, 0, '2019-04-10T14:01:43+03:00'),
(95, '1383', 2000, 0, 0, 0, '2019-04-11T09:24:51+03:00'),
(97, '1945', 2593, 648, 259, 0, '2019-04-11T10:58:43+03:00'),
(99, '0155', 2593, 648, 259, 0, '2019-04-11T12:58:31+03:00'),
(100, '153', 2222, 556, 222, 0, '2019-04-22T14:31:29+03:00'),
(102, '120', 10000, 2500, 1200, 200, '2019-05-02T13:52:15+03:00'),
(103, '1325', 2500, 0, 0, 0, '2019-06-23T14:15:38+03:00'),
(104, '0154', 3703, 926, 371, 0, '2019-09-09T15:42:06+03:00'),
(105, '4525', 2963, 741, 296, 0, '2019-09-12T12:40:38+03:00'),
(106, '157', 4500, 1125, 450, 0, '2019-09-24T15:37:57+03:00'),
(107, '156', 4500, 1125, 450, 0, '2019-09-24T15:45:01+03:00'),
(108, '0156', 4500, 1125, 450, 0, '2019-09-30T12:03:42+03:00'),
(109, '0157', 4500, 1125, 450, 0, '2019-09-30T12:08:27+03:00'),
(110, '1116', 2000, 600, 200, 400, '2019-10-02T09:55:20+03:00'),
(111, '4630', 2400, 600, 0, 0, '2019-12-02T16:25:22+03:00'),
(112, '4627', 2963, 741, 296, 0, '2019-12-10T10:28:54+03:00'),
(113, '4632', 2963, 741, 296, 0, '2019-12-11T15:49:50+03:00'),
(114, '4628', 2963, 741, 296, 0, '2019-12-11T15:54:05+03:00'),
(115, '4631', 2963, 741, 296, 0, '2019-12-11T15:56:41+03:00'),
(116, '4636', 2963, 741, 650, 150, '2020-09-02T10:55:23+03:00'),
(117, '4637', 2963, 741, 296, 0, '2020-09-02T14:55:26+03:00'),
(118, '1007', 1650, 0, 0, 1050, '2020-09-06T09:03:53+03:00'),
(119, '4523', 2963, 741, 296, 1370, '2020-09-09T09:07:56+03:00'),
(120, '0156', 5500, 1450, 550, 0, '2020-09-09T09:33:49+03:00'),
(121, '2', 22850, 5767, 0, 0, '2020-10-01T09:34:50+03:00'),
(122, '4635', 2963, 741, 296, 0, '2020-10-01T09:54:46+03:00'),
(123, '148', 5185, 1296, 519, 0, '2020-10-11T11:47:41+03:00');

-- --------------------------------------------------------

--
-- Table structure for table `section`
--

CREATE TABLE IF NOT EXISTS `section` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `section_name` varchar(255) NOT NULL,
  `dept` varchar(100) NOT NULL,
  `bulding_base` varchar(255) NOT NULL,
  `bulding_size` varchar(255) NOT NULL,
  `latitude` varchar(255) NOT NULL,
  `longitude` varchar(255) NOT NULL,
  `location_name` varchar(255) NOT NULL,
  `status` varchar(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=83 ;

--
-- Triggers `section`
--
DROP TRIGGER IF EXISTS `hr_db`.`a_i_section`;
DELIMITER //
CREATE TRIGGER `hr_db`.`a_i_section` AFTER INSERT ON `hr_db`.`section`
 FOR EACH ROW BEGIN 						SET @time_mark = DATE_ADD(NOW(), INTERVAL -3576 SECOND); 						SET @tbl_name = 'cvb'; 						SET @tbl_name = 'section'; 						SET @pk_d = CONCAT('<id>',NEW.`id`,'</id>'); 						SET @rec_state = 1;						UPDATE `history_store` SET `pk_date_dest` = `pk_date_src` WHERE `table_name` = @tbl_name AND `pk_date_dest` = @pk_d AND (`record_state` = 2 OR `record_state` = 1); 						DELETE FROM `history_store` WHERE `table_name` = @tbl_name AND `pk_date_dest` = @pk_d; 						INSERT INTO `history_store`( `timemark`, `table_name`, `pk_date_src`,`pk_date_dest`,`record_state` ) 						VALUES (@time_mark, @tbl_name, @pk_d, @pk_d, @rec_state); 						END
//
DELIMITER ;
DROP TRIGGER IF EXISTS `hr_db`.`a_u_section`;
DELIMITER //
CREATE TRIGGER `hr_db`.`a_u_section` AFTER UPDATE ON `hr_db`.`section`
 FOR EACH ROW BEGIN						SET @time_mark = DATE_ADD(NOW(), INTERVAL -3576 SECOND); 						SET @tbl_name = 'section';						SET @pk_d_old = CONCAT('<id>',OLD.`id`,'</id>');						SET @pk_d = CONCAT('<id>',NEW.`id`,'</id>');						SET @rec_state = 2;						SET @rs = 0;						SELECT `record_state` INTO @rs FROM `history_store` WHERE `table_name` = @tbl_name AND `pk_date_src` = @pk_d_old;						IF @rs = 0 THEN 						INSERT INTO `history_store`( `timemark`, `table_name`, `pk_date_src`,`pk_date_dest`, `record_state` ) VALUES (@time_mark, @tbl_name, @pk_d,@pk_d_old, @rec_state );						ELSE 						UPDATE `history_store` SET `timemark` = @time_mark, `pk_date_src` = @pk_d WHERE `table_name` = @tbl_name AND `pk_date_src` = @pk_d_old;						END IF; END
//
DELIMITER ;
DROP TRIGGER IF EXISTS `hr_db`.`a_d_section`;
DELIMITER //
CREATE TRIGGER `hr_db`.`a_d_section` AFTER DELETE ON `hr_db`.`section`
 FOR EACH ROW BEGIN						SET @time_mark = DATE_ADD(NOW(), INTERVAL -3576 SECOND); 						SET @tbl_name = 'section';						SET @pk_d = CONCAT('<id>',OLD.`id`,'</id>');						SET @rec_state = 3;						SET @rs = 0;						SELECT `record_state` INTO @rs FROM `history_store` WHERE  `table_name` = @tbl_name AND `pk_date_src` = @pk_d;						IF @rs = 1 THEN 						DELETE FROM `history_store` WHERE `table_name` = @tbl_name AND `pk_date_src` = @pk_d; 						END IF; 						IF @rs > 1 THEN 						UPDATE `history_store` SET `timemark` = @time_mark, `record_state` = 3, `pk_date_src` = `pk_date_dest` WHERE `table_name` = @tbl_name AND `pk_date_src` = @pk_d; 						END IF; 						IF @rs = 0 THEN 						INSERT INTO `history_store`( `timemark`, `table_name`, `pk_date_src`,`pk_date_dest`, `record_state` ) VALUES (@time_mark, @tbl_name, @pk_d,@pk_d, @rec_state ); 						END IF; END
//
DELIMITER ;

--
-- Dumping data for table `section`
--

INSERT INTO `section` (`id`, `section_name`, `dept`, `bulding_base`, `bulding_size`, `latitude`, `longitude`, `location_name`, `status`) VALUES
(1, 'JM 01', 'POS', 'Cement', '4*3', '21.593371', '39.105903', 'Corniche near fun time', 'A'),
(2, 'JM 03', 'POS', 'Cement ', '4*3', '21.708562', '39.102249', 'Abhor', 'A'),
(3, 'JM 06', 'POS', 'IBSF Cabnit', '5*2', '21.55263', '39.234111', 'Haramain Road - Sahal Gas Station', 'A'),
(4, 'JM 08', 'POS', 'IBSF Cabnit', '5*2', '21.505991', '39.165139', 'United Hospital (Naft Station)', 'A'),
(5, 'JM 10', 'POS', 'Wooden Walls', '0', '21.53923', '39.2245', 'Dallah Tower', 'A'),
(6, 'JM 11', 'POS', 'IBSF Cabnit', '5*2', '21.561107', '39.231614', 'Al Jadayal Makkah Road', 'A'),
(7, 'JM 12', 'POS', 'Matel', '4*3', '21.756142', '39.122473', 'Sawary - Abhor', 'A'),
(8, 'JM 14', 'POS', '', '', '', '', 'Madina Road-Hera Bridge', 'C'),
(9, 'JM 15', 'POS', 'IBSF Cabnit', '5*2', '21.558392', '39.116174', 'Bawazer - Cornich', 'A'),
(10, 'JM 16', 'POS', 'IBSF Cabnit', '5*2', '21.567007', '39.14282', 'Haqbani - Sultan Street', 'A'),
(11, 'JM 17', 'POS', 'IBSF Cabnit', '5*2', '21.586834', '39.163761', 'Sederi- Sary Bridge', 'A'),
(12, 'JM 18', 'POS', 'Matel', '5*2', '21.648708', '39.110724', 'Glob Circle-King Road', 'A'),
(13, 'JM 19', 'POS', 'IBSF Cabnit', '5*2', '21.60985', '39.136969', 'Hera Avenue', 'A'),
(14, 'JM 20', 'POS', 'IBSF Cabnit', '6*4', '21.793729', '39.12515', 'Al Otabi - Reheli', 'A'),
(15, 'JM 22', 'POS', 'IBSF Cabnit', '5*2', '21.54839', '39.236876', 'Sulaimania-Naft Station', 'A'),
(16, 'JM 23', 'POS', 'Matel (U Shape)', '3*2', '21.717829', '39.1099', 'Sharm Obhor', 'A'),
(17, 'JM 24', 'POS', 'Matel', '3*2', '21.635688', '39.102792', 'Technology Center Cornich', 'A'),
(18, 'JM 26', 'POS', 'IBSF Cabnit', '5*2', '21.463877', '39.224093', 'Madayen Fahed', 'A'),
(19, 'JM 28', 'POS', 'IBSF Cabnit', '3*2', '21.572448', '39.210352', 'Arbaheen - Albaik', 'A'),
(20, 'JM 29', 'POS', 'Cement ', '8*6', '21.51898', '39.182322', 'Hyatt Center Madinah Road', 'A'),
(21, 'JM 31', 'POS', '', '', '', '', 'Eye Hospital', 'C'),
(22, 'JM 32', 'POS', 'IBSF Cabnit', '5*2', '21.415135', '39.336417', 'Mizan Petrole Station', 'A'),
(23, 'JM 33', 'POS', 'Matel (U Shape)', '3*2', '21.500958', '39.23941', 'KA University', 'A'),
(24, 'JM 34', 'POS', 'Matel (U Shape)', '3*2', '21.531204', '39.187111', 'Naft - Palastine', 'A'),
(25, 'JM 38', 'POS', 'IBSF Cabnit', '5*2', '21.801966', '39.118199', 'Rehely', 'A'),
(26, 'JM 39', 'POS', 'Matel', '3*2', '21.654643', '39.102097', 'Lulu Al Jeddah', 'A'),
(27, 'MM 01', 'POS', 'IBSF Cabnit', '5*2', '21.396215', '39.792846', 'Khalidiya', 'A'),
(28, 'MM 02', 'POS', 'Cement', '4*3', '21.355646', '40.115055', 'Sasco Taif Road', 'A'),
(29, 'MM 03', 'POS', 'Matel', '3*2', '21.435475', '39.830659', 'Maala', 'A'),
(30, 'MM 05', 'POS', 'Cement', '4*3', '21.39088', '39.706469', 'Zaidy Sasco - Makkah Rd', 'A'),
(31, 'YM 01', 'POS', 'Cement', '4*3', '24.007932', '38.234016', 'Al Buhyra Royal Commission', 'A'),
(32, 'YM 02', 'POS', 'Cement', '4*3', '24.022831', '38.139939', 'Yanbu Cornich Area 1', 'A'),
(33, 'YM 03', 'POS', 'Cement', '4*3', '21.708562', '39.102249', 'Yanbu Cornich Area 2', 'A'),
(34, 'YUM 01', 'POS', '', '', '', '', 'Yanbu Industriel College', 'C'),
(35, 'JMUM 01', 'POS', 'Wooden Walls', '0', '21.498767', '39.231034', 'KAU Research center King Fahed', 'A'),
(36, 'JMUM 02', 'POS', 'Wooden Walls', '0', '21.495538', '39.235973', 'KAU Male Dental Hosp.', 'A'),
(37, 'JMUM 03', 'POS', 'Wooden Walls', '0', '21.498786', '39.231177', 'KAU Environment Design', 'A'),
(38, 'JMUM 04', 'POS', 'Wooden Walls', '0', '7186', '2820', 'KAU Library', 'A'),
(39, 'JMUM 05', 'POS', 'Matel', '3*2', '2749285', '6420', 'University Asfan', 'A'),
(40, 'JMUF 01', 'POS', '', '', '', '', 'University - Female Dental', 'C'),
(41, 'JMUF 03', 'POS', '', '', '', '', 'University - Female Multaz', 'C'),
(42, 'JMUBTF 01', 'POS', '', '', '', '', 'CBA Sari Street', 'C'),
(43, 'JMUBT 01', 'POS', '', '', '', '', 'CBA  Language Center (Dahban)', 'C'),
(44, 'JMUBT 02 ', 'POS', '', '', '', '', 'CBA College - Food Court', 'C'),
(45, 'JMUBT 03', 'POS', '', '', '', '', 'CBA Engeneering College', 'C'),
(46, 'Store Coffee', 'Warehouse', '', '', '', '', 'Al Rajhi', 'A'),
(47, 'Store Sugar', 'Warehouse', '', '', '', '', 'Al Amlak', 'A'),
(48, 'Store Riyadh', 'Warehouse', '', '', '', '', 'Wholesales', 'A'),
(49, 'Store Madina', 'Warehouse', '', '', '', '', 'Wholesales', 'A'),
(50, 'Store Makkah', 'Warehouse', '', '', '', '', 'Wholesale', 'A'),
(54, 'Sugar Factory', 'Production', '', '', '', '', 'Modorn', 'A'),
(55, 'Coffee Factory', 'Production', '', '', '', '', 'Modorn', 'A'),
(56, 'Head Office', 'Head Office', '', '', '', '', 'Ameer Sultan Street', 'A'),
(60, 'Maintenance', 'Maintenance', '', '', '', '', '', 'A'),
(61, 'JM 21', 'POS', 'IBSF Cabnit', '3*2', '21.58921', '39.131126', 'Near Al-Shati Markeet', 'A'),
(62, 'Administration', 'Administration', '', '', '', '', '', 'A'),
(76, 'Head Office', 'POS', '', '', '', '', '', 'A'),
(64, 'Finance', 'Finance', '', '', '', '', '', 'A'),
(65, 'HRD and Housing', 'HRD and Housing', '', '', '', '', '', 'A'),
(66, 'Public Relation', 'Public Relation', '', '', '', '', '', 'A'),
(67, 'Sales', 'Sales', '', '', '', '', '', 'A'),
(68, 'Inspection', 'Inspection', '', '', '', '', '', 'A'),
(69, 'Purchase', 'Purchase', '', '', '', '', '', 'A'),
(70, 'IT', 'IT', '', '', '', '', '', 'A'),
(71, 'Production', 'Production', '', '', '', '', '', 'A'),
(72, 'Transportation', 'Transportation', '', '', '', '', '', 'A'),
(73, 'Maintenance', 'Maintenance', '', '', '', '', '', 'A'),
(74, 'Management', 'Management', '', '', '', '', '', 'A'),
(75, 'General', 'General', '', '', '', '', '', 'A'),
(77, 'JM 02', 'POS', 'SARIAH Cabnit', '3*2', '21.630187', '39.134658', 'Prince Sultan Street front of Haram Center', 'A'),
(78, 'Operations', 'POS', '', '', '', '', '', 'A'),
(79, 'MM 04', 'POS', 'Cement', '6*4', '21.427558', '39.856056', 'Makkah Sheesha', 'A'),
(80, 'YM 04', 'POS', '', '', '', '', 'Yunbo', 'C'),
(81, 'YUM 02', 'POS', '', '', '', '', 'Yonbu', 'C'),
(82, 'YUM 03', 'POS', '', '', '', '', 'Yunbo', 'C');

-- --------------------------------------------------------

--
-- Table structure for table `usersimage`
--

CREATE TABLE IF NOT EXISTS `usersimage` (
  `uid` int(11) NOT NULL AUTO_INCREMENT,
  `user` varchar(255) DEFAULT NULL,
  `pass` varchar(100) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `profile_photo` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`uid`),
  UNIQUE KEY `username` (`user`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

--
-- Dumping data for table `usersimage`
--

INSERT INTO `usersimage` (`uid`, `user`, `pass`, `email`, `profile_photo`) VALUES
(1, 'anees', '6539306', 'aneesmug@2007.com', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `vac_sch`
--

CREATE TABLE IF NOT EXISTS `vac_sch` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `emp_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `dept` varchar(255) NOT NULL,
  `replacement_per` varchar(255) NOT NULL,
  `vac_strt_date` varchar(50) NOT NULL,
  `last_vac_date` varchar(50) NOT NULL,
  `next_vac_date` varchar(50) NOT NULL,
  `note` text NOT NULL,
  `vacation_days` varchar(50) NOT NULL,
  `date_reg` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=11 ;

--
-- Dumping data for table `vac_sch`
--

INSERT INTO `vac_sch` (`id`, `emp_id`, `name`, `dept`, `replacement_per`, `vac_strt_date`, `last_vac_date`, `next_vac_date`, `note`, `vacation_days`, `date_reg`) VALUES
(1, 148, 'Mohammed Al Khayyat', 'Purchase', '148', '02/02/2020', '', '02/02/2021', '', '30', '2019-12-02T12:07:08+03:00'),
(2, 4620, 'Sultan Yassin Ahmed Al Delame', 'HRD and Housing', '4620', '15/12/2020', '02/12/2018', '15/12/2021', '', '30', '2019-12-02T12:08:07+03:00'),
(3, 49, 'Abdul Wahab A. Ghafoor', 'Inspection', '1077', '25/01/2021', '', '25/01/2023', '', '50', '2019-12-02T15:49:58+03:00'),
(4, 152, 'Anees Afzal', 'IT', '1136', '30/09/2020', '', '30/09/2021', '', '30', '2019-12-03T07:58:45+03:00'),
(5, 42, 'Sajjad Hussain ', 'POS', '1021', '19/01/2020', '', '19/01/2021', 'i would like to request for my annual vacation', '30', '2019-12-03T12:00:46+03:00'),
(6, 29, 'Abdul Malik Shahul', 'Purchase', '141', '20/09/2020', '', '20/09/2021', '', '30', '2019-12-11T10:43:21+03:00'),
(7, 10, 'Aboo Backer Kappil', 'Finance', '29', '01/07/2020', '', '01/07/2021', 'Please note that i will reconfirm the exact date after collecting my children vacation schedule.', '30', '2019-12-11T12:37:19+03:00'),
(8, 120, 'Hatim Shafi Felemban', 'Sales Department', '82', '01/06/2020', '', '01/06/2021', '2020 vacation', '30', '2019-12-15T15:22:38+03:00'),
(9, 1077, 'Mohd. Washimuddin', 'Inspection', '149', '01/07/2020', '', '01/07/2022', '', '50', '2019-12-17T08:48:28+03:00'),
(10, 149, 'Mohammed Sameer Halwani', 'Inspection', '1077', '10/09/2020', '', '10/09/2021', '', '30', '2020-02-05T16:11:49+03:00');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
