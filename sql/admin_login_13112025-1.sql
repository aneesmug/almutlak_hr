-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 13, 2025 at 11:27 AM
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
-- Table structure for table `admin_login`
--

CREATE TABLE `admin_login` (
  `id` int(255) NOT NULL,
  `emp_id` varchar(11) NOT NULL,
  `id_iqama` varchar(15) NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `user_type` enum('administrator','gm','hr_senior_bp','hr_operations','hr_supervisor','hr_recruitment','hr_payroll','finance_officer','auditor','gr_officer','dept_user','employee','hr','it','finance','assistant') DEFAULT 'employee' COMMENT 'User role type - determines access permissions based on department and role',
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
(1, '5430', '2337318717', 'Anees Mughal', 'root', 'administrator', 'Manager', 1, '6', 'a.afzal@almutlak.com', 'Hain6539306', '0599723451', '', '', NULL, NULL, NULL, 'assets/emp_pics/2337318717.29512160_2101570866527042_5368874645299751559_n.jpg', 1, 'en', '7cf141c11c2f0c3f6e9dd5736514e6abb08bacefef2ef5959a3eb4e76ff9f857', '2025-12-12 13:24:01', 'true', '2025-11-12 07:24:01', '2025-11-12 07:24:01', '0000-00-00 00:00:00'),
(2, '5408', '1070652175', 'Sharifah Ahmed ALsalhi', 'sharifah', 'employee', '', 0, '5', 'a.afzal@almutlak.com', 'MocH#770', '0552514413', '', '', NULL, NULL, NULL, './assets/emp_pics/defultFemale.jpg', 1, 'en', '9eaed57154cc5a7a7c669005021472521a58aebaed889da8993a2ae6b8736a1f', '2025-11-27 13:50:57', NULL, '2025-10-28 07:50:57', '2025-11-13 10:23:45', '2025-06-15 15:38:00'),
(15, '5455', '1094918719', 'HAIFAA SAEED ALMALKI', '', 'hr_senior_bp', '', 0, '5', 'a.afzal@almutlak.com', '', '', '', '', NULL, NULL, NULL, '', 1, 'en', 'de17a90c8bf3349efff0e6559131d6e9f425fbefdd1ebe421dfa44edf6435d11', '2025-11-27 11:36:08', NULL, '2025-10-28 05:36:08', '2025-11-13 10:24:00', '2025-10-22 06:42:52'),
(4, '3928', '2006634469', 'MAHER THABET AL JABARI', 'mahar', 'gm', 'Manager', 0, '10', 'a.afzal@almutlak.com', '', '0505618108', '', '', NULL, NULL, NULL, './assets/emp_pics/defult.png', 1, 'en', NULL, NULL, NULL, '2025-06-11 10:34:44', '2025-11-13 10:23:48', '2025-06-11 05:11:15'),
(3, '4120', '2103034787', 'GAMAL ABDELRAHMAN ABDELRAHMAN', 'gamal', 'finance', 'Manager', 0, '2', 'a.afzal@almutlak.com', '', '0500575208', '', '', NULL, NULL, NULL, './assets/emp_pics/defult.png', 1, 'en', NULL, NULL, NULL, '2025-06-11 10:18:49', '2025-11-13 10:23:47', '2025-06-11 04:59:00'),
(8, '5111', '1085010682', 'ABDULRAHMAN MOHAMMED ALSALHI', 'arehman', 'assistant', '', 0, '5', 'a.afzal@almutlak.com', '123', '0569278564', '', '', NULL, NULL, NULL, './assets/emp_pics/defult.png', 1, 'ar', 'f180f8dd1de13abbd9aafee4ef53a6797b5c0a4d4ee91981154f16daece458f2', '2025-11-14 11:42:46', NULL, '2025-10-15 05:42:46', '2025-11-13 10:23:52', '2025-06-18 05:18:23'),
(6, '3431', '2293543845', 'LEANDRO BUNAG SANTIAGO', 'andro', 'hr_payroll', '', 0, '5', 'a.afzal@almutlak.com', '123', '0562017534', '', '', NULL, NULL, NULL, './assets/emp_pics/defult.png', 1, 'en', '0a020b4a95c86f8c0641afc4d56cbdf63ff9baaeccf2fd5e65b29bc305a45aba', '2025-12-13 11:29:11', NULL, '2025-11-13 05:29:11', '2025-11-13 10:23:49', '2025-06-12 07:43:39'),
(7, '5127', '2506165311', 'MAKARAN JAVAID', 'makaram', 'it', '', 0, '6', 'a.afzal@almutlak.com', '123', '0543837151', '', '', NULL, NULL, NULL, './assets/emp_pics/defult.png', 1, 'en', NULL, NULL, NULL, '2025-10-28 09:18:11', '2025-11-13 10:23:50', '2025-09-07 12:26:52'),
(9, '5115', '1000920619', 'ROUA AHMED SENDI', 'roua', 'hr_recruitment', '', 0, '5', 'a.afzal@almutlak.com', '123', '0562326246', '', '', NULL, NULL, NULL, './assets/emp_pics/defultFemale.jpg', 1, 'ar', '366de8d9e19ea333739bcfeaa38af580a897292342c8e50b8201d93347de050f', '2025-11-08 09:53:07', NULL, '2025-10-09 03:53:07', '2025-11-13 10:23:53', '2025-06-18 05:19:39'),
(10, '5423', '1057101337', 'ABRAR MOHAMMED ALSAHBI', 'abrar', 'hr_operations', '', 0, '5', 'a.afzal@almutlak.com', '123', '0558922759', '', '', NULL, NULL, NULL, './assets/emp_pics/defultFemale.jpg', 1, 'en', '353a639dc977b941ae44455db2393d0cd20affb1f15c03bf0554d0d1e6f48792', '2025-12-11 12:53:30', NULL, '2025-11-11 06:53:30', '2025-11-13 10:23:54', '2025-06-18 05:20:16'),
(11, '5422', '1122098906', 'ABDULRAHMAN SAMEER MALKI', 'sameermalki', 'assistant', '', 0, '5', 'a.almalki@almutlak.com', '123', '0565331473', '', '', NULL, NULL, NULL, './assets/emp_pics/defult.png', 1, 'en', 'b7d4547ee95e6c4a2ed25e001129f612b38f06111b4e6ea8e1c3f98e900f0e5c', '2025-11-27 13:06:46', NULL, '2025-10-28 07:06:46', '2025-10-28 07:06:46', '2025-06-18 05:21:37'),
(12, '5426', '1082615624', 'MONA IBRAHIM ALSAHER', 'mona', 'assistant', '', 0, '5', 'a.afzal@almutlak.com', '123', '0565121724', '', '', NULL, NULL, NULL, './assets/emp_pics/defultFemale.jpg', 1, 'ar', NULL, NULL, NULL, '2025-11-12 08:32:11', '2025-11-13 10:23:57', '2025-06-18 05:22:29'),
(44, '5378', '1099849703', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$q8vWNr0NB2x2fl6D/GdztuI8VuHxijKL8/MHCJsI.md1wthBEzLs6', '', NULL, NULL, NULL, '', 1, 'ar', NULL, NULL, NULL, NULL, '2025-10-28 08:22:19', '2025-10-28 08:20:40'),
(13, '3061', '2275998009', 'AHMED ABDELHAY A SOLIMAN', '', 'finance_officer', '', 0, '2', 'a.afzal@almutlak.com', '', '', '', '', '$2y$10$gVnwJ3gVe.UXc4yvljrBNuvLIjI6FucP5kEYvVDlcsiASEybsEX16', '2025-09-02 16:21:42', NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-11-13 10:23:58', '2025-09-02 10:17:56'),
(16, '5456', '1096266471', 'LINA ABDULRAHMAN ALMUTLAQ', '', 'it', 'Manager', 0, '6', 'it@almutlak.com', '', '', '', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, '2025-11-13 10:25:43', '2025-11-13 10:25:43', '2025-10-22 06:43:13'),
(17, '5454', '1006057515', 'MOHAMMED ABDO ALI HANTOOL', '', 'hr', 'Manager', 0, '10', 'a.afzal@almutlak.com', '', '', '', '', NULL, NULL, NULL, '', 1, 'en', '81f28f52619b5005b76661d3c33907e6ce3b1e7b374e4aa2b011dd40030ab76e', '2025-12-13 12:01:50', NULL, '2025-11-13 06:01:50', '2025-11-13 10:24:02', '2025-10-26 06:40:16'),
(19, '2975', '2264312873', 'N/A', '', 'dept_user', '', 0, '11', '', '', '', '', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-11-13 04:45:57', '2025-10-28 07:05:27'),
(20, '5414', '2596230710', 'N/A', '', 'dept_user', '', 0, '15', 'a.afzal@almutlak.com', '', '', '', '', NULL, NULL, NULL, '', 1, 'ar', 'c1245bb533e1b8b8bce4e6880b4cf8eea9d8c7f64b75d6c8be006675b1bfc484', '2025-11-28 08:21:07', NULL, '2025-10-29 02:21:07', '2025-11-13 10:24:04', '2025-10-28 23:49:19'),
(21, '5296', '2557840267', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$6GjJM9Orbd4vQLS6M9uKTuyl9kvGKOFbSvnFD.UgY7XCIdZUGX13W', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-10-28 07:06:08', '2025-10-28 07:06:08'),
(22, '4473', '2415102199', 'N/A', '', 'dept_user', '', 0, '11', '', '', '', '', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-11-13 04:46:15', '2025-10-28 07:07:41'),
(23, '3015', '2270479500', 'N/A', '', 'employee', '', 0, '15', '', '', '', '$2y$10$t.a9TvMNGm9CuLHk12uMlug2sSNDgDM3d7DK5YNBSow5Y32TPP66i', '', NULL, NULL, NULL, '', 1, 'ar', NULL, NULL, NULL, NULL, '2025-10-28 07:14:45', '2025-10-28 07:10:44'),
(24, '3294', '2307683322', 'N/A', '', 'dept_user', '', 0, '11', '', '', '', '', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-11-13 04:46:15', '2025-10-28 07:12:26'),
(25, '5294', '2518195926', 'N/A', '', 'employee', '', 0, '15', '', '', '', '$2y$10$Pn/HzFoNgcqHU927lqYhWOAszJsFHUYfOdRg5Nh5KXl4P9ZcHyoBS', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-10-28 07:21:36', '2025-10-28 07:13:55'),
(26, '5122', '1126378254', 'N/A', '', 'employee', '', 0, '1', '', '', '', '$2y$10$JdzDCAPqPJ.xeQU82ShxcO9nnhkyYLriuKDp9SlRsh9kE6gGRemNC', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-11-09 10:40:20', '2025-10-28 07:17:51'),
(27, '5448', '2200634356', 'N/A', '', 'employee', '', 0, '10', '', '', '', '$2y$10$.pBOvPx60NySBGBSDG0DjOES7MrbaJyUNhyzYWSUkLercubNu6RUy', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-10-28 07:21:02', '2025-10-28 07:21:02'),
(28, '5165', '2535402917', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$1rXfh9n958awEPlf7WBam.dDosLTaBfa1PjfaTfwpVc7h2vR1454a', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-10-28 07:21:06', '2025-10-28 07:21:06'),
(29, '5071', '2254297704', 'N/A', '', 'dept_user', '', 0, '11', '', '', '', '', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-11-13 04:46:15', '2025-10-28 07:21:33'),
(30, '5337', '1096073109', 'N/A', '', 'dept_user', '', 0, '15', 'k.alghamdi@almutlak.com', '', '', '', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, '2025-11-04 06:17:20', '2025-11-13 04:46:15', '2025-11-04 02:44:54'),
(31, '3497', '2334586811', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$L48MV2gH7IWSuN8Vfzxsa.vgoi.2H50jzWBeLoNQEvsFqwjDAx31C', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-10-28 07:26:33', '2025-10-28 07:26:33'),
(32, '5215', '1118483609', 'N/A', '', 'employee', '', 0, '16', '', '', '', '$2y$10$vlf79UHYWBFyUWxQZadlE.uObopVH3xri1yOVk5ND8naI6Rt6Ho7C', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-10-28 08:26:02', '2025-10-28 07:28:23'),
(33, '4134', '2069753438', 'N/A', '', 'dept_user', '', 0, '15', '', '', '', '', '', NULL, NULL, NULL, '', 1, 'ar', NULL, NULL, NULL, NULL, '2025-11-13 04:46:15', '2025-10-28 07:33:51'),
(34, '1837', '2125999124', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$5XudWCjlalTkZuXHvF1LW.6tqOh3Z6FpICeHk4lo2H0Dx/fdYfnRq', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-11-05 10:22:32', '2025-10-28 07:36:51'),
(35, '4220', '2385993783', 'N/A', '', 'employee', '', 0, '1', '', '', '', '$2y$10$4t/hiQWlHliSzSVVFl1Fl.0aeDPWpgjVBlF.xWrk5KYe7yajXxXka', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-10-28 07:51:10', '2025-10-28 07:37:26'),
(36, '3627', '2342981053', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$Mos/OvaIhmsabv..Pdw4UeheEqucH3C26J1UGUqvklSXF8Upskhbi', '', NULL, NULL, NULL, '', 1, 'ar', NULL, NULL, NULL, NULL, '2025-11-10 10:40:26', '2025-10-28 07:37:27'),
(37, '5253', '1061261697', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$Kfs.1MDQAvR3BGz3WVhPMuRvAVoZI0to7HjvksPIupT/qYJr/jNVS', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-10-28 07:39:54', '2025-10-28 07:39:54'),
(38, '5109', '2526994088', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$Mwa5lZnrVbEUI.wlOyWY/OYvQdJzxHRAAN0TSHADyGI5IHdHhK0a2', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-10-28 07:42:44', '2025-10-28 07:42:44'),
(39, '5214', '1105637928', 'N/A', '', 'employee', '', 0, '16', '', '', '', '$2y$10$Or1CrU9HBl58UspRg7DSsuCmt65eG90xtprsXP6Pa7EZUXy4BBUza', '', NULL, NULL, NULL, '', 1, 'ar', NULL, NULL, NULL, NULL, '2025-10-28 07:43:14', '2025-10-28 07:43:05'),
(40, '3205', '2292808579', 'N/A', '', 'employee', '', 0, '15', '', '', '', '$2y$10$UR2w/wbMjfXE4SzsbcYYruy8MOpFXi/E1cu8RxPx4.z2eIlApKuTq', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-10-28 07:47:11', '2025-10-28 07:47:11'),
(41, '5011', '2286414368', 'N/A', '', 'dept_user', '', 0, '11', '', '', '', '', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-11-13 04:46:15', '2025-10-28 07:48:31'),
(42, '5433', '1064586629', 'N/A', '', 'employee', '', 0, '1', '', '', '', '$2y$10$TRSEjIP.UpCVZeBUx37MxeGAo4qEPzkxgLYtEl8OVNNuCwLI7AUTe', '', NULL, NULL, NULL, '', 1, 'ar', NULL, NULL, NULL, NULL, '2025-10-28 07:59:10', '2025-10-28 07:56:43'),
(43, '3223', '2298943966', 'N/A', '', 'dept_user', '', 0, '11', '', '', '', '', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-11-13 04:46:15', '2025-10-28 08:02:03'),
(45, '3386', '2319494346', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$dzfJufairC4nK/Spv.Klm.o0OytFxXPAwZTKJmuU2ZEeOrzprMqIm', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-10-28 08:35:47', '2025-10-28 08:35:47'),
(46, '4533', '2422445771', 'N/A', '', 'employee', '', 0, '1', '', '', '', '$2y$10$Ug7Xsu/5XzXVaaOy9mwZ8egWFFiQZnayX4rfiEJY6aRNRgx6YEoxa', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-10-28 16:05:34', '2025-10-28 08:44:09'),
(47, '5238', '1106196965', 'N/A', '', 'employee', '', 0, '16', '', '', '', '$2y$10$bH2bSWhsey4f81RPSILo/uu0KsnT1ur6/Txv1MkEQkaJNddSYRkfu', '', NULL, NULL, NULL, '', 1, 'ar', NULL, NULL, NULL, NULL, '2025-10-28 08:46:41', '2025-10-28 08:44:49'),
(48, '5381', '1104320310', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$PpXsqHV49m85aj71eZIY1eYcacOQhIC.xFhXjEYlwD897IEdzgFxa', '', NULL, NULL, NULL, '', 1, 'ar', NULL, NULL, NULL, NULL, '2025-10-28 09:17:34', '2025-10-28 09:13:58'),
(49, '3862', '2153812553', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$diBWtK3Bc8Gdx4nZ1.EeMO5ccEzhRrtw8f2F6buBSrG0IVOrzi5mi', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-10-28 09:21:26', '2025-10-28 09:21:26'),
(50, '5415', '1124259522', 'N/A', '', 'employee', '', 0, '15', '', '', '', '$2y$10$xnEnqI83J8JXHwhFnh9oVeXLgx9lF8mGf3bs2ccc6KUwq4nzzMe5i', '', NULL, NULL, NULL, '', 1, 'ar', NULL, NULL, NULL, NULL, '2025-10-28 10:42:12', '2025-10-28 10:42:00'),
(51, '2690', '2104546326', 'N/A', '', 'employee', '', 0, '15', '', '', '', '$2y$10$45xTVA6Vmwophc2Nep6eJe4ysSVwWaSQRbBlnY/2YCVbdZDJ1dHOW', '', NULL, NULL, NULL, '', 1, 'ar', NULL, NULL, NULL, NULL, '2025-10-28 10:46:25', '2025-10-28 10:46:08'),
(52, '5438', '1062193071', 'N/A', '', 'employee', '', 0, '14', '', '', '', '$2y$10$AqF1QCj6TKM2MuqAcTaie.AxiEfunxbg38.6vNOgKQo880NVaeV2S', '', NULL, NULL, NULL, '', 1, 'ar', NULL, NULL, NULL, NULL, '2025-10-28 10:47:30', '2025-10-28 10:46:57'),
(53, '5282', '1117212306', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$9RNcUcoa3Ya1Kk/M/.jBH.pWn/g1gZziNVk4C2vQTwTe2IUO4j1ri', '', NULL, NULL, NULL, '', 1, 'ar', NULL, NULL, NULL, NULL, '2025-10-28 17:12:49', '2025-10-28 17:12:10'),
(54, '4835', '2487665123', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$tp4Frv2D55DxKpOIAE1V3ets4TguBfQhUctiT0JBI6kb32ZKg3Sn6', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-10-29 01:40:44', '2025-10-29 01:30:04'),
(55, '4840', '2486350750', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$DjTvRzqrvyUHbFxepfr3z.3IPp4qqXItRI2eUSJYcgWK/2BqjXYH6', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-10-29 02:00:26', '2025-10-29 02:00:26'),
(56, '4930', '2494842236', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$.noAxeNEz0EYWkZrW9H2wehlyohlYepJa4msYtuI07RMHgzctgDoS', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-10-29 02:07:35', '2025-10-29 02:07:35'),
(57, '5204', '1063748808', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$ulLM6pfpUF17vXRbBeuSv.wKiYzxvUhsVvGkw1unR1RuLTLfXvcU2', '', NULL, NULL, NULL, '', 1, 'ar', NULL, NULL, NULL, NULL, '2025-11-06 03:43:26', '2025-10-29 02:12:22'),
(58, '2368', '2154158378', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$KYvm7mVdnkoRfHMr9TsFP.OpQvsqcDGls3U6wLS32wo9Bugn6kPsC', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-10-29 02:22:12', '2025-10-29 02:22:12'),
(59, '3899', '2364229464', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$41QzmvTcz.fbN7aZGigOJOI9vs22FEmGLt3I5HvCotH.y4s36Dd8u', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-10-29 02:24:09', '2025-10-29 02:24:09'),
(60, '4907', '1096601495', 'N/A', '', 'dept_user', '', 0, '11', '', '', '', '', '', NULL, NULL, NULL, '', 1, 'ar', NULL, NULL, NULL, NULL, '2025-11-13 04:46:15', '2025-10-29 02:58:58'),
(61, '4962', '1081668988', 'N/A', '', 'employee', '', 0, '15', '', '', '', '$2y$10$eTtdfujKxVa5VbDFsIpGruyAej4KN1qeeS7G9v2iVsKMzqTuPyEYG', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-10-29 03:06:09', '2025-10-29 03:06:09'),
(62, '5021', '1104448012', 'N/A', '', 'gr_officer', '', 0, '1', '', '', '', '$2y$10$opjjz0vqAzZwBJL5JNj4wuotLNY2dTWPlxVNpOVD5hTBiZoqoFiS.', '', NULL, NULL, NULL, '', 1, 'ar', NULL, NULL, NULL, NULL, '2025-11-13 05:26:55', '2025-10-29 06:56:34'),
(63, '2423', '2192076640', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$P8Ogon2STn8vsZjBpSTTyODorSmRpX/YnuZPjnFdm3doR609kZj2G', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-10-29 09:09:24', '2025-10-29 09:09:24'),
(64, '3265', '2307173209', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$C1u8bFvhyXZawPC5PdQ6Wext63UlBi0EjNXhUZU2ceVve5YUVX7Xm', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-10-30 00:39:44', '2025-10-30 00:39:44'),
(65, '3286', '2159446802', 'N/A', '', 'employee', '', 0, '1', '', '', '', '$2y$10$3/RvAOyy4wPIEnGA4CJjtO9JDWPyhZrmSr6OD2vyNDxYlyNbKI1eq', '', NULL, NULL, NULL, '', 1, 'ar', NULL, NULL, NULL, NULL, '2025-10-30 03:47:38', '2025-10-30 03:47:11'),
(66, '3836', '2361360676', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$s830wcUK033aqEz4pty8O.MqvR6VBDD3urNLxLbRJiGHgkefAkXEu', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-10-30 05:59:08', '2025-10-30 05:59:08'),
(67, '2522', '2197777176', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$CI5LosGt3OnHef13at2s..Ag5BSCPu5uW/bRiF07yYSK1JtPLbGFe', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-10-30 14:39:51', '2025-10-30 14:39:51'),
(68, '1685', '2088275033', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$CK5za1cvdxBTSn7NtT9CeOMIynGTp7vmhSsev8FROVU5H2eRYL9Fu', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-10-30 16:15:01', '2025-10-30 16:15:01'),
(69, '3280', '2308680830', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$PxuKYEnIUwmsWEh.i0hKvuv17g/McIDsJ5u8MGkkrMJjmzUUtPHlC', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-10-30 16:16:25', '2025-10-30 16:16:25'),
(70, '3767', '2359751142', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$FW6nkgrAXPqB3grFt08tmeNmEGe4jdCsxBju4WHbLGCaLsVO6mhyK', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-10-30 16:21:20', '2025-10-30 16:21:20'),
(71, '3144', '2292109176', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$nK3zpES1xExzaBPVY5A.4.WaXqbnbrHrS2YYZG1Y.SAqdZ5GjegEK', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-10-30 16:25:07', '2025-10-30 16:25:07'),
(72, '2642', '2207767613', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$xW1z9ZySuuGLfpLRCYZJKOLqNHu0Q4EaRHa9iKVRTgVoVFF9WmutC', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-10-30 16:27:29', '2025-10-30 16:27:29'),
(73, '4861', '2486787381', 'N/A', '', 'employee', '', 0, '15', '', '', '', '$2y$10$6y/L74ZFO4AVLLphrL4DxuTnh7AMW2CgcDiwHwI7W/.pG31Tnu2Ru', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-10-31 04:36:29', '2025-10-31 04:36:29'),
(74, '4860', '2486791987', 'N/A', '', 'employee', '', 0, '15', '', '', '', '$2y$10$LaxtQmPOuGDa7sd5ZQDSLuWNdfLs6RBlSbadEStcG1pV473mpPKDm', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-10-31 04:42:30', '2025-10-31 04:42:30'),
(75, '3705', '2352398669', 'N/A', '', 'employee', '', 0, '15', '', '', '', '$2y$10$/vOFPiYapBDvxyFl2Ghx8ONIzcgfSsuiigF5yq/N.FYMZwPovaKyC', '', NULL, NULL, NULL, '', 1, 'ar', NULL, NULL, NULL, NULL, '2025-11-03 06:29:31', '2025-10-31 07:58:16'),
(76, '3845', '2361360882', 'N/A', '', 'employee', '', 0, '15', '', '', '', '$2y$10$R82WNAlKYAfGN4R9ACmCeOzNs24r4Uo2AJFRPMvW9gVensYNqSUjW', '', NULL, NULL, NULL, '', 1, 'ar', NULL, NULL, NULL, NULL, '2025-10-31 15:07:49', '2025-10-31 14:01:15'),
(77, '4837', '2487457778', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$KeSCqdZREM7M/N60BJgp6uq8VJ3RM0k5Sxo.tpr1qc5.Kh94vRTEq', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-10-31 14:21:34', '2025-10-31 14:21:34'),
(78, '5073', '2517010605', 'N/A', '', 'employee', '', 0, '15', '', '', '', '$2y$10$BIz3D4CCUNP3uJl/DE6dhu4jnjgQyJ/VmSo3w3deY7B3cEy4v3MCG', '', NULL, NULL, NULL, '', 1, 'ar', NULL, NULL, NULL, NULL, '2025-10-31 15:00:09', '2025-10-31 14:59:30'),
(79, '3092', '2285567141', 'N/A', '', 'dept_user', '', 0, '11', '', '', '', '', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-11-13 04:46:15', '2025-11-01 03:14:45'),
(80, '4385', '2405991411', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$.3nq1dq8vT9jMf/E.brfM.o7Y6YfukA8LIHPIHAWY19Ki.CIn11d6', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-11-01 03:24:30', '2025-11-01 03:24:30'),
(81, '3665', '2184891493', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$tfSa/dwvV5A0ZLYyQavvi.lTgc0.QJv9v7JqZKxNZFCnLdiqE2Wwu', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-11-01 04:11:30', '2025-11-01 04:11:30'),
(82, '4899', '2492201955', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$wAagHqLi44dQl1szW77Iluu1CtwqodXp63V9pgUZ3TenMrJOZHYTa', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-11-01 04:11:52', '2025-11-01 04:11:39'),
(83, '4474', '2400798464', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$S/mHM7E0bT9/P3ZUpdi62OoPDMA6Bvwke6a/ZYC/NAulBI2.EqmGG', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-11-01 04:35:01', '2025-11-01 04:35:01'),
(84, '4567', '2430135588', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$d4u79awm55b1L9W4tVl3nehAlGVvJwhK8IJbI7iqTmQ7bMchHKDZq', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-11-01 05:08:28', '2025-11-01 05:08:28'),
(85, '3140', '2292191018', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$kAfTTCNI0mK9mflRybTBSeX.1SmSXlaIm/H5C1yJ5f5Lpw2.FMWkW', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-11-01 05:48:38', '2025-11-01 05:48:38'),
(86, '3815', '2360046748', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$g.9WA9BymTEsCFBPc9kfuO3Le6sGfOUh8pdzqw.1Wha.SIPlloW2S', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-11-01 05:51:52', '2025-11-01 05:50:08'),
(87, '4328', '2396777688', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$KJh6Zbt9wt3GUwKd4xQbgeGb51Bi5gcr99Bub.gIi3D5tdigN7RcC', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-11-01 06:37:05', '2025-11-01 06:37:05'),
(88, '4853', '2487658789', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$kEIULeIL.RErDd1dKm7zf.IkYTddMDbU4y8GMrlR.9WSOtAQRYoAC', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-11-01 07:54:33', '2025-11-01 07:54:33'),
(89, '3802', '2360353425', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$BkpgL.lNHI6he2g7WG/ysuWHcwic2ohNFMeFuX6/nNASXyrNAZqne', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-11-01 10:36:58', '2025-11-01 10:36:58'),
(90, '3801', '2360354001', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$9QhFRKRfl72CJ1qDlh8B/OUc55s5guBGhwSyu5rFV.apLlo6fE.1O', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-11-01 10:41:50', '2025-11-01 10:41:50'),
(91, '5402', '2556145304', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$FYhWHrV.PHLHaxpFjOfo0.msHjlpgGut3gD9mDy2r2OyG7kUWjknK', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-11-01 14:37:16', '2025-11-01 14:37:16'),
(92, '3013', '2270480375', 'N/A', '', 'employee', '', 0, '15', '', '', '', '$2y$10$GLMw1kWeon1hPNJBpR6Z8eetGujKCRT.bEjAcXpF6Ks/s/QvVOcO6', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-11-02 01:58:21', '2025-11-02 01:58:21'),
(93, '3231', '2299495560', 'N/A', '', 'employee', '', 0, '1', '', '', '', '$2y$10$oxKeWXN/so0JdQK46U9TT.uKhULflbcN4VjRQneiw5eE9oelTyUaq', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-11-02 02:18:05', '2025-11-02 02:18:05'),
(94, '3370', '2318099567', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$tXFCa.g3UU2eyR8EcCAFietyhGH3m4bqE61spIzF4IgvEoQbf8Qma', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-11-02 04:22:31', '2025-11-02 04:22:31'),
(95, '5407', '1106726654', 'N/A', '', 'employee', '', 0, '15', '', '', '', '$2y$10$lDOdFq4.ZVSNE5sKs2u84.w2xQ/nBF8bFTFSSODPTNlR07g7O8ZgG', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-11-02 09:04:30', '2025-11-02 04:25:58'),
(96, '3230', '2299495727', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$zS15PEfX33bg/JgtB4b.EOTZ86vWPYNsrEwUC8KSx3LmVaMiEc0TS', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-11-02 04:36:32', '2025-11-02 04:36:32'),
(97, '2843', '2241151741', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$Udlp6FanMZrNB3.XUHiRduDwSWt11GkkvD3zSOhomUD/MsAa8V/7G', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-11-04 03:17:01', '2025-11-02 04:48:35'),
(98, '5049', '2510974054', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$j.9yajAfXijzmk/89WZxmOX8QZP/amUqn3GGekdEwBZrshv.6lkem', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-11-02 10:28:18', '2025-11-02 06:32:45'),
(99, '3255', '2299232849', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$ChbqR0exgXriiKqyK3/SFubE8GWxXcZRfpkr3bxZrTfC3qd9jDSN2', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-11-02 09:10:16', '2025-11-02 09:10:16'),
(100, '3249', '2299231627', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$wcLGmKc02uqSv2Mtc7i86ur5hJ1ygb9bW1XayERbmIaqpOHgnX2Vi', '', NULL, NULL, NULL, '', 1, 'ar', NULL, NULL, NULL, NULL, '2025-11-02 10:53:01', '2025-11-02 10:52:45'),
(101, '4917', '2494169275', 'N/A', '', 'employee', '', 0, '1', '', '', '', '$2y$10$2jC6ghQOBuNcsWEBypaZzeXOpk/CIrxmHhnvLFwrM1Ro0I2TYDi3a', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-11-02 15:13:13', '2025-11-02 15:13:13'),
(102, '3812', '2360050088', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$GeA0/F9IqKRy7UBn3e.WNOslyOjEbSh1O7jgY1ffiy7MeScVW745i', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-11-03 03:12:58', '2025-11-03 03:12:58'),
(103, '1996', '2145835134', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$vOY5tLVEjJCdYr.o0.M9n.96rvp.oDIySQRurPqX/nUGC.FDb07kS', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-11-03 03:21:43', '2025-11-03 03:21:43'),
(104, '4172', '2382651723', 'N/A', '', 'employee', '', 0, '15', '', '', '', '$2y$10$7ugT9E5F0JVjQdPoMABHZeiqFakoZdbVY14vdhKya3unAlbRD2O4u', '', NULL, NULL, NULL, '', 1, 'ar', NULL, NULL, NULL, NULL, '2025-11-04 03:26:45', '2025-11-03 05:22:10'),
(105, '4532', '2423196019', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$WZf2cMbfs5PgFrlwBpgnROkfprJVaS6RoE1YsTT0JVhMDBY1VF64y', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-11-03 05:34:07', '2025-11-03 05:34:07'),
(106, '4858', '2486790278', 'N/A', '', 'employee', '', 0, '15', '', '', '', '$2y$10$Fmgey9i3C3zv8suCr8dJne2erfWaq9PLMesin2./.XRfSQTPKGtVi', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-11-03 10:09:25', '2025-11-03 10:09:25'),
(107, '4859', '2486792225', 'N/A', '', 'employee', '', 0, '15', '', '', '', '$2y$10$rhoFuBq2sN8gfahD6JZRn.NFhyXM/3GU.wg2ZoEhjHTGsoYCqXLsy', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-11-03 10:30:25', '2025-11-03 10:30:25'),
(108, '4857', '2486786284', 'N/A', '', 'employee', '', 0, '15', '', '', '', '$2y$10$6jkDJ4CRYWj0bK927TzpNuAS2hzuj0pK7/pB2wtC07046ZWOusDWS', '', NULL, NULL, NULL, '', 1, 'ar', NULL, NULL, NULL, NULL, '2025-11-03 10:34:07', '2025-11-03 10:32:27'),
(109, '4856', '2486791581', 'N/A', '', 'employee', '', 0, '15', '', '', '', '$2y$10$EoWzgXqgBEBqOjyS3igU/O5af3fN9IN8RjPNnsjFaux7an4Xarupa', '', NULL, NULL, NULL, '', 1, 'ar', NULL, NULL, NULL, NULL, '2025-11-04 03:25:54', '2025-11-04 03:25:42'),
(110, '2953', '2261825422', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$zEaAeaOLSGl.ZVQPJ0om7uroN0SErkfWVjyI6wYMw59hVGAHnTl9.', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-11-04 04:34:10', '2025-11-04 04:34:10'),
(111, '4897', '2483834343', 'N/A', '', 'employee', '', 0, '15', '', '', '', '$2y$10$Zmc63zefqcU4wvOjBsrBbORnW5wSF.JvaUgmK2.8SkYZkDsLwY/KK', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-11-05 02:13:47', '2025-11-05 02:13:47'),
(112, '3798', '2359392111', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$1q1W4juzU0EhlZPuMsBnX.vfo/5B1qJM07rng7DjxrVGJ0u99lA8q', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-11-05 10:13:46', '2025-11-05 10:13:46'),
(113, '5065', '2239958099', 'N/A', '', 'employee', '', 0, '1', '', '', '', '$2y$10$nTBkl6hzC3FAl5hASoauQ.ZTTNXmD6.jigUtYCGZ7TNiSRPvJUgNK', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-11-05 10:41:26', '2025-11-05 10:41:26'),
(114, '1500', '2060295322', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$U0LCYrTrlRfMu9GDdC4SSeqPAIS2YoyZbOdW9W2xLNNGgaHxFX.qC', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-11-05 12:56:15', '2025-11-05 12:56:15'),
(115, '3795', '2359391931', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$I8hInrE8HzopuY9C4IuQ8ed5P47E5HG2YhxEOFtmOkecv/H5PeZaa', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-11-05 13:00:58', '2025-11-05 13:00:58'),
(116, '4843', '2486333327', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$mENU3nW3lVaKH/FVVAG8ieFTTd4hP/UlopSMEG/2fJH6rlbt/jK3a', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-11-06 04:01:57', '2025-11-06 04:01:57'),
(117, '4157', '2382651541', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$4wujspYjLCfzNZTxK.5G1.pMoHgNCDJ/XjKba8kgqh8CDOMcWrKra', '', NULL, NULL, NULL, '', 1, 'ar', NULL, NULL, NULL, NULL, '2025-11-07 11:43:39', '2025-11-07 11:40:43'),
(118, '5401', '2557580731', 'N/A', '', 'dept_user', '', 0, '11', '', '', '', '', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-11-13 04:46:15', '2025-11-07 12:46:24'),
(132, '4838', '2486350313', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$Eh9GDxQ0mJGL6t01H5go3uNnG0FNk./k6fs4escRYCIJAbpuXLzs.', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-11-11 07:40:35', '2025-11-11 07:40:35'),
(130, '5218', '1121457582', 'N/A', '', 'employee', '', 0, '16', '', '', '', '$2y$10$FZZz.dma.WeBzIHuL3jdBOHg0m9PQ1NzeSDYn4IOmewFIRzhIuaNC', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-11-11 03:08:35', '2025-11-11 03:08:35'),
(128, '5244', '1128383005', 'N/A', '', 'employee', '', 0, '16', '', '', '', '$2y$10$9FZmmH6dHxpbEwlNneUz3ezgw1tpOi5CTIAqeERxEzaSohUqeDIwm', '', NULL, NULL, NULL, '', 1, 'ar', NULL, NULL, NULL, NULL, '2025-11-13 04:56:56', '2025-11-10 11:26:24'),
(119, '3090', '2284877301', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$DTMCr82qdxguh2XWFLiAXuj70M9zAvmlBRhzCd8v9NSm3Qx3p6qA.', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-11-09 15:37:14', '2025-11-09 15:37:14'),
(120, '5346', '2564292593', 'N/A', '', 'employee', '', 0, '15', '', '', '', '$2y$10$CbMsUUpDANOSW3VrD3IMa.f.Y4xY5Hajj7/u7XomMmFeRyXzlusLK', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-11-10 02:45:20', '2025-11-10 02:45:20'),
(121, '5243', '1101774352', 'N/A', '', 'employee', '', 0, '16', '', '', '', '$2y$10$flNjf3gUJxEO1dS6LViUKebai8tOfTRB0jz2eV03MKwxMIKQfS3G6', '', NULL, NULL, NULL, '', 1, 'ar', NULL, NULL, NULL, NULL, '2025-11-10 09:50:30', '2025-11-10 09:50:10'),
(122, '5221', '1107555219', 'N/A', '', 'employee', '', 0, '16', '', '', '', '$2y$10$Atu13zeDVKbE8YkhR93sL.fAor9dgaDhglHUh6bK1TjNpRRPgpJNW', '', NULL, NULL, NULL, '', 1, 'ar', NULL, NULL, NULL, NULL, '2025-11-10 09:53:32', '2025-11-10 09:53:25'),
(123, '5225', '1151067996', 'N/A', '', 'employee', '', 0, '16', '', '', '', '$2y$10$wA8oL0zZQMCRFBLSqS0Z.OHxFDi0JsjA1NDbU3zgnKXqv8IAb28jK', '', NULL, NULL, NULL, '', 1, 'ar', NULL, NULL, NULL, NULL, '2025-11-10 09:54:37', '2025-11-10 09:54:23'),
(124, '5213', '1132819598', 'N/A', '', 'employee', '', 0, '16', '', '', '', '$2y$10$SfAelEqjipj21W1BOYj2ceiMaODAwTcPdzohp8gA6efk0lgh9.x42', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-11-10 09:54:41', '2025-11-10 09:54:41'),
(125, '5241', '1105645947', 'N/A', '', 'employee', '', 0, '16', '', '', '', '$2y$10$ojB.WIbetucyyFaxCgP3l.8FtA8YrQpPhkSYeJCYt3rzseEgIGahW', '', NULL, NULL, NULL, '', 1, 'ar', NULL, NULL, NULL, NULL, '2025-11-10 09:56:01', '2025-11-10 09:55:24'),
(126, '5242', '1126023447', 'N/A', '', 'employee', '', 0, '16', '', '', '', '$2y$10$xCJnWJjG/LJ3B1sGE3nh2uBDotLdO8rLx2ku/Fmdy.Aye54xBlEC.', '', NULL, NULL, NULL, '', 1, 'ar', NULL, NULL, NULL, NULL, '2025-11-10 09:57:00', '2025-11-10 09:56:51'),
(127, '5235', '1115865568', 'N/A', '', 'employee', '', 0, '16', '', '', '', '$2y$10$n30DvfDcPCjIHN3XG2yx3.ePRu9SBtBQPB9l5E2jGYDSSNzkWHu4O', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-11-10 09:56:52', '2025-11-10 09:56:52'),
(129, '1764', '2101940845', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$7/ECOfDJzjAmjamzpx03NeALZCz.j8gxIjzAbqsEefij6EFHmTsC.', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-11-10 12:37:21', '2025-11-10 12:37:21'),
(131, '5345', '1093675344', 'N/A', '', 'dept_user', '', 0, '11', '', '', '', '', '', NULL, NULL, NULL, '', 1, 'ar', NULL, NULL, NULL, NULL, '2025-11-13 04:46:15', '2025-11-11 03:35:58'),
(133, '5327', '2564430466', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$Baaw3CzD/tKlfZmeRblFzeWx3EKYW01UXb0ZJRAAu3bfGgNL619fq', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-11-12 12:35:38', '2025-11-12 12:35:38'),
(134, '3254', '2299656005', 'N/A', '', 'employee', '', 0, '11', '', '', '', '$2y$10$erFm9UUfBO.OlEtMlr6EmOVzCNvoElpqcTfUVXtpzjZtJAfbtNHX.', '', NULL, NULL, NULL, '', 1, 'en', NULL, NULL, NULL, NULL, '2025-11-13 02:29:28', '2025-11-13 02:25:22');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_login`
--
ALTER TABLE `admin_login`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`),
  ADD UNIQUE KEY `id_iqama` (`id_iqama`,`emp_id`) USING BTREE,
  ADD KEY `otp_index` (`otp`,`otp_expiration`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_login`
--
ALTER TABLE `admin_login`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=135;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
