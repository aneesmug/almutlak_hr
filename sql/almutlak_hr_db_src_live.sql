-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Feb 17, 2026 at 06:16 PM
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
  `user_id` varchar(100) NOT NULL COMMENT 'Employee ID of user who performed action',
  `user_name` varchar(255) DEFAULT NULL COMMENT 'Name of user for quick reference',
  `action_type` enum('CREATE','UPDATE','DELETE','LOGIN','LOGOUT','VIEW','DOWNLOAD','UPLOAD','APPROVE','REJECT','SUBMIT','EXPORT','IMPORT','OTHER') NOT NULL DEFAULT 'OTHER' COMMENT 'Type of action performed',
  `module` varchar(100) NOT NULL COMMENT 'Module/Section (e.g., Employee, Vacation, Loan, Payroll)',
  `page` varchar(255) NOT NULL COMMENT 'Page/file where action occurred',
  `record_id` varchar(255) DEFAULT NULL COMMENT 'ID of the affected record',
  `table_name` varchar(100) DEFAULT NULL COMMENT 'Database table affected',
  `description` text DEFAULT NULL COMMENT 'Human-readable description of action',
  `old_values` text DEFAULT NULL COMMENT 'JSON of old values (for UPDATE/DELETE)',
  `new_values` text DEFAULT NULL COMMENT 'JSON of new values (for CREATE/UPDATE)',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP address of user',
  `user_agent` text DEFAULT NULL COMMENT 'Browser/device information',
  `severity` enum('INFO','WARNING','CRITICAL','ERROR') DEFAULT 'INFO' COMMENT 'Severity level of action',
  `status` enum('SUCCESS','FAILED','PENDING') DEFAULT 'SUCCESS' COMMENT 'Status of action',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'When action occurred'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Comprehensive activity logging for all system actions';

-- --------------------------------------------------------

--
-- Table structure for table `ac_jobs`
--

CREATE TABLE `ac_jobs` (
  `id` int(11) NOT NULL,
  `job` varchar(50) NOT NULL,
  `job_ar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

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
  `user_type` enum('administrator','gm','hr_senior_bp','hr_operations','hr_supervisor','hr_recruitment','hr_payroll','finance_officer','auditor','gr_officer','dept_user','employee','hr','it','finance','assistant') NOT NULL DEFAULT 'employee' COMMENT 'References roles table',
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `allowed_companies` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'JSON array of company IDs user is allowed to access. NULL = all companies (full access). Example: [1,2,5]' CHECK (json_valid(`allowed_companies`)),
  `allowed_departments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'JSON array of department IDs user is allowed to access. NULL = all departments (full access). Example: [1,2,5]' CHECK (json_valid(`allowed_departments`)),
  `allowed_employees` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'JSON array of emp_id values user is allowed to access. NULL = no employee-based restriction' CHECK (json_valid(`allowed_employees`))
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

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
  `hr_note` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `gm_note` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `date_reg` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `approval_comments`
--

CREATE TABLE `approval_comments` (
  `id` int(11) NOT NULL,
  `request_inv_no` varchar(255) NOT NULL COMMENT 'Invoice number of the request',
  `request_type` enum('vacation_request','loan','smart_request','resignation','rejoin','excuse_leave','general_request') NOT NULL COMMENT 'Type of request',
  `approval_action` enum('approved','rejected','hold','adjusted') DEFAULT 'approved' COMMENT 'Action taken by approver',
  `approver_emp_id` int(11) DEFAULT NULL COMMENT 'Employee ID of the approver',
  `approver_admin_id` int(11) DEFAULT NULL COMMENT 'Admin/User ID of the approver if not employee',
  `approver_name` varchar(255) NOT NULL COMMENT 'Name of the approver (for reference)',
  `approval_level` int(11) DEFAULT 0 COMMENT 'Approval level in the chain',
  `comment_text` longtext DEFAULT NULL COMMENT 'Approver review/comment',
  `comment_date` datetime DEFAULT current_timestamp() COMMENT 'When comment was added',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores approval comments from each approver in the chain';

-- --------------------------------------------------------

--
-- Table structure for table `approval_request_types`
--

CREATE TABLE `approval_request_types` (
  `id` int(11) NOT NULL,
  `type_name` varchar(255) NOT NULL,
  `main_table_name` varchar(50) NOT NULL,
  `description` longtext DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `app_settings`
--

CREATE TABLE `app_settings` (
  `id` int(11) NOT NULL,
  `setting_name` varchar(255) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_group` varchar(100) DEFAULT 'general',
  `description` text DEFAULT NULL,
  `input_type` varchar(50) NOT NULL DEFAULT 'text',
  `options` text DEFAULT NULL COMMENT 'JSON encoded options for select/radio inputs'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assets`
--

CREATE TABLE `assets` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL COMMENT 'e.g., Laptop, Mobile Phone, SIM Card, Car',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `asset_items`
--

CREATE TABLE `asset_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `asset_id` int(10) UNSIGNED NOT NULL,
  `tracking_id` varchar(120) DEFAULT NULL,
  `serial_number` varchar(120) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('Available','Assigned','Lost','Damaged','Retired') NOT NULL DEFAULT 'Available',
  `assigned_emp_id` int(11) DEFAULT NULL,
  `assigned_date` date DEFAULT NULL,
  `return_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

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

-- --------------------------------------------------------

--
-- Table structure for table `benefit_types`
--

CREATE TABLE `benefit_types` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `calculation_type` enum('fixed','overtime_basic','overtime_total') DEFAULT 'fixed',
  `status` tinyint(4) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cart_order_status`
--

CREATE TABLE `cart_order_status` (
  `id` int(11) NOT NULL,
  `order_id` varchar(255) NOT NULL,
  `uid` varchar(100) DEFAULT NULL,
  `emp_name` varchar(255) NOT NULL,
  `notes` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `status` varchar(100) NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cart_wishlist`
--

CREATE TABLE `cart_wishlist` (
  `id` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

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

-- --------------------------------------------------------

--
-- Table structure for table `category_type`
--

CREATE TABLE `category_type` (
  `id` int(11) NOT NULL,
  `type` varchar(15) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `companies`
--

CREATE TABLE `companies` (
  `id` int(11) NOT NULL,
  `comp_id` int(11) NOT NULL,
  `comp_name` varchar(50) NOT NULL,
  `comp_name_ar` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contract_period`
--

CREATE TABLE `contract_period` (
  `id` int(11) NOT NULL,
  `period` varchar(50) NOT NULL,
  `vac_period` decimal(5,2) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `id` int(11) NOT NULL,
  `injazat_no` int(100) NOT NULL,
  `acc_no` varchar(100) NOT NULL,
  `full_name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

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
  `dep_nme_ar` varchar(120) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `mobile` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `designation` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dept_clr`
--

CREATE TABLE `dept_clr` (
  `id` int(11) NOT NULL,
  `dept_name` varchar(100) NOT NULL,
  `color` varchar(100) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `docu_type`
--

CREATE TABLE `docu_type` (
  `id` int(11) NOT NULL,
  `duc_type` varchar(150) NOT NULL,
  `date_reg` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

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
  `supervisor_id` varchar(255) DEFAULT NULL COMMENT 'Employee ID of direct supervisor/manager',
  `country` varchar(150) NOT NULL,
  `vacation_days` varchar(255) NOT NULL,
  `joining_date` varchar(255) NOT NULL,
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
  `address` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `gosi` decimal(10,2) DEFAULT NULL,
  `insurance_no` varchar(100) DEFAULT NULL,
  `insurance_exp` varchar(100) DEFAULT NULL,
  `insurance_class` varchar(50) DEFAULT NULL,
  `payment_type` enum('1','2','3','') NOT NULL DEFAULT '1' COMMENT '1 = Bank\r\n2 = Cash\r\n3 = Hold',
  `probation` varchar(15) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `status` int(11) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `contract_end_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

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
  `return_status` varchar(50) DEFAULT NULL COMMENT 'Status of asset return: NULL/pending, Assets Received, Employee Keep Assets',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `return_attachment` varchar(255) DEFAULT NULL COMMENT 'File path for the proof of return',
  `return_notes` text DEFAULT NULL,
  `signature_file` varchar(255) DEFAULT NULL,
  `proof_file` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `status` enum('Pending','Approved','Rejected','') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `update_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

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
  `overtime_hours` decimal(10,2) DEFAULT 0.00,
  `overtime_days` decimal(10,2) DEFAULT 0.00,
  `absent_days` int(11) DEFAULT 0,
  `deduction_hours` decimal(10,2) DEFAULT 0.00,
  `deduct` varchar(100) NOT NULL,
  `gosi_deduction` decimal(10,2) DEFAULT 0.00,
  `net_payment` varchar(100) NOT NULL,
  `notes` longtext NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `emp_evaluations`
--

CREATE TABLE `emp_evaluations` (
  `id` int(11) NOT NULL,
  `manager_emp_id` varchar(255) NOT NULL COMMENT 'Employee ID of the manager who conducted the evaluation',
  `employee_emp_id` varchar(255) NOT NULL COMMENT 'Employee ID of the person being evaluated',
  `dept_id` int(11) NOT NULL COMMENT 'Department ID at time of evaluation',
  `dept_name` varchar(255) DEFAULT NULL COMMENT 'Department name snapshot',
  `employee_name` varchar(255) DEFAULT NULL COMMENT 'Employee name snapshot',
  `employee_position` varchar(255) DEFAULT NULL COMMENT 'Job position snapshot from ac_jobs',
  `punctuality` tinyint(2) UNSIGNED NOT NULL DEFAULT 10 COMMENT 'الإنتظام وعدم التأخير - Punctuality Attendance',
  `achieving_time` tinyint(2) UNSIGNED NOT NULL DEFAULT 10 COMMENT 'التحقيق في الوقت المحدد - Achieving at the specified time',
  `job_knowledge` tinyint(2) UNSIGNED NOT NULL DEFAULT 10 COMMENT 'معرفة الوظيفة - Knowledge of job',
  `problem_solving` tinyint(2) UNSIGNED NOT NULL DEFAULT 10 COMMENT 'القدرة على حل المشاكل - The Ability to solve problems',
  `feedback_receptiveness` tinyint(2) UNSIGNED NOT NULL DEFAULT 10 COMMENT 'تقبل التوجيهات والتعليمات - Receptiveness to Feedback and Instructions',
  `self_development` tinyint(2) UNSIGNED NOT NULL DEFAULT 10 COMMENT 'السعي لتطوير المهارات والمعرفة وتحسين الأداء بإستمرار - Self & Professional Development',
  `work_under_pressure` tinyint(2) UNSIGNED NOT NULL DEFAULT 10 COMMENT 'العمل تحت الضغط - Work under pressure',
  `communication_teamwork` tinyint(2) UNSIGNED NOT NULL DEFAULT 10 COMMENT 'مهارات التواصل والعمل الجماعي - Communication skills and Teamwork',
  `creativity_response` tinyint(2) UNSIGNED NOT NULL DEFAULT 10 COMMENT 'الإبداع وسرعة الإستجابة - Creativity and speed of response',
  `initiative_cooperation` tinyint(2) UNSIGNED NOT NULL DEFAULT 10 COMMENT 'المبادرة والتعاون - Initiative and cooperation',
  `observation` text DEFAULT NULL COMMENT 'Remarks or observations from the manager',
  `total_score` smallint(3) UNSIGNED NOT NULL DEFAULT 100 COMMENT 'Total evaluation score (sum of all criteria)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `manager_acknowledgment_status` enum('pending','acknowledged','objected') DEFAULT 'pending' COMMENT 'Manager acknowledgment status: pending, acknowledged, or objected',
  `manager_objection_note` longtext DEFAULT NULL COMMENT 'Manager objection note/reason for objection',
  `manager_acknowledgment_date` datetime DEFAULT NULL COMMENT 'Date when manager acknowledged or objected to evaluation',
  `manager_acknowledged_by` int(11) DEFAULT NULL COMMENT 'Employee ID of the manager who acknowledged/objected'
) ;

-- --------------------------------------------------------

--
-- Table structure for table `emp_exit_interviews`
--

CREATE TABLE `emp_exit_interviews` (
  `id` int(11) NOT NULL,
  `resignation_id` int(11) NOT NULL COMMENT 'Reference to emp_resignations.id',
  `emp_id` varchar(255) NOT NULL COMMENT 'Employee ID for quick reference',
  `q1_reasons` text NOT NULL COMMENT 'Q1: Primary reasons for leaving the company',
  `q2_support` text NOT NULL COMMENT 'Q2: Team and management support experience',
  `q3_resources` text NOT NULL COMMENT 'Q3: Resources and tools availability',
  `q4_manager` text NOT NULL COMMENT 'Q4: Relationship with direct manager',
  `q5_growth` text NOT NULL COMMENT 'Q5: Professional growth and development opportunities',
  `q6_compensation` text NOT NULL COMMENT 'Q6: Satisfaction with compensation and benefits',
  `q7_different` text NOT NULL COMMENT 'Q7: What could company have done differently',
  `q8_recommend` text NOT NULL COMMENT 'Q8: Would recommend company to others',
  `q9_additional` text DEFAULT NULL COMMENT 'Q9: Additional comments or feedback',
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Exit interview submission timestamp',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Employee exit interview responses';

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
-- Table structure for table `emp_holidays`
--

CREATE TABLE `emp_holidays` (
  `id` int(11) NOT NULL,
  `holiday_name` varchar(255) NOT NULL COMMENT 'Name of the holiday (e.g., Eid al-Fitr, National Day)',
  `start_date` date NOT NULL COMMENT 'Start date of the holiday period',
  `end_date` date NOT NULL COMMENT 'End date of the holiday period',
  `total_days` int(11) NOT NULL COMMENT 'Total number of days in this holiday period',
  `holiday_type` enum('religious','national','other') DEFAULT 'other' COMMENT 'Type of holiday: religious, national, or other',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = Active (counts in deductions), 0 = Inactive (archived)',
  `remarks` text DEFAULT NULL COMMENT 'Additional remarks about the holiday',
  `created_by` varchar(255) DEFAULT NULL COMMENT 'User who created this holiday record',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Timestamp when record was created',
  `updated_by` varchar(255) DEFAULT NULL COMMENT 'User who last updated this record',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Timestamp of last update'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='Company holidays used to exclude days from vacation deductions';

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

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
  `note` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
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
  `inv_no` varchar(255) DEFAULT NULL COMMENT 'Unique loan request identifier (LOAN-YYYYMMDD-EMPID-HASH)',
  `emp_id` varchar(20) NOT NULL,
  `submitted_by_emp_id` int(11) DEFAULT NULL,
  `loan_type` enum('regular','emergency','end_of_service','housing','advance_salary') NOT NULL DEFAULT 'end_of_service',
  `loan_amount` decimal(10,2) NOT NULL COMMENT 'Principal loan amount requested',
  `approved_amount` decimal(10,2) DEFAULT NULL COMMENT 'Final approved amount (if modified by GM)',
  `approved_by_emp_id` varchar(50) DEFAULT NULL COMMENT 'Employee ID who approved/modified',
  `approved_at` datetime DEFAULT NULL COMMENT 'Timestamp of approval/modification',
  `installments` int(11) NOT NULL DEFAULT 1 COMMENT 'Number of monthly installments',
  `payment_proof_file` varchar(255) DEFAULT NULL COMMENT 'Payment proof uploaded by finance officer',
  `final_approved_amount` decimal(10,2) DEFAULT NULL COMMENT 'Final amount approved and paid by finance officer',
  `reason` text DEFAULT NULL COMMENT 'Reason for loan application',
  `interest_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `total_payable` decimal(10,2) NOT NULL COMMENT 'Total amount to be repaid (same as loan_amount for new loans)',
  `monthly_deduction` decimal(10,2) NOT NULL COMMENT 'Amount deducted per month',
  `deduction_mode` enum('automatic','manual') DEFAULT 'automatic',
  `start_date` date NOT NULL COMMENT 'Date when deductions start (first day of next month)',
  `end_date` date DEFAULT NULL,
  `status` enum('pending','approved','rejected','awaiting','paid') NOT NULL DEFAULT 'awaiting' COMMENT 'Status: pending_level_1 to pending_level_6, approved, rejected, paid',
  `current_approval_level` int(11) DEFAULT 1 COMMENT 'Current approval level in chain',
  `rejected_by` varchar(50) DEFAULT NULL COMMENT 'User ID who rejected',
  `rejection_reason` text DEFAULT NULL COMMENT 'Reason for rejection',
  `rejection_date` datetime DEFAULT NULL COMMENT 'Date of rejection',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `disbursement_receipt_id` varchar(255) DEFAULT NULL,
  `disbursement_attachment` varchar(255) DEFAULT NULL,
  `payer_emp_id` int(11) DEFAULT NULL COMMENT 'Employee ID of the person who will process the payment',
  `payment_date` datetime DEFAULT NULL COMMENT 'Date when payment was processed by Finance Manager'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `emp_loan_monthly_status`
--

CREATE TABLE `emp_loan_monthly_status` (
  `id` int(11) NOT NULL,
  `loan_id` int(11) NOT NULL,
  `month_year` varchar(7) NOT NULL COMMENT 'Format: YYYY-MM',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = Active (deduct), 0 = Skip (don''t deduct)',
  `skip_reason` varchar(255) DEFAULT NULL COMMENT 'Reason for skipping this month',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='Tracks monthly active/skip status for automatic loan deductions';

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
  `notes` text DEFAULT NULL COMMENT 'Payment notes including carry-forward info',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `emp_notice`
--

CREATE TABLE `emp_notice` (
  `id` int(11) NOT NULL,
  `emp_id` int(11) NOT NULL,
  `note` varchar(255) NOT NULL,
  `note_type` varchar(100) DEFAULT NULL COMMENT 'Type of note: warning, sick_leave, appreciation, etc.',
  `attachment` varchar(255) DEFAULT NULL COMMENT 'File path for attached document',
  `status` int(11) NOT NULL DEFAULT 1,
  `is_deleted` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `emp_resignations`
--

CREATE TABLE `emp_resignations` (
  `id` int(11) NOT NULL,
  `request_inv_no` varchar(50) DEFAULT NULL,
  `emp_id` varchar(255) NOT NULL COMMENT 'Employee ID from employees table',
  `last_working_day` date NOT NULL COMMENT 'Employee intended last working day',
  `hr_last_working_day` date DEFAULT NULL,
  `submission_date` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'When resignation was submitted',
  `status` enum('pending','approved','rejected','cancelled','withdrawn') NOT NULL DEFAULT 'pending' COMMENT 'Current status of resignation',
  `rejection_reason` text DEFAULT NULL COMMENT 'Reason if rejected',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `needs_replacement` tinyint(1) DEFAULT 0,
  `replacement_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`replacement_data`))
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Employee resignation records';

-- --------------------------------------------------------

--
-- Table structure for table `emp_resignation_attachments`
--

CREATE TABLE `emp_resignation_attachments` (
  `id` int(11) NOT NULL,
  `resignation_id` int(11) NOT NULL COMMENT 'Reference to emp_resignations.id',
  `file_name` varchar(255) NOT NULL COMMENT 'Original filename',
  `file_path` varchar(500) NOT NULL COMMENT 'Server path to file',
  `file_type` varchar(100) DEFAULT NULL COMMENT 'MIME type',
  `file_size` int(11) DEFAULT NULL COMMENT 'File size in bytes',
  `uploaded_by` varchar(255) NOT NULL COMMENT 'Employee ID who uploaded',
  `document_type` enum('resignation_letter','clearance_form','other') DEFAULT 'other' COMMENT 'Document category',
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Supporting documents for resignations';

-- --------------------------------------------------------

--
-- Table structure for table `emp_resignation_clearance`
--

CREATE TABLE `emp_resignation_clearance` (
  `id` int(11) NOT NULL,
  `resignation_id` int(11) NOT NULL COMMENT 'Reference to emp_resignations.id',
  `dept_name` varchar(100) NOT NULL COMMENT 'Department name (IT, Finance, HR, etc)',
  `cleared_by` varchar(255) DEFAULT NULL COMMENT 'Employee ID who cleared',
  `clearance_status` enum('pending','cleared','issues') DEFAULT 'pending' COMMENT 'Clearance status',
  `clearance_date` datetime DEFAULT NULL COMMENT 'When cleared',
  `notes` text DEFAULT NULL COMMENT 'Clearance notes or issues',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Exit clearance checklist by department';

-- --------------------------------------------------------

--
-- Table structure for table `emp_resignation_history`
--

CREATE TABLE `emp_resignation_history` (
  `id` int(11) NOT NULL,
  `resignation_id` int(11) NOT NULL COMMENT 'Reference to emp_resignations.id',
  `action` enum('submitted','approved','rejected','cancelled','withdrawn','modified','commented') NOT NULL COMMENT 'Action performed',
  `previous_status` enum('pending','approved','rejected','cancelled','withdrawn') DEFAULT NULL COMMENT 'Status before action',
  `new_status` enum('pending','approved','rejected','cancelled','withdrawn') NOT NULL COMMENT 'Status after action',
  `action_by` varchar(255) NOT NULL COMMENT 'Employee ID who performed action',
  `action_date` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'When action occurred',
  `notes` text DEFAULT NULL COMMENT 'Additional notes or comments',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP address of user'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Audit trail for resignation workflow';

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
  `remarks` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `vacation_salary_type` enum('payroll','end_of_service') DEFAULT NULL,
  `attachment_path` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachment_path`)),
  `is_deductible` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = Deductible from annual balance, 0 = Not deductible',
  `review` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `note` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
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
  `accommodation_provided` enum('yes','no') DEFAULT NULL COMMENT 'Is accommodation provided by the company (Business Trip)',
  `transportation_provided` enum('yes','no') DEFAULT NULL COMMENT 'Is transportation provided by the company (Business Trip)',
  `overtime_amount` decimal(10,2) DEFAULT 0.00,
  `deduction_amount` decimal(10,2) DEFAULT 0.00,
  `other_earnings` decimal(10,2) DEFAULT 0.00,
  `other_deductions` decimal(10,2) DEFAULT 0.00,
  `payment_status` enum('pending_payment','paid','needs_modification') DEFAULT 'pending_payment' COMMENT 'Payment status for final HR Payroll approval step',
  `payment_date` datetime DEFAULT NULL COMMENT 'Timestamp when payment was processed',
  `payment_modified_date` datetime DEFAULT NULL COMMENT 'Timestamp when payment was last modified',
  `payment_modified_by` varchar(50) DEFAULT NULL COMMENT 'Employee ID of user who modified payment',
  `is_payment_completed` tinyint(1) DEFAULT 0 COMMENT 'Flag: 0=payment pending,\r\n  ADD COLUMN `1` =payment processing complete'
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

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
  `opening_balance` decimal(10,2) DEFAULT NULL COMMENT 'Cron daily accrual base',
  `carryover_days` decimal(5,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `last_updated` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `emp_vacation_balance_history`
--

CREATE TABLE `emp_vacation_balance_history` (
  `id` int(11) NOT NULL,
  `emp_id` varchar(20) NOT NULL COMMENT 'Employee ID (matches employees.emp_id)',
  `vac_id` int(11) DEFAULT NULL COMMENT 'Reference to emp_vacation_balance.vac_id',
  `contract_id` int(11) DEFAULT NULL COMMENT 'Reference to emp_vacation_balance.contract_id',
  `balance_record_id` int(11) DEFAULT NULL COMMENT 'Reference to emp_vacation_balance.id',
  `old_available_balance` decimal(5,2) DEFAULT NULL COMMENT 'Available balance before update',
  `old_used_days` decimal(5,2) DEFAULT NULL COMMENT 'Used days before update',
  `old_remaining_balance` decimal(5,2) DEFAULT NULL COMMENT 'Remaining balance before update',
  `new_available_balance` decimal(5,2) NOT NULL COMMENT 'Available balance after update',
  `new_used_days` decimal(5,2) NOT NULL COMMENT 'Used days after update',
  `new_remaining_balance` decimal(5,2) NOT NULL COMMENT 'Remaining balance after update',
  `carryover_days` decimal(5,2) DEFAULT NULL COMMENT 'Days carried over from previous period',
  `total_days` decimal(5,2) DEFAULT NULL COMMENT 'Total days allocated for period',
  `period_start` date DEFAULT NULL COMMENT 'Start date of vacation period',
  `period_end` date DEFAULT NULL COMMENT 'End date of vacation period',
  `balance_changed` tinyint(1) DEFAULT 0 COMMENT 'True if available_balance changed',
  `change_amount` decimal(5,2) DEFAULT NULL COMMENT 'Amount of change (new - old)',
  `change_reason` varchar(255) DEFAULT NULL COMMENT 'Reason for change (request ID, manual, refund, etc)',
  `calculation_status` enum('success','warning','error','manual') DEFAULT 'success' COMMENT 'Status of calculation',
  `notes` text DEFAULT NULL COMMENT 'Any calculation notes or warnings',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'When this history record was created',
  `snapshot_date` date NOT NULL COMMENT 'Date snapshot was taken (YYYY-MM-DD)',
  `snapshot_time` datetime DEFAULT NULL COMMENT 'Exact time snapshot was taken'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historical record of vacation balance changes for audit trail and troubleshooting';

-- --------------------------------------------------------

--
-- Table structure for table `eos_calc`
--

CREATE TABLE `eos_calc` (
  `id` int(11) NOT NULL,
  `prid` int(11) NOT NULL,
  `cid` int(11) NOT NULL,
  `details` varchar(255) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `general_requests`
--

CREATE TABLE `general_requests` (
  `id` int(11) NOT NULL,
  `inv_no` varchar(255) NOT NULL COMMENT 'Request invoice number',
  `request_title` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT 'Title/subject of the request',
  `department_to` varchar(100) NOT NULL COMMENT 'Target department (IT, HR, Transportation, etc.)',
  `request_category` varchar(100) NOT NULL COMMENT 'Category of request (Equipment, Service, etc.)',
  `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `description` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT 'Additional details/notes',
  `emp_id` int(11) NOT NULL COMMENT 'Requesting employee ID',
  `emp_name` varchar(255) NOT NULL COMMENT 'Requesting employee name',
  `user_dept` varchar(255) NOT NULL COMMENT 'Requester department',
  `current_status` enum('draft','pending_approval','approved','rejected','completed','waiting_for_delivery') NOT NULL DEFAULT 'draft',
  `current_approval_level` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `completed_at` datetime DEFAULT NULL COMMENT 'When all items were delivered'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `general_request_attachments`
--

CREATE TABLE `general_request_attachments` (
  `id` int(11) NOT NULL,
  `request_inv_no` varchar(255) NOT NULL,
  `attachment` varchar(255) NOT NULL,
  `docu_ext` varchar(50) NOT NULL,
  `attachment_type` enum('request','delivery') NOT NULL DEFAULT 'request',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `general_request_deliveries`
--

CREATE TABLE `general_request_deliveries` (
  `id` int(11) NOT NULL COMMENT 'Unique delivery record ID',
  `request_inv_no` varchar(100) NOT NULL COMMENT 'Reference to general_requests.inv_no',
  `received_by` varchar(50) NOT NULL COMMENT 'Employee ID who received items',
  `delivery_date` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'When items were delivered',
  `attachment_filename` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Record creation time'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tracks item delivery and who received them';

-- --------------------------------------------------------

--
-- Table structure for table `general_request_items`
--

CREATE TABLE `general_request_items` (
  `id` int(11) NOT NULL,
  `request_inv_no` varchar(255) NOT NULL,
  `item_name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `item_type` varchar(100) DEFAULT NULL COMMENT 'Type/category of item',
  `quantity` int(11) NOT NULL DEFAULT 1,
  `specifications` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT 'Additional specifications or notes about the item',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `delivery_status` varchar(20) DEFAULT 'pending' COMMENT 'Status: pending, delivered, canceled',
  `delivery_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `guide_screenshots`
--

CREATE TABLE `guide_screenshots` (
  `id` int(11) NOT NULL,
  `section` varchar(50) NOT NULL COMMENT 'Section: vacations, loans, excuse, resignation, rejoin',
  `step_number` int(11) NOT NULL COMMENT 'Step number in the section',
  `title` varchar(100) NOT NULL COMMENT 'Screenshot title',
  `language` varchar(5) DEFAULT 'en',
  `filename` varchar(255) NOT NULL COMMENT 'Original filename',
  `file_path` varchar(255) NOT NULL COMMENT 'Path to the uploaded file',
  `display_order` int(11) DEFAULT 1 COMMENT 'Order to display screenshots',
  `is_active` tinyint(4) DEFAULT 1 COMMENT 'Whether to show this screenshot',
  `uploaded_by` int(11) DEFAULT NULL COMMENT 'User ID who uploaded',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `location_contract`
--

CREATE TABLE `location_contract` (
  `id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `owner_name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `owner_number` varchar(150) NOT NULL,
  `owner_email` varchar(150) NOT NULL,
  `contract_no` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

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

-- --------------------------------------------------------

--
-- Table structure for table `maint_type`
--

CREATE TABLE `maint_type` (
  `id` int(11) NOT NULL,
  `type` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

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
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

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
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

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
  `note` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `hours` int(11) DEFAULT NULL,
  `days` int(11) DEFAULT NULL,
  `calculation_type` varchar(50) DEFAULT 'fixed',
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
  `note` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `hours` int(11) DEFAULT NULL,
  `days` int(11) DEFAULT NULL,
  `calculation_type` varchar(50) DEFAULT 'fixed',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rejoin_requests`
--

CREATE TABLE `rejoin_requests` (
  `id` int(10) UNSIGNED NOT NULL,
  `request_inv_no` varchar(255) NOT NULL COMMENT 'Unique ID linking to request_approvers',
  `emp_id` varchar(20) NOT NULL,
  `vacation_id` int(10) UNSIGNED NOT NULL,
  `requested_rejoin_date` date NOT NULL COMMENT 'Date employee is requesting to rejoin',
  `requested_reason` text DEFAULT NULL COMMENT 'Reason given by employee for date change',
  `requested_at` datetime NOT NULL DEFAULT current_timestamp(),
  `requested_by_emp_id` varchar(20) DEFAULT NULL COMMENT 'Employee who submitted request (usually emp_id itself)',
  `status` enum('pending','approved','adjusted','rejected') DEFAULT 'pending',
  `approved_at` datetime DEFAULT NULL,
  `approved_by_emp_id` varchar(20) DEFAULT NULL COMMENT 'Supervisor emp_id who approved',
  `approval_note` text DEFAULT NULL COMMENT 'Note from supervisor on approval',
  `rejection_reason` text DEFAULT NULL COMMENT 'If rejected, reason for rejection',
  `adjustment_allowed` tinyint(1) DEFAULT 0 COMMENT 'If true, employee can adjust within date range',
  `adjustment_from_date` date DEFAULT NULL COMMENT 'Earliest date for adjustment',
  `adjustment_to_date` date DEFAULT NULL COMMENT 'Latest date for adjustment',
  `adjustment_reason_text` text DEFAULT NULL COMMENT 'Supervisor reason for allowing adjustment',
  `adjustment_submitted_date` date DEFAULT NULL COMMENT 'Final date employee chose after adjustment',
  `adjustment_submitted_at` datetime DEFAULT NULL COMMENT 'When employee submitted adjusted date',
  `final_approved_date` date DEFAULT NULL COMMENT 'Final approved rejoin date',
  `final_approved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tracks employee rejoin requests and approval workflow';

-- --------------------------------------------------------

--
-- Table structure for table `request_approvers`
--

CREATE TABLE `request_approvers` (
  `id` int(11) NOT NULL,
  `request_inv_no` varchar(255) NOT NULL COMMENT 'Links to inv_no in smart_request or other tables',
  `request_type_id` int(11) NOT NULL,
  `approver_id` varchar(255) NOT NULL,
  `approval_level` int(11) NOT NULL COMMENT '1 for 1st, 2 for 2nd, etc.',
  `status` enum('pending','approved','rejected','awaiting') NOT NULL DEFAULT 'awaiting',
  `note` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `payment_amount` decimal(10,2) DEFAULT NULL COMMENT 'Amount paid by the payer (for payer role)',
  `payment_proof_path` varchar(500) DEFAULT NULL COMMENT 'Path to payment proof document',
  `action_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `saudi_cities`
--

CREATE TABLE `saudi_cities` (
  `id` int(11) NOT NULL,
  `name_en` varchar(255) NOT NULL,
  `name_ar` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `section`
--

CREATE TABLE `section` (
  `id` int(11) NOT NULL,
  `section_name` varchar(255) NOT NULL,
  `location_owner` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `camera_in` int(11) DEFAULT NULL,
  `camera_out` int(11) DEFAULT NULL,
  `b_license_exp` varchar(25) NOT NULL,
  `b_license_no` varchar(50) NOT NULL,
  `location_dist` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `bulding_base` varchar(255) NOT NULL,
  `bulding_size` varchar(255) NOT NULL,
  `t_bulding_size` varchar(50) NOT NULL,
  `latitude` varchar(255) NOT NULL,
  `longitude` varchar(255) NOT NULL,
  `location_name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `municipality` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `sub_municipality` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `status` varchar(10) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settlement_attachments`
--

CREATE TABLE `settlement_attachments` (
  `id` int(10) UNSIGNED NOT NULL COMMENT 'Unique attachment ID',
  `settlement_id` int(11) NOT NULL COMMENT 'Foreign key to settlement_records.id',
  `request_inv_no` varchar(50) NOT NULL COMMENT 'Settlement reference number',
  `emp_id` int(11) NOT NULL COMMENT 'Employee ID for reference',
  `file_name` varchar(255) NOT NULL COMMENT 'Original uploaded file name',
  `file_path` varchar(500) NOT NULL COMMENT 'Path to stored file',
  `file_type` varchar(100) DEFAULT NULL COMMENT 'MIME type of file',
  `file_size` bigint(20) DEFAULT NULL COMMENT 'File size in bytes',
  `attachment_category` enum('wps_file','payment_proof','supporting_document','other') DEFAULT 'supporting_document' COMMENT 'Type of attachment',
  `uploaded_by` int(11) DEFAULT NULL COMMENT 'Employee ID of who uploaded',
  `uploaded_at` timestamp NULL DEFAULT current_timestamp() COMMENT 'Upload timestamp',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Store attachments for settlement records';

-- --------------------------------------------------------

--
-- Table structure for table `settlement_attachments_audit`
--

CREATE TABLE `settlement_attachments_audit` (
  `id` int(10) UNSIGNED NOT NULL,
  `attachment_id` int(10) UNSIGNED DEFAULT NULL,
  `settlement_id` int(11) DEFAULT NULL,
  `emp_id` int(11) DEFAULT NULL,
  `action` enum('uploaded','deleted','replaced','downloaded') DEFAULT 'uploaded',
  `file_name` varchar(255) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `action_timestamp` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Audit trail for settlement attachment operations';

-- --------------------------------------------------------

--
-- Table structure for table `settlement_records`
--

CREATE TABLE `settlement_records` (
  `id` int(11) NOT NULL,
  `request_inv_no` varchar(100) NOT NULL COMMENT 'Reference to the original request (vacation/loan)',
  `request_type` varchar(50) NOT NULL COMMENT 'Type: annual_vacation, loan_request, etc.',
  `emp_id` varchar(20) NOT NULL COMMENT 'Employee ID',
  `settlement_amount` decimal(10,2) NOT NULL COMMENT 'Amount to be settled',
  `settlement_method` varchar(50) DEFAULT 'bank_transfer' COMMENT 'Payment method: bank_transfer, cash, check',
  `settlement_status` enum('draft','pending_approval','approved','rejected','completed','processed','cancelled') DEFAULT 'draft' COMMENT 'Status: pending, approved, processed, rejected, cancelled',
  `payment_date` date DEFAULT NULL COMMENT 'Date settlement was processed',
  `settlement_approver` varchar(20) DEFAULT NULL COMMENT 'Employee ID of who approved settlement',
  `payment_reference` varchar(100) DEFAULT NULL COMMENT 'Bank transfer reference, check number, etc.',
  `notes` text DEFAULT NULL COMMENT 'Settlement notes/comments',
  `created_by` varchar(20) NOT NULL COMMENT 'User who created settlement record',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Settlement/Payment records for completed vacation and loan requests';

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
  `sub_title` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `sub_type` varchar(100) NOT NULL,
  `item_name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
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
  `submitted_by_emp_id` int(11) DEFAULT NULL,
  `approv_by` varchar(100) NOT NULL,
  `remarks` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `current_status` varchar(50) NOT NULL DEFAULT 'draft',
  `current_approval_level` int(11) DEFAULT NULL,
  `payable_by_emp_id` int(11) DEFAULT NULL COMMENT 'Employee ID assigned to process the payment',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

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

-- --------------------------------------------------------

--
-- Table structure for table `smt_notes`
--

CREATE TABLE `smt_notes` (
  `id` int(11) NOT NULL,
  `inv_no` varchar(255) NOT NULL,
  `emp_id` varchar(100) NOT NULL,
  `emp_name` varchar(255) NOT NULL,
  `note` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
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
  `note` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `smt_request_status`
--

CREATE TABLE `smt_request_status` (
  `id` int(11) NOT NULL,
  `inv_no` varchar(255) NOT NULL,
  `emp_id` varchar(100) NOT NULL,
  `emp_name` varchar(255) NOT NULL,
  `note` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `status` varchar(100) NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `smt_subject_type`
--

CREATE TABLE `smt_subject_type` (
  `id` int(11) NOT NULL,
  `sub_type` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sm_request_sr`
--

CREATE TABLE `sm_request_sr` (
  `id` int(10) NOT NULL,
  `sr` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sponsorship`
--

CREATE TABLE `sponsorship` (
  `id` int(11) NOT NULL,
  `sponsor` varchar(30) NOT NULL,
  `sponsor_ar` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

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
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

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

-- --------------------------------------------------------

--
-- Table structure for table `translation_cache`
--

CREATE TABLE `translation_cache` (
  `id` int(11) NOT NULL,
  `text_hash` varchar(32) NOT NULL,
  `source_text` varchar(500) DEFAULT NULL,
  `source_lang` varchar(10) NOT NULL DEFAULT 'en',
  `target_lang` varchar(10) NOT NULL DEFAULT 'ar',
  `translated_text` longtext NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

-- --------------------------------------------------------

--
-- Table structure for table `user_activity_log`
--

CREATE TABLE `user_activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `emp_id` varchar(50) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `login_time` datetime NOT NULL DEFAULT current_timestamp(),
  `logout_time` datetime DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `country_code` varchar(10) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `timezone` varchar(50) DEFAULT NULL,
  `isp` varchar(255) DEFAULT NULL,
  `browser` varchar(100) DEFAULT NULL,
  `browser_version` varchar(50) DEFAULT NULL,
  `os` varchar(100) DEFAULT NULL,
  `os_version` varchar(50) DEFAULT NULL,
  `device_type` varchar(50) DEFAULT NULL COMMENT 'Desktop, Mobile, Tablet',
  `screen_width` int(11) DEFAULT NULL,
  `screen_height` int(11) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `status` enum('active','logged_out','timeout') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `location_accuracy` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_notifications`
--

CREATE TABLE `user_notifications` (
  `id` int(11) NOT NULL,
  `emp_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `url` varchar(512) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action_type` (`action_type`),
  ADD KEY `idx_module` (`module`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_record_id` (`record_id`),
  ADD KEY `idx_table_name` (`table_name`);

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
  ADD KEY `otp_index` (`otp`,`otp_expiration`),
  ADD KEY `idx_user_type` (`user_type`);

--
-- Indexes for table `apply_vac_dep`
--
ALTER TABLE `apply_vac_dep`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `approval_comments`
--
ALTER TABLE `approval_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_request` (`request_inv_no`,`request_type`),
  ADD KEY `idx_approver` (`approver_emp_id`,`approver_admin_id`),
  ADD KEY `idx_action` (`approval_action`),
  ADD KEY `idx_date` (`comment_date`);

--
-- Indexes for table `approval_request_types`
--
ALTER TABLE `approval_request_types`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_is_default` (`is_default`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_type_name` (`type_name`);

--
-- Indexes for table `app_settings`
--
ALTER TABLE `app_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_name` (`setting_name`);

--
-- Indexes for table `assets`
--
ALTER TABLE `assets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `asset_items`
--
ALTER TABLE `asset_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `serial_number` (`serial_number`),
  ADD UNIQUE KEY `tracking_id` (`tracking_id`),
  ADD KEY `idx_asset_id` (`asset_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_assigned_emp` (`assigned_emp_id`),
  ADD KEY `idx_tracking` (`tracking_id`);

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
  ADD PRIMARY KEY (`emp_id`),
  ADD UNIQUE KEY `emp_id_2` (`emp_id`),
  ADD UNIQUE KEY `idx_id` (`id`),
  ADD KEY `emp_id` (`emp_id`),
  ADD KEY `idx_supervisor` (`supervisor_id`);

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
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `emp_evaluations`
--
ALTER TABLE `emp_evaluations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_employee` (`employee_emp_id`),
  ADD KEY `idx_manager` (`manager_emp_id`),
  ADD KEY `idx_dept` (`dept_id`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_manager_acknowledgment_status` (`manager_acknowledgment_status`),
  ADD KEY `idx_manager_acknowledged_by` (`manager_acknowledged_by`);

--
-- Indexes for table `emp_exit_interviews`
--
ALTER TABLE `emp_exit_interviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_resignation_id` (`resignation_id`),
  ADD KEY `idx_emp_id` (`emp_id`(250)),
  ADD KEY `idx_submitted_at` (`submitted_at`);

--
-- Indexes for table `emp_gosi`
--
ALTER TABLE `emp_gosi`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `emp_holidays`
--
ALTER TABLE `emp_holidays`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_holiday_dates` (`start_date`,`end_date`,`is_active`),
  ADD KEY `idx_holiday_active` (`is_active`,`start_date`);

--
-- Indexes for table `emp_inv_attachment`
--
ALTER TABLE `emp_inv_attachment`
  ADD PRIMARY KEY (`id`);

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
  ADD KEY `idx_payment_proof` (`payment_proof_file`),
  ADD KEY `idx_deduction_mode` (`deduction_mode`);

--
-- Indexes for table `emp_loan_approvals`
--
ALTER TABLE `emp_loan_approvals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loan_id` (`loan_id`);

--
-- Indexes for table `emp_loan_monthly_status`
--
ALTER TABLE `emp_loan_monthly_status`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_loan_month` (`loan_id`,`month_year`),
  ADD KEY `idx_loan_id` (`loan_id`),
  ADD KEY `idx_month_year` (`month_year`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_loan_month_status` (`loan_id`,`month_year`,`status`);

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
  ADD KEY `emp_id` (`emp_id`),
  ADD KEY `idx_note_type` (`note_type`);

--
-- Indexes for table `emp_resignations`
--
ALTER TABLE `emp_resignations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `request_inv_no` (`request_inv_no`),
  ADD KEY `idx_emp_id` (`emp_id`(250)),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_last_working_day` (`last_working_day`),
  ADD KEY `idx_submission_date` (`submission_date`),
  ADD KEY `idx_hr_last_working_day` (`hr_last_working_day`);

--
-- Indexes for table `emp_resignation_attachments`
--
ALTER TABLE `emp_resignation_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_resignation_id` (`resignation_id`),
  ADD KEY `idx_uploaded_by` (`uploaded_by`(250));

--
-- Indexes for table `emp_resignation_clearance`
--
ALTER TABLE `emp_resignation_clearance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_resignation_id` (`resignation_id`),
  ADD KEY `idx_clearance_status` (`clearance_status`);

--
-- Indexes for table `emp_resignation_history`
--
ALTER TABLE `emp_resignation_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_resignation_id` (`resignation_id`),
  ADD KEY `idx_action_by` (`action_by`(250)),
  ADD KEY `idx_action_date` (`action_date`);

--
-- Indexes for table `emp_salary`
--
ALTER TABLE `emp_salary`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `emp_vacation`
--
ALTER TABLE `emp_vacation`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_emp_vacation_request_inv_no` (`request_inv_no`),
  ADD KEY `idx_travel_email_sent` (`travel_email_sent`);

--
-- Indexes for table `emp_vacation_balance`
--
ALTER TABLE `emp_vacation_balance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `emp_contract_period` (`emp_id`,`contract_id`,`period_start`),
  ADD KEY `fk_contract` (`contract_id`),
  ADD KEY `idx_last_updated` (`last_updated`);

--
-- Indexes for table `emp_vacation_balance_history`
--
ALTER TABLE `emp_vacation_balance_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_emp_id` (`emp_id`),
  ADD KEY `idx_snapshot_date` (`snapshot_date`),
  ADD KEY `idx_emp_date` (`emp_id`,`snapshot_date`),
  ADD KEY `idx_balance_changed` (`balance_changed`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_balance_record` (`balance_record_id`);

--
-- Indexes for table `eos_calc`
--
ALTER TABLE `eos_calc`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `gallery`
--
ALTER TABLE `gallery`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `general_requests`
--
ALTER TABLE `general_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `inv_no` (`inv_no`),
  ADD KEY `emp_id` (`emp_id`),
  ADD KEY `current_status` (`current_status`),
  ADD KEY `idx_completed_at` (`completed_at`);

--
-- Indexes for table `general_request_attachments`
--
ALTER TABLE `general_request_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_inv_no` (`request_inv_no`);

--
-- Indexes for table `general_request_deliveries`
--
ALTER TABLE `general_request_deliveries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_request_inv_no` (`request_inv_no`),
  ADD KEY `idx_received_by` (`received_by`);

--
-- Indexes for table `general_request_items`
--
ALTER TABLE `general_request_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_inv_no` (`request_inv_no`),
  ADD KEY `idx_delivery_status` (`delivery_status`),
  ADD KEY `idx_delivery_id` (`delivery_id`);

--
-- Indexes for table `guide_screenshots`
--
ALTER TABLE `guide_screenshots`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_section` (`section`),
  ADD KEY `idx_step` (`step_number`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_language` (`language`),
  ADD KEY `idx_section_step_lang` (`section`,`step_number`,`language`);

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
-- Indexes for table `rejoin_requests`
--
ALTER TABLE `rejoin_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `request_inv_no` (`request_inv_no`),
  ADD KEY `idx_emp_id` (`emp_id`),
  ADD KEY `idx_vacation_id` (`vacation_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `request_approvers`
--
ALTER TABLE `request_approvers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_inv_no` (`request_inv_no`),
  ADD KEY `request_type_id` (`request_type_id`);

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
-- Indexes for table `settlement_attachments`
--
ALTER TABLE `settlement_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_settlement_id` (`settlement_id`),
  ADD KEY `idx_request_inv_no` (`request_inv_no`),
  ADD KEY `idx_emp_id` (`emp_id`),
  ADD KEY `idx_attachment_category` (`attachment_category`),
  ADD KEY `idx_uploaded_at` (`uploaded_at`);

--
-- Indexes for table `settlement_attachments_audit`
--
ALTER TABLE `settlement_attachments_audit`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_settlement_id` (`settlement_id`),
  ADD KEY `idx_attachment_id` (`attachment_id`),
  ADD KEY `idx_action_timestamp` (`action_timestamp`);

--
-- Indexes for table `settlement_records`
--
ALTER TABLE `settlement_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_settlement` (`request_inv_no`,`request_type`),
  ADD KEY `idx_emp_id` (`emp_id`),
  ADD KEY `idx_status` (`settlement_status`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_settlement_created` (`created_at`),
  ADD KEY `idx_settlement_emp_type` (`emp_id`,`request_type`);

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
-- Indexes for table `translation_cache`
--
ALTER TABLE `translation_cache`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_translation` (`text_hash`,`source_lang`,`target_lang`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `usersimage`
--
ALTER TABLE `usersimage`
  ADD PRIMARY KEY (`uid`),
  ADD UNIQUE KEY `username` (`user`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_emp_id` (`emp_id`),
  ADD KEY `idx_login_time` (`login_time`),
  ADD KEY `idx_ip_address` (`ip_address`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `user_notifications`
--
ALTER TABLE `user_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_emp_id_is_read` (`emp_id`,`is_read`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ac_jobs`
--
ALTER TABLE `ac_jobs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin_login`
--
ALTER TABLE `admin_login`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `apply_vac_dep`
--
ALTER TABLE `apply_vac_dep`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `approval_comments`
--
ALTER TABLE `approval_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `approval_request_types`
--
ALTER TABLE `approval_request_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `app_settings`
--
ALTER TABLE `app_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assets`
--
ALTER TABLE `assets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `asset_items`
--
ALTER TABLE `asset_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bank`
--
ALTER TABLE `bank`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bank_list`
--
ALTER TABLE `bank_list`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `benefit_types`
--
ALTER TABLE `benefit_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `brand_name`
--
ALTER TABLE `brand_name`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cars`
--
ALTER TABLE `cars`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cars_docu`
--
ALTER TABLE `cars_docu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cars_drv`
--
ALTER TABLE `cars_drv`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cars_maint`
--
ALTER TABLE `cars_maint`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cart_order_status`
--
ALTER TABLE `cart_order_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cart_wishlist`
--
ALTER TABLE `cart_wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `car_maker`
--
ALTER TABLE `car_maker`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `car_model`
--
ALTER TABLE `car_model`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `category_type`
--
ALTER TABLE `category_type`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `companies`
--
ALTER TABLE `companies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contract_period`
--
ALTER TABLE `contract_period`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `countries`
--
ALTER TABLE `countries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dept_clr`
--
ALTER TABLE `dept_clr`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `docu_type`
--
ALTER TABLE `docu_type`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_assets`
--
ALTER TABLE `employee_assets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_temp_contants`
--
ALTER TABLE `employee_temp_contants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `emp_docu`
--
ALTER TABLE `emp_docu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `emp_eos`
--
ALTER TABLE `emp_eos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `emp_evaluations`
--
ALTER TABLE `emp_evaluations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `emp_exit_interviews`
--
ALTER TABLE `emp_exit_interviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `emp_gosi`
--
ALTER TABLE `emp_gosi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `emp_holidays`
--
ALTER TABLE `emp_holidays`
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `emp_loan_approvals`
--
ALTER TABLE `emp_loan_approvals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `emp_loan_monthly_status`
--
ALTER TABLE `emp_loan_monthly_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `emp_loan_payments`
--
ALTER TABLE `emp_loan_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `emp_notice`
--
ALTER TABLE `emp_notice`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `emp_resignations`
--
ALTER TABLE `emp_resignations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `emp_resignation_attachments`
--
ALTER TABLE `emp_resignation_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `emp_resignation_clearance`
--
ALTER TABLE `emp_resignation_clearance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `emp_resignation_history`
--
ALTER TABLE `emp_resignation_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `emp_salary`
--
ALTER TABLE `emp_salary`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `emp_vacation`
--
ALTER TABLE `emp_vacation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `emp_vacation_balance`
--
ALTER TABLE `emp_vacation_balance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `emp_vacation_balance_history`
--
ALTER TABLE `emp_vacation_balance_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eos_calc`
--
ALTER TABLE `eos_calc`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gallery`
--
ALTER TABLE `gallery`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `general_requests`
--
ALTER TABLE `general_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `general_request_attachments`
--
ALTER TABLE `general_request_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `general_request_deliveries`
--
ALTER TABLE `general_request_deliveries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique delivery record ID';

--
-- AUTO_INCREMENT for table `general_request_items`
--
ALTER TABLE `general_request_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `guide_screenshots`
--
ALTER TABLE `guide_screenshots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `machines`
--
ALTER TABLE `machines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `machine_inv`
--
ALTER TABLE `machine_inv`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `machine_trans`
--
ALTER TABLE `machine_trans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `maint_type`
--
ALTER TABLE `maint_type`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `menu_category`
--
ALTER TABLE `menu_category`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `menu_item`
--
ALTER TABLE `menu_item`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `menu_item_img`
--
ALTER TABLE `menu_item_img`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payrolls`
--
ALTER TABLE `payrolls`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_benefits`
--
ALTER TABLE `payroll_benefits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_deductions`
--
ALTER TABLE `payroll_deductions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `portfolio`
--
ALTER TABLE `portfolio`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rejoin_requests`
--
ALTER TABLE `rejoin_requests`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `request_approvers`
--
ALTER TABLE `request_approvers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `saudi_cities`
--
ALTER TABLE `saudi_cities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `section`
--
ALTER TABLE `section`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settlement_attachments`
--
ALTER TABLE `settlement_attachments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Unique attachment ID';

--
-- AUTO_INCREMENT for table `settlement_attachments_audit`
--
ALTER TABLE `settlement_attachments_audit`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settlement_records`
--
ALTER TABLE `settlement_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `smart_request`
--
ALTER TABLE `smart_request`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `smt_attachment`
--
ALTER TABLE `smt_attachment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `smt_notes`
--
ALTER TABLE `smt_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `smt_request_status`
--
ALTER TABLE `smt_request_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `smt_subject_type`
--
ALTER TABLE `smt_subject_type`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sm_request_sr`
--
ALTER TABLE `sm_request_sr`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `social`
--
ALTER TABLE `social`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `social_list`
--
ALTER TABLE `social_list`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sponsorship`
--
ALTER TABLE `sponsorship`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `survey`
--
ALTER TABLE `survey`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `translations`
--
ALTER TABLE `translations`
  MODIFY `translation_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `translation_cache`
--
ALTER TABLE `translation_cache`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `usersimage`
--
ALTER TABLE `usersimage`
  MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_notifications`
--
ALTER TABLE `user_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vac_sch`
--
ALTER TABLE `vac_sch`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vouchers`
--
ALTER TABLE `vouchers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
-- Constraints for table `emp_loan_monthly_status`
--
ALTER TABLE `emp_loan_monthly_status`
  ADD CONSTRAINT `fk_loan_monthly_status` FOREIGN KEY (`loan_id`) REFERENCES `emp_loan` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `emp_loan_payments`
--
ALTER TABLE `emp_loan_payments`
  ADD CONSTRAINT `emp_loan_payments_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `emp_loan` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `request_approvers`
--
ALTER TABLE `request_approvers`
  ADD CONSTRAINT `fk_request_type` FOREIGN KEY (`request_type_id`) REFERENCES `approval_request_types` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `settlement_attachments`
--
ALTER TABLE `settlement_attachments`
  ADD CONSTRAINT `fk_settlement_attachments_settlement` FOREIGN KEY (`settlement_id`) REFERENCES `settlement_records` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
