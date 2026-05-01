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
