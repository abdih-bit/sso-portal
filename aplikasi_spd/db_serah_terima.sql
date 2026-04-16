-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 26, 2025 at 09:16 AM
-- Server version: 10.4.27-MariaDB
-- PHP Version: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_serah_terima`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `log_id` int(11) NOT NULL,
  `doc_id` varchar(100) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `details` text DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_log`
--

INSERT INTO `audit_log` (`log_id`, `doc_id`, `user_id`, `action`, `details`, `timestamp`) VALUES
(49, '3166243571', 7, 'Didaftarkan', 'Surat \"3166243571\" dibuat saat scan.', '2025-08-22 03:19:33'),
(50, '3166243571', 7, 'Diserahkan', 'Diserahkan oleh fauzi', '2025-08-22 03:19:35'),
(51, '3166243572', 7, 'Didaftarkan', 'Surat \"3166243572\" dibuat saat scan.', '2025-08-22 03:19:53'),
(52, '3166243572', 7, 'Diserahkan', 'Diserahkan oleh fauzi', '2025-08-22 03:19:55'),
(53, '3166243571', 10, 'Divalidasi', 'Divalidasi oleh abdi', '2025-08-22 03:21:07'),
(54, '3166243573', 7, 'Didaftarkan', 'Surat \"3166243573\" dibuat saat scan.', '2025-08-22 03:25:52'),
(55, '3166243573', 7, 'Diserahkan', 'Diserahkan oleh fauzi', '2025-08-22 03:25:55'),
(56, '10101010', 7, 'Didaftarkan', 'Surat \"10101010\" dibuat saat scan.', '2025-08-22 03:30:42'),
(57, '10101010', 7, 'Diserahkan', 'Diserahkan oleh fauzi', '2025-08-22 03:30:44'),
(58, '10101010', 10, 'Divalidasi', 'Divalidasi oleh abdi', '2025-08-22 03:31:29'),
(59, '10101011', 7, 'Didaftarkan', 'Surat \"10101011\" dibuat saat scan.', '2025-08-22 03:38:48'),
(60, '10101011', 7, 'Diserahkan', 'Diserahkan oleh fauzi', '2025-08-22 03:38:50'),
(70, '121212', 2, 'Didaftarkan', 'Surat \"121212\" dibuat saat scan.', '2025-08-22 07:34:45'),
(71, '121212', 2, 'Diserahkan', 'Diserahkan oleh admin', '2025-08-22 07:34:49'),
(72, '121212', 10, 'Divalidasi', 'Divalidasi oleh abdi', '2025-08-22 07:35:54'),
(73, '131313', 7, 'Didaftarkan', 'Surat \"131313\" dibuat saat scan.', '2025-08-22 07:38:06'),
(74, '131313', 7, 'Diserahkan', 'Diserahkan oleh fauzi', '2025-08-22 07:38:20'),
(75, '141414', 7, 'Didaftarkan', 'Surat \"141414\" dibuat saat scan.', '2025-08-22 07:38:31'),
(76, '151515', 7, 'Didaftarkan', 'Surat \"151515\" dibuat saat scan.', '2025-08-22 07:42:55'),
(77, '1616161', 2, 'Didaftarkan', 'Surat \"1616161\" dibuat saat scan.', '2025-08-22 07:47:17'),
(78, '171717', 2, 'Didaftarkan', 'Surat \"171717\" dibuat saat scan.', '2025-08-22 07:49:00'),
(79, '171717', 2, 'Diserahkan', 'Diserahkan oleh admin', '2025-08-22 07:54:39'),
(80, '1616161', 2, 'Diserahkan', 'Diserahkan oleh admin', '2025-08-22 07:55:06'),
(81, '1818181', 2, 'Didaftarkan', 'Surat \"1818181\" dibuat saat scan.', '2025-08-22 07:55:19'),
(82, '191919', 7, 'Didaftarkan', 'Surat \"191919\" dibuat saat scan.', '2025-08-22 08:00:50'),
(83, '1010101', 2, 'Didaftarkan', 'Surat \"1010101\" dibuat saat scan.', '2025-08-22 08:01:20'),
(84, '1012121', 2, 'Didaftarkan', 'Surat \"1012121\" dibuat saat scan.', '2025-08-22 08:05:19'),
(85, '1012121', 2, 'Diserahkan', 'Diserahkan oleh admin', '2025-08-22 08:05:45'),
(86, '12981928\\', 2, 'Didaftarkan', 'Surat \"12981928\\\" dibuat saat scan.', '2025-08-22 08:05:53'),
(87, '202020', 2, 'Didaftarkan', 'Surat \"202020\" dibuat saat scan.', '2025-08-22 08:13:45'),
(88, '212121', 2, 'Didaftarkan', 'Surat \"212121\" dibuat saat scan.', '2025-08-22 08:17:22'),
(89, '232323', 2, 'Didaftarkan', 'Surat \"232323\" dibuat saat scan.', '2025-08-22 08:22:23'),
(90, '232323', 2, 'Diserahkan', 'Diserahkan oleh admin', '2025-08-22 08:22:43'),
(91, '242424', 2, 'Didaftarkan', 'Surat \"242424\" dibuat saat scan.', '2025-08-22 08:24:46'),
(92, '1515151', 2, 'Didaftarkan', 'Surat \"1515151\" dibuat saat scan.', '2025-08-22 08:39:09'),
(93, '1515151', 2, 'Diserahkan', 'Diserahkan oleh admin', '2025-08-22 08:39:15'),
(94, '3131313', 2, 'Didaftarkan', 'Surat \"3131313\" dibuat saat scan.', '2025-08-22 08:55:31'),
(95, '292929', 2, 'Didaftarkan', 'Surat \"292929\" dibuat saat scan.', '2025-08-22 08:59:40'),
(96, '2198637', 2, 'Didaftarkan', 'Surat \"2198637\" dibuat saat scan.', '2025-08-22 09:03:32'),
(97, '7127152626', 2, 'Didaftarkan', 'Surat \"7127152626\" dibuat saat scan.', '2025-08-22 09:08:35'),
(98, '12831823828', 2, 'Didaftarkan', 'Surat \"12831823828\" dibuat saat scan.', '2025-08-23 02:39:26'),
(99, '9372837472', 2, 'Didaftarkan', 'Surat \"9372837472\" dibuat saat scan.', '2025-08-23 02:40:11'),
(100, '887878237', 2, 'Didaftarkan', 'Surat \"887878237\" dibuat saat scan.', '2025-08-23 02:48:04'),
(101, '81273878', 2, 'Didaftarkan', 'Surat \"81273878\" dibuat saat scan.', '2025-08-23 02:55:17'),
(102, '92384923', 2, 'Didaftarkan', 'Surat \"92384923\" dibuat saat scan.', '2025-08-23 03:09:47'),
(103, '82348237', 7, 'Didaftarkan', 'Surat \"82348237\" dibuat saat scan.', '2025-08-23 03:19:43'),
(104, '817237187', 7, 'Didaftarkan', 'Surat \"817237187\" dibuat saat scan.', '2025-08-23 03:29:49'),
(105, '817237187', 7, 'Diserahkan', 'Diserahkan oleh fauzi', '2025-08-23 03:30:17'),
(106, 'iqwqw8q8w', 7, 'Didaftarkan', 'Surat \"iqwqw8q8w\" dibuat saat scan.', '2025-08-23 03:30:21'),
(107, 'iqwqw8q8w', 7, 'Diserahkan', 'Diserahkan oleh fauzi', '2025-08-23 03:30:24'),
(108, '8238487238', 7, 'Didaftarkan', 'Surat \"8238487238\" dibuat saat scan.', '2025-08-23 03:30:34'),
(109, '817284712847', 2, 'Didaftarkan', 'Surat \"817284712847\" dibuat saat scan.', '2025-08-23 03:35:39'),
(110, '817284712847', 2, 'Diserahkan', 'Diserahkan oleh admin', '2025-08-23 03:35:43'),
(111, '8234823949', 2, 'Didaftarkan', 'Surat \"8234823949\" dibuat saat scan.', '2025-08-23 03:42:16'),
(112, '8234823949', 2, 'Diserahkan', 'Diserahkan oleh admin', '2025-08-23 03:42:21'),
(113, '8273847283748', 2, 'Didaftarkan', 'Surat \"8273847283748\" dibuat saat scan.', '2025-08-23 03:42:28'),
(114, '8273847283748', 2, 'Diserahkan', 'Diserahkan oleh admin', '2025-08-23 03:42:32'),
(115, '823748273', 2, 'Didaftarkan', 'Surat \"823748273\" dibuat saat scan.', '2025-08-23 03:51:11'),
(116, '923488', 2, 'Didaftarkan', 'Surat \"923488\" dibuat saat scan.', '2025-08-23 04:03:54'),
(117, '823748237492', 2, 'Didaftarkan', 'Surat \"823748237492\" dibuat saat scan.', '2025-08-23 04:19:04'),
(118, '823748237492', 2, 'Diserahkan', 'Diserahkan oleh admin', '2025-08-23 04:19:20'),
(119, '93892384928349', 2, 'Didaftarkan', 'Surat \"93892384928349\" dibuat saat scan.', '2025-08-23 04:19:26'),
(120, '93892384928349', 2, 'Diserahkan', 'Diserahkan oleh admin', '2025-08-23 04:19:29'),
(121, '23472673467', 2, 'Didaftarkan', 'Surat \"23472673467\" dibuat saat scan.', '2025-08-23 04:19:32'),
(122, '23472673467', 2, 'Diserahkan', 'Diserahkan oleh admin', '2025-08-23 04:19:37'),
(123, '827737613', 2, 'Didaftarkan', 'Surat \"827737613\" dibuat saat scan.', '2025-08-23 04:23:35'),
(124, '8482738472', 2, 'Didaftarkan', 'Surat \"8482738472\" dibuat saat scan.', '2025-08-23 04:29:58'),
(125, '8482738472', 2, 'Diserahkan', 'Diserahkan oleh admin', '2025-08-23 04:30:03'),
(126, '92349239', 2, 'Didaftarkan', 'Surat \"92349239\" dibuat saat scan.', '2025-08-23 04:30:07'),
(127, '92349239', 2, 'Diserahkan', 'Diserahkan oleh admin', '2025-08-23 04:30:09'),
(128, 'i234iu23i', 2, 'Didaftarkan', 'Surat \"i234iu23i\" dibuat saat scan.', '2025-08-23 04:30:11'),
(129, 'i234iu23i', 2, 'Diserahkan', 'Diserahkan oleh admin', '2025-08-23 04:30:15'),
(130, 'i23u4i2u34i', 2, 'Didaftarkan', 'Surat \"i23u4i2u34i\" dibuat saat scan.', '2025-08-23 04:30:17'),
(131, 'i23u4i2u34i', 2, 'Diserahkan', 'Diserahkan oleh admin', '2025-08-23 04:30:21'),
(132, '82734872384', 2, 'Didaftarkan', 'Surat \"82734872384\" dibuat saat scan.', '2025-08-23 04:30:24'),
(133, '82734872384', 2, 'Diserahkan', 'Diserahkan oleh admin', '2025-08-23 04:30:28'),
(134, '82374827384723', 2, 'Didaftarkan', 'Surat \"82374827384723\" dibuat saat scan.', '2025-08-23 04:30:31'),
(135, '82374827384723', 2, 'Diserahkan', 'Diserahkan oleh admin', '2025-08-23 04:30:47'),
(136, '2y371y2y1283', 2, 'Didaftarkan', 'Surat \"2y371y2y1283\" dibuat saat scan.', '2025-08-23 04:30:50'),
(137, '2y371y2y1283', 2, 'Diserahkan', 'Diserahkan oleh admin', '2025-08-23 04:30:52'),
(138, 'jhsdjfsjdhfsd', 2, 'Didaftarkan', 'Surat \"jhsdjfsjdhfsd\" dibuat saat scan.', '2025-08-23 04:33:02'),
(139, 'jhsdjfsjdhfsd', 2, 'Diserahkan', 'Diserahkan oleh admin', '2025-08-23 04:33:04'),
(140, '8237472634823', 2, 'Didaftarkan', 'Surat \"8237472634823\" dibuat saat scan.', '2025-08-23 04:33:07'),
(141, '8237472634823', 2, 'Diserahkan', 'Diserahkan oleh admin', '2025-08-23 04:35:55'),
(142, 'jashjahsd', 2, 'Didaftarkan', 'Surat \"jashjahsd\" dibuat saat scan.', '2025-08-23 04:40:31'),
(143, 'jashdjas', 2, 'Didaftarkan', 'Surat \"jashdjas\" dibuat saat scan.', '2025-08-23 04:40:54'),
(144, 'jashdjas', 2, 'Diserahkan', 'Diserahkan oleh admin', '2025-08-23 04:40:57'),
(145, 'jashdjasd', 2, 'Didaftarkan', 'Surat \"jashdjasd\" dibuat saat scan.', '2025-08-23 04:53:39'),
(146, 'uashdjasd', 2, 'Didaftarkan', 'Surat \"uashdjasd\" dibuat saat scan.', '2025-08-23 04:54:04'),
(147, 'jhjh', 2, 'Didaftarkan', 'Surat \"jhjh\" dibuat saat scan.', '2025-08-23 05:09:13'),
(148, 'jhjh', 2, 'Diserahkan', 'Diserahkan oleh admin', '2025-08-23 05:09:29'),
(149, 'jasdjas', 2, 'Didaftarkan', 'Surat \"jasdjas\" dibuat saat scan.', '2025-08-23 05:32:27'),
(150, 'jasdjas', 2, 'Diserahkan', 'Diserahkan oleh admin', '2025-08-23 05:32:31'),
(151, 'jajsdka', 2, 'Didaftarkan', 'Surat \"jajsdka\" dibuat saat scan.', '2025-08-23 06:03:37'),
(152, 'jashdja', 7, 'Didaftarkan', 'Surat \"jashdja\" dibuat saat scan.', '2025-08-23 06:07:47'),
(153, 'jashdja', 7, 'Diserahkan', 'Diserahkan oleh fauzi', '2025-08-23 06:07:53'),
(154, '817281723', 7, 'Didaftarkan', 'Surat \"817281723\" dibuat saat scan.', '2025-08-23 06:07:56'),
(155, '817281723', 7, 'Diserahkan', 'Diserahkan oleh fauzi', '2025-08-23 06:07:58'),
(156, '9123871283', 7, 'Didaftarkan', 'Surat \"9123871283\" dibuat saat scan.', '2025-08-23 06:08:00'),
(157, '9123871283', 7, 'Diserahkan', 'Diserahkan oleh fauzi', '2025-08-23 06:08:02'),
(158, '817238712', 7, 'Didaftarkan', 'Surat \"817238712\" dibuat saat scan.', '2025-08-23 06:08:05'),
(159, '817238712', 7, 'Diserahkan', 'Diserahkan oleh fauzi', '2025-08-23 06:08:08'),
(160, 'ksadkajsd', 7, 'Didaftarkan', 'Surat \"ksadkajsd\" dibuat saat scan.', '2025-08-23 06:08:20'),
(161, 'ksadkajsd', 7, 'Diserahkan', 'Diserahkan oleh fauzi', '2025-08-23 06:08:22'),
(162, '82389128d', 2, 'Didaftarkan', 'Surat \"82389128d\" dibuat saat scan.', '2025-08-23 06:12:44'),
(163, 'jsdfhajdfjas', 2, 'Didaftarkan', 'Surat \"jsdfhajdfjas\" dibuat saat scan.', '2025-08-23 13:32:13'),
(164, 'jsdfhajdfjas', 2, 'Diserahkan', 'Diserahkan oleh admin', '2025-08-23 13:32:25'),
(165, 'jajkshdkas', 2, 'Didaftarkan', 'Surat \"jajkshdkas\" dibuat saat scan.', '2025-08-23 13:32:27'),
(166, 'iasudia', 2, 'Didaftarkan', 'Surat \"iasudia\" dibuat saat scan.', '2025-08-23 13:38:38'),
(167, '8237482384', 2, 'Didaftarkan', 'Surat \"8237482384\" dibuat saat scan.', '2025-08-23 13:45:36'),
(168, '8237482384', 2, 'Diserahkan', 'Diserahkan oleh admin', '2025-08-23 13:45:41'),
(169, 'kasjdkasjd', 2, 'Didaftarkan', 'Surat \"kasjdkasjd\" dibuat saat scan.', '2025-08-23 14:49:12'),
(170, 'kasjdkasjd', 2, 'Diserahkan', 'Diserahkan oleh admin', '2025-08-23 14:50:32'),
(171, 'hjgjjfhj', 2, 'Didaftarkan', 'Surat \"hjgjjfhj\" dibuat saat scan.', '2025-08-23 14:50:35'),
(172, 'jkashdjahs', 2, 'Didaftarkan', 'Surat \"jkashdjahs\" dibuat saat scan.', '2025-08-25 01:04:20'),
(173, 'jkashdjahs', 2, 'Diserahkan', 'Diserahkan oleh admin', '2025-08-25 01:04:24'),
(174, '747646747', 2, 'Didaftarkan', 'Surat \"747646747\" dibuat saat scan.', '2025-08-25 01:08:44'),
(175, '747646747', 2, 'Diserahkan', 'Diserahkan oleh admin', '2025-08-25 01:08:46'),
(176, '82348723847', 2, 'Didaftarkan', 'Surat \"82348723847\" dibuat saat scan.', '2025-08-25 01:22:20'),
(177, '82348723847', 2, 'Diserahkan', 'Diserahkan oleh admin', '2025-08-25 01:22:40'),
(178, '982734982', 2, 'Didaftarkan', 'Surat \"982734982\" dibuat saat scan.', '2025-08-25 01:22:59'),
(179, '982734982', 2, 'Diserahkan', 'Diserahkan oleh admin', '2025-08-25 01:23:03'),
(180, '3274982374', 2, 'Didaftarkan', 'Surat \"3274982374\" dibuat saat scan.', '2025-08-25 01:24:33'),
(181, '12345678', 2, 'Didaftarkan', 'Surat \"12345678\" dibuat saat scan.', '2025-08-25 01:40:47'),
(182, '12345678', 2, 'Diserahkan', 'Diserahkan oleh admin', '2025-08-25 01:40:47'),
(183, '1234567890', 7, 'Didaftarkan', 'Surat \"1234567890\" dibuat saat scan.', '2025-08-25 01:42:05'),
(184, '1234567890', 7, 'Diserahkan', 'Diserahkan oleh fauzi', '2025-08-25 01:42:05'),
(185, '1234567890', 10, 'Divalidasi', 'Divalidasi oleh abdi', '2025-08-25 01:43:30'),
(186, '1234567', 7, 'Didaftarkan', 'Surat \"1234567\" dibuat saat scan.', '2025-08-25 01:44:00'),
(187, '1234567', 7, 'Diserahkan', 'Diserahkan oleh fauzi', '2025-08-25 01:44:00'),
(188, '1234567', 10, 'Divalidasi', 'Divalidasi oleh abdi', '2025-08-25 01:44:39'),
(189, '12345678', 10, 'Divalidasi', 'Divalidasi oleh abdi', '2025-08-25 01:46:26'),
(190, '1313131313', 7, 'Didaftarkan', 'Surat \"1313131313\" dibuat saat scan.', '2025-08-25 02:16:40'),
(191, '1313131313', 7, 'Diserahkan', 'Diserahkan oleh fauzi', '2025-08-25 02:16:40'),
(192, '00000000', 7, 'Didaftarkan', 'Surat \"00000000\" dibuat saat scan.', '2025-08-25 02:23:46'),
(193, '00000000', 7, 'Diserahkan', 'Diserahkan oleh fauzi', '2025-08-25 02:23:46'),
(194, '00000000', 10, 'Divalidasi', 'Divalidasi oleh abdi', '2025-08-25 02:24:27'),
(195, '1313131313', 10, 'Divalidasi', 'Divalidasi oleh abdi', '2025-08-25 02:24:40'),
(198, 'hsadadj', 7, 'Didaftarkan', 'Surat \"hsadadj\" dibuat saat scan.', '2025-08-25 04:11:49'),
(199, 'hsadadj', 7, 'Diserahkan', 'Diserahkan oleh fauzi', '2025-08-25 04:11:49'),
(200, '77777777', 7, 'Didaftarkan', 'Surat \"77777777\" dibuat saat scan.', '2025-08-25 04:11:59'),
(201, '77777777', 7, 'Diserahkan', 'Diserahkan oleh fauzi', '2025-08-25 04:11:59'),
(202, '77777777', 10, 'Divalidasi', 'Divalidasi oleh abdi', '2025-08-25 04:16:15'),
(208, '12761872368', 2, 'Didaftarkan', 'Surat \"12761872368\" dibuat saat scan.', '2025-08-25 08:03:34'),
(209, '12761872368', 2, 'Diserahkan', 'Diserahkan oleh admin', '2025-08-25 08:03:34'),
(210, '12531537', 2, 'Didaftarkan', 'Surat \"12531537\" dibuat saat scan.', '2025-08-25 08:22:43'),
(211, '12531537', 2, 'Diserahkan', 'Diserahkan oleh admin', '2025-08-25 08:22:43'),
(212, '1010101010', 2, 'Didaftarkan', 'Surat \"1010101010\" dibuat saat scan.', '2025-08-25 08:48:23'),
(213, '1010101010', 2, 'Diserahkan', 'Diserahkan oleh admin', '2025-08-25 08:48:23'),
(214, '3166243570', 7, 'Didaftarkan', 'Surat \"3166243570\" dibuat saat scan.', '2025-08-25 09:05:17'),
(215, '3166243570', 7, 'Diserahkan', 'Diserahkan oleh fauzi', '2025-08-25 09:05:17'),
(216, '3166243570', 10, 'Divalidasi', 'Divalidasi oleh abdi', '2025-08-25 09:08:02'),
(217, '9237482374', 7, 'Didaftarkan', 'Surat \"9237482374\" dibuat saat scan.', '2025-08-25 09:18:46'),
(218, '9237482374', 7, 'Diserahkan', 'Diserahkan oleh fauzi', '2025-08-25 09:18:46'),
(219, '8237487234', 7, 'Didaftarkan', 'Surat \"8237487234\" dibuat saat scan.', '2025-08-25 09:18:51'),
(220, '8237487234', 7, 'Diserahkan', 'Diserahkan oleh fauzi', '2025-08-25 09:18:51'),
(221, '8237487233', 2, 'Didaftarkan', 'Surat \"8237487233\" dibuat saat scan.', '2025-08-26 03:07:48'),
(222, '8237487233', 2, 'Diserahkan', 'Diserahkan oleh admin', '2025-08-26 03:07:48'),
(223, '9876543211', 7, 'Didaftarkan', 'Surat \"9876543211\" dibuat saat scan.', '2025-08-26 03:12:49'),
(224, '9876543211', 7, 'Diserahkan', 'Diserahkan oleh fauzi', '2025-08-26 03:12:49'),
(225, '8237487233', 10, 'Divalidasi', 'Divalidasi oleh abdi', '2025-08-26 03:14:05');

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `doc_id` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `documents`
--

INSERT INTO `documents` (`doc_id`, `title`, `created_at`) VALUES
('00000000', '00000000', '2025-08-25 02:23:46'),
('1010101', '1010101', '2025-08-22 08:01:20'),
('10101010', '10101010', '2025-08-22 03:30:42'),
('1010101010', '1010101010', '2025-08-25 08:48:23'),
('10101011', '10101011', '2025-08-22 03:38:48'),
('1012121', '1012121', '2025-08-22 08:05:19'),
('121212', '121212', '2025-08-22 07:34:45'),
('1234567', '1234567', '2025-08-25 01:44:00'),
('12345678', '12345678', '2025-08-25 01:40:47'),
('1234567890', '1234567890', '2025-08-25 01:42:05'),
('12531537', '12531537', '2025-08-25 08:22:43'),
('12761872368', '12761872368', '2025-08-25 08:03:34'),
('12831823828', '12831823828', '2025-08-23 02:39:26'),
('12981928\\', '12981928\\', '2025-08-22 08:05:53'),
('131313', '131313', '2025-08-22 07:38:06'),
('1313131313', '1313131313', '2025-08-25 02:16:40'),
('141414', '141414', '2025-08-22 07:38:31'),
('151515', '151515', '2025-08-22 07:42:55'),
('1515151', '1515151', '2025-08-22 08:39:09'),
('1616161', '1616161', '2025-08-22 07:47:17'),
('171717', '171717', '2025-08-22 07:49:00'),
('1818181', '1818181', '2025-08-22 07:55:19'),
('191919', '191919', '2025-08-22 08:00:50'),
('202020', '202020', '2025-08-22 08:13:45'),
('212121', '212121', '2025-08-22 08:17:22'),
('2198637', '2198637', '2025-08-22 09:03:32'),
('232323', '232323', '2025-08-22 08:22:23'),
('23472673467', '23472673467', '2025-08-23 04:19:32'),
('242424', '242424', '2025-08-22 08:24:46'),
('292929', '292929', '2025-08-22 08:59:40'),
('2y371y2y1283', '2y371y2y1283', '2025-08-23 04:30:50'),
('3131313', '3131313', '2025-08-22 08:55:31'),
('3166243570', '3166243570', '2025-08-25 09:05:17'),
('3166243571', '3166243571', '2025-08-22 03:19:33'),
('3166243572', '3166243572', '2025-08-22 03:19:53'),
('3166243573', '3166243573', '2025-08-22 03:25:52'),
('3274982374', '3274982374', '2025-08-25 01:24:33'),
('7127152626', '7127152626', '2025-08-22 09:08:35'),
('747646747', '747646747', '2025-08-25 01:08:44'),
('77777777', '77777777', '2025-08-25 04:11:59'),
('81273878', '81273878', '2025-08-23 02:55:17'),
('817237187', '817237187', '2025-08-23 03:29:49'),
('817238712', '817238712', '2025-08-23 06:08:05'),
('817281723', '817281723', '2025-08-23 06:07:56'),
('817284712847', '817284712847', '2025-08-23 03:35:39'),
('82348237', '82348237', '2025-08-23 03:19:43'),
('8234823949', '8234823949', '2025-08-23 03:42:16'),
('82348723847', '82348723847', '2025-08-25 01:22:20'),
('8237472634823', '8237472634823', '2025-08-23 04:33:07'),
('823748237492', '823748237492', '2025-08-23 04:19:04'),
('8237482384', '8237482384', '2025-08-23 13:45:36'),
('823748273', '823748273', '2025-08-23 03:51:10'),
('82374827384723', '82374827384723', '2025-08-23 04:30:31'),
('8237487233', '8237487233', '2025-08-26 03:07:48'),
('8237487234', '8237487234', '2025-08-25 09:18:51'),
('8238487238', '8238487238', '2025-08-23 03:30:34'),
('82389128d', '82389128d', '2025-08-23 06:12:44'),
('82734872384', '82734872384', '2025-08-23 04:30:24'),
('8273847283748', '8273847283748', '2025-08-23 03:42:28'),
('827737613', '827737613', '2025-08-23 04:23:35'),
('8482738472', '8482738472', '2025-08-23 04:29:58'),
('887878237', '887878237', '2025-08-23 02:48:04'),
('9123871283', '9123871283', '2025-08-23 06:08:00'),
('923488', '923488', '2025-08-23 04:03:54'),
('92349239', '92349239', '2025-08-23 04:30:07'),
('9237482374', '9237482374', '2025-08-25 09:18:46'),
('92384923', '92384923', '2025-08-23 03:09:47'),
('9372837472', '9372837472', '2025-08-23 02:40:11'),
('93892384928349', '93892384928349', '2025-08-23 04:19:26'),
('982734982', '982734982', '2025-08-25 01:22:59'),
('9876543211', '9876543211', '2025-08-26 03:12:49'),
('hjgjjfhj', 'hjgjjfhj', '2025-08-23 14:50:35'),
('hsadadj', 'hsadadj', '2025-08-25 04:11:49'),
('i234iu23i', 'i234iu23i', '2025-08-23 04:30:11'),
('i23u4i2u34i', 'i23u4i2u34i', '2025-08-23 04:30:17'),
('iasudia', 'iasudia', '2025-08-23 13:38:38'),
('iqwqw8q8w', 'iqwqw8q8w', '2025-08-23 03:30:21'),
('jajkshdkas', 'jajkshdkas', '2025-08-23 13:32:27'),
('jajsdka', 'jajsdka', '2025-08-23 06:03:37'),
('jasdjas', 'jasdjas', '2025-08-23 05:32:27'),
('jashdja', 'jashdja', '2025-08-23 06:07:47'),
('jashdjas', 'jashdjas', '2025-08-23 04:40:54'),
('jashdjasd', 'jashdjasd', '2025-08-23 04:53:39'),
('jashjahsd', 'jashjahsd', '2025-08-23 04:40:31'),
('jhjh', 'jhjh', '2025-08-23 05:09:13'),
('jhsdjfsjdhfsd', 'jhsdjfsjdhfsd', '2025-08-23 04:33:02'),
('jkashdjahs', 'jkashdjahs', '2025-08-25 01:04:20'),
('jsdfhajdfjas', 'jsdfhajdfjas', '2025-08-23 13:32:13'),
('kasjdkasjd', 'kasjdkasjd', '2025-08-23 14:49:12'),
('ksadkajsd', 'ksadkajsd', '2025-08-23 06:08:20'),
('uashdjasd', 'uashdjasd', '2025-08-23 04:54:04');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Super Admin','Admin Transport','Admin Invoice') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES
(2, 'admin', '$2y$10$nNYsRVFG79vPjU54c12Bm.ysGPh6dCYLs51TYhfbubebd/SvOsYKa', 'Super Admin'),
(7, 'fauzi', '$2y$10$vNaXPh47C3cxtaELNClnZOyUdmu8s1onqFnkI/h2QRvJF8vo1wdni', 'Admin Transport'),
(10, 'abdi', '$2y$10$sEs39WZuckku841UpzAgXevteU/0cvTZhCcc6JF5.6LXXUcOwPRM.', 'Admin Invoice');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `doc_id` (`doc_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`doc_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=226;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD CONSTRAINT `audit_log_ibfk_1` FOREIGN KEY (`doc_id`) REFERENCES `documents` (`doc_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `audit_log_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
