-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 07, 2025 at 07:25 AM
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
-- Table structure for table `accesslog`
--

CREATE TABLE `accesslog` (
  `id` int(11) NOT NULL,
  `action_page` varchar(100) DEFAULT NULL,
  `action_done` varchar(100) DEFAULT NULL,
  `remarks` varchar(150) DEFAULT NULL,
  `user_name` varchar(100) DEFAULT NULL,
  `entry_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `user_editor` varchar(100) NOT NULL,
  `page` varchar(255) NOT NULL,
  `pg_id` varchar(255) NOT NULL,
  `reg_date` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `user_editor`, `page`, `pg_id`, `reg_date`) VALUES
(1, '', 'add_emp_slry.php', '', '2025-06-23T08:34:59+03:00'),
(2, '', 'add_emp_slry.php', '', '2025-06-23T08:40:36+03:00'),
(3, '', 'add_emp_slry.php', '', '2025-06-23T08:41:16+03:00'),
(4, '', 'add_emp_slry.php', '', '2025-06-23T08:41:49+03:00'),
(5, '', 'edit_employee.php', '', '2025-06-26T15:31:00+03:00'),
(6, '', 'open_vac_aply.php', '1', '2025-07-02T10:53:36+03:00'),
(7, '', 'vac_app_emp.php', '1', '2025-07-02T11:02:29+03:00'),
(8, '', 'add_vac_emp.php', '1872', '2025-07-02T11:03:06+03:00'),
(9, '', 'arrived_emp.php', '', '2025-07-02T11:03:49+03:00'),
(10, '', 'edit_employee.php', '', '2025-07-02T11:15:12+03:00'),
(11, '', 'add_emp_slry.php', '', '2025-07-02T11:19:54+03:00'),
(12, '', 'edit_employee.php', '', '2025-07-02T13:30:55+03:00'),
(13, '', 'edit_employee.php', '', '2025-07-02T15:10:25+03:00'),
(14, '', 'edit_employee.php', '', '2025-07-02T15:19:32+03:00'),
(15, '', 'edit_employee.php', '', '2025-07-02T15:24:15+03:00'),
(16, '', 'edit_employee.php', '', '2025-07-02T15:24:20+03:00'),
(17, '', 'edit_employee.php', '', '2025-07-02T15:25:02+03:00'),
(18, '', 'edit_employee.php', '', '2025-07-02T15:25:32+03:00'),
(19, '', 'edit_employee.php', '', '2025-07-02T15:25:44+03:00'),
(20, '', 'edit_employee.php', '', '2025-07-02T15:35:23+03:00'),
(21, '', 'edit_employee.php', '', '2025-07-02T15:35:33+03:00'),
(22, '', 'edit_employee.php', '', '2025-07-02T15:35:50+03:00'),
(23, '', 'edit_employee.php', '', '2025-07-02T15:36:54+03:00'),
(24, '', 'edit_employee.php', '', '2025-07-02T15:38:59+03:00'),
(25, '', 'edit_employee.php', '', '2025-07-02T15:39:08+03:00'),
(26, '', 'edit_employee.php', '', '2025-07-02T15:44:36+03:00'),
(27, '', 'edit_employee.php', '', '2025-07-02T16:04:10+03:00'),
(28, '', 'edit_employee.php', '', '2025-07-02T16:04:47+03:00'),
(29, '', 'edit_employee.php', '', '2025-07-02T16:04:54+03:00'),
(30, '', 'edit_employee.php', '', '2025-07-02T16:16:32+03:00'),
(31, '', 'edit_employee.php', '', '2025-07-02T16:16:43+03:00'),
(32, '', 'edit_employee.php', '', '2025-07-02T16:19:39+03:00'),
(33, '', 'edit_employee.php', '', '2025-07-02T16:19:46+03:00'),
(34, '', 'edit_employee.php', '', '2025-07-02T16:19:52+03:00'),
(35, '', 'edit_employee.php', '', '2025-07-02T16:24:02+03:00'),
(36, '', 'edit_employee.php', '', '2025-07-02T16:24:17+03:00'),
(37, '', 'edit_employee.php', '', '2025-07-02T16:27:40+03:00'),
(38, '', 'edit_employee.php', '', '2025-07-02T16:27:51+03:00'),
(39, '', 'edit_employee.php', '', '2025-07-02T16:31:15+03:00'),
(40, '', 'edit_employee.php', '', '2025-07-02T16:31:25+03:00'),
(41, '', 'edit_employee.php', '', '2025-07-02T16:32:17+03:00'),
(42, '', 'edit_employee.php', '', '2025-07-02T16:32:35+03:00'),
(43, '', 'edit_employee.php', '', '2025-07-02T16:33:11+03:00'),
(44, '', 'edit_employee.php', '', '2025-07-02T16:33:39+03:00'),
(45, '', 'edit_employee.php', '', '2025-07-02T16:34:01+03:00'),
(46, '', 'edit_employee.php', '', '2025-07-02T16:35:19+03:00'),
(47, '', 'edit_employee.php', '', '2025-07-02T16:35:33+03:00'),
(48, '', 'edit_employee.php', '', '2025-07-02T16:36:51+03:00'),
(49, '', 'edit_employee.php', '', '2025-07-02T16:37:44+03:00'),
(50, '', 'edit_employee.php', '', '2025-07-02T16:38:09+03:00'),
(51, '', 'edit_employee.php', '', '2025-07-02T16:38:43+03:00'),
(52, '', 'edit_employee.php', '', '2025-07-02T16:38:50+03:00'),
(53, '', 'edit_employee.php', '', '2025-07-02T16:40:42+03:00'),
(54, '', 'edit_employee.php', '', '2025-07-02T16:41:10+03:00'),
(55, '', 'open_vac_aply.php', '2', '2025-07-06T10:33:50+03:00'),
(56, '', 'vac_app_emp.php', '2', '2025-07-06T10:35:54+03:00'),
(57, '', 'add_vac_emp.php', '5430', '2025-07-06T10:36:04+03:00'),
(58, '', 'open_vac_aply.php', '1', '2025-07-06T11:11:52+03:00'),
(59, '', 'vac_app_emp.php', '1', '2025-07-06T11:12:10+03:00'),
(60, '', 'add_vac_emp.php', '5430', '2025-07-06T11:12:23+03:00'),
(61, '', 'arrived_emp.php', '', '2025-07-06T11:15:42+03:00'),
(62, '', 'open_vac_aply.php', '1', '2025-07-06T11:42:31+03:00'),
(63, '', 'vac_app_emp.php', '1', '2025-07-06T11:42:44+03:00'),
(64, '', 'terminat_emp.php', '', '2025-07-07T09:13:22+03:00'),
(65, '', 'terminat_emp.php', '', '2025-07-07T09:13:56+03:00'),
(66, '', 'terminat_emp.php', '', '2025-07-07T09:14:41+03:00'),
(67, '', 'terminat_emp.php', '5410', '2025-07-07T09:16:10+03:00'),
(68, '', 'terminat_emp.php', '5410', '2025-07-07T09:16:18+03:00'),
(69, '', 'open_vac_aply.php', '1', '2025-07-07T11:40:25+03:00'),
(70, '', 'vac_app_emp.php', '1', '2025-07-07T11:41:55+03:00'),
(71, '', 'new_user.php', 'BASHIR AHMED GHLAM RASOOL', '2025-07-13T09:14:03+03:00'),
(72, '2337318717', 'emp_end_of_service', '5127', '2025-08-27T22:19:53+03:00'),
(73, '2337318717', 'emp_end_of_service', '5127', '2025-08-27T22:33:38+03:00'),
(74, '2337318717', 'emp_end_of_service', '5127', '2025-08-28T01:23:33+03:00'),
(75, '2337318717', 'emp_end_of_service', '5127', '2025-08-28T10:43:48+03:00'),
(76, '2337318717', 'emp_end_of_service', '5035', '2025-09-04T15:57:52+03:00'),
(77, '2337318717', 'emp_end_of_service', '5035', '2025-09-04T16:30:52+03:00'),
(78, '2337318717', 'emp_end_of_service', '5035', '2025-09-04T16:35:32+03:00');

-- --------------------------------------------------------

--
-- Table structure for table `ac_jobs`
--

CREATE TABLE `ac_jobs` (
  `id` int(11) NOT NULL,
  `job` varchar(50) NOT NULL,
  `job_ar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `ac_jobs`
--

INSERT INTO `ac_jobs` (`id`, `job`, `job_ar`, `created_at`) VALUES
(1, 'WELDER\r', 'لحام', '2025-09-01 11:06:40'),
(2, 'A/C TECHNICIAN\r', 'فني تكييف', '2025-09-01 11:06:40'),
(3, 'OPT. MACH.\r', 'مشغل آلة', '2025-09-01 11:06:40'),
(4, 'OPT.FORKLIFT\r', 'مشغل رافعة شوكية', '2025-09-01 11:06:40'),
(5, 'FOREMAN ASSISTANT\r', 'مساعد مشرف', '2025-09-01 11:06:40'),
(6, 'SANDBLASTER\r', 'عامل ساند بلاست', '2025-09-01 11:06:40'),
(7, 'DRAFTSMAN\r', 'رسام هندسي', '2025-09-01 11:06:40'),
(8, 'DRIVER LIGHT DUTY\r', 'سائق خفيف', '2025-09-01 11:06:40'),
(9, 'CASHIER\r', 'أمين صندوق', '2025-09-01 11:06:40'),
(10, 'ENG\'R.QUALITY CONTROL\r', 'مهندس ضبط جودة', '2025-09-01 11:06:40'),
(11, 'INSPECTOR PROD\r', 'مفتش إنتاج', '2025-09-01 11:06:40'),
(12, 'WATCHMAN\r', 'حارس', '2025-09-01 11:06:40'),
(13, 'FABRICATOR\r', 'فني تصنيع', '2025-09-01 11:06:40'),
(14, 'ALUMINUM WELDER\r', 'لحام ألمنيوم', '2025-09-01 11:06:40'),
(15, 'LABOURER\r', 'عامل', '2025-09-01 11:06:40'),
(16, 'MACHINIST\r', 'فني ميكانيكي', '2025-09-01 11:06:40'),
(17, 'SALESMAN\r', 'بائع', '2025-09-01 11:06:40'),
(18, 'ENG\'R. PRODUCTION\r', 'مهندس إنتاج', '2025-09-01 11:06:40'),
(19, 'ACCOUNTANT\r', 'محاسب', '2025-09-01 11:06:40'),
(20, 'CLEANER\r', 'عامل نظافة', '2025-09-01 11:06:40'),
(21, 'OPT. TEL\r', NULL, '2025-06-03 05:48:48'),
(22, 'ASSEMBLER\r', 'عامل تجميع', '2025-09-01 11:06:40'),
(23, 'CLEANER / WATCHMAN\r', 'عامل نظافة / حارس', '2025-09-01 11:06:40'),
(24, 'P.R.O.\r', 'مسؤول علاقات عامة', '2025-09-01 11:06:40'),
(25, 'ELECTRICIAN\r', 'كهربائي', '2025-09-01 11:06:40'),
(26, 'FOREMAN\r', 'مشرف', '2025-09-01 11:06:40'),
(27, 'OPT.PRESS\r', 'مشغل مكبس', '2025-09-01 11:06:40'),
(28, 'OFFICE BOY\r', 'مراسل مكتبي', '2025-09-01 11:06:40'),
(29, 'PAINTER\r', 'دهان', '2025-09-01 11:06:40'),
(30, 'PUTTY FIXER\r', 'عامل معجون', '2025-09-01 11:06:40'),
(31, 'STOREKEEPER\r', 'أمين مستودع', '2025-09-01 11:06:40'),
(32, 'Q.C.INSPECTOR\r', 'مفتش ضبط جودة', '2025-09-01 11:06:40'),
(33, 'SALES IN-CHARGE\r', 'مسؤول مبيعات', '2025-09-01 11:06:40'),
(34, 'PREPARATION\r', 'عامل تحضير', '2025-09-01 11:06:40'),
(35, 'PREPARATION  \r', 'عامل تحضير', '2025-09-01 11:06:40'),
(36, 'PROD.SUPERVISOR\r', 'مشرف إنتاج', '2025-09-01 11:06:40'),
(37, 'AUTOCAD\r', 'رسام أوتوكاد', '2025-09-01 11:06:40'),
(38, 'MECHANIC\r', 'ميكانيكي', '2025-09-01 11:06:40'),
(39, 'MGR.PRODUCTION\r', 'مدير إنتاج', '2025-09-01 11:06:40'),
(40, 'FORKLIFT OPERATOR\r', 'مشغل رافعة شوكية', '2025-09-01 11:06:40'),
(41, 'COOK H-IND\r', 'طباخ هندي', '2025-09-01 11:06:40'),
(42, 'AUDITOR\r', 'مدقق حسابات', '2025-09-01 11:06:40'),
(43, 'OPT.CNC\r', 'مشغل CNC', '2025-09-01 11:06:40'),
(44, 'HR OFFICER\r', 'مسؤول موارد بشرية', '2025-09-01 11:06:40'),
(45, 'TECH.OFFICER\r', 'مسؤول فني', '2025-09-01 11:06:40'),
(46, 'DRIVE HEAVY DUTY\r', 'سائق ثقيل', '2025-09-01 11:06:40'),
(47, 'MGR.REAL ESTATE\r', 'مدير عقارات', '2025-09-01 11:06:40'),
(48, 'PURCHASER\r', 'مشتريات', '2025-09-01 11:06:40'),
(49, 'DRIVER HEAVY DUTY\r', 'سائق ثقيل', '2025-09-01 11:06:40'),
(50, 'MGR. FINANCE\r', 'مدير مالي', '2025-09-01 11:06:40'),
(51, 'PURCHASING MGR\r', 'مدير مشتريات', '2025-09-01 11:06:40'),
(52, 'ACCOUNTS SUPERVISOR\r', 'مشرف حسابات', '2025-09-01 11:06:40'),
(53, 'MGR. AST. PRODUCTION \r', 'مساعد مدير إنتاج', '2025-09-01 11:06:40'),
(54, 'SALES MANAGER\r', 'مدير مبيعات', '2025-09-01 11:06:40'),
(55, 'MGR.MAINTAINANCE\r', 'مدير صيانة', '2025-09-01 11:06:40'),
(56, 'COOK-PAK\r', 'طباخ باكستاني', '2025-09-01 11:06:40'),
(57, 'MAINT.INCHARGE\r', 'مسؤول صيانة', '2025-09-01 11:06:40'),
(58, 'AUTO ELECTRICIAN\r', 'كهربائي سيارات', '2025-09-01 11:06:40'),
(59, 'OPT. TEL.\r', 'مشغل هاتف', '2025-09-01 11:06:40'),
(60, ' WELDER\r', 'لحام', '2025-09-01 11:06:40'),
(61, 'PLUMBER\r', 'سباك', '2025-09-01 11:06:40'),
(62, 'TYREMAN\r', 'فني إطارات', '2025-09-01 11:06:40'),
(63, 'ADMIN. OFFICER\r', 'مسؤول إداري', '2025-09-01 11:06:40'),
(64, 'OFFICE MANAGER\r', 'مدير مكتب', '2025-09-01 11:06:40'),
(65, 'PRODUCTION SUPERVISOR\r', 'مشرف إنتاج', '2025-09-01 11:06:40'),
(66, 'MAINTENANCE SUPERVISOR\r', 'مشرف صيانة', '2025-09-01 11:06:40'),
(67, 'PRODUCTION ENGINEER\r', 'مهندس إنتاج', '2025-09-01 11:06:40'),
(68, 'DRIVER HD\r', 'سائق ثقيل', '2025-09-01 11:06:40'),
(69, 'PURCHASING COORDINATOR\r', 'منسق مشتريات', '2025-09-01 11:06:40'),
(70, 'SALESMAN & PREPARATION\r', 'بائع ومحضر', '2025-09-01 11:06:40'),
(71, 'MARKETING MANAGER\r', 'مدير تسويق', '2025-09-01 11:06:40'),
(72, 'ADMIN OFFICER\r', 'مسؤول إداري', '2025-09-01 11:06:40'),
(73, 'NETWORK SYSTEM ENGR\r', 'مهندس شبكات', '2025-09-01 11:06:40'),
(74, 'SUPERVISOR\r', 'مشرف', '2025-09-01 11:06:40'),
(75, 'COOK-IND\r', 'طباخ هندي', '2025-09-01 11:06:40'),
(76, 'AREA MANAGER\r', 'مدير منطقة', '2025-09-01 11:06:40'),
(77, 'DRIVER\r', 'سائق', '2025-09-01 11:06:40'),
(78, 'SAFETY OFFICER\r', 'مسؤول سلامة', '2025-09-01 11:06:40'),
(79, 'AST. PROD MGR\r', 'مساعد مدير إنتاج', '2025-09-01 11:06:40'),
(80, 'MGR.FACTORY\r', 'مدير مصنع', '2025-09-01 11:06:40'),
(81, 'RECEPTIONIST\r', 'موظف استقبال', '2025-09-01 11:06:40'),
(82, 'OPT. FORKLIFT\r', 'مشغل رافعة شوكية', '2025-09-01 11:06:40'),
(83, 'IT. ADMINISTRATOR\r', 'مسؤول تقنية المعلومات', '2025-09-01 11:06:40'),
(84, 'LABOURER -LOADING/INLOADING\r', 'عامل تحميل وتنزيل', '2025-09-01 11:06:40'),
(85, 'LOADING/UNLOADING LABOUR\r', 'عامل تحميل وتنزيل', '2025-09-01 11:06:40'),
(86, 'PURCHASING OFFICER\r', 'مسؤول مشتريات', '2025-09-01 11:06:40'),
(87, 'SPRAY PAINTER\r', 'دهان رش', '2025-09-01 11:06:40'),
(88, 'ADMIN COORDINATOR\r', 'منسق إداري', '2025-09-01 11:06:40'),
(89, 'MANUFACTURING WORKER\r', 'عامل تصنيع', '2025-09-01 11:06:40'),
(90, ' PRODUCTION MGR\r', 'مدير إنتاج', '2025-09-01 11:06:40'),
(91, 'CNC MACHINE OPT\r', 'مشغل آلة CNC', '2025-09-01 11:06:40'),
(92, 'WELDER /FABRICATOR\r', 'لحام / فني تصنيع', '2025-09-01 11:06:40'),
(93, 'LOADING FACTOR\r', 'عامل تحميل', '2025-09-01 11:06:40'),
(94, 'MAINTENANCE ENGINEER\r', 'مهندس صيانة', '2025-09-01 11:06:40'),
(95, 'LOGISTIC SUPERVISOR\r', 'مشرف لوجستي', '2025-09-01 11:06:40'),
(96, 'ADMIN AST.\r', 'مساعد إداري', '2025-09-01 11:06:40'),
(97, 'WELDING TECHNICIAN\r', 'فني لحام', '2025-09-01 11:06:40'),
(98, 'ELECTRICAL MAINT.\r', 'فني صيانة كهربائية', '2025-09-01 11:06:40'),
(99, 'SALESMAN AND PREP\r', 'بائع ومحضر', '2025-09-01 11:06:40'),
(100, 'HR COORDINATOR\r', 'منسق موارد بشرية', '2025-09-01 11:06:40'),
(101, 'CUSTOMER DATA OFFICER\r', 'مسؤول بيانات العملاء', '2025-09-01 11:06:40'),
(102, 'IT\r', 'تقنية المعلومات', '2025-09-01 11:06:40'),
(103, 'TRAINEE', 'متدرب', '2025-09-01 11:01:25');

-- --------------------------------------------------------

--
-- Table structure for table `admin_login`
--

CREATE TABLE `admin_login` (
  `id` int(255) NOT NULL,
  `emp_id` varchar(11) NOT NULL,
  `id_iqama` varchar(15) NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `user_type` enum('administrator','hr','gm','dept_user','assistant','employee') NOT NULL DEFAULT 'employee' COMMENT 'References roles table',
  `emp_type` varchar(255) NOT NULL,
  `user_role` int(10) NOT NULL,
  `dept` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_pass` varchar(100) NOT NULL,
  `mobile` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `bk_password` varchar(255) NOT NULL,
  `otp` varchar(150) DEFAULT NULL,
  `otp_expiration` datetime DEFAULT NULL,
  `bk_otp` varchar(50) DEFAULT NULL,
  `avatar` varchar(255) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 0,
  `preferred_language` enum('en','ar') NOT NULL DEFAULT 'en' COMMENT 'User''s preferred language code (e.g., en, ar)',
  `remember_token` varchar(255) DEFAULT NULL,
  `remember_token_expiry` datetime DEFAULT NULL,
  `remember` varchar(10) DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `admin_login`
--

INSERT INTO `admin_login` (`id`, `emp_id`, `id_iqama`, `fullname`, `username`, `user_type`, `emp_type`, `user_role`, `dept`, `email`, `email_pass`, `mobile`, `password`, `bk_password`, `otp`, `otp_expiration`, `bk_otp`, `avatar`, `status`, `preferred_language`, `remember_token`, `remember_token_expiry`, `remember`, `last_login`, `created_at`, `updated_at`) VALUES
(1, '5430', '2337318717', 'Anees Mughal', 'root', 'administrator', 'Manager', 1, '6', 'a.afzal@almutlak.com', 'Hain6539306', '0599723451', '', '', NULL, NULL, NULL, 'assets/emp_pics/2337318717.29512160_2101570866527042_5368874645299751559_n.jpg', 1, 'ar', '4b70548966e8a7baf0a5d46a64be4651d2baff2ea30d9a9f225aa35271f9e61e', '2025-10-04 08:50:19', NULL, '2025-09-04 05:50:19', '2025-09-04 13:34:56', '0000-00-00 00:00:00'),
(2, '5408', '1070652175', 'Sharifah Ahmed ALsalhi', 'sharifah', 'hr', 'Manager', 0, '5', 'a.afzal@almutlak.com', 'MocH#770', '0552514413', '', '', NULL, NULL, NULL, './assets/emp_pics/defultFemale.jpg', 1, 'en', '6a7defae4280ee205fde551f820ba2808ad6f08506438998488d5e0a4b5b8485', '2025-09-25 14:35:10', NULL, '2025-08-26 11:35:10', '2025-08-26 11:35:10', '2025-06-11 11:00:07'),
(4, '3928', '2006634469', 'MAHER THABET AL JABARI', 'mahar', 'gm', 'Manager', 0, '10', 'a.afzal@almutlak.com', '123456', '0505618108', '', '', NULL, NULL, NULL, './assets/emp_pics/defult.png', 1, 'en', '7b0fa4ef227b81de6eed1246118da93ac68e4f474d18f0c417eeec0bab446f5f', '2025-09-25 14:49:00', NULL, '2025-08-26 11:49:00', '2025-08-26 11:49:00', '2025-06-11 08:11:15'),
(3, '4120', '2103034787', 'GAMAL ABDELRAHMAN ABDELRAHMAN', 'gamal', 'dept_user', 'Manager', 0, '2', 'a.afzal@almutlak.com', 'hain123', '0500575208', '', '', NULL, NULL, NULL, './assets/emp_pics/defult.png', 1, 'en', '5854e90e7971086cea0303e06df35d03f8127100cc360e73dcde5f6c2cf9239d', '2025-09-25 14:50:05', NULL, '2025-08-26 11:50:05', '2025-08-26 11:50:05', '2025-06-11 07:59:00'),
(5, '3061', '2275998009', 'AHMED ABDELHAY A SOLIMAN', 'ahmed', 'assistant', '', 0, '2', 'a.afzal@almutlak.com', '123', '0552592382', '', '', NULL, NULL, NULL, './assets/emp_pics/defult.png', 1, 'en', NULL, NULL, NULL, '2025-08-27 10:12:35', '2025-09-01 12:39:29', '2025-06-11 10:42:29'),
(6, '3431', '2293543845', 'LEANDRO BUNAG SANTIAGO', 'andro', 'assistant', '', 0, '5', 'a.afzal@almutlak.com', '123', '0562017534', '', '', NULL, NULL, NULL, './assets/emp_pics/defult.png', 1, 'en', '3467969513274b52e5829b5f296523eaded713c5c583b5879f47bd61cda3a8e9', '2025-09-25 14:32:21', NULL, '2025-08-26 11:32:21', '2025-08-26 11:32:21', '2025-06-12 10:43:39'),
(28, '1999', '2145832388', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$wYYziRj4NX7xWjVh2pJ/qu2DZF.hoBM8YNQ1g9TFzugymRtJ74oee', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-08-22 12:37:26', '2025-08-22 12:37:26'),
(13, '1928', '2122407089', 'N/A', '', 'employee', '', 0, '4', '', '', '', '$2y$10$Qc1eU7jlBVLJGJeJdukLfe0t37caPzJIUuyE4yUHaOpKk5dJTTPuK', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-08-20 09:28:26', '2025-08-20 09:28:26'),
(29, '1931', '2140657178', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$8qQw0fqXijswARBt0RFv3uipLJF6Z1Ofr6StbHmkY8iba6N.j2TVm', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-08-25 09:15:01', '2025-08-25 09:15:01'),
(30, '1996', '2145835134', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$usP1lhI/d.Za1Z.2W8QRauWqJO1eX7EilKbk.4v9rAPKiGry3Kbhi', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-08-27 07:22:10', '2025-08-26 11:28:09'),
(31, '5127', '2506165311', 'N/A', '', 'employee', '', 0, '6', 'makaramjavaid@gmail.com', '', '', '$2y$10$.7YX/4nlDoiAAFF0E8tyduZyZaHzoiIgrpHJPojE8dPHY9PsG6Z/q', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-08-26 11:30:36', '2025-08-26 11:30:36');

-- --------------------------------------------------------

--
-- Table structure for table `apply_vac_dep`
--

CREATE TABLE `apply_vac_dep` (
  `id` int(11) NOT NULL,
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
  `vacdays` int(50) NOT NULL,
  `replacement_person` varchar(255) NOT NULL,
  `ticket_pay` varchar(255) NOT NULL,
  `permit_fee` varchar(100) NOT NULL,
  `empgid` varchar(150) NOT NULL,
  `hr_note` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `gm_note` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `date_reg` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assets`
--

CREATE TABLE `assets` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL COMMENT 'e.g., Laptop, Mobile Phone, SIM Card, Car',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assets`
--

INSERT INTO `assets` (`id`, `name`, `created_at`) VALUES
(1, 'Mobile Phone', '2025-08-27 12:35:43'),
(2, 'Laptop', '2025-08-27 12:35:43'),
(3, 'SIM Card', '2025-08-27 12:35:43'),
(4, 'Car', '2025-08-27 12:35:43');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `emp_id` int(11) NOT NULL,
  `state` varchar(50) NOT NULL,
  `date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `time_in` varchar(20) NOT NULL,
  `time_out` varchar(20) NOT NULL,
  `type` varchar(10) NOT NULL,
  `note` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bank`
--

CREATE TABLE `bank` (
  `id` int(11) NOT NULL,
  `BANK_NO` int(11) NOT NULL,
  `BANK_NAME` varchar(100) NOT NULL,
  `BANK_NAME_S` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `bank`
--

INSERT INTO `bank` (`id`, `BANK_NO`, `BANK_NAME`, `BANK_NAME_S`) VALUES
(1, 6, 'الراجحي', 'RJHISARIXXX\r'),
(2, 7, 'الرياض', 'RIBLSARIXXX\r'),
(3, 1, 'البنك البريطاني ( ساب )', 'SABBSARIXXX\r'),
(4, 2, 'البنك الاهلي السعودي', 'NCBKSAJEXXX\r'),
(5, 3, 'الامريكي (سامبا)', 'SAMBSARIXXX\r'),
(6, 4, 'العربي', 'ARNBSARIXXX\r'),
(7, 5, 'الجزيرة', 'BJAZSAJEXXX\r'),
(8, 8, 'الهولندي', 'AAALSARIXXXXXXX\r'),
(9, 9, 'الفرنسي', 'BSFRSARIXXX\r'),
(10, 10, 'البلاد', 'ALBISARIXXX\r'),
(11, 11, 'السعودي للاستثمار', 'SIBCSARIXXX\r'),
(12, 12, 'الانماء', 'INMASARIXXX\r'),
(13, 13, 'الاول', 'AAALSARIXXX\r');

-- --------------------------------------------------------

--
-- Table structure for table `bank_list`
--

CREATE TABLE `bank_list` (
  `id` int(11) NOT NULL,
  `bnk_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `bank_name_ar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_name_s` varchar(30) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `bank_list`
--

INSERT INTO `bank_list` (`id`, `bnk_id`, `name`, `bank_name_ar`, `bank_name_s`) VALUES
(1, 2, 'The National Commercial Bank', 'البنك الأهلي التجاري', 'NCBKSAJEXXX'),
(2, 0, 'The Saudi British Bank', 'البنك السعودي البريطاني', 'SABBSARIXXX'),
(3, 4, 'Saudi Investment Bank', 'البنك السعودي للاستثمار', 'SIBCSARIXXX'),
(4, 7, 'Alinma Bank', 'مصرف الإنماء', 'INMASARIXXX'),
(5, 0, 'Banque Saudi Fransi', 'البنك السعودي الفرنسي', 'BSFRSARIXXX'),
(6, 3, 'Riyad Bank', 'بنك الرياض', 'RIBLSARIXXX'),
(7, 5, 'Samba Financial Group', 'مجموعة سامبا المالية', 'SAMBSARIXXX'),
(8, 0, 'Saudi Hollandi Bank', 'البنك السعودي الهولندي', 'AAALSARIXXXXXXX'),
(9, 6, 'Al-Rajhi Bank', 'مصرف الراجحي', 'RJHISARIXXX'),
(10, 8, 'Arab National Bank', 'البنك العربي الوطني', 'ARNBSARIXXX'),
(11, 0, 'Bank Al-Bilad', 'بنك البلاد', 'ALBISARIXXX'),
(12, 9, 'Bank AlJazira', 'بنك الجزيرة', 'BJAZSAJEXXX');

-- --------------------------------------------------------

--
-- Table structure for table `benefit_types`
--

CREATE TABLE `benefit_types` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `calculation_type` enum('fixed','overtime_basic','overtime_total') DEFAULT 'fixed',
  `status` tinyint(4) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `benefit_types`
--

INSERT INTO `benefit_types` (`id`, `name`, `calculation_type`, `status`) VALUES
(1, 'Overtime', 'overtime_basic', 1),
(2, 'Other Income', 'fixed', 1),
(3, 'Bonus', 'fixed', 1),
(4, 'Transportation Allowance', 'fixed', 1),
(5, 'Meal Allowance', 'fixed', 1);

-- --------------------------------------------------------

--
-- Table structure for table `brand_name`
--

CREATE TABLE `brand_name` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `descr` varchar(255) NOT NULL,
  `date_reg` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

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
(36, 'GEEPAS', '', '2020-10-12T14:06:33+03:00'),
(37, 'Kelvinator', '', '2020-11-22T15:49:25+03:00'),
(38, 'EKA', '', '2020-11-24T11:36:19+03:00'),
(39, 'FRUCOSOL', '', '2020-11-24T11:39:56+03:00');

-- --------------------------------------------------------

--
-- Table structure for table `cars`
--

CREATE TABLE `cars` (
  `id` int(11) NOT NULL,
  `maker_name` varchar(255) NOT NULL,
  `model` varchar(255) NOT NULL,
  `made_year` varchar(255) NOT NULL,
  `plate_no` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `status` varchar(10) NOT NULL DEFAULT '1',
  `remarks` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `cars`
--

INSERT INTO `cars` (`id`, `maker_name`, `model`, `made_year`, `plate_no`, `type`, `status`, `remarks`, `created_at`, `updated_at`) VALUES
(1, '35', '636', '2013', '2364-XNB', 'Car', '1', '', '2025-06-22 05:29:49', '2025-06-22 05:29:49'),
(2, '35', '636', '2013', '1234-DDF', 'Car', '1', '', '2025-09-02 06:48:37', '2025-09-02 06:48:37');

-- --------------------------------------------------------

--
-- Table structure for table `cars_docu`
--

CREATE TABLE `cars_docu` (
  `id` int(11) NOT NULL,
  `car_id` int(100) NOT NULL,
  `doc_type` varchar(100) NOT NULL,
  `issue_date` varchar(100) NOT NULL,
  `exp_date` varchar(100) NOT NULL,
  `file` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `cars_docu`
--

INSERT INTO `cars_docu` (`id`, `car_id`, `doc_type`, `issue_date`, `exp_date`, `file`, `created_at`, `updated_at`) VALUES
(4, 1, 'Licence', '2025-07-02', '2025-07-31', '', '2025-07-02 09:19:04', '2025-07-02 09:19:04');

-- --------------------------------------------------------

--
-- Table structure for table `cars_drv`
--

CREATE TABLE `cars_drv` (
  `id` int(11) NOT NULL,
  `car_id` int(100) NOT NULL,
  `car_user` varchar(255) NOT NULL,
  `rcv_date` varchar(255) NOT NULL,
  `rtn_date` varchar(255) NOT NULL,
  `status` varchar(10) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `cars_drv`
--

INSERT INTO `cars_drv` (`id`, `car_id`, `car_user`, `rcv_date`, `rtn_date`, `status`, `created_at`, `updated_at`) VALUES
(4, 1, '5430', '2025-06-22', '2025-06-22 10:14:14', '0', '2025-06-22 05:30:03', '2025-06-22 07:14:14'),
(5, 1, '5127', '2025-06-22', '2025-06-22 10:15:00', '0', '2025-06-22 07:14:29', '2025-06-22 07:15:00'),
(6, 1, '5430', '2025-06-22', '2025-06-23 14:10:42', '0', '2025-06-22 07:17:10', '2025-06-23 11:10:42'),
(7, 1, '5127', '2025-06-23', '2025-06-23 14:11:23', '0', '2025-06-23 11:10:55', '2025-06-23 11:11:23'),
(8, 1, '5430', '2025-07-02', '2025-07-02 12:20:15', '0', '2025-07-02 09:19:25', '2025-07-02 09:20:15'),
(9, 1, '4119', '2025-07-02', '2025-07-07 11:17:30', '0', '2025-07-02 09:20:47', '2025-07-07 08:17:30'),
(10, 1, '5430', '2025-07-07', '', '1', '2025-07-07 08:18:02', '2025-07-07 08:18:02');

-- --------------------------------------------------------

--
-- Table structure for table `cars_maint`
--

CREATE TABLE `cars_maint` (
  `id` int(11) NOT NULL,
  `car_id` int(100) NOT NULL,
  `meter` varchar(100) NOT NULL,
  `diffmeter` varchar(255) NOT NULL,
  `date` varchar(255) NOT NULL,
  `car_user` varchar(100) NOT NULL,
  `type` varchar(255) NOT NULL,
  `details` varchar(255) NOT NULL,
  `remarks` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `cars_maint`
--

INSERT INTO `cars_maint` (`id`, `car_id`, `meter`, `diffmeter`, `date`, `car_user`, `type`, `details`, `remarks`, `created_at`, `updated_at`) VALUES
(1, 1, '70000', '0KM', '2025-07-02', '4119', 'Oil', '800', '', '2025-07-02 09:21:57', '2025-07-02 09:21:57');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `post_id` varchar(150) NOT NULL,
  `name` varchar(255) NOT NULL,
  `price` varchar(255) NOT NULL,
  `qty` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cart_order_status`
--

CREATE TABLE `cart_order_status` (
  `id` int(11) NOT NULL,
  `order_id` varchar(255) NOT NULL,
  `uid` varchar(100) DEFAULT NULL,
  `emp_name` varchar(255) NOT NULL,
  `notes` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `status` varchar(100) NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `cart_order_status`
--

INSERT INTO `cart_order_status` (`id`, `order_id`, `uid`, `emp_name`, `notes`, `status`, `created_at`) VALUES
(1, '2307202331875', '23', '', '', 'draft', '2023-07-20 11:21:06');

-- --------------------------------------------------------

--
-- Table structure for table `cart_wishlist`
--

CREATE TABLE `cart_wishlist` (
  `id` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `car_maker`
--

CREATE TABLE `car_maker` (
  `id` int(11) NOT NULL,
  `maker` varchar(150) NOT NULL,
  `logo_pos` varchar(30) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `car_maker`
--

INSERT INTO `car_maker` (`id`, `maker`, `logo_pos`, `created_at`) VALUES
(1, 'Alfa Romeo\r', '-146px -1280px', '2023-10-02 05:46:35'),
(2, 'Aston Martin\r', '-9px -726px', '2023-10-02 05:47:51'),
(3, 'Audi\r', '-9px -2618px', '2023-10-02 05:47:55'),
(4, 'Baic\r', '-6px -1032px', '2023-10-02 05:48:07'),
(5, 'Bentley\r', '-9px -1274px', '2023-10-02 05:48:24'),
(6, 'BMW\r', '-146px -60px', '2023-10-02 05:48:41'),
(7, 'Buick\r', '', '2023-10-01 10:53:51'),
(8, 'BYD\r', '-3px -1338px', '2023-10-02 05:49:32'),
(9, 'Cadillac\r', '-146px -1582px', '2023-10-02 05:49:41'),
(10, 'Changan\r', '-9px -1949px', '2023-10-02 05:49:53'),
(11, 'Chery\r', '-146px -1398px', '2023-10-02 05:50:07'),
(12, 'Chevrolet\r', '0px -180px', '2023-10-02 05:50:23'),
(13, 'Chrysler\r', '-9px -1641px', '2023-10-02 05:50:34'),
(14, 'Citroen\r', '-146px -1523px', '2023-10-02 05:50:45'),
(15, 'CMC\r', '', '2023-10-01 10:53:51'),
(16, 'Daewoo\r', '', '2023-10-01 10:53:51'),
(17, 'Daihatsu\r', '-142px -2252px', '2023-10-02 05:51:17'),
(18, 'Dodge\r', '-6px -2674px', '2023-10-02 05:51:27'),
(19, 'Dongfeng\r', '-142px -1156px', '2023-10-02 05:51:37'),
(20, 'EXEED\r', '-18px -3307px', '2023-10-02 05:51:47'),
(21, 'FAW\r', '-143px -1949px', '2023-10-02 05:51:57'),
(22, 'Ferrari\r', '-148px -1645px', '2023-10-02 05:52:05'),
(23, 'Fiat\r', '-146px -550px', '2023-10-02 05:52:16'),
(24, 'Ford\r', '-146px -239px', '2023-10-02 05:52:29'),
(25, 'Foton\r', '-9px -1463px', '2023-10-02 05:52:39'),
(26, 'GAC\r', '-9px -2010px', '2023-10-02 05:52:54'),
(27, 'Geely\r', '-9px -789px', '2023-10-02 05:53:04'),
(28, 'Genesis\r', '-146px -2006px', '2023-10-02 05:53:14'),
(29, 'GMC\r', '-9px -1521px', '2023-10-02 05:53:24'),
(30, 'Great Wall\r', '-9px -364px', '2023-10-02 05:53:39'),
(31, 'Haval\r', '-6px -2253px', '2023-10-02 05:53:51'),
(32, 'Honda\r', '-146px -303px', '2023-10-02 05:54:04'),
(33, 'Hongqi\r', '-140px -2904px', '2023-10-02 05:54:15'),
(34, 'Hummer\r', '-157px -3453px', '2023-10-02 05:54:26'),
(35, 'Hyundai\r', '-143px 5px', '2023-10-02 05:54:40'),
(36, 'Infinit', '-9px -2131px', '2023-10-02 11:57:33'),
(37, 'Isuzu\r', '-9px -1825px', '2023-10-02 05:55:09'),
(38, 'JAC\r', '-9px -1218px', '2023-10-02 05:55:30'),
(39, 'Jaguar\r', '-139px -360px', '2023-10-02 05:55:41'),
(40, 'Jeep\r', '-143px -2126px', '2023-10-02 05:55:51'),
(41, 'JETOUR\r', '-9px -2908px', '2023-10-02 05:56:20'),
(42, 'JMC\r', '', '2023-10-01 10:53:51'),
(43, 'KIA\r', '-6px -120px', '2023-10-02 05:56:40'),
(44, 'Lamborghini\r', '-9px -2195px', '2023-10-02 05:56:51'),
(45, 'Land Rover\r', '-146px -487px', '2023-10-02 05:57:05'),
(46, 'Lexus\r', '-9px -1094px', '2023-10-02 05:57:16'),
(47, 'Lifan\r', '-146px -789px', '2023-10-02 05:57:24'),
(48, 'Lincoln\r', '-146px -851px', '2023-10-02 05:57:37'),
(49, 'Mahindra\r', '-146px -2556px', '2023-10-02 05:57:47'),
(50, 'Maserati\r', '-9px -851px', '2023-10-02 05:58:03'),
(51, 'MAXUS\r', '-146px -1091px', '2023-10-02 05:58:18'),
(52, 'Mazda\r', '-9px -670px', '2023-10-02 05:58:39'),
(53, 'McLaren\r', '-138px -910px', '2023-10-02 05:58:51'),
(54, 'Mercedes-Benz', '-9px -57px', '2023-10-02 12:11:43'),
(55, 'Mercury\r', '', '2023-10-01 10:53:51'),
(56, 'MG\r', '-9px -2072px', '2023-10-02 06:00:51'),
(57, 'Mitsubishi\r', '-146px -124px', '2023-10-02 06:01:39'),
(58, 'Motorcycles\r', '', '2023-10-01 10:53:51'),
(59, 'Nissan\r', '-146px -182px', '2023-10-02 06:02:49'),
(60, 'Opel\r', '-3px -973px', '2023-10-02 06:03:03'),
(61, 'Peugeot\r', '-146px -1216px', '2023-10-02 06:03:39'),
(62, 'Porsche\r', '-146px -2194px', '2023-10-02 06:03:52'),
(63, 'Proton\r', '-146px -608px', '2023-10-02 06:04:00'),
(64, 'Renault\r', '-146px -1765px', '2023-10-02 06:04:15'),
(65, 'Rolls-Royce\r', '-146px -1338px', '2023-10-02 06:04:49'),
(66, 'SABB\r', '', '2023-10-01 10:53:51'),
(67, 'Seat\r', '-142px -1705px', '2023-10-02 06:05:34'),
(68, 'Skoda\r', '-146px -426px', '2023-10-02 06:05:49'),
(69, 'SMART\r', '', '2023-10-01 10:53:51'),
(70, 'Soueaste\r', '-146px -2832px', '2023-10-02 06:06:25'),
(71, 'SsangYong\r', '-9px -1705px', '2023-10-02 06:06:42'),
(72, 'Subaru\r', '-9px -1765px', '2023-10-02 06:07:03'),
(73, 'Suzuki\r', '-9px -243px', '2023-10-02 06:07:14'),
(74, 'TATA\r', '-9px -423px', '2023-10-02 06:07:30'),
(75, 'Tesla\r', '-9px -607px', '2023-10-02 06:07:56'),
(76, 'Toyota\r', '-9px -302px', '2023-10-02 06:08:16'),
(77, 'Trucks and heavy equipment\r', '', '2023-10-01 10:53:51'),
(78, 'VICTORY AUTO\r', '', '2023-10-01 10:53:51'),
(79, 'Volkswagen\r', '-9px -1402px', '2023-10-02 06:08:54'),
(80, 'Volvo\r', '-146px -669px', '2023-10-02 06:09:10'),
(81, 'Zotye\r', '-9px -2756px', '2023-10-02 06:09:24'),
(82, 'ZXAUTO\r', '-12px -3446px', '2023-10-02 06:09:37');

-- --------------------------------------------------------

--
-- Table structure for table `car_model`
--

CREATE TABLE `car_model` (
  `id` int(11) NOT NULL,
  `mkid` varchar(20) NOT NULL,
  `model` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `car_model`
--

INSERT INTO `car_model` (`id`, `mkid`, `model`, `created_at`) VALUES
(1, '1', '147\r', '2023-10-03 05:11:03'),
(2, '1', '156\r', '2023-10-03 05:11:03'),
(3, '1', '159\r', '2023-10-03 05:11:03'),
(4, '1', '159 Ti\r', '2023-10-03 05:11:03'),
(5, '1', '4C\r', '2023-10-03 05:11:03'),
(6, '1', 'Brera\r', '2023-10-03 05:11:03'),
(7, '1', 'GT\r', '2023-10-03 05:11:03'),
(8, '1', 'Giulia\r', '2023-10-03 05:11:03'),
(9, '1', 'Giulietta\r', '2023-10-03 05:11:03'),
(10, '1', 'MiTo\r', '2023-10-03 05:11:03'),
(11, '1', 'Stelvio\r', '2023-10-03 05:11:03'),
(12, '1', 'Tonale\r', '2023-10-03 05:11:03'),
(13, '2', 'DB11\r', '2023-10-03 05:11:03'),
(14, '2', 'DB11 AMR\r', '2023-10-03 05:11:03'),
(15, '2', 'DB7 GT\r', '2023-10-03 05:11:03'),
(16, '2', 'DB9\r', '2023-10-03 05:11:03'),
(17, '2', 'DB9 GT\r', '2023-10-03 05:11:03'),
(18, '2', 'DB9 Volante\r', '2023-10-03 05:11:03'),
(19, '2', 'DBS\r', '2023-10-03 05:11:03'),
(20, '2', 'DBS Superleggera\r', '2023-10-03 05:11:03'),
(21, '2', 'DBS Superleggera Volante\r', '2023-10-03 05:11:03'),
(22, '2', 'DBX\r', '2023-10-03 05:11:03'),
(23, '2', 'Lagonda\r', '2023-10-03 05:11:03'),
(24, '2', 'Rapide\r', '2023-10-03 05:11:03'),
(25, '2', 'Rapide AMR\r', '2023-10-03 05:11:03'),
(26, '2', 'Rapide S\r', '2023-10-03 05:11:03'),
(27, '2', 'Valhalla\r', '2023-10-03 05:11:03'),
(28, '2', 'Vanquish\r', '2023-10-03 05:11:03'),
(29, '2', 'Vanquish S Volante\r', '2023-10-03 05:11:03'),
(30, '2', 'Vanquish Volante\r', '2023-10-03 05:11:03'),
(31, '2', 'Vanquish Zagato\r', '2023-10-03 05:11:03'),
(32, '2', 'Vantage\r', '2023-10-03 05:11:03'),
(33, '2', 'Vantage AMR\r', '2023-10-03 05:11:03'),
(34, '2', 'Vantage Roadster\r', '2023-10-03 05:11:03'),
(35, '2', 'Virage\r', '2023-10-03 05:11:03'),
(36, '3', 'A1\r', '2023-10-03 05:11:03'),
(37, '3', 'A1 Sportback\r', '2023-10-03 05:11:03'),
(38, '3', 'A3 Sedan\r', '2023-10-03 05:11:03'),
(39, '3', 'A3 Sportback\r', '2023-10-03 05:11:03'),
(40, '3', 'A4\r', '2023-10-03 05:11:03'),
(41, '3', 'A5 Cabriolet\r', '2023-10-03 05:11:03'),
(42, '3', 'A5 Convertible\r', '2023-10-03 05:11:03'),
(43, '3', 'A5 Coupe\r', '2023-10-03 05:11:03'),
(44, '3', 'A5 Sedan\r', '2023-10-03 05:11:03'),
(45, '3', 'A5 Sportback\r', '2023-10-03 05:11:03'),
(46, '3', 'A6\r', '2023-10-03 05:11:03'),
(47, '3', 'A7\r', '2023-10-03 05:11:03'),
(48, '3', 'A7 Sportback\r', '2023-10-03 05:11:03'),
(49, '3', 'A8\r', '2023-10-03 05:11:03'),
(50, '3', 'A8 L\r', '2023-10-03 05:11:03'),
(51, '3', 'Q2\r', '2023-10-03 05:11:03'),
(52, '3', 'Q3\r', '2023-10-03 05:11:03'),
(53, '3', 'Q3 Sportback\r', '2023-10-03 05:11:03'),
(54, '3', 'Q5\r', '2023-10-03 05:11:03'),
(55, '3', 'Q5 Sportback\r', '2023-10-03 05:11:03'),
(56, '3', 'Q7\r', '2023-10-03 05:11:03'),
(57, '3', 'Q8\r', '2023-10-03 05:11:03'),
(58, '3', 'Q8 e-tron\r', '2023-10-03 05:11:03'),
(59, '3', 'R8 Coupe\r', '2023-10-03 05:11:03'),
(60, '3', 'R8 Coupe V10\r', '2023-10-03 05:11:03'),
(61, '3', 'R8 Spyder\r', '2023-10-03 05:11:03'),
(62, '3', 'RS 3 Sportback\r', '2023-10-03 05:11:03'),
(63, '3', 'RS Q3\r', '2023-10-03 05:11:03'),
(64, '3', 'RS Q3 Sporback\r', '2023-10-03 05:11:03'),
(65, '3', 'RS Q8\r', '2023-10-03 05:11:03'),
(66, '3', 'RS3 Sedan\r', '2023-10-03 05:11:03'),
(67, '3', 'RS4\r', '2023-10-03 05:11:03'),
(68, '3', 'RS4 Avant\r', '2023-10-03 05:11:03'),
(69, '3', 'RS5 Cabriolet\r', '2023-10-03 05:11:03'),
(70, '3', 'RS5 Coupe\r', '2023-10-03 05:11:03'),
(71, '3', 'RS5 Sportback\r', '2023-10-03 05:11:03'),
(72, '3', 'RS6 Avant\r', '2023-10-03 05:11:03'),
(73, '3', 'RS6 Avant Performance\r', '2023-10-03 05:11:03'),
(74, '3', 'RS7\r', '2023-10-03 05:11:03'),
(75, '3', 'S3\r', '2023-10-03 05:11:03'),
(76, '3', 'S3 Sedan\r', '2023-10-03 05:11:03'),
(77, '3', 'S3 Sportback\r', '2023-10-03 05:11:03'),
(78, '3', 'S4\r', '2023-10-03 05:11:03'),
(79, '3', 'S5 Coupe\r', '2023-10-03 05:11:03'),
(80, '3', 'S5 Sportback\r', '2023-10-03 05:11:03'),
(81, '3', 'S6\r', '2023-10-03 05:11:03'),
(82, '3', 'S7\r', '2023-10-03 05:11:03'),
(83, '3', 'S8\r', '2023-10-03 05:11:03'),
(84, '3', 'SQ5\r', '2023-10-03 05:11:03'),
(85, '3', 'SQ5 Sportback\r', '2023-10-03 05:11:03'),
(86, '3', 'SQ8\r', '2023-10-03 05:11:03'),
(87, '3', 'TT\r', '2023-10-03 05:11:03'),
(88, '3', 'TT Coupe\r', '2023-10-03 05:11:03'),
(89, '3', 'TT Coupe Quattro\r', '2023-10-03 05:11:03'),
(90, '3', 'TT RS Coupe\r', '2023-10-03 05:11:03'),
(91, '3', 'TT Roadster\r', '2023-10-03 05:11:03'),
(92, '3', 'TTS Coupe\r', '2023-10-03 05:11:03'),
(93, '3', 'e-tron\r', '2023-10-03 05:11:03'),
(94, '3', 'e-tron GT\r', '2023-10-03 05:11:03'),
(95, '3', 'e-tron Sportback\r', '2023-10-03 05:11:03'),
(96, '4', 'A1 Hatchback\r', '2023-10-03 05:11:03'),
(97, '4', 'A1 Sedan\r', '2023-10-03 05:11:03'),
(98, '4', 'A115 Hatchback\r', '2023-10-03 05:11:03'),
(99, '4', 'A115 Sedan\r', '2023-10-03 05:11:03'),
(100, '4', 'A5\r', '2023-10-03 05:11:03'),
(101, '4', 'BJ40\r', '2023-10-03 05:11:03'),
(102, '4', 'BJ40 Plus\r', '2023-10-03 05:11:03'),
(103, '4', 'BJ80\r', '2023-10-03 05:11:03'),
(104, '4', 'D50\r', '2023-10-03 05:11:03'),
(105, '4', 'F40\r', '2023-10-03 05:11:03'),
(106, '4', 'X35\r', '2023-10-03 05:11:03'),
(107, '4', 'X55\r', '2023-10-03 05:11:03'),
(108, '4', 'X65\r', '2023-10-03 05:11:03'),
(109, '4', 'X7\r', '2023-10-03 05:11:03'),
(110, '5', 'Arnage\r', '2023-10-03 05:11:03'),
(111, '5', 'Azure\r', '2023-10-03 05:11:03'),
(112, '5', 'Bentayga\r', '2023-10-03 05:11:03'),
(113, '5', 'Brooklands\r', '2023-10-03 05:11:03'),
(114, '5', 'Brooklyn\r', '2023-10-03 05:11:03'),
(115, '5', 'Continental\r', '2023-10-03 05:11:03'),
(116, '5', 'Continental GT\r', '2023-10-03 05:11:03'),
(117, '5', 'Continental GT Convertible\r', '2023-10-03 05:11:03'),
(118, '5', 'Continental GTC\r', '2023-10-03 05:11:03'),
(119, '5', 'Continental Supersports\r', '2023-10-03 05:11:03'),
(120, '5', 'Continental Supersports Convertible\r', '2023-10-03 05:11:03'),
(121, '5', 'EXP 100 GT\r', '2023-10-03 05:11:03'),
(122, '5', 'Flying Spur\r', '2023-10-03 05:11:03'),
(123, '5', 'GT3-R\r', '2023-10-03 05:11:03'),
(124, '5', 'Mulsanne\r', '2023-10-03 05:11:03'),
(125, '5', 'New Flying Spur\r', '2023-10-03 05:11:03'),
(126, '5', 'R Type\r', '2023-10-03 05:11:03'),
(127, '5', 'S1\r', '2023-10-03 05:11:03'),
(128, '5', 'Turbo\r', '2023-10-03 05:11:03'),
(129, '6', '1 Series\r', '2023-10-03 05:11:03'),
(130, '6', '1 Series Convertible\r', '2023-10-03 05:11:03'),
(131, '6', '1 Series Coupe\r', '2023-10-03 05:11:03'),
(132, '6', '1 Series Hatchback\r', '2023-10-03 05:11:03'),
(133, '6', '2 Series Active Tourer\r', '2023-10-03 05:11:03'),
(134, '6', '2 Series Convertible\r', '2023-10-03 05:11:03'),
(135, '6', '2 Series Coupe\r', '2023-10-03 05:11:03'),
(136, '6', '2 Series Grand Coupe\r', '2023-10-03 05:11:03'),
(137, '6', '3 Series\r', '2023-10-03 05:11:03'),
(138, '6', '3 Series Convertible\r', '2023-10-03 05:11:03'),
(139, '6', '3 Series Coupe\r', '2023-10-03 05:11:03'),
(140, '6', '3 Series Gran Turismo\r', '2023-10-03 05:11:03'),
(141, '6', '3 Series Sedan\r', '2023-10-03 05:11:03'),
(142, '6', '325i\r', '2023-10-03 05:11:03'),
(143, '6', '4 Series Convertible\r', '2023-10-03 05:11:03'),
(144, '6', '4 Series Coupe\r', '2023-10-03 05:11:03'),
(145, '6', '4 Series Gran Coupe\r', '2023-10-03 05:11:03'),
(146, '6', '5 Series\r', '2023-10-03 05:11:03'),
(147, '6', '5 Series Gran Turismo\r', '2023-10-03 05:11:03'),
(148, '6', '5 Series Sedan\r', '2023-10-03 05:11:03'),
(149, '6', '525i\r', '2023-10-03 05:11:03'),
(150, '6', '6 Series Convertible\r', '2023-10-03 05:11:03'),
(151, '6', '6 Series Coupe\r', '2023-10-03 05:11:03'),
(152, '6', '6 Series Gran Coupe\r', '2023-10-03 05:11:03'),
(153, '6', '6 Series Gran Turismo\r', '2023-10-03 05:11:03'),
(154, '6', '6 Series Sedan\r', '2023-10-03 05:11:03'),
(155, '6', '635 CSI\r', '2023-10-03 05:11:03'),
(156, '6', '7 Series\r', '2023-10-03 05:11:03'),
(157, '6', '745Li\r', '2023-10-03 05:11:03'),
(158, '6', '8 Series Convertible\r', '2023-10-03 05:11:03'),
(159, '6', '8 Series Coupe\r', '2023-10-03 05:11:03'),
(160, '6', '8 Series Gran Coupe\r', '2023-10-03 05:11:03'),
(161, '6', 'Alpina B7\r', '2023-10-03 05:11:03'),
(162, '6', 'Grand Turismo\r', '2023-10-03 05:11:03'),
(163, '6', 'IX\r', '2023-10-03 05:11:03'),
(164, '6', 'M2\r', '2023-10-03 05:11:03'),
(165, '6', 'M2 Competition\r', '2023-10-03 05:11:03'),
(166, '6', 'M2 Coupe\r', '2023-10-03 05:11:03'),
(167, '6', 'M3\r', '2023-10-03 05:11:03'),
(168, '6', 'M3 Convertible\r', '2023-10-03 05:11:03'),
(169, '6', 'M3 Coupe\r', '2023-10-03 05:11:03'),
(170, '6', 'M3 Sedan\r', '2023-10-03 05:11:03'),
(171, '6', 'M4 Convertible\r', '2023-10-03 05:11:03'),
(172, '6', 'M4 Coupe\r', '2023-10-03 05:11:03'),
(173, '6', 'M5\r', '2023-10-03 05:11:03'),
(174, '6', 'M5 Sedan\r', '2023-10-03 05:11:03'),
(175, '6', 'M6 Convertible\r', '2023-10-03 05:11:03'),
(176, '6', 'M6 Coupe\r', '2023-10-03 05:11:03'),
(177, '6', 'M6 Gran Coupe\r', '2023-10-03 05:11:03'),
(178, '6', 'M8 Competition\r', '2023-10-03 05:11:03'),
(179, '6', 'M8 Convertible\r', '2023-10-03 05:11:03'),
(180, '6', 'M8 Coupe\r', '2023-10-03 05:11:03'),
(181, '6', 'M8 Gran Coupe\r', '2023-10-03 05:11:03'),
(182, '6', 'X1\r', '2023-10-03 05:11:03'),
(183, '6', 'X2\r', '2023-10-03 05:11:03'),
(184, '6', 'X3\r', '2023-10-03 05:11:03'),
(185, '6', 'X3 M\r', '2023-10-03 05:11:03'),
(186, '6', 'X4\r', '2023-10-03 05:11:03'),
(187, '6', 'X4 M\r', '2023-10-03 05:11:03'),
(188, '6', 'X5\r', '2023-10-03 05:11:03'),
(189, '6', 'X5 M\r', '2023-10-03 05:11:03'),
(190, '6', 'X5 M50d\r', '2023-10-03 05:11:03'),
(191, '6', 'X6\r', '2023-10-03 05:11:03'),
(192, '6', 'X6 M\r', '2023-10-03 05:11:03'),
(193, '6', 'X7\r', '2023-10-03 05:11:03'),
(194, '6', 'Z3\r', '2023-10-03 05:11:03'),
(195, '6', 'Z4\r', '2023-10-03 05:11:03'),
(196, '6', 'Z4 M\r', '2023-10-03 05:11:03'),
(197, '6', 'Z4 Roadster\r', '2023-10-03 05:11:03'),
(198, '6', 'i3\r', '2023-10-03 05:11:03'),
(199, '6', 'i7\r', '2023-10-03 05:11:03'),
(200, '6', 'i8\r', '2023-10-03 05:11:03'),
(201, '6', 'i8 Roadster\r', '2023-10-03 05:11:03'),
(202, '6', 'iX3\r', '2023-10-03 05:11:03'),
(203, '7', 'Velite 7 EV\r', '2023-10-03 05:11:03'),
(204, '8', 'Atto 3\r', '2023-10-03 05:11:03'),
(205, '8', 'F3\r', '2023-10-03 05:11:03'),
(206, '8', 'F5\r', '2023-10-03 05:11:03'),
(207, '8', 'F7\r', '2023-10-03 05:11:03'),
(208, '8', 'Qin Pro\r', '2023-10-03 05:11:03'),
(209, '8', 'S6\r', '2023-10-03 05:11:03'),
(210, '8', 'S7\r', '2023-10-03 05:11:03'),
(211, '8', 'Song Pro\r', '2023-10-03 05:11:03'),
(212, '8', 'Tang\r', '2023-10-03 05:11:03'),
(213, '9', 'ATS\r', '2023-10-03 05:11:03'),
(214, '9', 'ATS Coupe\r', '2023-10-03 05:11:03'),
(215, '9', 'ATS-V Coupe\r', '2023-10-03 05:11:03'),
(216, '9', 'ATS-V Sedan\r', '2023-10-03 05:11:03'),
(217, '9', 'ATS-V. 3.6L\r', '2023-10-03 05:11:03'),
(218, '9', 'BLS\r', '2023-10-03 05:11:03'),
(219, '9', 'Brougham\r', '2023-10-03 05:11:03'),
(220, '9', 'CT4\r', '2023-10-03 05:11:03'),
(221, '9', 'CT4-V Blackwing\r', '2023-10-03 05:11:03'),
(222, '9', 'CT5\r', '2023-10-03 05:11:03'),
(223, '9', 'CT5-V Blackwing\r', '2023-10-03 05:11:03'),
(224, '9', 'CT6 Sedan\r', '2023-10-03 05:11:03'),
(225, '9', 'CTS\r', '2023-10-03 05:11:03'),
(226, '9', 'CTS Coupe\r', '2023-10-03 05:11:03'),
(227, '9', 'CTS V-Coupe\r', '2023-10-03 05:11:03'),
(228, '9', 'CTS-V\r', '2023-10-03 05:11:03'),
(229, '9', 'CTS-V Sedan\r', '2023-10-03 05:11:03'),
(230, '9', 'DTS\r', '2023-10-03 05:11:03'),
(231, '9', 'DeVille\r', '2023-10-03 05:11:03'),
(232, '9', 'ESCALADE\r', '2023-10-03 05:11:03'),
(233, '9', 'ESCALADE ESV\r', '2023-10-03 05:11:03'),
(234, '9', 'ESCALADE EXT\r', '2023-10-03 05:11:03'),
(235, '9', 'Eldorado\r', '2023-10-03 05:11:03'),
(236, '9', 'Fleetwood\r', '2023-10-03 05:11:03'),
(237, '9', 'SLS\r', '2023-10-03 05:11:03'),
(238, '9', 'SRX\r', '2023-10-03 05:11:03'),
(239, '9', 'STS/Seville\r', '2023-10-03 05:11:03'),
(240, '9', 'Series 62\r', '2023-10-03 05:11:03'),
(241, '9', 'XT4\r', '2023-10-03 05:11:03'),
(242, '9', 'XT5\r', '2023-10-03 05:11:03'),
(243, '9', 'XT5 Crossover\r', '2023-10-03 05:11:03'),
(244, '9', 'XT6\r', '2023-10-03 05:11:03'),
(245, '9', 'XTS\r', '2023-10-03 05:11:03'),
(246, '10', 'Alsvin\r', '2023-10-03 05:11:03'),
(247, '10', 'Benni\r', '2023-10-03 05:11:03'),
(248, '10', 'CS15\r', '2023-10-03 05:11:03'),
(249, '10', 'CS35\r', '2023-10-03 05:11:03'),
(250, '10', 'CS35 Plus\r', '2023-10-03 05:11:03'),
(251, '10', 'CS55\r', '2023-10-03 05:11:03'),
(252, '10', 'CS75\r', '2023-10-03 05:11:03'),
(253, '10', 'CS75 Plus\r', '2023-10-03 05:11:03'),
(254, '10', 'CS85\r', '2023-10-03 05:11:03'),
(255, '10', 'CS95\r', '2023-10-03 05:11:03'),
(256, '10', 'Eado\r', '2023-10-03 05:11:03'),
(257, '10', 'Eado DT\r', '2023-10-03 05:11:03'),
(258, '10', 'Eado Plus\r', '2023-10-03 05:11:03'),
(259, '10', 'Hunter\r', '2023-10-03 05:11:03'),
(260, '10', 'UNI-K\r', '2023-10-03 05:11:03'),
(261, '10', 'UNI-T\r', '2023-10-03 05:11:03'),
(262, '10', 'UNI-V\r', '2023-10-03 05:11:03'),
(263, '10', 'V7\r', '2023-10-03 05:11:03'),
(264, '10', 'V7\r', '2023-10-03 05:11:03'),
(265, '11', 'Arrizo 3\r', '2023-10-03 05:11:03'),
(266, '11', 'Arrizo 5\r', '2023-10-03 05:11:03'),
(267, '11', 'Arrizo 6 Pro\r', '2023-10-03 05:11:03'),
(268, '11', 'Arrizo 8\r', '2023-10-03 05:11:03'),
(269, '11', 'Arrizo5\r', '2023-10-03 05:11:03'),
(270, '11', 'Arrizo6\r', '2023-10-03 05:11:03'),
(271, '11', 'Arrizo7\r', '2023-10-03 05:11:03'),
(272, '11', 'E5\r', '2023-10-03 05:11:03'),
(273, '11', 'E8\r', '2023-10-03 05:11:03'),
(274, '11', 'Tiggo\r', '2023-10-03 05:11:03'),
(275, '11', 'Tiggo 2\r', '2023-10-03 05:11:03'),
(276, '11', 'Tiggo 8 Pro Max\r', '2023-10-03 05:11:03'),
(277, '11', 'Tiggo2 Pro\r', '2023-10-03 05:11:03'),
(278, '11', 'Tiggo3\r', '2023-10-03 05:11:03'),
(279, '11', 'Tiggo4 Pro\r', '2023-10-03 05:11:03'),
(280, '11', 'Tiggo5\r', '2023-10-03 05:11:03'),
(281, '11', 'Tiggo7\r', '2023-10-03 05:11:03'),
(282, '11', 'Tiggo7 Pro\r', '2023-10-03 05:11:03'),
(283, '11', 'Tiggo8\r', '2023-10-03 05:11:03'),
(284, '11', 'Tiggo8 Pro\r', '2023-10-03 05:11:03'),
(285, '12', 'Avalanche\r', '2023-10-03 05:11:03'),
(286, '12', 'Aveo\r', '2023-10-03 05:11:03'),
(287, '12', 'Blazer\r', '2023-10-03 05:11:03'),
(288, '12', 'Bolt EV\r', '2023-10-03 05:11:03'),
(289, '12', 'Camaro Convertible\r', '2023-10-03 05:11:03'),
(290, '12', 'Camaro Coupe\r', '2023-10-03 05:11:03'),
(291, '12', 'Caprice\r', '2023-10-03 05:11:03'),
(292, '12', 'Captiva\r', '2023-10-03 05:11:03'),
(293, '12', 'Colorado\r', '2023-10-03 05:11:03'),
(294, '12', 'Corvette\r', '2023-10-03 05:11:03'),
(295, '12', 'Cruze\r', '2023-10-03 05:11:03'),
(296, '12', 'Equinox\r', '2023-10-03 05:11:03'),
(297, '12', 'Groove\r', '2023-10-03 05:11:03'),
(298, '12', 'Impala\r', '2023-10-03 05:11:03'),
(299, '12', 'Jumbo\r', '2023-10-03 05:11:03'),
(300, '12', 'Malibu\r', '2023-10-03 05:11:03'),
(301, '12', 'Onix\r', '2023-10-03 05:11:03'),
(302, '12', 'Silverado\r', '2023-10-03 05:11:03'),
(303, '12', 'Silverado Midnight Edition\r', '2023-10-03 05:11:03'),
(304, '12', 'Sonic\r', '2023-10-03 05:11:03'),
(305, '12', 'Spark\r', '2023-10-03 05:11:03'),
(306, '12', 'Suburban\r', '2023-10-03 05:11:03'),
(307, '12', 'Tahoe\r', '2023-10-03 05:11:03'),
(308, '12', 'Tahoe Midnight Edition\r', '2023-10-03 05:11:03'),
(309, '12', 'Trailblazer\r', '2023-10-03 05:11:03'),
(310, '12', 'Traverse\r', '2023-10-03 05:11:03'),
(311, '12', 'Trax\r', '2023-10-03 05:11:03'),
(312, '13', '200\r', '2023-10-03 05:11:03'),
(313, '13', '300\r', '2023-10-03 05:11:03'),
(314, '13', '300 SRT8\r', '2023-10-03 05:11:03'),
(315, '13', '300C\r', '2023-10-03 05:11:03'),
(316, '13', 'Grand Voyager\r', '2023-10-03 05:11:03'),
(317, '13', 'Pacifica\r', '2023-10-03 05:11:03'),
(318, '14', 'C1\r', '2023-10-03 05:11:03'),
(319, '14', 'C2\r', '2023-10-03 05:11:03'),
(320, '14', 'C3\r', '2023-10-03 05:11:03'),
(321, '14', 'C4\r', '2023-10-03 05:11:03'),
(322, '14', 'C6\r', '2023-10-03 05:11:03'),
(323, '14', 'Xara\r', '2023-10-03 05:11:03'),
(324, '14', 'Regency\r', '2023-10-03 05:11:03'),
(325, '14', 'Berlingo\r', '2023-10-03 05:11:03'),
(326, '15', 'CMC\r', '2023-10-03 05:11:03'),
(327, '16', 'Leganza\r', '2023-10-03 05:11:03'),
(328, '16', 'Lanos\r', '2023-10-03 05:11:03'),
(329, '16', 'Mats\r', '2023-10-03 05:11:03'),
(330, '16', 'Nubira\r', '2023-10-03 05:11:03'),
(331, '17', 'Sirion\r', '2023-10-03 05:11:03'),
(332, '17', 'Taurus\r', '2023-10-03 05:11:03'),
(333, '17', 'Materia\r', '2023-10-03 05:11:03'),
(334, '18', 'Avenger\r', '2023-10-03 05:11:03'),
(335, '18', 'Caliber\r', '2023-10-03 05:11:03'),
(336, '18', 'Challenger\r', '2023-10-03 05:11:03'),
(337, '18', 'Charger\r', '2023-10-03 05:11:03'),
(338, '18', 'Dakota\r', '2023-10-03 05:11:03'),
(339, '18', 'Dart\r', '2023-10-03 05:11:03'),
(340, '18', 'Durango\r', '2023-10-03 05:11:03'),
(341, '18', 'Grand Caravan\r', '2023-10-03 05:11:03'),
(342, '18', 'Journey\r', '2023-10-03 05:11:03'),
(343, '18', 'Magnum\r', '2023-10-03 05:11:03'),
(344, '18', 'Neon\r', '2023-10-03 05:11:03'),
(345, '18', 'Nitro\r', '2023-10-03 05:11:03'),
(346, '18', 'Power Wagon\r', '2023-10-03 05:11:03'),
(347, '18', 'RAM\r', '2023-10-03 05:11:03'),
(348, '18', 'Ram 1500\r', '2023-10-03 05:11:03'),
(349, '18', 'Viper\r', '2023-10-03 05:11:03'),
(350, '19', 'A30\r', '2023-10-03 05:11:03'),
(351, '19', 'A60 Max\r', '2023-10-03 05:11:03'),
(352, '19', 'AX4\r', '2023-10-03 05:11:03'),
(353, '19', 'AX7\r', '2023-10-03 05:11:03'),
(354, '19', 'AX7 Mache\r', '2023-10-03 05:11:03'),
(355, '19', 'DFAC Dollicar\r', '2023-10-03 05:11:03'),
(356, '19', 'Forthing T5 EVO\r', '2023-10-03 05:11:03'),
(357, '19', 'H30\r', '2023-10-03 05:11:03'),
(358, '19', 'S30\r', '2023-10-03 05:11:03'),
(359, '20', 'LX\r', '2023-10-03 05:11:03'),
(360, '20', 'TXL\r', '2023-10-03 05:11:03'),
(361, '20', 'VX\r', '2023-10-03 05:11:03'),
(362, '21', 'T80\r', '2023-10-03 05:11:03'),
(363, '21', 'V80\r', '2023-10-03 05:11:03'),
(364, '21', 'Oley\r', '2023-10-03 05:11:03'),
(365, '21', 'Besturn B50\r', '2023-10-03 05:11:03'),
(366, '21', 'Besturn B70\r', '2023-10-03 05:11:03'),
(367, '21', 'Besturn X80 \r', '2023-10-03 05:11:03'),
(368, '21', 'T77\r', '2023-10-03 05:11:03'),
(369, '21', 'X40\r', '2023-10-03 05:11:03'),
(370, '21', 'T33\r', '2023-10-03 05:11:03'),
(371, '21', 'T99\r', '2023-10-03 05:11:03'),
(372, '22', '296 GTB\r', '2023-10-03 05:11:03'),
(373, '22', '296 GTS\r', '2023-10-03 05:11:03'),
(374, '22', '348 GTS\r', '2023-10-03 05:11:03'),
(375, '22', '360 Modena\r', '2023-10-03 05:11:03'),
(376, '22', '360 Spider\r', '2023-10-03 05:11:03'),
(377, '22', '456 GTA\r', '2023-10-03 05:11:03'),
(378, '22', '458\r', '2023-10-03 05:11:03'),
(379, '22', '488\r', '2023-10-03 05:11:03'),
(380, '22', '488 GTB\r', '2023-10-03 05:11:03'),
(381, '22', '488 Spider\r', '2023-10-03 05:11:03'),
(382, '22', '550 Maranello\r', '2023-10-03 05:11:03'),
(383, '22', '575 Maranello\r', '2023-10-03 05:11:03'),
(384, '22', '599\r', '2023-10-03 05:11:03'),
(385, '22', '599 GTB\r', '2023-10-03 05:11:03'),
(386, '22', '599 GTO\r', '2023-10-03 05:11:03'),
(387, '22', '612 Scaglietti\r', '2023-10-03 05:11:03'),
(388, '22', '812 GTS\r', '2023-10-03 05:11:03'),
(389, '22', '812 Superfast\r', '2023-10-03 05:11:03'),
(390, '22', 'California\r', '2023-10-03 05:11:03'),
(391, '22', 'California T\r', '2023-10-03 05:11:03'),
(392, '22', 'Enzo\r', '2023-10-03 05:11:03'),
(393, '22', 'F12 TDF\r', '2023-10-03 05:11:03'),
(394, '22', 'F12 berlinetta\r', '2023-10-03 05:11:03'),
(395, '22', 'F355\r', '2023-10-03 05:11:03'),
(396, '22', 'F430\r', '2023-10-03 05:11:03'),
(397, '22', 'F8 Spider\r', '2023-10-03 05:11:03'),
(398, '22', 'F8 Tributo\r', '2023-10-03 05:11:03'),
(399, '22', 'FF\r', '2023-10-03 05:11:03'),
(400, '22', 'GTC4Lusso\r', '2023-10-03 05:11:03'),
(401, '22', 'La Ferrari\r', '2023-10-03 05:11:03'),
(402, '22', 'Portofino\r', '2023-10-03 05:11:03'),
(403, '22', 'Portofino M\r', '2023-10-03 05:11:03'),
(404, '22', 'Purosangue\r', '2023-10-03 05:11:03'),
(405, '22', 'Roma\r', '2023-10-03 05:11:03'),
(406, '22', 'SF90 Spider\r', '2023-10-03 05:11:03'),
(407, '22', 'SF90 Stradale\r', '2023-10-03 05:11:03'),
(408, '22', 'Superamerica\r', '2023-10-03 05:11:03'),
(409, '22', 'Testarossa\r', '2023-10-03 05:11:03'),
(410, '23', '124 Spider\r', '2023-10-03 05:11:03'),
(411, '23', '500\r', '2023-10-03 05:11:03'),
(412, '23', '500 Abarth\r', '2023-10-03 05:11:03'),
(413, '23', '500 Abarth 695 Tributo Ferrari\r', '2023-10-03 05:11:03'),
(414, '23', '500L\r', '2023-10-03 05:11:03'),
(415, '23', '500X\r', '2023-10-03 05:11:03'),
(416, '23', '500e\r', '2023-10-03 05:11:03'),
(417, '23', 'Bravo 1.4L\r', '2023-10-03 05:11:03'),
(418, '23', 'Doblo\r', '2023-10-03 05:11:03'),
(419, '23', 'Ducato\r', '2023-10-03 05:11:03'),
(420, '23', 'Fiorino\r', '2023-10-03 05:11:03'),
(421, '23', 'Fullback\r', '2023-10-03 05:11:03'),
(422, '23', 'Linea\r', '2023-10-03 05:11:03'),
(423, '23', 'Panda\r', '2023-10-03 05:11:03'),
(424, '23', 'Punto\r', '2023-10-03 05:11:03'),
(425, '23', 'Punto 1.3L\r', '2023-10-03 05:11:03'),
(426, '23', 'Punto Evo\r', '2023-10-03 05:11:03'),
(427, '23', 'Qubo\r', '2023-10-03 05:11:03'),
(428, '23', 'Tipo Hatchback\r', '2023-10-03 05:11:03'),
(429, '23', 'Tipo Sedan\r', '2023-10-03 05:11:03'),
(430, '24', '1934 Classic Coupe\r', '2023-10-03 05:11:03'),
(431, '24', 'Bronco\r', '2023-10-03 05:11:03'),
(432, '24', 'Bronco 2-door\r', '2023-10-03 05:11:03'),
(433, '24', 'Bronco Raptor\r', '2023-10-03 05:11:03'),
(434, '24', 'Bronco Sport\r', '2023-10-03 05:11:03'),
(435, '24', 'Crown Victoria\r', '2023-10-03 05:11:03'),
(436, '24', 'EcoSport\r', '2023-10-03 05:11:03'),
(437, '24', 'Edge\r', '2023-10-03 05:11:03'),
(438, '24', 'Edge ST\r', '2023-10-03 05:11:03'),
(439, '24', 'Escape\r', '2023-10-03 05:11:03'),
(440, '24', 'Escort\r', '2023-10-03 05:11:03'),
(441, '24', 'Excursion\r', '2023-10-03 05:11:03'),
(442, '24', 'Expedition\r', '2023-10-03 05:11:03'),
(443, '24', 'Expedition EL\r', '2023-10-03 05:11:03'),
(444, '24', 'Explorer\r', '2023-10-03 05:11:03'),
(445, '24', 'F-150\r', '2023-10-03 05:11:03'),
(446, '24', 'F-150 Raptor\r', '2023-10-03 05:11:03'),
(447, '24', 'F-250\r', '2023-10-03 05:11:03'),
(448, '24', 'F-Series\r', '2023-10-03 05:11:03'),
(449, '24', 'F350 Super Duty\r', '2023-10-03 05:11:03'),
(450, '24', 'Fairlane\r', '2023-10-03 05:11:03'),
(451, '24', 'Fiesta\r', '2023-10-03 05:11:03'),
(452, '24', 'Figo\r', '2023-10-03 05:11:03'),
(453, '24', 'Figo Sedan\r', '2023-10-03 05:11:03'),
(454, '24', 'Five Hundred\r', '2023-10-03 05:11:03'),
(455, '24', 'Flex\r', '2023-10-03 05:11:03'),
(456, '24', 'Focus\r', '2023-10-03 05:11:03'),
(457, '24', 'Freestar\r', '2023-10-03 05:11:03'),
(458, '24', 'Fusion\r', '2023-10-03 05:11:03'),
(459, '24', 'GT\r', '2023-10-03 05:11:03'),
(460, '24', 'Galaxie\r', '2023-10-03 05:11:03'),
(461, '24', 'Kuga\r', '2023-10-03 05:11:03'),
(462, '24', 'LTD\r', '2023-10-03 05:11:03'),
(463, '24', 'Mondeo\r', '2023-10-03 05:11:03'),
(464, '24', 'Mustang\r', '2023-10-03 05:11:03'),
(465, '24', 'Mustang Boss\r', '2023-10-03 05:11:03'),
(466, '24', 'Mustang Convertible\r', '2023-10-03 05:11:03'),
(467, '24', 'Mustang Coupe\r', '2023-10-03 05:11:03'),
(468, '24', 'Mustang Mach-E\r', '2023-10-03 05:11:03'),
(469, '24', 'Mustang Roush\r', '2023-10-03 05:11:03'),
(470, '24', 'Mustang Shelby Cobra GT500\r', '2023-10-03 05:11:03'),
(471, '24', 'Mustang V6 Convertible\r', '2023-10-03 05:11:03'),
(472, '24', 'Mustang V6 Coupe\r', '2023-10-03 05:11:03'),
(473, '24', 'Mustang V8 Convertible\r', '2023-10-03 05:11:03'),
(474, '24', 'Mustang V8 Coupe\r', '2023-10-03 05:11:03'),
(475, '24', 'Ranger\r', '2023-10-03 05:11:03'),
(476, '24', 'Ranger Raptor\r', '2023-10-03 05:11:03'),
(477, '24', 'Super Duty\r', '2023-10-03 05:11:03'),
(478, '24', 'Taurus\r', '2023-10-03 05:11:03'),
(479, '24', 'Territory\r', '2023-10-03 05:11:03'),
(480, '24', 'Tourneo Custom\r', '2023-10-03 05:11:03'),
(481, '24', 'Transit\r', '2023-10-03 05:11:03'),
(482, '25', 'AUV\r', '2023-10-03 05:11:03'),
(483, '25', 'SUP\r', '2023-10-03 05:11:03'),
(484, '25', 'Sauvana\r', '2023-10-03 05:11:03'),
(485, '25', 'Tunland\r', '2023-10-03 05:11:03'),
(486, '25', 'View\r', '2023-10-03 05:11:03'),
(487, '26', 'EMKOO\r', '2023-10-03 05:11:03'),
(488, '26', 'EMPOW\r', '2023-10-03 05:11:03'),
(489, '26', 'EMZOOM\r', '2023-10-03 05:11:03'),
(490, '26', 'GA3\r', '2023-10-03 05:11:03'),
(491, '26', 'GA3S\r', '2023-10-03 05:11:03'),
(492, '26', 'GA4\r', '2023-10-03 05:11:03'),
(493, '26', 'GA5\r', '2023-10-03 05:11:03'),
(494, '26', 'GA6\r', '2023-10-03 05:11:03'),
(495, '26', 'GA8\r', '2023-10-03 05:11:03'),
(496, '26', 'GE3 EV\r', '2023-10-03 05:11:03'),
(497, '26', 'GN6\r', '2023-10-03 05:11:03'),
(498, '26', 'GN8\r', '2023-10-03 05:11:03'),
(499, '26', 'GS3\r', '2023-10-03 05:11:03'),
(500, '26', 'GS4\r', '2023-10-03 05:11:03'),
(501, '26', 'GS5\r', '2023-10-03 05:11:03'),
(502, '26', 'GS7\r', '2023-10-03 05:11:03'),
(503, '26', 'GS8\r', '2023-10-03 05:11:03'),
(504, '27', 'Azkarra\r', '2023-10-03 05:11:03'),
(505, '27', 'Binray\r', '2023-10-03 05:11:03'),
(506, '27', 'Coolray\r', '2023-10-03 05:11:03'),
(507, '27', 'EC7\r', '2023-10-03 05:11:03'),
(508, '27', 'Emegrand\r', '2023-10-03 05:11:03'),
(509, '27', 'Emgrand 7\r', '2023-10-03 05:11:03'),
(510, '27', 'Emgrand 7 HB\r', '2023-10-03 05:11:03'),
(511, '27', 'Emgrand 7 RV\r', '2023-10-03 05:11:03'),
(512, '27', 'Emgrand 8\r', '2023-10-03 05:11:03'),
(513, '27', 'Emgrand GC9\r', '2023-10-03 05:11:03'),
(514, '27', 'Emgrand GT\r', '2023-10-03 05:11:03'),
(515, '27', 'Emgrand X7\r', '2023-10-03 05:11:03'),
(516, '27', 'Emgrand X7 Sport\r', '2023-10-03 05:11:03'),
(517, '27', 'GC2\r', '2023-10-03 05:11:03'),
(518, '27', 'GC6\r', '2023-10-03 05:11:03'),
(519, '27', 'GS\r', '2023-10-03 05:11:03'),
(520, '27', 'GS Sport\r', '2023-10-03 05:11:03'),
(521, '27', 'GX2\r', '2023-10-03 05:11:03'),
(522, '27', 'Geometry C\r', '2023-10-03 05:11:03'),
(523, '27', 'Imperial\r', '2023-10-03 05:11:03'),
(524, '27', 'Monjaro\r', '2023-10-03 05:11:03'),
(525, '27', 'Okavango\r', '2023-10-03 05:11:03'),
(526, '27', 'Pandino\r', '2023-10-03 05:11:03'),
(527, '27', 'Tugella\r', '2023-10-03 05:11:03'),
(528, '27', 'X7 Sport\r', '2023-10-03 05:11:03'),
(529, '27', 'XPandino\r', '2023-10-03 05:11:03'),
(530, '28', 'G70\r', '2023-10-03 05:11:03'),
(531, '28', 'G80\r', '2023-10-03 05:11:03'),
(532, '28', 'G90\r', '2023-10-03 05:11:03'),
(533, '28', 'GV70\r', '2023-10-03 05:11:03'),
(534, '28', 'GV80\r', '2023-10-03 05:11:03'),
(535, '29', 'Acadia\r', '2023-10-03 05:11:03'),
(536, '29', 'Acadia Denali\r', '2023-10-03 05:11:03'),
(537, '29', 'Astro\r', '2023-10-03 05:11:03'),
(538, '29', 'Envoy\r', '2023-10-03 05:11:03'),
(539, '29', 'Hummer EV Pickup\r', '2023-10-03 05:11:03'),
(540, '29', 'Hummer EV SUV\r', '2023-10-03 05:11:03'),
(541, '29', 'Jimmy\r', '2023-10-03 05:11:03'),
(542, '29', 'NHR\r', '2023-10-03 05:11:03'),
(543, '29', 'Savana\r', '2023-10-03 05:11:03'),
(544, '29', 'Sierra\r', '2023-10-03 05:11:03'),
(545, '29', 'Sierra Denali\r', '2023-10-03 05:11:03'),
(546, '29', 'Sierra HD\r', '2023-10-03 05:11:03'),
(547, '29', 'Sierra HD Denali\r', '2023-10-03 05:11:03'),
(548, '29', 'Syclone\r', '2023-10-03 05:11:03'),
(549, '29', 'Terrain\r', '2023-10-03 05:11:03'),
(550, '29', 'Terrain Denali\r', '2023-10-03 05:11:03'),
(551, '29', 'Yukon\r', '2023-10-03 05:11:03'),
(552, '29', 'Yukon Denali\r', '2023-10-03 05:11:03'),
(553, '29', 'Yukon XL\r', '2023-10-03 05:11:03'),
(554, '29', 'Yukon XL Denali\r', '2023-10-03 05:11:03'),
(555, '30', 'Florid\r', '2023-10-03 05:11:03'),
(556, '30', 'King Kong\r', '2023-10-03 05:11:03'),
(557, '30', 'POER\r', '2023-10-03 05:11:03'),
(558, '30', 'Wingle\r', '2023-10-03 05:11:03'),
(559, '30', 'Wingle 5\r', '2023-10-03 05:11:03'),
(560, '30', 'Wingle 6\r', '2023-10-03 05:11:03'),
(561, '30', 'Wingle 7\r', '2023-10-03 05:11:03'),
(562, '31', 'Dargo\r', '2023-10-03 05:11:03'),
(563, '31', 'H2\r', '2023-10-03 05:11:03'),
(564, '31', 'H6\r', '2023-10-03 05:11:03'),
(565, '31', 'H6 GT\r', '2023-10-03 05:11:03'),
(566, '31', 'H8\r', '2023-10-03 05:11:03'),
(567, '31', 'H9\r', '2023-10-03 05:11:03'),
(568, '31', 'Jolion\r', '2023-10-03 05:11:03'),
(569, '32', 'Accord\r', '2023-10-03 05:11:03'),
(570, '32', 'Accord Coupe\r', '2023-10-03 05:11:03'),
(571, '32', 'Accord Crosstour\r', '2023-10-03 05:11:03'),
(572, '32', 'CR-V\r', '2023-10-03 05:11:03'),
(573, '32', 'CRX\r', '2023-10-03 05:11:03'),
(574, '32', 'City\r', '2023-10-03 05:11:03'),
(575, '32', 'Civic\r', '2023-10-03 05:11:03'),
(576, '32', 'Civic Type R\r', '2023-10-03 05:11:03'),
(577, '32', 'Crosstour\r', '2023-10-03 05:11:03'),
(578, '32', 'E\r', '2023-10-03 05:11:03'),
(579, '32', 'HR-V\r', '2023-10-03 05:11:03'),
(580, '32', 'Jazz\r', '2023-10-03 05:11:03'),
(581, '32', 'Odyssey\r', '2023-10-03 05:11:03'),
(582, '32', 'Odyssey J\r', '2023-10-03 05:11:03'),
(583, '32', 'Pilot\r', '2023-10-03 05:11:03'),
(584, '32', 'Ridgeline\r', '2023-10-03 05:11:03'),
(585, '32', 'S2000\r', '2023-10-03 05:11:03'),
(586, '32', 'ZR-V\r', '2023-10-03 05:11:03'),
(587, '33', 'E-HS3\r', '2023-10-03 05:11:03'),
(588, '33', 'E-HS9\r', '2023-10-03 05:11:03'),
(589, '33', 'E-QM5\r', '2023-10-03 05:11:03'),
(590, '33', 'H5\r', '2023-10-03 05:11:03'),
(591, '33', 'H7\r', '2023-10-03 05:11:03'),
(592, '33', 'H9\r', '2023-10-03 05:11:03'),
(593, '33', 'HS5\r', '2023-10-03 05:11:03'),
(594, '33', 'HS7\r', '2023-10-03 05:11:03'),
(595, '33', 'L5\r', '2023-10-03 05:11:03'),
(596, '33', 'Ousado\r', '2023-10-03 05:11:03'),
(597, '34', 'H1\r', '2023-10-03 05:11:03'),
(598, '34', 'H2\r', '2023-10-03 05:11:03'),
(599, '34', 'H3\r', '2023-10-03 05:11:03'),
(600, '35', 'Accent\r', '2023-10-03 05:11:03'),
(601, '35', 'Accent HB\r', '2023-10-03 05:11:03'),
(602, '35', 'Accent Hatchback\r', '2023-10-03 05:11:03'),
(603, '35', 'Avante\r', '2023-10-03 05:11:03'),
(604, '35', 'Azera\r', '2023-10-03 05:11:03'),
(605, '35', 'Centennial\r', '2023-10-03 05:11:03'),
(606, '35', 'County\r', '2023-10-03 05:11:03'),
(607, '35', 'Coupe\r', '2023-10-03 05:11:03'),
(608, '35', 'Creta\r', '2023-10-03 05:11:03'),
(609, '35', 'Elantra\r', '2023-10-03 05:11:03'),
(610, '35', 'Elantra Coupe\r', '2023-10-03 05:11:03'),
(611, '35', 'Elantra HD\r', '2023-10-03 05:11:03'),
(612, '35', 'Equus\r', '2023-10-03 05:11:03'),
(613, '35', 'Galloper\r', '2023-10-03 05:11:03'),
(614, '35', 'Genesis\r', '2023-10-03 05:11:03'),
(615, '35', 'Genesis Coupe\r', '2023-10-03 05:11:03'),
(616, '35', 'Getz\r', '2023-10-03 05:11:03'),
(617, '35', 'Grand Santa Fe\r', '2023-10-03 05:11:03'),
(618, '35', 'Grand Starix\r', '2023-10-03 05:11:03'),
(619, '35', 'Grand i10\r', '2023-10-03 05:11:03'),
(620, '35', 'Grandeur\r', '2023-10-03 05:11:03'),
(621, '35', 'H1\r', '2023-10-03 05:11:03'),
(622, '35', 'HD 65\r', '2023-10-03 05:11:03'),
(623, '35', 'HD260\r', '2023-10-03 05:11:03'),
(624, '35', 'IX20\r', '2023-10-03 05:11:03'),
(625, '35', 'IX35\r', '2023-10-03 05:11:03'),
(626, '35', 'Ioniq\r', '2023-10-03 05:11:03'),
(627, '35', 'Ioniq 5\r', '2023-10-03 05:11:03'),
(628, '35', 'Kona\r', '2023-10-03 05:11:03'),
(629, '35', 'Matrix\r', '2023-10-03 05:11:03'),
(630, '35', 'Palisade\r', '2023-10-03 05:11:03'),
(631, '35', 'Porter H100\r', '2023-10-03 05:11:03'),
(632, '35', 'Santa Cruz\r', '2023-10-03 05:11:03'),
(633, '35', 'Santa Fe\r', '2023-10-03 05:11:03'),
(634, '35', 'Solaris\r', '2023-10-03 05:11:03'),
(635, '35', 'Solaris Hatchback\r', '2023-10-03 05:11:03'),
(636, '35', 'Sonata\r', '2023-10-03 05:11:03'),
(637, '35', 'Staria\r', '2023-10-03 05:11:03'),
(638, '35', 'Trajet\r', '2023-10-03 05:11:03'),
(639, '35', 'Tucson\r', '2023-10-03 05:11:03'),
(640, '35', 'Tucson Turbo\r', '2023-10-03 05:11:03'),
(641, '35', 'Veloster\r', '2023-10-03 05:11:03'),
(642, '35', 'Veloster N\r', '2023-10-03 05:11:03'),
(643, '35', 'Veloster Turbo\r', '2023-10-03 05:11:03'),
(644, '35', 'Venue\r', '2023-10-03 05:11:03'),
(645, '35', 'Veracruz\r', '2023-10-03 05:11:03'),
(646, '35', 'Verna\r', '2023-10-03 05:11:03'),
(647, '35', 'i10\r', '2023-10-03 05:11:03'),
(648, '35', 'i20\r', '2023-10-03 05:11:03'),
(649, '35', 'i30\r', '2023-10-03 05:11:03'),
(650, '35', 'i30 CW\r', '2023-10-03 05:11:03'),
(651, '35', 'i30cw\r', '2023-10-03 05:11:03'),
(652, '35', 'i40\r', '2023-10-03 05:11:03'),
(653, '36', 'EX 35\r', '2023-10-03 05:11:03'),
(654, '36', 'EX37\r', '2023-10-03 05:11:03'),
(655, '36', 'FX 35\r', '2023-10-03 05:11:03'),
(656, '36', 'FX 50\r', '2023-10-03 05:11:03'),
(657, '36', 'FX37\r', '2023-10-03 05:11:03'),
(658, '36', 'FX45\r', '2023-10-03 05:11:03'),
(659, '36', 'G Convertible\r', '2023-10-03 05:11:03'),
(660, '36', 'G Coupe\r', '2023-10-03 05:11:03'),
(661, '36', 'G Sedan\r', '2023-10-03 05:11:03'),
(662, '36', 'G25 Sedan\r', '2023-10-03 05:11:03'),
(663, '36', 'G35\r', '2023-10-03 05:11:03'),
(664, '36', 'G35x\r', '2023-10-03 05:11:03'),
(665, '36', 'G37\r', '2023-10-03 05:11:03'),
(666, '36', 'G37 Convertible\r', '2023-10-03 05:11:03'),
(667, '36', 'G37 S\r', '2023-10-03 05:11:03'),
(668, '36', 'G37 Sedan\r', '2023-10-03 05:11:03'),
(669, '36', 'G37x\r', '2023-10-03 05:11:03'),
(670, '36', 'G37xS\r', '2023-10-03 05:11:03'),
(671, '36', 'I35\r', '2023-10-03 05:11:03'),
(672, '36', 'JX35\r', '2023-10-03 05:11:03'),
(673, '36', 'M-Series\r', '2023-10-03 05:11:03'),
(674, '36', 'M37\r', '2023-10-03 05:11:03'),
(675, '36', 'M37 S\r', '2023-10-03 05:11:03'),
(676, '36', 'M37x\r', '2023-10-03 05:11:03'),
(677, '36', 'M56\r', '2023-10-03 05:11:03'),
(678, '36', 'Q30\r', '2023-10-03 05:11:03'),
(679, '36', 'Q40\r', '2023-10-03 05:11:03'),
(680, '36', 'Q45\r', '2023-10-03 05:11:03'),
(681, '36', 'Q50\r', '2023-10-03 05:11:03'),
(682, '36', 'Q60 Convertible\r', '2023-10-03 05:11:03'),
(683, '36', 'Q60 Coupe\r', '2023-10-03 05:11:03'),
(684, '36', 'Q70\r', '2023-10-03 05:11:03'),
(685, '36', 'QX30\r', '2023-10-03 05:11:03'),
(686, '36', 'QX4\r', '2023-10-03 05:11:03'),
(687, '36', 'QX50\r', '2023-10-03 05:11:03'),
(688, '36', 'QX55\r', '2023-10-03 05:11:03'),
(689, '36', 'QX56\r', '2023-10-03 05:11:03'),
(690, '36', 'QX60\r', '2023-10-03 05:11:03'),
(691, '36', 'QX70\r', '2023-10-03 05:11:03'),
(692, '36', 'QX80\r', '2023-10-03 05:11:03'),
(693, '36', 'i30\r', '2023-10-03 05:11:03'),
(694, '37', '117\r', '2023-10-03 05:11:03'),
(695, '37', 'D-MAX\r', '2023-10-03 05:11:03'),
(696, '37', 'MU-X\r', '2023-10-03 05:11:03'),
(697, '37', 'MUX\r', '2023-10-03 05:11:03'),
(698, '37', 'NPR\r', '2023-10-03 05:11:03'),
(699, '37', 'Reward\r', '2023-10-03 05:11:03'),
(700, '37', 'Trooper\r', '2023-10-03 05:11:03'),
(701, '38', 'J4\r', '2023-10-03 05:11:03'),
(702, '38', 'J5\r', '2023-10-03 05:11:03'),
(703, '38', 'J6\r', '2023-10-03 05:11:03'),
(704, '38', 'J7 Sportback\r', '2023-10-03 05:11:03'),
(705, '38', 'JS3\r', '2023-10-03 05:11:03'),
(706, '38', 'JS4\r', '2023-10-03 05:11:03'),
(707, '38', 'M1\r', '2023-10-03 05:11:03'),
(708, '38', 'M4\r', '2023-10-03 05:11:03'),
(709, '38', 'M5\r', '2023-10-03 05:11:03'),
(710, '38', 'N-Series\r', '2023-10-03 05:11:03'),
(711, '38', 'Refrigerated Truck\r', '2023-10-03 05:11:03'),
(712, '38', 'S2\r', '2023-10-03 05:11:03'),
(713, '38', 'S3\r', '2023-10-03 05:11:03'),
(714, '38', 'S4\r', '2023-10-03 05:11:03'),
(715, '38', 'S7\r', '2023-10-03 05:11:03'),
(716, '38', 'S7 Pro\r', '2023-10-03 05:11:03'),
(717, '38', 'Sunray\r', '2023-10-03 05:11:03'),
(718, '38', 'T6\r', '2023-10-03 05:11:03'),
(719, '38', 'T8\r', '2023-10-03 05:11:03'),
(720, '38', 'Tipper\r', '2023-10-03 05:11:03'),
(721, '39', 'E-Pace\r', '2023-10-03 05:11:03'),
(722, '39', 'F-Pace\r', '2023-10-03 05:11:03'),
(723, '39', 'F-Type\r', '2023-10-03 05:11:03'),
(724, '39', 'F-Type Convertible\r', '2023-10-03 05:11:03'),
(725, '39', 'F-Type Coupe\r', '2023-10-03 05:11:03'),
(726, '39', 'I-Pace\r', '2023-10-03 05:11:03'),
(727, '39', 'S-Type\r', '2023-10-03 05:11:03'),
(728, '39', 'Sovereign\r', '2023-10-03 05:11:03'),
(729, '39', 'X Type\r', '2023-10-03 05:11:03'),
(730, '39', 'XE\r', '2023-10-03 05:11:03'),
(731, '39', 'XF\r', '2023-10-03 05:11:03'),
(732, '39', 'XFR\r', '2023-10-03 05:11:03'),
(733, '39', 'XJ\r', '2023-10-03 05:11:03'),
(734, '39', 'XJ L\r', '2023-10-03 05:11:03'),
(735, '39', 'XK\r', '2023-10-03 05:11:03'),
(736, '39', 'XK8\r', '2023-10-03 05:11:03'),
(737, '39', 'XKR\r', '2023-10-03 05:11:03'),
(738, '39', 'XKR-S\r', '2023-10-03 05:11:03'),
(739, '40', 'Cherokee\r', '2023-10-03 05:11:03'),
(740, '40', 'Commander\r', '2023-10-03 05:11:03'),
(741, '40', 'Compass\r', '2023-10-03 05:11:03'),
(742, '40', 'Gladiator\r', '2023-10-03 05:11:03'),
(743, '40', 'Grand Cherokee\r', '2023-10-03 05:11:03'),
(744, '40', 'Grand Cherokee L\r', '2023-10-03 05:11:03'),
(745, '40', 'Grand Wagoneer\r', '2023-10-03 05:11:03'),
(746, '40', 'Jeep Grand Cherokee Laredo\r', '2023-10-03 05:11:03'),
(747, '40', 'Liberty\r', '2023-10-03 05:11:03'),
(748, '40', 'Patriot\r', '2023-10-03 05:11:03'),
(749, '40', 'Renegade\r', '2023-10-03 05:11:03'),
(750, '40', 'Wrangler\r', '2023-10-03 05:11:03'),
(751, '40', 'Wrangler Unlimited\r', '2023-10-03 05:11:03'),
(752, '41', 'Dashing\r', '2023-10-03 05:11:03'),
(753, '41', 'X70\r', '2023-10-03 05:11:03'),
(754, '41', 'X70 Plus\r', '2023-10-03 05:11:03'),
(755, '41', 'X70s\r', '2023-10-03 05:11:03'),
(756, '41', 'X90\r', '2023-10-03 05:11:03'),
(757, '41', 'X90 Plus\r', '2023-10-03 05:11:03'),
(758, '41', 'X95\r', '2023-10-03 05:11:03'),
(759, '42', 'JMC\r', '2023-10-03 05:11:03'),
(760, '43', 'Amanti\r', '2023-10-03 05:11:03'),
(761, '43', 'Cadenza\r', '2023-10-03 05:11:03'),
(762, '43', 'Carens\r', '2023-10-03 05:11:03'),
(763, '43', 'Carnival\r', '2023-10-03 05:11:03'),
(764, '43', 'Cee\'d\r', '2023-10-03 05:11:03'),
(765, '43', 'Cee\'d Station Wagon\r', '2023-10-03 05:11:03'),
(766, '43', 'Cerato\r', '2023-10-03 05:11:03'),
(767, '43', 'Cerato Hatchback\r', '2023-10-03 05:11:03'),
(768, '43', 'Cerato Koup\r', '2023-10-03 05:11:03'),
(769, '43', 'Forte\r', '2023-10-03 05:11:03'),
(770, '43', 'Grand Carnival\r', '2023-10-03 05:11:03'),
(771, '43', 'Grand Cerato\r', '2023-10-03 05:11:03'),
(772, '43', 'K2700\r', '2023-10-03 05:11:03'),
(773, '43', 'K4000G\r', '2023-10-03 05:11:03'),
(774, '43', 'K5\r', '2023-10-03 05:11:03'),
(775, '43', 'K8\r', '2023-10-03 05:11:03'),
(776, '43', 'K900\r', '2023-10-03 05:11:03'),
(777, '43', 'Koup\r', '2023-10-03 05:11:03'),
(778, '43', 'Mohave\r', '2023-10-03 05:11:03'),
(779, '43', 'Morning\r', '2023-10-03 05:11:03'),
(780, '43', 'Niro\r', '2023-10-03 05:11:03'),
(781, '43', 'Opirus\r', '2023-10-03 05:11:03'),
(782, '43', 'Optima\r', '2023-10-03 05:11:03'),
(783, '43', 'Pegas\r', '2023-10-03 05:11:03'),
(784, '43', 'Picanto\r', '2023-10-03 05:11:03'),
(785, '43', 'Quoris\r', '2023-10-03 05:11:03'),
(786, '43', 'Ray\r', '2023-10-03 05:11:03'),
(787, '43', 'Refrigerated Truck\r', '2023-10-03 05:11:03'),
(788, '43', 'Rio\r', '2023-10-03 05:11:03'),
(789, '43', 'Rio Hatchback\r', '2023-10-03 05:11:03'),
(790, '43', 'Rio Sedan\r', '2023-10-03 05:11:03'),
(791, '43', 'Sedona\r', '2023-10-03 05:11:03'),
(792, '43', 'Seltos\r', '2023-10-03 05:11:03'),
(793, '43', 'Sephia\r', '2023-10-03 05:11:03'),
(794, '43', 'Shuma\r', '2023-10-03 05:11:03'),
(795, '43', 'Sonet\r', '2023-10-03 05:11:03'),
(796, '43', 'Sorento\r', '2023-10-03 05:11:03'),
(797, '43', 'Soul\r', '2023-10-03 05:11:03'),
(798, '43', 'Sportage\r', '2023-10-03 05:11:03'),
(799, '43', 'Stinger\r', '2023-10-03 05:11:03'),
(800, '43', 'Stonic\r', '2023-10-03 05:11:03'),
(801, '43', 'Telluride\r', '2023-10-03 05:11:03'),
(802, '43', 'XCeed\r', '2023-10-03 05:11:03'),
(803, '44', 'Aventador\r', '2023-10-03 05:11:03'),
(804, '44', 'Aventador S\r', '2023-10-03 05:11:03'),
(805, '44', 'Aventador S Roadster\r', '2023-10-03 05:11:03'),
(806, '44', 'Aventador SVJ\r', '2023-10-03 05:11:03'),
(807, '44', 'Aventador SVJ 63 Roadster\r', '2023-10-03 05:11:03'),
(808, '44', 'Aventador Ultimae\r', '2023-10-03 05:11:03'),
(809, '44', 'Countach\r', '2023-10-03 05:11:03'),
(810, '44', 'DIABLO Roadster\r', '2023-10-03 05:11:03'),
(811, '44', 'Gallardo\r', '2023-10-03 05:11:03'),
(812, '44', 'Huracan\r', '2023-10-03 05:11:03'),
(813, '44', 'LM002\r', '2023-10-03 05:11:03'),
(814, '44', 'Lanzador\r', '2023-10-03 05:11:03'),
(815, '44', 'Murcielago\r', '2023-10-03 05:11:03'),
(816, '44', 'Sian FKP 37\r', '2023-10-03 05:11:03'),
(817, '44', 'Urus\r', '2023-10-03 05:11:03'),
(818, '45', 'Defender\r', '2023-10-03 05:11:03'),
(819, '45', 'Discovery\r', '2023-10-03 05:11:03'),
(820, '45', 'Discovery Sport\r', '2023-10-03 05:11:03'),
(821, '45', 'Freelander\r', '2023-10-03 05:11:03'),
(822, '45', 'HSE V8\r', '2023-10-03 05:11:03'),
(823, '45', 'LR2\r', '2023-10-03 05:11:03'),
(824, '45', 'LR3\r', '2023-10-03 05:11:03'),
(825, '45', 'LR4\r', '2023-10-03 05:11:03'),
(826, '45', 'Range Rover\r', '2023-10-03 05:11:03'),
(827, '45', 'Range Rover Evoque\r', '2023-10-03 05:11:03'),
(828, '45', 'Range Rover SV Coupe\r', '2023-10-03 05:11:03'),
(829, '45', 'Range Rover Sport\r', '2023-10-03 05:11:03'),
(830, '45', 'Range Rover Velar\r', '2023-10-03 05:11:03'),
(831, '46', 'CT\r', '2023-10-03 05:11:03'),
(832, '46', 'CT 200H\r', '2023-10-03 05:11:03'),
(833, '46', 'CT-Series\r', '2023-10-03 05:11:03'),
(834, '46', 'ES\r', '2023-10-03 05:11:03'),
(835, '46', 'ES 350\r', '2023-10-03 05:11:03'),
(836, '46', 'ES-Series\r', '2023-10-03 05:11:03'),
(837, '46', 'GS\r', '2023-10-03 05:11:03'),
(838, '46', 'GS 300\r', '2023-10-03 05:11:03'),
(839, '46', 'GS 350\r', '2023-10-03 05:11:03'),
(840, '46', 'GS 430\r', '2023-10-03 05:11:03'),
(841, '46', 'GS 460\r', '2023-10-03 05:11:03'),
(842, '46', 'GS-Series\r', '2023-10-03 05:11:03'),
(843, '46', 'GX\r', '2023-10-03 05:11:03'),
(844, '46', 'GX 460\r', '2023-10-03 05:11:03'),
(845, '46', 'GX-Series\r', '2023-10-03 05:11:03'),
(846, '46', 'I35/I30\r', '2023-10-03 05:11:03'),
(847, '46', 'IS\r', '2023-10-03 05:11:03'),
(848, '46', 'IS 300\r', '2023-10-03 05:11:03'),
(849, '46', 'IS 300C\r', '2023-10-03 05:11:03'),
(850, '46', 'IS 350\r', '2023-10-03 05:11:03'),
(851, '46', 'IS C\r', '2023-10-03 05:11:03'),
(852, '46', 'IS F\r', '2023-10-03 05:11:03'),
(853, '46', 'IS-Series\r', '2023-10-03 05:11:03'),
(854, '46', 'IS250\r', '2023-10-03 05:11:03'),
(855, '46', 'IS300\r', '2023-10-03 05:11:03'),
(856, '46', 'LC 500\r', '2023-10-03 05:11:03'),
(857, '46', 'LC 500 Convertible\r', '2023-10-03 05:11:03'),
(858, '46', 'LC 500h\r', '2023-10-03 05:11:03'),
(859, '46', 'LS\r', '2023-10-03 05:11:03'),
(860, '46', 'LS 400\r', '2023-10-03 05:11:03'),
(861, '46', 'LS 430\r', '2023-10-03 05:11:03'),
(862, '46', 'LS 460\r', '2023-10-03 05:11:03'),
(863, '46', 'LS 460L\r', '2023-10-03 05:11:03'),
(864, '46', 'LS 600hL\r', '2023-10-03 05:11:03'),
(865, '46', 'LS-Series\r', '2023-10-03 05:11:03'),
(866, '46', 'LS460\r', '2023-10-03 05:11:03'),
(867, '46', 'LX\r', '2023-10-03 05:11:03'),
(868, '46', 'LX 570\r', '2023-10-03 05:11:03'),
(869, '46', 'LX450d\r', '2023-10-03 05:11:03'),
(870, '46', 'NX\r', '2023-10-03 05:11:03'),
(871, '46', 'Other\r', '2023-10-03 05:11:03'),
(872, '46', 'RC\r', '2023-10-03 05:11:03'),
(873, '46', 'RC F\r', '2023-10-03 05:11:03'),
(874, '46', 'RX\r', '2023-10-03 05:11:03'),
(875, '46', 'RX 350\r', '2023-10-03 05:11:03'),
(876, '46', 'RX Series\r', '2023-10-03 05:11:03'),
(877, '46', 'SC 430\r', '2023-10-03 05:11:03'),
(878, '46', 'UX 200\r', '2023-10-03 05:11:03'),
(879, '46', 'UX 250h\r', '2023-10-03 05:11:03'),
(880, '47', 'Lifan\r', '2023-10-03 05:11:03'),
(881, '48', 'Aviator\r', '2023-10-03 05:11:03'),
(882, '48', 'Continental\r', '2023-10-03 05:11:03'),
(883, '48', 'Corsair\r', '2023-10-03 05:11:03'),
(884, '48', 'LS\r', '2023-10-03 05:11:03'),
(885, '48', 'MKC\r', '2023-10-03 05:11:03'),
(886, '48', 'MKS\r', '2023-10-03 05:11:03'),
(887, '48', 'MKT\r', '2023-10-03 05:11:03'),
(888, '48', 'MKX\r', '2023-10-03 05:11:03'),
(889, '48', 'MKZ\r', '2023-10-03 05:11:03'),
(890, '48', 'Nautilus\r', '2023-10-03 05:11:03'),
(891, '48', 'Navigator\r', '2023-10-03 05:11:03'),
(892, '48', 'Town Car\r', '2023-10-03 05:11:03'),
(893, '49', 'Scorpio Pickup\r', '2023-10-03 05:11:03'),
(894, '49', 'XUV500\r', '2023-10-03 05:11:03'),
(895, '50', 'Coupe\r', '2023-10-03 05:11:03'),
(896, '50', 'Ghibli\r', '2023-10-03 05:11:03'),
(897, '50', 'GranCabrio\r', '2023-10-03 05:11:03'),
(898, '50', 'GranTurismo\r', '2023-10-03 05:11:03'),
(899, '50', 'Grecale\r', '2023-10-03 05:11:03'),
(900, '50', 'Levante\r', '2023-10-03 05:11:03'),
(901, '50', 'MC20\r', '2023-10-03 05:11:03'),
(902, '50', 'MC20 Cielo\r', '2023-10-03 05:11:03'),
(903, '50', 'Quattroporte\r', '2023-10-03 05:11:03'),
(904, '51', 'D60\r', '2023-10-03 05:11:03'),
(905, '51', 'D90\r', '2023-10-03 05:11:03'),
(906, '51', 'D90 Pro\r', '2023-10-03 05:11:03'),
(907, '51', 'G10 7-Seater\r', '2023-10-03 05:11:03'),
(908, '51', 'G10 9-Seater\r', '2023-10-03 05:11:03'),
(909, '51', 'G10 Cargo\r', '2023-10-03 05:11:03'),
(910, '51', 'G50\r', '2023-10-03 05:11:03'),
(911, '51', 'T60\r', '2023-10-03 05:11:03'),
(912, '51', 'T70\r', '2023-10-03 05:11:03'),
(913, '51', 'T90\r', '2023-10-03 05:11:03'),
(914, '51', 'V80 15-Seater\r', '2023-10-03 05:11:03'),
(915, '51', 'V80 18-Seater\r', '2023-10-03 05:11:03'),
(916, '51', 'V80 Cargo Van\r', '2023-10-03 05:11:03'),
(917, '51', 'V90\r', '2023-10-03 05:11:03'),
(918, '52', '2\r', '2023-10-03 05:11:03'),
(919, '52', '2 Hatchback\r', '2023-10-03 05:11:03'),
(920, '52', '2 Sedan\r', '2023-10-03 05:11:03'),
(921, '52', '3\r', '2023-10-03 05:11:03'),
(922, '52', '3 Hatchback\r', '2023-10-03 05:11:03'),
(923, '52', '3 Sedan\r', '2023-10-03 05:11:03'),
(924, '52', '323\r', '2023-10-03 05:11:03'),
(925, '52', '5 MPV\r', '2023-10-03 05:11:03'),
(926, '52', '6\r', '2023-10-03 05:11:03'),
(927, '52', '6 Ultra\r', '2023-10-03 05:11:03'),
(928, '52', '626\r', '2023-10-03 05:11:03'),
(929, '52', 'BT-50 Pickup\r', '2023-10-03 05:11:03'),
(930, '52', 'CX 3\r', '2023-10-03 05:11:03'),
(931, '52', 'CX-30\r', '2023-10-03 05:11:03'),
(932, '52', 'CX-5\r', '2023-10-03 05:11:03'),
(933, '52', 'CX-60\r', '2023-10-03 05:11:03'),
(934, '52', 'CX-7\r', '2023-10-03 05:11:03'),
(935, '52', 'CX-9\r', '2023-10-03 05:11:03'),
(936, '52', 'MX-30\r', '2023-10-03 05:11:03'),
(937, '52', 'MX-5\r', '2023-10-03 05:11:03'),
(938, '52', 'MX-5 Convertible Hard Top\r', '2023-10-03 05:11:03'),
(939, '52', 'RX-8\r', '2023-10-03 05:11:03'),
(940, '53', '12C\r', '2023-10-03 05:11:03'),
(941, '53', '540C\r', '2023-10-03 05:11:03'),
(942, '53', '570 GT\r', '2023-10-03 05:11:03'),
(943, '53', '570S\r', '2023-10-03 05:11:03'),
(944, '53', '570S Spider\r', '2023-10-03 05:11:03'),
(945, '53', '600LT\r', '2023-10-03 05:11:03'),
(946, '53', '650S\r', '2023-10-03 05:11:03'),
(947, '53', '675 LT\r', '2023-10-03 05:11:03'),
(948, '53', '720S\r', '2023-10-03 05:11:03'),
(949, '53', '765LT\r', '2023-10-03 05:11:03'),
(950, '53', 'Artura\r', '2023-10-03 05:11:03'),
(951, '53', 'GT\r', '2023-10-03 05:11:03'),
(952, '53', 'MP4-12C\r', '2023-10-03 05:11:03'),
(953, '53', 'P1\r', '2023-10-03 05:11:03'),
(954, '53', 'Senna\r', '2023-10-03 05:11:03'),
(955, '54', '250 SE\r', '2023-10-03 05:11:03'),
(956, '54', '280\r', '2023-10-03 05:11:03'),
(957, '54', '280 SL\r', '2023-10-03 05:11:03'),
(958, '54', '300\r', '2023-10-03 05:11:03'),
(959, '54', '300/350/380\r', '2023-10-03 05:11:03'),
(960, '54', '300SE\r', '2023-10-03 05:11:03'),
(961, '54', '450 SEL\r', '2023-10-03 05:11:03'),
(962, '54', '560 SEL\r', '2023-10-03 05:11:03'),
(963, '54', '560 SL\r', '2023-10-03 05:11:03'),
(964, '54', 'A 35 AMG\r', '2023-10-03 05:11:03'),
(965, '54', 'A 45 AMG\r', '2023-10-03 05:11:03'),
(966, '54', 'A-Class\r', '2023-10-03 05:11:03'),
(967, '54', 'A-class Sedan\r', '2023-10-03 05:11:03'),
(968, '54', 'A250\r', '2023-10-03 05:11:03'),
(969, '54', 'A35 AMG\r', '2023-10-03 05:11:03'),
(970, '54', 'AMG GLE Coupe\r', '2023-10-03 05:11:03'),
(971, '54', 'AMG GT\r', '2023-10-03 05:11:03'),
(972, '54', 'AMG GT 4-Door Coupe\r', '2023-10-03 05:11:03'),
(973, '54', 'AMG GT Roadster\r', '2023-10-03 05:11:03'),
(974, '54', 'AMG GT63\r', '2023-10-03 05:11:03'),
(975, '54', 'Atego 1023\r', '2023-10-03 05:11:03'),
(976, '54', 'B Class\r', '2023-10-03 05:11:03'),
(977, '54', 'BRABUS\r', '2023-10-03 05:11:03'),
(978, '54', 'BRABUS G700\r', '2023-10-03 05:11:03'),
(979, '54', 'Brabus 500 4x4 Squared\r', '2023-10-03 05:11:03'),
(980, '54', 'Brabus 850\r', '2023-10-03 05:11:03'),
(981, '54', 'Brabus 900\r', '2023-10-03 05:11:03'),
(982, '54', 'Brabus B63S-700 6x6\r', '2023-10-03 05:11:03'),
(983, '54', 'Brabus G800\r', '2023-10-03 05:11:03'),
(984, '54', 'C 63 AMG\r', '2023-10-03 05:11:03'),
(985, '54', 'C 63 AMG Coupe\r', '2023-10-03 05:11:03'),
(986, '54', 'C Class Cabriolet\r', '2023-10-03 05:11:03'),
(987, '54', 'C-Class\r', '2023-10-03 05:11:03'),
(988, '54', 'C-Class Coupe\r', '2023-10-03 05:11:03'),
(989, '54', 'CL-Class\r', '2023-10-03 05:11:03'),
(990, '54', 'CLA 45 AMG\r', '2023-10-03 05:11:03'),
(991, '54', 'CLA-Class\r', '2023-10-03 05:11:03'),
(992, '54', 'CLC 180\r', '2023-10-03 05:11:03'),
(993, '54', 'CLC 200\r', '2023-10-03 05:11:03'),
(994, '54', 'CLK-Class\r', '2023-10-03 05:11:03'),
(995, '54', 'CLS 55 AMG\r', '2023-10-03 05:11:03'),
(996, '54', 'CLS 63 AMG\r', '2023-10-03 05:11:03'),
(997, '54', 'CLS-Class\r', '2023-10-03 05:11:03'),
(998, '54', 'E 280\r', '2023-10-03 05:11:03'),
(999, '54', 'E 63 AMG\r', '2023-10-03 05:11:03'),
(1000, '54', 'E-Class\r', '2023-10-03 05:11:03'),
(1001, '54', 'E-Class Cabriolet\r', '2023-10-03 05:11:03'),
(1002, '54', 'E-Class Coupe\r', '2023-10-03 05:11:03'),
(1003, '54', 'E-Class Saloon\r', '2023-10-03 05:11:03'),
(1004, '54', 'E320\r', '2023-10-03 05:11:03'),
(1005, '54', 'E55 AMG Wagon\r', '2023-10-03 05:11:03'),
(1006, '54', 'EQA\r', '2023-10-03 05:11:03'),
(1007, '54', 'EQB\r', '2023-10-03 05:11:03'),
(1008, '54', 'EQC\r', '2023-10-03 05:11:03'),
(1009, '54', 'EQE\r', '2023-10-03 05:11:03'),
(1010, '54', 'EQS SUV\r', '2023-10-03 05:11:03'),
(1011, '54', 'EQS Sedan\r', '2023-10-03 05:11:03'),
(1012, '54', 'EQV\r', '2023-10-03 05:11:03'),
(1013, '54', 'G 63 AMG\r', '2023-10-03 05:11:03'),
(1014, '54', 'G 63 AMG 6X6\r', '2023-10-03 05:11:03'),
(1015, '54', 'G 65 AMG\r', '2023-10-03 05:11:03'),
(1016, '54', 'G-Class\r', '2023-10-03 05:11:03'),
(1017, '54', 'G350\r', '2023-10-03 05:11:03'),
(1018, '54', 'GL 63 AMG\r', '2023-10-03 05:11:03'),
(1019, '54', 'GL-Class\r', '2023-10-03 05:11:03'),
(1020, '54', 'GLA\r', '2023-10-03 05:11:03'),
(1021, '54', 'GLB\r', '2023-10-03 05:11:03'),
(1022, '54', 'GLC 250\r', '2023-10-03 05:11:03'),
(1023, '54', 'GLC Coupe\r', '2023-10-03 05:11:03'),
(1024, '54', 'GLC-Class\r', '2023-10-03 05:11:03'),
(1025, '54', 'GLE 63 AMG\r', '2023-10-03 05:11:03'),
(1026, '54', 'GLE Coupe\r', '2023-10-03 05:11:03'),
(1027, '54', 'GLE-Class\r', '2023-10-03 05:11:03'),
(1028, '54', 'GLK-Class\r', '2023-10-03 05:11:03'),
(1029, '54', 'GLS\r', '2023-10-03 05:11:03'),
(1030, '54', 'GLS 63 AMG\r', '2023-10-03 05:11:03'),
(1031, '54', 'Gazelle\r', '2023-10-03 05:11:03'),
(1032, '54', 'M-Class\r', '2023-10-03 05:11:03'),
(1033, '54', 'MCV 400\r', '2023-10-03 05:11:03'),
(1034, '54', 'ML 63 AMG\r', '2023-10-03 05:11:03'),
(1035, '54', 'ML350\r', '2023-10-03 05:11:03'),
(1036, '54', 'ML400\r', '2023-10-03 05:11:03'),
(1037, '54', 'ML500\r', '2023-10-03 05:11:03'),
(1038, '54', 'ML55 AMG\r', '2023-10-03 05:11:03'),
(1039, '54', 'MPVs\r', '2023-10-03 05:11:03'),
(1040, '54', 'Maybach\r', '2023-10-03 05:11:03'),
(1041, '54', 'Maybach GLS\r', '2023-10-03 05:11:03'),
(1042, '54', 'R-Class\r', '2023-10-03 05:11:03'),
(1043, '54', 'R500\r', '2023-10-03 05:11:03'),
(1044, '54', 'R63 AMG\r', '2023-10-03 05:11:03'),
(1045, '54', 'S 280\r', '2023-10-03 05:11:03'),
(1046, '54', 'S 63 AMG\r', '2023-10-03 05:11:03'),
(1047, '54', 'S 63 AMG Coupe\r', '2023-10-03 05:11:03'),
(1048, '54', 'S 65 AMG\r', '2023-10-03 05:11:03'),
(1049, '54', 'S 65 AMG Coupe\r', '2023-10-03 05:11:03'),
(1050, '54', 'S Class Cabriolet\r', '2023-10-03 05:11:03'),
(1051, '54', 'S-Class\r', '2023-10-03 05:11:03'),
(1052, '54', 'S-Class Coupe\r', '2023-10-03 05:11:03'),
(1053, '54', 'S-Class Limousine\r', '2023-10-03 05:11:03'),
(1054, '54', 'SE 280\r', '2023-10-03 05:11:03'),
(1055, '54', 'SEL 500\r', '2023-10-03 05:11:03'),
(1056, '54', 'SL 63 AMG\r', '2023-10-03 05:11:03'),
(1057, '54', 'SL 65 AMG\r', '2023-10-03 05:11:03');
INSERT INTO `car_model` (`id`, `mkid`, `model`, `created_at`) VALUES
(1058, '54', 'SL-Class\r', '2023-10-03 05:11:03'),
(1059, '54', 'SLC\r', '2023-10-03 05:11:03'),
(1060, '54', 'SLK 55 AMG\r', '2023-10-03 05:11:03'),
(1061, '54', 'SLK-Class\r', '2023-10-03 05:11:03'),
(1062, '54', 'SLK200\r', '2023-10-03 05:11:03'),
(1063, '54', 'SLR\r', '2023-10-03 05:11:03'),
(1064, '54', 'SLS AMG\r', '2023-10-03 05:11:03'),
(1065, '54', 'Sprinter VIP Business Van\r', '2023-10-03 05:11:03'),
(1066, '54', 'V Class\r', '2023-10-03 05:11:03'),
(1067, '54', 'Vito\r', '2023-10-03 05:11:03'),
(1068, '54', 'X-Class\r', '2023-10-03 05:11:03'),
(1069, '54', 'X250d\r', '2023-10-03 05:11:03'),
(1070, '55', 'Mountaineer\r', '2023-10-03 05:11:03'),
(1071, '55', 'Marauder\r', '2023-10-03 05:11:03'),
(1072, '56', '-350\r', '2023-10-03 05:11:03'),
(1073, '56', '-5\r', '2023-10-03 05:11:03'),
(1074, '56', '-6\r', '2023-10-03 05:11:03'),
(1075, '56', '- GS\r', '2023-10-03 05:11:03'),
(1076, '56', '3\r', '2023-10-03 05:11:03'),
(1077, '56', '350\r', '2023-10-03 05:11:03'),
(1078, '56', '360\r', '2023-10-03 05:11:03'),
(1079, '56', '5\r', '2023-10-03 05:11:03'),
(1080, '56', '550\r', '2023-10-03 05:11:03'),
(1081, '56', '6\r', '2023-10-03 05:11:03'),
(1082, '56', '750\r', '2023-10-03 05:11:03'),
(1083, '56', 'Crossover\r', '2023-10-03 05:11:03'),
(1084, '56', 'EZS\r', '2023-10-03 05:11:03'),
(1085, '56', 'Extender\r', '2023-10-03 05:11:03'),
(1086, '56', 'GS\r', '2023-10-03 05:11:03'),
(1087, '56', 'GT\r', '2023-10-03 05:11:03'),
(1088, '56', 'HS\r', '2023-10-03 05:11:03'),
(1089, '56', 'MGB 1974 Classic\r', '2023-10-03 05:11:03'),
(1090, '56', 'MGB 1978 Classic\r', '2023-10-03 05:11:03'),
(1091, '56', 'One\r', '2023-10-03 05:11:03'),
(1092, '56', 'RX5\r', '2023-10-03 05:11:03'),
(1093, '56', 'RX8\r', '2023-10-03 05:11:03'),
(1094, '56', 'T60\r', '2023-10-03 05:11:03'),
(1095, '56', 'ZS\r', '2023-10-03 05:11:03'),
(1096, '56', 'ZS EV\r', '2023-10-03 05:11:03'),
(1097, '57', '4th Generation Pajero\r', '2023-10-03 05:11:03'),
(1098, '57', 'ASX\r', '2023-10-03 05:11:03'),
(1099, '57', 'Airtrek\r', '2023-10-03 05:11:03'),
(1100, '57', 'Attrage\r', '2023-10-03 05:11:03'),
(1101, '57', 'Canter\r', '2023-10-03 05:11:03'),
(1102, '57', 'Eclipse\r', '2023-10-03 05:11:03'),
(1103, '57', 'Eclipse Cross\r', '2023-10-03 05:11:03'),
(1104, '57', 'Endeavour\r', '2023-10-03 05:11:03'),
(1105, '57', 'FUSO\r', '2023-10-03 05:11:03'),
(1106, '57', 'Galant\r', '2023-10-03 05:11:03'),
(1107, '57', 'Grandis\r', '2023-10-03 05:11:03'),
(1108, '57', 'L200\r', '2023-10-03 05:11:03'),
(1109, '57', 'L300\r', '2023-10-03 05:11:03'),
(1110, '57', 'Lancer\r', '2023-10-03 05:11:03'),
(1111, '57', 'Lancer EX\r', '2023-10-03 05:11:03'),
(1112, '57', 'Lancer Evolution\r', '2023-10-03 05:11:03'),
(1113, '57', 'Lancer Fortis\r', '2023-10-03 05:11:03'),
(1114, '57', 'Magna\r', '2023-10-03 05:11:03'),
(1115, '57', 'Mirage\r', '2023-10-03 05:11:03'),
(1116, '57', 'Montero Sport\r', '2023-10-03 05:11:03'),
(1117, '57', 'Nativa\r', '2023-10-03 05:11:03'),
(1118, '57', 'Outlander\r', '2023-10-03 05:11:03'),
(1119, '57', 'Pajero\r', '2023-10-03 05:11:03'),
(1120, '57', 'Pajero Sport\r', '2023-10-03 05:11:03'),
(1121, '57', 'Pickup\r', '2023-10-03 05:11:03'),
(1122, '57', 'Rosa\r', '2023-10-03 05:11:03'),
(1123, '57', 'Spacestar\r', '2023-10-03 05:11:03'),
(1124, '57', 'Xpander\r', '2023-10-03 05:11:03'),
(1125, '57', 'Xpander Cross\r', '2023-10-03 05:11:03'),
(1126, '58', 'Suzuki\r', '2023-10-03 05:11:03'),
(1127, '58', 'Yamaha Motorcycles\r', '2023-10-03 05:11:03'),
(1128, '58', 'Chinese Motorcycle\r', '2023-10-03 05:11:03'),
(1129, '58', 'Honda Motorcycles\r', '2023-10-03 05:11:03'),
(1130, '58', 'Harley Motorcycles\r', '2023-10-03 05:11:03'),
(1131, '58', 'Ram\'s Motorcycles\r', '2023-10-03 05:11:03'),
(1132, '58', 'Kuzaki Motorcycles\r', '2023-10-03 05:11:03'),
(1133, '58', 'Jet Ski\r', '2023-10-03 05:11:03'),
(1134, '58', 'BMW Motorcycle\r', '2023-10-03 05:11:03'),
(1135, '58', 'KTM Motorcycles\r', '2023-10-03 05:11:03'),
(1136, '58', 'indian Motorcycle\r', '2023-10-03 05:11:03'),
(1137, '58', 'Buggy Car\r', '2023-10-03 05:11:03'),
(1138, '58', 'Polaris Bike\r', '2023-10-03 05:11:03'),
(1139, '58', 'Can Am\r', '2023-10-03 05:11:03'),
(1140, '58', 'Karting\r', '2023-10-03 05:11:03'),
(1141, '59', '280 ZX\r', '2023-10-03 05:11:03'),
(1142, '59', '350Z\r', '2023-10-03 05:11:03'),
(1143, '59', '370Z\r', '2023-10-03 05:11:03'),
(1144, '59', 'Altima\r', '2023-10-03 05:11:03'),
(1145, '59', 'Altima Coupe\r', '2023-10-03 05:11:03'),
(1146, '59', 'Ariya\r', '2023-10-03 05:11:03'),
(1147, '59', 'Armada\r', '2023-10-03 05:11:03'),
(1148, '59', 'Caravan\r', '2023-10-03 05:11:03'),
(1149, '59', 'Civilian\r', '2023-10-03 05:11:03'),
(1150, '59', 'Condor\r', '2023-10-03 05:11:03'),
(1151, '59', 'Cube\r', '2023-10-03 05:11:03'),
(1152, '59', 'Frontier\r', '2023-10-03 05:11:03'),
(1153, '59', 'GT-R\r', '2023-10-03 05:11:03'),
(1154, '59', 'Juke\r', '2023-10-03 05:11:03'),
(1155, '59', 'Kicks\r', '2023-10-03 05:11:03'),
(1156, '59', 'Leaf\r', '2023-10-03 05:11:03'),
(1157, '59', 'Maxima\r', '2023-10-03 05:11:03'),
(1158, '59', 'Micra\r', '2023-10-03 05:11:03'),
(1159, '59', 'Murano\r', '2023-10-03 05:11:03'),
(1160, '59', 'NV350\r', '2023-10-03 05:11:03'),
(1161, '59', 'Navara\r', '2023-10-03 05:11:03'),
(1162, '59', 'Pathfinder\r', '2023-10-03 05:11:03'),
(1163, '59', 'Patrol\r', '2023-10-03 05:11:03'),
(1164, '59', 'Patrol Pick Up\r', '2023-10-03 05:11:03'),
(1165, '59', 'Patrol Safari\r', '2023-10-03 05:11:03'),
(1166, '59', 'Patrol Super Safari\r', '2023-10-03 05:11:03'),
(1167, '59', 'Pickup\r', '2023-10-03 05:11:03'),
(1168, '59', 'Qashqai\r', '2023-10-03 05:11:03'),
(1169, '59', 'Quest\r', '2023-10-03 05:11:03'),
(1170, '59', 'Rogue\r', '2023-10-03 05:11:03'),
(1171, '59', 'Sentra\r', '2023-10-03 05:11:03'),
(1172, '59', 'Sunny\r', '2023-10-03 05:11:03'),
(1173, '59', 'Sunny Classic\r', '2023-10-03 05:11:03'),
(1174, '59', 'Terrano\r', '2023-10-03 05:11:03'),
(1175, '59', 'Tiida\r', '2023-10-03 05:11:03'),
(1176, '59', 'Titan\r', '2023-10-03 05:11:03'),
(1177, '59', 'United Diesel\r', '2023-10-03 05:11:03'),
(1178, '59', 'Urvan\r', '2023-10-03 05:11:03'),
(1179, '59', 'Versa\r', '2023-10-03 05:11:03'),
(1180, '59', 'X-Trail\r', '2023-10-03 05:11:03'),
(1181, '59', 'Xterra\r', '2023-10-03 05:11:03'),
(1182, '59', 'Z\r', '2023-10-03 05:11:03'),
(1183, '59', 'ZX300\r', '2023-10-03 05:11:03'),
(1184, '60', 'Astra\r', '2023-10-03 05:11:03'),
(1185, '60', 'Rekord\r', '2023-10-03 05:11:03'),
(1186, '61', '307\r', '2023-10-03 05:11:03'),
(1187, '61', '2008\r', '2023-10-03 05:11:03'),
(1188, '61', '206 CC\r', '2023-10-03 05:11:03'),
(1189, '61', '206cc\r', '2023-10-03 05:11:03'),
(1190, '61', '207\r', '2023-10-03 05:11:03'),
(1191, '61', '207cc\r', '2023-10-03 05:11:03'),
(1192, '61', '208\r', '2023-10-03 05:11:03'),
(1193, '61', '3008\r', '2023-10-03 05:11:03'),
(1194, '61', '301\r', '2023-10-03 05:11:03'),
(1195, '61', '307cc\r', '2023-10-03 05:11:03'),
(1196, '61', '308\r', '2023-10-03 05:11:03'),
(1197, '61', '308 CC\r', '2023-10-03 05:11:03'),
(1198, '61', '308 SW\r', '2023-10-03 05:11:03'),
(1199, '61', '407\r', '2023-10-03 05:11:03'),
(1200, '61', '408\r', '2023-10-03 05:11:03'),
(1201, '61', '5008\r', '2023-10-03 05:11:03'),
(1202, '61', '508\r', '2023-10-03 05:11:03'),
(1203, '61', '607\r', '2023-10-03 05:11:03'),
(1204, '61', 'Boxer\r', '2023-10-03 05:11:03'),
(1205, '61', 'Expert\r', '2023-10-03 05:11:03'),
(1206, '61', 'Landtrek\r', '2023-10-03 05:11:03'),
(1207, '61', 'Partner\r', '2023-10-03 05:11:03'),
(1208, '61', 'Partner B9\r', '2023-10-03 05:11:03'),
(1209, '61', 'Partner Origin\r', '2023-10-03 05:11:03'),
(1210, '61', 'RCZ\r', '2023-10-03 05:11:03'),
(1211, '61', 'RCZ (New)\r', '2023-10-03 05:11:03'),
(1212, '61', 'Rifter\r', '2023-10-03 05:11:03'),
(1213, '61', 'Traveller\r', '2023-10-03 05:11:03'),
(1214, '62', '718\r', '2023-10-03 05:11:03'),
(1215, '62', '911\r', '2023-10-03 05:11:03'),
(1216, '62', '918 Spyder\r', '2023-10-03 05:11:03'),
(1217, '62', '993\r', '2023-10-03 05:11:03'),
(1218, '62', 'Boxster\r', '2023-10-03 05:11:03'),
(1219, '62', 'Carrera GT\r', '2023-10-03 05:11:03'),
(1220, '62', 'Cayenne\r', '2023-10-03 05:11:03'),
(1221, '62', 'Cayenne Coupe\r', '2023-10-03 05:11:03'),
(1222, '62', 'Cayman\r', '2023-10-03 05:11:03'),
(1223, '62', 'GT2\r', '2023-10-03 05:11:03'),
(1224, '62', 'Macan\r', '2023-10-03 05:11:03'),
(1225, '62', 'Panamera\r', '2023-10-03 05:11:03'),
(1226, '62', 'Panamera Sport Turismo\r', '2023-10-03 05:11:03'),
(1227, '62', 'Taycan\r', '2023-10-03 05:11:03'),
(1228, '62', 'Taycan Cross Turismo\r', '2023-10-03 05:11:03'),
(1229, '63', 'GEN•2\r', '2023-10-03 05:11:03'),
(1230, '63', 'Persona\r', '2023-10-03 05:11:03'),
(1231, '63', 'Waja\r', '2023-10-03 05:11:03'),
(1232, '64', 'Captur\r', '2023-10-03 05:11:03'),
(1233, '64', 'Clio\r', '2023-10-03 05:11:03'),
(1234, '64', 'Clio Sport\r', '2023-10-03 05:11:03'),
(1235, '64', 'Dokker Van\r', '2023-10-03 05:11:03'),
(1236, '64', 'Duster\r', '2023-10-03 05:11:03'),
(1237, '64', 'Express Van\r', '2023-10-03 05:11:03'),
(1238, '64', 'Fluence\r', '2023-10-03 05:11:03'),
(1239, '64', 'Kadjar\r', '2023-10-03 05:11:03'),
(1240, '64', 'Koleos\r', '2023-10-03 05:11:03'),
(1241, '64', 'Laguna\r', '2023-10-03 05:11:03'),
(1242, '64', 'Lodgy\r', '2023-10-03 05:11:03'),
(1243, '64', 'Logan\r', '2023-10-03 05:11:03'),
(1244, '64', 'Logan MCV\r', '2023-10-03 05:11:03'),
(1245, '64', 'Master\r', '2023-10-03 05:11:03'),
(1246, '64', 'Megane\r', '2023-10-03 05:11:03'),
(1247, '64', 'Megane Hatchback\r', '2023-10-03 05:11:03'),
(1248, '64', 'Safrane\r', '2023-10-03 05:11:03'),
(1249, '64', 'Sandero\r', '2023-10-03 05:11:03'),
(1250, '64', 'Sandero Stepway\r', '2023-10-03 05:11:03'),
(1251, '64', 'Symbol\r', '2023-10-03 05:11:03'),
(1252, '64', 'Talisman\r', '2023-10-03 05:11:03'),
(1253, '64', 'Trafic\r', '2023-10-03 05:11:03'),
(1254, '64', 'Twizy\r', '2023-10-03 05:11:03'),
(1255, '64', 'XM3\r', '2023-10-03 05:11:03'),
(1256, '64', 'Zoe\r', '2023-10-03 05:11:03'),
(1257, '65', 'Corniche\r', '2023-10-03 05:11:03'),
(1258, '65', 'Cullinan\r', '2023-10-03 05:11:03'),
(1259, '65', 'Dawn\r', '2023-10-03 05:11:03'),
(1260, '65', 'Ghost\r', '2023-10-03 05:11:03'),
(1261, '65', 'Phantom\r', '2023-10-03 05:11:03'),
(1262, '65', 'Silver Cloud\r', '2023-10-03 05:11:03'),
(1263, '65', 'Silver Seraph\r', '2023-10-03 05:11:03'),
(1264, '65', 'Spectre\r', '2023-10-03 05:11:03'),
(1265, '65', 'Wraith\r', '2023-10-03 05:11:03'),
(1266, '66', 'SABB\r', '2023-10-03 05:11:03'),
(1267, '67', 'Seat\r', '2023-10-03 05:11:03'),
(1268, '68', 'Fabia\r', '2023-10-03 05:11:03'),
(1269, '68', 'Kamiq\r', '2023-10-03 05:11:03'),
(1270, '68', 'Karoq\r', '2023-10-03 05:11:03'),
(1271, '68', 'Kodiaq\r', '2023-10-03 05:11:03'),
(1272, '68', 'Kushaq\r', '2023-10-03 05:11:03'),
(1273, '68', 'Octavia\r', '2023-10-03 05:11:03'),
(1274, '68', 'Rapid\r', '2023-10-03 05:11:03'),
(1275, '68', 'Roomster\r', '2023-10-03 05:11:03'),
(1276, '68', 'Scala\r', '2023-10-03 05:11:03'),
(1277, '68', 'Superb\r', '2023-10-03 05:11:03'),
(1278, '68', 'Yeti\r', '2023-10-03 05:11:03'),
(1279, '69', 'SMART\r', '2023-10-03 05:11:03'),
(1280, '70', 'Soueaste\r', '2023-10-03 05:11:03'),
(1281, '71', 'Actyon Sport\r', '2023-10-03 05:11:03'),
(1282, '71', 'Chairman\r', '2023-10-03 05:11:03'),
(1283, '71', 'Khan\r', '2023-10-03 05:11:03'),
(1284, '71', 'Korando\r', '2023-10-03 05:11:03'),
(1285, '71', 'Rexton\r', '2023-10-03 05:11:03'),
(1286, '71', 'Rexton W\r', '2023-10-03 05:11:03'),
(1287, '71', 'Tivoli\r', '2023-10-03 05:11:03'),
(1288, '71', 'XLV\r', '2023-10-03 05:11:03'),
(1289, '72', 'BRZ\r', '2023-10-03 05:11:03'),
(1290, '72', 'Crosstrek\r', '2023-10-03 05:11:03'),
(1291, '72', 'Forester\r', '2023-10-03 05:11:03'),
(1292, '72', 'Impreza\r', '2023-10-03 05:11:03'),
(1293, '72', 'Impreza WRX STI\r', '2023-10-03 05:11:03'),
(1294, '72', 'Impreza wrx\r', '2023-10-03 05:11:03'),
(1295, '72', 'Legacy\r', '2023-10-03 05:11:03'),
(1296, '72', 'Outback\r', '2023-10-03 05:11:03'),
(1297, '72', 'Tribeca\r', '2023-10-03 05:11:03'),
(1298, '72', 'WRX\r', '2023-10-03 05:11:03'),
(1299, '72', 'XV\r', '2023-10-03 05:11:03'),
(1300, '73', 'APV\r', '2023-10-03 05:11:03'),
(1301, '73', 'Alto\r', '2023-10-03 05:11:03'),
(1302, '73', 'Baleno\r', '2023-10-03 05:11:03'),
(1303, '73', 'Carry\r', '2023-10-03 05:11:03'),
(1304, '73', 'Carry Microbus\r', '2023-10-03 05:11:03'),
(1305, '73', 'Celerio\r', '2023-10-03 05:11:03'),
(1306, '73', 'Celerio (Old Shape)\r', '2023-10-03 05:11:03'),
(1307, '73', 'Ciaz\r', '2023-10-03 05:11:03'),
(1308, '73', 'Dzire\r', '2023-10-03 05:11:03'),
(1309, '73', 'Ertiga\r', '2023-10-03 05:11:03'),
(1310, '73', 'Escudo\r', '2023-10-03 05:11:03'),
(1311, '73', 'Grand Vitara\r', '2023-10-03 05:11:03'),
(1312, '73', 'Ignis\r', '2023-10-03 05:11:03'),
(1313, '73', 'Jimny\r', '2023-10-03 05:11:03'),
(1314, '73', 'Kizashi\r', '2023-10-03 05:11:03'),
(1315, '73', 'S-Presso\r', '2023-10-03 05:11:03'),
(1316, '73', 'SX4\r', '2023-10-03 05:11:03'),
(1317, '73', 'Samurai\r', '2023-10-03 05:11:03'),
(1318, '73', 'Swift\r', '2023-10-03 05:11:03'),
(1319, '73', 'Swift Sport\r', '2023-10-03 05:11:03'),
(1320, '73', 'Swift dZire\r', '2023-10-03 05:11:03'),
(1321, '73', 'Vitara\r', '2023-10-03 05:11:03'),
(1322, '73', 'XL-7\r', '2023-10-03 05:11:03'),
(1323, '74', 'LPO 1618\r', '2023-10-03 05:11:03'),
(1324, '74', 'Xenon\r', '2023-10-03 05:11:03'),
(1325, '75', 'Model 3\r', '2023-10-03 05:11:03'),
(1326, '75', 'Model S\r', '2023-10-03 05:11:03'),
(1327, '75', 'Model X\r', '2023-10-03 05:11:03'),
(1328, '75', 'Model Y\r', '2023-10-03 05:11:03'),
(1329, '76', '4Runner\r', '2023-10-03 05:11:03'),
(1330, '76', '86\r', '2023-10-03 05:11:03'),
(1331, '76', 'Alphard\r', '2023-10-03 05:11:03'),
(1332, '76', 'Aristo\r', '2023-10-03 05:11:03'),
(1333, '76', 'Ascent\r', '2023-10-03 05:11:03'),
(1334, '76', 'Aurion\r', '2023-10-03 05:11:03'),
(1335, '76', 'Auris\r', '2023-10-03 05:11:03'),
(1336, '76', 'Avalon\r', '2023-10-03 05:11:03'),
(1337, '76', 'Avanza\r', '2023-10-03 05:11:03'),
(1338, '76', 'Avensis\r', '2023-10-03 05:11:03'),
(1339, '76', 'Belta\r', '2023-10-03 05:11:03'),
(1340, '76', 'C-HR\r', '2023-10-03 05:11:03'),
(1341, '76', 'Camry\r', '2023-10-03 05:11:03'),
(1342, '76', 'Century\r', '2023-10-03 05:11:03'),
(1343, '76', 'Chaser\r', '2023-10-03 05:11:03'),
(1344, '76', 'Coaster\r', '2023-10-03 05:11:03'),
(1345, '76', 'Corolla\r', '2023-10-03 05:11:03'),
(1346, '76', 'Corolla Cross\r', '2023-10-03 05:11:03'),
(1347, '76', 'Cressida\r', '2023-10-03 05:11:03'),
(1348, '76', 'Crown\r', '2023-10-03 05:11:03'),
(1349, '76', 'Dyna\r', '2023-10-03 05:11:03'),
(1350, '76', 'Echo\r', '2023-10-03 05:11:03'),
(1351, '76', 'FJ Cruiser\r', '2023-10-03 05:11:03'),
(1352, '76', 'Fielder\r', '2023-10-03 05:11:03'),
(1353, '76', 'Fortuner\r', '2023-10-03 05:11:03'),
(1354, '76', 'Granvia\r', '2023-10-03 05:11:03'),
(1355, '76', 'Harrier\r', '2023-10-03 05:11:03'),
(1356, '76', 'Hiace\r', '2023-10-03 05:11:03'),
(1357, '76', 'Highlander\r', '2023-10-03 05:11:03'),
(1358, '76', 'Hilux\r', '2023-10-03 05:11:03'),
(1359, '76', 'Hilux Surf\r', '2023-10-03 05:11:03'),
(1360, '76', 'Hino\r', '2023-10-03 05:11:03'),
(1361, '76', 'Innova\r', '2023-10-03 05:11:03'),
(1362, '76', 'Izoa\r', '2023-10-03 05:11:03'),
(1363, '76', 'Kluger\r', '2023-10-03 05:11:03'),
(1364, '76', 'Land Cruiser\r', '2023-10-03 05:11:03'),
(1365, '76', 'Land Cruiser Pick Up\r', '2023-10-03 05:11:03'),
(1366, '76', 'Land Cruiser Prado\r', '2023-10-03 05:11:03'),
(1367, '76', 'Levin\r', '2023-10-03 05:11:03'),
(1368, '76', 'Liteace\r', '2023-10-03 05:11:03'),
(1369, '76', 'Mark II Grande\r', '2023-10-03 05:11:03'),
(1370, '76', 'Mark X\r', '2023-10-03 05:11:03'),
(1371, '76', 'Noah\r', '2023-10-03 05:11:03'),
(1372, '76', 'Previa\r', '2023-10-03 05:11:03'),
(1373, '76', 'Prius\r', '2023-10-03 05:11:03'),
(1374, '76', 'Probox\r', '2023-10-03 05:11:03'),
(1375, '76', 'Raize\r', '2023-10-03 05:11:03'),
(1376, '76', 'Rav4\r', '2023-10-03 05:11:03'),
(1377, '76', 'Rumion\r', '2023-10-03 05:11:03'),
(1378, '76', 'Rush\r', '2023-10-03 05:11:03'),
(1379, '76', 'Sequoia\r', '2023-10-03 05:11:03'),
(1380, '76', 'Sienna\r', '2023-10-03 05:11:03'),
(1381, '76', 'Solara\r', '2023-10-03 05:11:03'),
(1382, '76', 'Supra\r', '2023-10-03 05:11:03'),
(1383, '76', 'Tacoma\r', '2023-10-03 05:11:03'),
(1384, '76', 'Tercel\r', '2023-10-03 05:11:03'),
(1385, '76', 'Townace\r', '2023-10-03 05:11:03'),
(1386, '76', 'Toyoace\r', '2023-10-03 05:11:03'),
(1387, '76', 'Tundra\r', '2023-10-03 05:11:03'),
(1388, '76', 'Urban Cruiser\r', '2023-10-03 05:11:03'),
(1389, '76', 'Vanguard\r', '2023-10-03 05:11:03'),
(1390, '76', 'Veloz\r', '2023-10-03 05:11:03'),
(1391, '76', 'Venza\r', '2023-10-03 05:11:03'),
(1392, '76', 'Verossa\r', '2023-10-03 05:11:03'),
(1393, '76', 'Vitz\r', '2023-10-03 05:11:03'),
(1394, '76', 'Wish\r', '2023-10-03 05:11:03'),
(1395, '76', 'XA\r', '2023-10-03 05:11:03'),
(1396, '76', 'Yaris\r', '2023-10-03 05:11:03'),
(1397, '76', 'Yaris Hatchback\r', '2023-10-03 05:11:03'),
(1398, '76', 'Yaris Sedan\r', '2023-10-03 05:11:03'),
(1399, '76', 'Zelas\r', '2023-10-03 05:11:03'),
(1400, '76', 'bZ4X\r', '2023-10-03 05:11:03'),
(1401, '76', 'iA\r', '2023-10-03 05:11:03'),
(1402, '76', 'iQ\r', '2023-10-03 05:11:03'),
(1403, '76', 'pickup\r', '2023-10-03 05:11:03'),
(1404, '76', 'tC\r', '2023-10-03 05:11:03'),
(1405, '77', 'Mercedes Truck\r', '2023-10-03 05:11:03'),
(1406, '77', 'Heavy Equipment\r', '2023-10-03 05:11:03'),
(1407, '77', 'Tipper\r', '2023-10-03 05:11:03'),
(1408, '77', 'Shiol\r', '2023-10-03 05:11:03'),
(1409, '77', 'Car Carrier\r', '2023-10-03 05:11:03'),
(1410, '77', 'Volvo Trucks\r', '2023-10-03 05:11:03'),
(1411, '77', 'MAN Truck\r', '2023-10-03 05:11:03'),
(1412, '77', 'Agricultural equipment\r', '2023-10-03 05:11:03'),
(1413, '77', 'Caravan\r', '2023-10-03 05:11:03'),
(1414, '77', 'Crane\r', '2023-10-03 05:11:03'),
(1415, '77', 'Bulldozer\r', '2023-10-03 05:11:03'),
(1416, '77', 'Crusher\r', '2023-10-03 05:11:03'),
(1417, '77', 'Scania Trucks\r', '2023-10-03 05:11:03'),
(1418, '77', 'Poclain\r', '2023-10-03 05:11:03'),
(1419, '77', 'Hino Truck\r', '2023-10-03 05:11:03'),
(1420, '77', 'Food Truck\r', '2023-10-03 05:11:03'),
(1421, '78', 'VICTORY AUTO\r', '2023-10-03 05:11:03'),
(1422, '79', 'Amarok\r', '2023-10-03 05:11:03'),
(1423, '79', 'Arteon\r', '2023-10-03 05:11:03'),
(1424, '79', 'Beetle\r', '2023-10-03 05:11:03'),
(1425, '79', 'CC\r', '2023-10-03 05:11:03'),
(1426, '79', 'Caddy\r', '2023-10-03 05:11:03'),
(1427, '79', 'Crafter\r', '2023-10-03 05:11:03'),
(1428, '79', 'Eos\r', '2023-10-03 05:11:03'),
(1429, '79', 'Eurovan\r', '2023-10-03 05:11:03'),
(1430, '79', 'Fox\r', '2023-10-03 05:11:03'),
(1431, '79', 'GTI\r', '2023-10-03 05:11:03'),
(1432, '79', 'Golf\r', '2023-10-03 05:11:03'),
(1433, '79', 'Golf GTI\r', '2023-10-03 05:11:03'),
(1434, '79', 'Golf R\r', '2023-10-03 05:11:03'),
(1435, '79', 'ID3\r', '2023-10-03 05:11:03'),
(1436, '79', 'ID4\r', '2023-10-03 05:11:03'),
(1437, '79', 'ID6\r', '2023-10-03 05:11:03'),
(1438, '79', 'Jetta\r', '2023-10-03 05:11:03'),
(1439, '79', 'LT46\r', '2023-10-03 05:11:03'),
(1440, '79', 'Multivan\r', '2023-10-03 05:11:03'),
(1441, '79', 'Passat\r', '2023-10-03 05:11:03'),
(1442, '79', 'Phaeton\r', '2023-10-03 05:11:03'),
(1443, '79', 'Polo\r', '2023-10-03 05:11:03'),
(1444, '79', 'R32\r', '2023-10-03 05:11:03'),
(1445, '79', 'Samba Type 2\r', '2023-10-03 05:11:03'),
(1446, '79', 'Scirocco\r', '2023-10-03 05:11:03'),
(1447, '79', 'Scirocco R\r', '2023-10-03 05:11:03'),
(1448, '79', 'T-Roc\r', '2023-10-03 05:11:03'),
(1449, '79', 'Teramont\r', '2023-10-03 05:11:03'),
(1450, '79', 'Tiguan\r', '2023-10-03 05:11:03'),
(1451, '79', 'Touareg\r', '2023-10-03 05:11:03'),
(1452, '79', 'Touran\r', '2023-10-03 05:11:03'),
(1453, '79', 'Transporter\r', '2023-10-03 05:11:03'),
(1454, '80', 'C30\r', '2023-10-03 05:11:03'),
(1455, '80', 'C40\r', '2023-10-03 05:11:03'),
(1456, '80', 'C70\r', '2023-10-03 05:11:03'),
(1457, '80', 'S40\r', '2023-10-03 05:11:03'),
(1458, '80', 'S60\r', '2023-10-03 05:11:03'),
(1459, '80', 'S80\r', '2023-10-03 05:11:03'),
(1460, '80', 'S90\r', '2023-10-03 05:11:03'),
(1461, '80', 'V40\r', '2023-10-03 05:11:03'),
(1462, '80', 'V60\r', '2023-10-03 05:11:03'),
(1463, '80', 'V70\r', '2023-10-03 05:11:03'),
(1464, '80', 'V90\r', '2023-10-03 05:11:03'),
(1465, '80', 'XC30\r', '2023-10-03 05:11:03'),
(1466, '80', 'XC40\r', '2023-10-03 05:11:03'),
(1467, '80', 'XC40 Recharge\r', '2023-10-03 05:11:03'),
(1468, '80', 'XC60\r', '2023-10-03 05:11:03'),
(1469, '80', 'XC70\r', '2023-10-03 05:11:03'),
(1470, '80', 'XC90\r', '2023-10-03 05:11:03'),
(1471, '81', 'Zotye\r', '2023-10-03 05:11:03'),
(1472, '82', 'Terralord\r', '2023-10-03 05:11:03'),
(1473, '0', 'Others', '2023-10-03 10:47:06'),
(1474, '31', 'ABC', '2023-10-05 06:21:03'),
(1475, '76', 'ABC', '2025-07-02 09:24:47');

-- --------------------------------------------------------

--
-- Table structure for table `category_type`
--

CREATE TABLE `category_type` (
  `id` int(11) NOT NULL,
  `type` varchar(15) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `category_type`
--

INSERT INTO `category_type` (`id`, `type`, `created_at`) VALUES
(1, 'Drive Thru', '2023-09-25 14:03:01'),
(2, 'University', '2023-09-25 14:03:01'),
(3, 'Non Trading', '2023-09-25 14:06:31'),
(4, 'Wholesale', '2023-09-25 14:04:15');

-- --------------------------------------------------------

--
-- Table structure for table `companies`
--

CREATE TABLE `companies` (
  `id` int(11) NOT NULL,
  `comp_id` int(11) NOT NULL,
  `comp_name` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `companies`
--

INSERT INTO `companies` (`id`, `comp_id`, `comp_name`, `created_at`) VALUES
(1, 1, 'JSPD', '2025-06-26 11:03:15'),
(2, 2, 'LOGISTIC', '2025-06-26 11:03:15'),
(3, 3, 'METAL', '2025-06-26 11:03:15'),
(4, 4, 'HEAD OFFICE', '2025-06-26 11:03:15'),
(5, 5, 'FILTER', '2025-06-26 11:03:15'),
(6, 7, 'RIYADH SPD / FIL', '2025-06-26 11:03:15'),
(7, 8, 'DAMMAM SPD / FIL / TYR', '2025-06-26 11:03:15'),
(8, 10, 'KILO 08', '2025-06-26 11:03:15'),
(9, 11, 'AMLAK', '2025-06-26 11:03:15'),
(10, 14, 'REEFER', '2025-06-26 11:03:15'),
(11, 15, 'SANAM', '2025-06-26 11:03:15');

-- --------------------------------------------------------

--
-- Table structure for table `contract_period`
--

CREATE TABLE `contract_period` (
  `id` int(11) NOT NULL,
  `period` varchar(50) NOT NULL,
  `vac_period` decimal(5,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `contract_period`
--

INSERT INTO `contract_period` (`id`, `period`, `vac_period`) VALUES
(3, '2 Years - 15', 30.00),
(4, '1 Year - 21', 21.00),
(5, '2 Years - 21', 42.00),
(6, '1 Year - 30', 30.00),
(7, '2 Years - 30', 60.00);

-- --------------------------------------------------------

--
-- Table structure for table `countries`
--

CREATE TABLE `countries` (
  `id` int(11) NOT NULL,
  `code` varchar(3) NOT NULL,
  `name` varchar(150) NOT NULL,
  `name_ar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dial_code` int(11) NOT NULL,
  `currency_name` varchar(20) NOT NULL,
  `currency_symbol` varchar(20) NOT NULL,
  `currency_code` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `countries`
--

INSERT INTO `countries` (`id`, `code`, `name`, `name_ar`, `dial_code`, `currency_name`, `currency_symbol`, `currency_code`) VALUES
(1, 'AF', 'Afghanistan', 'أفغانستان', 93, 'Afghan afghani', '؋', 'AFN'),
(2, 'AL', 'Albania', 'ألبانيا', 355, 'Albanian lek', 'L', 'ALL'),
(3, 'DZ', 'Algeria', 'الجزائر', 213, 'Algerian dinar', 'د.ج', 'DZD'),
(4, 'AS', 'American Samoa', 'ساموا الأمريكية', 1684, '', '', ''),
(5, 'AD', 'Andorra', 'أندورا', 376, 'Euro', '€', 'EUR'),
(6, 'AO', 'Angola', 'أنغولا', 244, 'Angolan kwanza', 'Kz', 'AOA'),
(7, 'AI', 'Anguilla', 'أنغويلا', 1264, 'East Caribbean dolla', '$', 'XCD'),
(8, 'AQ', 'Antarctica', 'القارة القطبية الجنوبية', 0, '', '', ''),
(9, 'AG', 'Antigua And Barbuda', 'أنتيغوا وبربودا', 1268, 'East Caribbean dolla', '$', 'XCD'),
(10, 'AR', 'Argentina', 'الأرجنتين', 54, 'Argentine peso', '$', 'ARS'),
(11, 'AM', 'Armenia', 'أرمينيا', 374, 'Armenian dram', '', 'AMD'),
(12, 'AW', 'Aruba', 'أروبا', 297, 'Aruban florin', 'ƒ', 'AWG'),
(13, 'AU', 'Australia', 'أستراليا', 61, 'Australian dollar', '$', 'AUD'),
(14, 'AT', 'Austria', 'النمسا', 43, 'Euro', '€', 'EUR'),
(15, 'AZ', 'Azerbaijan', 'أذربيجان', 994, 'Azerbaijani manat', '', 'AZN'),
(16, 'BS', 'Bahamas The', 'جزر البهاما', 1242, '', '', ''),
(17, 'BH', 'Bahrain', 'البحرين', 973, 'Bahraini dinar', '.د.ب', 'BHD'),
(18, 'BD', 'Bangladesh', 'بنغلاديش', 880, 'Bangladeshi taka', '৳', 'BDT'),
(19, 'BB', 'Barbados', 'بربادوس', 1246, 'Barbadian dollar', '$', 'BBD'),
(20, 'BY', 'Belarus', 'بيلاروسيا', 375, 'Belarusian ruble', 'Br', 'BYR'),
(21, 'BE', 'Belgium', 'بلجيكا', 32, 'Euro', '€', 'EUR'),
(22, 'BZ', 'Belize', 'بليز', 501, 'Belize dollar', '$', 'BZD'),
(23, 'BJ', 'Benin', 'بنين', 229, 'West African CFA fra', 'Fr', 'XOF'),
(24, 'BM', 'Bermuda', 'برمودا', 1441, 'Bermudian dollar', '$', 'BMD'),
(25, 'BT', 'Bhutan', 'بوتان', 975, 'Bhutanese ngultrum', 'Nu.', 'BTN'),
(26, 'BO', 'Bolivia', 'بوليفيا', 591, 'Bolivian boliviano', 'Bs.', 'BOB'),
(27, 'BA', 'Bosnia and Herzegovina', 'البوسنة والهرسك', 387, 'Bosnia and Herzegovi', 'KM or КМ', 'BAM'),
(28, 'BW', 'Botswana', 'بوتسوانا', 267, 'Botswana pula', 'P', 'BWP'),
(29, 'BV', 'Bouvet Island', 'جزيرة بوفيه', 0, '', '', ''),
(30, 'BR', 'Brazil', 'البرازيل', 55, 'Brazilian real', 'R$', 'BRL'),
(31, 'IO', 'British Indian Ocean Territory', 'إقليم المحيط البريطاني الهندي', 246, 'United States dollar', '$', 'USD'),
(32, 'BN', 'Brunei', 'بروناي', 673, 'Brunei dollar', '$', 'BND'),
(33, 'BG', 'Bulgaria', 'بلغاريا', 359, 'Bulgarian lev', 'лв', 'BGN'),
(34, 'BF', 'Burkina Faso', 'بوركينا فاسو', 226, 'West African CFA fra', 'Fr', 'XOF'),
(35, 'BI', 'Burundi', 'بوروندي', 257, 'Burundian franc', 'Fr', 'BIF'),
(36, 'KH', 'Cambodia', 'كمبوديا', 855, 'Cambodian riel', '៛', 'KHR'),
(37, 'CM', 'Cameroon', 'الكاميرون', 237, 'Central African CFA ', 'Fr', 'XAF'),
(38, 'CA', 'Canada', 'كندا', 1, 'Canadian dollar', '$', 'CAD'),
(39, 'CV', 'Cape Verde', 'الرأس الأخضر', 238, 'Cape Verdean escudo', 'Esc or $', 'CVE'),
(40, 'KY', 'Cayman Islands', 'جزر كايمان', 1345, 'Cayman Islands dolla', '$', 'KYD'),
(41, 'CF', 'Central African Republic', 'جمهورية أفريقيا الوسطى', 236, 'Central African CFA ', 'Fr', 'XAF'),
(42, 'TD', 'Chad', 'تشاد', 235, 'Central African CFA ', 'Fr', 'XAF'),
(43, 'CL', 'Chile', 'تشيلي', 56, 'Chilean peso', '$', 'CLP'),
(44, 'CN', 'China', 'الصين', 86, 'Chinese yuan', '¥ or 元', 'CNY'),
(45, 'CX', 'Christmas Island', 'جزيرة الكريسماس', 61, '', '', ''),
(46, 'CC', 'Cocos (Keeling) Islands', 'جزر كوكوس (كيلينغ)', 672, 'Australian dollar', '$', 'AUD'),
(47, 'CO', 'Colombia', 'كولومبيا', 57, 'Colombian peso', '$', 'COP'),
(48, 'KM', 'Comoros', 'جزر القمر', 269, 'Comorian franc', 'Fr', 'KMF'),
(49, 'CG', 'Congo', 'الكونغو', 242, '', '', ''),
(50, 'CD', 'Congo The Democratic Republic Of The', 'جمهورية الكونغو الديمقراطية', 242, '', '', ''),
(51, 'CK', 'Cook Islands', 'جزر كوك', 682, 'New Zealand dollar', '$', 'NZD'),
(52, 'CR', 'Costa Rica', 'كوستاريكا', 506, 'Costa Rican colón', '₡', 'CRC'),
(53, 'CI', 'Cote D\'Ivoire (Ivory Coast)', 'ساحل العاج', 225, '', '', ''),
(54, 'HR', 'Croatia (Hrvatska)', 'كرواتيا', 385, '', '', ''),
(55, 'CU', 'Cuba', 'كوبا', 53, 'Cuban convertible pe', '$', 'CUC'),
(56, 'CY', 'Cyprus', 'قبرص', 357, 'Euro', '€', 'EUR'),
(57, 'CZ', 'Czech Republic', 'جمهورية التشيك', 420, 'Czech koruna', 'Kč', 'CZK'),
(58, 'DK', 'Denmark', 'الدنمارك', 45, 'Danish krone', 'kr', 'DKK'),
(59, 'DJ', 'Djibouti', 'جيبوتي', 253, 'Djiboutian franc', 'Fr', 'DJF'),
(60, 'DM', 'Dominica', 'دومينيكا', 1767, 'East Caribbean dolla', '$', 'XCD'),
(61, 'DO', 'Dominican Republic', 'جمهورية الدومينيكان', 1809, 'Dominican peso', '$', 'DOP'),
(62, 'TP', 'East Timor', 'تيمور الشرقية', 670, 'United States dollar', '$', 'USD'),
(63, 'EC', 'Ecuador', 'الإكوادور', 593, 'United States dollar', '$', 'USD'),
(64, 'EG', 'Egypt', 'مصر', 20, 'Egyptian pound', '£ or ج.م', 'EGP'),
(65, 'SV', 'El Salvador', 'السلفادور', 503, 'United States dollar', '$', 'USD'),
(66, 'GQ', 'Equatorial Guinea', 'غينيا الاستوائية', 240, 'Central African CFA ', 'Fr', 'XAF'),
(67, 'ER', 'Eritrea', 'إريتريا', 291, 'Eritrean nakfa', 'Nfk', 'ERN'),
(68, 'EE', 'Estonia', 'إستونيا', 372, 'Euro', '€', 'EUR'),
(69, 'ET', 'Ethiopia', 'إثيوبيا', 251, 'Ethiopian birr', 'Br', 'ETB'),
(70, 'XA', 'External Territories of Australia', 'الأقاليم الخارجية لأستراليا', 61, '', '', ''),
(71, 'FK', 'Falkland Islands', 'جزر فوكلاند', 500, 'Falkland Islands pou', '£', 'FKP'),
(72, 'FO', 'Faroe Islands', 'جزر فارو', 298, 'Danish krone', 'kr', 'DKK'),
(73, 'FJ', 'Fiji Islands', 'جزر فيجي', 679, '', '', ''),
(74, 'FI', 'Finland', 'فنلندا', 358, 'Euro', '€', 'EUR'),
(75, 'FR', 'France', 'فرنسا', 33, 'Euro', '€', 'EUR'),
(76, 'GF', 'French Guiana', 'غيانا الفرنسية', 594, '', '', ''),
(77, 'PF', 'French Polynesia', 'بولينيزيا الفرنسية', 689, 'CFP franc', 'Fr', 'XPF'),
(78, 'TF', 'French Southern Territories', 'الأراضي الجنوبية الفرنسية', 0, '', '', ''),
(79, 'GA', 'Gabon', 'الغابون', 241, 'Central African CFA ', 'Fr', 'XAF'),
(80, 'GM', 'Gambia The', 'غامبيا', 220, '', '', ''),
(81, 'GE', 'Georgia', 'جورجيا', 995, 'Georgian lari', 'ლ', 'GEL'),
(82, 'DE', 'Germany', 'ألمانيا', 49, 'Euro', '€', 'EUR'),
(83, 'GH', 'Ghana', 'غانا', 233, 'Ghana cedi', '₵', 'GHS'),
(84, 'GI', 'Gibraltar', 'جبل طارق', 350, 'Gibraltar pound', '£', 'GIP'),
(85, 'GR', 'Greece', 'اليونان', 30, 'Euro', '€', 'EUR'),
(86, 'GL', 'Greenland', 'جرينلاند', 299, '', '', ''),
(87, 'GD', 'Grenada', 'غرينادا', 1473, 'East Caribbean dolla', '$', 'XCD'),
(88, 'GP', 'Guadeloupe', 'غوادلوب', 590, '', '', ''),
(89, 'GU', 'Guam', 'غوام', 1671, '', '', ''),
(90, 'GT', 'Guatemala', 'غواتيمالا', 502, 'Guatemalan quetzal', 'Q', 'GTQ'),
(91, 'XU', 'Guernsey and Alderney', 'غيرنسي وألدرني', 44, '', '', ''),
(92, 'GN', 'Guinea', 'غينيا', 224, 'Guinean franc', 'Fr', 'GNF'),
(93, 'GW', 'Guinea-Bissau', 'غينيا بيساو', 245, 'West African CFA fra', 'Fr', 'XOF'),
(94, 'GY', 'Guyana', 'غيانا', 592, 'Guyanese dollar', '$', 'GYD'),
(95, 'HT', 'Haiti', 'هايتي', 509, 'Haitian gourde', 'G', 'HTG'),
(96, 'HM', 'Heard and McDonald Islands', 'جزيرة هيرد وجزر ماكدونالد', 0, '', '', ''),
(97, 'HN', 'Honduras', 'هندوراس', 504, 'Honduran lempira', 'L', 'HNL'),
(98, 'HK', 'Hong Kong S.A.R.', 'هونغ كونغ', 852, '', '', ''),
(99, 'HU', 'Hungary', 'المجر', 36, 'Hungarian forint', 'Ft', 'HUF'),
(100, 'IS', 'Iceland', 'آيسلندا', 354, 'Icelandic króna', 'kr', 'ISK'),
(101, 'IN', 'India', 'الهند', 91, 'Indian rupee', '₹', 'INR'),
(102, 'ID', 'Indonesia', 'إندونيسيا', 62, 'Indonesian rupiah', 'Rp', 'IDR'),
(103, 'IR', 'Iran', 'إيران', 98, 'Iranian rial', '﷼', 'IRR'),
(104, 'IQ', 'Iraq', 'العراق', 964, 'Iraqi dinar', 'ع.د', 'IQD'),
(105, 'IE', 'Ireland', 'أيرلندا', 353, 'Euro', '€', 'EUR'),
(106, 'IL', 'Israel', 'إسرائيل', 972, 'Israeli new shekel', '₪', 'ILS'),
(107, 'IT', 'Italy', 'إيطاليا', 39, 'Euro', '€', 'EUR'),
(108, 'JM', 'Jamaica', 'جامايكا', 1876, 'Jamaican dollar', '$', 'JMD'),
(109, 'JP', 'Japan', 'اليابان', 81, 'Japanese yen', '¥', 'JPY'),
(110, 'XJ', 'Jersey', 'جيرسي', 44, 'British pound', '£', 'GBP'),
(111, 'JO', 'Jordan', 'الأردن', 962, 'Jordanian dinar', 'د.ا', 'JOD'),
(112, 'KZ', 'Kazakhstan', 'كازاخستان', 7, 'Kazakhstani tenge', '', 'KZT'),
(113, 'KE', 'Kenya', 'كينيا', 254, 'Kenyan shilling', 'Sh', 'KES'),
(114, 'KI', 'Kiribati', 'كيريباتي', 686, 'Australian dollar', '$', 'AUD'),
(115, 'KP', 'Korea North', 'كوريا الشمالية', 850, '', '', ''),
(116, 'KR', 'Korea South', 'كوريا الجنوبية', 82, '', '', ''),
(117, 'KW', 'Kuwait', 'الكويت', 965, 'Kuwaiti dinar', 'د.ك', 'KWD'),
(118, 'KG', 'Kyrgyzstan', 'قيرغيزستان', 996, 'Kyrgyzstani som', 'лв', 'KGS'),
(119, 'LA', 'Laos', 'لاوس', 856, 'Lao kip', '₭', 'LAK'),
(120, 'LV', 'Latvia', 'لاتفيا', 371, 'Euro', '€', 'EUR'),
(121, 'LB', 'Lebanon', 'لبنان', 961, 'Lebanese pound', 'ل.ل', 'LBP'),
(122, 'LS', 'Lesotho', 'ليسوتو', 266, 'Lesotho loti', 'L', 'LSL'),
(123, 'LR', 'Liberia', 'ليبيريا', 231, 'Liberian dollar', '$', 'LRD'),
(124, 'LY', 'Libya', 'ليبيا', 218, 'Libyan dinar', 'ل.د', 'LYD'),
(125, 'LI', 'Liechtenstein', 'ليختنشتاين', 423, 'Swiss franc', 'Fr', 'CHF'),
(126, 'LT', 'Lithuania', 'ليتوانيا', 370, 'Euro', '€', 'EUR'),
(127, 'LU', 'Luxembourg', 'لوكسمبورغ', 352, 'Euro', '€', 'EUR'),
(128, 'MO', 'Macau S.A.R.', 'ماكاو', 853, '', '', ''),
(129, 'MK', 'Macedonia', 'مقدونيا', 389, '', '', ''),
(130, 'MG', 'Madagascar', 'مدغشقر', 261, 'Malagasy ariary', 'Ar', 'MGA'),
(131, 'MW', 'Malawi', 'مالاوي', 265, 'Malawian kwacha', 'MK', 'MWK'),
(132, 'MY', 'Malaysia', 'ماليزيا', 60, 'Malaysian ringgit', 'RM', 'MYR'),
(133, 'MV', 'Maldives', 'جزر المالديف', 960, 'Maldivian rufiyaa', '.ރ', 'MVR'),
(134, 'ML', 'Mali', 'مالي', 223, 'West African CFA fra', 'Fr', 'XOF'),
(135, 'MT', 'Malta', 'مالطا', 356, 'Euro', '€', 'EUR'),
(136, 'XM', 'Man (Isle of)', 'جزيرة مان', 44, '', '', ''),
(137, 'MH', 'Marshall Islands', 'جزر مارشال', 692, 'United States dollar', '$', 'USD'),
(138, 'MQ', 'Martinique', 'مارتينيك', 596, '', '', ''),
(139, 'MR', 'Mauritania', 'موريتانيا', 222, 'Mauritanian ouguiya', 'UM', 'MRO'),
(140, 'MU', 'Mauritius', 'موريشيوس', 230, 'Mauritian rupee', '₨', 'MUR'),
(141, 'YT', 'Mayotte', 'مايوت', 269, '', '', ''),
(142, 'MX', 'Mexico', 'المكسيك', 52, 'Mexican peso', '$', 'MXN'),
(143, 'FM', 'Micronesia', 'ميكرونيزيا', 691, 'Micronesian dollar', '$', ''),
(144, 'MD', 'Moldova', 'مولدوفا', 373, 'Moldovan leu', 'L', 'MDL'),
(145, 'MC', 'Monaco', 'موناكو', 377, 'Euro', '€', 'EUR'),
(146, 'MN', 'Mongolia', 'منغوليا', 976, 'Mongolian tögrög', '₮', 'MNT'),
(147, 'MS', 'Montserrat', 'مونتسيرات', 1664, 'East Caribbean dolla', '$', 'XCD'),
(148, 'MA', 'Morocco', 'المغرب', 212, 'Moroccan dirham', 'د.م.', 'MAD'),
(149, 'MZ', 'Mozambique', 'موزمبيق', 258, 'Mozambican metical', 'MT', 'MZN'),
(150, 'MM', 'Myanmar', 'ميانمار', 95, 'Burmese kyat', 'Ks', 'MMK'),
(151, 'NA', 'Namibia', 'ناميبيا', 264, 'Namibian dollar', '$', 'NAD'),
(152, 'NR', 'Nauru', 'ناورو', 674, 'Australian dollar', '$', 'AUD'),
(153, 'NP', 'Nepal', 'نيبال', 977, 'Nepalese rupee', '₨', 'NPR'),
(154, 'AN', 'Netherlands Antilles', 'جزر الأنتيل الهولندية', 599, '', '', ''),
(155, 'NL', 'Netherlands The', 'هولندا', 31, '', '', ''),
(156, 'NC', 'New Caledonia', 'كاليدونيا الجديدة', 687, 'CFP franc', 'Fr', 'XPF'),
(157, 'NZ', 'New Zealand', 'نيوزيلندا', 64, 'New Zealand dollar', '$', 'NZD'),
(158, 'NI', 'Nicaragua', 'نيكاراغوا', 505, 'Nicaraguan córdoba', 'C$', 'NIO'),
(159, 'NE', 'Niger', 'النيجر', 227, 'West African CFA fra', 'Fr', 'XOF'),
(160, 'NG', 'Nigeria', 'نيجيريا', 234, 'Nigerian naira', '₦', 'NGN'),
(161, 'NU', 'Niue', 'نيوي', 683, 'New Zealand dollar', '$', 'NZD'),
(162, 'NF', 'Norfolk Island', 'جزيرة نورفولك', 672, '', '', ''),
(163, 'MP', 'Northern Mariana Islands', 'جزر ماريانا الشمالية', 1670, '', '', ''),
(164, 'NO', 'Norway', 'النرويج', 47, 'Norwegian krone', 'kr', 'NOK'),
(165, 'OM', 'Oman', 'عمان', 968, 'Omani rial', 'ر.ع.', 'OMR'),
(166, 'PK', 'Pakistan', 'باكستان', 92, 'Pakistani rupee', '₨', 'PKR'),
(167, 'PW', 'Palau', 'بالاو', 680, 'Palauan dollar', '$', ''),
(168, 'PS', 'Palestinian Territory Occupied', 'الأراضي الفلسطينية المحتلة', 970, '', '', ''),
(169, 'PA', 'Panama', 'بنما', 507, 'Panamanian balboa', 'B/.', 'PAB'),
(170, 'PG', 'Papua new Guinea', 'بابوا غينيا الجديدة', 675, 'Papua New Guinean ki', 'K', 'PGK'),
(171, 'PY', 'Paraguay', 'باراغواي', 595, 'Paraguayan guaraní', '₲', 'PYG'),
(172, 'PE', 'Peru', 'بيرو', 51, 'Peruvian nuevo sol', 'S/.', 'PEN'),
(173, 'PH', 'Philippines', 'الفلبين', 63, 'Philippine peso', '₱', 'PHP'),
(174, 'PN', 'Pitcairn Island', 'جزيرة بيتكيرن', 0, '', '', ''),
(175, 'PL', 'Poland', 'بولندا', 48, 'Polish złoty', 'zł', 'PLN'),
(176, 'PT', 'Portugal', 'البرتغال', 351, 'Euro', '€', 'EUR'),
(177, 'PR', 'Puerto Rico', 'بورتوريكو', 1787, '', '', ''),
(178, 'QA', 'Qatar', 'قطر', 974, 'Qatari riyal', 'ر.ق', 'QAR'),
(179, 'RE', 'Reunion', 'ريونيون', 262, '', '', ''),
(180, 'RO', 'Romania', 'رومانيا', 40, 'Romanian leu', 'lei', 'RON'),
(181, 'RU', 'Russia', 'روسيا', 70, 'Russian ruble', '', 'RUB'),
(182, 'RW', 'Rwanda', 'رواندا', 250, 'Rwandan franc', 'Fr', 'RWF'),
(183, 'SH', 'Saint Helena', 'سانت هيلينا', 290, 'Saint Helena pound', '£', 'SHP'),
(184, 'KN', 'Saint Kitts And Nevis', 'سانت كيتس ونيفيس', 1869, 'East Caribbean dolla', '$', 'XCD'),
(185, 'LC', 'Saint Lucia', 'سانت لوسيا', 1758, 'East Caribbean dolla', '$', 'XCD'),
(186, 'PM', 'Saint Pierre and Miquelon', 'سان بيير وميكلون', 508, '', '', ''),
(187, 'VC', 'Saint Vincent And The Grenadines', 'سانت فنسنت والجرينادين', 1784, 'East Caribbean dolla', '$', 'XCD'),
(188, 'WS', 'Samoa', 'ساموا', 684, 'Samoan tālā', 'T', 'WST'),
(189, 'SM', 'San Marino', 'سان مارينو', 378, 'Euro', '€', 'EUR'),
(190, 'ST', 'Sao Tome and Principe', 'ساو تومي وبرينسيب', 239, 'São Tomé and Príncip', 'Db', 'STD'),
(191, 'SA', 'Saudi Arabia', 'المملكة العربية السعودية', 966, 'Saudi riyal', 'ر.س', 'SAR'),
(192, 'SN', 'Senegal', 'السنغال', 221, 'West African CFA fra', 'Fr', 'XOF'),
(193, 'RS', 'Serbia', 'صربيا', 381, 'Serbian dinar', 'дин. or din.', 'RSD'),
(194, 'SC', 'Seychelles', 'سيشيل', 248, 'Seychellois rupee', '₨', 'SCR'),
(195, 'SL', 'Sierra Leone', 'سيراليون', 232, 'Sierra Leonean leone', 'Le', 'SLL'),
(196, 'SG', 'Singapore', 'سنغافورة', 65, 'Brunei dollar', '$', 'BND'),
(197, 'SK', 'Slovakia', 'سلوفاكيا', 421, 'Euro', '€', 'EUR'),
(198, 'SI', 'Slovenia', 'سلوفينيا', 386, 'Euro', '€', 'EUR'),
(199, 'XG', 'Smaller Territories of the UK', 'أقاليم المملكة المتحدة الصغيرة', 44, '', '', ''),
(200, 'SB', 'Solomon Islands', 'جزر سليمان', 677, 'Solomon Islands doll', '$', 'SBD'),
(201, 'SO', 'Somalia', 'الصومال', 252, 'Somali shilling', 'Sh', 'SOS'),
(202, 'ZA', 'South Africa', 'جنوب أفريقيا', 27, 'South African rand', 'R', 'ZAR'),
(203, 'GS', 'South Georgia', 'جورجيا الجنوبية', 0, '', '', ''),
(204, 'SS', 'South Sudan', 'جنوب السودان', 211, 'South Sudanese pound', '£', 'SSP'),
(205, 'ES', 'Spain', 'إسبانيا', 34, 'Euro', '€', 'EUR'),
(206, 'LK', 'Sri Lanka', 'سريلانكا', 94, 'Sri Lankan rupee', 'Rs or රු', 'LKR'),
(207, 'SD', 'Sudan', 'السودان', 249, 'Sudanese pound', 'ج.س.', 'SDG'),
(208, 'SR', 'Suriname', 'سورينام', 597, 'Surinamese dollar', '$', 'SRD'),
(209, 'SJ', 'Svalbard And Jan Mayen Islands', 'سفالبارد وجان ماين', 47, '', '', ''),
(210, 'SZ', 'Swaziland', 'سوازيلاند', 268, 'Swazi lilangeni', 'L', 'SZL'),
(211, 'SE', 'Sweden', 'السويد', 46, 'Swedish krona', 'kr', 'SEK'),
(212, 'CH', 'Switzerland', 'سويسرا', 41, 'Swiss franc', 'Fr', 'CHF'),
(213, 'SY', 'Syria', 'سوريا', 963, 'Syrian pound', '£ or ل.س', 'SYP'),
(214, 'TW', 'Taiwan', 'تايوان', 886, 'New Taiwan dollar', '$', 'TWD'),
(215, 'TJ', 'Tajikistan', 'طاجيكستان', 992, 'Tajikistani somoni', 'ЅМ', 'TJS'),
(216, 'TZ', 'Tanzania', 'تنزانيا', 255, 'Tanzanian shilling', 'Sh', 'TZS'),
(217, 'TH', 'Thailand', 'تايلاند', 66, 'Thai baht', '฿', 'THB'),
(218, 'TG', 'Togo', 'توغو', 228, 'West African CFA fra', 'Fr', 'XOF'),
(219, 'TK', 'Tokelau', 'توكيلاو', 690, '', '', ''),
(220, 'TO', 'Tonga', 'تونغا', 676, 'Tongan paʻanga', 'T$', 'TOP'),
(221, 'TT', 'Trinidad And Tobago', 'ترينيداد وتوباغو', 1868, 'Trinidad and Tobago ', '$', 'TTD'),
(222, 'TN', 'Tunisia', 'تونس', 216, 'Tunisian dinar', 'د.ت', 'TND'),
(223, 'TR', 'Turkey', 'تركيا', 90, 'Turkish lira', '', 'TRY'),
(224, 'TM', 'Turkmenistan', 'تركمانستان', 7370, 'Turkmenistan manat', 'm', 'TMT'),
(225, 'TC', 'Turks And Caicos Islands', 'جزر تركس وكايكوس', 1649, 'United States dollar', '$', 'USD'),
(226, 'TV', 'Tuvalu', 'توفالو', 688, 'Australian dollar', '$', 'AUD'),
(227, 'UG', 'Uganda', 'أوغندا', 256, 'Ugandan shilling', 'Sh', 'UGX'),
(228, 'UA', 'Ukraine', 'أوكرانيا', 380, 'Ukrainian hryvnia', '₴', 'UAH'),
(229, 'AE', 'United Arab Emirates', 'الإمارات العربية المتحدة', 971, 'United Arab Emirates', 'د.إ', 'AED'),
(230, 'GB', 'United Kingdom', 'المملكة المتحدة', 44, 'British pound', '£', 'GBP'),
(231, 'US', 'United States', 'الولايات المتحدة', 1, 'United States dollar', '$', 'USD'),
(232, 'UM', 'United States Minor Outlying Islands', 'جزر الولايات المتحدة الصغيرة النائية', 1, '', '', ''),
(233, 'UY', 'Uruguay', 'أوروغواي', 598, 'Uruguayan peso', '$', 'UYU'),
(234, 'UZ', 'Uzbekistan', 'أوزبكستان', 998, 'Uzbekistani som', '', 'UZS'),
(235, 'VU', 'Vanuatu', 'فانواتو', 678, 'Vanuatu vatu', 'Vt', 'VUV'),
(236, 'VA', 'Vatican City State (Holy See)', 'الفاتيكان', 39, '', '', ''),
(237, 'VE', 'Venezuela', 'فنزويلا', 58, 'Venezuelan bolívar', 'Bs F', 'VEF'),
(238, 'VN', 'Vietnam', 'فيتنام', 84, 'Vietnamese đồng', '₫', 'VND'),
(239, 'VG', 'Virgin Islands (British)', 'جزر العذراء (البريطانية)', 1284, '', '', ''),
(240, 'VI', 'Virgin Islands (US)', 'جزر العذراء (الأمريكية)', 1340, '', '', ''),
(241, 'WF', 'Wallis And Futuna Islands', 'واليس وفوتونا', 681, '', '', ''),
(242, 'EH', 'Western Sahara', 'الصحراء الغربية', 212, '', '', ''),
(243, 'YE', 'Yemen', 'اليمن', 967, 'Yemeni rial', '﷼', 'YER'),
(244, 'YU', 'Yugoslavia', 'يوغوسلافيا', 38, '', '', ''),
(245, 'ZM', 'Zambia', 'زامبيا', 260, 'Zambian kwacha', 'ZK', 'ZMW'),
(246, 'ZW', 'Zimbabwe', 'زيمبابوي', 263, 'Botswana pula', 'P', 'BWP'),
(247, '', 'Misplaced Tribes', 'قبائل في غير مكانها', 0, '', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `id` int(11) NOT NULL,
  `injazat_no` int(100) NOT NULL,
  `acc_no` varchar(100) NOT NULL,
  `full_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `lname` varchar(150) NOT NULL,
  `dob` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `pobox` varchar(100) NOT NULL,
  `business_phone` varchar(100) NOT NULL,
  `home_phone` varchar(100) NOT NULL,
  `mobile` varchar(100) NOT NULL,
  `fax number` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `city` varchar(100) NOT NULL,
  `nationality` varchar(100) NOT NULL,
  `zip_postal_code` varchar(100) NOT NULL,
  `country_region` varchar(100) NOT NULL,
  `status` varchar(10) NOT NULL DEFAULT 'A',
  `shop_no` varchar(10) NOT NULL,
  `issue_date` varchar(100) NOT NULL,
  `exp_date` varchar(100) NOT NULL,
  `reg_fee` varchar(100) NOT NULL,
  `notes` text NOT NULL,
  `card_receive_date` varchar(100) NOT NULL,
  `sectin_nme` varchar(150) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer_access`
--

CREATE TABLE `customer_access` (
  `id` int(11) NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(150) NOT NULL,
  `pass_bk` varchar(255) NOT NULL,
  `mobile` varchar(100) NOT NULL,
  `img` varchar(255) NOT NULL,
  `lang` varchar(20) NOT NULL DEFAULT 'en',
  `status` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer_cart_address`
--

CREATE TABLE `customer_cart_address` (
  `id` int(11) NOT NULL,
  `cust_id` int(11) NOT NULL,
  `street_name` text NOT NULL,
  `building_name` text NOT NULL,
  `others` varchar(255) NOT NULL,
  `city` varchar(255) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `default` int(1) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cust_card_update`
--

CREATE TABLE `cust_card_update` (
  `id` int(11) NOT NULL,
  `cust_no` int(100) NOT NULL,
  `injazat_no` varchar(100) NOT NULL,
  `sectin_nme` varchar(150) NOT NULL,
  `exp_date` varchar(255) NOT NULL,
  `status` varchar(10) NOT NULL,
  `created_at` timestamp NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `department`
--

CREATE TABLE `department` (
  `id` int(11) NOT NULL,
  `dep_nme` varchar(255) NOT NULL,
  `dep_nme_ar` varchar(120) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `mobile` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `designation` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `department`
--

INSERT INTO `department` (`id`, `dep_nme`, `dep_nme_ar`, `mobile`, `email`, `designation`) VALUES
(1, 'Administration and Transportation', 'الإدارة والنقل', '', '', ''),
(2, 'Finance', 'المالية', '', '', ''),
(5, 'Human Resources', 'الموارد البشرية', '', '', ''),
(12, 'Public Relation', 'العلاقات العامة', '', '', ''),
(14, 'Sales', 'المبيعات', '', '', ''),
(7, 'Inspection', 'التفتيش', '', '', ''),
(13, 'Purchase', 'المشتريات', '', '', ''),
(6, 'Information Technology', 'تكنولوجيا المعلومات', '', '', ''),
(11, 'Production', 'الإنتاج', '', '', ''),
(15, 'Warehouse', 'مستودع', '', '', ''),
(9, 'Maintenance', 'الصيانة', '', '', ''),
(10, 'Management', 'الإدارة', '', '', ''),
(3, 'General', 'عام', '', '', ''),
(4, 'Housing', 'السكن', '', '', ''),
(8, 'Logistics', 'الخدمات اللوجستية', '', '', ''),
(16, 'Training', 'التدريب', '', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `dept_clr`
--

CREATE TABLE `dept_clr` (
  `id` int(11) NOT NULL,
  `dept_name` varchar(100) NOT NULL,
  `color` varchar(100) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `dept_clr`
--

INSERT INTO `dept_clr` (`id`, `dept_name`, `color`) VALUES
(1, '1', 'custom'),
(2, '2', 'purple'),
(3, '5', 'primary'),
(4, '7', 'success'),
(5, '6', 'custom'),
(6, '9', 'purple'),
(7, '10', 'primary'),
(8, '8', 'success'),
(9, '11', 'custom'),
(10, '12', 'purple'),
(11, '13', 'primary'),
(12, '14', 'success'),
(13, '3', 'custom'),
(15, '15', 'purple'),
(16, '4', 'success'),
(18, '16', 'success');

-- --------------------------------------------------------

--
-- Table structure for table `docu_type`
--

CREATE TABLE `docu_type` (
  `id` int(11) NOT NULL,
  `duc_type` varchar(150) NOT NULL,
  `date_reg` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `docu_type`
--

INSERT INTO `docu_type` (`id`, `duc_type`, `date_reg`) VALUES
(1, 'Iqama', ''),
(2, 'Passport Front', ''),
(3, 'Passport Back', ''),
(4, 'Passport', ''),
(5, 'CompanyContract', ''),
(7, 'BaldiaCard', ''),
(8, 'BaldiaCertificate ', ''),
(9, 'ID Card', '');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `emp_id` varchar(255) NOT NULL,
  `iqama` varchar(255) NOT NULL,
  `iqama_exp` varchar(255) NOT NULL,
  `iqama_exp_g` varchar(100) NOT NULL,
  `mobile` varchar(255) NOT NULL,
  `passport_number` varchar(255) NOT NULL,
  `passport_exp` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `c_email` varchar(255) NOT NULL,
  `emg_mobile` varchar(255) DEFAULT NULL,
  `emg_name` varchar(100) DEFAULT NULL,
  `salary` varchar(255) NOT NULL,
  `dept` varchar(50) NOT NULL,
  `sectin_nme` varchar(50) NOT NULL,
  `emptype` varchar(50) NOT NULL,
  `country` varchar(150) NOT NULL,
  `vacation_days` varchar(255) NOT NULL,
  `joining_date` varchar(255) NOT NULL,
  `contract_end_date` date DEFAULT NULL,
  `fly` varchar(10) NOT NULL,
  `bank_name` varchar(255) NOT NULL,
  `iban` varchar(255) NOT NULL,
  `note` varchar(100) NOT NULL,
  `ter_note` varchar(255) NOT NULL,
  `ter_date` varchar(50) NOT NULL,
  `dob` varchar(255) NOT NULL,
  `dob_h` varchar(50) NOT NULL,
  `vac_period` varchar(100) NOT NULL,
  `sex` varchar(50) NOT NULL,
  `blood_type` varchar(255) NOT NULL,
  `actual_job` int(11) NOT NULL,
  `mar_status` varchar(10) NOT NULL,
  `t_shirt_size` varchar(100) NOT NULL,
  `emp_sup_type` varchar(20) NOT NULL,
  `comp_no` int(11) NOT NULL,
  `address` text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `gosi` decimal(10,2) DEFAULT NULL,
  `insurance_no` varchar(100) DEFAULT NULL,
  `insurance_exp` varchar(100) DEFAULT NULL,
  `insurance_class` varchar(50) DEFAULT NULL,
  `payment_type` enum('1','2','3','') NOT NULL DEFAULT '1' COMMENT '1 = Bank\r\n2 = Cash\r\n3 = Hold',
  `probation` varchar(15) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `status` int(11) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `name`, `emp_id`, `iqama`, `iqama_exp`, `iqama_exp_g`, `mobile`, `passport_number`, `passport_exp`, `email`, `c_email`, `emg_mobile`, `emg_name`, `salary`, `dept`, `sectin_nme`, `emptype`, `country`, `vacation_days`, `joining_date`, `contract_end_date`, `fly`, `bank_name`, `iban`, `note`, `ter_note`, `ter_date`, `dob`, `dob_h`, `vac_period`, `sex`, `blood_type`, `actual_job`, `mar_status`, `t_shirt_size`, `emp_sup_type`, `comp_no`, `address`, `gosi`, `insurance_no`, `insurance_exp`, `insurance_class`, `payment_type`, `probation`, `avatar`, `status`, `created_at`, `updated_at`) VALUES
(1, 'ABU AL FOTOOH A MAJD A FATTAH', '1061', '2006725473', '', '1962-12-31', '0549752221', 'A27971974', '', '', '', '', '', '2450', '11', '16', 'Supporter', '64', '60', '1986-12-29', NULL, '0', '2', 'SA7410000010850689000104', '', '', '', '1962-12-31', '1382-08-04', '7', '1', '', 1, 'married', '', '1', 3, '', NULL, '', '', 'CLT', '1', '', './assets/emp_pics/defult.png', 1, NULL, '2025-09-01 08:14:17'),
(2, 'BASHIR AHMED GHLAM RASOOL', '1496', '2060294820', '1961-01-01', '1961-01-01', '0559933765', 'CS1153133', '', '', '', '', '', '2750', '11', '16', 'Supporter', '166', '60', '1991-04-23', NULL, '0', '2', 'SA9310000010850651000109', '', '', '', '1961-01-01', '', '7', '1', '', 1, '', '', '2', 3, '', NULL, '', '', 'CLT', '1', NULL, './../../assets/emp_pics/emp_1496/avatar_1752407236.png', 1, NULL, '2025-07-13 11:47:25'),
(3, 'TAJ MOHD MOHD IBRAHIM', '1500', '2060295322', '1960-01-01', '1960-01-01', '0552291735', 'NL0152783', '', '', '', '', '', '2800', '11', '16', 'Supporter', '166', '60', '1991-04-23', NULL, '0', '3', 'SA3720000008112060295322', '', '', '', '1960-01-01', '', '7', '1', '', 1, '', '', '2', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(4, 'EDUARDO PRANADA', '1574', '2070030206', '1958-01-05', '1958-01-05', '0541482214', 'EB9973444', '', '', '', '', '', '3575', '1', '1', 'Supporter', '173', '60', '1992-02-04', NULL, '0', '3', 'SA6520000001802257419940', '', '', '', '1958-01-05', '', '7', '1', '', 2, '', '', '2', 4, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(5, 'PALLIYIL BHASKARAN SANTHOSH', '1673', '2088294448', '1965-05-03', '1965-05-03', '0562650957', 'M5270638', '', '', '', '', '', '2700', '11', '17', 'Supporter', '101', '60', '1993-07-01', NULL, '0', '8', 'SA0830400108086010600011', '', '', '', '1965-05-03', '', '7', '1', '', 3, '', '', '3', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(6, 'HARINDRAN MARAMANGALATH', '1685', '2088275033', '1964-05-15', '1964-05-15', '0553719500', 'L5933726', '', '', '', '', '', '3125', '11', '17', 'Supporter', '101', '60', '1993-07-04', NULL, '0', '6', 'SA4580000648608016892547', '', '', '', '1964-05-15', '', '7', '1', '', 3, '', '', '3', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(7, 'NABI AHMED NAZIR HUSSAIN', '1764', '2101940845', '1963-11-30', '1963-11-30', '0569483326', 'BH9996882', '', '', '', '', '', '2925', '11', '16', 'Supporter', '166', '60', '1994-08-21', NULL, '0', '6', 'SA2780000648608017757723', '', '', '', '1963-11-30', '', '7', '1', '', 4, '', '', '1', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(8, 'EBRAHEEM ABUBAKKER', '1805', '2117385332', '1964-06-08', '1964-06-08', '0560510389', 'K8234066', '', '', '', '', '', '3190', '11', '17', 'Supporter', '101', '60', '1996-03-14', NULL, '0', '6', 'SA7780000640608018638177', '', '', '', '1964-06-08', '', '7', '1', '', 5, '', '', '3', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(9, 'ASANARU KUNJU ABDULSALIM', '1822', '2098335132', '1964-05-19', '1964-05-19', '0557470459', 'E2106877', '', '', '', '', '', '2100', '11', '16', 'Supporter', '101', '60', '1996-09-19', NULL, '0', '6', 'SA1980000648608016812990', '', '', '', '1964-05-19', '', '7', '1', '', 6, '', '', '1', 3, '', NULL, '', '', 'CLT', '1', NULL, './../../assets/emp_pics/emp_1822/avatar_1752410844.png', 1, NULL, '2025-07-13 12:47:47'),
(10, 'MATHEW MATHEW OOMMEN', '1837', '2125999124', '1965-05-28', '1965-05-28', '0553766698', 'K4674996', '', '', '', '', '', '4750', '11', '17', 'Supporter', '101', '60', '1997-01-01', NULL, '0', '3', 'SA0920000001802249809940', '', '', '', '1965-05-28', '', '7', '1', '', 7, '', '', '3', 5, '', NULL, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(11, 'GHULAM MUSTAFA', '1857', '2128110885', '1960-11-30', '1960-11-30', '0509383458', 'AL0872373', '', '', '', '', '', '3800', '1', '1', 'Supporter', '166', '30', '1997-05-01', NULL, '0', '6', 'SA2080000991608016709298', '', '', '', '1960-11-30', '', '6', '1', '', 8, '', '', '1', 4, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(12, 'MOHAMMAD AZEEM', '1861', '2080282540', '1972-06-18', '1972-06-18', '0542933581', 'BB1850423', '', '', '', '', '', '2500', '11', '16', 'Supporter', '166', '60', '1997-05-07', NULL, '0', '3', 'SA9220000008112080282540', '', '', '', '1972-06-18', '', '7', '1', '', 1, '', '', '2', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(13, 'MOHAMED HASSAN TAHA', '1872', '2104892928', '1964-10-21', '1964-10-21', '0542425266', 'A15839591', '', '', '', '', '', '4920', '1', '1', 'Supporter', '64', '30', '1997-08-21', NULL, '0', '3', 'SA9820000001800190659940', '', '', '', '1964-10-21', '', '6', '1', '', 9, '', '', '1', 4, '', NULL, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(14, 'RAHIM MOHD AFZAL', '1897', '2122282532', '1966-11-11', '1966-11-11', '0501385260', 'K9609354', '', '', '', '', '', '4100', '11', '16', 'Supporter', '101', '30', '1997-03-15', NULL, '0', '3', 'SA0720000001802273129940', '', '', '', '1966-11-11', '', '6', '1', '', 10, '', '', '2', 3, '', NULL, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(15, 'VANNARA PURAYIL MADHU', '1916', '2104468182', '1970-03-19', '1970-03-19', '0547627518', 'K8232955', '', '', '', '', '', '2525', '11', '17', 'Supporter', '101', '60', '1998-03-01', NULL, '0', '2', 'SA4210000010850599000103', '', '', '', '1970-03-19', '', '7', '1', '', 11, '', '', '3', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(16, 'MOHAMMED MUSTHAFA KONOTHODY', '1928', '2122407089', '1972-05-20', '1972-05-20', '0556698961', 'L3809343', '', '', '', '', '', '2100', '4', '4', 'Supporter', '101', '60', '1998-05-26', NULL, '0', '6', 'SA5880000648608017994136', '', '', '', '1972-05-20', '', '7', '1', '', 12, '', '', '1', 3, '', NULL, '', '', 'CLT', '1', NULL, './../../assets/emp_pics/emp_1928/avatar_1755693449.png', 1, NULL, '2025-08-20 12:37:50'),
(17, 'ANIS HARUN RASHID', '1931', '2140657178', '1972-04-05', '1972-04-05', '0507416546', 'BT0441416', '', '', '', '', '', '2350', '11', '16', 'Supporter', '18', '60', '1998-05-29', NULL, '0', '3', 'SA3320000008112140657178', '', '', '', '1972-04-05', '', '7', '1', '', 13, '', '', '2', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(18, 'DANTE B BAYOT', '1944', '2141853594', '1971-02-20', '1971-02-20', '0560281845', 'P6441244B', '', '', '', '', '', '3100', '11', '17', 'Supporter', '173', '60', '1998-07-04', NULL, '0', '6', 'SA2380000640608017657509', '', '', '', '1971-02-20', '', '7', '1', '', 3, '', '', '3', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(19, 'FAROK HOSSAIN YOUNUS ALI', '1996', '2145835134', '1972-10-01', '1972-10-01', '0561625783', 'EG0000042', '', '', '', '', '', '2800', '11', '16', 'Supporter', '18', '60', '1998-10-22', NULL, '0', '3', 'SA6020000008112145835134', '', '', '', '1972-10-01', '', '7', '1', '', 14, '', '', '2', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-08-26 18:59:16'),
(20, 'JAHANGIR HOSSAIN', '1997', '2145835803', '1972-03-26', '1972-03-26', '0551961412', 'BW0762280', '', '', '', '', '', '2550', '11', '16', 'Supporter', '18', '60', '1998-10-22', NULL, '0', '3', 'SA3920000008112145835803', '', '', '', '1972-03-26', '', '7', '1', '', 13, '', '', '2', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(21, 'ABUL BASHAR FARHAD ALI', '1998', '2145834988', '1965-02-02', '1965-02-02', '0558914362', 'EG0949352', '', '', '', '', '', '2550', '11', '16', 'Supporter', '18', '60', '1998-10-22', NULL, '0', '3', 'SA2520000008112145834988', '', '', '', '1965-02-02', '', '7', '1', '', 13, '', '', '2', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(22, 'MOHAMMAD LAL MIAH', '1999', '2145832388', '1967-04-04', '1967-04-04', '0549561417', 'BW0146885', '', '', '', '', '', '2350', '11', '16', 'Supporter', '18', '60', '1998-10-22', NULL, '0', '6', 'SA1680000163608016133947', '', '', '', '1967-04-04', '', '7', '1', '', 1, '', '', '2', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(23, 'EBRAHIM YAHYA MAHBOB', '2009', '1085198743', '1970-02-01', '1970-02-01', '0561896129', '0', '', '', '', '', '', '4300', '11', '16', 'Supporter', '191', '30', '1998-11-04', NULL, '0', '3', 'SA7020000001251688229940', '', '', '', '1970-02-01', '', '6', '1', '', 15, '', '', '2', 3, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(24, 'ABDELRAHMAN ELAMIN ABDELRAHIM', '2048', '2149006047', '1953-01-01', '1953-01-01', '0550929136', 'P03223999', '', '', '', '', '', '2700', '11', '16', 'Supporter', '207', '60', '1999-03-06', NULL, '0', '3', 'SA3420000008112149006047', '', '', '', '1953-01-01', '', '7', '1', '', 16, '', '', '2', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(25, 'ABDULREHIMAN NOUSHAD', '2057', '2121840439', '1970-05-25', '1970-05-25', '0548512688', 'K8728136', '', '', '', '', '', '3050', '11', '17', 'Supporter', '101', '60', '1999-03-17', NULL, '0', '3', 'SA3320000008112121840439', '', '', '', '1970-05-25', '', '7', '1', '', 5, '', '', '3', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(26, 'AWADH ABDULLA A JABIR', '2079', '2085281489', '1977-09-08', '1977-09-08', '0547501743', '1628562', '', '', '', '', '', '3500', '15', '21', 'Supporter', '243', '60', '1999-05-03', NULL, '0', '3', 'SA4820000001251783729940', '', '', '', '1977-09-08', '', '7', '1', '', 8, '', '', '1', 1, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(27, 'MOHAMMED SALI SAVAD', '2181', '2160516908', '1973-05-27', '1973-05-27', '0544882095', 'H3495627', '', '', '', '', '', '4350', '15', '22', 'Supporter', '101', '60', '2000-08-27', NULL, '0', '8', 'sa2130400108081699660011', '', '', '', '1973-05-27', '', '7', '1', '', 17, '', '', '1', 7, '', NULL, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(28, 'SHAIKH MASRUR AHMED IBRAHIM', '2190', '2167000732', '1968-01-01', '1968-01-01', '0534534821', 'S9852986', '', '', '', '', '', '8400', '11', '17', 'Supporter', '101', '30', '2000-10-18', NULL, '0', '3', 'SA2020000001511837409940', '', '', '', '1968-01-01', '', '6', '1', '', 18, '', '', '3', 5, '', NULL, '', '', 'B', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(29, 'MOHD HASSAN MOHD GHONEIM', '2212', '2165771490', '1963-11-29', '1963-11-29', '0549062848', 'A20565726', '', '', '', '', '', '5440', '2', '2', 'Supporter', '64', '30', '2001-03-12', NULL, '0', '3', 'SA2820000001251763759940', '', '', '', '1963-11-29', '', '6', '1', '', 19, '', '', '1', 1, '', NULL, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(30, 'SHAHIDUL ABUL BEPARI', '2226', '2122481241', '1967-11-30', '1967-11-30', '0554466045', 'EB0867637', '', '', '', '', '', '2475', '11', '16', 'Supporter', '18', '60', '2001-03-28', NULL, '0', '6', 'SA1080000648608017733294', '', '', '', '1967-11-30', '', '7', '1', '', 13, '', '', '1', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(31, 'JASHIM UDDIN MOHAMMAD', '2238', '2173448040', '1976-02-01', '1976-02-01', '0546111927', 'BC0701091', '', '', '', '', '', '3300', '11', '17', 'Supporter', '18', '60', '2001-06-08', NULL, '0', '3', 'SA9720000008112173448040', '', '', '', '1976-02-01', '', '7', '1', '', 16, '', '', '3', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(32, 'JEWEL HOSSAIN RAFIQUE HOSSAIN', '2285', '2169448590', '1979-04-04', '1979-04-04', '0551134519', 'EF0966210', '', '', '', '', '', '2450', '1', '1', 'Supporter', '18', '60', '2002-03-23', NULL, '0', '6', 'SA5680000856608016895044', '', '', '', '1979-04-04', '', '7', '1', '', 1, '', '', '2', 4, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(33, 'KAMAL MUSHUR ALI', '2287', '2166322475', '1973-08-05', '1973-08-05', '0560002544', 'EE0159893', '', '', '', '', '', '2100', '11', '17', 'Supporter', '18', '60', '2002-03-19', NULL, '0', '6', 'SA8280000648608016262642', '', '', '', '1973-08-05', '', '7', '1', '', 4, '', '', '3', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(34, 'MUJEEB RAHMAN PICHAN', '2288', '2176421671', '1977-04-25', '1977-04-25', '0547790497', 'J4230493', '', '', '', '', '', '2350', '11', '18', 'Supporter', '101', '60', '2002-03-23', NULL, '0', '6', 'SA1980000644608016548283', '', '', '', '1977-04-25', '', '7', '1', '', 8, '', '', '3', 7, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(35, 'MOHAMMAD JAHANGIR ALIJAN', '2290', '2166401071', '1969-05-03', '1969-05-03', '0544492763', 'EB0509292', '', '', '', '', '', '2400', '11', '16', 'Supporter', '18', '60', '2002-04-10', NULL, '0', '6', 'SA9780000648608016967174', '', '', '', '1969-05-03', '', '7', '1', '', 1, '', '', '2', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(36, 'EESA KUNNAMVALLI', '2316', '2179807496', '1975-11-10', '1975-11-10', '0549973192', 'L3454596', '', '', '', '', '', '2000', '11', '16', 'Supporter', '101', '60', '2002-07-15', NULL, '0', '6', 'SA1280000648608017732288', '', '', '', '1975-11-10', '', '7', '1', '', 6, '', '', '1', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(37, 'EID ABDELKADER ELSAED ELKHALEG', '2337', '2173548773', '1978-01-13', '1978-01-13', '0562233629', 'A12562913', '', '', '', '', '', '3250', '11', '19', 'Supporter', '64', '60', '2002-07-16', NULL, '0', '3', 'SA4420000001802256869940', '', '', '', '1978-01-13', '', '7', '1', '', 1, '', '', '2', 14, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(38, 'SAIDALAVI PULLANI PPURAVAN', '2352', '2102052038', '1960-01-22', '1960-01-22', '0561578874', 'E5372670', '', '', '', '', '', '1700', '4', '4', 'Supporter', '101', '60', '2002-09-15', NULL, '0', '3', 'SA9320000008112102052038', '', '', '', '1960-01-22', '', '7', '1', '', 20, '', '', '2', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(39, 'SALIM MISTAK', '2358', '2014706820', '1976-01-05', '1976-01-05', '0558868105', 'AZ9992563', '', '', '', '', '', '4100', '1', '1', 'Supporter', '166', '30', '2002-10-27', NULL, '0', '3', 'SA5220000001801463249940', '', '', '', '1976-01-05', '', '6', '1', '', 21, '', '', '1', 4, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(40, 'JAMALUDDIN ABUL MIAH', '2361', '2175597331', '1974-01-01', '1974-01-01', '0547219038', 'BH0005499', '', '', '', '', '', '2225', '11', '17', 'Supporter', '18', '60', '2002-12-18', NULL, '0', '6', 'SA1280000859608012147827', '', '', '', '1974-01-01', '', '7', '1', '', 3, '', '', '3', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(41, 'FARUK MIAH RAHAM ALI', '2363', '2161753690', '1975-02-10', '1975-02-10', '0546792741', 'BC0343542', '', '', '', '', '', '2250', '11', '17', 'Supporter', '18', '60', '2002-12-18', NULL, '0', '3', 'SA3420000008112161753690', '', '', '', '1975-02-10', '', '7', '1', '', 22, '', '', '3', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(42, 'ABU BAKKA MEHER ALI', '2364', '2161754094', '1975-06-10', '1975-06-10', '0558739973', 'BC0343523', '', '', '', '', '', '2250', '11', '17', 'Supporter', '18', '60', '2002-11-25', NULL, '0', '3', 'SA8720000008112161754094', '', '', '', '1975-06-10', '', '7', '1', '', 22, '', '', '3', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(43, 'MOHAMMAD AZAD KHAN', '2367', '2121564062', '1965-11-18', '1965-11-18', '0559005548', 'CM9999512', '', '', '', '', '', '2150', '11', '16', 'Supporter', '166', '60', '2002-12-21', NULL, '0', '3', 'SA0420000001251684699940', '', '', '', '1965-11-18', '', '7', '1', '', 23, '', '', '1', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(44, 'AMIR HOSSAIN NOOR HOSSAIN', '2368', '2154158378', '1972-08-04', '1972-08-04', '0560671408', 'BC0692986', '', '', '', '', '', '2850', '11', '17', 'Supporter', '18', '60', '2002-12-18', NULL, '0', '2', 'SA3610000010852561000102', '', '', '', '1972-08-04', '', '7', '1', '', 11, '', '', '3', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(45, 'ABOOBACKER THAMBALAKKODEN', '2372', '2177466022', '1980-05-01', '1980-05-01', '0541314584', 'U0968565', '', '', '', '', '', '2350', '11', '17', 'Supporter', '101', '60', '2003-01-14', NULL, '0', '3', 'SA6220000008112177466022', '', '', '', '1980-05-01', '', '7', '1', '', 3, '', '', '3', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(46, 'ANSARI KAYAMUDDIN', '2376', '2161534298', '1970-02-04', '1970-02-04', '0556618565', 'P0038161', '', '', '', '', '', '2400', '11', '17', 'Supporter', '101', '60', '2003-01-14', NULL, '0', '3', 'SA2220000008112161534298', '', '', '', '1970-02-04', '', '7', '1', '', 22, '', '', '3', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(47, 'SALAH ALI SALAH AL AMRI', '2394', '1026832103', '1972-11-30', '1972-11-30', '0545558382', '0', '', '', '', '', '', '6750', '1', '1', 'Supporter', '191', '30', '2003-03-05', NULL, '0', '3', 'SA6120000001251687439940', '', '', '', '1972-11-30', '', '6', '1', '', 24, '', '', '2', 4, '', 9.75, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(48, 'ALAA HAMDI H ELDIN ELHELALI', '2423', '2192076640', '1972-11-30', '1972-11-30', '0501954926', 'A15701373', '', '', '', '', '', '4350', '11', '17', 'Supporter', '64', '30', '2003-05-12', NULL, '0', '3', 'SA1620000001251686399940', '', '', '', '1972-11-30', '', '6', '1', '', 8, '', '', '1', 5, '', NULL, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(49, 'KABEER KUTTY ASSANARU KUNJU', '2519', '2203336959', '1970-05-03', '1970-05-03', '0549745824', 'K8720846', '', '', '', '', '', '1950', '11', '16', 'Supporter', '101', '60', '2004-03-28', NULL, '0', '2', 'SA9810000010853504000103', '', '', '', '1970-05-03', '', '7', '1', '', 12, '', '', '3', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(50, 'PUSHKARAN MANHAKKALLIL', '2522', '2197777176', '1972-05-30', '1972-05-30', '0547122574', 'J6452031', '', '', '', '', '', '3050', '11', '17', 'Supporter', '101', '60', '2003-11-04', NULL, '0', '3', 'SA6420000001251765949940', '', '', '', '1972-05-30', '', '7', '1', '', 25, '', '', '3', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(51, 'VINOD KARUTHANDAN BALARAMAN', '2530', '2201259112', '1971-05-21', '1971-05-21', '0546354092', 'L0016657', '', '', '', '', '', '2050', '11', '19', 'Supporter', '101', '60', '2004-01-07', NULL, '0', '6', 'SA9580000648608016591529', '', '', '', '1971-05-21', '', '7', '1', '', 13, '', '', '1', 14, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(52, 'KAMRUL HASAN SAHAR ALI', '2539', '2190057675', '1979-06-10', '1979-06-10', '0548251178', 'AE315286', '', '', '', '', '', '2800', '11', '19', 'Supporter', '18', '60', '2004-03-27', NULL, '0', '3', 'SA4820000001800481689940', '', '', '', '1979-06-10', '', '7', '1', '', 26, '', '', '1', 14, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(53, 'ABDUL MUTHALIF MEERA SAHIB', '2541', '2099971760', '1971-05-04', '1971-05-04', '0554274792', 'L4595785', '', '', '', '', '', '2150', '11', '17', 'Supporter', '101', '60', '2004-04-08', NULL, '0', '3', 'SA4020000008112099971760', '', '', '', '1971-05-04', '', '7', '1', '', 27, '', '', '3', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(54, 'SAHABUDDIN MEHER ALI', '2550', '2185612443', '1977-01-01', '1977-01-01', '0547880470', 'EF0973736', '', '', '', '', '', '2100', '11', '17', 'Supporter', '18', '60', '2004-04-15', NULL, '0', '3', 'SA1520000008112185612443', '', '', '', '1977-01-01', '', '7', '1', '', 28, '', '', '3', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(55, 'NASSER AHMED ABDO', '2553', '2053708695', '1982-02-28', '1982-02-28', '0551490630', '9318460', '', '', '', '', '', '6500', '15', '21', 'Supporter', '243', '30', '2003-05-24', NULL, '0', '3', 'SA2320000001251775499940', '', '', '', '1982-02-28', '', '6', '1', '', 17, '', '', '4', 1, '', NULL, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(56, 'HUSSAIN VELLAT', '2554', '2200474456', '1976-02-04', '1976-02-04', '0562145140', 'M4376727', '', '', '', '', '', '2000', '11', '17', 'Supporter', '101', '60', '2004-04-10', NULL, '0', '6', 'SA4580000648608016410845', '', '', '', '1976-02-04', '', '7', '1', '', 15, '', '', '3', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(57, 'ABDUL JALIL SIDDEK ALI', '2565', '2152571788', '1975-05-29', '1975-05-29', '0560755039', 'EF0966231', '', '', '', '', '', '2100', '1', '1', 'Supporter', '18', '60', '2004-05-16', NULL, '0', '2', 'SA3810000066800000708507', '', '', '', '1975-05-29', '', '7', '1', '', 29, '', '', '2', 4, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(58, 'MORSHED ALAM ABDUR ROBMOLLAH', '2572', '2188173187', '1971-04-10', '1971-04-10', '0556206930', 'EK0349135', '', '', '', '', '', '2650', '11', '19', 'Supporter', '18', '60', '2004-05-18', NULL, '0', '3', 'SA5420000001800468959940', '', '', '', '1971-04-10', '', '7', '1', '', 13, '', '', '1', 14, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(59, 'FURQAN SHAH SHER SHAH', '2637', '2206135937', '1973-02-02', '1973-02-02', '0548275019', 'BB9997503', '', '', '', '', '', '2450', '11', '19', 'Supporter', '166', '60', '2004-07-17', NULL, '0', '3', 'SA0220000008112206135937', '', '', '', '1973-02-02', '', '7', '1', '', 1, '', '', '2', 14, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(60, 'MUJEEB ALI HAJITKATH', '2642', '2207767613', '1973-05-15', '1973-05-15', '0545852603', 'M2236955', '', '', '', '', '', '1950', '11', '16', 'Supporter', '101', '60', '2004-07-22', NULL, '0', '2', 'SA3810000011100434478705', '', '', '', '1973-05-15', '', '7', '1', '', 30, '', '', '1', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(61, 'UZZAL HOSSAIN ARSHAD ALI', '2645', '2202440018', '1978-01-10', '1978-01-10', '0558759563', 'BW0458820', '', '', '', '', '', '2300', '11', '16', 'Supporter', '18', '60', '2004-07-24', NULL, '0', '6', 'SA0680000648608017749274', '', '', '', '1978-01-10', '', '7', '1', '', 13, '', '', '1', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(62, 'ABU ZAHER ABDUL SOBHAN MIAH', '2648', '2192168330', '1973-05-17', '1973-05-17', '0557299439', 'EK0311990', '', '', '', '', '', '2350', '11', '17', 'Supporter', '18', '60', '2004-10-04', NULL, '0', '6', 'SA7980000856608012311186', '', '', '', '1973-05-17', '', '7', '1', '', 3, '', '', '3', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(63, 'CELSO ANGCAO BAURILE', '2654', '2215512647', '1978-07-28', '1978-07-28', '0561707591', 'P0734902B', '', '', '', '', '', '3075', '11', '17', 'Supporter', '173', '60', '2005-02-03', NULL, '0', '6', 'SA0280000640608017847134', '', '', '', '1978-07-28', '', '7', '1', '', 27, '', '', '3', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(64, 'HAMDI ELAREF AHMED MAHMOUD', '2688', '2200103105', '1978-01-28', '1978-01-28', '0559268730', 'A18604443', '', '', '', '', '', '6000', '15', '22', 'Supporter', '64', '30', '2004-12-13', NULL, '0', '6', 'SA2180000644608016072268', '', '', '', '1978-01-28', '', '6', '1', '', 17, '', '', '4', 7, '', NULL, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(65, 'ABDULLAH ABDULLAH AHMED', '2690', '2104546326', '1984-12-03', '1984-12-03', '0548130754', '7747670', '', '', '', '', '', '5050', '15', '21', 'Supporter', '243', '30', '2004-02-23', NULL, '0', '3', 'SA8620000001800364529940', '', '', '', '1984-12-03', '', '6', '1', '', 17, '', '', '4', 1, '', NULL, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(66, 'FATHI SAAD GADEL RABB SAIEH', '2709', '2165504123', '1975-12-28', '1975-12-28', '0543724533', 'A16394044', '', '', '', '', '', '2700', '15', '23', 'Supporter', '64', '60', '2005-08-23', NULL, '0', '6', 'SA1780000640608019157334', '', '', '', '1975-12-28', '', '7', '1', '', 31, '', '', '4', 10, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(67, 'ADRIANO JUNIO CHICO', '2745', '2225999537', '1969-06-12', '1969-06-12', '0548627271', 'P0087810B', '', '', '', '', '', '3285', '11', '19', 'Supporter', '173', '60', '2006-05-03', NULL, '0', '2', 'SA4910000066800001109404', '', '', '', '1969-06-12', '', '7', '1', '', 13, '', '', '5', 14, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(68, 'SOFRONIO ROMERO MABALOT JR', '2843', '2241151741', '1973-05-05', '1973-05-05', '0557778002', 'P4350119B', '', '', '', '', '', '3160', '11', '17', 'Supporter', '173', '60', '2007-05-11', NULL, '0', '3', 'SA4220000001800263559940', '', '', '', '1973-05-05', '', '7', '1', '', 16, '', '', '3', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(69, 'CHRISTOPHER GERMO MODELO', '2854', '2241927918', '1975-08-05', '1975-08-05', '0558942401', 'EC0131702', '', '', '', '', '', '2375', '11', '17', 'Supporter', '173', '60', '2007-05-27', NULL, '0', '3', 'SA1720000001800301889940', '', '', '', '1975-08-05', '', '7', '1', '', 32, '', '', '3', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(70, 'NIXON LATUMBO GALLAZA', '2869', '2243096068', '1981-05-23', '1981-05-23', '0545154350', 'EB4474914', '', '', '', '', '', '2950', '11', '17', 'Supporter', '173', '60', '2007-06-23', NULL, '0', '3', 'SA5020000001800304139940', '', '', '', '1981-05-23', '', '7', '1', '', 3, '', '', '3', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(71, 'PABLO DE LEON AGNES JR', '2876', '2243680630', '1967-09-05', '1967-09-05', '0544615177', 'EC8227837', '', '', '', '', '', '2375', '11', '17', 'Supporter', '173', '60', '2007-07-10', NULL, '0', '3', 'SA2620000001800302679940', '', '', '', '1967-09-05', '', '7', '1', '', 3, '', '', '3', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(72, 'KHALED BAKHIT ABDALLA BAKHIT', '2924', '2121976357', '1964-02-11', '1964-02-11', '0508555067', 'P02159078', '', '', '', '', '', '5600', '11', '17', 'Supporter', '207', '30', '2004-07-01', NULL, '0', '2', 'SA2110000012661312000103', '', '', '', '1964-02-11', '', '6', '1', '', 17, '', '', '3', 5, '', NULL, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(73, 'ROLANDO MABANSAG SERUNDO', '2953', '2261825422', '1966-10-21', '1966-10-21', '0550798243', 'EB1936731', '', '', '', '', '', '2850', '11', '17', 'Supporter', '173', '60', '2008-08-14', NULL, '0', '3', 'SA1220000001800466889940', '', '', '', '1966-10-21', '', '7', '1', '', 27, '', '', '5', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(74, 'ALLAN ALAYON DELAPENA', '2961', '2262972629', '1968-06-25', '1968-06-25', '0558625538', 'P6683315A', '', '', '', '', '', '3475', '11', '20', 'Supporter', '173', '60', '2008-09-11', NULL, '0', '3', 'SA6020000001800468839940', '', '', '', '1968-06-25', '', '7', '1', '', 3, '', '', '5', 15, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(75, 'JALAL OSMAN ALI', '2975', '2264312873', '1978-07-29', '1978-07-29', '0547390994', 'RL4006302', '', '', '', '', '', '11150', '11', '19', 'Supporter', '124', '30', '2008-10-08', NULL, '0', '3', 'SA5520000001800478639940', '', '', '', '1978-07-29', '', '6', '1', '', 33, '', '', '2', 14, '', NULL, '', '', 'B', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(76, 'NASER SALEH AHMED ALHARITHY', '3012', '1117339802', '1968-11-30', '1968-11-30', '0543952816', '0', '', '', '', '', '', '7563', '15', '22', 'Supporter', '191', '30', '2009-02-01', NULL, '0', '3', 'SA1080000149608010131425', '', '', '', '1968-11-30', '', '6', '1', '', 24, '', '', '2', 7, '', 9.75, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(77, 'MASHAKEL ABDOU M ABDELRAHMAN', '3013', '2270480375', '1977-05-05', '1977-05-05', '0548123846', 'A16800635', '', '', '', '', '', '2800', '15', '24', 'Supporter', '64', '60', '2009-02-25', NULL, '0', '6', 'SA5080000507608016154062', '', '', '', '1977-05-05', '', '7', '1', '', 31, '', '', '3', 2, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(78, 'ELNAGGAR SAAD OUMRAN BADAWY', '3014', '2270484336', '1979-11-20', '1979-11-20', '0542596934', 'A16241212', '', '', '', '', '', '3050', '15', '21', 'Supporter', '64', '60', '2009-02-25', NULL, '0', '6', 'SA6880000648608016304022', '', '', '', '1979-11-20', '', '7', '1', '', 34, '', '', '3', 1, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(79, 'AHMED SABER AHMED AYTALLAH', '3015', '2270479500', '1986-06-25', '1986-06-25', '0542162871', 'A17488157', '', '', '', '', '', '3550', '15', '25', 'Supporter', '64', '30', '2009-02-25', NULL, '0', '3', 'SA4720000003020608819940', '', '', '', '1986-06-25', '', '6', '1', '', 31, '', '', '3', 8, '', NULL, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(80, 'AHMED ABDELFATTAH MOHAMED GAD', '3018', '2270479187', '1979-08-15', '1979-08-15', '0543959064', 'A12467431', '', '', '', '', '', '2300', '15', '21', 'Supporter', '64', '60', '2009-02-25', NULL, '0', '6', 'SA0980000991608016659444', '', '', '', '1979-08-15', '', '7', '1', '', 35, '', '', '3', 1, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(81, 'NELSON MOLI LLANES', '3025', '2270310473', '1972-09-09', '1972-09-09', '0557469300', 'P5260030A', '', '', '', '', '', '4000', '11', '20', 'Supporter', '173', '60', '2009-03-29', NULL, '0', '3', 'SA8420000001800564389940', '', '', '', '1972-09-09', '', '7', '1', '', 36, '', '', '3', 15, '', NULL, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(82, 'BALRAM GAUTAM SHANTI', '3037', '2271849131', '1982-10-22', '1982-10-22', '0545072618', '8436279', '', '', '', '', '', '1700', '11', '16', 'Supporter', '153', '60', '2009-04-01', NULL, '0', '3', 'SA0920000001800575589940', '', '', '', '1982-10-22', '', '7', '1', '', 28, '', '', '3', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(83, 'ASH BAHADUR RAI JEENA', '3047', '2271633311', '1979-12-23', '1979-12-23', '0555568768', '8892837', '', '', '', '', '', '1900', '11', '17', 'Supporter', '153', '60', '2009-04-09', NULL, '0', '3', 'SA8720000001800565299940', '', '', '', '1979-12-23', '', '7', '1', '', 22, '', '', '3', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(84, 'AHMED ABDELHAY A SOLIMAN', '3061', '2275998009', '1986-08-25', '1986-08-25', '0552592382', 'A27077106', '', '', '', '', '', '5800', '2', '2', 'Supporter', '64', '30', '2009-05-29', NULL, '0', '3', 'SA6520000001800660799940', '', '', '', '1986-08-25', '', '6', '1', '', 19, '', '', '3', 4, '', NULL, '', '', 'B', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(85, 'WALEED ABDULLAH A HAYDARAH', '3062', '2173026598', '1987-09-22', '1987-09-22', '0543766624', '8147203', '', '', '', '', '', '3000', '15', '23', 'Supporter', '243', '60', '2008-09-03', NULL, '0', '3', 'SA6420000001800432869940', '', '', '', '1987-09-22', '', '7', '1', '', 17, '', '', '3', 10, '', NULL, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(86, 'KAMAL RAI RAM BAHADUR', '3074', '2280368925', '1987-09-28', '1987-09-28', '0544434998', '7600962', '', '', '', '', '', '2000', '11', '17', 'Supporter', '153', '60', '2009-09-01', NULL, '0', '3', 'SA3320000001800652709940', '', '', '', '1987-09-28', '', '7', '1', '', 12, '', '', '3', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(87, 'GAMAL AHMED EID ESAIRY', '3089', '2285213142', '1962-11-22', '1962-11-22', '0562613375', 'A16849999', '', '', '', '', '', '2150', '15', '24', 'Supporter', '64', '60', '2010-01-15', NULL, '0', '3', 'SA3920000001800683629940', '', '', '', '1962-11-22', '', '7', '1', '', 12, '', '', '2', 2, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(88, 'IBRAHIM SAADY HAMED ADAM', '3090', '2284877301', '1981-02-03', '1981-02-03', '0546081354', 'A15834118', '', '', '', '', '', '2000', '11', '19', 'Supporter', '64', '60', '2010-01-15', NULL, '0', '3', 'SA9820000001800690209940', '', '', '', '1981-02-03', '', '7', '1', '', 15, '', '', '2', 14, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(89, 'MOHAMED SALAMA ALI AHMED', '3091', '2285367328', '1971-08-10', '1971-08-10', '0562942964', 'A15830016', '', '', '', '', '', '1850', '11', '19', 'Supporter', '64', '60', '2010-01-15', NULL, '0', '3', 'SA5720000001800685209940', '', '', '', '1971-08-10', '', '7', '1', '', 12, '', '', '2', 14, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(90, 'MOHAMED AHMED ALI SAID', '3092', '2285567141', '1974-02-22', '1974-02-22', '0552808369', 'A160057874', '', '', '', '', '', '2600', '11', '19', 'Supporter', '64', '60', '2010-01-13', NULL, '0', '3', 'SA0320000001800683379940', '', '', '', '1974-02-22', '', '7', '1', '', 13, '', '', '2', 14, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(91, 'RODOLFO GEROY CLEMENTE', '3105', '2287603639', '1958-10-31', '1958-10-31', '0556652709', 'P0225938B', '', '', '', '', '', '2850', '11', '16', 'Supporter', '173', '60', '2010-03-23', NULL, '0', '3', 'SA0320000001801154799940', '', '', '', '1958-10-31', '', '7', '1', '', 5, '', '', '2', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(92, 'MANI RAJ RAI ASHA', '3134', '2288669704', '1985-06-23', '1985-06-23', '0545501834', '8302901', '', '', '', '', '', '1750', '11', '17', 'Supporter', '153', '60', '2010-04-26', NULL, '0', '3', 'SA4220000008112288669704', '', '', '', '1985-06-23', '', '7', '1', '', 22, '', '', '2', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(93, 'ABDULVAHAB KALLIGALATH M KUTT', '3140', '2292191018', '1979-01-18', '1979-01-18', '0544983947', 'S4664263', '', '', '', '', '', '2800', '11', '17', 'Supporter', '101', '60', '2010-05-16', NULL, '0', '8', 'SA0930400108084537690010', '', '', '', '1979-01-18', '', '7', '1', '', 16, '', '', '2', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(94, 'BABU KUTTY PAPPACHAN', '3144', '2292109176', '1971-05-27', '1971-05-27', '0549539767', 'L3451794', '', '', '', '', '', '2100', '11', '16', 'Supporter', '101', '60', '2010-05-16', NULL, '0', '3', 'SA6120000008112292109176', '', '', '', '1971-05-27', '', '7', '1', '', 1, '', '', '2', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(95, 'JOMON KURIAN KARTHON THOMAS', '3156', '2292270143', '1985-03-02', '1985-03-02', '0543684123', 'M3848459', '', '', '', '', '', '2600', '11', '17', 'Supporter', '101', '60', '2010-05-23', NULL, '0', '3', 'SA3720000008112292270143', '', '', '', '1985-03-02', '', '7', '1', '', 27, '', '', '2', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(96, 'KANHAIYA SAHATER SHARMA', '3162', '2293409815', '1981-12-09', '1981-12-09', '0560781859', 'P7100339', '', '', '', '', '', '2450', '11', '19', 'Supporter', '101', '60', '2010-05-23', NULL, '0', '3', 'SA0920000008112293409815', '', '', '', '1981-12-09', '', '7', '1', '', 1, '', '', '2', 14, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(97, 'DILIP KUMAR YADAV', '3163', '2293409872', '1986-05-15', '1986-05-15', '0547265116', 'U0576576', '', '', '', '', '', '2450', '11', '19', 'Supporter', '101', '60', '2010-05-23', NULL, '0', '6', 'SA0380000222608016105720', '', '', '', '1986-05-15', '', '7', '1', '', 1, '', '', '2', 14, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(98, 'NIM BAHADUR SUNAR TRIMALA', '3198', '2292532609', '1982-04-15', '1982-04-15', '0556696834', '8112839', '', '', '', '', '', '1600', '1', '1', 'Supporter', '153', '60', '2010-06-10', NULL, '0', '3', 'SA8120000008112292532609', '', '', '', '1982-04-15', '', '7', '1', '', 28, '', '', '2', 11, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(99, 'GHAMDAN AHMED A SHAFFAL', '3205', '2292808579', '1984-05-03', '1984-05-03', '0548582398', '6307966', '', '', '', '', '', '5200', '15', '26', 'Supporter', '243', '30', '2010-06-11', NULL, '0', '3', 'SA9020000001800722379940', '', '', '', '1984-05-03', '', '6', '1', '', 17, '', '', '2', 8, '', NULL, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(100, 'UDAYMAL RAJENDRA SAH', '3209', '2293801177', '1969-05-15', '1969-05-15', '0544977746', 'U0484534', '', '', '', '', '', '2200', '11', '16', 'Supporter', '101', '60', '2010-07-03', NULL, '0', '3', 'SA2720000008112293801177', '', '', '', '1969-05-15', '', '7', '1', '', 1, '', '', '2', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(101, 'SHIRAZ AHMED KHAN', '3223', '2298943966', '1984-05-10', '1984-05-10', '0507893899', 'P6287868', '', '', '', '', '', '4025', '11', '19', 'Supporter', '101', '60', '2010-10-11', NULL, '0', '3', 'SA2520000001510856639940', '', '', '', '1984-05-10', '', '7', '1', '', 37, '', '', '2', 14, '', NULL, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(102, 'SAHUL HAMEED ROWTHER M IKBAL', '3224', '2298881901', '1965-06-19', '1965-06-19', '0560938539', 'J3853610', '', '', '', '', '', '1900', '11', '17', 'Supporter', '101', '60', '2010-10-17', NULL, '0', '3', 'SA1320000008112298881901', '', '', '', '1965-06-19', '', '7', '1', '', 22, '', '', '2', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(103, 'PAULOSE VARGHESE', '3230', '2299495727', '1983-05-02', '1983-05-02', '0561562126', 'M8661105', '', '', '', '', '', '2650', '11', '17', 'Supporter', '101', '60', '2010-10-26', NULL, '0', '3', 'SA3420000008112299495727', '', '', '', '1983-05-02', '', '7', '1', '', 16, '', '', '2', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(104, 'VASUDEVAN VIJESH PADIYIL', '3231', '2299495560', '1973-12-07', '1973-12-07', '0558334982', 'M6170568', '', '', '', '', '', '3500', '1', '1', 'Supporter', '101', '60', '2010-10-26', NULL, '0', '3', 'SA8320000001510853539940', '', '', '', '1973-12-07', '', '7', '1', '', 2, '', '', '2', 4, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(105, 'SHAKEEL AHMED ASHIQ HUSSAIN', '3233', '2298253457', '1967-09-07', '1967-09-07', '0547205377', 'LQ1151852', '', '', '', '', '', '2700', '11', '16', 'Supporter', '166', '60', '2010-11-14', NULL, '0', '3', 'SA8220000008112298253457', '', '', '', '1967-09-07', '', '7', '1', '', 13, '', '', '2', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(106, 'OMAR ATTA ALLA MOHAMED KHALIL', '3249', '2299231627', '1989-01-15', '1989-01-15', '0548353542', '5254833', '', '', '', '', '', '2450', '11', '20', 'Supporter', '64', '60', '2010-12-24', NULL, '0', '6', 'SA0980000648608017993369', '', '', '', '1989-01-15', '', '7', '1', '', 3, '', '', '6', 15, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(107, 'MOHAMED ABOUELWAFFA ZAKY RABIE', '3250', '2299235784', '1982-11-11', '1982-11-11', '0544549290', 'A32935251', '', '', '', '', '', '2000', '11', '20', 'Supporter', '64', '60', '2010-12-24', NULL, '0', '6', 'SA9780000102608016659242', '', '', '', '1982-11-11', '', '7', '1', '', 3, '', '', '6', 15, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(108, 'MIGAHED ABOUDEIF ABBADY MOHD', '3252', '2299233532', '1973-11-19', '1973-11-19', '0554348984', 'A15665370', '', '', '', '', '', '2200', '15', '21', 'Supporter', '64', '60', '2010-12-24', NULL, '0', '3', 'SA4320000001800773759940', '', '', '', '1973-11-19', '', '7', '1', '', 35, '', '', '6', 1, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(109, 'SAHRY MOHAMED MAHMOUD MOHAMED', '3254', '2299656005', '1986-11-08', '1986-11-08', '0005594201', '5254377', '', '', '', '', '', '1900', '11', '20', 'Supporter', '64', '60', '2010-12-24', NULL, '0', '6', 'SA1180000648608017991587', '', '', '', '1986-11-08', '', '7', '1', '', 3, '', '', '6', 15, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(110, 'OMAR MOHAMED ALI MOHAMED', '3255', '2299232849', '1989-04-01', '1989-04-01', '0550128708', '5254383', '', '', '', '', '', '1950', '11', '20', 'Supporter', '64', '60', '2010-12-24', NULL, '0', '6', 'SA9780000648608017991397', '', '', '', '1989-04-01', '', '7', '1', '', 12, '', '', '6', 15, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(111, 'HAMED SAADY HAMED ADAM', '3256', '2299234746', '1977-09-24', '1977-09-24', '0549555289', 'A17051810', '', '', '', '', '', '2600', '15', '27', 'Supporter', '64', '60', '2010-12-24', NULL, '0', '6', 'sa6080000996608016134864', '', '', '', '1977-09-24', '', '7', '1', '', 8, '', '', '6', 7, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(112, 'HUSSEIN MAHMOUD HUSSEIN BADRAN', '3263', '2301169922', '1988-01-18', '1988-01-18', '0560585197', 'A15971044', '', '', '', '', '', '2000', '15', '22', 'Supporter', '64', '60', '2011-01-13', NULL, '0', '6', 'SA5580000644608016508253', '', '', '', '1988-01-18', '', '7', '1', '', 34, '', '', '6', 7, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(113, 'VENUS KOLANGARA KUNJUKUNJU', '3265', '2307173209', '1981-01-25', '1981-01-25', '0556905799', 'P1953508', '', '', '', '', '', '2600', '11', '20', 'Supporter', '101', '60', '2011-01-26', NULL, '0', '3', 'SA2720000008112307173209', '', '', '', '1981-01-25', '', '7', '1', '', 3, '', '', '2', 15, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(114, 'MANIKANDAN CHANDRAN', '3280', '2308680830', '1972-05-15', '1972-05-15', '0551218062', 'S9848566', '', '', '', '', '', '2550', '11', '17', 'Supporter', '101', '60', '2011-04-06', NULL, '0', '3', 'SA1920000008112308680830', '', '', '', '1972-05-15', '', '7', '1', '', 27, '', '', '2', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(115, 'ADIL MOHAMED SALIH MOHAMED', '3286', '2159446802', '1971-08-01', '1971-08-01', '0555957957', 'P01362861', '', '', '', '', '', '3375', '1', '1', 'Supporter', '207', '30', '2010-05-20', NULL, '0', '3', 'SA7120000006131672849940', '', '', '', '1971-08-01', '', '6', '1', '', 38, '', '', '2', 4, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(116, 'KAMAL NASER EID ABED HUSSEIN', '3294', '2307683322', '1976-09-17', '1976-09-17', '0542257934', 'N831001', '', '', '', '', '', '16000', '11', '20', 'Supporter', '111', '30', '2011-06-30', NULL, '0', '3', 'SA0520000001361325719940', '', '', '', '1976-09-17', '', '6', '1', '', 39, '', '', '5', 15, '', NULL, '', '', 'B', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(117, 'SHIV SHANKAR SAH SHIV', '3321', '2316309612', '1988-04-02', '1988-04-02', '0554232993', '5624408', '', '', '', '', '', '1700', '11', '17', 'Supporter', '153', '60', '2011-11-15', NULL, '0', '6', 'SA4680000102608016674613', '', '', '', '1988-04-02', '', '7', '1', '', 40, '', '', '5', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(118, 'RAM SOGARATH THAKUR BHUMIHAR', '3325', '2316309372', '1981-11-30', '1981-11-30', '0552636878', '8436293', '', '', '', '', '', '1550', '4', '4', 'Supporter', '153', '60', '2011-11-20', NULL, '0', '6', 'SA9080000106608016480397', '', '', '', '1981-11-30', '', '7', '1', '', 20, '', '', '5', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(119, 'SURENDRA BARAILI DHAN RAJ', '3329', '2317309488', '1988-11-17', '1988-11-17', '0545912991', '5576906', '', '', '', '', '', '1600', '1', '1', 'Supporter', '153', '60', '2011-12-03', NULL, '0', '3', 'SA7720000008112317309488', '', '', '', '1988-11-17', '', '7', '1', '', 15, '', '', '5', 4, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(120, 'RAM CHANDRA MUKHIYA SHIV', '3330', '2317310197', '1988-06-02', '1988-06-02', '0556941743', '12304563', '', '', '', '', '', '1600', '4', '4', 'Supporter', '153', '60', '2011-12-03', NULL, '0', '6', 'SA4280000640608013539032', '', '', '', '1988-06-02', '', '7', '1', '', 41, '', '', '5', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(121, 'AHMED MOHAMED AHMED MOUSSA', '3332', '2316986310', '1981-08-15', '1981-08-15', '0546245444', 'AO5299265', '', '', '', '', '', '13750', '1', '1', 'Supporter', '64', '30', '2011-12-07', NULL, '0', '3', 'SA0320000001800996689940', '', '', '', '1981-08-15', '', '6', '1', '', 42, '', '', '6', 4, '', NULL, '', '', 'A', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(122, 'JITHIN THAIPARAMBIL JAMES', '3370', '2318099567', '1987-04-07', '1987-04-07', '0543290915', 'U0483455', '', '', '', '', '', '2650', '11', '17', 'Supporter', '101', '60', '2012-01-02', NULL, '0', '6', 'SA6080000648608016970459', '', '', '', '1987-04-07', '', '7', '1', '', 43, '', '', '5', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(123, 'MOHAMMED RAHIL KHAN', '3384', '2320607506', '1989-04-28', '1989-04-28', '0554294295', 'H4921127', '', '', '', '', '', '2300', '11', '19', 'Supporter', '101', '60', '2012-01-25', NULL, '0', '2', 'SA4210000011100133100409', '', '', '', '1989-04-28', '', '7', '1', '', 31, '', '', '5', 14, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(124, 'JOSHIES KATTIL JOHN', '3386', '2319494346', '1986-09-24', '1986-09-24', '0504615750', 'H0836368', '', '', '', '', '', '3200', '11', '17', 'Supporter', '101', '60', '2012-01-29', NULL, '0', '2', 'SA9710000011100050947209', '', '', '', '1986-09-24', '', '7', '1', '', 10, '', '', '5', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(125, 'LEANDRO BUNAG SANTIAGO', '3431', '2293543845', '1962-01-02', '1962-01-02', '0562017534', 'P6788174B', '', '', 'lbs@almutlak.com', '', '', '5800', '5', '5', 'Supporter', '173', '30', '2012-05-02', NULL, '0', '2', 'SA9010000010863601000101', '', '', '', '1962-01-02', '', '6', '1', '', 44, '', '', '3', 4, '', NULL, '', '', 'B', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58');
INSERT INTO `employees` (`id`, `name`, `emp_id`, `iqama`, `iqama_exp`, `iqama_exp_g`, `mobile`, `passport_number`, `passport_exp`, `email`, `c_email`, `emg_mobile`, `emg_name`, `salary`, `dept`, `sectin_nme`, `emptype`, `country`, `vacation_days`, `joining_date`, `contract_end_date`, `fly`, `bank_name`, `iban`, `note`, `ter_note`, `ter_date`, `dob`, `dob_h`, `vac_period`, `sex`, `blood_type`, `actual_job`, `mar_status`, `t_shirt_size`, `emp_sup_type`, `comp_no`, `address`, `gosi`, `insurance_no`, `insurance_exp`, `insurance_class`, `payment_type`, `probation`, `avatar`, `status`, `created_at`, `updated_at`) VALUES
(126, 'SIVAPRAKASAN UNNICHEKKAN', '3497', '2334586811', '1966-05-29', '1966-05-29', '0541455261', 'T9666312', '', '', '', '', '', '5900', '11', '20', 'Supporter', '101', '30', '2012-07-13', NULL, '0', '3', 'SA9320000001802252979940', '', '', '', '1966-05-29', '', '6', '1', '', 45, '', '', '5', 15, '', NULL, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(127, 'MAHMOUD ATTITALLA Y FARGHALY', '3532', '2247155134', '1984-01-01', '1984-01-01', '0560259010', 'A17320590', '', '', '', '', '', '2500', '15', '28', 'Supporter', '64', '30', '2012-05-05', NULL, '0', '2', 'SA4510000053600001211307', '', '', '', '1984-01-01', '', '6', '1', '', 31, '', '', '1', 8, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(128, 'EHAB ABDELHAMID E I ELKABANY', '3586', '2341329825', '1968-11-01', '1968-11-01', '0591078061', 'A23712846', '', '', '', '', '', '4100', '11', '17', 'Supporter', '64', '30', '2013-01-20', NULL, '0', '3', 'SA3820000001801146339940', '', '', '', '1968-11-01', '', '6', '1', '', 17, '', '', '2', 5, '', NULL, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(129, 'SYED NAVEED SHAH SYED', '3602', '2294904970', '1989-02-04', '1989-02-04', '0561881803', 'PE5144862', '', '', '', '', '', '2550', '15', '25', 'Supporter', '166', '60', '2012-12-09', NULL, '0', '6', 'SA8080000513608010242527', '', '', '', '1989-02-04', '', '7', '1', '', 46, '', '', '1', 8, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(130, 'FAHD MOHAMED MUTLAK ALMUTLAK', '3618', '1022463440', '1968-09-23', '1968-09-23', '0540436661', '0', '', '', '', '', '', '12000', '1', '1', 'Supporter', '191', '30', '2013-02-01', NULL, '0', '3', 'SA9020000001801145299940', '', '', '', '1968-09-23', '', '6', '1', '', 47, '', '', '1', 11, '', 9.75, '', '', 'B', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(131, 'KALIM ULLAH ZAKIR ULLAH', '3622', '2339915874', '1969-03-15', '1969-03-15', '0546432139', 'BE2743513', '', '', '', '', '', '1800', '15', '23', 'Supporter', '166', '42', '2013-02-11', NULL, '0', '6', 'SA5920000001803288649940', '', '', '', '1969-03-15', '', '5', '1', '', 34, '', '', '7', 10, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(132, 'AHMED THARWAT SAAD M ABDALLA', '3627', '2342981053', '1987-12-15', '1987-12-15', '0547256188', 'AO7690763', '', '', '', '', '', '5100', '11', '18', 'Supporter', '64', '30', '2013-02-14', NULL, '0', '3', 'SA2520000002040789949940', '', '', '', '1987-12-15', '', '6', '1', '', 17, '', '', '5', 7, '', NULL, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(133, 'WAQAS GHANI ISRAR', '3665', '2184891493', '1990-01-03', '1990-01-03', '0552247368', 'AW4118612', '', '', '', '', '', '4400', '11', '16', 'Supporter', '166', '60', '2013-10-07', NULL, '0', '3', 'SA7420000001801337679940', '', '', '', '1990-01-03', '', '7', '1', '', 48, '', '', '6', 3, '', NULL, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(134, 'MEDHAT RAMADAN MOHAMED SAID', '3705', '2352398669', '1983-11-18', '1983-11-18', '0569311952', 'A27269729', '', '', '', '', '', '2800', '15', '24', 'Supporter', '64', '42', '2013-09-02', NULL, '0', '3', 'SA5320000001511961879940', '', '', '', '1983-11-18', '', '5', '1', '', 31, '', '', '2', 2, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(135, 'IMTIAZ ALI MALAL KHAN', '3724', '2355282621', '1981-01-01', '1981-01-01', '0553582022', 'SD1790083', '', '', '', '', '', '2850', '11', '16', 'Supporter', '166', '60', '2013-09-26', NULL, '0', '3', 'SA6320000008112355282621', '', '', '', '1981-01-01', '', '7', '1', '', 25, '', '', '3', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(136, 'MUHAMMAD RIAZ MIAN DAD', '3726', '2355282407', '1976-02-03', '1976-02-03', '0545450410', 'AK9914874', '', '', '', '', '', '1850', '15', '22', 'Supporter', '166', '60', '2013-09-26', NULL, '0', '6', 'SA8580000539608019183664', '', '', '', '1976-02-03', '', '7', '1', '', 15, '', '', '3', 7, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(137, 'MUHAMMAD SAEED KHAN SAID', '3727', '2355281540', '1970-04-22', '1970-04-22', '0545639704', 'DS5972522', '', '', '', '', '', '1600', '1', '1', 'Supporter', '166', '60', '2013-09-26', NULL, '0', '3', 'SA5320000008112355281540', '', '', '', '1970-04-22', '', '7', '1', '', 15, '', '', '3', 11, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(138, 'ABBAS ISRAR ISRAR AHMED', '3728', '2356662144', '1992-03-10', '1992-03-10', '0577706253', 'BZ1823812', '', '', '', '', '', '2050', '15', '22', 'Supporter', '166', '60', '2013-09-26', NULL, '0', '6', 'SA0580000215608016094782', '', '', '', '1992-03-10', '', '7', '1', '', 35, '', '', '3', 7, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(139, 'ZAR ALI KHAN MUZAMIL KHAN', '3729', '2355282274', '1991-04-07', '1991-04-07', '0559986831', 'XB4113713', '', '', '', '', '', '1850', '15', '28', 'Supporter', '166', '60', '2013-09-26', NULL, '0', '6', 'SA0380000104608016731353', '', '', '', '1991-04-07', '', '7', '1', '', 35, '', '', '3', 8, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(140, 'TYAGARAJAN GOVINDARAJ', '3742', '2359751365', '1972-06-05', '1972-06-05', '0559518286', 'N4015574', '', '', '', '', '', '2050', '11', '16', 'Supporter', '101', '60', '2013-11-18', NULL, '0', '3', 'SA4420000008112359751365', '', '', '', '1972-06-05', '', '7', '1', '', 15, '', '', '3', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(141, 'BALAKRISHNA PILLAI P THANK', '3767', '2359751142', '1959-02-24', '1959-02-24', '0542064474', 'J8416223', '', '', '', '', '', '2500', '11', '17', 'Supporter', '101', '60', '2013-11-24', NULL, '0', '3', 'SA5120000008112359751142', '', '', '', '1959-02-24', '', '7', '1', '', 27, '', '', '3', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(142, 'JITENDRA SHARMA R SHARMA', '3768', '2360050724', '1988-02-10', '1988-02-10', '0554743080', 'L2392656', '', '', '', '', '', '2200', '11', '19', 'Supporter', '101', '60', '2013-11-24', NULL, '0', '3', 'SA7020000008112360050724', '', '', '', '1988-02-10', '', '7', '1', '', 29, '', '', '3', 14, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(143, 'SHAIKH ASHRAF SHAIKH KHURSHID', '3787', '2360047902', '1977-04-10', '1977-04-10', '0561476087', 'K6810561', '', '', '', '', '', '2150', '11', '19', 'Supporter', '101', '60', '2013-12-05', NULL, '0', '3', 'SA2220000008112360047902', '', '', '', '1977-04-10', '', '7', '1', '', 29, '', '', '3', 14, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(144, 'RONALD PAINGAS CABAGAY', '3790', '2359462468', '1970-03-15', '1970-03-15', '0547191777', 'P7374751A', '', '', '', '', '', '2450', '11', '16', 'Supporter', '173', '60', '2013-12-12', NULL, '0', '3', 'SA0820000008112359462468', '', '', '', '1970-03-15', '', '7', '1', '', 29, '', '', '3', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(145, 'SYED NAZIR SYED MUBARAK', '3795', '2359391931', '1988-09-03', '1988-09-03', '0556198936', 'BL9822133', '', '', '', '', '', '2000', '11', '16', 'Supporter', '166', '60', '2013-12-13', NULL, '0', '3', 'SA0920000008112359391931', '', '', '', '1988-09-03', '', '7', '1', '', 1, '', '', '3', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(146, 'ZEHEER HUSSAIN MUHAMMAD PARYA', '3798', '2359392111', '1982-10-01', '1982-10-01', '0553426006', 'YA6895351', '', '', '', '', '', '2100', '11', '16', 'Supporter', '166', '60', '2013-12-13', NULL, '0', '3', 'SA9620000008112359392111', '', '', '', '1982-10-01', '', '7', '1', '', 1, '', '', '3', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(147, 'NIAZ MUHAMMAD RAZA MUHAMMAD', '3801', '2360354001', '1986-01-01', '1986-01-01', '0558375643', 'AT0154173', '', '', '', '', '', '2150', '11', '16', 'Supporter', '166', '60', '2013-12-13', NULL, '0', '3', 'SA4020000008112360354001', '', '', '', '1986-01-01', '', '7', '1', '', 1, '', '', '3', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(148, 'JAHANZEB OURANGZEB', '3802', '2360353425', '1982-02-01', '1982-02-01', '0547877607', 'BC8109552', '', '', '', '', '', '2500', '11', '16', 'Supporter', '166', '60', '2013-12-13', NULL, '0', '3', 'SA7220000008112360353425', '', '', '', '1982-02-01', '', '7', '1', '', 14, '', '', '3', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(149, 'WAHEED IQBAL FAZAL KARIM', '3803', '2360728907', '1974-01-01', '1974-01-01', '0543697732', 'FF1333453', '', '', '', '', '', '2270', '11', '19', 'Supporter', '166', '60', '2013-12-13', NULL, '0', '3', 'SA1320000008112360728907', '', '', '', '1974-01-01', '', '7', '1', '', 1, '', '', '3', 14, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(150, 'FREDENSON JAPOLE PALALLOS', '3810', '2360049049', '1982-10-13', '1982-10-13', '0546983366', 'P7196345A', '', '', '', '', '', '2400', '11', '16', 'Supporter', '173', '60', '2013-12-19', NULL, '0', '3', 'SA9320000008112360049049', '', '', '', '1982-10-13', '', '7', '1', '', 38, '', '', '3', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(151, 'MUHAMMAD NASEEM JAVED M AZEEM', '3812', '2360050088', '1975-10-15', '1975-10-15', '0544988932', 'CK8674512', '', '', '', '', '', '2350', '11', '16', 'Supporter', '166', '60', '2013-12-31', NULL, '0', '3', 'SA7320000008112360050088', '', '', '', '1975-10-15', '', '7', '1', '', 13, '', '', '3', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(152, 'FAISAL MEHMOOD ELLAHI DAD', '3815', '2360046748', '1988-02-25', '1988-02-25', '0548837930', 'UU0159241', '', '', '', '', '', '2050', '11', '16', 'Supporter', '166', '60', '2013-12-31', NULL, '0', '3', 'SA4320000008112360046748', '', '', '', '1988-02-25', '', '7', '1', '', 13, '', '', '3', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(153, 'MAMDOUH SAID IBRAHIM GALAL', '3836', '2361360676', '1991-09-02', '1991-09-02', '0558050523', 'A10884002', '', '', '', '', '', '1800', '11', '20', 'Supporter', '64', '60', '2014-01-29', NULL, '0', '6', 'SA5980000222608016102578', '', '', '', '1991-09-02', '', '7', '1', '', 8, '', '', '2', 15, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(154, 'MAHMOUD GAMAL MOHD SAID', '3845', '2361360882', '1991-09-03', '1991-09-03', '0548781479', 'A27312389', '', '', '', '', '', '1850', '15', '24', 'Supporter', '64', '42', '2014-02-09', NULL, '0', '3', 'SA2320000009325747039940', '', '', '', '1991-09-03', '', '5', '1', '', 8, '', '', '2', 2, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(155, 'ASIF SALAHUDDIN BEG', '3855', '2361925973', '1987-06-05', '1987-06-05', '0542084271', 'K2965714', '', '', '', '', '', '1950', '11', '17', 'Supporter', '101', '60', '2014-02-12', NULL, '0', '6', 'SA2080000163608016106133', '', '', '', '1987-06-05', '', '7', '1', '', 3, '', '', '3', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(156, 'MOHAMED ISMAIL SHAIKH', '3857', '2362106532', '1971-08-07', '1971-08-07', '0561172474', 'T9559288', '', '', '', '', '', '1900', '11', '17', 'Supporter', '101', '60', '2014-02-12', NULL, '0', '3', 'SA4920000008112362106532', '', '', '', '1971-08-07', '', '7', '1', '', 22, '', '', '3', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(157, 'IBRAHIM MOHAMMED ALI ALHUSAYNI', '3862', '2153812553', '1988-01-01', '1988-01-01', '0594949474', '0', '', '', '', '', '', '4500', '11', '16', 'Supporter', '243', '30', '2014-02-24', NULL, '0', '3', 'SA8420000001802253159940', '', '', '', '1988-01-01', '', '6', '1', '', 17, '', '', '1', 3, '', NULL, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(158, 'JARAN SURINKON', '3866', '2008825982', '1967-08-30', '1967-08-30', '0544393883', 'AA4012400', '', '', '', '', '', '4200', '11', '16', 'Supporter', '217', '60', '2013-01-27', NULL, '0', '3', 'SA4120000001801645829940', '', '', '', '1967-08-30', '', '7', '1', '', 38, '', '', '2', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(159, 'MEHAJA ALI YAHYA MASHI', '3876', '1071250938', '1978-07-01', '1978-07-01', '0551731307', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '30', '2014-03-08', NULL, '0', '3', 'SA4320000001511315089940', '', '', '', '1978-07-01', '', '6', '2', '', 15, '', '', '5', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(160, 'BIR BAHADUR YADAV', '3877', '2364524781', '1979-02-04', '1979-02-04', '0544796810', 'L3127965', '', '', '', '', '', '2000', '11', '17', 'Supporter', '101', '60', '2014-03-12', NULL, '0', '3', 'SA6320000008112364524781', '', '', '', '1979-02-04', '', '7', '1', '', 3, '', '', '3', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(161, 'HANEEFA MOHAMMED', '3897', '2116578267', '1963-09-04', '1963-09-04', '0542927810', 'L8619113', '', '', '', '', '', '2500', '15', '22', 'Supporter', '101', '60', '2013-07-06', NULL, '0', '6', 'SA8380000640608015152859', '', '', '', '1963-09-04', '', '7', '1', '', 31, '', '', '3', 7, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(162, 'ABID HUSSAIN MUHD ASLAM', '3899', '2364229464', '1976-01-01', '1976-01-01', '0552706332', 'AA1604553', '', '', '', '', '', '2550', '11', '17', 'Supporter', '166', '60', '2014-03-26', NULL, '0', '3', 'SA2820000008112364229464', '', '', '', '1976-01-01', '', '7', '1', '', 49, '', '', '3', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(163, 'MAHER THABET AL JABARI', '3928', '2006634469', '1966-07-01', '1966-07-01', '0505618108', 'P412574', '', 'aneesmug2007@hotmail.com', '', '', '', '38750', '10', '10', 'Manager', '111', '30', '2014-05-15', NULL, '0', '3', 'SA3920000001800077379940', '', '', '', '1966-07-01', '', '6', '1', '', 50, '', '', '1', 4, '', NULL, '', '', 'A', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(164, 'MOHAMMAD WASIM AMIN UDDIN', '3929', '2371344488', '1974-12-30', '1974-12-30', '0552618967', 'S4653413', '', '', '', '', '', '2300', '11', '16', 'Supporter', '101', '60', '2014-05-15', NULL, '0', '3', 'SA6720000008112371344488', '', '', '', '1974-12-30', '', '7', '1', '', 13, '', '', '3', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(165, 'ALRASHID YOUSIF ELTAHIR AHMED', '3935', '2149994606', '1967-01-01', '1967-01-01', '0530512003', 'P06165200', '', '', '', '', '', '4800', '11', '17', 'Supporter', '207', '30', '2013-11-15', NULL, '0', '3', 'SA2320000001511838319940', '', '', '', '1967-01-01', '', '6', '1', '', 48, '', '', '1', 5, '', NULL, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(166, 'ZAHRAH ABDU MOHD ALJAUNY', '3952', '1011150057', '1978-08-03', '1978-08-03', '0548802298', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '30', '2014-06-17', NULL, '0', '3', 'SA7020000001650846139940', '', '', '', '1978-08-03', '', '6', '2', '', 20, '', '', '3', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(167, 'KHIRISHY YOUSSEF FAREKALLY', '3987', '2078715071', '1967-07-01', '1967-07-01', '0550827786', 'A16431081', '', '', '', '', '', '3600', '15', '28', 'Supporter', '64', '30', '2014-07-12', NULL, '0', '3', 'SA3620000003420394539940', '', '', '', '1967-07-01', '', '6', '1', '', 17, '', '', '1', 8, '', NULL, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(168, 'HAIFA MASHUD MAZEN ALERYANI', '4059', '1052287818', '1968-11-30', '1968-11-30', '0543761626', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '30', '2014-10-21', NULL, '0', '3', 'SA0420000001363050399940', '', '', '', '1968-11-30', '', '6', '2', '', 15, '', '', '5', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(169, 'ABDELHAMIED ALI HAMED AHMED', '4119', '2152359184', '1962-07-18', '1962-07-18', '0552512289', 'P02707418', '', '', '', '', '', '14000', '15', '21', 'Supporter', '207', '30', '2015-02-10', NULL, '0', '3', 'SA5620000001800725969940', '', '', '', '1962-07-18', '', '6', '1', '', 51, '', '', '1', 1, '', NULL, '', '', 'B', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(170, 'GAMAL ABDELRAHMAN ABDELRAHMAN', '4120', '2103034787', '1968-10-06', '1968-10-06', '0500575208', 'A27257863', '', 'aneesmug2007@yahoo.com', '', '', '', '9600', '2', '2', 'Manager', '64', '30', '2015-02-15', NULL, '0', '3', 'SA9120000001800748549940', '', '', '', '1968-10-06', '', '6', '1', '', 52, '', '', '2', 3, '', NULL, '', '', 'B', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(171, 'JAIME JUCAR ABAT', '4124', '2030475541', '1955-01-10', '1955-01-10', '0562901598', 'P7180803B', '', '', '', '', '', '10600', '11', '16', 'Supporter', '173', '30', '2015-02-10', NULL, '0', '3', 'SA9520000001802272339940', '', '', '', '1955-01-10', '', '6', '1', '', 53, '', '', '2', 3, '', NULL, '', '', 'B', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(172, 'ISSA TAMOTOMA', '4133', '2101045827', '1967-07-10', '1967-07-10', '0548490599', 'N009097123', '', '', '', '', '', '13150', '11', '17', 'Supporter', '213', '30', '2015-02-15', NULL, '0', '3', 'SA7820000001511838189940', '', '', '', '1967-07-10', '', '6', '1', '', 54, '', '', '3', 5, '', NULL, '', '', 'B', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(173, 'ALI MOHAMED ALI NOUR', '4134', '2069753438', '1962-03-10', '1962-03-10', '0505699152', 'A29072263', '', '', '', '', '', '7600', '15', '22', 'Supporter', '64', '30', '2015-02-15', NULL, '0', '3', 'SA8520000002040682049940', '', '', '', '1962-03-10', '', '6', '1', '', 19, '', '', '1', 7, '', NULL, '', '', 'B', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(174, 'HAITHAM SALAMA AL SHAIR', '4136', '2014989806', '1976-05-31', '1976-05-31', '0500614658', 'POO249128', '', '', '', '', '', '9600', '15', '23', 'Supporter', '168', '30', '2015-02-15', NULL, '0', '3', 'SA3120000001801934549941', '', '', '', '1976-05-31', '', '6', '1', '', 33, '', '', '4', 10, '', NULL, '', '', 'B', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(175, 'YOUSSEF HASSAN MUSTAFA', '4149', '2032502128', '1978-01-30', '1978-01-30', '0558022352', 'P00112476', '', '', '', '', '', '5500', '11', '17', 'Supporter', '168', '30', '2015-03-01', NULL, '0', '2', 'SA1810000010851822000106', '', '', '', '1978-01-30', '', '6', '1', '', 17, '', '', '3', 5, '', NULL, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 0, NULL, '2025-07-10 11:30:58'),
(176, 'SAMEH METWALI ABDULHAMID', '4150', '2108852423', '1391-07-15', '1971-09-06', '0580707574', 'A09915280', '', '', '', '', '', '7500', '2', '2', 'Supporter', '64', '30', '2015-03-01', NULL, '0', '6', 'SA4380000113608010296949', '', '', '', '1971-09-06', '1391-07-15', '6', '1', '', 19, '', '', '3', 5, '', NULL, '', '', 'B', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(177, 'ESSAM TAHA SAID AHMED', '4157', '2382651541', '1988-08-21', '1988-08-21', '0551275398', 'A13472850', '', '', '', '', '', '1950', '11', '19', 'Supporter', '64', '60', '2015-02-14', NULL, '0', '6', 'SA6480000163608016135314', '', '', '', '1988-08-21', '', '7', '1', '', 15, '', '', '2', 14, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(178, 'MEGAHED ABDOU ABDELRAHMAN', '4172', '2382651723', '1980-02-26', '1980-02-26', '0563072444', 'A13142143', '', '', '', '', '', '2050', '15', '21', 'Supporter', '64', '42', '2015-03-05', NULL, '0', '6', 'SA3280000640608017080876', '', '', '', '1980-02-26', '', '5', '1', '', 15, '', '', '2', 1, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(179, 'BADAWY AHMED BEKHIT AHMED', '4195', '2384599920', '1984-05-26', '1984-05-26', '0546269192', 'A12450921', '', '', '', '', '', '1400', '1', '1', 'Supporter', '64', '60', '2015-04-06', NULL, '0', '3', 'SA3520000008112384599920', '', '', '', '1984-05-26', '', '7', '1', '', 12, '', '', '2', 11, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(180, 'ABDELELAH ELAREF AHMED MAHMOUD', '4215', '2076519673', '1968-05-06', '1968-05-06', '0507748299', 'A29286342', '', '', '', '', '', '4100', '15', '22', 'Supporter', '64', '30', '2015-05-14', NULL, '0', '3', 'SA3920000002040683939940', '', '', '', '1968-05-06', '', '6', '1', '', 9, '', '', '1', 7, '', NULL, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(181, 'ALY KAMAL ELSAYED ELTANAHY', '4220', '2385993783', '1991-08-06', '1991-08-06', '0547319996', 'A14737367', '', '', '', '', '', '2600', '1', '1', 'Supporter', '64', '60', '2015-05-01', NULL, '0', '3', 'SA1820000009324551129940', '', '', '', '1991-08-06', '', '7', '1', '', 1, '', '', '2', 4, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(182, 'EYAD KHALID MOHD KHALIL', '4273', '2196437186', '1978-08-26', '1978-08-26', '0549028869', '268561', '', '', '', '', '', '6800', '15', '28', 'Supporter', '64', '30', '2015-08-15', NULL, '0', '3', 'SA1520000002040681509940', '', '', '', '1978-08-26', '', '6', '1', '', 19, '', '', '1', 8, '', NULL, '', '', 'B', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(183, 'CHENTHIL KUMAR NAGAMONI', '4283', '2391830243', '1989-07-05', '1989-07-05', '0507870147', 'M8562116', '', '', '', '', '', '2550', '15', '22', 'Supporter', '101', '42', '2015-07-07', NULL, '0', '6', 'SA4180000640608015229178', '', '', '', '1989-07-05', '', '5', '1', '', 35, '', '', '2', 7, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(184, 'SALMAN KHAN BASHEER AHMED KHA', '4284', '2390831952', '1992-07-20', '1992-07-20', '0542324443', 'M6673925', '', '', '', '', '', '2050', '11', '17', 'Supporter', '101', '60', '2015-07-09', NULL, '0', '3', 'SA0420000008112390831952', '', '', '', '1992-07-20', '', '7', '1', '', 3, '', '', '2', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(185, 'HAISAM KAT AKRAM', '4288', '2162008482', '1960-01-01', '1960-01-01', '0558820803', 'N012396704', '', '', '', '', '', '14950', '1', '1', 'Supporter', '213', '30', '2015-07-15', NULL, '0', '3', 'SA3820000001800410109940', '', '', '', '1960-01-01', '', '6', '1', '', 55, '', '', '1', 4, '', NULL, '', '', 'B', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(186, 'JAMAL DEYAB AHMED', '4315', '2119137855', '1963-07-30', '1963-07-30', '0549113264', 'A16020930', '', '', '', '', '', '5600', '11', '18', 'Supporter', '64', '30', '2015-10-01', NULL, '0', '3', 'SA0320000002040683689940', '', '', '', '1963-07-30', '', '6', '1', '', 17, '', '', '3', 7, '', NULL, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(187, 'MOBIN MAHMAD HANIF MANSURI', '4328', '2396777688', '1988-08-14', '1988-08-14', '0553425626', 'N2906300', '', '', '', '', '', '3450', '11', '16', 'Supporter', '101', '42', '2015-10-01', NULL, '0', '3', 'SA8920000001802257909940', '', '', '', '1988-08-14', '', '5', '1', '', 10, '', '', '2', 3, '', NULL, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(188, 'AHMED HASSAN MAHMOUD HASSAN', '4329', '2399983192', '1985-05-07', '1985-05-07', '0553760048', 'A15684984', '', '', '', '', '', '5500', '15', '21', 'Supporter', '64', '30', '2015-10-01', NULL, '0', '3', 'SA7920000001801572319940', '', '', '', '1985-05-07', '', '6', '1', '', 31, '', '', '2', 1, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(189, 'MUHAMMAD NADEEM', '4338', '2404820850', '1993-06-27', '1993-06-27', '0552858308', 'DU9450832', '', '', '', '', '', '2050', '11', '20', 'Supporter', '166', '60', '2015-10-13', NULL, '0', '6', 'SA1580000858608014577576', '', '', '', '1993-06-27', '', '7', '1', '', 3, '', '', '2', 15, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(190, 'ELSAYED MOHAMED AHMED ELSAYED', '4340', '2399130489', '1990-05-01', '1990-05-01', '0548787524', 'A16169444', '', '', '', '', '', '3020', '11', '16', 'Supporter', '64', '60', '2015-10-19', NULL, '0', '3', 'SA9620000008112399130489', '', '', '', '1990-05-01', '', '7', '1', '', 48, '', '', '2', 3, '', NULL, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(191, 'PATERNO T MATIAS', '4365', '2054261827', '1963-10-19', '1963-10-19', '0561983373', 'P6923642B', '', '', '', '', '', '3100', '11', '16', 'Supporter', '173', '60', '2015-12-23', NULL, '0', '6', 'SA8680000648608017732411', '', '', '', '1963-10-19', '', '7', '1', '', 29, '', '', '1', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(192, 'NESTOR CACDAC FLORENDO', '4366', '2099395002', '1964-10-07', '1964-10-07', '0558229873', 'P6446956A', '', '', '', '', '', '3150', '11', '16', 'Supporter', '173', '60', '2015-12-23', NULL, '0', '3', 'SA2920000008112099395002', '', '', '', '1964-10-07', '', '7', '1', '', 38, '', '', '2', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(193, 'SHAHZAD HANIF MUHAMMAD HANIF', '4385', '2405991411', '1990-03-10', '1990-03-10', '0559701048', 'BK6787642', '', '', '', '', '', '2750', '11', '16', 'Supporter', '166', '60', '2015-12-28', NULL, '0', '6', 'SA5680000539608019093103', '', '', '', '1990-03-10', '', '7', '1', '', 14, '', '', '2', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(194, 'ABDULSATTAR MUHAMMAD RAMZAN', '4403', '2138505546', '1966-06-25', '1966-06-25', '0560844877', 'AB5420023', '', '', '', '', '', '2100', '4', '4', 'Supporter', '166', '60', '2016-02-04', NULL, '0', '6', 'SA5180000859608011651746', '', '', '', '1966-06-25', '', '7', '1', '', 56, '', '', '2', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(195, 'AHMED TAWFIK MOSTAFA MOHAMED', '4438', '2410964262', '1980-08-23', '1980-08-23', '0566727311', 'A17634871', '', '', '', '', '', '3300', '11', '18', 'Supporter', '64', '30', '2016-03-09', NULL, '0', '3', 'SA0420000009322419349940', '', '', '', '1980-08-23', '', '6', '1', '', 9, '', '', '1', 7, '', NULL, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(196, 'MUHAMMAD ISSA M ALI KHAN', '4439', '2409970700', '1994-02-10', '1994-02-10', '0560376536', 'AQ1894232', '', '', '', '', '', '1200', '1', '1', 'Supporter', '166', '60', '2016-03-19', NULL, '0', '3', 'SA6520000008112409970700', '', '', '', '1994-02-10', '', '7', '1', '', 15, '', '', '5', 11, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(197, 'YASSER SAID MOHAMED ABDELMEGID', '4465', '2412581361', '1980-04-28', '1980-04-28', '0546145369', 'A15008995', '', '', '', '', '', '1750', '11', '18', 'Supporter', '64', '42', '2016-05-09', NULL, '0', '3', 'SA9020000009324670939940', '', '', '', '1980-04-28', '', '5', '1', '', 15, '', '', '5', 7, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(198, 'MOHAMMED ISMAIL MOHAMMED AYUB', '4473', '2415102199', '1992-12-10', '1992-12-10', '0533185380', 'M8707641', '', '', '', '', '', '3100', '11', '20', 'Supporter', '101', '60', '2016-05-20', NULL, '0', '3', 'SA2720000008112415102199', '', '', '', '1992-12-10', '', '7', '1', '', 57, '', '', '5', 15, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(199, 'ISMAEEL ASGHAR MUHAMMAD ASGHAR', '4474', '2400798464', '1993-11-15', '1993-11-15', '0552554399', 'CK1822752', '', '', '', '', '', '2450', '11', '16', 'Supporter', '166', '42', '2016-05-22', NULL, '0', '3', 'SA2820000008112400798464', '', '', '', '1993-11-15', '', '5', '1', '', 14, '', '', '6', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(200, 'YUSEPWAHYU BN MUSTAPID SARBI', '4493', '2159709894', '1962-04-11', '1962-04-11', '0551820907', 'AS984959', '', '', '', '', '', '2700', '11', '17', 'Supporter', '102', '60', '2016-07-20', NULL, '0', '3', 'SA0220000008112159709894', '', '', '', '1962-04-11', '', '7', '1', '', 29, '', '', '3', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(201, 'RENANTE LAPAZ DIONISIO', '4526', '2162469775', '1972-09-08', '1972-09-08', '0548395878', 'P3963297A', '', '', '', '', '', '2800', '11', '16', 'Supporter', '173', '60', '2016-09-07', NULL, '0', '3', 'SA7020000008112162469775', '', '', '', '1972-09-08', '', '7', '1', '', 58, '', '', '2', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(202, 'AHMED SALEM MOSLEH ALGHAMDY', '4528', '1008764704', '1979-05-26', '1979-05-26', '0548099916', '0', '', '', '', '', '', '4500', '11', '16', 'Supporter', '191', '30', '2016-09-08', NULL, '0', '3', 'SA6820000001522346399940', '', '', '', '1979-05-26', '', '6', '1', '', 59, '', '', '3', 3, '', 9.75, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(203, 'GHULAM RAZA CHANDIO', '4532', '2423196019', '1986-07-01', '1986-07-01', '0562888676', 'TL4137822', '', '', '', '', '', '2300', '11', '16', 'Supporter', '166', '42', '2016-09-28', NULL, '0', '3', 'SA3020000008112423196019', '', '', '', '1986-07-01', '', '5', '1', '', 60, '', '', '2', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(204, 'MOHAMED AWAD MOHD M RADWAN', '4533', '2422445771', '1989-08-25', '1989-08-25', '0544708924', 'A18964607', '', '', '', '', '', '3000', '1', '1', 'Supporter', '64', '60', '2016-09-28', NULL, '0', '3', 'SA6120000001801730789940', '', '', '', '1989-08-25', '', '7', '1', '', 8, '', '', '5', 4, '', NULL, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(205, 'RIYASATH ALI MOHAMMED ALI', '4560', '2424787105', '1981-06-01', '1981-06-01', '0557373472', 'P4600516', '', '', '', '', '', '2400', '1', '1', 'Supporter', '101', '42', '2016-11-14', NULL, '0', '6', 'SA6480000694608017248320', '', '', '', '1981-06-01', '', '5', '1', '', 61, '', '', '5', 4, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(206, 'BET BAHADUR RAI', '4564', '2431177431', '1993-02-18', '1993-02-18', '0549533546', '6075187', '', '', '', '', '', '1450', '1', '1', 'Supporter', '153', '42', '2017-01-02', NULL, '0', '2', 'SA8410000011100441784402', '', '', '', '1993-02-18', '', '5', '1', '', 28, '', '', '5', 4, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(207, 'ASHOK KUMAR RAI', '4565', '2431177597', '1994-09-17', '1994-09-17', '0551701715', '8868369', '', '', '', '', '', '1450', '1', '1', 'Supporter', '153', '42', '2017-01-02', NULL, '0', '2', 'SA5710000011100441774606', '', '', '', '1994-09-17', '', '5', '1', '', 28, '', '', '5', 4, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(208, 'WASEEM AKRAM NABI AHMED', '4567', '2430135588', '1992-10-22', '1992-10-22', '0555206380', 'CY1326482', '', '', '', '', '', '1850', '11', '16', 'Supporter', '166', '42', '2017-02-11', NULL, '0', '3', 'SA6820000008112430135588', '', '', '', '1992-10-22', '', '5', '1', '', 62, '', '', '2', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(209, 'AZIZA SAYED KARIM M ABDULHAKIM', '4572', '1127594677', '1982-03-14', '1982-03-14', '0556569974', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2017-04-12', NULL, '0', '3', 'SA5420000001252601319940', '', '', '', '1982-03-14', '', '4', '2', '', 15, '', '', '3', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(210, 'FAHD MOHAMMED FAHD ALQUWAYI', '4631', '1078685367', '1993-09-24', '1993-09-24', '0559861405', '0', '', '', '', '', '', '4000', '1', '1', 'Supporter', '191', '21', '2018-01-08', NULL, '0', '9', 'SA4660100010480070666001', '', '', '', '1993-09-24', '', '4', '1', '', 21, '', '', '2', 4, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(211, 'NADA MUBARAK ALMOWALAD', '4655', '1064928706', '1983-09-19', '1983-09-19', '0557250061', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '30', '2018-03-04', NULL, '0', '3', 'SA0320000001761104469940', '', '', '', '1983-09-19', '', '6', '2', '', 15, '', '', '3', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(212, 'CHAUDRY SAEED AHMAD', '4689', '2025334018', '1958-09-09', '1958-09-09', '0554543800', 'BU4190933', '', '', '', '', '', '5500', '15', '21', 'Supporter', '166', '30', '2018-07-08', NULL, '0', '3', 'SA4920000001801936129941', '', '', '', '1958-09-09', '', '6', '1', '', 17, '', '', '1', 1, '', NULL, '', '', 'A', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(213, 'MOHAMED ABD AHMED AGRAM', '4709', '2046367385', '1970-07-29', '1970-07-29', '0594795623', 'P00110946', '', '', '', '', '', '2200', '15', '21', 'Supporter', '168', '30', '2018-09-01', NULL, '0', '3', 'SA1120000001803253719940', '', '', '', '1970-07-29', '', '6', '1', '', 15, '', '', '6', 1, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(214, 'FATMAH HUSSIAN ALI ALAHMARI', '4717', '1094251061', '1993-11-23', '1993-11-23', '0537931746', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2018-10-07', NULL, '0', '3', 'SA3320000001802250299940', '', '', '', '1993-11-23', '', '4', '2', '', 15, '', '', '1', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(215, 'SHAKE SHAKIL SHAKE LAEEQ', '4736', '2411405869', '1977-08-27', '1977-08-27', '0544720841', 'N7366919', '', '', '', '', '', '3100', '11', '16', 'Supporter', '101', '60', '2018-11-05', NULL, '0', '3', 'SA6220000008112411405869', '', '', '', '1977-08-27', '', '7', '1', '', 25, '', '', '2', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(216, 'SAID HAZIR SHAH', '4756', '2464401732', '1971-01-01', '1971-01-01', '0544434356', 'CZ5143643', '', '', '', '', '', '1900', '15', '26', 'Supporter', '166', '42', '2019-02-14', NULL, '0', '6', 'SA8080000104608016478252', '', '', '', '1971-01-01', '', '5', '1', '', 49, '', '', '6', 8, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(217, 'WALEED ABDULLAH G ALGHAMDI', '4768', '1080952110', '1990-09-20', '1990-09-20', '0561276400', '0', '', '', '', '', '', '8500', '1', '1', 'Supporter', '191', '30', '2019-03-31', NULL, '0', '3', 'SA1520000009624041599940', '', '', '', '1990-09-20', '', '6', '1', '', 63, '', '', '1', 4, '', 9.75, '', '', 'B', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(218, 'JAMILAH MAHDI MOHD MARZOUG', '4780', '1105785016', '1993-12-19', '1993-12-19', '0560052936', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2019-05-22', NULL, '0', '3', 'SA0520000001511836739940', '', '', '', '1993-12-19', '', '4', '2', '', 15, '', '', '2', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(219, 'HADI ABDULLAH HASSAN JAAFARI', '4820', '1020648935', '1956-02-12', '1956-02-12', '0551573419', '0', '', '', '', '', '', '4000', '15', '23', 'Supporter', '191', '30', '2019-09-15', NULL, '0', '3', 'SA7520000001802252369940', '', '', '', '1956-02-12', '', '6', '1', '', 17, '', '', '4', 10, '', 9.75, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(220, 'ROA MUTLAK ALMUTLAK', '4824', '1080415266', '1993-02-05', '1993-02-05', '0547434195', '0', '', '', '', '', '', '6000', '1', '1', 'Supporter', '191', '30', '2019-12-08', NULL, '0', '3', 'SA3120000001570945109940', '', '', '', '1993-02-05', '', '6', '2', '', 64, '', '', '7', 11, '', 9.75, '', '', 'VIP', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(221, 'OSAMA OTHMAN SAYED AHMED', '4825', '2425686223', '1961-06-14', '1961-06-14', '0582790025', 'Q289168', '', '', '', '', '', '7500', '15', '28', 'Supporter', '111', '30', '2019-12-24', NULL, '0', '3', 'SA0920000009629157499940', '', '', '', '1961-06-14', '', '6', '1', '', 17, '', '', '4', 8, '', NULL, '', '', 'B', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(222, 'ANANDHU RAMESH', '4835', '2487665123', '1996-10-04', '1996-10-04', '0555273879', 'R6089306', '', '', '', '', '', '4500', '11', '16', 'Supporter', '101', '42', '2020-01-16', NULL, '0', '3', 'SA9520000008112487665123', '', '', '', '1996-10-04', '', '5', '1', '', 37, '', '', '2', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(223, 'ABDULKHADAR ATTAR', '4837', '2487457778', '1989-07-01', '1989-07-01', '0548519173', '0', '', '', '', '', '', '2200', '11', '17', 'Supporter', '101', '42', '2020-01-19', NULL, '0', '3', 'SA5520000008112487457778', '', '', '', '1989-07-01', '', '5', '1', '', 3, '', '', '3', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(224, 'VINU VIJAYAN', '4838', '2486350313', '1993-02-15', '1993-02-15', '0541108889', 'L7817134', '', '', '', '', '', '2100', '11', '17', 'Supporter', '101', '42', '2020-01-18', NULL, '0', '6', 'SA8680000507608016159913', '', '', '', '1993-02-15', '', '5', '1', '', 32, '', '', '2', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(225, 'BABAR ALI', '4840', '2486350750', '1998-02-08', '1998-02-08', '0547538090', 'T0992846', '', '', '', '', '', '2250', '11', '17', 'Supporter', '101', '42', '2020-01-19', NULL, '0', '2', 'SA5710000011100094036202', '', '', '', '1998-02-08', '', '5', '1', '', 27, '', '', '2', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(226, 'SEKH BASRUL ALI', '4842', '2486340082', '1993-04-04', '1993-04-04', '0551868342', 'T8432636', '', '', '', '', '', '2700', '11', '17', 'Supporter', '101', '42', '2020-01-19', NULL, '0', '2', 'SA9210000011100113217807', '', '', '', '1993-04-04', '', '5', '1', '', 65, '', '', '3', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(227, 'MUJUMIL KHAN  V AHMED KHAN', '4843', '2486333327', '1994-05-30', '1994-05-30', '0547949132', 'R2110408', '', '', '', '', '', '2000', '11', '17', 'Supporter', '101', '42', '2020-01-19', NULL, '0', '3', 'SA0820000008112486333327', '', '', '', '1994-05-30', '', '5', '1', '', 3, '', '', '3', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(228, 'RUDRAPPA BASAPPA HOOLIKATTI', '4853', '2487658789', '1991-03-27', '1991-03-27', '0553177463', 'U2993366', '', '', '', '', '', '2100', '11', '17', 'Supporter', '101', '42', '2020-01-30', NULL, '0', '3', 'SA0520000008112487658789', '', '', '', '1991-03-27', '', '5', '1', '', 3, '', '', '2', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(229, 'MUSTAFA KAMEL MOHD SAID', '4856', '2486791581', '1989-01-12', '1989-01-12', '0558704408', 'A26211945', '', '', '', '', '', '1600', '15', '21', 'Supporter', '64', '42', '2020-02-17', NULL, '0', '6', 'SA3780000640608016626729', '', '', '', '1989-01-12', '', '5', '1', '', 15, '', '', '4', 1, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(230, 'MAHMOUD REFAAT MOHD TAHA', '4857', '2486786284', '1997-09-17', '1997-09-17', '0543466038', 'A26210359', '', '', '', '', '', '1700', '15', '24', 'Supporter', '64', '42', '2020-02-17', NULL, '0', '6', 'SA2080000640608016671170', '', '', '', '1997-09-17', '', '5', '1', '', 15, '', '', '4', 7, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(231, 'AHMED ABDELFATTAH MOHD SOLIMAN', '4858', '2486790278', '1988-01-14', '1988-01-14', '0557409095', 'A26204061', '', '', '', '', '', '1700', '15', '24', 'Supporter', '64', '42', '2020-02-17', NULL, '0', '6', 'SA3680000640608016646438', '', '', '', '1988-01-14', '', '5', '1', '', 15, '', '', '4', 7, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(232, 'SALAH AMER MOHD KHALIFA', '4859', '2486792225', '1988-03-20', '1988-03-20', '0546180862', 'A26211937', '', '', '', '', '', '1700', '15', '24', 'Supporter', '64', '42', '2020-02-17', NULL, '0', '3', 'SA5720000008112486792225', '', '', '', '1988-03-20', '', '5', '1', '', 15, '', '', '4', 7, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(233, 'HAMADA MOHD SALAMA ALYAN', '4860', '2486791987', '1992-07-10', '1992-07-10', '0547441945', 'A26204833', '', '', '', '', '', '1700', '15', '24', 'Supporter', '64', '42', '2020-02-17', NULL, '0', '6', 'SA8280000507608016153874', '', '', '', '1992-07-10', '', '5', '1', '', 15, '', '', '4', 2, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(234, 'ABDELRAHMAN GAMAL M SAID', '4861', '2486787381', '1994-04-13', '1994-04-13', '0547897760', 'A26207105', '', '', '', '', '', '1700', '15', '24', 'Supporter', '64', '42', '2020-02-17', NULL, '0', '6', 'SA6980000507608016153429', '', '', '', '1994-04-13', '', '5', '1', '', 15, '', '', '4', 2, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(235, 'ALVIN BARAQUEL SINUHIN', '4869', '2488211778', '1971-04-21', '1971-04-21', '0542979874', 'P0287628B', '', '', '', '', '', '2150', '11', '16', 'Supporter', '173', '42', '2020-03-10', NULL, '0', '3', 'SA2720000008112488211778', '', '', '', '1971-04-21', '', '5', '1', '', 3, '', '', '3', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(236, 'JAMEEL HAMED MOHAMMED ALSAYED', '4894', '1041826072', '1986-08-24', '1986-08-24', '0543689087', '0', '', '', '', '', '', '5500', '11', '16', 'Supporter', '191', '30', '2020-10-01', NULL, '0', '3', 'SA3020000001764275829940', '', '', '', '1986-08-24', '', '6', '1', '', 66, '', '', '2', 3, '', 9.75, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(237, 'JAVED KHAN JANAS KHAN ', '4897', '2483834343', '1971-01-01', '1971-01-01', '0552350432', 'BH4106053', '', '', '', '', '', '2550', '15', '24', 'Supporter', '166', '42', '2020-10-18', NULL, '0', '6', 'SA9480000572608016230433', '', '', '', '1971-01-01', '', '5', '1', '', 8, '', '', '7', 7, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(238, 'HAMID UR REHMAN', '4899', '2492201955', '1988-03-24', '1988-03-24', '0573272004', 'GQ9157302', '', '', '', '', '', '3300', '11', '16', 'Supporter', '166', '42', '2020-12-01', NULL, '0', '3', 'SA4420000008112492201955', '', '', '', '1988-03-24', '', '5', '1', '', 26, '', '', '2', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(239, 'BAIJANATHI RAM CHAMAR', '4902', '2492773102', '1996-06-07', '1996-06-07', '0551203084', '6562411', '', '', '', '', '', '1600', '15', '24', 'Supporter', '153', '42', '2020-12-18', NULL, '0', '6', 'DS3280000102608016655562', '', '', '', '1996-06-07', '', '5', '1', '', 40, '', '', '7', 2, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(240, 'BIDHANANDA CHAUDHAR', '4903', '2492773011', '1987-09-24', '1987-09-24', '0542003850', '8097550', '', '', '', '', '', '1950', '15', '25', 'Supporter', '153', '42', '2020-12-18', NULL, '0', '6', 'SA5080000106608016382759', '', '', '', '1987-09-24', '', '5', '1', '', 40, '', '', '7', 8, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(241, 'SANJIB SING TAMANG', '4905', '2492772708', '1983-07-03', '1983-07-03', '0548605264', '9231215', '', '', '', '', '', '2650', '15', '28', 'Supporter', '153', '42', '2020-12-18', NULL, '0', '6', 'SA8480000163608016166285', '', '', '', '1983-07-03', '', '5', '1', '', 49, '', '', '7', 8, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(242, 'ABDULLAH ABID A ALSREIHI', '4907', '1096601495', '1996-06-14', '1996-06-14', '0558672498', '0', '', '', '', '', '', '7100', '11', '19', 'Supporter', '191', '30', '2021-01-06', NULL, '0', '3', 'SA2420000001842671349940', '', '', '', '1996-06-14', '', '6', '1', '', 67, '', '', '5', 14, '', 9.75, '', '', 'B', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(243, 'AJAZ MASIH SADIQ MASIH', '4914', '2495193316', '1980-04-18', '1980-04-18', '0553022991', 'BB0846623', '', '', '', '', '', '2300', '11', '16', 'Supporter', '166', '42', '2021-01-18', NULL, '0', '3', 'SA5620000008112495193316', '', '', '', '1980-04-18', '', '5', '1', '', 14, '', '', '2', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(244, 'MOAMEN ABDELSABOUR M. KHALIL', '4917', '2494169275', '1994-07-07', '1994-07-07', '0541386174', 'A26535217', '', '', '', '', '', '1700', '1', '1', 'Supporter', '64', '42', '2021-01-23', NULL, '0', '6', 'SA8480000640608017081783', '', '', '', '1994-07-07', '', '5', '1', '', 15, '', '', '1', 11, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(245, 'MOHAMED SAAD MOHD HAMID', '4918', '2494169465', '1997-05-18', '1997-05-18', '0541908749', 'A26535730', '', '', '', '', '', '1700', '15', '21', 'Supporter', '64', '42', '2021-01-23', NULL, '0', '6', 'SA1080000640608017089711', '', '', '', '1997-05-18', '', '5', '1', '', 15, '', '', '1', 1, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(246, 'ALI KAMEL MOHD SAID', '4919', '2494168822', '1995-08-03', '1995-08-03', '0546342821', 'A26536552', '', '', '', '', '', '1700', '15', '22', 'Supporter', '64', '42', '2021-01-23', NULL, '0', '6', 'SA9280000640608015494137', '', '', '', '1995-08-03', '', '5', '1', '', 15, '', '', '1', 7, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(247, 'HATEM GABER MAHMOUD SAID', '4920', '2494169325', '1995-08-01', '1995-08-01', '0550433258', 'A26536544', '', '', '', '', '', '1700', '15', '21', 'Supporter', '64', '42', '2021-01-23', NULL, '0', '6', 'SA9880000640608016887628', '', '', '', '1995-08-01', '', '5', '1', '', 15, '', '', '1', 1, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(248, 'AHMED MOHAMED ALI HASSAN', '4921', '2494169077', '1991-06-25', '1991-06-25', '0536962059', 'A26536553', '', '', '', '', '', '1700', '15', '28', 'Supporter', '64', '42', '2021-01-23', NULL, '0', '6', 'SA7480000104608016619301', '', '', '', '1991-06-25', '', '5', '1', '', 15, '', '', '1', 8, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(249, 'MD KAMROOL HAQUE', '4927', '2494474030', '1982-11-27', '1982-11-27', '', '12045689', '', '', '', '', '', '1850', '15', '24', 'Supporter', '153', '42', '2021-02-11', NULL, '0', '6', 'SA3580000102608016654441', '', '', '', '1982-11-27', '', '5', '1', '', 68, '', '', '7', 2, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(250, 'RITO LLAMO', '4929', '2494842160', '1982-02-23', '1982-02-23', '0540946130', 'P5894507B', '', '', '', '', '', '2000', '11', '16', 'Supporter', '173', '42', '2021-02-17', NULL, '0', '3', 'SA0320000008112494842160', '', '', '', '1982-02-23', '', '5', '1', '', 3, '', '', '3', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58');
INSERT INTO `employees` (`id`, `name`, `emp_id`, `iqama`, `iqama_exp`, `iqama_exp_g`, `mobile`, `passport_number`, `passport_exp`, `email`, `c_email`, `emg_mobile`, `emg_name`, `salary`, `dept`, `sectin_nme`, `emptype`, `country`, `vacation_days`, `joining_date`, `contract_end_date`, `fly`, `bank_name`, `iban`, `note`, `ter_note`, `ter_date`, `dob`, `dob_h`, `vac_period`, `sex`, `blood_type`, `actual_job`, `mar_status`, `t_shirt_size`, `emp_sup_type`, `comp_no`, `address`, `gosi`, `insurance_no`, `insurance_exp`, `insurance_class`, `payment_type`, `probation`, `avatar`, `status`, `created_at`, `updated_at`) VALUES
(251, 'ROBERTO LABARIENTOS DELLOSON', '4930', '2494842236', '1999-12-08', '1999-12-08', '0542225688', 'P4615052B', '', '', '', '', '', '1900', '11', '16', 'Supporter', '173', '42', '2021-02-17', NULL, '0', '6', 'SA4180000105608016309639', '', '', '', '1999-12-08', '', '5', '1', '', 29, '', '', '3', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(252, 'SALMAH MUBARAK ALMOWALAD', '4932', '1074877018', '1991-05-20', '1991-05-20', '0556763374', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2021-03-01', NULL, '0', '3', 'SA8220000009322522549940', '', '', '', '1991-05-20', '', '4', '2', '', 15, '', '', '2', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(253, 'SHARIFA BISHI M HAMAMI', '4933', '1192942322', '1992-09-27', '1992-09-27', '0505262876', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2021-03-01', NULL, '0', '3', 'SA8620000009322375999940', '', '', '', '1992-09-27', '', '4', '2', '', 15, '', '', '2', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(254, 'RICHIELET TOPASE SALONGA', '4936', '2496872777', '1994-08-20', '1994-08-20', '0570463758', 'P3546372A', '', '', '', '', '', '2000', '11', '16', 'Supporter', '173', '42', '2021-03-10', NULL, '0', '3', 'SA7220000008112496872777', '', '', '', '1994-08-20', '', '5', '1', '', 13, '', '', '6', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(255, 'NOURAH ATEYAH ALSAEDI', '4949', '1041559905', '1979-09-16', '1979-09-16', '0508804712', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2021-04-15', NULL, '0', '3', 'SA6720000001840516549941', '', '', '', '1979-09-16', '', '4', '2', '', 15, '', '', '3', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(256, 'SEHAM ALI ABDU ALMAJRASHI', '4956', '1067103497', '1984-02-06', '1984-02-06', '0532891998', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2021-06-01', NULL, '0', '3', 'SA7820000001802251339940', '', '', '', '1984-02-06', '', '4', '2', '', 15, '', '', '5', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(257, 'WEJDAN SAAD ABDULHADI ALDWSARI', '4957', '1101962007', '1996-07-10', '1996-07-10', '0502876838', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2021-06-02', NULL, '0', '3', 'SA2320000009320193789940', '', '', '', '1996-07-10', '', '4', '2', '', 15, '', '', '5', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(258, 'RUWAYDAH ADNAN ALNAHARI', '4962', '1081668988', '1993-03-30', '1993-03-30', '0500375702', '0', '', '', '', '', '', '5600', '15', '21', 'Supporter', '191', '30', '2021-06-09', NULL, '0', '3', 'SA6620000001364201519940', '', '', '', '1993-03-30', '', '6', '2', '', 69, '', '', '2', 1, '', 9.75, '', '', 'B', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(259, 'MESHAL MOHD BULAYH ALMOWALLAD', '4969', '1103291264', '1998-03-31', '1998-03-31', '0534680061', '0', '', '', '', '', '', '4400', '15', '23', 'Supporter', '191', '30', '2021-06-16', NULL, '0', '3', 'SA6020000009320291989940', '', '', '', '1998-03-31', '', '6', '1', '', 70, '', '', '4', 10, '', 9.75, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(260, 'SALWA FARAJ ABDULHAI ABAH', '4976', '1088624661', '1981-02-26', '1981-02-26', '0533656782', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2021-07-06', NULL, '0', '3', 'SA6320000001763262489940', '', '', '', '1981-02-26', '', '4', '2', '', 15, '', '', '1', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(261, 'MOHAMMED HASSAN ALKHAWAHIR', '4984', '1084829447', '1995-02-12', '1995-02-12', '0550826826', '0', '', '', '', '', '', '5600', '15', '28', 'Supporter', '191', '30', '2021-08-01', NULL, '0', '3', 'SA3920000003302533119941', '', '', '', '1995-02-12', '', '6', '1', '', 70, '', '', '4', 8, '', 9.75, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(262, 'SONNY DE LEON AGNES', '4995', '2502141142', '1973-04-15', '1973-04-15', '0550730143', 'P3463066A', '', '', '', '', '', '2170', '11', '19', 'Supporter', '173', '60', '2021-08-24', NULL, '0', '3', 'SA6120000008112502141142', '', '', '', '1973-04-15', '', '7', '1', '', 1, '', '', '3', 14, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(263, 'MAY ALIATIAH ALLAHALYAZIDI', '4996', '1111811939', '2001-05-14', '2001-05-14', '0537405231', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2021-09-07', NULL, '0', '3', 'SA7520000009320715579940', '', '', '', '2001-05-14', '', '4', '2', '', 15, '', '', '1', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(264, 'ASHWAQ MOHAMMED MOHD MAJDILI', '5004', '1099240853', '1991-09-07', '1991-09-07', '0553616011', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2021-09-20', NULL, '0', '3', 'SA7320000009321181219940', '', '', '', '1991-09-07', '', '4', '2', '', 15, '', '', '2', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(265, 'AMJAD ALI SALEM ALARYANI', '5006', '1064472325', '1989-12-27', '1989-12-27', '0500469620', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2021-09-20', NULL, '0', '3', 'SA4520000009321181779940', '', '', '', '1989-12-27', '', '4', '2', '', 15, '', '', '2', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(266, 'RABIH MAHMOUD ABDULRAHMAN', '5011', '2286414368', '1979-05-23', '1979-05-23', '0597903025', 'RL4043750', '', '', '', '', '', '9150', '11', '20', 'Supporter', '124', '30', '2021-10-17', NULL, '0', '3', 'SA8320000001511992319940', '', '', '', '1979-05-23', '', '6', '1', '', 71, '', '', '6', 15, '', NULL, '', '', 'B', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(267, 'SABIRIN ALI AHMED ALSHEHRI', '5013', '1123514489', '1996-05-18', '1996-05-18', '0532344583', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2021-10-18', NULL, '0', '3', 'SA7220000009321509099940', '', '', '', '1996-05-18', '', '4', '2', '', 15, '', '', '1', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(268, 'FATIMAH KHEDIR ALQARNI', '5018', '1042366086', '1984-06-08', '1984-06-08', '0559991096', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2021-11-01', NULL, '0', '3', 'SA7420000001523220249940', '', '', '', '1984-06-08', '', '4', '2', '', 15, '', '', '2', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(269, 'HADEEL MASOUD SAAD ALHARTHI', '5021', '1104448012', '1998-08-23', '1998-08-23', '0590093668', '0', '', '', '', '', '', '6000', '1', '1', 'Supporter', '191', '21', '2021-11-07', NULL, '0', '3', 'SA9720000009321721029940', '', '', '', '1998-08-23', '', '4', '2', '', 72, '', '', '1', 4, '', 9.75, '', '', 'B', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(270, 'SALHAH AHMED H MAHZARI', '5024', '1073661413', '1978-05-22', '1978-05-22', '0501961340', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2021-11-07', NULL, '0', '6', 'SA2780000347608010512905', '', '', '', '1978-05-22', '', '4', '2', '', 15, '', '', '2', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(271, 'ALANOUD ALI AWADH ALKLABI', '5029', '1115793786', '1998-11-12', '1998-11-12', '0551736993', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2021-12-01', NULL, '0', '3', 'SA9720000009322019789940', '', '', '', '1998-11-12', '', '4', '2', '', 15, '', '', '5', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(272, 'SALMA AHMED ALSHEHRI', '5030', '1068736840', '1980-05-16', '1980-05-16', '0542175957', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2021-12-01', NULL, '0', '3', 'SA7820000009322018229940', '', '', '', '1980-05-16', '', '4', '2', '', 15, '', '', '5', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(273, 'RAHMAH AHMED H. ALSHARMRANI', '5033', '1104102023', '1969-08-14', '1969-08-14', '0552204394', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2021-12-05', NULL, '0', '3', 'SA4720000001202050879940', '', '', '', '1969-08-14', '', '4', '2', '', 15, '', '', '6', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(274, 'MOHAMMED HASSAN F ALAMRI', '5035', '1072048711', '1989-01-01', '1989-01-01', '0569998148', '0', '', '', '', '', '', '8000', '6', '3', 'Supporter', '191', '30', '2021-12-05', NULL, '0', '2', 'SA6810000011400000592203', '', 'end of contract', '2025-09-04 16:35:32', '1989-01-01', '', '6', '1', '', 73, '', '', '1', 4, '', 9.75, '', '', 'B', '1', NULL, './assets/emp_pics/defult.png', 0, NULL, '2025-09-04 13:35:32'),
(275, 'ABDULLAH MOHAMMED K ALDAWSARI', '5043', '1093929626', '1997-02-11', '1997-02-11', '0534760913', '0', '', '', '', '', '', '5400', '15', '22', 'Supporter', '191', '30', '2021-12-29', NULL, '0', '3', 'SA8920000002041635479940', '', '', '', '1997-02-11', '', '6', '1', '', 31, '', '', '4', 7, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(276, 'FAISAL AWWADAH EID ALSULAMI', '5045', '1082098177', '1991-05-08', '1991-05-08', '0545895826', '0', '', '', '', '', '', '5000', '15', '21', 'Supporter', '191', '30', '2022-01-05', NULL, '0', '3', 'SA9320000009322491289940', '', '', '', '1991-05-08', '', '6', '1', '', 31, '', '', '4', 1, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(277, 'ADEL MUBARAK ABOUD JUBAYR', '5046', '1045453634', '1967-10-05', '1967-10-05', '0553606975', '0', '', '', '', '', '', '4000', '11', '16', 'Supporter', '191', '30', '2022-01-12', NULL, '0', '6', 'SA4280000124608010151984', '', '', '', '1967-10-05', '', '6', '1', '', 74, '', '', '2', 3, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(278, 'MUHAMMAD SHAHBAZ', '5049', '2510974054', '1986-09-01', '1986-09-01', '0553892994', 'ES5148853', '', '', '', '', '', '2050', '11', '16', 'Supporter', '166', '42', '2022-01-21', NULL, '0', '3', 'SA0220000008112510974054', '', '', '', '1986-09-01', '', '5', '1', '', 1, '', '', '2', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(279, 'MIAD AHMED KHATIM ALARYANI', '5052', '1101285490', '1997-12-10', '1997-12-10', '0540292766', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2022-01-27', NULL, '0', '3', 'SA8720000009322687349940', '', '', '', '1997-12-10', '', '4', '2', '', 15, '', '', '3', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(280, 'FATIMAH MOHAMMED OTAYF', '5055', '1061178362', '1981-05-05', '1981-05-05', '0540738722', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2022-01-30', NULL, '0', '3', 'SA7020000009322700299940', '', '', '', '1981-05-05', '', '4', '2', '', 15, '', '', '3', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(281, 'EMAN ABDULRAHMAN AHMED', '5058', '1136393251', '1984-02-02', '1984-02-02', '0559858964', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2022-02-07', NULL, '0', '3', 'SA6420000001010654019940', '', '', '', '1984-02-02', '', '4', '2', '', 15, '', '', '2', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(282, 'VISAWAABDUL JABER AHMED', '5062', '2072957760', '1962-01-01', '1962-01-01', '0553517248', '0', '', '', '', '', '', '1600', '4', '4', 'Supporter', '101', '21', '2022-02-10', NULL, '0', '6', 'SA2880000102608016642636', '', '', '', '1962-01-01', '', '4', '1', '', 75, '', '', '2', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(283, 'ABDULAZIZ SALEH ALDUGHAYYIM', '5064', '1046360570', '1986-05-07', '1986-05-07', '0554409913', '0', '', '', '', '', '', '8975', '15', '22', 'Supporter', '191', '30', '2022-02-01', NULL, '0', '2', 'SA1510000021655138000104', '', '', '', '1986-05-07', '', '6', '1', '', 19, '', '', '1', 7, '', 9.75, '', '', 'B', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(284, 'SHEMEER CHEKKAM PARAMBU SIYAD', '5065', '2239958099', '1979-10-23', '1979-10-23', '0557380481', 'V4204240', '', '', '', '', '', '1600', '1', '1', 'Supporter', '101', '21', '2022-02-20', NULL, '0', '6', 'SA6680000206608016134001', '', '', '', '1979-10-23', '', '4', '1', '', 15, '', '', '6', 4, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(285, 'FAROUK IBRAHIM SHOKRI IBRAHIM', '5071', '2254297704', '1982-08-09', '1982-08-09', '0504602924', 'A34449354', '', '', '', '', '', '18050', '11', '18', 'Supporter', '64', '30', '2022-03-01', NULL, '0', '3', 'SA5920000002371115979940', '', '', '', '1982-08-09', '', '6', '1', '', 76, '', '', '3', 7, '', NULL, '', '', 'A', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(286, 'RIAZ AHMED MASTOI', '5072', '2512256930', '1975-01-01', '1975-01-01', '0570514295', 'AM0870754', '', '', '', '', '', '1200', '1', '1', 'Supporter', '166', '30', '2022-02-28', NULL, '0', '3', 'SA5320000001802786309940', '', '', '', '1975-01-01', '', '3', '1', '', 12, '', '', '8', 11, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(287, 'ABDALLA SAIED ABDALLA SAIED', '5073', '2517010605', '1989-02-09', '1989-02-09', '0569311952', 'A22392855', '', '', '', '', '', '1550', '15', '24', 'Supporter', '64', '42', '2022-03-14', NULL, '0', '6', 'SA9080000640608016618403', '', '', '', '1989-02-09', '', '5', '1', '', 15, '', '', '1', 2, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(288, 'KHALID IBRAHIM ALI', '5079', '2505156410', '1989-01-01', '1989-01-01', '0553749832', '0', '', '', '', '', '', '2050', '11', '18', 'Supporter', '207', '21', '2022-04-02', NULL, '0', '6', 'SA4180000331608016327827', '', '', '', '1989-01-01', '', '4', '1', '', 77, '', '', '3', 7, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(289, 'GHADEER MOHD ALQARNI', '5080', '1014464307', '1971-07-11', '1971-07-11', '0541516149', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2022-04-03', NULL, '0', '3', 'SA6820000009323624749940', '', '', '', '1971-07-11', '', '4', '2', '', 15, '', '', '2', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(290, 'WEDDAH HASSAN SHAMI', '5081', '1090755321', '1995-05-27', '1995-05-27', '0567429280', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2022-04-03', NULL, '0', '3', 'SA1420000009323553079940', '', '', '', '1995-05-27', '', '4', '2', '', 15, '', '', '5', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(291, 'RANA ABDULMUTI H ALHAZMI', '5085', '1099495390', '1997-12-26', '1997-12-26', '0506076091', '', '', '', '', '', '', '5000', '1', '1', 'Supporter', '191', '30', '2022-04-10', NULL, '0', '3', 'SA0420000001801633959940', '', '', '', '1997-12-26', '', '1 Year', '2', '', 0, '', '', '1', 4, '', 9.75, '', '', '', '1', NULL, './assets/emp_pics/defult.png', 0, NULL, '2025-07-10 11:30:58'),
(292, 'ZAHIR ULLAH BISMILLAH KHAN', '5087', '2518451758', '1991-11-20', '1991-11-20', '0573450656', 'CV2742493', '', '', '', '', '', '2400', '15', '21', 'Supporter', '166', '42', '2022-04-15', NULL, '0', '6', 'SA8980000163608016167747', '', '', '', '1991-11-20', '', '5', '1', '', 49, '', '', '7', 1, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(293, 'MOHAMED KOMSAN ELSAYED IBRAHIM', '5091', '2520459963', '1996-08-21', '1996-08-21', '0564680930', 'A29813003', '', '', '', '', '', '2000', '15', '24', 'Supporter', '64', '42', '2022-05-22', NULL, '0', '6', 'SA0680000640608016646008', '', '', '', '1996-08-21', '', '5', '1', '', 8, '', '', '7', 7, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(294, 'AHMED ELDAIDAMOUNI HASSAN', '5093', '2419374463', '1991-08-01', '1991-08-01', '0562198098', 'A18187898', '', '', '', '', '', '4200', '15', '22', 'Supporter', '64', '30', '2022-05-29', NULL, '0', '6', 'SA1980000564608016059329', '', '', '', '1991-08-01', '', '6', '1', '', 17, '', '', '4', 7, '', NULL, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(295, 'MOHARRAM ABDELMAWLA MOD', '5095', '2521108890', '1967-04-19', '1967-04-19', '0551324100', 'A28983312', '', '', '', '', '', '2500', '1', '1', 'Supporter', '64', '42', '2022-05-31', NULL, '0', '3', 'SA7220000009324473419940', '', '', '', '1967-04-19', '', '5', '1', '', 8, '', '', '5', 11, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(296, 'ABDULLAH AHMAD ALMASMOOM', '5096', '1088508286', '1994-10-03', '1994-10-03', '0562212240', '0', '', '', '', '', '', '6500', '11', '16', 'Supporter', '191', '21', '2022-06-05', NULL, '0', '6', 'SA0780000329608010593220', '', '', '', '1994-10-03', '', '4', '1', '', 78, '', '', '4', 3, '', 9.75, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(297, 'RAVI KUMAR', '5101', '2523491963', '1986-10-07', '1986-10-07', '0580540741', 'M4607099', '', '', '', '', '', '6600', '11', '17', 'Supporter', '101', '30', '2022-06-16', NULL, '0', '3', 'SA1920000001512076049940', '', '', '', '1986-10-07', '', '6', '1', '', 79, '', '', '3', 5, '', NULL, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(298, 'ABDULAZIZ AWADH ALHASSAN', '5107', '1072598939', '1991-06-13', '1991-06-13', '0542834093', '0', '', '', '', '', '', '6200', '15', '26', 'Supporter', '191', '30', '2022-07-06', NULL, '0', '6', 'SA1080000157608010003664', '', '', '', '1991-06-13', '', '6', '1', '', 31, '', '', '3', 8, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(299, 'SARFRAZ AHMED', '5109', '2526994088', '1981-05-01', '1981-05-01', '0572912252', 'S8728604', '', '', '', '', '', '3350', '11', '17', 'Supporter', '101', '60', '2022-08-09', NULL, '0', '2', 'SA9710000011100286780205', '', '', '', '1981-05-01', '', '7', '1', '', 31, '', '', '3', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(300, 'ABDULRAHMAN MOHAMMED ALSALHI', '5111', '1085010682', '1412-05-20', '1991-11-27', '0569278564', '0', '', '', '', '569278564', 'ABDULRAHMAN MOHAMMED ALSALHI', '4400', '5', '16', 'Supporter', '191', '30', '2022-08-17', NULL, '0', '3', 'SA9320000001802833039940', '', '', '', '1991-11-27', '1412-05-20', '6', '1', 'A+', 100, 'single', '', '4', 1, '', 9.75, '', '', 'C', '1', '', './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(301, 'ROUA AHMED SENDI', '5115', '1000920619', '1401-08-02', '1981-06-05', '0562326246', '0', '', '', '', '563636677', 'hani', '7100', '5', '5', 'Supporter', '191', '30', '2022-08-28', NULL, '0', '3', 'SA3610000011800000193604', '', '', '', '1981-06-05', '1401-08-02', '6', '2', 'A+', 44, 'married', '', '1', 4, '', 9.75, '', '', 'B', '1', NULL, './assets/emp_pics/defultFemale.jpg', 1, NULL, '2025-07-10 11:30:58'),
(302, 'SANDHEEV GOPINATHAN PILLAI', '5116', '2528081306', '1971-05-20', '1971-05-20', '0571706655', 'L3457327', '', '', '', '', '', '3700', '11', '16', 'Supporter', '101', '30', '2022-08-27', NULL, '0', '6', 'SA7180000856608016012947', '', '', '', '1971-05-20', '', '6', '1', '', 31, '', '', '2', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(303, 'GHULAM MURTAZA', '5118', '2016584977', '1957-07-05', '1957-07-05', '0508377100', 'AK0971363', '', '', '', '', '', '21000', '11', '16', 'Supporter', '166', '30', '2022-09-01', NULL, '0', '3', 'SA2220000001802272829940', '', '', '', '1957-07-05', '', '6', '1', '', 80, '', '', '2', 3, '', NULL, '', '', 'A', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(304, 'MOHAMED SAID MOSILHY ELKADI', '5119', '2274645338', '1977-02-17', '1977-02-17', '0597531994', 'A31122019', '', '', '', '', '', '8300', '1', '1', 'Supporter', '64', '30', '2022-09-01', NULL, '0', '3', 'SA4220000001800576869940', '', '', '', '1977-02-17', '', '6', '1', '', 19, '', '', '3', 11, '', NULL, '', '', 'B', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(305, 'SUHAIL SALEM ALZAHRANI', '5122', '1126378254', '2001-01-11', '2001-01-11', '0501529168', '0', '', '', '', '', '', '5500', '1', '1', 'Supporter', '191', '30', '2022-09-11', NULL, '0', '3', 'SA8920000001882652479940', '', '', '', '2001-01-11', '', '6', '1', '', 81, '', '', '1', 4, '', 9.75, '', '', 'B', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(306, 'ASANARU KUNJU HANEEFA', '5126', '2512752888', '1967-05-30', '1967-05-30', '0572261639', 'T8203762', '', '', '', '', '', '2550', '11', '17', 'Supporter', '101', '21', '2022-09-24', NULL, '0', '2', 'SA5710000034200002646109', '', '', '', '1967-05-30', '', '4', '1', '', 82, '', '', '3', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(307, 'MAKARAM JAVAID', '5127', '2506165311', '1447-08-07', '2026-01-26', '0543837151', 'AZ8697941', '', 'makaramjavaid@gmail.com', 'it.admin@almutak.com', '556945778', 'Brother - Qasim', '7000', '6', '3', 'Supporter', '166', '21', '2022-10-02', NULL, '0', '6', 'SA7080000126608016112806', '', '', '', '1997-07-13', '', '4', '1', 'O+', 83, 'single', 'M', '1', 4, '', NULL, '', '', 'C', '1', NULL, './assets/emp_pics/51273071756108274.png', 1, NULL, '2025-08-28 10:26:12'),
(308, 'ZAHRAH YAHYAH ALMARHABI', '5128', '1087728000', '1991-07-03', '1991-07-03', '0542018940', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2022-09-25', NULL, '0', '3', 'SA8220000009325593569940', '', '', '', '1991-07-03', '', '4', '2', '', 15, '', '', '3', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(309, 'MAJID HAMOUD ALHABRI', '5138', '1024997924', '1979-07-21', '1979-07-21', '0569795061', '0', '', '', '', '', '', '7000', '11', '16', 'Supporter', '191', '30', '2022-10-09', NULL, '0', '6', 'SA3980000598608016006247', '', '', '', '1979-07-21', '', '6', '2', '', 31, '', '', '2', 3, '', 9.75, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(310, 'HIND ALHASSAN ALMUWALLAD', '5141', '1015572637', '1978-10-13', '1978-10-13', '0533866864', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2022-10-23', NULL, '0', '3', 'SA0620000009325975329940', '', '', '', '1978-10-13', '', '4', '2', '', 15, '', '', '2', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(311, 'GITHAH ALMUNASHRI', '5142', '1060840186', '1980-07-27', '1980-07-27', '0551864753', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2022-10-23', NULL, '0', '3', 'SA8720000009325948489940', '', '', '', '1980-07-27', '', '4', '2', '', 15, '', '', '3', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(312, 'SABHA ALZAHRANI', '5146', '1070021454', '1985-09-24', '1985-09-24', '0503111803', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2022-10-23', NULL, '0', '3', 'SA2620000009325946799940', '', '', '', '1985-09-24', '', '4', '2', '', 15, '', '', '1', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(313, 'DAREEN SHABAAN ALI', '5147', '1154484370', '1997-10-15', '1997-10-15', '0542705764', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2022-10-23', NULL, '0', '6', 'SA1380000240608010730646', '', '', '', '1997-10-15', '', '4', '2', '', 15, '', '', '1', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(314, 'BIKASH B K', '5148', '2503964989', '1996-11-05', '1996-11-05', '0563584894', '12120202', '', '', '', '', '', '1650', '15', '26', 'Supporter', '153', '42', '2022-10-17', NULL, '0', '6', 'SA0880000513608016158883', '', '', '', '1996-11-05', '', '5', '1', '', 84, '', '', '3', 8, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(315, 'ALI HOSSAIN', '5149', '2497555447', '1979-01-01', '1979-01-01', '0598775619', 'EA0237421', '', '', '', '', '', '1500', '15', '22', 'Supporter', '18', '42', '2022-10-17', NULL, '0', '6', 'SA8480000125608010684678', '', '', '', '1979-01-01', '', '5', '1', '', 84, '', '', '3', 7, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(316, 'MD SHAMIM MIAH', '5150', '2499083687', '1996-03-01', '1996-03-01', '0558705699', '0', '', '', '', '', '', '1500', '11', '18', 'Supporter', '18', '42', '2022-10-30', NULL, '0', '6', 'SA4980000149608016095350', '', '', '', '1996-03-01', '', '5', '1', '', 15, '', '', '3', 7, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(317, 'FAZLUL HOQUE', '5151', '2525762163', '1978-02-01', '1978-02-01', '0552711006', 'A01992876', '', '', '', '', '', '2550', '11', '17', 'Supporter', '18', '42', '2022-11-01', NULL, '0', '2', 'SA4510000011100305083101', '', '', '', '1978-02-01', '', '5', '1', '', 15, '', '', '3', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(318, 'MUHAMMED RAFEEQ AHAMADUL', '5152', '2524487168', '1995-04-27', '1995-04-27', '0572000676', 'P3402302', '', '', '', '', '', '2100', '11', '17', 'Supporter', '101', '42', '2022-11-01', NULL, '0', '2', 'SA4810000066800001365308', '', '', '', '1995-04-27', '', '5', '1', '', 15, '', '', '3', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(319, 'ELHAM IBRAHIM S ASIRI', '5157', '1105721094', '1998-03-03', '1998-03-03', '0531521288', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2022-11-20', NULL, '0', '3', 'SA7720000009326181489940', '', '', '', '1998-03-03', '', '4', '2', '', 15, '', '', '3', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(320, 'MAZHRA MUHAMMED AL AMRI', '5160', '1047642390', '1984-01-18', '1984-01-18', '0541546495', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2022-11-20', NULL, '0', '3', 'SA0420000009326181979940', '', '', '', '1984-01-18', '', '4', '2', '', 15, '', '', '3', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(321, 'NABILAH ABDU ALMAGHRABI', '5161', '1103312516', '1993-05-18', '1993-05-18', '0550944582', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2022-11-20', NULL, '0', '3', 'SA7620000009326188299940', '', '', '', '1993-05-18', '', '4', '2', '', 15, '', '', '2', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(322, 'SOHAG HOSSAIN', '5163', '2429411834', '1990-08-15', '1990-08-15', '0572697865', 'BY0077877', '', '', '', '', '', '2000', '11', '19', 'Supporter', '18', '42', '2022-12-03', NULL, '0', '6', 'SA9280000640608017578861', '', '', '', '1990-08-15', '', '5', '1', '', 3, '', '', '5', 14, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(323, 'KOCHAN DAMODORAN BHOOPESH', '5165', '2535402917', '1969-05-22', '1969-05-22', '0539424591', 'S3832673', '', '', '', '', '', '4360', '11', '17', 'Supporter', '101', '30', '2022-11-28', NULL, '0', '2', 'SA7910000011100333519203', '', '', '', '1969-05-22', '', '6', '1', '', 48, '', '', '3', 5, '', NULL, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(324, 'MONIR HOSIN', '5167', '2499773998', '1983-04-09', '1983-04-09', '0573991286', 'BT0305683', '', '', '', '', '', '1800', '11', '20', 'Supporter', '18', '42', '2022-11-21', NULL, '0', '3', 'SA8720000001512132889940', '', '', '', '1983-04-09', '', '5', '1', '', 3, '', '', '6', 15, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(325, 'NAHLAH OUDAH ALHABI', '5168', '1011329057', '1980-12-21', '1980-12-21', '0549803997', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2022-11-29', NULL, '0', '3', 'SA1320000001882701529940', '', '', '', '1980-12-21', '', '4', '2', '', 15, '', '', '6', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(326, 'TOUFIQUE SIDDIQUE', '5173', '2536473644', '1990-04-04', '1990-04-04', '0569923740', 'U0693385', '', '', '', '', '', '2500', '11', '16', 'Supporter', '101', '60', '2022-12-18', NULL, '0', '2', 'SA1710000011100333902208', '', '', '', '1990-04-04', '', '7', '1', '', 85, '', '', '2', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(327, 'MD KABIR HOSSAIN', '5174', '2532734973', '1993-01-01', '1993-01-01', '0558548927', 'EF0508854', '', '', '', '', '', '1800', '11', '20', 'Supporter', '18', '42', '2022-11-21', NULL, '0', '3', 'SA9320000001512132769940', '', '', '', '1993-01-01', '', '5', '1', '', 3, '', '', '6', 15, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(328, 'NEIL OMAPAS BISNAR', '5181', '2538381449', '1975-08-11', '1975-08-11', '0547311415', 'P5964363B', '', '', '', '', '', '3075', '15', '22', 'Supporter', '173', '42', '2023-01-23', NULL, '0', '6', 'SA1380000640608018102240', '', '', '', '1975-08-11', '', '5', '1', '', 85, '', '', '7', 7, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(329, 'KHADRA HASSA OSAYLAH', '5186', '1199678739', '1992-07-27', '1992-07-27', '0550369171', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2023-02-01', NULL, '0', '3', 'SA8820000001523445979940', '', '', '', '1992-07-27', '', '4', '2', '', 15, '', '', '3', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(330, 'MARIAM ESSA SHABANI', '5187', '1059000081', '1989-02-21', '1989-02-21', '0557021316', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2023-02-01', NULL, '0', '3', 'SA2720000001882725499940', '', '', '', '1989-02-21', '', '4', '2', '', 15, '', '', '3', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(331, 'ABDULRAHMAN SAAD ALSULAMI', '5204', '1063748808', '1989-07-19', '1989-07-19', '0547322608', '0', '', '', '', '', '', '6000', '11', '17', 'Supporter', '191', '30', '2023-04-02', NULL, '0', '3', 'SA3520000001512195039940', '', '', '', '1989-07-19', '', '6', '1', '', 86, '', '', '3', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(332, 'ANANDU SASI KUMAR', '5251', '2540044407', '2000-01-20', '2000-01-20', '0535019030', 'V8780348', '', '', '', '', '', '1500', '11', '16', 'Supporter', '101', '42', '2023-05-22', NULL, '0', '6', 'SA2080000163608016146485', '', '', '', '2000-01-20', '', '5', '1', '', 15, '', '', '2', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(333, 'AJIKUMAR CHELLAPPAN', '5252', '2540044258', '1974-05-30', '1974-05-30', '0535019181', 'U9490176', '', '', '', '', '', '1800', '11', '16', 'Supporter', '101', '42', '2023-05-22', NULL, '0', '2', 'SA8410000011100369981510', '', '', '', '1974-05-30', '', '5', '1', '', 87, '', '', '2', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(334, 'KHULUD MOHAMMED NATHMI', '5253', '1061261697', '1989-05-04', '1989-05-04', '0568755865', '0', '', '', '', '', '', '5500', '11', '20', 'Supporter', '191', '21', '2023-06-04', NULL, '0', '2', 'SA3510000015448515000201', '', '', '', '1989-05-04', '', '4', '2', '', 88, '', '', '6', 15, '', 9.75, '', '', 'B', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(335, 'RABAB MOHAMMED ALQARNI', '5254', '1011314406', '1980-09-24', '1980-09-24', '0555628217', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2023-05-30', NULL, '0', '3', 'SA4920000001803045379940', '', '', '', '1980-09-24', '', '4', '2', '', 15, '', '', '5', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(336, 'SALIHAH AHMED ALSHAMRANI', '5257', '1054143670', '1981-05-05', '1981-05-05', '0566380198', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2023-06-05', NULL, '0', '3', 'SA9020000001803118279940', '', '', '', '1981-05-05', '', '4', '2', '', 15, '', '', '5', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(337, 'LAILA AHMED ASIRI', '5259', '1097061699', '1996-05-12', '1996-05-12', '0565929118', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2023-06-13', NULL, '0', '3', 'SA1620000001803121699940', '', '', '', '1996-05-12', '', '4', '2', '', 15, '', '', '5', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(338, 'AHMED MOHD MESSELHY SHALABY', '5261', '2549041156', '1973-12-07', '1973-12-07', '0555921138', 'A31414264', '', '', '', '', '', '13100', '11', '16', 'Supporter', '64', '30', '2023-06-20', NULL, '0', '3', 'SA5520000001512205309940', '', '', '', '1973-12-07', '', '6', '1', '', 54, '', '', '2', 3, '', NULL, '', '', 'B', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(339, 'NORAH HASSAN ALSHAIKHI', '5264', '1208238798', '1999-07-20', '1999-07-20', '0532335942', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2023-07-09', NULL, '0', '6', 'SA4880000648608016371021', '', '', '', '1999-07-20', '', '4', '2', '', 15, '', '', '5', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(340, 'AMNAH AHMED ALZAHRANI', '5266', '1121711863', '1984-01-24', '1984-01-24', '0509207220', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2023-07-10', NULL, '0', '6', 'SA3280000453608010149682', '', '', '', '1984-01-24', '', '4', '2', '', 15, '', '', '5', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(341, 'MOHAMED SALAMA IBRAHIM ELSEDEMY', '5268', '2547804068', '1979-04-27', '1979-04-27', '0570641109', 'A30859221', '', '', '', '', '', '1750', '11', '17', 'Supporter', '64', '42', '2023-07-23', NULL, '0', '6', 'SA0380000163608016150768', '', '', '', '1979-04-27', '', '5', '1', '', 8, '', '', '5', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(342, 'MUNNA BEPARY', '5271', '2483423576', '1993-08-25', '1993-08-25', '0558931334', '0', '', '', '', '', '', '1800', '11', '19', 'Supporter', '18', '42', '2023-08-16', NULL, '0', '6', 'SA1280000102608016514884', '', '', '', '1993-08-25', '', '5', '1', '', 15, '', '', '5', 14, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(343, 'ARIF HOSSAIN BEPARI', '5272', '2480338264', '1994-05-01', '1994-05-01', '0583117382', 'BR0431124', '', '', '', '', '', '2000', '11', '16', 'Supporter', '18', '42', '2023-08-01', NULL, '0', '6', 'SA3780000640608014415711', '', '', '', '1994-05-01', '', '5', '1', '', 89, '', '', '2', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(344, 'FATIMAH MUBARAK ALZAHRANI', '5273', '1091268571', '1993-07-21', '1993-07-21', '0536475144', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2023-07-17', NULL, '0', '3', 'SA2720000001424217169940', '', '', '', '1993-07-21', '', '4', '2', '', 15, '', '', '5', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(345, 'FAYZAH GHURMULLAH ALGHAMDI', '5275', '1064875675', '1990-02-03', '1990-02-03', '0531115180', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2023-07-17', NULL, '0', '3', 'SA5220000001523619359940', '', '', '', '1990-02-03', '', '4', '2', '', 15, '', '', '3', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(346, 'FATEN HASSAN ALSHAMRANI', '5276', '1043923125', '1986-05-15', '1986-05-15', '0507794995', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2023-07-17', NULL, '0', '2', 'SA8910000010300000804601', '', '', '', '1986-05-15', '', '4', '2', '', 15, '', '', '3', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(347, 'GABRIEL BERNADEZ CAMPILLOS', '5278', '2474837206', '1970-04-12', '1970-04-12', '0545652983', 'P6624052B', '', '', '', '', '', '2150', '11', '16', 'Supporter', '173', '42', '2023-08-10', NULL, '0', '2', 'SA8310000034000001020610', '', '', '', '1970-04-12', '', '5', '1', '', 1, '', '', '2', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(348, 'FATIMAH ALI AHMED ALFAQIH', '5280', '1075867679', '1986-11-28', '1986-11-28', '0531852912', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2023-08-03', NULL, '0', '3', 'SA0420000001532584089940', '', '', '', '1986-11-28', '', '4', '2', '', 15, '', '', '3', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(349, 'ABDULLAH MOHAMED AL HASHIM', '5282', '1117212306', '2001-10-29', '2001-10-29', '0507208146', '0', '', '', '', '', '', '7000', '11', '20', 'Supporter', '191', '30', '2023-08-13', NULL, '0', '3', 'SA8920000001193451959940', '', '', '', '2001-10-29', '', '6', '1', '', 31, '', '', '6', 15, '', 9.75, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(350, 'HANAN MOHAMMED ALABDALI', '5285', '1031062423', '', '1986-03-21', '0534281780', '0', '', '', '', '', '', '4200', '16', '17', 'Supporter', '191', '21', '2023-08-03', NULL, '0', '3', 'SA8620000001803217299940', '', '', '', '1986-03-21', '1406-07-10', '4', '2', '', 15, '', '', '2', 5, '', 9.75, '', '', 'CLT', '1', '', './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(351, 'MANAL YOUSEF AL DAHMAN', '5286', '1139930075', '2003-08-31', '2003-08-31', '0566021895', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2023-08-03', NULL, '0', '3', 'SA0620000001392145509940', '', '', '', '2003-08-31', '', '4', '2', '', 15, '', '', '2', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(352, 'MD EMAM HOSSEIN DIPU', '5287', '2548835533', '2000-02-03', '2000-02-03', '0572860819', 'A06449688', '', '', '', '', '', '1500', '15', '22', 'Supporter', '18', '42', '2023-08-15', NULL, '0', '6', 'SA9880000129608016674364', '', '', '', '2000-02-03', '', '5', '1', '', 15, '', '', '4', 7, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(353, 'REJI LUCKOSE', '5288', '2550232405', '1975-05-31', '1975-05-31', '0555719697', 'W0082859', '', '', '', '', '', '1500', '11', '17', 'Supporter', '101', '42', '2023-09-02', NULL, '0', '6', 'SA3480000163608016151956', '', '', '', '1975-05-31', '', '5', '1', '', 89, '', '', '3', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(354, 'OSMAN ALI OSMAN ALI', '5289', '2532798515', '1995-01-01', '1995-01-01', '0571865721', '0', '', '', '', '', '', '1800', '15', '22', 'Supporter', '207', '21', '2023-09-10', NULL, '0', '2', 'SA4010000011100343129404', '', '', '', '1995-01-01', '', '4', '1', '', 8, '', '', '3', 7, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(355, 'JIHAD UDDIN PARVEY', '5290', '2547497954', '1999-11-26', '1999-11-26', '0571756008', 'AO5380210', '', '', '', '', '', '1500', '11', '17', 'Supporter', '18', '42', '2023-10-01', NULL, '0', '3', 'SA6120000008112547497954', '', '', '', '1999-11-26', '', '5', '1', '', 15, '', '', '3', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(356, 'ISMAIL AKON', '5291', '2501949354', '1998-09-18', '1998-09-18', '0559274214', 'EH0596387', '', '', '', '', '', '1500', '11', '17', 'Supporter', '18', '42', '2023-10-01', NULL, '0', '6', 'SA8080000110608016212851', '', '', '', '1998-09-18', '', '5', '1', '', 15, '', '', '3', 5, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(357, 'MOHAMED ABDELLAH ELAREF AHMED', '5292', '2553676392', '1990-01-03', '1990-01-03', '0543969072', 'A31945509', '', '', '', '', '', '1700', '15', '28', 'Supporter', '64', '42', '2023-10-02', NULL, '0', '6', 'SA3080000996608017141876', '', '', '', '1990-01-03', '', '5', '1', '', 34, '', '', '4', 8, '', NULL, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(358, 'EMAM ELSAYED MOHD MUSTAFA RAHMA', '5293', '2557711864', '1968-04-27', '1968-04-27', '0554005213', 'A26897623', '', '', '', '', '', '11000', '11', '19', 'Supporter', '64', '60', '2023-09-29', NULL, '0', '1', 'SA8320000001803214449940', '', '', '', '1968-04-27', '', '7', '1', '', 90, '', '', '5', 14, '', NULL, '', '', 'B', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(359, 'ALI MAHMOUD ALI ELHATTAB', '5294', '2518195926', '1978-03-04', '1978-03-04', '0553454687', 'A29507121', '', '', '', '', '', '5300', '15', '21', 'Supporter', '64', '30', '2023-10-02', NULL, '0', '2', 'SA8610000011100257137106', '', '', '', '1978-03-04', '', '6', '1', '', 17, '', '', '4', 1, '', NULL, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(360, 'BASSAM ADNAN HALAWANI', '5295', '1084185899', '1993-12-22', '1993-12-22', '0564748079', '0', '', '', '', '', '', '6000', '11', '20', 'Supporter', '191', '21', '2023-10-15', NULL, '0', '3', 'SA5520000001226162009940', '', '', '', '1993-12-22', '', '4', '1', '', 78, '', '', '3', 15, '', 9.75, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(361, 'PETER FAROUK NOAMAN ABDELMESSEH', '5296', '2557840267', '1986-10-03', '1986-10-03', '0559964462', 'A28907170', '', '', '', '', '', '11700', '11', '16', 'Supporter', '64', '60', '2023-10-15', NULL, '0', '3', 'SA2920000001512272759940', '', '', '', '1986-10-03', '', '7', '1', '', 79, '', '', '2', 3, '', NULL, '', '', 'B', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(362, 'JOEL PEREZ PADALLAN', '5297', '2559098781', '1996-11-03', '1996-11-03', '0543764149', 'P9444235', '', '', '', '', '', '2175', '11', '16', 'Supporter', '173', '42', '2023-10-25', NULL, '0', '3', 'SA4420000008112559098781', '', '', '', '1996-11-03', '', '5', '1', '', 13, '', '', '2', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(363, 'REINALD GREGORIO ARAJA', '5298', '2559100157', '1995-02-09', '1995-02-09', '0573427703', 'P9718968A', '', '', '', '', '', '2175', '11', '16', 'Supporter', '173', '42', '2023-10-25', NULL, '0', '3', 'SA4320000008112559100157', '', '', '', '1995-02-09', '', '5', '1', '', 91, '', '', '2', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(364, 'JUNNIE PALALLOS VILLAROBIA JR', '5299', '2559100017', '1996-03-23', '1996-03-23', '0548850952', 'P2409149C', '', '', '', '', '', '2175', '11', '16', 'Supporter', '173', '42', '2023-10-25', NULL, '0', '3', 'SA4020000008112559100017', '', '', '', '1996-03-23', '', '5', '1', '', 38, '', '', '2', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(365, 'JOMEL PALALLOS VILLAROBIA', '5300', '2559100645', '1992-12-02', '1992-12-02', '0546134744', 'P7405891B', '', '', '', '', '', '2175', '11', '16', 'Supporter', '173', '42', '2023-10-25', NULL, '0', '3', 'SA5920000008112559100645', '', '', '', '1992-12-02', '', '5', '1', '', 38, '', '', '2', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(366, 'JAYSON LAYUG JUSAYAN', '5301', '2559100850', '1986-11-03', '1986-11-03', '0572447468', 'P8685110B', '', '', '', '', '', '2175', '11', '16', 'Supporter', '173', '42', '2023-10-25', NULL, '0', '3', 'SA5320000008112559100850', '', '', '', '1986-11-03', '', '5', '1', '', 92, '', '', '2', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(367, 'CHRISTIAN SAPNO PALAD', '5302', '2559099748', '1995-12-29', '1995-12-29', '0572679952', 'P6649624B', '', '', '', '', '', '2200', '11', '16', 'Supporter', '173', '42', '2023-10-25', NULL, '0', '3', 'SA2820000008112559099748', '', '', '', '1995-12-29', '', '5', '1', '', 92, '', '', '2', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(368, 'RODAN MISA NOCUM', '5303', '2559099474', '1989-08-12', '1989-08-12', '0571864474', 'P8688782A', '', '', '', '', '', '2175', '11', '16', 'Supporter', '173', '42', '2023-10-25', NULL, '0', '3', 'SA5420000008112559099474', '', '', '', '1989-08-12', '', '5', '1', '', 1, '', '', '2', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(369, 'ROMEO FAMATIGA ANORICO', '5304', '2559099193', '1978-01-25', '1978-01-25', '0572052820', 'P8494715B', '', '', '', '', '', '2175', '11', '16', 'Supporter', '173', '42', '2023-10-25', NULL, '0', '3', 'SA7520000008112559099193', '', '', '', '1978-01-25', '', '5', '1', '', 13, '', '', '2', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(370, 'CAPISTRANO VILLA AVILA', '5305', '2559100496', '2001-02-12', '2001-02-12', '0573203062', 'P8926307B', '', '', '', '', '', '2175', '11', '16', 'Supporter', '173', '42', '2023-10-25', NULL, '0', '3', 'SA0820000008112559100496', '', '', '', '2001-02-12', '', '5', '1', '', 29, '', '', '2', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(371, 'RANDY CARDENILLO PANGANIBAN', '5306', '2559101015', '1997-05-31', '1997-05-31', '0571450919', 'P9840203B', '', '', '', '', '', '2175', '11', '16', 'Supporter', '173', '42', '2023-10-30', NULL, '0', '3', 'SA6020000008112559101015', '', '', '', '1997-05-31', '', '5', '1', '', 91, '', '', '2', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(372, 'JUANITO GAPIT WAGAN', '5307', '2559101239', '1998-08-23', '1998-08-23', '0560495680', 'P1433944C', '', '', '', '', '', '2175', '11', '16', 'Supporter', '173', '42', '2023-10-30', NULL, '0', '3', 'SA2620000008112559101239', '', '', '', '1998-08-23', '', '5', '1', '', 91, '', '', '2', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(373, 'RYAN PARRILLA AUTIDA', '5308', '2559101635', '1998-02-06', '1998-02-06', '0572077843', 'P1257842B', '', '', '', '', '', '2175', '11', '16', 'Supporter', '173', '42', '2023-10-30', NULL, '0', '3', 'SA0420000008112559101635', '', '', '', '1998-02-06', '', '5', '1', '', 1, '', '', '2', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(374, 'CHRISTIAN ZORILLA ARROYO', '5309', '2559185067', '1992-09-18', '1992-09-18', '0532981801', 'P0133333C', '', '', '', '', '', '2200', '11', '16', 'Supporter', '173', '42', '2023-10-30', NULL, '0', '3', 'SA6820000008112559185067', '', '', '', '1992-09-18', '', '5', '1', '', 29, '', '', '2', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(375, 'SHARIF HOSSAIN ABDUR RASHID', '5312', '2468019845', '1996-01-12', '1996-01-12', '0564225968', 'BY0500295', '', '', '', '', '', '2100', '11', '19', 'Supporter', '18', '42', '2023-12-10', NULL, '0', '6', 'SA188000016360801613399', '', '', '', '1996-01-12', '', '5', '1', '', 89, '', '', '5', 14, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58');
INSERT INTO `employees` (`id`, `name`, `emp_id`, `iqama`, `iqama_exp`, `iqama_exp_g`, `mobile`, `passport_number`, `passport_exp`, `email`, `c_email`, `emg_mobile`, `emg_name`, `salary`, `dept`, `sectin_nme`, `emptype`, `country`, `vacation_days`, `joining_date`, `contract_end_date`, `fly`, `bank_name`, `iban`, `note`, `ter_note`, `ter_date`, `dob`, `dob_h`, `vac_period`, `sex`, `blood_type`, `actual_job`, `mar_status`, `t_shirt_size`, `emp_sup_type`, `comp_no`, `address`, `gosi`, `insurance_no`, `insurance_exp`, `insurance_class`, `payment_type`, `probation`, `avatar`, `status`, `created_at`, `updated_at`) VALUES
(376, 'ALTAIF DULAL DEWAN', '5313', '2501366906', '1998-10-05', '1998-10-05', '0507701229', 'EK0611778', '', '', '', '', '', '2100', '11', '19', 'Supporter', '18', '42', '2023-11-06', NULL, '0', '3', 'SA6020000008112501366906', '', '', '', '1998-10-05', '', '5', '1', '', 89, '', '', '5', 14, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(377, 'HOURIAH ABDRABAH ALJAAMALI', '5318', '1117945087', '1976-06-28', '1976-06-28', '0559375341', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2023-11-05', NULL, '0', '3', 'SA4720000001171928929941', '', '', '', '1976-06-28', '', '4', '2', '', 15, '', '', '3', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(378, 'WAFA ALI EIDAN ALAMMARI', '5319', '1118259108', '2001-11-03', '2001-11-03', '0532897814', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2023-11-05', NULL, '0', '6', 'SA7280000453608010559369', '', '', '', '2001-11-03', '', '4', '2', '', 15, '', '', '3', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(379, 'AHAD ABDULLAH ALSHAMRANI', '5321', '1112176316', '2001-06-15', '2001-06-15', '0535104843', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2023-11-19', NULL, '0', '2', 'SA1810000015600000757308', '', '', '', '2001-06-15', '', '4', '2', '', 15, '', '', '1', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(380, 'RAMADAN ABDOU ALI MOHAMED', '5325', '2556327365', '1986-06-11', '1986-06-11', '0546241340', '0', '', '', '', '', '', '2100', '15', '26', 'Supporter', '64', '21', '2023-12-10', NULL, '0', '6', 'SA2380000648608017441963', '', '', '', '1986-06-11', '', '4', '1', '', 93, '', '', '3', 8, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(381, 'YASSIN AHAMMAD KHAN LAL MIA KHAN', '5326', '2249550134', '1975-10-08', '1975-10-08', '0570192492', '0', '', '', '', '', '', '6000', '11', '20', 'Supporter', '18', '21', '2023-12-13', NULL, '0', '3', 'SA6320000001511335059940', '', '', '', '1975-10-08', '', '4', '1', '', 94, '', '', '5', 15, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(382, 'JOMARI CATATA GALVEZ', '5327', '2564430466', '1994-12-01', '1994-12-01', '0573494232', 'P1983191C', '', '', '', '', '', '2175', '11', '16', 'Supporter', '173', '42', '2023-12-27', NULL, '0', '6', 'SA1820000008112564430466', '', '', '', '1994-12-01', '', '5', '1', '', 92, '', '', '2', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(383, 'NAKELL CABRAL CORONEL', '5328', '2564430128', '1983-08-14', '1983-08-14', '0571517824', 'P9054529A', '', '', '', '', '', '2175', '11', '16', 'Supporter', '173', '42', '2023-12-27', NULL, '0', '6', 'SA8580000106608017254353', '', '', '', '1983-08-14', '', '5', '1', '', 92, '', '', '2', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(384, 'DINDO ASANA DOCTOR', '5329', '2564430631', '1984-08-15', '1984-08-15', '0572172817', 'P1720370C', '', '', '', '', '', '2175', '11', '16', 'Supporter', '173', '42', '2023-12-27', NULL, '0', '6', 'SA2520000008112564430631', '', '', '', '1984-08-15', '', '5', '1', '', 92, '', '', '2', 3, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(385, 'WEDYAN AHMED ALJADANI', '5331', '1106579186', '1999-03-01', '1999-03-01', '0541303097', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2023-12-20', NULL, '0', '2', 'SA1510000011300000090605', '', '', '', '1999-03-01', '', '4', '2', '', 15, '', '', '2', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(386, 'IBRAHIM MOHAMMED SHAGH', '5335', '1112227275', '2000-10-07', '2000-10-07', '0561172917', '', '', '', '', '', '', '8000', '11', '17', 'Supporter', '191', '30', '2024-01-01', NULL, '0', '3', 'SA9220000009320353429940', '', '', '', '2000-10-07', '', '1 Year', '1', '', 0, '', '', '3', 5, '', 9.75, '', '', '', '1', NULL, './assets/emp_pics/defult.png', 0, NULL, '2025-07-10 11:30:58'),
(387, 'KHALID AHMED ALGHAMDI', '5337', '1096073109', '1997-08-17', '1997-08-17', '0566153521', '0', '', '', '', '', '', '8000', '15', '24', 'Supporter', '191', '30', '2024-01-09', NULL, '0', '3', 'SA1620000001352076549940', '', '', '', '1997-08-17', '', '6', '1', '', 95, '', '', '7', 2, '', 9.75, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(388, 'MASHAEL SAHMI ALSHAMRANI', '5339', '1071720484', '1991-02-26', '1991-02-26', '0530877302', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2024-01-11', NULL, '0', '6', 'SA3580000129608010027700', '', '', '', '1991-02-26', '', '4', '2', '', 15, '', '', '3', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(389, 'KHALID FAYEZ ALHARBI', '5343', '1073898569', '1991-10-18', '1991-10-18', '0546033785', '0', '', '', '', '', '', '5900', '1', '1', 'Supporter', '191', '30', '2024-02-04', NULL, '0', '3', 'SA3120000001901120499940', '', '', '', '1991-10-18', '', '6', '1', '', 96, '', '', 'ALR/H', 11, '', 9.75, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(390, 'KHALID MOHAMMED ALGHUBAYSH', '5345', '1093675344', '1991-06-17', '1991-06-17', '0569740501', '0', '', '', '', '', '', '7000', '11', '19', 'Supporter', '191', '30', '2024-02-18', NULL, '0', '2', 'SA4910000075500000063600', '', '', '', '1991-06-17', '', '6', '1', '', 31, '', '', '5', 14, '', 9.75, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(391, 'KOYAMON ALIHAJINTAKATH', '5346', '2564292593', '1970-01-15', '1970-01-15', '0556911357', 'W0397328', '', '', '', '', '', '1600', '15', '28', 'Supporter', '101', '42', '2024-02-20', NULL, '0', '6', 'SA1080000648608017878891', '', '', '', '1970-01-15', '', '5', '1', '', 20, '', '', '4', 8, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(392, 'REHAB ABDULLAH ALZAHRANI', '5351', '1097696247', '1996-11-12', '1996-11-12', '0566524933', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2024-02-21', NULL, '0', '6', 'SA4980000502608016000391', '', '', '', '1996-11-12', '', '4', '2', '', 15, '', '', '2', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(393, 'MOHAMMED AHMED ALOMARY', '5352', '1100618469', '1997-01-17', '1997-01-17', '0548304814', '0', '', '', '', '', '', '5000', '11', '16', 'Supporter', '191', '30', '2024-02-25', NULL, '0', '3', 'SA9620000001882865739940', '', '', '', '1997-01-17', '', '6', '1', '', 97, '', '', '2', 3, '', 9.75, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(394, 'HAIFA HUSSAIN MARZOUK', '5357', '1138225253', '1999-10-28', '1999-10-28', '0549562134', '', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2024-04-28', NULL, '0', '2', 'SA6910000011100227902000', '', '', '', '1999-10-28', '', '1 Year', '2', '', 0, '', '', '1', 5, '', 9.75, '', '', '', '1', NULL, './assets/emp_pics/defult.png', 0, NULL, '2025-07-10 11:30:58'),
(395, 'ZAHRA HASSN ALZAHRANI', '5366', '1119601399', '2002-08-07', '2002-08-07', '0540653854', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2024-05-26', NULL, '0', '6', 'SA7080000512608016138902', '', '', '', '2002-08-07', '', '4', '2', '', 15, '', '', '1', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(396, 'AMANI JABER MUTANBAK', '5370', '1106026881', '1998-12-12', '1998-12-12', '0055762372', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2024-06-27', NULL, '0', '6', 'SA2380000618608010305039', '', '', '', '1998-12-12', '', '4', '2', '', 15, '', '', '1', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(397, 'GHURMULLAH SAAD ALZAHRANI', '5371', '1092608684', '1996-10-01', '1996-10-01', '0503002350', '0', '', '', '', '', '', '4500', '11', '17', 'Supporter', '191', '30', '2024-06-30', NULL, '0', '6', 'SA0780000377608010277122', '', '', '', '1996-10-01', '', '6', '1', '', 81, '', '', '1', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(398, 'SALMAH MOHAMMED ALRIZQI', '5376', '1038682520', '1983-11-06', '1983-11-06', '0565826236', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2024-07-04', NULL, '0', '2', 'SA1210000013878366000109', '', '', '', '1983-11-06', '', '4', '2', '', 15, '', '', '1', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(399, 'MOHAMMED TALAL FAYRAQ', '5378', '1099849703', '1997-12-20', '1997-12-20', '0543191612', '0', '', '', '', '', '', '7000', '11', '17', 'Supporter', '191', '30', '2024-07-10', NULL, '0', '3', 'SA4220000001224542369940', '', '', '', '1997-12-20', '', '6', '1', '', 31, '', '', '1', 5, '', 9.75, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(400, 'RANEEM SAAD ALZAHRANI', '5380', '1144201629', '2002-11-19', '2002-11-19', '0558739952', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2024-07-14', NULL, '0', '6', 'SA0480000176608016081133', '', '', '', '2002-11-19', '', '4', '2', '', 15, '', '', '1', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(401, 'LOAI MOHAMMED ASHE', '5381', '1104320310', '1999-07-04', '1999-07-04', '0568696464', '0', '', '', '', '', '', '8000', '11', '16', 'Supporter', '191', '30', '2024-07-17', NULL, '0', '2', 'SA2410000013847130007707', '', '', '', '1999-07-04', '', '6', '1', '', 67, '', '', '2', 3, '', 9.75, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(402, 'AMAL AWAD ALHMDI', '5383', '1090836576', '1414-10-28', '1994-04-10', '0545848538', '0', '', '', '', '', '', '5500', '2', '2', 'Supporter', '191', '30', '2024-07-24', NULL, '0', '2', 'SA3910000011100471971903', '', '', '', '1994-04-10', '1414-10-28', '6', '2', '', 19, '', '', '1', 4, '', 9.75, '', '', 'C', '1', NULL, './assets/emp_pics/defultFemale.jpg', 1, NULL, '2025-07-10 11:30:58'),
(403, 'FAISAL SALEH ALQAHTANI', '5384', '1101218269', '1998-09-11', '1998-09-11', '0555113725', '0', '', '', '', '', '', '8000', '11', '20', 'Supporter', '191', '30', '2024-07-29', NULL, '0', '6', 'SA1280000532608010359880', '', '', '', '1998-09-11', '', '6', '1', '', 67, '', '', '6', 15, '', 9.75, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(404, 'FATIMAH SALEM FARAJ', '5388', '107838261', '1981-11-21', '1981-11-21', '0535413350', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2024-08-05', NULL, '0', '2', 'SA2010000010375795000105', '', '', '', '1981-11-21', '', '4', '2', '', 15, '', '', '3', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(405, 'ALWAH MOHD BINALI ALSHEHRI', '5390', '1062960628', '1982-09-05', '1982-09-05', '0533429195', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2024-08-05', NULL, '0', '3', 'SA1820000009323969129940', '', '', '', '1982-09-05', '', '4', '2', '', 15, '', '', '2', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(406, 'DALAL ABDU MUTEB MASOUD', '5391', '1097931800', '1998-01-20', '1998-01-20', '0537969858', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2024-08-05', NULL, '0', '2', 'SA4110000010100000287107', '', '', '', '1998-01-20', '', '4', '2', '', 15, '', '', '2', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(407, 'ROAA SULTAN ALQURASHI', '5392', '1125720837', '1423-11-15', '2003-01-18', '0005039607', '0', '', '', '', '', '', '6000', '2', '2', 'Supporter', '191', '30', '2024-09-01', NULL, '0', '6', 'SA3380000671608017143619', '', '', '', '2003-01-18', '1423-11-15', '6', '2', '', 19, '', '', '1', 4, '', 9.75, '', '', 'C', '1', NULL, './assets/emp_pics/defultFemale.jpg', 1, NULL, '2025-07-10 11:30:58'),
(408, 'ABDELRAHMAN AHMED ELSAYED MOHAMED', '5396', '2508278336', '1998-05-08', '1998-05-08', '0539505105', 'A29121954', '', '', '', '', '', '3500', '11', '19', 'Supporter', '64', '21', '2024-08-18', NULL, '0', '6', 'SA2180000458608016182351', '', '', '', '1998-05-08', '', '4', '1', '', 70, '', '', '5', 14, '', NULL, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(409, 'SALIHAH ABDULLAH ZAYBI', '5397', '1087304505', '1994-08-06', '1994-08-06', '0501460636', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2024-10-23', NULL, '0', '2', 'SA9710000015600000975203', '', '', '', '1994-08-06', '', '4', '2', '', 15, '', '', '3', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(410, 'AFNAN OBAID ALMUWALLAD', '5399', '1091795797', '1993-06-27', '1993-06-27', '0557938005', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2024-10-23', NULL, '0', '2', 'SA4510000011100130075507', '', '', '', '1993-06-27', '', '4', '2', '', 15, '', '', '1', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(411, 'HASAN IMAM SAIYED HASAN', '5401', '2557580731', '1980-08-10', '1980-08-10', '0574585162', 'Y4495552', '', '', '', '', '', '2500', '11', '19', 'Supporter', '101', '21', '2024-11-21', NULL, '0', '6', 'SA3980000996608017760865', '', '', '', '1980-08-10', '', '4', '1', '', 98, '', '', '5', 14, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(412, 'MUHAMMAD SHAH', '5402', '2556145304', '1990-11-22', '1990-11-22', '0571257780', 'AP5109261', '', '', '', '', '', '1500', '11', '19', 'Supporter', '166', '21', '2024-11-19', NULL, '0', '6', 'SA8380000694608017905929', '', '', '', '1990-11-22', '', '4', '1', '', 93, '', '', '5', 14, '', NULL, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(413, 'RAGHAD IBRAHIM ALMARHABI', '5403', '1118996121', '2001-12-25', '2001-12-25', '0557852371', '', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2024-12-08', NULL, '0', '6', 'SA3880000176608016022418', '', '', '', '2001-12-25', '', '1 Year', '2', '', 0, '', '', '1', 5, '', 9.75, '', '', '', '1', NULL, './assets/emp_pics/defult.png', 0, NULL, '2025-07-10 11:30:58'),
(414, 'ASHJAN YAHYA M HAYHAN', '5406', '1199525948', '1996-07-18', '1996-07-18', '0557681081', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2024-12-11', NULL, '0', '6', 'SA6680000102608166141165', '', '', '', '1996-07-18', '', '4', '2', '', 15, '', '', '3', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(415, 'FAHAD AHMED ALSAYYALI', '5407', '1106726654', '2000-01-10', '2000-01-10', '0503099855', '0', '', '', '', '', '', '7000', '15', '24', 'Supporter', '191', '30', '2024-12-22', NULL, '0', '6', 'SA1080000526608010156516', '', '', '', '2000-01-10', '', '6', '1', '', 31, '', '', '1', 2, '', 9.75, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(416, 'SHARIFAH AHMED ALSALHI', '5408', '1070652175', '1452-01-01', '2030-05-03', '0509004301', '0', '', 'sharifahal-salhi@hotmail.com', 's.alsalhi@almutlak.com', '509004301', 'sharifah', '8150', '5', '3', 'Manager', '191', '30', '2025-01-07', NULL, '0', '3', 'SA1120000009324735569940', '', '', '', '1987-12-05', '1408-04-13', '6', '2', 'A+', 44, 'single', '', '1', 4, 'jeddah', 9.75, '', '', 'B', '1', NULL, './assets/emp_pics/defultFemale.jpg', 1, NULL, '2025-07-10 11:30:58'),
(417, 'FATIMAH HUSSAIN ALSHHERI', '5409', '1113114563', '2001-08-20', '2001-08-20', '0504418671', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2025-01-14', NULL, '0', '6', 'SA5480000321608016027684', '', '', '', '2001-08-20', '', '4', '2', '', 15, '', '', '1', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(418, 'HANAN YAHYA ALBAHIRI', '5410', '1117679132', '1995-09-16', '1995-09-16', '0549735407', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2025-01-14', NULL, '0', '2', 'SA3210000010399358000105', 'terminat', 'no', '2025-07-07T09:20:24+03:00', '1995-09-16', '', '4', '2', '', 15, '', '', '3', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 0, NULL, '2025-07-10 11:30:58'),
(419, 'ABDULLAH SALEM ALHARTHI', '5413', '1108275288', '1998-04-29', '1998-04-29', '0536635533', '0', '', '', '', '', '', '5000', '15', '22', 'Supporter', '191', '30', '2025-01-22', NULL, '0', '6', 'SA5180000467608010235261', '', '', '', '1998-04-29', '', '6', '1', '', 17, '', '', '4', 7, '', 9.75, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(420, 'NAGI AHMED ABDELFATTAH KANDIL', '5414', '2596230710', '1976-05-09', '1976-05-09', '', 'A37348500', '', '', '', '', '', '17850', '15', '21', 'Supporter', '64', '30', '2025-01-26', NULL, '0', '6', 'SA5280000858608011507733', '', '', '', '1976-05-09', '', '6', '1', '', 54, '', '', '6', 1, '', NULL, '', '', 'B', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(421, 'HASSAN DAHISH ALZAHRANI', '5415', '1124259522', '2000-06-27', '2000-06-27', '0503542067', '0', '', '', '', '', '', '4400', '15', '21', 'Supporter', '191', '30', '2025-02-10', NULL, '0', '6', 'SA8880000176608010651501', '', '', '', '2000-06-27', '', '6', '1', '', 99, '', '', '4', 1, '', 9.75, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(422, 'REEM ALI M ALQARNI', '5416', '1090128735', '1995-02-18', '1995-02-18', '0554921287', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2025-02-17', NULL, '0', '2', 'SA8710000012974082000109', '', '', '', '1995-02-18', '', '4', '2', '', 15, '', '', '3', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(423, 'RASHA HASSAN ALI OSAYLAH', '5418', '1068855558', '1985-04-02', '1985-04-02', '0557453142', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2025-02-17', NULL, '0', '2', 'SA9610000010761499000104', '', '', '', '1985-04-02', '', '4', '2', '', 15, '', '', '3', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(424, 'AMANI EASSA MA ALHAZZME', '5420', '1033722966', '1982-11-11', '1982-11-11', '0056331473', '0', '', '', '', '', '', '5500', '11', '17', 'Supporter', '191', '30', '2025-03-10', NULL, '0', '6', 'SA2980000453608010237909', '', '', '', '1982-11-11', '', '6', '2', '', 78, '', '', '3', 5, '', 9.75, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(425, 'ABDULRAHMAN SAMEER MALKI', '5422', '1122098906', '1447-12-18', '2026-06-04', '0565331473', '', '', 'abdomalki500@gmail.com', '', '545555274', 'ahmed malki', '6500', '5', '5', 'Supporter', '191', '30', '2025-04-24', NULL, '0', '6', 'SA7780000443608016007978', '', '', '', '2001-10-27', '1422-08-11', '6', '1', 'O+', 44, 'single', '', '1', 4, 'jeddah - almuhammadiah', 9.75, '', '', 'B', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(426, 'ABRAR MOHAMMED ALSAHBI', '5423', '1057101337', '1453-07-16', '2031-11-01', '0558922759', '0', '', '', '', '558922757', 'mama', '5500', '5', '5', 'Supporter', '191', '30', '2025-04-29', NULL, '0', '3', 'SA0920000001701706399940', '', '', '', '1988-12-10', '1409-05-01', '6', '2', 'B+', 44, 'single', '', '1', 4, '', 9.75, '', '', 'B', '1', NULL, './assets/emp_pics/defultFemale.jpg', 1, NULL, '2025-07-10 11:30:58'),
(427, 'RUWAYDA AHMED HAMDI', '5424', '1073176685', '1992-09-09', '1992-09-09', '0536759512', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2025-04-29', NULL, '0', '2', 'SA9110000011100388808502', '', '', '', '1992-09-09', '', '4', '2', '', 15, '', '', '3', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(428, 'ARWA ALI ALQARNI', '5425', '1128054267', '2003-02-28', '2003-02-28', '0558393629', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2025-04-29', NULL, '0', '6', 'SA7080000176608016290338', '', '', '', '2003-02-28', '', '4', '2', '', 15, '', '', '3', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(429, 'MONA IBRAHIM ALSAHER', '5426', '1082615624', '1412-12-14', '1992-06-15', '0565121724', '0', '', '', '', '565121724', 'mona', '5500', '5', '5', 'Supporter', '191', '21', '2025-05-04', NULL, '0', '6', 'SA7880000546608010048271', '', '', '', '1992-06-15', '1412-12-14', '4', '2', 'A+', 100, 'married', '', '1', 5, '', 9.75, '', '', 'C', '1', NULL, './assets/emp_pics/defultFemale.jpg', 1, NULL, '2025-07-10 11:30:58'),
(430, 'SUDA ALI M ALMUNTASHIRI', '5428', '1116133172', '1999-11-09', '1999-11-09', '0502933919', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2025-04-29', NULL, '0', '2', 'SA1810000011100230263801', '', '', '', '1999-11-09', '', '4', '2', '', 15, '', '', '6', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(431, 'RUYUF OTHMAN M MATLAA', '5431', '11431116638', '2003-08-24', '2003-08-24', '0507297568', '0', '', '', '', '', '', '4200', '11', '17', 'Supporter', '191', '21', '2025-05-14', NULL, '0', '6', 'SA3480000618608010302028', '', '', '', '2003-08-24', '', '4', '2', '', 15, '', '', '1', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(432, 'ANEES AFZAL MUHAMMAD AFZAL', '5430', '2337318717', '1447-12-26', '2026-06-12', '0599723451', 'EY1514452', '2027-08-08', 'aneesmug2007@hotmail.com', 'a.afzal@almutlak.com', '0538092933 - +923456539306', 'Rabia Anees', '9000', '6', '3', 'Manager', '166', '30', '2025-05-18', NULL, '0', '6', 'SA2480000358608010178729', '', '', '', '1989-04-14', '1409-09-08', '6', '1', 'B+', 102, 'married', 'XXL', '1', 4, 'Aziziya Jeddah', NULL, '43506563', '2026-02-04', 'B', '1', '', './assets/emp_pics/54304321751547932.png', 1, NULL, '2025-08-19 07:10:56'),
(433, 'YASSER ABDULGADER HASSAN HOWSASI', '5433', '1064586629', '', '', '0569133648', '', '', '', '', '', '', '5500', '1', '1', 'Supporter', '191', '21', '2025-05-18', NULL, '0', '2', 'SA3710000010251401000103', '', '', '', '1982-12-25', '', 'd', '1', '', 78, '', '', '5', 3, '', 9.75, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(434, 'AHMED ABDELHADI MOHAMED HASSAN SENNA', '5434', '2606316004', '', '', '0558336282', '', '', '', '', '', '', '4000', '1', '1', 'Supporter', '66', '21', '2025-05-18', NULL, '0', '', 'SA2580000859608017281027', '', '', '', '1977-11-22', '', '4', '1', '', 93, '', '', '5', 14, '', NULL, '', '', 'C', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(435, 'EIDAH SALEM MHAMMED ALHARBI', '5435', '1053064240', '', '', '0566482893', '', '', '', '', '', '', '4200', '1', '1', 'Supporter', '191', '21', '2025-05-19', NULL, '0', '6', 'SA4280000263608016004998', '', '', '', '1984-04-18', '', '4', '2', '', 15, '', '', '7', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defultFemale.jpg', 1, NULL, '2025-07-10 11:30:58'),
(436, 'RAZAN SULAIMAN ALMARHABI', '5436', '1087369946', '', '', '0500511691', '', '', '', '', '', '', '4200', '1', '1', 'Supporter', '191', '21', '2025-05-19', NULL, '0', '6', 'SA7380000535608010065013', '', '', '', '1990-12-27', '', '4', '2', '', 15, '', '', '2', 5, '', 9.75, '', '', 'CLT', '1', NULL, './assets/emp_pics/defultFemale.jpg', 1, NULL, '2025-07-10 11:30:58'),
(437, 'YOUSSEF HASSAN MUSTAFA', '5439', '2032502128', '1398-02-20', '1978-01-30', '0558022352', 'P00112476', '', '', '', '', '', '5500', '11', '17', 'Supporter', '168', '30', '2015-03-01', NULL, '0', '2', 'SA1810000010851822000106', '', '', '', '1978-01-30', '1398-02-20', '6', '1', '', 17, '', '', '3', 5, '', NULL, '', '', '', '1', NULL, './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(504, 'AMMA YASIR ALAHMADI', '5243', '1101774352', '', '', '0598055099', '', '', '', '', '', '', '4000', '16', '19', 'Supporter', '191', '21', '2023-03-26', NULL, '0', '6', 'SA8780000463608010702256', '', '', '', '1998-11-21', '', '5', '', '', 103, '', '', '2', 14, '', 9.75, '', '', '', '1', '', './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(503, 'ABDULRAHMAN ABDULAZIZ HANIF', '5242', '1126023447', '', '', '0563369172', '', '', '', '', '', '', '4000', '16', '19', 'Supporter', '191', '21', '2023-03-26', NULL, '0', '6', 'SA7480000550608016037214', '', '', '', '2004-08-31', '', '5', '', '', 103, '', '', '2', 14, '', 9.75, '', '', '', '1', '', './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(502, 'ABDULRAHMAN ABDULLAH ALJOHANI', '5241', '1105645947', '', '', '0565622969', '', '', '', '', '', '', '4000', '16', '19', 'Supporter', '191', '21', '2023-03-26', NULL, '0', '6', 'SA8380000538608016002538', '', '', '', '1999-09-10', '', '5', '', '', 103, '', '', '2', 14, '', 9.75, '', '', '', '1', '', './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(501, 'KHALED ABDULGADER BASHMAKH', '5235', '1115865568', '', '', '0563002113', '', '', '', '', '', '', '4000', '16', '19', 'Supporter', '191', '21', '2023-03-26', NULL, '0', '6', 'SA8080000376608016257848', '', '', '', '2002-02-25', '', '5', '', '', 103, '', '', '2', 14, '', 9.75, '', '', '', '1', '', './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(500, 'ABDULRAHMAN YAHYA ALGHARNI', '5225', '1151067996', '', '', '0535523234', '', '', '', '', '', '', '4000', '16', '19', 'Supporter', '191', '21', '2023-03-26', NULL, '0', '6', 'SA3580000355608016711534', '', '', '', '2004-04-08', '', '5', '', '', 103, '', '', '2', 14, '', 9.75, '', '', '', '1', '', './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(499, 'MESHARI ABDULRAHMAN ALGHAMDI', '5221', '1107555219', '', '', '0530303258', '', '', '', '', '', '', '4000', '16', '19', 'Supporter', '191', '21', '2023-03-26', NULL, '0', '6', 'SA2380000355608016249642', '', '', '', '2000-05-03', '', '5', '', '', 103, '', '', '3', 14, '', 9.75, '', '', '', '1', '', './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(498, 'MOIED KHALED BASHWAYA', '5218', '1121457582', '', '', '0582007781', '', '', '', '', '', '', '4000', '16', '19', 'Supporter', '191', '21', '2023-03-26', NULL, '0', '6', 'SA1880000525608016072064', '', '', '', '2003-08-08', '', '5', '', '', 103, '', '', '3', 14, '', 9.75, '', '', '', '1', '', './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(497, 'ABDULRAHMAN ABDULLAH ALHUZLI', '5216', '1121725525', '', '', '0545551610', '', '', '', '', '', '', '4000', '16', '19', 'Supporter', '191', '21', '2023-03-26', NULL, '0', '6', 'SA4780000141608010596856', '', '', '', '2002-10-13', '', '5', '', '', 103, '', '', '3', 14, '', 9.75, '', '', '', '1', '', './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(496, 'TALAL ABDULKARIM ALGHAMDI', '5213', '1132819598', '', '', '0561209224', '', '', '', '', '', '', '4000', '16', '19', 'Supporter', '191', '21', '2023-03-26', NULL, '0', '6', 'SA0480000598608016171777', '', '', '', '2000-05-14', '', '5', '', '', 103, '', '', '3', 14, '', 9.75, '', '', '', '1', '', './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(495, 'ALI ABDULRAZAK AL OFAIREET', '5245', '1122774936', '', '', '0543269799', '', '', '', '', '', '', '4000', '16', '26', 'Supporter', '191', '21', '2023-04-11', NULL, '0', '2', 'SA3710000011100358360107', '', '', '', '2003-11-26', '', '5', '', '', 103, '', '', '3', 8, '', 9.75, '', '', '', '1', '', './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(494, 'FAHAD ALI AL KAYADI', '5246', '1124321793', '', '', '0563119077', '', '', '', '', '', '', '4000', '16', '17', 'Supporter', '191', '21', '2023-04-11', NULL, '0', '6', 'SA8080000648608016160440', '', '', '', '2004-04-05', '', '5', '', '', 103, '', '', '3', 5, '', 9.75, '', '', '', '1', '', './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(493, 'AMMAR MANSOUR ALSHAMRANI', '5224', '1134806338', '', '', '0560507823', '', '', '', '', '', '', '4000', '16', '17', 'Supporter', '191', '21', '2023-03-26', NULL, '0', '6', 'SA1180000336608016114329', '', '', '', '2003-09-24', '', '5', '', '', 103, '', '', '3', 5, '', 9.75, '', '', '', '1', '', './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(492, 'BADER FAHAD ALSHAIBANI', '5222', '1111700694', '', '', '0534105093', '', '', '', '', '', '', '4000', '16', '17', 'Supporter', '191', '21', '2023-03-26', NULL, '0', '6', 'SA6580000103608016105898', '', '', '', '2001-05-12', '', '5', '', '', 103, '', '', '3', 5, '', 9.75, '', '', '', '1', '', './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(491, 'MOHAMMED KHALID ALSHEHRI', '5219', '112238067', '', '', '0549940806', '', '', '', '', '', '', '4000', '16', '17', 'Supporter', '191', '21', '2023-03-26', NULL, '0', '6', 'SA1880000591608016082344', '', '', '', '2003-10-14', '', '5', '', '', 103, '', '', '3', 5, '', 9.75, '', '', '', '1', '', './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(490, 'ABDULLAH HAMED ALSUFYANI', '5214', '1105637928', '', '', '0591092822', '', '', '', '', '', '', '4000', '16', '17', 'Supporter', '191', '21', '2023-03-26', NULL, '0', '6', 'SA9580000608608010547228', '', '', '', '1999-10-31', '', '5', '', '', 103, '', '', '3', 5, '', 9.75, '', '', '', '1', '', './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(489, 'ABDULRAHMAN SALEM BIN AFIF', '5211', '1114665852', '', '', '0557280860', '', '', '', '', '', '', '4000', '16', '17', 'Supporter', '191', '21', '2023-03-26', NULL, '0', '2', 'SA4810000011100051048001', '', '', '', '2001-11-22', '', '5', '', '', 103, '', '', '3', 5, '', 9.75, '', '', '', '1', '', './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(488, 'JABER YAHYA ALFAIFI', '5209', '1133635415', '', '', '0572520600', '', '', '', '', '', '', '4000', '16', '17', 'Supporter', '191', '21', '2023-03-26', NULL, '0', '6', 'SA9180000259608016015859', '', '', '', '2002-10-22', '', '5', '', '', 103, '', '', '3', 5, '', 9.75, '', '', '', '1', '', './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(487, 'ANAS MOHAMMED ALGHAMDI', '5205', '1122756107', '', '', '0556111042', '', '', '', '', '', '', '4000', '16', '17', 'Supporter', '191', '21', '2023-03-26', NULL, '0', '6', 'SA5980000156608016019160', '', '', '', '2003-11-19', '', '5', '', '', 103, '', '', '3', 5, '', 9.75, '', '', '', '1', '', './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(486, 'MUTAZ ALI OTAYF', '5239', '1116290568', '', '', '0506745314', '', '', '', '', '', '', '4000', '16', '16', 'Supporter', '191', '21', '2023-03-26', NULL, '0', '6', 'SA2380000189608016010301', '', '', '', '2002-05-20', '', '5', '', '', 103, '', '', '2', 3, '', 9.75, '', '', '', '1', '', './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(485, 'ABDULSAMAD ABUBAKR BAWADUD', '5237', '1125837359', '', '', '0540935260', '', '', '', '', '', '', '4000', '16', '16', 'Supporter', '191', '21', '2023-03-26', NULL, '0', '6', 'SA2180000259608016168633', '', '', '', '2004-08-10', '', '5', '', '', 103, '', '', '2', 3, '', 9.75, '', '', '', '1', '', './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(484, 'KHALID ABDULLAH ALJOHANI', '5236', '1123727834', '', '', '0543826290', '', '', '', '', '', '', '4000', '16', '16', 'Supporter', '191', '21', '2023-03-26', NULL, '0', '6', 'SA0680000635608016087150', '', '', '', '2004-02-21', '', '5', '', '', 103, '', '', '2', 3, '', 9.75, '', '', '', '1', '', './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(483, 'ALI MOHAMMED ALYAMI', '5234', '1124666437', '', '', '0546980998', '', '', '', '', '', '', '4000', '16', '16', 'Supporter', '191', '21', '2023-03-26', NULL, '0', '6', 'SA6680000463608016201758', '', '', '', '2003-07-01', '', '5', '', '', 103, '', '', '2', 3, '', 9.75, '', '', '', '1', '', './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(482, 'SABER KAMAL ALMALKI', '5233', '1173251925', '', '', '0537597549', '', '', '', '', '', '', '4000', '16', '16', 'Supporter', '191', '21', '2023-03-26', NULL, '0', '6', 'SA8780000463608010210078', '', '', '', '2001-03-28', '', '5', '', '', 103, '', '', '2', 3, '', 9.75, '', '', '', '1', '', './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(481, 'ABDULAZIZ ABDULLAH ALMAGHRABI', '5232', '1120691579', '', '', '0547197018', '', '', '', '', '', '', '4000', '16', '16', 'Supporter', '191', '21', '2023-03-26', NULL, '0', '2', 'SA5810000075300000003308', '', '', '', '2003-05-24', '', '5', '', '', 103, '', '', '2', 3, '', 9.75, '', '', '', '1', '', './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(480, 'ABDULLAH AHMED MAHNASHI', '5231', '1130070921', '', '', '0553627640', '', '', '', '', '', '', '4000', '16', '16', 'Supporter', '191', '21', '2023-03-26', NULL, '0', '6', 'SA9380000176608016211839', '', '', '', '2004-02-01', '', '5', '', '', 103, '', '', '2', 3, '', 9.75, '', '', '', '1', '', './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(479, 'MAJED ALI ALSHEHRI', '5230', '1126053519', '', '', '0507157907', '', '', '', '', '', '', '4000', '16', '16', 'Supporter', '191', '21', '2023-03-26', NULL, '0', '6', 'SA4080000176608016035089', '', '', '', '2000-10-23', '', '5', '', '', 103, '', '', '2', 3, '', 9.75, '', '', '', '1', '', './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(478, 'MOAYAD BANDAR ALAHMADI', '5229', '1104205479', '', '', '0567860007', '', '', '', '', '', '', '4000', '16', '16', 'Supporter', '191', '21', '2023-03-26', NULL, '0', '6', 'SA7480000327608016015287', '', '', '', '1999-07-04', '', '5', '', '', 103, '', '', '2', 3, '', 9.75, '', '', '', '1', '', './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(477, 'ABDULMAJEED AHMED HAZAZI', '5228', '1120658792', '', '', '0551264463', '', '', '', '', '', '', '4000', '16', '16', 'Supporter', '191', '21', '2023-03-26', NULL, '0', '6', 'SA0580000330608016003866', '', '', '', '2003-05-27', '', '5', '', '', 103, '', '', '2', 3, '', 9.75, '', '', '', '1', '', './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(476, 'ALI ATIAH ALBARAKATI', '5227', '1156258632', '', '', '0548085831', '', '', '', '', '', '', '4000', '16', '16', 'Supporter', '191', '21', '2023-03-26', NULL, '0', '6', 'SA5880000618608010641581', '', '', '', '2002-11-04', '', '5', '', '', 103, '', '', '2', 3, '', 9.75, '', '', '', '1', '', './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(475, 'MAJED MOHAMMED ALGHAMDI', '5226', '1121817611', '', '', '0560674768', '', '', '', '', '', '', '4000', '16', '16', 'Supporter', '191', '21', '2023-03-26', NULL, '0', '2', 'SA3610000011800000985706', '', '', '', '2003-08-10', '', '5', '', '', 103, '', '', '2', 3, '', 9.75, '', '', '', '1', '', './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(474, 'AHMED TALAL FARSI', '5207', '1122987181', '', '', '0557466003', '', '', '', '', '', '', '4000', '16', '16', 'Supporter', '191', '21', '2023-03-26', NULL, '0', '2', 'SA0410000011100214347905', '', '', '', '2003-12-06', '', '5', '', '', 103, '', '', '3', 3, '', 9.75, '', '', '', '1', '', './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(473, 'ABDULLAH FAYEZ BABAQI', '5215', '1118483609', '', '', '0568384359', '', '', '', '', '', '', '4000', '16', '24', 'Supporter', '191', '21', '2023-03-26', NULL, '0', '2', 'SA6710000011100134036009', '', '', '', '2002-11-21', '', '5', '', '', 103, '', '', '3', 2, '', 9.75, '', '', '', '1', '', './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(505, 'YOUSEF ABDULKHALIQ SAGHI', '5244', '1128383005', '', '', '0523824250', '', '', '', '', '', '', '4000', '16', '19', 'Supporter', '191', '21', '2023-03-26', NULL, '0', '2', 'SA9310000089100000664304', '', '', '', '2004-06-12', '', '5', '', '', 103, '', '', '2', 14, '', 9.75, '', '', '', '1', '', './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(506, 'AZZAM NASSER ALJOHANI', '5217', '1115064873', '', '', '0568860219', '', '', '', '', '', '', '4000', '16', '20', 'Supporter', '191', '21', '2023-03-26', NULL, '0', '6', 'SA7080000150608016025858', '', '', '', '2000-07-06', '', '5', '', '', 103, '', '', '3', 15, '', 9.75, '', '', '', '1', '', './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58'),
(507, 'ABDULLAH SALEEM ALMALKI', '5238', '1106196965', '', '', '0545514106', '', '', '', '', '', '', '4000', '16', '20', 'Supporter', '191', '21', '2023-03-26', NULL, '0', '6', 'SA8980000270608010379645', '', '', '', '1999-01-12', '', '5', '', '', 103, '', '', '2', 15, '', 9.75, '', '', '', '1', '', './assets/emp_pics/defult.png', 1, NULL, '2025-07-10 11:30:58');

-- --------------------------------------------------------

--
-- Table structure for table `employee_assets`
--

CREATE TABLE `employee_assets` (
  `id` int(11) NOT NULL,
  `emp_id` varchar(11) NOT NULL,
  `asset_id` int(11) NOT NULL,
  `serial_number` varchar(255) DEFAULT NULL COMMENT 'Serial number or unique identifier',
  `description` text DEFAULT NULL COMMENT 'e.g., Model, color, phone number for SIM',
  `assigned_date` date NOT NULL,
  `return_date` date DEFAULT NULL,
  `status` enum('Assigned','Returned','Lost','Damaged') NOT NULL DEFAULT 'Assigned',
  `return_attachment` varchar(255) DEFAULT NULL COMMENT 'File path for the proof of return',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_assets`
--

INSERT INTO `employee_assets` (`id`, `emp_id`, `asset_id`, `serial_number`, `description`, `assigned_date`, `return_date`, `status`, `return_attachment`, `created_at`) VALUES
(1, '5430', 2, '7439805vmn3b48cm', NULL, '2023-08-01', '2025-08-28', 'Assigned', './../../assets/assets_return/return_1_1756380166.pdf', '2025-08-27 13:14:03'),
(3, '5430', 4, '32rtw4ghb', 'toyota car', '2025-08-27', '2025-08-28', 'Assigned', './../../assets/assets_return/return_3_1756366939.pdf', '2025-08-27 17:57:28');

-- --------------------------------------------------------

--
-- Table structure for table `employee_temp_contants`
--

CREATE TABLE `employee_temp_contants` (
  `id` int(11) NOT NULL,
  `emp_id` int(11) NOT NULL,
  `type` varchar(100) NOT NULL,
  `new_value` text DEFAULT NULL,
  `path` varchar(255) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `update_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `employee_temp_contants`
--

INSERT INTO `employee_temp_contants` (`id`, `emp_id`, `type`, `new_value`, `path`, `notes`, `status`, `created_at`, `update_at`) VALUES
(16, 1928, 'Profile Picture', NULL, './../../assets/emp_pics/emp_1928/avatar_1755693449.png', '', 'Approved', '2025-08-20 12:37:50', '2025-08-20 12:37:29');

-- --------------------------------------------------------

--
-- Table structure for table `emp_docu`
--

CREATE TABLE `emp_docu` (
  `id` int(11) NOT NULL,
  `emp_id` varchar(255) NOT NULL,
  `pgid` int(11) NOT NULL,
  `docu_typ` varchar(100) NOT NULL,
  `docu_ext` varchar(10) NOT NULL,
  `path` varchar(255) NOT NULL,
  `status` varchar(10) NOT NULL DEFAULT 'A',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `emp_docu`
--

INSERT INTO `emp_docu` (`id`, `emp_id`, `pgid`, `docu_typ`, `docu_ext`, `path`, `status`, `created_at`) VALUES
(16, '1822', 9, 'Iqama', 'png', '9IQAMA69221752411220.png', 'A', '2025-07-13 12:53:40');

-- --------------------------------------------------------

--
-- Table structure for table `emp_eos`
--

CREATE TABLE `emp_eos` (
  `id` int(11) NOT NULL,
  `emp_id` varchar(50) NOT NULL,
  `contract_type` varchar(100) NOT NULL,
  `eos_reason` varchar(255) NOT NULL,
  `leaving_reason` varchar(100) NOT NULL,
  `leaving_reason_ar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `eos_amount` varchar(100) NOT NULL,
  `joining_date` varchar(100) NOT NULL,
  `end_date` varchar(100) NOT NULL,
  `t_years` varchar(100) NOT NULL,
  `t_months` varchar(100) NOT NULL,
  `t_days` varchar(100) NOT NULL,
  `curt_month_days` varchar(100) NOT NULL,
  `curt_month_salry` varchar(100) NOT NULL,
  `anul_vac_days` varchar(100) NOT NULL,
  `anul_vac_salry` varchar(100) NOT NULL,
  `deduct` varchar(100) NOT NULL,
  `net_payment` varchar(100) NOT NULL,
  `notes` longtext NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `emp_eos`
--

INSERT INTO `emp_eos` (`id`, `emp_id`, `contract_type`, `eos_reason`, `leaving_reason`, `leaving_reason_ar`, `eos_amount`, `joining_date`, `end_date`, `t_years`, `t_months`, `t_days`, `curt_month_days`, `curt_month_salry`, `anul_vac_days`, `anul_vac_salry`, `deduct`, `net_payment`, `notes`, `created_at`) VALUES
(5, '5035', '1', '6', 'Termination of the contract upon its expiration', 'إنتهاء العقد بإنتهاء مدته', '15144.44', '2021-12-05', '2025-09-17', '3', '9', '12', '17', '4533.33', '30', '8000', '0', '27677.77', 'end of contract', '2025-09-04 13:35:32');

-- --------------------------------------------------------

--
-- Table structure for table `emp_gosi`
--

CREATE TABLE `emp_gosi` (
  `id` int(11) NOT NULL,
  `emp_id` varchar(150) NOT NULL,
  `gosi_no` varchar(100) NOT NULL,
  `amount` varchar(100) NOT NULL,
  `date_greg` varchar(100) NOT NULL,
  `date_hijri` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `emp_incur`
--

CREATE TABLE `emp_incur` (
  `id` int(11) NOT NULL,
  `emp_id` int(11) NOT NULL,
  `class` varchar(50) NOT NULL,
  `incur_comp` varchar(150) NOT NULL,
  `incur_no` varchar(150) NOT NULL,
  `incur_exp` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `emp_inv_attachment`
--

CREATE TABLE `emp_inv_attachment` (
  `id` int(11) NOT NULL,
  `emp_id` int(11) NOT NULL,
  `srno` varchar(100) NOT NULL,
  `attachment` varchar(255) NOT NULL,
  `docu_ext` varchar(50) NOT NULL,
  `note` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `apprv_amount` decimal(10,2) NOT NULL,
  `status` varchar(150) NOT NULL DEFAULT 'draft',
  `inv_count` int(11) NOT NULL,
  `deleted` int(11) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `emp_loan`
--

CREATE TABLE `emp_loan` (
  `id` int(11) NOT NULL,
  `emp_id` varchar(20) NOT NULL,
  `loan_type` enum('regular','emergency') NOT NULL DEFAULT 'regular',
  `loan_amount` decimal(10,2) NOT NULL,
  `interest_rate` decimal(5,2) NOT NULL DEFAULT 1.40,
  `total_payable` decimal(10,2) NOT NULL,
  `monthly_deduction` decimal(10,2) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'dept_manager_pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `dept_manager_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `hr_manager_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `hr_assistant_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `finance_manager_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `gm_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `finance_assistant_status` enum('pending','processed') NOT NULL DEFAULT 'pending',
  `disbursement_receipt_id` varchar(255) DEFAULT NULL,
  `disbursement_attachment` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `emp_loan`
--

INSERT INTO `emp_loan` (`id`, `emp_id`, `loan_type`, `loan_amount`, `interest_rate`, `total_payable`, `monthly_deduction`, `start_date`, `end_date`, `status`, `created_at`, `dept_manager_status`, `hr_manager_status`, `hr_assistant_status`, `finance_manager_status`, `gm_status`, `finance_assistant_status`, `disbursement_receipt_id`, `disbursement_attachment`) VALUES
(15, '5430', 'regular', 3500.00, 0.00, 3500.00, 875.00, '2025-08-28', '2025-11-28', 'approved', '2025-08-28 11:59:38', 'approved', 'approved', 'approved', 'approved', 'approved', 'processed', '2w3erfgb', 'disbursement_15_1756382447.pdf');

-- --------------------------------------------------------

--
-- Table structure for table `emp_loan_approvals`
--

CREATE TABLE `emp_loan_approvals` (
  `id` int(11) NOT NULL,
  `loan_id` int(11) NOT NULL,
  `approver_id` varchar(50) NOT NULL,
  `approver_role` varchar(100) NOT NULL,
  `status` enum('approved','rejected') NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `emp_loan_approvals`
--

INSERT INTO `emp_loan_approvals` (`id`, `loan_id`, `approver_id`, `approver_role`, `status`, `notes`, `created_at`) VALUES
(53, 15, '2337318717', 'dept_manager', 'approved', 'Dept manager approved.', '2025-08-28 11:59:44'),
(54, 15, '2293543845', 'hr_assistant', 'approved', 'HR Assistant approved with modifications. New Amount: 3500, New Installments: 4.', '2025-08-28 11:59:54'),
(55, 15, '1070652175', 'hr_manager', 'approved', 'Hr manager approved.', '2025-08-28 12:00:02'),
(56, 15, '2103034787', 'finance_manager', 'approved', 'Finance manager approved.', '2025-08-28 12:00:08'),
(57, 15, '2006634469', 'gm', '', 'approved', '2025-08-28 12:00:14'),
(58, 15, '2275998009', 'finance_assistant', '', 'Loan finalized and disbursed. Disbursement Receipt: 2w3erfgb', '2025-08-28 12:00:47');

-- --------------------------------------------------------

--
-- Table structure for table `emp_loan_payments`
--

CREATE TABLE `emp_loan_payments` (
  `id` int(11) NOT NULL,
  `loan_id` int(11) NOT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('payroll','manual') NOT NULL DEFAULT 'payroll',
  `receipt_id` varchar(255) DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `emp_loan_payments`
--

INSERT INTO `emp_loan_payments` (`id`, `loan_id`, `payment_date`, `amount`, `payment_method`, `receipt_id`, `attachment`, `created_at`) VALUES
(28, 15, '2025-08-31', 0.00, 'payroll', NULL, NULL, '2025-08-28 12:24:28'),
(30, 15, '2025-08-31', 650.00, 'manual', '234r5tyui', 'receipt_15_1756618156.pdf', '2025-08-31 05:29:16');

-- --------------------------------------------------------

--
-- Table structure for table `emp_notice`
--

CREATE TABLE `emp_notice` (
  `id` int(11) NOT NULL,
  `emp_id` int(11) NOT NULL,
  `note` varchar(255) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `is_deleted` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `emp_notice`
--

INSERT INTO `emp_notice` (`id`, `emp_id`, `note`, `status`, `is_deleted`, `created_at`, `updated_at`) VALUES
(4, 3727, 'no need to buy', 1, 0, '2025-07-01 06:54:30', '2025-07-01 10:11:10'),
(5, 3727, 'no need to buy 9900', 1, 1, '2025-07-01 08:00:51', '2025-07-01 08:23:27'),
(6, 1872, 'Note', 1, 0, '2025-07-02 08:04:18', '2025-07-02 08:04:18');

-- --------------------------------------------------------

--
-- Table structure for table `emp_salary`
--

CREATE TABLE `emp_salary` (
  `id` int(11) NOT NULL,
  `emp_id` varchar(120) NOT NULL,
  `basic` int(100) NOT NULL,
  `housing` int(100) NOT NULL,
  `transport` int(100) NOT NULL,
  `food` int(11) NOT NULL,
  `misc` int(11) NOT NULL,
  `cashier` int(11) NOT NULL,
  `fuel` int(11) NOT NULL,
  `tel` int(11) NOT NULL,
  `other` int(11) NOT NULL,
  `guard` int(11) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `emp_salary`
--

INSERT INTO `emp_salary` (`id`, `emp_id`, `basic`, `housing`, `transport`, `food`, `misc`, `cashier`, `fuel`, `tel`, `other`, `guard`, `status`, `created_at`) VALUES
(1, '1061', 2450, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(2, '1496', 2450, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(3, '1500', 2500, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(4, '1574', 3200, 0, 0, 0, 300, 0, 0, 75, 0, 0, 1, '2025-05-25 16:27:03'),
(5, '1673', 2400, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(6, '1685', 2825, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(7, '1764', 2625, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(8, '1805', 2890, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(9, '1822', 1800, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(10, '1837', 4750, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(11, '1857', 3000, 0, 0, 300, 0, 0, 450, 50, 0, 0, 1, '2025-05-25 16:27:03'),
(12, '1861', 2200, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(13, '1872', 3950, 0, 370, 0, 0, 200, 300, 100, 0, 0, 1, '2025-05-25 16:27:03'),
(14, '1897', 4100, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(15, '1916', 2225, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(16, '1928', 1550, 0, 0, 300, 0, 0, 0, 50, 0, 200, 1, '2025-05-25 16:27:03'),
(17, '1931', 2050, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(18, '1944', 2800, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(19, '1996', 2500, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(20, '1997', 2250, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(21, '1998', 2250, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(22, '1999', 2050, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(23, '2009', 3300, 1000, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(24, '2048', 2700, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(25, '2057', 2750, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(26, '2079', 2800, 500, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(27, '2181', 4350, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(28, '2190', 7000, 900, 400, 0, 0, 0, 0, 100, 0, 0, 1, '2025-05-25 16:27:03'),
(29, '2212', 5000, 0, 440, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(30, '2226', 2175, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(31, '2238', 3000, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(32, '2285', 2150, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(33, '2287', 1800, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(34, '2288', 2000, 0, 0, 300, 0, 0, 0, 50, 0, 0, 1, '2025-05-25 16:27:03'),
(35, '2290', 2100, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(36, '2316', 1700, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(37, '2337', 3250, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(38, '2352', 1300, 0, 0, 300, 100, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(39, '2358', 3000, 400, 400, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(40, '2361', 1925, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(41, '2363', 1950, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(42, '2364', 1950, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(43, '2367', 1600, 0, 0, 300, 0, 0, 0, 50, 0, 200, 1, '2025-05-25 16:27:03'),
(44, '2368', 2550, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(45, '2372', 2050, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(46, '2376', 1850, 0, 0, 300, 0, 0, 0, 50, 0, 200, 1, '2025-05-25 16:27:03'),
(47, '2394', 5000, 800, 400, 0, 0, 0, 400, 150, 0, 0, 1, '2025-05-25 16:27:03'),
(48, '2423', 3850, 0, 0, 0, 0, 0, 450, 50, 0, 0, 1, '2025-05-25 16:27:03'),
(49, '2519', 1400, 0, 0, 300, 0, 0, 0, 50, 0, 200, 1, '2025-05-25 16:27:03'),
(50, '2522', 2750, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(51, '2530', 1750, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(52, '2539', 2500, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(53, '2541', 1850, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(54, '2550', 1800, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(55, '2553', 6000, 0, 500, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(56, '2554', 1700, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(57, '2565', 1800, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(58, '2572', 2350, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(59, '2637', 2150, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(60, '2642', 1650, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(61, '2645', 2000, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(62, '2648', 2050, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(63, '2654', 2775, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(64, '2688', 6000, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(65, '2690', 4000, 750, 300, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(66, '2709', 2700, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(67, '2745', 2985, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(68, '2843', 2860, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(69, '2854', 2075, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(70, '2869', 2650, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(71, '2876', 2075, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(72, '2924', 4500, 500, 0, 0, 0, 0, 500, 100, 0, 0, 1, '2025-05-25 16:27:03'),
(73, '2953', 2550, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(74, '2961', 3475, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(75, '2975', 9000, 1500, 0, 0, 0, 0, 500, 150, 0, 0, 1, '2025-05-25 16:27:03'),
(76, '3012', 5750, 1313, 0, 0, 0, 0, 400, 100, 0, 0, 1, '2025-05-25 16:27:03'),
(77, '3013', 2750, 0, 0, 0, 0, 0, 0, 50, 0, 0, 1, '2025-05-25 16:27:03'),
(78, '3014', 3000, 0, 0, 0, 0, 0, 0, 50, 0, 0, 1, '2025-05-25 16:27:03'),
(79, '3015', 3000, 0, 0, 0, 500, 0, 0, 50, 0, 0, 1, '2025-05-25 16:27:03'),
(80, '3018', 2100, 0, 0, 0, 0, 0, 0, 0, 0, 200, 1, '2025-05-25 16:27:03'),
(81, '3025', 4000, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(82, '3037', 1400, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(83, '3047', 1600, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(84, '3061', 5500, 0, 300, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(85, '3062', 3000, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(86, '3074', 1450, 0, 0, 300, 0, 0, 0, 50, 0, 200, 1, '2025-05-25 16:27:03'),
(87, '3089', 1950, 0, 0, 0, 0, 0, 0, 0, 0, 200, 1, '2025-05-25 16:27:03'),
(88, '3090', 2000, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(89, '3091', 1600, 0, 0, 0, 0, 0, 0, 50, 0, 200, 1, '2025-05-25 16:27:03'),
(90, '3092', 2600, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(91, '3105', 2550, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(92, '3134', 1450, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(93, '3140', 2500, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(94, '3144', 1800, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(95, '3156', 2300, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(96, '3162', 2150, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(97, '3163', 2150, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(98, '3198', 1300, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(99, '3205', 4000, 600, 0, 0, 0, 0, 500, 100, 0, 0, 1, '2025-05-25 16:27:03'),
(100, '3209', 1900, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(101, '3223', 4025, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(102, '3224', 1600, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(103, '3230', 2350, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(104, '3231', 2650, 0, 0, 300, 200, 0, 300, 50, 0, 0, 1, '2025-05-25 16:27:03'),
(105, '3233', 2400, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(106, '3249', 2250, 0, 0, 0, 200, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(107, '3250', 2000, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(108, '3252', 2200, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(109, '3254', 1900, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(110, '3255', 1700, 0, 0, 0, 0, 0, 0, 50, 0, 200, 1, '2025-05-25 16:27:03'),
(111, '3256', 2550, 0, 0, 0, 0, 0, 0, 50, 0, 0, 1, '2025-05-25 16:27:03'),
(112, '3263', 2000, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(113, '3265', 2300, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(114, '3280', 2250, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(115, '3286', 3100, 0, 0, 0, 0, 0, 200, 75, 0, 0, 1, '2025-05-25 16:27:03'),
(116, '3294', 13300, 1700, 800, 0, 0, 0, 0, 200, 0, 0, 1, '2025-05-25 16:27:03'),
(117, '3321', 1400, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(118, '3325', 1150, 0, 0, 300, 100, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(119, '3329', 1300, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(120, '3330', 1100, 0, 0, 300, 200, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(121, '3332', 11000, 1600, 600, 0, 0, 0, 400, 150, 0, 0, 1, '2025-05-25 16:27:03'),
(122, '3370', 2350, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(123, '3384', 2000, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(124, '3386', 2900, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(125, '3431', 5500, 0, 300, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(126, '3497', 5500, 0, 400, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(127, '3532', 2300, 0, 0, 0, 0, 0, 0, 0, 0, 200, 1, '2025-05-25 16:27:03'),
(128, '3586', 3200, 0, 300, 0, 0, 0, 500, 100, 0, 0, 1, '2025-05-25 16:27:03'),
(129, '3602', 2200, 0, 0, 300, 0, 0, 0, 50, 0, 0, 1, '2025-05-25 16:27:03'),
(130, '3618', 9000, 1700, 800, 0, 0, 0, 300, 200, 0, 0, 1, '2025-05-25 16:27:03'),
(131, '3622', 1800, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(132, '3627', 4500, 0, 0, 0, 0, 0, 500, 100, 0, 0, 1, '2025-05-25 16:27:03'),
(133, '3665', 4400, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(134, '3705', 2750, 0, 0, 0, 0, 0, 0, 50, 0, 0, 1, '2025-05-25 16:27:03'),
(135, '3724', 2850, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(136, '3726', 1850, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(137, '3727', 1400, 0, 0, 0, 0, 0, 0, 0, 200, 0, 1, '2025-05-25 16:27:03'),
(138, '3728', 2050, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(139, '3729', 1850, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(140, '3742', 1750, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(141, '3767', 2200, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(142, '3768', 1900, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(143, '3787', 1850, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(144, '3790', 2150, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(145, '3795', 1700, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(146, '3798', 1800, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(147, '3801', 1850, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(148, '3802', 2200, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(149, '3803', 1970, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(150, '3810', 2100, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(151, '3812', 2050, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(152, '3815', 1750, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(153, '3836', 1750, 0, 0, 0, 0, 0, 0, 50, 0, 0, 1, '2025-05-25 16:27:03'),
(154, '3845', 1800, 0, 0, 0, 0, 0, 0, 50, 0, 0, 1, '2025-05-25 16:27:03'),
(155, '3855', 1650, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(156, '3857', 1600, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(157, '3862', 3700, 400, 400, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(158, '3866', 4200, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(159, '3876', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(160, '3877', 1700, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(161, '3897', 2200, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(162, '3899', 2200, 0, 0, 300, 0, 0, 0, 50, 0, 0, 1, '2025-05-25 16:27:03'),
(163, '3928', 35000, 2500, 1000, 0, 0, 0, 0, 250, 0, 0, 1, '2025-05-25 16:27:03'),
(164, '3929', 2000, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(165, '3935', 4500, 0, 300, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(166, '3952', 3000, 1000, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(167, '3987', 3500, 0, 0, 0, 0, 0, 0, 100, 0, 0, 1, '2025-05-25 16:27:03'),
(168, '4059', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(169, '4119', 11500, 1700, 800, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(170, '4120', 9000, 0, 600, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(171, '4124', 10000, 0, 600, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(172, '4133', 11000, 1400, 600, 0, 0, 0, 0, 150, 0, 0, 1, '2025-05-25 16:27:03'),
(173, '4134', 6500, 0, 0, 0, 500, 0, 500, 100, 0, 0, 1, '2025-05-25 16:27:03'),
(174, '4136', 8000, 1200, 0, 0, 0, 0, 300, 100, 0, 0, 1, '2025-05-25 16:27:03'),
(175, '4149', 4500, 600, 300, 0, 0, 0, 0, 100, 0, 0, 1, '2025-05-25 16:27:03'),
(176, '4150', 7100, 0, 300, 0, 0, 0, 0, 100, 0, 0, 1, '2025-05-25 16:27:03'),
(177, '4157', 1950, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(178, '4172', 1850, 0, 0, 0, 0, 0, 0, 0, 0, 200, 1, '2025-05-25 16:27:03'),
(179, '4195', 1400, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(180, '4215', 3850, 0, 0, 0, 0, 200, 0, 50, 0, 0, 1, '2025-05-25 16:27:03'),
(181, '4220', 2400, 0, 0, 0, 0, 0, 200, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(182, '4273', 5500, 1000, 300, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(183, '4283', 2250, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(184, '4284', 1750, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(185, '4288', 12000, 1700, 800, 0, 0, 0, 300, 150, 0, 0, 1, '2025-05-25 16:27:03'),
(186, '4315', 5000, 0, 0, 0, 0, 0, 500, 100, 0, 0, 1, '2025-05-25 16:27:03'),
(187, '4328', 3450, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(188, '4329', 5500, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(189, '4338', 1750, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(190, '4340', 2270, 0, 0, 0, 0, 0, 650, 100, 0, 0, 1, '2025-05-25 16:27:03'),
(191, '4365', 2800, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(192, '4366', 2850, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(193, '4385', 2450, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(194, '4403', 1800, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(195, '4438', 3050, 0, 0, 0, 0, 200, 0, 50, 0, 0, 1, '2025-05-25 16:27:03'),
(196, '4439', 1200, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(197, '4465', 1700, 0, 0, 0, 0, 0, 0, 50, 0, 0, 1, '2025-05-25 16:27:03'),
(198, '4473', 2800, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(199, '4474', 2150, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(200, '4493', 2400, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(201, '4526', 2500, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(202, '4528', 3200, 1000, 300, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(203, '4532', 2000, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(204, '4533', 2300, 0, 0, 0, 0, 0, 450, 50, 0, 200, 1, '2025-05-25 16:27:03'),
(205, '4560', 1850, 0, 0, 300, 200, 0, 0, 50, 0, 0, 1, '2025-05-25 16:27:03'),
(206, '4564', 1150, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(207, '4565', 1150, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(208, '4567', 1550, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(209, '4572', 3000, 1000, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(210, '4631', 3200, 800, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(211, '4655', 3000, 1000, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(212, '4689', 4600, 600, 300, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(213, '4709', 2200, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(214, '4717', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(215, '4736', 3100, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(216, '4756', 1850, 0, 0, 0, 0, 0, 0, 50, 0, 0, 1, '2025-05-25 16:27:03'),
(217, '4768', 6200, 1200, 600, 0, 0, 0, 500, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(218, '4780', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(219, '4820', 3400, 600, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(220, '4824', 4900, 800, 300, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(221, '4825', 7000, 0, 0, 0, 0, 0, 300, 200, 0, 0, 1, '2025-05-25 16:27:03'),
(222, '4835', 4500, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(223, '4837', 1900, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(224, '4838', 1800, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(225, '4840', 1950, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(226, '4842', 2400, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(227, '4843', 1700, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(228, '4853', 1800, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(229, '4856', 1600, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(230, '4857', 1700, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(231, '4858', 1700, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(232, '4859', 1700, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(233, '4860', 1700, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(234, '4861', 1700, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(235, '4869', 1850, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(236, '4894', 4700, 600, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(237, '4897', 2200, 0, 0, 300, 0, 0, 0, 50, 0, 0, 1, '2025-05-25 16:27:03'),
(238, '4899', 3000, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(239, '4902', 1300, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(240, '4903', 1650, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(241, '4905', 2300, 0, 0, 300, 0, 0, 0, 50, 0, 0, 1, '2025-05-25 16:27:03'),
(242, '4907', 5400, 1200, 500, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(243, '4914', 2000, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(244, '4917', 1700, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(245, '4918', 1700, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(246, '4919', 1700, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(247, '4920', 1700, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(248, '4921', 1700, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(249, '4927', 1500, 0, 0, 300, 0, 0, 0, 50, 0, 0, 1, '2025-05-25 16:27:03'),
(250, '4929', 1700, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(251, '4930', 1600, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(252, '4932', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(253, '4933', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(254, '4936', 1700, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(255, '4949', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(256, '4956', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(257, '4957', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(258, '4962', 4000, 500, 1100, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(259, '4969', 3200, 800, 400, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(260, '4976', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(261, '4984', 3200, 800, 600, 0, 800, 200, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(262, '4995', 1870, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(263, '4996', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(264, '5004', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(265, '5006', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(266, '5011', 7050, 1400, 0, 0, 0, 0, 500, 200, 0, 0, 1, '2025-05-25 16:27:03'),
(267, '5013', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(268, '5018', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(269, '5021', 4200, 600, 1200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(270, '5024', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(271, '5029', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(272, '5030', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(273, '5033', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(274, '5035', 6600, 900, 500, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(275, '5043', 4000, 1000, 400, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(276, '5045', 4000, 1000, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(277, '5046', 3200, 800, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(278, '5049', 1750, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(279, '5052', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(280, '5055', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(281, '5058', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(282, '5062', 1100, 0, 0, 300, 200, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(283, '5064', 7175, 1300, 500, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(284, '5065', 1100, 0, 0, 300, 200, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(285, '5071', 15000, 2500, 0, 0, 0, 0, 350, 200, 0, 0, 1, '2025-05-25 16:27:03'),
(286, '5072', 1200, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(287, '5073', 1550, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(288, '5079', 2000, 0, 0, 0, 0, 0, 0, 50, 0, 0, 1, '2025-05-25 16:27:03'),
(289, '5080', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(290, '5081', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(291, '5085', 3900, 800, 300, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(292, '5087', 2100, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(293, '5091', 1950, 0, 0, 0, 0, 0, 0, 50, 0, 0, 1, '2025-05-25 16:27:03'),
(294, '5093', 3750, 0, 0, 0, 0, 0, 350, 100, 0, 0, 1, '2025-05-25 16:27:03'),
(295, '5095', 2500, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(296, '5096', 5200, 800, 500, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(297, '5101', 6600, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(298, '5107', 4600, 800, 500, 0, 300, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(299, '5109', 3050, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(300, '5111', 3200, 800, 400, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(301, '5115', 5000, 600, 1500, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(302, '5116', 3400, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(303, '5118', 20000, 0, 800, 0, 0, 0, 0, 200, 0, 0, 1, '2025-05-25 16:27:03'),
(304, '5119', 8000, 0, 300, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(305, '5122', 3700, 900, 400, 0, 500, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(306, '5126', 2250, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(307, '5127', 7000, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(308, '5128', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(309, '5138', 5700, 800, 500, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(310, '5141', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(311, '5142', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(312, '5146', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(313, '5147', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(314, '5148', 1350, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(315, '5149', 1200, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(316, '5150', 1200, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(317, '5151', 2250, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(318, '5152', 1800, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(319, '5157', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(320, '5160', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(321, '5161', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(322, '5163', 1700, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(323, '5165', 4060, 0, 300, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(324, '5167', 1500, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(325, '5168', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(326, '5173', 2200, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(327, '5174', 1500, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(328, '5181', 2775, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(329, '5186', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(330, '5187', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(331, '5204', 4900, 800, 300, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(332, '5251', 1200, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(333, '5252', 1500, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(334, '5253', 4400, 800, 300, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(335, '5254', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(336, '5257', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(337, '5259', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(338, '5261', 10700, 1600, 0, 0, 0, 0, 650, 150, 0, 0, 1, '2025-05-25 16:27:03'),
(339, '5264', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(340, '5266', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(341, '5268', 1700, 0, 0, 0, 0, 0, 0, 50, 0, 0, 1, '2025-05-25 16:27:03'),
(342, '5271', 1500, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(343, '5272', 1700, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(344, '5273', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(345, '5275', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(346, '5276', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(347, '5278', 1850, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(348, '5280', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(349, '5282', 5700, 800, 500, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(350, '5285', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(351, '5286', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(352, '5287', 1200, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(353, '5288', 1200, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(354, '5289', 1800, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(355, '5290', 1200, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(356, '5291', 1200, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(357, '5292', 1700, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(358, '5293', 11000, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(359, '5294', 5000, 0, 300, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(360, '5295', 4700, 1000, 300, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(361, '5296', 10000, 1700, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(362, '5297', 1875, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(363, '5298', 1875, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(364, '5299', 1875, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(365, '5300', 1875, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(366, '5301', 1875, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(367, '5302', 1900, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(368, '5303', 1875, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(369, '5304', 1875, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(370, '5305', 1875, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(371, '5306', 1875, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(372, '5307', 1875, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(373, '5308', 1875, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(374, '5309', 1900, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(375, '5312', 1800, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(376, '5313', 1800, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(377, '5318', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(378, '5319', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(379, '5321', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(380, '5325', 2100, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(381, '5326', 4800, 800, 400, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(382, '5327', 1875, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(383, '5328', 1875, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(384, '5329', 1875, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(385, '5331', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(386, '5335', 6700, 900, 400, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(387, '5337', 6400, 1200, 400, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(388, '5339', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(389, '5343', 4500, 800, 300, 0, 0, 0, 300, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(390, '5345', 5700, 800, 500, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(391, '5346', 1300, 0, 0, 300, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(392, '5351', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(393, '5352', 4400, 600, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(394, '5357', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(395, '5366', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(396, '5370', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(397, '5371', 3300, 800, 400, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(398, '5376', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(399, '5378', 5700, 800, 500, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(400, '5380', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(401, '5381', 6600, 900, 500, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(402, '5383', 4400, 800, 300, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(403, '5384', 6600, 900, 500, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(404, '5388', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(405, '5390', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(406, '5391', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(407, '5392', 4700, 1000, 300, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(408, '5396', 2500, 500, 0, 0, 0, 0, 500, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(409, '5397', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(410, '5399', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(411, '5401', 2200, 300, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(412, '5402', 1200, 300, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(413, '5403', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(414, '5406', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(415, '5407', 5700, 800, 500, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(417, '5409', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(418, '5410', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(419, '5413', 3703, 926, 371, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(420, '5414', 15000, 2000, 0, 0, 0, 0, 650, 200, 0, 0, 1, '2025-05-25 16:27:03'),
(421, '5415', 3200, 800, 400, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(422, '5416', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(423, '5418', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(424, '5420', 4400, 800, 300, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(425, '5422', 4400, 600, 1500, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(426, '5423', 4400, 600, 500, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(427, '5424', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(428, '5425', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(429, '5426', 4400, 800, 300, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(430, '5428', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(431, '5431', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-05-25 16:27:03'),
(434, '5430', 6900, 1500, 600, 0, 0, 0, 0, 0, 0, 0, 0, '2025-09-01 11:41:23'),
(438, '5408', 4500, 2000, 1500, 0, 0, 0, 0, 150, 0, 0, 0, '2025-07-03 13:04:44'),
(442, '5215', 3200, 800, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-07-03 11:08:30'),
(443, '5207', 3200, 800, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-07-03 11:08:30'),
(444, '5226', 3200, 800, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-07-03 11:08:30'),
(445, '5227', 3200, 800, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-07-03 11:08:30'),
(446, '5228', 3200, 800, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-07-03 11:08:30'),
(447, '5229', 3200, 800, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-07-03 11:08:30'),
(448, '5230', 3200, 800, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-07-03 11:08:30'),
(449, '5231', 3200, 800, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-07-03 11:08:30'),
(450, '5232', 3200, 800, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-07-03 11:08:30'),
(451, '5233', 3200, 800, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-07-03 11:08:30'),
(452, '5234', 3200, 800, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-07-03 11:08:30'),
(453, '5236', 3200, 800, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-07-03 11:08:30'),
(454, '5237', 3200, 800, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-07-03 11:08:30'),
(455, '5239', 3200, 800, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-07-03 11:08:30'),
(456, '5205', 3200, 800, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-07-03 11:08:30'),
(457, '5209', 3200, 800, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-07-03 11:08:30'),
(458, '5211', 3200, 800, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-07-03 11:08:30'),
(459, '5214', 3200, 800, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-07-03 11:08:30'),
(460, '5219', 3200, 800, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-07-03 11:08:30'),
(461, '5222', 3200, 800, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-07-03 11:08:30'),
(462, '5224', 3200, 800, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-07-03 11:08:30'),
(463, '5246', 3200, 800, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-07-03 11:08:30'),
(464, '5245', 3200, 800, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-07-03 11:08:30'),
(465, '5213', 3200, 800, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-07-03 11:08:30'),
(466, '5216', 3200, 800, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-07-03 11:08:30'),
(467, '5218', 3200, 800, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-07-03 11:08:30'),
(468, '5221', 3200, 800, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-07-03 11:08:30'),
(469, '5225', 3200, 800, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-07-03 11:08:30'),
(470, '5235', 3200, 800, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-07-03 11:08:30'),
(471, '5241', 3200, 800, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-07-03 11:08:30'),
(472, '5242', 3200, 800, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-07-03 11:08:30'),
(473, '5243', 3200, 800, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-07-03 11:08:30'),
(474, '5244', 3200, 800, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-07-03 11:08:30'),
(475, '5217', 3200, 800, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-07-03 11:08:30'),
(476, '5238', 3200, 800, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-07-03 11:08:30'),
(477, '5433', 4400, 800, 300, 0, 0, 0, 0, 0, 0, 0, 1, '2025-07-03 11:08:30'),
(478, '5434', 4000, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2025-07-03 11:08:30'),
(479, '5435', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-07-03 11:08:30'),
(480, '5436', 3200, 800, 200, 0, 0, 0, 0, 0, 0, 0, 1, '2025-07-03 11:08:30'),
(481, '5439', 4500, 600, 300, 0, 0, 0, 0, 100, 0, 0, 1, '2025-07-03 11:46:46'),
(482, '5408', 4500, 2000, 1500, 0, 0, 0, 0, 100, 50, 0, 0, '2025-07-03 13:04:54'),
(483, '5408', 4500, 2000, 1500, 0, 0, 0, 0, 150, 0, 0, 1, '2025-07-03 13:04:54'),
(494, '5430', 6900, 1500, 600, 0, 0, 0, 0, 0, 0, 0, 1, '2025-09-01 11:58:03'),
(493, '5430', 6900, 1500, 600, 0, 0, 0, 0, 0, 0, 0, 0, '2025-09-01 11:58:03'),
(492, '5430', 6900, 1500, 600, 0, 0, 0, 0, 0, 0, 0, 0, '2025-09-01 11:55:43'),
(491, '5430', 6900, 1500, 600, 0, 0, 0, 0, 0, 0, 0, 0, '2025-09-01 11:44:07');

-- --------------------------------------------------------

--
-- Table structure for table `emp_vacation`
--

CREATE TABLE `emp_vacation` (
  `id` int(11) NOT NULL,
  `emp_id` varchar(255) NOT NULL,
  `start_date` varchar(255) NOT NULL,
  `user_update` varchar(255) NOT NULL,
  `return_date` varchar(50) NOT NULL,
  `vacdays` int(50) NOT NULL,
  `vac_type` varchar(50) NOT NULL,
  `fly_type` enum('annual','emergency') DEFAULT NULL,
  `arrived_date` varchar(100) NOT NULL,
  `permit_no` varchar(100) NOT NULL,
  `remarks` varchar(255) NOT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `is_deductible` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = Deductible from annual balance, 0 = Not deductible',
  `review` varchar(50) DEFAULT NULL,
  `note` varchar(255) NOT NULL,
  `replacement_person` varchar(100) DEFAULT NULL,
  `last_vac_date` date DEFAULT NULL,
  `next_vac_date` date DEFAULT NULL,
  `ticket_pay` decimal(10,2) DEFAULT NULL,
  `permit_fee` decimal(10,2) DEFAULT NULL,
  `approval_status` enum('apply','dept_manager_pending','pending','hr_assistant_approved','hr_manager_approved','gm_approved','rejected') DEFAULT 'apply',
  `dep_manager_approval` timestamp NULL DEFAULT current_timestamp(),
  `hr_assistant_approval` timestamp NULL DEFAULT NULL,
  `hr_manager_approval` timestamp NULL DEFAULT NULL,
  `gm_approval` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `emp_vacation`
--

INSERT INTO `emp_vacation` (`id`, `emp_id`, `start_date`, `user_update`, `return_date`, `vacdays`, `vac_type`, `fly_type`, `arrived_date`, `permit_no`, `remarks`, `attachment_path`, `is_deductible`, `review`, `note`, `replacement_person`, `last_vac_date`, `next_vac_date`, `ticket_pay`, `permit_fee`, `approval_status`, `dep_manager_approval`, `hr_assistant_approval`, `hr_manager_approval`, `gm_approval`, `created_at`) VALUES
(42, '5127', '2025-08-27', '', '2025', 3, 'Sick Leave', NULL, '', '', 'Reason / Notes', NULL, 0, 'C', '', NULL, NULL, NULL, 0.00, 0.00, 'gm_approved', '2025-08-27 06:57:40', '2025-08-27 06:57:57', '2025-08-27 06:58:09', '2025-08-27 06:58:15', '2025-08-27 06:56:32'),
(41, '5127', '2025-08-27', 'System', '2025-08-31', 5, 'Fly', 'annual', '', '', '', NULL, 0, 'C', '', '5035', NULL, '2026-08-31', 2300.00, 200.00, 'gm_approved', '2025-08-27 05:36:01', '2025-08-27 05:40:11', '2025-08-27 05:40:22', '2025-08-27 05:40:31', '2025-08-27 05:35:32');

-- --------------------------------------------------------

--
-- Table structure for table `emp_vacation_balance`
--

CREATE TABLE `emp_vacation_balance` (
  `id` int(11) NOT NULL,
  `emp_id` varchar(20) NOT NULL,
  `vac_id` int(11) NOT NULL,
  `contract_id` int(11) NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `total_days` decimal(5,2) NOT NULL,
  `used_days` decimal(5,2) NOT NULL,
  `remaining_balance` decimal(5,2) NOT NULL,
  `available_balance` decimal(5,2) NOT NULL,
  `carryover_days` decimal(5,2) NOT NULL DEFAULT 0.00,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `emp_vacation_balance`
--

INSERT INTO `emp_vacation_balance` (`id`, `emp_id`, `vac_id`, `contract_id`, `period_start`, `period_end`, `total_days`, `used_days`, `remaining_balance`, `available_balance`, `carryover_days`, `last_updated`) VALUES
(20, '5127', 41, 4, '2024-10-02', '2025-10-02', 21.00, 5.00, 16.00, 13.00, 0.00, '2025-08-27 06:55:57');

-- --------------------------------------------------------

--
-- Table structure for table `eos_calc_`
--

CREATE TABLE `eos_calc_` (
  `id` int(11) NOT NULL,
  `prid` int(11) NOT NULL,
  `cid` int(11) NOT NULL,
  `details` varchar(255) NOT NULL,
  `details_ar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `eos_calc_`
--

INSERT INTO `eos_calc_` (`id`, `prid`, `cid`, `details`, `details_ar`, `status`) VALUES
(1, 0, 3, 'Direct termination of the contract during the probationary period according to Article 53 of  the Labor Law', 'فسخ العقد خلال فترة التجربة وفقاً للمادة 53 من نظام العمل', 1),
(2, 0, 4, 'By mutual agreement according to Article 74 of the Labor Law', 'بإتفاق الطرفين وفقاً للمادة 74 من نظام العمل', 1),
(3, 0, 5, 'Direct termination of the employment contract by the employer according to Article 80 of the Labor Law', 'فسخ عقد العمل من قبل صاحب العمل وفقاً للمادة 80 من نظام العمل', 1),
(4, 0, 6, 'Termination of the contract upon its expiration', 'إنتهاء العقد بإنتهاء مدته', 1),
(5, 0, 7, 'The worker reached retirement age according to Article 74 of the Labor Law', 'بلوغ العامل سن التقاعد وفقاً للمادة 74 من نظام العمل', 1),
(6, 0, 9, 'Permanent closure of the establishment according to Article 74 of the Labor Law', 'إغلاق المنشأة نهائياً وفقاً للمادة 74 من نظام العمل', 1),
(7, 0, 10, 'Termination of the activity in which the worker is employed according to Article 74 of the Labor Law', 'إنهاء النشاط الذي يعمل فيه العامل وفقاً للمادة 74 من نظام العمل', 1),
(8, 0, 12, 'Worker\'s inability to work according to Article 79 of the Labor Law', 'عجز العامل عن العمل وفقاً للمادة 79 من نظام العمل', 1),
(9, 0, 13, 'Termination of the contract without a legitimate reason according to Article 77 of the Labor Law', 'إنهاء العقد دون سبب مشروع وفقاً للمادة 77 من نظام العمل', 1),
(10, 0, 14, 'Death of the worker according to Article 79 of the Labor Law', 'وفاة العامل وفقاً للمادة 79 من نظام العمل', 1),
(11, 0, 15, 'Lack of desire to continue the contractual relationship based on mutual agreement', 'عدم الرغبة في استمرار العلاقة التعاقدية بناء على إرادة أحد الطرفين المنفردة في العقود الغير محددة المدة وفقاً للمادة 75 من نظام العمل', 1),
(12, 0, 16, 'Worker\'s inability to work (resulting from a work injury) according to Article 137 of the Labor Law', 'عجز العامل عن العمل (الناتج عن إصابة العمل) وفقاً للمادة 137 من نظام العمل', 1),
(13, 0, 17, 'Transfer of ownership of the individual establishment to a new owner and the worker refuses to continue according to Article 18 of the Labor Law', 'إنتقال ملكية المنشأة (الفردية) إلى مالك جديد وفقاً للمادة 18 من نظام العمل', 1),
(14, 0, 19, 'Termination of the contract by a female worker due to childbirth according to Article 87 of the Labor Law', 'إنهاء المرأة العاملة العقد بسبب الوضع وفقاً للمادة 87 من نظام العمل', 1),
(15, 0, 20, 'Termination of the contract by a female worker due to marriage within six months of marriage date according to Article 87 of the Labor Law', 'إنهاء المرأة العاملة العقد بسبب الزواج وفقاً للمادة 87 من نظام العمل', 1),
(16, 0, 21, 'Death of the employer according to Article 79 if the employer is an individual and the worker refuses to continue with the employer\'s heirs', 'وفاة صاحب العمل وفقاً للمادة 79 من نظام العمل إذا  كانت شخصيته قد روعيت في عقد العمل', 1),
(17, 0, 22, 'Direct termination of the contract by the worker according to Article 81 of the Labor Law', 'فسخ العقد من قبل العامل وفقاً للمادة 81 من نظام العمل', 1),
(18, 0, 3, 'Direct termination of the contract during the probationary period according to Article 53 of  the Labor Law', 'فسخ العقد خلال فترة التجربة وفقاً للمادة 53 من نظام العمل', 1),
(19, 0, 4, 'By mutual agreement according to Article 74 of the Labor Law', 'بإتفاق الطرفين وفقاً للمادة 74 من نظام العمل', 1),
(20, 0, 5, 'Direct termination of the employment contract by the employer according to Article 80 of the Labor Law', 'فسخ عقد العمل من قبل صاحب العمل وفقاً للمادة 80 من نظام العمل', 1),
(21, 0, 7, 'The worker reached retirement age according to Article 74 of the Labor Law', 'بلوغ العامل سن التقاعد وفقاً للمادة 74 من نظام العمل', 1),
(22, 0, 9, 'Permanent closure of the establishment according to Article 74 of the Labor Law', 'إغلاق المنشأة نهائياً وفقاً للمادة 74 من نظام العمل', 1),
(23, 0, 10, 'Termination of the activity in which the worker is employed according to Article 74 of the Labor Law', 'إنهاء النشاط الذي يعمل فيه العامل وفقاً للمادة 74 من نظام العمل', 1),
(24, 0, 12, 'Worker\'s inability to work according to Article 79 of the Labor Law', 'عجز العامل عن العمل وفقاً للمادة 79 من نظام العمل', 1),
(25, 0, 13, 'Termination of the contract without a legitimate reason according to Article 77 of the Labor Law', 'إنهاء العقد دون سبب مشروع وفقاً للمادة 77 من نظام العمل', 1),
(26, 0, 14, 'Death of the worker according to Article 79 of the Labor Law', 'وفاة العامل وفقاً للمادة 79 من نظام العمل', 1),
(27, 0, 16, 'Worker\'s inability to work (resulting from a work injury) according to Article 137 of the Labor Law', 'عجز العامل عن العمل (الناتج عن إصابة العمل) وفقاً للمادة 137 من نظام العمل', 1),
(28, 0, 17, 'Transfer of ownership of the individual establishment to a new owner and the worker refuses to continue according to Article 18 of the Labor Law', 'إنتقال ملكية المنشأة (الفردية) إلى مالك جديد وفقاً للمادة 18 من نظام العمل', 1),
(29, 0, 19, 'Termination of the contract by a female worker due to childbirth according to Article 87 of the Labor Law', 'إنهاء المرأة العاملة العقد بسبب الوضع وفقاً للمادة 87 من نظام العمل', 1),
(30, 0, 20, 'Termination of the contract by a female worker due to marriage within six months of marriage date according to Article 87 of the Labor Law', 'إنهاء المرأة العاملة العقد بسبب الزواج وفقاً للمادة 87 من نظام العمل', 1),
(31, 0, 21, 'Death of the employer according to Article 79 if the employer is an individual and the worker refuses to continue with the employer\'s heirs', 'وفاة صاحب العمل وفقاً للمادة 79 من نظام العمل إذا  كانت شخصيته قد روعيت في عقد العمل', 1),
(32, 0, 22, 'Direct termination of the contract by the worker according to Article 81 of the Labor Law', 'فسخ العقد من قبل العامل وفقاً للمادة 81 من نظام العمل', 1),
(33, 0, 1, 'Employee resignation', 'استقالة الموظف', 1),
(34, 0, 3, 'Direct termination of the contract during the probationary period according to Article 53 of  the Labor Law', 'فسخ العقد خلال فترة التجربة وفقاً للمادة 53 من نظام العمل', 1),
(35, 0, 4, 'By mutual agreement according to Article 74 of the Labor Law', 'بإتفاق الطرفين وفقاً للمادة 74 من نظام العمل', 1),
(36, 0, 5, 'Direct termination of the employment contract by the employer according to Article 80 of the Labor Law', 'فسخ عقد العمل من قبل صاحب العمل وفقاً للمادة 80 من نظام العمل', 1),
(37, 0, 6, 'Termination of the contract upon its expiration', 'إنتهاء العقد بإنتهاء مدته', 1),
(38, 0, 7, 'The worker reached retirement age according to Article 74 of the Labor Law', 'بلوغ العامل سن التقاعد وفقاً للمادة 74 من نظام العمل', 1),
(39, 0, 9, 'Permanent closure of the establishment according to Article 74 of the Labor Law', 'إغلاق المنشأة نهائياً وفقاً للمادة 74 من نظام العمل', 1),
(40, 0, 10, 'Termination of the activity in which the worker is employed according to Article 74 of the Labor Law', 'إنهاء النشاط الذي يعمل فيه العامل وفقاً للمادة 74 من نظام العمل', 1),
(41, 0, 12, 'Worker\'s inability to work according to Article 79 of the Labor Law', 'عجز العامل عن العمل وفقاً للمادة 79 من نظام العمل', 1),
(42, 0, 13, 'Termination of the contract without a legitimate reason according to Article 77 of the Labor Law', 'إنهاء العقد دون سبب مشروع وفقاً للمادة 77 من نظام العمل', 1),
(43, 0, 14, 'Death of the worker according to Article 79 of the Labor Law', 'وفاة العامل وفقاً للمادة 79 من نظام العمل', 1),
(44, 0, 15, 'Lack of desire to continue the contractual relationship based on mutual agreement', 'عدم الرغبة في استمرار العلاقة التعاقدية بناء على إرادة أحد الطرفين المنفردة في العقود الغير محددة المدة وفقاً للمادة 75 من نظام العمل', 1),
(45, 0, 16, 'Worker\'s inability to work (resulting from a work injury) according to Article 137 of the Labor Law', 'عجز العامل عن العمل (الناتج عن إصابة العمل) وفقاً للمادة 137 من نظام العمل', 1),
(46, 0, 17, 'Transfer of ownership of the individual establishment to a new owner and the worker refuses to continue according to Article 18 of the Labor Law', 'إنتقال ملكية المنشأة (الفردية) إلى مالك جديد وفقاً للمادة 18 من نظام العمل', 1),
(47, 0, 19, 'Termination of the contract by a female worker due to childbirth according to Article 87 of the Labor Law', 'إنهاء المرأة العاملة العقد بسبب الوضع وفقاً للمادة 87 من نظام العمل', 1),
(48, 0, 20, 'Termination of the contract by a female worker due to marriage within six months of marriage date according to Article 87 of the Labor Law', 'إنهاء المرأة العاملة العقد بسبب الزواج وفقاً للمادة 87 من نظام العمل', 1),
(49, 0, 21, 'Death of the employer according to Article 79 if the employer is an individual and the worker refuses to continue with the employer\'s heirs', 'وفاة صاحب العمل وفقاً للمادة 79 من نظام العمل إذا  كانت شخصيته قد روعيت في عقد العمل', 1),
(50, 0, 22, 'Direct termination of the contract by the worker according to Article 81 of the Labor Law', 'فسخ العقد من قبل العامل وفقاً للمادة 81 من نظام العمل', 1),
(51, 0, 3, 'Direct termination of the contract during the probationary period according to Article 53 of  the Labor Law', 'فسخ العقد خلال فترة التجربة وفقاً للمادة 53 من نظام العمل', 1),
(52, 0, 4, 'By mutual agreement according to Article 74 of the Labor Law', 'بإتفاق الطرفين وفقاً للمادة 74 من نظام العمل', 1),
(53, 0, 5, 'Direct termination of the employment contract by the employer according to Article 80 of the Labor Law', 'فسخ عقد العمل من قبل صاحب العمل وفقاً للمادة 80 من نظام العمل', 1),
(54, 0, 7, 'The worker reached retirement age according to Article 74 of the Labor Law', 'بلوغ العامل سن التقاعد وفقاً للمادة 74 من نظام العمل', 1),
(55, 0, 9, 'Permanent closure of the establishment according to Article 74 of the Labor Law', 'إغلاق المنشأة نهائياً وفقاً للمادة 74 من نظام العمل', 1),
(56, 0, 10, 'Termination of the activity in which the worker is employed according to Article 74 of the Labor Law', 'إنهاء النشاط الذي يعمل فيه العامل وفقاً للمادة 74 من نظام العمل', 1),
(57, 0, 12, 'Worker\'s inability to work according to Article 79 of the Labor Law', 'عجز العامل عن العمل وفقاً للمادة 79 من نظام العمل', 1),
(58, 0, 13, 'Termination of the contract without a legitimate reason according to Article 77 of the Labor Law', 'إنهاء العقد دون سبب مشروع وفقاً للمادة 77 من نظام العمل', 1),
(59, 0, 14, 'Death of the worker according to Article 79 of the Labor Law', 'وفاة العامل وفقاً للمادة 79 من نظام العمل', 1),
(60, 0, 16, 'Worker\'s inability to work (resulting from a work injury) according to Article 137 of the Labor Law', 'عجز العامل عن العمل (الناتج عن إصابة العمل) وفقاً للمادة 137 من نظام العمل', 1),
(61, 0, 17, 'Transfer of ownership of the individual establishment to a new owner and the worker refuses to continue according to Article 18 of the Labor Law', 'إنتقال ملكية المنشأة (الفردية) إلى مالك جديد وفقاً للمادة 18 من نظام العمل', 1),
(62, 0, 19, 'Termination of the contract by a female worker due to childbirth according to Article 87 of the Labor Law', 'إنهاء المرأة العاملة العقد بسبب الوضع وفقاً للمادة 87 من نظام العمل', 1),
(63, 0, 20, 'Termination of the contract by a female worker due to marriage within six months of marriage date according to Article 87 of the Labor Law', 'إنهاء المرأة العاملة العقد بسبب الزواج وفقاً للمادة 87 من نظام العمل', 1),
(64, 0, 21, 'Death of the employer according to Article 79 if the employer is an individual and the worker refuses to continue with the employer\'s heirs', 'وفاة صاحب العمل وفقاً للمادة 79 من نظام العمل إذا  كانت شخصيته قد روعيت في عقد العمل', 1),
(65, 0, 22, 'Direct termination of the contract by the worker according to Article 81 of the Labor Law', 'فسخ العقد من قبل العامل وفقاً للمادة 81 من نظام العمل', 1),
(66, 0, 1, 'Employee resignation', 'استقالة الموظف', 1),
(67, 0, 3, 'Direct termination of the contract during the probationary period according to Article 53 of  the Labor Law', 'فسخ العقد خلال فترة التجربة وفقاً للمادة 53 من نظام العمل', 1),
(68, 0, 4, 'By mutual agreement according to Article 74 of the Labor Law', 'بإتفاق الطرفين وفقاً للمادة 74 من نظام العمل', 1),
(69, 0, 5, 'Direct termination of the employment contract by the employer according to Article 80 of the Labor Law', 'فسخ عقد العمل من قبل صاحب العمل وفقاً للمادة 80 من نظام العمل', 1),
(70, 0, 6, 'Termination of the contract upon its expiration', 'إنتهاء العقد بإنتهاء مدته', 1),
(71, 0, 7, 'The worker reached retirement age according to Article 74 of the Labor Law', 'بلوغ العامل سن التقاعد وفقاً للمادة 74 من نظام العمل', 1),
(72, 0, 9, 'Permanent closure of the establishment according to Article 74 of the Labor Law', 'إغلاق المنشأة نهائياً وفقاً للمادة 74 من نظام العمل', 1),
(73, 0, 10, 'Termination of the activity in which the worker is employed according to Article 74 of the Labor Law', 'إنهاء النشاط الذي يعمل فيه العامل وفقاً للمادة 74 من نظام العمل', 1),
(74, 0, 12, 'Worker\'s inability to work according to Article 79 of the Labor Law', 'عجز العامل عن العمل وفقاً للمادة 79 من نظام العمل', 1),
(75, 0, 13, 'Termination of the contract without a legitimate reason according to Article 77 of the Labor Law', 'إنهاء العقد دون سبب مشروع وفقاً للمادة 77 من نظام العمل', 1),
(76, 0, 14, 'Death of the worker according to Article 79 of the Labor Law', 'وفاة العامل وفقاً للمادة 79 من نظام العمل', 1),
(77, 0, 15, 'Lack of desire to continue the contractual relationship based on mutual agreement', 'عدم الرغبة في استمرار العلاقة التعاقدية بناء على إرادة أحد الطرفين المنفردة في العقود الغير محددة المدة وفقاً للمادة 75 من نظام العمل', 1),
(78, 0, 16, 'Worker\'s inability to work (resulting from a work injury) according to Article 137 of the Labor Law', 'عجز العامل عن العمل (الناتج عن إصابة العمل) وفقاً للمادة 137 من نظام العمل', 1),
(79, 0, 17, 'Transfer of ownership of the individual establishment to a new owner and the worker refuses to continue according to Article 18 of the Labor Law', 'إنتقال ملكية المنشأة (الفردية) إلى مالك جديد وفقاً للمادة 18 من نظام العمل', 1),
(80, 0, 19, 'Termination of the contract by a female worker due to childbirth according to Article 87 of the Labor Law', 'إنهاء المرأة العاملة العقد بسبب الوضع وفقاً للمادة 87 من نظام العمل', 1),
(81, 0, 20, 'Termination of the contract by a female worker due to marriage within six months of marriage date according to Article 87 of the Labor Law', 'إنهاء المرأة العاملة العقد بسبب الزواج وفقاً للمادة 87 من نظام العمل', 1),
(82, 0, 21, 'Death of the employer according to Article 79 if the employer is an individual and the worker refuses to continue with the employer\'s heirs', 'وفاة صاحب العمل وفقاً للمادة 79 من نظام العمل إذا  كانت شخصيته قد روعيت في عقد العمل', 1),
(83, 0, 22, 'Direct termination of the contract by the worker according to Article 81 of the Labor Law', 'فسخ العقد من قبل العامل وفقاً للمادة 81 من نظام العمل', 1),
(84, 0, 3, 'Direct termination of the contract during the probationary period according to Article 53 of  the Labor Law', 'فسخ العقد خلال فترة التجربة وفقاً للمادة 53 من نظام العمل', 1),
(85, 0, 4, 'By mutual agreement according to Article 74 of the Labor Law', 'بإتفاق الطرفين وفقاً للمادة 74 من نظام العمل', 1),
(86, 0, 5, 'Direct termination of the employment contract by the employer according to Article 80 of the Labor Law', 'فسخ عقد العمل من قبل صاحب العمل وفقاً للمادة 80 من نظام العمل', 1),
(87, 0, 7, 'The worker reached retirement age according to Article 74 of the Labor Law', 'بلوغ العامل سن التقاعد وفقاً للمادة 74 من نظام العمل', 1),
(88, 0, 9, 'Permanent closure of the establishment according to Article 74 of the Labor Law', 'إغلاق المنشأة نهائياً وفقاً للمادة 74 من نظام العمل', 1),
(89, 0, 10, 'Termination of the activity in which the worker is employed according to Article 74 of the Labor Law', 'إنهاء النشاط الذي يعمل فيه العامل وفقاً للمادة 74 من نظام العمل', 1),
(90, 0, 12, 'Worker\'s inability to work according to Article 79 of the Labor Law', 'عجز العامل عن العمل وفقاً للمادة 79 من نظام العمل', 1),
(91, 0, 13, 'Termination of the contract without a legitimate reason according to Article 77 of the Labor Law', 'إنهاء العقد دون سبب مشروع وفقاً للمادة 77 من نظام العمل', 1),
(92, 0, 14, 'Death of the worker according to Article 79 of the Labor Law', 'وفاة العامل وفقاً للمادة 79 من نظام العمل', 1),
(93, 0, 16, 'Worker\'s inability to work (resulting from a work injury) according to Article 137 of the Labor Law', 'عجز العامل عن العمل (الناتج عن إصابة العمل) وفقاً للمادة 137 من نظام العمل', 1),
(94, 0, 17, 'Transfer of ownership of the individual establishment to a new owner and the worker refuses to continue according to Article 18 of the Labor Law', 'إنتقال ملكية المنشأة (الفردية) إلى مالك جديد وفقاً للمادة 18 من نظام العمل', 1),
(95, 0, 19, 'Termination of the contract by a female worker due to childbirth according to Article 87 of the Labor Law', 'إنهاء المرأة العاملة العقد بسبب الوضع وفقاً للمادة 87 من نظام العمل', 1),
(96, 0, 20, 'Termination of the contract by a female worker due to marriage within six months of marriage date according to Article 87 of the Labor Law', 'إنهاء المرأة العاملة العقد بسبب الزواج وفقاً للمادة 87 من نظام العمل', 1),
(97, 0, 21, 'Death of the employer according to Article 79 if the employer is an individual and the worker refuses to continue with the employer\'s heirs', 'وفاة صاحب العمل وفقاً للمادة 79 من نظام العمل إذا  كانت شخصيته قد روعيت في عقد العمل', 1),
(98, 0, 22, 'Direct termination of the contract by the worker according to Article 81 of the Labor Law', 'فسخ العقد من قبل العامل وفقاً للمادة 81 من نظام العمل', 1),
(99, 0, 1, 'Employee resignation', 'استقالة الموظف', 1),
(100, 0, 3, 'Direct termination of the contract during the probationary period according to Article 53 of  the Labor Law', 'فسخ العقد خلال فترة التجربة وفقاً للمادة 53 من نظام العمل', 1),
(101, 0, 4, 'By mutual agreement according to Article 74 of the Labor Law', 'بإتفاق الطرفين وفقاً للمادة 74 من نظام العمل', 1),
(102, 0, 5, 'Direct termination of the employment contract by the employer according to Article 80 of the Labor Law', 'فسخ عقد العمل من قبل صاحب العمل وفقاً للمادة 80 من نظام العمل', 1),
(103, 0, 6, 'Termination of the contract upon its expiration', 'إنتهاء العقد بإنتهاء مدته', 1),
(104, 0, 7, 'The worker reached retirement age according to Article 74 of the Labor Law', 'بلوغ العامل سن التقاعد وفقاً للمادة 74 من نظام العمل', 1),
(105, 0, 9, 'Permanent closure of the establishment according to Article 74 of the Labor Law', 'إغلاق المنشأة نهائياً وفقاً للمادة 74 من نظام العمل', 1),
(106, 0, 10, 'Termination of the activity in which the worker is employed according to Article 74 of the Labor Law', 'إنهاء النشاط الذي يعمل فيه العامل وفقاً للمادة 74 من نظام العمل', 1),
(107, 0, 12, 'Worker\'s inability to work according to Article 79 of the Labor Law', 'عجز العامل عن العمل وفقاً للمادة 79 من نظام العمل', 1),
(108, 0, 13, 'Termination of the contract without a legitimate reason according to Article 77 of the Labor Law', 'إنهاء العقد دون سبب مشروع وفقاً للمادة 77 من نظام العمل', 1),
(109, 0, 14, 'Death of the worker according to Article 79 of the Labor Law', 'وفاة العامل وفقاً للمادة 79 من نظام العمل', 1),
(110, 0, 15, 'Lack of desire to continue the contractual relationship based on mutual agreement', 'عدم الرغبة في استمرار العلاقة التعاقدية بناء على إرادة أحد الطرفين المنفردة في العقود الغير محددة المدة وفقاً للمادة 75 من نظام العمل', 1),
(111, 0, 16, 'Worker\'s inability to work (resulting from a work injury) according to Article 137 of the Labor Law', 'عجز العامل عن العمل (الناتج عن إصابة العمل) وفقاً للمادة 137 من نظام العمل', 1),
(112, 0, 17, 'Transfer of ownership of the individual establishment to a new owner and the worker refuses to continue according to Article 18 of the Labor Law', 'إنتقال ملكية المنشأة (الفردية) إلى مالك جديد وفقاً للمادة 18 من نظام العمل', 1),
(113, 0, 19, 'Termination of the contract by a female worker due to childbirth according to Article 87 of the Labor Law', 'إنهاء المرأة العاملة العقد بسبب الوضع وفقاً للمادة 87 من نظام العمل', 1),
(114, 0, 20, 'Termination of the contract by a female worker due to marriage within six months of marriage date according to Article 87 of the Labor Law', 'إنهاء المرأة العاملة العقد بسبب الزواج وفقاً للمادة 87 من نظام العمل', 1),
(115, 0, 21, 'Death of the employer according to Article 79 if the employer is an individual and the worker refuses to continue with the employer\'s heirs', 'وفاة صاحب العمل وفقاً للمادة 79 من نظام العمل إذا  كانت شخصيته قد روعيت في عقد العمل', 1),
(116, 0, 22, 'Direct termination of the contract by the worker according to Article 81 of the Labor Law', 'فسخ العقد من قبل العامل وفقاً للمادة 81 من نظام العمل', 1),
(117, 0, 3, 'Direct termination of the contract during the probationary period according to Article 53 of  the Labor Law', 'فسخ العقد خلال فترة التجربة وفقاً للمادة 53 من نظام العمل', 1),
(118, 0, 4, 'By mutual agreement according to Article 74 of the Labor Law', 'بإتفاق الطرفين وفقاً للمادة 74 من نظام العمل', 1),
(119, 0, 5, 'Direct termination of the employment contract by the employer according to Article 80 of the Labor Law', 'فسخ عقد العمل من قبل صاحب العمل وفقاً للمادة 80 من نظام العمل', 1),
(120, 0, 7, 'The worker reached retirement age according to Article 74 of the Labor Law', 'بلوغ العامل سن التقاعد وفقاً للمادة 74 من نظام العمل', 1),
(121, 0, 9, 'Permanent closure of the establishment according to Article 74 of the Labor Law', 'إغلاق المنشأة نهائياً وفقاً للمادة 74 من نظام العمل', 1),
(122, 0, 10, 'Termination of the activity in which the worker is employed according to Article 74 of the Labor Law', 'إنهاء النشاط الذي يعمل فيه العامل وفقاً للمادة 74 من نظام العمل', 1),
(123, 0, 12, 'Worker\'s inability to work according to Article 79 of the Labor Law', 'عجز العامل عن العمل وفقاً للمادة 79 من نظام العمل', 1),
(124, 0, 13, 'Termination of the contract without a legitimate reason according to Article 77 of the Labor Law', 'إنهاء العقد دون سبب مشروع وفقاً للمادة 77 من نظام العمل', 1),
(125, 0, 14, 'Death of the worker according to Article 79 of the Labor Law', 'وفاة العامل وفقاً للمادة 79 من نظام العمل', 1),
(126, 0, 16, 'Worker\'s inability to work (resulting from a work injury) according to Article 137 of the Labor Law', 'عجز العامل عن العمل (الناتج عن إصابة العمل) وفقاً للمادة 137 من نظام العمل', 1),
(127, 0, 17, 'Transfer of ownership of the individual establishment to a new owner and the worker refuses to continue according to Article 18 of the Labor Law', 'إنتقال ملكية المنشأة (الفردية) إلى مالك جديد وفقاً للمادة 18 من نظام العمل', 1),
(128, 0, 19, 'Termination of the contract by a female worker due to childbirth according to Article 87 of the Labor Law', 'إنهاء المرأة العاملة العقد بسبب الوضع وفقاً للمادة 87 من نظام العمل', 1),
(129, 0, 20, 'Termination of the contract by a female worker due to marriage within six months of marriage date according to Article 87 of the Labor Law', 'إنهاء المرأة العاملة العقد بسبب الزواج وفقاً للمادة 87 من نظام العمل', 1),
(130, 0, 21, 'Death of the employer according to Article 79 if the employer is an individual and the worker refuses to continue with the employer\'s heirs', 'وفاة صاحب العمل وفقاً للمادة 79 من نظام العمل إذا  كانت شخصيته قد روعيت في عقد العمل', 1),
(131, 0, 22, 'Direct termination of the contract by the worker according to Article 81 of the Labor Law', 'فسخ العقد من قبل العامل وفقاً للمادة 81 من نظام العمل', 1),
(132, 0, 1, 'Employee resignation', 'استقالة الموظف', 1),
(133, 0, 3, 'Direct termination of the contract during the probationary period according to Article 53 of  the Labor Law', 'فسخ العقد خلال فترة التجربة وفقاً للمادة 53 من نظام العمل', 1),
(134, 0, 4, 'By mutual agreement according to Article 74 of the Labor Law', 'بإتفاق الطرفين وفقاً للمادة 74 من نظام العمل', 1),
(135, 0, 5, 'Direct termination of the employment contract by the employer according to Article 80 of the Labor Law', 'فسخ عقد العمل من قبل صاحب العمل وفقاً للمادة 80 من نظام العمل', 1),
(136, 0, 6, 'Termination of the contract upon its expiration', 'إنتهاء العقد بإنتهاء مدته', 1),
(137, 0, 7, 'The worker reached retirement age according to Article 74 of the Labor Law', 'بلوغ العامل سن التقاعد وفقاً للمادة 74 من نظام العمل', 1),
(138, 0, 9, 'Permanent closure of the establishment according to Article 74 of the Labor Law', 'إغلاق المنشأة نهائياً وفقاً للمادة 74 من نظام العمل', 1),
(139, 0, 10, 'Termination of the activity in which the worker is employed according to Article 74 of the Labor Law', 'إنهاء النشاط الذي يعمل فيه العامل وفقاً للمادة 74 من نظام العمل', 1),
(140, 0, 12, 'Worker\'s inability to work according to Article 79 of the Labor Law', 'عجز العامل عن العمل وفقاً للمادة 79 من نظام العمل', 1),
(141, 0, 13, 'Termination of the contract without a legitimate reason according to Article 77 of the Labor Law', 'إنهاء العقد دون سبب مشروع وفقاً للمادة 77 من نظام العمل', 1),
(142, 0, 14, 'Death of the worker according to Article 79 of the Labor Law', 'وفاة العامل وفقاً للمادة 79 من نظام العمل', 1),
(143, 0, 15, 'Lack of desire to continue the contractual relationship based on mutual agreement', 'عدم الرغبة في استمرار العلاقة التعاقدية بناء على إرادة أحد الطرفين المنفردة في العقود الغير محددة المدة وفقاً للمادة 75 من نظام العمل', 1),
(144, 0, 16, 'Worker\'s inability to work (resulting from a work injury) according to Article 137 of the Labor Law', 'عجز العامل عن العمل (الناتج عن إصابة العمل) وفقاً للمادة 137 من نظام العمل', 1),
(145, 0, 17, 'Transfer of ownership of the individual establishment to a new owner and the worker refuses to continue according to Article 18 of the Labor Law', 'إنتقال ملكية المنشأة (الفردية) إلى مالك جديد وفقاً للمادة 18 من نظام العمل', 1),
(146, 0, 19, 'Termination of the contract by a female worker due to childbirth according to Article 87 of the Labor Law', 'إنهاء المرأة العاملة العقد بسبب الوضع وفقاً للمادة 87 من نظام العمل', 1),
(147, 0, 20, 'Termination of the contract by a female worker due to marriage within six months of marriage date according to Article 87 of the Labor Law', 'إنهاء المرأة العاملة العقد بسبب الزواج وفقاً للمادة 87 من نظام العمل', 1),
(148, 0, 21, 'Death of the employer according to Article 79 if the employer is an individual and the worker refuses to continue with the employer\'s heirs', 'وفاة صاحب العمل وفقاً للمادة 79 من نظام العمل إذا  كانت شخصيته قد روعيت في عقد العمل', 1),
(149, 0, 22, 'Direct termination of the contract by the worker according to Article 81 of the Labor Law', 'فسخ العقد من قبل العامل وفقاً للمادة 81 من نظام العمل', 1),
(150, 0, 3, 'Direct termination of the contract during the probationary period according to Article 53 of  the Labor Law', 'فسخ العقد خلال فترة التجربة وفقاً للمادة 53 من نظام العمل', 1),
(151, 0, 4, 'By mutual agreement according to Article 74 of the Labor Law', 'بإتفاق الطرفين وفقاً للمادة 74 من نظام العمل', 1),
(152, 0, 5, 'Direct termination of the employment contract by the employer according to Article 80 of the Labor Law', 'فسخ عقد العمل من قبل صاحب العمل وفقاً للمادة 80 من نظام العمل', 1),
(153, 0, 7, 'The worker reached retirement age according to Article 74 of the Labor Law', 'بلوغ العامل سن التقاعد وفقاً للمادة 74 من نظام العمل', 1),
(154, 0, 9, 'Permanent closure of the establishment according to Article 74 of the Labor Law', 'إغلاق المنشأة نهائياً وفقاً للمادة 74 من نظام العمل', 1),
(155, 0, 10, 'Termination of the activity in which the worker is employed according to Article 74 of the Labor Law', 'إنهاء النشاط الذي يعمل فيه العامل وفقاً للمادة 74 من نظام العمل', 1),
(156, 0, 12, 'Worker\'s inability to work according to Article 79 of the Labor Law', 'عجز العامل عن العمل وفقاً للمادة 79 من نظام العمل', 1),
(157, 0, 13, 'Termination of the contract without a legitimate reason according to Article 77 of the Labor Law', 'إنهاء العقد دون سبب مشروع وفقاً للمادة 77 من نظام العمل', 1),
(158, 0, 14, 'Death of the worker according to Article 79 of the Labor Law', 'وفاة العامل وفقاً للمادة 79 من نظام العمل', 1),
(159, 0, 16, 'Worker\'s inability to work (resulting from a work injury) according to Article 137 of the Labor Law', 'عجز العامل عن العمل (الناتج عن إصابة العمل) وفقاً للمادة 137 من نظام العمل', 1),
(160, 0, 17, 'Transfer of ownership of the individual establishment to a new owner and the worker refuses to continue according to Article 18 of the Labor Law', 'إنتقال ملكية المنشأة (الفردية) إلى مالك جديد وفقاً للمادة 18 من نظام العمل', 1),
(161, 0, 19, 'Termination of the contract by a female worker due to childbirth according to Article 87 of the Labor Law', 'إنهاء المرأة العاملة العقد بسبب الوضع وفقاً للمادة 87 من نظام العمل', 1),
(162, 0, 20, 'Termination of the contract by a female worker due to marriage within six months of marriage date according to Article 87 of the Labor Law', 'إنهاء المرأة العاملة العقد بسبب الزواج وفقاً للمادة 87 من نظام العمل', 1),
(163, 0, 21, 'Death of the employer according to Article 79 if the employer is an individual and the worker refuses to continue with the employer\'s heirs', 'وفاة صاحب العمل وفقاً للمادة 79 من نظام العمل إذا  كانت شخصيته قد روعيت في عقد العمل', 1),
(164, 0, 22, 'Direct termination of the contract by the worker according to Article 81 of the Labor Law', 'فسخ العقد من قبل العامل وفقاً للمادة 81 من نظام العمل', 1),
(165, 0, 1, 'Employee resignation', 'استقالة الموظف', 1),
(166, 0, 3, 'Direct termination of the contract during the probationary period according to Article 53 of  the Labor Law', 'فسخ العقد خلال فترة التجربة وفقاً للمادة 53 من نظام العمل', 1),
(167, 0, 4, 'By mutual agreement according to Article 74 of the Labor Law', 'بإتفاق الطرفين وفقاً للمادة 74 من نظام العمل', 1),
(168, 0, 5, 'Direct termination of the employment contract by the employer according to Article 80 of the Labor Law', 'فسخ عقد العمل من قبل صاحب العمل وفقاً للمادة 80 من نظام العمل', 1),
(169, 0, 6, 'Termination of the contract upon its expiration', 'إنتهاء العقد بإنتهاء مدته', 1),
(170, 0, 7, 'The worker reached retirement age according to Article 74 of the Labor Law', 'بلوغ العامل سن التقاعد وفقاً للمادة 74 من نظام العمل', 1),
(171, 0, 9, 'Permanent closure of the establishment according to Article 74 of the Labor Law', 'إغلاق المنشأة نهائياً وفقاً للمادة 74 من نظام العمل', 1),
(172, 0, 10, 'Termination of the activity in which the worker is employed according to Article 74 of the Labor Law', 'إنهاء النشاط الذي يعمل فيه العامل وفقاً للمادة 74 من نظام العمل', 1),
(173, 0, 12, 'Worker\'s inability to work according to Article 79 of the Labor Law', 'عجز العامل عن العمل وفقاً للمادة 79 من نظام العمل', 1),
(174, 0, 13, 'Termination of the contract without a legitimate reason according to Article 77 of the Labor Law', 'إنهاء العقد دون سبب مشروع وفقاً للمادة 77 من نظام العمل', 1),
(175, 0, 14, 'Death of the worker according to Article 79 of the Labor Law', 'وفاة العامل وفقاً للمادة 79 من نظام العمل', 1),
(176, 0, 15, 'Lack of desire to continue the contractual relationship based on mutual agreement', 'عدم الرغبة في استمرار العلاقة التعاقدية بناء على إرادة أحد الطرفين المنفردة في العقود الغير محددة المدة وفقاً للمادة 75 من نظام العمل', 1),
(177, 0, 16, 'Worker\'s inability to work (resulting from a work injury) according to Article 137 of the Labor Law', 'عجز العامل عن العمل (الناتج عن إصابة العمل) وفقاً للمادة 137 من نظام العمل', 1),
(178, 0, 17, 'Transfer of ownership of the individual establishment to a new owner and the worker refuses to continue according to Article 18 of the Labor Law', 'إنتقال ملكية المنشأة (الفردية) إلى مالك جديد وفقاً للمادة 18 من نظام العمل', 1),
(179, 0, 19, 'Termination of the contract by a female worker due to childbirth according to Article 87 of the Labor Law', 'إنهاء المرأة العاملة العقد بسبب الوضع وفقاً للمادة 87 من نظام العمل', 1),
(180, 0, 20, 'Termination of the contract by a female worker due to marriage within six months of marriage date according to Article 87 of the Labor Law', 'إنهاء المرأة العاملة العقد بسبب الزواج وفقاً للمادة 87 من نظام العمل', 1),
(181, 0, 21, 'Death of the employer according to Article 79 if the employer is an individual and the worker refuses to continue with the employer\'s heirs', 'وفاة صاحب العمل وفقاً للمادة 79 من نظام العمل إذا  كانت شخصيته قد روعيت في عقد العمل', 1),
(182, 0, 22, 'Direct termination of the contract by the worker according to Article 81 of the Labor Law', 'فسخ العقد من قبل العامل وفقاً للمادة 81 من نظام العمل', 1),
(183, 0, 3, 'Direct termination of the contract during the probationary period according to Article 53 of  the Labor Law', 'فسخ العقد خلال فترة التجربة وفقاً للمادة 53 من نظام العمل', 1),
(184, 0, 4, 'By mutual agreement according to Article 74 of the Labor Law', 'بإتفاق الطرفين وفقاً للمادة 74 من نظام العمل', 1),
(185, 0, 5, 'Direct termination of the employment contract by the employer according to Article 80 of the Labor Law', 'فسخ عقد العمل من قبل صاحب العمل وفقاً للمادة 80 من نظام العمل', 1),
(186, 0, 7, 'The worker reached retirement age according to Article 74 of the Labor Law', 'بلوغ العامل سن التقاعد وفقاً للمادة 74 من نظام العمل', 1),
(187, 0, 9, 'Permanent closure of the establishment according to Article 74 of the Labor Law', 'إغلاق المنشأة نهائياً وفقاً للمادة 74 من نظام العمل', 1),
(188, 0, 10, 'Termination of the activity in which the worker is employed according to Article 74 of the Labor Law', 'إنهاء النشاط الذي يعمل فيه العامل وفقاً للمادة 74 من نظام العمل', 1),
(189, 0, 12, 'Worker\'s inability to work according to Article 79 of the Labor Law', 'عجز العامل عن العمل وفقاً للمادة 79 من نظام العمل', 1),
(190, 0, 13, 'Termination of the contract without a legitimate reason according to Article 77 of the Labor Law', 'إنهاء العقد دون سبب مشروع وفقاً للمادة 77 من نظام العمل', 1),
(191, 0, 14, 'Death of the worker according to Article 79 of the Labor Law', 'وفاة العامل وفقاً للمادة 79 من نظام العمل', 1),
(192, 0, 16, 'Worker\'s inability to work (resulting from a work injury) according to Article 137 of the Labor Law', 'عجز العامل عن العمل (الناتج عن إصابة العمل) وفقاً للمادة 137 من نظام العمل', 1),
(193, 0, 17, 'Transfer of ownership of the individual establishment to a new owner and the worker refuses to continue according to Article 18 of the Labor Law', 'إنتقال ملكية المنشأة (الفردية) إلى مالك جديد وفقاً للمادة 18 من نظام العمل', 1),
(194, 0, 19, 'Termination of the contract by a female worker due to childbirth according to Article 87 of the Labor Law', 'إنهاء المرأة العاملة العقد بسبب الوضع وفقاً للمادة 87 من نظام العمل', 1),
(195, 0, 20, 'Termination of the contract by a female worker due to marriage within six months of marriage date according to Article 87 of the Labor Law', 'إنهاء المرأة العاملة العقد بسبب الزواج وفقاً للمادة 87 من نظام العمل', 1),
(196, 0, 21, 'Death of the employer according to Article 79 if the employer is an individual and the worker refuses to continue with the employer\'s heirs', 'وفاة صاحب العمل وفقاً للمادة 79 من نظام العمل إذا  كانت شخصيته قد روعيت في عقد العمل', 1),
(197, 0, 22, 'Direct termination of the contract by the worker according to Article 81 of the Labor Law', 'فسخ العقد من قبل العامل وفقاً للمادة 81 من نظام العمل', 1),
(198, 0, 1, 'Employee resignation', 'استقالة الموظف', 1),
(199, 0, 3, 'Direct termination of the contract during the probationary period according to Article 53 of  the Labor Law', 'فسخ العقد خلال فترة التجربة وفقاً للمادة 53 من نظام العمل', 1),
(200, 0, 4, 'By mutual agreement according to Article 74 of the Labor Law', 'بإتفاق الطرفين وفقاً للمادة 74 من نظام العمل', 1),
(201, 0, 5, 'Direct termination of the employment contract by the employer according to Article 80 of the Labor Law', 'فسخ عقد العمل من قبل صاحب العمل وفقاً للمادة 80 من نظام العمل', 1),
(202, 0, 6, 'Termination of the contract upon its expiration', 'إنتهاء العقد بإنتهاء مدته', 1),
(203, 0, 7, 'The worker reached retirement age according to Article 74 of the Labor Law', 'بلوغ العامل سن التقاعد وفقاً للمادة 74 من نظام العمل', 1),
(204, 0, 9, 'Permanent closure of the establishment according to Article 74 of the Labor Law', 'إغلاق المنشأة نهائياً وفقاً للمادة 74 من نظام العمل', 1),
(205, 0, 10, 'Termination of the activity in which the worker is employed according to Article 74 of the Labor Law', 'إنهاء النشاط الذي يعمل فيه العامل وفقاً للمادة 74 من نظام العمل', 1),
(206, 0, 12, 'Worker\'s inability to work according to Article 79 of the Labor Law', 'عجز العامل عن العمل وفقاً للمادة 79 من نظام العمل', 1),
(207, 0, 13, 'Termination of the contract without a legitimate reason according to Article 77 of the Labor Law', 'إنهاء العقد دون سبب مشروع وفقاً للمادة 77 من نظام العمل', 1),
(208, 0, 14, 'Death of the worker according to Article 79 of the Labor Law', 'وفاة العامل وفقاً للمادة 79 من نظام العمل', 1),
(209, 0, 15, 'Lack of desire to continue the contractual relationship based on mutual agreement', 'عدم الرغبة في استمرار العلاقة التعاقدية بناء على إرادة أحد الطرفين المنفردة في العقود الغير محددة المدة وفقاً للمادة 75 من نظام العمل', 1),
(210, 0, 16, 'Worker\'s inability to work (resulting from a work injury) according to Article 137 of the Labor Law', 'عجز العامل عن العمل (الناتج عن إصابة العمل) وفقاً للمادة 137 من نظام العمل', 1),
(211, 0, 17, 'Transfer of ownership of the individual establishment to a new owner and the worker refuses to continue according to Article 18 of the Labor Law', 'إنتقال ملكية المنشأة (الفردية) إلى مالك جديد وفقاً للمادة 18 من نظام العمل', 1),
(212, 0, 19, 'Termination of the contract by a female worker due to childbirth according to Article 87 of the Labor Law', 'إنهاء المرأة العاملة العقد بسبب الوضع وفقاً للمادة 87 من نظام العمل', 1),
(213, 0, 20, 'Termination of the contract by a female worker due to marriage within six months of marriage date according to Article 87 of the Labor Law', 'إنهاء المرأة العاملة العقد بسبب الزواج وفقاً للمادة 87 من نظام العمل', 1),
(214, 0, 21, 'Death of the employer according to Article 79 if the employer is an individual and the worker refuses to continue with the employer\'s heirs', 'وفاة صاحب العمل وفقاً للمادة 79 من نظام العمل إذا  كانت شخصيته قد روعيت في عقد العمل', 1),
(215, 0, 22, 'Direct termination of the contract by the worker according to Article 81 of the Labor Law', 'فسخ العقد من قبل العامل وفقاً للمادة 81 من نظام العمل', 1),
(216, 0, 3, 'Direct termination of the contract during the probationary period according to Article 53 of  the Labor Law', 'فسخ العقد خلال فترة التجربة وفقاً للمادة 53 من نظام العمل', 1),
(217, 0, 4, 'By mutual agreement according to Article 74 of the Labor Law', 'بإتفاق الطرفين وفقاً للمادة 74 من نظام العمل', 1),
(218, 0, 5, 'Direct termination of the employment contract by the employer according to Article 80 of the Labor Law', 'فسخ عقد العمل من قبل صاحب العمل وفقاً للمادة 80 من نظام العمل', 1),
(219, 0, 7, 'The worker reached retirement age according to Article 74 of the Labor Law', 'بلوغ العامل سن التقاعد وفقاً للمادة 74 من نظام العمل', 1),
(220, 0, 9, 'Permanent closure of the establishment according to Article 74 of the Labor Law', 'إغلاق المنشأة نهائياً وفقاً للمادة 74 من نظام العمل', 1),
(221, 0, 10, 'Termination of the activity in which the worker is employed according to Article 74 of the Labor Law', 'إنهاء النشاط الذي يعمل فيه العامل وفقاً للمادة 74 من نظام العمل', 1),
(222, 0, 12, 'Worker\'s inability to work according to Article 79 of the Labor Law', 'عجز العامل عن العمل وفقاً للمادة 79 من نظام العمل', 1),
(223, 0, 13, 'Termination of the contract without a legitimate reason according to Article 77 of the Labor Law', 'إنهاء العقد دون سبب مشروع وفقاً للمادة 77 من نظام العمل', 1),
(224, 0, 14, 'Death of the worker according to Article 79 of the Labor Law', 'وفاة العامل وفقاً للمادة 79 من نظام العمل', 1),
(225, 0, 16, 'Worker\'s inability to work (resulting from a work injury) according to Article 137 of the Labor Law', 'عجز العامل عن العمل (الناتج عن إصابة العمل) وفقاً للمادة 137 من نظام العمل', 1),
(226, 0, 17, 'Transfer of ownership of the individual establishment to a new owner and the worker refuses to continue according to Article 18 of the Labor Law', 'إنتقال ملكية المنشأة (الفردية) إلى مالك جديد وفقاً للمادة 18 من نظام العمل', 1),
(227, 0, 19, 'Termination of the contract by a female worker due to childbirth according to Article 87 of the Labor Law', 'إنهاء المرأة العاملة العقد بسبب الوضع وفقاً للمادة 87 من نظام العمل', 1),
(228, 0, 20, 'Termination of the contract by a female worker due to marriage within six months of marriage date according to Article 87 of the Labor Law', 'إنهاء المرأة العاملة العقد بسبب الزواج وفقاً للمادة 87 من نظام العمل', 1),
(229, 0, 21, 'Death of the employer according to Article 79 if the employer is an individual and the worker refuses to continue with the employer\'s heirs', 'وفاة صاحب العمل وفقاً للمادة 79 من نظام العمل إذا  كانت شخصيته قد روعيت في عقد العمل', 1),
(230, 0, 22, 'Direct termination of the contract by the worker according to Article 81 of the Labor Law', 'فسخ العقد من قبل العامل وفقاً للمادة 81 من نظام العمل', 1),
(231, 0, 1, 'Employee resignation', 'استقالة الموظف', 1),
(232, 0, 3, 'Direct termination of the contract during the probationary period according to Article 53 of  the Labor Law', 'فسخ العقد خلال فترة التجربة وفقاً للمادة 53 من نظام العمل', 1),
(233, 0, 4, 'By mutual agreement according to Article 74 of the Labor Law', 'بإتفاق الطرفين وفقاً للمادة 74 من نظام العمل', 1),
(234, 0, 5, 'Direct termination of the employment contract by the employer according to Article 80 of the Labor Law', 'فسخ عقد العمل من قبل صاحب العمل وفقاً للمادة 80 من نظام العمل', 1),
(235, 0, 6, 'Termination of the contract upon its expiration', 'إنتهاء العقد بإنتهاء مدته', 1),
(236, 0, 7, 'The worker reached retirement age according to Article 74 of the Labor Law', 'بلوغ العامل سن التقاعد وفقاً للمادة 74 من نظام العمل', 1),
(237, 0, 9, 'Permanent closure of the establishment according to Article 74 of the Labor Law', 'إغلاق المنشأة نهائياً وفقاً للمادة 74 من نظام العمل', 1),
(238, 0, 10, 'Termination of the activity in which the worker is employed according to Article 74 of the Labor Law', 'إنهاء النشاط الذي يعمل فيه العامل وفقاً للمادة 74 من نظام العمل', 1),
(239, 0, 12, 'Worker\'s inability to work according to Article 79 of the Labor Law', 'عجز العامل عن العمل وفقاً للمادة 79 من نظام العمل', 1),
(240, 0, 13, 'Termination of the contract without a legitimate reason according to Article 77 of the Labor Law', 'إنهاء العقد دون سبب مشروع وفقاً للمادة 77 من نظام العمل', 1),
(241, 0, 14, 'Death of the worker according to Article 79 of the Labor Law', 'وفاة العامل وفقاً للمادة 79 من نظام العمل', 1),
(242, 0, 15, 'Lack of desire to continue the contractual relationship based on mutual agreement', 'عدم الرغبة في استمرار العلاقة التعاقدية بناء على إرادة أحد الطرفين المنفردة في العقود الغير محددة المدة وفقاً للمادة 75 من نظام العمل', 1),
(243, 0, 16, 'Worker\'s inability to work (resulting from a work injury) according to Article 137 of the Labor Law', 'عجز العامل عن العمل (الناتج عن إصابة العمل) وفقاً للمادة 137 من نظام العمل', 1),
(244, 0, 17, 'Transfer of ownership of the individual establishment to a new owner and the worker refuses to continue according to Article 18 of the Labor Law', 'إنتقال ملكية المنشأة (الفردية) إلى مالك جديد وفقاً للمادة 18 من نظام العمل', 1),
(245, 0, 19, 'Termination of the contract by a female worker due to childbirth according to Article 87 of the Labor Law', 'إنهاء المرأة العاملة العقد بسبب الوضع وفقاً للمادة 87 من نظام العمل', 1),
(246, 0, 20, 'Termination of the contract by a female worker due to marriage within six months of marriage date according to Article 87 of the Labor Law', 'إنهاء المرأة العاملة العقد بسبب الزواج وفقاً للمادة 87 من نظام العمل', 1),
(247, 0, 21, 'Death of the employer according to Article 79 if the employer is an individual and the worker refuses to continue with the employer\'s heirs', 'وفاة صاحب العمل وفقاً للمادة 79 من نظام العمل إذا  كانت شخصيته قد روعيت في عقد العمل', 1),
(248, 0, 22, 'Direct termination of the contract by the worker according to Article 81 of the Labor Law', 'فسخ العقد من قبل العامل وفقاً للمادة 81 من نظام العمل', 1),
(249, 0, 3, 'Direct termination of the contract during the probationary period according to Article 53 of  the Labor Law', 'فسخ العقد خلال فترة التجربة وفقاً للمادة 53 من نظام العمل', 1),
(250, 0, 4, 'By mutual agreement according to Article 74 of the Labor Law', 'بإتفاق الطرفين وفقاً للمادة 74 من نظام العمل', 1),
(251, 0, 5, 'Direct termination of the employment contract by the employer according to Article 80 of the Labor Law', 'فسخ عقد العمل من قبل صاحب العمل وفقاً للمادة 80 من نظام العمل', 1),
(252, 0, 7, 'The worker reached retirement age according to Article 74 of the Labor Law', 'بلوغ العامل سن التقاعد وفقاً للمادة 74 من نظام العمل', 1),
(253, 0, 9, 'Permanent closure of the establishment according to Article 74 of the Labor Law', 'إغلاق المنشأة نهائياً وفقاً للمادة 74 من نظام العمل', 1),
(254, 0, 10, 'Termination of the activity in which the worker is employed according to Article 74 of the Labor Law', 'إنهاء النشاط الذي يعمل فيه العامل وفقاً للمادة 74 من نظام العمل', 1),
(255, 0, 12, 'Worker\'s inability to work according to Article 79 of the Labor Law', 'عجز العامل عن العمل وفقاً للمادة 79 من نظام العمل', 1),
(256, 0, 13, 'Termination of the contract without a legitimate reason according to Article 77 of the Labor Law', 'إنهاء العقد دون سبب مشروع وفقاً للمادة 77 من نظام العمل', 1),
(257, 0, 14, 'Death of the worker according to Article 79 of the Labor Law', 'وفاة العامل وفقاً للمادة 79 من نظام العمل', 1),
(258, 0, 16, 'Worker\'s inability to work (resulting from a work injury) according to Article 137 of the Labor Law', 'عجز العامل عن العمل (الناتج عن إصابة العمل) وفقاً للمادة 137 من نظام العمل', 1),
(259, 0, 17, 'Transfer of ownership of the individual establishment to a new owner and the worker refuses to continue according to Article 18 of the Labor Law', 'إنتقال ملكية المنشأة (الفردية) إلى مالك جديد وفقاً للمادة 18 من نظام العمل', 1),
(260, 0, 19, 'Termination of the contract by a female worker due to childbirth according to Article 87 of the Labor Law', 'إنهاء المرأة العاملة العقد بسبب الوضع وفقاً للمادة 87 من نظام العمل', 1),
(261, 0, 20, 'Termination of the contract by a female worker due to marriage within six months of marriage date according to Article 87 of the Labor Law', 'إنهاء المرأة العاملة العقد بسبب الزواج وفقاً للمادة 87 من نظام العمل', 1),
(262, 0, 21, 'Death of the employer according to Article 79 if the employer is an individual and the worker refuses to continue with the employer\'s heirs', 'وفاة صاحب العمل وفقاً للمادة 79 من نظام العمل إذا  كانت شخصيته قد روعيت في عقد العمل', 1),
(263, 0, 22, 'Direct termination of the contract by the worker according to Article 81 of the Labor Law', 'فسخ العقد من قبل العامل وفقاً للمادة 81 من نظام العمل', 1),
(264, 0, 1, 'Employee resignation', 'استقالة الموظف', 1),
(265, 0, 3, 'Direct termination of the contract during the probationary period according to Article 53 of  the Labor Law', 'فسخ العقد خلال فترة التجربة وفقاً للمادة 53 من نظام العمل', 1),
(266, 0, 4, 'By mutual agreement according to Article 74 of the Labor Law', 'بإتفاق الطرفين وفقاً للمادة 74 من نظام العمل', 1),
(267, 0, 5, 'Direct termination of the employment contract by the employer according to Article 80 of the Labor Law', 'فسخ عقد العمل من قبل صاحب العمل وفقاً للمادة 80 من نظام العمل', 1),
(268, 0, 6, 'Termination of the contract upon its expiration', 'إنتهاء العقد بإنتهاء مدته', 1),
(269, 0, 7, 'The worker reached retirement age according to Article 74 of the Labor Law', 'بلوغ العامل سن التقاعد وفقاً للمادة 74 من نظام العمل', 1),
(270, 0, 9, 'Permanent closure of the establishment according to Article 74 of the Labor Law', 'إغلاق المنشأة نهائياً وفقاً للمادة 74 من نظام العمل', 1),
(271, 0, 10, 'Termination of the activity in which the worker is employed according to Article 74 of the Labor Law', 'إنهاء النشاط الذي يعمل فيه العامل وفقاً للمادة 74 من نظام العمل', 1),
(272, 0, 12, 'Worker\'s inability to work according to Article 79 of the Labor Law', 'عجز العامل عن العمل وفقاً للمادة 79 من نظام العمل', 1),
(273, 0, 13, 'Termination of the contract without a legitimate reason according to Article 77 of the Labor Law', 'إنهاء العقد دون سبب مشروع وفقاً للمادة 77 من نظام العمل', 1),
(274, 0, 14, 'Death of the worker according to Article 79 of the Labor Law', 'وفاة العامل وفقاً للمادة 79 من نظام العمل', 1),
(275, 0, 15, 'Lack of desire to continue the contractual relationship based on mutual agreement', 'عدم الرغبة في استمرار العلاقة التعاقدية بناء على إرادة أحد الطرفين المنفردة في العقود الغير محددة المدة وفقاً للمادة 75 من نظام العمل', 1),
(276, 0, 16, 'Worker\'s inability to work (resulting from a work injury) according to Article 137 of the Labor Law', 'عجز العامل عن العمل (الناتج عن إصابة العمل) وفقاً للمادة 137 من نظام العمل', 1),
(277, 0, 17, 'Transfer of ownership of the individual establishment to a new owner and the worker refuses to continue according to Article 18 of the Labor Law', 'إنتقال ملكية المنشأة (الفردية) إلى مالك جديد وفقاً للمادة 18 من نظام العمل', 1),
(278, 0, 19, 'Termination of the contract by a female worker due to childbirth according to Article 87 of the Labor Law', 'إنهاء المرأة العاملة العقد بسبب الوضع وفقاً للمادة 87 من نظام العمل', 1),
(279, 0, 20, 'Termination of the contract by a female worker due to marriage within six months of marriage date according to Article 87 of the Labor Law', 'إنهاء المرأة العاملة العقد بسبب الزواج وفقاً للمادة 87 من نظام العمل', 1),
(280, 0, 21, 'Death of the employer according to Article 79 if the employer is an individual and the worker refuses to continue with the employer\'s heirs', 'وفاة صاحب العمل وفقاً للمادة 79 من نظام العمل إذا  كانت شخصيته قد روعيت في عقد العمل', 1),
(281, 0, 22, 'Direct termination of the contract by the worker according to Article 81 of the Labor Law', 'فسخ العقد من قبل العامل وفقاً للمادة 81 من نظام العمل', 1),
(282, 0, 3, 'Direct termination of the contract during the probationary period according to Article 53 of  the Labor Law', 'فسخ العقد خلال فترة التجربة وفقاً للمادة 53 من نظام العمل', 1),
(283, 0, 4, 'By mutual agreement according to Article 74 of the Labor Law', 'بإتفاق الطرفين وفقاً للمادة 74 من نظام العمل', 1),
(284, 0, 5, 'Direct termination of the employment contract by the employer according to Article 80 of the Labor Law', 'فسخ عقد العمل من قبل صاحب العمل وفقاً للمادة 80 من نظام العمل', 1),
(285, 0, 7, 'The worker reached retirement age according to Article 74 of the Labor Law', 'بلوغ العامل سن التقاعد وفقاً للمادة 74 من نظام العمل', 1);
INSERT INTO `eos_calc_` (`id`, `prid`, `cid`, `details`, `details_ar`, `status`) VALUES
(286, 0, 9, 'Permanent closure of the establishment according to Article 74 of the Labor Law', 'إغلاق المنشأة نهائياً وفقاً للمادة 74 من نظام العمل', 1),
(287, 0, 10, 'Termination of the activity in which the worker is employed according to Article 74 of the Labor Law', 'إنهاء النشاط الذي يعمل فيه العامل وفقاً للمادة 74 من نظام العمل', 1),
(288, 0, 12, 'Worker\'s inability to work according to Article 79 of the Labor Law', 'عجز العامل عن العمل وفقاً للمادة 79 من نظام العمل', 1),
(289, 0, 13, 'Termination of the contract without a legitimate reason according to Article 77 of the Labor Law', 'إنهاء العقد دون سبب مشروع وفقاً للمادة 77 من نظام العمل', 1),
(290, 0, 14, 'Death of the worker according to Article 79 of the Labor Law', 'وفاة العامل وفقاً للمادة 79 من نظام العمل', 1),
(291, 0, 16, 'Worker\'s inability to work (resulting from a work injury) according to Article 137 of the Labor Law', 'عجز العامل عن العمل (الناتج عن إصابة العمل) وفقاً للمادة 137 من نظام العمل', 1),
(292, 0, 17, 'Transfer of ownership of the individual establishment to a new owner and the worker refuses to continue according to Article 18 of the Labor Law', 'إنتقال ملكية المنشأة (الفردية) إلى مالك جديد وفقاً للمادة 18 من نظام العمل', 1),
(293, 0, 19, 'Termination of the contract by a female worker due to childbirth according to Article 87 of the Labor Law', 'إنهاء المرأة العاملة العقد بسبب الوضع وفقاً للمادة 87 من نظام العمل', 1),
(294, 0, 20, 'Termination of the contract by a female worker due to marriage within six months of marriage date according to Article 87 of the Labor Law', 'إنهاء المرأة العاملة العقد بسبب الزواج وفقاً للمادة 87 من نظام العمل', 1),
(295, 0, 21, 'Death of the employer according to Article 79 if the employer is an individual and the worker refuses to continue with the employer\'s heirs', 'وفاة صاحب العمل وفقاً للمادة 79 من نظام العمل إذا  كانت شخصيته قد روعيت في عقد العمل', 1),
(296, 0, 22, 'Direct termination of the contract by the worker according to Article 81 of the Labor Law', 'فسخ العقد من قبل العامل وفقاً للمادة 81 من نظام العمل', 1),
(297, 0, 1, 'Employee resignation', 'استقالة الموظف', 1),
(298, 0, 3, 'Direct termination of the contract during the probationary period according to Article 53 of  the Labor Law', 'فسخ العقد خلال فترة التجربة وفقاً للمادة 53 من نظام العمل', 1),
(299, 0, 4, 'By mutual agreement according to Article 74 of the Labor Law', 'بإتفاق الطرفين وفقاً للمادة 74 من نظام العمل', 1),
(300, 0, 5, 'Direct termination of the employment contract by the employer according to Article 80 of the Labor Law', 'فسخ عقد العمل من قبل صاحب العمل وفقاً للمادة 80 من نظام العمل', 1),
(301, 0, 6, 'Termination of the contract upon its expiration', 'إنتهاء العقد بإنتهاء مدته', 1),
(302, 0, 7, 'The worker reached retirement age according to Article 74 of the Labor Law', 'بلوغ العامل سن التقاعد وفقاً للمادة 74 من نظام العمل', 1),
(303, 0, 9, 'Permanent closure of the establishment according to Article 74 of the Labor Law', 'إغلاق المنشأة نهائياً وفقاً للمادة 74 من نظام العمل', 1),
(304, 0, 10, 'Termination of the activity in which the worker is employed according to Article 74 of the Labor Law', 'إنهاء النشاط الذي يعمل فيه العامل وفقاً للمادة 74 من نظام العمل', 1),
(305, 0, 12, 'Worker\'s inability to work according to Article 79 of the Labor Law', 'عجز العامل عن العمل وفقاً للمادة 79 من نظام العمل', 1),
(306, 0, 13, 'Termination of the contract without a legitimate reason according to Article 77 of the Labor Law', 'إنهاء العقد دون سبب مشروع وفقاً للمادة 77 من نظام العمل', 1),
(307, 0, 14, 'Death of the worker according to Article 79 of the Labor Law', 'وفاة العامل وفقاً للمادة 79 من نظام العمل', 1),
(308, 0, 15, 'Lack of desire to continue the contractual relationship based on mutual agreement', 'عدم الرغبة في استمرار العلاقة التعاقدية بناء على إرادة أحد الطرفين المنفردة في العقود الغير محددة المدة وفقاً للمادة 75 من نظام العمل', 1),
(309, 0, 16, 'Worker\'s inability to work (resulting from a work injury) according to Article 137 of the Labor Law', 'عجز العامل عن العمل (الناتج عن إصابة العمل) وفقاً للمادة 137 من نظام العمل', 1),
(310, 0, 17, 'Transfer of ownership of the individual establishment to a new owner and the worker refuses to continue according to Article 18 of the Labor Law', 'إنتقال ملكية المنشأة (الفردية) إلى مالك جديد وفقاً للمادة 18 من نظام العمل', 1),
(311, 0, 19, 'Termination of the contract by a female worker due to childbirth according to Article 87 of the Labor Law', 'إنهاء المرأة العاملة العقد بسبب الوضع وفقاً للمادة 87 من نظام العمل', 1),
(312, 0, 20, 'Termination of the contract by a female worker due to marriage within six months of marriage date according to Article 87 of the Labor Law', 'إنهاء المرأة العاملة العقد بسبب الزواج وفقاً للمادة 87 من نظام العمل', 1),
(313, 0, 21, 'Death of the employer according to Article 79 if the employer is an individual and the worker refuses to continue with the employer\'s heirs', 'وفاة صاحب العمل وفقاً للمادة 79 من نظام العمل إذا  كانت شخصيته قد روعيت في عقد العمل', 1),
(314, 0, 22, 'Direct termination of the contract by the worker according to Article 81 of the Labor Law', 'فسخ العقد من قبل العامل وفقاً للمادة 81 من نظام العمل', 1),
(315, 0, 3, 'Direct termination of the contract during the probationary period according to Article 53 of  the Labor Law', 'فسخ العقد خلال فترة التجربة وفقاً للمادة 53 من نظام العمل', 1),
(316, 0, 4, 'By mutual agreement according to Article 74 of the Labor Law', 'بإتفاق الطرفين وفقاً للمادة 74 من نظام العمل', 1),
(317, 0, 5, 'Direct termination of the employment contract by the employer according to Article 80 of the Labor Law', 'فسخ عقد العمل من قبل صاحب العمل وفقاً للمادة 80 من نظام العمل', 1),
(318, 0, 7, 'The worker reached retirement age according to Article 74 of the Labor Law', 'بلوغ العامل سن التقاعد وفقاً للمادة 74 من نظام العمل', 1),
(319, 0, 9, 'Permanent closure of the establishment according to Article 74 of the Labor Law', 'إغلاق المنشأة نهائياً وفقاً للمادة 74 من نظام العمل', 1),
(320, 0, 10, 'Termination of the activity in which the worker is employed according to Article 74 of the Labor Law', 'إنهاء النشاط الذي يعمل فيه العامل وفقاً للمادة 74 من نظام العمل', 1),
(321, 0, 12, 'Worker\'s inability to work according to Article 79 of the Labor Law', 'عجز العامل عن العمل وفقاً للمادة 79 من نظام العمل', 1),
(322, 0, 13, 'Termination of the contract without a legitimate reason according to Article 77 of the Labor Law', 'إنهاء العقد دون سبب مشروع وفقاً للمادة 77 من نظام العمل', 1),
(323, 0, 14, 'Death of the worker according to Article 79 of the Labor Law', 'وفاة العامل وفقاً للمادة 79 من نظام العمل', 1),
(324, 0, 16, 'Worker\'s inability to work (resulting from a work injury) according to Article 137 of the Labor Law', 'عجز العامل عن العمل (الناتج عن إصابة العمل) وفقاً للمادة 137 من نظام العمل', 1),
(325, 0, 17, 'Transfer of ownership of the individual establishment to a new owner and the worker refuses to continue according to Article 18 of the Labor Law', 'إنتقال ملكية المنشأة (الفردية) إلى مالك جديد وفقاً للمادة 18 من نظام العمل', 1),
(326, 0, 19, 'Termination of the contract by a female worker due to childbirth according to Article 87 of the Labor Law', 'إنهاء المرأة العاملة العقد بسبب الوضع وفقاً للمادة 87 من نظام العمل', 1),
(327, 0, 20, 'Termination of the contract by a female worker due to marriage within six months of marriage date according to Article 87 of the Labor Law', 'إنهاء المرأة العاملة العقد بسبب الزواج وفقاً للمادة 87 من نظام العمل', 1),
(328, 0, 21, 'Death of the employer according to Article 79 if the employer is an individual and the worker refuses to continue with the employer\'s heirs', 'وفاة صاحب العمل وفقاً للمادة 79 من نظام العمل إذا  كانت شخصيته قد روعيت في عقد العمل', 1),
(329, 0, 22, 'Direct termination of the contract by the worker according to Article 81 of the Labor Law', 'فسخ العقد من قبل العامل وفقاً للمادة 81 من نظام العمل', 1),
(330, 0, 1, 'Employee resignation', 'استقالة الموظف', 1),
(331, 0, 3, 'Direct termination of the contract during the probationary period according to Article 53 of  the Labor Law', 'فسخ العقد خلال فترة التجربة وفقاً للمادة 53 من نظام العمل', 1),
(332, 0, 4, 'By mutual agreement according to Article 74 of the Labor Law', 'بإتفاق الطرفين وفقاً للمادة 74 من نظام العمل', 1),
(333, 0, 5, 'Direct termination of the employment contract by the employer according to Article 80 of the Labor Law', 'فسخ عقد العمل من قبل صاحب العمل وفقاً للمادة 80 من نظام العمل', 1),
(334, 0, 6, 'Termination of the contract upon its expiration', 'إنتهاء العقد بإنتهاء مدته', 1),
(335, 0, 7, 'The worker reached retirement age according to Article 74 of the Labor Law', 'بلوغ العامل سن التقاعد وفقاً للمادة 74 من نظام العمل', 1),
(336, 0, 9, 'Permanent closure of the establishment according to Article 74 of the Labor Law', 'إغلاق المنشأة نهائياً وفقاً للمادة 74 من نظام العمل', 1),
(337, 0, 10, 'Termination of the activity in which the worker is employed according to Article 74 of the Labor Law', 'إنهاء النشاط الذي يعمل فيه العامل وفقاً للمادة 74 من نظام العمل', 1),
(338, 0, 12, 'Worker\'s inability to work according to Article 79 of the Labor Law', 'عجز العامل عن العمل وفقاً للمادة 79 من نظام العمل', 1),
(339, 0, 13, 'Termination of the contract without a legitimate reason according to Article 77 of the Labor Law', 'إنهاء العقد دون سبب مشروع وفقاً للمادة 77 من نظام العمل', 1),
(340, 0, 14, 'Death of the worker according to Article 79 of the Labor Law', 'وفاة العامل وفقاً للمادة 79 من نظام العمل', 1),
(341, 0, 15, 'Lack of desire to continue the contractual relationship based on mutual agreement', 'عدم الرغبة في استمرار العلاقة التعاقدية بناء على إرادة أحد الطرفين المنفردة في العقود الغير محددة المدة وفقاً للمادة 75 من نظام العمل', 1),
(342, 0, 16, 'Worker\'s inability to work (resulting from a work injury) according to Article 137 of the Labor Law', 'عجز العامل عن العمل (الناتج عن إصابة العمل) وفقاً للمادة 137 من نظام العمل', 1),
(343, 0, 17, 'Transfer of ownership of the individual establishment to a new owner and the worker refuses to continue according to Article 18 of the Labor Law', 'إنتقال ملكية المنشأة (الفردية) إلى مالك جديد وفقاً للمادة 18 من نظام العمل', 1),
(344, 0, 19, 'Termination of the contract by a female worker due to childbirth according to Article 87 of the Labor Law', 'إنهاء المرأة العاملة العقد بسبب الوضع وفقاً للمادة 87 من نظام العمل', 1),
(345, 0, 20, 'Termination of the contract by a female worker due to marriage within six months of marriage date according to Article 87 of the Labor Law', 'إنهاء المرأة العاملة العقد بسبب الزواج وفقاً للمادة 87 من نظام العمل', 1),
(346, 0, 21, 'Death of the employer according to Article 79 if the employer is an individual and the worker refuses to continue with the employer\'s heirs', 'وفاة صاحب العمل وفقاً للمادة 79 من نظام العمل إذا  كانت شخصيته قد روعيت في عقد العمل', 1),
(347, 0, 22, 'Direct termination of the contract by the worker according to Article 81 of the Labor Law', 'فسخ العقد من قبل العامل وفقاً للمادة 81 من نظام العمل', 1),
(348, 0, 3, 'Direct termination of the contract during the probationary period according to Article 53 of  the Labor Law', 'فسخ العقد خلال فترة التجربة وفقاً للمادة 53 من نظام العمل', 1),
(349, 0, 4, 'By mutual agreement according to Article 74 of the Labor Law', 'بإتفاق الطرفين وفقاً للمادة 74 من نظام العمل', 1),
(350, 0, 5, 'Direct termination of the employment contract by the employer according to Article 80 of the Labor Law', 'فسخ عقد العمل من قبل صاحب العمل وفقاً للمادة 80 من نظام العمل', 1),
(351, 0, 7, 'The worker reached retirement age according to Article 74 of the Labor Law', 'بلوغ العامل سن التقاعد وفقاً للمادة 74 من نظام العمل', 1),
(352, 0, 9, 'Permanent closure of the establishment according to Article 74 of the Labor Law', 'إغلاق المنشأة نهائياً وفقاً للمادة 74 من نظام العمل', 1),
(353, 0, 10, 'Termination of the activity in which the worker is employed according to Article 74 of the Labor Law', 'إنهاء النشاط الذي يعمل فيه العامل وفقاً للمادة 74 من نظام العمل', 1),
(354, 0, 12, 'Worker\'s inability to work according to Article 79 of the Labor Law', 'عجز العامل عن العمل وفقاً للمادة 79 من نظام العمل', 1),
(355, 0, 13, 'Termination of the contract without a legitimate reason according to Article 77 of the Labor Law', 'إنهاء العقد دون سبب مشروع وفقاً للمادة 77 من نظام العمل', 1),
(356, 0, 14, 'Death of the worker according to Article 79 of the Labor Law', 'وفاة العامل وفقاً للمادة 79 من نظام العمل', 1),
(357, 0, 16, 'Worker\'s inability to work (resulting from a work injury) according to Article 137 of the Labor Law', 'عجز العامل عن العمل (الناتج عن إصابة العمل) وفقاً للمادة 137 من نظام العمل', 1),
(358, 0, 17, 'Transfer of ownership of the individual establishment to a new owner and the worker refuses to continue according to Article 18 of the Labor Law', 'إنتقال ملكية المنشأة (الفردية) إلى مالك جديد وفقاً للمادة 18 من نظام العمل', 1),
(359, 0, 19, 'Termination of the contract by a female worker due to childbirth according to Article 87 of the Labor Law', 'إنهاء المرأة العاملة العقد بسبب الوضع وفقاً للمادة 87 من نظام العمل', 1),
(360, 0, 20, 'Termination of the contract by a female worker due to marriage within six months of marriage date according to Article 87 of the Labor Law', 'إنهاء المرأة العاملة العقد بسبب الزواج وفقاً للمادة 87 من نظام العمل', 1),
(361, 0, 21, 'Death of the employer according to Article 79 if the employer is an individual and the worker refuses to continue with the employer\'s heirs', 'وفاة صاحب العمل وفقاً للمادة 79 من نظام العمل إذا  كانت شخصيته قد روعيت في عقد العمل', 1),
(362, 0, 22, 'Direct termination of the contract by the worker according to Article 81 of the Labor Law', 'فسخ العقد من قبل العامل وفقاً للمادة 81 من نظام العمل', 1),
(363, 0, 1, 'Employee resignation', 'استقالة الموظف', 1);

-- --------------------------------------------------------

--
-- Table structure for table `gallery`
--

CREATE TABLE `gallery` (
  `id` int(11) NOT NULL,
  `image` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `status` varchar(10) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `gallery`
--

INSERT INTO `gallery` (`id`, `image`, `details`, `status`, `created_at`, `updated_at`) VALUES
(1, '1676871988-WhatsApp Image 2021-05-26 at 12.31.25 PM.jpeg', NULL, '1', '2023-02-20 13:46:28', '2023-02-20 05:46:28'),
(2, '1676871988-WhatsApp Image 2021-05-26 at 12.28.28 PM.jpeg', NULL, '1', '2023-02-20 13:46:28', '2023-02-20 05:46:28'),
(3, '1676871988-12.jpg', NULL, '1', '2023-02-20 13:46:28', '2023-02-20 05:46:28'),
(4, '1676871988-11.jpg', NULL, '1', '2023-02-20 13:46:28', '2023-02-20 05:46:28'),
(5, '1676871988-10.jpg', NULL, '1', '2023-02-20 13:46:28', '2023-02-20 05:46:28'),
(6, '1676871989-9.jpg', NULL, '1', '2023-02-20 13:46:29', '2023-02-20 05:46:29'),
(7, '1676871989-8.jpg', NULL, '1', '2023-02-20 13:46:29', '2023-02-20 05:46:29'),
(8, '1676871989-7.jpg', NULL, '1', '2023-02-20 13:46:29', '2023-02-20 05:46:29'),
(9, '1676871989-6.jpg', NULL, '1', '2023-02-20 13:46:29', '2023-02-20 05:46:29'),
(10, '1676871989-5.jpg', NULL, '1', '2023-02-20 13:46:29', '2023-02-20 05:46:29'),
(11, '1676871989-4.jpg', NULL, '1', '2023-02-20 13:46:29', '2023-02-20 05:46:29'),
(12, '1676871989-3.jpg', NULL, '1', '2023-02-20 13:46:29', '2023-02-20 05:46:29'),
(13, '1676871989-2.jpg', NULL, '1', '2023-02-20 13:46:29', '2023-02-20 05:46:29'),
(14, '1676871989-1.jpg', NULL, '1', '2023-02-20 13:46:29', '2023-02-20 05:46:29');

-- --------------------------------------------------------

--
-- Table structure for table `location_contract`
--

CREATE TABLE `location_contract` (
  `id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `owner_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `owner_number` varchar(150) NOT NULL,
  `owner_email` varchar(150) NOT NULL,
  `contract_no` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `start_cont_date` varchar(255) NOT NULL,
  `end_cont_date` varchar(255) NOT NULL,
  `rent` varchar(255) NOT NULL,
  `others` varchar(255) NOT NULL,
  `service` varchar(255) NOT NULL,
  `elect_prc` varchar(255) NOT NULL,
  `water_prc` varchar(255) NOT NULL,
  `incuranse_prc` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `location_docu`
--

CREATE TABLE `location_docu` (
  `id` int(11) NOT NULL,
  `location_id` varchar(50) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `docu_ext` varchar(20) NOT NULL,
  `created_at` timestamp NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `location_img`
--

CREATE TABLE `location_img` (
  `id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `in_img` varchar(255) NOT NULL,
  `out_img` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `location_img`
--

INSERT INTO `location_img` (`id`, `location_id`, `in_img`, `out_img`, `created_at`, `updated_at`) VALUES
(1, 3, './assets/location_content/default_in.jpg', './assets/location_content/default_in.jpg', '2025-09-02 08:45:10', '2025-09-02 08:45:10'),
(2, 4, './assets/location_content/default_in.jpg', './assets/location_content/default_in.jpg', '2025-09-02 08:53:46', '2025-09-02 08:53:46');

-- --------------------------------------------------------

--
-- Table structure for table `machines`
--

CREATE TABLE `machines` (
  `id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `m_id` varchar(255) NOT NULL,
  `name_mach` varchar(255) NOT NULL,
  `maker_name` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `made_year` varchar(255) NOT NULL,
  `remarks` varchar(255) NOT NULL,
  `serial` text NOT NULL,
  `serial_2` text NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT '1',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `machines`
--

INSERT INTO `machines` (`id`, `location_id`, `m_id`, `name_mach`, `maker_name`, `location`, `made_year`, `remarks`, `serial`, `serial_2`, `status`, `updated_at`, `created_at`) VALUES
(1, 1, 'EM2H001', 'ESPRESSO MACHINE 2 Hole', 'Bazzera', 'JM 01', '2003', '', 'AN00732613', '', '1', '2022-07-18 09:39:32', '2020-10-07 04:49:09'),
(2, 1, 'SM3H004', 'SLUSH MACHINE 3 hls', 'Ugolini', 'JM 01', '2003', '', '447875', '', '1', '2022-07-18 09:39:32', '2020-10-07 05:03:45'),
(3, 2, 'EM2H001', 'ESPRESSO MACHINE 2 Hole', 'Bazzera', 'JM 03', '2003', '', 'AN005615305', '', '1', '2022-07-18 09:39:32', '2020-10-07 06:34:32'),
(4, 25, 'SM3H004', 'SLUSH MACHINE 3 hls', 'Ugolini', 'JM 38', '2003', '', '466002', '', '1', '2022-07-18 09:39:32', '2020-10-07 06:42:41'),
(5, 3, 'EM3H002', 'ESPRESSO MACHINE 3 Hole', 'Bazzera', 'JM 06', '2002', '', '22082', '', '1', '2022-07-18 09:39:32', '2020-10-07 06:43:53'),
(6, 13, 'SM3H004', 'SLUSH MACHINE 3 hls', 'Ugolini', 'JM 19', '2003', '', '176890', '', '1', '2022-07-18 09:39:32', '2020-10-07 06:45:39'),
(7, 3, 'SM2H003', 'SLUSH MACHINE 2 hls', 'Ugolini', 'JM 06', '2002', '', '153504', '', '1', '2022-07-18 09:39:32', '2020-10-07 06:47:24'),
(8, 4, 'EM3H002', 'ESPRESSO MACHINE 3 Hole', 'Bazzera', 'JM 08', '2002', '', 'ANCC2000-17', '', '1', '2022-07-18 09:39:32', '2020-10-07 06:48:39'),
(9, 5, 'EM2H001', 'ESPRESSO MACHINE 2 Hole', 'Bazzera', 'JM 10', '2004', '', 'AM005363004', '', '1', '2022-07-18 09:39:32', '2020-10-07 06:49:24'),
(10, 6, 'EM3H002', 'ESPRESSO MACHINE 3 Hole', 'Bazzera', 'JM 11', '2007', '', 'AM006055307', '', '1', '2022-07-18 09:39:32', '2020-10-07 06:50:26'),
(11, 7, 'EM3H002', 'ESPRESSO MACHINE 3 Hole', 'Bazzera', 'JM 12', '2005', '', '44814', '', '1', '2022-07-18 09:39:32', '2020-10-07 07:09:18'),
(12, 8, 'EM2H001', 'ESPRESSO MACHINE 2 Hole', 'Bazzera', 'JM 14', '2001', '', 'AM007731313', '', '1', '2022-07-18 09:39:32', '2020-10-07 07:12:54'),
(13, 9, 'EM2H001', 'ESPRESSO MACHINE 2 Hole', 'Bazzera', 'JM 15', '2002', '', 'ANCC2002-11', '', '1', '2022-07-18 09:39:32', '2020-10-07 08:08:13'),
(14, 10, 'EM3H002', 'ESPRESSO MACHINE 3 Hole', 'Bazzera', 'JM 16', '2002', '', '44819', '', '1', '2022-07-18 09:39:32', '2020-10-07 08:10:11'),
(15, 11, 'EM2H001', 'ESPRESSO MACHINE 2 Hole', 'Bazzera', 'JM 17', '2005', '', 'AM007987214', '', '1', '2022-07-18 09:39:32', '2020-10-07 08:12:52'),
(16, 12, 'EM2H001', 'ESPRESSO MACHINE 2 Hole', 'Bazzera', 'JM 18', '2001', '', 'AN007731313', '', '1', '2022-07-18 09:39:32', '2020-10-07 08:18:11'),
(17, 13, 'EM3H002', 'ESPRESSO MACHINE 3 Hole', 'Bazzera', 'JM 19', '2009', '', '48119', '', '1', '2022-07-18 09:39:32', '2020-10-07 08:19:19'),
(18, 14, 'EM3H002', 'ESPRESSO MACHINE 3 Hole', 'Bazzera', 'JM 20', '2002', 'comp.srv.done fm hls.July.2019', '44815', '', '1', '2022-07-18 09:39:32', '2020-10-07 08:21:16'),
(19, 61, 'EM2H001', 'ESPRESSO MACHINE 2 Hole', 'Bazzera', 'JM 21', '2003', 'comp.srv.done fm hls.Feb.2019', 'AM005945506', '', '1', '2022-07-18 09:39:32', '2020-10-07 08:24:08'),
(20, 15, 'EM3H002', 'ESPRESSO MACHINE 3 Hole', 'Bazzera', 'JM 22', '2002', '', '49225', '', '1', '2022-07-18 09:39:32', '2020-10-07 08:42:52'),
(21, 16, 'EM3H002', 'ESPRESSO MACHINE 3 Hole', 'Bazzera', 'JM 23', '2006', '', '50903', '', '1', '2022-07-18 09:39:32', '2020-10-07 08:44:06'),
(22, 17, 'EM3H002', 'ESPRESSO MACHINE 3 Hole', 'Bazzera', 'JM 24', '2004', '', '50915', '', '1', '2022-07-18 09:39:32', '2020-10-07 08:52:05'),
(23, 18, 'EM3H002', 'ESPRESSO MACHINE 3 Hole', 'Bazzera', 'JM 26', '2002', '', 'ANCC200217', '', '1', '2022-07-18 09:39:32', '2020-10-07 08:53:54'),
(24, 19, 'EM2H001', 'ESPRESSO MACHINE 2 Hole', 'Bazzera', 'JM 28', '2002', '', '46440', '', '1', '2022-07-18 09:39:32', '2020-10-07 08:55:26'),
(25, 20, 'EM3H002', 'ESPRESSO MACHINE 3 Hole', 'Bazzera', 'JM 29', '2002', '', 'ANCC200229', '', '1', '2022-07-18 09:39:32', '2020-10-07 08:58:19'),
(26, 21, 'EM2H001', 'ESPRESSO MACHINE 2 Hole', 'Bazzera', 'JM 31', '2007', '', 'AM0063535808', '', '1', '2022-07-18 09:39:32', '2020-10-07 09:11:08'),
(27, 22, 'EM3H002', 'ESPRESSO MACHINE 3 Hole', 'Bazzera', 'JM 32', '2001', '', 'ANCC2009-17', '', '1', '2022-07-18 09:39:32', '2020-10-07 09:13:36'),
(28, 23, 'EM2H001', 'ESPRESSO MACHINE 2 Hole', 'Bazzera', 'JM 33', '2001', '', 'AM00545606', '', '1', '2022-07-18 09:39:32', '2020-10-07 09:15:53'),
(29, 24, 'EM2H001', 'ESPRESSO MACHINE 2 Hole', 'Bazzera', 'JM 34', '2002', '', 'AM005614905', '', '1', '2022-07-18 09:39:32', '2020-10-07 09:17:46'),
(30, 25, 'EM3H002', 'ESPRESSO MACHINE 3 Hole', 'Bazzera', 'JM 38', '2001', '', '50928', '', '1', '2022-07-18 09:39:32', '2020-10-07 09:18:52'),
(31, 26, 'EM3H002', 'ESPRESSO MACHINE 3 Hole', 'Bazzera', 'JM 39', '2006', '', '43222', '', '1', '2022-07-18 09:39:32', '2020-10-07 09:20:03'),
(32, 35, 'EM2H001', 'ESPRESSO MACHINE 2 Hole', 'Bazzera', 'JMUM 01', '2002', '', 'AM005363004', '', '1', '2022-07-18 09:39:32', '2020-10-07 09:21:33'),
(33, 36, 'EM3H002', 'ESPRESSO MACHINE 3 Hole', 'Bazzera', 'JMUM 02', '2006', '', 'M2/5520512', '', '1', '2022-07-18 09:39:32', '2020-10-07 09:24:56'),
(34, 37, 'EM3H002', 'ESPRESSO MACHINE 3 Hole', 'Bazzera', 'JMUM 03', '2006', '', '40053', '', '1', '2022-07-18 09:39:32', '2020-10-07 09:26:55'),
(35, 39, 'EM3H002', 'ESPRESSO MACHINE 3 Hole', 'Bazzera', 'JMUM 05', '2006', '', '46457', '', '1', '2022-07-18 09:39:32', '2020-10-07 09:30:20'),
(36, 38, 'EM3H002', 'ESPRESSO MACHINE 3 Hole', 'Cimbali', 'JMUM 04', '2012', '', '1297427', '', '1', '2022-07-18 09:39:32', '2020-10-07 09:31:16'),
(37, 42, 'EM3H002', 'ESPRESSO MACHINE 3 Hole', 'Bazzera', 'JMUBTF 01', '2005', '', '40246', '', '1', '2022-07-18 09:39:32', '2020-10-07 09:32:45'),
(38, 41, 'EM2H001', 'ESPRESSO MACHINE 2 Hole', 'Bazzera', 'JMUF 03', '2003', '', 'AM007987914', '', '1', '2022-07-18 09:39:32', '2020-10-07 09:34:24'),
(39, 43, 'EM3H002', 'ESPRESSO MACHINE 3 Hole', 'Bazzera', 'JMUBT 01', '2003', '', 'AM007730113', '', '1', '2022-07-18 09:39:32', '2020-10-08 05:37:42'),
(40, 44, 'EM3H002', 'ESPRESSO MACHINE 3 Hole', 'Bazzera', 'JMUBT 02 ', '2003', '', 'AM0077325', '', '1', '2022-07-18 09:39:32', '2020-10-08 05:39:37'),
(41, 45, 'EM3H002', 'ESPRESSO MACHINE 3 Hole', 'Bazzera', 'JMUBT 03', '2004', '', 'AM07626613', '', '1', '2022-07-18 09:39:32', '2020-10-08 05:40:58'),
(42, 84, 'EM2H001', 'ESPRESSO MACHINE 2 Hole', 'Bazzera', 'JM 04', '2007', '', 'AM0079881014', '', '1', '2022-07-18 09:39:32', '2020-10-08 05:42:24'),
(43, 27, 'EM3H002', 'ESPRESSO MACHINE 3 Hole', 'Bazzera', 'MM 01', '2002', '', '40250.220', '', '1', '2022-07-18 09:39:32', '2020-10-08 05:44:08'),
(44, 28, 'EM2H001', 'ESPRESSO MACHINE 2 Hole', 'Bazzera', 'MM 02', '2009', 'Srvce.done 2018', 'AM006556309', '', '1', '2022-07-18 09:39:32', '2020-10-08 05:47:45'),
(45, 29, 'EM2H001', 'ESPRESSO MACHINE 2 Hole', 'Bazzera', 'MM 03', '2009', '', 'AM006556109', '', '1', '2022-07-18 09:39:32', '2020-10-08 05:49:22'),
(46, 30, 'EM3H002', 'ESPRESSO MACHINE 3 Hole', 'Bazzera', 'MM 05', '2002', 'Srvce.done 2018', '44816.220', '', '1', '2022-07-26 06:07:20', '2020-10-08 05:50:57'),
(47, 31, 'EM3H002', 'ESPRESSO MACHINE 3 Hole', 'Bazzera', 'YM 01', '2005', '', 'AM005992307', '', '1', '2022-07-18 09:39:32', '2020-10-08 05:57:16'),
(48, 32, 'EM2H001', 'ESPRESSO MACHINE 2 Hole', 'Bazzera', 'YM 02', '2001', '', '206433250', '', '1', '2022-07-18 09:39:32', '2020-10-08 05:58:54'),
(49, 33, 'EM2H001', 'ESPRESSO MACHINE 2 Hole', 'Bazzera', 'YM 03', '2001', '', '260943750', '', '1', '2022-07-18 09:39:32', '2020-10-08 06:00:23'),
(50, 80, 'EM2H001', 'ESPRESSO MACHINE 2 Hole', 'Bazzera', 'YM 04', '2002', 'Ym04 closed shop as a spare in yanbu', '261123750', '', '1', '2022-07-18 09:39:32', '2020-10-08 06:32:33'),
(51, 34, 'EM2H001', 'ESPRESSO MACHINE 2 Hole', 'Bazzera', 'YUM 01', '2005', 'Transfer to Jeddah Coffee Store', 'AM007625813', '', '1', '2022-07-18 09:39:32', '2020-10-08 06:34:14'),
(53, 1, 'ORG', 'Orange Machine', 'CITROCASA', 'JM 01', '2013', '', '100-727', '', '1', '2022-07-18 09:39:32', '2020-10-12 06:09:01'),
(54, 1, 'ICM', 'Ice Cub Machine ', 'MIGEL', 'JM 01', '2013', '', 'NO1328234', '', '1', '2022-07-18 09:39:32', '2020-10-12 06:52:54'),
(55, 1, 'ICMWP', 'Ice Machine Water Pump', 'FLOJET', 'JM 01', '2013', '', 'AJ1PUMP', '', '1', '2022-07-18 09:39:32', '2020-10-12 06:57:44'),
(57, 1, 'RFGR', ' Refrigerator  ', 'GENERAL SUPER', 'JM 01', '2010', '', 'AJ1TRFGR', '', '1', '2022-07-18 09:39:32', '2020-10-12 07:52:53'),
(58, 1, 'RFGR', ' Refrigerator  ', 'SANYO', 'JM 01', '2013', '', '10701260', '', '1', '2022-07-18 09:39:32', '2020-10-12 07:58:15'),
(59, 1, 'HTR', 'Heater', 'GEEPAS', 'JM 01', '2018', '', '5B15050629', '', '1', '2022-07-18 09:39:32', '2020-10-12 08:08:17'),
(60, 1, 'TSTR', 'Toaster', 'TEFAL', 'JM 01', '2015', '', 'AJ1TSTR', '', '1', '2022-07-18 09:39:32', '2020-10-12 08:10:14'),
(61, 1, 'GRDR ', 'Grinder Machine', 'Bazzera', 'JM 01', '2010', '', 'MD001021418', '', '1', '2022-07-18 09:39:32', '2020-10-14 08:53:26'),
(62, 77, 'EM2H001', 'ESPRESSO MACHINE 2 Hole', 'Bazzera', 'JM 02', '2000', 'Srvce.done 2020', '26112', '', '1', '2022-07-18 09:39:32', '2020-10-14 09:07:14'),
(63, 77, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'JM 02', '2003', 'Transfered from UM3 to JM2 16-5-19(Repaired Cost sr 2296)', '291004', '', '1', '2022-07-18 09:39:32', '2020-10-14 09:08:37'),
(64, 77, 'ORG', 'Orange Machine', 'CITROCASA', 'JM 02', '2012', '', '100570', '', '1', '2022-07-18 09:39:32', '2020-10-14 09:09:31'),
(65, 77, 'DRFGR', ' Display Refrigerator 142*80*142', 'Hisense', 'JM 02', '2012', '', 'BC100BHE52', '', '1', '2022-07-18 09:39:32', '2020-10-14 09:15:51'),
(66, 77, 'HTR', 'Heater', 'Unknown', 'JM 02', '2019', '', 'K1S91F9E', '', '1', '2022-07-18 09:39:32', '2020-10-14 09:17:13'),
(67, 77, 'OVNS', 'Oven Small ', 'Penasonic', 'JM 02', '2019', '', '1007688', '', '1', '2022-07-18 09:39:32', '2020-10-14 09:19:53'),
(68, 77, 'FLC', 'Fly Catcher', 'ICCUME', 'JM 02', '2019', '', '10328', '', '1', '2022-07-18 09:39:32', '2020-10-14 09:21:16'),
(69, 77, 'WMAPE', ' Water Motor,Auto Pump ESP.', 'SAMNAN', 'JM 02', '2019', '', 'AJ2WMAPE', '', '1', '2022-07-18 09:39:32', '2020-10-14 09:24:20'),
(70, 77, 'WMAPW', ' Water Motor,Auto Pump WTR.', 'SAMNAN', 'JM 02', '2019', '', 'AJ2WMAPW', '', '1', '2022-07-18 09:39:32', '2020-10-14 09:26:31'),
(71, 77, 'SU.AC', 'Air Condition ', 'Unknown', 'JM 02', '2019', '', 'AJ2SU.AC', '', '1', '2022-07-18 09:39:32', '2020-10-14 09:31:20'),
(73, 79, 'EM2H001', 'ESPRESSO MACHINE 2 Hole', 'Bazzera', 'MM 04', '1-9-2020', '', 'AM009935819', '', '1', '2022-07-18 09:39:32', '2020-10-19 06:40:24'),
(74, 79, 'SM3H004', 'SLUSH MACHINE 3 Hls', 'Ugolini', 'MM 04', '1-9-2020', '', '2.001E+09', '', '1', '2022-07-18 09:39:32', '2020-10-19 09:03:29'),
(75, 79, 'GRDR', 'Grinder Machine', 'Unknown', 'MM 04', '2008', '', 'BB090ATOR05', '', '1', '2022-07-18 09:39:32', '2020-10-19 09:05:06'),
(76, 4, 'SM3H004', 'SLUSH MACHINE 3 Hls', 'Ugolini', 'JM 08', '2006', 'Transfered from UM1 to JM8 16-6-19 (repaired cost sr 4141)', '219601', '', '1', '2022-07-18 09:39:32', '2020-10-19 09:15:04'),
(77, 6, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Unknown', '0', '2006', '', '445609', '', '1', '2022-07-20 04:29:43', '2020-10-19 09:16:18'),
(78, 7, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'JM 12', '2006', 'Transfered from UBT2 to JM12 10-6-19(Repaired Cost sr 1885)', '398702', '', '1', '2022-07-18 09:39:32', '2020-10-19 09:17:22'),
(79, 9, 'SM3H004', 'SLUSH MACHINE 3 Hls', 'Ugolini', 'JM 15', '2009', '', '447878', '', '1', '2022-07-18 09:39:32', '2020-10-19 09:18:39'),
(80, 10, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'JM 16', '2002', '', '441929', '', '1', '2022-07-18 09:39:32', '2020-10-19 09:20:40'),
(81, 10, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'JM 16', '2002', '', '153499', '', '1', '2022-07-18 09:39:32', '2020-10-19 09:22:00'),
(82, 11, 'SM3H004', 'SLUSH MACHINE 3 Hls', 'Ugolini', 'JM 17', '2005', '', '350699', '', '1', '2022-07-18 09:39:32', '2020-10-19 09:22:42'),
(83, 12, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'JM 18', '2002', '', '323288', '', '1', '2022-07-18 09:39:32', '2020-10-19 09:23:24'),
(84, 12, 'SM3H004', 'SLUSH MACHINE 3 Hls', 'Ugolini', 'JM 18', '2004', '', '377662', '', '1', '2022-07-18 09:39:32', '2020-10-19 09:23:58'),
(85, 14, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'JM 20', '2003', '', '123620', '', '1', '2022-07-18 09:39:32', '2020-10-19 09:32:07'),
(86, 61, 'SM3H004', 'SLUSH MACHINE 3 Hls', 'Ugolini', 'JM 21', '2008', '', '523625', '', '1', '2022-07-18 09:39:32', '2020-10-19 09:32:53'),
(87, 15, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'JM 22', '2002', '', '176634', '', '1', '2022-07-18 09:39:32', '2020-10-19 09:34:07'),
(88, 15, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'JM 22', '2002', '', '175533', '', '1', '2022-07-18 09:39:32', '2020-10-19 09:34:44'),
(89, 16, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'JM 23', '2003', '', '201754', '', '1', '2022-07-18 09:39:32', '2020-10-19 09:44:02'),
(90, 17, 'SM3H004', 'SLUSH MACHINE 3 Hls', 'Ugolini', 'JM 24', '2005', '', '219614', '', '1', '2022-07-18 09:39:32', '2020-10-19 09:44:36'),
(91, 18, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'JM 26', '2010', '', '470214', '', '1', '2022-07-18 09:39:32', '2020-10-19 09:45:29'),
(92, 19, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'JM 28', '2001', '', 'FAEM.MD2.SM.01', '', '1', '2022-07-18 09:39:32', '2020-10-19 09:46:21'),
(93, 20, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'JM 29', '2005', 'Repaired date 22-11-18 &  Cost sr 860', '445580', '', '1', '2022-07-18 09:39:32', '2020-10-19 09:47:32'),
(94, 22, 'SM3H004', 'SLUSH MACHINE 3 Hls', 'Ugolini', 'JM 32', '2010', '', '176847', '', '1', '2022-07-18 09:39:32', '2020-10-19 09:48:24'),
(95, 23, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'JM 33', '2005', '', '398703', '', '1', '2022-07-18 09:39:32', '2020-10-19 09:49:01'),
(96, 24, 'SM3H004', 'SLUSH MACHINE 3 Hls', 'Ugolini', 'JM 34', '2009', '', '470314', '', '1', '2022-07-18 09:39:32', '2020-10-19 09:49:43'),
(97, 0, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'Store Coffee', '2002', '', '465988', '', '1', '2022-07-18 09:39:32', '2020-10-19 09:50:27'),
(98, 0, 'SM3H004', 'SLUSH MACHINE 3 Hls', 'Ugolini', 'Store Coffee', '2004', '', '1219617', '', '1', '2022-07-18 09:39:32', '2020-10-19 09:51:06'),
(99, 26, 'SM3H004', 'SLUSH MACHINE 3 Hls', 'Ugolini', 'JM 39', '2010', '', '447867', '', '1', '2022-07-18 09:39:32', '2020-10-19 09:51:57'),
(100, 2, 'SM3H004', 'SLUSH MACHINE 3 Hls', 'Ugolini', 'JM 03', '2003', '', '240102', '', '1', '2022-07-18 09:39:32', '2020-10-27 05:16:45'),
(101, 7, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'JM 12', '2006', '', '445641', '', '1', '2022-07-18 09:39:32', '2020-10-27 05:27:11'),
(102, 20, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'JM 29', '2005', '', '176771', '', '1', '2022-07-18 09:39:32', '2020-10-27 05:45:10'),
(103, 27, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'MM 01', '2006', '', '155392', '', '1', '2022-07-18 09:39:32', '2020-10-27 06:06:15'),
(104, 27, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'MM 01', '2006', '', '166490', '', '1', '2022-07-18 09:39:32', '2020-10-27 06:07:04'),
(105, 28, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'MM 02', '2003', '', '155314', '', '1', '2022-07-18 09:39:32', '2020-10-27 06:08:15'),
(106, 28, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'MM 02', '2003', '', '412826', '', '1', '2022-07-18 09:39:32', '2020-10-27 06:09:07'),
(107, 29, 'SM3H004', 'SLUSH MACHINE 3 Hls', 'Ugolini', 'MM 03', '2003', '', '146561', '', '1', '2022-07-18 09:39:32', '2020-10-27 06:10:02'),
(108, 30, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'MM 05', '2007', '', '214168', '', '1', '2022-07-18 09:39:32', '2020-10-27 06:12:50'),
(109, 30, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'MM 05', '2007', '', '412833', '', '1', '2022-07-26 04:33:20', '2020-10-27 06:13:40'),
(110, 31, 'SM3H004', 'SLUSH MACHINE 3 Hls', 'Ugolini', 'YM 01', '2004', '', '465925', '', '1', '2022-07-18 09:39:32', '2020-10-27 06:38:38'),
(111, 32, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'YM 02', '2004', '', '127112', '', '1', '2022-07-18 09:39:32', '2020-10-27 06:39:56'),
(112, 32, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'YM 02', '2006', '', '454659', '', '1', '2022-07-18 09:39:32', '2020-10-27 06:40:39'),
(113, 0, 'SM3H004', 'SLUSH MACHINE 3 Hls', 'Ugolini', 'Store Coffee', '2006', '', '447877', '', '1', '2022-07-18 09:39:32', '2020-10-27 06:41:29'),
(114, 0, 'SM3H004', 'SLUSH MACHINE 3 Hls', 'Ugolini', 'Store Coffee', '2002', 'out of order from UM2', '211682', '', '1', '2022-07-18 09:39:32', '2020-10-27 06:47:57'),
(115, 0, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'Store Coffee', '2008', 'UM2 out of order', '454656', '', '1', '2022-07-18 09:39:32', '2020-10-27 06:49:05'),
(116, 36, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'JMUM 02', '2004', 'from Um3 ', '412889', '', '1', '2022-07-18 09:39:32', '2020-10-27 06:51:07'),
(117, 39, 'SM3H004', 'SLUSH MACHINE 3 Hls', 'Ugolini', 'JMUM 05', '2004', '', '410486', '', '1', '2022-07-18 09:39:32', '2020-10-27 07:15:50'),
(118, 35, 'SM3H004', 'SLUSH MACHINE 3 Hls', 'Ugolini', 'JMUM 01', '2004', '', '410502', '', '1', '2022-07-18 09:39:32', '2020-10-27 08:20:04'),
(119, 37, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'JMUM 03', '2007', '', '454658', '', '1', '2022-07-18 09:39:32', '2020-10-27 08:23:53'),
(120, 38, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'JMUM 04', '2001', '', '83728', '', '1', '2022-07-18 09:39:32', '2020-10-27 08:59:33'),
(121, 38, 'SM3H004', 'SLUSH MACHINE 3 Hls', 'Ugolini', 'JMUM 04', '2006', '', '447876', '', '1', '2022-07-18 09:39:32', '2020-11-01 07:02:30'),
(122, 0, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'Store Coffee', '2003', 'OUT OF ORDER', '398775', '', '1', '2022-07-18 09:39:32', '2020-11-03 05:31:11'),
(123, 0, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'Store Coffee', '2002', 'OUT OF ORDER', '082686', '', '1', '2022-07-18 09:39:32', '2020-11-03 05:32:01'),
(124, 0, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'Store Coffee', '2004', 'OUT OF ORDER', '214179', '', '1', '2022-07-18 09:39:32', '2020-11-03 05:32:44'),
(125, 0, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'Store Coffee', '2003', 'OUT OF ORDER', '287783', '', '1', '2022-07-18 09:39:32', '2020-11-03 05:35:02'),
(126, 0, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'Store Coffee', '2001', 'OUT OF ORDE FROM JM15', 'JM.15003SL2', '', '1', '2022-07-18 09:39:32', '2020-11-03 05:36:35'),
(127, 0, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'Store Coffee', '2003', 'OUT OF ORDER', '155367', '', '1', '2022-07-18 09:39:32', '2020-11-03 05:39:29'),
(128, 0, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'Store Coffee', '2004', 'OUT OF ORDER', '172063', '', '1', '2022-07-18 09:39:32', '2020-11-03 05:40:17'),
(129, 0, 'SM2H003', 'SLUSH MACHINE 2 Hls', 'Ugolini', 'Store Coffee', '2005', 'OUT OF ORDER', '194183', '', '1', '2022-07-18 09:39:32', '2020-11-03 05:41:10'),
(130, 2, 'ORG', 'Orange Machine', 'CITROCASA', 'JM 03', '2010', '', '100.476', '', '1', '2022-07-18 09:39:32', '2020-11-03 08:37:03'),
(131, 2, 'ICM', 'Ice Cub Machine ', 'MIGEL', 'JM 03', '2010', '', '1528882', '', '1', '2022-07-18 09:39:32', '2020-11-03 08:39:01'),
(132, 2, 'ICMWP', 'Ice Machine Water Pump', 'FLOJET', 'JM 03', '2010', '', 'J3ICMWP', '', '1', '2022-07-18 09:39:32', '2020-11-03 08:42:09'),
(133, 2, 'RFGR', ' Refrigerator  ', 'SAMSUNG', 'JM 03', '2012', '', '06FW43CF300451R', '', '1', '2022-07-18 09:39:32', '2020-11-03 08:50:54'),
(134, 2, 'RFGR', ' Refrigerator  ', 'SAMSUNG', 'JM 03', '2005', '', '40944DAC102387H', '', '1', '2022-07-18 09:39:32', '2020-11-03 08:53:29'),
(135, 2, 'HTR', 'Heater', 'GEEPAS', 'JM 03', '2014', '', 'J3HTR', '', '1', '2022-07-18 09:39:32', '2020-11-03 08:54:42'),
(136, 2, 'OVNS', 'Oven Small ', 'Penasonic', 'JM 03', '2009', '', 'J3OVNS', '', '1', '2022-07-18 09:39:32', '2020-11-03 08:56:01'),
(137, 17, 'ICM', 'Ice Cub Machine ', 'MIGEL', 'JM 24', '2012', '', 'N1528877', '', '1', '2022-07-18 09:39:32', '2020-11-18 09:29:47'),
(138, 0, 'FZR', 'FREEZER', 'Kelvinator', 'Store Coffee', '2012', '', '101362898', '', '1', '2022-07-18 09:39:32', '2020-11-22 09:52:25'),
(139, 27, 'GRDR', 'Grinder Machine', 'Bazzera', 'MM 01', '2002', '', 'MC0034108', '', '1', '2022-07-18 09:39:32', '2020-11-24 05:34:19'),
(140, 28, 'OVNB', 'OVEN BIG', 'EKA', 'MM 02', '2012', '', '415000864', '', '1', '2022-07-18 09:39:32', '2020-11-24 05:38:03'),
(141, 28, 'ORG', 'Orange Machine', 'FRUCOSOL', 'MM 02', '2013', '', 'F0012595', '', '1', '2022-07-18 09:39:32', '2020-11-24 05:40:44'),
(142, 29, 'GRDR', 'Grinder Machine', 'Bazzera', 'MM 03', '2009', '', 'MC001138609', '', '1', '2022-07-18 09:39:32', '2020-11-24 05:49:15'),
(143, 79, 'OVNB', 'OVEN BIG', 'EKA', 'MM 04', '2012', '', '2.115E+09', '', '1', '2022-07-18 09:39:32', '2020-11-24 05:53:57'),
(144, 79, 'ORG', 'Orange Machine', 'CITROCASA', 'MM 04', '2012', '', '100705', '', '1', '2022-07-18 09:39:32', '2020-11-24 05:55:32'),
(145, 30, 'OVNB', 'OVEN BIG', 'EKA', 'MM 05', '2012', '', '1.814E+09', '', '1', '2022-07-18 09:39:32', '2020-11-24 05:56:29'),
(146, 30, 'ORG', 'Orange Machine', 'CITROCASA', 'MM 05', '2012', '', '100583', '', '1', '2022-07-18 09:39:32', '2020-11-24 05:57:16'),
(147, 30, 'GRDR', 'Grinder Machine', 'Bazzera', 'MM 05', '2008', '', 'MC00113508', '', '1', '2022-07-18 09:39:32', '2020-11-24 05:58:09'),
(155, 28, '', 'Attendance Machine', 'ZKTeco', '', '', 'administrator is 6539306', '6350150200224', 'Model: U160-C', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(156, 28, '', 'Internet Modem', 'STC', '', '', 'admin is hain6539306', 'F5S7S19409001517', 'SIM S/N: 831045541472', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(157, 28, '', 'POS', 'Carisma', '', '', '', '0', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(158, 28, '', 'Mouse', 'Genius', '', '', '', 'X8A94692805711', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(159, 28, '', 'Keyboard', 'Genius', '', '', '', 'XP1811308520', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(160, 28, '', 'Camera Receiver', 'Hikvision', '', '', 'admin is admin54321', 'C08656332', 'Model: DS-7604NI-K1/4', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(161, 28, '', 'Internet Switch', 'TP Link', '', '', '', '218857008706', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(162, 28, '', 'Printer', 'Epson', '', '', '', 'MUMF037597', 'Model: T20', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(163, 28, '', 'Hard Drive', 'Western Digital', '', '', '', 'WD543PF50', 'WDBKXH5000ABK', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(164, 13, '', 'Internet Switch', 'TP Link', '', '', '', '2195682030158', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(165, 13, '', 'POS Adopter', 'POSIFLEX', '', '', '', '1106345270', 'Model: GM601-240250', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(166, 13, '', 'Printer Adopter', 'POSIFLEX', '', '', '', 'DPE0960A1914', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(167, 13, '', 'Attendance Machine', 'ZKTeco', '', '', 'administrator is 6539306', '350141300074', 'Model: U160-C', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(168, 13, '', 'Internet Modem', 'STC', '', '', 'admin is hain6539306', 'F5S7S19409001547', 'SIM S/N: 831044432018', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(169, 13, '', 'Printer', 'Brich', '', '', '', '11310242KI', 'Model: BP-003', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(170, 13, '', 'POS', 'POSIFLEX', '', '', '', 'XT321571109003E', 'Model: XT-3215', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(171, 9, '', 'Printer', 'Epson', '', '', '', 'TC8F014537', 'Model: T20II', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(172, 9, '', 'POS Adopter', 'POSIFLEX', '', '', '', 'DPD016080097', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(173, 9, '', 'Internet Modem', 'STC', '', '', 'admin is hain6539306', 'F5S7S19409001544', 'SIM S/N: 831045630787', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(174, 9, '', 'Attendance Machine', 'ZKTeco', '', '', 'administrator is 6539306', '350141600086', 'Model: U160-C', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(175, 9, '', 'Camera Receiver', 'Hikvision', '', '', 'admin is asd12345', 'E88634515', 'Model: DS-7604NI-K1/4', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(176, 9, '', 'POS', 'Carisma', '', '', '', '0', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(177, 27, '', 'POS', 'POSIFLEX', '', '', '', 'AL74350007140171', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(178, 27, '', 'POS Adopter', 'POSIFLEX', '', '', '', 'DVCD1423009659', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(179, 27, '', 'Mouse', 'Lenovo', '', '', '', 'LZ212HD0NA7', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(180, 27, '', 'Internet Switch', 'TP Link', '', '', '', '2195682029519', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(181, 27, '', 'Printer Adopter', 'Brich', '', '', '', '1106253082', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(182, 27, '', 'Internet Modem', 'STC', '', '', 'admin is hain6539306', 'F5S7S19409001520', 'SIM S/N: 831045553610', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(183, 27, '', 'Hard Drive', 'Western Digital', '', '', '', 'EX81A53V8982', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(184, 27, '', 'Printer', 'Brich', '', '', '', '11310303KI', 'Model: BP-003', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(185, 27, '', 'Attendance Machine', 'ZKTeco', '', '', 'administrator is 6539306', '350141300133', 'Model: U160-C', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(186, 4, '', 'POS', 'POSIFLEX', '', '', '', 'DVCD1341020965', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(187, 4, '', 'POS Adopter', 'POSIFLEX', '', '', '', 'DVCD1341020965', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(188, 4, '', 'Printer', 'Brich', '', '', '', '11310361KI', 'Model: BP-003', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(189, 4, '', 'Attendance Machine', 'ZKTeco', '', '', 'administrator is 6539306', '350141600038', 'Model: U160-C', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(190, 4, '', 'Internet Modem', 'STC', '', '', 'admin is hain6539306', 'F5S7S19409001473', 'SIM S/N: 831042978507', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(191, 4, '', 'Camera Receiver', 'Hikvision', '', '', 'admin is asd12345', 'F25522907', 'Model: DS-7604NI-K1/4', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(192, 84, '', 'Attendance Machine', 'ZKTeco', '', '', '', 'BYAD42808503', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(193, 84, '', 'Camera RCV Adopter', 'Hikvision', '', '', '', 'JKQHCA176005A2', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(194, 84, '', 'Camera Receiver', 'Hikvision', '', '', 'admin is hik@12345', 'E92686074', 'Model: DS-7604NI-K1/4', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(195, 84, '', 'Internet Switch', 'TP Link', '', '', '', '2195682029087', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(196, 84, '', 'Internet Modem', 'STC', '', '', 'admin is hain6539306', 'F5S7S19409001471', 'SIM S/N: 831040671553', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(197, 84, '', 'Printer Adopter', 'Brich', '', '', '', '1206100708', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(198, 84, '', 'Attendance Machine', 'ZKTeco', '', '', 'administrator is 6539306', '350142400422', 'Model: U160-C', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(199, 84, '', 'Printer', 'Brich', '', '', '', '11310537KI', 'Model: BP-003', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(200, 84, '', 'Hard Drive', 'Western Digital', '', '', '', 'WX91AB3F6297', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(201, 84, '', 'POS', 'POSIFLEX', '', '', '', 'AL74350001140318', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(202, 84, '', 'POS Adopter', 'POSIFLEX', '', '', '', 'DVCD1341020527', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(203, 22, '', 'POS', 'POSIFLEX', '', '', '', '72351293300', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(204, 22, '', 'Printer', 'Brich', '', '', '', '11310441KI', 'Model: BP-003', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(205, 22, '', 'Internet Modem', 'STC', '', '', 'admin is hain6539306', 'F5S7S19409001541', 'SIM S/N: 831045719990', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(206, 22, '', 'Printer Adopter', 'Brich', '', '', '', '1106384506', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(207, 22, '', 'Hard Drive', 'Western Digital', '', '', '', 'WX41A23S5074', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(208, 4, '', 'Hard Drive', 'Western Digital', '', '', '', 'WX71EB3AKK22', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(209, 18, '', 'POS', 'POSIFLEX', '', '', '', 'XT3215711009003E', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(210, 18, '', 'Hard Drive', 'Western Digital', '', '', '', 'WX71EB3VL252', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(211, 18, '', 'Attendance Machine', 'ZKTeco', '', '', 'administrator is 6539306', '350142400414', 'Model: U160-C', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(212, 18, '', 'Printer', 'Brich', '', '', '', '11310302KI', 'Model: BP-003', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(213, 18, '', 'Internet Modem', 'STC', '', '', 'admin is hain6539306', 'F5S7S19409001472', 'SIM S/N: 831045473839', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(214, 18, '', 'Printer Adopter', 'Epson', '', '', '', 'DPF1360B0324', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(215, 18, '', 'POS Adopter', 'POSIFLEX', '', '', '', 'DPD0160B0897', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(216, 18, '', 'Internet Switch', 'TP Link', '', '', '', '2195682029161', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(217, 4, '', 'Internet Switch', 'TP Link', '', '', '', '2195682029056', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(218, 9, '', 'Internet Switch', 'TP Link', '', '', '', '2195682029044', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(219, 9, '', 'Hard Drive', 'Western Digital', '', '', '', 'WXC1A5351728', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(220, 35, '', 'POS', 'POSIFLEX', '', '', '', 'AL74350007140164', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(221, 35, '', 'Internet Switch', 'TP Link', '', '', '', '2194001008544', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(222, 35, '', 'Attendance Machine', 'ZKTeco', '', '', '', '6350150200258', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(223, 35, '', 'Internet Modem', 'STC', '', '', 'admin is hain6539306', 'F5S7S19409001519', 'SIM S/N: 831043167157', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(224, 35, '', 'Printer', 'Brich', '', '', '', '12220747LW', 'Model: BP-003', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(225, 35, '', 'POS Adopter', 'POSIFLEX', '', '', '', 'DVCD1423009668', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(226, 35, '', 'Hard Drive', 'Western Digital', '', '', '', 'WX11E23RF744', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(227, 2, '', 'POS', 'Carisma', '', '', '', '0', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(228, 2, '', 'Attendance Machine', 'ZKTeco', '', '', 'administrator is 6539306', '350142400413', 'Model: U160-C', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(229, 2, '', 'Printer', 'Brich', '', '', '', '11310562KI', 'Model: BP-003', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(230, 2, '', 'POS Adopter', 'Carisma', '', '', '', '231211001C3', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(231, 2, '', 'Internet Modem', 'STC', '', '', 'admin is hain6539306', 'F5S7S194009001513', 'SIM S/N: 831041021091', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(232, 2, '', 'Internet Switch', 'TP Link', '', '', '', '2195682030150', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(233, 2, '', 'Hard Drive', 'Western Digital', '', '', '', 'WXC1A5355308', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(234, 6, '', 'POS', 'FEC', '', '', '', '0', 'Model: AL-7435', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(235, 6, '', 'Printer', 'Brich', '', '', '', '11310564KI', 'Model: BP-003', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(236, 6, '', 'Internet Modem', 'STC', '', '', 'admin is hain6539306', 'F5S7S19409001589', 'SIM S/N: 831044915689', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(237, 6, '', 'Internet Switch', 'TP Link', '', '', '', '13CC3900631', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(238, 6, '', 'Hard Drive', 'Western Digital', '', '', '', 'WX21ECLY541', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(239, 6, '', 'Printer Adopter', 'Epson', '', '', '', 'CYYZJ18', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(240, 6, '', 'POS Adopter', 'POSIFLEX', '', '', '', 'DVCD1341020964', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(241, 6, '', 'Attendance Machine', 'ZKTeco', '', '', 'administrator is 6539306', '350141600069', 'Model: U160-C', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(242, 83, '', 'POS', 'Carisma', '', '', '', 'IT7000111', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(243, 83, '', 'POS Adopter', 'Carisma', '', '', '', '231211001C3', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(244, 83, '', 'Printer Adopter', 'POSIFLEX', '', '', '', 'DPE2660B0557', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(245, 83, '', 'Internet Switch', 'TP Link', '', '', '', '2195682029078', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(246, 83, '', 'Attendance Machine', 'ZKTeco', '', '', 'administrator is 6539306', '350141600084', 'Model: U160-C', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(247, 83, '', 'Internet Modem', 'STC', '', '', 'admin is hain6539306', 'F5S7S19409001465', 'SIM S/N: 831044688043', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(248, 83, '', 'Printer', 'POSIFLEX', '', '', '', 'PPEA1642', 'Model: PP-8800U-B', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(249, 83, '', 'Hard Drive', 'Western Digital', '', '', '', 'WXD1A43K4896', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(250, 83, '', 'POS', 'Carisma', '', '', '', '7011085143', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(251, 83, '', 'Camera RCV Adopter', 'Hikvision', '', '', 'admin is Admin54321', 'D24664079', 'Model: DS-7604NI-K1/4', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(252, 32, '', 'POS Adopter', 'FEC', '', '', '', 'DVCD1325012522', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(253, 32, '', 'POS', 'FEC', '', '', '', 'DVCD1325012522', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(254, 32, '', 'Printer', 'POSIFLEX', '', '', '', 'PPG1E711', 'Model: PP-8800U-B', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(255, 32, '', 'Printer Adopter', 'POSIFLEX', '', '', '', 'DPF4460C0486', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(256, 32, '', 'Internet Modem', 'STC', '', '', 'admin is hain6539306', 'F5S7S19409001655', 'SIM S/N: 831047485951', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(257, 32, '', 'Attendance Machine', 'ZKTeco', '', '', 'administrator is 6539306', '350141300112', 'Model: U160-C', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(258, 32, '', 'Internet Switch', 'TP Link', '', '', '', '2195682029088', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(259, 32, '', 'Camera Receiver', 'Hikvision', '', '', 'admin is hik12345', 'D25447368', 'Model: DS-7604NI-K1/4', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(260, 32, '', 'Camera RCV Adopter', 'Hikvision', '', '', '', 'MSPLBJE0736701', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(261, 14, '', 'Internet Switch', 'TP Link', '', '', '', '2188456003276', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(262, 14, '', 'Printer Adopter', 'Epson', '', '', '', 'CYYZJ18664I', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(263, 14, '', 'Internet Modem', 'STC', '', '', 'admin is hain6539306', 'F5S7S19409001548', 'SIM S/N: 831045654277', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(264, 14, '', 'POS Adopter', 'POSIFLEX', '', '', '', 'DPE0760A3487', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(265, 14, '', 'Attendance Machine', 'ZKTeco', '', '', 'administrator is 6539306', '350141300087', 'Model: U160-C', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(266, 14, '', 'Printer', 'POSIFLEX', '', '', '', 'PPD33855', 'Model: PP-8800S-B', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(267, 14, '', 'Hard Drive', 'Western Digital', '', '', '', 'WXB1A63F6202', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(268, 14, '', 'POS', 'POSIFLEX', '', '', '', 'XT301571100900', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(269, 14, '', 'Camera Receiver', 'Hikvision', '', '', 'admin is hik12345', 'C71831720', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(270, 25, '', 'Internet Switch', 'TP Link', '', '', '', '2195682029157', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(271, 25, '', 'POS Adopter', 'Carisma', '', '', '', '231211001C3', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(272, 25, '', 'Internet Modem', 'STC', '', '', 'admin is hain6539306', 'F5S7S19409001652', 'SIM S/N: 831045443334', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(273, 25, '', 'Printer', 'Brich', '', '', '', '12320228LW', 'Model: BP-003', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(274, 25, '', 'Attendance Machine', 'ZKTeco', '', '', 'administrator is 6539306', '350142400418', 'Model: U160-C', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(275, 25, '', 'POS', 'Carisma', '', '', '', '0', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(276, 33, '', 'POS', 'POSIFLEX', '', '', '', 'KS72351DM3300', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(277, 33, '', 'Internet Switch', 'TP Link', '', '', '', '2195682029121', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(278, 33, '', 'Printer', 'POSIFLEX', '', '', '', 'PPG1E710', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(279, 33, '', 'Printer Adopter', 'POSIFLEX', '', '', '', 'DPF4460C0498', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(280, 33, '', 'POS Adopter', 'POSIFLEX', '', '', '', 'DPF2860F2240', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(281, 33, '', 'Attendance Machine', 'ZKTeco', '', '', 'administrator is 6539306', '350141600027', 'Model: U160-C', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(282, 33, '', 'Internet Modem', 'STC', '', '', 'admin is hain6539306', 'F5S7S19409001645', 'SIM S/N: 831045035985', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(283, 33, '', 'Camera Receiver', 'Hikvision', '', '', 'admin is hik12345', 'D25448658', 'Model: DS-7604NI-K1/4', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(284, 33, '', 'Camera RCV Adopter', 'Hikvision', '', '', '', 'MSPLBJE0968801', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(285, 11, '', 'POS', 'Carisma', '', '', '', '0', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(286, 11, '', 'Printer', 'Brich', '', '', '', '11310216KI', 'Model: BP-003', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(287, 11, '', 'POS Adopter', 'Carisma', '', '', '', '4711173871118', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(288, 11, '', 'Printer Adopter', 'Brich', '', '', '', '1208003254', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(289, 11, '', 'Attendance Machine', 'ZKTeco', '', '', 'administrator is 6539306', '350141600109', 'Model: U160-C', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(290, 11, '', 'Internet Switch', 'TP Link', '', '', '', '2195682029106', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(291, 11, '', 'Camera Receiver', 'Hikvision', '', '', 'admin is asd12345', 'E07066484', 'Model: DS-7604NI-K1/4', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(292, 11, '', 'Internet Modem', 'STC', '', '', 'admin is hain6539306', 'F5S7S19409001546', 'SIM S/N: 831045281666', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(293, 20, '', 'POS', 'Carisma', '', '', '', '7012107022', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(294, 20, '', 'Printer', 'POSIFLEX', '', '', '', 'PPE72608', 'Model: PP-8800U-B', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(295, 20, '', 'Internet Switch', 'TP Link', '', '', '', '2195682029197', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(296, 20, '', 'Attendance Machine', 'ZKTeco', '', '', 'administrator is 6539306', '350141600077', 'Model: U160-C', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(297, 24, '', 'Internet Switch', 'TP Link', '', '', '', '2149087008734', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(298, 24, '', 'POS', 'POSIFLEX', '', '', '', 'XT3215711009003E', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(299, 24, '', 'POS Adopter', 'POSIFLEX', '', '', '', 'DPE0760A2082', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(300, 24, '', 'Printer', 'Brich', '', '', '', '11310591KI', 'Model: BP-003', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(301, 24, '', 'Printer Adopter', 'Brich', '', '', '', '689238', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(302, 24, '', 'Attendance Machine', 'ZKTeco', '', '', 'administrator is 6539306', '350142400448', 'Model: U160-C', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(303, 24, '', 'Camera Receiver', 'Hikvision', '', '', 'admin is Admin54321', 'E07067102', 'Model: DS-7604NI-K1/4', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(304, 24, '', 'Camera RCV Adopter', 'Hikvision', '', '', '', 'MSPLBJL2064401', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(305, 1, '', 'POS Adopter', 'POSIFLEX', '', '', '', 'DFP1180G1363', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(306, 1, '', 'Printer Adopter', 'POSIFLEX', '', '', '', 'DFP1760B0336', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(307, 1, '', 'Internet Modem', 'STC', '', '', 'admin is hain6539306', 'F5S7S19409001650', 'SIM S/N: 831040393224', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(308, 1, '', 'Camera RCV Adopter', 'Hikvision', '', '', '', 'MSPLBJG1491801', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(309, 1, '', 'Camera Receiver', 'Hikvision', '', '', 'admin is admin123456', 'D48849818', 'Model: DS-7604NI-K1/4', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(310, 1, '', 'Internet Switch', 'TP Link', '', '', '', '13CC3900607', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(311, 1, '', 'Attendance Machine', 'ZKTeco', '', '', 'administrator is 6539306', '6351153500048', 'Model: U560-C', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(312, 1, '', 'POS', 'POSIFLEX', '', '', '', 'XT3215711009003E', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(313, 1, '', 'Printer', 'POSIFLEX', '', '', '', 'PPF60978', 'Model: PP-8800U-B', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(314, 15, '', 'Printer', 'Brich', '', '', '', '122220598LW', 'Model: BP-003', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(315, 15, '', 'POS', 'Carisma', '', '', '', '0', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(316, 15, '', 'Attendance Machine', 'ZKTeco', '', '', 'administrator is 6539306', '350142400447', 'Model: U160-C', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(317, 15, '', 'Printer Adopter', 'POSIFLEX', '', '', '', '1208003', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(318, 15, '', 'Internet Switch', 'TP Link', '', '', '', '2195682029031', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(319, 15, '', 'POS Adopter', 'Carisma', '', '', '', '4711173871118', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(320, 31, '', 'POS', 'FEC', '', '', '', 'DVCD1341020735', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(321, 31, '', 'POS Adopter', 'FEC', '', '', '', 'DVCD1341020735', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(322, 31, '', 'Printer', 'POSIFLEX', '', '', '', 'PD33871', 'Model: PP-8800S-B', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(323, 23, '', 'Attendance Machine', 'ZKTeco', '', '', 'administrator is 6539306', '350141600051', 'Model: U160-C', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(324, 23, '', 'Printer Adopter', 'Brich', '', '', '', 'E321192518', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(325, 23, '', 'Internet Modem', 'STC', '', '', 'admin is hain6539306', 'F5S7S19409001542', 'SIM S/N: 831045296371', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(326, 23, '', 'Internet Switch', 'TP Link', '', '', '', '2195682030038', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(327, 23, '', 'POS', 'Carisma', '', '', '', '0', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(328, 23, '', 'Printer', 'Brich', '', '', '', '11310541KI', 'Model: BP-003', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(329, 23, '', 'POS Adopter', 'Carisma', '', '', '', '231211661C3', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(330, 31, '', 'Printer Adopter', 'POSIFLEX', '', '', '', 'DPD0160B0928', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(331, 31, '', 'Internet Modem', 'STC', '', '', 'admin is hain6539306', 'F5S7S19409001654', 'SIM S/N: 831045453268', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(332, 31, '', 'Attendance Machine', 'ZKTeco', '', '', 'administrator is 6539306', '350141300137', 'Model: U160-C', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(333, 31, '', 'Internet Switch', 'TP Link', '', '', '', '2195682029021', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(334, 31, '', 'Camera Receiver', 'Hikvision', '', '', 'admin is hik12345', 'D15144856', 'Model: DS-7604NI-K1/4', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(335, 20, '', 'Internet Modem', 'STC', '', '', 'admin is hain6539306', 'F5S7S19409001540', 'SIM S/N: 831044978174', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(336, 20, '', 'Printer Adopter', 'Brich', '', '', '', '0', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(337, 15, '', 'Internet Modem', 'STC', '', '', 'admin is hain6539306', 'F5S7S19409001514', 'SIM S/N: 831047494573', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(338, 29, '', 'Camera Receiver', 'Hikvision', '', '', 'admin is Admin54321', 'D14306930', 'Model: DS-7604NI-K1/4', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(339, 29, '', 'Attendance Machine', 'ZKTeco', '', '', 'administrator is 6539306', '350141300055', 'Model: U160-C', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(340, 29, '', 'POS', 'Carisma', '', '', '', '0', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(341, 29, '', 'Printer', 'Brich', '', '', '', '11310350KI', 'Model: BP-003', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(342, 29, '', 'Internet Switch', 'TP Link', '', '', '', '2195682029035', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(343, 29, '', 'Internet Modem', 'STC', '', '', 'admin is hain6539306', 'F5S7S19409001521', 'SIM S/N: 831045627490', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(344, 30, '', 'Attendance Machine', 'ZKTeco', '', '', 'administrator is 6539306', '350141600034', 'Model: U160-C', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(345, 30, '', 'Internet Modem', 'STC', '', '', 'admin is hain6539306', 'F5S7S19409001516', 'SIM S/N: 831044804058', '1', '2022-07-26 04:34:24', '0000-00-00 00:00:00'),
(346, 30, '', 'Printer', 'Epson', '', '', '', 'TC8F013557', 'Model: TM-T20II', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(347, 30, '', 'POS', 'POSIFLEX', '', '', '', 'AL7350001140321', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(348, 30, '', 'POS Adopter', 'POSIFLEX', '', '', '', 'DVCD1341020520', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(349, 30, '', 'Printer Adopter', 'Epson', '', '', '', '0625178', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(350, 30, '', 'Hard Drive', 'Western Digital', '', '', '', 'WX11E23AFF64', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(351, 79, '', 'Printer Adopter', 'Brich', '', '', '', '1106389180', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(352, 79, '', 'Camera Receiver', 'Hikvision', '', '', 'admin is admin54321', 'E20580468', 'Model: DS-7604NI-K1/4', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(353, 79, '', 'POS', 'Carisma', '', '', '', '0', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(354, 79, '', 'Printer', 'Brich', '', '', '', '11310277KI', 'Model: BP-003', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(355, 79, '', 'Hard Drive', 'Western Digital', '', '', '', 'WX11E23PA533', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(356, 79, '', 'Attendance Machine', 'ZKTeco', '', '', 'administrator is 6539306', '6351153500151', 'Model: U160-C', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(357, 79, '', 'Internet Switch', 'TP Link', '', '', '', '2195682029126', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(358, 61, '', 'Internet Switch', 'TP Link', '', '', '', '2188572008534', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(359, 61, '', 'Attendance Machine', 'ZKTeco', '', '', 'administrator is 6539306', '350141300078', 'Model: U160-C', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(360, 61, '', 'Internet Modem', 'STC', '', '', 'admin is hain6539306', 'F5S7S19409001537', 'SIM S/N: 831044589182', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(361, 61, '', 'Printer', 'Brich', '', '', '', '11310554KI', 'Model: BP-003', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(362, 61, '', 'POS', 'POSIFLEX', '', '', '', 'DVCD1341020740', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(363, 61, '', 'POS Adopter', 'POSIFLEX', '', '', '', 'DVCD1341020740', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(364, 61, '', 'Camera Receiver', 'Hikvision', '', '', 'admin is admin54321', 'C09371972', 'Model: DS-7604NI-K1/4', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(365, 12, '', 'Camera RCV Adopter', 'Hikvision', '', '', '', 'MSPLBJE0000401', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(366, 12, '', 'Camera Receiver', 'Hikvision', '', '', 'admin is Admin54321', 'D27302712', 'Model: DS-7604NI-K1/4', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00');
INSERT INTO `machines` (`id`, `location_id`, `m_id`, `name_mach`, `maker_name`, `location`, `made_year`, `remarks`, `serial`, `serial_2`, `status`, `updated_at`, `created_at`) VALUES
(367, 12, '', 'Internet Switch', 'TP Link', '', '', '', '2195682029217', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(368, 12, '', 'POS Adopter', 'POSIFLEX', '', '', '', 'DPF22600119', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(369, 12, '', 'Printer', 'Epson', '', '', '', 'TC8F013552', 'Model: TM-T20II', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(370, 12, '', 'Internet Modem', 'STC', '', '', 'admin is hain6539306', 'F5S7S19409001475', 'SIM S/N: 831043968733', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(371, 12, '', 'POS', 'POSIFLEX', '', '', '', 'KS72351DM3300', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(372, 12, '', 'Attendance Machine', 'ZKTeco', '', '', 'administrator is 6539306', '350141600073', 'Model: U160-C', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(373, 79, '', 'Internet Modem', 'STC', '', '', 'admin is hain6539306', 'F5S7S19409001468', 'SIM S/N: 831044727949', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(374, 77, '', 'Camera Receiver', 'Hikvision', '', '', 'admin is Admin54321', 'C31063307', 'Model: DS-7604NI-Q1/4P', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(375, 77, '', 'Internet Modem', 'STC', '', '', 'admin is hain6539306', 'F5S7S19409001466', 'SIM S/N: 831045532968', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(376, 77, '', 'Internet Switch', 'TP Link', '', '', '', '219568202927', 'Model: AL-7435', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(377, 77, '', 'Attendance Machine', 'ZKTeco', '', '', 'administrator is 6539306', '0350142400421', 'Model: U160-C', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(378, 77, '', 'Printer Adopter', 'POSIFLEX', '', '', '', 'DPE1660B1164', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(379, 77, '', 'POS Adopter', 'POSIFLEX', '', '', '', 'DVCD1425016254', '', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00'),
(380, 77, '', 'Printer', 'Brich', '', '', '', '12320298LW', 'Model: BP-003', '1', '2022-07-25 06:42:44', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `machine_inv`
--

CREATE TABLE `machine_inv` (
  `id` int(11) NOT NULL,
  `mid` varchar(255) NOT NULL,
  `inv_no` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `item` varchar(255) NOT NULL,
  `qty` varchar(255) NOT NULL,
  `price` varchar(255) NOT NULL,
  `date_reg` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

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

CREATE TABLE `machine_trans` (
  `id` int(11) NOT NULL,
  `m_id` varchar(255) NOT NULL,
  `mid` int(50) NOT NULL,
  `location` varchar(255) NOT NULL,
  `old_location` varchar(255) NOT NULL,
  `date_reg` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `machine_trans`
--

INSERT INTO `machine_trans` (`id`, `m_id`, `mid`, `location`, `old_location`, `date_reg`) VALUES
(1, 'SM3H004', 6, 'JM 19', 'JM 06', '2020-10-19T15:27:53+03:00'),
(2, 'EM2H001', 42, 'JM 04', 'JMUF 01', '2020-10-26T14:51:49+03:00'),
(3, 'SM3H004', 4, 'JM 38', 'JM 03', '2020-10-27T11:18:16+03:00'),
(4, 'SM3H004', 100, 'JM 03', 'JMUF 01', '2020-10-27T11:19:11+03:00'),
(5, 'SM2H003', 97, 'Store Coffee', 'JM 38', '2020-10-27T11:54:03+03:00'),
(6, 'SM3H004', 98, 'Coffee Factory', 'JM 38', '2020-10-27T11:54:49+03:00'),
(7, 'ICM', 137, 'JM 24', 'JM 10', '2020-11-18T15:31:04+03:00'),
(8, 'SM3H004', 113, 'Store Coffee', 'YM 03', '2020-11-19T10:32:35+03:00'),
(9, 'FZR', 138, 'Store Coffee', 'JMUM 01', '2020-11-22T15:54:22+03:00');

-- --------------------------------------------------------

--
-- Table structure for table `maint_type`
--

CREATE TABLE `maint_type` (
  `id` int(11) NOT NULL,
  `type` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `maint_type`
--

INSERT INTO `maint_type` (`id`, `type`, `created_at`) VALUES
(1, 'Oil', '2023-05-22 11:47:38'),
(2, 'Tire', '2023-05-22 11:47:41'),
(3, 'Spare-part', '2023-05-22 11:47:44'),
(9, 'Others', '2023-05-23 05:31:06');

-- --------------------------------------------------------

--
-- Table structure for table `menu_category`
--

CREATE TABLE `menu_category` (
  `id` int(11) NOT NULL,
  `cate_id` int(11) NOT NULL,
  `name_eng` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `name_ar` varchar(255) NOT NULL,
  `desc_eng` varchar(255) NOT NULL,
  `desc_ar` varchar(255) NOT NULL,
  `status` varchar(10) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `menu_category`
--

INSERT INTO `menu_category` (`id`, `cate_id`, `name_eng`, `name_ar`, `desc_eng`, `desc_ar`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Hot', 'الساخنة', 'Hot Beverages', 'المشروبات الساخنة', '1', '0000-00-00 00:00:00', '2023-09-27 11:29:07'),
(2, 1, 'Cold', 'الباردة', 'Cold Beverages', 'المشروبات الباردة', '1', '0000-00-00 00:00:00', '2023-09-27 11:29:09'),
(3, 1, 'Tea', 'شاي', 'Tea', 'شاي', '1', '0000-00-00 00:00:00', '2023-09-27 11:29:11'),
(4, 1, 'Boxes', 'علبة', 'Mochachino Products', 'منتجات موكاتشينو', '1', '0000-00-00 00:00:00', '2023-09-27 11:29:13'),
(5, 1, 'Pastries', 'المعجنات', 'Pastries & Sandwiches', 'المعجنات و السندوتشات', '1', '0000-00-00 00:00:00', '2023-09-27 11:29:14'),
(6, 1, 'Flavours', 'النكهات', 'Flavours', 'النكهات', '1', '0000-00-00 00:00:00', '2023-09-27 11:29:15'),
(8, 2, 'Hot', 'الساخنة', 'Hot Beverages', 'المشروبات الساخنة', '1', '0000-00-00 00:00:00', '2023-09-27 11:38:41'),
(9, 2, 'Cold', 'الباردة', 'Cold Beverages', 'المشروبات الباردة', '1', '0000-00-00 00:00:00', '2023-09-27 11:38:41'),
(10, 2, 'Tea', 'شاي', 'Tea', 'شاي', '1', '0000-00-00 00:00:00', '2023-09-27 11:38:41'),
(11, 2, 'Boxes', 'علبة', 'Mochachino Products', 'منتجات موكاتشينو', '1', '0000-00-00 00:00:00', '2023-09-27 11:38:41'),
(12, 2, 'Pastries', 'المعجنات', 'Pastries & Sandwiches', 'المعجنات و السندوتشات', '1', '0000-00-00 00:00:00', '2023-09-27 11:38:41'),
(13, 2, 'Flavours', 'النكهات', 'Flavours', 'النكهات', '1', '0000-00-00 00:00:00', '2023-09-27 11:38:41'),
(14, 4, 'Carton', 'كرتون', 'Carton packing', 'تعبئة الكرتون', '1', '0000-00-00 00:00:00', '2023-09-27 11:45:44'),
(15, 4, 'Box', 'علبة', 'Mochachino Products', 'منتجات موكاتشينو', '1', '0000-00-00 00:00:00', '2023-09-27 11:45:44'),
(16, 4, 'Paket', 'باكت', 'Paket', 'باكت', '1', '0000-00-00 00:00:00', '2023-09-27 11:45:44');

-- --------------------------------------------------------

--
-- Table structure for table `menu_item`
--

CREATE TABLE `menu_item` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `price_level` varchar(100) NOT NULL,
  `name_eng` varchar(255) NOT NULL,
  `name_ar` varchar(255) NOT NULL,
  `big_price` varchar(100) NOT NULL,
  `small_price` varchar(100) NOT NULL,
  `big_cal` int(11) NOT NULL,
  `small_cal` int(11) NOT NULL,
  `image` varchar(255) NOT NULL,
  `status` varchar(10) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `menu_item`
--

INSERT INTO `menu_item` (`id`, `category_id`, `price_level`, `name_eng`, `name_ar`, `big_price`, `small_price`, `big_cal`, `small_cal`, `image`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, '1', 'Macchiato', 'ميكاتو', '17', '11', 56, 42, '1644831806.jpg', '1', '0000-00-00 00:00:00', '2023-05-15 06:38:31'),
(2, 1, '1', 'Espersso', 'اسبيرسو', '16', '10', 38, 30, '1644831840.jpg', '1', '0000-00-00 00:00:00', '2022-02-14 14:43:36'),
(3, 1, '1', 'CAPPUCCINO', 'كابتشينو ', '17', '12', 60, 45, 'Mochachino.jpg', '1', '0000-00-00 00:00:00', '2022-02-13 10:51:03'),
(8, 1, '1', 'MOCHACHINO', 'موكاتشينو', '18', '14', 44, 33, '1645091741.jpg', '1', '0000-00-00 00:00:00', '2022-02-17 14:55:17'),
(9, 1, '1', 'AMERICAN COFF', 'قهوة أمريكي', '17', '10', 5, 4, '1645091877.jpg', '1', '0000-00-00 00:00:00', '2022-02-17 14:57:33'),
(10, 1, '1', 'AMERICANO', 'قهوة أمريكانو', '17', '10', 5, 4, '1644741263.jpg', '1', '0000-00-00 00:00:00', '2022-02-13 13:33:59'),
(11, 1, '1', 'ARABIC MALAKY COFFEE', 'قهوة عربي ملكي', '12', '8', 6, 4, '1644754253.jpg', '1', '0000-00-00 00:00:00', '2022-02-13 17:10:29'),
(12, 1, '1', 'ARABIC HEJAZY COFF.', 'قهوة عربي حجازي ', '12', '8', 74, 55, '1644754409.jpg', '1', '0000-00-00 00:00:00', '2022-02-13 17:13:04'),
(13, 1, '1', 'COFFEE LATTE', 'قهوة لاتيه ', '17', '12', 77, 58, '1644754525.jpg', '1', '0000-00-00 00:00:00', '2022-02-13 17:15:00'),
(14, 1, '1', 'INSTANT COFFEE', 'نسكافيه ', '', '10', 0, 4, '1644755224.jpg', '1', '0000-00-00 00:00:00', '2022-02-14 10:59:49'),
(15, 1, '1', 'INST COFE W/MILK', 'نسكافيه حليب', '', '12', 0, 16, '1644755340.jpg', '1', '0000-00-00 00:00:00', '2022-02-14 11:00:09'),
(16, 1, '1', 'FRENCH COFFEE', 'قهوة فرنسي', '17', '12', 68, 51, '1644755478.jpg', '1', '0000-00-00 00:00:00', '2022-02-13 17:30:54'),
(17, 1, '1', 'TURKISH COFFEE', 'قهوة تركي ', '17', '10', 15, 11, '1644755644.jpg', '1', '0000-00-00 00:00:00', '2022-02-13 17:33:40'),
(18, 1, '1', 'TURKISH COFFEE WITH MILK', 'قهوة تركي بالحليب ', '17', '12', 90, 68, '1644756064.jpg', '1', '0000-00-00 00:00:00', '2022-02-13 17:40:39'),
(19, 1, '1', 'HOT CHOCOLATE', 'شوكولاتة ساخنة', '17', '13', 141, 106, '1644756476.jpg', '1', '0000-00-00 00:00:00', '2022-02-13 17:47:31'),
(20, 1, '1', 'ROYAL CAPPUCCINO', 'رويال كابتشينو ', '18', '', 60, 0, '1644756655.jpg', '1', '0000-00-00 00:00:00', '2022-02-14 11:02:27'),
(22, 1, '1', 'M MARSHMALLOW', 'مارشميلو ', '20', '', 13, 0, '1644824902.jpg', '1', '0000-00-00 00:00:00', '2022-02-14 12:47:57'),
(21, 1, '1', 'ROYAL MOCHACHINO', 'رويال موكاتشينو', '18', '0', 165, 0, '1644756922.jpg', '1', '0000-00-00 00:00:00', '2022-02-13 17:54:58'),
(23, 1, '1', 'SPANISH LATE HOT', 'اسبنش لاتيه ساخن', '17', '12', 0, 0, '1644825204.jpg', '1', '0000-00-00 00:00:00', '2022-02-14 12:53:00'),
(24, 1, '1', 'CORTADO', 'كورتادو', '17', '11', 0, 0, '1644825287.jpg', '1', '0000-00-00 00:00:00', '2022-02-14 12:54:22'),
(25, 5, '1', 'CROISSANT CHEESE 90GMS', 'كروسون جبنة ', '9', '', 372, 0, '1645091607.png', '1', '0000-00-00 00:00:00', '2022-06-06 14:33:18'),
(26, 5, '1', 'COOKIES', 'كوكيز', '6', '', 188, 0, '1644828688.jpg', '1', '0000-00-00 00:00:00', '2022-06-06 14:33:46'),
(27, 5, '1', 'MUFFIN', 'مافن', '5.50', '', 369, 0, '1644828846.jpg', '1', '0000-00-00 00:00:00', '2022-02-14 13:53:42'),
(28, 5, '1', 'ENGLISH CAKE', 'كيك انجليزي ', '5.50', '', 418, 0, '1644828928.jpg', '1', '0000-00-00 00:00:00', '2022-02-14 13:55:04'),
(29, 5, '1', ' Donut	', 'دونات ', '7', '', 406, 0, '1644829011.jpg', '1', '0000-00-00 00:00:00', '2022-02-14 13:56:26'),
(30, 3, '1', 'ADANY TEA', 'شاهي عدني', '13', '11', 15, 11, '1644830753.jpg', '1', '0000-00-00 00:00:00', '2023-05-14 09:17:13'),
(31, 3, '1', 'TEA WITH STEAM MILK', 'شاهي حليب ', '', '7', 0, 32, '1644830972.jpg', '1', '0000-00-00 00:00:00', '2022-02-14 14:29:07'),
(32, 3, '1', 'STEAM MILK', 'حليب ساخن ', '', '6', 0, 73, '1644831088.jpg', '1', '0000-00-00 00:00:00', '2022-02-14 14:31:04'),
(33, 3, '1', 'MOROCCAN TEA', 'شاي مغربي ', '', '6', 0, 4, '1644831719.jpg', '1', '0000-00-00 00:00:00', '2022-02-14 14:41:34'),
(34, 2, '1', 'ICE CAPPUCCINO', 'أيس كابتشينو', '16', '12', 72, 46, '1645088859.jpg', '1', '0000-00-00 00:00:00', '2022-02-17 14:07:15'),
(35, 2, '1', 'ICE CHOCOLATE', 'أيس شوكولاتة', '16', '12', 176, 113, '1645088939.jpg', '1', '0000-00-00 00:00:00', '2022-02-17 14:08:34'),
(36, 2, '1', 'ICE MOCHACHINO', 'أيس موكاتشينو', '16', '12', 58, 37, '1645089026.jpg', '1', '0000-00-00 00:00:00', '2022-02-17 14:10:01'),
(37, 2, '1', 'ICE STRAWBERRY', 'ايس فروله ', '16', '12', 122, 78, '1645089695.jpg', '1', '0000-00-00 00:00:00', '2022-02-17 14:21:10'),
(38, 2, '1', 'MONTE BLANC', 'ايس مونت بلانك ', '18', '16', 159, 102, '1645089922.jpg', '1', '0000-00-00 00:00:00', '2022-02-17 14:24:58'),
(39, 2, '1', 'MOCHACHINO FRAPPE', 'موكا فرابي ', '18', '16', 282, 181, '1645090015.jpg', '1', '0000-00-00 00:00:00', '2022-02-17 14:26:30'),
(40, 2, '1', 'SPANISH LATE', 'سبانيش لاتية ', '15', '0', 59, 0, '1645090258.jpg', '1', '0000-00-00 00:00:00', '2022-02-17 14:30:33'),
(41, 2, '1', 'ICED WHITE MOCHA', 'وايت موكا مثلج', '15', '0', 124, 0, '1645090364.jpg', '1', '0000-00-00 00:00:00', '2022-02-17 14:32:19'),
(42, 2, '1', 'ICED CARAMEL MOCHA', 'قهوه كاراميل موكا', '15', '0', 99, 0, '1645090451.jpg', '1', '0000-00-00 00:00:00', '2022-02-17 14:33:46'),
(43, 2, '1', 'ORANGE JUICE', 'عصير بتقال', '13', '10', 73, 47, '1645090522.jpg', '1', '0000-00-00 00:00:00', '2022-02-17 14:34:58'),
(44, 2, '1', 'ICED AMERICAN', 'ايس امريكانو ', '13', '0', 5, 0, '1645091116.jpg', '1', '0000-00-00 00:00:00', '2022-02-17 14:44:52'),
(45, 4, '1', 'ADANY TEA 100 GMS', 'شاهي عدني كيس 100 جرام ', '20', '0', 342, 0, '1645358263.jpg', '1', '0000-00-00 00:00:00', '2023-04-11 04:21:36'),
(46, 4, '1', 'ADANY TEA 100 GMS	', 'شاهي عدني كيس 100 جرام ', '20', '0', 342, 0, '1646139150.jpg', '1', '0000-00-00 00:00:00', '2023-04-11 04:21:47'),
(47, 4, '1', 'AM COFFEE PKT 200 GMS	', 'قهوة أمريكي 200 جرام ', '20', '0', 812, 0, '1646139320.jpg', '1', '0000-00-00 00:00:00', '2022-03-01 17:54:56'),
(48, 4, '1', 'MALAKY BOX QUICK CUP 	', 'قهوة عربي ملكي علبة ', '16', '0', 129, 0, '1646139524.jpg', '1', '0000-00-00 00:00:00', '2023-04-11 04:21:58'),
(49, 4, '1', 'HEJAZY BOX QUICK CUP', 'علبه قهوه عربي حجازي', '16', '0', 125, 0, '1646139708.jpg', '1', '0000-00-00 00:00:00', '2023-04-11 04:23:38'),
(50, 4, '1', 'TURKISH COFFEE DARK NO HAIL ', 'قهوة تركي علبة ', '20', '0', 319, 0, '1646139911.jpg', '1', '0000-00-00 00:00:00', '2023-04-11 04:23:35'),
(51, 4, '1', 'DRINKING CHOCOLATE BOX (5*20GMS)	', 'شرب الشوكولاته مربع (5 * 20GMS)', '20', '0', 531, 0, '1646139998.jpg', '1', '0000-00-00 00:00:00', '2023-04-11 04:23:34'),
(52, 4, '1', 'INSTANT COFFEE BOX (20X2)	', 'قهوة سريعة التحضير علبة ', '20', '0', 199, 0, '1646140097.jpg', '1', '0000-00-00 00:00:00', '2023-04-11 04:23:32'),
(53, 4, '1', 'COFFEE CREAMER BOX (20*3GMS)	', 'حليب بودره علبه ', '20', '0', 100, 0, '1646140182.jpg', '1', '0000-00-00 00:00:00', '2023-04-11 04:23:24'),
(54, 4, '1', 'FRENCH COFFEE HAZELNUT BOX (5*18GMS)', 'قهوة فرنسي بندق علبة ', '20', '0', 350, 0, '1646140255.jpg', '1', '0000-00-00 00:00:00', '2023-04-11 04:23:22'),
(55, 4, '1', 'MOCHA SP TEA PACKET 100 GMS	', 'شاي خاص علبة 100 جرام ', '20', '0', 315, 0, '1646140335.jpg', '1', '0000-00-00 00:00:00', '2023-04-11 04:23:21'),
(56, 6, '1', 'CARAMEL SYRUP ADDITIONAL	', 'نكه كرميل ', '0', '2', 0, 3, '1646205054.jpg', '1', '0000-00-00 00:00:00', '2022-04-13 12:56:39'),
(57, 6, '1', 'SAUCE ADDITIONAL	', 'نكه  صوصو ', '0', '3', 0, 5, '1646205176.jpg', '1', '0000-00-00 00:00:00', '2022-03-02 12:12:32'),
(58, 6, '1', ' CREAM	', 'كريم ', '0', '3', 0, 3, '1646205850.jpg', '1', '0000-00-00 00:00:00', '2022-03-02 12:23:45'),
(60, 1, '2', 'Macchiato', 'ميكاتو', '13', '9', 56, 42, '1644831806.jpg', '1', '0000-00-00 00:00:00', '2022-04-12 14:49:44'),
(61, 1, '2', 'Espersso', 'اسبيرسو', '10', '8', 38, 30, '1644831840.jpg', '1', '0000-00-00 00:00:00', '2022-04-12 14:49:44'),
(62, 1, '2', 'CAPPUCCINO', 'كابتشينو ', '13', '11', 60, 45, 'Mochachino.jpg', '1', '0000-00-00 00:00:00', '2022-04-12 14:49:44'),
(63, 1, '2', 'MOCHACHINO', 'موكاتشينو', '13', '12', 44, 33, '1645091741.jpg', '1', '0000-00-00 00:00:00', '2022-04-12 14:49:44'),
(64, 1, '2', 'AMERICAN COFF', 'قهوة أمريكي', '10', '9', 5, 4, '1645091877.jpg', '1', '0000-00-00 00:00:00', '2022-04-13 12:51:57'),
(65, 1, '2', 'AMERICANO', 'قهوة أمريكانو', '10', '9', 5, 4, '1644741263.jpg', '1', '0000-00-00 00:00:00', '2022-04-13 12:52:22'),
(66, 1, '2', 'ARABIC MALAKY COFFEE', 'قهوة عربي ملكي', '8', '6', 6, 4, '1644754253.jpg', '1', '0000-00-00 00:00:00', '2022-04-12 14:49:44'),
(67, 1, '2', 'ARABIC HEJAZY COFF.', 'قهوة عربي حجازي ', '8', '6', 74, 55, '1644754409.jpg', '1', '0000-00-00 00:00:00', '2022-04-12 14:49:44'),
(68, 1, '2', 'COFFEE LATTE', 'قهوة لاتيه ', '13', '12', 77, 58, '1644754525.jpg', '1', '0000-00-00 00:00:00', '2022-04-12 14:49:44'),
(69, 1, '2', 'INSTANT COFFEE', 'نسكافيه ', '', '9', 0, 4, '1644755224.jpg', '1', '0000-00-00 00:00:00', '2022-04-12 14:49:44'),
(70, 1, '2', 'INST COFE W/MILK', 'نسكافيه حليب', '', '10', 0, 16, '1644755340.jpg', '1', '0000-00-00 00:00:00', '2022-04-13 15:10:34'),
(71, 1, '2', 'FRENCH COFFEE', 'قهوة فرنسي', '12', '9', 68, 51, '1644755478.jpg', '1', '0000-00-00 00:00:00', '2022-04-12 14:49:44'),
(72, 1, '2', 'TURKISH COFFEE', 'قهوة تركي ', '12', '9', 15, 11, '1644755644.jpg', '1', '0000-00-00 00:00:00', '2022-04-12 14:54:04'),
(73, 1, '2', 'TURKISH COFFEE WITH MILK', 'قهوة تركي بالحليب ', '13', '10', 90, 68, '1644756064.jpg', '1', '0000-00-00 00:00:00', '2022-04-12 14:54:04'),
(74, 1, '2', 'HOT CHOCOLATE', 'شوكولاتة ساخنة', '13', '9', 141, 106, '1644756476.jpg', '1', '0000-00-00 00:00:00', '2022-04-12 14:54:04'),
(75, 1, '2', 'ROYAL CAPPUCCINO', 'رويال كابتشينو ', '17', '', 60, 0, '1644756655.jpg', '1', '0000-00-00 00:00:00', '2022-05-05 13:01:41'),
(76, 1, '2', 'M MARSHMALLOW', 'مارشميلو ', '14', '', 13, 0, '1644824902.jpg', '1', '0000-00-00 00:00:00', '2022-04-12 14:54:04'),
(77, 1, '2', 'ROYAL MOCHACHINO', 'رويال موكاتشينو', '17', '0', 165, 0, '1644756922.jpg', '1', '0000-00-00 00:00:00', '2022-05-05 13:01:55'),
(78, 1, '2', 'SPANISH LATE HOT', 'اسبنش لاتيه ساخن', '15', '12', 0, 0, '1644825204.jpg', '1', '0000-00-00 00:00:00', '2022-04-12 14:54:04'),
(79, 1, '2', 'CORTADO', 'كورتادو', '13', '9', 0, 0, '1644825287.jpg', '1', '0000-00-00 00:00:00', '2022-04-12 14:54:04'),
(80, 5, '2', 'CROISSANT CHEESE 90GMS', 'كروسون جبنة ', '8', '', 372, 0, '1645091607.png', '1', '0000-00-00 00:00:00', '2022-06-06 14:33:33'),
(81, 11, '2', 'COOKIES', 'كوكيز', '8', '', 188, 0, '1644828688.jpg', '1', '0000-00-00 00:00:00', '2024-01-25 07:23:03'),
(82, 11, '2', 'MUFFIN', 'مافن', '8', '', 369, 0, '1644828846.jpg', '1', '0000-00-00 00:00:00', '2024-01-25 07:23:59'),
(83, 5, '2', 'ENGLISH CAKE', 'كيك انجليزي ', '5.50', '', 418, 0, '1644828928.jpg', '1', '0000-00-00 00:00:00', '2022-03-02 13:12:59'),
(84, 5, '2', ' Donut	', 'دونات ', '5.50', '', 406, 0, '1644829011.jpg', '1', '0000-00-00 00:00:00', '2022-04-12 14:55:59'),
(85, 10, '2', 'ADANY TEA', 'شاهي عدني', '8', '9', 15, 11, '1644830753.jpg', '1', '0000-00-00 00:00:00', '2023-09-28 09:24:15'),
(86, 3, '2', 'TEA WITH STEAM MILK', 'شاهي حليب ', '', '7', 0, 32, '1644830972.jpg', '1', '0000-00-00 00:00:00', '2022-03-02 13:12:59'),
(87, 3, '2', 'STEAM MILK', 'حليب ساخن ', '', '5', 0, 73, '1644831088.jpg', '1', '0000-00-00 00:00:00', '2022-04-12 14:55:59'),
(88, 3, '2', 'MOROCCAN TEA', 'شاي مغربي ', '', '5', 0, 4, '1644831719.jpg', '1', '0000-00-00 00:00:00', '2022-04-12 14:55:59'),
(89, 2, '2', 'ICE CAPPUCCINO', 'أيس كابتشينو', '12', '10', 72, 46, '1645088859.jpg', '1', '0000-00-00 00:00:00', '2022-04-12 14:55:59'),
(90, 11, '2', 'ICE CHOCOLATE', 'أيس شوكولاتة', '12', '10', 176, 113, '1645088939.jpg', '1', '0000-00-00 00:00:00', '2023-09-28 09:22:05'),
(91, 2, '2', 'ICE MOCHACHINO', 'أيس موكاتشينو', '12', '10', 58, 37, '1645089026.jpg', '1', '0000-00-00 00:00:00', '2022-04-12 14:59:31'),
(92, 2, '2', 'ICE STRAWBERRY', 'ايس فروله ', '12', '10', 122, 78, '1645089695.jpg', '1', '0000-00-00 00:00:00', '2022-04-12 14:59:31'),
(93, 2, '2', 'MONTE BLANC', 'ايس مونت بلانك ', '16', '14', 159, 102, '1645089922.jpg', '1', '0000-00-00 00:00:00', '2022-04-12 14:59:31'),
(94, 2, '2', 'MOCHACHINO FRAPPE', 'موكا فرابي ', '16', '14', 282, 181, '1645090015.jpg', '1', '0000-00-00 00:00:00', '2022-04-12 14:59:31'),
(95, 2, '2', 'SPANISH LATE', 'سبانيش لاتية ', '15', '0', 59, 0, '1645090258.jpg', '1', '0000-00-00 00:00:00', '2022-03-02 13:12:59'),
(96, 2, '2', 'ICED WHITE MOCHA', 'وايت موكا مثلج', '15', '0', 124, 0, '1645090364.jpg', '1', '0000-00-00 00:00:00', '2022-03-02 13:12:59'),
(97, 2, '2', 'ICED CARAMEL MOCHA', 'قهوه كاراميل موكا', '15', '0', 99, 0, '1645090451.jpg', '1', '0000-00-00 00:00:00', '2022-03-02 13:12:59'),
(98, 2, '2', 'ORANGE JUICE', 'عصير بتقال', '10', '8', 73, 47, '1645090522.jpg', '1', '0000-00-00 00:00:00', '2022-04-12 14:59:31'),
(99, 2, '2', 'ICED AMERICAN', 'ايس امريكانو ', '12', '0', 5, 0, '1645091116.jpg', '1', '0000-00-00 00:00:00', '2022-04-12 14:59:31'),
(100, 4, '2', 'ADANY TEA 100 GMS', 'شاهي عدني كيس 100 جرام ', '18', '0', 342, 0, '1660028211.jpg', '1', '0000-00-00 00:00:00', '2022-08-09 03:56:51'),
(101, 4, '2', 'ADANY TEA 100 GMS	', 'شاهي عدني كيس 100 جرام ', '0', '18', 0, 342, '1646139150.jpg', '1', '0000-00-00 00:00:00', '2022-04-13 12:48:23'),
(102, 4, '2', 'AM COFFEE PKT 200 GMS	', 'قهوة أمريكي 200 جرام ', '18', '0', 812, 0, '1646139320.jpg', '1', '0000-00-00 00:00:00', '2022-04-13 12:49:02'),
(103, 4, '2', 'MALAKY BOX QUICK CUP 	', 'قهوة عربي ملكي علبة ', '0', '16', 0, 129, '1646139524.jpg', '1', '0000-00-00 00:00:00', '2022-03-02 13:12:59'),
(104, 4, '2', 'HEJAZY BOX QUICK CUP', 'علبه قهوه عربي حجازي', '0', '16', 0, 125, '1646139708.jpg', '1', '0000-00-00 00:00:00', '2022-03-02 13:12:59'),
(105, 4, '2', 'TURKISH COFFEE DARK NO HAIL ', 'قهوة تركي علبة ', '0', '20', 0, 319, '1646139911.jpg', '1', '0000-00-00 00:00:00', '2022-03-02 13:12:59'),
(106, 4, '2', 'DRINKING CHOCOLATE BOX (5*20GMS)	', 'شرب الشوكولاته مربع (5 * 20GMS)', '0', '20', 0, 531, '1646139998.jpg', '1', '0000-00-00 00:00:00', '2022-03-02 13:12:59'),
(107, 4, '2', 'INSTANT COFFEE BOX (20X2)	', 'قهوة سريعة التحضير علبة ', '0', '18', 0, 199, '1646140097.jpg', '1', '0000-00-00 00:00:00', '2022-04-13 17:28:24'),
(108, 4, '2', 'COFFEE CREAMER BOX (20*3GMS)	', 'حليب بودره علبه ', '0', '18', 0, 100, '1646140182.jpg', '1', '0000-00-00 00:00:00', '2022-04-13 12:58:08'),
(109, 4, '2', 'FRENCH COFFEE HAZELNUT BOX (5*18GMS)', 'قهوة فرنسي بندق علبة ', '0', '20', 0, 350, '1646140255.jpg', '1', '0000-00-00 00:00:00', '2022-03-02 13:12:59'),
(110, 4, '2', 'MOCHA SP TEA PACKET 100 GMS	', 'شاي خاص علبة 100 جرام ', '0', '18', 0, 315, '1646140335.jpg', '1', '0000-00-00 00:00:00', '2022-04-13 17:32:01'),
(111, 6, '2', 'CARAMEL SYRUP ADDITIONAL	', 'نكه كرميل ', '0', '2', 0, 3, '1646205054.jpg', '1', '0000-00-00 00:00:00', '2022-04-13 12:53:53'),
(112, 6, '2', 'SAUCE ADDITIONAL	', 'نكه  صوصو ', '0', '2', 0, 5, '1646205176.jpg', '1', '0000-00-00 00:00:00', '2022-05-05 14:12:23'),
(113, 6, '2', ' CREAM	', 'كريم ', '0', '2', 0, 3, '1646205850.jpg', '1', '0000-00-00 00:00:00', '2022-05-05 14:12:40'),
(114, 2, '1', 'SWEET MEMORY', 'سويت ميموري', '18', '16', 185, 119, '1646211631.jpg', '1', '0000-00-00 00:00:00', '2022-03-02 14:00:07'),
(115, 2, '1', 'SLUSH DREAM', 'سلاش دريم ', '18', '16', 229, 147, '1646211755.jpg', '1', '0000-00-00 00:00:00', '2022-03-02 14:02:11'),
(116, 2, '1', 'WATER 	', 'ماء بارد ', '2', '1', 0, 0, '1646915187.png', '1', '0000-00-00 00:00:00', '2022-03-10 17:26:02'),
(117, 3, '1', 'MOCHA SP TEA CUP', 'شاي خاص ', '0', '7', 0, 4, '1646212037.jpg', '1', '0000-00-00 00:00:00', '2022-03-02 14:06:53'),
(118, 3, '1', 'GREEN TEA', 'شاي أخضر ', '0', '6', 0, 4, '1646212169.jpg', '1', '0000-00-00 00:00:00', '2022-03-02 14:09:04'),
(119, 3, '1', 'GREEN TEA GINGER', 'شاي أخضر مع زنجبيل ', '0', '7', 0, 8, '1646212292.jpg', '1', '0000-00-00 00:00:00', '2022-03-02 14:11:08'),
(120, 5, '1', 'OLIFETA BROWN CIBATTA	', 'شيباتا فيتا ساندوتش أسمر', '13', '', 0, 0, '1649849559.JPG', '1', '0000-00-00 00:00:00', '2022-04-13 16:32:14'),
(121, 5, '1', 'DAYTUNA BROWN CIBATTA	', 'شيباتا تونا ساندوتش أسمر', '13', '', 0, 0, '1649849722.JPG', '1', '0000-00-00 00:00:00', '2022-04-13 16:34:57'),
(122, 5, '1', 'CHICKEN SHISHTAWOOK	', 'شيش طاووق دجاج ', '17', '', 0, 0, '1649849793.JPG', '1', '0000-00-00 00:00:00', '2022-04-13 16:36:09'),
(123, 5, '1', 'CHICKEN MASHROOM	', 'ساندوتش دجاج بالفطر ', '17', '', 0, 0, '1649849981.JPG', '1', '0000-00-00 00:00:00', '2022-04-13 16:39:17'),
(124, 5, '1', 'CHICKEN MIX TOAST	', 'ساندوتش توست دجاج مكس ', '10', '', 0, 0, '1649850778.JPG', '1', '0000-00-00 00:00:00', '2022-08-02 08:12:26'),
(125, 5, '1', 'TUNA MIX TOAST	', 'ساندوتش توست تونه مكس ', '10', '', 0, 0, '1649850855.JPG', '1', '0000-00-00 00:00:00', '2022-08-02 08:12:55'),
(126, 5, '1', 'TURKEY TOAST	', 'ساندوتش توست تركي ', '10', '', 0, 0, '1649850919.JPG', '1', '0000-00-00 00:00:00', '2022-08-02 08:13:11'),
(127, 5, '1', 'GRANOLA	', 'جرانولا ', '24', '', 0, 0, '1649850980.JPG', '1', '0000-00-00 00:00:00', '2022-04-13 16:55:56'),
(128, 5, '1', 'ROOTS CEASER	', 'سلطه سيزر روتس ', '0', '17', 0, 0, '1655021459.JPG', '1', '0000-00-00 00:00:00', '2022-06-12 13:10:34'),
(129, 3, '2', 'Ginger Drink 	', 'مشروب الزنجبيل الساخن ', '0', '5', 0, 4, '1651738879.jpg', '1', '0000-00-00 00:00:00', '2022-05-05 13:20:54'),
(130, 3, '2', 'MOROCCAN TEA', 'شاي مغربي ', '0', '5', 0, 4, '1651739048.jpg', '1', '0000-00-00 00:00:00', '2022-05-05 13:23:43'),
(131, 3, '2', 'GREEN TEA CUP ', 'شاي أخضر ', '0', '5', 0, 4, '1651739313.jpg', '1', '0000-00-00 00:00:00', '2022-05-05 13:28:09'),
(132, 3, '2', 'GREEN TEA GINGER', 'شاي أخضر مع زنجبيل ', '0', '5', 0, 4, '1651739384.jpg', '1', '0000-00-00 00:00:00', '2022-05-05 13:29:20'),
(133, 2, '2', 'Water Nestle Bottle - 600 ML', 'مياه نستلة 600 ملل', '1', '0', 0, 0, '1651740315.jpg', '1', '0000-00-00 00:00:00', '2022-05-05 13:44:51'),
(134, 2, '2', 'SLASH DREAM', '	مشروب سلاش دريم', '16', '14', 229, 147, '1651743247.jpg', '1', '0000-00-00 00:00:00', '2022-05-05 14:33:43'),
(135, 5, '2', 'OLIFETA BROWN CIBATTA	', 'شيباتا فيتا ساندوتش أسمر', '13', '0', 0, 0, '1652002455.jpg', '1', '0000-00-00 00:00:00', '2022-05-08 14:33:50'),
(136, 5, '2', 'DAYTUNA BROWN CIBATTA	', 'شيباتا تونا ساندوتش أسمر', '13', '', 0, 0, '1652002621.jpg', '1', '0000-00-00 00:00:00', '2022-05-08 14:36:37'),
(137, 5, '2', 'CHICKEN SHISHTAWOOK	', 'شيش طاووق دجاج ', '17', '', 0, 0, '1652002839.jpg', '1', '0000-00-00 00:00:00', '2022-05-08 14:40:15'),
(138, 5, '2', 'CHICKEN MASHROOM	', 'ساندوتش دجاج بالفطر ', '17', '', 0, 0, '1652003051.jpg', '1', '0000-00-00 00:00:00', '2022-05-08 14:43:47'),
(139, 5, '2', 'CHICKEN MIX TOAST	', 'ساندوتش توست دجاج مكس ', '10', '', 0, 0, '1652003250.jpg', '1', '0000-00-00 00:00:00', '2022-08-02 08:12:41'),
(140, 5, '2', 'GRANOLA	', 'جرانولا ', '24', '', 0, 0, '1655021665.JPG', '1', '0000-00-00 00:00:00', '2022-06-12 13:14:00'),
(141, 5, '2', 'ROOTS CEASER	', 'سلطه سيزر روتس ', '28', '', 0, 0, '1652004386.jpg', '1', '0000-00-00 00:00:00', '2022-05-08 15:06:01'),
(142, 2, '2', 'MOJITO PASSION FRUIT ', 'باشن فروت موهيتو', '15', '0', 234, 0, '1654418302.jpg', '1', '0000-00-00 00:00:00', '2022-06-08 14:16:03'),
(143, 2, '2', 'ICED TEA PASSION FRUIT ', 'باشن فروت ايس تي ', '15', '0', 200, 0, '1654418122.jpg', '1', '0000-00-00 00:00:00', '2022-06-08 14:17:32'),
(144, 2, '1', 'ICED TEA PASSION FRUIT ', 'باشن فروت ايس تي ', '15', '0', 200, 0, '1654418181.jpg', '1', '0000-00-00 00:00:00', '2022-06-08 14:17:49'),
(145, 2, '1', 'MOJITO PASSION FRUIT ', 'باشن فروت موهيتو', '15', '0', 234, 0, '1654418227.jpg', '1', '0000-00-00 00:00:00', '2022-06-08 14:16:51'),
(146, 2, '1', 'MOJITO PASSION FRUIT', 'باشن فروت موهيتو', '15', '', 0, 0, '1654508339.jpg', '0', '0000-00-00 00:00:00', '2022-06-06 14:40:45'),
(147, 5, '2', 'QUINOA TWIST	', 'سلطة كينوا تويست ', '0', '18', 0, 0, '1655019707.JPG', '1', '0000-00-00 00:00:00', '2022-06-12 12:41:22'),
(148, 5, '2', 'PASTA SALAD	', 'سلطة مكرونة ', '0', '18', 0, 0, '1655021252.JPG', '1', '0000-00-00 00:00:00', '2022-06-12 13:07:07'),
(150, 14, '4', 'Sugar White 1000', 'سكر ابيض 1000', '0', '0', 0, 0, '', '1', '0000-00-00 00:00:00', '2023-09-28 03:57:41'),
(151, 14, '4', 'Sugar White 2000', 'سكر ابيض 2000', '0', '0', 0, 0, '', '1', '0000-00-00 00:00:00', '2023-09-28 03:57:48'),
(152, 14, '4', 'Sugar Brown 1000', 'سكر براون 1000', '0', '0', 0, 0, '', '1', '0000-00-00 00:00:00', '2023-09-28 03:57:50'),
(153, 14, '4', 'Sugar Brown 2000', ' 2000 سكر براون', '0', '0', 0, 0, '', '1', '0000-00-00 00:00:00', '2023-09-28 03:57:51'),
(154, 16, '4', 'Espresso Coffee beans', 'حبوب قهوة اسبريسو', '0', '0', 0, 0, '', '1', '0000-00-00 00:00:00', '2023-09-28 03:57:53'),
(155, 16, '4', 'Cappuccino Beans', 'حبوب الكابتشينو', '0', '0', 0, 0, '', '1', '0000-00-00 00:00:00', '2023-09-28 03:57:55'),
(156, 16, '4', 'Espresso Coffee powder', 'مسحوق قهوة الاسبريسو', '0', '0', 0, 0, '', '1', '0000-00-00 00:00:00', '2023-09-28 03:57:57'),
(157, 16, '4', 'Cappuccino Powder', 'مسحوق الكابتشينو', '0', '0', 0, 0, '', '1', '0000-00-00 00:00:00', '2023-09-28 03:57:58'),
(158, 16, '4', 'American Coffee', 'القهوة الأمريكية', '0', '0', 0, 0, '', '1', '0000-00-00 00:00:00', '2023-09-28 03:58:00'),
(159, 16, '4', 'Turkish Medium Coffee', 'قهوة تركية وسط', '0', '0', 0, 0, '', '1', '0000-00-00 00:00:00', '2023-09-28 03:58:01'),
(160, 16, '4', 'Turkish Medium Coffee W/Cardamom', 'قهوة تركية وسط مع هيل', '0', '0', 0, 0, '', '1', '0000-00-00 00:00:00', '2023-09-28 03:58:03'),
(161, 16, '4', 'Turkish Coffee Dark', 'القهوة التركية الداكنة', '', '', 0, 0, '', '1', '0000-00-00 00:00:00', '2023-09-28 04:03:13'),
(162, 16, '4', 'Turkish Coffee Dark W/Cardamom', 'قهوة تركية داكنة مع الهيل', '', '', 0, 0, '', '1', '0000-00-00 00:00:00', '2023-09-28 04:04:24'),
(163, 16, '4', 'Arabic Coffee ready mix (Hejazi)', 'خلطة القهوة العربية الجاهزة (حجازي)', '', '', 0, 0, '', '1', '0000-00-00 00:00:00', '2023-09-28 04:05:42'),
(164, 16, '4', 'Arabic Coffee ready mix (Royal Malaky)', 'خلطة القهوة العربية الجاهزة (رويال ملكي)', '', '', 0, 0, '', '1', '0000-00-00 00:00:00', '2023-09-28 04:06:39'),
(165, 16, '4', 'Mochachino Special tea', 'شاي موكاشينو خاص', '', '', 0, 0, '', '1', '0000-00-00 00:00:00', '2023-09-28 04:07:44'),
(166, 16, '4', 'Mochachino Green tea', 'موكاشينو شاي أخضر', '', '', 0, 0, '', '1', '0000-00-00 00:00:00', '2023-09-28 04:08:20'),
(167, 16, '4', 'Adany mix', 'مزيج عدني', '', '', 0, 0, '', '1', '0000-00-00 00:00:00', '2023-09-28 04:09:05'),
(168, 16, '4', 'French Coffee', 'قهوة فرنسية', '', '', 0, 0, '', '1', '0000-00-00 00:00:00', '2023-09-28 11:25:37'),
(169, 16, '4', 'Double Mocha', 'دبل موكا', '', '', 0, 0, '', '1', '0000-00-00 00:00:00', '2023-09-28 04:09:55'),
(170, 16, '4', 'Drinking Chocolate', 'شرب الشوكولاتة', '', '', 0, 0, '', '1', '0000-00-00 00:00:00', '2023-09-28 04:10:23'),
(171, 16, '4', 'Intant Coffee', 'وفي الوقت نفسه القهوة', '', '', 0, 0, '', '1', '0000-00-00 00:00:00', '2023-09-28 04:12:30'),
(173, 14, '4', 'INSTANT COFFEE', 'نسكافيه ', '', '', 0, 0, '', '1', '2023-09-28 18:03:50', '2023-09-28 11:03:50'),
(174, 14, '4', 'Coffee Creamer', 'كريمة قهوة', '', '', 0, 0, '', '1', '2023-09-28 18:19:56', '2023-09-28 11:19:56'),
(175, 15, '4', 'Malkay Quick', 'مالكي كويك', '', '', 0, 0, '', '1', '2023-09-28 18:23:08', '2023-09-28 11:23:08'),
(176, 15, '4', 'Hejazy Quick', ' حجازي كويك', '', '', 0, 0, '', '1', '2023-09-28 18:24:01', '2023-09-28 11:24:01');

-- --------------------------------------------------------

--
-- Table structure for table `menu_item_img`
--

CREATE TABLE `menu_item_img` (
  `id` int(11) NOT NULL,
  `itm_id` int(11) NOT NULL,
  `file` varchar(150) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu_item_img`
--

INSERT INTO `menu_item_img` (`id`, `itm_id`, `file`, `created_at`) VALUES
(1, 1, '1644831806.jpg', '2023-05-15 03:38:31'),
(2, 2, '1644831840.jpg', '2022-02-14 11:43:36'),
(3, 3, 'Mochachino.jpg', '2022-02-13 07:51:03'),
(4, 8, '1645091741.jpg', '2022-02-17 11:55:17'),
(5, 9, '1645091877.jpg', '2022-02-17 11:57:33'),
(6, 10, '1644741263.jpg', '2022-02-13 10:33:59'),
(7, 11, '1644754253.jpg', '2022-02-13 14:10:29'),
(8, 12, '1644754409.jpg', '2022-02-13 14:13:04'),
(9, 13, '1644754525.jpg', '2022-02-13 14:15:00'),
(10, 14, '1644755224.jpg', '2022-02-14 07:59:49'),
(11, 15, '1644755340.jpg', '2022-02-14 08:00:09'),
(12, 16, '1644755478.jpg', '2022-02-13 14:30:54'),
(13, 17, '1644755644.jpg', '2022-02-13 14:33:40'),
(14, 18, '1644756064.jpg', '2022-02-13 14:40:39'),
(15, 19, '1644756476.jpg', '2022-02-13 14:47:31'),
(16, 20, '1644756655.jpg', '2022-02-14 08:02:27'),
(17, 22, '1644824902.jpg', '2022-02-14 09:47:57'),
(18, 21, '1644756922.jpg', '2022-02-13 14:54:58'),
(19, 23, '1644825204.jpg', '2022-02-14 09:53:00'),
(20, 24, '1644825287.jpg', '2022-02-14 09:54:22'),
(21, 25, '1645091607.png', '2022-06-06 11:33:18'),
(22, 26, '1644828688.jpg', '2022-06-06 11:33:46'),
(23, 27, '1644828846.jpg', '2022-02-14 10:53:42'),
(24, 28, '1644828928.jpg', '2022-02-14 10:55:04'),
(25, 29, '1644829011.jpg', '2022-02-14 10:56:26'),
(26, 30, '1644830753.jpg', '2023-05-14 06:17:13'),
(27, 31, '1644830972.jpg', '2022-02-14 11:29:07'),
(28, 32, '1644831088.jpg', '2022-02-14 11:31:04'),
(29, 33, '1644831719.jpg', '2022-02-14 11:41:34'),
(30, 34, '1645088859.jpg', '2022-02-17 11:07:15'),
(31, 35, '1645088939.jpg', '2022-02-17 11:08:34'),
(32, 36, '1645089026.jpg', '2022-02-17 11:10:01'),
(33, 37, '1645089695.jpg', '2022-02-17 11:21:10'),
(34, 38, '1645089922.jpg', '2022-02-17 11:24:58'),
(35, 39, '1645090015.jpg', '2022-02-17 11:26:30'),
(36, 40, '1645090258.jpg', '2022-02-17 11:30:33'),
(37, 41, '1645090364.jpg', '2022-02-17 11:32:19'),
(38, 42, '1645090451.jpg', '2022-02-17 11:33:46'),
(39, 43, '1645090522.jpg', '2022-02-17 11:34:58'),
(40, 44, '1645091116.jpg', '2022-02-17 11:44:52'),
(41, 45, '1645358263.jpg', '2023-04-11 01:21:36'),
(42, 46, '1646139150.jpg', '2023-04-11 01:21:47'),
(43, 47, '1646139320.jpg', '2022-03-01 14:54:56'),
(44, 48, '1646139524.jpg', '2023-04-11 01:21:58'),
(45, 49, '1646139708.jpg', '2023-04-11 01:23:38'),
(46, 50, '1646139911.jpg', '2023-04-11 01:23:35'),
(47, 51, '1646139998.jpg', '2023-04-11 01:23:34'),
(48, 52, '1646140097.jpg', '2023-04-11 01:23:32'),
(49, 53, '1646140182.jpg', '2023-04-11 01:23:24'),
(50, 54, '1646140255.jpg', '2023-04-11 01:23:22'),
(51, 55, '1646140335.jpg', '2023-04-11 01:23:21'),
(52, 56, '1646205054.jpg', '2022-04-13 09:56:39'),
(53, 57, '1646205176.jpg', '2022-03-02 09:12:32'),
(54, 58, '1646205850.jpg', '2022-03-02 09:23:45'),
(55, 60, '1644831806.jpg', '2022-04-12 11:49:44'),
(56, 61, '1644831840.jpg', '2022-04-12 11:49:44'),
(57, 62, 'Mochachino.jpg', '2022-04-12 11:49:44'),
(58, 63, '1645091741.jpg', '2022-04-12 11:49:44'),
(59, 64, '1645091877.jpg', '2022-04-13 09:51:57'),
(60, 65, '1644741263.jpg', '2022-04-13 09:52:22'),
(61, 66, '1644754253.jpg', '2022-04-12 11:49:44'),
(62, 67, '1644754409.jpg', '2022-04-12 11:49:44'),
(63, 68, '1644754525.jpg', '2022-04-12 11:49:44'),
(64, 69, '1644755224.jpg', '2022-04-12 11:49:44'),
(65, 70, '1644755340.jpg', '2022-04-13 12:10:34'),
(66, 71, '1644755478.jpg', '2022-04-12 11:49:44'),
(67, 72, '1644755644.jpg', '2022-04-12 11:54:04'),
(68, 73, '1644756064.jpg', '2022-04-12 11:54:04'),
(69, 74, '1644756476.jpg', '2022-04-12 11:54:04'),
(70, 75, '1644756655.jpg', '2022-05-05 10:01:41'),
(71, 76, '1644824902.jpg', '2022-04-12 11:54:04'),
(72, 77, '1644756922.jpg', '2022-05-05 10:01:55'),
(73, 78, '1644825204.jpg', '2022-04-12 11:54:04'),
(74, 79, '1644825287.jpg', '2022-04-12 11:54:04'),
(75, 80, '1645091607.png', '2022-06-06 11:33:33'),
(76, 81, '1644828688.jpg', '2022-06-06 11:33:58'),
(77, 82, '1644828846.jpg', '2022-05-05 10:39:08'),
(78, 83, '1644828928.jpg', '2022-03-02 10:12:59'),
(79, 84, '1644829011.jpg', '2022-04-12 11:55:59'),
(80, 85, '1644830753.jpg', '2022-04-12 11:25:24'),
(81, 86, '1644830972.jpg', '2022-03-02 10:12:59'),
(82, 87, '1644831088.jpg', '2022-04-12 11:55:59'),
(83, 88, '1644831719.jpg', '2022-04-12 11:55:59'),
(84, 89, '1645088859.jpg', '2022-04-12 11:55:59'),
(85, 90, '1645088939.jpg', '2022-04-12 11:59:31'),
(86, 91, '1645089026.jpg', '2022-04-12 11:59:31'),
(87, 92, '1645089695.jpg', '2022-04-12 11:59:31'),
(88, 93, '1645089922.jpg', '2022-04-12 11:59:31'),
(89, 94, '1645090015.jpg', '2022-04-12 11:59:31'),
(90, 95, '1645090258.jpg', '2022-03-02 10:12:59'),
(91, 96, '1645090364.jpg', '2022-03-02 10:12:59'),
(92, 97, '1645090451.jpg', '2022-03-02 10:12:59'),
(93, 98, '1645090522.jpg', '2022-04-12 11:59:31'),
(94, 99, '1645091116.jpg', '2022-04-12 11:59:31'),
(95, 100, '1660028211.jpg', '2022-08-09 00:56:51'),
(96, 101, '1646139150.jpg', '2022-04-13 09:48:23'),
(97, 102, '1646139320.jpg', '2022-04-13 09:49:02'),
(98, 103, '1646139524.jpg', '2022-03-02 10:12:59'),
(99, 104, '1646139708.jpg', '2022-03-02 10:12:59'),
(100, 105, '1646139911.jpg', '2022-03-02 10:12:59'),
(101, 106, '1646139998.jpg', '2022-03-02 10:12:59'),
(102, 107, '1646140097.jpg', '2022-04-13 14:28:24'),
(103, 108, '1646140182.jpg', '2022-04-13 09:58:08'),
(104, 109, '1646140255.jpg', '2022-03-02 10:12:59'),
(105, 110, '1646140335.jpg', '2022-04-13 14:32:01'),
(106, 111, '1646205054.jpg', '2022-04-13 09:53:53'),
(107, 112, '1646205176.jpg', '2022-05-05 11:12:23'),
(108, 113, '1646205850.jpg', '2022-05-05 11:12:40'),
(109, 114, '1646211631.jpg', '2022-03-02 11:00:07'),
(110, 115, '1646211755.jpg', '2022-03-02 11:02:11'),
(111, 116, '1646915187.png', '2022-03-10 14:26:02'),
(112, 117, '1646212037.jpg', '2022-03-02 11:06:53'),
(113, 118, '1646212169.jpg', '2022-03-02 11:09:04'),
(114, 119, '1646212292.jpg', '2022-03-02 11:11:08'),
(115, 120, '1649849559.JPG', '2022-04-13 13:32:14'),
(116, 121, '1649849722.JPG', '2022-04-13 13:34:57'),
(117, 122, '1649849793.JPG', '2022-04-13 13:36:09'),
(118, 123, '1649849981.JPG', '2022-04-13 13:39:17'),
(119, 124, '1649850778.JPG', '2022-08-02 05:12:26'),
(120, 125, '1649850855.JPG', '2022-08-02 05:12:55'),
(121, 126, '1649850919.JPG', '2022-08-02 05:13:11'),
(122, 127, '1649850980.JPG', '2022-04-13 13:55:56'),
(123, 128, '1655021459.JPG', '2022-06-12 10:10:34'),
(124, 129, '1651738879.jpg', '2022-05-05 10:20:54'),
(125, 130, '1651739048.jpg', '2022-05-05 10:23:43'),
(126, 131, '1651739313.jpg', '2022-05-05 10:28:09'),
(127, 132, '1651739384.jpg', '2022-05-05 10:29:20'),
(128, 133, '1651740315.jpg', '2022-05-05 10:44:51'),
(129, 134, '1651743247.jpg', '2022-05-05 11:33:43'),
(130, 135, '1652002455.jpg', '2022-05-08 11:33:50'),
(131, 136, '1652002621.jpg', '2022-05-08 11:36:37'),
(132, 137, '1652002839.jpg', '2022-05-08 11:40:15'),
(133, 138, '1652003051.jpg', '2022-05-08 11:43:47'),
(134, 139, '1652003250.jpg', '2022-08-02 05:12:41'),
(135, 140, '1655021665.JPG', '2022-06-12 10:14:00'),
(136, 141, '1652004386.jpg', '2022-05-08 12:06:01'),
(137, 142, '1654418302.jpg', '2022-06-08 11:16:03'),
(138, 143, '1654418122.jpg', '2022-06-08 11:17:32'),
(139, 144, '1654418181.jpg', '2022-06-08 11:17:49'),
(140, 145, '1654418227.jpg', '2022-06-08 11:16:51'),
(141, 146, '1654508339.jpg', '2022-06-06 11:40:45'),
(142, 147, '1655019707.JPG', '2022-06-12 09:41:22'),
(143, 148, '1655021252.JPG', '2022-06-12 10:07:07');

-- --------------------------------------------------------

--
-- Table structure for table `payrolls`
--

CREATE TABLE `payrolls` (
  `id` int(11) NOT NULL,
  `emp_id` varchar(255) NOT NULL,
  `month_year` varchar(50) NOT NULL,
  `basic_salary` decimal(10,2) NOT NULL,
  `housing_allowance` decimal(10,2) NOT NULL,
  `transport_allowance` decimal(10,2) NOT NULL,
  `food_allowance` decimal(10,2) NOT NULL,
  `miscellaneous_allowance` decimal(10,2) NOT NULL,
  `cashier_allowance` decimal(10,2) NOT NULL,
  `fuel_allowance` decimal(10,2) NOT NULL,
  `telephone_allowance` decimal(10,2) NOT NULL,
  `other_allowance` decimal(10,2) NOT NULL,
  `guard_allowance` decimal(10,2) NOT NULL,
  `total_gross_salary` decimal(10,2) NOT NULL,
  `total_benefits` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_deductions` decimal(10,2) NOT NULL DEFAULT 0.00,
  `net_salary` decimal(10,2) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'generated',
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_benefits`
--

CREATE TABLE `payroll_benefits` (
  `id` int(11) NOT NULL,
  `emp_id` varchar(15) NOT NULL,
  `benefit` varchar(100) NOT NULL,
  `note` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `hours` int(11) DEFAULT NULL,
  `month` varchar(50) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `type_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_deductions`
--

CREATE TABLE `payroll_deductions` (
  `id` int(11) NOT NULL,
  `emp_id` varchar(15) NOT NULL,
  `deduction` varchar(100) NOT NULL,
  `note` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `month` varchar(50) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `portfolio`
--

CREATE TABLE `portfolio` (
  `id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `emp_id` int(11) NOT NULL,
  `attachment` varchar(255) NOT NULL,
  `description` longtext NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `portfolio`
--

INSERT INTO `portfolio` (`id`, `title`, `emp_id`, `attachment`, `description`, `updated_at`, `created_at`) VALUES
(1, 'IT', 5430, 'IT71271690440357.pdf', '<p>I am a dedicated and experienced IT professional with a passion for solving complex technical challenges. With a bachelor\'s degree in Computer Science and 10+ years of experience, I have developed expertise in areas such as programming languages, database management, and network administration.</p><p><br>I possess strong skills in troubleshooting hardware and software issues, implementing system upgrades, and ensuring data security. I am proficient in languages like JavaScript, PHP Core, and Python, and have hands-on experience in web application development and software testing. Collaborative, adaptable, and committed to continuous learning, I thrive in dynamic environments and enjoy contributing to the success of innovative projects. Explore further to discover more about my achievements and capabilities in the IT field.<br></p>', '2025-05-25 10:55:15', '2023-07-27 13:45:57');

-- --------------------------------------------------------

--
-- Table structure for table `saudi_cities`
--

CREATE TABLE `saudi_cities` (
  `id` int(11) NOT NULL,
  `name_en` varchar(255) NOT NULL,
  `name_ar` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `saudi_cities`
--

INSERT INTO `saudi_cities` (`id`, `name_en`, `name_ar`) VALUES
(1, 'Riyadh', 'الرياض'),
(2, 'Jeddah', 'جدة'),
(3, 'Makkah', 'مكة المكرمة'),
(4, 'Medina', 'المدينة المنورة'),
(5, 'Al-Ahsa', 'الحصة'),
(6, 'Taif', 'الطائف'),
(7, 'Dammam', 'الدمام'),
(8, 'Khamis Mushait', 'خميس مشيط'),
(9, 'Buraidah', 'بريدة'),
(10, 'Khobar', 'الخبر'),
(11, 'Tabuk', 'تبوك'),
(12, 'Hail', 'حائل'),
(13, 'Hafar Al-Batin', 'حفر الباطن'),
(14, 'Jubail', 'الجبيل'),
(15, 'Al-Kharj', 'الخرج'),
(16, 'Qatif', 'القطيف'),
(17, 'Abha', 'أبها'),
(18, 'Najran', 'نجران'),
(19, 'Yanbu', 'Yanbu'),
(20, 'Al Qunfudhah', 'القنفذة'),
(21, 'Other', 'أخرى'),
(22, 'Jiza', 'جازان');

-- --------------------------------------------------------

--
-- Table structure for table `section`
--

CREATE TABLE `section` (
  `id` int(11) NOT NULL,
  `section_name` varchar(255) NOT NULL,
  `location_owner` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `camera_in` int(11) DEFAULT NULL,
  `camera_out` int(11) DEFAULT NULL,
  `b_license_exp` varchar(25) NOT NULL,
  `b_license_no` varchar(50) NOT NULL,
  `location_dist` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `bulding_base` varchar(255) NOT NULL,
  `bulding_size` varchar(255) NOT NULL,
  `t_bulding_size` varchar(50) NOT NULL,
  `latitude` varchar(255) NOT NULL,
  `longitude` varchar(255) NOT NULL,
  `location_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `municipality` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `sub_municipality` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `status` varchar(10) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `section`
--

INSERT INTO `section` (`id`, `section_name`, `location_owner`, `camera_in`, `camera_out`, `b_license_exp`, `b_license_no`, `location_dist`, `bulding_base`, `bulding_size`, `t_bulding_size`, `latitude`, `longitude`, `location_name`, `municipality`, `sub_municipality`, `status`, `created_at`, `updated_at`) VALUES
(3, 'Al-Mutlak - Head Office', '', NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '1', '2025-05-27 05:16:28', '2025-07-07 05:50:41'),
(4, 'Jeddah - Campus', '', NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '1', '2025-05-27 05:17:39', '2025-05-27 07:52:12'),
(16, 'Jeddah - Metal Factory', '', NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '1', '2025-05-27 06:50:42', '2025-05-27 07:44:54'),
(17, 'Jeddah - Filter Factory', '', NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '1', '2025-05-27 06:50:42', '2025-05-27 07:44:43'),
(18, 'Riyadh - Filter Factory', '', NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '1', '2025-05-27 06:50:42', '2025-05-27 07:45:13'),
(19, 'Jeddah - Reefer Factory', '', NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '1', '2025-05-27 06:50:42', '2025-05-27 07:45:01'),
(20, 'Jeddah - Sanam Factory', '', NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '1', '2025-05-27 06:50:42', '2025-05-27 07:45:29'),
(21, 'Jeddah - Spare Part Warehouse', '', NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '1', '2025-05-27 07:50:03', '2025-05-27 07:50:03'),
(22, 'Riyadh - Spare Part Warehouse', '', NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '1', '2025-05-27 07:50:03', '2025-05-27 07:50:03'),
(23, 'Jeddah - Kilo 8 Spare Part Warehouse', '', NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '1', '2025-05-27 07:50:03', '2025-05-27 07:50:03'),
(24, 'Jeddah - Al-Readah Logistic Warehouse', '', NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '1', '2025-05-27 07:50:03', '2025-05-27 07:50:03'),
(25, 'Damam - Al-Readah Logistic Warehouse', '', NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '1', '2025-05-27 07:50:03', '2025-05-27 07:50:03'),
(26, 'Damam - Filter Warehouse', '', NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '1', '2025-05-27 07:50:03', '2025-05-27 10:09:08'),
(27, 'Riyadh - Al-Readah Logistic Warehouse', '', NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '1', '2025-05-27 07:50:03', '2025-05-27 07:50:03'),
(28, 'Damam - Spare Part Warehouse', '', NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '1', '2025-05-27 07:50:03', '2025-05-27 07:50:03'),
(30, 'Amlak - Head Office', '', NULL, NULL, '', '', '', '', '', '', '', '', '', '', '', '1', '2025-07-07 05:51:26', '2025-07-07 05:51:45');

-- --------------------------------------------------------

--
-- Table structure for table `smart_request`
--

CREATE TABLE `smart_request` (
  `id` int(11) NOT NULL,
  `inv_no` varchar(255) NOT NULL,
  `tally_id` varchar(255) NOT NULL,
  `injazat_id` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `sub_title` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `sub_type` varchar(100) NOT NULL,
  `item_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `quantity` varchar(255) NOT NULL,
  `product_price` varchar(255) NOT NULL,
  `itmvalue` varchar(100) NOT NULL,
  `vat_rate` varchar(100) NOT NULL,
  `vat_val` varchar(100) NOT NULL,
  `amount` varchar(100) NOT NULL,
  `idiscount` varchar(100) NOT NULL,
  `total_cost` varchar(255) NOT NULL,
  `discount` varchar(255) NOT NULL,
  `emp_id` int(11) NOT NULL,
  `department` varchar(255) NOT NULL,
  `prep_by` varchar(255) NOT NULL,
  `approv_by` varchar(100) NOT NULL,
  `remarks` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `current_status` varchar(50) NOT NULL DEFAULT 'draft',
  `dept_manager_id` int(11) DEFAULT NULL,
  `dept_manager_status` enum('pending','approved','rejected') DEFAULT NULL,
  `dept_manager_note` text DEFAULT NULL,
  `dept_manager_date` datetime DEFAULT NULL,
  `finance_manager_id` int(11) DEFAULT NULL,
  `finance_manager_status` enum('pending','approved','rejected') DEFAULT NULL,
  `finance_manager_note` text DEFAULT NULL,
  `finance_manager_date` datetime DEFAULT NULL,
  `gm_id` int(11) DEFAULT NULL,
  `gm_status` enum('pending','approved','rejected') DEFAULT NULL,
  `gm_note` text DEFAULT NULL,
  `gm_date` datetime DEFAULT NULL,
  `requires_gm_approval` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = Yes, 0 = No',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `smart_request`
--

INSERT INTO `smart_request` (`id`, `inv_no`, `tally_id`, `injazat_id`, `location`, `sub_title`, `sub_type`, `item_name`, `quantity`, `product_price`, `itmvalue`, `vat_rate`, `vat_val`, `amount`, `idiscount`, `total_cost`, `discount`, `emp_id`, `department`, `prep_by`, `approv_by`, `remarks`, `current_status`, `dept_manager_id`, `dept_manager_status`, `dept_manager_note`, `dept_manager_date`, `finance_manager_id`, `finance_manager_status`, `finance_manager_note`, `finance_manager_date`, `gm_id`, `gm_status`, `gm_note`, `gm_date`, `requires_gm_approval`, `created_at`) VALUES
(2, 'SMT54302508263449', '', '', 'Damam - Al-Readah Logistic Warehouse', 'Buy hardware for office', 'New Cash', '/Item Name/Invoice Num.', '1', '500', '500.00', '15', '75.00', '575.00', '0', '575.00', '0', 5430, '6', 'ANEES AFZAL', '', '', 'Paid', 5430, 'approved', NULL, '2025-08-26 16:36:29', 3061, 'approved', '', '2025-08-26 16:37:17', NULL, NULL, NULL, NULL, 0, '2025-08-26 13:37:38'),
(3, 'SMT54302508260641', '', '', 'Al-Mutlak - Head Office', 'Pay Eletric', 'New Cash', 'pay for elec.', '1', '300', '300.00', '15', '45.00', '345.00', '0', '345.00', '0', 5430, '6', 'ANEES AFZAL', '', 'no remarks', 'Paid', 5430, 'approved', NULL, '2025-08-26 19:07:22', 3061, 'approved', '', '2025-08-26 19:08:09', 3928, 'approved', '', '2025-08-26 19:08:27', 1, '2025-08-26 16:08:58'),
(4, 'SMT34312508261108', '', '', 'Amlak - Head Office', 'Iqama Renewal', 'Online Payment', 'Iqama', '1', '650', '650.00', '15', '84.78', '650.00', '0', '650.00', '0', 3431, '5', 'LEANDRO SANTIAGO', '', '', 'Paid', 5408, 'approved', '', '2025-08-26 19:22:14', 4120, 'approved', '', '2025-08-26 19:22:46', 3928, 'approved', '', '2025-08-26 19:23:05', 1, '2025-08-26 16:24:15'),
(5, 'SMT54302508285535', '', '', 'Amlak - Head Office', 'Iqama Renewal', 'Online Payment', 'FAHAD', '1', '650', '650.00', '15', '97.50', '747.50', '0', '747.50', '0', 5430, '6', 'ANEES AFZAL', '', '', 'Paid', 5430, 'approved', NULL, '2025-08-28 14:01:50', 3061, 'approved', '', '2025-08-28 14:03:27', NULL, NULL, NULL, NULL, 0, '2025-08-28 11:04:21'),
(6, 'SMT34312508280444', '', '', 'Amlak - Head Office', 'Pay Eletric', 'Online Payment', 'Description', '1', '600', '600.00', '15', '90.00', '690.00', '0', '690.00', '0', 3431, '5', 'LEANDRO SANTIAGO', '', '', 'approved', 5408, 'approved', '', '2025-08-28 14:11:32', 4120, 'approved', '', '2025-08-28 14:11:59', 3928, 'approved', '', '2025-08-28 14:12:24', 1, '2025-09-01 17:22:13'),
(7, 'SMT34312508280444', '', '', 'Amlak - Head Office', 'Pay Eletric', 'Online Payment', 'Item Name', '1', '1000', '1000.00', '15', '150.00', '1150.00', '0', '1150.00', '0', 3431, '5', 'LEANDRO SANTIAGO', '', '', 'approved', 5408, 'approved', '', '2025-08-28 14:11:32', 4120, 'approved', '', '2025-08-28 14:11:59', 3928, 'approved', '', '2025-08-28 14:12:24', 1, '2025-08-28 11:12:24');

-- --------------------------------------------------------

--
-- Table structure for table `smt_attachment`
--

CREATE TABLE `smt_attachment` (
  `id` int(11) NOT NULL,
  `inv_no` varchar(255) NOT NULL,
  `attachment` varchar(255) NOT NULL,
  `docu_ext` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `smt_attachment`
--

INSERT INTO `smt_attachment` (`id`, `inv_no`, `attachment`, `docu_ext`, `created_at`) VALUES
(1, 'SMT54302508263449', 'SMT54302508263449_bae784ab3664f523dd0b4decc5cf077a.pdf', 'pdf', '2025-08-26 13:35:22'),
(2, 'SMT54302508285535', 'SMT54302508285535_68d5535b971d558f594f10a5affd0a71.jpeg', 'jpeg', '2025-08-28 11:00:13'),
(3, 'SMT54302508285535', 'SMT54302508285535_2fc30606f6646d8dab38bee6e3677f69.pdf', 'pdf', '2025-08-28 11:00:31');

-- --------------------------------------------------------

--
-- Table structure for table `smt_notes`
--

CREATE TABLE `smt_notes` (
  `id` int(11) NOT NULL,
  `inv_no` varchar(255) NOT NULL,
  `emp_id` varchar(100) NOT NULL,
  `emp_name` varchar(255) NOT NULL,
  `note` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `smt_payment`
--

CREATE TABLE `smt_payment` (
  `id` int(11) NOT NULL,
  `inv_no` varchar(255) NOT NULL,
  `paid_amount` decimal(10,2) NOT NULL,
  `payment_invoice` varchar(255) NOT NULL,
  `paid_by_id` int(11) NOT NULL,
  `paid_by_name` varchar(255) NOT NULL,
  `note` text CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `smt_payment`
--

INSERT INTO `smt_payment` (`id`, `inv_no`, `paid_amount`, `payment_invoice`, `paid_by_id`, `paid_by_name`, `note`, `created_at`) VALUES
(1, 'SMT54302508263449', 575.00, 'SMT54302508263449_payment_1756215458.pdf', 0, '', '', '2025-08-26 13:37:38'),
(2, 'SMT54302508260641', 345.00, 'SMT54302508260641_payment_1756224538.pdf', 0, '', '', '2025-08-26 16:08:58'),
(3, 'SMT34312508261108', 650.00, 'SMT34312508261108_payment_1756225455.pdf', 0, '', '', '2025-08-26 16:24:15'),
(4, 'SMT54302508285535', 747.50, 'SMT54302508285535_payment_1756379061.pdf', 3061, 'AHMED SOLIMAN', '', '2025-08-28 11:04:21');

-- --------------------------------------------------------

--
-- Table structure for table `smt_request_status`
--

CREATE TABLE `smt_request_status` (
  `id` int(11) NOT NULL,
  `inv_no` varchar(255) NOT NULL,
  `emp_id` varchar(100) NOT NULL,
  `emp_name` varchar(255) NOT NULL,
  `note` text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `status` varchar(100) NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `smt_request_status`
--

INSERT INTO `smt_request_status` (`id`, `inv_no`, `emp_id`, `emp_name`, `note`, `status`, `created_at`) VALUES
(2, 'SMT54302508263449', '5430', 'ANEES AFZAL', '', 'draft', '2025-08-26 13:35:05'),
(3, 'SMT54302508263449', '5430', 'ANEES AFZAL', 'Submitted by Manager for Finance Approval.', 'pending_finance_approval', '2025-08-26 13:36:29'),
(4, 'SMT54302508263449', '4120', 'GAMAL ABDELRAHMAN', 'Approved by Finance.', 'approved', '2025-08-26 13:37:17'),
(5, 'SMT54302508263449', '', '', 'Payment processed.', 'Paid', '2025-08-26 13:37:38'),
(6, 'SMT54302508260641', '5430', 'ANEES AFZAL', '', 'draft', '2025-08-26 16:07:06'),
(7, 'SMT54302508260641', '5430', 'ANEES AFZAL', 'Submitted by Manager for Finance Approval.', 'pending_finance_approval', '2025-08-26 16:07:22'),
(8, 'SMT54302508260641', '4120', 'GAMAL ABDELRAHMAN', '', 'pending_gm_approval', '2025-08-26 16:08:09'),
(9, 'SMT54302508260641', '3928', 'MAHER JABARI', '', 'approved', '2025-08-26 16:08:27'),
(10, 'SMT54302508260641', '', '', 'Payment processed.', 'Paid', '2025-08-26 16:08:58'),
(11, 'SMT34312508261108', '3431', 'LEANDRO SANTIAGO', '', 'draft', '2025-08-26 16:11:26'),
(12, 'SMT34312508261108', '3431', 'LEANDRO SANTIAGO', 'Submitted for approval.', 'pending_dept_manager_approval', '2025-08-26 16:11:41'),
(13, 'SMT34312508261108', '5408', 'SHARIFAH ALSALHI', '', 'pending_finance_approval', '2025-08-26 16:22:14'),
(14, 'SMT34312508261108', '4120', 'GAMAL ABDELRAHMAN', '', 'pending_gm_approval', '2025-08-26 16:22:46'),
(15, 'SMT34312508261108', '3928', 'MAHER JABARI', '', 'approved', '2025-08-26 16:23:05'),
(16, 'SMT34312508261108', '', '', 'Payment processed.', 'Paid', '2025-08-26 16:24:15'),
(17, 'SMT54302508285535', '5430', 'ANEES AFZAL', '', 'draft', '2025-08-28 10:57:25'),
(18, 'SMT54302508285535', '5430', 'ANEES AFZAL', 'Submitted by Manager for Finance Approval.', 'pending_finance_approval', '2025-08-28 11:01:50'),
(19, 'SMT54302508285535', '4120', 'GAMAL ABDELRAHMAN', 'Approved by Finance.', 'approved', '2025-08-28 11:03:27'),
(20, 'SMT54302508285535', '3061', 'AHMED SOLIMAN', 'Payment processed.', 'Paid', '2025-08-28 11:04:21'),
(21, 'SMT34312508280444', '3431', 'LEANDRO SANTIAGO', '', 'draft', '2025-08-28 11:05:13'),
(22, 'SMT34312508280444', '3431', 'LEANDRO SANTIAGO', 'Submitted for approval.', 'pending_dept_manager_approval', '2025-08-28 11:05:21'),
(23, 'SMT34312508280444', '5408', 'SHARIFAH ALSALHI', '', 'pending_finance_approval', '2025-08-28 11:11:32'),
(24, 'SMT34312508280444', '4120', 'GAMAL ABDELRAHMAN', '', 'pending_gm_approval', '2025-08-28 11:11:59'),
(25, 'SMT34312508280444', '3928', 'MAHER JABARI', '', 'approved', '2025-08-28 11:12:24');

-- --------------------------------------------------------

--
-- Table structure for table `smt_subject_type`
--

CREATE TABLE `smt_subject_type` (
  `id` int(11) NOT NULL,
  `sub_type` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `smt_subject_type`
--

INSERT INTO `smt_subject_type` (`id`, `sub_type`, `created_at`) VALUES
(1, 'Cash with Invoice Submission', '2023-05-01 06:38:35'),
(2, 'New Cash', '2022-06-13 21:10:57'),
(3, 'Cheque Request', '2022-06-13 21:10:57'),
(4, 'Online Payment', '2022-06-13 21:11:25'),
(5, 'Payment Request', '2022-06-13 17:07:40'),
(6, 'Purchase Order', '2022-06-13 17:07:40'),
(7, 'Quotation Submission', '2022-06-13 17:07:40'),
(8, 'Sales Order', '2022-06-13 17:07:40');

-- --------------------------------------------------------

--
-- Table structure for table `sm_request_sr`
--

CREATE TABLE `sm_request_sr` (
  `id` int(10) NOT NULL,
  `sr` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `social`
--

CREATE TABLE `social` (
  `id` int(11) NOT NULL,
  `emp_id` int(11) NOT NULL,
  `social_id` int(11) NOT NULL,
  `s_link` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `social`
--

INSERT INTO `social` (`id`, `emp_id`, `social_id`, `s_link`, `created_at`, `updated_at`) VALUES
(1, 5430, 1, 'in/aneesmug2007', '2025-05-25 11:07:54', '2025-05-25 11:07:59'),
(3, 5430, 3, 'aneesmug2019', '2023-07-20 08:41:27', '2025-05-25 10:52:50'),
(4, 5430, 4, 'aneesmug2007', '2023-07-20 08:46:56', '2025-05-25 10:52:53'),
(5, 5430, 5, 'aneesmug', '2023-07-20 08:47:16', '2025-05-25 10:52:56'),
(6, 5430, 6, 'aneesmug', '2023-07-20 08:49:54', '2025-05-25 10:53:00'),
(7, 5430, 7, 'aneesmug2007', '2023-07-20 08:54:27', '2025-05-25 11:03:17'),
(8, 5430, 8, 'aneesmug2007', '2023-07-20 08:59:41', '2025-05-25 11:03:22'),
(9, 5430, 9, '3197872/anees-mughal', '2023-07-20 09:01:34', '2025-05-25 11:03:26'),
(13, 5430, 2, 'aneesmug2007', '2023-07-30 15:52:17', '2025-05-25 11:03:30'),
(14, 4788, 1, '/in/thamer-mahmood-noor-4a6850159', '2023-07-30 16:22:13', '2023-07-30 09:22:13'),
(15, 10, 1, 'abuveng@yahoo.com', '2023-09-14 19:20:22', '2023-09-14 12:20:22'),
(16, 10, 2, 'abuveng@yahoo.com', '2023-09-14 19:20:39', '2023-09-14 12:20:39');

-- --------------------------------------------------------

--
-- Table structure for table `social_list`
--

CREATE TABLE `social_list` (
  `id` int(11) NOT NULL,
  `sname` text NOT NULL,
  `link` varchar(255) NOT NULL,
  `icon` varchar(100) NOT NULL,
  `color` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `social_list`
--

INSERT INTO `social_list` (`id`, `sname`, `link`, `icon`, `color`, `created_at`) VALUES
(1, 'Linkedin', 'https://linkedin.com/', 'fa fa-linkedin-square', '#0A66C2', '2023-07-26 12:12:39'),
(2, 'Facebook', 'https://facebook.com/', 'fa fa-facebook-square', '#3b5998', '2023-07-26 12:12:46'),
(3, 'Instagram', 'https://Instagram.com/', 'fa fa-instagram', '#F10879', '2023-07-26 12:12:53'),
(4, 'Twitter', 'https://twitter.com/', 'fa fa-twitter-square', '#00acee', '2023-07-26 12:12:56'),
(5, 'Youtube', 'https://youtube.com/', 'fa fa-youtube', '#c4302b', '2023-07-26 12:13:00'),
(6, 'GitHub', 'https://github.com/', 'fa fa-github', '#4078c0', '2023-07-26 12:13:06'),
(7, 'Dribbble', 'https://dribbble.com/', 'fa fa-dribbble', '#ea4c89', '2023-07-26 12:13:13'),
(8, 'Behabce', 'https://behance.net/', 'fa fa-behance-square', '#053eff', '2023-07-26 12:13:18'),
(9, 'Stack Over Flow', 'https://stackoverflow.com/users/', 'fa fa-stack-overflow', '#f48024', '2023-07-26 12:13:25');

-- --------------------------------------------------------

--
-- Table structure for table `sponsorship`
--

CREATE TABLE `sponsorship` (
  `id` int(11) NOT NULL,
  `sponsor` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `sponsorship`
--

INSERT INTO `sponsorship` (`id`, `sponsor`) VALUES
(1, 'TRADE'),
(2, 'METAL'),
(3, 'FILTER'),
(4, 'SPD'),
(5, 'REEFER'),
(6, 'SAN/FA'),
(7, 'ALR/TR'),
(8, 'SHEIKH'),
(9, 'ALR/H');

-- --------------------------------------------------------

--
-- Table structure for table `survey`
--

CREATE TABLE `survey` (
  `id` int(100) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(150) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `mobile` varchar(15) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `age` int(10) NOT NULL,
  `gender` varchar(10) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `location` varchar(50) NOT NULL,
  `question_1` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL COMMENT 'How was the service provided?',
  `add_msg_1` text NOT NULL COMMENT 'How was the service provided?',
  `question_2` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL COMMENT 'Are you satisfied with the quality of the service provided?',
  `add_msg_2` text NOT NULL COMMENT 'Are you satisfied with the quality of the service provided?',
  `question_3` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL COMMENT 'The speed of completion service?',
  `add_msg_3` text NOT NULL COMMENT 'The speed of completion service?',
  `question_4` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL COMMENT 'Was this your first experience with us?',
  `add_msg_4` text NOT NULL COMMENT 'Was this your first experience with us?',
  `question_5` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL COMMENT 'Could your experience be better? If yes. How ?',
  `add_msg_5` text NOT NULL COMMENT 'Could your experience be better? If yes. How ?',
  `add_msg_6` text NOT NULL COMMENT 'What can we do to improve, add or change? What''s your suggestion?',
  `date_reg` varchar(25) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `survey`
--

INSERT INTO `survey` (`id`, `full_name`, `email`, `mobile`, `age`, `gender`, `location`, `question_1`, `add_msg_1`, `question_2`, `add_msg_2`, `question_3`, `add_msg_3`, `question_4`, `add_msg_4`, `question_5`, `add_msg_5`, `add_msg_6`, `date_reg`) VALUES
(1, 'Alaa', 'dr.alaaturkustani@gmail.com', '0546667422', 0, 'Female', 'JM05', 'Very Good', 'ممتاز جدا و السعر ممتاز و مقبول', 'Statified', '', 'Statified', '', 'Yes', '', 'No', '', 'الاستمرارية على نفس المستوى و عدم زيادة الأسعار', '2021-07-07T08:38:57+03:00'),
(2, 'Alaa', 'dr.alaaturkustani@gmail.com', '0546667422', 0, 'Female', 'JM05', 'Very Good', 'ممتاز جدا و السعر ممتاز و مقبول', 'Statified', '', 'Statified', '', 'Yes', '', 'No', '', 'الاستمرارية على نفس المستوى و عدم زيادة الأسعار', '2021-07-07T08:55:50+03:00'),
(3, 'Faisal', 'Faisalbintturkii11@gmail.com', '0560606084', 43, 'Male', 'instagram', 'Very bad', 'فرع ابحر عند المحطه اسم العمال محمد وشيهان الاخلاق والتعامل سيئ جدااا ', 'Not Statified', '', 'Not Statified', '', 'No', 'زبون دائم ولكن نقلت بيت جديد والعمال نفس الاخلاق يوميا ', 'Yes', '', 'تغيير نوع العمال في هذا الفرع لا اتعامل معهم يوميا وانتو شركه كبير ', '2021-07-08T17:39:51+03:00'),
(4, 'Wael alharbi', 'wa6x@hotmail.com', '0560222488', 38, 'Male', 'instagram', 'Very bad', 'ذهبت الى فرع الكورنيش الساعة ١١:٤٨ دقيقة الموظف لم بكن  موجود عند وصولي والفرع مغلق وعند عودته كان تعاملة جدا سيئ ورفض يعطيني قهوة ساخنة وافاد بوجود مشروبات باردة فقط من الجاهزة بحجة التنظيف  واغلق الشباك في وجهي \r\n0560222488', 'Not Statified', '', 'Not Statified', '', 'No', '', 'No', '', '', '2021-07-10T11:53:30+03:00'),
(5, 'Khaled alharbi', 'khal3d-harbi@live.com', '0547779117', 40, 'Male', 'instagram', 'Very bad', 'ارغب في التواصل معي ', 'Not Statified', '', 'Not Statified', '', 'No', '', 'No', '', 'الاتصال علي ', '2021-07-10T19:02:01+03:00'),
(6, 'تركي', 'turki.mously@gmail.com', '0550565668', 30, 'Male', 'JM05', 'Average', 'الرجاء اضافة طلب سبانيش لاتيه حار..\r\nوشكرا', 'Statified', '', 'Statified', '', 'No', '', 'Yes', '', 'الرجاء اضافة سبانيش لاتيه حار', '2021-07-12T14:12:44+03:00'),
(7, 'Loay Aloqbi', 'LoayOq@gmail.com', '0545953539', 18, 'Male', 'instagram', 'Very Good', '', 'Statified', '', 'Statified', '', 'No', '', 'Yes', 'تقليل الأسعار، وافتتاح فرع في المقر الرئيسي لجامعة جدة\r\n\r\nhttps://dsa.uj.edu.sa/Pages-%D8%A7%D8%B9%D9%84%D8%A7%D9%869-%D8%A7%D9%84%D9%85%D9%88%D8%A7%D9%82%D8%B9-%D8%A7%D9%84%D8%A7%D8%B3%D8%AA%D8%AB%D9%85%D8%A7%D8%B1%D9%8A%D8%A9.aspx', 'تقليل الأسعار، وافتتاح فرع في المقر الرئيسي لجامعة جدة\r\n\r\nhttps://dsa.uj.edu.sa/Pages-%D8%A7%D8%B9%D9%84%D8%A7%D9%869-%D8%A7%D9%84%D9%85%D9%88%D8%A7%D9%82%D8%B9-%D8%A7%D9%84%D8%A7%D8%B3%D8%AA%D8%AB%D9%85%D8%A7%D8%B1%D9%8A%D8%A9.aspx', '2021-08-30T03:53:37+03:00'),
(8, 'كنانه عدنان تليتي', 'loushy2007@gmail.com', '0532702110', 39, 'Female', 'JM05', 'Very Good', '', 'Statified', '', 'Statified', '', 'Yes', '', 'No', '', '', '2021-10-03T10:16:36+03:00'),
(9, 'Raneen Ahyad', 'raneenahyad0@gmail.com', '0552289078', 30, 'Female', 'UM01', 'Very Good', 'شكراً للأخذ بالاعتبار اقتراحنا بفتح منطقة الجلوس في مركز الملك فهد', 'Statified', '', 'Statified', '', 'No', '', 'Yes', 'خفض الاسعار ', '', '2021-10-03T10:32:51+03:00'),
(10, 'Aseel Alharbi', 'alharbiaseel2@gmail.com', '0555550916', 0, 'Female', 'JM05', 'Very Good', '', 'Statified', '', 'Statified', '', 'Yes', '', 'No', '', '', '2021-10-11T09:19:00+03:00'),
(11, 'Faisal', 'faisal.shawli@hotmail.com', '0505666780', 0, 'Male', 'twitter', 'Very bad', 'خدمة سيئة والقهوة سيئه', 'Not Statified', '', 'Not Statified', '', 'No', '', 'No', '', 'تدريب الموظفين بشكل ممتاز بالتعامل مع العميل ودقة اتقان القهوة', '2021-10-23T19:20:51+03:00'),
(12, 'Faisal ', 'faisal.shawli@hotmail.com', '0505666780', 0, 'Male', 'twitter', 'Very bad', '', 'Not Statified', '', 'Not Statified', '', 'No', '', 'No', '', '', '2021-10-24T03:15:32+03:00'),
(13, 'Shooq', 'shooq.h6@gmail.com', '0550149788', 23, 'Female', 'UM01', 'Very Good', 'Your croissants are the best ', 'Statified', '', 'Statified', '', 'No', '', 'Yes', '', '', '2021-10-28T12:01:36+03:00'),
(14, 'ا', 'suh@gmail.com', '0549680207', 18, 'Male', 'UM01', 'Very bad', '', 'Statified', '', 'Statified', '', 'Yes', '', 'Yes', '', '', '2021-11-02T12:42:56+03:00'),
(15, 'ا', 'suh@gmail.com', '0549680207', 18, 'Male', 'UM01', 'Very bad', '', 'Statified', '', 'Statified', '', 'Yes', '', 'Yes', '', '', '2021-11-02T13:37:40+03:00'),
(16, 'Ahmad ', 'aalbazzan@gmail.com', '0500660733', 54, 'Male', 'JM05', 'Very Good', '', 'Statified', '', 'Statified', '', 'Yes', '', 'Yes', '', '', '2021-11-07T08:41:38+03:00'),
(17, 'salem', 's.j.j201@gmail.com', '0562416046', 30, 'Male', '', 'Very bad', 'خدمة سيئة من العامل الموجود في فرع البحر جدة فترة الصباح \r\nhttps://goo.gl/maps/Kj1ssatG6PZ1BdkG7 \r\n\r\nيكلم بالجوال ولما اطلبه الطلب معصب مررره ويقول (ايش يبغا .. ايش يبغا ) اسلوب منفر وسيء في التعامل .. وغير متعاون ابدا اطلبه سكر يقول (خلاص خلاص كلو موجود ) انا دافع قيمه طلباتي مو ماخذها شحته منه ', 'Not Statified', '', 'Not Statified', '', 'No', '', 'No', '', '', '2021-11-14T10:20:58+03:00'),
(18, 'غادة ', 'galshikyalshiky@gmail.com', '6541315910', 0, 'Female', 'JM05', 'Average', 'can you avaliable coffee with milk free lactose\r\nالرجاء توفير قهوة بحليب خالي اللاكتوز ', 'Not Statified', '', 'Statified', '', 'No', '', 'Yes', '', '', '2021-11-21T11:16:43+03:00'),
(19, 'Wafaa', 'alznbqiwafaa@gmail.con', '0591666060', 0, 'Female', 'instagram', 'Very bad', 'لو سمحتم طعم الكابتشينو في رائحة بقايا حليب وذلك يدل ع عدم تنظيف مكائن القهوة يوجد منظفات لهذه المكائن لجودة مشروبكم والمنافسة مع المحلات الاخرى  محبتكم وفاء', 'Statified', 'خدمة الموظقين ممتازة المشكلة في مشروباتكم', 'Statified', '', 'No', '', 'Yes', 'اتمنى المرة القادمة مشروباتكم تتحسن ويختفي الطعم السيء', 'نظفو مكائن القهوة بمنظفات خاصة فيها دائما لان الحليب يسبب نكهة غير مستحبة تضر بالجودة', '2022-01-11T18:27:58+03:00'),
(20, 'مستورة علي ', 'maso0o@hotmail.com', '0555882476', 0, 'Female', 'instagram', 'Very bad', 'طلبت كولد شوكلت واعطاني هوت يقول انت قلت هوت ومطلعني غلطانه ورفض انه يعدل طلبي من غير اسلوبة وتعاملة السيئ  تجربة خلتني اكره اشتري من هذا المكان ', 'Not Statified', '', 'Not Statified', '', 'No', '', 'Yes', 'وجود عامل متفاهم و يعوض الزبون عند الغلط في طلبه ', 'وجود عامل متفاهم و يعوض الزبون عند الغلط في طلبه  ', '2022-01-12T08:09:38+03:00'),
(21, 'نزار بانافع', 'tousle11@yahoo.com', '0548212086', 44, 'Male', 'twitter', 'Average', 'السلام عليكم .. يوم الجمعة 14 يناير2022 الساعة 5 و نصف عصرا مريت على محلكم في جدة الواقع على طريق الكورنيش داخل محطة بنزين نفط بالقرب من مستشفى الاطباء المتحدون و طلبت ايس شوكوليت مع كريمة و شوكوليت سوس حجم كبير لكن الموظف ما كان فاهم كلامي و لا الكلام المكتوب في ورقة بالطلب اللي ابغاه .. اعطاني كاسة صغيرة عكس طلبي و بدون الكريمة و لا كان فاهم انا ايش قاعد اقول . رقم الفاتورة 88079', 'Not Statified', 'لم احصل على الطلب المحدد رقم الفاتورة 88079', 'Statified', '', 'No', '', 'No', '', 'موظفين يفهمون اللغتين العربية و الانجليزية و يعرفون الاصناف و الاحجام باللغتين العربية و الانجليزية', '2022-01-15T10:00:25+03:00'),
(22, 'Sadek Medher', 'Sadek_medher@hotmail.com', '0505644030', 0, 'Male', 'JM05', 'Very Good', '', 'Statified', '', 'Statified', '', 'Yes', '', 'Yes', '', '', '2022-01-23T12:16:55+03:00'),
(23, 'Ahmed alzilaei', 'gnc1386@gmail.com', '0568644972', 0, 'Male', 'JM05', 'Very Good', '', 'Statified', '', 'Statified', '', 'No', '', 'Yes', '', 'زيادت  الفروع', '2022-01-26T10:34:43+03:00'),
(24, 'وليد محمد المرزوقي', 'waleedmarzouq2@gmail.com', '0504127407', 29, 'Male', 'instagram', 'Very bad', 'السلام عليكم ورحمة الله وبركاته. \r\nانا وليد المرزوقي اوريد منك التواصل معي لبلاغكم بمشكله في احد فروعكم \r\n ورقم التواصل ٠٥٠٤١٢٧٤٠٧ وشكرا جزيلا ', 'Not Statified', '', 'Not Statified', '', 'No', '', 'No', '', 'التحسن في الموظفين ', '2022-01-28T01:44:13+03:00'),
(25, 'Yoyo', 'proyom90@gmail.com', '0531882833', 20, 'Female', 'instagram', 'Very Good', 'بليز افتحو فرع في الطايف في الحلقة مره نحتاجكم', 'Statified', 'مره ممتاز', 'Statified', '', 'Yes', '', 'Yes', 'اذا جيتو الطايف', 'افتحو فرع في مدينة الطائف ', '2022-02-13T19:27:58+03:00'),
(26, 'Ob', 'owb20006@hotmail.com', '0899888888', 0, 'Male', 'twitter', 'Very Good', '', 'Statified', '', 'Statified', '', 'No', '', 'No', '', 'تحسين فرع جامعة الملك عبدالعزيز المشروبات هناك سيئة', '2022-02-16T13:44:27+03:00'),
(27, 'فاعل خير', 'zgzgzgzg@hotmail.com', '0557210775', 0, 'Male', 'UM01', 'Very Good', 'الموظفة جميلة 7/03/2022 9:56', 'Statified', '', 'Statified', '', 'Yes', '', 'Yes', '', '', '2022-03-07T10:02:10+03:00'),
(28, 'فاعل خير', 'zgzgzgzg@hotmail.com', '0557210775', 0, 'Male', 'UM01', 'Very Good', 'الموظفة جميلة 7/03/2022 9:56', 'Statified', '', 'Statified', '', 'Yes', '', 'Yes', '', '', '2022-03-07T12:31:36+03:00'),
(29, 'عمر احمد ', 'omar.ahmad507@gmail.com', '0537244659', 24, 'Male', 'JM05', 'Very bad', '', 'Not Statified', '', 'Not Statified', '', 'Yes', '', 'No', '', '', '2022-03-24T09:23:36+03:00'),
(30, 'Um saod ', 'tota_mofi@hotmail.com', '0565056828', 0, 'Female', 'instagram', 'Very bad', '', 'Statified', '', 'Not Statified', '', 'No', '', 'No', '', '', '2022-03-24T16:21:43+03:00'),
(31, 'Aysha Alzahrani', 'ashly.ay@hotmail.com', '9665069522', 30, 'Female', 'instagram', 'Very bad', 'طلبت قهوة من موكاتشينو ينبع الصناعيه فرع الي عند المسرح الروماني العامل كان جدا سي و حرامي مو راضي يرجع ليا باقي المبلغ حقي و الموكاتشينو سي جدا جددددا 0505952205', 'Not Statified', '', 'Not Statified', '', 'Yes', '', 'No', '', 'حسن التعامل مع العملاء', '2022-05-05T16:30:41+03:00'),
(32, 'لجين مقدم', 'l-o-j-e@hotmail.com', '0568300854', 23, 'Female', 'JM05', 'Good', 'الاسعار مبالغ فيها بشكل كبير كاننو كوفي شوب مع انو مافي كراسي ولا اتجهيز غير تيك اوي يعني المفروض السعر اقل من كدة', 'Statified', 'الاكل اتسخن بس اخدتو دافئ', 'Statified', '', 'No', '', 'Yes', 'وضع مكائن تسخين زيادة \r\nتقليل الاسعار\r\nتوفير كرواسون ', '', '2022-05-31T13:08:51+03:00'),
(33, 'Rakan', 'almahyawir@hotmail.com', '0502507490', 0, 'Male', 'twitter', 'Very bad', '', 'Not Statified', '', 'Not Statified', '', 'No', '', 'Yes', '', '', '2022-06-22T22:03:52+03:00'),
(34, 'A', 'a@gmail.com', '0546585248', 0, 'Male', 'JM05', 'Very Good', '', 'Statified', '', 'Statified', '', 'Yes', '', 'No', '', '', '2022-06-26T09:47:52+03:00'),
(35, 'Mohammed', 'mohamadbm2020@gmail.com', '0550602130', 22, 'Male', 'instagram', 'Bad', ' أنا طلبت وايت موكا وسبانش لاتيه وكان فيه مرارة والطعم ليس بجيد فقط اللون يختلف \r\n\r\nأرجو تغيير جودة القهوة الباردة ولم أستلم فاتورة على الطلب', 'Not Statified', '', 'Statified', '', 'No', '', 'Yes', 'تحسين جودة القهوة الباردة ', 'إعادة النظر في نوع المنتجات المستخدمة في القهوة الباردة حيث لم أجد نكهة مثل بقية المحلات المنافسة التي تصنع \r\n قهوة باردة ', '2022-07-01T15:09:51+03:00'),
(36, 'Shareefa', '11shareefa@gmail.com', '0560605774', 40, 'Female', 'twitter', 'Very Good', 'الاسعار في الفروع في جدة متغيرة كل فرع يحط سعر على كيفه ولكن افضل فرع فرع الكورنيش واجهة جدة خصوصا العامل في المساء ', 'Statified', 'ارجو توحيد الاسعار في الفروع في جدة كل فرع له سعر ولكن افضل الفروع جدة الكورنيش واجهة جدة وخصوصا عامل المساء جدا ممتاز واسلوبه حلو ', 'Statified', 'ارجو توحيد الاسعار في الفروع في جدة كل فرع له سعر ولكن افضل الفروع جدة الكورنيش واجهة جدة وخصوصا عامل المساء جدا ممتاز واسلوبه حلو ', 'No', 'ارجو توحيد الاسعار في الفروع في جدة كل فرع له سعر ولكن افضل الفروع جدة الكورنيش واجهة جدة وخصوصا عامل المساء جدا ممتاز واسلوبه حلو ', 'Yes', 'ارجو توحيد الاسعار في الفروع في جدة كل فرع له سعر ولكن افضل الفروع جدة الكورنيش واجهة جدة وخصوصا عامل المساء جدا ممتاز واسلوبه حلو ', 'ارجو توحيد الاسعار في الفروع في جدة كل فرع  نريد كتابة الاسعار وتسليم الطلب بفواتير له سعر ولكن افضل الفروع جدة الكورنيش واجهة جدة وخصوصا عامل المساء جدا ممتاز واسلوبه حلو ', '2022-07-01T23:14:04+03:00'),
(43, 'غاده الشايع', 'gado121212@gmail.com', '0503503018', 0, 'Female', 'instagram', 'Very bad', 'السلام عليكم معاكم غاده الشايع ذهبت الى فرعكم في وادي نعمان بمكه وتفاجات بسوء معاملة الموظف لديكم كان طلبي موكا حار وقام بتقديم نصف كوب وليس بكامل وافاد في حال رغبتكم بكوب كامل ادفع ريال زياده باسلوب مستفز جدا ايقن انه لايمثل شركتكم الموقره اليووم الخميس 13 اكتوبر 2022م رقم التواصل 0503503018', 'Not Statified', 'سوء المعامله وتعامل الموظف الغير جيد ', 'Not Statified', '', 'No', '', 'Yes', 'اذا كان هناك تقدير عالي جدا للعميل ', 'التاكد من تميز الموظف ورحابة صدره وانتمائه للمنشأه', '2022-10-13T17:24:40+03:00'),
(44, 'shahad', 'shaddd15@hotmail.com', '0566020882', 19, 'Female', 'instagram', 'Good', '', 'Statified', '', 'Statified', '', 'No', '', 'Yes', '', '', '2022-10-20T02:50:06+03:00'),
(45, 'Muhannad Aburuzayzah', 'maburuzayzah@gmail.com', '0096650266', 38, 'Male', 'twitter', 'Good', 'الموظف رفض اعطائي مصاص وعند سؤاله عن السبب افادني بان هذه توجيهات الشركة\r\nسؤالي هل هذا صحيح وهل اعطاء الزبون مصاص اضافي يؤثر على ميزانية موكاتشينو', 'Statified', '', 'Statified', '', 'No', '', 'Yes', '', '', '2022-10-27T21:31:25+03:00'),
(46, 'Rafat Babouli ', 'rafat.babouli@hotmail.com', '0557770068', 0, 'Male', 'instagram', 'Very Good', 'I am loyal client for mochachino in jeddah since 20 years, I had to move to  riyadh 2 years back ever since then deprived from my favorite macchiato ???? any plans to open in riyadh ?', 'Statified', '', 'Statified', '', 'No', '', 'No', '', 'To open in riyadh please ', '2022-11-25T09:37:53+03:00'),
(47, 'شيماء ', 'shim7140@gmail.com', '0537523780', 21, 'Female', 'instagram', 'Average', '..', 'Not Statified', '', 'Statified', '', 'No', '', 'Yes', 'بالاهتمام بهذا الفرع ', 'طعم القهوة في فرع جامعة جدة مرا سئ \r\n ديكورات الكافي جداً كئيبة ولا يشغلوا اللمبات وحرام مساحة الكافي تساعد ع الاهتمام بجودة الفرع وديكوراته وخصوصاً انكم فموقع مهم ونحنا الطالبات بحاجه مكان مريح وطعم قهوة ممتاز ', '2022-11-25T11:16:03+03:00'),
(48, 'Abdullah Alsuliman', 'abdullah_299@hotmail.com', '0555953097', 0, 'Male', 'twitter', 'Very bad', 'آلسلام عليكمَ ورحمة الله وبركاته \r\nأتمنى التواصل معي هاتفيا لطلب كميات من المغلفات او تزويدي برقم المبيعات لانني تواصلت على حساب الانستقرام و التويتر ولم يتم الرد علي عاجلا', 'Not Statified', '', 'Not Statified', '', 'Yes', '', 'Yes', 'التواصل معي او تزويدي برقم للمبيعات', 'ان يكون هناك خدمة عملاء ورد سريع للاستفسارات', '2022-12-05T09:23:07+03:00'),
(49, 'ahmad dabbas', 'abolona1@gmail.com', '0596905337', 0, 'Female', 'twitter', 'Very bad', 'وجدة دودة في القهوة قبل انتهاء الكوب وهي تتحرك داخل الكوب وصورتها فيديو للاثبات', 'Not Statified', '', 'Not Statified', '', 'No', '', 'No', '', '', '2022-12-15T17:51:16+03:00'),
(50, 'عاليه ', 'alya1433@hotmail.com', '0543990921', 40, 'Female', 'instagram', 'Very bad', 'اشتريت كيك ماڤن وطلع خربان الرجاء التواصل او سيتم رفع شكوى لوزارة التجاره رقم الجوال ٠٥٤٣٩٩٠٩٢١ ', 'Not Statified', '', 'Not Statified', '', 'No', '', 'No', '', 'تم شراء كيك ماڤن وطلع خربان الرجاء التواصل معي ٠٥٤٣٩٩٠٩٢١', '2022-12-19T21:15:33+03:00'),
(51, 'Layla ', 'mohd.lkd@hotmail.com', '0540487076', 0, 'Female', 'instagram', 'Very bad', 'اعطني كيك موفن معفن فين اقدم الشكوى ', 'Not Statified', 'اعطني كيك مفن معن ', 'Not Statified', '', 'No', '', 'No', '', 'طريقه التواصل وعمل شكوي ع اعطي مفن معن', '2022-12-27T08:21:12+03:00'),
(52, 'ايمن ', 'bamufleh.am@hotmail.com', '0553630588', 40, 'Male', 'instagram', 'Very bad', 'اسوء كابتشينو ضقتها جدا جدا سيئه ', 'Not Statified', 'القهوه سيئه جدا', 'Statified', '', 'No', '', 'Yes', 'اذا الموظف يزبط الكابتشينو ', 'تحسين في عمل القهوه ', '2023-01-02T08:37:40+03:00'),
(53, 'Manal', 'mafq20@gmail.com', '0558261006', 48, 'Female', 'twitter', 'Very Good', '', 'Statified', '', 'Statified', '', 'Yes', '', 'Yes', '', '', '2023-01-06T10:19:16+03:00'),
(54, 'Jebali Manel', 'maneljeblai@gmail.com', '0591574633', 27, 'Female', 'instagram', 'Very Good', '', 'Statified', '', 'Statified', '', 'Yes', '', 'Yes', '', '', '2023-01-09T22:36:32+03:00'),
(55, 'أنس الحربي ', 'baaxis@icloud.com', '0547420450', 0, 'Male', 'twitter', 'Very bad', 'العامل ما يفهم شي في القهوة ، حاطلي كيلو سكر وانا قايله سويت كريم بس وحطّلي نص الكوب سكر يعني والله ماني عارف الين بتوصلون كم مرة اطلب قهوة من عندكم وتكون القهوة جداً سيئة ', 'Not Statified', '', 'Statified', '', 'No', '', 'No', '', '', '2023-01-11T18:38:58+03:00'),
(56, 'Maram', 'mar00m87@hotmail.com', '0543670684', 30, 'Female', 'instagram', 'Very Good', 'سلام ???? انا عميلتكم من ٦ سنين يوميًا قهوة الدوام من عندكم ليه ماعندكم تقدير للعملاء وعضوية ومكافآت فكروا شوي ', 'Statified', '', 'Statified', '', 'No', '', 'Yes', '', 'سلام ???? انا عميلتكم من ٦ سنين يوميًا قهوة الدوام من عندكم ليه ماعندكم تقدير للعملاء وعضوية ومكافآت فكروا شوي ', '2023-01-12T15:41:57+03:00'),
(57, 'رؤى', 'rere15.5@hotmail.com', '0593235820', 30, 'Female', 'instagram', 'Very Good', '', 'Statified', '', 'Statified', '', 'No', '', 'Yes', '', '', '2023-01-24T04:15:41+03:00'),
(58, 'خلود الدوسري', 'kaldossari1982@hotmail.com', '0504134697', 0, 'Female', 'instagram', 'Very Good', 'ابي اطلب كرتون الاظرف  للرياض ', 'Statified', '', 'Statified', '', 'No', '', 'Yes', 'منذ فترة انتظر الاظرف تصل للرياض ', 'اريد توفير كرتون الاظرف للرياض ', '2023-01-29T21:05:13+03:00'),
(59, 'Basma ', 'shuaibi50@gmail.com', '9689395880', 0, 'Female', 'instagram', 'Good', '', 'Statified', '', 'Statified', '', 'Yes', '', 'Yes', '', '', '2023-02-13T08:57:20+03:00'),
(60, 'Alfaraz ansari', 'alfarazansari94@gmail.com', '7617843033', 34, 'Male', 'instagram', 'Very Good', 'Delicious ???? taste ???? ', 'Statified', '', 'Statified', '', 'Yes', '', 'Yes', '', '', '2023-02-20T16:55:21+03:00'),
(61, 'يحيى الشهري ', 'e221@hotmail.com', '0507386863', 0, 'Male', 'twitter', 'Very bad', 'خدمة عملاء سئيه وحسابات تويتر لا احد يقوم برد ', 'Not Statified', '', 'Not Statified', '', 'No', '', 'No', '', 'خدمة عملاء حسابات شبه وهميه لااحد يقوم برد ', '2023-02-22T14:57:34+03:00'),
(62, 'Ahmed ', 'drhelas2@gmail.com', '0532182255', 0, 'Male', 'instagram', 'Very Good', 'اليوم الجمعة اشتريت امريكانو كبير من موكاتشينو حي البوادي طريق المدينة عند محطة نفط-ميد.\r\nللأسف الكوب كان غير لائف؛ الموظف قال لي هذا اخر كوب ولا استطيع تغييره؛\r\nلدي صورة من الكوب', 'Statified', '', 'Statified', '', 'No', 'اليوم الجمعة اشتريت امريكانو كبير من موكاتشينو حي البوادي طريق المدينة عند محطة نفط-ميد.\r\nللأسف الكوب كان غير لائف؛ الموظف قال لي هذا اخر كوب ولا استطيع تغييره؛\r\nلدي صورة من الكوب', 'Yes', 'اليوم الجمعة اشتريت امريكانو كبير من موكاتشينو حي البوادي طريق المدينة عند محطة نفط-ميد.\r\nللأسف الكوب كان غير لائف؛ الموظف قال لي هذا اخر كوب ولا استطيع تغييره؛\r\nلدي صورة من الكوب', 'اليوم الجمعة اشتريت امريكانو كبير من موكاتشينو حي البوادي طريق المدينة عند محطة نفط-ميد.\r\nللأسف الكوب كان غير لائف؛ الموظف قال لي هذا اخر كوب ولا استطيع تغييره؛\r\nلدي صورة من الكوب', '2023-03-10T23:30:53+03:00'),
(63, 'Bader', 'bader.gamdi@hotmail.com', '0599626000', 0, 'Male', 'instagram', 'Very bad', '0599626000\r\n\r\nارجوا التواصل للاهميه ', 'Not Statified', '', 'Not Statified', '', 'No', '', 'No', '', 'ارجوا التواصل ', '2023-03-27T03:57:47+03:00'),
(64, 'Yones', 'yoo.8@hotmail.com', '0540078149', 0, 'Male', 'twitter', 'Very Good', '', 'Statified', '', 'Statified', '', 'Yes', '', 'Yes', '', 'A', '2023-03-31T09:07:18+03:00'),
(65, 'yazeed ghurm abdullah ', 'yazeedzhrani14@gmail.com', '0593019044', 28, 'Female', 'instagram', 'Very Good', '', 'Statified', '', 'Statified', '', 'No', '', 'Yes', '', '', '2023-04-28T04:46:15+03:00'),
(66, 'علي العواجي', 'ali_alawagi@hotmail.com', '0501771223', 40, 'Female', 'تويتر', 'Very bad', 'اسوأ خدمة اتلقاها من سنين.. بسب موظف سيء/ جدة حي الرحاب/ الخط السريع عند محطة الدريس وباسكن روبنز.. الجمعة ٨ مساء', 'Not Statified', '', 'Not Statified', '', 'No', 'زبون قديم', 'Yes', 'أخلاق العامل مهمة ', 'حسن تعامل العاملين', '2023-05-05T21:05:26+03:00'),
(67, 'Ali', '?Ali753884@gmail.com', '0504569227', 0, 'Male', 'instagram', 'Good', '', 'Statified', '', 'Statified', '', 'Yes', '', 'Yes', '', '', '2023-05-06T05:11:22+03:00'),
(68, 'سعاد', 'soaad.m.almadani@gmail.com', '0590006850', 30, 'Female', 'twitter', 'Very Good', '', 'Statified', '', 'Statified', '', 'Yes', '', 'Yes', '', '', '2023-05-31T14:42:21+03:00'),
(69, 'تركي سيف', 'tstb@hotmail.com', '0509069633', 0, 'Male', 'instagram', 'Very bad', 'عدد ايام ارسل لطلب المنتج ولا يوجد رد', 'Not Statified', '', 'Not Statified', '', 'Yes', '', 'No', '', 'ارجو ارد ع الرسايل', '2023-06-18T00:39:26+03:00'),
(70, 'ali', 'p3mcs3@gmail.com', '0114934943', 0, 'Male', 'instagram', 'Very bad', 'نحتاج كميات من القهوة ومندوبكم من عدة اشهر\r\nيقول غير متوفرة!!!\r\nفهل معقول من مدة طويلة انتم متوقفين عن النشاط؟\r\nامل الافادة وشكرا\r\np3mcs3@gmail. com', 'Not Statified', '', 'Not Statified', '', 'No', '', 'Yes', 'وضع مندوب يوفر منتجاتكم', '', '2023-07-09T11:00:02+03:00'),
(71, 'wejdan', 'wejdan.77x@gmail.com', '0554997420', 24, 'Female', 'instagram', 'Very bad', 'لقيت ذبانه ف مشروبي ????????????????????', 'Not Statified', '', 'Statified', '', 'No', '', 'No', '', '', '2023-07-18T16:58:46+03:00'),
(72, 'محمد جوهرجي', 'moaljo7@gmail.com', '0555563242', 35, 'Male', 'instagram', 'Very bad', 'كشك محطه الميزان دائما لا يوجد به عامل بطريق مكه جده ', 'Not Statified', '', 'Not Statified', '', 'No', '', 'No', '', '', '2023-07-20T17:49:03+03:00'),
(73, 'Asrar', 'asrarbas114@gmail.com', '0569622481', 0, 'Female', 'instagram', 'Very Good', '', 'Statified', '', 'Statified', '', 'No', '', 'Yes', '', 'تغيير حجم الاكواب بحيث يكون كوب الامريكانو بـ ٧ ريال لحجم الـ ٩ اونصه والـ ١٣ اونصه بـ ١٦ ', '2023-07-29T23:45:01+03:00'),
(74, 'Nasser alshmrani', 'nasser.va41@gmail.com', '0564681092', 27, 'Male', 'instagram', 'Very bad', '', 'Not Statified', '', 'Not Statified', '', 'No', '', 'No', '', 'انا من العملاء الي اخذ قهوتي الفرنسيه من عندكم فقط هذي المره انصدمت بوجود حشره داخل القهوه  ', '2023-08-15T11:42:35+03:00'),
(75, 'مبارك هيثم باهيثم ', 'yxop3005@gmail.com', '0503225543', 0, 'Male', 'instagram', 'Very Good', '', 'Statified', '', 'Statified', '', 'Yes', '', 'Yes', '', '', '2023-08-29T12:18:54+03:00'),
(76, 'زكريا علي القحطاني ', 'zaky1387@gmail.com', '0555721088', 0, 'Male', 'instagram', 'Very bad', 'للمرة الثانية امر على موكاتشينو اللي بعد محطة الرحيلي الساعة 1:20 ظهرا يوم الجمعة 8-9-2023 واقول للعامل ابغى 2 موكاتشينو مضبوط بدون حليب ويعمل لي بدون سكر اخد امرين يا يكون علمتوه ان مضبوط منزغير سكر يا انه يبغى يخرب السمعة المشكلة ما كان فيه خط رجعة ولو فيه كان رديتها له واخذت فلوسي  استغفر الله للتأكيد هذا رقم جوالي 0555721088', 'Not Statified', '', 'Not Statified', '', 'No', '', 'No', '', 'تغيير العامل اللي في محطة الميزان طرقدمكة السريع', '2023-09-08T16:35:58+03:00'),
(77, 'جهان ', 'jooj7036@gmail.com', '0542112116', 19, 'Male', 'instagram', 'Very Good', '', 'Statified', '', 'Statified', '', 'Yes', '', 'Yes', '', 'احتاج وظيفه اتوصوا معي الله يسعدكم 0542112116', '2023-09-25T03:25:01+03:00'),
(78, 'ايمان ', 'm8080963@gmail.com', '0590486604', 0, 'Female', 'instagram', 'Very bad', 'موكاتشينو فرع جامعه الملك عبدالعزيز شطر الطالبات جدددا سيئ \r\n   القهوه غير موزونه ابدا يجيني سبانش لاتيه طعمها حليب مافي نكهه قهوه ومرات مافي سكر ومرات حاليه بزياده ', 'Not Statified', '', 'Not Statified', '', 'No', '', 'Yes', 'اتمنى تدريب موظفات شطر الطالبات ', 'قهوه نكهه اقوى  ووزنيات مضبوطه', '2023-09-26T23:42:47+03:00'),
(79, 'Enas salman', 'enalharbi5@hotmail.com', '0550409862', 0, 'Female', 'twitter', 'Very bad', 'اليوم زرت فرعكم اللي في السليمانيه عند بوابة جامعة الملك عبدالعزيز وكان العامل مو ملتزم ب اجراءات النظافه كان مو لابس قلفز و غطاء للشعر', 'Not Statified', '', 'Not Statified', 'تأخر ', 'No', 'دايم الموظفين اللي قبل هذا افضل منه بكثير اما اللي موجود حاليا سيء وغير نظيف', 'No', '', 'ارجو عمل اللازم بحق هذا الموظف الغير ملتزم بالنظافه !!', '2023-09-29T08:38:35+03:00'),
(80, 'Nuha', 'nitti1431@gmail.com', '0500383873', 0, 'Female', 'instagram', 'Very Good', '', 'Statified', '', 'Statified', '', 'Yes', '', 'Yes', '', '', '2023-10-01T21:24:46+03:00'),
(81, 'Afnan', 'funosha@hotmail.com', '0503558788', 27, 'Female', 'instagram', 'Very bad', 'موكاتشينو فرع جامعة الملك عبدالعزيز داخل شطر الطالبات الموظفات يداموا من ٧ ولايفتحوا الا قبل ٨ بشوي يعني كل اللي بيشتري قهوة الصباح يكنسل فكرته لان مافيه وقت ياخذ ويلحق المحاضرة الاولى علما ان الموظفات نشوفهم داخل وجالسين يسولفوا والالات جاهزة ! يعني لو اللي يطلع من الفرع ربح ٧٠ بالمية لو فتح من ٧ ونص او ربع يطلع دبل الربح ومنها نستفيد كطالبات ! ', 'Not Statified', '', 'Not Statified', 'بطيئة جدا ', 'No', '', 'Yes', 'اذا فتح الفرع ٧ وربع او نص والموظفات يكونوا اسرع في الخدمة مو معقولة استلم الكرسون قبل القهوه ب ١٠ دقايق ويبرد الكرسون وانا م استلمت القهوة واحيانا اكثر من ١٠ دقايق كل موظفة تنتظر الثانية تتلم شغلها ', '', '2023-10-03T07:45:28+03:00'),
(82, 'المعتز', 'almharthi@gmail.com', '0550055113', 0, 'Male', 'instagram', 'Very bad', '0550055113 من انحدار لانحدار كنتو من الافضل الان من الاسوء عميل من بداياتكم والان اسمحو لي ان اقول مبروك الاغلاق  والخروج من السوق', 'Not Statified', '', 'Not Statified', '', 'No', '', 'Yes', '', 'حافضو على الموظفين القداما ولا تنزلو موضفين جدد حتى يجتازو كل اختبارات التحضير ', '2023-10-08T18:52:11+03:00'),
(83, 'Mahmoud masoud', 'mahmoud.masoud.201044@gmail.com', '0592724343', 33, 'Male', 'instagram', 'Very Good', 'السلام عليكم ورحمه الله وبركاته \r\nمعك مندوب الشركة الغربيه للتغليف \r\nكنت محتاج رقم مسئول المشتريات او مكان مقر الشركة \r\nوشكرًا جزيلًا ', 'Statified', 'السلام عليكم ورحمه الله وبركاته \r\nمعك مندوب الشركة الغربيه للتغليف \r\nكنت محتاج رقم مسئول المشتريات او مقر الشركة للتواصل \r\nوشكرًا جزيلًا ', 'Statified', '', 'Yes', '', 'Yes', '', '.', '2023-10-11T10:37:42+03:00'),
(84, 'حنان نجار', 'm9najjar@gmail.com', '0558001334', 0, 'Female', 'twitter', 'Average', 'اخذت باوند كيك من الكوفي حقكم في المجمع طعمها فيه عفن ????????????', 'Not Statified', '', 'Statified', '', 'No', '', 'Yes', '', 'الاهتمام بشكوتي عن الباوند كيك', '2023-10-17T16:29:27+03:00'),
(85, 'Hind', 'hs-8888@hotmail.com', '0506774664', 0, 'Female', 'instagram', 'Very bad', '', 'Not Statified', '', 'Not Statified', '', 'No', '', 'No', '', 'عندي شكوى بخصوص الفرح اللي بجامعة الملك عبدالعزيز ', '2023-10-23T10:57:27+03:00'),
(86, 'Rana', 'ranaj2003@hotmail.com', '0554768811', 0, 'Female', 'instagram', 'Very bad', 'اليوم طلبت موكاتشينو من فرع حراء وميل علبه التقديم وانا بأخذ القهوى طاحت في الشارع واتكبت جاه يسويلي قهوى تانية يب يدفعني فلوس الاثنين مارضيت اخذها زعق وقلي انا دحين نازلك يبغى يتهجم عليادا مع العلم انع من اسؤ الاشخاص الي قابلتهم في الموكاتشينو لا اخلاق ولا قوه عدله ملاحظه انا زبونه لكم من ٢٠ سنه يومينا اخذ كوب صباحي اعدل مزاجي ارجو تغير الموظف لسوء معاملته ', 'Not Statified', '', 'Not Statified', '', 'No', 'التجربه المليون اعشق قهوتكم ', 'No', ' المليون اعشق قهوتكمالمرة', 'تغير موظف فرع حراء الصباح غير جيد اخلاقا ولا تعاملا والقهوى سئه', '2023-11-06T12:38:29+03:00'),
(87, 'Aryam ', 'aryamalnasri22@gmail.com', '0553620462', 19, 'Female', 'instagram', 'Very Good', '', 'Statified', '', 'Statified', '', 'Yes', '', 'Yes', '', 'السلام عليكم ورحمة الله وبركاته \r\nحابه اقترح عليكم  ف فرع جامعة الملك عبدالعزيز مبنى التحضيري\r\nانكم تخلو يفتح في فترة المساء وانا لو وافقتو على الاقتراح مستعده اداوم ف الفتره المسائي ونتبادل الخبرات والفوائد وان شاء الله يكون اقتراح ينال اعجابكم \r\nودي ارسل سيرتي الذاتيه هنا بس مافي خانه لو ناسبكم الاقتراح هاذا رقمي \r\n0553620462', '2023-11-08T05:14:44+03:00'),
(88, 'Ruba', 'rubashafi@gmail.com', '0540888998', 25, 'Female', 'instagram', 'Very bad', 'طلبت ايس كابتشينو وجاني طعمو دواء تنظيف!!', 'Not Statified', '', 'Statified', '', 'Yes', '', 'No', '', '', '2023-11-27T20:40:12+03:00'),
(89, 'امتنان احمد عقيلي', 'emt16emt@icloud.com', '0545594138', 28, 'Female', 'instagram', 'Very bad', 'رحت فرع مستشفى الملك عبدالله وطلبت موكا بارد اعطاني شوكلت بس بدون قهوه عساس المكينه خربانه ودحين طلبت من فرع ثاني في الرحيلي  فيه طعم الموكا كلها فيه طعم مدري ايش سببه كانه فيه شي بايت او شي  ', 'Not Statified', 'مره سي', 'Not Statified', 'سي', 'No', '', 'Yes', 'اذا رجعتوا زي زمان', 'جودة الشغل ', '2023-11-30T17:17:28+03:00'),
(90, 'عبدالرحيم عبدالله باهرمز', 'A.bahurmoz@kafu.com.sa', '0505550310', 45, 'Male', 'twitter', 'Very Good', '', 'Statified', '', 'Statified', '', 'Yes', '', 'No', '', 'ارجو الاتصال على رقم لعرض موقع منيز لكم 0505550310 عبدالرحيم باهرمز', '2023-12-19T11:11:49+03:00'),
(91, 'اسيل سعد ', 'wasanalgamdi557@gmail.com', '0509509944', 21, 'Female', 'instagram', 'Very bad', 'طلبت ساندوتش من فرع جامعه الملك عبد العزيز تحضيري قلت للكاشير لا بغيره لان حسبته دجاج طلع ديك رومي قالت لي لا ما يتغير قلت لها طيب انا ما اكلته! قالت خلاص طلعت الفاتوره الساندىتش ب ١٨ وانا جيعانه ما اكلته! ', 'Not Statified', 'ابغا تعويض', 'Not Statified', '', 'No', '', 'Yes', 'اذا كانت متفهمه وغيرته لي لاني جيعانه ولا اكل ديك رومي', 'اني اقدر اغيره دامني ما اكلته! ', '2023-12-20T14:50:27+03:00'),
(92, 'MURUJ JIZANI', 'moroj.jizani@gmail.com', '0597281962', 26, 'Female', 'twitter', 'Very bad', 'طلبت ايس كابتشينو من فرع مستشفى الملك عبدالله وطلع الحليب خربان ومحمض جداً سيء وانا مو اول مرا اخدو !!', 'Statified', '', 'Statified', '', 'Yes', '', 'Yes', '', 'فرع مستشفى الملك عبدالله جداً سيء', '2024-01-09T11:22:16+03:00'),
(93, 'Mona Ahmed', 'mreviewstuff@gmail.com', '0546881900', 0, 'Female', 'instagram', 'Very bad', 'The coffee tastes like Turkish coffee no matter what you order. ', 'Not Statified', '', 'Statified', '', 'No', '', 'Yes', 'Change your cappuccino recipe and beans', 'The barista must make sure he’s using the right coffee beans for each order', '2024-01-16T10:47:19+03:00'),
(94, 'Fai AlOhali ', 'falouhali@gmail.com', '0553000075', 0, 'Female', 'instagram', 'Very Good', 'لو سمحتوا موكاتشينو فرع جامعة جدة القسم النسائي في الفيصلية \r\nالقرفة البودرة لها مغترة مخلصة و إلى الآن ما توفرت ممكن توفروها  وشكراً ', 'Statified', '', 'Statified', '', 'No', '', 'No', 'صراحة الموظفات جداً رائعات و خلوقات و سريعات أنا تقريباً يومياً   أخذ قهوتي من هذا الفرع حق جامعة جدة بس ناقص القرفة البودرة ويا ليت ترجعوا الكروسون الأول كان أبد من النوع الجديد و ساندوتش التركي مليان مايونيز فيا ليت توفروا تشكيلة أكثر من السندوتشات و السلطات و أنواع حلى أكثر و شكولاتات ', 'تنويع الأصناف و زيادة أصناف ', '2024-02-01T13:39:21+03:00');

-- --------------------------------------------------------

--
-- Table structure for table `translations`
--

CREATE TABLE `translations` (
  `translation_id` int(11) NOT NULL,
  `lang_key` varchar(255) NOT NULL COMMENT 'The unique identifier for a string, e.g., "user_management_title"',
  `lang_code` varchar(5) NOT NULL COMMENT 'The language code, e.g., "en" or "ar"',
  `translation` text NOT NULL COMMENT 'The translated text for the given key and language'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `translations`
--

INSERT INTO `translations` (`translation_id`, `lang_key`, `lang_code`, `translation`) VALUES
(1, 'add_language', 'en', 'Add Language'),
(2, 'add_language', 'ar', 'إضافة لغة'),
(3, 'update_translation_title', 'en', 'Update Translation'),
(4, 'update_translation_title', 'ar', 'تحديث الترجمة'),
(5, 'are_you_sure', 'en', 'Are you sure?'),
(6, 'are_you_sure', 'ar', 'هل أنت متأكد؟'),
(7, 'add_new_translation', 'en', 'Add New Translation'),
(8, 'add_new_translation', 'ar', 'إضافة ترجمة جديدة'),
(9, 'add_new_translation_desc', 'en', 'Add new translation descriptions'),
(10, 'add_new_translation_desc', 'ar', 'إضافة أوصاف ترجمة جديدة'),
(11, 'language_key', 'en', 'Language Key'),
(12, 'language_key', 'ar', 'مفتاح اللغة'),
(13, 'arabic_translation', 'en', 'Arabic Translation'),
(14, 'arabic_translation', 'ar', 'الترجمة العربية'),
(15, 'table_header', 'en', 'Table Header'),
(16, 'table_header', 'ar', 'رأس الجدول'),
(17, 'english_translation', 'en', 'English translation'),
(18, 'english_translation', 'ar', 'الترجمة الانجليزية'),
(19, 'all_translations', 'en', 'All Translations'),
(20, 'all_translations', 'ar', 'جميع الترجمات'),
(21, 'translation_management_page_title', 'en', 'Translation Management'),
(22, 'translation_management_page_title', 'ar', 'إدارة الترجمة'),
(23, 'table_header_english', 'en', 'Header english'),
(24, 'table_header_english', 'ar', 'العنوان باللغة الإنجليزية'),
(25, 'table_header_arabic', 'en', 'Header arabic'),
(26, 'table_header_arabic', 'ar', 'رأس الصفحة باللغة العربية'),
(27, 'table_header_action', 'en', 'Action'),
(28, 'table_header_action', 'ar', 'فعل'),
(29, 'human_resource_system', 'en', 'Human Resource System'),
(30, 'human_resource_system', 'ar', 'نظام الموارد البشرية'),
(31, 'welcome_to_al-mutlak_co._admin_panel', 'en', 'Welcome to Al-Mutlak Co. Admin Panel'),
(32, 'welcome_to_al-mutlak_co._admin_panel', 'ar', 'مرحباً بكم في لوحة إدارة شركة المطلق'),
(33, 'search', 'en', 'Search'),
(34, 'search', 'ar', 'يبحث'),
(35, 'dashboard', 'en', 'Dashboard'),
(36, 'dashboard', 'ar', 'لوحة القيادة'),
(37, 'employee\'s', 'en', 'Employee\'s'),
(38, 'employee\'s', 'ar', 'موظفين'),
(39, 'new_employee', 'en', 'New Employee'),
(40, 'new_employee', 'ar', 'موظف جديد'),
(41, 'all_employees', 'en', 'All Employees'),
(42, 'all_employees', 'ar', 'جميع الموظفين'),
(43, 'employees_bank', 'en', 'Employees Bank'),
(44, 'employees_bank', 'ar', 'بنك الموظفين'),
(45, 'payroll', 'en', 'Payroll'),
(46, 'payroll', 'ar', 'الرواتب'),
(47, 'approvals', 'en', 'Approvals'),
(48, 'approvals', 'ar', 'الموافقات'),
(49, 'vacations', 'en', 'Vacations'),
(50, 'vacations', 'ar', 'العطلات'),
(51, 'loans', 'en', 'Loans'),
(52, 'loans', 'ar', 'القروض'),
(53, 'content_updates', 'en', 'Content updates'),
(54, 'content_updates', 'ar', 'تحديثات المحتوى'),
(55, 'smart_requests', 'en', 'Smart requests'),
(56, 'smart_requests', 'ar', 'الطلبات الذكية'),
(57, 'vouchers', 'en', 'Vouchers'),
(58, 'vouchers', 'ar', 'قسائم'),
(59, 'cars', 'en', 'Cars'),
(60, 'cars', 'ar', 'السيارات'),
(61, 'locations', 'en', 'Locations'),
(62, 'locations', 'ar', 'المواقع'),
(63, 'settings', 'en', 'Settings'),
(64, 'settings', 'ar', 'الإعدادات'),
(65, 'users', 'en', 'Users'),
(66, 'users', 'ar', 'المستخدمين'),
(67, 'language', 'en', 'Language'),
(68, 'language', 'ar', 'اللغة'),
(69, 'almutlak_employees', 'en', 'Almutlak employees'),
(70, 'almutlak_employees', 'ar', 'موظفي شركة المطلق'),
(71, 'man_power_employees', 'en', 'Man power employees'),
(72, 'man_power_employees', 'ar', 'موظفي القوى العاملة'),
(73, 'total_on_job_employees', 'en', 'Total on job employees'),
(74, 'total_on_job_employees', 'ar', 'إجمالي الموظفين العاملين'),
(75, 'on_vacations_employees', 'en', 'On vacations employees'),
(76, 'on_vacations_employees', 'ar', 'موظفو العطلات'),
(77, 'terminated_employees', 'en', 'Terminated employees'),
(78, 'terminated_employees', 'ar', 'الموظفين المفصولين'),
(79, 'total_employees', 'en', 'Total employees'),
(80, 'total_employees', 'ar', 'إجمالي الموظفين'),
(81, 'all_employees_grouping', 'en', 'All employees grouping'),
(82, 'all_employees_grouping', 'ar', 'تجميع جميع الموظفين'),
(83, 'departments', 'en', 'Departments'),
(84, 'departments', 'ar', 'الأقسام'),
(85, 'employees_list', 'en', 'Employees list'),
(86, 'employees_list', 'ar', 'قائمة الموظفين'),
(87, 'emp_id', 'en', 'Emp id'),
(88, 'emp_id', 'ar', 'معرف الموظف'),
(89, 'employee_name', 'en', 'Employee name'),
(90, 'employee_name', 'ar', 'اسم الموظف'),
(91, 'department', 'en', 'Department'),
(92, 'department', 'ar', 'قسم'),
(93, 'iqama_id', 'en', 'Iqama id'),
(94, 'iqama_id', 'ar', 'هوية / الإقامة'),
(95, 'mobile', 'en', 'Mobile'),
(96, 'mobile', 'ar', 'الجوال'),
(97, 'date_of_birth', 'en', 'Date of birth'),
(98, 'date_of_birth', 'ar', 'تاريخ الميلاد'),
(99, 'sponsorship', 'en', 'Sponsorship'),
(100, 'sponsorship', 'ar', 'كفالة'),
(101, 'blood_group', 'en', 'Blood group'),
(102, 'blood_group', 'ar', 'فصيلة الدم'),
(103, 'gender', 'en', 'Gender'),
(104, 'gender', 'ar', 'الجنس'),
(105, 'country', 'en', 'Country'),
(106, 'country', 'ar', 'دولة'),
(107, 'joining_date', 'en', 'Joining date'),
(108, 'joining_date', 'ar', 'تاريخ الانضمام'),
(109, 'action', 'en', 'Action'),
(110, 'action', 'ar', 'فعل'),
(111, 'open', 'en', 'Open'),
(112, 'open', 'ar', 'يفتح'),
(113, 'edit', 'en', 'Edit'),
(114, 'edit', 'ar', 'تعديل'),
(115, 'delete', 'en', 'Delete'),
(116, 'delete', 'ar', 'حذف'),
(117, 'show', 'en', 'Show'),
(118, 'show', 'ar', 'يعرض'),
(119, 'entries', 'en', 'Entries'),
(120, 'entries', 'ar', 'الإدخالات'),
(121, 'showing', 'en', 'Showing'),
(122, 'showing', 'ar', 'عرض'),
(123, 'to', 'en', 'To'),
(124, 'to', 'ar', 'إلى'),
(125, 'of', 'en', 'Of'),
(126, 'of', 'ar', 'من'),
(127, 'filtered_from', 'en', 'Filtered from'),
(128, 'filtered_from', 'ar', 'مُصفى من'),
(129, 'total_entries', 'en', 'Total entries'),
(130, 'total_entries', 'ar', 'إجمالي الإدخالات'),
(131, 'first', 'en', 'First'),
(132, 'first', 'ar', 'الأول'),
(133, 'last', 'en', 'Last'),
(134, 'last', 'ar', 'الأخير'),
(135, 'next', 'en', 'Next'),
(136, 'next', 'ar', 'التالي'),
(137, 'previous', 'en', 'Previous'),
(138, 'previous', 'ar', 'سابق'),
(139, 'no_data_available_in_table', 'en', 'No data available in table'),
(140, 'no_data_available_in_table', 'ar', 'لا توجد بيانات متاحة في الجدول'),
(141, 'no_matching_records_found', 'en', 'No matching records found'),
(142, 'no_matching_records_found', 'ar', 'لم يتم العثور على سجلات مطابقة'),
(143, 'loading', 'en', 'Loading...'),
(144, 'loading', 'ar', 'جار التحميل...'),
(145, 'name', 'en', 'Name'),
(146, 'name', 'ar', 'الاسم'),
(147, 'vacation_days', 'en', 'Vacation days'),
(148, 'vacation_days', 'ar', 'أيام العطلة'),
(149, 'employee_no', 'en', 'Employee no'),
(150, 'employee_no', 'ar', 'رقم الموظفى'),
(151, 'nationality', 'en', 'Nationality'),
(152, 'nationality', 'ar', 'الجنسية'),
(153, 'balance_vacations', 'en', 'Balance vacations'),
(154, 'balance_vacations', 'ar', 'عطلات متوازنة'),
(155, 'days', 'en', 'Days'),
(156, 'days', 'ar', 'أيام'),
(157, 'date', 'en', 'Date'),
(158, 'date', 'ar', 'تاريخ'),
(159, 'more', 'en', 'More'),
(160, 'more', 'ar', 'المزيد'),
(161, 'add_documents', 'en', 'Add documents'),
(162, 'add_documents', 'ar', 'إضافة المستندات'),
(163, 'apply_loan', 'en', 'Apply loan'),
(164, 'apply_loan', 'ar', 'طلب قرض'),
(165, 'emergency_loan', 'en', 'Emergency loan'),
(166, 'emergency_loan', 'ar', 'قرض الطوارئ'),
(167, 'send_qr', 'en', 'Send QR'),
(168, 'send_qr', 'ar', 'إرسال رمز الاستجابة السريعة'),
(169, 'create_end_of_service', 'en', 'Create end of service'),
(170, 'create_end_of_service', 'ar', 'إنشاء نهاية الخدمة'),
(171, 'apply_annual_vacation', 'en', 'Apply annual vacation'),
(172, 'apply_annual_vacation', 'ar', 'تطبيق الإجازة السنوية'),
(173, 'apply_leave', 'en', 'Apply leave'),
(174, 'apply_leave', 'ar', 'تقديم طلب إجازة'),
(175, 'create_login', 'en', 'Create login'),
(176, 'create_login', 'ar', 'إنشاء تسجيل الدخول'),
(177, 'arrived', 'en', 'Arrived'),
(178, 'arrived', 'ar', 'وصل'),
(179, 'add_vacation', 'en', 'Add vacation'),
(180, 'add_vacation', 'ar', 'إضافة إجازة'),
(181, 'terminat', 'en', 'Terminat'),
(182, 'terminat', 'ar', 'تم الانتهاء'),
(183, 'note', 'en', 'Note'),
(184, 'note', 'ar', 'ملاحظة'),
(185, 'edit_information', 'en', 'Edit information'),
(186, 'edit_information', 'ar', 'تعديل المعلومات'),
(187, 'goto_back', 'en', 'Goto back'),
(188, 'goto_back', 'ar', 'العودة إلى الخلف'),
(189, 'basic', 'en', 'Basic'),
(190, 'basic', 'ar', 'أساسي'),
(191, 'housing', 'en', 'Housing'),
(192, 'housing', 'ar', 'السكن'),
(193, 'transport', 'en', 'Transport'),
(194, 'transport', 'ar', 'النقل'),
(195, 'food', 'en', 'Food'),
(196, 'food', 'ar', 'طعام'),
(197, 'misc', 'en', 'Misc.'),
(198, 'misc', 'ar', 'متنوع'),
(199, 'cashier', 'en', 'Cashier'),
(200, 'cashier', 'ar', 'أمين الصندوق'),
(201, 'fuel', 'en', 'Fuel'),
(202, 'fuel', 'ar', 'وقود'),
(203, 'tel', 'en', 'Tel'),
(204, 'tel', 'ar', 'هاتف'),
(205, 'other', 'en', 'Other'),
(206, 'other', 'ar', 'اخرى'),
(207, 'guard', 'en', 'Guard'),
(208, 'guard', 'ar', 'حارس'),
(209, 'encashed', 'en', 'Encashed'),
(210, 'encashed', 'ar', 'تم صرفها'),
(211, 'flys', 'en', 'Flys'),
(212, 'flys', 'ar', 'سافر'),
(213, 'assign_asset', 'en', 'Assign asset'),
(214, 'assign_asset', 'ar', 'تعيين الأصول'),
(215, 'total_salary', 'en', 'Total salary'),
(216, 'total_salary', 'ar', 'إجمالي الراتب'),
(217, 'employee_information', 'en', 'Employee information'),
(218, 'employee_information', 'ar', 'معلومات الموظف'),
(219, 'profile', 'en', 'Profile'),
(220, 'profile', 'ar', 'الملف الشخصي'),
(221, 'bank_&_gosi_details', 'en', 'Bank & gosi details'),
(222, 'bank_&_gosi_details', 'ar', 'تفاصيل البنك والتأمينات الاجتماعية'),
(223, 'vacation_details', 'en', 'Vacation details'),
(224, 'vacation_details', 'ar', 'تفاصيل الإجازة'),
(225, 'loan_details', 'en', 'Loan details'),
(226, 'loan_details', 'ar', 'تفاصيل القرض'),
(227, 'assets_details', 'en', 'Assets details'),
(228, 'assets_details', 'ar', 'تفاصيل الأصول'),
(229, 'documents', 'en', 'Documents'),
(230, 'documents', 'ar', 'المستندات'),
(231, 'notes', 'en', 'Notes'),
(232, 'notes', 'ar', 'ملحوظات'),
(233, 'name_of_employee', 'en', 'Name of employee'),
(234, 'name_of_employee', 'ar', 'اسم الموظف'),
(235, 'email', 'en', 'Email'),
(236, 'email', 'ar', 'بريد إلكتروني'),
(237, 'personal', 'en', 'Personal'),
(238, 'personal', 'ar', 'شخصي'),
(239, 'company', 'en', 'Company'),
(240, 'company', 'ar', 'شركة'),
(241, 'id_expiry', 'en', 'Id expiry'),
(242, 'id_expiry', 'ar', 'انتهاء صلاحية الهوية'),
(243, 'passport_no', 'en', 'Passport no'),
(244, 'passport_no', 'ar', 'رقم جواز السفر'),
(245, 'passport_expiry', 'en', 'Passport expiry'),
(246, 'passport_expiry', 'ar', 'انتهاء صلاحية جواز السفر'),
(247, 'age', 'en', 'Age'),
(248, 'age', 'ar', 'العمر'),
(249, 'gender_blood_group', 'en', 'Gender | Blood group'),
(250, 'gender_blood_group', 'ar', 'الجنس | فصيلة الدم'),
(251, 'marital_status', 'en', 'Marital status'),
(252, 'marital_status', 'ar', 'الحالة الاجتماعية'),
(253, 'tshirt_size', 'en', 'T-Shirt size'),
(254, 'tshirt_size', 'ar', 'مقاس القميص'),
(255, 'contract_period', 'en', 'Contract period'),
(256, 'contract_period', 'ar', 'مدة العقد'),
(257, 'car_maker', 'en', 'Car maker'),
(258, 'car_maker', 'ar', 'صانع السيارات'),
(259, 'car_model', 'en', 'Car model'),
(260, 'car_model', 'ar', 'طراز السيارة'),
(261, 'section_area_sponsorship', 'en', 'Section Area | Sponsorship'),
(262, 'section_area_sponsorship', 'ar', 'قسم المنطقة | الرعاية'),
(263, 'bank_transfer', 'en', 'Bank transfer'),
(264, 'bank_transfer', 'ar', 'التحويل البنكي'),
(265, 'cash_payment', 'en', 'Cash payment'),
(266, 'cash_payment', 'ar', 'الدفع نقدا'),
(267, 'about_to_hold', 'en', 'About to hold'),
(268, 'about_to_hold', 'ar', 'على وشك عقد'),
(269, 'bank_name', 'en', 'Bank name'),
(270, 'bank_name', 'ar', 'اسم البنك'),
(271, 'iban', 'en', 'IBAN'),
(272, 'iban', 'ar', 'رقم الحساب المصرفي الدولي (IBAN)'),
(273, 'gosi_gosi_no', 'en', 'Gosi - Gosi No'),
(274, 'gosi_gosi_no', 'ar', 'أوز - أوز ولكن'),
(275, 'gosi_expiry', 'en', 'Gosi expiry'),
(276, 'gosi_expiry', 'ar', 'انتهاء صلاحية التأمينات الاجتماعية'),
(277, 'actual_job', 'en', 'Actual job'),
(278, 'actual_job', 'ar', 'الوظيفة الفعلية'),
(279, 'probation_period', 'en', 'Probation period'),
(280, 'probation_period', 'ar', 'فترة الاختبار'),
(281, 'insurance_no_class', 'en', 'Insurance No | class'),
(282, 'insurance_no_class', 'ar', 'رقم التأمين | الفئة'),
(283, 'insurance_expiry', 'en', 'Insurance expiry'),
(284, 'insurance_expiry', 'ar', 'انتهاء صلاحية التأمين'),
(285, 'emergency_contact', 'en', 'Emergency contact'),
(286, 'emergency_contact', 'ar', 'جهة الاتصال في حالات الطوارئ'),
(287, 'address', 'en', 'Address'),
(288, 'address', 'ar', 'العنوان'),
(289, 'print_profile', 'en', 'Print profile'),
(290, 'print_profile', 'ar', 'طباعة الملف الشخصي'),
(291, 'no_probation', 'en', 'No probation'),
(292, 'no_probation', 'ar', 'لا فترة اختبار'),
(293, 'under_probation', 'en', 'Under probation'),
(294, 'under_probation', 'ar', '3 أشهر (فترة اختبار افتراضية)'),
(295, 'new_request', 'en', 'New request'),
(296, 'new_request', 'ar', 'طلب جديد'),
(297, 'assistant_pending', 'en', 'Assistant pending'),
(298, 'assistant_pending', 'ar', 'مساعد معلق'),
(299, 'hr_assistant_approved', 'en', 'HR Assistant Approved'),
(300, 'hr_assistant_approved', 'ar', 'مساعد الموارد البشرية معتمد'),
(301, 'hr_manager_approved', 'en', 'HR Manager Approved'),
(302, 'hr_manager_approved', 'ar', 'تمت الموافقة من قبل مدير الموارد البشرية'),
(303, 'gm_approved', 'en', 'GM Approved'),
(304, 'gm_approved', 'ar', 'معتمد من جنرال موتورز'),
(305, 'rejected', 'en', 'Rejected'),
(306, 'rejected', 'ar', 'مرفوض'),
(307, 'bank_account_information', 'en', 'Bank account information'),
(308, 'bank_account_information', 'ar', 'معلومات الحساب المصرفي'),
(309, 'gosi_information', 'en', 'Gosi information'),
(310, 'gosi_information', 'ar', 'معلومات التأمينات الاجتماعية'),
(311, 'gosi_no', 'en', 'Gosi no'),
(312, 'gosi_no', 'ar', 'رقم التأمينات الاجتماعية'),
(313, 'gosi_payment', 'en', 'Gosi payment'),
(314, 'gosi_payment', 'ar', 'دفع التأمينات الاجتماعية'),
(315, 'gregorian_date', 'en', 'Gregorian date'),
(316, 'gregorian_date', 'ar', 'التاريخ الميلادي'),
(317, 'hijri_date', 'en', 'Hijri date'),
(318, 'hijri_date', 'ar', 'التاريخ الهجري'),
(319, 'add_gosi_details', 'en', 'Add gosi details'),
(320, 'add_gosi_details', 'ar', 'إضافة تفاصيل التأمينات الاجتماعية'),
(321, 'active_loan_summary', 'en', 'Active loan summary'),
(322, 'active_loan_summary', 'ar', 'ملخص القرض النشط'),
(323, 'total_payable_amount', 'en', 'Total payable amount'),
(324, 'total_payable_amount', 'ar', 'إجمالي المبلغ المستحق'),
(325, 'total_paid', 'en', 'Total paid'),
(326, 'total_paid', 'ar', 'إجمالي المدفوع'),
(327, 'remaining_balance', 'en', 'Remaining balance'),
(328, 'remaining_balance', 'ar', 'الرصيد المتبقي'),
(329, 'disbursement_receipt_id', 'en', 'Disbursement receipt id'),
(330, 'disbursement_receipt_id', 'ar', 'معرف إيصال الصرف'),
(331, 'disbursement_proof', 'en', 'Disbursement proof'),
(332, 'disbursement_proof', 'ar', 'إثبات الصرف'),
(333, 'loan_history', 'en', 'Loan history'),
(334, 'loan_history', 'ar', 'تاريخ القروض'),
(335, 'add_manual_payment', 'en', 'Add manual payment'),
(336, 'add_manual_payment', 'ar', 'إضافة الدفع اليدوي'),
(337, 'loan_amount', 'en', 'Loan amount'),
(338, 'loan_amount', 'ar', 'مبلغ القرض'),
(339, 'monthly_deduction', 'en', 'Monthly deduction'),
(340, 'monthly_deduction', 'ar', 'الخصم الشهري'),
(341, 'start_date', 'en', 'Start date'),
(342, 'start_date', 'ar', 'تاريخ البدء'),
(343, 'end_date', 'en', 'End date'),
(344, 'end_date', 'ar', 'تاريخ الانتهاء'),
(345, 'type', 'en', 'Type'),
(346, 'type', 'ar', 'نوع'),
(347, 'status', 'en', 'Status'),
(348, 'status', 'ar', 'حالة'),
(349, 'report', 'en', 'Report'),
(350, 'report', 'ar', 'تقرير'),
(351, 'repayment_history', 'en', 'Repayment history'),
(352, 'repayment_history', 'ar', 'سجل السداد'),
(353, 'payment_date', 'en', 'Payment date'),
(354, 'payment_date', 'ar', 'تاريخ الدفع'),
(355, 'amount', 'en', 'Amount'),
(356, 'amount', 'ar', 'المبلغ'),
(357, 'receipt_id', 'en', 'Receipt id'),
(358, 'receipt_id', 'ar', 'معرف الإيصال'),
(359, 'attachment', 'en', 'Attachment'),
(360, 'attachment', 'ar', 'المرفق'),
(361, 'loan_id', 'en', 'Loan id'),
(362, 'loan_id', 'ar', 'معرف القرض'),
(363, 'view', 'en', 'View'),
(364, 'view', 'ar', 'منظر'),
(365, 'assigned_assets', 'en', 'Assigned assets'),
(366, 'assigned_assets', 'ar', 'الأصول المخصصة'),
(367, 'asset_type', 'en', 'Asset type'),
(368, 'asset_type', 'ar', 'نوع الأصول'),
(369, 'serial_number', 'en', 'Serial number'),
(370, 'serial_number', 'ar', 'الرقم التسلسلي'),
(371, 'assigned_date', 'en', 'Assigned date'),
(372, 'assigned_date', 'ar', 'التاريخ المخصص'),
(373, 'return_date', 'en', 'Return date'),
(374, 'return_date', 'ar', 'تاريخ العودة'),
(375, 'assigned', 'en', 'Assigned'),
(376, 'assigned', 'ar', 'مُكَلَّف'),
(377, 'print_for_return', 'en', 'Print for return'),
(378, 'print_for_return', 'ar', 'طباعة للإرجاع'),
(379, 'submit_return', 'en', 'Submit return'),
(380, 'submit_return', 'ar', 'إرسال الإرجاع'),
(381, 'print_report', 'en', 'Print report'),
(382, 'print_report', 'ar', 'طباعة التقرير'),
(383, 'view_proof', 'en', 'View proof'),
(384, 'view_proof', 'ar', 'عرض الدليل'),
(385, 'my_files', 'en', 'My files'),
(386, 'my_files', 'ar', 'ملفاتي'),
(387, 'all_notes', 'en', 'All notes'),
(388, 'all_notes', 'ar', 'جميع الملاحظات'),
(389, 'remarks', 'en', 'Remarks'),
(390, 'remarks', 'ar', 'ملاحظات'),
(391, 'fly_date', 'en', 'Fly date'),
(392, 'fly_date', 'ar', 'موعد الطيران'),
(393, 'permit_no', 'en', 'Permit no'),
(394, 'permit_no', 'ar', 'رقم التصريح'),
(395, 'created_at', 'en', 'Created at'),
(396, 'created_at', 'ar', 'تم إنشاؤه في'),
(397, 'id', 'en', 'Id'),
(398, 'id', 'ar', 'معرف'),
(399, 'update_salary', 'en', 'Update salary'),
(400, 'update_salary', 'ar', 'تحديث الراتب'),
(401, 'print_end_of_service', 'en', 'Print end of service'),
(402, 'print_end_of_service', 'ar', 'طباعة نهاية الخدمة'),
(403, 'returned', 'en', 'Returned'),
(404, 'returned', 'ar', 'أرجع'),
(405, 'lost', 'en', 'Lost'),
(406, 'lost', 'ar', 'ضائع'),
(407, 'damaged', 'en', 'Damaged'),
(408, 'damaged', 'ar', 'متضرر'),
(409, 'pending', 'en', 'Pending'),
(410, 'pending', 'ar', 'قيد الانتظار'),
(411, 'approved', 'en', 'Approved'),
(412, 'approved', 'ar', 'موافق عليه'),
(413, 'processed', 'en', 'Processed'),
(414, 'processed', 'ar', 'تمت معالجتها'),
(415, 'regular', 'en', 'Regular'),
(416, 'regular', 'ar', 'عادي'),
(417, 'emergency', 'en', 'Emergency'),
(418, 'emergency', 'ar', 'حالة طوارئ'),
(419, 'single', 'en', 'Single'),
(420, 'single', 'ar', 'أعزب'),
(421, 'married', 'en', 'Married'),
(422, 'married', 'ar', 'متزوج'),
(423, 'years', 'en', 'Years'),
(424, 'years', 'ar', 'سنوات'),
(425, 'male', 'en', 'Male'),
(426, 'male', 'ar', 'ذكر'),
(427, 'female', 'en', 'Female'),
(428, 'female', 'ar', 'أنثى'),
(429, 'add_employee_documents', 'en', 'Add employee documents'),
(430, 'add_employee_documents', 'ar', 'إضافة مستندات الموظف'),
(431, 'type_of_document', 'en', 'Type of document'),
(432, 'type_of_document', 'ar', 'نوع الوثيقة'),
(535, 'revert_warning', 'en', 'You won\'t be able to revert this!'),
(536, 'revert_warning', 'ar', 'لن تتمكن من التراجع عن هذا!'),
(537, 'yes_delete_it', 'en', 'Yes, delete it!'),
(538, 'yes_delete_it', 'ar', 'نعم، احذفه!'),
(539, 'signout_warning', 'en', 'You won\'t be signout this account!'),
(540, 'signout_warning', 'ar', 'لن تقوم بتسجيل الخروج من هذا الحساب!'),
(541, 'yes_signout', 'en', 'Yes, Signout!'),
(542, 'yes_signout', 'ar', 'نعم، تسجيل الخروج!'),
(543, 'add_new_item_info', 'en', 'Add new Item information'),
(544, 'add_new_item_info', 'ar', 'إضافة معلومات عنصر جديد'),
(545, 'yes_register', 'en', 'Yes, Register!'),
(546, 'yes_register', 'ar', 'نعم، تسجيل!'),
(547, 'enter_name_en_validation', 'en', 'Please enter Name in English'),
(548, 'enter_name_en_validation', 'ar', 'الرجاء إدخال الاسم باللغة الإنجليزية'),
(549, 'enter_name_ar_validation', 'en', 'Please enter Name in Arabic'),
(550, 'enter_name_ar_validation', 'ar', 'الرجاء إدخال الاسم باللغة العربية'),
(551, 'select_price_level_validation', 'en', 'Please select price level'),
(552, 'select_price_level_validation', 'ar', 'الرجاء تحديد مستوى السعر'),
(553, 'select_item_category_validation', 'en', 'Please select item category'),
(554, 'select_item_category_validation', 'ar', 'الرجاء تحديد فئة العنصر'),
(555, 'enter_big_item_price_validation', 'en', 'Please enter big item price'),
(556, 'enter_big_item_price_validation', 'ar', 'الرجاء إدخال سعر العنصر الكبير'),
(557, 'enter_small_item_price_validation', 'en', 'Please enter small item price'),
(558, 'enter_small_item_price_validation', 'ar', 'الرجاء إدخال سعر العنصر الصغير'),
(559, 'enter_big_calories_validation', 'en', 'Please enter big calories'),
(560, 'enter_big_calories_validation', 'ar', 'الرجاء إدخال السعرات الحرارية الكبيرة'),
(561, 'enter_small_calories_validation', 'en', 'Please enter small calories'),
(562, 'enter_small_calories_validation', 'ar', 'الرجاء إدخال السعرات الحرارية الصغيرة'),
(563, 'select_item_image_validation', 'en', 'Please select item image.'),
(564, 'select_item_image_validation', 'ar', 'الرجاء تحديد صورة العنصر.'),
(565, 'upload_jpg_png_only_validation', 'en', 'You can upload only JPG OR PNG files'),
(566, 'upload_jpg_png_only_validation', 'ar', 'يمكنك تحميل ملفات JPG أو PNG فقط'),
(567, 'upload_size_limit_5mb_validation', 'en', 'Upload file not morethen 5MB.'),
(568, 'upload_size_limit_5mb_validation', 'ar', 'يجب ألا يزيد حجم الملف المرفوع عن 5 ميجابايت.'),
(569, 'fill_mandatory_fields', 'en', 'Please fill all mendatory(*) fields first!'),
(570, 'fill_mandatory_fields', 'ar', 'يرجى ملء جميع الحقول الإلزامية (*) أولاً!'),
(571, 'update_item_info', 'en', 'Update Item information'),
(572, 'update_item_info', 'ar', 'تحديث معلومات العنصر'),
(573, 'yes_update', 'en', 'Yes, Update!'),
(574, 'yes_update', 'ar', 'نعم، تحديث!'),
(575, 'not_an_image_validation', 'en', 'You cannot upload this file because it’s not an image.'),
(576, 'not_an_image_validation', 'ar', 'لا يمكنك تحميل هذا الملف لأنه ليس صورة.'),
(577, 'add_category_info', 'en', 'Add Category information'),
(578, 'add_category_info', 'ar', 'إضافة معلومات الفئة'),
(579, 'enter_category_name_en_validation', 'en', 'Please enter category name in english.'),
(580, 'enter_category_name_en_validation', 'ar', 'الرجاء إدخال اسم الفئة باللغة الإنجليزية.'),
(581, 'enter_category_name_ar_validation', 'en', 'Please enter category name in arabic.'),
(582, 'enter_category_name_ar_validation', 'ar', 'الرجاء إدخال اسم الفئة باللغة العربية.'),
(583, 'select_category_type_validation', 'en', 'Please enter select category type.'),
(584, 'select_category_type_validation', 'ar', 'الرجاء تحديد نوع الفئة.'),
(585, 'update_category_info', 'en', 'Update Category information'),
(586, 'update_category_info', 'ar', 'تحديث معلومات الفئة'),
(587, 'add_new_car_info', 'en', 'Add new car information'),
(588, 'add_new_car_info', 'ar', 'إضافة معلومات سيارة جديدة'),
(589, 'enter_car_maker_validation', 'en', 'Please enter car maker name.'),
(590, 'enter_car_maker_validation', 'ar', 'الرجاء إدخال اسم صانع السيارة.'),
(591, 'enter_car_model_validation', 'en', 'Please enter car model.'),
(592, 'enter_car_model_validation', 'ar', 'الرجاء إدخال طراز السيارة.'),
(593, 'enter_car_made_year_validation', 'en', 'Please enter car made year.'),
(594, 'enter_car_made_year_validation', 'ar', 'الرجاء إدخال سنة صنع السيارة.'),
(595, 'select_car_type_validation', 'en', 'Please select car type.'),
(596, 'select_car_type_validation', 'ar', 'الرجاء تحديد نوع السيارة.'),
(597, 'enter_car_plate_no_validation', 'en', 'Please enter car plate no.'),
(598, 'enter_car_plate_no_validation', 'ar', 'الرجاء إدخال رقم لوحة السيارة.'),
(599, 'update_car_info', 'en', 'Update Car information'),
(600, 'update_car_info', 'ar', 'تحديث معلومات السيارة'),
(601, 'add_maintenance_info', 'en', 'Add Maintenance information'),
(602, 'add_maintenance_info', 'ar', 'إضافة معلومات الصيانة'),
(603, 'select_car_driver_validation', 'en', 'Please select car driver name.'),
(604, 'select_car_driver_validation', 'ar', 'الرجاء تحديد اسم سائق السيارة.'),
(605, 'select_maintenance_date_validation', 'en', 'Please select maintenance date.'),
(606, 'select_maintenance_date_validation', 'ar', 'الرجاء تحديد تاريخ الصيانة.'),
(607, 'enter_meter_reading_validation', 'en', 'Please enter car meter reading.'),
(608, 'enter_meter_reading_validation', 'ar', 'الرجاء إدخال قراءة عداد السيارة.'),
(609, 'select_maintenance_type_validation', 'en', 'Please select maintenance type.'),
(610, 'select_maintenance_type_validation', 'ar', 'الرجاء تحديد نوع الصيانة.'),
(611, 'enter_maintenance_details_validation', 'en', 'Please enter details of maintenance.'),
(612, 'enter_maintenance_details_validation', 'ar', 'الرجاء إدخال تفاصيل الصيانة.'),
(613, 'add_driver_info', 'en', 'Add driver information.'),
(614, 'add_driver_info', 'ar', 'إضافة معلومات السائق.'),
(615, 'select_issue_date_validation', 'en', 'Please select issue date.'),
(616, 'select_issue_date_validation', 'ar', 'الرجاء تحديد تاريخ الإصدار.'),
(617, 'want_to_return_car', 'en', 'You want to return this car!'),
(618, 'want_to_return_car', 'ar', 'هل تريد إرجاع هذه السيارة!'),
(619, 'yes_do_it', 'en', 'Yes, do it!'),
(620, 'yes_do_it', 'ar', 'نعم، افعلها!'),
(621, 'add_type', 'en', 'Add type'),
(622, 'add_type', 'ar', 'إضافة نوع'),
(623, 'enter_type_name_validation', 'en', 'Please enter type name.'),
(624, 'enter_type_name_validation', 'ar', 'الرجاء إدخال اسم النوع.'),
(625, 'add_documents_info', 'en', 'Add documents information.'),
(626, 'add_documents_info', 'ar', 'إضافة معلومات المستندات.'),
(627, 'select_documents_type_validation', 'en', 'Please select documents type.'),
(628, 'select_documents_type_validation', 'ar', 'الرجاء تحديد نوع المستندات.'),
(629, 'select_attachment_selection_validation', 'en', 'Please select attachment selection.'),
(630, 'select_attachment_selection_validation', 'ar', 'الرجاء تحديد اختيار المرفق.'),
(631, 'select_attachment_file_validation', 'en', 'Please select attachment file.'),
(632, 'select_attachment_file_validation', 'ar', 'الرجاء تحديد ملف المرفق.'),
(633, 'upload_pdf_jpg_only_validation', 'en', 'You can upload only PDF OR JPG files'),
(634, 'upload_pdf_jpg_only_validation', 'ar', 'يمكنك تحميل ملفات PDF أو JPG فقط'),
(635, 'update_line_info', 'en', 'Update Line information'),
(636, 'update_line_info', 'ar', 'تحديث معلومات السطر'),
(637, 'enter_item_name_validation', 'en', 'Please enter item name.'),
(638, 'enter_item_name_validation', 'ar', 'الرجاء إدخال اسم العنصر.'),
(639, 'select_location_validation', 'en', 'Please select location.'),
(640, 'select_location_validation', 'ar', 'الرجاء تحديد الموقع.'),
(641, 'enter_item_quantity_validation', 'en', 'Please item quantity.'),
(642, 'enter_item_quantity_validation', 'ar', 'الرجاء إدخال كمية العنصر.'),
(643, 'enter_product_price_validation', 'en', 'Please enter price of product.'),
(644, 'enter_product_price_validation', 'ar', 'الرجاء إدخال سعر المنتج.'),
(645, 'enter_vat_rate_validation', 'en', 'Please enter vat rate.'),
(646, 'enter_vat_rate_validation', 'ar', 'الرجاء إدخال نسبة الضريبة المضافة.'),
(647, 'update_request_info', 'en', 'Update Request information'),
(648, 'update_request_info', 'ar', 'تحديث معلومات الطلب'),
(649, 'enter_request_subtitle_validation', 'en', 'Please enter request sub-title.'),
(650, 'enter_request_subtitle_validation', 'ar', 'الرجاء إدخال العنوان الفرعي للطلب.'),
(651, 'select_request_type_validation', 'en', 'Please select request type.'),
(652, 'select_request_type_validation', 'ar', 'الرجاء تحديد نوع الطلب.'),
(653, 'dropzone_file_upload', 'en', 'Dropzone File Upload'),
(654, 'dropzone_file_upload', 'ar', 'تحميل ملف Dropzone'),
(655, 'yes_upload_it', 'en', 'Yes, Upload it!'),
(656, 'yes_upload_it', 'ar', 'نعم، قم بالتحميل!'),
(657, 'uploaded', 'en', 'Uploaded!'),
(658, 'uploaded', 'ar', 'تم التحميل!'),
(659, 'files_uploaded_successfully', 'en', 'Your files bas been uploaded successfully.'),
(660, 'files_uploaded_successfully', 'ar', 'تم تحميل ملفاتك بنجاح.'),
(661, 'update_shop_image', 'en', 'Update Shop Image'),
(662, 'update_shop_image', 'ar', 'تحديث صورة المحل'),
(663, 'select_image', 'en', 'Select Image:'),
(664, 'select_image', 'ar', 'اختر صورة:'),
(665, 'oops', 'en', 'Ooops!'),
(666, 'oops', 'ar', 'عفواً!'),
(667, 'file_not_supported', 'en', 'File not supported'),
(668, 'file_not_supported', 'ar', 'الملف غير مدعوم'),
(669, 'add_new_location', 'en', 'Add new location'),
(670, 'add_new_location', 'ar', 'إضافة موقع جديد'),
(671, 'enter_section_name_validation', 'en', 'Please enter section name.'),
(672, 'enter_section_name_validation', 'ar', 'الرجاء إدخال اسم القسم.'),
(673, 'enter_latitude_validation', 'en', 'Please enter latitude for section.'),
(674, 'enter_latitude_validation', 'ar', 'الرجاء إدخال خط العرض للقسم.'),
(675, 'enter_longitude_validation', 'en', 'Please enter longitude for section.'),
(676, 'enter_longitude_validation', 'ar', 'الرجاء إدخال خط الطول للقسم.'),
(677, 'select_department_validation', 'en', 'Please select department.'),
(678, 'select_department_validation', 'ar', 'الرجاء تحديد القسم.'),
(679, 'enter_building_size_validation', 'en', 'Please enter bulding size.'),
(680, 'enter_building_size_validation', 'ar', 'الرجاء إدخال مساحة المبنى.'),
(681, 'enter_location_district_validation', 'en', 'Please enter location district.'),
(682, 'enter_location_district_validation', 'ar', 'الرجاء إدخال منطقة الموقع.'),
(683, 'enter_baladya_license_no_validation', 'en', 'Please enter baladya license no.'),
(684, 'enter_baladya_license_no_validation', 'ar', 'الرجاء إدخال رقم رخصة البلدية.'),
(685, 'select_balady_license_expiry_validation', 'en', 'Please select balady license expiry.'),
(686, 'select_balady_license_expiry_validation', 'ar', 'الرجاء تحديد تاريخ انتهاء رخصة البلدية.'),
(687, 'update_location_info', 'en', 'Update Location information'),
(688, 'update_location_info', 'ar', 'تحديث معلومات الموقع'),
(689, 'add_location_contract_info', 'en', 'Add Location Contrtact information'),
(690, 'add_location_contract_info', 'ar', 'إضافة معلومات عقد الموقع'),
(691, 'enter_owner_name_validation', 'en', 'Please enter owner name.'),
(692, 'enter_owner_name_validation', 'ar', 'الرجاء إدخال اسم المالك.'),
(693, 'enter_owner_contact_validation', 'en', 'Please enter owner contact number.'),
(694, 'enter_owner_contact_validation', 'ar', 'الرجاء إدخال رقم الاتصال بالمالك.'),
(695, 'enter_owner_email_validation', 'en', 'Please enter owner email.'),
(696, 'enter_owner_email_validation', 'ar', 'الرجاء إدخال البريد الإلكتروني للمالك.'),
(697, 'enter_contract_number_validation', 'en', 'Please enter contract number.'),
(698, 'enter_contract_number_validation', 'ar', 'الرجاء إدخال رقم العقد.'),
(699, 'select_start_contract_date_validation', 'en', 'Please select start contract date.'),
(700, 'select_start_contract_date_validation', 'ar', 'الرجاء تحديد تاريخ بدء العقد.'),
(701, 'select_end_contract_date_validation', 'en', 'Please select end contract date.'),
(702, 'select_end_contract_date_validation', 'ar', 'الرجاء تحديد تاريخ انتهاء العقد.'),
(703, 'enter_rent_amount_validation', 'en', 'Please enter rent amount.'),
(704, 'enter_rent_amount_validation', 'ar', 'الرجاء إدخال مبلغ الإيجار.'),
(705, 'enter_insurance_amount_validation', 'en', 'Please enter incuranse amount.'),
(706, 'enter_insurance_amount_validation', 'ar', 'الرجاء إدخال مبلغ التأمين.'),
(707, 'add_new_customer', 'en', 'Add new Customer'),
(708, 'add_new_customer', 'ar', 'إضافة عميل جديد'),
(709, 'enter_customer_full_name_validation', 'en', 'Please enter customer full name.'),
(710, 'enter_customer_full_name_validation', 'ar', 'الرجاء إدخال اسم العميل الكامل.'),
(711, 'enter_customer_injazat_no_validation', 'en', 'Please enter customer INJAZAT no.'),
(712, 'enter_customer_injazat_no_validation', 'ar', 'الرجاء إدخال رقم إنجازات العميل.'),
(713, 'enter_mobile_number_validation', 'en', 'Please enter mobile number.'),
(714, 'enter_mobile_number_validation', 'ar', 'الرجاء إدخال رقم الجوال.'),
(715, 'enter_account_number_validation', 'en', 'Please account number.'),
(716, 'enter_account_number_validation', 'ar', 'الرجاء إدخال رقم الحساب.'),
(717, 'select_expiry_date_validation', 'en', 'Please select expiry date.'),
(718, 'select_expiry_date_validation', 'ar', 'الرجاء تحديد تاريخ انتهاء الصلاحية.'),
(719, 'update_customer_info', 'en', 'Update customer information'),
(720, 'update_customer_info', 'ar', 'تحديث معلومات العميل'),
(721, 'update_vip_customer_card', 'en', 'Update VIP Customer Card'),
(722, 'update_vip_customer_card', 'ar', 'تحديث بطاقة عميل VIP'),
(723, 'add_vip_customer_card', 'en', 'Add VIP Customer Card'),
(724, 'add_vip_customer_card', 'ar', 'إضافة بطاقة عميل VIP'),
(725, 'enter_new_injazat_no_validation', 'en', 'Please enter new Injazat No.'),
(726, 'enter_new_injazat_no_validation', 'ar', 'الرجاء إدخال رقم إنجازات جديد.'),
(727, 'update_user_info', 'en', 'Update user information'),
(728, 'update_user_info', 'ar', 'تحديث معلومات المستخدم'),
(729, 'update_password_for_user', 'en', 'Update Password for User'),
(730, 'update_password_for_user', 'ar', 'تحديث كلمة المرور للمستخدم'),
(731, 'show_password', 'en', 'Show Password'),
(732, 'show_password', 'ar', 'إظهار كلمة المرور'),
(733, 'enter_new_password_validation', 'en', 'Please enter new password'),
(734, 'enter_new_password_validation', 'ar', 'الرجاء إدخال كلمة مرور جديدة'),
(735, 'enter_confirm_password_validation', 'en', 'Enter confirm password'),
(736, 'enter_confirm_password_validation', 'ar', 'أدخل تأكيد كلمة المرور'),
(737, 'password_minlength_5_validation', 'en', 'Password minlength is 5'),
(738, 'password_minlength_5_validation', 'ar', 'الحد الأدنى لطول كلمة المرور هو 5'),
(739, 'password_no_match_validation', 'en', 'Confirm password does not match!'),
(740, 'password_no_match_validation', 'ar', 'تأكيد كلمة المرور غير متطابق!'),
(741, 'your_current_password', 'en', 'Your Current Password'),
(742, 'your_current_password', 'ar', 'كلمة المرور الحالية الخاصة بك'),
(743, 'close', 'en', 'Close'),
(744, 'close', 'ar', 'إغلاق'),
(745, 'create_new_user', 'en', 'Create New User'),
(746, 'create_new_user', 'ar', 'إنشاء مستخدم جديد'),
(747, 'create_user', 'en', 'Create User'),
(748, 'create_user', 'ar', 'إنشاء مستخدم'),
(749, 'request_failed', 'en', 'Request failed: '),
(750, 'request_failed', 'ar', 'فشل الطلب: '),
(751, 'enter_valid_email', 'en', 'Please enter a valid email address.'),
(752, 'enter_valid_email', 'ar', 'الرجاء إدخال عنوان بريد إلكتروني صالح.'),
(753, 'select_employee_type', 'en', 'Please select an employee type.'),
(754, 'select_employee_type', 'ar', 'الرجاء تحديد نوع الموظف.'),
(3065, 'eos_calculator_title', 'en', 'End of service benefits calculator'),
(3066, 'eos_calculator_title', 'ar', 'حاسبة مستحقات نهاية الخدمة'),
(3067, 'yes_calculate', 'en', 'Yes, Calcuclate!'),
(3068, 'yes_calculate', 'ar', 'نعم، احسب!'),
(3069, 'select_type', 'en', 'Select type'),
(3070, 'select_type', 'ar', 'اختر النوع'),
(3071, 'fixed_time', 'en', 'Fixed time'),
(3072, 'fixed_time', 'ar', 'عقد محدد المدة'),
(3073, 'unlimited_period', 'en', 'Unlimited period'),
(3074, 'unlimited_period', 'ar', 'عقد غير محدد المدة'),
(3075, 'select_reason', 'en', 'Select reason'),
(3076, 'select_reason', 'ar', 'اختر السبب'),
(3077, 'type_of_contract', 'en', 'Type of Contract'),
(3078, 'type_of_contract', 'ar', 'نوع العقد'),
(3079, 'end_of_service_reason', 'en', 'End of Service Reason'),
(3080, 'end_of_service_reason', 'ar', 'سبب نهاية الخدمة'),
(3081, 'end_of_service_date', 'en', 'End of Service Date'),
(3082, 'end_of_service_date', 'ar', 'تاريخ نهاية الخدمة'),
(3083, 'salary', 'en', 'Salary'),
(3084, 'salary', 'ar', 'الراتب'),
(3085, 'duration_of_service_years', 'en', 'Duration of service (years)'),
(3086, 'duration_of_service_years', 'ar', 'مدة الخدمة (سنوات)'),
(3087, 'number_of_months', 'en', 'Number of months'),
(3088, 'number_of_months', 'ar', 'عدد الشهور'),
(3089, 'number_of_days', 'en', 'Number of day'),
(3090, 'number_of_days', 'ar', 'عدد الأيام'),
(3091, 'change_employee_image', 'en', 'Change Employee Image'),
(3092, 'change_employee_image', 'ar', 'تغيير صورة الموظف'),
(3093, 'file_error_title', 'en', 'File error..'),
(3094, 'file_error_title', 'ar', 'خطأ في الملف..'),
(3095, 'select_jpg_format_only', 'en', 'Please select JPG format only.'),
(3096, 'select_jpg_format_only', 'ar', 'الرجاء تحديد صيغة JPG فقط.'),
(3097, 'add_social_media_links', 'en', 'Add Social Media links'),
(3098, 'add_social_media_links', 'ar', 'إضافة روابط التواصل الاجتماعي'),
(3099, 'select', 'en', 'Select'),
(3100, 'select', 'ar', 'اختر'),
(3101, 'enter_social_address_validation', 'en', 'Please enter social address.'),
(3102, 'enter_social_address_validation', 'ar', 'الرجاء إدخال عنوان التواصل الاجتماعي.'),
(3103, 'select_social_link_validation', 'en', 'Please select social link.'),
(3104, 'select_social_link_validation', 'ar', 'الرجاء تحديد رابط التواصل الاجتماعي.'),
(3105, 'add_portfolio_details', 'en', 'Add Portfolio details'),
(3106, 'add_portfolio_details', 'ar', 'إضافة تفاصيل معرض الأعمال'),
(3107, 'add_details_for_portfolio_placeholder', 'en', 'Add details for portfolio.'),
(3108, 'add_details_for_portfolio_placeholder', 'ar', 'أضف تفاصيل لمعرض الأعمال.'),
(3109, 'enter_portfolio_title_validation', 'en', 'Please enter portfolio title.'),
(3110, 'enter_portfolio_title_validation', 'ar', 'الرجاء إدخال عنوان معرض الأعمال.'),
(3111, 'update_id_expiry_info', 'en', 'Update ID Expiry information'),
(3112, 'update_id_expiry_info', 'ar', 'تحديث معلومات انتهاء صلاحية الهوية'),
(3113, 'select_date_type', 'en', 'Select Date Type'),
(3114, 'select_date_type', 'ar', 'اختر نوع التاريخ'),
(3115, 'select_id_iqama_expiry_validation', 'en', 'Please select ID/Iqama expiry.'),
(3116, 'select_id_iqama_expiry_validation', 'ar', 'الرجاء تحديد تاريخ انتهاء صلاحية الهوية/الإقامة.'),
(3117, 'others', 'en', 'Others'),
(3118, 'others', 'ar', 'أخرى'),
(3119, 'employee_info_update_request', 'en', 'Employee Information Update Request'),
(3120, 'employee_info_update_request', 'ar', 'طلب تحديث معلومات الموظف'),
(3121, 'submit_action', 'en', 'Submit Action'),
(3122, 'submit_action', 'ar', 'إرسال الإجراء'),
(3123, 'show_attachment', 'en', 'Show Attachment'),
(3124, 'show_attachment', 'ar', 'عرض المرفق'),
(3125, 'request_update_field', 'en', 'REQUEST: Update'),
(3126, 'request_update_field', 'ar', 'طلب: تحديث'),
(3127, 'description', 'en', 'Description:'),
(3128, 'description', 'ar', 'الوصف:'),
(3129, 'new_value', 'en', 'New Value:'),
(3130, 'new_value', 'ar', 'القيمة الجديدة:'),
(3131, 'approval_notes', 'en', 'Approval Notes'),
(3132, 'approval_notes', 'ar', 'ملاحظات الموافقة'),
(3133, 'rejection_reason', 'en', 'Rejection Reason'),
(3134, 'rejection_reason', 'ar', 'سبب الرفض'),
(3135, 'select_action', 'en', '-- Select Action --'),
(3136, 'select_action', 'ar', '-- اختر إجراء --'),
(3137, 'approve_request', 'en', 'Approve Request'),
(3138, 'approve_request', 'ar', 'الموافقة على الطلب'),
(3139, 'reject_request', 'en', 'Reject Request'),
(3140, 'reject_request', 'ar', 'رفض الطلب'),
(3141, 'optional_notes_placeholder', 'en', 'Optional notes...'),
(3142, 'optional_notes_placeholder', 'ar', 'ملاحظات اختيارية...'),
(3143, 'provide_rejection_reason_placeholder', 'en', 'Please provide a reason for rejecting the request...'),
(3144, 'provide_rejection_reason_placeholder', 'ar', 'يرجى تقديم سبب لرفض الطلب...'),
(3145, 'select_action_validation', 'en', 'Please select an action (Approve or Reject).'),
(3146, 'select_action_validation', 'ar', 'الرجاء تحديد إجراء (موافقة أو رفض).'),
(3147, 'enter_rejection_reason_validation', 'en', 'Please enter a reason for rejecting the request.'),
(3148, 'enter_rejection_reason_validation', 'ar', 'الرجاء إدخال سبب لرفض الطلب.'),
(3149, 'request_failed_status', 'en', 'Request failed:'),
(3150, 'request_failed_status', 'ar', 'فشل الطلب:'),
(3151, 'what_to_update_title', 'en', 'What do you want to update?'),
(3152, 'what_to_update_title', 'ar', 'ماذا تريد أن تحدث؟'),
(3153, 'select_an_item_placeholder', 'en', 'Select an item'),
(3154, 'select_an_item_placeholder', 'ar', 'اختر عنصرا'),
(3155, 'you_need_to_select_something_validation', 'en', 'You need to select something!'),
(3156, 'you_need_to_select_something_validation', 'ar', 'يجب عليك اختيار شيء ما!'),
(3157, 'passport_number', 'en', 'Passport Number'),
(3158, 'passport_number', 'ar', 'رقم جواز السفر'),
(3159, 'passport_expiry_date', 'en', 'Passport Expiry Date'),
(3160, 'passport_expiry_date', 'ar', 'تاريخ انتهاء صلاحية جواز السفر'),
(3161, 'profile_picture', 'en', 'Profile Picture'),
(3162, 'profile_picture', 'ar', 'الصورة الشخصية'),
(3163, 'change_profile_picture_title', 'en', 'Change Profile Picture'),
(3164, 'change_profile_picture_title', 'ar', 'تغيير الصورة الشخصية'),
(3165, 'current_picture', 'en', 'Current Picture:'),
(3166, 'current_picture', 'ar', 'الصورة الحالية:'),
(3167, 'new_picture', 'en', 'New Picture:'),
(3168, 'new_picture', 'ar', 'الصورة الجديدة:'),
(3169, 'request_failed_try_again', 'en', 'Request failed. Please try again.'),
(3170, 'request_failed_try_again', 'ar', 'فشل الطلب. يرجى المحاولة مرة أخرى.'),
(3171, 'update_field_title', 'en', 'Update'),
(3172, 'update_field_title', 'ar', 'تحديث'),
(3173, 'your_current_value_is', 'en', 'Your current value is:'),
(3174, 'your_current_value_is', 'ar', 'قيمتك الحالية هي:'),
(3175, 'enter_new_field_placeholder', 'en', 'Enter new'),
(3176, 'enter_new_field_placeholder', 'ar', 'أدخل الجديد'),
(3177, 'submit_request', 'en', 'Submit Request'),
(3178, 'submit_request', 'ar', 'إرسال الطلب'),
(3179, 'select_date_for_calculation', 'en', 'Select Date for Calcuclation'),
(3180, 'select_date_for_calculation', 'ar', 'اختر تاريخًا للحساب'),
(3181, 'yes_select', 'en', 'Yes, Select!'),
(3182, 'yes_select', 'ar', 'نعم، اختر!'),
(3183, 'select_date_for_eos_validation', 'en', 'Please select date for EOS.'),
(3184, 'select_date_for_eos_validation', 'ar', 'الرجاء تحديد تاريخ نهاية الخدمة.'),
(3185, 'assign_new_asset', 'en', 'Assign New Asset'),
(3186, 'assign_new_asset', 'ar', 'تعيين أصل جديد'),
(3187, 'serial_number_identifier', 'en', 'Serial Number / Identifier'),
(3188, 'serial_number_identifier', 'ar', 'الرقم التسلسلي / المعرف'),
(3189, 'select_an_asset', 'en', 'Select an asset...'),
(3190, 'select_an_asset', 'ar', 'اختر أصلاً...'),
(3191, 'serial_placeholder', 'en', 'e.g., SN-12345, Plate No., IMEI'),
(3192, 'serial_placeholder', 'ar', 'مثال: SN-12345، رقم اللوحة، IMEI'),
(3193, 'description_placeholder', 'en', 'e.g., Dell Latitude 5420, iPhone 13 Pro, Zain SIM'),
(3194, 'description_placeholder', 'ar', 'مثال: Dell Latitude 5420، iPhone 13 Pro، شريحة زين'),
(3195, 'assign', 'en', 'Assign'),
(3196, 'assign', 'ar', 'تعيين'),
(3197, 'select_asset_type_validation', 'en', 'Please select an Asset type.'),
(3198, 'select_asset_type_validation', 'ar', 'الرجاء تحديد نوع الأصل.'),
(3199, 'enter_asset_identity_serial_validation', 'en', 'Please enter asset identity or Serial.'),
(3200, 'enter_asset_identity_serial_validation', 'ar', 'الرجاء إدخال هوية الأصل أو الرقم التسلسلي.'),
(3201, 'select_assigned_date_validation', 'en', 'Please select an assigned date.'),
(3202, 'select_assigned_date_validation', 'ar', 'الرجاء تحديد تاريخ التعيين.'),
(3203, 'error_title', 'en', 'Error!'),
(3204, 'error_title', 'ar', 'خطأ!'),
(3205, 'unexpected_error', 'en', 'An unexpected error occurred.'),
(3206, 'unexpected_error', 'ar', 'حدث خطأ غير متوقع.'),
(3207, 'could_not_load_asset_types', 'en', 'Could not load asset types from the server. Please add assets in the database first.'),
(3208, 'could_not_load_asset_types', 'ar', 'تعذر تحميل أنواع الأصول من الخادم. يرجى إضافة الأصول في قاعدة البيانات أولاً.'),
(3209, 'failed_to_connect_for_asset_types', 'en', 'Failed to connect to the server to get asset types.'),
(3210, 'failed_to_connect_for_asset_types', 'ar', 'فشل الاتصال بالخادم للحصول على أنواع الأصول.'),
(3211, 'return_asset', 'en', 'Return Asset'),
(3212, 'return_asset', 'ar', 'إرجاع الأصل'),
(3213, 'return_status', 'en', 'Return Status'),
(3214, 'return_status', 'ar', 'حالة الإرجاع'),
(3215, 'proof_of_return', 'en', 'Proof of Return (Signed Form)'),
(3216, 'proof_of_return', 'ar', 'إثبات الإرجاع (نموذج موقع)'),
(3217, 'select_status', 'en', 'Select status'),
(3218, 'select_status', 'ar', 'اختر الحالة'),
(3219, 'select_return_status_validation', 'en', 'Please select return status.'),
(3220, 'select_return_status_validation', 'ar', 'الرجاء تحديد حالة الإرجاع.'),
(3221, 'select_return_date_validation', 'en', 'Please select an return date.'),
(3222, 'select_return_date_validation', 'ar', 'الرجاء تحديد تاريخ الإرجاع.'),
(3223, 'select_proof_of_return_file_validation', 'en', 'Please select file, prof of return.'),
(3224, 'select_proof_of_return_file_validation', 'ar', 'الرجاء تحديد ملف إثبات الإرجاع.'),
(3225, 'add_new_voucher_title', 'en', 'Add New Voucher'),
(3226, 'add_new_voucher_title', 'ar', 'إضافة قسيمة جديدة'),
(3227, 'select_employee_validation', 'en', 'Please select employee.'),
(3228, 'select_employee_validation', 'ar', 'الرجاء اختيار الموظف.'),
(3229, 'select_voucher_type_validation', 'en', 'Please select voucher type.'),
(3230, 'select_voucher_type_validation', 'ar', 'الرجاء اختيار نوع القسيمة.'),
(3231, 'enter_voucher_amount_validation', 'en', 'Please enter voucher amount.'),
(3232, 'enter_voucher_amount_validation', 'ar', 'الرجاء إدخال مبلغ القسيمة.'),
(3233, 'enter_voucher_details_validation', 'en', 'Please enter details for voucher.'),
(3234, 'enter_voucher_details_validation', 'ar', 'الرجاء إدخال تفاصيل القسيمة.'),
(3235, 'add_rejected_note_title', 'en', 'Add Rejected Note.'),
(3236, 'add_rejected_note_title', 'ar', 'إضافة ملاحظة مرفوضة.'),
(3237, 'yes_add', 'en', 'Yes, Add!'),
(3238, 'yes_add', 'ar', 'نعم، أضف!'),
(3239, 'enter_rejected_note_validation', 'en', 'Please enter rejected note.'),
(3240, 'enter_rejected_note_validation', 'ar', 'الرجاء إدخال ملاحظة الرفض.'),
(3241, 'status_not_selected', 'en', 'Status not selected'),
(3242, 'status_not_selected', 'ar', 'لم يتم تحديد الحالة'),
(3243, 'approve', 'en', 'Approve'),
(3244, 'approve', 'ar', 'موافقة'),
(3245, 'reject', 'en', 'Reject'),
(3246, 'reject', 'ar', 'رفض'),
(3247, 'add_total_amount_title', 'en', 'Add Total Amount.'),
(3248, 'add_total_amount_title', 'ar', 'إضافة المبلغ الإجمالي.'),
(3249, 'enter_total_invoice_amount_validation', 'en', 'Please enter total invoice amount.'),
(3250, 'enter_total_invoice_amount_validation', 'ar', 'الرجاء إدخال المبلغ الإجمالي للفاتورة.'),
(3251, 'approve_total_amount_title', 'en', 'Approve Total Amount'),
(3252, 'approve_total_amount_title', 'ar', 'الموافقة على المبلغ الإجمالي'),
(3253, 'days_suffix', 'en', ' Days'),
(3254, 'days_suffix', 'ar', ' أيام'),
(3255, 'day_suffix', 'en', ' Day'),
(3256, 'day_suffix', 'ar', ' يوم'),
(3257, 'loading_employee_info', 'en', 'Loading Employee Info...'),
(3258, 'loading_employee_info', 'ar', 'جاري تحميل معلومات الموظف...'),
(3259, 'leave_application_for', 'en', 'Leave Application for'),
(3260, 'leave_application_for', 'ar', 'طلب إجازة لـ'),
(3261, 'employee_not_found', 'en', 'Employee Not Found'),
(3262, 'employee_not_found', 'ar', 'لم يتم العثور على الموظف'),
(3263, 'error_fetching_data', 'en', 'Error Fetching Data'),
(3264, 'error_fetching_data', 'ar', 'خطأ في جلب البيانات'),
(3265, 'select_leave_type_placeholder', 'en', 'Select a leave type...'),
(3266, 'select_leave_type_placeholder', 'ar', 'اختر نوع الإجازة...'),
(3267, 'select_leave_type_validation', 'en', 'Please select a leave type.'),
(3268, 'select_leave_type_validation', 'ar', 'الرجاء اختيار نوع الإجازة.'),
(3269, 'start_date_required', 'en', 'Start date is required.'),
(3270, 'start_date_required', 'ar', 'تاريخ البدء مطلوب.'),
(3271, 'end_date_required', 'en', 'End date is required.'),
(3272, 'end_date_required', 'ar', 'تاريخ الانتهاء مطلوب.'),
(3273, 'end_date_before_start_date_validation', 'en', 'End date cannot be before the start date.'),
(3274, 'end_date_before_start_date_validation', 'ar', 'لا يمكن أن يكون تاريخ الانتهاء قبل تاريخ البدء.'),
(3275, 'destination_required_validation', 'en', 'Destination is required for a Business Trip.'),
(3276, 'destination_required_validation', 'ar', 'الوجهة مطلوبة لرحلة عمل.'),
(3277, 'reason_required_validation', 'en', 'Reason / Notes field is required for this leave type.'),
(3278, 'reason_required_validation', 'ar', 'حقل السبب / الملاحظات مطلوب لهذا النوع من الإجازات.'),
(3279, 'apply_vacation_info_title', 'en', 'Apply vacation information.'),
(3280, 'apply_vacation_info_title', 'ar', 'تقديم معلومات الإجازة.'),
(3281, 'select_vacation_type_validation', 'en', 'Please select a Vacation type.'),
(3282, 'select_vacation_type_validation', 'ar', 'الرجاء اختيار نوع الإجازة.'),
(3283, 'start_return_date_required_validation', 'en', 'Start Date and Return Date are required.'),
(3284, 'start_return_date_required_validation', 'ar', 'تاريخ البدء وتاريخ العودة مطلوبان.'),
(3285, 'replacement_person_required_validation', 'en', 'Replacement person is required.'),
(3286, 'replacement_person_required_validation', 'ar', 'الشخص البديل مطلوب.'),
(3287, 'add_note_to_employee_title', 'en', 'Add Note to Employee'),
(3288, 'add_note_to_employee_title', 'ar', 'إضافة ملاحظة للموظف'),
(3289, 'enter_notes_validation', 'en', 'Please enter notes'),
(3290, 'enter_notes_validation', 'ar', 'الرجاء إدخال الملاحظات'),
(3291, 'failed_to_update_password', 'en', 'Failed to update password'),
(3292, 'failed_to_update_password', 'ar', 'فشل تحديث كلمة المرور'),
(3293, 'error_no_connection', 'en', 'Not connect. Verify Network.'),
(3294, 'error_no_connection', 'ar', 'غير متصل. تحقق من الشبكة.'),
(3295, 'error_404', 'en', 'Requested page not found. [404]'),
(3296, 'error_404', 'ar', 'الصفحة المطلوبة غير موجودة. [404]'),
(3297, 'error_500', 'en', 'Internal Server Error [500].'),
(3298, 'error_500', 'ar', 'خطأ داخلي في الخادم [500].'),
(3299, 'error_parser', 'en', 'Requested JSON parse failed.'),
(3300, 'error_parser', 'ar', 'فشل تحليل JSON المطلوب.'),
(3301, 'error_timeout', 'en', 'Time out error.'),
(3302, 'error_timeout', 'ar', 'خطأ في المهلة.'),
(3303, 'error_abort', 'en', 'Ajax request aborted.'),
(3304, 'error_abort', 'ar', 'تم إحباط طلب Ajax.'),
(3305, 'error_uncaught', 'en', 'Uncaught Error.'),
(3306, 'error_uncaught', 'ar', 'خطأ غير متوقع.'),
(3307, 'success_title', 'en', 'Success!'),
(3308, 'success_title', 'ar', 'نجاح!'),
(3309, 'copy_success_message', 'en', 'The text has been copied successfully.'),
(3310, 'copy_success_message', 'ar', 'تم نسخ النص بنجاح.'),
(3311, 'enter_valid_value_alert', 'en', 'Please enter valid value'),
(3312, 'enter_valid_value_alert', 'ar', 'الرجاء إدخال قيمة صالحة'),
(3313, 'amount_label', 'en', 'Amount:'),
(3314, 'amount_label', 'ar', 'المبلغ:'),
(3315, 'vat_percent_label', 'en', 'VAT, %:'),
(3316, 'vat_percent_label', 'ar', 'ضريبة القيمة المضافة، ٪:'),
(3317, 'operation_label', 'en', 'Operation:'),
(3318, 'operation_label', 'ar', 'العملية:'),
(3319, 'vat_added_label', 'en', 'VAT added:'),
(3320, 'vat_added_label', 'ar', 'تمت إضافة ضريبة القيمة المضافة:'),
(3321, 'gross_amount_label', 'en', 'Gross amount:'),
(3322, 'gross_amount_label', 'ar', 'المبلغ الإجمالي:'),
(3323, 'vat_excluded_label', 'en', 'VAT excluded:'),
(3324, 'vat_excluded_label', 'ar', 'ضريبة القيمة المضافة مستبعدة:'),
(3325, 'net_amount_label', 'en', 'Net amount:'),
(3326, 'net_amount_label', 'ar', 'صافي المبلغ:'),
(3327, 'password_no_match_alert', 'en', 'Confirm password not matching.'),
(3328, 'password_no_match_alert', 'ar', 'تأكيد كلمة المرور غير متطابق.'),
(3329, 'cancel', 'en', 'Cancel'),
(3330, 'cancel', 'ar', 'إلغاء'),
(3331, 'local_vacation', 'en', 'Local vacation'),
(3332, 'local_vacation', 'ar', 'إجازة محلية'),
(3333, 'select_vacation_type', 'en', 'Select vacation type'),
(3334, 'select_vacation_type', 'ar', 'حدد نوع الإجازة'),
(3335, 'emergency_vacation', 'en', 'Emergency vacation'),
(3336, 'emergency_vacation', 'ar', 'إجازة طارئة'),
(3337, 'fly', 'en', 'Fly');
INSERT INTO `translations` (`translation_id`, `lang_key`, `lang_code`, `translation`) VALUES
(3338, 'fly', 'ar', 'سافر'),
(3339, 'annual_vacation', 'en', 'Annual vacation'),
(3340, 'annual_vacation', 'ar', 'الإجازة السنوية'),
(3341, 'employee_id', 'en', 'Employee ID'),
(3342, 'employee_id', 'ar', 'رقم الموظفى'),
(26621, 'leave_type', 'en', 'Leave Type'),
(26622, 'leave_type', 'ar', 'نوع الإجازة'),
(26623, 'total_days', 'en', 'Total Days'),
(26624, 'total_days', 'ar', 'إجمالي الأيام'),
(26625, 'auto_calculated_placeholder', 'en', 'Auto-calculated'),
(26626, 'auto_calculated_placeholder', 'ar', 'يتم حسابه تلقائيًا'),
(26627, 'destination', 'en', 'Destination'),
(26628, 'destination', 'ar', 'الوجهة'),
(26629, 'destination_placeholder', 'en', 'e.g., Riyadh, KSA'),
(26630, 'destination_placeholder', 'ar', 'مثال: الرياض، المملكة العربية السعودية'),
(26631, 'reason_notes', 'en', 'Reason / Notes'),
(26632, 'reason_notes', 'ar', 'السبب / ملاحظات'),
(26633, 'reason_placeholder', 'en', 'Provide a brief reason for your leave...'),
(26634, 'reason_placeholder', 'ar', 'قدم سببًا موجزًا لإجازتك...'),
(26635, 'attach_document_optional', 'en', 'Attach Document (Optional)'),
(26636, 'attach_document_optional', 'ar', 'إرفاق مستند (اختياري)'),
(26637, 'attachment_example', 'en', 'e.g., Medical certificate for sick leave.'),
(26638, 'attachment_example', 'ar', 'مثال: شهادة طبية للإجازة المرضية.'),
(26639, 'active', 'en', 'Active'),
(26640, 'active', 'ar', 'نشط'),
(26641, 'inactive', 'en', 'Inactive'),
(26642, 'inactive', 'ar', 'غير نشط'),
(26643, 'name_in_english', 'en', 'Name in English'),
(26644, 'name_in_english', 'ar', 'الاسم باللغة الإنجليزية'),
(26645, 'name_in_arabic', 'en', 'Name in Arabic'),
(26646, 'name_in_arabic', 'ar', 'الاسم باللغة العربية'),
(26647, 'select_price_type', 'en', 'Select Price Type'),
(26648, 'select_price_type', 'ar', 'اختر نوع السعر'),
(26649, 'select_category', 'en', 'Select Category'),
(26650, 'select_category', 'ar', 'اختر الفئة'),
(26651, 'large_price', 'en', 'Larg Price'),
(26652, 'large_price', 'ar', 'سعر كبير'),
(26653, 'small_price', 'en', 'Small Price'),
(26654, 'small_price', 'ar', 'سعر صغير'),
(26655, 'large_calorie', 'en', 'Larg Calorie'),
(26656, 'large_calorie', 'ar', 'سعرات حرارية كبيرة'),
(26657, 'small_calorie', 'en', 'Small Calorie'),
(26658, 'small_calorie', 'ar', 'سعرات حرارية صغيرة'),
(26659, 'select_item_image', 'en', 'Select Item Image'),
(26660, 'select_item_image', 'ar', 'اختر صورة العنصر'),
(26661, 'maker_name', 'en', 'Maker Name'),
(26662, 'maker_name', 'ar', 'اسم الصانع'),
(26663, 'model', 'en', 'Model'),
(26664, 'model', 'ar', 'الموديل'),
(26665, 'enter_model_placeholder', 'en', 'Enter model'),
(26666, 'enter_model_placeholder', 'ar', 'أدخل الموديل'),
(26667, 'made_year', 'en', 'Made Year'),
(26668, 'made_year', 'ar', 'سنة الصنع'),
(26669, 'enter_made_year_placeholder', 'en', 'Enter made year'),
(26670, 'enter_made_year_placeholder', 'ar', 'أدخل سنة الصنع'),
(26671, 'type_of_car', 'en', 'Type of car'),
(26672, 'type_of_car', 'ar', 'نوع السيارة'),
(26673, 'bus', 'en', 'Bus'),
(26674, 'bus', 'ar', 'حافلة'),
(26675, 'car', 'en', 'Car'),
(26676, 'car', 'ar', 'سيارة'),
(26677, 'dyna', 'en', 'Dyna'),
(26678, 'dyna', 'ar', 'دينا'),
(26679, 'fork_lift', 'en', 'Fork Lift'),
(26680, 'fork_lift', 'ar', 'رافعة شوكية'),
(26681, 'jeep', 'en', 'Jeep'),
(26682, 'jeep', 'ar', 'جيب'),
(26683, 'pick_up', 'en', 'Pick Up'),
(26684, 'pick_up', 'ar', 'بيك أب'),
(26685, 'truck', 'en', 'Truck'),
(26686, 'truck', 'ar', 'شاحنة'),
(26687, 'van', 'en', 'Van'),
(26688, 'van', 'ar', 'فان'),
(26689, 'plate_no', 'en', 'Plate No.'),
(26690, 'plate_no', 'ar', 'رقم اللوحة'),
(26691, 'enter_remarks_placeholder', 'en', 'Enter remarks'),
(26692, 'enter_remarks_placeholder', 'ar', 'أدخل ملاحظات'),
(26693, 'item_name', 'en', 'Item Name'),
(26694, 'item_name', 'ar', 'اسم العنصر'),
(26695, 'location', 'en', 'Location'),
(26696, 'location', 'ar', 'الموقع'),
(26697, 'quantity', 'en', 'Quantity'),
(26698, 'quantity', 'ar', 'الكمية'),
(26699, 'unit_cost', 'en', 'Unit Cost'),
(26700, 'unit_cost', 'ar', 'تكلفة الوحدة'),
(26701, 'item_value', 'en', 'Item Value'),
(26702, 'item_value', 'ar', 'قيمة العنصر'),
(26703, 'vat_opt', 'en', 'Vat Opt.'),
(26704, 'vat_opt', 'ar', 'خيار ضريبة القيمة المضافة'),
(26705, 'include', 'en', 'Include'),
(26706, 'include', 'ar', 'شامل'),
(26707, 'exclude', 'en', 'Exclude'),
(26708, 'exclude', 'ar', 'غير شامل'),
(26709, 'vat_rate_percent', 'en', 'Vat Rate %'),
(26710, 'vat_rate_percent', 'ar', 'نسبة ضريبة القيمة المضافة %'),
(26711, 'vat_val_percent', 'en', 'Vat Val. %'),
(26712, 'vat_val_percent', 'ar', 'قيمة ضريبة القيمة المضافة %'),
(26713, 'discount', 'en', 'Discount'),
(26714, 'discount', 'ar', 'خصم'),
(26715, 'total', 'en', 'Total'),
(26716, 'total', 'ar', 'الإجمالي'),
(26717, 'sub_title', 'en', 'Sub-Title'),
(26718, 'sub_title', 'ar', 'عنوان فرعي'),
(26719, 'sub_type', 'en', 'Sub. Type *'),
(26720, 'sub_type', 'ar', 'نوع فرعي *'),
(26721, 'tally_id', 'en', 'Tally ID.'),
(26722, 'tally_id', 'ar', 'معرف تالي.'),
(26723, 'injazat_id', 'en', 'Injazat ID.'),
(26724, 'injazat_id', 'ar', 'معرف إنجازات.'),
(26725, 'description_in_english', 'en', 'Description in English'),
(26726, 'description_in_english', 'ar', 'الوصف باللغة الإنجليزية'),
(26727, 'description_in_arabic', 'en', 'Description in Arabic'),
(26728, 'description_in_arabic', 'ar', 'الوصف باللغة العربية'),
(26729, 'category_type', 'en', 'Category Type'),
(26730, 'category_type', 'ar', 'نوع الفئة'),
(26731, 'location_name', 'en', 'Location Name'),
(26732, 'location_name', 'ar', 'اسم الموقع'),
(26733, 'latitude', 'en', 'Latitude'),
(26734, 'latitude', 'ar', 'خط العرض'),
(26735, 'enter_latitude_placeholder', 'en', 'Enter google latitude'),
(26736, 'enter_latitude_placeholder', 'ar', 'أدخل خط عرض جوجل'),
(26737, 'longitude', 'en', 'Longitude'),
(26738, 'longitude', 'ar', 'خط الطول'),
(26739, 'enter_longitude_placeholder', 'en', 'Enter google longitude'),
(26740, 'enter_longitude_placeholder', 'ar', 'أدخل خط طول جوجل'),
(26741, 'balady_license_exp', 'en', 'Balady License Exp.'),
(26742, 'balady_license_exp', 'ar', 'انتهاء رخصة البلدية.'),
(26743, 'enter_balady_license_exp_placeholder', 'en', 'Enter Balady License Exp.'),
(26744, 'enter_balady_license_exp_placeholder', 'ar', 'أدخل تاريخ انتهاء رخصة البلدية.'),
(26745, 'balady_license_no', 'en', 'Balady License No.'),
(26746, 'balady_license_no', 'ar', 'رقم رخصة البلدية.'),
(26747, 'enter_balady_license_no_placeholder', 'en', 'Enter Balady License No.'),
(26748, 'enter_balady_license_no_placeholder', 'ar', 'أدخل رقم رخصة البلدية.'),
(26749, 'select_department', 'en', 'Select Department'),
(26750, 'select_department', 'ar', 'اختر القسم'),
(26751, 'camera_in', 'en', 'Camera (IN)'),
(26752, 'camera_in', 'ar', 'كاميرا (داخلية)'),
(26753, 'enter_camera_in_placeholder', 'en', 'Enter camera Inside'),
(26754, 'enter_camera_in_placeholder', 'ar', 'أدخل الكاميرا الداخلية'),
(26755, 'camera_out', 'en', 'Camera (OUT)'),
(26756, 'camera_out', 'ar', 'كاميرا (خارجية)'),
(26757, 'enter_camera_out_placeholder', 'en', 'Enter camera outside'),
(26758, 'enter_camera_out_placeholder', 'ar', 'أدخل الكاميرا الخارجية'),
(26759, 'total_building_size_m', 'en', 'Total Bulding Size (M)'),
(26760, 'total_building_size_m', 'ar', 'إجمالي مساحة المبنى (م)'),
(26761, 'enter_total_building_size_placeholder', 'en', 'Enter total bulding base in metters'),
(26762, 'enter_total_building_size_placeholder', 'ar', 'أدخل إجمالي قاعدة المبنى بالأمتار'),
(26763, 'building_base', 'en', 'Bulding Base'),
(26764, 'building_base', 'ar', 'قاعدة المبنى'),
(26765, 'enter_building_base_placeholder', 'en', 'Enter bulding base'),
(26766, 'enter_building_base_placeholder', 'ar', 'أدخل قاعدة المبنى'),
(26767, 'building_size_l_w', 'en', 'Bulding Size (L * W)'),
(26768, 'building_size_l_w', 'ar', 'مساحة المبنى (طول * عرض)'),
(26769, 'enter_building_size_l_w_placeholder', 'en', 'Enter Bulding Size (L * W)'),
(26770, 'enter_building_size_l_w_placeholder', 'ar', 'أدخل مساحة المبنى (طول * عرض)'),
(26771, 'district', 'en', 'District'),
(26772, 'district', 'ar', 'الحي'),
(26773, 'enter_district_placeholder', 'en', 'Enter District'),
(26774, 'enter_district_placeholder', 'ar', 'أدخل الحي'),
(26775, 'municipality', 'en', 'Municipality'),
(26776, 'municipality', 'ar', 'البلدية'),
(26777, 'enter_municipality_placeholder', 'en', 'Enter Municipality name'),
(26778, 'enter_municipality_placeholder', 'ar', 'أدخل اسم البلدية'),
(26779, 'sub_municipality', 'en', 'Sub-municipality'),
(26780, 'sub_municipality', 'ar', 'بلدية فرعية'),
(26781, 'enter_sub_municipality_placeholder', 'en', 'Enter sub municipality name'),
(26782, 'enter_sub_municipality_placeholder', 'ar', 'أدخل اسم البلدية الفرعية'),
(26783, 'location_address', 'en', 'Location Address'),
(26784, 'location_address', 'ar', 'عنوان الموقع'),
(26785, 'enter_location_address_placeholder', 'en', 'Enter location address'),
(26786, 'enter_location_address_placeholder', 'ar', 'أدخل عنوان الموقع'),
(26787, 'select_driver', 'en', 'Select Driver'),
(26788, 'select_driver', 'ar', 'اختر سائق'),
(26789, 'new_meter_reading', 'en', 'New Meter Reading'),
(26790, 'new_meter_reading', 'ar', 'قراءة العداد الجديدة'),
(26791, 'old_meter_reading', 'en', 'Old Meter Reading'),
(26792, 'old_meter_reading', 'ar', 'قراءة العداد القديمة'),
(26793, 'diff_meter_reading', 'en', 'Diff. Meter Reading'),
(26794, 'diff_meter_reading', 'ar', 'فرق قراءة العداد'),
(26795, 'add', 'en', 'Add'),
(26796, 'add', 'ar', 'إضافة'),
(26797, 'description_for_maintenance', 'en', 'Description for maintenance'),
(26798, 'description_for_maintenance', 'ar', 'وصف الصيانة'),
(26799, 'type_name', 'en', 'Type name'),
(26800, 'type_name', 'ar', 'اسم النوع'),
(26801, 'licence', 'en', 'Licence'),
(26802, 'licence', 'ar', 'رخصة'),
(26803, 'insurance', 'en', 'Insurance'),
(26804, 'insurance', 'ar', 'تأمين'),
(26805, 'mvpi', 'en', 'MVPI'),
(26806, 'mvpi', 'ar', 'الفحص الدوري'),
(26807, 'issue_date', 'en', 'Issue Date'),
(26808, 'issue_date', 'ar', 'تاريخ الإصدار'),
(26809, 'select_issue_date_placeholder', 'en', 'Select issue date'),
(26810, 'select_issue_date_placeholder', 'ar', 'اختر تاريخ الإصدار'),
(26811, 'expiry_date', 'en', 'Expiry Date'),
(26812, 'expiry_date', 'ar', 'تاريخ الانتهاء'),
(26813, 'select_expiry_date_placeholder', 'en', 'Select expiry date'),
(26814, 'select_expiry_date_placeholder', 'ar', 'اختر تاريخ الانتهاء'),
(26815, 'have_attachments', 'en', 'Have Attachments'),
(26816, 'have_attachments', 'ar', 'يوجد مرفقات'),
(26817, 'no_attachment', 'en', 'No Attachment'),
(26818, 'no_attachment', 'ar', 'لا يوجد مرفقات'),
(26819, 'browse_files', 'en', 'Browse files'),
(26820, 'browse_files', 'ar', 'تصفح الملفات'),
(26821, 'select_driver_name', 'en', 'Select driver name'),
(26822, 'select_driver_name', 'ar', 'اختر اسم السائق'),
(26823, 'customer_name', 'en', 'Customer Name'),
(26824, 'customer_name', 'ar', 'اسم العميل'),
(26825, 'injazat_no', 'en', 'Injazat No.'),
(26826, 'injazat_no', 'ar', 'رقم إنجازات'),
(26827, 'mobile_no', 'en', 'Mobile No.'),
(26828, 'mobile_no', 'ar', 'رقم الجوال'),
(26829, 'account_no', 'en', 'Account No.'),
(26830, 'account_no', 'ar', 'رقم الحساب'),
(26831, 'card_expire', 'en', 'Card Expire'),
(26832, 'card_expire', 'ar', 'انتهاء صلاحية البطاقة'),
(26833, 'for_shop', 'en', 'For Shop'),
(26834, 'for_shop', 'ar', 'للمحل'),
(26835, 'new_injazat_no', 'en', 'New Injazat No.'),
(26836, 'new_injazat_no', 'ar', 'رقم إنجازات جديد'),
(26837, 'location_owner_name', 'en', 'Location Owner Name'),
(26838, 'location_owner_name', 'ar', 'اسم مالك الموقع'),
(26839, 'enter_owner_name_placeholder', 'en', 'Enter owner name'),
(26840, 'enter_owner_name_placeholder', 'ar', 'أدخل اسم المالك'),
(26841, 'owner_number', 'en', 'Owner Number'),
(26842, 'owner_number', 'ar', 'رقم المالك'),
(26843, 'enter_owner_number_placeholder', 'en', 'Enter Owner number'),
(26844, 'enter_owner_number_placeholder', 'ar', 'أدخل رقم المالك'),
(26845, 'owner_email', 'en', 'Owner Email'),
(26846, 'owner_email', 'ar', 'البريد الإلكتروني للمالك'),
(26847, 'enter_owner_email_placeholder', 'en', 'Enter owner email'),
(26848, 'enter_owner_email_placeholder', 'ar', 'أدخل البريد الإلكتروني للمالك'),
(26849, 'contract_no', 'en', 'Contract No.'),
(26850, 'contract_no', 'ar', 'رقم العقد'),
(26851, 'enter_contract_no_placeholder', 'en', 'Enter contract no'),
(26852, 'enter_contract_no_placeholder', 'ar', 'أدخل رقم العقد'),
(26853, 'contract_starting_date', 'en', 'Contract Starting Date'),
(26854, 'contract_starting_date', 'ar', 'تاريخ بدء العقد'),
(26855, 'enter_contract_start_date_placeholder', 'en', 'Enter Contract Start Date'),
(26856, 'enter_contract_start_date_placeholder', 'ar', 'أدخل تاريخ بدء العقد'),
(26857, 'contract_ending_date', 'en', 'Contract Ending Date'),
(26858, 'contract_ending_date', 'ar', 'تاريخ انتهاء العقد'),
(26859, 'enter_contract_ending_date_placeholder', 'en', 'Enter Contract Ending Date'),
(26860, 'enter_contract_ending_date_placeholder', 'ar', 'أدخل تاريخ انتهاء العقد'),
(26861, 'amount_of_rent', 'en', 'Amount of Rent'),
(26862, 'amount_of_rent', 'ar', 'مبلغ الإيجار'),
(26863, 'enter_amount_of_rent_placeholder', 'en', 'Enter Amount of Rent'),
(26864, 'enter_amount_of_rent_placeholder', 'ar', 'أدخل مبلغ الإيجار'),
(26865, 'amount_of_services', 'en', 'Amount of Services'),
(26866, 'amount_of_services', 'ar', 'مبلغ الخدمات'),
(26867, 'enter_amount_of_services_placeholder', 'en', 'Enter Amount of Services'),
(26868, 'enter_amount_of_services_placeholder', 'ar', 'أدخل مبلغ الخدمات'),
(26869, 'amount_of_electricity', 'en', 'Amount of Electric City'),
(26870, 'amount_of_electricity', 'ar', 'مبلغ الكهرباء'),
(26871, 'enter_amount_of_electricity_placeholder', 'en', 'Enter Amount of Electric City'),
(26872, 'enter_amount_of_electricity_placeholder', 'ar', 'أدخل مبلغ الكهرباء'),
(26873, 'amount_of_water', 'en', 'Amount of Water'),
(26874, 'amount_of_water', 'ar', 'مبلغ المياه'),
(26875, 'enter_amount_of_water_placeholder', 'en', 'Enter Amount of Water'),
(26876, 'enter_amount_of_water_placeholder', 'ar', 'أدخل مبلغ المياه'),
(26877, 'amount_of_insurance', 'en', 'Amount of Incuranse'),
(26878, 'amount_of_insurance', 'ar', 'مبلغ التأمين'),
(26879, 'enter_amount_of_insurance_placeholder', 'en', 'Enter Amount of Incuranse'),
(26880, 'enter_amount_of_insurance_placeholder', 'ar', 'أدخل مبلغ التأمين'),
(26881, 'enter_others_placeholder', 'en', 'Enter others'),
(26882, 'enter_others_placeholder', 'ar', 'أدخل أخرى'),
(26883, 'enter_new_password', 'en', 'Enter new password'),
(26884, 'enter_new_password', 'ar', 'أدخل كلمة المرور الجديدة'),
(26885, 'confirm_password', 'en', 'Confirm password'),
(26886, 'confirm_password', 'ar', 'تأكيد كلمة المرور'),
(26887, 'email_required', 'en', 'Email *'),
(26888, 'email_required', 'ar', 'البريد الإلكتروني *'),
(26889, 'employee_type_required', 'en', 'Employee Type *'),
(26890, 'employee_type_required', 'ar', 'نوع الموظف *'),
(26891, 'manager', 'en', 'Manager'),
(26892, 'manager', 'ar', 'مدير'),
(26893, 'assistant', 'en', 'Assistant'),
(26894, 'assistant', 'ar', 'مساعد'),
(26895, 'full_name', 'en', 'Full Name'),
(26896, 'full_name', 'ar', 'الاسم الكامل'),
(26897, 'username', 'en', 'Username'),
(26898, 'username', 'ar', 'اسم المستخدم'),
(26899, 'type_of_permission', 'en', 'Type of Permission'),
(26900, 'type_of_permission', 'ar', 'نوع الإذن'),
(26901, 'administrator', 'en', 'Administrator'),
(26902, 'administrator', 'ar', 'مسؤول'),
(26903, 'department_manager', 'en', 'Department Manager'),
(26904, 'department_manager', 'ar', 'مدير القسم'),
(26905, 'employee', 'en', 'Employee'),
(26906, 'employee', 'ar', 'موظف'),
(26907, 'general_manager', 'en', 'General Manager'),
(26908, 'general_manager', 'ar', 'مدير عام'),
(26909, 'hr', 'en', 'Human Resource'),
(26910, 'hr', 'ar', 'الموارد البشرية'),
(26911, 'email_password', 'en', 'Email Password'),
(26912, 'email_password', 'ar', 'كلمة مرور البريد الإلكتروني'),
(26913, 'changing_password', 'en', 'Changing Password'),
(26914, 'changing_password', 'ar', 'تغيير كلمة المرور'),
(26915, 'update_password', 'en', 'Update Password'),
(26916, 'update_password', 'ar', 'تحديث كلمة المرور'),
(26917, 'select_join_date_placeholder', 'en', 'Select Join Date'),
(26918, 'select_join_date_placeholder', 'ar', 'اختر تاريخ الانضمام'),
(26919, 'select_end_date_placeholder', 'en', 'Select end date'),
(26920, 'select_end_date_placeholder', 'ar', 'اختر تاريخ الانتهاء'),
(26921, 'add_link_address', 'en', 'Add Link Address'),
(26922, 'add_link_address', 'ar', 'إضافة عنوان الرابط'),
(26923, 'add_portfolio_title', 'en', 'Add Portfolio title (*)'),
(26924, 'add_portfolio_title', 'ar', 'إضافة عنوان معرض الأعمال (*)'),
(26925, 'select_attachment_file', 'en', 'Select attachment file (*)'),
(26926, 'select_attachment_file', 'ar', 'اختر ملف المرفق (*)'),
(26927, 'description_of_portfolio', 'en', 'Description of portfolio (*)'),
(26928, 'description_of_portfolio', 'ar', 'وصف معرض الأعمال (*)'),
(26929, 'please_select', 'en', 'Please select'),
(26930, 'please_select', 'ar', 'الرجاء الاختيار'),
(26931, 'select_from_list', 'en', 'Select from List'),
(26932, 'select_from_list', 'ar', 'اختر من القائمة'),
(26933, 'iqama_expiry', 'en', 'Iqama Expiry'),
(26934, 'iqama_expiry', 'ar', 'انتهاء الإقامة'),
(26935, 't_shirt_size', 'en', 'T-Size'),
(26936, 't_shirt_size', 'ar', 'مقاس التيشيرت'),
(26937, 'bank_account', 'en', 'Bank Account'),
(26938, 'bank_account', 'ar', 'الحساب البنكي'),
(26939, 'total_invoices_amount', 'en', 'Total invoice\'s amount'),
(26940, 'total_invoices_amount', 'ar', 'إجمالي مبلغ الفاتورة'),
(26941, 'approve_total_invoices_amount', 'en', 'Approve total invoice\'s amount'),
(26942, 'approve_total_invoices_amount', 'ar', 'الموافقة على إجمالي مبلغ الفاتورة'),
(26943, 'select_start_date_placeholder', 'en', 'Select Start date'),
(26944, 'select_start_date_placeholder', 'ar', 'اختر تاريخ البدء'),
(26945, 'select_return_date_placeholder', 'en', 'Select Return Date'),
(26946, 'select_return_date_placeholder', 'ar', 'اختر تاريخ العودة'),
(26947, 'replacement_person', 'en', 'Replacement Person'),
(26948, 'replacement_person', 'ar', 'الشخص البديل'),
(26949, 'select_date_for_eos', 'en', 'Select date for EOS'),
(26950, 'select_date_for_eos', 'ar', 'اختر تاريخ نهاية الخدمة'),
(26951, 'enter_note', 'en', 'Enter note'),
(26952, 'enter_note', 'ar', 'أدخل ملاحظة'),
(26953, 'error_json_parse', 'en', 'Requested JSON parse failed.'),
(26954, 'error_json_parse', 'ar', 'فشل تحليل JSON المطلوب.'),
(26955, 'error_ajax_aborted', 'en', 'Ajax request aborted.'),
(26956, 'error_ajax_aborted', 'ar', 'تم إحباط طلب Ajax.'),
(26957, 'text_copied_successfully', 'en', 'The text has been copied successfully.'),
(26958, 'text_copied_successfully', 'ar', 'تم نسخ النص بنجاح.'),
(26959, 'enter_valid_value_validation', 'en', 'Please enter valid value'),
(26960, 'enter_valid_value_validation', 'ar', 'الرجاء إدخال قيمة صالحة'),
(26961, 'confirm_password_not_matching', 'en', 'Confirm password not matching.'),
(26962, 'confirm_password_not_matching', 'ar', 'تأكيد كلمة المرور غير متطابق.'),
(26963, 'request_failed_title', 'en', 'Request Failed!'),
(26964, 'request_failed_title', 'ar', 'فشل الطلب!'),
(26965, 'server_network_error', 'en', 'A server or network error occurred'),
(26966, 'server_network_error', 'ar', 'حدث خطأ في الخادم أو الشبكة'),
(26967, 'loading_loan_details', 'en', 'Loading Loan Details...'),
(26968, 'loading_loan_details', 'ar', 'جاري تحميل تفاصيل القرض...'),
(26969, 'please_wait', 'en', 'Please wait.'),
(26970, 'please_wait', 'ar', 'الرجاء الانتظار.'),
(26971, 'end_of_service_benefit', 'en', 'End of Service Benefit'),
(26972, 'end_of_service_benefit', 'ar', 'مكافأة نهاية الخدمة'),
(26973, 'total_calculated', 'en', 'Total Calculated:'),
(26974, 'total_calculated', 'ar', 'إجمالي المحسوب:'),
(26975, 'max_loan_amount_40_percent', 'en', 'Maximum Loan Amount (40%):'),
(26976, 'max_loan_amount_40_percent', 'ar', 'الحد الأقصى لمبلغ القرض (40٪):'),
(26977, 'max_loan_amount', 'en', 'Maximum Loan Amount:'),
(26978, 'max_loan_amount', 'ar', 'الحد الأقصى لمبلغ القرض:'),
(26979, 'month', 'en', 'Month'),
(26980, 'month', 'ar', 'شهر'),
(26981, 'months', 'en', 'Months'),
(26982, 'months', 'ar', 'أشهر'),
(26983, 'apply_for_loan_title', 'en', 'Apply for Loan'),
(26984, 'apply_for_loan_title', 'ar', 'التقدم بطلب للحصول على قرض'),
(26985, 'notice', 'en', 'Notice'),
(26986, 'notice', 'ar', 'ملاحظة'),
(26987, 'eos_based_amount_notice', 'en', 'This calcuclated amount based on your end of service.'),
(26988, 'eos_based_amount_notice', 'ar', 'هذا المبلغ المحسوب يعتمد على نهاية خدمتك.'),
(26989, 'loan_amount_label', 'en', 'Loan Amount'),
(26990, 'loan_amount_label', 'ar', 'مبلغ القرض'),
(26991, 'enter_loan_amount_placeholder', 'en', 'Enter loan amount'),
(26992, 'enter_loan_amount_placeholder', 'ar', 'أدخل مبلغ القرض'),
(26993, 'number_of_installments_label', 'en', 'Number of Installments'),
(26994, 'number_of_installments_label', 'ar', 'عدد الأقساط'),
(26995, 'monthly_deduction_label', 'en', 'Monthly Deduction'),
(26996, 'monthly_deduction_label', 'ar', 'الخصم الشهري'),
(26997, 'start_date_of_deduction_label', 'en', 'Start Date of Deduction'),
(26998, 'start_date_of_deduction_label', 'ar', 'تاريخ بدء الخصم'),
(26999, 'submit_application_button', 'en', 'Submit Application'),
(27000, 'submit_application_button', 'ar', 'إرسال الطلب'),
(27001, 'amount_exceeds_max_validation', 'en', 'Amount exceeds the maximum allowed.'),
(27002, 'amount_exceeds_max_validation', 'ar', 'المبلغ يتجاوز الحد الأقصى المسموح به.'),
(27003, 'fill_all_fields_validation', 'en', 'Please fill out all fields.'),
(27004, 'fill_all_fields_validation', 'ar', 'يرجى ملء جميع الحقول.'),
(27005, 'loan_amount_cannot_exceed_validation', 'en', 'Loan amount cannot exceed'),
(27006, 'loan_amount_cannot_exceed_validation', 'ar', 'لا يمكن أن يتجاوز مبلغ القرض'),
(27007, 'loan_amount_must_be_positive_validation', 'en', 'Loan amount must be greater than zero.'),
(27008, 'loan_amount_must_be_positive_validation', 'ar', 'يجب أن يكون مبلغ القرض أكبر من صفر.'),
(27009, 'failed_to_fetch_loan_details', 'en', 'Failed to fetch loan details.'),
(27010, 'failed_to_fetch_loan_details', 'ar', 'فشل في جلب تفاصيل القرض.'),
(27011, 'loading_loan_balance', 'en', 'Loading Loan Balance...'),
(27012, 'loading_loan_balance', 'ar', 'جاري تحميل رصيد القرض...'),
(27013, 'add_manual_loan_payment_title', 'en', 'Add Manual Loan Payment'),
(27014, 'add_manual_loan_payment_title', 'ar', 'إضافة دفعة قرض يدوية'),
(27015, 'remaining_balance_label', 'en', 'Remaining Balance:'),
(27016, 'remaining_balance_label', 'ar', 'الرصيد المتبقي:'),
(27017, 'payment_amount_label', 'en', 'Payment Amount'),
(27018, 'payment_amount_label', 'ar', 'مبلغ الدفعة'),
(27019, 'enter_amount_placeholder', 'en', 'Enter amount'),
(27020, 'enter_amount_placeholder', 'ar', 'أدخل المبلغ'),
(27021, 'payment_date_label', 'en', 'Payment Date'),
(27022, 'payment_date_label', 'ar', 'تاريخ الدفع'),
(27023, 'enter_receipt_id_placeholder', 'en', 'Enter receipt ID'),
(27024, 'enter_receipt_id_placeholder', 'ar', 'أدخل معرف الإيصال'),
(27025, 'submit_payment_button', 'en', 'Submit Payment'),
(27026, 'submit_payment_button', 'ar', 'إرسال الدفعة'),
(27027, 'payment_exceeds_balance_validation', 'en', 'Payment cannot exceed the remaining balance.'),
(27028, 'payment_exceeds_balance_validation', 'ar', 'لا يمكن أن تتجاوز الدفعة الرصيد المتبقي.'),
(27029, 'receipt_id_duplicate_validation', 'en', 'This Receipt ID is already registered.'),
(27030, 'receipt_id_duplicate_validation', 'ar', 'معرف الإيصال هذا مسجل بالفعل.'),
(27031, 'fill_amount_and_date_validation', 'en', 'Please fill out amount and date.'),
(27032, 'fill_amount_and_date_validation', 'ar', 'يرجى ملء المبلغ والتاريخ.'),
(27033, 'payment_amount_must_be_positive_validation', 'en', 'Payment amount must be greater than zero.'),
(27034, 'payment_amount_must_be_positive_validation', 'ar', 'يجب أن يكون مبلغ الدفعة أكبر من صفر.'),
(27035, 'enter_receipt_id_validation', 'en', 'Please enter the Receipt ID.'),
(27036, 'enter_receipt_id_validation', 'ar', 'الرجاء إدخال معرف الإيصال.'),
(27037, 'select_receipt_attachment_validation', 'en', 'Please select a receipt attachment file.'),
(27038, 'select_receipt_attachment_validation', 'ar', 'الرجاء تحديد ملف مرفق الإيصال.'),
(27039, 'failed_to_fetch_loan_balance', 'en', 'Failed to fetch loan balance.'),
(27040, 'failed_to_fetch_loan_balance', 'ar', 'فشل في جلب رصيد القرض.'),
(27041, 'unknown_error_occurred', 'en', 'An unknown error occurred.'),
(27042, 'unknown_error_occurred', 'ar', 'حدث خطأ غير معروف.'),
(27043, 'request_timed_out', 'en', 'The request timed out.'),
(27044, 'request_timed_out', 'ar', 'انتهت مهلة الطلب.'),
(27045, 'error_parsing_response', 'en', 'There was an error parsing the response.'),
(27046, 'error_parsing_response', 'ar', 'حدث خطأ أثناء تحليل الاستجابة.'),
(27047, 'could_not_connect_server', 'en', 'Could not connect to the server.'),
(27048, 'could_not_connect_server', 'ar', 'لا يمكن الاتصال بالخادم.'),
(27049, 'apply_for_emergency_loan_title', 'en', 'Apply for Emergency Loan'),
(27050, 'apply_for_emergency_loan_title', 'ar', 'التقدم بطلب للحصول على قرض طارئ'),
(27051, 'emergency_loan_notice', 'en', 'Emergency loans are subject to management review.'),
(27052, 'emergency_loan_notice', 'ar', 'تخضع القروض الطارئة لمراجعة الإدارة.'),
(27053, 'max_20_days_range_alert', 'en', 'Maximum 20 days range allowed'),
(27054, 'max_20_days_range_alert', 'ar', 'الحد الأقصى المسموح به هو 20 يومًا'),
(27055, 'confirm_approval_title', 'en', 'Confirm Approval'),
(27056, 'confirm_approval_title', 'ar', 'تأكيد الموافقة'),
(27057, 'confirm_approve_loan_text', 'en', 'Are you sure you want to approve this loan request and send it to the next level?'),
(27058, 'confirm_approve_loan_text', 'ar', 'هل أنت متأكد أنك تريد الموافقة على طلب القرض هذا وإرساله إلى المستوى التالي؟'),
(27059, 'yes_approve_it_button', 'en', 'Yes, approve it!'),
(27060, 'yes_approve_it_button', 'ar', 'نعم، وافق عليه!'),
(27061, 'confirm_rejection_title', 'en', 'Confirm Rejection'),
(27062, 'confirm_rejection_title', 'ar', 'تأكيد الرفض'),
(27063, 'provide_rejection_reason_label', 'en', 'Please provide a reason for rejection:'),
(27064, 'provide_rejection_reason_label', 'ar', 'يرجى تقديم سبب للرفض:'),
(27065, 'enter_rejection_reason_placeholder', 'en', 'Enter the reason here...'),
(27066, 'enter_rejection_reason_placeholder', 'ar', 'أدخل السبب هنا...'),
(27067, 'submit_rejection_button', 'en', 'Submit Rejection'),
(27068, 'submit_rejection_button', 'ar', 'إرسال الرفض'),
(27069, 'rejection_reason_required_validation', 'en', 'You must provide a reason for rejection!'),
(27070, 'rejection_reason_required_validation', 'ar', 'يجب تقديم سبب للرفض!'),
(27071, 'finalize_and_disburse_loan_title', 'en', 'Finalize and Disburse Loan'),
(27072, 'finalize_and_disburse_loan_title', 'ar', 'إنهاء وصرف القرض'),
(27073, 'finalize_loan_notice', 'en', 'Please provide the disbursement receipt details. This will mark the loan as active.'),
(27074, 'finalize_loan_notice', 'ar', 'يرجى تقديم تفاصيل إيصال الصرف. سيؤدي هذا إلى تفعيل القرض.'),
(27075, 'receipt_attachment_label', 'en', 'Receipt Attachment'),
(27076, 'receipt_attachment_label', 'ar', 'مرفق الإيصال'),
(27077, 'submit_and_finalize_button', 'en', 'Submit & Finalize'),
(27078, 'submit_and_finalize_button', 'ar', 'إرسال وإنهاء'),
(27079, 'receipt_id_and_attachment_required_validation', 'en', 'Please provide both a Receipt ID and an attachment.'),
(27080, 'receipt_id_and_attachment_required_validation', 'ar', 'يرجى تقديم كل من معرف الإيصال ومرفق.'),
(27081, 'calculating_eos_wait_message', 'en', 'Calculating End of Service benefit, please wait.'),
(27082, 'calculating_eos_wait_message', 'ar', 'جاري حساب مكافأة نهاية الخدمة، يرجى الانتظار.'),
(27083, 'modify_and_approve_loan_title', 'en', 'Modify and Approve Loan'),
(27084, 'modify_and_approve_loan_title', 'ar', 'تعديل والموافقة على القرض'),
(27085, 'sar_currency', 'en', 'SAR'),
(27086, 'sar_currency', 'ar', 'ريال سعودي'),
(27087, 'not_applicable', 'en', 'N/A'),
(27088, 'not_applicable', 'ar', 'غير متاح'),
(27089, 'submit_and_approve_button', 'en', 'Submit & Approve'),
(27090, 'submit_and_approve_button', 'ar', 'إرسال وموافقة'),
(27091, 'valid_amount_installments_validation', 'en', 'Please enter a valid amount and number of installments.'),
(27092, 'valid_amount_installments_validation', 'ar', 'يرجى إدخال مبلغ وعدد أقساط صالحين.'),
(27093, 'loan_amount_cannot_exceed_max_validation', 'en', 'Loan amount cannot exceed the maximum allowed of'),
(27094, 'loan_amount_cannot_exceed_max_validation', 'ar', 'لا يمكن أن يتجاوز مبلغ القرض الحد الأقصى المسموح به وهو'),
(27095, 'hr_asst_modify_approve_title', 'en', 'HR Asst: Modify & Approve'),
(27096, 'hr_asst_modify_approve_title', 'ar', 'مساعد الموارد البشرية: تعديل وموافقة'),
(27097, 'error_no_access_to_department', 'en', 'Error ooooh! You don\'t have access for ( %s ) Department.'),
(27098, 'error_no_access_to_department', 'ar', 'خطأ! ليس لديك صلاحية الوصول إلى قسم ( %s ).'),
(27099, 'expired', 'en', 'Expired'),
(27100, 'expired', 'ar', 'منتهي الصلاحية'),
(27101, 'terminated', 'en', 'Terminated'),
(27102, 'terminated', 'ar', 'تم إنهاؤه'),
(27103, 'success', 'en', 'Success!'),
(27104, 'success', 'ar', 'نجاح!'),
(27105, 'employee_details_edited_successfully', 'en', 'Employee details will be edit successfully!'),
(27106, 'employee_details_edited_successfully', 'ar', 'سيتم تعديل تفاصيل الموظف بنجاح!'),
(27107, 'info', 'en', 'Info!'),
(27108, 'info', 'ar', 'معلومات!'),
(27109, 'no_changes_made', 'en', 'No changes are made'),
(27110, 'no_changes_made', 'ar', 'لم يتم إجراء أي تغييرات'),
(27111, 'database_error', 'en', 'Database error:'),
(27112, 'database_error', 'ar', 'خطأ في قاعدة البيانات:'),
(27113, 'edit_employee_title', 'en', 'Edit Employee'),
(27114, 'edit_employee_title', 'ar', 'تعديل موظف'),
(27115, 'employee_name_label', 'en', 'Employee Name'),
(27116, 'employee_name_label', 'ar', 'اسم الموظف'),
(27117, 'employee_id_label', 'en', 'Employee ID.'),
(27118, 'employee_id_label', 'ar', 'رقم الموظف.'),
(27119, 'id_iqama_label', 'en', 'ID / Iqama'),
(27120, 'id_iqama_label', 'ar', 'الهوية / الإقامة'),
(27121, 'id_iqama_expire_gregorian_label', 'en', 'ID / Iqama Expire in Gregorian'),
(27122, 'id_iqama_expire_gregorian_label', 'ar', 'انتهاء الهوية / الإقامة بالميلادي'),
(27123, 'id_iqama_expire_hijri_label', 'en', 'ID / Iqama Expire in Hijri'),
(27124, 'id_iqama_expire_hijri_label', 'ar', 'انتهاء الهوية / الإقامة بالهجري'),
(27125, 'passport_no_label', 'en', 'Passport No.'),
(27126, 'passport_no_label', 'ar', 'رقم الجواز.'),
(27127, 'passport_expire_label', 'en', 'Passport Expire'),
(27128, 'passport_expire_label', 'ar', 'انتهاء صلاحية الجواز'),
(27129, 'mobile_no_label', 'en', 'Mobile No.'),
(27130, 'mobile_no_label', 'ar', 'رقم الجوال'),
(27131, 'emergency_mobile_no_label', 'en', 'Emergency Mobile No.'),
(27132, 'emergency_mobile_no_label', 'ar', 'رقم جوال الطوارئ'),
(27133, 'emergency_contact_name_label', 'en', 'Emergency Contact Name'),
(27134, 'emergency_contact_name_label', 'ar', 'اسم جهة اتصال الطوارئ'),
(27135, 'nationality_label', 'en', 'Nationality'),
(27136, 'nationality_label', 'ar', 'الجنسية'),
(27137, 'department_label', 'en', 'Department'),
(27138, 'department_label', 'ar', 'القسم'),
(27139, 'section_label', 'en', 'Section'),
(27140, 'section_label', 'ar', 'القطاع'),
(27141, 'employee_type_label', 'en', 'Employee Type'),
(27142, 'employee_type_label', 'ar', 'نوع الموظف'),
(27143, 'joining_date_label', 'en', 'Joining Date'),
(27144, 'joining_date_label', 'ar', 'تاريخ التعيين'),
(27145, 'dob_gregorian_label', 'en', 'Date of Birth in Gregorian'),
(27146, 'dob_gregorian_label', 'ar', 'تاريخ الميلاد بالميلادي'),
(27147, 'dob_hijri_label', 'en', 'Date of Birth in Hijri'),
(27148, 'dob_hijri_label', 'ar', 'تاريخ الميلاد بالهجري'),
(27149, 't_shirt_size_label', 'en', 'T-Shirt Size'),
(27150, 't_shirt_size_label', 'ar', 'مقاس التيشيرت'),
(27151, 'gender_label', 'en', 'Gender'),
(27152, 'gender_label', 'ar', 'الجنس'),
(27153, 'marital_status_label', 'en', 'Marital Status'),
(27154, 'marital_status_label', 'ar', 'الحالة الاجتماعية'),
(27155, 'blood_type_label', 'en', 'Blood Type'),
(27156, 'blood_type_label', 'ar', 'فصيلة الدم'),
(27157, 'sponsorship_label', 'en', 'Sponsorship'),
(27158, 'sponsorship_label', 'ar', 'الكفالة'),
(27159, 'company_label', 'en', 'Company'),
(27160, 'company_label', 'ar', 'الشركة'),
(27161, 'actual_job_label', 'en', 'Actual Job'),
(27162, 'actual_job_label', 'ar', 'الوظيفة الفعلية'),
(27163, 'contract_period_label', 'en', 'Contract Period'),
(27164, 'contract_period_label', 'ar', 'مدة العقد'),
(27165, 'vacation_days_label', 'en', 'Vacation Days'),
(27166, 'vacation_days_label', 'ar', 'أيام الإجازة'),
(27167, 'salary_label', 'en', 'Salary'),
(27168, 'salary_label', 'ar', 'الراتب'),
(27169, 'bank_name_label', 'en', 'Bank Name'),
(27170, 'bank_name_label', 'ar', 'اسم البنك'),
(27171, 'iban_label', 'en', 'IBAN'),
(27172, 'iban_label', 'ar', 'رقم الآيبان'),
(27173, 'employee_email_label', 'en', 'Employee Email'),
(27174, 'employee_email_label', 'ar', 'بريد الموظف الإلكتروني'),
(27175, 'company_email_label', 'en', 'Company Email'),
(27176, 'company_email_label', 'ar', 'بريد الشركة الإلكتروني'),
(27177, 'employee_address_label', 'en', 'Employee Address'),
(27178, 'employee_address_label', 'ar', 'عنوان الموظف'),
(27179, 'insurance_no_label', 'en', 'Insurance No.'),
(27180, 'insurance_no_label', 'ar', 'رقم التأمين'),
(27181, 'insurance_class_label', 'en', 'Insurance Class'),
(27182, 'insurance_class_label', 'ar', 'فئة التأمين'),
(27183, 'insurance_expire_label', 'en', 'Insurance Expire'),
(27184, 'insurance_expire_label', 'ar', 'انتهاء التأمين'),
(27185, 'gosi_label', 'en', 'Gosi'),
(27186, 'gosi_label', 'ar', 'التأمينات الاجتماعية'),
(27187, 'probation_period_label', 'en', 'Probation Period'),
(27188, 'probation_period_label', 'ar', 'فترة التجربة'),
(27189, 'salary_payment_type_label', 'en', 'Salary Payment type'),
(27190, 'salary_payment_type_label', 'ar', 'نوع دفع الراتب'),
(27191, 'back_button', 'en', 'Back'),
(27192, 'back_button', 'ar', 'رجوع'),
(27193, 'save_edit_button', 'en', 'Save Edit'),
(27194, 'save_edit_button', 'ar', 'حفظ التعديلات'),
(27195, 'you_need_to_terminate_text', 'en', 'You need to Terminat!'),
(27196, 'you_need_to_terminate_text', 'ar', 'يجب عليك الإنهاء!'),
(27197, 'terminate_submit_button', 'en', 'Terminat Submit'),
(27198, 'terminate_submit_button', 'ar', 'إرسال الإنهاء'),
(27199, 'terminate_button', 'en', 'Terminat'),
(27200, 'terminate_button', 'ar', 'إنهاء'),
(27201, 'select_option', 'en', 'Select'),
(27202, 'select_option', 'ar', 'اختر'),
(27203, 'manager_option', 'en', 'Manager'),
(27204, 'manager_option', 'ar', 'مدير'),
(27205, 'supervisor_option', 'en', 'Supervisor'),
(27206, 'supervisor_option', 'ar', 'مشرف'),
(27207, 'supporter_option', 'en', 'Supporter'),
(27208, 'supporter_option', 'ar', 'داعم'),
(27209, 'male_option', 'en', 'Male'),
(27210, 'male_option', 'ar', 'ذكر'),
(27211, 'female_option', 'en', 'Female'),
(27212, 'female_option', 'ar', 'أنثى'),
(27213, 'married_option', 'en', 'Married'),
(27214, 'married_option', 'ar', 'متزوج'),
(27215, 'single_option', 'en', 'Single'),
(27216, 'single_option', 'ar', 'أعزب'),
(27217, 'no_probation_period_option', 'en', 'No Probation Period'),
(27218, 'no_probation_period_option', 'ar', 'بدون فترة تجربة'),
(27219, '3_months_option', 'en', '3 Months'),
(27220, '3_months_option', 'ar', '3 أشهر'),
(27221, '6_months_option', 'en', '6 Months'),
(27222, '6_months_option', 'ar', '6 أشهر'),
(27223, 'bank_option', 'en', 'Bank'),
(27224, 'bank_option', 'ar', 'بنك'),
(27225, 'cash_option', 'en', 'Cash'),
(27226, 'cash_option', 'ar', 'نقداً'),
(27227, 'hold_option', 'en', 'Hold'),
(27228, 'hold_option', 'ar', 'معلق'),
(27229, 'ok', 'en', 'Ok'),
(27230, 'ok', 'ar', 'موافق'),
(27359, 'print_vacation_report_title', 'en', 'Print Vacation Report'),
(27360, 'print_vacation_report_title', 'ar', 'طباعة تقرير الإجازة'),
(27361, 'view_details', 'en', 'View details'),
(27362, 'view_details', 'ar', 'عرض التفاصيل'),
(27363, 'page', 'en', 'Page'),
(27364, 'page', 'ar', 'الصفحات'),
(27365, 'search_results_for', 'en', 'Search results for'),
(27366, 'search_results_for', 'ar', 'نتائج البحث عن'),
(27367, 'all_results', 'en', 'All results'),
(27368, 'all_results', 'ar', 'جميع النتائج'),
(27369, 'results_are_found', 'en', 'Results are found'),
(27370, 'results_are_found', 'ar', 'تم العثور على النتائج'),
(27371, 'days_of_expiry', 'en', 'Days of expiry'),
(27372, 'days_of_expiry', 'ar', 'أيام انتهاء الصلاحية'),
(27373, 'iqama_id_expiry', 'en', 'Iqama / ID expiry'),
(27374, 'iqama_id_expiry', 'ar', 'انتهاء صلاحية الإقامة/الهوية'),
(27375, 'update_expiry', 'en', 'Update expiry'),
(27376, 'update_expiry', 'ar', 'انتهاء صلاحية التحديث'),
(27377, 'this_value_is_required', 'en', 'This value is required.'),
(27378, 'this_value_is_required', 'ar', 'هذه القيمة مطلوبة.'),
(27379, 'new_almutlak_co_employee', 'en', 'New almutlak co employee'),
(27380, 'new_almutlak_co_employee', 'ar', 'موظف جديد في شركة المطلق'),
(27381, 'register_new_employee', 'en', 'Register new employee'),
(27382, 'register_new_employee', 'ar', 'تسجيل موظف جديد'),
(27383, 'in_gregorian', 'en', 'In Gregorian'),
(27384, 'in_gregorian', 'ar', 'الميلادي'),
(27385, 'in_hijri', 'en', 'In Hijri'),
(27386, 'in_hijri', 'ar', 'بالهجري'),
(27387, 'edit_employee_salary', 'en', 'Edit employee salary'),
(27388, 'edit_employee_salary', 'ar', 'تعديل راتب الموظف'),
(27389, 'salary_details_saved_successfully!', 'en', 'Salary details saved successfully!'),
(27390, 'salary_details_saved_successfully!', 'ar', 'تم حفظ تفاصيل الراتب بنجاح!'),
(27391, 'total_salary_mismatch', 'en', 'Total salary mismatch'),
(27392, 'total_salary_mismatch', 'ar', 'عدم تطابق إجمالي الراتب.'),
(27393, 'expected', 'en', 'Expected'),
(27394, 'expected', 'ar', 'مُتوقع'),
(27395, 'submitted', 'en', 'Submitted'),
(27396, 'submitted', 'ar', 'مُقَدَّم'),
(27397, 'no_valid_salary_components_provided', 'en', 'No valid salary components provided'),
(27398, 'no_valid_salary_components_provided', 'ar', 'لم يتم توفير مكونات الراتب الصالحة'),
(27399, 'salary_components_dont_add_up_to_the_total', 'en', 'Salary components dont add up to the total'),
(27400, 'salary_components_dont_add_up_to_the_total', 'ar', 'مكونات الراتب لا تصل إلى الإجمالي'),
(27401, 'almutlak_co_employee', 'en', 'Almutlak co employee'),
(27402, 'almutlak_co_employee', 'ar', 'موظف في شركة المطلق'),
(27403, 'manpower_employee', 'en', 'Manpower Employee'),
(27404, 'manpower_employee', 'ar', 'موظف القوى العاملة'),
(27405, 'employee_created_successfully', 'en', 'Employee created successfully'),
(27406, 'employee_created_successfully', 'ar', 'تم إنشاء الموظف بنجاح.'),
(27407, 'employee_id_must_be_numeric', 'en', 'Employee id must be numeric.'),
(27408, 'employee_id_must_be_numeric', 'ar', 'يجب أن يكون معرف الموظف رقميًا.'),
(27409, 'salary_must_be_numeric', 'en', 'Salary must be numeric.'),
(27410, 'salary_must_be_numeric', 'ar', 'الراتب يجب أن يكون رقميا.'),
(27411, 'employees_bank_details', 'en', 'Employees bank details'),
(27412, 'employees_bank_details', 'ar', 'تفاصيل بنك الموظفين'),
(27413, 'bank_swift_code', 'en', 'Bank swift code'),
(27414, 'bank_swift_code', 'ar', 'رمز سويفت البنكي'),
(27765, 'benefit', 'en', 'Benefit'),
(27766, 'benefit', 'ar', 'منفعة'),
(27767, 'deduction', 'en', 'Deduction'),
(27768, 'deduction', 'ar', 'خصم'),
(27769, 'employee_payroll_management', 'en', 'Employee Payroll Management'),
(27770, 'employee_payroll_management', 'ar', 'إدارة رواتب الموظفين'),
(27771, 'generate_report', 'en', 'Generate Report'),
(27772, 'generate_report', 'ar', 'إنشاء تقرير'),
(27773, 'salary_allowances_breakdown', 'en', 'Salary & Allowances Breakdown'),
(27774, 'salary_allowances_breakdown', 'ar', 'تفاصيل الراتب والبدلات'),
(27775, 'select_benefit_type', 'en', 'Select Benefit Type'),
(27776, 'select_benefit_type', 'ar', 'اختر نوع المنفعة'),
(28209, 'restrict_to_numbers_warning', 'en', 'restrictToNumbers: inputElement is null or undefined.'),
(28210, 'restrict_to_numbers_warning', 'ar', 'restrictToNumbers: عنصر الإدخال فارغ أو غير معرف.'),
(28211, 'cannot_read_stylesheet_log', 'en', 'Cannot read stylesheet'),
(28212, 'cannot_read_stylesheet_log', 'ar', 'لا يمكن قراءة ورقة الأنماط'),
(28213, 'select_department_first_option', 'en', 'Select Department First'),
(28214, 'select_department_first_option', 'ar', 'اختر القسم أولاً'),
(28215, 'select_section_option', 'en', 'Select Section'),
(28216, 'select_section_option', 'ar', 'اختر القطاع'),
(28217, 'error_loading_sections_option', 'en', 'Error loading sections'),
(28218, 'error_loading_sections_option', 'ar', 'خطأ في تحميل القطاعات'),
(28219, 'payroll_management_title', 'en', 'Payroll Management'),
(28220, 'payroll_management_title', 'ar', 'إدارة الرواتب'),
(28221, 'select_month_label', 'en', 'Select Month'),
(28222, 'select_month_label', 'ar', 'اختر الشهر'),
(28223, 'filter_by_company_label', 'en', 'Filter by Company'),
(28224, 'filter_by_company_label', 'ar', 'تصفية حسب الشركة'),
(28225, 'all_companies_option', 'en', 'All Companies'),
(28226, 'all_companies_option', 'ar', 'جميع الشركات'),
(28227, 'generate_payroll_for_selected_button', 'en', 'Generate Payroll for Selected'),
(28228, 'generate_payroll_for_selected_button', 'ar', 'إنشاء كشوف المرتبات للمحدد'),
(28229, 'generate_payroll_report_button', 'en', 'Generate Payroll Report'),
(28230, 'generate_payroll_report_button', 'ar', 'إنشاء تقرير الرواتب'),
(28231, 'actions_label', 'en', 'Actions'),
(28232, 'actions_label', 'ar', 'الإجراءات'),
(28233, 'no_deductions_recorded', 'en', 'No deductions recorded.'),
(28234, 'no_deductions_recorded', 'ar', 'لا توجد خصومات مسجلة.'),
(28235, 'fixed_amount_option', 'en', 'Fixed Amount'),
(28236, 'fixed_amount_option', 'ar', 'مبلغ ثابت'),
(28237, 'deduction_by_hour_option', 'en', 'Deduction by Hour'),
(28238, 'deduction_by_hour_option', 'ar', 'خصم بالساعة'),
(28239, 'deduction_by_day_option', 'en', 'Deduction by Day'),
(28240, 'deduction_by_day_option', 'ar', 'خصم باليوم'),
(28241, 'deduction_reason_placeholder', 'en', 'Deduction Reason'),
(28242, 'deduction_reason_placeholder', 'ar', 'سبب الخصم'),
(28243, 'hours_placeholder', 'en', 'Hours'),
(28244, 'hours_placeholder', 'ar', 'ساعات'),
(28245, 'days_placeholder', 'en', 'Days'),
(28246, 'days_placeholder', 'ar', 'أيام'),
(28247, 'amount_placeholder', 'en', 'Amount'),
(28248, 'amount_placeholder', 'ar', 'المبلغ'),
(28249, 'no_benefits_recorded_for_month', 'en', 'No benefits recorded for this month.'),
(28250, 'no_benefits_recorded_for_month', 'ar', 'لا توجد مزايا مسجلة لهذا الشهر.'),
(28251, 'select_type_option', 'en', 'Select Type'),
(28252, 'select_type_option', 'ar', 'اختر النوع'),
(28253, 'benefit_name_placeholder', 'en', 'Benefit Name'),
(28254, 'benefit_name_placeholder', 'ar', 'اسم المنفعة'),
(28255, 'generated_badge', 'en', 'Generated'),
(28256, 'generated_badge', 'ar', 'تم إنشاؤه'),
(28257, 'paid_badge', 'en', 'Paid'),
(28258, 'paid_badge', 'ar', 'مدفوع'),
(28259, 'search_employees_placeholder', 'en', 'Search employees...'),
(28260, 'search_employees_placeholder', 'ar', 'ابحث عن موظفين...'),
(28261, 'show_employees_per_page_label', 'en', 'Show _MENU_ employees per page'),
(28262, 'show_employees_per_page_label', 'ar', 'إظهار _MENU_ موظفين في كل صفحة'),
(28263, 'showing_employees_info', 'en', 'Showing _START_ to _END_ of _TOTAL_ employees'),
(28264, 'showing_employees_info', 'ar', 'إظهار _START_ إلى _END_ من أصل _TOTAL_ موظفين'),
(28265, 'no_employees_found', 'en', 'No employees found'),
(28266, 'no_employees_found', 'ar', 'لم يتم العثور على موظفين'),
(28267, 'filtered_from_total_employees_info', 'en', '(filtered from _MAX_ total employees)'),
(28268, 'filtered_from_total_employees_info', 'ar', '(تمت التصفية من إجمالي _MAX_ موظفين)'),
(28269, 'no_employee_data_available_for_month', 'en', 'No employee data available for the selected month/filters.'),
(28270, 'no_employee_data_available_for_month', 'ar', 'لا توجد بيانات موظفين متاحة للشهر/الفلاتر المحددة.'),
(28271, 'no_employees_selected_warning_title', 'en', 'No Employees Selected'),
(28272, 'no_employees_selected_warning_title', 'ar', 'لم يتم تحديد أي موظفين'),
(28273, 'please_select_one_employee_warning', 'en', 'Please select at least one employee to generate payroll.'),
(28274, 'please_select_one_employee_warning', 'ar', 'الرجاء تحديد موظف واحد على الأقل لإنشاء كشوف المرتبات.'),
(28275, 'month_not_selected_warning_title', 'en', 'Month Not Selected'),
(28276, 'month_not_selected_warning_title', 'ar', 'لم يتم تحديد الشهر'),
(28277, 'please_select_payroll_month_warning', 'en', 'Please select a payroll month.'),
(28278, 'please_select_payroll_month_warning', 'ar', 'الرجاء تحديد شهر كشوف المرتبات.'),
(28279, 'generating_payroll_title', 'en', 'Generating Payroll...'),
(28280, 'generating_payroll_title', 'ar', 'جاري إنشاء كشوف المرتبات...'),
(28281, 'please_wait_generating_payroll', 'en', 'Please wait, this might take a moment.'),
(28282, 'please_wait_generating_payroll', 'ar', 'الرجاء الانتظار، قد يستغرق هذا بعض الوقت.'),
(28283, 'payroll_generated_success_title', 'en', 'Payroll Generated!'),
(28284, 'payroll_generated_success_title', 'ar', 'تم إنشاء كشوف المرتبات!'),
(28285, 'error_generating_payroll_title', 'en', 'Error Generating Payroll'),
(28286, 'error_generating_payroll_title', 'ar', 'خطأ في إنشاء كشوف المرتبات'),
(28287, 'select_report_month_title', 'en', 'Select Report Month'),
(28288, 'select_report_month_title', 'ar', 'اختر شهر التقرير'),
(28289, 'choose_month_for_report_label', 'en', 'Choose a month to generate the payroll report:'),
(28290, 'choose_month_for_report_label', 'ar', 'اختر شهرًا لإنشاء تقرير الرواتب:'),
(28291, 'please_select_month_for_report_validation', 'en', 'Please select a month to generate the report.'),
(28292, 'please_select_month_for_report_validation', 'ar', 'الرجاء تحديد شهر لإنشاء التقرير.'),
(28293, 'failed_to_fetch_available_months_error', 'en', 'Failed to fetch available payroll months for report.'),
(28294, 'failed_to_fetch_available_months_error', 'ar', 'فشل في جلب أشهر الرواتب المتاحة للتقرير.'),
(28295, 'no_generated_payroll_months_found', 'en', 'No generated payroll months found.'),
(28296, 'no_generated_payroll_months_found', 'ar', 'لم يتم العثور على أشهر كشوف مرتبات تم إنشاؤها.'),
(28297, 'error_loading_report_months', 'en', 'Error loading months:'),
(28298, 'error_loading_report_months', 'ar', 'خطأ في تحميل الأشهر:'),
(28299, 'generating_report_title', 'en', 'Generating Report...'),
(28300, 'generating_report_title', 'ar', 'جاري إنشاء التقرير...'),
(28301, 'fetching_payroll_data_for_month', 'en', 'Fetching payroll data for'),
(28302, 'fetching_payroll_data_for_month', 'ar', 'جلب بيانات الرواتب لشهر'),
(28303, 'please_wait_fetching_data', 'en', 'Please wait.'),
(28304, 'please_wait_fetching_data', 'ar', 'الرجاء الانتظار.'),
(28305, 'no_payroll_data_info_title', 'en', 'No Payroll Data'),
(28306, 'no_payroll_data_info_title', 'ar', 'لا توجد بيانات رواتب'),
(28307, 'no_generated_payrolls_for_month_info', 'en', 'No generated payrolls found for this month.'),
(28308, 'no_generated_payrolls_for_month_info', 'ar', 'لم يتم العثور على كشوف مرتبات تم إنشاؤها لهذا الشهر.'),
(28309, 'payroll_report_for_month_title', 'en', 'Payroll Report for'),
(28310, 'payroll_report_for_month_title', 'ar', 'تقرير الرواتب لشهر'),
(28311, 'mark_as_paid_button', 'en', 'Mark as Paid'),
(28312, 'mark_as_paid_button', 'ar', 'وضع علامة كمدفوع'),
(28313, 'pdf_button', 'en', 'PDF'),
(28314, 'pdf_button', 'ar', 'PDF'),
(28315, 'excel_button', 'en', 'Excel'),
(28316, 'excel_button', 'ar', 'Excel'),
(28317, 'grand_total_label', 'en', 'Grand Total:'),
(28318, 'grand_total_label', 'ar', 'المجموع الإجمالي:'),
(28319, 'loading_payroll_for_employee', 'en', 'Loading Payroll for'),
(28320, 'loading_payroll_for_employee', 'ar', 'جاري تحميل الرواتب لـ'),
(28321, 'salary_section', 'en', 'Salary'),
(28322, 'salary_section', 'ar', 'الراتب'),
(28323, 'benefits_section', 'en', 'Benefits'),
(28324, 'benefits_section', 'ar', 'المنافع'),
(28325, 'deductions_section', 'en', 'Deductions'),
(28326, 'deductions_section', 'ar', 'الخصومات'),
(28327, 'basic_components_title', 'en', 'Basic Components'),
(28328, 'basic_components_title', 'ar', 'المكونات الأساسية'),
(28329, 'basic_salary_label', 'en', 'Basic Salary'),
(28330, 'basic_salary_label', 'ar', 'الراتب الأساسي'),
(28331, 'housing_allowance_label', 'en', 'Housing Allowance'),
(28332, 'housing_allowance_label', 'ar', 'بدل السكن'),
(28333, 'transport_allowance_label', 'en', 'Transport Allowance'),
(28334, 'transport_allowance_label', 'ar', 'بدل النقل'),
(28335, 'food_allowance_label', 'en', 'Food Allowance'),
(28336, 'food_allowance_label', 'ar', 'بدل الطعام'),
(28337, 'additional_components_title', 'en', 'Additional Components'),
(28338, 'additional_components_title', 'ar', 'مكونات إضافية'),
(28339, 'miscellaneous_allowance_label', 'en', 'Miscellaneous'),
(28340, 'miscellaneous_allowance_label', 'ar', 'متنوع'),
(28341, 'cashier_allowance_label', 'en', 'Cashier Allowance'),
(28342, 'cashier_allowance_label', 'ar', 'بدل أمين الصندوق'),
(28343, 'fuel_allowance_label', 'en', 'Fuel Allowance'),
(28344, 'fuel_allowance_label', 'ar', 'بدل الوقود'),
(28345, 'telephone_allowance_label', 'en', 'Telephone Allowance'),
(28346, 'telephone_allowance_label', 'ar', 'بدل الهاتف'),
(28347, 'guard_allowance_label', 'en', 'Guard Allowance'),
(28348, 'guard_allowance_label', 'ar', 'بدل الحراسة'),
(28349, 'other_allowance_label', 'en', 'Other Allowance'),
(28350, 'other_allowance_label', 'ar', 'بدلات أخرى'),
(28351, 'total_gross_salary_label', 'en', 'Total Gross Salary'),
(28352, 'total_gross_salary_label', 'ar', 'إجمالي الراتب الإجمالي'),
(28353, 'add_benefit_button', 'en', 'Add Benefit'),
(28354, 'add_benefit_button', 'ar', 'إضافة منفعة'),
(28355, 'add_deduction_button', 'en', 'Add Deduction'),
(28356, 'add_deduction_button', 'ar', 'إضافة خصم'),
(28357, 'total_benefits_label', 'en', 'Total Benefits'),
(28358, 'total_benefits_label', 'ar', 'إجمالي المنافع'),
(28359, 'total_deductions_label', 'en', 'Total Deductions'),
(28360, 'total_deductions_label', 'ar', 'إجمالي الخصومات'),
(28361, 'net_salary_label', 'en', 'Net Salary'),
(28362, 'net_salary_label', 'ar', 'صافي الراتب'),
(28363, 'save_changes_button', 'en', 'Save Changes'),
(28364, 'save_changes_button', 'ar', 'حفظ التغييرات'),
(28365, 'saving_changes_title', 'en', 'Saving Changes...'),
(28366, 'saving_changes_title', 'ar', 'جاري حفظ التغييرات...'),
(28367, 'changes_saved_success_title', 'en', 'Changes Saved!'),
(28368, 'changes_saved_success_title', 'ar', 'تم حفظ التغييرات!'),
(28369, 'error_saving_changes_title', 'en', 'Error Saving Changes'),
(28370, 'error_saving_changes_title', 'ar', 'خطأ في حفظ التغييرات'),
(28371, 'error_loading_payroll_title', 'en', 'Error Loading Payroll'),
(28372, 'error_loading_payroll_title', 'ar', 'خطأ في تحميل الرواتب'),
(28373, 'delete_benefit_q_title', 'en', 'Delete Benefit?'),
(28374, 'delete_benefit_q_title', 'ar', 'هل تريد حذف المنفعة؟'),
(28375, 'are_you_sure_delete_benefit_q', 'en', 'Are you sure you want to delete this benefit?'),
(28376, 'are_you_sure_delete_benefit_q', 'ar', 'هل أنت متأكد من رغبتك في حذف هذه المنفعة؟'),
(28377, 'yes_delete_it_button', 'en', 'Yes, delete it!'),
(28378, 'yes_delete_it_button', 'ar', 'نعم، احذفها!'),
(28379, 'deleted_success_title', 'en', 'Deleted!'),
(28380, 'deleted_success_title', 'ar', 'تم الحذف!'),
(28381, 'benefit_deleted_success_msg', 'en', 'The benefit has been deleted.'),
(28382, 'benefit_deleted_success_msg', 'ar', 'تم حذف المنفعة.'),
(28383, 'delete_deduction_q_title', 'en', 'Delete Deduction?'),
(28384, 'delete_deduction_q_title', 'ar', 'هل تريد حذف الخصم؟'),
(28385, 'are_you_sure_delete_deduction_q', 'en', 'Are you sure you want to delete this deduction?');
INSERT INTO `translations` (`translation_id`, `lang_key`, `lang_code`, `translation`) VALUES
(28386, 'are_you_sure_delete_deduction_q', 'ar', 'هل أنت متأكد من رغبتك في حذف هذا الخصم؟'),
(28387, 'deduction_deleted_success_msg', 'en', 'The deduction has been deleted.'),
(28388, 'deduction_deleted_success_msg', 'ar', 'تم حذف الخصم.'),
(28389, 'hourly_deduction_default_name', 'en', 'Hourly Deduction'),
(28390, 'hourly_deduction_default_name', 'ar', 'خصم بالساعة'),
(28391, 'daily_deduction_default_name', 'en', 'Daily Deduction'),
(28392, 'daily_deduction_default_name', 'ar', 'خصم يومي'),
(28393, 'employee_payroll_report_for_month', 'en', 'Employee Payroll Report for'),
(28394, 'employee_payroll_report_for_month', 'ar', 'تقرير رواتب الموظفين لشهر'),
(28395, 'benefits_details_label', 'en', 'Details'),
(28396, 'benefits_details_label', 'ar', 'التفاصيل'),
(28397, 'benefits_total_label', 'en', 'Total'),
(28398, 'benefits_total_label', 'ar', 'المجموع'),
(28399, 'deductions_details_label', 'en', 'Details'),
(28400, 'deductions_details_label', 'ar', 'التفاصيل'),
(28401, 'deductions_total_label', 'en', 'Total'),
(28402, 'deductions_total_label', 'ar', 'المجموع'),
(28403, 'page_footer', 'en', 'Page'),
(28404, 'page_footer', 'ar', 'صفحة'),
(28405, 'no_records_selected_warning_title', 'en', 'No Records Selected'),
(28406, 'no_records_selected_warning_title', 'ar', 'لم يتم تحديد أي سجلات'),
(28407, 'please_select_one_record_to_update_warning', 'en', 'Please select at least one payroll record to update.'),
(28408, 'please_select_one_record_to_update_warning', 'ar', 'الرجاء تحديد سجل كشوف مرتبات واحد على الأقل للتحديث.'),
(28409, 'mark_records_as_status_q_title', 'en', 'Mark {0} record(s) as {1}?'),
(28410, 'mark_records_as_status_q_title', 'ar', 'هل تريد وضع علامة على {0} سجل (سجلات) كـ {1}؟'),
(28411, 'action_cannot_be_undone', 'en', 'This action cannot be undone.'),
(28412, 'action_cannot_be_undone', 'ar', 'لا يمكن التراجع عن هذا الإجراء.'),
(28413, 'yes_mark_as_status_button', 'en', 'Yes, Mark as {0}!'),
(28414, 'yes_mark_as_status_button', 'ar', 'نعم، ضع علامة كـ {0}!'),
(28415, 'updating_status_title', 'en', 'Updating Status...'),
(28416, 'updating_status_title', 'ar', 'جاري تحديث الحالة...'),
(28417, 'status_updated_success_title', 'en', 'Status Updated!'),
(28418, 'status_updated_success_title', 'ar', 'تم تحديث الحالة!'),
(28419, 'update_failed_title', 'en', 'Update Failed'),
(28420, 'update_failed_title', 'ar', 'فشل التحديث'),
(29465, 'loan_approval_center', 'en', 'Loan Approval Center'),
(29466, 'loan_approval_center', 'ar', 'مركز الموافقة على القروض'),
(29467, 'filter_by_status', 'en', 'Filter by Status'),
(29468, 'filter_by_status', 'ar', 'تصفية حسب الحالة'),
(29469, 'all_requests', 'en', 'All Requests'),
(29470, 'all_requests', 'ar', 'جميع الطلبات'),
(29471, 'search_by_name_id', 'en', 'Search by Name / ID'),
(29472, 'search_by_name_id', 'ar', 'البحث بالاسم / الرقم'),
(29473, 'enter_search_term', 'en', 'Enter search term...'),
(29474, 'enter_search_term', 'ar', 'أدخل مصطلح البحث...'),
(29475, 'total_found', 'en', 'Total Found'),
(29476, 'total_found', 'ar', 'إجمالي النتائج'),
(29477, 'applied', 'en', 'Applied'),
(29478, 'applied', 'ar', 'تم التقديم'),
(29479, 'start', 'en', 'Start'),
(29480, 'start', 'ar', 'البداية'),
(29481, 'return', 'en', 'Return'),
(29482, 'return', 'ar', 'العودة'),
(29483, 'view_file', 'en', 'View File'),
(29484, 'view_file', 'ar', 'عرض الملف'),
(29485, 'remaining', 'en', 'Remaining'),
(29486, 'remaining', 'ar', 'المتبقي'),
(29487, 'finalize', 'en', 'Finalize'),
(29488, 'finalize', 'ar', 'إنهاء'),
(29489, 'no_loan_requests_found', 'en', 'No Loan Requests Found'),
(29490, 'no_loan_requests_found', 'ar', 'لم يتم العثور على طلبات قروض'),
(29491, 'no_requests_matching_filters', 'en', 'There are no loan requests matching your current filters.'),
(29492, 'no_requests_matching_filters', 'ar', 'لا توجد طلبات قروض مطابقة للفلاتر الحالية.'),
(29493, 'vacation_approval_center', 'en', 'Vacation Approval Center'),
(29494, 'vacation_approval_center', 'ar', 'مركز الموافقة على الإجازات'),
(29495, 'showing_requests', 'en', 'Showing: {0} Requests'),
(29496, 'showing_requests', 'ar', 'عرض: {0} طلبات'),
(29497, 'no_requests_found', 'en', 'No Requests Found'),
(29498, 'no_requests_found', 'ar', 'لم يتم العثور على طلبات'),
(29499, 'no_requests_matching_filters_vac', 'en', 'There are no requests matching your current filters.'),
(29500, 'no_requests_matching_filters_vac', 'ar', 'لا توجد طلبات مطابقة للفلاتر الحالية.'),
(29501, 'no_requests_to_display', 'en', 'There are no requests to display at this time.'),
(29502, 'no_requests_to_display', 'ar', 'لا توجد طلبات لعرضها في الوقت الحالي.'),
(29503, 'request_details', 'en', 'Request Details'),
(29504, 'request_details', 'ar', 'تفاصيل الطلب'),
(29505, 'confirm_approval', 'en', 'Confirm Approval'),
(29506, 'confirm_approval', 'ar', 'تأكيد الموافقة'),
(29507, 'enter_approval_details', 'en', 'Enter Approval Details:'),
(29508, 'enter_approval_details', 'ar', 'أدخل تفاصيل الموافقة:'),
(29509, 'ticket_payment_optional', 'en', 'Ticket Payment (optional)'),
(29510, 'ticket_payment_optional', 'ar', 'دفع التذكرة (اختياري)'),
(29511, 'permit_fee_optional', 'en', 'Permit Fee (optional)'),
(29512, 'permit_fee_optional', 'ar', 'رسوم التصريح (اختياري)'),
(29513, 'submit_approval', 'en', 'Submit Approval'),
(29514, 'submit_approval', 'ar', 'إرسال الموافقة'),
(29515, 'yes_approve_it', 'en', 'Yes, approve it!'),
(29516, 'yes_approve_it', 'ar', 'نعم، وافق!'),
(29517, 'confirm_rejection', 'en', 'Confirm Rejection'),
(29518, 'confirm_rejection', 'ar', 'تأكيد الرفض'),
(29519, 'provide_rejection_reason', 'en', 'Please provide a reason for rejection:'),
(29520, 'provide_rejection_reason', 'ar', 'يرجى تقديم سبب للرفض:'),
(29521, 'enter_reason_here', 'en', 'Enter the reason here...'),
(29522, 'enter_reason_here', 'ar', 'أدخل السبب هنا...'),
(29523, 'submit_rejection', 'en', 'Submit Rejection'),
(29524, 'submit_rejection', 'ar', 'إرسال الرفض'),
(29525, 'must_provide_rejection_reason', 'en', 'You must provide a reason for rejection!'),
(29526, 'must_provide_rejection_reason', 'ar', 'يجب تقديم سبب للرفض!'),
(29527, 'add_edit_payments_for', 'en', 'Add/Edit Payments for {0}'),
(29528, 'add_edit_payments_for', 'ar', 'إضافة/تعديل المدفوعات لـ {0}'),
(29529, 'enter_update_payment_details', 'en', 'Enter or update payment details:'),
(29530, 'enter_update_payment_details', 'ar', 'أدخل أو حدث تفاصيل الدفع:'),
(29531, 'ticket_payment', 'en', 'Ticket Payment'),
(29532, 'ticket_payment', 'ar', 'دفع التذكرة'),
(29533, 'permit_fee', 'en', 'Permit Fee'),
(29534, 'permit_fee', 'ar', 'رسوم التصريح'),
(29535, 'update_payments', 'en', 'Update Payments'),
(29536, 'update_payments', 'ar', 'تحديث المدفوعات'),
(29537, 'error_updating_payments', 'en', 'An error occurred while updating payments.'),
(29538, 'error_updating_payments', 'ar', 'حدث خطأ أثناء تحديث المدفوعات.'),
(29539, 'approved_and_processed', 'en', 'Approved and Processed'),
(29540, 'approved_and_processed', 'ar', 'تمت الموافقة عليها ومعالجتها'),
(29541, 'monthly', 'en', 'Monthly'),
(29542, 'monthly', 'ar', 'شهريا'),
(29543, 'all_loan_requests', 'en', 'All Loan Requests'),
(29544, 'all_loan_requests', 'ar', 'جميع طلبات القروض'),
(29545, 'pending_department_manager', 'en', 'Pending department manager'),
(29546, 'pending_department_manager', 'ar', 'مدير قسم معلق'),
(29547, 'pending_hr_assistant', 'en', 'Pending HR Assistant'),
(29548, 'pending_hr_assistant', 'ar', 'مساعد الموارد البشرية المعلق'),
(29549, 'pending_hr_manager', 'en', 'Pending HR Manager'),
(29550, 'pending_hr_manager', 'ar', 'مدير الموارد البشرية المعلق'),
(29551, 'pending_finance_manager', 'en', 'Pending Finance Manager'),
(29552, 'pending_finance_manager', 'ar', 'مدير مالي معلق'),
(29553, 'pending_gm', 'en', 'Pending GM'),
(29554, 'pending_gm', 'ar', 'المدير العام المعلق'),
(29555, 'pending_final_processing', 'en', 'Pending Final Processing'),
(29556, 'pending_final_processing', 'ar', 'في انتظار المعالجة النهائية'),
(29557, 'paid_and_closed', 'en', 'Paid and Closed'),
(29558, 'paid_and_closed', 'ar', 'تم الدفع وإغلاقه'),
(29559, 'all_requests_title', 'en', 'All Request\'s'),
(29560, 'all_requests_title', 'ar', 'جميع الطلبات'),
(29561, 'all_smart_requests_header', 'en', 'All Smart Requests'),
(29562, 'all_smart_requests_header', 'ar', 'جميع الطلبات الذكية'),
(29563, 'search_placeholder', 'en', 'Search...'),
(29564, 'search_placeholder', 'ar', 'بحث...'),
(29565, 'all_statuses_option', 'en', 'All Statuses'),
(29566, 'all_statuses_option', 'ar', 'جميع الحالات'),
(29567, 'draft_status', 'en', 'Draft'),
(29568, 'draft_status', 'ar', 'مسودة'),
(29569, 'pending_dept_manager_status', 'en', 'Pending Dept. Manager'),
(29570, 'pending_dept_manager_status', 'ar', 'بانتظار موافقة مدير القسم'),
(29571, 'pending_finance_status', 'en', 'Pending Finance'),
(29572, 'pending_finance_status', 'ar', 'بانتظار موافقة المالية'),
(29573, 'pending_gm_status', 'en', 'Pending GM'),
(29574, 'pending_gm_status', 'ar', 'بانتظار موافقة المدير العام'),
(29575, 'paid_status', 'en', 'Paid'),
(29576, 'paid_status', 'ar', 'مدفوع'),
(29577, 'invoice_no_header', 'en', 'Invoice No.'),
(29578, 'invoice_no_header', 'ar', 'رقم الفاتورة'),
(29579, 'subject_title_header', 'en', 'Subject Title'),
(29580, 'subject_title_header', 'ar', 'عنوان الموضوع'),
(29581, 'prepared_by_header', 'en', 'Prepared by'),
(29582, 'prepared_by_header', 'ar', 'أعدها'),
(29583, 'new_request_button', 'en', 'New Request'),
(29584, 'new_request_button', 'ar', 'طلب جديد'),
(29585, 'all_status_logs_button', 'en', 'All Status Logs'),
(29586, 'all_status_logs_button', 'ar', 'جميع سجلات الحالة'),
(30143, 'create_new_request', 'en', 'Create New Request'),
(30144, 'create_new_request', 'ar', 'إنشاء طلب جديد'),
(30145, 'invoice_date', 'en', 'Invoice Date'),
(30146, 'invoice_date', 'ar', 'تاريخ الفاتورة'),
(30147, 'sub_title_required', 'en', 'Sub-Title'),
(30148, 'sub_title_required', 'ar', 'العنوان الفرعي'),
(30149, 'sub_type_required', 'en', 'Sub. Type'),
(30150, 'sub_type_required', 'ar', 'النوع الفرعي'),
(30151, 'invoice_no', 'en', 'Invoice No.'),
(30152, 'invoice_no', 'ar', 'رقم الفاتورة'),
(30153, 'description_item_name_invoice_num', 'en', 'Description/Item Name/Invoice Num.'),
(30154, 'description_item_name_invoice_num', 'ar', 'الوصف/اسم الصنف/رقم الفاتورة'),
(30155, 'vat_percent', 'en', 'Vat%'),
(30156, 'vat_percent', 'ar', 'نسبة الضريبة'),
(30157, 'vat_val', 'en', 'Vat Val'),
(30158, 'vat_val', 'ar', 'قيمة الضريبة'),
(30159, 'net_total_without_vat', 'en', 'Net-Total (without VAT)'),
(30160, 'net_total_without_vat', 'ar', 'الإجمالي الصافي (بدون ضريبة القيمة المضافة)'),
(30161, 'vat_15_percent', 'en', 'VAT 15%'),
(30162, 'vat_15_percent', 'ar', 'ضريبة القيمة المضافة 15%'),
(30163, 'total_before_disc', 'en', 'Total (Before Disc.)'),
(30164, 'total_before_disc', 'ar', 'الإجمالي (قبل الخصم)'),
(30165, 'grand_total', 'en', 'Grand Total'),
(30166, 'grand_total', 'ar', 'المجموع الكلي'),
(30167, 'save', 'en', 'Save'),
(30168, 'save', 'ar', 'حفظ'),
(30169, 'add_row', 'en', 'Add Row'),
(30170, 'add_row', 'ar', 'إضافة صف'),
(30171, 'remove_row', 'en', 'Remove Row'),
(30172, 'remove_row', 'ar', 'إزالة صف'),
(30173, 'confirm_remove_item', 'en', 'Are you sure you want to remove this item?'),
(30174, 'confirm_remove_item', 'ar', 'هل أنت متأكد أنك تريد إزالة هذا العنصر؟'),
(30853, 'payment_processed_successfully', 'en', 'Payment processed successfully.'),
(30854, 'payment_processed_successfully', 'ar', 'تمت معالجة الدفع بنجاح.'),
(30855, 'failed_to_save_payment_details', 'en', 'Failed to save payment details.'),
(30856, 'failed_to_save_payment_details', 'ar', 'فشل حفظ تفاصيل الدفع.'),
(30857, 'error_uploading_file', 'en', 'Sorry, there was an error uploading your file.'),
(30858, 'error_uploading_file', 'ar', 'عذراً، حدث خطأ أثناء تحميل ملفك.'),
(30859, 'select_payment_invoice_to_upload', 'en', 'Please select a payment invoice to upload.'),
(30860, 'select_payment_invoice_to_upload', 'ar', 'يرجى تحديد فاتورة الدفع للتحميل.'),
(30861, 'added_successfully', 'en', 'Added Successfully!'),
(30862, 'added_successfully', 'ar', 'تمت الإضافة بنجاح!'),
(30863, 'fill_out_form_error', 'en', 'Please fill out the form!'),
(30864, 'fill_out_form_error', 'ar', 'يرجى ملء النموذج!'),
(30865, 'submitted_by_manager_for_finance_approval', 'en', 'Submitted by Manager for Finance Approval.'),
(30866, 'submitted_by_manager_for_finance_approval', 'ar', 'مقدمة من المدير للموافقة المالية.'),
(30867, 'submitted_for_approval', 'en', 'Submitted for approval.'),
(30868, 'submitted_for_approval', 'ar', 'مقدمة للموافقة.'),
(30869, 'approved_by_finance', 'en', 'Approved by Finance.'),
(30870, 'approved_by_finance', 'ar', 'معتمدة من المالية.'),
(30871, 'success_record_submitted', 'en', 'This record has been submitted successfully.'),
(30872, 'success_record_submitted', 'ar', 'تم إرسال هذا السجل بنجاح.'),
(30873, 'draft_not_submitted', 'en', 'Draft (Not Submitted)'),
(30874, 'draft_not_submitted', 'ar', 'مسودة (لم تقدم)'),
(30875, 'pending_department_manager_approval', 'en', 'Pending Department Manager Approval'),
(30876, 'pending_department_manager_approval', 'ar', 'بانتظار موافقة مدير القسم'),
(30877, 'pending_finance_approval', 'en', 'Pending Finance Approval'),
(30878, 'pending_finance_approval', 'ar', 'بانتظار موافقة المالية'),
(30879, 'pending_general_manager_approval', 'en', 'Pending General Manager Approval'),
(30880, 'pending_general_manager_approval', 'ar', 'بانتظار موافقة المدير العام'),
(30881, 'rejected_by_gm', 'en', 'Rejected by General Manager'),
(30882, 'rejected_by_gm', 'ar', 'مرفوضة من المدير العام'),
(30883, 'rejected_by_finance', 'en', 'Rejected by Finance'),
(30884, 'rejected_by_finance', 'ar', 'مرفوضة من المالية'),
(30885, 'rejected_by_dm', 'en', 'Rejected by Department Manager'),
(30886, 'rejected_by_dm', 'ar', 'مرفوضة من مدير القسم'),
(30887, 'rejected_by_system', 'en', 'Rejected by System'),
(30888, 'rejected_by_system', 'ar', 'مرفوضة من النظام'),
(30889, 'payment_paid', 'en', 'Payment Paid'),
(30890, 'payment_paid', 'ar', 'تم سداد الدفعة'),
(30891, 'unknown_status', 'en', 'Unknown Status'),
(30892, 'unknown_status', 'ar', 'حالة غير معروفة'),
(30893, 'request_rejected_reason', 'en', 'This request was <b>rejected</b>. Reason:'),
(30894, 'request_rejected_reason', 'ar', 'تم <b>رفض</b> هذا الطلب. السبب:'),
(30895, 'scan_qr_for_attachments', 'en', 'Scan QR from mobile Attachments'),
(30896, 'scan_qr_for_attachments', 'ar', 'امسح رمز الاستجابة السريعة من مرفقات الجوال'),
(30897, 'prepared_by', 'en', 'Prepared by'),
(30898, 'prepared_by', 'ar', 'أعدها'),
(30899, 'select_finance_approver', 'en', 'Select Finance Approver'),
(30900, 'select_finance_approver', 'ar', 'اختر موافقًا ماليًا'),
(30901, 'forward_to_gm', 'en', 'Forward to GM?'),
(30902, 'forward_to_gm', 'ar', 'إرسال إلى المدير العام؟'),
(30903, 'no', 'en', 'No'),
(30904, 'no', 'ar', 'لا'),
(30905, 'yes', 'en', 'Yes'),
(30906, 'yes', 'ar', 'نعم'),
(30907, 'rejection_note', 'en', 'Rejection Note'),
(30908, 'rejection_note', 'ar', 'ملاحظة الرفض'),
(30909, 'select_gm_approver', 'en', 'Select GM Approver'),
(30910, 'select_gm_approver', 'ar', 'اختر موافق المدير العام'),
(30911, 'payment_information', 'en', 'Payment Information'),
(30912, 'payment_information', 'ar', 'معلومات الدفع'),
(30913, 'paid_amount', 'en', 'Paid Amount'),
(30914, 'paid_amount', 'ar', 'المبلغ المدفوع'),
(30915, 'paid_by', 'en', 'Paid By'),
(30916, 'paid_by', 'ar', 'مدفوع بواسطة'),
(30917, 'on', 'en', 'on'),
(30918, 'on', 'ar', 'في'),
(30919, 'view_payment_invoice', 'en', 'View Payment Invoice'),
(30920, 'view_payment_invoice', 'ar', 'عرض فاتورة الدفع'),
(30921, 'existing_attachments', 'en', 'Existing Attachments'),
(30922, 'existing_attachments', 'ar', 'المرفقات الحالية'),
(30923, 'make_it_zoom', 'en', 'Make it Zoom'),
(30924, 'make_it_zoom', 'ar', 'تكبير'),
(30925, 'submit_for_approval', 'en', 'Submit for Approval'),
(30926, 'submit_for_approval', 'ar', 'إرسال للموافقة'),
(30927, 'add_line', 'en', 'Add Line'),
(30928, 'add_line', 'ar', 'إضافة سطر'),
(30929, 'edit_request_details', 'en', 'Edit Request Details'),
(30930, 'edit_request_details', 'ar', 'تعديل تفاصيل الطلب'),
(30931, 'print', 'en', 'Print'),
(30932, 'print', 'ar', 'طباعة'),
(30933, 'process_payment', 'en', 'Process Payment'),
(30934, 'process_payment', 'ar', 'معالجة الدفع'),
(30935, 'process_payment_for', 'en', 'Process Payment for'),
(30936, 'process_payment_for', 'ar', 'معالجة الدفع لـ'),
(30937, 'submit_payment', 'en', 'Submit Payment'),
(30938, 'submit_payment', 'ar', 'إرسال الدفع'),
(30939, 'fill_required_fields_error', 'en', 'Please fill out all required fields.'),
(30940, 'fill_required_fields_error', 'ar', 'يرجى ملء جميع الحقول المطلوبة.'),
(30941, 'paid_amount_sar', 'en', 'Paid Amount (SAR)'),
(30942, 'paid_amount_sar', 'ar', 'المبلغ المدفوع (ريال سعودي)'),
(30943, 'payment_invoice_receipt', 'en', 'Payment Invoice/Receipt'),
(30944, 'payment_invoice_receipt', 'ar', 'فاتورة الدفع/الإيصال'),
(30945, 'note_optional', 'en', 'Note (Optional)'),
(30946, 'note_optional', 'ar', 'ملاحظة (اختياري)'),
(30947, 'update_request_information', 'en', 'Update Request Information'),
(30948, 'update_request_information', 'ar', 'تحديث معلومات الطلب'),
(30949, 'update', 'en', 'Update'),
(30950, 'update', 'ar', 'تحديث'),
(30951, 'subject_type', 'en', 'Subject Type'),
(30952, 'subject_type', 'ar', 'نوع الموضوع'),
(30953, 'subject_title', 'en', 'Subject Title'),
(30954, 'subject_title', 'ar', 'عنوان الموضوع'),
(30955, 'update_line_information', 'en', 'Update Line information'),
(30956, 'update_line_information', 'ar', 'تحديث معلومات السطر'),
(30957, 'fill_required_fields_validation', 'en', 'Please fill all required fields.'),
(30958, 'fill_required_fields_validation', 'ar', 'يرجى ملء جميع الحقول المطلوبة.'),
(30959, 'approval_status', 'en', 'Approval status'),
(30960, 'approval_status', 'ar', 'حالة الموافقة'),
(30961, 'finance', 'en', 'Finance'),
(30962, 'finance', 'ar', 'المالية'),
(30963, 'pending_gm_approval', 'en', 'Pending gm approval'),
(30964, 'pending_gm_approval', 'ar', 'في انتظار موافقة المدير العام'),
(30965, 'pending_dept_manager_approval', 'en', 'Pending dept manager approval'),
(30966, 'pending_dept_manager_approval', 'ar', 'في انتظار موافقة مدير القسم'),
(30967, 'draft', 'en', 'Draft'),
(30968, 'draft', 'ar', 'مسودة'),
(30969, 'paid', 'en', 'Paid'),
(30970, 'paid', 'ar', 'مدفوع'),
(30971, 'payment_processed', 'en', 'Payment processed'),
(30972, 'payment_processed', 'ar', 'تمت معالجة الدفع'),
(30973, 'all_vouchers_title', 'en', 'All Vouchers'),
(30974, 'all_vouchers_title', 'ar', 'جميع القسائم'),
(30975, 'all_registered_items_header', 'en', 'All Registerd Items'),
(30976, 'all_registered_items_header', 'ar', 'جميع البنود المسجلة'),
(30977, 'all_option', 'en', 'All'),
(30978, 'all_option', 'ar', 'الكل'),
(30979, 'receipt_option', 'en', 'Receipt'),
(30980, 'receipt_option', 'ar', 'إيصال'),
(30981, 'payment_option', 'en', 'Payment'),
(30982, 'payment_option', 'ar', 'دفع'),
(30983, 'voucher_no_header', 'en', 'voucher_no'),
(30984, 'voucher_no_header', 'ar', 'رقم القسيمة'),
(30985, 'voucher_from_header', 'en', 'Voucher From'),
(30986, 'voucher_from_header', 'ar', 'قسيمة من'),
(30987, 'to_employee_header', 'en', 'To Employee'),
(30988, 'to_employee_header', 'ar', 'إلى الموظف'),
(30989, 'voucher_type_header', 'en', 'Voucher Type'),
(30990, 'voucher_type_header', 'ar', 'نوع القسيمة'),
(30991, 'amount_header', 'en', 'Amount'),
(30992, 'amount_header', 'ar', 'المبلغ'),
(30993, 'details_header', 'en', 'Details'),
(30994, 'details_header', 'ar', 'التفاصيل'),
(30995, 'created_at_header', 'en', 'Created At'),
(30996, 'created_at_header', 'ar', 'تم الإنشاء في'),
(30997, 'add_voucher_button', 'en', 'Add Voucher'),
(30998, 'add_voucher_button', 'ar', 'إضافة قسيمة'),
(30999, 'select_employee', 'en', 'Select employee'),
(31000, 'select_employee', 'ar', 'اختر الموظف'),
(31001, 'select_voucher', 'en', 'Select voucher'),
(31002, 'select_voucher', 'ar', 'اختر القسيمة'),
(31003, 'details', 'en', 'Details'),
(31004, 'details', 'ar', 'تفاصيل'),
(31005, 'cheque_no', 'en', 'Cheque No.'),
(31006, 'cheque_no', 'ar', 'رقم الاختيار'),
(31007, 'payment_receipt', 'en', 'Payment receipt'),
(31008, 'payment_receipt', 'ar', 'إيصال الدفع'),
(31009, 'payment_voucher', 'en', 'Payment voucher'),
(31010, 'payment_voucher', 'ar', 'قسيمة الدفع'),
(31011, 'error_record_submitted', 'en', 'Record not added because there are some error'),
(31012, 'error_record_submitted', 'ar', 'لم يتم إضافة السجل بسبب وجود بعض الأخطاء.'),
(31013, 'year', 'en', 'Year'),
(31014, 'year', 'ar', 'سنة'),
(31015, 'this_record_has_been_updated_successfully', 'en', 'This record has been updated successfully.'),
(31016, 'this_record_has_been_updated_successfully', 'ar', 'تم تحديث هذا السجل بنجاح.'),
(31107, 'car_view_title', 'en', 'Car View'),
(31108, 'car_view_title', 'ar', 'عرض السيارة'),
(31109, 'maker_name_label', 'en', 'Maker Name'),
(31110, 'maker_name_label', 'ar', 'اسم الصانع'),
(31111, 'model_label', 'en', 'Model'),
(31112, 'model_label', 'ar', 'الموديل'),
(31113, 'made_year_label', 'en', 'Made Year'),
(31114, 'made_year_label', 'ar', 'سنة الصنع'),
(31115, 'remarks_label', 'en', 'Remarks'),
(31116, 'remarks_label', 'ar', 'ملاحظات'),
(31117, 'plate_no_label', 'en', 'Plate No'),
(31118, 'plate_no_label', 'ar', 'رقم اللوحة'),
(31119, 'type_label', 'en', 'Type'),
(31120, 'type_label', 'ar', 'النوع'),
(31121, 'date_registration_label', 'en', 'Date Registration'),
(31122, 'date_registration_label', 'ar', 'تاريخ التسجيل'),
(31123, 'add_docs_button', 'en', 'Add Docs.'),
(31124, 'add_docs_button', 'ar', 'إضافة مستندات'),
(31125, 'add_driver_button', 'en', 'Add Driver'),
(31126, 'add_driver_button', 'ar', 'إضافة سائق'),
(31127, 'return_car_button', 'en', 'Return Car'),
(31128, 'return_car_button', 'ar', 'إرجاع السيارة'),
(31129, 'add_maintenance_button', 'en', 'Add Maintenance'),
(31130, 'add_maintenance_button', 'ar', 'إضافة صيانة'),
(31131, 'edit_button', 'en', 'Edit'),
(31132, 'edit_button', 'ar', 'تعديل'),
(31133, 'driver_label', 'en', 'Driver'),
(31134, 'driver_label', 'ar', 'السائق'),
(31135, 'no_driver_text', 'en', 'No Driver'),
(31136, 'no_driver_text', 'ar', 'لا يوجد سائق'),
(31137, 'receive_date_label', 'en', 'Receive Date'),
(31138, 'receive_date_label', 'ar', 'تاريخ الاستلام'),
(31139, 'licence_label', 'en', 'Licence'),
(31140, 'licence_label', 'ar', 'رخصة'),
(31141, 'days_text', 'en', 'Day(s)'),
(31142, 'days_text', 'ar', 'أيام'),
(31143, 'expiry_date_label', 'en', 'Expiry Date'),
(31144, 'expiry_date_label', 'ar', 'تاريخ الانتهاء'),
(31145, 'insurance_label', 'en', 'Insurance'),
(31146, 'insurance_label', 'ar', 'تأمين'),
(31147, 'mvpi_label', 'en', 'MVPI'),
(31148, 'mvpi_label', 'ar', 'الفحص الدوري'),
(31149, 'documents_details_header', 'en', 'Documents Details'),
(31150, 'documents_details_header', 'ar', 'تفاصيل المستندات'),
(31151, 'documents_type_header', 'en', 'Documents Type'),
(31152, 'documents_type_header', 'ar', 'نوع المستندات'),
(31153, 'issue_date_header', 'en', 'Issue Date'),
(31154, 'issue_date_header', 'ar', 'تاريخ الإصدار'),
(31155, 'expiry_date_header', 'en', 'Expiry Date'),
(31156, 'expiry_date_header', 'ar', 'تاريخ الانتهاء'),
(31157, 'attachment_header', 'en', 'Attachment'),
(31158, 'attachment_header', 'ar', 'المرفق'),
(31159, 'reg_date_header', 'en', 'Reg. Date'),
(31160, 'reg_date_header', 'ar', 'تاريخ التسجيل'),
(31161, 'view_file_button', 'en', 'View File'),
(31162, 'view_file_button', 'ar', 'عرض الملف'),
(31163, 'no_file_text', 'en', 'No File'),
(31164, 'no_file_text', 'ar', 'لا يوجد ملف'),
(31165, 'drivers_details_header', 'en', 'Driver(s) Details'),
(31166, 'drivers_details_header', 'ar', 'تفاصيل السائق (السائقين)'),
(31167, 'drivers_name_header', 'en', 'Driver(s) Name'),
(31168, 'drivers_name_header', 'ar', 'اسم السائق (السائقين)'),
(31169, 'receiving_header', 'en', 'Receiving'),
(31170, 'receiving_header', 'ar', 'الاستلام'),
(31171, 'return_date_header', 'en', 'Return Date'),
(31172, 'return_date_header', 'ar', 'تاريخ الإرجاع'),
(31173, 'status_header', 'en', 'Status'),
(31174, 'status_header', 'ar', 'الحالة'),
(31175, 'on_job_text', 'en', 'ON JOB'),
(31176, 'on_job_text', 'ar', 'على رأس العمل'),
(31177, 'on_driving_text', 'en', 'On Driving'),
(31178, 'on_driving_text', 'ar', 'أثناء القيادة'),
(31179, 'returned_text', 'en', 'Returned'),
(31180, 'returned_text', 'ar', 'تم الإرجاع'),
(31181, 'maintenance_details_header', 'en', 'Maintenance Details'),
(31182, 'maintenance_details_header', 'ar', 'تفاصيل الصيانة'),
(31183, 'meter_reading_header', 'en', 'Meter Reading'),
(31184, 'meter_reading_header', 'ar', 'قراءة العداد'),
(31185, 'difference_reading_header', 'en', 'Difference Readin'),
(31186, 'difference_reading_header', 'ar', 'قراءة الفرق'),
(31187, 'date_header', 'en', 'Date'),
(31188, 'date_header', 'ar', 'التاريخ'),
(31189, 'type_of_maint_header', 'en', 'Type of Maint.'),
(31190, 'type_of_maint_header', 'ar', 'نوع الصيانة'),
(31191, 'remarks_header', 'en', 'Remarks'),
(31192, 'remarks_header', 'ar', 'ملاحظات'),
(31193, 'created_header', 'en', 'Created'),
(31194, 'created_header', 'ar', 'تم الإنشاء'),
(31195, 'select_date', 'en', 'Select date'),
(31196, 'select_date', 'ar', 'تحديد التاريخ'),
(31197, 'all_locations_title', 'en', 'All Locations'),
(31198, 'all_locations_title', 'ar', 'جميع المواقع'),
(31199, 'all_registered_locations_header', 'en', 'All Registerd Locations'),
(31200, 'all_registered_locations_header', 'ar', 'جميع المواقع المسجلة'),
(31201, 'no_image_text', 'en', 'No Image'),
(31202, 'no_image_text', 'ar', 'لا توجد صورة'),
(31203, 'active_status', 'en', 'Active'),
(31204, 'active_status', 'ar', 'نشط'),
(31205, 'closed_status', 'en', 'Closed'),
(31206, 'closed_status', 'ar', 'مغلق'),
(31207, 'open_action', 'en', 'Open'),
(31208, 'open_action', 'ar', 'فتح'),
(31209, 'confirm_update_status_text', 'en', 'You wannt to update this status!'),
(31210, 'confirm_update_status_text', 'ar', 'هل تريد تحديث هذه الحالة!'),
(31211, 'yes_update_button', 'en', 'Yes, update!'),
(31212, 'yes_update_button', 'ar', 'نعم، تحديث!'),
(31213, 'location_name_label', 'en', 'Location Name'),
(31214, 'location_name_label', 'ar', 'اسم الموقع'),
(31215, 'total_building_size_label', 'en', 'Total Bulding Size'),
(31216, 'total_building_size_label', 'ar', 'إجمالي مساحة المبنى'),
(31217, 'district_label', 'en', 'District'),
(31218, 'district_label', 'ar', 'الحي'),
(31219, 'camera_in_label', 'en', 'Camera (IN)'),
(31220, 'camera_in_label', 'ar', 'كاميرا (داخلية)'),
(31221, 'camera_out_label', 'en', 'Camera (Out)'),
(31222, 'camera_out_label', 'ar', 'كاميرا (خارجية)'),
(31223, 'owner_name_label', 'en', 'Owner Name'),
(31224, 'owner_name_label', 'ar', 'اسم المالك'),
(31225, 'license_no_label', 'en', 'License No'),
(31226, 'license_no_label', 'ar', 'رقم الرخصصة'),
(31227, 'license_exp_label', 'en', 'License Exp.'),
(31228, 'license_exp_label', 'ar', 'انتهاء الرخصة'),
(31229, 'municipality_label', 'en', 'Municipality'),
(31230, 'municipality_label', 'ar', 'البلدية'),
(31231, 'sub_municipality_label', 'en', 'Sub-Municipality'),
(31232, 'sub_municipality_label', 'ar', 'البلدية الفرعية'),
(31233, 'total_machines_label', 'en', 'Total Machines'),
(31234, 'total_machines_label', 'ar', 'إجمالي الأجهزة'),
(31235, 'latitude_label', 'en', 'Latitude'),
(31236, 'latitude_label', 'ar', 'خط العرض'),
(31237, 'longitude_label', 'en', 'Longitude'),
(31238, 'longitude_label', 'ar', 'خط الطول'),
(31239, 'building_base_label', 'en', 'Bulding Base'),
(31240, 'building_base_label', 'ar', 'قاعدة المبنى'),
(31241, 'building_size_label', 'en', 'Bulding Size'),
(31242, 'building_size_label', 'ar', 'مساحة المبنى'),
(31243, 'upload_documents_button', 'en', 'Upload Documents'),
(31244, 'upload_documents_button', 'ar', 'تحميل المستندات'),
(31245, 'add_contract_button', 'en', 'Add Contract'),
(31246, 'add_contract_button', 'ar', 'إضافة عقد'),
(31247, 'location_google_map_header', 'en', 'Location Google map.'),
(31248, 'location_google_map_header', 'ar', 'خريطة جوجل للموقع.'),
(31249, 'inside_image_header', 'en', 'Inside Image'),
(31250, 'inside_image_header', 'ar', 'صورة داخلية'),
(31251, 'no_inside_image_alt', 'en', 'No Uploaded Image for Inside of Shop'),
(31252, 'no_inside_image_alt', 'ar', 'لا توجد صورة محملة لداخل المحل'),
(31253, 'inside_image_of_text', 'en', 'Inside image of'),
(31254, 'inside_image_of_text', 'ar', 'صورة داخلية لـ'),
(31255, 'outside_image_header', 'en', 'Outside Image'),
(31256, 'outside_image_header', 'ar', 'صورة خارجية'),
(31257, 'no_outside_image_alt', 'en', 'No Uploaded Image for Inside of Shop'),
(31258, 'no_outside_image_alt', 'ar', 'لا توجد صورة محملة لخارج المحل'),
(31259, 'outside_image_of_text', 'en', 'Outside image of'),
(31260, 'outside_image_of_text', 'ar', 'صورة خارجية لـ'),
(31261, 'documents_for_location_header', 'en', 'Documents for location'),
(31262, 'documents_for_location_header', 'ar', 'مستندات الموقع'),
(31263, 'contract_detail_header', 'en', 'Contract Detail'),
(31264, 'contract_detail_header', 'ar', 'تفاصيل العقد'),
(31265, 'sr_no_header', 'en', 'Sr.'),
(31266, 'sr_no_header', 'ar', 'م.'),
(31267, 'owner_name_header', 'en', 'Owner Name'),
(31268, 'owner_name_header', 'ar', 'اسم المالك'),
(31269, 'contact_header', 'en', 'Contact'),
(31270, 'contact_header', 'ar', 'جهة الاتصال'),
(31271, 'email_header', 'en', 'Email'),
(31272, 'email_header', 'ar', 'البريد الإلكتروني'),
(31273, 'contract_no_header', 'en', 'Contract No.'),
(31274, 'contract_no_header', 'ar', 'رقم العقد'),
(31275, 'start_date_header', 'en', 'Start Date'),
(31276, 'start_date_header', 'ar', 'تاريخ البدء'),
(31277, 'end_date_header', 'en', 'End Date'),
(31278, 'end_date_header', 'ar', 'تاريخ الانتهاء'),
(31279, 'rent_header', 'en', 'Rent'),
(31280, 'rent_header', 'ar', 'الإيجار'),
(31281, 'machines_detail_header', 'en', 'Machines Detail'),
(31282, 'machines_detail_header', 'ar', 'تفاصيل الأجهزة'),
(31283, 'machine_name_header', 'en', 'Machine Name'),
(31284, 'machine_name_header', 'ar', 'اسم الجهاز'),
(31285, 'm_id_header', 'en', 'M. ID'),
(31286, 'm_id_header', 'ar', 'معرف الجهاز'),
(31287, 'serial_header', 'en', 'Serial'),
(31288, 'serial_header', 'ar', 'الرقم التسلسلي'),
(31289, 'model_header', 'en', 'Model'),
(31290, 'model_header', 'ar', 'الموديل'),
(31569, 'sr_header', 'en', 'sr'),
(31570, 'sr_header', 'ar', 'الرقم'),
(31571, 'section_name_header', 'en', 'Section Name'),
(31572, 'section_name_header', 'ar', 'اسم القسم'),
(31573, 'department_header', 'en', 'Department'),
(31574, 'department_header', 'ar', 'القسم'),
(31575, 'building_base_header', 'en', 'Bulding Base'),
(31576, 'building_base_header', 'ar', 'قاعدة المبنى'),
(31577, 'building_size_header', 'en', 'Bulding Size'),
(31578, 'building_size_header', 'ar', 'مساحة المبنى'),
(31579, 'address_header', 'en', 'Address'),
(31580, 'address_header', 'ar', 'العنوان'),
(31581, 'devices_header', 'en', 'Devices'),
(31582, 'devices_header', 'ar', 'الأجهزة'),
(31583, 'action_header', 'en', 'Action'),
(31584, 'action_header', 'ar', 'الإجراء'),
(31585, 'open_link', 'en', 'Open'),
(31586, 'open_link', 'ar', 'فتح'),
(31587, 'delete_link', 'en', 'Delete'),
(31588, 'delete_link', 'ar', 'حذف'),
(31589, 'add_location_button', 'en', 'Add Location'),
(31590, 'add_location_button', 'ar', 'إضافة موقع'),
(31591, 'update_status_confirm_title', 'en', 'Are you sure?'),
(31592, 'update_status_confirm_title', 'ar', 'هل أنت متأكد؟'),
(31593, 'update_status_confirm_text', 'en', 'You wannt to update this status!'),
(31594, 'update_status_confirm_text', 'ar', 'هل تريد تحديث هذه الحالة!'),
(31595, 'enter_section_name_placeholder', 'en', 'Enter section name'),
(31596, 'enter_section_name_placeholder', 'ar', 'أدخل اسم القسم'),
(32197, 'no_uploaded_image_inside', 'en', 'No Uploaded Image for Inside of Shop'),
(32198, 'no_uploaded_image_inside', 'ar', 'لا توجد صورة محملة لداخل المحل'),
(32199, 'inside_image_of', 'en', 'Inside image of'),
(32200, 'inside_image_of', 'ar', 'الصورة الداخلية ل'),
(32201, 'outside_image_of', 'en', 'Outside image of'),
(32202, 'outside_image_of', 'ar', 'الصورة الخارجية ل'),
(32203, 'documents_for_location', 'en', 'Documents for location'),
(32204, 'documents_for_location', 'ar', 'مستندات الموقع'),
(32205, 'existing_attachments_header', 'en', 'Existing Attachments'),
(32206, 'existing_attachments_header', 'ar', 'المرفقات الحالية'),
(32207, 'total_building_size', 'en', 'Total building size'),
(32208, 'total_building_size', 'ar', 'الحجم الإجمالي للمبنى'),
(32209, 'owner_name', 'en', 'Owner name'),
(32210, 'owner_name', 'ar', 'اسم المالك'),
(32211, 'license_no', 'en', 'License no'),
(32212, 'license_no', 'ar', 'رقم الرخصة'),
(32213, 'license_exp', 'en', 'license expiry'),
(32214, 'license_exp', 'ar', 'انتهاء صلاحية الترخيص'),
(32215, 'total_machines', 'en', 'Total machines'),
(32216, 'total_machines', 'ar', 'مجموع الآلات'),
(32217, 'building_size', 'en', 'Building size'),
(32218, 'building_size', 'ar', 'حجم المبنى'),
(33995, 'personal_employment_details_header', 'en', 'Personal & Employment Details'),
(33996, 'personal_employment_details_header', 'ar', 'التفاصيل الشخصية والوظيفية'),
(33997, 'date_label', 'en', 'Date'),
(33998, 'date_label', 'ar', 'التاريخ'),
(33999, 'personal_information_header', 'en', 'Personal Information'),
(34000, 'personal_information_header', 'ar', 'معلومات شخصية'),
(34001, 'iqama_id_label', 'en', 'Iqama / ID'),
(34002, 'iqama_id_label', 'ar', 'الإقامة / الهوية'),
(34003, 'expires_label', 'en', 'Expires'),
(34004, 'expires_label', 'ar', 'تنتهي في'),
(34005, 'passport_label', 'en', 'Passport'),
(34006, 'passport_label', 'ar', 'جواز السفر'),
(34007, 'dob_label', 'en', 'Date of Birth'),
(34008, 'dob_label', 'ar', 'تاريخ الميلاد'),
(34009, 'employment_information_header', 'en', 'Employment Information'),
(34010, 'employment_information_header', 'ar', 'معلومات التوظيف'),
(34011, 'date_hired_label', 'en', 'Date Hired'),
(34012, 'date_hired_label', 'ar', 'تاريخ التعيين'),
(34013, 'working_period', 'en', 'Working Period'),
(34014, 'working_period', 'ar', 'فترة العمل'),
(34015, 'status_label', 'en', 'Status'),
(34016, 'status_label', 'ar', 'الحالة'),
(34017, 'contact_label', 'en', 'Contact'),
(34018, 'contact_label', 'ar', 'جهة الاتصال'),
(34019, 'financial_details_header', 'en', 'Financial Details'),
(34020, 'financial_details_header', 'ar', 'التفاصيل المالية'),
(34021, 'salary_breakdown_header', 'en', 'Salary Breakdown'),
(34022, 'salary_breakdown_header', 'ar', 'تفاصيل الراتب'),
(34023, 'basic_label', 'en', 'Basic'),
(34024, 'basic_label', 'ar', 'أساسي'),
(34025, 'housing_label', 'en', 'Housing'),
(34026, 'housing_label', 'ar', 'السكن'),
(34027, 'transport_label', 'en', 'Transport'),
(34028, 'transport_label', 'ar', 'النقل'),
(34029, 'food_label', 'en', 'Food'),
(34030, 'food_label', 'ar', 'الطعام'),
(34031, 'misc_label', 'en', 'Misc'),
(34032, 'misc_label', 'ar', 'متنوع'),
(34033, 'cashier_label', 'en', 'Cashier'),
(34034, 'cashier_label', 'ar', 'أمين الصندوق'),
(34035, 'fuel_label', 'en', 'Fuel'),
(34036, 'fuel_label', 'ar', 'الوقود'),
(34037, 'tel_label', 'en', 'Tel'),
(34038, 'tel_label', 'ar', 'هاتف'),
(34039, 'other_label', 'en', 'Other'),
(34040, 'other_label', 'ar', 'أخرى'),
(34041, 'guard_label', 'en', 'Guard'),
(34042, 'guard_label', 'ar', 'حارس'),
(34043, 'total_salary_label', 'en', 'Total Salary'),
(34044, 'total_salary_label', 'ar', 'إجمالي الراتب'),
(34045, 'bank_insurance_header', 'en', 'Bank & Insurance'),
(34046, 'bank_insurance_header', 'ar', 'البنك والتأمين'),
(34047, 'gosi_no_label', 'en', 'Gosi No'),
(34048, 'gosi_no_label', 'ar', 'رقم التأمينات الاجتماعية'),
(34049, 'gosi_payment_label', 'en', 'Gosi Payment'),
(34050, 'gosi_payment_label', 'ar', 'دفع التأمينات الاجتماعية'),
(34051, 'insurance_expiry_label', 'en', 'Insurance Expiry'),
(34052, 'insurance_expiry_label', 'ar', 'انتهاء صلاحية التأمين'),
(34053, 'assigned_assets_header', 'en', 'Assigned Assets'),
(34054, 'assigned_assets_header', 'ar', 'الأصول المعينة'),
(34055, 'assigned_car_header', 'en', 'Assigned Car'),
(34056, 'assigned_car_header', 'ar', 'السيارة المعينة'),
(34057, 'maker_model_label', 'en', 'Maker & Model'),
(34058, 'maker_model_label', 'ar', 'الصانع والموديل'),
(34059, 'other_assets_header', 'en', 'Other Assets'),
(34060, 'other_assets_header', 'ar', 'أصول أخرى'),
(34061, 'loan_history_header', 'en', 'Loan History'),
(34062, 'loan_history_header', 'ar', 'سجل القروض'),
(34063, 'deduction_header', 'en', 'Deduction'),
(34064, 'deduction_header', 'ar', 'الخصم'),
(34065, 'balance_header', 'en', 'Balance'),
(34066, 'balance_header', 'ar', 'الرصيد'),
(34067, 'start_header', 'en', 'Start'),
(34068, 'start_header', 'ar', 'البداية'),
(34069, 'end_header', 'en', 'End'),
(34070, 'end_header', 'ar', 'النهاية'),
(34071, 'type_header', 'en', 'Type'),
(34072, 'type_header', 'ar', 'النوع'),
(34073, 'vacation_history_header', 'en', 'Vacation History'),
(34074, 'vacation_history_header', 'ar', 'سجل الإجازات'),
(34075, 'days_header', 'en', 'Days'),
(34076, 'days_header', 'ar', 'أيام'),
(34077, 'permit_no_header', 'en', 'Permit No.'),
(34078, 'permit_no_header', 'ar', 'رقم التصريح'),
(34079, 'arrived_header', 'en', 'Arrived'),
(34080, 'arrived_header', 'ar', 'وصل'),
(34081, 'not_yet_text', 'en', 'Not Yet'),
(34082, 'not_yet_text', 'ar', 'ليس بعد'),
(34083, 'notes_notices_header', 'en', 'Notes / Notices'),
(34084, 'notes_notices_header', 'ar', 'ملاحظات / إشعارات'),
(34085, 'note_header', 'en', 'Note'),
(34086, 'note_header', 'ar', 'ملاحظة'),
(34087, 'employee_documents_header', 'en', 'Employee Documents'),
(34088, 'employee_documents_header', 'ar', 'مستندات الموظف'),
(34089, 'happy_life_with_us', 'en', 'Happy life with us'),
(34090, 'happy_life_with_us', 'ar', 'حياة سعيدة معنا'),
(34091, 'coming_soon_expiry_with_30_days', 'en', 'Coming soon expiry with 30 days'),
(34092, 'coming_soon_expiry_with_30_days', 'ar', 'قادم قريبًا تاريخ الانتهاء خلال 30 يومًا'),
(34291, 'invalid_request_parameters', 'en', 'Invalid request parameters.'),
(34292, 'invalid_request_parameters', 'ar', 'معلمات الطلب غير صالحة.'),
(34293, 'loan_request_not_found', 'en', 'Loan request not found.'),
(34294, 'loan_request_not_found', 'ar', 'لم يتم العثور على طلب القرض.'),
(34295, 'loan_report_title', 'en', 'Loan Report'),
(34296, 'loan_report_title', 'ar', 'تقرير القرض'),
(34297, 'print_report_button', 'en', 'Print Report'),
(34298, 'print_report_button', 'ar', 'طباعة التقرير'),
(34299, 'loan_request_report_header', 'en', 'Loan Request Report'),
(34300, 'loan_request_report_header', 'ar', 'تقرير طلب القرض'),
(34301, 'loan_id_label', 'en', 'Loan ID'),
(34302, 'loan_id_label', 'ar', 'معرف القرض'),
(34303, 'loan_details_header', 'en', 'Loan Details'),
(34304, 'loan_details_header', 'ar', 'تفاصيل القرض'),
(34305, 'total_payable_label', 'en', 'Total Payable'),
(34306, 'total_payable_label', 'ar', 'إجمالي المبلغ المستحق'),
(34307, 'start_date_label', 'en', 'Start Date'),
(34308, 'start_date_label', 'ar', 'تاريخ البدء'),
(34309, 'end_date_label', 'en', 'End Date'),
(34310, 'end_date_label', 'ar', 'تاريخ الانتهاء'),
(34311, 'current_status_label', 'en', 'Current Status'),
(34312, 'current_status_label', 'ar', 'الحالة الحالية'),
(34313, 'payment_history_header', 'en', 'Payment History'),
(34314, 'payment_history_header', 'ar', 'سجل الدفعات'),
(34315, 'no_payments_recorded_yet', 'en', 'No payments have been recorded yet.'),
(34316, 'no_payments_recorded_yet', 'ar', 'لم يتم تسجيل أي مدفوعات حتى الآن.'),
(34317, 'method_header', 'en', 'Method'),
(34318, 'method_header', 'ar', 'الطريقة'),
(34319, 'approval_history_header', 'en', 'Approval History'),
(34320, 'approval_history_header', 'ar', 'سجل الموافقات'),
(34321, 'no_approval_history_found', 'en', 'No approval history found.'),
(34322, 'no_approval_history_found', 'ar', 'لم يتم العثور على سجل موافقات.'),
(34323, 'note_label', 'en', 'Note'),
(34324, 'note_label', 'ar', 'ملاحظة'),
(34325, 'manual', 'en', 'Manual'),
(34326, 'manual', 'ar', 'يدوي'),
(34327, 'dept_manager', 'en', 'Department Manager'),
(34328, 'dept_manager', 'ar', 'مدير القسم'),
(34329, 'hr_assistant', 'en', 'HR Assistant'),
(34330, 'hr_assistant', 'ar', 'مساعد الموارد البشرية'),
(34331, 'hr_manager', 'en', 'HR Manager'),
(34332, 'hr_manager', 'ar', 'مدير الموارد البشرية'),
(34333, 'finance_manager', 'en', 'Finance Manager'),
(34334, 'finance_manager', 'ar', 'مدير مالي'),
(34335, 'gm', 'en', 'GM'),
(34336, 'gm', 'ar', 'المدير العام'),
(34337, 'finance_assistant', 'en', 'Finance Assistant'),
(34338, 'finance_assistant', 'ar', 'مساعد مالي'),
(34339, 'items_per_page', 'en', 'Items per page'),
(34340, 'items_per_page', 'ar', 'العناصر في كل صفحة'),
(34341, 'search_by_name_id_mobile_iqama_id', 'en', 'Search by Name /Emp. ID / Mobile / Iqama / ID No.'),
(34342, 'search_by_name_id_mobile_iqama_id', 'ar', 'البحث حسب الاسم / رقم الموظف / رقم الجوال / الإقامة / رقم الهوية.'),
(34343, 'showing_all', 'en', 'Showing all'),
(34344, 'showing_all', 'ar', 'عرض الكل'),
(34345, 'on_vacations', 'en', 'On vacations'),
(34346, 'on_vacations', 'ar', 'في العطلات'),
(34347, 'no_employees_found_matching_your_criteria_in_this_department', 'en', 'No employees found matching your criteria in this department'),
(34348, 'no_employees_found_matching_your_criteria_in_this_department', 'ar', 'لم يتم العثور على موظفين يطابقون معاييرك في هذا القسم.'),
(34349, 'email_verification', 'en', 'Email verification'),
(34350, 'email_verification', 'ar', 'التحقق من البريد الإلكتروني'),
(34351, 'enter_the_6digit_code_sent_to_your_registered_email_address', 'en', 'Enter the 6digit code sent to your registered email address'),
(34352, 'enter_the_6digit_code_sent_to_your_registered_email_address', 'ar', 'أدخل الرمز المكون من 6 أرقام المرسل إلى عنوان بريدك الإلكتروني المسجل.'),
(34353, 'otp_error_expired', 'en', 'Your OTP has expired. Please try logging in again.'),
(34354, 'otp_error_expired', 'ar', 'انتهت صلاحية كلمة المرور لمرة واحدة (OTP). يُرجى محاولة تسجيل الدخول مرة أخرى.'),
(34355, 'otp_error_invalid_format', 'en', 'Invalid OTP format. Please enter all 6 digits.'),
(34356, 'otp_error_invalid_format', 'ar', 'صيغة OTP غير صحيحة. يُرجى إدخال جميع الأرقام الستة.'),
(34357, 'otp_error_too_many_attempts', 'en', 'Too many failed attempts. Please try logging in again.'),
(34358, 'otp_error_too_many_attempts', 'ar', 'محاولات فاشلة كثيرة. يُرجى محاولة تسجيل الدخول مرة أخرى.'),
(34361, 'otp_error_incorrect', 'en', 'The OTP you entered is incorrect. Please try again.'),
(34362, 'otp_error_incorrect', 'ar', 'كلمة المرور لمرة واحدة (OTP) التي أدخلتها غير صحيحة. يُرجى المحاولة مرة أخرى.'),
(34363, 'you_can_resend_otp_in', 'en', 'You can resend otp in'),
(34364, 'you_can_resend_otp_in', 'ar', 'يمكنك إعادة إرسال OTP في'),
(34365, 'resend_otp', 'en', 'Resend otp'),
(34366, 'resend_otp', 'ar', 'إعادة إرسال OTP'),
(34367, 'signout_successfully', 'en', 'Signout Successfully...'),
(34368, 'signout_successfully', 'ar', 'تم تسجيل الخروج بنجاح...'),
(34369, 'unable_to_signout_this_action', 'en', 'Unable to signout this action'),
(34370, 'unable_to_signout_this_action', 'ar', 'غير قادر على تسجيل الخروج من هذا الإجراء...'),
(34371, 'welcome_message', 'en', 'Welcome!'),
(34372, 'welcome_message', 'ar', 'مرحباً!'),
(34373, 'my_account', 'en', 'My Account'),
(34374, 'my_account', 'ar', 'حسابي'),
(34375, 'logout_button', 'en', 'Logout'),
(34376, 'logout_button', 'ar', 'تسجيل الخروج'),
(34377, 'a_new_otp_has_been_sent_to_your_email', 'en', 'A new OTP has been sent to your email.'),
(34378, 'a_new_otp_has_been_sent_to_your_email', 'ar', 'لقد تم إرسال OTP جديد إلى بريدك الإلكتروني.'),
(34379, 'email_heading', 'en', 'Confirm Your Identity'),
(34380, 'email_heading', 'ar', 'تأكيد هويتك'),
(34381, 'email_greeting', 'en', 'Hello'),
(34382, 'email_greeting', 'ar', 'مرحبًا'),
(34383, 'email_verification_code_label', 'en', 'your single-use verification code is below.'),
(34384, 'email_verification_code_label', 'ar', 'رمز التحقق الخاص بك للاستخدام مرة واحدة موجود أدناه'),
(34385, 'email_expiration_notice', 'en', 'For your security, this code will expire in 2 minutes.'),
(34386, 'email_expiration_notice', 'ar', 'لأجل أمنك، هذا الرمز سوف ينتهي صلاحيته خلال دقيقتين.'),
(34387, 'email_security_notice', 'en', 'You are receiving this email because a login attempt was made on your account. If this was not you, please secure your account immediately.'),
(34388, 'email_security_notice', 'ar', 'لقد وصلتك هذه الرسالة الإلكترونية بسبب محاولة تسجيل دخول إلى حسابك. إذا لم تكن أنت من قام بذلك، يُرجى تأمين حسابك فورًا.'),
(34389, 'email_otp_subject', 'en', 'Your Login OTP Code'),
(34390, 'email_otp_subject', 'ar', 'رمز تسجيل الدخول الخاص بك'),
(34391, 'verification_code', 'en', 'Verification Code'),
(34392, 'verification_code', 'ar', 'رمز التحقق');

-- --------------------------------------------------------

--
-- Table structure for table `usersimage`
--

CREATE TABLE `usersimage` (
  `uid` int(11) NOT NULL,
  `user` varchar(255) DEFAULT NULL,
  `pass` varchar(100) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `profile_photo` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `usersimage`
--

INSERT INTO `usersimage` (`uid`, `user`, `pass`, `email`, `profile_photo`) VALUES
(1, 'anees', '6539306', 'aneesmug@2007.com', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `vac_sch`
--

CREATE TABLE `vac_sch` (
  `id` int(11) NOT NULL,
  `emp_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `dept` varchar(255) NOT NULL,
  `replacement_per` varchar(255) NOT NULL,
  `vac_strt_date` varchar(50) NOT NULL,
  `last_vac_date` varchar(50) NOT NULL,
  `next_vac_date` varchar(50) NOT NULL,
  `note` text NOT NULL,
  `vacation_days` varchar(50) NOT NULL,
  `date_reg` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vouchers`
--

CREATE TABLE `vouchers` (
  `id` int(11) NOT NULL,
  `emp_id` varchar(10) NOT NULL,
  `to_emp` varchar(255) NOT NULL,
  `voucher_no` varchar(30) NOT NULL,
  `voucher_type` varchar(10) NOT NULL,
  `voucher_amount` decimal(10,2) DEFAULT NULL,
  `details` varchar(255) NOT NULL,
  `acc_no` varchar(255) NOT NULL,
  `chq_no` varchar(255) NOT NULL,
  `dept` varchar(100) NOT NULL,
  `file` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vouchers`
--

INSERT INTO `vouchers` (`id`, `emp_id`, `to_emp`, `voucher_no`, `voucher_type`, `voucher_amount`, `details`, `acc_no`, `chq_no`, `dept`, `file`, `created_at`) VALUES
(2, '5430', '5430', 'R54302509020132', 'receipt', 4467.00, 'no any.', '', '', '6', '', '2025-09-02 06:01:32'),
(3, '5430', '4119', 'P54302509021905', 'payment', 466.00, 'no', '', '', '6', '', '2025-09-02 06:19:05'),
(4, '5430', '2372', 'P54302509022008', 'payment', 3456.00, 'dfgvbn ', '', '', '6', '', '2025-09-02 06:20:08');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ac_jobs`
--
ALTER TABLE `ac_jobs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admin_login`
--
ALTER TABLE `admin_login`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`),
  ADD UNIQUE KEY `id_iqama` (`id_iqama`,`emp_id`) USING BTREE,
  ADD KEY `otp_index` (`otp`,`otp_expiration`);

--
-- Indexes for table `apply_vac_dep`
--
ALTER TABLE `apply_vac_dep`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `assets`
--
ALTER TABLE `assets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uid` (`uid`);

--
-- Indexes for table `bank`
--
ALTER TABLE `bank`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bank_list`
--
ALTER TABLE `bank_list`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bnk_id` (`bnk_id`);

--
-- Indexes for table `benefit_types`
--
ALTER TABLE `benefit_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `brand_name`
--
ALTER TABLE `brand_name`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cars`
--
ALTER TABLE `cars`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cars_docu`
--
ALTER TABLE `cars_docu`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`);

--
-- Indexes for table `cars_drv`
--
ALTER TABLE `cars_drv`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`);

--
-- Indexes for table `cars_maint`
--
ALTER TABLE `cars_maint`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cart_order_status`
--
ALTER TABLE `cart_order_status`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cart_wishlist`
--
ALTER TABLE `cart_wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`);

--
-- Indexes for table `car_maker`
--
ALTER TABLE `car_maker`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `car_model`
--
ALTER TABLE `car_model`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `category_type`
--
ALTER TABLE `category_type`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `comp_id` (`comp_id`);

--
-- Indexes for table `contract_period`
--
ALTER TABLE `contract_period`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `countries`
--
ALTER TABLE `countries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customer_access`
--
ALTER TABLE `customer_access`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customer_cart_address`
--
ALTER TABLE `customer_cart_address`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cust_card_update`
--
ALTER TABLE `cust_card_update`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `department`
--
ALTER TABLE `department`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `dept_clr`
--
ALTER TABLE `dept_clr`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `docu_type`
--
ALTER TABLE `docu_type`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `emp_id` (`emp_id`);

--
-- Indexes for table `employee_assets`
--
ALTER TABLE `employee_assets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `emp_id` (`emp_id`),
  ADD KEY `asset_id` (`asset_id`);

--
-- Indexes for table `employee_temp_contants`
--
ALTER TABLE `employee_temp_contants`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `emp_docu`
--
ALTER TABLE `emp_docu`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `emp_eos`
--
ALTER TABLE `emp_eos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`);

--
-- Indexes for table `emp_gosi`
--
ALTER TABLE `emp_gosi`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `emp_incur`
--
ALTER TABLE `emp_incur`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `emp_inv_attachment`
--
ALTER TABLE `emp_inv_attachment`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `emp_loan`
--
ALTER TABLE `emp_loan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `emp_loan_approvals`
--
ALTER TABLE `emp_loan_approvals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loan_id` (`loan_id`);

--
-- Indexes for table `emp_loan_payments`
--
ALTER TABLE `emp_loan_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loan_id` (`loan_id`);

--
-- Indexes for table `emp_notice`
--
ALTER TABLE `emp_notice`
  ADD PRIMARY KEY (`id`),
  ADD KEY `emp_id` (`emp_id`);

--
-- Indexes for table `emp_salary`
--
ALTER TABLE `emp_salary`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `emp_vacation`
--
ALTER TABLE `emp_vacation`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `emp_vacation_balance`
--
ALTER TABLE `emp_vacation_balance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `emp_contract_period` (`emp_id`,`contract_id`,`period_start`),
  ADD KEY `fk_contract` (`contract_id`);

--
-- Indexes for table `eos_calc_`
--
ALTER TABLE `eos_calc_`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `gallery`
--
ALTER TABLE `gallery`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `location_contract`
--
ALTER TABLE `location_contract`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `location_docu`
--
ALTER TABLE `location_docu`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `location_img`
--
ALTER TABLE `location_img`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `machines`
--
ALTER TABLE `machines`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`);

--
-- Indexes for table `machine_inv`
--
ALTER TABLE `machine_inv`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `machine_trans`
--
ALTER TABLE `machine_trans`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `maint_type`
--
ALTER TABLE `maint_type`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `menu_category`
--
ALTER TABLE `menu_category`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `menu_item`
--
ALTER TABLE `menu_item`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `menu_item_img`
--
ALTER TABLE `menu_item_img`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payrolls`
--
ALTER TABLE `payrolls`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_emp_month` (`emp_id`,`month_year`);

--
-- Indexes for table `payroll_benefits`
--
ALTER TABLE `payroll_benefits`
  ADD UNIQUE KEY `id` (`id`);

--
-- Indexes for table `payroll_deductions`
--
ALTER TABLE `payroll_deductions`
  ADD UNIQUE KEY `id` (`id`);

--
-- Indexes for table `portfolio`
--
ALTER TABLE `portfolio`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `saudi_cities`
--
ALTER TABLE `saudi_cities`
  ADD UNIQUE KEY `id` (`id`);

--
-- Indexes for table `section`
--
ALTER TABLE `section`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `smart_request`
--
ALTER TABLE `smart_request`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `smt_attachment`
--
ALTER TABLE `smt_attachment`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `smt_notes`
--
ALTER TABLE `smt_notes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `smt_payment`
--
ALTER TABLE `smt_payment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `inv_no` (`inv_no`);

--
-- Indexes for table `smt_request_status`
--
ALTER TABLE `smt_request_status`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `smt_subject_type`
--
ALTER TABLE `smt_subject_type`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sm_request_sr`
--
ALTER TABLE `sm_request_sr`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `social`
--
ALTER TABLE `social`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `social_list`
--
ALTER TABLE `social_list`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sponsorship`
--
ALTER TABLE `sponsorship`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `survey`
--
ALTER TABLE `survey`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `translations`
--
ALTER TABLE `translations`
  ADD PRIMARY KEY (`translation_id`),
  ADD UNIQUE KEY `lang_key_code` (`lang_key`,`lang_code`);

--
-- Indexes for table `usersimage`
--
ALTER TABLE `usersimage`
  ADD PRIMARY KEY (`uid`),
  ADD UNIQUE KEY `username` (`user`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `vac_sch`
--
ALTER TABLE `vac_sch`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `vouchers`
--
ALTER TABLE `vouchers`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT for table `ac_jobs`
--
ALTER TABLE `ac_jobs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- AUTO_INCREMENT for table `admin_login`
--
ALTER TABLE `admin_login`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `apply_vac_dep`
--
ALTER TABLE `apply_vac_dep`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `assets`
--
ALTER TABLE `assets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bank`
--
ALTER TABLE `bank`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `bank_list`
--
ALTER TABLE `bank_list`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `benefit_types`
--
ALTER TABLE `benefit_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `brand_name`
--
ALTER TABLE `brand_name`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `cars`
--
ALTER TABLE `cars`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `cars_docu`
--
ALTER TABLE `cars_docu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `cars_drv`
--
ALTER TABLE `cars_drv`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `cars_maint`
--
ALTER TABLE `cars_maint`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cart_order_status`
--
ALTER TABLE `cart_order_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cart_wishlist`
--
ALTER TABLE `cart_wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `car_maker`
--
ALTER TABLE `car_maker`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT for table `car_model`
--
ALTER TABLE `car_model`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1476;

--
-- AUTO_INCREMENT for table `category_type`
--
ALTER TABLE `category_type`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `companies`
--
ALTER TABLE `companies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `contract_period`
--
ALTER TABLE `contract_period`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `countries`
--
ALTER TABLE `countries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=248;

--
-- AUTO_INCREMENT for table `customer`
--
ALTER TABLE `customer`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customer_access`
--
ALTER TABLE `customer_access`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customer_cart_address`
--
ALTER TABLE `customer_cart_address`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cust_card_update`
--
ALTER TABLE `cust_card_update`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `department`
--
ALTER TABLE `department`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `dept_clr`
--
ALTER TABLE `dept_clr`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `docu_type`
--
ALTER TABLE `docu_type`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=508;

--
-- AUTO_INCREMENT for table `employee_assets`
--
ALTER TABLE `employee_assets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `employee_temp_contants`
--
ALTER TABLE `employee_temp_contants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `emp_docu`
--
ALTER TABLE `emp_docu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `emp_eos`
--
ALTER TABLE `emp_eos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `emp_gosi`
--
ALTER TABLE `emp_gosi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `emp_incur`
--
ALTER TABLE `emp_incur`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `emp_inv_attachment`
--
ALTER TABLE `emp_inv_attachment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `emp_loan`
--
ALTER TABLE `emp_loan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `emp_loan_approvals`
--
ALTER TABLE `emp_loan_approvals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `emp_loan_payments`
--
ALTER TABLE `emp_loan_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `emp_notice`
--
ALTER TABLE `emp_notice`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `emp_salary`
--
ALTER TABLE `emp_salary`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=495;

--
-- AUTO_INCREMENT for table `emp_vacation`
--
ALTER TABLE `emp_vacation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `emp_vacation_balance`
--
ALTER TABLE `emp_vacation_balance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `eos_calc_`
--
ALTER TABLE `eos_calc_`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=364;

--
-- AUTO_INCREMENT for table `gallery`
--
ALTER TABLE `gallery`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `location_contract`
--
ALTER TABLE `location_contract`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `location_docu`
--
ALTER TABLE `location_docu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `location_img`
--
ALTER TABLE `location_img`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `machines`
--
ALTER TABLE `machines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=381;

--
-- AUTO_INCREMENT for table `machine_inv`
--
ALTER TABLE `machine_inv`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `machine_trans`
--
ALTER TABLE `machine_trans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `maint_type`
--
ALTER TABLE `maint_type`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `menu_category`
--
ALTER TABLE `menu_category`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `menu_item`
--
ALTER TABLE `menu_item`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=177;

--
-- AUTO_INCREMENT for table `menu_item_img`
--
ALTER TABLE `menu_item_img`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=166;

--
-- AUTO_INCREMENT for table `payrolls`
--
ALTER TABLE `payrolls`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `payroll_benefits`
--
ALTER TABLE `payroll_benefits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_deductions`
--
ALTER TABLE `payroll_deductions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `portfolio`
--
ALTER TABLE `portfolio`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `saudi_cities`
--
ALTER TABLE `saudi_cities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `section`
--
ALTER TABLE `section`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `smart_request`
--
ALTER TABLE `smart_request`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `smt_attachment`
--
ALTER TABLE `smt_attachment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `smt_notes`
--
ALTER TABLE `smt_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `smt_payment`
--
ALTER TABLE `smt_payment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `smt_request_status`
--
ALTER TABLE `smt_request_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `smt_subject_type`
--
ALTER TABLE `smt_subject_type`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `sm_request_sr`
--
ALTER TABLE `sm_request_sr`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `social`
--
ALTER TABLE `social`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `social_list`
--
ALTER TABLE `social_list`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `sponsorship`
--
ALTER TABLE `sponsorship`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `survey`
--
ALTER TABLE `survey`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=95;

--
-- AUTO_INCREMENT for table `translations`
--
ALTER TABLE `translations`
  MODIFY `translation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34393;

--
-- AUTO_INCREMENT for table `usersimage`
--
ALTER TABLE `usersimage`
  MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `vac_sch`
--
ALTER TABLE `vac_sch`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vouchers`
--
ALTER TABLE `vouchers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `employee_assets`
--
ALTER TABLE `employee_assets`
  ADD CONSTRAINT `employee_assets_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `emp_loan_approvals`
--
ALTER TABLE `emp_loan_approvals`
  ADD CONSTRAINT `emp_loan_approvals_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `emp_loan` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `emp_loan_payments`
--
ALTER TABLE `emp_loan_payments`
  ADD CONSTRAINT `emp_loan_payments_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `emp_loan` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `emp_vacation_balance`
--
ALTER TABLE `emp_vacation_balance`
  ADD CONSTRAINT `fk_contract` FOREIGN KEY (`contract_id`) REFERENCES `contract_period` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
